<?php
/**
 * 2014-8-5 20:33:12
 * @author      x.li
 * @abstract    会议管理
 */
class Res_MeetingController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $user_session = new Zend_Session_Namespace('user');
        
        $this->view->meetingAdmin = 0;
        
        $this->view->user_id = 0;
        
        if(isset($user_session->user_info)){
            $user_id = $user_session->user_info['user_id'];
            
            $this->view->user_id = $user_session->user_info['user_id'];
        
            if(Application_Model_User::checkPermissionByRoleName('系统管理员')){
                $this->view->meetingAdmin = 1;
            }
        }
    }
    
    // 获取会议室列表
    public function getroomAction()
    {
        // 请求参数
        $request = $this->getRequest()->getParams();
    
        $option = isset($request['option']) ? $request['option'] : 'list';
    
        $room = new Res_Model_Meetingroom();
    
        if($option == 'list'){
            echo Zend_Json::encode($room->getList());
        }else{
            echo Zend_Json::encode($room->getData());
        }
    
        exit;
    }
    
    // 编辑会议室
    public function editroomAction()
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
        
        $room = new Res_Model_Meetingroom();
        
        if(count($updated) > 0){
            foreach ($updated as $val){
                $data = array(
                        'active'        => $val->active,
                        'name'          => $val->name,
                        'projector'     => $val->projector,
                        'tel'           => $val->tel,
                        'qty'           => $val->qty,
                        'remark'        => $val->remark,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
    
                if($room->fetchAll("id != ".$val->id." and name = '".$val->name."'")->count() > 0){
                    $result['success'] = false;
                    $result['info'] = '会议室：'.$val->name."已存在，请勿重复添加！";
                }else{
                    try {
                        $room->update($data, "id = ".$val->id);
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
                        'name'          => $val->name,
                        'projector'     => $val->projector,
                        'tel'           => $val->tel,
                        'qty'           => $val->qty,
                        'remark'        => $val->remark,
                        'create_time'   => $now,
                        'create_user'   => $user_id,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
    
                if($room->fetchAll("name = '".$val->name."'")->count() > 0){
                    $result['success'] = false;
                    $result['info'] = '会议室：'.$val->name."已存在，请勿重复添加！";
                }else{
                    try{
                        $room->insert($data);
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
            $meeting = new Res_Model_Meeting();
    
            foreach ($deleted as $val){
                if($meeting->fetchAll("room_id = ".$val->id)->count() == 0){
                    try {
                        $room->delete("id = ".$val->id);
                    } catch (Exception $e){
                        $result['success'] = false;
                        $result['info'] = $e->getMessage();
    
                        echo Zend_Json::encode($result);
    
                        exit;
                    }
                }else{
                    $result['success'] = false;
                    $result['info'] = '会议室已使用，不能删除！';
    
                    echo Zend_Json::encode($result);
    
                    exit;
                }
            }
        }
    
        echo Zend_Json::encode($result);
    
        exit;
    }
    
    public function cancelAction()
    {
        $result = array(
                'success'   => true,
                'info'      => '提交成功'
        );
        
        $request = $this->getRequest()->getParams();
        
        $id = isset($request['id']) && $request['id'] != '' ? $request['id'] : null;
        
        if($id){
            $now = date('Y-m-d H:i:s');
            $user_session = new Zend_Session_Namespace('user');
            $user_id = $user_session->user_info['user_id'];
            
            $meeting = new Res_Model_Meeting();
            
            $data = array(
                    'state'         => 2,
                    'update_user'   => $user_id,
                    'update_time'   => $now
            );
            
            $meeting->update($data, "id = ".$id);
            
            // 获取会议完整信息
            $meetingData = $meeting->getData(null, $id);
            // 发送会议纪要通知
            $mail = new Application_Model_Log_Mail();
            
            $user = new Application_Model_User();
            
            $moderatorInfo = $user->getEmployeeInfoById($meetingData['moderator']);
            
            $mailContent = '<div>会议预定 - 取消：</div>
                                    <div>
                                    <p><b>预订人：</b>'.$meetingData['updater'].'</p>
                                    <p><b>会议室：</b>'.$meetingData['room_name'].'</p>
                                    <p><b>主题：</b>'.$meetingData['subject'].'</p>
                                    <p><b>时间：</b>'.$meetingData['time_from'].' - '.$meetingData['time_to'].'</p>
                                    <p><b>主持人：</b>'.$moderatorInfo['cname'].'</p>
                                    <p><b>参会人员：</b>'.$meetingData['members_cname'].'</p>
                                    <p><b>备注：</b>'.$meetingData['remark'].'</p>
                                    <hr>
                                    <p><b>会议纪要：</b>'.$meetingData['mom'].'</p>
                                    </div>';
            
            $memberArr = explode(',', $meetingData['members']);
            array_push($memberArr, $meetingData['moderator']);
            $toArr = array();
            
            foreach ($memberArr as $m){
                $memberInfo = $user->getEmployeeInfoById($m);
            
                $memberInfo = $user->getEmployeeInfoById($m);
            
                array_push($toArr, $memberInfo['email']);
            }
            
            $mailData = array(
                    'type'      => '消息',
                    'subject'   => '会议预定 - 取消',
                    'to'        => implode(',', $toArr),
                    'cc'        => $user_session->user_info['user_email'],
                    'user_id'   => $meetingData['create_user'],
                    'content'   => $mailContent,
                    'add_date'  => $now
            );
            
            try {
                // 记录邮件日志并发送邮件
                $mail->insert($mailData);
                $mail->send($mail->insert($mailData));
            } catch (Exception $e) {
                $result['success'] = false;
                $result['info'] = $e->getMessage();
            }
        }else{
            $result['success'] = false;
            $result['info'] = '参数错误';
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    public function momAction()
    {
        $result = array(
                'success'   => true,
                'info'      => '提交成功'
        );
        
        $request = $this->getRequest()->getParams();
        
        $id = isset($request['id']) && $request['id'] != '' ? $request['id'] : null;
        $mom = isset($request['mom']) && $request['mom'] != '' ? $request['mom'] : null;
        
        if($id && $mom){
            $meeting = new Res_Model_Meeting();
            
            $now = date('Y-m-d H:i:s');
            $user_session = new Zend_Session_Namespace('user');
            $user_id = $user_session->user_info['user_id'];
            
            $data = array(
                    'mom'           => $mom,
                    'update_user'   => $user_id,
                    'update_time'   => $now
            );
            // 记录会议纪要
            $meeting->update($data, "id = ".$id);
            // 获取会议完整信息
            $meetingData = $meeting->getData(null, $id);
            // 发送会议纪要通知
            $mail = new Application_Model_Log_Mail();
            
            $user = new Application_Model_User();
            
            $moderatorInfo = $user->getEmployeeInfoById($meetingData['moderator']);
            
            $mailContent = '<div>会议预定 - 会议纪要：</div>
                                    <div>
                                    <p><b>预订人：</b>'.$meetingData['updater'].'</p>
                                    <p><b>会议室：</b>'.$meetingData['room_name'].'</p>
                                    <p><b>主题：</b>'.$meetingData['subject'].'</p>
                                    <p><b>时间：</b>'.$meetingData['time_from'].' - '.$meetingData['time_to'].'</p>
                                    <p><b>主持人：</b>'.$moderatorInfo['cname'].'</p>
                                    <p><b>参会人员：</b>'.$meetingData['members_cname'].'</p>
                                    <p><b>备注：</b>'.$meetingData['remark'].'</p>
                                    <hr>
                                    <p><b>会议纪要：</b>'.$meetingData['mom'].'</p>
                                    </div>';
            
            $memberArr = explode(',', $meetingData['members']);
            array_push($memberArr, $meetingData['moderator']);
            $toArr = array();
            
            foreach ($memberArr as $m){
                $memberInfo = $user->getEmployeeInfoById($m);
            
                $memberInfo = $user->getEmployeeInfoById($m);
            
                array_push($toArr, $memberInfo['email']);
            }
            
            $mailData = array(
                    'type'      => '消息',
                    'subject'   => '会议预定 - 会议纪要',
                    'to'        => implode(',', $toArr),
                    'cc'        => $user_session->user_info['user_email'],
                    'user_id'   => $meetingData['create_user'],
                    'content'   => $mailContent,
                    'add_date'  => $now
            );
            
            try {
                // 记录邮件日志并发送邮件
                $mail->insert($mailData);
                $mail->send($mail->insert($mailData));
            } catch (Exception $e) {
                $result['success'] = false;
                $result['info'] = $e->getMessage();
            }
        }else{
            $result['success'] = false;
            $result['info'] = '参数错误';
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    public function newAction()
    {
        $result = array(
                'success'   => true,
                'info'      => '提交成功'
        );
        
        $request = $this->getRequest()->getParams();
        
        $operate = isset($request['operate']) && $request['operate'] != '' ? $request['operate'] : null;
        $id = isset($request['id']) && $request['id'] != '' ? $request['id'] : null;
        $moderator = isset($request['moderator']) && $request['moderator'] != '' ? $request['moderator'] : null;
        $subject = isset($request['subject']) && $request['subject'] != '' ? $request['subject'] : null;
        $room_id = isset($request['room_id']) && $request['room_id'] != '' ? $request['room_id'] : null;
        $time_from = isset($request['time_from']) && $request['time_from'] != '' ? $request['time_from'] : null;
        $time_to = isset($request['time_to']) && $request['time_to'] != '' ? $request['time_to'] : null;
        $members = isset($request['members_id']) && $request['members_id'] != '' ? $request['members_id'] : null;
        $remark = isset($request['remark']) && $request['remark'] != '' ? $request['remark'] : null;
        
        if($subject && $members && $operate && $moderator && $time_from && $time_to){
            $meeting = new Res_Model_Meeting();
            $user = new Application_Model_User();
            
            if($meeting->checkMeetingConflict($room_id, $time_from, $time_to, $id)){
                $result['success'] = false;
                $result['info'] = '会议室预定冲突，请重新选择会议室！';
            }else{
                // 检查成员会议时间冲突
                $membersAdded = $meeting->getConflictMembers($time_from, $time_to, $members, $id);
                
                if(count($membersAdded) > 0){
                    $result['success'] = false;
                    $result['info'] = '会议时间冲突！<br>'.implode('<br>', $membersAdded);
                }else{
                    $now = date('Y-m-d H:i:s');
                    $user_session = new Zend_Session_Namespace('user');
                    $user_id = $user_session->user_info['user_id'];
                    
                    $membersArr = explode(',', $members);
                    $membersCnameArr = array();
                    $membersEnameArr = array();
                    
                    foreach ($membersArr as $m){
                        $memberInfo = $user->getEmployeeInfoById($m);
                        
                        array_push($membersCnameArr, $memberInfo['cname']);
                        array_push($membersEnameArr, $memberInfo['ename']);
                    }
                    
                    $data = array(
                            'state'         => 0,
                            'room_id'       => $room_id,
                            'subject'       => $subject,
                            'moderator'     => $moderator,
                            'time_from'     => $time_from,
                            'time_to'       => $time_to,
                            'members'       => $members,
                            'members_cname' => implode(',', $membersCnameArr),
                            'members_ename' => implode(',', $membersEnameArr),
                            'remark'        => $remark,
                            'update_user'   => $user_id,
                            'update_time'   => $now
                    );
                    
                    if($operate == 'edit'){
                        $type = '更新';
                        $meeting->update($data, "id = ".$id);
                    }else{
                        $type = '新建';
                        $data['number'] = $meeting->getNewNum();
                        $data['create_user'] = $user_id;
                        $data['create_time'] = $now;
                        
                        $id = $meeting->insert($data);
                    }
                    
                    $meetingData = $meeting->getData(null, $id);
                    
                    $mail = new Application_Model_Log_Mail();
                    
                    $moderatorInfo = $user->getEmployeeInfoById($moderator);
                    
                    $mailContent = '<div>会议预定 - '.$type.'：</div>
                                    <div>
                                    <p><b>预订人：</b>'.$meetingData['updater'].'</p>
                                    <p><b>会议室：</b>'.$meetingData['room_name'].'</p>
                                    <p><b>主题：</b>'.$meetingData['subject'].'</p>
                                    <p><b>时间：</b>'.$meetingData['time_from'].' - '.$meetingData['time_to'].'</p>
                                    <p><b>主持人：</b>'.$moderatorInfo['cname'].'</p>
                                    <p><b>参会人员：</b>'.$meetingData['members_cname'].'</p>
                                    <p><b>备注：</b>'.$meetingData['remark'].'</p>
                                    </div>';
                    
                    $memberArr = explode(',', $meetingData['members']);
                    array_push($memberArr, $moderator);
                    $toArr = array();
                    
                    foreach ($memberArr as $m){
                        $memberInfo = $user->getEmployeeInfoById($m);
                        
                        $memberInfo = $user->getEmployeeInfoById($m);
                        
                        array_push($toArr, $memberInfo['email']);
                    }
                    
                    $mailData = array(
                            'type'      => '消息',
                            'subject'   => '会议预定',
                            'to'        => implode(',', $toArr),
                            'cc'        => $user_session->user_info['user_email'],
                            'user_id'   => $meetingData['create_user'],
                            'content'   => $mailContent,
                            'add_date'  => $now
                    );
                    
                    try {
                        // 记录邮件日志并发送邮件
                        $mail->insert($mailData);
                        $mail->send($mail->insert($mailData));
                    } catch (Exception $e) {
                        $result['success'] = false;
                        $result['info'] = $e->getMessage();
                    }
                }
            }
        }else{
            $result['success'] = false;
            $result['info'] = '参数错误';
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    public function getmeetingAction()
    {
        $request = $this->getRequest()->getParams();
        
        $option = isset($request['option']) ? $request['option'] : 'data';
        
        $condition = array(
                'key'       => isset($request['key']) && $request['key'] != '' ? $request['key'] : null,
                'state'     => isset($request['state']) ? $request['state'] : null,
                'date_from' => isset($request['date_from']) ? $request['date_from'] : date('Y-m-01'),
                'date_to'   => isset($request['date_to']) ? $request['date_to'] : date('Y-m-t'),
                'page'      => isset($request['page']) ? $request['page'] : 1,
                'limit'     => isset($request['limit']) ? $request['limit'] : 0,
                'type'      => $option
        );
        
        $meeting = new Res_Model_Meeting();
        $data = $meeting->getData($condition);
        
        if($option == 'csv'){
            $this->view->layout()->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);
        
            $h = new Application_Model_Helpers();
            $h->exportCsv($data, '会议预定');
        }else{
            echo Zend_Json::encode($data);
        }
        
        exit;
    }
}