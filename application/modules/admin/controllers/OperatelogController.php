<?php
/**
 * 2013-10-16 下午11:24:19
 * @author x.li
 * @abstract 
 */
class Admin_OperatelogController extends Zend_Controller_Action
{
    public function indexAction()
    {
        
    }
    
    public function gettypeAction()
    {
        $type = new Application_Model_Log_Operate();
        
        echo Zend_Json::encode($type->getType());
        
        exit;
    }
    
    public function getdataAction()
    {
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        $option = isset($request['option']) ? $request['option'] : null;
        
        $condition = array(
                'key'       => isset($request['key']) ? $request['key'] : '',
                'operate'   => isset($request['operate']) ? $request['operate'] : '',
                'date_from' => isset($request['date_from']) ? $request['date_from'] : date('Y-m-01'),
                'date_to'   => isset($request['date_to']) ? $request['date_to'] : date('Y-m-t'),
                'page'      => isset($request['page']) ? $request['page'] : 1,
                'limit'     => isset($request['limit']) ? $request['limit'] : 0,
                'option'    => $option
        );
        
        $operate = new Application_Model_Log_Operate();
        
        $data = $operate->getData($condition);
        
        if($option == 'csv'){
            $this->view->layout()->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);
        
            $h = new Application_Model_Helpers();
            $h->exportCsv($data);
        }else{
            echo Zend_Json::encode($data);
        }
        
        exit;
    }
}