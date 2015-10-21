<?php
/**
 * 2013-9-11 下午10:47:35
 * @author x.li
 * @abstract 
 */
class Erp_Model_Purchse_Buyerwork extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'erp_pur_buyer_work';
    protected $_primary = 'id';
    
    // 获取列表
    public function getMember($buyer_id)
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array())
                    ->joinLeft(array('t2' => $this->_dbprefix.'product_type'), "t2.id = t1.type_id", array('id', 'name' => new Zend_Db_Expr("concat(code, ' ', name)")))
                    ->where("buyer_id = ".$buyer_id)
                    ->order("t2.code");
        $data = $this->fetchAll($sql)->toArray();
        
        return $data;
    }
    
    public function getMemberId($buyer_id)
    {
        $data = array();
        
        $member_data = $this->getMember($buyer_id);
        
        foreach ($member_data as $member){
            array_push($data, intval($member['id']));
        }
        
        return $data;
    }
}