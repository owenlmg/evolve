<?php
/**
 * 2013-7-6 下午10:32:26
 * @author x.li
 * @abstract 
 */
class Hra_Model_Employee extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'employee';
    protected $_primary = 'id';
    
    public function getManagerByUserId($user_id)
    {
        $manger = array();
        
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array())
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t2.employee_id = t1.id", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t3.id = t1.manager_id", array('employee_id' => 'id', 'email'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t4.employee_id = t3.id", array('user_id' => 'id'))
                    ->where("t2.id = ".$user_id);
        
        $res = $this->fetchAll($sql);
        
        if ($res->count() > 0) {
            $data = $res->toArray();
            $manger = $data[0];
        }
        
        return $manger;
    }
    
    public function getDeptManagerByUserId($user_id)
    {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array())
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t2.employee_id = t1.id", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t3.id = t1.dept_manager_id", array('email'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t4.employee_id = t3.id", array('user_id' => 'id'))
                    ->where("t2.id = ".$user_id);
        
        $data = $this->fetchAll($sql)->toArray();
        
        return $data[0];
    }
    
    public function getSeparation($year, $type = 0)
    {
        $data = array();
        
        for($i = 1; $i <= 12; $i++){
            $ym = $year.'-'.str_pad($i, 2, '0', STR_PAD_LEFT);
            
            $start = $ym.'-01';
            $end = date('Y-m-t', strtotime($start));
            
            // 月初人数
            $start_qty = $this->fetchAll("active = 1 and employment_type = ".$type." and entry_date <= '".$start."'")->count();
            
            // 月末人数
            $end_qty = $this->fetchAll("active = 1 and employment_type = ".$type." and entry_date <= '".$end."'")->count();
            
            // 入职人数
            $in_qty = $this->fetchAll("active = 1 and employment_type = ".$type." and entry_date >= '".$start."' and entry_date <= '".$end."'")->count();
            
            // 离职人数
            $in_qty = $this->fetchAll("active = 1 and employment_type = ".$type." and leave_date >= '".$start."' and leave_date <= '".$end."'")->count();
        }
    }
    
    /**
     * 获取人员组织结构
     * @param unknown $type
     */
    public function getStructure($type)
    {
        $data = array();
        
        if($type == 'education'){// 学历
            $sql = $this->select()
                        ->from($this->_name, array('name' => 'education', 'qty' => 'count(*)'))
                        ->where("active = 1 and education is not null and education != ''")
                        ->group("education")
                        ->order("qty");
            
            $data = $this->fetchAll($sql)->toArray();
        }else if($type == 'area'){// 地区
            $sql = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(array('t1' => $this->_name), array())
                        ->joinLeft(array('t2' => $this->_dbprefix.'public_options'), "t1.area_id = t2.id", array('name', 'qty' => 'count(*)'))
                        ->where("t1.active = 1 and t1.area_id is not null and t2.name is not null")
                        ->group("t2.name")
                        ->order("qty");
            
            $data = $this->fetchAll($sql)->toArray();
        }else if($type == 'professional_qualifications'){// 技术职称
            $sql = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(array('t1' => $this->_name), array())
                        ->joinLeft(array('t2' => $this->_dbprefix.'public_options'), "t1.professional_qualifications_id = t2.id", array('name', 'qty' => 'count(*)'))
                        ->where("t1.active = 1 and t1.professional_qualifications_id is not null and t2.name is not null")
                        ->group("t2.name")
                        ->order("qty");
            
            $data = $this->fetchAll($sql)->toArray();
        }else if($type == 'age'){// 年龄段
            $sql = $this->select()
                        ->from($this->_name, array('age' => 'floor(datediff(curdate(), birthday) / 3650)', 'qty' => 'count(*)'))
                        ->where("active = 1 and birthday is not null")
                        ->group("age")
                        ->order("qty");
                        
            $data = $this->fetchAll($sql)->toArray();
            
            for($i = 0; $i < count($data); $i++){
                $data[$i]['name'] = $data[$i]['age'].'0 - '.($data[$i]['age'] + 1).'0';
            }
        }else if($type == 'in'){// 入职年限
            $sql = $this->select()
                        ->from($this->_name, array('name' => 'floor(datediff(curdate(), entry_date) / 365)', 'qty' => 'count(*)'))
                        ->where("active = 1 and entry_date is not null")
                        ->group("name")
                        ->order("qty");
            
            $data = $this->fetchAll($sql)->toArray();
        }else if($type == 'sex'){// 性别
            $sql = $this->select()
                        ->from($this->_name, array('sex', 'qty' => 'count(*)'))
                        ->where("active = 1 and sex is not null")
                        ->group("sex")
                        ->order("qty");
            
            $data = $this->fetchAll($sql)->toArray();
            
            for($i = 0; $i < count($data); $i++){
                $data[$i]['name'] = $data[$i]['sex'] == 1 ? '男' : '女';
            }
        }else if($type == 'seniority'){// 工作年限
            $sql = $this->select()
                        ->from($this->_name, array('name' => 'work_years', 'qty' => 'count(*)'))
                        ->where("active = 1 and work_years is not null")
                        ->group("work_years")
                        ->order("qty");
            
            $dataArr = $this->fetchAll($sql)->toArray();
            
            $data = array(
                    array('name' => '< 1', 'qty' => 0),
                    array('name' => '1 - 3', 'qty' => 0),
                    array('name' => '3 - 5', 'qty' => 0),
                    array('name' => '> 5', 'qty' => 0)
            );
            
            foreach ($dataArr as $d){
                if($d['name'] < 1){
                    // 小于1
                    $data[0]['qty'] += $d['qty'];
                }else if($d['name'] >= 1 && $d['name'] < 3){
                    // 1 - 3
                    $data[1]['qty'] += $d['qty'];
                }else if($d['name'] >= 3 && $d['name'] < 5){
                    // 3 - 5
                    $data[2]['qty'] += $d['qty'];
                }else if($d['name'] >= 5){
                    // 大于5
                    $data[3]['qty'] += $d['qty'];
                }
            }
        }else if($type == 'post_type'){// 职位类别
            $sql = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(array('t1' => $this->_name), array())
                        ->joinLeft(array('t2' => $this->_dbprefix.'employee_post'), "t1.post_id = t2.id", array())
                        ->joinLeft(array('t3' => $this->_dbprefix.'public_options'), "t3.id = t2.type_id", array('name', 'qty' => 'count(*)'))
                        ->where("t1.active = 1 and t2.name is not null")
                        ->group("t3.name")
                        ->order("qty");
            $data = $this->fetchAll($sql)->toArray();
        }
        
        return $data;
    }
    
    /**
     * 根据员工入职日期获取员工入司年数
     * @param unknown $employee_id
     * @return number
     */
    public function getInCompanyYearQty($regularization_date)
    {
        $qty = 0;
        
        $in_year = date('Y', strtotime($regularization_date));
        $in_md = date('m-d', strtotime($regularization_date));
        
        $now_year = date('Y');
        
        if($in_year < $now_year){
            if(time() > strtotime($now_year.'-'.$in_md)){
                $qty = $now_year - $in_year;
            }else{
                $qty = $now_year - $in_year - 1;
            }
        }
        
        return $qty;
    }
    
    /**
     * 根据ID获取员工信息
     * @param unknown $id
     * @return Ambigous <multitype:, array>
     */
    public function getInfoById($id)
    {
        $data = array();
        
        $sql = $this->select()
                    ->from($this)
                    ->where("id = ".$id);
        
        if ($this->fetchAll($sql)->count() > 0) {
            $data = $this->fetchRow($sql)->toArray();
        }
        
        return $data;
    }
    
    public function getContacts($type = null, $dept = null, $key = null)
    {
        $data = array();
        
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array(
                            'id', 
                            'photo_path',
                            'type' => 'employment_type', 
                            'dept_id',
                            'number', 
                            'cname', 
                            'ename', 
                            'tel', 
                            'ext', 
                            'email'))
                    ->joinLeft(array('t2' => $this->_dbprefix.'employee_dept'), "t1.dept_id = t2.id", array('dept' => 'name'))
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t1.manager_id = t3.id", array("manager" => 'cname'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'employee'), "t1.dept_manager_id = t4.id", array("dept_manager" => 'cname'))
                    ->joinLeft(array('t5' => $this->_dbprefix.'employee_post'), "t1.post_id = t5.id", array('post' => 'name'))
                    ->where("t1.active = 1 and t1.hide = 0")
                    ->order('t1.ename');
        
        if($type !== null){
            $sql->where("t1.employment_type = ".$type);
        }
        
        if($dept){
            $sql->where("t1.dept_id = ".$dept);
        }
        
        if($key){
            $sql->where("t1.ename like '%".$key."%' or t1.cname like '%".$key."%' or t2.name like '%".$key."%' or t5.name like '%".$key."%'");
        }
        
        $data = $this->fetchAll($sql)->toArray();
        
        for ($i = 0; $i < count($data); $i++){
            $data[$i]['type_name'] = $data[$i]['type'] == 0 ? '弹性' : '非弹性';
            
            $data[$i]['photo_path'] = $data[$i]['photo_path'] == '' ? HOME_PATH.'/public/images/portrait.png' : HOME_PATH.'/public/'.$data[$i]['photo_path'];
        }
        
        return $data;
    }
    
    /**
     * 获取通讯录列表信息
     * @param int $parentid 部门ID
     * @param string $root
     * @return multitype:
     */
    public function getContactList($key = null, $dept_id = 0, $root = true)
    {
        $list = array();
        $data = array();
        
        if($key){
            $sql = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(array('t1' => $this->_name))
                        ->joinLeft(array('t2' => $this->_dbprefix.'employee_post'), "t2.id = t1.post_id", array('post' => 'name'))
                        ->joinLeft(array('t3' => $this->_dbprefix.'user'), "t3.employee_id = t1.id", array('user_id' => 'id'))
                        ->joinLeft(array('t4' => $this->_dbprefix.'employee_dept'), "t1.dept_id = t4.id", array('dept' => 'name'))
                        ->where("t1.active = 1 and (t1.ename like '%".$key."%' or t1.cname like '%".$key."%' or t1.number like '%".$key."%')")
                        ->order("CONVERT( t1.cname USING gbk )");
            
            $employeeData = $this->fetchAll($sql)->toArray();
            
            foreach ($employeeData as $e){
                array_push($data, array(
                'id'            => null,
                'parentId'      => null,
                'dept_id'       => null,
                'user_id'       => $e['user_id'],
                'employee_id'   => $e['id'],
                'text'          => '['.$e['number'].'] '.$e['cname'],
                'post'          => $e['post'],
                'dept'          => $e['dept'],
                'cname'         => $e['cname'],
                'ename'         => $e['ename'],
                'email'         => $e['email'],
                'tel'           => $e['tel'],
                'official_qq'   => $e['official_qq'],
                'work_place'    => $e['work_place'],
                'short_num'     => $e['short_num'],
                'tel'           => $e['msn'],
                'ext'           => $e['ext'],
                'state'         => 1,
                'iconCls'       => 'icon-hra-employee',
                'checked'       => false,
                'qtitle'        => '个人信息',
                'qalign'        => 'bl-tl',
                'qtip'          => '<p><b>中文名：</b>'.$e['cname'].'</p><p><b>英文名：</b>'.$e['ename'].'</p><p><b>部门：</b>'.$e['dept'].'</p><p><b>职位：</b>'.$e['post'].'</p><p><b>邮箱：</b>'.$e['email'].'</p><p><b>手机号：</b>'.$e['tel'].'</p><p><b>分机号：</b>'.$e['ext'].'</p>',
                'leaf'          => true
                ));
            }
        }else{
            // 查找当前部门的所有下级部门
            $dept = new Hra_Model_Dept();
            
            $deptData = $dept->fetchAll("active = 1 and parentid = ".$dept_id)->toArray();
            
            foreach ($deptData as $d){
                // 递归获取当前部门所有下级部门信息
                $dTemp = array(
                        'id'            => $d['id'],
                        'parentId'      => $dept_id,
                        'dept_id'       => null,
                        'user_id'       => null,
                        'employee_id'   => null,
                        'text'          => $d['name'],
                        'post'          => null,
                        'dept'          => null,
                        'cname'         => null,
                        'ename'         => null,
                        'email'         => null,
                        'tel'           => null,
                        'official_qq'   => null,
                        'short_num'     => null,
                        'msn'           => null,
                        'ext'           => null,
                        'state'         => 1,
                        'iconCls'       => 'icon-hra-dept',
                        'checked'       => false,
                        'qtitle'        => null,
                        'qtip'          => null,
                        'leaf'          => true
                );
                
                // 如果有下级部门，递归获取下级部门信息
                if($dept->fetchAll("active = 1 and parentid = ".$d['id'])->count() > 0 || $this->fetchAll("dept_id = ".$d['id'])->count() > 0){
                    $dTemp['children'] = $this->getContactList(null, $d['id'], false);
                    $dTemp['leaf'] = false;
                }
                
                // 如果有成员，获取成员信息
                
                
                array_push($data, $dTemp);
            }
            
            $employee = new Hra_Model_Employee();
            
            // 获取当前部门下员工信息
            $sql = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(array('t1' => $this->_name))
                        ->joinLeft(array('t2' => $this->_dbprefix.'employee_post'), "t2.id = t1.post_id", array('post' => 'name'))
                        ->joinLeft(array('t3' => $this->_dbprefix.'user'), "t3.employee_id = t1.id", array('user_id' => 'id'))
                        ->joinLeft(array('t4' => $this->_dbprefix.'employee_dept'), "t1.dept_id = t4.id", array('dept' => 'name'))
                        ->where("t1.active = 1 and t1.dept_id = ".$dept_id)
                        ->order("CONVERT( t1.cname USING gbk )");
            
            $employeeData = $this->fetchAll($sql)->toArray();
            
            foreach ($employeeData as $e){
                array_push($data, array(
                    'id'            => null,
                    'parentId'      => $dept_id,
                    'dept_id'       => $dept_id,
                    'user_id'       => $e['user_id'],
                    'employee_id'   => $e['id'],
                    'text'          => '['.$e['number'].'] '.$e['cname'],
                    'post'          => $e['post'],
                    'dept'          => $e['dept'],
                    'cname'         => $e['cname'],
                    'ename'         => $e['ename'],
                    'email'         => $e['email'],
                    'tel'           => $e['tel'],
                    'official_qq'   => $e['official_qq'],
                    'short_num'     => $e['short_num'],
                    'msn'           => $e['msn'],
                    'ext'           => $e['ext'],
                    'state'         => 1,
                    'iconCls'       => 'icon-hra-employee',
                    'checked'       => false,
                    'qtitle'        => '个人信息',
                    'qtip'          => '<p><b>中文名：</b>'.$e['cname'].'</p><p><b>英文名：</b>'.$e['ename'].'</p><p><b>职位：</b>'.$e['post'].'</p><p><b>邮箱：</b>'.$e['email'].'</p><p><b>手机号：</b>'.$e['tel'].'</p><p><b>分机号：</b>'.$e['ext'].'</p>',
                    'leaf'          => true
                ));
            }
        }
        
        // 格式化根数据格式
        if($root){
            $list = array(
                    'id'            => null,
                    'parentId'      => null,
                    'dept_id'       => null,
                    'user_id'       => null,
                    'employee_id'   => null,
                    'text'          => 'Root',
                    'post'          => null,
                    'dept'          => null,
                    'cname'         => null,
                    'ename'         => null,
                    'email'         => null,
                    'tel'           => null,
                    'official_qq'   => null,
                    'short_num'     => null,
                    'msn'           => null,
                    'ext'           => null,
                    'state'         => 1,
                    'iconCls'       => 'icon-hra-dept',
                    'leaf'          => false,
                    'checked'       => false,
                    'qtitle'        => null,
                    'qtip'          => null,
                    'children'      => $data
            );
        }else{
            $list = $data;
        }
        
        return $list;
    }
    
    public function getNameList()
    {
        $data = array();
        
        $sql = $this->select()
                    ->from($this, array('name' => new Zend_Db_Expr("concat(number, ' ', ename, ' ', cname)")))
                    ->order("number");
        
        $data = $this->fetchAll($sql)->toArray();
        
        return $data;
    }
    
    /**
     * 获取员工清单数据
     * @param   int     $badgenumber
     * @return  array   $data
     */
    public function getList($badgenumber)
    {
        // 当需要显示工号时，调整数据格式
        if($badgenumber == 1){
             $sql = $this->select()
                        ->from($this, array('id', 'name' => new Zend_Db_Expr("concat('[', number, '] ', cname)")))
                        ->order("CONVERT( cname USING gbk )");
        }else{
            $sql = $this->select()
                        ->from($this, array('id' => 'id', 'name' => 'cname'))
                        ->order("CONVERT( cname USING gbk )");
        }
        
        $data = $this->fetchAll($sql)->toArray();
        
        return $data;
    }
    
    public function getEmployeeByUserId($id)
    {
        $data = array();
        
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t1.id = t2.employee_id", array('account' => 'id', 'account_active' => 'active'))
                    ->joinLeft(array('t3' => $this->_dbprefix.'user'), "t1.create_user = t3.id", array())
                    ->joinLeft(array('t4' => $this->_name), "t3.employee_id = t4.id", array('creater' => 'cname'))
                    ->joinLeft(array('t5' => $this->_dbprefix.'user'), "t1.update_user = t5.id", array())
                    ->joinLeft(array('t6' => $this->_name), "t5.employee_id = t6.id", array('updater' => 'cname'))
                    ->joinLeft(array("t7" => $this->_dbprefix.'employee_dept'), "t1.dept_id = t7.id", array('dept' => 'name'))
                    ->joinLeft(array("t8" => $this->_dbprefix.'employee_post'), "t1.post_id = t8.id", array('post' => 'name'))
                    ->joinLeft(array("t9" => $this->_name), "t1.manager_id = t9.id", array('manager' => 'cname'))
                    ->joinLeft(array("t10" => $this->_name), "t1.dept_manager_id = t10.id", array('dept_manager' => 'cname'))
                    ->where("t2.id = ".$id);
        $data = $this->fetchRow($sql)->toArray();
        
        $data['employment_type'] = $data['employment_type'] == 0 ? '弹性' : '非弹性';
        
        if($data['politics_status'] == 0){
            $data['politics_status'] = '群众';
        }else if($data['politics_status'] == 1){
            $data['politics_status'] = '团员';
        }else if($data['politics_status'] == 2){
            $data['politics_status'] = '党员';
        }else if($data['politics_status'] == 3){
            $data['politics_status'] = '其他';
        }
        
        return array('success' => true, 'info' => $data);
    }
    
    /*
     * 根据工号获取用户ID
     */
    public function getUserIdByNumber($number)
    {
        $data = array();
        
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name), array())
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t1.id = t2.employee_id", array('user_id' => 'id'))
                    ->where("t1.number = ".$number);
        
        $data = $this->fetchRow($sql)->toArray();
        
        return $data['user_id'];
    }

    /**
     * 获取员工列表数据
     * @param   array $condition
     * @return  array $data
     */
    public function getData($condition = array())
    {//echo '<pre>';print_r($condition);exit;
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t1.id = t2.employee_id", array('account' => 'id', 'account_active' => 'active'))
                    ->joinLeft(array('t3' => $this->_dbprefix.'user'), "t1.create_user = t3.id", array())
                    ->joinLeft(array('t4' => $this->_name), "t3.employee_id = t4.id", array('creater' => 'cname'))
                    ->joinLeft(array('t5' => $this->_dbprefix.'user'), "t1.update_user = t5.id", array())
                    ->joinLeft(array('t6' => $this->_name), "t5.employee_id = t6.id", array('updater' => 'cname'))
                    ->joinLeft(array('t7' => $this->_dbprefix.'employee_dept'), "t7.id = t1.dept_id", array('dept_parentid' => 'parentid', 'dept_name' => 'name'))
                    ->joinLeft(array('t8' => $this->_dbprefix.'employee_post'), "t8.id = t1.post_id", array('post_name' => 'name'))
                    ->joinLeft(array('t9' => $this->_name), "t9.id = t1.manager_id", array('manager_name' => 'cname'))
                    ->joinLeft(array('t10' => $this->_dbprefix.'public_options'), "t10.id = t1.area_id", array('area' => 'name'))
                    ->joinLeft(array('t11' => $this->_dbprefix.'public_options'), "t11.id = t1.professional_qualifications_id", array('professional_qualifications' => 'name'))
                    ->joinLeft(array('t12' => $this->_name), "t12.id = t1.dept_manager_id", array('dept_manager_name' => 'cname'))
                    ->joinLeft(array('t13' => $this->_dbprefix.'employee_type'), "t13.id = t1.employment_type", array('employee_type_name' => 'name'))
                    ->where("t1.active = ".$condition['active'])
                    ->order(array('t1.number'));
        
        if ($condition['entry_date_from']){
            $sql->where("t1.entry_date >= '".$condition['entry_date_from']."'");
        }
        
        if ($condition['entry_date_to']){
            $sql->where("t1.entry_date <= '".$condition['entry_date_to']."'");
        }
        
        if ($condition['dept_id'] && $condition['dept_id'] != 'null'){
            $dept = json_decode($condition['dept_id']);
            
            if (count($dept)){
                $sql->where("t1.dept_id in (".implode(',', $dept).")");
            }
        }
        
        if ($condition['employment_type'] && $condition['employment_type'] != 'null'){
            $employment_type = json_decode($condition['employment_type']);
            
            if (count($employment_type)){
                $sql->where("t1.employment_type in (".implode(',', $employment_type).")");
            }
        }
        
        if ($condition['key']){
            $sql->where("t1.cname like '%".$condition['key']."%' or t1.ename like '%".$condition['key']."%' or t1.number like '%".$condition['key']."%'");
        }
        
        $total = $this->fetchAll($sql)->count();
        
        if($condition['type'] == 'data'){
            $sql->limitPage($condition['page'], $condition['limit']);
        }
        //echo $sql;exit;
        $data = $this->fetchAll($sql)->toArray();
        
        $deptModel = new Hra_Model_Dept();

        for($i = 0; $i < count($data); $i++){
            $data[$i]['dept_info'] = $data[$i]['dept_parentid'] > 0 ? $deptModel->getDeptInfoDesc($data[$i]['dept_id']) : $data[$i]['dept_name'];
            
            $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
            $data[$i]['update_time'] = strtotime($data[$i]['update_time']);
            $data[$i]['sex'] = intval($data[$i]['sex']);
            $data[$i]['driving_license'] = intval($data[$i]['driving_license']);
            $data[$i]['marital_status'] = intval($data[$i]['marital_status']);
            $data[$i]['politics_status'] = intval($data[$i]['politics_status']);
            $data[$i]['employment_type'] = intval($data[$i]['employment_type']);
            $data[$i]['hide'] = $data[$i]['hide'] == 1 ? true : false;
            $data[$i]['active'] = $data[$i]['active'] == 1 ? true : false;
            $data[$i]['leader'] = $data[$i]['leader'] == 1 ? true : false;
            $data[$i]['account'] = $data[$i]['account'] != '' ? true : false;
            $data[$i]['account_active'] = $data[$i]['account_active'] == 1 ? true : false;
            $data[$i]['dept_id'] = intval($data[$i]['dept_id']);
            $data[$i]['post_id'] = intval($data[$i]['post_id']);
            $data[$i]['manager_id'] = intval($data[$i]['manager_id']);
            $data[$i]['dept_manager_id'] = intval($data[$i]['dept_manager_id']);
            $data[$i]['area_id'] = intval($data[$i]['area_id']);
            $data[$i]['professional_qualifications_id'] = intval($data[$i]['professional_qualifications_id']);
        }
        //echo '<pre>';print_r($data);exit;
        if($condition['type'] == 'csv'){
            $data_csv = array();
            
            $title = array(
                    'cnt'                           => '#',
                    'active'                        => '在职',
                    'account'                       => '系统账号',
                    'account_active'                => '账号状态',
                    'employee_type_name'            => '用工形式',
                    'work_place'                    => '工作地点',
                    'number'                        => '工号',
                    'cname'                         => '中文名',
                    'ename'                         => '英文名',
                    'sex'                           => '性别',
                    'birthday'                      => '出生日期',
                    'dept_name'                     => '部门',
                    'dept_info'                     => '部门信息',
                    'manager_name'                  => '上级主管',
                    'dept_manager_name'             => '部门主管',
                    'post_name'                     => '职位',
                    'email'                         => '邮箱',
                    'tel'                           => '电话',
                    'msn'                           => 'MSN',
                    'official_qq'                   => '企业QQ',
                    'short_num'                     => '短号',
                    'ext'                           => '分机号',
                    'address'                       => '家庭住址',
                    'area'                          => '地区',
                    'offical_address'               => '户籍地址',
                    'other_contact'                 => '紧急联系人',
                    'other_relationship'            => '双方关系',
                    'other_contact_way'             => '联系方式',
                    'work_years'                    => '工作年限',
                    'politics_status'               => '政治面貌',
                    'employment_type'               => '用工形式',
                    'dept_manager_name'             => '部门主管',
                    'manager_name'                  => '上级主管',
                    'marital_status'                => '婚否',
                    'marry_day'                     => '结婚纪念日',
                    'children_birthday'             => '小孩生日',
                    'id_card'                       => '身份证号',
                    'insurcode'                     => '社保号',
                    'salary'                        => '薪水',
                    'professional_qualifications'   => '技术职称等级',
                    'driving_license'               => '驾照',
                    'bank'                          => '开户行',
                    'bank_num'                      => '银行卡号',
                    'accumulation_fund_code'        => '公积金号',
                    'education'                     => '学历',
                    'school'                        => '毕业院校',
                    'major'                         => '专业',
                    'entry_date'                    => '入职日期',
                    'regularization_date'           => '转正日期',
                    'labor_contract_start'          => '劳动合同-开始',
                    'labor_contract_end'            => '劳动合同-截止',
                    'leave_date'                    => '离职日期',
                    'remark'                        => '备注',
                    'create_user'                   => '创建人',
                    'create_time'                   => '创建时间',
                    'update_user'                   => '更新人',
                    'update_time'                   => '更新时间'
            );
            
            array_push($data_csv, $title);
            
            $i = 0;
            
            foreach ($data as $d){
                $i++;
                
                $info = array(
                        'cnt'                           => $i,
                        'active'                        => $d['active'] == 1 ? '是' : '否',
                        'account'                       => $d['account'] == 1 ? '有' : '无',
                        'account_active'                => $d['account_active'] == 1 ? '是' : '否',
                        'employee_type_name'            => $d['employee_type_name'],
                        'work_place'                    => $d['work_place'],
                        'number'                        => $d['number'],
                        'cname'                         => $d['cname'],
                        'ename'                         => $d['ename'],
                        'sex'                           => $d['sex'],
                        'birthday'                      => $d['birthday'],
                        'dept_name'                     => $d['dept_name'],
                        'dept_info'                     => $d['dept_info'],
                        'manager_name'                  => $d['manager_name'],
                        'dept_manager_name'             => $d['dept_manager_name'],
                        'post_name'                     => $d['post_name'],
                        'email'                         => $d['email'],
                        'tel'                           => $d['tel'],
                        'msn'                           => $d['msn'],
                        'official_qq'                   => $d['official_qq'],
                        'short_num'                     => $d['short_num'],
                        'ext'                           => $d['ext'],
                        'address'                       => $d['address'],
                        'area'                          => $d['area'],
                        'offical_address'               => $d['offical_address'],
                        'other_contact'                 => $d['other_contact'],
                        'other_relationship'            => $d['other_relationship'],
                        'other_contact_way'             => $d['other_contact_way'],
                        'work_years'                    => $d['work_years'],
                        'politics_status'               => $d['politics_status'],
                        'employment_type'               => $d['employment_type'],
                        'dept_manager_name'             => $d['dept_manager_name'],
                        'manager_name'                  => $d['manager_name'],
                        'marital_status'                => $d['marital_status'] == 1 ? '是' : '否',
                        'marry_day'                     => $d['marry_day'],
                        'children_birthday'             => $d['children_birthday'],
                        'id_card'                       => $d['id_card'],
                        'insurcode'                     => $d['insurcode'],
                        'salary'                        => $d['salary'],
                        'professional_qualifications'   => $d['professional_qualifications'],
                        'driving_license'               => $d['driving_license'] == 1 ? '有' : '无',
                        'bank'                          => $d['bank'],
                        'bank_num'                      => $d['bank_num'],
                        'accumulation_fund_code'        => $d['accumulation_fund_code'],
                        'education'                     => $d['education'],
                        'school'                        => $d['school'],
                        'major'                         => $d['major'],
                        'entry_date'                    => $d['entry_date'],
                        'regularization_date'           => $d['regularization_date'],
                        'labor_contract_start'          => $d['labor_contract_start'],
                        'labor_contract_end'            => $d['labor_contract_end'],
                        'leave_date'                    => $d['leave_date'],
                        'remark'                        => $d['remark'],
                        'create_user'                   => $d['creater'],
                        'create_time'                   => date('Y-m-d H:i:s', $d['create_time']),
                        'update_user'                   => $d['updater'],
                        'update_time'                   => date('Y-m-d H:i:s', $d['update_time'])
                );
                
                array_push($data_csv, $info);
            }
            
            return $data_csv;
        }

        return array('total' => $total, 'rows' => $data);
    }

    /**
     * 根据员工id获取员工信息，在一行返回
     */
    public function getInfosByOneLine($ids) {
        $completionNo = explode(",", $ids);
        $sql = $this->select()
                ->from($this, array("cname" => "group_concat(cname)", "ename" => "group_concat(ename)", "email" => "group_concat(email)"))
                ->where("active = 1 and id in(?) ", $completionNo)
                ->order(array('number'));

        $data = $this->fetchRow($sql);
        return $data;
    }
}