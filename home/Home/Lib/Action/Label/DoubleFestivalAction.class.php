<?php

/**
 * todo 1、抽奖id 怎么弄 2、task数据初始化 双旦 PC落地页面
 *
 * @author Jeff Liu<liuwy@imageco.com.cn> Class DoubleFestivalAction
 */
class DoubleFestivalAction extends MyBaseAction {

    const ACTIVE = 'active';

    const UNACTIVE = 'unactive';

    /**
     *
     * @var UserSessService
     */
    public $UserSessService;

    /**
     *
     * @var DoubleFestivalService
     */
    public $DoubleFestivalService;

    /**
     *
     * @var CjTraceModel
     */
    public $CjTraceModel;

    /**
     *
     * @var CjAction
     */
    public $claimAction;

    /**
     *
     * @var OrderModel
     */
    public $OrderModel;

    /**
     *
     * @var MarketingInfoModel
     */
    public $MarketingInfoModel;

    /**
     *
     * @var WheelModel
     */
    public $WheelModel;

    /**
     *
     * @var NodeInfoModel
     */
    public $NodeInfoModel;

    /**
     * 手机验证码过期时间
     */
    const VERIFY_CODE_EXPIRE_TIME = 60;

    const PER_PAGE_NUM = 20;
    
    // PS key 为 tcj_cate id
    // sql: SELECT c.id,c.name FROM tcj_cate c WHERE batch_id IN (SELECT
    // batch_id FROM tbatch_channel WHERE id IN (12995, 13194,89172,89173))
    public $testMapping = array(
        6092 => 33,  // 33哈根达斯券
        6093 => 1,  // 1Q币
        6094 => 20,  // 20元话费
        6168 => 108,  // 100哈根达斯券
        6170 => 109,  // 100元话费
        6172 => 5);
    // 5kg有机大米一袋
    
    public $productionMapping = array(
        15938 => 33,  // 33哈根达斯券
        15939 => 1,  // 1Q币
        15937 => 20,  // 20元话费
        15946 => 108,  // 100哈根达斯券
        15956 => 109,  // 100元话费
        15945 => 5);
    // 5kg有机大米一袋
    
    public $currentPageUrl = '';

    public $_authAccessMap = '*';

    public $needCheckLogin = false;

    public $checkLoginReturn = true;

    public $needCheckUserPower = false;

    private $realAwardList = false;

    private $currentDate = '';

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     */
    public function _initialize() {
        BaseAction::_initialize(); // 因为 url里面没有id参数 所以不能直接使用 MyBaseAction的
                                   // _initialize方法
        
        $this->currentDate = date('YmdHi');
        $this->UserSessService = D('UserSess', 'Service');
        $this->DoubleFestivalService = D('DoubleFestival', 'Service');
        $this->CjTraceModel = D('CjTrace');
        $this->OrderModel = D('Order');
        $this->NodeInfoModel = D('NodeInfo');
        $this->MarketingInfoModel = D('MarketingInfo');
        
        $userInfo = $this->UserSessService->getUserInfo();
        
        if ($userInfo) {
            if (! isset($userInfo['hasPayModuleM1'])) {
                $userInfo['hasPayModuleM1'] = $this->hasPayModule('m1');
                $this->UserSessService->setUserInfo('hasPayModuleM1', 
                    $userInfo['hasPayModuleM1']);
            }
            
            if (! isset($this->userInfo['needUpdateTask'])) {
                $this->userInfo['needUpdateTask'] = date('YmdH0');
                $this->UserSessService->setUserInfo('needUpdateTask', 
                    $this->userInfo['paiedorder']);
            }
            $this->userInfo = $userInfo;
        }
        
        $tgId = trim(I('tg_id', 0));
        $this->currentPageUrl = $this->getCurrentUrl();
        $fromurl = U('Home/MarketActive/createNew', 
            array(
                'typelist' => 4));
        $fromurolEncoded = urlencode($fromurl);
        $regUrl = U('Home/Reg/index', 
            array(
                'tg_id' => $tgId, 
                'fromurl' => $fromurolEncoded));
        $loginUrl = U('Home/Login/showLogin', 
            array(
                'tg_id' => $tgId, 
                'fromurl' => $fromurolEncoded));
        
        session('fromurl', $fromurl);
        $contactPhone = isset($this->nodeInfo['contact_phone']) ? $this->nodeInfo['contact_phone'] : '';
        $this->assign('hasPayModuleM1', $this->userInfo['hasPayModuleM1']);
        $this->assign('contact_phone', $contactPhone);
        $this->assign('timeInterval', self::VERIFY_CODE_EXPIRE_TIME);
        $this->assign('currentPageUrl', $this->currentPageUrl);
        $this->assign('currentPageUrlEncoded', $fromurolEncoded);
        $this->assign('regUrl', $regUrl);
        $this->assign('loginUrl', $loginUrl);
        $this->assign('tgId', $tgId);
        $this->assign('userInfo', $this->userInfo);
    }

    /**
     * 双旦活动展示页面
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     */
    public function show() {
        $regTaskProcess = TaskService::TASK_NOT_FOUND; // 注册任务状态
        $payOrderTaskProcess = TaskService::TASK_NOT_FOUND; // 支付双旦模块任务状态
        $favourTaskProcess = TaskService::TASK_NOT_FOUND; // 最终任务状态
        if ($this->userInfo) { // 用户已经登录 获取用户各个任务的完成状态
            $params = $this->userInfo;
            $params['forceFinish'] = true;
            if (! $this->userInfo['hasPayModuleM1']) { // m1 不允许参与 前2个任务
                tag('payorder_task_init', $this->userInfo);
            }
            
            $nodeName = session('node_name');
            if (empty($nodeName)) {
                $nodeName = $this->getNodeName();
                if ($nodeName) {
                    session('node_name', $nodeName);
                }
            }
            
            if (! $this->userInfo['hasPayModuleM1']) { // m1 不允许参与 前2个任务
                $getPaiedorder = $this->getPaiedorder();
                if ($getPaiedorder) { // 已经购买过双旦模块 完成任务 //todo 能否使用其他方式实现？？
                    tag('payorder_task_finish', $this->userInfo);
                }
            }
            
            $needFinishFavourTask = $this->needFinishFavourTask(); // todo
                                                                   // 能否使用其他方式实现？？
            if ($needFinishFavourTask) {
                tag('favour_task_finish', $params);
            }
            
            $regTaskProcess = $this->DoubleFestivalService->getProgressStatus(
                DoubleFestivalService::REG_TASK_ID);
            $payOrderTaskProcess = $this->DoubleFestivalService->getProgressStatus(
                DoubleFestivalService::PAY_ORDER_TASK_ID);
            if ($payOrderTaskProcess > 0 || $this->userInfo['hasPayModuleM1']) {
                $favourTaskProcess = $this->DoubleFestivalService->getProgressStatus(
                    DoubleFestivalService::FAVOUR_TASK_ID);
            }
        }
        
        if ($this->realAwardList) {
            $regLabelId = $this->DoubleFestivalService->getCjLabelIdByTaskId(
                DoubleFestivalService::REG_TASK_ID);
            $regTaskAwardList = $this->CjTraceModel->getAwardListByLabelId(
                $regLabelId, self::PER_PAGE_NUM);
        } else {
            $regTaskAwardList = $this->getRegAwardList();
        }
        $regTaskAwardList = $this->formatData($regTaskAwardList);
        
        if ($this->realAwardList) {
            $payOrderLabelId = $this->DoubleFestivalService->getCjLabelIdByTaskId(
                DoubleFestivalService::PAY_ORDER_TASK_ID);
            $payOrderTaskAwardList = $this->CjTraceModel->getAwardListByLabelId(
                $payOrderLabelId, self::PER_PAGE_NUM);
        } else {
            $payOrderTaskAwardList = $this->getPayOrderAwardList();
        }
        
        $payOrderTaskAwardList = $this->formatData($payOrderTaskAwardList);
        
        $regDivStatus = $this->getRegDivStatus($regTaskProcess);
        $payOrderDivStatus = $this->getPayOrderDivStatus($regTaskProcess, 
            $payOrderTaskProcess);
        $finalTaskDivStatus = $this->getFinalTaskDivStatus($payOrderTaskProcess);
        
        $regButtonStatus = $this->getRegButtonStatus($regTaskProcess);
        $payOrderButtonStatus = $this->getPayOrderButtonStatus(
            $payOrderTaskProcess);
        $finalTaskButtonStatus = $this->getFinalTaskButtonStatus(
            $favourTaskProcess);
        
        $this->assign('regTaskAwardList', $regTaskAwardList);
        $this->assign('payOrderTaskAwardList', $payOrderTaskAwardList);
        $this->assign('regDivStatus', $regDivStatus);
        $this->assign('payOrderDivStatus', $payOrderDivStatus);
        $this->assign('finalTaskDivStatus', $finalTaskDivStatus);
        $this->assign('regButtonStatus', $regButtonStatus);
        $this->assign('payOrderButtonStatus', $payOrderButtonStatus);
        $this->assign('finalTaskButtonStatus', $finalTaskButtonStatus);
        $this->assign('regTaskProcess', $regTaskProcess);
        $this->assign('payOrderTaskProcess', $payOrderTaskProcess);
        $this->assign('favourTaskProcess', $favourTaskProcess);
        $this->assign('regTaskId', DoubleFestivalService::REG_TASK_ID);
        $this->assign('payOrderTaskId', 
            DoubleFestivalService::PAY_ORDER_TASK_ID);
        
        $this->display();
    }

    public function getNodeName() {
        $nodeName = '';
        if ($this->userInfo) {
            $nodeName = $this->NodeInfoModel->getNodeName(
                array(
                    'node_id' => $this->userInfo['node_id']));
        }
        
        return $nodeName;
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn
     * @param bool $getParam
     *
     * @return string
     */
    public function getCurrentUrl($getParam = true) {
        $pageURL = 'http';
        
        if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
            $pageURL .= "s";
        }
        $pageURL .= "://";
        
        $this_page = $_SERVER["REQUEST_URI"];
        
        // 只取 ? 前面的内容
        if ($getParam == false) {
            if (strpos($this_page, "?") !== false) {
                $this_pages = explode("?", $this_page);
                $this_page = reset($this_pages);
            }
        }
        
        $name = '';
        if (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST']) {
            $name = $_SERVER['HTTP_HOST'];
        } else if (isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME']) {
            $name = $_SERVER['SERVER_NAME'];
        }
        
        if ($_SERVER['SERVER_PORT'] != "80") {
            $pageURL .= $name . ':' . $_SERVER['SERVER_PORT'] . $this_page;
        } else {
            $pageURL .= $name . $this_page;
        }
        
        return $pageURL;
    }

    /**
     *
     * @return mixed
     */
    public function getPaiedorder() {
        $nodeId = isset($this->userInfo['node_id']) ? $this->userInfo['node_id'] : '';
        $diff = $this->currentDate - $this->userInfo['needUpdateTask'];
        if ($diff % 3 == 0) {
            return $this->OrderModel->getPaiedOrderInfoByNode($nodeId);
        }
        
        return false;
    }

    /**
     *
     * @return bool
     */
    public function needFinishFavourTask() {
        $diff = $this->currentDate - $this->userInfo['needUpdateTask'];
        if ($diff % 3 == 0) {
            $marketingInfoList = $this->getMarketingInfoList();
            foreach ($marketingInfoList as $index => $item) {
                $click_count = isset($item['click_count']) ? $item['click_count'] : '';
                if ($click_count >= 5000) {
                    return true;
                }
            }
        }
        
        return false;
    }

    public function getMarketingInfoList() {
        $nodeId = isset($this->userInfo['node_id']) ? $this->userInfo['node_id'] : '';
        $return = $this->MarketingInfoModel->getList(
            array(
                'batch_type' => 59, 
                'node_id' => $nodeId));
        
        return $return;
    }

    public function getRegAwardList() {
        $awardList = array(
            '20元话费', 
            '33元代金券', 
            '1Q币');
        $phoneHeader = array(
            '131', 
            '132', 
            '133', 
            '135', 
            '136', 
            '138', 
            '139', 
            '150', 
            '151', 
            '155', 
            '170', 
            '180', 
            '181', 
            '153', 
            '189', 
            '187', 
            '188');
        $finalData = array();
        for ($i = 0; $i < 20; $i ++) {
            $finalData[] = array(
                'mobile' => $phoneHeader[array_rand($phoneHeader)] . '****' .
                     rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9), 
                    'goods_name' => $awardList[array_rand($awardList)]);
        }
        
        return $finalData;
    }

    public function getPayOrderAwardList() {
        $awardList = array(
            '5kg有机大米', 
            '100元代金券', 
            '100元话费');
        $phoneHeader = array(
            '131', 
            '132', 
            '133', 
            '135', 
            '136', 
            '138', 
            '139', 
            '150', 
            '151', 
            '155', 
            '170', 
            '180', 
            '181', 
            '153', 
            '189', 
            '187', 
            '188');
        $finalData = array();
        for ($i = 0; $i < 20; $i ++) {
            $finalData[] = array(
                'mobile' => $phoneHeader[array_rand($phoneHeader)] . '****' .
                     rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9), 
                    'goods_name' => $awardList[array_rand($awardList)]);
        }
        
        return $finalData;
    }

    /**
     *
     * @param $list
     * @return mixed
     */
    public function formatData($list) {
        foreach ((array) $list as $index => $item) {
            $mobile = isset($item['mobile']) ? $item['mobile'] : '';
            $mobile = substr($mobile, 0, 3) . '****' . substr($mobile, - 4);
            $list[$index]['mobile'] = $mobile;
        }
        
        return $list;
    }

    /**
     *
     * @param $payOrderTaskProcess
     * @return string
     */
    public function getFinalTaskDivStatus($payOrderTaskProcess) {
        if ($this->userInfo && $this->userInfo['hasPayModuleM1']) { // m1用户
                                                                    // 直接第三步骤
            return self::ACTIVE;
        } else if ($this->userInfo && ($payOrderTaskProcess ==
             TaskService::TASK_FINISHED ||
             $payOrderTaskProcess == TaskService::TASK_CLAIMED)) {
            return self::ACTIVE;
        } else {
            return self::UNACTIVE;
        }
    }

    /**
     *
     * @param $payOrderTaskProcess
     * @return string
     */
    public function getPayOrderDivStatus($regTaskProcess, $payOrderTaskProcess) {
        if ($this->userInfo) {
            if ($this->userInfo['hasPayModuleM1']) {
                return self::UNACTIVE;
            } else if (($regTaskProcess == TaskService::TASK_FINISHED ||
                 $regTaskProcess == TaskService::TASK_CLAIMED) &&
                 $payOrderTaskProcess == TaskService::TASK_PROGRESSING) {
                return self::ACTIVE;
            } else if ($regTaskProcess == TaskService::TASK_NOT_FOUND &&
                 $payOrderTaskProcess == TaskService::TASK_PROGRESSING) {
                return self::ACTIVE;
            }
        }
        
        return self::UNACTIVE;
    }

    /**
     *
     * @param $regTaskProcess
     * @return string
     */
    public function getRegDivStatus($regTaskProcess) {
        if ($this->userInfo) {
            if ($regTaskProcess == TaskService::TASK_PROGRESSING) {
                return self::ACTIVE;
            } else {
                return self::UNACTIVE;
            }
        } else {
            return self::ACTIVE;
        }
    }

    /**
     * 获得最终任务按钮显示状态
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $favourTaskProcess
     * @return string
     */
    public function getFinalTaskButtonStatus($favourTaskProcess) {
        if ($this->userInfo) { // 已经登录，查看用户是否有资格参与，如果有资格, 查看是否已经领取奖励了。
            if ($favourTaskProcess == TaskService::TASK_FINISHED) { // 可以领奖
                return 'award';
            }
        }
        
        return 'hiden';
    }

    /**
     * 获得购买双旦模块按钮显示状态
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $payOrderTaskProcess
     * @return string
     */
    public function getPayOrderButtonStatus($payOrderTaskProcess) {
        if ($this->userInfo) { // 已经登录，查看用户是否有资格参与，如果有资格, 查看是否已经领取奖励了。
            if ($payOrderTaskProcess == TaskService::TASK_CLAIMED) { // 已领奖
                return 'award-ok';
            } else if ($payOrderTaskProcess == TaskService::TASK_FINISHED) { // 可以领奖
                return 'award';
            }
        }
        
        return 'unaward'; // 不可以领奖
    }

    /**
     * 获得注册按钮显示状态
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $regTaskProcess
     * @return string
     */
    public function getRegButtonStatus($regTaskProcess) {
        if ($this->userInfo) { // 已经登录，查看用户是否有资格参与，如果有资格, 查看是否已经领取奖励了。
            if ($regTaskProcess == TaskService::TASK_NOT_FOUND) { // 新用户
                                                                  // 可以参与，查看是否已经领取奖励了。
                return 'unaward';
            } else if ($regTaskProcess == TaskService::TASK_CLAIMED) { // 已领奖
                return 'award-ok';
            } else { // 可以领奖
                return 'award';
            }
        } else {
            return 'register';
        }
    }

    /**
     * 是否为新注册用户
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @return boolean
     */
    public function isNewRegUser() {
        $add_time = isset($this->userInfo['add_time']) ? $this->userInfo['add_time'] : '';
        
        $start_time = $this->DoubleFestivalService->getNewRegisterStartTimeByTaskId(
            DoubleFestivalService::REG_TASK_ID);
        if (empty($start_time)) {
            $start_time = '20151125000000';
        }
        
        return $add_time >= $start_time;
    }

    /**
     * 双旦任务领取奖励 ajax请求
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     */
    public function claimTaskAward() {
        if (empty($this->userInfo)) { // 没有登录直接返回错误
            $this->ajaxReturn(- 1053);
        }
        $taskId = I('request.task_id', null);
        switch ($taskId) {
            case DoubleFestivalService::REG_TASK_ID:
            case DoubleFestivalService::PAY_ORDER_TASK_ID:
                $result = $this->verifyCheckCode();
                if ($result < 0) {
                    $this->ajaxReturn($result);
                }
                $this->doClaimTaskAward($taskId);
                break;
            default:
                $this->ajaxReturn(- 1053);
        }
    }

    /**
     * 领取奖励
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $taskId
     */
    private function doClaimTaskAward($taskId) {
        $taskProcess = $this->DoubleFestivalService->getProgressStatus($taskId);
        if ($taskProcess == TaskService::TASK_FINISHED) { // 任务已完成
            $cjId = $this->DoubleFestivalService->getCjLabelIdByTaskId($taskId);
            if ($cjId) { // 领取奖励

                // 过滤重复请求 start
                $params = array(
                    'id' => $cjId, 
                    'needCheckVerify' => false, 
                    'from' => 'doubuleFestival', 
                    'node_id' => $this->node_id);
                $prefix = 'doublefestival:';
                $this->filterDuplicateRequest($params, $prefix);
                // 过滤重复请求 end
                
                $where = array(
                    'label_id' => $cjId, 
                    'node_id' => $this->node_id, 
                    'status' => '1');
                $cjTraceInfo = $this->CjTraceModel->getCjTrace($where, 
                    BaseModel::SELECT_TYPE_ONE);
                if (empty($cjTraceInfo)) { // 还没有中过奖
                    $_POST['id'] = $cjId;
                    $_POST['needCheckVerify'] = false;
                    $_REQUEST['needCheckVerify'] = false;
                    $_REQUEST['id'] = $cjId;
                    $_POST['from'] = 'doubuleFestival';
                    $_REQUEST['from'] = 'doubuleFestival';
                    session('doClaimTaskAwardVerified', 1);
                    $this->claimAction = A('Label/Cj');
                    $result = $this->claimAction->doNormalDrawLotterySubmit(
                        true);
                    $data = 0;
                    if (isset($result['data']['cj_cate_id'])) {
                        $cj_cate_id = $result['data']['cj_cate_id'];
                        if (is_production()) {
                            $data = isset($this->productionMapping[$cj_cate_id]) ? $this->productionMapping[$cj_cate_id] : 0;
                        } else {
                            $data = isset($this->testMapping[$cj_cate_id]) ? $this->testMapping[$cj_cate_id] : 0;
                        }
                    }
                    if ($taskId == DoubleFestivalService::REG_TASK_ID) {
                        tag('reg_task_claim', $this->userInfo);
                    } else if ($taskId ==
                         DoubleFestivalService::PAY_ORDER_TASK_ID) {
                        tag('payorder_task_claim', $this->userInfo);
                    }
                    
                    if (empty($this->WheelModel)) {
                        $this->WheelModel = D('Wheel');
                    }
                    
                    // 送旺币 start
                    if ($taskId == DoubleFestivalService::PAY_ORDER_TASK_ID) {
                        $this->WheelModel->setWb($this->userInfo['node_id'], 
                            200, date('Ymd', strtotime('+60day')), 36); // 送200旺币
                                                                            // （60天有效期）
                    }
                    // 送旺币 end
                    
                    $result['data'] = $data;
                    $this->DoubleFestivalService->finish($taskId, 
                        $this->userInfo);
                    $this->ajaxReturn($result);
                } else { // 已经领取过奖品
                    $this->DoubleFestivalService->finish($taskId, 
                        $this->userInfo);
                    $this->ajaxReturn(- 1052);
                }
            } else {
                $this->ajaxReturn(- 1053);
            }
        } else {
            if ($taskProcess == TaskService::TASK_CLAIMED) {
                $this->ajaxReturn(- 1052);
            } else {
                $this->ajaxReturn(- 1051);
            }
        }
    }

    public function sendCheckCode() {
        $mobile = I('post.phone', null);
        $error = '';
        if (! check_str($mobile, 
            array(
                'null' => false, 
                'strtype' => 'mobile'), $error)) {
            $this->showErrorByErrno(- 1008, null, $error);
        }
        
        // 发送频率验证
        $check_code = session('checkCode');
        $oldMobile = isset($check_code['mobile']) ? $check_code['mobile'] : '';
        if (! empty($check_code) && $oldMobile == $mobile &&
             (time() - $check_code['add_time']) < self::VERIFY_CODE_EXPIRE_TIME) {
            $this->showErrorByErrno(- 1017, null, 
                time() - $check_code['add_time']);
        }
        $num = mt_rand(1000, 9999);
        // 短信内容
        $node_name = session('node_name');
        $code_info = "双旦任务抽奖活动,您此次的动态验证码为：{$num} 如非本人操作请忽略！";
        if ($node_name) {
            $code_info = "【{$node_name}】 " . $code_info;
        }
        
        // 通知支撑
        $transaction_id = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
                                                                    // 请求参数
        $req_array = array(
            'NotifyReq' => array(
                'TransactionID' => $transaction_id, 
                'ISSPID' => C('MOBILE_ISSPID'), 
                'SystemID' => C('ISS_SYSTEM_ID'), 
                'SendLevel' => '1', 
                'Recipients' => array(
                    'Number' => $mobile),  // 手机号
                'SendClass' => 'MMS', 
                'MessageText' => $code_info,  // 短信内容
                'Subject' => '', 
                'ActivityID' => C('MOBILE_ACTIVITYID'), 
                'ChannelID' => '', 
                'ExtentCode' => ''));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $resp_array = $RemoteRequest->requestIssServ($req_array);
        
        $ret_msg = $resp_array['NotifyRes']['Status'];
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
             $ret_msg['StatusCode'] != '0001')) {
            $this->showErrorByErrno(- 1018);
        }
        $check_code = array(
            'number' => $num, 
            'add_time' => time(), 
            'mobile' => $mobile);
        session('checkCode', $check_code);
        $this->success('验证码已发送');
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @return int
     */
    public function verifyCheckCode() {
        $checkCode = I('post.verify', null);
        $error = '';
        if ($checkCode && ! check_str($checkCode, 
            array(
                'null' => false), $error)) {
            return - 1007;
        }
        $phone_check_code = session('checkCode');
        if (function_exists('is_production') && ! is_production() &&
             $checkCode == '1111') {
            return 1;
        } else {
            if (empty($phone_check_code) ||
                 $phone_check_code['number'] != $checkCode) { // 手机验证码不正确
                return - 1009;
            }
            if (time() - $phone_check_code['add_time'] >
                 self::VERIFY_CODE_EXPIRE_TIME) { // 手机验证码已经过期
                return - 1010;
            }
        }
        
        return 1;
    }

}