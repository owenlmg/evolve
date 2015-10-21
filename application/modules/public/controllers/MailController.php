<?php
/**
 * 2013-9-8 下午2:53:55
 * @author x.li
 * @abstract 
 */
class Public_MailController extends Zend_Controller_Action
{
    public function sendAction()
    {
        // 返回值数组
        $result = array();
        
        // 请求参数
        // $request = $this->getRequest()->getParams();
        
        $now = date('Y-m-d H:i:s');
        
        /* $data = array(
                'subject'       => $request['subject'],
                'to'            => $request['to'],
                'to_name'       => $request['to_name'],
                'cc'            => $request['cc'],
                'cc_name'       => $request['cc_name'],
                'content'       => $request['content'],
                'attachment'    => $request['attachment']
        ); */
        
        $data = array(
                'subject'       => 'test',
                'to'            => '14706931@qq.com',
                'to_name'       => '新大陆',
                'cc'            => 'leonli188@126.com',
                'cc_name'       => 'leon',
                'content'       => 'test123测试',
                'charset'       => 'utf-8',
                'attachment'    => null
        );
        echo '<pre>';
        print_r($data);
        
        $mailConfig = new Zend_Config_Ini(CONFIGS_PATH.'/application.ini','mail');
        $from = $mailConfig->smtp->from;
        $fromname = $mailConfig->smtp->fromname;
        $transport = new Zend_Mail_Transport_Smtp($mailConfig->smtp->server, $mailConfig->smtp->params->toArray());
        
        $mail = new Zend_Mail();
        $mail->setSubject($data['subject']);
        $mail->setBodyText($data['content'], $data['charset']);
        $mail->setFrom($from, $fromname);
        $mail->addTo($data['to'], $data['to_name']);
        $mail->addCc($data['cc'], $data['cc_name']);
        $mail->addAttachment('MailController.php');
        $mail->createAttachment(file_get_contents('E:\\sina.png'), 'image/png', Zend_Mime::DISPOSITION_INLINE  , Zend_Mime::ENCODING_BASE64 , 'sina.png');
        
        print_r($mail->send($transport));
        
        //echo Zend_Json::encode($result);
        
        exit;
    }
}