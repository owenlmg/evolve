<?php
/**
 * 2013-11-25 上午12:08:30
 * @author x.li
 * @abstract 
 */
class Product_Model_Rolesetmember extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'product_catalog_roleset_member';
    protected $_primary = 'id';

    public function getData($roleset_id)
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array('role_id' => new Zend_Db_Expr("group_concat(t1.user_id)")))
                    ->joinLeft(array('t2' => $this->_dbprefix.'product_catalog_roleset'), "t2.id = t1.roleset_id", array('user_id'))
                    ->where("t1.roleset_id = ".$roleset_id)
                    ->group("t1.user_id");

        $data = $this->fetchAll($sql)->toArray();
        
        for($i = 0; $i < count($data); $i++){
            
        }
        
        return $data;
    }
}