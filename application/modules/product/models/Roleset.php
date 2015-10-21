<?php
/**
 * 2013-11-25 上午12:08:30
 * @author x.li
 * @abstract 
 */
class Product_Model_Roleset extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'product_catalog_roleset';
    protected $_primary = 'id';

    public function getData($catalog_id)
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'product_catalog_roleset_member'), "t1.id = t2.roleset_id", array('user_id' => new Zend_Db_Expr("group_concat(t2.user_id)")))
                    ->joinLeft(array('t3' => $this->_dbprefix.'user_role'), "t1.role_id = t3.id", array('role' => 't3.name'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t4.id = t2.user_id", array())
                    ->joinLeft(array('t5' => $this->_dbprefix.'employee'), "t5.id = t4.employee_id", array('user' => new Zend_Db_Expr("group_concat(t5.cname)")))
                    ->where("t1.catalog_id = ".$catalog_id)
                    ->order(array('CONVERT( t3.name USING gbk )'))
                    ->group("t1.role_id");
        
        $data = $this->fetchAll($sql)->toArray();
        
        for($i = 0; $i < count($data); $i++){
            $data[$i]['active'] = $data[$i]['active'] == 1 ? true : false;
        }
        
        return $data;
    }
}