<?php
/**
 * 2014-4-5
 * @author mg.luo
 * @abstract
 */
class Product_Model_Rate extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'rate';
    protected $_primary = 'id';

    public function getList($where){
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'employee'), "t1.update_user = t2.id", array('updater' => 'cname'))
                    ->joinLeft(array('t3' => $this->_dbprefix.'currency'), "t1.currency = t3.code", array('currency_name' => 'name', 'symbol'))
                    ->where($where)
                    ->order(array('t1.currency'));
        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }
}