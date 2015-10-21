<?php
/**
 * 2014-2-15 下午3:51:09
 * @author x.li
 * @abstract 
 */
class Erp_Purchse_ReqController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $user_session = new Zend_Session_Namespace('user');
        
        $this->view->typeEditDisable = 1;
        $this->view->accessViewOrder = 0;
        
        $this->view->user_id = 0;
        
        if(isset($user_session->user_info)){
            $this->view->user_id = $user_session->user_info['user_id'];
            
            if(Application_Model_User::checkPermissionByRoleName('系统管理员') || Application_Model_User::checkPermissionByRoleName('供应商管理员')){
                $this->view->typeEditDisable = 0;
                $this->view->accessViewOrder = 1;
            }else if(Application_Model_User::checkPermissionByRoleName('采购申请明细查看')){
                $this->view->accessViewOrder = 1;
            }
        }
    }
    
    public function checkdescAction()
    {
        $result = array(
                'success'   => true,
                'info'      => ''
        );
        
        $request = $this->getRequest()->getParams();
        
        $code = isset($request['code']) ? $request['code'] : null;
        
        if ($code) {
            $desc = new Product_Model_Desc();
            $result['success'] = $desc->isChanging($code);
        }else{
            $result['success'] = false;
            $result['info'] = '料号为空，获取失败！';
        }
        
        echo Zend_Json::encode($result);
        
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
        
        if(isset($request['id']) && isset($request['type_id'])){
            $req = new Erp_Model_Purchse_Req();
            $items = new Erp_Model_Purchse_Reqitems();
            $purchse_type = new Erp_Model_Purchse_Type();
            $dept = new Hra_Model_Dept();
            
            $typeData = $purchse_type->getData($request['type_id']);
            
            $reqData = $req->getData(null, $request['id']);
            $itemsData = $items->getData($request['id']);
            
            $type = '其它';
            if($typeData['name'] == '物料原材料' || $typeData['name'] == '辅料工具'){
                $type = '物料原材料';
            }
            
            $tpl = new Erp_Model_Tpl();
            $tplHtmlData = $tpl->fetchRow("type = 'req' and name = '".$type."'")->toArray();
            $tplHtml = $tplHtmlData['html'];
            
            $itemsHtml = '';
            $itemsHtml_office = '';
            $itemsHtml_other = '';
            $i = 0;
            
            foreach ($itemsData as $item){
                $i++;
                
                // 获取需求部门名称
                $deptName = '无';
                if($item['items_dept_id'] > 0){
                    $deptData = $dept->fetchRow("id = ".$item['items_dept_id'])->toArray();
                    $deptName = $deptData['name'];
                }
                
                $itemsHtml .= '
                    <tr>
                        <td>'.$i.'</td>
                        <td>'.$item['items_code'].'</td>
                        <td width="100px" style="word-wrap:break-word;">'.$item['items_name'].'</td>
                        <td width="150px" style="word-wrap:break-word;">'.$item['items_description'].'</td>
                        <td>'.$item['items_qty'].'</td>
                        <td>'.$item['items_unit'].'</td>
                        <td>'.$item['items_price'].'</td>
                        <td>'.$item['items_line_total'].'</td>
                        <td>'.$item['items_date_req'].'</td>
                        <td>'.$item['items_project_info'].'</td>
                        <td>'.$item['items_order_req_num'].'</td>
                        <td>'.$item['items_customer_address'].'</td>
                        <td>'.$item['items_customer_aggrement'].'</td>
                        <td>'.$item['items_remark'].'</td>
                    </tr>';
                
                $itemsHtml_office .= '
                    <tr>
                        <td>'.$i.'</td>
                        <td width="100px" style="word-wrap:break-word;">'.$item['items_name'].'</td>
                        <td width="150px" style="word-wrap:break-word;">'.$item['items_description'].'</td>
                        <td>'.$item['items_qty'].'</td>
                        <td>'.$item['items_unit'].'</td>
                        <td>'.$item['items_price'].'</td>
                        <td>'.$item['items_line_total'].'</td>
                        <td>'.$item['items_date_req'].'</td>
                        <td>'.$item['items_supplier'].'</td>
                        <td>'.$item['items_model'].'</td>
                        <td>'.$deptName.'</td>
                        <td>'.$item['items_remark'].'</td>
                    </tr>';
                
                $itemsHtml_other .= '
                    <tr>
                        <td>'.$i.'</td>
                        <td width="100px" style="word-wrap:break-word;">'.$item['items_name'].'</td>
                        <td width="150px" style="word-wrap:break-word;">'.$item['items_description'].'</td>
                        <td>'.$item['items_qty'].'</td>
                        <td>'.$item['items_unit'].'</td>
                        <td>'.$item['items_price'].'</td>
                        <td>'.$item['items_line_total'].'</td>
                        <td>'.$item['items_date_req'].'</td>
                        <td>'.$item['items_remark'].'</td>
                    </tr>';
            }
            
            $orderInfo = array(
                    'title'         => '采购申请 - '.$typeData['name'],
                    'date'          => $reqData['create_time'],
                    'total'         => $reqData['total'],
                    'number'        => $reqData['number'],
                    'dept'          => $reqData['dept'],
                    'applier'       => $reqData['apply_user_name'],
                    'reason'        => $reqData['reason'],
                    'remark'        => $reqData['remark'],
                    'items'         => $itemsHtml,
                    'items_office'  => $itemsHtml_office,
                    'items_other'   => $itemsHtml_other,
                    'company_logo'  => HOME_PATH.'/public/images/company.png'
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
    
    public function importitemsAction()
    {
        $result = array(
                'success'   => true,
                'data'      => array(),
                'info'      => '导入成功'
        );
        
        if(isset($_FILES['csv'])){
            $file = $_FILES['csv'];
            
            $file_extension = strrchr($file['name'], ".");
            
            $h = new Application_Model_Helpers();
            $tmp_file_name = $h->getMicrotimeStr().$file_extension;
            
            $savepath = "../temp/";
            $tmp_file_path = $savepath.$tmp_file_name;
            move_uploaded_file($file["tmp_name"], $tmp_file_path);
            
            $file = fopen($tmp_file_path, "r");
            $i = 0;
            
            $materiel = new Product_Model_Materiel();
            $desc = new Product_Model_Desc();
            
            while(! feof($file))
            {
                $csv_data = fgetcsv($file);
                
                $code = isset($csv_data[1]) ? $csv_data[1] : '';
                $qty = isset($csv_data[2]) ? $csv_data[2] : 0;
                $date_req = isset($csv_data[3]) ? str_replace('-', '/', $csv_data[3]) : '';
                $project_info = isset($csv_data[4]) ? $csv_data[4] : '';
                $order_req_num = isset($csv_data[5]) ? $csv_data[5] : '';
                $customer_address = isset($csv_data[6]) ? $csv_data[6] : '';
                $customer_aggrement = isset($csv_data[7]) ? $csv_data[7] : '';
                $remark = isset($csv_data[8]) ? $csv_data[8] : '';
                
                if($i > 0 && $code != ''){
                    $materielData = $materiel->getOptionList($code);
                    
                    $is_changing = false;
                    
                    if ($code != '') {
                        $is_changing = $desc->isChanging($code);
                    }
                    
                    if(count($materielData) > 0){
                        array_push($result['data'], array(
                            'code'                  => $code,
                            'is_changing'           => $is_changing,
                            'name'                  => $materielData['name'],
                            'description'           => $materielData['description'],
                            'unit'                  => $materielData['unit'],
                            'qty'                   => $qty,
                            'date_req'              => $date_req,
                            'project_info'          => mb_convert_encoding($project_info, 'UTF-8', 'GBK'),
                            'order_req_num'         => $order_req_num,
                            'customer_address'      => $customer_address,
                            'customer_aggrement'    => $customer_aggrement,
                            'remark'                => mb_convert_encoding($remark, 'UTF-8', 'GBK')
                        ));
                    }else {
                        $result['success'] = 0;
                        $result['info'] = $code.'未找到，导入失败！';
                        
                        echo Zend_Json::encode($result);
                        
                        exit;
                    }
                }
                
                $i++;
            }
            
            fclose($file);
        }else{
            $result['success'] = false;
            $result['info'] = '没有选择文件，导入失败！';
        }
        //echo '<pre>';print_r($result);die;
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    // 获取申请列表（用于选择加入订单项目列表）
    public function getreqitemslistAction()
    {
        $request = $this->getRequest()->getParams();
        
        $key = isset($request['key']) && $request['key'] != '' ? $request['key'] : null;
        $option = isset($request['option']) && $request['option'] != '' ? $request['option'] : 'data';
        
        $req = new Erp_Model_Purchse_Req();
        
        $data = $req->getReqItemsList($key, $option);
        
        if($option == 'csv'){
            $this->view->layout()->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);
            
            $h = new Application_Model_Helpers();
            $h->exportCsv($data, '采购申请列表');
        }else{
            echo Zend_Json::encode($data);
        }
        
        exit;
    }
    
    function cancelReq($id)
    {
        $result = array(
                'success'   => true,
                'info'      => '取消成功'
        );
        
        $req = new Erp_Model_Purchse_req();
        $reqData = $req->getData(null, $id);
        
        // 取消申请：如当前订单状态为被拒绝，则直接取消，否则检查是否存在下单
        if($reqData['state'] == 1){
            $req->cancelReqById($id);
        }else{
            $orderItems = new Erp_Model_Purchse_Orderitems();
            $items = $orderItems->getOrderedReqItems($reqData['number']);
             
            if(count($items)){
                $codeArr = array();
                 
                foreach ($items as $item){
                    array_push($codeArr, $item['code']);
                }
                 
                $result['success'] = false;
                $result['info'] = '取消失败，'.implode(',', $codeArr).'已下单！';
            }else{
                $req->cancelReqById($id);
            }
        }
        
        return $result;
    }
    
    public function deleteAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '删除成功'
        );
        
        $request = $this->getRequest()->getParams();
        
        $id = isset($request['id']) ? $request['id'] : null;
        
        if($id){
            $req = new Erp_Model_Purchse_req();
            $reqData = $req->getData(null, $id);
            
            if($reqData['state'] == 1){
                $req->delete("id = ".$id);
                
                $reqItems = new Erp_Model_Purchse_Reqitems();
                $reqItems->delete("req_id = ".$id);
                
                $review = new Dcc_Model_Review();
                $review->delete("type = 'purchse_req_add' and file_id = ".$id);
            }else{
                $result['success'] = false;
                $result['info'] = "删除失败，只能删除被拒绝的申请！";
            }
        }else{
            $result['success'] = false;
            $result['info'] = "ID为空，操作失败！";
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    // 取消采购申请
    public function cancelAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '取消成功'
        );
        
        $request = $this->getRequest()->getParams();
        
        if(isset($request['id'])){
            $result = $this->cancelReq($request['id']);
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
        
        $transfer_items = new Erp_Model_Purchse_Transferreqitems();
        
        $req_items = new Erp_Model_Purchse_Reqitems();
        
        $items = $transfer_items->getData($transfer_id);
        
        $req_id = null;
        
        foreach ($items as $item){
            $active = $item['items_active'] ? 1 : 0;
            $req_id = $item['items_req_id'];
            
            $data = array(
                    'req_id'                => $req_id,
                    'active'                => $active,
                    'code'                  => $item['items_code'],
                    'name'                  => $item['items_name'],
                    'description'           => $item['items_description'],
                    'qty'                   => $item['items_qty'],
                    'unit'                  => $item['items_unit'],
                    'price'                 => $item['items_price'],
                    'line_total'            => $item['items_line_total'],
                    'date_req'              => $item['items_date_req'],
                    'supplier'              => $item['items_supplier'],
                    'dept_id'               => $item['items_dept_id'],
                    'model'                 => $item['items_model'],
                    'project_info'          => $item['items_project_info'],
                    'order_req_num'         => $item['items_order_req_num'],
                    'customer_address'      => $item['items_customer_address'],
                    'customer_aggrement'    => $item['items_customer_aggrement'],
                    'remark'                => $item['items_remark'],
                    'update_user'           => $user_id,
                    'update_time'           => $now
            );
            
            if($item['items_transfer_type'] == 'add'){
                $data['create_user'] = $user_id;
                $data['create_time'] = $now;
        
                $req_items->insert($data);
            }else if($item['items_transfer_type'] == 'delete'){
                $req_items->delete("id = ".$item['items_req_item_id']);
            }else if($item['items_transfer_type'] == 'update'){
                $req_items->update($data, "id = ".$item['items_req_item_id']);
            }
        }
        
        if($req_id){
            $req_items->refreshReqTotal($req_id);
        }
    }
    
    public function testAction()
    {
        $req = new Erp_Model_Purchse_Req();
        $reqData = $req->getData(null, 1264);
        
        echo '<pre>';print_r($reqData);exit;
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
        
        $review_id = isset($request['review_id']) ? $request['review_id'] : null;
        //$review_type_id = isset($request['review_type_id']) ? $request['review_type_id'] : null;
        $review_operate = isset($request['review_operate']) ? $request['review_operate'] : null;
        $review_current_step = isset($request['review_current_step']) ? $request['review_current_step'] : null;// 当前阶段（review表ID）
        $review_last_step = isset($request['review_last_step']) ? $request['review_last_step'] : null;// 是否当前阶段为最后一阶段
        $review_to_finish = isset($request['review_to_finish']) ? $request['review_to_finish'] : null;// 是否批准后当前阶段结束
        $review_next_step = isset($request['review_next_step']) ? $request['review_next_step'] : null;// 下一阶段（review表ID）
        $review_remark = isset($request['review_remark']) ? $request['review_remark'] : null;
        $review_transfer_user = isset($request['review_transfer_user']) ? $request['review_transfer_user'] : null;//转审人
        $review_transfer = $request['review_transfer'] == 1 ? true : false;
        
        if($review_id && $review_operate){
            $transfer = new Erp_Model_Purchse_Transfer();
            
            $now = date('Y-m-d H:i:s');
            $user_session = new Zend_Session_Namespace('user');
            $user_id = $user_session->user_info['user_id'];
            $employee_id = $user_session->user_info['employee_id'];
            
            // 评审意见
            $review_info = '意见: '.$review_remark;
            
            $req = new Erp_Model_Purchse_Req();
            $user = new Application_Model_User();
            $review = new Dcc_Model_Review();
            $employee = new Hra_Model_Employee();
            
            $reqData = $req->getData(null, $review_id);
            
            // 更新审核状态及审核意见
            if($review_operate == 'transfer'){
                // 转审
                $review_info = $reqData['review_info'].'<br>'.$now.': '.$user_session->user_info['user_name'].' [审核-转审] ['.$review_info.']';
                
                $req->update(array('review_info' => $review_info), "id = ".$review_id);
                
                $reviewState = $review->fetchRow("type = 'purchse_req_add' and finish_flg = 0 and file_id = ".$review_id, "id")->toArray();
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
                
                $mailContent = '<div>采购申请审核：</div>
                                <div>
                                <p><b>审核人：</b>'.$user_session->user_info['user_name'].'</p>
                                <p><b>审核结果：</b>'.$reviewResult.'</p>
                                <p><b>审核意见：</b>'.$review_remark.'</p>
                                <p><b>申请单号：</b>'.$reqData['number'].'</p>
                                <p><b>申请部门：</b>'.$reqData['dept'].'</p>
                                <p><b>申请人：</b>'.$reqData['apply_user_name'].'</p>
                                <p><b>制单人：</b>'.$reqData['creater'].'</p>
                                <p><b>类别：</b>'.$reqData['type'].'</p>
                                <p><b>事由：</b>'.$reqData['reason'].'</p>
                                <p><b>金额：</b><a style="color: #467500;font-weight: bold;">￥'.round($reqData['total'], 2).'</a></p>
                                <p><b>备注：</b>'.$reqData['remark'].'</p>
                                <p><b>制单时间：</b>'.$reqData['create_time'].'</p>
                                <p><b>更新时间：</b>'.$reqData['update_time'].'</p>
                                <hr>
                                <p><b>审核日志：</b></p><p>'.$reqData['review_info'].'</p>
                                </div>';
                
                $mailData = array(
                        'type'      => '消息',
                        'subject'   => '采购申请-转审',
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
                // 更新采购申请状态
                $review_info = $now.': '.$user_session->user_info['user_name'].' [审核-拒绝] ['.$review_info.']';
                $data = array(
                        'state'                 => 1,
                        'transfer_description'  => null,
                        'review_info'           => $reqData['review_info'].'<br>'.$review_info
                );
                
                $req->update($data, "id = ".$review_id);
                
                if($review_transfer){
                    $transfer->update(array('state' => 1), "id = ".$reqData['transfer_id']);
                }
                
                // 删除当前申请的审核配置
                $review->delete("type = 'purchse_req_add' and file_id = ".$review_id);
                
                // 发送邮件通知制单人
                $reviewResult = '<font style="color: #FF0000"><b>拒绝</b></font>';
                
                $mail = new Application_Model_Log_Mail();
                
                $applyEmployeeData = $user->fetchRow("id = ".$reqData['create_user'])->toArray();
                $applyEmployee = $employee->fetchRow("id = ".$applyEmployeeData['employee_id'])->toArray();
                $to = $applyEmployee['email'];
                
                $mailContent = '<div>采购申请审核：</div>
                                <div>
                                <p><b>审核人：</b>'.$user_session->user_info['user_name'].'</p>
                                <p><b>审核结果：</b>'.$reviewResult.'</p>
                                <p><b>审核意见：</b>'.$review_remark.'</p>
                                <p><b>申请单号：</b>'.$reqData['number'].'</p>
                                <p><b>申请部门：</b>'.$reqData['dept'].'</p>
                                <p><b>申请人：</b>'.$reqData['apply_user_name'].'</p>
                                <p><b>制单人：</b>'.$reqData['creater'].'</p>
                                <p><b>类别：</b>'.$reqData['type'].'</p>
                                <p><b>事由：</b>'.$reqData['reason'].'</p>
                                <p><b>金额：</b><a style="color: #467500;font-weight: bold;">￥'.round($reqData['total'], 2).'</a></p>
                                <p><b>备注：</b>'.$reqData['remark'].'</p>
                                <p><b>制单时间：</b>'.$reqData['create_time'].'</p>
                                <p><b>更新时间：</b>'.$reqData['update_time'].'</p>
                                <hr>
                                <p><b>审核日志：</b></p><p>'.$reqData['review_info'].'</p>
                                </div>';
                
                $mailData = array(
                        'type'      => '消息',
                        'subject'   => '采购申请-审核',
                        'to'        => $to,
                        'cc'        => $user_session->user_info['user_email'],
                        'user_id'   => $reqData['create_user'],
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
                $reqUpdateData = array(
                        'review_info'   => $reqData['review_info'].'<br>'.$review_info
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
                        if($review_transfer){
                            if($reqData['transfer_type'] == '取消'){
                                $this->cancelReq($review_id);
                            }else{
                                $this->approveTransferUpdateItems($reqData['transfer_id']);
                            }
                            
                            $transfer->update(array('state' => 2), "id = ".$reqData['transfer_id']);
                        }
                        
                        $data = array(
                                'actual_user'   => $actual_user,
                                'finish_time'   => $now,
                                'finish_flg'    => 1
                        );
                        
                        $reviewResult = '<font style="color: #006400"><b>发布</b></font>';
                        
                        // 发布
                        
                        // 更新申请状态
                        $reqUpdateData['state'] = 2;
                        $reqUpdateData['transfer_description'] = null;
                        $reqUpdateData['release_time'] = $now;
                        
                        // 更新审核记录表
                        $review->update($data, "id = ".$review_current_step);
                        
                        $mail = new Application_Model_Log_Mail();
                        
                        $applyEmployeeData = $user->fetchRow("id = ".$reqData['create_user'])->toArray();
                        $applyEmployee = $employee->fetchRow("id = ".$applyEmployeeData['employee_id'])->toArray();
                        $to = $applyEmployee['email'];
                        $cc = $user_session->user_info['user_email'];
                        
                        // 发布通知采购员
                        $buyer = new Erp_Model_Purchse_Buyer();
                        $buyerData = $buyer->getData();
                        
                        foreach ($buyerData as $b){
                            $cc .= ','.$b['email'];
                        }
                        
                        $mailContent = '<div>采购申请审核批准，请登录系统查看：</div>
                                <div>
                                <p><b>审核人：</b>'.$user_session->user_info['user_name'].'</p>
                                <p><b>审核结果：</b>'.$reviewResult.'</p>
                                <p><b>审核意见：</b>'.$review_remark.'</p>
                                <p><b>申请单号：</b>'.$reqData['number'].'</p>
                                <p><b>申请部门：</b>'.$reqData['dept'].'</p>
                                <p><b>申请人：</b>'.$reqData['apply_user_name'].'</p>
                                <p><b>制单人：</b>'.$reqData['creater'].'</p>
                                <p><b>类别：</b>'.$reqData['type'].'</p>
                                <p><b>事由：</b>'.$reqData['reason'].'</p>
                                <p><b>金额：</b><a style="color: #467500;font-weight: bold;">￥'.round($reqData['total'], 2).'</a></p>
                                <p><b>备注：</b>'.$reqData['remark'].'</p>
                                <p><b>制单时间：</b>'.$reqData['create_time'].'</p>
                                <p><b>更新时间：</b>'.$reqData['update_time'].'</p>
                                <hr>
                                <p><b>审核日志：</b></p><p>'.$reqUpdateData['review_info'].'</p>
                                </div>';
                        
                        $mailData = array(
                                'type'      => '消息',
                                'subject'   => '采购申请-发布',
                                'to'        => $to,
                                'cc'        => $cc,
                                'user_id'   => $reqData['create_user'],
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
                        $reviewNextStepData = $review->fetchRow("id = ".$review_next_step)->toArray();
                        
                        $mailTo = explode(',', $reviewNextStepData['plan_user']);
                        
                        if($mailTo){
                            $mailContent = '<div>新建采购申请，请登录系统查看：</div>
                                            <div>
                                            <p><b>申请单号：</b>'.$reqData['number'].'</p>
                                            <p><b>申请部门：</b>'.$reqData['dept'].'</p>
                                            <p><b>申请人：</b>'.$reqData['apply_user_name'].'</p>
                                            <p><b>制单人：</b>'.$reqData['creater'].'</p>
                                            <p><b>类别：</b>'.$reqData['type'].'</p>
                                            <p><b>事由：</b>'.$reqData['reason'].'</p>
                                            <p><b>金额：</b><a style="color: #467500;font-weight: bold;">￥'.round($reqData['total'], 2).'</a></p>
                                            <p><b>备注：</b>'.$reqData['remark'].'</p>
                                            <p><b>制单时间：</b>'.$reqData['create_time'].'</p>
                                            <p><b>更新时间：</b>'.$reqData['update_time'].'</p>
                                            <hr>
                                            <p><b>审核日志：</b></p><p>'.$reqUpdateData['review_info'].'</p>
                                            </div>';
                            
                            $mailData = array(
                                    'type'      => '消息',
                                    'subject'   => '采购申请-新申请',
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
                $req->update($reqUpdateData, "id = ".$review_id);
            }
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }

    /**
     * 根据申购单ID获取项目列表
     */
    public function getreqitemsAction()
    {
        $data = array();
    
        $request = $this->getRequest()->getParams();
    
        $req_id = isset($request['req_id']) ? $request['req_id'] : 0;
    
        if($req_id > 0){
            $items = new Erp_Model_Purchse_Reqitems();
    
            $data = $items->getData($req_id);
        }
    
        echo Zend_Json::encode($data);
    
        exit;
    }

    /**
     * 根据申购单ID获取项目列表
     */
    public function getreqtransferitemsAction()
    {
        $data = array();
    
        $request = $this->getRequest()->getParams();
    
        $transfer_id = isset($request['transfer_id']) ? $request['transfer_id'] : 0;
    
        if($transfer_id > 0){
            $items = new Erp_Model_Purchse_Transferreqitems();
    
            $data = $items->getData($transfer_id);
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
    
    // 获取采购类别列表
    public function gettypeAction()
    {
        // 请求参数
        $request = $this->getRequest()->getParams();
    
        $option = isset($request['option']) ? $request['option'] : 'list';
    
        $type = new Erp_Model_Purchse_Type();
    
        if($option == 'list'){
            echo Zend_Json::encode($type->getList());
        }else{
            echo Zend_Json::encode($type->getData());
        }
    
        exit;
    }
    
    // 获取模板列表
    public function gettplAction()
    {
        // 请求参数
        $request = $this->getRequest()->getParams();
    
        $type = isset($request['type']) ? $request['type'] : null;
        $option = isset($request['option']) ? $request['option'] : 'list';
    
        $tpl = new Erp_Model_Tpl();
    
        if($option == 'list'){
            echo Zend_Json::encode($tpl->getList($type));
        }else{
            echo Zend_Json::encode($tpl->getData($type));
        }
    
        exit;
    }
    
    // 获取模板类别列表
    public function gettpltypeAction()
    {
        $tpl = new Erp_Model_Tpl();
    
        echo Zend_Json::encode($tpl->getTypeList());
    
        exit;
    }
    
    /**
     * 编辑采购类别信息
     */
    public function edittypeAction()
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
    
        $type = new Erp_Model_Purchse_Type();
    
        if(count($updated) > 0){
            foreach ($updated as $val){
                $data = array(
                        'active'        => $val->active,
                        'chk_package_qty'   => $val->chk_package_qty,
                        'code'          => $val->code,
                        'name'          => $val->name,
                        'tpl_id'        => $val->tpl_id,
                        'req_flow_id'   => $val->req_flow_id,
                        'order_flow_id' => $val->order_flow_id,
                        'description'   => $val->description,
                        'remark'        => $val->remark,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
    
                if($type->fetchAll("id != ".$val->id." and (name = '".$val->name."' or code = '".$val->code."')")->count() > 0){
                    $result['success'] = false;
                    $result['info'] = '采购类别：'.$val->name."已存在，请勿重复添加！";
                }else{
                    try {
                        $type->update($data, "id = ".$val->id);
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
                        'chk_package_qty'   => $val->chk_package_qty,
                        'code'          => $val->code,
                        'name'          => $val->name,
                        'tpl_id'        => $val->tpl_id,
                        'req_flow_id'   => $val->req_flow_id,
                        'order_flow_id' => $val->order_flow_id,
                        'description'   => $val->description,
                        'remark'        => $val->remark,
                        'create_time'   => $now,
                        'create_user'   => $user_id,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
    
                if($type->fetchAll("name = '".$val->name."' or code = '".$val->code."'")->count() > 0){
                    $result['success'] = false;
                    $result['info'] = '采购类别：'.$val->name."已存在，请勿重复添加！";
                }else{
                    try{
                        $type->insert($data);
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
            $req = new Erp_Model_Purchse_Req();
    
            foreach ($deleted as $val){
                if($req->fetchAll("type_id = ".$val->id)->count() == 0){
                    try {
                        $type->delete("id = ".$val->id);
                    } catch (Exception $e){
                        $result['success'] = false;
                        $result['info'] = $e->getMessage();
    
                        echo Zend_Json::encode($result);
    
                        exit;
                    }
                }else{
                    $result['success'] = false;
                    $result['info'] = '采购类别'.$val->name.'已使用，不能删除';
    
                    echo Zend_Json::encode($result);
    
                    exit;
                }
            }
        }
    
        echo Zend_Json::encode($result);
    
        exit;
    }
    
    /**
     * 获取采购申请列表
     */
    public function getreqAction()
    {
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        $option = isset($request['option']) ? $request['option'] : 'data';
        
        $req = new Erp_Model_Purchse_Req();
        
        if($option == 'list'){
            echo Zend_Json::encode($req->getList());
        }else{
            // 查询条件
            $condition = array(
                    'key'       => isset($request['key']) ? $request['key'] : '',
                    'applier'     => isset($request['applier']) ? $request['applier'] : '',
                    'date_from' => isset($request['date_from']) ? $request['date_from'] : null,
                    'date_to'   => isset($request['date_to']) ? $request['date_to'] : null,
                    'active'    => (isset($request['active']) && $request['active'] != 'null') ? $request['active'] : 1,
                    'state'     => (isset($request['state']) && $request['state'] != 'null') ? $request['state'] : null,
                    'type'      => (isset($request['type']) && $request['type'] != 'null') ? $request['type'] : null,
                    'dept'      => (isset($request['dept']) && $request['dept'] != 'null') ? $request['dept'] : null,
                    'page'      => isset($request['page']) ? $request['page'] : 1,
                    'limit'     => isset($request['limit']) ? $request['limit'] : 0
            );
            
            echo Zend_Json::encode($req->getData($condition));
        }
    
        exit;
    }
    
    /**
     * 编辑采购申请（新建、更新、删除）
     */
    public function editreqAction()
    {
        // 返回值数组
        $result = array(
                'success'       => true,
                'info'          => '提交成功',
                'req_id'        => 0,
                'transfer_id'   => 0
        );
    
        $request = $this->getRequest()->getParams();
        
        $typeArr = array(
                'new'       => '新建',
                'edit'      => '修改',
                'transfer'  => '变更'
        );
        
        // 操作类别（新建、更新、删除）
        $type = isset($request['operate']) ? $request['operate'] : '';
        
        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user_id = $user_session->user_info['user_id'];
    
        $req = new Erp_Model_Purchse_Req();
    
        if($type == 'new' || $type == 'edit' || $type == 'transfer'){
            $hand = 0;
            if(isset($request['hand']) && $request['hand'] == 'on'){
                $hand = 1;
            }
            
            $data = array(
                    'dept_id'       => $request['dept_id'],
                    'apply_user'    => $request['apply_user'],
                    'type_id'       => $request['type_id'],
                    'remark'        => $request['remark'],
                    'reason'        => $request['reason'],
                    'update_time'   => $now,
                    'update_user'   => $user_id
            );
            
            if ($type == 'new') {
                if($hand){
                    $data['state'] = 2;
                    $data['hand'] = 1;
                    $data['number'] = $request['hand_number'];
                    $data['review_info'] = date('Y-m-d H:i:s').' 补单<br>';
                
                    if($req->fetchAll("number = '".$data['number']."'")->count() > 0){
                        $result['success'] = false;
                        $result['info'] = "添加错误，申请单号重复！";
                
                        echo Zend_Json::encode($result);
                
                        exit;
                    }
                }else{
                    $data['hand'] = 0;
                    $data['number'] = $req->getNewNum($request['type_id']);// 生成申订单号
                }
                
                $data['create_time'] = $now;
                $data['create_user'] = $user_id;
                $data['review_info'] = $now.': '.$user_session->user_info['user_name'].' ['.$typeArr[$type].']';
    
                try{
                    $req_id = $result['req_id'] = $req->insert($data);
                } catch (Exception $e){
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
                }
            }elseif ($type == 'edit' || $type == 'transfer'){
                try {
                    $review_info = $now.': '.$user_session->user_info['user_name'].' ['.$typeArr[$type].']';
                    $reqData = $req->getData(null, $request['id']);
                    
                    if($request['type_id'] != $reqData['type_id']){
                        // 当类别发送改变时，生成申请单号
                        $data['number'] = $req->getNewNum($request['type_id']);
                    }
                    
                    $data['review_info'] = $reqData['review_info'].'<br>'.$review_info;
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
                            $transfer_items = new Erp_Model_Purchse_Transferreqitems();
                            $transfer_items->delete("transfer_id = ".$request['transfer_id']);
                            
                            $transferData['state'] = 0;
                            $transfer->update($transferData, "id = ".$request['transfer_id']);
                            
                            $data['transfer_id'] = $result['transfer_id'] = $request['transfer_id'];
                        }else{
                            $transferData['type'] = 'req';
                            $transferData['target_id'] = $request['id'];
                            
                            $data['transfer_id'] = $result['transfer_id'] = $transfer->insert($transferData);
                        }
                    }
                    
                    $req->update($data, "id = ".$request['id']);
                    $result['req_id'] = $request['id'];
                } catch (Exception $e) {
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
                }
            }
        }elseif ($type == 'delete'){
            try {
                $req->delete("id = ".$request['req_id']);
            } catch (Exception $e){
                $result['success'] = false;
                $result['info'] = $e->getMessage();
            }
        }
        
        echo Zend_Json::encode($result);
    
        exit;
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
                'new'       => '新建',
                'edit'      => '修改',
                'transfer'  => '变更'
        );
    
        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user_id = $user_session->user_info['user_id'];
    
        $json = json_decode($request['json']);
    
        $req_id = $json->req_id;
        $transfer_id = $json->transfer_id;
        
        $json_items = $json->items;
    
        $items_updated    = $json_items->updated;
        $items_inserted   = $json_items->inserted;
        $items_deleted    = $json_items->deleted;
        
        $items = new Erp_Model_Purchse_Reqitems();
        $transfer_items = new Erp_Model_Purchse_Transferreqitems();
        
        // 更新
        if(count($items_updated) > 0){
            foreach ($items_updated as $val){
                $active = $val->items_active ? 1 : 0;
                
                $line_total = round($val->items_qty * $val->items_price, 2);
    
                $data = array(
                        'active'                => $active,
                        'code'                  => $val->items_code,
                        'name'                  => $val->items_name,
                        'description'           => $val->items_description,
                        'qty'                   => $val->items_qty,
                        'unit'                  => $val->items_unit,
                        'price'                 => $val->items_price,
                        'line_total'            => $line_total,
                        'date_req'              => $val->items_date_req,
                        'supplier'              => $val->items_supplier,
                        'dept_id'               => $val->items_dept_id,
                        'model'                 => $val->items_model,
                        'project_info'          => $val->items_project_info,
                        'order_req_num'         => $val->items_order_req_num,
                        'customer_address'      => $val->items_customer_address,
                        'customer_aggrement'    => $val->items_customer_aggrement,
                        'remark'                => $val->items_remark
                );
                
                try {
                    if($type == 'transfer'){
                        $data['req_id'] = $req_id;
                        $data['transfer_id'] = $transfer_id;
                        $data['req_item_id'] = $val->items_id;
                        $data['transfer_type'] = 'update';
                        
                        $transfer_items->insert($data);
                    }else{
                        $data['update_user'] = $user_id;
                        $data['update_time'] = $now;
                        
                        $items->update($data, "id = ".$val->items_id);
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
                
                $line_total = round($val->items_qty * $val->items_price, 2);
                
                $data = array(
                        'req_id'                => $req_id,
                        'active'                => $active,
                        'code'                  => $val->items_code,
                        'name'                  => $val->items_name,
                        'description'           => $val->items_description,
                        'qty'                   => $val->items_qty,
                        'unit'                  => $val->items_unit,
                        'price'                 => $val->items_price,
                        'line_total'            => $line_total,
                        'date_req'              => $val->items_date_req,
                        'supplier'              => $val->items_supplier,
                        'dept_id'               => $val->items_dept_id,
                        'model'                 => $val->items_model,
                        'project_info'          => $val->items_project_info,
                        'order_req_num'         => $val->items_order_req_num,
                        'customer_address'      => $val->items_customer_address,
                        'customer_aggrement'    => $val->items_customer_aggrement,
                        'remark'                => $val->items_remark
                );
                
                try {
                    if($type == 'transfer'){
                        $data['transfer_id'] = $transfer_id;
                        $data['req_item_id'] = $val->items_id;
                        $data['transfer_type'] = 'add';
                        
                        $transfer_items->insert($data);
                    }else{
                        $data['create_user'] = $user_id;
                        $data['create_time'] = $now;
                        $data['update_user'] = $user_id;
                        $data['update_time'] = $now;
                        
                        $items->insert($data);
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
                        
                        $line_total = round($val->items_qty * $val->items_price, 2);
                        
                        $data = array(
                                'req_id'                => $req_id,
                                'transfer_type'         => 'delete',
                                'transfer_id'           => $transfer_id,
                                'req_item_id'           => $val->items_id,
                                'active'                => $active,
                                'code'                  => $val->items_code,
                                'name'                  => $val->items_name,
                                'description'           => $val->items_description,
                                'qty'                   => $val->items_qty,
                                'unit'                  => $val->items_unit,
                                'price'                 => $val->items_price,
                                'line_total'            => $line_total,
                                'date_req'              => $val->items_date_req,
                                'supplier'              => $val->items_supplier,
                                'dept_id'               => $val->items_dept_id,
                                'model'                 => $val->items_model,
                                'project_info'          => $val->items_project_info,
                                'order_req_num'         => $val->items_order_req_num,
                                'customer_address'      => $val->items_customer_address,
                                'customer_aggrement'    => $val->items_customer_aggrement,
                                'remark'                => $val->items_remark
                        );
                        
                        $transfer_items->insert($data);
                    }else{
                        $items->delete("id = ".$val->items_id);
                    }
                } catch (Exception $e){
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
    
                    echo Zend_Json::encode($result);
    
                    exit;
                }
            }
        }
        
        // 更新采购申请总计
        if($type != 'transfer'){
            $items->refreshReqTotal($req_id);
        }
        
        $req = new Erp_Model_Purchse_Req();
        $req_data = $req->getData(null, $req_id);
        
        // 保存成功，进入审批流程
        if($result['success'] && $req_data['hand'] == 0){
            // 根据流程ID获取阶段信息
            $flow = new Admin_Model_Flow();
            $flowData = $flow->fetchRow("id = ".$req_data['req_flow_id'])->toArray();
            // 获取审核阶段
            $step = new Admin_Model_Step();
            $stepIds = $flowData['step_ids'];
            $stepArr = explode(',', $stepIds);
            
            $review = new Dcc_Model_Review();
            $review->delete("type = 'purchse_req_add' and file_id = ".$req_id);
            
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
                        'type'      => 'purchse_req_add',
                        'file_id'   => $req_id,
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
                $mailContent = '<div>采购申请 - '.$typeArr[$type].'，请登录系统查看：</div>
                            <div>
                            <p><b>申请单号：</b>'.$req_data['number'].'</p>
                            <p><b>申请部门：</b>'.$req_data['dept'].'</p>
                            <p><b>申请人：</b>'.$req_data['apply_user_name'].'</p>
                            <p><b>制单人：</b>'.$user_session->user_info['user_name'].'</p>
                            <p><b>类别：</b>'.$req_data['type'].'</p>
                            <p><b>事由：</b>'.$req_data['reason'].'</p>
                            <p><b>金额：</b><a style="color: #467500;font-weight: bold;">￥'.round($req_data['total'], 2).'</a></p>
                            <p><b>备注：</b>'.$req_data['remark'].'</p>
                            <p><b>制单时间：</b>'.$req_data['create_time'].'</p>
                            <p><b>更新时间：</b>'.$req_data['update_time'].'</p>
                            </div><hr>';
                
                $transferContent = '';
                
                if($type == 'transfer'){
                    $transferContent .= '<hr><div><style type="text/css">
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
                            <th>已下单</th>
                            <th>需求日期</th>
                            <th>项目信息</th>
                            <th>订货产品出库申请号</th>
                            <th>客户收件人地址简码</th>
                            <th>客户合同号</th>
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
                            <td>'.date('Y-m-d', strtotime($val->items_date_req)).'</td>
                            <td>'.$val->items_project_info.'</td>
                            <td>'.$val->items_order_req_num.'</td>
                            <td>'.$val->items_customer_address.'</td>
                            <td>'.$val->items_customer_aggrement.'</td>
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
                            <td>'.$val->items_qty_order.'</td>
                            <td>'.$val->items_date_req.'</td>
                            <td>'.$val->items_project_info.'</td>
                            <td>'.$val->items_order_req_num.'</td>
                            <td>'.$val->items_customer_address.'</td>
                            <td>'.$val->items_customer_aggrement.'</td>
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
                            <td>'.$val->items_date_req.'</td>
                            <td>'.$val->items_project_info.'</td>
                            <td>'.$val->items_order_req_num.'</td>
                            <td>'.$val->items_customer_address.'</td>
                            <td>'.$val->items_customer_aggrement.'</td>
                            <td>'.$val->items_remark.'</td>
                            </tr>';
                        
                        $i++;
                    }
                    
                    $transferContent .= '</table></div>';
                }
                
                if($transferContent != ''){
                    $transfer = new Erp_Model_Purchse_Transfer();
                    $transfer->update(array('transfer_content' => $transferContent), "id = ".$transfer_id);
                }
                
                $mailData = array(
                        'type'      => '消息',
                        'subject'   => '采购申请-'.$typeArr[$type],
                        'cc'        => $user_session->user_info['user_email'],
                        'content'   => $mailContent.$transferContent,
                        'add_date'  => $now
                );
                
                $result = $help->sendMailToStep($mailTo, $mailData);
            }
        }
        
        echo Zend_Json::encode($result);
    
        exit;
    }
    
    /**
     * 根据业务伙伴ID获取联系方式列表
     */
    public function getcontactAction()
    {
        $data = array();
    
        $request = $this->getRequest()->getParams();
    
        $partner_id = isset($request['partner_id']) ? $request['partner_id'] : 0;
    
        if($partner_id > 0){
            $contact = new Erp_Model_Contact();
    
            $record = $contact->fetchAll("partner_id = ".$partner_id)->toArray();
    
            foreach ($record as $rec){
                $active = $rec['active'] == 1 ? true : false;
    
                array_push($data, array(
                'contact_id'            => $rec['id'],
                'contact_partner_id'    => $rec['partner_id'],
                'contact_active'        => $active,
                'contact_name'          => $rec['name'],
                'contact_post'          => $rec['post'],
                'contact_tel'           => $rec['tel'],
                'contact_fax'           => $rec['fax'],
                'contact_email'         => $rec['email'],
                'contact_country'       => $rec['country'],
                'contact_area'          => $rec['area'],
                'contact_address'       => $rec['address'],
                'contact_zip_code'      => $rec['zip_code'],
                'contact_remark'        => $rec['remark']
                ));
            }
        }
    
        echo Zend_Json::encode($data);
    
        exit;
    }
    
    public function gettransferinfoAction()
    {
        $info = '';
        
        $request = $this->getRequest()->getParams();
        
        $id = isset($request['id']) && $request['id'] != '' ? $request['id'] : null;
        
        if($id){
            $transfer = new Erp_Model_Purchse_Transfer();
            
            $transferData = $transfer->getTransfer('req', $id);
            
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
                
                $info .= '<div style="font-size: 12px;">['.$i.'] ['.$stateInfo.'] [用户：'.$t['creater'].'] [时间：'.$t['create_time'].'] [<b>类别：'.$t['transfer_type'].'</b>] [说明：'.$t['transfer_description'].']</div>'.$content.'<hr>';
                
                $i--;
            }
            
            $result['info'] = $info;
        }
        
        $info = $info == '' ? '无' : $info;
        
        echo $info;
        
        exit;
    }
}