<?php
/**
 * 2014-12-06 22:32:26
 * @author x.li
 * @abstract 
 */
class Erp_Model_Sale_Priceitems extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'erp_sale_price_items';
    protected $_primary = 'id';
    
    // 获取价格
    public function getPrice($code, $customer_id, $fix = true, $date = null, $qty = null, $currency = null)
    {
        $price = array(
                'price_tax' => 0, // 价格是否含税
                'price' => 0, //价格
                'currency' => 'CNY' // 币种
        );
        
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array(
                            'id',
                            'price_final',
                            'currency'
                    ))
                    ->joinLeft(array('t2' => $this->_dbprefix.'erp_sale_price'), "t1.price_id = t2.id", array(
                            'price_tax'
                    ))
                    ->where("t1.active = 1 and t1.code = '".$code."' and t1.active_date <= '".$date."' and t1.customer_id = ".$customer_id);
        
        $data = $this->fetchAll($sql);
        $taxRateModel = new Erp_Model_Setting_Taxrate();
        
        if(count($data)){
            $price['price_tax'] = $data[0]['price_tax'];
            
            if($date && !$fix){
                $ladder = new Erp_Model_Sale_Priceitemladder();
                
                $price_ladder = $ladder->getPrice($data[0]['id'], $qty);
                
                if($price_ladder > 0){
                    $price['price'] = $price_ladder;
                    $price['currency'] = $data[0]['currency'];
                }else{
                    $price['price'] = $data[0]['price_final'];
                    $price['currency'] = $data[0]['currency'];
                }
            }else{
                $price['price'] = $data[0]['price_final'];
                $price['currency'] = $data[0]['currency'];
            }
        }
        
        if($currency != $price['currency']){
            $rate = new Erp_Model_Setting_Currencyrate();
            
            $rate1 = $rate->getRateByCode($currency, $date);
            $rate2 = $rate->getRateByCode($price['currency'], $date);
            
            $price['price'] = round($price['price'] * ($rate2 / $rate1), 8);
        }
        /* echo '<pre>';
        print_r($price);
        exit; */
        return $price;
    }
    
    public function getCodeList($customer_id)
    {
        $data = array();
        
        $sql = $this->select()
                    ->from($this)
                    ->where('customer_id = '.$customer_id." and active = 1")
                    ->order("code");
        
        $data = $this->fetchAll($sql)->toArray();
        
        /* foreach ($items as $item){
            array_push($data, array(
                
            ));
        } */
        
        return $data;
    }
    
    public function getPriceList($customer_id, $code, $show_inactive)
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix."erp_sale_price"), "t2.id = t1.price_id", array(
                            'price_date',
                            'price_number'      => 'number',
                            'price_currency'    => 'currency',
                            'price_tax',
                            'price_tax_id'      => 'tax_id',
                            'price_description' => 'description',
                            'price_remark'      => 'remark',
                            'price_release_time'=> 'release_time'
                    ))
                    ->joinLeft(array('t3' => $this->_dbprefix.'user'), "t2.create_user = t3.id", array())
                    ->joinLeft(array('t4' => $this->_dbprefix.'employee'), "t3.employee_id = t4.id", array(
                            'price_creater' => 'cname'
                    ))
                    ->joinLeft(array('t5' => $this->_dbprefix."bpartner"), "t2.customer_id = t5.id", array(
                            'customer_name' => new Zend_Db_Expr("case when t5.cname = '' then t5.ename else t5.cname end"),
                            'customer'      => 'code'
                    ))
                    ->where("t2.deleted = 0 and t2.state = 2")
                    ->order(array("t1.code", "t1.active desc", "t1.active_date desc"));
        
        if (!$show_inactive) {
            $sql->where("t1.active = 1");
        }
        
        if ($customer_id) {
            $sql->where("t5.id = ".$customer_id);
        }
        
        if ($code) {
            $sql->where("t1.code like '%".$code."%'");
        }
        
        $data = $this->fetchAll($sql)->toArray();
        
        $ladderModel = new Erp_Model_Sale_Priceitemladder();
        
        for ($i = 0; $i < count($data); $i++){
            $data[$i]['price'] = $data[$i]['price_final'];
            $data[$i]['customer'] .= ' '.$data[$i]['customer_name'];
            $data[$i]['ladder'] = Zend_Json::encode($ladderModel->getLadder($data[$i]['id']));
            
            if ($data[$i]['type'] == 'catalog') {
                $data[$i]['name'] = $data[$i]['customer_name'];
                $data[$i]['description'] = $data[$i]['customer_description'];
            }
        }
        
        return $data;
    }
    
    public function inactivePrice($except_price_id, $customer_id, $item_type, $item_code)
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array('id'))
                    ->joinLeft(array('t2' => $this->_dbprefix.'erp_sale_price'), 't1.price_id = t2.id', array())
                    ->where("t2.id != ".$except_price_id." and t2.customer_id = ".$customer_id." and t1.type = '".$item_type."' and t1.code = '".$item_code."'");
        
        $data = $this->fetchAll($sql)->toArray();
        
        foreach ($data as $d){
            $this->update(array('active' => 0), "id = ".$d['id']);
        }
    }
    
    public function getItems($price_id)
    {
        $items = array();
        
        $data = $this->fetchAll("price_id = ".$price_id)->toArray();
        
        $ladderModel = new Erp_Model_Sale_Priceitemladder();
        
        foreach ($data as $d){
            $ladder = $ladderModel->getLadder($d['id']);
            
            $itemsLadder = count($ladder) > 0 ? Zend_Json::encode($ladder) : '';
            
            array_push($items, array(
                'items_id'                      => $d['id'],
                'items_price_id'                => $d['price_id'],
                'items_type'                    => $d['type'],
                'items_code'                    => $d['code'],
                'items_name'                    => $d['name'],
                'items_description'             => $d['description'],
                'items_customer_code'           => $d['customer_code'],
                'items_customer_description'    => $d['customer_description'],
                'items_unit'                    => $d['unit'],
                'items_price_start'             => $d['price_start'],
                'items_price_final'             => $d['price_final'],
                'items_remark'                  => $d['remark'],
                'items_product_type'            => $d['product_type'],
                'items_product_series'          => $d['product_series'],
                'items_active_date'             => $d['active_date'],
                'items_ladder'                  => $itemsLadder
            ));
        }
        
        return $items;
    }
}