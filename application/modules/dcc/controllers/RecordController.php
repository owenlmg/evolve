<?php
/**
 * 2013-8-7
 * @author      mg.luo
 * @abstract    处理记录
 */
class Dcc_RecordController extends Zend_Controller_Action
{
    public function indexAction()
    {

    }

    /**
     * @abstract    获取记录
     * @return      null
     */
    public function getrecordAction()
    {
    	$model = new Dcc_Model_Record();

    	$request = $this->getRequest()->getParams();
    	$table = $request['table'];
    	$id = $request['id'];

    	$data = $model->getList("table_name = '$table' and table_id = '$id'");
    	for($i = 0; $i < count($data); $i++){
            $data[$i]['handle_time'] = strtotime($data[$i]['handle_time']);
    	}

        echo Zend_Json::encode($data);

        exit;
    }
}