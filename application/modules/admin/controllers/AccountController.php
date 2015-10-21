<?php
/**
 * 2013-7-16 下午8:49:47
 * @author x.li
 * @abstract 
 */
class Admin_AccountController extends Zend_Controller_Action
{
    public function indexAction()
    {
        
    }
    
    // 重置密码
    public function resetpwdAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '密码重置成功！（初始密码：123456）'
        );
        
        $request = $this->getRequest()->getParams();
        
        $user_id = isset($request['id']) ? $request['id'] : null;
        
        if($user_id){
            $user = new Application_Model_User();
            
            $employeeInfo = $user->getEmployeeInfoById($user_id);
            
            $pwd = md5($employeeInfo['number'].'123456');
            
            try {
                $user->update(array('password' => $pwd), "id = ".$user_id);
            } catch (Exception $e) {
                $result['success'] = false;
                $result['info'] = $e->getMessage();
            
                echo Zend_Json::encode($result);
            
                exit;
            }
        }else{
            $result['success'] = false;
            $result['info'] = '用户ID不能为空！';
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    // 修改账号信息
    public function editAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '编辑成功'
        );
        
        $request = $this->getRequest()->getParams();
        
        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user_id = $user_session->user_info['user_id'];
        
        $json = json_decode($request['json']);
        
        $updated = $json->updated;
        
        $account = new Application_Model_User();
        
        if(count($updated) > 0){
            foreach ($updated as $val){
                $data = array(
                        'active'        => $val->active,
                        'remark'        => $val->remark,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
                
                $where = "id = ".$val->id;
                
                try {
                    $account->update($data, $where);
                } catch (Exception $e) {
                    $result['result'] = false;
                    $result['info'] = $e->getMessage();
                
                    echo Zend_Json::encode($result);
                
                    exit;
                }
            }
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    // 获取角色列表
    public function getrolelistAction()
    {
        $data = array();
        
        $role = new Admin_Model_Role();
        
        $data = $role->getList();
        
        echo Zend_Json::encode($data);
        
        exit;
    }
    
    // 获取相关用户列表
    public function getrelateduserlistAction()
    {
        $data = array();
    
        $user_session = new Zend_Session_Namespace('user');
        $user_id = $user_session->user_info['user_id'];
        $user_name = '['.$user_session->user_info['user_number'].'] '.$user_session->user_info['user_name'];
        
        array_push($data, array('id' => $user_id, 'name' => $user_name));
        
        $user = new Application_Model_User();
        
        $subordinate = $user->getSubordinateUser($user_id);
        
        foreach ($subordinate as $s){
            array_push($data, array('id' => $s['id'], 'name' => $s['name']));
        }
        
        echo Zend_Json::encode($data);
        
        exit;
    }
    
    // 获取部门主管列表
    public function getmanageruserlistAction()
    {
        $data = array();
    
        $user_session = new Zend_Session_Namespace('user');
        $user_id = $user_session->user_info['user_id'];
        
        $user = new Application_Model_User();
        
        $manager = $user->getManagerUser($user_id);
        
        foreach ($manager as $m){
            array_push($data, array('id' => $m['id'], 'name' => $m['name']));
        }
        
        echo Zend_Json::encode($data);
        
        exit;
    }
    
    // 获取部门主管列表
    public function getleaderuserlistAction()
    {
        $data = array();
    
        $user = new Application_Model_User();
        
        $leader = $user->getLeaderList();
        
        foreach ($leader as $m){
            array_push($data, array('id' => $m['id'], 'name' => $m['name']));
        }
        
        echo Zend_Json::encode($data);
        
        exit;
    }
    
    // 获取用户列表
    public function getuserlistAction()
    {
        $data = array();
    
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        $key = isset($request['key']) ? $request['key'] : null;
        $role_id = (isset($request['role_id']) && $request['role_id'] != '[]') ? $request['role_id'] : null;
        $filter_id = isset($request['filter_id']) ? $request['filter_id'] : null;
        $csv = isset($request['csv']) ? ($request['csv'] != 'false' ? true : false) : false;
        
        $condition = array(
                'key'       => $key,
                'role_id'   => $role_id,
                'csv'       => $csv,
                'filter_id' => $filter_id
        );
        
        $user = new Application_Model_User();
    
        if(isset($request['active'])){
            $data = $user->getUserList($condition, $request['active']);
        }else{
            $data = $user->getUserList($condition, 1, false);
        }
        
        echo Zend_Json::encode($data);
    
        exit;
    }
    
    // 导出CSV
    public function exportcsvAction()
    {
        $user = new Application_Model_User();
        $data = $user->getUserList(array('csv' => true));
        
        $this->view->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        
        $h = new Application_Model_Helpers();
        $h->exportCsv($data);
    }
    
    // 获取角色信息
    public function getroleAction()
    {
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        $option = isset($request['option']) ? $request['option'] : 'list';
        
        $type = new Hra_Model_Newstype();
        
        if($option == 'list'){
            echo Zend_Json::encode($type->getList());
        }else{
            echo Zend_Json::encode($type->getData());
        }
        
        exit;
    }
}