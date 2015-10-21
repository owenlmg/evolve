<?php
/**
 * 2013-10-14 下午9:50:42
 * @author x.li
 * @abstract 
 */
class Application_Model_Log_Msgsend extends Application_Model_Db
{
    protected $_name = 'log_msg_send';
    protected $_primary = 'id';
    
    /**
     * 获取数据
     * @param unknown $condition
     * @return multitype:number Ambigous <number, multitype:>
     */
    public function getList($type, $page, $limit, $condition = array())
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->join(array('t2' => $this->_dbprefix.'log_msg'), "t1.msg_id = t2.id", array('msg_id' => 'id', 'title', 'priority', 'content', 'remark', 'active', 'create_time', 'update_time'))
                    ->joinLeft(array('t3' => $this->_dbprefix.'user'), "t2.create_user = t3.id", array())
                    ->joinLeft(array('t4' => $this->_dbprefix.'employee'), "t3.employee_id = t4.id", array('creater' => 'cname'))
                    ->joinLeft(array('t5' => $this->_dbprefix.'user'), "t2.update_user = t5.id", array())
                    ->joinLeft(array('t6' => $this->_dbprefix.'employee'), "t5.employee_id = t6.id", array('updater' => 'cname'))
                    ->order(array('t1.view', 't2.create_time desc'));
        
        if(isset($condition['user_id'])){
            $sql = $sql->where("t1.user_id = ".$condition['user_id']);
        }
        
        if($type != 'shortlist'){
            if(isset($condition['key']) && $condition['key']){
                $sql->where("t4.cname like '%".$condition['key']."%' or t4.ename like '%".$condition['key']."%' or t2.title like '%".$condition['key']."%' or t2.content like '%".$condition['key']."%' or t2.remark like '%".$condition['key']."%'");
            }
            
            if(isset($condition['date_from']) && $condition['date_from']){
                $sql->where("t2.create_time >= '".$condition['date_from']." 00:00:00'");
            }
            
            if(isset($condition['date_to']) && $condition['date_to']){
                $sql->where("t2.create_time <= '".$condition['date_to']." 23:59:59'");
            }
        }
        
        $total = $this->fetchAll($sql)->count();
        
        $sql = $sql->order(array('t2.create_time desc'))->limitPage($page, $limit);
        
        $data = $this->fetchAll($sql)->toArray();
        
        for($i = 0; $i < count($data); $i++){
            $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
            $data[$i]['update_time'] = strtotime($data[$i]['update_time']);
            $data[$i]['active'] = $data[$i]['active'] == 1 ? true : false;
            $data[$i]['email'] = $data[$i]['email'] == 1 ? true : false;
            $data[$i]['view'] = $data[$i]['view'] == 1 ? true : false;
        }
        
        return array('total' => $total, 'rows' => $data);
    }
}