<?php
/**
 * 2013-9-20 下午10:13:52
 * @author x.li
 * @abstract 
 */
class Hra_SealController extends Zend_Controller_Action
{
    public function indexAction()
    {
        
    }
    
    public function applyAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '申请成功'
        );
        
        $request = $this->getRequest()->getParams();
        
        $seal_id = isset($request['apply_seal_id']) ? $request['apply_seal_id'] : null;
        $review_user = isset($request['review_user']) ? $request['review_user'] : null;
        $apply_reason = isset($request['apply_reason']) ? $request['apply_reason'] : null;
        
        if($seal_id){
            $sealuse = new Hra_Model_Sealuse();
            
            $now = date('Y-m-d H:i:s');
            $user_session = new Zend_Session_Namespace('user');
            $user_id = $user_session->user_info['user_id'];
            
            $data = array(
                    'seal_id'       => $seal_id,
                    'review_user'   => $review_user,
                    'apply_reason' => $apply_reason,
                    'apply_user'    => $user_id,
                    'apply_time'    => $now
            );
            
            try{
                $sealuse->insert($data);
                
                $mail = new Application_Model_Log_Mail();
                $seal = new Hra_Model_Seal();
                $member = new Admin_Model_Member();
                $employee = new Hra_Model_Employee();
                
                /* $toIds = $member->getUserids('印章管理员');
                
                $toAddress = array();
                
                foreach ($toIds as $toId){
                    $em = $employee->getEmployeeByUserId($toId);
                    array_push($toAddress, $em['info']['email']);
                } */
                
                $toAddress = array();
                
                $em = $employee->getEmployeeByUserId($review_user);
                
                array_push($toAddress, $em['info']['email']);
                
                $sealData = $seal->fetchRow("id = ".$seal_id)->toArray();
                
                $content = '<div>你有一个新的印章使用申请，请登录系统审核：</div>
                                    <div>
                                    <p><b>印章名称：</b>'.$sealData['name'].'</p>
                                    <p><b>事由：</b>'.$apply_reason.'</p>
                                    <p><b>申请人：</b>'.$user_session->user_info['user_name'].'</p>
                                    <p><b>申请时间：</b>'.$now.'</p>
                                    </div>';
                
                $data = array(
                        'type'      => '消息',
                        'subject'   => '印章-使用申请',
                        'to'        => implode(',', $toAddress),
                        'cc'        => $user_session->user_info['user_email'],
                        'user_id'   => $review_user,
                        'content'   => $content,
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
            } catch (Exception $e){
                $result['success'] = false;
                $result['info'] = $e->getMessage();
            }
        }else{
            $result['success'] = false;
            $result['info'] = '未选择印章';
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    // 维护印章
    public function editsealAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '编辑成功'
        );
        
        $request = $this->getRequest()->getParams();
        
        // 操作类别（新建、更新、删除）
        $type = isset($request['edit_type']) ? $request['edit_type'] : '';
        
        $seal = new Hra_Model_Seal();
        
        if($type == 'new' || $type == 'edit'){
            $now = date('Y-m-d H:i:s');
            $user_session = new Zend_Session_Namespace('user');
            $user_id = $user_session->user_info['user_id'];
            
            $data = array(
                    'active'        => $request['active'],
                    'name'          => $request['name'],
                    'administrator' => $request['administrator'],
                    'description'   => $request['description'],
                    'remark'        => $request['remark'],
                    'update_time'   => $now,
                    'update_user'   => $user_id
            );
            
            if ($type == 'new') {
                $data['create_time'] = $now;
                $data['create_user'] = $user_id;
                
                try{
                    $seal->insert($data);
                } catch (Exception $e){
                    $result['result'] = false;
                    $result['info'] = $e->getMessage();
                
                    echo Zend_Json::encode($result);
                
                    exit;
                }
            }elseif ($type == 'edit'){
                try {
                    $seal->update($data, "id = ".$request['seal_id']);
                } catch (Exception $e) {
                    $result['result'] = false;
                    $result['info'] = $e->getMessage();
                
                    echo Zend_Json::encode($result);
                
                    exit;
                }
            }
        }elseif ($type == 'delete'){
            try {
                $seal->delete("id = ".$request['seal_id']);
            } catch (Exception $e){
                $result['result'] = false;
                $result['info'] = $e->getMessage();
            
                echo Zend_Json::encode($result);
            
                exit;
            }
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    // 获取印章列表
    public function getsealAction()
    {
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        $option = isset($request['option']) ? $request['option'] : 'data';
        
        $seal = new Hra_Model_Seal();
        
        if($option == 'list'){
            echo Zend_Json::encode($seal->getList());
        }else{
            // 获取部门数据并转为json格式
            echo Zend_Json::encode($seal->getData());
        }
        
        exit;
    }
    
    // 获取审核列表
    public function getreviewAction()
    {
        $request = $this->getRequest()->getParams();
        
        $user_session = new Zend_Session_Namespace('user');
        $review_user = $user_session->user_info['user_id'];
        
        if($review_user){
            $sealuse = new Hra_Model_Sealuse();
            
            echo Zend_Json::encode($sealuse->getReviewList($review_user));
        }
        
        exit;
    }
    
    // 审核印章使用申请
    public function reviewAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '审核成功'
        );
        
        $request = $this->getRequest()->getParams();
        
        $ids = isset($request['ids']) ? $request['ids'] : null;
        $operate = isset($request['operate']) ? $request['operate'] : null;
        $opinion = isset($request['opinion']) ? $request['opinion'] : null;
        
        if($ids && $operate){
            $idArr = explode(',', $ids);
            
            $state = $operate == 'approve' ? 2 : 3;
            
            $now = date('Y-m-d H:i:s');
            $user_session = new Zend_Session_Namespace('user');
            $user_id = $user_session->user_info['user_id'];
            
            $data = array(
                    'state'             => $state,
                    'review_user'       => $user_id,
                    'review_time'       => $now,
                    'review_state'      => 1,
                    'review_opinion'    => $opinion
            );
            
            $sealuse = new Hra_Model_Sealuse();
            
            foreach ($idArr as $id){
                try {
                    $sealuse->update($data, "id = ".$id);
                } catch (Exception $e){
                    $result['result'] = false;
                    $result['info'] = $e->getMessage();
                
                    echo Zend_Json::encode($result);
                
                    exit;
                }
            }
        }else{
            $result['result'] = false;
            $result['info'] = '审核ID不能为空';
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    public function deletelogAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '删除成功'
        );
        
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        $id = isset($request['id']) ? $request['id'] : null;
        
        if($id){
            $log = new Hra_Model_Sealuse();
            
            try {
                $log->delete("id = ".$id);
            } catch (Exception $e){
                $result['success'] = false;
                $result['info'] = $e->getMessage();
            
                echo Zend_Json::encode($result);
            
                exit;
            }
        }else{
            $result['success'] = false;
            $result['info'] = '删除失败，ID不能为空！';
            
            echo Zend_Json::encode($result);
            
            exit;
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    // 获取印章使用记录
    public function getlogAction()
    {
        // 请求参数
        $request = $this->getRequest()->getParams();
    
        $option = isset($request['option']) ? $request['option'] : null;
    
        $condition = array(
                'key'       => isset($request['key']) ? $request['key'] : '',
                'date_from' => isset($request['date_from']) ? $request['date_from'] : date('Y-m-01'),
                'date_to'   => isset($request['date_to']) ? $request['date_to'] : date('Y-m-t'),
                'page'      => isset($request['page']) ? $request['page'] : 1,
                'limit'     => isset($request['limit']) ? $request['limit'] : 0,
                'option'    => $option
        );
    
        $sealuse = new Hra_Model_Sealuse();
    
        $data = $sealuse->getData($condition);
    
        if($option == 'csv'){
            $this->view->layout()->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);
    
            $h = new Application_Model_Helpers();
            $h->exportCsv($data);
        }else{
            echo Zend_Json::encode($data);
        }
    
        exit;
    }
}