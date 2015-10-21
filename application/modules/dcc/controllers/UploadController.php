<?php

/**
 * 2013-7-31
 * @author      mg.luo
 * @abstract    文件管理
 */
class Dcc_UploadController extends Zend_Controller_Action {

    public function indexAction() {

    }

    /**
     * @abstract    获取文件JSON数据
     * @return      null
     */
    public function getfilesAction() {
        $this->archiveFile();
        // 请求参数
        $request = $this->getRequest()->getParams();
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];
        $dept = $user_session->user_info['dept_id'];

        $archive = 0;
        if (isset($request['search_archive']) && ($request['search_archive'] == '已归档' || $request['search_archive'] == '1')) {
            $archive = 1;
        }
        $where = "1 = 1";
        // 是否用于文件归档
        if (isset($request['type']) && $archive == 0) {
            $where = "t1.archive = 0";
        }
        if (!isset($request['all'])) {
            $where .= " and t1.private=0";
        }
        if(isset($request['full'])) {
            $where = "1=1";
        }

        foreach ($request as $k => $v) {
            if ($v != '') {
                if ("search_upload_date_from" == $k && $v) {
                    $where .= " and t1.upload_time >= '" . str_replace('T', ' ', $v) . "'";
                } else if ("search_upload_date_to" == $k && $v) {
                    $where .= " and t1.upload_time <= '" . str_replace('T00:00:00', ' 23:59:59', $v) . "'";
                } else if ("search_archive_date_from" == $k && $v) {
                    $where .= " and t1.archive_time >= '" . str_replace('T', ' ', $v) . "'";
                } else if ("search_archive_date_to" == $k && $v) {
                    $where .= " and t1.archive_time <= '" . str_replace('T00:00:00', ' 23:59:59', $v) . "'";
                } else if ("search_archive" == $k) {
                    if(!isset($request['full']) || $request['full'] != 1)
                        $where .= " and t1.archive = $archive";
                } else if ("search_del" == $k) {
                    $where .= " and t1.del = $v";
                } else {
                    $col = str_replace('search_', '', $k);
                    if ($col != $k) {
                        // 查询条件
                        $where .= " and ifnull(t1." . $col . ",'') like '%" . $v . "%'";
                    }
                }
            }
        }

        $upload = new Dcc_Model_Upload();
        if (isset($request['full'])) {
            $where1 = $where2 = "";
        } else {
            $where1 = " and (t1.create_user = $user or t1.update_user = $user)";
            $where2 = " and (FIND_IN_SET($user, share_user) or FIND_IN_SET($dept, share_dept))";
        }

        $data = $upload->getFilesList($where . $where1, $where . $where2);
        $employee = new Hra_Model_Employee();
        for ($i = 0; $i < count($data); $i++) {
            $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
            $data[$i]['update_time'] = strtotime($data[$i]['update_time']);
            $data[$i]['upload_time'] = strtotime($data[$i]['upload_time']);
            $data[$i]['archive'] = $data[$i]['archive'] == 1 ? true : false;

            // 共享期间
            $begin = $data[$i]['share_time_begin'];
            $end = $data[$i]['share_time_end'];
            $data[$i]['share_time'] = $begin == '2013-01-01' ? '' : $begin;
            $data[$i]['share_time'] .= "~";
            $data[$i]['share_time'] .= $end == '2099-12-31' ? '' : $end;
            $data[$i]['share_time'] = $data[$i]['share_time'] == '~' ? '' : $data[$i]['share_time'];
            $data[$i]['share_time_begin'] = strtotime($data[$i]['share_time_begin']);
            $data[$i]['share_time_end'] = strtotime($data[$i]['share_time_end']);

            // 共享给个人
            $share_user = $data[$i]['share_id'];
            if ($share_user) {
                $share = str_replace("E", "", $share_user);
                $share_name = $employee->getInfosByOneLine($share_user);
                $data[$i]['share_name'] = $share_name['cname'];
            }
            // 共享给部门
            $share_dept = $data[$i]['share_dept'];
            if ($share_dept) {
                $share = str_replace("D", "", $share_dept);
                $share_name = $upload->getDeptNames($share_dept);
                $data[$i]['share_dept_name'] = $share_name['dept_name'];
            }
        }

        echo Zend_Json::encode($data);

        exit;
    }

    public function gettreeAction() {
        // 请求参数
        $request = $this->getRequest()->getParams();
        if(isset($request['name']) && $request['name']) {
            $name = $request['name'];
            $where = "cname like '%$name%'";
        } else {
            $where = "1=1";
        }

        $node = isset($request['node']) ? $request['node'] : 0;
        $node = $node == 'root' ? 0 : $node;

        $method = isset($request['method']);

        $upload = new Dcc_Model_Upload();

        $data = $upload->getTree($node);

        if (!$method) {
            $data = $upload->getUserTree($data, $where);
            // 去掉没有用户的部门
            $data = $this->removeEmpty($data);
        }

        // 将模块数据转为json格式并输出
        echo "[" . Zend_Json::encode($data) . "]";

        exit;
    }
    
    public function removeEmpty($data) {
        $d = $data['children'];
        for($i = 0; $i < count($d); $i++) {
            if(strpos($d[$i]['id'], "D") !== false) {
                if(isset($d[$i]['children']) && $d[$i]['children'] && count($d[$i]['children']) > 0) {
                    $d[$i] = $this->removeEmpty($d[$i]);
                } else {
                    if($d[$i]['leaf'] == false) {
//                         unset($d[$i]);
                        array_splice($d, $i, 1);
                        $i--;
                    }
                }
                
            }
        }
        $data['children'] = $d;
        return $data;
    }

    /**
     * @abstract    添加新文件
     * @return      null
     */
    public function saveAction() {
        // 返回值数组
        $result = array(
            'success' => true,
            'result' => true,
            'convert' => true,
            'info' => '保存成功'
        );

        $request = $this->getRequest()->getParams();

        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];
        if(!$user) {
            $user = $this->getRequest()->getParam('employee_id');
        }

        $val = (object) $request;
        $private = 0;
        if (isset($request['private'])) {
            $private = 1;
        }

        $upload = new Dcc_Model_Upload();
        // 新增还是编辑
        if ($val->id) {
            if (isset($_FILES['file']) && $_FILES['file']['name']) {
                $file = $_FILES['file'];
                $fileSize = $file['size'];

                $fileType = strrchr($file['name'], ".");
                $fname_arr = explode(".", $file['name']);
                $fileName = $file['name'];
                // 检查本人上传的同名文件已存在
//                if ($upload->fetchAll("id != $val->id and name = '$fileName' and update_user = $user")->count() > 0) {
//                    $result['result'] = false;
//                    $result['info'] = '文件已存在';
//
//                    echo Zend_Json::encode($result);
//
//                    exit;
//                }
//                $savepath = $_SERVER['DOCUMENT_ROOT']."/evolve/upload/files/".date('Y-m-d')."/";
                $savepath = "../upload/files/" . date('Y-m-d') . "/";
                $fujian_name_tmp = $this->randomkeys(15) . strtolower($fileType);
                if (!is_dir($savepath)) {
                    mkdir($savepath);
                }
                $path = $savepath . $fujian_name_tmp;
                move_uploaded_file($file["tmp_name"], $path);
                $fileType = str_replace('.', '', $fileType);
                if (!$fileSize) {
                    $fileSize = $_SERVER['CONTENT_LENGTH'];
                }

                $data = array(
                    'category' => $val->category,
                    'name' => $fileName,
                    'type' => $fileType,
                    'size' => $fileSize,
                    'path' => $path,
                    'description' => $val->description,
                    'remark' => $val->remark,
                    'private' => $private,
                    'upload_time' => $now,
                    'update_time' => $now,
                    'update_user' => $user
                );
            } else {
                $data = array(
                    'category' => $val->category,
                    'description' => $val->description,
                    'remark' => $val->remark,
                    'private' => $private,
                    'update_time' => $now,
                    'update_user' => $user
                );
            }
            $id = $val->id;
            $where = "id = " . $id;
            try {
                $upload->update($data, $where);

                if ($id && ($val->employeeId || $val->deptId)) {
                    $share = new Dcc_Model_Share();
                    $where = "shared_id = " . $id;
                    if ($share->fetchAll($where)->count() > 0) {
                        // 更新
                        $shareData = array(
                            'type' => 'upload',
                            'share_user' => str_replace('E', '', $val->employeeId),
                            'share_dept' => str_replace('D', '', $val->deptId),
                            'share_time_begin' => $val->share_time_begin ? $val->share_time_begin : '2013-01-01',
                            'share_time_end' => $val->share_time_end ? $val->share_time_end : '2099-12-31'
                        );

                        $share->update($shareData, $where);
                    } else {
                        // 插入
                        $shareData = array(
                            'type' => 'upload',
                            'shared_id' => $id,
                            'share_user' => str_replace('E', '', $val->employeeId),
                            'share_dept' => str_replace('D', '', $val->deptId),
                            'share_time_begin' => $val->share_time_begin,
                            'share_time_end' => $val->share_time_end,
                            'create_user' => $user,
                            'create_time' => $now
                        );
                        $share->insert($shareData);
                    }
                    // 共享
                }
            } catch (Exception $e) {
                $result['result'] = false;
                $result['info'] = $e->getMessage();

                echo Zend_Json::encode($result);

                exit;
            }

            echo Zend_Json::encode($result);

            exit;
        } else {
            if(!isset($val->uploadedFileIds)) {
                // 没有上传文件
                $result['result'] = false;
                $result['info'] = '没有上传文件';
                echo Zend_Json::encode($result);
                exit;
            }
            $filesarr = explode(',', $val->uploadedFileIds);
            foreach($filesarr as $id) {
                $data = array(
                        'category' => isset($val->category) ? $val->category : null,
                        'description' => $val->description,
                        'remark' => $val->remark,
                        'private' => $private
                );
                $where = "id = $id";
                try {
                    $rows = $upload->update($data, $where);
                    if ($rows && ($val->employeeId || $val->deptId)) {
                        $share = new Dcc_Model_Share();
                        // 共享
                        $shareData = array(
                                'type' => 'upload',
                                'shared_id' => $id,
                                'share_user' => str_replace('E', '', $val->employeeId),
                                'share_dept' => str_replace('D', '', $val->deptId),
                                'share_time_begin' => $val->share_time_begin ? $val->share_time_begin : '2013-01-01',
                                'share_time_end' => $val->share_time_end ? $val->share_time_end : '2099-12-31',
                                'create_user' => $user,
                                'create_time' => $now
                        );
                        $share->insert($shareData);
                    }
                } catch (Exception $e) {
                    $result['result'] = false;
                    $result['info'] = $e->getMessage();
                }
            }

            echo Zend_Json::encode($result);

            exit;
        }
    }

    /**
     * @abstract    删除文件
     * @return      null
     */
    public function removeAction() {
        // 返回值数组
        $result = array(
            'success' => true,
            'result' => true,
            'info' => '删除成功'
        );

        $request = $this->getRequest()->getParams();

        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];

        $ids = json_decode($request['id']);

        $upload = new Dcc_Model_Upload();

        foreach ($ids as $id) {
            $where = "id = $id";
            try {
                $upload->delete($where);
                // 删除文件
            } catch (Exception $e) {
                $result['result'] = false;
                $result['info'] = $e->getMessage();

                echo Zend_Json::encode($result);

                exit;
            }
        }
        echo Zend_Json::encode($result);
        exit;
    }
    
    public function multiuploadAction() {
        // 返回值数组
        $result = array(
            'success' => true,
            'result' => true
        );
        
        $upload = new Dcc_Model_Upload();
        $request = $this->getRequest()->getParams();
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];
        if(!$user) {
            $user = $this->getRequest()->getParam('employee_id');
        }

        $now = date('Y-m-d H:i:s');
        $val = (object) $request;
        
        $file = $_FILES['file'];
        $fileSize = $file['size'];
        $fname_arr = explode(".", $file['name']);
        $fileName = $file['name'];
        $fileType = strrchr($file['name'], ".");
        // 检查本人上传的同名文件已存在
        if ($upload->fetchAll("name = '$fileName' and update_user = $user")->count() > 0) {
            $result['result'] = false;
            $result['info'] = '文件已存在';

            echo Zend_Json::encode($result);

            exit;
        }
        $absolutepath = $_SERVER['DOCUMENT_ROOT'] . "/evolve/upload/files/" . date('Y-m-d') . "/";
        $savepath = "../upload/files/" . date('Y-m-d') . "/";
        $fujian_name_tmp = $this->randomkeys(15) . strtolower($fileType);
        if (!is_dir($savepath)) {
            mkdir($savepath);
        }
        $path = $savepath . $fujian_name_tmp;
        move_uploaded_file($file["tmp_name"], $path);
        $fileType = str_replace('.', '', $fileType);
        if (!$fileSize) {
            $fileSize = $_SERVER['CONTENT_LENGTH'];
        }

        $data = array(
            'category' => isset($val->category) ? $val->category : null,
            'name' => $fileName,
            'type' => $fileType,
            'size' => $fileSize,
            'path' => $path,
            'upload_time' => $now,
            'create_time' => $now,
            'create_user' => $user,
            'update_time' => $now,
            'update_user' => $user
        );

        try {
            $id = $upload->insert($data);
            if ($id) {
                $result['id'] = $id;
            }
        } catch (Exception $e) {
            $result['result'] = false;
            $result['info'] = $e->getMessage();

            echo Zend_Json::encode($result);

            exit;
        }

        echo Zend_Json::encode($result);

        exit;
    
        // 返回值数组
        $result = array(
            'success' => true,
            'result' => true,
            'info' => $user
        );
        echo Zend_Json::encode($result);
        exit;
    }

    /*
     * 生成随机数
     */

    function randomkeys($length) {
        $key = '';

        $pattern = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ'; //字符池
        for ($i = 0; $i < $length; $i++) {
            $key .= $pattern{mt_rand(0, 35)}; //生成php随机数
        }
        return $key;
    }
    
    /**
     * 处理显示未归档但实际已归档的文件
     */
    private function archiveFile() {
        $upload = new Dcc_Model_Upload();
        $files = (new Dcc_Model_Files())->getName();
        $id_name = $upload->getName().'.id';
        $ids_name = $files.'.file_ids';
        $join = array(
                array(
                        'type' => 5,
                        'table' => $files,
                        'condition' => "FIND_IN_SET($id_name, $ids_name)"
                )
        );
        $where = $upload->getName().".archive=0 and ".$upload->getName().".del=0 and ".$upload->getName().".private=0 and ".$files.".state='Active'";
        $data = $upload->getJoinList($where, $join);
        foreach($data as $row) {
            $id = $row['id'];
            $upload->update(array('archive' => 1), array('id=?' => $id));
        }
    }

}