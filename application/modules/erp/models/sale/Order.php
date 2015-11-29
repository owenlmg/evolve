<?php
/**
 * 2013-9-11 下午10:47:35
 * @author x.li
 * @abstract 
 */
class Erp_Model_Sale_Order extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'erp_sale_order';
    protected $_primary = 'id';
    
    public function getCustomerByNumber($number)
    {
        $customer = array();
        
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array(
                            'customer_id'))
                    ->joinLeft(array('t2' => $this->_dbprefix.'bpartner'), "t1.customer_id = t2.id", array(
                            'customer_code' => 'code', 
                            'customer_name' => new Zend_Db_Expr("case when t2.cname != '' then t2.cname else t2.ename end")))
                    ->where("t1.number = '".$number."'");
        
        if($this->fetchAll($sql)->count() > 0){
            $customer = $this->fetchRow($sql)->toArray();
        }
        
        return $customer;
    }
    
    public function getOrderQty($item_ids)
    {
        $orderData = array();
        $receive = new Erp_Model_Purchse_Receiveitemsorder();
        
        if(count($item_ids) > 0){
            foreach ($item_ids as $item_id){
                $sql = $this->select()
                            ->setIntegrityCheck(false)
                            ->from(array('t1' => $this->_name), array('number', 'create_time'))
                            ->joinLeft(array('t2' => $this->_dbprefix.'erp_sale_order_items'), "t2.order_id = t1.id", array('id'))
                            ->where("t2.id = ".$item_id);
                $data = $this->fetchAll($sql)->toArray();
                
                if(isset($data[0]['code'])){
                    array_push($orderData, array(
                        'send_data'     => $receive->getOrderItemReceivedQty($data[0]['id']),//---------------------------------
                        'number'        => $data[0]['number'],
                        'code'          => $data[0]['code'],
                        'qty'           => 0 - $data[0]['qty'],// 交货（负数）
                        'time'          => $data[0]['create_time']
                    ));
                }
            }
        }
        
        return $orderData;
    }
    
    public function getList()
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array("id", "number"))
                    ->joinLeft(array('t2' => $this->_dbprefix.'bpartner'), "t1.customer_id = t2.id", array(
                            'customer_code' => 'code', 
                            'customer_name' => new Zend_Db_Expr("case when t2.cname != '' then t2.cname else t2.ename end")))
                    ->where("t1.deleted = 0 and t1.state = 2 and t1.number != ''")
                    ->order("t1.number DESC");
        
        $data = $this->fetchAll($sql)->toArray();
        
        return $data;
    }
    
    // 获取订单及订单项详细信息
    public function getItemDetails($number, $code)
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array('order_date'))
                    ->joinLeft(array('t2' => $this->_dbprefix."erp_sale_order_items"), "t1.id = t2.order_id", array(
                            'order_item_qty' => 'qty'
                    ))
                    ->joinLeft(array('t6' => $this->_dbprefix."bpartner"), "t6.id = t1.customer_id", array(
                            'customer_code' => 'code', 
                            'customer_name' => new Zend_Db_Expr("case when t6.cname != '' then t6.cname else t6.ename end")
                    ))
                    ->where("t1.number = '".$number."' and t2.code = '".$code."'");
        
        $data = $this->fetchAll($sql)->toArray();
        
        return $data;
    }
    
    public function getInvoiceOrderItemsList($customer_id, $currency, $key = null)
    {
        $data = array();
        
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array(
                            'order_tax_id' => 'tax_id', 
                            'order_tax_name' => 'tax_name', 
                            'order_tax_rate' => 'tax_rate', 
                            'order_price_tax' => 'price_tax', 
                            'order_sales_id' => 'sales_id', 
                            'order_customer_id' => 'customer_id', 
                            'order_date', 
                            'order_number' => 'number', 
                            'order_remark' => 'remark', 
                            'order_currency' => 'currency', 
                            'order_currency_rate' => 'currency_rate', 
                            'order_type_id' => 'type_id'))
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t1.create_user = t2.id", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t2.employee_id = t3.id", array(
                            'creater' => 'cname'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t1.update_user = t4.id", array())
                    ->joinLeft(array('t5' => $this->_dbprefix.'employee'), "t4.employee_id = t5.id", array(
                            'updater' => 'cname'))
                    ->joinLeft(array('t7' => $this->_dbprefix.'erp_pur_type'), "t1.type_id = t7.id", array(
                            'order_type_name' => 'name'))
                    ->joinLeft(array('t8' => $this->_dbprefix.'erp_sale_order_items'), "t1.id = t8.order_id", array(
                            'id',
                            'order_id',
                            'request_date',
                            'code',
                            'name',
                            'description',
                            'remark',
                            'price',
                            'qty',
                            'unit'))
                    ->joinLeft(array('t9' => $this->_dbprefix.'bpartner'), "t1.customer_id = t9.id", array(
                            'order_customer_code' => 'code', 
                            'order_customer_name' => new Zend_Db_Expr("case when t9.cname != '' then t9.cname else t9.ename end")))
                    ->joinLeft(array('t10' => $this->_dbprefix.'bpartner_contact'), "t1.customer_contact_id = t10.id", array(
                            'order_customer_contact' => new Zend_Db_Expr("concat(t10.name, ' [', t10.tel, ']')")))
                    ->where("t1.customer_id = ".$customer_id." and t1.currency = '".$currency."' and t1.state = 2 and t1.active = 1");
        
        if($key){
            $sql->where("t1.number like '%".$key."%' 
                    or t1.remark like '%".$key."%' 
                    or t3.cname like '%".$key."%' 
                    or t3.ename like '%".$key."%' 
                    or t7.name like '%".$key."%' 
                    or t8.code like '%".$key."%' 
                    or t8.name like '%".$key."%' 
                    or t8.description like '%".$key."%'");
        }
        /* echo $sql;
         exit; */
        $data = $this->fetchAll($sql)->toArray();
        
        $items_invoice = new Erp_Model_Sale_Invoiceitems();
        $items_receive = new Erp_Model_Purchse_Receiveitemsorder();// 交货
        $sales = new Erp_Model_Sale_Sales();
        
        for($i = 0; $i < count($data); $i++){
            // 获取已开票数量
            $data[$i]['qty_invoice'] = $items_invoice->getQty($data[$i]['id']);
            $data[$i]['qty_left'] = $data[$i]['qty'] - $data[$i]['qty_invoice'];
            $data[$i]['qty_invoice'] = 0;
        
            $salesData = $sales->getData($data[$i]['order_sales_id']);
            $data[$i]['order_sales_name'] = isset($salesData['cname']) ? $salesData['cname'] : '';
            
            // 交货数量
            $data[$i]['qty_send'] = 0 - $items_receive->getQty('sale_order', $data[$i]['id']);// 交货（负数）
            
            $data[$i]['description'] = str_replace("'", "\'", $data[$i]['description']);
        }
        
        return $data;
    }
    
    public function getOrderItemsList($key = null, $customer_id = null)
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array(
                            'order_sales_id' => 'sales_id', 
                            'order_customer_id' => 'customer_id', 
                            'order_date', 
                            'order_number' => 'number', 
                            'order_remark' => 'remark', 
                            'order_currency' => 'currency', 
                            'order_type_id' => 'type_id'))
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t1.create_user = t2.id", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t2.employee_id = t3.id", array(
                            'creater' => 'cname'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t1.update_user = t4.id", array())
                    ->joinLeft(array('t5' => $this->_dbprefix.'employee'), "t4.employee_id = t5.id", array(
                            'updater' => 'cname'))
                    ->joinLeft(array('t7' => $this->_dbprefix.'erp_sale_type'), "t1.type_id = t7.id", array(
                            'order_type_name' => 'name'))
                    ->joinLeft(array('t8' => $this->_dbprefix.'erp_sale_order_items'), "t1.id = t8.order_id", array(
                            'id',
                            'order_id',
                            'request_date',
                            'code',
                            'code_internal',
                            'name',
                            'description',
                            'customer_code',
                            'customer_description',
                            'remark',
                            'price',
                            'qty',
                            'unit'))
                    ->joinLeft(array('t9' => $this->_dbprefix.'bpartner'), "t1.customer_id = t9.id", array(
                            'order_customer_code' => 'code', 
                            'order_customer_name' => new Zend_Db_Expr("case when t9.cname != '' then t9.cname else t9.ename end")))
                    ->joinLeft(array('t10' => $this->_dbprefix.'bpartner_contact'), "t1.customer_contact_id = t10.id", array(
                            'order_customer_contact' => new Zend_Db_Expr("concat(t10.name, ' [', t10.tel, ']')")))
                    ->where("t8.code != '' and t1.state = 2 and t1.active = 1");
        
        if($key){
            $sql->where("t1.number like '%".$key."%' 
                    or t1.remark like '%".$key."%' 
                    or t3.cname like '%".$key."%' 
                    or t3.ename like '%".$key."%' 
                    or t7.name like '%".$key."%' 
                    or t8.code like '%".$key."%' 
                    or t8.customer_code like '%".$key."%' 
                    or t8.name like '%".$key."%' 
                    or t8.description like '%".$key."%' 
                    or t8.customer_description like '%".$key."%'");
        }
        
        if ($customer_id) {
            $sql->where("t1.customer_id = ".$customer_id);
        }
        /* echo $sql;
        exit; */
        $data = $this->fetchAll($sql)->toArray();
        
        $items_send = new Erp_Model_Sale_Receiveitemsordersale();
        $sales = new Erp_Model_Sale_Sales();
        
        for($i = 0; $i < count($data); $i++){
            // 获取已下单数量
            $data[$i]['qty_send'] = $items_send->getQty('order', $data[$i]['id']);// 交货（负数）
            $data[$i]['qty_left'] = $data[$i]['qty'] - $data[$i]['qty_send'];
            $data[$i]['qty_send'] = 0;
            
            $salesData = $sales->getData($data[$i]['order_sales_id']);
            $data[$i]['order_sales_name'] = isset($salesData['cname']) ? $salesData['cname'] : '';
            
            $data[$i]['description'] = str_replace("'", "\'", $data[$i]['description']);
        }
        
        return $data;
    }
    
    public function getOrderStatistics($condition = array())
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array(
                            'order_state' => new Zend_Db_Expr("case when t1.state = 0 then '审核中' when t1.state = 1 then '拒绝' else '批准' end"), 
                            'order_create_time' => 'create_time', 
                            'order_release_time' => 'release_time', 
                            'order_sales_id' => 'sales_id', 
                            'order_customer_id' => 'customer_id', 
                            'order_date', 
                            'order_status', 
                            'order_id' => 'id', 
                            'order_number' => 'number', 
                            'order_remark' => 'remark', 
                            'order_currency' => 'currency', 
                            'order_type_id' => 'type_id'))
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t1.create_user = t2.id", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t2.employee_id = t3.id", array(
                            'creater' => 'cname'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t1.update_user = t4.id", array())
                    ->joinLeft(array('t5' => $this->_dbprefix.'employee'), "t4.employee_id = t5.id", array(
                            'updater' => 'cname'))
                    ->joinLeft(array('t7' => $this->_dbprefix.'erp_sale_type'), "t1.type_id = t7.id", array(
                            'order_type_name' => 'name'))
                    ->joinLeft(array('t8' => $this->_dbprefix.'erp_sale_order_items'), "t1.id = t8.order_id", array(
                            'delivery_date',
                            'delivery_date_remark',
                            'active',
                            'id',
                            'type',
                            'order_id',
                            'request_date',
                            'code',
                            'code_internal',
                            'name',
                            'description',
                            'customer_code',
                            'customer_description',
                            'product_type',
                            'product_series',
                            'remark',
                            'price',
                            'total',
                            'total',
                            'price_tax',
                            'qty',
                            'product_type',
                            'product_series',
                            'sales_remark',
                            'unit'))
                    ->joinLeft(array('t9' => $this->_dbprefix.'bpartner'), "t1.customer_id = t9.id", array(
                            'order_customer_code' => 'code', 
                            'order_customer_name' => new Zend_Db_Expr("case when t9.cname != '' then t9.cname else t9.ename end")))
                    ->joinLeft(array('t10' => $this->_dbprefix.'bpartner_contact'), "t1.customer_address_code = t10.area_code", array(
                            'order_customer_contact' => new Zend_Db_Expr("concat(t10.area_code, ' [', t10.name, ']')")))
                    ->where("t1.active = 1")
                    ->group("t8.id")
                    ->order(array('t1.number desc', 't1.create_time desc'));
        
        // 状态
        if($condition['state'] != null){
            $sql->where("t1.state = ".$condition['state']);
        }
        // 日前从
        if($condition['date_from']){
            $sql->where("t1.create_time >= '".$condition['date_from']." 00:00:00'");
        }
        // 日期至
        if($condition['date_to']){
            $sql->where("t1.create_time <= '".$condition['date_to']." 23:59:59'");
        }
        // 采购类别
        if ($condition['type']){
            $type = json_decode($condition['type']);
        
            if(count($type)){
                $type_con = "t1.type_id = ".$type[0];
        
                for($i = 1; $i < count($type); $i++){
                    $type_con .= " or t1.type_id = ".$type[$i];
                }
        
                $sql->where($type_con);
            }
        }
        
        if ($condition['sales']){
            $sales = json_decode($condition['sales']);
        
            if(count($sales)){
                $sales_con = "t1.sales_id = ".$sales[0];
        
                for($i = 1; $i < count($sales); $i++){
                    $sales_con .= " or t1.sales_id = ".$sales[$i];
                }
        
                $sql->where($sales_con);
            }
        }
        
        if($condition['key']){
            $sql->where("t9.code like '%".$condition['key']."%' 
                    or t9.cname like '%".$condition['key']."%' 
                    or t9.ename like '%".$condition['key']."%' 
                    or t3.cname like '%".$condition['key']."%' 
                    or t3.ename like '%".$condition['key']."%' 
                    or t1.number like '%".$condition['key']."%' 
                    or t1.remark like '%".$condition['key']."%' 
                    or t3.cname like '%".$condition['key']."%' 
                    or t3.ename like '%".$condition['key']."%' 
                    or t7.name like '%".$condition['key']."%' 
                    or t8.code like '%".$condition['key']."%' 
                    or t8.name like '%".$condition['key']."%' 
                    or t8.description like '%".$condition['key']."%'");
        }
        
        $total = $this->fetchAll($sql)->count();
        /* echo $sql;
        exit; */
        if($condition['option'] != 'csv'){
            $sql->limitPage($condition['page'], $condition['limit']);
        }
        
        $data = $this->fetchAll($sql)->toArray();
        //echo '<pre>';print_r($data);exit;
        $item = new Erp_Model_Sale_Receiveitemsordersale();// 交货
        $items_invoice = new Erp_Model_Sale_Invoiceitems();
        $sales = new Erp_Model_Sale_Sales();
        
        $operateModel = new Application_Model_Log_Operate();
        
        for($i = 0; $i < count($data); $i++){
            $data[$i]['delivery_date_first'] = '';
            
            // 交期回复日志
            if ($data[$i]['delivery_date'] != '' || $data[$i]['delivery_date_remark'] != '') {
                $logInfo = array();
                
                $log = $operateModel->getLogByOperateAndTargetId('销售交期回复', $data[$i]['id']);
                
                $j = 0;
                foreach ($log as $l){
                    $content = Zend_Json::decode($l['content']);
                    
                    $logText = $content['time'].' ['.$content['delivery_date'].'] ['.$content['delivery_date_remark'].'] '.$content['user'];
                    
                    array_push($logInfo, $logText);
                    
                    if($j == count($log) - 1){
                        $data[$i]['delivery_date_first'] = $content['delivery_date'];
                    }
                    
                    $j++;
                }
                
                $data[$i]['delivery_reply_log'] = implode(',', $logInfo);
            }
            
        	$data[$i]['order_customer'] = $data[$i]['order_customer_code'].$data[$i]['order_customer_name'];
        	
            $data[$i]['qty_send'] = 0;
            $data[$i]['send_info'] = '';
            if($data[$i]['id']){
                if($data[$i]['code'] != ''){
                    // 已交货：从销售交货
                    $receiveData = $item->getOrderItemSendQty($data[$i]['id']);
                    $sendInfoArr = array();
                    
                    foreach ($receiveData as $r){
                        $data[$i]['qty_send'] += $r['qty'];// 交货（负数）
                        array_push($sendInfoArr, $r['number'].' ['.$r['qty'].'] ['.$r['time'].']');
                    }
                    
                    $data[$i]['send_info'] = implode(',', $sendInfoArr);
                }else{
                    // 已交货非物料：从销售发票
                    $data[$i]['qty_send'] = $items_invoice->getQty($data[$i]['id'], 1);
                }
            }
            
            $data[$i]['qty_left'] = $data[$i]['qty'] - $data[$i]['qty_send'];
            
            $salesData = $sales->getData($data[$i]['order_sales_id']);
            $data[$i]['order_sales_name'] = isset($salesData['cname']) ? $salesData['cname'] : '';
        }
        //echo '<pre>';print_r($data);exit;
        if($condition['option'] == 'csv'){
            $data_csv = array();
            $showPrice = false;
            if(Application_Model_User::checkPermissionByRoleName('系统管理员') || Application_Model_User::checkPermissionByRoleName('销售人员') || Application_Model_User::checkPermissionByRoleName('财务人员')){
                $showPrice = true;
            }
            
            if ($showPrice) {
                $title = array(
                        'cnt'                   => '#',
                        'active'                => '启用',
                        'order_number'          => '订单号',
                        'order_state'           => '审核状态',
                        'order_status'          => '订单状态',
                        'price'                 => '价格',
                        'total'                 => '金额',
                        'type'                  => '类别',
                        'code'                  => '产品型号',
                        'code_internal'         => '内部型号',
                        'order_sales_name'      => '销售员',
                        'order_type_name'       => '订单类别',
                        'order_customer_code'   => '客户代码',
                        'order_customer_name'   => '客户名称',
                        'name'                  => '名称',
                        'description'           => '描述',
                        'customer_code'         => '客户产品型号',
                        'customer_description'  => '客户产品描述',
                        'product_type'          => '产品类别',
                        'product_series'        => '产品系列',
                        'qty'                   => '订单数量',
                        'qty_send'              => '交货数量',
                        'send_info'             => '交货信息',
                        'qty_left'              => '未交货数量',
                        'request_date'          => '需求交期',
                        'delivery_date_first'   => '预计交期(首次)',
                        'delivery_date'         => '预计交期(最终)',
                        'delivery_date_remark'  => '交期备注',
                        'order_customer_contact'=> '客户收件人地址简码',
                        'remark'                => '备注',
                        'order_create_date'     => '下单日期',
                        'order_create_time'     => '下单时间',
                        'order_release_date'    => '批准日期',
                        'order_release_time'    => '批准时间'
                );
            }else{
                $title = array(
                        'cnt'                   => '#',
                        'active'                => '启用',
                        'order_number'          => '订单号',
                        'order_state'           => '审核状态',
                        'order_status'          => '订单状态',
                        'type'                  => '类别',
                        'code'                  => '产品型号',
                        'code_internal'         => '内部型号',
                        'order_sales_name'      => '销售员',
                        'order_type_name'       => '订单类别',
                        'order_customer_code'   => '客户代码',
                        'order_customer_name'   => '客户名称',
                        'name'                  => '名称',
                        'description'           => '描述',
                        'customer_code'         => '客户产品型号',
                        'customer_description'  => '客户产品描述',
                        'product_type'          => '产品类别',
                        'product_series'        => '产品系列',
                        'qty'                   => '订单数量',
                        'qty_send'              => '交货数量',
                        'send_info'             => '交货信息',
                        'qty_left'              => '未交货数量',
                        'request_date'          => '需求交期',
                        'delivery_date_first'   => '预计交期(首次)',
                        'delivery_date'         => '预计交期(最终)',
                        'delivery_date_remark'  => '交期备注',
                        'order_customer_contact'=> '客户收件人地址简码',
                        'remark'                => '备注',
                        'order_create_date'     => '下单日期',
                        'order_create_time'     => '下单时间',
                        'order_release_date'    => '批准日期',
                        'order_release_time'    => '批准时间'
                );
            }
            
            
            array_push($data_csv, $title);
        
            $i = 0;
        
            foreach ($data as $d){
                $i++;
                
                if ($showPrice) {
                    $info = array(
                            'cnt'                   => $i,
                            'active'                => $d['active'] == 1 ? '是' : '否',
                            'order_number'          => $d['order_number'],
                            'order_state'           => $d['order_state'],
                            'order_status'          => $d['order_status'],
                            'price'                 => $d['price'],
                            'total'                 => $d['total'],
                            'type'                  => $d['type'] == 'catalog' ? '产品' : '物料',
                            'code'                  => $d['code'],
                            'code_internal'         => $d['code_internal'],
                            'order_sales_name'      => $d['order_sales_name'],
                            'order_type_name'       => $d['order_type_name'],
                            'order_customer_code'   => $d['order_customer_code'],
                            'order_customer_name'   => $d['order_customer_name'],
                            'name'                  => $d['name'],
                            'description'           => $d['description'],
                            'customer_code'         => $d['customer_code'],
                            'customer_description'  => $d['customer_description'],
                            'product_type'          => $d['product_type'],
                            'product_series'        => $d['product_series'],
                            'qty'                   => $d['qty'],
                            'qty_send'              => $d['qty_send'],
                            'send_info'             => $d['send_info'],
                            'qty_left'              => $d['qty_left'],
                            'request_date'          => $d['request_date'],
                            'delivery_date_first'   => $d['delivery_date_first'],
                            'delivery_date'         => $d['delivery_date'],
                            'delivery_date_remark'  => $d['delivery_date_remark'],
                            'order_customer_contact'=> $d['order_customer_contact'],
                            'remark'                => $d['remark'],
                            'order_create_date'     => $d['order_create_time'] != '' ? date('Y-m-d', strtotime($d['order_create_time'])) : '',
                            'order_create_time'     => $d['order_create_time'] != '' ? date('H:i:s', strtotime($d['order_create_time'])) : '',
                            'order_release_date'    => $d['order_release_time'] != '' ? date('Y-m-d', strtotime($d['order_release_time'])) : '',
                            'order_release_time'    => $d['order_release_time'] != '' ? date('H:i:s', strtotime($d['order_release_time'])) : ''
                    );
                }else{
                    $info = array(
                            'cnt'                   => $i,
                            'active'                => $d['active'] == 1 ? '是' : '否',
                            'order_number'          => $d['order_number'],
                            'order_state'           => $d['order_state'],
                            'order_status'          => $d['order_status'],
                            'type'                  => $d['type'] == 'catalog' ? '产品' : '物料',
                            'code'                  => $d['code'],
                            'code_internal'         => $d['code_internal'],
                            'order_sales_name'      => $d['order_sales_name'],
                            'order_type_name'       => $d['order_type_name'],
                            'order_customer_code'   => $d['order_customer_code'],
                            'order_customer_name'   => $d['order_customer_name'],
                            'name'                  => $d['name'],
                            'description'           => $d['description'],
                            'customer_code'         => $d['customer_code'],
                            'customer_description'  => $d['customer_description'],
                            'product_type'          => $d['product_type'],
                            'product_series'        => $d['product_series'],
                            'qty'                   => $d['qty'],
                            'qty_send'              => $d['qty_send'],
                            'send_info'             => $d['send_info'],
                            'qty_left'              => $d['qty_left'],
                            'request_date'          => $d['request_date'],
                            'delivery_date_first'   => $d['delivery_date_first'],
                            'delivery_date'         => $d['delivery_date'],
                            'delivery_date_remark'  => $d['delivery_date_remark'],
                            'order_customer_contact'=> $d['order_customer_contact'],
                            'remark'                => $d['remark'],
                            'order_create_date'     => $d['order_create_time'] != '' ? date('Y-m-d', strtotime($d['order_create_time'])) : '',
                            'order_create_time'     => $d['order_create_time'] != '' ? date('H:i:s', strtotime($d['order_create_time'])) : '',
                            'order_release_date'    => $d['order_release_time'] != '' ? date('Y-m-d', strtotime($d['order_release_time'])) : '',
                            'order_release_time'    => $d['order_release_time'] != '' ? date('H:i:s', strtotime($d['order_release_time'])) : ''
                    );
                }
                
                array_push($data_csv, $info);
            }
        
            return $data_csv;
        }
        
        return array('total' => $total, 'rows' => $data);
    }
    
    // 取消订单
    public function cancelOrderById($id)
    {
        if($id){
            $now = date('Y-m-d H:i:s');
            $user_session = new Zend_Session_Namespace('user');
            $user_id = $user_session->user_info['user_id'];
            
            $orderData = $this->getData(null, $id);
            $review_info = $orderData['review_info'].'<br>'.$now.': '.$user_session->user_info['user_name'].' [取消]';
            
            $data = array(
                    'active'        => 0,
                    'review_info'   => $review_info,
                    'update_user'   => $user_id,
                    'update_time'   => $now
            );
            // 取消订单
            $this->update($data, "id = ".$id);
            // 取消订单项
            $items = new Erp_Model_Sale_Orderitems();
            $items->cancelByOrderId($id);
        }
    }
    
    public function getNewNum($type_id, $company)
    {
        $type = new Erp_Model_Sale_Type();
        
        $pre = $type->getPrefix($type_id);
        
        $num_pre = $pre.date('y');
        
        $data = $this->fetchAll("number like '".$num_pre."%'", array('number desc'));
        
        if($data->count() == 0){
            $num = '0001';
        }else{
            $last_item = $data->getRow(0)->toArray();
            
            $new_order = intval(substr($last_item['number'], strlen($pre) + 4)) + 1;
            
            $num = sprintf ("%04d", $new_order);
        }
        
        return $num_pre.$num;
    }
    
    public function getData($condition = array(), $order_id = null)
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t1.create_user = t2.id", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t2.employee_id = t3.id", array(
                            'creater' => 'cname'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t1.update_user = t4.id", array())
                    ->joinLeft(array('t5' => $this->_dbprefix.'employee'), "t4.employee_id = t5.id", array(
                            'updater' => 'cname'))
                    ->joinLeft(array('t7' => $this->_dbprefix.'erp_sale_type'), "t1.type_id = t7.id", array(
                            'type' => 'name', 
                            'order_flow_id' => 'flow_id'))
                    ->joinLeft(array('t9' => $this->_dbprefix.'bpartner'), "t1.customer_id = t9.id", array(
                            'customer_code' => 'code', 
                            'customer_ename' => new Zend_Db_Expr("case when t9.ename = '' then t9.cname else t9.ename end"), 
                            'customer_cname' => 'cname', 
                            'customer_bank_type' => 'bank_type', 
                            'customer_bank_account' => 'bank_account', 
                            'customer_tax_id' => 'tax_id', 
                            'customer_tax_num' => 'tax_num', 
                            'customer_bank_payment_days' => 'bank_payment_days'))
                    ->joinLeft(array('t10' => $this->_dbprefix.'bpartner_payment'), "t10.id = t9.bank_payment_days", array(
                            'customer_payment' => 't10.name'))
                    ->joinLeft(array('t11' => $this->_dbprefix.'erp_pur_transfer'), "t11.id = t1.transfer_id", array(
                            'transfer_type', 
                            'transfer_description', 
                            'transfer_state' => 'state'))
                    ->where("t1.deleted = 0");
                    /* ->joinLeft(array('t8' => $this->_dbprefix.'erp_pur_order_items'), "t1.id = t8.order_id", array()) */;
        
        if($order_id){
            $sql->where("t1.id = ".$order_id);
            
            return $this->fetchRow($sql)->toArray();
        }else{
            $sql->where("t1.active = ".$condition['active']." and t1.state = ".$condition['state']);
            
            if($condition['date_from']){
                $sql->where("t1.create_time >= '".$condition['date_from']." 00:00:00'");
            }
            
            if($condition['date_to']){
                $sql->where("t1.create_time <= '".$condition['date_to']." 23:59:59'");
            }
            
            if ($condition['type']){
                $type = json_decode($condition['type']);
            
                if(count($type)){
                    $type_con = "t1.type_id = ".$type[0];
            
                    for($i = 1; $i < count($type); $i++){
                        $type_con .= " or t1.type_id = ".$type[$i];
                    }
            
                    $sql->where($type_con);
                }
            }
            
            if ($condition['key']){
                $sql->where("t9.code like '%".$condition['key']."%' 
                        or t9.cname like '%".$condition['key']."%' 
                        or t9.ename like '%".$condition['key']."%' 
                        or t1.number like '%".$condition['key']."%' 
                        or t1.description like '%".$condition['key']."%' 
                        or t1.remark like '%".$condition['key']."%'");// or t8.name like '%".$condition['key']."%'
            }
            
            $total = $this->fetchAll($sql)->count();
            
            $sql->order(array('t1.state', 't1.number desc', 't1.create_time desc'))
                ->limitPage($condition['page'], $condition['limit']);
            
            $data = $this->fetchAll($sql)->toArray();
            
            $review = new Dcc_Model_Review();
            $help = new Application_Model_Helpers();
            $user_session = new Zend_Session_Namespace('user');
            $employee_id = $user_session->user_info['employee_id'];
            
            for($i = 0; $i < count($data); $i++){
                $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
                $data[$i]['update_time'] = strtotime($data[$i]['update_time']);
                $data[$i]['release_time'] = strtotime($data[$i]['release_time']);
                $data[$i]['state'] = intval($data[$i]['state']);
                $data[$i]['review_state'] = "";
                $data[$i]['review_info_tip'] = $data[$i]['review_info'];
                $data[$i]['review_info'] = str_replace('<br>', ' > ', $data[$i]['review_info']);
                $data[$i]['reviewer'] = '';
                
                // 当状态不为拒绝时才能获取，否则会报错
                if($data[$i]['state'] != 1){
                    // 获取审核情况
                    $review_state = $help->getReviewState('sale_order_add', $data[$i]['id']);
                    
                    $data[$i]['reviewer'] = implode(',', $help->getEmployeeNameByIdArr($review_state['reviewer']));
                    $data[$i]['review_state'] = $review_state['info'];
                    $data[$i]['can_review'] = in_array($employee_id, $review_state['reviewer']) && !in_array($employee_id, $review_state['reviewer_finished']) ? 1 : 0;
                    $data[$i]['current_step'] = $review_state['step_chk']['current_step'];
                    $data[$i]['last_step'] = $review_state['step_chk']['last_step'];
                    $data[$i]['to_finish'] = $review_state['step_chk']['to_finish'];
                    $data[$i]['next_step'] = $review_state['step_chk']['next_step'];
                }
                
                if($help->chkIsReviewer('sale_order_add', $data[$i]['id'], $employee_id)){
                    $data[$i]['is_reviewer'] = 1;
                }else{
                    $data[$i]['is_reviewer'] = 0;
                }
            }
            
            return array('total' => $total, 'rows' => $data);
        }
    }
}