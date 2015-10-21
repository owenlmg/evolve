<?php

/**
 * 2013-7-20 下午4:01:30
 * @author mg.luo
 * @abstract 
 */
class Admin_Model_Form extends Application_Model_Db {

    /**
     * 表名、主键
     */
    protected $_name = 'admin_formattr';
    protected $_primary = 'id';

    public function getListById($modeId) {
        $sql = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t1' => $this->_name))
                ->joinLeft(array('t2' => $this->_dbprefix . 'employee'), "t1.create_user = t2.id", array('creater' => 'cname'))
                ->joinLeft(array('t3' => $this->_dbprefix . 'employee'), "t1.update_user = t3.id", array('updater' => 'cname'))
                ->joinLeft(array('t4' => $this->_dbprefix . 'admin_model'), "t1.model_id = t4.id and t4.state=1", array('model_id' => 'id', 'model_name' => 'name'))
                ->joinLeft(array('t5' => $this->_dbprefix . 'admin_enumlist'), "t1.enumlist = t5.id and t5.state=1", array('enumlistid' => 'id', 'enumlistname' => 'name'))
                ->joinLeft(array('t6' => $this->_dbprefix . 'admin_column'), "t1.type = t6.id and t6.state=1", array('type' => 'id', 'type_name' => 'name'))
                ->joinLeft(array('t7' => $this->_dbprefix . 'admin_enum'), "t1.default_value = t7.id and t7.state=1", array('option_key' => 'id', 'option_value' => 'option_value'))
                ->where("t1.model_id = ?", $modeId)
                ->order(array('t1.form_sort'));

        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }

    public function getFormByModel($model) {
        $sql = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t1' => $this->_name), array("id", "name", "type", "length", "nullable", "enumlist", "default_value", "multi"))
                ->joinLeft(array('t2' => $this->_dbprefix.'admin_column'), 't1.type = t2.id', array('default_width'))
                ->where("t1.state = 1 and t1.model_id = ?", $model)
                ->order(array('t1.id'));

        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }

    public function getAttrAndValByMenu($menu) {
        $data = array();

        $sql = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t1' => $this->_name))
                ->joinLeft(array('t2' => $this->_dbprefix . 'admin_formval'), "t1.id = t2.attrid", array("value"))
                ->joinLeft(array('t3' => $this->_dbprefix . 'admin_enum'), "t2.value = t3.id and t3.state=1 and t1.enumlist = t3.list_id", array('option_key' => 'id', 'option_value' => 'option_value'))
                ->where("t1.state = 1 and t2.menu = ?", $menu)
                ->order(array('t1.id'));

        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }

}