<?php
/**
 * 2014-4-1 下午9:47:41
 * @author x.li
 * @abstract 
 */
class Erp_Warehouse_PricelistController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $user_session = new Zend_Session_Namespace('user');
        
        $this->view->defaultCurrency = '';
        
        $this->view->bpartnerAdminDisabled = 1;
        $this->view->supplierAdmin = 0;
        $this->view->customerAdmin = 0;
        $this->view->editDisable = 1;
        
        $this->view->user_id = 0;
        
        if(isset($user_session->user_info)){
            $this->view->user_id = $user_session->user_info['user_id'];
            
            if(Application_Model_User::checkPermissionByRoleName('业务伙伴管理员')){
                $this->view->bpartnerAdminDisabled = 0;
                $this->view->editDisable = 0;
            }
            
            if(Application_Model_User::checkPermissionByRoleName('供应商管理员') || Application_Model_User::checkPermissionByRoleName('系统管理员')){
                $this->view->supplierAdmin = 1;
                $this->view->editDisable = 0;
            }
            
            if(Application_Model_User::checkPermissionByRoleName('客户管理员') || Application_Model_User::checkPermissionByRoleName('系统管理员')){
                $this->view->customerAdmin = 1;
                $this->view->editDisable = 0;
            }
        }
        
        $currency = new Erp_Model_Setting_Currency();
        
        $this->view->defaultCurrency = $currency->getDefaultCurrency();
    }
    
    public function testAction()
    {
        $price_list = new Erp_Model_Warehouse_Pricelist();
        
        $price = $price_list->getMultiPrice('MPS050004', 'CNY');
        
        echo '<pre>';
        print_r($price);
        
        exit;
    }
    
    public function getpriceAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'price'     => array(),
                'info'      => '获取成功'
        );
        
        $request = $this->getRequest()->getParams();
        
        $code = isset($request['code']) && $request['code'] != '' ? $request['code'] : null;
        $supplier_id = isset($request['supplier_id']) && $request['supplier_id'] != '' ? $request['supplier_id'] : null;
        $fix = isset($request['fix']) && $request['fix'] == 1 ? true : false;
        $date = isset($request['date']) && $request['date'] != '' ? $request['date'] : null;
        $qty = isset($request['qty']) && $request['qty'] != '' ? $request['qty'] : null;
        $currency = isset($request['currency']) && $request['currency'] != '' ? $request['currency'] : null;
        
        if($code && $supplier_id){
            $pricelist = new Erp_Model_Warehouse_Pricelist();
            
            $result['price'] = $pricelist->getPrice($code, $supplier_id, $fix, $date, $qty, $currency);
        }else{
            $result['success'] = false;
            $result['info'] = '料号/业务伙伴为空，价格获取失败！';
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    public function getpricelistAction()
    {
        // 请求参数
        $request = $this->getRequest()->getParams();
        $option = isset($request['option']) ? $request['option'] : 'list';
        
        $condition = array(
                'key'           => isset($request['key']) && $request['key'] != '' ? $request['key'] : null,
                'partner_type'  => isset($request['partner_type']) && $request['partner_type'] != 'null' ? $request['partner_type'] : null,
                'supplier_id'   => isset($request['supplier_id']) && $request['supplier_id'] != 'null' ? $request['supplier_id'] : null,
                'page'          => isset($request['page']) ? $request['page'] : 1,
                'limit'         => isset($request['limit']) ? $request['limit'] : 0,
                'type'          => $option
        );
        //echo '<pre>';print_r($condition);exit;
        if($condition['partner_type'] != null){
            $pricelist = new Erp_Model_Warehouse_Pricelist();
            $data = $pricelist->getData($condition);
            
            if($option == 'csv'){
                $this->view->layout()->disableLayout();
                $this->_helper->viewRenderer->setNoRender(true);
            
                $h = new Application_Model_Helpers();
                $h->exportCsv($data, '价格清单');
            }else{
                echo Zend_Json::encode($data);
            }
        }
        
        exit;
    }
    
    // 获取物料号选项列表
    public function getcodelistAction()
    {
        $data = array();
        
        $code = new Product_Model_Materiel();
        
        echo Zend_Json::encode($code->getOptionList());
        
        exit;
    }
    
    public function editAction()
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
        
        $pricelist = new Erp_Model_Warehouse_Pricelist();
        
        if(count($updated) > 0){
            foreach ($updated as $val){
                $data = array(
                        'type'          => $val->type,
                        'supplier_id'   => $val->supplier_id,
                        'price'         => $val->price,
                        'currency'      => $val->currency,
                        'remark'        => $val->remark,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
                
                if($val->type == 1){
                    $code = $val->product_code;
                    $data['product_code'] = $val->product_code;
                }else{
                    $code = $val->code;
                    $data['code'] = $val->code;
                }
                
                if($pricelist->fetchAll("id != ".$val->id." and type = ".$val->type." and supplier_id = ".$val->supplier_id." and code = '".$code."'")->count() > 0){
                    $result['success'] = false;
                    $result['info'] = '当前业务伙伴物料['.$val->code.']已存在，请勿重复添加！';
                }else{
                    try {
                        $pricelist->update($data, "id = ".$val->id);
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
                        'type'          => $val->type,
                        'supplier_id'   => $val->supplier_id,
                        'price'         => $val->price,
                        'currency'      => $val->currency,
                        'remark'        => $val->remark,
                        'create_time'   => $now,
                        'create_user'   => $user_id,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
                
                if($val->type == 1){
                    $code = $val->product_code;
                    $data['product_code'] = $val->product_code;
                }else{
                    $code = $val->code;
                    $data['code'] = $val->code;
                }
        
                if($pricelist->fetchAll("type = ".$val->type." and supplier_id = ".$val->supplier_id." and code = '".$code."'")->count() > 0){
                    $result['success'] = false;
                    $result['info'] = '当前业务伙伴物料['.$val->code.']已存在，请勿重复添加！';
                }else{
                    try{
                        $pricelist->insert($data);
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
            $ladder = new Erp_Model_Warehouse_Ladder();
            $qty = new Erp_Model_Warehouse_Ladderqty();
            
            foreach ($deleted as $val){
                try {
                    $ladderRes = $ladder->fetchAll("pricelist_id = ".$val->id);
                    
                    if($ladderRes->count() > 0){
                        $ladderData = $ladderRes->toArray();
                        
                        foreach ($ladderData as $ld){
                            $qty->delete("ladder_id = ".$ld['id']);
                        }
                        
                        $ladder->delete("pricelist_id = ".$val->id);
                    }
                    
                    $pricelist->delete("id = ".$val->id);
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
    
    public function getladderAction()
    {
        $request = $this->getRequest()->getParams();
        $pricelist_id = isset($request['pricelist_id']) ? $request['pricelist_id'] : null;
        
        if($pricelist_id){
            $ladder = new Erp_Model_Warehouse_Ladder();
            
            echo Zend_Json::encode($ladder->getData($pricelist_id));
        }
        
        exit;
    }
    
    public function editladderAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '编辑成功'
        );
        
        $request = $this->getRequest()->getParams();
        
        // 价格清单ID
        $pricelist_id = isset($request['pricelist_id']) ? $request['pricelist_id'] : null;
        
        if($pricelist_id){
            $now = date('Y-m-d H:i:s');
            $user_session = new Zend_Session_Namespace('user');
            $user_id = $user_session->user_info['user_id'];
            
            $json = json_decode($request['json']);
            
            $updated    = $json->updated;
            $inserted   = $json->inserted;
            $deleted    = $json->deleted;
            
            $ladder = new Erp_Model_Warehouse_Ladder();
            
            if(count($updated) > 0){
                foreach ($updated as $val){
                    $val->date = substr($val->date, 0, 10);
                    
                    $data = array(
                            'pricelist_id'  => $pricelist_id,
                            'date'          => $val->date,
                            'price'         => $val->price,
                            'currency'      => $val->currency,
                            'remark'        => $val->remark,
                            'update_time'   => $now,
                            'update_user'   => $user_id
                    );
            
                    if($ladder->fetchAll("id != ".$val->id." and pricelist_id = ".$pricelist_id." and date = '".$val->date."' and price = ".$val->price)->count() > 0){
                        $result['success'] = false;
                        $result['info'] = '价格清单重复，请勿重复添加！';
                    }else{
                        try {
                            $ladder->update($data, "id = ".$val->id);
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
                            'pricelist_id'  => $pricelist_id,
                            'date'          => $val->date,
                            'price'         => $val->price,
                            'currency'      => $val->currency,
                            'remark'        => $val->remark,
                            'create_time'   => $now,
                            'create_user'   => $user_id,
                            'update_time'   => $now,
                            'update_user'   => $user_id
                    );
            
                    if($ladder->fetchAll("pricelist_id = ".$pricelist_id." and date = '".$val->date."' and price = ".$val->price)->count() > 0){
                        $result['success'] = false;
                        $result['info'] = '价格清单重复，请勿重复添加！';
                    }else{
                        try{
                            $ladder->insert($data);
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
                $qty = new Erp_Model_Warehouse_Ladderqty();
                
                foreach ($deleted as $val){
                    try {
                        // 删除日期起数量阶梯价
                        $qty->delete("ladder_id = ".$val->id);
                        $ladder->delete("id = ".$val->id);
                    } catch (Exception $e){
                        $result['success'] = false;
                        $result['info'] = $e->getMessage();
            
                        echo Zend_Json::encode($result);
            
                        exit;
                    }
                }
            }
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    public function getladderqtyAction()
    {
        $request = $this->getRequest()->getParams();
        $ladder_id = isset($request['ladder_id']) ? $request['ladder_id'] : null;
        
        if($ladder_id){
            $qty = new Erp_Model_Warehouse_Ladderqty();
            
            echo Zend_Json::encode($qty->getData($ladder_id));
        }
        
        exit;
    }
    
    public function editladderqtyAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '编辑成功'
        );
        
        $request = $this->getRequest()->getParams();
        
        // 价格清单ID
        $ladder_id = isset($request['ladder_id']) ? $request['ladder_id'] : null;
        
        if($ladder_id){
            $now = date('Y-m-d H:i:s');
            $user_session = new Zend_Session_Namespace('user');
            $user_id = $user_session->user_info['user_id'];
            
            $json = json_decode($request['json']);
            
            $updated    = $json->updated;
            $inserted   = $json->inserted;
            $deleted    = $json->deleted;
            
            $qty = new Erp_Model_Warehouse_Ladderqty();
            
            if(count($updated) > 0){
                foreach ($updated as $val){
                    $data = array(
                            'ladder_id'     => $ladder_id,
                            'qty'           => $val->qty,
                            'price'         => $val->price,
                            'currency'      => $val->currency,
                            'remark'        => $val->remark,
                            'update_time'   => $now,
                            'update_user'   => $user_id
                    );
            
                    if($qty->fetchAll("id != ".$val->id." and ladder_id = ".$ladder_id." and qty = ".$val->qty." and price = ".$val->price)->count() > 0){
                        $result['success'] = false;
                        $result['info'] = '价格清单重复，请勿重复添加！';
                    }else{
                        try {
                            $qty->update($data, "id = ".$val->id);
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
                            'ladder_id'     => $ladder_id,
                            'qty'           => $val->qty,
                            'price'         => $val->price,
                            'currency'      => $val->currency,
                            'remark'        => $val->remark,
                            'create_time'   => $now,
                            'create_user'   => $user_id,
                            'update_time'   => $now,
                            'update_user'   => $user_id
                    );
                    
                    if($qty->fetchAll("ladder_id = ".$ladder_id." and qty = ".$val->qty." and price = ".$val->price)->count() > 0){
                        $result['success'] = false;
                        $result['info'] = '价格清单重复，请勿重复添加！';
                    }else{
                        try{
                            $qty->insert($data);
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
                        $qty->delete("id = ".$val->id);
                    } catch (Exception $e){
                        $result['success'] = false;
                        $result['info'] = $e->getMessage();
            
                        echo Zend_Json::encode($result);
            
                        exit;
                    }
                }
            }
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
}