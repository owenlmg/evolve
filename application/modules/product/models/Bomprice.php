<?php
/**
 * 2014-9-8
 * @author mg.luo
 * @abstract
 */
class Product_Model_BomPrice extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'product_bom_price';
    protected $_primary = 'id';
    // id recordkey code ver low_cny low_usd high_cny high_usd average_cny average_usd mid

    public function getList($where, $start, $limit){
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t0' => $this->_name))
                    ->join(array('t1' => $this->_dbprefix.'product_fa'), "t1.recordkey = t0.recordkey")
                    ->join(array('t3' => $this->_dbprefix.'product_materiel'), "t1.id = t3.id", array("name", "description", "materiel_type" => "type"))
                    ->joinLeft(array('t5' => $this->_dbprefix.'product_catalog'), "t1.project_no = t5.id", array('project_no_name' => 'model_internal'))
                    ->where($where)
                    ->order(array('state', 'sid desc', 'code desc', 'ver desc'));
        if(isset($limit) && $limit) {
        	$sql = $sql->limit($limit, $start);
        }

        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }
    public function getListCount($where){
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t0' => $this->_name))
                    ->join(array('t1' => $this->_dbprefix.'product_fa'), "t1.recordkey = t0.recordkey")
                    ->join(array('t3' => $this->_dbprefix.'product_materiel'), "t1.id = t3.id", array("name", "description", "materiel_type" => "type"))
                    ->joinLeft(array('t5' => $this->_dbprefix.'product_catalog'), "t1.project_no = t5.id", array('project_no_name' => 'model_internal'))
                    ->where($where);

        return $this->fetchAll($sql)->count();;
    }

    /**
     *  初始化所有价格
     */
    public function initPrice() {
        $fa = new Product_Model_Fa();
        $data = $fa->getJoinList("state != 'Obsolete'");
        foreach($data as $bom) {
            $this->setBomPrice($bom['recordkey'], false);
        }
    }

    /**
     * 计算BOM价格
     * @param unknown $recordkey
     * @param string $currency
     * @param string $type
     * @return multitype:NULL
     */
    public function getBomPrice($recordkey, $currency = 'CNY', $type = 'all') {
        $low = $high = $average = 0.0;
        $fa = new Product_Model_Fa();
        $son = new Product_Model_Son();
        $price_list = new Erp_Model_Warehouse_Pricelist();

        // 计算价格
        $sonData = $son->getJoinList("recordkey = ".$recordkey, array(), array('recordkey', 'code', 'qty'));
        foreach($sonData as $sonRow) {
            if(0 && isset($_SESSION['bomprice'.$currency][$sonRow['recordkey']])) {
            	// session 中存在
            	$price = $_SESSION['bomprice'.$currency][$sonRow['recordkey']];
            } else {
                // 检查是否有BOM数据，如果没有，当作物料处理
                $sonFaData = $fa->getJoinList("code = '".$sonRow['code']."'", array(), array('recordkey'), array('ver desc'));
                if(count($sonFaData) == 0) {
                    if(isset($_SESSION['mprice'][$sonRow['code']])) {
                        // session 中存在
                        $price = $_SESSION['mprice'][$sonRow['code']];
                    } else {
                        $price = $price_list->getMultiPrice($sonRow['code'], $currency);
                        $_SESSION['mprice'][$sonRow['code']] = $price;
                    }

                    // 是否有物料无价格
                    if($price['average'] == 0) {
                        $_SESSION['noPrice'][] = $recordkey;
                    }
                } else {
                    $price = $this->getBomPrice($sonFaData[0]['recordkey'], $currency);
                    $_SESSION['bomprice'.$currency][$sonFaData[0]['recordkey']] = $price;
                    if(in_array($sonFaData[0]['recordkey'], $_SESSION['noPrice'])) {
                        $_SESSION['noPrice'][] = $recordkey;
                    }
                }
            }
            $low += $price['low'] * $sonRow['qty'];
            $high += $price['high'] * $sonRow['qty'];
            $average += $price['average'] * $sonRow['qty'];
            
        }
        return array(
            'low' => round($low, 4),
            'high' => round($high, 4),
            'average' => round($average, 4)
        );
    }

    public function calcBomPrice($recordkey, $currency = 'CNY') {
        $low = $high = $average = 0.0;
        $fa = new Product_Model_Fa();
        $son = new Product_Model_Son();
        $price_list = new Erp_Model_Warehouse_Pricelist();

        $data = $this->getJoinList('recordkey = '.$recordkey);
        // 检查当前BOM是否已有价格
        if(count($data) == 0) {
            // 计算价格
            $sonData = $son->getJoinList("recordkey = ".$recordkey, array(), array('code', 'qty'));
            foreach($sonData as $sonRow) {
                // 检查是否有BOM数据，如果没有，当作物料处理
                $sonFaData = $fa->getJoinList("code = '".$sonRow['code']."'", array(), array('recordkey'), array('ver desc'));
                if(count($sonFaData) == 0) {
                    if(isset($_SESSION['mprice'][$sonRow['code']])) {
                        // session 中存在
                        $price = $_SESSION['mprice'][$sonRow['code']];
                    } else {
                        $price = $price_list->getMultiPrice($sonRow['code'], $currency);
                        $_SESSION['mprice'][$sonRow['code']] = $price;
                    }
                } else {
                    $price = $this->calcBomPrice($sonFaData[0]['recordkey'], $currency);
                }
                $low += $price['low'] * $sonRow['qty'];
                $high += $price['high'] * $sonRow['qty'];
                $average += $price['average'] * $sonRow['qty'];
            }
        } else {
            $bomPrice = $data[0];
            $low = $bomPrice['low_'.strtolower($currency)];
            $high = $bomPrice['high_'.strtolower($currency)];
            $average = $bomPrice['average_'.strtolower($currency)];
        }
        return array(
            'low' => round($low, 4),
            'high' => round($high, 4),
            'average' => round($average, 4)
        );
    }
}