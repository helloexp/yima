<?php

/**
 * 活动发动到渠道 @auther:徐应松 @lastedit:tr 2015.01.23
 */
class BindChannelAction extends BaseAction {

    const BATCH_TYPE_WEIGUANWANG = 13;

    const OFF_LINE_TYPE = 2;

    const ON_LINE_TYPE = 1;

    const ELSE_CHANNEL = 5;

    const MEMBER_RECRUIT_BATCH_TYPE = 52;

    /**
     * @var BindChannelModel
     */
    public $BindChannelModel;

    /**
     * @var AuthAccessService
     */
    public $AuthAccessService;

    /**
     * @var PreViewChannelModel
     */
    public $PreViewChannelModel;


    public function _initialize() {
        $this->AuthAccessService = D('AuthAccess', 'Service');
        $this->BindChannelModel = D('BindChannel');
        parent::_initialize();
        
        import('@.Vendor.CommonConst') or die('include file fail.');
    }

    /**
     * [beforeCheckAuth 校验发布权限]
     *
     * @return mixed
     */
    protected function beforeCheckAuth() {
        $batch_type = I('get.batch_type');
        if (in_array($this->wc_version,
                        array(
                                'v0',
                                'v0.5',
                                'v4',
                                'v9')) || !$this->AuthAccessService->needVerifyBindChannelPower($batch_type)) {
            $this->_authAccessMap = '*';
        } else {
            $this->error("尊敬的旺财用户，您需要开通营销工具模块功能或单独支付该营销工具使用费才能发布该活动！",
                    array(
                            '点此开通' => U('Home/Wservice/marketToolVersion')));
        }
        return;
    }

    /**
     * [afterCheckAuth 系统校验完之后，进行付费校验]
     *
     * @return mixed
     */
    protected function afterCheckAuth() {
        $batch_type = I('get.batch_type');
        if (!$this->AuthAccessService->needVerifyBindChannelPower($batch_type)) { //不需要判断权限
            return ;
        }

        $actionName = strtolower('index');
        $curActionName = strtolower(ACTION_NAME);
        if ($actionName == $curActionName) {
            if ($this->wc_version == 'v4')
                return;
            
            $batch_type = I('batch_type');
            // 会员管理，需要认证
            if ($batch_type == '52') {
                if ($this->wc_version == 'v0') {
                    $this->error("尊敬的旺财用户，您只要购买营销工具礼包或单独支付该营销工具使用费即可发布该活动！", 
                        array(
                            '点此开通' => U('Home/Wservice/marketToolVersion')));
                }
            }
            
            // 如果开通了多宝电商权限，那么下面的活动都可以发布
            if ($this->hasPayModule('m2') && in_array($batch_type, 
                array(
                    '26', 
                    '27', 
                    '29', 
                    '31', 
                    '41', 
                    '55'))) {
                return;
            }
            if ($this->BindChannelModel->isInFreeUserBuyList($batch_type)) {
                return;
            }
            if ($batch_type == '17') {//门店导航
                if ($this->wc_version != 'v0') {
                    return;
                }
            }
            if ($this->wc_version == 'v9' && $this->hasPayModule('m1')) {
                return;
            } else {
                $batch_id = I('get.batch_id');
                $bRet = $this->BindChannelModel->isPaid($this->node_id, $batch_id);
                if (! $bRet) {
                    $this->error("尊敬的旺财用户，您只要购买营销工具礼包或单独支付该营销工具使用费即可发布该活动！", 
                        array(
                            '点此开通' => U('Home/Wservice/marketToolVersion')));
                }
            }
            return;
        }
    }
    // 发布到渠道
    public function index() {
        $batch_type = I('batch_type');
        $batch_id = I('batch_id');
        if (empty($batch_type) || empty($batch_id) || $batch_type == '5')
            $this->error('错误参数！');
        // 判断系统专用渠道是否创建，没有的话就创建一个
        $appChannel = $this->BindChannelModel->appExists($this->node_id) or
             $this->error('系统专用渠道创建失败！');
        $batch_name = '';
        try {
            $mInfo = $this->BindChannelModel->getMarketInfo($this->node_id, $batch_type,
                $batch_id, array(
                    'name'));
            $batch_name = $mInfo['name'];
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
        $myChannel = $this->BindChannelModel->getAvailableChannel($this->node_id, $batch_id);
        $this->assign('myChannel', $myChannel);
        $this->assign('batch_name', $batch_name);
        $this->assign('batch_type', $batch_type);
        $this->assign('batch_id', $batch_id);
        $isReEdit = I('isReEdit', '1');
        $this->assign('isReEdit', $isReEdit); // 用来控制是否有"上一步"按钮
        $webTitle = $this->BindChannelModel->getWebTitle($batch_type); // web页的title
        $this->assign('webTitle', $webTitle);
        $cancelUrl = $this->BindChannelModel->getCancelUrl($batch_type); // 取消按钮的链接
        $this->assign('cancelUrl', $cancelUrl);
        $selfDefineActionArr = I('selfDefineActionArr');
        $this->assign('selfDefineActionArr', $selfDefineActionArr);
        $stepBar = D('CjSet')->getActStepsBar('publish', $batch_id, 
            $publishGroupModule, $isReEdit, $selfDefineActionArr);
        //判断是否有员工渠道(0:没有员工渠道，1：有员工渠道)
        $hasStaffChannel = $this->BindChannelModel->hasStaffChannel($this->node_id);
        $this->assign('hasStaffChannel', $hasStaffChannel);
        $this->display();
    }
    // 发布到渠道提交
    public function submitBind() {
        $channel    = I('channel'); 
        $batch_type = I('batch_type');
        $batch_id   = I('batch_id');
        if (empty($batch_type) || empty($batch_id))
            $this->error('错误参数！');
        
        if (! $channel) {
            $this->error('请选择渠道');
        }
        $res = $this->BindChannelModel->bindMyChannel($this->node_id, $channel, $batch_id, $batch_type, true);
        node_log('活动发布|活动类型-活动号' . $batch_type . '-' . $batch_id);
        if ($res) {
            $this->success();
        } else {
            $this->error('发布到渠道失败');
        }
        
    }

    /**
     * 检查是否能跳转到成功页
     */
    public function checkSuccess() {
        $batch_id = I('get.batch_id');
        $batch_type = I('get.batch_type');
        $isReEdit = I('get.isReEdit');
        $selfDefineActionArr = I('get.selfDefineActionArr');
        $params = array(
            'batch_id' => $batch_id, 
            'batch_type' => $batch_type, 
            'isReEdit' => $isReEdit, 
            'selfDefineActionArr' => $selfDefineActionArr);
        if ($this->hasPayModule('m1')) {
            $this->redirect('publishSuccess', $params);
        }
        if ($this->BindChannelModel->isInFreeUserBuyList($batch_type)) { // 是否在免费用户需要买的模块范围内
            if ($this->BindChannelModel->isPaid($this->node_id, $batch_id)) { // 查看有没有付款
                $this->redirect('publishSuccess', $params);
            }
            if ($batch_type == CommonConst::BATCH_TYPE_EUROCUP) {//如果是欧洲杯之前购买过2980的直接跳成功页
                if ($this->BindChannelModel->hasBuyAllGame($this->node_id)) {
                    $this->redirect('publishSuccess', $params);
                }
            }
            $this->redirect('order', $params); // 需要付款的跳转到订单确认页面
        } else {
            $this->redirect('publishSuccess', $params);
        }
    }

    /**
     * 订单确认页面
     */
    public function order() {
        $batch_id = I('get.batch_id');
        $batch_type = I('get.batch_type');
        $isReEdit = I('get.isReEdit');
        // 判断活动信息是否填写完整
        $result = $this->BindChannelModel->checkCanPublish($this->node_id, $batch_id);
        if ($result !== true) {
            $this->error('请完善奖项配置');
        }
        $stepBar = D('CjSet')->getActStepsBar('', $batch_id, '', $isReEdit);
        $orderId = $this->BindChannelModel->createOrder($this->node_id, $batch_id,
            $batch_type);
        $orderInfo = $this->BindChannelModel->getOrderInfo($orderId, $this->node_id);
        // 1表示付费订单2表示免费订单
        // pay_status:0表示未付1表示已付
        if ($orderInfo['order_type'] == '2' ||
             ($orderInfo['order_type'] == '1' && $orderInfo['pay_status'] == '1')) {
            session('batch_type_belong_all', null);
            redirect(
                U('publishSuccess', 
                    array(
                        'batch_id' => $batch_id, 
                        'batch_type' => $batch_type)));
            exit();
        }
        $this->assign('len', count($orderInfo['detail']['couponDetail'])); // 订单所存在的发码种类的长度,方便前台显示
        $this->assign('mId', $batch_id);
        $this->assign('orderInfo', $orderInfo);
        $this->assign('stepBar', $stepBar); // 抽奖活动步骤条
        $batchNameArr = C('BATCH_TYPE_NAME');
        $this->assign('batchName', $batchNameArr[$batch_type]);
        // 预览渠道
        if (empty($this->PreViewChannelModel)) {
            $this->PreViewChannelModel = D('PreViewChannel');
        }
        $qrCodeSrc = $this->PreViewChannelModel->getPreviewChannelCode($this->node_id, $batch_id, $batch_type);
        $this->assign('qrCodeSrc', $qrCodeSrc);
        // 付费的活动种类,设置用户须知的路径
        $isInFreeUserBuyList = $this->BindChannelModel->isInFreeUserBuyList($batch_type);
        if (! $isInFreeUserBuyList) {
            $this->error('不在付费活动列表内');
        }
        // 付费活动的弹框说明url
//         $noticeUrl = $this->getPayActivityNoticeUrl($batch_type);
        $noticeUrl = U('LabelAdmin/Notice/activity');
        $this->assign('noticeUrl', $noticeUrl);
        
        $this->assign('batchType', $batch_type);
        $this->display();
    }

    /**
     * 获取单独模块单独付费的说明文字页的模板路径
     *
     * @param int $batch_type
     * @return string url
     */
    protected function getPayActivityNoticeUrl($batch_type) {
        $url = U('LabelAdmin/Notice/batchType' . $batch_type);
        return $url;
    }

    /**
     * 发布成功页
     */
    public function publishSuccess() {
        $batch_id = I('batch_id');
        $batch_type = I('batch_type');
        $batch_name = '';
        try {
            $mInfo = $this->BindChannelModel->getMarketInfo($this->node_id, $batch_type,
                $batch_id, 
                array(
                    'name', 
                    'is_new'));
            $batch_name = $mInfo['name'];
            $is_new = $mInfo['is_new'];
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
        // 判断系统专用渠道是否创建，没有的话就创建一个
        $appChannel = $this->BindChannelModel->appExists($this->node_id) or $this->error('系统专用渠道创建失败！');
        //绑定了的batch_channel的id（包括我的渠道和多了互动默认渠道）
        //结构array(0 => array('bcid' => 'xxx', 'name' => 'yyy'), 1 => array('bcid' => 'aaa', 'name' => 'bbb'))
        $selectedChannel = $this->BindChannelModel->getBindedMyBatchChannelId($this->node_id, $batch_id);
        $selectedChannel = $this->BindChannelModel->addCodeUrl($selectedChannel);
        $this->assign('batch_name', $batch_name);
        $this->assign('batch_id', $batch_id);
        $this->assign('batch_type', $batch_type);
        $this->assign('selectedChannel', $selectedChannel);
        // 返回活动列表的url
        $backActListUrl = $this->BindChannelModel->getCancelUrl($batch_type, $is_new);
        $this->assign('backActListUrl', $backActListUrl);
        // 是否显示活动步骤的进度条
        $showSteps = $this->BindChannelModel->isShowSteps($batch_type);
        $this->assign('showSteps', $showSteps);
        // 获取进度条
        $publishGroupModule = I('publishGroupModule');
        $selfDefineActionArr = I('selfDefineActionArr');
        if($batch_type == '52'){
            $stepBar = D('CjSet')->getActStepsBar('', $batch_id, $publishGroupModule, '1', array('setActBasicInfo' => '基础信息', 'setPrize' => '奖项设定','publish'=>'活动发布'));
        }else{
            $stepBar = D('CjSet')->getActStepsBar('', $batch_id, $publishGroupModule, '1', $selfDefineActionArr);           }
        $this->assign('stepBar', $stepBar);
        // 是否显示批量下载的按钮
        $showGroupDownload = (count($selectedChannel) > 1) ? '1' : '0';
        $this->assign('showGroupDownload', $showGroupDownload);
        $needShowEposMail = $this->BindChannelModel->needShowEposMail($this->node_id,
            $batch_type);
        $this->assign('needShowEposMail', $needShowEposMail);
        if ($needShowEposMail) {
            // 显示默认的epos邮箱发送框，默认邮件地址为注册时用的地址
            $regEmail = $this->BindChannelModel->getRegEmail($this->node_id);
            $this->assign('regEmail', $regEmail);
        }
        // 是否是有付款订单的活动,如果有的话,发布成功后的文字不一样
        $currentOrder = $this->BindChannelModel->currentActivityOrder($this->node_id, $batch_id);
        // 如果有付费订单
        if ($currentOrder) {
            // 计算服务费(原先的是订单金额,暂时改成只计算服务费)
            // $detail = json_decode($currentOrder['detail'], true);
            // $sendAmount = $currentOrder['amount'] -
            // $detail['serviceArr']['num'] *
            // $detail['serviceArr']['config']['price'];
            
            // 20151030 现在又改为订单金额
            $sendAmount = $currentOrder['amount'];
            // 充值链接
            $addMoneyUrl = C('YZ_RECHARGE_URL') . '&node_id=' . $this->node_id .
                 '&name=' . $this->user_name . '&token=' .
                 $this->userInfo['token'];
            $this->assign('addMoneyUrl', $addMoneyUrl);
            $this->assign('sendAmount', $sendAmount);
        }
        // 是否显示创建海报的链接
        $showCreatePosterLink = ($batch_type == CommonConst::BATCH_TYPE_POSTER) ? false : true;
        $this->assign('showCreatePosterLink', $showCreatePosterLink);
        $this->display();
    }

    /**
     * 打包下载二维码
     */
    public function downloadQrCode() {
        $batchChannelIdArr = I('batch_channel_id', array()); // batch_channel表的id
        if (empty($batchChannelIdArr)) {
            $this->error('请选中您要下载的二维码');
        }
        $showCodeAction = A('LabelAdmin/ShowCode');
        $rootpath = APP_PATH . 'Upload/batch_channel_qr_code/';
        $path = $rootpath . $this->node_id . '/';
        $realpath = get_upload_url(
            APP_PATH . 'Upload/batch_channel_qr_code/' . $this->node_id . '/');
        if (! is_dir($rootpath)) {
            mkdir($rootpath);
        }
        if (! is_dir($path)) {
            mkdir($path);
        }
        $zip = new ZipArchive();
        $zipfilename = 'code_' . date('YmdHis') . '.zip';
        $zip_path = $path . $zipfilename;
        // $zip_path = mb_convert_encoding($zipfile, "GBK", "UTF-8");
        $batchChannelRe = M('tbatch_channel')->where(
            array(
                'id' => array(
                    'in', 
                    $batchChannelIdArr)))->getField('id,channel_id,batch_id', true);
        $batchChannelReValues = array_values($batchChannelRe);
        $mName = M('tmarketing_info')->where(
            array(
                'id' => $batchChannelReValues[0]['batch_id']))->getField('name');
        $channelModel = M('tchannel');
        if ($zip->open($zip_path, ZipArchive::OVERWRITE) === TRUE) {
            foreach ($batchChannelIdArr as $batch_channel_id) {
                $type = $channelModel->where(
                    array(
                        'id' => $batchChannelRe[$batch_channel_id]['channel_id']))->getField(
                    'type');
                if ($type == 1) { // 社交渠道用这个(channel表type为1)
                    $showCodeAction->download($batch_channel_id, true, $path);
                } else {
                    $showCodeAction->code(
                        $batchChannelRe[$batch_channel_id]['channel_id'], true, 
                        $path, $mName, $batch_channel_id);
                }
                $fileName = mb_convert_encoding($showCodeAction->fileName, 
                    "GBK", "UTF-8");
                $file = $path . $fileName . '.png';
                if (file_exists($file)) {
                    $zip->addFile($file, $fileName . '.png');
                }
            }
            $zip->close();
        }
        redirect($zip_path);
    }
    
    // 制图写入库
    public function writeTable($id) {
        $label_id = M('tbatch_channel')->where(
            array(
                'id' => $id))->getField('label_id');
        $ap_arr = array(
            'label_id' => $label_id,  // 标签ID
            'is_resp' => '1');
        
        $url = U('Label/Label/index', array(
            'id' => $id), '', '', true);
        import('@.Vendor.phpqrcode.phpqrcode') or die('include file fail.');
        $size_arr = C('SIZE_TYPE_ARR');
        empty($type) ? $size = 4 : $size = $size_arr[$type];
        empty($filename) ? $filename = time() . ".png" : $filename .= '.png';
        ob_start();
        // add by tr
        $log_dir = '';
        $color = '';
        QRcode::png($url, false, '0', $size, $margin = 2, $saveandprint = false, 
            $log_dir, $color, $ap_arr);
        $data = ob_get_contents();
        ob_end_clean();
        $result = base64_encode($data);
        // 'data:image/jpeg;base64,'.base64_encode($data);die;
        return $result;
    }

    /*
     * 处理无权限时的错误提示 @auther :tr
     */
    public function _handleCheckAuth($msg, $return_arr = null) {
        $vs = $this->wc_version;
        if ($vs == 'v0' || $vs == 'v0.5') {
            $msg = '尊敬的旺财用户，开通旺财标准版后才能将此活动发布到您的营销渠道。<a href="' .
                 U('Home/Wservice/buywc') . '">点此开通</a>。';
        }
        if ($return_arr)
            $this->error($msg, $return_arr);
        else
            $this->error($msg);
    }

    /**
     * 检查活动是否能发布,检查是否活动必要信息已经填写完整
     */
    public function checkCanPublish() {
        $batchId = I('get.batch_id');
        $result = false;
        try {
            $result = $this->BindChannelModel->checkCanPublish($this->node_id, $batchId);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
        if (true === $result) {
            $this->success();
        } else {
            $this->ajaxReturn(array(
                'result' => $result));
        }
    }
    
    /**
     * 检查辅助码是否有效
     */
    public function checkDiscount() {
        $assistCode = I('discount_number', '', 'trim');//辅助码
        if (!$assistCode) {
            $this->error('请输入优惠券');
        }
        //获取欧洲杯活动用来验证优惠券的配置信息
        $verifyCodeConfig = $this->BindChannelModel->getVerifyBarcodeConfigByBatchType(CommonConst::BATCH_TYPE_EUROCUP);
        $nodeId = get_val($verifyCodeConfig, 'node_id', '');
        //检查tbarcode_trace表里是否有未使用的优惠券记录
        $re = $this->BindChannelModel->checkBarCodeTrace($assistCode, $nodeId);
        if ($re['resp_code'] == '0000') {
            $this->success();
        } else {
            $this->error($re['msg']);
        }
    }
    
    
    /**
     * 核销活动订单
     */
    public function verifyDiscount() {
        $assistCode = I('discount_number', '','trim');//辅助码
        if (!$assistCode) {
            $this->error("辅助码不正确", "",
                array(
                    'err' => 0));
        }
        $orderId = I('orderId');
        $mPosVerify = D('PosVerify', 'Service');
        // 检查是否有未冲正的过往订单
        $PosReversalModel = M('tpos_reversal');
        $verifyCodeConfig = $this->BindChannelModel->getVerifyBarcodeConfigByBatchType(CommonConst::BATCH_TYPE_EUROCUP);
        $posId = get_val($verifyCodeConfig, 'pos_id', '');
        $nodeId = get_val($verifyCodeConfig, 'node_id', '');
        $aReversal = $PosReversalModel->where(array('node_id' => $nodeId,'status' => '1'))->find();
        if (! empty($aReversal)) {
            $szRetInfo = $mPosVerify->doPosReversal($aReversal['pos_id'],
                $aReversal['res_seq'], $aReversal['assist_code']);
            if ($szRetInfo['business_trans']['result']['id'] != '0000') {
                $this->error("您还有未冲正的过往记录，请联系客服处理！", "",
                    array(
                        'err' => 0));
            } else {
                $PosReversalModel->where(
                    array(
                        'id' => $aReversal['id']))->delete();
            }
        }
        
        // 初始化流水号
        $mPosVerify->setopt();
        // 提前记录一次
        $aVerifyData = array(
            'pos_id' => $posId,
            'node_id' => $nodeId,
            'res_seq' => $mPosVerify->posSeq,
            'assist_code' => $assistCode,
            'add_time' => date('YmdHis'));
        $bRet = $PosReversalModel->add($aVerifyData);
        if (! $bRet) {
            $this->error("验证失败，请重试！", "",
                array(
                    'err' => 0));
        }
        // 验证辅助码
        $szRetInfo = $mPosVerify->doPosVerify($posId, $assistCode, true);
        // 超时，删除记录
        if (! $szRetInfo) {
            $PosReversalModel->where(array(
                'id' => $bRet))->delete();
            $this->error("验证失败，请重试！", "",
                array(
                    'err' => 0));
        }
        
        // 处理返回结果
        if ($szRetInfo['business_trans']['result']['id'] == '3035') {
            // 有后续动作，删除记录
            $PosReversalModel->where(array(
                'id' => $bRet))->delete();
        
            $remainAmt = $szRetInfo['business_trans']['addition_info']['remain_amt'];
            $this->error("还有后续动作", "",
                array(
                    'err' => 1,
                    'remain_amt' => $remainAmt));
        } else if ($szRetInfo['business_trans']['result']['id'] == '0000') {
            // 验证成功
            $PosReversalModel->where(array(
                'id' => $bRet))->save(
                    array(
                        'status' => '3',
                        'trans_time' => $szRetInfo['business_trans']['trans_time'],
                        'tx_amt' => $szRetInfo['business_trans']['addition_info']['tx_amt'],
                        'phone_no' => $szRetInfo['business_trans']['addition_info']['phone_no']));
                    $mId = M('tactivity_order')->where(['id' => $orderId])->getField('m_id');
                    $re = M('tactivity_order')->where(['id' => $orderId])->save(['order_type' => '2']);
                    if (false === $re) {
                        log_write('更改活动订单为免费订单出错，时间：' . date('YmdHis')
                            . '，订单id：' . $orderId . '，辅助码：' . $assistCode . '，活动号：' . $mId);
                    }
                    $re = M('tmarketing_info')->where(['id' => $mId])->save(['pay_status' => '1']);
                    if (false === $re) {
                        log_write('更改活动表支付状态出错，时间：' . date('YmdHis')
                            . '，订单id：' . $orderId . '，辅助码：' . $assistCode . '，活动号：' . $mId);
                    }
                    $this->success();
        } else {
            // 验证失败，删除记录
            $PosReversalModel->where(array(
                'id' => $bRet))->delete();
            $this->error($szRetInfo['business_trans']['result']['comment'], "",array('err' => 0));
        }
        
    }
}