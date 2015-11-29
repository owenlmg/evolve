<?php

/**
 * 2013-7-17
 * @author      mg.luo
 * @abstract    文件管理
 */
class Dcc_FilesController extends Zend_Controller_Action {

    public function indexAction() {

    }

    /**
     * @abstract    获取文件JSON数据
     * @return      null
     */
    public function getfilesAction() {
        // 请求参数
        $request = $this->getRequest()->getParams();
        $limit = $request['limit'];
        $start = $request['start'];

        $where = " (t1.state = 'Active' or t1.state='Obsolete')";
        foreach ($request as $k => $v) {
            if ($v) {
                if($k == 'search_tag') {
            	    $cols = array("t1.project_info", "t1.code", "t1.name", "t1.description", "t1.remark", "t2.cname", "t7.model_standard", "t7.model_internal");
            	    $arr=preg_split('/\s+/',trim($v));
            	    for ($i=0;$i<count($arr);$i++) {
            	        $tmp = array();
            	        foreach($cols as $c) {
            	            $tmp[] = "ifnull($c,'')";
            	        }
            	        $arr[$i] = "concat(".implode(',', $tmp).") like '%".$arr[$i]."%'";
            	    }
            	    $where .= " and ".join(' AND ', $arr);
            	    
//             		$where .= " and (ifnull(t1.project_info,'') like '%$v%' or ifnull(t1.code,'') like '%$v%' or ifnull(t1.name,'') like '%$v%' or ifnull(t1.description,'') like '%$v%' or ifnull(t1.remark,'') like '%$v%' or ifnull(t2.cname,'') like '%$v%' or ifnull(t7.model_standard,'') like '%$v%' or ifnull(t7.model_internal,'') like '%$v%')";
            	} else if ("search_archive_date_from" == $k && $v) {
                    $where .= " and t1.archive_time >= '" . str_replace('T', ' ', $v) . "'";
                } else if ("search_category" == $k && $v) {
                    $where .= " and t5.category = '$v'";
                } else if ("search_archive_date_to" == $k && $v) {
                    $where .= " and t1.archive_time <= '" . str_replace('T00:00:00', ' 23:59:59', $v) . "'";
                } else {
                    $col = str_replace('search_', '', $k);
                    if ($col != $k) {
                        // 查询条件
                        $where .= " and ifnull(t1." . $col . ",'') like '%" . $v . "%'";
                    }
                }
            }
        }

        $files = new Dcc_Model_Files();
        $codemaster = new Admin_Model_Codemaster();

        $data = $files->getFilesList($where, $start, $limit);
        $tmp = $files->getAdapter()->query("select count(*) as sum from oa_doc_files t1 left join oa_employee t2 on t1.create_user = t2.id left join oa_doc_code t4 on t1.code = t4.code left join oa_doc_type t5 on t4.prefix = t5.id left join oa_product_catalog t7 on t4.project_no = t7.id where $where")->fetchObject();
	    $count = $tmp->sum;
//        $count = $files->getCount($where, $start, $limit);
        $totalCount = $count;

        for ($i = 0; $i < count($data); $i++) {
            $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
            $data[$i]['update_time'] = strtotime($data[$i]['update_time']);
            $data[$i]['archive_time'] = strtotime($data[$i]['archive_time']);
            $data[$i]['codever'] = $data[$i]['code'] . 'V' . $data[$i]['ver'];
            $data[$i]['description'] = $data[$i]['code_description'];
            if($data[$i]['reason_type']) {
                $masterData = $codemaster->fetchRow("type = 5 and code = '".$data[$i]['reason_type']."'");
                $reason_type_name = $masterData->text;
                $data[$i]['reason_type_name'] = $reason_type_name;
            }
        }
        $resutl = array(
            "totalCount" => $totalCount,
            "topics" => $data
        );
        echo Zend_Json::encode($resutl);

        exit;
    }

    /**
     * 获取文件编码及文件信息
     */
    public function getfilesbyidAction() {
        // 获取参数
        $request = $this->getRequest()->getParams();
        $id = $request['id'];
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];
        $myDept = $user_session->user_info['dept_id'];
        $nowDate = date('Y-m-d');

        $files = new Dcc_Model_Files();
        $record = new Dcc_Model_Record();
        $upload = new Dcc_Model_Upload();
        $codeModel = new Dcc_Model_Code();
	    $share = new Dcc_Model_Share();
	    $review = new Dcc_Model_Review();
	    $modelUser = new Application_Model_User();

        $data = $files->getDataById($id);
        // [id, code, ver, file_id, file_name, file_path, file_view_path]
        $result = array();

        $row0 = $data[0];
        $names = explode(',', $row0['name']);
        $codes = explode(',', $row0['code']);
        $vers = explode(',', $row0['ver']);
        $description = explode('|', $row0['description']);
        $project_info = explode(',', $row0['project_info']);
        $final_codes = array();
        $final_vers = array();
        $final_names = array();
        $final_descs = array();
        $final_projs = array();
        $k = 0;
        for ($i = 0; $i < count($names); $i++) {
            $sub_names = explode('|', $names[$i]);
            $final_codes[$k] = $codes[$i];
            $final_vers[$k] = $vers[$i];
			if(isset($description[$i]) && $description[$i]) {
                $final_descs[$k] = $description[$i];
			} else {
				$final_descs[$k] = "";
			}
			if(isset($project_info[$i]) && $project_info[$i]) {
                $final_projs[$k] = $project_info[$i];
			} else {
				$final_projs[$k] = "";
			}
            $final_names[$k] = $sub_names[0];
            $k++;
            for ($j = 1; $j < count($sub_names); $j++) {
                $final_codes[$k] = $codes[$i];
                $final_vers[$k] = $vers[$i];
                $final_names[$k] = $sub_names[$j];
				if(isset($description[$i]) && $description[$i]) {
					$final_descs[$k] = $description[$i];
				} else {
					$final_descs[$k] = "";
				}
				if(isset($project_info[$i]) && $project_info[$i]) {
					$final_projs[$k] = $project_info[$i];
				} else {
					$final_projs[$k] = "";
				}
                $k++;
            }
        }
        $catalog = new Product_Model_Catalog();
        for ($i = 0; $i < count($data); $i++) {
            $paths = $this->getPathByName($data, trim($final_names[$i]));
            $row = $data[$i];
            $result[$i]['id'] = $row['id'];
            $result[$i]['state'] = $row['state'];
            if($final_descs[$i]) {
                $result[$i]['description'] = $final_descs[$i];
            } else {
                // 获取描述
                $descData = $codeModel->getJoinList("code = '$final_codes[$i]'", array(), array('description', 'project_no'));
                if(count($descData) > 0) {
                    $result[$i]['description'] = $descData[0]['description'];
                    if(!$final_projs[$i]) {
                        $final_projs[$i] = $descData[0]['project_no'];
                    }
                }
            }
            $result[$i]['project_no'] = $final_projs[$i];
            if($final_projs[$i]) {
                $projData = $catalog->getById($final_projs[$i]);
                if($projData && $projData['model_internal']) {
                    $result[$i]['project_name'] = $projData['model_internal'];
                }
            }

            $result[$i]['code'] = $final_codes[$i];
            $result[$i]['ver'] = $final_vers[$i];
            $result[$i]['file_id'] = $paths[0];
            $result[$i]['file_name'] = $final_names[$i];
            $result[$i]['file_path'] = $paths[1];
            $result[$i]['file_view_path'] = $paths[2];

            // 权限检查
	        // 有权限的情况：1、本人上传的文件 2、本人提交的文件评审 3、本人参与审核的文件评审 4、共享给我的 5、将要审核的审核人 6、文件管理员、BOM管理员、物料管理员
	        // 首先检查文件是否存在
	        $result[$i]['exists'] = true;
	        if(!file_exists($paths[1])) {
	        	$result[$i]['exists'] = false;
	        }
	        $role = false;
	        if($modelUser->checkPermissionByRoleName('物料管理员')
	        || $modelUser->checkPermissionByRoleName('文件管理员')
	        || $modelUser->checkPermissionByRoleName('系统管理员')
	        || $modelUser->checkPermissionByRoleName('BOM管理员')) {
	        	$role = true;
	        } else if($row0['create_user'] == $user || $row0['update_user'] == $user) {
	        	$role = true;
	        } else if($record->fetchAll("table_id = $id and handle_user = $user and table_name = 'oa_doc_files' and action = '审批'")->count() > 0) {
	        	$role = true;
	        } else if($upload->fetchAll("id = ".$row['id']." and (create_user = $user or update_user = $user)")->count() > 0) {
	        	$role = true;
	        } else if($share->fetchAll("shared_id = ".$row['id']." and type = 'upload' and share_time_begin <= '$nowDate' and share_time_end >= '$nowDate'  and (FIND_IN_SET($user, share_user) or FIND_IN_SET($myDept, share_dept))")->count() > 0) {
	        	$role = true;
	        } else if($review->fetchAll("file_id = $id and FIND_IN_SET($user, plan_user) and type = 'files'")->count() > 0) {
	        	$role = true;
	        }
	        $result[$i]['role'] = $role;

        }
        echo Zend_Json::encode($result);

        exit;
    }

    private function getPathByName($arrays, $name) {
        for ($i = 0; $i < count($arrays); $i++) {
            $row = $arrays[$i];
            if ($name == $row['file_name']) {
                return array($row['file_id'], $row['path'], $row['view_path']);
            }
        }
    }

    /**
     * @abstract    获取自定义表单内容
     * @return      null
     */
    public function getformvalAction() {
        // 获取参数
        $request = $this->getRequest()->getParams();
        $menu = $request['menu'];

        $formval = new Admin_Model_Formval();

        $data = $formval->getListByMenu($menu);

        echo Zend_Json::encode($data);

        exit;
    }

    /**
     * 文件下载
     */
    public function downloadAction() {
        // 返回值数组
        $result = array(
            'success' => true
        );
        // 获取参数
        $request = $this->getRequest()->getParams();
        $id = $request['id'];
        $name = $request['name'];

        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];

        $files = new Dcc_Model_Files();
        $record = new Dcc_Model_Record();

        $file = $files->getOne($id);
        $path = $file['path'];

        if (count($file) > 0 && $path) {
            $data = array(
                'type' => "files",
                'table_name' => "oa_doc_files",
                'table_id' => $id,
                'handle_user' => $user,
                'handle_time' => $now,
                'action' => "下载",
                'ip' => $_SERVER['REMOTE_ADDR']
            );

            try {
                $record->insert($data);
            } catch (Exception $e) {
                $result['result'] = false;
                $result['info'] = $e->getMessage();

                echo Zend_Json::encode($result);

                exit;
            }

            $result['result'] = true;
            $result['info'] = $path;

            echo Zend_Json::encode($result);

            exit;
        } else {
            // 文件不存在
            // 返回值数组
            $result['result'] = false;
            $result['info'] = '文件不存在';

            echo Zend_Json::encode($result);

            exit;
        }
    }

    /**
     * 文件下载
     */
    public function downloadallAction() {
        // 返回值数组
        $result = array(
            'success' => true
        );
        // 获取参数
        $ids = $this->getRequest()->getParam('ids');

        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];

        $files = new Dcc_Model_Files();
        $record = new Dcc_Model_Record();
        if(!$ids) {
            exit;
        }
        $sql = "select group_concat(file_ids) as fileids from oa_doc_files where id in ($ids)";
        $data = $files->getAdapter()->query($sql)->fetchObject();
        if($data && $data->fileids) {
            $fileids = $data->fileids;
            $uploadSql = "select distinct path, name from oa_doc_upload where id in ($fileids)";
            $uploadData = $files->getAdapter()->query($uploadSql)->fetchAll();
            if($uploadData) {
                $zip=new ZipArchive;
                $time = date('YmdHis');
                $zipFileName = "zip" . $time . ".zip";
                $zipFile = "../temp/$zipFileName";
                $zipPath = "../temp/zip" . $time . "/";
                if (!file_exists($zipPath)) {
                    mkdir($zipPath);
                }
                foreach($uploadData as $upload) {
                    $name = $upload['name'];
                    $path = $upload['path'];
                    $name = iconv('utf-8', 'gbk', $name);
                    $newPath = $zipPath.$name;
                    copy($path, $newPath);
                }

                $zip=new ZipArchive();
                if($zip->open($zipFile, ZipArchive::OVERWRITE)=== TRUE){
                    $datalist = $this->list_dir($zipPath);
                    foreach( $datalist as $val){
                        if(file_exists($val)){
                            $zip->addFile( $val, basename($val));//第二个参数是放在压缩包中的文件名称，如果文件可能会有重复，就需要注意一下
                        }
                    }
                    $zip->close();//关闭
                }
            }
        }
        if(isset($zipFile) && file_exists($zipFile)) {
            $filename = $zipFileName;
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Pragma: can-cache");
            header("Content-type: application/octet-stream");
            $ua = $_SERVER['HTTP_USER_AGENT'];
            if (preg_match('/MSIE/', $ua)) {
                header("Content-disposition: attachment;filename=\"" . urlencode($filename) . "\"");
            } elseif (preg_match('/FireFox/', $ua)) {
                header("Content-disposition: attachment;filename*=\"utf-8''" . $filename . "\"");
            } else {
                header("Content-disposition: attachment;filename=\"" . $filename . "\"");
            }
            
            readfile($zipFile);
            
            exit;
        }
        exit;
    }
    /**
     * 获取文件列表
     * @param unknown $dir
     * @return multitype:
     */
    function list_dir($dir){
        $result = array();
        if (is_dir($dir)){
            $file_dir = scandir($dir);
            foreach($file_dir as $file){
                if ($file == '.' || $file == '..'){
                    continue;
                }
                elseif (is_dir($dir.$file)){
                    $result = array_merge($result, list_dir($dir.$file.'/'));
                }
                else{
                    array_push($result, $dir.$file);
                }
            }
        }
        return $result;
    }
}