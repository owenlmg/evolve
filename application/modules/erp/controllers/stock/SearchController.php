<?php
/**
 * 2014-4-1 下午9:47:41
 * @author x.li
 * @abstract 
 */
class Erp_Stock_SearchController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $user_session = new Zend_Session_Namespace('user');
        
        $this->view->admin = 0;
        $this->view->user_id = 0;
        
        if(isset($user_session->user_info)){
            $this->view->user_id = $user_session->user_info['user_id'];
        
            if(Application_Model_User::checkPermissionByRoleName('系统管理员')){
                $this->view->admin = 0;
            }
        }
    }
    
    public function searchAction()
    {
        $request = $this->getRequest()->getParams();
        
        $option = isset($request['option']) ? $request['option'] : 'data';
        
        $stock = new Erp_Model_Stock_Stock();
        
        // 查询条件
        $condition = array(
                'key'               => isset($request['key']) ? $request['key'] : '',
                'page'              => isset($request['page']) ? $request['page'] : 1,
                'limit'             => isset($request['limit']) ? $request['limit'] : 0,
                'type'              => $option
        );
        
        $data = $stock->getSearchData($condition);
        
        if($option == 'csv'){
            $this->view->layout()->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);
        
            $h = new Application_Model_Helpers();
            $h->exportCsv($data, '库存数据');
        }else{
            echo Zend_Json::encode($data);
        }
        
        exit;
    }
}