<?php
/**
 * 2014-7-7 下午10:40:19
 * @author x.li
 * @abstract
 */
class Erp_Bpartner_CustomercontactController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $this->view->customerAdmin = 0;
         
        if(Application_Model_User::checkPermissionByRoleName('客户管理员') 
              || Application_Model_User::checkPermissionByRoleName('系统管理员')){
            $this->view->customerAdmin = 1;
        }
    }
    
    public function editcontactAction()
    {
        // 返回值数组
        $result = array(
                'success'       => true,
                'info'          => '提交成功'
        );
        
        $request = $this->getRequest()->getParams();
        
        // 操作类别（新建、更新、删除）
        $operate = isset($request['operate']) ? $request['operate'] : null;
        
        $customer_id = isset($request['customer_id']) ? $request['customer_id'] : null;
        $area_code = isset($request['area_code']) ? $request['area_code'] : null;
        
        $contact = new Erp_Model_Contact();
        
        if($operate == 'delete' && isset($request['id'])){
            $contact->delete("id = ".$request['id']);
        }else if($area_code){
            if($operate && $customer_id){
                if(($operate == 'new' && $contact->fetchAll("area_code = '".$request['area_code']."'")->count() > 0) ||
                   ($operate == 'edit' && $contact->fetchAll("id != ".$request['id']." and area_code = '".$request['area_code']."'")->count() > 0)){
                    $result['success'] = false;
                    $result['info'] = '地址简码重复，操作失败！';
                }else{
                    $now = date('Y-m-d H:i:s');
                    $user_session = new Zend_Session_Namespace('user');
                    $user_id = $user_session->user_info['user_id'];
                    
                    $active = isset($request['active']) ? 1 : 0;
                    $default = isset($request['default']) ? 1 : 0;
                    
                    $data = array(
                            'partner_id'     => $customer_id,
                            'active'        => $active,
                            'default'        => $default,
                            'name'            => $request['name'],
                            'post'            => $request['post'],
                            'tel'            => $request['tel'],
                            'fax'            => $request['fax'],
                            'email'            => $request['email'],
                            'remark'        => $request['remark'],
                            'country'        => $request['country'],
                            'area'            => $request['area'],
                            'address'        => $request['address'],
                            'zip_code'        => $request['zip_code'],
                            'area_city'        => $request['area_city'],
                            'area_code'        => $request['area_code'],
                            'person_liable'    => $request['person_liable']
                    );
                    
                    /* echo '<pre>';
                     print_r($data);
                    exit; */
                    
                    if($operate == 'new'){
                        $contact_id = $contact->insert($data);
                    }else if($operate == 'edit'){
                        $contact_id = $request['id'];
                        $contact->update($data, "id = ".$contact_id);
                    }
                        
                    // 更新非默认联系人
                    if($default){
                        $contact->update(array('default' => 0), "id != ".$contact_id." and partner_id = ".$customer_id);
                    }
                }
            }else{
                $result['success'] = false;
                $result['info'] = '操作/客户ID为空，操作失败！';
            }
        }else{
            $result['success'] = false;
            $result['info'] = '地址简码为空，操作失败！';
        }
        
        echo Zend_Json::encode($result);
        
        exit;
    }
    
    public function getcontactAction()
    {
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        $option = isset($request['option']) ? $request['option'] : 'list';
        
        $contact = new Erp_Model_Contact();
        
        // 查询条件
        $condition = array(
                'type'        => 1,
                'key'       => isset($request['key']) && $request['key'] != '' ? $request['key'] : null,
                'page'      => isset($request['page']) ? $request['page'] : 1,
                'limit'     => isset($request['limit']) ? $request['limit'] : 0,
                'option'    => $option
        );
        
        $data = $contact->getContact($condition);
        
        if($option == 'csv'){
            $this->view->layout()->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);
        
            $h = new Application_Model_Helpers();
            $h->exportCsv($data, '客户联系人');
        }else{
            echo Zend_Json::encode($data);
        }
        
        exit;
    }
}