<?php
/**
 * 2013-11-24 下午11:43:05
 * @author x.li
 * @abstract 
 */
class Product_CatalogController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $user_session = new Zend_Session_Namespace('user');
        
        $this->view->salesDisable = 1;
        $this->view->pmDisable = 1;
        $this->view->reviewDisable = 1;
        
        $this->view->user_id = 0;
        
        if(isset($user_session->user_info)){
            $this->view->user_id = $user_session->user_info['user_id'];
        
            if(Application_Model_User::checkPermissionByRoleName('产品中心-市场')){
                $this->view->salesDisable = 0;
            }
        
            if(Application_Model_User::checkPermissionByRoleName('产品中心-PM')){
                $this->view->salesDisable = 0;
                $this->view->pmDisable = 0;
            }
        
            if(Application_Model_User::checkPermissionByRoleName('产品中心-审核')){
                $this->view->reviewDisable = 0;
            }
        
            if(Application_Model_User::checkPermissionByRoleName('系统管理员')){
                $this->view->salesDisable = 0;
                $this->view->pmDisable = 0;
                $this->view->reviewDisable = 0;
            }
        }
    }
    
    public function getcodelistAction()
    {
        $catalog = new Product_Model_Catalog();
        
        echo Zend_Json::encode($catalog->getCodeList());
        
        exit;
    }
    
    public function getuserlistAction()
    {
        $request = $this->getRequest()->getParams();
        
        $type = isset($request['type']) ? $request['type'] : null;
        
        if($type){
            $catalog = new Product_Model_Catalog();
            
            echo Zend_Json::encode($catalog->getProposeReviewUserList($type));
        }
        
        exit;
    }
    
    public function reviewAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '审核成功'
        );
        
        $request = $this->getRequest()->getParams();
        
        $review_id = isset($request['review_id']) ? $request['review_id'] : null;
        $review_operate = isset($request['review_operate']) ? $request['review_operate'] : null;
        $review_transfer_user = isset($request['review_transfer_user']) ? $request['review_transfer_user'] : null;
        $review_remark = isset($request['review_remark']) ? $request['review_remark'] : null;
        
        if($review_id && $review_operate){
            if($review_operate == 'transfer' && $review_transfer_user == null){
                $result['success'] = false;
                $result['info'] = '转审对象为空，批准失败！';
            }else{
                $user = new Application_Model_User();
                $review = new Dcc_Model_Review();
                
                if($review_operate == 'transfer'){
                    // 转审
                    $userData = $user->fetchRow("id = ".$review_transfer_user)->toArray();
                    $employee_id = $userData['employee_id'];
                    
                    $review->update(array('plan_user' => $employee_id), "type = 'product_add' and file_id = ".$review_id." and finish_flg = 0");
                }else{
                    $now = date('Y-m-d H:i:s');
                    $user_session = new Zend_Session_Namespace('user');
                    $user_id = $user_session->user_info['user_id'];
                    
                    $userData = $user->fetchRow("id = ".$user_id)->toArray();
                    $employee_id = $userData['employee_id'];
                    
                    $employee = new Hra_Model_Employee();
                    //$flow = new Admin_Model_Flow();
                    //$step = new Admin_Model_Step();
                    $catalog = new Product_Model_Catalog();
                    
                    $data = array(
                            'actual_user'   => $employee_id,
                            'finish_time'   => $now,
                            'finish_flg'    => 1
                    );
                    
                    // 更新审核记录
                    $review->update($data, "type = 'product_add' and file_id = ".$review_id." and finish_flg = 0");
                    
                    // 更新审核状态及审核意见
                    if($review_operate == 'no'){
                        $reviewResult = '<font style="color: #FF0000"><b>拒绝</b></font>';
                        $catalog->update(array('review' => 1, 'auditor_remark' => $review_remark), "id = ".$review_id);
                    }else{
                        $reviewResult = '<font style="color: #006400"><b>批准</b></font>';
                        $catalog->update(array('auditor_id' => $user_id, 'auditor_time' => $now, 'review' => 2, 'auditor_remark' => $review_remark), "id = ".$review_id);
                    }
                    
                    $catalogData = $catalog->fetchRow("id = ".$review_id)->toArray();
                    
                    if($review_operate == 'no'){
                        $mail = new Application_Model_Log_Mail();
                        
                        $applyEmployeeData = $user->fetchRow("id = ".$catalogData['create_user'])->toArray();
                        $applyEmployee = $employee->fetchRow("id = ".$applyEmployeeData['employee_id'])->toArray();
                        $to = $applyEmployee['email'];
                        
                        $mailContent = '<div>产品中心新增产品型号，已审核：</div>
                                        <div>
                                        <p><b>审核结果：</b>'.$reviewResult.'</p>
                                        <p><b>审核意见：</b>'.$review_remark.'</p>
                                        <p><b>标准型号：</b>'.$catalogData['model_standard'].'</p>
                                        <p><b>内部型号：</b>'.$catalogData['model_internal'].'</p>
                                        <p><b>描述：</b>'.$catalogData['description'].'</p>
                                        <p><b>申请人：</b>'.$user_session->user_info['user_name'].'</p>
                                        <p><b>申请时间：</b>'.$now.'</p>
                                        </div>';
                        
                        $mailData = array(
                                'type'      => '消息',
                                'subject'   => '产品中心-审核',
                                'to'        => $to,
                                'cc'        => $user_session->user_info['user_email'],
                                'user_id'   => $catalogData['create_user'],
                                'content'   => $mailContent,
                                'add_date'  => $now
                        );
                        
                        try {
                            // 记录邮件日志并发送邮件
                            $mail->send($mail->insert($mailData));
                        } catch (Exception $e) {
                            $result['success'] = false;
                            $result['info'] = $e->getMessage();
                        
                            echo Zend_Json::encode($result);
                        
                            exit;
                        }
                    }
                }
            }
        }else{
            $result['success'] = false;
            $result['info'] = 'ID为空，批准失败！';
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    public function approvecatalogAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '批准成功'
        );
        
        $request = $this->getRequest()->getParams();
        
        if(isset($request['id'])){
            $now = date('Y-m-d H:i:s');
            $user_session = new Zend_Session_Namespace('user');
            $user_id = $user_session->user_info['user_id'];
            
            $catalog = new Product_Model_Catalog();
            
            $data = array(
                    'auditor_id'    => $user_id,
                    'auditor_time'  => $now
            );
            
            try {
                $catalog->update($data, "id = ".$request['id']);
            } catch (Exception $e) {
                $result['result'] = false;
                $result['info'] = $e->getMessage();
            
                echo Zend_Json::encode($result);
            
                exit;
            }
        }else{
            $result['success'] = false;
            $result['info'] = 'ID为空，批准失败！';
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    public function editrolememberAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '添加成功'
        );
        
        $request = $this->getRequest()->getParams();
        
        $roleset_id = isset($request['roleset_id']) ? $request['roleset_id'] : null;
        $idStr = isset($request['idStr']) ? $request['idStr'] : null;
        
        if($roleset_id){
            $rolesetmember = new Product_Model_Rolesetmember();
            
            if($idStr){
                $idArrNew = explode(',', $idStr);
                $idArrOld = array();
                
                $idsAdd = array();
                $idsDelete = array();
                
                $data = $rolesetmember->fetchAll("roleset_id = ".$roleset_id)->toArray();
                
                // 获取删除的成员用户
                foreach ($data as $d){
                    array_push($idArrOld, $d['user_id']);
                    
                    if(!in_array($d['user_id'], $idArrNew)){
                        array_push($idsDelete, $d['user_id']);
                    }
                }
                
                // 获取新增的成员用户
                foreach ($idArrNew as $n){
                    if(!in_array($n, $idArrOld)){
                        array_push($idsAdd, $n);
                    }
                }
                
                // 添加新成员
                foreach ($idsAdd as $id){
                    if($rolesetmember->fetchAll("roleset_id = ".$roleset_id." and user_id = ".$id)->count() == 0){
                        $data = array(
                                'roleset_id'    => $roleset_id,
                                'user_id'       => $id
                        );
                        
                        try {
                            $rolesetmember->insert($data);
                        } catch (Exception $e) {
                            $result['result'] = false;
                            $result['info'] = $e->getMessage();
                        
                            echo Zend_Json::encode($result);
                        
                            exit;
                        }
                    }
                }
                
                //删除旧成员
                foreach ($idsDelete as $id){
                    try {
                        $rolesetmember->delete("roleset_id = ".$roleset_id." and user_id = ".$id);
                    } catch (Exception $e) {
                        $result['result'] = false;
                        $result['info'] = $e->getMessage();
                    
                        echo Zend_Json::encode($result);
                    
                        exit;
                    }
                }
            }else{
                try {
                    $rolesetmember->delete("roleset_id = ".$roleset_id);
                } catch (Exception $e) {
                    $result['result'] = false;
                    $result['info'] = $e->getMessage();
                
                    echo Zend_Json::encode($result);
                
                    exit;
                }
            }
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    // 获取项目设置中当前角色的全部成员
    public function getrolememberAction()
    {
        $data = array();
        
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        $role_id = isset($request['role_id']) ? $request['role_id'] : null;
        $user_id = isset($request['user_id']) ? $request['user_id'] : null;
        
        if($role_id){
            $member = new Admin_Model_Member();
        
            $data = $member->getMemberTreeList($role_id, $user_id);
        }
        
        echo Zend_Json::encode($data);
        
        exit;
    }
    
    // 添加角色，并将角色设置中的成员全部初始化到当前项目角色
    public function addroleAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '添加成功'
        );
        
        $request = $this->getRequest()->getParams();
        
        $catalog_id = isset($request['catalog_id']) ? $request['catalog_id'] : null;        
        $idStr = isset($request['idStr']) ? $request['idStr'] : null;
        
        if($catalog_id && $idStr){
            $now = date('Y-m-d H:i:s');
            $user_session = new Zend_Session_Namespace('user');
            $user_id = $user_session->user_info['user_id'];
            
            $roleset = new Product_Model_Roleset();
            $rolesetmember = new Product_Model_Rolesetmember();
            
            // 新配置
            $newSetIds = explode(',', $idStr);
            
            $addIds = array();// role_id
            $delIds = array();// id
            
            // 旧配置
            $oldSetIds = array();
            $oldSet = $roleset->fetchAll("catalog_id = ".$catalog_id)->toArray();
            foreach ($oldSet as $o){
                array_push($oldSetIds, $o['role_id']);
                
                // 需要删除的角色
                if(!in_array($o['role_id'], $newSetIds)){
                    array_push($delIds, $o['id']);
                }
            }
            
            foreach ($newSetIds as $n){
                if(!in_array($n, $oldSetIds)){
                    array_push($addIds, $n);
                }
            }
            
            // 添加新角色
            foreach ($addIds as $role_id){
                // 如所选角色未添加，则加入当前项目
                if($roleset->fetchAll("catalog_id = ".$catalog_id." and role_id = ".$role_id)->count() == 0){
                    $data = array(
                            'catalog_id'    => $catalog_id,
                            'role_id'       => $role_id
                    );
                    
                    try {
                        // 记录角色
                        $roleset_id = $roleset->insert($data);
                        
                        // 获取角色设置中的全部成员
                        $member = new Admin_Model_Member();
                        $members = $member->getMember($role_id);
                        
                        // 默认加入当前项目角色全部成员
                        foreach ($members as $m){
                            // 不需要添加管理员
                            if($m['user_id'] != 1){
                                $data = array(
                                        'roleset_id'    => $roleset_id,
                                        'user_id'       => $m['user_id']
                                );
                                
                                try {
                                    $rolesetmember->insert($data);
                                } catch (Exception $e) {
                                    $result['result'] = false;
                                    $result['info'] = $e->getMessage();
                                
                                    echo Zend_Json::encode($result);
                                
                                    exit;
                                }
                            }
                        }
                    } catch (Exception $e) {
                        $result['result'] = false;
                        $result['info'] = $e->getMessage();
                    
                        echo Zend_Json::encode($result);
                    
                        exit;
                    }
                }
            }
            
            // 删除取消选中的角色
            foreach ($delIds as $id){
                try {
                    $roleset->delete("id = ".$id);
                    
                    try {
                        $rolesetmember->delete("roleset_id = ".$id);
                    } catch (Exception $e){
                        $result['success'] = false;
                        $result['info'] = $e->getMessage();
                    
                        echo Zend_Json::encode($result);
                    
                        exit;
                    }
                } catch (Exception $e){
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
                
                    echo Zend_Json::encode($result);
                
                    exit;
                }
            }
        }else{
            $result['success'] = false;
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    // 根据项目ID获取角色配置
    public function getroleAction()
    {
        $data = array();
        
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        $catalog_id = isset($request['catalog_id']) ? $request['catalog_id'] : null;
        
        if($catalog_id){
            $roleset = new Product_Model_Roleset();
        
            $data = $roleset->getData($catalog_id);
        }
        
        echo Zend_Json::encode($data);
        
        exit;
    }
    
    // 获取产品目录
    public function getcatalogAction()
    {
        // 请求参数
        $request = $this->getRequest()->getParams();
        $option = isset($request['option']) ? $request['option'] : 'data';
        
        $catalog = new Product_Model_Catalog();
        
        // 查询条件
        $condition = array(
                'active'            => isset($request['active']) ? $request['active'] : 1,
                'key'               => isset($request['key']) ? $request['key'] : '',
                'date_from'         => isset($request['date_from']) ? $request['date_from'] : null,
                'date_to'           => isset($request['date_to']) ? $request['date_to'] : null,
                'display_deleted'   => isset($request['display_deleted']) ? ($request['display_deleted'] == 'true' ? 1 : 0) : 0,
                'type_id'           => isset($request['type_id']) && $request['type_id'] != 'null' ? $request['type_id'] : '',
                'series_id'         => isset($request['series_id']) && $request['series_id'] != 'null' ? $request['series_id'] : '',
                'stage_id'          => isset($request['stage_id']) && $request['stage_id'] != 'null' && $request['stage_id'] != 'undefined' ? $request['stage_id'] : '',
                'developmode_id'    => isset($request['developmode_id']) && $request['developmode_id'] && $request['developmode_id'] != 'undefined' ? $request['developmode_id'] : '',
                'page'              => isset($request['page']) ? $request['page'] : 1,
                'limit'             => isset($request['limit']) ? $request['limit'] : 0,
                'create_user'       => isset($request['create_user']) && $request['create_user'] != '' && $request['create_user'] != 'undefined' ? $request['create_user'] : null,
                'auditor_id'        => isset($request['auditor_id']) && $request['auditor_id'] != '' && $request['auditor_id'] != 'undefined' ? $request['auditor_id'] : null,
                'evt_date'          => isset($request['evt_date']) && $request['evt_date'] != 'null' ? substr($request['evt_date'], 0, 10)  : null,
                'dvt_date'          => isset($request['dvt_date']) && $request['dvt_date'] != 'null' ? substr($request['dvt_date'], 0, 10) : null,
                'qa1_date'          => isset($request['qa1_date']) && $request['qa1_date'] != 'null' ? substr($request['qa1_date'], 0, 10) : null,
                'qa2_date'          => isset($request['qa2_date']) && $request['qa2_date'] != 'null' ? substr($request['qa2_date'], 0, 10) : null,
                'mass_production_date'  => isset($request['mass_production_date']) && $request['mass_production_date'] != 'null' ? substr($request['mass_production_date'], 0, 10) : null,
                'type'              => $option
        );
        
        $data = $catalog->getData($condition);
        
        if($option == 'csv'){
            $this->view->layout()->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);
            
            $h = new Application_Model_Helpers();
            $h->exportCsv($data, '产品中心');
        }else{
            echo Zend_Json::encode($data);
        }
        
        exit;
    }
    
    // 获取产品分类
    public function gettypeAction()
    {
        $type = new Product_Model_Producttype();
        
        echo Zend_Json::encode($type->getData());
        
        exit;
    }
    
    // 获取产品系列
    public function getseriesAction()
    {
        $series = new Product_Model_Series();
        
        echo Zend_Json::encode($series->getData());
        
        exit;
    }
    
    // 获取产品阶段
    public function getstageAction()
    {
        $stage = new Product_Model_Stage();
        
        echo Zend_Json::encode($stage->getData());
        
        exit;
    }
    
    // 获取产品开发模式
    public function getmodeAction()
    {
        $mode = new Product_Model_Mode();
        
        echo Zend_Json::encode($mode->getData());
        
        exit;
    }
    
    // 获取产品角色设置
    public function getrolesetAction()
    {
        $roleset = new Product_Model_Roleset();
        
        $request = $this->getRequest()->getParams();
        
        $catalog_id = isset($request['catalog_id']) ? $request['catalog_id'] : null;
        
        if($catalog_id){
            echo Zend_Json::encode($roleset->getData($catalog_id));
        }
        
        exit;
    }
    
    // 修改产品目录
    public function editcatalogAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '编辑成功'
        );
        
        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user_id = $user_session->user_info['user_id'];
        
        $request = $this->getRequest()->getParams();
        
        $attribute = isset($request['attribute']) ? $request['attribute'] : false;
        
        $catalog = new Product_Model_Catalog();
        
        if($attribute){
            $catalog_id = isset($request['id']) ? $request['id'] : null;
            $code = isset($request['code']) ? $request['code'] : null;
            $code_old = isset($request['code_old']) ? $request['code_old'] : null;
            $stage_id = isset($request['stage_id']) && $request['stage_id'] != '' ? $request['stage_id'] : null;
            $remark = isset($request['remark']) ? $request['remark'] : null;
            $date_dvt = isset($request['date_dvt']) && $request['date_dvt'] != '' ? $request['date_dvt'] : null;
            $qa1_date = isset($request['qa1_date']) && $request['qa1_date'] != '' ? $request['qa1_date'] : null;
            $qa2_date = isset($request['qa2_date']) && $request['qa2_date'] != '' ? $request['qa2_date'] : null;
            $evt_date = isset($request['evt_date']) && $request['evt_date'] != '' ? $request['evt_date'] : null;
            $mass_production_date = isset($request['mass_production_date']) && $request['mass_production_date'] != '' ? $request['mass_production_date'] : null;
            
            if($catalog_id && $code){
                $data = array(
                        'code'                  => $code,
                        'code_old'              => $code_old,
                        'stage_id'              => $stage_id,
                        'remark'                => $remark,
                        'date_dvt'              => $date_dvt,
                        'qa1_date'              => $qa1_date,
                        'qa2_date'              => $qa2_date,
                        'evt_date'              => $evt_date,
                        'mass_production_date'  => $mass_production_date,
                        'update_time'           => $now,
                        'update_user'           => $user_id
                );
                
                $where = "id = ".$catalog_id;
                
                try {
                    $catalog->update($data, $where);
                } catch (Exception $e) {
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
                
                    echo Zend_Json::encode($result);
                
                    exit;
                }
            }else{
                $result['success'] = false;
                $result['info'] = '信息不完整，编辑失败！';
            }
        }else{
            $operate = isset($request['operate']) ? $request['operate'] : null;
            $review = isset($request['review']) ? $request['review'] : null;
            $id = isset($request['id']) ? $request['id'] : null;
            $active = isset($request['active']) ? $request['active'] : null;
            $active = $active == 'on' ? 1 : 0;
            $description = isset($request['description']) ? $request['description'] : null;
            $remark = isset($request['remark']) ? $request['remark'] : null;
            $code_customer = isset($request['code_customer']) ? $request['code_customer'] : null;
            $model_customer = isset($request['model_customer']) ? $request['model_customer'] : null;
            $description_customer = isset($request['description_customer']) ? $request['description_customer'] : null;
            $type_id = isset($request['type_id']) ? $request['type_id'] : null;
            $type_id = isset($request['type_id']) ? $request['type_id'] : null;
            $series_id = isset($request['series_id']) ? $request['series_id'] : null;
            $series_id = isset($request['series_id']) ? $request['series_id'] : null;
            $developmode_id = isset($request['developmode_id']) ? $request['developmode_id'] : null;
            $model_standard = isset($request['model_standard']) ? $request['model_standard'] : null;
            $model_internal = isset($request['model_internal']) ? $request['model_internal'] : null;
            
            if($operate == 'delete' && $id){
                try {
                    if($catalog->fetchAll("id = ".$id." and auditor_id is not null")->count() == 1){
                        $catalog->update(array('delete' => 1), "id = ".$id);
                    }else{
                        $catalog->delete("id = ".$id);
                        
                        $review = new Dcc_Model_Review();
                        $review->delete("type = 'product_add' and file_id = ".$id);
                    }
                } catch (Exception $e){
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
                
                    echo Zend_Json::encode($result);
                
                    exit;
                }
            }else if($operate == 'edit' && $id){
                // 判断是否重复
                if($catalog->fetchAll("id != ".$id." and model_internal = '".$model_internal."'")->count() == 0){//type_id = ".$type_id." and series_id = ".$series_id." and 
                    $data = array(
                            'active'            => $active,
                            'type_id'           => $type_id,
                            'series_id'         => $series_id,
                            'developmode_id'    => $developmode_id,
                            'model_standard'    => $model_standard,
                            'model_internal'    => $model_internal,
                            'description'       => $description,
                            'remark'            => $remark,
                            'review'            => $review == 1 ? 0 : 2,
                            'code_customer'     => $code_customer,
                            'model_customer'    => $model_customer,
                            'description_customer'     => $description_customer,
                            'update_time'       => $now,
                            'update_user'       => $user_id
                    );
                    
                    $where = "id = ".$id;
                    
                    try {
                        $catalog->update($data, $where);
                        
                        $info = "产品中心新增产品型号，请登录系统审核：";
                        $editType = "修改";
                        
                        if($review == 2){
                            $info = "产品中心产品信息变更，请登录系统查看：";
                            $editType = "变更";
                        }
                        
                        $member = new Admin_Model_Member();
                        $user = new Application_Model_User();
                        $employee = new Hra_Model_Employee();
                        $flow = new Admin_Model_Flow();
                        $step = new Admin_Model_Step();
                        $review = new Dcc_Model_Review();
                        
                        $flowData = $flow->fetchRow("flow_name = '产品中心-审核'")->toArray();
                        // 获取审核阶段
                        $stepIds = $flowData['step_ids'];
                        $stepArr = explode(',', $stepIds);
                        
                        $i = 0;
                        
                        foreach ($stepArr as $s){
                            $stepData = $step->fetchRow("id = ".$s)->toArray();
                        
                            $step_user = $stepData['user'] != '' ? $stepData['user'] : null;
                            $step_role = $stepData['dept'] != '' ? $stepData['dept'] : null;
                        
                            $reviewData = array(
                                    'type'      => 'product_add',
                                    'file_id'   => $id,
                                    'step_name' => $stepData['step_name'],
                                    'plan_user' => $step_user,
                                    'plan_dept' => $step_role,
                                    'method'    => $stepData['method'],
                                    'return'    => $stepData['return']
                            );
                        
                            $review->insert($reviewData);
                        
                            // 第一阶段发送邮件通知
                            if($i == 0){
                                $employeeIdArr = array();
                        
                                if($step_user){
                                    $tmpArr = explode(',', $stepData['user']);
                        
                                    foreach ($tmpArr as $t){
                                        if(!in_array($t, $employeeIdArr)){
                                            array_push($employeeIdArr, $t);
                                        }
                                    }
                                }
                        
                                if($step_role){
                                    $tmpArr = $member->getMember($stepData['dept']);
                        
                                    foreach ($tmpArr as $t){
                                        if(!in_array($t, $employeeIdArr)){
                                            array_push($employeeIdArr, $t['employee_id']);
                                        }
                                    }
                                }
                        
                                $toAddress = array();
                                $toIds = array();
                        
                                foreach ($employeeIdArr as $employeeId){
                                    $em = $employee->getInfoById($employeeId);
                                    array_push($toAddress, $em['email']);
                                    $u = $user->fetchRow("employee_id = ".$employeeId)->toArray();
                                    array_push($toIds, $u['id']);
                                }
                        
                                $mail = new Application_Model_Log_Mail();
                        
                                $mailContent = '<div>'.$info.'</div>
                                    <div>
                                    <p><b>标准型号：</b>'.$model_standard.'</p>
                                    <p><b>内部型号：</b>'.$model_internal.'</p>
                                    <p><b>描述：</b>'.$description.'</p>
                                    <p><b>申请人：</b>'.$user_session->user_info['user_name'].'</p>
                                    <p><b>申请时间：</b>'.$now.'</p>
                                    </div>';
                        
                                $mailData = array(
                                        'type'      => '消息',
                                        'subject'   => '产品中心-'.$editType,
                                        'to'        => implode(',', $toAddress),
                                        //'cc'        => $user_session->user_info['user_email'],
                                        'user_id'   => implode(',', $toIds),
                                        'content'   => $mailContent,
                                        'add_date'  => $now
                                );
                        
                                try {
                                    // 记录邮件日志并发送邮件
                                    $mail->send($mail->insert($mailData));
                                } catch (Exception $e) {
                                    $result['success'] = false;
                                    $result['info'] = $e->getMessage();
                        
                                    echo Zend_Json::encode($result);
                        
                                    exit;
                                }
                            }
                        
                            $i++;
                        }
                    } catch (Exception $e) {
                        $result['success'] = false;
                        $result['info'] = $e->getMessage();
                    
                        echo Zend_Json::encode($result);
                    
                        exit;
                    }
                }else{
                    $result['success'] = false;
                    $result['info'] = '当前型号已存在，请勿重复添加！';
                    
                    echo Zend_Json::encode($result);
                    
                    exit;
                }
            }else if($operate == 'add'){
                if($catalog->fetchAll("model_internal = '".$model_internal."'")->count() == 0){//type_id = ".$type_id." and series_id = ".$series_id." and 
                    $data = array(
                            'active'            => $active,
                            'type_id'           => $type_id,
                            'series_id'         => $series_id,
                            'developmode_id'    => $developmode_id,
                            'model_standard'    => $model_standard,
                            'model_internal'    => $model_internal,
                            'remark'            => $remark,
                            'description'       => $description,
                            'code_customer'     => $code_customer,
                            'model_customer'    => $model_customer,
                            'description_customer'     => $description_customer,
                            'create_time'       => $now,
                            'create_user'       => $user_id,
                            'update_time'       => $now,
                            'update_user'       => $user_id
                    );
                    
                    try {
                        $catalog_id = $catalog->insert($data);
                        
                        $member = new Admin_Model_Member();
                        $user = new Application_Model_User();
                        $employee = new Hra_Model_Employee();
                        $flow = new Admin_Model_Flow();
                        $step = new Admin_Model_Step();
                        $review = new Dcc_Model_Review();
                        
                        $flowData = $flow->fetchRow("flow_name = '产品中心-审核'")->toArray();
                        // 获取审核阶段
                        $stepIds = $flowData['step_ids'];
                        $stepArr = explode(',', $stepIds);
                        
                        $i = 0;
                        
                        foreach ($stepArr as $s){
                            $stepData = $step->fetchRow("id = ".$s)->toArray();
                            
                            $step_user = $stepData['user'] != '' ? $stepData['user'] : null;
                            $step_role = $stepData['dept'] != '' ? $stepData['dept'] : null;
                            
                            $reviewData = array(
                                    'type'      => 'product_add',
                                    'file_id'   => $catalog_id,
                                    'step_name' => $stepData['step_name'],
                                    'plan_user' => $step_user,
                                    'plan_dept' => $step_role,
                                    'method'    => $stepData['method'],
                                    'return'    => $stepData['return']
                            );
                            
                            $review->insert($reviewData);
                            
                            // 第一阶段发送邮件通知
                            if($i == 0){
                                $employeeIdArr = array();
                                
                                if($step_user){
                                    $tmpArr = explode(',', $stepData['user']);
                                    
                                    foreach ($tmpArr as $t){
                                        if(!in_array($t, $employeeIdArr)){
                                            array_push($employeeIdArr, $t);
                                        }
                                    }
                                }
                                
                                if($step_role){
                                    $tmpArr = $member->getMember($stepData['dept']);
                                    
                                    foreach ($tmpArr as $t){
                                        if(!in_array($t, $employeeIdArr)){
                                            array_push($employeeIdArr, $t['employee_id']);
                                        }
                                    }
                                }
                                
                                $toAddress = array();
                                $toIds = array();
                                
                                foreach ($employeeIdArr as $employeeId){
                                    $em = $employee->getInfoById($employeeId);
                                    array_push($toAddress, $em['email']);
                                    $u = $user->fetchRow("employee_id = ".$employeeId)->toArray();
                                    array_push($toIds, $u['id']);
                                }
                                
                                $mail = new Application_Model_Log_Mail();
                                
                                $mailContent = '<div>产品中心新增产品型号，请登录系统审核：</div>
                                    <div>
                                    <p><b>标准型号：</b>'.$model_standard.'</p>
                                    <p><b>内部型号：</b>'.$model_internal.'</p>
                                    <p><b>描述：</b>'.$description.'</p>
                                    <p><b>申请人：</b>'.$user_session->user_info['user_name'].'</p>
                                    <p><b>申请时间：</b>'.$now.'</p>
                                    </div>';
                                
                                $mailData = array(
                                        'type'      => '消息',
                                        'subject'   => '产品中心-新建',
                                        'to'        => implode(',', $toAddress),
                                        //'cc'        => $user_session->user_info['user_email'],
                                        'user_id'   => implode(',', $toIds),
                                        'content'   => $mailContent,
                                        'add_date'  => $now
                                );
                                
                                try {
                                    // 记录邮件日志并发送邮件
                                    $mail->send($mail->insert($mailData));
                                } catch (Exception $e) {
                                    $result['success'] = false;
                                    $result['info'] = $e->getMessage();
                                
                                    echo Zend_Json::encode($result);
                                
                                    exit;
                                }
                            }
                            
                            $i++;
                        }
                    } catch (Exception $e) {
                        $result['success'] = false;
                        $result['info'] = $e->getMessage();
                    
                        echo Zend_Json::encode($result);
                    
                        exit;
                    }
                }else{
                    $result['success'] = false;
                    $result['info'] = '当前型号已存在，请勿重复添加！';
                    
                    echo Zend_Json::encode($result);
                    
                    exit;
                }
            }
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    // 修改产品分类
    public function edittypeAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '编辑成功'
        );
        
        $request = $this->getRequest()->getParams();
        
        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user_id = $user_session->user_info['user_id'];
        
        $json = json_decode($request['json']);
        
        $updated    = $json->updated;
        $inserted   = $json->inserted;
        $deleted    = $json->deleted;
        
        $type = new Product_Model_Producttype();
        
        if(count($updated) > 0){
            foreach ($updated as $val){
                $data = array(
                        'active'        => $val->active,
                        'name'          => $val->name,
                        'description'   => $val->description,
                        'remark'        => $val->remark,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
        
                $where = "id = ".$val->id;
        
                try {
                    $type->update($data, $where);
                } catch (Exception $e) {
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
        
                    echo Zend_Json::encode($result);
        
                    exit;
                }
            }
        }
        
        if(count($inserted) > 0){
            foreach ($inserted as $val){
                $data = array(
                        'active'        => $val->active,
                        'name'          => $val->name,
                        'description'   => $val->description,
                        'remark'        => $val->remark,
                        'create_time'   => $now,
                        'create_user'   => $user_id,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
        
                try{
                    $type->insert($data);
                } catch (Exception $e){
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
        
                    echo Zend_Json::encode($result);
        
                    exit;
                }
            }
        }
        
        if(count($deleted) > 0){
            $catalog = new Product_Model_Catalog();
        
            foreach ($deleted as $val){
                if($catalog->fetchAll("type_id = ".$val->id)->count() == 0){
                    try {
                        $type->delete("id = ".$val->id);
                    } catch (Exception $e){
                        $result['success'] = false;
                        $result['info'] = $e->getMessage();
        
                        echo Zend_Json::encode($result);
        
                        exit;
                    }
                }else{
                    $result['success'] = false;
                    $result['info'] = '产品分类ID'.$val->id.'存在关联产品信息，不能删除';
        
                    echo Zend_Json::encode($result);
        
                    exit;
                }
            }
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    // 修改产品系列
    public function editseriesAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '编辑成功'
        );
        
        $request = $this->getRequest()->getParams();
        
        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user_id = $user_session->user_info['user_id'];
        
        $json = json_decode($request['json']);
        
        $updated    = $json->updated;
        $inserted   = $json->inserted;
        $deleted    = $json->deleted;
        
        $series = new Product_Model_Series();
        
        if(count($updated) > 0){
            foreach ($updated as $val){
                $data = array(
                        'active'        => $val->active,
                        'name'          => $val->name,
                        'code'          => $val->code,
                        'description'   => $val->description,
                        'remark'        => $val->remark,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
        
                $where = "id = ".$val->id;
        
                try {
                    $series->update($data, $where);
                } catch (Exception $e) {
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
        
                    echo Zend_Json::encode($result);
        
                    exit;
                }
            }
        }
        
        if(count($inserted) > 0){
            foreach ($inserted as $val){
                $data = array(
                        'active'        => $val->active,
                        'name'          => $val->name,
                        'code'          => $val->code,
                        'description'   => $val->description,
                        'remark'        => $val->remark,
                        'create_time'   => $now,
                        'create_user'   => $user_id,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
        
                try{
                    $series->insert($data);
                } catch (Exception $e){
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
        
                    echo Zend_Json::encode($result);
        
                    exit;
                }
            }
        }
        
        if(count($deleted) > 0){
            $catalog = new Product_Model_Catalog();
        
            foreach ($deleted as $val){
                if($catalog->fetchAll("series_id = ".$val->id)->count() == 0){
                    try {
                        $series->delete("id = ".$val->id);
                    } catch (Exception $e){
                        $result['success'] = false;
                        $result['info'] = $e->getMessage();
        
                        echo Zend_Json::encode($result);
        
                        exit;
                    }
                }else{
                    $result['success'] = false;
                    $result['info'] = '产品系列ID'.$val->id.'存在关联产品信息，不能删除';
        
                    echo Zend_Json::encode($result);
        
                    exit;
                }
            }
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    // 修改产品阶段
    public function editstageAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '编辑成功'
        );
        
        $request = $this->getRequest()->getParams();
        
        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user_id = $user_session->user_info['user_id'];
        
        $json = json_decode($request['json']);
        
        $updated    = $json->updated;
        $inserted   = $json->inserted;
        $deleted    = $json->deleted;
        
        $stage = new Product_Model_Stage();
        
        if(count($updated) > 0){
            foreach ($updated as $val){
                $data = array(
                        'active'        => $val->active,
                        'name'          => $val->name,
                        'description'   => $val->description,
                        'remark'        => $val->remark,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
        
                $where = "id = ".$val->id;
        
                try {
                    $stage->update($data, $where);
                } catch (Exception $e) {
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
        
                    echo Zend_Json::encode($result);
        
                    exit;
                }
            }
        }
        
        if(count($inserted) > 0){
            foreach ($inserted as $val){
                $data = array(
                        'active'        => $val->active,
                        'name'          => $val->name,
                        'description'   => $val->description,
                        'remark'        => $val->remark,
                        'create_time'   => $now,
                        'create_user'   => $user_id,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
        
                try{
                    $stage->insert($data);
                } catch (Exception $e){
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
        
                    echo Zend_Json::encode($result);
        
                    exit;
                }
            }
        }
        
        if(count($deleted) > 0){
            $catalog = new Product_Model_Catalog();
        
            foreach ($deleted as $val){
                if($catalog->fetchAll("stage_id = ".$val->id)->count() == 0){
                    try {
                        $stage->delete("id = ".$val->id);
                    } catch (Exception $e){
                        $result['success'] = false;
                        $result['info'] = $e->getMessage();
        
                        echo Zend_Json::encode($result);
        
                        exit;
                    }
                }else{
                    $result['success'] = false;
                    $result['info'] = '阶段ID'.$val->id.'存在关联产品信息，不能删除';
        
                    echo Zend_Json::encode($result);
        
                    exit;
                }
            }
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    // 修改产品开发模式
    public function editmodeAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '编辑成功'
        );
        
        $request = $this->getRequest()->getParams();
        
        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user_id = $user_session->user_info['user_id'];
        
        $json = json_decode($request['json']);
        
        $updated    = $json->updated;
        $inserted   = $json->inserted;
        $deleted    = $json->deleted;
        
        $mode = new Product_Model_Mode();
        
        if(count($updated) > 0){
            foreach ($updated as $val){
                $data = array(
                        'active'        => $val->active,
                        'name'          => $val->name,
                        'description'   => $val->description,
                        'remark'        => $val->remark,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
        
                $where = "id = ".$val->id;
        
                try {
                    $mode->update($data, $where);
                } catch (Exception $e) {
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
        
                    echo Zend_Json::encode($result);
        
                    exit;
                }
            }
        }
        
        if(count($inserted) > 0){
            foreach ($inserted as $val){
                $data = array(
                        'active'        => $val->active,
                        'name'          => $val->name,
                        'description'   => $val->description,
                        'remark'        => $val->remark,
                        'create_time'   => $now,
                        'create_user'   => $user_id,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
        
                try{
                    $mode->insert($data);
                } catch (Exception $e){
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
        
                    echo Zend_Json::encode($result);
        
                    exit;
                }
            }
        }
        
        if(count($deleted) > 0){
            $catalog = new Product_Model_Catalog();
        
            foreach ($deleted as $val){
                if($catalog->fetchAll("mode_id = ".$val->id)->count() == 0){
                    try {
                        $mode->delete("id = ".$val->id);
                    } catch (Exception $e){
                        $result['success'] = false;
                        $result['info'] = $e->getMessage();
        
                        echo Zend_Json::encode($result);
        
                        exit;
                    }
                }else{
                    $result['success'] = false;
                    $result['info'] = '开发模式ID'.$val->id.'存在关联产品信息，不能删除';
        
                    echo Zend_Json::encode($result);
        
                    exit;
                }
            }
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    // 修改产品角色设置
    public function editrolesetAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '编辑成功'
        );
        
        $request = $this->getRequest()->getParams();
        
        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user_id = $user_session->user_info['user_id'];
        
        $json = json_decode($request['json']);
        
        $updated = $json->updated;
        
        $roleset = new Product_Model_Roleset();
        
        if(count($updated) > 0){
            foreach ($updated as $val){
                $data = array(
                        'active'        => $val->active,
                        'remark'        => $val->remark
                );
        
                $where = "id = ".$val->id;
        
                try {
                    $roleset->update($data, $where);
                } catch (Exception $e) {
                    $result['success'] = false;
                    $result['info'] = $e->getMessage();
        
                    echo Zend_Json::encode($result);
        
                    exit;
                }
            }
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
}