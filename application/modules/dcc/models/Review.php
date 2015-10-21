<?php
/**
 * 2013-8-7 下午16:37:30
 * @author mg.luo
 * @abstract
 */
class Dcc_Model_Review extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'review';
    protected $_primary = 'id';
    
    public function getReviewUserInfo($type, $file_id)
    {
        $reviewerInfo = array();
        
        $sql = $this->select()
                    ->from($this, array('plan_user' => new Zend_Db_Expr("GROUP_CONCAT(plan_user SEPARATOR ',')")))
                    ->where("type = '".$type."' and file_id = ".$file_id)
                    ->group("file_id");
        
        if($this->fetchAll($sql)->count() > 0){
            $data = $this->fetchRow($sql)->toArray();
             
            $reviewers = array_unique(explode(',', $data['plan_user']));
             
            $user = new Application_Model_User();
             
            foreach ($reviewers as $r){
                $userData = $user->fetchRow("employee_id = ".$r)->toArray();
                $user_id = $userData['id'];
            
                $userInfo = $user->getEmployeeInfoById($user_id);
                $user_email = $userInfo['email'];
            
                array_push($reviewerInfo, array('user_id' => $user_id, 'email' => $user_email));
            }
        }
        
        return $reviewerInfo;
    }

    public function getList($where, $type){
        $sql = $this->select()
                    ->from($this)
                    ->where($where)
                    ->where("type = ?", $type)
                    ->order(array('id'));

        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }

    public function getCurrent($type, $file_id) {
        $sql = $this->select()
                ->from($this)
                ->where("type='$type' and file_id = $file_id and finish_flg = 1")
                ->order(array('id'));
        $data = $this->fetchRow($sql);
        return $data;
    }

    public function getFirstNoReview($type, $file_id) {
        $sql = $this->select()
                ->from($this)
                ->where("type='$type' and file_id = $file_id and finish_flg = 0")
                ->order(array('id'))
                ->limit(1, 0);
        $data = $this->fetchRow($sql);
        return $data;
    }

    public function getBacklogList($user) {
        $sql = "select count(*) as count, type from oa_review t1  where (plan_user = $user or plan_user like '$user,%' or plan_user like '%,$user' or plan_user like '%,$user,%' ) and (actual_user is null or (actual_user != $user and actual_user not like '$user,%' and actual_user not like '%,$user' and actual_user not like '%,$user,%' )) and t1.id in (select min(id) from oa_review t2 where t2.file_id=t1.file_id and t2.type = t1.type and t2.finish_flg=0) group by type";
        $data = $this->getAdapter()->query($sql)->fetchAll();
        return $data;
    }
}