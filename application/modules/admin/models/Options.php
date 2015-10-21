<?php
/**
 * 2013-7-21 下午1:01:30
 * @author mg.luo
 * @abstract 
 */
class Admin_Model_Options extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'public_options';
    protected $_primary = 'id';

    public function getList($type = null)
    {
        $data = array();

        $sql = $this->select()
                    ->from($this, array('id', 'name'))
                    ->where("active = 1")
                    ->order(array('order', 'id'));
        
        if($type){
            $sql->where("type = '".$type."'");
        }

        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }
}