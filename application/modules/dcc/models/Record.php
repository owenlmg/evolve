<?php
/**
 * 2013-7-30 下午16:37:30
 * @author mg.luo
 * @abstract
 */
class Dcc_Model_Record extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'record';
    protected $_primary = 'id';

    public function getList($where){
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'employee'), "t1.handle_user = t2.id", array('handle_user' => 'cname'))
                    ->where($where)
                    ->order(array('handle_time'));
        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }

    public function getHis($id, $type, $table_name='') {
    	$where = "type='$type' and table_id = ".$id;
    	if(isset($table_name) && $table_name) {
    	    $where .= " and table_name = '$table_name'";
    	}
    	$data = $this->getList($where);
    	$result = "";
    	foreach($data as $row) {
    		if($result) $result .= ",";
    		$result .= $row['handle_user']."--".$row['handle_time']."--".$row['action']."--".$row['result']."--".$row['remark'];
    	}
    	return $result;
    }

    public function getEmployeeIds($table_id, $type){
        $sql = $this->select()
                    ->from(array('t1' => $this->_name))
                    ->where("t1.table_id = $table_id and t1.type = '$type'");
        $data = $this->fetchAll($sql)->toArray();
        $ids = array();
        foreach($data as $row) {
        	if(!in_array($row['handle_user'], $ids)) {
        		$ids[] = $row['handle_user'];
        	}
        }

        return implode(',', $ids);
    }
}