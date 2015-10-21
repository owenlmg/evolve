<?php
/**
 * 2013-9-11 下午10:47:35
 * @author x.li
 * @abstract 
 */
class Erp_Model_Sale_Invoiceitems extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'erp_sale_invoice_items';
    protected $_primary = 'id';
    
    public function getData($invoice_id)
    {
        $result = array();
    
        $data = $this->fetchAll("invoice_id = ".$invoice_id)->toArray();
        
        foreach ($data as $d){
            array_push($result, array(
                'items_id'                  => $d['id'],
                'items_order_id'            => $d['order_id'],
                'items_order_date'          => $d['order_date'],
                'items_order_number'        => $d['order_number'],
                'items_order_item_id'       => $d['order_item_id'],
                'items_order_currency'      => $d['currency'],
                'items_order_currency_rate' => $d['currency_rate'],
                'items_order_tax_id'        => $d['tax_id'],
                'items_order_tax_name'      => $d['tax_name'],
                'items_order_tax_rate'      => $d['tax_rate'],
                'items_code'                => $d['code'],
                'items_name'                => $d['name'],
                'items_description'         => $d['description'],
                'items_qty'                 => $d['qty'],
                'items_unit'                => $d['unit'],
                'items_price'               => $d['price'],
                'items_price_tax'           => $d['price_tax'],
                'items_total'               => $d['total'],
                'items_total_tax'           => $d['total_tax'],
                'items_total_no_tax'        => $d['total_no_tax'],
                'items_forein_total'        => $d['forein_total'],
                'items_forein_total_tax'    => $d['forein_total_tax'],
                'items_forein_total_no_tax' => $d['forein_total_no_tax'],
                'items_remark'              => $d['remark']
            ));
        }
    
        return $result;
    }
    
    // 刷新金额
    public function refreshInvoiceTotal($invoice_id)
    {
        $invoiceData = array(
                'total'                 => 0,// 含税金额
                'total_tax'             => 0,// 税金
                'total_no_tax'          => 0,// 不含税金额
                'forein_total'          => 0,// 外币含税金额
                'forein_total_tax'      => 0,// 外币税金
                'forein_total_no_tax'   => 0// 外币不含税金额
        );
        
        if($invoice_id){
            $dataTmp = $this->fetchAll("invoice_id = ".$invoice_id);
            
            if($dataTmp->count() > 0){
                $items = $dataTmp->toArray();
                
                foreach ($items as $item){
                    $data = array(
                            'total'                 => 0,// 含税金额
                            'total_tax'             => 0,// 税金
                            'total_no_tax'          => 0,// 不含税金额
                            'forein_total'          => 0,// 外币含税金额
                            'forein_total_tax'      => 0,// 外币税金
                            'forein_total_no_tax'   => 0// 外币不含税金额
                    );
                    
                    if($item['currency_rate'] == 1){
                        // 本币
                        $data['total'] = $item['price'] * $item['qty'];
                        
                        if($item['price_tax'] == 1){
                            // 价格含税
                            $data['total_no_tax'] = $data['total'] / (1 + $item['tax_rate']);
                            $data['total_tax'] = $data['total'] - $data['total_no_tax'];
                        }else{
                            // 价格不含税
                            $data['total_no_tax'] = $data['total'];
                            $data['total_tax'] = $data['total'] * $item['tax_rate'];
                            $data['total'] = $data['total_no_tax'] + $data['total_tax'];
                        }
                    }else{
                        // 外币
                        $data['forein_total'] = $item['price'] * $item['qty'];
                        
                        if($item['price_tax'] == 1){
                            // 价格含税
                            $data['forein_total_no_tax'] = $data['total'] / (1 + $item['tax_rate']);
                            $data['forein_total_tax'] = $data['total'] - $data['forein_total_no_tax'];
                        }else{
                            // 价格不含税
                            $data['forein_total_no_tax'] = $data['forein_total'];
                            $data['forein_total_tax'] = $data['forein_total'] * $item['tax_rate'];
                            $data['forein_total'] = $data['forein_total_no_tax'] + $data['forein_total_tax'];
                        }
                    }
                    
                    $this->update($data, "id = ".$item['id']);
                    
                    $invoiceData['total'] += $data['total'];
                    $invoiceData['total_tax'] += $data['total_tax'];
                    $invoiceData['total_no_tax'] += $data['total_no_tax'];
                    $invoiceData['forein_total'] += $data['forein_total'];
                    $invoiceData['forein_total_tax'] += $data['forein_total_tax'];
                    $invoiceData['forein_total_no_tax'] += $data['forein_total_no_tax'];
                }
            }
        }
        
        $invoice = new Erp_Model_Purchse_Invoice();
        
        $invoice->update($invoiceData, "id = ".$invoice_id);
    }
    
    // 获取已开票上来
    public function getQty($item_id, $approved = null)
    {
        $qty = 0;
        
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array('qty' => new Zend_Db_Expr("sum(qty)")))
                    ->joinLeft(array('t2' => $this->_dbprefix.'erp_sale_invoice'), "t1.invoice_id = t2.id")
                    ->where("t1.order_item_id = ".$item_id);
        
        if($approved == 1){
            $sql->where("t2.state = 2");
        }
        
        $data = $this->fetchRow($sql)->toArray();
        
        if($data['qty'] > 0){
            $qty = $data['qty'];
        }
        
        return $qty;
    }
}