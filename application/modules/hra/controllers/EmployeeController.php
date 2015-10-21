<?php
/**
 * 2013-7-6 下午10:27:09
 * @author      x.li
 * @abstract    员工管理
 */
class Hra_EmployeeController extends Zend_Controller_Action
{
    public function indexAction()
    {
        
    }
    
    public function photoAction()
    {
        $result = array(
                'success'   => true,
                'info'      => '上传成功'
        );
    
        $request = $this->getRequest()->getParams();
    
        $employee_id = isset($request['employee_id']) ? $request['employee_id'] : null;
    
        if($employee_id && isset($_FILES['attach_file'])){
            $now = date('Y-m-d H:i:s');
            $user_session = new Zend_Session_Namespace('user');
            $user_id = $user_session->user_info['user_id'];
    
            $file = $_FILES['attach_file'];
    
            $file_name = $file['name'];
            $file_extension = strrchr($file_name, ".");
            
            if ($file_extension != '.jpg') {
                $result = array(
                        'success'   => false,
                        'info'      => '格式错误，请选择格式为jpg的相片文件！'
                );
                
                echo Zend_Json::encode($result);
                
                exit;
            }
    
            $h = new Application_Model_Helpers();
            $tmp_file_name = $h->getMicrotimeStr().$file_extension;
    
            $savepath = "../upload/portrait/";
    
            if(!is_dir($savepath)){
                mkdir($savepath);// 目录不存在则创建目录
            }
    
            $tmp_file_path = $savepath.$tmp_file_name;
            move_uploaded_file($file["tmp_name"], $tmp_file_path);
    
            $employee = new Hra_Model_Employee();
            $employeeData = $employee->getInfoById($employee_id);
    
            if($employeeData['photo_path'] != '' && file_exists($employeeData['photo_path'])){
                unlink($employeeData['photo_path']);
            }
    
            $employeeData = array(
                    'photo_path'   => $tmp_file_path,
                    'update_user'   => $user_id,
                    'update_time'   => $now
            );
            
            $employee->update($employeeData, "id = ".$employee_id);
        }
    
        echo Zend_Json::encode($result);
    
        exit;
    }
    
    // 获取离职率
    public function getseparationrateAction()
    {
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        $year = isset($request['year']) ? $request['year'] : date('Y');
        
        $employee = new Hra_Model_Employee();
        
        $data = $employee->getSeparation($year);
        
        echo Zend_Json::encode($data);
        
        exit;
    }
    
    // 获取图标数据
    public function getstructureAction()
    {
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        $option = isset($request['option']) ? $request['option'] : null;
        
        if($option){
            $employee = new Hra_Model_Employee();
            
            $data = $employee->getStructure($option);
            
            echo Zend_Json::encode($data);
        }
        
        exit;
    }
    
    // 获取职位列表
    public function getpostAction()
    {
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        $option = isset($request['option']) ? $request['option'] : 'list';
        
        $post = new Hra_Model_Post();
        
        if($option == 'list'){
            echo Zend_Json::encode($post->getList());
        }else{
            echo Zend_Json::encode($post->getData());
        }
        
        exit;
    }
    
    public function getemployeeforselAction() {
        // 请求参数
        $request = $this->getRequest()->getParams();
        $limit = $request['limit'];
        $start = $request['start'];
        $key = isset($request['search_key']) ? $request['search_key'] : '';
        
        $employee = new Hra_Model_Employee();
        // 查询条件
        $cols = array("number", "cname", "ename", "id_card", "email", "tel", "address");
        $arr=preg_split('/\s+/',trim($key));
        for ($i=0;$i<count($arr);$i++) {
            $tmp = array();
            foreach($cols as $c) {
                $tmp[] = "ifnull($c,'')";
            }
            $arr[$i] = "concat(".implode(',', $tmp).") like '%".$arr[$i]."%'";
        }
        $where = "active=1 and ".join(' AND ', $arr);
        
        $total = $employee->getJoinCount($where);
        $data = array();
        if($total > 0) {
            $data = $employee->getJoinList($where, array(), array('id', 'number', 'cname', 'email'), array('cname'));
        }
        $resutl = array('total' => $total, 'rows' => $data);
        echo Zend_Json::encode($resutl);
    
        exit;
    }
    
    // 获取用工形式列表
    public function gettypeAction()
    {
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        $option = isset($request['option']) ? $request['option'] : 'list';
        
        $type = new Hra_Model_Type();
        
        if($option == 'list'){
            echo Zend_Json::encode($type->getList());
        }else{
            echo Zend_Json::encode($type->getData());
        }
        
        exit;
    }
    
    public function getemployeelistAction()
    {
        $employee = new Hra_Model_Employee();
        
        echo Zend_Json::encode($employee->getNameList());
        
        exit;
    }
    
    /**
     * 获取员工列表
     */
    public function getemployeeAction()
    {
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        $option = isset($request['option']) ? $request['option'] : 'list';
        $badgenumber = isset($request['badgenumber']) ? $request['badgenumber'] : 0;
        
        $employee = new Hra_Model_Employee();
        
        if($option == 'list'){
            echo Zend_Json::encode($employee->getList($badgenumber));
        }else{
            // 查询条件
            $condition = array(
                    'active'            => isset($request['active']) ? $request['active'] : 1,
                    'leader'            => isset($request['leader']) ? $request['leader'] : 0,
                    'key'               => isset($request['key']) && $request['key'] != 'undefined' ? $request['key'] : '',
                    'entry_date_from'   => isset($request['entry_date_from']) ? $request['entry_date_from'] : '',
                    'entry_date_to'     => isset($request['entry_date_to']) ? $request['entry_date_to'] : '',
                    'dept_id'           => isset($request['dept_id']) ? $request['dept_id'] : '',
                    'employment_type'   => isset($request['employment_type']) ? $request['employment_type'] : '',
                    'page'              => isset($request['page']) ? $request['page'] : 1,
                    'limit'             => isset($request['limit']) ? $request['limit'] : 0,
                    'type'              => $option
            );
            
            $data = $employee->getData($condition);
            
            if($option == 'csv'){
                $this->view->layout()->disableLayout();
                $this->_helper->viewRenderer->setNoRender(true);
                
                $h = new Application_Model_Helpers();
                $h->exportCsv($data, '员工数据');
            }else{
                echo Zend_Json::encode($data);
            }
        }
    
        exit;
    }
    
    /**
     * 编辑员工信息
     */
    public function editAction()
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
        
        $employee = new Hra_Model_Employee();
        $user = new Application_Model_User();
        
        if(count($updated) > 0){
            foreach ($updated as $val){
                if($employee->fetchAll("id != ".$val->id." and email = '".$val->email."'")->count() > 0){
                    $result['success'] = false;
                    $result['info'] = '更新失败，邮箱地址重复!';
                    
                    echo Zend_Json::encode($result);
                    
                    exit;
                }else if($employee->fetchAll("id != ".$val->id." and number = '".$val->number."'")->count() > 0){
                    $result['success'] = false;
                    $result['info'] = '更新失败，工号重复!';
                
                    echo Zend_Json::encode($result);
                
                    exit;
                }else{
                    $dept_manager_id = $val->dept_manager_id == '' ? null : $val->dept_manager_id;
                    $manager_id = $val->manager_id == '' ? null : $val->manager_id;
                    $dept_id = $val->dept_id == '' ? null : $val->dept_id;
                    $post_id = $val->post_id == '' ? null : $val->post_id;
                    $area_id = $val->area_id == '' ? null : $val->area_id;
                    $professional_qualifications_id = $val->professional_qualifications_id == '' ? null : $val->professional_qualifications_id;
                    
                    $data = array(
                            'hide'                              => $val->hide,
                            'active'                            => $val->active,
                            'leader'                            => $val->leader,
                            'number'                            => $val->number,
                            'cname'                             => $val->cname,
                            'ename'                             => $val->ename,
                            'sex'                               => $val->sex,
                            'birthday'                          => $val->birthday,
                            'id_card'                           => $val->id_card,
                            'dept_id'                           => $dept_id,
                            'post_id'                           => $post_id,
                            'area_id'                           => $area_id,
                            'professional_qualifications_id'    => $professional_qualifications_id,
                            'dept_manager_id'                   => $dept_manager_id,
                            'manager_id'                        => $manager_id,
                            'salary'                            => $val->salary,
                            'email'                             => $val->email,
                            'tel'                               => $val->tel,
                            'official_qq'                       => $val->official_qq,
                            'work_place'                        => $val->work_place,
                            'short_num'                         => $val->short_num,
                            'msn'                               => $val->msn,
                            'address'                           => $val->address,
                            'remark'                            => $val->remark,
                            'marital_status'                    => $val->marital_status,
                            'marry_day'                         => $val->marry_day,
                            'children_birthday'                 => $val->children_birthday,
                            'insurcode'                         => $val->insurcode,
                            'accumulation_fund_code'            => $val->accumulation_fund_code,
                            'education'                         => $val->education,
                            'school'                            => $val->school,
                            'major'                             => $val->major,
                            'entry_date'                        => $val->entry_date,
                            'regularization_date'               => $val->regularization_date,
                            'labor_contract_start'              => $val->labor_contract_start,
                            'labor_contract_end'                => $val->labor_contract_end,
                            'offical_address'                   => $val->offical_address,
                            'other_contact'                     => $val->other_contact,
                            'other_relationship'                => $val->other_relationship,
                            'other_contact_way'                 => $val->other_contact_way,
                            'work_years'                        => $val->work_years,
                            'politics_status'                   => $val->politics_status,
                            'employment_type'                   => $val->employment_type,
                            'leave_date'                        => $val->leave_date,
                            'ext'                               => $val->ext,
                            'driving_license'                   => $val->driving_license,
                            'salary'                            => $val->salary,
                            'bank'                              => $val->bank,
                            'bank_num'                          => $val->bank_num,
                            'update_time'                       => $now,
                            'update_user'                       => $user_id
                    );
            
                    $where = "id = ".$val->id;
                    
                    try {
                        $employee->update($data, $where);
                    } catch (Exception $e) {
                        $result['success'] = false;
                        $result['info'] = $e->getMessage();
            
                        echo Zend_Json::encode($result);
            
                        exit;
                    }
                    
                    if($val->account == 1){
                        if($user->fetchAll("employee_id = ".$val->id)->count() > 0){
                            $account_active = $val->account_active == true ? 1 : 0;
                            
                            // 当员工系统账号已存在时，如需要改变账号状态，则更新系统账号状态信息
                            if($user->fetchAll("active = ".$account_active." and employee_id = ".$val->id)->count() == 0){
                                try {
                                    $user->update(array('active' => $account_active, 'update_user' => $user_id, 'update_time' => $now), "employee_id = ".$val->id);
                                } catch (Exception $e) {
                                    $result['success'] = false;
                                    $result['info'] = $e->getMessage();
                        
                                    echo Zend_Json::encode($result);
                        
                                    exit;
                                }
                            }
                        }else{
                            // 当员工系统账号不存在时，则添加新的系统账号信息
                            $data = array(
                                    'employee_id'   => $val->id,
                                    'active'        => $val->account_active,
                                    'password'      => md5($val->number.'123456'),
                                    'create_time'   => $now,
                                    'create_user'   => $user_id,
                                    'update_time'   => $now,
                                    'update_user'   => $user_id
                            );
                            
                            try {
                                $newUserId = $user->insert($data);
                                
                                // 初始化用户角色为普通用户
                                $roleMember = new Admin_Model_Member();
                                
                                try {
                                    $roleMember->insert(array('user_id'   => $newUserId));
                                } catch (Exception $e) {
                                    $result['success'] = false;
                                    $result['info'] = $e->getMessage();
                        
                                    echo Zend_Json::encode($result);
                        
                                    exit;
                                }
                            } catch (Exception $e) {
                                $result['success'] = false;
                                $result['info'] = $e->getMessage();
                    
                                echo Zend_Json::encode($result);
                    
                                exit;
                            }
                        }
                    }else{
                        if($user->fetchAll("employee_id = ".$val->id)->count() > 0){
                            // 当员工系统账号已存在时，如需要改变账号状态，则更新系统账号状态信息
                            if($user->fetchAll("active = ".$val->account_active." and employee_id = ".$val->id)->count() == 0){
                                try {
                                    $user->update(array('active' => $val->account_active, 'update_user' => $user_id, 'update_time' => $now), "employee_id = ".$val->id);
                                } catch (Exception $e) {
                                    $result['success'] = false;
                                    $result['info'] = $e->getMessage();
                        
                                    echo Zend_Json::encode($result);
                        
                                    exit;
                                }
                            }
                        }
                    }
                }
            }
        }
        
        if(count($inserted) > 0){
            foreach ($inserted as $val){
                if($employee->fetchAll("email = '".$val->email."'")->count() > 0){
                    $result['success'] = false;
                    $result['info'] = '添加失败，邮箱地址重复!';
                
                    echo Zend_Json::encode($result);
                
                    exit;
                }else if($employee->fetchAll("number = '".$val->number."'")->count() > 0){
                    $result['success'] = false;
                    $result['info'] = '添加失败，工号重复!';
                
                    echo Zend_Json::encode($result);
                
                    exit;
                }else{
                    $data = array(
                            'hide'                              => isset($val->hide) ? $val->hide : 0,
                            'active'                            => $val->active,
                            'leader'                            => $val->leader,
                            'number'                            => $val->number,
                            'cname'                             => $val->cname,
                            'ename'                             => $val->ename,
                            'sex'                               => $val->sex,
                            'birthday'                          => $val->birthday,
                            'id_card'                           => $val->id_card,
                            'dept_id'                           => $val->dept_id,
                            'post_id'                           => $val->post_id,
                            'area_id'                           => $val->area_id,
                            'professional_qualifications_id'    => $val->professional_qualifications_id,
                            'dept_manager_id'                   => $val->dept_manager_id,
                            'manager_id'                        => $val->manager_id,
                            'salary'                            => $val->salary,
                            'email'                             => $val->email,
                            'tel'                               => $val->tel,
                            'official_qq'                       => $val->official_qq,
                            'work_place'                        => $val->work_place,
                            'short_num'                         => $val->short_num,
                            'msn'                               => $val->msn,
                            'address'                           => $val->address,
                            'remark'                            => $val->remark,
                            'marital_status'                    => $val->marital_status,
                            'marry_day'                         => $val->marry_day,
                            'children_birthday'                 => $val->children_birthday,
                            'insurcode'                         => $val->insurcode,
                            'accumulation_fund_code'            => $val->accumulation_fund_code,
                            'education'                         => $val->education,
                            'school'                            => $val->school,
                            'major'                             => $val->major,
                            'entry_date'                        => $val->entry_date,
                            'regularization_date'               => $val->regularization_date,
                            'labor_contract_start'              => $val->labor_contract_start,
                            'labor_contract_end'                => $val->labor_contract_end,
                            'offical_address'                   => $val->offical_address,
                            'other_contact'                     => $val->other_contact,
                            'other_relationship'                => $val->other_relationship,
                            'other_contact_way'                 => $val->other_contact_way,
                            'work_years'                        => $val->work_years,
                            'politics_status'                   => $val->politics_status,
                            'employment_type'                   => $val->employment_type,
                            'leave_date'                        => $val->leave_date,
                            'ext'                               => $val->ext,
                            'driving_license'                   => $val->driving_license,
                            'salary'                            => $val->salary,
                            'bank'                              => $val->bank,
                            'bank_num'                          => $val->bank_num,
                            'create_time'                       => $now,
                            'create_user'                       => $user_id,
                            'update_time'                       => $now,
                            'update_user'                       => $user_id
                    );
                    
                    try{
                        $employee_id = $employee->insert($data);
                    } catch (Exception $e){
                        $result['success'] = false;
                        $result['info'] = $e->getMessage();
                    
                        echo Zend_Json::encode($result);
                    
                        exit;
                    }
                    
                    if($val->account == 1 && $user->fetchAll("employee_id = ".$employee_id)->count() == 0){
                        try{
                            $data = array(
                                    'employee_id'   => $employee_id,
                                    'active'        => $val->account_active,
                                    'password'      => md5($val->number.'123456'),
                                    'create_time'   => $now,
                                    'create_user'   => $user_id,
                                    'update_time'   => $now,
                                    'update_user'   => $user_id
                            );
                            
                            $newUserId = $user->insert($data);
                            
                            // 初始化用户角色为普通用户
                            $roleMember = new Admin_Model_Member();
                            
                            try {
                                $roleMember->insert(array('user_id'   => $newUserId));
                            } catch (Exception $e) {
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
                }
            }
        }
        
        if(count($deleted) > 0){
            foreach ($deleted as $val){
                if($user->fetchAll("employee_id = ".$val->id)->count() == 0){
                    try {
                        $employee->delete("id = ".$val->id);
                    } catch (Exception $e){
                        $result['success'] = false;
                        $result['info'] = $e->getMessage();
                    
                        echo Zend_Json::encode($result);
                    
                        exit;
                    }
                }else{
                    $result['success'] = false;
                    $result['info'] = '员工ID'.$val->id.'存在关联系统账号，不能删除';
                    
                    echo Zend_Json::encode($result);
                    
                    exit;
                } 
            }
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    /**
     * 编辑职位信息
     */
    public function editpostAction()
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
        
        $post = new Hra_Model_Post();
        
        if(count($updated) > 0){
            foreach ($updated as $val){
                $data = array(
                        'active'        => $val->active,
                        'type_id'       => $val->type_id,
                        'name'          => $val->name,
                        'description'   => $val->description,
                        'remark'        => $val->remark,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
                
                if($post->fetchAll("id != ".$val->id." and name = '".$val->name."'")->count() > 0){
                    $result['success'] = false;
                    $result['info'] = '职位：'.$val->name."已存在，请勿重复添加！";
                }else{
                    try {
                        $post->update($data, "id = ".$val->id);
                    } catch (Exception $e) {
                        $result['success'] = false;
                        $result['info'] = $e->getMessage();
                    
                        echo Zend_Json::encode($result);
                    
                        exit;
                    }
                }
            }
        }
        
        if(count($inserted) > 0){
            foreach ($inserted as $val){
                $data = array(
                        'active'        => $val->active,
                        'type_id'       => $val->type_id,
                        'name'          => $val->name,
                        'description'   => $val->description,
                        'remark'        => $val->remark,
                        'create_time'   => $now,
                        'create_user'   => $user_id,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
                
                if($post->fetchAll("name = '".$val->name."'")->count() > 0){
                    $result['success'] = false;
                    $result['info'] = '职位：'.$val->name."已存在，请勿重复添加！";
                }else{
                    try{
                        $post->insert($data);
                    } catch (Exception $e){
                        $result['success'] = false;
                        $result['info'] = $e->getMessage();
                    
                        echo Zend_Json::encode($result);
                    
                        exit;
                    }
                }
            }
        }
        
        if(count($deleted) > 0){
            $employee = new Hra_Model_Employee();
            
            foreach ($deleted as $val){
                if($employee->fetchAll("post_id = ".$val->id)->count() == 0){
                    try {
                        $post->delete("id = ".$val->id);
                    } catch (Exception $e){
                        $result['success'] = false;
                        $result['info'] = $e->getMessage();
                    
                        echo Zend_Json::encode($result);
                    
                        exit;
                    }
                }else{
                    $result['success'] = false;
                    $result['info'] = '职位ID'.$val->id.'存在关联员工信息，不能删除';
                    
                    echo Zend_Json::encode($result);
                    
                    exit;
                } 
            }
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    /**
     * 编辑用工形式信息
     */
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
        
        $type = new Hra_Model_Type();
        
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
                
                if($type->fetchAll("id != ".$val->id." and name = '".$val->name."'")->count() > 0){
                    $result['success'] = false;
                    $result['info'] = $val->name."已存在，请勿重复添加！";
                }else{
                    try {
                        $type->update($data, "id = ".$val->id);
                    } catch (Exception $e) {
                        $result['success'] = false;
                        $result['info'] = $e->getMessage();
                    
                        echo Zend_Json::encode($result);
                    
                        exit;
                    }
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
                
                if($type->fetchAll("name = '".$val->name."'")->count() > 0){
                    $result['success'] = false;
                    $result['info'] = $val->name."已存在，请勿重复添加！";
                }else{
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
        }
        
        if(count($deleted) > 0){
            $employee = new Hra_Model_Employee();
            
            foreach ($deleted as $val){
                if($employee->fetchAll("type_id = ".$val->id)->count() == 0){
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
                    $result['info'] = $val->id.'存在关联员工信息，不能删除';
                    
                    echo Zend_Json::encode($result);
                    
                    exit;
                } 
            }
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
}