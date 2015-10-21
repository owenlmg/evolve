<?php

/**
 * 2013-7-31
 * @author mg.luo
 * @abstract 
 */
class Dcc_Model_Log extends Application_Model_Db {

    /**
     * 表名、主键
     */
    protected $_name = 'record';
    protected $_primary = 'id';

    public function getList($where, $start, $limit) {
        $files = $this->_dbprefix . 'doc_upload';
        $sql = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t1' => $this->_name))
                ->joinLeft(array('t3' => $files), "t1.table_id = t3.id and t1.table_name='$files'", array('name', 'description', 'path', 'view_path', 'type'))
                ->joinLeft(array('t2' => $this->_dbprefix . 'employee'), "t1.handle_user = t2.id", array('handler' => 'cname'))
                ->where($where)
                ->order("handle_time desc");
        if($start != null && $limit) {
            $sql = $sql->limit($limit, $start);
        }

        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }

}