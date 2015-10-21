<?php
/**
 * 2013-8-20 下午8:11:21
 * @author x.li
 * @abstract 
 */
class Hra_Model_Worktime extends Application_Model_Db
{
    protected $_name = 'attendance_worktime';
    protected $_primary = 'id';
    
    public function getWorkHours($type, $date)
    {
        $hours = 0;
        
        $sql = $this->select()
                    ->from($this)
                    ->where("type = ".$type." and active_from <= '".$date."' and active_to >= '".$date."'");
        
        if($this->fetchAll($sql)->count() > 0){
            $data = $this->fetchRow($sql)->toArray();
            
            $hours = round((strtotime($data['work_to']) - strtotime($data['work_from'])) / 3600, 2);
        }
        
        return $hours;
    }
    
    public function getWroktimeRange($employment_type, $date)
    {
        $data = $this->fetchRow("type = ".$employment_type." and active_from <= '".$date."'", "active_from desc")->toArray();
        
        $timeRange = array(
                'from'  => substr($data['work_from'], 11),
                'to'    => substr($data['work_to'], 11)
        );
        
        return $timeRange;
    }
    
    public function getWorktimeList($condition = array())
    {
        $data = array();
        
        $cond_type = "";
        if($condition['type'] > 0){
            $cond_type = " and t1.type = ".$condition['type'];
        }
        
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t1.create_user = t2.id", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t2.employee_id = t3.id", array('creater' => 'cname'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t1.update_user = t4.id", array())
                    ->joinLeft(array('t5' => $this->_dbprefix.'employee'), "t4.employee_id = t5.id", array('updater' => 'cname'))
                    ->where("t1.active_from != ''".$cond_type)
                    ->order(array("t1.type", "t1.active_from desc"));
        $data = $this->fetchAll($sql)->toArray();
        
        for($i = 0; $i < count($data); $i++){
            $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
            $data[$i]['update_time'] = strtotime($data[$i]['update_time']);
            $data[$i]['work_from'] = strtotime($data[$i]['work_from']);
            $data[$i]['work_to'] = strtotime($data[$i]['work_to']);
            $data[$i]['rest_from'] = strtotime($data[$i]['rest_from']);
            $data[$i]['rest_to'] = strtotime($data[$i]['rest_to']);
            
            $data[$i]['type'] = intval($data[$i]['type']);
        }
        
        return $data;
    }
}