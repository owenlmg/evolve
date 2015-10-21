<?php
/**
 * 2013-7-6 下午10:32:26
 * @author x.li
 * @abstract 
 */
class Erp_Model_Warehouse_Ladder extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'erp_pricelist_ladder';
    protected $_primary = 'id';
    
    // 获取价格
    public function getPrice($pricelist_id, $date, $qty = null)
    {
        $price = array('price' => 0, 'currency' => 'CNY');
        
        $res = $this->fetchAll("pricelist_id = ".$pricelist_id." and date <= '".$date."'", array("date desc"));
        
        if($res->count() > 0){
            $data = $res->toArray();
            
            if($qty){
                $ladder_qty = new Erp_Model_Warehouse_Ladderqty();
        
                $price_ladder_qty = $ladder_qty->getPrice($data[0]['id'], $qty);
                
                if($price_ladder_qty['price'] > 0){
                    $price = $price_ladder_qty;
                }else{
                    $price['price'] = $data[0]['price'];
                    $price['currency'] = $data[0]['currency'];
                }
            }else{
                $price['price'] = $data[0]['price'];
                $price['currency'] = $data[0]['currency'];
            }
        }
        
        return $price;
    }

    // 获取价格清单
    public function getData($pricelist_id)
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t2.id = t1.create_user", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t3.id = t2.employee_id", array('creater' => 'cname'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t4.id = t1.update_user", array())
                    ->joinLeft(array('t5' => $this->_dbprefix.'employee'), "t5.id = t4.employee_id", array('updater' => 'cname'))
                    ->where("t1.pricelist_id = ".$pricelist_id)
                    ->order("t1.date desc");
        
        $data = $this->fetchAll($sql)->toArray();
        
        $ladderQty = new Erp_Model_Warehouse_Ladderqty();
        
        for($i = 0; $i < count($data); $i++){
            $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
            $data[$i]['update_time'] = strtotime($data[$i]['update_time']);
            
            if($ladderQty->fetchAll("ladder_id = ".$data[$i]['id'])->count() > 0){
                $data[$i]['qty_range'] = 1;
            }else{
                $data[$i]['qty_range'] = 0;
            }
        }
        
        return $data;
    }
}