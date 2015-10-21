<?php
/**
 * 2013-9-8
 * @author mg.luo
 * @abstract
 */
class Product_Model_Materiel extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'product_materiel';
    protected $_primary = 'id';
    
    /**
     * 获取选项列表
     * @return array
     */
    public function getOptionList($key = null)
    {
        $data = array();
        
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array('id', 'code', 'mpq', 'moq', 'name', 'description'))
                    ->joinLeft(array('t2' => $this->_dbprefix.'codemaster'), "t1.unit = t2.id", array('unit' => 'text'))
                    ->where("t1.state != 'Deleted' and t1.code != '' and t2.type_name = '单位'")
                    ->order("t1.code");
        
        if($key){
            $sql->where("t1.code like '%".$key."%' or description like '%".$key."%' or name like '%".$key."%'");
            $res = $this->fetchAll($sql);
            if($res->count() > 0){
                $data_tmp = $res->toArray();
                return $data_tmp[0];
            }
            return $data;
        }
        
        $data = $this->fetchAll($sql)->toArray();
        
        for($i = 0; $i < count($data); $i++){
            $data[$i]['description'] = str_replace("'", "\'", $data[$i]['description']);
            $data[$i]['text'] = $data[$i]['code'];
            $data[$i]['mpq'] = $data[$i]['mpq'] ? $data[$i]['mpq'] : 0;
            $data[$i]['moq'] = $data[$i]['moq'] ? $data[$i]['moq'] : 0;
        }
        
        return $data;
    }
    
    /**
     * 获取选项列表
     * @return array
     */
    public function getMaterils($key = null)
    {
        $data = array();
        
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array('id', 'code', 'mpq', 'moq', 'name', 'description'))
                    ->joinLeft(array('t2' => $this->_dbprefix.'codemaster'), "t1.unit = t2.id", array('unit' => 'text'))
                    ->where("t1.state != 'Deleted' and t1.code != '' and t2.type_name = '单位'")
                    ->order("t1.code");
        
        if($key){
            $sql->where("t1.code like '%".$key."%' or description like '%".$key."%' or name like '%".$key."%'");
        }
        
        $data = $this->fetchAll($sql)->toArray();
        
        for($i = 0; $i < count($data); $i++){
            $data[$i]['description'] = str_replace("'", "\'", $data[$i]['description']);
            $data[$i]['text'] = $data[$i]['code'];
            $data[$i]['mpq'] = $data[$i]['mpq'] ? $data[$i]['mpq'] : 0;
            $data[$i]['moq'] = $data[$i]['moq'] ? $data[$i]['moq'] : 0;
        }
        
        return $data;
    }

    public function getList($where, $start, $limit){
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t1.create_user = t3.id", array('creater' => 'cname'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'bpartner'), "t4.id = t1.supply1", array('supply_code1' => 'code', 'supply_cname1' => 'cname', 'supply_ename1' => 'ename'))
                    ->joinLeft(array('t5' => $this->_dbprefix.'bpartner'), "t5.id = t1.supply2", array('supply_code2' => 'code', 'supply_cname2' => 'cname', 'supply_ename2' => 'ename'))
                    ->joinLeft(array('t6' => $this->_dbprefix.'product_type'), "t1.type = t6.id", array('type_name' => 'name', 'auto', 'example', 'datafile_flg', 'checkreport_flg', 'tsr_flg'))
                    ->joinLeft(array('t7' => $this->_dbprefix.'codemaster'), "t1.unit = t7.id and t7.type=1", array('unit_name' => 'text'))
                    ->joinLeft(array('t8' => $this->_dbprefix.'doc_upload'), "t1.data_file_id = t8.id", array('data_file' => 'name', 'data_file_path' => 'path'))
                    ->joinLeft(array('t9' => $this->_dbprefix.'doc_upload'), "t1.first_report_id = t9.id", array('first_report' => 'name', 'first_report_path' => 'path'))
                    ->joinLeft(array('t10' => $this->_dbprefix.'doc_upload'), "t1.tsr_id = t10.id", array('tsr' => 'name', 'tsr_path' => 'path'))
                    ->joinLeft(array('t11' => $this->_dbprefix . 'product_catalog'), "t1.project_no = t11.id", array('project_name' => 'model_internal'))
                    ->where("t1.state != 'Deleted'")
                    ->where($where)
                    ->order(array('t1.archive_time desc', 't1.code desc'));
                    if(isset($limit)) {
                        $sql = $sql->limit($limit, $start);
                    }
        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }
    public function getCount($where, $start, $limit) {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t1.create_user = t3.id", array('creater' => 'cname'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'bpartner'), "t4.id = t1.supply1", array('supply_code1' => 'code', 'supply_cname1' => 'cname', 'supply_ename1' => 'ename'))
                    ->joinLeft(array('t5' => $this->_dbprefix.'bpartner'), "t5.id = t1.supply2", array('supply_code2' => 'code', 'supply_cname2' => 'cname', 'supply_ename2' => 'ename'))
                    ->joinLeft(array('t6' => $this->_dbprefix.'product_type'), "t1.type = t6.id", array('type_name' => 'name', 'auto', 'example', 'datafile_flg', 'checkreport_flg', 'tsr_flg'))
                    ->joinLeft(array('t7' => $this->_dbprefix.'codemaster'), "t1.unit = t7.id and t7.type=1", array('unit_name' => 'text'))
                    ->joinLeft(array('t8' => $this->_dbprefix.'doc_upload'), "t1.data_file_id = t8.id", array('data_file' => 'name', 'data_file_path' => 'path'))
                    ->joinLeft(array('t9' => $this->_dbprefix.'doc_upload'), "t1.first_report_id = t9.id", array('first_report' => 'name', 'first_report_path' => 'path'))
                    ->joinLeft(array('t10' => $this->_dbprefix.'doc_upload'), "t1.tsr_id = t10.id", array('tsr' => 'name', 'tsr_path' => 'path'))
                    ->joinLeft(array('t11' => $this->_dbprefix . 'product_catalog'), "t1.project_no = t11.id", array('project_name' => 'model_internal'))
                    ->where("t1.state != 'Deleted' and t1.state != 'Reviewing' and t1.state != 'Return'")
                    ->where($where)
                    ->order(array('id desc'));
        $data = $this->fetchAll($sql)->count();

        return $data;
    }

    public function getById($id){
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t4' => $this->_dbprefix.'bpartner'), "t4.id = t1.supply1", array('supply_code1' => 'code', 'supply_cname1' => 'cname', 'supply_ename1' => 'ename'))
                    ->joinLeft(array('t5' => $this->_dbprefix.'bpartner'), "t5.id = t1.supply2", array('supply_code2' => 'code', 'supply_cname2' => 'cname', 'supply_ename2' => 'ename'))
                    ->joinLeft(array('t6' => $this->_dbprefix.'product_type'), "t1.type = t6.id", array('type_name' => 'name', 'bom'))
                    ->where("t1.id = ?", $id);
        $data = $this->fetchRow($sql);

        return $data;
    }

    public function getOne($id){
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->join(array('t2' => $this->_dbprefix . 'review'), "t1.id = t2.file_id and t2.finish_flg = 0 and t2.type = 'materiel'", array("review_id" => "id"))
                    ->joinLeft(array('t3' => $this->_dbprefix.'product_type'), "t1.type = t3.id", array())
                    ->joinLeft(array('t4' => $this->_dbprefix.'employee'), "t1.create_user = t4.id", array('creater' => 'cname'))
                    ->where("t1.id=?", $id)
                    ->order('t2.id');
        $data = $this->fetchRow($sql);

        return $data;
    }

    public function getMy($type, $where, $myId, $start, $limit) {
        $sql3 = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t1' => $this->_name))
                ->join(array('t2' => $this->_dbprefix . 'review'), "t1.id = t2.file_id and t2.finish_flg = 0 and t2.type = 'materiel'", array())
                ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t1.create_user = t3.id", array('creater' => 'cname'))
                ->joinLeft(array('t4' => $this->_dbprefix.'bpartner'), "t4.id = t1.supply1", array('supply_code1' => 'code', 'supply_cname1' => 'cname', 'supply_ename1' => 'ename'))
                ->joinLeft(array('t5' => $this->_dbprefix.'bpartner'), "t5.id = t1.supply2", array('supply_code2' => 'code', 'supply_cname2' => 'cname', 'supply_ename2' => 'ename'))
                ->joinLeft(array('t6' => $this->_dbprefix.'product_type'), "t1.type = t6.id", array('type_name' => 'name', 'auto', 'example', 'datafile_flg', 'checkreport_flg', 'tsr_flg'))
                ->joinLeft(array('t7' => $this->_dbprefix.'codemaster'), "t1.unit = t7.id and t7.type=1", array('unit_name' => 'text'))
                ->joinLeft(array('t8' => $this->_dbprefix.'doc_upload'), "t1.data_file_id = t8.id", array('data_file' => 'name', 'data_file_path' => 'path'))
                ->joinLeft(array('t9' => $this->_dbprefix.'doc_upload'), "t1.first_report_id = t9.id", array('first_report' => 'name', 'first_report_path' => 'path'))
                ->joinLeft(array('t10' => $this->_dbprefix.'doc_upload'), "t1.tsr_id = t10.id", array('tsr' => 'name', 'tsr_path' => 'path'))
                ->joinLeft(array('t11' => $this->_dbprefix . 'product_catalog'), "t1.project_no = t11.id", array('project_name' => 'model_internal'))
                ->where("t1.state = 'Reviewing' and (t2.actual_user is null or !(FIND_IN_SET($myId,t2.actual_user))) and (FIND_IN_SET($myId, t2.plan_user))")
                ->where($where)
                ->where("t2.id in (select min(id) from oa_review where finish_flg = 0 and type = 'materiel' GROUP BY file_id)");

        $sql1 = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t1' => $this->_name))
                ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t1.create_user = t3.id", array('creater' => 'cname'))
                ->joinLeft(array('t4' => $this->_dbprefix.'bpartner'), "t4.id = t1.supply1", array('supply_code1' => 'code', 'supply_cname1' => 'cname', 'supply_ename1' => 'ename'))
                ->joinLeft(array('t5' => $this->_dbprefix.'bpartner'), "t5.id = t1.supply2", array('supply_code2' => 'code', 'supply_cname2' => 'cname', 'supply_ename2' => 'ename'))
                ->joinLeft(array('t6' => $this->_dbprefix.'product_type'), "t1.type = t6.id", array('type_name' => 'name', 'auto', 'example', 'datafile_flg', 'checkreport_flg', 'tsr_flg'))
                ->joinLeft(array('t7' => $this->_dbprefix.'codemaster'), "t1.unit = t7.id and t7.type=1", array('unit_name' => 'text'))
                ->joinLeft(array('t8' => $this->_dbprefix.'doc_upload'), "t1.data_file_id = t8.id", array('data_file' => 'name', 'data_file_path' => 'path'))
                ->joinLeft(array('t9' => $this->_dbprefix.'doc_upload'), "t1.first_report_id = t9.id", array('first_report' => 'name', 'first_report_path' => 'path'))
                ->joinLeft(array('t10' => $this->_dbprefix.'doc_upload'), "t1.tsr_id = t10.id", array('tsr' => 'name', 'tsr_path' => 'path'))
                ->joinLeft(array('t11' => $this->_dbprefix . 'product_catalog'), "t1.project_no = t11.id", array('project_name' => 'model_internal'))
                ->where("t1.state != 'Deleted'")
                ->where($where);

        $sql2 = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t1' => $this->_name))
                ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t1.create_user = t3.id", array('creater' => 'cname'))
                ->joinLeft(array('t4' => $this->_dbprefix.'bpartner'), "t4.id = t1.supply1", array('supply_code1' => 'code', 'supply_cname1' => 'cname', 'supply_ename1' => 'ename'))
                ->joinLeft(array('t5' => $this->_dbprefix.'bpartner'), "t5.id = t1.supply2", array('supply_code2' => 'code', 'supply_cname2' => 'cname', 'supply_ename2' => 'ename'))
                ->joinLeft(array('t6' => $this->_dbprefix.'product_type'), "t1.type = t6.id", array('type_name' => 'name', 'auto', 'example', 'datafile_flg', 'checkreport_flg', 'tsr_flg'))
                ->joinLeft(array('t7' => $this->_dbprefix.'codemaster'), "t1.unit = t7.id and t7.type=1", array('unit_name' => 'text'))
                ->joinLeft(array('t8' => $this->_dbprefix.'doc_upload'), "t1.data_file_id = t8.id", array('data_file' => 'name', 'data_file_path' => 'path'))
                ->joinLeft(array('t9' => $this->_dbprefix.'doc_upload'), "t1.first_report_id = t9.id", array('first_report' => 'name', 'first_report_path' => 'path'))
                ->joinLeft(array('t10' => $this->_dbprefix.'doc_upload'), "t1.tsr_id = t10.id", array('tsr' => 'name', 'tsr_path' => 'path'))
                ->joinLeft(array('t11' => $this->_dbprefix . 'product_catalog'), "t1.project_no = t11.id", array('project_name' => 'model_internal'))
                ->where("t1.state != 'Deleted'")
                ->where($where);

        if($type == 1) {
            $sqlArray = array($sql1);
        } else if($type == 2) {
            $ids = $this->getMyId($myId);
            if($ids) {
                $sql2 = $sql2->where("t1.id in ($ids)");
            }
            $sqlArray = array($sql2);
        } else if($type == 3) {
            $sqlArray = array($sql3);
        } else {
            $ids = $this->getMyId($myId);
            if($ids) {
                $sql2 = $sql2->where("t1.id in ($ids)");
            }
            $sqlArray = array($sql1, $sql2, $sql3);
        }
        $selectUnion = $this->select()->union($sqlArray, Zend_Db_Select::SQL_UNION)
                            ->order('state DESC')
                            ->order('update_time desc')
                            ->limit($limit, $start);

        $data = $this->fetchAll($selectUnion)->toArray();

        return $data;
    }

    public function getMyCount($type, $where, $myId) {
        $sql3 = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t1' => $this->_name))
                ->join(array('t2' => $this->_dbprefix . 'review'), "t1.id = t2.file_id and t2.finish_flg = 0 and t2.type = 'materiel'", array())
                ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t1.create_user = t3.id", array('creater' => 'cname'))
                ->where("t1.state != 'Deleted'")
                ->where($where)
                ->where("t2.id in (select min(id) from oa_review where finish_flg = 0 and type = 'materiel' GROUP BY file_id)");

        $sql1 = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t1' => $this->_name))
                ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t1.create_user = t3.id", array('creater' => 'cname'))
                ->where("t1.state != 'Deleted'")
                ->where($where);

        $sql2 = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t1' => $this->_name))
                ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t1.create_user = t3.id", array('creater' => 'cname'))
                ->where("t1.state != 'Deleted'")
                ->where($where);

        if($type == 1) {
            $sqlArray = array($sql1);
        } else if($type == 2) {
            $ids = $this->getMyId($myId);
            if($ids) {
                $sql2 = $sql2->where("t1.id in ($ids)");
            }
            $sqlArray = array($sql2);
        } else if($type == 3) {
            $sqlArray = array($sql3);
        } else {
            $ids = $this->getMyId($myId);
            if($ids) {
                $sql2 = $sql2->where("t1.id in ($ids)");
            }
            $sqlArray = array($sql1, $sql2, $sql3);
        }
        $selectUnion = $this->select()->union($sqlArray, Zend_Db_Select::SQL_UNION)
                            ->order('state DESC')
                            ->order('update_time desc');

        $data = $this->fetchAll($selectUnion)->count();

        return $data;
    }

    function getMyId($myId) {
        if($myId) {
            $idsData = $this->getAdapter()->query("SELECT group_concat(table_id) as ids from oa_record where handle_user = $myId and table_name = 'oa_product_materiel' and action = '审批'")->fetchObject();
            if($idsData && $idsData->ids) {
                return $idsData->ids;
            }
        }
        return "";
    }

    public function getArchiveList($where, $start, $limit){
        $sql = $this->select()
                    ->from(array('t1' => $this->_name))
                    ->where("t1.state = 'Active' or t1.state = 'APL'")
                    ->where($where)
                    ->order(array('t1.archive_time desc', 't1.code desc'));
        if(isset($limit)) {
            $sql = $sql->limit($limit, $start);
        }
        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }

    public function getBomList($where, $start, $limit) {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    //->join(array('t2' => $this->_dbprefix . 'product_catalog'), "t2.model_internal = t1.code", array("review_id" => "id"))
                    ->joinLeft(array('t3' => $this->_dbprefix . 'product_bom_fa'), "t3.code = t1.code", array("project_no" => "project_no"))
                    ->joinLeft(array('t4' => $this->_dbprefix . 'product_catalog'), "t4.model_internal = t1.code", array("model_internal" => "id"))
                    ->where("t1.state = 'Active' or t1.state = 'APL'")
                    //->where("t2.active = 1 and t2.delete = 0")
                    ->where($where)
                    ->group(array("t1.id"));
        if(isset($limit)) {
            $sql = $sql->limit($limit, $start);
        }
        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }

    public function getMaterielByCode($code){
        $sql = $this->select()
                    ->from($this->_name)
                    ->where("code=?", $code);
        $data = $this->fetchRow($sql);

        return $data;
    }

    public function checkExist($code){
        $sql = $this->select()
                    ->from($this->_name)
                    ->where("code=?", $code);
        return $this->fetchAll($sql)->count();
    }

    public function getListBySel($where, $start, $limit){
        $sql = $this->select()
                    ->from($this)
                    ->where("state != 'Deleted' and state != 'Return' and state != 'Reviewing'")
                    ->where($where)
                    ->order(array('code'));
                    if(isset($limit)) {
                        $sql = $sql->limit($limit, $start);
                    }
        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }
}