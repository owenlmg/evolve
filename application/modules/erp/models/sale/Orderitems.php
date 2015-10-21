<?php
/**
 * 2013-9-11 下午10:47:35
 * @author x.li
 * @abstract 
 */
class Erp_Model_Sale_Orderitems extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'erp_sale_order_items';
    protected $_primary = 'id';
    
    public function getDeliveryNoticeItems($time, $diff)
    {
        $data = array();
        
        $diffSql = new Zend_Db_Expr("CAST(SUBSTRING(TIMEDIFF('".$time."', delivery_date_update_time), 1, 2) AS UNSIGNED)");
        
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array(
                            'id',
                            'order_id',
                            'code',
                            'qty',
                            'name',
                            'description',
                            'request_date',
                            'delivery_date', 
                            'delivery_date_remark', 
                            'delivery_date_update_time',
                            'diff' => $diffSql))
                    ->joinLeft(array('t2' => $this->_dbprefix.'erp_sale_order'), "t1.order_id = t2.id", array(
                            'order_number'  => 'number',
                            'create_user_id'=> 'create_user'
                    ))
                    ->joinLeft(array('t3' => $this->_dbprefix.'user'), "t2.create_user = t3.id", array())
                    ->joinLeft(array('t4' => $this->_dbprefix.'employee'), "t3.employee_id = t4.id", array(
                            'create_user_name'  => 'cname',
                            'create_user_email' => 'email'
                    ))
                    ->where("t1.delivery_date_update_time != '' and ".$diffSql." < ".$diff)
                    ->order("t1.order_id");
        
        $data = $this->fetchAll($sql)->toArray();
        
        return $data;
    }
    
    public function getOpenOrderByCode($code)
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array())
                    ->joinLeft(array('t2' => $this->_dbprefix.'erp_sale_order'), "t1.order_id = t2.id", array(
                            'number'))
                    ->where("t1.code = '".$code."' and t2.state != 2")
                    ->order("t2.number");
        $data = $this->fetchAll($sql)->toArray();
        
        return $data;
    }
    
    // 根据订单ID取消订单项
    public function cancelByOrderId($order_id)
    {
        if($order_id){
            $this->update(array('active' => 0), "order_id = ".$order_id);
        }
    }
    
    public function getData($order_id)
    {
        $result = array();
        
        $data = $this->fetchAll("order_id = ".$order_id)->toArray();
        $item = new Erp_Model_Sale_Receiveitemsordersale();
        
        foreach ($data as $d){
            $active = $d['active'] == 1 ? true : false;
            $price_tax = $d['price_tax'] == 1 ? true : false;
            
            $qty_send = $item->getQty('order', $d['id']);// 交货（负数）
            
            array_push($result, array(
                'items_id'                  => $d['id'],
                'items_type'                => $d['type'],
                'items_order_id'            => $d['order_id'],
                'items_active'              => $active,
                'items_code'                => $d['code'],
                'items_name'                => $d['name'],
                'items_description'         => $d['description'],
                'items_qty'                 => $d['qty'],
                'items_qty_send'              => $qty_send,
                'items_unit'                => $d['unit'],
                'items_price'               => $d['price'],
                'items_price_tax'           => $price_tax,
                'items_total'               => $d['total'],
                'items_request_date'        => $d['request_date'],
                'items_customer_code'       => $d['customer_code'],
                'items_customer_description'=> $d['customer_description'],
                'items_remark'              => $d['remark']
            ));
        }
        
        return $result;
    }
    
    public function refreshOrderTotal($order_id)
    {
        $data = array(
                'total'                 => 0,// 含税金额
                'forein_total'          => 0// 外币含税金额
        );
        
        if($order_id){
            $dataTmp = $this->fetchAll("order_id = ".$order_id);
            
            if($dataTmp->count() > 0){
                $items = $dataTmp->toArray();
                
                foreach ($items as $item){
                    $data['total'] += $item['total'];
                }
            }
        }
        
        $order = new Erp_Model_Sale_Order();
        $orderData = $order->getData(null, $order_id);
        
        // 如果不是本币（汇率不为1），则需要将外币换算为本币
        if($orderData['currency_rate'] != 1){
            $data['forein_total'] = $data['total'];
            
            $data['total'] = $data['forein_total'] * $orderData['currency_rate'];
        }
        
        $order->update($data, "id = ".$order_id);
    }
}