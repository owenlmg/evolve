<?php
/**
 * 2013-9-8
 * @author mg.luo
 * @abstract
 */
class Product_Model_Updbom extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'product_bom_upd';
    protected $_primary = 'id';

    public function getList($where, $start, $limit){
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t1.create_user = t3.id", array('creater' => 'cname'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'codemaster'), "t4.code = t1.reason_type and t4.type=6", array('reason_type_name' => 'text'))
                    ->where("t1.state != 'Deleted'")
                    ->where($where)
                    ->order(array('t1.archive_time desc', 't1.code desc'));
                    if(isset($limit)) {
                        $sql = $sql->limit($limit, $start);
                    }
        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }

    public function getOne($id){
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->join(array('t2' => $this->_dbprefix . 'review'), "t1.id = t2.file_id and t2.finish_flg = 0 and (t2.type = 'updbom' or t2.type = 'devbom' or t2.type = 'ecobom')", array("review_id" => "id"))
                    ->joinLeft(array('t4' => $this->_dbprefix.'employee'), "t1.create_user = t4.id", array('creater' => 'cname'))
                    ->joinLeft(array('t5' => $this->_dbprefix.'codemaster'), "t5.code = t1.reason_type and t5.type=6", array('reason_type_name' => 'text'))
                    ->where("t1.id=?", $id);
        $data = $this->fetchRow($sql);

        return $data;
    }

    public function getListByIds($where){
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'employee'), "t1.create_user = t2.id", array('creater' => 'cname'))
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t1.update_user = t3.id", array('updater' => 'cname'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'product_materiel'), "t1.mid = t4.id", array('fa_description' => 'description'))
                    ->joinLeft(array('t5' => $this->_dbprefix.'codemaster'), "t5.code = t1.reason_type and t5.type=6", array('reason_type_name' => 'text'))
                    ->where($where)
                    ->order(array('code'));
        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }

    /**
     * 获取BOM列表
     */
    public function getMy($type, $where, $myId, $start, $limit) {
    	// 该我审批的
        $sql3 = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t1' => $this->_name))
                ->join(array('t2' => $this->_dbprefix . 'review'), "t1.id = t2.file_id and t2.finish_flg = 0 and (t2.type = 'updbom' or t2.type = 'devbom' or t2.type = 'ecobom')", array())
                ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t1.create_user = t3.id", array('creater' => 'cname'))
                ->joinLeft(array('t4' => $this->_dbprefix.'codemaster'), "t4.code = t1.reason_type and t4.type=6", array('reason_type_name' => 'text'))
                ->where("t1.state = 'Reviewing' and (t2.actual_user is null or !(FIND_IN_SET($myId,t2.actual_user))) and (FIND_IN_SET($myId, t2.plan_user))")
                ->where($where);
        $ids = $this->getMyReviewing($myId);
        if($ids) {
            $sql3->where("t2.id in ($ids)");
        }
        // 我申请的
        $sql1 = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t1' => $this->_name))
                ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t1.create_user = t3.id", array('creater' => 'cname'))
                ->joinLeft(array('t4' => $this->_dbprefix.'codemaster'), "t4.code = t1.reason_type and t4.type=6", array('reason_type_name' => 'text'))
                ->where($where);
        if(!Application_Model_User::checkPermissionByRoleName('文件管理员')
        && !Application_Model_User::checkPermissionByRoleName('物料管理员')
        && !Application_Model_User::checkPermissionByRoleName('系统管理员')) {
        	$sql1 = $sql1->where("(t1.state = 'Reviewing' or t1.state = 'Return' or t1.state = 'Draft' or t1.state = 'Active') and t1.create_user = $myId");
        } else {
        	$sql1 = $sql1->where("(t1.state = 'Reviewing' or t1.state = 'Return' or t1.state = 'Active') or (t1.state = 'Draft' and t1.create_user = $myId)");
        }

        // 我审批过的
        $sql2 = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t1' => $this->_name))
                ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t1.create_user = t3.id", array('creater' => 'cname'))
                ->joinLeft(array('t4' => $this->_dbprefix.'codemaster'), "t4.code = t1.reason_type and t4.type=6", array('reason_type_name' => 'text'))
                ->where("t1.state = 'Reviewing' or t1.state = 'Return' or t1.state = 'Active'")
                ->where($where);
        $ids = $this->getMyReviewed($myId);
        if($ids) {
            $sql2->where("t1.id in ($ids)");
        }
        
        if($type == 1) {
        	$sqlArray = array($sql1);
        } else if($type == 2) {
        	$sqlArray = array($sql2);
        } else if($type == 3) {
        	$sqlArray = array($sql3);
        } else {
        	$sqlArray = array($sql3, $sql1, $sql2);
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
                ->join(array('t2' => $this->_dbprefix . 'review'), "t1.id = t2.file_id and t2.finish_flg = 0 and (t2.type = 'updbom' or t2.type = 'devbom' or t2.type = 'ecobom')", array())
                ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t1.create_user = t3.id", array('creater' => 'cname'))
                ->where("t1.state != 'Deleted'")
                ->where($where);
        $ids = $this->getMyReviewing($myId);
        if($ids) {
            $sql3->where("t2.id in ($ids)");
        }

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
        $ids = $this->getMyReviewed($myId);
        if($ids) {
            $sql2->where("t1.id in ($ids)");
        }
        if($type == 1) {
        	$sqlArray = array($sql1);
        } else if($type == 2) {
        	$sqlArray = array($sql2);
        } else if($type == 3) {
        	$sqlArray = array($sql3);
        } else {
        	$sqlArray = array($sql1, $sql2, $sql3);
        }
    	$selectUnion = $this->select()->union($sqlArray, Zend_Db_Select::SQL_UNION)
    	                    ->order('state DESC')
    	                    ->order('update_time desc');

        $data = $this->fetchAll($selectUnion)->count();

        return $data;
    }
    
    function getMyReviewed($myId) {
        $sql = "SELECT group_concat(t2.table_id) as ids from oa_record t2 where t2.handle_user = $myId and (t2.type = 'updbom' or t2.type = 'devbom' or t2.type = 'ecobom') and t2.action = '审批'";
        $data = $this->getAdapter()->query($sql)->fetchObject();
        if($data && $data->ids) {
            return $data->ids;
        }
    }
    
    function getMyReviewing($myId) {
        $sql = "select min(id) as id from oa_review where finish_flg = 0 and (type = 'updbom' or type = 'devbom' or type = 'ecobom') GROUP BY file_id";
        $data = $this->getAdapter()->query($sql)->fetchAll();
        $result = array();
        if($data) {
            foreach($data as $d) {
                $result[] = $d['id'];
            }
        }
        return implode(',', $result);
    }
}