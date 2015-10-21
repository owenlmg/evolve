<?php
/**
 * 2014-4-5
 * @author mg.luo
 * @abstract
 */
class Product_Model_Bomrole extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'product_bom_role';
    protected $_primary = 'id';

    public function selectByUser($where, $start, $limit) {
        if(!$where) {
            $where = "1=1";
        }
        $sql = "select employee_id as userId, employee_name as userName, relation, GROUP_CONCAT(bom) as bom from oa_product_bom_role t1 where $where GROUP BY employee_id order by employee_id";
        if(isset($start) && isset($limit)) {
            $sql .= " limit $start, $limit";
        }
        $sqlSet = "SET SESSION group_concat_max_len = 1000000";
        $this->getAdapter()->query($sqlSet);
        return $this->getAdapter()->query($sql)->fetchAll();
    }

    public function countByUser($where) {
        if(!$where) {
            $where = "1=1";
        }
        $sql = "select sum(a.sum) as count from (select 1 as sum from oa_product_bom_role t1 where $where GROUP BY employee_id) a";
        $data = $this->getAdapter()->query($sql)->fetchColumn();
        return $data;
    }

    public function selectByBom($where, $start, $limit) {
        if(!$where) {
            $where = "1=1";
        }
        $sql = "select t1.bom, relation, GROUP_CONCAT(t1.employee_id) as userId, GROUP_CONCAT(t1.employee_name) as userName from oa_product_bom_role t1 where $where GROUP BY t1.bom order by t1.bom";
        if(isset($start) && isset($limit)) {
            $sql .= " limit $start, $limit";
        }
        return $this->getAdapter()->query($sql)->fetchAll();
    }

    public function countByBom($where) {
        if(!$where) {
            $where = "1=1";
        }
        $sql = "select sum(a.sum) as count from (select 1 as sum from oa_product_bom_role t1 where $where GROUP BY t1.bom) a";
        $data = $this->getAdapter()->query($sql)->fetchColumn();
        return $data;
    }

    public function saveData($bom, $user, $relation) {
        $employee = new Hra_Model_Employee();
        $userData = $employee->getById($user);
        if($userData && count($userData) > 0) {
            $userName = $userData['cname'];
            $data = array(
                'bom' => $bom,
                'employee_id' => $user,
                'employee_name' => $userName,
                'relation' => $relation,
                'create_time' => date('Y-m-d H:i:s')
            );
            $this->insert($data);
        }
    }

    public function remove($boms, $userIds) {
        $bomArr = explode(',', $boms);
        $userIdArr = explode(',', $userIds);
        $where = "(1!=1 ";
        foreach($bomArr as $bom) {
            $where .= " or bom = '$bom'";
        }
        $where .= ") and (1!=1 ";
        foreach($userIdArr as $userId) {
            $where .= " or employee_id = '$userId'";
        }
        $where .= ")";

        $this->delete($where);
    }
}