<?php
/**
 * 2014-4-1 下午9:47:41
 * @author x.li
 * @abstract 
 */
class Erp_Stock_SendController extends Zend_Controller_Action
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
            || Application_Model_User::checkPermissionByRoleName('销售人员')){
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
        
        if(isset($request['id'])){
            $type = $request['type'];
            $type_name = $type == 'send' ? '销售交货' : '销售退货';
            
            $receive = new Erp_Model_Stock_Receive();
            $data = $receive->getData(null, $request['id'], $type_name);
            $data['customer'] = '';
            
            if ($data['customer_id']) {
                $partnerModel = new Erp_Model_Partner();
                $customerInfo = $partnerModel->getInfoById($data['customer_id']);
                
                if ($customerInfo['ename'] != '') {
                    $data['customer'] = $customerInfo['code'].' '.$customerInfo['ename'];
                }else{
                    $data['customer'] = $customerInfo['code'].' '.$customerInfo['cname'];
                }
            }
            
            $items = new Erp_Model_Purchse_Receiveitems();
            $itemsData = $items->getData($request['id']);
            
            $tpl = new Erp_Model_Tpl();
            $tplHtmlData = $tpl->fetchRow("type = 'sale_send'")->toArray();
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
                        <td>'.$item['items_customer_code'].'</td>
                        <td width="150px" style="word-wrap:break-word;">'.$item['items_customer_description'].'</td>
                        <td>'.$item['items_warehouse_code'].'</td>
                        <td>'.$item['items_remark'].'</td>
                    </tr>';
            }
            
            $orderInfo = array(
                    'title'         => $type_name,
                    'number'        => $data['number'],
                    'customer'      => $data['customer'],
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
    
    public function getsendAction()
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
        
        $data = $receive->getData($condition, null, '销售交货');
        
        if($option == 'csv'){
            $this->view->layout()->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);
        
            $h = new Application_Model_Helpers();
            $h->exportCsv($data, '销售交货');
        }else{
            echo Zend_Json::encode($data);
        }
        
        exit;
    }
    
    public function getsenditemsAction()
    {
        $data = array();
        
        $request = $this->getRequest()->getParams();
        
        $send_id = isset($request['send_id']) ? $request['send_id'] : 0;
        
        if($send_id > 0){
            $items = new Erp_Model_Purchse_Receiveitems();
            
            $data = $items->getData($send_id);
        }
        
        echo Zend_Json::encode($data);
        
        exit;
    }
    
    public function editsendAction()
    {
        // 返回值数组
        $result = array(
                'success'       => true,
                'info'          => '编辑成功',
                'send_id'      => 0
        );
        
        $request = $this->getRequest()->getParams();
        
        $operate = array(
                'new'       => '新建',
                'edit'      => '编辑',
                'delete'    => '删除'
        );
        
        // 操作类别（新建、更新、删除）
        $type = isset($request['operate']) ? $request['operate'] : '';
        $pre = isset($request['pre']) ? $request['pre'] : 'SOI';
        
        $orderType = '销售交货';
        if($pre == 'SOO'){
            $orderType = '销售退货';
        }
        
        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user_id = $user_session->user_info['user_id'];
        
        $receive = new Erp_Model_Stock_receive();
        
        if($type == 'new' || $type == 'edit'){
            $data = array(
                    'type'              => $orderType,
                    'transaction_type'  => $orderType,
                    'customer_id'       => $request['customer_id'],
                    'date'              => $request['date'],
                    'remark'            => $request['remark'],
                    'description'       => $request['description'],
                    'update_time'       => $now,
                    'update_user'       => $user_id
            );
        
            if ($type == 'new') {
                $data['number'] = $receive->getNewNum($pre);// 生成交货单号
                $data['create_time'] = $now;
                $data['create_user'] = $user_id;
                $data['review_info'] = $now.': '.$user_session->user_info['user_name'].' [新建]';
        
                try{
                    $send_id = $receive->insert($data);
                    $result['send_id'] = $send_id;
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
                    $result['send_id'] = $request['id'];
                } catch (Exception $e) {
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
                }
            }
        }elseif ($type == 'delete'){
            try {
                $receive->delete("id = ".$request['send_id']);
                
                $items = new Erp_Model_Purchse_Receiveitems();
                $items->delete('receive_id = '.$request['send_id']);
            } catch (Exception $e){
                $result['success'] = false;
                $result['info'] = $e->getMessage();
            }
        }
        //echo '<pre>';print_r($result);exit;
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
            $itemsorder = new Erp_Model_Sale_Receiveitemsordersale();
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
                    'receive_item_id'       => $splitData['receive_item_id'],
                    'order_item_id'         => $splitData['items_order_item_id'],
                    'qty'                   => $splitData['items_qty'],
                    'price'                 => $price,
                    'total'                 => $total,
                    'order_id'              => $splitData['items_order_id'],
                    'order_number'          => $splitData['items_order_number'],
                    'code'                  => $splitData['items_code'],
                    'product_code'          => $splitData['items_product_code'],
                    'customer_code'         => $splitData['items_customer_code'],
                    'customer_description'  => $splitData['items_customer_description'],
                    'remark'                => $splitData['items_remark'],
                    'create_user'           => $user_id,
                    'create_time'           => $now
            );
            
            $itemsorder->insert($data);
            
            // 更新行总计
            $items->update(array('total' => $total), "id = ".$splitData['receive_item_id']);
            
            // 记录库存数据
            $stockData = array(
                    'code'              => $splitData['items_code'],
                    'product_code'      => $splitData['items_product_code'],
                    'warehouse_code'    => $splitData['items_warehouse_code'],
                    'qty'               => 0 - $splitData['items_qty'],
                    'total'             => 0 - $total,
                    'create_user'       => $user_id,
                    'create_time'       => $now,
                    'doc_type'          => '销售交货',
                    'doc_number'        => $splitData['receive_number']
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
        
        $send_id = $json->send_id;
        
        $json_items = $json->items;
        
        $items_updated    = $json_items->updated;
        $items_inserted   = $json_items->inserted;
        $items_deleted    = $json_items->deleted;
        
        $receive = new Erp_Model_Stock_receive();
        $items = new Erp_Model_Purchse_Receiveitems();
        $itemsSend = new Erp_Model_Sale_Receiveitemsordersale();
        $stock = new Erp_Model_Stock_Stock();
        
        $receiveData = $receive->getData(null, $send_id, '销售交货');
        
        // 更新
        if(count($items_updated) > 0){
            foreach ($items_updated as $val){
                $data = array(
                        'code'              => $val->items_code,
                        'name'              => $val->items_name,
                        'description'       => $val->items_description,
                        'qty'               => $val->items_qty,
                        'unit'              => $val->items_unit,
                        'customer_code'         => $val->items_customer_code,
                        'customer_description'  => $val->items_customer_description,
                        'warehouse_code'    => $val->items_warehouse_code,
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
                $code = $val->items_code_internal != '' ? $val->items_code_internal : $val->items_code;
                $product_code = $val->items_code_internal != '' ? $val->items_code : '';
                
                $data = array(
                        'receive_id'            => $send_id,
                        'code'                  => $code,
                        'product_code'          => $product_code,
                        'name'                  => $val->items_name,
                        'description'           => $val->items_description,
                        'qty'                   => $val->items_qty,
                        'price'                 => $val->items_price,
                        'total'                 => $total,
                        'unit'                  => $val->items_unit,
                        'customer_code'         => $val->items_customer_code,
                        'customer_description'  => $val->items_customer_description,
                        'warehouse_code'        => $val->items_warehouse_code,
                        'remark'                => $val->items_remark,
                        'order_number'          => $val->items_order_number,
                        'create_user'           => $user_id,
                        'create_time'           => $now,
                        'update_user'           => $user_id,
                        'update_time'           => $now
                );
    
                try {
                    $receive_item_id = $items->insert($data);
                    
                    // 记录分拆数据
                    $itemData = array(
                            'receive_number'            => $receiveData['number'],
                            'receive_item_id'           => $receive_item_id,
                            'items_order_item_id'       => $val->items_order_item_id,
                            'items_order_currency'      => $val->items_order_currency,
                            'items_order_date'          => $val->items_order_date,
                            'items_code'                => $code,
                            'items_product_code'        => $product_code,
                            'items_customer_code'       => $val->items_customer_code,
                            'items_customer_description'=> $val->items_customer_description,
                            'items_remark'              => $val->items_remark,
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
        $items->refreshReceiveTotal($send_id);
        
        if($result['success']){
            $member = new Admin_Model_Member();
            
            $noticeMails = array();
            $noticeUsers = array();
            
            $noticeTo = $member->getMemberWithManagerByName('通知-销售交货');
            
            foreach ($noticeTo as $n){
                if($n['email'] != '' && !in_array($n['user_id'], $noticeUsers)){
                    array_push($noticeMails, $n['email']);
                    array_push($noticeUsers, $n['user_id']);
                }
            }
            
            if(count($noticeMails)){
                $warehouse = new Erp_Model_Warehouse_Warehouse();
                
                $title = '销售交货-'.$receiveData['transaction_type'];
                
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
                            <th>内部型号 / 物料号</th>
                            <th>名称</th>
                            <th>描述</th>
                            <th>数量</th>
                            <th>单位</th>
                            <th>客户产品型号</th>
                            <th>客户产品描述</th>
                            <th>交货仓位</th>
                            <th>备注</th>
                            </tr>';
                
                $itemsData = $items->getData($send_id);
                $i = 0;
                foreach ($itemsData as $d){
                    $i++;
                    
                    $warehouseData = $warehouse->getInfoByCode($d['items_warehouse_code']);
                    $warehouseInfo = $warehouseData["code"];
                    
                    if(isset($warehouseData["name"])){
                        $warehouseInfo = $warehouseData["code"].' '.$warehouseData["name"];
                    }
                    
                    $mailContent .= '<tr>
                        <td>'.$i.'</td>
                        <td>'.$d['items_code'].'</td>
                        <td>'.$d['items_name'].'</td>
                        <td>'.$d['items_description'].'</td>
                        <td>'.$d['items_qty'].'</td>
                        <td>'.$d['items_unit'].'</td>
                        <td>'.$d['items_customer_code'].'</td>
                        <td>'.$d['items_customer_description'].'</td>
                        <td>'.$warehouseInfo.'</td>
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
    
        echo Zend_Json::encode($result);
    
        exit;
    }
}