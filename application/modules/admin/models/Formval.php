<?php
/**
 * 2013-7-27 下午4:56:30
 * @author mg.luo
 * @abstract 
 */
class Admin_Model_Formval extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'admin_formval';
    protected $_primary = 'id';

    /**
     * 根据menu获取值
     */
    public function getListByMenu($menu)
    {
        $data = array();

        $sql = $this->select()
                    ->from($this->_name)
                    ->where("menu = ?", $menu);

        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }
    
    public function getFormByMenu($menu)
    {
        $data = array();

        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array("id", "name", "type", "length", "nullable", "enumlist", "default_value"))
                    ->where("t1.state = 1 and t1.menu = ?", $menu)
                    ->order(array('t1.id'));

        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }
}