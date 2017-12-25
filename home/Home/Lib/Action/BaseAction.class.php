<?php

// 后台基础类
abstract class BaseAction extends Action
{
    public $pos_id = '';

    public $position = '';

    public $version = '';

    public $nodeMail = '';

    public $storeName = '';

    public $nodeId = '';

    public $node_id = '';

    public $userId = '';

    public $user_name = '';

    public $user_id = '';

    public $contractId = '';

    public $nodeType = '';

    public $payType = '';

    public $nodePath = '';

    public $nodeQsCheckStatus = '';

    public $clientId = '';

    public $topNodeFlag = false;

    public $isAdmin = false;

    public $isXiaoMei = false;

    public $node_type_name = '';

    public $userInfo = array();

    public $wc_version = '';

    public $_authAccessMap = null;
    // 权限映射表
    public $nodeInfo = array();

    public $new_role_id = '';

    /**
     * @var ChannelModel
     */
    private $ChannelModel;

    /**
     * @var WeiXinService
     */
    public $WeiXinService;

    /**
     * @var DoubleFestivalService
     */
    public $DoubleFestivalService;

    protected $needCheckLogin = true;

    protected $checkLoginReturn = false;

    protected $needCheckUserPower = true;
    public $hideGetCash='';//是否显示左侧的提现列表

    /**
     * @var WeiXinCardService
     */
    protected $WeiXinCardService;

    public $wxSess = array();// 微信信息
    /**
     * @var CheckStatus
     */
    public $checkStatusObj;

    /**
     * @var RedisHelper
     */
    protected $RedisHelper;

    public function debugWechat()
    {
        if (isset($_GET['_debug_wx_']) && $_GET['_debug_wx_'] == 'test' ||
            (isset($_SESSION['_debug_wx_']) && $_SESSION['_debug_wx_'] == 'test')) {
            // todo
            $_SERVER['HTTP_USER_AGENT'] = 'MicroMessenger'; // 模拟微信
            session('_debug_wx_', 'test');
            $this->forTestWechatLogin();
        }
    }

    public function _initialize()
    {
        $this->debugWechat();

        // 校验敏感词
        if ($this->isPost() && C('CHECK_FUCK_WORD')) {
            $result = has_fuck_word();
            if ($result) {
                $this->error('输入内容含有敏感词【'.implode(',', $result).'】');
            }
        }


        $sid = I('get.sid', '0', 'string');//APP访问
        if($sid){
            $json = file_get_contents(C('USER_URL') . $sid);
            $result = (get_magic_quotes_gpc()) ? stripcslashes($json) : $json;
            $userinfo = json_decode($result, TRUE);

            if ($userinfo['retCode'] != 0000) {
                header('Location: ' . C('INDEX_URL'));
            }

            $this->nodeId = $userinfo['loginInfo']['user_info']['NODE_ID'];
            $this->node_id = $userinfo['loginInfo']['user_info']['NODE_ID'];
            $this->userId = $userinfo['loginInfo']['user_info']['USER_ID'];
            $this->user_id = $userinfo['loginInfo']['user_info']['USER_ID'];
            $this->pos_id = $userinfo['loginInfo']['user_info']['POS_ID'];
            $this->nodeMail = $userinfo['loginInfo']['user_info']['MAIL_ADDR'];
            $this->storeName = $userinfo['loginInfo']['user_info']['STORE_NAME'];
            $this->position = $userinfo['loginInfo']['user_info']['USER_NAME'] == '管理员' ? true : false;
            $this->nodeInfo = $nodeInfo = get_node_info($this->nodeId);
            $this->version = $nodeInfo['app_pay_flag'] == '1' ? true : false;

            $_SESSION['APP']['node_id'] = $this->node_id;
            $_SESSION['APP']['user_id'] = $this->user_id;
            $_SESSION['APP']['pos_id'] = $this->pos_id;
            $_SESSION['APP']['nodeMail'] = $this->nodeMail;
            $_SESSION['APP']['storeName'] = $this->storeName;
            $_SESSION['APP']['position'] = $this->position;
            $_SESSION['APP']['version'] = $this->version;
        }else{
            $this->nodeId = $_SESSION['APP']['node_id'];
            $this->node_id = $_SESSION['APP']['node_id'];
            $this->userId = $_SESSION['APP']['user_id'];
            $this->user_id = $_SESSION['APP']['user_id'];
            $this->pos_id = $_SESSION['APP']['pos_id'];
            $this->nodeMail = $_SESSION['APP']['nodeMail'];
            $this->storeName = $_SESSION['APP']['storeName'];
            $this->position = $_SESSION['APP']['position'];
            $this->version = $_SESSION['APP']['version'];
            if($this->nodeId){
                $this->nodeInfo = get_node_info($this->nodeId);
            }
        }

        if(!$this->nodeInfo){

        // 判断是否登录
        if ($this->needCheckLogin) {
            $this->_checkLogin($this->checkLoginReturn);
        }

        // 获取node_id,user_id
        $userService = D('UserSess', 'Service');
        $userInfo = $this->userInfo = $userService->getUserInfo();

        // dump(session('userSessInfo'));exit;
        $this->nodeId = $userInfo['node_id'];
        $this->node_id = $userInfo['node_id'];
        $this->userId = $userInfo['user_id'];
        $this->user_id = $userInfo['user_id'];
        $this->user_name = $userInfo['user_name'];
        $this->nodeInfo = $nodeInfo = get_node_info($this->nodeId);
        if(get_val($this->nodeInfo['cfg_data'], 'hideGetCash',0)==1){
             $this->assign('hideGetCash','1');
             $this->hideGetCash=1;
        }
        $this->wc_version = $nodeInfo['wc_version'];
        if ($this->node_id === C('yhb.node_id')) {
            if (GROUP_NAME != 'Yhb' &&
                !in_array(GROUP_NAME.'-'.MODULE_NAME, C('yhb.allow_gm')) &&
                !in_array(GROUP_NAME, C('yhb.allow_gm'))) {
                $this->redirect(U('Yhb/Index/index'));
            }
        }
        // 统计常用模块
        D('ClickSum')->insertPopular($this->nodeId);
        // 积分名称获取
        integralSetName($this->node_id);

        // 黑名单校验
        if ($nodeInfo['status_tips'] == '2' || $nodeInfo['status_tips'] == '3') {
            $this->error('该用户已被加入黑名单,如有疑问,请致电客服热线400-8827770');
        }
        if ($this->node_id === C('GpEye.node_id')) {
            $userInfo['merchant_id'] = M('tuser_info')->where(array('user_id' => $this->userId, 'node_id' => $this->node_id))->getField('merchant_id');
            $status = M('tfb_gp_merchant')->where(array('id' => $userInfo['merchant_id']))->getField('status');
            if ($status != 0) {
                $this->error('商户状态异常。');
            }
        }
        // 获取商户结算号,商户类型
        $userInfo = array_merge($userInfo, $nodeInfo);
        $this->contractId = $nodeInfo['contract_no'];
        $this->nodeType = $nodeInfo['node_type'];
        $this->payType = $nodeInfo['pay_type'];
        $this->nodePath = $nodeInfo['full_id'];
        $this->nodeQsCheckStatus = $nodeInfo['check_status'];
        $this->user_phones = $nodeInfo['contact_phone'];
        $this->clientId = $nodeInfo['client_id'];
        $this->topNodeFlag = $this->checkNodeLevel();
        $this->userInfo = $userInfo;
        //将用户信息存入redis中
        import('@.Vendor.RedisHelper');
        $this->redis = RedisHelper::getInstance();
        $this->redis->set('curlUserInfo:'.$this->node_id, $userInfo);

        // 判断用户类型
        if ($nodeInfo['wc_version'] == 'v0') {
            // C0是注册没有认证的
            $this->node_type_name = 'c0';
        } elseif ($nodeInfo['wc_version'] == 'v0.5') {
            // C1是认证没有付费的
            $this->node_type_name = 'c1';
        } elseif ($nodeInfo['wc_version'] == 'v9') {
            // C2是付费的用户
            $this->node_type_name = 'c2';
        } elseif ($nodeInfo['wc_version'] == 'v4') {
            // 演示账号
            $this->node_type_name = 'staff';
        }
        $this->assign('userInfo', $userInfo);
        // 校验用户权限
        if ($this->needCheckUserPower) {
            $this->checkUserPower($userInfo);
        }

        // 检查快捷菜单子菜单多宝电商，条码支付
        $this->assign('ispowero2o',
            $this->_checkUserAuth('Ecshop/Index/preview'));
        $this->assign('ispoweralipy',
            $this->_checkUserAuth('Alipay/Index/index'));
        if ($this->wc_version == 'v4') {
            $this->assign('iswfx', true);
        } else {
            $this->assign('iswfx', $this->_checkUserAuth('Wfx/Static/index'));
        }
        $this->batchFrom(I('batch_come_from'));

        $this->isAdmin = $this->checkUserLevel();

        $this->assign('topNodeFlag', $this->topNodeFlag);
        $this->assign('isAdmin', $this->isAdmin);

        // 消息数量
        $tmessage_feedback = M('tmessage_feedback')->where(
            array(
                'node_id' => "{$this->nodeId}",
                'reply_time' => array(
                    'exp',
                    'is not null', ),
                'status' => '0', ))->count('id');
        if (!isset($tmessage_recored)) {
            $tmessage_recored = 0;
        }
        $tmessage_feedback = (int) $tmessage_feedback;
        $this->assign('tmessage_recored', $tmessage_recored);
        $this->assign('tmessage_feedback', $tmessage_feedback);
        $this->assign('messageSum', $tmessage_recored + $tmessage_feedback);
        $this->assign('node_type_name', $this->node_type_name);

        $this->assign('nodeType', $this->nodeType);
        $this->assign('token', $userInfo['token']);
        $this->assign('iswfx', $this->_checkUserAuth('Wfx/Static/index'));
        // 查询最新消息
        $msgArr = M()->table('tmessage_news a')
            ->field('a.id,a.title,b.seq_id,b.status')
            ->join('inner join tmessage_recored b on a.id=b.message_id ')
            ->where(
                array(
                    'b.node_id' => $this->nodeId,
                    'a.status' => '0', ))
            ->order('b.status,b.add_time desc')
            ->limit(3)
            ->select();

        // 查询各种新消息的数量
        $msgArrcount = M()->table('tmessage_stat a')
            ->field('new_message_cnt,message_type')
            ->where(array(
                'a.node_id' => $this->nodeId, ))
            ->where('a.new_message_cnt > 0')
            ->order('message_type asc')
            ->select();

        $this->assign('msgArrcount', $msgArrcount);

        // 判断是否有未读消息
        $Model = new Model();
        $unReadMsg = $Model->query(
            "SELECT SUM(new_message_cnt) FROM tmessage_stat WHERE node_id = '$this->nodeId'");
        $unReadMsg = $unReadMsg[0]['SUM(new_message_cnt)'];

        tag('function_switch', $this);
        // 双旦 start
        tag('double_festival', $this);
        // //双旦 end
        $this->unReadMsg = $unReadMsg;
        $this->msgArr = $msgArr;
        $this->assign('unReadMsg', $unReadMsg);
        $this->assign('msgArr', $msgArr);

        $this->assign('wc_version', $this->wc_version);
        $this->assign('is_cnpc_gx',
            ($this->nodeId == C('cnpc_gx.node_id')) ? 1 : 0);
        $this->assign('is_zggk', ($this->nodeId == C('zggk.node_id')) ? 1 : 0);
        $this->assign('is_adb', ($this->nodeId == C('adb.node_id')) ? 1 : 0);
        if ($this->node_id === C('GpEye.node_id') && $this->new_role_id != 2) {
            if (GROUP_NAME != 'GpEye') {
                $this->redirect(U('GpEye/Treatment/index'));
            }
        }

        }
    }

    public function setDoubleFestivalData($data)
    {
        foreach ($data as $index => $item) {
            $this->assign($index, $index);
        }
    }

    // 用户中心新消息tmessage_stat+1
    /**
     * @data 新增的消息数 @where 条件 node_id,message_type Enter description here .
     * ..
     *
     * @param unknown_type $where
     */
    // 验证码图片
    public function verifyImage()
    {
        $verifyName = $this->_param('verifyName', '', 'verify');

//        import("ORG.Util.Image");
        //        Image::buildImageVerify(4, 1, 'png', 48, 22, $verifyName);

        import('@.Service.ImageVerifyService');
        ImageVerifyService::buildImageCodeByParam($verifyName);
    }

    /* 校验验证码 */
    public function _checkVerifyCode($checkCode, $verifyName = 'verify')
    {
        if ($checkCode == '' || $checkCode != session($verifyName)) {
            return false;
        } else {
            return true;
        }
    }

    public function checkLoginByApi()
    {
        $return = [
            'code' => 0,
            'msg' => null,
            'data' => $_SESSION['userSessInfo'],
        ];
        exit(json_encode($return, JSON_UNESCAPED_UNICODE));
    }

    /* 校验是否登录 */
    public function _checkLogin($return = false)
    {
        $user = D('UserSess', 'Service');
        $token = $this->_get('token');
        $resp = $user->loginByToken($token);
        if (!$user->isLogin(true)) {
            if ($return) {
                return false;
            } else {
                if (IS_GET) {
                    session('fromurl',
                        U(GROUP_NAME.'/'.MODULE_NAME.'/'.ACTION_NAME,
                            I('get.')));
                }
                if (I('IS_AJAX', 0, 'intval') == 1) {
                    $data = array(
                        'info' => '您尚未登录或登录已超时：'.$user->getErrorMsg(),
                        'status' => 1,
                        'rcode' => '900',
                        'url' => array(
                            '请立即登录' => 'javascript:openlogin();', ), );
                    $this->ajaxReturn($data, 'json');
                } else {
                    $this->error('您尚未登录或登录已超时：'.$user->getErrorMsg(),
                        array(
                            '请立即登录' => 'javascript:openlogin();', ),
                        array(
                            'rcode' => '900', ));
                }
            }
        }

        return true;
    }

    // 重定义错误输出
    protected function error($message = '', $jumpUrl = '', $ajax = null)
    {
        if ($jumpUrl && !is_array($jumpUrl)) {
            $jumpUrl = array(
                '现在跳转' => $jumpUrl, );
        }
        if (is_null($jumpUrl)) {
            $jumpUrl = array(
                '返回' => 'javascript:history.go(-1)', );
        }
        $this->assign('jumpUrlList', $jumpUrl, $ajax);
        M()->rollback(); // 回滚事务
        if (is_array($message)) {
            if (isset($message['errorSoftTxt'])) {
                $message = $message['errorSoftTxt'];
            } else {
                if ((function_exists('is_production') && !is_production()) ||
                    (isset($_GET['_debug_']) && $_GET['_debug_'] == 'show_debug')) {
                    $message = var_export($message, 1);
                } else {
                    $message = '好像遇到了一点小问题，请您刷新页面重拾^_^！';
                }
            }
        }
        parent::error($message, $jumpUrl, $ajax);
    }

    /**
     * 显示错误信息.
     *
     * @author Jeff Liu
     *
     * @param int    $errno
     * @param bool   $isAjax
     * @param string $extraMsg
     */
    public function showErrorByErrno($errno, $isAjax = false, $extraMsg = '')
    {
        $messageInfo = $this->getMessageInfoByErrno($errno);
        $jumpUrl = '';
        if ($jumpUrl && !is_array($jumpUrl)) {
            $jumpUrl = array(
                '现在跳转' => $jumpUrl, );
        }
        if (is_null($jumpUrl)) {
            $jumpUrl = array(
                '返回' => 'javascript:history.go(-1)', );
        }
        $this->assign('jumpUrlList', $jumpUrl);
        M()->rollback(); // 回滚事务
        $msg = isset($messageInfo['errorSoftTxt']) ? $messageInfo['errorSoftTxt'] : '';
        if ($extraMsg) {
            $msg .= ':'.$extraMsg;
        }
        parent::error($msg, $jumpUrl, $isAjax);
    }

    /**
     * Ajax方式返回数据到客户端.
     *
     * @param mixed  $data 要返回的数据
     * @param string $type AJAX返回数据格式
     */
    protected function ajaxReturn($data, $type = '', $status = null)
    {
        if (func_num_args() == 1 && is_numeric($data) && $data < 0) {
            $data = $this->buildAjaxErrorReturnData($data);
            parent::ajaxReturn($data, $type);
        } elseif (func_num_args() == 3) {
            // 3.0的方式
            $args = func_get_args();
            $data = $args[0];
            $info = $args[1];
            $status = $args[2];
            parent::ajaxReturn($data, $info, $status);
        } else {
            parent::ajaxReturn($data, $type);
        }
    }

    /**
     * @author Jeff Liu<liuwy@iamgeco.com.cn>
     *
     * @param $errno
     *
     * @return array
     */
    protected function buildAjaxErrorReturnData($errno)
    {
        $messageInfo = $this->getMessageInfoByErrno($errno);
        $msg = isset($messageInfo['errorSoftTxt']) ? $messageInfo['errorSoftTxt'] : $errno;
        $data = array(
            'data' => 'error',
            'info' => $msg,
            'status' => $errno, );

        return $data;
    }

    /**
     * @param $info
     * @param int  $type    1: echo 2:echo json 3:file
     * @param bool $is_exit
     */
    protected static function _debug($info, $type = 1, $is_exit = false)
    {
        switch ($type) {
            case 1:
                echo '<pre>';
                var_export($info);
                echo '</pre>';
                break;
            case 2:
                echo json_encode($info);
                break;
            case 3:
                error_log(var_export($info, 1).PHP_EOL, 3, 'e:/tmp/log.log');
                break;
        }

        if ($is_exit) {
            exit();
        }
    }

    /**
     * 根据编号获得对一个tipsInfo.
     *
     * @author Jeff Liu
     *
     * @param $errno
     *
     * @return mixed
     */
    protected function getMessageInfoByErrno($errno)
    {
        import('@.Service.TipsInfoService');

        return TipsInfoService::getMessageInfoByErrno($errno);
    }

    // 重定义正确输出
    protected function success($message = '', $jumpUrl = '', $ajax = false)
    {
        if ($jumpUrl && !is_array($jumpUrl)) {
            $jumpUrl = array(
                '现在跳转' => $jumpUrl, );
        }
        if (is_null($jumpUrl)) {
            $jumpUrl = array(
                '返回' => 'javascript:history.go(-1)', );
        }
        $this->assign('jumpUrlList', $jumpUrl);
        parent::success($message, $jumpUrl, $ajax);
    }

    protected function nodeIn($nodeId = null, $sqlStr = false)
    {
        static $_node_full_id = array();
        if ($nodeId) {
            if (isset($_node_full_id[$nodeId])) {
                $path = $_node_full_id[$nodeId];
            } elseif ($nodeId == $this->node_id) {
                $path = $this->nodePath;
            } else {
                $path = get_node_info($nodeId, 'full_id');
                $_node_full_id[$nodeId] = $path;
            }
        } else {
            $path = $this->nodePath;
        }
        if (!$path) {
            return "'".$this->node_id."'";
        }
        $sql = "select node_id from tnode_info where full_id like '".$path.
            "%'";
        $limit = 10;
        $moreLimit = 11;
        $infos = M()->query($sql.' LIMIT '.$moreLimit);
        if (count($infos) > $limit || true === $sqlStr) {
            return $sql;
        } else {
            $tmp_array = array();
            foreach ($infos as $val) {
                $tmp_array[] = $val['node_id'];
            }

            return "'".implode("','", $tmp_array)."'";
        }
    }

    // 校验机构权限
    protected function checkNodePower()
    {
        $actionList = array(
            GROUP_NAME.'/'.MODULE_NAME.'/'.ACTION_NAME, // 操作控制
            GROUP_NAME.'/'.MODULE_NAME, // 模块控制
            GROUP_NAME, ); // 分组控制

        foreach ($actionList as $v) {
            $model = M('tnode_type_power');
            if ($this->nodeType == '2' || $this->nodeType == '5') {
                // 校验
                $map = array(
                    'type' => $this->nodeType,
                    'status' => '1', );
                $count = substr_count($v, '/');
                if ($count > 1) {
                    $map['action_list'] = array(
                        'like',
                        '%'.$v.'%', );
                } else {
                    $map['action_list'] = $v;
                }
                $result = $model->field('action_list,cate_str,power_name')->where(
                    $map)->select();
                if ($result) {
                    foreach ($result as $row) {
                        // 校验参数
                        if ($row['cate_str']) {
                            if (strpos($row['cate_str'], '=')) {
                                $str_arr = explode('=', $row['cate_str']);
                                $value = I('request.'.$str_arr[0]);
                                if ($value == $str_arr[1] && $value != '') {
                                    $this->assign('name', $row['power_name']);
                                    $this->display(
                                        APP_PATH.'/Tpl/Public/Public_open.html');
                                    exit();
                                }
                            }
                        } else {
                            $this->assign('name', $row['power_name']);
                            $this->display(
                                APP_PATH.'/Tpl/Public/Public_open.html');
                            exit();
                        }
                    }
                }
            }
        }

        return true;
    }
    // 用户权限校验
    protected function checkUserPower($userInfo)
    {
        // 没有权限时，提示语
        define('NORMAL_PERMISSION_ERROR', '对不起，您没有该功能的使用权限！');
        define('O2O_PERMISSION_ERROR', '尊敬的旺财用户，您未开通多宝电商服务模块，不能使用该功能！');
        define('CONFIRM_PERMISSION_ERROR', '该功能面向企业认证客户开放，请前去进行您的企业认证。');
        define('ACCOUNT_STOP_ERROR', '您账户已被停用或注销');

        // 停用和注销的商户不能访问
        if (in_array($userInfo['status'],
            array(
                '1',
                '2', ))) {
            $this->error(ACCOUNT_STOP_ERROR);
        }
        // 获取当前用户的角色权限的id，目前旺财平台只有new_role_id有效，role_id已舍弃
        // 00000000这种nodeid的用户，等同于免费版用户
        $newRoleId = M('tuser_info')->where(
            array(
                'user_id' => $this->userId,
                'node_id' => $this->node_id, ))->getField('new_role_id');
        if (empty($newRoleId) && $this->node_id != '00000000') {
            $this->error(NORMAL_PERMISSION_ERROR);
        }
        $this->new_role_id = $newRoleId;
        $this->assign('new_role_id', $this->new_role_id);

        // 提前校验权限
        if (method_exists($this, 'beforeCheckAuth')) {
            $this->beforeCheckAuth();
        }
        //校验系统权限
        do {
            //免校验系统权限
            if ($this->_authAccessMap == '*' || ($this->wc_version == 'v4')) {
                $this->_updatePowerClick();
                break;
            }
            // 系统权限校验
            if (D('UserSess', 'Service')->checkAuth('', '', $this->nodeInfo)) {
                $this->_updatePowerClick();
                break;
            } else {

                // 兼容老的电商权限，以后要运维掉
                $nodePower = M('tauth_node_power')->getFieldByNode_id(
                    $this->node_id, 'powers');
                $nodePowerArr = explode(',', trim($nodePower['powers'], ','));
                // 电商的提示单独设置
                if (GROUP_NAME == 'Ecshop' ||
                    (GROUP_NAME == 'LabelAdmin' && MODULE_NAME == 'OrderList')) {
                    $return_arr = array(
                        '返回电商首页' => U('Home/Index/marketingShow5'),
                        '在线开通' => U('Home/Wservice/ecommerceVersion'), );
                    $this->_handleCheckAuth(O2O_PERMISSION_ERROR, $return_arr);
                    break;
                }
                $this->error(NORMAL_PERMISSION_ERROR);
            }
        } while (true);

        // 通过后校验权限
        if (method_exists($this, 'afterCheckAuth')) {
            $this->afterCheckAuth();
        }

        return true;
    }

    /**
     * [hasPayModule 是否有付费模块权限] [strModule 逗号隔开的模块,如'm0,m1,m2'].
     *
     * @return bool [description]
     */
    protected function hasPayModule($strModule, $node_id = null)
    {
        if ($node_id) {
            $this->nodeInfo = get_node_info($node_id);
            $this->wc_version = $this->nodeInfo['wc_version'];
        }
        if ($this->wc_version == 'v4') {
            return true;
        }
        $strModule = trim($strModule, ',');
        if (empty($strModule)) {
            $this->error('hasMudlePower:参数不得为空');
        }
        $arrModule = explode(',', $strModule);
        $payModule = explode(',', trim($this->nodeInfo['pay_module'], ','));
        if (empty($payModule)) {
            return false;
        }
        foreach ($arrModule as $k => $v) {
            if (!in_array($v, $payModule)) {
                return false;
            }
        }

        return true;
    }

    public function hasPayModuleWithCache($strModule, $node_id) {
        $redisHelper = $this->getRedisInstance();
        $key = 'pay_module:'.$node_id.$strModule;
        $ret = $redisHelper->get($key);
        if ($ret == 1) {
            return true;
        } else if ($ret == -1) {
            return false;
        } else {
            $ret = $this->hasPayModule($strModule, $node_id);
            $cachedValue = $ret ? 1: -1;
            $redisHelper->set($key, $cachedValue);
            $redisHelper->expire($key, 3600);//缓存一个小时
        }
        return $ret;
    }

    /*
     * 更新被点击的权限数
     */
    protected function _updatePowerClick()
    {
        if (!$this->node_id) {
            return false;
        }
        // 获取当前模块ID
        $gma = GROUP_NAME.'/'.MODULE_NAME.'/'.ACTION_NAME;
        $info = M('tauth_power')->where(
            array(
                'name' => $gma,
                'level' => 1, ))->find();
        if (!$info) {
            return;
        }
        $mid = $info['id'];
        $dao = M('tbuss_visit_stat');
        $statInfo = $dao->where(
            array(
                'node_id' => $this->node_id,
                'auth_power_id' => $mid, ))->find();
        if (!$statInfo) {
            $result = $dao->add(
                array(
                    'node_id' => $this->node_id,
                    'auth_power_id' => $mid,
                    'visit_num' => 1, ));
        } else {
            $result = $dao->where(
                array(
                    'node_id' => $this->node_id,
                    'auth_power_id' => $mid, ))->setInc('visit_num');
        }

        return $result;
    }

    //
    protected function getNodeTree()
    {
        $nodeData = M('tnode_info')->field('node_id,parent_id,node_name')->where(
            "full_id LIKE '{$this->nodePath}%'")->select();
        $nodeData = $this->genCate($nodeData);

        return $nodeData;
    }

    // 树形结构
    protected function genCate($data, $pid = 0, $level = 0)
    {
        if ($level == 10) {
            return;
        }
        $l = str_repeat('&nbsp;&nbsp;&nbsp;', $level);
        // $l = $l.'└';
        static $arrcat = array();
        $arrcat = empty($level) ? array() : $arrcat;
        foreach ($data as $k => $row) {
            $row['parent_id'] = empty($row['parent_id']) ? 0 : $row['parent_id'];
            if ($row['parent_id'] === $pid) {
                // 如果父ID为当前传入的id，且不为空
                // 如果当前遍历的id不为空
                $row['node_name'] = $level == 0 ? $row['node_name'] : $l.
                    $row['node_name'];
                $row['level'] = $level;
                $arrcat[] = $row;
                // var_array($arr);
                $this->genCate($data, $row['node_id'], $level + 1); // 递归调用
            }
        }

        return $arrcat;
    }

    protected function checkNodeQs($inFrame = false, $return = false)
    {
        if ($this->nodeType == '2' && $this->nodeQsCheckStatus != '2') {
            if ($return) {
                return false;
            }

            $msg = '您还未上传营业执照或营业执照还未通过审核!';
            if ($this->isAjax()) {
                $this->error($msg);
            } else {
                $this->assign('error', $msg);
                if ($inFrame) {
                    $this->display(
                        APP_PATH.'/Tpl/Public/Public_msgArtdialog.html');
                } else {
                    $this->error($msg, U('Home/AccountInfo/Index'));
                }
                exit();
            }
        }

        return true;
    }

    // 判断当前商户是否是顶级商户
    protected function checkNodeLevel()
    {
        if (count(explode(',', $this->nodePath)) == 1) {
            return true;
        }

        return false;
    }

    // 判断当前用户是否是管理员
    protected function checkUserLevel($userInfo = array())
    {
        $userInfo = $userInfo ? $userInfo : $this->userInfo;
        // 如果当前userInfo中没有role_id和new_role_id，则查询这2个值
        if (!isset($userInfo['role_id']) && !isset($userInfo['new_role_id'])) {
            $roleInfo = M('tuser_info')->where(
                "user_id='{$userInfo['user_id']}' AND node_id='{$userInfo['node_id']}'")->field(
                'role_id,new_role_id')->find();
            $userInfo['role_id'] = $roleInfo['role_id'];
            $userInfo['new_role_id'] = $roleInfo['new_role_id'];
        }
        $role_id = isset($userInfo['role_id']) ? $userInfo['role_id'] : null;
        $new_role_id = isset($userInfo['new_role_id']) ? $userInfo['new_role_id'] : null;
        if ($new_role_id) {
            if ($new_role_id == '2') {
                return true;
            }
        } else {
            if ($role_id == '2') {
                return true;
            }
        }

        return false;
    }

    // 查询用户余额和流量
    public function getAccountInfo()
    {
        // 查询商户账户余额和流量
        $TransactionID = date('YmdHis').mt_rand(100000, 999999); // 请求单号
        // 商户余额流浪报文参数
        $req_array = array(
            'QueryShopInfoReq' => array(
                'SystemID' => C('YZ_SYSTEM_ID'),
                'NodeID' => $this->nodeId,
                'TransactionID' => $TransactionID,
                'ContractID' => $this->contractId, ), );
        $RemoteRequest = D('RemoteRequest', 'Service');
        $AccountInfo = $RemoteRequest->requestYzServ($req_array);

        if (!$AccountInfo || ($AccountInfo['Status']['StatusCode'] != '0000' &&
            $AccountInfo['Status']['StatusCode'] != '0001')) {
            $AccountInfo = array();
        }

        return $AccountInfo;
    }

    // 查询用户名旺币余额和流水 $type 1获取所有wb信息 2只获取wb余额
    public function getWbInfo($type = 1)
    {
        // 创建接口对象
        $RemoteRequest = D('RemoteRequest', 'Service');
        $TransactionID = date('YmdHis').mt_rand(100000, 999999); // 请求单号
        // 商户服务信息报文参数
        $req_array = array(
            'QueryWbListReq' => array(
                'SystemID' => C('YZ_SYSTEM_ID'),
                'TransactionID' => $TransactionID,
                'ClientID' => $this->clientId, ), );
        $nodeWbInfo = $RemoteRequest->requestYzServ($req_array);
        // dump($nodeWbInfo);
        $nodeWbList = array();
        $nodeWbList['wbOver'] = '0';
        if (!empty($nodeWbInfo['WbList'])) {
            if (isset($nodeWbInfo['WbList'][0])) {
                $nodeWbList['list'] = $nodeWbInfo['WbList'];
            } else {
                $nodeWbList['list'][] = $nodeWbInfo['WbList'];
            }
        }

        if (!empty($nodeWbList['list'])) {
            foreach ($nodeWbList['list'] as $k => $v) {
                if (date('YmdHis') > $v['EndTime']) {
                    $nodeWbList['list'][$k]['WbListId'] .= '(已过期)';
                } elseif (date('YmdHis') < $v['BeingTime']) {
                    $nodeWbList['list'][$k]['WbListId'] .= '(未开始)';
                } elseif ($v['Status'] == '-1') {
                    $nodeWbList['list'][$k]['WbListId'] .= '(已失效)';
                } else {
                    $nodeWbList['wbOver'] += $v['WbCurBalance'];
                }
            }
        }
        if ($type == 2) {
            unset($nodeWbList['list']);
        }

        return $nodeWbList;
    }
    // 活动来源
    protected function batchFrom($batch_from)
    {
        $config_arr = array(
            '1' => 'LabelAdmin/News/add',
            '2' => 'LabelAdmin/Coupon/add',
            '3' => 'LabelAdmin/Answers/add',
            '4' => 'LabelAdmin/Vote/add',
            '5' => 'LabelAdmin/List/index',
            '6' => 'Ecshop/MaShangMai/add',
            '7' => 'Ecshop/Index/index', );

        $batchOpt = GROUP_NAME.'/'.MODULE_NAME.'/'.ACTION_NAME;
        if (in_array($batchOpt, $config_arr)) {
            if (!empty($batch_from)) {
                if (!session('batch_from')) {
                    session('batch_from', $batch_from);
                }
            } else {
                if (!$this->isPost()) {
                    session('batch_from', null);
                }
            }
        }
    }

    public function _verifyReqData($rules, $return = false, $method = 'post',
        $value_array = array())
    {
        if (!is_array($rules)) {
            return;
        }
        $error = '';
        $req_data = array();
        foreach ($rules as $k => $v) {
            // $value = I($method . '.' . $k);
            if (isset($value_array[$k])) {
                $value = $value_array[$k];
            } else {
                $value = I($method.'.'.$k);
            }
            if (!check_str($value, $v, $error)) {
                $msg = $v['name'].$error;
                if ($return) {
                    return $msg;
                } else {
                    $this->error($msg);
                }
            }

            $req_data[$k] = $value;
        }

        return $req_data;
    }

    /*
     * public function _handleCheckAuth($msg,$return_arr=null){ if($return_arr)
     * $this->error($msg,$return_arr); else $this->error($msg); }
     */

    /*
     * 处理无权限时的错误提示 @auther :tr
     */
    public function _handleCheckAuth($msg, $return_arr = null)
    {
        if ($this->wc_version == 'v0' && $this->userInfo['new_role_id'] == '2') {
            $msg = '尊敬的旺财用户，您需要先通过企业资质认证。
            <br/>
            <a href="'.
            U('Home/AccountInfo/index',
                array(
                    'show_auth' => 'true', )).
            '" class="btn-all w110">上传企业资质</a>
            <p>
                <!--<img src="'.
            C('TMPL_PARSE_STRING.__PUBLIC__').'/Image/guid_auth.jpg"/>-->
            </p>
            ';
        }
        if ($return_arr) {
            $this->error($msg, $return_arr);
        } else {
            $this->error($msg);
        }
    }

    /**
     * 判断你是否是标准版.
     */
    public function _hasStandard($node_id = null)
    {
        if ($node_id) {
            $this->wc_version = get_node_info($node_id, 'wc_version');
        }
        if ($this->wc_version == 'v4') {
            return true;
        }
        if ($this->wc_version == 'v9') {
            return $this->hasPayModule('m1', $node_id);
        } else {
            return false;
        }
    }

    /**
     * 判断你是否有电商权限.
     */
    public function _hasEcshop($node_id = null)
    {
        if ($node_id) {
            $this->wc_version = get_node_info($node_id, 'wc_version');
        }
        if ($this->wc_version == 'v4') {
            return true;
        }
        if ($this->wc_version == 'v9') {
            return $this->hasPayModule('m2', $node_id);
        } else {
            return false;
        }
    }

    /**
     * 判断你是否有积分营销
     */
    public function _hasIntegral($node_id = null)
    {
        if ($node_id) {
            $this->wc_version = get_node_info($node_id, 'wc_version');
        }
        if ($this->wc_version == 'v4') {
            return true;
        }
        if ($this->wc_version == 'v9') {
            return $this->hasPayModule('m4', $node_id);
        } else {
            return false;
        }
    }

    /**
     * 判断你是否有中秋节权限.
     */
    public function _hasMoonDayEcshop($node_id = null)
    {
        return $this->wc_version == 'v0' ? false : true;
    }

    /**
     * 判读微信设置字段setting的存储.
     *
     * @param json  $setting  数据库setting字段
     * @param array $location 地址信息
     * @param array $msg      自动回复设置信息
     *
     * @return json $setting 存储setting
     */
    public function _setJson($setting = null, $location = null, $msg = null)
    {
        $defaultSetting = [
            'location' => [],
            'msg' => [],
        ];
        $setting = $setting ? json_decode($setting, true) : $defaultSetting;
        if ($location !== null) {
            $setting['location'] = $location;
        }

        if ($msg !== null) {
            $setting['msg'] = $msg;
        }
        /*
         * if('' == $setting){ if(null != $location && null == $msg){ $setting =
         * array('location' => $location); }elseif(null != $msg && null ==
         * $location){ $setting = array('msg' => $msg); } }else{ $setting =
         * json_decode($setting, true); if(array_key_exists("msg", $setting) &&
         * array_key_exists("location", $setting)){ if($location != null){
         * $setting = array_merge(array('msg' => $setting['msg']), $location); }
         * if($msg != null){ $setting = array_merge(array('location' =>
         * $setting['location']), array('msg' => $msg)); }
         * }elseif(array_key_exists("location", $setting) &&
         * !array_key_exists("msg", $setting)){ if($msg != null){ $setting =
         * array_merge($setting, array('msg' => $msg)); } if($location != null){
         * $setting = array('location' => $location); }
         * }elseif(array_key_exists("msg", $setting) &&
         * !array_key_exists("location", $setting)){ if($location != null){
         * $setting = array_merge($setting, $location); } if($msg != null){
         * $setting = array('msg' => $msg); } } }
         */
        return json_encode($setting);
    }

    public function _empty()
    {
        try {
            $content = $this->fetch();
            echo $content;
        } catch (Exception $e) {
            header('HTTP/1.0 404 Not Found');
            $this->display('../Public/404');
        }
    }

    // 活动下线告示
    public function offlineactivitynotice()
    {
        $this->error('该活动已下线');
        exit();
    }

    protected function responseJson($code, $msg, $data = null)
    {
        $resp = array(
            'code' => $code,
            'msg' => $msg,
            'data' => $data, );
        echo json_encode($resp);
        exit();
    }

    // 校验当前权限
    protected function _checkUserAuth($name)
    {
        $userService = D('UserSess', 'Service');
        $userInfo = $userService->getUserInfo();
        // 如果没有账户中心权限，则跳转到修改密码界面
        if (!$userService->checkAuth($name, $userInfo['user_id'],
            $this->nodeInfo)) {
            return false;
        }

        return true;
    }

    /**
     * @param $batchId
     * @param $batchType
     *
     * @return bool|mixed|string
     */
    protected function getLabelIdAndTryAddLabelInfo($batchId, $batchType)
    {
        $labelId = '';
        if ($batchId != '' && $batchType != '') {
            if (empty($this->ChannelModel)) {
                $this->ChannelModel = D('Channel');
            }
            $channelId = $this->ChannelModel->getChannelField(
                array(
                    'node_id' => $this->node_id,
                    'type' => $this->CHANNEL_TYPE,
                    'sns_type' => $this->CHANNEL_SNS_TYPE, ), 'id');
            if ($channelId) {
                // 所选链接为活动链接时,$batch_id&&$batch_type为空时表示是自定义链接
                $labelId = get_batch_channel($batchId, $channelId, $batchType,
                    $this->node_id);
            }
        }

        return $labelId;
    }

    /**
     * [sendMessage 给个人发送站内信].
     *
     * @param [type] $title   [站内信标题]
     * @param [type] $content [站内信内容]
     * @param [type] $nodeId  [发信对象]
     *
     * @return [type] [BOOL]
     */
    protected function sendMessage($title, $content, $nodeId = null)
    {
        if (!$nodeId) {
            $nodeId = $this->nodeId;
        }

        return D('Message')->send($title, $content, $nodeId);
    }

    /**
     * [runVDT 实例化校验类].
     *
     * @param [type] $VDT        [Widget\ValidateComponents下的文件]
     * @param [type] $selfHandle [true表示自己处理错误]
     * @param [type] $config     [参数，空表示自动获取参数]
     *
     * @return [type] [错误类对象] 例子:'LabelAdmin.SpringMonkeyVDT.setActBasicInfo'
     *                表示'Widget\ValidateComponents\LabelAdmin\SpringMonkeyVDT.class.php'中的setActBasicInfo方法
     */
    protected function runVDT($VDT, $selfHandle = false, $config = array())
    {
        list($root, $vdtClass, $runFunction) = explode('.', $VDT);
        if (empty($config) && I('request.')) {
            foreach (I('request.') as $key => $value) {
                $config[$key] = trim($value);
            }
        }

        import('@.Widget.ValidateComponents.BaseVDT');
        import("@.Widget.ValidateComponents.{$root}.{$vdtClass}");

        $vdt = new $vdtClass();

        $vdt->setNodeInfo($this->nodeInfo);

        $vdt->setConfig($config);

        $vdt->$runFunction();

        if ($selfHandle) {
            return $vdt->errorInfo;
        }
        if (!empty($vdt->errorInfo)) {
            $errorInfo = array_values($vdt->errorInfo);
            $this->error($errorInfo[0]);
        }
    }

    /**
     * 跳转到授权页面.
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param int    $nodeId           机构号
     * @param string $type             0:用户基础信息 1：用户完整信息
     * @param string $finalRedirectUrl 最终回调地址
     * @param string $apiCallbackUrl   微信授权callback地址
     */
    public function wechatAuthorizeByNodeId($nodeId, $type = '',
        $finalRedirectUrl = '', $apiCallbackUrl = '')
    {
        if (empty($type)) {
            $type = I('type', '0'); // 1是 基本信息
        }

        if (empty($apiCallbackUrl)) {
            $apiCallbackUrl = U('Label/MyBase/callbackAndRedirectByNodeId',
                array(
                    'id' => $this->id,
                    'type' => $type, ), '', '', true);
        }

        if (empty($finalRedirectUrl)) {
            $finalRedirectUrl = I('backurl', '', 'html_entity_decode');
        }

        if (empty($this->WeiXinService)) {
            $this->WeiXinService = D('WeiXin', 'Service');
        }

        $result = $this->WeiXinService->wechatAuthorizeAndRedirectByNodeId(
            $nodeId, $type, $finalRedirectUrl, $apiCallbackUrl);
        if (isset($result['errmsg']) && $result['errmsg'] != '') {
            $this->error($result['errmsg']);
        }
    }

    /**
     */
    public function callbackAndRedirectByNodeId()
    {
        log_write(print_r(I('get.'), true));
        $code = I('code', null);
        $type = I('type', '0');
        $nodeId = I('node_id', $this->node_id);
        if (empty($code)) {
            $this->error('参数错误！');
        }
        if (empty($this->WeiXinService)) {
            $this->WeiXinService = D('WeiXin', 'Service');
        }

        $callbackResult = $this->WeiXinService->callbackAndRedirectByNodeId(
            $code, $nodeId, $type);

        if (is_array($callbackResult) && isset($callbackResult['errmsg']) &&
            $callbackResult['errmsg']) {
            $this->error($callbackResult['errmsg']);
        }
    }

    /**
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param $nodeId
     *
     * @return mixed
     */
    public function getWechatUserInfo($nodeId)
    {
        if (empty($this->WeiXinService)) {
            $this->WeiXinService = D('WeiXin', 'Service');
        }

        return $this->WeiXinService->getWechatUserInfo($nodeId);
    }

    // 生成wap页分享的wxconfig配置数据
    protected function getWechatShareConfig($appId = '', $appSecret = '')
    {
        if (empty($appId)) {
            $appId = C('WEIXIN.appid');
        }
        if (empty($appSecret)) {
            $appSecret = C('WEIXIN.secret');
        }
        import('@.Vendor.JSSDK', '', '.php');
        $jssdk = new JSSDK($appId, $appSecret);
        $signPackage = $jssdk->getSignPackage();
        $config_arr = array(
            'appId' => $appId,
            'timestamp' => $signPackage['timestamp'],
            'nonceStr' => $signPackage['nonceStr'],
            'signature' => $signPackage['signature'],
        );

        return $config_arr;
    }

    /**
     * @param        $wechatShareConfig
     * @param string $marketInfo
     * @param string $linkUrl
     *
     * @return array
     */
    protected function generateWechatShareData($wechatShareConfig, $marketInfo = '', $linkUrl = '')
    {
        if (empty($linkUrl)) {
            $linkUrl = __URL__;
        }
        if (empty($marketInfo) && isset($this->marketInfo)) {
            $marketInfo = $this->marketInfo;
        }
        $configDataStr = isset($marketInfo['config_data']) ? $marketInfo['config_data'] : '';
        $configData = [];
        if ($configDataStr) {
            $configData = unserialize($marketInfo['config_data']);
        }
        if (isset($marketInfo['share_pic']) && $marketInfo['share_pic']) {
            $configData['share_pic'] = get_upload_url($marketInfo['share_pic']);
        }

        log_write(__METHOD__.'wechatShareDefaultInfo:'.var_export(C('wechatShareDefaultInfo'), 1));
        log_write(__METHOD__.'$configData:'.var_export($configData, 1));
        if (!isset($configData['share_title']) || empty($configData['share_title']) || !isset($configData['share_descript']) || empty($configData['share_descript']) || !isset($configData['share_pic']) || empty($configData['share_pic'])) {
            $wechatShareDefaultInfo = C('wechatShareDefaultInfo');
            $batchType = isset($marketInfo['batch_type']) ? $marketInfo['batch_type'] : 0;
            if (isset($wechatShareDefaultInfo[$batchType])) {
                $defaultConfigData = $wechatShareDefaultInfo[$batchType];
                if (!isset($configData['share_title']) || empty($configData['share_title'])) {
                    $configData['share_title'] = isset($defaultConfigData['share_title']) ? $defaultConfigData['share_title'] : '分享标题';
                }
                if (!isset($configData['share_descript']) || empty($configData['share_descript'])) {
                    $configData['share_descript'] = isset($defaultConfigData['share_descript']) ? $defaultConfigData['share_descript'] : '分享内容';
                }
                if (!isset($configData['share_pic']) || empty($configData['share_pic'])) {
                    $configData['share_pic'] = isset($defaultConfigData['share_pic']) ? $defaultConfigData['share_pic'] : '分享图片';
                }
            }
        }

        log_write(__METHOD__.'$configData before return:'.var_export($configData, 1));

        $wechatShareData = array(
            'config' => $wechatShareConfig,
            'link' => $linkUrl,
            'title' => isset($configData['share_title']) ? $configData['share_title'] : '',
            'desc' => isset($configData['share_descript']) ? $configData['share_descript'] : '',
            'imgUrl' => isset($configData['share_pic']) ? $configData['share_pic'] : '',
        );

        return $wechatShareData;
    }

    /**
     * @param        $appId
     * @param        $appSecret
     * @param string $marketInfo
     * @param string $linkUrl
     *
     * @return array
     */
    protected function generateWechatShareDataByAppIdAndSecret($appId, $appSecret, $marketInfo = '', $linkUrl = '')
    {
        $wechatShareConfig = D('WeiXin', 'Service')->getWxShareConfig('', $appId, $appSecret);

        return $this->generateWechatShareData($wechatShareConfig, $marketInfo, $linkUrl);
    }
    /**
     * 导出csv格式文件.
     *
     * @param   $file_name string      csv文件名
     * @param   $title     array()     csv头部标题
     * @param   $body      array()     csv内容部分
     */
    public function csv_lead($file_name, $header, $body)
    {
        header('Content-type: text/csv; charset=utf-8');
        header('Content-type:   application/octet-stream');
        header("Accept-Ranges:   bytes\r\n\r\n");
        header('Content-type:application/vnd.ms-excel');
        header('Content-Disposition:attachment;filename="'.$file_name.'.csv"');
        //头部显示文件
        $head_string = '';
        if ($header && is_array($header)) {
            foreach ($header as $key => $value) {
                $head_string .= $value.',';
            }
            $head_string = substr($head_string, 0, -1);
            $head_string .= "\n";
            $head_string = mb_convert_encoding($head_string, 'GBK', 'UTF-8');
            $head_keys = array_keys($header);
            echo $head_string;
        } else {
            die('头部标题不是数组');
        }
        //需要导出的数据部分
        if ($body && is_array($body)) {
            $string = '';
            foreach ($body as $field) {
                foreach ($head_keys as $key) {
                    $content = get_val($field, $key);
                    if ($content != null) {
                        if (is_numeric($content) && strlen($content) > 15) {
                            $content = "'".$content;
                        }
                        $string .= $content.',';
                    }
                }
                $string = substr($string, 0, -1);
                $string .= "\n";
            }
            $string = mb_convert_encoding($string, 'GBK', 'UTF-8');
            echo $string;
        } else {
            die('内容不是数组');
        }
        exit;
    }

    /**
     * 过滤重复请求（相同mobile，batch_id,node_id视为相同的请求）.
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param array  $params
     * @param string $prefix
     * @param bool   $isReturn
     *
     * @return mixed
     */
    public function filterDuplicateRequest($params = array(), $prefix = '', $isReturn = false)
    {
        import('@.Service.FilterDuplicateRequestService');
        $requestService = new FilterDuplicateRequestService();
        $ret = $requestService->filterDuplicateRequestBaseRedis($params, $prefix);
        if ($isReturn) {
            return $ret;
        }
        if ($ret === false) {
            $this->ajaxReturn('error', '请不要短时间内重复提交！', 0);
        }

        return true;
    }

    /**
     * 当前是否为预览渠道.
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param        $nodeId
     * @param string $id
     *
     * @return bool
     */
    public function isPreviewChannel($nodeId, $id = '')
    {
        if (empty($id)) {
            $id = $this->getId();
        }

        if (empty($id)) {
            $this->showErrorByErrno(-1002);
        }

        $redis = $this->getRedisInstance();
        $key = 'is_preview_channel:'.$id;
        $ret = $redis->get($key);
        if ($ret == 1) {
            return true;
        } else if ($ret == -1){
            return false;
        }

        if (empty($this->checkStatusObj)) {
            $this->checkStatusObj = new CheckStatus();
            $this->checkStatusObj->checkId($id);
        }
        if (empty($this->ChannelModel)) {
            $this->ChannelModel = D('Channel');
        }
        $previewChannelId = $this->ChannelModel->getPreviewChannelId($nodeId);
        $batchChannelInfo = $this->checkStatusObj->batchChannelInfo;
        $channelId = isset($batchChannelInfo['channel_id']) ? $batchChannelInfo['channel_id'] : 0;
        $previewChannelId = (int) $previewChannelId;
        $channelId = (int) $channelId;
        if ($previewChannelId === $channelId) {
            $redis->set($key, 1);
            $redis->expire($key, 604800);//缓存一周
            return true;
        }
        $redis->set($key, -1);
        $redis->expire($key, 604800);//缓存一周

        return false;
    }

    public function getId()
    {
        // 标签
        $id = I('get.id', I('post.id'), 'trim');
        if (empty($id)) {
            $id = session('id');
        }

        return $id;
    }

    public function setApi()
    {
        import('@.ORG.Api.Api');
    }

    /**
     * 获取微信登录用户信息
     *
     * @return mixed
     */
    public function getWxUserInfo()
    {
        if (empty($this->WeiXinCardService)) {
            $this->WeiXinCardService = D('WeiXinCard', 'Service');
        }

        return $this->WeiXinCardService->getWxUserInfo($this->node_id);
    }

    /**
     * @return mixed|string
     */
    public function getPersonId()
    {
        if (empty($this->wxSess)) {
            $wxUserInfo = $this->getWxUserInfo();
        } else {
            $wxUserInfo = $this->wxSess;
        }

        if ($wxUserInfo) {
            $return = $wxUserInfo['openid'];
        } else {
            $return = I('post.mobile', I('post.phone_no', I('post.phone')));
        }
        if (empty($return)) {
            $return = session_id();
        }
        return $return;
    }

    /**
     * 必须从微信登录
     *
     * @param bool|false $autoLogin
     */
    public function _loginByWeixin($autoLogin = false)
    {
        if (isset($_GET['_debug_wx_']) && $_GET['_debug_wx_'] == 'test') {
            $this->forTestWechatLogin();
        }

        // 如果发现没有带上 wechat_card_js，则加上
        if (IS_GET && empty($_GET['wechat_card_js'])) {
            $_GET['wechat_card_js'] = 1;
            $jumpurl                = U('', I('get.'), '', '', true);
            redirect($jumpurl);
            return;
        }

        $wxSess = session('node_wxid_' . $this->node_id);
        if (!$wxSess || !$wxSess['openid']) {
            // 如果不自动跳转登录
            if (!$autoLogin) {
                $this->wxSess = null;
                return;
            }
            $type = 1;
            if ($this->node_id == C('Yhb.node_id')) {
                $type = 0;
            }
            // 计算回调的页面

            $_GET['wechat_card_js'] = 1;
            $backurl                = U('', I('get.'), '', '', true);
            $backurl                = urlencode($backurl);
            $authorParams           = ['id' => $this->id, 'type' => $type, 'backurl' => $backurl];
            $jumpurl                = U('Label/WeixinLoginNode/wechatAuthorizeAndRedirectByDefault', $authorParams);
            redirect($jumpurl);
        }
        $this->wxSess = $wxSess;
    }

    /**
     * todo for Test wechatLogin 用户微信测试
     */
    public function forTestWechatLogin()
    {
        $openid = I('get.openid', null);
        if (empty($openid)) {
            $openid = 'oyJjks0_kzqtqyNFQ2mlItXtDcf7';
        }
        $wxSessInfo = ['access_token' => 'token', 'openid' => $openid, 'nickname' => 'test'];

        session('node_wxid_' . $this->node_id, $wxSessInfo);
    }

    /**
     * @return Redis|RedisHelper
     */
    public function getRedisInstance()
    {
        if (empty($this->RedisHelper)) {
            import('@.Vendor.RedisHelper');
            $this->RedisHelper = RedisHelper::getInstance();
        }
        return $this->RedisHelper;
    }

    /**
     * @param $param
     * @param $task
     */
    public function canDrawLotteryProcessTask($param)
    {
        //设置正在处理抽奖标识（和过滤重复请求差不多，如果请求处理时间比较长，就有可能出现问题） start
        import('@.Service.FilterDuplicateRequestService');
        $batchId = get_val($param, 'batchId');
        $personId = get_val($param, 'personId');
        $processingFlag = FilterDuplicateRequestService::getDrawLotteryProcessingFlag($batchId, $personId);
        if ($processingFlag) { //正在处理 不能再次请求抽奖接口
            return false;
        }
        return true;
    }

    public function canDrawLotteryProcessTaskAndSetStatus($param)
    {
        import('@.Service.FilterDuplicateRequestService');
        $ret = $this->canDrawLotteryProcessTask($param);
        $this->setDrawLotteryProcessFlag($param);
        return $ret;
    }

    public function setDrawLotteryProcessFlag($param)
    {
        import('@.Service.FilterDuplicateRequestService');
        $batchId = get_val($param, 'batchId');
        $personId = get_val($param, 'personId');
        FilterDuplicateRequestService::setDrawLotteryProcessingFlag($batchId,$personId, 300);//默认 最长设置为300s 300s还没处理完的话自动释放
    }

    public function clearDrawLotteryProcessFlag($param)
    {
        import('@.Service.FilterDuplicateRequestService');
        $batchId = get_val($param, 'batchId');
        $personId = get_val($param, 'personId');
        FilterDuplicateRequestService::delDrawLotteryProcessingFlag($batchId, $personId);
    }

    public function getIgnoreFlag($param)
    {
        import('@.Service.FilterDuplicateRequestService');
        $batchId = get_val($param, 'batchId');
        return  FilterDuplicateRequestService::getIgnoreFlag($batchId);
    }

    public function setIgnoreFlag($param)
    {
        import('@.Service.FilterDuplicateRequestService');
        $batchId = get_val($param, 'batchId');
        $value   = get_val($param, 'value');
        $expire  = get_val($param, 'expire');
        FilterDuplicateRequestService::setIgnoreFlag($batchId, $value, $expire);
    }

}
