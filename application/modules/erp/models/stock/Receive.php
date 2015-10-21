<?php
/**
 * 2013-7-6 下午10:32:26
 * @author x.li
 * @abstract 
 */
class Erp_Model_Stock_Receive extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'erp_stock_receive';
    protected $_primary = 'id';
    
    /*
     * 库存重估
     */
    public function correctInStockQty($receive_id = null, $date_from = null, $date_to = null)
    {
        $orderItemReqModel = new Erp_Model_Purchse_Orderitemsreq();
        $reqReceiveModel = new Erp_Model_Purchse_Reqitemsreceived();
        
        $data = $this->getInStockItemsInfo($receive_id, $date_from, $date_to);
        
        foreach ($data as $d){
            if ($d['order_item_id'] != '') {
                $req_info = $orderItemReqModel->getOrderItemReqInfo($d['order_item_id']);
                
                $receive_qty = $d['qty'];
                
                // 冲抵申请项数量（按申请时间顺序）
                foreach ($req_info as $info){
                    $req_receive_qty = 0;
                
                    if ($receive_qty > 0) {
                        if ($info['qty'] >= $receive_qty) {
                            // 当前收货可只够冲抵当前申请项数量
                            $req_receive_qty = $receive_qty;
                
                            $receive_qty = 0;
                        }else{
                            // 当前收货冲抵当前申请项后还有剩余可用于冲抵其它关联申请
                            $req_receive_qty = $info['qty'];
                
                            $receive_qty = $receive_qty - $info['qty'];
                        }
                    }
                
                    // 当前申请项有收货数量冲抵
                    if ($req_receive_qty > 0) {
                        if ($reqReceiveModel->fetchAll("req_item_id = ".$info['req_item_id']." and receive_item_id = ".$d['receive_item_id'])->count() == 0) {
                            $req_receive_data = array(
                                    'order_item_id'     => $d['order_item_id'],
                                    'req_item_id'       => $info['req_item_id'],
                                    'receive_item_id'   => $d['receive_item_id'],
                                    'order_number'      => $d['order_number'],
                                    'req_number'        => $info['req_number'],
                                    'receive_number'    => $d['number'],
                                    'order_time'        => $d['order_time'],
                                    'req_time'          => $info['req_time'],
                                    'receive_time'      => $d['create_time'],
                                    'qty'               => $req_receive_qty
                            );
                
                            $reqReceiveModel->insert($req_receive_data);
                        }
                    }
                }
            }
        }
    }
    
    public function getInStockItemsInfo($receive_id = null, $date_from = null, $date_to = null)
    {
        $data = array();
        
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array(
                            'number', 
                            'order_id', 
                            'create_time', 
                            'create_user'
                    ))
                    ->joinLeft(array('t2' => $this->_dbprefix.'erp_stock_receive_items'), 't1.id = t2.receive_id', array(
                            'receive_item_id'   => 'id',
                            'receive_id', 
                            'code', 
                            'qty'
                    ))
                    ->joinLeft(array('t3' => $this->_dbprefix.'erp_pur_order'), 't1.order_id = t3.id', array(
                            'order_number'  => 'number',
                            'order_time'    => 'create_time'
                    ))
                    ->joinLeft(array('t4' => $this->_dbprefix.'erp_pur_order_items'), 't4.order_id = t3.id and t4.code = t2.code', array(
                            'order_item_id'     => 'id',
                            'order_item_qty'    => 'qty'
                    ))
                    ->where("t1.order_id != ''")
                    ->order(array("t1.order_id", "t2.code"));
        
        if ($receive_id) {
            $sql->where("t1.id = ".$receive_id);
        }
        
        if ($date_from) {
            $sql->where("t1.create_time >= '".$date_from." 00:00:00'");
        }
        
        if ($date_to) {
            $sql->where("t1.create_time <= '".$date_to." 23:59:59'");
        }
        
        $data = $this->fetchAll($sql)->toArray();
        
        return $data;
    }
    
    public function getNewNum($pre = 'POI')
    {
        $num_pre = $pre.date('ymd');// 采购收货单前缀
    
        $data = $this->fetchAll("number like '".$num_pre."%'", array('number desc'));
        
        if($data->count() == 0){
            $num = $num_pre.'01';
        }else{
            $last_item = $data->getRow(0)->toArray();
            
            $new_order = intval(substr($last_item['number'], strlen($pre) + 6)) + 1;
    
            $num = $num_pre.sprintf ("%02d", $new_order);
        }
    
        return $num;
    }

    // 获取数据
    public function getData($condition, $receive_id = null, $type = '采购收货')
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t1.create_user = t2.id", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t2.employee_id = t3.id", array('creater' => 'cname'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t1.update_user = t4.id", array())
                    ->joinLeft(array('t5' => $this->_dbprefix.'employee'), "t4.employee_id = t5.id", array('updater' => 'cname'))
                    ->where("t1.type = '".$type."'");
        
        if($receive_id){
            $sql->where("t1.id = ".$receive_id);
            
            return $this->fetchRow($sql)->toArray();
        }else{
            if($condition['date_from']){
                $sql->where("t1.create_time >= '".$condition['date_from']." 00:00:00'");
            }
        
            if($condition['date_to']){
                $sql->where("t1.create_time <= '".$condition['date_to']." 23:59:59'");
            }
        
            if ($condition['key']){
                $sql->where("t1.number like '%".$condition['key']."%' 
                        or t1.description like '%".$condition['key']."%' 
                        or t1.remark like '%".$condition['key']."%'");// or t8.name like '%".$condition['key']."%'
            }
            
            $total = $this->fetchAll($sql)->count();
            
            $sql->order(array('t1.number desc', 't1.create_time desc'));
            
            if($condition['type'] != 'csv'){
                $sql->limitPage($condition['page'], $condition['limit']);
            }
            
            $data = $this->fetchAll($sql)->toArray();
            $receive_items = new Erp_Model_Purchse_Receiveitems();
            
            for($i = 0; $i < count($data); $i++){
                $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
                $data[$i]['update_time'] = strtotime($data[$i]['update_time']);
                
                $orderInfo = $receive_items->getOrderNumbersByReceiveId($data[$i]['id'], $type);
                $data[$i]['order_number'] = implode(',', $orderInfo['order_number']);
                
                if ($type == '采购收货') {
                    $suppliers = array();
                    
                    foreach ($orderInfo['supplier'] as $s){
                        if (!in_array($s, $suppliers)) {
                            array_push($suppliers, $s);
                        }
                    }
                    
                    $data[$i]['supplier'] = implode(',', $suppliers);
                }else{
                    $customers = array();
                    
                    foreach ($orderInfo['customer'] as $c){
                        if (!in_array($c, $customers)) {
                            array_push($customers, $c);
                        }
                    }
                    
                    $data[$i]['customer'] = implode(',', $customers);
                }
            }
            
            if($condition['type'] == 'csv'){
                $data_csv = array();
                
                $showAmount = false;
                
                if(Application_Model_User::checkPermissionByRoleName('系统管理员')
                || Application_Model_User::checkPermissionByRoleName('财务人员')
                || (($type = '采购收货' || $type = '采购退货' || $type = '调拨') && Application_Model_User::checkPermissionByRoleName('采购人员'))
                || ($type = '销售交货' && Application_Model_User::checkPermissionByRoleName('销售人员'))){
                    $showAmount = true;
                }
                
                if($showAmount){
                    if(isset($condition['action_type']) && $condition['action_type'] == '采购收货'){
                        $title = array(
                                'cnt'               => '#',
                                'number'            => '收货单号',
                                'order_number'      => '采购订单',
                                'total'             => '金额',
                                'supplier'          => '供应商',
                                'date'              => '收货日期',
                                'description'       => '描述',
                                'remark'            => '备注',
                                'creater'           => '收货人',
                                'create_time'       => '收货时间'
                        );
                    }else if(isset($condition['action_type']) && $condition['action_type'] == '销售交货'){
                        $title = array(
                                'cnt'               => '#',
                                'number'            => '交货单号',
                                'order_number'      => '销售订单',
                                'total'             => '金额',
                                'customer'          => '客户',
                                'date'              => '交货日期',
                                'description'       => '描述',
                                'remark'            => '备注',
                                'creater'           => '交货人',
                                'create_time'       => '交货时间'
                        );
                    }else{
                        $title = array(
                                'cnt'               => '#',
                                'transaction_type'  => '库存交易类别',
                                'number'            => '单据号',
                                'date'              => '单据日期',
                                'total'             => '金额',
                                'description'       => '描述',
                                'remark'            => '备注',
                                'creater'           => '制单人',
                                'create_time'       => '制单时间'
                        );
                    }
                }else{
                    if(isset($condition['action_type']) && $condition['action_type'] == '采购收货'){
                        $title = array(
                                'cnt'               => '#',
                                'number'            => '收货单号',
                                'order_number'      => '采购订单',
                                'supplier'          => '供应商',
                                'date'              => '收货日期',
                                'description'       => '描述',
                                'remark'            => '备注',
                                'creater'           => '收货人',
                                'create_time'       => '收货时间'
                        );
                    }else if(isset($condition['action_type']) && $condition['action_type'] == '销售交货'){
                        $title = array(
                                'cnt'               => '#',
                                'number'            => '交货单号',
                                'order_number'      => '销售订单',
                                'customer'          => '客户',
                                'date'              => '交货日期',
                                'description'       => '描述',
                                'remark'            => '备注',
                                'creater'           => '交货人',
                                'create_time'       => '交货时间'
                        );
                    }else{
                        $title = array(
                                'cnt'               => '#',
                                'transaction_type'  => '库存交易类别',
                                'number'            => '单据号',
                                'date'              => '单据日期',
                                'description'       => '描述',
                                'remark'            => '备注',
                                'creater'           => '制单人',
                                'create_time'       => '制单时间'
                        );
                    }
                }
            
                array_push($data_csv, $title);
            
                $i = 0;
            
                foreach ($data as $d){
                    $i++;
                    
                    if($showAmount){
                        if(isset($condition['action_type']) && $condition['action_type'] == '采购收货'){
                            $info = array(
                                    'cnt'           => $i,
                                    'number'        => $d['number'],
                                    'order_number'  => $d['order_number'],
                                    'total'         => $d['total'],
                                    'supplier'      => $d['supplier'],
                                    'date'          => $d['date'],
                                    'description'   => $d['description'],
                                    'remark'        => $d['remark'],
                                    'creater'       => $d['creater'],
                                    'create_time'   => date('Y-m-d H:i:s', $d['create_time'])
                            );
                        }else if(isset($condition['action_type']) && $condition['action_type'] == '销售交货'){
                            $info = array(
                                    'cnt'           => $i,
                                    'number'        => $d['number'],
                                    'order_number'  => $d['order_number'],
                                    'total'         => $d['total'],
                                    'customer'      => $d['customer'],
                                    'date'          => $d['date'],
                                    'description'   => $d['description'],
                                    'remark'        => $d['remark'],
                                    'creater'       => $d['creater'],
                                    'create_time'   => date('Y-m-d H:i:s', $d['create_time'])
                            );
                        }else{
                            $info = array(
                                    'cnt'               => $i,
                                    'transaction_type'  => $d['transaction_type'],
                                    'number'            => $d['number'],
                                    'date'              => $d['date'],
                                    'total'             => $d['total'],
                                    'description'       => $d['description'],
                                    'remark'            => $d['remark'],
                                    'creater'           => $d['creater'],
                                    'create_time'       => date('Y-m-d H:i:s', $d['create_time'])
                            );
                        }
                    }else{
                        if(isset($condition['action_type']) && $condition['action_type'] == '采购收货'){
                            $info = array(
                                    'cnt'           => $i,
                                    'number'        => $d['number'],
                                    'order_number'  => $d['order_number'],
                                    'supplier'      => $d['supplier'],
                                    'date'          => $d['date'],
                                    'description'   => $d['description'],
                                    'remark'        => $d['remark'],
                                    'creater'       => $d['creater'],
                                    'create_time'   => date('Y-m-d H:i:s', $d['create_time'])
                            );
                        }else if(isset($condition['action_type']) && $condition['action_type'] == '销售交货'){
                            $info = array(
                                    'cnt'           => $i,
                                    'number'        => $d['number'],
                                    'order_number'  => $d['order_number'],
                                    'customer'      => $d['customer'],
                                    'date'          => $d['date'],
                                    'description'   => $d['description'],
                                    'remark'        => $d['remark'],
                                    'creater'       => $d['creater'],
                                    'create_time'   => date('Y-m-d H:i:s', $d['create_time'])
                            );
                        }else{
                            $info = array(
                                    'cnt'               => $i,
                                    'transaction_type'  => $d['transaction_type'],
                                    'number'            => $d['number'],
                                    'date'              => $d['date'],
                                    'description'       => $d['description'],
                                    'remark'            => $d['remark'],
                                    'creater'           => $d['creater'],
                                    'create_time'       => date('Y-m-d H:i:s', $d['create_time'])
                            );
                        }
                    }
            
                    array_push($data_csv, $info);
                }
            
                return $data_csv;
            }
        
            return array('total' => $total, 'rows' => $data);
        }
    }
}