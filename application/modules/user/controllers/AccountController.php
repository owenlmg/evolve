<?php
/**
 * 2013-11-7 下午10:24:11
 * @author x.li
 * @abstract 
 */
class User_AccountController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $user_session = new Zend_Session_Namespace('user');
        $user_id = $user_session->user_info['user_id'];
        
        $employee = new Hra_Model_Employee();
        
        $user_info = $employee->getEmployeeByUserId($user_id);
        
        $this->view->user_info = $user_info['info'];
    }
    
    public function editpwdAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '修改密码成功'
        );
        
        $user_session = new Zend_Session_Namespace('user');
        $user_id = $user_session->user_info['user_id'];
        
        $request = $this->getRequest()->getParams();
        
        $pwd0 = isset($request['pwd0']) ? $request['pwd0'] : null;
        $pwd1 = isset($request['pwd1']) ? $request['pwd1'] : null;
        $pwd2 = isset($request['pwd2']) ? $request['pwd2'] : null;
        
        if($pwd0 && $pwd1 && $pwd2){
            $user = new Application_Model_User();
            
            if($user->checkUserPwdById($user_id, $pwd0)){
                $employeeInfo = $user->getEmployeeInfoById($user_id);
                
                $pwd = md5($employeeInfo['number'].$pwd1);
                
                try {
                    $user->update(array('password' => $pwd), "id = ".$user_id);
                    
                    $computer_name = gethostbyaddr(getenv("REMOTE_ADDR"));// 计算机名
                    $now = date('Y-m-d H:i:s');
                    
                    $data = array(
                            'operate'       => '修改密码',
                            'target'        => 'UserAccount',
                            'computer_name' => $computer_name,
                            'ip'            => $_SERVER['REMOTE_ADDR'],
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
                $result['success'] = false;
                $result['info'] = '旧密码输入错误！';
            }
        }else{
            $result['success'] = false;
            $result['info'] = '新、旧密码不能为空！';
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
}