<?php
/**
 * 2013-10-20 下午10:49:58
 * @author x.li
 * @abstract 
 */
class Home_Model_Menurole extends Application_Model_Db
{
    protected $_name = 'menu_role';
    protected $_primary = 'id';
    
    /**
     * 添加当前角色到当前菜单及所有上级
     * @param number $role_id
     * @param number $menu_id
     */
    public function addRoleToMenu($role_id, $menu_id)
    {
        $menu = new Home_Model_Menu();
        
        // 检查当前菜单是否存在上级菜单
        $data = $menu->fetchRow("id = ".$menu_id);
        
        // 上级菜单ID > 0表示当前菜单拥有上级菜单
        if($data['parent_id'] > 0){
            // 如存在上级菜单，且上级菜单未包含当前角色，添加角色到上级菜单（继承上级菜单权限）
            if($this->fetchAll("menu_id = ".$data['parent_id']." and role_id = ".$role_id)->count() == 0){
                $this->addRoleToMenu($role_id, $data['parent_id']);
            }
        }
        
        // 如当前菜单未曾添加当前角色，则添加角色到当前菜单
        if($this->fetchAll("role_id = ".$role_id." and menu_id = ".$menu_id) -> count() == 0){
            $this->insert(array('role_id' => $role_id, 'menu_id' => $menu_id));
        }
    }
    
    /**
     * 删除当前菜单及下级菜单的所有当前角色
     * @param number $user_id
     * @param number $role_id
     */
    public function deleteRoleFromMenu($role_id, $menu_id)              
    {
        $menu = new Home_Model_Menu();
        
        // 检查当前菜单是否存在下级菜单
        $m =  $menu->fetchAll("parent_id = ".$menu_id);
        
        // 当前菜单拥有下级菜单
        if($m->count() > 0){
            $data = $m->toArray();
            
            foreach ($data as $d){
                // 从下级菜单中清除当前角色
                $this->deleteRoleFromMenu($role_id, $d['id']);
            }
        }
        
        // 从当前菜单清除角色
        $this->delete("role_id = ".$role_id." and menu_id = ".$menu_id);
    }
    
    /**
     * 根据菜单ID获取所分配的角色信息
     * @param   number  $parentid
     * @param   string  $type
     * @return  array   $data
     */
    public function getRoleOfMenu($menu_id, $type = 'id', $getAdmin = true)
    {
        if($type == 'id'){
            $sql = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(array('t1' => $this->_name), array('id' => 'role_id'))
                        ->joinLeft(array('t2' => $this->_dbprefix.'user_role'), "t1.role_id = t2.id", array('name'))
                        ->where("t1.menu_id = ".$menu_id)
                        ->order(array('t2.name desc'));
        }else{
            $sql = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(array('t1' => $this->_name), array())
                        ->joinLeft(array('t2' => $this->_dbprefix.'user_role'), "t1.role_id = t2.id", array('name'))
                        ->where("t1.menu_id = ".$menu_id)
                        ->order(array('t2.name desc'));
        }
        
        if(!$getAdmin){
            $sql->where("role_id != 2");
        }
        
        $data = $this->fetchAll($sql)->toArray();
        
        return $data;
    }
}