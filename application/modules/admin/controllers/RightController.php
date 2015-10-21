<?php
/**
 * 2013-7-16 下午8:55:17
 * @author x.li
 * @abstract 
 */
class Admin_RightController extends Zend_Controller_Action
{
    public function indexAction()
    {

    }
    
    public function menuordereditAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '编辑成功'
        );
        
        $request = $this->getRequest()->getParams();
        
        if(isset($request['json'])){
            $json = json_decode($request['json']);
            
            $now = date('Y-m-d H:i:s');
            $user_session = new Zend_Session_Namespace('user');
            $user = $user_session->user_info['user_id'];
            
            $id             = $json->id;// 原上级ID
            $oldParentId    = $json->oldParentId;// 原上级ID
            $newParentId    = $json->newParentId;// 新上级ID
            $order          = $json->order;// 顺序值
            
            if($id != '' && $oldParentId != '' && $newParentId != ''){
                $menu = new Home_Model_Menu();
                
                $menu->updateOrder($id, $oldParentId, $newParentId, $order);
            }
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    // 获取菜单角色成员
    public function getmenuroleAction()
    {
        $data = array();
        
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        $menu_id = isset($request['parentid']) ? $request['parentid'] : null;
        
        if($menu_id){
            $menurole = new Home_Model_Menurole();
            
            $data = $menurole->getRoleOfMenu($menu_id, 'id', false);
        }
        
        echo Zend_Json::encode($data);
        
        exit;
    }
    
    // 修改角色成员
    public function editmemberAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '编辑成功'
        );
        
        $request = $this->getRequest()->getParams();
        
        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['user_id'];
        
        $role_id = isset($request['editrole_id']) ? $request['editrole_id'] : null;
        $member_id = isset($request['member']) ? $request['member'] : null;
        
        if($role_id){
            if($member_id){
                $role = new Admin_Model_Role();
                
                // 用户从
                $userToArr = strpos($member_id, ',') ? explode(',', $member_id) : array($member_id);
                
                $member = new Admin_Model_Member();
                // 用户至
                $members = $member->fetchAll("role_id = ".$role_id);
                $userFromArr = array();
                
                foreach ($members as $m){
                    array_push($userFromArr, $m['user_id']);
                }
                
                // 新增用户
                $userNew = array();
                // 删除用户
                $userDel = array();
                
                // 获取新增用户
                foreach ($userToArr as $to){
                    if(!in_array($to, $userFromArr)){
                        array_push($userNew, $to);
                    }
                }
                
                // 获取删除用户
                foreach ($userFromArr as $from){
                    if(!in_array($from, $userToArr)){
                        array_push($userDel, $from);
                    }
                }
                
                // 为角色添加新用户
                foreach ($userNew as $new){
                    /**
                     * 添加“系统管理员”成员时（ROLE_ID=2），添加当前成员到所有角色中（“角色用户”除外）
                     * 否则只加入到当前角色
                     */
                    if($role_id == 2){
                        $roles = $role->getList();
                        
                        foreach ($roles as $r){
                            if($r['firstLevel']['name'] != '角色用户'){
                                try {
                                    if($member->fetchAll("role_id = ".$r['id']." and user_id = ".$new)->count() == 0){
                                        $member->addUserToRole($new, $r['id']);
                                    }
                                } catch (Exception $e) {
                                    $result['success'] = false;
                                    $result['info'] = $e->getMessage();
                                
                                    echo Zend_Json::encode($result);
                                
                                    exit;
                                }
                            }
                        }
                    }else{
                        try {
                            $member->addUserToRole($new, $role_id);
                        } catch (Exception $e) {
                            $result['success'] = false;
                            $result['info'] = $e->getMessage();
                        
                            echo Zend_Json::encode($result);
                        
                            exit;
                        }
                    }
                }
                
                // 为角色删除旧用户
                foreach ($userDel as $del){
                    // 不允许删除“系统管理员”角色中的用户名为“管理员”的用户
                    if($role_id != 2 && $del != 1){
                        try {
                            $member->deleteUserFromRole($del, $role_id);
                        } catch (Exception $e) {
                            $result['success'] = false;
                            $result['info'] = $e->getMessage();
                        
                            echo Zend_Json::encode($result);
                        
                            exit;
                        }
                    }
                }
            }else{
                $result['success'] = false;
                $result['info'] = '角色用户不能为空';
            }
        }else{
            $result['success'] = false;
            $result['info'] = '角色ID不能为空';
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    // 修改菜单角色成员
    public function editmenuroleAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '编辑成功'
        );
        
        $request = $this->getRequest()->getParams();
        
        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['user_id'];
        
        $menu_id = isset($request['editmenu_id']) ? $request['editmenu_id'] : null;
        $role_id = isset($request['menuRole']) ? $request['menuRole'] : null;
        
        if($menu_id){
            if($role_id){
                $menurole = new Home_Model_Menurole();
                
                // 角色从
                $roleToArr = strpos($role_id, ',') ? explode(',', $role_id) : array($role_id);
                // 角色至
                $roles = $menurole->fetchAll("menu_id = ".$menu_id);
                $roleFromArr = array();
                
                foreach ($roles as $r){
                    array_push($roleFromArr, $r['role_id']);
                }
                
                // 新增角色
                $roleNew = array();
                // 删除角色
                $roleDel = array();
                
                // 获取新增角色
                foreach ($roleToArr as $to){
                    if(!in_array($to, $roleFromArr)){
                        array_push($roleNew, $to);
                    }
                }
                
                // 获取删除角色
                foreach ($roleFromArr as $from){
                    if(!in_array($from, $roleToArr)){
                        array_push($roleDel, $from);
                    }
                }
                
                // 为菜单添加新角色
                foreach ($roleNew as $new){
                    try {
                        $menurole->addRoleToMenu($new, $menu_id);
                    } catch (Exception $e) {
                        $result['success'] = false;
                        $result['info'] = $e->getMessage();
                    
                        echo Zend_Json::encode($result);
                    
                        exit;
                    }
                }
                
                // 为菜单删除旧角色
                foreach ($roleDel as $del){
                    try {
                        $menurole->deleteRoleFromMenu($del, $menu_id);
                    } catch (Exception $e) {
                        $result['success'] = false;
                        $result['info'] = $e->getMessage();
                    
                        echo Zend_Json::encode($result);
                    
                        exit;
                    }
                }
            }else{
                $result['success'] = false;
                $result['info'] = '菜单角色不能为空';
            }
        }else{
            $result['success'] = false;
            $result['info'] = '菜单ID不能为空';
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    // 获取成员数据
    public function getmemberAction()
    {
        $data = array();
        
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        $parentid = isset($request['parentid']) ? $request['parentid'] : null;
        
        if($parentid){
            $member = new Admin_Model_Member();
            
            $data = $member->getMember($parentid, 'id', false);
        }
        
        echo Zend_Json::encode($data);
        
        exit;
    }
    
    // 获取角色数据
    public function getroleAction()
    {
        $data = array();

        // 请求参数
        $request = $this->getRequest()->getParams();

        $option = isset($request['option']) ? $request['option'] : 'list';

        $role = new Admin_Model_Role();

        if($option == 'list'){
            $data = $role->getList(false);
        }else{
            // 请求部门的层级ID
            $parentId = isset($request['parentId']) ? $request['parentId'] : 0;
            $active = isset($request['active']) ? $request['active'] : null;
            // 获取部门数据
            $data = $role->getData($parentId, $active);
        }

        // 将部门数据转为json格式并输出
        echo Zend_Json::encode($data);
        
        exit;
    }

    //添加、删除、修改角色
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
        $user = $user_session->user_info['user_id'];

        $json = json_decode($request['json']);

        $updated    = $json->updated;
        $inserted   = $json->inserted;
        $deleted    = $json->deleted;

        $role = new Admin_Model_Role();
        $member = new Admin_Model_Member();

        if(count($updated) > 0){
            foreach ($updated as $val){
                if($role->fetchAll("id != ".$val->id." and name = '".$val->name."'")->count() > 0){
                    $result['result'] = false;
                    $result['info'] = '角色：'.$val->name.' 重名';
                    
                    echo Zend_Json::encode($result);
                    
                    exit;
                }else{
                    $data = array(
                            'parentid'      => $val->parentId,
                            'name'          => $val->name,
                            'description'   => $val->description,
                            'remark'        => $val->remark,
                            'active'        => $val->active,
                            'update_time'   => $now,
                            'update_user'   => $user
                    );
    
                    $where = "id = ".$val->id;
    
                    try {
                        $role->update($data, $where);
                    } catch (Exception $e) {
                        $result['result'] = false;
                        $result['info'] = $e->getMessage();
    
                        echo Zend_Json::encode($result);
    
                        exit;
                    }
                }
            }
        }

        if(count($inserted) > 0){
            foreach ($inserted as $val){
                if($role->fetchAll("name = '".$val->name."'")->count() > 0){
                    $result['result'] = false;
                    $result['info'] = '角色：'.$val->name.' 重名';
                
                    echo Zend_Json::encode($result);
                
                    exit;
                }else{
                    $data = array(
                            'parentid'      => $val->parentId,
                            'name'          => $val->name,
                            'description'   => $val->description,
                            'remark'        => $val->remark,
                            'active'         => $val->active,
                            'create_time'   => $now,
                            'create_user'   => $user,
                            'update_time'   => $now,
                            'update_user'   => $user
                    );
                    
                    try{
                        $role_id = $role->insert($data);
                        
                        // 自动添加系统管理员到新角色中
                        $adminIds = $member->getUserids("系统管理员");
                        
                        foreach ($adminIds as $admin){
                            if($member->fetchAll("user_id = ".$admin." and role_id = ".$role_id)->count() == 0){
                                try{
                                    $member->insert(array('user_id' => $admin, 'role_id' => $role_id));
                                } catch (Exception $e){
                                    $result['result'] = false;
                                    $result['info'] = $e->getMessage();
                        
                                    echo Zend_Json::encode($result);
                        
                                    exit;
                                }
                            }
                        }
                    } catch (Exception $e){
                        $result['result'] = false;
                        $result['info'] = $e->getMessage();
                    
                        echo Zend_Json::encode($result);
                    
                        exit;
                    }
                }
            }
        }

        if(count($deleted) > 0){
            foreach ($deleted as $val){
                $adminIds = $member->getMemberWithNoManager($val->id);
                
                if(count($adminIds) == 0){
                    try {
                        $role->deleteRoleTreeData($val->id);
                    } catch (Exception $e){
                        $result['result'] = false;
                        $result['info'] = $e->getMessage();
                    
                        echo Zend_Json::encode($result);
                    
                        exit;
                    }
                }else{
                    $result['result'] = false;
                    $result['info'] = '角色ID '.$val->id.'有管理员以外的其它成员，请先删除其它成员';
                    
                    echo Zend_Json::encode($result);
                    
                    exit;
                }
            }
        }

        echo Zend_Json::encode($result);

        exit;
    }

    //添加、删除、修改菜单
    public function editmenuAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '编辑成功'
        );

        $request = $this->getRequest()->getParams();

        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['user_id'];

        $json = json_decode($request['json']);

        $updated    = $json->updated;
        $inserted   = $json->inserted;
        $deleted    = $json->deleted;
        
        $menu = new Home_Model_Menu();
        $menurole = new Home_Model_Menurole();

        if(count($updated) > 0){
            foreach ($updated as $val){
                if($menu->fetchAll("id != ".$val->id." and text = '".$val->text."'")->count() > 0){
                    $result['result'] = false;
                    $result['info'] = '菜单重名';
                    
                    echo Zend_Json::encode($result);
                    
                    exit;
                }else{
                    $data = array(
                            'parent_id'     => $val->parentId,
                            'text'          => $val->text,
                            'iconCls'       => $val->iconCls,
                            'tooltip'       => $val->tooltip,
                            'handler'       => $val->handler,
                            'url'           => $val->url,
                            'disabled'      => $val->disabled,
                            'params'        => $val->params,
                            'update_time'   => $now,
                            'update_user'   => $user
                    );
    
                    $where = "id = ".$val->id;
    
                    try {
                        $menu->update($data, $where);
                    } catch (Exception $e) {
                        $result['result'] = false;
                        $result['info'] = $e->getMessage();
    
                        echo Zend_Json::encode($result);
    
                        exit;
                    }
                }
            }
        }

        if(count($inserted) > 0){
            foreach ($inserted as $val){
                if($menu->fetchAll("text = '".$val->text."'")->count() > 0){
                    $result['result'] = false;
                    $result['info'] = '菜单重名';
                
                    echo Zend_Json::encode($result);
                
                    exit;
                }else{
                    $data = array(
                            'parent_id'     => $val->parentId,
                            'text'          => $val->text,
                            'iconCls'       => $val->iconCls,
                            'tooltip'       => $val->tooltip,
                            'handler'       => $val->handler,
                            'url'           => $val->url,
                            'disabled'      => $val->disabled,
                            'params'        => $val->params,
                            'create_time'   => $now,
                            'create_user'   => $user,
                            'update_time'   => $now,
                            'update_user'   => $user
                    );
                    
                    try{
                        $menu_id = $menu->insert($data);
                        
                        try{
                            $user_role = new Admin_Model_Role();
                            
                            $menurole->insert(array('menu_id' => $menu_id, 'role_id' => $user_role->getAdminId()));
                        } catch (Exception $e){
                            $result['result'] = false;
                            $result['info'] = $e->getMessage();
                        
                            echo Zend_Json::encode($result);
                        
                            exit;
                        }
                    } catch (Exception $e){
                        $result['result'] = false;
                        $result['info'] = $e->getMessage();
                    
                        echo Zend_Json::encode($result);
                    
                        exit;
                    }
                }
            }
        }

        if(count($deleted) > 0){
            foreach ($deleted as $val){
                if($menurole->fetchAll("menu_id = ".$val->id)->count() == 0){
                    try {
                        $menu->deleteMenuTreeData($val->id);
                    } catch (Exception $e){
                        $result['result'] = false;
                        $result['info'] = $e->getMessage();
                    
                        echo Zend_Json::encode($result);
                    
                        exit;
                    }
                }else{
                    $result['result'] = false;
                    $result['info'] = '角色ID'.$val->id.'已使用，不能删除';
                    
                    echo Zend_Json::encode($result);
                    
                    exit;
                }
            }
        }

        echo Zend_Json::encode($result);

        exit;
    }
}