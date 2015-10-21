<?php
/**
 * 2013-9-8
 * @author mg.luo
 * @abstract
 */
class Product_Model_Type extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'product_type';
    protected $_primary = 'id';
    
    public function getTypeList()
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array('id', 'name' => new Zend_Db_Expr("concat(t1.code, ' ', t1.name)")))
                    ->joinLeft(array('t2' => $this->_name), "t1.parent_id = t2.id", array('code' => new Zend_Db_Expr("concat(t2.code, ' ', t2.name)")))
                    ->where("t1.parent_id != 0")
                    ->order("t1.parent_id", "t1.code");
        $data = $this->fetchAll($sql)->toArray();
        
        return $data;
    }

    public function getList($where){
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'employee'), "t1.create_user = t2.id", array('creater' => 'cname'))
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t1.update_user = t3.id", array('updater' => 'cname'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'doc_type'), "t1.prefix = t4.id", array('type_id' => 'id', 'type_code' => 'code'))
                    ->where($where)
                    ->order(array('code'));
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
                    ->from($this->_name, array("id", "parent_id", "text" => "name", 'auto'))
                    ->where("active = 1 and parent_id = ".$parentId)
                    ->order(array('code'));

        $data = $this->fetchAll($sql)->toArray();

        // 判断是否包含子数据
        for($i = 0; $i < count($data); $i++){
            if($this->fetchAll("parent_id = ".$data[$i]['id'])->count() > 0){
                $data[$i]['leaf'] = false;
                $data[$i]['children'] = $this->getTree($data[$i]['id'], false);
            }else{
                $data[$i]['checked'] = false;
                $data[$i]['leaf'] = true;
            }
        }

        // 格式化根数据格式
        if($parentId == 0){
            $type = array(
                    'id'            => 0,
                    'parent_id'      => 0,
                    'text'          => '类别',
                    'leaf'          => false,
                    'expanded'        => true,
                    'children'      => $data
            );
        }else{
            $type = $data;
        }

        return $type;
    }

    /**
     * @abstract    递归删除
     * @param       number  $id  类别ID
     * @return      null
     */
    public function deleteTreeData($id){
        $this->delete("id = ".$id);

        $children = $this->fetchAll("parent_id = ".$id);

        foreach ($children as $child){
            $this->deleteTreeData($child['id']);
        }
    }

    /**
     * @abstract    获取类别树数据
     * @param       number  $parentId  上级ID
     * @param       boolen  $root       是否为最上级
     * @return      array   $dept
     */
    public function getData($parentId = 0, $root = true)
    {
        $dept = array();
        $data = array();

        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'employee'), "t1.create_user = t2.id", array('creater' => 'cname'))
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t1.update_user = t3.id", array('updater' => 'cname'))
                    ->where("parent_id = ".$parentId)
                    ->order(array('code'));

        $data = $this->fetchAll($sql)->toArray();

        // 判断是否包含子类别
        for($i = 0; $i < count($data); $i++){
            $data[$i]['active'] = $data[$i]['active'] == 1 ? true : false;
            $data[$i]['bom'] = $data[$i]['bom'] == 1 ? true : false;
            $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
            $data[$i]['update_time'] = strtotime($data[$i]['update_time']);

            if($this->fetchAll("parent_id = ".$data[$i]['id'])->count() > 0){
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
                    'parent_id'      => null,
                    'name'          => '',
                    'description'   => '',
                    'remark'        => '',
                    'active'         => null,
                    'leaf'          => false,
                    'children'      => $data
            );
        }else{
            $dept = $data;
        }

        return $dept;
    }

    public function getInfo($sid, $column) {
        if($column) {
            $sql = $this->select()
                        ->from($this->_name, array("parent_id", $column))
                        ->where("id=?", $sid);
            $data = $this->fetchRow($sql);
            if($data[$column] || $data['parent_id'] == 0) {
                return $data[$column];
            } else {
                return $this->getInfo($data['parent_id'], $column);
            }
        } else {
            return "";
        }
    }

    public function getFlowId($sid, $t) {
        if($t == 'new' || $t == 'upd' || $t == 'del') {
            $column = $t."_flow_id";
        }
        if($column) {
            $sql = $this->select()
                        ->from($this->_name, array("parent_id", 'flow_id' => $column))
                        ->where("id=?", $sid);
            $data = $this->fetchRow($sql);
            if($data['flow_id']) {
                return $data['flow_id'];
            } else {
//                return $this->getFlowId($data['parent_id'], $t);
                return "";
            }
        } else {
            return "";
        }
    }

    public function getTypeByConnect($id, $name) {
        if($id) {
            $type = new Product_Model_Type();
            $row = $type->fetchRow("id = $id");
            if($row) {
                $id = $row->parent_id;
                $name = $row->name.'--'.$name;

                return $this->getTypeByConnect($id, $name);
            }
        }
        return trim($name, '--');
    }

    public function getTypes($value) {
        $ids = array();
        $types = $this->getAdapter()->query("select id, parent_id from oa_product_type where name like '%$value%'")->fetchAll();
        foreach($types as $t) {
            $ids[] = $t['id'];
            $ids = $this->getIdByConnect($t['id'], $ids);
        }
        if(count($ids) > 0) {
            return implode(',', $ids);
        }
        return "";
    }

    private function getIdByConnect($id, $ids) {
        if ($id) {
            $row = $this->fetchAll("parent_id = $id")->toArray();
            if(count($row) > 0) {
                foreach($row as $r) {
                    $ids[] = $r['id'];
                    $ids = $this->getIdByConnect($r['id'], $ids);
                }
            } else {
                return $ids;
            }
        }
        return $ids;
    }
}