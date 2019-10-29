<?php

/**
 * 2013-7-15 下午16:01:30
 * @author mg.luo
 * @abstract
 */
class Dcc_Model_Code extends Application_Model_Db {

    /**
     * 表名、主键
     */
    protected $_name = 'doc_code';
    protected $_primary = 'id';

    public function getCode($where, $start, $limit) {
        $sql = "SELECT `t1`.*, `t2`.`cname` AS `creater`, `t3`.`cname` AS `updater`, `t4`.`id` AS `type_id`, `t4`.`code` AS `type_code`, `t4`.`model_id`, `t6`.`automethod`," .
                " (select id from oa_doc_files t5 where t1.code = t5.code and t5.state = 'Active' and del_flg = 0 limit 1) as files_id,  " .
                " (select id from oa_doc_files  where FIND_IN_SET(t1.code,code) and  (del_flg = 0 and state!='Obsolete') limit 1) as reviewing_id, " .
                " t5.model_internal as project_name ".
                " FROM `oa_doc_code` AS `t1` " .
                " LEFT JOIN `oa_employee` AS `t2` ON t1.create_user = t2.id" .
                " LEFT JOIN `oa_employee` AS `t3` ON t1.update_user = t3.id" .
                " LEFT JOIN `oa_doc_type` AS `t4` ON t1.prefix = t4.id" .
                " LEFT JOIN `oa_doc_auto` AS `t6` ON t4.autotype = t6.id" .
                " LEFT JOIN `oa_product_catalog` AS `t5` ON t1.project_no = t5.id WHERE ($where) ORDER BY `create_time` DESC LIMIT $limit OFFSET $start";
        $data = $this->getAdapter()->query($sql)->fetchAll();

        return $data;
    }

    /**
     * 获取文件编码 新文件申请用
     */
    public function getCodeForApp($where) {
        $sql = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t1' => $this->_name))
                ->join(array('t2' => $this->_dbprefix . 'doc_type'), "t1.prefix = t2.id", array('type_id' => 'id', 'type_code' => 'code', 'model_id', 'dev_model_id'))
                ->joinLeft(array('t3' => $this->_dbprefix . 'doc_files'), "FIND_IN_SET(t1.code, t3.code) and t3.state != 'Delete' and t3.state != 'Obsolete'", array('ver'))
                ->joinLeft(array('t4' => $this->_dbprefix . 'employee'), "t1.create_user = t4.id", array('creater' => 'cname'))
                ->joinLeft(array('t5' => $this->_dbprefix . 'product_catalog'), "t1.project_no = t5.id", array('project_name' => 'model_internal'))
                ->where($where . " and t3.id is null")
                ->order(array('t1.create_time DESC'));
//        $sql = "SELECT `t1`.*, `t2`.`id` AS `type_id`, `t2`.`code` AS `type_code`, `t2`.`model_id`, `t2`.`dev_model_id`, `t3`.`ver` FROM `oa_doc_code` AS `t1` " .
//                " INNER JOIN `oa_doc_type` AS `t2` ON t1.prefix = t2.id " .
//                " LEFT JOIN `oa_doc_files` AS `t3` ON FIND_IN_SET(t1.code, t3.code) and (t3.del_flg = 0) " .
//                " WHERE ($where and t3.id is null) ORDER BY `create_time` DESC";
        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }

    /**
     * 获取已归档文件编码
     */
    public function getArchivedCode($where) {
        $sql = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t1' => $this->_name))
                ->join(array('t2' => $this->_dbprefix . 'doc_type'), "t1.prefix = t2.id", array('type_id' => 'id', 'type_code' => 'code', 'model_id', 'dev_model_id'))
                ->join(array('t3' => $this->_dbprefix . 'doc_files'), "t1.code = t3.code and t3.state='Active' and t3.del_flg=0", array('ver'))
                ->joinLeft(array('t4' => $this->_dbprefix . 'employee'), "t1.create_user = t4.id", array('creater' => 'cname'))
                ->joinLeft(array('t5' => $this->_dbprefix . 'product_catalog'), "t1.project_no = t5.id", array('project_name' => 'model_internal'))
                ->where($where)
                ->order(array('t1.code'));
        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }

    /**
     * 获取文件编码 文件升版用
     */
    public function getCodeForDev($where) {
        $sql = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t1' => $this->_name))
                ->join(array('t2' => $this->_dbprefix . 'doc_type'), "t1.prefix = t2.id", array('type_id' => 'id', 'type_code' => 'code', 'model_id', 'dev_model_id'))
                ->join(array('t3' => $this->_dbprefix . 'doc_files'), "FIND_IN_SET(t1.code, t3.code) and (t3.state = 'Active')", array('ver' => 'max(ver)', 'old_code' => 'code'))
                ->joinLeft(array('t5' => $this->_dbprefix . 'product_catalog'), "t1.project_no = t5.id", array('project_name' => 'model_internal'))
                ->where($where)
                ->where(" not EXISTS (select 1 from oa_doc_files t4 where FIND_IN_SET(t1.code,t4.code) and (t4.state = 'Reviewing' or t4.state = 'Return'))")
                ->order(array('t1.code'))
                ->group("t1.code");
        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }

    /**
     * 根据文件编码ID获取文件类别信息
     */
    public function getTypeInfo($where) {
        $sql = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t1' => $this->_name))
                ->joinLeft(array('t2' => $this->_dbprefix . 'doc_type'), "t1.prefix = t2.id", array("flow_id", "dev_flow_id"))
                ->where($where);

        $data = $this->fetchRow($sql);

        return $data;
    }

    public function getApply($like) {
        $sql = $this->select()
                ->from($this, array('code'))
                ->where('code like ?', $like)
                ->order(array('code DESC'))
                ->limit(1);
        $data = $this->fetchRow($sql);

        return $data;
    }

}