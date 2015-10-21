<?php
/**
 * 2013-7-6 下午10:32:26
 * @author x.li
 * @abstract 
 */
class Hra_Model_Newscomment extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'news_comment';
    protected $_primary = 'id';

    public function getComment($news_id)
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t1.user_id = t2.id", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t2.employee_id = t3.id", array('user' => 'cname'))
                    ->where("t1.news_id = ".$news_id." and t1.public = 1 and deleted = 0")
                    ->order(array('t1.create_time'));
        
        $data = $this->fetchAll($sql)->toArray();

        for($i = 0; $i < count($data); $i++){
            $data[$i]['anonymity'] = $data[$i]['anonymity'] == 1 ? true : false;
            $data[$i]['public'] = $data[$i]['public'] == 1 ? true : false;
        }

        return $data;
    }
}