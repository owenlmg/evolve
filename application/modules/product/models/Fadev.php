<?php
/**
 * 2013-9-8
 * @author mg.luo
 * @abstract
 */
class Product_Model_Fadev extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'product_bom_fa_dev';
    protected $_primary = 'id';

    public function getList($where){
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t4' => $this->_dbprefix.'product_materiel'), "t1.id = t4.id", array('name', 'type', 'state', 'description'))
                    ->joinLeft(array('t5' => $this->_dbprefix.'product_catalog'), "t1.project_no = t5.id", array('project_no_name' => 'model_internal'))
                    ->where($where)
                    ->order(array('code'));
        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }

    public function getFaList($where){
        $sql = $this->select()
                    ->from($this->_name)
                    ->where($where);
        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }

    public function getListByIds($where){
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'employee'), "t1.create_user = t2.id", array('creater' => 'cname'))
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t1.update_user = t3.id", array('updater' => 'cname'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'product_materiel'), "t1.id = t4.id", array('fa_description' => 'description'))
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
     * @abstract    获取树数据
     * @param       number  $parentId  上级ID
     * @param       boolen  $root       是否为最上级
     * @return      array   $dept
     */
    public function getData($parentId = 0, $root = true)
    {
        $dept = array();
        $data = array();

        $sql = $this->select()
                    ->from(array('t1' => $this->_name))
                    ->where("parent_id = ".$parentId)
                    ->order(array('code'));

        $data = $this->fetchAll($sql)->toArray();

        // 判断是否包含子类别
        for($i = 0; $i < count($data); $i++){
            $data[$i]['active'] = $data[$i]['active'] == 1 ? true : false;
            $data[$i]['auto'] = $data[$i]['auto'] == 1 ? true : false;

            if($this->fetchAll("parent_id = ".$data[$i]['id'])->count() > 0){
                $data[$i]['leaf'] = false;
                $data[$i]['children'] = $this->getData($data[$i]['id'], false);
            }else{
                $data[$i]['leaf'] = true;
            }
        }

        // 格式化根数据格式
        if($root){
            $dept = array(
                    'id'            => null,
                    'parent_id'      => null,
                    'name'          => '',
                    'description'   => '',
                    'remark'        => '',
                    'active'         => null,
                    'leaf'          => false,
                    'children'      => $data
            );
        }else{
            $dept = $data;
        }

        return $dept;
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
                ->where("t1.state != 'Deleted'")
                ->where($where)
                ->where("t2.id in (select min(id) from oa_review where finish_flg = 0 and type = 'bom' GROUP BY file_id)");

        // 我申请的
        $sql1 = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t1' => $this->_name))
                ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t1.create_user = t3.id", array('creater' => 'cname'))
                ->where("t1.state != 'Deleted'")
                ->where($where);

        // 我审批过的
        $sql2 = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t1' => $this->_name))
                ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t1.create_user = t3.id", array('creater' => 'cname'))
                ->where("t1.state != 'Deleted'")
                ->where($where)
                ->where("t1.id in (SELECT t2.table_id from oa_record t2 where t2.handle_user = $myId and t2.type = 'bom' and t2.action = '审批')");

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
                ->where("t1.state != 'Deleted'")
                ->where($where)
                ->where("t2.id in (select min(id) from oa_review where finish_flg = 0 and type = 'bom' GROUP BY file_id)");

        $sql1 = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t1' => $this->_name))
                ->where("t1.state != 'Deleted'")
                ->where($where);

        $sql2 = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t1' => $this->_name))
                ->where("t1.state != 'Deleted'")
                ->where($where)
                ->where("t1.id in (SELECT t2.table_id from oa_record t2 where t2.handle_user = $myId and t2.type = 'bom' and t2.action = '审批')");

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
}