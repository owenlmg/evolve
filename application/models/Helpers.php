<?php
/**
 * 2013-12-8 下午11:53:33
 * @author x.li
 * @abstract 
 */
class Application_Model_Helpers
{
    public function getOpenReqOrderByCode($code)
    {
        $openNumber = array();
        
        $reqItemsModel = new Erp_Model_Purchse_Reqitems();
        $orderItemsModel = new Erp_Model_Purchse_Orderitems();
        
        $openOrder = $orderItemsModel->getOpenOrderByCode($code);
        
        foreach ($openOrder as $o){
            array_push($openNumber, $o['number']);
        }
        
        $openReq = $reqItemsModel->getOpenReqByCode($code);
        
        foreach ($openReq as $r){
            array_push($openNumber, $r['number']);
        }
        
        return $openNumber;
    }
    
    public function getEmployeeNameByIdArr($ids)
    {
        $names = array();
        
        $employee = new Hra_Model_Employee();
        
        foreach ($ids as $id){
            $empInfo = $employee->getInfoById($id);
            array_push($names, $empInfo['cname']);
        }
        
        return $names;
    }
    
    /**
     * 删除HTMl标签
     *
     * @access public
     * @param string str
     * @return string
     *
     */
    function deletehtml($str) {
        $str = trim($str);
        $str = strip_tags($str,"");
        $str = preg_replace("/\t/","",$str);
        
        $str = preg_replace("/\r\n/","",$str);
        $str = preg_replace("/\r/","",$str);
        $str = preg_replace("/\n/","",$str);
        
        $str = preg_replace("<br>","/\n/",$str);
        
        $str = preg_replace("/&amp;/","&",$str);
        $str = preg_replace("/&quot; /",'"',$str);
        $str = preg_replace("/&lt;/","<",$str);
        $str = preg_replace("/&gt;/",">",$str);
        $str = preg_replace("/&nbsp;/"," ",$str);
        
        return trim($str);
    }
    
    /**
     * 检查用户是否为审核人
     * @param unknown $type
     * @param unknown $id
     * @param unknown $employee_id
     * @return boolean
     */
    public function chkIsReviewer($type, $id, $employee_id)
    {
        $review = new Dcc_Model_Review();
        
        $res = $review->fetchAll("type = '".$type."' and file_id = ".$id);
        
        if($res->count() > 0){
            $data = $res->toArray();
            
            foreach ($data as $d){
                $reviewerArr = explode(',', $d['plan_user']);
                
                if(in_array($employee_id, $reviewerArr)){
                    return true;
                }
            }
        }
        
        return false;
    }
    
    // 根据类别及对象ID获取当前记录审核情况
    public function getReviewState($type, $file_id)
    {
        $reviewState = array(
                'info'              => null,
                'reviewer'          => array(),
                'reviewer_finished' => array(),
                'step_chk'          => array('current_step' => 0, 'last_step' => 0, 'to_finish' => 1, 'next_step' => 0)
        );
        
        $review = new Dcc_Model_Review();
        
        // 获取审核情况
        $reviewRes = $review->fetchAll("type = '".$type."' and file_id = ".$file_id, array('id'));
        
        if($reviewRes->count() > 0){
            $reviewData = $reviewRes->toArray();
            
            // 检查当前阶段是否为审核最后阶段
            if((count($reviewData) == 1) 
                || ($reviewData[count($reviewData) - 1]['finish_flg'] == 0 && count($reviewData) > 1 && $reviewData[count($reviewData) - 2]['finish_flg'] == 1)){
                // 最后一阶段未审核，倒数第二阶段已审核
                $reviewState['step_chk']['last_step'] = 1;
            }
            
            // 审核状态信息
            $reviewStateArr = array();
            
            // 当前阶段信息
            $currentStepMethod = 1;
            $currentStepEmployee = array();
            $currentStepEmployeeFinished = "";
            
            $i = 0;
            
            foreach ($reviewData as $r){
                $reviewType = $r['method'] == 1 ? '任意' : '全部';
                
                $actual_user = explode(',', $r['actual_user']);
                
                $reviewEmployeeArr = array(
                        'id'    => explode(',', $r['plan_user']),
                        'name'  => $this->getEmployeeNameByIdArr(explode(',', $r['plan_user']))
                );
                
                if($r['method'] == 2){
                    for($j = 0; $j < count($reviewEmployeeArr['id']); $j++){
                        if(in_array($reviewEmployeeArr['id'][$j], $actual_user)){
                            $reviewEmployeeArr['name'][$j] .= ': 已审核';
                        }
                    }
                }
                
                $reviewEmployee = implode(', ', $reviewEmployeeArr['name']).'（'.$reviewType.'）';
                
                $stepStr = '<a title="'.$reviewEmployee.'">'.$r['step_name'].'</a>';
                
                if($r['finish_flg'] == 0){
                    if($reviewState['step_chk']['current_step'] > 0){
                        array_push($reviewStateArr, $stepStr);
                    }else{
                        array_push($reviewStateArr, '<b>'.$stepStr.'</b>');
                        $currentStepEmployee = $reviewState['reviewer'] = $reviewEmployeeArr['id'];
                        $reviewState['step_chk']['current_step'] = $r['id'];
                        
                        if(isset($reviewData[$i + 1])){
                            // 获取下一阶段review表ID，作为阶段跳转或发布申请的备用
                            $reviewState['step_chk']['next_step'] = $reviewData[$i + 1]['id'];
                        }
                        
                        $currentStepMethod = $r['method'];
                        $currentStepEmployeeFinished = $reviewState['reviewer_finished'] = $actual_user;
                    }
                }else{
                    array_push($reviewStateArr, $stepStr);
                }
                
                $i++;
            }
            
            // 当前阶段方法为全部：检查是否所有人已审核（自己除外）
            if($currentStepMethod == 2){
                $user_session = new Zend_Session_Namespace('user');
                $self_id = $user_session->user_info['employee_id'];
                
                foreach ($currentStepEmployee as $e){
                    if($self_id != $e && !in_array($e, $currentStepEmployeeFinished)){
                        $reviewState['step_chk']['to_finish'] = 0;
                        break;
                    }
                }
            }
            
            $reviewState['info'] = implode(' > ', $reviewStateArr);
        }
        
        return $reviewState;//<img src="' + HOME_PATH + '/public/images/icons/record_next.png"></img>
    }
    
    // 根据阶段的用户及角色配置获取审核人
    public function getReviewEmployee($user, $role)//$employee
    {
        $employeeArr = array(
                'id'    => array(),
                'name'  =>array()
        );
        
        $member = new Admin_Model_Member();
        $employee = new Hra_Model_Employee();
        
        if($user){
            $tmpArr = explode(',', $user);
        
            foreach ($tmpArr as $t){
                if(!in_array($t, $employeeArr['id'])){
                    array_push($employeeArr['id'], $t);
                    
                    $empInfo = $employee->getInfoById($t);
                    array_push($employeeArr['name'], $empInfo['cname']);
                }
            }
        }
        
        if($role){
            $tmpArr = $member->getMember($role, 'id', false, true);
        
            foreach ($tmpArr as $t){
                if(!in_array($t['employee_id'], $employeeArr['id'])){
                    array_push($employeeArr['id'], $t['employee_id']);
                    
                    $empInfo = $employee->getInfoById($t['employee_id']);
                    array_push($employeeArr['name'], $empInfo['cname']);
                }
            }
        }
        
        return $employeeArr;
    }
    
    // 发送邮件通知到阶段用户及角色
    public function sendMailToStep($mailTo, $mailData)
    {
        $result = array(
                'success'   => true,
                'info'      => '发送成功'
        );
        
        $user = new Application_Model_User();
        $employee = new Hra_Model_Employee();
        
        // 检查是否有接收人
        if(count($mailTo)){
            $toAddress = array();
            $toIds = array();
            
            foreach ($mailTo as $employeeId){
                $em = $employee->getInfoById($employeeId);
                array_push($toAddress, $em['email']);
                $u = $user->fetchRow("employee_id = ".$employeeId)->toArray();
                array_push($toIds, $u['id']);
            }
            
            $mail = new Application_Model_Log_Mail();
            
            $mailData['to'] = implode(',', $toAddress);
            $mailData['user_id'] = implode(',', $toIds);
            
            try {
                // 记录邮件日志并发送邮件
                $mail->send($mail->insert($mailData));
            } catch (Exception $e) {
                $result['success'] = false;
                $result['info'] = $e->getMessage();
            }
        }else{
            $result['success'] = false;
            $result['info'] = '邮件通知没有接受对象';
        }
        
        return $result;
    }
    
    // 用时间生成文件名称
    public function getMicrotimeStr()
    {
        list($usec, $sec) = explode(" ", microtime());
    
        return $sec.'_'.substr($usec, 2);
    }
    
    /**
     * 导出CSV
     * @param array $data
     */
    public function exportCsv($data, $name = null){
        //print(chr(0xEF).chr(0xBB).chr(0xBF));
        
        $name = $name ? $name.'_'.date('ymdHis').'.csv' : $this->getMicrotimeStr().'.csv';
        
        $filePath = HOME_REAL_PATH.'/temp/'.$name;
        
        $fp = fopen($filePath,'w');
        
        foreach ($data as $d){
            fputcsv($fp, $d);
        }
        
        fclose($fp);
        
        $content = "\xEF\xBB\xBF".file_get_contents($filePath);
        
        
        header('Content-Disposition: attachment; filename="'.$name.'"');
        header( "Content-type: text/csv" ) ;
        //header('Content-Transfer-Encoding: binary');
        header("Pragma: no-cache");
        header("Expires: 0");
        
        /* header( "Cache-Control: public" );
        header( "Pragma: public" );
        header( "Content-type: text/csv" ) ;
        header( "Content-Dis; filename=".$name ) ;
        header( "Content-Length: ". strlen( $content ) ); */
        
        echo $content;
    }
    
    /**
     * 人民币小写转大写
     *
     * @param string $number 数值
     * @param string $int_unit 币种单位，默认"元"，有的需求可能为"圆"
     * @param bool $is_round 是否对小数进行四舍五入
     * @param bool $is_extra_zero 是否对整数部分以0结尾，小数存在的数字附加0,比如1960.30，
     *             有的系统要求输出"壹仟玖佰陆拾元零叁角"，实际上"壹仟玖佰陆拾元叁角"也是对的
     * @return string
     */
    public function num2rmb($number = 0, $int_unit = '元', $is_round = TRUE, $is_extra_zero = FALSE)
    {
        // 将数字切分成两段
        $parts = explode('.', $number, 2);
        $int = isset($parts[0]) ? strval($parts[0]) : '0';
        $dec = isset($parts[1]) ? strval($parts[1]) : '';
    
        // 如果小数点后多于2位，不四舍五入就直接截，否则就处理
        $dec_len = strlen($dec);
        if (isset($parts[1]) && $dec_len > 2){
            $dec = $is_round
            ? substr(strrchr(strval(round(floatval("0.".$dec), 2)), '.'), 1)
            : substr($parts[1], 0, 2);
        }
    
        // 当number为0.001时，小数点后的金额为0元
        if(empty($int) && empty($dec)){
            return '零';
        }
    
        // 定义
        $chs = array('0','壹','贰','叁','肆','伍','陆','柒','捌','玖');
        $uni = array('','拾','佰','仟');
        $dec_uni = array('角', '分');
        $exp = array('', '万');
        $res = '';
    
        // 整数部分从右向左找
        for($i = strlen($int) - 1, $k = 0; $i >= 0; $k++){
            $str = '';
            // 按照中文读写习惯，每4个字为一段进行转化，i一直在减
            for($j = 0; $j < 4 && $i >= 0; $j++, $i--){
                $u = $int{$i} > 0 ? $uni[$j] : ''; // 非0的数字后面添加单位
                $str = $chs[$int{$i}] . $u . $str;
            }
            //echo $str."|".($k - 2)."<br>";
            $str = rtrim($str, '0');// 去掉末尾的0
            $str = preg_replace("/0+/", "零", $str); // 替换多个连续的0
            if(!isset($exp[$k])){
                $exp[$k] = $exp[$k - 2] . '亿'; // 构建单位
            }
            $u2 = $str != '' ? $exp[$k] : '';
            $res = $str . $u2 . $res;
        }
    
        // 如果小数部分处理完之后是00，需要处理下
        $dec = rtrim($dec, '0');
    
        // 小数部分从左向右找
        if(!empty($dec)){
            $res .= $int_unit;
        
            // 是否要在整数部分以0结尾的数字后附加0，有的系统有这要求
            if ($is_extra_zero){
                if (substr($int, -1) === '0'){
                    $res.= '零';
                }
            }
        
            for($i = 0, $cnt = strlen($dec); $i < $cnt; $i++){
                $u = $dec{$i} > 0 ? $dec_uni[$i] : ''; // 非0的数字后面添加单位
                $res .= $chs[$dec{$i}] . $u;
            }
            $res = rtrim($res, '0');// 去掉末尾的0
            $res = preg_replace("/0+/", "零", $res); // 替换多个连续的0
        }else{
            $res .= $int_unit . '整';
        }
        return $res;
    }
}