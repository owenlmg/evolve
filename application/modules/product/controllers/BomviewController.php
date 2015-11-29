<?php
/**
 * 2013-9-8
 * @author      mg.luo
 * @abstract    物料类别管理
 */
class Product_BomviewController extends Zend_Controller_Action
{

    public function indexAction()
    {

    }

    public function getlistAction() {
        $request = $this->getRequest()->getParams();
        if(!isset($request['recordkey']) || !$request['recordkey']) {
        	exit;
        }
        $recordkey = $request['recordkey'];
        $fa = new Product_Model_Fa();
        $son = new Product_Model_Son();
        $db = $fa->getAdapter();

        $data = $fa->getOne($recordkey);
        $bom = array();
        for($i = 0; $i < count($data); $i++) {
        	if($i == 0){
        		$row = $data[$i];
	            $bom = array(
	                    'sid'            => $row['sid'],
	                    'nid'            => $row['nid'],
	                    'recordkey'      => $row['recordkey'],
	                    'id'             => $row['id'],
	                    'name'          => $row['name'],
	                    'description'   => $row['description'],
	                    'remark'        => $row['remark'],
	                    'project_no_name' => $row['project_no_name'],
	                    'bom_file' => $row['bom_file'],
	                    'code'        => $row['code'],
	                    'qty'        => $row['qty'],
	                    'ver'        => $row['ver'],
	                    'partposition'        => "",
	                    'replace'        => "",
	                    'state'         => $row['state'],
	                    'count'          => 1,
	                    'leaf'          => false,
	                    'children'      => $this->getData($fa, $son, $recordkey, 2)
	            );
	        }
        }
        $result = array(
                'sid'            => '',
                'nid'            => '',
                'recordkey'      => '',
                'id'             => '',
                'name'          => '',
                'description'   => '',
                'remark'        => '',
                'code'        => '',
                'qty'        => '',
                'partposition'        => '',
                'replace'        => '',
                'state'         => '',
                'leaf'          => false,
                'children'      => $bom
        );
        // 将类别数据转为json格式并输出
        $this->view->recordkey = $row['recordkey'];
        $this->view->code = $row['code'];
        $this->view->ver = $row['ver'];
        echo Zend_Json::encode($result);

        exit;
    }

    /**
     * @abstract    获取类别树数据
     * @param       number  $parentId  上级ID
     * @param       boolen  $root       是否为最上级
     * @return      array   $dept
     */
    private function getData($fa, $son, $recordkey, $count)
    {
        $data = $son->getSon($recordkey);

        for($i = 0; $i < count($data); $i++){
            $fadata = $fa->getFa($data[$i]['code'], null);
            $faRow = "";
            if($fadata && count($fadata) > 0) {
        	    $faRow = $fadata[0];
            }
            if($faRow){
                $data[$i]['ver'] = $faRow['ver'];
                $data[$i]['leaf'] = false;
                $data[$i]['state'] = $faRow['state'];
                $data[$i]['count'] = $count;
                $data[$i]['children'] = $this->getData($fa, $son, $faRow['recordkey'], $count++);
            }else{
                $data[$i]['leaf'] = true;
                $data[$i]['count'] = 0;
                $data[$i]['state'] = $data[$i]['mstate'];
            }
        }

        return $data;
    }

    public function gettypetreeAction() {
        $data = array();

        // 请求参数
        $request = $this->getRequest()->getParams();

        $node = isset($request['node']) ? $request['node'] : 0;
        $node = $node == 'root' ? 0 : $node;

        $type = new Product_Model_Type();

        $data = $type->getTree($node);

        // 将模块数据转为json格式并输出
        echo "[".Zend_Json::encode($data)."]";

        exit;
    }

}

