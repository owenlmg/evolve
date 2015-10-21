<?php

/**
 * 2013-7-31
 * @author      mg.luo
 * @abstract    文件管理
 */
class Dcc_LogController extends Zend_Controller_Action {

    public function indexAction() {
        
    }

    /**
     * @abstract    获取文件JSON数据
     * @return      null
     */
    public function getlistAction() {
        // 请求参数
        $request = $this->getRequest()->getParams();
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];
        $limit = $request['limit'];
        $start = $request['start'];

        $where = "table_name = 'oa_doc_upload'";
        if (isset($request['search_name']) && $request['search_name'])
            $where .= " and t3.name like '%" . $request['search_name'] . "%'";
        if (isset($request['search_description']) && $request['search_description'])
            $where .= " and description like '%" . $request['search_description'] . "%'";
        if (isset($request['search_date_from']) && $request['search_date_from'])
            $where .= " and handle_time >= '" . $request['search_date_from'] . " 00:00:00'";
        if (isset($request['search_date_to']) && $request['search_date_to'])
            $where .= " and handle_time <= '" . $request['search_date_to'] . " 23:59:59'";
        if (isset($request['search_type']) && $request['search_type'])
            $where .= " and action = '" . $request['search_type'] . "'";
        if (isset($request['search_handle_user']) && $request['search_handle_user']) {
            $handler_user = $request['search_handle_user'];
            $employee = new Hra_Model_Employee();
            if(count(($handlers = $employee->fetchAll("ename = '$handler_user' or cname = '$handler_user'")->toArray())) > 0) {
                $whereHandler = "(";
                foreach($handlers as $handler) {
                    if($whereHandler != '(') $whereHandler .= " or ";
                    $whereHandler .= " handle_user = ".$handler['id'];
                }
                $whereHandler .= ")";
            }
            if(isset($whereHandler) && $whereHandler != '()') {
                $where .= " and ".$whereHandler;
            }
        }

        $log = new Dcc_Model_Log();
        $wherePage = $where." and t1.type = 'files'";
        $data = $log->getList($wherePage, $start, $limit);
        for ($i = 0; $i < count($data); $i++) {
            $data[$i]['handle_time'] = strtotime($data[$i]['handle_time']);
        }

        $whereAll = $where." and type='files'";
        $resutl = array(
            "totalCount" => count($log->getList($wherePage, null, null)),
            "topics" => $data
        );
        echo Zend_Json::encode($resutl);

        exit;
    }

}