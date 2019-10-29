<?php
/**
 *
 * @author Administrator
 */
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    function _initHeader(){
        //date_default_timezone_set("PRC");
        header("Content-Type:text/html;charset=utf-8");

        $config = new Zend_Config_Ini(CONFIGS_PATH.'/application.ini', 'production');
        defined("SYS_NAME") || define("SYS_NAME", $config->sys->name);
        defined("SYS_COPYRIGHT") || define("SYS_COPYRIGHT", $config->sys->copyright.date('Y'));
        defined("SYS_EMAIL_SUFFIX") || define("SYS_EMAIL_SUFFIX", $config->sys->email_suffix);
        defined("SYS_REVIEW_EMAIL") || define("SYS_REVIEW_EMAIL", $config->email->review);

        $page_head = '<title>'.SYS_NAME.'</title>
                      <link type="image/x-icon" rel=icon href="'.HOME_PATH.'/public/images/favicon.ico">
                      <link rel="stylesheet" type="text/css" href="'.HOME_PATH.'/public/css/style.css"/>
                      <link rel="stylesheet" type="text/css" href="'.HOME_PATH.'/public/css/icon.css"/>
                      <link rel="stylesheet" type="text/css" href="'.HOME_PATH.'/library/ext/resources/css/ext-all.css">
                      <link rel="stylesheet" type="text/css" href="'.HOME_PATH.'/public/css/BoxSelect.css">
                      <script type="text/javascript" src="'.HOME_PATH.'/library/ext/ext-all.js"></script>
                      <script type="text/javascript" src="'.HOME_PATH.'/library/ext/locale/ext-lang-zh_CN.js"></script>
                      <script type="text/javascript" src="'.HOME_PATH.'/public/js/BoxSelect.js"></script>
                      <script type="text/javascript" src="'.HOME_PATH.'/public/js/EmployeeCombo.js"></script>
                      <script type="text/javascript">Ext.override(Ext.view.Table, { enableTextSelection: true });var homePath="'.HOME_PATH.'";</script>';
        defined("SYS_HEAD") || define("SYS_HEAD", $page_head);

        $router = new Zend_Controller_Router_Rewrite();
        $request = new Zend_Controller_Request_Http();
        $router->route($request);
        $controller = $request->getControllerName();
        $action = $request->getActionName();
        // 已登录验证过滤
        if(($controller != 'home' && $controller != 'index' && $controller != 'login' && $controller != 'hra' && $controller != 'admin' && $controller != 'dcc')
            || ($controller == 'hra' && $action != 'news')
            || ($controller == 'admin' && $action != 'cron')
            || ($controller == 'dcc' && $action != 'upload')){
            $user_session = new Zend_Session_Namespace('user');

            if(!isset($user_session->user_info)){
                exit("<script>window.location.href='".HOME_PATH."/public/home/login';</script>");
            }
        }
    }

    protected function _initAppAutoload() {
        $autoloader = new Zend_Loader_Autoloader_Resource(array(
            'namespace' => 'Application',
            'basePath' => APPLICATION_PATH,
            'resourceTypes' => array(
                'model' => array(
                    'path' => 'models/',
                    'namespace' => 'Model_',
                ),
                'model_log' => array(
                    'path' => 'models/log',
                    'namespace' => 'Model_Log_'
                )
            )
        ));
        return $autoloader;
    }
}

