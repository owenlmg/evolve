<?php
/**
 * 2013-9-8
 * @author mg.luo
 * @abstract 
 */
class Product_Model_Transfer extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'product_materiel_transfer';
    protected $_primary = 'id';
    
    public function getList($where){
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t1.create_user = t3.id", array('creater' => 'cname'))
                    ->join(array('t4' => $this->_dbprefix.'product_materiel'), "t1.mid = t4.id", array('description'))
                    ->where("t1.state = 'Active' or t1.state = 'Reviewing'")
                    ->where($where)
                    ->order(array('id desc'));
        $data = $this->fetchAll($sql)->toArray();
        
        return $data;
    }
    
    public function getOne($id){
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->join(array('t2' => $this->_dbprefix . 'review'), "t1.id = t2.file_id and t2.finish_flg = 0 and t2.type = 'materiel_transfer'", array("review_id" => "id"))
                    ->where("t1.id=?", $id);
        $data = $this->fetchRow($sql);

        return $data;
    }

    public function getMy($type, $where, $myId) {
        $sql3 = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t1' => $this->_name))
                ->join(array('t2' => $this->_dbprefix . 'review'), "t1.id = t2.file_id and t2.finish_flg = 0 and t2.type = 'materiel_transfer'", array())
                ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t1.create_user = t3.id", array('creater' => 'cname'))
                ->join(array('t4' => $this->_dbprefix.'product_materiel'), "t1.mid = t4.id", array('description'))
                ->where("t1.state != 'Deleted'")
                ->where($where)
                ->where("t2.id in (select min(id) from oa_review where finish_flg = 0 and type = 'materiel_transfer' GROUP BY file_id)");
                
        $sql1 = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t1' => $this->_name))
                ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t1.create_user = t3.id", array('creater' => 'cname'))
                ->join(array('t4' => $this->_dbprefix.'product_materiel'), "t1.mid = t4.id", array('description'))
                ->where("t1.state != 'Deleted'")
                ->where($where);
                
        $sql2 = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t1' => $this->_name))
                ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t1.create_user = t3.id", array('creater' => 'cname'))
                ->join(array('t4' => $this->_dbprefix.'product_materiel'), "t1.mid = t4.id", array('description'))
                ->where("t1.state != 'Deleted'")
                ->where($where)
                ->where("t1.id in (SELECT t2.table_id from oa_record t2 where t2.handle_user = $myId and t2.table_name = 'oa_product_materiel_transfer' and t2.action = '审批')");

        if($type == 1) {
        	$sqlArray = array($sql1);
        } else if($type == 2) {
        	$sqlArray = array($sql2);
        } else if($type == 3) {
        	$sqlArray = array($sql3);
        } else {
        	$sqlArray = array($sql1, $sql2, $sql3);
        }
    	$selectUnion = $this->select()->union($sqlArray, Zend_Db_Select::SQL_UNION);

        $data = $this->fetchAll($selectUnion)->toArray();

        return $data;
    }
}