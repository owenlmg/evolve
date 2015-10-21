<?php
/**
 * 2013-9-11 下午10:47:35
 * @author x.li
 * @abstract 
 */
class Erp_Model_Purchse_Orderitems extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'erp_pur_order_items';
    protected $_primary = 'id';
    
    public function getReqInfoByOrderItemId($id)
    {
        $data = array();
        
        $sql = $this->select()
                    ->from(array('t1' => $this->_name), array())
                    ->setIntegrityCheck(false)
                    ->joinLeft(array('t2' => $this->_dbprefix.'erp_pur_order_items_req'), "t1.id = t2.order_item_id", array(
                            'req_order_qty' => 'qty'
                    ))
                    ->joinLeft(array('t3' => $this->_dbprefix.'erp_pur_req_items'), "t2.req_item_id = t3.id", array(
                            'req_qty'   => 'qty'
                    ))
                    ->joinLeft(array('t4' => $this->_dbprefix.'erp_pur_req'), "t3.req_id = t4.id", array(
                            'req_number'        => 'number',
                            'create_user_id'    => 'create_user',
                            'apply_user_id'     => 'apply_user',
                            'create_time',
                            'release_time'
                    ))
                    ->joinLeft(array('t5' => $this->_dbprefix.'user'), "t4.create_user = t5.id", array())
                    ->joinLeft(array('t6' => $this->_dbprefix.'employee'), "t5.employee_id = t6.id", array(
                            'create_user_name'  => 'cname',
                            'create_user_email' => 'email'
                    ))
                    ->joinLeft(array('t7' => $this->_dbprefix.'user'), "t4.apply_user = t7.id", array())
                    ->joinLeft(array('t8' => $this->_dbprefix.'employee'), "t7.employee_id = t8.id", array(
                            'apply_user_name'   => 'cname',
                            'apply_user_email'  => 'email'
                    ))
                    ->where("t1.id = ".$id)
                    ->order("t4.create_time");
        
        $data = $this->fetchAll($sql)->toArray();
        
        return $data;
    }
    
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
                            'project_info',
                            'request_date',
                            'delivery_date', 
                            'delivery_date_remark', 
                            'delivery_date_update_time',
                            'diff' => $diffSql))
                    ->joinLeft(array('t2' => $this->_dbprefix.'erp_pur_order'), "t1.order_id = t2.id", array(
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
        
        for ($i = 0; $i < count($data); $i++){
            $data[$i]['req_info'] = $this->getReqInfoByOrderItemId($data[$i]['id']);
        }
        
        return $data;
    }
    
    public function getDeliveryNotice($time, $diff)
    {
        $notice = array();
        
        $items = $this->getDeliveryNoticeItems($time, $diff);
        
        $noticeUser = array();
        
        foreach ($items as $item){
            foreach ($item['req_info'] as $req){
                $apply_user_id = $req['apply_user_id'];
                
                if (!in_array($apply_user_id, $noticeUser)) {
                    array_push($noticeUser, $apply_user_id);
                    
                    $notice[$apply_user_id] = array();
                }
                
                $info = array(
                        'order_number'      => $item['order_number'],
                        'order_buyer'       => $item['create_user_name'],
                        'order_buyer_email' => $item['create_user_email'],
                        'item_code'         => $item['code'],
                        'item_name'         => $item['name'],
                        'item_description'  => $item['description'],
                        'item_qty'          => $item['qty'],
                        'item_project_info' => $item['project_info'],
                        'item_request_date' => $item['request_date'],
                        'item_update_time'  => $item['delivery_date_update_time'],
                        'item_delivery_date'    => $item['delivery_date'],
                        'item_delivery_remark'  => $item['delivery_date_remark'],
                        'req_order_qty'     => $req['req_order_qty'],//
                        'req_qty'           => $req['req_qty'],//
                        'req_number'        => $req['req_number'],//
                        'req_create_time'   => $req['create_time'],
                        'req_release_time'  => $req['release_time'],
                        'req_creater_id'    => $req['apply_user_id'],
                        'req_creater_name'  => $req['create_user_name'],
                        'req_creater_email' => $req['create_user_email'],
                        'req_applier_name'  => $req['apply_user_name'],
                        'req_applier_email' => $req['apply_user_email']
                );
                
                array_push($notice[$apply_user_id], $info);
            }
        }
        
        return $notice;
    }
    
    public function getOrderedReqItems($req_number)
    {
        $data = array();
        
        $res = $this->fetchAll("active = 1 and req_number like '%".$req_number."%'");
         
        if($res->count()){
            $data = $res->toArray();
        }
         
        return $data;
    }
    
    public function getOpenOrderByCode($code)
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array())
                    ->joinLeft(array('t2' => $this->_dbprefix.'erp_pur_order'), "t1.order_id = t2.id", array('number'))
                    ->where("t1.code = '".$code."' and t2.state != 2")
                    ->order("t2.number");
        $data = $this->fetchAll($sql)->toArray();
        
        return $data;
    }
    
    // 根据订单ID取消订单项
    public function cancelByOrderId($order_id)
    {
        if($order_id){
            $res = $this->fetchAll("order_id = ".$order_id);
            
            if($res->count() > 0){
                $data = $res->toArray();
                
                $itemsreq = new Erp_Model_Purchse_Orderitemsreq();
                
                foreach ($data as $item){
                    // 取消订单对应申请项
                    $itemsreq->cancelByItemId($item['id']);
                }
            }
            
            $this->update(array('active' => 0), "order_id = ".$order_id);
        }
    }
    
    public function getData($order_id)
    {
        $result = array();
        
        $data = $this->fetchAll("order_id = ".$order_id)->toArray();
        $item = new Erp_Model_Purchse_Receiveitemsorder();
        $reqItem = new Erp_Model_Purchse_Orderitemsreq();
        
        foreach ($data as $d){
            $active = $d['active'] == 1 ? true : false;
            
            $qty_receive = $item->getQty('order', $d['id']);
            
            $itemReqInfo = $reqItem->getOrderItemReqInfo($d['id']);
            
            $req_item_ids = array();
            $req_qtys = array();
            
            foreach ($itemReqInfo as $info){
                array_push($req_item_ids, $info['req_item_id']);
                array_push($req_qtys, $info['qty']);
            }
            
            $items_req_item_id = implode(',', $req_item_ids);
            $items_req_qty = implode(',', $req_qtys);
        
            array_push($result, array(
                'items_id'                  => $d['id'],
                'items_order_id'            => $d['order_id'],
                'items_req_number'          => $d['req_number'],
                'items_active'              => $active,
                'items_code'                => $d['code'],
                'items_name'                => $d['name'],
                'items_description'         => $d['description'],
                'items_supplier_code'       => $d['supplier_code'],
                'items_supplier_codename'   => $d['supplier_codename'],
                'items_supplier_description'=> $d['supplier_description'],
                'items_warehouse_code'      => $d['warehouse_code'],
                'items_qty'                 => $d['qty'],
                'items_qty_receive'          => $qty_receive,
                'items_unit'                => $d['unit'],
                'items_price'               => $d['price'],
                'items_total'               => $d['total'],
                'items_request_date'        => $d['request_date'],
                'items_dept_id'             => $d['dept_id'],
                'items_project_info'        => $d['project_info'],
                'items_req_item_id'            => $items_req_item_id,
                'items_req_qty'                => $items_req_qty,
                'items_remark'              => $d['remark']
            ));
        }
        
        return $result;
    }
    
    public function refreshOrderTotal($order_id)
    {
        $data = array(
                'total'                 => 0,// 含税金额
                'total_tax'             => 0,// 税金
                'total_no_tax'          => 0,// 不含税金额
                'forein_total'          => 0,// 外币含税金额
                'forein_total_tax'      => 0,// 外币税金
                'forein_total_no_tax'   => 0// 外币不含税金额
        );
        
        $total = 0;
        
        if($order_id){
            $dataTmp = $this->fetchAll("order_id = ".$order_id);
            
            if($dataTmp->count() > 0){
                $items = $dataTmp->toArray();
                
                foreach ($items as $item){
                    $total += $item['total'];
                }
            }
        }
        
        $order = new Erp_Model_Purchse_Order();
        $orderData = $order->getData(null, $order_id);
        
        // 当单价不含税时，需要根据实时税率计算税金及含税价
        if($orderData['price_tax'] == 0){
            $data['total_tax']= $total * $orderData['tax_rate'];// 税金
            $data['total_no_tax'] = $total;// 不含税金额
            $data['total'] = $data['total_no_tax'] + $data['total_tax'];// 含税金额
        }else{
            $data['total_no_tax'] = $total / (1 + $orderData['tax_rate']);
            $data['total_tax'] = $total - $data['total_no_tax'];
            $data['total'] = $total;
        }
        
        // 如果不是本币（汇率不为1），则需要将外币换算为本币
        if($orderData['currency_rate'] != 1){
            $data['forein_total'] = $data['total'];
            $data['forein_total_no_tax'] = $data['total_no_tax'];
            $data['forein_total_tax'] = $data['total_tax'];
            
            $data['total'] = $data['forein_total'] * $orderData['currency_rate'];
            $data['total_no_tax'] = $data['forein_total_no_tax'] * $orderData['currency_rate'];
            $data['total_tax'] = $data['forein_total_tax'] * $orderData['currency_rate'];
        }
        
        $order->update($data, "id = ".$order_id);
    }
}