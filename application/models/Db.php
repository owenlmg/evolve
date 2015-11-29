<?php
/**
 * 2013-6-26 下午1:41:44
 * @author      x.li
 * @abstract    DB类（继承框架类并指定表前缀）
 */
class Application_Model_Db extends Zend_Db_Table
{
    public $_dbprefix;
    protected $_name;
    
    public function __construct()
    {
        $dbconfig = new Zend_Config_Ini(CONFIGS_PATH."/application.ini", 'production');
        Zend_Registry::set('dbprefix', $dbconfig->resources->db->params->prefix);
        $this->_dbprefix = $dbconfig->resources->db->params->prefix;
        $dbprefix=Zend_Registry::get('dbprefix');
        $this->_name=$dbprefix.$this->_name;
        parent::__construct();
    }

    /**
     * 多表查询
     *
     * @param array $where
     *            查询条件
     * @example 查询条件例 1.数组
     *          array (
     *         
     *          'id' => array (
     *          '=>' => '11'
     *          '<>' => '12'
     *          ),
     *         
     *          'cdate' => array (
     *          '=' => '12342189'
     *          )
     *          )
     * @example 查询条件例 2.字符串
     *          'id >= 11 AND cdate = 12342189'
     *         
     * @param array $join
     *            连接的表
     *            例：array(
     *	    	         'type' => INNERJOIN,
     *                   'table' => table1,
     *	                 'condition' => 'table1.column1 = 'table.column',
     *	                 'cols' => array('column1')
     *	             )
     *            
     * @param array $columns
     *            要查找的列
     *            例：array('id', 'name')
     * @param array $order
     *            排序条件
     * @param int $start
     *            开始条数
     * @param int $limit
     *            返回数据的条数
     * @param array $group
     *            分组
     * @return array 二维数组
     */
    public function getJoinList ($where = array(), $join = array(), $columns = null, $order = array(), $start = null, 
            $limit = null, $group = array())
    {
        try {
            $select = $this->select();
            // 查找列
            if (isset($columns)) {
                $select->from($this, $columns);
            } else {
                $select->from($this);
            }
            // 查找条件
            if (is_array($where) && count($where) > 0) {
                foreach ($where as $column => $ops) {
                    foreach ($ops as $opt => $opd) {
                        $select->where($column . $opt . '?', $opd);
                    }
                }
            } else if(!is_array($where)) {
                $select->where($where);
            }
            // 连接
            if(is_array($join)) {
                $first = true;
                foreach($join as $j) {
                    if(is_array($j) && isset($j['type']) && isset($j['table']) && isset($j['condition'])) {
                        if($first) {
                            $select->setIntegrityCheck(false);
                            $first = false;
                        }
                        $type = $j['type'];
                        $tablename = $j['table'];
                        $condition = $j['condition'];
                        if(isset($j['cols'])) {
                            $cols = $j['cols'];
                        } else {
                            $cols = array();
                        }
                        
                        switch($type) {
                        	case LEFTJONIN:
                        	    $select->joinLeft($tablename, $condition, $cols);
                        	    break;
                        	case RIGJTJOIN:
                        	    $select->joinRight($tablename, $condition, $cols);
                        	    break;
                        	case CROSSJOIN:
                        	    $select->joinCross($tablename, $condition, $cols);
                        	    break;
                        	case FULLJOIN:
                        	    $select->joinFull($tablename, $condition, $cols);
                        	    break;
                        	case NATUREJOIN:
                        	    $select->joinNatural($tablename, $condition, $cols);
                        	    break;
                        	default:
                        	    $select->join($tablename, $condition, $cols);
                        	    break;
                        }
                    }
                }
            }
            // 排序
            if (is_array($order) && count($order) > 0) {
                $select->order($order);
            }
            // Limit
            if (isset($start) && isset($limit)) {
                $select->limit($limit, $start);
            }
            // 分组
            if(isset($group)) {
                $select->group($group);
            }
            return $this->fetchAll($select)->toArray();
        } catch (Zend_Exception $e) {
            return array();
        }
    }
    
    /**
     * 条数查询
     *
     * @param array $where
     *            查询条件
     * @example 查询条件例 1.数组
     *          array (
     *         
     *          'id' => array (
     *          '=>' => '11'
     *          '<>' => '12'
     *          ),
     *         
     *          'cdate' => array (
     *          '=' => '12342189'
     *          )
     *          )
     * @example 查询条件例 2.字符串
     *          'id >= 11 AND cdate = 12342189'
     *         
     * @param array $join
     *            连接的表
     *            例：array(
     *	    	         'type' => INNERJOIN,
     *                   'table' => table1,
     *	                 'condition' => 'table1.column1 = 'table.column'
     *	             )
     * @return 异常时返回false，正常时返回条数
     */
    public function getJoinCount($where = array(), $join = array(), $column = 'count(*)') {
        try {
            $select = $this->select();
            // 查找列
            $select->from($this, array('sum' => $column));
            // 查找条件
            if (is_array($where) && count($where) > 0) {
                foreach ($where as $column => $ops) {
                    foreach ($ops as $opt => $opd) {
                        $select->where($column . $opt . '?', $opd);
                    }
                }
            } else if(!is_array($where)) {
                $select->where($where);
            }
            // 连接
            if(is_array($join)) {
                $first = true;
                foreach($join as $j) {
                    if(is_array($j) && isset($j['type']) && isset($j['table']) && isset($j['condition'])) {
                        if($first) {
                            $select->setIntegrityCheck(false);
                            $first = false;
                        }
                        $type = $j['type'];
                        $tablename = $j['table'];
                        $condition = $j['condition'];
                        
                        switch($type) {
                        	case LEFTJONIN:
                        	    $select->joinLeft($tablename, $condition);
                        	    break;
                        	case RIGJTJOIN:
                        	    $select->joinRight($tablename, $condition);
                        	    break;
                        	case CROSSJOIN:
                        	    $select->joinCross($tablename, $condition);
                        	    break;
                        	case FULLJOIN:
                        	    $select->joinFull($tablename, $condition);
                        	    break;
                        	case NATUREJOIN:
                        	    $select->joinNatural($tablename, $condition);
                        	    break;
                        	default:
                        	    $select->join($tablename, $condition);
                        	    break;
                        }
                    }
                }
            }
            $data = $this->getAdapter()->query($select)->fetchAll();
            $sum = 0;
            if(count($data) > 0) {
                $sum = $data[0]['sum'];
            }
            return $sum;
        } catch (Zend_Exception $e) {
            return false;
        }
    }

    public function getById($id) {
        if(!$id) {
            return array();
        }
        $where = $this->getAdapter()->quoteInto($this->getPrimary().'=?', $id);
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array($this->_name))
            ->where($where);
        $data = $this->fetchAll($select)->toArray();
        if($data && count($data) > 0) {
            return $data[0];
        }
        return array();
    }

    public function getPrimary() {
        if(is_array($this->_primary)) {
            reset($this->_primary);
            return current($this->_primary);
        }
        return $this->_primary;
    }
    
    /**
     * 获取表名
     * @return string
     */
    public function getName() {
        return $this->_name;
    }
}