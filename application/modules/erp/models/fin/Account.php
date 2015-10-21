<?php
/**
 * 2014-3-30 上午11:45:03
 * @author x.li
 * @abstract 
 */
class Erp_Model_Fin_Account extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'erp_fin_account';
    protected $_primary = 'id';
    
    /**
     * 获取科目列表
     * @return array $data
     */
    public function getList()
    {
        $data = array();
    
        $sql = $this->select()
                    ->from($this, array('id', 'name'))
                    ->order(array('CONVERT( name USING gbk )'));
    
        $data = $this->fetchAll($sql, 'name')->toArray();
    
        return $data;
    }
    
    /**
     * @abstract    递归删除科目树数据
     * @param       number  $id  科目ID
     * @return      null
     */
    public function deleteAccountTreeData($id){
        $this->delete("id = ".$id);
    
        $children = $this->fetchAll("parentId = ".$id);
    
        foreach ($children as $child){
            $this->deleteAccountTreeData($child['id']);
        }
    }
    
    /**
     * @abstract    获取科目树数据
     * @param       number  $parentid   上级科目ID
     * @param       boolen  $root       是否为最上级科目
     * @return      array   $account
     */
    public function getData($parentId = 0, $root = true)
    {
        $account = array();
        $data = array();
    
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t1.create_user = t2.id", array('creater_id' => 'id'))
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t2.employee_id = t3.id", array('creater' => 'cname'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t1.update_user = t4.id", array('updater_id' => 'id'))
                    ->joinLeft(array('t5' => $this->_dbprefix.'employee'), "t4.employee_id = t5.id", array('updater' => 'cname'))
                    ->where("parentId = ".$parentId)
                    ->order(array('t1.code'));
    
        $data = $this->fetchAll($sql)->toArray();
    
        // 判断是否包含子科目并格式化时间
        for($i = 0; $i < count($data); $i++){
            $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
            $data[$i]['update_time'] = strtotime($data[$i]['update_time']);
            $data[$i]['active'] = $data[$i]['active'] == 1 ? true : false;
    
            if($this->fetchAll("parentId = ".$data[$i]['id'])->count() > 0){
                $data[$i]['leaf'] = false;
                $data[$i]['children'] = $this->getData($data[$i]['id'], false);
            }else{
                $data[$i]['leaf'] = true;
            }
        }
    
        // 格式化根数据格式
        if($root){
            $account = array(
                    'id'            => null,
                    'parentId'      => null,
                    'code'          => '',
                    'name'          => '',
                    'description'   => '',
                    'remark'        => '',
                    'create_time'   => null,
                    'update_time'   => null,
                    'create_user'   => '',
                    'update_user'   => '',
                    'active'         => null,
                    'leaf'          => false,
                    'children'      => $data
            );
        }else{
            $account = $data;
        }
    
        return $account;
    }
}