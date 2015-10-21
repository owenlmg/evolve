<?php
/**
 * 2013-10-6 上午9:04:00
 * @author x.li
 * @abstract
 */
class Admin_Model_Member extends Application_Model_Db
{
    protected $_name = 'user_role_member';
    protected $_primary = 'id';

    // 获取项目角色成员（以树的形式）
    public function getMemberTreeList($role_id, $user_id)
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array('user_id'))
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t1.user_id = t2.id", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t2.employee_id = t3.id", array('text' => 'cname'))
                    ->where("t1.role_id = ".$role_id)
                    ->order("t3.cname");

        $data = $this->fetchAll($sql)->toArray();

        $userArr = array();

        if($user_id){
            $userArr = explode(',', $user_id);
        }

        for($i = 0; $i < count($data); $i++){
            $data[$i]['leaf'] = true;

            if(in_array($data[$i]['user_id'], $userArr)){
                $data[$i]['checked'] = true;
            }else{
                $data[$i]['checked'] = false;
            }
        }

        $role = array(
                'id'        => null,
                'text'      => '',
                'leaf'      => false,
                'children'  => $data
        );

        return $role;
    }

    /**
     * 添加当前用户到当前角色及所有上级
     * @param number $user_id
     * @param number $role_id
     */
    public function addUserToRole($user_id, $role_id)
    {
        $role = new Admin_Model_Role();

        // 检查当前角色是否存在上级角色
        $data = $role->fetchRow("id = ".$role_id);

        // 上级角色ID > 0表示当前角色拥有上级角色
        if($data['parentid'] > 0){
            // 如存在上级角色，添加用户到上级角色（继承上级角色权限）
            $this->addUserToRole($user_id, $data['parentid']);
        }

        // 如当前角色未曾添加当前用户，则添加用户到当前角色
        if($this->fetchAll("role_id = ".$role_id." and user_id = ".$user_id) -> count() == 0){
            $this->insert(array('role_id' => $role_id, 'user_id' => $user_id));
        }
    }

    /**
     * 删除当前角色及下级角色的所有当前用户
     * @param number $user_id
     * @param number $role_id
     */
    public function deleteUserFromRole($user_id, $role_id)
    {
        $role = new Admin_Model_Role();

        // 检查当前角色是否存在下级角色
        $r =  $role->fetchAll("parentid = ".$role_id);

        // 当前角色拥有下级角色
        if($r->count() > 0){
            $data = $r->toArray();

            foreach ($data as $d){
                // 从下级角色中清除当前用户
                $this->deleteUserFromRole($user_id, $d['id']);
            }
        }

        // 从当前角色清除用户
        $this->delete("role_id = ".$role_id." and user_id = ".$user_id);
    }

    /**
     * 根据用户ID获取用户角色
     * @param   number  $user_id
     * @return  array   $data
     */
    public function getMemberRole($user_id = 0)
    {
        if($user_id == 0){
            $sql = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(array('t1' => $this->_name), array())
                        ->joinLeft(array('t2' => $this->_dbprefix.'user_role'), "t1.role_id = t2.id", array('id', 'name'))
                        ->order(array('t2.name desc'));
        }else{
            $sql = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(array('t1' => $this->_name), array())
                        ->joinLeft(array('t2' => $this->_dbprefix.'user_role'), "t1.role_id = t2.id", array('id', 'name'))
                        ->where("t1.user_id = ".$user_id)
                        ->order(array('t2.name desc'));
        }
        //echo $sql.'<br><br><br>';

        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }

    /**
     * 根据角色ID获取成员信息
     * @param   number  $parentid
     * @param   string  $type
     * @return  array   $data
     */
    public function getMember($parentid, $type = 'id', $getAdmin = true, $employee_id = false)
    {
        if($employee_id){
            $sql = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(array('t1' => $this->_name), array())
                        ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t1.user_id = t2.id", array())
                        ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t2.employee_id = t3.id", array('employee_id' => 'id', 'user_name' => 'cname'))
                        ->where("t1.role_id = ".$parentid)
                        ->order(array('t3.cname desc'));
        }else{
            if($type == 'id'){
                $sql = $this->select()
                            ->setIntegrityCheck(false)
                            ->from(array('t1' => $this->_name), array('user_id'))
                            ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t1.user_id = t2.id", array())
                            ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t2.employee_id = t3.id", array('user_name' => 'cname', 'email'))
                            ->where("t1.role_id = ".$parentid)
                            ->order(array('t3.cname desc'));
            }else{
                $sql = $this->select()
                            ->setIntegrityCheck(false)
                            ->from(array('t1' => $this->_name), array())
                            ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t1.user_id = t2.id", array())
                            ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t2.employee_id = t3.id", array('employee_id' => 'id', 'name' => new Zend_Db_Expr("concat('[', t3.number, '] ', t3.cname)")))
                            ->where("t1.role_id = ".$parentid)
                            ->order(array('t3.cname desc'));
            }
        }
        
        if(!$getAdmin){
            $sql->where("t1.user_id != 1");
        }

        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }

    /**
     * 根据角色ID获取成员信息
     * @param   number  $parentid
     * @param   string  $type
     * @return  array   $data
     */
    public function getMemberWithNoManager($parent)
    {
        $sql = $this->select()
                    ->from(array('t1' => $this->_name), array('user_id'))
                    ->where("t1.user_id != 1 and t1.role_id = ".$parent);

        $data = $this->fetchAll($sql)->toArray();
//        $managers = $this->getUserids("系统管理员");
//        $tmp = array();
//        foreach ($data as $m){
//            if(in_array($m['user_id'], $managers)) {
//                continue;
//            }
//            $tmp[] = $m;
//        }

        return $data;
    }

    /**
     * 获取系统管理员以外的角色成员
     * @param string $parent
     * @return array
     */
    public function getMemberWithNoManagerByName($parent)
    {
        $data = array();

        if($parent != '系统管理员'){
            $sql = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(array('t1' => $this->_name), array('user_id'))
                        ->joinLeft(array('t2' => $this->_dbprefix."user_role"), "t1.role_id = t2.id", array())
                        ->joinLeft(array('t3' => $this->_dbprefix."user"), "t1.user_id = t3.id", array())
                        ->joinLeft(array('t4' => $this->_dbprefix."employee"), "t3.employee_id = t4.id", array('employee_id' => 't4.id', 'employee_name' => 't4.cname', 'email'))
                        ->where("t2.name = '".$parent."'");
            $sql->where("t1.user_id not in (select urm.user_id from ".$this->_dbprefix."user_role ur left join ".$this->_dbprefix."user_role_member urm on urm.role_id = ur.id where ur.name = '系统管理员')");
            
            $data = $this->fetchAll($sql)->toArray();
        }

        return $data;
    }

    /**
     * 获取系统管理员以外的角色成员
     * @param string $parent
     * @return array
     */
    public function getMemberWithManagerByName($parent)
    {
        $data = array();

        if($parent != '系统管理员'){
            $sql = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(array('t1' => $this->_name), array('user_id'))
                        ->joinLeft(array('t2' => $this->_dbprefix."user_role"), "t1.role_id = t2.id", array())
                        ->joinLeft(array('t3' => $this->_dbprefix."user"), "t1.user_id = t3.id", array())
                        ->joinLeft(array('t4' => $this->_dbprefix."employee"), "t3.employee_id = t4.id", array('employee_id' => 't4.id', 'employee_name' => 't4.cname', 'email'))
                        ->where("t4.active = 1 and t1.user_id != 1 and t2.name = '".$parent."'");
            
            $data = $this->fetchAll($sql)->toArray();
        }

        return $data;
    }

    /**
     * 根据角色名称获取人员id
     */
    public function getUserids($roleName)
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array('ids' => 'group_concat(user_id)'))
                    ->join(array('t2' => $this->_dbprefix.'user_role'), "t1.role_id = t2.id", array())
                    ->where("t2.name=?", $roleName);

        $data = $this->fetchRow($sql);

        return explode(',', $data['ids']);
    }
}