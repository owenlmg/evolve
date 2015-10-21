<?php
/**
 * 2013-7-6 13:41:44
 * @author      x.li
 * @abstract    主页
 */
class Home_IndexController extends Zend_Controller_Action
{
    /**
     * 初始化：检查用户是否登录，未登录则跳转到登录界面，否则跳转到主页
     * !CodeTemplates.overridecomment.nonjd!
     * @see Zend_Controller_Action::init()
     */
    public function init()
    {
        $user_session = new Zend_Session_Namespace('user');
        
        if(!isset($user_session->user_info)){
            //$this->_forward('index', 'login');
            $this->_redirect('public/home/login');
        }
    }
    
    public function testAction()
    {
        
    }

    /**
     * 首页
     */
    public function indexAction()
    {
        $user_session = new Zend_Session_Namespace('user');
        
        $weekArr = array('日', '一', '二', '三', '四', '五', '六');
        
        $date = date('Y年m月d日').' 星期'.$weekArr[date('w')];
        
        $this->view->loginInfo = array(
                'date' => $date,
                'user' => '当前用户： '.$user_session->user_info['user_number'].' '.$user_session->user_info['user_name']
        );
    }
    
    /**
     * 获取菜单
     */
    public function getmenusAction()
    {
        // 请求参数
        $request = $this->getRequest()->getParams();
        
        // 请求菜单的层级ID
        $parent_id = isset($request['parent_id']) ? $request['parent_id'] : 0;
        $option = isset($request['option']) ? $request['option'] : null;
        
        $menu = new Home_Model_Menu();
        
        //echo date('H:i:s').'<br>';
        
        if($option == 'treedata'){
            echo Zend_Json::encode($menu->getTreeData($parent_id));
        }else{
            $data = $menu->getMenuData($parent_id);
            /* echo '<pre>';
            print_r($data);
            exit; */
            $json = Zend_Json::encode($data);
            
            $patterns[0] = '/"disabled":"0"/';
            $patterns[1] = '/"disabled":"1"/';
            $patterns[2] = '/"handler":"menuClick"/';
            
            $replacements[2] = '"disabled":0';
            $replacements[1] = '"disabled":1';
            $replacements[0] = '"handler":menuClick';
            
            // 转换JSON中的数据格式（临时解决办法）
            echo preg_replace($patterns, $replacements, $json);
        }
        
        //echo date('H:i:s').'<br>';
        
        exit;
    }
}

