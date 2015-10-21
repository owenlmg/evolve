<?php
/**
 * 2013-10-17 23:54:30
 * @author mg.luo
 * @abstract
 */
class Admin_BacklogController extends Zend_Controller_Action {

    public function indexAction() {

    }

    public function getlistAction() {
        $review = new Dcc_Model_Review();
        $user_session = new Zend_Session_Namespace('user');
        $user = $user_session->user_info['employee_id'];

        // 清理无效数据
        $where1 = " type='files' and file_id in (select id from oa_doc_files where state = 'Return' or state = 'Delete')";
        $review->delete($where1);

        $where2 = " type='materiel' and file_id in (select id from oa_product_materiel where state = 'Return' or state = 'Delete')";
        $review->delete($where2);

        $where3 = " type='bom' and file_id in (select id from oa_product_bom_new where state = 'Return' or state = 'Delete')";
        $review->delete($where3);

        $where4 = " type='devbom' and file_id in (select id from oa_product_bom_upd where state = 'Return' or state = 'Delete')";
        $review->delete($where4);

        $where5 = " type='ecobom' and file_id in (select id from oa_product_bom_upd where state = 'Return' or state = 'Delete')";
        $review->delete($where5);

        $where6 = " type='materiel_desc' and file_id in (select id from oa_product_materiel_desc where state = 'Return' or state = 'Delete')";
        $review->delete($where6);

        $data = $review->getBacklogList($user);
        $updcount = 0;
        for($i = 0; $i < count($data); $i++) {
            if($data[$i]['type'] == 'devbom') {
                $updcount += $data[$i]['count'];
            } else if($data[$i]['type'] == 'ecobom') {
                $updcount += $data[$i]['count'];
            } else if($data[$i]['type'] == 'updbom') {
                $updcount += $data[$i]['count'];
            }
        }
        $result = array();
        for($i = 0; $i < count($data); $i++) {
            if($data[$i]['type'] != 'devbom' && $data[$i]['type'] != 'ecobom' && $data[$i]['type'] != 'updbom') {
                $result[] = $data[$i];
            }
        }
        if($updcount > 0) {
            $result[] = array("count" => $updcount, "type" => "updbom");
        }


        echo Zend_Json::encode($result);

        exit;
    }
}