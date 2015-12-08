<?php

/**
 * 2013-8-6 10:21:10
 * @author mg.luo
 * @abstract
 */
class Admin_Model_Flow extends Application_Model_Db {

    /**
     * 表名、主键
     */
    protected $_name = 'admin_flow';
    protected $_primary = 'id';

    public function getListForCombo() {
        $data = array();

        $sql = $this->select()
                ->from($this, array('id', 'text' => 'flow_name'))
                ->order(array('flow_name'));

        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }

    public function getBomListForCombo() {
        $data = array();

        $sql = $this->select()
                ->from($this, array('id', 'text' => 'flow_name'))
                ->where("flow_name like 'BOM%'")
                ->order(array('flow_name'));

        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }

    public function getRow($id) {
        $data = array();

        $sql = $this->select()
                ->from($this, array("step_ids"))
                ->where("id = ?", $id);

        $data = $this->fetchRow($sql);

        return $data;
    }

    public function getList($where, $start, $limit) {
        $sql = $this->select()
                ->from($this)
                ->where($where)
                ->order(array('flow_name'))
                ->limit($limit, $start);

        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }

    /**
     * @abstract    获取树数据
     * @param       number  $parentId  上级ID
     * @return      array   $dept
     */
    public function getTree($where) {
        $sql = $this->select()
                ->from($this->_name)
                ->where($where)
                ->order(array('flow_name'));

        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }

}