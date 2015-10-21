<?php
/**
 * 2013-9-8
 * @author      mg.luo
 * @abstract    物料编码申请
 */
class Product_BommeController extends Zend_Controller_Action
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
        $data = $fa->getListMy($whereSearch, $start, $limit, $user);
        $totalCount = $fa->getListCountMy($whereSearch, $user);
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

