<?php
/**
 * 2014-12-06 22:32:26
 * @author x.li
 * @abstract 
 */
class Erp_Model_Sale_Priceitemladder extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'erp_sale_price_items_ladder';
    protected $_primary = 'id';
    
    // 获取价格
    public function getPrice($item_id, $qty = null)
    {
        $price = 0;
        
        $res = $this->fetchAll("item_id = ".$item_id." and qty <= '".$qty."'", array("qty desc"));
        
        if($res->count() > 0){
            $data = $res->toArray();
            $price = $data[0]['price_final'];
        }
        
        return $price;
    }
    
    public function getLadder($item_id)
    {
        $ladder = array();
        
        $sql = $this->select()
                    ->from($this)
                    ->where("item_id = ".$item_id);
        
        $ladder = $this->fetchAll($sql)->toArray();
        
        return $ladder;
    }
}