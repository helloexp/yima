<?php

class SpringMonkeyAction extends MyBaseAction
{
    // 初始参数
    const BATCH_TYPE  = '60';
    // 要传到页面的数据
    public $assignArr = array();

    public $wxUserInfo;

    public function _initialize()
    {
        parent::_initialize();
        // 判断是否是微信参与
        if($this->marketInfo['join_mode']){
            // 校验微信是否登录
            self::checkWxUserInfo();
            // 更新添加twx_wap_user表数据
            self::updateWxUserInfo();
        }
        // 设置微信分享配置参数
        self::setWxShareConfig();
        // 设置传到页面的数据
        self::setAssignArr();

    }
    /**
     * [index 金猴闹春主页]
     * @return [type]
     */
    public function index()
    {
        // 校验活动时间是否过期
        self::checkMarketDate(false);
        // 校验活动类型是否正确
        self::checkBatchType();
        // 获取奖项、奖品
        self::getAllJpInfo();
        // 调用assign方法分配参数
        self::distributeAssign();
        // 访问量统计
        self::UpdateRecord();

        $this->display();
    }
    
    public function _before_cjSubmit() {
        //检测上一次的处理更新是否已完成
        $str = '';
        if ($this->marketInfo['join_mode'] == '0') {
            $mobile = I('post.mobile', 'null','trim');
            $str = $mobile;
        } else {
            $str = $this->wxUserInfo['openid'];
        }
        $getUpdateInfo = S('is_over_cj_times_'.$this->batch_id . ',no:' . $str);
        //log_write( 'str:'. json_encode($str). '缓存：' . json_encode($getUpdateInfo));
        if ($getUpdateInfo) {
            $this->success(['code'=>- 1041]);
        }
    }
    
    /**
     * [cjSubmit 提交抽奖]
     * @return [type] [description]
     */
    public function cjSubmit()
    {
        // 校验提交的参数，并返回手机号码
        $mobile = self::checkCjPost();
        // 中奖记录
        self::setLuckRecord($mobile);
        
        // 运行抽奖主逻辑
        self::runCJ($mobile,$this->marketInfo['join_mode']);
    }


    //---------------下面是抽奖需要用到的私有方法-------------------



    /**
     * 校验活动是否过期
     * @return bool
     */
    private function checkMarketDate($flag = true) {
        $mInfo = $this->marketInfo;
        $cInfo = $this->channelInfo;
        if (! empty($mInfo['start_time']) && ! empty($mInfo['end_time']))
        {
            $curTime = date('YmdHis');
            if ($curTime < $mInfo['start_time'] || $curTime > $mInfo['end_time']) {
                $flag or $this->showErrorByErrno(- 1016);
                return false;
            }
        }
        return true;
    }
    /**
     * [checkBatchType 检查活动类型]
     * @return [type] [null]
     */
    private function checkBatchType(){
        if($this->batch_type != self::BATCH_TYPE)
        {
            $errorInfo = array(
                'errorImg'     => '__PUBLIC__/Label/Image/waperro5.png',
                'errorTxt'     => '错误访问！',
                'errorSoftTxt' => '你的访问地址出错啦~'
                );
            $this->error($errorInfo);
        }

    }
    /**
     * [checkWxUserInfo 检查微信登录]
     * @return [type] [null]
     */
    private function checkWxUserInfo(){
        // 获取微信登录信息
        $this->wxUserInfo = $this->getWxUserInfo();
        // 为空则重新登录
        $this->wxUserInfo or $this->_checkUser(true);
    }
    /**
     * [updateWxUserInfo 更新微信用户表]
     * @return [type] [null]
     */
    private function updateWxUserInfo()
    {
        try {
            D('WxWapUser')->setWxUserInfo($this->wxUserInfo, $this->node_id,
                $this->id);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }
    /**
     * [setWxShareConfig 设置微信分享参数]
     */
    private function setWxShareConfig(){
        $wx_share_config = D('WeiXin', 'Service')->getWxShareConfig();
        $config = unserialize($this->marketInfo['config_data']);
        $shareArr = array(
            'config' => $wx_share_config,
            'link'   => '',
            'title'  => $config['share_title']?$config['share_title']:'金猴闹新春',
            'desc'   => $config['share_descript']?$config['share_descript']:'2016羊去猴来，是时候告白“霾头苦干”的日子了。动动你的手指，点燃红火、沸腾的吉庆之鞭，享受清新、愉悦的新年气象。金猴闹新春，好礼等你拿！',
            'imgUrl' => $this->marketInfo['share_pic'] ? get_upload_url(
                $this->marketInfo['share_pic']) : C('CURRENT_DOMAIN') .
                 'Home/Public/Label/Image/20160208/icon1.png');
        $this->assignArr = array_merge($this->assignArr,array(
            'wx_share_config' => $wx_share_config,
            'shareData'       => $shareArr
            ));
    }
    /**
     * [setAssignArr 设置页面参数]
     */
    private function setAssignArr(){
        $wap_info = str_replace("\r", '',str_replace("\n", '', nl2br($this->marketInfo['wap_info'])));
        $this->assignArr = array_merge($this->assignArr,array(
            'marketInfo' => $this->marketInfo,
            'wap_info'   => $wap_info,
            'id'         => $this->id,
            'node_name'  => $this->node_name
            ));
    }
    /**
     * [distributeAssign 分配页面参数]
     * @return [type] [null]
     */
    private function distributeAssign(){
        if($this->assignArr)
        {
            foreach ($this->assignArr as $key => $value) {
                $this->assign($key,$value);
            }
        }
    }
    private function getAllJpInfo()
    {
        // 获取抽奖按钮
        $cjruleMap = array(
            'batch_id' => $this->marketInfo['id']
            );
        // 获取奖项数据
        $cateMap = array(
            'node_id'  => $this->node_id,
            'batch_id' => $this->marketInfo['id']
            );
        $cateRet = M('tcj_cate')->where($cateMap)->order('sort asc')->select();
        // 获取奖品数据
        $strField = 'a.*,b.batch_name,b.remain_num,b.storage_num,b.batch_img';
        $strJoin  = 'tbatch_info b on a.b_id=b.id';
        $jpMap = array(
            'a.node_id'  => $this->node_id,
            'a.batch_id' => $this->marketInfo['id'],
            'a.status'   => 1
            );
        $jpRet = M()->table("tcj_batch a")->field($strField)->join($strJoin)->where($jpMap)->select();

        $tmpCate = "";
        foreach ($jpRet as $k => $v) {
            $jpRet[$k]['batch_img'] = get_upload_url($v['batch_img']);
            $tmpCate .= ','.$v['cj_cate_id'];
        }
        $tmpCateArr = array_unique(explode(',',$tmpCate));
        foreach ($cateRet as $kk => $vv) {
            if(!in_array($vv['id'],$tmpCateArr))
            {
                unset($cateRet[$kk]);
            }
        }
        $cjData = array(
            'cj_button_text' => M('tcj_rule')->where($cjruleMap)->getField('cj_button_text'),
            'prize'          => $jpRet,
            'cate'           => $cateRet
            );
        $this->assignArr = array_merge($this->assignArr,$cjData);
    }
    /**
     * [checkCjPost 查看抽奖提交的数据是否正确]
     * @return [type] [手机号码]
     */
    private function checkCjPost()
    {
        // 日期验证失败
        if (self::checkMarketDate() === false) {
            $this->success(array('code' => - 1032));
        }
        // 活动不能抽奖
        if ($this->marketInfo['is_cj'] != '1')
        {
            $this->success(array('code' => - 1040));
        }
        // 非法提交
        if (! $this->isPost()) {
            $this->success(array('code' => - 1067));
        }
        // 是否预览渠道
        if (self::isPreChannel()) {
            $this->success(array('code' => - 1048));
        }
        // 手机号码校验
        $mobile = I('post.mobile', 'null','trim');
        if(! is_numeric($mobile) || strlen($mobile) != '11')
        {
            $this->success(array('code' => - 1035));
        }
        // 校验活动是否付费
        if ((! $this->hasPayModule('m1', $this->node_id) &&
             $this->marketInfo['pay_status'] === '0')) {
            $this->success(array('code' => - 1046));
        }
        session('cj_mobile',$mobile);
        session('cj_openid',$mobile);
        return $mobile;
    }
    /**
     * [isPreChannel 是否是预览渠道]
     * @return boolean [bool]
     */
    private function isPreChannel()
    {
        $previewChannelId = D('Channel')->getPreviewChannelId($this->node_id);
        $channelId = $this->channel_id ? $this->channel_id : 0;
        $previewChannelId = (int) $previewChannelId;
        $channelId = (int) $channelId;
        return $previewChannelId === $channelId;
    }
    /**
     * [runCJ 抽奖主流程]
     * $mobile      手机号码
     * $joinMode    参与方式
     * @return [type] [bool]
     */
    private function runCJ($mobile,$joinMode){

        // 调用抽奖接口，并发码
        if($this->id == C('SpringMonkeyChannelId')){
            if(self::isWhite($mobile)){
                $resp = self::getCjResult($mobile,$joinMode);
            }else{
                $this->success(['code'=>- 1002]);
            }
        }else{
            $resp = self::getCjResult($mobile,$joinMode);
        }
        // 判断抽奖结果
        if(!isset($resp['resp_id']))
        {
            $this->success(['code'=>- 1067]);
        }
        if($resp['resp_id'] == '1016')
        {
            $this->success(['code'=>- 1040]);
        }
        if($resp['resp_id'] == '1001') {
            $this->success(['code'=>- 1042]);
        }
        if($resp['resp_id'] != '0000')
        {
            $this->success(['code'=>- 1002]);
        }
        if($joinMode && $resp['prize_type'] == 3)
        {
            // 如果是微信，并且是红包
            self::reSendHb($mobile,$resp);
        }
        if($joinMode && $resp['prize_type'] == 4 && $resp['integral_get_flag'] != 1)
        {
            // 如果是微信，并且是积分奖品
            self::reSendJf($mobile,$resp);
        }
        if($joinMode && $resp['prize_type'] == 1 && $resp['card_id'])
        {
            // 如果是微信，并且是微信卡券奖品，不需要特殊处理
            //self::reSendWxCard($mobile,$resp);
        }
        if($joinMode && $resp['prize_type'] == 1 && !$resp['card_id'])
        {
            $request_id = $resp['request_id'];
            if (
                (isset($resp['batch_class']) 
                    && ($resp['batch_class'] == '7' || $resp['batch_class'] == '8' || $resp['batch_class'] == '15')) //话费、q币,流量包
               ) {
               $result = M('tcj_trace')->where(['id' => $resp['cj_trace_id']])->save(['send_mobile' => $mobile]);
               if (!$result) {
                   $this->success(['code' => - 1069]);
               }
               $sendAwardRe = M('tsend_award_trace')
               ->where(['request_id' => $request_id])
               ->save(['deal_flag' => 1, 'phone_no' => $mobile]);
               log_write('afterCjTrace:type = hf&Qb&llb,result = ' . $sendAwardRe . ',request_id = ' . $request_id);
            } else {
                // 如果是微信，并且是卡券奖品
                self::reSendCard($mobile,$resp);
            }
        }
        // 获取奖品详情
        $retInfo = self::getSingleJpInfo($mobile,$resp);
        // 获取剩余抽奖次数
        $retInfo = self::getLastChances($retInfo,$mobile);
        $this->success($retInfo);
    }
    /**
     * [reSendHb 重发红包]
     * @param  [type] $mobile [description]
     * @param  [type] $resp   [description]
     * @return [type]         [description]
     */
    private function reSendHb($mobile,$resp){
        if (! $resp['bonus_use_detail_id']) {
            $this->success(['code'=>- 1069]);
        }
        $bonusUseDetailModel = D('BonusUseDetail');
        $bonusUseDetail = $bonusUseDetailModel->getBonusUseDetail(
            array(
                'id' => $resp['bonus_use_detail_id']), BaseModel::SELECT_TYPE_ONE);
        if (! $bonusUseDetail) {
            $this->success(['code'=>- 1069]);
        }
        $result = $bonusUseDetailModel->updateBonusPhone(
            array(
                'id' => $resp['bonus_use_detail_id']), $mobile);
        if($result === false)
        {
            $this->success(['code'=>- 1069]);
        }
        return true;
    }
    /**
     * [reSendJf 重发积分]
     * @param  [type] $mobile [description]
     * @param  [type] $resp   [description]
     * @return [type]         [description]
     */
    private function reSendJf($mobile,$resp){
        if (! $resp['integral_get_id']) {
            $this->success(['code'=>- 1069]);
        }
        $integalGetDetail = D('IntegalGetDetail')->getIntegalGetDetail(
            array(
                'id' => $resp['integral_get_id']), BaseModel::SELECT_TYPE_ONE);
        log_write(__METHOD__ .' node_id:' . $this->node_id . ' integalGetDetail:'.var_export($integalGetDetail,1));

        if (! $integalGetDetail) {
            $this->success(['code'=>- 1069]);
        }
        $result = D('MemberInstall')->receiveIntegal($integalGetDetail['node_id'],
            $resp['integral_get_id'], $mobile);
        if($result === false)
        {
            $this->success(['code'=>- 1069]);
        }
        return true;
    }
    /**
     * 微信参与的，奖品是普通卡券
     */
    private function reSendCard($mobile,$resp) {
        M()->startTrans();
        // 修改数据库中的手机号字段，并且调用重发接口
        $result = M('tcj_trace')->where(
            array(
                'id' => $resp['cj_trace_id']))->save(
            array(
                'send_mobile' => $mobile));
        if (false === $result) {
            log_write('tcj_trace更新失败，id:' . $this->id . ',resp:' .print_r($resp,true));
            M()->rollback();
            $this->success(['code' => - 1069]);
        }
        // 修改发码表的字段
        $result = M('tbarcode_trace')->where(
            array(
                'request_id' => $resp['request_id']))->save(
            array(
                'phone_no' => $mobile));
        if (false === $result) {
            log_write('tbarcode_trace更新失败，id:' . $this->id . ',resp:' .print_r($resp,true));
            M()->rollback();
            $this->success(['code' => - 1069]);
        }
        M()->commit();
        // 然后调用重发接口
        import("@.Vendor.CjInterface");
        $cjInterFace = new CjInterface();
        $resenData = array(
            'request_id' => $resp['request_id'],
            'node_id'    => $this->node_id,
            'user_id'    => '00000000'
            );
        $result = $cjInterFace->cj_resend($resenData);
        log_write('重发结果：'.print_r($result,true));
        if (! $result || $result['resp_id'] != '0000') {
            log_write('重发失败' . print_r($resp,true) . ',重发的返回结果：' .print_r($result,true));
            $this->success(['code' => - 1069]);
        }
        return true;
    }
    /**
     * [getCjResult 调用抽奖接口并砝码]
     * @param  [type] $mobile   [手机号码]
     * @param  [type] $joinMode [抽奖形式]
     * @return [type]           [抽奖结果]
     */
    private function getCjResult($mobile,$joinMode){
        // 调用抽奖接口
        import('@.Vendor.ChouJiang');
        // 判断抽奖形式，0手机，1微信
        $otherParam = $joinMode ? array(
            'wx_open_id' => $this->wxUserInfo['openid'],
            'wx_nick'    => $this->wxUserInfo['nickname']
            ) : array();
        // 实例化抽奖类对象
        $chouJiang = new ChouJiang($this->id, $mobile, $this->full_id, '',$otherParam);
        $resp = $chouJiang->send_code();
        log_write('渠道id：'.$this->id.'手机号码：'.$mobile.'抽奖结果：'.print_r($resp,true));
        return $resp;
    }
    /**
     * [getSingleJpInfo 获取抽到的奖品信息]
     * @return [type] [array]
     */
    private function getSingleJpInfo($mobile,$resp)
    {
        // 查询goodsinfo
        $strJoinCj    = 'tcj_trace t on t.b_id=b.id';
        $strJoinBatch = 'tbatch_info b on b.goods_id = g.goods_id';
        $map          = array('t.id'=>$resp['cj_trace_id'],'g.node_id'=>$this->node_id);
        $goodsInfo = M()->table("tgoods_info g")
                    ->field('g.*')
                    ->join($strJoinBatch)
                    ->join($strJoinCj)
                    ->where($map)
                    ->find();
        log_write('goodsInfo：'.print_r($goodsInfo,true));
        // 根据奖品类型来显示页面信息
        if(empty($goodsInfo))
        {
            return array();
        }
        $jpInfo['name']    = $goodsInfo['goods_name'];
        $jpInfo['image']   = get_upload_url($goodsInfo['goods_image']);
        $jpInfo['amount']  = $goodsInfo['goods_amt'];
        if($goodsInfo['goods_type'] == '14')
        {
            // 积分
            $jpInfo['jp_type'] = 'jf';
            $jpInfo['image']   = $goodsInfo['goods_image'];
            $jpInfo['amount']  = (int)M()->table("tbatch_info b")->join('tcj_trace c on c.b_id=b.id')->where(array('c.id'=>$resp['cj_trace_id']))->getField('batch_amt');
            $jpInfo['link_url'] = U('Label/Member/index',['node_id'=>$this->node_id]);
        }elseif($goodsInfo['goods_type'] == '12'){
            // 红包
            $jpInfo['jp_type'] = 'hb';
            $jpInfo['link_url'] = M('tbonus_info')->getFieldById($goodsInfo['bonus_id'],'link_url');
        }elseif(!empty($resp['card_id'])){
            // 微信卡券
            $jpInfo['jp_type'] = 'wxcard';
            $jpInfo['card_id'] = $resp['card_id'];
            $jpInfo['card_ext'] = $resp['card_ext'];
            $jpInfo['name'] = M('twx_card_type')->where(['node_id'=>$this->node_id])->getFieldByCard_id($resp['card_id'],'title');
        }else{
            // 卡券
            $jpInfo['jp_type'] = 'card';
        }
        $retCode = array('code'=>0);
        log_write('渠道id：'.$this->id.'手机号码：'.$mobile.'抽奖结果：'.print_r($jpInfo,true));
        return array_merge($jpInfo,$retCode);
    }
    /**
     * [UpdateRecord 统计访问量]
     */
    private function UpdateRecord()
    {
        // 访问量
        import('@.Vendor.DataStat');
        $opt = new DataStat($this->id, $this->full_id);
        $opt->UpdateRecord();
    }
    /**
     * [setLuckRecord 设置中奖记录参数]
     * @param [type] $mobile [description]
     */
    private function setLuckRecord($mobile)
    {
        $other = array('openid'=>$this->wxUserInfo['openid']);
        $cj_url = I('post.cj_url');
        if (empty($cj_url)) {
            $cj_url = U('Label/SpringMonkey/index', array('id' => $this->id, 'wechat_card_js' => 1), '', '', true);
        }
        D('DrawLotteryCommon', 'Service')->setMobileAndGobackUrl($this->id, $other, $mobile, $cj_url);
    }
    /**
     * [getLastChances 获取剩余抽奖次数]
     * @param  [type] $retInfo [奖品信息]
     * @param  [type] $mobile  [手机号码]
     * @return [type]          [数组]
     */
    private function getLastChances($retInfo,$mobile)
    {
        // 获取Model
        $DrawLotteryModel = D('DrawLottery');
        // 得到抽奖规则信息
        $cjResult = $DrawLotteryModel->getCjInfo($this->marketInfo);
        // 初始化每日抽奖次数
        $phoneCountPerDay = 0;
        // 获取每日抽奖次数
        if (isset($cjResult['data']['cj_rule']['phone_day_part'])) {
            $phoneCountPerDay = $cjResult['data']['cj_rule']['phone_day_part'];
        }
        // 获取剩余次数
        $leftChances = $DrawLotteryModel->getDrawLotteryLeftChancesPerDay(
            $this->id, $this->batch_type, $this->marketInfo['id'],$mobile,
            $phoneCountPerDay);
        $drawLotteryChancesInfo = array(
            'leftChances'      => $leftChances,
            'phoneCountPerDay' => $phoneCountPerDay);
        return array_merge($retInfo,$drawLotteryChancesInfo);
    }
    /**
     * [isWhite 判断该手机号码是否是白名单]
     * @param  [type]  $mobile [手机号码]
     * @return boolean         [description]
     */
    private function isWhite($mobile){
        $map = array(
            'phone_no' => $mobile,
            'node_id'  => $this->node_id,
            'status'   => '1'
            );
        M()->startTrans();
        $ret = M('tcj_white_blacklist')->lock(true)->where($map)->find();
        if(!empty($ret)){
            $ret = M('tcj_white_blacklist')->where($map)->save(['status'=>'2']);
            if($ret === false){
                M()->rollback();
                return false;
            }else{
                M()->commit();
                return true;
            }
        }else{
            M()->rollback();
            return false;
        }
    }
}
