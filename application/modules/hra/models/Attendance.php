<?php
/**
 * 2013-8-20 下午8:11:21
 * @author x.li
 * @abstract 
 */
class Hra_Model_Attendance extends Application_Model_Db
{
    protected $_name = 'attendance';
    protected $_primary = 'id';
    
    public function setClock($number, $employment_type, $from, $to, $remark)
    {
        $from_date = date('Y-m-d', strtotime($from));
        $to_date = date('Y-m-d', strtotime($to));
        
        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user_id = $user_session->user_info['user_id'];
        
        $workday = new Hra_Model_Workday();
        $worktime = new Hra_Model_Worktime();
        $attendance = new Hra_Model_Attendance();
        
        $vacationWorkDayInfo = $workday->getWorkdayQtyByTimeRange($employment_type, $from, $to);
        $vacationWorkDayArr = $vacationWorkDayInfo['work_day'];
        
        // 根据时间范围覆盖的工作日范围记录对应工号员工的打卡时间
        for($i = 0; $i < count($vacationWorkDayArr); $i++){
            $d = $vacationWorkDayArr[$i];
            
            $worktimeRange = $worktime->getWroktimeRange($employment_type, $d);
            
            // 打卡时间
            $time_on = $d.' '.$worktimeRange['from'];
            $time_off = $d.' '.$worktimeRange['to'];
            
            
            if($i == 0 && $d == $from_date){
                // 当起始日期和覆盖的工作日范围第一天一致时，判断起始时间是否 > 当天设定的上班打卡时间，是的话记录上班打卡时间为起始时间，否则记录上班打卡时间为系统设定时间
                if(strtotime($from > strtotime($d.' '.$worktimeRange['from']))){
                    $time_on = $from;
                }
            }else if(($i == count($vacationWorkDayArr) - 1) && $d == $to_date){
                // 当截止日期和覆盖的工作日范围最后一天一致时，判断截止时间是否 < 当天设定的下班打卡时间，是的话记录下班打卡时间为截止时间，否则记录上班打卡时间为系统设定时间
                if(strtotime($to < strtotime($d.' '.$worktimeRange['to']))){
                    $time_off = $to;
                }
            }
            
            // 记录上班时间
            $data = array(
                    'number'        => $number,
                    'time'          => $time_on,
                    'type'          => 1,
                    'remark'        => $remark,
                    'create_user'   => $user_id,
                    'create_time'   => $now,
                    'update_user'   => $user_id,
                    'update_time'   => $now
            );
            
            $attendance->insert($data);
            
            // 记录下班时间
            $data = array(
                    'number'        => $number,
                    'time'          => $time_off,
                    'type'          => 2,
                    'remark'        => $remark,
                    'create_user'   => $user_id,
                    'create_time'   => $now,
                    'update_user'   => $user_id,
                    'update_time'   => $now
            );
            
            $attendance->insert($data);
        }
    }
    
    public function setClockType($id, $employment_type, $time_in, $time_out)
    {
        // 打卡超过分钟数
        $clock_arr = array(
                'clock_chk'         => 1,
                'type'              => 0,
                'sec_late'          => 0,
                'sec_leave'         => 0,
                'sec_truancy_half'  => 0,
                'sec_truancy'       => 0,
                'clock_info'        => null
        );
        
        $time = isset($time_in) && $time_in != '' ? $time_in : $time_out;
        
        $time = strtotime($time);
        
        $date = date('Y-m-d', $time);
        
        $worktime = new Hra_Model_Worktime();
        
        $where = "type = ".$employment_type." and active_from <= '".$date."' and active_to >= '".$date."'";
        
        if($worktime->fetchAll($where)->count() > 0){
            $data = $worktime->fetchRow($where)->toArray();
            
            $work_from = substr($data['work_from'], 11);
            $work_to = substr($data['work_to'], 11);
            
            $work_from_time = strtotime($date.' '.$work_from);
            $work_to_time = strtotime($date.' '.$work_to);
            
            $limit_late_time = $work_from_time + $data['limit_late'] * 60;
            $limit_leave_time = $work_to_time - $data['limit_leave'] * 60;
            
            $limit_truancy_half_from_time = $work_from_time + $data['limit_truancy_half'] * 60;
            $limit_truancy_half_to_time = $work_to_time - $data['limit_truancy_half'] * 60;
            
            $limit_truancy_from_time = $work_from_time + $data['limit_truancy'] * 60;
            $limit_truancy_to_time = $work_to_time - $data['limit_truancy'] * 60;
            
            $clock_info_arr = array();
            
            // 检查上班时间
            if(isset($time_in) && $time_in != ''){
                $time = strtotime($time_in);
                
                if($time > $limit_late_time){
                    if($time <= $limit_truancy_half_from_time){
                        // 迟到
                        $clock_arr['sec_late'] = $time - $work_from_time;
                        
                        $clock_arr['type'] = 1;
                        
                        array_push($clock_info_arr, '迟到');
                    }else if($time <= $limit_truancy_from_time){
                        // 旷工半天
                        $clock_arr['sec_truancy_half'] += $time - $work_from_time;
                        
                        $clock_arr['type'] = 3;
                
                        array_push($clock_info_arr, '旷工半天');
                    }else{
                        // 旷工一天
                        $clock_arr['sec_truancy'] += $time - $work_from_time;
                        
                        $clock_arr['type'] = 4;
                
                        array_push($clock_info_arr, '旷工一天');
                    }
                }
            }
            
            // 检查下班时间
            if(isset($time_out) && $time_out != ''){
                $time = strtotime($time_out);
                
                if($time < $limit_leave_time){
                    if($time >= $limit_truancy_half_to_time){
                        // 早退
                        $clock_arr['sec_leave'] = $work_to_time - $time;
                        
                        $clock_arr['type'] = 2;
                
                        array_push($clock_info_arr, '早退');
                    }else if($time >= $limit_truancy_to_time){
                        // 旷工半天
                        $clock_arr['sec_truancy_half'] += $work_to_time - $time;
                        
                        $clock_arr['type'] = $clock_arr['type'] < 3 ? 3 : $clock_arr['type'];// 取最大值（1：迟到，2：早退，3：旷工半天，4：旷工一天）
                
                        array_push($clock_info_arr, '旷工半天');
                    }else{
                        // 旷工一天
                        $clock_arr['sec_truancy'] += $work_to_time - $time;
                        
                        $clock_arr['type'] = $clock_arr['type'] < 4 ? 4 : $clock_arr['type'];// 取最大值（1：迟到，2：早退，3：旷工半天，4：旷工一天）
                
                        array_push($clock_info_arr, '旷工一天');
                    }
                }
            }
            
            $clock_arr['clock_info'] = implode(',', $clock_info_arr);
            
            // 更新记录
            $this->update($clock_arr, "id = ".$id);
        }
        
        return $clock_arr;
    }
    
    public function convertSecToTime($sec)
    {
        $hour = floor($sec / 3600);
        
        $min = floor($sec % 3600 / 60);
        
        $sec = floor($sec % 60);
        
        return sprintf ("%02d", $hour).":".sprintf ("%02d", $min).":".sprintf ("%02d", $sec);
    }
    
    public function getStatisticsList($condition = array())
    {
        $data = array();
        
        $employee = new Hra_Model_Employee();
        
        $sql = $employee->select()
                        ->setIntegrityCheck(false)
                        ->from(array('t1' => $this->_dbprefix.'employee'))
                        ->joinLeft(array('t2' => $this->_dbprefix.'employee_dept'), "t2.id = t1.dept_id", array('dept_name' => 'name'))
                        ->joinLeft(array('t3' => $this->_dbprefix.'employee_post'), "t3.id = t1.post_id", array('post_name' => 'name'))
                        ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t4.employee_id = t1.id", array('user_id' => 'id'))
                        ->joinLeft(array('t5' => $this->_dbprefix.'employee_type'), "t1.employment_type = t5.id", array('employment_type' => 'name'))
                        ->where("(t1.leave_date is null or (t1.leave_date is not null and t1.leave_date >= '".$condition['date_from']."' and t1.leave_date <= '".$condition['date_to']."'))")
                        ->order(array("t1.employment_type", "t1.number"));
        
        if($condition['employment_type'] != ''){
            $sql->where("employment_type = ".$condition['employment_type']);
        }
        
        if($condition['key']){
            $sql->where("t1.ename like '".$condition['key']."' or t1.cname like '".$condition['key']."' or t1.number like '".$condition['key']."'");
        }
        
        $total = $employee->fetchAll($sql)->count();
        
        if($condition['type'] != 'csv'){
            $sql->limitPage($condition['page'], $condition['limit']);
        }
        
        $data = $employee->fetchAll($sql)->toArray();
        
        $workday = new Hra_Model_Workday();
        $vacation = new Hra_Model_Attendance_Vacation();
        $overtime = new Hra_Model_Attendance_Overtime();
        
        // 员工工作日设置（目前均按弹性员工工作日设置处理）
        $workdaySetting = $workday->getDayQtyBase(1, 1, $condition['date_from'], $condition['date_to']);
        $holidaySetting = $workday->getDayQtyBase(3, 1, $condition['date_from'], $condition['date_to']);
        
        for($i = 0; $i < count($data); $i++){
            $data[$i]['workday_qty']    = $workdaySetting['day'];
            $data[$i]['workhour_qty']   = $workdaySetting['hour'];
            $data[$i]['holiday_qty']    = $holidaySetting['day'];
            
            $data[$i]['attendance_qty'] = null;
            $data[$i]['attendance_hour_qty'] = 0;
            $data[$i]['v_personal_qty'] = null;
            $data[$i]['v_vacation_qty'] = null;
            $data[$i]['v_sick_qty'] = null;
            $data[$i]['v_marriage_qty'] = null;
            $data[$i]['v_funeral_qty'] = null;
            $data[$i]['v_childbirth_qty'] = null;
            $data[$i]['v_childbirth_with_qty'] = null;
            $data[$i]['v_other_qty'] = null;
            $data[$i]['o_workday_qty'] = null;
            $data[$i]['o_restday_qty'] = null;
            $data[$i]['o_holiday_qty'] = null;
            $data[$i]['late_qty'] = null;
            $data[$i]['leave_early_qty'] = null;
            $data[$i]['absence_halfday_qty'] = null;
            $data[$i]['absence_qty'] = null;
            
            if($data[$i]['user_id']){
                // 出勤天数
                $sql = $this->select()
                            ->from($this->_name)
                            ->where("number = '".$data[$i]['number']."' and clock_in >= '".$condition['date_from']." 00:00:00' and clock_out <= '".$condition['date_to']." 23:59:59'")
                            ->group("date_format(clock_in, '%Y-%m-%d')");
                
                $aData = $this->fetchAll($sql)->toArray();
                
                foreach ($aData as $a){
                    $data[$i]['attendance_hour_qty'] += $a['clock_hours'];
                }
                
                $data[$i]['attendance_qty'] = count($aData);
                
                // 请假天数
                $data[$i]['v_personal_qty'] = $vacation->getVacationQty($data[$i]['user_id'], 1, $condition['date_from'], $condition['date_to']);
                $data[$i]['v_vacation_qty'] = $vacation->getVacationQty($data[$i]['user_id'], 2, $condition['date_from'], $condition['date_to']);
                $data[$i]['v_sick_qty'] = $vacation->getVacationQty($data[$i]['user_id'], 3, $condition['date_from'], $condition['date_to']);
                $data[$i]['v_marriage_qty'] = $vacation->getVacationQty($data[$i]['user_id'], 4, $condition['date_from'], $condition['date_to']);
                $data[$i]['v_funeral_qty'] = $vacation->getVacationQty($data[$i]['user_id'], 5, $condition['date_from'], $condition['date_to']);
                $data[$i]['v_childbirth_qty'] = $vacation->getVacationQty($data[$i]['user_id'], 6, $condition['date_from'], $condition['date_to']);
                $data[$i]['v_childbirth_with_qty'] = $vacation->getVacationQty($data[$i]['user_id'], 7, $condition['date_from'], $condition['date_to']);
                $data[$i]['v_other_qty'] = $vacation->getVacationQty($data[$i]['user_id'], 8, $condition['date_from'], $condition['date_to']);
                
                // 加班天数
                $data[$i]['o_workday_qty'] = $overtime->getOvertimeQty($data[$i]['user_id'], 1, $condition['date_from'], $condition['date_to']);
                $data[$i]['o_restday_qty'] = $overtime->getOvertimeQty($data[$i]['user_id'], 2, $condition['date_from'], $condition['date_to']);
                $data[$i]['o_holiday_qty'] = $overtime->getOvertimeQty($data[$i]['user_id'], 3, $condition['date_from'], $condition['date_to']);
                
                //其它统计（迟到、早退、旷工次数）
                $other = $this->getAbsenceQty($data[$i]['number'], $condition['date_from'], $condition['date_to']);
                $data[$i]['late_qty'] = $other[1];
                $data[$i]['leave_early_qty'] = $other[2];
                $data[$i]['absence_halfday_qty'] = $other[3];
                $data[$i]['absence_qty'] = $other[4];
            }
        }
        
        if($condition['type'] == 'csv'){
            $data_csv = array();
        
            $title = array(
                    'cnt'                   => '#',
                    'employment_type'       => '用工形式',
                    'number'                => '工号',
                    'cname'                 => '中文名',
                    'ename'                 => '英文名',
                    'dept_name'             => '部门',
                    'post_name'             => '职位',
                    'active'                => '状态',
                    'attendance'            => '出勤/考勤 [天]',
                    //'attendance_hours'      => '出勤/考勤 [小时]',
                    'holiday_qty'           => '法定假日',
                    'v_personal_qty'        => '事假',
                    'v_vacation_qty'        => '年假',
                    'v_sick_qty'            => '病假',
                    'v_marriage_qty'        => '婚假',
                    'v_funeral_qty'         => '丧假',
                    'v_childbirth_qty'      => '产假',
                    'v_childbirth_with_qty' => '陪产假',
                    'v_other_qty'           => '调休',
                    'o_workday_qty'         => '工作日加班',
                    'o_restday_qty'         => '休息日加班',
                    'o_holiday_qty'         => '法定假日加班',
                    'late_qty'              => '迟到',
                    'leave_early_qty'       => '早退',
                    'absence_halfday_qty'   => '旷工半天',
                    'absence_qty'           => '旷工一天'
            );
        
            array_push($data_csv, $title);
        
            $i = 0;
        
            foreach ($data as $d){
                $i++;
        
                $info = array(
                        'cnt'                   => $i,
                        'employment_type'       => $d['employment_type'] == 0 ? '弹性' : '非弹性',
                        'number'                => $d['number'],
                        'cname'                 => $d['cname'],
                        'ename'                 => $d['ename'],
                        'dept_name'             => $d['dept_name'],
                        'post_name'             => $d['post_name'],
                        'active'                => $d['active'] == 1 ? '在职' : '离职',
                        'attendance'            => $d['workday_qty'].' { '.$d['attendance_qty'].' }',
                        'attendance_hours'      => $d['workhour_qty'].' { '.$d['attendance_hour_qty'].' }',
                        'holiday_qty'           => $d['holiday_qty'],
                        'v_personal_qty'        => $d['v_personal_qty'],
                        'v_vacation_qty'        => $d['v_vacation_qty'],
                        'v_sick_qty'            => $d['v_sick_qty'],
                        'v_marriage_qty'        => $d['v_marriage_qty'],
                        'v_funeral_qty'         => $d['v_funeral_qty'],
                        'v_childbirth_qty'      => $d['v_childbirth_qty'],
                        'v_childbirth_with_qty' => $d['v_childbirth_with_qty'],
                        'v_other_qty'           => $d['v_other_qty'],
                        'o_workday_qty'         => $d['o_workday_qty'],
                        'o_restday_qty'         => $d['o_restday_qty'],
                        'o_holiday_qty'         => $d['o_holiday_qty'],
                        'late_qty'              => $d['late_qty'],
                        'leave_early_qty'       => $d['leave_early_qty'],
                        'absence_halfday_qty'   => $d['absence_halfday_qty'],
                        'absence_qty'           => $d['absence_qty']
                );
        
                array_push($data_csv, $info);
            }
            
            return $data_csv;
        }
        
        return array('total' => $total, 'rows' => $data);
    }
    
    public function getAbsenceQty($number, $date_from, $date_to)
    {
        $qty = array(
                1 => 0,
                2 => 0,
                3 => 0,
                4 => 0
        );
        
        $sql = $this->select()
                    ->from($this->_name, array('qty' => "count(*)", 'type'))
                    ->where("number = '".$number."' and type > 0 and clock_in >= '".$date_from." 00:00:00' and clock_out <= '".$date_to." 23:59:59'")
                    ->group("type");
        
        $data = $this->fetchAll($sql)->toArray();
        
        foreach ($data as $d){
            $qty[$d['type']] = $d['qty'];
        }
        
        return $qty;
    }
    
    // 获取员工考勤信息表
    public function getAttendanceList($condition = array())
    {
        $data = array();
        
        $cond_dept = "";
        
        if($condition['dept'] > 0){
            $cond_dept = " and t3.id = ".$condition['dept'];
        }
        
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'employee'), "t2.number = t1.number", array('cname', 'ename', 'employment_type', 'employee_id' => 'id'))
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee_dept'), "t3.id = t2.dept_id", array('dept' => 'name'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t1.create_user = t4.id", array())
                    ->joinLeft(array('t5' => $this->_dbprefix.'employee'), "t4.employee_id = t5.id", array('creater' => 'cname'))
                    ->joinLeft(array('t6' => $this->_dbprefix.'user'), "t1.update_user = t6.id", array())
                    ->joinLeft(array('t7' => $this->_dbprefix.'employee'), "t6.employee_id = t7.id", array('updater' => 'cname'))
                    ->where("(t1.clock_date >= '".$condition['date_from']."' and t1.clock_date <= '".$condition['date_to']."') and (t1.number like '%".$condition['key']."%' or t2.cname like '%".$condition['key']."%' or t2.ename like '%".$condition['key']."%')")
                    ->order(array("t1.number", "t1.clock_in", "t1.clock_out"));
        
        $total = $this->fetchAll($sql)->count();
        
        if($condition['type'] != 'csv'){
            $sql->limitPage($condition['page'], $condition['limit']);
        }
        
        $data = $this->fetchAll($sql)->toArray();
        
        for($i = 0; $i < count($data); $i++){
            $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
            $data[$i]['update_time'] = strtotime($data[$i]['update_time']);
            
            if($data[$i]['clock_chk'] == 0){
                $clock_type = $this->setClockType($data[$i]['id'], $data[$i]['employment_type'], $data[$i]['clock_in'], $data[$i]['clock_out']);
                
                $data[$i]['sec_late'] = $clock_type['sec_late'];
                $data[$i]['sec_leave'] = $clock_type['sec_leave'];
                $data[$i]['sec_truancy_half'] = $clock_type['sec_truancy_half'];
                $data[$i]['sec_truancy'] = $clock_type['sec_truancy'];
                
                $data[$i]['clock_info'] = $clock_type['clock_info'];
                
                $data[$i]['clock_chk'] = 1;
            }
            
            $absence = $data[$i]['sec_late'] + $data[$i]['sec_leave'] + $data[$i]['sec_truancy_half'] + $data[$i]['sec_truancy'];
            $data[$i]['absence'] = $this->convertSecToTime($absence);
            $data[$i]['sec_late'] = $this->convertSecToTime($data[$i]['sec_late']);
            $data[$i]['sec_leave'] = $this->convertSecToTime($data[$i]['sec_leave']);
            $data[$i]['sec_truancy_half'] = $this->convertSecToTime($data[$i]['sec_truancy_half']);
            $data[$i]['sec_truancy'] = $this->convertSecToTime($data[$i]['sec_truancy']);
            $data[$i]['clock_info'] = $data[$i]['clock_info'] == null ? '' : $data[$i]['clock_info'];
            
            $data[$i]['clock_in'] = $data[$i]['clock_in'] ? strtotime($data[$i]['clock_in']) : null;
            $data[$i]['clock_out'] = $data[$i]['clock_out'] ? strtotime($data[$i]['clock_out']) : null;
            // 日期
            $date = $data[$i]['clock_in'] ? date('Y-m-d', $data[$i]['clock_in']) : date('Y-m-d', $data[$i]['clock_out']);
            // 周次
            $data[$i]['week'] = date('W', strtotime($date));
            // 星期
            $data[$i]['day_of_week'] = date('w', strtotime($date));
        }
        /* echo '<pre>';
        print_r($data);
        exit; */
        
        if($condition['type'] == 'csv'){
            $data_csv = array();
        
            $title = array(
                    'cnt'           => '#',
                    'number'        => '工号',
                    'cname'         => '中文名',
                    'ename'         => '英文名',
                    'dept'          => '部门',
                    'week'          => '周次',
                    'day_of_week'   => '星期',
                    'clock_in'      => '上班时间',
                    'clock_out'     => '下班时间',
                    'clock_hours'   => '时长',
                    'clock_info'    => '打卡结果',
                    'absence'       => '缺勤时长',
                    'remark'        => '备注',
                    'create_user'   => '创建人',
                    'create_time'   => '创建时间',
                    'update_user'   => '更新人',
                    'update_time'   => '更新时间'
            );
        
            array_push($data_csv, $title);
        
            $i = 0;
        
            foreach ($data as $d){
                $i++;
                
                $info = array(
                        'cnt'           => $i,
                        'number'        => $d['number'],
                        'cname'         => $d['cname'],
                        'ename'         => $d['ename'],
                        'dept'          => $d['dept'],
                        'week'          => $d['week'],
                        'day_of_week'   => $d['day_of_week'],
                        'clock_in'      => date('Y-m-d H:i:s', $d['clock_in']),
                        'clock_out'     => date('Y-m-d H:i:s', $d['clock_out']),
                        'clock_hours'   => $d['clock_hours'],
                        'clock_info'    => $d['clock_info'],
                        'absence'       => $d['absence'] == '00:00:00' ? '' : $d['absence'],
                        'remark'        => $d['remark'],
                        'create_user'   => $d['creater'],
                        'create_time'   => date('Y-m-d H:i:s', $d['create_time']),
                        'update_user'   => $d['updater'],
                        'update_time'   => date('Y-m-d H:i:s', $d['update_time'])
                );
                
                array_push($data_csv, $info);
            }
            
            return $data_csv;
        }
        
        return array('total' => $total, 'rows' => $data);
    }
    
    // 获取员工考勤信息表
    public function getAttendanceList1($condition = array())
    {
        $data = array();
        
        $cond_dept = "";
        
        if($condition['dept'] > 0){
            $cond_dept = " and t3.id = ".$condition['dept'];
        }
        
        $where = "(t1.number like '%".$condition['key']."%' or t2.cname like '%".$condition['key']."%' or t2.ename like '%".$condition['key']."%') and t1.clock_in >= '".$condition['date_from']." 00:00:00' and t1.clock_out <= '".$condition['date_to']." 23:59:59'".$cond_dept;
        
        if($condition['only_on_and_off']){
            $where .= " and type > 0";
        }
        
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'employee'), "t2.number = t1.number", array('cname', 'ename'))
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee_dept'), "t3.id = t2.dept_id", array('dept' => 'name'))
                    ->where($where);
        
        $total = $this->fetchAll($sql)->count();
        
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'employee'), "t2.number = t1.number", array('cname', 'ename', 'employment_type', 'employee_id' => 'id'))
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee_dept'), "t3.id = t2.dept_id", array('dept' => 'name'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t1.create_user = t4.id", array())
                    ->joinLeft(array('t5' => $this->_dbprefix.'employee'), "t4.employee_id = t5.id", array('creater' => 'cname'))
                    ->joinLeft(array('t6' => $this->_dbprefix.'user'), "t1.update_user = t6.id", array())
                    ->joinLeft(array('t7' => $this->_dbprefix.'employee'), "t6.employee_id = t7.id", array('updater' => 'cname'))
                    ->where($where)
                    ->order(array("t1.number", "t1.time"));
        
        if($condition['type'] != 'csv'){
            $sql->limitPage($condition['page'], $condition['limit']);
        }
        
        $data = $this->fetchAll($sql)->toArray();
        
        for($i = 0; $i < count($data); $i++){
            $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
            $data[$i]['update_time'] = strtotime($data[$i]['update_time']);
            $data[$i]['week'] = date('W', strtotime($data[$i]['time']));
            $data[$i]['day_of_week'] = date('w', strtotime($data[$i]['time']));
            
            $data[$i]['type'] = intval($data[$i]['type']);
            
            // 未确认打卡类别时，获取打卡类别
            if($data[$i]['type'] > 0 && $data[$i]['clock_chk'] == 0){
                $clock_type = $this->setClockType($data[$i]['id'], $data[$i]['employment_type'], $data[$i]['type'], $data[$i]['time']);
                
                $data[$i]['sec_late'] = $clock_type['sec_late'];
                $data[$i]['sec_leave'] = $clock_type['sec_leave'];
                $data[$i]['sec_truancy_half'] = $clock_type['sec_truancy_half'];
                $data[$i]['sec_truancy'] = $clock_type['sec_truancy'];
                
                $data[$i]['clock_info'] = $clock_type['clock_info'];
                
                $data[$i]['clock_chk'] = 1;
            }
            
            $data[$i]['sec_late'] = $this->convertSecToTime($data[$i]['sec_late']);
            $data[$i]['sec_leave'] = $this->convertSecToTime($data[$i]['sec_leave']);
            $data[$i]['sec_truancy_half'] = $this->convertSecToTime($data[$i]['sec_truancy_half']);
            $data[$i]['sec_truancy'] = $this->convertSecToTime($data[$i]['sec_truancy']);
            $data[$i]['clock_info'] = $data[$i]['clock_info'] == null ? '' : $data[$i]['clock_info'];
            
            $data[$i]['absence'] = '';
            
            if($data[$i]['sec_late'] != '00:00:00'){
                $data[$i]['absence'] = $data[$i]['sec_late'];
            }else if($data[$i]['sec_leave'] != '00:00:00'){
                $data[$i]['absence'] = $data[$i]['sec_leave'];
            }else if($data[$i]['sec_truancy_half'] != '00:00:00'){
                $data[$i]['absence'] = $data[$i]['sec_truancy_half'];
            }else if($data[$i]['sec_truancy'] != '00:00:00'){
                $data[$i]['absence'] = $data[$i]['sec_truancy'];
            }
            
            $data[$i]['time'] = strtotime($data[$i]['time']);
        }
        
        /* echo '<pre>';
        print_r($data);
        exit; */
        
        if($condition['type'] == 'csv'){
            $data_csv = array();
        
            $title = array(
                    'cnt'           => '#',
                    'number'        => '工号',
                    'type'          => '类别',
                    'time'          => '打卡时间',
                    'week'          => '周次',
                    'day_of_week'   => '星期',
                    'dept'          => '部门',
                    'cname'         => '中文名',
                    'ename'         => '英文名',
                    'pc'            => '计算机名',
                    'ip'            => 'IP地址',
                    'remark'        => '备注',
                    'create_user'   => '创建人',
                    'create_time'   => '创建时间',
                    'update_user'   => '更新人',
                    'update_time'   => '更新时间'
            );
        
            array_push($data_csv, $title);
        
            $i = 0;
        
            foreach ($data as $d){
                $i++;
                
                $info = array(
                        'cnt'           => $i,
                        'number'        => $d['number'],
                        'type'          => $d['type'],
                        'time'          => date('Y-m-d H:i:s', $d['time']),
                        'week'          => $d['week'],
                        'day_of_week'   => $d['day_of_week'],
                        'dept'          => $d['dept'],
                        'cname'         => $d['cname'],
                        'ename'         => $d['ename'],
                        'pc'            => $d['pc'],
                        'ip'            => $d['ip'],
                        'remark'        => $d['remark'],
                        'create_user'   => $d['creater'],
                        'create_time'   => date('Y-m-d H:i:s', $d['create_time']),
                        'update_user'   => $d['updater'],
                        'update_time'   => date('Y-m-d H:i:s', $d['update_time'])
                );
                
                array_push($data_csv, $info);
            }
            
            return $data_csv;
        }
        
        return array('total' => $total, 'rows' => $data);
    }
}