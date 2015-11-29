<?php
/**
 * 2013-9-11 下午10:47:35
 * @author x.li
 * @abstract 
 */
class Erp_Model_Purchse_Receiveitems extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'erp_stock_receive_items';
    protected $_primary = 'id';
    
    public function getOrderNumbersByReceiveId($id, $type = '采购收货')// 采购订单 / 销售订单来源
    {
        $data = array(
                'order_number'  => array(),
                'supplier'      => array(),
                'customer'      => array()
        );
        
        if ($type == '采购收货') {
            $sql = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(array('t1' => $this->_name), array("order_number"))
                        ->joinLeft(array('t2' => $this->_dbprefix.'erp_pur_order'), "t2.number = t1.order_number", array())
                        ->joinLeft(array('t3' => $this->_dbprefix.'bpartner'), "t3.id = t2.supplier_id", array('supplier' => new Zend_Db_Expr("case when t3.ename != '' then concat(t3.code, ' ', t3.ename) else concat(t3.code, ' ', t3.cname) end")))
                        ->where("t1.receive_id = ".$id)
                        ->group("t1.order_number");
        }else{
            $sql = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(array('t1' => $this->_name), array("order_number"))
                        ->joinLeft(array('t2' => $this->_dbprefix.'erp_sale_order'), "t2.number = t1.order_number", array())
                        ->joinLeft(array('t3' => $this->_dbprefix.'bpartner'), "t3.id = t2.customer_id", array('customer' => new Zend_Db_Expr("case when t3.ename != '' then concat(t3.code, ' ', t3.ename) else concat(t3.code, ' ', t3.cname) end")))
                        ->where("t1.receive_id = ".$id)
                        ->group("t1.order_number");
        }
        
        $number = $this->fetchAll($sql)->toArray();
        
        foreach ($number as $n){
            array_push($data['order_number'], $n['order_number']);
            
            if ($type == '采购收货') {
                array_push($data['supplier'], $n['supplier']);
            }else{
                array_push($data['customer'], $n['customer']);
            }
            
        }
        
        return $data;
    }
    
    public function getReceivedOrderItems($order_number)
    {
    	$data = array();
    	
    	$res = $this->fetchAll("order_number = '".$order_number."'");
    	
    	if($res->count()){
    		$data = $res->toArray();
    	}
    	
    	return $data;
    }
    
    public function getData($receive_id)
    {
        $result = array();
        
        $data = $this->fetchAll("receive_id = ".$receive_id)->toArray();
        
        foreach ($data as $d){
            $code = $d['product_code'] != '' ? $d['product_code'] : $d['code'];
            $code_internal = $d['product_code'] != '' ? $d['code'] : '';
            
            array_push($result, array(
                'items_id'                      => $d['id'],
                'items_order_id'                => $d['receive_id'],
                'items_order_number'            => $d['order_number'],
                'items_code'                    => $code,
                'items_code_internal'           => $code_internal,
                'items_name'                    => $d['name'],
                'items_description'             => $d['description'],
                'items_warehouse_code'          => $d['warehouse_code'],
                'items_warehouse_code_transfer' => $d['warehouse_code_transfer'],
                'items_qty'                     => $d['qty'],
                'items_price'                   => $d['price'],
                'items_customer_code'           => $d['customer_code'],
                'items_customer_description'    => $d['customer_description'],
                'items_unit'                    => $d['unit'],
                'items_total'                   => $d['total'],
                'items_remark'                  => $d['remark']
            ));
        }
        
        return $result;
    }
    
    // 更新收货单总计金额
    public function refreshReceiveTotal($receive_id)
    {
        $total = 0;
        
        if($receive_id){
            $data = $this->fetchAll("receive_id = ".$receive_id);
            
            if($data->count() > 0){
                $items = $data->toArray();
                
                foreach ($items as $item){
                    $total += $item['total'];
                }
            }
        }
        
        $receive = new Erp_Model_Stock_Receive();
        
        $receive->update(array('total' => $total), "id = ".$receive_id);
    }
}