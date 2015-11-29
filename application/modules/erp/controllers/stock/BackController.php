<?php
/**
 * 销售退货
 * 2014-4-1 下午9:47:41
 * @author x.li
 * @abstract 
 */
class Erp_Stock_BackController extends Zend_Controller_Action
{
    public function indexAction()
    {
    	$user_session = new Zend_Session_Namespace('user');
    	
    	$this->view->accessViewTotal = 0;
    	
    	$this->view->user_id = 0;
    	
    	if(isset($user_session->user_info)){
    		$this->view->user_id = $user_session->user_info['user_id'];
    	
    		if(Application_Model_User::checkPermissionByRoleName('系统管理员') 
    		|| Application_Model_User::checkPermissionByRoleName('销售人员') 
    		|| Application_Model_User::checkPermissionByRoleName('财务人员')){
    			$this->view->accessViewTotal = 1;
    		}
    	}
    }
    
    public function getcanbebacklistAction()
    {
        $request = $this->getRequest()->getParams();
        
        $key = isset($request['key']) && $request['key'] != '' ? $request['key'] : null;
        $customer_id = isset($request['customer_id']) && $request['customer_id'] != '' ? $request['customer_id'] : null;
        
        $order = new Erp_Model_Sale_Receiveitemsordersale();
        
        echo Zend_Json::encode($order->getCanBeBackQty($key, $customer_id));
        
        exit;
    }
    
    public function getbackAction()
    {
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        $option = isset($request['option']) ? $request['option'] : 'data';
        
        $receive = new Erp_Model_Stock_Receive();
    
        // 查询条件
        $condition = array(
                'key'       => isset($request['key']) ? $request['key'] : '',
                'date_from' => isset($request['date_from']) ? $request['date_from'] : null,
                'date_to'   => isset($request['date_to']) ? $request['date_to'] : null,
                'page'      => isset($request['page']) ? $request['page'] : 1,
                'limit'     => isset($request['limit']) ? $request['limit'] : 0,
                'type'      => $option
        );
        
        $data = $receive->getData($condition, null, '销售退货');
        
        if($option == 'csv'){
            $this->view->layout()->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);
        
            $h = new Application_Model_Helpers();
            $h->exportCsv($data, '销售退货');
        }else{
            echo Zend_Json::encode($data);
        }
        
        exit;
    }
    
    public function saveSplitItem($splitData)
    {
        if($splitData['items_order_id']){
            $items = new Erp_Model_Purchse_Receiveitems();
            $itemsorder = new Erp_Model_Sale_Receiveitemsordersale();
            $stock = new Erp_Model_Stock_Stock();
    
            // 更新是先清空之前所保存的收货数据分拆信息
            $itemsorder->delete("receive_item_id = ".$splitData['receive_item_id']);
    
            $now = date('Y-m-d H:i:s');
            $user_session = new Zend_Session_Namespace('user');
            $user_id = $user_session->user_info['user_id'];
            
            $qty = 0 - $splitData['items_qty'];
            $price = $splitData['items_price'];
            $total = round($price * $qty, 2);
            $lineTotal = round(0 - $total, 2);
            
            $data = array(
                    'receive_item_id'       => $splitData['receive_item_id'],
                    'order_item_id'         => $splitData['items_order_item_id'],
                    'qty'                   => $qty,
                    'price'                 => $price,
                    'total'                 => $lineTotal,
                    'order_id'              => $splitData['items_order_id'],
                    'order_number'          => $splitData['items_order_number'],
                    'code'                  => $splitData['items_code'],
                    'customer_code'         => $splitData['items_customer_code'],
                    'customer_description'  => $splitData['items_customer_description'],
                    'type'                  => '销售退货',
                    'create_user'           => $user_id,
                    'create_time'           => $now
            );
            
            $itemsorder->insert($data);
            
            // 更新行总计
            $items->update(array('total' => $lineTotal), "id = ".$splitData['receive_item_id']);
            
            // 记录库存数据
            $stockData = array(
                    'code'              => $splitData['items_code'],
                    'warehouse_code'    => $splitData['items_warehouse_code'],
                    'qty'               => 0 - $qty,
                    'total'             => $total,
                    'create_user'       => $user_id,
                    'create_time'       => $now,
                    'doc_type'          => '销售退货',
                    'doc_number'        => $splitData['return_number']
            );
            
            $stock->insert($stockData);
        }
    }
    
    public function edititemsAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '编辑成功'
        );
    
        $request = $this->getRequest()->getParams();
    
        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user_id = $user_session->user_info['user_id'];
    
        $json = json_decode($request['json']);
    
        $back_id = $json->back_id;
    
        $json_items = $json->items;
    
        $items_updated    = $json_items->updated;
        $items_inserted   = $json_items->inserted;
        $items_deleted    = $json_items->deleted;
    
        $receive = new Erp_Model_Stock_receive();
        $items = new Erp_Model_Purchse_Receiveitems();
        $itemsorder = new Erp_Model_Sale_Receiveitemsordersale();
    
        $receiveData = $receive->getData(null, $back_id, '销售退货');
    
        // 更新
        if(count($items_updated) > 0){
            foreach ($items_updated as $val){
                $data = array(
                        'order_number'      => $val->items_order_number,
                        'code'              => $val->items_code,
                        'name'              => $val->items_name,
                        'description'       => $val->items_description,
                        'qty'               => $val->items_qty,
                        'unit'              => $val->items_unit,
                        'remark'            => $val->items_remark,
                        'update_user'       => $user_id,
                        'update_time'       => $now
                );
    
                try {
                    $items->update($data, "id = ".$val->items_id);
    
                    // 记录分拆数据
                    $splitData = array(
                            'receive_number'            => $receiveData['number'],
                            'receive_item_id'           => $val->items_id,
                            'items_order_qty'           => $val->items_order_qty,
                            'items_order_item_price'    => $val->items_order_item_price,
                            'items_code'                => $val->items_code,
                            'items_order_id'            => $val->items_order_id,
                            'items_order_number'        => $val->items_order_number
                    );
    
                    $this->saveSplitItem($splitData);
                } catch (Exception $e) {
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
    
                    echo Zend_Json::encode($result);
    
                    exit;
                }
            }
        }
        
        // 此次退货货的采购订单号
        $orderNumArr = array();
        
        // 插入
        if(count($items_inserted) > 0){
            foreach ($items_inserted as $val){
                $data = array(
                        'receive_id'            => $back_id,
                        'order_number'          => $val->items_order_number,
                        'code'                  => $val->items_code,
                        'name'                  => $val->items_name,
                        'description'           => $val->items_description,
                        'qty'                   => $val->items_qty,
                        'price'                 => $val->items_price,
                        'unit'                  => $val->items_unit,
                        'remark'                => $val->items_remark,
                        'customer_code'         => $val->items_customer_code,
                        'customer_description'  => $val->items_customer_description,
                        'warehouse_code'        => $val->items_warehouse_code,
                        'create_user'           => $user_id,
                        'create_time'           => $now,
                        'update_user'           => $user_id,
                        'update_time'           => $now
                );
    
                try {
                    $receive_item_id = $items->insert($data);
    
                    // 记录分拆数据
                    $splitData = array(
                            'return_number'                 => $receiveData['number'],
                            'receive_item_id'               => $receive_item_id,
                            'items_qty'                     => $val->items_qty,
                            'items_price'                   => $val->items_price,
                            'items_code'                    => $val->items_code,
                            'items_customer_code'           => $val->items_customer_code,
                            'items_customer_description'    => $val->items_customer_description,
                            'items_order_id'                => $val->items_order_id,
                            'items_order_item_id'           => $val->items_order_item_id,
                            'items_warehouse_code'          => $val->items_warehouse_code,
                            'items_order_number'            => $val->items_order_number
                    );
    
                    $this->saveSplitItem($splitData);
                    
                    if(!in_array($val->items_order_number, $orderNumArr)){
                        array_push($orderNumArr, $val->items_order_number);
                    }
                } catch (Exception $e) {
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
    
                    echo Zend_Json::encode($result);
    
                    exit;
                }
            }
        }
    
        // 更新总计
        $items->refreshReceiveTotal($back_id);
        
        
        
        
        
        
        echo Zend_Json::encode($result);
        
        exit;
        
        
        
        
        
        
        // 退货通知
        $receive_items_order = new Erp_Model_Purchse_Receiveitemsorder();
        $order = new Erp_Model_Purchse_Order();
        
        $relatedUserInfo = count($orderNumArr) > 0 ? $receive_items_order->getReqRelatedUsers($orderNumArr) : array();
        
        $member = new Admin_Model_Member();
        $warehouse = new Erp_Model_Warehouse_Warehouse();
        
        $noticeTo = $member->getMemberWithManagerByName('采购退货通知');
        
        $noticeMails = array();
        $noticeUsers = array();
        
        foreach ($noticeTo as $n){
            if($n['email'] != '' && !in_array($n['user_id'], $noticeUsers)){
                array_push($noticeMails, $n['email']);
                array_push($noticeUsers, $n['user_id']);
            }
        }
        
        foreach ($relatedUserInfo as $n){
            if($n['email'] != '' && !in_array($n['user_id'], $noticeUsers)){
                array_push($noticeMails, $n['email']);
                array_push($noticeUsers, $n['user_id']);
            }
        }
        
        $mail = new Application_Model_Log_Mail();
        
        $supplierArr = array();
        
        $i = 0;
        $itemsTable = '<style type="text/css">
table.gridtable {
	font-family: verdana,arial,sans-serif;
	font-size:11px;
	color:#333333;
	border-width: 1px;
	border-color: #666666;
	border-collapse: collapse;
}
table.gridtable th {
	border-width: 1px;
	padding: 8px;
	border-style: solid;
	border-color: #666666;
	background-color: #dedede;
}
table.gridtable td {
	border-width: 1px;
	padding: 8px;
	border-style: solid;
	border-color: #666666;
	background-color: #ffffff;
}
</style><table class="gridtable"><tr>
                                <th>#</th>
                                <th>供应商</th>
                                <th>采购订单</th>
                                <th>物料号</th>
                                <th>物料名称</th>
                                <th>物料描述</th>
                                <th>退货数量</th>
                                <th>单位</th>
                                <th>退货仓库</th>
                                <th>备注</th>
                                <th>订单信息</th>
                                </tr>';
        
        foreach ($items_inserted as $val){
            $itemInfo = '';
        
            if($val->items_order_number){
                $itemInfoData = $order->getItemDetails($val->items_order_number, $val->items_code);
                
                $itemInfo = '<table class="gridtable"><tr>
							<th>申请单号</th>
							<th>申请日期</th>
							<th>申请数量</th>
							<th>下单数量</th>
							<th>订单数量</th>
                            <th>客户收件人地址简码</th>
                            <th>客户合同号</th>
							</tr>';
                
                foreach ($itemInfoData as $d){
                    $itemInfo .= '<tr>
							      <td>'.$d['req_number'].'</td>
							      <td>'.$d['req_item_date'].'</td>
							      <td>'.$d['req_item_qty'].'</td>
							      <td>'.$d['order_req_item_qty'].'</td>
							      <td>'.$d['order_item_qty'].'</td>
							      <td>'.$d['customer_address'].'</td>
							      <td>'.$d['customer_aggrement'].'</td>
							      </tr>';
                }
                
                $itemInfo .= '</table>';
                
                $supplier = $d['supplier_code'].' '.$d['supplier_name'];
                
                if(!in_array($supplier, $supplierArr)){
                    array_push($supplierArr, $supplier);
                }
            }
        
            $i++;
             
            $warehouseInfo = $warehouse->getInfoByCode($val->items_warehouse_code);
             
            $itemsTable .= '<tr>
							<td>'.$i.'</td>
							<td>'.$supplier.'</td>
							<td>'.$val->items_order_number.'</td>
							<td>'.$val->items_code.'</td>
							<td>'.$val->items_name.'</td>
							<td>'.$val->items_description.'</td>
							<td>'.$val->items_qty.'</td>
							<td>'.$val->items_unit.'</td>
							<td>'.$val->items_warehouse_code.' '.$warehouseInfo['name'].'</td>
							<td>'.$val->items_remark.'</td>
							<td>'.$itemInfo.'</td>
							</tr>';
        }
        $itemsTable .= '</table>';
        
        $mailContent = '<div>采购退货，请登录系统查看：</div>
					    <div>
					    <p><b>退货单号：</b>'.$receiveData['number'].'</p>
					    <p><b>收货日期：</b>'.$receiveData['date'].'</p>
					    <p><b>收货人：</b>'.$user_session->user_info['user_name'].'</p>
					    <p><b>描述：</b>'.$receiveData['description'].'</p>
					    <p><b>备注：</b>'.$receiveData['remark'].'</p>
					    </div><div>'.$itemsTable.'</div><hr>';
        
        $mailData = array(
                'type'      => '通知',
                'subject'   => '退货通知 - '.$receiveData['number'].' - '.implode(', ', $supplierArr),
                'cc'        => $user_session->user_info['user_email'],
                'content'   => $mailContent,
                'add_date'  => $now,
                'to'        => implode(',', $noticeMails),
                'user_id'   => implode(',', $noticeUsers)
        );
        
        try {
            // 记录邮件日志并发送邮件
            $mail->send($mail->insert($mailData));
        } catch (Exception $e) {
            $result['success'] = false;
            $result['info'] = $e->getMessage();
        }
    
        echo Zend_Json::encode($result);
    
        exit;
    }
}