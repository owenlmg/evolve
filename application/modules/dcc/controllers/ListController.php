<?php

/**
 * 2014-8-2
 * @author      mg.luo
 * @abstract      文件清单
 */
class Dcc_ListController extends Zend_Controller_Action {

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
        $where = "1=1";
        foreach ($request as $k => $v) {
            if ($v) {
                if ($k == 'search_tag') {
                    $where .= " and (ifnull(t1.project_info,'') like '%$v%' or ifnull(t1.code,'') like '%$v%' or ifnull(t1.name,'') like '%$v%' or ifnull(t1.description,'') like '%$v%' or ifnull(t1.remark,'') like '%$v%' or ifnull(t2.cname,'') like '%$v%')";
                } else if ("search_category" == $k && $v) {
                    $where .= " and t5.category = '$v'";
                } else if ("search_archive_date_from" == $k && $v) {
                    $where .= " and t1.archive_time >= '" . str_replace('T', ' ', $v) . "'";
                } else if ("search_archive_date_to" == $k && $v) {
                    $where .= " and t1.archive_time <= '" . str_replace('T00:00:00', ' 23:59:59', $v) . "'";
                } else {
                    $col = str_replace('search_', '', $k);
                    if ($col != $k) {
                        // 查询条件
                        $where .= " and ifnull(t1." . $col . ",'') like '%" . $v . "%'";
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

        if (isset($request['method'])) {
            $data = $files->getFilesListForEdit($where, $start, $limit);
            $count = $files->getCountForEdit($where, $start, $limit);
            $totalCount = $count;
        } else {
            $data = $files->getFilesList($where . " and (t1.state = 'Active' or t1.state = 'Obsolete')", $start, $limit);
            $count = $files->getCount($where . " and (t1.state = 'Active' or t1.state = 'Obsolete')", $start, $limit);
            $totalCount = $count;
        }

        for ($i = 0; $i < count($data); $i++) {
            $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
            $data[$i]['update_time'] = strtotime($data[$i]['update_time']);
            $data[$i]['archive_time'] = strtotime($data[$i]['archive_time']);
            $data[$i]['codever'] = $data[$i]['code'] . ' V' . $data[$i]['ver'];
            $data[$i]['send_require'] = $data[$i]['send_require'] == 1 ? true : false;
            $data[$i]['description'] = $data[$i]['code_description'];

            // 增加审核状态
            $reviewState = "";
            $step_name = "";
            if ($data[$i]['state'] == 'Active') {
                $reviewState = "已发布";
            } else if ($data[$i]['state'] == 'Reviewing') {
                // 查询当前审核状态
                // 查询所有审核阶段
                $reviewRows = $review->getList("file_id = " . $data[$i]['id'], "files");
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
                                $plan_dept = $reviewRow['plan_dept'];
                                $depts = array();
                                $plan_user = explode(',', $planUser);
                                $diff = array_diff($plan_user, $actual_user);

                                foreach ($diff as $u) {
                                    if (!$u)
                                        continue;
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
            } else if ($data[$i]['state'] == 'Obsolete') {
                $reviewState = "已作废";
            } else if ($data[$i]['state'] == 'Return') {
                $reviewState = "退回";
            } else {
                $reviewState = $data[$i]['state'];
            }
            $data[$i]['step_name'] = $step_name;
            $data[$i]['review_state'] = $reviewState;
            if($data[$i]['reason_type']) {
                $masterData = $codemaster->fetchRow("type = 5 and code = '".$data[$i]['reason_type']."'");
                $reason_type_name = $masterData->text;
                $data[$i]['reason_type_name'] = $reason_type_name;
            }
        }
        $resutl = array(
            "totalCount" => $totalCount,
            "topics" => $data
        );
        echo Zend_Json::encode($resutl);

        exit;
    }

    /**
     * @abstract    获取文件的版本JSON数据
     * @return      null
     */
    public function verAction() {
        // 返回值数组
        $result = array(
            'success' => true,
            'ver' => '',
            'prefix' => ''
        );
        // 获取参数
        $request = $this->getRequest()->getParams();
        $file_code = $request['code'];

        $files = new Dcc_Model_Files();

        $data = $files->getVer($file_code);
        if (!$data) {
            $result['ver'] = '1.0';
        } else {
            $result['ver'] = $data['ver'] + 0.1;
        }

        echo Zend_Json::encode($result);

        exit;
    }

    /**
     * @abstract    获取自定义表单内容
     * @return      null
     */
    public function getformvalAction() {
        // 获取参数
        $request = $this->getRequest()->getParams();
        $menu = $request['menu'];

        $formval = new Admin_Model_Formval();

        $data = $formval->getListByMenu($menu);

        echo Zend_Json::encode($data);

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
            'info' => '删除成功'
        );

        $request = $this->getRequest()->getParams();

        $now = date('Y-m-d H:i:s');
        $user = 1; // 需替换为当前用户ID

        $json = json_decode($request['json']);

        $deleted = $json->deleted;

        $files = new Dcc_Model_Files();

        if (count($deleted) > 0) {
            foreach ($deleted as $val) {
                if(!$val->id) continue;
                $row = $files->getOne($val->id);
                try {
                    if($row['state'] == 'Reviewing' || $row['state'] == 'Return') {
                        $files->delete("id = " . $val->id);
                    } else {
                        $data = array(
                            'state' => 'Deleted',
                            'update_time' => $now,
                            'update_user' => $user
                        );
                        $files->update($data, "id = " . $val->id);
                    }
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
     * @abstract    添加新文件
     * @return      null
     */
    public function saveAction() {
        // 返回值数组
        $result = array(
            'success' => true,
            'result' => true,
            'info' => '添加成功'
        );

        $request = $this->getRequest()->getParams();

        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];

        $val = (object) $request;

        $files = new Dcc_Model_Files();
        $review = new Dcc_Model_Review();
        $code = new Dcc_Model_Code();
        $upgrade = new Dcc_Model_Upgrade();

        // 新增还是编辑
        if ($val->id) {
            // 判断此次版本是否已存在
            if ($files->fetchAll("id != $val->id and ver = '" . $val->ver . "' and code = '" . $val->code . "'")->count() > 0) {
                $result['result'] = false;
                $result['info'] = "文件“" . $val->code . "”的" . $val->ver . "版本已经存在";

                echo Zend_Json::encode($result);

                exit;
            }
            $result['info'] = '修改成功';
            $data = array(
                'state' => $val->state,
                'code' => $val->code,
                'ver' => $val->ver,
                'name' => $val->name,
                'file_ids' => $val->file_ids,
                'description' => $val->description,
                'project_info' => $val->project_info,
                'send_require' => isset($val->send_require) ? 1 : 0,
                'remark' => $val->remark,
                'tag' => $val->tag,
                'archive_time' => $val->archive_time,
                'update_time' => $now,
                'update_user' => $user
            );
            $id = $val->id;
            $where = "id = " . $id;
            $up = false;
            if($val->ver && $val->ver > 1.0) {
                $up = true;
                // 修改升版记录
                $upgradeData = array(
                    'reason' => $val->reason,
                    'reason_type' => $val->reason_type,
                    'update_time' => $now,
                    'update_user' => $user
                );
                $upgradeWhere = "file_id = $id";
            }
            // 更新旧版文件的状态为已作废
            if ($val->ver != '1.0' && $val->state == 'Active') {
                $obsoluteWhere = "ver < '" . $val->ver . "' and code = '" . $val->code . "'";
                $obsoluteData = array(
                    "state" => "Obsolete"
                );
            }
            $projectData = array("project_no" => $val->project_no);
            $projectWhere = "code= '".$val->code."'";
            try {
                if (isset($obsoluteData) && isset($obsoluteWhere)) {
                    $files->update($obsoluteData, $obsoluteWhere);
                }
                if($up) {
                    $upgrade->update($upgradeData, $upgradeWhere);
                }
                $files->update($data, $where);
                $code->update($projectData, $projectWhere);
                if ($val->state == 'Active' && isset($val->file_ids) && $val->file_ids != "") {
                    // 更新文件状态为已归档
                    $uploadData = array(
                        "archive" => 1,
                        "archive_time" => $now
                    );
                    $upload = new Dcc_Model_Upload();
                    // 获取上传文件id
                    $ids = $val->file_ids;
                    $uploadWhere = "id in ($ids)";
                    $upload->update($uploadData, $uploadWhere);
                }
                if ($val->state == 'Return') {
                    // 更改review状态
                    // 需更新的审核记录: 所有
                    $reviewWhere = "type = 'files' and file_id = $id";
                    // 审核情况更新数据
                    $reviewData = array(
                        "actual_user" => "",
                        "finish_time" => null,
                        "finish_flg" => 0
                    );
                    $review->update($reviewData, $reviewWhere);
                }

                if ($id) {
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
                    $record = new Dcc_Model_Record();
                    // 操作记录
                    $data = array(
                        'type' => "files",
                        'table_name' => "oa_doc_files",
                        'table_id' => $id,
                        'handle_user' => $user,
                        'handle_time' => $now,
                        'action' => "编辑",
                        'ip' => $_SERVER['REMOTE_ADDR'],
                        'remark' => isset($request['edit_info']) ? $request['edit_info'] : ''
                    );
                    $record->insert($data);
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
            // 判断此次版本是否已存在
            if ($files->fetchAll("ver = " . $val->ver . " and code = '" . $val->code . "'")->count() > 0) {
                $result['result'] = false;
                $result['info'] = "文件“" . $val->code . "”的" . $val->ver . "版本已经存在";

                echo Zend_Json::encode($result);

                exit;
            }
            $data = array(
                'state' => $val->state,
                'code' => $val->code,
                'ver' => $val->ver,
                'name' => $val->name,
                'file_ids' => $val->file_ids,
                'description' => $val->description,
                'project_info' => $val->project_info,
                'send_require' => isset($val->send_require) ? 1 : 0,
                'remark' => $val->remark,
                'tag' => $val->tag,
                'archive_time' => $val->archive_time,
                'create_time' => $now,
                'create_user' => $user,
                'update_time' => $now,
                'update_user' => $user
            );
            // 更新旧版文件的状态为已作废
            if ($val->ver != '1.0' && $val->state == 'Active') {
                $obsoluteWhere = "ver < '" . $val->ver . "' and code = '" . $val->code . "'";
                $obsoluteData = array(
                    "state" => "Obsolete"
                );
            }

            try {
                $id = $files->insert($data);
                if($id && $val->ver && $val->ver > 1.0) {
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
                }
                if (isset($obsoluteData) && isset($obsoluteWhere)) {
                    $files->update($obsoluteData, $obsoluteWhere);
                }
                if(isset($val->file_ids) && $val->file_ids != "") {
                    // 更新文件状态为已归档
                    $uploadData = array(
                        "archive" => 1
                    );
                    $upload = new Dcc_Model_Upload();
                    // 获取上传文件id
                    $ids = $val->file_ids;
                    $uploadWhere = "id in ($ids)";
                    $upload->update($uploadData, $uploadWhere);
                }

                if ($id) {
                    $attrval = new Admin_Model_Formval();
                    // 自定义字段
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
                    $record = new Dcc_Model_Record();
                    // 操作记录
                    $data = array(
                        'type' => "files",
                        'table_name' => "oa_doc_files",
                        'table_id' => $id,
                        'handle_user' => $user,
                        'handle_time' => $now,
                        'action' => "新增",
                        'ip' => $_SERVER['REMOTE_ADDR']
                    );
                    $record->insert($data);
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

    // 导出CSV
    public function exportcsvAction() {
        $request = $this->getRequest()->getParams();
//        if(isset($request['source']) && $request['source'] == 'list') {
//            $whereSearch = "t1.state != 'Reviewing' and t1.state != 'Return' ";
//        } else {
//            $whereSearch = "1=1";
//        }
        $where = "1=1";
        foreach ($request as $k => $v) {
            if ($v) {
                if ($k == 'search_tag') {
                    $where .= " and (t1.tag like '%$v%' or t1.project_info like '%$v%' or t1.code like '%$v%' or t1.name like '%$v%' or t1.description like '%$v%' or t1.remark like '%$v%' or t2.cname like '%$v%')";
                } else if ("search_category" == $k && $v) {
                    $where .= " and t5.category = '$v'";
                } else if ("search_archive_date_from" == $k && $v) {
                    $where .= " and t1.archive_time >= '" . str_replace('T', ' ', $v) . "'";
                } else if ("search_archive_date_to" == $k && $v) {
                    $where .= " and t1.archive_time <= '" . str_replace('T00:00:00', ' 23:59:59', $v) . "'";
                } else {
                    $col = str_replace('search_', '', $k);
                    if ($col != $k) {
                        // 查询条件
                        $where .= " and t1." . $col . " like '%" . $v . "%'";
                    }
                }
            }
        }

        $files = new Dcc_Model_Files();
        $record = new Dcc_Model_Record();
        // 获取物料数据
        $data = $files->getFilesListForEdit($where, null, null);
        $data_csv = array();
        $title = array('#', 'ID', '文件简号', '简号中文解释', '文件类别', '文件号', '版本', '文件名', '状态', '产品型号', '描述', '更改原因类型', '更改描述', '备注', '归档时间', '申请人', '申请时间', '自定义信息');

        $title = $this->object_array($title);
        $date = date('YmdHsi');
        $filename = "files_list" . $date;
        $path = "../temp/" . $filename . ".csv";
        $file = fopen($path, "w");

        $k = 0;
        for ($i = 0; $i < count($data); $i++) {
            $d = $data[$i];
            $k++;

            $info = array(
                'cnt' => $k,
                'id' => $d['id'],
                'prefix' => $d['type_code'],
                'type_name' => $d['type_name'],
                'category_name' => $d['category_name'],
                'code' => $d['code'],
                'ver' => 'V' . $d['ver'],
                'name' => $d['name'],
                'state' => $d['state'],
                'project_name' => $d['project_name'],
                'description' => $d['description'],
                'reason_type' => $d['reason_type_name'],
                'reason' => $d['reason'],
                'remark' => $d['remark'],
                'archive_time' => $d['archive_time'],
                'creater' => $d['creater'],
                'create_time' => $d['create_time']
            );
            
            // 获取自定义信息
            $menu = 'oa_doc_files_'.$d['id'];
            $form = new Admin_Model_Form();
            $dataform = $form->getAttrAndValByMenu($menu);
            foreach($dataform as $row) {
                $name = $row['name'];
                $value = $row['value'];
                $info[] = "[$name]:[$value]";
            }
            

            $d = $this->object_array($info);
            if($i == 0) {
                fputcsv($file, $title);
                array_push($data_csv, $title);
            }
            array_push($data_csv, $info);
            fputcsv($file, $d);
        }

        fclose($file);
        $this->operate("文件导出");

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
            $a[$key] = iconv('utf-8', 'GBK//TRANSLIT', $v);
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
            'target' => 'Materiel',
            'computer_name' => $computer_name,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'time' => $now
        );

        $operate->insert($data);
    }

}