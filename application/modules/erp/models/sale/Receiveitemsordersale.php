<?php
/**
 * 2013-9-11 下午10:47:35
 * @author x.li
 * @abstract 
 */
class Erp_Model_Sale_Receiveitemsordersale extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'erp_stock_receive_items_order_sale';
    protected $_primary = 'id';
    
    public function getOrderItemSendQty($order_item_id)
    {
        $receiveData = array();
        
        if($order_item_id){
            $sql = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(array('t1' => $this->_name), array('code', 'qty'))
                        ->joinLeft(array('t2' => $this->_dbprefix.'erp_stock_receive_items'), "t1.receive_item_id = t2.id", array())
                        ->joinLeft(array('t3' => $this->_dbprefix.'erp_stock_receive'), "t2.receive_id = t3.id", array('number', 'create_time'))
                        ->where("t1.order_item_id = ".$order_item_id);
            
            $data = $this->fetchAll($sql)->toArray();
            
            foreach ($data as $d){
                array_push($receiveData, array(
                    'number'=> $d['number'],
                    'code'  => $d['code'],
                    'qty'   => $d['qty'],
                    'time'  => $d['create_time']
                ));
            }
        }
        
        return $receiveData;
    }
    
    public function getCanBeBackQty($key = null, $customer_id = null)
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array(
                            'order_item_id', 
                            'code', 
                            'price', 
                            'order_id', 
                            'order_number', 
                            'qty' => new Zend_Db_Expr("sum(t1.qty)")
                    ))
                    ->joinLeft(array('t3' => $this->_dbprefix.'erp_sale_order'), "t1.order_number = t3.number", array(
                            'sales_id'
                    ))
                    ->joinLeft(array('t2' => $this->_dbprefix.'erp_sale_order_items'), "t3.id = t2.order_id", array(/*  and t3.code = t1.code */
                            'name',
                            'description',
                            'customer_code',
                            'customer_description'
                    ))
                    ->joinLeft(array('t4' => $this->_dbprefix.'bpartner'), "t3.customer_id = t4.id", array(
                            'customer_code' => 'code', 
                            'customer_name' => new Zend_Db_Expr("case when t4.cname != '' then t4.cname else t4.ename end")
                    ))
                    ->joinLeft(array('t5' => $this->_dbprefix.'erp_stock_receive_items'), "t1.receive_item_id = t5.id", array(
                            'unit', 
                            'warehouse_code'
                    ))
                    ->joinLeft(array('t6' => $this->_dbprefix.'erp_stock_receive'), "t5.receive_id = t6.id", array(
                            'receive_number' => 'number'
                    ))
                    ->where("t1.active = 1 and t1.locked = 0")
                    ->group(array("t1.order_id", "t1.code"));
        
        if($key){
            $sql->where("t1.code like '%".$key."%' 
                    or t1.order_number like '%".$key."%' 
                    or t2.name like '%".$key."%' 
                    or t2.description like '%".$key."%'
                    or t2.customer_code like '%".$key."%'
                    or t2.customer_description like '%".$key."%'");
        }
        
        if ($customer_id) {
            $sql->where("t4.id = ".$customer_id);
        }
        
        $data = $this->fetchAll($sql)->toArray();
        
        $sales = new Erp_Model_Sale_Sales();
        /* echo '<pre>';
        print_r($data);
        exit; */
        for($i = 0; $i < count($data); $i++){
            if($data[$i]['sales_id']){
                $salesData = $sales->getData($data[$i]['sales_id']);
                $data[$i]['order_sales'] = $salesData['cname'];
            }
        }
        
        return $data;
    }
    
    // 取消交货处理
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
    
    public function getQty($type, $item_id)
    {
        $qty = 0;
        
        $sql = $this->select()
                    ->from($this->_name, array('qty' => new Zend_Db_Expr("sum(qty)")))
                    ->where("active = 1");
        
        if($type == 'sale_receive'){
            // 获取交货数量
            $sql->where("receive_item_id = ".$item_id);
        }else if($type == 'order'){
            // 获取销售订单已交货数量
            $sql->where("order_item_id = ".$item_id);
        }
        
        $data = $this->fetchRow($sql)->toArray();
        
        if($data['qty'] > 0){
            $qty = $data['qty'];
        }
        
        return $qty;
    }
}