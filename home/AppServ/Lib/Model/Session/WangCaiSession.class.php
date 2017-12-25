<?php

/**
 * Session管理类，用于SSO session的管理和验证
 *
 * @author cxz
 */
class WangCaiSession extends Session {

    /**
     * session名称
     *
     * @var String
     */
    Const SESSION_NAME = "WangCaiSession";

    /**
     * session信息 用户信息键名
     *
     * @var string
     */
    Const SESSION_USER_KEY_NAME = "user_info";

    /**
     * SSO用户号键名
     *
     * @var string
     */
    Const SESSION_USER_ID_KEY_NAME = "USER_ID";

    /**
     * SSO用户机构号键名
     *
     * @var string
     */
    Const SESSION_NODE_ID_KEY_NAME = "NODE_ID";

    /**
     * SSO用户名键名
     *
     * @var string
     */
    Const SESSION_USER_NAME_KEY_NAME = "USER_NAME";

    /**
     * SSO真实名键名
     *
     * @var string
     */
    Const SESSION_REAL_USER_NAME_KEY_NAME = "REAL_USER_NAME";

    /**
     * session是否是后台管理员键名
     *
     * @var string
     */
    Const SESSION_ADMIN_USER_FLAG_KEY_NAME = "ADMIN_USER_FLAG";

    /**
     * 构造函数
     *
     * @param string $sessionName session名称 不设置为默认值
     */
    public function __construct($sessionName = self::SESSION_NAME) {
        parent::__construct($sessionName);
    }

    /**
     * 获取用户信息 return array 用户信息
     */
    public function getUserInfo() {
        $nodeId   = $this->getNodeId();
        $userId   = $this->getUserId();
        $userName = $this->getUserName();
        $name     = $this->getRealUserName();

        if ($nodeId == '' || $userId == '' || $userName == '') {
            $data = array();
        } else {
            $data = array(
                    self::SESSION_NODE_ID_KEY_NAME        => $nodeId,
                    self::SESSION_USER_ID_KEY_NAME        => $userId,
                    self::SESSION_USER_NAME_KEY_NAME      => $userName,
                    self::SESSION_REAL_USER_NAME_KEY_NAME => $name,
            );
        }

        return $data;
    }

    /**
     * 设置用户ID
     *
     * @param $userId 用户ID
     */
    public function setUserId($userId) {
        $this->setArg(self::SESSION_USER_ID_KEY_NAME, $userId);
    }

    /**
     * 获取用户ID
     */
    public function getUserId() {
        $userId = $this->getArg(self::SESSION_USER_ID_KEY_NAME);

        return $userId;
    }

    /**
     * 设置机构ID
     *
     * @param $nodeId 用户ID
     */
    public function setNodeId($nodeId) {
        $this->setArg(self::SESSION_NODE_ID_KEY_NAME, $nodeId);
    }

    /**
     * 获取机构ID
     */
    public function getNodeId() {
        $nodeId = $this->getArg(self::SESSION_NODE_ID_KEY_NAME);

        return $nodeId;
    }

    /**
     * 设置用户名
     *
     * @param $userName 用户名
     */
    public function setUserName($userName) {
        $this->setArg(self::SESSION_USER_NAME_KEY_NAME, $userName);
    }

    /**
     * 获取用户名
     */
    public function getUserName() {
        $userName = $this->getArg(self::SESSION_USER_NAME_KEY_NAME);

        return $userName;
    }

    /**
     * 设置真实用户名
     *
     * @param $name 用户名
     */
    public function setRealUserName($name) {
        $this->setArg(self::SESSION_REAL_USER_NAME_KEY_NAME, $name);
    }

    /**
     * 获取真实用户名
     */
    public function getRealUserName() {
        $name = $this->getArg(self::SESSION_REAL_USER_NAME_KEY_NAME);

        return $name;
    }

    /**
     * 设置后台管理员
     */
    public function setAdminUserFlag($flag = 1) {
        $this->setArg(self::SESSION_ADMIN_USER_FLAG_KEY_NAME, $flag);
    }

    /**
     * 是否后台管理员
     */
    public function isAdminUser() {
        $flag = $this->getArg(self::SESSION_ADMIN_USER_FLAG_KEY_NAME);

        // 是后台管理员
        if ($flag == 1) {
            return true;
        } else {
            return false;
        }
    }
}

?>