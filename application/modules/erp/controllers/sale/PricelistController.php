<?php
/**
 * 2014-9-15 19:51:09
 * @author x.li
 * @abstract 
 */
class Erp_Sale_PricelistController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $user_session = new Zend_Session_Namespace('user');
        
        $this->view->user_id = 0;
        $this->view->admin = 0;
        
        if(isset($user_session->user_info)){
            $this->view->user_id = $user_session->user_info['user_id'];
        
            if(Application_Model_User::checkPermissionByRoleName('系统管理员') || Application_Model_User::checkPermissionByRoleName('客户管理员')){
                $this->view->admin = 1;
            }
        }
    }
    
    public function dataAction()
    {
        $data = array();
        
        $request = $this->getRequest()->getParams();
        
        $customer_id = isset($request['customer_id']) ? $request['customer_id'] : null;
        $code = isset($request['code']) ? $request['code'] : null;
        $show_inactive = isset($request['show_inactive']) && $request['show_inactive'] == 'true' ? true : false;
        
        $priceListModel = new Erp_Model_Sale_Priceitems();
        
        $data = $priceListModel->getPriceList($customer_id, $code, $show_inactive);
        
        echo Zend_Json::encode($data);
        
        exit;
    }
}