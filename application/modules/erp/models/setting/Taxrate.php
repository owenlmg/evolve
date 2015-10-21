<?php
/**
 * 2013-7-6 下午10:32:26
 * @author x.li
 * @abstract 
 */
class Erp_Model_Setting_Taxrate extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'erp_setting_tax_rate';
    protected $_primary = 'id';
    
    // 获取当前汇率
    public function getCurrentRate($tax_id, $date = null)
    {
        $rate = 0;
        
        $date = $date ? $date : date('Y-m-d');
        
        $res = $this->fetchAll("tax_id = ".$tax_id." and date <= '".$date."'", "date desc");
        
        if($res->count() > 0){
            $data = $res->toArray();
            
            $rate = $data[0]['rate'];
        }
        
        return $rate;
    }

    // 获取税率表
    public function getData($tax_id)
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t2.id = t1.create_user", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t3.id = t2.employee_id", array('creater' => 'cname'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t4.id = t1.update_user", array())
                    ->joinLeft(array('t5' => $this->_dbprefix.'employee'), "t5.id = t4.employee_id", array('updater' => 'cname'))
                    ->where("tax_id = ".$tax_id)
                    ->order(array('date desc'));
        
        $data = $this->fetchAll($sql)->toArray();

        for($i = 0; $i < count($data); $i++){
            $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
            $data[$i]['update_time'] = strtotime($data[$i]['update_time']);
        }

        return $data;
    }
}