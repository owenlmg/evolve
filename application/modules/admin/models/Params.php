<?php
/**
 * 2013-9-15
 * @author mg.luo
 * @abstract 
 */
class Admin_Model_Params extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'params';
    protected $_primary = 'id';
    
    public function getValue($code){
        $sql = $this->select()
                    ->from(array($this->_name), array('value'))
                    ->where("code = '$code'");
        $data = $this->fetchAll($sql)->toArray();
        if(count($data) > 0) {
            return $data[0]['value'];
        }
        
        return '';
    }
}