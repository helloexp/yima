<?php
// 指令接口服务设置
class ServiceAction extends BaseAction {

    protected $wx;

    public $node_id;

    public $req;

    public $token;

    public $access_token;

    public $app_id;

    public $app_secret;

    public $user_name;

    public $response_msg_id;

    public $node_wx_id;

    public $scene_id;

    public $msg_type;

    public $msg_info;

    /*
     * {"location":{"location_flag":"1","resp_count":"3","large_image":"00004488top.jpg","small_image":"00004488item.jpg"}}
     */
    public $setting = array();

    public function _initialize() {
        C('WeixinServ', require (CONF_PATH . 'configWeixinServ.php'));
        if (C('WeixinServ.LOG_PATH'))
            C('LOG_PATH', C('WeixinServ.LOG_PATH')); // 重新定义目志目录
    }

    /* 入口函数 */
    public function index() {
        $postStr = file_get_contents('php://input');
        $this->log("index:component_verify_ticket :" . $postStr);
        $this->log(
            'http://' . $_SERVER['HTTP_HOST'] . ':' . $_SERVER["SERVER_PORT"] .
                 $_SERVER["REQUEST_URI"]);
        $wx_grant = D('WeiXinGrant', 'Service');
        $wx_grant->init();
        $res_str = $wx_grant->api_service_notify_decrypt($postStr, 
            $_REQUEST['msg_signature'], $_REQUEST['timestamp'], 
            $_REQUEST['nonce']);
        $this->log("dercypt :" . print_r($res_str, true));
    }

    public function auth() {
        $postStr = file_get_contents('php://input');
        $this->log("auth:component_verify_ticket :" . $postStr);
        $this->log(
            'http://' . $_SERVER['HTTP_HOST'] . ':' . $_SERVER["SERVER_PORT"] .
                 $_SERVER["REQUEST_URI"]);
        $this->log('header_url:-----' . $_REQUEST['header_url'] . '-------');
        // $wx_grant = D('WeiXinGrant','Service');
        // $wx_grant->init();
        // $wx_grant->set_weixin_info($_REQUEST['node_id'],
        // $_REQUEST['auth_code']);
        print_r($_REQUEST);
        // header('Location: '.$_REQUEST['header_url']);
    }
    // 获取消息服务，然后转向原处理地址
    public function message() {
        $postStr = file_get_contents('php://input');
        $this->log("message:component_message :" . $postStr);
        $this->log("message:component_message app_id:" . $_REQUEST['appid']);
        $this->log(
            'http://' . $_SERVER['HTTP_HOST'] . ':' . $_SERVER["SERVER_PORT"] .
                 $_SERVER["REQUEST_URI"]);
        
        $wx_grant = D('WeiXinGrant', 'Service');
        $wx_grant->init();
        $res_str = $wx_grant->msg_redirect($postStr, $_REQUEST['msg_signature'], 
            $_REQUEST['timestamp'], $_REQUEST['nonce'], $_REQUEST['appid']);
        $this->log("message:component_message res_str :" . $res_str);
        echo $res_str;
    }

    public function jump() {
        // $url =
        // 'https://mp.weixin.qq.com/cgi-bin/componentloginpage?component_appid=wx57031ac296b60d74&pre_auth_code=Lvj22nFYxpvX51IzJELQsykgjP-VXzfCeq10CVCKSla1yBIDXHwJRsf5FqmQGfhq&redirect_uri=http%3A%2F%2Ftest.wangcaio2o.com';
        // $url =
        // 'http://222.44.51.34:3700/cgi-bin/componentloginpage?component_appid=wx57031ac296b60d74&pre_auth_code=Lvj22nFYxpvX51IzJELQsykgjP-VXzfCeq10CVCKSla1yBIDXHwJRsf5FqmQGfhq&redirect_uri=http%3A%2F%2Ftest.wangcaio2o.com';
        // $url =
        // 'http://221.181.75.20/jienu/jump.php?component_appid=wx57031ac296b60d74&pre_auth_code=Lvj22nFYxpvX51IzJELQsykgjP-VXzfCeq10CVCKSla1yBIDXHwJRsf5FqmQGfhq&redirect_uri=http%3A%2F%2Ftest.wangcaio2o.com';
        // header('Location:'.$url);
        echo '<a href="https://mp.weixin.qq.com/cgi-bin/componentloginpage?component_appid=wx57031ac296b60d74&pre_auth_code=6qbCPICJawny2xGmSrvccS64WGxoTyDtSnaYSxd7oE9iQJHNAsVie2Q5zR8LRnNA&redirect_uri=http%3A%2F%2Ftest.wangcaio2o.com%2Findex.php%3Fg%3DWeixinServ%26m%3DService%26a%3Dauth%26node_id%3D00012070%26header_url%3Dhttp%253A%252F%252Ftest.wangcaio2o.com">testttttttttttttttttt</a>';
    }
    
    // 测试
    public function test() {
        $postStr = file_get_contents('php://input');
        $this->log("微信 component_message :" . $postStr);
        $this->log(
            'http://' . $_SERVER['HTTP_HOST'] . ':' . $_SERVER["SERVER_PORT"] .
                 $_SERVER["REQUEST_URI"]);
    }
    
    // 记录日志
    protected function log($msg, $level = Log::INFO) {
        // trace('Log.'.$level.':'.$msg);
        Log::write($msg, '[' . _APP_PID_ . ']' . $level);
    }
}