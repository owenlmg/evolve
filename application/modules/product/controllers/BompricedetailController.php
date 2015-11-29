<?php
/**
 * 2013-9-8
 * @author      mg.luo
 * @abstract    物料类别管理
 */
class Product_BompricedetailController extends Zend_Controller_Action
{

    public function indexAction()
    {

    }

    public function getlistAction() {
        $request = $this->getRequest()->getParams();
        if(!isset($request['recordkey']) || !$request['recordkey']) {
        	exit;
        }
        $recordkey = $request['recordkey'];
        $fa = new Product_Model_Fa();
        $son = new Product_Model_Son();
        $price_list = new Erp_Model_Warehouse_Pricelist();
        $bomprice = new Product_Model_BomPrice();
        
        $currencyrate = new Erp_Model_Setting_Currencyrate();
        $date = date('Y-m-d');
        $rateCny = $currencyrate->getRateByCode('CNY', $date);
        $rateUsd = $currencyrate->getRateByCode('USD', $date);
        $rate = round($rateCny / $rateUsd, 4);
        
        $db = $fa->getAdapter();

        $data = $fa->getOne($recordkey);
        $bom = array();
        for($i = 0; $i < count($data); $i++) {
        	if($i == 0){
        		$row = $data[$i];
                $price = $bomprice->calcBomPrice($row['recordkey'], 'CNY');
	            $bom = array(
	                    'sid'            => $row['sid'],
	                    'nid'            => $row['nid'],
	                    'recordkey'      => $row['recordkey'],
	                    'id'             => $row['id'],
	                    'name'          => $row['name'],
	                    'description'   => $row['description'],
	                    'remark'        => $row['remark'],
	                    'project_no_name' => $row['project_no_name'],
	                    'bom_file' => $row['bom_file'],
	                    'code'        => $row['code'],
	                    'qty'        => $row['qty'],
	                    'ver'        => $row['ver'],
	                    'partposition'        => "",
	                    'replace'        => "",
	                    'state'         => $row['state'],
	                    'count'          => 1,
	                    'leaf'          => false,
	                    'low_cny'          => $price['low'],
	                    'low_usd'          => round($price['low'] * $rate, 4),
	                    'high_cny'          => $price['high'],
	                    'high_usd'          => round($price['high'] * $rate, 4),
	                    'average_cny'          => $price['average'],
	                    'average_usd'          => round($price['average'] * $rate, 4),
	                    'children'      => $this->getData($fa, $son, $recordkey, 2, $rate)
	            );
	        }
        }
        $result = array(
                'sid'            => '',
                'nid'            => '',
                'recordkey'      => '',
                'id'             => '',
                'name'          => '',
                'description'   => '',
                'remark'        => '',
                'code'        => '',
                'qty'        => '',
                'partposition'        => '',
                'replace'        => '',
                'state'         => '',
                'leaf'          => false,
                'children'      => $bom
        );
        // 将类别数据转为json格式并输出
        $this->view->recordkey = $row['recordkey'];
        $this->view->code = $row['code'];
        $this->view->ver = $row['ver'];
        echo Zend_Json::encode($result);

        exit;
    }

    /**
     * @abstract    获取类别树数据
     * @param       number  $parentId  上级ID
     * @param       boolen  $root       是否为最上级
     * @return      array   $dept
     */
    private function getData($fa, $son, $recordkey, $count, $rate)
    {
        $price_list = new Erp_Model_Warehouse_Pricelist();
        $bomprice = new Product_Model_BomPrice();
        $data = $son->getSon($recordkey);

        for($i = 0; $i < count($data); $i++){
            $fadata = $fa->getFa($data[$i]['code'], null);
            $faRow = "";
            if($fadata && count($fadata) > 0) {
        	    $faRow = $fadata[0];
            }
            if($faRow){
                $price = $bomprice->calcBomPrice($faRow['recordkey'], 'CNY');
                $data[$i]['low_cny'] = $price['low'];
                $data[$i]['low_usd'] = round($price['low'] * $rate, 4);
                $data[$i]['high_cny'] = $price['high'];
                $data[$i]['high_usd'] = round($price['high'] * $rate, 4);
                $data[$i]['average_cny'] = $price['average'];
                $data[$i]['average_usd'] = round($price['average'] * $rate, 4);

                $data[$i]['ver'] = $faRow['ver'];
                $data[$i]['leaf'] = false;
                $data[$i]['state'] = $faRow['state'];
                $data[$i]['count'] = $count;
                $data[$i]['children'] = $this->getData($fa, $son, $faRow['recordkey'], $count++, $rate);
            }else{
                if(isset($_SESSION['mprice'][$data[$i]['code']])) {
                    // session 中存在
                    $price = $_SESSION['mprice'][$data[$i]['code']];
                } else {
                    $price = $price_list->getMultiPrice($data[$i]['code'], 'CNY');
                    $_SESSION['mprice'][$data[$i]['code']] = $price;
                }
                $data[$i]['low_cny'] = $price['low'];
                $data[$i]['low_usd'] = round($price['low'] * $rate, 6);
                $data[$i]['high_cny'] = $price['high'];
                $data[$i]['high_usd'] = round($price['high'] * $rate, 6);
                $data[$i]['average_cny'] = $price['average'];
                $data[$i]['average_usd'] = round($price['average'] * $rate, 6);
                $data[$i]['leaf'] = true;
                $data[$i]['count'] = 0;
                $data[$i]['state'] = $data[$i]['mstate'];
            }
        }

        return $data;
    }

    public function gettypetreeAction() {
        $data = array();

        // 请求参数
        $request = $this->getRequest()->getParams();

        $node = isset($request['node']) ? $request['node'] : 0;
        $node = $node == 'root' ? 0 : $node;

        $type = new Product_Model_Type();

        $data = $type->getTree($node);

        // 将模块数据转为json格式并输出
        echo "[".Zend_Json::encode($data)."]";

        exit;
    }

    /*
     * 导出CSV 多文件
     */
    public function exportcsvAction()
    {
        set_time_limit(0);
        
        $request = $this->getRequest()->getParams();
        $fa = new Product_Model_Fa();
        $son = new Product_Model_Son();
        $db = $fa->getAdapter();
        $explanded = array();
        $whereSearch = "1=1";
        foreach ($request as $k => $v) {
            if ($v) {
            	if ("search_recordkey" == $k && $v) {
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
                'low_cny'           => '最低价格-人民币',
                'high_cny'          => '最高价格-人民币',
                'average_cny'       => '平均价格-人民币',
                'low_usd'           => '最低价格-美元',
                'high_usd'          => '最高价格-美元',
                'average_usd'       => '平均价格-美元',
                'project_no_name'   => '产品型号',
                'qty'               => '数量',
                'replace'           => '替代料',
                'partposition'      => '器件位置',
                'remark'            => '备注'
        );

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

        $bomPrice = new Product_Model_BomPrice();
        $currencyrate = new Erp_Model_Setting_Currencyrate();
        $date = date('Y-m-d');
        $rateCny = $currencyrate->getRateByCode('CNY', $date);
        $rateUsd = $currencyrate->getRateByCode('USD', $date);
        $rate = round($rateCny / $rateUsd, 4);

        $title = $this->object_array($title);

        $typeids = array();
        $typenames = array();
        $k = 0;
        for($i = 0; $i < count($data); $i++) {
            $push_data = array();
            
            $d = $data[$i];

            $k++;
            $price = $bomPrice->calcBomPrice($d['recordkey'], 'CNY');

            $info = array(
                'cnt'               => $k,
                'code'              => Helper::ifNull($d, 'code')." V".$d['ver'],
                'state'             => Helper::ifNull($d, 'state'),
                'name'              => Helper::ifNull($d, 'name'),
                'description'       => Helper::ifNull($d, 'description'),
                'low_cny'           => $price['low'],
                'low_usd'           => round($price['low'] * $rate, 4),
                'high_cny'          => $price['high'],
                'high_usd'          => round($price['high'] * $rate, 4),
                'average_cny'       => $price['average'],
                'average_usd'       => round($price['average'] * $rate, 4),
                'qty'               => $d['qty'],
                'replace'           => Helper::ifNull($d, 'replace'),
                'partposition'      => Helper::ifNull($d, 'partposition'),
                'remark'            => Helper::ifNull($d, 'remark'),
            );
            
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
            $push_data = $this->getBomInfo($push_data, $fa, $son, $d['recordkey'], 1, $explanded, $rate);
            
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

    private function getBomInfo($push_data, $fa, $son, $recordkey, $count, $explanded, $rate) {
        $price_list = new Erp_Model_Warehouse_Pricelist();
        $bomPrice = new Product_Model_BomPrice();
        
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
	            
                $price = $bomPrice->calcBomPrice($faRow['recordkey'], 'CNY');
                $row['low_cny'] = round($price['low'] * $data[$i]['qty'], 4);
                $row['low_usd'] = round($price['low'] * $rate * $data[$i]['qty'], 4);
                $row['high_cny'] = round($price['high'] * $data[$i]['qty'], 4);
                $row['high_usd'] = round($price['high'] * $rate * $data[$i]['qty'], 4);
                $row['average_cny'] = round($price['average'] * $data[$i]['qty'], 4);
                $row['average_usd'] = round($price['average'] * $rate * $data[$i]['qty'], 4);
                
	            $row['project_no_name'] = $faRow['project_no_name'];
	            $row['qty'] = $data[$i]['qty'];
	            $row['replace'] = $data[$i]['replace'];
	            $row['partposition'] = $data[$i]['partposition'];
	            $row['remark'] = $data[$i]['remark'];
	            $row['count'] = $count;
                $push_data[] = $row;
                if(count($explanded) == 0 || in_array($data[$i]['code'], $explanded)) {
	                $push_data = $this->getBomInfo($push_data, $fa, $son, $faRow['recordkey'], ++$count, $explanded, $rate);
	                $count--;
                }
            }else{
	            $row['state'] = $data[$i]['mstate'];
	            $row['name'] = $data[$i]['name'];
	            $row['description'] = $data[$i]['description'];

	            if(isset($_SESSION['mprice'][$data[$i]['code']])) {
	                // session 中存在
	                $price = $_SESSION['mprice'][$data[$i]['code']];
	            } else {
	                $price = $price_list->getMultiPrice($data[$i]['code'], 'CNY');
	                $_SESSION['mprice'][$data[$i]['code']] = $price;
	            }
	            $row['low_cny'] = round($price['low'] * $data[$i]['qty'], 4);
	            $row['low_usd'] = round($price['low'] * $rate * $data[$i]['qty'], 4);
	            $row['high_cny'] = round($price['high'] * $data[$i]['qty'], 4);
	            $row['high_usd'] = round($price['high'] * $rate * $data[$i]['qty'], 4);
	            $row['average_cny'] = round($price['average'] * $data[$i]['qty'], 4);
	            $row['average_usd'] = round($price['average'] * $rate * $data[$i]['qty'], 4);
	            
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

}

