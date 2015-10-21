<?php

/**
 * 2013-9-2
 * @author      mg.luo
 * @abstract    在线浏览
 */
class Dcc_OnlineController extends Zend_Controller_Action {

    public function indexAction() {
        
    }

    /**
     * 文件查看
     */
    public function viewAction() {
        // 返回值数组
        $result = array(
            'convert' => false,
            'success' => true
        );
        // 获取参数
        $request = $this->getRequest()->getParams();
        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];
        $id = $request['id'];
        // 根据id获取在线浏览路径
        $upload = new Dcc_Model_Upload();
        $record = new Dcc_Model_Record();

        $file = $upload->fetchRow("id = '$id'");
        if (count($file) > 0 && $file->view_path && is_file($file->view_path)) {
            // swf文件已经存在，直接返回浏览即可
            $result['convert'] = true;
            $viewPath = $file->view_path;
        } else {
            // 文件转换 TODO
            $convertObj = new Dcc_Model_FileCovert();
            $viewPath = "";
            if ($file->path && is_file($file->path) && $convertObj->isSupport($file->type)) {
                // 此处应该使用绝对路径
                $path = str_replace("../", "/evolve/", $file->path);
                $name = substr($path, strripos($path, "/") + 1);
                $path = str_replace($name, "", $path);
                $absolutepath = $_SERVER['DOCUMENT_ROOT'] . $path;
                $viewPath = $convertObj->createPreview($absolutepath, $name);
                if (!$viewPath) {
                    $result['convert'] = false;
                } else {
                    // 转换回相对路径
                    $viewPath = str_replace($_SERVER['DOCUMENT_ROOT'] . "/evolve", "..", $viewPath);
                    $result['convert'] = true;
                    $fileData = array(
                        'view_path' => $viewPath
                    );
                    try {
                        $upload->update($fileData, "id=" . $file->id);
                    } catch (Exception $e) {
                        $result['result'] = false;
                        $result['info'] = $e->getMessage();

                        echo Zend_Json::encode($result);
                        exit;
                    }
                }
            }
        }

        if ($result['convert'] == true) {
            $data = array(
                'type' => "files",
                'table_name' => "oa_doc_upload",
                'table_id' => $file->id,
                'handle_user' => $user,
                'handle_time' => $now,
                'action' => "在线浏览",
                'ip' => $_SERVER['REMOTE_ADDR']
            );

            try {
                $record->insert($data);
                $this->operate("在线浏览");
            } catch (Exception $e) {
                $result['result'] = false;
                $result['info'] = $e->getMessage();

                echo Zend_Json::encode($result);

                exit;
            }

            $result['result'] = true;
            $result['viewpath'] = $viewPath;

            echo Zend_Json::encode($result);

            exit;
        } else {
            // 文件不存在
            // 返回值数组
            $result['result'] = false;
            $result['viewpath'] = '';

            echo Zend_Json::encode($result);

            exit;
        }
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