<?php
/**
 * 2013-8-20 下午8:11:21
 * @author x.li
 * @abstract 
 */
class Hra_Model_Attendanceparams extends Application_Model_Db
{
    protected $_name = 'attendance_params';
    protected $_primary = 'id';
    
    public function getParamByType($type)
    {
        $data = array();
        
        $tmpData = $this->fetchAll()->toArray();
        
        foreach ($tmpData as $t){
            $data[$t['employment_type']] = $t[$type];
        }
        
        return $data;
    }
    
    /**
     * 获取工作日设置信息列表
     * @param unknown $condition
     * @return number
     */
    public function getData()
    {
        $data = array();
        
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t1.create_user = t2.id", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t2.employee_id = t3.id", array('creater' => 'cname'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t1.update_user = t4.id", array())
                    ->joinLeft(array('t5' => $this->_dbprefix.'employee'), "t4.employee_id = t5.id", array('updater' => 'cname'))
                    ->order(array("t1.employment_type"));
        
        $data = $this->fetchAll($sql)->toArray();
        
        for($i = 0; $i < count($data); $i++){
            $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
            $data[$i]['update_time'] = strtotime($data[$i]['update_time']);
        }
        
        return $data;
    }
}