<?php
/**
 * 2013-8-17 上午5:49:43
 * @author x.li
 * @abstract 
 */
class Hra_AttendanceController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $user_session = new Zend_Session_Namespace('user');
        
        $this->view->hraAdmin = 0;
        
        $this->view->user_id = 0;
        
        if(isset($user_session->user_info)){
            $this->view->user_id = $user_session->user_info['user_id'];
        
            if(Application_Model_User::checkPermissionByRoleName('系统管理员') || Application_Model_User::checkPermissionByRoleName('人事主管')){
                $this->view->hraAdmin = 1;
            }
        }
    }
    
    public function refreshhoursAction()
    {
        $attendance = new Hra_Model_Attendance();
        
        $data = $attendance->fetchAll("clock_in != '' and clock_out != ''")->toArray();
        
        foreach ($data as $d){
            $clock_hours = (strtotime($d['clock_out']) - strtotime($d['clock_in'])) / 3600;
            
            $attendance->update(array('clock_hours' => $clock_hours), 'id = '.$d['id']);
        }
        
        exit;
    }
    
    /**
     * 根据员工打卡时间，判断打卡类别（0：无，1：上班，2：下班）
     * @param unknown $number
     * @param unknown $time
     */
    public function checkAttendanceType($number, $time, $attendance_id = null)
    {
        $type = 0;
        
        $attendance = new Hra_Model_Attendance();
        
        // 打卡记录ID
        $idCond = "";
        
        // 当打卡记录ID不为空时，当前操作属于更新数据，需要排除当前打卡记录ID
        if($attendance_id){
            $idCond = " and id != ".$attendance_id;
        }
        
        $date = date('Y-m-d', strtotime($time));
        
        // 检查员工当天是否有更早的打卡时间
        if($attendance->fetchAll("number = '".$number."' and DATE(time) = '".$date."' and time < '".$time."'".$idCond)->count() > 0){
            // 检查员工当天是否有更晚的打卡时间
            if($attendance->fetchAll("number = '".$number."' and DATE(time) = '".$date."' and time > '".$time."'".$idCond)->count() > 0){
                // 当前打卡类型为“0：无”
                $type = 0;
            }else{
                // 当前打卡类型为“2：下班”
                $type = 2;
            }
        }else{
            // 当前打卡类型为“1：上班”
            $type = 1;
        }
        
        return $type;
    }
    
    public function setAttendanceType($id)
    {
        $attendance = new Hra_Model_Attendance();
        
        $data = $attendance->fetchRow("id = ".$id)->toArray();
        
        $type = $this->checkAttendanceType($data['number'], $data['time'], $id);
        
        $date = date('Y-m-d', strtotime($data['time']));
        
        // 当类别不为0时，更新原有数据中（当天、当前用户）打卡类别为当前类别的数据为0（更新打卡类别）
        if($type != 0){
            try {
                $updateData = array(
                        'type'              => 0,
                        'sec_late'          => 0,
                        'sec_leave'         => 0,
                        'sec_truancy_half'  => 0,
                        'sec_truancy'       => 0,
                        'clock_chk'         => 0,
                        'clock_info'        => null
                );
        
                $attendance->update($updateData, "id != ".$id." and number = '".$data['number']."' and DATE(time) = '".$date."' and type = ".$type);
            } catch (Exception $e) {
                $result['success'] = false;
                $result['info'] = $e->getMessage();
        
                echo Zend_Json::encode($result);
        
                exit;
            }
        }
        
        try {
            $attendance->update(array('type' => $type), "id = ".$id);
        } catch (Exception $e) {
            $result['success'] = false;
            $result['info'] = $e->getMessage();
        
            echo Zend_Json::encode($result);
        
            exit;
        }
    }
    
    // 修改打卡记录
    public function editattendanceAction()
    {
        $result = array(
                'success'   => true,
                'info'      => '保存成功'
        );
        
        $request = $this->getRequest()->getParams();
        
        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user_id = $user_session->user_info['user_id'];
        
        $json = json_decode($request['json']);
        
        $updated = $json->updated;
        $inserted = $json->inserted;
        $deleted = $json->deleted;
        
        $attendance = new Hra_Model_Attendance();
        
        if(count($updated) > 0){
            foreach ($updated as $val){
                $clock_in = str_replace('T', ' ', $val->clock_in);
                $clock_out = str_replace('T', ' ', $val->clock_out);
                
                if($attendance->fetchAll("id != ".$val->id." and number = '".$val->number."' and (clock_in = '".$clock_in."' or clock_out = '".$clock_out."')")->count() > 0){
                    $result['success'] = false;
                    $result['info'] = $val->number." 打卡时间重叠，请确认打卡时间！";
                    
                    echo Zend_Json::encode($result);
                    
                    exit;
                }
            }
            
            foreach ($updated as $val){
                $clock_in = str_replace('T', ' ', $val->clock_in);
                $clock_out = str_replace('T', ' ', $val->clock_out);
                
                $clock_hours = 0;
                
                if($clock_in != '' && $clock_out != ''){
                    $clock_hours = (strtotime($clock_out) - strtotime($clock_in)) / 3600;
                }
                
                $data = array(
                        'number'        => $val->number,
                        'clock_in'      => $clock_in,
                        'clock_out'     => $clock_out,
                        'clock_hours'   => $clock_hours,
                        'clock_chk'     => 0,// 更新数据后需要重新刷新打卡结果
                        'remark'        => $val->remark,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
                
                $where = "id = ".$val->id;
                
                try {
                    $attendance->update($data, $where);
                } catch (Exception $e) {
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
                    
                    echo Zend_Json::encode($result);
                    
                    exit;
                }
            }
        }
        
        if(count($inserted) > 0){
            foreach ($inserted as $val){
                $clock_in = str_replace('T', ' ', $val->clock_in);
                $clock_out = str_replace('T', ' ', $val->clock_out);
                
                if($attendance->fetchAll("number = '".$val->number."' and (clock_in = '".$clock_in."' or clock_out = '".$clock_out."')")->count() > 0){
                    $result['success'] = false;
                    $result['info'] = $val->number." 打卡时间重叠，请确认打卡时间！";
                    
                    echo Zend_Json::encode($result);
                    
                    exit;
                }
            }
            
            foreach ($inserted as $val){
                $clock_in = str_replace('T', ' ', $val->clock_in);
                $clock_out = str_replace('T', ' ', $val->clock_out);
                
                $clock_hours = 0;
                
                if($clock_in != '' && $clock_out != ''){
                    $clock_hours = (strtotime($clock_out) - strtotime($clock_in)) / 3600;
                }
                
                $data = array(
                        'number'        => $val->number,
                        'clock_in'      => $clock_in,
                        'clock_out'     => $clock_out,
                        'clock_hours'   => $clock_hours,
                        'remark'        => $val->remark,
                        'create_time'   => $now,
                        'create_user'   => $user_id,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
                
                try {
                    $attendance->insert($data);
                } catch (Exception $e) {
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
                
                    echo Zend_Json::encode($result);
                
                    exit;
                }
            }
        }
        
        if(count($deleted) > 0){
            foreach ($deleted as $val){
                try {
                    $attendance->delete("id = ".$val->id);
                } catch (Exception $e){
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
                    
                    echo Zend_Json::encode($result);
                    
                    exit;
                }
            }
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    // 导入测试数据
    public function addtestdataAction()
    {
        $result = array(
                'success'   => true,
                'info'      => '导入成功'
        );
        
        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user_id = $user_session->user_info['user_id'];
        
        $employee = new Hra_Model_Employee();
        
        $eArr = $employee->fetchAll()->toArray();
        
        $dateStart = '2014-01-01';
        
        $attendance = new Hra_Model_Attendance();
        
        for($i = 0; $i < 59; $i++){
            $date = date('Y-m-d', strtotime($dateStart."+".$i." day"));
            
            $w = date('w', strtotime($date));
            
            if($w != 0 && $w != 6){
                foreach ($eArr as $e){
                    $data = array(
                            'number'        => $e['number'],
                            'time'          => $date.' 08:55:12',
                            'type'          => 1,
                            'remark'        => '123',
                            'create_user'   => $user_id,
                            'create_time'   => $now,
                            'update_user'   => $user_id,
                            'update_time'   => $now
                    );
                    
                    $attendance->insert($data);
                    
                    $data = array(
                            'number'        => $e['number'],
                            'time'          => $date.' 18:01:33',
                            'type'          => 2,
                            'remark'        => '456',
                            'create_user'   => $user_id,
                            'create_time'   => $now,
                            'update_user'   => $user_id,
                            'update_time'   => $now
                    );
                    
                    $attendance->insert($data);
                }
            }
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    // 导入打卡记录
    public function importAction()
    {
        $result = array(
                'success'   => true,
                'info'      => '导入成功'
        );
        
        $request = $this->getRequest()->getParams();
        
        $type = isset($request['type']) ? $request['type'] : null;
        
        if(isset($_FILES['csv'])){
            $now = date('Y-m-d H:i:s');
            $user_session = new Zend_Session_Namespace('user');
            $user_id = $user_session->user_info['user_id'];
            
            $file = $_FILES['csv'];
            
            $file_extension = strrchr($file['name'], ".");
            
            $h = new Application_Model_Helpers();
            $tmp_file_name = $h->getMicrotimeStr().$file_extension;
            
            $savepath = "../temp/";
            $tmp_file_path = $savepath.$tmp_file_name;
            move_uploaded_file($file["tmp_name"], $tmp_file_path);
            
            if($type == 'attendance'){
                $attendance = new Hra_Model_Attendance();
                
                // 出勤
                $file = fopen($tmp_file_path, "r");
                
                $insertIds = array();
                
                while(! feof($file))
                {
                    $csv_data = fgetcsv($file);
                    
                    $number = isset($csv_data[0]) && $csv_data[0] != '' ? $csv_data[0] : null;
                    $clock_in = isset($csv_data[2]) && $csv_data[2] != '' ? $csv_data[1].' '.$csv_data[2] : null;
                    $clock_out = isset($csv_data[3]) && $csv_data[3] != '' ? $csv_data[1].' '.$csv_data[3] : null;
                    
                    if($number && ($clock_in || $clock_out)){
                        // 获取打卡日期：上班或下班打卡日期
                        $clock_date = $clock_in ? date('Y-m-d', strtotime($clock_in)) : date('Y-m-d', strtotime($clock_out));
                        
                        if($attendance->fetchAll("number = '".$number."' and clock_date = '".$clock_date."'")->count() == 0){
                            $clock_hours = 0;
                            
                            if($clock_in != '' && $clock_out != ''){
                                $clock_hours = (strtotime($clock_out) - strtotime($clock_in)) / 3600;
                            }
                            
                            $data = array(
                                    'number'        => $number,
                                    'clock_date'    => $clock_date,
                                    'clock_in'      => $clock_in,
                                    'clock_out'     => $clock_out,
                                    'clock_hours'   => $clock_hours,
                                    'create_user'   => $user_id,
                                    'create_time'   => $now,
                                    'update_user'   => $user_id,
                                    'update_time'   => $now
                            );
                            
                            try {
                                $insertId = $attendance->insert($data);
                            
                                array_push($insertIds, $insertId);
                            } catch (Exception $e) {
                                $result['success'] = false;
                                $result['info'] = $e->getMessage();
                            
                                echo Zend_Json::encode($result);
                            
                                exit;
                            }
                        }
                    }
                }
                
                fclose($file);
                
                // 刷新打卡类别setAttendanceType($id)
                /* foreach ($insertIds as $id){
                    $this->setAttendanceType($id);
                } */
            }
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    // 刷新员工年假库
    public function refreshvacationstorageAction()
    {
        $result = array(
                'success'   => true,
                'info'      => '刷新成功'
        );
        
        $request = $this->getRequest()->getParams();
        
        $employee_id = isset($request['employee_id']) ? $request['employee_id'] : null;
        $cover = isset($request['cover']) ? $request['cover'] : 0;
        
        $storage = new Hra_Model_Vacationstorage();
        
        $result = $storage->refreshStorage($employee_id, $cover);
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    /**
     * 获取年假库
     */
    public function getvacationstorageAction()
    {
        $request = $this->getRequest()->getParams();
        $option = isset($request['option']) ? $request['option'] : 'list';
        
        $condition = array(
                'key'   => isset($request['key']) ? $request['key'] : '',
                'type'  => $option
        );
        
        $storage = new Hra_Model_Vacationstorage();
        
        $data = $storage->getList($condition);
        
        if($option == 'csv'){
            $this->view->layout()->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);
            
            $h = new Application_Model_Helpers();
            $h->exportCsv($data, '假期库');
        }else{
            echo Zend_Json::encode($data);
        }
        
        exit;
    }
    
    /**
     * 获取出勤信息
     */
    public function getattendanceAction()
    {
        $request = $this->getRequest()->getParams();
        $option = isset($request['option']) ? $request['option'] : 'list';
        
        $condition = array(
                'key'               => isset($request['key']) ? $request['key'] : '',
                'only_on_and_off'   => isset($request['only_on_and_off']) ? ($request['only_on_and_off'] == 'true' ? 1: 0) : 0,
                'date_from'         => isset($request['date_from']) ? $request['date_from'] : date('Y-m-01'),
                'date_to'           => isset($request['date_to']) ? $request['date_to'] : date('Y-m-t'),
                'dept'              => isset($request['dept']) ? $request['dept'] : 0,
                'page'              => isset($request['page']) ? $request['page'] : 1,
                'limit'             => isset($request['limit']) ? $request['limit'] : 0,
                'type'              => $option
        );
        
        $attendance = new Hra_Model_Attendance();
        
        $data = $attendance->getAttendanceList($condition);
        
        if($option == 'csv'){
            $this->view->layout()->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);
        
            $h = new Application_Model_Helpers();
            $h->exportCsv($data, '打卡记录');
        }else{
            echo Zend_Json::encode($data);
        }
        
        exit;
    }
    
    /**
     * 获取考勤统计信息
     */
    public function getstatisticsAction()
    {
        $request = $this->getRequest()->getParams();
        $option = isset($request['option']) ? $request['option'] : 'list';
        
        $condition = array(
                'key'               => isset($request['key']) ? $request['key'] : null,
                'employment_type'   => isset($request['employment_type']) ? $request['employment_type'] : 1,
                'date_from'         => isset($request['date_from']) ? $request['date_from'] : date('Y-m-01'),
                'date_to'           => isset($request['date_to']) ? $request['date_to'] : date('Y-m-t'),
                'page'              => isset($request['page']) ? $request['page'] : 1,
                'limit'             => isset($request['limit']) ? $request['limit'] : 0,
                'type'              => $option
        );
        
        $attendance = new Hra_Model_Attendance();
        
        $data = $attendance->getStatisticsList($condition);
        
        if($option == 'csv'){
            $this->view->layout()->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);
        
            $h = new Application_Model_Helpers();
            $h->exportCsv($data, '考勤统计');
        }else{
            echo Zend_Json::encode($data);
        }
        
        exit;
    }
    
    /**
     * 初始化年份工作日设置
     */
    public function iniworkdayAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '编辑成功'
        );
        
        $request = $this->getRequest()->getParams();
        
        if(isset($request['year'])){
            $workday = new Hra_Model_Workday();
            
            $r = $workday->fetchAll("day like '".$request['year']."%'")->toArray();
            
            if(count($r) > 0){
                $result['success'] = false;
                $result['info'] = '年份设置错误，'.$request['year'].'年已有数据！';
            }else{
                $day = $request['year'].'-01-01';
                $end = $request['year'].'-12-31';
                
                $now = date('Y-m-d H:i:s');
                $user_session = new Zend_Session_Namespace('user');
                $user_id = $user_session->user_info['user_id'];
                
                while($day <= $end){
                    $weekIdx = date('w', strtotime($day));
                    $type = 1;
                    if($weekIdx == 0 || $weekIdx == 6){
                        $type = 2;
                    }
                    
                    $data = array(
                            'day' => $day,
                            'type' => $type,
                            'create_time'   => $now,
                            'create_user'   => $user_id,
                            'update_time'   => $now,
                            'update_user'   => $user_id
                    );
                    $workday->insert($data);
                    
                    $day = date('Y-m-d', strtotime($day."+1 day"));
                }
            }
        }else{
            $result['success'] = false;
            $result['info'] = '未设置年份';
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    /**
     * 获取参数设置
     */
    public function getparamAction()
    {
        $param = new Hra_Model_Attendanceparams();
        
        echo Zend_Json::encode($param->getData());
        
        exit;
    }
    
    public function editparamAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '编辑成功'
        );
    
        $request = $this->getRequest()->getParams();
    
        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user_id = $user_session->user_info['user_id'];
    
        $json = json_decode($request['json']);
    
        $updated = $json->updated;
        $inserted = $json->inserted;
        $deleted = $json->deleted;
    
        $param = new Hra_Model_Attendanceparams();
    
        if(count($updated) > 0){
            foreach ($updated as $val){
                if ($param->fetchAll("id != ".$val->id." and employment_type = ".$val->employment_type)->count() == 0) {
                    foreach ($updated as $val){
                        $data = array(
                                'employment_type'   => $val->employment_type,
                                'private'           => $val->private,
                                'vacation'          => $val->vacation,
                                'sick'              => $val->sick,
                                'marriage'          => $val->marriage,
                                'funeral'           => $val->funeral,
                                'maternity'         => $val->maternity,
                                'paternity'         => $val->paternity,
                                'other'             => $val->other,
                                'remark'            => $val->remark,
                                'update_time'       => $now,
                                'update_user'       => $user_id
                        );
                    
                        $where = "id = ".$val->id;
                    
                        try {
                            $param->update($data, $where);
                        } catch (Exception $e) {
                            $result['success'] = false;
                            $result['info'] = $e->getMessage();
                    
                            echo Zend_Json::encode($result);
                    
                            exit;
                        }
                    }
                }else{
                    $result['success'] = false;
                    $result['info'] = '用工形式数据已存在，请勿重复添加！';
    
                    echo Zend_Json::encode($result);
    
                    exit;
                }
            }
        }
    
        if(count($inserted) > 0){
            foreach ($inserted as $val){
                if ($param->fetchAll("employment_type = ".$val->employment_type)->count() == 0) {
                    $data = array(
                            'employment_type'   => $val->employment_type,
                            'private'           => $val->private,
                            'vacation'          => $val->vacation,
                            'sick'              => $val->sick,
                            'marriage'          => $val->marriage,
                            'funeral'           => $val->funeral,
                            'maternity'         => $val->maternity,
                            'paternity'         => $val->paternity,
                            'other'             => $val->other,
                            'remark'            => $val->remark,
                            'create_time'       => $now,
                            'create_user'       => $user_id,
                            'update_time'       => $now,
                            'update_user'       => $user_id
                    );
    
                    try {
                        $param->insert($data);
                    } catch (Exception $e) {
                        $result['success'] = false;
                        $result['info'] = $e->getMessage();
    
                        echo Zend_Json::encode($result);
    
                        exit;
                    }
                }else{
                    $result['success'] = false;
                    $result['info'] = '用工形式数据已存在，请勿重复添加！';
    
                    echo Zend_Json::encode($result);
    
                    exit;
                }
            }
        }
    
        if(count($deleted) > 0){
            foreach ($deleted as $val){
                try {
                    $param->delete("id = ".$val->id);
                } catch (Exception $e){
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
    
                    echo Zend_Json::encode($result);
    
                    exit;
                }
            }
        }
    
        echo Zend_Json::encode($result);
    
        exit;
    }
    
    /**
     * 获取工作日设置
     */
    public function getworkdayAction()
    {
        $request = $this->getRequest()->getParams();
        $option = isset($request['option']) ? $request['option'] : 'list';
        
        $condition = array(
                'type' => isset($request['type']) ? $request['type'] : 0,
                'date_from' => isset($request['date_from']) ? $request['date_from'] : date('Y-m-01'),
                'date_to' => isset($request['date_to']) ? $request['date_to'] : date('Y-m-t')
        );
        /* echo '<pre>';
        print_r($condition); */
        
        $workday = new Hra_Model_Workday();
        
        if($option == 'list'){
            echo Zend_Json::encode($workday->getWorkdayList($condition));
        }
        
        exit;
    }
    
    /**
     * 编辑工作日设置
     */
    public function editworkdayAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '编辑成功'
        );
        
        $request = $this->getRequest()->getParams();
        
        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user_id = $user_session->user_info['user_id'];
        
        $json = json_decode($request['json']);
        
        $updated = $json->updated;
        
        $workday = new Hra_Model_Workday();
        
        if(count($updated) > 0){
            foreach ($updated as $val){
                $data = array(
                        'type'          => $val->type,
                        'remark'        => $val->remark,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
        
                $where = "id = ".$val->id;
                
                try {
                    $workday->update($data, $where);
                } catch (Exception $e) {
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
        
                    echo Zend_Json::encode($result);
        
                    exit;
                }
            }
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    /**
     * 编辑假期库
     */
    public function editvacationstorageAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '编辑成功'
        );
        
        $request = $this->getRequest()->getParams();
        
        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user_id = $user_session->user_info['user_id'];
        
        $json = json_decode($request['json']);
        
        $updated = $json->updated;
        
        $storage = new Hra_Model_Vacationstorage();
        
        if(count($updated) > 0){
            foreach ($updated as $val){
                $data = array(
                        'qty'           => $val->qty,
                        'qty_used'      => $val->qty_used,
                        'remark'        => $val->remark,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
        
                $where = "id = ".$val->id;
                
                try {
                    $storage->update($data, $where);
                } catch (Exception $e) {
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
        
                    echo Zend_Json::encode($result);
        
                    exit;
                }
            }
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    /**
     * 获取工作时间设置
     */
    public function getworktimeAction()
    {
        $request = $this->getRequest()->getParams();
        $option = isset($request['option']) ? $request['option'] : 'list';
        
        $condition = array(
                'type' => isset($request['type']) ? $request['type'] : 0
        );
        
        $worktime = new Hra_Model_Worktime();
        
        if($option == 'list'){
            echo Zend_Json::encode($worktime->getWorktimeList($condition));
        }
        
        exit;
    }
    
    public function testAction()
    {
        $workday = new Hra_Model_Workday();
        
        $workdaySetting = $workday->getDayQtyBase(1, 0, '2014-07-01', '2014-07-31');
        
        echo '<pre>';print_r($workdaySetting);exit;
    }
    
    /**
     * 编辑工作时间设置
     */
    public function editworktimeAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '编辑成功'
        );
        
        $request = $this->getRequest()->getParams();
        
        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user_id = $user_session->user_info['user_id'];
        
        $json = json_decode($request['json']);
        
        $updated = $json->updated;
        $inserted = $json->inserted;
        $deleted = $json->deleted;
        
        $worktime = new Hra_Model_Worktime();
        
        if(count($updated) > 0){
            foreach ($updated as $val){
                if ($worktime->fetchAll("id != ".$val->id." and type = ".$val->type)->count() == 0) {
                    $data = array(
                            'type'                  => $val->type,
                            'active_from'           => $val->active_from,
                            'active_to'             => $val->active_to,
                            'work_from'             => str_replace('T', ' ', $val->work_from),
                            'work_to'               => str_replace('T', ' ', $val->work_to),
                            'rest_from'             => str_replace('T', ' ', $val->rest_from),
                            'rest_to'               => str_replace('T', ' ', $val->rest_to),
                            'limit_late'            => $val->limit_late,
                            'limit_leave'           => $val->limit_leave,
                            'limit_truancy_half'    => $val->limit_truancy_half,
                            'limit_truancy'          => $val->limit_truancy,
                            'remark'                => $val->remark,
                            'update_time'           => $now,
                            'update_user'           => $user_id
                    );
                    
                    $where = "id = ".$val->id;
                    
                    try {
                        $worktime->update($data, $where);
                    } catch (Exception $e) {
                        $result['success'] = false;
                        $result['info'] = $e->getMessage();
                    
                        echo Zend_Json::encode($result);
                    
                        exit;
                    }
                }else{
                    $result['success'] = false;
                    $result['info'] = '用工形式数据已存在，请勿重复添加！';
                    
                    echo Zend_Json::encode($result);
                    
                    exit;
                }
            }
        }
        
        if(count($inserted) > 0){
            foreach ($inserted as $val){
                if ($worktime->fetchAll("type = ".$val->type)->count() == 0) {
                    $data = array(
                            'type'                  => $val->type,
                            'active_from'           => $val->active_from,
                            'active_to'             => $val->active_to,
                            'work_from'             => $val->work_from,
                            'work_to'               => $val->work_to,
                            'rest_from'             => $val->rest_from,
                            'rest_to'               => $val->rest_to,
                            'limit_late'            => $val->limit_late,
                            'limit_leave'           => $val->limit_leave,
                            'limit_truancy_half'    => $val->limit_truancy_half,
                            'limit_truancy'         => $val->limit_truancy,
                            'remark'                => $val->remark,
                            'create_time'           => $now,
                            'create_user'           => $user_id,
                            'update_time'           => $now,
                            'update_user'           => $user_id
                    );
                    
                    try {
                        $worktime->insert($data);
                    } catch (Exception $e) {
                        $result['success'] = false;
                        $result['info'] = $e->getMessage();
                    
                        echo Zend_Json::encode($result);
                    
                        exit;
                    }
                }else{
                    $result['success'] = false;
                    $result['info'] = '用工形式数据已存在，请勿重复添加！';
                    
                    echo Zend_Json::encode($result);
                    
                    exit;
                }
            }
        }
        
        if(count($deleted) > 0){
            foreach ($deleted as $val){
                try {
                    $worktime->delete("id = ".$val->id);
                } catch (Exception $e){
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
    
                    echo Zend_Json::encode($result);
    
                    exit;
                }
            }
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
}