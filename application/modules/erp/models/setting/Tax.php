<?php
/**
 * 2013-7-6 下午10:32:26
 * @author x.li
 * @abstract 
 */
class Erp_Model_Setting_Tax extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'erp_setting_tax';
    protected $_primary = 'id';
    
    // 获取税率清单
    public function getList(){
        $sql = $this->select()
                    ->from($this, array('id', 'name'))
                    ->order(array('CONVERT( name USING gbk )'));
        $data = $this->fetchAll($sql)->toArray();
        
        for($i = 0; $i < count($data); $i++){
            $data[$i]['id'] = intval($data[$i]['id']);
        }
        
        return $data;
    }

    // 获取税表
    public function getData($id = null)
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t2.id = t1.create_user", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t3.id = t2.employee_id", array('creater' => 'cname'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t4.id = t1.update_user", array())
                    ->joinLeft(array('t5' => $this->_dbprefix.'employee'), "t5.id = t4.employee_id", array('updater' => 'cname'))
                    ->order(array('CONVERT( t1.name USING gbk )'));
        if($id){
            $sql->where("t1.id = ".$id);
            $data = $this->fetchAll($sql)->toArray();
            $rate = new Erp_Model_Setting_Taxrate();
            $data[0]['rate'] = $rate->getCurrentRate($data[0]['id']);
            
            return $data[0];
        }
        
        $data = $this->fetchAll($sql)->toArray();
        
        $tax = new Erp_Model_Setting_Taxrate();

        for($i = 0; $i < count($data); $i++){
            $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
            $data[$i]['update_time'] = strtotime($data[$i]['update_time']);
            $data[$i]['active'] = $data[$i]['active'] == 1 ? true : false;
            $data[$i]['default'] = $data[$i]['default'] == 1 ? true : false;
            
            $data[$i]['current_rate'] = $tax->getCurrentRate($data[$i]['id']);
        }

        return $data;
    }
}