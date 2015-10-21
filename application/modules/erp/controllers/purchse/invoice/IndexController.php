<?php
/**
 * 2014-8-12 下午8:51:09
 * @author x.li
 * @abstract 
 */
class Erp_Purchse_Invoice_IndexController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $user_session = new Zend_Session_Namespace('user');
        
        $this->view->invoiceAdmin = 0;
        
        $this->view->user_id = 0;
        
        if(isset($user_session->user_info)){
            $this->view->user_id = $user_session->user_info['user_id'];
        
            if(Application_Model_User::checkPermissionByRoleName('系统管理员') || Application_Model_User::checkPermissionByRoleName('供应商管理员')){
                $this->view->invoiceAdmin = 1;
            }
        }
    }
    
    public function getinvoiceAction()
    {
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        $option = isset($request['option']) ? $request['option'] : 'data';
        
        $invoice = new Erp_Model_Purchse_Invoice();
        
        if($option == 'list'){
            echo Zend_Json::encode($invoice->getList());
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
        
            echo Zend_Json::encode($invoice->getData($condition));
        }
        
        exit;
    }
    
    public function editinvoiceAction()
    {
        // 返回值数组
        $result = array(
                'success'       => true,
                'info'          => '编辑成功',
                'invoice_id'      => 0
        );
        
        $request = $this->getRequest()->getParams();
        
        $typeArr = array(
                'new'       => '新建',
                'edit'      => '修改'
        );
        
        // 操作类别（新建、更新）
        $type = isset($request['operate']) ? $request['operate'] : '';
        $flow = new Admin_Model_Flow();
        $flowData = $flow->fetchRow("flow_name = '采购发票'")->toArray();
    
        if($type == 'new' || $type == 'edit'){
            $now = date('Y-m-d H:i:s');
            $user_session = new Zend_Session_Namespace('user');
            $user_id = $user_session->user_info['user_id'];
    
            $invoice = new Erp_Model_Purchse_Invoice();
            
            $data = array(
                    'flow_id'       => $flowData['id'],
                    'invoice_date'  => $request['invoice_date'],
                    'supplier_id'   => $request['supplier_id'],
                    'currency'      => $request['currency'],
                    'remark'        => $request['remark'],
                    'description'   => $request['description'],
                    'update_time'   => $now,
                    'update_user'   => $user_id
            );
    
            if ($type == 'new') {
                $data['number'] = $invoice->getNewNum();// 生成发票号
    
                $data['create_time'] = $now;
                $data['create_user'] = $user_id;
                $data['review_info'] = $now.': '.$user_session->user_info['user_name'].' ['.$typeArr[$type].']';
    
                try{
                    $order_id = $result['invoice_id'] = $invoice->insert($data);
                } catch (Exception $e){
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
                }
            }elseif ($type == 'edit'){
                try {
                    $review_info = $now.': '.$user_session->user_info['user_name'].' ['.$typeArr[$type].']';
                    $invoiceData = $invoice->getData(null, $request['id']);
                    
                    $data['review_info'] = $invoiceData['review_info'].'<br>'.$review_info;
                    $data['state'] = 0;
                    
                    $invoice->update($data, "id = ".$request['id']);
                    $result['invoice_id'] = $request['id'];
                } catch (Exception $e) {
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
                }
            }
        }elseif ($type == 'delete'){
            try {
                $invoice->delete("id = ".$request['id']);
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
        $type = isset($request['operate']) ? $request['operate'] : '';// 操作类别
        
        $typeArr = array(
                'new'        => '新建',
                'edit'        => '修改'
        );
        
        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user_id = $user_session->user_info['user_id'];
        
        $json = json_decode($request['json']);
        $invoice_id = $json->invoice_id;
        
        $json_items = $json->items;
        
        $items_updated    = $json_items->updated;
        $items_inserted   = $json_items->inserted;
        $items_deleted    = $json_items->deleted;
        
        $items = new Erp_Model_Purchse_Invoiceitems();
        
        // 更新
        if(count($items_updated) > 0){
            foreach ($items_updated as $val){
                $data = array(
                        'invoice_id'    => $invoice_id,
                        'order_id'      => $val->items_order_id,
                        'order_date'    => $val->items_order_date,
                        'order_item_id' => $val->items_order_item_id,
                        'order_number'  => $val->items_order_number,
                        'code'          => $val->items_code,
                        'name'          => $val->items_name,
                        'description'   => $val->items_description,
                        'qty'           => $val->items_qty,
                        'unit'          => $val->items_unit,
                        'price'         => $val->items_price,
                        'price_tax'     => $val->items_order_price_tax,
                        'currency'      => $val->items_order_currency,
                        'currency_rate' => $val->items_order_currency_rate,
                        'tax_id'        => $val->items_order_tax_id,
                        'tax_name'      => $val->items_order_tax_name,
                        'tax_rate'      => $val->items_order_tax_rate,
                        'remark'        => $val->items_remark,
                        'update_user'   => $user_id,
                        'update_time'   => $now
                );
                
                try {
                    $data['update_user'] = $user_id;
                    $data['update_time'] = $now;
                    
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
                $data = array(
                        'invoice_id'    => $invoice_id,
                        'order_id'      => $val->items_order_id,
                        'order_date'    => $val->items_order_date,
                        'order_item_id' => $val->items_order_item_id,
                        'order_number'  => $val->items_order_number,
                        'code'          => $val->items_code,
                        'name'          => $val->items_name,
                        'description'   => $val->items_description,
                        'qty'           => $val->items_qty,
                        'unit'          => $val->items_unit,
                        'price'         => $val->items_price,
                        'price_tax'     => $val->items_price_tax,
                        'currency'      => $val->items_order_currency,
                        'currency_rate' => $val->items_order_currency_rate,
                        'tax_id'        => $val->items_order_tax_id,
                        'tax_name'      => $val->items_order_tax_name,
                        'tax_rate'      => $val->items_order_tax_rate,
                        'remark'        => $val->items_remark,
                        'create_user'   => $user_id,
                        'create_time'   => $now,
                        'update_user'   => $user_id,
                        'update_time'   => $now
                );
                
                try {
                    $invoice_item_id = $items->insert($data);
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
                    $items->delete("id = ".$val->items_id);
                } catch (Exception $e){
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
                    
                    echo Zend_Json::encode($result);
                    
                    exit;
                }
            }
        }
        
        $items->refreshInvoiceTotal($invoice_id);
        
        $invoice = new Erp_Model_Purchse_Invoice();
        $invoice_data = $invoice->getData(null, $invoice_id);
        
        // 保存成功，进入审批流程
        if($result['success']){
            // 根据流程ID获取阶段信息
            $flow = new Admin_Model_Flow();
            $flowData = $flow->fetchRow("id = ".$invoice_data['flow_id'])->toArray();
            // 获取审核阶段
            $step = new Admin_Model_Step();
            $stepIds = $flowData['step_ids'];
            $stepArr = explode(',', $stepIds);
            
            $review = new Dcc_Model_Review();
            $review->delete("type = 'purchse_invoice_add' and file_id = ".$invoice_id);
            
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
                        'type'      => 'purchse_invoice_add',
                        'file_id'   => $invoice_id,
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
                $currencyInfo = $currency->getInfoByCode($invoice_data['currency']);
                
                $total = $invoice_data['total'];
                if($invoice_data['currency'] != 'CNY'){
                    $total = $invoice_data['forein_total'];
                }
                
                $mailContent = '<div>新建采购发票，请登录系统查看：</div>
                            <div>
                            <p><b>发票号：</b>'.$invoice_data['number'].'</p>
                            <p><b>制单员：</b>'.$user_session->user_info['user_name'].'</p>
                            <p><b>供应商：</b>'.$invoice_data['supplier_ename'].'</p>
                            <p><b>金额：</b><a style="color: #467500;font-weight: bold;">'.$currencyInfo['symbol'].$total.'</a></p>
                            <p><b>描述：</b>'.$invoice_data['description'].'</p>
                            <p><b>备注：</b>'.$invoice_data['remark'].'</p>
                            <p><b>申请时间：</b>'.$invoice_data['create_time'].'</p>
                            <p><b>更新时间：</b>'.$invoice_data['update_time'].'</p>
                            </div><hr>';
                
                $mailData = array(
                        'type'      => '消息',
                        'subject'   => '采购发票-'.$typeArr[$type],
                        'cc'        => $user_session->user_info['user_email'],
                        'content'   => $mailContent,
                        'add_date'  => $now
                );
                
                $result = $help->sendMailToStep($mailTo, $mailData);
            }
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    public function getinvoiceitemsAction()
    {
        $data = array();
    
        $request = $this->getRequest()->getParams();
    
        $invoice_id = isset($request['invoice_id']) ? $request['invoice_id'] : 0;
    
        if($invoice_id > 0){
            $items = new Erp_Model_Purchse_Invoiceitems();
    
            $data = $items->getData($invoice_id);
        }
    
        echo Zend_Json::encode($data);
    
        exit;
    }
    
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
        $review_operate = isset($request['review_operate']) ? $request['review_operate'] : null;
        $review_current_step = isset($request['review_current_step']) ? $request['review_current_step'] : null;// 当前阶段（review表ID）
        $review_last_step = isset($request['review_last_step']) ? $request['review_last_step'] : null;// 是否当前阶段为最后一阶段
        $review_to_finish = isset($request['review_to_finish']) ? $request['review_to_finish'] : null;// 是否批准后当前阶段结束
        $review_next_step = isset($request['review_next_step']) ? $request['review_next_step'] : null;// 下一阶段（review表ID）
        $review_remark = isset($request['review_remark']) ? $request['review_remark'] : null;
        
        if($review_id && $review_operate){
            $now = date('Y-m-d H:i:s');
            $user_session = new Zend_Session_Namespace('user');
            $user_id = $user_session->user_info['user_id'];
            
            // 评审意见
            $review_info = '意见: '.$review_remark;
            
            $invoice = new Erp_Model_Purchse_Invoice();
            $invoiceItems = new Erp_Model_Purchse_Invoiceitems();
            
            $user = new Application_Model_User();
            $review = new Dcc_Model_Review();
            $employee = new Hra_Model_Employee();
            
            $invoiceData = $invoice->getData(null, $review_id);
            
            // 更新审核状态及审核意见
            if($review_operate == 'no'){
                // 更新采购申请状态
                $review_info = $now.': '.$user_session->user_info['user_name'].' [审核-拒绝] ['.$review_info.']';
                $data = array(
                        'state'         => 1,
                        'review_info'   => $invoiceData['review_info'].'<br>'.$review_info
                );
                
                // 更新订单状态
                $invoice->update($data, "id = ".$review_id);
                
                // 删除当前申请的审核配置
                $review->delete("type = 'purchse_invoice_add' and file_id = ".$review_id);
                
                // 发送邮件通知申请人
                $reviewResult = '<font style="color: #FF0000"><b>拒绝</b></font>';
                
                $mail = new Application_Model_Log_Mail();
                
                $applyEmployeeData = $user->fetchRow("id = ".$invoiceData['create_user'])->toArray();
                $applyEmployee = $employee->fetchRow("id = ".$applyEmployeeData['employee_id'])->toArray();
                $to = $applyEmployee['email'];
                
                // 获取币种信息
                $currency = new Erp_Model_Setting_Currency();
                $currencyInfo = $currency->getInfoByCode($invoiceData['currency']);
                
                $total = $invoiceData['total'];
                if($invoiceData['currency'] != 'CNY'){
                    $total = $invoiceData['forein_total'];
                }
                
                $mailContent = '<div>采购发票审核：</div>
                                <div>
                                <p><b>审核人：</b>'.$user_session->user_info['user_name'].'</p>
                                <p><b>审核结果：</b>'.$reviewResult.'</p>
                                <p><b>审核意见：</b>'.$review_remark.'</p>
                                <p><b>发票号：</b>'.$invoiceData['number'].'</p>
                                <p><b>申请人：</b>'.$invoiceData['creater'].'</p>
                                <p><b>金额：</b><a style="color: #467500;font-weight: bold;">'.$currencyInfo['symbol'].$total.'</a></p>
                                <p><b>备注：</b>'.$invoiceData['remark'].'</p>
                                <p><b>申请时间：</b>'.$invoiceData['create_time'].'</p>
                                <p><b>更新时间：</b>'.$invoiceData['update_time'].'</p>
                                <hr>
                                <p><b>审核日志：</b></p><p>'.$invoiceData['review_info'].'</p>
                                </div>';
                
                $mailData = array(
                        'type'      => '消息',
                        'subject'   => '采购发票-审核',
                        'to'        => $to,
                        'cc'        => $user_session->user_info['user_email'],
                        'user_id'   => $invoiceData['create_user'],
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
                $invoiceUpdateData = array(
                        'review_info'   => $invoiceData['review_info'].'<br>'.$review_info
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
                        $data = array(
                                'actual_user'   => $actual_user,
                                'finish_time'   => $now,
                                'finish_flg'    => 1
                        );
                        
                        $reviewResult = '<font style="color: #006400"><b>发布</b></font>';
                        
                        // 发布
                        
                        // 更新申请状态
                        $invoiceUpdateData['state'] = 2;
                        $invoiceUpdateData['release_time'] = $now;
                        
                        // 更新审核记录表
                        $review->update($data, "id = ".$review_current_step);
                        
                        $mail = new Application_Model_Log_Mail();
                        
                        $applyEmployeeData = $user->fetchRow("id = ".$invoiceData['create_user'])->toArray();
                        $applyEmployee = $employee->fetchRow("id = ".$applyEmployeeData['employee_id'])->toArray();
                        $to = $applyEmployee['email'];
                        
                        // 获取币种信息
                        $currency = new Erp_Model_Setting_Currency();
                        $currencyInfo = $currency->getInfoByCode($invoiceData['currency']);
                        
                        $total = $invoiceData['total'];
                        if($invoiceData['currency'] != 'CNY'){
                            $total = $invoiceData['forein_total'];
                        }
                        
                        $mailContent = '<div>采购发票审核批准，请登录系统查看：</div>
                                <div>
                                <p><b>审核人：</b>'.$user_session->user_info['user_name'].'</p>
                                <p><b>审核结果：</b>'.$reviewResult.'</p>
                                <p><b>审核意见：</b>'.$review_remark.'</p>
                                <p><b>发票号：</b>'.$invoiceData['number'].'</p>
                                <p><b>申请人：</b>'.$invoiceData['creater'].'</p>
                                <p><b>金额：</b><a style="color: #467500;font-weight: bold;">'.$currencyInfo['symbol'].$total.'</a></p>
                                <p><b>备注：</b>'.$invoiceData['remark'].'</p>
                                <p><b>申请时间：</b>'.$invoiceData['create_time'].'</p>
                                <p><b>更新时间：</b>'.$invoiceData['update_time'].'</p>
                                <hr>
                                <p><b>审核日志：</b></p><p>'.$invoiceUpdateData['review_info'].'</p>
                                </div>';
                        
                        $mailData = array(
                                'type'      => '消息',
                                'subject'   => '采购发票-发布',
                                'to'        => $to,
                                'cc'        => $user_session->user_info['user_email'],
                                'user_id'   => $invoiceData['create_user'],
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
                                $currencyInfo = $currency->getInfoByCode($invoiceData['currency']);
                                
                                $total = $invoiceData['total'];
                                if($invoiceData['currency'] != 'CNY'){
                                    $total = $invoiceData['forein_total'];
                                }
                                
                                $mailContent = '<div>新建采购发票，请登录系统查看：</div>
                                                <div>
                                                <p><b>订单号：</b>'.$invoiceData['number'].'</p>
                                                <p><b>申请人：</b>'.$invoiceData['creater'].'</p>
                                                <p><b>金额：</b><a style="color: #467500;font-weight: bold;">'.$currencyInfo['symbol'].$total.'</a></p>
                                                <p><b>备注：</b>'.$invoiceData['remark'].'</p>
                                                <p><b>申请时间：</b>'.$invoiceData['create_time'].'</p>
                                                <p><b>更新时间：</b>'.$invoiceData['update_time'].'</p>
                                                <hr>
                                                <p><b>审核日志：</b></p><p>'.$invoiceUpdateData['review_info'].'</p>
                                                </div>';
                                
                                $mailData = array(
                                        'type'      => '消息',
                                        'subject'   => '采购发票-新建',
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
                $invoice->update($invoiceUpdateData, "id = ".$review_id);
            }
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    public function editattachAction()
    {
        $result = array(
                'success'   => true,
                'info'      => '上传成功'
        );
        
        $request = $this->getRequest()->getParams();
        
        $invoice_id = isset($request['invoice_id']) ? $request['invoice_id'] : null;
        //$remark = isset($request['attach_remark']) ? $request['attach_remark'] : null;
        
        if($invoice_id && isset($_FILES['attach_file'])){
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
            
            $invoice = new Erp_Model_Purchse_Invoice();
            $invoiceData = $invoice->getData(null, $invoice_id);
    
            if($invoiceData['attach_path'] != '' && file_exists($invoiceData['attach_path'])){
                unlink($invoiceData['attach_path']);
            }
    
            $invoiceData = array(
                    'attach_name'   => $file_name,
                    'attach_path'   => $tmp_file_path,
                    //'remark'        => $remark,
                    'update_user'   => $user_id,
                    'update_time'   => $now
            );
    
            $invoice->update($invoiceData, "id = ".$invoice_id);
        }
    
        echo Zend_Json::encode($result);
    
        exit;
    }
}