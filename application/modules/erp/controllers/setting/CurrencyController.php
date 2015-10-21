<?php
/**
 * 2014-3-30 上午11:40:53
 * @author x.li
 * @abstract 
 */
class Erp_Setting_CurrencyController extends Zend_Controller_Action
{
    public function indexAction()
    {
        
    }
    
    /**
     * 获取币种
     */
    public function getcurrencyAction()
    {
        // 请求参数
        $request = $this->getRequest()->getParams();
    
        $option = isset($request['option']) ? $request['option'] : 'list';
    
        $currency = new Erp_Model_Setting_Currency();
    
        if($option == 'list'){
            echo Zend_Json::encode($currency->getList());
        }else{
            echo Zend_Json::encode($currency->getData());
        }
    
        exit;
    }
    
    public function editcurrencyAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '编辑成功'
        );
    
        $request = $this->getRequest()->getParams();
    
        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user_id = $user_session->user_info['user_id'];
    
        $json = json_decode($request['json']);
    
        $updated    = $json->updated;
        $inserted   = $json->inserted;
        $deleted    = $json->deleted;
    
        $currency = new Erp_Model_Setting_Currency();
    
        if(count($updated) > 0){
            foreach ($updated as $val){
                $data = array(
                        'active'        => $val->active,
                        'default'       => $val->default,
                        'code'     => $val->code,
                        'symbol'        => $val->symbol,
                        'name'          => $val->name,
                        'remark'        => $val->remark,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
    
                if($currency->fetchAll("id != ".$val->id." and (name = '".$val->name."' or code = '".$val->code."' or symbol = '".$val->symbol."')")->count() > 0){
                    $result['success'] = false;
                    $result['info'] = '货币：'.$val->name."已存在，请勿重复添加！";
                }else{
                    try {
                        $currency->update($data, "id = ".$val->id);
                    } catch (Exception $e) {
                        $result['success'] = false;
                        $result['info'] = $e->getMessage();
    
                        echo Zend_Json::encode($result);
    
                        exit;
                    }
                }
            }
        }
    
        if(count($inserted) > 0){
            foreach ($inserted as $val){
                $data = array(
                        'active'        => $val->active,
                        'default'       => $val->default,
                        'code'     => $val->code,
                        'symbol'        => $val->symbol,
                        'name'          => $val->name,
                        'remark'        => $val->remark,
                        'create_time'   => $now,
                        'create_user'   => $user_id,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
    
                if($currency->fetchAll("name = '".$val->name."' or code = '".$val->code."' or symbol = '".$val->symbol."'")->count() > 0){
                    $result['success'] = false;
                    $result['info'] = '货币：'.$val->name."已存在，请勿重复添加！";
                }else{
                    try{
                        $currency->insert($data);
                    } catch (Exception $e){
                        $result['success'] = false;
                        $result['info'] = $e->getMessage();
    
                        echo Zend_Json::encode($result);
    
                        exit;
                    }
                }
            }
        }
    
        if(count($deleted) > 0){
            foreach ($deleted as $val){
                try {
                    $currency->delete("id = ".$val->id);
                } catch (Exception $e){
                    $result['success'] = false;
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
     * 获取汇率
     */
    public function getcurrencyrateAction()
    {
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        if(isset($request['currency_id'])){
            $currency_rate = new Erp_Model_Setting_Currencyrate();
            echo Zend_Json::encode($currency_rate->getData($request['currency_id']));
        }
    
        exit;
    }
    
    public function editcurrencyrateAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '编辑成功'
        );
    
        $request = $this->getRequest()->getParams();
        
        $currency_id = isset($request['currency_id']) ? $request['currency_id'] : null;
        
        if($currency_id){
            $now = date('Y-m-d H:i:s');
            $user_session = new Zend_Session_Namespace('user');
            $user_id = $user_session->user_info['user_id'];
        
            $json = json_decode($request['json']);
            
            $updated    = $json->updated;
            $inserted   = $json->inserted;
            $deleted    = $json->deleted;
        
            $currency_rate = new Erp_Model_Setting_Currencyrate();
        
            if(count($updated) > 0){
                foreach ($updated as $val){
                    $val->date = substr($val->date, 0, 10);
                    
                    $data = array(
                            'date'          => $val->date,
                            'rate'          => $val->rate,
                            'remark'        => $val->remark,
                            'update_time'   => $now,
                            'update_user'   => $user_id
                    );
                    
                    if($currency_rate->fetchAll("id != ".$val->id." and currency_id = ".$currency_id." and date = '".$val->date."'")->count() > 0){
                        $result['success'] = false;
                        $result['info'] = '当前货币汇率的生效日期['.$val->date.']已存在,请勿重复添加!';
                    }else{
                        try {
                            $currency_rate->update($data, "id = ".$val->id);
                        } catch (Exception $e) {
                            $result['success'] = false;
                            $result['info'] = $e->getMessage();
        
                            echo Zend_Json::encode($result);
        
                            exit;
                        }
                    }
                }
            }
        
            if(count($inserted) > 0){
                foreach ($inserted as $val){
                    $val->date = substr($val->date, 0, 10);
                    
                    $data = array(
                            'currency_id'   => $currency_id,
                            'date'          => $val->date,
                            'rate'          => $val->rate,
                            'remark'        => $val->remark,
                            'create_time'   => $now,
                            'create_user'   => $user_id,
                            'update_time'   => $now,
                            'update_user'   => $user_id
                    );
                    
                    if($currency_rate->fetchAll("currency_id = ".$currency_id." and date = '".$val->date."'")->count() > 0){
                        $result['success'] = false;
                        $result['info'] = '当前货币汇率的生效日期['.$val->date.']已存在,请勿重复添加!';
                    }else{
                        try{
                            $currency_rate->insert($data);
                        } catch (Exception $e){
                            $result['success'] = false;
                            $result['info'] = $e->getMessage();
        
                            echo Zend_Json::encode($result);
        
                            exit;
                        }
                    }
                }
            }
            
            if(count($deleted) > 0){
                foreach ($deleted as $val){
                    $val->date = substr($val->date, 0, 10);
                    
                    if(time() >= strtotime($val->date)){
                        $result['success'] = false;
                        $result['info'] = '当前日期已超过['.$val->date.']，不能删除!';
                    }else{
                        try {
                            $currency_rate->delete("id = ".$val->id);
                        } catch (Exception $e){
                            $result['success'] = false;
                            $result['info'] = $e->getMessage();
                        
                            echo Zend_Json::encode($result);
                        
                            exit;
                        }
                    }
                }
            }
        }else{
            $result['success'] = false;
            $result['info'] = '保存失败，没有选择货币！';
        }
        
        echo Zend_Json::encode($result);
    
        exit;
    }
}