<?php
/**
 * 2013-9-11 下午10:47:35
 * @author x.li
 * @abstract 
 */
class Erp_Model_Purchse_Invoice extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'erp_pur_invoice';
    protected $_primary = 'id';
    
    public function getNewNum()
    {
        $num_pre = 'INV'.date('ymd');
        
        $data = $this->fetchAll("number like '".$num_pre."%'", array('number desc'));
        
        if($data->count() == 0){
            $num = '01';
        }else{
            $last_item = $data->getRow(0)->toArray();
            
            $new_order = intval(substr($last_item['number'], 9)) + 1;
            
            $num = sprintf ("%02d", $new_order);
        }
        
        return $num_pre.$num;
    }
    
    public function getData($condition = array(), $invoice_id = null)
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t1.create_user = t2.id", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t2.employee_id = t3.id", array('creater' => 'cname'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t1.update_user = t4.id", array())
                    ->joinLeft(array('t5' => $this->_dbprefix.'employee'), "t4.employee_id = t5.id", array('updater' => 'cname'))
                    ->joinLeft(array('t9' => $this->_dbprefix.'bpartner'), "t1.supplier_id = t9.id", array('supplier_code' => 'code', 'supplier_ename' => new Zend_Db_Expr("case when t9.ename = '' then t9.cname else t9.ename end"), 'supplier_cname' => 'cname', 'supplier_bank_type' => 'bank_type', 'supplier_bank_account' => 'bank_account', 'supplier_tax_id' => 'tax_id', 'supplier_tax_num' => 'tax_num', 'supplier_bank_payment_days' => 'bank_payment_days'))
                    /* ->joinLeft(array('t8' => $this->_dbprefix.'erp_pur_order_items'), "t1.id = t8.order_id", array()) */;
        
        if($invoice_id){
            $sql->where("t1.id = ".$invoice_id);
            
            return $this->fetchRow($sql)->toArray();
        }else{
            $sql->where("t1.state = ".$condition['state']);
            
            if($condition['date_from']){
                $sql->where("t1.create_time >= '".$condition['date_from']." 00:00:00'");
            }
            
            if($condition['date_to']){
                $sql->where("t1.create_time <= '".$condition['date_to']." 23:59:59'");
            }
            
            if ($condition['key']){
                $sql->where("t9.code like '%".$condition['key']."%' or t9.cname like '%".$condition['key']."%' or t9.ename like '%".$condition['key']."%' or t1.number like '%".$condition['key']."%' or t1.description like '%".$condition['key']."%' or t1.remark like '%".$condition['key']."%'");// or t8.name like '%".$condition['key']."%'
            }
            
            $total = $this->fetchAll($sql)->count();
            
            $sql->order(array('t1.state', 't1.number desc', 't1.create_time desc'))
                ->limitPage($condition['page'], $condition['limit']);
            
            $data = $this->fetchAll($sql)->toArray();
            
            $review = new Dcc_Model_Review();
            $help = new Application_Model_Helpers();
            $user_session = new Zend_Session_Namespace('user');
            $employee_id = $user_session->user_info['employee_id'];
            
            for($i = 0; $i < count($data); $i++){
                $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
                $data[$i]['update_time'] = strtotime($data[$i]['update_time']);
                $data[$i]['release_time'] = strtotime($data[$i]['release_time']);
                $data[$i]['state'] = intval($data[$i]['state']);
                $data[$i]['review_state'] = "";
                $data[$i]['review_info_tip'] = $data[$i]['review_info'];
                $data[$i]['review_info'] = str_replace('<br>', ' > ', $data[$i]['review_info']);
                $data[$i]['reviewer'] = '';
                
                // 当状态不为拒绝时才能获取，否则会报错
                if($data[$i]['state'] != 1){
                    // 获取审核情况
                    $review_state = $help->getReviewState('purchse_invoice_add', $data[$i]['id']);
                    /* echo '<pre>';
                    print_r($review_state);
                    exit; */
                    $data[$i]['reviewer'] = implode(',', $help->getEmployeeNameByIdArr($review_state['reviewer']));
                    $data[$i]['review_state'] = $review_state['info'];
                    $data[$i]['can_review'] = in_array($employee_id, $review_state['reviewer']) && !in_array($employee_id, $review_state['reviewer_finished']) ? 1 : 0;
                    $data[$i]['current_step'] = $review_state['step_chk']['current_step'];
                    $data[$i]['last_step'] = $review_state['step_chk']['last_step'];
                    $data[$i]['to_finish'] = $review_state['step_chk']['to_finish'];
                    $data[$i]['next_step'] = $review_state['step_chk']['next_step'];
                }
                
                if($help->chkIsReviewer('purchse_invoice_add', $data[$i]['id'], $employee_id)){
                    $data[$i]['is_reviewer'] = 1;
                }else{
                    $data[$i]['is_reviewer'] = 0;
                }
            }
            
            return array('total' => $total, 'rows' => $data);
        }
    }
}