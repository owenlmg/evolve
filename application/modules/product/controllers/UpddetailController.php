<?php
/**
 * 2013-9-8
 * @author      mg.luo
 * @abstract    物料类别管理
 */
class Product_UpddetailController extends Zend_Controller_Action
{

    public function indexAction()
    {

    }

    public function getlistAction() {
        $request = $this->getRequest()->getParams();
        $fa = new Product_Model_Fa();
        $fadev = new Product_Model_Fadev();
        $db = $fa->getAdapter();
        if(!isset($request['left_code']) || !isset($request['left_ver']) 
            || !isset($request['right_code']) || !isset($request['right_ver'])
            || !$request['left_code'] || !$request['left_ver']
            || !$request['right_code'] || !$request['right_ver']) {
            echo Zend_Json::encode("");
            exit;
        }
        $left_code = $request['left_code'];
        $left_ver = $request['left_ver'];
        $right_code = $request['right_code'];
        $right_ver = $request['right_ver'];
        
        $whereleft = "code='$left_code' and ver='$left_ver'";
        $whereright = "code='$right_code' and ver='$right_ver'";
        $left_recordkey = "";
        $right_recordkey = "";
        $left_type = "";
        $right_type = "";
        if($fa->getJoinCount($whereleft) || $fadev->getJoinCount($whereleft)) {
            $left_fa = $fa->getJoinList($whereleft, array(), array('recordkey'));
            if($left_fa && count($left_fa) > 0) {
                $left_recordkey = $left_fa[0]['recordkey'];
            } else {
                $left_fa = $fadev->getJoinList($whereleft, array(), array('recordkey'));
                if($left_fa && count($left_fa) > 0) {
                    $left_recordkey = $left_fa[count($left_fa)-1]['recordkey'];
                    $left_type = "dev";
                }
            }
        }
        if($fa->getJoinCount($whereright) || $fadev->getJoinCount($whereright)) {
            $right_fa = $fa->getJoinList($whereright, array(), array('recordkey'));
            if($right_fa && count($right_fa) > 0) {
                $right_recordkey = $right_fa[0]['recordkey'];
            } else {
                $right_fa = $fadev->getJoinList($whereright, array(), array('recordkey'));
                if($right_fa && count($right_fa) > 0) {
                    $right_recordkey = $right_fa[count($right_fa)-1]['recordkey'];
                    $right_type = "dev";
                }
            }
        }
        if($left_recordkey && $right_recordkey) {
            if($left_type == 'dev') {
                $son_left = new Product_Model_Sondev();
            } else {
                $son_left = new Product_Model_Son();
            }
            
            if($right_type == 'dev') {
                $son_right = new Product_Model_Sondev();
            } else {
                $son_right = new Product_Model_Son();
            }
            
            $leftData = $son_left->getSon($left_recordkey);
            $rightData = $son_right->getSon($right_recordkey);
            if($leftData && $rightData) {
                $leftCode = array();
                $rightCode = array();
                foreach($rightData as $d) {
                    $rightCode[] = $d['code'];
                }
                foreach($leftData as $d) {
                    $leftCode[] = $d['code'];
                }
                $result = array();
                for($i = 0; $i < count($rightData); $i++) {
                    $data = array();
            
                    $row = $rightData[$i];
                    $data['code']          = $row['code'];
                    $data['code2']         = $row['code'];
                    $data['name']          = $row['name'];
                    $data['description']   = $row['description'];
                    $data['qty2']          = $row['qty'];
                    $data['partposition2'] = $row['partposition'];
                    $data['replace2']      = $row['replace'];
            
                    $left = $this->getRowFromArrayByCode($leftData, $row['code']);
                    $leftData = $this->removeFromArray($leftData, $row['code']);
            
                    $data['code1']         = $left['code'];
                    $data['qty1']          = $left['qty'];
                    $data['partposition1'] = $left['partposition'];
                    $data['replace1']      = $left['replace'];
            
                    $result[] = $data;
                }
                if(count($leftData) > 0) {
                    foreach($leftData as $row) {
                        $data = array();
            
                        $data['code']          = $row['code'];
                        $data['code1']         = $row['code'];
                        $data['name']          = $row['name'];
                        $data['description']   = $row['description'];
                        $data['qty1']          = $row['qty'];
                        $data['partposition1'] = $row['partposition'];
                        $data['replace1']      = $row['replace'];
                        $data['code2']         = "";
                        $data['qty2']          = "";
                        $data['partposition2'] = "";
                        $data['replace2']      = "";
            
                        $result[] = $data;
                    }
                }
                // 将类别数据转为json格式并输出
                echo Zend_Json::encode($result);
                exit;
            } else {
                echo Zend_Json::encode($leftData);
                exit;
            }
        }
        $msg = "BOM不存在";
        if(!$left_recordkey) {
            $msg = $left_code." V".$left_ver."不存在";
        } else if(!$right_recordkey){
            $msg = $right_code." V".$right_ver."不存在";
            
        }
        echo Zend_Json::encode(array("msg" => $msg));
        exit;
    }

    public function getcodeverAction() {
        $request = $this->getRequest()->getParams();
        $fa = new Product_Model_Fadev();
        $son = new Product_Model_Son();
        $db = $fa->getAdapter();
        $code = $request['code'];
        $result = array("");
        if($code) {
            $where = "code = '$code'";
            $data = $fa->getJoinList($where, array(), array('id' => 'ver', 'text' => 'ver'), array('ver desc'));
            if($data && count($data) > 0) {
                $result = $data;
            }
        }
        // 将类别数据转为json格式并输出
        echo Zend_Json::encode($result);

        exit;
    }

    private function getRowFromArrayByCode($array, $code) {
    	foreach($array as $row) {
    		if($row['code'] == $code) {
    			return $row;
    		}
    	}
    	return array("qty" => "",
    	             "code" => "",
    	             "partposition" => "",
    	             "replace" => ""
    	            );
    }

    private function removeFromArray($array, $code) {
    	$result = array();
    	foreach($array as $row) {
    		if($code != $row['code']) {
    			$result[] = $row;
    		}
    	}
    	return $result;
    }


}

