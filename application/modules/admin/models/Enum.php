<?php
/**
 * 2013-7-20 14:30:10
 * @author mg.luo
 * @abstract 
 */
class Admin_Model_Enum extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'admin_enum';
    protected $_primary = 'id';

    public function getList($enumlist)
    {
        $data = array();

        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->join(array('t2' => $this->_dbprefix.'admin_enumlist'), "t1.list_id = t2.id", array('enumlist_id' => 't2.id', 'enumlist_name' => 't2.name'))
                    ->where("t1.state = 1 and t1.list_id = ?", $enumlist)
                    ->order(array('option_sort'));

        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }
    public function getListByListId($enumlist)
    {
        $data = array();

        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from($this->_name, array("option_key" => "id", "option_value"))
                    ->where("state = 1 and list_id = ?", $enumlist)
                    ->order(array('option_sort'));

        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }
    
    public function getAll($enumlist)
    {
        $data = array();

        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->join(array('t2' => $this->_dbprefix.'admin_enumlist'), "t1.list_id = t2.id", array('enumlist_id' => 't2.id', 'enumlist_name' => 't2.name'))
                    ->where("t1.list_id = ?", $enumlist)
                    ->order(array('option_sort'));

        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }
}