<?php
/**
 * 2013-10-24 上午1:11:20
 * @author x.li
 * @abstract 
 */
class Home_LoginController extends Zend_Controller_Action
{
    public function indexAction()
    {
        // 查找用户最后一次登录记录是否有勾选“记住登录状态”
        $computer_name = gethostbyaddr(getenv("REMOTE_ADDR"));// 计算机名
        $ip = $_SERVER['REMOTE_ADDR'];// IP地址
        
        $operate = new Application_Model_Log_Operate();
        
        $log = $operate->fetchRow("target = 'Login' and ip = '".$ip."' and computer_name = '".$computer_name."'", "time desc");
        
        $this->view->username = SYS_EMAIL_SUFFIX;
        
        /* if($log){
            if($log['params'] != ''){
                $data = $log->toArray();
                
                $params = explode(',', $data['params']);
                
                $this->view->username = $params[0];
            }
        } */
    }
    /**
     * 用户登录
     */
    public function loginAction()
    {
        if(isset($_SESSION['user_id'])){
            $this->_forward('index','index');
        }else{
            if(strtolower($_SERVER['REQUEST_METHOD'])=='post'){
                $request = $this->getRequest()->getParams();
                
                $username = isset($request['username']) ? $request['username'] : '';
                $password = isset($request['password']) ? $request['password'] : '';
                $remember_login = isset($request['remember_login']) ? $request['remember_login'] : '';
                
                $condition = array(
                        'username' => $username,
                        'password' => $password
                );
                
                $user = new Application_Model_User();
                
                $result = $user->checkLogin($condition);
                
                if($result['success']){
                    // 记录日志
                    $operate = new Application_Model_Log_Operate();
                    
                    $now = date('Y-m-d H:i:s');
                    
                    $computer_name = gethostbyaddr(getenv("REMOTE_ADDR"));
                    
                    // 记住用户登录状态（下次登录根据IP和计算机名匹配初始化登录界面并在提交时区分验证）
                    $params = $result['user_info']['user_email'].','.$result['user_info']['md5_key'];
                    
                    $data = array(
                            'user_id'       => $result['user_info']['user_id'],
                            'operate'       => '登录',
                            'target'        => 'Login',
                            'computer_name' =>  $computer_name,
                            'ip'            =>  $_SERVER['REMOTE_ADDR'],
                            'params'        => $params,
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
                    
                    // 记录用户最后登录时间
                    try {
                        $user->update(array('last_login_time' => $now), "id = ".$result['user_info']['user_id']);
                    } catch (Exception $e) {
                        $result['success'] = false;
                        $result['info'] = $e->getMessage();
                        
                        echo Zend_Json::encode($result);
                        
                        exit;
                    }
                    
                    // 记录Session
                    $user_session = new Zend_Session_Namespace('user');
                    $user_session->user_info = $result['user_info'];
                    
                    if($password == '123456'){
                        $result['need_change_pwd'] = true;
                    }
                }
                
                echo Zend_Json::encode($result);
                
                exit;
            }
        }
    }
    
    /**
     * 退出系统，跳转到登录界面
     */
    public function logoutAction()
    {
        // 记录日志
        $operate = new Application_Model_Log_Operate();
        
        $now = date('Y-m-d H:i:s');
        $computer_name = gethostbyaddr(getenv("REMOTE_ADDR"));
        
        $user_session = new Zend_Session_Namespace('user');
        $user_id = $user_session->user_info['user_id'];
        
        $data = array(
                'user_id'       => $user_id,
                'operate'       => '注销',
                'target'        => 'Logout',
                'computer_name' =>  $computer_name,
                'ip'            =>  $_SERVER['REMOTE_ADDR'],
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
        
        Zend_Session::destroy(true, true);
        
        $this->_redirect('home/login');
        
        exit;
    }
    
    // 保存密码
    public function savepasswordAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '修改密码成功'
        );
        
        $request = $this->getRequest()->getParams();
        
        $user_id = isset($request['user_id']) ? $request['user_id'] : null;
        $key = isset($request['key']) ? $request['key'] : null;
        $pwd = isset($request['pwd1']) ? $request['pwd1'] : null;
        
        // 检查输入
        if($user_id && $pwd){
            $user = new Application_Model_User();
            
            $employeeInfo = $user->getEmployeeInfoById($user_id);
            
            $pwd = md5($employeeInfo['number'].$pwd);
            
            try {
                $user->update(array('password' => $pwd), "id = ".$user_id);
                
                // 当检查到key，更新重置密码邮件key
                if($key){
                    $mail = new Application_Model_Log_Mail();
                    
                    try {
                        $mail->clearKey($key);
                    } catch (Exception $e) {
                        $result['success'] = false;
                        $result['info'] = $e->getMessage();
                    
                        echo Zend_Json::encode($result);
                    
                        exit;
                    }
                }
            } catch (Exception $e) {
                $result['success'] = false;
                $result['info'] = $e->getMessage();
            
                echo Zend_Json::encode($result);
            
                exit;
            }
        }else{
            $result['success'] = false;
            $result['info'] = '输入错误，请重新输入！';
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    // 重置密码
    public function resetpasswordAction()
    {
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        if(isset($request['user_id']) && isset($request['key'])){
            $user_id = $request['user_id'];
            $key = $request['key'];
            
            $mail = new Application_Model_Log_Mail();
            
            $m = $mail->fetchRow("user_id = ".$user_id." and datediff(curdate(), send_time) <= 1", "id desc");
            
            if($m){
                $mm = $m->toArray();
                
                if($mm['key'] == ''){
                    echo '<script>alert("链接已使用，请重新提交重置申请获取新链接。");window.location.href="'.HOME_PATH.'/public/home/login"</script>';
                    exit;
                }else if($mm['key'] != $key){
                    echo '<script>alert("链接校验码错误，请重新提交重置申请。");window.location.href="'.HOME_PATH.'/public/home/login"</script>';
                    exit;
                }
            }else{
                echo '<script>alert("重置密码已过期，请重新提交重置申请。");window.location.href="'.HOME_PATH.'/public/home/login"</script>';
                exit;
            }
        }else{
            $this->_redirect('home/login');
        }
    }
    
    // 发送找回密码邮件
    public function getpasswordAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '密码找回邮件已发往你的邮箱，请通过邮件中的链接地址重设密码。'
        );
    
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        if(isset($request['email'])){
            $email = $request['email'];
            
            $user = new Application_Model_User();
            
            $checkEmail = $user->checkEmail($email);
            
            if($checkEmail['success']){
                $mail = new Application_Model_Log_Mail();
    
                $now = date('Y-m-d H:i:s');
                
                $key = time().rand(1, 100);
                $link = HOME_PATH.'/public/login/resetpassword/user_id/'.$checkEmail['user_id'].'/key/'.$key;
    
                $data = array(
                        'type'      => '找回密码',
                        'subject'   => '找回密码',
                        'to'        => $email,
                        'key'       => $key,
                        'user_id'   => $checkEmail['user_id'],
                        'content'   => '请通过以下链接重设密码：<br><a href="'.$link.'">'.$link.'</a>',
                        'add_date'  => $now
                );
                
                try {
                    // 记录邮件日志
                    $mailId = $mail->insert($data);
                    // 发送邮件
                    $mail->send($mailId);
                    // 记录日志
                    $computer_name = gethostbyaddr(getenv("REMOTE_ADDR"));// 计算机名
                    
                    $data = array(
                            'operate'       => '找回密码',
                            'target'        => 'Login',
                            'computer_name' => $computer_name,
                            'ip'            => $_SERVER['REMOTE_ADDR'],
                            'remark'        => $email,
                            'time'          => $now
                    );
                    
                    $operate = new Application_Model_Log_Operate();
                    
                    try {
                        $operate->insert($data);
                    } catch (Exception $e) {
                        $result['success'] = false;
                        $result['info'] = $e->getMessage();
                    
                        echo Zend_Json::encode($result);
                    
                        exit;
                    }
                } catch (Exception $e) {
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
    
                    echo Zend_Json::encode($result);
    
                    exit;
                }
            }else{
                $result = $checkEmail;
            }
        }else{
            $result = array(
                    'success'   => false,
                    'info'      => '邮箱地址错误'
            );
        }
        
        echo Zend_Json::encode($result);
    
        exit;
    }
}