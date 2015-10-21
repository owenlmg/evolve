<?php
/**
 * 2013-7-23 15:30:10
 * @author mg.luo
 * @abstract 
 */
class Admin_Model_Enumlist extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'admin_enumlist';
    protected $_primary = 'id';

    public function getList($where)
    {
        $data = array();

        $sql = $this->select()
                    ->from($this)
                    ->where($where)
                    ->order(array('name'));

        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }

    public function getListForSel($key)
    {
        $data = array();

        $sql = $this->select()
                    ->from($this)
                    ->where("name like ?", "%".$key."%")
                    ->where("state = 1");

        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }
}