<?php
/**
 * 2013-9-8
 * @author      mg.luo
 * @abstract    物料编码申请
 */
class Product_BomController extends Zend_Controller_Action
{

    public function indexAction()
    {

    }

    public function getlistAction() {
        $fa = new Product_Model_Fa();
        $fa->updateArchiveTime();
        
        $request = $this->getRequest()->getParams();
        $limit = $request['limit'];
        $start = $request['start'];
        $db = $fa->getAdapter();
        $whereSearch = "1=1";
        foreach ($request as $k => $v) {
            if ($v) {
                if($k == 'search_key') {
                    $whereSearch .= " and ";
                    if(preg_match('/[a-zA-Z]\d/', $v)) {
                        // 可能是器件位置
                        $sonData = $db->query("select group_concat(DISTINCT recordkey) as recordkey from oa_product_bom_son where partposition like '%$v%'")->fetchObject();
                        if($sonData && $sonData->recordkey) {
                            $recordkey = $sonData->recordkey;
                            $whereSearch .= " t1.recordkey in (".$recordkey.") and ";
                        }
                        
                    }

                    $cols = array("t1.remark", "t5.model_internal", "t1.code", "t3.description", "t3.name");
                    $arr=preg_split('/\s+/',trim($v));
                    for ($i=0;$i<count($arr);$i++) {
                        $tmp = array();
                        foreach($cols as $c) {
                            $tmp[] = "ifnull($c,'')";
                        }
                        $arr[$i] = "concat(".implode(',', $tmp).") like '%".$arr[$i]."%'";
                    }
                    $whereSearch .= ' '.join(' AND ', $arr);
//                     $whereSearch .= " ifnull(t1.remark,'') like '%$v%' or ifnull(t3.name,'') like '%$v%' or ifnull(t3.description,'') like '%$v%' or ifnull(t5.model_internal, '') like '%$v%')";
                } else if ("search_archive_date_from" == $k && $v) {
                    $whereSearch .= " and t1.bom_upd_time >= '" . str_replace('T', ' ', $v) . "'";
                } else if ("search_archive_date_to" == $k && $v) {
                    $whereSearch .= " and t1.bom_upd_time <= '" . str_replace('T00:00:00', ' 23:59:59', $v) . "'";
                } else if ("search_fa" == $k && $v) {
                    $whereSearch .= " and t1.code like '%" . $v . "%'";
                } else if ("search_son" == $k && $v) {
                    $recordkey = "";
                    $sonData = $db->query("select group_concat(DISTINCT recordkey) as recordkey from oa_product_bom_son where code like '%$v%'")->fetchObject();
                    if($sonData && $sonData->recordkey) {
                        $recordkey = $sonData->recordkey;
                    }
                    if(!$recordkey) {
                        $recordkey = "0";
                        $recordkeys = array("0");
                    } else {
                        $recordkeys = array();
                        foreach(explode(',', $recordkey) as $rk) {
                            $recordkeys = $this->getRecordkeyRecursive($rk, $recordkeys);
                        }
                        $recordkeys = array_unique($recordkeys);
                    }
                    $whereSearch .= " and t1.recordkey in (".implode(',', $recordkeys).")";
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

        $fa = new Product_Model_Fa();
        $son = new Product_Model_Son();
        // 获取物料数据
        $data = $fa->getList($whereSearch, $start, $limit);
        $totalCount = $fa->getListCount($whereSearch);
        for($i = 0; $i < count($data); $i++) {
            if(!$data[$i]['type'] || $data[$i]['type'] == 'new') {
                $data[$i]['archive_time'] = strtotime($data[$i]['archive_time_new']);
                $data[$i]['remark_head'] = $data[$i]['remark_new'];
                $data[$i]['description_head'] = $data[$i]['description_new'];
                $data[$i]['upd_type'] = 'new';
            } else {
                $data[$i]['archive_time'] = strtotime($data[$i]['archive_time_upd']);
                $data[$i]['remark_head'] = $data[$i]['remark_upd'];
                $data[$i]['description_head'] = $data[$i]['description_upd'];
            }
            if(!$data[$i]['archive_time'] && $data[$i]['bom_upd_time']) {
                $data[$i]['archive_time'] = strtotime($data[$i]['bom_upd_time']);
            }
            
            if($data[$i]['bom_file']) {
                $codes = array();
                foreach(explode(',', $data[$i]['bom_file']) as $code) {
                    $codes[] = "'".$code."'";
                }
                $sql = "select group_concat(t1.ver) as ver, group_concat(t2.description) as des from oa_doc_files t1 inner join oa_doc_code t2 on t1.code = t2.code where t1.state='Active' and t1.`code` in (".implode(',', $codes).")";
                $res = $fa->getAdapter()->query($sql)->fetchObject();
                if($res && $res->ver) {
                    $data[$i]['file_ver'] = $res->ver;
                    $data[$i]['file_desc'] = $res->des;
                }
            }
        }
        $result = array(
            "totalCount" => $totalCount,
            "topics" => $data
        );
        // 转为json格式并输出
        echo Zend_Json::encode($result);

        exit;
    }

    private function getRecordkeyRecursive($recordkey, $result) {
        $fa = new Product_Model_Fa();
        $db = $fa->getAdapter();
        $result[] = $recordkey;
        // 找到fa中的code
        $faData = $db->query("select distinct code from oa_product_bom_fa where state != 'Obsolete' and recordkey = '$recordkey'")->fetchObject();
        if($faData && $faData->code) {
            $code = $faData->code;
            // 找到使用此code的子bom
            $sonData = $db->query("select group_concat(recordkey) as recordkeys from oa_product_bom_son where code = '$code'")->fetchObject();
            if($sonData && $sonData->recordkeys) {
                $recordkeys = $sonData->recordkeys;
                foreach(explode(',', $recordkeys) as $k) {
                    $result = $this->getRecordkeyRecursive($k, $result);
                }
                return $result;
            } else {
                return $result;
            }
        } else {
            return $result;
        }
    }

    public function getfaAction() {
        $result = $this->getRequest()->getParams();
        $recordkey = $result['recordkey'];
        $data = array();
        if($recordkey) {
            $fa = new Product_Model_Fa();
            $where = "t1.recordkey = $recordkey";
            $data = $fa->getList($where, null, null);
            for($i = 0; $i < count($data); $i++) {
                if(($typeId = $data[$i]['materiel_type']) != '') {
                    $typeName = $this->getTypeByConnect($typeId, '');
                    $data[$i]['type_name'] = $typeName;
                }
            }
        }
        // 转为json格式并输出
        echo Zend_Json::encode($data);

        exit;
    }

    public function getsonAction() {
        $result = $this->getRequest()->getParams();
        $recordkey = $result['recordkey'];
        $data = array();
        if($recordkey) {
            $son = new Product_Model_Son();
            $data = $son->getSon($recordkey);
        }
        // 转为json格式并输出
        echo Zend_Json::encode($data);

        exit;
    }

    public function getmaterielAction() {
        $request = $this->getRequest()->getParams();
        $whereSearch = "1=1";
        foreach ($request as $k => $v) {
            if ($v) {
                if($k == "search_state") {
                    continue;
                }
                $col = str_replace('search_', '', $k);
                if ($col != $k) {
                    // 查询条件
                    $whereSearch .= " and ifnull(t1." . $col . ",'') like '%" . $v . "%'";
                }
            }
        }
        $model = "";
        if(isset($request["model"]) && $request["model"]) {
            $model = $request["model"];
        }
        $type = "";
        if(isset($request["type"]) && $request["type"]) {
            $type = $request["type"];
        }

        $materiel = new Product_Model_Materiel();
        $record = new Dcc_Model_Record();
        $db = $materiel->getAdapter();
        $existsIds = array();
        // 获取当前正在流程中的id
        if($model == 'fa' && $type == 'new') {
            $table = "oa_product_bom_fa_dev";
            $bomIds = $db->query("select t1.id from $table t1 inner join oa_product_bom_new t2 on t1.nid=t2.id and t1.type = '$type' where t2.state = 'Reviewing' or t2.state = 'Return' or t2.state = 'Active'")->fetchAll();
            if($bomIds && count($bomIds) > 0) {
                foreach($bomIds as $ids) {
                    $existsIds[] = $ids['id'];
                }
            }
            //  已归档的
            $whereSearch .= " and t1.id not in (select t.id from oa_product_materiel t inner join oa_product_bom_fa tt where t.code=tt.code)";
            
            // 获取物料数据
            $data = $materiel->getBomList($whereSearch, 0, 100);
        } else if($model== 'fa' && ($type == 'DEV' || $type == 'ECO')) {
            $table = "oa_product_bom_fa_dev";
            $bomIds = $db->query("select t1.id from $table t1 inner join oa_product_bom_upd t2 on t1.nid=t2.id and t1.type = '$type' where t2.state = 'Reviewing' or t2.state = 'Return'")->fetchAll();
            if($bomIds && count($bomIds) > 0) {
                foreach($bomIds as $ids) {
                    $existsIds[] = $ids['id'];
                }
            }
            // 已存在的BOM
            $sql = "select distinct id from oa_product_bom_fa";
            /* if($type == 'ECO') {
                $sql = "select distinct id from oa_product_bom_fa where state = 'MBOM'";
            } */
            $results = $db->query($sql)->fetchAll();
            $bomIds = array();
            foreach($results as $row) {
                $bomIds[] = $row['id'];
            }
            $ids = array();
            if(count($bomIds) > 0) {
                foreach($bomIds as $id) {
                    if(!in_array($id, $ids)) {
                        $ids[] = $id;
                    }
                }
            }
            if(count($ids) > 0) {
                $whereSearch .= " and t1.id in (".implode(',', $ids).")";
            }
            // 获取物料数据
            $data = $materiel->getBomList($whereSearch, 0, 100);
        } else if($model == 'son') {
            /* if($type == 'ECO') {
                $whereSearch .= " and t1.state = 'APL'";
            } */
            $data = $materiel->getArchiveList($whereSearch, 0, 100);
        }
        $tmps = array();
        for($i = 0; $i < count($data); $i++) {
            if(in_array($data[$i]['id'], $existsIds)) {
                continue;
            }
            if(($typeId = $data[$i]['type']) != '') {
                $typeName = $this->getTypeByConnect($typeId, '');
                $data[$i]['type_name'] = $typeName;
            }
            $data[$i]['remark1'] = $data[$i]['remark'];
            $data[$i]['sid'] = $data[$i]['id'].time();
            $data[$i]['remark'] = "";
            $data[$i]['qty'] = "1";
            if(isset($data[$i]['model_internal']) && !$data[$i]['project_no']) {
                $data[$i]['project_no'] = $data[$i]['model_internal'];
            }
            $tmps[] = $data[$i];
        }
        // 转为json格式并输出
        echo Zend_Json::encode($tmps);

        exit;
    }

    public function getbomforselAction() {
        // 请求参数
        $request = $this->getRequest()->getParams();
        $limit = $request['limit'];
        $start = $request['start'];
        $key = isset($request['search_key']) ? $request['search_key'] : '';

        $fa = new Product_Model_Fa();
        $materiel = new Product_Model_Materiel();
        $materielTable = $materiel->getName();
        $product = new Product_Model_Catalog();
        $productTable = $product->getName();
        // 查询条件
        /*$cols = array("code", "description");
        $arr=preg_split('/\s+/',trim($key));
        for ($i=0;$i<count($arr);$i++) {
            $tmp = array();
            foreach($cols as $c) {
                $tmp[] = "ifnull($c,'')";
            }
            $arr[$i] = "concat(".implode(',', $tmp).") like '%".$arr[$i]."%'";
        }*/
        $where = $fa->getName().".state!='Obsolete' and (".$fa->getName().".code like '%$key%' or ".$fa->getName().".code like '%$key%')";

        $join = array(
            array(
                'type' => INNERJOIN,
                'table' => $materielTable,
                'condition' => $materielTable.'.code = '.$fa->getName().'.code',
                'cols' => array('description')
            ),
            array(
                'type' => LEFTJONIN,
                'table' => $productTable,
                'condition' => $productTable.'.id = '.$fa->getName().'.project_no',
                'cols' => array('model_internal')
            )
        );

        $total = $fa->getJoinCount($where, $join);
        $data = array();
        if($total > 0) {
            $data = $fa->getJoinList($where, $join, null, array($fa->getName().'.code'));
        }
        $resutl = array('total' => $total, 'rows' => $data);
        echo Zend_Json::encode($resutl);

        exit;
    }

    public function getfilecodeAction() {
        $request = $this->getRequest()->getParams();
        $whereSearch = "1=1";
        foreach ($request as $k => $v) {
            if ($v) {
                if($k == "search_state") {
                    continue;
                }
                $col = str_replace('search_', '', $k);
                if ($col != $k) {
                    // 查询条件
                    $whereSearch .= " and ifnull(t1." . $col . ",'') like '%" . $v . "%'";
                }
            }
        }

        $filecode = new Dcc_Model_Code();
        $data = $filecode->getArchivedCode($whereSearch);

        for ($i = 0; $i < count($data); $i++) {
            $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
            $data[$i]['update_time'] = strtotime($data[$i]['update_time']);
            $data[$i]['active'] = $data[$i]['active'] == 1 ? true : false;
        }
        // 转为json格式并输出
        echo Zend_Json::encode($data);

        exit;
    }

    public function getbomconfigAction() {
        $request = $this->getRequest()->getParams();
        $type = new Product_Model_Type();
        $data = $type->getAdapter()->query("select * from oa_product_bom_config")->fetchAll();
        $dev_form_id = "";
        $eco_form_id = "";
        $new_form_id = "";
        foreach($data as $row) {
            $formname = $row['form'];
            $form = $type->getAdapter()->query("select id from oa_admin_model where name = '".$formname."'")->fetchObject();
            if($form) {
                if($row['type'] == 'DEV') {
                    $dev_form_id = $form->id;
                } else if($row['type'] == 'ECO') {
                    $eco_form_id = $form->id;
                } else if($row['type'] == 'new') {
                    $new_form_id = $form->id;
                }
            }
        }
        $result = array("success" => true,
                        "new_form_id" => $new_form_id,
                        "dev_form_id" => $dev_form_id,
                        "eco_form_id" => $eco_form_id);
        // 转为json格式并输出
        echo Zend_Json::encode($result);
        exit;
    }
    
    public function getexistsonAction() {
        $result = $this->getRequest()->getParams();
        $code = $result['code'];
        $data = array();
        if($code) {
            $fa = new Product_Model_Fa();
            $son = new Product_Model_Son();
            $db = $fa->getAdapter();
            // 获取最新版
            $recordkeys = $db->query("select recordkey from oa_product_bom_fa where code = '$code' order by ver desc limit 1")->fetchObject();
            if($recordkeys && $recordkeys->recordkey) {
                $recordkey = $recordkeys->recordkey;
                $data = $son->getSon($recordkey);
            }

        }
        // 转为json格式并输出
        echo Zend_Json::encode($data);

        exit;
    }

    /**
     * @abstract    取得物料信息
     * @return      物料代码+物料代码
     */
    public function getmaterielforselAction() {
        $result = $this->getRequest()->getParams();

        $materiel = new Product_Model_Materiel();
        if(isset($result['q']) && $result['q']) {
            $query = $result['q'];
            if(stripos($query, ',') !== false) {
                $query = substr($query, strripos($query, ',')+1);
            }
            $where = "code like '%$query%' or name like '%$query%' or description like '%$query%'";
        } else {
            $where = "1=1";
        }

        $data = $materiel->getListBySel($where, 0, 50);
        $result = array();
        for($i = 0; $i < count($data); $i++) {
            $result[$i] = array();
            $result[$i]['code'] = $data[$i]['code'];
            $result[$i]['name'] = $data[$i]['name'];
            $result[$i]['description'] = $data[$i]['description'];
            $result[$i]['state'] = $data[$i]['state'];
            /*if(($typeId = $data[$i]['type']) != '') {
                $typeName = $this->getTypeByConnect($typeId, '');
                $result[$i]['type_name'] = $typeName;
            }*/
        }
        // 转为json格式并输出
        echo Zend_Json::encode($result);

        exit;
    }

    /**
     * bom dosage
     * @param $id
     * @param $name
     * @return string
     */
    public function getmaterieldosageAction() {
        $code = $this->getRequest()->getParam('code');
        $data = array();
        if($code) {
            $fa = new Product_Model_Fa();
            $son = new Product_Model_Son();
            $materiel = new Product_Model_Materiel();
            $catalog = new Product_Model_Catalog();
            // first step: find recordkey from bom_son
            $recordkeyData = $son->getJoinList("code = '$code'", array(), array('recordkey', 'code', 'qty', 'partposition', 'replace'));
            $recordkeys = $codes = $qtys = $partpositions = $replaces = array();
            foreach($recordkeyData as $r) {
                if(!in_array($r['recordkey'], $recordkeys)) {
                    $recordkey = $r['recordkey'];
                    $recordkeys[] = $recordkey;
                    $codes[$recordkey] = $r['code'];
                    $qtys[$recordkey] = $r['qty'];
                    $partpositions[$recordkey] = $r['partposition'];
                    $replaces[$recordkey] = $r['replace'];
                }
            }

            // second step: query bom code from bom_son with recordkey
            if(count($recordkeys) > 0) {
                $join = array(
                    array(
                        'type' => INNERJOIN,
                        'table' => $materiel->getName(),
                        'condition' => $materiel->getName().'.code = '.$fa->getName().'.code',
                        'cols' => array('name', 'description')
                    ),
                    array(
                        'type' => LEFTJONIN,
                        'table' => $catalog->getName(),
                        'condition' => $catalog->getName().'.id = '.$fa->getName().'.project_no',
                        'cols' => array('project_no_name' => 'model_internal')
                    )
                );
                $where = $fa->getName().".recordkey in (".implode(',', $recordkeys).") and ".$fa->getName().".state != 'Obsolete'";
                $data = $fa->getJoinList($where, $join, array('sid', 'recordkey', 'code', 'state', 'ver', 'bom_upd_time'));
                for($i = 0; $i < count($data); $i++) {
                    $recordkey = $data[$i]['recordkey'];
                    $data[$i]['archive_time'] = strtotime($data[$i]['bom_upd_time']);
                    $data[$i]['qty'] = $qtys[$recordkey];
                    $data[$i]['partposition'] = $partpositions[$recordkey];
                    $data[$i]['replace'] = $replaces[$recordkey];
                }
            }
        }
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

        $newbom = new Product_Model_Newbom();
        $record = new Dcc_Model_Record();
        // 保存数据
        $data = array(
            "description" =>  isset($val->description) ? $val->description : "",
            "state"       =>   'Draft',
            "remark"      =>   isset($val->remark) ? $val->remark : "",
            "create_time" => $now,
            "create_user" => $user,
            "update_time" => $now,
            "update_user" => $user
        );
        try {
            $id = $newbom->insert($data);
            if ($id) {
                $result['nid'] = $id;

                // 操作记录
                $data = array(
                        'type'             => "bom",
                        'table_name'       => "oa_product_bom_new",
                        'table_id'         => $id,
                        'handle_user'      => $user,
                        'handle_time'      => $now,
                        'action'           => "新建",
                        'ip'               => $_SERVER['REMOTE_ADDR']
                );
                $record->insert($data);

                // 自定义字段
                $attrval = new Admin_Model_Formval();
                $menu = 'oa_product_bom_new_' . $id;
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
     * 复制从功能实现
     */
    public function getcopysonAction() {
        $pid = $this->getRequest()->getParam('pid');
        $faid = $this->getRequest()->getParam('faid');
        $son = new Product_Model_Son();
        $data = array();
        if($pid && $faid) {
            $sql = "select recordkey from oa_product_bom_fa where id = $faid order by sid desc";
            $r = $son->getAdapter()->query($sql)->fetchObject();
            if($r && $r->recordkey) {
                $recordkey = $r->recordkey;
                $data = $son->getListByRecordkey($recordkey);
                echo Zend_Json::encode($data);
                exit;
            }
        }
        echo Zend_Json::encode($data);
        exit;
        
    }

    public function autosaveAction(){
        // 返回值数组
        $result = array(
            'success' => true,
            'result'  => true,
            'info' => '保存成功'
        );

        $request = $this->getRequest()->getParams();

        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];

        $val = (object)$request;
        $fas = array();
        if($val->fa) {
            $fas = Zend_Json::decode($val->fa);

        }
        $sons = array();
        if($val->son) {
            $sons = Zend_Json::decode($val->son);
        }
        $type = $val->type;

        if($type == 'edit') {
            $fadev = new Product_Model_Fa();
            $sondev = new Product_Model_Son();
        } else {
            $fadev = new Product_Model_Fadev();
            $sondev = new Product_Model_Sondev();
        }

        $materiel = new Product_Model_Materiel();
        $db = $fadev->getAdapter();

        // 保存数据到临时表
        $id = $val->nid;
        $recordkey = "";
        if(isset($val->recordkey)) {
            $recordkey = $val->recordkey;
        }
        if($type == 'edit' && !$recordkey) {
            $result['result'] = false;
            $result['info'] = "参数错误，保存失败";

            echo Zend_Json::encode($result);
            exit;
        }
        if(!$id && $type != 'edit') {
            // 如果不存在表单数据，则创建一条
            if($type == 'new') {
                $bomModel = new Product_Model_Newbom();
            } else {
                $bomModel = new Product_Model_Updbom();

            }

            // 保存数据
            $data = array(
                "state"       => 'Draft',
                "create_time" => $now,
                "create_user" => $user,
                "update_time" => $now,
                "update_user" => $user
            );
            try {
                $id = $bomModel->insert($data);
            } catch (Exception $e) {
                $result['result'] = false;
                $result['info'] = $e->getMessage();

                echo Zend_Json::encode($result);
                exit;
            }
        }

        if($type == 'edit') {
            // 2 保存fa数据
            $faDatas = array();
            $keyArr = array();

            // 清除原先的数据
            $where = "recordkey=".$recordkey;
            $sondev->delete($where);
            // 3 保存son数据
            $fa = $fas[0];
            $faData = array(
                "project_no"  =>   isset($fa['project_no']) ? $fa['project_no'] : 0,
                "bom_file"  =>   isset($fa['bom_file']) ? $fa['bom_file'] : "",
                "remark"      =>   isset($fa['remark']) ? $fa['remark'] : ""
            );
            $sonDatas = array();
            foreach($sons as $son) {
                if(!$son['id']) continue;
                $row = $materiel->getById($son['id']);
                if(!$row['id']) continue;
                $sonData = array(
                    "nid"         =>   0,
                    "recordkey"   =>   $recordkey,
                    "pid"         =>   $son['pid'],
                    "id"          =>   $row['id'],
                    "code"        =>   $row['code'],
                    "qty"         =>   isset($son['qty']) ? $son['qty'] : 1,
                    "remark"      =>   isset($son['remark']) ? $son['remark'] : "",
                    "partposition"=>   isset($son['partposition']) ? $son['partposition'] : "",
                    "replace"     =>   isset($son['replace']) ? $son['replace'] : ""
                );
                $sonDatas[] = $sonData;
            }
            $db->beginTransaction();
            try {
                $fadev->update($faData, $where);
                foreach($sonDatas as $sonData) {
                    $sondev->insert($sonData);
                }
                $db->commit(); //执行commit
                $result['info'] = "编辑成功";
            } catch (Exception $e) {
                $db->rollBack(); //如果出现错误，执行回滚操作
                $result['result'] = false;
                $result['info'] = $e->getMessage();

                echo Zend_Json::encode($result);
                exit;
            }
        } else {
            // 2 保存fa数据
            $faDatas = array();
            $keyArr = array();

            // 清除原先的数据
            $delWhere = "nid=".$id;
            $fadev->delete($delWhere);
            $sondev->delete($delWhere);
            // 只取一次最大值，可能遇到重号
            $maxkeys1 = $db->query("select ifnull(max(recordkey),0) + 1 as maxkey from oa_product_bom_fa_dev")->fetchObject();
            $maxkeys2 = $db->query("select ifnull(max(recordkey),0) + 1 as maxkey from oa_product_bom_fa")->fetchObject();
            $recordkey1 = $maxkeys1->maxkey;
            $recordkey2 = $maxkeys2->maxkey;
            $recordkey = max($recordkey1, $recordkey2);
            $faArr = array();
            foreach($fas as $fa) {
                $fastate = "EBOM";
                if(!$fa['id']) continue;
                $row = $materiel->getById($fa['id']);
                if(!$row['id']) continue;

                $ver = "";
                if($type == 'new') {
                    $ver = "1.0";
                } else {
                    // 获取最新版本
                    $vers = $db->query("select (max(ver) + 0.1) as ver, state from oa_product_bom_fa where code = '".$row['code']."'")->fetchObject();
                    if($vers && $vers->ver) {
                        $ver = round($vers->ver,1);
                    }
                    if($vers && $vers->state && ($vers->state == 'EBOM' || $vers->state == 'MBOM')) {
                        $fastate = $vers->state;
                    }
                    if(!$ver) {
                        $result['result'] = false;
                        $result['info'] = "BOM：".$row['code']."版本错误！";
                        echo Zend_Json::encode($result);
                        exit;
                    }
                }

                $keyArr[$row['id']] = $recordkey;
                $faArr[] = $row['id'];
                if($type == 'MBOM') {
                    $fastate = 'MBOM';
                }
                if(!$fastate) {
                    $fastate = 'EBOM';
                }
                $faData = array(
                    "nid"         =>   $id,
                    "recordkey"   =>   $recordkey,
                    "id"          =>   $row['id'],
                    "code"        =>   $row['code'],
                    "project_no"  =>   isset($fa['project_no']) ? $fa['project_no'] : 0,
                    "bom_file"    =>   isset($fa['bom_file']) ? $fa['bom_file'] : "",
                    "qty"         =>   1,
                    "state"       =>   $fastate,
                    "ver"         =>   $ver,
                    "type"        =>   $type,
                    "remark"      =>   isset($fa['remark']) ? $fa['remark'] : ""
                );
                $faDatas[] = $faData;
                $recordkey++;
            }
            // 3 保存son数据
            $sonDatas = array();
            foreach($sons as $son) {
                if(!$son['id'] || !$son['pid']) continue;
                $row = $materiel->getById($son['id']);
                if(!$row['id']) continue;
                if(!in_array($son['pid'], $faArr) || !$keyArr[$son['pid']]) continue;
                $sonData = array(
                    "nid"         =>   $id,
                    "recordkey"   =>   $keyArr[$son['pid']],
                    "pid"         =>   $son['pid'],
                    "id"          =>   $row['id'],
                    "code"        =>   $row['code'],
                    "qty"         =>   isset($son['qty']) ? $son['qty'] : 1,
                    "remark"      =>   isset($son['remark']) ? $son['remark'] : "",
                    "partposition"=>   isset($son['partposition']) ? $son['partposition'] : "",
                    "replace"     =>   isset($son['replace']) ? $son['replace'] : ""
                );
                $sonDatas[] = $sonData;
            }
            $db->beginTransaction();
            try {

                foreach($faDatas as $faData) {
                    $fadev->insert($faData);
                }
                foreach($sonDatas as $sonData) {
                    $sondev->insert($sonData);
                }
                $db->commit(); //执行commit
                $result['info'] = $id;
            } catch (Exception $e) {
                $db->rollBack(); //如果出现错误，执行回滚操作
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
     * @abstract    保存
     * @return      null
     */
    public function saveAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'result'    => true,
                'info'      => '提交成功'
        );

        $request = $this->getRequest()->getParams();

        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];
        $user_name = $user_session->user_info['user_name'];

        $val = (object)$request;

        $materiel = new Product_Model_Materiel();
        $record = new Dcc_Model_Record();
        $review = new Dcc_Model_Review();
        $employee = new Hra_Model_Employee();
        $mail = new Application_Model_Log_Mail();
        $member = new Admin_Model_Member();
        $fadev = new Product_Model_Fadev();
        $faModel = new Product_Model_Fa();
        $sondev = new Product_Model_Sondev();
        $flow = new Admin_Model_Flow();
        $step = new Admin_Model_Step();
        $db = $fadev->getAdapter();

        $type = $val->upd_type;
        if($type == 'new') {
            $bomModel = new Product_Model_Newbom();
        } else {
            $bomModel = new Product_Model_Updbom();
        }

        // 检查BOM信息是否完整
        if(!isset($val->id) || !$val->id) {
            $result['result'] = false;
            $result['info'] = "BOM信息不完整，请重新编辑";
            echo Zend_Json::encode($result);
            exit;
        }
        $id = $val->id;
        $ismanager = "";
        if (isset($val->ismanager) && $val->ismanager == '1') {
            $ismanager = "1";
            $managerState = $val->state;
        }
        // BOM完整：存在上级物料和下级物料，每个上级物料都有下级物料,替代料都存在
        $bomFinishFlg = true;
        $facount = 0;
        $faData = $fadev->fetchAll("type = '$type' and nid = ".$id)->toArray();
        if(count($faData) > 0) {
            $files = new Dcc_Model_Files();
            foreach($faData as $farow) {
                if($farow['bom_file']) {
                    $fileArr = explode(',', $farow['bom_file']);
                    foreach($fileArr as $filecode) {
                        if($files->fetchAll("code='$filecode' and state ='Active'")->count() == 0) {
                            $result['result'] = false;
                            $result['info'] = "BOM ".$farow['code']."的关联文件：“<b>".$filecode."</b>”不存在！";
                            echo Zend_Json::encode($result);
                            exit;
                        }
                    }
                }
            }
        }
        $sonData = $sondev->fetchAll("nid = ".$id)->toArray();
        if(count($faData) > 0 && count($sonData) > 0) {
            foreach($sonData as $son) {
                if($son['replace'] == "") continue;
                $replace = explode(',', $son['replace']);
                foreach($replace as $r) {
                    if($materiel->fetchAll("code='$r'")->count() == 0) {
                        $result['result'] = false;
                        $result['info'] = "物料".$son['code']."的替代料：<b>".$r."</b>不存在！";
                        echo Zend_Json::encode($result);
                        exit;
                    }
                }


            }
            foreach($faData as $fa) {
                foreach($sonData as $son) {
                    if($fa['recordkey'] == $son['recordkey']) {
                        $facount++;
                        break;
                    }
                }
            }
            if($facount != count($faData)) {
                $bomFinishFlg = false;
            }
        } else {
            $bomFinishFlg = false;
        }
        if(!$bomFinishFlg) {
              $result['result'] = false;
            $result['info'] = "BOM信息不完整，请重新编辑";
            echo Zend_Json::encode($result);
            exit;
        }
        // 下级BOM未归档
        $materielType = new Product_Model_Type();
        $bomArr = array();
        foreach($faData as $fa) {
            if($type == 'new') {
                // 检查上级bom是否已经存在，或者正在申请
                $join = array(
                    array(
                        'type' => INNERJOIN,
                        'table' => $bomModel->getName(),
                        'condition' => $fadev->getName().'.nid = '.$bomModel->getName().'.id'
                    )
                );
                $jwhere = $fadev->getName().".code = '".$fa['code']."' and (".$bomModel->getName().".state = 'Active' or ".$bomModel->getName().".state = 'Reviewing')";
                if($fadev->getJoinCount($jwhere,$join)) {
                    $result['result'] = false;
                    $result['info'] = "BOM“".$fa['code']."”已存在或正在申请";
                    echo Zend_Json::encode($result);
                    exit;
                }
                // 检查bom的状态，不能是作废或者删除
                $bomMateriel = $materiel->getMaterielByCode($fa['code']);
                if(!$bomMateriel) {
                    $result['result'] = false;
                    $result['info'] = "物料“".$fa['code']."”不存在";
                    echo Zend_Json::encode($result);
                    exit;
                }
                if($bomMateriel['state'] == 'Obsolete' || $bomMateriel['state'] == 'Deleted') {
                    $result['result'] = false;
                    $result['info'] = "物料“".$fa['code']."”已作废";
                    echo Zend_Json::encode($result);
                    exit;
                }
                
                
            }
            
            // 检查下级bom是否归档
            foreach($sonData as $son) {
                if(isset($bomArr[$son['id']]) && $bomArr[$son['id']]) {
                    $isbom = true;
                } else if(isset($bomArr[$son['id']]) && !$bomArr[$son['id']]) {
                    $isbom = false;
                } else {
                    $d = $materiel->fetchRow('id = '.$son['id']);
                    $d = $materielType->fetchRow('id = '.$d['type']);
                    $bomArr[$son['id']] = $d['bom'];
                    $isbom = false;
                }
                
                if($isbom) {
                    if($faModel->fetchAll("id = '".$son['id']."'")->count() == 0) {
                        $result['result'] = false;
                        $result['info'] = "子BOM“".$son['code']."”未归档";
                        echo Zend_Json::encode($result);
                        exit;
                    }
                }
            }
        }

        // 根据BOM获取审批流
        $flow_id = "";
        $flowRow = $db->query("select t1.* from oa_admin_flow t1 inner join oa_product_bom_config t2 on t1.flow_name=t2.flow where t2.type='$type'")->fetchObject();
        if($flowRow) {
            $flow_id = $flowRow->id;
        }
        if(!$flow_id) {
            $result['result'] = false;
            $result['info'] = "还没有配置BOM审核流程，请联系管理员配置";
            echo Zend_Json::encode($result);
            exit;
        }
        // 根据流程ID获取阶段
        $step_ids = $flowRow->step_ids;
        if($step_ids) {
            $stepRows = $step->getListByFlow($step_ids);
            $state = "Reviewing";
        }
        $state = "Reviewing";
        if(isset($managerState) && $managerState) {
            $state = $managerState;
        }

        if($type == 'new') {
            $data = array(
                "description" =>  isset($val->description) ? $val->description : "",
                "state"       =>   $state,
                "remark"      =>   isset($val->remark) ? $val->remark : "",
                "update_time" => $now,
                "update_user" => $user
            );
        } else {
            $data = array(
                "upd_type"    =>  isset($val->upd_type) ? $val->upd_type : "",
                "replace_flg" =>  isset($val->replace_flg) ? 1 : 0,
                "description" =>  isset($val->description) ? $val->description : "",
                "upd_reason"  =>  isset($val->upd_reason) ? $val->upd_reason : "",
                "reason_type" =>  isset($val->reason_type) ? $val->reason_type : "",
                "state"       =>   $state,
                "remark"      =>   isset($val->remark) ? $val->remark : "",
                "update_time" => $now,
                "update_user" => $user
            );
        }
        if(isset($managerState) && $managerState == 'Active') {
            $data['archive_time'] = $now;
        }

        try{
            $bomModel->update($data, "id=".$id);

            // 自定义字段
            $attrval = new Admin_Model_Formval();
            if($type == 'new') {
                $table = "oa_product_bom_new";
                $recordType = "bom";
            } else if($type == 'ECO'){
                $table = "oa_product_bom_eco";
                $recordType = "ecobom";
            } else if($type == 'DEV'){
                $table = "oa_product_bom_dev";
                $recordType = "devbom";
            }
            $menu = $table.'_' . $id;
            $attrval->delete("menu = '".$menu."'");
            foreach($request as $field => $value) {
                if(stripos($field, "intelligenceField") !== false && $value) {
                    $attrId = str_replace("intelligenceField", "", $field);
                    $formval = array(
                               'attrid' => $attrId,
                               'value' => $value,
                               'menu' => $menu
                    );
                    $attrval->insert($formval);
                }
            }

            $action = "申请";
            if($record->fetchAll("type='$recordType' and table_id=$id")->count() > 0) {
                $action = "编辑";
            }

            // 操作记录
            $data = array(
                    'type'             => $recordType,
                    'table_name'       => $table,
                    'table_id'         => $id,
                    'handle_user'      => $user,
                    'handle_time'      => $now,
                    'action'           => $action,
                    'ip'               => $_SERVER['REMOTE_ADDR']
            );
            $record->insert($data);

            // 管理员将状态改为审核中也会触发审批流程
            if(!$ismanager || (isset($managerState) && $managerState == 'Reviewing')) {
                // 审核流程
                // 把阶段信息插入review记录
                // 删除已有流程
                $review->delete("type='$recordType' and file_id = $id");
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
                        'type' => "$recordType",
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
                        $boms = array();
                        foreach($faData as $fa) {
                            if($type == 'new') {
                                $boms[] = $fa['code'];
                            } else {
                                $boms[] = $fa['code']." V".$fa['ver'];
                            }
                        }
                        if($type == 'new') {
                            $content = "你有新BOM归档申请需要审核，<p>" .
                                       "<b>BOM号：</b>" . implode(',', $boms) . "</p>" .
                                       "<p><b>描述：</b>" . $val->description . "</p>" .
                                       "<p><b>备注：</b>" . $val->remark . "</p>" .
                                       "<p><b>申请人：</b>" . $user_name . "</p>" .
                                       "<p><b>申请时间：</b>" . $now . "</p>" .
                                       "<p>请登录系统查看详情！</p>";
                        } else {
                            $reson_type = "";
                            if($val->reason_type) {
                                $codemaster = new Admin_Model_Codemaster();
                                $mstData = $codemaster->getList("type=6 and code='".$val->reason_type."'");
                                if($mstData && count($mstData) > 0) {
                                    $reson_type = $mstData[0]['text'];
                                }
                            }
                            $content = "你有新BOM升版申请需要审核，<p>" .
                                       "<b>BOM号：</b>" . implode(',', $boms) . "</p>" .
                                       "<p><b>升版类型：</b>" . $val->upd_type . "</p>" .
                                       "<p><b>升版原因分类：</b>" . $reson_type . "</p>" .
                                       "<p><b>升版原因：</b>" . $val->upd_reason . "</p>" .
                                       "<p><b>升版描述：</b>" . $val->description . "</p>" .
                                       "<p><b>备注：</b>" . $val->remark . "</p>" .
                                       "<p><b>申请人：</b>" . $user_name . "</p>" .
                                       "<p><b>申请时间：</b>" . $now . "</p>" .
                                       "<p>请登录系统查看详情！</p>";
                        }
                        $mailData = array(
                            'type' => 'BOM归档审批',
                            'subject' => 'BOM归档审批',
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
            } else if(isset($managerState) && $managerState == 'Active') {
                $bomfaData = $fadev->fetchAll("type = '$type' and nid=".$id)->toArray();
                // 如果是多个BOM，需要拆分
                foreach($bomfaData as $bomfa) {
                    // 升版的情况，旧版作废
                    $facode = $bomfa['code'];
                    if($type != 'new') {
                        $obsoleteData = array(
                            "state" => "Obsolete"
                        );
                        $obsoleteWhere = "code = '$facode'";
                        $faModel->update($obsoleteData, $obsoleteWhere);
                    }

                    $recordkey = $bomfa['recordkey'];
                    $sql = "insert into oa_product_bom_fa (nid, recordkey, id, code, project_no, bom_file, qty, state, ver, type, remark) select nid, recordkey, id, code, project_no, bom_file, qty, 'EBOM', ver, type, remark from oa_product_bom_fa_dev where recordkey = $recordkey";
                    $db->query($sql);
                    $sql = "insert into oa_product_bom_son (nid, recordkey, pid, id, code, qty, partposition, `replace`, remark) select nid, recordkey, pid, id, code, qty, partposition, `replace`, remark from oa_product_bom_son_dev where recordkey = $recordkey";
                    $db->query($sql);
                }
                // 更新所有record的finish_flg为0
                $reviewWhere = "type = '$recordType' and file_id = $id";
                // 审核情况
                $reviewData = array(
                    "actual_user" => $user,
                    "finish_time" => $now,
                    "finish_flg" => '1'
                );
                // 更新审核情况
                $review->update($reviewData, $reviewWhere);
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

        $data = $review->getList($where, "materiel");
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

        $record = new Dcc_Model_Record();
        $review = new Dcc_Model_Review();
        $employee = new Hra_Model_Employee();
        $db = $employee->getAdapter();
        $fadev = new Product_Model_Fadev();
        $fa = new Product_Model_Fa();
        $sondev = new Product_Model_Sondev();

        $id = $val->id;
        $remark = $val->remark;
        $pass = $val->review_result;
        $type = $val->type;
        $publish = false;

        if($type == 'new') {
            $table = "oa_product_bom_new";
            $recordType = "bom";
            $bomModel = new Product_Model_Newbom();
        } else if($type == 'ECO'){
            $table = "oa_product_bom_eco";
            $recordType = "ecobom";
            $bomModel = new Product_Model_Updbom();
        } else if($type == 'DEV'){
            $table = "oa_product_bom_dev";
            $recordType = "devbom";
            $bomModel = new Product_Model_Updbom();
        }

        // 获取物料信息
        $bomData = $bomModel->getOne($id);
        if(!$bomData) {
            $result['result'] = false;
            $result['info'] = "数据状态已改变";

            echo Zend_Json::encode($result);
            exit;
        }
        $review_id = $bomData->review_id;

        // 获取当前审核情况
        // 如果record记录被删除或状态已改变，报错
        $reviewWhere = "id = $review_id";
        $reviewRows = $review->getList($reviewWhere, $recordType);
        if (count($reviewRows) == 0 || !$type) {
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
        $bomfaData = $fadev->fetchAll("type = '$type' and nid=".$id)->toArray();

        // 处理记录
        $recordData = array(
            "type" => $recordType,
            "table_name" => $table,
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
                "plan_user" => $plan_user
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
                $reviewWhere = "type = '$recordType' and file_id = $id";
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
                $reviewWhere = "type = '$recordType' and finish_flg = 0 and file_id = $id";
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
                $reviewWhere = "type = '$recordType' and file_id = $id";
                // 审核情况
                $reviewData = array(
                    "actual_user" => $actual_user,
                    "finish_time" => $finish_time,
                    "finish_flg" => $finish_flg
                );
            }
        }

        // 如果所有record的记录的finish_flg 都为1，则发布
        if ($finish_flg == 1 && $review->fetchAll("type = '$recordType' and finish_flg = 0 and file_id = $id")->count() == 1) {
            $publish = true;
            $mData = array(
                "state" => "Active",
                "archive_time" => $now
            );
            $fileWhere = "id = $id";

            $creater = $bomData['create_user'];
            // 如果是多个BOM，需要拆分
            foreach($bomfaData as $bomfa) {
                // 升版的情况，旧版作废
                $facode = $bomfa['code'];
                if($type != 'new') {
                    $obsoleteData = array(
                        "state" => "Obsolete"
                    );
                    $obsoleteWhere = "code = '$facode' and recordkey != '".$bomfa['recordkey']."'";
                    $fa->update($obsoleteData, $obsoleteWhere);
                }

                $recordkey = $bomfa['recordkey'];
                $sql = "insert into oa_product_bom_fa (nid, recordkey, id, code, project_no, bom_file, qty, state, ver, type, remark, create_user) select nid, recordkey, id, code, project_no, bom_file, qty, state, ver, type, remark, '$creater' from oa_product_bom_fa_dev where recordkey = $recordkey";
                $db->query($sql);
                $sql = "insert into oa_product_bom_son (nid, recordkey, pid, id, code, qty, partposition, `replace`, remark) select nid, recordkey, pid, id, code, qty, partposition, `replace`, remark from oa_product_bom_son_dev where recordkey = $recordkey";
                $db->query($sql);
            }
        }

        try {
            // 更新审核情况
            $review->update($reviewData, $reviewWhere);
            // 更新BOM
            if (isset($fileWhere)) {
                $bomModel->update($mData, $fileWhere);
            }
            $this->operate("BOM审批");
        } catch (Exception $e) {
            $result['result'] = false;
            $result['info'] = $e->getMessage();

            echo Zend_Json::encode($result);

            exit;
        }

        // 邮件任务
        // 文件提交者或更新人
        $owner = $bomData['create_user'];
        $dev = false;
        $boms = array();
        foreach($bomfaData as $fa) {
            if($type == 'new') {
                $boms[] = $fa['code'];
            } else {
                $boms[] = $fa['code']." V".$fa['ver'];
            }
        }

        $bomflg = "devbom";
        if($type == 'ECO') {
            $bomflg = "ecobom";
        }

        if($type == 'new') {
            $subject_type = "新BOM申请";
            $content = "<b>BOM号：</b>" . implode(',', $boms) . "</p>" .
                       "<p><b>描述：</b>" . $bomData['description'] . "</p>" .
                       "<p><b>备注：</b>" . $bomData['remark'] . "</p>" .
                       "<p><b>申请人：</b>" . $bomData['creater'] . "</p>" .
                       "<p><b>申请时间：</b>" . $bomData['create_time'] . "</p>" .
                       "<p>请登录系统查看详情！</p>";
        } else {
            $subject_type = "BOM升版";
            $reson_type = "";
            if($bomData['reason_type']) {
                $codemaster = new Admin_Model_Codemaster();
                $mstData = $codemaster->getList("type=6 and code='".$bomData['reason_type']."'");
                if($mstData && count($mstData) > 0) {
                    $reson_type = $mstData[0]['text'];
                }
            }
            $content = "<b>BOM号：</b>" . implode(',', $boms) . "</p>" .
                       "<p><b>升版类型：</b>" . $bomData['upd_type'] . "</p>" .
                       "<p><b>升版原因分类：</b>" . $reson_type . "</p>" .
                       "<p><b>升版原因：</b>" . $bomData['upd_reason'] . "</p>" .
                       "<p><b>升版描述：</b>" . $bomData['description'] . "</p>" .
                       "<p><b>备注：</b>" . $bomData['remark'] . "</p>" .
                       "<p><b>申请人：</b>" . $bomData['creater'] . "</p>" .
                       "<p><b>申请时间：</b>" . $bomData['create_time'] . "</p>" .
                       "<p>请登录系统查看详情！</p>";
        }
        $content = "<p><b>BOM号：</b>" . implode(',', $boms) . "</p><p><b>版本：</b>1.0</p><p><b>描述：</b>" . $bomData['description'] . "</p><p><b>备注：</b>" . $bomData['remark'] . "</p><p><b>申请人：</b>" . $bomData['creater'] . "</p><p><b>申请时间：</b>" . $bomData['create_time'] . "</p><p>请登录系统查看详情！</p>";
        // 发邮件的情况：
        // 1、单站审核结束 $finish_flg = 1 && $publish = false
        if ($finish_flg == 1 && !$publish) {
            $subject = $subject_type . "审批";
            // $to = 下一站审核人
            $current = $review->getCurrent($bomflg, $id);
            $to = $employee->getInfosByOneLine($current['plan_user']);
            //
            $cc = $employee->getInfosByOneLine($owner);
            $cc = $cc['email'];
            $content = "你有一个" . $subject_type . "需要审批，$content";
        }

        // 2、所有审核结束  $publish = true
        if ($publish) {
            $subject = $subject_type . "发布";
            $to = $employee->getInfosByOneLine($owner);
            $cc = $employee->getInfosByOneLine($record->getEmployeeIds($bomData['id'], 'bom'));
            $cc = $cc['email'];
            $content .= "<hr><p><b>审核记录：</b><br>" . str_replace(',','<br>',$record->getHis($bomData['id'], $bomflg))."</p>";
            $content = "你申请的" . $subject_type . "已通过审批，$content";
        }
        // 3、退回 isset($return)
        if (isset($return)) {
            $subject = $subject_type . "退回";
            $to = $employee->getInfosByOneLine($owner);
            $cc = "";
            // 原审核人
            if($reviewRow['plan_user']) {
                $orgUser = $reviewRow['plan_user'];
                $cc = $employee->getInfosByOneLine($orgUser);
                $cc = $cc['email'];
            }
            $content = "你申请的" . $subject_type . "已被退回，<p><b>退回原因：</b>" . $remark . "</p>，$content";
        }
        // 4、转审 $pass == 3
        if ($pass == 3) {
            $subject = $subject_type . "转审";
            $toUser = str_replace('E', '', $val->transfer_id);
            $to = $employee->getInfosByOneLine($toUser);
            // 原审核人
            if($reviewRow['plan_user']) {
                $orgUser = $reviewRow['plan_user'];
                $owner .= ",".$orgUser;
            }
            $cc = $employee->getInfosByOneLine($owner);
            $cc = $cc['email'];
            $content = "有新的" . $subject_type . "被转移到你处审批，$content";
        }

        if(isset($subject)) {
            $mailData = array(
                'type' => $subject_type,
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
            'target' => 'bom',
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

        $newbom = new Product_Model_Newbom();
        $fa = new Product_Model_Fa();
        $son = new Product_Model_Son();
        $fadev = new Product_Model_Fadev();
        $sondev = new Product_Model_Sondev();
        $record = new Dcc_Model_Record();
        $review = new Dcc_Model_Review();

        if (count($deleted) > 0) {
            foreach ($deleted as $val) {
                $id = $val->sid;
                $recordkey = $val->recordkey;
                // 操作记录
                $data = array(
                    'type' => "bom",
                    'table_name' => "oa_product_bom_new",
                    'table_id' => $id,
                    'handle_user' => $user,
                    'handle_time' => $now,
                    'action' => "删除",
                    'ip' => $_SERVER['REMOTE_ADDR']
                );
                try {
                    // 增加record记录
                    $record->insert($data);
                    // 删除bom表
                    $fa->delete("sid = $id");
                    $son->delete("recordkey = $recordkey");
                    if($val->nid) {
                        $fadev->delete("recordkey = $recordkey");
                        $sondev->delete("recordkey = $recordkey");
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
     * @abstract    作废
     * @return      null
     */
    public function obsoleteAction() {
        // 返回值数组
        $result = array(
            'success' => true,
            'result' => true,
            'info' => '作废成功'
        );

        $request = $this->getRequest()->getParams();

        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];

        $json = json_decode($request['json']);

        $deleted = $json->deleted;

        $newbom = new Product_Model_Newbom();
        $fa = new Product_Model_Fa();
        $fadev = new Product_Model_Fadev();
        $record = new Dcc_Model_Record();
        $review = new Dcc_Model_Review();

        if (count($deleted) > 0) {
            foreach ($deleted as $val) {
                $id = $val->sid;
                $recordkey = $val->recordkey;
                // 操作记录
                $data = array(
                    'type' => "bom",
                    'table_name' => "oa_product_bom_new",
                    'table_id' => $id,
                    'handle_user' => $user,
                    'handle_time' => $now,
                    'action' => "作废",
                    'ip' => $_SERVER['REMOTE_ADDR']
                );
                try {
                    // 增加record记录
                    $record->insert($data);
                    // 删除bom表
                    $udata = array(
                        'state' => 'Obsolete'
                    );
                    $fa->update($udata, "sid = $id");
                    $fadev->update($udata, "recordkey = $recordkey");
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

    /*
     * 导出CSV 多文件
     */
    public function exportcsvAction()
    {
        set_time_limit(0);
        /* $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
        ->setLastModifiedBy("Maarten Balliauw")
        ->setTitle("PHPExcel Test Document")
        ->setSubject("PHPExcel Test Document")
        ->setDescription("Test document for PHPExcel, generated using PHP classes.")
        ->setKeywords("office PHPExcel php")
        ->setCategory("Test result file");
        $objPHPExcel->setActiveSheetIndex(0)
        ->setCellValue('A1', 'Hello')
        ->setCellValue('B2', 'world!')
        ->setCellValue('C1', 'Hello')
        ->setCellValue('D2', 'world!');
        $objPHPExcel->setActiveSheetIndex(0)
        ->setCellValue('A4', 'Miscellaneous glyphs')
        ->setCellValue('A5', 'éàèùâêîôûëïüÿäöüç');
        $objPHPExcel->getActiveSheet()->setTitle('Simple');
        $objPHPExcel->setActiveSheetIndex(0);
        $callStartTime = microtime(true);
        
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save(str_replace('.php', '.xlsx', __FILE__)); */
        
        $request = $this->getRequest()->getParams();
        $fa = new Product_Model_Fa();
        $son = new Product_Model_Son();
        $db = $fa->getAdapter();
        $explanded = array();
        $whereSearch = "1=1";
        foreach ($request as $k => $v) {
            if ($v) {
                if($k == 'search_key') {
                    $whereSearch .= " and (ifnull(t1.remark,'') like '%$v%' or ifnull(t3.name,'') like '%$v%' or ifnull(t3.description,'') like '%$v%' or ifnull(t5.model_internal, '') like '%$v%')";
                } else if ("search_archive_date_from" == $k && $v) {
                    $whereSearch .= " and t1.bom_upd_time >= '" . str_replace('T', ' ', $v) . "'";
                } else if ("search_archive_date_to" == $k && $v) {
                    $whereSearch .= " and t1.bom_upd_time <= '" . str_replace('T00:00:00', ' 23:59:59', $v) . "'";
                } else if ("search_fa" == $k && $v) {
                    $whereSearch .= " and t1.code like '%" . $v . "%'";
                } else if ("search_son" == $k && $v) {
                    $recordkey = "";
                    $sonData = $db->query("select group_concat(recordkey) as recordkey from oa_product_bom_son where code like '%$v%'")->fetchObject();
                    if($sonData && $sonData->recordkey) {
                        $recordkey = $sonData->recordkey;
                    }
                    if(!$recordkey) {
                        $recordkey = "0";
                    }
                    $whereSearch .= " and t1.recordkey in ($recordkey)";
                } else if ("search_recordkey" == $k && $v) {
                    $whereSearch .= " and t1.recordkey = '$v'";
                } else if ("explanded" == $k && $v) {
                    $explanded = json_decode($v);
                } else {
                    $col = str_replace('search_', '', $k);
                    if ($col != $k) {
                        // 查询条件
                        $whereSearch .= " and ifnull(t1." . $col . ",'') like '%" . $v . "%'";
                    }
                }
            }
        }
        $updflg = false;
        if(isset($request['upd_flg']) && $request['upd_flg'] == 1) {
            // 只导出升版
            $whereSearch .= " and t1.ver > 1.0";
            $updflg = true;
        }
        if(isset($request['my']) && $request['my'] == 1) {
            $user_session = new Zend_Session_Namespace('user');
            $user = $user_session->user_info['employee_id'];
            if(isset($request['step']) && $request['step'] == 'dev') {
                // 从升版里取
                $data = $db->query("select group_concat(id) as nids from oa_product_bom_upd where create_user = $user and state = 'Active'")->fetchObject();
                if($data && $data->nids) {
                    $nids = $data->nids;
                }
            } else {
                $data = $db->query("select group_concat(id) as nids from oa_product_bom_new where create_user = $user and state = 'Active'")->fetchObject();
                if($data && $data->nids) {
                    $nids = $data->nids;
                }
            }
            if(isset($nids) && $nids) {
                $whereSearch .= " and t1.nid in ($nids)";
            }
        }
        // 获取物料数据
        $data = $fa->getList($whereSearch, null, null);

        print(chr(0xEF).chr(0xBB).chr(0xBF));
        // 获取物料数据
        $data_csv = array();
        $title = array(
                'cnt'               => '#',
                'code'              => '物料号',
                'state'             => '状态',
                'name'              => '物料名称',
                'description'       => '物料描述',
                'project_no_name'   => '产品型号',
                'qty'               => '数量',
                'replace'           => '替代料',
                'partposition'      => '器件位置',
                'remark'            => '备注'
        );
        if(1) {
            $title['upd_type'] = '升版类型';
            $title['reason_type'] = '升版原因分类';
            $title['upd_reason'] = '升版原因';
            $title['description_upd'] = '升版描述';
        }

        $date = date('YmdHsi');
        // 文件名
        if(count($data) == 1) {
            $name = $data[0]['code'];
        } else {
            $zip=new ZipArchive;
            $zipFileName = "boms" . $date . ".zip";
            $zipFile = "../temp/$zipFileName";
            $zipPath = "../temp/boms" . $date . "/";
            if (!file_exists($zipPath)) {
                mkdir($zipPath);
            }
            $name = "bom_list";
        }

        $title = $this->object_array($title);

        $typeids = array();
        $typenames = array();
        $k = 0;
        for($i = 0; $i < count($data); $i++) {
            $push_data = array();
            
            $d = $data[$i];

            $k++;

            $info = array(
                'cnt'               => $k,
                'code'              => $this->ifNull($d, 'code')." V".$d['ver'],
                'state'             => $this->ifNull($d, 'state'),
                'name'              => $this->ifNull($d, 'name'),
                'description'       => $this->ifNull($d, 'description'),
                'project_no_name'   => $this->ifNull($d, 'project_no_name'),
                'qty'               => $d['qty'],
                'replace'           => $this->ifNull($d, 'replace'),
                'partposition'      => $this->ifNull($d, 'partposition'),
                'remark'            => $this->ifNull($d, 'remark'),
            );
            if(1) {
                $info['upd_type'] = $this->ifNull($d, 'upd_type');
                $info['reason_type'] = $this->ifNull($d, 'reason_type_name');
                $info['upd_reason'] = $this->ifNull($d, 'upd_reason');
                $info['description_upd'] = $this->ifNull($d, 'description_upd');
            }
            
            $filename = $info['code'].'-' . $date;
            if(isset($zipPath)) {
                $path = $zipPath . $filename . ".csv";
            } else {
                $path = "../temp/" . $filename . ".csv";
            }
            
            $file = fopen($path, "w");
            fputcsv($file, $title);
            array_push($data_csv, $title);
            
            $info['count'] = 0;
            $push_data[] = $info;
            $push_data = $this->getBomInfo($push_data, $fa, $son, $d['recordkey'], 1, $explanded);
            
            foreach($push_data as $bomdata) {
                $count = $bomdata['count'];
                $bomdata['count'] = "";
                $prefix = "";
                for($ii = 0;$ii < $count; $ii++){
                    $prefix .= "  ";
                }
                $bomdata['code'] = $prefix.$bomdata['code'];
                $bomd = $this->object_array($bomdata);
                fputcsv($file, $bomd);
            }
            fclose($file);
        }
        /* foreach($push_data as $data) {
            $count = $data['count'];
            $data['count'] = "";
            $prefix = "";
            for($i = 0;$i < $count; $i++){
                $prefix .= "  ";
            }
            $data['code'] = $prefix.$data['code'];
            $d = $this->object_array($data);
            fputcsv($file, $d);
        }

        fclose($file); */
        $this->operate("BOM导出");

        if(isset($zipPath)) {
            $zip=new ZipArchive();
            $helper = new Application_Model_Helpers();
            if($zip->open($zipFile, ZipArchive::OVERWRITE)=== TRUE){
                $datalist = $this->list_dir($zipPath);
                foreach( $datalist as $val){
                    if(file_exists($val)){
                        $zip->addFile( $val, basename($val));//第二个参数是放在压缩包中的文件名称，如果文件可能会有重复，就需要注意一下
                    }
                }
                $zip->close();//关闭
            }
            echo $zipFileName;
        } else {
            echo $filename;
        }
        exit;
    }

    /*
     * 导出CSV 单文件
     */
    public function exportcsvoneAction()
    {
        $request = $this->getRequest()->getParams();
        $fa = new Product_Model_Fa();
        $son = new Product_Model_Son();
        $db = $fa->getAdapter();
        $explanded = array();
        $whereSearch = "1=1";
        foreach ($request as $k => $v) {
            if ($v) {
                if($k == 'search_key') {
                    $whereSearch .= " and (ifnull(t1.remark,'') like '%$v%' or ifnull(t3.name,'') like '%$v%' or ifnull(t3.description,'') like '%$v%' or ifnull(t5.model_internal, '') like '%$v%')";
                } else if ("search_archive_date_from" == $k && $v) {
                    $whereSearch .= " and t1.bom_upd_time >= '" . str_replace('T', ' ', $v) . "'";
                } else if ("search_archive_date_to" == $k && $v) {
                    $whereSearch .= " and t1.bom_upd_time <= '" . str_replace('T00:00:00', ' 23:59:59', $v) . "'";
                } else if ("search_fa" == $k && $v) {
                    $whereSearch .= " and t1.code like '%" . $v . "%'";
                } else if ("search_son" == $k && $v) {
                    $recordkey = "";
                    $sonData = $db->query("select group_concat(recordkey) as recordkey from oa_product_bom_son where code like '%$v%'")->fetchObject();
                    if($sonData && $sonData->recordkey) {
                        $recordkey = $sonData->recordkey;
                    }
                    if(!$recordkey) {
                        $recordkey = "0";
                    }
                    $whereSearch .= " and t1.recordkey in ($recordkey)";
                } else if ("search_recordkey" == $k && $v) {
                    $whereSearch .= " and t1.recordkey = '$v'";
                } else if ("explanded" == $k && $v) {
                    $explanded = json_decode($v);
                } else {
                    $col = str_replace('search_', '', $k);
                    if ($col != $k) {
                        // 查询条件
                        $whereSearch .= " and ifnull(t1." . $col . ",'') like '%" . $v . "%'";
                    }
                }
            }
        }
        $updflg = false;
        if(isset($request['upd_flg']) && $request['upd_flg'] == 1) {
            // 只导出升版
            $whereSearch .= " and t1.ver > 1.0";
            $updflg = true;
        }
        if(isset($request['my']) && $request['my'] == 1) {
            $user_session = new Zend_Session_Namespace('user');
            $user = $user_session->user_info['employee_id'];
            if(isset($request['step']) && $request['step'] == 'dev') {
                // 从升版里取
                $data = $db->query("select group_concat(id) as nids from oa_product_bom_upd where create_user = $user and state = 'Active'")->fetchObject();
                if($data && $data->nids) {
                    $nids = $data->nids;
                }
            } else {
                $data = $db->query("select group_concat(id) as nids from oa_product_bom_new where create_user = $user and state = 'Active'")->fetchObject();
                if($data && $data->nids) {
                    $nids = $data->nids;
                }
            }
            if(isset($nids) && $nids) {
                $whereSearch .= " and t1.nid in ($nids)";
            }
        }
        // 获取物料数据
        $data = $fa->getList($whereSearch, null, null);

        print(chr(0xEF).chr(0xBB).chr(0xBF));
        // 获取物料数据
        $data_csv = array();
        $title = array(
                'cnt'               => '#',
                'code'              => '物料号',
                'state'             => '状态',
                'name'              => '物料名称',
                'description'       => '物料描述',
                'project_no_name'   => '产品型号',
                'qty'               => '数量',
                'replace'           => '替代料',
                'partposition'      => '器件位置',
                'remark'            => '备注'
        );
        if($updflg) {
            $title['upd_type'] = '升版类型';
            $title['reason_type'] = '升版原因分类';
            $title['upd_reason'] = '升版原因';
            $title['description_upd'] = '升版描述';
        }

        // 文件名
        if(count($data) == 1) {
            $name = $data[0]['code'];
        } else {
            $name = "bom_list";
        }

        $title = $this->object_array($title);
        $date = date('YmdHsi');
        $filename = $name.'-' . $date;
        $path = "../temp/" . $filename . ".csv";
        $file = fopen($path, "w");
        fputcsv($file, $title);
        array_push($data_csv, $title);

        $typeids = array();
        $typenames = array();
        $k = 0;
        $push_data = array();
        for($i = 0; $i < count($data); $i++) {
            $d = $data[$i];

            $k++;

            $info = array(
                'cnt'               => $k,
                'code'              => $this->ifNull($d, 'code')." V".$d['ver'],
                'state'             => $this->ifNull($d, 'state'),
                'name'              => $this->ifNull($d, 'name'),
                'description'       => $this->ifNull($d, 'description'),
                'project_no_name'   => $this->ifNull($d, 'project_no_name'),
                'qty'               => $d['qty'],
                'replace'           => $this->ifNull($d, 'replace'),
                'partposition'      => $this->ifNull($d, 'partposition'),
                'remark'            => $this->ifNull($d, 'remark'),
            );
            if($updflg) {
                $info['upd_type'] = $this->ifNull($d, 'upd_type');
                $info['reason_type'] = $this->ifNull($d, 'reason_type_name');
                $info['upd_reason'] = $this->ifNull($d, 'upd_reason');
                $info['description_upd'] = $this->ifNull($d, 'description_upd');
            }
            
            $info['count'] = 0;
            $push_data[] = $info;
            $push_data = $this->getBomInfo($push_data, $fa, $son, $d['recordkey'], 1, $explanded);
        }
        foreach($push_data as $data) {
            $count = $data['count'];
            $data['count'] = "";
            $prefix = "";
            for($i = 0;$i < $count; $i++){
                $prefix .= "  ";
            }
            $data['code'] = $prefix.$data['code'];
            $d = $this->object_array($data);
            fputcsv($file, $d);
        }

        fclose($file);
        $this->operate("BOM导出");

        echo $filename;

        exit;
    }

    private function getBomInfo($push_data, $fa, $son, $recordkey, $count, $explanded) {
        $data = $son->getSon($recordkey);

        for($i = 0; $i < count($data); $i++){
            $fadata = $fa->getFa($data[$i]['code'], null);
            $faRow = "";
            if($fadata && count($fadata) > 0) {
                $faRow = $fadata[0];
            }
            $row = array();
            $row['cnt'] = "";
            $row['code'] = $data[$i]['code'];
            if($faRow){
                $row['code'] = $data[$i]['code'].' V'.$faRow['ver'];
                $row['state'] = $faRow['state'];
                $row['name'] = $data[$i]['name'];
                $row['description'] = $data[$i]['description'];
                $row['project_no_name'] = $faRow['project_no_name'];
                $row['qty'] = $data[$i]['qty'];
                $row['replace'] = $data[$i]['replace'];
                $row['partposition'] = $data[$i]['partposition'];
                $row['remark'] = $data[$i]['remark'];
                $row['count'] = $count;
                $push_data[] = $row;
                if(count($explanded) == 0 || in_array($data[$i]['code'], $explanded)) {
                    $push_data = $this->getBomInfo($push_data, $fa, $son, $faRow['recordkey'], ++$count, $explanded);
                    $count--;
                }
            }else{
                $row['state'] = $data[$i]['mstate'];
                $row['name'] = $data[$i]['name'];
                $row['description'] = $data[$i]['description'];
                $row['project_no_name'] = "";
                $row['qty'] = $data[$i]['qty'];
                $row['replace'] = $data[$i]['replace'];
                $row['partposition'] = $data[$i]['partposition'];
                $row['remark'] = $data[$i]['remark'];
                $row['count'] = $count;
                $push_data[] = $row;
            }
        }

        return $push_data;

    }

    private function ifNull($array, $key) {
        if(isset($array[$key])) {
            return $array[$key];
        } else {
            return "";
        }
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
//             var_dump($v);
//             $a[$key] = iconv('utf-8', 'GBK//TRANSLIT', $v);
            $a[$key] = mb_convert_encoding($v, 'GBK', 'utf-8');
            
            
        }
        return $a;
    }
    /**
     * 获取文件列表
     * @param unknown $dir
     * @return multitype:
     */
    function list_dir($dir){
        $result = array();
        if (is_dir($dir)){
            $file_dir = scandir($dir);
            foreach($file_dir as $file){
                if ($file == '.' || $file == '..'){
                    continue;
                }
                elseif (is_dir($dir.$file)){
                    $result = array_merge($result, $this->list_dir($dir.$file.'/'));
                }
                else{
                    array_push($result, $dir.$file);
                }
            }
        }
        return $result;
    }

}

