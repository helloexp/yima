<?php
// +----------------------------------------------------------------------
// + 用户权限校验类
// + edit by tr
// +----------------------------------------------------------------------
class Auth {

    protected $userInfo = array();
    // 默认配置
    protected $_config = array(
        'AUTH_ON' => true,  // 认证开关
        'AUTH_TYPE' => 1,  // 认证方式，1为实时认证；2为登录认证。
        'AUTH_ADMIN_ROLE' => '2');
    // 机构管理员角色特殊号,该组可以设置所有权限
    
    public function __construct() {
        $prefix = C('DB_PREFIX');
        if (C('AUTH_CONFIG')) {
            // 可设置配置项 AUTH_CONFIG, 此配置项为数组。
            $this->_config = array_merge($this->_config, C('AUTH_CONFIG'));
        }
    }

    /**
     * 检查权限
     *
     * @param name string|array 需要验证的规则列表,支持逗号分隔的权限规则或索引数组
     * @param uid int 认证用户的id
     * @param string mode 执行check的模式
     * @param relation string 如果为 'or' 表示满足任一条规则即通过验证;如果为 'and'则表示需满足所有规则才能通过验证
     * @return boolean 通过验证返回true;失败返回false
     */
    public function check($name, $uid, $type = '0', $mode = 'url', $relation = 'or') {
        if (! $this->_config['AUTH_ON'])
            return true;
        $authList = $this->getAuthList($uid, $type); // 获取用户需要验证的所有有效规则列表
        if (is_string($name)) {
            $name = strtolower($name);
            if (strpos($name, ',') !== false) {
                $name = explode(',', $name);
            } else {
                $name = array(
                    $name);
            }
        }
        $list = array(); // 保存验证通过的规则名
        if ($mode == 'url') {
            $REQUEST = unserialize(strtolower(serialize($_REQUEST)));
        }
        foreach ($authList as $_auth) {
            // 如果是数组
            $condition = '';
            $auth = $_auth;
            if (is_array($_auth)) {
                $auth = $_auth['name'];
                $condition = $_auth['condition'];
            }
            $query = preg_replace('/^.+\?/U', '', $auth);
            if ($mode == 'url' && $query != $auth) {
                parse_str($query, $param); // 解析规则中的param
                $intersect = array_intersect_assoc($REQUEST, $param);
                $auth = preg_replace('/\?.*$/U', '', $auth);
                if (in_array($auth, $name) && $intersect == $param) { // 如果节点相符且url参数满足
                    $list[] = $auth;
                } elseif ((strpos($auth, '*') != false) && $intersect == $param) {
                    // 正则匹配
                    foreach ($name as $_name) {
                        if ($this->checkReg($auth, $_name)) {
                            $list[] = $auth;
                            break;
                        }
                    }
                }
            } // 如果有全部的，则要校验是否在免校验名单中
elseif (strpos($auth, '*') != false) {
                foreach ($name as $_name) {
                    // 条件匹配
                    if (! $this->checkCondition($auth, $_name, $condition)) {
                        continue;
                    }
                    // 正则匹配
                    if ($this->checkReg($auth, $_name)) {
                        $list[] = $auth;
                        break;
                    }
                }
            } elseif (in_array($auth, $name)) {
                $list[] = $auth;
            }
        }
        if ($relation == 'or' and ! empty($list)) {
            return true;
        }
        $diff = array_diff($name, $list);
        if ($relation == 'and' and empty($diff)) {
            return true;
        }
        return false;
    }

    /**
     * 根据用户id获取用户组,返回值为数组
     *
     * @param uid int 用户id
     * @return array 用户所属的用户组 array(
     *         array('uid'=>'用户id','group_id'=>'用户组id','title'=>'用户组名称','rules'=>'用户组拥有的规则id,多个,号隔开'),
     *         ...)
     */
    public function getGroups($uid) {
        static $groups = array();
        if (isset($groups[$uid]))
            return array($groups[$uid]);
        $user_groups = M()->table('tuser_info a')
            ->join("tauth_user_role g on g.id=a.new_role_id")
            ->where("a.user_id='$uid'")
            ->field('a.new_role_id role_id,g.powers')
            ->find();
        // 查询用户组,机构组
        /*
         * if($user_groups && $user_groups['role_id']){ $user_groups['powers'] =
         * M('tauth_role_power') ->where(array(
         * 'role_id'=>$user_groups['role_id'] )) ->getField("group_concat(id)
         * ids"); }
         */
        $groups[$uid] = $user_groups ? $user_groups : array();
        return $groups[$uid] ? array(
            $groups[$uid]) : array();
    }

    /**
     * 获得权限列表
     *
     * @param integer $uid 用户id
     * @param integer $level
     */
    protected function getAuthList($uid, $level) {
        static $_authList = array(); // 保存用户验证通过的权限列表
        $_authList = array();
        $t = implode(',', (array) $level);
        if (isset($_authList[$uid . $t])) {
            return $_authList[$uid . $t];
        }
        if ($this->_config['AUTH_TYPE'] == 2 &&
             isset($_SESSION['_AUTH_LIST_' . $uid . $t])) {
            return $_SESSION['_AUTH_LIST_' . $uid . $t];
        }
        $powers = array();
        // 读取系统默认忽略的权限
        $powersDefault = M('tauth_power')->where(
            array(
                'status' => 2))
            ->field('condition,name')
            ->select();
        do {
            // 读取机构所属权限组
            $nodePowers = $this->getNodePowers($uid);
            if (! $nodePowers) {
                break;
            }
            // 读取用户所属用户组
            $groups = $this->getGroups($uid);
            $ids = array(); // 保存用户所属用户组设置的所有权限规则id
            $is_admin = false;
            foreach ($groups as $g) {
                if (is_array($g)) {
                    if (!isset($g['powers'])) {
                        $g['powers'] = '';
                    }
                    if (!isset($g['role_id'])) {
                        $g['role_id'] = '';
                    }
                    $ids = array_merge($ids, explode(',', trim($g['powers'], ',')));
                    if ($g['role_id'] == $this->_config['AUTH_ADMIN_ROLE']) {
                        $is_admin = true;
                    }
                }
            }
            // 如果是管理员
            if ($is_admin) {
                $ids = $nodePowers;
            }  // 取交集
else {
                $ids = array_intersect($ids, $nodePowers); // 交集
            }
            // 获取所有相关联的ID
            if ($ids) {
                $replationIds = M('tauth_power')->where(
                    array(
                        'parent_id' => array(
                            'in', 
                            $ids)))->select();
                $replationIds = array_valtokey($replationIds, 0, 'id');
                $ids = array_merge($ids, $replationIds);
            }
            
            $ids = array_unique($ids);
            if (empty($ids)) {
                break;
            }
            
            $map = array(
                'id' => array(
                    'in', 
                    $ids), 
                'level' => $level, 
                'status' => 1);
            // 获取默认权限
            // 读取用户组所有权限规则
            $powers = M()->table('tauth_power')
                ->where($map)
                ->field('condition,name')
                ->select() or $powers = array();
        }
        while (0);
        
        // 合并默认权限和用户权限
        if ($powersDefault) {
            $powers = array_merge($powers, $powersDefault);
        }
        
        // 循环规则，判断结果。
        $authList = array(); //
        foreach ($powers as $rule) {
            if (! empty($rule['condition'])) { // 根据condition进行验证
                $authList[] = array(
                    'name' => strtolower($rule['name']), 
                    'condition' => $rule['condition']);
            } else {
                // 只要存在就记录
                $authList[] = strtolower($rule['name']);
            }
        }
        $_authList[$uid . $t] = $authList;
        if ($this->_config['AUTH_TYPE'] == 2) {
            // 规则列表结果保存到session
            $_SESSION['_AUTH_LIST_' . $uid . $t] = $authList;
        }
        return array_unique($authList);
    }

    /**
     * 获得用户资料,根据自己的情况读取数据库
     */
    public function getUserInfo($uid) {
        $userInfo = & $this->userInfo;
        if (! isset($userInfo[$uid])) {
            $userInfo[$uid] = M()->where(
                array(
                    'id' => $uid))
                ->table($this->_config['AUTH_USER'])
                ->find();
        }
        return $userInfo[$uid];
    }

    /**
     * 设置用户资料
     */
    public function setUserInfo($uid, $info) {
        $userInfo = & $this->userInfo;
        $userInfo[$uid] = $info;
        return $userInfo;
    }
    
    // 权限映射校验
    public function checkAccessMap($_accessMap = null, $uid) {
        // 权限映射校验
        $_accessMapFlag = false;
        if (isset($_accessMap) && isset($_accessMap[ACTION_NAME])) {
            $accessAction = $_accessMap[ACTION_NAME];
            $accessAction = explode(',', $accessAction);
            foreach ($accessAction as $v) {
                $v = explode('/', $v);
                if (! isset($v[1])) {
                    $v = array(
                        GROUP_NAME, 
                        MODULE_NAME, 
                        $v[0]);
                }
                if ($this->check(implode('/', $v), $uid)) {
                    $_accessMapFlag = true;
                    break;
                }
            }
        }
        return $_accessMapFlag;
    }

    public function checkReg($rule, $path) {
        $rule = Addcslashes($rule, '^$().[]|?+-\{}\'"/'); // 除了*号，转义正则里所有保留字符
        $rule = str_replace("*", "[^\/]*", $rule); // 将 * 号变成 .*
        return preg_match("/^" . $rule . "$/isU", $path);
    }
    
    // 校验条件
    public function checkCondition($rule, $path, $conition) {
        if (! $conition) {
            return true;
        }
        $isBlack = false;
        if (strpos($conition, '!') === 0) {
            $isBlack = true;
            $conition = substr($conition, 1);
        }
        
        $arr = explode(',', $conition);
        // 把 a/b/* 转成 a/b/$v
        foreach ($arr as $v) {
            $new_rule = str_replace('*', $v, $rule);
            // 黑名单
            if ($isBlack) {
                if ($new_rule == $path) {
                    return false;
                }
            }  // 白名单
else {
                if ($new_rule == $path) {
                    return true;
                }
            }
        }
        return $isBlack;
    }
    
    // 获取机构权限
    public function getNodePowers($uid) {
        $userInfo = & $this->userInfo;
        $info = $userInfo[$uid];
        $ids = array();
        // 获取模块权限及非标权限
        $nodePowers = M('tauth_node_power')->getFieldByNode_id($info['node_id'], 
            'powers');
        $fbPowers = trim($nodePowers, ',');
        $modulePowers = trim($info['pay_module'], ',');
        
        // 获取机构权限，
        $role_alias = $info['wc_version'];
        if (in_array($role_alias, 
            array(
                'v0', 
                'v0.5'))) {
            $PowersRet = M('tauth_node_role')->getFieldByAlias($role_alias, 
                'powers');
            $Powers = trim($PowersRet, ',');
            if ($Powers) {
                $ids = array_unique(explode(',', $Powers));
            }
        } elseif ($role_alias == 'v9') {
            $modulePowers .= ',v0.5'; // v9权限和v0.5一样，只是个付费标志而已
            $moduleRet = M('tauth_node_role')->field('powers')
                ->where(
                array(
                    'alias' => array(
                        'in', 
                        $modulePowers)))
                ->select();
            $idsTmp = '';
            if (! empty($moduleRet)) {
                foreach ($moduleRet as $k => $v) {
                    if (trim($v['powers'], ',')) {
                        $idsTmp .= ',' . trim($v['powers'], ',');
                    }
                }
            }
            $ids = array_unique(explode(',', trim($idsTmp, ',')));
        }
        
        if (! empty($fbPowers)) {
            $ids2 = explode(',', $fbPowers);
            $ids = array_merge($ids, $ids2);
            $ids = array_unique($ids);
        }
        return $ids;
    }
    
    // 处理条件
    public function handleCondition($name, $rule) {
        return true;
    }
}
