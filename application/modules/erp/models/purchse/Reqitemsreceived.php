<?php
/**
 * 2014-11-19 下午11:28:29
 * @author x.li
 * @abstract 
 */
class Erp_Model_Purchse_Reqitemsreceived extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'erp_pur_req_items_received';
    protected $_primary = 'id';
    
    public function getReceivedInfo($type, $item_id)
    {
        $info = array(
                'qty'   => 0,
                'info'  => array()
        );
        
        if ($type == 'order') {
            $sql = $this->select()
                        ->from($this, array(
                                'qty',
                                'info' => new Zend_Db_Expr("concat(order_number, ' [', qty, '] [', order_time, ']')")
                        ))
                        ->where("order_item_id = ".$item_id);
        }else{
            $sql = $this->select()
                        ->from($this, array(
                                'qty',
                                'info' => new Zend_Db_Expr("concat(req_number, ' [', qty, '] [', req_time, ']')")
                        ))
                        ->where("req_item_id = ".$item_id);
        }
        
        $data = $this->fetchAll($sql)->toArray();
        
        foreach ($data as $d){
            $info['qty'] += $d['qty'];
            array_push($info['info'], $d['info']);
        }
        
        return $info;
    }
}