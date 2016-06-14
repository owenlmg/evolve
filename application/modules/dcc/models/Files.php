<?php

/**
 * 2013-7-17
 * @author mg.luo
 * @abstract
 */
class Dcc_Model_Files extends Application_Model_Db {

    /**
     * 表名、主键
     */
    protected $_name = 'doc_files';
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
                ->order(array('(select case t1.state when "Reviewing" then 1 when "Return" then 2 else 3 end)','t1.archive_time desc', 't1.code desc', 't1.ver desc'))
                ->limit($limit, $start);
        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }
    public function getCount($where, $start, $limit) {
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
                ->order(array('t1.archive_time desc', 't1.code', 't1.ver desc'));
        $data = $this->fetchAll($sql)->count();

        return $data;
    }

    public function getDataById($id) {
        $sql = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t1' => $this->_name), array('id', 'code', 'ver', 'file_ids', 'name', 'state', 'create_user', 'update_user', 'description', 'project_info'))
                ->joinLeft(array('t2' => $this->_dbprefix . 'doc_upload'), "FIND_IN_SET(t2.id,t1.file_ids)", array('file_id' => 'id', 'file_name' => 'name', 'path', 'view_path'))
                ->where("t1.id=?", $id);
        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }

    public function getFilesListForEdit($where, $start, $limit) {
        $sql = $this->select()
	                ->setIntegrityCheck(false)
	                ->from(array('t1' => $this->_name))
	                ->joinLeft(array('t2' => $this->_dbprefix . 'employee'), "t1.create_user = t2.id", array('creater' => 'cname'))
	                ->joinLeft(array('t3' => $this->_dbprefix . 'employee'), "t1.update_user = t3.id", array('updater' => 'cname'))
	                ->joinLeft(array('t4' => $this->_dbprefix . 'doc_code'), "t1.code = t4.code", array('prefix', 'project_no', 'code_description' => 'description'))
	                ->joinLeft(array('t5' => $this->_dbprefix . 'doc_type'), "t4.prefix = t5.id", array('type_id' => 'id', 'type_code' => 'code', 'type_name' => 'name', 'model_id'))
	                ->joinLeft(array('t6' => $this->_dbprefix . 'doc_upgrade'), "t1.id = t6.file_id", array('reason', 'reason_type', 'newest_ver' => 'ver_original', 'future_ver' => 'ver'))
	                ->joinLeft(array('t7' => $this->_dbprefix . 'product_catalog'), "t4.project_no = t7.id", array('project_name' => 'model_internal'))
                    ->joinLeft(array('t9' => $this->_dbprefix . 'codemaster'), "t5.category = t9.code and t9.type=4", array('category' => 'id', 'category_name' => 'text'))
                    ->joinLeft(array('t10' => $this->_dbprefix . 'codemaster'), "t6.reason_type = t10.code and t10.type=5", array('reason_type_name' => 'text'))
	                ->where($where)
	                ->order(array('(select case t1.state when "Reviewing" then 1 when "Return" then 2 else 3 end)', 't1.id desc'));
        if(isset($limit) && isset($start)) {
        	$sql = $sql->limit($limit, $start);
        }

        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }

    public function getCountForEdit($where, $start, $limit) {
        $sql = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t1' => $this->_name))
                ->joinLeft(array('t2' => $this->_dbprefix . 'employee'), "t1.create_user = t2.id", array('creater' => 'cname'))
                ->joinLeft(array('t3' => $this->_dbprefix . 'employee'), "t1.update_user = t3.id", array('updater' => 'cname'))
                ->joinLeft(array('t4' => $this->_dbprefix . 'doc_code'), "t1.code = t4.code", array('prefix', 'project_no', 'code_description' => 'description'))
                ->joinLeft(array('t5' => $this->_dbprefix . 'doc_type'), "t4.prefix = t5.id", array('type_id' => 'id', 'type_code' => 'code', 'type_name' => 'name', 'model_id'))
                ->joinLeft(array('t6' => $this->_dbprefix . 'doc_upgrade'), "t1.id = t6.file_id", array('reason', 'newest_ver' => 'ver_original', 'future_ver' => 'ver'))
                ->joinLeft(array('t7' => $this->_dbprefix . 'product_catalog'), "t4.project_no = t7.id", array('project_name' => 'model_internal'))
                ->joinLeft(array('t8' => $this->_dbprefix . 'codemaster'), "t6.reason_type is not null and t6.reason_type = t8.id and t8.type=5", array('reason_type_name' => 'text'))
                ->joinLeft(array('t9' => $this->_dbprefix . 'codemaster'), "t5.category = t9.code and t9.type=4", array('category' => 'id', 'category_name' => 'text'))
                ->where($where)
                ->order(array('t1.state desc', 't1.id desc'));
        $data = $this->fetchAll($sql)->count();

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

    public function getVer($code) {
        $sql = $this->select()
                ->from(array('t1' => $this->_name), array('ver'))
                ->where('t1.state = "Active" and t1.code = ?', $code)
                ->order(array('ver DESC'))
                ->limit(1);
        $data = $this->fetchRow($sql);

        return $data;
    }

    public function getOne($id) {
        $sql = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t1' => $this->_name))
                ->joinLeft(array('t2' => $this->_dbprefix . 'employee'), "t1.create_user = t2.id", array('creater' => 'cname'))
                ->where("t1.id = ?", $id);
        $data = $this->fetchRow($sql);

        return $data;
    }

    public function getMy($type, $where, $myId, $file_ids, $start, $limit) {
            // 我申请或更新的
            $sql1_1 = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t0' => $this->_dbprefix . 'doc_code'), "t0.code = t1.code", array('prefix', 'project_no', 'code_description' => 'description'))
                    ->joinLeft(array('t' => $this->_dbprefix . 'doc_type'), "t0.prefix = t.id", array('model_id', 'flow_id', 'dev_model_id', 'dev_flow_id', 'type_name' => 'name'))
                    ->joinLeft(array('t2' => $this->_dbprefix . 'employee'), "t1.create_user = t2.id", array('creater' => 'cname'))
                    ->joinLeft(array('t3' => $this->_dbprefix . 'doc_upgrade'), "t1.id = t3.file_id", array('reason', 'reason_type', 'newest_ver' => 'ver_original', 'future_ver' => 'ver'))
                    ->joinLeft(array('t5' => $this->_dbprefix . 'product_catalog'), "t0.project_no = t5.id", array('project_name' => 'model_internal'))
                    ->joinLeft(array('t8' => $this->_dbprefix . 'codemaster'), "t.category = t8.code and t8.type=4", array('category' => 'id', 'category_name' => 'text'))
                    ->where("t1.code not like '%,%' and t1.state != 'Deleted' and t1.del_flg = 0 and (t1.create_user = $myId or t1.update_user = $myId)")
                    ->where($where);
          $sql1_2 = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t0' => $this->_dbprefix . 'doc_code'), "FIND_IN_SET(t0.code, t1.code) and t0.active =1", array('prefix', 'project_no', 'code_description' => 'description'))
                    ->joinLeft(array('t' => $this->_dbprefix . 'doc_type'), "t0.prefix = t.id", array('model_id', 'flow_id', 'dev_model_id', 'dev_flow_id', 'type_name' => 'name'))
                    ->joinLeft(array('t2' => $this->_dbprefix . 'employee'), "t1.create_user = t2.id", array('creater' => 'cname'))
                    ->joinLeft(array('t3' => $this->_dbprefix . 'doc_upgrade'), "t1.id = t3.file_id", array('reason', 'reason_type', 'newest_ver' => 'ver_original', 'future_ver' => 'ver'))
                    ->joinLeft(array('t5' => $this->_dbprefix . 'product_catalog'), "t0.project_no = t5.id", array('project_name' => 'model_internal'))
                    ->joinLeft(array('t8' => $this->_dbprefix . 'codemaster'), "t.category = t8.code and t8.type=4", array('category' => 'id', 'category_name' => 'text'))
                    ->where("t1.code like '%,%' and t1.state != 'Deleted' and t1.state != 'Active' and t1.del_flg = 0 and (t1.create_user = $myId or t1.update_user = $myId)")
                    ->where($where);
            // 我审核过的
            $sql2_1 = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t0' => $this->_dbprefix . 'doc_code'), "t0.code = t1.code", array('prefix', 'project_no', 'code_description' => 'description'))
                    ->joinLeft(array('t' => $this->_dbprefix . 'doc_type'), "t0.prefix = t.id", array('model_id', 'flow_id', 'dev_model_id', 'dev_flow_id', 'type_name' => 'name'))
                    ->joinLeft(array('t2' => $this->_dbprefix . 'employee'), "t1.create_user = t2.id", array('creater' => 'cname'))
                    ->joinLeft(array('t3' => $this->_dbprefix . 'doc_upgrade'), "t1.id = t3.file_id", array('reason', 'reason_type', 'newest_ver' => 'ver_original', 'future_ver' => 'ver'))
                    ->joinLeft(array('t5' => $this->_dbprefix . 'product_catalog'), "t0.project_no = t5.id", array('project_name' => 'model_internal'))
                    ->joinLeft(array('t8' => $this->_dbprefix . 'codemaster'), "t.category = t8.code and t8.type=4", array('category' => 'id', 'category_name' => 'text'))
                    ->where("t1.code not like '%,%' and t1.state != 'Deleted' and t1.del_flg = 0")
                    ->where($where);
            $sql2_2 = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t0' => $this->_dbprefix . 'doc_code'), "FIND_IN_SET(t0.code, t1.code) and t0.active =1", array('prefix', 'project_no', 'code_description' => 'description'))
                    ->joinLeft(array('t' => $this->_dbprefix . 'doc_type'), "t0.prefix = t.id", array('model_id', 'flow_id', 'dev_model_id', 'dev_flow_id', 'type_name' => 'name'))
                    ->joinLeft(array('t2' => $this->_dbprefix . 'employee'), "t1.create_user = t2.id", array('creater' => 'cname'))
                    ->joinLeft(array('t3' => $this->_dbprefix . 'doc_upgrade'), "t1.id = t3.file_id", array('reason', 'reason_type', 'newest_ver' => 'ver_original', 'future_ver' => 'ver'))
                    ->joinLeft(array('t5' => $this->_dbprefix . 'product_catalog'), "t0.project_no = t5.id", array('project_name' => 'model_internal'))
                    ->joinLeft(array('t8' => $this->_dbprefix . 'codemaster'), "t.category = t8.code and t8.type=4", array('category' => 'id', 'category_name' => 'text'))
                    ->where("t1.code like '%,%' and t1.state != 'Deleted' and t1.del_flg = 0")
                    ->where($where);
            // 我将要审核的
            $sql3_1 = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->join(array('t4' => $this->_dbprefix . 'review'), "t1.id = t4.file_id and t4.finish_flg = 0 and t4.type = 'files'", array())
                    ->joinLeft(array('t0' => $this->_dbprefix . 'doc_code'), "t0.code = t1.code", array('prefix', 'project_no', 'code_description' => 'description'))
                    ->joinLeft(array('t' => $this->_dbprefix . 'doc_type'), "t0.prefix = t.id", array('model_id', 'flow_id', 'dev_model_id', 'dev_flow_id', 'type_name' => 'name'))
                    ->joinLeft(array('t2' => $this->_dbprefix . 'employee'), "t1.create_user = t2.id", array('creater' => 'cname'))
                    ->joinLeft(array('t3' => $this->_dbprefix . 'doc_upgrade'), "t1.id = t3.file_id", array('reason', 'reason_type', 'newest_ver' => 'ver_original', 'future_ver' => 'ver'))
                    ->joinLeft(array('t5' => $this->_dbprefix . 'product_catalog'), "t0.project_no = t5.id", array('project_name' => 'model_internal'))
                    ->joinLeft(array('t8' => $this->_dbprefix . 'codemaster'), "t.category = t8.code and t8.type=4", array('category' => 'id', 'category_name' => 'text'))
                    ->where("t1.code not like '%,%' and t1.state = 'Reviewing' and (t4.actual_user is null or !(FIND_IN_SET($myId,t4.actual_user))) and (FIND_IN_SET($myId, t4.plan_user))")
                    ->where("t4.id in (select min(id) from oa_review where finish_flg = 0 and type = 'files' GROUP BY file_id)")
                    ->where($where);
            $sql3_2 = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->join(array('t4' => $this->_dbprefix . 'review'), "t1.id = t4.file_id and t4.finish_flg = 0 and t4.type = 'files'", array())
                    ->joinLeft(array('t0' => $this->_dbprefix . 'doc_code'), "FIND_IN_SET(t0.code, t1.code) and t0.active =1", array('prefix', 'project_no', 'code_description' => 'description'))
                    ->joinLeft(array('t' => $this->_dbprefix . 'doc_type'), "t0.prefix = t.id", array('model_id', 'flow_id', 'dev_model_id', 'dev_flow_id', 'type_name' => 'name'))
                    ->joinLeft(array('t2' => $this->_dbprefix . 'employee'), "t1.create_user = t2.id", array('creater' => 'cname'))
                    ->joinLeft(array('t3' => $this->_dbprefix . 'doc_upgrade'), "t1.id = t3.file_id", array('reason', 'reason_type', 'newest_ver' => 'ver_original', 'future_ver' => 'ver'))
                    ->joinLeft(array('t5' => $this->_dbprefix . 'product_catalog'), "t0.project_no = t5.id", array('project_name' => 'model_internal'))
                    ->joinLeft(array('t8' => $this->_dbprefix . 'codemaster'), "t.category = t8.code and t8.type=4", array('category' => 'id', 'category_name' => 'text'))
                    ->where("t1.code like '%,%' and t1.state = 'Reviewing' and (t4.actual_user is null or !(FIND_IN_SET($myId,t4.actual_user))) and (FIND_IN_SET($myId, t4.plan_user))")
                    ->where("t4.id in (select min(id) from oa_review where finish_flg = 0 and type = 'files' GROUP BY file_id)")
                    ->where($where);
            // 共享给我的
            $sql4_1 = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t0' => $this->_dbprefix . 'doc_code'), "t0.code = t1.code", array('prefix', 'project_no', 'code_description' => 'description'))
                    ->joinLeft(array('t' => $this->_dbprefix . 'doc_type'), "t0.prefix = t.id", array('model_id', 'flow_id', 'dev_model_id', 'dev_flow_id', 'type_name' => 'name'))
                    ->joinLeft(array('t2' => $this->_dbprefix . 'employee'), "t1.create_user = t2.id", array('creater' => 'cname'))
                    ->joinLeft(array('t3' => $this->_dbprefix . 'doc_upgrade'), "t1.id = t3.file_id", array('reason', 'reason_type', 'newest_ver' => 'ver_original', 'future_ver' => 'ver'))
                    ->joinLeft(array('t5' => $this->_dbprefix . 'product_catalog'), "t0.project_no = t5.id", array('project_name' => 'model_internal'))
                    ->joinLeft(array('t8' => $this->_dbprefix . 'codemaster'), "t.category = t8.code and t8.type=4", array('category' => 'id', 'category_name' => 'text'))
                    ->where("t1.code not like '%,%'")
                    ->where($where);
            $sql4_2 = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t0' => $this->_dbprefix . 'doc_code'), "FIND_IN_SET(t0.code, t1.code) and t0.active =1", array('prefix', 'project_no', 'code_description' => 'description'))
                    ->joinLeft(array('t' => $this->_dbprefix . 'doc_type'), "t0.prefix = t.id", array('model_id', 'flow_id', 'dev_model_id', 'dev_flow_id', 'type_name' => 'name'))
                    ->joinLeft(array('t2' => $this->_dbprefix . 'employee'), "t1.create_user = t2.id", array('creater' => 'cname'))
                    ->joinLeft(array('t3' => $this->_dbprefix . 'doc_upgrade'), "t1.id = t3.file_id", array('reason', 'reason_type', 'newest_ver' => 'ver_original', 'future_ver' => 'ver'))
                    ->joinLeft(array('t5' => $this->_dbprefix . 'product_catalog'), "t0.project_no = t5.id", array('project_name' => 'model_internal'))
                    ->joinLeft(array('t8' => $this->_dbprefix . 'codemaster'), "t.category = t8.code and t8.type=4", array('category' => 'id', 'category_name' => 'text'))
                    ->where("t1.code like '%,%'")
                    ->where($where);
            if(count($file_ids) > 0) {
            	$sql4_1 = $sql4_1->where("t1.id in (" . implode(',', $file_ids) . ")");
            	$sql4_2 = $sql4_2->where("t1.id in (" . implode(',', $file_ids) . ")");
            }
        $sql4 = $this->select()->union(array($sql4_1, $sql4_2), Zend_Db_Select::SQL_UNION);
        $sql3 = $this->select()->union(array($sql3_1, $sql3_2), Zend_Db_Select::SQL_UNION);
        
        $sql1 = $this->select()->union(array($sql1_1, $sql1_2), Zend_Db_Select::SQL_UNION);
            if($type == 1) {
            	$sqlArray = array($sql1);
            } else if($type == 2) {
            	$ids = $this->getMyId($myId);
            	if($ids) {
            		$sql2_1 = $sql2_1->where("t1.id in ($ids)");
            		$sql2_2 = $sql2_2->where("t1.id in ($ids)");
            	} else {
            		$sql2_1 = $sql2_1->where("t1.id is null");
            		$sql2_2 = $sql2_2->where("t1.id is null");
            	}
                $sql2 = $this->select()->union(array($sql2_1, $sql2_2), Zend_Db_Select::SQL_UNION);
            	$sqlArray = array($sql2);
            } else if($type == 3) {
            	$sqlArray = array($sql3);
            } else if($type == 4) {
            	$sqlArray = array($sql4);
            } else {
            	$ids = $this->getMyId($myId);
            	if($ids) {
            		$sql2_1 = $sql2_1->where("t1.id in ($ids)");
            		$sql2_2 = $sql2_2->where("t1.id in ($ids)");
            	} else {
            		$sql2_1 = $sql2_1->where("t1.id is null");
            		$sql2_2 = $sql2_2->where("t1.id is null");
            	}
                $sql2 = $this->select()->union(array($sql2_1, $sql2_2), Zend_Db_Select::SQL_UNION);
            	$sqlArray = array($sql3, $sql1, $sql2, $sql4);
            }
        	$selectUnion = $this->select()->union($sqlArray, Zend_Db_Select::SQL_UNION)
        	                    ->order('(select case t1.state when "Reviewing" then 1 when "Return" then 2 else 3 end)')
        	                    ->limit($limit, $start);
    
            $data = $this->fetchAll($selectUnion)->toArray();
    
            return $data;
    }

    public function getMyCount($type, $where, $myId, $file_ids) {
            // 我申请或更新的
            $sql1_1 = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t0' => $this->_dbprefix . 'doc_code'), "t0.code = t1.code", array('prefix', 'project_no', 'code_description' => 'description'))
                    ->joinLeft(array('t' => $this->_dbprefix . 'doc_type'), "t0.prefix = t.id", array('model_id', 'flow_id', 'dev_model_id', 'dev_flow_id', 'type_name' => 'name'))
                    ->joinLeft(array('t2' => $this->_dbprefix . 'employee'), "t1.create_user = t2.id", array('creater' => 'cname'))
                    ->joinLeft(array('t3' => $this->_dbprefix . 'doc_upgrade'), "t1.id = t3.file_id", array('reason', 'reason_type', 'newest_ver' => 'ver_original', 'future_ver' => 'ver'))
                    ->joinLeft(array('t5' => $this->_dbprefix . 'product_catalog'), "t0.project_no = t5.id", array('project_name' => 'model_internal'))
                    ->joinLeft(array('t8' => $this->_dbprefix . 'codemaster'), "t.category = t8.code and t8.type=4", array('category' => 'id', 'category_name' => 'text'))
                    ->where("t1.code not like '%,%' and t1.state != 'Deleted' and t1.del_flg = 0 and (t1.create_user = $myId or t1.update_user = $myId)")
                    ->where($where);
            $sql1_2 = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t0' => $this->_dbprefix . 'doc_code'), "FIND_IN_SET(t0.code, t1.code) and t0.active =1", array('prefix', 'project_no', 'code_description' => 'description'))
                    ->joinLeft(array('t' => $this->_dbprefix . 'doc_type'), "t0.prefix = t.id", array('model_id', 'flow_id', 'dev_model_id', 'dev_flow_id', 'type_name' => 'name'))
                    ->joinLeft(array('t2' => $this->_dbprefix . 'employee'), "t1.create_user = t2.id", array('creater' => 'cname'))
                    ->joinLeft(array('t3' => $this->_dbprefix . 'doc_upgrade'), "t1.id = t3.file_id", array('reason', 'reason_type', 'newest_ver' => 'ver_original', 'future_ver' => 'ver'))
                    ->joinLeft(array('t5' => $this->_dbprefix . 'product_catalog'), "t0.project_no = t5.id", array('project_name' => 'model_internal'))
                    ->joinLeft(array('t8' => $this->_dbprefix . 'codemaster'), "t.category = t8.code and t8.type=4", array('category' => 'id', 'category_name' => 'text'))
                    ->where("t1.code like '%,%' and t1.state != 'Deleted' and t1.state != 'Active' and t1.del_flg = 0 and (t1.create_user = $myId or t1.update_user = $myId)")
                    ->where($where);
            // 我审核过的
            $sql2_1 = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t0' => $this->_dbprefix . 'doc_code'), "t0.code = t1.code", array('prefix', 'project_no', 'code_description' => 'description'))
                    ->joinLeft(array('t' => $this->_dbprefix . 'doc_type'), "t0.prefix = t.id", array('model_id', 'flow_id', 'dev_model_id', 'dev_flow_id', 'type_name' => 'name'))
                    ->joinLeft(array('t2' => $this->_dbprefix . 'employee'), "t1.create_user = t2.id", array('creater' => 'cname'))
                    ->joinLeft(array('t3' => $this->_dbprefix . 'doc_upgrade'), "t1.id = t3.file_id", array('reason', 'reason_type', 'newest_ver' => 'ver_original', 'future_ver' => 'ver'))
                    ->joinLeft(array('t5' => $this->_dbprefix . 'product_catalog'), "t0.project_no = t5.id", array('project_name' => 'model_internal'))
                    ->joinLeft(array('t8' => $this->_dbprefix . 'codemaster'), "t.category = t8.code and t8.type=4", array('category' => 'id', 'category_name' => 'text'))
                    ->where("t1.code not like '%,%' and t1.state != 'Deleted' and t1.del_flg = 0")
                    ->where($where);
            $sql2_2 = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t0' => $this->_dbprefix . 'doc_code'), "FIND_IN_SET(t0.code, t1.code) and t0.active =1", array('prefix', 'project_no', 'code_description' => 'description'))
                    ->joinLeft(array('t' => $this->_dbprefix . 'doc_type'), "t0.prefix = t.id", array('model_id', 'flow_id', 'dev_model_id', 'dev_flow_id', 'type_name' => 'name'))
                    ->joinLeft(array('t2' => $this->_dbprefix . 'employee'), "t1.create_user = t2.id", array('creater' => 'cname'))
                    ->joinLeft(array('t3' => $this->_dbprefix . 'doc_upgrade'), "t1.id = t3.file_id", array('reason', 'reason_type', 'newest_ver' => 'ver_original', 'future_ver' => 'ver'))
                    ->joinLeft(array('t5' => $this->_dbprefix . 'product_catalog'), "t0.project_no = t5.id", array('project_name' => 'model_internal'))
                    ->joinLeft(array('t8' => $this->_dbprefix . 'codemaster'), "t.category = t8.code and t8.type=4", array('category' => 'id', 'category_name' => 'text'))
                    ->where("t1.code like '%,%' and t1.state != 'Deleted' and t1.del_flg = 0")
                    ->where($where);
            // 我将要审核的
            $sql3_1 = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->join(array('t4' => $this->_dbprefix . 'review'), "t1.id = t4.file_id and t4.finish_flg = 0 and t4.type = 'files'", array())
                    ->joinLeft(array('t0' => $this->_dbprefix . 'doc_code'), "t0.code = t1.code", array('prefix', 'project_no', 'code_description' => 'description'))
                    ->joinLeft(array('t' => $this->_dbprefix . 'doc_type'), "t0.prefix = t.id", array('model_id', 'flow_id', 'dev_model_id', 'dev_flow_id', 'type_name' => 'name'))
                    ->joinLeft(array('t2' => $this->_dbprefix . 'employee'), "t1.create_user = t2.id", array('creater' => 'cname'))
                    ->joinLeft(array('t3' => $this->_dbprefix . 'doc_upgrade'), "t1.id = t3.file_id", array('reason', 'reason_type', 'newest_ver' => 'ver_original', 'future_ver' => 'ver'))
                    ->joinLeft(array('t5' => $this->_dbprefix . 'product_catalog'), "t0.project_no = t5.id", array('project_name' => 'model_internal'))
                    ->joinLeft(array('t8' => $this->_dbprefix . 'codemaster'), "t.category = t8.code and t8.type=4", array('category' => 'id', 'category_name' => 'text'))
                    ->where("t1.code not like '%,%' and t1.state = 'Reviewing' and (t4.actual_user is null or !(FIND_IN_SET($myId,t4.actual_user))) and (FIND_IN_SET($myId, t4.plan_user))")
                    ->where("t4.id in (select min(id) from oa_review where finish_flg = 0 and type = 'files' GROUP BY file_id)")
                    ->where($where);
            $sql3_2 = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->join(array('t4' => $this->_dbprefix . 'review'), "t1.id = t4.file_id and t4.finish_flg = 0 and t4.type = 'files'", array())
                    ->joinLeft(array('t0' => $this->_dbprefix . 'doc_code'), "FIND_IN_SET(t0.code, t1.code) and t0.active =1", array('prefix', 'project_no', 'code_description' => 'description'))
                    ->joinLeft(array('t' => $this->_dbprefix . 'doc_type'), "t0.prefix = t.id", array('model_id', 'flow_id', 'dev_model_id', 'dev_flow_id', 'type_name' => 'name'))
                    ->joinLeft(array('t2' => $this->_dbprefix . 'employee'), "t1.create_user = t2.id", array('creater' => 'cname'))
                    ->joinLeft(array('t3' => $this->_dbprefix . 'doc_upgrade'), "t1.id = t3.file_id", array('reason', 'reason_type', 'newest_ver' => 'ver_original', 'future_ver' => 'ver'))
                    ->joinLeft(array('t5' => $this->_dbprefix . 'product_catalog'), "t0.project_no = t5.id", array('project_name' => 'model_internal'))
                    ->joinLeft(array('t8' => $this->_dbprefix . 'codemaster'), "t.category = t8.code and t8.type=4", array('category' => 'id', 'category_name' => 'text'))
                    ->where("t1.code like '%,%' and t1.state = 'Reviewing' and (t4.actual_user is null or !(FIND_IN_SET($myId,t4.actual_user))) and (FIND_IN_SET($myId, t4.plan_user))")
                    ->where("t4.id in (select min(id) from oa_review where finish_flg = 0 and type = 'files' GROUP BY file_id)")
                    ->where($where);
            // 共享给我的
            $sql4_1 = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t0' => $this->_dbprefix . 'doc_code'), "t0.code = t1.code", array('prefix', 'project_no', 'code_description' => 'description'))
                    ->joinLeft(array('t' => $this->_dbprefix . 'doc_type'), "t0.prefix = t.id", array('model_id', 'flow_id', 'dev_model_id', 'dev_flow_id', 'type_name' => 'name'))
                    ->joinLeft(array('t2' => $this->_dbprefix . 'employee'), "t1.create_user = t2.id", array('creater' => 'cname'))
                    ->joinLeft(array('t3' => $this->_dbprefix . 'doc_upgrade'), "t1.id = t3.file_id", array('reason', 'reason_type', 'newest_ver' => 'ver_original', 'future_ver' => 'ver'))
                    ->joinLeft(array('t5' => $this->_dbprefix . 'product_catalog'), "t0.project_no = t5.id", array('project_name' => 'model_internal'))
                    ->joinLeft(array('t8' => $this->_dbprefix . 'codemaster'), "t.category = t8.code and t8.type=4", array('category' => 'id', 'category_name' => 'text'))
                    ->where("t1.code not like '%,%'")
                    ->where($where);
            $sql4_2 = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t0' => $this->_dbprefix . 'doc_code'), "FIND_IN_SET(t0.code, t1.code) and t0.active =1", array('prefix', 'project_no', 'code_description' => 'description'))
                    ->joinLeft(array('t' => $this->_dbprefix . 'doc_type'), "t0.prefix = t.id", array('model_id', 'flow_id', 'dev_model_id', 'dev_flow_id', 'type_name' => 'name'))
                    ->joinLeft(array('t2' => $this->_dbprefix . 'employee'), "t1.create_user = t2.id", array('creater' => 'cname'))
                    ->joinLeft(array('t3' => $this->_dbprefix . 'doc_upgrade'), "t1.id = t3.file_id", array('reason', 'reason_type', 'newest_ver' => 'ver_original', 'future_ver' => 'ver'))
                    ->joinLeft(array('t5' => $this->_dbprefix . 'product_catalog'), "t0.project_no = t5.id", array('project_name' => 'model_internal'))
                    ->joinLeft(array('t8' => $this->_dbprefix . 'codemaster'), "t.category = t8.code and t8.type=4", array('category' => 'id', 'category_name' => 'text'))
                    ->where("t1.code like '%,%'")
                    ->where($where);
            if(count($file_ids) > 0) {
            	$sql4_1 = $sql4_1->where("t1.id in (" . implode(',', $file_ids) . ")");
            	$sql4_2 = $sql4_2->where("t1.id in (" . implode(',', $file_ids) . ")");
            }
            $sql4 = $this->select()->union(array($sql4_1, $sql4_2), Zend_Db_Select::SQL_UNION);
            $sql3 = $this->select()->union(array($sql3_1, $sql3_2), Zend_Db_Select::SQL_UNION);
            $sql1 = $this->select()->union(array($sql1_1, $sql1_2), Zend_Db_Select::SQL_UNION);
            if($type == 1) {
            	$sqlArray = array($sql1);
            } else if($type == 2) {
            	$ids = $this->getMyId($myId);
            	if($ids) {
            		$sql2_1 = $sql2_1->where("t1.id in ($ids)");
            		$sql2_2 = $sql2_2->where("t1.id in ($ids)");
            	} else {
            		$sql2_1 = $sql2_1->where("t1.id is null");
            		$sql2_2 = $sql2_2->where("t1.id is null");
            	}
                $sql2 = $this->select()->union(array($sql2_1, $sql2_2), Zend_Db_Select::SQL_UNION);
            	$sqlArray = array($sql2);
            } else if($type == 3) {
            	$sqlArray = array($sql3);
            } else if($type == 4) {
            	$sqlArray = array($sql4);
            } else {
            	$ids = $this->getMyId($myId);
            	if($ids) {
            		$sql2_1 = $sql2_1->where("t1.id in ($ids)");
            		$sql2_2 = $sql2_2->where("t1.id in ($ids)");
            	} else {
            		$sql2_1 = $sql2_1->where("t1.id is null");
            		$sql2_2 = $sql2_2->where("t1.id is null");
            	}
                $sql2 = $this->select()->union(array($sql2_1, $sql2_2), Zend_Db_Select::SQL_UNION);
            	$sqlArray = array($sql3, $sql1, $sql2, $sql4);
            }
        	$selectUnion = $this->select()->union($sqlArray, Zend_Db_Select::SQL_UNION)
        	                    ->order('create_time desc');
    
            $data = $this->fetchAll($selectUnion)->toArray();
    
            return $data;
    }

    function getMyId($myId) {
    	if($myId) {
    	    $idsData = $this->getAdapter()->query("SELECT group_concat(table_id) as ids from oa_record where handle_user = $myId and table_name = 'oa_doc_files' and action = '审批'")->fetchObject();
    	    if($idsData && $idsData->ids) {
    	    	return $idsData->ids;
    	    }
    	}
    	return "";
    }

    public function obsoleteOldFile() {
    	$where = "";
    	$this->update();
    }

}