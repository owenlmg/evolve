<?php
/**
 * 2013-9-11 下午10:47:14
 * @author x.li
 * @abstract 
 */
class Erp_Model_Contact extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'bpartner_contact';
    protected $_primary = 'id';
    
    public function getContact($condition)
    {
    	$sql = $this->select()
			    	->setIntegrityCheck(false)
			    	->from(array('t1' => $this->_name))
			    	->joinLeft(array('t2' => $this->_dbprefix.'bpartner'), "t1.partner_id = t2.id", array('customer_id' => 'id', 'customer_active' => 'active', 'customer_code' => 'code', 'customer_name' => new Zend_Db_Expr("case when t2.cname = '' then t2.ename else t2.cname end")))
			    	->where("t2.type = ".$condition['type']." and t2.code != ''")
			    	->order(array("t2.code", "t1.name"));
    	if($condition['key']){
    		$sql->where("t2.code like '%".$condition['key']."%' or t2.cname like '%".$condition['key']."%' or t2.ename like '%".$condition['key']."%' or t1.area_code like '%".$condition['key']."%' or t1.name like '%".$condition['key']."%' or t1.tel like '%".$condition['key']."%' or t1.address like '%".$condition['key']."%'");
    	}
    	
    	$total = $this->fetchAll($sql)->count();
    	
    	$sql->limitPage($condition['page'], $condition['limit']);
    	
    	$data = $this->fetchAll($sql)->toArray();
    	
    	return array('total' => $total, 'rows' => $data);
    }
    
    // 获取地址简码列表
    public function getAddressCodeList($type = 0, $partner_id = null)
    {
        $sql = $this->select()
                    ->from($this, array('code' => 'area_code', 'name' => new Zend_Db_Expr("concat(area_code, ' [', name, ']')")))
                    ->where("area_code != ''")
                    ->order("area_code");
        
        if ($partner_id) {
            $sql->where("partner_id = ".$partner_id);
        }
        
        $data = $this->fetchAll($sql)->toArray();
        
        if ($type == 0) {
            array_push($data, array('id' => 0, 'name' => '收货人'));
        }
        
        return $data;
    }
    
    public function getData($id)
    {
        return $this->fetchRow("id = ".$id)->toArray();
    }
    
    public function getDataByCode($code)
    {
        $data = array(
                'address'   => '',
                'name'      => '',
                'tel'       => '',
                'fax'       => ''
        );
        
        $res = $this->fetchAll("area_code = '".$code."'");
        
        if($res->count() > 0){
            $tmp = $res->toArray();
            
            $data = $tmp[0];
        }
        
        return $data;
    }
}