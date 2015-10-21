<?php
/**
 * 2013-9-11 下午10:46:06
 * @author x.li
 * @abstract 
 */
class Erp_Model_Partner extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'bpartner';
    protected $_primary = 'id';
    
    public function getInfoById($id)
    {
        $info = array();
        
        $sql = $this->select()
                    ->from($this)
                    ->where("id = ".$id);
        
        $info = $this->fetchRow($sql)->toArray();
        
        return $info;
    }
    
    public function getTaxInfo($id)
    {
        $data = array();
        
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array())
                    ->joinLeft(array('t2' => $this->_dbprefix.'erp_setting_tax'), "t1.tax_id = t2.id", array('t2.id', 'name'))
                    ->where("t1.id = ".$id);
        
        $data = $this->fetchRow($sql)->toArray();
        
        $rate = new Erp_Model_Setting_Taxrate();
        $data['rate'] = $rate->getCurrentRate($data['id']);
        
        return $data;
    }
    
    /**
     * 获取供应商列表
     * @param number $type
     * @return unknown
     */
    public function getList($type = 0)
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array(
                            'id', 
                            'tax_id', 
                            'name' => new Zend_Db_Expr("concat(t1.code, ' ', case when t1.cname = '' then t1.ename else t1.cname end)"), 
                            'currency_id' => 'bank_currency'))
                    ->joinLeft(array('t2' => $this->_dbprefix.'erp_setting_currency'), "t1.bank_currency = t2.id", array(
                            'currency' => 't2.code'))
                    ->joinLeft(array('t3' => $this->_dbprefix.'erp_setting_tax'), "t1.tax_id = t3.id", array(
                            'tax_name' => 'name'
                    ))
                    ->where("t1.type = ".$type)
                    ->order("t1.code");
        
        $data = $this->fetchAll($sql)->toArray();
        
        $taxRateModel = new Erp_Model_Setting_Taxrate();
        
        for ($i = 0; $i < count($data); $i++){
            $data[$i]['tax_rate'] = $taxRateModel->getCurrentRate($data[$i]['tax_id']);
        }
    
        return $data;
    }
    
    /**
     * 获取全部供应商
     * @param unknown $condition
     * @return multitype:number Ambigous <string, multitype:>
     */
    public function getData($condition = array())
    {
        $group = json_decode($condition['group']);
        
        $where_group = "";
        if(count($group) > 0){
            $where_group = " and (";
        
            for($i = 0; $i < count($group); $i++){
                if($i == 0){
                    $where_group .= "t1.group_id = ".$group[$i];
                }else{
                    $where_group .= " or t1.group_id = ".$group[$i];
                }
            }
        
            $where_group .= ")";
        }
        
        $where = "t1.type = ".$condition['type']." and (t1.code like '%".$condition['key']."%' or t1.cname like '%".$condition['key']."%' or t1.ename like '%".$condition['key']."%')".$where_group;
        
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->where($where);
        
        $total = $this->fetchAll($sql)->count();
        
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t1.create_user = t2.id", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t2.employee_id = t3.id", array('creater' => 'cname'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t1.update_user = t4.id", array())
                    ->joinLeft(array('t5' => $this->_dbprefix.'employee'), "t4.employee_id = t5.id", array('updater' => 'cname'))
                    ->joinLeft(array('t6' => $this->_dbprefix.'bpartner_group'), "t1.group_id = t6.id", array('group_name' => new Zend_Db_Expr("case when t6.name is null then '' else t6.name end")))
                    ->joinLeft(array('t7' => $this->_dbprefix.'erp_setting_currency'), "t1.bank_currency = t7.id", array('bank_currency_name' => 'code'))
                    ->joinLeft(array('t8' => $this->_dbprefix.'bpartner_payment'), "t1.bank_payment_days = t8.id", array('bank_payment_days_name' => 'name'))
                    ->joinLeft(array('t9' => $this->_dbprefix.'erp_setting_tax'), "t1.tax_id = t9.id", array('tax_name' => 'name'))
                    ->where($where);
        
        $total = $this->fetchAll($sql)->count();
        
        $sql->order(array('t1.code'));
        
        if($condition['export'] == 0){
            $sql->limitPage($condition['page'], $condition['limit']);
        }
        
        $data = $this->fetchAll($sql)->toArray();
        
        for($i = 0; $i < count($data); $i++){
            $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
            $data[$i]['update_time'] = strtotime($data[$i]['update_time']);
            $data[$i]['active'] = intval($data[$i]['active']);
            $data[$i]['type'] = $data[$i]['type'] == 1 ? '客户' : '供应商';
        }
        
        if($condition['export']){
            $data_csv = array();
            
            $title = array(
                    'cnt'                       => '#',
                    'type'                      => '类别',
                    'code'                      => '代码',
                    'cname'                     => '中文名称',
                    'ename'                     => '英文名称',
                    'group_name'                => '组',
                    'rsm'                       => 'RSM',
                    'terminal_customer'         => '终端客户',
                    'suffix'                    => '后缀',
                    'bank_payment_days_name'    => '付款方式',
                    'bank_currency_name'        => '币种',
                    'tax_name'                  => '税率',
                    'bank_country'              => '银行国家',
                    'bank_type'                 => '开户行',
                    'bank_account'              => '账号',
                    'tax_num'                   => '税号',
                    'bank_name'                 => '开户名称',
                    'bank_remark'               => '付款备注',
                    'remark'                    => '备注'
            );
            
            array_push($data_csv, $title);
            
            $i = 0;
            
            foreach ($data as $d){
                $i++;
            
                $info = array(
                        'cnt'                       => $i,
                        'type'                      => $d['type'],
                        'code'                      => $d['code'],
                        'cname'                     => $d['cname'],
                        'ename'                     => $d['ename'],
                        'group_name'                => $d['group_name'],
                        'rsm'                       => $d['rsm'],
                        'terminal_customer'         => $d['terminal_customer'],
                        'suffix'                    => $d['suffix'],
                        'bank_payment_days_name'    => $d['bank_payment_days_name'],
                        'bank_currency_name'        => $d['bank_currency_name'],
                        'tax_name'                  => $d['tax_name'],
                        'bank_country'              => $d['bank_country'],
                        'bank_type'                 => $d['bank_type'],
                        'bank_account'              => substr($d['bank_account'], 0, 5).' '.substr($d['bank_account'], 5),
                        'tax_num'                   => substr($d['tax_num'], 0, 5).' '.substr($d['tax_num'], 5),
                        'bank_name'                 => $d['bank_name'],
                        'bank_remark'               => $d['bank_remark'],
                        'remark'                    => $d['remark']
                );
            
                array_push($data_csv, $info);
            }
            
            return $data_csv;
        }
        
        return array('total' => $total, 'rows' => $data);
    }
}