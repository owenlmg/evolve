<?php

/**
 * 2013-8-6 10:21:10
 * @author mg.luo
 * @abstract 
 */
class Admin_Model_Step extends Application_Model_Db {

    /**
     * 表名、主键
     */
    protected $_name = 'admin_step';
    protected $_primary = 'id';

    public function getListByFlow($step_ids) {
        $sql = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t1' => $this->_name))
                ->joinLeft(array('t2' => $this->_dbprefix . 'codemaster'), "t1.method = t2.code and t2.type=3", array('method_name' => 'text'))
                ->joinLeft(array('t3' => $this->_dbprefix . 'codemaster'), "t1.return = t3.code and t3.type=2", array('return_name' => 'text'))
                ->where("t1.id in ($step_ids)")
                ->order("field(t1.id," . $step_ids . ")");

        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }

    public function getList($where, $start, $limit, $orders) {
        $sql = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t1' => $this->_name))
                ->joinLeft(array('t2' => $this->_dbprefix . 'codemaster'), "t1.method = t2.code and t2.type=3", array('method_name' => 'text'))
                ->joinLeft(array('t3' => $this->_dbprefix . 'codemaster'), "t1.return = t3.code and t3.type=2", array('return_name' => 'text'))
                ->joinLeft(array('t4' => $this->_dbprefix . 'user_role'), "FIND_IN_SET(t1.dept, t4.id) and t4.active=1", array('step_dept_name' => 'name'))
                ->where($where)
                ->limit($limit, $start)
                ->order("t1.step_name");
        if ($orders) {
            $sql = $sql->order("field(id," . $orders . ")");
        }


        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }

}