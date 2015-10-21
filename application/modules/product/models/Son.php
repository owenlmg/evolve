<?php
/**
 * 2013-9-8
 * @author mg.luo
 * @abstract
 */
class Product_Model_Son extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'product_bom_son';
    protected $_primary = 'id';

    public function getListByIds($where){
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'product_materiel'), "t1.id = t2.id", array('son_description' => 'description'))
                    ->where($where)
                    ->order(array('id'));
        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }

    public function getList($where){
        $sql = $this->select()
                    ->from($this->_name)
                    ->where($where);
        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }

    public function getListById($id){
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'product_materiel'), "t1.id = t2.id", array('state', 'description', 'name'))
                    ->where("t1.nid=?", $id)
                    ->order(array('code'));
        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }
    
    public function getListByRecordkey($recordkey){
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'product_materiel'), "t1.id = t2.id", array('state', 'description', 'name'))
                    ->where("t1.recordkey=?", $recordkey)
                    ->order(array('code'));
        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }

    /**
     * @abstract    获取树数据
     * @param       number  $parentId  上级ID
     * @return      array   $dept
     */
    public function getSon($recordkey)
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->join(array('t2' => $this->_dbprefix.'product_materiel'), "t1.id = t2.id", array('name', 'description', 'mstate' => 'state'))
                    ->where("recordkey = ".$recordkey);

        return $this->fetchAll($sql)->toArray();
    }
}