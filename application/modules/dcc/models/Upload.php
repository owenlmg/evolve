<?php

/**
 * 2013-7-31
 * @author mg.luo
 * @abstract 
 */
class Dcc_Model_Upload extends Application_Model_Db {

    /**
     * 表名、主键
     */
    protected $_name = 'doc_upload';
    protected $_primary = 'id';

    public function getFilesList($where1, $where2) {
        $select1 = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t1' => $this->_name))
                ->joinLeft(array('t2' => $this->_dbprefix . 'employee'), "t1.create_user = t2.id", array('creater' => 'cname'))
                ->joinLeft(array('t3' => $this->_dbprefix . 'employee'), "t1.update_user = t3.id", array('updater' => 'cname'))
                ->joinLeft(array('t4' => $this->_dbprefix . 'doc_share'), "t1.id = t4.shared_id and t4.type = 'upload'", array('share_id' => 'share_user', 'share_dept', 'share_time_begin', 'share_time_end'))
                ->joinLeft(array('t5' => $this->_dbprefix . 'doc_files'), "FIND_IN_SET(t1.id, t5.file_ids) and t5.state='Reviewing'", array('files_id' => 'id'))
                ->joinLeft(array('t6' => $this->_dbprefix . 'codemaster'), "t1.category = t6.code and t6.type=4", array('category_name' => 'text'))
                ->joinLeft(array('t7' => $this->_dbprefix . 'product_materiel'), "t7.state='Reviewing' and (t1.id = t7.data_file_id or t1.id = t7.tsr_id or t1.id = t7.first_report_id)", array('materiel_id' => 'id'))
                ->where("t1.del=0")
                ->where($where1);

        $select2 = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t1' => $this->_name))
                ->joinLeft(array('t2' => $this->_dbprefix . 'employee'), "t1.create_user = t2.id", array('creater' => 'cname'))
                ->joinLeft(array('t3' => $this->_dbprefix . 'employee'), "t1.update_user = t3.id", array('updater' => 'cname'))
                ->join(array('t4' => $this->_dbprefix . 'doc_share'), "t1.id = t4.shared_id and t4.type = 'upload'", array('share_id' => 'share_user', 'share_dept', 'share_time_begin', 'share_time_end'))
                ->joinLeft(array('t5' => $this->_dbprefix . 'doc_files'), "FIND_IN_SET(t1.id, t5.file_ids) and t5.state='Reviewing'", array('files_id' => 'id'))
                ->joinLeft(array('t6' => $this->_dbprefix . 'codemaster'), "t1.category = t6.code and t6.type=4", array('category_name' => 'text'))
                ->joinLeft(array('t7' => $this->_dbprefix . 'product_materiel'), "t7.state='Reviewing' and (t1.id = t7.data_file_id or t1.id = t7.tsr_id or t1.id = t7.first_report_id)", array('materiel_id' => 'id'))
                ->where("t1.del=0")
                ->where($where2);

        $sql = $this->select()
                ->union(array('(' . $select1 . ')', '(' . $select2 . ')'))
                ->order('update_time desc');

        $data = $this->fetchAll($sql)->toArray();

        return $data;
    }

    /**
     * @abstract    获取部门树数据
     * @param       number  $parentId  上级部门ID
     * @return      array   $dept
     */
    public function getTree($parentId) {
        $dept = array();
        $data = array();
        $return = array();

        $dept = new Hra_Model_Dept();
        $parentId = str_replace("D", "", str_replace("E", "", $parentId));

        $sql = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t1' => $this->_dbprefix . 'employee_dept'), array("id", "parentid", "text" => "name"))
                ->where("t1.active = 1 and parentid = " . $parentId)
                ->order(array('t1.name'));

        $data = $this->fetchAll($sql)->toArray();

        // 判断是否包含子部门
        for ($i = 0; $i < count($data); $i++) {
            $data[$i]['checked'] = false;
            if ($dept->fetchAll("parentId = " . $data[$i]['id'])->count() > 0) {
                $data[$i]['leaf'] = false;
                $data[$i]['children'] = $this->getTree($data[$i]['id'], false);
            } else {
                $data[$i]['leaf'] = true;
            }
            $data[$i]['id'] = "D" . $data[$i]['id'];
        }

        // 格式化根数据格式
        if ($parentId == 0) {
            $dept = array(
                'id' => "D0",
                'parentId' => 0,
                'text' => '总公司',
                'leaf' => false,
                'checked' => false,
                'expanded' => true,
                'children' => $data
            );
        } else {
            $dept = $data;
        }

        return $dept;
    }

    /**
     * 在部门树种添加员工节点
     * @param $dept 部门ID
     * @return $dept 包含部门和员工的树
     */
    public function getUserTree($dept, $where) {
        $udata = array();
        if (strpos($dept['id'], "D") !== false) {
            $dept_id = str_replace("D", "", $dept['id']);
            if ($dept_id != '')
                $udata = $this->getUser($dept_id, $where);
        }

        if (count($udata) > 0) {
            // 添加到当前节点的children节点中
            $dept['leaf'] = false;
            if (isset($dept['children'])) {
                for ($i = 0; $i < count($dept['children']); $i++) {
                    $dept['children'][$i] = $this->getUserTree($dept['children'][$i], $where);
                }
                $dept['children'] = array_merge($dept['children'], $udata);
            } else {
                $dept['children'] = $udata;
            }
        } else {
            if (strpos($dept['id'], "D") !== false) {
                $dept['leaf'] = false;
                if (isset($dept['children'])) {
                    for ($i = 0; $i < count($dept['children']); $i++) {
                        $dept['children'][$i] = $this->getUserTree($dept['children'][$i], $where);
                    }
                }
            }
        }

        return $dept;
    }

    /**
     * 根据部门id获取用户信息
     */
    public function getUser($dept, $where) {
        $sql = $this->select()
                ->setIntegrityCheck(false)
                ->from(array($this->_dbprefix . 'employee'), array("id", "number", "text" => "cname"))
                ->where($where)
                ->where("active = 1 and dept_id = " . $dept." and id in (select employee_id from oa_user where active=1)")
                ->order(array('number'));

        $data = $this->fetchAll($sql)->toArray();

        for ($i = 0; $i < count($data); $i++) {
            $data[$i]['leaf'] = true;
            $data[$i]['checked'] = false;
            $data[$i]['id'] = "E" . $data[$i]['id'];
        }
        return $data;
    }

    /**
     * 根据部门id获取员工信息
     */
    public function getEmployee($dept) {
        $sql = $this->select()
                ->setIntegrityCheck(false)
                ->from(array($this->_dbprefix . 'employee'), array("id", "number", "text" => "cname"))
                ->where("active = 1 and dept_id = " . $dept)
                ->order(array('number'));

        $data = $this->fetchAll($sql)->toArray();

        for ($i = 0; $i < count($data); $i++) {
            $data[$i]['leaf'] = true;
            $data[$i]['checked'] = false;
            $data[$i]['id'] = "E" . $data[$i]['id'];
        }
        return $data;
    }

    /**
     * 根据部门id获取部门名称
     */
    public function getDeptNames($ids) {
        $completionNo = explode(",", $ids);
        $sql = $this->select()
                ->setIntegrityCheck(false)
                ->from(array($this->_dbprefix . 'employee_dept'), array("dept_name" => "group_concat(name)"))
                ->where("active = 1 and id in(?) ", $completionNo)
                ->order(array('name'));

        $data = $this->fetchRow($sql);
        return $data;
    }

    /**
     * 获取当个文件
     */
    public function getOne($id) {
        $sql = $this->select()
                ->from($this)
                ->where("id = ?", $id);
        $data = $this->fetchRow($sql);

        return $data;
    }
    
    public function getFileByIds($ids) {
        $completionNo = explode(",", $ids);
        $sql = $this->select()
                    ->from($this->_name, array("name" => "group_concat(name)", "path" => "group_concat(path)"))
                    ->where("id in(?) ", $completionNo)
                    ->order(array('name'));
        $data = $this->fetchRow($sql);

        return $data;
        
    }

}