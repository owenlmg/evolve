<?php
/**
 * 2013-10-12 下午4:48:38
 * @author x.li
 * @abstract 
 */
class Application_Model_Log_Operate extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'log_operate';
    protected $_primary = 'id';
    
    public function getLogByOperateAndTargetId($operate, $target_id)
    {
        $data = array();
        
        $sql = $this->select()
                    ->from($this, array('time', 'content'))
                    ->where("operate = '".$operate."' and target_id = ".$target_id)
                    ->order("time DESC");
        
        $data = $this->fetchAll($sql)->toArray();
        
        return $data;
    }
    
    /**
     * 获取类别列表
     * @return unknown
     */
    public function getType()
    {
        $sql = $this->select()
                    ->from($this, array('type' => 'operate'))
                    ->group(array('operate'))
                    ->order(array('operate'));
        
        $data = $this->fetchAll($sql)->toArray();
        
        return $data;
    }
    
    /**
     * 获取数据
     * @param unknown $condition
     * @return multitype:number Ambigous <number, multitype:>
     */
    public function getData($condition)
    {
        $where = "t1.time >= '".$condition['date_from']." 00:00:00' and t1.time <= '".$condition['date_to']." 23:59:59'";
        
        $operate = json_decode($condition['operate']);
        
        if(count($operate) > 0){
            $where .= " and (";
        
            for($i = 0; $i < count($operate); $i++){
                if($i == 0){
                    $where .= "t1.operate = '".$operate[$i]."'";
                }else{
                    $where .= " or t1.operate = '".$operate[$i]."'";
                }
            }
        
            $where .= ")";
        }
        
        if($condition['key'] != ''){
            $where .= " and (t3.cname like '%".$condition['key']."%' or t3.ename like '%".$condition['key']."%' or t3.number like '%".$condition['key']."%')";
        }
        
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t1.user_id = t2.id", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t2.employee_id = t3.id", array('user' => new Zend_Db_Expr("concat('[', t3.number, '] ', t3.cname)")))
                    ->order(array('t1.time desc'))
                    ->where($where);
        
        $total = $this->fetchAll($sql)->count();
        
        if($condition['option'] != 'csv'){
            $sql->limitPage($condition['page'], $condition['limit']);
        }
        
        $data = $this->fetchAll($sql)->toArray();

        for($i = 0; $i < count($data); $i++){
            $data[$i]['time'] = strtotime($data[$i]['time']);
        }
        
        if($condition['option'] == 'csv'){
            $data_csv = array();
        
            $title = array(
                    'cnt'           => '#',
                    'time'          => '时间',
                    'user'          => '用户',
                    'operate'       => '操作',
                    'target'        => '目标地址',
                    'ip'            => 'IP',
                    'computer_name' => '计算机名',
                    'remark'        => '备注'
            );
        
            array_push($data_csv, $title);
        
            $i = 0;
        
            foreach ($data as $d){
                $i++;
        
                $info = array(
                        'cnt'           => $i,
                        'time'          => date('Y-m-d H:i:s', $d['time']),
                        'user'          => $d['user'],
                        'operate'       => $d['operate'],
                        'target'        => $d['target'],
                        'ip'            => $d['ip'],
                        'computer_name' => $d['computer_name'],
                        'remark'        => $d['remark']
                );
        
                array_push($data_csv, $info);
            }
        
            return $data_csv;
        }

        return array('total' => $total, 'rows' => $data);
    }
}