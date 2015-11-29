<?php
/**
 * 2013-9-11 下午10:47:35
 * @author x.li
 * @abstract 
 */
class Erp_Model_Purchse_Orderitemsreq extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'erp_pur_order_items_req';
    protected $_primary = 'id';
    
    public function getOrderItemReqInfo($order_item_id)
    {
    	$info = array();
    	
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array(
                            'req_item_id', 
                            'qty'
                    ))
                    ->joinLeft(array('t2' => $this->_dbprefix.'erp_pur_req_items'), 't1.req_item_id = t2.id', array(
                            'req_item_qty' => 'qty'
                    ))
                    ->joinLeft(array('t3' => $this->_dbprefix.'erp_pur_req'), 't2.req_id = t3.id', array(
                            'req_number'    => 'number',
                            'req_time'      => 'create_time'
                    ))
                    ->where("t1.order_item_id = ".$order_item_id)
                    ->order("t3.create_time");
        
        $data = $this->fetchAll($sql)->toArray();
        
        foreach ($data as $d){
            if ($d['req_item_id'] != '') {
                array_push($info, array(
                    'req_item_id'   => $d['req_item_id'],
                    'qty'           => $d['qty'],
                    'req_item_qty'  => $d['req_item_qty'],
                    'req_number'    => $d['req_number'],
                    'req_time'      => $d['req_time'],
                ));
            }
        }
    	
    	return $info;
    }
    
    // 取消订单处理
    public function cancelByItemId($item_id)
    {
        if($item_id){
            $this->update(array('active' => 0), "order_item_id = ".$item_id);
        }
    }
    
    public function getData($req_id)
    {
        $data = array();
        
        return $data;
    }
    
    public function getQty($type, $item_id, $req_split = true)
    {
        $qty = 0;
        
        $sql = null;
        
        if($type == 'order'){
            // 获取订单数量
            $sql = $this->select()
                        ->from($this->_name, array('qty' => new Zend_Db_Expr("sum(qty)")))
                        ->where("active = 1 and order_item_id = ".$item_id);
            
        }else if($type == 'req'){
            // 获取申请单已下单数量
            if($req_split){
                $sql = $this->select()
                            ->setIntegrityCheck(false)
                            ->from(array('t1' => $this->_name), array())
                            ->joinLeft(array('t2' => $this->_dbprefix.'erp_pur_order_items'), "t1.order_item_id = t2.id", array('qty' => new Zend_Db_Expr("sum(t2.qty)")))
                            ->where("t1.active = 1 and t1.req_item_id = ".$item_id);
            }else{
                $sql = $this->select()
                            ->from($this->_name, array('qty' => new Zend_Db_Expr("sum(qty)")))
                            ->where("active = 1 and req_item_id = ".$item_id);
            }
        }
        
        if($sql){
            $res = $this->fetchAll($sql);
            
            if($res->count() > 0){
                $data = $res->toArray();
                
                if($data[0]['qty'] > 0){
                    $qty = $data[0]['qty'];
                }
            }
        }
        
        return $qty;
    }
}