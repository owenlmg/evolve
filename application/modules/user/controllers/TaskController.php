<?php
/**
 * 2015-02-02
 * @author      larry.luo
 * @abstract    任务管理
 */
class User_TaskController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
    }

    /**
     * 获取当前用户的下级用户
     */
    public function getuserAction() {
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];
        $dept = $user_session->user_info['dept_id'];

        // 查询直接下级
        $result = array(
            'id' => $user,
            'leaf' => true,
            'cname' => $user_session->user_info['user_name'],
            'expanded' => true
        );
        $where = "id != $user and ";
        if($user == 1) {
            // 管理员
            $where .= "1=1";
        } else {
            $where .= "manager_id = ".$user;
        }
        $employee = new Hra_Model_Employee();
        $data = $employee->getJoinList($where, array(), array('id', 'cname', 'number', 'leaf' => new Zend_Db_Expr('true')));
        if(count($data) > 0) {
            array_unshift($data, $result);
            $result['children'] = $data;
        }
        echo Zend_Json::encode($result);
        exit;
    }

    /**
     * 获取事件
     */
    public function geteventAction() {
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];
        // 自己还是下级
        $request = $this->getRequest();
        $employeeId = $request->getParam('employeeId');
        $data = array();
        if($employeeId || $user == $employeeId) {
            // 自己
        } else {
            // 下级

        }
        // {"success":true,"evts":[{"EventId":"64298669-9CDC-4069-A5E0-952CB8FD7334","CalendarId":2,"StartStr":1414944000000,"EndStr":1417021200000,"Title":"测试1177122","IsAllDay":true,"Notes":"阿瑟大时代asdasd","Reminder":"1440"},{"EventId":"FFF534AC-A738-4297-8CE0-42DD9E322704","CalendarId":2,"StartStr":1415116800000,"EndStr":1415145600000,"Title":"sada233","Notes":"但是是IIII"}]}

        $result = array(
            'success' => true,
            'evts' => $data
        );
        echo Zend_Json::encode($result);
        exit;
    }


}

