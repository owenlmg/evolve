<?php
/**
 * 2013-7-6 13:41:44
 * @author      x.li
 * @abstract    入口文件
 */
$arr = explode('/', $_SERVER['PHP_SELF']);
$root_dir = $arr[1];
// 站点物理路径
defined("HOME_REAL_PATH") || define("HOME_REAL_PATH", $_SERVER['DOCUMENT_ROOT'].'/'.$root_dir);
// 站点根目录
defined("HOME_PATH") || define("HOME_PATH", 'http://'.$_SERVER['HTTP_HOST'].'/'.$root_dir);
// 应用根目录
defined('APPLICATION_PATH')|| define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));
// 第三方库目录
defined("LIBRARY_PATH") || define("LIBRARY_PATH", realpath(DIRNAME(__FILE__)."/../library"));
// 站点配置文件目录
defined("CONFIGS_PATH") || define("CONFIGS_PATH", REALPATH(APPLICATION_PATH."/configs"));
// 默认以调试方式运行
defined("APPLICATION_ENV") || define("APPLICATION_ENV", (getenv("APPLICATION_ENV") ? getenv("APPLICATION_ENV"):"development"));

set_include_path(implode(PATH_SEPARATOR, array(realpath(LIBRARY_PATH), get_include_path())));

// 自动加载library/Evolve目录下的文件
require_once LIBRARY_PATH."/Evolve/ClassLoader.php";
// 常量
require_once CONFIGS_PATH."/Constant.php";

require_once 'Zend/Application.php';
$application = new Zend_Application(APPLICATION_ENV, CONFIGS_PATH."/application.ini");
$application->bootstrap()->run();