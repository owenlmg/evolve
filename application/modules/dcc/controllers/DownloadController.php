<?php

/**
 * 2013-8-2
 * @author      mg.luo
 * @abstract    文件下载
 */
class Dcc_DownloadController extends Zend_Controller_Action {

    public function indexAction() {
        
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
        $source = isset($request['source']) ? $request['source'] : '';

        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];

        $upload = new Dcc_Model_Upload();
        $record = new Dcc_Model_Record();

        $file = $upload->getOne($id);
        $path = $file['path'];
        $name = $file['name'];
        
        if($source == 'edit') {
            $source = '日常维护';
        } else if($source == 'files') {
            $source = '文件检索';
        } else if($source == 'mine') {
            $source = '我的文档';
        }
        if (count($file) > 0 && $path) {
            $data = array(
                'type' => "files",
                'table_name' => "oa_doc_upload",
                'table_id' => $id,
                'handle_user' => $user,
                'handle_time' => $now,
                'action' => "下载",
                'ip' => $_SERVER['REMOTE_ADDR'],
                'source' => $source
            );

            try {
                $record->insert($data);
                $this->operate("下载");
            } catch (Exception $e) {
                $result['result'] = false;
                $result['info'] = $e->getMessage();

                echo Zend_Json::encode($result);

                exit;
            }

            $filename = $name;
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

            readfile($path);

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

    public function exportcsvAction() {
        $request = $this->getRequest()->getParams();
        $data = json_decode($request['data']);
        $date = date('YmdHsi');
        $path = "../temp/files_" . $date . ".csv";
        $file = fopen($path, "w");
        $head = ['文件号', '版本', '文件名', '状态', '关键字', '描述', '升版原因', '备注', '归档时间', '申请人', '申请时间'];
        foreach ($head as $i => $v) {
            $head[$i] = iconv('utf-8', 'gbk', $v);
        }
        $iHead = true;
        foreach ($data as $row) {
            if ($iHead) {
                fputcsv($file, $head);
                $iHead = false;
            }
            $d = $this->object_array($row);
            fputcsv($file, $d);
        }

        fclose($file);
        $this->operate("导出");

        echo "files_" . $date;

        exit;
    }

    function downcsvAction() {
        $request = $this->getRequest()->getParams();
        $path = $request['path'];
        if(strpos($path, '.zip') != false) {
            $path = "../temp/" . $path;
        } else {
            $path = "../temp/" . $path . ".csv";
        }

        $filename = basename($path);
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

        readfile($path);
        exit;
    }

    /**
     * 
     * 把对象类型转换为数组类型，并转码
     * @param object $array
     * @return array $a
     */
    private function object_array($array) {
        $a = array();
        foreach ($array as $key => $v) {
            if (preg_match('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $v)) {
                $v = str_replace("T", " ", $v);
            }
            $a[$key] = iconv('utf-8', 'gbk', $v);
        }
        return $a;
    }

    private function operate($type) {
        // 记录日志
        $operate = new Application_Model_Log_Operate();

        $now = date('Y-m-d H:i:s');

        $computer_name = gethostbyaddr(getenv("REMOTE_ADDR"));

        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['user_id'];
        
        $data = array(
            'user_id' => $user,
            'operate' => $type,
            'target' => 'Dcc',
            'computer_name' => $computer_name,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'time' => $now
        );

        $operate->insert($data);
    }

}