<?php

/**
 * 2013-9-8
 * @author      mg.luo
 * @abstract    物料编码申请
 */
class Product_ApplyController extends Zend_Controller_Action {

    public function indexAction() {

    }

    public function getlistAction() {
        $request = $this->getRequest()->getParams();
        $limit = $request['limit'];
        $start = $request['start'];

        $type = new Product_Model_Type();
        $whereSearch = "1=1";
        foreach ($request as $k => $v) {
            if ($v) {
                if ($k == 'search_tag') {
                    $cols = array("t1.name", "t1.remark", "t1.manufacturers", "t1.code", "t1.description", "t3.cname");
                    $arr=preg_split('/\s+/',trim($v));
                    for ($i=0;$i<count($arr);$i++) {
                        $tmp = array();
                        foreach($cols as $c) {
                            $tmp[] = "ifnull($c,'')";
                        }
                        $arr[$i] = "concat(".implode(',', $tmp).") like '%".$arr[$i]."%'";
                    }
                    $whereSearch .= " and ".join(' AND ', $arr);
//                     $whereSearch .= " and (ifnull(t1.name,'') like '%$v%' or ifnull(t1.remark,'') like '%$v%' or ifnull(t1.manufacturers,'') like '%$v%' or ifnull(t1.code,'') like '%$v%' or ifnull(t1.description,'') like '%$v%' or ifnull(t3.cname,'') like '%$v%')";
                } else if ("search_archive_date_from" == $k && $v) {
                    $whereSearch .= " and t1.archive_time >= '" . str_replace('T', ' ', $v) . "'";
                } else if ("search_archive_date_to" == $k && $v) {
                    $whereSearch .= " and t1.archive_time <= '" . str_replace('T00:00:00', ' 23:59:59', $v) . "'";
                } else if("search_type" == $k && $v) {
                    $whereSearch .= " and t1.type in ($v)";
                } else {
                    $col = str_replace('search_', '', $k);
                    if ($col != $k) {
                        // 查询条件
                        $whereSearch .= " and ifnull(t1." . $col . ",'') like '%" . $v . "%'";
                    }
                }
            }
        }

        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];

        $materiel = new Product_Model_Materiel();
        $record = new Dcc_Model_Record();
        $flow = new Admin_Model_Flow();
        $step = new Admin_Model_Step();
        $share = new Dcc_Model_Share();
        $type = new Dcc_Model_Type();
        $review = new Dcc_Model_Review();
        $employee = new Hra_Model_Employee();
        $dept = new Hra_Model_Dept();
        // 查询条件
        // 类型
        $myType = "";
        if (isset($request['mytype'])) {
            $myType = $request['mytype'];
        }
        // 获取物料数据
        $data = $materiel->getMy($myType, $whereSearch, $user, $start, $limit);
        $totalCount = $materiel->getMyCount($myType, $whereSearch, $user);
        for ($i = 0; $i < count($data); $i++) {
            $data[$i]['description'] = htmlspecialchars($data[$i]['description']);
            $mytype = 2;
            if ($data[$i]['create_user'] == $user) {
                $mytype = 1;
            }
            if (($typeId = $data[$i]['type']) != '') {
                $typeName = $this->getTypeByConnect($typeId, '');
                $data[$i]['type_name'] = $typeName;
            }
            $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
            $data[$i]['archive_time'] = strtotime($data[$i]['archive_time']);

            // 增加审核状态
            $reviewState = "";
            $step_name = "";
            if ($data[$i]['state'] == 'Active') {
                $reviewState = "已归档";
            } else if ($data[$i]['state'] == 'Reviewing') {
                // 查询当前审核状态
                // 查询所有审核阶段
                $reviewRows = $review->getList("file_id = " . $data[$i]['id'], "materiel");
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
                                $first = false;

                                $step_name .= "<b>" . $row['step_name'] . "</b>";

                                $reviewRow = $row;
                                $actual_user = explode(',', $reviewRow['actual_user']);
                                $planUser = $reviewRow['plan_user'];
                                $method = $reviewRow['method'];
                                $plan_user = explode(',', $planUser);
                                $diff = array_diff($plan_user, $actual_user);

                                foreach ($diff as $u) {
                                    if (!$u)
                                        continue;
                                    if ($u == $user) {
                                        $mytype = 3;
                                    }
                                    $e = $employee->fetchRow("id = $u");
                                    if ($reviewState)
                                        $reviewState .= ", ";
                                    $reviewState .= $e['cname'] . "：未审核";
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
            } else if ($data[$i]['state'] == 'Obsolete') {
                $reviewState = "已作废";
            } else if ($data[$i]['state'] == 'Return') {
                $reviewState = "退回";
            } else {
                $reviewState = $data[$i]['state'];
            }
            $data[$i]['step_name'] = $step_name;
            $data[$i]['review_state'] = $reviewState;
            $data[$i]['mytype'] = $mytype;

            $data[$i]['record'] = $record->getHis($data[$i]['id'], 'materiel');
        }
        // 排序
        $dataT3 = array();
        $dataT2 = array();
        $dataT1 = array();
        for ($i = 0; $i < count($data); $i++) {
            if ($data[$i]['mytype'] == 3) {
                $dataT3[] = $data[$i];
            } else if ($data[$i]['mytype'] == 2) {
                $dataT2[] = $data[$i];
            } else {
                $dataT1[] = $data[$i];
            }
        }
        $data = array_merge($dataT3, $dataT1, $dataT2);
        $resutl = array(
            "totalCount" => $totalCount,
            "topics" => $data
        );
        // 转为json格式并输出
        echo Zend_Json::encode($resutl);

        exit;
    }

    private function getTypeByConnect($id, $name) {
        if ($id) {
            $type = new Product_Model_Type();
            $row = $type->fetchRow("id = $id");
            if ($row) {
                $id = $row->parent_id;
                if ($id == 0) {
                    if($name) {
                        $name = "<b>" . $row->name . ' &gt; ' . "</b>" . $name;
                    } else {
                        $name = "<b>" . $row->name . "</b>";
                    }
                } else {
                    $name = $row->name . ' &gt; ' . $name;
                }

                return $this->getTypeByConnect($id, $name);
            }
        }
        return trim($name, ' &gt; ');
    }

    private function getTypeCodeByConnect($id, $code = "") {
        if ($id) {
            $type = new Product_Model_Type();
            $row = $type->fetchRow("id = $id");
            if ($row) {
                $id = $row->parent_id;
                $code = $row->code . $code;

                return $this->getTypeCodeByConnect($id, $code);
            }
        }
        return trim($code);
    }

    /**
     * @abstract    取得供应商信息
     * @return      供应商代码+供应商名称
     */
    public function getsupplyAction() {
        $result = $this->getRequest()->getParams();

        $bpartner = new Product_Model_Bpartner();
        if (isset($result['q']) && $result['q']) {
            $query = $result['q'];
            $where = "code = '$query' or cname like '%$query%' or ename like '%$query%'";
        } else {
            $where = "1=1";
        }

        $data = $bpartner->getListForSel($where);
        for ($i = 0; $i < count($data); $i++) {
            if (($code = $data[$i]['code']) != '') {
                if (($cname = $data[$i]['cname']) != '') {
                    $data[$i]['text'] = $code . $cname;
                } else if (($ename = $data[$i]['ename']) != '') {
                    $data[$i]['text'] = $code . $ename;
                } else {
                    $data[$i]['text'] = $code;
                }
            }
        }
        // 转为json格式并输出
        echo Zend_Json::encode($data);

        exit;
    }

    public function getunitAction() {
        $where = "type=1";
        $codemaster = new Admin_Model_Codemaster();
        $data = $codemaster->getList($where);
        // 转为json格式并输出
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

        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];

        $val = (object) $request;

        $materiel = new Product_Model_Materiel();
        $record = new Dcc_Model_Record();
        $review = new Dcc_Model_Review();
        $employee = new Hra_Model_Employee();
        $mail = new Application_Model_Log_Mail();
        $member = new Admin_Model_Member();

        // 根据物料类别获取审批流
        $typeId = $val->type_id;
        $stepRows = array();
        if ($typeId) {
            $type = new Product_Model_Type();
            // 获取当前物料类别对应的流程ID 如果找不到，继续往上搜索
            $flow_id = $type->getFlowId($typeId, 'new');

            if ($flow_id) {
                // 根据流程ID获取阶段
                $flow = new Admin_Model_Flow();
                $step = new Admin_Model_Step();

                $flowRow = $flow->getRow($flow_id);
                $step_ids = $flowRow['step_ids'];
                if ($step_ids) {
                    $stepRows = $step->getListByFlow($step_ids);
                    $state = "Reviewing";
                }
            }
        }
        $ismanager = "";
        // 新增还是编辑
        if (isset($val->id) && $val->id) {
            // 检查物料描述是不是已被使用
            $desc = trim($val->description);
            // 取消描述相同检查
            /*$where = $materiel->getAdapter()->quoteInto("state!= 'Deleted' and state!= 'Return' and id != ".$val->id." and description = ?", $desc);
            if(!$desc || $materiel->fetchAll($where)->count() > 0) {
                $result['success'] = false;
                $result['result'] = false;
                $result['info'] = "物料描述已存在";
                echo Zend_Json::encode($result);
                exit;
            }*/
            $result['info'] = '修改成功';
            if (isset($val->ismanager) && $val->ismanager == '1') {
                $ma = $materiel->getById($val->id);
                $mcode = $ma['code'];
                $mcode1 = $mcode;
                if(isset($val->mcode) && $val->mcode) {
                    $mcode1 = $val->mcode;
                }
                if(isset($val->state) && ($val->state == 'Obsolete' || $val->state == 'Pre-Obsolete' || $val->state == 'Reviewing' || $val->state == 'Return')) {
                    // 检查有没得bom引用
                    $sql = "select count(*) as sum from oa_product_bom_fa fa where fa.state != 'Obsolete' and fa.recordkey in (select recordkey from oa_product_bom_son son where son.code = '$mcode' or son.code = '$mcode1')";
                    $r = $materiel->getAdapter()->query($sql)->fetchObject();
                    $count = $r->sum;
                    if($count > 0) {
                        // 有bom在引用，不能修改为这几种状态
                        $result['success'] = false;
                        $result['result'] = false;
                        $result['info'] = "此物料有BOM在引用，不能修改为".$val->state."状态";

                        echo Zend_Json::encode($result);

                        exit;
                    }

                }
                $ismanager = "1";
                $data = array(
                    'type' => $val->type_id,
                    'state' => $val->state,
                    'project_no' => isset($val->project_no) ? $val->project_no : "",
                    'name' => $val->name,
                    'description' => $desc,
                    'remark' => $val->remark,
                    'update_time' => $now,
                    'data_file_id' => $val->data_file_id,
                    'first_report_id' => $val->first_report_id,
                    'tsr_id' => $val->tsr_id
                );
                if(isset($val->mcode) && $val->mcode) {
                    $data['code'] = $val->mcode;
                }
            } else {
                $data = array(
                    'type' => $val->type_id,
                    'project_no' => isset($val->project_no) ? $val->project_no : "",
                    'name' => $val->name,
                    'description' => $desc,
                    'remark' => $val->remark,
                    'state' => isset($state) ? 'Reviewing' : 'Active',
                    'update_time' => $now,
                    'data_file_id' => $val->data_file_id,
                    'first_report_id' => $val->first_report_id,
                    'tsr_id' => $val->tsr_id
                );
                if(isset($val->mcode) && $val->mcode) {
                    $data['code'] = $val->mcode;
                }
            }
            $id = $val->id;
            $where = "id = " . $id;
            try {
                if ($id) {
                    $materiel->update($data, $where);
                    /*
                      $attrval = new Admin_Model_Formval();
                      // 自定义字段
                      foreach($request as $field => $value) {
                      if(stripos($field, "intelligenceField") !== false) {
                      $attrId = str_replace("intelligenceField", "", $field);
                      $menu = 'oa_doc_files_'.$id;

                      $formval = array(
                      'attrid' => $attrId,
                      'value' => $value,
                      'menu' => $menu
                      );
                      $where = "attrid = ".$attrId." and menu = '".$menu."'";
                      if($attrval->fetchAll($where)->count() > 0) {
                      // 更新
                      $attrval->update($formval, $where);
                      } else {
                      $attrval->insert($formval);
                      }
                      }
                      }
                     */
                    // 操作记录
                    $data = array(
                        'type' => "materiel",
                        'table_name' => "oa_product_materiel",
                        'table_id' => $id,
                        'handle_user' => $user,
                        'handle_time' => $now,
                        'action' => "编辑",
                        'ip' => $_SERVER['REMOTE_ADDR']
                    );
                    $record->insert($data);

                    if (!$ismanager) {
                        // 审核流程
                        // 删除已存在的审核记录
                        $review->delete("type = 'materiel' and file_id = " . $id);
                        // 把阶段信息插入review记录
                        $first = true;
                        foreach ($stepRows as $s) {
                            $plan_user = $s['user'];
                            if ($s['dept']) {
                                $tmpUser = array();
                                $plan_dept = $s['dept'];
                                foreach (explode(',', $plan_dept) as $role) {
                                    $tmpRole = $member->getMemberWithNoManager($role);
                                    foreach ($tmpRole as $m) {
                                        $tmpUser[] = $m['user_id'];
                                    }
                                }
                                if (count($tmpUser) == 0 && !$plan_user) {
                                    $tmpUser = $member->getUserids("系统管理员");
                                }
                                if (count($tmpUser) > 0) {
                                    $tmpUser = $employee->getAdapter()->query("select group_concat(employee_id) as users from oa_user where active = 1 and id in ( " . implode(',', $tmpUser) . ")")->fetchObject();
                                    $users = $tmpUser->users;
                                }
                                if (isset($users) && $users) {
                                    if ($plan_user)
                                        $plan_user .= ",";
                                    $plan_user .= $users;
                                }
                            }
                            $repeatUser = explode(',', $plan_user);
                            $plan_user = array();
                            foreach ($repeatUser as $u) {
                                if ($u && !in_array($u, $plan_user)) {
                                    $plan_user[] = $u;
                                }
                            }
                            $plan_user = implode(',', $plan_user);

                            $reviewData = array(
                                'type' => "materiel",
                                'file_id' => $id,
                                'plan_user' => $plan_user,
                                'method' => $s['method'],
                                'return' => $s['return'],
                                'step_name' => $s['step_name'],
                                'step_ename' => $s['step_ename']
                            );
                            $review->insert($reviewData);
                        }
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
            // 检查物料代码是否已被使用
            if(isset($val->mcode) && $val->mcode) {
                $where = $materiel->getAdapter()->quoteInto("state!= 'Deleted' and state!= 'Return' and code = ?", $val->mcode);
                if($materiel->fetchAll($where)->count() > 0) {
                    $result['success'] = false;
                    $result['result'] = false;
                    $result['info'] = "物料代码已存在";
                    echo Zend_Json::encode($result);
                    exit;
                }
            }
            // 检查物料描述是不是已被使用
            $desc = trim($val->description);
            /*$where = $materiel->getAdapter()->quoteInto("state!= 'Deleted' and state!= 'Return' and description = ?", $desc);
            if(!$desc || $materiel->fetchAll($where)->count() > 0) {
                $result['success'] = false;
                $result['result'] = false;
                $result['info'] = "物料描述已存在";
                echo Zend_Json::encode($result);
                exit;
            }*/
            $data = array(
                'type' => $val->type_id,
                'name' => $val->name,
                'project_no' => isset($val->project_no) ? $val->project_no : "",
                'code' => isset($val->mcode) && $val->mcode ? $val->mcode : null,
                'description' => $desc,
                'remark' => $val->remark,
                'state' => isset($state) ? 'Reviewing' : 'Active',
                'create_user' => $user,
                'create_time' => $now,
                'update_time' => $now,
                'data_file_id' => $val->data_file_id,
                'first_report_id' => $val->first_report_id,
                'tsr_id' => $val->tsr_id
            );

            try {
                $id = $materiel->insert($data);
                if ($id) {
                    /*
                      // 自定义字段
                      $attrval = new Admin_Model_Formval();
                      foreach($request as $field => $value) {
                      if(stripos($field, "intelligenceField") !== false && $value) {
                      $attrId = str_replace("intelligenceField", "", $field);
                      $menu = 'oa_doc_files_'.$id;

                      $formval = array(
                      'attrid' => $attrId,
                      'value' => $value,
                      'menu' => $menu
                      );
                      $attrval->insert($formval);
                      }
                      }
                     */
                    // 操作记录
                    $data = array(
                        'type' => "materiel",
                        'table_name' => "oa_product_materiel",
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
                    if(count($stepRows) > 0) {
                        foreach ($stepRows as $s) {
                            $plan_user = $s['user'];
                            if ($s['dept']) {
                                $tmpUser = array();
                                $plan_dept = $s['dept'];
                                foreach (explode(',', $plan_dept) as $role) {
                                    $tmpRole = $member->getMemberWithNoManager($role);
                                    foreach ($tmpRole as $m) {
                                        $tmpUser[] = $m['user_id'];
                                    }
                                }
                                if (count($tmpUser) == 0 && !$plan_user) {
                                    $tmpUser = $member->getUserids("系统管理员");
                                }
                                if (count($tmpUser) > 0) {
                                    $tmpUser = $employee->getAdapter()->query("select group_concat(employee_id) as users from oa_user where active = 1 and id in ( " . implode(',', $tmpUser) . ")")->fetchObject();
                                    $users = $tmpUser->users;
                                }
                                if (isset($users) && $users) {
                                    if ($plan_user)
                                        $plan_user .= ",";
                                    $plan_user .= $users;
                                }
                            }
                            $repeatUser = explode(',', $plan_user);
                            $plan_user = array();
                            foreach ($repeatUser as $u) {
                                if ($u && !in_array($u, $plan_user)) {
                                    $plan_user[] = $u;
                                }
                            }
                            $plan_user = implode(',', $plan_user);
    
                            $reviewData = array(
                                'type' => "materiel",
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
                                $mailData = array(
                                    'type' => '物料号归档审批',
                                    'subject' => '物料号归档审批',
                                    'to' => $to->mail_to,
                                    'cc' => '',
                                    'content' => '你有新物料号归档申请需要审核，请登录系统查看详情',
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
                    } else {
                        // 自动生成物料编码
                        $code = $data['code'];
                        $materielData = $materiel->getById($id);
                        if(!$code) {
                            $code = $this->getCode($materielData->type, $materielData->project_no);
                        }
                        if (!$code) {
                            $result['result'] = false;
                            $result['info'] = "生成物料编码失败";
    
                            echo Zend_Json::encode($result);
    
                            exit;
                        } else {
                            $mData = array(
                                "state" => "Active",
                                "code" => $code,
                                "archive_time" => $now
                            );
                            $fileWhere = "id = $id";
                            // 如果有ds等文件，这些文件也设置为已归档
                            $data_file_id = $materielData->data_file_id;
                            $tsr_id = $materielData->tsr_id;
                            $first_report_id = $materielData->first_report_id;
                            if($data_file_id || $tsr_id || $first_report_id) {
                                $uploadUpdWhere = " archive = 0 and (1=0 ";
                                if($data_file_id) {
                                    $uploadUpdWhere .= " or id = ".$data_file_id;
                                }
                                if($tsr_id) {
                                    $uploadUpdWhere .= " or id = ".$tsr_id;
                                }
                                if($first_report_id) {
                                    $uploadUpdWhere .= " or id = ".$first_report_id;
                                }
                                $uploadUpdWhere .= ")";
                                $uploadUpdData = array(
                                    'archive' => 1,
                                    'archive_time' => $now
                                );
                            }
                            try {
                                // 更新文件
                                if (isset($fileWhere)) {
                                    $materiel->update($mData, $fileWhere);
                                }
                                // 更新上传文件
                                if (isset($uploadUpdData)) {
                                    $upload = new Dcc_Model_Upload();
                                    $upload->update($uploadUpdData, $uploadUpdWhere);
                                }
                                
                                $result['info'] .= "，物料代码：".$code;
                            } catch (Exception $e) {
                                $result['result'] = false;
                                $result['info'] = $e->getMessage();
                
                                echo Zend_Json::encode($result);
                
                                exit;
                            }
                        }
                    
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
     * @abstract    保存
     * @return      null
     */
    public function editAction() {
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

        $val = (object) $request;

        $materiel = new Product_Model_Materiel();
        $record = new Dcc_Model_Record();
        $review = new Dcc_Model_Review();
        $employee = new Hra_Model_Employee();
        $mail = new Application_Model_Log_Mail();
        $member = new Admin_Model_Member();

        // 根据物料类别获取审批流
        $typeId = $val->type_id;
        $stepRows = array();
        if ($typeId) {
            $type = new Product_Model_Type();
            // 获取当前物料类别对应的流程ID 如果找不到，继续往上搜索
            $flow_id = $type->getFlowId($typeId, 'new');

            if ($flow_id) {
                // 根据流程ID获取阶段
                $flow = new Admin_Model_Flow();
                $step = new Admin_Model_Step();

                $flowRow = $flow->getRow($flow_id);
                $step_ids = $flowRow['step_ids'];
                if ($step_ids) {
                    $stepRows = $step->getListByFlow($step_ids);
                    $state = "Reviewing";
                }
            }
        }

        // 新增还是编辑
        if (isset($val->id) && $val->id) {
            $result['info'] = '修改成功';
            $data = array(
                'type' => $val->type_id,
                'description' => $val->description,
                'remark' => $val->remark,
                'ver' => $val->ver,
                'unit' => $val->unit,
                'state' => isset($state) ? 'Reviewing' : 'Active',
                'manufacturers' => $val->manufacturers,
                'supply1' => $val->supply1,
                'supply2' => $val->supply2,
                'mpq' => $val->mpq,
                'moq' => $val->moq,
                'tod' => $val->tod,
                'data_file_id' => $val->data_file_id,
                'first_report_id' => $val->first_report_id
            );
            $id = $val->id;
            $where = "id = " . $id;
            try {
                if ($id) {
                    $materiel->update($data, $where);
                    /*
                      $attrval = new Admin_Model_Formval();
                      // 自定义字段
                      foreach($request as $field => $value) {
                      if(stripos($field, "intelligenceField") !== false) {
                      $attrId = str_replace("intelligenceField", "", $field);
                      $menu = 'oa_doc_files_'.$id;

                      $formval = array(
                      'attrid' => $attrId,
                      'value' => $value,
                      'menu' => $menu
                      );
                      $where = "attrid = ".$attrId." and menu = '".$menu."'";
                      if($attrval->fetchAll($where)->count() > 0) {
                      // 更新
                      $attrval->update($formval, $where);
                      } else {
                      $attrval->insert($formval);
                      }
                      }
                      }
                     */
                    // 操作记录
                    $data = array(
                        'type' => "materiel",
                        'table_name' => "oa_product_materiel",
                        'table_id' => $id,
                        'handle_user' => $user,
                        'handle_time' => $now,
                        'action' => "编辑",
                        'ip' => $_SERVER['REMOTE_ADDR']
                    );
                    $record->insert($data);

                    // 审核流程
                    // 删除已存在的审核记录
                    $review->delete("type = 'materiel' and file_id = " . $id);
                    // 把阶段信息插入review记录
                    $first = true;
                    foreach ($stepRows as $s) {
                        $plan_user = $s['user'];
                        if ($s['dept']) {
                            $tmpUser = array();
                            $plan_dept = $s['dept'];
                            foreach (explode(',', $plan_dept) as $role) {
                                $tmpRole = $member->getMemberWithNoManager($role);
                                foreach ($tmpRole as $m) {
                                    $tmpUser[] = $m['user_id'];
                                }
                            }
                            if (count($tmpUser) == 0 && !$plan_user) {
                                $tmpUser = $member->getUserids("系统管理员");
                            }
                            if (count($tmpUser) > 0) {
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
                            'type' => "materiel",
                            'file_id' => $id,
                            'plan_user' => $plan_user,
                            'method' => $s['method'],
                            'return' => $s['return'],
                            'step_name' => $s['step_name'],
                            'step_ename' => $s['step_ename']
                        );
                        $review->insert($reviewData);
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
                'type' => $val->type_id,
                'description' => $val->description,
                'remark' => $val->remark,
                'ver' => $val->ver,
                'unit' => $val->unit,
                'state' => isset($state) ? 'Reviewing' : 'Active',
                'manufacturers' => $val->manufacturers,
                'supply1' => $val->supply1,
                'supply2' => $val->supply2,
                'mpq' => $val->mpq,
                'moq' => $val->moq,
                'tod' => $val->tod,
                'data_file_id' => $val->data_file_id,
                'first_report_id' => $val->first_report_id,
                'create_user' => $user,
                'create_time' => $now
            );

            try {
                $id = $materiel->insert($data);
                if ($id) {
                    /*
                      // 自定义字段
                      $attrval = new Admin_Model_Formval();
                      foreach($request as $field => $value) {
                      if(stripos($field, "intelligenceField") !== false && $value) {
                      $attrId = str_replace("intelligenceField", "", $field);
                      $menu = 'oa_doc_files_'.$id;

                      $formval = array(
                      'attrid' => $attrId,
                      'value' => $value,
                      'menu' => $menu
                      );
                      $attrval->insert($formval);
                      }
                      }
                     */
                    // 操作记录
                    $data = array(
                        'type' => "materiel",
                        'table_name' => "oa_product_materiel",
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
                            $tmpUser = array();
                            $plan_dept = $s['dept'];
                            foreach (explode(',', $plan_dept) as $role) {
                                $tmpRole = $member->getMemberWithNoManager($role);
                                foreach ($tmpRole as $m) {
                                    $tmpUser[] = $m['user_id'];
                                }
                            }
                            if (count($tmpUser) == 0 && !$plan_user) {
                                $tmpUser = $member->getUserids("系统管理员");
                            }
                            if (count($tmpUser) > 0) {
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
                            'type' => "materiel",
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
                            $mailData = array(
                                'type' => '物料号归档审批',
                                'subject' => '物料号归档审批',
                                'to' => $to->mail_to,
                                'cc' => '',
                                'content' => '你有新物料号归档申请需要审核，请登录系统查看详情',
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
        }
    }

    /**
     * 检查是否只剩最后一人审批
     */
    public function checkfinishAction() {
        // 返回值数组
        $result = array(
            'success' => true,
            'result' => true,
            'info' => ''
        );

        $req = $this->getRequest()->getParams();
        $review = new Dcc_Model_Review();
        $where = "finish_flg=0";
        if (isset($req['id']) && $req['id']) {
            $id = $req['id'];
            $where .= " and file_id = $id";
        }

        $data = $review->getList($where, "materiel");
        if (count($data) == 1) {
            $method = $data[0]['method'];
            // 所有人审批
            if ($method == 1) {
                $actual_user = explode(',', $data[0]['actual_user']);
                $plan_user = explode(',', $data[0]['plan_user']);
                $diff = array_diff($plan_user, $actual_user);
                if (count($diff) > 1) {
                    $result['result'] = false;
                }
            }
        } else {
            $result['result'] = false;
        }
        // 转为json格式并输出
        echo Zend_Json::encode($result);

        exit;
    }

    /**
     * @abstract    审核
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

        $materiel = new Product_Model_Materiel();
        $record = new Dcc_Model_Record();
        $review = new Dcc_Model_Review();
        $employee = new Hra_Model_Employee();

        $id = $val->id;
        $remark = $val->remark1;
        $pass = $val->review_result;
        $publish = false;

        if (isset($val->code) && $val->code != '') {
            $code = $val->code;
            // 检查code是否重复
            if ($materiel->fetchAll("id != $id and code = '" . $code . "'")->count() > 0) {
                $result['result'] = false;
                $result['info'] = "代码“" . $code . "”已经存在";

                echo Zend_Json::encode($result);

                exit;
            }
        }

        if (isset($val->ids) && $val->ids && strpos($val->ids, ',') !== false) {
            // 多个
            $ids = explode(',', $val->ids);
        } else {
            $ids = array($id);
        }

        $newCodes = array();
        foreach ($ids as $id) {
            // 获取物料信息
            $materielData = $materiel->getOne($id);
            if (!$materielData) {
                $result['result'] = false;
                $result['info'] = "数据状态已改变";

                echo Zend_Json::encode($result);
                exit;
            }
            $review_id = $materielData->review_id;

            // 获取当前审核情况
            // 如果record记录被删除或状态已改变，报错
            $reviewWhere = "id = $review_id";
            $reviewRows = $review->getList($reviewWhere, "materiel");
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
                "type" => "materiel",
                "table_name" => "oa_product_materiel",
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
                    $planA = explode(',', $plan_user);
                    $actualA = explode(',', $actual_user);
                    $passFlg = true;
                    foreach ($planA as $u) {
                        if ($u && !in_array($u, $actualA)) {
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
                        (Application_Model_User::checkPermissionByRoleName('物料管理员') ||
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
                    $reviewWhere = "type = 'materiel' and file_id = $id";
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
                    $reviewWhere = "type = 'materiel' and finish_flg = 0 and file_id = $id";
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
                    $mData = array(
                        "state" => "Return"
                    );
                    // 退到初始状态
                    // 更新所有record的finish_flg为0
                    $reviewWhere = "type = 'materiel' and file_id = $id";
                    // 审核情况
                    $reviewData = array(
                        "actual_user" => $actual_user,
                        "finish_time" => $finish_time,
                        "finish_flg" => $finish_flg
                    );
                }
            }

            // 如果所有record的记录的finish_flg 都为1，则发布
            if ($finish_flg == 1 && $review->fetchAll("type = 'materiel' and finish_flg = 0 and file_id = $id")->count() == 1) {
                $publish = true;
                if (!isset($code) || !$code) {
                    // 自动生成物料编码
                    $code = $this->getCode($materielData->type, $materielData->project_no);
                    if (!$code) {
                        $result['result'] = false;
                        $result['info'] = "生成物料编码失败";

                        echo Zend_Json::encode($result);

                        exit;
                    } else {
                        if(count($ids) > 1) {
                            $newCodes[] = $code;
                            $result['info'] = "审批成功";
                        } else {
                            $result['info'] = "审批成功，系统分配物料号：$code";
                        }
                    }
                }
                $mData = array(
                    "state" => "Active",
                    "code" => $code,
                    "archive_time" => $now
                );
                $fileWhere = "id = $id";
                
                // 如果有ds等文件，这些文件也设置为已归档
                $data_file_id = $materielData->data_file_id;
                $tsr_id = $materielData->tsr_id;
                $first_report_id = $materielData->first_report_id;
                if($data_file_id || $tsr_id || $first_report_id) {
                    $uploadUpdWhere = " archive = 0 and (1=0 ";
                    if($data_file_id) {
                        $uploadUpdWhere .= " or id = ".$data_file_id;
                    }
                    if($tsr_id) {
                        $uploadUpdWhere .= " or id = ".$tsr_id;
                    }
                    if($first_report_id) {
                        $uploadUpdWhere .= " or id = ".$first_report_id;
                    }
                    $uploadUpdWhere .= ")";
                    $uploadUpdData = array(
                        'archive' => 1,
                        'archive_time' => $now
                    );
                }
            }

            try {
                // 更新审核情况
                $review->update($reviewData, $reviewWhere);
                // 更新文件
                if (isset($fileWhere)) {
                    $materiel->update($mData, $fileWhere);
                }
                // 更新上传文件
                if (isset($uploadUpdData)) {
                    $upload = new Dcc_Model_Upload();
                    $upload->update($uploadUpdData, $uploadUpdWhere);
                }
                $this->operate("物料评审");
            } catch (Exception $e) {
                $result['result'] = false;
                $result['info'] = $e->getMessage();

                echo Zend_Json::encode($result);

                exit;
            }

            // 邮件任务
            // 文件提交者或更新人
            $owner = $materielData['create_user'];
            $dev = false;
            $type = "物料申请";
            // 发邮件的情况：
            // 1、单站审核结束 $finish_flg = 1 && $publish = false
            if ($finish_flg == 1 && !$publish) {
                $subject = $type . "审批";
                // $to = 下一站审核人
                $current = $review->getFirstNoReview("materiel", $id);
                $to = $employee->getInfosByOneLine($current['plan_user']);
                //
                $cc = $employee->getInfosByOneLine($owner);
                $cc = $cc['email'];
                $content = "你有一个" . $type . "需要审批，请登录系统查看详情！";
            }

            // 2、所有审核结束  $publish = true
            if ($publish) {
                $subject = $type . "发布";
                $to = $employee->getInfosByOneLine($owner);
                $cc = $employee->getInfosByOneLine($record->getEmployeeIds($materielData['id'], 'materiel'));
                $cc = $cc['email'];
                $config = new Zend_Config_Ini(CONFIGS_PATH.'/application.ini', 'production');
                if(isset($config) && isset($config->email->apply->publish)) {
                    $to_plus = $config->email->apply->publish;
                    if($to_plus) {
                        if($cc) {
                            $cc .= ",".$to_plus;
                        } else {
                            $cc = $to_plus;
                        }
                    }
                }
//                 $cc = "";
                $his = "<p><b>审核记录：</b><br>" . str_replace(',','<br>',$record->getHis($materielData['id'], 'materiel'))."</p>";
                $content = "你申请的" . $type . "已通过审批。<p><b>物料编码：</b>" . $code . "</p><p><b>类别：</b>" . $this->getTypeByConnect($materielData['type'], '') . "</p><p><b>名称：</b>" . $materielData['name'] . "</p><p><b>描述：</b>" . $materielData['description'] . "</p><p><b>申请人：</b>" . $materielData['creater'] . "</p><p><b>申请时间：</b>" . $materielData['create_time'] . "</p>".$his."<p>请登录系统查看详情！</p>";
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
                $content = "你申请的" . $type . "已被退回，<p><b>退回原因：</b>" . $remark . "</p>，请登录系统查看详情！";
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
                $content = "有新的" . $type . "被转移到你处审批，请登录系统查看详情！";
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
            $code = null;
        }
        if(count($newCodes) > 0) {
            $result['info'] .= '，系统分配物料号分别为：'.implode(',', $newCodes);
        }

        echo Zend_Json::encode($result);

        exit;
    }

    /**
     * @abstract    根据类别编码和流水号生成最新文件编码
     * @return      null
     */
    private function getCode($id, $proj) {
        $type = new Product_Model_Type();
        $materiel = new Product_Model_Materiel();
        // 获取流水号长度
        $data = $type->fetchRow("id = $id");
        $sn_length = $data->sn_length;
        if ($proj) {
            // 从产品清单获取内部型号
            $projData = $materiel->getAdapter()->query("select model_internal from oa_product_catalog where id = '$proj'")->fetchObject();
            $code = trim($projData->model_internal) . "-";
        } else {
            $code = $this->getTypeCodeByConnect($id);
        }
        if ($code && $code != "-" && $sn_length) {

            $like = "";
            for ($i = 0; $i < $sn_length; $i++) {
                $like .= "_";
            }
            $reg = $code."[0-9]"."{".$sn_length."}";
            $mData = $materiel->getAdapter()->query("select max(code) as maxcode from oa_product_materiel where code regexp '$reg'")->fetchObject();
            $num = "";
            if ($mData && $mData->maxcode) {
                $max = $mData->maxcode;
                $num = str_replace($code, '', $max);
                if (strlen($num) != $sn_length) {
                    $num = "";
                    for ($i = 0; $i < $sn_length; $i++) {
                        $num .= "0";
                    }
                }
            } else {
                for ($i = 0; $i < $sn_length; $i++) {
                    $num .= "0";
                }
            }
            // +1
            $code .= substr((("1" . $num) + 1), 1);
            return $code;
        } else {
            return trim($code, '-');
        }
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
            'target' => 'materiel',
            'computer_name' => $computer_name,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'time' => $now
        );

        $operate->insert($data);
    }

    /**
     * @abstract    删除
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

        $materiel = new Product_Model_Materiel();
        $record = new Dcc_Model_Record();
        $review = new Dcc_Model_Review();

        if (count($deleted) > 0) {
            foreach ($deleted as $val) {
                $id = $val->id;
                // 操作记录
                $data = array(
                    'type' => "materiel",
                    'table_name' => "oa_product_materiel",
                    'table_id' => $id,
                    'handle_user' => $user,
                    'handle_time' => $now,
                    'action' => "删除",
                    'ip' => $_SERVER['REMOTE_ADDR']
                );
                try {
                    // 增加record记录
                    $record->insert($data);
                    // 删除review记录
                    $review->delete("type = 'materiel' and file_id = $id");
                    // 更新物料状态
                    $materiel->update(array('state' => 'Deleted'), "id = $id");
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

