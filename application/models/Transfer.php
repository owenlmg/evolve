<?php
/**
 * 2013-7-15 下午12:49:35
 * @author x.li
 * @abstract
 */
class Application_Model_Transfer extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'transfer';
    protected $_primary = 'id';
    
    // 批准变更内容（更新变更内容状态）
    public function approveTransferContent($type, $transfer_id)
    {
    	$res = $this->fetchAll("type = '".$type."' and transfer_id = ".$transfer_id, 'id desc');
    	
    	if($res->count() > 0){
    		$data = $res->toArray();
    		
    		// 仅更新最后一项变更内容
    		$this->update(array('active' => 1), "id = ".$data[0]['id']);
    	}
    }
    
    public function getTransfer($type, $transfer_id)
    {
    	$data = array();
    	
    	if($type && $transfer_id){
    		$sql = $this->select()
			    		->setIntegrityCheck(false)
			    		->from(array('t1' => $this->_name))
			    		->joinLeft(array('t2' => $this->_dbprefix.'user'), "t1.create_user = t2.id", array())
			    		->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t2.employee_id = t3.id", array('creater' => 'cname'))
			    		->where("t1.type = '".$type."' and t1.transfer_id = ".$transfer_id)
			    		->order("t1.create_time desc");
    		
    		$data = $this->fetchAll($sql)->toArray();
    	}
    	
    	return $data;
    }
}