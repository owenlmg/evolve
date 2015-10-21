<?php
/**
 * 2013-10-14 下午9:50:42
 * @author x.li
 * @abstract 
 */
class Application_Model_Log_Mail extends Application_Model_Db
{
    protected $_name = 'log_mail';
    protected $_primary = 'id';
    
    /**
     * 获取类别列表
     * @return unknown
     */
    public function getType()
    {
        $sql = $this->select()
                    ->from($this, array('type'))
                    ->group(array('type'))
                    ->order(array('type'));
        
        $data = $this->fetchAll($sql)->toArray();
        
        return $data;
    }
    
    /**
     * 获取数据
     * @param unknown $condition
     * @return multitype:number Ambigous <number, multitype:>
     */
    public function getData($condition)
    {
        $where = "t1.add_date >= '".$condition['date_from']."' and t1.add_date <= '".$condition['date_to']."'";
        
        $type = json_decode($condition['type']);
        
        if(count($type) > 0){
            $where .= " and (";
        
            for($i = 0; $i < count($type); $i++){
                if($i == 0){
                    $where .= "t1.type = '".$type[$i]."'";
                }else{
                    $where .= " or t1.type = '".$type[$i]."'";
                }
            }
        
            $where .= ")";
        }
        
        if($condition['key'] != ''){
            $where .= " and (t1.subject like '%".$condition['key']."%' or t1.to like '%".$condition['key']."%' or t1.cc like '%".$condition['key']."%' or t1.remark like '%".$condition['key']."%' or t3.cname like '%".$condition['key']."%' or t3.ename like '%".$condition['key']."%' or t3.number like '%".$condition['key']."%')";
        }
        
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t1.user_id = t2.id", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t2.employee_id = t3.id", array('user' => new Zend_Db_Expr("concat('[', t3.number, '] ', t3.cname)")))
                    ->order(array('t1.send_time desc'))
                    ->where($where);
        
        $total = $this->fetchAll($sql)->count();
        
        if($condition['option'] != 'csv'){
            $sql->limitPage($condition['page'], $condition['limit']);
        }
        
        $data = $this->fetchAll($sql)->toArray();

        if($condition['option'] == 'csv'){
            $data_csv = array();
            
            $title = array(
                    'cnt'               => '#',
                    'add_date'          => '日期',
                    'user'              => '用户',
                    'type'              => '类别',
                    'state'             => '发送状态',
                    'subject'           => '主题',
                    'to'                => '收件人',
                    'cc'                => '抄送',
                    'attachment_name'   => '附件',
                    'send_time'         => '发送时间',
                    'err_info'          => '错误信息',
                    'key'               => 'Key',
                    'remark'            => '备注'
            );
            
            array_push($data_csv, $title);
            
            $i = 0;
            
            foreach ($data as $d){
                $i++;
            
                $info = array(
                        'cnt'               => $i,
                        'add_date'          => $d['add_date'],
                        'user'              => $d['user'],
                        'type'              => $d['type'],
                        'state'             => $d['err_info'] == '' ? '是' : '否',
                        'subject'           => $d['subject'],
                        'to'                => $d['to'],
                        'cc'                => $d['cc'],
                        'attachment_name'   => $d['attachment_name'],
                        'send_time'         => $d['send_time'],
                        'err_info'          => $d['err_info'],
                        'key'               => $d['key'],
                        'remark'            => $d['remark']
                );
            
                array_push($data_csv, $info);
            }
            
            return $data_csv;
        }

        return array('total' => $total, 'rows' => $data);
    }
    
    /**
     * 清空KEY
     * @param unknown $key
     */
    public function clearKey($key)
    {
        $this->update(array('key' => null), $this->_name.".key = '".$key."'");
    }
    
    /**
     * 检查收件人和抄送人邮箱地址格式
     * @param string $to    收件人
     * @param string $cc    抄送人
     * @return string
     */
    public function checkAddress($to, $cc)
    {
        $errInfo = '';
        
        $toArr = explode(',', $to);
        
        if($cc){
            $ccArr = explode(',', $cc);
            
            /* for($i = 0; $i < count($ccArr); $i++){
                if(!preg_match("/^[0-9a-zA-Z]+(?:[_-][a-z0-9-]+)*@[a-zA-Z0-9]+(?:[-.][a-zA-Z0-9]+)*.[a-zA-Z]+$/i", $ccArr[$i])){
                    return '抄送人地址错误：'.$ccArr[$i];
                    
                    break;
                }
            } */
        }
        
        return $errInfo;
    }
    
    /**
     * 检查附件信息是否正确
     * @param string $fileName  附件名称
     * @param string $filePath  附件路径
     * @return string
     */
    public function checkAttachment($fileName, $filePath)
    {
        $errInfo = '';
        
        if($fileName){
            $fileNameArr = explode(',', $fileName);
            $filePathArr = explode(',', $filePath);
            
            if(count($fileNameArr) != count($filePathArr)){
                $errInfo = '附件名称、路径数量不匹配';
            }else{
                for($i = 0; $i < count($fileNameArr); $i++){
                    if(!file_exists($filePathArr[$i])){
                        $errInfo = '附件地址错误：'.$filePathArr[$i];
                        
                        break;
                    }
                }
            }
        }
        
        return $errInfo;
    }
    
    /**
     * 检查邮件发送数据是否正确
     * @param array $data
     * @return string
     */
    public function check($data)
    {
        $errInfo = '';
        
        if(!$data['type']){
            $errInfo = '类别为空';
        }elseif (!$data['subject']){
            $errInfo = '主题为空';
        }elseif (!$data['to']){
            $errInfo = '接收人为空';
        }elseif (!$data['content']){
            $errInfo = '正文为空';
        }else{
            $errInfo = $this->checkAddress($data['to'], $data['cc']);
            
            if(!$errInfo){
                $errInfo = $this->checkAttachment($data['attachment_name'], $data['attachment_path']);
            }
        }
        
        return $errInfo;
    }
    
    public function sendAll()
    {
        $mails = $this->fetchAll("state = 0")->toArray();
        $result = array();
        
        foreach ($mails as $mail){
            $result = $this::send($mail['id']);
            
            
        }
    }
    
    /**
     * 发送邮件
     * @param number $mailId        邮件任务ID
     * @param number $activeLimit   邮件失效期限
     * @return multitype:boolean string
     */
    public function send($mailId, $activeLimit = 0, $to_name = '', $footer = '', $header = false)
    {
        set_time_limit(0);
        
        $errInfo = '';
        
        if($mailId){
            $send_state = 0;
            $now = date('Y-m-d H:i:s');
            
            // 获取邮件任务
            $where = "";
            
            if($activeLimit > 0){
                $where = " and datediff('".date('Y-m-d')."', add_date) < ".$activeLimit;
            }
            
            $data = $this->fetchRow("id = ".$mailId.$where)->toArray();
            
            if($data){
                // 检查邮件信息是否正确
                $errInfo = $this->check($data);
                
                if(!$errInfo){
                    $employeeModel = new Hra_Model_Employee();
                    
                    // 读取邮件发送配置
                    $mailServerConfig = new Zend_Config_Ini(CONFIGS_PATH.'/application.ini','mail');
                    $transport = new Zend_Mail_Transport_Smtp($mailServerConfig->smtp->server, $mailServerConfig->smtp->params->toArray());
                    
                    if($header) {
                        $mail_header = '';
                    } else {
                        $mail_header = '<div>'.$to_name.'您好，<br><br></div>';
                    }
                    if(!$footer) {
                        $mail_footer = '<div style="color:FF0000;"><br><br>系统邮件，请勿回复！</div><div>'.SYS_COPYRIGHT.'</div>';
                    } else {
                        $mail_footer = '<div style="color:FF0000;">'.$footer.'</div>';
                    }
                    
                    $mail = new Zend_Mail('UTF-8');
                    $mail->setSubject($data['type'].'-'.$data['subject']);
                    $mail->setBodyHtml($mail_header.'<div>'.$data['content'].'</div>'.$mail_footer);
                    $mail->setFrom($mailServerConfig->smtp->from, $mailServerConfig->smtp->fromname);
                    
                    $sendCnt = 0;
                    
                    // 添加收件人
                    $toArr = explode(',', $data['to']);
                    
                    foreach ($toArr as $toMail){
                        // 文件外发会用到外部邮箱，不能从employee表检查
                        /*if(stripos($toMail, '@') != flase) {
                            $mail->addTo($toMail);
                            $sendCnt++;
                        }*/
                        if(stripos($toMail, SYS_EMAIL_SUFFIX) != false) {
                            // 内部邮箱，检查是否在职
                            if($employeeModel->fetchAll("active = 1 and email = '".$toMail."'")->count() > 0){
                                $mail->addTo($toMail);
                                $sendCnt++;
                            }
                        } else {
                            $mail->addTo($toMail);
                            $sendCnt++;
                        }
                        /*if($employeeModel->fetchAll("active = 1 and email = '".$toMail."'")->count() > 0){
                            $mail->addTo($toMail);
                            $sendCnt++;
                        }*/
                    }
                    
                    // 添加抄送人
                    if($data['cc']){
                        $ccArr = explode(',', $data['cc']);
                    
                        foreach ($ccArr as $ccMail){
                            if($employeeModel->fetchAll("active = 1 and email = '".$ccMail."'")->count() > 0){
                                $mail->addCc($ccMail);
                                $sendCnt++;
                            }
                        }
                    }
                    
                    if($sendCnt > 0){
                        // 添加附件
                        if($data['attachment_path']){
                            $fileNameArr = explode(',', $data['attachment_name']);
                            $filePathArr = explode(',', $data['attachment_path']);
                        
                            for($i = 0; $i < count($filePathArr); $i++){
                                $mail->createAttachment(
                                        file_get_contents($filePathArr[$i]),
                                        'application/octet-stream',
                                        Zend_Mime::DISPOSITION_INLINE,
                                        Zend_Mime::ENCODING_BASE64 ,
                                        "=?UTF-8?B?".base64_encode($fileNameArr[$i])."?="
                                );
                            }
                        }
                        
                        // 发送
                        try{
                            $mail->send($transport);
                            $send_state = 1;
                        } catch (Exception $e){
                            $errInfo = $e->getMessage();
                        }
                    }
                }
                
                // 记录发送结果
                try {
                    $this->update(array('state' => $send_state, 'send_time' => $now, 'err_info' => $errInfo), "id = ".$mailId);
                } catch (Exception $e) {
                    $errInfo = $e->getMessage();
                }
            }else{
                $errInfo = "邮件已过期";
            }
        }else{
            $errInfo = "邮件ID为空";
        }
        
        $result = array(
                'success'   => $errInfo ? false : true,
                'info'      => $errInfo
        );
        
        return $result;
    }
}