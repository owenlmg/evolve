<?php
/**
 * 2013-7-6 下午10:32:26
 * @author x.li
 * @abstract 
 */
class Erp_Model_Setting_Currency extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'erp_setting_currency';
    protected $_primary = 'id';
    
    public function getInfoByCode($code)
    {
        return $this->fetchRow("code = '".$code."'")->toArray();
    }
    
    // 获取本币
    public function getDefaultCurrency()
    {
        $defaultCurrency = '';
        
        $sql = $this->select()
                    ->from($this, array('code'))
                    ->where("`default` = 1");
        
        $data = $this->fetchAll($sql)->toArray();
        
        if(count($data) > 0){
            $defaultCurrency = $data[0]['code'];
        }
        
        return $defaultCurrency;
    }
    
    // 获取币种清单
    public function getList(){
        $sql = $this->select()
                    ->from($this, array('id', 'name' => 'code', 'text' => 'code', 'default', 'symbol'))
                    ->order('code');
        $data = $this->fetchAll($sql)->toArray();
        
        $rate = new Erp_Model_Setting_Currencyrate();
        
        for($i = 0; $i < count($data); $i++){
            $data[$i]['id'] = intval($data[$i]['id']);
            
            $data[$i]['rate'] = $rate->getCurrentRate($data[$i]['id']);
        }
        
        return $data;
    }

    // 获取职位表
    public function getData()
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t2.id = t1.create_user", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t3.id = t2.employee_id", array('creater' => 'cname'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t4.id = t1.update_user", array())
                    ->joinLeft(array('t5' => $this->_dbprefix.'employee'), "t5.id = t4.employee_id", array('updater' => 'cname'))
                    ->order(array('code'));
        
        $data = $this->fetchAll($sql)->toArray();
        
        $rate = new Erp_Model_Setting_Currencyrate();

        for($i = 0; $i < count($data); $i++){
            $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
            $data[$i]['update_time'] = strtotime($data[$i]['update_time']);
            $data[$i]['active'] = $data[$i]['active'] == 1 ? true : false;
            $data[$i]['default'] = $data[$i]['default'] == 1 ? true : false;
            
            $data[$i]['current_rate'] = $rate->getCurrentRate($data[$i]['id']);
        }

        return $data;
    }
}