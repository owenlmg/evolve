<?php
/**
 * 2014-3-30 下午3:46:44
 * @author x.li
 * @abstract 
 */
class Erp_SettingController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $user_session = new Zend_Session_Namespace('user');
        $user_id = $user_session->user_info['user_id'];
        $user_number = $user_session->user_info['user_number'];
        
        $this->view->admin = 0;
        
        if(isset($user_session->user_info)){
            $this->view->user_id = $user_id;
        
            if(Application_Model_User::checkPermissionByRoleName('系统管理员') || Application_Model_User::checkPermissionByRoleName('人事管理员')){
                $this->view->admin = 1;
            }
        }
        
        $time = time();
        $this->view->default_date_from = date('Y-m-01',strtotime(date('Y',$time).'-'.(date('m',$time)-1).'-01'));
        $this->view->default_date_to = date('Y-m-t',strtotime(date('Y',$time).'-'.(date('m',$time)+1).'-01'));
    }
    
    
}