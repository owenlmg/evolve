<?php
/**
 * 2014-4-1 下午9:47:41
 * @author x.li
 * @abstract 
 */
class Erp_Warehouse_IndexController extends Zend_Controller_Action
{
    public function indexAction()
    {
        
    }
    
    public function getlistAction()
    {
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        $type = isset($request['type']) ? $request['type'] : null;
        
        $warehouse = new Erp_Model_Warehouse_Warehouse();
        
        echo Zend_Json::encode($warehouse->getList($type));
        
        exit;
    }
    
    public function getwarehouseAction()
    {
        // 请求参数
        $request = $this->getRequest()->getParams();
        $option = isset($request['option']) ? $request['option'] : 'list';
        
        $condition = array(
                'key'           => isset($request['key']) && $request['key'] != '' ? $request['key'] : null,
                'type_id'       => isset($request['type_id']) && $request['type_id'] != 'null' ? $request['type_id'] : null,
                'page'          => isset($request['page']) ? $request['page'] : 1,
                'limit'         => isset($request['limit']) ? $request['limit'] : 0,
                'type'          => $option
        );
        
        $warehouse = new Erp_Model_Warehouse_Warehouse();
        $data = $warehouse->getData($condition);
        
        if($option == 'csv'){
            $this->view->layout()->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);
        
            $h = new Application_Model_Helpers();
            $h->exportCsv($data, '仓库列表');
        }else{
            echo Zend_Json::encode($data);
        }
        
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
        
        $warehouse = new Erp_Model_Warehouse_Warehouse();
        
        if(count($updated) > 0){
            foreach ($updated as $val){
                $data = array(
                        'active'        => $val->active,
                        'in_stock'      => $val->in_stock,
                        'locked'        => $val->locked,
                        'code'          => $val->code,
                        'name'          => $val->name,
                        'type_id'       => $val->type_id,
                        'description'   => $val->description,
                        'remark'        => $val->remark,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
        
                if($warehouse->fetchAll("id != ".$val->id." and (name = '".$val->name."' or code = '".$val->code."')")->count() > 0){
                    $result['success'] = false;
                    $result['info'] = '当前仓位['.$val->code.'] ['.$val->name.']已存在，请勿重复添加！';
                }else{
                    try {
                        $warehouse->update($data, "id = ".$val->id);
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
                        'in_stock'      => $val->in_stock,
                        'locked'        => $val->locked,
                        'code'          => $val->code,
                        'name'          => $val->name,
                        'type_id'       => $val->type_id,
                        'description'   => $val->description,
                        'remark'        => $val->remark,
                        'create_time'   => $now,
                        'create_user'   => $user_id,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
        
                if($warehouse->fetchAll("name = '".$val->name."' or code = '".$val->code."'")->count() > 0){
                    $result['success'] = false;
                    $result['info'] = '当前仓位['.$val->code.'] ['.$val->name.']已存在，请勿重复添加！';
                }else{
                    try{
                        $warehouse->insert($data);
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
                    $warehouse->delete("id = ".$val->id);
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
    
    public function gettypeAction()
    {
        // 请求参数
        $request = $this->getRequest()->getParams();
    
        $option = isset($request['option']) ? $request['option'] : 'list';
    
        $type = new Erp_Model_Warehouse_Warehousetype();
    
        if($option == 'list'){
            echo Zend_Json::encode($type->getList());
        }else{
            echo Zend_Json::encode($type->getData());
        }
    
        exit;
    }
    
    public function gettransactionAction()
    {
        // 请求参数
        $request = $this->getRequest()->getParams();
    
        $option = isset($request['option']) ? $request['option'] : 'list';
    
        $transaction = new Erp_Model_Warehouse_Warehousetransaction();
    
        if($option == 'list'){
            echo Zend_Json::encode($transaction->getList());
        }else{
            echo Zend_Json::encode($transaction->getData());
        }
    
        exit;
    }
    
    public function getreceiverAction()
    {
        // 请求参数
        $request = $this->getRequest()->getParams();
    
        $option = isset($request['option']) ? $request['option'] : 'list';
    
        $receiver = new Erp_Model_Warehouse_Warehousereceiver();
    
        if($option == 'list'){
            echo Zend_Json::encode($receiver->getList());
        }else{
            echo Zend_Json::encode($receiver->getData());
        }
    
        exit;
    }
    
    public function edittypeAction()
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
    
        $type = new Erp_Model_Warehouse_Warehousetype();
    
        if(count($updated) > 0){
            foreach ($updated as $val){
                $data = array(
                        'active'        => $val->active,
                        'code'          => $val->code,
                        'name'          => $val->name,
                        'description'   => $val->description,
                        'remark'        => $val->remark,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
    
                if($type->fetchAll("id != ".$val->id." and name = '".$val->name."'")->count() > 0){
                    $result['success'] = false;
                    $result['info'] = '仓库：'.$val->name."已存在，请勿重复添加！";
                }else{
                    try {
                        $type->update($data, "id = ".$val->id);
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
                        'code'          => $val->code,
                        'name'          => $val->name,
                        'description'   => $val->description,
                        'remark'        => $val->remark,
                        'create_time'   => $now,
                        'create_user'   => $user_id,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
    
                if($type->fetchAll("name = '".$val->name."'")->count() > 0){
                    $result['success'] = false;
                    $result['info'] = '仓库：'.$val->name."已存在，请勿重复添加！";
                }else{
                    try{
                        $type->insert($data);
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
            $warehouse = new Erp_Model_Warehouse_Warehouse();
    
            foreach ($deleted as $val){
                if($warehouse->fetchAll("type_id = ".$val->id)->count() == 0){
                    try {
                        $type->delete("id = ".$val->id);
                    } catch (Exception $e){
                        $result['success'] = false;
                        $result['info'] = $e->getMessage();
    
                        echo Zend_Json::encode($result);
    
                        exit;
                    }
                }else{
                    $result['success'] = false;
                    $result['info'] = '仓库ID'.$val->id.'存在关联仓位信息，不能删除';
    
                    echo Zend_Json::encode($result);
    
                    exit;
                }
            }
        }
    
        echo Zend_Json::encode($result);
    
        exit;
    }
    
    public function edittransactionAction()
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
    
        $transaction = new Erp_Model_Warehouse_Warehousetransaction();
    
        if(count($updated) > 0){
            foreach ($updated as $val){
                $data = array(
                        'active'        => $val->active,
                        'name'          => $val->name,
                        'description'   => $val->description,
                        'remark'        => $val->remark,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
    
                if($transaction->fetchAll("id != ".$val->id." and name = '".$val->name."'")->count() > 0){
                    $result['success'] = false;
                    $result['info'] = '库存交易类别：'.$val->name."已存在，请勿重复添加！";
                }else{
                    try {
                        $transaction->update($data, "id = ".$val->id);
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
                        'name'          => $val->name,
                        'description'   => $val->description,
                        'remark'        => $val->remark,
                        'create_time'   => $now,
                        'create_user'   => $user_id,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
    
                if($transaction->fetchAll("name = '".$val->name."'")->count() > 0){
                    $result['success'] = false;
                    $result['info'] = '库存交易类别：'.$val->name."已存在，请勿重复添加！";
                }else{
                    try{
                        $transaction->insert($data);
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
            try {
                $transaction->delete("id = ".$val->id);
            } catch (Exception $e){
                $result['success'] = false;
                $result['info'] = $e->getMessage();
            
                echo Zend_Json::encode($result);
            
                exit;
            }
        }
    
        echo Zend_Json::encode($result);
    
        exit;
    }
    
    public function editreceiverAction()
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
    
        $receiver = new Erp_Model_Warehouse_Warehousereceiver();
    
        if(count($updated) > 0){
            foreach ($updated as $val){
                $data = array(
                        'active'        => $val->active,
                        'user_id'       => $val->user_id,
                        'address'       => $val->address,
                        'address_en'     => $val->address_en,
                        'tel'           => $val->tel,
                        'fax'           => $val->fax,
                        'remark'        => $val->remark,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
    
                if($receiver->fetchAll("id != ".$val->id." and user_id = ".$val->user_id)->count() > 0){
                    $result['success'] = false;
                    $result['info'] = "收货人已存在，请勿重复添加！";
                }else{
                    try {
                        $receiver->update($data, "id = ".$val->id);
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
                        'user_id'       => $val->user_id,
                        'address'       => $val->address,
                        'address_en'    => $val->address_en,
                        'tel'           => $val->tel,
                        'fax'           => $val->fax,
                        'remark'        => $val->remark,
                        'create_time'   => $now,
                        'create_user'   => $user_id,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
    
                if($receiver->fetchAll("user_id = ".$val->user_id)->count() > 0){
                    $result['success'] = false;
                    $result['info'] = "收货人已存在，请勿重复添加！";
                }else{
                    try{
                        $receiver->insert($data);
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
                    $receiver->delete("id = ".$val->id);
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
}