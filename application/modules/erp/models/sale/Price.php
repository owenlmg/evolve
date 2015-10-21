<?php
/**
 * 2014-12-06 22:32:26
 * @author x.li
 * @abstract 
 */
class Erp_Model_Sale_Price extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'erp_sale_price';
    protected $_primary = 'id';
    
    public function updatePriceByPriceId($price_id)
    {
        $priceData = $this->fetchRow("id = ".$price_id)->toArray();
        
        $priceItemModel = new Erp_Model_Sale_Priceitems();
        
        $items = $priceItemModel->getItems($price_id);
        
        foreach ($items as $item){
            $priceItemModel->update(array('active' => 1), "id = ".$item['items_id']);
            
            $priceItemModel->inactivePrice($price_id, $priceData['customer_id'], $item['items_type'], $item['items_code']);
        }
    }
    
    public function getNewNum()
    {
        $pre = 'SP';
    
        $num_pre = $pre.date('ymd');
    
        $data = $this->fetchAll("number like '".$num_pre."%'", array('number desc'));
    
        if($data->count() == 0){
            $num = '01';
        }else{
            $last_item = $data->getRow(0)->toArray();
    
            $new_order = intval(substr($last_item['number'], strlen($pre) + 6)) + 1;
    
            $num = sprintf ("%02d", $new_order);
        }
    
        return $num_pre.$num;
    }
    
    public function getData($conditions, $price_id = null)
    {
        $data = array();
        
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t1.create_user = t2.id", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t2.employee_id = t3.id", array(
                            'creater' => 'cname'
                    ))
                    ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t1.update_user = t4.id", array())
                    ->joinLeft(array('t5' => $this->_dbprefix.'employee'), "t4.employee_id = t5.id", array(
                            'updater' => 'cname'
                    ))
                    ->joinLeft(array('t6' => $this->_dbprefix.'bpartner'), "t1.customer_id = t6.id", array(
                            'customer_name' => new Zend_Db_Expr("case when t6.cname = '' then t6.ename else t6.cname end"),
                            'customer_code' => 'code'
                    ))
                    ->joinLeft(array('t7' => $this->_dbprefix.'erp_setting_tax'), "t1.tax_id = t7.id", array(
                            'tax_name' => 'name'
                    ))
                    ->where("t1.deleted = 0");
        
        if ($conditions['key']) {
            $sql->where("t1.number LIKE '%".$conditions['key']."%' OR t6.code LIKE '%".$conditions['key']."%' OR t6.cname LIKE '%".$conditions['key']."%' OR t6.ename LIKE '%".$conditions['key']."%' OR t3.cname LIKE '%".$conditions['key']."%' OR t3.ename LIKE '%".$conditions['key']."%'");
        }
        
        if ($price_id) {
            $sql->where("t1.id = ".$price_id);
            
            return $this->fetchRow($sql)->toArray();
        }else{
            $sql->where("state = ".$conditions['state']);
            
            if ($conditions['date_from']) {
                $sql->where("price_date >= '".$conditions['date_from']." 00:00:00'");
            }
            
            if ($conditions['date_to']) {
                $sql->where("price_date <= '".$conditions['date_to']." 23:59:59'");
            }
            
            $data = $this->fetchAll($sql)->toArray();
            
            $review = new Dcc_Model_Review();
            $help = new Application_Model_Helpers();
            $user_session = new Zend_Session_Namespace('user');
            $employee_id = $user_session->user_info['employee_id'];
            
            for ($i = 0; $i < count($data); $i++){
                // 当状态不为拒绝时才能获取，否则会报错
                if($data[$i]['state'] != 1){
                    // 获取审核情况
                    $review_state = $help->getReviewState('sale_price_add', $data[$i]['id']);
            
                    $data[$i]['reviewer'] = implode(',', $help->getEmployeeNameByIdArr($review_state['reviewer']));
                    $data[$i]['review_state'] = $review_state['info'];
                    $data[$i]['can_review'] = in_array($employee_id, $review_state['reviewer']) && !in_array($employee_id, $review_state['reviewer_finished']) ? 1 : 0;
                    $data[$i]['current_step'] = $review_state['step_chk']['current_step'];
                    $data[$i]['last_step'] = $review_state['step_chk']['last_step'];
                    $data[$i]['to_finish'] = $review_state['step_chk']['to_finish'];
                    $data[$i]['next_step'] = $review_state['step_chk']['next_step'];
                }
            
                if($help->chkIsReviewer('sale_price_add', $data[$i]['id'], $employee_id)){
                    $data[$i]['is_reviewer'] = 1;
                }else{
                    $data[$i]['is_reviewer'] = 0;
                }
            }
        }
        
        return $data;
    }
}