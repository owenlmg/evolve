<?php
/**
 * 2014-8-5 20:33:12
 * @author      x.li
 * @abstract    会议室
 */
class Res_Model_Meetingroom extends Application_Model_Db
{
    protected $_name = 'meeting_room';
    protected $_primary = 'id';
    
    // 获取列表
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

    // 获取数据
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
            $data[$i]['active'] = $data[$i]['active'] == 1 ? true : false;
        }

        return $data;
    }
}