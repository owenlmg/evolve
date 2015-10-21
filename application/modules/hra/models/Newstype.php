<?php
/**
 * 2013-7-6 下午10:32:26
 * @author x.li
 * @abstract 
 */
class Hra_Model_Newstype extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'news_type';
    protected $_primary = 'id';
    
    public function getList(){
        $sql = $this->select()
                    ->from($this, array('id', 'name'))
                    ->order(array('name'));
        $data = $this->fetchAll($sql)->toArray();
        
        return $data;
    }

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
            $data[$i]['public'] = $data[$i]['public'] == 1 ? true : false;
        }

        return $data;
    }
}