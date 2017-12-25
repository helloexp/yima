<?php

class MarketActiveAction extends BaseAction {

    public $_authAccessMap = '*';

    private $style_arr = array();

    private $transUrlsAdd = array();

    private $transUrlsEdit = array();

    private $batchList = array();

    private $batchList1 = array();

    private $batchList2 = array();

    private $batchList3 = array();

    private $batchList4 = array();

    public function _initialize() {
        parent::_initialize();
    }
    // 首页展示
    public function index() {
        $search['node_id'] = $this->node_id;
        $wh = "where node_id = " . $this->node_id;
        // 获取banner信息
        $bannerInfo = M('tmarketing_active')->where(
            array(
                'is_show' => '1', 
                'status' => '1'))
            ->order('banner_order asc,batch_order asc')
            ->select();
        foreach ($bannerInfo as $k => $v) {
            $bannerInfo[$k]['createUrl'] = U('Home/MarketActive/createUrl') .
                 "&actionName=" . $v['batch_create_url'];
        }
        // 获取活动图标信息
        $styleInfo = M('tmarketing_active')->field('batch_type,batch_icon')->select();
        foreach ($styleInfo as $k1 => $v1) {
            $this->style_arr[$v1['batch_type']] = $v1['batch_icon'];
        }
        // 活动的总访问量
        $visiteAmt = M('tmarketing_info')->where($search)->sum('click_count');
        // 总验码量
        /*
         * $sendvfyAmt =
         * M('tpos_trace')->where(array('node_id'=>$this->node_id,'trans_type'=>array('in','0,3'),'status'=>'0'))->count();
         */
        // 趋势图显示一个月内的趋势走向
        // 最近一月的访问量
        $tempMap = array();
        $begin_date = dateformat("-30 days", 'Ymd');
        $end_date = dateformat("0 days", 'Ymd');
        $tempMap['node_id'] = $this->node_id;
        $tempMap['day'] = array(
            array(
                'EGT', 
                $begin_date), 
            array(
                'ELT', 
                $end_date));
        $visiteAmtByDay = M('Tdaystat')->where($tempMap)
            ->field("day,sum(click_count) as click_count")
            ->group('day')
            ->select();
        $chartDateInfo = "";
        $chartDataInfo = "";
        foreach ($visiteAmtByDay as $k2 => $v2) {
            $chartDateInfo .= "'" . date('Y-m-d', strtotime($v2['day'])) . "',";
            $chartDataInfo .= "{$v2['click_count']},";
        }
        // 渠道分析图
        // 访问量最高的6个渠道
        $click_sum = "";
        $channel_arr = M('tchannel')->field('name,click_count')
            ->where("node_id='" . $this->node_id . "' and type in('1','2','3')")
            ->order('click_count desc')
            ->limit(9)
            ->select();
        if ($channel_arr) {
            foreach ($channel_arr as $v) {
                $click_sum .= '[' . "'" . $v['name'] . "'" . ',' .
                     $v['click_count'] . '],';
            }
        }
        // 访问量最高的3个活动
        $visite_arr = M('tmarketing_info')->field(
            'id,batch_type,name,send_count,click_count')
            ->where(array(
            'node_id' => $this->node_id))
            ->order('click_count desc')
            ->limit(3)
            ->select();
        $this->assign('chartDateInfo', $chartDateInfo);
        $this->assign('chartDataInfo', $chartDataInfo);
        $this->assign('bannerInfo', $bannerInfo);
        $this->assign('visiteIP', get_val($visiteIpInfo));
        $this->assign('visiteAmt', $visiteAmt);
        $this->assign('click_sum', $click_sum);
        $this->assign('visite_arr', $visite_arr);
        $this->assign('style_arr', $this->style_arr);
        $this->display();
    }
    // 开展活动
    public function createNew() {
        // 获取当前活动属于哪一类
        ! I('typelist', '0') or $map['batch_belongto'] = I('typelist', '0');
        // 活动必须有效
        $map['status'] = 1;
        // 只有翼码市场部才显示注册有礼,且列表模板不在此处显示
        if ($this->node_id != '00014056') {
            $map['batch_type'] = array(
                'not in', 
                '8,32');
        } else {
            $map['batch_type'] = array(
                'neq', 
                '8');
        }
        // 导入分页类
        import('@.ORG.Util.Page');
        $mapcount = M('tmarketing_active')->where($map)->count();
        $Page = new Page($mapcount, 8); // 实例化分页类
        $Page->setConfig('theme', 
            '%upPage% %prePage%  %linkPage%  %nextPage% %downPage% ');
        $Page->setConfig('prev', '«');
        $Page->setConfig('next', '»');
        $show = $Page->show(); // 分页显示输出
        $batchList = M('tmarketing_active')->where($map)
            ->limit($Page->firstRow, $Page->listRows)
            ->order('batch_order')
            ->select();
        // 付费用户不用显示日价格和免费使用多少天
        $isFreeUser = D('node')->getNodeVersion($this->node_id);
        foreach ($batchList as $k => $v) {
            $batchList[$k]['batch_create_url'] = U(
                'Home/MarketActive/createUrl') . "&actionName=" .
                 $v['batch_create_url'];
            $batchList[$k]['needShowPayIcon'] = D('MarketActive')->needShowPayIcon(
                $this->node_id, $v['batch_type'], $isFreeUser);
            $commentAndExplian = D('MarketActive')->getComment($this->node_id, 
                $v['batch_type'], $isFreeUser, $v['price']);
            $batchList[$k]['comment'] = $commentAndExplian['comment'];
            $batchList[$k]['explain'] = $commentAndExplian['explain'];
        }
        // 获取活动图标信息
        $styleInfo = M('tmarketing_active')->field('batch_type,batch_icon')->select();
        foreach ($styleInfo as $k1 => $v1) {
            $this->style_arr[$v1['batch_type']] = $v1['batch_icon'];
        }
        $this->assign('style_arr', $this->style_arr);
        $this->assign('batchList', $batchList);
        $this->assign('isFreeUser', $isFreeUser);
        $this->assign('typelist', I('typelist', '0'));
        $this->assign('page', $show);
        $this->display();
    }
    // 活动列表
    public function listNew() {
        //是否有m1的权限（是否购买过行销活动打包）
        $hasM1 = $this->hasPayModule('m1') ? 1 : 0;
        $this->assign('hasM1', $hasM1);
        $marketingActive = M('tmarketing_active');
        $marketingInfoModel = M('tmarketing_info');
        $batch_name = I('key', '', 'htmlspecialchars,trim');
        $start_time = I('start_time', '', 'htmlspecialchars,trim');
        $end_time = I('end_time', '', 'htmlspecialchars,trim');
        $batchType = I('batchtype', '');
        $status = I('status', '');
        $liststyle = I('liststyle', '1');
        $marketingArr = $marketingActive->where(
            array(
                'status' => '1'))
            ->field('batch_type,batch_create_url')
            ->select();
        $batch_type = array();
        $batch_Url = array();
        foreach ($marketingArr as $k1 => $v1) {
            $batch_type[] = $v1['batch_type'];
            $batch_Url[$v1['batch_type']] = $v1['batch_create_url'];
        }
        // 营销活动比较特殊，不应根据status来判断,不管列表模板是否开启，都应该在列表模板页面显示
        if ($liststyle == '2') {
            $batch_type[] = "8";
            $batch_Url['8'] = 'List';
        } else {
            foreach ($batch_type as $kj => $vj) {
                if ($vj == '8') {
                    unset($batch_type[$kj]);
                }
                // 只有翼码市场部才显示注册有礼
                if ($vj == '32' && $this->node_id != '00014056') {
                    unset($batch_type[$kj]);
                }
            }
        }
        $map = array(
            'node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"), 
            'batch_type' => array(
                'in', 
                $batch_type));
        if (! empty($batch_name)) {
            $map['name'] = array(
                'like', 
                '%' . $batch_name . '%');
            $this->assign('batch_name', $batch_name);
        }
        if (! empty($start_time)) {
            $map['add_time'][] = array(
                'EGT', 
                $start_time . '000000');
            $this->assign('start_time', $start_time);
        }
        if (! empty($end_time)) {
            $map['add_time'][] = array(
                'ELT', 
                $end_time . '235959');
            $this->assign('end_time', $end_time);
        }
        if (! empty($batchType)) {
            $map['batch_type'] = $batchType;
            $this->assign('batchType', $batchType);
        }
        if (! empty($status)) {
            if ($status == '4') {
                $map['status'] = 2;
            } else if ($status == '5') {
                $map['pay_status'] = "0";
            } else {
                if ($status == '1') {
                    $map['start_time'] = array(
                        'GT', 
                        date('Ymd') . '000000');
                } elseif ($status == '3') {
                    $map['end_time'] = array(
                        'LT', 
                        date('Ymd') . '235959');
                } else {
                    $map['start_time'] = array(
                        'ELT', 
                        date('Ymd') . '000000');
                    $map['end_time'] = array(
                        'EGT', 
                        date('Ymd') . '235959');
                }
            }
            $this->assign('status', $status);
        }
        import('@.ORG.Util.Page'); // 导入分页类
        $mapcount = $marketingInfoModel->where($map)->count();
        $Page = new Page($mapcount, 8); // 实例化分页类
        $Page->setConfig('theme', 
            '%upPage% %prePage%  %linkPage%  %nextPage% %downPage% ');
        $Page->setConfig('prev', '«');
        $Page->setConfig('next', '»');
        $show = $Page->show(); // 分页显示输出
        $list = $marketingInfoModel->where($map)
            ->order('id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        if ($list) {
            $cjRuleModel = M('TcjRule');
            $ciBatchModel = M('TcjBatch');
            foreach ($list as $k => $v) {
                // 付费用户(购买过打包工具的用户)，状态全为1//这里的pay_status仅仅用于显示那个付款按钮,不代表真正的付款状态
                if (! D('node')->getNodeVersion($this->node_id)) {
                    $list[$k]['pay_status'] = '1';
                }
                // 判断订单是否免费
                $orderType = M('tactivity_order')->getFieldByM_id($v['id'], 
                    'order_type');
                if ($orderType == '2') {
                    $list[$k]['pay_status'] = '1';
                }
                // 是否是购买单次活动的那种活动
                if (D('BindChannel')->isInFreeUserBuyList($v['batch_type'])) {
                    // 判断这个活动类型有没有免费的活动订单(现在只有大转盘有免费订单)，没有的话，第一次的活动是免费的（付款状态应该为已付款）
                    if ($v['batch_type'] == '53') {
                        $hasFreeOrder = D('MarketActive')->hasFreeActivity(
                            $this->node_id, $v['batch_type']);
                        if (! $hasFreeOrder) {
                            $list[$k]['pay_status'] = '1';
                        }
                    }
                } else { // 不再这个范围内的就不显示付款按钮
                    $list[$k]['pay_status'] = '1';
                }
                $is_publish = M('tbatch_channel')->getFieldByBatch_id($v['id'], 
                    "id");
                if (empty($is_publish)) {
                    $list[$k]['is_publish'] = 0;
                } else {
                    $list[$k]['is_publish'] = 1;
                }
                $list[$k]['edit_url'] = U('Home/MarketActive/editUrl', 
                    array(
                        'actionName' => $batch_Url[$v['batch_type']], 
                        'id' => $v['id']));
                $list[$k]['actionName'] = $batch_Url[$v['batch_type']];
                $list[$k]['is_mem_batch'] = 'N';
                if (strtotime($v['end_time']) >= time()) {
                    $list[$k]['leave_time'] = floor(
                        (strtotime($v['end_time']) - time()) / (3600 * 24));
                } else {
                    $list[$k]['leave_time'] = '0';
                }
                if ($v['is_cj'] == '1') {
                    $rule_id = $cjRuleModel->where(
                        array(
                            'batch_id' => $v['id'], 
                            'node_id' => $v['node_id'], 
                            'status' => '1'))->getField('id');
                    $mem_batch = $ciBatchModel->where(
                        array(
                            'cj_rule_id' => $rule_id, 
                            'member_batch_id' => array(
                                'neq', 
                                '')))->find();
                    if ($mem_batch) {
                        $list[$k]['is_mem_batch'] = 'Y';
                    }
                }
                //是否有发送奖品失败的记录
                $failedRecord = D('SendAwardTrace')->getFailedRecord($this->node_id, $v['id']);
                $list[$k]['failedRecordFlag'] = $failedRecord ? 1 : 0;
            }
        }
        
        // /////////////////////////////////////////////////////////////////////////////
        // //非标页面处理
        // /
        
        if ($this->node_id == C('hbtpybx.node_id')) {
            $this->assign('fb_type', 'hbtpybx');
        }
        if ($this->node_id == C('sxtpybx.node_id')) {
            $this->assign('fb_type', 'sxtpybx');
        }
        if ($this->node_id == C('gstpybx.node_id')) {
            $this->assign('fb_type', 'gstpybx');
        }
        if (in_array($this->node_id, C('DM_Haagen_Dazs'))) {
            $this->assign('hgds_flag', true);
        }
        
        if ($this->node_id == C('GWYL_NODE')) {
            $this->assign('gwyl', true);
        }
        
        $fb_batch_list = array(
            '2', 
            '3', 
            '10', 
            '20');
        
        $this->assign('fb_batch_list', $fb_batch_list);
        
        // /////////////////////////////////////////////////////////////////////////////
        
        $node_short_name = M('tnode_info')->where(
            array(
                'node_id' => $this->node_id))->getField('node_short_name');
        $this->assign('batchlist', $list);
        $this->assign('batchInfo', $batch_type);
        $this->assign('liststyle', $liststyle);
        $this->assign('batch_type_name', C('BATCH_TYPE_NAME'));
        $this->assign('node_short_name', $node_short_name);
        $this->assign('page', $show);
        $this->display();
    }

    public function createUrl() {
        $actionName = I('actionName', "");
        $isNewActivity = I('isNewActivity', false); // 是否是新步骤的那种活动
        ! empty($actionName) or $this->error("非法操作!");
        $newUrl = U('LabelAdmin/' . $actionName . '/setActBasicInfo');
        switch ($actionName) {
            // 抽奖
            case 'News':
                $addUrl = U('LabelAdmin/News/add') .
                     "&model=event&type=draw&action=create&customer=" .
                     $this->node_type_name;
                break;
            // 市场调研
            case 'Bm':
                $addUrl = U('LabelAdmin/Bm/add') .
                     "&model=event&type=survey&action=create&customer=" .
                     $this->node_type_name;
                break;
            // 有奖答题
            case 'Answers':
                $addUrl = U('LabelAdmin/Answers/add') .
                     "&model=event&type=question&action=create&customer=" .
                     $this->node_type_name;
                break;
            // 投票
            case 'Vote':
                $addUrl = U('LabelAdmin/Vote/add') .
                     "&model=event&type=survey&action=create&customer=" .
                     $this->node_type_name;
                break;
            // 爱拼才会赢
            case 'Spelling':
                $addUrl = U('LabelAdmin/Spelling/add') .
                     "&model=event&type=draw&action=create&customer=" .
                     $this->node_type_name;
                break;
            // 礼品派发
            case 'Feedback':
                $addUrl = U('LabelAdmin/Feedback/add') .
                     "&model=event&type=gift&action=create&customer=" .
                     $this->node_type_name;
                break;
            // 优惠券
            case 'Coupon':
                $addUrl = U('LabelAdmin/Coupon/add') .
                     "&model=event&type=coupon&action=create&customer=" .
                     $this->node_type_name;
                break;
            // 注册有礼
            case 'Registration':
                $addUrl = U('LabelAdmin/Registration/add') .
                     "&model=event&type=Registration&customer=" .
                     $this->node_type_name;
                break;
            // 列表模板
            case 'List':
                // U('LabelAdmin/List/index');
                // "&model=event&type=combination&customer=".$node_type_name;
                break;
            // 电子海报
            case 'Poster':
                $addUrl = U('LabelAdmin/Poster/add');
                break;
            // 谁是大腕儿
            case 'Dawan':
                $addUrl = U('LabelAdmin/Dawan/add');
                break;
            // 妈妈我爱你
            case 'MamaSjb':
                $addUrl = U('LabelAdmin/MamaSjb/add');
                break;
            // 劳动最光荣
            case 'LaborDay':
                $addUrl = U('LabelAdmin/LaborDay/add');
                break;
            // 打炮总动员
            case 'Spring2015':
                $addUrl = U('LabelAdmin/Spring2015/add');
                break;
            // 端午节
            case 'DuanWu':
                $addUrl = U('LabelAdmin/DuanWu/add');
                break;
            // 圣诞节
            case 'SnowBall':
                $addUrl = U('LabelAdmin/SnowBall/add');
                break;
            // 中秋节
            case 'ZhongQiu':
                $addUrl = U('LabelAdmin/ZhongQiu/add');
                break;
            // 七夕节
            case 'Qixi':
                $addUrl = U('LabelAdmin/Qixi/setActBasicInfo');
                break;
            // 母亲节
            case 'Mama':
                $addUrl = U('LabelAdmin/Mama/add');
                break;
            // 真假大冒险
            case 'LogoGuess':
                $addUrl = U('LabelAdmin/LogoGuess/add') .
                     "&model=event&type=315theme&action=create&customer=" .
                     $this->node_type_name;
                break;
            // 女人我最大
            case 'Women':
                $addUrl = U('LabelAdmin/Women/add') .
                     "&model=event&type=38theme&action=create&customer=" .
                     $this->node_type_name;
                break;
            // 天生一对
            case 'Valentine':
                $addUrl = U('LabelAdmin/Valentine/add') .
                     "&model=event&type=valentine&action=create&customer=" .
                     $this->node_type_name;
                break;
            // 码上有红包
            case 'Special':
                $addUrl = U('LabelAdmin/Special/add') .
                     "&model=event&type=envelope&action=create&customer=" .
                     $this->node_type_name;
                break;
            // 冠军竞猜
            case 'ChampionGuess':
                $addUrl = U('ZtWorldcup/ChampionGuess/add') .
                     "&model=event&type=recruiting&action=create&customer=" .
                     $this->node_type_name;
                break;
            // 赛事竞猜
            case 'MatchGuess':
                $addUrl = U('ZtWorldcup/MatchGuess/add');
                break;
            // 签到有礼
            case 'DakaHasGift':
                $addUrl = U('ZtWorldcup/DakaHasGift/add');
                break;
            // 进球有礼
            case 'GoalHasGift':
                $addUrl = U('ZtWorldcup/GoalHasGift/add') .
                     "&model=event&type=draw&action=create&customer=" .
                     $this->node_type_name;
                break;
            // 粉丝招募
            case 'MemberRegistration':
                $addUrl = U('LabelAdmin/MemberRegistration/add') .
                     "&model=event&type=recruiting&action=create&customer=" .
                     $this->node_type_name;
                break;
            // 粉丝回馈
            case 'MemberFeedback':
                $addUrl = U('Member/MemberFeedback/add') .
                     "&model=event&type=feedback&action=create&customer=" .
                     $this->node_type_name;
                break;
            // 图文编辑
            case 'Med':
                $addUrl = U('LabelAdmin/Med/add') .
                     "&model=event&type=question&action=create&customer=" .
                     $this->node_type_name;
                break;
            // 粽礼寻Ta
            case 'ZongZi':
                $addUrl = U('LabelAdmin/ZongZi/add') .
                     "&model=event&type=question&action=create&customer=" .
                     $this->node_type_name;
                break;
            // 大转盘抽奖
            case 'DrawLotteryAdmin':
                $addUrl = U('LabelAdmin/DrawLotteryAdmin/setActBasicInfo');
                break;
            // 我是升旗手
            case 'RaiseFlag':
                $addUrl = U('LabelAdmin/RaiseFlag/setActBasicInfo');
                break;
            // 双旦祝福
            case 'TwoFestivalAdmin':
                $addUrl = U('LabelAdmin/TwoFestivalAdmin/setActBasicInfo');
                break;
            // 金猴闹春
            case 'SpringMonkey':
                $addUrl = U('LabelAdmin/SpringMonkey/setActBasicInfo');
                break;
            default:
                if ($isNewActivity) {
                    $addUrl = $newUrl;
                } else {
                    $this->error("此活动无法创建!");
                }
                break;
        }
        redirect($addUrl);
    }

    public function editUrl() {
        $actionName = I('actionName', "");
        $id = I('id', "");
        ! empty($actionName) or $this->error("非法操作!");
        ! empty($id) or $this->error("非法操作!");
        switch ($actionName) {
            // 抽奖
            case 'News':
                $editUrl = U('LabelAdmin/News/edit');
                break;
            // 市场调研
            case 'Bm':
                $editUrl = U('LabelAdmin/Bm/edit');
                break;
            // 有奖答题
            case 'Answers':
                $editUrl = U('LabelAdmin/Answers/edit');
                break;
            // 投票
            case 'Vote':
                $editUrl = U('LabelAdmin/Vote/edit');
                break;
            // 爱拼才会赢
            case 'Spelling':
                $editUrl = U('LabelAdmin/Spelling/edit');
                break;
            // 礼品派发
            case 'Feedback':
                $editUrl = U('LabelAdmin/Feedback/edit');
                break;
            // 优惠券
            case 'Coupon':
                $editUrl = U('LabelAdmin/Coupon/edit');
                break;
            // 注册有礼
            case 'Registration':
                $editUrl = U('LabelAdmin/Registration/edit');
                break;
            // 列表模板
            case 'List':
                $editUrl = U('LabelAdmin/List/setActBasicInfo');
                break;
            // 电子海报
            case 'Poster':
                $editUrl = U('LabelAdmin/Poster/add');
                break;
            // 谁是大腕儿
            case 'Dawan':
                $editUrl = U('LabelAdmin/Dawan/edit');
                break;
            // 妈妈我爱你
            case 'MamaSjb':
                $editUrl = U('LabelAdmin/MamaSjb/edit');
                break;
            // 劳动最光荣
            case 'LaborDay':
                $editUrl = U('LabelAdmin/LaborDay/edit');
                break;
            // 打炮总动员
            case 'Spring2015':
                $editUrl = U('LabelAdmin/Spring2015/edit');
                break;
            // 端午节
            case 'DuanWu':
                $editUrl = U('LabelAdmin/DuanWu/edit');
                break;
            // 圣诞节
            case 'SnowBall':
                $editUrl = U('LabelAdmin/SnowBall/edit');
                break;
            // 中秋节
            case 'ZhongQiu':
                $editUrl = U('LabelAdmin/ZhongQiu/edit');
                break;
            // 七夕节
            case 'Qixi':
                $editUrl = U('LabelAdmin/Qixi/setActBasicInfo');
                break;
            // 母亲节
            case 'Mama':
                $editUrl = U('LabelAdmin/Mama/edit');
                break;
            // 真假大冒险
            case 'LogoGuess':
                $editUrl = U('LabelAdmin/LogoGuess/edit');
                break;
            // 女人我最大
            case 'Women':
                $editUrl = U('LabelAdmin/Women/edit');
                break;
            // 天生一对
            case 'Valentine':
                $editUrl = U('LabelAdmin/Valentine/edit');
                break;
            // 码上有红包
            case 'Special':
                $editUrl = U('LabelAdmin/Special/edit');
                break;
            // 冠军竞猜
            case 'ChampionGuess':
                $editUrl = U('ZtWorldcup/ChampionGuess/edit');
                break;
            // 赛事竞猜
            case 'MatchGuess':
                $editUrl = U('ZtWorldcup/MatchGuess/edit');
                break;
            // 签到有礼
            case 'DakaHasGift':
                $editUrl = U('ZtWorldcup/DakaHasGift/edit');
                break;
            // 进球有礼
            case 'GoalHasGift':
                $editUrl = U('ZtWorldcup/GoalHasGift/edit');
                break;
            // 粉丝招募
            case 'MemberRegistration':
                $editUrl = U('LabelAdmin/MemberRegistration/edit');
                break;
            // 粉丝回馈
            case 'MemberFeedback':
                $editUrl = U('Member/MemberFeedback/edit');
                break;
            // 图文编辑
            case 'Med':
                $editUrl = U('LabelAdmin/Med/edit');
                break;
            // 粽礼寻Ta
            case 'ZongZi':
                $editUrl = U('LabelAdmin/ZongZi/edit');
                break;
            // 大转盘抽奖
            case 'DrawLotteryAdmin':
                $editUrl = U('LabelAdmin/DrawLotteryAdmin/setActBasicInfo');
                break;
            // 我是升旗手
            case 'RaiseFlag':
                $editUrl = U('LabelAdmin/RaiseFlag/setActBasicInfo');
                break;
            // 双旦祝福
            case 'TwoFestivalAdmin':
                $editUrl = U('LabelAdmin/TwoFestivalAdmin/setActBasicInfo');
                break;
            // 金猴闹春
            case 'SpringMonkey':
                $editUrl = U('LabelAdmin/SpringMonkey/setActBasicInfo');
                break;
            // 微官网
            case 'MicroWeb':
                $editUrl = U('MicroWeb/Index/add', array('mw_batch_id' => $id));
                redirect($editUrl);
                break;
            // 闪购
            case 'GoodsSale':
                $editUrl = U('Ecshop/GoodsSale/edit');
                break;
            //会员招募
            case 'Member':
                $editUrl = U('Wmember/Member/setActBasicInfo');
                break;
            // 门店导航
            case 'Navigate':
                $editUrl = U('Home/Store/navigation');
                redirect($editUrl);
                break;
            // 马上买
            case 'MaShangMai':
                $editUrl = U('Ecshop/MaShangMai/edit');
                break;
            default:
                $this->error("此活动无法编辑!");
                break;
        }
        if (in_array($actionName, 
            array(
                'Member',
                'Qixi', 
                'DrawLotteryAdmin', 
                'RaiseFlag', 
                'List', 
                'TwoFestivalAdmin',
                'SpringMonkey'))) {
            redirect($editUrl . '&m_id=' . $id);
        }
        redirect($editUrl . '&id=' . $id);
    }

    public function setPrize() {
        $actionName = I('actionName', "");
        $id = I('id', "");
        ! empty($actionName) or $this->error("非法操作!");
        ! empty($id) or $this->error("非法操作!");
        switch ($actionName) {
            // 七夕
            case 'Qixi':
                $setPrizeUrl = U('LabelAdmin/Qixi/setPrize');
                break;
            // 大转盘
            case 'DrawLotteryAdmin':
                $setPrizeUrl = U('LabelAdmin/DrawLotteryAdmin/setPrize');
                break;
            case 'RaiseFlag':
                $setPrizeUrl = U('LabelAdmin/RaiseFlag/setPrize');
                break;
            case 'TwoFestivalAdmin':
                $setPrizeUrl = U('LabelAdmin/TwoFestivalAdmin/setPrize');
                break;
            case 'SpringMonkey':
                $setPrizeUrl = U('LabelAdmin/SpringMonkey/setPrize');
                break;
            default:
                $this->error("此活动无法设置奖项!");
                break;
        }
        redirect($setPrizeUrl . '&m_id=' . $id);
    }
}

