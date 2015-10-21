<?php
/**
 * 2014-7-11 下午10:47:35
 * @author x.li
 * @abstract 
 */
class Erp_Model_Sale_Transferorderitems extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'erp_sale_order_items_transfer';
    protected $_primary = 'id';
    
    public function getData($transfer_id)
    {
        $result = array();
    
        $data = $this->fetchAll("transfer_id = ".$transfer_id)->toArray();
        
        foreach ($data as $d){
            $active = $d['active'] == 1 ? true : false;
            
            array_push($result, array(
                'items_id'                  => $d['id'],
                'items_type'                => $d['type'],
                'items_order_item_id'       => $d['order_item_id'],
                'items_transfer_type'       => $d['transfer_type'],
                'items_order_id'            => $d['order_id'],
                'items_active'              => $active,
                'items_code'                => $d['code'],
                'items_name'                => $d['name'],
                'items_description'         => $d['description'],
                'items_qty'                 => $d['qty'],
                'items_unit'                => $d['unit'],
                'items_price'               => $d['price'],
                'items_total'               => $d['total'],
                'items_request_date'        => $d['request_date'],
                'items_remark'              => $d['remark']
            ));
        }
    
        return $result;
    }
}