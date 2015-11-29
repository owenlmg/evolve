<?php
/**
 * 2014-2-15 下午3:51:09
 * @author x.li
 * @abstract
 */
class Erp_Purchse_Statistics_OrderController extends Zend_Controller_Action
{
    public function indexAction()
    {
    	$user_session = new Zend_Session_Namespace('user');
    	
    	$this->view->canReply = 0;
    	
    	$this->view->user_id = 0;
    	
    	if(isset($user_session->user_info)){
    		$this->view->user_id = $user_session->user_info['user_id'];
    		
    		if(Application_Model_User::checkPermissionByRoleName('系统管理员') || Application_Model_User::checkPermissionByRoleName('采购人员') || Application_Model_User::checkPermissionByRoleName('财务人员')){
    			$this->view->canReply = 1;
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
                'date_from' => isset($request['date_from']) ? $request['date_from'] : null,
                'date_to'   => isset($request['date_to']) ? $request['date_to'] : null,
                'complete'  => (isset($request['complete']) && $request['complete'] != 'null') ? $request['complete'] : 1,
                'type'      => (isset($request['type']) && $request['type'] != 'null') ? $request['type'] : null,
                'dept'      => (isset($request['dept']) && $request['dept'] != 'null') ? $request['dept'] : null,
                'buyer'     => (isset($request['buyer']) && $request['buyer'] != 'null') ? $request['buyer'] : null,
                'page'      => isset($request['page']) ? $request['page'] : 1,
                'limit'     => isset($request['limit']) ? $request['limit'] : 0,
                'option'    => $option
        );
        
        $order = new Erp_Model_Purchse_Order();
        
        $data = $order->getOrderStatistics($condition);
        
        if($option == 'csv'){
            $this->view->layout()->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);
            
            $h = new Application_Model_Helpers();
            $h->exportCsv($data, '采购订单统计');
        }else{
            echo Zend_Json::encode($data);
        }
        
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
    		$item = new Erp_Model_Purchse_Orderitems();
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
    		            'operate'         => '采购交期回复',
    		            'target'          => 'Purchse Order Statistics',
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