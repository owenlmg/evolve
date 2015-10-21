<?php
/**
 * 2013-8-8 下午16:57:30
 * @author mg.luo
 * @abstract 
 */
class Dcc_Model_Upgrade extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'doc_upgrade';
    protected $_primary = 'file_id';
    
    public function getList($where){
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'employee'), "t1.create_user = t2.id", array('create_user' => 'cname'))
                    ->where($where)
                    ->order(array('create_time desc'));
        $data = $this->fetchAll($sql)->toArray();
        
        return $data;
    }
}