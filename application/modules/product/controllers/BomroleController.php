<?php
/**
 * 2014-4-5
 * @author      mg.luo
 * @abstract    bom权限
 */
class Product_BomroleController extends Zend_Controller_Action
{

    public function indexAction()
    {

    }

    public function getlistAction() {
        $request = $this->getRequest()->getParams();
        $limit = $request['limit'];
        $start = $request['start'];
        $group = $request['group'];
        $whereSearch = "1=1";
        foreach ($request as $k => $v) {
            if ($v) {
                $col = str_replace('search_', '', $k);
                if ($col != $k) {
                    // 查询条件
                    $whereSearch .= " and ifnull(t1." . $col . ",'') like '%" . $v . "%'";
                }
            }
        }

        $bomrole = new Product_Model_Bomrole();
        // 获取物料数据
        if($group == 'bom') {
            $resutl = array(
                "totalCount" => $bomrole->countByBom($whereSearch),
                "topics" => $bomrole->selectByBom($whereSearch, $start, $limit)
            );
        } else {
            $resutl = array(
                "totalCount" => $bomrole->countByUser($whereSearch),
                "topics" => $bomrole->selectByUser($whereSearch, $start, $limit)
            );
        }
        // 转为json格式并输出
        echo Zend_Json::encode($resutl);

        exit;
    }

    /**
     * @abstract    保存
     * @return      null
     */
    public function saveAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '保存成功'
        );

        $request = $this->getRequest()->getParams();

        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];

        $bomId = $request['code_id'];
        $userId = $request['employee_id'];
        $bomIds = array();
        $userIds = array();
        if($bomId) {
            $bomIds = explode(',', $bomId);
        }
        if($userId) {
            $userIds = explode(',', $userId);
        }
        $bomrole = new Product_Model_Bomrole();
        $fa = new Product_Model_Fa();
        $emp = new Hra_Model_Employee();
        try{
            foreach($bomIds as $b) {
                $bomCodes = $fa->getById($b);
                $bomCode = $bomCodes['code'];
                foreach($userIds as $u) {
                    $userNames = $emp->getById($u);
                    $userName = $userNames['cname'];
                    $data = array(
                        'bom' => $bomCode,
                        'employee_id' => $u,
                        'employee_name' => $userName,
                        'relation' => '管理员增加',
                        'create_user' => $user,
                        'create_time' => $now
                    );

                    $bomrole->insert($data);
                }
            }
        } catch (Exception $e){
            $result['result'] = false;
            $result['info'] = $e->getMessage();
            echo Zend_Json::encode($result);
            exit;
        }
        echo Zend_Json::encode($result);
        exit;
    }

    /**
     * @abstract    删除
     * @return      null
     */
    public function removeAction()
    {
        // 返回值数组
        $result = array(
            'success'   => true,
            'info'      => '删除成功'
        );

        $request = $this->getRequest()->getParams();
        $bomrole = new Product_Model_Bomrole();
        $boms = $request['boms'];
        $userIds = $request['userIds'];

        try{
            if($boms && $userIds) {
                $bomrole->remove($boms, $userIds);
            }
        } catch (Exception $e){
            $result['result'] = false;
            $result['info'] = $e->getMessage();
            echo Zend_Json::encode($result);
            exit;
        }
        echo Zend_Json::encode($result);
        exit;
    }

}

