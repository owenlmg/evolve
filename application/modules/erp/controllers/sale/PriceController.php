<?php
/**
 * 2014-9-15 19:51:09
 * @author x.li
 * @abstract 
 */
class Erp_Sale_PriceController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $user_session = new Zend_Session_Namespace('user');
        
        $this->view->user_id = 0;
        $this->view->admin = 0;
        
        if(isset($user_session->user_info)){
            $this->view->user_id = $user_session->user_info['user_id'];
        
            if(Application_Model_User::checkPermissionByRoleName('系统管理员') || Application_Model_User::checkPermissionByRoleName('客户管理员')){
                $this->view->admin = 1;
            }
        }
    }
    
    public function getpriceAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'price'     => array(),
                'info'      => '获取成功'
        );
        
        $request = $this->getRequest()->getParams();
        
        $code = isset($request['code']) && $request['code'] != '' ? $request['code'] : null;
        $customer_id = isset($request['customer_id']) && $request['customer_id'] != '' ? $request['customer_id'] : null;
        $fix = isset($request['fix']) && $request['fix'] == 1 ? true : false;
        $date = isset($request['date']) && $request['date'] != '' ? $request['date'] : null;
        $qty = isset($request['qty']) && $request['qty'] != '' ? $request['qty'] : null;
        $currency = isset($request['currency']) && $request['currency'] != '' ? $request['currency'] : null;
        
        if($code && $customer_id){
            $pricelist = new Erp_Model_Sale_Priceitems();
            
            $result['price'] = $pricelist->getPrice($code, $customer_id, $fix, $date, $qty, $currency);
        }else{
            $result['success'] = false;
            $result['info'] = '料号/业务伙伴为空，价格获取失败！';
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    // 获取物料号选项列表
    public function getcodelistAction()
    {
        $data = array();
        
        $request = $this->getRequest()->getParams();
        //$customer_id = 201;
        $customer_id = isset($request['customer_id']) ? $request['customer_id'] : null;
        
        if ($customer_id) {
            $code = new Erp_Model_Sale_Priceitems();
            
            $data = $code->getCodeList($customer_id);
        }
        
        echo Zend_Json::encode($data);
        
        exit;
    }
    
    public function editAction()
    {
        $result = array(
                'success'   => true,
                'info'      => ''
        );
        
        $request = $this->getRequest()->getParams();
        
        $data = isset($request['data']) ? Zend_Json::decode($request['data']) : null;
        //echo '<pre>';print_r($data);exit;
        if ($data) {
            $priceModel         = new Erp_Model_Sale_Price();
            $priceItemsModel    = new Erp_Model_Sale_Priceitems();
            $priceLadderModel   = new Erp_Model_Sale_Priceitemladder();
            $partnerModel       = new Erp_Model_Partner();
            
            $price_id = $data['id'];
            $now = date('Y-m-d H:i:s');
            $user_session = new Zend_Session_Namespace('user');
            $user_id = $user_session->user_info['user_id'];
            
            $taxData = $partnerModel->getTaxInfo($data['customer_id']);
            //echo '<pre>';print_r(Zend_Json::decode($data['items']['inserted'][1]['items_ladder']));exit;
            $priceData = array(
                    'price_date'    => $data['price_date'],
                    'customer_id'   => $data['customer_id'],
                    'currency'      => $data['currency'],
                    'price_tax'     => $data['price_tax'],
                    'tax_id'        => $taxData['id'],
                    'description'   => $data['description'],
                    'remark'        => $data['remark'],
                    'update_user'   => $user_id,
                    'update_time'   => $now
            );
            
            if ($data['operate'] == 'new') {
                $priceData['number'] = $priceModel->getNewNum();
                $priceData['create_user'] = $user_id;
                $priceData['create_time'] = $now;
                
                try{
                    $price_id = $priceModel->insert($priceData);
                } catch (Exception $e){
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
                    echo Zend_Json::encode($result);
                    exit;
                }
            }else{
                $priceData['state'] = 0;
                $priceData['update_user'] = $user_id;
                $priceData['update_time'] = $now;
                
                try{
                    $priceModel->update($priceData, "id = ".$price_id);
                } catch (Exception $e){
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
                    echo Zend_Json::encode($result);
                    exit;
                }
            }
            
            if ($result['success']) {
                // 插入
                foreach ($data['items']['inserted'] as $item){
                    $insertData = array(
                            'customer_id'           => $data['customer_id'],
                            'type'                  => $item['items_type'],
                            'currency'              => $data['currency'],
                            'price_id'              => $price_id,
                            'active_date'           => $item['items_active_date'],
                            'code'                  => $item['items_code'],
                            'price_start'           => $item['items_price_start'],
                            'price_final'           => $item['items_price_final'],
                            'remark'                => $item['items_remark'],
                            'customer_code'         => $item['items_customer_code'],
                            'customer_description'  => $item['items_customer_description'],
                            'name'                  => $item['items_name'],
                            'description'           => $item['items_description'],
                            'product_type'          => $item['items_product_type'],
                            'product_series'        => $item['items_product_series'],
                            'remark'                => $item['items_remark']
                    );
                    
                    try{
                        $item_id = $priceItemsModel->insert($insertData);
                    } catch (Exception $e){
                        $result['success'] = false;
                        $result['info'] = $e->getMessage();
                        echo Zend_Json::encode($result);
                        exit;
                    }
                    
                    if ($result['success'] && $item['items_ladder'] != '') {
                        $ladderData = Zend_Json::decode($item['items_ladder']);
                        
                        foreach ($ladderData as $ld){
                            $ld['item_id'] = $item_id;
                            
                            $priceLadderModel->insert($ld);
                        }
                    }
                }
                
                // 表体处理
                if ($data['operate'] == 'edit') {
                    foreach ($data['items']['updated'] as $item){
                        $updateData = array(
                                'type'                  => $item['items_type'],
                                'currency'              => $data['currency'],
                                'active_date'           => $item['items_active_date'],
                                'code'                  => $item['items_code'],
                                'name'                  => $item['items_name'],
                                'description'           => $item['items_description'],
                                'customer_code'         => $item['items_customer_code'],
                                'customer_description'  => $item['items_customer_description'],
                                'price_start'           => $item['items_price_start'],
                                'price_final'           => $item['items_price_final'],
                                'product_type'          => $item['items_product_type'],
                                'product_series'        => $item['items_product_series'],
                                'remark'                => $item['items_remark']
                        );
                        
                        try{
                            $priceItemsModel->update($updateData, "id = ".$item['items_id']);
                        } catch (Exception $e){
                            $result['success'] = false;
                            $result['info'] = $e->getMessage();
                            echo Zend_Json::encode($result);
                            exit;
                        }
                        
                        $item_id = $item['items_id'];
                        
                        if ($result['success'] && $item['items_ladder'] != '') {
                            $priceLadderModel->delete("item_id = ".$item_id);
                            
                            $ladderData = Zend_Json::decode($item['items_ladder']);
                        
                            foreach ($ladderData as $ld){
                                $ld['item_id'] = $item_id;
                        
                                $priceLadderModel->insert($ld);
                            }
                        }
                    }
                    
                    foreach ($data['items']['deleted'] as $item){
                        try{
                            $priceItemsModel->delete("id = ".$item['items_id']);
                        } catch (Exception $e){
                            $result['success'] = false;
                            $result['info'] = $e->getMessage();
                            echo Zend_Json::encode($result);
                            exit;
                        }
                        
                        if ($result['success'] && $item['ladder'] != '') {
                            $priceLadderModel->delete("item_id = ".$item_id);
                        }
                    }
                }
                
                // 进入审核流程
                $flow = new Admin_Model_Flow();
                $flowData = $flow->fetchRow("flow_name = '销售价格清单审核'")->toArray();
                // 获取审核阶段
                $step = new Admin_Model_Step();
                $stepIds = $flowData['step_ids'];
                $stepArr = explode(',', $stepIds);
                
                $review = new Dcc_Model_Review();
                $review->delete("type = 'sale_price_add' and file_id = ".$price_id);
                
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
                            'type'      => 'sale_price_add',
                            'file_id'   => $price_id,
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
                
                // 邮件通知
                if ($mailTo) {
                    $customerInfo = $partnerModel->getInfoById($data['customer_id']);
                    $customerName = $customerInfo['cname'] ? $customerInfo['cname'] : $customerInfo['ename'];
                    
                    $priceData = $priceModel->getData(null, $price_id);
                    
                    $mailContent = '<div>销售价格申请，请登录系统查看：</div>
                            <div>
                            <p><b>申请单号：</b>'.$priceData['number'].'</p>
                            <p><b>客户代码：</b>'.$customerInfo['code'].'</p>
                            <p><b>客户名称：</b>'.$customerName.'</p>
                            <p><b>申请人：</b>'.$user_session->user_info['user_name'].'</p>
                            <p><b>描述：</b>'.$data['description'].'</p>
                            <p><b>备注：</b>'.$data['remark'].'</p>
                            <p><b>提交时间：</b>'.$now.'</p>
                            </div><hr>';
                    
                    $itemsContent = '<div><style type="text/css">
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
                            <th>类别</th>
                            <th>物料号/内部型号</th>
                            <th>名称</th>
                            <th>产品类别</th>
                            <th>产品系列</th>
                            <th>描述</th>
                            <th>客户产品名称</th>
                            <th>客户产品描述</th>
                            <th>初始价格</th>
                            <th>最终价格</th>
                            <th>生效日期</th>
                            <th>备注</th>
                            </tr>';
                    
                    $itemsData = $priceItemsModel->getItems($price_id);
                    
                    $i = 0;
                    
                    foreach ($itemsData as $item){
                        $i++;
                        
                        $itemType = $item['items_type'] == 'catalog' ? '内部型号' : '物料号';
                        
                        $itemsContent .= '<tr>
                            <td>'.$i.'</td>
                            <td>'.$itemType.'</td>
                            <td>'.$item['items_code'].'</td>
                            <td>'.$item['items_name'].'</td>
                            <td>'.$item['items_product_type'].'</td>
                            <td>'.$item['items_product_series'].'</td>
                            <td>'.$item['items_description'].'</td>
                            <td>'.$item['items_customer_code'].'</td>
                            <td>'.$item['items_customer_description'].'</td>
                            <td>'.$item['items_price_start'].'</td>
                            <td>'.$item['items_price_final'].'</td>
                            <td>'.$item['items_active_date'].'</td>
                            <td>'.$item['items_remark'].'</td>
                            </tr>';
                    }
                    
                    $itemsContent .= '</table></div>';
                    
                    $mailData = array(
                            'type'      => '消息',
                            'subject'   => '销售价格清单申请',
                            'cc'        => $user_session->user_info['user_email'],
                            'content'   => $mailContent.$itemsContent,
                            'add_date'  => $now
                    );
                    
                    $result = $help->sendMailToStep($mailTo, $mailData);
                }
            }
        }else{
            $result['success'] = false;
            $result['info'] = '参数错误';
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    public function getAction()
    {
        $data = array();
        
        $request = $this->getRequest()->getParams();
        
        $date_from = isset($request['date_from']) && $request['date_from'] != '' ? $request['date_from'] : null;
        $date_to = isset($request['date_to']) && $request['date_to'] != '' ? $request['date_to'] : null;
        $state = isset($request['state']) ? $request['state'] : 0;
        $key = isset($request['key']) ? $request['key'] : null;
        $option = isset($request['option']) ? $request['option'] : 'data';
        
        $priceModel = new Erp_Model_Sale_Price();
        
        $conditions = array(
                'date_from' => $date_from,
                'date_to'   => $date_to,
                'state'     => $state,
                'key'       => $key,
                'option'    => $option
        );
        
        $data = $priceModel->getData($conditions);
        
        echo Zend_Json::encode($data);
        
        exit;
    }
    
    public function getitemsAction()
    {
        $data = array();
        
        $request = $this->getRequest()->getParams();
        
        $price_id = isset($request['price_id']) ? $request['price_id'] : null;
        
        if ($price_id) {
            $priceItemsModel = new Erp_Model_Sale_Priceitems();
            
            $data = $priceItemsModel->getItems($price_id);
        }
        
        echo Zend_Json::encode($data);
        
        exit;
    }
    
    public function selectAction()
    {
        $data = array();
        $dataTmp = array();
        
        $request = $this->getRequest()->getParams();
        
        $type = isset($request['type']) ? $request['type'] : null;
        $key = isset($request['key']) ? $request['key'] : null;
        
        if ($type == 'catalog') {
            $model = new Product_Model_Catalog();
            
            $dataTmp = $model->getCodeList($key);
        }else if ($type == 'material') {
            $model = new Product_Model_Materiel();
            
            $dataTmp = $model->getMaterils($key);
        }
        
        for ($i = 0; $i < count($dataTmp); $i++){
            if ($i < 200) {
                $dataTmp[$i]['type'] = $type;
                array_push($data, $dataTmp[$i]);
            }
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
        
        $review_id = isset($request['review_id']) ? $request['review_id'] : null;
        $review_operate = isset($request['review_operate']) ? $request['review_operate'] : null;
        $review_current_step = isset($request['review_current_step']) ? $request['review_current_step'] : null;// 当前阶段（review表ID）
        $review_last_step = isset($request['review_last_step']) ? $request['review_last_step'] : null;// 是否当前阶段为最后一阶段
        $review_to_finish = isset($request['review_to_finish']) ? $request['review_to_finish'] : null;// 是否批准后当前阶段结束
        $review_next_step = isset($request['review_next_step']) ? $request['review_next_step'] : null;// 下一阶段（review表ID）
        $review_remark = isset($request['review_remark']) ? $request['review_remark'] : null;
        $review_transfer_user = isset($request['review_transfer_user']) ? $request['review_transfer_user'] : null;//转审人
    
        if($review_id && $review_operate){
            $now = date('Y-m-d H:i:s');
            $user_session = new Zend_Session_Namespace('user');
            $user_id = $user_session->user_info['user_id'];
            $employee_id = $user_session->user_info['employee_id'];
    
            // 评审意见
            $review_info = '意见: '.$review_remark;
    
            $price = new Erp_Model_Sale_Price();
            $user = new Application_Model_User();
            $review = new Dcc_Model_Review();
            $employee = new Hra_Model_Employee();
    
            $priceData = $price->getData(null, $review_id);
    
            // 更新审核状态及审核意见
            if($review_operate == 'transfer'){
                // 转审
                $tmp = $priceData['review_info'] != '' ? $priceData['review_info'].'<br>' : '';
                $review_info = $tmp.$now.': '.$user_session->user_info['user_name'].' [审核-转审] ['.$review_info.']';
    
                $price->update(array('review_info' => $review_info), "id = ".$review_id);
    
                $reviewState = $review->fetchRow("type = 'sale_price_add' and finish_flg = 0 and file_id = ".$review_id, "id")->toArray();
                // 转审对象
                $transferUserInfo = $user->getEmployeeInfoById($review_transfer_user);
    
                $reviewUsers = $reviewState['plan_user'];
    
                $reviewUserArr = explode(',', $reviewUsers);
                $reviewer = array();
    
                if($reviewState['method'] == 2){
                    // 任意： 直接替换审核人为转审对象
                    foreach ($reviewUserArr as $review_user){
                        if($review_user == $employee_id){
                            array_push($reviewer, $transferUserInfo['id']);
                        }
                    }
                }else{
                    // 全部： 替换当前审核人为转审对象，其余审核人保留
                    foreach ($reviewUserArr as $review_user){
                        if($review_user == $employee_id){
                            array_push($reviewer, $transferUserInfo['id']);
                        }else if(!in_array($review_user, $reviewer)){
                            array_push($reviewer, $review_user);
                        }
                    }
                }
    
                $reviewerstr = implode(',', $reviewer);
    
                $review->update(array('plan_user' => $reviewerstr), "id = ".$reviewState['id']);
                
                // 发送邮件通知制单人
                $reviewResult = '<font style="color: #FF0000"><b>转审</b></font>';
    
                $mail = new Application_Model_Log_Mail();
    
                $to = $transferUserInfo['email'];
    
                $mailContent = '<div>销售价格申请审核：</div>
                                <div>
                                <p><b>审核人：</b>'.$user_session->user_info['user_name'].'</p>
                                <p><b>审核结果：</b>'.$reviewResult.'</p>
                                <p><b>审核意见：</b>'.$review_remark.'</p>
                                <p><b>申请单号：</b>'.$priceData['number'].'</p>
                                <p><b>申请人：</b>'.$priceData['creater'].'</p>
                                <p><b>描述：</b>'.$priceData['description'].'</p>
                                <p><b>备注：</b>'.$priceData['remark'].'</p>
                                <hr>
                                <p><b>审核日志：</b></p><p>'.$priceData['review_info'].'</p>
                                </div>';
    
                $mailData = array(
                        'type'      => '消息',
                        'subject'   => '销售价格申请-转审',
                        'to'        => $to,
                        'cc'        => $user_session->user_info['user_email'],
                        'user_id'   => $user_session->user_info['user_id'],
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
            }else if($review_operate == 'no'){
                // 更新申请状态
                $review_info = $now.': '.$user_session->user_info['user_name'].' [审核-拒绝] ['.$review_info.']';
                $tmp = $priceData['review_info'] != '' ? $priceData['review_info'].'<br>' : '';
                $data = array(
                        'state'         => 1,
                        'review_info'   => $tmp.$review_info
                );
    
                $price->update($data, "id = ".$review_id);
                
                // 删除当前申请的审核配置
                $review->delete("type = 'sale_price_add' and file_id = ".$review_id);
    
                // 发送邮件通知制单人
                $reviewResult = '<font style="color: #FF0000"><b>拒绝</b></font>';
    
                $mail = new Application_Model_Log_Mail();
    
                $applyEmployeeData = $user->fetchRow("id = ".$priceData['create_user'])->toArray();
                $applyEmployee = $employee->fetchRow("id = ".$applyEmployeeData['employee_id'])->toArray();
                $to = $applyEmployee['email'];
    
                $mailContent = '<div>销售价格申请审核：</div>
                                <div>
                                <p><b>审核人：</b>'.$user_session->user_info['user_name'].'</p>
                                <p><b>审核结果：</b>'.$reviewResult.'</p>
                                <p><b>审核意见：</b>'.$review_remark.'</p>
                                <p><b>申请单号：</b>'.$priceData['number'].'</p>
                                <p><b>申请人：</b>'.$priceData['creater'].'</p>
                                <p><b>描述：</b>'.$priceData['description'].'</p>
                                <p><b>备注：</b>'.$priceData['remark'].'</p>
                                <hr>
                                <p><b>审核日志：</b></p><p>'.$priceData['review_info'].'</p>
                                </div>';
    
                $mailData = array(
                        'type'      => '消息',
                        'subject'   => '销售价格申请-审核',
                        'to'        => $to,
                        'cc'        => $user_session->user_info['user_email'],
                        'user_id'   => $priceData['create_user'],
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
                $tmp = $priceData['review_info'] != '' ? $priceData['review_info'].'<br>' : '';
                $reqUpdateData = array(
                        'review_info'   => $tmp.$review_info
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
    
                        // 发布：检查客户的当前产品（包括类别）是否存在生效价格（如存在则更新历史价格为作废）
                        $price->updatePriceByPriceId($review_id);
    
                        // 更新申请状态
                        $reqUpdateData['state'] = 2;
                        $reqUpdateData['release_time'] = $now;
    
                        // 更新审核记录表
                        $review->update($data, "id = ".$review_current_step);
    
                        $mail = new Application_Model_Log_Mail();
    
                        $applyEmployeeData = $user->fetchRow("id = ".$priceData['create_user'])->toArray();
                        $applyEmployee = $employee->fetchRow("id = ".$applyEmployeeData['employee_id'])->toArray();
                        $to = $applyEmployee['email'];
                        $cc = $user_session->user_info['user_email'];
                        
                        $mailContent = '<div>销售价格审核批准，请登录系统查看：</div>
                                <div>
                                <p><b>审核人：</b>'.$user_session->user_info['user_name'].'</p>
                                <p><b>审核结果：</b>'.$reviewResult.'</p>
                                <p><b>审核意见：</b>'.$review_remark.'</p>
                                <p><b>申请单号：</b>'.$priceData['number'].'</p>
                                <p><b>申请人：</b>'.$priceData['creater'].'</p>
                                <p><b>描述：</b>'.$priceData['description'].'</p>
                                <p><b>备注：</b>'.$priceData['remark'].'</p>
                                <hr>
                                <p><b>审核日志：</b></p><p>'.$reqUpdateData['review_info'].'</p>
                                </div>';
    
                        $mailData = array(
                                'type'      => '消息',
                                'subject'   => '销售价格申请-发布',
                                'to'        => $to,
                                'cc'        => $cc,
                                'user_id'   => $priceData['create_user'],
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
                        $reviewNextStepData = $review->fetchRow("id = ".$review_next_step)->toArray();
    
                        $mailTo = explode(',', $reviewNextStepData['plan_user']);
    
                        if($mailTo){
                            $mailContent = '<div>销售价格申请，请登录系统查看：</div>
                                            <div>
                                            <p><b>审核人：</b>'.$user_session->user_info['user_name'].'</p>
                                            <p><b>审核结果：</b>'.$reviewResult.'</p>
                                            <p><b>审核意见：</b>'.$review_remark.'</p>
                                            <p><b>申请单号：</b>'.$priceData['number'].'</p>
                                            <p><b>申请人：</b>'.$priceData['creater'].'</p>
                                            <p><b>描述：</b>'.$priceData['description'].'</p>
                                            <p><b>备注：</b>'.$priceData['remark'].'</p>
                                            <hr>
                                            <p><b>审核日志：</b></p><p>'.$reqUpdateData['review_info'].'</p>
                                            </div>';
    
                            $mailData = array(
                                    'type'      => '消息',
                                    'subject'   => '销售价格申请-新申请',
                                    'cc'        => $user_session->user_info['user_email'],
                                    'content'   => $mailContent,
                                    'add_date'  => $now
                            );
    
                            $resultMail = $help->sendMailToStep($mailTo, $mailData);
    
                            if(!$resultMail['success']){
                                $result = $resultMail;
                            }
                        }
                    }else{
                        $data = array(
                                'actual_user'   => $actual_user
                        );
    
                        $review->update($data, "id = ".$review_current_step);
    
                        // 等待其他审核人批准
    
                    }
                }
    
                // 更新申请状态
                $price->update($reqUpdateData, "id = ".$review_id);
            }
        }
    
        echo Zend_Json::encode($result);
    
        exit;
    }
    
    public function testAction()
    {
        $priceItemsModel = new Erp_Model_Sale_Priceitems();
        $itemsData = $priceItemsModel->getItems(7);
        
        echo '<pre>';print_r($itemsData);exit;
    }
}