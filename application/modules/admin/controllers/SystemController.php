<?php
/**
 * 2013-7-16 下午8:48:49
 * @author x.li
 * @abstract 
 */
class Admin_SystemController extends Zend_Controller_Action
{
    public function indexAction()
    {
        
    }
    
    // 获取系统选项列表
    public function getoptionsAction()
    {
        $data = array();
        
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        $type = isset($request['type']) ? $request['type'] : null;
        
        $options = new Admin_Model_Options();
        
        $data = $options->getList($type);
        
        echo Zend_Json::encode($data);
        
        exit;
    }
}