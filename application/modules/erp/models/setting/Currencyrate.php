<?php
/**
 * 2013-7-6 下午10:32:26
 * @author x.li
 * @abstract 
 */
class Erp_Model_Setting_Currencyrate extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'erp_setting_currency_rate';
    protected $_primary = 'id';
    
    public function getRateByCode($code, $date = null)
    {
        $rate = 1;
        
        // 获取汇率条件中日期为空时，以当前日期为准（获取最近汇率）
        if(!$date){
            $date = date('Y-m-d');
        }
        
        $currency = new Erp_Model_Setting_Currency();
        
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'erp_setting_currency'), "t2.id = t1.currency_id", array())
                    ->where("t2.code = '".$code."' and t1.date <= '".$date."'");
        
        $data = $this->fetchAll($sql)->toArray();
        
        if(count($data) > 0){
            $rate = $data[0]['rate'];
        }
        
        return $rate;
    }
    
    // 获取当前汇率
    public function getCurrentRate($currency_id)
    {
        $rate = 1;
        
        $res = $this->fetchAll("currency_id = ".$currency_id." and date <= '".date('Y-m-d')."'", "date desc");
        
        if($res->count() > 0){
            $data = $res->toArray();
            
            $rate = $data[0]['rate'];
        }
        
        return $rate;
    }
    
    // 获取当前汇率
    public function getCurrentRateByCode($code)
    {
        $rate = 1;
        
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array('rate'))
                    ->joinLeft(array('t2' => $this->_dbprefix.'erp_setting_currency'), "t1.currency_id = t2.id", array())
                    ->where("t2.code = '".$code."' and t1.date <= '".date('Y-m-d')."'")
                    ->order('t1.date desc');
        
        $res = $this->fetchAll($sql);
        
        if($res->count() > 0){
            $data = $res->toArray();
            
            $rate = $data[0]['rate'];
        }
        
        return $rate;
    }

    // 获取汇率表
    public function getData($currency_id)
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t2.id = t1.create_user", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t3.id = t2.employee_id", array('creater' => 'cname'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t4.id = t1.update_user", array())
                    ->joinLeft(array('t5' => $this->_dbprefix.'employee'), "t5.id = t4.employee_id", array('updater' => 'cname'))
                    ->where("currency_id = ".$currency_id)
                    ->order(array('date desc'));
        
        $data = $this->fetchAll($sql)->toArray();

        for($i = 0; $i < count($data); $i++){
            $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
            $data[$i]['update_time'] = strtotime($data[$i]['update_time']);
        }

        return $data;
    }
}