<?php
/**
 * 2013-9-6 下午11:13:14
 * @author x.li
 * @abstract 
 */
class Public_Model_Mail
{
    public function test()
    {
        return '123';
    }
    /* protected $_data = array();
    protected $_from = null;
    protected $_fromname = null;
    protected $_mailConfig = null;
    protected $_transport = null;
    
    public function __construct($data = array())
    {
        $this->_data = $data;
        $this->_mailConfig = new Zend_Config_Ini(CONFIGS_PATH.'/application.ini','mail');
        $this->_from = $this->_mailConfig->smtp->from;
        $this->_fromname = $this->_mailConfig->smtp->fromname;
        $this->_transport = new Zend_Mail_Transport_Smtp($this->_mailConfig->smtp->server, $this->_mailConfig->smtp->params->toArray());
    }
    
    public function send()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '发送成功'
        );
        
        if(count($this->_data) > 0){
            $mail = new Zend_Mail();
            $mail->setBodyText($this->_data['content']);
            $mail->setFrom($this->_from, $this->_fromname);
            $mail->addTo($this->_data['to'], $this->_data['to_name']);
            $mail->addCc($this->_data['cc'], $this->_data['cc_name']);
            //$mail->addAttachment($this->_data['attachment']);
            $mail->setSubject($this->_data['subject']);
            
            $result['success'] = $mail->send($this->_transport);
        }else{
            $result = array(
                    'success'   => false,
                    'info'      => '收件人为空，发送失败！'
            );
        }
        
        return $result;
    } */
}