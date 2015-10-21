<?php
/**
 * 2013-7-6 下午5:20:23
 * @author x.li
 * @abstract 
 */
class Home_Model_Menu extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'menu';
    protected $_primary = 'id';
    
    public function updateOrder($id, $oldParentId, $newParentId, $order)
    {
        // 更新上级菜单下除当前菜单以外且顺序大于新顺序的所有子菜单顺序+1
        $this->update(array('order' => 'order + 1'), "parent_id = ".$newParentId." and order > ".$order." and id != ".$id);
        // 更新当前子菜单顺序为新顺序
        $this->update(array('order = '.$order), "id = ".$id);
        
        if($oldParentId != $newParentId){
            // 当上级菜单发生改变时，更新旧上级菜单下除当前子菜单以外，且顺序大于新顺序的所有子菜单顺序-1
            $this->update(array('order' => 'order - 1'), "parent_id = ".$oldParentId." and order > ".$order." and id != ".$id);
        }
    }
    
    public function deleteMenuTreeData($id)
    {
        $this->delete("id = ".$id);
        
        $children = $this->fetchAll("parent_id = ".$id);
        
        foreach ($children as $child){
            $this->deleteMenuTreeData($child['id']);
        }
    }
    
    public function getTreeData($parentId, $root = true)
    {
        $role = array();
        $data = array();
    
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t1.create_user = t2.id", array('creater_id' => 'id'))
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t2.employee_id = t3.id", array('creater' => 'cname'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t1.update_user = t4.id", array('updater_id' => 'id'))
                    ->joinLeft(array('t5' => $this->_dbprefix.'employee'), "t4.employee_id = t5.id", array('updater' => 'cname'))
                    ->where("t1.parent_id = ".$parentId)
                    ->order(array('t1.order'));
        
        $data = $this->fetchAll($sql)->toArray();
        
        // 判断是否包含子角色并格式化时间
        for($i = 0; $i < count($data); $i++){
            $data[$i]['parentId'] = $data[$i]['parent_id'];
            $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
            $data[$i]['update_time'] = strtotime($data[$i]['update_time']);
            $data[$i]['disabled'] = $data[$i]['disabled'] == 1 ? true : false;
            
            $memberRole = new Home_Model_Menurole();
            $roleArr = $memberRole->getRoleOfMenu($data[$i]['id'], 'name', false);
            $roleStr = '';
            
            for($j = 0; $j < count($roleArr); $j++){
                if($j == 0){
                    $roleStr = $roleArr[$j]['name'];
                }else{
                    $roleStr .= ', '.$roleArr[$j]['name'];
                }
            }
            
            $data[$i]['role'] = $roleStr;
    
            if($this->fetchAll("parent_id = ".$data[$i]['id'])->count() > 0){
                $data[$i]['leaf'] = false;
                $data[$i]['children'] = $this->getTreeData($data[$i]['id'], false);
            }else{
                $data[$i]['leaf'] = true;
            }
        }
    
        // 格式化根数据格式
        if($root){
            $role = array(
                    'id'            => null,
                    'parentId'      => null,
                    'order'         => '',
                    'iconCls'       => '',
                    'tooltip'       => '',
                    'text'          => '',
                    'handler'       => '',
                    'disabled'      => '',
                    'url'           => '',
                    'params'        => '',
                    'create_time'   => null,
                    'update_time'   => null,
                    'create_user'   => '',
                    'update_user'   => '',
                    'leaf'          => false,
                    'children'      => $data
            );
        }else{
            $role = $data;
        }
    
        return $role;
    }
    
    /**
     * @abstract    递归获取菜单数据
     * @param       number $parent_id 上级菜单ID
     * @return      array
     */
    public function getMenuData($parent_id = 0, $start = true)
    {
        // 菜单数据
        $data = array();
    
        $sql = $this->select()
                    ->from($this, array(
                            'id',
                            'disabled',
                            'parent_id',
                            'text',
                            'iconCls',
                            'tooltip',
                            'handler',
                            'url',
                            'params'))
                    ->where("parent_id = ".$parent_id)
                    ->order(array('order'));
        
        $data = $this->fetchAll($sql)->toArray();
        
        for($i = 0; $i < count($data); $i++){
            $id = $data[$i]['id'];
            
            // 当菜单为启用时，检查用户菜单使用权限
            if(!$data[$i]['disabled']){
                $data[$i]['disabled'] = $this->checkUserMenuPermissonByMenuId($id) ? 0 : 1;
            }
            
            $data[$i]['id'] = 'mainMenu_'.$id;
            
            if($this->fetchAll("parent_id = ".$id)->count() > 0){
                // 递归
                $data[$i]['menu'] = $this->getMenuData($id);
            }
        }
        
        return $data;
    }
    
    public function checkUserMenuPermissonByMenuId($menuId)
    {
        $user_session = new Zend_Session_Namespace('user');
        $userRole = $user_session->user_info['user_role'];
        
        $menuRole = new Home_Model_Menurole();
        
        $roles = $menuRole->getRoleOfMenu($menuId, 'id');
        
        foreach ($roles as $r){
            foreach ($userRole as $u){
                if($u['id'] == $r['id']){
                    return true;
                }
            }
        }
        
        return false;
    }
}