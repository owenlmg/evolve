<?php
/**
 * 2014-4-5
 * @author      mg.luo
 * @abstract    价格清单
 */
class Product_PriceController extends Zend_Controller_Action
{

    public function indexAction()
    {

    }

    public function getlistAction() {
        $request = $this->getRequest()->getParams();
        $limit = $request['limit'];
        $start = $request['start'];
        $whereSearch = "1=1";
        foreach ($request as $k => $v) {
            if ($v) {
                if($k == 'search_tag') {
                    $whereSearch .= " and (ifnull(t1.code,'') like '%$v%' or ifnull(t2.name,'') like '%$v%' or ifnull(t2.description,'') like '%$v%' or ifnull(t3.code,'') like '%$v%' or ifnull(t3.cname,'') like '%$v%')";
                } else {
                    $col = str_replace('search_', '', $k);
                    if ($col != $k) {
                        // 查询条件
                        $whereSearch .= " and ifnull(t1." . $col . ",'') like '%" . $v . "%'";
                    }
                }
            }
        }

        $price = new Product_Model_Price();
        $materiel = new Product_Model_Materiel();
        $time = date('Y-m-d H:i:s');
        // 获取物料数据
        $data = $price->getList($whereSearch, $time, $start, $limit);
        $count = $price->getCount($whereSearch, $time, $start, $limit);
        $totalCount = $count;
        for($i = 0; $i < count($data); $i++) {
            /*if(($typeId = $data[$i]['type']) != '') {
                $typeName = $this->getTypeByConnect($typeId, '');
                $data[$i]['type_name'] = $typeName;
            }*/
            $data[$i]['update_time'] = strtotime($data[$i]['update_time']);
            $data[$i]['supply'] = $data[$i]['supply_code'].$data[$i]['supply_name'];
            $data[$i]['currency'] = $data[$i]['bank_currency'];
        }
        $resutl = array(
            "totalCount" => $totalCount,
            "topics" => $data
        );
        // 转为json格式并输出
        echo Zend_Json::encode($resutl);

        exit;
    }

    private function getTypeByConnect($id, $name) {
        if ($id) {
            $type = new Product_Model_Type();
            $row = $type->fetchRow("id = $id");
            if ($row) {
                $id = $row->parent_id;
                if($id == 0) {
                   $name = "<b>".$row->name . ' &gt; '."</b>" . $name;
                } else {
                   $name = $row->name . ' &gt; ' . $name;
                }

                return $this->getTypeByConnect($id, $name);
            }
        }
        return trim($name, ' &gt; ');
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
            $where = "code like '%$query%' or cname like '%$query%' or ename like '%$query%'";
        } else {
            $where = "1=1";
        }

        $data = $bpartner->getListForSel($where);
        $result = array();
        for($i = 0; $i < count($data); $i++) {
            if(($code = $data[$i]['code']) != '') {
                $result[$i] = array();
                $result[$i]['supply_code'] = $code;
                if(($cname = $data[$i]['cname']) != '') {
                    $result[$i]['supply_name'] = $cname;
                } else if(($ename = $data[$i]['ename']) != '') {
                    $result[$i]['supply_name'] = $ename;
                } else {
                    $result[$i]['supply_name'] = '';
                }
                $result[$i]['supply'] = $result[$i]['supply_code'].$result[$i]['supply_name'];

                $result[$i]['currency'] = $data[$i]['bank_currency'];
            }
        }
        // 转为json格式并输出
        echo Zend_Json::encode($result);

        exit;
    }

    /**
     * @abstract    取得物料信息
     * @return      物料代码+物料代码
     */
    public function getmaterielAction() {
        $result = $this->getRequest()->getParams();

        $materiel = new Product_Model_Materiel();
        if(isset($result['q']) && $result['q']) {
            $query = $result['q'];
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

    public function getcurrencyAction() {
        $currency = new Product_Model_Currency();
        $data = $currency->getList("1=1");
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
                'info'      => '保存成功'
        );

        $request = $this->getRequest()->getParams();

        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];

        $json = json_decode($request['json']);

        $updated    = $json->updated;
        $inserted   = $json->inserted;
        $deleted    = $json->deleted;

        $price = new Product_Model_Price();

        if(count($updated) > 0){
            foreach ($updated as $val){
                if($val->id) {
                    // 检查数据是否重复
                    $this->checkExtists($val);

                    $data = array(
                            'code'          => $val->code,
                            'supply_code'   => $val->supply_code,
                            'min_num'       => $val->min_num,
                            'max_num'       => $val->max_num,
                            'currency'      => $val->currency,
                            'price'         => $val->price,
                            'update_user'   => $user,
                            'update_time'   => $now
                    );

                    $where = "id = ".$val->id;

                    try {
                        $price->update($data, $where);
                    } catch (Exception $e) {
                        $result['result'] = false;
                        $result['info'] = $e->getMessage();
                        echo Zend_Json::encode($result);
                        exit;
                    }
                }
            }
        }

        if(count($inserted) > 0){
            foreach ($inserted as $val){
                // 检查数据是否重复
                $this->checkExtists($val);
                $data = array(
                            'code'          => $val->code,
                            'supply_code'   => $val->supply_code,
                            'min_num'       => $val->min_num,
                            'max_num'       => $val->max_num,
                            'currency'      => $val->currency,
                            'price'         => $val->price,
                            'update_user'   => $user,
                            'update_time'   => $now
                    );

                try{
                    $price->insert($data);
                } catch (Exception $e){
                    $result['result'] = false;
                    $result['info'] = $e->getMessage();
                    echo Zend_Json::encode($result);
                    exit;
                }
            }
        }

        if(count($deleted) > 0){
            foreach ($deleted as $val){
                try {
                    $price->delete("id = ".$val->id);
                } catch (Exception $e){
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

    private function checkExtists($data) {
        $code = $data->code;
        $supply_code = $data->supply_code;
        $min_num = $data->min_num;
        $max_num = $data->max_num;
        $price = $data->price;
        if(isset($data->id) && $data->id) {
            $id = $data->id;
        }
        $now = date('Y-m-d H:i:s');

        $priceModel = new Product_Model_Price();

        $result = array(
                'success'   => true,
                'info'      => '保存成功'
        );
        if(!$code) {
            $result['result'] = false;
            $result['info'] = '物料代码未填写！';
            echo Zend_Json::encode($result);
            exit;
        }
        if(!$price) {
            $result['result'] = false;
            $result['info'] = '价格未填写！';
            echo Zend_Json::encode($result);
            exit;
        }
        $where = "t1.code='$code' and !(min_num > $max_num or max_num < $min_num)";
        if(isset($id)) {
            $where .= " and t1.id!=$id";
        }
        if($supply_code) {
            $where .= " and supply_code = '$supply_code'";
        }
        if(count($priceModel->getList($where, $now, null, null)) > 0) {
            $result['result'] = false;
            $result['info'] = '此物料代码的价格已存在！';
            echo Zend_Json::encode($result);
            exit;
        }

        return true;
    }

}

