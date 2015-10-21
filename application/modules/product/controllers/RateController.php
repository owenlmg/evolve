<?php
/**
 * 2014-4-5
 * @author      mg.luo
 * @abstract    价格清单
 */
class Product_RateController extends Zend_Controller_Action
{

    public function indexAction()
    {

    }

    public function getlistAction() {
        $rate = new Product_Model_Rate();
        // 获取物料数据
        $data = $rate->getList("1=1");
        for($i = 0; $i < count($data); $i++) {
            $data[$i]['update_time'] = strtotime($data[$i]['update_time']);
            $data[$i]['start_time'] = strtotime($data[$i]['start_time']);
            $data[$i]['end_time'] = strtotime($data[$i]['end_time']);
        }
        // 转为json格式并输出
        echo Zend_Json::encode($data);

        exit;
    }

    public function getcurrencyAction() {
        $currency = new Product_Model_Currency();
        $data = $currency->getList("1=1");
        // 转为json格式并输出
        echo Zend_Json::encode($data);

        exit;
    }
    function getExchangeRate($from_Currency,$to_Currency) {
        $from_Currency = urlencode($from_Currency);
        $to_Currency = urlencode($to_Currency);
        $url = "download.finance.yahoo.com/d/quotes.html?s=".$from_Currency.$to_Currency."=X&f=sl1d1t1ba&e=.html";
        $ch = curl_init();
        $timeout = 0;
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,  CURLOPT_USERAGENT , "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)");
          curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $rawdata = curl_exec($ch);
        curl_close($ch);
        $data = explode(',', $rawdata);
        return $data[1];
    }

    public function getrateAction() {
        $request = $this->getRequest()->getParams();

        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];

        $json = json_decode($request['json']);
        $result = array();
        foreach($json as $val) {
            if($val) {
                $rate = $this->getExchangeRate($val,"cny");
                $result[$val] = $rate;
            }
        }

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

        $rate = new Product_Model_Rate();

        if(count($updated) > 0){
            foreach ($updated as $val){
                if($val->id) {
                    // 检查数据是否重复
                    $this->checkExtists($val);

                    $start_time = date('Y-m-d H:i:s');
                    if(isset($val->start_time)) {
                        $start_time = str_replace("T", " ", $val->start_time);
                    }

                    $data = array(
                            'currency'      => $val->currency,
                            'rate'          => $val->rate,
                            'start_time'    => $start_time,
                            'update_user'   => $user,
                            'update_time'   => $now
                    );

                    $where = "id = ".$val->id;

                    try {
                        $rate->update($data, $where);
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

                $start_time = date('Y-m-d H:i:s');
                if(isset($val->start_time)) {
                    $start_time = $start_time = str_replace("T", " ", $val->start_time);
                }
                $data = array(
                            'currency'      => $val->currency,
                            'rate'          => $val->rate,
                            'start_time'    => $start_time,
                            'update_user'   => $user,
                            'update_time'   => $now
                    );

                try{
                    $rate->insert($data);
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
                    $rate->delete("id = ".$val->id);
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
        $currency = $data->currency;
        $rate = $data->rate;

        if(isset($data->id) && $data->id) {
            $id = $data->id;
        }

        $rateModel = new Product_Model_Rate();

        $result = array(
                'success'   => true,
                'info'      => '保存成功'
        );
        if(!$currency) {
            $result['result'] = false;
            $result['info'] = '币种未填写！';
            echo Zend_Json::encode($result);
            exit;
        }
        if(!$rate) {
            $result['result'] = false;
            $result['info'] = '汇率未填写！';
            echo Zend_Json::encode($result);
            exit;
        }
        $where = "t1.currency='$currency'";
        if(isset($id)) {
            $where .= " and t1.id!=$id";
        }
        if(count($rateModel->getList($where)) > 0) {
            $result['result'] = false;
            $result['info'] = '此币种的汇率已存在！';
            echo Zend_Json::encode($result);
            exit;
        }

        return true;
    }

}

