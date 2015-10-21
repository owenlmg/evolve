<?php
/**
 * 2014-4-5
 * @author mg.luo
 * @abstract
 */
class Product_Model_Price extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'product_materiel_price';
    protected $_primary = 'id';

    public function getList($where, $time, $start, $limit){
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->join(array('t2' => $this->_dbprefix.'product_materiel'), "t2.code = t1.code", array("name", "description", "type"))
                    ->joinLeft(array('t3' => $this->_dbprefix.'bpartner'), "t3.code = t1.supply_code and t3.active=1", array('supply_name' => 'cname', 'bank_currency'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'employee'), "t4.id = t1.update_user", array('updater' => 'cname'))
                    ->joinLeft(array('t5' => $this->_dbprefix.'rate'), "t5.currency = t1.currency and t5.start_time >= '$time' and t5.end_time <= '$time'", array('rate'))
                    ->joinLeft(array('t6' => $this->_dbprefix.'currency'), "t3.bank_currency = t6.code", array('symbol', 'currency_name' => 'name'))
                    ->where($where)
                    ->order(array('t1.code', 't1.supply_code', 't1.min_num'));
                    if(isset($limit)) {
                        $sql = $sql->limit($limit, $start);
                    }
        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }
    public function getCount($where, $start, $limit) {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->join(array('t2' => $this->_dbprefix.'product_materiel'), "t2.code = t1.code", array("name", "description", "type"))
                    ->joinLeft(array('t3' => $this->_dbprefix.'bpartner'), "t3.code = t1.supply_code and t3.active=1", array('supply_name' => 'cname', 'bank_currency'))
                    ->where($where);
        $data = $this->fetchAll($sql)->count();

        return $data;
    }
}