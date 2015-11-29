<?php
/**
 * 2013-11-25 上午12:08:30
 * @author x.li
 * @abstract 
 */
class Product_Model_Catalog extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'product_catalog';
    protected $_primary = 'id';
    
    public function getCodeList($key = null)
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array(
                            'customer_code' => 'code_customer', 
                            'customer_description' => 'description_customer', 
                            'code' => 'model_internal', 
                            'text' => 'model_internal', 
                            'description'
                    ))
                    ->joinLeft(array('t2' => $this->_dbprefix.'product_catalog_type'), 't1.type_id = t2.id', array('product_type' => 'name'))
                    ->joinLeft(array('t3' => $this->_dbprefix.'product_catalog_series'), 't1.series_id = t3.id', array('product_series' => 'name'))
                    ->where("t1.active = 1 and t1.`delete` = 0 and t1.review = 2");
        
        if ($key) {
            $sql->where("t1.model_standard like '".$key."%' 
                    or t1.model_internal like '".$key."%' 
                    or t1.code like '".$key."%' 
                    or t1.description like '%".$key."%' 
                    or t1.code_customer like '".$key."%'");
            //$sql->where("description like '%".$key."%' or code_customer like '%".$key."%' or description_customer like '%".$key."%' or model_internal like '%".$key."%'");
        }
        
        $data = $this->fetchAll($sql)->toArray();
        
        for ($i = 0; $i < count($data); $i++){
            $data[$i]['unit'] = '个';
        }
        
        return $data;
    }
    
    public function getProposeReviewUserList($type = 'create_user')
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array('id' => $type))
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t2.id = t1.".$type, array())
                    ->join(array('t3' => $this->_dbprefix.'employee'), "t3.id = t2.employee_id", array('name' => 'cname'))
                    ->group("t1.".$type)
                    ->order("t3.cname");
        $data = $this->fetchAll($sql)->toArray();
        
        return $data;
    }

    public function getData($condition = array())
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'product_catalog_type'), "t1.type_id = t2.id", array('type_name' => 'name'))
                    ->joinLeft(array('t3' => $this->_dbprefix.'product_catalog_stage'), "t1.stage_id = t3.id", array('stage_name' => 'name'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'product_catalog_series'), "t1.series_id = t4.id", array('series_name' => 'name'))
                    ->joinLeft(array('t5' => $this->_dbprefix.'product_catalog_developmode'), "t1.developmode_id = t5.id", array('developmode_name' => 'name'))
                    ->joinLeft(array('t6' => $this->_dbprefix.'user'), "t1.create_user = t6.id", array())
                    ->joinLeft(array('t7' => $this->_dbprefix.'employee'), "t6.employee_id = t7.id", array('creater' => 'cname'))
                    ->joinLeft(array('t8' => $this->_dbprefix.'user'), "t1.auditor_id = t8.id", array())
                    ->joinLeft(array('t9' => $this->_dbprefix.'employee'), "t8.employee_id = t9.id", array('auditor' => 'cname'))
                    ->where("t1.active = ".$condition['active'])
                    ->order(array('t1.review', 't1.create_time desc'));
        
        if($condition['display_deleted'] == 0){
            $sql->where("t1.`delete` = 0");
        }
        
        if($condition['key']){
            $sql->where("t1.model_standard like '%".$condition['key']."%' or t1.model_internal like '%".$condition['key']."%' or t1.code like '%".$condition['key']."%' or t1.description like '%".$condition['key']."%' or t1.code_customer like '%".$condition['key']."%'");
        }
        
        if($condition['date_from']){
            $sql->where("t1.create_time >= '".$condition['date_from']." 00:00:00'");
        }
        
        if($condition['date_to']){
            $sql->where("t1.create_time <= '".$condition['date_to']." 23:59:59'");
        }
        
        if($condition['type_id'] && $condition['type_id'] != 'undefined'){
            $sql->where("t1.type_id = ".$condition['type_id']);
        }
        
        if($condition['series_id'] != 'undefined' && $condition['series_id']){
            $sql->where("t1.series_id = ".$condition['series_id']);
        }
        
        if($condition['stage_id']){
            $sql->where("t1.stage_id = ".$condition['stage_id']);
        }
        
        if(isset($condition['have_code']) && $condition['have_code'] === '0'){
            $sql->where("t1.code = '' or t1.code is null");
        }else if (isset($condition['have_code']) && $condition['have_code'] === '1'){
            $sql->where("t1.code != '' and t1.code is not null");
        }
        
        if($condition['developmode_id'] && $condition['developmode_id'] != 'null'){
            $sql->where("t1.developmode_id = ".$condition['developmode_id']);
        }
        
        if($condition['create_user'] && $condition['create_user'] != 'null'){
            $sql->where("t1.create_user = ".$condition['create_user']);
        }
        
        if($condition['auditor_id'] && $condition['auditor_id'] != 'null'){
            $sql->where("t1.auditor_id = ".$condition['auditor_id']);
        }
        
        if($condition['evt_date']){
            $sql->where("t1.evt_date = '".$condition['evt_date']."'");
        }
        
        if($condition['dvt_date']){
            $sql->where("t1.date_dvt = '".$condition['dvt_date']."'");
        }
        
        if($condition['dvt_date']){
            $sql->where("t1.date_dvt = '".$condition['dvt_date']."'");
        }
        
        if($condition['qa1_date']){
            $sql->where("t1.qa1_date = '".$condition['qa1_date']."'");
        }
        
        if($condition['qa2_date']){
            $sql->where("t1.qa2_date = '".$condition['qa2_date']."'");
        }
        
        if($condition['mass_production_date']){
            $sql->where("t1.mass_production_date = '".$condition['mass_production_date']."'");
        }
        /* echo $sql;
        exit; */
        $total = $this->fetchAll($sql)->count();
        
        if($condition['type'] == 'data'){
            $sql->limitPage($condition['page'], $condition['limit']);
        }
        
        $data = $this->fetchAll($sql)->toArray();
        $user_session = new Zend_Session_Namespace('user');
        $employee_id = $user_session->user_info['employee_id'];
        $h = new Application_Model_Helpers();

        for($i = 0; $i < count($data); $i++){
            $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
            $data[$i]['update_time'] = strtotime($data[$i]['update_time']);
            $data[$i]['auditor_time'] = strtotime($data[$i]['auditor_time']);
            $data[$i]['active'] = $data[$i]['active'] == 1 ? true : false;
            $data[$i]['review_info'] = isset($data[$i]['review_info']) ? $data[$i]['review_info'] : '';
            $data[$i]['description'] = $h->deletehtml($data[$i]['description']);
            $data[$i]['remark'] = $h->deletehtml($data[$i]['remark']);
            $data[$i]['review_info_tip'] = $data[$i]['review_info'];
            $data[$i]['review_info'] = str_replace('<br>', ' > ', $data[$i]['review_info']);
            
            if ($data[$i]['review'] == 0){
                $review_state = $h->getReviewState('product_add', $data[$i]['id']);
                $data[$i]['reviewer'] = implode(',', $h->getEmployeeNameByIdArr($review_state['reviewer']));
                //echo $data[$i]['reviewer'];exit;
                $data[$i]['can_review'] = in_array($employee_id, $review_state['reviewer']) && !in_array($employee_id, $review_state['reviewer_finished']) ? 1 : 0;
            }
        }
        
        if($condition['type'] == 'csv'){
            $data_csv = array();
            
            $title = array(
                    'cnt'                   => '#',
                    'type_name'             => '产品分类',
                    'series_name'           => '产品系列',
                    'model_internal'        => '内部型号',
                    'review'                => '审核状态',
                    'model_standard'        => '标准型号',
                    'code'                  => '产品代码',
                    'code_old'              => '旧产品代码',
                    'description'           => '产品描述',
                    'code_customer'         => '客户代码',
                    'model_customer'        => '客户型号',
                    'description_customer'  => '客户产品描述',
                    'developmode_name'      => '产品开发模式',
                    'stage_name'            => '产品阶段',
                    'evt_date'              => 'EVT通过日期',
                    'date_dvt'              => 'DVT通过日期',
                    'qa1_date'              => 'QA1通过日期',
                    'qa2_date'              => 'QA2通过日期',
                    'mass_production_date'  => '量产日期',
                    'remark'                => '备注',
                    'creater'               => '创建人',
                    'create_time'           => '创建时间',
                    'auditor'               => '审核人',
                    'auditor_time'          => '审核时间'
            );
        
            array_push($data_csv, $title);
        
            $i = 0;
            
            foreach ($data as $d){
                $i++;
                
                if($d['review'] == 0){
                    $d['review'] = '未审核';
                }else if($d['review'] == 1){
                    $d['review'] = '拒绝';
                }else if($d['review'] == 2){
                    $d['review'] = '已审核';
                }
                
                $info = array(
                    'cnt'                   => $i,
                    'type_name'             => $d['type_name'],
                    'series_name'           => $d['series_name'],
                    'model_internal'        => $d['model_internal'],
                    'review'                => $d['review'],
                    'model_standard'        => $d['model_standard'],
                    'code'                  => $d['code'],
                    'code_old'              => $d['code_old'],
                    'description'           => strip_tags($d['description']),
                    'code_customer'         => $d['code_customer'],
                    'model_customer'        => $d['model_customer'],
                    'description_customer'  => $d['description_customer'],
                    'developmode_name'      => $d['developmode_name'],
                    'stage_name'            => $d['stage_name'],
                    'evt_date'              => $d['evt_date'],
                    'date_dvt'              => $d['date_dvt'],
                    'qa1_date'              => $d['qa1_date'],
                    'qa2_date'              => $d['qa2_date'],
                    'mass_production_date'  => $d['mass_production_date'],
                    'remark'                => $d['remark'],
                    'creater'               => $d['creater'],
                    'create_time'           => date('Y-m-d H:i:s', $d['create_time']),
                    'auditor'               => $d['auditor'],
                    'auditor_time'          => date('Y-m-d H:i:s', $d['auditor_time'])
                );
        
                array_push($data_csv, $info);
            }
            
            return $data_csv;
        }

        return array('total' => $total, 'rows' => $data);
    }
}