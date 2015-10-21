<?php
/**
 * 2013-7-21 下午1:01:30
 * @author mg.luo
 * @abstract 
 */
class Admin_Model_Column extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'admin_column';
    protected $_primary = 'id';

    public function getList()
    {
        $data = array();

        $sql = $this->select()
                    ->from($this, array('id', 'name'))
                    ->where("state = 1")
                    ->order(array('name'));

        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }
}