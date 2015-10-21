<?php
/**
 * 2013-9-11 下午10:47:57
 * @author x.li
 * @abstract 
 */
class Erp_Model_Payment extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'bpartner_payment';
    protected $_primary = 'id';
    
    /**
     * 获取付款方式列表
     * @return unknown
     */
    public function getList(){
        $sql = $this->select()
                    ->from($this, array('id', 'name' => new Zend_Db_Expr("concat(name, ' [', qty, ']')")))
                    ->order(array('name'));
        $data = $this->fetchAll($sql)->toArray();
    
        return $data;
    }
    
    /**
     * 获取付款方式数据
     * @return Ambigous <boolean, multitype:>
     */
    public function getData()
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t2.id = t1.create_user", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t3.id = t2.employee_id", array('creater' => 'cname'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t4.id = t1.update_user", array())
                    ->joinLeft(array('t5' => $this->_dbprefix.'employee'), "t5.id = t4.employee_id", array('updater' => 'cname'))
                    ->order(array('name'));
    
        $data = $this->fetchAll($sql)->toArray();
    
        for($i = 0; $i < count($data); $i++){
            $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
            $data[$i]['update_time'] = strtotime($data[$i]['update_time']);
            $data[$i]['active'] = $data[$i]['active'] == 1 ? true : false;
        }
    
        return $data;
    }
}