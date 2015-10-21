<?php
/**
 * 2014-4-1 下午9:47:41
 * @author x.li
 * @abstract 
 */
class Erp_Stock_TransferController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $user_session = new Zend_Session_Namespace('user');
        
        $this->view->accessViewTotal = 0;
        
        $this->view->user_id = 0;
        
        if(isset($user_session->user_info)){
            $this->view->user_id = $user_session->user_info['user_id'];
        
            if(Application_Model_User::checkPermissionByRoleName('系统管理员') || Application_Model_User::checkPermissionByRoleName('财务人员')){
                $this->view->accessViewTotal = 1;
            }
        }
    }
    
    /**
     * 校正订单入库数量、申请入库数量
     */
    public function correctinstockAction()
    {
        $receiveModel = new Erp_Model_Stock_Receive();
        $data = $receiveModel->correctInStockQty();
        
        exit;
    }
    
    // 获取打印内容
    public function getprintAction()
    {
        $result = array(
                'success'   => true,
                'info'      => ''
        );
        
        $request = $this->getRequest()->getParams();
        
        if(isset($request['id'])){
            $receive = new Erp_Model_Stock_Receive();
            $data = $receive->getData(null, $request['id'], '调拨');
            
            $items = new Erp_Model_Purchse_Receiveitems();
            $itemsData = $items->getData($request['id']);
            
            $tpl = new Erp_Model_Tpl();
            $tplHtmlData = $tpl->fetchRow("type = 'stock_transfer'")->toArray();
            $tplHtml = $tplHtmlData['html'];
            
            $itemsHtml = '';
            $i = 0;
            
            foreach ($itemsData as $item){
                $i++;
                
                $itemsHtml .= '
                    <tr>
                        <td>'.$i.'</td>
                        <td>'.$item['items_code'].'</td>
                        <td width="100px" style="word-wrap:break-word;">'.$item['items_name'].'</td>
                        <td width="150px" style="word-wrap:break-word;">'.$item['items_description'].'</td>
                        <td>'.$item['items_qty'].'</td>
                        <td>'.$item['items_unit'].'</td>
                        <td>'.$item['items_warehouse_code'].'</td>
                        <td>'.$item['items_warehouse_code_transfer'].'</td>
                        <td>'.$item['items_remark'].'</td>
                    </tr>';
            }
            
            $orderInfo = array(
                    'title'         => '库存交易 - 调拨',
                    'number'        => $data['number'],
                    'type'          => $data['transaction_type'],
                    'creater'       => $data['creater'],
                    'date'          => $data['date'],
                    'description'   => $data['description'],
                    'remark'        => $data['remark'],
                    'items'         => $itemsHtml,
                    'company_logo'  => HOME_PATH.'/public/images/company.png'
            );
            
            foreach ($orderInfo as $key => $val){
                $tplHtml = str_replace('<tpl_'.$key.'>', $val, $tplHtml);
            }
            
            $result['info'] = $tplHtml;
        }else{
            $result['success'] = false;
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    public function gettransferAction()
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
        
        $data = $receive->getData($condition, null, '调拨');
        
        if($option == 'csv'){
            $this->view->layout()->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);
        
            $h = new Application_Model_Helpers();
            $h->exportCsv($data, '库存交易-调拨');
        }else{
            echo Zend_Json::encode($data);
        }
        
        exit;
    }
    
    public function gettransferitemsAction()
    {
        $data = array();
        
        $request = $this->getRequest()->getParams();
        
        $receive_id = isset($request['transfer_id']) ? $request['transfer_id'] : 0;
        
        if($receive_id > 0){
            $items = new Erp_Model_Purchse_Receiveitems();
            
            $data = $items->getData($receive_id);
        }
        
        echo Zend_Json::encode($data);
        
        exit;
    }
    
    public function edittransferAction()
    {
        // 返回值数组
        $result = array(
                'success'       => true,
                'info'          => '编辑成功',
                'receive_id'      => 0
        );
        
        $request = $this->getRequest()->getParams();
        
        $operate = array(
                'new'       => '新建',
                'edit'      => '编辑',
                'delete'    => '删除'
        );
        
        // 操作类别（新建、更新、删除）
        $type = isset($request['operate']) ? $request['operate'] : '';
        $pre = isset($request['pre']) ? $request['pre'] : 'WT';
        
        $orderType = '调拨';
        /* if($pre == 'WT'){
            $orderType = '调拨';
        } */
        
        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user_id = $user_session->user_info['user_id'];
        
        $receive = new Erp_Model_Stock_receive();
        
        if($type == 'new' || $type == 'edit'){
            $data = array(
                    'type'              => $orderType,
                    'order_id'          => $request['order_id'],
                    'transaction_type'  => $request['transaction_type'],
                    'date'              => $request['date'],
                    'remark'            => $request['remark'],
                    'description'       => $request['description'],
                    'update_time'       => $now,
                    'update_user'       => $user_id
            );
        
            if ($type == 'new') {
                $data['number'] = $receive->getNewNum($pre);
                $data['create_time'] = $now;
                $data['create_user'] = $user_id;
                $data['review_info'] = $now.': '.$user_session->user_info['user_name'].' [新建]';
        
                try{
                    $transfer_id = $result['transfer_id'] = $receive->insert($data);
                } catch (Exception $e){
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
                }
            }elseif ($type == 'edit'){
                try {
                    $review_info = $now.': '.$user_session->user_info['user_name'].' [修改]';
                    $receiveData = $receive->getData(null, $request['id']);
                    
                    $data['review_info'] = $receiveData['review_info'].'<br>'.$review_info;
                    $receive->update($data, "id = ".$request['id']);
                    $result['outstock_id'] = $request['id'];
                } catch (Exception $e) {
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
                }
            }
        }elseif ($type == 'delete'){
            try {
                $receive->delete("id = ".$request['transfer_id']);
            } catch (Exception $e){
                $result['success'] = false;
                $result['info'] = $e->getMessage();
            }
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    public function testAction()
    {
        $member = new Admin_Model_Member();
        
        $noticeMails = array();
        $noticeUsers = array();
        
        $noticeTo = $member->getMemberWithManagerByName('库房');
        
        // 通知：库房
        foreach ($noticeTo as $n){
            if($n['email'] != '' && !in_array($n['user_id'], $noticeUsers)){
                array_push($noticeMails, $n['email']);
                array_push($noticeUsers, $n['user_id']);
            }
        }
        echo '<pre>';print_r($noticeMails);exit;
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
        
        $receive_id = $json->transfer_id;
        
        $json_items = $json->items;
        
        $items_updated    = $json_items->updated;
        $items_inserted   = $json_items->inserted;
        $items_deleted    = $json_items->deleted;
        
        $receive = new Erp_Model_Stock_receive();
        $items = new Erp_Model_Purchse_Receiveitems();
        $stock = new Erp_Model_Stock_Stock();
        $warehouse = new Erp_Model_Warehouse_Warehouse();
        
        $receiveData = $receive->getData(null, $receive_id, '调拨');
        
        // 更新
        if(count($items_updated) > 0){
            foreach ($items_updated as $val){
                $data = array(
                        'code'              => $val->items_code,
                        'name'              => $val->items_name,
                        'description'       => $val->items_description,
                        'qty'               => $val->items_qty,
                        'unit'              => $val->items_unit,
                        'warehouse_code'    => $val->items_warehouse_code,
                        'warehouse_code_transfer'    => $val->items_warehouse_code_transfer,
                        'remark'            => $val->items_remark,
                        'update_user'       => $user_id,
                        'update_time'       => $now
                );
    
                try {
                    $items->update($data, "id = ".$val->items_id);
                } catch (Exception $e) {
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
    
                    echo Zend_Json::encode($result);
    
                    exit;
                }
            }
        }
        
        // 插入
        if(count($items_inserted) > 0){
            foreach ($items_inserted as $val){
                $total = round($val->items_qty * $val->items_price, 2);
                
                $data = array(
                        'receive_id'        => $receive_id,
                        'code'              => $val->items_code,
                        'name'              => $val->items_name,
                        'description'       => $val->items_description,
                        'qty'               => $val->items_qty,
                        'price'             => $val->items_price,
                        'total'             => $total,
                        'unit'              => $val->items_unit,
                        'warehouse_code'    => $val->items_warehouse_code,
                        'warehouse_code_transfer'    => $val->items_warehouse_code_transfer,
                        'remark'            => $val->items_remark,
                        'create_user'       => $user_id,
                        'create_time'       => $now,
                        'update_user'       => $user_id,
                        'update_time'       => $now
                );
    
                try {
                    $receive_item_id = $items->insert($data);
                    
                    // 记录库存数据
                    $stockData = array(
                            'code'              => $val->items_code,
                            'warehouse_code'    => $val->items_warehouse_code_transfer,
                            'qty'               => $val->items_qty,
                            'total'             => $total,
                            'create_user'       => $user_id,
                            'create_time'       => $now,
                            'doc_type'          => '调拨收货',
                            'transaction_type'  => $receiveData['transaction_type'],
                            'doc_number'        => $receiveData['number']
                    );
                    
                    $stock->insert($stockData);
                    
                    $qty = round(0 - $val->items_qty, 4);
                    $total = round(0 - $total, 2);
                    
                    // 记录库存数据
                    $stockData = array(
                            'code'              => $val->items_code,
                            'warehouse_code'    => $val->items_warehouse_code,
                            'qty'               => $qty,
                            'total'             => $total,
                            'create_user'       => $user_id,
                            'create_time'       => $now,
                            'doc_type'          => '调拨发货',
                            'doc_number'        => $receiveData['number']
                    );
                    
                    $stock->insert($stockData);
                } catch (Exception $e) {
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
    
                    echo Zend_Json::encode($result);
    
                    exit;
                }
            }
        }
        
        // 更新总计
        $items->refreshReceiveTotal($receive_id);
        
        if($result['success']){
            // 转储通知（针对从待检仓转出）
            if ($receiveData['transaction_type'] == '外购入库' && $receiveData['order_id'] != '') {
                // 计算收货入库数量（订单、申请）
                $receiveModel = new Erp_Model_Stock_Receive();
                $receiveModel->correctInStockQty($receive_id);
                
                $member = new Admin_Model_Member();
            
                $noticeMails = array();
                $noticeUsers = array();
            
                $noticeTo = $member->getMemberWithManagerByName('外购入库');
            
                // 通知：外购入库
                foreach ($noticeTo as $n){
                    if($n['email'] != '' && !in_array($n['user_id'], $noticeUsers)){
                        array_push($noticeMails, $n['email']);
                        array_push($noticeUsers, $n['user_id']);
                    }
                }
            
                $noticeTo = $member->getMemberWithManagerByName('库房');
            
                // 通知：库房
                foreach ($noticeTo as $n){
                    if($n['email'] != '' && !in_array($n['user_id'], $noticeUsers)){
                        array_push($noticeMails, $n['email']);
                        array_push($noticeUsers, $n['user_id']);
                    }
                }
            
                // 通知：QA
                $noticeTo = $member->getMemberWithManagerByName('QA');
            
                foreach ($noticeTo as $n){
                    if($n['email'] != '' && !in_array($n['user_id'], $noticeUsers)){
                        array_push($noticeMails, $n['email']);
                        array_push($noticeUsers, $n['user_id']);
                    }
                }
            
                // 通知：外购入库通知
                $noticeTo = $member->getMemberWithManagerByName('外购入库通知');
            
                foreach ($noticeTo as $n){
                    if($n['email'] != '' && !in_array($n['user_id'], $noticeUsers)){
                        array_push($noticeMails, $n['email']);
                        array_push($noticeUsers, $n['user_id']);
                    }
                }
            
                // 获取采购订单信息
                $order = new Erp_Model_Purchse_Order();
                $order_data = $order->getData(null, $receiveData['order_id']);
                // 获取采购订单相关参与人员
                $receive_items_order = new Erp_Model_Purchse_Receiveitemsorder();
                $relatedUserInfo = $receive_items_order->getReqRelatedUsers(array($order_data['number']));
            
                foreach ($relatedUserInfo as $n){
                    if($n['email'] != '' && !in_array($n['user_id'], $noticeUsers)){
                        array_push($noticeMails, $n['email']);
                        array_push($noticeUsers, $n['user_id']);
                    }
                }
            
                $mail = new Application_Model_Log_Mail();
            
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
                                <th>物料号</th>
                                <th>物料名称</th>
                                <th>物料描述</th>
                                <th>数量</th>
                                <th>单位</th>
                                <th>出库仓位</th>
                                <th>入库仓位</th>
                                <th>备注</th>
                                </tr>';
                $i = 0;
                foreach ($items_inserted as $val){
                    $i++;
            
                    $warehouseInfo = $warehouse->getInfoByCode($val->items_warehouse_code);
                    $warehouseTransferInfo = $warehouse->getInfoByCode($val->items_warehouse_code_transfer);
            
                    $stockQty = $stock->getStockQty($val->items_code, array($val->items_warehouse_code));
                    $stockTransferQty = $stock->getStockQty($val->items_code, array($val->items_warehouse_code_transfer));
            
                    $itemsTable .= '<tr>
                            <td>'.$i.'</td>
                            <td>'.$val->items_code.'</td>
                            <td>'.$val->items_name.'</td>
                            <td>'.$val->items_description.'</td>
                            <td>'.$val->items_qty.'</td>
                            <td>'.$val->items_unit.'</td>
                            <td>'.$val->items_warehouse_code.' '.$warehouseInfo['name'].' ['.$stockQty['total'].']</td>
                            <td>'.$val->items_warehouse_code_transfer.' '.$warehouseTransferInfo['name'].' ['.$stockTransferQty['total'].']</td>
                            <td>'.$val->items_remark.'</td>
                            </tr>';
                }
                $itemsTable .= '</table>';
            
                $title = '库存调拨 - '.$receiveData['transaction_type'].' ['.$order_data['supplier_code'].' '.$order_data['supplier_ename'].']';
            
                $mailContent = '<div>'.$title.'，请登录系统查看：</div>
                        <div>
                        <p><b>单据号：</b>'.$receiveData['number'].'</p>
                        <p><b>日期：</b>'.$receiveData['date'].'</p>
                        <p><b>采购订单：</b>'.$order_data['number'].'</p>
                        <p><b>制单人：</b>'.$user_session->user_info['user_name'].'</p>
                        <p><b>描述：</b>'.$receiveData['description'].'</p>
                        <p><b>备注：</b>'.$receiveData['remark'].'</p>
                        </div><div>'.$itemsTable.'</div><hr>';
            
                $mailData = array(
                        'type'      => '通知',
                        'subject'   => $title,
                        'cc'        => $user_session->user_info['user_email'],
                        'content'   => $mailContent,
                        'add_date'  => $now,
                        'to'        => implode(',', $noticeMails),
                        'user_id'   => $user_id
                );
                
                try {
                    // 记录邮件日志并发送邮件
                    $mail->send($mail->insert($mailData));
                } catch (Exception $e) {
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
                }
            }else{
                $member = new Admin_Model_Member();
                
                $noticeMails = array();
                $noticeUsers = array();
                
                $noticeTo = $member->getMemberWithManagerByName('通知-库存交易-调拨');
                
                foreach ($noticeTo as $n){
                    if($n['email'] != '' && !in_array($n['user_id'], $noticeUsers)){
                        array_push($noticeMails, $n['email']);
                        array_push($noticeUsers, $n['user_id']);
                    }
                }
                
                if(count($noticeMails)){
                    $warehouse = new Erp_Model_Warehouse_Warehouse();
                
                    $title = '库存交易-调拨-'.$receiveData['transaction_type'];
                
                    $mailContent = '<div><b>'.$title.'</b>，请登录系统查看：</div>
                            <div>
                            <p><b>单据号：</b>'.$receiveData['number'].'</p>
                            <p><b>制单员：</b>'.$user_session->user_info['user_name'].'</p>
                            <p><b>描述：</b>'.$receiveData['description'].'</p>
                            <p><b>备注：</b>'.$receiveData['remark'].'</p>
                            <p><b>时间：</b>'.$receiveData['create_time'].'</p>
                            </div><hr>';
                
                    $mailContent .= '<div><style type="text/css">
    table.gridtable {
        font-family: verdana,arial,sans-serif;
        font-size:12px;
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
    .delete{
        text-decoration: line-through;
        color: #FF0000;
    }
    .update{
        font-weight: bold;
        color: #000093;
    }
    .inactive{
        font-weight: bold;
        color: #999999;
    }
    </style><table class="gridtable">
                                <tr>
                                <th>#</th>
                                <th>物料号</th>
                                <th>名称</th>
                                <th>描述</th>
                                <th>数量</th>
                                <th>单位</th>
                                <th>出库仓位</th>
                                <th>入货仓位</th>
                                <th>备注</th>
                                </tr>';
                
                    $itemsData = $items->getData($receive_id);
                    $i = 0;
                    foreach ($itemsData as $d){
                        $i++;
                
                        $warehouseData = $warehouse->getInfoByCode($d['items_warehouse_code']);
                        $warehouseInfo = $warehouseData["code"];
                
                        if(isset($warehouseData["name"])){
                            $warehouseInfo = $warehouseData["code"].' '.$warehouseData["name"];
                        }
                
                        $warehouseTransferData = $warehouse->getInfoByCode($d['items_warehouse_code_transfer']);
                        $warehouseTransferInfo = $warehouseTransferData["code"];
                
                        if(isset($warehouseTransferData["name"])){
                            $warehouseTransferInfo = $warehouseTransferData["code"].' '.$warehouseTransferData["name"];
                        }
                
                        $mailContent .= '<tr>
                            <td>'.$i.'</td>
                            <td>'.$d['items_code'].'</td>
                            <td>'.$d['items_name'].'</td>
                            <td>'.$d['items_description'].'</td>
                            <td>'.$d['items_qty'].'</td>
                            <td>'.$d['items_unit'].'</td>
                            <td>'.$warehouseInfo.'</td>
                            <td>'.$warehouseTransferInfo.'</td>
                            <td>'.$d['items_remark'].'</td>
                          </tr>';
                    }
                
                    $mailContent .= '</table></div><hr>';
                
                    $mailData = array(
                            'type'      => '通知',
                            'subject'   => $title,
                            'cc'        => $user_session->user_info['user_email'],
                            'content'   => $mailContent,
                            'add_date'  => $now,
                            'to'        => implode(',', $noticeMails),
                            'user_id'   => $user_id
                    );
                
                    try {
                        // 记录邮件日志并发送邮件
                        $mail = new Application_Model_Log_Mail();
                        $mail->send($mail->insert($mailData));
                    } catch (Exception $e) {
                        $result['success'] = false;
                        $result['info'] = $e->getMessage();
                    }
                }
            }
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
}