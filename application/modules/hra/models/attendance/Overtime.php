<?php
/**
 * 2013-9-4 下午9:55:04
 * @author x.li
 * @abstract 
 */
class Hra_Model_Attendance_Overtime extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'attendance_overtime';
    protected $_primary = 'id';
    
    public function getExchangeQty($user_id)
    {
        $sql = $this->select()
                    ->from($this->_name)
                    ->where("apply_user = ".$user_id." and state = 3 and exchange = 0 and time_from >= '".date('Y-m-01', strtotime('-3 month', time()))."'")
                    ->order("time_from desc");
        
        $data = $this->fetchAll($sql)->toArray();
        
        return $data;
    }
    
    public function getOvertimeQtyByIds($ids)
    {
        $qty = 0;
        
        $where = '';
        
        $i = 0;
        
        foreach ($ids as $id){
            if($i == 0){
                $where = "id = ".$id;
            }else{
                $where .= " or id = ".$id;
            }
            
            $i++;
        }
        
        $sql = $this->select()
                    ->from($this, array('qty' => new Zend_Db_Expr("sum(qty)")))
                    ->where($where);
        $data = $this->fetchRow($sql)->toArray();
        
        return $data['qty'];
    }
    
    public function getOvertimeQty($user_id, $type, $date_from, $date_to)
    {
        $qty = 0;
    
        // 获取员工在日期范围内已发布的加班总数
        $sql = $this->select()
                    ->from($this->_name)
                    ->where("state = 3 and type = ".$type." and apply_user = ".$user_id." and time_from >= '".$date_from." 00:00:00' and time_to <= '".$date_to." 23:59:59'");
    
        $data = $this->fetchAll($sql)->toArray();
    
        foreach ($data as $d){
            $qty += $d['qty'];
        }
    
        return $qty;
    }
    
    public function checkTimeOverlap($apply_user, $time_from, $time_to, $filter_id = null)
    {
        $where = "apply_user = ".$apply_user." and (('".$time_from."' <= time_from and '".$time_to."' >= time_from) 
                 or ('".$time_from."' <= time_to and '".$time_to."' >= time_to) 
                 or ('".$time_from."' >= time_from and '".$time_to."' <= time_to))";
        
        if($filter_id){
            $where = "id != ".$filter_id." and ".$where;
        }
        
        if($this->fetchAll($where)->count() > 0){
            return true;
        }else{
            return false;
        }
    }
    
    // 获取请假记录表
    public function getData($condition = array(), $overtime_id = null)
    {
        $state = array(
                0 => '未审核', 
                1 => '拒绝', 
                2 => 'HR未审核', 
                3 => '已批准');
        
        $data = array();
        
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t2.id = t1.apply_user", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t3.id = t2.employee_id", array('manager_id', 'number', 'employment_type', 'apply_employee_id' => 't3.id', 'apply_user_name' => new Zend_Db_Expr("concat('[', t3.number, '] ', t3.cname)")))
                    ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t4.id = t1.review_user_1", array())
                    ->joinLeft(array('t5' => $this->_dbprefix.'employee'), "t5.id = t4.employee_id", array('review_employee_1_id' => 't5.id', 'review_user_1_name' => 't5.cname'))
                    ->joinLeft(array('t6' => $this->_dbprefix.'user'), "t6.id = t1.create_user", array())
                    ->joinLeft(array('t7' => $this->_dbprefix.'employee'), "t7.id = t6.employee_id", array('creater' => 't7.cname'))
                    ->joinLeft(array('t10' => $this->_dbprefix.'employee_dept'), "t10.id = t3.dept_id", array('dept' => 't10.name'))
                    ->joinLeft(array('t11' => $this->_dbprefix.'user'), "t11.id = t1.release_user", array())
                    ->joinLeft(array('t12' => $this->_dbprefix.'employee'), "t12.id = t11.employee_id", array('release_user_name' => 't12.cname'))
                    ->joinLeft(array('t13' => $this->_dbprefix.'user'), "t13.id = t1.update_user", array())
                    ->joinLeft(array('t14' => $this->_dbprefix.'employee'), "t14.id = t13.employee_id", array('updater' => 't14.cname'))
                    ->joinLeft(array('t15' => $this->_dbprefix.'user'), "t15.id = t1.review_user_2", array())
                    ->joinLeft(array('t16' => $this->_dbprefix.'employee'), "t16.id = t15.employee_id", array('review_employee_2_id' => 't16.id', 'review_user_2_name' => 't16.cname'))
                    ->order(array('t1.state', 't1.time_from desc', 't3.cname'));
        
        if($overtime_id){
            $sql->where("t1.id = ".$overtime_id);
            
            $data = $this->fetchRow($sql)->toArray();
            
            $data['state_info'] = $state[intval($data['state'])];
            
            return $data;
        }else{
            $sql->where("t1.state != 3 or (t1.time_from >= '".$condition['date_from']." 00:00:00' and t1.time_to <= '".$condition['date_to']." 23:59:59')");
            
            if($condition['key']){
                $sql->where("t3.ename like '%".$condition['key']."%' or t3.cname like '%".$condition['key']."%' or t3.number like '%".$condition['key']."%' or t1.remark like '%".$condition['key']."%' or t1.reason like '%".$condition['key']."%'");
            }
            
            $total = $this->fetchAll($sql)->count();
            
            if($condition['type'] != 'csv'){
                $sql->limitPage($condition['page'], $condition['limit']);
            }
            
            if($condition['state'] != ''){
                $stateConArr = explode(',', $condition['state']);
                
                $stateCon = "t1.state = ".$stateConArr[0];
                
                for($i = 1; $i < count($stateConArr); $i++){
                    $stateCon .= " or t1.state = ".$stateConArr[$i];
                }
                
                $sql->where($stateCon);
            }
            
            $data = $this->fetchAll($sql)->toArray();
            
            for($i = 0; $i < count($data); $i++){
                $data[$i]['time_from'] = strtotime($data[$i]['time_from']);
                $data[$i]['time_to'] = strtotime($data[$i]['time_to']);
                $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
                $data[$i]['update_time'] = strtotime($data[$i]['update_time']);
                $data[$i]['release_time'] = strtotime($data[$i]['release_time']);
                $data[$i]['review_time_1'] = $data[$i]['review_time_1'] != '' ? strtotime($data[$i]['review_time_1']) : null;
                $data[$i]['review_time_2'] = $data[$i]['review_time_2'] != '' ? strtotime($data[$i]['review_time_2']) : null;
                
                $data[$i]['state'] = intval($data[$i]['state']);
                
                $data[$i]['state_info'] = $state[$data[$i]['state']];
            }
            
            if($condition['type'] == 'csv'){
                $data_csv = array();
                
                $title = array(
                        'cnt'           => '#',
                        'state_info'    => '状态',
                        'apply_user'    => '申请人',
                        'time_from'     => '时间从',
                        'time_to'       => '时间至',
                        'reason'        => '事由',
                        'remark'        => '备注',
                        'review_user_1' => '部门主管',
                        'review_time_1' => '审核时间',
                        'review_user_2' => '总经理',
                        'review_time_2' => '审核时间',
                        'release_user'  => '发布人',
                        'release_time'  => '发布时间',
                        'create_user'   => '创建人',
                        'create_time'   => '创建时间',
                        'release_user'  => '发布人',
                        'release_time'  => '发布时间',
                        'update_user'   => '更新人',
                        'update_time'   => '更新时间'
                );
                
                array_push($data_csv, $title);
                
                $i = 0;
                
                foreach ($data as $d){
                    $i++;
                    
                    $info = array(
                            'cnt'           => $i,
                            'state'         => $d['state_info'],
                            'apply_user'    => $d['apply_user_name'],
                            'time_from'     => date('Y-m-d H:i:s', $d['time_from']),
                            'time_to'       => date('Y-m-d H:i:s', $d['time_to']),
                            'reason'        => $d['reason'],
                            'remark'        => $d['remark'],
                            'review_user_1' => $d['review_user_1_name'],
                            'review_time_1' => $d['review_time_1'] != null ? date('Y-m-d H:i:s', $d['review_time_1']) : null,
                            'release_user'  => $d['release_user_name'],
                            'release_time'  => $d['release_time'] != null ? date('Y-m-d H:i:s', $d['release_time']) : null,
                            'review_user_2' => $d['review_user_2_name'],
                            'review_time_2' => $d['review_time_2'] != null ? date('Y-m-d H:i:s', $d['review_time_2']) : null,
                            'release_user'  => $d['release_user_name'],
                            'release_time'  => date('Y-m-d H:i:s', $d['release_time']),
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
}