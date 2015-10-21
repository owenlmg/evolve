<?php
/**
 * 2014-3-30 上午11:40:53
 * @author x.li
 * @abstract 
 */
class Erp_Setting_TaxController extends Zend_Controller_Action
{
    public function indexAction()
    {
        
    }
    
    public function gettaxAction()
    {
        // 请求参数
        $request = $this->getRequest()->getParams();
    
        $option = isset($request['option']) ? $request['option'] : 'list';
    
        $tax = new Erp_Model_Setting_Tax();
    
        if($option == 'list'){
            echo Zend_Json::encode($tax->getList());
        }else{
            echo Zend_Json::encode($tax->getData());
        }
    
        exit;
    }
    
    public function edittaxAction()
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
    
        $tax = new Erp_Model_Setting_Tax();
    
        if(count($updated) > 0){
            foreach ($updated as $val){
                $data = array(
                        'active'        => $val->active,
                        'default'       => $val->default,
                        'code'     => $val->code,
                        'name'          => $val->name,
                        'remark'        => $val->remark,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
    
                if($tax->fetchAll("id != ".$val->id." and (name = '".$val->name."' or code = '".$val->code."')")->count() > 0){
                    $result['success'] = false;
                    $result['info'] = '货币：'.$val->name."已存在，请勿重复添加！";
                }else{
                    try {
                        $tax->update($data, "id = ".$val->id);
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
                        'name'          => $val->name,
                        'remark'        => $val->remark,
                        'create_time'   => $now,
                        'create_user'   => $user_id,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
    
                if($tax->fetchAll("name = '".$val->name."' or code = '".$val->code."'")->count() > 0){
                    $result['success'] = false;
                    $result['info'] = '货币：'.$val->name."已存在，请勿重复添加！";
                }else{
                    try{
                        $tax->insert($data);
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
                    $tax->delete("id = ".$val->id);
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
     * 获取税率
     */
    public function gettaxrateAction()
    {
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        if(isset($request['tax_id'])){
            $tax_rate = new Erp_Model_Setting_Taxrate();
            echo Zend_Json::encode($tax_rate->getData($request['tax_id']));
        }
    
        exit;
    }
    
    public function edittaxrateAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '编辑成功'
        );
    
        $request = $this->getRequest()->getParams();
        
        $tax_id = isset($request['tax_id']) ? $request['tax_id'] : null;
        
        if($tax_id){
            $now = date('Y-m-d H:i:s');
            $user_session = new Zend_Session_Namespace('user');
            $user_id = $user_session->user_info['user_id'];
        
            $json = json_decode($request['json']);
            
            $updated    = $json->updated;
            $inserted   = $json->inserted;
            $deleted    = $json->deleted;
        
            $tax_rate = new Erp_Model_Setting_Taxrate();
        
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
                    
                    if($tax_rate->fetchAll("id != ".$val->id." and tax_id = ".$tax_id." and date = '".$val->date."'")->count() > 0){
                        $result['success'] = false;
                        $result['info'] = '当前税率的生效日期['.$val->date.']已存在,请勿重复添加!';
                    }else{
                        try {
                            $tax_rate->update($data, "id = ".$val->id);
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
                            'tax_id'   => $tax_id,
                            'date'          => $val->date,
                            'rate'          => $val->rate,
                            'remark'        => $val->remark,
                            'create_time'   => $now,
                            'create_user'   => $user_id,
                            'update_time'   => $now,
                            'update_user'   => $user_id
                    );
                    
                    if($tax_rate->fetchAll("tax_id = ".$tax_id." and date = '".$val->date."'")->count() > 0){
                        $result['success'] = false;
                        $result['info'] = '当前税率的生效日期['.$val->date.']已存在,请勿重复添加!';
                    }else{
                        try{
                            $tax_rate->insert($data);
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
                            $tax_rate->delete("id = ".$val->id);
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
            $result['info'] = '保存失败，没有选择税种！';
        }
        
        echo Zend_Json::encode($result);
    
        exit;
    }
}