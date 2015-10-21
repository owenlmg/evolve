<?php
/**
 * 2013-9-8
 * @author mg.luo
 * @abstract
 */
class Product_Model_Desc extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'product_materiel_desc';
    protected $_primary = 'id';

    public function getList($where){
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t1.create_user = t3.id", array('creater' => 'cname'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'bpartner'), "t4.id = t1.supply1_before", array('supply1_code_before' => 'code', 'supply1_cname_before' => 'cname', 'supply1_ename_before' => 'ename'))
                    ->joinLeft(array('t5' => $this->_dbprefix.'bpartner'), "t5.id = t1.supply1_after", array('supply1_code_after' => 'code', 'supply1_cname_after' => 'cname', 'supply1_ename_before' => 'ename'))
                    ->joinLeft(array('t6' => $this->_dbprefix.'bpartner'), "t6.id = t1.supply2_before", array('supply2_code_before' => 'code', 'supply2_cname_before' => 'cname', 'supply2_ename_before' => 'ename'))
                    ->joinLeft(array('t7' => $this->_dbprefix.'bpartner'), "t7.id = t1.supply2_after", array('supply2_code_after' => 'code', 'supply2_cname_after' => 'cname', 'supply2_ename_before' => 'ename'))
                    ->joinLeft(array('t8' => $this->_dbprefix.'product_type'), "t1.type_before = t8.id", array('type_name_before' => 'name'))
                    ->joinLeft(array('t9' => $this->_dbprefix.'product_type'), "t1.type_after = t9.id", array('type_name_after' => 'name'))
                    ->where("t1.state = 'Active' or t1.state = 'Reviewing'")
                    ->where($where)
                    ->order(array('t1.state desc'));
        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }

    public function getOne($id){
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->join(array('t2' => $this->_dbprefix . 'review'), "t1.id = t2.file_id and t2.finish_flg = 0 and t2.type = 'materiel_desc'", array("review_id" => "id"))
                    ->joinLeft(array('t4' => $this->_dbprefix.'bpartner'), "t4.id = t1.supply1_before", array('supply1_code_before' => 'code', 'supply1_cname_before' => 'cname', 'supply1_ename_before' => 'ename'))
                    ->joinLeft(array('t5' => $this->_dbprefix.'bpartner'), "t5.id = t1.supply1_after", array('supply1_code_after' => 'code', 'supply1_cname_after' => 'cname', 'supply1_ename_before' => 'ename'))
                    ->joinLeft(array('t6' => $this->_dbprefix.'bpartner'), "t6.id = t1.supply2_before", array('supply2_code_before' => 'code', 'supply2_cname_before' => 'cname', 'supply2_ename_before' => 'ename'))
                    ->joinLeft(array('t7' => $this->_dbprefix.'bpartner'), "t7.id = t1.supply2_after", array('supply2_code_after' => 'code', 'supply2_cname_after' => 'cname', 'supply2_ename_before' => 'ename'))
                    ->where("t1.id=?", $id);
        $data = $this->fetchRow($sql);

        return $data;
    }

    public function getMy($type, $where, $myId) {
        $sql3 = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t1' => $this->_name))
                ->join(array('t2' => $this->_dbprefix . 'review'), "t1.id = t2.file_id and t2.finish_flg = 0 and t2.type = 'materiel_desc'", array())
                ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t1.create_user = t3.id", array('creater' => 'cname'))
                ->joinLeft(array('t4' => $this->_dbprefix.'bpartner'), "t4.id = t1.supply1_before", array('supply1_code_before' => 'code', 'supply1_cname_before' => 'cname', 'supply1_ename_before' => 'ename'))
                ->joinLeft(array('t5' => $this->_dbprefix.'bpartner'), "t5.id = t1.supply1_after", array('supply1_code_after' => 'code', 'supply1_cname_after' => 'cname', 'supply1_ename_before' => 'ename'))
                ->joinLeft(array('t6' => $this->_dbprefix.'bpartner'), "t6.id = t1.supply2_before", array('supply2_code_before' => 'code', 'supply2_cname_before' => 'cname', 'supply2_ename_before' => 'ename'))
                ->joinLeft(array('t7' => $this->_dbprefix.'bpartner'), "t7.id = t1.supply2_after", array('supply2_code_after' => 'code', 'supply2_cname_after' => 'cname', 'supply2_ename_before' => 'ename'))
                ->joinLeft(array('t8' => $this->_dbprefix.'product_type'), "t1.type_before = t8.id", array('type_name_before' => 'name'))
                ->joinLeft(array('t9' => $this->_dbprefix.'product_type'), "t1.type_after = t9.id", array('type_name_after' => 'name'))
                ->where("t1.state = 'Reviewing' and (t2.actual_user is null or !(FIND_IN_SET($myId,t2.actual_user))) and (FIND_IN_SET($myId, t2.plan_user))")
                ->where($where)
                ->where("t2.id in (select min(id) from oa_review where finish_flg = 0 and type = 'materiel_desc' GROUP BY file_id)");

        $sql1 = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t1' => $this->_name))
                ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t1.create_user = t3.id", array('creater' => 'cname'))
                ->joinLeft(array('t4' => $this->_dbprefix.'bpartner'), "t4.id = t1.supply1_before", array('supply1_code_before' => 'code', 'supply1_cname_before' => 'cname', 'supply1_ename_before' => 'ename'))
                ->joinLeft(array('t5' => $this->_dbprefix.'bpartner'), "t5.id = t1.supply1_after", array('supply1_code_after' => 'code', 'supply1_cname_after' => 'cname', 'supply1_ename_before' => 'ename'))
                ->joinLeft(array('t6' => $this->_dbprefix.'bpartner'), "t6.id = t1.supply2_before", array('supply2_code_before' => 'code', 'supply2_cname_before' => 'cname', 'supply2_ename_before' => 'ename'))
                ->joinLeft(array('t7' => $this->_dbprefix.'bpartner'), "t7.id = t1.supply2_after", array('supply2_code_after' => 'code', 'supply2_cname_after' => 'cname', 'supply2_ename_before' => 'ename'))
                ->joinLeft(array('t8' => $this->_dbprefix.'product_type'), "t1.type_before = t8.id", array('type_name_before' => 'name'))
                ->joinLeft(array('t9' => $this->_dbprefix.'product_type'), "t1.type_after = t9.id", array('type_name_after' => 'name'))
                ->where("t1.state != 'Deleted'")
                ->where($where);

        $sql2 = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t1' => $this->_name))
                ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t1.create_user = t3.id", array('creater' => 'cname'))
                ->joinLeft(array('t4' => $this->_dbprefix.'bpartner'), "t4.id = t1.supply1_before", array('supply1_code_before' => 'code', 'supply1_cname_before' => 'cname', 'supply1_ename_before' => 'ename'))
                ->joinLeft(array('t5' => $this->_dbprefix.'bpartner'), "t5.id = t1.supply1_after", array('supply1_code_after' => 'code', 'supply1_cname_after' => 'cname', 'supply1_ename_before' => 'ename'))
                ->joinLeft(array('t6' => $this->_dbprefix.'bpartner'), "t6.id = t1.supply2_before", array('supply2_code_before' => 'code', 'supply2_cname_before' => 'cname', 'supply2_ename_before' => 'ename'))
                ->joinLeft(array('t7' => $this->_dbprefix.'bpartner'), "t7.id = t1.supply2_after", array('supply2_code_after' => 'code', 'supply2_cname_after' => 'cname', 'supply2_ename_before' => 'ename'))
                ->joinLeft(array('t8' => $this->_dbprefix.'product_type'), "t1.type_before = t8.id", array('type_name_before' => 'name'))
                ->joinLeft(array('t9' => $this->_dbprefix.'product_type'), "t1.type_after = t9.id", array('type_name_after' => 'name'))
                ->where("t1.state != 'Deleted'")
                ->where($where)
                ->where("t1.id in (SELECT t2.table_id from oa_record t2 where t2.handle_user = $myId and t2.table_name = 'oa_product_materiel_desc' and t2.action = '审批')");

        if($type == 1) {
            $sqlArray = array($sql1);
        } else if($type == 2) {
            $sqlArray = array($sql2);
        } else if($type == 3) {
            $sqlArray = array($sql3);
        } else {
            $sqlArray = array($sql3, $sql1, $sql2);
        }
        $selectUnion = $this->select()->union($sqlArray, Zend_Db_Select::SQL_UNION);

        $data = $this->fetchAll($selectUnion)->toArray();

        return $data;
    }

    /**
     * 物料是否在变更中
     * @param $code
     * @return bool
     */
    public function isChanging($code) {
        $sql = $this->select()
            ->setIntegrityCheck(false)
            ->from(array($this->_name))
            ->where("(state = 'Reviewing' or state = 'Return') and code = '$code'");
        $data = $this->fetchAll($sql)->toArray();
        return count($data) > 0;
    }

}