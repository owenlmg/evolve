<?php
/**
 * 2013-10-18 下午11:32:23
 * @author x.li
 * @abstract 
 */
class Hra_Model_Sealuse extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'seal_use';
    protected $_primary = 'id';
    
    public function getReviewList($review_user)
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'seal'), "t1.seal_id = t2.id", array('seal_name' => 'name'))
                    ->joinLeft(array('t3' => $this->_dbprefix.'user'), "t1.apply_user = t3.id", array())
                    ->joinLeft(array('t4' => $this->_dbprefix.'employee'), "t4.id = t3.employee_id", array('apply_user' => 'cname'))
                    ->joinLeft(array('t5' => $this->_dbprefix.'user'), "t1.review_user = t5.id", array())
                    ->joinLeft(array('t6' => $this->_dbprefix.'employee'), "t6.id = t5.employee_id", array('review_user' => 'cname'))
                    ->where("t1.review_user = ".$review_user." and review_state = 0")
                    ->order(array('t1.apply_time desc'));
        $data = $this->fetchAll($sql)->toArray();
        
        return $data;
    }
    
    public function getData($condition)
    {
        $where = "t1.apply_time >= '".$condition['date_from']." 00:00:00' and t1.apply_time <= '".$condition['date_to']." 23:59:59'";
        
        if($condition['key'] != ''){
            $where .= " and (t3.cname like '%".$condition['key']."%' or t3.ename like '%".$condition['key']."%' or t3.number like '%".$condition['key']."%')";
        }
        
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'seal'), "t1.seal_id = t2.id", array('seal_name' => 'name'))
                    ->joinLeft(array('t3' => $this->_dbprefix.'user'), "t1.apply_user = t3.id", array())
                    ->joinLeft(array('t4' => $this->_dbprefix.'employee'), "t4.id = t3.employee_id", array('apply_user_name' => 'cname'))
                    ->joinLeft(array('t5' => $this->_dbprefix.'user'), "t1.review_user = t5.id", array())
                    ->joinLeft(array('t6' => $this->_dbprefix.'employee'), "t6.id = t5.employee_id", array('review_user_name' => 'cname'))
                    ->order(array('t1.review_state', 't1.apply_time desc'));
        
        if(!Application_Model_User::checkPermissionByRoleName('印章管理员') && !Application_Model_User::checkPermissionByRoleName('系统管理员')){
            $user_session = new Zend_Session_Namespace('user');
            $user_id = $user_session->user_info['user_id'];
            
            $sql->where("apply_user = ".$user_id." or review_user = ".$user_id);
        }
        
        $total = $this->fetchAll($sql)->count();
        
        if($condition['option'] != 'csv'){
            $sql->limitPage($condition['page'], $condition['limit']);
        }
        
        $data = $this->fetchAll($sql)->toArray();

        for($i = 0; $i < count($data); $i++){
            switch ($data[$i]['state']){
                case 1:
                    $data[$i]['state'] = '新申请';
                    break;
                case 2:
                    $data[$i]['state'] = '批准';
                    break;
                case 3:
                    $data[$i]['state'] = '拒绝';
                    break;
                default:
                    $data[$i]['state'] = '无';
                    break;
            }
        }
        
        if($condition['option'] == 'csv'){
            $data_csv = array();
        
            $title = array(
                    'cnt'               => '#',
                    'state'             => '状态',
                    'seal_name'         => '印章',
                    'apply_reason'      => '事由',
                    'review_user'       => '审核人',
                    'review_time'       => '审核时间',
                    'review_opinion'    => '审核意见',
                    'apply_user'        => '申请人',
                    'apply_time'        => '申请时间',
                    'remark'            => '备注'
            );
        
            array_push($data_csv, $title);
        
            $i = 0;
        
            foreach ($data as $d){
                $i++;
        
                $info = array(
                        'cnt'               => $i,
                        'state'             => $d['state'],
                        'seal_name'         => $d['seal_name'],
                        'apply_reason'      => $d['apply_reason'],
                        'review_user'       => $d['review_user'],
                        'review_time'       => $d['review_time'],
                        'review_opinion'    => $d['review_opinion'],
                        'apply_user'        => $d['apply_user'],
                        'apply_time'        => $d['apply_time'],
                        'remark'            => $d['remark']
                );
        
                array_push($data_csv, $info);
            }
        
            return $data_csv;
        }

        return array('total' => $total, 'rows' => $data);
    }
}