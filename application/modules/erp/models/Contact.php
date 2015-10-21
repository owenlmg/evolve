<?php
/**
 * 2013-9-11 下午10:47:14
 * @author x.li
 * @abstract 
 */
class Erp_Model_Contact extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'bpartner_contact';
    protected $_primary = 'id';
    
    public function getContact($condition)
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'bpartner'), "t1.partner_id = t2.id", array('customer_id' => 'id', 'customer_active' => 'active', 'customer_code' => 'code', 'customer_name' => new Zend_Db_Expr("case when t2.cname = '' then t2.ename else t2.cname end")))
                    ->where("t2.type = ".$condition['type']." and t2.code != ''")
                    ->order(array("t2.code", "t1.name"));
        if($condition['key']){
            $sql->where("t2.code like '%".$condition['key']."%' or t2.cname like '%".$condition['key']."%' or t2.ename like '%".$condition['key']."%' or t1.area_code like '%".$condition['key']."%' or t1.name like '%".$condition['key']."%' or t1.tel like '%".$condition['key']."%' or t1.address like '%".$condition['key']."%'");
        }
        
        if($condition['option'] != 'csv'){
            $sql->limitPage($condition['page'], $condition['limit']);
        }
        
        $total = $this->fetchAll($sql)->count();
        
        $data = $this->fetchAll($sql)->toArray();
        
        if($condition['option'] == 'csv'){
            $data_csv = array();
            $customerAdmin = false;
           
            if(Application_Model_User::checkPermissionByRoleName('客户管理员')
               || Application_Model_User::checkPermissionByRoleName('系统管理员')){
                $customerAdmin = true;
            }
            
            if ($customerAdmin) {
                $title = array(
                        'cnt'              => '#',
                        'customer_code'    => '客户代码',
                        'area_code'        => '客户地址简码',
                        'name'             => '收件人',
                        'tel'              => '联系电话',
                        'address'          => '发货地址',
                        'customer_name'    => '客户名称',
                        'post'             => '职位',
                        'fax'              => '传真',
                        'email'            => '邮箱',
                        'country'          => '国家',
                        'area'             => '省/州/县',
                        'area_city'        => '城市',
                        'zip_code'         => '邮编',
                        'person_liable'    => '责任人',
                        'remark'           => '备注'
                );
            } else {
                $title = array(
                        'cnt'              => '#',
                        'customer_code'    => '客户代码',
                        'area_code'        => '客户地址简码',
                        'name'             => '收件人',
                        'tel'              => '联系电话',
                        'address'          => '发货地址'
                );
            }
        
            array_push($data_csv, $title);
        
            $i = 0;
        
            foreach ($data as $d){
                $i++;
               
                if ($customerAdmin) {
                    $info = array(
                            'cnt'              => $i,
                            'customer_code'    => $d['customer_code'],
                            'area_code'        => $d['area_code'],
                            'name'             => $d['name'],
                            'tel'              => $d['tel'],
                            'address'          => $d['address'],
                            'customer_name'    => $d['customer_name'],
                            'post'             => $d['post'],
                            'fax'              => $d['fax'],
                            'email'            => $d['email'],
                            'country'          => $d['country'],
                            'area'             => $d['area'],
                            'area_city'        => $d['area_city'],
                            'zip_code'         => $d['zip_code'],
                            'person_liable'    => $d['person_liable'],
                            'remark'           => $d['remark']
                    );
                } else {
                    $info = array(
                            'cnt'              => $i,
                            'customer_code'    => $d['customer_code'],
                            'area_code'        => $d['area_code'],
                            'name'             => $d['name'],
                            'tel'              => $d['tel'],
                            'address'          => $d['address']
                    );
                }
        
                array_push($data_csv, $info);
            }
        
            return $data_csv;
        }
        
        return array('total' => $total, 'rows' => $data);
    }
    
    // 获取地址简码列表
    public function getAddressCodeList($type = 0, $partner_id = null)
    {
        $sql = $this->select()
                    ->from($this, array('code' => 'area_code', 'name' => new Zend_Db_Expr("concat(area_code, ' [', name, ']')")))
                    ->where("area_code != ''")
                    ->order("area_code");
        
        if ($partner_id) {
            $sql->where("partner_id = ".$partner_id);
        }
        
        $data = $this->fetchAll($sql)->toArray();
        
        if ($type == 0) {
            array_push($data, array('id' => 0, 'name' => '收货人'));
        }
        
        return $data;
    }
    
    public function getData($id)
    {
        return $this->fetchRow("id = ".$id)->toArray();
    }
    
    public function getDataByCode($code)
    {
        $data = array(
                'address'   => '',
                'name'      => '',
                'tel'       => '',
                'fax'       => ''
        );
        
        $res = $this->fetchAll("area_code = '".$code."'");
        
        if($res->count() > 0){
            $tmp = $res->toArray();
            
            $data = $tmp[0];
        }
        
        return $data;
    }
}