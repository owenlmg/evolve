<?php
/**
 * 2013-9-15
 * @author mg.luo
 * @abstract
 */
class Product_Model_Bpartner extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'bpartner';
    protected $_primary = 'id';

    public function getListForSel($where){
        $sql = $this->select()
                    ->from(array($this->_name), array("id", "code", "cname", "ename", "bank_currency"))
                    ->where($where)
                    ->where("type = 0 and active = 1")
                    ->order(array('code'));
        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }
}