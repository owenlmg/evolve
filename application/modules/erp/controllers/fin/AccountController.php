<?php
/**
 * 2014-3-30 上午11:40:53
 * @author x.li
 * @abstract 
 */
class Erp_Fin_AccountController extends Zend_Controller_Action
{
    public function indexAction()
    {
        
    }
    
    /**
     * @abstract    获取科目JSON数据
     * @param       number $parentid 上级菜单ID
     * @return      null
     */
    public function getaccountAction()
    {
        $data = array();
    
        // 请求参数
        $request = $this->getRequest()->getParams();
    
        $option = isset($request['option']) ? $request['option'] : 'list';
    
        $account = new Erp_Model_Fin_Account();
    
        if($option == 'list'){
            $data = $account->getList();
        }else{
            // 请求科目的层级ID
            $parentId = isset($request['parentId']) ? $request['parentId'] : 0;
            // 获取科目数据
            $data = $account->getData($parentId);
        }
    
        // 将科目数据转为json格式并输出
        echo Zend_Json::encode($data);
    
        exit;
    }
    
    /**
     * @abstract    添加、删除、修改科目属性
     * @return      null
     */
    public function editAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '编辑成功'
        );
    
        $request = $this->getRequest()->getParams();
    
        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['user_id'];
    
        $json = json_decode($request['json']);
    
        $updated    = $json->updated;
        $inserted   = $json->inserted;
        $deleted    = $json->deleted;
    
        $account = new Erp_Model_Fin_Account();
    
        if(count($updated) > 0){
            foreach ($updated as $val){
                if($account->fetchAll("id != ".$val->id." and name = '".$val->name."' or code = '".$val->code."'")->count() > 0){
                    $result['result'] = false;
                    $result['info'] = '科目'.$val->name.' ['.$val->code.'] 已存在，编辑失败！';
                    
                    echo Zend_Json::encode($result);
                    
                    exit;
                }
            }
            
            foreach ($updated as $val){
                $data = array(
                        'parentid'     => $val->parentId,
                        'code'          => $val->code,
                        'name'          => $val->name,
                        'description'   => $val->description,
                        'remark'        => $val->remark,
                        'active'         => $val->active,
                        'update_time'   => $now,
                        'update_user'   => $user
                );
    
                $where = "id = ".$val->id;
    
                try {
                    $account->update($data, $where);
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
                if($account->fetchAll("name = '".$val->name."' or code = '".$val->code."'")->count() > 0){
                    $result['result'] = false;
                    $result['info'] = '科目：'.$val->name.' ['.$val->code.'] 已存在，编辑失败！';
            
                    echo Zend_Json::encode($result);
            
                    exit;
                }
            }
            
            foreach ($inserted as $val){
                $data = array(
                        'parentid'     => $val->parentId,
                        'code'          => $val->code,
                        'name'          => $val->name,
                        'description'   => $val->description,
                        'remark'        => $val->remark,
                        'active'         => $val->active,
                        'create_time'   => $now,
                        'create_user'   => $user,
                        'update_time'   => $now,
                        'update_user'   => $user
                );
    
                try{
                    $account->insert($data);
                } catch (Exception $e){
                    $result['result'] = false;
                    $result['info'] = $e->getMessage();
    
                    echo Zend_Json::encode($result);
    
                    exit;
                }
            }
        }
    
        if(count($deleted) > 0){
            foreach ($deleted as $val){
                try {
                    $account->deleteaccountTreeData($val->id);
                } catch (Exception $e){
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
}