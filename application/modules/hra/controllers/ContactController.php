<?php
/**
 * 2013-7-10 下午11:01:21
 * @author x.li
 * @abstract 
 */
class Hra_ContactController extends Zend_Controller_Action
{
    public function indexAction()
    {
        
    }
    
    public function sendmsgAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '发送成功'
        );
        
        $request = $this->getRequest()->getParams();
        // 标题
        $title = isset($request['msg_title']) ? $request['msg_title'] : null;
        // 优先级
        $priority = isset($request['msg_priority']) ? $request['msg_priority'] : null;
        // 是否发送邮件
        $sendmail = isset($request['msg_sendmail']) ? $request['msg_sendmail'] : null;
        // 内容
        $content = isset($request['msg_content']) ? $request['msg_content'] : null;
        // 备注
        $remark = isset($request['msg_remark']) ? $request['msg_remark'] : null;
        // 接收人用户ID
        $receivers_ids = isset($request['msg_receivers_ids']) ? $request['msg_receivers_ids'] : null;
        // 接收人邮箱
        $receivers_email = isset($request['msg_receivers_email']) ? $request['msg_receivers_email'] : null;
        // 接收人
        $receivers = isset($request['msg_receivers']) ? $request['msg_receivers'] : null;
        
        if($title && $priority && $content && $receivers_ids){
            $receiverIdArr = explode(',', $receivers_ids);
            $receiverEmailArr = explode(',', $receivers_email);
            $receiverArr = explode(',', $receivers);
            
            $now = date('Y-m-d H:i:s');
            $user_session = new Zend_Session_Namespace('user');
            $user_id = $user_session->user_info['user_id'];
            $user_name = $user_session->user_info['user_name'];
            
            // 记录消息内容
            $msg = new Application_Model_Log_Msg();
            
            $email = $sendmail == 'on' ? 1 : 0;
            
            $data = array(
                    'title'         => $title,
                    'priority'      => $priority,
                    'content'       => $content,
                    'remark'        => $remark,
                    'email'         => $email,
                    'receivers'     => $receivers,
                    'create_time'   => $now,
                    'create_user'   => $user_id,
                    'update_time'   => $now,
                    'update_user'   => $user_id
            );
            
            $msg_id = null;
            
            try {
                $msg_id = $msg->insert($data);
            } catch (Exception $e) {
                $result['success'] = false;
                $result['info'] = $e->getMessage();
        
                echo Zend_Json::encode($result);
        
                exit;
            }
            
            // 记录接收人信息
            $msgsend = new Application_Model_Log_Msgsend();
            
            foreach ($receiverIdArr as $r){
                $data = array(
                        'msg_id'    => $msg_id,
                        'user_id'   => $r,
                        'email'     => $email
                );
                
                try {
                    $msgsend->insert($data);
                } catch (Exception $e) {
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
                
                    echo Zend_Json::encode($result);
                
                    exit;
                }
            }
            
            // 当选择同时发送邮件时，向接收人发送邮件消息
            if($sendmail == 'on'){
                $mail = new Application_Model_Log_Mail();
                
                $mailContent = '<div>你有一条新消息：</div>
                            <div><b>发送人：</b>'.$user_name.'</div>
                            <div><b>发送时间：</b>'.$now.'</div>
                            <div><b>优先级：</b>'.$priority.'</div>
                            <div><b>内容：</b><br>'.$content.'<br></div>
                            <div><b>备注：</b>'.$remark.'</div>';
                
                for($i = 0; $i < count($receiverEmailArr); $i++){
                    $data = array(
                            'type'      => '消息',
                            'subject'   => $title,
                            'to'        => $receiverEmailArr[$i],
                            'user_id'   => $receiverArr[$i],
                            'content'   => $mailContent,
                            'add_date'  => $now
                    );
                    
                    try {
                        // 记录邮件日志并发送邮件
                        $mail->send($mail->insert($data));
                    } catch (Exception $e) {
                        $result['success'] = false;
                        $result['info'] = $e->getMessage();
                    
                        echo Zend_Json::encode($result);
                    
                        exit;
                    }
                }
            }
            
            // 记录日志
            $operate = new Application_Model_Log_Operate();
            $computer_name = gethostbyaddr(getenv("REMOTE_ADDR"));// 计算机名
            
            $data = array(
                    'operate'       => '发送消息',
                    'user_id'       => $user_id,
                    'target'        => 'Contact',
                    'computer_name' => $computer_name,
                    'ip'            => $_SERVER['REMOTE_ADDR'],
                    'remark'        => $receivers,
                    'time'          => $now
            );
            
            try {
                $operate->insert($data);
            } catch (Exception $e) {
                $result['success'] = false;
                $result['info'] = $e->getMessage();
            
                echo Zend_Json::encode($result);
            
                exit;
            }
        }else{
            $result['result'] = false;
            $result['info'] = '发送失败：信息不完整！';
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    public function getdeptlistAction()
    {
        $dept = array();
        
        $ids = array();
        
        $employee = new Hra_Model_Employee();
        
        $data = $employee->getContacts();
        
        foreach ($data as $d){
            if(!in_array($d['dept_id'], $ids)){
                array_push($ids, $d['dept_id']);
                
                array_push($dept, array(
                    'id'    => $d['dept_id'],
                    'name'  => $d['dept']
                ));
            }
        }
        
        echo Zend_Json::encode($dept);
        
        exit;
    }
    
    public function getcontactsAction()
    {
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        $type = isset($request['type']) ? $request['type'] : 0;
        $dept = isset($request['dept']) ? $request['dept'] : 0;
        $key = isset($request['key']) ? $request['key'] : null;
        
        $employee = new Hra_Model_Employee();
        
        $list = $employee->getContacts($type, $dept, $key);
        
        echo Zend_Json::encode($list);
        
        exit;
    }
    
    // 获取联系人列表
    public function getcontactAction()
    {
        $data = array();
        
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        $key = isset($request['key']) ? $request['key'] : null;
        
        $employee = new Hra_Model_Employee();
        
        $list = $employee->getContactList($key);
        
        echo Zend_Json::encode($list);
        
        exit;
    }
}