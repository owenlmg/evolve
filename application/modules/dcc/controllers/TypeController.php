<?php

/**
 * 2013-7-11 上午10:56:20
 * @author      mg.luo
 * @abstract    文件分类管理
 */
class Dcc_TypeController extends Zend_Controller_Action {

    public function indexAction() {

    }

    /**
     * @abstract    获取文件分类JSON数据
     * @return      null
     */
    public function gettypeAction() {
        $request = $this->getRequest()->getParams();
        $whereSearch = "1=1";
        foreach ($request as $k => $v) {
            if ($v) {
            	if($k == 'search_tag') {
            		$whereSearch .= " and (ifnull(t1.code,'') like '%$v%' or ifnull(t1.fullname,'') like '%$v%' or ifnull(t1.name,'') like '%$v%' or ifnull(t1.description,'') like '%$v%' or ifnull(t1.remark,'') like '%$v%' or ifnull(t1.secretlevel,'') like '%$v%')";
            	} else if($k == 'search_type') {
            		if($v) {
	            		$v = json_decode($v);
	            		$v = implode(',', $v);
	            		if($v) {
	            		    $whereSearch .= " and t1.category in ($v)";
	            		}
            		}
            	} else {
	                $col = str_replace('search_', '', $k);
	                if ($col != $k) {
	                    // 查询条件
	                    $whereSearch .= " and ifnull(t1." . $col . ",'') like '%" . $v . "%'";
	                }
            	}
            }
        }
        $type = new Dcc_Model_Type();
        $flow = new Admin_Model_Flow();
        $step = new Admin_Model_Step();

        $data = $type->getTypeList($whereSearch);
        for ($i = 0; $i < count($data); $i++) {
            $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
            if($data[$i]['state'] != 1) {
            	$data[$i]['update_time'] = strtotime($data[$i]['update_time']);
            } else {
            	$data[$i]['update_time'] = "";
            }

            $data[$i]['state'] = $data[$i]['state'] == 1 ? true : false;
            $data[$i]['autocode'] = $data[$i]['autocode'] == 1 ? true : false;
            $data[$i]['modelrequire'] = $data[$i]['modelrequire'] == 1 ? true : false;
            $data[$i]['filerequire'] = $data[$i]['filerequire'] == 1 ? true : false;

            // 查询所有审核阶段
            if ($data[$i]['step_ids']) {
                $stepRows = $step->getListByFlow($data[$i]['step_ids']);
                $step_name = "";
                if (count($stepRows) > 0) {
                    $first = true;
                    foreach ($stepRows as $row) {
                        if ($step_name)
                            $step_name .= "->";
                        $step_name .= $row['step_name'];
                    }
                }
                $data[$i]['step'] = $step_name;
            }

            if ($data[$i]['dev_step_ids']) {
                $devStepRows = $step->getListByFlow($data[$i]['dev_step_ids']);
                $dev_step_name = "";
                if (count($devStepRows) > 0) {
                    $first = true;
                    foreach ($devStepRows as $row) {
                        if ($dev_step_name)
                            $dev_step_name .= "->";
                        $dev_step_name .= $row['step_name'];
                    }
                }
                $data[$i]['dev_step'] = $dev_step_name;
            }

            if ($data[$i]['resp_emp_id']) {
                $emp = $data[$i]['resp_emp_id'];
                $sql = "select GROUP_CONCAT(name) as resp_emp_name from oa_employee_post where id in ($emp)";
                $db = $step->getAdapter();
                $result = $db->query($sql)->fetchObject();
                $data[$i]['resp_emp_name'] = $result->resp_emp_name;
            }
            if ($data[$i]['resp_dept_id']) {
            	$dept = $data[$i]['resp_dept_id'];
                $sql = "select GROUP_CONCAT(name) as resp_dept_name from oa_employee_dept where id in ($dept)";
                $db = $step->getAdapter();
                $result = $db->query($sql)->fetchObject();
                $data[$i]['resp_dept_name'] = $result->resp_dept_name;
            }
            if ($data[$i]['grant_dept_id']) {
            	$dept = $data[$i]['grant_dept_id'];
                $sql = "select GROUP_CONCAT(name) as grant_dept_name from oa_employee_dept where id in ($dept)";
                $db = $step->getAdapter();
                $result = $db->query($sql)->fetchObject();
                $data[$i]['grant_dept_name'] = $result->grant_dept_name;
            }
        }

        echo Zend_Json::encode($data);

        exit;
    }

    /**
     * @abstract    获取文件分类JSON数据
     * @return      null
     */
    public function gettypeforcodeAction() {
        $type = new Dcc_Model_Type();

        echo Zend_Json::encode($type->getTypeForCode());

        exit;
    }

    /**
     * @abstract    获取流水号产生方式JSON数据
     * @return      null
     */
    public function getautoAction() {
        $auto = new Dcc_Model_Auto();

        $data = $auto->getAuto();

        echo Zend_Json::encode($data);

        exit;
    }

    /**
     * @abstract    获取文件模板
     * @return      null
     */
    public function gettemplateAction() {
        $template = new Dcc_Model_Template();

        $data = $template->getList();

        echo Zend_Json::encode($data);

        exit;
    }

    public function getpostAction() {
        $post = new Hra_Model_Post();
        echo Zend_Json::encode($post->getList());
        exit;
    }

    /**
     * @abstract    删除文件简号
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

        $type = new Dcc_Model_Type();

        if (count($deleted) > 0) {
            foreach ($deleted as $val) {
                try {
                    $type->delete("id = " . $val->id);
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
     * @abstract    添加文件简号
     * @return      null
     */
    public function saveAction() {
        // 返回值数组
        $result = array(
            'success' => true,
            'saveOrNot' => true,
            'info' => '提交成功'
        );

        $request = $this->getRequest()->getParams();

        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];

        $val = (object) $request;

        $type = new Dcc_Model_Type();

        if (isset($val->id) && $val->id) {
            // 编辑
            // 检查是否文件简号已经存在
            if ($type->fetchAll("id != " . $val->id . " and code = '" . $val->code . "'")->count() > 0) {
                $result['result'] = false;
                $result['info'] = "文件简号“" . $val->code . "”已经存在";

                echo Zend_Json::encode($result);

                exit;
            }
            // 启用状态和自动编码设置
            $data = array(
                'resp_emp_id' => str_replace('E', '', $val->resp_emp_id),
                'resp_dept_id' => str_replace('D', '', $val->resp_dept_id),
                'grant_dept_id' => str_replace('D', '', $val->grant_dept_id),
                'template' => $val->template,
                'filerequire' => isset($val->filerequire) ? 1 : 0,
                'secretlevel' => $val->secretlevel,
                'code' => $val->code,
                'name' => $val->name,
                'category' => $val->category,
                'duration' => $val->duration,
                'fullname' => $val->fullname,
                'length' => $val->length,
                'description' => $val->description,
                'remark' => $val->remark,
                'state' => isset($val->state) ? 1 : 0,
                'autocode' => isset($val->autocode) ? 1 : 0,
                'modelrequire' => isset($val->modelrequire) ? 1 : 0,
                'autotype' => $val->auto_id,
                'model_id' => $val->model_id,
                'flow_flg' => isset($val->flow_flg) ? 1 : 0,
                'flow_id' => isset($val->flow_flg) ? $val->flow_id : 0,
                'dev_model_id' => $val->dev_model_id,
                'dev_flow_id' => isset($val->flow_flg) ? $val->dev_flow_id : 0,
                'apply_flow_id' => isset($val->apply_flow_id) ? $val->apply_flow_id : 0,
                'update_time' => $now,
                'update_user' => $user
            );

            $where = "id = " . $val->id;

            try {
                $type->update($data, $where);
            } catch (Exception $e) {
                $result['result'] = false;
                $result['info'] = $e->getMessage();

                echo Zend_Json::encode($result);

                exit;
            }
        } else {
            // 检查是否文件简号已经存在
            if ($type->fetchAll("code = '" . $val->code . "'")->count() > 0) {
                $result['result'] = false;
                $result['saveOrNot'] = false;
                $result['info'] = "文件简号“" . $val->code . "”已经存在";

                echo Zend_Json::encode($result);

                exit;
            }
            $data = array(
                'resp_emp_id' => str_replace('E', '', $val->resp_emp_id),
                'resp_dept_id' => str_replace('D', '', $val->resp_dept_id),
                'grant_dept_id' => str_replace('D', '', $val->grant_dept_id),
                'template' => $val->template,
                'filerequire' => isset($val->filerequire) ? 1 : 0,
                'secretlevel' => $val->secretlevel,
                'code' => strtoUpper($val->code),
                'name' => $val->name,
                'category' => $val->category,
                'duration' => $val->duration,
                'fullname' => $val->fullname,
                'length' => $val->length,
                'description' => $val->description,
                'remark' => $val->remark,
                'state' => isset($val->state) ? 1 : 0,
                'autocode' => isset($val->autocode) ? 1 : 0,
                'modelrequire' => isset($val->modelrequire) ? 1 : 0,
                'autotype' => $val->auto_id,
                'model_id' => $val->model_id,
                'flow_flg' => isset($val->flow_flg) ? 1 : 0,
                'flow_id' => isset($val->flow_flg) ? $val->flow_id : 0,
                'dev_model_id' => $val->dev_model_id,
                'dev_flow_id' => isset($val->flow_flg) ? $val->dev_flow_id : 0,
                'apply_flow_id' => isset($val->apply_flow_id) ? $val->apply_flow_id : 0,
                'create_time' => $now,
                'create_user' => $user,
                'update_time' => $now,
                'update_user' => $user
            );

            try {
                $type->insert($data);
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

    public function getcodemasterAction() {
        $request = $this->getRequest()->getParams();
        if ($request['type'] == 'category') {
            $type = 4;
        }
        if ($type) {
            $where = "type=" . $type;
            $codemaster = new Admin_Model_Codemaster();
            $data = $codemaster->getList($where);
            // 转为json格式并输出
            echo Zend_Json::encode($data);
        }

        exit;
    }

    // 导出CSV
    public function exportcsvAction()
    {
        $request = $this->getRequest()->getParams();
        $whereSearch = "1=1";
        foreach ($request as $k => $v) {
            if ($v) {
            	if($k == 'search_tag') {
            		$whereSearch .= " and (ifnull(t1.code,'') like '%$v%' or ifnull(t1.fullname,'') like '%$v%' or ifnull(t1.name,'') like '%$v%' or ifnull(t1.description,'') like '%$v%' or ifnull(t1.remark,'') like '%$v%' or ifnull(t1.secretlevel,'') like '%$v%')";
            	} else if($k == 'search_type') {
            		if($v) {
	            		$v = json_decode($v);
	            		$v = implode(',', $v);
	            		if($v) {
	            		    $whereSearch .= " and t1.category in ($v)";
	            		}
            		}
            	} else {
	                $col = str_replace('search_', '', $k);
	                if ($col != $k) {
	                    // 查询条件
	                    $whereSearch .= " and ifnull(t1." . $col . ",'') like '%" . $v . "%'";
	                }
            	}
            }
        }
        print(chr(0xEF).chr(0xBB).chr(0xBF));

        $record = new Dcc_Model_Record();
        $type = new Dcc_Model_Type();
        $flow = new Admin_Model_Flow();
        $step = new Admin_Model_Step();

        $data = $type->getTypeList($whereSearch);
        $data_csv = array();
        $title = array(
                'cnt'               => '#',
                'id'                => 'ID',
                'code'              => '文件简号',
                'name'              => '中文解释',
                'fullname'          => '全称',
                'category'          => '文件类别',
                'resp_dept_id'       => '责任部门',
                'resp_emp_id'         => '责任人',
                'filerequire'             => '文件是否必须',
                'secretlevel'               => '文件密级',
                'template'              => '文件模板编号',
                'duration'      => '审核时效',
                'length'           => '流水号长度',
                'autotype'           => '自动编码方式',
                'description'     => '描述',
                'model_id'               => '新文件自定义表单',
                'dev_model_id'               => '新版自定义表单',
                'flow_id'               => '新文件审批流程',
                'dev_flow_id'         => '升版文件审批流程',
                'remark'               => '备注',
                'creater'      => '添加人',
                'create_time'            => '生效日期',
                'update_time'      => '取消日期'
        );

        $title = $this->object_array($title);
        $date = date('YmdHsi');
        $filename = "type_list" . $date;
        $path = "../temp/" . $filename . ".csv";
        $file = fopen($path, "w");
        fputcsv($file, $title);
        array_push($data_csv, $title);

        $typeids = array();
        $typenames = array();
        $k = 0;
        for($i = 0; $i < count($data); $i++) {
            if($data[$i]['state'] != 1) {
            } else {
            	$data[$i]['update_time'] = "";
            }

            $data[$i]['state'] = $data[$i]['state'] == 1 ? true : false;
            $data[$i]['autocode'] = $data[$i]['autocode'] == 1 ? true : false;
            $data[$i]['modelrequire'] = $data[$i]['modelrequire'] == 1 ? true : false;
            $data[$i]['filerequire'] = $data[$i]['filerequire'] == 1 ? '是' : '否';

            // 查询所有审核阶段
            if ($data[$i]['step_ids']) {
                $stepRows = $step->getListByFlow($data[$i]['step_ids']);
                $step_name = "";
                if (count($stepRows) > 0) {
                    $first = true;
                    foreach ($stepRows as $row) {
                        if ($step_name)
                            $step_name .= "->";
                        $step_name .= $row['step_name'];
                    }
                }
                $data[$i]['step'] = $step_name;
            }

            if ($data[$i]['dev_step_ids']) {
                $devStepRows = $step->getListByFlow($data[$i]['dev_step_ids']);
                $dev_step_name = "";
                if (count($devStepRows) > 0) {
                    $first = true;
                    foreach ($devStepRows as $row) {
                        if ($dev_step_name)
                            $dev_step_name .= "->";
                        $dev_step_name .= $row['step_name'];
                    }
                }
                $data[$i]['dev_step'] = $dev_step_name;
            }

            $data[$i]['resp_emp_name'] = "";
            $data[$i]['resp_dept_name'] = "";
            $data[$i]['grant_dept_name'] = "";
            if ($data[$i]['resp_emp_id']) {
                $emp = $data[$i]['resp_emp_id'];
                $sql = "select GROUP_CONCAT(name) as resp_emp_name from oa_employee_post where id in ($emp)";
                $db = $step->getAdapter();
                $result = $db->query($sql)->fetchObject();
                $data[$i]['resp_emp_name'] = $result->resp_emp_name;
            }
            if ($data[$i]['resp_dept_id']) {
            	$dept = $data[$i]['resp_dept_id'];
                $sql = "select GROUP_CONCAT(name) as resp_dept_name from oa_employee_dept where id in ($dept)";
                $db = $step->getAdapter();
                $result = $db->query($sql)->fetchObject();
                $data[$i]['resp_dept_name'] = $result->resp_dept_name;
            }
            if ($data[$i]['grant_dept_id']) {
            	$dept = $data[$i]['grant_dept_id'];
                $sql = "select GROUP_CONCAT(name) as grant_dept_name from oa_employee_dept where id in ($dept)";
                $db = $step->getAdapter();
                $result = $db->query($sql)->fetchObject();
                $data[$i]['grant_dept_name'] = $result->grant_dept_name;
            }
            //$data[$i]['record'] = $record->getHis($data[$i]['id'], 'materiel');
            $d = $data[$i];

            $k++;

            $info = array(
                'cnt'               => $k,
                'id'                => $d['id'],
                'code'              => $d['code'],
                'name'              => $d['name'],
                'fullname'          => $d['fullname'],
                'category'          => $d['category_name'],
                'resp_dept_id'      => $d['resp_dept_name'],
                'resp_emp_id'       => $d['resp_emp_name'],
                'filerequire'       => $d['filerequire'],
                'secretlevel'       => $d['secretlevel'],
                'template'          => $d['template'],
                'duration'          => $d['duration'],
                'length'            => $d['length'],
                'autotype'          => $d['auto_description'],
                'description'       => $d['description'],
                'model_id'          => $d['model_name'],
                'dev_model_id'      => $d['dev_model_name'],
                'flow_id'           => $d['flow_name'],
                'dev_flow_id'       => $d['dev_flow_name'],
                'remark'            => $d['remark'],
                'creater'           => $d['creater'],
                'create_time'       => $d['create_time'],
                'update_time'       => $d['update_time']
        );
            $d = $this->object_array($info);
            array_push($data_csv, $info);
            fputcsv($file, $d);
        }

        fclose($file);
        $this->operate("文件分类导出");

        echo $filename;

        exit;
    }

    /**
     *
     * 把对象类型转换为数组类型，并转码
     * @param object $array
     * @return array $a
     */
    private function object_array($array) {
        $a = array();
        foreach ($array as $key => $v) {
            if (preg_match('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $v)) {
                $v = str_replace("T", " ", $v);
            }
//            $a[$key] = iconv('utf-8', 'gbk//IGNORE', $v);
            $v = str_replace("μ", "[u]", $v);
            $v = str_replace("ø", "[o]", $v);
//            mb_convert_encoding ("Ø","HTML-ENTITIES","UTF-8");
            $a[$key] = iconv('utf-8', 'GBK//TRANSLIT', $v);
//            $a[$key] = $v;
        }
        return $a;
    }

    private function operate($type) {
        // 记录日志
        $operate = new Application_Model_Log_Operate();

        $now = date('Y-m-d H:i:s');

        $computer_name = gethostbyaddr(getenv("REMOTE_ADDR"));

        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['user_id'];

        $data = array(
            'user_id' => $user,
            'operate' => $type,
            'target' => 'DccType',
            'computer_name' => $computer_name,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'time' => $now
        );

        $operate->insert($data);
    }

}