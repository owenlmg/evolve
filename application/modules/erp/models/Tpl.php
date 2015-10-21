<?php
/**
 * 2013-9-11 下午10:47:35
 * @author x.li
 * @abstract 
 */
class Erp_Model_Tpl extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'erp_tpl';
    protected $_primary = 'id';
    
    // 获取类别列表
    public function getTypeList(){
        $sql = $this->select()
                    ->from($this, array('name' => 'type', 'text' => 'type'))
                    ->group('type')
                    ->order(array('CONVERT( type USING gbk )'));
        $data = $this->fetchAll($sql)->toArray();
    
        return $data;
    }
    
    // 获取列表
    public function getList($type){
        $sql = $this->select()
                    ->from($this, array('id', 'name'))
                    ->order(array('CONVERT( name USING gbk )'));
        
        if($type){
            $sql->where("type = '".$type."'");
        }
        
        $data = $this->fetchAll($sql)->toArray();
    
        for($i = 0; $i < count($data); $i++){
            $data[$i]['id'] = intval($data[$i]['id']);
        }
    
        return $data;
    }
    
    // 获取所有数据
    public function getData($type)
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t2.id = t1.create_user", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t3.id = t2.employee_id", array('creater' => 'cname'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t4.id = t1.update_user", array())
                    ->joinLeft(array('t5' => $this->_dbprefix.'employee'), "t5.id = t4.employee_id", array('updater' => 'cname'))
                    ->order(array('CONVERT( t1.type USING gbk )', 'CONVERT( t1.name USING gbk )'));
    
        if($type){
            $sql->where("t1.type = '".$type."'");
        }
        
        $data = $this->fetchAll($sql)->toArray();
    
        for($i = 0; $i < count($data); $i++){
            $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
            $data[$i]['update_time'] = strtotime($data[$i]['update_time']);
        }
    
        return $data;
    }
}