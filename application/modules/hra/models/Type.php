<?php
/**
 * 2013-7-6 下午10:32:26
 * @author x.li
 * @abstract 
 */
class Hra_Model_Type extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'employee_type';
    protected $_primary = 'id';
    
    // 获取职位清单
    public function getList(){
        $sql = $this->select()
                    ->from($this, array('id', 'name'))
                    ->order(array('CONVERT( name USING gbk )'));
        $data = $this->fetchAll($sql)->toArray();
        
        for($i = 0; $i < count($data); $i++){
            $data[$i]['id'] = intval($data[$i]['id']);
        }
        
        return $data;
    }

    // 获取职位表
    public function getData()
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t2.id = t1.create_user", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t3.id = t2.employee_id", array('creater' => 'cname'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t4.id = t1.update_user", array())
                    ->joinLeft(array('t5' => $this->_dbprefix.'employee'), "t5.id = t4.employee_id", array('updater' => 'cname'))
                    ->order(array('CONVERT( t1.name USING gbk )'));
        
        $data = $this->fetchAll($sql)->toArray();

        for($i = 0; $i < count($data); $i++){
            $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
            $data[$i]['update_time'] = strtotime($data[$i]['update_time']);
            $data[$i]['active'] = $data[$i]['active'] == 1 ? true : false;
        }

        return $data;
    }
}