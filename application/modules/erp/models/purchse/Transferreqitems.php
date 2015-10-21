<?php
/**
 * 2014-7-11 下午10:47:35
 * @author x.li
 * @abstract 
 */
class Erp_Model_Purchse_Transferreqitems extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'erp_pur_req_items_transfer';
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
            'items_transfer_type'       => $d['transfer_type'],
            'items_req_id'              => $d['req_id'],
            'items_req_item_id'         => $d['req_item_id'],
            'items_active'              => $active,
            'items_code'                => $d['code'],
            'items_name'                => $d['name'],
            'items_description'         => $d['description'],
            'items_qty'                 => $d['qty'],
            //'items_qty_order'         => $qty_order,
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
}