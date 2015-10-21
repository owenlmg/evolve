<?php
/**
 * 2013-9-8
 * @author mg.luo
 * @abstract
 */
class Product_Model_Fa extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'product_bom_fa';
    protected $_primary = 'id';

    public function getList($where, $start, $limit){
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'product_bom_new'), "t1.nid = t2.id and t1.type='new'", array("description_new" => "description", "remark_new" => "remark", "archive_time_new" => "archive_time"))
                    ->join(array('t3' => $this->_dbprefix.'product_materiel'), "t1.id = t3.id", array("name", "description", "materiel_type" => "type"))
                    ->joinLeft(array('t4' => $this->_dbprefix.'product_bom_upd'), "t1.nid = t4.id and (t1.type = 'DEV' or t1.type = 'ECO')", array("upd_type", "upd_reason", "reason_type", "description_upd" => "description", "remark_upd" => "remark", "archive_time_upd" => "archive_time"))
                    ->joinLeft(array('t5' => $this->_dbprefix.'product_catalog'), "t1.project_no = t5.id", array('project_no_name' => 'model_internal'))
                    ->joinLeft(array('t6' => $this->_dbprefix.'codemaster'), "t6.code = t4.reason_type and t6.type=6", array('reason_type_name' => 'text'))
                    ->where($where)
                    ->order(array('state', 'sid desc', 'code desc', 'ver desc'));
        if(isset($limit) && $limit) {
            $sql = $sql->limit($limit, $start);
        }

        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }
    
    public function getListMy($where, $start, $limit, $myId){
        $sql = $this->select()
        ->setIntegrityCheck(false)
        ->from(array('t1' => $this->_name))
        ->joinLeft(array('t2' => $this->_dbprefix.'product_bom_new'), "t1.nid = t2.id and t1.type='new'", array("description_new" => "description", "remark_new" => "remark", "archive_time_new" => "archive_time"))
        ->join(array('t3' => $this->_dbprefix.'product_materiel'), "t1.id = t3.id", array("name", "description", "materiel_type" => "type"))
        ->joinLeft(array('t4' => $this->_dbprefix.'product_bom_upd'), "t1.nid = t4.id and (t1.type = 'DEV' or t1.type = 'ECO')", array("upd_type", "upd_reason", "reason_type", "description_upd" => "description", "remark_upd" => "remark", "archive_time_upd" => "archive_time"))
        ->joinLeft(array('t5' => $this->_dbprefix.'product_catalog'), "t1.project_no = t5.id", array('project_no_name' => 'model_internal'))
        ->joinLeft(array('t6' => $this->_dbprefix.'codemaster'), "t6.code = t4.reason_type and t6.type=6", array('reason_type_name' => 'text'))
        ->where("t1.create_user=".$myId)
        ->where($where)
        ->order(array('state', 'sid desc', 'code desc', 'ver desc'));
        if(isset($limit) && $limit) {
            $sql = $sql->limit($limit, $start);
        }
    
        $data = $this->fetchAll($sql)->toArray();
    
        return $data;
    }
    
    public function updateArchiveTime() {
        $data = $this->fetchAll("bom_upd_time is null or bom_upd_time = '0000-00-00 00:00:00'")->toArray();
        $newbom = new Product_Model_Newbom();
        $updbom = new Product_Model_Updbom();
        foreach($data as $fa) {
            if(!$fa['nid']) {
                continue;
            }
            $nid = $fa['nid'];
            if($fa['ver'] == '1.0') {
                $row = $newbom->fetchRow("id = $nid");
                if($row) {
                    $archive_time = $row['archive_time'];
                }
            } else {
                $row = $updbom->fetchRow("id = $nid");
                if($row) {
                    $archive_time = $row['archive_time'];
                }
            }
            if(isset($archive_time)) {
                $this->update(array('bom_upd_time' => $archive_time), "sid = ".$fa['sid']);
            }
        }
    }

    public function getFaList($where){
        $sql = $this->select()
                    ->from($this->_name)
                    ->where($where);
        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }

    public function getListCount($where){
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'product_bom_new'), "t1.nid = t2.id", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'product_materiel'), "t1.id = t3.id", array("name", "description"))
                    ->joinLeft(array('t4' => $this->_dbprefix.'product_bom_upd'), "t1.nid = t4.id and (t1.type = 'DEV' or t1.type = 'ECO')", array("upd_type", "upd_reason", "reason_type", "description_upd" => "description", "remark_upd" => "remark", "archive_time_upd" => "archive_time"))
                    ->joinLeft(array('t5' => $this->_dbprefix.'product_catalog'), "t1.project_no = t5.id", array('project_no_name' => 'model_internal'))
                    ->joinLeft(array('t6' => $this->_dbprefix.'codemaster'), "t6.code = t4.reason_type and t6.type=6", array('reason_type_name' => 'text'))
                    ->where($where)
                    ->order(array('code desc', 'ver desc'));
        return $this->fetchAll($sql)->count();
    }

    public function getListCountMy($where, $myId){
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'product_bom_new'), "t1.nid = t2.id", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'product_materiel'), "t1.id = t3.id", array("name", "description"))
                    ->joinLeft(array('t4' => $this->_dbprefix.'product_bom_upd'), "t1.nid = t4.id and (t1.type = 'DEV' or t1.type = 'ECO')", array("upd_type", "upd_reason", "reason_type", "description_upd" => "description", "remark_upd" => "remark", "archive_time_upd" => "archive_time"))
                    ->joinLeft(array('t5' => $this->_dbprefix.'product_catalog'), "t1.project_no = t5.id", array('project_no_name' => 'model_internal'))
                    ->joinLeft(array('t6' => $this->_dbprefix.'codemaster'), "t6.code = t4.reason_type and t6.type=6", array('reason_type_name' => 'text'))
                    ->where("t1.create_user=".$myId)
                    ->where($where)
                    ->order(array('code desc', 'ver desc'));
        return $this->fetchAll($sql)->count();
    }

    /**
     * @abstract    获取树数据
     */
    public function getOne($recordkey)
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->join(array('t2' => $this->_dbprefix.'product_materiel'), "t1.id = t2.id", array('name', 'description', 'mstate' => 'state'))
                    ->joinLeft(array('t5' => $this->_dbprefix.'product_catalog'), "t1.project_no = t5.id", array('project_no_name' => 'model_internal'))
                    ->where("recordkey = '$recordkey'");

        return $this->fetchAll($sql)->toArray();
    }

    /**
     * @abstract    获取树数据
     */
    public function getFa($code, $ver)
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->join(array('t2' => $this->_dbprefix.'product_materiel'), "t1.id = t2.id", array('name', 'description', 'mstate' => 'state'))
                    ->joinLeft(array('t5' => $this->_dbprefix.'product_catalog'), "t1.project_no = t5.id", array('project_no_name' => 'model_internal'))
                    ->where("t1.code = '$code'");
        if(isset($ver) && $ver) {
            $sql = $sql->where("ver='$ver'");
        }
        $sql = $sql->order("ver desc");

        return $this->fetchAll($sql)->toArray();
    }
}