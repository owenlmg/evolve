<?php

/**
 * 2013-7-20 下午2:20:48
 * @author mg.luo
 * @abstract 
 */
class Admin_FormController extends Zend_Controller_Action {

    public function indexAction() {
        
    }

    /**
     * @abstract    根据模块id获取自定义表单内容（智能表单实现时用）
     * @return      null
     */
    public function getattrAction() {
        // 请求参数
        $request = $this->getRequest()->getParams();

        $id = "";
        if (isset($request['id'])) {
            $id = $request['id'];
        }

        $form = new Admin_Model_Form();

        $data = $form->getFormByModel($id);
        for ($i = 0; $i < count($data); $i++) {
            $data[$i]['nullable'] = $data[$i]['nullable'] == 1 ? true : false;
            $data[$i]['multi'] = $data[$i]['multi'] == 1 ? true : false;
        }

        // 将模块数据转为json格式并输出
        echo Zend_Json::encode($data);

        exit;
    }

    /**
     * @abstract    根据模块id获取自定义表单内容和值
     * @return      null
     */
    public function getvalAction() {
        $data = array();

        // 请求参数
        $request = $this->getRequest()->getParams();

        $id = "";
        if (isset($request['menu'])) {
            $menu = $request['menu'];
        }

        $form = new Admin_Model_Form();

        $data = $form->getAttrAndValByMenu($menu);

        // 将模块数据转为json格式并输出
        echo Zend_Json::encode($data);

        exit;
    }

    /**
     * @abstract    获取枚举值JSON数据（智能表单实现时用）
     * @return      null
     */
    public function getcomboAction() {
        $request = $this->getRequest()->getParams();
        $enumlist = $request['option'];

        $enum = new Admin_Model_Enum();
        $data = $enum->getListByListId($enumlist);

        // 将模块数据转为json格式并输出
        echo Zend_Json::encode($data);

        exit;
    }

    /**
     * @abstract    根据模块id获取自定义表单内容
     * @return      null
     */
    public function getformsAction() {
        $data = array();

        // 请求参数
        $request = $this->getRequest()->getParams();

        $id = "";
        if (isset($request['model_id'])) {
            $id = $request['model_id'];
        }

        $form = new Admin_Model_Form();
        $enum = new Admin_Model_Enum();

        $data = $form->getListById($id);
        for ($i = 0; $i < count($data); $i++) {
            $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
            $data[$i]['update_time'] = strtotime($data[$i]['update_time']);
            $data[$i]['state'] = $data[$i]['state'] == 1 ? true : false;
            $data[$i]['nullable'] = $data[$i]['nullable'] == 1 ? true : false;

            // 默认值
            // 如果默认值是多个，需要重新检索
            if ($data[$i]['enumlistid'] && strpos($data[$i]['default_value'], ',') !== false) {
                $default = $data[$i]['default_value'];
                $list_id = $data[$i]['enumlistid'];
                $sql = "select GROUP_CONCAT(option_value) as option_value from oa_admin_enum where id in ($default) and list_id = $list_id";
                $db = $form->getAdapter();
                $result = $db->query($sql)->fetchObject();
                $data[$i]['option_key'] = $default;
                $data[$i]['option_value'] = $result->option_value;
            }
        }

        // 将模块数据转为json格式并输出
        echo Zend_Json::encode($data);

        exit;
    }

    /**
     * @abstract    获取模块JSON数据
     * @return      null
     */
    public function getmodelAction() {
        $data = array();

        // 请求参数
        $request = $this->getRequest()->getParams();

        $option = isset($request['option']) ? $request['option'] : 'list';

        $model = new Admin_Model_Model();

        if ($option == 'list') {
            $data = $model->getList();
        } else {
            // 请求模块的层级ID
            $parentId = isset($request['parentId']) ? $request['parentId'] : 0;
            $expanded = isset($request['expanded']) ? $request['expanded'] : 0;
            // 获取模块数据
            $data = $model->getData($parentId, true, $expanded);
        }

        // 将模块数据转为json格式并输出
        echo Zend_Json::encode($data);

        exit;
    }

    /**
     * 添加模块
     */
    function addmodelAction() {
        // 返回值数组
        $result = array(
            'success' => true,
            'result' => true,
            'info' => ''
        );

        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];

        $request = $this->getRequest()->getParams();
        $parentid = $request['parentid'];
        $nodeId = $request['nodeId'];
        $name = $request['name'];
        $method = $request['method'];

        if ($parentid && $name && $method == 'add') {
            if($parentid == 'root') {
                $parentid = 0;
            }
            $model = new Admin_Model_Model();
            // 检查是否有表单项目
            $form = new Admin_Model_Form();
            if ($form->fetchAll("model_id = $parentid and state = 1")->count() > 0) {
                $result['result'] = false;
                $result['info'] = '此节点还有表单项，要为其增加子节点，请先删除右侧列表中的项目。 ';
                echo Zend_Json::encode($result);
                exit;
            }
            // 检查是否存在
            if ($parentid != 'root' && $model->fetchAll("id = $parentid")->count() == 0) {
                $result['result'] = false;
                $result['info'] = '父节点不存在，请刷新后重试 ';
                echo Zend_Json::encode($result);
                exit;
            } else if ($model->fetchAll("parentid = $parentid and name = '$name'")->count() > 0) {
                $result['result'] = false;
                $result['info'] = '同名节点已存在 ';
                echo Zend_Json::encode($result);
                exit;
            } else {
                $data = array(
                    'parentid' => $parentid == 'root' ? 0 : $parentid,
                    'name' => $name,
                    'create_user' => $user,
                    'create_time' => $now,
                    'update_user' => $user,
                    'update_time' => $now
                );

                try {
                    $id = $model->insert($data);
                    $result['info'] = $id;
                } catch (Exception $e) {
                    $result['result'] = false;
                    $result['info'] = $e->getMessage();
                }
                echo Zend_Json::encode($result);
                exit;
            }
        } else if ($nodeId && $name && $method == 'edit') {
            $model = new Admin_Model_Model();
            // 检查是否有同名
            if ($model->fetchAll("parentid = $parentid and name = '$name'")->count() > 0) {
                $result['result'] = false;
                $result['info'] = '同名节点已存在 ';
                echo Zend_Json::encode($result);
                exit;
            } else {
                $data = array(
                    'name' => $name,
                    'update_user' => $user,
                    'update_time' => $now
                );
                $where = "id = '$nodeId'";
                try {
                    $model->update($data, $where);
                } catch (Exception $e) {
                    $result['result'] = false;
                    $result['info'] = $e->getMessage();
                }
                echo Zend_Json::encode($result);
                exit;
            }
        }
    }

    /**
     * 删除模块
     */
    function delmodelAction() {
        // 返回值数组
        $result = array(
            'success' => true,
            'result' => true,
            'info' => ''
        );

        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];

        $request = $this->getRequest()->getParams();
        $id = $request['id'];

        if ($id) {
            $model = new Admin_Model_Model();
            $formattr = new Admin_Model_Form();
            try {
            	$ids = $this->getsons($model, $id, array($id));
                $db = $model->getAdapter();
                $sql = "delete t1, t2, t3 from oa_admin_model t1 left JOIN oa_admin_formattr t2 on t1.id = t2.model_id left JOIN oa_admin_formval t3 on t2.id = t3.attrid where t1.id in (".implode(',', $ids).")";
                $db->query($sql);
            } catch (Exception $e) {
                $result['result'] = false;
                $result['info'] = $e->getMessage();
            }
            echo Zend_Json::encode($result);
            exit;
        }
    }
    
    private function getsons($model, $parent, $ids) {
    	$data = $model->fetchRow("parentid = $parent");
    	if($data) {
    		$ids[] = $data['id'];
    		return $this->getsons($model, $data['id'], $ids);
    	} else {
    		return $ids;
    	}
    }

    public function getmodeltreeAction() {
        // 请求参数
        $request = $this->getRequest()->getParams();

        $node = isset($request['node']) ? $request['node'] : 0;
        $node = $node == 'root' ? 0 : $node;

        $model = new Admin_Model_Model();

        $data = $model->getTree($node);

        // 将模块数据转为json格式并输出
        echo Zend_Json::encode($data) ;

        exit;
    }

    /**
     * @abstract    获取列类型JSON数据
     * @return      null
     */
    public function getcolumnAction() {
        $model = new Admin_Model_Column();

        // 将模块数据转为json格式并输出
        echo Zend_Json::encode($model->getList());

        exit;
    }

    /**
     * @abstract    获取已存在的枚举对象
     * @return      null
     */
    public function getenumlistAction() {
        $request = $this->getRequest()->getParams();
        $where = "1=1";
        foreach ($request as $k => $v) {
            $col = str_replace('search_', '', $k);
            if ($col != $k) {
                // 查询条件
                if ($col == 'state') {
                    $where .= " and state = " . $v;
                } else {
                    $where .= " and " . $col . " like '%" . $v . "%'";
                }
            }
        }

        $enumlist = new Admin_Model_EnumList();

        $data = $enumlist->getList($where);
        for ($i = 0; $i < count($data); $i++) {
            $data[$i]['state'] = $data[$i]['state'] == 1 ? true : false;
        }

        // 将模块数据转为json格式并输出
        echo Zend_Json::encode($data);

        exit;
    }

    /**
     * @abstract    获取选择用下拉框名称
     * @return      null
     */
    public function getenumlistselAction() {
        $request = $this->getRequest()->getParams();
        $key = "";
        if (isset($request['key'])) {
            $key = $request['key'];
        }

        $enumlist = new Admin_Model_EnumList();

        $data = $enumlist->getListForSel($key);

        // 将模块数据转为json格式并输出
        echo Zend_Json::encode($data);

        exit;
    }

    /**
     * @abstract    获取枚举值JSON数据
     * @return      null
     */
    public function getenumAction() {
        $request = $this->getRequest()->getParams();
        $enumlist = $request['enumlist'];
        $enum = new Admin_Model_Enum();
        $data = $enum->getAll($enumlist);

        for ($i = 0; $i < count($data); $i++) {
            $data[$i]['state'] = $data[$i]['state'] == 1 ? true : false;
        }

        // 将模块数据转为json格式并输出
        echo Zend_Json::encode($data);

        exit;
    }

    /**
     * @abstract    根据模块的ID返回模块的名称（父模块+子模块）
     * @return      null
     */
    public function getleafvalAction() {
        // 返回值数组
        $result = array(
            'success' => true,
            'id' => '',
            'name' => ''
        );

        $request = $this->getRequest()->getParams();
        $leaf = $request['leaf'];

        $model = new Admin_Model_Model();
        $data = $model->getLeafValue($leaf);
        $result['id'] = $data['id'];
        $result['name'] = $data['name'];

        echo Zend_Json::encode($result);

        exit;
    }

    /**
     * @abstract    添加、删除、修改下拉框值
     * @return      null
     */
    public function editenumAction() {
        // 返回值数组
        $result = array(
            'success' => true,
            'info' => '编辑成功'
        );

        $request = $this->getRequest()->getParams();

        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];

        $json = json_decode($request['json']);

        $updated = $json->updated;
        $inserted = $json->inserted;
        $deleted = $json->deleted;

        $enum = new Admin_Model_Enum();

        if (count($updated) > 0) {
            foreach ($updated as $val) {
                // 检查是否存在同名的key或value
                if ($enum->fetchAll("id != " . $val->id . " and list_id = " . $val->list_id . " and (option_value = '" . $val->option_value . "')")->count() > 0) {
                    $result['success'] = false;
                    $result['info'] = "值已存在";

                    echo Zend_Json::encode($result);

                    exit;
                }
                // 启用状态和自动编码设置
                $data = array(
                    'option_value' => $val->option_value,
                    'description' => $val->description,
                    'state' => $val->state,
                    'option_sort' => $val->option_sort,
                    'list_id' => $val->list_id
                );

                $where = "id = " . $val->id;

                try {
                    $enum->update($data, $where);
                } catch (Exception $e) {
                    $result['result'] = false;
                    $result['info'] = $e->getMessage();

                    echo Zend_Json::encode($result);

                    exit;
                }
            }
        }

        if (count($inserted) > 0) {
            foreach ($inserted as $val) {
                // 检查是否存在同名的key或value
                if ($enum->fetchAll("list_id = " . $val->list_id . " and (option_value = '" . $val->option_value . "')")->count() > 0) {
                    $result['success'] = false;
                    $result['info'] = "值已存在";

                    echo Zend_Json::encode($result);

                    exit;
                }
                $data = array(
                    'option_value' => $val->option_value,
                    'description' => $val->description,
                    'state' => $val->state,
                    'option_sort' => $val->option_sort,
                    'list_id' => $val->list_id
                );

                try {
                    $enum->insert($data);
                } catch (Exception $e) {
                    $result['result'] = false;
                    $result['info'] = $e->getMessage();

                    echo Zend_Json::encode($result);

                    exit;
                }
            }
        }

        if (count($deleted) > 0) {
            foreach ($deleted as $val) {
                // 检查此文件编码是否正在使用 TODO

                try {
                    $enum->delete("id = " . $val->id);
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

    /**
     * @abstract    添加、删除、修改下拉框列表
     * @return      null
     */
    public function editenumlistAction() {
        // 返回值数组
        $result = array(
            'success' => true,
            'info' => '编辑成功'
        );

        $request = $this->getRequest()->getParams();

        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];

        $json = json_decode($request['json']);

        $updated = $json->updated;
        $inserted = $json->inserted;
        $deleted = $json->deleted;

        $enumlist = new Admin_Model_Enumlist();

        if (count($updated) > 0) {
            foreach ($updated as $val) {
                // 检查是否存在同名的key或value
                if ($enumlist->fetchAll("id != " . $val->id . " and name = '" . $val->name . "'")->count() > 0) {
                    $result['success'] = false;
                    $result['info'] = "名称“$val->name”已存在";

                    echo Zend_Json::encode($result);

                    exit;
                }
                // 启用状态和自动编码设置
                $data = array(
                    'name' => $val->name,
                    'description' => $val->description,
                    'remark' => $val->remark,
                    'state' => $val->state,
                    'update_time' => $now,
                    'update_user' => $user
                );

                $where = "id = " . $val->id;

                try {
                    $enumlist->update($data, $where);
                } catch (Exception $e) {
                    $result['result'] = false;
                    $result['info'] = $e->getMessage();

                    echo Zend_Json::encode($result);

                    exit;
                }
            }
        }

        if (count($inserted) > 0) {
            foreach ($inserted as $val) {
                // 检查是否存在同名的key或value
                if ($enumlist->fetchAll("name = '" . $val->name . "'")->count() > 0) {
                    $result['success'] = false;
                    $result['info'] = "名称“$val->name”已存在";

                    echo Zend_Json::encode($result);

                    exit;
                }
                $data = array(
                    'name' => $val->name,
                    'description' => $val->description,
                    'remark' => $val->remark,
                    'state' => $val->state,
                    'create_time' => $now,
                    'create_user' => $user,
                    'update_time' => $now,
                    'update_user' => $user
                );

                try {
                    $enumlist->insert($data);
                } catch (Exception $e) {
                    $result['result'] = false;
                    $result['info'] = $e->getMessage();

                    echo Zend_Json::encode($result);

                    exit;
                }
            }
        }

        if (count($deleted) > 0) {
            foreach ($deleted as $val) {
                // 检查此下拉框是否正在使用 TODO

                try {
                    $enumlist->delete("id = " . $val->id);
                    // 同时删除下拉框具体选项
                    $enum = new Admin_Model_Enum();
                    $enum->delete("list_id = " . $val->id);
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

    /**
     * @abstract    添加自定义表单项
     * @return      null
     */
    public function saveAction() {
        // 返回值数组
        $result = array(
            'success' => true,
            'saveOrNot' => true,
            'info' => '添加成功'
        );

        $request = $this->getRequest()->getParams();

        $now = date('Y-m-d H:i:s');
        $user = 1; // 需替换为当前用户ID

        $val = (object) $request;

        $form = new Admin_Model_Form();

        $defaultValues = $val->defaultValues;

        // 编辑
        if ($val->id) {
            // 检查字段名称是否已存在
            if ($form->fetchAll("id != $val->id and model_id = " . $val->model_id . " and state = 1 and name = '" . $val->name . "'")->count() > 0) {
                $result['result'] = false;
                $result['saveOrNot'] = false;
                $result['info'] = "字段名称“" . $val->name . "”已经存在";

                echo Zend_Json::encode($result);

                exit;
            }
            $result['info'] = '提交成功';
            $data = array(
                'name' => $val->name,
                'type' => $val->type,
                'length' => isset($val->length) ? $val->length : "",
                'nullable' => isset($val->nullable) ? 1 : 0,
                'multi' => isset($val->multi) ? 1 : 0,
                'description' => $val->description,
                'remark' => $val->remark,
                'state' => isset($val->state) ? 1 : 0,
                'enumlist' => isset($val->enumlistid) ? $val->enumlistid : "",
                'model_id' => $val->model_id,
                'default_value' => $defaultValues ? $defaultValues : (isset($val->default_value) ? $val->default_value : ""),
                'update_time' => $now,
                'update_user' => $user
            );

            $where = "id = " . $val->id;

            try {
                $form->update($data, $where);
            } catch (Exception $e) {
                $result['result'] = false;
                $result['info'] = $e->getMessage();

                echo Zend_Json::encode($result);

                exit;
            }
        } else {
            // 检查字段名称是否已存在
            if ($form->fetchAll("model_id = " . $val->model_id . " and state = 1 and name = '" . $val->name . "'")->count() > 0) {
                $result['result'] = false;
                $result['saveOrNot'] = false;
                $result['info'] = "字段名称“" . $val->name . "”已经存在";

                echo Zend_Json::encode($result);

                exit;
            }
            // 获取顺序
            $sort = $form->fetchAll("model_id = " . $val->model_id . " and state = 1")->count() + 1;
            $data = array(
                'name' => $val->name,
                'type' => $val->type,
                'length' => isset($val->length) ? $val->length : "",
                'nullable' => isset($val->nullable) ? 1 : 0,
                'multi' => isset($val->multi) ? 1 : 0,
                'description' => $val->description,
                'remark' => $val->remark,
                'state' => isset($val->state) ? 1 : 0,
                'enumlist' => isset($val->enumlistid) ? $val->enumlistid : "",
                'model_id' => $val->model_id,
                'default_value' => $defaultValues ? $defaultValues : (isset($val->default_value) ? $val->default_value : ""),
                'form_sort' => $sort,
                'create_time' => $now,
                'create_user' => $user,
                'update_time' => $now,
                'update_user' => $user
            );

            try {
                $form->insert($data);
            } catch (Exception $e) {
                $result['result'] = false;
                $result['info'] = $e->getMessage();

                echo Zend_Json::encode($result);

                exit;
            }
        }

        echo Zend_Json::encode($result);

        exit;
    }

    /**
     * @abstract    排序
     * @return      null
     */
    public function updatesortAction() {
        // 返回值数组
        $result = array(
            'success' => true,
            'saveOrNot' => true,
            'info' => '保存成功'
        );

        $request = $this->getRequest()->getParams();
        $json = json_decode($request['json']);

        $updated = $json->updated;

        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];

        $form = new Admin_Model_Form();
        if (count($updated) > 0) {
            foreach ($updated as $val) {
                $id = $val->id;
                $form_sort = $val->form_sort;
                if ($id && $form_sort) {
                    $data = array(
                        'form_sort' => $form_sort,
                        'update_time' => $now,
                        'update_user' => $user
                    );
                    $where = "id = $id";
                    try {
                        $form->update($data, $where);
                    } catch (Exception $e) {
                        $result['result'] = false;
                        $result['info'] = $e->getMessage();

                        echo Zend_Json::encode($result);

                        exit;
                    }
                }
            }
        }

        echo Zend_Json::encode($result);

        exit;
    }

    /**
     * @abstract    删除自定义表单
     * @return      null
     */
    public function removeAction() {
        // 返回值数组
        $result = array(
            'success' => true,
            'info' => '删除成功'
        );

        $request = $this->getRequest()->getParams();

        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];

        $json = json_decode($request['json']);

        $deleted = $json->deleted;

        $form = new Admin_Model_Form();

        if (count($deleted) > 0) {
            foreach ($deleted as $val) {
                try {
                    $form->delete("id = " . $val->id);
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

}