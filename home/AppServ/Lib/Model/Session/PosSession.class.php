<?php

/**
 * 记录终端的Session
 *
 * @author wangtr @editor wangtr
 */
class PosSession {

    /**
     * session名称
     *
     * @var String
     */
    Const SESSION_NAME = "webPosSession";

    /**
     * 登陆系统成功后，session
     *
     * @var String
     */
    Const SESSION_ARG_LOGIN_KEY_NAME = "posweb_login_info";

    /**
     * 令牌存在cookie中的名称
     *
     * @var String
     */
    Const COOKIE_TOKEN_KEY_NAME = "SSO_TOKEN";

    /**
     * session信息 商户号Id 键名
     *
     * @var string
     */
    Const SESSION_NODEID_KEY_NAME = "node_id";

    /**
     * session信息 商户名 键名
     *
     * @var string
     */
    Const SESSION_NODENAME_KEY_NAME = "node_name";

    /**
     * session信息 用户Id 键名
     *
     * @var string
     */
    Const SESSION_USERID_KEY_NAME = "user_id";

    /**
     * session信息 用户昵称 键名
     *
     * @var string
     */
    Const SESSION_USERNAME_KEY_NAME = "user_name";

    /**
     * session信息 用户角色
     *
     * @var string
     */
    Const SESSION_USERROLE_KEY_NAME = "user_role";

    /**
     * session信息 PosId 键名
     *
     * @var string
     */
    Const SESSION_POSID_KEY_NAME = "pos_id";

    /**
     * session信息 PosName 键名
     *
     * @var string
     */
    Const SESSION_POSNAME_KEY_NAME = "pos_name";

    /**
     * session信息 用户邮箱 键名
     *
     * @var string
     */
    Const SESSION_MAILADDR_KEY_NAME = "mail_addr";

    /**
     * session信息 辅助密钥 键名
     *
     * @var string
     */
    Const SESSION_WORKKEY_KEY_NAME = "work_key";

    /**
     * session信息 主密钥 键名
     *
     * @var string
     */
    Const SESSION_MASTERKEY_KEY_NAME = "master_key";

    /**
     * session信息 用户流水号 键名
     *
     * @var string
     */
    Const SESSION_ID_KEY_NAME = "id";

    /**
     * session信息 用户信息键名
     *
     * @var string
     */
    Const SESSION_USER_KEY_NAME = "user_info";

    /**
     * session信息 一次验证提交信息
     *
     * @var string
     */
    Const SESSION_TRADE_INF = "trade_inf";

    /**
     * session信息 一次验证提交对账
     *
     * @var string
     */
    Const SESSION_SETTLE_INF = "settle_inf";

    /**
     * session信息 可访问系统 键名
     *
     * @var string
     */
    // Const SESSION_ROLE_MENU_KEY_NAME = "role_menu_info";

    /**
     * 构造函数
     *
     * @param string $sessionName session名称 不设置为默认值
     */
    public $session;

    public function __construct() {
        // 初始化session
        if (!isset($_SESSION[self::SESSION_NAME])) {
            $_SESSION[self::SESSION_NAME] = array();
        }
        $this->session = &$_SESSION[self::SESSION_NAME];
    }

    /**
     * 清空一次交易的信息
     */
    public function clearTradeInf() {
        $this->setArg(self::SESSION_TRADE_INF, array());
    }

    /**
     * 平台号 system_id
     */
    public function setSystemId($system_id) {
        $trade_inf              = $this->getArg(self::SESSION_TRADE_INF);
        $trade_inf['system_id'] = $system_id;
        $this->setArg(self::SESSION_TRADE_INF, $trade_inf);
    }

    public function getSystemId() {
        $trade_inf = $this->getArg(self::SESSION_TRADE_INF);

        return isset($trade_inf['system_id']) ? $trade_inf['system_id'] : '';
    }

    /**
     * 设置传递条码类型 099辅助码验证 098条码内容验证
     *
     * @param $assCodeType 条码类型
     */
    public function setAssCodeType($assCodeType) {
        $trade_inf                = $this->getArg(self::SESSION_TRADE_INF);
        $trade_inf['assCodeType'] = $assCodeType;
        $this->setArg(self::SESSION_TRADE_INF, $trade_inf);
    }

    /**
     * 获取条码类型
     */
    public function getAssCodeType() {
        $trade_inf = $this->getArg(self::SESSION_TRADE_INF);

        return isset($trade_inf['assCodeType']) ? $trade_inf['assCodeType'] : '';
    }

    /**
     * 设置辅助码
     *
     * @param $assCode 辅助码
     */
    public function setAssCode($assCode) {
        $trade_inf            = $this->getArg(self::SESSION_TRADE_INF);
        $trade_inf['assCode'] = $assCode;
        $this->setArg(self::SESSION_TRADE_INF, $trade_inf);
    }

    /**
     * 获取辅助码
     */
    public function getAssCode() {
        $trade_inf = $this->getArg(self::SESSION_TRADE_INF);

        return isset($trade_inf['assCode']) ? $trade_inf['assCode'] : '';
    }

    /**
     * 设置条码
     *
     * @param $codeHex 条码
     */
    public function setCodeHex($codeHex) {
        $trade_inf            = $this->getArg(self::SESSION_TRADE_INF);
        $trade_inf['codeHex'] = $codeHex;
        $this->setArg(self::SESSION_TRADE_INF, $trade_inf);
    }

    /**
     * 获取条码
     */
    public function getCodeHex() {
        $trade_inf = $this->getArg(self::SESSION_TRADE_INF);

        return isset($trade_inf['codeHex']) ? $trade_inf['codeHex'] : '';
    }

    /**
     * 设置验码尝试次数
     */
    public function addTryTime() {
        $trade_inf = $this->getArg(self::SESSION_TRADE_INF);
        if (isset($trade_inf['tryTime'])) {
            $trade_inf['tryTime'] += 1;
        } else {
            $trade_inf['tryTime'] = 1;
        }
        $this->setArg(self::SESSION_TRADE_INF, $trade_inf);
    }

    /**
     * 获取尝试次数
     */
    public function getTryTime() {
        $trade_inf = $this->getArg(self::SESSION_TRADE_INF);

        return isset($trade_inf['tryTime']) ? $trade_inf['tryTime'] : 0;
    }

    /**
     * 设置交易金额
     *
     * @param $amount
     */
    public function setAmount($amount) {
        $trade_inf           = $this->getArg(self::SESSION_TRADE_INF);
        $trade_inf['amount'] = $amount;
        $this->setArg(self::SESSION_TRADE_INF, $trade_inf);
    }

    /**
     * 获取交易金额
     */
    public function getAmount() {
        $trade_inf = $this->getArg(self::SESSION_TRADE_INF);

        return isset($trade_inf['amount']) ? $trade_inf['amount'] : '';
    }

    /**
     * 设置交易密码
     *
     * @param $pwd
     */
    public function setPwd($pwd) {
        $trade_inf        = $this->getArg(self::SESSION_TRADE_INF);
        $trade_inf['pwd'] = $pwd;
        $this->setArg(self::SESSION_TRADE_INF, $trade_inf);
    }

    /**
     * 获取交易密码
     */
    public function getPwd() {
        $trade_inf = $this->getArg(self::SESSION_TRADE_INF);

        return isset($trade_inf['pwd']) ? $trade_inf['pwd'] : '';
    }

    /*
     * 设置goods_id
     */
    public function setGoodsId($goods_id) {
        $trade_inf             = $this->getArg(self::SESSION_TRADE_INF);
        $trade_inf['goods_id'] = $goods_id;
        $this->setArg(self::SESSION_TRADE_INF, $trade_inf);
    }

    /*
     * 获取goods_id
     */
    public function getGoodsId() {
        $trade_inf = $this->getArg(self::SESSION_TRADE_INF);

        return isset($trade_inf['goods_id']) ? $trade_inf['goods_id'] : '';
    }

    /**
     * 设置登录信息
     *
     * @param array $userInfo 用户登陆信息
     * @param array $userInfo 可访问系统信息
     */
    private function setLoginInfo($userInfo) {
        $data = array(
                self::SESSION_USER_KEY_NAME => $userInfo,
        );
        $this->setArg(self::SESSION_ARG_LOGIN_KEY_NAME, $data);
    }

    /**
     * 获取登陆信息
     *
     * @return array
     */
    public function getLoginInfo() {
        return $this->getArg(self::SESSION_ARG_LOGIN_KEY_NAME);
    }

    /**
     * 用户登陆（不负责用户名密码验证，只在验证通过后负责session的设置）
     *
     * @param boolean $isRemember 是否需要记住登录状态
     */
    public function login($userInfo, $isRemember = false) {
        $this->setLoginInfo($userInfo);
        $token = $this->getSessionId();
        $this->putTokenToCookie($token, !$isRemember);
    }

    /**
     * 登出
     */
    public function logout() {
        $this->clearToken();
        $this->close();
    }

    /**
     * 是否登陆了 （注意：在调用此方法前不能调用startSession方法）
     *
     * @param $sysId 系统Id
     *
     * @return boolean
     */
    public function isLogin($sysId = 0) {

        // 检查是否有登陆系统权限(考虑是否应该在业务系统处理)
        $rs = $this->checkIsAllowLogin($sysId);
        if (!$rs) {
            return false;
        }

        return true;
    }

    /**
     * 用户数据表自增id
     */
    public function getId() {
        $info = $this->getLoginInfo();

        return $info[self::SESSION_USER_KEY_NAME][self::SESSION_ID_KEY_NAME];
    }

    /**
     * 商户 id
     */
    public function getNodeId() {
        $info = $this->getLoginInfo();

        return $info[self::SESSION_USER_KEY_NAME][self::SESSION_NODEID_KEY_NAME];
    }

    /**
     * 商户名称
     */
    public function getNodeName() {
        $info = $this->getLoginInfo();

        return $info[self::SESSION_USER_KEY_NAME][self::SESSION_NODENAME_KEY_NAME];
    }

    /**
     * 用户id
     */
    public function getUserId() {
        $info = $this->getLoginInfo();

        return $info[self::SESSION_USER_KEY_NAME][self::SESSION_USERID_KEY_NAME];
    }

    /**
     * 用户名称
     */
    public function getUserName() {
        $info = $this->getLoginInfo();

        return $info[self::SESSION_USER_KEY_NAME][self::SESSION_USERNAME_KEY_NAME];
    }

    // 用户角色
    public function getUserRole() {
        $info = $this->getLoginInfo();

        return $info[self::SESSION_USER_KEY_NAME][self::SESSION_USERROLE_KEY_NAME];
    }

    /**
     * 用户名称
     */
    public function getPosName() {
        $info = $this->getLoginInfo();

        return $info[self::SESSION_USER_KEY_NAME][self::SESSION_POSNAME_KEY_NAME];
    }

    /**
     * 终端号
     */
    public function getPosId() {
        $info = $this->getLoginInfo();

        return $info[self::SESSION_USER_KEY_NAME][self::SESSION_POSID_KEY_NAME];
    }

    /**
     * 邮件地址
     */
    public function getEmail() {
        $info = $this->getLoginInfo();

        return $info[self::SESSION_USER_KEY_NAME][self::SESSION_MAILADDR_KEY_NAME];
    }

    /**
     * 主密钥
     */
    public function getMasterKey() {
        $info = $this->getLoginInfo();

        return $info[self::SESSION_USER_KEY_NAME][self::SESSION_MASTERKEY_KEY_NAME];
    }

    /**
     * 辅助密钥
     */
    public function getWorkKey() {
        $info = $this->getLoginInfo();

        return $info[self::SESSION_USER_KEY_NAME][self::SESSION_WORKKEY_KEY_NAME];
    }

    /**
     * 清空一次对账的信息
     */
    public function cleaSettleInf() {
        $this->setArg(self::SESSION_SETTLE_INF, array());
    }

    /**
     * 终端对账批次号 settle_batch
     */
    public function setSettleBatch($settle_batch) {
        $settle_inf                 = $this->getArg(self::SESSION_SETTLE_INF);
        $settle_inf['settle_batch'] = $settle_batch;
        $this->setArg(self::SESSION_SETTLE_INF, $settle_inf);
    }

    public function getSettleBatch() {
        $settle_inf = $this->getArg(self::SESSION_SETTLE_INF);

        return isset($settle_inf['settle_batch']) ? $settle_inf['settle_batch'] : '';
    }

    /**
     * 终端对账状态 settle_batch
     */
    public function setSettleStatus($settle_status) {
        $settle_inf                  = $this->getArg(self::SESSION_SETTLE_INF);
        $settle_inf['settle_status'] = $settle_status;
        $this->setArg(self::SESSION_SETTLE_INF, $settle_inf);
    }

    public function getSettleStatus() {
        $settle_inf = $this->getArg(self::SESSION_SETTLE_INF);

        return isset($settle_inf['settle_status']) ? $settle_inf['settle_status'] : '';
    }

    /**
     * 对账信息 settle_info
     */
    public function setSettleInfo($settle_info) {
        $settle_inf                = $this->getArg(self::SESSION_SETTLE_INF);
        $settle_inf['settle_info'] = $settle_info;
        $this->setArg(self::SESSION_SETTLE_INF, $settle_inf);
    }

    public function getSettleInfo() {
        $settle_inf = $this->getArg(self::SESSION_SETTLE_INF);

        return isset($settle_inf['settle_info']) ? $settle_inf['settle_info'] : '';
    }

    /**
     * 检查是否有登陆系统权限
     *
     * @param int $sysId 接入sso系统的系统id
     *
     * @return boolean
     */
    private function checkIsAllowLogin($sysId) {
        $info = $this->getLoginInfo();
        $data = $info[self::SESSION_USER_KEY_NAME];
        if (!is_array($data)) {
            return false;
        }

        return true;
    }

    /**
     * 将令牌写入cookie
     *
     * @param String  $token    令牌
     * @param boolean $isExpire 是否逾期（如果逾期，关闭浏览器立即失效）
     */
    private function putTokenToCookie($token, $isExpire = true) {
        if (!$isExpire) {
            $expireTime = time() + 60 * 60 * 24 * 30;
            setcookie(self::COOKIE_TOKEN_KEY_NAME, $token, $expireTime, "/");
        } else {
            setcookie(self::COOKIE_TOKEN_KEY_NAME, $token, 0, "/");
        } // print_r($_COOKIE);
        // die("SFDDF");
    }

    /**
     * 清除令牌
     */
    private function clearToken() {
        setcookie(self::COOKIE_TOKEN_KEY_NAME, '', -1, "/");
    }

    public function setArg($key, $val) {
        $this->session[$key] = $val;
    }

    public function getArg($key) {
        return $this->session[$key];
    }

    public function getSessionId() {
        return session_id();
    }
}

?>