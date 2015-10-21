<?php
/**
 * 2014-4-1 下午9:47:41
 * @author x.li
 * @abstract 
 */
class Erp_Stock_StatisticsController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $user_session = new Zend_Session_Namespace('user');
        
        $this->view->user_id = 0;
        $this->view->accessViewTotal = 0;
        
        if(isset($user_session->user_info)){
            $this->view->user_id = $user_session->user_info['user_id'];
        
            if(Application_Model_User::checkPermissionByRoleName('系统管理员') || Application_Model_User::checkPermissionByRoleName('财务人员')){
                $this->view->accessViewTotal = 1;
            }
        }
    }
    
    public function statisticsAction()
    {
        $request = $this->getRequest()->getParams();
        
        $option = isset($request['option']) ? $request['option'] : 'data';
        
        if($option == 'data'){
            $doc_type = isset($request['doc_type']) && $request['doc_type'] != '' ? $request['doc_type'] : null;
            $transaction_type = isset($request['transaction_type']) && $request['transaction_type'] != '' ? $request['transaction_type'] : null;
        }else{
            $doc_type = isset($request['doc_type']) && $request['doc_type'] != '' ? json_encode(explode(',', $request['doc_type'])) : null;
            $transaction_type = isset($request['transaction_type']) && $request['transaction_type'] != '' ? json_encode(explode(',', $request['transaction_type'])) : null;
        }
        
        $warehouse_type = isset($request['warehouse_type']) && $request['warehouse_type'] != 'null' ? $request['warehouse_type'] : null;
        $warehouse = isset($request['warehouse']) && $request['warehouse'] != 'null' ? $request['warehouse'] : null;
        $key = isset($request['key']) ? $request['key'] : '';
        $date_from = isset($request['date_from']) ? $request['date_from'] : date('Y-m-01');
        $date_to = isset($request['date_to']) ? $request['date_to'] : date('Y-m-t');
        $page = isset($request['page']) ? $request['page'] : 1;
        $limit = isset($request['limit']) ? $request['limit'] : 0;
        
        $stock = new Erp_Model_Stock_Stock();
        
        // 查询条件
        $condition = array(
                'warehouse_type'    => $warehouse_type,
                'warehouse'         => $warehouse,
                'doc_type'          => $doc_type,
                'transaction_type'  => $transaction_type,
                'key'               => $key,
                'date_from'         => $date_from,
                'date_to'           => $date_to,
                'page'              => $page,
                'limit'             => $limit,
                'type'              => $option
        );
        
        $data = $stock->getStatisticsData($condition);
        
        if($option == 'csv'){
            $this->view->layout()->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);
        
            $h = new Application_Model_Helpers();
            $h->exportCsv($data, '库存交易统计');
        }else{
            echo Zend_Json::encode($data);
        }
        
        exit;
    }
}