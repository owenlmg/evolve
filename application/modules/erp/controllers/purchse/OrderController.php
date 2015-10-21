<?php
/**
 * 2014-2-15 下午3:51:09
 * @author x.li
 * @abstract 
 */
class Erp_Purchse_OrderController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $user_session = new Zend_Session_Namespace('user');
        
        $this->view->buyerFunctionDisable = 1;
        $this->view->accessViewOrder = 0;
        
        $this->view->user_id = 0;
        
        if(isset($user_session->user_info)){
            $this->view->user_id = $user_session->user_info['user_id'];
        
            if(Application_Model_User::checkPermissionByRoleName('系统管理员') || Application_Model_User::checkPermissionByRoleName('采购人员')){
                $this->view->buyerFunctionDisable = 0;
                $this->view->accessViewOrder = 1;
            }else if(Application_Model_User::checkPermissionByRoleName('采购订单明细查看')){
                $this->view->accessViewOrder = 1;
            }
        }
    }
    
    public function checkcanbedeleteAction()
    {
        $result = array(
                'success'   => true,
                'info'      => ''
        );
        
        $request = $this->getRequest()->getParams();
        
        $order_id = isset($request['order_id']) ? $request['order_id'] : 0;
        
        if($order_id > 0){
            $items = new Erp_Model_Purchse_Orderitems();
        
            $data = $items->getData($order_id);
            
            foreach ($data as $d){
                if ($d['items_qty_receive'] > 0) {
                    $result['success'] = false;
                    $result['info'] = $d['items_code'].'已收货'.$d['items_qty_receive'];
                    break;
                }
            }
        }else{
            $result['success'] = false;
            $result['info'] = '参数错误: 订单ID为空';
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    public function deleteAction()
    {
        $result = array(
                'success'   => true,
                'info'      => '删除成功！'
        );
        
        $request = $this->getRequest()->getParams();
        
        $id = isset($request['id']) ? $request['id'] : null;
        
        if($id){
            $now = date('Y-m-d H:i:s');
            $user_session = new Zend_Session_Namespace('user');
            $user_id = $user_session->user_info['user_id'];
            
            $order = new Erp_Model_Purchse_Order();
            $order->update(array('active' => 0, 'deleted' => 1, 'update_user' => $user_id, 'update_time' => $now), "deleted = 0 and id = ".$id);
            
            // 更新订单项状态(申请信息：用于扣减下单数量)
            $orderItems = new Erp_Model_Purchse_Orderitems();
            $dataRes = $orderItems->fetchAll("order_id = ".$id);
            if($dataRes->count() > 0){
                $itemsData = $dataRes->toArray();
                $orderItemsReq = new Erp_Model_Purchse_Orderitemsreq();
                
                foreach ($itemsData as $d){
                    $orderItemsReq->update(array('active' => 0), "order_item_id = ".$d['id']);
                }
            }
            
            $orderItems->update(array('active' => 0), "order_id = ".$id);
        }else{
            $result['success'] = false;
            $result['info'] = '订单号错误，删除失败！';
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    /**
     * 根据申购单ID获取项目列表
     */
    public function getordertransferitemsAction()
    {
        $data = array();
    
        $request = $this->getRequest()->getParams();
    
        $transfer_id = isset($request['transfer_id']) ? $request['transfer_id'] : 0;
    
        if($transfer_id > 0){
            $items = new Erp_Model_Purchse_Transferorderitems();
    
            $data = $items->getData($transfer_id);
        }
    
        echo Zend_Json::encode($data);
    
        exit;
    }
    
    public function editattachAction()
    {
        $result = array(
                'success'   => true,
                'info'      => '上传成功'
        );
        
        $request = $this->getRequest()->getParams();
        
        $order_id = isset($request['order_id']) ? $request['order_id'] : null;
        //$remark = isset($request['attach_remark']) ? $request['attach_remark'] : null;
        
        if($order_id && isset($_FILES['attach_file'])){
            $now = date('Y-m-d H:i:s');
            $user_session = new Zend_Session_Namespace('user');
            $user_id = $user_session->user_info['user_id'];
            
            $file = $_FILES['attach_file'];
            
            $file_name = $file['name'];
            $file_extension = strrchr($file_name, ".");
            
            $h = new Application_Model_Helpers();
            $tmp_file_name = $h->getMicrotimeStr().$file_extension;
            
            $savepath = "../upload/files/".date('Y-m-d').'/';
            
            if(!is_dir($savepath)){
                mkdir($savepath);// 目录不存在则创建目录
            }
            
            $tmp_file_path = $savepath.$tmp_file_name;
            move_uploaded_file($file["tmp_name"], $tmp_file_path);
            
            $order = new Erp_Model_Purchse_Order();
            $orderData = $order->getData(null, $order_id);
            
            if($orderData['attach_path'] != '' && file_exists($orderData['attach_path'])){
                unlink($orderData['attach_path']);
            }
            
            $orderData = array(
                    'attach_name'   => $file_name,
                    'attach_path'   => $tmp_file_path,
                    //'remark'        => $remark,
                    'update_user'   => $user_id,
                    'update_time'   => $now
            );
            
            $order->update($orderData, "id = ".$order_id);
        }
    
        echo Zend_Json::encode($result);
    
        exit;
    }
    
    // 获取未开票订单
    public function getinvoiceorderlistAction()
    {
        $request = $this->getRequest()->getParams();
        
        $key = isset($request['key']) && $request['key'] != '' ? $request['key'] : null;
        $supplier_id = isset($request['supplier_id']) ? $request['supplier_id'] : null;
        $currency = isset($request['currency']) ? $request['currency'] : null;
        
        $order = new Erp_Model_Purchse_Order();
        
        echo Zend_Json::encode($order->getInvoiceOrderItemsList($supplier_id, $currency, $key));
        
        exit;
    }
    
    // 获取未清订单
    public function getopenorderlistAction()
    {
        $request = $this->getRequest()->getParams();
        
        $key = isset($request['key']) && $request['key'] != '' ? $request['key'] : null;
        
        $order = new Erp_Model_Purchse_Order();
        
        echo Zend_Json::encode($order->getOrderItemsList($key));
        
        exit;
    }
    
    public function editbuyerAction()
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
    
        $updated    = $json->updated;
        $inserted   = $json->inserted;
        $deleted    = $json->deleted;
    
        $buyer = new Erp_Model_Purchse_Buyer();
        $work = new Erp_Model_Purchse_Buyerwork();
    
        if(count($updated) > 0){
            foreach ($updated as $val){
                $data = array(
                        'active'        => $val->active,
                        'user_id'       => $val->user_id,
                        'tel'           => $val->tel,
                        'fax'           => $val->fax,
                        'remark'        => $val->remark,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
    
                if($buyer->fetchAll("id != ".$val->id." and user_id = ".$val->user_id)->count() > 0){
                    $result['success'] = false;
                    $result['info'] = "采购员已存在，请勿重复添加！";
                }else{
                    try {
                        $buyer->update($data, "id = ".$val->id);
                        
                        $work->delete("buyer_id = ".$val->id);
                        
                        if ($val->type) {
                            foreach ($val->type as $type_id){
                                $work->insert(array('buyer_id' => $val->id, 'type_id' => $type_id));
                            }
                        }
                    } catch (Exception $e) {
                        $result['success'] = false;
                        $result['info'] = $e->getMessage();
    
                        echo Zend_Json::encode($result);
    
                        exit;
                    }
                }
            }
        }
    
        if(count($inserted) > 0){
            foreach ($inserted as $val){
                $data = array(
                        'active'        => $val->active,
                        'user_id'       => $val->user_id,
                        'tel'           => $val->tel,
                        'fax'           => $val->fax,
                        'remark'        => $val->remark,
                        'create_time'   => $now,
                        'create_user'   => $user_id,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
    
                if($buyer->fetchAll("user_id = ".$user_id)->count() > 0){
                    $result['success'] = false;
                    $result['info'] = "采购员已存在，请勿重复添加！";
                }else{
                    try{
                        $buyer_id = $buyer->insert($data);
                        
                        if ($val->type) {
                            foreach ($val->type as $type_id){
                                $work->insert(array('buyer_id' => $buyer_id, 'type_id' => $type_id));
                            }
                        }
                    } catch (Exception $e){
                        $result['success'] = false;
                        $result['info'] = $e->getMessage();
    
                        echo Zend_Json::encode($result);
    
                        exit;
                    }
                }
            }
        }
    
        if(count($deleted) > 0){
            foreach ($deleted as $val){
                try {
                    $buyer->delete("id = ".$val->id);
                    
                    $work->delete("buyer_id = ".$val->id);
                } catch (Exception $e){
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
                
                    echo Zend_Json::encode($result);
                
                    exit;
                }
            }
        }
    
        echo Zend_Json::encode($result);
    
        exit;
    }
    
    public function getbuyerAction()
    {
        // 请求参数
        $request = $this->getRequest()->getParams();
    
        $option = isset($request['option']) ? $request['option'] : 'list';
    
        $buyer = new Erp_Model_Purchse_Buyer();
    
        if($option == 'list'){
            echo Zend_Json::encode($buyer->getList());
        }else{
            echo Zend_Json::encode($buyer->getData());
        }
    
        exit;
    }
    
    public function getmaterialtypelistAction()
    {
        $type = new Product_Model_Type();
        
        echo Zend_Json::encode($type->getTypeList());
        
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
        
        if(isset($request['id']) && isset($request['tpl_id'])){
            $order = new Erp_Model_Purchse_Order();
            $buyer = new Erp_Model_Purchse_Buyer();
            $items = new Erp_Model_Purchse_Orderitems();
            $helper = new Application_Model_Helpers();
            $supplier_contact = new Erp_Model_Contact();
            $tax = new Erp_Model_Setting_Tax();
            $currency = new Erp_Model_Setting_Currency();
            
            $orderData = $order->getData(null, $request['id']);
            $itemsData = $items->getData($request['id']);
            $buyerData = $buyer->getData($orderData['buyer_id']);
            $supplierContactData = $supplier_contact->getData($orderData['supplier_contact_id']);
            $taxData = $tax->getData($orderData['supplier_tax_id']);
            $currencyData = $currency->getInfoByCode($orderData['currency']);
            
            $tpl = new Erp_Model_Tpl();
            $tplHtmlData = $tpl->fetchRow("id = ".$request['tpl_id'])->toArray();
            $tplHtml = $tplHtmlData['html'];
            
            $itemsHtml = '';
            $itemsHtml_en = '';
            $itemsHtml_other = '';
            $i = 0;
            foreach ($itemsData as $item){
                $i++;
                
                $req_number = str_replace(',', '<br>', $item['items_req_number']);
                
                $itemsHtml .= '
                    <tr>
                        <td>'.$i.'</td>
                        <td>'.$item['items_code'].'</td>
                        <td width="100px">'.$item['items_name'].'</td>
                        <td width="150px">'.$item['items_description'].'</td>
                        <td>'.$item['items_qty'].'</td>
                        <td>'.$item['items_unit'].'</td>
                        <td>'.$currencyData['symbol'].$item['items_price'].'</td>
                        <td>'.$currencyData['symbol'].$item['items_total'].'</td>
                        <td>'.$req_number.'</td>
                        <td>'.$item['items_request_date'].'</td>
                        <td>'.$item['items_remark'].'</td>
                    </tr>';
                
                $itemsHtml_en .= '
                    <tr>
                        <td>'.$i.'</td>
                        <td>'.$item['items_code'].'</td>
                        <td width="100px" style="word-wrap:break-word;">'.$item['items_name'].'</td>
                        <td width="150px" style="word-wrap:break-word;">'.$item['items_description'].'</td>
                        <td>'.$item['items_qty'].'</td>
                        <td>'.$item['items_unit'].'</td>
                        <td>'.$currencyData['symbol'].$item['items_price'].'</td>
                        <td>'.$currencyData['symbol'].$item['items_total'].'</td>
                        <td>'.$req_number.'</td>
                        <td>'.$item['items_request_date'].'</td>
                        <td>'.$item['items_remark'].'</td>
                    </tr>';
                
                $itemsHtml_other .= '
                    <tr>
                        <td>'.$i.'</td>
                        <td>'.$item['items_code'].'</td>
                        <td width="250px" style="word-wrap:break-word;">'.$item['items_name'].' ['.$item['items_description'].']</td>
                        <td>'.$item['items_qty'].'</td>
                        <td>'.$item['items_unit'].'</td>
                        <td>'.$currencyData['symbol'].$item['items_price'].'</td>
                        <td>'.$currencyData['symbol'].$item['items_total'].'</td>
                        <td>'.$req_number.'</td>
                        <td>'.$item['items_request_date'].'</td>
                        <td>'.$item['items_remark'].'</td>
                    </tr>';
            }
            
            $receive = array();
            
            if($orderData['receiver_id']){
                // 公司收货人
                $receiver = new Erp_Model_Warehouse_Warehousereceiver();
                $receiverData = $receiver->getData($orderData['receiver_id']);
                
                $receive['address'] = $receiverData['address'];
                $receive['address_en'] = $receiverData['address_en'];
                $receive['cname'] = $receiverData['cname'];
                $receive['ename'] = $receiverData['ename'];
                $receive['tel'] = $receiverData['tel'];
                $receive['fax'] = $receiverData['fax'];
            }else{
                // 客户收货人
                $receiverData = $supplier_contact->getDataByCode($orderData['customer_address_code']);
                
                $receive['address'] = $receiverData['address'];
                $receive['address_en'] = $receiverData['address'];
                $receive['cname'] = $receiverData['name'];
                $receive['ename'] = $receiverData['name'];
                $receive['tel'] = $receiverData['tel'];
                $receive['fax'] = $receiverData['fax'];
            }
            
            $orderInfo = array(
                    'order_symbol'          => $currencyData['symbol'],
                    'company_name'          => '成都欧飞凌通讯技术有限公司',
                    'company_tel'           => '028-85161178',
                    'company_fax'           => '028-85161176',
                    'company_address'       => '成都市高新区云华路333号1-5栋 3层',
                    'company_logo'          => HOME_PATH.'/public/images/company.png',
                    'company_tax'           => '510198698882411',
                    'company_account'       => '128904731510811',
                    'company_bank'          => '招商银行成都分行高新支行',
                    'order_title'           => '采 购 合 同',
                    'type'                  => ' - 物料原材料',
                    'number'                => $orderData['number'],
                    'date'                  => date('Y-m-d', strtotime($orderData['create_time'])),
                    'contact_name'          => $buyerData['cname'],
                    'contact_ename'         => $buyerData['ename'],
                    'contact_tel'           => $buyerData['tel'],
                    'contact_fax'           => $buyerData['fax'],
                    'supplier_cname'        => $orderData['supplier_cname'],
                    'supplier_ename'        => $orderData['supplier_ename'],
                    'supplier_code'         => $orderData['supplier_code'],
                    'supplier_bank_type'    => $orderData['supplier_bank_type'],
                    'supplier_bank_account' => $orderData['supplier_bank_account'],
                    'supplier_address'      => $supplierContactData['address'],
                    'supplier_contact_name' => $supplierContactData['name'],
                    'supplier_contact_tel'  => $supplierContactData['tel'],
                    'supplier_contact_fax'  => $supplierContactData['fax'],
                    'receive_address'       => $receive['address'],
                    'receive_address_en'    => $receive['address_en'],
                    'receive_cname'         => $receive['cname'],
                    'receive_ename'         => $receive['ename'],
                    'receive_tel'           => $receive['tel'],
                    'receive_fax'           => $receive['fax'],
                    'total_upper'           => $helper->num2rmb($orderData['total']),
                    'total_lower'           => $orderData['currency_rate'] != 1 ? $orderData['forein_total'] : $orderData['total'],
                    'supplier_tax'          => $orderData['supplier_tax_num'],
                    'supplier_tax_name'     => $taxData['name'],
                    'supplier_tax_rate'     => $taxData['rate'],
                    'supplier_payment_days' => $orderData['supplier_payment'],
                    'settle_way'            => $orderData['settle_way'],
                    'delvery_clause'        => $orderData['delvery_clause'],
                    'manufacture'            => $orderData['manufacture'],
                    'responsible'            => $orderData['responsible'],
                    'table'                 => $itemsHtml,
                    'table_en'              => $itemsHtml_en,
                    'table_other'           => $itemsHtml_other,//采购项清单（其它）
                    'order_other_info'      => $orderData['remark'],
                    'responsible'            => $orderData['responsible'],
                    'order_title_en'        => 'Purchase Order',
                    'order_title_other'     => '设 备 类 采 购 合 同',
                    'company_name_en'       => 'OPhylink Communication Technology Inc.'
            );
            
            foreach ($orderInfo as $key => $val){
                $tplHtml = str_replace('<tpl_'.$key.'>', $val, $tplHtml);
            }
            
            $result['info'] = $tplHtml;
        }else{
            $result['success'] = false;
        }
        /* echo $tplHtml;
        exit; */
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    function cancelOrder($id)
    {
        $result = array(
                'success'   => true,
                'info'      => '取消成功'
        );
        
        $order = new Erp_Model_Purchse_Order();
        $orderData = $order->getData(null, $id);
        
        // 取消订单：如当前订单状态为被拒绝，则直接取消，否则检查是否存在收货订单项
        if($orderData['state'] == 1){
            $order->cancelOrderById($id);
        }else{
            $receiveItems = new Erp_Model_Purchse_Receiveitems();
            $items = $receiveItems->getReceivedOrderItems($orderData['number']);
             
            if(count($items)){
                $codeArr = array();
                 
                foreach ($items as $item){
                    array_push($codeArr, $item['code']);
                }
                 
                $result['success'] = false;
                $result['info'] = '取消失败，['.implode(',', $codeArr).'] 已收货！';
            }else{
                $order->cancelOrderById($id);
            }
        }
        
        return $result;
    }
    
    // 取消采购订单
    public function cancelAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '取消成功'
        );
        
        $request = $this->getRequest()->getParams();
        
        if(isset($request['id'])){
            $result = $this->cancelOrder($request['id']);
        }else{
            $result['success'] = false;
            $result['info'] = "ID为空，操作失败！";
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    public function approveTransferUpdateItems($transfer_id)
    {
        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user_id = $user_session->user_info['user_id'];
        
        $transfer_items = new Erp_Model_Purchse_Transferorderitems();
        
        $order_items = new Erp_Model_Purchse_Orderitems();
        $order_req_items = new Erp_Model_Purchse_Orderitemsreq();
        
        $items = $transfer_items->getData($transfer_id);
        
        $order_id = null;
        
        foreach ($items as $item){
            $active = $item['items_active'] ? 1 : 0;
            $order_id = $item['items_order_id'];
            
            $data = array(
                    'order_id'              => $order_id,
                    'active'                => $active,
                    'req_number'            => $item['items_req_number'],
                    'code'                  => $item['items_code'],
                    'name'                  => $item['items_name'],
                    'description'           => $item['items_description'],
                    'supplier_code'         => $item['items_supplier_code'],
                    'supplier_codename'     => $item['items_supplier_codename'],
                    'supplier_description'  => $item['items_supplier_description'],
                    'qty'                   => $item['items_qty'],
                    'unit'                  => $item['items_unit'],
                    'price'                 => $item['items_price'],
                    'total'                 => $item['items_qty'] * $item['items_price'],
                    'warehouse_code'        => $item['items_warehouse_code'],
                    'request_date'          => $item['items_request_date'],
                    'dept_id'               => $item['items_dept_id'],
                    'project_info'          => $item['items_project_info'],
                    'remark'                => $item['items_remark'],
                    'update_user'           => $user_id,
                    'update_time'           => $now
            );
            
            if($item['items_transfer_type'] == 'add'){
                $data['create_user'] = $user_id;
                $data['create_time'] = $now;
                
                $order_item_id = $order_items->insert($data);
                
                $this->saveSplitItem('insert', $order_item_id, $item['items_req_item_id'], $item['items_req_qty'], $item['items_code']);
            }else if($item['items_transfer_type'] == 'delete'){
                $order_items->delete("id = ".$item['items_order_item_id']);
                $order_req_items->delete("order_item_id = ".$item['items_order_item_id']);
            }else if($item['items_transfer_type'] == 'update'){
                $order_items->update($data, "id = ".$item['items_order_item_id']);
                
                $this->saveSplitItem('update', $item['items_order_item_id'], $item['items_req_item_id'], $item['items_qty'], $item['items_code'], $active);
            }
        }
        
        if($order_id){
            $order_items->refreshOrderTotal($order_id);
        }
    }
    
    // 审核采购申请
    public function reviewAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '审核成功'
        );
        
        $request = $this->getRequest()->getParams();
        /* echo '<pre>';
        print_r($request);
        exit; */
        $review_id = isset($request['review_id']) ? $request['review_id'] : null;
        //$review_type_id = isset($request['review_type_id']) ? $request['review_type_id'] : null;
        $review_operate = isset($request['review_operate']) ? $request['review_operate'] : null;
        $review_current_step = isset($request['review_current_step']) ? $request['review_current_step'] : null;// 当前阶段（review表ID）
        $review_last_step = isset($request['review_last_step']) ? $request['review_last_step'] : null;// 是否当前阶段为最后一阶段
        $review_to_finish = isset($request['review_to_finish']) ? $request['review_to_finish'] : null;// 是否批准后当前阶段结束
        $review_next_step = isset($request['review_next_step']) ? $request['review_next_step'] : null;// 下一阶段（review表ID）
        $review_remark = isset($request['review_remark']) ? $request['review_remark'] : null;
        $review_transfer = $request['review_transfer'] == 1 ? true : false;
        
        if($review_id && $review_operate){
            $transfer = new Erp_Model_Purchse_Transfer();
            
            $now = date('Y-m-d H:i:s');
            $user_session = new Zend_Session_Namespace('user');
            $user_id = $user_session->user_info['user_id'];
            
            // 评审意见
            $review_info = '意见: '.$review_remark;
            
            $order = new Erp_Model_Purchse_Order();
            $user = new Application_Model_User();
            $review = new Dcc_Model_Review();
            $employee = new Hra_Model_Employee();
            
            $orderData = $order->getData(null, $review_id);
            
            // 更新审核状态及审核意见
            if($review_operate == 'no'){
                // 更新采购申请状态
                $review_info = $now.': '.$user_session->user_info['user_name'].' [审核-拒绝] ['.$review_info.']';
                $data = array(
                        'state'                 => 1,
                        'transfer_description'    => null,
                        'review_info'           => $orderData['review_info'].'<br>'.$review_info
                );
                
                // 更新订单状态
                $order->update($data, "id = ".$review_id);
                
                if($review_transfer){
                    $transfer->update(array('state' => 1), "id = ".$orderData['transfer_id']);
                }
                
                // 删除当前申请的审核配置
                $review->delete("type = 'purchse_order_add' and file_id = ".$review_id);
                
                // 发送邮件通知采购员
                $reviewResult = '<font style="color: #FF0000"><b>拒绝</b></font>';
                
                $mail = new Application_Model_Log_Mail();
                
                $applyEmployeeData = $user->fetchRow("id = ".$orderData['create_user'])->toArray();
                $applyEmployee = $employee->fetchRow("id = ".$applyEmployeeData['employee_id'])->toArray();
                $to = $applyEmployee['email'];
                
                // 获取币种信息
                $currency = new Erp_Model_Setting_Currency();
                $currencyInfo = $currency->getInfoByCode($orderData['currency']);
                
                $total = $orderData['total'];
                if($orderData['currency_rate'] != 1){
                    $total = $orderData['forein_total'];
                }
                
                $mailContent = '<div>采购订单审核：</div>
                                <div>
                                <p><b>审核人：</b>'.$user_session->user_info['user_name'].'</p>
                                <p><b>审核结果：</b>'.$reviewResult.'</p>
                                <p><b>审核意见：</b>'.$review_remark.'</p>
                                <p><b>订单号：</b>'.$orderData['number'].'</p>
                                <p><b>采购员：</b>'.$orderData['creater'].'</p>
                                <p><b>类别：</b>'.$orderData['type'].'</p>
                                <p><b>金额：</b><a style="color: #467500;font-weight: bold;">'.$currencyInfo['symbol'].$total.'</a></p>
                                <p><b>备注：</b>'.$orderData['remark'].'</p>
                                <p><b>申请时间：</b>'.$orderData['create_time'].'</p>
                                <p><b>更新时间：</b>'.$orderData['update_time'].'</p>
                                <hr>
                                <p><b>审核日志：</b></p><p>'.$orderData['review_info'].'</p>
                                </div>';
                
                $mailData = array(
                        'type'      => '消息',
                        'subject'   => '采购订单-审核',
                        'to'        => $to,
                        'cc'        => $user_session->user_info['user_email'],
                        'user_id'   => $orderData['create_user'],
                        'content'   => $mailContent,
                        'add_date'  => $now
                );
                
                try {
                    // 记录邮件日志并发送邮件
                    $mail->send($mail->insert($mailData));
                } catch (Exception $e) {
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
                }
            }else{
                $help = new Application_Model_Helpers();
                
                $review_info = $now.': '.$user_session->user_info['user_name'].' [审核-批准] ['.$review_info.']';
                $orderUpdateData = array(
                        'review_info'   => $orderData['review_info'].'<br>'.$review_info
                );
                
                $reviewData = $review->fetchRow("id = ".$review_current_step)->toArray();
                
                $actual_user = $reviewData['actual_user'] == '' ? $user_session->user_info['employee_id'] : $reviewData['actual_user'].','.$user_session->user_info['employee_id'];
                
                $data = array(
                        'actual_user'   => $actual_user,
                        'finish_time'   => $now,
                        'finish_flg'    => 1
                );
                
                // 当前审核阶段为最后一阶段
                if($review_last_step == 1){
                    // 当前阶段已完结
                    if($review_to_finish == 1){
                        // 订单变更
                        if($review_transfer){
                            if($orderData['transfer_type'] == '取消'){
                                $this->cancelOrder($review_id);
                            }else{
                                $this->approveTransferUpdateItems($orderData['transfer_id']);
                            }
                        
                            $transfer->update(array('state' => 2), "id = ".$orderData['transfer_id']);
                        }
                        
                        $data = array(
                                'actual_user'   => $actual_user,
                                'finish_time'   => $now,
                                'finish_flg'    => 1
                        );
                        
                        $reviewResult = '<font style="color: #006400"><b>发布</b></font>';
                        
                        // 发布
                        
                        // 更新申请状态
                        $orderUpdateData['state'] = 2;
                        $orderUpdateData['transfer_description'] = null;
                        $orderUpdateData['release_time'] = $now;
                        
                        // 更新审核记录表
                        $review->update($data, "id = ".$review_current_step);
                        
                        $mail = new Application_Model_Log_Mail();
                        
                        $applyEmployeeData = $user->fetchRow("id = ".$orderData['create_user'])->toArray();
                        $applyEmployee = $employee->fetchRow("id = ".$applyEmployeeData['employee_id'])->toArray();
                        $to = $applyEmployee['email'];
                        
                        // 获取币种信息
                        $currency = new Erp_Model_Setting_Currency();
                        $currencyInfo = $currency->getInfoByCode($orderData['currency']);
                        
                        $total = $orderData['total'];
                        if($orderData['currency_rate'] != 1){
                            $total = $orderData['forein_total'];
                        }
                        
                        $mailContent = '<div>采购订单审核批准，请登录系统查看：</div>
                                <div>
                                <p><b>审核人：</b>'.$user_session->user_info['user_name'].'</p>
                                <p><b>审核结果：</b>'.$reviewResult.'</p>
                                <p><b>审核意见：</b>'.$review_remark.'</p>
                                <p><b>订单号：</b>'.$orderData['number'].'</p>
                                <p><b>采购员：</b>'.$orderData['creater'].'</p>
                                <p><b>类别：</b>'.$orderData['type'].'</p>
                                <p><b>金额：</b><a style="color: #467500;font-weight: bold;">'.$currencyInfo['symbol'].$total.'</a></p>
                                <p><b>备注：</b>'.$orderData['remark'].'</p>
                                <p><b>申请时间：</b>'.$orderData['create_time'].'</p>
                                <p><b>更新时间：</b>'.$orderData['update_time'].'</p>
                                <hr>
                                <p><b>审核日志：</b></p><p>'.$orderUpdateData['review_info'].'</p>
                                </div>';
                        
                        $mailData = array(
                                'type'      => '消息',
                                'subject'   => '采购订单-发布',
                                'to'        => $to,
                                'cc'        => $user_session->user_info['user_email'],
                                'user_id'   => $orderData['create_user'],
                                'content'   => $mailContent,
                                'add_date'  => $now
                        );
                        
                        try {
                            // 记录邮件日志并发送邮件
                            $mail->send($mail->insert($mailData));
                        } catch (Exception $e) {
                            $result['success'] = false;
                            $result['info'] = $e->getMessage();
                        }
                    }else{
                        $data = array(
                                'actual_user'   => $actual_user
                        );
                        
                        $review->update($data, "id = ".$review_current_step);
                        
                        // 等待其他审核人批准
                        
                    }
                }else{
                    // 当前阶段已完结
                    if($review_to_finish == 1){
                        $data = array(
                                'actual_user'   => $actual_user,
                                'finish_time'   => $now,
                                'finish_flg'    => 1
                        );
                        
                        $reviewResult = '<font style="color: #006400"><b>批准</b></font>';
                        
                        // 进入下一阶段
                        
                        // 更新审核记录表
                        $review->update($data, "id = ".$review_current_step);
                        
                        // 邮件通知下一阶段审核人
                        if($review->fetchAll("id = ".$review_next_step)->count() > 0){
                            $reviewNextStepData = $review->fetchRow("id = ".$review_next_step)->toArray();
                            
                            $mailTo = explode(',', $reviewNextStepData['plan_user']);
                            
                            if($mailTo){
                                // 获取币种信息
                                $currency = new Erp_Model_Setting_Currency();
                                $currencyInfo = $currency->getInfoByCode($orderData['currency']);
                                
                                $total = $orderData['total'];
                                if($orderData['currency_rate'] != 1){
                                    $total = $orderData['forein_total'];
                                }
                                
                                $mailContent = '<div>新建采购订单，请登录系统查看：</div>
                                                <div>
                                                <p><b>订单号：</b>'.$orderData['number'].'</p>
                                                <p><b>采购员：</b>'.$orderData['creater'].'</p>
                                                <p><b>类别：</b>'.$orderData['type'].'</p>
                                                <p><b>金额：</b><a style="color: #467500;font-weight: bold;">'.$currencyInfo['symbol'].$total.'</a></p>
                                                <p><b>备注：</b>'.$orderData['remark'].'</p>
                                                <p><b>申请时间：</b>'.$orderData['create_time'].'</p>
                                                <p><b>更新时间：</b>'.$orderData['update_time'].'</p>
                                                <hr>
                                                <p><b>审核日志：</b></p><p>'.$orderUpdateData['review_info'].'</p>
                                                </div>';
                                
                                $mailData = array(
                                        'type'      => '消息',
                                        'subject'   => '采购订单-新订单',
                                        'cc'        => $user_session->user_info['user_email'],
                                        'content'   => $mailContent,
                                        'add_date'  => $now
                                );
                                
                                $resultMail = $help->sendMailToStep($mailTo, $mailData);
                                
                                if(!$resultMail['success']){
                                    $result = $resultMail;
                                }
                            }
                        }
                    }else{
                        $data = array(
                                'actual_user'   => $actual_user
                        );
                        
                        $review->update($data, "id = ".$review_current_step);
                    }
                }
                
                // 更新申请状态
                $order->update($orderUpdateData, "id = ".$review_id);
            }
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }

    /**
     * 根据采购订单ID获取项目列表
     */
    public function getorderitemsAction()
    {
        $data = array();
        
        $request = $this->getRequest()->getParams();
        
        $order_id = isset($request['order_id']) ? $request['order_id'] : 0;
        
        if($order_id > 0){
            $items = new Erp_Model_Purchse_Orderitems();
            
            $data = $items->getData($order_id);
        }
        
        echo Zend_Json::encode($data);
        
        exit;
    }
    
    // 获取流程列表
    public function getflowlistAction()
    {
        $flow = new Admin_Model_Flow();
        
        $data = array();
        
        $dataFlow = $flow->fetchAll("state = 1", array('CONVERT( flow_name USING gbk )'))->toArray();
        
        for($i = 0; $i < count($dataFlow); $i++){
            $data[$i]['id'] = $dataFlow[$i]['id'];
            $data[$i]['name'] = $dataFlow[$i]['flow_name'];
        }
        
        echo Zend_Json::encode($data);
        
        exit;
    }
    
    public function getorderlistAction()
    {
        $order = new Erp_Model_Purchse_Order();
        
        echo Zend_Json::encode($order->getList());
        
        exit;
    }
    
    /**
     * 获取采购申请列表
     */
    public function getorderAction()
    {
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        $option = isset($request['option']) ? $request['option'] : 'data';
        
        $order = new Erp_Model_Purchse_Order();
        
        if($option == 'list'){
            echo Zend_Json::encode($order->getList());
        }else{
            // 查询条件
            $condition = array(
                    'key'       => isset($request['key']) ? $request['key'] : '',
                    'date_from' => isset($request['date_from']) ? $request['date_from'] : null,
                    'date_to'   => isset($request['date_to']) ? $request['date_to'] : null,
                    'active'    => (isset($request['active']) && $request['active'] != 'null') ? $request['active'] : 1,
                    'state'        => (isset($request['state']) && $request['state'] != 'null') ? $request['state'] : 0,
                    'type'      => (isset($request['type']) && $request['type'] != 'null') ? $request['type'] : null,
                    'dept'      => (isset($request['dept']) && $request['dept'] != 'null') ? $request['dept'] : null,
                    'page'      => isset($request['page']) ? $request['page'] : 1,
                    'limit'     => isset($request['limit']) ? $request['limit'] : 0
            );
            
            echo Zend_Json::encode($order->getData($condition));
        }
    
        exit;
    }
    
    /**
     * 编辑采购申请（新建、更新、删除）
     */
    public function editorderAction()
    {
        // 返回值数组
        $result = array(
                'success'       => true,
                'info'          => '编辑成功',
                'order_id'      => 0,
                'transfer_id'   => 0
        );
    
        $request = $this->getRequest()->getParams();
        
        $typeArr = array(
                'new'       => '新建',
                'edit'      => '修改',
                'transfer'    => '变更'
        );
        /* $result['success'] = false;
         echo '<pre>';
        print_r($request);
        exit; */
        // 操作类别（新建、更新、删除）
        $type = isset($request['operate']) ? $request['operate'] : '';
        
        if($type == 'new' || $type == 'edit' || $type == 'transfer'){
            $now = date('Y-m-d H:i:s');
            $user_session = new Zend_Session_Namespace('user');
            $user_id = $user_session->user_info['user_id'];
            
            $order = new Erp_Model_Purchse_Order();
            $partner = new Erp_Model_Partner();
            $setting = new Erp_Model_Setting_Currencyrate();
            
            $price_tax = isset($request['price_tax']) ? $request['price_tax'] : null;
            $price_tax = $price_tax == 'on' ? 1 : 0;
            
            $tax = $partner->getTaxInfo($request['supplier_id']);
           
            $currency_rate = $setting->getCurrentRateByCode($request['currency']);
            
            $hand = 0;
            if(isset($request['hand']) && $request['hand'] == 'on'){
                $hand = 1;
            }
            
            $data = array(
                    'company'               => $request['company'],
                    'currency'              => $request['currency'],
                    'currency_rate'         => $currency_rate,
                    'order_date'            => $request['order_date'],
                    'buyer_id'              => $request['buyer_id'],
                    'receiver_id'           => $request['receiver_id'] != '' ? $request['receiver_id'] : null,
                    'customer_address_code' => $request['customer_address_code'] != '' ? $request['customer_address_code'] : null,
                    'request_date'          => $request['request_date'],
                    'supplier_contact_id'   => $request['supplier_contact_id'],
                    'supplier_id'           => $request['supplier_id'],
                    'price_tax'             => $price_tax,
                    'tax_id'                => $tax['id'],
                    'tax_name'              => $tax['name'],
                    'tax_rate'              => $tax['rate'],
                    'tpl_id'                => $request['tpl_id'],
                    'type_id'               => $request['type_id'],
                    'settle_way'            => $request['settle_way'],
                    'delvery_clause'        => $request['delvery_clause'],
                    'remark'                => $request['remark'],
                    'description'           => $request['description'],
                    'manufacture'           => $request['manufacture'],
                    'responsible'            => $request['responsible'],
                    'update_time'           => $now,
                    'update_user'           => $user_id
            );
            
            if ($type == 'new') {
                if($hand){
                    $data['state'] = 2;
                    $data['hand'] = 1;
                    $data['number'] = $request['hand_number'];
                    $data['review_info'] = date('Y-m-d H:i:s').' 补单<br>';
                    
                    if($order->fetchAll("number = '".$data['number']."'")->count() > 0){
                        $result['success'] = false;
                        $result['info'] = "添加错误，订单号重复！";
                        
                        echo Zend_Json::encode($result);
                        
                        exit;
                    }
                }else{
                    $data['hand'] = 0;
                    $data['number'] = $order->getNewNum($request['type_id'], $request['company']);// 生成申订单号
                }
                
                $data['create_time'] = $now;
                $data['create_user'] = $user_id;
                $data['review_info'] = $now.': '.$user_session->user_info['user_name'].' ['.$typeArr[$type].']';
                
                try{
                    $order_id = $result['order_id'] = $order->insert($data);
                } catch (Exception $e){
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
                }
            }elseif ($type == 'edit' || $type == 'transfer'){
                try {
                    $review_info = $now.': '.$user_session->user_info['user_name'].' ['.$typeArr[$type].']';
                    $orderData = $order->getData(null, $request['id']);
                    
                    if($request['type_id'] != $orderData['type_id']){
                        // 当类别发送改变时，生成订单号
                        $data['number'] = $order->getNewNum($request['type_id']);
                    }
                    
                    $data['review_info'] = $orderData['review_info'].'<br>'.$review_info;
                    $data['state'] = 0;
                    
                    if($type == 'transfer'){
                        $transfer = new Erp_Model_Purchse_Transfer();
                    
                        $transferData = array(
                                'transfer_type'         => $request['transfer_type'],
                                'transfer_description'  => $request['transfer_description'],
                                'create_user'           => $user_id,
                                'create_time'           => $now
                        );
                    
                        $data['submit_type'] = 'transfer';
                    
                        // 修改变更时，先清空被拒绝的修改内容列表
                        if($request['transfer_id'] != ''){
                            $transfer_items = new Erp_Model_Purchse_Transferorderitems();
                            $transfer_items->delete("transfer_id = ".$request['transfer_id']);
                            
                            $transferData['state'] = 0;
                            $transfer->update($transferData, "id = ".$request['transfer_id']);
                    
                            $data['transfer_id'] = $result['transfer_id'] = $request['transfer_id'];
                        }else{
                            $transferData['type'] = 'order';
                            $transferData['target_id'] = $request['id'];
                    
                            $data['transfer_id'] = $result['transfer_id'] = $transfer->insert($transferData);
                        }
                    }
                    
                    $order->update($data, "id = ".$request['id']);
                    $result['order_id'] = $request['id'];
                } catch (Exception $e) {
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
                }
            }
        }elseif ($type == 'delete'){
            try {
                $order->delete("id = ".$request['req_id']);
            } catch (Exception $e){
                $result['success'] = false;
                $result['info'] = $e->getMessage();
            }
        }
        
        echo Zend_Json::encode($result);
    
        exit;
    }
    
    public function saveSplitItem($type, $order_item_id, $id, $qty, $code, $active = 1)
    {
        if($id){
            $itemsreq = new Erp_Model_Purchse_Orderitemsreq();
            
            $req_item_id_arr = explode(',', $id);
            $req_qty_arr = explode(',', $qty);
            
            if($type == 'delete'){
                // 删除订单、申请对应数据
                $itemsreq->delete("order_item_id = ".$order_item_id);
            }else if($type == 'update'){
                for($i = 0; $i < count($req_item_id_arr); $i++){
                    $data = array(
                            'active'    => $active,
                            'code'      => $code,
                            'qty'        => isset($req_qty_arr[$i]) ? $req_qty_arr[$i] : 0
                    );
                    //print_r($data);
                    //echo $order_item_id.'<br>'.$req_item_id_arr[$i].'<br>';
                    $itemsreq->update($data, "order_item_id = ".$order_item_id." and req_item_id = ".$req_item_id_arr[$i]);
                }
            }else if($type == 'insert'){
                $now = date('Y-m-d H:i:s');
                $user_session = new Zend_Session_Namespace('user');
                $user_id = $user_session->user_info['user_id'];
                
                for($i = 0; $i < count($req_item_id_arr); $i++){
                    $data = array(
                            'active'        => $active,
                            'order_item_id' => $order_item_id,
                            'req_item_id'   => $req_item_id_arr[$i],
                            'qty'           => $req_qty_arr[$i],
                            'code'          => $code,
                            'create_user'   => $user_id,
                            'create_time'   => $now
                    );
                    
                    $itemsreq->insert($data);
                }
            }
        }
    }
    
    /**
     * 保存采购申请表体
     */
    public function edititemsAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '编辑成功'
        );
    
        $request = $this->getRequest()->getParams();
        $type = isset($request['operate']) ? $request['operate'] : '';// 操作类别
        
        $typeArr = array(
                'new'        => '新建',
                'edit'        => '修改',
                'transfer'    => '变更'
        );
    
        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user_id = $user_session->user_info['user_id'];
    
        $json = json_decode($request['json']);
        $order_id = $json->order_id;
        $transfer_id = $json->transfer_id;
    
        $order_id = $json->order_id;
    
        $json_items = $json->items;
    
        $items_updated    = $json_items->updated;
        $items_inserted   = $json_items->inserted;
        $items_deleted    = $json_items->deleted;
        
        $items = new Erp_Model_Purchse_Orderitems();
        $itemsreq = new Erp_Model_Purchse_Orderitemsreq();
        $transfer_items = new Erp_Model_Purchse_Transferorderitems();
        
        // 更新
        if(count($items_updated) > 0){
            foreach ($items_updated as $val){
                $active = $val->items_active ? 1 : 0;
                
                $total = round($val->items_qty * $val->items_price, 4);
                
                $data = array(
                        'active'                => $active,
                        'req_number'            => $val->items_req_number,
                        'code'                  => $val->items_code,
                        'name'                  => $val->items_name,
                        'description'           => $val->items_description,
                        'supplier_code'         => $val->items_supplier_code,
                        'supplier_codename'     => $val->items_supplier_codename,
                        'supplier_description'  => $val->items_supplier_description,
                        'qty'                   => $val->items_qty,
                        'unit'                  => $val->items_unit,
                        'price'                 => $val->items_price,
                        'total'                 => $total,
                        'warehouse_code'        => $val->items_warehouse_code,
                        'request_date'          => $val->items_request_date,
                        'dept_id'               => $val->items_dept_id,
                        'project_info'          => $val->items_project_info,
                        'remark'                => $val->items_remark
                );
    
                try {
                    if($type == 'transfer'){
                        $data['req_item_id'] = $val->items_req_item_id;
                        $data['order_id'] = $order_id;
                        $data['transfer_id'] = $transfer_id;
                        $data['order_item_id'] = $val->items_id;
                        $data['transfer_type'] = 'update';
                    
                        $transfer_items->insert($data);
                    }else{
                        $data['update_user'] = $user_id;
                        $data['update_time'] = $now;
                    
                        $items->update($data, "id = ".$val->items_id);
                        
                        // 保存分拆订单项信息
                        $this->saveSplitItem('update', $val->items_id, $val->items_req_item_id, $val->items_qty, $val->items_code, $active);
                    }
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
                $active = $val->items_active ? 1 : 0;
                
                $total = round($val->items_qty * $val->items_price, 4);
                
                $data = array(
                        'order_id'              => $order_id,
                        'active'                => $active,
                        'req_number'            => $val->items_req_number,
                        'code'                  => $val->items_code,
                        'name'                  => $val->items_name,
                        'description'           => $val->items_description,
                        'supplier_code'         => $val->items_supplier_code,
                        'supplier_codename'     => $val->items_supplier_codename,
                        'supplier_description'  => $val->items_supplier_description,
                        'qty'                   => $val->items_qty,
                        'unit'                  => $val->items_unit,
                        'price'                 => $val->items_price,
                        'total'                 => $total,
                        'warehouse_code'        => $val->items_warehouse_code,
                        'request_date'          => $val->items_request_date,
                        'dept_id'               => $val->items_dept_id,
                        'project_info'          => $val->items_project_info,
                        'remark'                => $val->items_remark
                );
                
                try {
                    if($type == 'transfer'){
                        $data['transfer_id'] = $transfer_id;
                        $data['order_item_id'] = $val->items_id;
                        $data['req_item_id'] = $val->items_req_item_id;
                        $data['req_qty'] = $val->items_req_qty;
                        $data['transfer_type'] = 'add';
                        
                        $transfer_items->insert($data);
                    }else{
                        $data['create_user'] = $user_id;
                        $data['create_time'] = $now;
                        $data['update_user'] = $user_id;
                        $data['update_time'] = $now;
                        
                        $order_item_id = $items->insert($data);
                        
                        $this->saveSplitItem('insert', $order_item_id, $val->items_req_item_id, $val->items_req_qty, $val->items_code);
                    }
                } catch (Exception $e) {
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
    
                    echo Zend_Json::encode($result);
    
                    exit;
                }
            }
        }
    
        // 删除
        if(count($items_deleted) > 0){
            foreach ($items_deleted as $val){
                try {
                    if($type == 'transfer'){
                        $active = $val->items_active ? 1 : 0;
                    
                        $line_total = round($val->items_qty * $val->items_price, 4);
                    
                        $data = array(
                                'active'                => $active,
                                'transfer_type'         => 'delete',
                                'transfer_id'           => $transfer_id,
                                'order_item_id'         => $val->items_id,
                                'req_number'            => $val->items_req_number,
                                'code'                  => $val->items_code,
                                'name'                  => $val->items_name,
                                'description'           => $val->items_description,
                                'supplier_code'         => $val->items_supplier_code,
                                'supplier_codename'     => $val->items_supplier_codename,
                                'supplier_description'  => $val->items_supplier_description,
                                'qty'                   => $val->items_qty,
                                'unit'                  => $val->items_unit,
                                'price'                 => $val->items_price,
                                'total'                 => $total,
                                'warehouse_code'        => $val->items_warehouse_code,
                                'request_date'          => $val->items_request_date,
                                'dept_id'               => $val->items_dept_id,
                                'project_info'          => $val->items_project_info,
                                'remark'                => $val->items_remark
                        );
                    
                        $transfer_items->insert($data);
                    }else{
                        $items->delete("id = ".$val->items_id);
                        
                        // 清除订单项对应的申请项数据
                        $itemsreq->delete("order_item_id = ".$val->items_id);
                        
                        $this->saveSplitItem('delete', $val->items_id, $val->items_req_item_id, $val->items_req_qty, $val->items_code);
                    }
                } catch (Exception $e){
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
    
                    echo Zend_Json::encode($result);
    
                    exit;
                }
            }
        }
        
        // 更新订单项状态
        $activeItemsRes = $items->fetchAll("active = 1 and order_id = ".$order_id);
        
        if($activeItemsRes->count() > 0){
            $activeItems = $activeItemsRes->toArray();
            
            for($j = 0; $j < count($activeItems); $j++){
                $itemsreq->update(array('active' => 1), "order_item_id = ".$activeItems[$j]['id']);
            }
        }
        
        // 更新采购申请总计
        if($type != 'transfer'){
            $items->refreshOrderTotal($order_id);
        }
        
        $order = new Erp_Model_Purchse_Order();
        $order_data = $order->getData(null, $order_id);
        
        // 保存成功，进入审批流程
        if($result['success']/*  && $order_data['hand'] == 0 */){
            // 根据流程ID获取阶段信息
            $flow = new Admin_Model_Flow();
            $flowData = $flow->fetchRow("id = ".$order_data['order_flow_id'])->toArray();
            // 获取审核阶段
            $step = new Admin_Model_Step();
            $stepIds = $flowData['step_ids'];
            $stepArr = explode(',', $stepIds);
            
            $review = new Dcc_Model_Review();
            $review->delete("type = 'purchse_order_add' and file_id = ".$order_id);
            
            $mailTo = null;
            
            $i = 0;
            
            $help = new Application_Model_Helpers();
            
            // 根据阶段信息初始化审批流程，并向第一阶段审核人发送邮件
            foreach ($stepArr as $s){
                $stepData = $step->fetchRow("id = ".$s)->toArray();
                
                $step_user = $stepData['user'] != '' ? $stepData['user'] : null;
                $step_role = $stepData['dept'] != '' ? $stepData['dept'] : null;
                
                $employeeArr = $help->getReviewEmployee($step_user, $step_role);
                $employeeIdArr = $employeeArr['id'];
                $employeeIds = implode(',', $employeeIdArr);
            
                $reviewData = array(
                        'type'      => 'purchse_order_add',
                        'file_id'   => $order_id,
                        'step_name' => $stepData['step_name'],
                        'plan_user' => $employeeIds,
                        'plan_dept' => $step_role,
                        'method'    => $stepData['method'],
                        'return'    => $stepData['return']
                );
                
                $review->insert($reviewData);
            
                // 第一阶段发送邮件通知
                if($i == 0){
                    $mailTo = $employeeIdArr;
                }
            
                $i++;
            }
            
            if($mailTo){
                // 获取币种信息
                $currency = new Erp_Model_Setting_Currency();
                $currencyInfo = $currency->getInfoByCode($order_data['currency']);
                
                $total = $order_data['total'];
                if($order_data['currency_rate'] != 1){
                    $total = $order_data['forein_total'];
                }
                
                $mailContent = '<div>新建采购订单，请登录系统查看：</div>
                            <div>
                            <p><b>订单号：</b>'.$order_data['number'].'</p>
                            <p><b>采购员：</b>'.$user_session->user_info['user_name'].'</p>
                            <p><b>类别：</b>'.$order_data['type'].'</p>
                            <p><b>金额：</b><a style="color: #467500;font-weight: bold;">'.$currencyInfo['symbol'].$total.'</a></p>
                            <p><b>描述：</b>'.$order_data['description'].'</p>
                            <p><b>备注：</b>'.$order_data['remark'].'</p>
                            <p><b>申请时间：</b>'.$order_data['create_time'].'</p>
                            <p><b>更新时间：</b>'.$order_data['update_time'].'</p>
                            </div><hr>';
                
                $transferContent = '';
                
                if($type == 'transfer'){
                    $transferContent .= '<div><style type="text/css">
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
                            <th>操作类别</th>
                            <th>启用</th>
                            <th>物料号</th>
                            <th>名称</th>
                            <th>描述</th>
                            <th>数量</th>
                            <th>已收货</th>
                            <th>需求日期</th>
                            <th>项目信息</th>
                            <th>备注</th>
                            </tr>';
                    $i = 1;
                     
                    foreach ($items_inserted as $val){
                        $active = $val->items_active ? '是' : '否';
                        
                        $transferContent .= '<tr>
                            <td>'.$i.'</td>
                            <td>新增</td>
                            <td>'.$active.'</td>
                            <td>'.$val->items_code.'</td>
                            <td>'.$val->items_name.'</td>
                            <td>'.$val->items_description.'</td>
                            <td>'.$val->items_qty.'</td>
                            <td>0</td>
                            <td>'.date('Y-m-d', strtotime($val->items_request_date)).'</td>
                            <td>'.$val->items_project_info.'</td>
                            <td>'.$val->items_remark.'</td>
                            </tr>';
                
                        $i++;
                    }
                     
                    foreach ($items_updated as $val){
                        $tr = '<tr>';
                        
                        if(!$val->items_active){
                            $tr = '<tr class="inactive">';
                        }else{
                            $tr = '<tr class="update">';
                        }
                        
                        $active = $val->items_active ? '是' : '否';
                        
                        $transferContent .= $tr.'
                            <td>'.$i.'</td>
                            <td>更新</td>
                            <td>'.$active.'</td>
                            <td>'.$val->items_code.'</td>
                            <td>'.$val->items_name.'</td>
                            <td>'.$val->items_description.'</td>
                            <td>'.$val->items_qty.'</td>
                            <td>'.$val->items_qty_receive.'</td>
                            <td>'.$val->items_request_date.'</td>
                            <td>'.$val->items_project_info.'</td>
                            <td>'.$val->items_remark.'</td>
                            </tr>';
                
                        $i++;
                    }
                     
                    foreach ($items_deleted as $val){
                        $active = $val->items_active ? '是' : '否';
                        
                        $transferContent .= '<tr class="delete">
                            <td>'.$i.'</td>
                            <td>删除</td>
                            <td>'.$active.'</td>
                            <td>'.$val->items_code.'</td>
                            <td>'.$val->items_name.'</td>
                            <td>'.$val->items_description.'</td>
                            <td>'.$val->items_qty.'</td>
                            <td>0</td>
                            <td>'.$val->items_request_date.'</td>
                            <td>'.$val->items_project_info.'</td>
                            <td>'.$val->items_remark.'</td>
                            </tr>';
                
                        $i++;
                    }
                     
                    $transferContent .= '</table></div>';
                }
                
                $mailData = array(
                        'type'      => '消息',
                        'subject'   => '采购订单-'.$typeArr[$type],
                        'cc'        => $user_session->user_info['user_email'],
                        'content'   => $mailContent.$transferContent,
                        'add_date'  => $now
                );
                
                if($transferContent != ''){
                    $transfer = new Erp_Model_Purchse_Transfer();
                    $transfer->update(array('transfer_content' => $transferContent), "id = ".$transfer_id);
                }
                
                $result = $help->sendMailToStep($mailTo, $mailData);
            }
        }
        
        echo Zend_Json::encode($result);
    
        exit;
    }
    
    public function gettransferinfoAction()
    {
        $info = '';
        
        $request = $this->getRequest()->getParams();
        
        $id = isset($request['id']) && $request['id'] != '' ? $request['id'] : null;
        
        if($id){
            $transfer = new Erp_Model_Purchse_Transfer();
            
            $transferData = $transfer->getTransfer('order', $id);
            
            $i = count($transferData);
            
            foreach ($transferData as $t){
                $stateInfo = '<span style="color: #006000;font-weight: bold;">已批准</span>';
                
                if($t['state'] == 0){
                    $stateInfo = '<span style="color: #FF0000;font-weight: bold;">未审核</span>';
                }else if($t['state'] == 1){
                    $stateInfo = '<span style="color: #FF0000;font-weight: bold;">已拒绝</span>';
                }
                
                $content = '';
                
                if($t['transfer_type'] == '修改'){
                    $content = $t['transfer_content'];
                }
                
                $info .= '<div style="font-size: 12px;">['.$i.'] ['.$stateInfo.'] [用户：'.$t['creater'].'] [时间：'.$t['create_time'].'] [<b>类别：'.$t['transfer_type'].'</b>] [说明：'.$t['transfer_description'].']<div>'.$content.'<hr>';
                
                $i--;
            }
            
            $result['info'] = $info;
        }
        
        $info = $info == '' ? '无' : $info;
        
        echo $info;
        
        exit;
    }
}