<?php
/**
 * 2014-4-5
 * @author mg.luo
 * @abstract
 */
class Product_Model_Currency extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'Currency';
    protected $_primary = 'code';

    public function getList($where){
        $sql = $this->select()
                    ->from($this)
                    ->where($where);
        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }
}