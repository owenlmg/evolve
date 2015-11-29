<?php
/**
 * 2013-9-8
 * @author      mg.luo
 * @abstract       BOM价格
 */
class Product_BompriceController extends Zend_Controller_Action
{

    public function indexAction()
    {

    }
    
    public function refreshAction() {
        $fa = new Product_Model_Fa();
        $son = new Product_Model_Son();
        $bomPrice = new Product_Model_BomPrice();
        
        $code = $this->getRequest()->getParam('code');
        if($code) {
            $where = "state = 'EBOM' and code = '$code'";
            $type = 'single';
        } else {
            $where = "state = 'EBOM'";
            $type = 'all';
        }
        
        
        
        $currencyrate = new Erp_Model_Setting_Currencyrate();
        $date = date('Y-m-d');
        $rateCny = $currencyrate->getRateByCode('CNY', $date);
        $rateUsd = $currencyrate->getRateByCode('USD', $date);
        $rate = round($rateCny / $rateUsd, 6);
        
        $data = $fa->getJoinList($where);
        foreach($data as $bom) {
            $this->updatePrice($bom, $type, $rate);
            
        }
        echo 'success';
        exit;
        
    }
    
    /**
     * 更新价格
     * @param unknown $recordkey
     * @param unknown $currency
     * @param unknown $type
     */
    public function updatePrice($bom, $type, $rate) {
        $fa = new Product_Model_Fa();
        $son = new Product_Model_Son();
        $bomPrice = new Product_Model_BomPrice();

        $recordkey = $bom['recordkey'];
        if($type == 'single') {
            $bomPrice->delete('recordkey = '.$recordkey);
            $priceData = false;
        } else {
            $priceData = $bomPrice->getJoinCount('recordkey = '.$recordkey);
        }
        if(!$priceData) {
            $_SESSION['mNoPrice'] = null;
            $price_cny = $bomPrice->getBomPrice($recordkey, 'CNY', $type);
            $low_cny = $price_cny['low'];
            $high_cny = $price_cny['high'];
            $average_cny = $price_cny['average'];
            
            $low_usd = round($low_cny * $rate, 4);
            $high_usd = round($high_cny * $rate, 4);
            $average_usd = round($average_cny * $rate, 4);
            
            $noprice = 0;
            if(in_array($recordkey, $_SESSION['noPrice'])) {
                $noprice = 1;
            }

            // id recordkey code ver low_cny low_usd high_cny high_usd average_cny average_usd mid project_no state
            $data = array(
                    'recordkey' => $recordkey,
                    'code' => $bom['code'],
                    'ver' => $bom['ver'],
                    'low_cny' => $low_cny,
                    'low_usd' => $low_usd,
                    'high_cny' => $high_cny,
                    'high_usd' => $high_usd,
                    'average_cny' => $average_cny,
                    'average_usd' => $average_usd,
                    'mid' => $bom['id'],
                    'project_no' => $bom['project_no'],
                    'state' => $bom['state'],
                    'update_time' => date('Y-m-d H:i:s'),
                    'noprice' => $noprice
            );
            $bomPrice->insert($data);
            return true;
        }
        return false;
    }
    
    /**
     * 增加价格
     */
    public function addpriceAction() {
        set_time_limit(0);
        
        $currencyrate = new Erp_Model_Setting_Currencyrate();
        $date = date('Y-m-d');
        $rateCny = $currencyrate->getRateByCode('CNY', $date);
        $rateUsd = $currencyrate->getRateByCode('USD', $date);
        $rate = round($rateCny / $rateUsd, 6);
        
        $fa = new Product_Model_Fa();
        $bomPrice = new Product_Model_BomPrice();
        $sql = "SELECT * FROM `oa_product_bom_fa` t1 where t1.recordkey not in (select recordkey from oa_product_bom_price) and t1.state = 'EBOM'";
        $db = $fa->getAdapter();
        $data = $db->query($sql)->fetchAll();
        $num = 0;
        foreach($data as $bom) {
            if($this->updatePrice($bom, 'all', $rate)) {
                $num++;
            }
        }
        $result = array('success' => true, 'num' => $num);
        echo Zend_Json::encode($result);
        exit;
    }

    /**
     * 获取价格清单
     */
    public function getlistAction() {
        $fa = new Product_Model_Fa();
        $bomPrice = new Product_Model_BomPrice();
        $son = new Product_Model_Son();
        $catalog = new Product_Model_Catalog();
        $materiel = new Product_Model_Materiel();

        $request = $this->getRequest()->getParams();
        $limit = $request['limit'];
        $start = $request['start'];
        
        $faName = $fa->getName();
        $priceName = $bomPrice->getName();
        $mName = $materiel->getName();
        $catalogName = $catalog->getName();
        
        $db = $fa->getAdapter();
        $whereSearch = "$faName.state != 'Obsolete'";
        foreach ($request as $k => $v) {
            if ($v) {
                if($k == 'search_key') {
            		$whereSearch .= " and (ifnull($faName.remark,'') like '%$v%' or ifnull($mName.name,'') like '%$v%' or ifnull($mName.description,'') like '%$v%' or ifnull($catalogName.model_internal, '') like '%$v%')";
            	} else if("search_fa" == $k && $v) {
                    $whereSearch .= " and $faName.code like '%$v%'";
                } else if ("search_archive_date_from" == $k && $v) {
                    $whereSearch .= " and $faName.bom_upd_time >= '" . str_replace('T', ' ', $v) . "'";
                } else if ("search_archive_date_to" == $k && $v) {
                    $whereSearch .= " and v.bom_upd_time <= '" . str_replace('T00:00:00', ' 23:59:59', $v) . "'";
                } else if ("search_son" == $k && $v) {
                    $recordkey = "";
                    $sonData = $db->query("select group_concat(recordkey) as recordkey from oa_product_bom_son where code like '%$v%'")->fetchObject();
                    if($sonData && $sonData->recordkey) {
                    	$recordkey = $sonData->recordkey;
                    }
                    if(!$recordkey) {
                    	$recordkey = "0";
                    }
                    $whereSearch .= " and $faName.recordkey in ($recordkey)";
                } else if ("search_recordkey" == $k && $v) {
                    $whereSearch .= " and $faName.recordkey = '$v'";
                } else if ("explanded" == $k && $v) {
                    $explanded = json_decode($v);
                } else {
	                $col = str_replace('search_', '', $k);
	                if ($col != $k) {
	                    // 查询条件
	                    $whereSearch .= " and ifnull($faName." . $col . ",'') like '%" . $v . "%'";
	                }
            	}
            }
        }
        
        $currencyrate = new Erp_Model_Setting_Currencyrate();
        $date = date('Y-m-d');
        $rateCny = $currencyrate->getRateByCode('CNY', $date);
        $rateUsd = $currencyrate->getRateByCode('USD', $date);
        $rate = round($rateCny / $rateUsd, 6);
        
        // 刷新价格
        $recordkeys = $this->getRequest()->getParam('recordkeys');
        if($recordkeys) {
            $data = $fa->getJoinList('recordkey in ('.$recordkeys.')');
            foreach($data as $bom) {
                $this->updatePrice($bom, 'single', $rate);
            }
            
        }
        
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];
        
        $sort = $this->getRequest()->getParam('sort');
        if($sort) {
            $sort = json_decode($sort);
            $sort =$sort[0];
            $property = $sort->property;
            $direction = $sort->direction;
            if(in_array($property, array('code', 'ver', 'state', 'remark')) ) {
                $order = array($faName.'.'.$property.' '.$direction);
            } else if(in_array($property, array('low_cny', 'low_usd', 'high_cny', 'high_usd', 'average_cny', 'average_usd', 'update_time')) ) {
                $order = array($bomPrice->getName().'.'.$property.' '.$direction);
            } else if(in_array($property, array('description', 'name')) ) {
                $order = array($mName.'.'.$property.' '.$direction);
            }
        }
        if(isset($order)) {
            $order[] = $faName.'.code';
        } else {
            $order = array($faName.'.code');
        }

        // 获取物料数据

        $cjoin = array(
                array(
                        'type' => LEFTJONIN,
                        'table' => $catalog->getName(),
                        'condition' => $catalog->getName().'.id = '.$fa->getName().'.project_no',
                ),
                array(
                        'type' => INNERJOIN,
                        'table' => $bomPrice->getName(),
                        'condition' => $bomPrice->getName().'.recordkey = '.$fa->getName().'.recordkey',
                ),
                array(
                        'type' => INNERJOIN,
                        'table' => $materiel->getName(),
                        'condition' => $materiel->getName().'.id = '.$fa->getName().'.id',
                )
        );
        
        $join = array(
        	array(
    	        'type' => LEFTJONIN,
                'table' => $catalog->getName(),
                'condition' => $catalog->getName().'.id = '.$fa->getName().'.project_no',
                'cols' => array('project_no_name' => 'model_internal')
            ),
            array(
                    'type' => INNERJOIN,
                    'table' => $bomPrice->getName(),
                    'condition' => $bomPrice->getName().'.recordkey = '.$fa->getName().'.recordkey',
                    'cols' => array("low_cny", "low_usd", 'high_cny', 'high_usd', 'average_cny', 'average_usd', 'update_time', 'noprice')
            ),
            array(
                    'type' => INNERJOIN,
                    'table' => $materiel->getName(),
                    'condition' => $materiel->getName().'.id = '.$fa->getName().'.id',
                    'cols' => array("name", "description")
            )
        );
        $data = $fa->getJoinList($whereSearch, $join, null, $order, $start, $limit);
        $totalCount = $fa->getJoinCount($whereSearch, $cjoin);
        $allData = array();
        for($i = 0; $i < count($data); $i++) {
            $data[$i]['bom_upd_time'] = strtotime($data[$i]['bom_upd_time']);
            $data[$i]['update_time'] = strtotime($data[$i]['update_time']);

            // 对于每个BOM，递归计算每个下级物料或BOM的价格
            if(!$data[$i]['low_cny']) {
                $price = $bomPrice->calcBomPrice($data[$i]['recordkey'], 'CNY');
                $data[$i]['low_cny'] = $price['low'];
                $data[$i]['high_cny'] = $price['high'];
                $data[$i]['average_cny'] = $price['average'];
                
                $data[$i]['low_usd'] = round($data[$i]['low_cny'] * $rate, 4);
                $data[$i]['high_usd'] = round($data[$i]['high_cny'] * $rate, 4);
                $data[$i]['average_usd'] = round($data[$i]['average_cny'] * $rate, 4);
            }
            
            $allData[] = $data[$i];

        }
        $result = array(
            "totalCount" => $totalCount,
            "topics" => $allData
        );
        // 转为json格式并输出
        echo Zend_Json::encode($result);

        exit;
    }

    /**
     *    BOM列表
     */
    public function getbomAction() {
        $code = $this->getRequest()->getParam('q');
        $where = "code like '%$code%'";
        $fa = new Product_Model_Fa();
        $data = $fa->getJoinList($where, array(), array('recordkey' => 'recordkey', 'code', 'ver'), array(), 0, 10, array('code'));
        echo Zend_Json::encode($data);
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
        $fa = new Product_Model_Fa();
        $bomPrice = new Product_Model_BomPrice();
        $son = new Product_Model_Son();
        $catalog = new Product_Model_Catalog();
        $materiel = new Product_Model_Materiel();

        $request = $this->getRequest()->getParams();
        
        $faName = $fa->getName();
        $priceName = $bomPrice->getName();
        $mName = $materiel->getName();
        $catalogName = $catalog->getName();
        
        $db = $fa->getAdapter();
        $whereSearch = "$faName.state != 'Obsolete'";
        foreach ($request as $k => $v) {
            if ($v) {
                if($k == 'search_key') {
            		$whereSearch .= " and (ifnull($faName.remark,'') like '%$v%' or ifnull($mName.name,'') like '%$v%' or ifnull($mName.description,'') like '%$v%' or ifnull($catalogName.model_internal, '') like '%$v%')";
            	} else if("search_fa" == $k && $v) {
                    $whereSearch .= " and $faName.code like '%$v%'";
                } else if ("search_archive_date_from" == $k && $v) {
                    $whereSearch .= " and $faName.bom_upd_time >= '" . str_replace('T', ' ', $v) . "'";
                } else if ("search_archive_date_to" == $k && $v) {
                    $whereSearch .= " and v.bom_upd_time <= '" . str_replace('T00:00:00', ' 23:59:59', $v) . "'";
                } else if ("search_son" == $k && $v) {
                    $recordkey = "";
                    $sonData = $db->query("select group_concat(recordkey) as recordkey from oa_product_bom_son where code like '%$v%'")->fetchObject();
                    if($sonData && $sonData->recordkey) {
                    	$recordkey = $sonData->recordkey;
                    }
                    if(!$recordkey) {
                    	$recordkey = "0";
                    }
                    $whereSearch .= " and $faName.recordkey in ($recordkey)";
                } else if ("search_recordkey" == $k && $v) {
                    $whereSearch .= " and $faName.recordkey = '$v'";
                } else if ("explanded" == $k && $v) {
                    $explanded = json_decode($v);
                } else {
	                $col = str_replace('search_', '', $k);
	                if ($col != $k) {
	                    // 查询条件
	                    $whereSearch .= " and ifnull($faName." . $col . ",'') like '%" . $v . "%'";
	                }
            	}
            }
        }
        
        $rate = new Erp_Model_Setting_Currencyrate();
        $date = date('Y-m-d');
        $rateCny = $rate->getRateByCode('CNY', $date);
        $rateUsd = $rate->getRateByCode('USD', $date);
        
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];
        
        $sort = $this->getRequest()->getParam('sort');
        if($sort) {
            $sort = json_decode($sort);
            $sort =$sort[0];
            $property = $sort->property;
            $direction = $sort->direction;
            if(in_array($property, array('code', 'ver', 'state', 'remark')) ) {
                $order = array($faName.'.'.$property.' '.$direction);
            } else if(in_array($property, array('low_cny', 'low_usd', 'high_cny', 'high_usd', 'average_cny', 'average_usd', 'update_time')) ) {
                $order = array($bomPrice->getName().'.'.$property.' '.$direction);
            } else if(in_array($property, array('description', 'name')) ) {
                $order = array($mName.'.'.$property.' '.$direction);
            }
        }
        if(isset($order)) {
            $order[] = $faName.'.code';
        } else {
            $order = array($faName.'.code');
        }

        // 获取物料数据

        $cjoin = array(
                array(
                        'type' => LEFTJONIN,
                        'table' => $catalog->getName(),
                        'condition' => $catalog->getName().'.id = '.$fa->getName().'.project_no',
                ),
                array(
                        'type' => INNERJOIN,
                        'table' => $bomPrice->getName(),
                        'condition' => $bomPrice->getName().'.recordkey = '.$fa->getName().'.recordkey',
                ),
                array(
                        'type' => INNERJOIN,
                        'table' => $materiel->getName(),
                        'condition' => $materiel->getName().'.id = '.$fa->getName().'.id',
                )
        );
        
        $join = array(
        	array(
    	        'type' => LEFTJONIN,
                'table' => $catalog->getName(),
                'condition' => $catalog->getName().'.id = '.$fa->getName().'.project_no',
                'cols' => array('project_no_name' => 'model_internal')
            ),
            array(
                    'type' => INNERJOIN,
                    'table' => $bomPrice->getName(),
                    'condition' => $bomPrice->getName().'.recordkey = '.$fa->getName().'.recordkey',
                    'cols' => array("low_cny", "low_usd", 'high_cny', 'high_usd', 'average_cny', 'average_usd', 'update_time')
            ),
            array(
                    'type' => INNERJOIN,
                    'table' => $materiel->getName(),
                    'condition' => $materiel->getName().'.id = '.$fa->getName().'.id',
                    'cols' => array("name", "description")
            )
        );
        $data = $fa->getJoinList($whereSearch, $join, null, $order);
        $allData = array();
        for($i = 0; $i < count($data); $i++) {

            // 对于每个BOM，递归计算每个下级物料或BOM的价格
            if(!$data[$i]['low_cny']) {
                $price = $bomPrice->calcBomPrice($data[$i]['recordkey'], 'CNY');
                $data[$i]['low_cny'] = $price['low'];
                $data[$i]['high_cny'] = $price['high'];
                $data[$i]['average_cny'] = $price['average'];
                
                $data[$i]['low_usd'] = round($data[$i]['low_cny'] * ($rateCny / $rateUsd), 4);
                $data[$i]['high_usd'] = round($data[$i]['high_cny'] * ($rateCny / $rateUsd), 4);
                $data[$i]['average_usd'] = round($data[$i]['average_cny'] * ($rateCny / $rateUsd), 4);
            }
            
            $allData[] = $data[$i];

        }

        print(chr(0xEF).chr(0xBB).chr(0xBF));
        // 获取物料数据
        $data_csv = array();
        $title = array(
                'cnt'               => '#',
                'code'              => 'BOM号',
                'ver'               => '版本',
                'state'             => '状态',
                'low_cny'           => '最低价格-人民币',
                'high_cny'          => '最高价格-人民币',
                'average_cny'       => '平均价格-人民币',
                'low_usd'           => '最低价格-美元',
                'high_usd'          => '最高价格-美元',
                'average_usd'       => '平均价格-美元',
                'update_time'       => '更新时间',
                'name'              => '物料名称',
                'description'       => '物料描述',
                'project_no_name'   => '产品型号',
                'bom_upd_time'      => '归档时间',
                'remark'            => '备注'
        );

        $title = $this->object_array($title);
        $date = date('YmdHsi');
        $filename = 'bomprice-' . $date;
        $path = "../temp/" . $filename . ".csv";
        $file = fopen($path, "w");
        fputcsv($file, $title);
        array_push($data_csv, $title);

        $typeids = array();
        $typenames = array();
        $k = 0;
        for($i = 0; $i < count($data); $i++) {
            $d = $data[$i];

            $k++;

            $info = array(
                'cnt'               => $k,
                'code'              => Helper::ifNull($d, 'code'),
                'ver'               => "V".$d['ver'],
                'state'             => Helper::ifNull($d, 'state'),
                'low_cny'           => Helper::ifNull($d, 'low_cny'),
                'high_cny'          => Helper::ifNull($d, 'high_cny'),
                'average_cny'       => Helper::ifNull($d, 'average_cny'),
                'low_usd'           => Helper::ifNull($d, 'low_usd'),
                'high_usd'          => Helper::ifNull($d, 'high_usd'),
                'average_usd'       => Helper::ifNull($d, 'average_usd'),
                'update_time'       => $d['update_time'],
                'name'              => Helper::ifNull($d, 'name'),
                'description'       => Helper::ifNull($d, 'description'),
                'project_no_name'   => Helper::ifNull($d, 'project_no_name'),
                'bom_upd_time'      => $d['bom_upd_time'],
                'remark'            => Helper::ifNull($d, 'remark')
            );
            
            $bomd = $this->object_array($info);
            fputcsv($file, $bomd);
        }
        fclose($file);
        $this->operate("BOM导出");

        echo $filename;
        exit;
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

