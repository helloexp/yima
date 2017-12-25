<?php
// 首页
class IndexAction extends BaseAction {
    /**
     * [beforeCheckAuth 提前校验权限]
     */
    public function beforeCheckAuth() {
        if (ACTION_NAME == 'index') {
            $this->_authAccessMap = '*';
        }
    }
    public function index(){
        // 新老入口切换
        $index_flag = I('get.useold');
        if($index_flag == 2)
            cookie('useoldck',null);
        if($index_flag == 1 || cookie('useoldck'))
            self::oldindex();
        // 如果有非标，或者特殊判断的，请写在specialRedirect中
        self::specialRedirect();
        // 商户头像
        $headImg = $this->userInfo['head_photo'];
        if($headImg)
            $headImg = get_upload_url($headImg);
        else
            $headImg = "__PUBLIC__/Image/portrait.gif";
        // 是否VIP
        if($this->userInfo['node_type'] == '2' && $this->userInfo['check_status'] != '2')
            $VIP = 0;
        else
            $VIP = 1;
        // 是否认证，及认证链接，及充值链接
        $certFlag  = $this->userInfo['wc_version'] == 'v0';
        $certUrl   = C('CERTIF_URL').$this->userInfo['token'];
        $chargeUrl = C('YZ_RECHARGE_URL')."&node_id=".$this->nodeId."&name=".$this->userInfo['user_name']."&token=".$this->userInfo['token'];
        // 企业旺号，机构名称
        $clientId = $this->userInfo['client_id'];
        $clientId = str_pad($clientId,6,0,STR_PAD_LEFT);
        $nodeName = $this->userInfo['node_name'];
        // 账户余额，旺币余额
        $accountInfo = $this->getAccountInfo();
        $AccBalance = number_format($accountInfo['AccountPrice'],2);
        $WbBalance  = number_format($accountInfo['WbPrice'],2);
        // 是否显示充值按钮
        if(in_array($this->userInfo['pay_type'],['0','1']) && $this->userInfo['node_type'] <= 2)
            $showChargeBtn = 1;
        else
            $showChargeBtn = 0;
        // 总访问量、粉丝数、商品数量、卡券数量、可提现金额
        $headStatistics = self::getHeadStatistics();
        // 首页中，六大图片板块
        $promotion = self::getPromotionForIndex();
        // 常用功能区域，只显示5个经常使用
        $popularModule = D('ClickSum')->getPopularModule($this->nodeId);
        // 获取公告信息
        $sysmsglist = self::getSysMsgList();

        $this->assign('headImg',$headImg);
        $this->assign('VIP',$VIP);
        $this->assign('AccBalance',$AccBalance);
        $this->assign('WbBalance',$WbBalance);
        $this->assign('showChargeBtn',$showChargeBtn);
        $this->assign('headStatistics',$headStatistics);
        $this->assign('promotion',$promotion);
        $this->assign('popularModule',$popularModule);
        $this->assign('sysmsglist',$sysmsglist);
        $this->assign('clientId',$clientId);
        $this->assign('nodeName',$nodeName);
        $this->assign('certFlag',$certFlag);
        $this->assign('certUrl',$certUrl);
        $this->assign('chargeUrl',$chargeUrl);
        $this->display();
    }
    /**
     * [specialRedirect 特殊情况的处理]
     * @return [type] [null]
     */
    private function specialRedirect()
    {
        if ($this->nodeId == C('Yhb.node_id')) { // 翼惠宝帐号登录跳转
            header("location:index.php?g=Yhb&m=Index&a=index");
        }
        if ($this->nodeId == C('cnpc_gx.node_id')) { // 翼惠宝帐号登录跳转
            header("location:index.php?g=CnpcGX&m=Index&a=index");
        }
        if ($this->nodeId == C('Zggk.node_id')) { // 中港高科登录跳转
            header("location:index.php?g=Zggk&m=Index&a=index");
        }
        // 是否显示报表管理的菜单
        if($this->userInfo['node_id'] == C('gansu.node_id'))
        {
            if($this->new_role_id == C('gansu.role_id'))
                $ReportUrl = U('ReportManagement/StaffManagement/salesReport');
            else
                $ReportUrl = U('ReportManagement/StaffManagement/index');
            $this->assign('ReportUrl',$ReportUrl);
        }
        // 是否显示光平治疗管理的菜单
        if($this->new_role_id==2 && $this->userInfo['node_id']==C('GpEye.node_id'))
            $this->assign('TreatmentUrl',U('GpEye/Treatment/index'));
    }
    private function getHeadStatistics()
    {
        // 查询活动类型
        $map = array();
        $map['status'] = '1';
        $info = M('tmarketing_active')->field('batch_type,batch_name,batch_order')->where($map)->order('batch_order asc')->select();
        $batchType = array();
        foreach ($info as $v) {
            $batchType[$v['batch_type']] = $v['batch_name'];
            // 只有翼码市场部的可以看到注册有礼
            if($v['batch_type'] == '32' && $this->node_id != '00014056'){
                unset($batchType[$v['batch_type']]);
            }
        }
        // 获取访问量数据统计
        $map = array();
        $map['node_id']    = $this->nodeId;
        $map['batch_type'] =  array('in',implode(array_keys($batchType), ','));
        $field = "SUM(click_count) AS  pv";
        $list = M('tdaystat')->field($field)->where($map)->select();
        // 获取微信粉丝数据统计
        $weixinInfo = D('tweixin_info')->where(['node_id'=>$this->nodeId,'status'=>0])->find();
        $memberSum = M('twx_user')->where(['node_id'=>$this->nodeId,'node_wx_id'=>$weixinInfo['node_wx_id'],'subscribe'=>1])->count();
        // 头部会员管理的链接
        $huiyuansum = M('tmember_info')->where(['node_id' => $this->nodeId])->count();
        $ispowermember = $this->_checkUserAuth('Wmember/Member/index');
        if($ispowermember && $huiyuansum > 0)
            $MemberUrl = U('Wmember/Member/index');
        else
            $MemberUrl = U('Wmember/Member/promotionn4880');
        $this->assign('MemberUrl',$MemberUrl);
        // 获取卡券数据统计，自建的数量与购买的数量
        $goodsTypeNumSelf = D('Goods')->getGoodsNum(
            array(
                'exp', 
                "in ({$this->nodeIn()})"), '0',array('status'=>0));
        $goodsTypeNumBuy = D('Goods')->getGoodsNum(
            array(
                'exp', 
                "in ({$this->nodeIn()})"), '1',array('status'=>0));
        $goodsNum = 0;
        foreach ($goodsTypeNumSelf as $key=>$value) {
            if(in_array($key,[0,1,2,3]))
                $goodsNum += $value;
        }
        foreach ($goodsTypeNumBuy as $key=>$value) {
            if(in_array($key,[0,1,2,3,7,8,15]))
                $goodsNum += $value;
        }
        // 微信卡券数量，也要统计到卡券总数中
        $wxCardFriendNum = M('twx_card_type')->where(['node_id'=>$this->nodeId])->count();
        $goodsNum += $wxCardFriendNum;
        // 微信红包，也要统计到卡券总数中
        $weiXinBagNum = M('tgoods_info')->where(['node_id'=>$this->nodeId,'source'=>['in','6,7']])->count();
        $goodsNum += $weiXinBagNum;
        // 微博卡券，也要统计到卡券总数中
        $weiboNum = M("tweibo_card_type w")->where(['node_id'=>$this->nodeId])->count();
        $goodsNum += $weiboNum;
        // 商品总数
        $where = array(
            't.node_id' => $this->nodeId, 
            't.status'  => '0',
            't.is_delete' => 0,  // 显示未删除的信息
            'm.batch_type' => array('in',['26','27','31']));
        $goodsTotal = M()->table('tbatch_info as t')
            ->field('a.*,t.*')
            ->join('tecshop_goods_ex as a ON a.node_id=t.node_id and t.id = a.b_id')
            ->join('tmarketing_info as m ON m.id=t.m_id')
            ->where($where)
            ->count();
        // 可提现金额
        $allowMoney = D('HomeCm','Service')->getAllowCash($this->nodeId);
        return array_merge(
                array('visitNum'   => $list[0]['pv']),
                array('memberSum'  => $memberSum),
                array('goodsNum'   => $goodsNum),
                array('goodsTotal' => $goodsTotal),
                array('allowMoney' => $allowMoney));
    }

    private function getPromotionForIndex()
    {
        // 商户标签
        $nodeTag = M('tnode_tag')->getByNode_id($this->nodeId);
        $config['node_tag'] = empty($nodeTag) ? array() : $nodeTag;
        // 翼码标签
        $ymTag = M('tbusiness_recommend_new')->select();
        $config['ym_tag'] = empty($ymTag) ? array() : $ymTag;
        // 商户点击记录
        $config['visit_trace'] = M('tnode_recommend_visit')->where(['node_id'=>$this->nodeId])->select();
        // 调用算法
        $algorithm = D('Algorithm');
        $algorithm->Init($config);
        return $algorithm->getResult(7);
    }
    private function getSysMsgList()
    {
        $sql = "SELECT * FROM (
            SELECT tmessage_news.id,tmessage_news.is_special,tmessage_news.title,tmessage_news.content,tmessage_recored.add_time AS add_time,
            tmessage_recored.seq_id,tmessage_recored.status,tmessage_news.msg_type
            FROM `tmessage_news` LEFT JOIN tmessage_recored ON tmessage_news.id = tmessage_recored.message_id
             WHERE ( tmessage_news.status = '0' )  AND ( node_id='$this->node_id' ) AND(tmessage_recored.add_time > '$time') AND(tmessage_recored.status = '0')
            UNION
            SELECT tmessage_news.id,tmessage_news.is_special,tmessage_news.title,tmessage_news.content,tmessage_news.add_time AS add_time,
            NULL,'0',tmessage_news.msg_type
            FROM `tmessage_news`  WHERE ( tmessage_news.status = '0' ) AND id NOT IN (  SELECT message_id FROM `tmessage_recored` WHERE node_id='$this->nodeId' ) AND(tmessage_news.add_time > '$time') AND send_to_who = '1'
            ) a ORDER BY a.add_time DESC";
        
        $sysmsglist1 = M()->query($sql);
        
        // 取两周之内所有已读的特殊公告
        $sql2 = "SELECT m.id,m.is_special,m.title,r.add_time AS add_time, r.seq_id
            FROM tmessage_news m LEFT JOIN tmessage_recored r ON r.message_id = m.id
            WHERE r.node_id = '$this->node_id' AND m.is_special = '1' AND m.msg_type<>'0' AND m.status='0' AND r.status = '1' AND r.add_time>'$time'";
        
        $sysmsglist2 = M()->query($sql2);
        
        // 合并两个结果
        $sysmsglist = array_merge($sysmsglist1, $sysmsglist2);
        
        return array_chunk($sysmsglist, 2);
    }
    /**
     * [promotionClick 促销信息的点击]
     * @return [type] [json]
     */
    public function promotionClick(){
        $id = I('post.id');
        $ret = M('tbusiness_recommend_new')->where(['id'=>$id])->setInc('click_cnt');
        if($ret === false)
            $this->error('数据存储错误，错误码：0x0100');
        $ret = M('tnode_recommend_visit')->where(['node_id'=>$this->nodeId,'recommend_id'=>$id])->find();
        if(empty($ret))
        {
            $data = [
                'node_id'          => $this->nodeId,
                'recommend_id'     => $id,
                'first_visit_time' => date('YmdHis')];
            $ret = M('tnode_recommend_visit')->add($data);
            if(!$ret)
                $this->error('数据存储错误，错误码：0x0200');
        }
        $this->success('enable to transfer!');
    }
    public function oldindex() {
        cookie('useoldck','1');
        $flower = null;
        $card_name = null;
        $card_email = null;
        $card_phone = null;
        if ($this->nodeId == C('Yhb.node_id')) { // 翼惠宝帐号登录跳转
            header("location:index.php?g=Yhb&m=Index&a=index");
        }
        if ($this->nodeId == C('cnpc_gx.node_id')) { // 翼惠宝帐号登录跳转
            header("location:index.php?g=CnpcGX&m=Index&a=index");
        }
        // 帮助提示层状态
        $helpStatus = M('tuser_info')->where(
            "node_id='{$this->nodeId}' AND user_id={$this->userId}")->getField(
            'help_tip_status');
        if (! $helpStatus)
            $helpStatus = 0;
        $this->assign('helpStatus', $helpStatus);
        
        $currdate = date('Ymd');
        $curr_date = date('Y-m-d');
        $currtime = date('Ymd') . "000000";
        $starttime = date('Ymd') . "235959";
        
        $nodename = M('tnode_info')->field(
            'client_id,node_id,node_name,pay_type,node_type,check_status,check_memo,contract_no,head_photo')
            ->where("node_id='" . $this->nodeId . "'")
            ->find();
        $this->assign('nodeid', $this->nodeId);
        $userService = D('UserSess', 'Service');
        $userInfo = $userService->getUserInfo();
        
        // 查询用户角色
        $userRole = M('tuser_info')->field('role_id')
            ->where(
            "node_id='" . $this->nodeId . "' AND user_id='" .
                 $userInfo['user_id'] . "'")
            ->find();
        // 多乐互动
        $model = M('tmarketing_info');
        
        // 进行中的活动
        $batchNum = $model->where(
            array(
                'node_id' => $this->node_id))->count();
        $batchNum = is_null($batchNum) ? 0 : $batchNum;
        
        // 今日活动总访问量
        $batchingNum = M()->table('tmarketing_info m')
            ->join('tdaystat t ON m.id=t.batch_id')
            ->where(
            "m.node_id='{$this->nodeId}' and m.status=1 and t.day='{$currdate}'")
            ->sum('t.click_count');
        $batchingNum = is_null($batchingNum) ? 0 : $batchingNum;
        
        // 有效期内的卡券
        // $numGoodsSum = M('tbatch_info')->where("node_id='{$this->nodeId}' AND
        // batch_type=0")->count();
        $numGoodsSum = M('tgoods_info')->where(
            "node_id='{$this->nodeId}' and begin_time<='{$starttime}' and  end_time>='{$currtime}'")->count();
        
        // 发码总数
        $sendSum = M('tpos_day_count')->where("node_id='{$this->nodeId}'")->sum(
            'send_num');
        $sendSum = is_null($sendSum) ? 0 : $sendSum;
        
        // 渠道
        $channelSum = M('tchannel')->where("node_id='{$this->nodeId}'")->count();
        $clickCount = M('tchannel')->where("node_id='{$this->nodeId}'")->sum(
            'click_count');
        $clickCount = is_null($clickCount) ? 0 : $clickCount;
        
        // 粉丝总数
        // $memberSum =
        // M('tmember_info')->where("node_id='{$this->nodeId}'")->count();
        
        // 今日新增粉丝
        // $daymember = M('tmember_info')->where("node_id='{$this->nodeId}' and
        // left(add_time,8)='{$currdate}'")->count();
        
        if ($nodename['contract_no'] != "") {
            $req_array = array(
                'QueryJsUserReq' => array(
                    'ContractID' => $nodename['contract_no']));
            Log::write("查询客户经理=" . $nodename['contract_no']);
            $RemoteRequest = D('RemoteRequest', 'Service');
            $reqResult = $RemoteRequest->requestYzServ($req_array);
            // print_r($reqResult);
            if ($reqResult['Status']['StatusCode'] == '0000') {
                $card_name = $reqResult['NAME'];
                session("card_name", $card_name);
                $card_email = $reqResult['EMAIL'];
                session("card_email", $card_email);
                $card_phone = $reqResult['PHONE_CODE'];
                session("card_phone", $card_phone);
            }
        }
        
        if (checkUserRights($this->nodeId, C("MEMBER_CHARGE_ID")) ||
             checkUserRights($this->nodeId, C("BASIC_CHARGE_ID"))) {
            $flower = 1;
        }
        
        // 查询最新4条营销平
        // $map['A.batch_type']='3';
        // $map['A.check_status']='1';
        // $map['A.status']=array('eq','0');
        
        // $lastBatch=M()->table('tbatch_info
        // A')->field('id,batch_img')->where($map)->order('id
        // desc')->limit('0,4')->select();
        
        // 取活动总访问量
        $visitmap['node_id'] = $this->nodeId;
        $visitSum = M()->table('tmarketing_info')
            ->where($visitmap)
            ->sum('click_count');
        if ($visitSum == "") {
            $visitSum = 0;
        }
        
        // 当前时间减七天
        /*
         * $dates = time() - 7 * 86400; $sttime=date('Ymd',$dates)."000000";
         * $edtime=date('Ymd')."235959"; //取显示一周内点击量最高的活动
         * $clickList=M()->table('tbatch_channel
         * a')->field("a.id,c.name,a.batch_id,a.batch_type,a.click_count")
         * ->join('tchannel b ON a.channel_id=b.id') ->join('tmarketing_info c
         * ON a.batch_id = c.id') ->where("b.type=1 AND b.sns_type=12 AND
         * a.status ='1' AND b.status = '1' AND c.status = '1' and
         * c.start_time<='".$sttime."' and c.end_time>='".$edtime."'")
         * ->order("a.click_count desc") ->limit("0,1")->find();
         * $this->assign('clickList',$clickList);
         */
        $this->assign('batch_name_arr', C("BATCH_TYPE_NAME"));
        
        // 查询商户首次登录时间
        $RemoteRequest = D('RemoteRequest', 'Service');
        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
                                                                   // 商户首次登录信息查询报文
        $req_array = array(
            'CertifQueryReq' => array(
                'TransactionID' => $TransactionID, 
                'SystemID' => C('YZ_SYSTEM_ID'), 
                'ClientID' => $nodename['client_id']));
        $firstInfo = $RemoteRequest->requestYzServ($req_array);
        if (empty($firstInfo['FirstLoginWCTime'])) {
            $first_time = M('tuser_info')->where("node_id='{$this->nodeId}'")->min(
                'first_time');
            
            if ($first_time) {
                // 商户首次登录信息设置报文
                $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
                $req_array = array(
                    'CertifSetReq' => array(
                        'TransactionID' => $TransactionID, 
                        'SystemID' => C('YZ_SYSTEM_ID'), 
                        'ClientID' => $nodename['client_id'], 
                        'CertifDate' => '', 
                        'FirstLoginWCTime' => $first_time));
                $FirstTimeSet = $RemoteRequest->requestYzServ($req_array);
            }
        }
        
        // 查询公开课信息
        // $o2o_info = M('tmessage_tmp')->find();
        $o2o_info = M('tmessage_tmp')->cache(true, 600, 'file')
            ->where(
            array(
                'apply_time' => array(
                    'egt', 
                    date('YmdHis'))))
            ->order('apply_time')
            ->find();
        
        // 判断是否来自手机注册，如果是的话，就提示他开通门店
        $nRegFrom = M('tnode_info')->getFieldByNode_id($this->nodeId, 
            'reg_from');
        // 判断用户是否创建过门店
        $isCreateStore = M('tstore_info')->where(
            array(
                'node_id' => $this->nodeId, 
                'type' => 1))->count();
        
        // 热门场景营销推广图标
        $hotModel = D('HotScene');
        $sceneList = $hotModel->hotScene($this->node_id);
        
        // 更新提示
        // 获取系统消息的滚动部分 只显示发送时间在2周之内的未读消息
        // 特殊公告不管是否已读。都要滚动2周
        $mintime = date("YmdHis", strtotime(date('YmdHis')) - 14 * 24 * 60 * 60);
        $results = M('tnode_info')->where("node_id = $this->nodeId")
            ->field('add_time')
            ->find();
        $nodeIdAddTime = date("YmdHis", strtotime($results['add_time'])); // node_id创建时间
                                                                          // 用2周时间和账号创建时间相比较
        if ($nodeIdAddTime > $mintime) {
            $time = $nodeIdAddTime;
        } elseif ($mintime > $nodeIdAddTime) {
            $time = $mintime;
        }
        // 取两周之内所有未读消息排除特别公告
        // 不能排除特别公告 因为如果公告未读的话是不会出现在另一个表里的
        // 那就取两周之内所有未读消息
        $sql = "SELECT * FROM (
SELECT tmessage_news.id,tmessage_news.is_special,tmessage_news.title,tmessage_news.content,tmessage_recored.add_time AS add_time,
tmessage_recored.seq_id,tmessage_recored.status,tmessage_news.msg_type
FROM `tmessage_news` LEFT JOIN tmessage_recored ON tmessage_news.id = tmessage_recored.message_id
 WHERE ( tmessage_news.status = '0' )  AND ( node_id='$this->node_id' ) AND(tmessage_recored.add_time > '$time') AND(tmessage_recored.status = '0')
UNION
SELECT tmessage_news.id,tmessage_news.is_special,tmessage_news.title,tmessage_news.content,tmessage_news.add_time AS add_time,
NULL,'0',tmessage_news.msg_type
FROM `tmessage_news`  WHERE ( tmessage_news.status = '0' ) AND id NOT IN (  SELECT message_id FROM `tmessage_recored` WHERE node_id='$this->nodeId' ) AND(tmessage_news.add_time > '$time') AND send_to_who = '1'
) a ORDER BY a.add_time DESC";
        
        $sysmsglist1 = M()->query($sql);
        
        // 然后再取两周之内所有已读的特殊公告
        $sql2 = "SELECT m.id,m.is_special,m.title,r.add_time AS add_time, r.seq_id
FROM tmessage_news m LEFT JOIN tmessage_recored r ON r.message_id = m.id
WHERE r.node_id = '$this->node_id' AND m.is_special = '1' AND m.msg_type<>'0' AND m.status='0' AND r.status = '1' AND r.add_time>'$time'";
        
        $sysmsglist2 = M()->query($sql2);
        
        // 合并两个结果
        $sysmsglist = array_merge($sysmsglist1, $sysmsglist2);
        
        $sysmsglistbak = array_chunk($sysmsglist, 2);
        
        $this->assign('sysmsglist', $sysmsglistbak);
        $this->editHelpStatus($helpStatus);
        $this->assign('o2o_info', $o2o_info);
        // $this->assign('daymember',$daymember);
        $this->assign('visitSum', $visitSum);
        $this->assign('nRegFrom', $nRegFrom);
        $this->assign('isCreateStore', $isCreateStore);
        $this->assign('userRole', $userRole);
        // $this->assign('lastBatch',$lastBatch);
        $this->assign('flower', $flower);
        $this->assign('card_name', $card_name);
        $this->assign('card_email', $card_email);
        $this->assign('card_phone', $card_phone);
        $this->assign('batchNum', $batchNum);
        $this->assign('batchingNum', $batchingNum);
        $this->assign('numGoodsSum', $numGoodsSum);
        $this->assign('sendSum', $sendSum);
        $this->assign('channelSum', $channelSum);
        $this->assign('clickCount', $clickCount);
        // $this->assign('memberSum',$memberSum);
        $this->assign('node', $nodename);
        $this->assign('nodename', $nodename['node_name']);
        $this->assign('nodeInfo', $nodename);
        $this->assign('clientid', str_pad($nodename['client_id'], 6, '0', STR_PAD_LEFT));
        $this->assign('nodetype', $nodename['node_type']);
        $this->assign('FlowInfo', $this->getAccountInfo());
        $this->assign('hotList', $sceneList['hotList']);
        $this->assign('clickList', $sceneList['clickList']);
        // /////////////////////////////////////////////////////////////////////////////
        // //非标页面处理
        $tpl = null;
        
        if ($this->node_id == C('df.node_id')) {
            $this->assign('fb_type', 'df');
        }
        if ($this->node_id == C('onlinesee.node_id')) {
            $this->assign('fb_type', 'onlinesee');
        }
        
        // /////////////////////////////////////////////////////////////////////////////
        
        // 是否当日第一次登录
        $today_first_login = false;
        if (session('?today_first_login') &&
             session('today_first_login') === true) {
            $today_first_login = true;
            session('today_first_login', null);
        }
        $this->assign('today_first_login', $today_first_login);
        
        // 劳动最光荣首页弹框控制
        $flag = date('Ymd') <= '20150509' && $today_first_login &&
             ($this->wc_version == 'v0' || $this->wc_version == 'v0.5');
        $this->assign('laborday_popwin_flag', $flag);
        $saleList = M('tsale_node_relation')->where(
            "node_id='{$this->node_id}'")->find();
        $this->assign('saleList', $saleList);

        $this->display('oldindex');
        exit;
    }
    
    // 创建多乐互动
    public function marketingShow() {
        $userService = D('UserSess', 'Service');
        $userInfo = $userService->getUserInfo();
        $aiPaiUrl = C('AI_PAI_URL') .
             "shop_id='{$this->nodeId}'&shop_name={$userInfo['name']}";
        // 可验证门店数量
        
        // 检查开通权限
        
        if (checkUserRights($this->nodeId, C("MEMBER_CHARGE_ID")) ||
             checkUserRights($this->nodeId, C("BASIC_CHARGE_ID"))) {
            $flower = 1;
        }
        $this->assign('flower', $flower);
        
        $this->assign('aiPaiUrl', $aiPaiUrl);
        $this->display();
    }

    public function marketingShow1() {
        $this->display();
    }

    public function marketingShow2() {
        // 真假大冒险
        $galink = "&model=event&type=315theme&customer=" . $this->node_type_name;
        // 女人我最大
        $womenlink = "&model=event&type=38theme&customer=" .
             $this->node_type_name;
        // 天生一对
        $valenlink = "&model=event&type=valentine&customer=" .
             $this->node_type_name;
        // 马上有红包
        $pakagelink = "&model=event&type=envelope&customer=" .
             $this->node_type_name;
        $this->assign('galink', $galink);
        $this->assign('womenlink', $womenlink);
        $this->assign('valenlink', $valenlink);
        $this->assign('pakagelink', $pakagelink);
        $this->display();
    }

    public function marketingShow5() {
        $channelInfo = M('tchannel')->where(
            array(
                'node_id' => $this->node_id, 
                'type' => '4', 
                'sns_type' => '46'))->find();
        if (! $channelInfo) { // 不存在则添加渠道
            $channel_arr = array(
                'name' => '旺财小店默认渠道', 
                'type' => '4', 
                'sns_type' => '46', 
                'status' => '1', 
                'start_time' => date('YmdHis'), 
                'end_time' => date('YmdHis', strtotime("+1 year")), 
                'add_time' => date('YmdHis'), 
                'click_count' => 0, 
                'cj_count' => 0, 
                'send_count' => 0, 
                'node_id' => $this->node_id);
            
            $cid = M('tchannel')->add($channel_arr);
            if (! $cid) {
                $this->error('初始化旺财小店默认渠道失败');
            }
        }
        /* START 中秋砍价活动提现 */
        // 取得用户权限信息
        $nodeInfo = M('tnode_info')->where(
            array(
                'node_id' => $this->node_id))
            ->field('wc_version')
            ->find();
        // 判断是否为电商正是用户
        $getCashStatus = 0;
        if (! $this->hasPayModule('m2')) {
            $batchInfo = M('tbatch_info')->field('end_time')
                ->where(
                array(
                    'node_id' => $this->node_id, 
                    'batch_type' => '0'))
                ->find();
            if (false != $batchInfo) {
                $nowTime = time();
                if (isset($batchInfo['end_time'])) {
                    $getCashTime = strtotime($batchInfo['end_time']) +
                         3600 * 24 * 14;
                    if ($nowTime < $getCashTime) {
                        $getCashStatus = 1;
                    }
                }
            }
        } else {
            $getCashStatus = 3;
        }
        /* END 中秋砍价活动提现 */
        $marketInfo = M('tmarketing_info')->where(
            array(
                'batch_type' => '29', 
                'node_id' => $this->node_id))->find();
        if (! $marketInfo) { // 不存在则自动新建该多乐互动
            $m_arr = array(
                'batch_type' => '29', 
                'name' => '旺财小店', 
                'status' => '1', 
                'start_time' => date('YmdHis'), 
                'end_time' => date('YmdHis', strtotime("+10 year")), 
                'add_time' => date('YmdHis'), 
                'click_count' => 0, 
                'cj_count' => 0, 
                'send_count' => 0, 
                'node_id' => $this->node_id, 
                'batch_come_from' => session('batch_from') ? session(
                    'batch_from') : '1');
            
            $m_id = M('tmarketing_info')->add($m_arr);
            if (! $m_id) {
                $this->error('初始化旺财小店失败');
            }
        }
        
        $node_info = M('tnode_info')->where(
            array(
                'node_id' => $this->node_id))->find();
        // 累计访问量
        $all_click = M('tmarketing_info')->where(
            array(
                'node_id' => $this->node_id, 
                'batch_type' => array(
                    'in', 
                    '26,27,29,31')))->getField('sum(click_count)');
        // 累计订单数
        $order_count = M('ttg_order_info')->where(
            array(
                'node_id' => $this->node_id, 
                'order_status' => '0', 
                'pay_status' => '2'))->count();
        // 待发货订单数
        $needSendOrderCount = M('ttg_order_info')->where(
            array(
                'node_id' => $this->node_id, 
                'order_status' => '0', 
                'delivery_status' => '1',
                //'other_type' => '0',     //订购订单
                'receiver_type' => '1'))->count();

        if (!$needSendOrderCount)
            $needSendOrderCount = 0;
        // 累计成交额
        $order_amount = M('ttg_order_info')->where(
            array(
                'node_id' => $this->node_id, 
                'order_status' => '0', 
                'pay_status' => '2'))->getField('sum(order_amt)');
        if (! $order_amount)
            $order_amount = 0;
            // 正在销售的商品
        $sale_goods = M('tgoods_info')->where(
            array(
                'node_id' => $this->node_id, 
                'goods_type' => '6'))->count();
        
        // 获取收款账户信息
        $model = M('tnode_account');
        $nodeAccountInfo = $model->where(
            array(
                'node_id' => $this->node_id))->select();
        $nodeAccountInfo = array_valtokey($nodeAccountInfo, 'account_type');
        // 小店的m_id
        $m_id = $marketInfo['id'];
        // $m_id =
        // M('tmarketing_info')->where(array('node_id'=>$this->node_id,'batch_type'=>'29'))->getField('id');
        
        // 店铺地址
        $channel_id = M('tchannel')->where(
            array(
                'node_id' => $this->node_id, 
                'type' => '4', 
                'sns_type' => '46'))->getField('id');
        $label_id = get_batch_channel($m_id, $channel_id, '29', $this->node_id);
        
        $today = date('Ymd');
        $yesterday = date('Ymd', strtotime("-1 day"));
        $batch_type = '29';
        $batch_id = $m_id;
        $_get = I('get.');
        // 查询
        $_get['begin_date'] = $begin_date = I('begin_date', 
            dateformat("-30 days", 'Ymd'));
        $_get['end_date'] = $end_date = I('end_date', 
            dateformat("0 days", 'Ymd'));
        $map = array(
            'batch_type' => $batch_type, 
            'batch_id' => $batch_id, 
            'node_id' => $this->node_id);
        
        // 查询日期
        $map['day'] = array();
        if ($begin_date != '') {
            $map['day'][] = array(
                'EGT', 
                $begin_date);
        }
        if ($end_date != '') {
            $map['day'][] = array(
                'ELT', 
                $end_date);
        }
        
        $shop_jsChartDataClick = array(); // 小店PV访问量
        $shop_jsChartDataOrder = array(); // 小店订单数
        $shop_jsChartDataAmt = array(); // 小店销售额
        $shop_data = array(
            'PV' => array(
                date('Ymd') => 0, 
                date('Ymd', strtotime("-1 day")) => 0), 
            'order' => array(
                date('Ymd') => 0, 
                date('Ymd', strtotime("-1 day")) => 0), 
            'saleamt' => array(
                date('Ymd') => 0, 
                date('Ymd', strtotime("-1 day")) => 0));
        
        // 小店访问量
        $pv_arr = M('Tdaystat')->where($map)
            ->field("batch_type,batch_id,day,sum(click_count) click_count")
            ->group("day")
            ->select();
        // 小店-计算出JS值
        foreach ($pv_arr as $v) {
            $shop_jsChartDataClick[$v['day']] = array(
                $v['day'], 
                $v['click_count'] * 1);
            if ($v['day'] == $today)
                $shop_data['PV'][$today] = $v['click_count'] * 1;
            if ($v['day'] == $yesterday)
                $shop_data['PV'][$yesterday] = $v['click_count'] * 1;
        }
        // 小店订单数
        $order_map = array(
            'order_type' => '2', 
            'order_status' => '0', 
            'pay_status' => '2', 
            'node_id' => $this->node_id);
        // 小店查询日期
        $order_map['add_time'] = array();
        if ($begin_date != '') {
            $order_map['add_time'][] = array(
                'EGT', 
                $begin_date . '000000');
        }
        if ($end_date != '') {
            $order_map['add_time'][] = array(
                'ELT', 
                $end_date . '235959');
        }
        $order_arr = M("ttg_order_info")->field(
            "count(order_id) as order_count,substr(add_time,'1',8) as day,sum(order_amt) as order_amt")
            ->where($order_map)
            ->group("substr(add_time,1,8)")
            ->select();
        
        // 小店-计算出JS值
        foreach ($order_arr as $v) {
            $shop_jsChartDataOrder[$v['day']] = array(
                $v['day'], 
                $v['order_count'] * 1);
            $shop_jsChartDataAmt[$v['day']] = array(
                $v['day'], 
                $v['order_amt'] * 1);
            
            if ($v['day'] == $today) {
                $shop_data['order'][$today] = $v['order_count'] * 1;
                $shop_data['saleamt'][$today] = $v['order_amt'] * 1;
            }
            if ($v['day'] == $yesterday) {
                $shop_data['order'][$yesterday] = $v['order_count'] * 1;
                $shop_data['saleamt'][$yesterday] = $v['order_amt'] * 1;
            }
        }
        
        // 单品销售显示30天数据
        $_get['begin_date2'] = $begin_date2 = I('begin_date', 
            dateformat("-30 days", 'Ymd'));
        // 单品销售  单品销售PV访问量  单品销售订单数  单品销售销售额
        $sg_jsChartDataClick = $sg_jsChartDataOrder = $sg_jsChartDataAmt = $msm_jsChartDataClick = $msm_jsChartDataOrder = $msm_jsChartDataAmt = array();
        $sg_data = array(
            'PV' => array(
                date('Ymd') => 0, 
                date('Ymd', strtotime("-1 day")) => 0), 
            'order' => array(
                date('Ymd') => 0, 
                date('Ymd', strtotime("-1 day")) => 0), 
            'saleamt' => array(
                date('Ymd') => 0, 
                date('Ymd', strtotime("-1 day")) => 0));
        // 单品销售访问量
        $sg_map = array(
            'batch_type' => array(
                "in", 
                "26,27"), 
            'node_id' => $this->node_id);
        
        // 查询日期
        $sg_map['day'] = array();
        if ($begin_date2 != '') {
            $sg_map['day'][] = array(
                'EGT', 
                $begin_date2);
        }
        if ($end_date != '') {
            $sg_map['day'][] = array(
                'ELT', 
                $end_date);
        }
        $pv_arr2 = M('Tdaystat')->where($sg_map)
            ->field("batch_type,day,sum(click_count) click_count")
            ->group("day")
            ->select();
        // 单品销售-计算出JS值
        foreach ($pv_arr2 as $v) {
            $sg_jsChartDataClick[$v['day']] = array(
                $v['day'], 
                $v['click_count'] * 1);
            if ($v['day'] == $today)
                $sg_data['PV'][$today] = $v['click_count'] * 1;
            if ($v['day'] == $yesterday)
                $sg_data['PV'][$yesterday] = $v['click_count'] * 1;
        }
        // 单品销售订单数
        $order_map2 = array(
            'order_type' => '0', 
            'order_status' => '0', 
            'pay_status' => '2', 
            'node_id' => $this->node_id);
        // 单品销售查询日期
        $order_map2['add_time'] = array();
        if ($begin_date2 != '') {
            $order_map2['add_time'][] = array(
                'EGT', 
                $begin_date2 . '000000');
        }
        if ($end_date != '') {
            $order_map2['add_time'][] = array(
                'ELT', 
                $end_date . '235959');
        }
        $order_map2['batch_no'] = array(
            'exp', 
            "in (select id from tmarketing_info where batch_type='26' and node_id='{$this->node_id}')");
        $order_arr2 = M("ttg_order_info")->field(
            "count(order_id) as order_count,substr(add_time,'1',8) as day,sum(order_amt) as order_amt")
            ->where($order_map2)
            ->group("substr(add_time,1,8)")
            ->select();
        // 单品销售-计算出JS值
        foreach ($order_arr2 as $v) {
            $sg_jsChartDataOrder[$v['day']] = array(
                $v['day'], 
                $v['order_count'] * 1);
            $sg_jsChartDataAmt[$v['day']] = array(
                $v['day'], 
                $v['order_amt'] * 1);
            
            if ($v['day'] == $today) {
                $sg_data['order'][$today] = $v['order_count'] * 1;
                $sg_data['saleamt'][$today] = $v['order_amt'] * 1;
            }
            if ($v['day'] == $yesterday) {
                $sg_data['order'][$yesterday] = $v['order_count'] * 1;
                $sg_data['saleamt'][$yesterday] = $v['order_amt'] * 1;
            }
        }
        // 补齐点状图数据
        // 闪购
        foreach ($sg_jsChartDataClick as $k => $v) {
            if (!isset($sg_jsChartDataOrder[$k]))
                $sg_jsChartDataOrder[$k] = array($v[0],0);
            if (!isset($sg_jsChartDataAmt[$k]))
                $sg_jsChartDataAmt[$k] = array($v[0],0);
        }
        // 码上买
        foreach ($msm_jsChartDataClick as $k => $v) {
            if (!isset($msm_jsChartDataOrder[$k]))
                $msm_jsChartDataOrder[$k] = array($v[0],0);
            if (!isset($msm_jsChartDataAmt[$k]))
                $msm_jsChartDataAmt[$k] = array($v[0],0);
        }
        // 小店
        foreach ($shop_jsChartDataClick as $k => $v) {
            if (!isset($shop_jsChartDataOrder[$k]))
                $shop_jsChartDataOrder[$k] = array($v[0], 0);
            if (!isset($shop_jsChartDataAmt[$k]))
                $shop_jsChartDataAmt[$k] = array($v[0], 0);
        }
        // 按日期重新排序
        isset($sg_jsChartDataOrder) ? ksort($sg_jsChartDataOrder) : '';
        isset($sg_jsChartDataAmt) ? ksort($sg_jsChartDataAmt) : '';
        isset($msm_jsChartDataOrder) ? ksort($msm_jsChartDataOrder) : '';
        isset($msm_jsChartDataAmt) ? ksort($msm_jsChartDataAmt) : '';
        isset($shop_jsChartDataOrder) ? ksort($shop_jsChartDataOrder) : '';
        isset($shop_jsChartDataAmt) ? ksort($shop_jsChartDataAmt) : '';
        
        // 经典案例展示
        $list = M('tbatch_o2o_case')->field(
            'id,company_logo,case_name,company_name,bo_comment')
            ->order('shop_recommend_top desc, case_sort asc ,add_time desc')
            ->where(
            array(
                'status' => 0, 
                'shop_recommend_flag' => 1))
            ->limit(3)
            ->select();
        
        $uploadurl = C("WCADMIN_UPLOAD");
        //获取可提现金额
        $allowMoney = D('HomeCm','Service')->getAllowCash($this->node_id);
        
        $freeze_money = M('tnode_cash_trace')->where(
            array(
                'node_id' => $this->node_id, 
                'trans_status' => array('in', array('1', '2')), 
                'trans_type' => '2'
                )
            )->sum('cash_money');
        //计算T+3的日期
        $cashTraceLastTime = date('YmdHis', time()- 3*24*3600);
        $cashTraceLastTime = dateformat($cashTraceLastTime, 'Ymd', '235959');
        $orderNotCash = M('ttg_order_info')->where(
            array(
                'pay_status' => '2', 
                'node_id' => $this->node_id, 
                'pay_time' => array(
                    'gt', 
                    $cashTraceLastTime)))->getField("SUM(order_amt - fee_amt)");

        // 判断是否设置过红包和红包规则
        $bonusCount = M('tmarketing_info')->where(
            array(
                'node_id' => $this->node_id, 
                'batch_type' => 41, 
                'end_time' => array(
                    'gt', 
                    date('YmdHis'))))->count();
        $bonusRuleCount = M('tbonus_rules')->where(
            array(
                'node_id' => $this->node_id))->count();
        if (($bonusCount > 0) && ($bonusRuleCount < 1))
            $bonusRuleShow = 1; // 设置了红包但为设置过红包使用规则
        else
            $bonusRuleShow = 0;

            // 货到付款已支付金额
        //待发货订单数
        $this->assign('needSendOrderCount', $needSendOrderCount);
        $this->assign('deliPayMoney', $deliPayMoney);
        $this->assign('bonusRuleShow', $bonusRuleShow);
        $this->assign('uploadurl', $uploadurl);
        $this->assign('list', $list);
        $this->assign('node_info', $node_info);
        $this->assign('all_click', $all_click);
        $this->assign('order_count', $order_count);
        $this->assign('order_amount', $order_amount);
        $this->assign('getCashStatus', $getCashStatus); // 提现金状态
        $this->assign('sale_goods', $sale_goods);
        $this->assign('nodeAccountInfo', $nodeAccountInfo);
        $this->assign('m_id', $m_id);
        $this->assign('_get', $_get);
        $this->assign('shop_jsChartDataClick', json_encode(array_values($shop_jsChartDataClick)));
        $this->assign('shop_jsChartDataOrder', json_encode(array_values($shop_jsChartDataOrder)));
        $this->assign('shop_jsChartDataAmt', json_encode(array_values($shop_jsChartDataAmt)));
        $this->assign('shop_data', $shop_data);
        $this->assign('sg_jsChartDataClick', json_encode(array_values($sg_jsChartDataClick)));
        $this->assign('sg_jsChartDataOrder', json_encode(array_values($sg_jsChartDataOrder)));
        $this->assign('sg_jsChartDataAmt', json_encode(array_values($sg_jsChartDataAmt)));
        $this->assign('sg_data', $sg_data);
        $this->assign('msm_jsChartDataClick', json_encode(array_values($msm_jsChartDataClick)));
        $this->assign('msm_jsChartDataOrder', json_encode(array_values($msm_jsChartDataOrder)));
        $this->assign('msm_jsChartDataAmt', json_encode(array_values($msm_jsChartDataAmt)));
        $this->assign('today', $today);
        $this->assign('yesterday', $yesterday);
        $this->assign('label_id', $label_id);
        $this->assign('allow_money', $allowMoney);
        $this->assign('freeze_money', $freeze_money);
        $this->assign('orderNotCash', $orderNotCash); // 未结算金额
        $this->assign('account_pwd', $node_info['account_pwd']);
        if ($this->node_id == C('fb_boya.node_id')) {
            $this->assign('isParkYard', 'Y');
        } else {
            $this->assign('isParkYard', 'N');
        }
        $this->display();
    }
    
    // 业绩报表
    public function performance() {
        $time = I('status', '0');
        $start_time = I('start_time', '');
        $end_time = I('end_time', '');
        $wh = " AND toi.order_status = '0' AND toi.pay_status = '2' ";
        if ($start_time && $end_time) {
            $wh .= " and (toi.add_time between '$start_time" .
                 "000000' and '$end_time" . "235959') ";
        } else {
            switch ($time) {
                // 全部
                case 0:
                    $wh .= 'and  1=1 ';
                    break;
                // 昨日
                case 1:
                    $wh .= " and (toi.add_time between '" .
                         date('Ymd', strtotime('-1 day')) . '000000' . "' and '" .
                         date('Ymd', strtotime('-1 day')) . '235959' . "') ";
                    break;
                // 上周
                case 2:
                    $wh .= " and (toi.add_time between '" .
                         date('Ymd', strtotime('-14 day')) . '000000' . "' and '" .
                         date('Ymd', strtotime('-7 day')) . '235959' . "')";
                    break;
                // 上月
                case 3:
                    $wh .= " and (toi.add_time between '" .
                         date('Ymd', strtotime('-2 month')) . '000000' .
                         "' and '" . date('Ymd', strtotime('-1 month')) .
                         '235959' . "')";
                    break;
                // 今日
                case 4:
                    $wh .= " and (toi.add_time between '" . date('Ymd') .
                         '000000' . "' and '" . date('Ymd') . '235959' . "')";
                    ;
                    break;
                // 本周
                case 5:
                    $wh .= " and (toi.add_time between'" .
                         date('Ymd', strtotime('-7 day')) . '000000' . "' and '" .
                         date('Ymd') . '235959' . "')";
                    break;
                // 本月
                case 6:
                    $wh .= " and (toi.add_time between'" .
                         date('Ymd', strtotime('-1 month')) . '000000' .
                         "' and '" . date('Ymd') . '235959' . "')";
                    break;
                default:
                    break;
            }
        }
        
        $list = M()->table("tecshop_promotion ep")->join(
            "tecshop_promotion_member epm ON epm.promotion_id=ep.promotion_id")
            ->join("ttg_order_info toi ON toi.parm1 = epm.id $wh")
            ->field(
            'ep.promotion_id,ep.department,ep.petname,sum(toi.buy_num) as buy_num,sum(toi.order_amt) as order_amt')
            ->order('toi.add_time Desc')
            ->group('ep.promotion_id')
            ->select();
        
        $map = " and wq.node_id ='" . C('fb_boya.node_id') . "'";
        if ($start_time && $end_time) {
            $map .= " and (wq.add_time between '$start_time" .
                 "000000' and '$end_time" . "235959') ";
        } else {
            switch ($time) {
                // 全部
                case 0:
                    $map .= ' and  1=1 ';
                    break;
                // 昨日
                case 1:
                    $map .= " and (wq.add_time between '" .
                         date('Ymd', strtotime('-1 day')) . '000000' . "' and '" .
                         date('Ymd', strtotime('-1 day')) . '235959' . "') ";
                    break;
                // 上周
                case 2:
                    $map .= " and (wq.add_time between '" .
                         date('Ymd', strtotime('-14 day')) . '000000' . "' and '" .
                         date('Ymd', strtotime('-7 day')) . '235959' . "')";
                    break;
                // 上月
                case 3:
                    $map .= " and (wq.add_time between '" .
                         date('Ymd', strtotime('-2 month')) . '000000' .
                         "' and '" . date('Ymd', strtotime('-1 month')) .
                         '235959' . "')";
                    break;
                // 今日
                case 4:
                    $map .= " and (wq.add_time between '" . date('Ymd') .
                         '000000' . "' and '" . date('Ymd') . '235959' . "')";
                    ;
                    break;
                // 本周
                case 5:
                    $map .= " and (wq.add_time between'" .
                         date('Ymd', strtotime('-7 day')) . '000000' . "' and '" .
                         date('Ymd') . '235959' . "')";
                    break;
                // 本月
                case 6:
                    $map .= " and (wq.add_time between'" .
                         date('Ymd', strtotime('-1 month')) . '000000' .
                         "' and '" . date('Ymd') . '235959' . "')";
                    break;
                default:
                    break;
            }
        }
        $list1 = M()->table("tecshop_promotion ep")->join(
            "twx_qrchannel wq ON ep.promotion_id=wq.channel_id $map")
            ->field('wq.subscribe_count, ep.promotion_id')
            ->select();
        
        foreach ($list as &$v) {
            foreach ($list1 as $kk => $vv) {
                if ($vv['promotion_id'] == $v['promotion_id']) {
                    $v['num'] = $vv['subscribe_count'];
                }
            }
        }
        $export = I('export', '0');
        if ($export) {
            $fileName = '推广员业绩报表.csv';
            header("Content-type: text/plain");
            header("Accept-Ranges: bytes");
            header("Content-Disposition: attachment; filename=" . $fileName);
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Pragma: no-cache");
            header("Expires: 0");
            $title_ = "部门, 姓名, 新增客户数, 订单数, 销售额\r\n";
            $title_ = iconv('utf-8', 'gbk', $title_);
            echo $title_;
            if ($list) {
                foreach ($list as $value) {
                    echo iconv('utf-8', 'gbk', $value['department']) . ", ";
                    echo iconv('utf-8', 'gbk', $value['petname']) . ", ";
                    echo iconv('utf-8', 'gbk', $value['num']) . ", ";
                    echo iconv('utf-8', 'gbk', $value['buy_num']) . ", ";
                    echo iconv('utf-8', 'gbk', $value['order_amt']) . "\r\n";
                }
            }
            exit();
        }
        
        $this->assign('list', $list);
        $this->display();
    }
    
    // 推广员客户列表
    public function clientList() {
        $id = I('get.id', '');
        $phone = I('phone', '');
        $start_time = I('start_time', '');
        $end_time = I('end_time', '');
        $petname = M()->table("tecshop_promotion ep")->where(
            array(
                'ep.promotion_id' => $id))->getField('petname');
        $status = I('get.status', 0);
        if ($phone) {
            $wh['toi.order_phone'] = $phone;
        }
        if ($start_time && $end_time && $start_time <= $end_time) {
            $wh['epm.add_time'] = array(
                'between', 
                array(
                    $start_time . '000000', 
                    $end_time . '235959'));
        } elseif ($start_time != '' && $end_time == '') {
            $wh['epm.add_time'] = array(
                'egt', 
                $start_time . '000000');
        } elseif ($start_time == '' && $end_time != '') {
            $wh['epm.add_time'] = array(
                'elt', 
                $end_time . '235959');
        }
        
        $wh['epm.promotion_id'] = $id;
        $wh['epm.status'] = $status;
        
        if ($id) {
            $isDownload = I('post.export', '0', 'string');
            if ($isDownload == '1') {
                $this->_clientListDown($wh);
                exit();
            }
            import('ORG.Util.Page');
            $count = M()->table("tecshop_promotion_member epm")->join(
                'tmember_info mi ON mi.id=epm.member_id')
                ->join(
                "ttg_order_info toi ON toi.order_phone = mi.phone_no AND toi.`parm1`=epm.`id` AND toi.order_status = '0'
    AND toi.pay_status = '2' ")
                ->join('twx_user wx ON epm.wx_user_id=wx.id')
                ->field(
                'epm.status,epm.id as id,wx.nickname,epm.wx_user_id,toi.order_phone as phone_on,epm.add_time AS subscribe_time,sum(toi.buy_num) as buy_num,sum(toi.order_amt) as order_amt')
                ->order('epm.add_time DESC')
                ->where($wh)
                ->count('distinct epm.id ');
            $Page = new Page($count, 10);
            $show = $Page->show();
            
            $list = M()->table("tecshop_promotion_member epm")->join(
                'tmember_info mi ON mi.id=epm.member_id')
                ->join(
                "ttg_order_info toi ON toi.order_phone = mi.phone_no AND toi.`parm1`=epm.`id` AND toi.order_status = '0'
    AND toi.pay_status = '2'")
                ->join('twx_user wx ON epm.wx_user_id=wx.id')
                ->field(
                'epm.status,epm.id as id,wx.nickname,epm.wx_user_id,toi.order_phone as phone_on,epm.add_time AS subscribe_time,sum(toi.buy_num) as buy_num,sum(toi.order_amt) as order_amt')
                ->group('wx.id,toi.parm1,epm.`promotion_id`')
                ->order('epm.add_time DESC')
                ->where($wh)
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->select();
            // 职位查询
            $this->_getPromotionOption();
            $this->assign('page', $show);
            $this->assign('list', $list);
        }
        
        $this->assign('petname', $petname);
        $this->display();
    }
    
    // 推广员客户列表下载
    public function _clientListDown($wh) {
        $sql = M()->table("tecshop_promotion_member epm")->join(
            'tmember_info mi ON mi.id=epm.member_id')
            ->join(
            "ttg_order_info toi ON toi.order_phone = mi.phone_no AND toi.`parm1`=epm.`id` AND toi.order_status = '0'
    AND toi.pay_status = '2'")
            ->join('twx_user wx ON epm.wx_user_id=wx.id')
            ->field(
            'epm.status,epm.id as id,wx.nickname,epm.wx_user_id,toi.order_phone as phone_on,epm.add_time AS subscribe_time,sum(toi.buy_num) as buy_num,sum(toi.order_amt) as order_amt')
            ->group('wx.id,toi.parm1,epm.`promotion_id`')
            ->order('epm.add_time DESC')
            ->where($wh)
            ->buildSql();
        
        // echo $sql;
        // 微信昵称 手机号 注册时间 订单数 交易总额 操作
        $cols_arr = array(
            'nickname' => '微信昵称', 
            'phone_on' => '手机号', 
            'buy_num' => '订单数', 
            'order_amt' => '交易总额');
        if ($wh['epm.status'] == '0') {
            $cols_arr['subscribe_time'] = '绑定时间';
        } elseif ($wh['epm.status'] == '1') {
            $cols_arr['subscribe_time'] = '解绑时间';
        }
        $filename = '客户信息表';
        if (querydata_download($sql, $cols_arr, M(), $filename) == false) {
            $this->error('下载失败');
        }
    }
    
    // 销售数据
    public function orderNum() {
        $id = I('id', '');
        $petname = M()->table("tecshop_promotion ep")->where(
            array(
                'ep.promotion_id' => $id))->getField('petname');
        
        $list = M()->table("tecshop_promotion_member epm")->join(
            'twx_qrchannel wq ON wq.channel_id=epm.promotion_id')
            ->join('ttg_order_info toi ON epm.id=toi.parm1')
            ->field(
            'wq.click_count,COUNT(DISTINCT epm.id) AS num,sum(toi.buy_num) as buy_num,sum(toi.order_amt) as order_amt')
            ->where(
            array(
                'epm.promotion_id' => $id, 
                'toi.order_status' => '0', 
                'toi.pay_status' => '2'))
            ->find();
        
        // 查询
        $_get['begin_date'] = $begin_date = I('begin_date', 
            dateformat("-30 days", 'Ymd'));
        $_get['end_date'] = $end_date = I('end_date', 
            dateformat("0 days", 'Ymd'));
        
        // 查询日期
        $map['day'] = array();
        if ($begin_date != '') {
            $map['day'][] = array(
                'EGT', 
                $begin_date);
        }
        if ($end_date != '') {
            $map['day'][] = array(
                'ELT', 
                $end_date);
        }
        
        $jsChartDataOrder = array(); // 订单数
        $jsChartDataAmt = array(); // 销售额
                                   
        // 订单数
        $order_map = array(
            'toi.order_status' => '0', 
            'toi.pay_status' => '2');
        // 查询日期
        $order_map['epm.add_time'] = array();
        if ($begin_date != '') {
            $order_map['epm.add_time'][] = array(
                'EGT', 
                $begin_date . '000000');
        }
        if ($end_date != '') {
            $order_map['epm.add_time'][] = array(
                'ELT', 
                $end_date . '235959');
        }
        $order_map['epm.promotion_id'][] = array(
            'eq', 
            $id);
        
        $order_arr = M()->table("tecshop_promotion_member epm")->join(
            'ttg_order_info toi ON epm.id=toi.parm1')
            ->field(
            "substr(epm.add_time,'1',8) as day,sum(toi.buy_num) as buy_num,sum(toi.order_amt) as order_amt")
            ->where($order_map)
            ->group("substr(epm.add_time,1,8)")
            ->select();
        
        // 计算出JS值
        foreach ($order_arr as $v) {
            $jsChartDataOrder[] = array(
                strtotime($v['day'] . " UTC") * 1000, 
                $v['buy_num'] * 1);
            $jsChartDataAmt[] = array(
                strtotime($v['day'] . " UTC") * 1000, 
                $v['order_amt'] * 1);
        }
        
        $this->assign('jsChartDataOrder', json_encode($jsChartDataOrder));
        $this->assign('jsChartDataAmt', json_encode($jsChartDataAmt));
        $this->assign('begin_date', $begin_date);
        $this->assign('end_date', $end_date);
        $this->assign('list', $list);
        $this->assign('petname', $petname);
        $this->display();
    }
    
    // 更新单条客户信息列表数据
    public function oneUpMember() {
        $newPromotionID = I('changeToID');
        $id = I('id');
        
        if ($id) {
            $ecshopPromotionMemberModel = M('TecshopPromotionMember');
            
            $ecshopPromotionMemberModel->startTrans();
            $isSuccess1 = $ecshopPromotionMemberModel->where(
                array(
                    'id' => $id))->save(
                array(
                    'status' => '1'));
            
            if (! $isSuccess1) {
                $ecshopPromotionMemberModel->rollback();
                $this->ajaxReturn(1, '移交失败[status=1]', 0);
            }
            
            $dataOld = $ecshopPromotionMemberModel->where(
                array(
                    'id' => $id))->find();
            
            $data = array(
                'promotion_id' => $newPromotionID, 
                'add_time' => date('YmdHis'), 
                'member_id' => $dataOld['member_id'], 
                'new_promotion_id' => $dataOld['promotion_id'], 
                'wx_user_id' => $dataOld['wx_user_id'], 
                'status' => 0);
            
            $isSuccess2 = $ecshopPromotionMemberModel->add($data);
            if (! $isSuccess2) {
                $ecshopPromotionMemberModel->rollback();
                $this->ajaxReturn(1, '移交失败[status=0]', 0);
            } else {
                $ecshopPromotionMemberModel->commit();
                if ($isSuccess2) {
                    $this->ajaxReturn(1, '移交成功', 1);
                } else {
                    $this->ajaxReturn(1, '移交失败', 0);
                }
            }
        }
    }
    
    // 解除客户关系
    public function clientRelation() {
        $id = I('id', '');
        if ($id) {
            $result = M('tecshop_promotion_member')->where(
                array(
                    'id' => $id))->save(
                array(
                    'status' => '1'));
            
            if ($result) {
                $this->ajaxReturn(1, '解除成功', 1);
            }
        }
    }

    public function editHelpStatus($helpStatus) {
        if ($helpStatus != 1) {
            $data = array(
                'help_tip_status' => 1);
            M('tuser_info')->where(
                "node_id='{$this->nodeId}' AND user_id='{$this->userId}'")->save(
                $data);
            Log::write("更新提示标志=" . M('tuser_info')->getLastSql());
        }
    }

    public function crop_head() {
        $callback = I('get.call_back', null, 'trim');
        $img = I('get.img', null, 'trim');
        $picpath = C("UPLOAD") . "/" . $this->nodeId . "/" . $img;
        list ($width, $height) = getimagesize($picpath);
        $this->assign("picpath", $picpath);
        $this->assign("picwidth", $width);
        $this->assign("picheight", $height);
        $this->assign("callback", $callback);
        $this->assign("img", $img);
        $this->display("crop_image");
    }
    
    // 更新头像
    public function update_head() {
        $head_file = I('post.head_file', null, 'trim');
        // 记录入库
        // 截取扩展名
        
        $sourceArr = explode("/", $head_file);
        $filename = $sourceArr[count($sourceArr) - 1];
        $dataArr = array(
            "head_photo" => $filename);
        $ifok = M('tnode_info')->where("node_id='{$this->nodeId}'")->save(
            $dataArr);
        if ($ifok) {
            echo "1";
        } else {
            
            echo "2";
        }
    }
    
    // 提交裁剪
    public function crop_head_submit() {
        
        /*
         * $imageSource=I('post.imageSource', null, 'trim');
         * $viewPortW=I('post.viewPortW', null, 'trim');
         * $viewPortH=I('post.viewPortH', null, 'trim');
         * $imageW=I('post.imageW', null, 'trim'); $imageH=I('post.imageH',
         * null, 'trim'); $selectorX=I('post.selectorX', null, 'trim');
         * $selectorY=I('post.selectorY', null, 'trim');
         * $imageRotate=I('post.imageRotate', null, 'trim');
         * $imageX=I('post.imageX', null, 'trim'); $imageY=I('post.imageY',
         * null, 'trim'); $selectorW=I('post.selectorW', null, 'trim');
         * $selectorH=I('post.selectorH', null, 'trim');
         * import('@.ORG.Util.crop_image'); $filep=
         * C("UPLOAD")."/".$this->nodeId.'/'; $crop=new
         * crop_image($filep,$imageSource,$viewPortW,$viewPortH,$imageW,$imageH,$selectorX,$selectorY,$imageRotate,$imageX,$imageY,$selectorW,$selectorH);
         * $file=$crop->crop(); echo $file;
         */
        $pic_name = I('post.pic_name', null, 'trim');
        $x = I('post.x', null, 'trim');
        $y = I('post.y', null, 'trim');
        $w = I('post.w', null, 'trim');
        $h = I('post.h', null, 'trim');
        $targ_w = $targ_h = 66;
        
        import('@.ORG.Util.jcrop_image');
        $filep = C("UPLOAD") . "/" . $this->nodeId . '/';
        $crop = new jcrop_image($filep, $pic_name, $x, $y, $w, $h, $targ_w, 
            $targ_h);
        $file = $crop->crop();
    }
    // 上传图片（已作废by tr)
    public function _uploadFile() {
        import('ORG.Net.UploadFile');
        $upload = new UploadFile(); // 实例化上传类
        $upload->maxSize = 1024 * 1024; // 设置附件上传大小 1兆
        $upload->allowExts = array(
            'jpg', 
            'gif', 
            'png'); // 设置附件上传类型
        $upload->savePath = APP_PATH . 'Upload/' . $this->nodeId . '/'; // 设置附件
        $upload->saveRule = time() . sprintf('%04s', mt_rand(0, 1000));
        if (! $upload->upload()) { // 上传错误提示错误信息
            exit(
                json_encode(
                    array(
                        'info' => $upload->getErrorMsg(), 
                        'status' => 0)));
        } else { // 上传成功 获取上传文件信息
            $info = $upload->getUploadFileInfo();
            if ($info)
                $info = $info[0];
            if (! $info) {
                exit(
                    json_encode(
                        array(
                            'info' => "系统正忙", 
                            'status' => 0)));
            }
            $dataArr = array(
                "head_photo" => $info['savename']);
            $ifok = M('tnode_info')->where("node_id='{$this->nodeId}'")->save(
                $dataArr);
            exit(
                json_encode(
                    array(
                        'info' => array(
                            'fileId' => '', 
                            'imgName' => $info['savename'], 
                            'imgUrl' => $this->_getImgUrl($info['savename']), 
                            'imgWay' => $upload->savePath), 
                        'status' => 1)));
        }
    }

    /*
     * 更新头象
     */
    public function saveHeadLogo() {
        $savename = I('post.savename');
        // 更新图片地址
        $dataArr = array(
            "head_photo" => $savename);
        $ifok = M('tnode_info')->where("node_id='{$this->nodeId}'")->save(
            $dataArr);
        exit(
            json_encode(
                array(
                    'info' => '更新成功', 
                    'status' => 1)));
    }

    private function _getImgUrl($imgname) {
        return $this->uploadPath . $imgname;
    }

    public function UpdateEmail() {
        $nodeInfo = M('tnode_info')->where(
            array(
                "node_id" => $this->nodeId))->find();
        $this->assign('nodeInfo', $nodeInfo);
        $this->display();
    }

    /*
     * $dataArr=array( "head_photo"=>$info[0]['savename'] );
     * $ifok=M('tnode_info')->where("node_id='{$this->nodeId}'")->save($dataArr);
     * //首页$upload->savePath = APP_PATH.'/Upload/'.$this->nodeId.'/';// 设置附件
     * $upload->saveRule = time().sprintf('%04s',mt_rand(0,1000)); public
     * function index(){ if($this->charge_id){//已购买服务界面 //获取礼包单项中的套餐信息
     * $packagesInfo = M()->Table('tcharge_relation r') ->join('tcharge_info i
     * ON r.relation_id=i.charge_id') ->where("r.charge_id='{$this->charge_id}'
     * AND i.charge_type=1") ->find(); if(!$packagesInfo){
     * $this->error('未找到套餐信息'); } $this->assign('packagesInfo',$packagesInfo);
     * $this->display('buyIndex'); }else{//未购买服务界面 $this->display(); }
     * $chargeInfoModel = D('TchargeInfo'); //购买页面,获取所有旺财终端服务
     * if($this->terminalCount > 0){ $TerminalServices =
     * $chargeInfoModel->getChargeInfoByType(2,2); } //获取所有旺财营销服务
     * $MarketingServices = $chargeInfoModel->getChargeInfoByType(2,1);
     * $this->assign('TerminalServices',$TerminalServices);
     * $this->assign('MarketingService',$MarketingServices);
     * $this->assign('terminalCount',$this->terminalCount); $this->display(); }
     * //礼包列表页 public function chargeInfoList(){
     * if($this->charge_id){//判断商户是否已购买服务
     * $this->error('您已经购买了我们的服务',U('Index/index')); } //获取礼包信息 $chargeInfoModel
     * = M('tcharge_info'); $where = array(//查询所有礼包 'charge_type' => '0' );
     * $chargeInfo = $chargeInfoModel->where($where)->select(); //处理礼包内数据
     * if($chargeInfo){ foreach ($chargeInfo as $k=>$v){ $arr = explode('|',
     * $v['charge_memo']); // foreach ($arr as $k_1=>$v_1){ // $arr[$k_1] =
     * explode('~', $v_1); // } $chargeInfo[$k]['charge_memo'] = $arr; } }
     * $this->assign('chargeInfo',$chargeInfo); $this->display(); } //填写订单信息
     * public function goBuy(){ if($this->charge_id){//判断商户是否已购买服务
     * $this->error('您已经购买了我们的服务',U('Index/index')); } $chargeId =
     * $this->_post('charge_id','trim'); if(empty($chargeId)){
     * $this->error('请选择要购买的礼包'); } //检查是否是有效礼包 $chargeInfoModel =
     * M('tcharge_info'); $where = array( 'charge_id' => $chargeId,
     * 'charge_type' => 0 ); $chargeInfo =
     * $chargeInfoModel->where($where)->find(); if(!$chargeInfo){
     * $this->error('未找到礼包信息'); } $this->assign('chargeInfo',$chargeInfo);
     * $this->display(); } //确认订单 public function confirmOrder(){
     * if($this->charge_id){//判断商户是否已购买服务
     * $this->error('您已经购买了我们的服务',U('Index/index')); } $data = array_map('trim',
     * $_POST); //数据验证 if(empty($data['charge_id'])){ $this->error('参数错误'); }
     * //检查是否是有效礼包 $chargeInfoModel = M('tcharge_info'); $where = array(
     * 'charge_id' => $data['charge_id'], 'charge_type' => 0 ); $chargeInfo =
     * $chargeInfoModel->where($where)->find(); if(!$chargeInfo){
     * $this->error('未找到礼包信息'); } $error = '';
     * if(!check_str($data['contact_name'],array('null'=>false),$error)){
     * $this->error("请填写收货人姓名"); }
     * if(!check_str($data['contact_addr'],array('null'=>false),$error)){
     * $this->error("请填写详细地址"); }
     * if(!check_str($data['contact_mobile'],array('null'=>false,'strtype'=>'mobile'),$error)){
     * $this->error("手机号码{$error}"); } if(!isset($data['agreement']) ||
     * $data['agreement']!='1' ){ $this->error("您还没同意我们的《旺财礼包购买协议》"); }
     * $data['order_id'] = get_sn(); $data['user_id'] = $this->userId;
     * $data['node_id'] = $this->nodeId; $data['add_time'] = date('YmdHis');
     * $data['charge_name'] = $chargeInfo['charge_name']; $data['busi_amt'] =
     * $chargeInfo['charge_amt']; session('orderInfo',$data);
     * $this->assign('chargeInfo',$chargeInfo); $this->assign('data',$data);
     * $this->display(); } //支付订单 public function payOrder(){
     * if($this->charge_id){//判断商户是否已购买服务
     * $this->error('您已经购买了我们的服务',U('Index/index')); } $data =
     * session('orderInfo'); if(empty($data)) $this->error('缺少必要参数'); //添加订单
     * $result = M('torder_list')->data($data)->add(); if(!$result)
     * $this->error('订单添加失败'); //更新商户表推荐人信息 if(isset($data['reference_name']) &&
     * $data['reference_name']!=''){
     * M('tnode_info')->where("node_id='{$data['node_id']}'")->save(array('reference_name'
     * => $data['reference_name'],)); } session('orderInfo',null); //调用支付宝接口
     * import('Home.Vendor.AlipayModel'); $alipayModel = new AlipayModel();
     * $alipayModel->AlipayTo($data['order_id'],$data['charge_name'],$data['busi_amt']);
     * }
     */
    function parkYardIndex() {
        if ($this->node_id != C('fb_boya.node_id')) {
            redirect($_SERVER['HTTP_REFERER']);
            exit();
        }
        $searchCondition = array();
        if (IS_POST) {
            $promotionName = I('post.name');
            if ($promotionName != '') {
                $searchCondition['tecshop_promotion.petname'] = array(
                    'like', 
                    '%' . $promotionName . '%');
                $this->assign('promotionName', $promotionName);
            }
            
            $promotionStatus = I('post.status');
            if ($promotionStatus != '') {
                $searchCondition['tecshop_promotion.status'] = $promotionStatus;
                $this->assign('promotionStatus', $promotionStatus);
            }
            
            $searchPromotionOption = I('post.option');
            if ($searchPromotionOption != '') {
                $searchCondition['tecshop_promotion.department'] = array(
                    'like', 
                    '%' . $searchPromotionOption . '%');
                $this->assign('searchPromotionOption', $searchPromotionOption);
            }
            $promotionSearchStartTime = I('post.start_time');
            if ($promotionSearchStartTime) {
                $promotionSearchStartTime .= '000000';
            }
            
            $promotionSearchEndTime = I('post.end_time');
            if ($promotionSearchEndTime) {
                $promotionSearchEndTime .= '235959';
            }
            
            if ($promotionSearchStartTime != '' && $promotionSearchEndTime != '' &&
                 $promotionSearchStartTime < $promotionSearchEndTime) {
                $searchCondition['tecshop_promotion.add_time'] = array(
                    'egt', 
                    $promotionSearchStartTime);
                $searchCondition[' tecshop_promotion.add_time'] = array(
                    'elt', 
                    $promotionSearchEndTime);
            } elseif ($promotionSearchStartTime != '' &&
                 $promotionSearchEndTime == '') {
                $searchCondition['tecshop_promotion.add_time'] = array(
                    'egt', 
                    $promotionSearchStartTime);
            } elseif ($promotionSearchStartTime == '' &&
                 $promotionSearchEndTime != '') {
                $searchCondition['tecshop_promotion.add_time'] = array(
                    'elt', 
                    $promotionSearchEndTime);
            } elseif ($promotionSearchStartTime != '' &&
                 $promotionSearchEndTime != '' &&
                 $promotionSearchStartTime > $promotionSearchEndTime) {
                $this->error('请核对开始搜索时间以及结束搜索时间!');
            }
            $this->assign('searchStartTime', $promotionSearchStartTime);
            $this->assign('searchEndTime', $promotionSearchEndTime);
        }
        $ecshopPromotionModel = M('TecshopPromotion');
        $searchCondition['tecshop_promotion.node_id'] = $this->nodeId;
        import('ORG.Util.Page');
        $promotionMemberCount = $ecshopPromotionModel->where($searchCondition)->count();
        $Page = new Page($promotionMemberCount, 10);
        $show = $Page->show();
        $promotionMemberList = $ecshopPromotionModel->join(
            'twx_qrchannel ON twx_qrchannel.channel_id = tecshop_promotion.promotion_id')
            ->where($searchCondition)
            ->order(
            'tecshop_promotion.status ASC, tecshop_promotion.add_time desc')
            ->field(
            'tecshop_promotion.promotion_id, tecshop_promotion.petname, tecshop_promotion.department, tecshop_promotion.add_time, tecshop_promotion.status, twx_qrchannel.img_info ')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        if ($promotionMemberCount > 0) {
            $this->assign('promotionMemberList', $promotionMemberList);
            $this->assign('page', $show);
            
            // 职位查询
            $this->_getPromotionOption();
            $this->display();
            exit();
        } else {
            $weixinInfoModel = M('TweixinInfo');
            $wichatID = $weixinInfoModel->where(
                array(
                    'node_id' => $this->nodeId, 
                    'status' => '0'))->getfield('id');
            ;
            if (! $wichatID) {
                $this->assign('isUseWechat', 'N');
            } else {
                $this->assign('isUseWechat', 'Y');
            }
            $this->assign('promotionOption', $promotionOption);
            $this->display();
            exit();
        }
    }

    function _getPromotionOption() {
        $ecshopPromotionModel = M('TecshopPromotion');
        $promotionOption = $ecshopPromotionModel->distinct(TRUE)
            ->field('department')
            ->where(array(
            'node_id' => $this->nodeId))
            ->select();
        $this->assign('promotionOption', $promotionOption);
    }

    function getPromotion() {
        $department = I('post.department', '0', 'string');
        $changeID = I('post.changeID');
        $ecshopPromotionModel = M('TecshopPromotion');
        $option = $ecshopPromotionModel->where(
            array(
                'node_id' => $this->node_id, 
                'department' => $department, 
                'promotion_id' => array(
                    'NEQ', 
                    $changeID)))
            ->field('petname, promotion_id')
            ->select();
        $optionArray = array();
        foreach ($option as $val) {
            $optionArray[$val['promotion_id']] = $val['petname'];
        }
        $this->ajaxReturn($optionArray);
    }

    function changeStatus() {
        $status = I('post.status', '0', 'string');
        $promotionID = I('post.user_id', '0', 'string');
        if ($status == '1') {
            $data['status'] = '2';
        } else {
            $data['status'] = '1';
        }
        $tecshopPromotionModel = M('TecshopPromotion');
        $tecshopPromotionModel->where(
            array(
                'node_id' => $this->node_id, 
                'promotion_id' => $promotionID))->save($data);
        $this->ajaxReturn($result = array(
            'error' => '0'));
    }

    function changePromotion() {
        $result = array();
        $newPromotionID = I('post.changeToID');
        $poromotionID = I('post.changeID');
        
        $ecshopPromotionMemberModel = M('TecshopPromotionMember');
        $ecshoPromotionArray = $ecshopPromotionMemberModel->where(
            array(
                'promotion_id' => $poromotionID, 
                'status' => '0'))->select();
        
        $tranDb = new Model();
        $tranDb->startTrans();
        $nowDate = date('YmdHis');
        $isSuccess = $ecshopPromotionMemberModel->where(
            array(
                'promotion_id' => $poromotionID))->save(
            array(
                'status' => '1', 
                'add_time' => $nowDate));
        if (! $isSuccess) {
            $tranDb->rollback();
            $result['error'] = '10001';
            $result['msg'] = '无法修改移交客户数据！';
            $this->ajaxReturn($result);
            exit();
        }
        
        $errorCount = 0;
        foreach ($ecshoPromotionArray as $val) {
            $data = array();
            $data['promotion_id'] = $newPromotionID;
            $data['member_id'] = $val['member_id'];
            $data['add_time'] = $nowDate;
            $data['status'] = '0';
            $data['new_promotion_id'] = $poromotionID;
            $data['wx_user_id'] = $val['wx_user_id'];
            $addSuccess = $ecshopPromotionMemberModel->add($data);
            if (! $addSuccess) {
                $errorCount += 1;
            }
        }
        
        if ($errorCount > 0) {
            $tranDb->rollback();
            $result['error'] = '20001';
            $result['msg'] = '无法新增被移交客户数据！';
            $this->ajaxReturn($result);
            exit();
        } else {
            $tranDb->commit();
            $result['error'] = '0';
            $result['msg'] = '移交客户成功！';
            $this->ajaxReturn($result);
            exit();
        }
    }

    function parkYardQercDown() {
        if ($this->node_id != C('fb_boya.node_id')) {
            exit();
        }
        $type = I('get.type', '0', 'string');
        if ($type == 'single') {
            $TwxQrchannelModel = M('TwxQrchannel');
            $channel = I('get.channel');
            $promotionInfo = $TwxQrchannelModel->join(
                'tecshop_promotion ON tecshop_promotion.promotion_id = twx_qrchannel.channel_id')
                ->where(
                array(
                    'twx_qrchannel.channel_id' => $channel))
                ->field(
                'twx_qrchannel.img_info, tecshop_promotion.petname, tecshop_promotion.department')
                ->find();
            $file = $promotionInfo['department'] . '-' .
                 $promotionInfo['petname'] . '.png';
            $filename = iconv('utf-8', 'gbk', $file);
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . $filename);
            header('Content-Type: image/png');
            echo file_get_contents($promotionInfo['img_info']);
        } elseif ($type == 'more') {
            $ecshopPromotionModel = M('TecshopPromotion');
            $promotionInfo = $ecshopPromotionModel->join(
                'twx_qrchannel ON tecshop_promotion.promotion_id = twx_qrchannel.channel_id')
                ->where(
                array(
                    "tecshop_promotion.node_id" => $this->node_id))
                ->field(
                'twx_qrchannel.img_info, tecshop_promotion.petname, tecshop_promotion.department')
                ->select();
            set_time_limit(0);
            $path = APP_PATH . "Upload/ParkYardZip/";
            if (! is_dir($path)) {
                mkdir($path);
            }
            $downname = $path . "parkyard.zip";
            $zip = new ZipArchive();
            if ($zip->open($downname, ZIPARCHIVE::CREATE) !== TRUE) {
                exit('无法打开文件，或者文件创建失败');
            }
            foreach ($promotionInfo as $val) {
                $new_name = $val['department'] . '-' . $val['petname'] . '.png';
                $new_name = iconv('utf-8', 'gbk', $new_name);
                $PNGFile = $path . $new_name;
                $content = file_get_contents($val['img_info']);
                $fp = fopen($PNGFile, "w");
                fwrite($fp, $content);
                fclose($fp);
                $zip->addFile($PNGFile, $new_name);
            }
            $zip->close();
            header("Cache-Control: max-age=0");
            header("Content-Description: File Transfer");
            header(
                'Content-disposition: attachment; filename=' .
                     basename($downname));
            header("Content-Type: application/zip");
            header("Content-Transfer-Encoding: binary");
            header('Content-Length: ' . filesize($downname));
            @readfile($downname);
            
            $dh = opendir($path);
            while ($file = readdir($dh)) {
                if ($file != "." && $file != "..") {
                    $fullpath = $path . $file;
                    if (! is_dir($fullpath)) {
                        unlink($fullpath);
                    }
                }
            }
            closedir($dh);
        }
    }
    //收款帐号
    public function accountEdit(){
        //取得支付通道信息
        $nodeAccount = M('tnode_account')->where(array('node_id' => $this->node_id))->select();
        $nodeAccountInfo = array_valtokey($nodeAccount, 'account_type');
        // 提现帐号信息
        $cashInfo = M('tnode_cash')->where(array('node_id' => $this->node_id))->find();
        //预留手机信息
        $phoneInfo = M('tnode_info')->where(array('node_id' => $this->node_id))->getField('receive_phone');
        $nodeInfo = M('tnode_info')->where(
            array(
                'node_id' => $this->node_id))->find();
        // 弹窗标识
        $popupMark = I('get.popupMark', 'false');
        $this->assign('popupMark', $popupMark);
        $this->assign('account_pwd', $nodeInfo['account_pwd']);
        $this->assign('cashInfo', $cashInfo);
        $this->assign('phoneInfo', $phoneInfo);
        $this->assign('nodeAccountInfo', $nodeAccountInfo);
        $this->assign('isPass', $isPass);
        $this->display();
    }
    //账号提现
    public function getCash(){
        if($this->hideGetCash==1){
            $this->error('当前帐号无法进入提现页面！');
        }
        //获取可提现金额
        $allowMoney = D('HomeCm','Service')->getAllowCash($this->node_id);
        //账号信息提取
        $cashInfo = M('tnode_cash')->where(array('node_id' => $this->node_id))->find();
        if (! $cashInfo || ! $cashInfo['account_no']){
            $this->error('您未配置提现帐号，无法进行提现申请！');
        }    
        // 取得电商手续费
        $o2oFee = D('O2O', 'Service')->getO2OFEE();
        $today = date('Ymd');
        
        //提现切换按钮
        $cashDeliy = I('cashDeliy', null);
        $payChannelArray = array(
            '1' => '支付宝', 
            '2' => '联动优势', 
            '3' => '微信');
        // 计算费率
        $tnodeAccount = M('Tnode_account');
        $tnodeAccountResult = $tnodeAccount->where(
            "account_type='1' AND node_id='{$this->node_id}'")->select();
        if (is_array($tnodeAccountResult)) {
            $tnodeAccountResult[0]['fee_rate'] = $tnodeAccountResult[0]['fee_rate'] ? $tnodeAccountResult[0]['fee_rate'] : 0.02;
        }
        $fee_rate = $tnodeAccountResult[0]['fee_rate'];
        // 当天时间
        $todayYmd = date('Ymd', $_SERVER['REQUEST_TIME']);
        $todayY_m_d = date('Y-m-d', $_SERVER['REQUEST_TIME']);
        // 查询条件---时间
        $beginDate = I('start_time', null, 'mysql_real_escape_string,trim');
        $endDate = I('end_time', null, 'mysql_real_escape_string,trim');
        if (! empty($beginDate) && ! empty($endDate)) {
            $map['o.update_time'] = array(
                'between', 
                $beginDate . '000000,' . $endDate . '235959');
        } elseif (! empty($beginDate) && empty($endDate)) {
            $map['o.update_time'] = array(
                'egt', 
                $beginDate . '000000');
        } elseif (empty($beginDate) && ! empty($endDate)) {
            $map['o.update_time'] = array(
                'elt', 
                $endDate . '235959');
        }
        // 查询条件---支付通道
        $pay_channel = I('post.pay_channel');
        if (! empty($pay_channel) || $pay_channel == "0") {
            $map['o.pay_channel'] = I('post.pay_channel');
        }
        // 必须是已经支付的
        $map['o.pay_status'] = 2;
        // 必须是当前的登录机构
        $map['o.node_id'] = array(
            'exp', 
            "in (" . $this->nodeIn() . ")");
        import('ORG.Util.Page'); // 导入分页类
                                 // 查询的字段
        $field = array(
            "DATE_FORMAT(o.update_time,'%Y-%m-%d') AS trans_time,SUM(o.order_amt) AS order_amt,ifnull(SUM(o.freight),0.00) AS frieght,IFNULL(b.bonus_amount,0.00) AS bonus_amt,
			CASE WHEN o.pay_channel='1' THEN ifnull(SUM(o.fee_amt),0.00) WHEN o.pay_channel<>'1' THEN ifnull(SUM(o.fee_amt),0.00) END rate_amt, 
			CASE WHEN o.pay_channel='1' THEN ifnull(SUM(o.order_amt-o.fee_amt),0.00) WHEN o.pay_channel<>'1' THEN ifnull(SUM(o.order_amt-o.fee_amt),0.00) END act_amt,
			CASE WHEN o.pay_channel='1' THEN '支付宝' WHEN o.pay_channel='2' THEN '联动优势' WHEN o.pay_channel='3' THEN '微信' ELSE '其他' END pay_channel");
        
        // 查询的全部销售额日报表的总数
        $field1 = M()->table('ttg_order_info o')
            ->field($field)
            ->join(
            '(SELECT order_id,bonus_amount from tbonus_use_detail group by order_id HAVING order_id>0) b ON b.order_id=o.order_id')
            ->group('SUBSTR(o.update_time,1,8),o.pay_channel')
            ->where($map)
            ->select();

        $mapcount = count($field1);
        $Page = new Page($mapcount, 10, array('cashDeliy'=>'1')); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        // 开始查询，全部销售额日报表
        $field2 = M()->table('ttg_order_info o')
            ->field($field)
            ->join(
            '(SELECT order_id,bonus_amount from tbonus_use_detail group by order_id HAVING order_id>0) b ON b.order_id=o.order_id')
            ->group('SUBSTR(o.update_time,1,8),o.pay_channel')
            ->where($map)
            ->order('SUBSTR(o.update_time,1,8) desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        // 00004488 91机构
        // 查询当日数据
        $todayWhere['o.pay_status'] = '2';
        $todayWhere['o.node_id'] = $this->node_id;
        $todayWhere['o.update_time'] = array(
            'between', 
            $todayYmd . '000000,' . $todayYmd . '235959');
        //  start 提现数据
        
        $map = array(
            'n.node_id' => $this->node_id, 
            'n.trans_type' => '2');
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = M()->table('tnode_cash_trace n')
            ->where($map)
            ->count(); // 查询满足要求的总记录数
        $cashPage = new Page($mapcount, 10, array('cashDeliy'=>'2')); // 实例化分页类 传入总记录数和每页显示的记录数
        
        $cashShow = $cashPage->show(); // 分页显示输出
                               // 判断是否为c1和c2用户
        $nodeInfo = M('tnode_info')->where(
            array(
                'node_id' => $this->node_id))->find();
        // 判断是否为电商正是用户
        $wctype = 1;
        if (! $this->hasPayModule('m2')) {
            $wctype = 2;
        }
        if (2 == $wctype) {
            $traceInfo = M()->table('tnode_cash_trace n')
                ->field(
                'n.*,ta.account_no,u.user_name,IFNULL((select sum(a.cash_money) from tnode_cash_trace a where a.get_id=n.id and a.cash_type=2 and a.trans_type=1),0) yl_money,IFNULL((select sum(a.cash_money) from tnode_cash_trace a where a.get_id=n.id and a.cash_type=3 and a.trans_type=1),0) wx_money,IFNULL((select sum(a.cash_money) from tnode_cash_trace a where a.get_id=n.id and a.cash_type=1 and a.trans_type=1),0) aliypay_money')
                ->join('tuser_info u on u.user_id=n.user_id')
                ->join('tnode_cash ta ON ta.node_id = n.node_id')
                ->where($map)
                ->limit($cashPage->firstRow . ',' . $cashPage->listRows)
                ->order('n.id desc')
                ->select();
        } else {
            $traceInfo = M()->table('tnode_cash_trace n')
                ->field(
                'n.*,ta.account_no,u.user_name,IFNULL((select sum(a.cash_money) from tnode_cash_trace a where a.get_id=n.id and a.cash_type=2 and a.trans_type=1),0) yl_money,IFNULL((select sum(a.cash_money) from tnode_cash_trace a where a.get_id=n.id and a.cash_type=3 and a.trans_type=1),0) wx_money')
                ->join('tuser_info u on u.user_id=n.user_id')
                ->join('tnode_cash ta ON ta.node_id = n.node_id')
                ->where($map)
                ->limit($cashPage->firstRow . ',' . $cashPage->listRows)
                ->order('n.id desc')
                ->select();
        }
        $tranType = array(
            '1' => '订单金额充值', 
            '2' => '提现');
        $tranStatus = array( // 提现时有用 0-提交成功 1-处理中 2-已取消 3-已处理 9-导出中
            '0' => '提交成功', 
            '1' => '提交成功', 
            '2' => '已取消', 
            '3' => '已完成', 
            '4' => '处理中', 
            '9' => '导出中');
        
        $this->assign('traceInfo', $traceInfo);
        $this->assign('tranType', $tranType);
        $this->assign('wctype', $wctype);
        $this->assign('tranStatus', $tranStatus);
        $this->assign('cahsPage', $cashShow);
     
        
        
        //  end  提现数据
        
        $this->assign('isPlayToday', $Page->firstRow);
        $this->assign('cashDeliy', $cashDeliy);
        $this->assign('saleList', $field2);
        $this->assign('payChannelArray', $payChannelArray);
        $this->assign('pay_channel', I('post.pay_channel'));
        $this->assign('page', $show);
        
        $this->assign('cashInfo', $cashInfo);
        $this->assign('o2oFee', (int) $o2oFee);
        $this->assign('today', $today);
        $this->assign('allowMoney', $allowMoney);
        $this->display();
    }
    
    //记录下载
    public function getCashDown(){
        $sId = I('sid');  //1==销售日报表，2==提现记录
        $map = [];
        $cols_arr = [];
        $payChannelArray = array(
            '1' => '支付宝', 
            '2' => '联动优势', 
            '3' => '微信');
        if('1' === $sId){
            $beginDate = I('startTime', null, 'mysql_real_escape_string,trim');
            $endDate = I('endTime', null, 'mysql_real_escape_string,trim');
            if (! empty($beginDate) && ! empty($endDate)) {
                $map['o.update_time'] = array(
                    'between', 
                    $beginDate . '000000,' . $endDate . '235959');
            } elseif (! empty($beginDate) && empty($endDate)) {
                $map['o.update_time'] = array(
                    'egt', 
                    $beginDate . '000000');
            } elseif (empty($beginDate) && ! empty($endDate)) {
                $map['o.update_time'] = array(
                    'elt', 
                    $endDate . '235959');
            }
            // 查询条件---支付通道
            $pay_channel = I('post.batchStatus');
            // 必须是已经支付的
            $map['o.pay_status'] = 2;
            // 必须是当前的登录机构
            $map['o.node_id'] = array(
                'exp', 
                "in (" . $this->nodeIn() . ")");
            
            $field = array(
            "DATE_FORMAT(o.update_time,'%Y-%m-%d') AS trans_time,SUM(o.order_amt) AS order_amt,ifnull(SUM(o.freight),0.00) AS frieght,IFNULL(b.bonus_amount,0.00) AS bonus_amt,
			CASE WHEN o.pay_channel='1' THEN ifnull(SUM(o.fee_amt),0.00) WHEN o.pay_channel<>'1' THEN ifnull(SUM(o.fee_amt),0.00) END rate_amt, 
			CASE WHEN o.pay_channel='1' THEN ifnull(SUM(o.order_amt-o.fee_amt),0.00) WHEN o.pay_channel<>'1' THEN ifnull(SUM(o.order_amt-o.fee_amt),0.00) END act_amt,
			CASE WHEN o.pay_channel='1' THEN '支付宝' WHEN o.pay_channel='2' THEN '联动优势' WHEN o.pay_channel='3' THEN '微信' ELSE '其他' END pay_channel");
            
            $cols_arr['trans_time'] = '日期';
            $cols_arr['order_amt'] = '订单金额';
            $cols_arr['frieght'] = '运费';
            $cols_arr['bonus_amt'] = '优惠金额(红包)';
            $cols_arr['rate_amt'] = '支付扣费';
            $cols_arr['act_amt'] = '实收金额';
            $cols_arr['pay_channel'] = '支付通道';
            $sql = M()->table('ttg_order_info o')
                    ->field($field)
                    ->join('(SELECT order_id,bonus_amount from tbonus_use_detail group by order_id HAVING order_id>0) b ON b.order_id=o.order_id')
                    ->group('SUBSTR(o.update_time,1,8),o.pay_channel')
                    ->where($map)
                    ->order('SUBSTR(o.update_time,1,8) desc')
                    ->buildSql();
        }else{
            echo false;
        }
        log_write($sql);
        if (querydata_download($sql, $cols_arr, M()) == false) {
            $this->error('下载失败');
        }
    }
    
    //运费信息
    public function freightConfig(){

        // 获取电商运费配置
        S($_SESSION['userSessInfo']['node_id'] . 'cityExpressPrice', NULL); // 强制清除缓存文件
        $map = array(
            'node_id' => $this->node_id);
        $cityCodeModel = M('tcity_code');
        $cityExpressShippingModel = M('tcity_express_shipping');
        $info = $cityExpressShippingModel->where($map)->find();

        if ($info['express_rule'] != '') {
            $result = array();
            $expressRule = json_decode($info['express_rule'], TRUE);
            foreach ($expressRule as $key => $val) {
                $cityInfo = $cityCodeModel->field(
                    'province_code, province, path as city_code, city')
                    ->group('city')
                    ->where(
                    array(
                        'path' => array(
                            'in', 
                            '0,' . $val['cityCode'])))
                    ->select();
                $cityCode = '';
                $ruleArray = array();
                foreach ($cityInfo as $cityVal) {
                    if ($ruleArray[$cityVal['province_code']] == '') {
                        $ruleArray[$cityVal['province_code']]['province'] = $cityVal['province'];
                        if ($cityCode == '') {
                            $cityCode = $cityVal['city_code'];
                        } else {
                            $cityCode .= ',' . $cityVal['city_code'];
                        }
                        $ruleArray[$cityVal['province_code']]['city'] = $cityVal['city'];
                    } else {
                        $cityCode .= ',' . $cityVal['city_code'];
                        $ruleArray[$cityVal['province_code']]['city'] .= ';' .
                             $cityVal['city'];
                    }
                }
                $result['rule'] = $ruleArray;
                $result['cityCode'] = $cityCode;
                $result['price'] = $val['price'];
                $result['key'] = $key;
                $expressRule[$key] = $result;
            }
        }
   

        $this->assign('node_id', $this->node_id);
        $this->assign('freight_config', $info);
        $this->assign('expressRule', $expressRule);
        $this->display();
    }
    
    //物流通知
    public function logisticsNotice(){
        $map = array('node_id' => $this->node_id);
        $cityExpressShippingModel = M('tcity_express_shipping');
        $info = $cityExpressShippingModel->where($map)->getDbFields('sms_notice');
        $this->assign('freight_config', $info);
        $this->display();
    }
    
    //保护密码
    public function  protectPassword(){
        $passInfo = M('tnode_info')->where(array('node_id' => $this->node_id))->getField('account_pwd');
        $isSetPass = true;
        if(false == $passInfo){
            $isSetPass = false;
        }
        if ($this->isPost()) {
            if($this->node_id != session('accountPassCheck') && true === $isSetPass){
                $this->error("密码保护错误,请重新输入", array("关闭" => "javascript:window.location.reload();"));
            }
            $role_id = M('tuser_info')->where(
                array(
                    'user_name' => $this->user_name))->getField('role_id');
            if ($role_id > 2) {
                $this->error('非管理员账户不得操作收款帐号');
            }
            // 接收参数
            $error = '';
            // 数据校验
            $rules = array(
                'account_pwd' => array(
                    'null' => true, 
                    'name' => '保护密码'), 
                'account_pwd2' => array(
                    'null' => true, 
                    'name' => '确认保护密码'), 
                );
            $reqData = $this->_verifyReqData($rules);
            
            // 保护密码
            if (($reqData['account_pwd'] != '') && ($reqData['account_pwd2'] != '') && ($reqData['account_pwd'] != $reqData['account_pwd2'])) {
                $this->error("2次输入的保护密码不一致");
            }
            
            $nodeData = array();
            if ($reqData['account_pwd'] != '' && $reqData['account_pwd2'] != '') {
                $nodeData['account_pwd'] = md5($reqData['account_pwd']);
            }
            $result = M('tnode_info')->where(array('node_id' => $this->node_id))->save($nodeData);
            if ($result === false) {
                $this->error("更新保护密码失败");
            }
            session('accountPassCheck', '');
            $this->success("编辑成功", array("关闭" => "javascript:window.location.reload();"));
        }

        $this->assign('isSetPass', $isSetPass);
        $this->display();
    }
    public function promotion(){
        $recommend_type_arr = array(
            '1'=>'优惠促销',
            '2'=>'产品介绍',
            '3'=>'产品案例',
            '4'=>'最新动态',
            '5'=>'使用攻略',
            );
        $busi_type_arr = array(
            '1'=>'多米收单',
            '2'=>'多景联盟',
            '3'=>'多宝电商',
            '4'=>'多乐互动',
            '5'=>'翼码卡券',
            '6'=>'卡券商城',
            '7'=>'多赢积分',
            );
        $type = I('get.type',1);
        $this->assign('list',self::getRecommendList($type));
        $this->assign('recommend_type_arr',$recommend_type_arr);
        $this->assign('busi_type_arr',$busi_type_arr);
        $this->assign('type',$type);
        $this->display();
    }
    private function getRecommendList($type)
    {
        // 商户标签
        $nodeTag = M('tnode_tag')->getByNode_id($this->nodeId);
        $config['node_tag'] = empty($nodeTag) ? array() : $nodeTag;
        // 翼码标签
        $map['recommend_type'] = $type;
        $ymTag = M('tbusiness_recommend_new')->where($map)->select();
        $config['ym_tag'] = empty($ymTag) ? array() : $ymTag;
        // 商户点击记录
        $config['visit_trace'] = M('tnode_recommend_visit')->where(['node_id'=>$this->nodeId])->select();
        // 调用算法
        $algorithm = D('Algorithm');
        $algorithm->Init($config);
        $res = $algorithm->getResult();
        // 分页
        import('ORG.Util.Page'); 
        $Page = new Page(count($res), 10);
        $show = $Page->show();
        $this->assign('page',$show);
        return array_slice($res, $Page->firstRow, $Page->listRows);
    }
}