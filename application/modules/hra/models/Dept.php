<?php
/**
 * 2013-7-6 下午11:01:30
 * @author x.li
 * @abstract 
 */
class Hra_Model_Dept extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'employee_dept';
    protected $_primary = 'id';
    
    public function getDeptInfoDesc($dept_id)
    {
        $infoDesc = '';
        
        $info = $this->getDeptInfo($dept_id);
        
        $arr = explode(' > ', $info);
        
        $infoDescArr = array();
        
        for($i = count($arr) - 1; $i >= 0; $i--){
            array_push($infoDescArr, $arr[$i]);
        }
        
        $infoDesc = implode(' > ', $infoDescArr);
        
        return $infoDesc;
    }
    
    public function getDeptInfo($dept_id)
    {
        $info = '';
        
        $sql = $this->select()
                    ->from($this)
                    ->where("id = ".$dept_id);
        
        if($this->fetchAll($sql)->count() > 0){
            $data = $this->fetchRow($sql)->toArray();
            
            $info = $data['description'] != '' ? $data['name'].' ['.$data['description'].']' : $data['name'];
            
            if($data['parentid'] != 0){
                $info .= ' > '.$this->getDeptInfo($data['parentid']);
            }
        }
        
        return $info;
    }

    /**
     * 获取部门列表
     * @return array $data
     */
    public function getList()
    {
        $data = array();

        $sql = $this->select()
                    ->from($this, array('id', 'name'))
                    //->where("id in (SELECT dept_id FROM ".$this->_dbprefix."employee GROUP BY dept_id)")
                    ->order(array('CONVERT( name USING gbk )'));

        $data = $this->fetchAll($sql, 'name')->toArray();

        return $data;
    }

    /**
     * @abstract    递归删除部门树数据
     * @param       number  $id  部门ID
     * @return      null
     */
    public function deleteDeptTreeData($id){
        $this->delete("id = ".$id);

        $children = $this->fetchAll("parentId = ".$id);

        foreach ($children as $child){
            $this->deleteDeptTreeData($child['id']);
        }
    }

    /**
     * @abstract    获取部门树数据
     * @param       number  $parentId   上级部门ID
     * @param       boolen  $root       是否为最上级部门
     * @return      array   $dept
     */
    public function getData($parentId = 0, $root = true)
    {
        $dept = array();
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
        
        $data = $this->fetchAll($sql)->toArray();

        // 判断是否包含子部门并格式化时间
        for($i = 0; $i < count($data); $i++){
            $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
            $data[$i]['update_time'] = strtotime($data[$i]['update_time']);
            $data[$i]['active'] = $data[$i]['active'] == 1 ? true : false;

            if($this->fetchAll("parentId = ".$data[$i]['id'])->count() > 0){
                $data[$i]['leaf'] = false;
                $data[$i]['children'] = $this->getData($data[$i]['id'], false);
            }else{
                $data[$i]['leaf'] = true;
            }
        }

        // 格式化根数据格式
        if($root){
            $dept = array(
                    'id'            => null,
                    'parentId'      => null,
                    'name'          => '',
                    'description'   => '',
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
            $dept = $data;
        }

        return $dept;
    }
}