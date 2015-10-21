<?php
/**
 * 2014-4-1 下午9:47:41
 * @author x.li
 * @abstract 
 */
class Erp_Stock_OutController extends Zend_Controller_Action
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
            $data = $receive->getData(null, $request['id'], '发货');
            
            $items = new Erp_Model_Purchse_Receiveitems();
            $itemsData = $items->getData($request['id']);
            
            $tpl = new Erp_Model_Tpl();
            $tplHtmlData = $tpl->fetchRow("type = 'stock_out'")->toArray();
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
                        <td>'.$item['items_remark'].'</td>
                    </tr>';
            }
            
            $orderInfo = array(
                    'title'         => '库存交易 - 发货',
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
    
    public function getpriceAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'price'     => 0,
                'info'      => '获取成功'
        );
        
        $request = $this->getRequest()->getParams();
        
        $code = isset($request['code']) && $request['code'] != '' ? $request['code'] : null;
        $warehouse_code = isset($request['warehouse_code']) && $request['warehouse_code'] != '' ? $request['warehouse_code'] : null;
        
        if($code && $warehouse_code){
            $stock = new Erp_Model_Stock_Stock();
            
            $result['price'] = $stock->getPrice($code, $warehouse_code);
        }else{
            $result['success'] = false;
            $result['info'] = '料号/仓库为空，价格获取失败！';
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    public function getoutstockAction()
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
        
        $data = $receive->getData($condition, null, '发货');
        
        if($option == 'csv'){
            $this->view->layout()->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);
        
            $h = new Application_Model_Helpers();
            $h->exportCsv($data, '库存交易-发货');
        }else{
            echo Zend_Json::encode($data);
        }
        
        exit;
    }
    
    public function getoutstockitemsAction()
    {
        $data = array();
        
        $request = $this->getRequest()->getParams();
        
        $receive_id = isset($request['outstock_id']) ? $request['outstock_id'] : 0;
        
        if($receive_id > 0){
            $items = new Erp_Model_Purchse_Receiveitems();
            
            $data = $items->getData($receive_id);
        }
        
        echo Zend_Json::encode($data);
        
        exit;
    }
    
    public function editoutstockAction()
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
        $pre = isset($request['pre']) ? $request['pre'] : 'WO';
        
        $orderType = '发货';
        /* if($pre == 'WO'){
            $orderType = '发货';
        } */
        
        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user_id = $user_session->user_info['user_id'];
        
        $receive = new Erp_Model_Stock_receive();
        
        if($type == 'new' || $type == 'edit'){
            $data = array(
                    'type'          => $orderType,
                    'transaction_type'  => $request['transaction_type'],
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
                    $outstock_id = $result['outstock_id'] = $receive->insert($data);
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
                $receive->delete("id = ".$request['outstock_id']);
            } catch (Exception $e){
                $result['success'] = false;
                $result['info'] = $e->getMessage();
            }
        }
        
        echo Zend_Json::encode($result);
        
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
        
        $receive_id = $json->outstock_id;
        
        $json_items = $json->items;
        
        $items_updated    = $json_items->updated;
        $items_inserted   = $json_items->inserted;
        $items_deleted    = $json_items->deleted;
        
        $receive = new Erp_Model_Stock_receive();
        $items = new Erp_Model_Purchse_Receiveitems();
        $stock = new Erp_Model_Stock_Stock();
        
        $receiveData = $receive->getData(null, $receive_id, '发货');
        
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
                        'remark'            => $val->items_remark,
                        'create_user'       => $user_id,
                        'create_time'       => $now,
                        'update_user'       => $user_id,
                        'update_time'       => $now
                );
    
                try {
                    $receive_item_id = $items->insert($data);
                    
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
                            'doc_type'          => '发货',
                            'transaction_type'  => $receiveData['transaction_type'],
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
            $member = new Admin_Model_Member();
            
            $noticeMails = array();
            $noticeUsers = array();
            
            $noticeTo = $member->getMemberWithManagerByName('通知-库存交易-发货');
            
            foreach ($noticeTo as $n){
                if($n['email'] != '' && !in_array($n['user_id'], $noticeUsers)){
                    array_push($noticeMails, $n['email']);
                    array_push($noticeUsers, $n['user_id']);
                }
            }
            
            if(count($noticeMails)){
                $warehouse = new Erp_Model_Warehouse_Warehouse();
                
                $title = '库存交易-发货-'.$receiveData['transaction_type'];
                
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
                            <th>发货仓位</th>
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
                    
                    $mailContent .= '<tr>
                        <td>'.$i.'</td>
                        <td>'.$d['items_code'].'</td>
                        <td>'.$d['items_name'].'</td>
                        <td>'.$d['items_description'].'</td>
                        <td>'.$d['items_qty'].'</td>
                        <td>'.$d['items_unit'].'</td>
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