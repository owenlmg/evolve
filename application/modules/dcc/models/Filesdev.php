<?php

/**
 * 2013-7-17
 * @author mg.luo
 * @abstract
 */
class Dcc_Model_Filesdev extends Application_Model_Db {

    /**
     * 表名、主键
     */
    protected $_name = 'doc_files_dev';
    protected $_primary = 'id';

    public function getFilesList($where, $start, $limit) {
        $sql = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t1' => $this->_name))
                ->joinLeft(array('t2' => $this->_dbprefix . 'employee'), "t1.create_user = t2.id", array('creater' => 'cname'))
                ->joinLeft(array('t3' => $this->_dbprefix . 'employee'), "t1.update_user = t3.id", array('updater' => 'cname'))
                ->joinLeft(array('t4' => $this->_dbprefix . 'doc_code'), "t1.code = t4.code", array('prefix', 'project_no', 'code_description' => 'description'))
                ->joinLeft(array('t5' => $this->_dbprefix . 'doc_type'), "t4.prefix = t5.id", array('type_id' => 'id', 'type_code' => 'code', 'type_name' => 'name', 'model_id'))
                ->joinLeft(array('t6' => $this->_dbprefix . 'doc_upgrade'), "t1.id = t6.file_id", array('reason', 'reason_type', 'newest_ver' => 'ver_original', 'future_ver' => 'ver'))
                ->joinLeft(array('t7' => $this->_dbprefix . 'product_catalog'), "t4.project_no = t7.id", array('project_name' => 'model_internal'))
                ->joinLeft(array('t8' => $this->_dbprefix . 'codemaster'), "t5.category = t8.code and t8.type=4", array('category' => 'id', 'category_name' => 'text'))
                ->where($where)
                ->order(array('t1.archive_time desc', 't1.code desc', 't1.ver desc'))
                ->limit($limit, $start);
        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }

}