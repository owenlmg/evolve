<?php

/**
 * 2013-7-31
 * @author      mg.luo
 * @abstract    文件管理
 */
class Dcc_SendController extends Zend_Controller_Action {

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
        
        $dept = new Hra_Model_Dept();
        $deptdata = $dept->getList();

        $where = " 1=1 ";
        if(isset($request['search_key']) && $request['search_key']) {
            $where .= " and (file_names like '%" . $request['search_key'] . "%' 
                    or doc_names like '%" . $request['search_key'] . "%' 
                    or subject like '%" . $request['search_key'] . "%')";
        }
        if (isset($request['search_object']) && $request['search_object'])
            $where .= " and outsendtype = '" . $request['search_object'] . "'";
        
        if (isset($request['search_objectname']) && $request['search_objectname']) {
            $where .= " and (partner like '%" . $request['search_objectname'] . "%' ";
            
            foreach($deptdata as $dd) {
                if(strpos($dd['name'], $request['search_objectname']) !== false) {
                    $deptid = $dd['id'];
                    $where .= " or dept = $deptid";
                }
            }
            
            $sql = "select group_concat(code) as codes from oa_bpartner where cname like '%" . $request['search_objectname'] . "%' or ename like '%" . $request['search_objectname'] . "%'";
            $depts = $dept->getAdapter()->query($sql)->fetchObject();
            if($depts && $depts->codes) {
                $ds = explode(',', $depts->codes);
                for($i = 0; $i < count($ds); $i++) {
                    $ds[$i] = "'".$ds[$i]."'";
                }
                
            }
            if(isset($ds)) {
                $where .= " or partner in (".implode(',', $ds)."))";
            } else {
                $where .= ")";
            }
        }
        
        if (isset($request['search_date_from']) && $request['search_date_from'])
            $where .= " and handle_time >= '" . $request['search_date_from'] . " 00:00:00'";
        
        if (isset($request['search_date_to']) && $request['search_date_to'])
            $where .= " and handle_time <= '" . $request['search_date_to'] . " 23:59:59'";
        
        if (isset($request['search_cc']) && $request['search_cc']) {
            $where .= " and (t1.to like '%" . $request['search_cc'] . "%' or t1.cc like '%" . $request['search_cc'] . "%')";
        }
        if (isset($request['search_sendtype']) && $request['search_sendtype']) {
            $where .= " and sendtype = '" . $request['search_sendtype'] . "'";
        }

        $send = new Dcc_Model_Send();
        $data = $send->getList($where, $start, $limit);
        
        for ($i = 0; $i < count($data); $i++) {
            $data[$i]['handle_time'] = strtotime($data[$i]['handle_time']);
            if($data[$i]['cname']) {
                $data[$i]['partner_name'] = $data[$i]['partner'].$data[$i]['cname'];
            } else if($data[$i]['ename']) {
                $data[$i]['partner_name'] = $data[$i]['partner'].$data[$i]['ename'];
            } else {
                $data[$i]['partner_name'] = $data[$i]['partner'];
            }
            if($data[$i]['sendtype'] == '内发' && $data[$i]['dept']) {
                // 获取部门名称
                $dept_name = array();
                foreach(explode(',', $data[$i]['dept']) as $d) {
                    foreach($deptdata as $dept) {
                        if($dept['id'] == $d) {
                            $dept_name[] = $dept['name'];
                        }
                    }
                }
                $data[$i]['partner_name'] = implode(',', $dept_name);
            }
            if($data[$i]['linkman']) {
                $data[$i]['linkman'] = $data[$i]['contact_name'].'('.$data[$i]['linkman'].')';
            }
            // 产品型号
            if($data[$i]['doc_names']) {
                $codes = array();
                foreach(explode(',', $data[$i]['doc_names']) as $code) {
                    $codes[] = "'".$code."'";
                }
                $sql = "select GROUP_CONCAT(t1.model_internal) as model from oa_product_catalog t1 inner join oa_doc_code t2 on t1.id = t2.project_no where t2.code in (".implode(',', $codes).")";
                $res = $send->getAdapter()->query($sql)->fetchObject();
                if($res && $res->model) {
                    $data[$i]['model'] = $res->model;
                }
            }
            
            // 去冗余
            /* $rids = array();
            $rcodes = array();
            $ids = explode(',', $data[$i]['doc_ids']);
            $codes = explode(',', $data[$i]['doc_names']);
            $name = $data[$i]['file_names'];
            $fileids = $data[$i]['file_ids'];
            $k = 0;
            if(count($ids) > 1 && count(explode(',', $fileids)) == 0) {
                foreach($ids as $id) {
                    $code = $codes[$k++];
                    if($send->getAdapter()->query("select t1.id from oa_doc_files t1  where FIND_IN_SET(t1.file_ids, $fileids) and state = 'Active' and t1.id = $id and FIND_IN_SET(t1.name, '$name') ")->rowCount() > 0) {
                        $rids[] = $id;
                        $rcodes[]  = $code;
                    }
                }
                $data[$i]['doc_ids'] = implode(',', $rids);
                $data[$i]['doc_names'] = implode(',', $rcodes);
            } */
        }

        $resutl = array(
            "totalCount" => $send->getAdapter()->query("select t1.id from oa_doc_send t1 where ($where)")->rowCount(),
            "topics" => $data
        );
        echo Zend_Json::encode($resutl);

        exit;
    }
    
    public function filesendAction() {
        $this->render('/filesend');
    }
    
    /**
     * 获取业务伙伴信息列表
     */
    public function getpartnerAction()
    {
        // 请求参数
        $search_code = $this->getRequest()->getParam('search_code');
        $type = $this->getRequest()->getParam('type');
        
        if($type != 1) {
            $type = 0;
        }
        $partner = new Erp_Model_Partner();
        
        $where = "type = $type and (code like '$search_code%' or cname like '%$search_code%' or ename like '%$search_code%')";
        $data = $partner->getJoinList($where, array(), array('id', 'code', 'cname', 'ename'));
        $result = array();
        for($i = 0; $i < count($data); $i++) {
            $row['id'] = $data[$i]['id'];
            $row['code'] = $data[$i]['code'];
            if($data[$i]['cname']) {
                $row['name'] = $data[$i]['code'].$data[$i]['cname'];
            } else if($data[$i]['ename']){
                $row['name'] = $data[$i]['code'].$data[$i]['ename'];
            } else {
                $row['name'] = $data[$i]['code'];
            }
            $result[] = $row;
        }
        echo Zend_Json::encode($result);
        exit;
    }
    
    /**
     * 获取联系人信息
     */
    public function getlinkmanAction() {
        $partner_id = $this->getRequest()->getParam('partner_id');
        if($partner_id) {
            $contact = new Erp_Model_Contact();
        
            $where = "email != '' and partner_id = $partner_id";
            $data = $contact->getJoinList($where, array(), array('id', 'email', 'name' => "concat(name, '(', email, ')')"));
            echo Zend_Json::encode($data);
            exit;
        } else {
            echo Zend_Json::encode(array());
            exit;
        }
    }

    /**
     * 文件发送
     */
    public function sendAction() {
        // 返回值数组
        $result = array(
            'success' => true,
            'result' => true,
            'info' => '发送成功'
        );

        $request = $this->getRequest()->getParams();

        $now = date('Y-m-d H:i:s');

        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];

        $val = (object) $request;

        $upload = new Dcc_Model_Upload();
        $doc_ids = "";
        $doc_names = "";
        if ($val->exfile_ids) {
            // 根据文件id获取归档文件id
            $tmp = $upload->getAdapter()->query("select group_concat(id) as doc_ids, group_concat(code) as doc_names from oa_doc_files where (state = 'Active') and name != '' and file_ids in ( " . $val->exfile_ids . ")")->fetchObject();
            $doc_ids = $tmp->doc_ids;
            $doc_names = $tmp->doc_names;

            $row = $upload->getFileByIds($val->exfile_ids);
            $names = $row['name'];
            $paths = $row['path'];
        }

        if ($paths) {
            foreach (explode(',', $paths) as $path) {
                if (!is_file($path)) {
                    $result['result'] = false;
                    $result['info'] = "文件不存在";
                    echo Zend_Json::encode($result);
                    exit;
                }
            }
            $employee = new Hra_Model_Employee();
            
            $to = array();
            if($val->to_id) {
                $toUser = str_replace('E', '', $val->to_id);
                $to = $employee->getInfosByOneLine($toUser);
                $to = explode(',', $to['email']);
            }
            if(isset($val->custom_linkman) && $val->custom_linkman) {
                $to[] = $val->custom_linkman;
            }
            if(isset($val->supply_linkman) && $val->supply_linkman) {
                $to[] = $val->supply_linkman;
            }
            $dept = '';
            if(isset($val->innerdept_id) && $val->innerdept_id) {
                $dept = str_replace('D', '', $val->innerdept_id);
                $sql = "select group_concat(email) as email from oa_employee where dept_id in ($dept)";
                $data = $employee->getAdapter()->query($sql)->fetchObject();
                if($data && $data->email) {
                	$toemail = $data->email;
                	foreach(explode(',', $toemail) as $t) {
                	    $to[] = $t;
                	}
                }
            }
            
            $cc = array();
            if($val->cc_id) {
                $ccUser = str_replace('E', '', $val->cc_id);
                $cc = $employee->getInfosByOneLine($ccUser);
                $cc = explode(',', $cc['email']);
            }
            $u = $employee->getInfosByOneLine($user);
            $cc[] = $u->email;
            
            if(isset($val->personal_linkman) && $val->personal_linkman) {
                foreach(explode(',', $val->personal_linkman) as $m) {
                    if(strpos($m, '@') != false) {
                        $to[] = $m;
                    }
                }
            }
            
            $mailData = array(
                'type' => $val->sendtype == '外发' ? "文件外发" : "文件发放",
                'subject' => $val->subject,
                'to' => implode(',', $to),
                'cc' => implode(',', $cc),
                'content' => $val->content,
                'attachment_name' => $names,
                'attachment_path' => $paths,
                'send_time' => $now,
                'add_date' => $now
            );

            $mail = new Application_Model_Log_Mail();
            try {
                $mailId = $mail->insert($mailData);
                if ($mailId) {
                    $sendResult = $mail->send($mailId, 0, $val->to_name, $val->footer,true);
                }
                $error_info = "";
                $success = false;
                if ($sendResult) {
                    $$error_info = $sendResult['info'];
                    $success = $sendResult['success'];
                }
                $result['result'] = $success;
                $result['info'] = $$error_info;
                if (isset($val->out_sendtype)) {
                    $outsend = $val->out_sendtype;
                } else {
                    $outsend = "";
                }
                if(isset($val->out_custom)) {
                    $partner = $val->out_custom;
                } else if(isset($val->out_supply)) {
                    $partner = $val->out_supply;
                } else {
                    $partner = '';
                }
                if(isset($val->custom_linkman)) {
                    $linkman = $val->custom_linkman;
                } else if(isset($val->supply_linkman)) {
                    $linkman = $val->supply_linkman;
                } else {
                    $linkman = '';
                }
                // 获取发放编号
                $code = $this->getSendCode();
                // 记录
                $to = $val->to;
                if(isset($val->personal_linkman) && $val->personal_linkman) {
                    $to .= $val->personal_linkman;
                }
                $data = array(
                    'code'     => $code,
                    'dept' => $dept,
                    'partner' => $partner,
                    'linkman' => $linkman,
                    'sendtype' => $val->sendtype,
                    'to_name' => $val->to_name,
                    'footer' => $val->footer,
                    'remark' => $val->remark,
                    'outsendtype' => $outsend,
                    'to' => $to,
                    'cc' => $val->cc,
                    'subject' => $val->subject,
                    'content' => $val->content,
                    'doc_ids' => $doc_ids,
                    'doc_names' => $doc_names,
                    'file_ids' => $val->exfile_ids,
                    'file_names' => $names,
                    'error_info' => $error_info,
                    'result' => $success,
                    'handle_time' => $now,
                    'handle_user' => $user
                );
                $send = new Dcc_Model_Send();
                $send->insert($data);
            } catch (Exception $e) {
                $result['result'] = false;
                $result['info'] = $e->getMessage();

                echo Zend_Json::encode($result);

                exit;
            }

            if (!$result['info']) {
                $result['info'] = "发送成功";
            }
            echo Zend_Json::encode($result);

            exit;
        } else {
            $result['result'] = false;
            $result['info'] = "文件不存在";
            echo Zend_Json::encode($result);
            exit;
        }
    }

    private function getSendCode() {
    	// 编号规则：OGD+年（XXXX）+月（XX）+日（XX）+批次号（XX）
    	$prefix = "OGD".date('Ymd');
    	$like = $prefix."__";
    	$send = new Dcc_Model_Send();
    	$data = $send->getAdapter()->query("select max(code) as code from oa_doc_send where code like '$like'")->fetchObject();
        if($data && $data->code) {
        	$code = $data->code;
        	$code = $prefix.substr((("1" . str_replace($prefix, "", $code)) + 1), 1);
        } else {
        	$code = $prefix."01";
        }
        return $code;
    }

}