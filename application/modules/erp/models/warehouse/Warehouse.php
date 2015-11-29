<?php
/**
 * 2013-7-6 下午10:32:26
 * @author x.li
 * @abstract 
 */
class Erp_Model_Warehouse_Warehouse extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'erp_warehouse';
    protected $_primary = 'id';
    
    public function getInfoByCode($code)
    {
    	$info = array();
    	
    	$res = $this->fetchAll("code = '".$code."'");
    	
    	if($res->count() > 0){
    		$data = $res->toArray();
    		
    		$info = $data[0];
    	}
    	
    	return $info;
    }
    
    // 获取仓库列表
    public function getList($type = null, $active = 1, $locked = 0)
    {
        $sql = $this->select()
                    ->from($this, array('id', 'locked', 'in_stock', 'name' => new Zend_Db_Expr("concat(code, ' ', name)"), 'code'))
                    ->order('code');
        if($type){
            $type_arr = json_decode($type);
            
            $typeCond = "";
            for($i = 0; $i < count($type_arr); $i++){
                if($i == 0){
                    $typeCond = "type_id = ".$type_arr[$i];
                }else{
                    $typeCond .= " or type_id = ".$type_arr[$i];
                }
            }
            
            if($typeCond != ''){
                $sql->where($typeCond);
            }
        }
        
        $data = $this->fetchAll($sql)->toArray();
        
        return $data;
    }

    // 获取仓库数据
    public function getData($condition)
    {
        $type = json_decode($condition['type_id']);
        
        $where_type = "";
        if(count($type) > 0){
            $where_type = " and (";
            
            for($i = 0; $i < count($type); $i++){
                if($i == 0){
                    $where_type .= "t1.type_id = ".$type[$i];
                }else{
                    $where_type .= " or t1.type_id = ".$type[$i];
                }
            }
            
            $where_type .= ")";
        }
        
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t2.id = t1.create_user", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t3.id = t2.employee_id", array('creater' => 'cname'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t4.id = t1.update_user", array())
                    ->joinLeft(array('t5' => $this->_dbprefix.'employee'), "t5.id = t4.employee_id", array('updater' => 'cname'))
                    ->joinLeft(array('t6' => $this->_dbprefix.'erp_warehouse_type'), "t6.id = t1.type_id", array('type_code' => 'code'))
                    ->where("(t1.code like '%".$condition['key']."%' or t1.name like '%".$condition['key']."%')".$where_type)
                    ->order("t1.code desc");
        
        $total = $this->fetchAll($sql)->count();
        
        if($condition['type'] == 'data'){
            $sql->limitPage($condition['page'], $condition['limit']);
        }
        
        $data = $this->fetchAll($sql)->toArray();
        
        for($i = 0; $i < count($data); $i++){
            $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
            $data[$i]['update_time'] = strtotime($data[$i]['update_time']);
            
            $data[$i]['active'] = $data[$i]['active'] == 1 ? true : false;
            $data[$i]['in_stock'] = $data[$i]['in_stock'] == 1 ? true : false;
            $data[$i]['locked'] = $data[$i]['locked'] == 1 ? true : false;
        }
        
        if($condition['type'] == 'csv'){
            $data_csv = array();
            
            $title = array(
                    'cnt'           => '#',
                    'active'        => '启用',
                    'in_stock'      => '库存',
                    'locked'        => '锁定',
                    'type_name'     => '类别',
                    'code'          => '仓库代码',
                    'name'          => '仓库名称',
                    'description'   => '描述',
                    'remark'        => '备注',
                    'create_user'   => '创建人',
                    'create_time'   => '创建时间',
                    'update_user'   => '更新人',
                    'update_time'   => '更新时间'
            );
            
            array_push($data_csv, $title);
            
            $i = 0;
            
            foreach ($data as $d){
                $i++;
                
                $info = array(
                        'cnt'           => $i,
                        'active'        => $d['active'] == 1 ? '是' : '否',
                        'in_stock'      => $d['in_stock'] == 1 ? '是' : '否',
                        'locked'        => $d['locked'] == 1 ? '是' : '否',
                        'type_name'     => $d['type_name'],
                        'code'          => $d['code'],
                        'name'          => $d['name'],
                        'description'   => $d['description'],
                        'remark'        => $d['remark'],
                        'create_user'   => $d['creater'],
                        'create_time'   => date('Y-m-d H:i:s', $d['create_time']),
                        'update_user'   => $d['updater'],
                        'update_time'   => date('Y-m-d H:i:s', $d['update_time'])
                );
                
                array_push($data_csv, $info);
            }
            
            return $data_csv;
        }

        return array('total' => $total, 'rows' => $data);
    }
}