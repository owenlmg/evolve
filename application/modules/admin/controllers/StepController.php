<?php

/**
 * 2013-8-6 10:54:30
 * @author mg.luo
 * @abstract
 */
class Admin_StepController extends Zend_Controller_Action {

    public function indexAction() {
        
    }

    /**
     * 获取阶段
     */
    public function getListByFlow() {
        // 参数
        $request = $this->getRequest()->getParams();
        $id = $request['id'];

        $model = new Admin_Model_Step();

        $data = $model->getListByFlow($id);
        echo Zend_Json::encode($data);

        exit;
    }

    public function getlistAction() {
        // 请求参数
        $request = $this->getRequest()->getParams();
        $limit = $request['limit'];
        $start = $request['start'];

        $step = new Admin_Model_Step();
        $employee = new Hra_Model_Employee();

        $where = " 1=1";
        foreach ($request as $k => $v) {
            if ($v != '') {
                $col = str_replace('search_', '', $k);
                if ($col != $k) {
                    // 查询条件
                    $where .= " and " . $col . " like '%" . $v . "%'";
                }
            }
        }
        $data = $step->getList($where, $start, $limit, null);
        for ($i = 0; $i < count($data); $i++) {
            $step_user = $data[$i]['user'];
            if ($step_user) {
                $step_user_name = $employee->getInfosByOneLine($step_user);
                $data[$i]['step_user_name'] = $step_user_name['cname'];
            }
            // 重新检索角色信息
            if ($data[$i]['dept']) {
                $role = $data[$i]['dept'];
                $sql = "select GROUP_CONCAT(name) as step_dept_name from oa_user_role where id in ($role)";
                $db = $step->getAdapter();
                $result = $db->query($sql)->fetchObject();
                $data[$i]['step_dept_name'] = $result->step_dept_name;
            }
            $data[$i]['manager'] = $data[$i]['manager'] == 1 ? true : false;
        }
        $resutl = array(
            "totalCount" => count($step->getList($where, null, null, null)),
            "topics" => $data
        );
        echo Zend_Json::encode($resutl);

        exit;
    }

    public function getcodemasterAction() {
        $request = $this->getRequest()->getParams();
        if ($request['type'] == 'passrule') {
            $type = 3;
        } else if ($request['type'] == 'returnmethod') {
            $type = 2;
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

    public function saveAction() {
        // 返回值数组
        $result = array(
            'success' => true,
            'result' => true,
            'info' => '提交成功'
        );

        $request = $this->getRequest()->getParams();
        $val = (object) $request;

        $step = new Admin_Model_Step();
        if ($val->id) {
            // 检查阶段名称是否已经存在
            if ($step->fetchAll("id != ".$val->id." and step_name = '" . $val->step_name . "'")->count() > 0) {
                $result['result'] = false;
                $result['info'] = "阶段名称已经存在";

                echo Zend_Json::encode($result);

                exit;
            }
            $data = array(
                'step_name' => $val->step_name,
                'user' => str_replace('E', '', $val->user_id),
                'dept' => $val->dept_id,
                'manager' => isset($val->manager) ? 1 : 0,
                'method' => $val->method,
                'return' => $val->return,
                'description' => $val->description,
                'remark' => $val->remark
            );

            $id = $val->id;
            $where = "id = " . $id;
            try {
                $step->update($data, $where);
                $result['info'] = "编辑成功";
            } catch (Exception $e) {
                $result['result'] = false;
                $result['info'] = $e->getMessage();

                echo Zend_Json::encode($result);

                exit;
            }
        } else {
            // 检查阶段名称是否已经存在
            if ($step->fetchAll("step_name = '" . $val->step_name . "'")->count() > 0) {
                $result['result'] = false;
                $result['info'] = "阶段名称已经存在";

                echo Zend_Json::encode($result);

                exit;
            }
            $data = array(
                'step_name' => $val->step_name,
                'user' => str_replace('E', '', $val->user_id),
                'dept' => $val->dept_id,
                'manager' => isset($val->manager) ? 1 : 0,
                'method' => $val->method,
                'return' => $val->return,
                'description' => $val->description,
                'remark' => $val->remark
            );

            try {
                $step->insert($data);
                $result['info'] = "添加成功";
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
        $step = new Admin_Model_Step();
        if(count($deleted) > 0){
            foreach ($deleted as $val){
                try {
                    $step->delete("id = ".$val->id);
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