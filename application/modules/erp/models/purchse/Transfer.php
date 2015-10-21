<?php
/**
 * 2014-7-11 下午10:47:35
 * @author x.li
 * @abstract 
 */
class Erp_Model_Purchse_Transfer extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'erp_pur_transfer';
    protected $_primary = 'id';
    
    public function getTransfer($type, $transfer_id)
    {
        $data = array();
         
        if($type && $transfer_id){
            $sql = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(array('t1' => $this->_name))
                        ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t1.create_user = t2.id", array())
                        ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t2.employee_id = t3.id", array('creater' => 'cname'))
                        ->where("t1.type = '".$type."' and t1.target_id = ".$transfer_id)
                        ->order("t1.create_time desc");
            
            $data = $this->fetchAll($sql)->toArray();
        }
         
        return $data;
    }
}