<?php
/**
 * 2013-9-8
 * @author      mg.luo
 * @abstract    物料编码申请
 */
class Product_BomlistController extends Zend_Controller_Action
{

    public function indexAction()
    {

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
                'bom_upd_time'      => '归档时间',
                'remark'            => '备注'
        );

        $title = $this->object_array($title);
        $date = date('YmdHsi');
        $filename = 'bomlist-' . $date;
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
                'code'              => $this->ifNull($d, 'code')." V".$d['ver'],
                'state'             => $this->ifNull($d, 'state'),
                'name'              => $this->ifNull($d, 'name'),
                'description'       => $this->ifNull($d, 'description'),
                'project_no_name'   => $this->ifNull($d, 'project_no_name'),
                'archive_time'      => $d['bom_upd_time'],
                'remark'            => $this->ifNull($d, 'remark'),
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

