<?php
/**
 * 2013-11-17 下午3:04:57
 * @author x.li
 * @abstract 
 */
class User_MsgController extends Zend_Controller_Action
{
    public function indexAction()
    {
        
    }
    
    public function getnotreadnumAction()
    {
        $user_session = new Zend_Session_Namespace('user');
        $user_id = $user_session->user_info['user_id'];
        
        $msg = new Application_Model_Log_Msgsend();
        $cnt = $msg->fetchAll("user_id = ".$user_id." and view = 0")->count();
        
        $result = array(
                'success'   => true,
                'cnt'      => $cnt
        );
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    public function getlistAction()
    {
        $data = array();
        
        // 请求参数
        $request = $this->getRequest()->getParams();

        // shortlist & list （短列表 & 列表）
        $type = isset($request['type']) ? $request['type'] : 'list';
        $page = isset($request['page']) ? $request['page'] : 1;
        $limit = isset($request['limit']) ? $request['limit'] : 0;
        $key = isset($request['key']) ? $request['key'] : null;
        $date_from = isset($request['date_from']) ? $request['date_from'] : null;
        $date_to = isset($request['date_to']) ? $request['date_to'] : null;
        
        $user_session = new Zend_Session_Namespace('user');
        $user_id = $user_session->user_info['user_id'];
        
        $condition = array(
                'user_id'   => $user_id,
                'key'       => $key,
                'date_from' => $date_from,
                'date_to'   => $date_to
        );
        
        $msg = new Application_Model_Log_Msgsend();
        
        $list = $msg->getList($type, $page, $limit, $condition);
        
        /* echo '<pre>';
        print_r($list);
        exit; */
        
        echo Zend_Json::encode($list);
        
        exit;
    }
    
    public function updatestatusAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '修改密码成功'
        );
        
        $request = $this->getRequest()->getParams();
        
        if(isset($request['id'])){
            $msgSend = new Application_Model_Log_Msgsend();
            
            try {
                $msgSend->update(array('view' => 1), "id = ".$request['id']);
            } catch (Exception $e) {
                $result['success'] = false;
                $result['info'] = $e->getMessage();
            }
        }else{
            $result['success'] = false;
            $result['info'] = 'ID不能为空，消息状态更新失败！';
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
}