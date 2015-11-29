<?php
/**
 * 2013-7-20 12:21:10
 * @author mg.luo
 * @abstract 
 */
class Admin_Model_Model extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'admin_model';
    protected $_primary = 'id';

    public function getList()
    {
        $data = array();

        $sql = $this->select()
                    ->from($this, array('id', 'name'))
                    ->order(array('name'));

        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }

    /**
     * @abstract    获取树数据
     * @param       number  $parentId  上级ID
     * @return      array   $dept
     */
    public function getTree($parentId)
    {
        $model = array();
        $data = array();
        $return = array();
                
        $sql = $this->select()
                    ->from($this->_name, array("id", "parentid", "text" => "name"))
                    ->where("state = 1 and parentid = ".$parentId)
                    ->order(array('name'));

        $data = $this->fetchAll($sql)->toArray();

        // 判断是否包含子模块
        for($i = 0; $i < count($data); $i++){
            if($this->fetchAll("parentId = ".$data[$i]['id'])->count() > 0){
                $data[$i]['leaf'] = false;
                $data[$i]['children'] = $this->getTree($data[$i]['id'], false);             
            }else{
                $data[$i]['checked'] = false;
                $data[$i]['leaf'] = true;
            }
        }

        // 格式化根数据格式
        if($parentId == 0){
//            $model = array(
//                    'id'            => 0,
//                    'parentId'      => 0,
//                    'text'          => '模块',
//                    'leaf'          => false,
//                    'expanded'        => true,
//                    'children'      => $data
//            );
            $model = $data;
        }else{
            $model = $data;
        }

        return $model;
    }
    
    /**
     * @abstract    根据节点id查询节点名字
     * @param       number  $id  节点ID
     * @return      null
     */
    public function getLeafValue($id) {
    	$data = array();
    	$name = "";
    	$sql = $this->select()
    	            ->from($this, array('id', 'parentid', 'name'))
    	            ->where("state = 1 and id = ?", $id);
    	$data = $this->fetchRow($sql);
    	$parentId = $data['parentid'];
    	$name = $data['name'];
    	while($parentId > 0) {
    		$sql = $this->select()
    	                ->from($this, array('id', 'parentid', 'name'))
    	                ->where("state = 1 and id = ?", $parentId);
    	    $data = $this->fetchRow($sql);
    	    
    	    $parentId = $data['parentid'];
    	    $name = $data['name']. '_'.$name;
    	}
    	return array('id' => $id, 'name' => $name);
    }

    /**
     * @abstract    递归删除模块树数据
     * @param       number  $id  部门ID
     * @return      null
     */
    public function deleteModelTreeData($id){
        $this->delete("id = ".$id);

        $children = $this->fetchAll("parentId = ".$id);

        foreach ($children as $child){
            $this->deleteModelTreeData($child['id']);
        }
    }

    /**
     * @abstract    获取模块树数据
     * @param       number  $parentId  上级模块ID
     * @param       boolen  $root       是否为最上级模块
     * @return      array   $dept
     */
    public function getData($parentId = 0, $root = true, $expanded)
    {
        $dept = array();
        $data = array();
        
        $sql = $this->select()
                    ->from($this->_name, array('id', 'name'))
                    ->where("state = 1 and parentId = ".$parentId)
                    ->order(array('name'));

        $data = $this->fetchAll($sql)->toArray();

        // 判断是否包含子模块
        for($i = 0; $i < count($data); $i++){
            if($this->fetchAll("parentId = ".$data[$i]['id'])->count() > 0){
                $data[$i]['leaf'] = false;
                if($expanded == $data[$i]['id']) {
                	$data[$i]['expanded'] = true;
                }
                $data[$i]['children'] = $this->getData($data[$i]['id'], false, $expanded);
            }else{
                $data[$i]['leaf'] = true;
            }
        }

        // 格式化根数据格式
        if($root){
            $dept = array(
                    'id'            => null,
                    'name'          => 'Root',
                    'expanded'      => true,
                    'leaf'          => false,
                    'children'      => $data
            );
        }else{
            $dept = $data;
        }

        return $dept;
    }
}