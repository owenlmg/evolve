<?php
/**
 * 2013-8-20 下午8:11:21
 * @author x.li
 * @abstract 
 */
class Hra_Model_Workday extends Application_Model_Db
{
    protected $_name = 'attendance_workday';
    protected $_primary = 'id';
    
    /**
     * 获取工作日设置信息列表
     * @param unknown $condition
     * @return number
     */
    public function getWorkdayList($condition = array())
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
                    ->where("t1.day >= '".$condition['date_from']."' and t1.day <= '".$condition['date_to']."'".$cond_type)
                    ->order(array("t1.day"));
        $data = $this->fetchAll($sql)->toArray();
        
        for($i = 0; $i < count($data); $i++){
            $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
            $data[$i]['update_time'] = strtotime($data[$i]['update_time']);
            
            $data[$i]['weekday'] = intval(date('w', strtotime($data[$i]['day'])));
            
            $data[$i]['type'] = intval($data[$i]['type']);
        }
        
        return $data;
    }
    
    /**
     * 根据用工形式和日期范围获取工作日、休息日、法定假日天数
     * @param unknown $employment_type
     * @param unknown $date_from
     * @param unknown $date_to
     * @return number
     */
    public function getDayQtyBase($day_type, $employment_type, $date_from, $date_to)
    {
        $qty = array(
                'day'   => 0,
                'hour'  => 0
        );
        
        $sql = $this->select()
                    ->from($this->_name)
                    ->where("type = ".$day_type." and employment_type = ".$employment_type." and day >= '".$date_from."' and day <= '".$date_to."'");
        
        $data = $this->fetchAll($sql);
        
        $worktime = new Hra_Model_Worktime();
        
        foreach ($data as $d){
            $qty['hour'] += $worktime->getWorkHours($day_type, $d['day']);
        }
        
        $qty['day'] = count($data);
        
        return $qty;
    }
    
    /**
     * 获取日期类别
     * @param unknown $date
     * @return unknown|number
     */
    public function getWorkdayType($employment_type, $date)
    {
        //$data = $this->fetchRow("employment_type = ".$employment_type." and day = '".$date."'")->toArray();
        $res = $this->fetchAll("day = '".$date."'");//employment_type = ".$employment_type." and 
        
        if($res->count() > 0){
            $data = $res->toArray();
            return $data[0]['type'];
        }else{
            return 0;
        }
    }
    
    /**
     * 根据时间工作时间范围、规定工作时间范围、规定休息时间范围获取当日工作天数（精确到小数点后2位）
     * @param unknown $work_from    实际工作起始时间
     * @param unknown $work_to      实际工作截止时间
     * @param unknown $c_work_from  规定工作开始时间
     * @param unknown $c_work_to    规定工作截止时间
     * @param unknown $c_rest_from  规定午休开始时间
     * @param unknown $c_rest_to    规定午休截止时间
     * @return number
     */
    public function getWorkdayQty($work_from, $work_to, $c_work_from, $c_work_to, $c_rest_from, $c_rest_to, $type = 'vacation', $work_day_type = 1)
    {
        $qty = 0;
        $qty_hours = 0;
        // 没设置午休时间的情况设置为0
        $c_rest_to_sec = $c_rest_to ? strtotime($c_rest_to) : 0;
        $c_rest_from_sec = $c_rest_from ? strtotime($c_rest_from) : 0;
        
        // 规定工作小时数：用于根据小时数计算天数
        $c_hours = round(((strtotime($c_work_to) - strtotime($c_work_from)) - ($c_rest_to_sec - $c_rest_from_sec)) / 3600, 2);
        
        // 实际工作小时数
        $w_hours = 0;
        
        // 起始时间小于截止时间
        if(strtotime($work_from) < strtotime($work_to)){
            // 如果当前获取类别是针对请假：按规定上班时间范围获取时长
            if($type == 'vacation'){
                // 工作日
                // 当有规定午休时间时，除开午休时间
                if($c_rest_from && $c_rest_to){
                    // 规定了午休时间
                    // 只记录规定工作时间内的工作时间（除开午休时间）
                    $from = strtotime($work_from) <= strtotime($c_work_from) ? $c_work_from : $work_from;
                    $to = strtotime($work_to) >= strtotime($c_work_to) ? $c_work_to : $work_to;
                    
                    if(strtotime($work_to) <= strtotime($c_rest_from) || strtotime($work_from) >= strtotime($c_rest_to)){
                        // 时间范围只属于上午或下午：工作截止时间小于公司规定休息起始时间（上午）或工作起始时间大于公司规定休息截止时间（下午）
                        $w_hours = round((strtotime($to) - strtotime($from)) / 3600, 2);
                    }else{
                        // 上午工作小时数
                        $am_w_hours = 0;
                        // 下午工作小时数
                        $pm_w_hours = 0;
                        // 获取上午工作小时数
                        if(strtotime($from) < strtotime($c_rest_from)){
                            $am_w_hours = round((strtotime($c_rest_from) - strtotime($from)) / 3600, 2);
                        }
                        // 获取下午工作小时数
                        if(strtotime($c_rest_to) < strtotime($to)){
                            $pm_w_hours = round((strtotime($to) - strtotime($c_rest_to)) / 3600, 2);
                        }
                        // 获取一天内总工作小时数
                        $w_hours += $am_w_hours + $pm_w_hours;
                    }
                }else{
                    // 当没规定午休时间时：根据起始时间和截止时间获取小时数
                    $w_hours = round((strtotime($work_to) - strtotime($work_from)) / 3600, 2);
                }
            }else{
                // 上午工作小时数
                $am_w_hours = 0;
                // 下午工作小时数
                $pm_w_hours = 0;
                
                // 如果当前获取类别针对加班
                if($work_day_type == 1){
                    if(strtotime($work_from) >= strtotime($c_work_to) || strtotime($work_to) <= strtotime($c_work_from)){
                        $w_hours = round((strtotime($work_to) - strtotime($work_from)) / 3600, 2);
                    }else{
                        // 获取上午工作小时数：规定上班时间前的工作小时数
                        if(strtotime($c_work_from) > strtotime($work_from)){
                            $am_w_hours = round((strtotime($c_work_from) - strtotime($work_from)) / 3600, 2);
                        }
                        // 获取下午工作小时数：规定下班时间后的工作小时数
                        if(strtotime($c_work_to) < strtotime($work_to)){
                            $pm_w_hours = round((strtotime($work_to) - strtotime($c_work_to)) / 3600, 2);
                        }
                        $w_hours += $am_w_hours + $pm_w_hours;
                    }
                }else{
                    // 休息日或法定假日加班：除开午休时间以外均算加班
                    if($c_rest_from && $c_rest_to){
                        // 获取上午工作小时数：规定午休起始时间前的工作小时数
                        if(strtotime($work_from) < strtotime($c_rest_from)){
                            $am_w_hours = round((strtotime($c_rest_from) - strtotime($work_from)) / 3600, 2);
                        }
                        // 获取下午工作小时数：固定午休结束时间后的工作小时数
                        if(strtotime($c_rest_to) < strtotime($work_to)){
                            $pm_w_hours = round((strtotime($work_to) - strtotime($c_rest_to)) / 3600, 2);
                        }
                        // 获取一天内总工作小时数
                        $w_hours += $am_w_hours + $pm_w_hours;
                    }else{
                        // 当没规定午休时间时：根据起始时间和截止时间获取小时数
                        $w_hours = round((strtotime($work_to) - strtotime($work_from)) / 3600, 2);
                    }
                }
            }
        }
        
        $qty = round($w_hours / $c_hours, 2);
        $qty_hours = $w_hours;
        
        return array('qty' => $qty, 'qty_hours' => $qty_hours);
    }
    
    /**
     * 根据时间范围获取总请假（工作日）天数：按实际工作时间和规定工作时间相除换算，精确到小数点后2位
     * @param unknown $userType
     * @param unknown $from
     * @param unknown $to
     * @return number
     */
    public function getWorkdayQtyByTimeRange($userType, $from, $to)
    {
        $result = array(
                'qty'       => 0,
                'qty_hours' => 0,
                'work_day'  => null
        );
        
        // 获取请假期间的工作日天数
        $workDayQty = 0;
        $workDayQty_hours = 0;
        
        $workDayArr = array();
        
        if(strtotime($from) < strtotime($to)){
            $dayCnt = 1;
    
            $fromDate = date('Y-m-d', strtotime($from));
            $toDate = date('Y-m-d', strtotime($to));
            
            $cnt = (strtotime($toDate) - strtotime($fromDate)) / 3600 / 24 + 1;//请假时间包含天数
            
            $workTime = new Hra_Model_Worktime();
            
            for($i = 0; $i < $cnt; $i++){
                $date = date('Y-m-d', strtotime($fromDate."+".$i." day"));
                
                // 获取工作日设置（暂时统一员工和工人的工作日设置）
                //$workDayRes = $this->fetchAll("employment_type = ".$userType." and day = '".$date."'");
                $workDayRes = $this->fetchAll("employment_type = 1 and day = '".$date."'");// 职员、工人工作日设置相同
                
                if($workDayRes->count() > 0){
                    $workDayInfo = $workDayRes->toArray();
                    
                    // 检查当前日期是否是工作日
                    if($workDayInfo[0]['type'] == 1){
                        // 记录日期范围包含的工作日
                        array_push($workDayArr, $date);
                        // 获取工作时间设置
                        $workTimeRes = $workTime->fetchAll("type = ".$userType." and active_from <= '".$date."' and active_to >= '".$date."'");
                        
                        if($workTimeRes->count() > 0){
                            $workTimeInfo = $workTimeRes->toArray();
                            
                            $work_from = $from;
                            $work_to = $to;
                            $c_work_from = $date.' '.substr($workTimeInfo[0]['work_from'], 11);
                            $c_work_to = $date.' '.substr($workTimeInfo[0]['work_to'], 11);
                            $c_rest_from = $workTimeInfo[0]['rest_from'] != '' ? $date.' '.substr($workTimeInfo[0]['rest_from'], 11) : null;
                            $c_rest_to = $workTimeInfo[0]['rest_to'] != '' ? $date.' '.substr($workTimeInfo[0]['rest_to'], 11) : null;
                            
                            // 请假时间是否超过1天
                            if($cnt > 1){
                                if($i == 0 || $i == $cnt - 1){
                                    if($i == 0){
                                        // 第一天：下班时间为公司规定下班时间
                                        $work_to = $c_work_to;
                                    }else if($i == $cnt - 1){
                                        // 最后一天：上班时间为公司规定上班时间
                                        $work_from = $c_work_from;
                                    }
                                    
                                    $qty = $this->getWorkdayQty($work_from, $work_to, $c_work_from, $c_work_to, $c_rest_from, $c_rest_to);
                                    
                                    $workDayQty += $qty['qty'];
                                    $workDayQty_hours += $qty['qty_hours'];
                                }else{
                                    // 请假中间日期算全勤：总天数加1
                                    $workDayQty++;
                                    
                                    $qty = $this->getWorkdayQty($c_work_from, $c_work_to, $c_work_from, $c_work_to, $c_rest_from, $c_rest_to);
                                    
                                    $workDayQty_hours += $qty['qty_hours'];
                                }
                            }else{
                                $qty = $this->getWorkdayQty($work_from, $work_to, $c_work_from, $c_work_to, $c_rest_from, $c_rest_to);
                                
                                $workDayQty = $qty['qty'];
                                $workDayQty_hours = $qty['qty_hours'];
                            }
                        }
                    }
                }
            }
        }
        
        $result['qty'] = $workDayQty;
        $result['qty_hours'] = $workDayQty_hours;
        $result['work_day'] = $workDayArr;
        
        return $result;
    }
    
    /**
     * 获取加班时长
     * @param unknown $userType
     * @param unknown $from
     * @param unknown $to
     * @return multitype:number NULL multitype:
     */
    public function getOvertimeQtyByTimeRange($userType, $from, $to)
    {
        $result = array(
                'qty'       => 0,
                'qty_hours' => 0,
                'work_day'  => null
        );
        
        $workDayQty = 0;
        $workDayQty_hours = 0;
    
        $workDayArr = array();
    
        if(strtotime($from) < strtotime($to)){
            $dayCnt = 1;
    
            $fromDate = date('Y-m-d', strtotime($from));
            $toDate = date('Y-m-d', strtotime($to));
            // 时间范围包含天数
            $cnt = (strtotime($toDate) - strtotime($fromDate)) / 3600 / 24 + 1;
    
            $workTime = new Hra_Model_Worktime();
            
            for($i = 0; $i < $cnt; $i++){
                $date = date('Y-m-d', strtotime($fromDate."+".$i." day"));
                
                // 获取工作日设置（暂时统一工人、员工的工作日设置）
                //$workDayRes = $this->fetchAll("employment_type = ".$userType." and day = '".$date."'");
                $workDayRes = $this->fetchAll("employment_type = 1 and day = '".$date."'");
    
                if($workDayRes->count() > 0){
                    $workDayInfo = $workDayRes->toArray();
                    // 记录加班时间范围所包含的工作日
                    array_push($workDayArr, $date);
                    
                    // 获取工作时间设置
                    $workTimeRes = $workTime->fetchAll("type = ".$userType." and active_from <= '".$date."' and active_to >= '".$date."'");
                    //$workTimeRes = $workTime->fetchAll("type = 0 and active_from <= '".$date."' and active_to >= '".$date."'");
                    
                    if($workTimeRes->count() > 0){
                        $workTimeInfo = $workTimeRes->toArray();
                    
                        $work_from = $from;
                        $work_to = $to;
                        $c_work_from = $date.' '.substr($workTimeInfo[0]['work_from'], 11);
                        $c_work_to = $date.' '.substr($workTimeInfo[0]['work_to'], 11);
                        $c_rest_from = $date.' '.substr($workTimeInfo[0]['rest_from'], 11);
                        $c_rest_to = $date.' '.substr($workTimeInfo[0]['rest_to'], 11);
                    
                        // 加班时间是否超过1天
                        if($cnt > 1){
                            if($i == 0 || $i == $cnt - 1){
                                if($i == 0){
                                    // 第一天：下班时间为公司规定下班时间
                                    $work_to = $date.' 23:59:59';
                                }else if($i == $cnt - 1){
                                    // 最后一天：上班时间为公司规定上班时间
                                    $work_from = $date.' 00:00:00';
                                }
                                
                                $qty = $this->getWorkdayQty($work_from, $work_to, $c_work_from, $c_work_to, $c_rest_from, $c_rest_to, 'overtime', $workDayInfo[0]['type']);
                                
                                $workDayQty += $qty['qty'];
                                $workDayQty_hours += $qty['qty_hours'];
                            }else{
                                // 没设置午休时间的情况设置为0
                                $c_rest_to_sec = $c_rest_to ? strtotime($c_rest_to) : 0;
                                $c_rest_from_sec = $c_rest_from ? strtotime($c_rest_from) : 0;
                                
                                // 规定工作小时数：用于根据小时数计算天数
                                $c_hours = round(((strtotime($c_work_to) - strtotime($c_work_from)) - ($c_rest_to_sec - $c_rest_from_sec)) / 3600, 2);
                                
                                $qty_hours = 0;
                                
                                if($workDayInfo[0]['type'] == 1){
                                    $qty_hours = 24 - $c_hours - ($c_rest_to_sec - $c_rest_from_sec) / 3600;
                                }else{
                                    $qty_hours = 24;
                                }
                                
                                $workDayQty_hours += $qty_hours;
                                $workDayQty += round($qty_hours / $c_hours, 2);
                            }
                        }else{
                            $qty = $this->getWorkdayQty($work_from, $work_to, $c_work_from, $c_work_to, $c_rest_from, $c_rest_to, 'overtime', $workDayInfo[0]['type']);
                            
                            $workDayQty = $qty['qty'];
                            $workDayQty_hours = $qty['qty_hours'];
                        }
                    }
                }
            }
        }
        
        $result['qty'] = $workDayQty;
        $result['qty_hours'] = $workDayQty_hours;
        $result['work_day'] = $workDayArr;
        
        return $result;
    }
}