<?php
/**
 * 2013-7-6 下午10:32:26
 * @author x.li
 * @abstract 
 */
class Hra_Model_News extends Application_Model_Db
{
    /**
     * 表名、主键
     */
    protected $_name = 'news';
    protected $_primary = 'id';
    
    /**
     * 获取标题列表
     * @param number $type
     * @param number $count
     * @return Ambigous <boolean, multitype:>
     */
    public function getTitleList($public = 0, $type = 0, $page, $limit)
    {
        $sql = $this->select()
                    ->from($this, array('id', 'title', 'create_time'))
                    ->order(array('create_time desc'))
                    ->where("deleted = 0 and active = 1");
        
        if($public == 1){
            $sql = $sql->where("public = ".$public);
        }
        
        if($type > 0){
            $sql = $sql->where("type_id = '".$type."'");
        }
        
        $total = $this->fetchAll($sql)->count();
        
        $sql = $sql->limitPage($page, $limit);
        
        $data = $this->fetchAll($sql)->toArray();
        
        for($i = 0; $i < count($data); $i++){
            $now = date('Y-m-d H:i:s');
            
            $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
            
            if(((time() - $data[$i]['create_time']) / 86400) < 30){
                $data[$i]['new'] = true;
            }else{
                $data[$i]['new'] = false;
            }
        }
        
        return array('total' => $total, 'rows' => $data);
    }

    /**
     * 获取文章及评论
     * @param unknown $condition
     * @return Ambigous <boolean, multitype:>
     */
    public function getData($condition = array())
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
        
        $where_date_from = $condition['date_from'] != '' ? " and t1.create_time >= '".$condition['date_from']." 00:00:00'" : "";
        $where_date_to = $condition['date_to'] != '' ? " and t1.create_time <= '".$condition['date_to']." 23:59:59'" : "";
        
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('t1' => $this->_name))
                    ->joinLeft(array('t2' => $this->_dbprefix.'user'), "t1.create_user = t2.id", array())
                    ->joinLeft(array('t3' => $this->_dbprefix.'employee'), "t2.employee_id = t3.id", array('creater' => 'cname'))
                    ->joinLeft(array('t4' => $this->_dbprefix.'user'), "t1.update_user = t4.id", array())
                    ->joinLeft(array('t5' => $this->_dbprefix.'employee'), "t4.employee_id = t5.id", array('updater' => 'cname'))
                    ->joinLeft(array('t6' => new Zend_Db_Expr("(select news_id, count(*) as cnt from ".$this->_dbprefix."news_comment group by news_id)")), "t1.id = t6.news_id", array('comment_cnt' => new Zend_Db_Expr ('case when cnt > 0 then cnt else 0 end')))
                    ->joinLeft(array('t7' => $this->_dbprefix.'news_type'), "t7.id = t1.type_id", array('type_name' => 'name'))
                    ->where("(t1.title like '%".$condition['key']."%' or t1.subhead like '%".$condition['key']."%' or t1.content like '%".$condition['key']."%')".$where_type.$where_date_from.$where_date_to)
                    ->order(array('t1.create_time desc'));
        $data = $this->fetchAll($sql)->toArray();

        for($i = 0; $i < count($data); $i++){
            $data[$i]['create_time'] = strtotime($data[$i]['create_time']);
            $data[$i]['update_time'] = strtotime($data[$i]['update_time']);
            $data[$i]['active'] = $data[$i]['active'] == 1 ? true : false;
            $data[$i]['public'] = $data[$i]['public'] == 1 ? true : false;
            $data[$i]['deleted'] = $data[$i]['deleted'] == 1 ? true : false;
        }
        
        if($condition['type'] == 'csv'){
            $data_csv = array();
        
            $title = array(
                    'cnt'           => '#',
                    'type_name'     => '类别',
                    'deleted'       => '删除',
                    'active'        => '激活',
                    'public'        => '公开',
                    'title'         => '标题',
                    'subhead'       => '副标题',
                    'summary'       => '摘要',
                    'comment_cnt'   => '评论数',
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
                        'type_name'     => $d['type_name'],
                        'deleted'       => $d['deleted'],
                        'active'        => $d['active'],
                        'public'        => $d['public'],
                        'title'         => $d['title'],
                        'subhead'       => $d['subhead'],
                        'summary'       => $d['summary'],
                        'comment_cnt'   => $d['comment_cnt'],
                        'create_user'   => $d['creater'],
                        'create_time'   => date('Y-m-d H:i:s', $d['create_time']),
                        'update_user'   => $d['updater'],
                        'update_time'   => date('Y-m-d H:i:s', $d['update_time'])
                );
        
                array_push($data_csv, $info);
            }
        
            return $data_csv;
        }

        return $data;
    }
}