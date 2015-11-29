<?php
/**
 * 2013-9-8
 * @author      mg.luo
 * @abstract    物料编码申请
 */
class Product_ListController extends Zend_Controller_Action
{

    public function indexAction()
    {

    }

    public function getlistAction() {
        $request = $this->getRequest()->getParams();
        $limit = $request['limit'];
        $start = $request['start'];
        $type = new Product_Model_Type();
        $state = $this->getRequest()->getParam('search_state');
        if(!$state) {
            $whereSearch = "t1.state != 'Reviewing' and t1.state != 'Return' ";
        } else {
            $whereSearch = "t1.state = '$state'";
        }
        foreach ($request as $k => $v) {
            if ($v) {
            	if($k == 'search_tag') {
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
            	    
//             		$whereSearch .= " and (ifnull(t1.name,'') like '%$v%' or ifnull(t1.remark,'') like '%$v%' or ifnull(t1.manufacturers,'') like '%$v%' or ifnull(t1.code,'') like '%$v%' or ifnull(t1.description,'') like '%$v%' or ifnull(t3.cname,'') like '%$v%')";
            	} else if($k == 'search_state' && $v == 1) {
            		$whereSearch .= " and (state = 'Active' or state = 'APL' or state = 'Preliminary')";
            	} else if("search_type" == $k && $v) {
//                 	$ids = $type->getTypes($v);
//                 	if($ids) {
//                 		$whereSearch .= " and t1.type in ($ids)";
//                 	}
                	$whereSearch .= " and t1.type in ($v)";
                } else if ("search_archive_date_from" == $k && $v) {
                    $whereSearch .= " and t1.archive_time >= '" . str_replace('T', ' ', $v) . "'";
                } else if ("search_archive_date_to" == $k && $v) {
                    $whereSearch .= " and t1.archive_time <= '" . str_replace('T00:00:00', ' 23:59:59', $v) . "'";
                } else {
	                $col = str_replace('search_', '', $k);
	                if ($col != $k) {
	                    // 查询条件
	                    $whereSearch .= " and ifnull(t1." . $col . ",'') like '%" . $v . "%'";
	                }
            	}
            }
        }

        $materiel = new Product_Model_Materiel();
        $record = new Dcc_Model_Record();
        // 获取物料数据
        $data = $materiel->getList($whereSearch, $start, $limit);
        $count = $materiel->getCount($whereSearch, $start, $limit);
        $totalCount = $count;
        for($i = 0; $i < count($data); $i++) {
            if(($typeId = $data[$i]['type']) != '') {
                $typeName = $this->getTypeByConnect($typeId, '');
                $data[$i]['type_name'] = $typeName;
            }
            $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
            $data[$i]['archive_time'] = strtotime($data[$i]['archive_time']);

            $data[$i]['record'] = $record->getHis($data[$i]['id'], 'materiel');

        }
        $resutl = array(
            "totalCount" => $totalCount,
            "topics" => $data
        );
        // 转为json格式并输出
        echo Zend_Json::encode($resutl);

        exit;
    }

    public function getlistnopageAction() {
        $request = $this->getRequest()->getParams();
        $whereSearch = "1=1";
        foreach ($request as $k => $v) {
            if ($v) {
            	if($k == 'search_tag') {
            		$whereSearch .= " and (ifnull(t1.name,'') like '%$v%' or ifnull(t1.remark,'') like '%$v%' or ifnull(t1.manufacturers,'') like '%$v%' or ifnull(t1.code,'') like '%$v%' or ifnull(t1.description,'') like '%$v%' or ifnull(t6.name,'') like '%$v%')";
            	} else if($k == 'search_state' && $v == 1) {
            		$whereSearch .= " and (state = 'Active' or state = 'APL' or state = 'Preliminary' or state = 'Obsolete' or state = 'Pre-Obsolete')";
            	} else if($k == 'search_state') {
            		$whereSearch .= " and state = '$v'";
            	} else {
	                $col = str_replace('search_', '', $k);
	                if ($col != $k) {
	                    // 查询条件
	                    $whereSearch .= " and ifnull(t1." . $col . ",'') like '%" . $v . "%'";
	                }
            	}
            }
        }
        $model = "";
        if(isset($request["model"]) && $request["model"]) {
        	$model = $request["model"];
        }

        $materiel = new Product_Model_Materiel();
        $record = new Dcc_Model_Record();
        // 获取当前正在流程中的id
        if($model == 'desc' || $model == 'transfer' || $model == 'bom') {
        	$table = "oa_product_materiel_".$model;
        	if($model == 'bom') {
        		$table = "oa_product_bom_fa";
        	}
        	$whereSearch .= " and t1.id not in (select mid from $table where state = 'Reviewing' or state = 'Return')";
        }
        // 获取物料数据
        $data = $materiel->getList($whereSearch, 0, 100);
        for($i = 0; $i < count($data); $i++) {
            if(($typeId = $data[$i]['type']) != '') {
                $typeName = $this->getTypeByConnect($typeId, '');
                $data[$i]['type_name'] = $typeName;
            }
            $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
            $data[$i]['archive_time'] = strtotime($data[$i]['archive_time']);

            $data[$i]['record'] = $record->getHis($data[$i]['id'], 'materiel');

        }
        // 转为json格式并输出
        echo Zend_Json::encode($data);

        exit;
    }

    private function getTypeByConnect($id, $name) {
        if ($id) {
            $type = new Product_Model_Type();
            $row = $type->fetchRow("id = $id");
            if ($row) {
                $id = $row->parent_id;
                if($id == 0) {
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

    private function getTypeByConnectForExport($id, $name) {
        if ($id) {
            $type = new Product_Model_Type();
            $row = $type->fetchRow("id = $id");
            if ($row) {
                $id = $row->parent_id;
                if($id == 0) {
                   $name = $row->name . ' > '. $name;
                } else {
                   $name = $row->name . ' > ' . $name;
                }

                return $this->getTypeByConnectForExport($id, $name);
            }
        }
        return trim($name, ' > ');
    }

    private function getTypeCodeByConnect($id, $code="") {
        if($id) {
            $type = new Product_Model_Type();
            $row = $type->fetchRow("id = $id");
            if($row) {
                $id = $row->parent_id;
                $code = $row->code.$code;

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
        if(isset($result['q']) && $result['q']) {
        	$query = $result['q'];
            $where = "code = '$query' or cname like '%$query%' or ename like '%$query%'";
        } else {
            $where = "1=1";
        }

        $data = $bpartner->getListForSel($where);
        for($i = 0; $i < count($data); $i++) {
            if(($code = $data[$i]['code']) != '') {
                if(($cname = $data[$i]['cname']) != '') {
                    $data[$i]['text'] = $code.$cname;
                } else if(($ename = $data[$i]['ename']) != '') {
                    $data[$i]['text'] = $code.$ename;
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
        // 新增还是编辑
        if(isset($val->id) && $val->id) {
        	$row = $materiel->fetchRow("id = ".$val->id);
        	$edit_count = $row['edit_count'];
            $data = array(
                    'edit_count'    => $edit_count+1,
                    'state'        => $val->state,
                    'remark'        => $val->remark,
                    'unit'          => $val->unit,
                    'manufacturers' => $val->manufacturers,
                    'supply1'     => $val->supply1,
                    'supply2'     => $val->supply2,
                    'mpq'           => $val->mpq,
                    'moq'           => $val->moq,
                    'tod'           => $val->tod
            );
            $id = $val->id;
            $where = "id = ".$id;
            try{
                if($id) {
                    $materiel->update($data, $where);
                    // 操作记录
                    $data = array(
                            'type'             => "materiel",
                            'table_name'       => "oa_product_materiel",
                            'table_id'         => $id,
                            'handle_user'      => $user,
                            'handle_time'      => $now,
                            'action'           => "维护",
                            'ip'               => $_SERVER['REMOTE_ADDR']
                    );
                    $record->insert($data);
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

        // 获取物料信息
        $materielData = $materiel->getOne($id);
        if(!$materielData) {
            $result['result'] = false;
            $result['info'] = "数据状态已改变";

            echo Zend_Json::encode($result);
            exit;}
        $review_id = $materielData->review_id;

        // 获取当前审核情况
        // 如果record记录被删除或状态已改变，报错
        $reviewWhere = "id = $review_id";
        $reviewRows = $review->getList($reviewWhere);
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

        if ($pass == 1) {
            // 通过方式
            $method = $reviewRow['method'];
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
                if (strlen($plan_user) == strlen($actual_user)) {
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
            // 更改审核情况中的审核人
            $plan_users = explode(',', $reviewRow['plan_user']);
            for ($i = 0; $i < count($plan_users); $i++) {
                if ($plan_users[$i] == $user) {
                    $plan_users[$i] = str_replace('E', '', $val->transfer_id);
                    break;
                }
            }
            $plan_user = implode(',', $plan_users);

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
            $code = $val->code;
            if(!$code) {
            	// 自动生成物料编码
            	$code = $this->getCode($materielData->type);
            	if(!$code) {
            		$result['result'] = false;
		            $result['info'] = "生成物料编码失败";

		            echo Zend_Json::encode($result);

		            exit;
            	}
            }
            $mData = array(
                "state" => "Active",
                "code" => $code,
                "archive_time" => $now
            );
            $fileWhere = "id = $id";
        }

        try {
            // 更新审核情况
            $review->update($reviewData, $reviewWhere);
            // 更新文件
            if (isset($fileWhere)) {
                $materiel->update($mData, $fileWhere);
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
            $current = $review->getCurrent("materiel", $id);
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
            $cc = "";
            $content = "你申请的" . $type . "已通过审批，分配的物料编码为：".$code."，请登录系统查看详情！";
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

        echo Zend_Json::encode($result);

        exit;
    }

    /**
     * @abstract    根据文件简号和流水号生成最新文件编码
     * @return      null
     */
    private function getCode($id) {
        $type = new Product_Model_Type();
        $materiel = new Product_Model_Materiel();
        $code = $this->getTypeCodeByConnect($id);
        // 获取流水号长度
        $data = $type->fetchRow("id = $id");
        $sn_length = $data->sn_length;
        if($code && $sn_length) {
        	$mData = $materiel->getAdapter()->query("select max(code) as maxcode from oa_product_materiel where code like '$code%'")->fetchObject();
        	$num = "";
        	if($mData && $mData->maxcode) {
        		$max = $mData->maxcode;
        	    $num = str_replace($code, '', $max);
        	} else {
        		for($i = 0; $i < $sn_length;$i++) {
        			$num .= "0";
        		}
        	}
        	// +1
        	$code .= substr((("1".$num) + 1), 1);
        	return $code;
        } else {
            return "";
        }
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

    // 导出CSV
    public function exportcsvAction()
    {
        $request = $this->getRequest()->getParams();
        if(isset($request['source']) && $request['source'] == 'list') {
            $whereSearch = "t1.state != 'Reviewing' and t1.state != 'Return' ";
        } else {
            $whereSearch = "1=1";
        }
        foreach ($request as $k => $v) {
            if ($v) {
            	if($k == 'search_tag') {
            		$whereSearch .= " and (t1.name like '%$v%' or t1.remark like '%$v%' or t1.manufacturers like '%$v%' or t1.code like '%$v%' or t1.description like '%$v%')";
            	} else if($k == 'search_state' && $v == 1) {
            		$whereSearch .= " and (state = 'Active' or state = 'APL' or state = 'Preliminary')";
            	} else if($k == 'search_state') {
            		$whereSearch .= " and state = '$v'";
            	} else if ("search_archive_date_from" == $k && $v) {
                    $whereSearch .= " and t1.archive_time >= '" . str_replace('T', ' ', $v) . "'";
                } else if ("search_archive_date_to" == $k && $v) {
                    $whereSearch .= " and t1.archive_time <= '" . str_replace('T00:00:00', ' 23:59:59', $v) . "'";
                } else {
	                $col = str_replace('search_', '', $k);
	                if ($col != $k) {
	                    // 查询条件
	                    $whereSearch .= " and t1." . $col . " like '%" . $v . "%'";
	                }
            	}
            }
        }
        print(chr(0xEF).chr(0xBB).chr(0xBF));
        $materiel = new Product_Model_Materiel();
        $record = new Dcc_Model_Record();
        // 获取物料数据
        $data = $materiel->getList($whereSearch,null,null);
        $data_csv = array();
        $title = array(
                'cnt'               => '#',
                'id'                => 'ID',
                'code'              => '物料号',
                'name'              => '名称',
                'description'       => '描述',
                'type_name'         => '物料类别',
                'state'             => '状态',
                'ver'               => '版本',
                'unit'              => '单位',
                'project_name'      => '产品型号',
                'supply1'           => '供应商1',
                'supply2'           => '供应商2',
                'manufacturers'     => '制造商',
                'mpq'               => 'MPQ',
                'moq'               => 'MOQ',
                'tod'               => '标准货期',
                'data_file'         => '数据手册',
                'tsr'               => 'TSR',
                'first_report'      => '样品检验报告',
                'remark'            => '备注',
                'archive_time'      => '发布日期',
                'creater'           => '申请人',
                'create_time'       => '申请时间'
//                'record'            => '审批记录'
        );

        $title = $this->object_array($title);
        $date = date('YmdHsi');
        $filename = "materiel_list" . $date;
        $path = "../temp/" . $filename . ".csv";
        $file = fopen($path, "w");
        fputcsv($file, $title);
        array_push($data_csv, $title);

        $typeids = array();
        $typenames = array();
        $k = 0;
        for($i = 0; $i < count($data); $i++) {
            if(($typeId = $data[$i]['type']) != '') {
            	if(in_array($typeId, $typeids)) {
            		$data[$i]['type_name'] = $typenames[$typeId];
            	} else {
	                $typeName = $this->getTypeByConnectForExport($typeId, '');
	                $data[$i]['type_name'] = $typeName;

	                $typeids[] = $typeId;
	                $typenames[$typeId] = $typeName;
            	}
            }
            //$data[$i]['record'] = $record->getHis($data[$i]['id'], 'materiel');
            $d = $data[$i];

            $k++;

            $info = array(
                'cnt'               => $k,
                'id'                => $d['id'],
                'code'              => $d['code'],
                'name'              => $d['name'],
                'description'       => $d['description'],
                'type_name'         => $d['type_name'],
                'state'             => $d['state'],
                'ver'               => 'V'.$d['ver'],
                'unit'              => $d['unit_name'],
                'project_name'      => $d['project_name'],
                'supply1'           => $d['supply_code1'].$d['supply_cname1'],
                'supply2'           => $d['supply_code2'].$d['supply_cname2'],
                'manufacturers'     => $d['manufacturers'],
                'mpq'               => $d['mpq'],
                'moq'               => $d['moq'],
                'tod'               => $d['tod'],
                'data_file'         => $d['data_file'],
                'tsr'               => $d['tsr'],
                'first_report'      => $d['first_report'],
                'remark'            => $d['remark'],
                'archive_time'      => $d['archive_time'],
                'creater'           => $d['creater'],
                'create_time'       => $d['create_time']
//                'record'            => $d['record'],
            );
            $d = $this->object_array($info);
            array_push($data_csv, $info);
            fputcsv($file, $d);
        }

        fclose($file);
        $this->operate("物料导出");

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
//            $v = str_replace("μ", "[u]", $v);
//            $v = str_replace("ø", "[o]", $v);
//            mb_convert_encoding ("Ø","HTML-ENTITIES","UTF-8");
            $a[$key] = iconv('utf-8', 'GB18030//TRANSLIT', $v);
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
            'target' => 'Materiel',
            'computer_name' => $computer_name,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'time' => $now
        );

        $operate->insert($data);
    }

    // 导入
    public function importAction()
    {
        $result = array(
            'success'   => true,
            'info'      => '导入成功'
        );

        $request = $this->getRequest()->getParams();
        $now = date('Y-m-d H:i:s');
        if(isset($_FILES['csv'])){
            $now = date('Y-m-d H:i:s');
            $user_session = new Zend_Session_Namespace('user');
            $user_id = $user_session->user_info['user_id'];

            $file = $_FILES['csv'];
            $file_extension = strrchr($file['name'], ".");
            $h = new Application_Model_Helpers();
            $tmp_file_name = $h->getMicrotimeStr().$file_extension;
            $savepath = "../temp/";
            $tmp_file_path = $savepath.$tmp_file_name;
            move_uploaded_file($file["tmp_name"], $tmp_file_path);

            $materiel = new Product_Model_Materiel();
            $supply = new Product_Model_Bpartner();
            $db = $materiel->getAdapter();

            $file = fopen($tmp_file_path, "r");
            $data = array();
            while(!feof($file)){
                array_push($data, fgetcsv($file));
            }
            // 数据校验
            if(count($data) <= 1) {
                $result['success'] = false;
                $result['info'] = "文件中无数据！";
                fclose($file);
                echo Zend_Json::encode($result);
                exit;
            }
            // 文件格式 # 物料号 MPQ MOQ LT 供应商1 供应商2 备注
            $errorCode = array();
            $okData = array();
            for($i = 1; $i < count($data); $i++) {
                $num = $i+1;
                $row = $data[$i];
                if(count($row) < 2 || !$row[1]) {
                    continue;
                }
                for($k = 0; $k < count($row); $k++) {
                    $row[$k] = str_replace("\"", "", $row[$k]);
                }
                // check materiel code
                if($materiel->checkExist($row[1])) {
                    // data
                    $code = $row[1];
                    $supply1Id = $supply2Id = '';
                    $mpq = $moq = $lt = 0;
                    $manufacturers = $remark = '';
                    if(isset($row[2]) && $row[2] && is_numeric($row[2])) {
                        $mpq = $row[2];
                    }
                    if(isset($row[3]) && $row[3] && is_numeric($row[3])) {
                        $moq = $row[3];
                    }
                    if(isset($row[4]) && $row[4] && is_numeric($row[4])) {
                        $lt = $row[4];
                    }
                    // supply
                    $supply1 = isset($row[5]) ? $row[5] : 0;
                    $supply2 = isset($row[6]) ? $row[6] : 0;
                    // check supply exists
                    if($supply1) {
                        $supply1 = iconv('GBK', 'UTF-8', $supply1);
                        $where = "code = '$supply1' or cname like '$supply1' or ename like '$supply1' or concat(code, cname) like '$supply1' or concat(code, ename) like '$supply1'";
                        $supply1Data = $supply->getJoinList($where, array(), array('id'));
                        if(count($supply1Data) > 0) {
                            $supply1Id = $supply1Data[0]['id'];
                        }
                    }
                    if($supply2) {
                        $supply2 = iconv('GBK', 'UTF-8', $supply2);
                        $where = "code = '$supply2' or cname like '$supply2' or ename like '$supply2' or concat(code, cname) like '$supply2' or concat(code, ename) like '$supply2'";
                        $supply2Data = $supply->getJoinList($where, array(), array('id'));
                        if(count($supply2Data) > 0) {
                            $supply2Id = $supply2Data[0]['id'];
                        }
                    }
                    $manufacturers = isset($row[7]) ? iconv('GBK', 'UTF-8', $row[7]) : '';
                    $remark = isset($row[8]) ? iconv('GBK', 'UTF-8', $row[8]) : '';

                    $profile = array(
                        'mpq' => $mpq,
                        'moq' => $moq,
                        'tod' => $lt,
                        'supply1' => $supply1Id,
                        'supply2' => $supply2Id,
                        'manufacturers' => $manufacturers,
                        'remark' => $remark
                    );
                    $where = "code = '$code'";
                    try {
                        $materiel->update($profile, $where);
                    } catch(Exception $e) {
                        $errorCode[] = $code;
                    }

                    $okData[] = $row;
                } else {
                    $errorCode[] = $row[1];
                }
            }
            if(count($errorCode) > 0) {
                $result['error'] = $errorCode;
            }

            fclose($file);
        }
        echo Zend_Json::encode($result);
        exit;
    }

}

