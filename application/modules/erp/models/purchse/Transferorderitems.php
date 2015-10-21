<?php
/**
 * 2014-7-11 下午10:47:35
 * @author x.li
 * @abstract 
 */
class Erp_Model_Purchse_Transferorderitems extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'erp_pur_order_items_transfer';
    protected $_primary = 'id';
    
    public function getData($transfer_id)
    {
        $result = array();
    
        $data = $this->fetchAll("transfer_id = ".$transfer_id)->toArray();
        //$item = new Erp_Model_Purchse_Orderitemsreq();
        
        foreach ($data as $d){
            $active = $d['active'] == 1 ? true : false;
    
            //$qty_order = $item->getQty('req', $d['id']);
            
            array_push($result, array(
                'items_id'                  => $d['id'],
                'items_order_item_id'       => $d['order_item_id'],
                'items_transfer_type'       => $d['transfer_type'],
                'items_order_id'            => $d['order_id'],
                'items_req_number'          => $d['req_number'],
                'items_active'              => $active,
                'items_code'                => $d['code'],
                'items_name'                => $d['name'],
                'items_description'         => $d['description'],
                'items_supplier_code'       => $d['supplier_code'],
                'items_supplier_codename'   => $d['supplier_codename'],
                'items_supplier_description'=> $d['supplier_description'],
                'items_warehouse_code'      => $d['warehouse_code'],
                'items_qty'                 => $d['qty'],
                //'items_qty_receive'          => $qty_receive,
                'items_unit'                => $d['unit'],
                'items_price'               => $d['price'],
                'items_total'               => $d['total'],
                'items_request_date'        => $d['request_date'],
                'items_dept_id'             => $d['dept_id'],
                'items_project_info'        => $d['project_info'],
                'items_req_item_id'            => $d['req_item_id'],
                'items_req_qty'                => $d['req_qty'],
                'items_remark'              => $d['remark']
            ));
        }
    
        return $result;
    }
}