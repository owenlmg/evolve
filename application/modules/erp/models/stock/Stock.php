<?php
/**
 * 2013-7-6 下午10:32:26
 * @author x.li
 * @abstract 
 */
class Erp_Model_Stock_Stock extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'erp_stock';
    protected $_primary = 'id';
    
    // 获取库存交易类别
    public function getTypeList()
    {
        $sql = $this->select()
                    ->from($this->_name, array('name' => 'doc_type', 'text' => 'doc_type'))
                    ->group("doc_type")
                    ->order("CONVERT( doc_type USING gbk )");
        
        $data = $this->fetchAll($sql)->toArray();
        
        return $data;
    }
    
    /**
     * 获取存货平均价格
     * @param string $code
     * @param array $warehouse_code
     * @return number
     */
    public function getPrice($code, $warehouse_code = null)
    {
        $price = 0;
        
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array('price' => new Zend_Db_Expr('sum(t1.total) / sum(t1.qty)')))
                    ->join(array('t2' => $this->_dbprefix.'erp_warehouse'), "t1.warehouse_code = t2.code", array())
                    ->where("t1.code = '".$code."' and t2.in_stock = 1");
        
        if($warehouse_code){
            $sql->where("t1.warehouse_code = '".$warehouse_code."'");
        }
        
        $data = $this->fetchAll($sql)->toArray();
        
        if(count($data) > 0){
            $price = round($data[0]['price'], 4);
        }
        
        if($price == 0){
            // 当库存均价为0时，获取价格清单最低价
            $pricelist = new Erp_Model_Warehouse_Pricelist();
            
            $priceData = $pricelist->getMultiPrice($code);
            
            $price = $priceData['low'];
        }
        
        return $price;
    }
    
    // 获取库存数量
    public function getStockQty($code, $warehouse_code = null)
    {
        $qty = array(
                'total'     => 0,
                'details'   => array()
        );
        
        $sql = $this->select()
                    ->from($this->_name, array('warehouse_code', 'qty' => new Zend_Db_Expr("sum(qty)")))
                    ->where("code = '".$code."'")
                    ->group("warehouse_code")
                    ->order("warehouse_code");
        $warehouseCond = "";
        
        for($i = 0; $i < count($warehouse_code); $i++){
            if($i == 0){
                $warehouseCond = "warehouse_code = '".$warehouse_code[$i]."'";
            }else{
                $warehouseCond .= " or warehouse_code = '".$warehouse_code[$i]."'";
            }
        }
        
        if($warehouseCond != ""){
            $sql->where($warehouseCond);
        }
        
        $data = $this->fetchAll($sql)->toArray();
        
        foreach ($data as $d){
            $qty['total'] += $d['qty'];
            
            array_push($qty['details'], array('warehouse_code' => $d['warehouse_code'], 'qty' => $d['qty']));
        }
        
        return $qty;
    }
    
    /*
     * 根据值获取数组索引
     */
    public function getArrayIndex($array, $val)
    {
        for($i = 0; $i < count($array); $i++){
            if($array[$i] == $val){
                return $i;
            }
        }
        
        return false;
    }
    
    /**
     * 数组去重，重新索引
     * @param unknown $array
     * @return multitype:
     */
    public function noRepeatArr($array)
    {
        $result = array();
        
        foreach ($array as $a){
            if(!in_array($a, $result)){
                array_push($result, $a);
            }
        }
        
        return $result;
    }
    
    /*
     * 重组库存信息
     */
    public function reGroupStock($warehouse, $warehouse_qty)
    {
        $result = array();
        
        $name = array();
        $qty = array();
        
        $warehouseArr = $this->noRepeatArr($warehouse);
        $warehouseModel = new Erp_Model_Warehouse_Warehouse();
        
        for($i = 0; $i < count($warehouseArr); $i++){
            array_push($name, '');
            array_push($qty, 0);
        }
        
        for($i = 0; $i < count($warehouse); $i++){
            $index = $this->getArrayIndex($warehouseArr, $warehouse[$i]);
            
            $name[$index] = $warehouse[$i];
            
            $qtyTmp = isset($warehouse_qty[$i]) ? $warehouse_qty[$i] : 0;
            
            $qty[$index] += $qtyTmp;
        }
        
        for($i = 0; $i < count($name); $i++){
            if($qty[$i] > 0){
                $warehouseData = $warehouseModel->getInfoByCode($name[$i]);
                
                array_push($result, array(
                    'name'  => $warehouseData['name'],
                    'code'  => $name[$i],
                    'qty'   => $qty[$i]
                ));
                
                //array_push($result, $name[$i].' '.$warehouseData['name'].' [<b>'.$qty[$i].'</b>]');
            }
        }
        
        sort($result);
        
        return $result;
        
        //return implode(', ', $result);
    }
    
    public function getQtyByStockCode($code, $warehouse_code)
    {
        $qty = 0;
        
        $sql = $this->select()
                    ->from($this, array('qty' => new Zend_Db_Expr("sum(qty)")))
                    ->where("code = '".$code."' and warehouse_code = '".$warehouse_code."'");
        
        $data = $this->fetchRow($sql)->toArray();
        
        $qty = $data['qty'];
        
        return $qty;
    }

    // 获取库存
    public function getSearchData($condition)
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array('code', 'qty' => new Zend_Db_Expr('sum(qty)')))
                    ->joinLeft(array('t2' => $this->_dbprefix.'product_materiel'), "t2.code = t1.code", array('name', 'description'))
                    ->joinLeft(array('t3' => $this->_dbprefix.'product_type'), "t3.id = t2.type", array('type' => 'name'))
                    //->where("t2.state = 'Active'")
                    ->group(array('t1.code'))
                    ->order("t1.code");
        
        // 关键字
        if($condition['key']){
            $sql->where("t1.code like '%".$condition['key']."%' or t3.name like '%".$condition['key']."%' or t2.name like '%".$condition['key']."%' or t2.description like '%".$condition['key']."%'");
        }
        
        $total = $this->fetchAll($sql)->count();
        
        if($condition['type'] != 'csv'){
            $sql->limitPage($condition['page'], $condition['limit']);
        }
        
        $data = $this->fetchAll($sql)->toArray();
        $warehouseModel = new Erp_Model_Warehouse_Warehouse();
        $warehouseList = $warehouseModel->getList();
        
        for($i = 0; $i < count($data); $i++){
            foreach ($warehouseList as $w){
                $qty = $this->getQtyByStockCode($data[$i]['code'], $w['code']);
                
                $data[$i]['w_'.$w['code']] = $qty > 0 ? $qty : 0;
            }
            
            $data[$i]['description'] = str_replace("'", "\'", $data[$i]['description']);
        }
        
        if($condition['type'] == 'csv'){
            $data_csv = array();
            
            $warehouseModel = new Erp_Model_Warehouse_Warehouse();
            
            $warehouseArr = $warehouseModel->getList();
        
            $title = array(
                    'cnt'               => '#',
                    'code'              => '物料代码',
                    'qty'               => '数量',
                    'name'              => '物料名称',
                    'description'       => '物料描述',
                    'type'              => '物料类别'
            );
            
            foreach ($warehouseArr as $w){
                array_push($title, $w['name']);
            }
        
            array_push($data_csv, $title);
        
            $i = 0;
        
            foreach ($data as $d){
                $i++;
                
                $info = array(
                        'cnt'               => $i,
                        'code'              => $d['code'],
                        'qty'               => $d['qty'],
                        'name'              => $d['name'],
                        'description'       => str_replace("\'", "'", $d['description']),
                        'type'              => $d['type']
                );
                
                foreach ($warehouseArr as $w){
                    $qty = 0;
                    
                    if (isset($d['w_'.$w['code']])) {
                        $qty = $d['w_'.$w['code']];
                    }
                    
                    array_push($info, $qty);
                }
                
                array_push($data_csv, $info);
            }
            
            return $data_csv;
        }
        
        return array('total' => $total, 'rows' => $data);
    }

    // 获取库存交易记录
    public function getStatisticsData($condition)
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'erp_warehouse'), "t2.code = t1.warehouse_code", array('warehouse' => new Zend_Db_Expr("CONCAT(t2.code, ' ', t2.name)")))
                    ->joinLeft(array('t3' => $this->_dbprefix.'erp_warehouse_type'), "t3.id = t2.type_id", array('warehouse_type' => new Zend_Db_Expr("CONCAT(t3.code, ' ', t3.name)")))
                    ->joinLeft(array('t4' => $this->_dbprefix.'product_materiel'), "t4.code = t1.code", array('name', 'description', 'remark'))
                    ->joinLeft(array('t5' => $this->_dbprefix.'user'), "t5.id = t1.create_user", array())
                    ->joinLeft(array('t6' => $this->_dbprefix.'employee'), "t6.id = t5.employee_id", array('creater' => 'cname'))
                    //->joinLeft(array('t7' => $this->_dbprefix.'erp_stock_receive'), "t1.doc_number = t7.number", array('doc_description' => 'description', 'doc_remark' => 'remark'))
                    ->order("t1.id desc");
        
        if($condition['date_from']){
            $sql->where("t1.create_time >= '".$condition['date_from']." 00:00:00'");
        }
        
        if($condition['date_to']){
            $sql->where("t1.create_time <= '".$condition['date_to']." 23:59:59'");
        }
        
        $transaction_type_arr = json_decode($condition['transaction_type']);
        $transactionTypeCond = "";
        for($i = 0; $i < count($transaction_type_arr); $i++){
            if($i == 0){
                $transactionTypeCond = " t1.transaction_type = '".$transaction_type_arr[$i]."'";
            }else{
                $transactionTypeCond .= " or t1.transaction_type = '".$transaction_type_arr[$i]."'";
            }
        }
        
        if($transactionTypeCond != ''){
            $sql->where($transactionTypeCond);
        }
        
        $doc_type_arr = json_decode($condition['doc_type']);
        $docTypeCond = "";
        for($i = 0; $i < count($doc_type_arr); $i++){
            if($i == 0){
                $docTypeCond = " t1.doc_type = '".$doc_type_arr[$i]."'";
            }else{
                $docTypeCond .= " or t1.doc_type = '".$doc_type_arr[$i]."'";
            }
        }
        
        if($docTypeCond != ''){
            $sql->where($docTypeCond);
        }
        
        $warehouse_type_arr = json_decode($condition['warehouse_type']);
        $warehouseTypeCond = "";
        for($i = 0; $i < count($warehouse_type_arr); $i++){
            if($i == 0){
                $warehouseTypeCond = " t3.id = ".$warehouse_type_arr[$i];
            }else{
                $warehouseTypeCond .= " or t3.id = ".$warehouse_type_arr[$i];
            }
        }
        
        if($warehouseTypeCond != ''){
            $sql->where($warehouseTypeCond);
        }
        
        $warehouse_arr = json_decode($condition['warehouse']);
        $warehouseCond = "";
        for($i = 0; $i < count($warehouse_arr); $i++){
            if($i == 0){
                $warehouseCond = " t2.id = ".$warehouse_arr[$i];
            }else{
                $warehouseCond .= " or t2.id = ".$warehouse_arr[$i];
            }
        }
        
        if($warehouseCond != ''){
            $sql->where($warehouseCond);
        }
        
        if($condition['key']){
            $sql->where("t1.doc_type like '%".$condition['key']."%' or t1.doc_number like '%".$condition['key']."%' or t1.code like '%".$condition['key']."%' or t4.name like '%".$condition['key']."%' or t4.description like '%".$condition['key']."%'");
        }
        
        $total = $this->fetchAll($sql)->count();
        
        if($condition['type'] != 'csv'){
            $sql->limitPage($condition['page'], $condition['limit']);
        }
        //echo $sql;exit;
        $data = $this->fetchAll($sql)->toArray();
        
        $receive = new Erp_Model_Stock_Receive();
        
        for ($i = 0; $i < count($data); $i++){
            $data[$i]['doc_description'] = '';
            $data[$i]['doc_remark'] = '';
            
            if($data[$i]['doc_number'] != ''){
                $receiveData = $receive->fetchRow("number = '".$data[$i]['doc_number']."'")->toArray();
                
                $data[$i]['doc_description'] = $receiveData['description'];
                $data[$i]['doc_remark'] = $receiveData['remark'];
            }
        }
        
        if($condition['type'] == 'csv'){
            $data_csv = array();
        
            $title = array(
                    'cnt'               => '#',
                    'doc_type'          => '单据类别',
                    'transaction_type'  => '库存交易类别',
                    'doc_number'        => '单据号',
                    'code'              => '物料号',
                    'warehouse_type'    => '仓库',
                    'warehouse'         => '仓位',
                    'qty'               => '数量',
                    //'total'             => '金额',
                    'name'              => '物料名称',
                    'description'       => '物料描述',
                    'remark'            => '物料备注',
                    'doc_description'   => '描述',
                    'doc_remark'        => '备注',
                    'creater'           => '制单人',
                    'create_time'       => '制单时间'
            );
        
            array_push($data_csv, $title);
        
            $i = 0;
        
            foreach ($data as $d){
                $i++;
        
                $info = array(
                        'cnt'               => $i,
                        'doc_type'          => $d['doc_type'],
                        'transaction_type'  => $d['transaction_type'],
                        'doc_number'        => $d['doc_number'],
                        'code'              => $d['code'],
                        'warehouse_type'    => $d['warehouse_type'],
                        'warehouse'         => $d['warehouse'],
                        'qty'               => $d['qty'],
                        //'total'             => $d['total'],
                        'name'              => $d['name'],
                        'description'       => $d['description'],
                        'remark'            => $d['remark'],
                        'doc_description'   => $d['doc_description'],
                        'doc_remark'        => $d['doc_remark'],
                        'creater'           => $d['creater'],
                        'create_time'       => $d['create_time']
                );
        
                array_push($data_csv, $info);
            }
        
            return $data_csv;
        }
        
        return array('total' => $total, 'rows' => $data);
    }
}