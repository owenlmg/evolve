<?php

/**
 * 2013-7-16 下午8:54:30
 * @author x.li
 * @abstract 
 */
class Admin_FlowController extends Zend_Controller_Action {

    public function indexAction() {
        
    }

    /**
     * 获取流程
     */
    public function getflowforcomboAction() {
        $flow = new Admin_Model_Flow();

        $data = $flow->getListForCombo();
        echo Zend_Json::encode($data);

        exit;
    }

    /**
     * grid用
     */
    public function getlistAction() {
        // 请求参数
        $request = $this->getRequest()->getParams();
        $limit = $request['limit'];
        $start = $request['start'];

        $where = "1=1";
        foreach ($request as $k => $v) {
            if ($v != '') {
                $col = str_replace('search_', '', $k);
                if ($col != $k) {
                    // 查询条件
                    $where .= " and " . $col . " like '%" . $v . "%'";
                }
            }
        }

        $flow = new Admin_Model_Flow();
        $step = new Admin_Model_Step();
        $employee = new Hra_Model_Employee();

        $data = $flow->getList($where, $start, $limit);
        for ($i = 0; $i < count($data); $i++) {
            if ($data[$i]['step_ids']) {
                $stepData = $step->getListByFlow($data[$i]['step_ids']);
	            
	            $tips = "<table class='eoa_table'><tr><th width='80'>阶段</th><th>人员</th><th>角色</th></tr>";
                $step_name = "";
                for ($k = 0; $k < count($stepData); $k++) {
                    if ($step_name)
                        $step_name .= "->";
                    $step_name .= $stepData[$k]['step_name'];
                    
                    $tips .= "<tr><td>".$stepData[$k]['step_name']."</td><td>";
                    $users = $stepData[$k]['user'];
                    $roles = $stepData[$k]['dept'];
                    if($users) {
	                    $userData = $employee->getAdapter()->query("select group_concat(cname) as names from oa_employee where id in ( " . $users . ")")->fetchObject();
	                    $user_name = $userData->names;
	                    $tips .= $user_name;
	                }
	                $tips .= "</td><td>";
	                if($roles) {
	                    $deptData = $employee->getAdapter()->query("select group_concat(name) as names from oa_user_role where id in ( " . $roles . ")")->fetchObject();
	                    $role_name = $deptData->names;
	                    $tips .= $role_name;
	                }
	                $tips .= "</td></tr>";
                }
                $data[$i]['tips'] = $tips;
                $data[$i]['step_names'] = $step_name;
            }
        }
        $resutl = array(
            "totalCount" => count($flow->getList($where, null, null)),
            "topics" => $data
        );
        echo Zend_Json::encode($resutl);

        exit;
    }

    /**
     * grid用
     */
    public function getstepAction() {
        // 请求参数
        $request = $this->getRequest()->getParams();
        $ids = $request['ids'];

        $step = new Admin_Model_Step();
        $employee = new Hra_Model_Employee();

        $where = "t1.id in ($ids)";
        $data = $step->getListByFlow($ids);
        for ($i = 0; $i < count($data); $i++) {
            $step_user = $data[$i]['user'];
            if ($step_user) {
                $step_user_name = $employee->getInfosByOneLine($step_user);
                $data[$i]['step_user_name'] = $step_user_name['cname'];
            }

            $step_dept = $data[$i]['dept'];
            if ($step_dept) {
                $tmp = $step->getAdapter()->query("select group_concat(name) as name from oa_user_role where id in ( " . $step_dept . ")")->fetchObject();
                $step_dept_name = $tmp->name;
                $data[$i]['step_dept_name'] = $step_dept_name;
            }
        }
        echo Zend_Json::encode($data);

        exit;
    }

    /**
     * @abstract    保存
     * @return      null
     */
    public function saveAction() {
        // 返回值数组
        $result = array(
            'success' => true,
            'result' => true,
            'info' => '保存成功'
        );

        $request = $this->getRequest()->getParams();

        $val = (object) $request;
        $id = $val->id;

        $flow = new Admin_Model_Flow();

        if ($id) {
            // 检查是否存在
            if ($flow->fetchAll("id != " . $id . " and flow_name = '" . $val->flow_name . "'")->count() > 0) {
                $result['result'] = false;
                $result['info'] = "流程“" . $val->flow_name . "”已经存在";

                echo Zend_Json::encode($result);

                exit;
            }
            $data = array(
                'flow_name' => $val->flow_name,
                'description' => $val->description,
                'remark' => $val->remark,
                'step_ids' => $val->json
            );
            $where = "id=" . $id;
            try {
                $flow->update($data, $where);
            } catch (Exception $e) {
                $result['result'] = false;
                $result['info'] = $e->getMessage();

                echo Zend_Json::encode($result);

                exit;
            }
        } else {
            // 检查是否存在
            if ($flow->fetchAll("flow_name = '" . $val->flow_name . "'")->count() > 0) {
                $result['result'] = false;
                $result['info'] = "流程“" . $val->flow_name . "”已经存在";

                echo Zend_Json::encode($result);

                exit;
            }
            $data = array(
                'flow_name' => $val->flow_name,
                'description' => $val->description,
                'remark' => $val->remark,
                'step_ids' => $val->json
            );
            try {
                $flow->insert($data);
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
     * @abstract    删除
     * @return      null
     */
    public function removeAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '删除成功'
        );
        $request = $this->getRequest()->getParams();
        $json = json_decode($request['json']);
        $deleted    = $json->deleted;
        $flow = new Admin_Model_Flow();
        if(count($deleted) > 0){
            foreach ($deleted as $val){
                try {
                    $flow->delete("id = ".$val->id);
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