<?php
/**
 * 2013-9-11 下午10:47:35
 * @author x.li
 * @abstract 
 */
class Erp_Model_Purchse_Req extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'erp_pur_req';
    protected $_primary = 'id';
    
    public function getReqQty($item_ids)
    {
        $reqData = array();
        
        if(count($item_ids) > 0){
            foreach ($item_ids as $item_id){
                $sql = $this->select()
                            ->setIntegrityCheck(false)
                            ->from(array('t1' => $this->_name), array('number', 'create_time'))
                            ->joinLeft(array('t2' => $this->_dbprefix.'erp_pur_req_items'), "t2.req_id = t1.id", array('code', 'qty'))
                            ->where("t2.id = ".$item_id);
                $data = $this->fetchAll($sql)->toArray();
                
                if(isset($data[0]['code'])){
                    array_push($reqData, array(
                        'number'=> $data[0]['number'],
                        'code'  => $data[0]['code'],
                        'qty'   => $data[0]['qty'],
                        'time'  => $data[0]['create_time']
                    ));
                }
            }
        }
        
        return $reqData;
    }
    
    // 获取订单项关联人员
    public function getRelatedUsers($numberArr)
    {
        $userInfo = array();
        
        $review = new Dcc_Model_Review();
        
        $userIdsAdded = array();
        
        foreach ($numberArr as $number){
            // 获取申请人
            $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array('id', 'create_user', 'apply_user'))
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t1.create_user = t2.id", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t2.employee_id = t3.id", array('creater_email' => 'email'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t1.apply_user = t4.id", array())
                    ->joinLeft(array('t5' => $this->_dbprefix.'employee'), "t4.employee_id = t5.id", array('applier_email' => 'email'))
                    ->where("t1.number = '".$number."'");
            //echo $sql.'<br><br>';
            $data = $this->fetchRow($sql)->toArray();
            
            array_push($userInfo, array('user_id' => $data['create_user'], 'email' => $data['creater_email']));
            array_push($userIdsAdded, $data['create_user']);
            
            if($data['apply_user'] && !in_array($data['apply_user'], $userIdsAdded)){
                array_push($userInfo, array('user_id' => $data['apply_user'], 'email' => $data['applier_email']));
            }
            
            // 获取审核人
            $reviewerInfo = $review->getReviewUserInfo('purchse_req_add', $data['id']);
            
            foreach ($reviewerInfo as $info){
                if(!in_array($info['user_id'], $userIdsAdded)){
                    array_push($userInfo, $info);
                }
            }
        }
        
        return $userInfo;
    }
    
    public function getReqItemsList($key = null, $option = 'data')
    {
        $dataResult = array();
        
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array('req_number' => 'number', 'req_dept_id' => 'dept_id', 'req_release_time' => 'release_time', 'req_remark' => 'remark', 'req_reason' => 'reason', 'req_type_id' => 'type_id'))
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t1.create_user = t2.id", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t2.employee_id = t3.id", array('creater' => 'cname'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t1.update_user = t4.id", array())
                    ->joinLeft(array('t5' => $this->_dbprefix.'employee'), "t4.employee_id = t5.id", array('updater' => 'cname'))
                    ->joinLeft(array('t6' => $this->_dbprefix.'employee_dept'), "t1.dept_id = t6.id", array('req_dept' => 'name'))
                    ->joinLeft(array('t7' => $this->_dbprefix.'erp_pur_type'), "t1.type_id = t7.id", array('req_type' => 'name'))
                    ->joinLeft(array('t8' => $this->_dbprefix.'erp_pur_req_items'), "t1.id = t8.req_id")
                    ->where("t1.state = 2 and t1.order_flag = 0 and t1.active = 1 and t8.active = 1");
        
        if($key){
            $sql->where("t8.code like '%".$key."%' or t1.number like '%".$key."%' or t1.remark like '%".$key."%' or t1.reason like '%".$key."%' or t3.cname like '%".$key."%' or t3.ename like '%".$key."%' or t7.name like '%".$key."%' or t8.name like '%".$key."%' or t8.description like '%".$key."%'");
        }
        
        $data = $this->fetchAll($sql)->toArray();
        
        $items_req = new Erp_Model_Purchse_Orderitemsreq();
        
        for($i = 0; $i < count($data); $i++){
            $data[$i]['qty_order'] = 0;
            if($data[$i]['id']){
                $data[$i]['qty_order'] = $items_req->getQty('req', $data[$i]['id'], false);
            }
            
            $data[$i]['qty_left'] = $data[$i]['qty'] - $data[$i]['qty_order'];
            $data[$i]['qty_order'] = 0;
            
            $data[$i]['description'] = str_replace("'", "\'", $data[$i]['description']);
            
            if($data[$i]['qty_left'] > 0){
                array_push($dataResult, $data[$i]);
            }
        }
        
        if($option == 'csv'){
            $data_csv = array();
        
            $title = array(
                    'cnt'                   => '#',
                    'req_number'            => '申请单号',
                    'req_dept'              => '申请部门',
                    'req_type'              => '采购类别',
                    'creater'               => '申请人',
                    'code'                  => '物料号',
                    'name'                  => '名称',
                    'qty'                   => '申请数量',
                    'qty_left'              => '剩余数量',
                    'unit'                  => '单位',
                    'description'           => '描述',
                    'customer_address'      => '客户收件人地址简码',
                    'remark'                => '备注',
                    'project_info'          => '项目信息',
                    'req_reason'            => '申请事由',
                    'req_remark'            => '申请备注'
            );
        
            array_push($data_csv, $title);
        
            $i = 0;
        
            foreach ($data as $d){
                $i++;
        
                $info = array(
                        'cnt'                   => $i,
                        'req_number'            => $d['req_number'],
                        'req_dept'              => $d['req_dept'],
                        'req_type'              => $d['req_type'],
                        'creater'               => $d['creater'],
                        'code'                  => $d['code'],
                        'name'                  => $d['name'],
                        'qty'                   => $d['qty'],
                        'qty_left'              => $d['qty_left'],
                        'unit'                  => $d['unit'],
                        'description'           => $d['description'],
                        'customer_address'      => $d['customer_address'],
                        'remark'                => $d['remark'],
                        'project_info'          => $d['project_info'],
                        'req_reason'            => $d['req_reason'],
                        'req_remark'            => $d['req_remark']
                );
        
                array_push($data_csv, $info);
            }
        
            return $data_csv;
        }
        
        return $dataResult;
    }
    
    public function getReqStatistics($condition = array())
    {
        $data = array();
        
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array(
                            'req_active' => 'active', 
                            'req_state' => new Zend_Db_Expr("case when t1.state = 0 then '审核中' when t1.state = 1 then '拒绝' else '批准' end"), 
                            'req_number' => 'number', 
                            'req_dept_id' => 'dept_id', 
                            'req_release_time' => 'release_time', 
                            'req_create_time' => 'create_time', 
                            'req_remark' => 'remark', 
                            'req_reason' => 'reason', 
                            'req_type_id' => 'type_id'))
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t1.create_user = t2.id", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t2.employee_id = t3.id", array('req_creater' => 'cname'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t1.update_user = t4.id", array())
                    ->joinLeft(array('t5' => $this->_dbprefix.'employee'), "t4.employee_id = t5.id", array('req_updater' => 'cname'))
                    ->joinLeft(array('t6' => $this->_dbprefix.'employee_dept'), "t1.dept_id = t6.id", array('req_dept' => 'name'))
                    ->joinLeft(array('t7' => $this->_dbprefix.'erp_pur_type'), "t1.type_id = t7.id", array('req_type' => 'name'))
                    ->joinLeft(array('t8' => $this->_dbprefix.'erp_pur_req_items'), "t1.id = t8.req_id")
                    ->joinLeft(array('t9' => $this->_dbprefix.'employee_dept'), "t8.dept_id = t9.id", array('dept' => 'name'))
                    ->joinLeft(array('t10' => $this->_dbprefix.'erp_pur_order_items_req'), "t10.req_item_id = t8.id and t10.active = 1", array(
                            'req_item_id', 
                            'order_item_ids' => new Zend_Db_Expr("group_concat(t10.order_item_id)"), 
                            'qty_order' => new Zend_Db_Expr("sum(t10.qty)")))
                    ->joinLeft(array('t11' => $this->_dbprefix.'erp_pur_order_items'), "t10.order_item_id = t11.id", array(
                            'order_item_id' => 'id', 
                            'delivery_date', 
                            'delivery_date_remark'
                    ))
                    ->joinLeft(array('t14' => $this->_dbprefix.'erp_pur_order'), "t11.order_id = t14.id", array(
                            'order_number' => new Zend_Db_Expr("group_concat(t14.number)")))
                    ->joinLeft(array('t12' => $this->_dbprefix.'user'), "t1.apply_user = t12.id", array())
                    ->joinLeft(array('t13' => $this->_dbprefix.'employee'), "t12.employee_id = t13.id", array(
                            'req_applier' => 'cname'))
                    ->group("t8.id")
                    ->order(array('t1.number desc', 't1.create_time desc'));
        
        // 状态
        if($condition['state'] != null){
            if($condition['state'] == 3){
                $sql->where("t1.active = 0");
            }else{
                $sql->where("t1.state = ".$condition['state']);
            }
        }
        // 申请人
        if($condition['applier']){
            $sql->where("t3.cname like '%".$condition['applier']."%' or t3.ename like '%".$condition['applier']."%'");
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
                $dept_con = "t1.dept_id = ".$dept[0];
        
                for($i = 1; $i < count($dept); $i++){
                    $dept_con .= " or t1.dept_id = ".$dept[$i];
                }
        
                $sql->where($dept_con);
            }
        }
        
        if($condition['key']){
            $sql->where("t1.number like '%".$condition['key']."%' or t1.remark like '%".$condition['key']."%' or t1.reason like '%".$condition['key']."%' or t3.cname like '%".$condition['key']."%' or t3.ename like '%".$condition['key']."%' or t7.name like '%".$condition['key']."%' or t8.code like '%".$condition['key']."%' or t8.name like '%".$condition['key']."%' or t8.description like '%".$condition['key']."%'");
        }
        
        $total = $this->fetchAll($sql)->count();
        
        if($condition['option'] != 'csv'){
            $sql->limitPage($condition['page'], $condition['limit']);
        }
        
        $data = $this->fetchAll($sql)->toArray();
        
        $items_req = new Erp_Model_Purchse_Orderitemsreq();
        $order = new Erp_Model_Purchse_Order();
        $operateModel = new Application_Model_Log_Operate();
        $receiveModel = new Erp_Model_Purchse_Reqitemsreceived();
        
        for($i = 0; $i < count($data); $i++){
            $data[$i]['delivery_reply_log'] = '';
            
            // 入库日志
            $data[$i]['in_stock_qty'] = 0;
            $data[$i]['in_stock_info'] = '';
            
            if($data[$i]['req_item_id']){
                $in_stock_info = $receiveModel->getReceivedInfo('req', $data[$i]['req_item_id']);
                $data[$i]['in_stock_qty'] = $in_stock_info['qty'];
                $data[$i]['in_stock_info'] = implode(',', $in_stock_info['info']);
            }
            
            // 交期回复日志
            if ($data[$i]['delivery_date'] != '' || $data[$i]['delivery_date_remark'] != '') {
                $logInfo = array();
            
                $log = $operateModel->getLogByOperateAndTargetId('采购交期回复', $data[$i]['order_item_id']);
            
                foreach ($log as $l){
                    $content = Zend_Json::decode($l['content']);
            
                    $logText = $content['time'].' ['.$content['delivery_date'].'] ['.$content['delivery_date_remark'].'] '.$content['user'];
            
                    array_push($logInfo, $logText);
                }
            
                $data[$i]['delivery_reply_log'] = implode(',', $logInfo);
            }
            
            // 获取采购订单数量（合并下单的申请分拆显示）
            $data[$i]['req_info'] = '';
            $data[$i]['receive_info'] = '';
            $data[$i]['receive_qty'] = 0;
            $data[$i]['order_info'] = '';
            
            if($data[$i]['order_item_ids'] != ''){
                $item_ids = explode(',', $data[$i]['order_item_ids']);
            
                $req_item_data = $order->getOrderQty($item_ids, $data[$i]['req_item_id']);
            
                $orderInfoArr = array();
                $receiveInfoArr = array();
                //echo '<pre>';print_r($req_item_data);exit;
                foreach ($req_item_data as $req_info){
                    foreach ($req_info['receive_data'] as $r){
                        array_push($receiveInfoArr, $r['number'].' ['.$r['qty'].'] ['.$r['time'].']');
                        $data[$i]['receive_qty'] += $r['qty'];
                    }
                    
                    array_push($orderInfoArr, $req_info['number'].' ['.$req_info['qty'].'] ['.$req_info['time'].']');
                }
            
                $data[$i]['order_info'] = implode(',', $orderInfoArr);
                $data[$i]['receive_info'] = implode(',', $receiveInfoArr);
            }
            
            $data[$i]['qty_left'] = $data[$i]['qty'] - $data[$i]['qty_order'];
            
            if($data[$i]['req_active'] == 0){
                $data[$i]['req_state'] = '取消';
            }
        }
        //echo '<pre>';print_r($data);exit;
        if($condition['option'] == 'csv'){
            $data_csv = array();
        
            $title = array(
                    'cnt'                   => '#',
                    'active'                => '启用',
                    'req_number'            => '申请单号',
                    'req_type'              => '类别',
                    'req_dept'              => '申请部门',
                    'dept'                  => '需求部门',
                    'code'                  => '物料号',
                    'qty'                   => '申请数量',
                    'qty_order'             => '下单数量',
                    'order_info'            => '采购订单',
                    'receive_qty'           => '到货数量',
                    'receive_info'          => '到货信息',
                    'in_stock_qty'          => '入库数量',
                    'in_stock_info'         => '入库信息',
                    'qty_left'              => '未下单数量',
                    'date_req'              => '需求日期',
                    'delivery_date'         => '预计交期',
                    'delivery_date_remark'  => '交期备注',
                    'delivery_reply_log'    => '交期回复日志',
                    'name'                  => '名称',
                    'description'           => '描述',
                    'model'                 => '型号',
                    'order_req_num'         => '订货产品出库申请号',
                    'customer_address'      => '客户收件人地址简码',
                    'customer_aggrement'    => '客户合同号',
                    'remark'                => '备注',
                    'req_creater'           => '制单人',
                    'req_applier'           => '申请人',
                    'req_reason'            => '申请事由',
                    'project_info'          => '项目信息',
                    'req_remark'            => '申请备注',
                    'req_create_date'       => '申请日期',
                    'req_create_time'       => '申请时间',
                    'req_release_date'      => '批准日期',
                    'req_release_time'      => '批准时间'
            );
        
            array_push($data_csv, $title);
        
            $i = 0;
        
            foreach ($data as $d){
                $i++;
        
                $info = array(
                        'cnt'                   => $i,
                        'active'                => $d['active'] == 1 ? '是' : '否',
                        'req_number'            => $d['req_number'],
                        'req_type'              => $d['req_type'],
                        'req_dept'              => $d['req_dept'],
                        'dept'                  => $d['dept'],
                        'code'                  => $d['code'],
                        'qty'                   => $d['qty'],
                        'qty_order'             => $d['qty_order'],
                        'order_info'            => $d['order_info'],
                        'receive_qty'           => $d['receive_qty'],
                        'receive_info'          => $d['receive_info'],
                        'in_stock_qty'          => $d['in_stock_qty'],
                        'in_stock_info'         => $d['in_stock_info'],
                        'qty_left'              => $d['qty_left'],
                        'date_req'              => $d['date_req'],
                        'delivery_date'         => $d['delivery_date'],
                        'delivery_date_remark'  => $d['delivery_date_remark'],
                        'delivery_reply_log'    => $d['delivery_reply_log'],
                        'name'                  => $d['name'],
                        'description'           => $d['description'],
                        'model'                 => $d['model'],
                        'order_req_num'         => isset($d['order_req_num']) ? $d['order_req_num'] : '',
                        'customer_address'      => $d['customer_address'],
                        'customer_aggrement'    => $d['customer_aggrement'],
                        'remark'                => $d['remark'],
                        'req_creater'           => $d['req_creater'],
                        'req_applier'           => $d['req_applier'],
                        'req_reason'            => $d['req_reason'],
                        'project_info'          => $d['project_info'],
                        'req_remark'            => $d['req_remark'],
                        'req_create_date'       => $d['req_create_time'] != '' ? date('Y-m-d', strtotime($d['req_create_time'])) : '',
                        'req_create_time'       => $d['req_create_time'] != '' ? date('H:i:s', strtotime($d['req_create_time'])) : '',
                        'req_release_date'      => $d['req_release_time'] != '' ? date('Y-m-d', strtotime($d['req_release_time'])) : '',
                        'req_release_time'      => $d['req_release_time'] != '' ? date('H:i:s', strtotime($d['req_release_time'])) : ''
                );
        
                array_push($data_csv, $info);
            }
        
            return $data_csv;
        }
        
        return array('total' => $total, 'rows' => $data);
    }
    
    /**
     * 获取最新申购单编号
     * @param unknown $type_id
     * @return string
     */
    public function getNewNum($type_id)
    {
        $type = new Erp_Model_Purchse_Type();
        
        $data = $type->fetchRow("id = ".$type_id)->toArray();
        
        $type_code = $data['code'];
        
        $pre = 'PR'.$type_code;
        
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
    
    // 取消申请
    public function cancelReqById($id)
    {
        if($id){
            $now = date('Y-m-d H:i:s');
            $user_session = new Zend_Session_Namespace('user');
            $user_id = $user_session->user_info['user_id'];
            
            $reqData = $this->getData(null, $id);
            $review_info = $reqData['review_info'].'<br>'.$now.': '.$user_session->user_info['user_name'].' [取消]';
            
            $data = array(
                    'active'        => 0,
                    'review_info'   => $review_info,
                    'update_user'   => $user_id,
                    'update_time'   => $now
            );
            // 取消申请
            $this->update($data, "id = ".$id);
            // 取消申请项
            $items = new Erp_Model_Purchse_Reqitems();
            $items->cancelByReqId($id);
        }
    }
    
    public function getData($condition = array(), $req_id = null)
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t1.create_user = t2.id", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t2.employee_id = t3.id", array('creater' => 'cname'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t1.update_user = t4.id", array())
                    ->joinLeft(array('t5' => $this->_dbprefix.'employee'), "t4.employee_id = t5.id", array('updater' => 'cname'))
                    ->joinLeft(array('t6' => $this->_dbprefix.'employee_dept'), "t1.dept_id = t6.id", array('dept' => 'name'))
                    ->joinLeft(array('t7' => $this->_dbprefix.'erp_pur_type'), "t1.type_id = t7.id", array('type' => 'name', 'req_flow_id'))
                    ->joinLeft(array('t9' => $this->_dbprefix.'user'), "t1.apply_user = t9.id", array())
                    ->joinLeft(array('t10' => $this->_dbprefix.'employee'), "t9.employee_id = t10.id", array('apply_user_name' => 'cname'))
                    ->joinLeft(array('t11' => $this->_dbprefix.'erp_pur_transfer'), "t11.id = t1.transfer_id", array('transfer_type', 'transfer_description', 'transfer_state' => 'state'))
                    /* ->joinLeft(array('t8' => $this->_dbprefix.'erp_pur_req_items'), "t1.id = t8.req_id", array()) */;
        
        if($req_id){
            $sql->where("t1.id = ".$req_id);
            
            return $this->fetchRow($sql)->toArray();
        }else{
            $sql->where("t1.active = ".$condition['active']);
            
            if ($condition['state'] !== null) {
                $sql->where("t1.state = ".$condition['state']);
            }
            
            if($condition['applier']){
                $sql->where("t10.cname like '%".$condition['applier']."%' or t10.ename like '%".$condition['applier']."%'");
            }
            
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
            
            if ($condition['dept']){
                $dept = json_decode($condition['dept']);
            
                if(count($dept)){
                    $dept_con = "t1.dept_id = ".$dept[0];
            
                    for($i = 1; $i < count($dept); $i++){
                        $dept_con .= " or t1.dept_id = ".$dept[$i];
                    }
            
                    $sql->where($dept_con);
                }
            }
            
            if ($condition['key']){
                $sql->where("t1.number like '%".$condition['key']."%' or t1.reason like '%".$condition['key']."%' or t1.remark like '%".$condition['key']."%' or t1.approved_user like '%".$condition['key']."%'");// or t8.name like '%".$condition['key']."%'
            }
            
            $total = $this->fetchAll($sql)->count();
            
            $sql->order(array('t1.state', 't1.number desc', 't1.create_time desc'))
                ->limitPage($condition['page'], $condition['limit']);
            
            $data = $this->fetchAll($sql)->toArray();
            /* echo '<pre>';
            print_r($data);
            exit; */
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
                
                // 当状态不为拒绝时才能获取，否则会报错
                if($data[$i]['state'] != 1){
                    // 获取审核情况
                    $review_state = $help->getReviewState('purchse_req_add', $data[$i]['id']); 
                    
                    $data[$i]['reviewer'] = implode(',', $help->getEmployeeNameByIdArr($review_state['reviewer']));
                    $data[$i]['review_state'] = $review_state['info'];
                    $data[$i]['can_review'] = in_array($employee_id, $review_state['reviewer']) && !in_array($employee_id, $review_state['reviewer_finished']) ? 1 : 0;
                    $data[$i]['current_step'] = $review_state['step_chk']['current_step'];
                    $data[$i]['last_step'] = $review_state['step_chk']['last_step'];
                    $data[$i]['to_finish'] = $review_state['step_chk']['to_finish'];
                    $data[$i]['next_step'] = $review_state['step_chk']['next_step'];
                }
                
                if($help->chkIsReviewer('purchse_req_add', $data[$i]['id'], $employee_id)){
                    $data[$i]['is_reviewer'] = 1;
                }else{
                    $data[$i]['is_reviewer'] = 0;
                }
            }
            
            return array('total' => $total, 'rows' => $data);
        }
    }
}