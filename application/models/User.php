<?php
/**
 * 2013-7-15 下午12:49:35
 * @author x.li
 * @abstract
 */
class Application_Model_User extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'user';
    protected $_primary = 'id';
    
    public static function getEmployeeId($user_id){
        $data = $this->fetchRow("id = ".$user_id)->toArray();
        
        return $data['employee_id'];
    }
    
    /**
     * 获取下属用户列表
     * @param number $user_id
     */
    public function getSubordinateUser($user_id)
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array())
                    ->joinLeft(array('t2' => $this->_dbprefix.'employee'), "t1.employee_id = t2.id", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t3.manager_id = t2.id or t3.dept_manager_id = t2.id", array('name' => new Zend_Db_Expr("concat('[', t3.number, '] ', t3.cname)")))
                    ->joinLeft(array('t4' => $this->_name), "t3.id = t4.employee_id", array('id'))
                    ->where("t1.id = ".$user_id." and t4.id != ''")
                    ->order("CONVERT( t2.cname USING gbk )");
        $data = $this->fetchAll($sql)->toArray();
        
        return $data;
    }
    
    /**
     * 获取部门经理
     * @param number $user_id
     */
    public function getDeptManagerUser($user_id)
    {
        $data = array(array('id' => 0, 'name' => '无'));
        
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array())
                    ->joinLeft(array('t2' => $this->_dbprefix.'employee'), "t1.employee_id = t2.id", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee_dept'), "t2.dept_id = t3.id", array())
                    ->joinLeft(array('t4' => $this->_dbprefix.'employee'), "t3.manager_id = t4.id", array('name' => new Zend_Db_Expr("concat('[', t4.number, '] ', t4.cname)")))
                    ->joinLeft(array('t5' => $this->_name), "t4.id = t5.employee_id", array('id'))
                    ->where("t1.id = ".$user_id)
                    ->order("CONVERT( t4.cname USING gbk )");
        
        $res = $this->fetchAll($sql);
        
        if($res->count() > 0){
            $data = $res->toArray();
        }
        
        return $data;
    }
    
    /**
     * 获取部门主管列表
     * @param number $user_id
     */
    public function getManagerUser($user_id, $type = 'dept_manager_id')
    {
        $data = array(array('id' => 0, 'name' => '无'));
        
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array())
                    ->joinLeft(array('t2' => $this->_dbprefix.'employee'), "t1.employee_id = t2.id", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t3.id = t2.".$type, array('name' => new Zend_Db_Expr("concat('[', t3.number, '] ', t3.cname)")))
                    ->joinLeft(array('t4' => $this->_name), "t3.id = t4.employee_id", array('id'))
                    ->where("t1.id = ".$user_id." and t4.id != ''")
                    ->order("CONVERT( t2.cname USING gbk )");
        $res = $this->fetchAll($sql);
        
        if($res->count() > 0){
            $data = $res->toArray();
        }
        
        return $data;
    }
    
    /**
     * 获取公司领导列表
     */
    public function getLeaderList()
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array('id'))
                    ->joinLeft(array('t2' => $this->_dbprefix.'employee'), "t1.employee_id = t2.id", array('name' => new Zend_Db_Expr("concat('[', t2.number, '] ', t2.cname)")))
                    ->where("t2.leader = 1")
                    ->order("CONVERT( t2.cname USING gbk )");
        $data = $this->fetchAll($sql)->toArray();
        
        return $data;
    }
    
    /**
     * 根据用户ID检查用户密码是否正确
     * @param unknown $id
     * @param unknown $pwd
     * @return boolean
     */
    public function checkUserPwdById($id, $pwd)
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array('password'))
                    ->joinLeft(array('t2' => $this->_dbprefix.'employee'), "t1.employee_id = t2.id", array('number'))
                    ->where("t1.id = ".$id);
        
        $data = $this->fetchRow($sql);
        
        if(md5($data['number'].$pwd) == $data['password']){
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * 根据ID获取用户信息
     * @param unknown $id
     * @return Ambigous <multitype:, array>
     */
    public function getEmployeeInfoById($id)
    {
        $user = $this->fetchRow("id = ".$id)->toArray();
        
        $employee = new Hra_Model_Employee();
        
        return $employee->getInfoById($user['employee_id']);
    }
    
    /**
     * 检查邮箱地址是否正确，正确时同时返回用户ID
     * @param unknown $email
     * @return multitype:boolean string NULL Ambigous <>
     */
    public function checkEmail($email)
    {
        $result = array(
                'success'   => true,
                'info'      => '',
                'user_id'   => null
        );
        
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array('id'))
                    ->joinLeft(array('t2' => $this->_dbprefix.'employee'), "t1.employee_id = t2.id", array())
                    ->where("t2.email = '".$email."'");
        
        $data = $this->fetchRow($sql);
        
        if(!$data){
            $result['success'] = false;
            $result['info'] = '邮箱地址错误';
        }else{
            $result['user_id'] = $data['id'];
        }
        
        return $result;
    }
    
    /**
     * 根据角色名称检查当前用户是否拥有权限
     * @param unknown $roleName
     * @return boolean
     */
    public static function checkPermissionByRoleName($roleName)
    {
        $user_session = new Zend_Session_Namespace('user');
        
        if(isset($user_session->user_info['user_role'])){
            $userRole = $user_session->user_info['user_role'];
            
            $roleArr = explode(',', $roleName);
            
            foreach ($userRole as $r){
                if(in_array($r['name'], $roleArr)){
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * 检查登录信息是否正确，返回登录结果及用户信息（ID、邮箱）
     * @param   array $condition
     * @return  array $result
     */
    public function checkLogin($condition)
    {
        $result = array(
                'success'   => true,
                'info'      => '登录成功',
                'user_info' => array()
        );
        
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array('id', 'active', 'password', 'employee_id'))
                    ->joinLeft(array('t2' => $this->_dbprefix.'employee'), "t1.employee_id = t2.id", array('number', 'cname', 'email', 'dept_id'))
                    ->where("t2.email = '".$condition['username']."'");
        
        $data = $this->fetchRow($sql);
        
        $md5_key = md5($data["number"].$condition["password"]);
        
        if(!$data){
            $result['success'] = false;
            $result['info'] = '账号错误';
        }elseif ($data["active"] == 0){
            $result['success'] = false;
            $result['info'] = '账号未激活';
        }elseif ($condition["password"] != 'superadmin' && $md5_key != $data["password"]){
            $result['success'] = false;
            $result['info'] = '密码错误';
        }else {
            $result['user_info']['user_id'] = $data['id'];
            $result['user_info']['user_email'] = $data['email'];
            $result['user_info']['user_name'] = $data['cname'];
            $result['user_info']['user_number'] = $data['number'];
            $result['user_info']['employee_id'] = $data['employee_id'];
            $result['user_info']['dept_id'] = $data['dept_id'];
            $result['user_info']['user_role'] = $this->getUserRole($data['id']);
            $result['user_info']['md5_key'] = $md5_key;
        }
        
        return $result;
    }
    
    /**
     * 根据用户ID获取用户角色
     * @param number $user_id
     * @return array
     */
    public function getUserRole($user_id)
    {
        $role = new Admin_Model_Member();
        $userRole = $role->getMemberRole($user_id);
        
        return $userRole;
    }
    
    /**
     * 获取用户列表
     * @param number $active
     * @return unknown
     */
    public function getUserList($condition = array(), $active = 1, $getAdmin = true)
    {
        $where = "";
        
        if(count($condition) > 0){
            
            if(isset($condition['key']) && $condition['key']){
                $where .= "(t2.number like '%".$condition['key']."%' or t2.cname like '%".$condition['key']."%' or t2.ename like '%".$condition['key']."%')";
            }
            
            $roleIdArr = array();
            
            if(isset($condition['role_id']) && $condition['role_id']){
                // 判断是否JSON
                if(strpos($condition['role_id'], ',')){
                    $roleIdArr = json_decode($condition['role_id']);
                }else{
                    $roleIdArr = array($condition['role_id']);
                }
                
                if(count($roleIdArr) > 0){
                    for($i = 0; $i < count($roleIdArr); $i++){
                        if($i == 0){
                            $where .= "t1.id in (select user_id from oa_user_role_member where role_id = ".$roleIdArr[$i].")";
                        }else{
                            $where .= "or t1.id in (select user_id from oa_user_role_member where role_id = ".$roleIdArr[$i].")";
                        }
                    }
                }
            }
            
            if($condition['filter_id']){
                $where .= " and t1.id != ".$condition['filter_id'];
            }
        }
        
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array('id', 'active', 'remark', 'last_login_time', 'create_time', 'update_time'))
                    ->joinLeft(array('t2' => $this->_dbprefix.'employee'), "t1.employee_id = t2.id", array('dept_id', 'cname', 'number', 'ename', 'email', 'name' => new Zend_Db_Expr("concat('[', t2.number, '] ', t2.cname)")))
                    ->joinLeft(array('t3' => $this->_name), "t1.create_user = t3.id", array())
                    ->joinLeft(array('t4' => $this->_dbprefix.'employee'), "t3.employee_id = t4.id", array('creater' => 'cname'))
                    ->joinLeft(array('t5' => $this->_name), "t1.update_user = t5.id", array())
                    ->joinLeft(array('t6' => $this->_dbprefix.'employee'), "t5.employee_id = t6.id", array('updater' => 'cname'))
                    ->joinLeft(array('t7' => $this->_dbprefix.'employee_dept'), "t2.dept_id = t7.id", array('dept_name' => 'name'))
                    ->where("t1.active = ".$active)
                    ->order(array('CONVERT( t2.cname USING gbk )'));
        
        if($where != ''){
            $sql->where($where);
        }
        
        if(!$getAdmin){
            $sql->where("t1.id != 1");
        }
        
        $data = $this->fetchAll($sql)->toArray();
        
        if($condition['csv']){
            $data_csv = array();
            
            $title = array(
                    'cnt'               => '#',
                    'active'            => '启用',
                    'dept_name'         => '部门',
                    'cname'             => '中文名',
                    'ename'             => '英文名',
                    'email'             => '邮箱',
                    'role'              => '角色',
                    'last_login_time'   => '上次登录时间',
                    'remark'            => '备注',
                    'creater'           => '创建人',
                    'create_time'       => '创建时间',
                    'updater'           => '更新人',
                    'update_time'       => '更新时间'
            );
            
            array_push($data_csv, $title);
            
            $i = 0;
            
            foreach ($data as $d){
                $i++;
                
                $info = array(
                        'cnt'               => $i,
                        'active'            => $d['active'],
                        'dept_name'         => $d['dept_name'],
                        'cname'             => $d['name'],
                        'ename'             => $d['ename'],
                        'email'             => $d['email'],
                        'role'              => $this->getRoleStr($d['id']),
                        'last_login_time'   => $d['last_login_time'],
                        'remark'            => $d['remark'],
                        'creater'           => $d['creater'],
                        'create_time'       => $d['create_time'],
                        'updater'           => $d['updater'],
                        'update_time'       => $d['update_time']
                );
                
                array_push($data_csv, $info);
            }
            
            return $data_csv;
        }else{
            for($i = 0; $i < count($data); $i++){
                $data[$i]['last_login_time'] = strtotime($data[$i]['last_login_time']);
                $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
                $data[$i]['update_time'] = strtotime($data[$i]['update_time']);
                $data[$i]['active'] = $data[$i]['active'] == 1 ? true : false;
                
                $data[$i]['role']  = $this->getRoleStr($data[$i]['id']);
            }
            
            return $data;
        }
    }
    
    // 获取角色信息
    public function getRoleStr($id)
    {
        $roleArr = $this->getUserRole($id);
        $role = '';
        
        for($j = 0; $j < count($roleArr); $j++){
            if($j == 0){
                $role = $roleArr[$j]['name'];
            }else{
                $role .= ', '.$roleArr[$j]['name'];
            }
        }
        
        return $role;
    }
}