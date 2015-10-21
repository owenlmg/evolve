<?php
/**
 * 2014-8-5 20:33:12
 * @author      x.li
 * @abstract    会议
 */
class Res_Model_Meeting extends Application_Model_Db
{
    protected $_name = 'meeting';
    protected $_primary = 'id';
    
    public function checkMeetingConflict($room_id, $time_from, $time_to, $id = null)
    {
        $conflictQty = 0;
        
        $cond = "state = 0 and room_id = ".$room_id."
                and ((time_from >= '".$time_from."' and time_from <= '".$time_to."')
                or (time_to >= '".$time_from."' and time_to <= '".$time_to."')
                or (time_from >= '".$time_from."' and time_to <= '".$time_to."'))";
        
        if($id){
            $cond .= " and id != ".$id;
        }
        //echo $cond;exit;
        $conflictQty = $this->fetchAll($cond)->count();
        
        if($conflictQty > 0){
            return true;
        }else{
            return false;
        }
    }
    
    public function getConflictMembers($time_from, $time_to, $members, $id = null)
    {
        $conflictMembers = array();
        $membersArr = explode(',', $members);
        
        $cond = "state = 0
                and ((time_from >= '".$time_from."' and time_from <= '".$time_to."')
                or (time_to >= '".$time_from."' and time_to <= '".$time_to."')
                or (time_from >= '".$time_from."' and time_to <= '".$time_to."'))";
        
        if($id){
            $cond .= " and id != ".$id;
        }
        
        $res = $this->fetchAll($cond);
        
        if($res->count() > 0){
            $user = new Application_Model_User();
            $data = $res->toArray();
            
            foreach ($data as $d){
                $membersAddedArr = explode(',', $d['members']);
                
                foreach ($membersAddedArr as $m){
                    if(in_array($m, $membersArr) && !in_array($m, $conflictMembers)){
                        $memberInfo = $user->getEmployeeInfoById($m);
                        
                        array_push($conflictMembers, $memberInfo['cname'].': '.$d['time_from'].' - '.$d['time_to']);
                    }
                }
            }
        }
        
        return $conflictMembers;
    }
    
    public function getNewNum()
    {
        $num_pre = 'MOM'.date('ymd');
    
        $data = $this->fetchAll("number like '".$num_pre."%'", array('number desc'));
    
        if($data->count() == 0){
            $num = '01';
        }else{
            $last_item = $data->getRow(0)->toArray();
    
            $new_number = intval(substr($last_item['number'], 9)) + 1;
    
            $num = sprintf ("%02d", $new_number);
        }
    
        return $num_pre.$num;
    }
    
    public function getData($condition, $id = null)
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t1.create_user = t2.id", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t2.employee_id = t3.id", array('creater' => 'cname'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t1.update_user = t4.id", array())
                    ->joinLeft(array('t5' => $this->_dbprefix.'employee'), "t4.employee_id = t5.id", array('updater' => 'cname'))
                    ->joinLeft(array('t6' => $this->_dbprefix.'user'), "t1.moderator = t6.id", array())
                    ->joinLeft(array('t7' => $this->_dbprefix.'employee'), "t6.employee_id = t7.id", array('moderator_name' => 'cname'))
                    ->joinLeft(array('t8' => $this->_dbprefix.'meeting_room'), "t1.room_id = t8.id", array('room_name' => 'name'))
                    ->order("t1.time_from");
        if($id){
            $sql->where("t1.id = ".$id);
            
            return $this->fetchRow($sql)->toArray();
        }else{
            if($condition['state'] != null){
                $sql->where("t1.state = ".$condition['state']);
            }
            
            if($condition['date_from']){
                $sql->where("t1.time_from >= '".$condition['date_from']." 00:00:00'");
            }
            
            if($condition['date_to']){
                $sql->where("t1.time_to <= '".$condition['date_to']." 23:59:59'");
            }
            
            if($condition['key']){
                $sql->where("t1.cname like '%".$condition['key']."%' or t1.ename like '%".$condition['key']."%' or t1.subject like '%".$condition['key']."%' or t7.cname like '%".$condition['key']."%' or t7.ename like '%".$condition['key']."%' or t7.number like '%".$condition['key']."%' or t1.number like '%".$condition['key']."%'");
            }
            
            $total = $this->fetchAll($sql)->count();
            
            if($condition['type'] != 'csv'){
                $sql->limitPage($condition['page'], $condition['limit']);
            }
            
            $data = $this->fetchAll($sql)->toArray();
            
            $user = new Application_Model_User();
            
            for($i = 0; $i < count($data); $i++){
                $data[$i]['members_id'] = $data[$i]['members'];
                $memberArr = explode(',', $data[$i]['members']);
            
                $data[$i]['members'] = array();
            
                foreach ($memberArr as $m){
                    array_push($data[$i]['members'], intval($m));
                }
            
                // 根据当前时间刷新会议状态
                if($data[$i]['state'] == 0 && time() >= strtotime($data[$i]['time_from'])){
                    $data[$i]['state'] = 1;
                    $this->update(array('state' => 1), "id = ".$data[$i]['id']);
                }
            }
            
            if($condition['type'] == 'csv'){
                $data_csv = array();
            
                $title = array(
                        'cnt'               => '#',
                        'number'            => '会议编号',
                        'state_name'        => '状态',
                        'room_name'         => '会议室',
                        'subject'           => '主题',
                        'moderator_name'    => '主持人',
                        'mom'               => '会议纪要',
                        'time_from'         => '开始时间',
                        'time_to'           => '结束时间',
                        'members_cname'     => '参会人员',
                        'create_user'       => '创建人',
                        'create_time'       => '创建时间',
                        'update_user'       => '更新人',
                        'update_time'       => '更新时间'
                );
            
                array_push($data_csv, $title);
            
                $i = 0;
            
                foreach ($data as $d){
                    $i++;
            
                    $state = '开启';
            
                    if($d['state'] == 1){
                        $state = '结束';
                    }else if($d['state'] == 2){
                        $state = '取消';
                    }
            
                    $info = array(
                            'cnt'               => $i,
                            'number'            => $d['number'],
                            'state_name'        => $state,
                            'room_name'         => $d['room_name'],
                            'subject'           => $d['subject'],
                            'moderator_name'    => $d['moderator_name'],
                            'mom'               => $d['mom'],
                            'time_from'         => $d['time_from'],
                            'time_to'           => $d['time_to'],
                            'members_cname'      => $d['members_cname'],
                            'create_user'       => $d['creater'],
                            'create_time'       => $d['create_time'],
                            'update_user'       => $d['updater'],
                            'update_time'       => $d['update_time']
                    );
            
                    array_push($data_csv, $info);
                }
            
                return $data_csv;
            }
            
            return array('total' => $total, 'rows' => $data);
        }
    }
}