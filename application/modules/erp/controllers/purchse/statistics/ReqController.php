<?php
/**
 * 2014-2-15 下午3:51:09
 * @author x.li
 * @abstract
 */
class Erp_Purchse_Statistics_ReqController extends Zend_Controller_Action
{
    public function indexAction()
    {
        
    }
    
    public function getreqAction()
    {
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        $option = isset($request['option']) ? $request['option'] : 'data';
        
        $condition = array(
                'key'       => isset($request['key']) ? $request['key'] : '',
                'state'     => isset($request['state']) && is_numeric($request['state']) ? $request['state'] : null,
                'applier'   => isset($request['applier']) ? $request['applier'] : null,
                'date_from' => isset($request['date_from']) ? $request['date_from'] : null,
                'date_to'   => isset($request['date_to']) ? $request['date_to'] : null,
                'complete'  => (isset($request['complete']) && $request['complete'] != 'null') ? $request['complete'] : 1,
                'type'      => (isset($request['type']) && $request['type'] != 'null') ? $request['type'] : null,
                'dept'      => (isset($request['dept']) && $request['dept'] != 'null') ? $request['dept'] : null,
                'page'      => isset($request['page']) ? $request['page'] : 1,
                'limit'     => isset($request['limit']) ? $request['limit'] : 0,
                'option'    => $option
        );
        
        $reqItems = new Erp_Model_Purchse_Req();
        
        $data = $reqItems->getReqStatistics($condition);
        //echo '<pre>';print_r($data);exit;
        if($option == 'csv'){
            $this->view->layout()->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);
        
            $h = new Application_Model_Helpers();
            $h->exportCsv($data, '采购申请统计');
        }else{
            echo Zend_Json::encode($data);
        }
        
        exit;
    }
}