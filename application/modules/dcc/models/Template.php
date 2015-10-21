<?php
/**
 * 2013-7-15 下午14:37:30
 * @author mg.luo
 * @abstract 
 */
class Dcc_Model_Template extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'doc_template';
    protected $_primary = 'id';
    
    public function getList(){
        $sql = $this->select()
                    ->from($this, array('id' => 'id', 'name' => 'name'))
                    ->where("active=1")
                    ->order(array('name'));
        $data = $this->fetchAll($sql)->toArray();
        
        return $data;
    }
}