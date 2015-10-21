<?php
/**
 * 2014-2-15 下午3:51:09
 * @author x.li
 * @abstract
 */
class Erp_Sale_Statistics_OrderController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $user_session = new Zend_Session_Namespace('user');
        
        $this->view->canReplyPlan = 0;
        $this->view->canReplySales = 0;
        
        $this->view->user_id = 0;
        
        if(isset($user_session->user_info)){
            $this->view->user_id = $user_session->user_info['user_id'];
            
            if (Application_Model_User::checkPermissionByRoleName('系统管理员')) {
                $this->view->canReplyPlan = 1;
                $this->view->canReplySales = 1;
            }
            
            if(Application_Model_User::checkPermissionByRoleName('计划人员')){
                $this->view->canReplyPlan = 1;
            }
            
            if(Application_Model_User::checkPermissionByRoleName('销售人员')){
                $this->view->canReplySales = 1;
            }
        }
    }
    
    public function getorderAction()
    {
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        $option = isset($request['option']) ? $request['option'] : 'data';
        
        $condition = array(
                'key'       => isset($request['key']) ? $request['key'] : '',
                'state'     => isset($request['state']) && is_numeric($request['state']) ? $request['state'] : null,
                'status'    => isset($request['status']) && is_numeric($request['status']) ? $request['status'] : null,
                'date_from' => isset($request['date_from']) ? $request['date_from'] : null,
                'date_to'   => isset($request['date_to']) ? $request['date_to'] : null,
                'type'      => (isset($request['type']) && $request['type'] != 'null') ? $request['type'] : null,
                'sales'     => (isset($request['sales']) && $request['sales'] != 'null') ? $request['sales'] : null,
                'page'      => isset($request['page']) ? $request['page'] : 1,
                'limit'     => isset($request['limit']) ? $request['limit'] : 0,
                'option'    => $option
        );
        
        $order = new Erp_Model_Sale_Order();
        
        $data = $order->getOrderStatistics($condition);
        
        if($option == 'csv'){
            $this->view->layout()->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);
            
            $h = new Application_Model_Helpers();
            $h->exportCsv($data, '销售订单统计');
        }else{
            echo Zend_Json::encode($data);
        }
        
        exit;
    }
    
    public function editorderstatusAction()
    {
        $result = array(
                'success'   => true,
                'info'      => ''
        );
        
        $request = $this->getRequest()->getParams();
         
        $id = isset($request['id']) ? $request['id'] : null;
        $order_status = isset($request['order_status']) ? $request['order_status'] : null;
        
        if($id && $order_status){
            $order = new Erp_Model_Sale_Order();
            $order->update(array('order_status' => $order_status), "id = ".$id);
        }else{
            $result['success'] = false;
            $result['info'] = '参数错误';
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    public function editinternalcodeAction()
    {
        $result = array(
                'success'   => true,
                'info'      => ''
        );
        
        $request = $this->getRequest()->getParams();
         
        $id = isset($request['id']) ? $request['id'] : null;
        $code_internal = isset($request['code_internal']) ? $request['code_internal'] : null;
        
        if($id){
            $item = new Erp_Model_Sale_Orderitems();
            $item->update(array('code_internal' => $code_internal), "id = ".$id);
        }else{
            $result['success'] = false;
            $result['info'] = '参数错误';
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    public function editsalesremarkAction()
    {
        $result = array(
                'success'   => true,
                'info'      => ''
        );
        
        $request = $this->getRequest()->getParams();
         
        $id = isset($request['id']) ? $request['id'] : null;
        $sales_remark = isset($request['sales_remark']) ? $request['sales_remark'] : null;
        
        if($id){
            $item = new Erp_Model_Sale_Orderitems();
            $item->update(array('sales_remark' => $sales_remark), "id = ".$id);
        }else{
            $result['success'] = false;
            $result['info'] = '参数错误';
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    public function replydeliverydateAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => ''
        );
        
        $request = $this->getRequest()->getParams();
        
        $id = isset($request['id']) ? $request['id'] : null;
        $date = isset($request['date']) && $request['date'] != '' ? $request['date'] : null;
        $remark = isset($request['remark']) ? $request['remark'] : null;
        
        if($id){
            $item = new Erp_Model_Sale_Orderitems();
            $itemData = $item->fetchRow("id = ".$id)->toArray();
            $user_session = new Zend_Session_Namespace('user');
            $user_id = $user_session->user_info['user_id'];
            $user_name = $user_session->user_info['user_name'];
            $now = date('Y-m-d H:i:s');
            
            $itemInfo = $item->fetchRow("id = ".$id)->toArray();
            
            $updateData = array(
                    'delivery_date_remark'        => $remark
            );
            
            if($itemInfo['delivery_date'] != $date){
                $updateData['delivery_date'] = $date;
                $updateData['delivery_date_update_time'] = $now;
                
                $params = Zend_Json::encode(array(
                        'code'                    => $itemData['code'],
                        'name'                    => $itemData['name'],
                        'description'             => $itemData['description'],
                        'delivery_date'           => $date,
                        'delivery_date_remark'    => $remark,
                        'user'                    => $user_name,
                        'time'                    => $now
                ));
                
                $data = array(
                        'user_id'         => $user_id,
                        'operate'         => '销售交期回复',
                        'target'          => 'Sale Order Statistics',
                        'target_id'       => $id,
                        'computer_name'   => gethostbyaddr(getenv("REMOTE_ADDR")),
                        'ip'              => $_SERVER['REMOTE_ADDR'],
                        'content'         => $params,
                        'time'            => $now
                );
                
                try {
                    $operate = new Application_Model_Log_Operate();
                
                    $operate->insert($data);
                } catch (Exception $e) {
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
                
                    echo Zend_Json::encode($result);
                
                    exit;
                }
            }
            
            $item->update($updateData, "id = ".$id);
        }else{
            $result['success'] = false;
            $result['info'] = '信息填写错误，交期回复失败！';
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
}