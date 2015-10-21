<?php
/**
 * 2013-7-15 下午14:37:30
 * @author mg.luo
 * @abstract 
 */
class Dcc_Model_Auto extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'doc_auto';
    protected $_primary = 'id';
    
    public function getAuto(){
        $sql = $this->select()
                    ->from($this, array('id' => 'id', 'auto_description' => 'description'))
                    ->order(array('id'));
        $data = $this->fetchAll($sql)->toArray();
        
        return $data;
    }
}