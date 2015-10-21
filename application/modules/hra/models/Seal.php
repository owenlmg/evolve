<?php
/**
 * 2013-9-21 下午3:05:50
 * @author x.li
 * @abstract 
 */
class Hra_Model_Seal extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'seal';
    protected $_primary = 'id';
    
    public function getList()
    {
        $sql = $this->select()
                    ->from($this, array('id' => 'id', 'name' => 'name'))
                    ->where("active = 1")
                    ->order(array('name'));
        $data = $this->fetchAll($sql)->toArray();
        
        return $data;
    }
    
    public function getData()
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t1.create_user = t2.id", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t2.employee_id = t3.id", array('creater' => 'cname'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t1.update_user = t4.id", array())
                    ->joinLeft(array('t5' => $this->_dbprefix.'employee'), "t4.employee_id = t5.id", array('updater' => 'cname'))
                    ->joinLeft(array('t6' => $this->_dbprefix.'user'), "t1.administrator = t6.id", array())
                    ->joinLeft(array('t7' => $this->_dbprefix.'employee'), "t6.employee_id = t7.id", array('administrator_name' => 'cname'))
                    ->order(array('t1.name'));
        $data = $this->fetchAll($sql)->toArray();
        
        for($i = 0; $i < count($data); $i++){
            $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
            $data[$i]['update_time'] = strtotime($data[$i]['update_time']);
            $data[$i]['active'] = intval($data[$i]['active']);
        }
        
        return $data;
    }
}