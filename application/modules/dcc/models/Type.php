<?php

/**
 * 2013-7-6 下午14:01:30
 * @author mg.luo
 * @abstract
 */
class Dcc_Model_Type extends Application_Model_Db {

    /**
     * 表名、主键
     */
    protected $_name = 'doc_type';
    protected $_primary = 'id';

    public function getTypeList($where) {
        $sql = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t1' => $this->_name))
                ->joinLeft(array('t2' => $this->_dbprefix . 'employee'), "t1.create_user = t2.id", array('creater' => 'cname'))
                ->joinLeft(array('t3' => $this->_dbprefix . 'employee'), "t1.update_user = t3.id", array('updater' => 'cname'))
                ->joinLeft(array('t4' => $this->_dbprefix . 'doc_auto'), "t1.autotype = t4.id", array('auto_id' => 'id', 'auto_description' => 'description'))
                ->joinLeft(array('t5' => $this->_dbprefix . 'admin_model'), "t1.model_id = t5.id", array('model_name' => 'name'))
                ->joinLeft(array('t6' => $this->_dbprefix . 'admin_model'), "t1.dev_model_id = t6.id", array('dev_model_name' => 'name'))
                ->joinLeft(array('t7' => $this->_dbprefix . 'admin_flow'), "t1.flow_id = t7.id", array('flow_name', 'step_ids'))
                ->joinLeft(array('t8' => $this->_dbprefix . 'admin_flow'), "t1.dev_flow_id = t8.id", array('dev_flow_name' => 'flow_name', 'dev_step_ids' => 'step_ids'))
                ->joinLeft(array('t9' => $this->_dbprefix . 'codemaster'), "t1.category = t9.code and t9.type=4", array('category_name' => 'text'))
                ->where($where)
                ->order(array('t1.code'));
        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }

    public function getTypeForCode() {
        $sql = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t1' => $this->_name), array('id', 'code', 'autocode', 'modelrequire'))
                ->joinLeft(array('t2' => $this->_dbprefix . 'doc_auto'), "t1.autotype = t2.id", array('automethod'))
                ->where('state = 1')
                ->order(array('code'));
        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }

    public function getLengthAndMethod($type) {
        $sql = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t1' => $this->_name), array('code', 'length'))
                ->joinLeft(array('t2' => $this->_dbprefix . 'doc_auto'), "t1.autotype = t2.id", array('automethod'))
                ->where('t1.id = ?', $type);
        $data = $this->fetchRow($sql)->toArray();

        return $data;
    }

    public function getList($where) {
        $data = array();

        $sql = $this->select()
                ->from($this)
                ->where($where);

        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }

}