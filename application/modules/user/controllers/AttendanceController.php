<?php
/**
 * 2013-8-12 下午4:09:44
 * @author x.li
 * @abstract
 */
class User_AttendanceController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $user_session = new Zend_Session_Namespace('user');
        $user_id = $user_session->user_info['user_id'];
        $user_number = $user_session->user_info['user_number'];
        
        $this->view->hraAdmin = 0;
        
        $this->view->user_id = 0;
        
        $request = $this->getRequest()->getParams();
        
        $this->view->active_tab = isset($request['active_tab']) ? $request['active_tab'] : 0;
        
        if(isset($user_session->user_info)){
            $this->view->user_id = $user_id;
        
            if(Application_Model_User::checkPermissionByRoleName('系统管理员') || Application_Model_User::checkPermissionByRoleName('人事主管')){
                $this->view->hraAdmin = 1;
            }
        }
        
        $user = new Application_Model_User();
        $member = new Admin_Model_Member();
        
        // 获取部门主管
        $manager = $user->getManagerUser($user_session->user_info['user_id']);
        $this->view->default_manager_id = $manager[0]['id'];
        
        // 获取总经理
        /* $leader = $member->getMemberWithNoManagerByName('总经理');
        $this->view->default_leader_id = $leader[0]['user_id']; */
        
        // 获取公司领导
        /* $leader = $user->getLeaderList();
        $this->view->default_leader_id = $leader[0]['id']; */
        
        // 获取当前用户剩余假期
        $this->view->vacation_qty_left = $this->getVacaionLeftQty($user_id);
        
        // 获取默认日期范围（最近3个月）
        $time = time();
        $this->view->default_date_from = date('Y-m-01',strtotime(date('Y',$time).'-'.(date('m',$time)-1).'-01'));
        $this->view->default_date_to = date('Y-m-t',strtotime(date('Y',$time).'-'.(date('m',$time)+1).'-01'));
    }
    
    public function getdeptmanagerAction()
    {
        $result = array(
                'success'       => true,
                'manager_id'    => 41,
                'info'          => ''
        );
        
        $request = $this->getRequest()->getParams();
        
        $user_id = isset($request['user_id']) ? $request['user_id'] : null;
        
        if($user_id){
            $user = new Application_Model_User();
            $manager = $user->getManagerUser($user_id);
            
            if($manager[0]['id'] > 0){
                $result['manager_id'] = $manager[0]['id'];
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
        
        $id = isset($request['id']) ? $request['id'] : null;
        
        if($id && isset($_FILES['attach_file'])){
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
            
            $vacation = new Hra_Model_Attendance_Vacation();
            $vacationData = $vacation->getData(null, $id);
            
            if($vacationData['attach_path'] != '' && file_exists($vacationData['attach_path'])){
                unlink($vacationData['attach_path']);
            }
            
            $data = array(
                    'attach_name'   => $file_name,
                    'attach_path'   => $tmp_file_path,
                    'update_user'   => $user_id,
                    'update_time'   => $now
            );
            
            $vacation->update($data, "id = ".$id);
        }
    
        echo Zend_Json::encode($result);
    
        exit;
    }
    
    // 获取当前用户剩余假期
    private function getVacaionLeftQty($user_id)
    {
        $user = new Application_Model_User();
        
        $employeeInfo = $user->getEmployeeInfoById($user_id);
        
        $storage = new Hra_Model_Vacationstorage();
        $storage->refreshStorage($employeeInfo['id']);// 刷新当前员工最近一年的年假（如果没有刷新过的话）
        $qty = $storage->getVacationQty($employeeInfo['number']);
        
        return $qty['qty'];
    }
    
    public function getleftvacationAction()
    {
        $result = array(
                'success'   => true,
                'qty'       => 0,
                'info'      => '审核成功'
        );
        
        $request = $this->getRequest()->getParams();
        
        $user_id = isset($request['user_id']) ? $request['user_id'] : null;
        
        if($user_id){
            $result['qty'] = $this->getVacaionLeftQty($user_id);
        }else {
            $result['success'] = false;
            $result['info'] = '用户ID为空，获取失败！';
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    // 获取可用调休加班列表
    public function getexchangelistAction()
    {
        $request = $this->getRequest()->getParams();
        
        $user_id = isset($request['user_id']) ? $request['user_id'] : null;
        
        $overtime = new Hra_Model_Attendance_Overtime();
        
        $data = $overtime->getExchangeQty($user_id);
        
        echo Zend_Json::encode($data);
        
        exit;
    }
    
    public function testAction()
    {
        $employeeModel = new Hra_Model_Employee();
        $manager = $employeeModel->getManagerByUserId(0);
        echo '<pre>';print_r($manager);exit;
    }
    
    // 审核
    public function reviewAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '审核成功'
        );
        
        $request = $this->getRequest()->getParams();
        
        $review_type = isset($request['review_type']) ? $request['review_type'] : null;
        $review_step = isset($request['review_step']) ? $request['review_step'] : null;
        $review_id = isset($request['review_id']) ? $request['review_id'] : null;
        $review_operate = isset($request['review_operate']) ? $request['review_operate'] : null;
        $review_remark = isset($request['review_remark']) ? $request['review_remark'] : null;
        
        if($review_id && $review_type){
            $now = date('Y-m-d H:i:s');
            $user_session = new Zend_Session_Namespace('user');
            $user_id = $user_session->user_info['user_id'];
            
            // 评审意见
            $review_info = '意见: '.$review_remark;
            
            $user = new Application_Model_User();
            $review = new Dcc_Model_Review();
            $employee = new Hra_Model_Employee();
            
            if($review_type == 'vacation'){
                $vacation = new Hra_Model_Attendance_Vacation();
                $vacationData = $vacation->getData(null, $review_id);
                
                if($review_operate == 'no'){
                    // 拒绝
                    $review_info = $now.': '.$user_session->user_info['user_name'].' [审核-拒绝] ['.$review_info.']';
                    
                    $timeType = 'review_time_1';
                    if($vacationData['review_time_1']){
                        $timeType = 'review_time_2';
                    }
                    
                    $data = array(
                            'state'         => 1,
                            $timeType       => date('Y-m-d H:i:s'),
                            'review_info'   => $vacationData['review_info'].'<br>'.$review_info
                    );
                    
                    $vacation->update($data, "id = ".$review_id);
                    // 删除当前申请的审核配置
                    $review->delete("type = 'attendance_vacation' and file_id = ".$review_id);
                    
                    // 调休假申请被拒绝后：还原对调加班时间的调休状态
                    if($vacationData['type'] == 8){
                        $overtimeIdArr = explode(',', $vacationData['exchange_overtime_ids']);
                        
                        $overtime = new Hra_Model_Attendance_Overtime();
                        
                        foreach ($overtimeIdArr as $overtimeId){
                            $overtime->update(array('exchange' => 0), "id = ".$overtimeId);
                        }
                    }
                    
                    // 发送邮件通知申请人
                    $reviewResult = '<font style="color: #FF0000"><b>拒绝</b></font>';
                    
                    $mail = new Application_Model_Log_Mail();
                    
                    $applyEmployeeData = $user->fetchRow("id = ".$vacationData['create_user'])->toArray();
                    $applyEmployee = $employee->fetchRow("id = ".$applyEmployeeData['employee_id'])->toArray();
                    $to = $applyEmployee['email'];
                    
                    $mailContent = '<div>请假申请审核：</div>
                                    <div>
                                    <p><b>审核人：</b>'.$user_session->user_info['user_name'].'</p>
                                    <p><b>审核结果：</b>'.$reviewResult.'</p>
                                    <p><b>审核意见：</b>'.$review_remark.'</p>
                                    <p><b>申请人：</b><a style="color:#008B00;font-weight: bold;">'.$vacationData['apply_user_name'].'</a></p>
                                    <p><b>请假类别：</b>'.$vacationData['type_name'].'</p>
                                    <p><b>请假时间：</b><a style="color:#008B00;font-weight: bold;">'.$vacationData['time_from'].'</a> 至 <a style="color:#008B00;font-weight: bold;">'.$vacationData['time_to'].'</a></p>
                                    <p><b>代理人：</b>'.$vacationData['agent_name'].'</p>
                                    <p><b>事由：</b>'.$vacationData['reason'].'</p>
                                    <p><b>工作交接：</b>'.$vacationData['work'].'</p>
                                    <p><b>备注：</b>'.$vacationData['remark'].'</p>
                                    <p><b>创建人：</b>'.$vacationData['creater'].'</p>
                                    <p><b>申请时间：</b>'.$vacationData['create_time'].'</p>
                                    <hr>
                                    <p><b>审核日志：</b></p><p>'.$data['review_info'].'</p>
                                    </div>';
                    
                    $mailData = array(
                            'type'      => '消息',
                            'subject'   => '请假申请-审核',
                            'to'        => $to,
                            'user_id'   => $vacationData['create_user'],
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
                    // 批准
                    $review_info = $now.': '.$user_session->user_info['user_name'].' [审核-批准] ['.$review_info.']';
                    
                    $reviewResult = '<font style="color: #006400"><b>批准</b></font>';
                    
                    $updateData = array();
                    
                    $toArr = array();
                    
                    $subject = '审核';
                    
                    $mailCc = null;
                    
                    if($review_step == 'review_1'){
                        $step_name = '审核人1';
                        
                        if($vacationData['review_user_2'] > 0 && $vacationData['review_time_1'] == ''){
                            // 二级审核，当前申请批准后进入第二级审核
                            array_push($toArr, $vacationData['review_employee_2_id']);
                            
                            $updateData = array(
                                    'review_time_1' => $now,
                                    'review_info'   => $vacationData['review_info'].'<br>'.$review_info
                            );
                        }else{
                            // 一级审核，当前申请批准后进入HRA审核
                            $member = new Admin_Model_Member();
                            $hraAdminUserArr = $member->getMemberWithNoManagerByName('人事主管');
                            $hraEmployeeIdArr = array();
                            
                            foreach ($hraAdminUserArr as $hra){
                                $hra_info = $user->getEmployeeInfoById($hra['user_id']);
                                array_push($toArr, $hra_info['id']);
                            }
                            
                            if (isset($vacationData['manager_id']) && $vacationData['manager_id'] != '') {
                                array_push($toArr, $vacationData['manager_id']);
                            }
                            
                            $updateData = array(
                                    'state'         => 2,
                                    'review_time_1' => $now,
                                    'review_info'   => $vacationData['review_info'].'<br>'.$review_info
                            );
                        }
                    }else if($review_step == 'review_2'){
                        // 二级审核，当前申请批准后进入HRA审核
                        $step_name = '审核人2';
                        
                        $member = new Admin_Model_Member();
                        $hraAdminUserArr = $member->getMemberWithNoManagerByName('人事主管');
                        $hraEmployeeIdArr = array();
                        
                        foreach ($hraAdminUserArr as $hra){
                            $hra_info = $user->getEmployeeInfoById($hra['user_id']);
                            array_push($toArr, $hra_info['id']);
                        }
                        
                        if (isset($vacationData['manager_id']) && $vacationData['manager_id'] != '') {
                            array_push($toArr, $vacationData['manager_id']);
                        }
                        
                        $updateData = array(
                                'state'         => 2,
                                'review_time_2' => $now,
                                'review_info'   => $vacationData['review_info'].'<br>'.$review_info
                        );
                    }else if($review_step == 'review_hra'){
                        // HRA审核，当前申请批准后发布
                        $step_name = '人事审核';
                        
                        $reviewResult = '<font style="color: #006400"><b>发布</b></font>';
                        
                        $subject = '发布';
                        
                        array_push($toArr, $vacationData['apply_employee_id']);
                        
                        $updateData = array(
                                'state'         => 3,
                                'release_time'  => $now,
                                'release_user'  => $user_id,
                                'review_info'   => $vacationData['review_info'].'<br>'.$review_info
                        );
                        
                        $mailCc = $vacationData['agent_email'];
                        
                        if ($mailCc != '') {
                            if ($vacationData['manager_email'] != '') {
                                $mailCc .= ','.$vacationData['manager_email'];
                            }
                        }else{
                            $mailCc = $vacationData['manager_email'];
                        }
                        
                        if($vacationData['type'] == 2){
                            // 当年假申请批准后，更新年假库的已用时间：根据员工工号以及入司年数
                            $vacationStorage = new Hra_Model_Vacationstorage();
                        
                            $vacationStorage->update(array('qty_used' => new Zend_Db_Expr("qty_used + ".$vacationData['qty'])), "number = '".$vacationData['number']."' and in_year_qty = ".$vacationData['in_year_qty']);
                        }
                        
                        // 请假申请发布后，在打卡记录中按请假时间加入打卡时间，且备注请假类别
                        /* $attendance = new Hra_Model_Attendance();
                        $attendance->setClock($vacationData['number'], $vacationData['employment_type'], $vacationData['time_from'], $vacationData['time_to'], $vacationData['type_name']); */
                    }
                    
                    // 更新Review表
                    $reviewData = array(
                            'actual_user'   => $user_session->user_info['employee_id'],
                            'finish_time'   => $now,
                            'finish_flg'    => 1
                    );
                    
                    $review->update($reviewData, "type = 'attendance_vacation' and file_id = ".$review_id." and step_name = '".$step_name."'");
                    
                    // 更新请假申请
                    $vacation->update($updateData, "id = ".$review_id);
                    
                    // 发送邮件
                    if(count($toArr)){
                        $mailContent = '<div>请假申请审核：</div>
                                        <div>
                                        <p><b>审核人：</b>'.$user_session->user_info['user_name'].'</p>
                                        <p><b>审核结果：</b>'.$reviewResult.'</p>
                                        <p><b>审核意见：</b>'.$review_remark.'</p>
                                        <p><b>申请人：</b><a style="color:#008B00;font-weight: bold;">'.$vacationData['apply_user_name'].'</a></p>
                                        <p><b>请假类别：</b><a style="color:#008B00;font-weight: bold;">'.$vacationData['type_name'].'</a></p>
                                        <p><b>请假时间：</b><a style="color:#008B00;font-weight: bold;">'.$vacationData['time_from'].'</a> 至 <a style="color:#008B00;font-weight: bold;">'.$vacationData['time_to'].'</a></p>
                                        <p><b>代理人：</b>'.$vacationData['agent_name'].'</p>
                                        <p><b>事由：</b>'.$vacationData['reason'].'</p>
                                        <p><b>工作交接：</b>'.$vacationData['work'].'</p>
                                        <p><b>备注：</b>'.$vacationData['remark'].'</p>
                                        <p><b>创建人：</b>'.$vacationData['creater'].'</p>
                                        <p><b>申请时间：</b>'.$vacationData['create_time'].'</p>
                                        <hr>
                                        <p><b>审核日志：</b></p><p>'.$updateData['review_info'].'</p>
                                        </div>';
                        
                        $mailData = array(
                                'type'      => '消息',
                                'subject'   => '请假申请-'.$subject,
                                'user_id'   => $vacationData['create_user'],
                                'content'   => $mailContent,
                                'add_date'  => $now
                        );
                        
                        if($mailCc){
                            $mailData['cc'] = $mailCc;
                        }
                        
                        $help = new Application_Model_Helpers();
                        
                        $resultMail = $help->sendMailToStep($toArr, $mailData);
                        
                        if(!$result['success']){
                            $result = $resultMail;
                        }
                    }
                }
            }else if($review_type == 'overtime'){
                $overtime = new Hra_Model_Attendance_Overtime();
                $overtimeData = $overtime->getData(null, $review_id);
                
                if($review_operate == 'no'){
                    // 拒绝
                    $review_info = $now.': '.$user_session->user_info['user_name'].' [审核-拒绝] ['.$review_info.']';
                    
                    $timeType = 'review_time_1';
                    if($overtimeData['review_time_1']){
                        $timeType = 'review_time_2';
                    }
                    
                    $data = array(
                            'state'         => 1,
                            $timeType       => date('Y-m-d H:i:s'),
                            'review_info'   => $overtimeData['review_info'].'<br>'.$review_info
                    );
                    
                    $overtime->update($data, "id = ".$review_id);
                    // 删除当前申请的审核配置
                    $review->delete("type = 'attendance_overtime' and file_id = ".$review_id);
                    
                    // 发送邮件通知申请人
                    $reviewResult = '<font style="color: #FF0000"><b>拒绝</b></font>';
                    
                    $mail = new Application_Model_Log_Mail();
                    
                    $applyEmployeeData = $user->fetchRow("id = ".$overtimeData['create_user'])->toArray();
                    $applyEmployee = $employee->fetchRow("id = ".$applyEmployeeData['employee_id'])->toArray();
                    $to = $applyEmployee['email'];
                    
                    $mailContent = '<div>加班申请审核：</div>
                                    <div>
                                    <p><b>审核人：</b>'.$user_session->user_info['user_name'].'</p>
                                    <p><b>审核结果：</b>'.$reviewResult.'</p>
                                    <p><b>审核意见：</b>'.$review_remark.'</p>
                                    <p><b>申请人：</b><a style="color:#008B00;font-weight: bold;">'.$overtimeData['apply_user_name'].'</a></p>
                                    <p><b>加班时间：</b><a style="color:#008B00;font-weight: bold;">'.$overtimeData['time_from'].'</a> 至 <a style="color:#008B00;font-weight: bold;">'.$overtimeData['time_to'].'</a></p>
                                    <p><b>事由：</b>'.$overtimeData['reason'].'</p>
                                    <p><b>备注：</b>'.$overtimeData['remark'].'</p>
                                    <p><b>创建人：</b>'.$overtimeData['creater'].'</p>
                                    <p><b>申请时间：</b>'.$overtimeData['create_time'].'</p>
                                    <hr>
                                    <p><b>审核日志：</b></p><p>'.$data['review_info'].'</p>
                                    </div>';
                    
                    $mailData = array(
                            'type'      => '消息',
                            'subject'   => '加班申请-审核',
                            'to'        => $to,
                            'user_id'   => $overtimeData['create_user'],
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
                    // 批准
                    $review_info = $now.': '.$user_session->user_info['user_name'].' [审核-批准] ['.$review_info.']';
                    
                    $reviewResult = '<font style="color: #006400"><b>批准</b></font>';
                    
                    $updateData = array();
                    
                    $toArr = array();
                    
                    $subject = '审核';
                    
                    if($review_step == 'review_1'){
                        $step_name = '审核人1';
                        
                        if($overtimeData['review_user_2'] > 0 && $overtimeData['review_time_1'] == ''){
                            // 二级审核，当前申请批准后进入第二级审核
                            array_push($toArr, $overtimeData['review_employee_2_id']);
                            
                            $updateData = array(
                                    'review_time_1' => $now,
                                    'review_info'   => $overtimeData['review_info'].'<br>'.$review_info
                            );
                        }else{
                            // 一级审核，当前申请批准后进入HRA审核
                            $member = new Admin_Model_Member();
                            $hraAdminUserArr = $member->getMemberWithNoManagerByName('人事主管');
                            $hraEmployeeIdArr = array();
                            
                            foreach ($hraAdminUserArr as $hra){
                                $hra_info = $user->getEmployeeInfoById($hra['user_id']);
                                array_push($toArr, $hra_info['id']);
                            }
                            
                            if (isset($overtimeData['manager_id']) && $overtimeData['manager_id'] != '') {
                                array_push($toArr, $overtimeData['manager_id']);
                            }
                            
                            $updateData = array(
                                    'state'         => 2,
                                    'review_time_1' => $now,
                                    'review_info'   => $overtimeData['review_info'].'<br>'.$review_info
                            );
                        }
                    }else if($review_step == 'review_2'){
                        // 二级审核，当前申请批准后进入HRA审核
                        $step_name = '审核人2';
                        
                        $member = new Admin_Model_Member();
                        $hraAdminUserArr = $member->getMemberWithNoManagerByName('人事主管');
                        $hraEmployeeIdArr = array();
                        
                        foreach ($hraAdminUserArr as $hra){
                            $hra_info = $user->getEmployeeInfoById($hra['user_id']);
                            array_push($toArr, $hra_info['id']);
                        }
                        
                        if (isset($overtimeData['manager_id']) && $overtimeData['manager_id'] != '') {
                            array_push($toArr, $overtimeData['manager_id']);
                        }
                        
                        $updateData = array(
                                'state'         => 2,
                                'review_time_2' => $now,
                                'review_info'   => $overtimeData['review_info'].'<br>'.$review_info
                        );
                    }else if($review_step == 'review_hra'){
                        // HRA审核，当前申请批准后发布
                        $step_name = '人事审核';
                        
                        $reviewResult = '<font style="color: #006400"><b>发布</b></font>';
                        
                        $subject = '发布';
                        
                        array_push($toArr, $overtimeData['apply_employee_id']);
                        
                        $updateData = array(
                                'state'         => 3,
                                'release_time'  => $now,
                                'release_user'  => $user_id,
                                'review_info'   => $overtimeData['review_info'].'<br>'.$review_info
                        );
                    }
                    
                    // 更新Review表
                    $reviewData = array(
                            'actual_user'   => $user_session->user_info['employee_id'],
                            'finish_time'   => $now,
                            'finish_flg'    => 1
                    );
                    
                    $review->update($reviewData, "type = 'attendance_overtime' and file_id = ".$review_id." and step_name = '".$step_name."'");
                    
                    // 更新加班申请
                    $overtime->update($updateData, "id = ".$review_id);
                    
                    // 发送邮件
                    if(count($toArr)){
                        $mailContent = '<div>加班申请审核：</div>
                                        <div>
                                        <p><b>审核人：</b>'.$user_session->user_info['user_name'].'</p>
                                        <p><b>审核结果：</b>'.$reviewResult.'</p>
                                        <p><b>审核意见：</b>'.$review_remark.'</p>
                                        <p><b>申请人：</b><a style="color:#008B00;font-weight: bold;">'.$overtimeData['apply_user_name'].'</a></p>
                                        <p><b>请假时间：</b><a style="color:#008B00;font-weight: bold;">'.$overtimeData['time_from'].'</a> 至 <a style="color:#008B00;font-weight: bold;">'.$overtimeData['time_to'].'</a></p>
                                        <p><b>事由：</b>'.$overtimeData['reason'].'</p>
                                        <p><b>备注：</b>'.$overtimeData['remark'].'</p>
                                        <p><b>创建人：</b>'.$overtimeData['creater'].'</p>
                                        <p><b>申请时间：</b>'.$overtimeData['create_time'].'</p>
                                        <hr>
                                        <p><b>审核日志：</b></p><p>'.$updateData['review_info'].'</p>
                                        </div>';
                        
                        $mailData = array(
                                'type'      => '消息',
                                'subject'   => '加班申请-'.$subject,
                                'user_id'   => $overtimeData['create_user'],
                                'content'   => $mailContent,
                                'add_date'  => $now
                        );
                        
                        $help = new Application_Model_Helpers();
                        
                        $resultMail = $help->sendMailToStep($toArr, $mailData);
                        
                        if(!$result['success']){
                            $result = $resultMail;
                        }
                    }
                }
            }
        }else{
            $result['success'] = false;
            $result['info'] = "审核失败，审核对象和操作类别不能为空！";
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    // 获取请假记录
    public function getvacationAction()
    {
        $request = $this->getRequest()->getParams();
        
        $option = isset($request['option']) ? $request['option'] : 'list';
        
        $vacation = new Hra_Model_Attendance_Vacation();
    
        $condition = array(
                'key'       => isset($request['key']) ? $request['key'] : null,
                'v_type'     => isset($request['type']) ? $request['type'] : null,
                'state'     => isset($request['state']) ? $request['state'] : null,
                'date_from' => isset($request['date_from']) ? $request['date_from'] : date('Y-m-01'),
                'date_to'   => isset($request['date_to']) ? $request['date_to'] : date('Y-m-t'),
                'page'      => isset($request['page']) ? $request['page'] : 1,
                'limit'     => isset($request['limit']) ? $request['limit'] : 0,
                'type'      => $option
        );
    
        $data = $vacation->getData($condition);
    
        if($option == 'csv'){
            $this->view->layout()->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);
    
            $h = new Application_Model_Helpers();
            $h->exportCsv($data, '请假记录');
        }else{
            echo Zend_Json::encode($data);
        }
    
        exit;
    }
    
    // 获取加班记录
    public function getovertimeAction()
    {
        $request = $this->getRequest()->getParams();
        
        $option = isset($request['option']) ? $request['option'] : 'list';
        
        $overtime = new Hra_Model_Attendance_Overtime();
    
        $condition = array(
                'key'       => isset($request['key']) ? $request['key'] : null,
                'state'     => isset($request['state']) ? $request['state'] : null,
                'date_from' => isset($request['date_from']) ? $request['date_from'] : date('Y-m-01'),
                'date_to'   => isset($request['date_to']) ? $request['date_to'] : date('Y-m-t'),
                'page'      => isset($request['page']) ? $request['page'] : 1,
                'limit'     => isset($request['limit']) ? $request['limit'] : 0,
                'type'      => $option
        );
    
        $data = $overtime->getData($condition);
    
        if($option == 'csv'){
            $this->view->layout()->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);
    
            $h = new Application_Model_Helpers();
            $h->exportCsv($data, '加班记录');
        }else{
            echo Zend_Json::encode($data);
        }
    
        exit;
    }
    
    private function getOvertimeQty($ids)
    {
        $qty = 0;
        
        $overtime = new Hra_Model_Attendance_Overtime();
        
        return $overtime->getOvertimeQtyByIds($ids);
    }
    
    // 提交请假申请
    public function vacationAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '提交成功'
        );
        
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        // 操作类型
        $operate = array(
                'new'       => '新建',
                'edit'      => '编辑',
                'delete'    => '删除'
        );
        
        // 操作类别（新建、更新、删除）
        $operate_type = isset($request['operate']) ? $request['operate'] : '';
        
        $vacation = new Hra_Model_Attendance_Vacation();
        
        if($operate_type == 'new_hra' || $operate_type == 'new' || $operate_type == 'edit'){
            $time_from = str_replace('T', ' ', $request['time_from']);
            $time_to = str_replace('T', ' ', $request['time_to']);
            
            if(date('Y-m', strtotime($time_from)) != date('Y-m', strtotime($time_to))){
                $result['success'] = false;
                $request['info'] = '不允许跨月申请，请重新填写时间！';
            
                echo Zend_Json::encode($result);
            
                exit;
            }
            
            // 获取请假人员的用工形式（0：弹性，1：非弹性）
            $user = new Application_Model_User();
            $userInfo = $user->getEmployeeInfoById($request['apply_user']);
            $userType = $userInfo['employment_type'];
            
            $workday = new Hra_Model_Workday();
            // 获取请假区间包含的工作日天数
            $vacationInfo = $workday->getWorkdayQtyByTimeRange($userType, $request['time_from'], $request['time_to']);
            $vacationQty = $vacationInfo['qty'];
            $vacationQty_hours = $vacationInfo['qty_hours'];
            
            // 当编辑请假申请时，检查时间是否重叠需要过滤当前请假申请ID
            $filter_id = null;
            
            if($operate_type == 'edit'){
                $filter_id = $request['id'];
            }
            
            // 检查请假时间范围是否包含工作日
            if($vacationQty > 0){
                $overtimeQty = 0;
                
                if($request['exchange_overtime_ids'] != ''){
                    $overtimeQty = $this->getOvertimeQty(explode(',', $request['exchange_overtime_ids']));
                }
                
                $vStorage = new Hra_Model_Vacationstorage();
                //获取员工最近一年的剩余年假以及对于入司年数
                $vStorageQty = $vStorage->getVacationQty($userInfo['number']);
                
                if(round($vStorageQty['qty'], 1) == round($vacationQty, 1)){
                    $vacationQty = $vStorageQty['qty'];
                }
                
                // 检查请假时间范围是否跟已有申请（包括已审核和审核中的申请）重叠
                if($vacation->checkTimeOverlap($request['apply_user'], $request['time_from'], $request['time_to'], $filter_id)){
                    $result['success'] = false;
                    $result['info'] = '时间设置错误，请假时间重叠，请优先处理未审申请。';
                }else if($request['type'] == 2 && round($vStorageQty['qty'], 1) < round($vacationQty, 1)){
                    // 当员工请假类别为年假时，检查年假剩余天数是否充足
                    $result['success'] = false;
                    $result['info'] = '时间设置错误，剩余年假天数不足。';
                }else if($request['type'] == 8 && round($overtimeQty, 1) < round($vacationQty, 1)){
                    // 当员工请假类别为调休时，检查调休可用天数是否充足
                    $result['success'] = false;
                    $result['info'] = '时间设置错误，剩余可用加班天数不足。';
                }else{
                    $now = date('Y-m-d H:i:s');
                    $user_session = new Zend_Session_Namespace('user');
                    $user_id = $user_session->user_info['user_id'];
                    
                    // 当前申请为代申请时，获取申请人真实部门主管
                    if($request['apply_user'] != $user_id){
                        // 获取部门主管
                        $manager = $user->getManagerUser($request['apply_user']);
                        $request['review_user_1'] = $manager[0]['id'];
                    }
                    
                    if($request['review_user_1'] >= 0){
                        // 当员工请假类别是“年假”时，记录员工可用年假对应的入司年数（均以最近一年的年假对应入司年数为准）
                        $in_year_qty = $request['type'] == 2 ? $vStorageQty['in_year_qty'] : null;
                        
                        $data = array(
                                'type'          => $request['type'],
                                'in_year_qty'   => $in_year_qty,
                                'qty'           => $vacationQty,
                                'qty_hours'     => $vacationQty_hours,
                                'apply_user'    => $request['apply_user'],
                                'review_user_1' => $request['review_user_1'],
                                //'review_user_2' => $request['review_user_2'],
                                'time_from'     => $request['time_from'],
                                'time_to'       => $request['time_to'],
                                'reason'        => $request['reason'],
                                'work'          => $request['work'],
                                'remark'        => $request['remark'],
                                'exchange_overtime_ids' => $request['exchange_overtime_ids'],
                                'agent'         => $request['agent'],
                                'update_time'   => $now,
                                'update_user'   => $user_id
                        );
                        /* echo '<pre>';
                        print_r($data);
                        exit; */
                        $vacation_id = 0;
                        
                        if($operate_type == 'new_hra'){
                            // HRA手动添加
                            $data['state'] = 3;
                            $data['create_time'] = $now;
                            $data['create_user'] = $user_id;
                            $data['review_info'] = $now.': '.$user_session->user_info['user_name'].' [手动添加]';
                            
                            try{
                                $vacation_id = $vacation->insert($data);
                                
                                echo Zend_Json::encode($result);
                                
                                exit;
                            } catch (Exception $e){
                                $result['success'] = false;
                                $result['info'] = $e->getMessage();
                            }
                        }else if($operate_type == 'new'){
                            $data['create_time'] = $now;
                            $data['create_user'] = $user_id;
                            $data['review_info'] = $now.': '.$user_session->user_info['user_name'].' [新建]';
                            
                            try{
                                $vacation_id = $vacation->insert($data);
                            } catch (Exception $e){
                                $result['success'] = false;
                                $result['info'] = $e->getMessage();
                            }
                        }else{
                            $vacation_id = $request['id'];
                            $review_info = $now.': '.$user_session->user_info['user_name'].' [修改]';
                            $vacationData = $vacation->getData(null, $vacation_id);
                            
                            $data['review_info'] = $vacationData['review_info'].'<br>'.$review_info;
                            $data['state'] = 0;
                            $data['review_time_1'] = null;
                            //$data['review_time_2'] = null;
                            $data['release_time'] = null;
                            
                            try {
                                $vacation->update($data, "id = ".$vacation_id);
                            } catch (Exception $e) {
                                $result['success'] = false;
                                $result['info'] = $e->getMessage();
                            }
                        }
                        
                        if($request['type'] == 8){
                            $overtime = new Hra_Model_Attendance_Overtime();
                            
                            $overtime_id_arr = explode(',', $request['exchange_overtime_ids']);
                            
                            foreach ($overtime_id_arr as $overtime_id){
                                $overtime->update(array('exchange' => 1, 'exchange_vacation_id' => $vacation_id), "id = ".$overtime_id);
                            }
                        }
                        
                        if($vacation_id > 0){
                            $vacationData = $vacation->getData(null, $vacation_id);
                            
                            $review = new Dcc_Model_Review();
                            $user = new Application_Model_User();
                            $help = new Application_Model_Helpers();
                            
                            // 记录审核日志：审核人1
                            $apply_user_info = $user->getEmployeeInfoById($request['apply_user']);
                            $review_user_1_info = $user->getEmployeeInfoById($request['review_user_1']);
                            //$agent_info = $user->getEmployeeInfoById($request['agent']);
                            
                            $reviewData = array(
                                    'type'      => 'attendance_vacation',
                                    'file_id'   => $vacation_id,
                                    'step_name' => '审核人1',
                                    'plan_user' => $vacationData['review_employee_1_id'],
                                    'method'    => 1,
                                    'return'    => 1
                            );
                            
                            $review->insert($reviewData);
                            
                            // 如果存在审核人2记录审核日志：审核人2
                            /* if($request['review_user_2']){
                                $review_user_2_info = $user->getEmployeeInfoById($request['review_user_2']);
                                
                                $reviewData = array(
                                        'type'      => 'attendance_vacation',
                                        'file_id'   => $vacation_id,
                                        'step_name' => '审核人2',
                                        'plan_user' => $vacationData['review_employee_2_id'],
                                        'method'    => 1,
                                        'return'    => 1
                                );
                                
                                $review->insert($reviewData);
                            } */
                            
                            // 记录审核日志：人事主管
                            $member = new Admin_Model_Member();
                            $hraAdminUserArr = $member->getMemberWithNoManagerByName('人事主管');
                            $hraEmployeeIdArr = array();
                            
                            foreach ($hraAdminUserArr as $hra){
                                $hra_info = $user->getEmployeeInfoById($hra['user_id']);
                                array_push($hraEmployeeIdArr, $hra_info['id']);
                            }
                            
                            $hraIds = implode(',', $hraEmployeeIdArr);
                            
                            $reviewData = array(
                                    'type'      => 'attendance_vacation',
                                    'file_id'   => $vacation_id,
                                    'step_name' => '人事审核',
                                    'plan_user' => $hraIds,
                                    'method'    => 1,
                                    'return'    => 1
                            );
                            
                            $review->insert($reviewData);
                            
                            // 向审核人1发送邮件
                            $mailContent = '<div>你有一个新的请假申请等待审核，请登录系统查看：</div>
                                            <div>
                                            <p><b>申请人：</b><a style="color:#008B00;font-weight: bold;">'.$vacationData['apply_user_name'].'</a></p>
                                            <p><b>请假类别：</b><a style="color:#008B00;font-weight: bold;">'.$vacationData['type_name'].'</a></p>
                                            <p><b>请假时间：</b><a style="color:#008B00;font-weight: bold;">'.$vacationData['time_from'].'</a> 至 <a style="color:#008B00;font-weight: bold;">'.$vacationData['time_to'].'</a></p>
                                            <p><b>代理人：</b>'.$vacationData['agent_name'].'</p>
                                            <p><b>事由：</b>'.$vacationData['reason'].'</p>
                                            <p><b>工作交接：</b>'.$vacationData['work'].'</p>
                                            <p><b>备注：</b>'.$vacationData['remark'].'</p>
                                            <p><b>创建人：</b>'.$vacationData['creater'].'</p>
                                            <p><b>申请时间：</b>'.$vacationData['create_time'].'</p>
                                            <hr>
                                            <p><b>审核日志：</b></p><p>'.$data['review_info'].'</p>
                                            </div>';
                            
                            $mailData = array(
                                    'type'      => '消息',
                                    'subject'   => '请假申请-新申请',
                                    'cc'        => $user_session->user_info['user_email'],
                                    'content'   => $mailContent,
                                    'add_date'  => $now
                            );
                            
                            $result = $help->sendMailToStep(array($review_user_1_info['id']), $mailData);
                        }else{
                            $result['success'] = false;
                            $result['info'] = '保存错误，请确认填写内容是否正确。';
                        }
                    }else{
                        $result['success'] = false;
                        $result['info'] = '申请失败，当前申请人没有设置部门主管。';
                    }
                }
            }else{
                $result['success'] = false;
                $result['info'] = '时间设置错误，请确认时间范围包含工作日。';
            }
        }else if($operate_type == 'delete'){
            if(isset($request['id']) && count(Zend_Json::decode($request['id'])) > 0){
                $ids = Zend_Json::decode($request['id']);
                // 多条申请逐条删除
                foreach ($ids as $id){
                    try {
                        $review = new Dcc_Model_Review();
                        // 删除审核日志
                        $review->delete("type = 'attendance_vacation' and file_id = ".$id);
                    
                        $vacation->delete("id = ".$id);
                    
                        $overtime = new Hra_Model_Attendance_Overtime();
                    
                        $overtime->update(array('exchange' => 0, 'exchange_vacation_id' => 0), "exchange_vacation_id = ".$id);
                    } catch (Exception $e) {
                        $result['success'] = false;
                        $result['info'] = $e->getMessage();
                    }
                }
            }else{
                $result['success'] = false;
                $result['info'] = '参数错误，没有删除对象。';
            }
        }else{
            $result['success'] = false;
            $result['info'] = '参数错误，没有操作类别。';
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    // 提交加班申请
    public function overtimeAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '提交成功'
        );
        
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        // 操作类型
        $operate = array(
                'new'       => '新建',
                'edit'      => '编辑',
                'delete'    => '删除'
        );
        
        // 操作类别（新建、更新、删除）
        $operate_type = isset($request['operate']) ? $request['operate'] : '';
        
        $overtime = new Hra_Model_Attendance_Overtime();
        
        if($operate_type == 'new_hra' || $operate_type == 'new' || $operate_type == 'edit'){
            $time_from = str_replace('T', ' ', $request['time_from']);
            $time_to = str_replace('T', ' ', $request['time_to']);
            
            if(date('Y-m', strtotime($time_from)) != date('Y-m', strtotime($time_to))){
                $result['success'] = false;
                $request['info'] = '不允许跨月申请，请重新填写时间！';
                
                echo Zend_Json::encode($result);
                
                exit;
            }
            
            // 获取加班人员的用工形式（0：弹性，1：非弹性）
            $user = new Application_Model_User();
            $userInfo = $user->getEmployeeInfoById($request['apply_user']);
            $userType = $userInfo['employment_type'];
            
            $workday = new Hra_Model_Workday();
            // 获取请假区间包含的工作日天数
            
            $overtimeInfo = $workday->getOvertimeQtyByTimeRange($userType, $request['time_from'], $request['time_to']);
            $overtimeQty = $overtimeInfo['qty'];
            $overtimeQty_hours = $overtimeInfo['qty_hours'];
            /* echo $overtimeQty;
            exit; */
            // 加班类别按起始日期获取
            $overtimeType = $workday->getWorkdayType($userType, date('Y-m-d', strtotime($request['time_from'])));
            
            if ($overtimeType == 0) {
                $result['success'] = false;
                $request['info'] = '工作日设置错误，请检查工作日设置！';
                
                echo Zend_Json::encode($result);
                
                exit;
            }
            
            // 当编辑请假申请时，检查时间是否重叠需要过滤当前加班申请ID
            $filter_id = null;
            
            if($operate_type == 'edit'){
                $filter_id = $request['id'];
            }
            
            // 检查加班时间范围是否正确
            if($overtimeQty > 0){
                // 检查加班时间范围是否跟已有申请（包括已审核和审核中的申请）重叠
                if($overtime->checkTimeOverlap($request['apply_user'], $request['time_from'], $request['time_to'], $filter_id)){
                    $result['success'] = false;
                    $result['info'] = '时间设置错误，加班时间重叠，请优先处理未审申请。';
                }else{
                    $now = date('Y-m-d H:i:s');
                    $user_session = new Zend_Session_Namespace('user');
                    $user_id = $user_session->user_info['user_id'];
                    
                    // 当前申请为代申请时，获取申请人真实部门主管
                    if($request['apply_user'] != $user_id){
                        // 获取部门主管
                        $manager = $user->getManagerUser($request['apply_user']);
                        $request['review_user_1'] = $manager[0]['id'];
                    }
                    
                    if($request['review_user_1'] >= 0){
                        $data = array(
                                'type'          => $overtimeType,
                                'apply_user'    => $request['apply_user'],
                                'review_user_1' => $request['review_user_1'],
                                //'review_user_2' => $request['review_user_2'],
                                'time_from'     => $request['time_from'],
                                'time_to'       => $request['time_to'],
                                'qty'           => $overtimeQty,
                                'qty_hours'     => $overtimeQty_hours,
                                'reason'        => $request['reason'],
                                'remark'        => $request['remark'],
                                'update_time'   => $now,
                                'update_user'   => $user_id
                        );
                        /* echo '<pre>';
                        print_r($data);
                        exit; */
                        if($operate_type == 'new_hra'){
                            // HRA手动添加
                            $data['state'] = 3;
                            $data['create_time'] = $now;
                            $data['create_user'] = $user_id;
                            $data['review_info'] = $now.': '.$user_session->user_info['user_name'].' [手动添加]';
                            
                            try{
                                $overtime_id = $overtime->insert($data);
                                
                                echo Zend_Json::encode($result);
                                
                                exit;
                            } catch (Exception $e){
                                $result['success'] = false;
                                $result['info'] = $e->getMessage();
                            }
                        }else if($operate_type == 'new'){
                            $data['create_time'] = $now;
                            $data['create_user'] = $user_id;
                            $data['review_info'] = $now.': '.$user_session->user_info['user_name'].' [新建]';
                            
                            try{
                                $overtime_id = $overtime->insert($data);
                            } catch (Exception $e){
                                $result['success'] = false;
                                $result['info'] = $e->getMessage();
                            }
                        }else{
                            $overtime_id = $request['id'];
                            $review_info = $now.': '.$user_session->user_info['user_name'].' [修改]';
                            $overtimeData = $overtime->getData(null, $overtime_id);
                            
                            $data['review_info'] = $overtimeData['review_info'].'<br>'.$review_info;
                            $data['state'] = 0;
                            $data['review_time_1'] = null;
                            //$data['review_time_2'] = null;
                            $data['release_time'] = null;
                            
                            try {
                                $overtime->update($data, "id = ".$overtime_id);
                            } catch (Exception $e) {
                                $result['success'] = false;
                                $result['info'] = $e->getMessage();
                            }
                        }
                        
                        $overtimeData = $overtime->getData(null, $overtime_id);
                        
                        $review = new Dcc_Model_Review();
                        $user = new Application_Model_User();
                        $help = new Application_Model_Helpers();
                        
                        // 记录审核日志：审核人1
                        $apply_user_info = $user->getEmployeeInfoById($request['apply_user']);
                        $review_user_1_info = $user->getEmployeeInfoById($request['review_user_1']);
                        
                        $reviewData = array(
                                'type'      => 'attendance_overtime',
                                'file_id'   => $overtime_id,
                                'step_name' => '审核人1',
                                'plan_user' => $overtimeData['review_employee_1_id'],
                                'method'    => 1,
                                'return'    => 1
                        );
                        
                        $review->insert($reviewData);
                        
                        // 如果存在审核人2记录审核日志：审核人2
                        /* if($request['review_user_2']){
                            $review_user_2_info = $user->getEmployeeInfoById($request['review_user_2']);
                            
                            $reviewData = array(
                                    'type'      => 'attendance_overtime',
                                    'file_id'   => $overtime_id,
                                    'step_name' => '审核人2',
                                    'plan_user' => $overtimeData['review_employee_2_id'],
                                    'method'    => 1,
                                    'return'    => 1
                            );
                            
                            $review->insert($reviewData);
                        } */
                        
                        // 记录审核日志：人事主管
                        $member = new Admin_Model_Member();
                        $hraAdminUserArr = $member->getMemberWithNoManagerByName('人事主管');
                        $hraEmployeeIdArr = array();
                        
                        foreach ($hraAdminUserArr as $hra){
                            $hra_info = $user->getEmployeeInfoById($hra['user_id']);
                            array_push($hraEmployeeIdArr, $hra_info['id']);
                        }
                        
                        $hraIds = implode(',', $hraEmployeeIdArr);
                        
                        $reviewData = array(
                                'type'      => 'attendance_overtime',
                                'file_id'   => $overtime_id,
                                'step_name' => '人事审核',
                                'plan_user' => $hraIds,
                                'method'    => 1,
                                'return'    => 1
                        );
                        
                        $review->insert($reviewData);
                        
                        // 向审核人1发送邮件
                        $mailContent = '<div>你有一个新的加班申请等待审核，请登录系统查看：</div>
                                        <div>
                                        <p><b>申请人：</b><a style="color:#008B00;font-weight: bold;">'.$overtimeData['apply_user_name'].'</a></p>
                                        <p><b>加班时间：</b><a style="color:#008B00;font-weight: bold;">'.$overtimeData['time_from'].'</a> 至 <a style="color:#008B00;font-weight: bold;">'.$overtimeData['time_to'].'</a></p>
                                        <p><b>事由：</b>'.$overtimeData['reason'].'</p>
                                        <p><b>备注：</b>'.$overtimeData['remark'].'</p>
                                        <p><b>创建人：</b>'.$overtimeData['creater'].'</p>
                                        <p><b>申请时间：</b>'.$overtimeData['create_time'].'</p>
                                        <hr>
                                        <p><b>审核日志：</b></p><p>'.$data['review_info'].'</p>
                                        </div>';
                        
                        $mailData = array(
                                'type'      => '消息',
                                'subject'   => '加班申请-新申请',
                                'cc'        => $user_session->user_info['user_email'],
                                'content'   => $mailContent,
                                'add_date'  => $now
                        );
                        
                        $result = $help->sendMailToStep(array($review_user_1_info['id']), $mailData);
                    }else{
                        $result['success'] = false;
                        $result['info'] = '申请失败，当前申请人未设置部门主管。';
                    }
                }
            }else{
                $result['success'] = false;
                $result['info'] = '时间设置错误，请确认时间范围是否正确。';
            }
        }else if($operate_type == 'delete'){
            if(isset($request['id']) && count(Zend_Json::decode($request['id'])) > 0){
                $ids = Zend_Json::decode($request['id']);
                // 多条申请逐条删除
                foreach ($ids as $id){
                    try {
                        $review = new Dcc_Model_Review();
                        // 删除审核日志
                        $review->delete("type = 'attendance_overtime' and file_id = ".$id);
                    
                        $overtime->delete("id = ".$id);
                    } catch (Exception $e) {
                        $result['success'] = false;
                        $result['info'] = $e->getMessage();
                    }
                }
            }else{
                $result['success'] = false;
                $result['info'] = '参数错误，没有删除对象。';
            }
        }else{
            $result['success'] = false;
            $result['info'] = '参数错误，没有操作类别。';
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
}