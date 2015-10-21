<?php
/**
 * 2014-2-15 下午3:51:09
 * @author x.li
 * @abstract
 */
class Erp_Purchse_Statistics_SearchController extends Zend_Controller_Action
{
    public function indexAction()
    {
        
    }
    
    public function getsearchAction()
    {
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        $option = isset($request['option']) ? $request['option'] : 'data';
        
        $reqItems = new Erp_Model_Purchse_Reqitems();
        
        $data = $reqItems->getItemsState();
        
        echo Zend_Json::encode($data);
        
        exit;
    }
}