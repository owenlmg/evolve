<?php
/**
 * 2013-7-6 下午11:04:20
 * @author      x.li
 * @abstract    部门管理
 */
class Hra_DeptController extends Zend_Controller_Action
{
    public function indexAction()
    {

    }

    /**
     * @abstract    获取部门JSON数据
     * @param       number $parent_id 上级菜单ID
     * @return      null
     */
    public function getdeptAction()
    {
        $data = array();

        // 请求参数
        $request = $this->getRequest()->getParams();

        $option = isset($request['option']) ? $request['option'] : 'list';

        $dept = new Hra_Model_Dept();

        if($option == 'list'){
            $data = $dept->getList();
        }else{
            // 请求部门的层级ID
            $parentId = isset($request['parentId']) ? $request['parentId'] : 0;
            // 获取部门数据
            $data = $dept->getData($parentId);
        }
        
        // 将部门数据转为json格式并输出
        echo Zend_Json::encode($data);

        exit;
    }

    /**
     * @abstract    添加、删除、修改部门属性
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

        $dept = new Hra_Model_Dept();

        if(count($updated) > 0){
            foreach ($updated as $val){
                $data = array(
                        'parentid'      => $val->parentId,
                        'name'          => $val->name,
                        'manager_id'    => $val->manager_id,
                        'description'   => $val->description,
                        'remark'        => $val->remark,
                        'active'         => $val->active,
                        'update_time'   => $now,
                        'update_user'   => $user
                );

                $where = "id = ".$val->id;

                try {
                    $dept->update($data, $where);
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
                        'parentid'      => $val->parentId,
                        'name'          => $val->name,
                        'manager_id'    => $val->manager_id,
                        'description'   => $val->description,
                        'remark'        => $val->remark,
                        'active'         => $val->active,
                        'create_time'   => $now,
                        'create_user'   => $user,
                        'update_time'   => $now,
                        'update_user'   => $user
                );
                
                try{
                    $dept->insert($data);
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
                $employee = new Hra_Model_Employee();
                
                if($employee->fetchAll("dept_id = ".$val->id)->count() == 0){
                    try {
                        $dept->deleteDeptTreeData($val->id);
                    } catch (Exception $e){
                        $result['result'] = false;
                        $result['info'] = $e->getMessage();
                    
                        echo Zend_Json::encode($result);
                    
                        exit;
                    }
                }else{
                    $result['result'] = false;
                    $result['info'] = '部门ID'.$val->id.'已使用，不能删除';
                    
                    echo Zend_Json::encode($result);
                    
                    exit;
                }
            }
        }

        echo Zend_Json::encode($result);

        exit;
    }
}