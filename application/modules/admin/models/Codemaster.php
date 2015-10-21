<?php
/**
 * 2013-9-15
 * @author mg.luo
 * @abstract 
 */
class Admin_Model_Codemaster extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'codemaster';
    protected $_primary = 'id';
    
    public function getList($where){
        $sql = $this->select()
                    ->from(array($this->_name), array("id" => "code", "text"))
                    ->where("active = 1")
                    ->where($where);
        $data = $this->fetchAll($sql)->toArray();
        
        return $data;
    }
}