<?php

/**
 * 2013-9-8
 * @author      mg.luo
 * @abstract    物料类别管理
 */
class Product_TypeController extends Zend_Controller_Action
{

    public function indexAction ()
    {}

    public function getlistAction ()
    {
        $request = $this->getRequest()->getParams();

        $type = new Product_Model_Type();
        // 请求类别的层级ID
        $parentId = isset($request['parentId']) ? $request['parentId'] : 0;
        // 获取类别数据
        $data = $type->getData($parentId);
        // 将类别数据转为json格式并输出
        echo Zend_Json::encode($data);

        exit();
    }

    public function gettypetreeAction ()
    {
        $data = array();

        // 请求参数
        $request = $this->getRequest()->getParams();

        $node = isset($request['node']) ? $request['node'] : 0;
        $node = $node == 'root' ? 0 : $node;

        $type = new Product_Model_Type();

        $data = $type->getTree($node);

        // 将模块数据转为json格式并输出
        echo "[" . Zend_Json::encode($data) . "]";

        exit();
    }

    public function getexampleAction ()
    {
        $result = array(
                'success' => true,
                'result' => true,
                'info' => ''
        );
        $p = $this->getRequest()->getParams();

        $type = new Product_Model_Type();
        if (isset($p['type']) && $p['type']) {
            $sid = $p['type'];
        }

        $example = $type->getInfo($sid, "example");
        $datafile_flg = $type->getInfo($sid, "datafile_flg");
        $tsr_flg = $type->getInfo($sid, "tsr_flg");
        $checkreport_flg = $type->getInfo($sid, "checkreport_flg");

        $result['info'] = array(
                "example" => $example,
                "datafile_flg" => $datafile_flg,
                "tsr_flg" => $tsr_flg,
                "checkreport_flg" => $checkreport_flg
        );
        // 转为json格式并输出
        echo Zend_Json::encode($result);

        exit();
    }

    /**
     *
     * @abstract 添加、删除、修改类别
     * @return null
     */
    public function editAction ()
    {
        // 返回值数组
        $result = array(
                'success' => true,
                'info' => '编辑成功'
        );

        $request = $this->getRequest()->getParams();

        $now = date('Y-m-d H:i:s');
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];

        $json = json_decode($request['json']);

        $updated = $json->updated;
        $inserted = $json->inserted;
        $deleted = $json->deleted;

        $type = new Product_Model_Type();

        if (count($updated) > 0) {
            foreach ($updated as $val) {
                // 检查code是否重复
                if ($type->fetchAll(
                        "id != " . $val->id . " and parent_id = '" .
                                 $val->parent_id . "' and code = '" . $val->code .
                                 "'")->count() > 0) {
                    $result['result'] = false;
                    $result['info'] = "代码“" . $val->code . "”已经存在";

                    echo Zend_Json::encode($result);

                    exit();
                }
                $data = array(
                        'code' => $val->code,
                        'parent_id' => $val->parent_id,
                        'name' => $val->name,
                        'description' => $val->description,
                        'remark' => $val->remark,
                        'active' => $val->active,
                        'sn_length' => $val->sn_length,
                        'auto' => $val->auto,
                        'bom' => $val->bom,
                        'new_flow_id' => $val->new_flow_id,
                        'upd_flow_id' => $val->upd_flow_id,
                        'del_flow_id' => $val->del_flow_id,
                        'example' => $val->example,
                        'datafile_flg' => $val->datafile_flg,
                        'tsr_flg' => $val->tsr_flg,
                        'checkreport_flg' => $val->checkreport_flg,
                        'update_time' => $now,
                        'update_user' => $user,
                        'hum_level' => $val->hum_level
                );

                $where = "id = " . $val->id;

                try {
                    $type->update($data, $where);
                } catch (Exception $e) {
                    $result['result'] = false;
                    $result['info'] = $e->getMessage();

                    echo Zend_Json::encode($result);

                    exit();
                }
            }
        }

        if (count($inserted) > 0) {
            foreach ($inserted as $val) {
                // 检查code是否重复
                if ($type->fetchAll(
                        " parent_id = '" . $val->parent_id . "' and code = '" .
                                 $val->code . "'")->count() > 0) {
                    $result['result'] = false;
                    $result['info'] = "代码“" . $val->code . "”已经存在";

                    echo Zend_Json::encode($result);

                    exit();
                }
                $data = array(
                        'code' => $val->code,
                        'parent_id' => $val->parent_id,
                        'name' => $val->name,
                        'description' => $val->description,
                        'remark' => $val->remark,
                        'active' => $val->active,
                        'sn_length' => $val->sn_length,
                        'auto' => $val->auto,
                        'bom' => $val->bom,
                        'new_flow_id' => $val->new_flow_id,
                        'upd_flow_id' => $val->upd_flow_id,
                        'del_flow_id' => $val->del_flow_id,
                        'example' => $val->example,
                        'datafile_flg' => $val->datafile_flg,
                        'tsr_flg' => $val->tsr_flg,
                        'checkreport_flg' => $val->checkreport_flg,
                        'create_time' => $now,
                        'create_user' => $user,
                        'update_time' => $now,
                        'update_user' => $user,
                        'hum_level' => $val->hum_level
                );

                try {
                    $type->insert($data);
                } catch (Exception $e) {
                    $result['result'] = false;
                    $result['info'] = $e->getMessage();

                    echo Zend_Json::encode($result);

                    exit();
                }
            }
        }

        if (count($deleted) > 0) {
            foreach ($deleted as $val) {
                $materiel = new Product_Model_Materiel();

                if ($materiel->fetchAll("type = " . $val->id)->count() == 0) {
                    try {
                        $type->deleteTreeData($val->id);
                    } catch (Exception $e) {
                        $result['result'] = false;
                        $result['info'] = $e->getMessage();

                        echo Zend_Json::encode($result);

                        exit();
                    }
                } else {
                    $result['result'] = false;
                    $result['info'] = '类别ID' . $val->id . '已使用，不能删除';

                    echo Zend_Json::encode($result);

                    exit();
                }
            }
        }

        echo Zend_Json::encode($result);

        exit();
    }

    public function exportcsvAction () {
        $request = $this->getRequest()->getParams();

        $type = new Product_Model_Type();
        // 请求类别的层级ID
        $parentId = isset($request['parentId']) ? $request['parentId'] : 0;
        // 获取类别数据
        $data = $type->getData($parentId);

        print(chr(0xEF) . chr(0xBB) . chr(0xBF));
        // 获取物料数据
        $data_csv = array();
        $title = array(
                'cnt' => '#',
                'code1' => '大类',
                'code2' => '小类',
                'name' => '名称',
                'active' => '启用状态',
                'auto' => '是否自动编码',
                'sn_length' => '流水号长度',
                'description' => '描述',
                'example' => '描述规则',
                'datafile_flg' => '数据手册',
                'tsr_flg' => 'TSR',
                'checkreport_flg' => '样品检验报告',
                'remark' => '备注'
        );

        $title = $this->object_array($title);
        $filename = "materiel_type";
        $path = "../temp/" . $filename . ".csv";
        $file = fopen($path, "w");
        fputcsv($file, $title);
        array_push($data_csv, $title);
        $result = array();
        $this->recursive($data['children'], $result, 0);
        foreach($result as $row) {
            $d = $this->object_array($row);
            fputcsv($file, $d);
        }

        fclose($file);

        echo $filename;

        exit();
    }

    /**
     * 递归把获取到的json数据转为数组
     * @param unknown $data
     * @param unknown $result
     * @param number $num
     */
    private function recursive($data, &$result, $num = 0) {
        foreach($data as $d) {
            $num++;
            $tmpArr = array(
            	'cnt' => $num,
                'code1' => isset($d['children']) && $d['children'] ? $d['code'] : '',
                'code2' => isset($d['children']) && $d['children'] ? '' : $d['code'],
                'name' => $d['name'],
                'active' => $d['active'] ? '启用' : '不启用',
                'auto' => $d['auto'] ? '是' : '否',
                'sn_length' => $d['sn_length'],
                'description' => $d['description'],
                'example' => $d['example'],
                'datafile_flg' => $d['datafile_flg'] == 1 ? '需要（不强制）' : ($d['datafile_flg'] == 2 ? '需要（强制）' : '不需要'),
                'tsr_flg' => $d['tsr_flg'] == 1 ? '需要（不强制）' : ($d['tsr_flg'] == 2 ? '需要（强制）' : '不需要'),
                'checkreport_flg' => $d['tsr_flg'] == 1 ? '需要（不强制）' : ($d['tsr_flg'] == 2 ? '需要（强制）' : '不需要'),
                'remark' => $d['remark']
            );
            $result[] = $tmpArr;
            if(isset($d['children']) && $d['children']) {
                $this->recursive($d['children'], $result, $num);
            }

        }
    }

    /**
     *
     * 把对象类型转换为数组类型，并转码
     * @param object $array
     * @return array $a
     */
    private function object_array($array) {
        $a = array();
        foreach ($array as $key => $v) {
            if(is_numeric($v)) {
                $v = '="'.$v.'"';
            }
            $a[$key] = iconv('utf-8', 'GBK//TRANSLIT', $v);
        }
        return $a;
    }
}

