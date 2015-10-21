<?php
/**
 * 2013-8-5 下午16:01:30
 * @author mg.luo
 * @abstract 
 */
class Dcc_Model_Apply extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'doc_apply';
    protected $_primary = 'id';
    
    public function getCode($where){
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'employee'), "t1.create_user = t2.id", array('creater' => 'cname'))
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t1.update_user = t3.id", array('updater' => 'cname'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'doc_type'), "t1.prefix = t4.id", array('type_id' => 'id', 'type_code' => 'code'))
                    ->where($where)
                    ->order(array('code'));
        $data = $this->fetchAll($sql)->toArray();
        
        return $data;
    }
    
    /**
     * 获取文件编码 新文件申请用
     */
    public function getCodeForApp($where){
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'doc_type'), "t1.prefix = t2.id", array('type_id' => 'id', 'type_code' => 'code'))
                    ->joinLeft(array('t3' => $this->_dbprefix.'doc_files'), "t1.code = t3.code and (t3.state = 'Active' or t3.state = 'Obsolete')", array())
                    ->where($where." and t3.id is null")
                    ->order(array('code'));
        $data = $this->fetchAll($sql)->toArray();
        
        return $data;
    }
    
    /**
     * 获取文件编码 文件升版用
     */
    public function getCodeForDev($where){
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t4' => $this->_dbprefix.'doc_type'), "t1.prefix = t4.id", array('type_id' => 'id', 'type_code' => 'code'))
                    ->where($where)
                    ->order(array('code'));
        $data = $this->fetchAll($sql)->toArray();
        
        return $data;
    }
    
    public function getApply($like) {
        $sql = $this->select()
                    ->from($this, array('code'))
                    ->where('code like ?', $like)
                    ->order(array('code DESC'))
                    ->limit(1);
        $data = $this->fetchRow($sql);
        
        return $data;
    }
}