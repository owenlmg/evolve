<?php
/**
 * 2014-4-1 下午9:47:41
 * @author x.li
 * @abstract 
 */
class Erp_Stock_IndexController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $user_session = new Zend_Session_Namespace('user');
        
        $this->view->accessViewTotal = 0;
        
        $this->view->user_id = 0;
        
        if(isset($user_session->user_info)){
            $this->view->user_id = $user_session->user_info['user_id'];
        
            if(Application_Model_User::checkPermissionByRoleName('系统管理员') 
                || Application_Model_User::checkPermissionByRoleName('财务人员')
                || Application_Model_User::checkPermissionByRoleName('采购人员')){
                $this->view->accessViewTotal = 1;
            }
        }
    }
    
    // 获取打印内容
    public function getprintAction()
    {
        $result = array(
                'success'   => true,
                'info'      => ''
        );
        
        $request = $this->getRequest()->getParams();
        
        if(isset($request['id']) && isset($request['type'])){
            $type = $request['type'];
            $type_name = $type == 'in' ? '采购收货' : '采购退货';
            
            $receive = new Erp_Model_Stock_Receive();
            $data = $receive->getData(null, $request['id'], $type_name);
            
            $items = new Erp_Model_Purchse_Receiveitems();
            $itemsData = $items->getData($request['id']);
            
            $tpl = new Erp_Model_Tpl();
            $tplHtmlData = $tpl->fetchRow("type = 'purchse_".$type."'")->toArray();
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
                        <td>'.$item['items_order_number'].'</td>
                        <td>'.$item['items_qty'].'</td>
                        <td>'.$item['items_unit'].'</td>
                        <td>'.$item['items_warehouse_code'].'</td>
                        <td>'.$item['items_remark'].'</td>
                    </tr>';
            }
            
            $orderInfo = array(
                    'title'         => $type_name,
                    'number'        => $data['number'],
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
    
    // 获取库存交易类别
    public function getstocktypelistAction()
    {
        $stock = new Erp_Model_Stock_Stock();
        
        echo Zend_Json::encode($stock->getTypeList());
        
        exit;
    }
    
    public function getqtyAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'qty'     => 0,
                'info'      => '获取成功'
        );
        
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        $code = isset($request['code']) ? $request['code'] : null;
        $warehouse_code = isset($request['warehouse_code']) ? $request['warehouse_code'] : null;
        
        if($code && $warehouse_code){
            $stock = new Erp_Model_Stock_Stock();
            $warehouse = array();
            array_push($warehouse, $warehouse_code);
        
            $qty = $stock->getStockQty($code, $warehouse);
            
            $result['qty'] = $qty['total'];
        }else{
            $result['success'] = false;
            $result['info'] = '料号/仓库为空，数量获取失败！';
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    public function getstockAction()
    {
        $condition = array();
        
        $stock = new Erp_Model_Stock_Stock();
        
        echo Zend_Json::encode($stock->getData($condition));
        
        exit;
    }
    
    public function getreceiveAction()
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
                'action_type'   => '采购收货',
                'type'      => $option
        );
        
        $data = $receive->getData($condition);
        
        if($option == 'csv'){
            $this->view->layout()->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);
        
            $h = new Application_Model_Helpers();
            $h->exportCsv($data, '采购收货');
        }else{
            echo Zend_Json::encode($data);
        }
        
        exit;
    }
    
    public function getreceiveitemsAction()
    {
        $data = array();
        
        $request = $this->getRequest()->getParams();
        
        $receive_id = isset($request['receive_id']) ? $request['receive_id'] : 0;
        
        if($receive_id > 0){
            $items = new Erp_Model_Purchse_Receiveitems();
            
            $data = $items->getData($receive_id);
        }
        
        echo Zend_Json::encode($data);
        
        exit;
    }
    
    public function editreceiveAction()
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
        $pre = isset($request['pre']) ? $request['pre'] : 'POI';
        
        $orderType = '采购收货';
        if($pre == 'POO'){
            $orderType = '采购退货';
        }
        
        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user_id = $user_session->user_info['user_id'];
        
        $receive = new Erp_Model_Stock_receive();
        
        if($type == 'new' || $type == 'edit'){
            $data = array(
                    'type'          => $orderType,
                    'date'          => $request['date'],
                    'remark'        => $request['remark'],
                    'description'   => $request['description'],
                    'update_time'   => $now,
                    'update_user'   => $user_id
            );
        
            if ($type == 'new') {
                $data['number'] = $receive->getNewNum($pre);// 生成收货单号
                $data['create_time'] = $now;
                $data['create_user'] = $user_id;
                $data['review_info'] = $now.': '.$user_session->user_info['user_name'].' [新建]';
        
                try{
                    $receive_id = $result['receive_id'] = $receive->insert($data);
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
                    $result['receive_id'] = $request['id'];
                } catch (Exception $e) {
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
                }
            }
        }elseif ($type == 'delete'){
            try {
                $receive->delete("id = ".$request['req_id']);
            } catch (Exception $e){
                $result['success'] = false;
                $result['info'] = $e->getMessage();
            }
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    /*
     * 保存收货数据分拆信息
     */
    public function saveItemDetails($splitData)
    {
        if($splitData['items_order_item_id']){
            $items = new Erp_Model_Purchse_Receiveitems();
            $itemsorder = new Erp_Model_Purchse_Receiveitemsorder();
            $rate = new Erp_Model_Setting_Currencyrate();
            $stock = new Erp_Model_Stock_Stock();
            
            // 更新是先清空之前所保存的收货数据分拆信息
            $itemsorder->delete("receive_item_id = ".$splitData['receive_item_id']);
            
            $now = date('Y-m-d H:i:s');
            $user_session = new Zend_Session_Namespace('user');
            $user_id = $user_session->user_info['user_id'];
            
            // 转换币种价格，按本币价格入库
            $price = round($splitData['items_price'] * $rate->getRateByCode($splitData['items_order_currency'], $splitData['items_order_date']), 2);
            $total = round($price * $splitData['items_qty'], 2);
            
            $data = array(
                    'receive_item_id'   => $splitData['receive_item_id'],
                    'order_item_id'     => $splitData['items_order_item_id'],
                    'qty'               => $splitData['items_qty'],
                    'price'             => $price,
                    'total'             => $total,
                    'order_id'          => $splitData['items_order_id'],
                    'order_number'      => $splitData['items_order_number'],
                    'code'              => $splitData['items_code'],
                    'create_user'       => $user_id,
                    'create_time'       => $now
            );
            
            $itemsorder->insert($data);
            
            // 更新行总计
            $items->update(array('total' => $total), "id = ".$splitData['receive_item_id']);
            
            // 记录库存数据
            $stockData = array(
                    'code'              => $splitData['items_code'],
                    'warehouse_code'    => $splitData['items_warehouse_code'],
                    'qty'               => $splitData['items_qty'],
                    'total'             => $total,
                    'create_user'       => $user_id,
                    'create_time'       => $now,
                    'doc_type'          => '采购收货',
                    'doc_number'        => $splitData['receive_number']
            );
            
            $stock->insert($stockData);
        }
    }
    
    public function refreshtotalAction()
    {
        $receive = new Erp_Model_Stock_receive();
        $items = new Erp_Model_Purchse_Receiveitems();
        
        $data = $receive->fetchAll("total = 0")->toArray();
        
        foreach ($data as $d){
            $items->refreshReceiveTotal($d['id']);
        }
        
        exit;
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
        
        $receive_id = $json->receive_id;
        
        $json_items = $json->items;
        
        $items_updated    = $json_items->updated;
        $items_inserted   = $json_items->inserted;
        $items_deleted    = $json_items->deleted;
        
        $receive = new Erp_Model_Stock_receive();
        $items = new Erp_Model_Purchse_Receiveitems();
        $itemsorder = new Erp_Model_Purchse_Receiveitemsorder();
        
        $receiveData = $receive->getData(null, $receive_id);
        
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
                        'warehouse_code'    => $val->items_warehouse_code,
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
                            'items_order_item_id'       => $val->items_order_item_id,
                            'items_order_qty'           => $val->items_order_qty,
                            'items_order_item_price'    => $val->items_order_item_price,
                            'items_order_item_currency' => $val->items_order_item_currency,
                            'items_order_item_date'     => $val->items_order_item_date,
                            'items_code'                => $val->items_code,
                            'items_warehouse_code'      => $val->items_warehouse_code,
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
        
        // 此次收货的采购订单号
        $orderNumArr = array();
        
        // 插入
        if(count($items_inserted) > 0){
            foreach ($items_inserted as $val){
                $data = array(
                        'receive_id'        => $receive_id,
                        'order_number'      => $val->items_order_number,
                        'code'              => $val->items_code,
                        'name'              => $val->items_name,
                        'description'       => $val->items_description,
                        'qty'               => $val->items_qty,
                        'price'             => $val->items_price,
                        'unit'              => $val->items_unit,
                        'warehouse_code'    => $val->items_warehouse_code,
                        'remark'            => $val->items_remark,
                        'create_user'       => $user_id,
                        'create_time'       => $now,
                        'update_user'       => $user_id,
                        'update_time'       => $now
                );
                
                // 记录此次收货的采购订单号
                if($val->items_order_number != ''){
                    $orderNumberTmp = explode(',', $val->items_order_number);
                    
                    foreach ($orderNumberTmp as $number){
                        if(!in_array($number, $orderNumArr)){
                            array_push($orderNumArr, $number);
                        }
                    }
                }
    
                try {
                    $receive_item_id = $items->insert($data);
                    
                    // 记录分拆数据
                    $itemData = array(
                            'receive_number'            => $receiveData['number'],
                            'receive_item_id'           => $receive_item_id,
                            'items_order_item_id'       => $val->items_order_item_id,
                            'items_order_currency'      => $val->items_order_currency,
                            'items_order_date'          => $val->items_order_date,
                            'items_code'                => $val->items_code,
                            'items_qty'                 => $val->items_qty,
                            'items_price'               => $val->items_price,
                            'items_warehouse_code'      => $val->items_warehouse_code,
                            'items_order_id'            => $val->items_order_id,
                            'items_order_number'        => $val->items_order_number
                    );
                    
                    $this->saveItemDetails($itemData);
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
        
        $receive_items_order = new Erp_Model_Purchse_Receiveitemsorder();
        $order = new Erp_Model_Purchse_Order();
        // 订单关联人员（申请人、审核人）
        
        $relatedUserInfo = count($orderNumArr) > 0 ? $receive_items_order->getReqRelatedUsers($orderNumArr) : array();
        
        // 到货通知
        $member = new Admin_Model_Member();
        $warehouse = new Erp_Model_Warehouse_Warehouse();
        
        $noticeTo = $member->getMemberWithManagerByName('采购到货通知');
        
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
                                <th>收货数量</th>
                                <th>单位</th>
                                <th>收货仓库</th>
                                <th>备注</th>
                                <th>备注</th>
                                <th>申请信息</th>
                                </tr>';
        
        foreach ($items_inserted as $val){
            $itemInfo = '';
        
            if($val->items_order_number){
                $itemInfoData = $order->getItemDetails($val->items_order_number, $val->items_code);
              
                $itemInfo = '<table class="gridtable"><tr>
                            <th>申请单号</th>
                            <th>申请时间</th>
                            <th>需求日期</th>
                            <th>申请数量</th>
                            <th>下单数量</th>
                            <th>订单数量</th>
                            <th>客户收件人地址简码</th>
                            <th>客户合同号</th>
                            </tr>';
              
                foreach ($itemInfoData as $d){
                    $itemInfo .= '<tr>
                                  <td>'.$d['req_number'].'</td>
                                  <td>'.$d['req_create_time'].'</td>
                                  <td>'.$d['req_item_date'].'</td>
                                  <td>'.$d['req_item_qty'].'</td>
                                  <td>'.$d['order_req_item_qty'].'</td>
                                  <td>'.$d['order_item_qty'].'</td>
                                  <td>'.$d['customer_address'].'</td>
                                  <td>'.$d['customer_aggrement'].'</td>
                                  </tr>';
                }
              
                $itemInfo .= '</table>';
            }
        
            $i++;
            
            $supplier = $val->items_order_supplier.' '.$val->items_order_supplier_name;
        
            $warehouseInfo = $warehouse->getInfoByCode($val->items_warehouse_code);
            
            if(!in_array($supplier, $supplierArr)){
                array_push($supplierArr, $supplier);
            }
            
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
        
        $mailContent = '<div>采购到货，请登录系统查看：</div>
                        <div>
                        <p><b>收货单号：</b>'.$receiveData['number'].'</p>
                        <p><b>收货日期：</b>'.$receiveData['date'].'</p>
                        <p><b>收货人：</b>'.$user_session->user_info['user_name'].'</p>
                        <p><b>描述：</b>'.$receiveData['description'].'</p>
                        <p><b>备注：</b>'.$receiveData['remark'].'</p>
                        </div><div>'.$itemsTable.'</div><hr>';
        
        $mailData = array(
                'type'      => '通知',
                'subject'   => '到货通知 - '.$receiveData['number'].' - '.implode(', ', $supplierArr),
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
    
        echo Zend_Json::encode($result);
    
        exit;
    }
    
    public function mailByHand($receive_id)
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '编辑成功'
        );
        
        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user_id = $user_session->user_info['user_id'];
        
        $receive = new Erp_Model_Stock_receive();
        $items = new Erp_Model_Purchse_Receiveitems();
        $itemsorder = new Erp_Model_Purchse_Receiveitemsorder();
        $receive_items_order = new Erp_Model_Purchse_Receiveitemsorder();
        $order = new Erp_Model_Purchse_Order();
        
        $receiveData = $receive->getData(null, $receive_id);
        
        $itemsData = $items->getData($receive_id);
        
        $orderNumArr = array();
        
        foreach ($itemsData as $item){
            $numbers = explode(',', $item['items_order_number']);
            
            foreach ($numbers as $number){
                if(!in_array($number, $orderNumArr)){
                    array_push($orderNumArr, $number);
                }
            }
        }
        
        // 订单关联人员（申请人、审核人）
        $relatedUserInfo = count($orderNumArr) > 0 ? $receive_items_order->getReqRelatedUsers($orderNumArr) : array();
        
        // 到货通知
        $member = new Admin_Model_Member();
        $warehouse = new Erp_Model_Warehouse_Warehouse();
        
        $noticeTo = $member->getMemberWithManagerByName('采购到货通知');
        
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
        $supplierIdArr = array();
        
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
                                <th>收货数量</th>
                                <th>单位</th>
                                <th>收货仓库</th>
                                <th>备注</th>
                                <th>备注</th>
                                <th>申请信息</th>
                                </tr>';
        
        foreach ($itemsData as $val){
            $itemInfo = '';
        
            if($val['items_order_number']){
                $itemInfoData = $order->getItemDetails($val['items_order_number'], $val['items_code']);
                 
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
            }
        
            $i++;
            
            $numberArr = explode(',', $val['items_order_number']);
            
            foreach ($numberArr as $n){
                $supplierData = $order->getSupplierByNumber($n);
                
                if(isset($supplierData['id']) && !in_array($supplierData['id'], $supplierIdArr)){
                    array_push($supplierArr, $supplierData['supplier_code'].' '.$supplierData['supplier_name']);
                    array_push($supplierIdArr, $supplierData['id']);
                }
            }
            
            $warehouseInfo = $warehouse->getInfoByCode($val['items_warehouse_code']);
            
            $itemsTable .= '<tr>
                            <td>'.$i.'</td>
                            <td>'.implode(', ', $supplierArr).'</td>
                            <td>'.$val['items_order_number'].'</td>
                            <td>'.$val['items_code'].'</td>
                            <td>'.$val['items_name'].'</td>
                            <td>'.$val['items_description'].'</td>
                            <td>'.$val['items_qty'].'</td>
                            <td>'.$val['items_unit'].'</td>
                            <td>'.$val['items_warehouse_code'].' '.$warehouseInfo['name'].'</td>
                            <td>'.$val['items_remark'].'</td>
                            <td>'.$itemInfo.'</td>
                            </tr>';
        }
        $itemsTable .= '</table>';
        
        $mailContent = '<div>采购到货，请登录系统查看：</div>
                        <div>
                        <p><b>收货单号：</b>'.$receiveData['number'].'</p>
                        <p><b>收货日期：</b>'.$receiveData['date'].'</p>
                        <p><b>收货人：</b>'.$user_session->user_info['user_name'].'</p>
                        <p><b>描述：</b>'.$receiveData['description'].'</p>
                        <p><b>备注：</b>'.$receiveData['remark'].'</p>
                        </div><div>'.$itemsTable.'</div><hr>';
        
        $mailData = array(
                'type'      => '通知',
                'subject'   => '到货通知 - '.$receiveData['number'].' - '.implode(', ', $supplierArr),
                'cc'        => $user_session->user_info['user_email'],
                'content'   => $mailContent,
                'add_date'  => $now,
                'to'        => implode(',', $noticeMails),
                'user_id'   => $user_id
        );
        
        try {
            // 记录邮件日志并发送邮件
            //echo '<pre>';print_r($mailData);exit;
            $mail->send($mail->insert($mailData));
        } catch (Exception $e) {
            $result['success'] = false;
            $result['info'] = $e->getMessage();
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
}