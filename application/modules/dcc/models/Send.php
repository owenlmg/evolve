<?php

/**
 * 2013-11-30
 * @author mg.luo
 * @abstract 
 */
class Dcc_Model_Send extends Application_Model_Db {

    /**
     * 表名、主键
     */
    protected $_name = 'doc_send';
    protected $_primary = 'id';

    public function getList($where, $start, $limit) {
        $sql = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t1' => $this->_name))
                ->joinLeft(array('t2' => $this->_dbprefix . 'employee'), "t1.handle_user = t2.id", array('handler' => 'cname'))
                ->joinLeft(array('t3' => $this->_dbprefix . 'bpartner'), "t1.partner = t3.code", array('cname', 'ename'))
                ->joinLeft(array('t4' => $this->_dbprefix . 'bpartner_contact'), "t1.linkman = t4.email and t3.id=t4.partner_id", array('contact_name' => 'name'))
                ->where($where)
                ->order("t1.id desc");

        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }

}