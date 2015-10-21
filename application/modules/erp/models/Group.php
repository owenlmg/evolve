<?php
/**
 * 2013-9-11 下午10:47:57
 * @author x.li
 * @abstract 
 */
class Erp_Model_Group extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'bpartner_group';
    protected $_primary = 'id';
    
    /**
     * 获取组列表
     * @return unknown
     */
    public function getList($type){
        if ($type !== null) {
            $sql = $this->select()
                        ->from($this, array('id', 'name' => new Zend_Db_Expr("concat(code, ' ', case when name is null then '' else name end)")))
                        ->where("type = ".$type)
                        ->order(array('name'));
            $data = $this->fetchAll($sql)->toArray();
        }else{
            return array();
        }
    
        return $data;
    }
    
    /**
     * 获取组数据
     * @return Ambigous <boolean, multitype:>
     */
    public function getData($type)
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t2.id = t1.create_user", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t3.id = t2.employee_id", array('creater' => 'cname'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t4.id = t1.update_user", array())
                    ->joinLeft(array('t5' => $this->_dbprefix.'employee'), "t5.id = t4.employee_id", array('updater' => 'cname'))
                    ->where("t1.type = ".$type)
                    ->order(array('name'));
    
        $data = $this->fetchAll($sql)->toArray();
    
        for($i = 0; $i < count($data); $i++){
            $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
            $data[$i]['update_time'] = strtotime($data[$i]['update_time']);
            $data[$i]['active'] = $data[$i]['active'] == 1 ? true : false;
        }
    
        return $data;
    }
}