<?php
/**
 * 2013-9-11 下午10:47:35
 * @author x.li
 * @abstract 
 */
class Erp_Model_Purchse_Receiveitemsorder extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'erp_stock_receive_items_order';
    protected $_primary = 'id';
    
    public function getOrderItemReceivedQty($order_item_id)
    {
        $receiveData = array();
        
        if($order_item_id){
            $sql = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(array('t1' => $this->_name), array('code', 'qty'))
                        ->joinLeft(array('t2' => $this->_dbprefix.'erp_stock_receive_items'), "t1.receive_item_id = t2.id", array())
                        ->joinLeft(array('t3' => $this->_dbprefix.'erp_stock_receive'), "t2.receive_id = t3.id", array('number', 'create_time'))
                        ->where("t1.order_item_id = ".$order_item_id);
            
            $data = $this->fetchAll($sql)->toArray();
            
            foreach ($data as $d){
                array_push($receiveData, array(
                    'number'=> $d['number'],
                    'code'  => $d['code'],
                    'qty'   => $d['qty'],
                    'time'  => $d['create_time']
                ));
            }
        }
        
        return $receiveData;
    }
    
    public function getReqRelatedUsers($numberArr)
    {
        $userInfo = array();
        $userIdsAdded = array();
         
        $review = new Dcc_Model_Review();
        $employee = new Hra_Model_Employee();
        $i = 0;
        foreach ($numberArr as $order){
            $i++;
            $sql = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(array('t1' => $this->_name), array())
                        ->joinLeft(array('t2' => $this->_dbprefix."erp_pur_order_items"), "t1.order_item_id = t2.id", array())
                        ->joinLeft(array('t3' => $this->_dbprefix."erp_pur_order"), "t1.order_number = t3.number", array('order_create_user' => 'create_user'))
                        ->joinLeft(array('t4' => $this->_dbprefix."erp_pur_req"), "t2.req_number = t4.number", array('id', 'create_user', 'apply_user'))
                        ->joinLeft(array('t5' => $this->_dbprefix.'user'), "t4.create_user = t5.id", array())
            	    	->joinLeft(array('t6' => $this->_dbprefix.'employee'), "t5.employee_id = t6.id", array('creater_email' => 'email'))
            	    	->joinLeft(array('t7' => $this->_dbprefix.'user'), "t4.apply_user = t7.id", array())
            	    	->joinLeft(array('t8' => $this->_dbprefix.'employee'), "t7.employee_id = t8.id", array('applier_email' => 'email'))
                        ->joinLeft(array('t9' => $this->_dbprefix.'user'), "t3.create_user = t9.id", array())
            	    	->joinLeft(array('t10' => $this->_dbprefix.'employee'), "t9.employee_id = t10.id", array('order_creater_email' => 'email'))
                        ->where("t1.order_number = '".$order."'");
            
            $data = $this->fetchRow($sql)->toArray();
            
            array_push($userInfo, array('user_id' => $data['order_create_user'], 'email' => $data['order_creater_email']));
            array_push($userIdsAdded, $data['order_create_user']);
            
            if($data['applier_email'] != '' && $data['apply_user'] && !in_array($data['apply_user'], $userIdsAdded)){
                array_push($userInfo, array('user_id' => $data['apply_user'], 'email' => $data['applier_email']));
            }
            
            // 添加采购申请人：直接主管
            if($data['create_user'] != ''){
                array_push($userInfo, array('user_id' => $data['create_user'], 'email' => $data['creater_email']));
                array_push($userIdsAdded, $data['create_user']);
                
                $managerInfo = $employee->getManagerByUserId($data['create_user']);
                if(isset($managerInfo['email']) && $managerInfo['email'] != ''&& !in_array($managerInfo['user_id'], $userIdsAdded)){
                    array_push($userInfo, array('user_id' => $managerInfo['user_id'], 'email' => $managerInfo['email']));
                }
                
                // 添加采购申请人：部门主管
                $deptManagerInfo = $employee->getDeptManagerByUserId($data['create_user']);
                if(isset($deptManagerInfo['email']) && $deptManagerInfo['email'] != ''&& !in_array($deptManagerInfo['user_id'], $userIdsAdded)){
                    array_push($userInfo, array('user_id' => $deptManagerInfo['user_id'], 'email' => $deptManagerInfo['email']));
                }
            }
            
            // 获取审核人
            if($data['id'] != ''){
                $reviewerInfo = $review->getReviewUserInfo('purchse_req_add', $data['id']);
                
                foreach ($reviewerInfo as $info){
                    if(!in_array($info['user_id'], $userIdsAdded)){
                        array_push($userInfo, $info);
                    }
                }
            }
        }
        
        $data = array();
        $ids = array();
        
        foreach ($userInfo as $user){
            if(!in_array($user['user_id'], $ids)){
                array_push($data, $user);
                array_push($ids, $user['user_id']);
            }
        }
        
        return $data;
    }
    
    public function getCanBeReturnQty($key = null)
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array('order_item_id', 'code', 'price', 'order_id', 'order_number', 'qty' => new Zend_Db_Expr("sum(t1.qty)")))
                    ->joinLeft(array('t2' => $this->_dbprefix.'product_materiel'), "t1.code = t2.code", array('name', 'description'))
                    ->joinLeft(array('t3' => $this->_dbprefix.'erp_pur_order'), "t1.order_number = t3.number", array('buyer_id'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'bpartner'), "t3.supplier_id = t4.id", array('supplier_code' => 'code', 'supplier_name' => new Zend_Db_Expr("case when t4.cname != '' then t4.cname else t4.ename end")))
                    ->joinLeft(array('t5' => $this->_dbprefix.'erp_stock_receive_items'), "t1.receive_item_id = t5.id", array('unit', 'warehouse_code'))
                    ->joinLeft(array('t6' => $this->_dbprefix.'erp_stock_receive'), "t5.receive_id = t6.id", array('receive_number' => 'number'))
                    ->where("t1.active = 1 and t1.locked = 0")
                    ->group(array("t1.order_id", "t1.code"));
        
        if($key){
            $sql->where("t1.code like '%".$key."%' or t1.order_number like '%".$key."%' or t2.name like '%".$key."%' or t2.description like '%".$key."%'");
        }
        
        $data = $this->fetchAll($sql)->toArray();
        
        $buyer = new Erp_Model_Purchse_Buyer();
        /* echo '<pre>';
        print_r($data);
        exit; */
        for($i = 0; $i < count($data); $i++){
        	if($data[$i]['buyer_id']){
        		$buyerData = $buyer->getData($data[$i]['buyer_id']);
        		$data[$i]['order_buyer'] = $buyerData['cname'];
        	}
        }
        
        return $data;
    }
    
    // 取消收货处理
    public function cancelByItemId($item_id)
    {
        if($item_id){
            $this->update(array('active' => 0), "order_item_id = ".$item_id);
        }
    }
    
    public function getData($req_id)
    {
        $data = array();
        
        return $data;
    }
    
    public function getQty($type, $item_id)
    {
        $qty = 0;
        
        $sql = $this->select()
                    ->from($this->_name, array('qty' => new Zend_Db_Expr("sum(qty)")))
                    ->where("active = 1");
        
        if($type == 'receive'){
            // 获取收货数量
            $sql->where("receive_item_id = ".$item_id);
            
        }else if($type == 'order'){
            // 获取采购订单已收货数量
            $sql->where("order_item_id = ".$item_id);
        }
        
        $data = $this->fetchRow($sql)->toArray();
        
        if($data['qty'] > 0){
            $qty = $data['qty'];
        }
        
        return $qty;
    }
}