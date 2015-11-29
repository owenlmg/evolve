<?php
/**
 * 2013-9-4 下午11:51:29
 * @author x.li
 * @abstract 
 */
class Erp_BpartnerController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $user_session = new Zend_Session_Namespace('user');
        
        $this->view->bpartnerAdminDisabled = 1;
        $this->view->supplierAdmin = 0;
        $this->view->customerAdmin = 0;
        $this->view->supplierView = 0;
        $this->view->customerView = 0;
        
        $this->view->user_id = 0;
        
        if(isset($user_session->user_info)){
            $this->view->user_id = $user_session->user_info['user_id'];
            
            if(Application_Model_User::checkPermissionByRoleName('业务伙伴管理员')){
                $this->view->bpartnerAdminDisabled = 0;
                $this->view->supplierView = 1;
                $this->view->customerView = 1;
            }
            
            if(Application_Model_User::checkPermissionByRoleName('业务伙伴-查看-供应商')){
                $this->view->supplierView = 1;
            }
            
            if(Application_Model_User::checkPermissionByRoleName('业务伙伴-查看-客户')){
                $this->view->customerView = 1;
            }
            
            if(Application_Model_User::checkPermissionByRoleName('供应商管理员') || Application_Model_User::checkPermissionByRoleName('系统管理员')){
                $this->view->supplierAdmin = 1;
            }
            
            if(Application_Model_User::checkPermissionByRoleName('客户管理员') || Application_Model_User::checkPermissionByRoleName('系统管理员')){
                $this->view->customerAdmin = 1;
            }
        }
    }
    
    public function getaddresscodelistAction()
    {
        $contact = new Erp_Model_Contact();
        
        $request = $this->getRequest()->getParams();
        
        $partner_id = isset($request['partner_id']) ? $request['partner_id'] : null;
        
        echo Zend_Json::encode($contact->getAddressCodeList(1, $partner_id));
        
        exit;
    }
    
    public function importcontactAction()
    {
    	$result = array(
    			'success'   => true,
    			'data'      => array(),
    			'info'      => '导入成功'
    	);
    	
    	if(isset($_FILES['csv'])){
    		$file = $_FILES['csv'];
    	
    		$file_extension = strrchr($file['name'], ".");
    	
    		$h = new Application_Model_Helpers();
    		$tmp_file_name = $h->getMicrotimeStr().$file_extension;
    	
    		$savepath = "../temp/";
    		$tmp_file_path = $savepath.$tmp_file_name;
    		move_uploaded_file($file["tmp_name"], $tmp_file_path);
    	
    		$file = fopen($tmp_file_path, "r");
    		$i = 0;
    	
    		$materiel = new Product_Model_Materiel();
    	
    		while(! feof($file))
    		{
    			$csv_data = fgetcsv($file);
    			
    			$name = isset($csv_data[1]) ? $csv_data[1] : '';
    			$post = isset($csv_data[2]) ? $csv_data[2] : '';
    			$tel = isset($csv_data[3]) ? $csv_data[3] : '';
    			$fax = isset($csv_data[4]) ? $csv_data[4] : '';
    			$email = isset($csv_data[5]) ? $csv_data[5] : '';
    			$person_liable = isset($csv_data[6]) ? $csv_data[6] : '';
    			$area_code = isset($csv_data[7]) ? $csv_data[7] : '';
    			$country = isset($csv_data[8]) ? $csv_data[8] : '';
    			$area = isset($csv_data[9]) ? $csv_data[9] : '';
    			$area_city = isset($csv_data[10]) ? $csv_data[10] : '';
    			$address = isset($csv_data[11]) ? $csv_data[11] : '';
    			$zip_code = isset($csv_data[12]) ? $csv_data[12] : '';
    			$remark = isset($csv_data[13]) ? $csv_data[13] : '';
    	
    			if($i > 0 && $name != ''){
    				array_push($result['data'], array(
	    				'name'      	=> $name,
	    				'post'       	=> $post,
	    				'tel'         	=> $tel,
	    				'fax'        	=> $fax,
	    				'email'       	=> $email,
	    				'person_liable'	=> $person_liable,
	    				'area_code'  	=> $area_code,
	    				'country'    	=> $country,
	    				'area'      	=> $area,
	    				'area_city'    	=> $area_city,
	    				'address'      	=> $address,
	    				'zip_code'    	=> $zip_code,
	    				'remark'      	=> $remark
    				));
    			}
    	
    			$i++;
    		}
    	
    		fclose($file);
    	}else{
    		$result['success'] = false;
    		$result['info'] = '没有选择文件，导入失败！';
    	}
    	/* echo '<pre>';
    	print_r($result);
    	exit; */
    	echo Zend_Json::encode($result);
    	
    	exit;
    }
    
    /**
     * 获取业务伙伴信息列表
     */
    public function getpartnerAction()
    {
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        $export = isset($request['export']) ? $request['export'] : 0;
        $option = isset($request['option']) ? $request['option'] : 'data';
        $type = isset($request['type']) ? $request['type'] : 0;
        
        $partner = new Erp_Model_Partner();
        
        if($option == 'list'){
            echo Zend_Json::encode($partner->getList($type));
        }else{
            // 查询条件
            $condition = array(
                    'type'  => isset($request['type']) ? $request['type'] : 0,
                    'key'   => isset($request['key']) ? $request['key'] : '',
                    'group' => isset($request['group_id']) ? $request['group_id'] : '',
                    'page'  => isset($request['page']) ? $request['page'] : 1,
                    'limit' => isset($request['limit']) ? $request['limit'] : 0,
                    'export'=> $export
            );
            
            $data = $partner->getData($condition);
            
            if($export){
                $this->view->layout()->disableLayout();
                $this->_helper->viewRenderer->setNoRender(true);
            
                $h = new Application_Model_Helpers();
                $h->exportCsv($data, '业务伙伴');
            }else{
                echo Zend_Json::encode($data);
            }
        }
        
        exit;
    }
    
    /**
     * 编辑业务伙伴（新建、更新、删除）
     */
    public function editpartnerAction()
    {
        // 返回值数组
        $result = array(
                'success'       => true,
                'info'          => '编辑成功',
                'partner_id'    => 0
        );
        
        $request = $this->getRequest()->getParams();
        
        // 操作类别（新建、更新、删除）
        $type = isset($request['edit_type']) ? $request['edit_type'] : '';
        
        $partner = new Erp_Model_Partner();
        
        if($type == 'new' || $type == 'edit'){
            $now = date('Y-m-d H:i:s');
            $user_session = new Zend_Session_Namespace('user');
            $user_id = $user_session->user_info['user_id'];
            
            $partner_type = 0;
            if($request['type'] == '客户' || $request['type'] == 1){
                $partner_type = 1;
            }
            
            $data = array(
                    'active'            => $request['active'],
                    'type'              => $partner_type,
                    'code'              => $request['code'],
                    'group_id'          => $request['group_id'],
                    'cname'             => $request['cname'],
                    'ename'             => $request['ename'],
                    'bank_payment_days' => $request['bank_payment_days'],
                    'bank_country'      => $request['bank_country'],
                    'bank_currency'     => $request['bank_currency'],
                    'tax_id'            => $request['tax_id'],
                    'bank_type'         => $request['bank_type'],
                    'bank_account'      => $request['bank_account'],
                    'tax_num'           => $request['tax_num'],
                    'rsm'               => $request['rsm'],
                    'terminal_customer' => $request['terminal_customer'],
                    'suffix'            => $request['suffix'],
                    'bank_name'         => $request['bank_name'],
                    'bank_remark'       => $request['bank_remark'],
                    'remark'            => $request['remark'],
                    'update_time'       => $now,
                    'update_user'       => $user_id
            );
            
            if ($type == 'new') {
                if ($partner->fetchAll("type = ".$partner_type." and (code = '".$request['code']."' or (cname != '' and cname = '".$request['cname']."') or (ename != '' and ename = '".$request['ename']."'))")->count() > 0) {
                    $result['success'] = false;
                    $result['info'] = '代码重复，添加失败！';
                    
                    echo Zend_Json::encode($result);
                    
                    exit;
                }else{
                    $data['create_time'] = $now;
                    $data['create_user'] = $user_id;
                    
                    try{
                        $result['partner_id'] = $partner->insert($data);
                    } catch (Exception $e){
                        $result['success'] = false;
                        $result['info'] = $e->getMessage();
                    
                        echo Zend_Json::encode($result);
                    
                        exit;
                    }
                }
            }elseif ($type == 'edit'){
                if ($partner->fetchAll("id != ".$request['partner_id']." and type = ".$partner_type." and (code = '".$request['code']."')")->count() > 0) {
                    $result['success'] = false;
                    $result['info'] = '代码重复，添加失败！';
                
                    echo Zend_Json::encode($result);
                
                    exit;
                }else{
                    try {
                        $partner->update($data, "id = ".$request['partner_id']);
                        $result['partner_id'] = $request['partner_id'];
                    } catch (Exception $e) {
                        $result['success'] = false;
                        $result['info'] = $e->getMessage();
                    
                        echo Zend_Json::encode($result);
                    
                        exit;
                    }
                }
            }
        }elseif ($type == 'delete'){
            try {
                $partner->delete("id = ".$request['partner_id']);
            } catch (Exception $e){
                $result['success'] = false;
                $result['info'] = $e->getMessage();
            
                echo Zend_Json::encode($result);
            
                exit;
            }
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    /**
     * 保存业务伙伴的联系人及地址信息
     */
    public function editlistinfoAction()
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
        
        $partner_id = $json->partner_id;
        
        $json_contact = $json->contact;
        
        $contact_updated    = $json_contact->updated;
        $contact_inserted   = $json_contact->inserted;
        $contact_deleted    = $json_contact->deleted;
        
        $contact = new Erp_Model_Contact();
        
        // 更新联系人
        if(count($contact_updated) > 0){
            foreach ($contact_updated as $val){
                $active = $val->contact_active ? 1 : 0;
                $default = $val->contact_default ? 1 : 0;
                
                $data = array(
                        'active'    => $active,
                        'default'   => $default,
                        'name'      => $val->contact_name,
                        'post'      => $val->contact_post,
                        'tel'       => $val->contact_tel,
                        'fax'       => $val->contact_fax,
                        'email'     => $val->contact_email,
                        'country'   => $val->contact_country,
                        'area'      => $val->contact_area,
                        'area_city' => $val->contact_area_city,
                        'area_code' => $val->contact_area_code,
                        'person_liable' => $val->contact_person_liable,
                        'address'   => $val->contact_address,
                        'zip_code'  => $val->contact_zip_code,
                        'remark'    => $val->contact_remark
                );
                
                try {
                    $contact->update($data, "id = ".$val->contact_id);
                } catch (Exception $e) {
                    $result['result'] = false;
                    $result['info'] = $e->getMessage();
                
                    echo Zend_Json::encode($result);
                
                    exit;
                }
            }
        }
        
        // 插入联系人
        if(count($contact_inserted) > 0){
            foreach ($contact_inserted as $val){
                $active = $val->contact_active ? 1 : 0;
                $default = $val->contact_default ? 1 : 0;
                
                $data = array(
                        'partner_id'    => $partner_id,
                        'active'        => $active,
                        'default'       => $default,
                        'name'          => $val->contact_name,
                        'post'          => $val->contact_post,
                        'tel'           => $val->contact_tel,
                        'fax'           => $val->contact_fax,
                        'email'         => $val->contact_email,
                        'country'       => $val->contact_country,
                        'area'          => $val->contact_area,
                        'area_city'     => $val->contact_area_city,
                        'area_code'     => $val->contact_area_code,
                        'person_liable' => $val->contact_person_liable,
                        'address'       => $val->contact_address,
                        'zip_code'      => $val->contact_zip_code,
                        'remark'        => $val->contact_remark
                );
                
                try {
                    $contact->insert($data);
                } catch (Exception $e) {
                    $result['result'] = false;
                    $result['info'] = $e->getMessage();
        
                    echo Zend_Json::encode($result);
        
                    exit;
                }
            }
        }
        
        // 删除联系人
        if(count($contact_deleted) > 0){
            foreach ($contact_deleted as $val){
                try {
                    $contact->delete("id = ".$val->contact_id);
                } catch (Exception $e){
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
    
    /**
     * 根据业务伙伴ID获取联系方式列表
     */
    public function getcontactAction()
    {
        $data = array();
        
        $request = $this->getRequest()->getParams();
        
        $option = isset($request['option']) ? $request['option'] : 'data';
        
        $partner_id = isset($request['partner_id']) ? $request['partner_id'] : 0;
        
        if($partner_id > 0){
            $contact = new Erp_Model_Contact();
            
            $record = $contact->fetchAll("partner_id = ".$partner_id, 'CONVERT( name USING gbk )')->toArray();
            
            if($option == 'list'){
                foreach ($record as $rec){
                    $active = $rec['active'] == 1 ? true : false;
                    $default = $rec['default'] == 1 ? true : false;
                    
                    array_push($data, array(
                        'id'            => $rec['id'],
                        'partner_id'    => $rec['partner_id'],
                        'active'        => $active,
                        'default'       => $default,
                        'name'          => $rec['name'],
                        'post'          => $rec['post'],
                        'tel'           => $rec['tel'],
                        'fax'           => $rec['fax'],
                        'email'         => $rec['email'],
                        'country'       => $rec['country'],
                        'area'          => $rec['area'],
                        'address'       => $rec['address'],
                        'zip_code'      => $rec['zip_code'],
                        'remark'        => $rec['remark']
                    ));
                }
            }else{
                foreach ($record as $rec){
                    $active = $rec['active'] == 1 ? true : false;
                    $default = $rec['default'] == 1 ? true : false;
                    
                    array_push($data, array(
                        'contact_id'            => $rec['id'],
                        'contact_partner_id'    => $rec['partner_id'],
                        'contact_active'        => $active,
                        'contact_default'       => $default,
                        'contact_name'          => $rec['name'],
                        'contact_post'          => $rec['post'],
                        'contact_tel'           => $rec['tel'],
                        'contact_fax'           => $rec['fax'],
                        'contact_email'         => $rec['email'],
                        'contact_country'       => $rec['country'],
                        'contact_area'          => $rec['area'],
                        'contact_area_city'     => $rec['area_city'],
                        'contact_area_code'     => $rec['area_code'],
                        'contact_person_liable' => $rec['person_liable'],
                        'contact_address'       => $rec['address'],
                        'contact_zip_code'      => $rec['zip_code'],
                        'contact_remark'        => $rec['remark']
                    ));
                }
            }
        }
        
        echo Zend_Json::encode($data);
        
        exit;
    }
    
    /**
     * 根据业务伙伴ID获取联系地址列表
     */
    public function getaddressAction()
    {
        $data = array();
        
        $request = $this->getRequest()->getParams();
        
        $partner_id = isset($request['partner_id']) ? $request['partner_id'] : 0;
        
        if($partner_id > 0){
            $address = new Erp_Model_Address();
            
            $record = $address->fetchAll("partner_id = ".$partner_id)->toArray();
            
            foreach ($record as $rec){
                $active = $rec['active'] == 1 ? true : false;
                
                array_push($data, array(
                    'address_id'            => $rec['id'],
                    'address_partner_id'    => $rec['partner_id'],
                    'address_active'        => $active,
                    'address_country'       => $rec['country'],
                    'address_area'          => $rec['area'],
                    'address_name'          => $rec['name'],
                    'address_zip_code'      => $rec['zip_code'],
                    'address_remark'        => $rec['remark']
                ));
            }
        }
        
        echo Zend_Json::encode($data);
        
        exit;
    }
    
    /**
     * 获取组信息
     */
    public function getgroupAction()
    {
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        $type = isset($request['type']) ? $request['type'] : 0;
        
        $option = isset($request['option']) ? $request['option'] : 'list';
        
        $group = new Erp_Model_Group();
        
        if($option == 'list'){
            echo Zend_Json::encode($group->getList($type));
        }else{
            echo Zend_Json::encode($group->getData($type));
        }
        
        exit;
    }
    
    /**
     * 编辑组信息
     */
    public function editgroupAction()
    {
        // 返回值数组
        $result = array(
                'success'   => true,
                'info'      => '编辑成功'
        );
        
        $request = $this->getRequest()->getParams();
        
        $type = isset($request['type']) ? $request['type'] : 0;
        
        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user_id = $user_session->user_info['user_id'];
    
        $json = json_decode($request['json']);
    
        $updated    = $json->updated;
        $inserted   = $json->inserted;
        $deleted    = $json->deleted;
    
        $group = new Erp_Model_Group();
    
        if(count($updated) > 0){
            foreach ($updated as $val){
                $data = array(
                        'active'        => $val->active,
                        'type'          => $type,
                        'name'          => $val->name,
                        'description'   => $val->description,
                        'remark'        => $val->remark,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
    
                $where = "id = ".$val->id;
    
                try {
                    $group->update($data, $where);
                } catch (Exception $e) {
                    $result['result'] = false;
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
                        'type'          => $type,
                        'name'          => $val->name,
                        'description'   => $val->description,
                        'remark'        => $val->remark,
                        'create_time'   => $now,
                        'create_user'   => $user_id,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
    
                try{
                    $group->insert($data);
                } catch (Exception $e){
                    $result['result'] = false;
                    $result['info'] = $e->getMessage();
    
                    echo Zend_Json::encode($result);
    
                    exit;
                }
            }
        }
    
        if(count($deleted) > 0){
            $partner = new Erp_Model_Partner();
    
            foreach ($deleted as $val){
                if($partner->fetchAll("group_id = ".$val->id)->count() == 0){
                    try {
                        $group->delete("id = ".$val->id);
                    } catch (Exception $e){
                        $result['result'] = false;
                        $result['info'] = $e->getMessage();
    
                        echo Zend_Json::encode($result);
    
                        exit;
                    }
                }else{
                    $result['result'] = false;
                    $result['info'] = '组ID'.$val->id.'存在关联业务伙伴信息，不能删除';
    
                    echo Zend_Json::encode($result);
    
                    exit;
                }
            }
        }
    
        echo Zend_Json::encode($result);
    
        exit;
    }
    
    /**
     * 获取付款方式信息
     */
    public function getpaymentAction()
    {
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        $option = isset($request['option']) ? $request['option'] : 'list';
        
        $payment = new Erp_Model_Payment();
        
        if($option == 'list'){
            echo Zend_Json::encode($payment->getList());
        }else{
            echo Zend_Json::encode($payment->getData());
        }
        
        exit;
    }
    
    /**
     * 编辑付款方式信息
     */
    public function editpaymentAction()
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
    
        $payment = new Erp_Model_Payment();
    
        if(count($updated) > 0){
            foreach ($updated as $val){
                $data = array(
                        'active'        => $val->active,
                        'qty'           => $val->qty,
                        'name'          => $val->name,
                        'description'   => $val->description,
                        'remark'        => $val->remark,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
    
                $where = "id = ".$val->id;
    
                try {
                    $payment->update($data, $where);
                } catch (Exception $e) {
                    $result['result'] = false;
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
                        'qty'           => $val->qty,
                        'name'          => $val->name,
                        'description'   => $val->description,
                        'remark'        => $val->remark,
                        'create_time'   => $now,
                        'create_user'   => $user_id,
                        'update_time'   => $now,
                        'update_user'   => $user_id
                );
    
                try{
                    $payment->insert($data);
                } catch (Exception $e){
                    $result['result'] = false;
                    $result['info'] = $e->getMessage();
    
                    echo Zend_Json::encode($result);
    
                    exit;
                }
            }
        }
    
        if(count($deleted) > 0){
            $partner = new Erp_Model_Partner();
    
            foreach ($deleted as $val){
                if($partner->fetchAll("payment_id = ".$val->id)->count() == 0){
                    try {
                        $payment->delete("id = ".$val->id);
                    } catch (Exception $e){
                        $result['result'] = false;
                        $result['info'] = $e->getMessage();
    
                        echo Zend_Json::encode($result);
    
                        exit;
                    }
                }else{
                    $result['result'] = false;
                    $result['info'] = '付款方式ID'.$val->id.'存在关联业务伙伴信息，不能删除';
    
                    echo Zend_Json::encode($result);
    
                    exit;
                }
            }
        }
    
        echo Zend_Json::encode($result);
    
        exit;
    }
}