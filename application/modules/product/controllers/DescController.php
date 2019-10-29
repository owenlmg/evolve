<?php
/**
 * 2013-9-8
 * @author      mg.luo
 * @abstract    物料编码申请
 */
class Product_DescController extends Zend_Controller_Action
{

    public function indexAction()
    {

    }

    public function getlistAction() {
        $request = $this->getRequest()->getParams();
        $limit = $request['limit'];
        $start = $request['start'];

        $desc = new Product_Model_Desc();
        $descName = $desc->getName();
        $whereSearch = "1=1";
        $mytype = $this->getRequest()->getParam('mytype');
        if($mytype == 3) {
            $whereSearch = "$descName.state = 'Reviewing'";
        }
        foreach ($request as $k => $v) {
            if ($v) {
            	if($k == 'search_tag') {
            		$whereSearch .= " and (ifnull($descName.remark,'') like '%$v%' or ifnull($descName.manufacturers_before,'') like '%$v%' or ifnull($descName.manufacturers_after,'') like '%$v%' or ifnull($descName.desc_before,'') like '%$v%' or ifnull($descName.desc_after,'') like '%$v%')";
            	} else {
	                $col = str_replace('search_', '', $k);
	                if ($col != $k) {
	                    // 查询条件
	                    $whereSearch .= " and ifnull($descName." . $col . ",'') like '%" . $v . "%'";
	                }
            	}
            }
        }

        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];

        $materiel = new Product_Model_Materiel();
        $type = new Product_Model_Type();
        $record = new Dcc_Model_Record();
        $flow = new Admin_Model_Flow();
        $step = new Admin_Model_Step();
        $review = new Dcc_Model_Review();
        $employee = new Hra_Model_Employee();

        // 查询条件

        // 供应商
        $supply = new Product_Model_Bpartner();
        $supplyData = $supply->getJoinList(array(), array(), array('id', 'code', 'cname', 'ename'));
        // 审核中
        $reviewingData = $review->getJoinList("finish_flg = 0 and type = 'materiel_desc' and (actual_user is null or !(FIND_IN_SET($user,actual_user))) and (FIND_IN_SET($user, plan_user))", array(), array('table_id'));
        $reviewingIds = array();
        foreach($reviewingData as $r) {
            if(!in_array($r['id'], $reviewingIds)) {
                $reviewingIds[] = $r['id'];
            }
        }
        // 审核过
        $reviewedData = $review->getJoinList("finish_flg = 1 and type = 'materiel_desc' and (FIND_IN_SET($user,actual_user))", array(), array('table_id'));
        $reviewedIds = array();
        foreach($reviewedData as $r) {
            if(!in_array($r['id'], $reviewedIds)) {
                $reviewedIds[] = $r['id'];
            }
        }
        $join = array(
            array(
                'type' => INNERJOIN,
                'table' => $employee->getName(),
                'condition' => $employee->getName().'.id = '.$desc->getName().'.create_user'
            )
        );
        $myType = "";
        if (isset($request['mytype'])) {
            $myType = $request['mytype'];
        }
        if($myType == 1) {
            $whereSearch .= " and $descName.create_user = $user";
        } else if($myType == 2 && count($reviewedIds) > 0) {
            $whereSearch .= " and $descName.id in (".implode(',',$reviewedIds).")";
        } else if($myType == 3 && count($reviewingIds) > 0) {
            $whereSearch .= " and $descName.id in (".implode(',',$reviewingIds).")";
        } else {
//            $whereSearch .= " and ($descName.create_user = $user ";
            $whereSearch .= " and (1=1 ";
            if(count($reviewedIds) > 0) {
                $whereSearch .= " or $descName.id in (".implode(',',$reviewedIds).")";
            }
            if(count($reviewingIds) > 0) {
                $whereSearch .= " or $descName.id in (".implode(',',$reviewingIds).")";
            }
            $whereSearch .= ")";
        }
        $total = $desc->getJoinCount($whereSearch);
        if($total > 0) {
            $join = array(
                array(
                    'type' => INNERJOIN,
                    'table' => $employee->getName(),
                    'condition' => $employee->getName().'.id = '.$desc->getName().'.create_user',
                    'cols' => array('creater' => 'cname')
                )
            );
            $data = $desc->getJoinList($whereSearch, $join, null, array($desc->getName().'.state desc',$desc->getName().'.create_time desc'), $start, $limit);
        } else {
            $data = array();
        }

        $fileIds = array();
        // 获取物料数据
        for($i = 0; $i < count($data); $i++) {
        	$mytype = 2;
        	if($data[$i]['create_user'] == $user) {
        		$mytype = 1;
        	}
            if(($typeId = $data[$i]['type_before']) != '') {
                $typeName = $type->getTypeByConnect($typeId, '');
                $data[$i]['type_name'] = $typeName;
            }
            $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
            $data[$i]['archive_time'] = strtotime($data[$i]['archive_time']);

            // files
            if($data[$i]['data_file_id_before'] && !in_array($data[$i]['data_file_id_before'], $fileIds)) {
                $fileIds[] = $data[$i]['data_file_id_before'];
            }
            if($data[$i]['data_file_id_after'] && !in_array($data[$i]['data_file_id_after'], $fileIds)) {
                $fileIds[] = $data[$i]['data_file_id_after'];
            }
            if($data[$i]['tsr_id_before'] && !in_array($data[$i]['tsr_id_before'], $fileIds)) {
                $fileIds[] = $data[$i]['tsr_id_before'];
            }
            if($data[$i]['tsr_id_after'] && !in_array($data[$i]['tsr_id_after'], $fileIds)) {
                $fileIds[] = $data[$i]['tsr_id_after'];
            }
            if($data[$i]['first_report_id_before'] && !in_array($data[$i]['first_report_id_before'], $fileIds)) {
                $fileIds[] = $data[$i]['first_report_id_before'];
            }
            if($data[$i]['first_report_id_after'] && !in_array($data[$i]['first_report_id_after'], $fileIds)) {
                $fileIds[] = $data[$i]['first_report_id_after'];
            }

            // 增加审核状态
            $reviewState = "";
            $step_name = "";
            if ($data[$i]['state'] == 'Active') {
                $reviewState = "已归档";
            } else if ($data[$i]['state'] == 'Reviewing') {
                // 查询当前审核状态
                // 查询所有审核阶段
                $reviewRows = $review->getList("file_id = " . $data[$i]['id'], "materiel_desc");
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
                                    if($u == $user) {
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

            $data[$i]['record'] = $record->getHis($data[$i]['id'], 'materiel_desc');
        }
        // get file names
        if(count($fileIds) > 0) {
            $upload = new Dcc_Model_Upload();
            $fileData = $upload->getJoinList("id in (".implode(',', $fileIds).")", array(), array('id', 'name'));
            $files = array();
            foreach($fileData as $f) {
                $files[$f['id']] = $f['name'];
            }
            // data rebuild
            for($i = 0; $i < count($data); $i++) {
                if($data[$i]['data_file_id_before'] && in_array($data[$i]['data_file_id_before'], $fileIds)) {
                    $data[$i]['data_file_before'] = $files[$data[$i]['data_file_id_before']];
                }
                if($data[$i]['data_file_id_after'] && in_array($data[$i]['data_file_id_after'], $fileIds) && isset($files[$data[$i]['data_file_id_after']])) {
                    $data[$i]['data_file_after'] = $files[$data[$i]['data_file_id_after']];
                }
                if($data[$i]['tsr_id_before'] && in_array($data[$i]['tsr_id_before'], $fileIds)) {
                    $data[$i]['tsr_before'] = $files[$data[$i]['tsr_id_before']];
                }
                if($data[$i]['tsr_id_after'] && in_array($data[$i]['tsr_id_after'], $fileIds)) {
                    $data[$i]['tsr_after'] = $files[$data[$i]['tsr_id_after']];
                }
                if($data[$i]['first_report_id_before'] && in_array($data[$i]['first_report_id_before'], $fileIds)) {
                    $data[$i]['first_report_before'] = $files[$data[$i]['first_report_id_before']];
                }
                if($data[$i]['first_report_id_after'] && in_array($data[$i]['first_report_id_after'], $fileIds)) {
                    $data[$i]['first_report_after'] = $files[$data[$i]['first_report_id_after']];
                }
            }
        }
        // 转为json格式并输出
        $resutl = array(
            "totalCount" => $total,
            "topics" => $data
        );
        echo Zend_Json::encode($resutl);

        exit;
    }

    public function getoneAction() {
        $request = $this->getRequest()->getParams();
        $data = "";
        if(isset($request['id']) && $request['id']) {
        	$id = $request['id'];
        	$materiel = new Product_Model_Materiel();
        	$data = $materiel->getById($id);
        }
        // 转为json格式并输出
        echo Zend_Json::encode($data);

        exit;
    }

    /**
     * @abstract    保存
     * @return      null
     */
    public function saveAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'result'    => true,
                'info'      => '保存成功'
        );

        $request = $this->getRequest()->getParams();

        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];

        $val = (object)$request;

        $materiel = new Product_Model_Materiel();
        $record = new Dcc_Model_Record();
        $review = new Dcc_Model_Review();
        $employee = new Hra_Model_Employee();
        $mail = new Application_Model_Log_Mail();
        $member = new Admin_Model_Member();
        $desc = new Product_Model_Desc();

        if(isset($val->mid) && $val->mid) {
        	$mid = $val->mid;
        	$md = $materiel->fetchRow("id = $mid");
        } else {
            $result['result'] = false;
            $result['info'] = "数据加载失败";

            echo Zend_Json::encode($result);
            exit;
        }
        // 根据物料类别获取审批流
        $typeId = $md->type;
        $stepRows = array();
        if($typeId) {
            $type = new Product_Model_Type();
            // 获取当前物料类别对应的流程ID 如果找不到，继续往上搜索
            $flow_id = $type->getFlowId($typeId, 'upd');

            if($flow_id) {
                // 根据流程ID获取阶段
                $flow = new Admin_Model_Flow();
                $step = new Admin_Model_Step();

                $flowRow = $flow->getRow($flow_id);
                $step_ids = $flowRow['step_ids'];
                if($step_ids) {
                    $stepRows = $step->getListByFlow($step_ids);
                    $state = "Reviewing";
                }
            }
        }

        // 新增还是编辑
        if(isset($val->id) && $val->id) {
            $result['info'] = '修改成功';
            $data = array(
                    'mid'          => $mid,
                    'code'        => $md->code,
                    'state'        => 'Reviewing',
                    'type_before'   => $md->type,
                    'type_after'        => $md->type,
                    'desc_before'           => $md->description,
                    'desc_after'          => $val->desc_after,
                    'supply1_before'        => $md->supply1,
                    'supply1_after' => $val->supply1_after,
                    'supply2_before'     => $md->supply2,
                    'supply2_after'     => $val->supply2_after,
                    'manufacturers_before'           => $md->manufacturers,
                    'manufacturers_after'           => $val->manufacturers_after,
                    'data_file_id_before'           => $md->data_file_id,
                    'data_file_id_after'           => $val->data_file_id_after,
                    'tsr_id_before'           => $md->tsr_id,
                    'tsr_id_after'           => $val->tsr_id_after,
                    'first_report_id_before'           => $md->first_report_id,
                    'first_report_id_after'           => $val->first_report_id_after,
                    'name_before'   => $md->name,
                    'name_after'        => $val->name_after,
                    'remark'           => $val->remark,
                    'create_user'      => $user,
                    'create_time'   => $now
            );
            $id = $val->id;
            $where = "id = ".$id;
            try{
                if($id) {
                    $desc->update($data, $where);
                    // 操作记录
                    $data = array(
                            'type'             => "materiel_desc",
                            'table_name'       => "oa_product_materiel_desc",
                            'table_id'         => $id,
                            'handle_user'      => $user,
                            'handle_time'      => $now,
                            'action'           => "编辑",
                            'ip'               => $_SERVER['REMOTE_ADDR']
                    );
                    $record->insert($data);

                    // 审核流程
                    // 删除已存在的审核记录
                    $review->delete("type = 'materiel_desc' and file_id = ".$id);
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
                            'type' => "materiel_desc",
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
            } catch (Exception $e){
                $result['result'] = false;
                $result['info'] = $e->getMessage();

                echo Zend_Json::encode($result);

                exit;
            }

            echo Zend_Json::encode($result);

            exit;
        } else {
            $data = array(
                    'mid'          => $mid,
                    'code'        => $md->code,
                    'state'        => 'Reviewing',
                    'ver_before'   => $md->ver,
                    'ver_after'   => ($md->ver + 0.1),
                    'type_before'   => $md->type,
                    'type_after'        => $md->type,
                    'desc_before'           => $md->description,
                    'desc_after'          => $val->desc_after,
                    'supply1_before'        => $md->supply1,
                    'supply1_after' => $val->supply1_after,
                    'supply2_before'     => $md->supply2,
                    'supply2_after'     => $val->supply2_after,
                    'manufacturers_before'           => $md->manufacturers,
                    'manufacturers_after'           => $val->manufacturers_after,
                    'data_file_id_before'           => $md->data_file_id,
                    'data_file_id_after'           => $val->data_file_id_after,
                    'tsr_id_before'           => $md->tsr_id,
                    'tsr_id_after'           => $val->tsr_id_after,
                    'first_report_id_before'           => $md->first_report_id,
                    'first_report_id_after'           => $val->first_report_id_after,
                    'name_before'   => $md->name,
                    'name_after'        => $val->name_after,
                    'remark'           => $val->remark,
                    'create_user'      => $user,
                    'create_time'   => $now
            );

            try{
                $id = $desc->insert($data);
                if($id) {
                    // 操作记录
                    $data = array(
                            'type'             => "materiel_desc",
                            'table_name'       => "oa_product_materiel_desc",
                            'table_id'         => $id,
                            'handle_user'      => $user,
                            'handle_time'      => $now,
                            'action'           => "申请",
                            'ip'               => $_SERVER['REMOTE_ADDR']
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
                            'type' => "materiel_desc",
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
                                'type' => '物料号变更审批',
                                'subject' => '物料号变更审批',
                                'to' => $to->mail_to,
                                'cc' => '',
                                'content' => '你有新物料号变更申请(物料代码：'.$md["code"].')需要审核，请登录系统查看详情',
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
            } catch (Exception $e){
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
                'success'   => true,
                'result'    => true,
                'info'      => ''
        );

        $req = $this->getRequest()->getParams();
        $review = new Dcc_Model_Review();
        $where = "finish_flg=0";
        if(isset($req['id']) && $req['id']) {
        	$id = $req['id'];
            $where .= " and file_id = $id";
        }

        $data = $review->getList($where, "materiel_desc");
        if(count($data) == 1) {
        	$method = $data[0]['method'];
        	// 所有人审批
        	if($method == 1) {
                $actual_user = explode(',', $data[0]['actual_user']);
                $plan_user = explode(',', $data[0]['plan_user']);
                $diff = array_diff($plan_user, $actual_user);
                if(count($diff) > 1) {
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
        $desc = new Product_Model_Desc();

        $id = $val->id;
        $remark = $val->remark1;
        $pass = $val->review_result;
        $publish = false;

        if (isset($val->ids) && $val->ids && strpos($val->ids, ',') !== false) {
            // 多个
            $ids = explode(',', $val->ids);
        } else {
            $ids = array($id);
        }
        foreach($ids as $id) {
            // 获取物料信息
            $materielData = $desc->getOne($id);
            if(!$materielData) {
                $result['result'] = false;
                $result['info'] = "数据状态已改变";
    
                echo Zend_Json::encode($result);
                exit;
            }
            $review_id = $materielData->review_id;
    
            // 获取当前审核情况
            // 如果record记录被删除或状态已改变，报错
            $reviewWhere = "id = $review_id";
            $reviewRows = $review->getList($reviewWhere, "materiel_desc");
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
                "type" => "materiel_desc",
                "table_name" => "oa_product_materiel_desc",
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
    	            for ($i = 0; $i < count($plan_users); $i++) {
    	                if ($plan_users[$i] == $user) {
    	                    $plan_users[$i] = str_replace('E', '', $val->transfer_id);
    	                    break;
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
                    $reviewWhere = "type = 'materiel_desc' and file_id = $id";
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
                    $reviewWhere = "type = 'materiel_desc' and finish_flg = 0 and file_id = $id";
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
                    $reviewWhere = "type = 'materiel_desc' and file_id = $id";
                    // 审核情况
                    $reviewData = array(
                        "actual_user" => $actual_user,
                        "finish_time" => $finish_time,
                        "finish_flg" => $finish_flg
                    );
                }
            }
    
            // 如果所有record的记录的finish_flg 都为1，则发布
            if ($finish_flg == 1 && $review->fetchAll("type = 'materiel_desc' and finish_flg = 0 and file_id = $id")->count() == 1) {
                $publish = true;
                // 修改物料信息
                $descData = array(
                    'ver'   => $materielData->ver_after,
                    'name'   => $materielData->name_after,
                    'description'   => $materielData->desc_after,
                    'manufacturers' => $materielData->manufacturers_after,
                    'supply1'     => $materielData->supply1_after,
                    'supply2'     => $materielData->supply2_after,
                    'data_file_id'     => $materielData->data_file_id_after,
                    'tsr_id'     => $materielData->tsr_id_after,
                    'first_report_id'     => $materielData->first_report_id_after
                );
                $descWhere = "id = ".$materielData->mid;
    
                $mData = array(
                    "state" => "Active",
                    "archive_time" => $now
                );
                $fileWhere = "id = $id";
            }
    
            try {
                // 更新审核情况
                $review->update($reviewData, $reviewWhere);
                // 更新文件
                if (isset($fileWhere)) {
                    $desc->update($mData, $fileWhere);
                }
                if (isset($descWhere)) {
                    $materiel->update($descData, $descWhere);
                }
                $this->operate("物料变更评审");
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
            $type = "物料变更申请(物料代码：".$materielData['code'].")";
            // 发邮件的情况：
            // 1、单站审核结束 $finish_flg = 1 && $publish = false
            if ($finish_flg == 1 && !$publish) {
                $subject = $type . "审批";
                // $to = 下一站审核人
                $current = $review->getFirstNoReview("materiel_desc", $id);
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
                $cc = $employee->getInfosByOneLine($record->getEmployeeIds($materielData['id'], 'materiel_desc'));
                $cc = $cc['email'];
//                 $cc = "";
                $detail = "物料代码：".$materielData['code'];
                if($materielData['name_before'] != $materielData['name_after']) {
                    if($detail) {
                        $detail .= '<br>';
                    }
                    $detail .= "名称：" . $materielData['name_before'] . " → " . $materielData['name_after'];
                }
                if($materielData['desc_before'] != $materielData['desc_after']) {
                    if($detail) {
                        $detail .= '<br>';
                    }
                    $detail .= "描述：" . $materielData['desc_before'] . " → " . $materielData['desc_after'];
                }
                if($materielData['supply1_before'] != $materielData['supply1_after']) {
                    if($detail) {
                        $detail .= '<br>';
                    }
                    $detail .= "供应商1：" . $materielData['supply1_code_before'].$materielData['supply1_cname_before'] . 
                    " → " . $materielData['supply1_code_after'].$materielData['supply1_cname_after'];
                }
                if($materielData['supply2_before'] != $materielData['supply2_after']) {
                    if($detail) {
                        $detail .= '<br>';
                    }
                    $detail .= "供应商2：" . $materielData['supply2_code_before'].$materielData['supply2_cname_before'] . 
                    " → " . $materielData['supply2_code_after'].$materielData['supply2_cname_after'];
                }
                if($materielData['manufacturers_before'] != $materielData['manufacturers_after']) {
                    if($detail) {
                        $detail .= '<br>';
                    }
                    $detail .= "制造商：" . $materielData['manufacturers_before'] . " → " . $materielData['manufacturers_after'];
                }
                $content = "你申请的" . $type . "已通过审批，物料信息已自动变更，请登录系统查看详情！<br>变更详情：<br>$detail";
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
                $content = "你申请的" . $type . "已被退回，请登录系统查看详情！";
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
    
            if(isset($subject)) {
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

        $desc = new Product_Model_Desc();
        $record = new Dcc_Model_Record();
        $review = new Dcc_Model_Review();

        if (count($deleted) > 0) {
            foreach ($deleted as $val) {
                $id = $val->id;
                // 操作记录
                $data = array(
                    'type' => "materiel_desc",
                    'table_name' => "oa_product_materiel_desc",
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
                    $review->delete("type = 'materiel_desc' and file_id = $id");
                    // 更新物料状态
                    $desc->update(array('state' => 'Deleted'), "id = $id");
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

    public function checkcodeAction() {
        $codes = $this->getRequest()->getParam('code');
        $result = array(
            result => 0,
            code => array()
        );
        if($codes) {
            foreach(explode(',', $codes) as $code) {
                $h = new Application_Model_Helpers();
                $openNumber = $h->getOpenReqOrderByCode($code);
                if(count($openNumber) > 0) {
                    $result['result'] = 1;
                    $result['code'][] = $code;
                }
            }
        }
        echo Zend_Json::encode($result);
        exit;
    }

}

