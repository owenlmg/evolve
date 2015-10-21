<?php
/**
 * 2013-9-8
 * @author mg.luo
 * @abstract
 */
class Product_Model_Newbom extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'product_bom_new';
    protected $_primary = 'id';

    public function getList($where, $start, $limit){
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t1.create_user = t3.id", array('creater' => 'cname'))
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
                    ->join(array('t2' => $this->_dbprefix . 'review'), "t1.id = t2.file_id and t2.finish_flg = 0 and t2.type = 'bom'", array("review_id" => "id"))
                    ->joinLeft(array('t4' => $this->_dbprefix.'employee'), "t1.create_user = t4.id", array('creater' => 'cname'))
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
                    ->where($where)
                    ->order(array('code'));
        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }

    /**
     * 获取文件编码 新文件申请用
     */
    public function getCodeForApp($where){
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'doc_type'), "t1.prefix = t2.id", array('type_id' => 'id', 'type_code' => 'code'))
                    ->joinLeft(array('t3' => $this->_dbprefix.'doc_files'), "t1.code = t3.code and (t3.state = 'Active' or t3.state = 'Obsolete')", array())
                    ->where($where." and t3.id is null")
                    ->order(array('code'));
        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }

    /**
     * 获取文件编码 文件升版用
     */
    public function getCodeForDev($where){
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t4' => $this->_dbprefix.'doc_type'), "t1.prefix = t4.id", array('type_id' => 'id', 'type_code' => 'code'))
                    ->where($where)
                    ->order(array('code'));
        $data = $this->fetchAll($sql)->toArray();

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

    /**
     * 获取BOM列表
     */
    public function getMy($type, $where, $myId, $start, $limit) {
        // 该我审批的
        $sql3 = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t1' => $this->_name))
                ->join(array('t2' => $this->_dbprefix . 'review'), "t1.id = t2.file_id and t2.finish_flg = 0 and t2.type = 'bom'", array())
                ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t1.create_user = t3.id", array('creater' => 'cname'))
                ->where("t1.state = 'Reviewing' and (t2.actual_user is null or !(FIND_IN_SET($myId,t2.actual_user))) and (FIND_IN_SET($myId, t2.plan_user))")
                ->where($where);
        $ids = $this->getMyReviewing($myId);
        if($ids) {
            $sql3->where("t2.id in ($ids)");
        } else {
            $sql3->where("t2.id is null");
        }
        // 我申请的
        $sql1 = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t1' => $this->_name))
                ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t1.create_user = t3.id", array('creater' => 'cname'))
                ->where($where);
        if(!Application_Model_User::checkPermissionByRoleName('BOM管理员')
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
                ->where("t1.state = 'Reviewing' or t1.state = 'Return' or t1.state = 'Active'")
                ->where($where);
        $ids = $this->getMyReviewed($myId);
        if($ids) {
            $sql2->where("t1.id in ($ids)");
        } else {
            $sql2->where("t1.id is null");
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

        // 判断是否包含子数据
//        $son = new Product_Model_Son();
//        for($i = 0; $i < count($data); $i++){
//            $data[$i]['leaf'] = false;
//            $data[$i]['children'] = $son->getTree($data[$i]['id'], false);
//        }
//
//        $fabom = array(
//                'id'            => null,
//                'pid'      => null,
//                'name'          => '',
//                'description'   => '',
//                'remark'        => '',
//                'active'         => null,
//                'leaf'          => false,
//                'children'      => $data
//        );

        return $data;
    }

    public function getMyCount($type, $where, $myId) {
        $sql3 = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t1' => $this->_name))
                ->join(array('t2' => $this->_dbprefix . 'review'), "t1.id = t2.file_id and t2.finish_flg = 0 and t2.type = 'bom'", array())
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
                ->where($where);
        if(!Application_Model_User::checkPermissionByRoleName('BOM管理员')
        && !Application_Model_User::checkPermissionByRoleName('系统管理员')) {
            $sql1 = $sql1->where("(t1.state = 'Reviewing' or t1.state = 'Return' or t1.state = 'Draft' or t1.state = 'Active') and t1.create_user = $myId");
        } else {
            $sql1 = $sql1->where("(t1.state = 'Reviewing' or t1.state = 'Return' or t1.state = 'Active') or (t1.state = 'Draft' and t1.create_user = $myId)");
        }

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
        $ids = array();
        $sql = "SELECT t2.table_id as ids from oa_record t2 where t2.handle_user = $myId and t2.type = 'bom' and t2.action = '审批'";
//        $sql = "SELECT group_concat(t2.table_id) as ids from oa_record t2 where t2.handle_user = $myId and t2.type = 'bom' and t2.action = '审批'";
        $data = $this->getAdapter()->query($sql)->fetchObject();
        if($data && $data->ids) {
            $ids[] = $data->ids;
        }
        return implode(',', $ids);
    }
    
    function getMyReviewing($myId) {
        $sql = "select min(id) as id from oa_review where finish_flg = 0 and type = 'bom' GROUP BY file_id";
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