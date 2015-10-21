<?php
/**
 * 2013-7-6 13:41:44
 * @author      x.li
 * @abstract    系统管理
 */
class Admin_IndexController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
        echo 'admin ini';
    }

    public function indexAction()
    {
        // action body
        echo 'admin';
    }

    /* public function sendmailAction(){
        $mail = new Application_Model_Log_Mail();
    
        $result = $mail->send(1);
        
        print_r($result);
    
        exit;
    } */
}

