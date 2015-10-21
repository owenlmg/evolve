<?php
/**
 * 2013-7-6 下午10:32:26
 * @author x.li
 * @abstract 
 */
class Erp_Model_Warehouse_Ladderqty extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'erp_pricelist_ladder_qty';
    protected $_primary = 'id';
    
    // 获取价格
    public function getPrice($ladder_id, $qty)
    {
        $price = array('price' => 0, 'currency' => 'CNY');
        
        // 获取固定价格
        $res = $this->fetchAll("ladder_id = ".$ladder_id." and qty <= '".$qty."'", "qty desc");
        
        if($res->count() > 0){
            $data = $res->toArray();
            
            $price['price'] = $data[0]['price'];
            $price['currency'] = $data[0]['currency'];
        }
        
        return $price;
    }

    // 获取价格清单
    public function getData($ladder_id)
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t2.id = t1.create_user", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t3.id = t2.employee_id", array('creater' => 'cname'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t4.id = t1.update_user", array())
                    ->joinLeft(array('t5' => $this->_dbprefix.'employee'), "t5.id = t4.employee_id", array('updater' => 'cname'))
                    ->where("t1.ladder_id = ".$ladder_id)
                    ->order("t1.qty desc");
        
        $data = $this->fetchAll($sql)->toArray();
        
        for($i = 0; $i < count($data); $i++){
            $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
            $data[$i]['update_time'] = strtotime($data[$i]['update_time']);
        }
        
        return $data;
    }
}