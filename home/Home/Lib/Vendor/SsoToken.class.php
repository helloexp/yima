<?php
// SSO处理类
class SsoToken {

    public $memcache;

    public $opt = array();

    public $_debug_log = array();

    public function __construct() {
        // 初始化默认选项
        $this->opt = array(
            'SSO_CHECK_URL' => '', 
            'SSO_LOGOUT_URL' => '', 
            'SSO_USER_SERV_URL' => '', 
            'SSO_LOGIN_URL' => '', 
            'SSO_SYSID' => '', 
            'MEMCACHE_IP' => '', 
            'MEMCACHE_PORT' => '11211', 
            'MEMCACHE_PRE' => md5(__FILE__));
    }
    // 初始化
    public function init($opt = array()) {
        $this->opt = (array) $opt + $this->opt;
        $opt = $this->opt;
        $memcache = new Memcache();
        $memcache_ip = $opt['MEMCACHE_IP'];
        $memcache_port = $opt['MEMCACHE_PORT'];
        $memcache->addserver($memcache_ip, $memcache_port) or
             die("Could not connect to memcache localhost!");
        $this->memcache = $memcache;
    }

    public function nodeLogin($nodeInfo) {
        $opt = $this->opt;
        $sso_url = $opt['SSO_LOGIN_URL'];
        // 提交地址参数
        $sso_url = $sso_url .
             ((strpos($opt['SSO_LOGIN_URL'], '?') === false) ? '?' : '') .
             "&node_id=" . $nodeInfo['node_id'] . "&user_name=" .
             $nodeInfo['email'] . "&password=" . md5($nodeInfo['password']) .
             "&last_ip=&sysid=" . $opt['SSO_SYSID'];
        
        log_write("登录请求==" . $sso_url);
        
        $this->_debug_log[] = '发往：' . $sso_url;
        $ch = curl_init();
        $timeout = 5;
        curl_setopt($ch, CURLOPT_URL, $sso_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $sso_result = curl_exec($ch);
        curl_close($ch);
        log_write("登录响应==" . var_export($sso_result,1) .  ' error:' . curl_error($ch));
        $this->_debug_log[] = '响应：' . $sso_result;
        // $sso_result =
        // mb_convert_encoding(urldecode($sso_result),'utf-8','utf-8');
        if ($sso_result) {
            $sso_result = json_decode($sso_result);
        } else {
            if (!is_production()) { //测试环境显示错误信息
                $sso_result = ['error' => curl_error($ch)];
            }
        }

        return $sso_result;
    }

    public function EditPwd($nodeInfo) {
        $opt = $this->opt;
        $sso_url = $opt['SSO_USER_SERV_URL'];
        $sys_auth = "connecttosso";
        $sys_psw = md5($sys_auth . md5($sys_auth));
        
        // $sys_auth="998123";
        // $sys_psw=md5($sys_auth.md5($sys_auth));
        
        // 提交地址参数
        $sso_url = $sso_url .
             ((strpos($opt['SSO_USER_SERV_URL'], '?') === false) ? '?' : '') .
             "&node_id=" . $nodeInfo['node_id'] . "&name=" . $nodeInfo['email'] .
             "&password=" . $nodeInfo['new_password1'] . "&password_old=" .
             $nodeInfo['old_password'] . "&sys_auth=" . $sys_auth . "&sys_psw=" .
             $sys_psw . "&sysid=" . $opt['SSO_SYSID'] . "&type=3";
        log_write("修改密码请求==" . $sso_url);
        
        $this->_debug_log[] = '发往：' . $sso_url;
        $ch = curl_init();
        $timeout = 5;
        curl_setopt($ch, CURLOPT_URL, $sso_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $sso_result = curl_exec($ch);
        curl_close($ch);
        log_write("修改密码响应==" . $sso_result);
        $this->_debug_log[] = '响应：' . $sso_result;
        // $sso_result =
        // mb_convert_encoding(urldecode($sso_result),'utf-8','utf-8');
        $sso_result = json_decode($sso_result);
        return $sso_result;
    }
    
    // 添加用户
    public function addUser($userInfo) {
        $opt = $this->opt;
        $sso_url = $opt['SSO_USER_SERV_URL'];
        $sys_auth = "connecttosso";
        $sys_psw = md5($sys_auth . md5($sys_auth));
        
        // $sys_auth="998123";
        // $sys_psw=md5($sys_auth.md5($sys_auth));
        
        // 提交地址参数
        $sso_url = $sso_url .
             ((strpos($opt['SSO_USER_SERV_URL'], '?') === false) ? '?' : '') .
             "&node_id=" . $userInfo['node_id'] . "&name=" . $userInfo['name'] .
             "&user_name=" . $userInfo['user_name'] . "&password=" .
             $userInfo['pws'] . "&email=" . $userInfo['mail'] . "&sys_auth=" .
             $sys_auth . "&sys_psw=" . $sys_psw . "&sysid=" . $opt['SSO_SYSID'] .
             "&type=0";
        log_write("新增用户请求==" . $sso_url);
        
        $this->_debug_log[] = '发往：' . $sso_url;
        $ch = curl_init();
        $timeout = 5;
        curl_setopt($ch, CURLOPT_URL, $sso_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $sso_result = curl_exec($ch);
        curl_close($ch);
        log_write("新增用户响应==" . $sso_result);
        $this->_debug_log[] = '响应：' . $sso_result;
        // $sso_result =
        // mb_convert_encoding(urldecode($sso_result),'utf-8','utf-8');
        $sso_result = json_decode($sso_result);
        return $sso_result;
    }
    
    // 修改用户
    public function editUser($userInfo) {
        $opt = $this->opt;
        $sso_url = $opt['SSO_USER_SERV_URL'];
        $sys_auth = "connecttosso";
        $sys_psw = md5($sys_auth . md5($sys_auth));
        
        // $sys_auth="998123";
        // $sys_psw=md5($sys_auth.md5($sys_auth));
        
        // 提交地址参数
        $sso_url = $sso_url .
             ((strpos($opt['SSO_USER_SERV_URL'], '?') === false) ? '?' : '') .
             "&" . http_build_query($userInfo) . "&sys_auth=" . $sys_auth .
             "&sys_psw=" . $sys_psw . "&sysid=" . $opt['SSO_SYSID'] . "&type=1";
        log_write("修改用户请求==" . $sso_url);
        
        $this->_debug_log[] = '发往：' . $sso_url;
        $ch = curl_init();
        $timeout = 5;
        curl_setopt($ch, CURLOPT_URL, $sso_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $sso_result = curl_exec($ch);
        curl_close($ch);
        log_write("修改用户响应==" . $sso_result);
        $this->_debug_log[] = '响应：' . $sso_result;
        // $sso_result =
        // mb_convert_encoding(urldecode($sso_result),'utf-8','utf-8');
        $sso_result = json_decode($sso_result);
        return $sso_result;
    }

    /*
     * 校验token,并返回用户信息 return array( 'resp_id' =>'0000', 'user_id' =>
     * $sso_result->resp_data->user_id, 'node_id' =>
     * $sso_result->resp_data->node_id, 'user_name' =>
     * iconv('UTF-8','GBK',urldecode($sso_result->resp_data->user_name)), 'name'
     * => iconv('UTF-8','GBK',urldecode($sso_result->resp_data->name)), );
     */
    public function tokenCheck($token) {
        static $tokenCheck_num = 0;
        log_write('tokenCheck_num:' . $tokenCheck_num ++);
        if (! $token) {
            return false;
        }
        if (! isset($_SESSION)) {
            session_start();
        }
        $opt = $this->opt;
        $sso_url = $opt['SSO_CHECK_URL'];
        // 提交地址参数
        $sso_url = $sso_url .
             ((strpos($opt['SSO_CHECK_URL'], '?') === false) ? '?' : '') .
             '&sysid=' . $opt['SSO_SYSID'] . '&token=' . $token;
        $this->_debug_log[] = '发往：' . $sso_url;
        log_write("发往SSO登录校验:" . $sso_url);
        $ch = curl_init();
        $timeout = 50;
        curl_setopt($ch, CURLOPT_URL, $sso_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $sso_result = curl_exec($ch);
        curl_close($ch);
        $this->_debug_log[] = '响应：' . $sso_result;
        // $sso_result =
        // mb_convert_encoding(urldecode($sso_result),'utf-8','utf-8');
        log_write("登陆校验返回" . $sso_result);
        $sso_result = json_decode($sso_result);
        log_write(
            "发往SSO登录校验报文返回==resp_id:" . $sso_result->resp_id . "&user_name=" .
                 $sso_result->resp_data->user_name);
        // 如果校验通过
        if ($sso_result->resp_id == '0000' && $sso_result->resp_data->isLogin) {
            $session_time = ini_get('session.gc_maxlifetime'); // 获取SESION有效时间
                                                               // 保存
                                                               // session_id->token
            $token_key = $opt['MEMCACHE_PRE'] . session_id();
            $this->memcache->set($token_key, $token, 0, $session_time);
            // 保存 token->session_id
            $session_key = $opt['MEMCACHE_PRE'] . $token;
            $this->memcache->set($session_key, session_id(), 0, $session_time);
            // 保存 token_list
            $token_list = $this->memcache->get(
                $opt['MEMCACHE_PRE'] . 'token_list');
            if (! in_array($token, (array) $token_list)) {
                $token_list[] = $token;
                $this->memcache->set($opt['MEMCACHE_PRE'] . 'token_list', 
                    $token_list);
            }
            
            // ----------
            return array(
                'resp_id' => '0000', 
                'user_id' => $sso_result->resp_data->user_id, 
                'node_id' => $sso_result->resp_data->node_id, 
                'user_name' => urldecode($sso_result->resp_data->user_name), 
                'name' => urldecode($sso_result->resp_data->name));
        }
        return array(
            'resp_id' => $sso_result->resp_id, 
            'resp_str' => urldecode($sso_result->resp_data->msg));
    }

    /*
     * 退出token 根据token 删除相对的 session_id
     */
    public function tokenDelete($token) {
        if (! $token)
            return false;
        $opt = $this->opt;
        // 根据token得到session_id
        $session_id = $this->memcache->get($opt['MEMCACHE_PRE'] . $token);
        if (isset($_SESSION)) {
            session_write_close();
        }
        if ($session_id) {
            session(array(
                'id' => $session_id));
        }
        session('[start]');
        session_unset();
        // 删除两个对应关系
        $this->memcache->delete($opt['MEMCACHE_PRE'] . $token);
        $this->memcache->delete($opt['MEMCACHE_PRE'] . $session_id);
        // 向SSO退出接口发起请求
        $sso_url = $opt['SSO_LOGOUT_URL'];
        // 提交地址参数
        $sso_url = $sso_url .
             ((strpos($opt['SSO_LOGOUT_URL'], '?') === false) ? '?' : '') .
             '&sysid=' . $opt['SSO_SYSID'] . '&token=' . $token;
        $this->_debug_log[] = '发往：' . $sso_url;
        log_write("发往SSO退出:" . $sso_url);
        $ch = curl_init();
        $timeout = 30;
        curl_setopt($ch, CURLOPT_URL, $sso_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $sso_result = curl_exec($ch);
        curl_close($ch);
        $this->_debug_log[] = '响应：' . $sso_result;
        log_write("发往SSO退出响应:" . $sso_result);
        $sso_result = json_decode($sso_result);
        return array(
            'resp_id' => $sso_result->resp_id, 
            'resp_str' => $sso_result->resp_data->msg, 
            'session_id' => $session_id);
    }

    /* 返回token可用列表 */
    public function tokenList() {
        $opt = $this->opt;
        $token_list = $this->memcache->get($opt['MEMCACHE_PRE'] . 'token_list');
        if (! $token_list) {
            return array();
        }
        foreach ((array) $token_list as $k => $v) {
            if (! $this->memcache->get($opt['MEMCACHE_PRE'] . $v))
                unset($token_list[$k]);
        }
        $this->memcache->set($opt['MEMCACHE_PRE'] . 'token_list', $token_list);
        return $token_list;
    }

    /* 根据session_id返回 token 并且更新当前memcache有效期 */
    public function getToken($session_id) {
        if (! $session_id)
            return false;
        $opt = $this->opt;
        $token_key = $opt['MEMCACHE_PRE'] . $session_id;
        $token = $this->memcache->get($token_key);
        if (! $token)
            return false;
        $session_time = ini_get('session.gc_maxlifetime'); // 获取SESION有效时间
                                                           // 保存
                                                           // session_id->token
        $token_key = $opt['MEMCACHE_PRE'] . $session_id;
        $this->memcache->set($token_key, $token, 0, $session_time);
        // 保存 token->session_id
        $session_key = $opt['MEMCACHE_PRE'] . $token;
        $this->memcache->set($session_key, $session_id, 0, $session_time);
        return $token;
    }
}

/*
$sso = new SsoToken;
$sso->init(array(
	'SSO_URL'=>	'http://222.44.51.34/SSO2/index.php?m=Interface&a=SsoCheck',
	'SSO_SYSID'=> '00013',
	'MEMCACHE_IP'=>	'127.0.0.1',
));
$token = '5ee08dedb161991e4cb16487a6f216fb';
$result = $sso->tokenCheck($token);
//print_r($sso);
//print_r($result);
$result = $sso->tokenList();
//print_r($result);
$result = $sso->tokenDelete($token);
//var_dump(session_id());
print_r($result);
*/