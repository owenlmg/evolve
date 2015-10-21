<?php
/**
 * 2013-7-6 下午10:32:26
 * @author x.li
 * @abstract 
 */
class Erp_Model_Warehouse_Pricelist extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'erp_pricelist';
    protected $_primary = 'id';
    
    /**
     * 获取最低价格
     * @param string $code
     * @return number
     */
    public function getMultiPrice($code, $currency = null)
    {
        $price = array(
                'low'       => 0,
                'high'      => 0,
                'average'   => 0
        );
        
        $res = $this->fetchAll("code = '".$code."'");
        
        if($res->count() > 0){
            $priceArr = array();
            
            $data = $res->toArray();
            
            foreach ($data as $d){
                $b = $this->getBestPrice($code, $d['supplier_id'], $currency);
                
                if(($price['low'] == 0 && $b['price'] > 0) || $b['price'] < $price['low']){
                    $price['low'] = round($b['price'], 6);
                }
                
                $w = $this->getWorstPrice($code, $d['supplier_id'], $currency);
                
                if($w['price'] > $price['high']){
                    $price['high'] = round($w['price'], 6);
                }
            }
        }
        
        $price['average'] = round(($price['low'] + $price['high']) / 2, 6);
        
        return $price;
    }
    
    // 获取最优价格（当前最新且数量阶梯最便宜的价格，一般为最便宜价格）
    public function getBestPrice($code, $supplier_id, $currency = null)
    {
        return $this->getPrice($code, $supplier_id, false, date('Y-m-d'), 1000000, $currency);
    }
    
    // 获取最高价格
    public function getWorstPrice($code, $supplier_id, $currency = null)
    {
        return $this->getPrice($code, $supplier_id, false, date('Y-m-d'), 1, $currency);
    }
    
    // 获取价格
    public function getPrice($code, $supplier_id, $fix = true, $date = null, $qty = null, $currency = null)
    {
        $price = array('price' => 0, 'currency' => '');
        
        $res = $this->fetchAll("code = '".$code."' and supplier_id = ".$supplier_id);
        
        if($res->count() > 0){
            $data = $res->toArray();
            
            if($date && !$fix){
                $ladder = new Erp_Model_Warehouse_Ladder();
                
                $price_ladder = $ladder->getPrice($data[0]['id'], $date, $qty);
                
                if($price_ladder['price'] > 0){
                    $price = $price_ladder;
                }else{
                    $price['price'] = $data[0]['price'];
                    $price['currency'] = $data[0]['currency'];
                }
            }else{
                $price['price'] = $data[0]['price'];
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

    // 获取价格清单
    public function getData($condition = null)
    {
        if($condition['partner_type'] == 0){
            $sql = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(array('t1' => $this->_name))
                        ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t2.id = t1.create_user", array())
                        ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t3.id = t2.employee_id", array('creater' => 'cname'))
                        ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t4.id = t1.update_user", array())
                        ->joinLeft(array('t5' => $this->_dbprefix.'employee'), "t5.id = t4.employee_id", array('updater' => 'cname'))
                        ->joinLeft(array('t6' => $this->_dbprefix.'bpartner'), "t1.supplier_id = t6.id", array('supplier_code' => 'code', 'supplier_name' => new Zend_Db_Expr("case when t6.cname != '' then t6.cname else t6.ename end")))
                        ->joinLeft(array('t8' => $this->_dbprefix.'product_materiel'), "t1.code = t8.code", array('t8.name', 't8.description'))
                        ->where("t1.type = ".$condition['partner_type'])
                        ->order("t1.code");
        }else{
            $sql = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(array('t1' => $this->_name))
                        ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t2.id = t1.create_user", array())
                        ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t3.id = t2.employee_id", array('creater' => 'cname'))
                        ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t4.id = t1.update_user", array())
                        ->joinLeft(array('t5' => $this->_dbprefix.'employee'), "t5.id = t4.employee_id", array('updater' => 'cname'))
                        ->joinLeft(array('t6' => $this->_dbprefix.'bpartner'), "t1.supplier_id = t6.id", array('supplier_code' => 'code', 'supplier_name' => new Zend_Db_Expr("case when t6.cname != '' then t6.cname else t6.ename end")))
                        ->joinLeft(array('t8' => $this->_dbprefix.'product_catalog'), "t1.product_code = t8.model_internal", array('product_description' => 't8.description'))
                        ->where("t1.type = ".$condition['partner_type'])
                        ->order("t1.code");
        }
        
        if($condition['supplier_id']){
            $sql->where("t1.supplier_id = ".$condition['supplier_id']);
        }
        
        if($condition['key']){
            $sql->where("t1.code like '%".$condition['key']."%' or t8.description like '%".$condition['key']."%' or t1.remark like '%".$condition['key']."%'");
        }
        
        $total = $this->fetchAll($sql)->count();
        
        if($condition['type'] != 'csv'){
            $sql->limitPage($condition['page'], $condition['limit']);
        }
        
        $data = $this->fetchAll($sql)->toArray();
        
        $ladder = new Erp_Model_Warehouse_Ladder();
        
        for($i = 0; $i < count($data); $i++){
            $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
            $data[$i]['update_time'] = strtotime($data[$i]['update_time']);
            
            $price = $this->getBestPrice($data[$i]['code'], $data[$i]['supplier_id'], $data[$i]['currency']);
            
            $data[$i]['price_best'] = $price['price'];
            
            if($ladder->fetchAll("pricelist_id = ".$data[$i]['id'])->count() > 0){
                $data[$i]['date_range'] = 1;
            }else{
                $data[$i]['date_range'] = 0;
            }
        }
        
        if($condition['type'] == 'csv'){
            $data_csv = array();
            
            $title = array(
                    'cnt'           => '#',
                    'supplier_name' => '业务伙伴',
                    'code'          => '物料号',
                    'name'          => '名称',
                    'description'   => '描述',
                    'price'         => '固定价格',
                    'price_best'    => '最低价格',
                    'currency'      => '币种',
                    'remark'        => '备注'
            );
            
            if($condition['partner_type'] == 1){
                $title = array(
                        'cnt'           => '#',
                        'supplier_name' => '业务伙伴',
                        'code'          => '产品型号',
                        'description'   => '描述',
                        'price'         => '固定价格',
                        'price_best'    => '最低价格',
                        'currency'      => '币种',
                        'remark'        => '备注'
                );
            }
        
            array_push($data_csv, $title);
        
            $i = 0;
        
            foreach ($data as $d){
                $i++;
                
                if($condition['partner_type'] == 1){
                    $info = array(
                            'cnt'           => $i,
                            'supplier_name' => $d['supplier_name'],
                            'code'          => $d['product_code'],
                            'description'   => $d['product_description'],
                            'price'         => $d['price'],
                            'price_best'    => $d['price_best'],
                            'currency'      => $d['currency'],
                            'remark'        => $d['remark']
                    );
                }else{
                    $info = array(
                            'cnt'           => $i,
                            'supplier_name' => $d['supplier_name'],
                            'code'          => $d['code'],
                            'name'          => $d['name'],
                            'description'   => $d['description'],
                            'price'         => $d['price'],
                            'price_best'    => $d['price_best'],
                            'currency'      => $d['currency'],
                            'remark'        => $d['remark']
                    );
                }
                
                array_push($data_csv, $info);
            }
        
            return $data_csv;
        }
        
        return array('total' => $total, 'rows' => $data);
    }
}