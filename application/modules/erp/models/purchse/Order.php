<?php
/**
 * 2013-9-11 下午10:47:35
 * @author x.li
 * @abstract 
 */
class Erp_Model_Purchse_Order extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'erp_pur_order';
    protected $_primary = 'id';
    
    public function getSupplierByNumber($number)
    {
        $supplier = array();
        
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array('supplier_id'))
                    ->joinLeft(array('t2' => $this->_dbprefix.'bpartner'), "t1.supplier_id = t2.id", array('supplier_code' => 'code', 'supplier_name' => new Zend_Db_Expr("case when t2.cname != '' then t2.cname else t2.ename end")))
                    ->where("t1.number = '".$number."'");
        
        if($this->fetchAll($sql)->count() > 0){
            $supplier = $this->fetchRow($sql)->toArray();
        }
        
        return $supplier;
    }
    
    public function getOrderQty($item_ids, $req_item_id)
    {
        $orderData = array();
        $receive = new Erp_Model_Purchse_Receiveitemsorder();
        
        if(count($item_ids) > 0){
            foreach ($item_ids as $item_id){
                $sql = $this->select()
                            ->setIntegrityCheck(false)
                            ->from(array('t1' => $this->_name), array('number', 'create_time'))
                            ->joinLeft(array('t2' => $this->_dbprefix.'erp_pur_order_items'), "t2.order_id = t1.id", array('id'))
                            ->joinLeft(array('t3' => $this->_dbprefix.'erp_pur_order_items_req'), "t3.order_item_id = t2.id and t3.req_item_id = ".$req_item_id, array('code', 'qty'))
                            ->where("t2.id = ".$item_id);
                $data = $this->fetchAll($sql)->toArray();
                
                if(isset($data[0]['code'])){
                    array_push($orderData, array(
                        'receive_data' => $receive->getOrderItemReceivedQty($data[0]['id']),
                        'number'=> $data[0]['number'],
                        'code'  => $data[0]['code'],
                        'qty'   => $data[0]['qty'],
                        'time'  => $data[0]['create_time']
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
                    ->joinLeft(array('t2' => $this->_dbprefix.'bpartner'), "t1.supplier_id = t2.id", array('supplier_code' => 'code', 'supplier_name' => new Zend_Db_Expr("case when t2.cname != '' then t2.cname else t2.ename end")))
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
                    ->joinLeft(array('t2' => $this->_dbprefix."erp_pur_order_items"), "t1.id = t2.order_id", array(
                            'order_item_qty' => 'qty'
                    ))
                    ->joinLeft(array('t3' => $this->_dbprefix."erp_pur_order_items_req"), "t3.order_item_id = t2.id", array(
                            'order_req_item_qty' => 'qty'
                    ))
                    ->joinLeft(array('t4' => $this->_dbprefix."erp_pur_req_items"), "t3.req_item_id = t4.id", array(
                            'req_item_qty' => 'qty', 
                            'req_item_date' => 'date_req', 
                            'customer_address', 
                            'customer_aggrement'
                    ))
                    ->joinLeft(array('t5' => $this->_dbprefix."erp_pur_req"), "t4.req_id = t5.id", array(
                            'req_number' => 'number', 
                            'req_create_time' => 'create_time'
                    ))
                    ->joinLeft(array('t6' => $this->_dbprefix."bpartner"), "t6.id = t1.supplier_id", array(
                            'supplier_code' => 'code', 
                            'supplier_name' => new Zend_Db_Expr("case when t6.cname != '' then t6.cname else t6.ename end")
                    ))
                    ->where("t1.number = '".$number."' and t2.code = '".$code."'");
        
        $data = $this->fetchAll($sql)->toArray();
        
        return $data;
    }
    
    public function getInvoiceOrderItemsList($supplier_id, $currency, $key = null)
    {
        $data = array();
        
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array('order_tax_id' => 'tax_id', 'order_tax_name' => 'tax_name', 'order_tax_rate' => 'tax_rate', 'order_price_tax' => 'price_tax', 'order_buyer_id' => 'buyer_id', 'order_supplier_id' => 'supplier_id', 'order_date', 'order_number' => 'number', 'order_remark' => 'remark', 'order_currency' => 'currency', 'order_currency_rate' => 'currency_rate', 'order_type_id' => 'type_id'))
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t1.create_user = t2.id", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t2.employee_id = t3.id", array('creater' => 'cname'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t1.update_user = t4.id", array())
                    ->joinLeft(array('t5' => $this->_dbprefix.'employee'), "t4.employee_id = t5.id", array('updater' => 'cname'))
                    ->joinLeft(array('t7' => $this->_dbprefix.'erp_pur_type'), "t1.type_id = t7.id", array('order_type_name' => 'name'))
                    ->joinLeft(array('t8' => $this->_dbprefix.'erp_pur_order_items'), "t1.id = t8.order_id", array('id', 'order_id', 'request_date','code', 'name', 'description', 'remark', 'supplier_code', 'supplier_codename', 'supplier_description', 'warehouse_code', 'price', 'qty', 'unit', 'project_info'))
                    ->joinLeft(array('t9' => $this->_dbprefix.'bpartner'), "t1.supplier_id = t9.id", array('order_supplier_code' => 'code', 'order_supplier_name' => new Zend_Db_Expr("case when t9.cname != '' then t9.cname else t9.ename end")))
                    ->joinLeft(array('t10' => $this->_dbprefix.'bpartner_contact'), "t1.supplier_contact_id = t10.id", array('order_supplier_contact' => new Zend_Db_Expr("concat(t10.name, ' [', t10.tel, ']')")))
                    ->where("t1.supplier_id = ".$supplier_id." and t1.currency = '".$currency."' and t1.state = 2 and t1.active = 1");
        
        if($key){
            $sql->where("t1.number like '%".$key."%' or t1.remark like '%".$key."%' or t3.cname like '%".$key."%' or t3.ename like '%".$key."%' or t7.name like '%".$key."%' or t8.code like '%".$key."%' or t8.name like '%".$key."%' or t8.description like '%".$key."%'");
        }
        /* echo $sql;
         exit; */
        $data = $this->fetchAll($sql)->toArray();
        
        $items_invoice = new Erp_Model_Purchse_Invoiceitems();
        $items_receive = new Erp_Model_Purchse_Receiveitemsorder();
        $buyer = new Erp_Model_Purchse_Buyer();
        
        for($i = 0; $i < count($data); $i++){
            // 获取已开票数量
            $data[$i]['qty_invoice'] = $items_invoice->getQty($data[$i]['id']);
            $data[$i]['qty_left'] = $data[$i]['qty'] - $data[$i]['qty_invoice'];
            $data[$i]['qty_invoice'] = 0;
        
            $buyerData = $buyer->getData($data[$i]['order_buyer_id']);
            $data[$i]['order_buyer_name'] = isset($buyerData['cname']) ? $buyerData['cname'] : '';
            
            // 收货数量
            $data[$i]['qty_receive'] = $items_receive->getQty('order', $data[$i]['id']);
            
            $data[$i]['description'] = str_replace("'", "\'", $data[$i]['description']);
        }
        
        return $data;
    }
    
    public function getOrderItemsList($key = null)
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array('order_buyer_id' => 'buyer_id', 'order_supplier_id' => 'supplier_id', 'order_date', 'order_number' => 'number', 'order_remark' => 'remark', 'order_currency' => 'currency', 'order_type_id' => 'type_id'))
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t1.create_user = t2.id", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t2.employee_id = t3.id", array('creater' => 'cname'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t1.update_user = t4.id", array())
                    ->joinLeft(array('t5' => $this->_dbprefix.'employee'), "t4.employee_id = t5.id", array('updater' => 'cname'))
                    ->joinLeft(array('t7' => $this->_dbprefix.'erp_pur_type'), "t1.type_id = t7.id", array('order_type_name' => 'name'))
                    ->joinLeft(array('t8' => $this->_dbprefix.'erp_pur_order_items'), "t1.id = t8.order_id", array('id', 'order_id', 'request_date','code', 'name', 'description', 'remark', 'supplier_code', 'supplier_codename', 'supplier_description', 'warehouse_code', 'price', 'qty', 'unit', 'project_info'))
                    ->joinLeft(array('t9' => $this->_dbprefix.'bpartner'), "t1.supplier_id = t9.id", array('order_supplier_code' => 'code', 'order_supplier_name' => new Zend_Db_Expr("case when t9.cname != '' then t9.cname else t9.ename end")))
                    ->joinLeft(array('t10' => $this->_dbprefix.'bpartner_contact'), "t1.supplier_contact_id = t10.id", array('order_supplier_contact' => new Zend_Db_Expr("concat(t10.name, ' [', t10.tel, ']')")))
                    ->where("t1.receive_flag = 0 and t8.code != '' and t1.state = 2 and t1.active = 1");
        
        if($key){
            $sql->where("t1.number like '%".$key."%' or t1.remark like '%".$key."%' or t3.cname like '%".$key."%' or t3.ename like '%".$key."%' or t7.name like '%".$key."%' or t8.code like '%".$key."%' or t8.name like '%".$key."%' or t8.description like '%".$key."%'");
        }
        /* echo $sql;
        exit; */
        $data = $this->fetchAll($sql)->toArray();
        
        $items_receive = new Erp_Model_Purchse_Receiveitemsorder();
        $buyer = new Erp_Model_Purchse_Buyer();
        
        for($i = 0; $i < count($data); $i++){
            // 获取已下单数量
            $data[$i]['qty_receive'] = $items_receive->getQty('order', $data[$i]['id']);
            $data[$i]['qty_left'] = $data[$i]['qty'] - $data[$i]['qty_receive'];
            $data[$i]['qty_receive'] = 0;
            
            $buyerData = $buyer->getData($data[$i]['order_buyer_id']);
            $data[$i]['order_buyer_name'] = isset($buyerData['cname']) ? $buyerData['cname'] : '';
            
            $data[$i]['description'] = str_replace("'", "\'", $data[$i]['description']);
        }
        
        return $data;
    }
    
    public function getOrderStatistics($condition = array())
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array('order_price_tax' => 'price_tax', 'order_state' => new Zend_Db_Expr("case when t1.state = 0 then '审核中' when t1.state = 1 then '拒绝' else '批准' end"), 'order_create_time' => 'create_time', 'order_release_time' => 'release_time', 'order_buyer_id' => 'buyer_id', 'order_supplier_id' => 'supplier_id', 'order_date', 'order_number' => 'number', 'order_remark' => 'remark', 'order_currency' => 'currency', 'order_type_id' => 'type_id'))
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t1.create_user = t2.id", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t2.employee_id = t3.id", array('creater' => 'cname'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t1.update_user = t4.id", array())
                    ->joinLeft(array('t5' => $this->_dbprefix.'employee'), "t4.employee_id = t5.id", array('updater' => 'cname'))
                    ->joinLeft(array('t7' => $this->_dbprefix.'erp_pur_type'), "t1.type_id = t7.id", array('order_type_name' => 'name'))
                    ->joinLeft(array('t8' => $this->_dbprefix.'erp_pur_order_items'), "t1.id = t8.order_id", array('delivery_date', 'delivery_date_remark', 'active', 'id', 'order_id', 'request_date','code', 'name', 'description', 'remark', 'supplier_code', 'supplier_codename', 'supplier_description', 'warehouse_code', 'price', 'qty', 'unit', 'project_info', 'req_number'))
                    ->joinLeft(array('t9' => $this->_dbprefix.'bpartner'), "t1.supplier_id = t9.id", array('order_supplier_code' => 'code', 'order_supplier_name' => new Zend_Db_Expr("case when t9.cname != '' then t9.cname else t9.ename end")))
                    ->joinLeft(array('t10' => $this->_dbprefix.'bpartner_contact'), "t1.supplier_contact_id = t10.id", array('order_supplier_contact' => new Zend_Db_Expr("concat(t10.name, ' [', t10.tel, ']')")))
                    ->joinLeft(array('t11' => $this->_dbprefix.'erp_pur_order_items_req'), "t11.order_item_id = t8.id", array('req_item_ids' => new Zend_Db_Expr("group_concat(t11.req_item_id)")))
                    ->joinLeft(array('t12' => $this->_dbprefix.'erp_pur_req_items'), "t12.id = t11.req_item_id", array('qty_req' => new Zend_Db_Expr('sum(t12.qty)'), 'order_req_num', 'customer_address', 'customer_aggrement'))
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
        // 需求部门
        if ($condition['dept']){
            $dept = json_decode($condition['dept']);
        
            if(count($dept)){
                $dept_con = "t8.dept_id = ".$dept[0];
        
                for($i = 1; $i < count($dept); $i++){
                    $dept_con .= " or t8.dept_id = ".$dept[$i];
                }
        
                $sql->where($dept_con);
            }
        }
        
        // 采购员
        if ($condition['buyer']){
            $buyer = json_decode($condition['buyer']);
        
            if(count($buyer)){
                $buyer_con = "t1.buyer_id = ".$buyer[0];
        
                for($i = 1; $i < count($buyer); $i++){
                    $buyer_con .= " or t1.buyer_id = ".$buyer[$i];
                }
        
                $sql->where($buyer_con);
            }
        }
        
        if($condition['key']){
            $sql->where("t9.code like '%".$condition['key']."%' or t9.cname like '%".$condition['key']."%' or t9.ename like '%".$condition['key']."%' or t3.cname like '%".$condition['key']."%' or t3.ename like '%".$condition['key']."%' or t1.number like '%".$condition['key']."%' or t1.remark like '%".$condition['key']."%' or t3.cname like '%".$condition['key']."%' or t3.ename like '%".$condition['key']."%' or t7.name like '%".$condition['key']."%' or t8.code like '%".$condition['key']."%' or t8.name like '%".$condition['key']."%' or t8.description like '%".$condition['key']."%'");
        }
        
        $total = $this->fetchAll($sql)->count();
        /* echo $sql;
        exit; */
        if($condition['option'] != 'csv'){
            $sql->limitPage($condition['page'], $condition['limit']);
        }
        
        $data = $this->fetchAll($sql)->toArray();
        //echo '<pre>';print_r($data);exit;
        $items_receive = new Erp_Model_Purchse_Receiveitemsorder();
        $items_invoice = new Erp_Model_Purchse_Invoiceitems();
        $buyer = new Erp_Model_Purchse_Buyer();
        $req = new Erp_Model_Purchse_Req();
        
        $operateModel = new Application_Model_Log_Operate();
        $receiveModel = new Erp_Model_Purchse_Reqitemsreceived();
        
        for($i = 0; $i < count($data); $i++){
            // 入库日志
            $in_stock_info = $receiveModel->getReceivedInfo('order', $data[$i]['id']);
            $data[$i]['in_stock_qty'] = $in_stock_info['qty'];
            $data[$i]['in_stock_info'] = implode(',', $in_stock_info['info']);
            
            // 交期回复日志
            if ($data[$i]['delivery_date'] != '' || $data[$i]['delivery_date_remark'] != '') {
                $logInfo = array();
                
                $log = $operateModel->getLogByOperateAndTargetId('采购交期回复', $data[$i]['id']);
                
                foreach ($log as $l){
                    $content = Zend_Json::decode($l['content']);
                    
                    $logText = $content['time'].' ['.$content['delivery_date'].'] ['.$content['delivery_date_remark'].'] '.$content['user'];
                    
                    array_push($logInfo, $logText);
                }
                
                $data[$i]['delivery_reply_log'] = implode(',', $logInfo);
            }
            
            // 获取采购申请数量（合并下单的申请分拆显示）
            $data[$i]['req_info'] = '';
            
            if($data[$i]['req_item_ids'] != ''){
                $item_ids = explode(',', $data[$i]['req_item_ids']);
                
                $req_item_data = $req->getReqQty($item_ids);
                
                $reqInfoArr = array();
                
                foreach ($req_item_data as $req_info){
                    array_push($reqInfoArr, $req_info['number'].' ['.$req_info['qty'].'] ['.$req_info['time'].']');
                }
                
                $data[$i]['req_info'] = implode(',', $reqInfoArr);
            }
            
            $data[$i]['order_supplier'] = $data[$i]['order_supplier_code'].$data[$i]['order_supplier_name'];
            
            $data[$i]['qty_receive'] = 0;
            $data[$i]['receive_info'] = '';
            if($data[$i]['id']){
                if($data[$i]['code'] != ''){
                    // 已收货物料：从采购收货
                    $receiveData = $items_receive->getOrderItemReceivedQty($data[$i]['id']);
                    $receiveInfoArr = array();
                    
                    foreach ($receiveData as $r){
                        $data[$i]['qty_receive'] += $r['qty'];
                        array_push($receiveInfoArr, $r['number'].' ['.$r['qty'].'] ['.$r['time'].']');
                    }
                    
                    $data[$i]['receive_info'] = implode(',', $receiveInfoArr);
                }else{
                    // 已收货非物料：从采购发票
                    $data[$i]['qty_receive'] = $items_invoice->getQty($data[$i]['id'], 1);
                }
            }
            
            $data[$i]['qty_left'] = $data[$i]['qty'] - $data[$i]['qty_receive'];
            
            $buyerData = $buyer->getData($data[$i]['order_buyer_id']);
            $data[$i]['order_buyer_name'] = isset($buyerData['cname']) ? $buyerData['cname'] : '';
        }
        
        if($condition['option'] == 'csv'){
            $data_csv = array();
            $showPrice = false;
            if(Application_Model_User::checkPermissionByRoleName('系统管理员') || Application_Model_User::checkPermissionByRoleName('采购人员') || Application_Model_User::checkPermissionByRoleName('财务人员')){
                $showPrice = true;
            }
        
            if($showPrice){
                $title = array(
                        'cnt'                   => '#',
                        'active'                => '启用',
                        'order_number'          => '订单号',
                        'order_state'           => '订单状态',
                        'order_date'            => '订单日期',
                        'order_buyer_name'      => '采购员',
                        'order_type_name'       => '类别',
                        'order_supplier_code'   => '供应商代码',
                        'order_supplier_name'   => '供应商名称',
                        'code'                  => '物料号',
                        'price'                 => '价格',
                        'order_price_tax'       => '含税',
                        'qty'                   => '订单数量',
                        'qty_req'               => '申请数量',
                        'qty_receive'           => '到货数量',
                        'receive_info'          => '到货信息',
                        'in_stock_qty'          => '入库数量',
                        'in_stock_info'         => '入库信息',
                        'qty_left'              => '未到货数量',
                        'request_date'          => '需求交期',
                        'delivery_date'         => '预计交期',
                        'delivery_date_remark'  => '交期备注',
                        'order_req_num'         => '订货产品出库申请号',
                        'customer_address'      => '客户收件人地址简码',
                        'customer_aggrement'    => '客户合同号',
                        'name'                  => '名称',
                        'description'           => '描述',
                        'req_info'              => '申购单号',
                        //'dept_id'               => '需求部门',
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
                        'order_state'           => '订单状态',
                        'order_date'            => '订单日期',
                        'order_buyer_name'      => '采购员',
                        'order_type_name'       => '类别',
                        'order_supplier_code'   => '供应商代码',
                        'order_supplier_name'   => '供应商名称',
                        'code'                  => '物料号',
                        'qty'                   => '订单数量',
                        'qty_req'               => '申请数量',
                        'qty_receive'           => '到货数量',
                        'receive_info'          => '到货信息',
                        'in_stock_qty'          => '入库数量',
                        'in_stock_info'         => '入库信息',
                        'qty_left'              => '未到货数量',
                        'request_date'          => '需求交期',
                        'delivery_date'         => '预计交期',
                        'delivery_date_remark'  => '交期备注',
                        'order_req_num'         => '订货产品出库申请号',
                        'customer_address'      => '客户收件人地址简码',
                        'customer_aggrement'    => '客户合同号',
                        'name'                  => '名称',
                        'description'           => '描述',
                        'req_info'              => '申购单号',
                        //'dept_id'               => '需求部门',
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
                
                if($showPrice){
                    $info = array(
                            'cnt'                   => $i,
                            'active'                => $d['active'] == 1 ? '是' : '否',
                            'order_number'          => $d['order_number'],
                            'order_state'           => $d['order_state'],
                            'order_date'            => $d['order_date'],
                            'order_buyer_name'      => $d['order_buyer_name'],
                            'order_type_name'       => $d['order_type_name'],
                            'order_supplier_code'   => $d['order_supplier_code'],
                            'order_supplier_name'   => $d['order_supplier_name'],
                            'code'                  => $d['code'],
                            'price'                 => $d['price'],
                            'order_price_tax'       => $d['order_price_tax'] == 1 ? '是' : '否',
                            'qty'                   => $d['qty'],
                            'qty_req'               => $d['qty_req'],
                            'qty_receive'           => $d['qty_receive'],
                            'receive_info'          => $d['receive_info'],
                            'in_stock_qty'          => $d['in_stock_qty'],
                            'in_stock_info'          => $d['in_stock_info'],
                            'qty_left'              => $d['qty_left'],
                            'request_date'          => $d['request_date'],
                            'delivery_date'         => $d['delivery_date'],
                            'delivery_date_remark'  => $d['delivery_date_remark'],
                            'order_req_num'         => $d['order_req_num'],
                            'customer_address'      => $d['customer_address'],
                            'customer_aggrement'    => $d['customer_aggrement'],
                            'name'                  => $d['name'],
                            'description'           => $d['description'],
                            'req_info'              => $d['req_info'],
                            //'dept_id'               => $d['dept_id'],
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
                            'order_date'            => $d['order_date'],
                            'order_buyer_name'      => $d['order_buyer_name'],
                            'order_type_name'       => $d['order_type_name'],
                            'order_supplier_code'   => $d['order_supplier_code'],
                            'order_supplier_name'   => $d['order_supplier_name'],
                            'code'                  => $d['code'],
                            'qty'                   => $d['qty'],
                            'qty_req'               => $d['qty_req'],
                            'qty_receive'           => $d['qty_receive'],
                            'receive_info'          => $d['receive_info'],
                            'in_stock_qty'          => $d['in_stock_qty'],
                            'in_stock_qty'          => $d['in_stock_info'],
                            'qty_left'              => $d['qty_left'],
                            'request_date'          => $d['request_date'],
                            'delivery_date'         => $d['delivery_date'],
                            'delivery_date_remark'  => $d['delivery_date_remark'],
                            'order_req_num'         => $d['order_req_num'],
                            'customer_address'      => $d['customer_address'],
                            'customer_aggrement'    => $d['customer_aggrement'],
                            'name'                  => $d['name'],
                            'description'           => $d['description'],
                            'req_info'              => $d['req_info'],
                            //'dept_id'               => $d['dept_id'],
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
            $items = new Erp_Model_Purchse_Orderitems();
            $items->cancelByOrderId($id);
        }
    }
    
    /**
     * 获取最新申购单编号
     * @param unknown $type_id
     * @return string
     */
    public function getNewNum($type_id, $company)
    {
        $type = new Erp_Model_Purchse_Type();
        
        $pre = $company == 0 ? 'OPP' : 'OSP';
        
        $num_pre = $pre.date('ymd');
        
        $data = $this->fetchAll("number like '".$num_pre."%'", array('number desc'));
        
        if($data->count() == 0){
            $num = '01';
        }else{
            $last_item = $data->getRow(0)->toArray();
            
            $new_order = intval(substr($last_item['number'], strlen($pre) + 6)) + 1;
            
            $num = sprintf ("%02d", $new_order);
        }
        
        return $num_pre.$num;
    }
    
    public function getData($condition = array(), $order_id = null)
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t1.create_user = t2.id", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t2.employee_id = t3.id", array('creater' => 'cname'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t1.update_user = t4.id", array())
                    ->joinLeft(array('t5' => $this->_dbprefix.'employee'), "t4.employee_id = t5.id", array('updater' => 'cname'))
                    ->joinLeft(array('t7' => $this->_dbprefix.'erp_pur_type'), "t1.type_id = t7.id", array('type' => 'name', 'order_flow_id'))
                    ->joinLeft(array('t9' => $this->_dbprefix.'bpartner'), "t1.supplier_id = t9.id", array('supplier_code' => 'code', 'supplier_ename' => new Zend_Db_Expr("case when t9.ename = '' then t9.cname else t9.ename end"), 'supplier_cname' => 'cname', 'supplier_bank_type' => 'bank_type', 'supplier_bank_account' => 'bank_account', 'supplier_tax_id' => 'tax_id', 'supplier_tax_num' => 'tax_num', 'supplier_bank_payment_days' => 'bank_payment_days'))
                    ->joinLeft(array('t10' => $this->_dbprefix.'bpartner_payment'), "t10.id = t9.bank_payment_days", array('supplier_payment' => 't10.name'))
                    ->joinLeft(array('t11' => $this->_dbprefix.'erp_pur_transfer'), "t11.id = t1.transfer_id", array('transfer_type', 'transfer_description', 'transfer_state' => 'state'))
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
                $sql->where("t9.code like '%".$condition['key']."%' or t9.cname like '%".$condition['key']."%' or t9.ename like '%".$condition['key']."%' or t1.number like '%".$condition['key']."%' or t1.description like '%".$condition['key']."%' or t1.remark like '%".$condition['key']."%'");// or t8.name like '%".$condition['key']."%'
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
                    $review_state = $help->getReviewState('purchse_order_add', $data[$i]['id']);
                    
                    $data[$i]['reviewer'] = implode(',', $help->getEmployeeNameByIdArr($review_state['reviewer']));
                    $data[$i]['review_state'] = $review_state['info'];
                    $data[$i]['can_review'] = in_array($employee_id, $review_state['reviewer']) && !in_array($employee_id, $review_state['reviewer_finished']) ? 1 : 0;
                    $data[$i]['current_step'] = $review_state['step_chk']['current_step'];
                    $data[$i]['last_step'] = $review_state['step_chk']['last_step'];
                    $data[$i]['to_finish'] = $review_state['step_chk']['to_finish'];
                    $data[$i]['next_step'] = $review_state['step_chk']['next_step'];
                }
                
                if($help->chkIsReviewer('purchse_order_add', $data[$i]['id'], $employee_id)){
                    $data[$i]['is_reviewer'] = 1;
                }else{
                    $data[$i]['is_reviewer'] = 0;
                }
            }
            
            return array('total' => $total, 'rows' => $data);
        }
    }
}