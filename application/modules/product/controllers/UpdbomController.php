<?php
/**
 * 2013-9-8
 * @author      mg.luo
 * @abstract    BOM升版
 */
class Product_UpdbomController extends Zend_Controller_Action
{

    public function indexAction()
    {

    }

    public function getlistAction() {
        $request = $this->getRequest()->getParams();
        $limit = $request['limit'];
        $start = $request['start'];
        $fa = new Product_Model_Fa();
        $db = $fa->getAdapter();
        $whereSearch = "1=1";
//        if(isset($request['search_state']) && $request['search_state'] != 'Active') {
//            $whereSearch = " t1.state != 'Active'";
//        }
        foreach ($request as $k => $v) {
            if ($v) {
                if($k == 'search_key') {
                    $whereSearch .= " and (ifnull(t1.remark,'') like '%$v%' or ifnull(t3.cname,'') like '%$v%' or ifnull(t1.description,'') like '%$v%')";
                } else if ("search_fa" == $k && $v) {
                    $nid = "";
                    $faData = $db->query("select group_concat(nid) as nid from oa_product_bom_fa_dev where code like '%$v%'")->fetchObject();
                    if($faData && $faData->nid) {
                        $nid = $faData->nid;
                    }
                    if(!$nid) {
                        $nid = "0";
                    }
                    $whereSearch .= " and t1.id in ($nid)";
                } else if ("search_son" == $k && $v) {
                    $nid = "";
                    $sonData = $db->query("select group_concat(nid) as nid from oa_product_bom_son_dev where code like '%$v%'")->fetchObject();
                    if($sonData && $sonData->nid) {
                        $nid = $sonData->nid;
                    }
                    if(!$nid) {
                        $nid = "0";
                    }
                    $whereSearch .= " and t1.id in ($nid)";
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
        $type = new Product_Model_Type();
        $record = new Dcc_Model_Record();
        $flow = new Admin_Model_Flow();
        $step = new Admin_Model_Step();
        $share = new Dcc_Model_Share();
        $type = new Dcc_Model_Type();
        $review = new Dcc_Model_Review();
        $employee = new Hra_Model_Employee();
        $dept = new Hra_Model_Dept();
        $updbom = new Product_Model_Updbom();
        // 查询条件

        // 类型
        $myType = "";
        if (isset($request['mytype'])) {
            $myType = $request['mytype'];
        }
        // 获取物料数据
        $data = $updbom->getMy($myType, $whereSearch, $user, $start, $limit);
        $totalCount = $updbom->getMyCount($myType, $whereSearch, $user);
        for($i = 0; $i < count($data); $i++) {
            $upd_type = $data[$i]['upd_type'];
            if($upd_type == 'ECO') {
                $bomflg = "ecobom";
            } else if($upd_type == 'DEV') {
                $bomflg = "devbom";
            }
            $mytype = 2;
            if($data[$i]['create_user'] == $user) {
                $mytype = 1;
            }
            $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
            $data[$i]['archive_time'] = strtotime($data[$i]['archive_time']);
            // 获取BOM
            $table = "oa_product_bom_fa_dev";
            $bomData = $updbom->getAdapter()->query("select group_concat(concat(code,' V', ver)) as bom from $table where type='$upd_type' and nid = ".$data[$i]['id'])->fetchObject();
            $data[$i]['bom'] = $bomData->bom;

            // 增加审核状态
            $reviewState = "";
            $step_name = "";
            if ($data[$i]['state'] == 'Active') {
                $reviewState = "已归档";
            } else if ($data[$i]['state'] == 'Reviewing') {
                // 查询当前审核状态
                // 查询所有审核阶段
                $reviewRows = $review->getList("file_id = " . $data[$i]['id'], $bomflg);
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
            } else if ($data[$i]['state'] == 'Draft') {
                $reviewState = "草稿";
            } else {
                $reviewState = $data[$i]['state'];
            }
            $data[$i]['step_name'] = $step_name;
            $data[$i]['review_state'] = $reviewState;
            $data[$i]['mytype'] = $mytype;
            $data[$i]['replacea'] = $data[$i]['replace'];

            $data[$i]['record'] = $record->getHis($data[$i]['id'], $bomflg);
        }
        // 排序
        $dataT3 = array();
        $dataT2 = array();
        $dataT1 = array();
        for($i = 0; $i < count($data); $i++) {
            if($data[$i]['mytype'] == 3) {
                $dataT3[] = $data[$i];
            } else if($data[$i]['mytype'] == 2) {
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

    public function getfaAction() {
        $result = $this->getRequest()->getParams();
        $nid = $result['nid'];
        $data = array();
        if($nid) {
            $fa = new Product_Model_Fadev();
            $where = "t1.nid=$nid and (t1.type = 'DEV' or t1.type = 'ECO')";
            $data = $fa->getList($where);
            for($i = 0; $i < count($data); $i++) {
                if(($typeId = $data[$i]['type']) != '') {
                    $typeName = $this->getTypeByConnect($typeId, '');
                    $data[$i]['type_name'] = $typeName;
                }
                $data[$i]['bom_file_view'] = $data[$i]['bom_file'];
            }
        }
        // 转为json格式并输出
        echo Zend_Json::encode($data);

        exit;
    }

    public function getsonAction() {
        $result = $this->getRequest()->getParams();
        $nid = $result['nid'];
        $data = array();
        if($nid) {
            $son = new Product_Model_Sondev();
            $data = $son->getListById($nid);
        }
        // 转为json格式并输出
        echo Zend_Json::encode($data);

        exit;
    }

    private function getTypeByConnect($id, $name) {
        if($id) {
            $type = new Product_Model_Type();
            $row = $type->fetchRow("id = $id");
            if($row) {
                $id = $row->parent_id;
                $name = $row->name.' > '.$name;

                return $this->getTypeByConnect($id, $name);
            }
        }
        return trim($name, ' > ');
    }

    public function savedraftAction(){
        // 返回值数组
        $result = array(
            'success' => true,
            'result'  => true
        );

        $request = $this->getRequest()->getParams();

        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];

        $val = (object)$request;
        $upd_type = isset($val->upd_type) ? $val->upd_type : "";
        $id = "";
        if(isset($val->nid) && $val->nid) {
            $id = $val->nid;
        }

        $updbom = new Product_Model_Updbom();
        $record = new Dcc_Model_Record();
        $faModel = new Product_Model_Fa();
        $sonModel = new Product_Model_Son();
        $fadevModel = new Product_Model_Fadev();
        $sondevModel = new Product_Model_Sondev();
        $db = $updbom->getAdapter();
        // 保存数据
        $data = array(
            "upd_type"    =>  $upd_type,
            "replace_flg" =>  isset($val->replace_flg) ? 1 : 0,
            "replace"     =>  isset($val->replacea) ? $val->replacea : "",
            "replaced"    =>  isset($val->replaced) ? $val->replaced : "",
            "description" =>  isset($val->description) ? $val->description : "",
            "upd_reason"  =>  isset($val->upd_reason) ? $val->upd_reason : "",
            "reason_type" =>  isset($val->reason_type) ? $val->reason_type : "",
            "state"       =>  'Draft',
            "remark"      =>  isset($val->remark) ? $val->remark : "",
            "create_time" => $now,
            "create_user" => $user,
            "update_time" => $now,
            "update_user" => $user
        );
        try {
            if(!$id) {
                $id = $updbom->insert($data);
                if ($id) {
                    $result['nid'] = $id;

                    /*// 操作记录
                    $data = array(
                            'type'             => "dev",
                            'table_name'       => "oa_product_bom_upd",
                            'table_id'         => $id,
                            'handle_user'      => $user,
                            'handle_time'      => $now,
                            'action'           => "新建",
                            'ip'               => $_SERVER['REMOTE_ADDR']
                    );
                    $record->insert($data);*/

                    // 自定义字段
                    $attrval = new Admin_Model_Formval();
                    if($upd_type == 'ECO') {
                        $menu = 'oa_product_bom_eco_' . $id;
                    } else {
                        $menu = 'oa_product_bom_dev_' . $id;
                    }
                    $attrval->delete("menu = '".$menu."'");
                    foreach ($request as $field => $value) {
                        if (stripos($field, "intelligenceField") !== false && $value) {
                            $attrId = str_replace("intelligenceField", "", $field);

                            $formval = array(
                                'attrid' => $attrId,
                                'value' => $value,
                                'menu' => $menu
                            );
                            $attrval->insert($formval);
                        }
                    }
                }
            }
            // 替代料
            if($data['replace_flg'] && $data['replace'] && $data['replaced']) {
                $result['success'] = false;
                $result['info'] = "不存在以“".$data['replaced']."”为子物料的BOM";
                // 删除bom表
                $delKeys = $db->query("select group_concat(recordkey) as recordkey from oa_product_bom_fa_dev where nid = $id and type='ECO'")->fetchObject();
                $fadevModel->delete("nid = $id and type='ECO'");
                if($delKeys && $delKeys->recordkey) {
                    $sondevModel->delete("nid = $id");
                }

                $replaceids = $db->query("select id from oa_product_materiel where code = '".$data['replace']."'")->fetchObject();
                $replaceid = $replaceids->id;
                // 查询fa
                $recordkeys = $db->query("select group_concat(recordkey) as recordkey from oa_product_bom_son where code = '".$data['replaced']."'")->fetchObject();
                if($recordkeys && $recordkeys->recordkey) {
                    $fas = $faModel->getFaList("(state = 'MBOM' or state = 'EBOM') and recordkey in ($recordkeys->recordkey)");
                    if(count($fas) > 0) {
                        // 只取一次最大值，可能遇到重号
                        $maxkeys1 = $db->query("select ifnull(max(recordkey),0) + 1 as maxkey from oa_product_bom_fa_dev")->fetchObject();
                        $maxkeys2 = $db->query("select ifnull(max(recordkey),0) + 1 as maxkey from oa_product_bom_fa")->fetchObject();
                        $recordkey1 = $maxkeys1->maxkey;
                        $recordkey2 = $maxkeys2->maxkey;
                        $recordkey = max($recordkey1, $recordkey2);
                        $fainsert = array();
                        $soninsert = array();
                        foreach($fas as $farow) {
                            $ver = $farow['ver'] + 0.1;
                            if(is_int($ver)) {
                                $ver = $ver.'.0';
                            }
                            $fadevdata = array(
                                "nid"        => $id,
                                "recordkey"  => $recordkey,
                                "id"         => $farow['id'],
                                "code"       => $farow['code'],
                                "project_no" => $farow['project_no'],
                                "bom_file"   => $farow['bom_file'],
                                "qty"        => $farow['qty'],
                                "state"      => $farow['state'],
                                "ver"        => $ver,
                                "type"       => $upd_type,
                                "remark"     => $farow['remark']
                            );
                            $fainsert[] = $fadevdata;
                            $sons = $sonModel->getList("recordkey = '".$farow['recordkey']."'");
                            foreach($sons as $sonrow) {
                                $sondevdata = array(
                                    "nid"          => $id,
                                    "recordkey"    => $recordkey,
                                    "pid"          => $sonrow['pid'],
                                    "id"           => $sonrow['id'],
                                    "code"         => $sonrow['code'],
                                    "qty"          => $sonrow['qty'],
                                    "partposition" => $sonrow['partposition'],
                                    "replace"      => $sonrow['replace'],
                                    "remark"       => $sonrow['remark']
                                );
                                if($sonrow['code'] == $data['replaced']) {
                                    $sondevdata['code'] = $data['replace'];
                                    $sondevdata['id'] = $replaceid;
                                    $sonreplaceArr = explode(',', $sonrow['replace']);
                                    if(in_array($data['replace'], $sonreplaceArr)) {
                                        for($i=0;$i<count($sonreplaceArr);$i++) {
                                            if($sonreplaceArr[$i] == $data['replace']) {
                                                unset($sonreplaceArr[$i]);
                                            }
                                        }
                                    }
                                    $sondevdata['replace'] = implode(',', $sonreplaceArr);
                                }
                                $soninsert[] = $sondevdata;
                            }
                            $recordkey++;
                        }

                        foreach($fainsert as $faData) {
                            $fadevModel->insert($faData);
                        }
                        foreach($soninsert as $sonData) {
                            $sondevModel->insert($sonData);
                        }
                        $result['success'] = true;
                        $result['info'] = "";
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

        $updbom = new Product_Model_Updbom();
        $fadev = new Product_Model_Fadev();
        $sondev = new Product_Model_Sondev();
        $fa = new Product_Model_Fa();
        $son = new Product_Model_Son();
        $record = new Dcc_Model_Record();
        $review = new Dcc_Model_Review();

        if (count($deleted) > 0) {
            foreach ($deleted as $val) {
                $id = $val->id;
                $upd_type = $val->upd_type;
                if($upd_type == 'ECO') {
                    $bomflg = "ecobom";
                    $table = "oa_product_bom_eco";
                } else if($upd_type == 'DEV'){
                    $bomflg = "devbom";
                    $table = "oa_product_bom_dev";
                }
                // 操作记录
                $data = array(
                    'type' => $bomflg,
                    'table_name' => $table,
                    'table_id' => $id,
                    'handle_user' => $user,
                    'handle_time' => $now,
                    'action' => "删除",
                    'ip' => $_SERVER['REMOTE_ADDR']
                );
                // 获取bom fa表的recordkey
                $sql = "select recordkey from oa_product_bom_fa_dev where nid=$id and (type='DEV' or type='ECO')";
                $keys = $fa->getAdapter()->query($sql)->fetchObject();
                if($keys && $keys->recordkey) {
                    $recordkey = $keys->recordkey;
                } else {
                    $sql = "select recordkey from oa_product_bom_fa where nid=$id and (type='DEV' or type='ECO')";
                    $keys = $fa->getAdapter()->query($sql)->fetchObject();
                    if($keys && $keys->recordkey) {
                        $recordkey = $keys->recordkey;
                    }
                }
                
                try {
                    // 增加record记录
                    $record->insert($data);
                    // 删除review记录
                    $review->delete("type = '$bomflg' and file_id = $id");
                    // 删除bom表
                    $updbom->delete("id = $id");
                    if(isset($recordkey) && $recordkey) {
                        $where = "recordkey = $recordkey";
                        $fadev->delete($where);
                        $sondev->delete($where);
                        $fa->delete($where);
                        $son->delete($where);
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

    // 导入
    public function importAction()
    {
        $result = array(
                'success'   => true,
                'info'      => '导入成功'
        );

        $request = $this->getRequest()->getParams();

        $type = isset($request['type']) ? $request['type'] : null;
        $nid = isset($request['nid']) ? $request['nid'] : "";

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
            $db = $materiel->getAdapter();

            if($type == 'dev') {
                $fa = new Product_Model_Fadev();
                $son = new Product_Model_Sondev();
                $table = "oa_product_bom_fa_dev";
            } else if($type == 'bom') {
                $fa = new Product_Model_Fa();
                $son = new Product_Model_Son();
                $table = "oa_product_bom_fa";
            }

            if($type == 'dev' || $type == 'bom'){
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
                for($i = 1; $i < count($data); $i++) {
                    $num = $i+1;
                    $row = $data[$i];
                    if($i == 1 && !$row[1]) {
                        $result['success'] = false;
                        $result['info'] = "请以上级物料开头：第".$num."行！";
                        fclose($file);
                        echo Zend_Json::encode($result);
                        exit;
                    }
                    if(!$row[1] && !$row[2]) {
                        continue;
                    }
                    if($row[1] && $row[2]) {
                        $result['success'] = false;
                        $result['info'] = "上级物料和下级物料不能同时存在：第".$num."行！";
                        fclose($file);
                        echo Zend_Json::encode($result);
                        exit;
                    }
                     if($row[1]) {
                         if(!$materiel->checkExist($row[1])) {
                             $result['success'] = false;
                            $result['info'] = "物料“".$row[1]."”不存在：第".$num."行！";
                            fclose($file);
                            echo Zend_Json::encode($result);
                            exit;
                         }
                        $m = $materiel->getMaterielByCode($row[1]);
                    }
                     if($row[2]) {
                         if(!$materiel->checkExist($row[2])) {
                             $result['success'] = false;
                            $result['info'] = "物料“".$row[2]."”不存在：第".$num."行！";
                            fclose($file);
                            echo Zend_Json::encode($result);
                            exit;
                         }

                    }
                     if($row[2] && $row[7]) {
                         $replace = explode(',', $row[7]);
                         foreach($replace as $r) {
                             $r = iconv('utf-8', 'gbk//IGNORE', $r);
                             if(!$materiel->checkExist($r)) {
                                 $result['success'] = false;
                                $result['info'] = "物料“".$r."”不存在：第".$i."行！";
                                fclose($file);
                                echo Zend_Json::encode($result);
                                exit;
                             }
                         }
                    }
                    if($i > 1 && $row[1] && $data[$i-1][1]) {
                        $result['success'] = false;
                        $result['info'] = "BOM“".$data[$i-1][1]."”不存在子物料：第$i行！";
                        fclose($file);
                        echo Zend_Json::encode($result);
                        exit;
                    }
                }

                // 只取一次最大值，可能遇到重号
                $maxkeys = $fa->getAdapter()->query("select ifnull(max(recordkey),0) as maxkey from $table")->fetchObject();
                $recordkey = $maxkeys->maxkey;
                $pid = "";
                $faArr = array();
                $sonArr = array();
                $obsoleteWhere = "1=0";
                // 校验成功，开始保存数据
                for($i = 1; $i < count($data); $i++) {
                    $row = $data[$i];
                    if($row[1]) {
                        $m = $materiel->getMaterielByCode($row[1]);
                        $recordkey++;
                        $pid = $m['id'];
                        $faData = array(
                            'nid'        => $nid,
                            'recordkey'  => $recordkey,
                            'id'         => $m['id'],
                            'code'       => $row[1],
                            'qty'        => 1,
                            'state'      => $row[3] ? $row[3] : 'EBOM',
                            'ver'        => $row[4] ? $row[4] : '1.0'
                        );
                        if($faData['ver'] > '1.0') {
                            $obsoleteWhere .= " or (code='".$row[1]."' and ver < '".$faData['ver']."')";
                        }
                        // 检查是否已经存在
                        if($type == 'dev') {
                            $list = $fa->getFaList("nid = $nid and ver = '".$faData['ver']."' and id = ".$m['id']);
                        } else {
                            $list = $fa->getFaList("ver = '".$faData['ver']."' and id = ".$m['id']);
                        }
                        if($list && count($list) > 0) {
                            $result['success'] = false;
                            $result['info'] = "数据已存在：".$faData['code']." ".$faData['ver']."！";
                            fclose($file);
                            echo Zend_Json::encode($result);
                            exit;
                        }
                        $faArr[] = $faData;
                    }
                    if($row[2]) {
                        $m = $materiel->getMaterielByCode($row[2]);
                        $sonData = array(
                            'nid'        => $nid,
                            'recordkey'  => $recordkey,
                            'pid'        => $pid,
                            'id'         => $m['id'],
                            'code'       => $row[2],
                            'qty'        => $row[5] ? $row[5] : 1,
                            'partposition'   => $row[6] ? $row[6] : '',
                            'replace'        => $row[7] ? $row[7] : '',
                            'remark'         => $row[8] ? $row[8] : ''
                        );
                        // 检查是否已经存在

                        if($type == 'dev') {
                            $list = $son->getList("nid = $nid and id = ".$m['id']);
                        } else {
                            $list = $son->getList("recordkey = $recordkey and id = ".$m['id']);
                        }

                        if($list && count($list) > 0) {
                            $result['success'] = false;
                            $result['info'] = "数据已存在：".$sonData['code']."！";
                            fclose($file);
                            echo Zend_Json::encode($result);
                            exit;
                        }
                        $sonArr[] = $sonData;
                    }
                }
                $db->beginTransaction();
                try {
                    foreach($faArr as $faData) {
                        $fa->insert($faData);
                    }
                    foreach($sonArr as $sonData) {
                        $son->insert($sonData);
                    }
                    // 旧版作废
                    if($type == 'bom') {
                        $bosoleteData = array("state" => "Obsolete");
                        $fa->update($bosoleteData, $obsoleteWhere);
                    }
                    $db->commit(); //执行commit
                } catch (Exception $e) {
                    $db->rollBack(); //如果出现错误，执行回滚操作
                    $result['result'] = false;
                    $result['info'] = $e->getMessage();

                    fclose($file);
                    echo Zend_Json::encode($result);
                    exit;
                }

                fclose($file);
            }
        }

        echo Zend_Json::encode($result);

        exit;
    }

    public function getreasonAction() {
        $where = "type=6";
        $codemaster = new Admin_Model_Codemaster();
        $data = $codemaster->getList($where);
        // 转为json格式并输出
        echo Zend_Json::encode($data);

        exit;
    }

    public function verifyexistsAction() {
        $return = array(
            "success" => true,
            "info" => ""
        );
        $result = $this->getRequest()->getParams();
        if(isset($result['nid']) && $result['nid']) {
            $nid = $result['nid'];
            $type = $result['type'];
            $fadev = new Product_Model_Fadev();
            $data = $fadev->getFaList("nid=$nid and type='$type'");
            if(count($data) == 0) {
                $return['success'] = false;
            }
        } else {
            $return['success'] = false;
            $return['info'] = "没有获取到ID，请刷新页面再重试";
        }
        // 转为json格式并输出
        echo Zend_Json::encode($return);
        exit;
    }
    public function removeexistsAction() {
        $return = array(
            "success" => true,
            "info" => ""
        );
        $result = $this->getRequest()->getParams();
        if(isset($result['nid']) && $result['nid']) {
            $nid = $result['nid'];
            $type = $result['type'];
            $fadev = new Product_Model_Fadev();
            $sondev = new Product_Model_Sondev();
            $data = $fadev->getFaList("nid=$nid and type='$type'");
            $keys = array();
            foreach($data as $row) {
                $keys[] = $row['recordkey'];
            }
            $fadev->delete("nid=$nid and type='$type'");
            $sondev->delete("recordkey in (".implode(',', $keys).")");
        } else {
            $return['success'] = false;
            $return['info'] = "没有获取到ID，请刷新页面再重试";
        }
        // 转为json格式并输出
        echo Zend_Json::encode($return);
        exit;
    }

}

