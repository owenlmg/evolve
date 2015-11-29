<?php
/**
 * 2015-02-02
 * @author      larry.luo
 * @abstract    任务管理
 */
class User_TaskController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
    }

    /**
     * 获取当前用户的下级用户
     */
    public function getuserAction() {
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];
        $dept = $user_session->user_info['dept_id'];

        // 查询直接下级
        $result = array(
            'id' => $user,
            'leaf' => true,
            'cname' => $user_session->user_info['user_name'],
            'expanded' => true
        );
        $result = array();
        $where = "id != $user and active=1 and ";
        if($user == 1) {
            // 管理员
            $where .= "1=1";
        } else {
            $where .= "manager_id = ".$user;
        }
        $employee = new Hra_Model_Employee();
        $data = $employee->getJoinList($where, array(), array('id', 'cname', 'number', 'leaf' => new Zend_Db_Expr('true')));
        if(count($data) > 0) {
            //array_unshift($data, $result);
            $result['children'] = $data;
        }
        echo Zend_Json::encode($result);
        exit;
    }

    public function getsubusersAction() {
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];
        // 请求参数
        $request = $this->getRequest()->getParams();
        $key = isset($request['search_key']) ? $request['search_key'] : '';

        $employee = new Hra_Model_Employee();
        // 查询条件
        $cols = array("number", "cname", "ename", "id_card", "email", "tel", "address");
        $arr=preg_split('/\s+/',trim($key));
        for ($i=0;$i<count($arr);$i++) {
            $tmp = array();
            foreach($cols as $c) {
                $tmp[] = "ifnull($c,'')";
            }
            $arr[$i] = "concat(".implode(',', $tmp).") like '%".$arr[$i]."%'";
        }
        if($user == 1) {
            // 管理员
            $where = " 1=1";
        } else {
            $where = " (manager_id = ".$user. " or id = $user)";
        }
        $where .= "  and active=1 and ".join(' AND ', $arr);

        $total = $employee->getJoinCount($where);
        $data = array();
        if($total > 0) {
            $data = $employee->getJoinList($where, array(), array('id', 'number', 'cname', 'email'), array('cname'));
        }
        $resutl = array('total' => $total, 'rows' => $data);
        echo Zend_Json::encode($resutl);

        exit;
    }

    /**
     * 获取事件
     */
    public function geteventAction() {
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];
        // 自己还是下级
        $request = $this->getRequest();
        $employeeId = $request->getParam('employeeId');
        $data = array();
        $task = new User_Model_Task();
        if(!$employeeId || $user == $employeeId) {
            // 自己
            $where = "create_user = ".$user." or FIND_IN_SET($user, responsible_id) or FIND_IN_SET($user, follow_id)";
            $data = $task->getJoinList($where);
        } else {
            // 下级
            $where = " FIND_IN_SET($employeeId, responsible_id)";
            $data = $task->getJoinList($where);

        }
        for($i=0; $i < count($data); $i++) {
            $data[$i]['cid'] = 1;
            $data[$i]['follower'] = '0';
            // 关注着
            if($user != $data[$i]['create_user']
                && !in_array($user, explode(',', $data[$i]['responsible_id']))
                && in_array($user, explode(',', $data[$i]['follow_id']))) {
                $data[$i]['follower'] = '1';
                $data[$i]['cid'] = 3;
            }

            // 情形一：创建者 可以删除、修改、更新进度
            // 情形二：责任人 可以更新进度
            // 情形三：关注着 只能看
            // 情形四：下级 只能看
            if($user == $data[$i]['create_user']) {
                $data[$i]['relation'] = 1;
            } else if(in_array($user, explode(',', $data[$i]['responsible_id']))) {
                $data[$i]['relation'] = 2;
            } else if(in_array($user, explode(',', $data[$i]['follow_id']))) {
                $data[$i]['relation'] = 3;
            } else {
                $data[$i]['relation'] = 4;
            }
            $data[$i]['responsible'] = $data[$i]['responsible_id'];
            $data[$i]['follow'] = $data[$i]['follow_id'];
            $data[$i]['owner'] =
                $data[$i]['create_user'] == $user || in_array($user, explode(',', $data[$i]['responsible_id']))
                ? '1' : '0';
            if($data[$i]['parent'] == 0) {
                $data[$i]['parent'] = null;
            }
        }

        $result = array(
            'success' => true,
            'evts' => $data
        );
        echo Zend_Json::encode($result);
        exit;
    }

    /**
     * 责任人是自己或者关注者或者自己创建的任务
     */
    public function currentAction() {
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];
        $task = new User_Model_Task();
        $where = " create_user = $user or FIND_IN_SET($user, responsible_id) or FIND_IN_SET($user, follow_id)";
        $data = $task->getJoinList($where);
        for($i=0; $i < count($data); $i++) {
            $data[$i]['cid'] = 1;
            $data[$i]['follower'] = '0';
            // 关注着
            if($user != $data[$i]['create_user']
                && !in_array($user, explode(',', $data[$i]['responsible_id']))
                && in_array($user, explode(',', $data[$i]['follow_id']))) {
                $data[$i]['follower'] = '1';
                $data[$i]['cid'] = 3;
            }

            // 情形一：创建者 可以删除、修改、更新进度
            // 情形二：责任人 可以更新进度
            // 情形三：关注着 只能看
            // 情形四：下级 只能看
            if($user == $data[$i]['create_user']) {
                $data[$i]['relation'] = 1;
            } else if(in_array($user, explode(',', $data[$i]['responsible_id']))) {
                $data[$i]['relation'] = 2;
            } else if(in_array($user, explode(',', $data[$i]['follow_id']))) {
                $data[$i]['relation'] = 3;
            } else {
                $data[$i]['relation'] = 4;
            }
            $data[$i]['responsible'] = $data[$i]['responsible_id'];
            $data[$i]['follow'] = $data[$i]['follow_id'];
            $data[$i]['owner'] =
                $data[$i]['create_user'] == $user || in_array($user, explode(',', $data[$i]['responsible_id']))
                    ? '1' : '0';
            if($data[$i]['parent'] == 0) {
                $data[$i]['parent'] = null;
            }
        }

        $result = array(
            'success' => true,
            'evts' => $data
        );
        echo Zend_Json::encode($result);
        exit;
    }

    /**
     * 读取进度
     */
    public function processAction() {
        $user_session = new Zend_Session_Namespace('user');
        $process = new User_Model_Process();
        $task = new User_Model_Task();
        $request = $this->getRequest();
        $task_id = $request->getParam('task_id');
        $employee_id = $request->getParam('employee_id');
        $t = $task->getById($task_id);
        if($t && $t['type'] == '独立') {
            $where = " task_id = $task_id and employee_id = $employee_id";
        } else {
            $where = " task_id = $task_id";
        }

        $data = $process->getJoinList($where, array(),null,array('update_time desc'));
        for($i=0; $i < count($data); $i++) {
            $data[$i]['update_time'] = strtotime($data[$i]['update_time']);
        }

        echo Zend_Json::encode($data);
        exit;
    }

    /**
     * 保存任务
     */
    public function saveAction() {
        // 返回值数组
        $result = array(
            'success' => true,
            'result' => true,
            'info' => '保存成功'
        );
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];
        $now = date('Y-m-d H:i:s');

        $task = new User_Model_Task();
        // Content-Type:application/json 不能直接到request里面读取
        $request_body = file_get_contents('php://input');
        $json = json_decode($request_body);
        if(isset($json->id) && $json->id > 0) {
            $where = "id = ".$json->id;
            $data = array(
                'parent' => $json->Parent,
                'start' => $json->StartDate,
                'end' => $json->EndDate,
                'title' => $json->Title,
                'notes' => $json->Notes,
                'content' => $json->Notes,
                'responsible_id' => $json->Responsible_id,
                'follow_id' => $json->Follow_id,
                'priority' => $json->Priority ? $json->Priority : '紧急',
                'important' => $json->Important ? $json->Important : '重要',
                'type' => $json->Type ? $json->Type : '独立',
//                'step' => $json->Step,
                'update_user' => $user,
                'update_time' => $now
            );

            try {
                $task->update($data, $where);
                $processData = array();
                if(isset($json->Process) && $json->Process) {
                    $pro = json_decode($json->Process);
                    $process = new User_Model_Process();
                    if($pro && $pro != '[]') {
                        foreach ($pro as $val) {
                            $val = (array)$val;
                            $processData = array(
                                'task_id' => $json->id,
                                'employee_id' => $user,
                                'update_time' => $val['update_time'],
                                'rate' => $val['rate'],
                                'remark' => $val['remark'],
                                'status' => $val['status']
                            );
                            $process->insert($processData);
                        }
                    }
                }
                if(count($processData) > 0) {
                    $state = $processData['status'];
                    $uData['state'] = $state;
                    if($state == '取消' || $state == '完成') {
                        $uData = array(
                            'end' => $now
                        );
                    }
                    $task->update($uData, $where);
                }
                $this->send($json->id, 'update');
            } catch(Exception $e) {
                $result['result'] = false;
                $result['info'] = $e->getMessage();

                echo Zend_Json::encode($result);

                exit;
            }
        } else {
            $data = array(
                'parent' => $json->Parent,
                'start' => $json->StartDate,
                'end' => $json->EndDate,
                'title' => $json->Title,
                'notes' => $json->Notes,
                'content' => $json->Notes,
                'responsible_id' => $json->Responsible_id,
                'follow_id' => $json->Follow_id,
                'priority' => $json->Priority ? $json->Priority : '紧急',
                'important' => $json->Important ? $json->Important : '重要',
                'type' => $json->Type ? $json->Type : '独立',
//                'step' => $json->Step,
                'create_user' => $user,
                'update_user' => $user,
                'create_time' => $now,
                'update_time' => $now
            );
            try {
                $id = $task->insert($data);
                $result['info'] = $id;

                $processData = array();
                if(isset($json->Process) && $json->Process) {
                    $pro = $json->Process;
                    $process = new User_Model_Process();
                    if($pro && $pro != '[]') {
                        foreach ($pro as $val) {
                            $val = (array)$val;
                            $processData = array(
                                'task_id' => $id,
                                'employee_id' => $user,
                                'update_time' => $val['update_time'],
                                'rate' => $val['rate'],
                                'remark' => $val['remark'],
                                'status' => $val['status']
                            );
                            $process->insert($processData);
                        }
                    }
                }
                if(count($processData) > 0) {
                    $state = $processData['status'];
                } else {
                    $state = '发起';
                }
                $task->update(array('state'=>$state), "id=".$id);
                $this->send($id, 'add');
            } catch(Exception $e) {
                $result['result'] = false;
                $result['info'] = $e->getMessage();

                echo Zend_Json::encode($result);

                exit;
            }
        }

        echo Zend_Json::encode($result);
        exit;
    }

    public function removeAction() {
        // 返回值数组
        $result = array(
            'success' => true,
            'result' => true,
            'info' => '删除成功'
        );
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];
        $now = date('Y-m-d H:i:s');

        $task = new User_Model_Task();
        // Content-Type:application/json 不能直接到request里面读取
        $request_body = file_get_contents('php://input');
        $json = json_decode($request_body);

        try {
            if($json->EventId) {
                $this->send($json->EventId, 'delete');
                $task->delete("id=".$json->EventId);
            }
        } catch(Exception $e) {
            $result['result'] = false;
            $result['info'] = $e->getMessage();
            echo Zend_Json::encode($result);
            exit;
        }
        echo Zend_Json::encode($result);
        exit;
    }

    private function send($id, $updFlg) {
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];
        $user_name = $user_session->user_info['user_name'];
        $now = date('Y-m-d H:i:s');

        $employee = new Hra_Model_Employee();
        $mail = new Application_Model_Log_Mail();
        $task = new User_Model_Task();
        $process = new User_Model_Process();

        $record = $task->getJoinList("id=".$id);
        if(!$record) {
            return;
        }
        $record = $record[0];
        if($record['type'] == '独立') {
            $where = " task_id = $id and employee_id = $user";
        } else {
            $where = " task_id = $id";
        }
        $join = array(
            'type' => INNERJOIN,
            'table' => $employee,
            'condition' => $employee->getName() . '.id = ' . $process->getName() . '.employee_id',
            'cols' => array('cname')
        );
        $pro = $process->getJoinList($where, $join);

        $to_id = $record['responsible_id'];
        $cc_id = $record['follow_id'];
        // 上级
        if($to_id == $user) {
            $up = $employee->getAdapter()->query("select manager_id from oa_employee where id = $user")->fetchObject();
            if($up && $up->manager_id && $up->manager_id != $user) {
                if($cc_id) {
                    $cc_id .= ','.$up->manager_id;
                } else {
                    $cc_id = $up->manager_id;
                }
            }
        }
        $to = $employee->getAdapter()->query("select group_concat(email) as mail_to from oa_employee where id in ( " . $to_id . ")")->fetchObject();
        $ccmail = null;
        if($cc_id) {
            $cc = $employee->getAdapter()->query("select group_concat(email) as mail_to from oa_employee where id in ( " . $cc_id . ")")->fetchObject();
            $ccmail = $cc->mail_to;
        }
        if($updFlg == 'add') {
            $title = "新任务提示";
            $content = $user_name."创建了新任务 ".$record['title'];
        } else if($updFlg == 'update') {
            $title = "任务更新提示";
            $content = $user_name."更新了任务 ".$record['title'];
        } else {
            $title = "任务删除提示";
            $content = $user_name."删除了任务 ".$record['title'];
        }
        $content .= "<p><b>任务名称：</b>" . $record['title'] . "</p>";
        $content .= "<p><b>当前状态：</b>" . $record['state'] . "</p>";
        $content .= "<p><b>任务时间：</b>" . $record['start'] . "到" . $record['end'] . "</p>";
        $content .= "<p><b>任务描述：</b>" . $record['notes'] . "</p>";
        $content .= "<p><b>重要程度：</b>" . $record['important'] . "</p>";
        $content .= "<p><b>优先级：</b>" . $record['priority'] . "</p>";
        $content .= "<p><b>协作模式：</b>" . $record['type'] . "</p>";
        $creater = $employee->getById($record['create_user']);
        $content .= "<p><b>创建人：</b>" . $creater['cname'] . "</p>";
        $content .= "<p><b>创建时间：</b>" . $record['create_time'] . "</p>";
        $content .= "<p><b>进度</b></p>";
        foreach($pro as $p) {
            $content .= "<p>&nbsp;&nbsp;";
            if($record['type'] == '协作') {
                $content .= $p['cname']."&nbsp;&nbsp;";
            }
            $content .= $p['update_time']."&nbsp;&nbsp;".$p['status']."&nbsp;&nbsp;".$p['rate']."%&nbsp;&nbsp;".$p['remark']."</p>";
        }
        $mailData = array(
            'type' => '任务',
            'subject' => $title,
            'to' => $to->mail_to,
            'cc' => $ccmail,
            'content' => $content,
            'send_time' => $now,
            'add_date' => $now
        );

        $mailId = $mail->insert($mailData);
        if ($mailId) {
            $mail->send($mailId);
        }
    }


}

