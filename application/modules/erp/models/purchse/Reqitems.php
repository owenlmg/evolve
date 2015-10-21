<?php
/**
 * 2013-9-11 下午10:47:35
 * @author x.li
 * @abstract 
 */
class Erp_Model_Purchse_Reqitems extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'erp_pur_req_items';
    protected $_primary = 'id';
    
    // 根据申请ID取消订单项
    public function cancelByReqId($req_id)
    {
        if($req_id){
            $this->update(array('active' => 0), "req_id = ".$req_id);
        }
    }
    
    public function getOpenReqByCode($code)
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array())
                    ->joinLeft(array('t2' => $this->_dbprefix.'erp_pur_req'), "t1.req_id = t2.id", array('number'))
                    ->where("t1.code = '".$code."' and t2.state != 2")
                    ->order("t2.number");
        $data = $this->fetchAll($sql)->toArray();
        
        return $data;
    }
    
    public function getItemsState()
    {
        $data = array();
        
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array('item_code' => 'code', 'item_name' => 'name', 'item_description' => 'description', 'item_qty' => 'qty', 'item_unit' => 'unit', 'item_date_req' => 'date_req', 'item_supplier' => 'supplier', 'item_dept_id' => 'dept_id', 'item_remark' => 'remark', 'item_active' => 'active', 'item_project_info' => 'project_info', 'item_model' => 'model', 'item_id' => 'id'))
                    ->joinLeft(array('t2' => $this->_dbprefix.'erp_pur_req'), "t2.id = t1.req_id", array('req_number' => 'number', 'req_dept_id' => 'dept_id', 'req_review_info' => 'review_info', 'req_state' => 'state', 'req_remark' => 'remark', 'req_reason' => 'reason', 'req_active' => 'active', 'req_id' => 'id'))
                    ->joinLeft(array('t3' => $this->_dbprefix.'user'), "t2.create_user = t3.id", array())
                    ->joinLeft(array('t4' => $this->_dbprefix.'employee'), "t3.employee_id = t4.id", array('creater' => 'cname'))
                    ->joinLeft(array('t5' => $this->_dbprefix.'erp_pur_type'), "t5.id = t2.type_id", array('req_type_name' => 'name'))
                    ->joinLeft(array('t6' => $this->_dbprefix.'erp_pur_order_items_req'), "t6.req_item_id = t1.id", array('req_order_qty' => 'qty'))
                    ->joinLeft(array('t7' => $this->_dbprefix.'erp_pur_order_items'), "t7.id = t6.order_item_id", array('order_item_request_date' => 'request_date', 'order_item_active' => 'active'))
                    ->joinLeft(array('t8' => $this->_dbprefix.'erp_pur_order'), "t8.id = t7.order_id", array('order_active' => 'active', 'order_state' => 'state', 'order_number' => 'number', 'order_request_date' => 'request_date', 'order_review_info' => 'review_info', 'order_description' => 'description', 'order_remark' => 'remark', 'order_id' => 'id'))
                    /* ->where("") */;//'req_state' => 'state', 
        /* echo $sql;
        exit; */
        $data = $this->fetchAll($sql)->toArray();
        
        return $data;
    }
    
    // 获取申请项剩余下单数量
    public function getItemQtyLeft($item_id)
    {
        $qty = 0;
        
        $data = $this->fetchRow("id = ".$item_id)->toArray();
        
        if($data['active']){
            $itemsreq = new Erp_Model_Purchse_Orderitemsreq();
            
            $res = $itemsreq->fetchAll("req_item_id = ".$item_id);
            
            if($res->count() > 0){
                $data = $res->toArray();
                
                foreach ($data as $d){
                    if($d['active']){
                        $qty += $d['qty'];
                    }
                }
            }
        }
        
        return $qty;
    }
    
    public function getData($req_id)
    {
        $result = array();
        
        $data = $this->fetchAll("req_id = ".$req_id)->toArray();
        $item = new Erp_Model_Purchse_Orderitemsreq();
        
        foreach ($data as $d){
            $active = $d['active'] == 1 ? true : false;
            
            //$qty_order = $item->getQty('req', $d['id']);
            $qty_order = $item->getQty('req', $d['id'], false);//2014-12-28
        
            array_push($result, array(
                'items_id'                  => $d['id'],
                'items_req_id'              => $d['req_id'],
                'items_active'              => $active,
                'items_code'                => $d['code'],
                'items_name'                => $d['name'],
                'items_description'         => $d['description'],
                'items_qty'                 => $d['qty'],
                'items_qty_order'             => $qty_order,
                'items_unit'                => $d['unit'],
                'items_price'               => $d['price'],
                'items_line_total'          => $d['line_total'],
                'items_date_req'            => $d['date_req'],
                'items_supplier'            => $d['supplier'],
                'items_dept_id'             => $d['dept_id'],
                'items_model'               => $d['model'],
                'items_project_info'        => $d['project_info'],
                'items_order_req_num'       => $d['order_req_num'],
                'items_customer_address'    => $d['customer_address'],
                'items_customer_aggrement'  => $d['customer_aggrement'],
                'items_remark'              => $d['remark']
            ));
        }
        
        return $result;
    }
    
    public function refreshReqTotal($req_id)
    {
        $total = 0;
        
        if($req_id){
            $data = $this->fetchAll("req_id = ".$req_id);
            
            if($data->count() > 0){
                $items = $data->toArray();
                
                foreach ($items as $item){
                    $total += $item['line_total'];
                }
            }
        }
        
        $req = new Erp_Model_Purchse_Req();
        
        $req->update(array('total' => $total), "id = ".$req_id);
    }
}