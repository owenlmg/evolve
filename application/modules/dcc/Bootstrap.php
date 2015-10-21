<?php
class Dcc_Bootstrap extends Zend_Application_Module_Bootstrap{
    function _initHeader(){
        header("Content-Type:text/html;charset=utf-8");
    }
}