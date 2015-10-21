<?php
/**
 * 2013-9-8
 * @author      mg.luo
 * @abstract    产品管理
 */
class Product_ProductlistController extends Zend_Controller_Action
{

    public function indexAction()
    {

    }

    public function getlistAction() {
        $request = $this->getRequest()->getParams();
        $limit = $request['limit'];
        $start = $request['start'];
        $whereSearch = "1=1";
        $cols = array("sn","code","step","description","is_bom_exists","bom_apply_time","bom_archive_time","product_code","bosa","bosa_supply","tosa","tosa_supply","rosa","rosa_supply","pcb","pcba","dg02","dg01","product_label","barcode_label","label_print_rule","tooling_model","sample_send_time","pra","trial_produce_qa1","pmr","dl","ipa","cri","ds","dd","pl","pes","pcb_file","icd","smt","mp","sqr","dvs","dvp","dvr","dvt","rtr","emr","mtb","tsq","sqc","ed","pts","sp","ep","fep","fsp","ld","pd","pg","nfc","frm","pfc","wi","other","create_time","create_user","update_time","update_user");

        $key = $request['search_tag'];
        $arr=preg_split('/\s+/',trim($key));
        for ($i=0;$i<count($arr);$i++) {
            $tmp = array();
            foreach($cols as $c) {
                $tmp[] = "ifnull($c,'')";
            }
            $arr[$i] = "concat(".implode(',', $tmp).") like '%".$arr[$i]."%'";
        }
        $whereSearch = join(' AND ', $arr);

        $plist = new Product_Model_Productlist();
        // 获取数据
        $data = array();
        $total = $plist->getJoinCount($whereSearch);
        if($total > 0) {
            $data = $plist->getJoinList($whereSearch, array(), null, array("bom_apply_time desc"), $start, $limit);
        }
        $result = array(
            "totalCount" => $total,
            "topics" => $data
        );
        // 转为json格式并输出
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
                'success' => true,
                'info' => '保存成功'
        );

        $request = $this->getRequest()->getParams();

        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];

       $json = json_decode($request['json']);

        $updated = $json->updated;
        $inserted = $json->inserted;
        $deleted = $json->deleted;

        $plist = new Product_Model_Productlist();
        $cols = array("sn","code","step","description","is_bom_exists","bom_apply_time","bom_archive_time","product_code","bosa","bosa_supply","tosa","tosa_supply","rosa","rosa_supply","pcb","pcba","dg02","dg01","product_label","barcode_label","label_print_rule","tooling_model","sample_send_time","pra","trial_produce_qa1","pmr","dl","ipa","cri","ds","dd","pl","pes","pcb_file","icd","smt","mp","sqr","dvs","dvp","dvr","dvt","rtr","emr","mtb","tsq","sqc","ed","pts","sp","ep","fep","fsp","ld","pd","pg","nfc","frm","pfc","wi","other","create_time","create_user","update_time","update_user");

        if (count($updated) > 0) {
            foreach ($updated as $val) {
                $val = (array)$val;
                // 检查code是否重复
                if ($plist->fetchAll("id != " . $val['id'] . " and code = '" . $val['code'] . "'")->count() > 0) {
                    $result['result'] = false;
                    $result['info'] = "成品物料号“" . $val['code'] . "”已经存在";
                    echo Zend_Json::encode($result);
                    exit();
                }
                $data = array();
                foreach($cols as $c) {
                    if(isset($val[$c])) {
                        $data[$c] = $val[$c];
                    }
                }
                $data['update_time'] = $now;
                $data['update_user'] = $user;

                $where = "id = " . $val['id'];

                try {
                    $plist->update($data, $where);
                } catch (Exception $e) {
                    $result['result'] = false;
                    $result['info'] = $e->getMessage();
                    echo Zend_Json::encode($result);
                    exit();
                }
            }
        }

        if (count($inserted) > 0) {
            foreach ($inserted as $val) {
                $val = (array)$val;
                // 检查code是否重复
                if ($plist->fetchAll("code = '" . $val['code'] . "'")->count() > 0) {
                    $result['result'] = false;
                    $result['info'] = "成品物料号“" . $val['code'] . "”已经存在";
                    echo Zend_Json::encode($result);
                    exit();
                }
                $data = array();
                foreach($cols as $c) {
                    if(isset($val[$c])) {
                        $data[$c] = $val[$c];
                    }
                }
                $data['create_time'] = $now;
                $data['update_time'] = $now;
                $data['create_user'] = $user;
                $data['update_user'] = $user;

                try {
                    $plist->insert($data);
                } catch (Exception $e) {
                    $result['result'] = false;
                    $result['info'] = $e->getMessage();
                    echo Zend_Json::encode($result);
                    exit();
                }
            }
        }

        if (count($deleted) > 0) {
            foreach ($deleted as $val) {
                $val = (array)$val;
                $where = "id = ".$val['id'];
                try {
                    $plist->delete($where);
                } catch (Exception $e) {
                    $result['result'] = false;
                    $result['info'] = $e->getMessage();
                    echo Zend_Json::encode($result);
                    exit();
                }
            }
        }

        echo Zend_Json::encode($result);

        exit();
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
        $json = json_decode($request['json']);
        $deleted = $json->deleted;
        $plist = new Product_Model_Productlist();
        if (count($deleted) > 0) {
            foreach ($deleted as $val) {
                $id = $val->id;
                try {
                    // 删除
                    $plist->delete("id = $id");
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

            $plist = new Product_Model_Productlist();

            // 读取文件
            require_once LIBRARY_PATH."/Excel/reader.php";
            $reader = new Spreadsheet_Excel_Reader();
            $reader->setOutputEncoding('UTF-8');
            $reader->setUTFEncoder('mb');
            $reader->setRowColOffset(1);
            $reader->read($tmp_file_path);
            error_reporting(E_ALL ^ E_NOTICE);
            

            $data = array();
            for ($i = 1; $i <= $reader->sheets[0]['numRows']; $i++) {
                $row = array();
                for ($j = 1; $j <= $reader->sheets[0]['numCols']; $j++) {
                    $row[] = $reader->sheets[0]['cells'][$i][$j];
                }
                $data[] = $row;

            }
            // 数据校验
            if(count($data) <= 1) {
                $result['success'] = false;
                $result['info'] = "文件中无数据！";
                echo Zend_Json::encode($result);
                exit;
            }
            $cols = array("sn","code","step","description","is_bom_exists","bom_apply_time","bom_archive_time","product_code","bosa","bosa_supply","tosa","tosa_supply","rosa","rosa_supply","pcb","pcba","dg02","dg01","product_label","barcode_label","label_print_rule","tooling_model","sample_send_time","pra","trial_produce_qa1","pmr","dl","ipa","cri","ds","dd","pl","pes","pcb_file","icd","smt","mp","sqr","dvs","dvp","dvr","dvt","rtr","emr","mtb","tsq","sqc","ed","pts","sp","ep","fep","fsp","ld","pd","pg","nfc","frm","pfc","wi","other","create_time","create_user","update_time","update_user");
            for($i = 1; $i < count($data); $i++) {
                $num = $i+1;
                $row = $data[$i];
                if(count($row) < 2 || !$row[1]) {
                    continue;
                }
                $code = $row[1];
                if(!$this->checkExists($code)) {
                    $insertData = array();
                    $k = 0;
                    foreach($cols as $c) {
                        $insertData[$c] = $row[$k];
                        $k++;
                    }
                    try {
                        $plist->insert($insertData);
                    } catch(Exception $e) {
                        $result['result'] = false;
                        $result['info'] = $e->getMessage();
                        echo Zend_Json::encode($result);
                        exit;
                    }
                } else {
                    $errors[] = $code;
                }
            }

            $result['error'] = $errors;
        }
        echo Zend_Json::encode($result);
        exit;
    }

    public function checkExists($code) {
        $where = "code = '$code'";
        $plist = new Product_Model_Productlist();
        return $plist->getJoinCount($where);
    }

}

