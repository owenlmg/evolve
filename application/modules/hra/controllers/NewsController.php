<?php
/**
 * 2013-7-15 下午9:47:26
 * @author x.li
 * @abstract 
 */
class Hra_NewsController extends Zend_Controller_Action
{
    public function indexAction()
    {
        
    }
    
    // 获取公告标题列表
    public function gettitlelistAction()
    {
        $request = $this->getRequest()->getParams();
        
        $public = isset($request['public']) ? $request['public'] : 0;
        $type = isset($request['type']) ? $request['type'] : 0;
        $page = isset($request['page']) ? $request['page'] : 1;
        $limit = isset($request['limit']) ? $request['limit'] : 0;
        
        $news = new Hra_Model_News();
        
        $data = $news->getTitleList($public, $type, $page, $limit);
        
        echo Zend_Json::encode($data);
        
        exit;
    }
    
    // 公告评论
    public function commentAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '评论成功'
        );
        
        $request = $this->getRequest()->getParams();
        
        $news_id = isset($request['news_id']) ? $request['news_id'] : null;
        $comment = isset($request['comment']) ? $request['comment'] : null;
        $anonymity = isset($request['anonymity']) ? $request['anonymity'] : null;
        
        if($news_id && $comment){
            $anonymity = $anonymity == 'true' ? 1 : 0;
            
            $user_session = new Zend_Session_Namespace('user');
            $user = $user_session->user_info['user_id'];
            $now = date('Y-m-d H:i:s');
            
            $data = array(
                    'news_id'       => $news_id,
                    'comment'       => $comment,
                    'anonymity'     => $anonymity,
                    'create_time'   => $now,
                    'update_time'   => $now,
                    'user_id'       => $user
            );
            
            $newsComment = new Hra_Model_Newscomment();
            
            try {
                $newsComment->insert($data);
            } catch (Exception $e) {
                $result['result'] = false;
                $result['info'] = $e->getMessage();
            
                echo Zend_Json::encode($result);
            
                exit;
            }
        }else{
            $result['result'] = false;
            $result['info'] = '文章ID和评论内容不能为空';
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    // 查看公告
    public function viewAction()
    {
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        $id = isset($request['id']) ? $request['id'] : 0;
        
        if($id != 0){
            $news = new Hra_Model_News();
            $info = $news->fetchRow("id = ".$id)->toArray();
            $this->view->content = $info;
            
            $newsComment = new Hra_Model_Newscomment();
            $comment = $newsComment->getComment($id);
            
            $this->view->comment = $comment;
        }else{
            $this->view->all = '或以删除，打开失败！';
        }
    }
    
    // 获取公告类别列表
    public function gettypeAction()
    {
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        $option = isset($request['option']) ? $request['option'] : 'list';
        
        $type = new Hra_Model_Newstype();
        
        if($option == 'list'){
            echo Zend_Json::encode($type->getList());
        }else{
            echo Zend_Json::encode($type->getData());
        }
        
        exit;
    }
    
    // 获取公告内容
    public function getcontentAction()
    {
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        $news_id = isset($request['news_id']) ? $request['news_id'] : '';
        
        if($news_id != ''){
            $news = new Hra_Model_News();
            
            $data = $news->fetchAll("id = ".$news_id);
            $content = $data[0]['content'] == '' ? '' : $data[0]['content'];
            
            echo Zend_Json::encode(array('success' => true, 'data' => array('news_id' => $news_id, 'news_content' => $content)));
        }
        
        exit;
    }
    
    // 获取公告列表
    public function getnewsAction()
    {
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        $option = isset($request['option']) ? $request['option'] : 'list';
        
        $news = new Hra_Model_News();
        
        if($option == 'list'){
            echo Zend_Json::encode($news->getList());
        }else{
            // 查询条件
            $condition = array(
                    'key'       => isset($request['key']) ? $request['key'] : '',
                    'date_from' => isset($request['date_from']) ? $request['date_from'] : '',
                    'date_to'   => isset($request['date_to']) ? $request['date_to'] : '',
                    'type_id'   => isset($request['type_id']) ? $request['type_id'] : '',
                    'type'      => $option
            );
            
            $data = $news->getData($condition);
            
            if($option == 'csv'){
                $this->view->layout()->disableLayout();
                $this->_helper->viewRenderer->setNoRender(true);
            
                $h = new Application_Model_Helpers();
                $h->exportCsv($data);
            }else{
                echo Zend_Json::encode($data);
            }
        }
    
        exit;
    }
    
    // 删除评论
    public function deletecommentAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '编辑成功'
        );
        
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        $id = isset($request['comment_id']) ? $request['comment_id'] : null;
        
        if($id){
            $comment = new Hra_Model_Newscomment();
            
            try {
                $comment->update(array('deleted' => 1), "id = ".$id);
            } catch (Exception $e) {
                $result['result'] = false;
                $result['info'] = $e->getMessage();
            
                echo Zend_Json::encode($result);
            
                exit;
            }
        }else{
            $result['result'] = false;
            $result['info'] = 'ID不能为空';
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    // 修改评论
    public function editcommentAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '编辑成功'
        );
        
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        $id = isset($request['comment_id']) ? $request['comment_id'] : null;
        $content = isset($request['content']) ? $request['content'] : null;
        
        if($id){
            if($content){
                $now = date('Y-m-d H:i:s');
                $user_session = new Zend_Session_Namespace('user');
                $user_id = $user_session->user_info['user_id'];
                
                $comment = new Hra_Model_Newscomment();
                
                $data = array(
                        'comment'   => $content,
                        'update_user'   => $user_id,
                        'update_time'   => $now
                );
                
                try {
                    $comment->update($data, "id = ".$id);
                } catch (Exception $e) {
                    $result['result'] = false;
                    $result['info'] = $e->getMessage();
                
                    echo Zend_Json::encode($result);
                
                    exit;
                }
            }else{
                $result['result'] = false;
                $result['info'] = '内容不能为空！';
            }
        }else{
            $result['result'] = false;
            $result['info'] = 'ID不能为空';
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    // 保存公告内容
    public function editAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '编辑成功'
        );
        
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        $id = isset($request['id']) ? $request['id'] : null;
        $content = isset($request['content']) ? $request['content'] : null;
        $type_id = isset($request['type_id']) ? $request['type_id'] : null;
        $active = isset($request['active']) ? ($request['active'] == 'on' ? 1 : 0) : 1;
        $public = isset($request['public']) ? ($request['public'] == 'on' ? 1 : 0) : 0;
        $title = isset($request['title']) ? $request['title'] : null;
        $subhead = isset($request['subhead']) ? $request['subhead'] : null;
        $summary = isset($request['summary']) ? $request['summary'] : null;
        
        if($title && $type_id){
            $now = date('Y-m-d H:i:s');
            $user_session = new Zend_Session_Namespace('user');
            $user_id = $user_session->user_info['user_id'];
            
            if($id){
                $data = array(
                        'content' => $content,
                        'type_id' => $type_id,
                        'active' => $active,
                        'public' => $public,
                        'title' => $title,
                        'subhead' => $subhead,
                        'summary' => $summary,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
                
                $news = new Hra_Model_News();
                
                try {
                    $news->update($data, "id = ".$id);
                } catch (Exception $e) {
                    $result['result'] = false;
                    $result['info'] = $e->getMessage();
                
                    echo Zend_Json::encode($result);
                
                    exit;
                }
            }else{
                $data = array(
                        'content' => $content,
                        'type_id' => $type_id,
                        'active' => $active,
                        'public' => $public,
                        'title' => $title,
                        'subhead' => $subhead,
                        'summary' => $summary,
                        'create_time'   => $now,
                        'create_user'   => $user_id,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
                //echo '<pre>';print_r($data);exit;
                $news = new Hra_Model_News();
                
                try {
                    $news->insert($data);
                    $result['info'] = '添加成功';
                } catch (Exception $e) {
                    $result['result'] = false;
                    $result['info'] = $e->getMessage();
                    
                    echo Zend_Json::encode($result);
                
                    exit;
                }
            }
        }else{
            $result['result'] = false;
            $result['info'] = '类别为空，请求失败！';
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    // 删除公告
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
            $now = date('Y-m-d H:i:s');
            $user_session = new Zend_Session_Namespace('user');
            $user_id = $user_session->user_info['user_id'];
            
            $news = new Hra_Model_News();
            
            try {
                $data = array(
                        'deleted' => 1,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
                
                $news->update($data, "id = ".$id);
            } catch (Exception $e) {
                $result['result'] = false;
                $result['info'] = $e->getMessage();
            
                echo Zend_Json::encode($result);
            
                exit;
            }
        }else{
            $request['success'] = false;
            $request['info'] = 'ID不能为空！';
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    // 编辑公告属性
    public function updateAction()
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
        
        $news = new Hra_Model_News();
        
        if(count($updated) > 0){
            foreach ($updated as $val){
                $type_id = $val->type_id == '' ? null : $val->type_id;
                
                $create_time = str_replace('T', ' ', $val->create_time);
                
                $data = array(
                        'type_id'       => $val->type_id,
                        'active'        => $val->active,
                        'public'        => $val->public,
                        'deleted'       => $val->deleted,
                        'create_time'   => $create_time,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
        
                $where = "id = ".$val->id;
                
                try {
                    $news->update($data, $where);
                } catch (Exception $e) {
                    $result['result'] = false;
                    $result['info'] = $e->getMessage();
        
                    echo Zend_Json::encode($result);
        
                    exit;
                }
            }
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    // 编辑公告类别
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
        
        $type = new Hra_Model_Newstype();
        
        if(count($updated) > 0){
            foreach ($updated as $val){
                $data = array(
                        'active'        => $val->active,
                        'public'        => $val->public,
                        'name'          => $val->name,
                        'description'   => $val->description,
                        'remark'        => $val->remark,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
        
                $where = "id = ".$val->id;
        
                try {
                    $type->update($data, $where);
                } catch (Exception $e) {
                    $result['result'] = false;
                    $result['info'] = $e->getMessage();
        
                    echo Zend_Json::encode($result);
        
                    exit;
                }
            }
        }
        
        if(count($inserted) > 0){
            foreach ($inserted as $val){
                $data = array(
                        'active'        => $val->active,
                        'public'        => $val->public,
                        'name'          => $val->name,
                        'description'   => $val->description,
                        'remark'        => $val->remark,
                        'create_time'   => $now,
                        'create_user'   => $user_id,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
                
                try{
                    $type->insert($data);
                } catch (Exception $e){
                    $result['result'] = false;
                    $result['info'] = $e->getMessage();
            
                    echo Zend_Json::encode($result);
            
                    exit;
                }
            }
        }
        
        if(count($deleted) > 0){
            $news = new Hra_Model_News();
            
            foreach ($deleted as $val){
                if($news->fetchAll("type_id = ".$val->id)->count() == 0){
                    try {
                        $type->delete("id = ".$val->id);
                    } catch (Exception $e){
                        $result['result'] = false;
                        $result['info'] = $e->getMessage();
                    
                        echo Zend_Json::encode($result);
                    
                        exit;
                    }
                }else{
                    $result['result'] = false;
                    $result['info'] = '类别ID'.$val->id.'存在关联公告信息，不能删除';
                    
                    echo Zend_Json::encode($result);
                    
                    exit;
                } 
            }
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
}