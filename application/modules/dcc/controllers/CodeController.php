<?php

/**
 * 2013-7-15 下午13:27:20
 * @author      mg.luo
 * @abstract    文件编码申请
 */
class Dcc_CodeController extends Zend_Controller_Action {

    public function indexAction() {

    }

    /**
     * @abstract    获取文件编码JSON数据
     * @return      null
     */
    public function getcodeAction() {
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];
        // 请求参数
        $request = $this->getRequest()->getParams();
        $limit = $request['limit'];
        $start = $request['start'];

        $where = "1=1";
        foreach ($request as $k => $v) {
            $col = str_replace('search_', '', $k);
            if ($col != $k) {
                // 查询条件
                if ($col == 'state') {
                    $where .= " and t1.active = " . $v;
                } else if ($col == 'active') {
                    continue;
                } else if($v != '') {
                    $where .= " and ifnull(t1." . $col . ",'') like '%" . $v . "%'";
                }
            }
        }
        if (isset($request['personal'])) {
            if($request['personal'] == 1) {
                $where .= " and (t1.create_user = $user or t1.update_user = $user)";
            } else if($request['personal'] == 3) {
                //审核
                $where .= " and t1.state='Reviewing' and t1.id in (select file_id from oa_review where type = 'code_apply' and finish_flg=0)";
            }
        }
        if(isset($request['search_active'])) {
            if($request['search_active'] == "0") {
                $where .= " and NOT EXISTS (select * from oa_doc_files t0 where t1.`code` = t0.code)";
            } else if($request['search_active'] == "1") {
                $where .= " and EXISTS (select * from oa_doc_files t0 where t1.`code` = t0.code)";
            }
        }
        $code = new Dcc_Model_Code();

        // 根据不用来源，返回不同结果
        if (isset($request['step'])) {
            $step = $request['step'];
            switch ($step) {
                case "apply":
                    // 新申请
                    // 条件：1、启用状态为1；2、文件列表中无有效文件；3、新文件列表中无有效文件
                    if (isset($request['code']) && $request['code']) {
                        $where .= " and t1.code != '" . $request['code'] . "' and t1.project_no = (select project_no from oa_doc_code where code = '" . $request['code'] . "') and t2.model_id = (select t4.model_id from oa_doc_code t3 INNER JOIN oa_doc_type t4 on t3.prefix = t4.id where t3.code = '" . $request['code'] . "') and t2.flow_id = (select t4.flow_id from oa_doc_code t3 INNER JOIN oa_doc_type t4 on t3.prefix = t4.id where t3.code = '" . $request['code'] . "')";
                    }
                    $where .= " and not exists (select 1 from oa_doc_files where code = t1.code and state != 'Delete' and state != 'Obsolete')";
                    $data = $code->getCodeForApp($where);
                    break;
                case "dev":
                    // 升版
                    // 条件：1、文件列表中有有效文件；2、无其他版本文件正在审核
                    if (isset($request['code']) && $request['code']) {
                        $where .= " and t1.code != '" . $request['code'] . "' and t1.project_no = (select project_no from oa_doc_code where code = '" . $request['code'] . "') and t2.model_id = (select t4.model_id from oa_doc_code t3 INNER JOIN oa_doc_type t4 on t3.prefix = t4.id where t3.code = '" . $request['code'] . "') and t2.flow_id = (select t4.flow_id from oa_doc_code t3 INNER JOIN oa_doc_type t4 on t3.prefix = t4.id where t3.code = '" . $request['code'] . "')";
                    }
                    $data = $code->getCodeForDev($where);
                    break;
                default:
                    $data = $code->getCode($where, $start, $limit);
                    break;
            }
        } else {
            $data = $code->getCode($where, $start, $limit);
            $totalCount = $code->getAdapter()->query("select t1.id from oa_doc_code t1 where ($where)")->rowCount();
        }

        for ($i = 0; $i < count($data); $i++) {
            $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
            $data[$i]['update_time'] = strtotime($data[$i]['update_time']);
            $data[$i]['active'] = $data[$i]['active'] == 1 ? true : false;
        }
        if (isset($totalCount)) {
            $resutl = array(
                "totalCount" => $code->getAdapter()->query("select t1.id from oa_doc_code t1 where ($where)")->rowCount(),
                "topics" => $data
            );
        } else {
            $resutl = $data;
        }

        echo Zend_Json::encode($resutl);

        exit;
    }

    /**
     * @abstract    添加、删除、修改文件编码
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

        $code = new Dcc_Model_Code();
        if (count($deleted) > 0) {
            foreach ($deleted as $val) {
                // 检查此文件编码是否正在使用 TODO

                try {
                    $code->delete("id = " . $val->id);
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
     * @abstract    添加文件编码
     * @return      null
     */
    public function saveAction() {
        // 返回值数组
        $result = array(
            'success' => true,
            'result' => true,
            'info' => '申请成功'
        );

        $request = $this->getRequest()->getParams();

        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];

        $val = (object) $request;

        $code = new Dcc_Model_Code();
        $type = new Dcc_Model_Type();
        $db = $code->getAdapter();
        if ($val->id) {
            // 编辑
            if(isset($val->code) && $val->code) {
	            $auto_code = $val->code;
	            // 检查是否文件号已经存在
	            if ($code->fetchAll("id != " . $val->id . " and code = '" . $auto_code . "'")->count() > 0) {
	                $result['result'] = false;
	                $result['info'] = "文件编码“" . $val->code . "”已经存在";

	                echo Zend_Json::encode($result);
	                exit;
	            }
            } else {
            	$auto_code = "";
            }
            $data = array(
                'code' => $auto_code,
                'active' => isset($val->active) ? 1 : 0,
                'project_no' => $val->project_no,
                'project_standard_no' => isset($val->project_standard_no) ? $val->project_standard_no : "",
                'description' => $val->description,
                'remark' => $val->remark,
                'update_time' => $now,
                'update_user' => $user
            );

            try {
                $code->update($data, "id = " . $val->id);
                $result['info'] = "修改成功";
            } catch (Exception $e) {
                $result['result'] = false;
                $result['info'] = $e->getMessage();

                echo Zend_Json::encode($result);

                exit;
            }
        } else {
            // 检查是自动生成编码还是手动
            $typedata = $db->query("select t2.automethod from oa_doc_type t1 inner join oa_doc_auto t2 on t1.autotype=t2.id where t1.state = 1 and t1.id = ".$val->prefix)->fetchObject();
            if ($typedata && $typedata->automethod != 'H' &&  $typedata->automethod != 'A' &&  $typedata->automethod != 'F') {
                $auto_code = $this->getCode($val->prefix, $val->project_no);
	            if (!$auto_code) {
	                $result['result'] = false;
	                $result['info'] = "文件编码获取失败";
	                echo Zend_Json::encode($result);
	                exit;
	            }
            } else if($typedata && $typedata->automethod == 'A' && (!isset($val->code) || !$val->code)) {
                $auto_code = "";
                // 需发邮件
                $mailId = '';
                $type = "文件编码申请";
                $subject = "文件号分配";
                // $to =   文件管理员
                $employee = new Hra_Model_Employee();
                $member = new Admin_Model_Member();
                $role = new Admin_Model_Role();
                $toArr = array();
                $roleArr = $role->getRoleIdByName('文件管理员');
                $roleData = array();
                if($roleArr['role_id']) {
                    $roleData = $member->getMember($roleArr['role_id']);
                }
                foreach($roleData as $r) {
                    if($r['user_id'] == 1) {
                        continue;
                    }
                    $toArr[] =  $r['email'];
                }
                $to = implode(',', $toArr);
                if($to) {
                    $emp = $employee->getInfoById($user);
                    $userName = '';
                    if(count($emp) > 0) {
                        $userName = $emp[0]['cname'];
                    }
                    $user_name = $user_session->user_info['user_name'];
                    $content = "<p>你有一个文件编码申请需要分配文件号</p>";
                    $content .= "<p><b>文件描述：</b>" . $val->description . "</p><p><b>备注：</b>" . $val->remark . "</p><p><b>申请人：</b>" . $user_name . "</p><p><b>申请时间：</b>" . $now . "</p><p>请登录系统查看详情！</p>";
                    $mailData = array(
                        'type' => $type,
                        'subject' => $subject,
                        'to' => $to,
                        'content' => $content,
                        'send_time' => $now,
                        'add_date' => $now
                    );
                    $mail = new Application_Model_Log_Mail();
                    try {
                        $mailId = $mail->insert($mailData);
                    } catch (Exception $e) {
                    }
                    if ($mailId) {
                        $mail->send($mailId);
                    }
                }
            } else if($typedata && $typedata->automethod == 'F'
                && (!isset($val->code) || !$val->code)) {
                // 流程
                // 获取当前文件类别对应的流程ID
                $row = $type->getList("id='$val->prefix'");
                $flow_id = '';
                if($row && count($row) > 0) {
                    $flow_id = $row[0]['apply_flow_id'];
                } else {

                    $result['info'] = "文件类型未设置审核流程";
                    echo Zend_Json::encode($result);
                    exit;
                }

                // 根据流程ID获取阶段
                $flow = new Admin_Model_Flow();
                $step = new Admin_Model_Step();
                $member = new Admin_Model_Member();
                $employee = new Hra_Model_Employee();
                $mail = new Application_Model_Log_Mail();
                $record = new Dcc_Model_Record();
                $review = new Dcc_Model_Review();

                $flowRow = $flow->getRow($flow_id);
                $step_ids = $flowRow['step_ids'];
                if($step_ids) {
                    $data = array(
                        'prefix' => $val->prefix,
                        'code' => '',
                        'state' => 'Reviewing',
                        'active' => isset($val->active) ? 1 : 0,
                        'project_no' => $val->project_no,
                        'project_standard_no' => isset($val->project_standard_no) ? $val->project_standard_no : "",
                        'description' => $val->description,
                        'remark' => $val->remark,
                        'create_time' => $now,
                        'create_user' => $user,
                        'update_time' => $now,
                        'update_user' => $user
                    );

                    try {
                        $id = $code->insert($data);
                        $inserted = true;
                        // 操作记录
                        $data = array(
                            'type'             => "code_apply",
                            'table_name'       => "oa_doc_code",
                            'table_id'         => $id,
                            'handle_user'      => $user,
                            'handle_time'      => $now,
                            'action'           => "申请",
                            'ip'               => $_SERVER['REMOTE_ADDR']
                        );
                        $record->insert($data);
                    } catch (Exception $e) {
                        $result['result'] = false;
                        $result['info'] = $e->getMessage();

                        echo Zend_Json::encode($result);

                        exit;
                    }

                    $stepRows = $step->getListByFlow($step_ids);
                    $state = "Reviewing";
                    // 把阶段信息插入review记录
                    $first = true;
                    foreach ($stepRows as $s) {
                        $plan_user = $s['user'];
                        if ($s['dept']) {
                            $tmpUser = array();
                            $plan_dept = $s['dept'];
                            foreach(explode(',', $plan_dept) as $role) {
                                $tmpRole = $member->getMemberWithNoManager($role);
                                foreach ($tmpRole as $m){
                                    $tmpUser[] = $m['user_id'];
                                }
                            }
                            if(count($tmpUser) == 0 && !$plan_user) {
                                $tmpUser = $member->getUserids("系统管理员");
                            }
                            if(count($tmpUser) > 0) {
                                $tmpUser = $employee->getAdapter()->query("select group_concat(employee_id) as users from oa_user where active = 1 and id in ( " . implode(',', $tmpUser) . ")")->fetchObject();
                                $users = $tmpUser->users;
                            }
                            if ($users) {
                                if ($plan_user)
                                    $plan_user .= ",";
                                $plan_user .= $users;
                            }
                        }
                        $repeatUser = explode(',', $plan_user);
                        $repeatUser = array_unique($repeatUser);
                        $plan_user = implode(',', $repeatUser);

                        $reviewData = array(
                            'type' => "code_apply",
                            'file_id' => $id,
                            'plan_user' => $plan_user,
                            'method' => $s['method'],
                            'return' => $s['return'],
                            'step_name' => $s['step_name'],
                            'step_ename' => $s['step_ename']
                        );
                        $review->insert($reviewData);

                        // 邮件任务
                        if ($first) {
                            // 需发邮件
                            $mailId = '';
                            $type = "文件编码申请";
                            $subject = "文件编码申请审核";
                            $user_name = $user_session->user_info['user_name'];

                            $to = $employee->getAdapter()->query("select group_concat(email) as mail_to from oa_employee where id in ( " . $plan_user . ")")->fetchObject();
                            $content = "<p>你有一个文件编码申请需要审核</p>";
                            $content .= "<p><b>文件描述：</b>" . $val->description . "</p><p><b>备注：</b>" . $val->remark . "</p><p><b>申请人：</b>" . $user_name . "</p><p><b>申请时间：</b>" . $now . "</p><p>请登录系统查看详情！</p>";
                            $mailData = array(
                                'type' => $type,
                                'subject' => $subject,
                                'to' => $to->mail_to,
                                'content' => $content,
                                'send_time' => $now,
                                'add_date' => $now
                            );

                            $mailId = $mail->insert($mailData);
                            if ($mailId) {
                                $mail->send($mailId);
                            }
                        }
                        $first = false;
                    }
                    $result['info'] = "申请成功，已通知相关人员审核";
                    echo Zend_Json::encode($result);
                    exit;
                }
            } else {
                $auto_code = $val->code;
                // 检查是否文件号已经存在
                if ($auto_code && $code->fetchAll("code = '" . $auto_code . "'")->count() > 0) {
                    $result['result'] = false;
                    $result['info'] = "文件编码“" . $val->code . "”已经存在";

                    echo Zend_Json::encode($result);

                    exit;
                }
            }
            $auto_code = strtoupper($auto_code);
            $data = array(
                'prefix' => $val->prefix,
                'code' => $auto_code,
                'state' => isset($state) ? 'Reviewing' : 'Active',
                'active' => isset($val->active) ? 1 : 0,
                'project_no' => $val->project_no,
                'project_standard_no' => isset($val->project_standard_no) ? $val->project_standard_no : "",
                'description' => $val->description,
                'remark' => $val->remark,
                'create_time' => $now,
                'create_user' => $user,
                'update_time' => $now,
                'update_user' => $user
            );

            try {
                $code->insert($data);
                if($auto_code) {
                	$result['info'] = "申请成功，文件编码：$auto_code";
                } else {
                	$result['info'] = "申请成功，请通知管理员分配文件编码";
                }
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
     * @abstract    根据文件简号和流水号生成最新文件编码
     * @return      null
     */
    private function getCode($type_id, $proj_no) {
        $type = new Dcc_Model_Type();

        // 获取流水号长度和自动编码方式
        $data = $type->getLengthAndMethod($type_id);
        $type_code = $data['code'];
        $length = $data['length'];
        $automethod = $data['automethod'];

        $code = new Dcc_Model_Code();

        if (strripos($automethod, 'S') !== false) {
            // 获取产品系列代码
            $seriesData = $code->getAdapter()->query("select code from oa_product_catalog_series where id = (select series_id from oa_product_catalog where id = $proj_no)")->fetchObject();
            $ymd = $seriesData->code;
            if (!$ymd) {
                $result['result'] = false;
                $result['info'] = "产品型号对应的产品代码不存在！";

                echo Zend_Json::encode($result);

                exit;
            }
        } else {
            // 查询符合当前长度和编码方式的流水号
            $ymd = str_replace('DD', date('d'), str_replace('MM', date('m'), str_replace('YYYY', date('y'), str_replace('9', '', $automethod))));
        }
        if ($length) {
            $prefix = $type_code . $ymd;
            $like = "";
            for ($i = 0; $i < $length; $i++) {
                $like .= "_";
            }
            $mData = $code->getAdapter()->query("select max(code) as maxcode from oa_doc_code where code like '$prefix$like'")->fetchObject();
            $num = "";
            if ($mData && $mData->maxcode) {
                $max = $mData->maxcode;
                $num = str_replace($prefix, '', $max);
                if (strlen($num) != $length) {
                    $num = "";
                    for ($i = 0; $i < $length; $i++) {
                        $num .= "0";
                    }
                }
            } else {
                for ($i = 0; $i < $length; $i++) {
                    $num .= "0";
                }
            }
            // +1
            $prefix .= substr((("1" . $num) + 1), 1);
            return $prefix;
        } else {
            return "";
        }
//        // 真实流水号长度
////        $num_length = $length - strlen($ymd);
//        $num_length = $length;
//        // 查询条件
//        $prefix = $type_code . $ymd;
//        $like = $prefix;
//        $up = 1;
//        for ($i = 0; $i < $num_length; $i++) {
//            if ($num_length != $length) {
//                $like .= '_';
//            }
//            $up *= 10;
//            if ($i + 1 == $num_length) {
//                $prefix .= '1';
//            } else {
//                $prefix .= '0';
//            }
//        }
//        if ($num_length == $length) {
//            $like = $prefix . '%';
//        }
//        $c = $code->getApply($like);
//        if ($c) {
//            $current_code = $c['code'];
//            $prefix_code = substr($current_code, 0, strlen($current_code) - $num_length);
//            $real_code = substr($current_code, strlen($prefix_code), $num_length);
//            $tmp = substr($up + $real_code + 1, 1);
//            $next_code = $prefix_code . $tmp;
//            $return_code = $next_code;
//        } else {
//            $return_code = $prefix;
//        }
//        return $return_code;
    }

    /**
     * 取得项目号
     */
    public function getprojectAction() {
    	$request = $this->getRequest()->getParams();
    	$where = "1=1";
    	if(isset($request['q']) && $request['q']) {
    		$q = $request['q'];
    		$where = "t1.model_internal like '%$q%'";
    	}
        $catalog = new Product_Model_Catalog();
        $data = $catalog->getAdapter()->query("select t1.id, concat(t1.model_internal,'[', t2.name, ']') as name from oa_product_catalog t1 inner join oa_product_catalog_series t2 on t1.series_id = t2.id where $where and t1.active = 1 and t2.active=1 and t1.model_internal != '' order by t1.model_internal")->fetchAll();

        echo Zend_Json::encode($data);

        exit;
    }

    /**
     * 取得标准产品型号
     */
    public function getprojectstandardAction() {
    	$request = $this->getRequest()->getParams();
    	$where = "1=1";
    	if(isset($request['q']) && $request['q']) {
    		$q = $request['q'];
    		$where = "t1.model_standard like '%$q%'";
    	}
        $catalog = new Product_Model_Catalog();
        $data = $catalog->getAdapter()->query("select t1.id, concat(t1.model_standard,'[', t2.name, ']') as name from oa_product_catalog t1 inner join oa_product_catalog_series t2 on t1.series_id = t2.id where $where and t1.active = 1 and t2.active=1 and t1.model_standard != '' order by t1.model_standard")->fetchAll();

        echo Zend_Json::encode($data);

        exit;
    }

    /**
     * 保存文件编码
     */
    public function savecodeAction() {
        $request = $this->getRequest();
        $id = $request->getParam('id');
        $code = $request->getParam('code');
        if($id && $code) {
            $code = strtoupper($code);
            // 检查文件编码是否已经存在
            $codeModel = new Dcc_Model_Code();
            $data = $codeModel->fetchAll("code = '$code'")->toArray();
            if($data && count($data) > 0) {
                $result['success'] = false;
                $result['info'] = "文件编码已存在！";
                echo Zend_Json::encode($result);
                exit;
            }
            $now = date('Y-m-d H:i:s');
            $user_session = new Zend_Session_Namespace('user');
            $user = $user_session->user_info['employee_id'];

            $data = array(
                'code' => $code,
                'state' => 'Active',
                'update_user' => $user,
                'update_time' => $now
            );
            $where = "id = ".$id;
            $codeData = $codeModel->getById($id);
            try {
                $codeModel->update($data, $where);
                $record = new Dcc_Model_Record();
                $review = new Dcc_Model_Review();
                // 处理记录
                $recordData = array(
                    "type" => "code_apply",
                    "table_name" => "oa_doc_code",
                    "table_id" => $id,
                    "handle_user" => $user,
                    "handle_time" => $now,
                    "action" => "审批",
                    "result" => '批准',
                    "ip" => $_SERVER['REMOTE_ADDR']
                );
                // 增加记录
                $record->insert($recordData);
                // 审核情况
                $reviewData = array(
                    "actual_user" => $user,
                    "finish_time" => $now,
                    "finish_flg" => 1
                );
                $review->update($reviewData, "type='code_apply' and file_id = ".$id);

                // 发邮件
                $type = "文件编码申请";
                $subject = "文件编码申请审核";
                $user_name = $user_session->user_info['user_name'];

                $to = $codeModel->getAdapter()->query("select group_concat(email) as mail_to from oa_employee where id =(select create_user from oa_doc_code where id = $id)")->fetchObject();
                $content = "<p>你有一个文件编码申请已分配，文件号：$code</p>";
                $content .= "<p><b>文件描述：</b>" . $codeData['description'] . "</p><p><b>备注：</b>" . $codeData['remark'] . "</p><p><b>申请时间：</b>" . $codeData['create_time'] . "</p><p>请登录系统查看详情！</p>";
                $mailData = array(
                    'type' => $type,
                    'subject' => $subject,
                    'to' => $to->mail_to,
                    'content' => $content,
                    'send_time' => $now,
                    'add_date' => $now
                );

                $mail = new Application_Model_Log_Mail();
                $mailId = $mail->insert($mailData);
                if ($mailId) {
                    $mail->send($mailId);
                }

                $result['success'] = true;
                $result['info'] = '分配成功';
                echo Zend_Json::encode($result);
                exit;
            } catch(Exception $e) {
                $result['success'] = false;
                $result['info'] = $e->getMessage();
                echo Zend_Json::encode($result);
                exit;
            }
        }
    }

}