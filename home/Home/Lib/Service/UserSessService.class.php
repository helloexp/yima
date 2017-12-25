<?php
/* 用户登录服务 */
class UserSessService {

    private $userInfo;

    private $sso;

    private $errorMsg = '';

    const USERSESS = 'userSessInfo';

    public $authService;

    public $_ssoCheckFlag = false;

    public $_ssoCheckExpire = 300;
    // sso校验时间300秒
    public function __construct() {
        //SessionMemcache 会传递sessionid
        $this->userInfo = session(self::USERSESS);
        
        
         // 以下是测试登录信息 
         // $_SESSION[self::USERSESS] = null; 
         // $_SESSION[self::USERSESS] = $_SESSION[self::USERSESS] or $_SESSION[self::USERSESS] = array(
         //    'user_id'     => 7845,
         //    'node_id'     => '00026412', 
         //    'user_name'   => 'imagecodf@mail.bccto.me', 
         //    'name'        => '福特', 
         //    'last_time'   => date('YmdHis'),
         //    'token'       => '', 
         //    'new_role_id' => '2' 
         //    );
         
    }

    /* 校验用户是否登录 */
    public function isLogin($param = false) {
        if ($param) {
            // 游客用户，只允许特定url通过
            if (D('TempLogin', 'Service')->isTempUser($this->userInfo['node_id']) == 1) {
                $ret = M('tguest_url_info')->field('group_concat(url_info) AS urls')
                    ->where(['node_id' => $this->userInfo['node_id']])->find();
                $urlInfo = explode(',', $ret['urls']);
                $gmUrl = GROUP_NAME.'/'.MODULE_NAME.'/*';
                $gmaUrl = GROUP_NAME.'/'.MODULE_NAME.'/'.ACTION_NAME;
                $blackList = array(
                    'MarketActive/VisualCode/index'
                    );
                $userInfo = session(self::USERSESS);
                if ((in_array($gmUrl, $urlInfo) || in_array($gmaUrl, $urlInfo)) 
                    && !empty($userInfo)
                    && !in_array($gmaUrl, $blackList))
                {
                    return true;
                }
                else{
                    return false;
                }
            }
        }
        $this->userInfo = session(self::USERSESS);
        if (! $this->userInfo || ! $this->userInfo['node_id']) {
            session(self::USERSESS, null); // 清空
            return false;
        }
        
        // 校验sso_token有效性
        if (! $this->_ssoCheckFlag && $this->userInfo['token']) {
            $this->_ssoCheckFlag = true;
            $result = S('_SSO_CHECK_FLAG_' . $this->userInfo['user_id']);
            if ($result) {
                return ! ! $result;
            }
            // 校验sso的token有效性
            $sso = $this->initSso();
            $result = $sso->tokenCheck($this->userInfo['token']);
            if (! $result || $result['resp_id'] != '0000') {
                S('_SSO_CHECK_FLAG_' . $this->userInfo['user_id'], false, 
                    array(
                        'expire' => $this->_ssoCheckExpire));
                session(self::USERSESS, null); // 清空
                return false;
            }
            S('_SSO_CHECK_FLAG_' . $this->userInfo['user_id'], true, 
                array(
                    'expire' => $this->_ssoCheckExpire));
            return true;
        }
        return true;
    }

    /*
     * 第三方登录，没有测注册，有登录 @param $req_array['user_id'], @param
     * $req_array['node_id'], @param $req_array['user_name'], @param
     * $req_array['name'], @param $req_array['reg_from'], @param
     * $req_array['third_token'], @param $req_array['third_uid'],
     */
    public function thirdLogin($req_array) {
        // token校验过
        if ($req_array && $req_array['user_id']) {
            // array_walk_recursive($sso_result,'_filter_iconv');
            // 记录session值
            // $_SESSION[self::USERSESS] = array(
            // 'user_id' => $req_array['user_id'],
            // 'node_id' => $req_array['node_id'],
            // 'user_name' => $req_array['user_name'],
            // 'name' => $req_array['name'],
            // 'last_time' => date('YmdHis'),
            // 'token' => '',
            // 'third_token' => $req_array['third_token'],
            // 'third_uid' => $req_array['third_uid'],
            // 'reg_from' => $req_array['reg_from'],
            // );
            
            $this->setLoginInfo($req_array, 
                array(
                    'user_id', 
                    'node_id', 
                    'user_name', 
                    'name', 
                    'third_token', 
                    'third_uid', 
                    'reg_from'), array(
                    'token' => ''));
            
            // 更新ip,登陆时间
            $data = array(
                'login_time' => date('YmdHis'), 
                'login_ip' => get_client_ip(), 
                'update_time' => date('YmdHis'));
            M('tuser_info')->where(
                array(
                    'user_id' => $req_array['user_id']))->save($data);
            
            // 记录日志
            node_log('第三方登录', print_r($req_array, true), 
                array(
                    'log_type' => 'LOGIN'));
            return true;
        }  // 如果token不对，清空session
else {
            unset($_SESSION[self::USERSESS]);
            $this->errorMsg = '登录超时，请重新登录';
            return false;
        }
    }

    /* SSO接口登录 */
    public function ssoLogin($req_array) {
        $sso = $this->initSso();
        $sso_result = $sso->nodeLogin($req_array);
        
        if ($sso_result && $sso_result->resp_id == '0000') {
            $this->loginIndexByToken($sso_result->resp_data->token);
            if (! $this->isLogin()) {
                return '您还未登录';
            }
            
            $tg_id = isset($_COOKIE['reg_tgxt_id']) ? $_COOKIE['reg_tgxt_id'] : '';
            if ($tg_id != null && ! empty($tg_id)) {
                $dataTgxt = array(
                    'tg_id' => $tg_id, 
                    "node_id" => isset($req_array['node_id']) ? $req_array['node_id'] : '',
                    'login_name' => isset($req_array['email']) ? $req_array['email'] : '',
                    'login_time' => date('YmdHis'), 
                    'login_ip' => get_client_ip());
                M('tuser_info_tgxt')->add($dataTgxt);
            }
            
            return true;
        } else {
            
            return $sso_result->resp_data->msg;
        }
    }

    /* SSO退出 */
    public function ssoLogout($token) {
        if (! $token) {
            return false;
        }
        $sso = $this->initSso();
        $arr = $sso->tokenDelete($token);
        return $arr;
    }

    /* 修改密码 */
    public function EditPwd($req_array) {
        if (empty($req_array)) {
            return false;
        }
        $sso = $this->initSso();
        $arr = $sso->EditPwd($req_array);
        return $arr;
    }

    /* 校验sso得到用户信息 */
    public function loginByToken($token = null) {
        $sso = $this->initSso();
        if ($token == null) {
            // 保持keep
            $token = $sso->getToken(session_id());
        } else {
            // flag判断是否重复登录
            if ($_SESSION[self::USERSESS]['token'] == $token)
                $flag = '1';
            else
                $flag = '0';
                // $token = 'e389a83de051e4ca0b8ada7226dde7bb';
                // 验证是否SSO已经登陆
            $sso_result = $sso->tokenCheck($token);
            
            // token校验过
            if ($sso_result && $sso_result['resp_id'] == '0000') {
                // array_walk_recursive($sso_result,'_filter_iconv');
                // 记录session值
                // $_SESSION[self::USERSESS] = array(
                // 'user_id' => $sso_result['user_id'],
                // 'node_id' => $sso_result['node_id'],
                // 'user_name' => $sso_result['user_name'],
                // 'name' => $sso_result['name'],
                // 'last_time' => date('YmdHis'),
                // 'token' => $token
                // );
                $this->setLoginInfo($sso_result, 
                    array(
                        'user_id', 
                        'node_id', 
                        'user_name', 
                        'name'), 
                    array(
                        'token' => $token));
                // 更新ip,登陆时间
                $data = array(
                    'login_time' => date('YmdHis'), 
                    'login_ip' => get_client_ip(), 
                    'update_time' => date('YmdHis'));
                // 更新首次登录时间
                $userInfo = $this->getUserInfo();
                $userloginInfo = M('tuser_info')->field('login_time,first_time')
                    ->where("user_name='" . $userInfo['user_name'] . "'")
                    ->find();
                if (! $userloginInfo['login_time'])
                    $data['first_time'] = date('YmdHis');
                elseif ($userloginInfo['login_time'] &&
                     ! $userloginInfo['first_time'])
                    $data['first_time'] = $userloginInfo['login_time'];
                $result = M('tuser_info')->where(
                    "node_id={$sso_result['node_id']} AND user_id={$sso_result['user_id']}")->save(
                    $data);
                if (! $result) {
                    log_write('更新ip，上次登陆时间失败');
                }
                if ($flag == '0')
                    node_log("登录");
            }  // 如果token不对，清空session
else {
                unset($_SESSION[self::USERSESS]);
                $this->errorMsg = $sso_result['resp_str'];
                return false;
            }
        }
        return true;
    }

    /* 校验sso得到用户信息 */
    public function loginIndexByToken($token = null) {
        $sso = $this->initSso();
        if ($token == null) {
            // 保持keep
            $token = $sso->getToken(session_id());
        } else {
            // $token = 'e389a83de051e4ca0b8ada7226dde7bb';
            // 验证是否SSO已经登陆
            $sso_result = $sso->tokenCheck($token);
            
            // token校验过
            if ($sso_result && $sso_result['resp_id'] == '0000') {
                // array_walk_recursive($sso_result,'_filter_iconv');
                // 记录session值
                // $_SESSION[self::USERSESS] = array(
                // 'user_id' => $sso_result['user_id'],
                // 'node_id' => $sso_result['node_id'],
                // 'user_name' => $sso_result['user_name'],
                // 'name' => $sso_result['name'],
                // 'last_time' => date('YmdHis'),
                // 'token' => $token
                // );
                $this->setLoginInfo($sso_result, 
                    array(
                        'user_id', 
                        'node_id', 
                        'user_name', 
                        'name'), 
                    array(
                        'token' => $token));
            }  // 如果token不对，清空session
else {
                unset($_SESSION[self::USERSESS]);
                $this->errorMsg = $sso_result['resp_str'];
                return false;
            }
        }
        return true;
    }

    /**
     * 统一设置用户登录信息
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param array $result
     * @param array $requireField
     * @param array $optInfo
     */
    private function setLoginInfo($result, $requireField = array(), $optInfo = array()) {
        if (empty($requireField)) {
            $requiredInfo = $result;
        } else {
            foreach ($requireField as $field) {
                $requiredInfo[$field] = isset($result[$field]) ? $result[$field] : '';
            }
        }
        
        $userName = isset($requiredInfo['user_name']) ? $requiredInfo['user_name'] : '';
        if ($userName) {
            $userloginInfo = M('tuser_info')->field('add_time')
                ->where("user_name='" . $userName . "'")
                ->find();
            $addTime = isset($userloginInfo['add_time']) ? $userloginInfo['add_time'] : '';
            $requiredInfo['add_time'] = $addTime;
        }
        //企业简称
        $requiredInfo['node_short_name'] = M('tnode_info')->where(array('node_id'=>$requiredInfo['node_id']))->getField('node_short_name');
        
        $requiredInfo['last_time'] = date('YmdHis');
        $_SESSION[self::USERSESS] = array_merge($requiredInfo, (array) $optInfo);
    }
    
    // 新增用户
    public function addUser($reqArray) {
        if (empty($reqArray)) {
            return false;
        }
        $sso = $this->initSso();
        $arr = $sso->addUser($reqArray);
        return $arr;
    }
    
    // 编辑用户
    public function editUser($reqArray) {
        if (empty($reqArray)) {
            return false;
        }
        $sso = $this->initSso();
        $arr = $sso->editUser($reqArray);
        return $arr;
    }

    /* 初始化sso */
    public function initSso() {
        if (! $this->sso) {
            // 载入sso类
            import("@.Vendor.SsoToken");
            $sso = new SsoToken();
            $sso->init(
                array(
                    'SSO_CHECK_URL' => C('SSO_CHECK_URL'), 
                    'SSO_LOGOUT_URL' => C('SSO_LOGOUT_URL'), 
                    'SSO_LOGIN_URL' => C('SSO_LOGIN_URL'), 
                    'SSO_USER_SERV_URL' => C('SSO_USER_SERV_URL'), 
                    'SSO_SYSID' => C('SSO_SYSID'), 
                    'MEMCACHE_IP' => C('SSO_MEMCACHE_IP'), 
                    'MEMCACHE_PRE' => 'WC_'));
            $this->sso = $sso;
        }
        return $this->sso;
    }

    /* 获取用户信息 */
    public function getUserInfo($k = null) {
        if ($k) {
            return isset($this->userInfo[$k]) ? $this->userInfo[$k] : null;
        } else {
            return $this->userInfo;
        }
    }

    public function getErrorMsg() {
        return $this->errorMsg;
    }

    public function setUserInfo($name, $value) {
        // 设置用户信息
        $info = & $_SESSION[self::USERSESS];
        $info[$name] = $value;
        $this->userInfo = $info;
        return $info;
    }
    
    // 校验权限
    public function checkAuth($name = '', $user_id = null, $userInfo = array()) {
        if(!is_array($userInfo)){
            $userInfo = array();
        }
        $userInfo = array_merge($this->getUserInfo(), $userInfo);
        if (! $user_id) {
            $user_id = $userInfo['user_id'];
        }
        if ($name == '') {
            $name = GROUP_NAME . '/' . MODULE_NAME . '/' . ACTION_NAME;
        }
        import('@.Vendor.Auth');
        if (! $this->authService) {
            $this->authService = new Auth();
        }
        $auth = $this->authService;
        $auth->setUserInfo($user_id, $userInfo);
        return $auth->check($name, $user_id);
    }
    private function allowUrl()
    {

    }
}