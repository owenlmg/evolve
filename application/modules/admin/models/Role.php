<?php
/**
 * 2013-10-4 下午2:31:44
 * @author x.li
 * @abstract 
 */
class Admin_Model_Role extends Application_Model_Db
{
    protected $_name = 'user_role';
    protected $_primary = 'id';
    
    /**
     * 获取超级管理员ID
     * @return Ambigous <NULL, string, Ambigous <>>
     */
    public function getAdminId()
    {
        $admin = $this->getRoleIdByName("系统管理员");
        
        return $admin['role_id'];
    }
    
    /**
     * 根据角色名称获取角色ID
     * @param unknown $role_name
     * @return multitype:NULL string Ambigous <>
     */
    public function getRoleIdByName($role_name)
    {
        $result = array(
                'err_msg'   => null,
                'role_id'   => null
        );
        
        if($role_name){
            if($this->fetchAll("name = '".$role_name."'")->count() == 0){
                $result['err_msg'] = "角色'".$role_name."'未找到";
            }else{
                $r = $this->fetchRow("name = '".$role_name."'")->toArray();
                
                $result['role_id'] = $r['id'];
            }
        }else{
            $result['err_msg'] = "角色名称为空";
        }
        
        return $result;
    }
    
    /**
     * 获取当前角色的最上级角色名称
     * @param unknown $role_id
     * @return Ambigous <>
     */
    public function getFirstLevelRoleName($role_id)
    {
        $data = array();
        
        $r = $this->fetchRow("id = ".$role_id)->toArray();
        
        if($r['parentid'] > 0){
            return $this->getFirstLevelRoleName($r['parentid']);
        }else{
            $data = array(
                    'id'    => $role_id,
                    'name'  => $r['name']
            );
            
            return $data;
        }
    }
    
    /**
     * 获取角色列表
     * @return array $data
     */
    public function getList($getAdemin = true)
    {
        $data = array();
    
        $sql = $this->select()
                    ->from($this, array('id', 'name'))
                    ->order(array('CONVERT( name USING gbk )'));
        
        if(!$getAdemin){
            $sql->where("id != 2");
        }
    
        $data = $this->fetchAll($sql)->toArray();
        
        for($i = 0; $i < count($data); $i++){
            $data[$i]['firstLevel'] = $this->getFirstLevelRoleName($data[$i]['id']);
        }
    
        return $data;
    }
    
    /**
     * @abstract    递归删除角色树数据
     * @param       number  $id  部门ID
     * @return      null
     */
    public function deleteRoleTreeData($id){
        $this->delete("id = ".$id);
    
        $children = $this->fetchAll("parentId = ".$id);
    
        foreach ($children as $child){
            $this->deleteRoleTreeData($child['id']);
        }
    }
    
    /**
     * 获取角色数据
     * @param   number  $parentId   上级角色ID
     * @param   bollean $root       是否为最上级
     * @return  array   $role
     */
    public function getData($parentId, $active = null, $root = true)
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
                    ->where("parentId = ".$parentId)
                    ->order(array('CONVERT( t1.name USING gbk )'));
        
        if($active){
            $sql = $sql->where('t1.active = '.$active);
        }

        $data = $this->fetchAll($sql)->toArray();

        // 判断是否包含子角色并格式化时间
        for($i = 0; $i < count($data); $i++){
            $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
            $data[$i]['update_time'] = strtotime($data[$i]['update_time']);
            $data[$i]['active'] = $data[$i]['active'] == 1 ? true : false;
            $data[$i]['text'] =  $data[$i]['name'];

            if($this->fetchAll("parentId = ".$data[$i]['id'])->count() > 0){
                $data[$i]['leaf'] = false;
                $data[$i]['children'] = $this->getData($data[$i]['id'], $active, false);
            }else{
                $data[$i]['leaf'] = true;
                $data[$i]['checked'] = false;
            }
            
            $member = new Admin_Model_Member();
            $memberArr = $member->getMember($data[$i]['id'], 'name', false);
            $memberStr = '';
            
            for($j = 0; $j < count($memberArr); $j++){
                if($j == 0){
                    $memberStr = $memberArr[$j]['name'];
                }else{
                    $memberStr .= ', '.$memberArr[$j]['name'];
                }
            }
            
            $data[$i]['member'] = $memberStr;
        }

        // 格式化根数据格式
        if($root){
            $role = array(
                    'id'            => null,
                    'parentId'      => null,
                    'name'          => '',
                    'text'          => '',
                    'description'   => '',
                    'member'        => '',
                    'remark'        => '',
                    'create_time'   => null,
                    'update_time'   => null,
                    'create_user'   => '',
                    'update_user'   => '',
                    'active'         => null,
                    'leaf'          => false,
                    'children'      => $data
            );
        }else{
            $role = $data;
        }

        return $role;
    }
}