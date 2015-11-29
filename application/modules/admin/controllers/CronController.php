<?php
/**
 * 2013-11-9 下午12:17:03
 * @author x.li
 * @abstract 
 */
class Admin_CronController extends Zend_Controller_Action
{
    public function indexAction()
    {
        exit;
    }
    
    public function runAction()
    {
        $sendResult = true;
        $errInfo = '';
        
        // 采购交期回复批量回复
        $hourSet = array('12:00', '18:00');// 12点和18点各运行一次
        $timeDiff = 12;
        
        foreach ($hourSet as $hour){
            if ($hour == date('H:00')){
                $this->generatePurchseDeliveryUpdateEmail(date('Y-m-d H:00:00'), $timeDiff);
            }
        }
        
        $mail = new Application_Model_Log_Mail();
        
        $content = "[ Start ".date("Y-m-d H:i:s")." ]\r\n";
        
        $dateStart = date('Y-m-d', strtotime('-3 day'));
        
        $data = $mail->fetchAll("state = 0 and add_date >= '".$dateStart."'")->toArray();
        //echo '<pre>';print_r($data);exit;
        foreach ($data as $d){
            $mail->update(array('content' => '<div style="color:#f00;font-weight:bold;">系统任务计划重发！</div>'.$d['content']), "id = ".$d['id']);
            $result = $mail->send($d['id']);
            
            if(!$result['success']){
                $sendResult = false;
                $errInfo = $result['info'];
                break;
            }
        }
        
        if(count($data) > 0){
            if(!$sendResult){
                $content .= "      ".date("Y-m-d H:i:s")." Faild [".$errInfo."]\r\n";
            }else{
                $content .= "      ".date("Y-m-d H:i:s")." Success\r\n";
            }
        }
        
        $content .= "[ End   ".date("Y-m-d H:i:s")." ]\r\n";
        
        $this::writeLog($content, 'cron_mail');
        
        if($errInfo){
            echo iconv('utf-8', 'gbk', 'Faild! '.$errInfo);
        }else{
            echo iconv('utf-8', 'gbk', 'Success! ');
        }
        
        exit;
    }
    
    public function writeLog($content, $fileName)
    {
        $filename = HOME_REAL_PATH."/log/".$fileName.".log";
        $fh = fopen($filename, "a+");
        fwrite($fh, $content);
        fclose($fh);
    }
    
    //public function mailPurchseDeliveryReplay()
    public function generatePurchseDeliveryUpdateEmail($time, $diff)
    {
        $item = new Erp_Model_Purchse_Orderitems();
        $mail = new Application_Model_Log_Mail();
        
        $emails = array();
        
        $now = date('Y-m-d H:i:s');
        
        $notice = $item->getDeliveryNotice($time, $diff);
        
        $table = '<style type="text/css">
table.gridtable {
	font-family: verdana,arial,sans-serif;
	font-size:11px;
	color:#333333;
	border-width: 1px;
	border-color: #666666;
	border-collapse: collapse;
}
table.gridtable th {
	border-width: 1px;
	padding: 8px;
	border-style: solid;
	border-color: #666666;
	background-color: #dedede;
}
table.gridtable td {
	border-width: 1px;
	padding: 8px;
	border-style: solid;
	border-color: #666666;
	background-color: #ffffff;
}
</style><table class="gridtable"><tr>
                                <th>#</th>
                                <th>采购订单</th>
                                <th>采购员</th>
                                <th>物料号</th>
                                <th>物料名称</th>
                                <th>物料描述</th>
                                <th>申请单号</th>
                                <th>申请数量</th>
                                <th>采购数量</th>
                                <th>订单数量</th>
                                <th>项目信息</th>
                                <th>需求日期</th>
                                <th>预计交期</th>
                                <th>交期备注</th>
                                <th>申请制单人</th>
                                <th>申请人</th>
                                <th>交期更新时间</th>
                                </tr>';
        
        foreach ($notice as $n){
            $itemTable = $table;
            $applier_id = 0;
            $noticeMails = array();
            $i = 0;
            
            foreach ($n as $item){
                $i++;
                
                $applier_id = $item['req_creater_id'];
                
                if (!in_array($item['req_creater_email'], $noticeMails)) {
                    array_push($noticeMails, $item['req_creater_email']);
                }
                
                if (!in_array($item['req_applier_email'], $noticeMails)) {
                    array_push($noticeMails, $item['req_applier_email']);
                }
                
                $itemTable .= '<tr>
                        <td>'.$i.'</td>
                        <td>'.$item['order_number'].'</td>
                        <td>'.$item['order_buyer'].'</td>
                        <td>'.$item['item_code'].'</td>
                        <td>'.$item['item_name'].'</td>
                        <td>'.$item['item_description'].'</td>
                        <td>'.$item['req_number'].'</td>
                        <td><b>'.$item['req_qty'].'</b></td>
                        <td><b>'.$item['req_order_qty'].'</b></td>
                        <td>'.$item['item_qty'].'</td>
                        <td>'.$item['item_project_info'].'</td>
                        <td><b>'.$item['item_request_date'].'</b></td>
                        <td><b>'.$item['item_delivery_date'].'</b></td>
                        <td>'.$item['item_delivery_remark'].'</td>
                        <td>'.$item['req_creater_name'].'</td>
                        <td>'.$item['req_applier_name'].'</td>
                        <td><b>'.$item['item_update_time'].'</b></td>
                        <tr>';
            }
            
            $itemTable .= '</table>';
            
            $mailContent = '<div>采购交期更新，请登录系统查看：</div><div>'.$itemTable.'</div><hr>';
            
            $mailData = array(
                    'type'      => '通知',
                    'subject'   => '采购交期更新',
                    'content'   => $mailContent,
                    'add_date'  => $now,
                    'to'        => implode(',', $noticeMails),
                    'user_id'   => $applier_id
            );
            
            $mail->insert($mailData);
        }
    }
}