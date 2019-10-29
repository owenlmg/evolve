<?php

/**
 * 2013-7-29
 * @author      mg.luo
 * @abstract    文件管理
 */
class Dcc_MineController extends Zend_Controller_Action {

    public function indexAction() {

    }

    /**
     * @abstract    获取文件JSON数据
     * @return      null
     */
    public function getfilesAction() {
        // 请求参数
        $request = $this->getRequest()->getParams();
        $limit = $request['limit'];
        $start = $request['start'];
        $whereSearch = "1=1";
        foreach ($request as $k => $v) {
            if ($v) {
                if ($k == 'search_tag') {
            	    $cols = array("t1.project_info", "t1.code", "t1.name", "t1.description", "t1.remark", "t2.cname");
            	    $arr=preg_split('/\s+/',trim($v));
            	    for ($i=0;$i<count($arr);$i++) {
            	        $tmp = array();
            	        foreach($cols as $c) {
            	            $tmp[] = "ifnull($c,'')";
            	        }
            	        $arr[$i] = "concat(".implode(',', $tmp).") like '%".$arr[$i]."%'";
            	    }
            	    $whereSearch .= " and ".join(' AND ', $arr);
            	    
//                     $whereSearch .= " and (ifnull(t1.project_info,'') like '%$v%' or ifnull(t1.code,'') like '%$v%' or ifnull(t1.name,'') like '%$v%' or ifnull(t1.description,'') like '%$v%' or ifnull(t1.remark,'') like '%$v%' or ifnull(t2.cname,'') like '%$v%')";
                } else if ("search_category" == $k && $v) {
                    $whereSearch .= " and t.category = '$v'";
                } else {
                    $col = str_replace('search_', '', $k);
                    if ($col != $k) {
                        // 查询条件
                        $whereSearch .= " and ifnull(t1." . $col . ",'') like '%" . $v . "%'";
                    }
                }
            }
        }

        $files = new Dcc_Model_Files();
        $record = new Dcc_Model_Record();
        $flow = new Admin_Model_Flow();
        $step = new Admin_Model_Step();
        $share = new Dcc_Model_Share();
        $type = new Dcc_Model_Type();
        $review = new Dcc_Model_Review();
        $employee = new Hra_Model_Employee();
        $dept = new Hra_Model_Dept();
        $codemaster = new Admin_Model_Codemaster();

        // 我的ID
        $user_session = new Zend_Session_Namespace('user');
        $myId = $user_session->user_info['employee_id'];
        $myDept = $user_session->user_info['dept_id'];

        // 类型
        $myType = "";
        if (isset($request['mytype'])) {
            $myType = $request['mytype'];
        }

        $file_ids = array();
        if ($myType == 4 || $myType == '') {
            // 4、共享给我的
            // 查询共享给我的文件ID
            $share = new Dcc_Model_Share();
            $nowDate = date('Y-m-d');
            $uploadRows = $share->fetchAll("type = 'upload' and share_time_begin <= '$nowDate' and share_time_end >= '$nowDate'  and (FIND_IN_SET($myId, share_user) or FIND_IN_SET($myDept, share_dept))")->toArray();
            for ($i = 0; $i < count($uploadRows); $i++) {
                $upload_id = $uploadRows[$i]['shared_id'];
                // 查询使用了此上传文件的文件
                $filesRows = $files->fetchAll("state != 'Deleted' and del_flg = 0 and FIND_IN_SET($upload_id, file_ids)")->toArray();
                for ($j = 0; $j < count($filesRows); $j++) {
                    $file_id = $filesRows[$j]['id'];
                    if (!in_array($file_id, $file_ids)) {
                        $file_ids[] = $file_id;
                    }
                }
            }
        }
//         $datas = $files->getMy($myType, $whereSearch, $myId, $file_ids, $start, $limit);
        $totalData = $files->getMyCount($myType, $whereSearch, $myId, $file_ids);
        $totalCount = count($totalData);
        $datas = array_slice($totalData, $start, $limit);

        $data = array();
        foreach ($datas as $tmp) {
            $mytype = 2;
            if ($tmp['create_user'] == $myId) {
                $mytype = 1;
            } else if(in_array($tmp['id'], $file_ids)) {
            	$mytype = 4;
            }

            $tmp['create_time'] = strtotime($tmp['create_time']);
            $tmp['update_time'] = strtotime($tmp['update_time']);
            $tmp['archive_time'] = strtotime($tmp['archive_time']);
            $tmp['codever'] = $tmp['code'] . ' V' . $tmp['ver'];
            $tmp['send_require'] = $tmp['send_require'] == 1 ? true : false;
            $tmp['description'] = $tmp['code_description'];

            // 增加审核状态
            $reviewState = "";
            $step_name = "";
            if ($tmp['state'] == 'Active') {
                $reviewState = "已发布";
            } else if ($tmp['state'] == 'Reviewing') {
                // 查询当前审核状态
                // 查询所有审核阶段
                $reviewRows = $review->getList("file_id = " . $tmp['id'], "files");
                if (count($reviewRows) > 0) {
                    $first = true;
                    foreach ($reviewRows as $row) {
                        if ($row['finish_flg'] == 1) {
                            if ($step_name)
                                $step_name .= "->";
                            $step_name .= $row['step_name'];
                        } else {
                            if ($step_name)
                                $step_name .= "->";

                            // 第一条未审核记录就是当前待审核记录
                            if ($first) {
                    	        $tmp['review_id'] = $row['id'];
                                $first = false;

                                $step_name .= "<b>" . $row['step_name'] . "</b>";

                                $reviewRow = $row;
                                $actual_user = explode(',', $reviewRow['actual_user']);
                                $planUser = $reviewRow['plan_user'];
                                $method = $reviewRow['method'];
                                $plan_dept = $reviewRow['plan_dept'];
                                $depts = array();
                                /* if ($method == 1 && $plan_dept) {
                                  // 获取部门所有人员
                                  $tmpUser = $employee->getAdapter()->query("select group_concat(id) as ids from oa_employee where dept_id in ( " . $plan_dept . ")")->fetchObject();
                                  if ($tmpUser->ids) {
                                  if ($planUser)
                                  $planUser .= ",";
                                  $planUser .= $tmpUser->ids;
                                  }
                                  } else if ($method == 2 && $plan_dept) {
                                  $tmpDept = $step->getAdapter()->query("select group_concat(name) as name from oa_employee_dept where id in ( " . $plan_dept . ")")->fetchObject();
                                  $step_dept_name = $tmpDept->name;
                                  if ($step_dept_name) {
                                  $depts = explode(',', $step_dept_name);
                                  }
                                  } */
                                $plan_user = explode(',', $planUser);
                                $diff = array_diff($plan_user, $actual_user);

                                foreach ($diff as $u) {
                                    if (!$u)
                                        continue;
                                    if ($u == $myId) {
                                        $mytype = 3;
                                    }
                                    $e = $employee->fetchRow("id = $u");
                                    if ($reviewState)
                                        $reviewState .= ", ";
                                    $reviewState .= $e['cname'] . "：未审核";
                                }
                                foreach ($depts as $d) {
                                    if ($reviewState)
                                        $reviewState .= ", ";
                                    $reviewState .= $d . "：未审核";
                                }
                                foreach ($actual_user as $u) {
                                    if (!$u)
                                        continue;
                                    $e = $employee->fetchRow("id = $u");
                                    if ($reviewState)
                                        $reviewState .= ", ";
                                    $reviewState .= $e['cname'] . "：已审核";
                                }
                            } else {
                                $step_name .= $row['step_name'];
                            }
                        }
                    }
                }
            } else if ($tmp['state'] == 'Obsolete') {
                $reviewState = "已作废";
            } else if ($tmp['state'] == 'Return') {
                $reviewState = "退回";
            } else {
                $reviewState = $tmp['state'];
            }
            $tmp['step_name'] = $step_name;
            $tmp['review_state'] = $reviewState;
            if ($tmp['reason_type']) {
                $masterData = $codemaster->fetchRow("type = 5 and code = '" . $tmp['reason_type'] . "'");
                $reason_type_name = $masterData->text;
                $tmp['reason_type_name'] = $reason_type_name;
            }
            // int转换为字符串
            $tmp['mytype'] = " ".$mytype;

            $data[] = $tmp;

        }
        // 排序
        $dataT3 = array();
        $dataT1 = array();
        $dataT2 = array();
        $dataT4 = array();
        for ($i = 0; $i < count($data); $i++) {
            if ($data[$i]['mytype'] == 3) {
                $dataT3[] = $data[$i];
            } else if ($data[$i]['mytype'] == 1) {
                $dataT1[] = $data[$i];
            } else if ($data[$i]['mytype'] == 2) {
                $dataT2[] = $data[$i];
            } else {
                $dataT4[] = $data[$i];
            }
        }
        $data = array_merge($dataT3, $dataT1, $dataT2, $dataT4);
        $resutl = array(
            "totalCount" => $totalCount,
            "topics" => $data
        );

        echo Zend_Json::encode($resutl);

        exit;
    }

    /**
     * @abstract    删除文件
     * @return      null
     */
    public function removeAction() {
        // 返回值数组
        $result = array(
            'success' => true,
            'result' => true,
            'info' => '删除成功'
        );

        $request = $this->getRequest()->getParams();

        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];

        $json = json_decode($request['json']);

        $deleted = $json->deleted;

        $files = new Dcc_Model_Files();
        $record = new Dcc_Model_Record();
        $review = new Dcc_Model_Review();

        if (count($deleted) > 0) {
            foreach ($deleted as $val) {
                $id = $val->id;
                // 操作记录
                $data = array(
                    'type' => "files",
                    'table_name' => "oa_doc_files",
                    'table_id' => $id,
                    'handle_user' => $user,
                    'handle_time' => $now,
                    'action' => "删除",
                    'ip' => $_SERVER['REMOTE_ADDR']
                );
                try {
                    $record->insert($data);
                    // 删除review记录
                    $review->delete("type = 'files' and file_id = $id");
                    // 更新文件状态
                    $files->delete("id = $id");
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
     * @abstract    申请新文件
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

        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];
        $user_name = $user_session->user_info['user_name'];

        $val = (object) $request;

        $json = json_decode($request['json']);
        $code_file_code_id = $json->code_id;
        $code_file_code = $json->code;
        $code_file_file_id = $json->file_id;
        $code_file_file = $json->file;
        $code_file_project_no = isset($json->code_file_project_no) ? $json->code_file_project_no : '';
        $code_file_desc = isset($json->code_file_desc) ? $json->code_file_desc : '';

        $files = new Dcc_Model_Files();
        $upload = new Dcc_Model_Upload();
        $record = new Dcc_Model_Record();
        $review = new Dcc_Model_Review();
        $employee = new Hra_Model_Employee();
        $mail = new Application_Model_Log_Mail();
        $member = new Admin_Model_Member();

        // 检查文件号是否已经使用
        foreach ($code_file_code as $code) {
            if ($files->fetchAll("FIND_IN_SET('$code', code) and del_flg = 0 and (state = 'Reviewing' or state = 'Active' or state = 'Obsolete') ")->count() > 0) {
                $result['result'] = false;
                $result['info'] = "文件 $code 已存在！";

                echo Zend_Json::encode($result);

                exit;
            }
            $vers[] = "1.0";
        }

        $code = $code_file_code[0];
        $stepRows = array();
        if (count($code) > 0 && $code) {
            $codeModel = new Dcc_Model_Code();
            // 获取当前文件类别对应的流程ID
            $row = $codeModel->getTypeInfo("t1.code = '$code'");
            $flow_id = $row['flow_id'];

            if ($flow_id) {
                // 根据流程ID获取阶段
                $flow = new Admin_Model_Flow();
                $step = new Admin_Model_Step();

                $flowRow = $flow->getRow($flow_id);
                $step_ids = $flowRow['step_ids'];
                if ($step_ids) {
                    $stepRows = $step->getListByFlow($step_ids);
                    $state = $stepRows[0]['step_name'];
                }
            }
        }

        // 新增还是编辑
        if ($val->id) {
            $result['info'] = '修改成功';
            $data = array(
                'state' => isset($state) ? 'Reviewing' : 'Active',
                'code' => implode(',', $code_file_code),
                'name' => implode(',', $code_file_file),
                'file_ids' => implode(',', $code_file_file_id),
                'ver' => implode(',', $vers),
                'send_require' => isset($val->send_require) ? 1 : 0,
                'remark' => $val->remark,
                'tag' => $val->tag,
                'update_time' => $now,
                'update_user' => $user
            );
            $id = $val->id;
            $where = "id = " . $id;
            try {
                if ($id) {
                    $files->update($data, $where);
                    $attrval = new Admin_Model_Formval();
                    // 自定义字段
                    foreach ($request as $field => $value) {
                        if (stripos($field, "intelligenceField") !== false) {
                            $attrId = str_replace("intelligenceField", "", $field);
                            $menu = 'oa_doc_files_' . $id;

                            $formval = array(
                                'attrid' => $attrId,
                                'value' => $value,
                                'menu' => $menu
                            );
                            $where = "attrid = " . $attrId . " and menu = '" . $menu . "'";
                            if ($attrval->fetchAll($where)->count() > 0) {
                                // 更新
                                $attrval->update($formval, $where);
                            } else {
                                $attrval->insert($formval);
                            }
                        }
                    }

                    // 操作记录
                    $data = array(
                        'type' => "files",
                        'table_name' => "oa_doc_files",
                        'table_id' => $id,
                        'handle_user' => $user,
                        'handle_time' => $now,
                        'action' => "编辑",
                        'ip' => $_SERVER['REMOTE_ADDR']
                    );
                    $record->insert($data);

                    // 审核流程
                    // 删除已存在的审核记录
                    $review->delete("type = 'files' and file_id = " . $id);
                    // 把阶段信息插入review记录

                    $first = true;
                    foreach ($stepRows as $s) {
                        $plan_user = $s['user'];
                        if ($s['dept']) {
                            $plan_dept = $s['dept'];
                            // 根据角色id和项目号获取用户成员列表
                            $roleid = $s['dept'];
                            $codeTmp = $employee->getAdapter()->query("select project_no from oa_doc_code where code = '$code'")->fetchObject();
                            $projectno = $codeTmp->project_no;
                            // 如果不存在项目号，则直接取角色中的用户 否则根据角色id和项目号获取roleset id
                            $tmpUser = array();
                            $tmpBool = true;
                            if ($projectno) {
                                // 根据角色id和项目号获取roleset id
                                $rolesetTmp = $employee->getAdapter()->query("select group_concat(id) as id from oa_product_catalog_roleset where active=1 and catalog_id='$projectno' and role_id in ( " . $roleid . ")")->fetchObject();
                                $rolesetid = $rolesetTmp->id;
                                if ($rolesetid) {
                                    $tmpBool = false;
                                    $userTmp = $employee->getAdapter()->query("select group_concat(user_id) as ids from oa_product_catalog_roleset_member where roleset_id in ( " . $rolesetid . ")")->fetchObject();
                                    $tmpUser = explode(',', $userTmp->ids);
                                    // 如果没有取到用户，还是使用默认用户
                                    if (count($tmpUser) == 0) {
                                        $tmpBool = true;
                                    }
                                }
                            }
                            if ($tmpBool) {
                                foreach (explode(',', $plan_dept) as $role) {
                                    $tmpRole = $member->getMemberWithNoManager($role);
                                    foreach ($tmpRole as $m) {
                                        $tmpUser[] = $m['user_id'];
                                    }
                                }
                            }
                            if (count($tmpUser) == 0 && !$plan_user) {
                                $tmpUser = $member->getUserids("系统管理员");
                            }
                            // user id 转换成employee id
                            if (count($tmpUser) > 0) {
                                $tmpUser = $employee->getAdapter()->query("select group_concat(employee_id) as users from oa_user where active = 1 and id in ( " . implode(',', $tmpUser) . ")")->fetchObject();
                                $users = $tmpUser->users;
                            }
                            // 获取角色所有人员
                            if (isset($users) && $users) {
                                if ($plan_user)
                                    $plan_user .= ",";
                                $plan_user .= $users;
                            }
                            $repeatUser = explode(',', $plan_user);
                            $plan_user = array();
                            foreach($repeatUser as $u) {
                                if($u && !in_array($u, $plan_user)) {
                                    $plan_user[] = $u;
                                }
                            }
                            $plan_user = implode(',', $plan_user);
                        }
                        $reviewData = array(
                            'type' => "files",
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
                            $to = $employee->getAdapter()->query("select group_concat(email) as mail_to from oa_employee where id in ( " . $plan_user . ")")->fetchObject();
                            $content = "你有新文件需要审核，<p><b>文件号：</b>" . implode(',', $code_file_code) . "</p><p><b>版本：</b>" . implode(',', $vers) . "</p><p><b>文件描述：</b>" . $val->description . "</p><p><b>备注：</b>" . $val->remark . "</p><p><b>申请人：</b>" . $user_name . "</p><p><b>申请时间：</b>" . $now . "</p><p>请登录系统查看详情！</p>";
                            $mailData = array(
                                'type' => '新文件',
                                'subject' => '新文件评审',
                                'to' => $to->mail_to,
                                'cc' => '',
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
                }
            } catch (Exception $e) {
                $result['result'] = false;
                $result['info'] = $e->getMessage();

                echo Zend_Json::encode($result);

                exit;
            }

            echo Zend_Json::encode($result);

            exit;
        } else {
            $data = array(
                'state' => isset($state) ? 'Reviewing' : 'Active',
                'code' => implode(',', $code_file_code),
                'name' => implode(',', $code_file_file),
                'file_ids' => implode(',', $code_file_file_id),
                'ver' => implode(',', $vers),
                'send_require' => isset($val->send_require) ? 1 : 0,
                'remark' => $val->remark,
                'tag' => $val->tag,
                'create_time' => $now,
                'create_user' => $user,
                'update_time' => $now,
                'update_user' => $user,
                'archive_time'=> isset($state) ? '' : $now
            );

            try {
                $id = $files->insert($data);
                if ($id) {
                    // 自定义字段
                    $attrval = new Admin_Model_Formval();
                    foreach ($request as $field => $value) {
                        if (stripos($field, "intelligenceField") !== false && $value) {
                            $attrId = str_replace("intelligenceField", "", $field);
                            $menu = 'oa_doc_files_' . $id;

                            $formval = array(
                                'attrid' => $attrId,
                                'value' => $value,
                                'menu' => $menu
                            );
                            $attrval->insert($formval);
                        }
                    }

                    // 操作记录
                    $data = array(
                        'type' => "files",
                        'table_name' => "oa_doc_files",
                        'table_id' => $id,
                        'handle_user' => $user,
                        'handle_time' => $now,
                        'action' => "申请",
                        'ip' => $_SERVER['REMOTE_ADDR']
                    );
                    $record->insert($data);

                    // 审核流程
                    // 把阶段信息插入review记录
                    $first = true;
                    foreach ($stepRows as $s) {
                        $plan_user = $s['user'];
                        if ($s['dept']) {
                            $plan_dept = $s['dept'];
                            // 根据角色id和项目号获取用户成员列表
                            $roleid = $s['dept'];
                            $codeTmp = $employee->getAdapter()->query("select project_no from oa_doc_code where code = '$code'")->fetchObject();
                            $projectno = $codeTmp->project_no;
                            // 如果不存在项目号，则直接取角色中的用户 否则根据角色id和项目号获取roleset id
                            $tmpUser = array();
                            $tmpBool = true;
                            if ($projectno) {
                                // 根据角色id和项目号获取roleset id
                                $rolesetTmp = $employee->getAdapter()->query("select group_concat(id) as id from oa_product_catalog_roleset where active=1 and catalog_id='$projectno' and role_id in ( " . $roleid . ")")->fetchObject();
                                $rolesetid = $rolesetTmp->id;
                                if ($rolesetid) {
                                    $tmpBool = false;
                                    $userTmp = $employee->getAdapter()->query("select group_concat(user_id) as ids from oa_product_catalog_roleset_member where roleset_id in ( " . $rolesetid . ")")->fetchObject();
                                    $tmpUser = explode(',', $userTmp->ids);
                                    // 如果没有取到用户，还是使用默认用户
                                    if (count($tmpUser) == 0) {
                                        $tmpBool = true;
                                    }
                                }
                            }
                            if ($tmpBool) {
                                foreach (explode(',', $plan_dept) as $role) {
                                    $tmpRole = $member->getMemberWithNoManager($role);
                                    foreach ($tmpRole as $m) {
                                        $tmpUser[] = $m['user_id'];
                                    }
                                }
                            }
                            if (count($tmpUser) == 0 && !$plan_user) {
                                $tmpUser = $member->getUserids("系统管理员");
                            }
                            // user id 转换成employee id
                            if (count($tmpUser) > 0) {
                                $tmpUser = $employee->getAdapter()->query("select group_concat(employee_id) as users from oa_user where active = 1 and id in ( " . implode(',', $tmpUser) . ")")->fetchObject();
                                $users = $tmpUser->users;
                            }
                            // 获取角色所有人员
//                            $tmpUser = $employee->getAdapter()->query("select group_concat(id) as ids from oa_employee where dept_id in ( " . $plan_dept . ")")->fetchObject();
                            if ($users) {
                                if ($plan_user)
                                    $plan_user .= ",";
                                $plan_user .= $users;
                            }
                            $repeatUser = explode(',', $plan_user);
                            $plan_user = array();
                            foreach($repeatUser as $u) {
                                if($u && !in_array($u, $plan_user)) {
                                    $plan_user[] = $u;
                                }
                            }
                            $plan_user = implode(',', $plan_user);
                        }
                        $reviewData = array(
                            'type' => "files",
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
                            $to = $employee->getAdapter()->query("select group_concat(email) as mail_to from oa_employee where id in ( " . $plan_user . ")")->fetchObject();
                            $content = "你有新文件需要审核，<p><b>文件号：</b>" . implode(',', $code_file_code) . "</p><p><b>版本：</b>" . implode(',', $vers) . "</p><p><b>文件描述：</b>" . $val->description . "</p><p><b>备注：</b>" . $val->remark . "</p><p><b>申请人：</b>" . $user_name . "</p><p><b>申请时间：</b>" . $now . "</p><p>请登录系统查看详情！</p>";
                            $mailData = array(
                                'type' => '新文件',
                                'subject' => '新文件评审',
                                'to' => $to->mail_to,
                                'cc' => '',
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
                    // 没有审批流程，旧版文件自动作废
                    if(!isset($state)) {
                    	$sids = array($id);
                    	if (isset($sids) && count($sids) > 0) {
                    		if(count($code_file_code) > 0) {
                    			$codes = array();
                    			foreach($code_file_code as $c) {
                    				$codes[] = $c;
                    			}
                    		}
                    		if(isset($codes) && count($codes) > 0)
                    		    for($i = 0; $i < count($codes); $i++) {
                    		        $codes[$i] = "'".$codes[$i]."'";
                    		    }
		                        $obsoluteWhere = " id not in (" . implode(',', $sids) . ") and code in (".implode(',', $codes).")";
		                }
		                $obsoluteData = array(
		                    "state" => "Obsolete"
		                );
		                if (isset($obsoluteWhere)) {
			                $files->update($obsoluteData, $obsoluteWhere);
			            }

			            // 更改文件状态为归档
			            $uploadData = array(
			                "archive" => 1,
			                "archive_time" => $now
			            );
			            $uploadWhere = "id in (".implode(',', $code_file_file_id).")";
			            // 更新文件
			            $upload->update($uploadData, $uploadWhere);
                    }
                }
            } catch (Exception $e) {
                $result['result'] = false;
                $result['info'] = $e->getMessage();

                echo Zend_Json::encode($result);

                exit;
            }

            echo Zend_Json::encode($result);

            exit;
        }
    }

    /**
     * @abstract    文件升版
     * @return      null
     */
    public function upgradeAction() {
        // 返回值数组
        $result = array(
            'success' => true,
            'result' => true,
            'info' => '保存成功'
        );

        $request = $this->getRequest()->getParams();

        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];
        $user_name = $user_session->user_info['user_name'];

        $val = (object) $request;

        $json = json_decode($request['json']);
        $code_file_code_id = $json->code_id;
        $code_file_code = $json->code;
        $code_file_file_id = $json->file_id;
        $code_file_file = $json->file;
        $code_file_desc = isset($json->code_file_desc) ? $json->code_file_desc : '';
        $code_file_project_no = isset($json->code_file_project_no) ? $json->code_file_project_no : '';

        $files = new Dcc_Model_Files();
        $upload = new Dcc_Model_Upload();
        $record = new Dcc_Model_Record();
        $review = new Dcc_Model_Review();
        $upgrade = new Dcc_Model_Upgrade();
        $employee = new Hra_Model_Employee();
        $mail = new Application_Model_Log_Mail();
        $member = new Admin_Model_Member();

        $code = $code_file_code[0];
        $stepRows = array();
        if ($code) {
            $codeModel = new Dcc_Model_Code();
            // 获取当前文件类别对应的流程ID
            $row = $codeModel->getTypeInfo("t1.code = '$code'");
            $flow_id = $row['dev_flow_id'];

            if ($flow_id) {
                // 根据流程ID获取阶段
                $flow = new Admin_Model_Flow();
                $step = new Admin_Model_Step();

                $flowRow = $flow->getRow($flow_id);
                $step_ids = $flowRow['step_ids'];
                if ($step_ids) {
                    $stepRows = $step->getListByFlow($step_ids);
                    $state = $stepRows[0]['step_name'];
                }
            }
        }
        // 获取所有文件的版本号
        $vers = array();
        foreach ($code_file_code as $c) {
            $d = $files->getAdapter()->query("select code, max(ver) as ver from oa_doc_files where (code = :code or FIND_IN_SET(:code, code)) and state = 'Active' and del_flg = 0", array('code' => $c))->fetch();
            $ver = $d['ver'];
            $oldVers = explode(',', $ver);
            $oldCodes = explode(',', $d['code']);

            if(count($oldVers) > 1 && count($oldCodes) > 1) {
                for($i=0;$i<count($oldCodes);$i++) {
                    if($oldCodes[$i] == $c) {
                        $v = round($oldVers[$i] + 0.1, 1);
                        break;
                    }
                }
            } else {
                $v = round($d['ver'] + 0.1, 1);

            }
            if(strcmp($v , (int)$v) === 0) {
                $v .= ".0";
            }
            $vers[] = $v;
        }

        // 新增还是编辑
        if ($val->id) {
            $result['info'] = '修改成功';
            $data = array(
                'state' => isset($state) ? 'Reviewing' : 'Active',
                'code' => implode(',', $code_file_code),
                'name' => implode(',', $code_file_file),
                'file_ids' => implode(',', $code_file_file_id),
                'ver' => implode(',', $vers),
                'description' => $code_file_desc,
                'project_info' => implode(',', $code_file_project_no),
                'remark' => $val->remark,
                'tag' => $val->tag,
                'update_time' => $now,
                'update_user' => $user
            );
            $id = $val->id;
            $where = "id = " . $id;

            // 修改升版记录
            $upgradeData = array(
                'reason' => $val->reason,
                'reason_type' => $val->reason_type,
                'update_time' => $now,
                'update_user' => $user
            );
            $upgradeWhere = "file_id = $id";
            try {
                if ($id) {
                    $upgrade->update($upgradeData, $upgradeWhere);
                    $files->update($data, $where);

                    $attrval = new Admin_Model_Formval();
                    // 自定义字段
                    foreach ($request as $field => $value) {
                        if (stripos($field, "intelligenceField") !== false) {
                            $attrId = str_replace("intelligenceField", "", $field);
                            $menu = 'oa_doc_files_' . $id;

                            $formval = array(
                                'attrid' => $attrId,
                                'value' => $value,
                                'menu' => $menu
                            );
                            $where = "attrid = " . $attrId . " and menu = '" . $menu . "'";
                            if ($attrval->fetchAll($where)->count() > 0) {
                                // 更新
                                $attrval->update($formval, $where);
                            } else {
                                $attrval->insert($formval);
                            }
                        }
                    }

                    // 操作记录
                    $data = array(
                        'type' => "files",
                        'table_name' => "oa_doc_files",
                        'table_id' => $id,
                        'handle_user' => $user,
                        'handle_time' => $now,
                        'action' => "编辑",
                        'ip' => $_SERVER['REMOTE_ADDR']
                    );
                    $record->insert($data);

                    // 审核流程
                    // 删除已存在的审核记录
                    $review->delete("type='files' and file_id = " . $id);
                    // 把阶段信息插入review记录

                    $first = true;
                    foreach ($stepRows as $s) {
                        $plan_user = $s['user'];
                        if ($s['dept']) {
                            $plan_dept = $s['dept'];
                            // 根据角色id和项目号获取用户成员列表
                            $roleid = $s['dept'];
                            $codeTmp = $employee->getAdapter()->query("select project_no from oa_doc_code where code = '$code'")->fetchObject();
                            $projectno = $codeTmp->project_no;
                            // 如果不存在项目号，则直接取角色中的用户 否则根据角色id和项目号获取roleset id
                            $tmpUser = array();
                            $tmpBool = true;
                            if ($projectno) {
                                // 根据角色id和项目号获取roleset id
                                $rolesetTmp = $employee->getAdapter()->query("select group_concat(id) as id from oa_product_catalog_roleset where active=1 and catalog_id='$projectno' and role_id in ( " . $roleid . ")")->fetchObject();
                                $rolesetid = $rolesetTmp->id;
                                if ($rolesetid) {
                                    $tmpBool = false;
                                    $userTmp = $employee->getAdapter()->query("select group_concat(user_id) as ids from oa_product_catalog_roleset_member where roleset_id in ( " . $rolesetid . ")")->fetchObject();
                                    $tmpUser = explode(',', $userTmp->ids);
                                    // 如果没有取到用户，还是使用默认用户
                                    if (count($tmpUser) == 0) {
                                        $tmpBool = true;
                                    }
                                }
                            }
                            if ($tmpBool) {
                                foreach (explode(',', $plan_dept) as $role) {
                                    $tmpRole = $member->getMemberWithNoManager($role);
                                    foreach ($tmpRole as $m) {
                                        $tmpUser[] = $m['user_id'];
                                    }
                                }
                            }
                            if (count($tmpUser) == 0 && !$plan_user) {
                                $tmpUser = $member->getUserids("系统管理员");
                            }
                            // user id 转换成employee id
                            if (count($tmpUser) > 0) {
                                $tmpUser = $employee->getAdapter()->query("select group_concat(employee_id) as users from oa_user where active = 1 and id in ( " . implode(',', $tmpUser) . ")")->fetchObject();
                                $users = $tmpUser->users;
                            }
                            // 获取角色所有人员
//                            $tmpUser = $employee->getAdapter()->query("select group_concat(id) as ids from oa_employee where dept_id in ( " . $plan_dept . ")")->fetchObject();
                            if ($users) {
                                if ($plan_user)
                                    $plan_user .= ",";
                                $plan_user .= $users;
                            }
                            $repeatUser = explode(',', $plan_user);
                            $repeatUser = array_unique($repeatUser);
                            $plan_user = implode(',', $repeatUser);
                        }
                        $reviewData = array(
                            'type' => "files",
                            'file_id' => $id,
                            'plan_dept' => $s['dept'],
                            'plan_user' => $plan_user,
                            'method' => $s['method'],
                            'return' => $s['return'],
                            'step_name' => $s['step_name'],
                            'step_ename' => $s['step_ename']
                        );
                        $review->insert($reviewData);

                        // 邮件任务
                        if ($first) {
                            $to = $employee->getAdapter()->query("select group_concat(email) as mail_to from oa_employee where id in ( " . $plan_user . ")")->fetchObject();
                            $content = "你有文件升版需要审核，<p><b>文件号：</b>" . implode(',', $code_file_code) . "</p><p><b>版本：</b>" . implode(',', $vers) . "</p><p><b>升版原因：</b>" . $val->reason . "</p><p><b>备注：</b>" . $val->remark . "</p><p><b>申请人：</b>" . $user_name . "</p><p><b>申请时间：</b>" . $now . "</p><p>请登录系统查看详情！</p>";
                            $mailData = array(
                                'type' => '升版文件',
                                'subject' => '升版文件评审',
                                'to' => $to->mail_to,
                                'cc' => '',
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

                    // 没有审批流程，旧版文件自动作废
                    if(!isset($state)) {
                        $sids = array($id);
                        if (isset($sids) && count($sids) > 0) {
                            if(count($code_file_code) > 0) {
                                $codes = array();
                                foreach($code_file_code as $c) {
                                    $codes[] = $c;
                                }
                            }
                            if(isset($codes) && count($codes) > 0)
                            for($i = 0; $i < count($codes); $i++) {
                                $codes[$i] = "'".$codes[$i]."'";
                            }
                            $obsoluteWhere = " id not in (" . implode(',', $sids) . ") and code in (".implode(',', $codes).")";
                        }
                        $obsoluteData = array(
                                "state" => "Obsolete"
                        );
                        if (isset($obsoluteWhere)) {
                            $files->update($obsoluteData, $obsoluteWhere);
                        }
                    
                        // 更改文件状态为归档
                        $uploadData = array(
                                "archive" => 1,
                                "archive_time" => $now
                        );
                        $uploadWhere = "id in (".implode(',', $code_file_file_id).")";
                        // 更新文件
                        $upload->update($uploadData, $uploadWhere);
                    }
                }
            } catch (Exception $e) {
                $result['result'] = false;
                $result['info'] = $e->getMessage();

                echo Zend_Json::encode($result);

                exit;
            }

            echo Zend_Json::encode($result);

            exit;
        } else {
            $data = array(
                'state' => isset($state) ? 'Reviewing' : 'Active',
                'code' => implode(',', $code_file_code),
                'name' => implode(',', $code_file_file),
                'file_ids' => implode(',', $code_file_file_id),
                'ver' => implode(',', $vers),
                'description' => $code_file_desc,
                'project_info' => implode(',', $code_file_project_no),
                'remark' => $val->remark,
                'tag' => $val->tag,
                'create_time' => $now,
                'create_user' => $user,
                'update_time' => $now,
                'update_user' => $user
            );

            try {
                $id = $files->insert($data);
                if ($id) {
                    // 升版记录
                    $upgradeData = array(
                        'file_id' => $id,
                        'reason' => $val->reason,
                        'reason_type' => $val->reason_type,
                        'create_time' => $now,
                        'create_user' => $user,
                        'update_time' => $now,
                        'update_user' => $user
                    );
                    $upgrade->insert($upgradeData);

                    // 自定义字段
                    $attrval = new Admin_Model_Formval();
                    foreach ($request as $field => $value) {
                        if (stripos($field, "intelligenceField") !== false && $value) {
                            $attrId = str_replace("intelligenceField", "", $field);
                            $menu = 'oa_doc_files_' . $id;

                            $formval = array(
                                'attrid' => $attrId,
                                'value' => $value,
                                'menu' => $menu
                            );
                            $attrval->insert($formval);
                        }
                    }

                    // 操作记录
                    $data = array(
                        'type' => "files",
                        'table_name' => "oa_doc_files",
                        'table_id' => $id,
                        'handle_user' => $user,
                        'handle_time' => $now,
                        'action' => "申请",
                        'ip' => $_SERVER['REMOTE_ADDR']
                    );
                    $record->insert($data);

                    // 审核流程
                    // 把阶段信息插入review记录
                    $first = true;
                    foreach ($stepRows as $s) {
                        $plan_user = $s['user'];
                        if ($s['dept']) {
                            $plan_dept = $s['dept'];
                            // 根据角色id和项目号获取用户成员列表
                            $roleid = $s['dept'];
                            $codeTmp = $employee->getAdapter()->query("select project_no from oa_doc_code where code = '$code'")->fetchObject();
                            $projectno = $codeTmp->project_no;
                            // 如果不存在项目号，则直接取角色中的用户 否则根据角色id和项目号获取roleset id
                            $tmpUser = array();
                            $tmpBool = true;
                            if ($projectno) {
                                // 根据角色id和项目号获取roleset id
                                $rolesetTmp = $employee->getAdapter()->query("select group_concat(id) as id from oa_product_catalog_roleset where active=1 and catalog_id='$projectno' and role_id in ( " . $roleid . ")")->fetchObject();
                                $rolesetid = $rolesetTmp->id;
                                if ($rolesetid) {
                                    $tmpBool = false;
                                    $userTmp = $employee->getAdapter()->query("select group_concat(user_id) as ids from oa_product_catalog_roleset_member where roleset_id in ( " . $rolesetid . ")")->fetchObject();
                                    $tmpUser = explode(',', $userTmp->ids);
                                    // 如果没有取到用户，还是使用默认用户
                                    if (count($tmpUser) == 0) {
                                        $tmpBool = true;
                                    }
                                }
                            }
                            if ($tmpBool) {
                                foreach (explode(',', $plan_dept) as $role) {
                                    $tmpRole = $member->getMemberWithNoManager($role);
                                    foreach ($tmpRole as $m) {
                                        $tmpUser[] = $m['user_id'];
                                    }
                                }
                            }
                            if (count($tmpUser) == 0 && !$plan_user) {
                                $tmpUser = $member->getUserids("系统管理员");
                            }
                            // user id 转换成employee id
                            if (count($tmpUser) > 0) {
                                $tmpUser = $employee->getAdapter()->query("select group_concat(employee_id) as users from oa_user where active = 1 and id in ( " . implode(',', $tmpUser) . ")")->fetchObject();
                                $users = $tmpUser->users;
                            }
                            // 获取角色所有人员
//                            $tmpUser = $employee->getAdapter()->query("select group_concat(id) as ids from oa_employee where dept_id in ( " . $plan_dept . ")")->fetchObject();
                            if ($users) {
                                if ($plan_user)
                                    $plan_user .= ",";
                                $plan_user .= $users;
                            }
                            $repeatUser = explode(',', $plan_user);
                            $repeatUser = array_unique($repeatUser);
                            $plan_user = implode(',', $repeatUser);
                        }
                        $reviewData = array(
                            'type' => "files",
                            'file_id' => $id,
                            'plan_dept' => $s['dept'],
                            'plan_user' => $plan_user,
                            'method' => $s['method'],
                            'return' => $s['return'],
                            'step_name' => $s['step_name'],
                            'step_ename' => $s['step_ename']
                        );
                        $review->insert($reviewData);

                        // 邮件任务
                        if ($first) {
                            $to = $employee->getAdapter()->query("select group_concat(email) as mail_to from oa_employee where id in ( " . $plan_user . ")")->fetchObject();
                            $content = "你有文件升版需要审核，<p><b>文件号：</b>" . implode(',', $code_file_code) . "</p><p><b>版本：</b>" . implode(',', $vers) . "</p><p><b>升版原因：</b>" . $val->reason . "</p><p><b>备注：</b>" . $val->remark . "</p><p><b>申请人：</b>" . $user_name . "</p><p><b>申请时间：</b>" . $now . "</p><p>请登录系统查看详情！</p>";
                            $mailData = array(
                                'type' => '升版文件',
                                'subject' => '升版文件评审',
                                'to' => $to->mail_to,
                                'cc' => '',
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


                    // 没有审批流程，旧版文件自动作废
                    if(!isset($state)) {
                        $sids = array($id);
                        if (isset($sids) && count($sids) > 0) {
                            if(count($code_file_code) > 0) {
                                $codes = array();
                                foreach($code_file_code as $c) {
                                    $codes[] = $c;
                                }
                            }
                            if(isset($codes) && count($codes) > 0)
                            for($i = 0; $i < count($codes); $i++) {
                                $codes[$i] = "'".$codes[$i]."'";
                            }
                            $obsoluteWhere = " id not in (" . implode(',', $sids) . ") and code in (".implode(',', $codes).")";
                        }
                        $obsoluteData = array(
                                "state" => "Obsolete"
                        );
                        if (isset($obsoluteWhere)) {
                            $files->update($obsoluteData, $obsoluteWhere);
                        }
                    
                        // 更改文件状态为归档
                        $uploadData = array(
                                "archive" => 1,
                                "archive_time" => $now
                        );
                        $uploadWhere = "id in (".implode(',', $code_file_file_id).")";
                        // 更新文件
                        $upload->update($uploadData, $uploadWhere);
                    }
                }
            } catch (Exception $e) {
                $result['result'] = false;
                $result['info'] = $e->getMessage();

                echo Zend_Json::encode($result);

                exit;
            }

            echo Zend_Json::encode($result);

            exit;
        }
    }

    /**
     * @abstract    审核文件
     * @return      null
     */
    public function reviewAction() {
        // 返回值数组
        $result = array(
            'success' => true,
            'result' => true,
            'info' => '审批成功'
        );

        $request = $this->getRequest()->getParams();

        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];

        $val = (object) $request;

        $files = new Dcc_Model_Files();
        $record = new Dcc_Model_Record();
        $review = new Dcc_Model_Review();
        $formval = new Admin_Model_Formval();
        $employee = new Hra_Model_Employee();
        $upgrade = new Dcc_Model_Upgrade();

        $id = $val->id;
        $remark = $val->remark;
        $pass = $val->review_result;
        $review_id = $val->review_id;
        $publish = false;

        // 获取文件信息
        $filesData = $files->getOne($id);

        // 获取当前审核情况
        // 如果record记录被删除或状态已改变，报错
        $reviewWhere = "id = $review_id";
        $reviewRows = $review->getList($reviewWhere, "files");
        if (count($reviewRows) == 0) {
            $result['result'] = false;
            $result['info'] = "非法数据";

            echo Zend_Json::encode($result);
            exit;
        }
        $reviewRow = $reviewRows[0];
        if ($reviewRow['finish_flg'] != 0) {
            $result['result'] = false;
            $result['info'] = "数据状态已改变";

            echo Zend_Json::encode($result);
            exit;
        }

        // 处理记录
        $recordData = array(
            "type" => "files",
            "table_name" => "oa_doc_files",
            "table_id" => $id,
            "handle_user" => $user,
            "handle_time" => $now,
            "action" => "审批",
            "result" => $pass == 1 ? "批准" : ($pass == 2 ? "拒绝" : "转审"),
            "ip" => $_SERVER['REMOTE_ADDR'],
            "remark" => $remark
        );
        // 增加记录
        $record->insert($recordData);
        // 通过方式
        $method = $reviewRow['method'];

        if ($pass == 1) {
            if ($method == 2) {
                // 任何一人处理即通过
                $finish_flg = 1;
                $actual_user = $user;
                $finish_time = $now;
            } else {
                // 所有人都需要审核，检查是否所有人都已经审核
                $plan_user = $reviewRow['plan_user'];
                $actual_user = $reviewRow['actual_user'];
                $actual_user = !$actual_user ? $user : $actual_user . "," . $user;
                // 检查计划审核人和实际审核人是否一致

//                $plan_dept = $reviewRow['plan_dept'];
//                if ($plan_dept) {
//                    // 获取部门所有人员
//                    $tmpUser = $employee->getAdapter()->query("select group_concat(id) as ids from oa_employee where dept_id in ( " . $plan_dept . ")")->fetchObject();
//                    if ($tmpUser->ids) {
//                        if ($plan_user)
//                            $plan_user .= ",";
//                        $plan_user .= $tmpUser->ids;
//                    }
//                }
                $planA = explode(',', $plan_user);
                $actualA = explode(',', $actual_user);
                $passFlg = true;
                foreach($planA as $u) {
                    if($u && !in_array($u, $actualA)) {
                        $passFlg = false;
                    }
                }
                if ($passFlg) {
                    $finish_flg = 1;
                    $finish_time = $now;
                } else {
                    $finish_flg = 0;
                    $finish_time = null;
                }
            }

            // 审核情况
            $reviewData = array(
                "actual_user" => $actual_user,
                "finish_time" => $finish_time,
                "finish_flg" => $finish_flg
            );
        } else if ($pass == 3) {
            // 转审
            $finish_flg = 0;
            if($method == 2) {
            	// 处理方式为任意时，一个人转审之后其他人员也删除
            	$plan_user = str_replace('E', '', $val->transfer_id);
            } else {
	            // 更改审核情况中的审核人
	            $plan_users = explode(',', $reviewRow['plan_user']);
	                
                // 审核人不在审核人列表中，并且是管理员，则替换所有人
                if(!in_array($user, $plan_users) &&
                    (Application_Model_User::checkPermissionByRoleName('文件管理员') ||
                        Application_Model_User::checkPermissionByRoleName('系统管理员'))) {
                    $plan_users = array(str_replace('E', '', $val->transfer_id));
                } else {
	                for ($i = 0; $i < count($plan_users); $i++) {
	                    if ($plan_users[$i] == $user) {
	                        $plan_users[$i] = str_replace('E', '', $val->transfer_id);
	                        break;
	                    }
	                }
                }
	            $plan_user = implode(',', $plan_users);
            }

            // 审核情况
            $reviewData = array(
                "plan_user" => $plan_user,
                "method" => 1
            );
        } else {
            // 退回
            $actual_user = null;
            $finish_time = null;
            $finish_flg = 0;
            // 退回选项
            $return = $reviewRow['return'];
            if ($return == 2) {
                // 退到初始状态
                // 需更新的审核记录: 所有
                $reviewWhere = "type = 'files' and file_id = $id";
                // 审核情况更新数据
                $reviewData = array(
                    "actual_user" => $actual_user,
                    "finish_time" => $finish_time,
                    "finish_flg" => $finish_flg
                );
                // 文件状态不更新
            } else if ($return == 4) {
                // 退到本阶段开始
                // 需更新的审核记录
                $reviewWhere = "type = 'files' and finish_flg = 0 and file_id = $id";
                // 审核情况更新数据
                $reviewData = array(
                    "actual_user" => $actual_user,
                    "finish_time" => $finish_time,
                    "finish_flg" => $finish_flg
                );
                // 文件状态不更新
            } else if ($return == 3) {
                // 退到上一阶段
                // 需更新的审核记录：最后一个finish_flg为1的数据和第一个finish_flg为0的数据
                $last_1 = $first_0 = 0;
                foreach ($reviewRows as $r) {
                    if ($r['finish_flg'] == 1) {
                        $last_1 = $r['id'];
                    }
                    if ($r['finish_flg'] == 0 && $first_0 == null) {
                        $first_0 = $r['id'];
                    }
                }
                $reviewWhere = "id = $last_1 or id = $first_0";
                // 审核情况更新数据
                $reviewData = array(
                    "actual_user" => $actual_user,
                    "finish_time" => $finish_time,
                    "finish_flg" => $finish_flg
                );
                // 文件状态不更新
            } else {
                $fileWhere = "id = $id";
                // 更新文件状态为退回
                $fileData = array(
                    "state" => "Return"
                );
                // 退到初始状态
                // 更新所有record的finish_flg为0
                $reviewWhere = "type = 'files' and file_id = $id";
                // 审核情况
                $reviewData = array(
                    "actual_user" => $actual_user,
                    "finish_time" => $finish_time,
                    "finish_flg" => $finish_flg
                );
            }
        }

        $filesRow = $files->getOne($reviewRow['file_id']);
        // 判断是否需要更新文件
        // 如果所有record的记录的finish_flg 都为1，则发布
        if ($finish_flg == 1 && $review->fetchAll("type = 'files' and finish_flg = 0 and file_id = $id")->count() == 1) {
            $publish = true;
            $obsolute = false;
            // 如果是多个文件同时归档，需拆分
            if (strpos($filesRow['code'], ',') !== false) {
                $codes = explode(',', $filesRow['code']);
                $names = explode(',', $filesRow['name']);
                $file_ids = explode(',', $filesRow['file_ids']);
                $vers = explode(',', $filesRow['ver']);
                $description = explode('|', $filesRow['description']);
                $project_info = explode(',', $filesRow['project_info']);
                $ids = array();
                $k = 0;
                for ($i = 0; $i < count($names); $i++) {
                    $ids[] = "";
                    $length = substr_count($names[$i], '|');
                    for ($j = 0; $j <= $length; $j++) {
                        if (isset($ids[$i]) && $ids[$i])
                            $ids[$i] .= ',';
                        $ids[$i] .= $file_ids[$k++];
                    }
                }
                for ($i = 0; $i < count($codes); $i++) {
                    // 更新第一条
                    if ($i == 0) {
                        $data = array(
                            "state" => "Active",
                            "code" => $codes[$i],
                            "name" => $names[$i],
                            "file_ids" => $ids[$i],
                            "ver" => $vers[$i],
                            "description" => $description[$i],
                            "project_info" => $project_info[$i],
                            "archive_time" => $now
                        );
                        try {
                            $files->update($data, "id = $id");
                            // 如果是升版，登录升版信息
                            if ($vers[$i] > 1.0) {
                                $upgradeTmpData = array(
                                    "project_no" => $project_info[$i],
                                    "description" => $description[$i]
                                );
                                $upgrade->update($upgradeTmpData, "file_id=".$id);
                            }
                        } catch (Exception $e) {
                            $result['result'] = false;
                            $result['info'] = $e->getMessage();

                            echo Zend_Json::encode($result);

                            exit;
                        }
                    } else {
                        $data = array(
                            "state" => "Active",
                            "code" => $codes[$i],
                            "ver" => $vers[$i],
                            "tag" => $filesRow['tag'],
                            "name" => $names[$i],
                            "file_ids" => $ids[$i],
                            "description" => $description[$i],
                            "project_info" => $project_info[$i],
                            "remark" => $filesRow['remark'],
                            "create_time" => $filesRow['create_time'],
                            "update_time" => $filesRow['update_time'],
                            "create_user" => $filesRow['create_user'],
                            "update_user" => $filesRow['update_user'],
                            "archive_time" => $now,
                            "add_flg" => $filesRow['add_flg']
                        );
                        try {
                            $sid = $files->insert($data);
                            // 拆分智能表单数据
                            if ($sid) {
                                $sids[] = $sid;
                                $whereMenu = "oa_doc_files_" . $id;
                                $menus = $formval->getListByMenu($whereMenu);
                                foreach ($menus as $menu) {
                                    $menuData = array(
                                        "attrid" => $menu['attrid'],
                                        "value" => $menu['value'],
                                        "menu" => "oa_doc_files_" . $sid
                                    );
                                    $formval->insert($menuData);
                                }

                                // 如果是升版，登录升版信息
                                if ($vers[$i] > 1.0) {
                                    $obsolute = true;
                                    $upgradeRow = $upgrade->fetchAll("file_id = $id")->toArray();
                                    foreach ($upgradeRow as $row) {
                                        $upgradeTmpData = array(
                                            "file_id"     => $sid,
                                            "reason"      => $row['reason'],
                                            "reason_type" => $row['reason_type'],
                                            "project_no" => $project_info[$i],
                                            "description" => $description[$i],
                                            "create_user" => $row['create_user'],
                                            "create_time" => $row['create_time'],
                                            "update_user" => $row['update_user'],
                                            "update_time" => $row['update_time']
                                        );
                                        $upgrade->insert($upgradeTmpData);
                                    }
                                }
                                $recordRow = $record->fetchAll("type='files' and table_name='oa_doc_files' and table_id=$id")->toArray();
                                foreach ($recordRow as $row) {
                                    // 增加记录
                                    $recordTmpData = array(
                                        "type" => $row['type'],
                                        "table_name" => $row['table_name'],
                                        "table_id" => $sid,
                                        "handle_user" => $row['handle_user'],
                                        "handle_time" => $row['handle_time'],
                                        "action" => $row['action'],
                                        "result" => $row['result'],
                                        "ip" => $row['ip'],
                                        "remark" => $row['remark'],
                                    );
                                    $record->insert($recordTmpData);
                                }
                            }
                        } catch (Exception $e) {
                            $result['result'] = false;
                            $result['info'] = $e->getMessage();

                            echo Zend_Json::encode($result);

                            exit;
                        }
                    }
                }
            }
            $fileData = array(
                "state" => "Active",
                "archive_time" => $now
            );
            $fileWhere = "id = $id";
            // 更新文件状态为已归档
            $uploadData = array(
                "archive" => 1,
                "archive_time" => $now
            );
            $upload = new Dcc_Model_Upload();
            // 获取上传文件id
            $ids = $filesRow['file_ids'];
            $uploadWhere = "id in ($ids)";
            $upload->update($uploadData, $uploadWhere);
            // 更新旧版文件的状态为已作废
            if (strpos($filesData['ver'], '1.0') === false || $obsolute) {
                if (isset($sids) && count($sids) > 0) {
                    $obsoluteWhere = " id not in (" . implode(',', $sids) . ") and FIND_IN_SET(code, '" . $filesRow['code'] . "')";
                } else {
                    $obsoluteWhere = " FIND_IN_SET(code, '" . $filesRow['code'] . "')";
                }
                $obsoluteData = array(
                    "state" => "Obsolete"
                );
            }
        }

        try {
            // 更新审核情况
            $review->update($reviewData, $reviewWhere);
            if (isset($obsoluteData) && isset($obsoluteWhere)) {
                $files->update($obsoluteData, $obsoluteWhere);
            }
            // 更新文件
            if (isset($fileWhere)) {
                $files->update($fileData, $fileWhere);
            }
            $this->operate("文件审批");
        } catch (Exception $e) {
            $result['result'] = false;
            $result['info'] = $e->getMessage();

            echo Zend_Json::encode($result);

            exit;
        }

        // 邮件任务
        // 文件提交者或更新人
        $owner = $filesData['create_user'];
        if ($filesData['create_user'] != $filesData['update_user']) {
            $owner .= "," . $filesData['update_user'];
        }

        $dev = false;
        $type = "新文件";
        if (stripos($filesData['ver'], '1.0') === false) {
            $dev = true;
            $type = "升版文件";
        }
        $content = "<p><b>文件号：</b>" . $filesData['code'] . "</p><p><b>版本：</b>" . $filesData['ver'] . "</p><p><b>文件描述：</b>" . $filesData['description'] . "</p><p><b>备注：</b>" . $filesData['remark'] . "</p><p><b>申请人：</b>" . $filesData['creater'] . "</p><p><b>申请时间：</b>" . $filesData['create_time'] . "</p><p>请登录系统查看详情！</p>";
        // 发邮件的情况：
        // 1、单站审核结束 $finish_flg = 1 && $publish = false
        if ($finish_flg == 1 && !$publish) {
            $subject = $type . "审批";
            // $to = 下一站审核人
            $current = $review->getFirstNoReview("files", $id);
            $to = $employee->getInfosByOneLine($current['plan_user']);
            //
            $cc = $employee->getInfosByOneLine($owner);
            $cc = $cc['email'];
            //$cc = "";
            $content = "你有一个" . $type . "需要审批，" . $content;
        }

        // 2、所有审核结束  $publish = true
        if ($publish) {
            $subject = $type . "发布";
            $to = $employee->getInfosByOneLine($owner);
            $cc = $employee->getInfosByOneLine($record->getEmployeeIds($filesData['id'], 'files'));
            $cc = $cc['email'];
//            $cc = "";
            $content .= "<p><b>审核记录：</b><br>" . str_replace(',','<br>',$record->getHis($filesData['id'], 'files', 'oa_doc_files'))."</p>";
            $content = "你申请的" . $type . "已通过审批，" . $content;
        }
        // 3、退回 isset($return)
        if (isset($return)) {
            $subject = $type . "退回";
            $to = $employee->getInfosByOneLine($owner);
            $cc = "";
            // 原审核人
            if($reviewRow['plan_user']) {
                $orgUser = $reviewRow['plan_user'];
                $cc = $employee->getInfosByOneLine($orgUser);
                $cc = $cc['email'];
            }
            $content = "你申请的" . $type . "已被退回，<p><b>退回原因：</b>" . $remark . "</p>" . $content;
        }
        // 4、转审 $pass == 3
        if ($pass == 3) {
            $subject = $type . "转审";
            $toUser = str_replace('E', '', $val->transfer_id);
            $to = $employee->getInfosByOneLine($toUser);
            // 原审核人
            if($reviewRow['plan_user']) {
                $orgUser = $reviewRow['plan_user'];
                $owner .= ",".$orgUser;
            }
            $cc = $employee->getInfosByOneLine($owner);
            $cc = $cc['email'];
//             $cc = "";
            $content = "有新的" . $type . "被转移到你处审批：" . $content;
        }

        if (isset($subject)) {
            $mailData = array(
                'type' => $type,
                'subject' => $subject,
                'to' => $to['email'],
                'cc' => $cc,
                'content' => $content,
                'send_time' => $now,
                'add_date' => $now
            );

            $mail = new Application_Model_Log_Mail();
            try {
                $mailId = $mail->insert($mailData);
            } catch (Exception $e) {
                $result['result'] = false;
                $result['info'] = $e->getMessage();

                echo Zend_Json::encode($result);

                exit;
            }
            if ($mailId) {
                $mail->send($mailId);
            }
        }

        echo Zend_Json::encode($result);

        exit;
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
            'target' => 'Dcc',
            'computer_name' => $computer_name,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'time' => $now
        );

        $operate->insert($data);
    }

    public function getreasonAction() {
        $where = "type=5";
        $codemaster = new Admin_Model_Codemaster();
        $data = $codemaster->getList($where);
        // 转为json格式并输出
        echo Zend_Json::encode($data);

        exit;
    }

}