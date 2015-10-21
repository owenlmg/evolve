<?php
/**
 * 2013-8-20 下午8:11:21
 * @author x.li
 * @abstract 
 */
class Hra_Model_Vacationstorage extends Application_Model_Db
{
    protected $_name = 'attendance_vacation_storage';
    protected $_primary = 'id';
    
    /**
     * 根据工号获取员工剩余年假天数
     * @param unknown $number
     * @return number
     */
    public function getVacationQty($number)
    {
        $result = array(
                'qty'           => 0,
                'qty_used'      => 0,
                'in_year_qty'   => 0
        );
        
        $res = $this->fetchAll("number = '".$number."'", "in_year_qty desc");
        
        if($res->count() > 0){
            $data = $res->toArray();
            
            $result['qty_used'] = $data[0]['qty_used'];
            $result['qty'] = $data[0]['qty'];
            $result['in_year_qty'] = $data[0]['in_year_qty'];
            
            $employee = new Hra_Model_Employee();
            $user_id = $employee->getUserIdByNumber($number);
            
            $vacation = new Hra_Model_Attendance_Vacation();
            // 员工剩余可使用年假天数需要扣除已提交但未发布的请假申请
            $vRes = $vacation->fetchAll("apply_user = ".$user_id." and type = 2 and state != 3 and state != 1");
            
            if($vRes->count() > 0){
                $vData = $vRes->toArray();
                
                foreach ($vData as $v){
                    $result['qty_used'] += $v['qty'];
                }
            }
            
            $result['qty'] -= $result['qty_used'];
        }
        /* echo '<pre>';
        print_r($result);
        exit; */
        return $result;
    }
    
    /**
     * 刷新员工年假库
     * 1、根据员工用功形式获取员工的其实年假设置；
     * 2、
     * @param number $employee_id
     */
    public function refreshStorage($employee_id = null, $cover = 0)
    {
        $result = array(
                'success'   => true,
                'info'      => '刷新成功'
        );
        
        $qty = 0;
        
        $employee = new Hra_Model_Employee();
        $paramModel = new Hra_Model_Attendanceparams();
        // 获取所有用工形式的首年年假设置天数
        $vacationSet = $paramModel->getParamByType('vacation');
        
        $employeeCond = "";
        
        // 刷新个人或全部在职员工的最近一年的年假天数
        if($employee_id){
            $employeeCond = " and id = ".$employee_id;
        }
        
        $data = $employee->fetchAll("active = 1".$employeeCond)->toArray();
        
        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user_id = $user_session->user_info['user_id'];
        
        foreach ($data as $d){
            // 根据转正日期
            if($d['regularization_date'] != ''){
                // 起始年假天数（来自基础设置）
                $qtyStart = $vacationSet[$d['employment_type']];
                
                // 入司年数
                $in_year_qty = 0;
                
                // 2014-10-08之后入职的员工按入职日期开始计算，否则按转正日期计算
                if (strtotime($d['entry_date']) >= strtotime('2014-10-08')) {
                    $in_year_qty = $employee->getInCompanyYearQty($d['entry_date']);//入职日期
                }else{
                    $in_year_qty = $employee->getInCompanyYearQty($d['regularization_date']);//转正日期
                }
                
                $qty = 0;
                
                if($in_year_qty > 0){
                    // 根据入司年数计算员工最近一年应有几天年假
                    $qty = $qtyStart + $in_year_qty - 1;
                }
                
                if($qty >= 0){
                    // 当员工最近一年的年假已记录时，更新（用户选择是否覆盖）员工最近的年假天数，否则插入新数据
                    $oldDataRes = $this->fetchAll("number = '".$d['number']."' and in_year_qty = ".$in_year_qty);
                    
                    if($oldDataRes->count() > 0 && $cover == 1){
                        $oldData = $oldDataRes->toArray();
                        
                        // 当员工当前入司年份有数据时，更新记录
                        $t = array(
                                'qty'           => $qty,// 年假天数
                                'remark'        => '刷新更新',
                                'update_user'   => $user_id,
                                'update_time'   => $now
                        );
                    
                        try {
                            $this->update($t, "id = ".$oldData[0]['id']);
                        } catch (Exception $e) {
                            $result['success'] = false;
                            $result['info'] = $e->getMessage();
                    
                            return $result;
                        }
                    }else if($oldDataRes->count() == 0){
                        // 当员工当前入司年份没有数据时，插入新记录
                        $t = array(
                                'number'        => $d['number'],
                                'in_year_qty'   => $in_year_qty,
                                'qty'           => $qty,
                                'remark'        => '刷新生成',
                                'create_user'   => $user_id,
                                'create_time'   => $now,
                                'update_user'   => $user_id,
                                'update_time'   => $now
                        );
                        
                        try {
                            $this->insert($t);
                        } catch (Exception $e) {
                            $result['success'] = false;
                            $result['info'] = $e->getMessage();
                            
                            return $result;
                        }
                    }
                }
            }
        }
        
        return $result;
    }
    
    // 获取年假有效期限
    public function getLimitToDayQty($regularization_date, $in_year_qty)
    {
        $limit_to_year = date('Y', strtotime($regularization_date)) + 1 + $in_year_qty;
        
        $limit_to_date = $limit_to_year.'-'.date('m-d', strtotime($regularization_date));
        
        $qty_left = (strtotime($limit_to_date) - strtotime(date('Y-m-d'))) / (3600 * 24);
        
        $qty = $qty_left <= 0 ? 0 : $qty_left;
        
        $limit = array(
                'date'  => $limit_to_date,
                'qty'   => $qty
        );
        
        return $limit;
    }
    
    // 获取年假库
    public function getList($condition = array())
    {
        $data = array();
        
        $where = "t2.active = 1 and (t1.number like '%".$condition['key']."%' or t2.cname like '%".$condition['key']."%' or t2.ename like '%".$condition['key']."%')";
        
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'employee'), "t2.number = t1.number", array('employee_id' => 't2.id', 'cname', 'ename', 'entry_date', 'regularization_date'))
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee_dept'), "t3.id = t2.dept_id", array('dept' => 'name'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t1.create_user = t4.id", array())
                    ->joinLeft(array('t5' => $this->_dbprefix.'employee'), "t4.employee_id = t5.id", array('creater' => 'cname'))
                    ->joinLeft(array('t6' => $this->_dbprefix.'user'), "t1.update_user = t6.id", array())
                    ->joinLeft(array('t7' => $this->_dbprefix.'employee'), "t6.employee_id = t7.id", array('updater' => 'cname'))
                    ->joinLeft(array('t8' => $this->_dbprefix.'employee_type'), 't8.id = t2.employment_type', array('employee_type' => 'name'))
                    ->where($where)
                    ->order(array("t1.number", "t1.in_year_qty desc"));
        
        if(!Application_Model_User::checkPermissionByRoleName('系统管理员') && !Application_Model_User::checkPermissionByRoleName('人事管理员')){
            $user_session = new Zend_Session_Namespace('user');
            $user_number = $user_session->user_info['user_number'];
            
            $sql->where("t1.number = '".$user_number."'");
            $sql->limit(1);
        }
        
        $data = $this->fetchAll($sql)->toArray();
        
        $dataTmp = array();
        $numberArr = array();
        
        for($i = 0; $i < count($data); $i++){
            if (!in_array($data[$i]['number'], $numberArr)) {
                array_push($numberArr, $data[$i]['number']);
                
                $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
                $data[$i]['update_time'] = strtotime($data[$i]['update_time']);
                
                if($data[$i]['qty'] > 0){
                    $limit = array();
                
                    if (strtotime($data[$i]['entry_date']) >= strtotime('2014-10-08')) {
                        $limit = $this->getLimitToDayQty($data[$i]['entry_date'], $data[$i]['in_year_qty']);
                    }else{
                        $limit = $this->getLimitToDayQty($data[$i]['regularization_date'], $data[$i]['in_year_qty']);
                    }
                
                    $data[$i]['limit_to_qty'] = $limit['qty'];
                    $data[$i]['limit_to_date'] = $limit['date'];
                }else{
                    $data[$i]['limit_to_qty'] = '';
                }
                
                $data[$i]['qty_left'] = $data[$i]['qty'] - $data[$i]['qty_used'];
                
                array_push($dataTmp, $data[$i]);
            }
        }
        
        $data = $dataTmp;
        
        if($condition['type'] == 'csv'){
            $data_csv = array();
        
            $title = array(
                    'cnt'                   => '#',
                    'number'                => '工号',
                    'dept'                  => '部门',
                    'cname'                 => '中文名',
                    'ename'                 => '英文名',
                    'regularization_date'   => '转正日期',
                    'in_year_qty'           => '入司年数',
                    'qty'                   => '年假天数',
                    'qty_used'              => '已使用天数',
                    'qty_left'              => '剩余天数',
                    'limit_to_qty'          => '有效期限',
                    'remark'                => '备注',
                    'create_user'           => '创建人',
                    'create_time'           => '创建时间',
                    'update_user'           => '更新人',
                    'update_time'           => '更新时间'
            );
        
            array_push($data_csv, $title);
        
            $i = 0;
        
            foreach ($data as $d){
                $i++;
                
                $info = array(
                        'cnt'                   => $i,
                        'number'                => $d['number'],
                        'dept'                  => $d['dept'],
                        'cname'                 => $d['cname'],
                        'ename'                 => $d['ename'],
                        'regularization_date'   => $d['regularization_date'],
                        'in_year_qty'           => $d['in_year_qty'],
                        'qty'                   => $d['qty'],
                        'qty_used'              => $d['qty_used'],
                        'qty_left'              => $d['qty_left'],
                        'limit_to_qty'          => $d['limit_to_qty'],
                        'remark'                => $d['remark'],
                        'create_user'           => $d['create_user'],
                        'create_time'           => date('Y-m-d H:i:s', $d['create_time']),
                        'update_user'           => $d['update_user'],
                        'update_time'           => date('Y-m-d H:i:s', $d['update_time'])
                );
                
                array_push($data_csv, $info);
            }
            
            return $data_csv;
        }
        
        return $data;
    }
}