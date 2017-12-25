<?php

/**
 *
 * @author lwb Time 20150720
 */
class BindChannelModel extends Model {

    const OFF_LINE_TYPE = 2;

    const ON_LINE_TYPE = 1;

    const ELSE_CHANNEL = 5;

    const APP_CHANNEL = 6;
    
    protected $tableName = 'tchannel';

    public function _initialize() {
        import('@.Vendor.CommonConst') or die('include file fail.');
    }

    /**
     * 获取网页title
     *
     * @param int $batchType 活动种类的id
     * @return string $webTitle 网页的title
     */
    public function getWebTitle($batchType) {
        $webTitle = '';
        switch ($batchType) {
            case CommonConst::BATCH_TYPE_RECRUIT:
                $webTitle = '会员管理-招募活动创建-活动发布';
                break;
            default:
                $webTitle = '发布活动_营销活动中心_翼码旺财';
        }
        return $webTitle;
    }

    /**
     * 检查是否可以发布，并且返回相应的提示和链接
     *
     * @param int $nodeId
     * @param string $batchId
     * @return array(array('url' => {url}, 'tips' => {tips}), ...)
     */
    public function checkCanPublish($nodeId, $batchId) {
        $marketModel = M('tmarketing_info');
        $mInfo = $marketModel->where(
            array(
                'id' => $batchId, 
                'node_id' => $nodeId))->find();
        if (! $mInfo) {
            throw_exception('参数错误');
        }
        switch ($mInfo['batch_type']) {
            case CommonConst::BATCH_TYPE_RECRUIT:
                $result = $this->checkRecruitPublish($nodeId, $mInfo);
                break;
            case CommonConst::BATCH_TYPE_QIXI:
                $result = $this->checkQixiPublish($nodeId, $mInfo);
                break;
            case CommonConst::BATCH_TYPE_WEEL:
                $result = $this->checkWheelPublish($nodeId, $mInfo);
                break;
            case CommonConst::BATCH_TYPE_RAISEFLAG:
                $result = $this->checkRaiseFlagPublish($nodeId, $mInfo);
                break;
            case CommonConst::BATCH_TYPE_TWO_VESTIVAL:
                $result = $this->checkTwoFestivalPublish($nodeId, $mInfo);
                break;
            case CommonConst::BATCH_TYPE_SPRINGMONKEY:
                $result = $this->checkSpringMonkeyPublish($nodeId, $mInfo);
                break;
            default:
                $result = true;
        }
        return $result;
    }

    /**
     * 获取取消按钮的链接
     *
     * @param int $batchType
     * @param int $is_new
     *
     * @return string $cancelUrl
     */
    public function getCancelUrl($batchType, $is_new = 1) {
        switch ($batchType) {
            case CommonConst::BATCH_TYPE_RECRUIT:
                $cancelUrl = U('Wmember/Member/recruit');
                break;
            case CommonConst::BATCH_TYPE_GOODSSALE:
                $cancelUrl = U('Ecshop/O2OHot/index/', 
                    array(
                        'batch_type' => CommonConst::BATCH_TYPE_GOODSSALE));
                break;
            case CommonConst::BATCH_TYPE_ZQCUT:
            case CommonConst::BATCH_TYPE_NEWSTORE:
            case CommonConst::BATCH_TYPE_MASHANGMAI:
                $cancelUrl = U('Ecshop/GoodsInfo/indexNew/', 
                    array(
                        'batch_type' => CommonConst::BATCH_TYPE_NEWSTORE));
                break;
            case CommonConst::BATCH_TYPE_POSTER:
                $cancelUrl = U('LabelAdmin/Poster/index');
                break;
            case CommonConst::BATCH_TYPE_RICH_MEDIA:
                $cancelUrl = U('MarketActive/Tool/pictext');
                break;
            case CommonConst::BATCH_TYPE_NEWPOSTER:
                $cancelUrl = U('MarketActive/NewPoster/index');
                break;
            case CommonConst::BATCH_TYPE_TEMPLATE:
                $cancelUrl = U('MarketActive/NewPictext/index');
                break;
            case CommonConst::BATCH_TYPE_STORE_LBS:
                $cancelUrl = U('Home/Store/navigation_list');
                break;
            default:
                $cancelUrl = U('MarketActive/Activity/MarketList');
        }
        return $cancelUrl;
    }

    public function isShowSteps($batchType) {
        $showSteps = false;
        $useStepsActivities = C('ACTIVITY_MODULE');
        $stepBarBatchTypeArr = array_keys($useStepsActivities);
        if (in_array($batchType, $stepBarBatchTypeArr)) {
            $showSteps = true;
        }
        return $showSteps;
    }
    // 是否存在系统专用渠道，没有就创建一个
    public function appExists($nodeId) {
        $resId = array();
        $ret = M('tchannel')->where(
            array(
                'type' => 6, 
                'sns_type' => '62', 
                'status' => 1, 
                'node_id' => $nodeId))->find();
        if (! $ret) {
            $data = array(
                'name' => '旺财APP', 
                'type' => 6, 
                'sns_type' => '62', 
                'status' => 1, 
                'node_id' => $nodeId, 
                'add_time' => date('YmdHis'));
            $nRet = M('tchannel')->add($data);
            $resId[0] = array(
                'id' => $nRet, 
                'name' => '旺财APP');
        } else {
            $resId[0] = array(
                'id' => $ret['id'], 
                'name' => '旺财APP');
        }
        $ret = M('tchannel')->where(
            array(
                'type' => 6, 
                'sns_type' => '63',  // 多乐互动专用渠道
                'status' => 1, 
                'node_id' => $nodeId))->find();
        if (! $ret) {
            $data = array(
                'name' => '多乐互动专用渠道', 
                'type' => 6, 
                'sns_type' => '63',  // 多乐互动专用渠道
                'status' => 1, 
                'node_id' => $nodeId, 
                'batch_type' => -1, 
                'add_time' => date('YmdHis'));
            $nRet = M('tchannel')->add($data);
            $resId[1] = array(
                'id' => $nRet, 
                'name' => '多乐互动专用渠道');
        } else {
            $resId[1] = array(
                'id' => $ret['id'], 
                'name' => '多乐互动专用渠道');
        }
        return $resId;
    }

    public function getRangeChannelInfo($nodeId) {
        $channelModel = M('tchannel');
        // 线下渠道(标签渠道)
        $offLineChannel = $channelModel->where(
            array(
                'type' => self::OFF_LINE_TYPE, 
                'status' => '1', 
                'node_id' => $nodeId))->order('batch_type asc,id desc')->getField(
            'id,id,name,batch_type', true);
        if (! $offLineChannel) {
            $offLineChannel = array();
        }
        
        // 原先叫互联网渠道(2腾讯,3QQ空间,4人人网,5开心网,6豆瓣)->现在叫社交渠道
        $onLineChannel = $channelModel->where(
            array(
                'type' => self::ON_LINE_TYPE, 
                'sns_type' => array(
                    'in', 
                    '2,3,4,5,6'), 
                'status' => '1', 
                'node_id' => $nodeId))->getField('id,id,name,batch_type', true);
        if (! $onLineChannel) {
            $onLineChannel = array();
        }
        // 自定义渠道
        $selfDefinedChannel = $channelModel->where(
            array(
                'type' => self::ON_LINE_TYPE, 
                'sns_type' => '11', 
                'node_id' => $nodeId, 
                'status' => '1'))->getField('id,id,name,batch_type', true);
        if (! $selfDefinedChannel) {
            $selfDefinedChannel = array();
        }
        
        // 员工渠道
        $staffChannelId = $channelModel->where(
            array(
                'type' => self::ELSE_CHANNEL, 
                'sns_type' => '51',  // 员工渠道
                'node_id' => $nodeId, 
                'status' => '1'))->getField('id');
        // 系统专用渠道
        $appChannel = $channelModel->where(
            array(
                'type' => self::APP_CHANNEL, 
                'sns_type' => array(
                    'in', 
                    '62,63'),  // 旺财系统专用渠道
                'node_id' => $nodeId, 
                'status' => '1'))->getField('id,id,name,batch_type', true);
        if (! $appChannel) {
            $appChannel = array();
        }
        return array(
            'offLineChannel' => $offLineChannel, 
            'onLineChannel' => $onLineChannel, 
            'selfDefinedChannel' => $selfDefinedChannel, 
            'appChannel' => $appChannel, 
            'staffChannelId' => $staffChannelId);
    }

    public function getMarketInfo($nodeId, $batch_type, $batch_id, 
        $fields = array('name', 'is_new')) {
        $marketModel = M('tmarketing_info');
        $map2 = array(
            'id' => $batch_id, 
            'batch_type' => $batch_type, 
            'node_id' => $nodeId);
        $batch_arr = $marketModel->where($map2)->Field($fields)->find();
        if (! $batch_arr) {
            throw_exception('活动不存在');
        }
        return $batch_arr;
    }

    public function getSelectChannelId($nodeId, $batch_id, $batch_type, 
        $rangeChannelInfo) {
        // 绑定过的渠道,里面可能包含不用显示出来的渠道(新浪,预览,o2o活动)
        $selectedChannelId = M('tbatch_channel')->where(
            array(
                'batch_type' => $batch_type, 
                'batch_id' => $batch_id, 
                'node_id' => $nodeId, 
                'status' => '1'))->getField('channel_id', true);
        // 取出可选范围内的channel_id
        $rangeChannelId = array_merge(
            array_keys($rangeChannelInfo['offLineChannel']), 
            array_keys($rangeChannelInfo['onLineChannel']), 
            array_keys($rangeChannelInfo['selfDefinedChannel']), 
            array_keys($rangeChannelInfo['appChannel']));
        // 交集部分就是被选中的channelId
        $selectedChannelId = array_intersect($rangeChannelId, 
            is_array($selectedChannelId) ? $selectedChannelId : array());
        return $selectedChannelId;
    }

    public function getSelectChannelInfo($selectedChannelId, $rangeChannelInfo) {
        // 然后把被选中的channelID的其他信息整成数组
        $rangeChannel = $rangeChannelInfo['offLineChannel'] +
             $rangeChannelInfo['onLineChannel'] +
             $rangeChannelInfo['selfDefinedChannel'] +
             $rangeChannelInfo['appChannel'];
        $selectedChannel = array();
        foreach ($selectedChannelId as $val) {
            $selectedChannel[] = $rangeChannel[$val];
        }
        return $selectedChannel;
    }

    /**
     *
     * @param array $channelId
     * @return array $snsTypeChannelId
     */
    public function getSnsTypeArr($nodeId, $channelId) {
        // type1表示线上
        $snsTypeChannelId = M('tchannel')->where(
            array(
                'node_id' => $nodeId, 
                'type' => '1', 
                'sns_type' => CommonConst::SNS_TYPE_QIYEWEB,  // 企业官网
                'id' => array(
                    'in', 
                    $channelId)))->getField('id');
        return $snsTypeChannelId;
    }

    /**
     *
     * @param int $nodId
     * @param array $channel
     * @param int $batch_id
     * @return array ('channelInfoArr' => 需要显示的channelInfo,
     *         'channelIdToBatchChannelId' => 下标是channel_id值为channel_id,id)
     */
    public function getChannelInfoAndBatchChannelId($nodId, $channel, $batch_id) {
        $exec = M('tbatch_channel');
        $carr = array();
        $succ_arr = array();
        if (! empty($channel)) {
            $search_map['channel_id'] = array(
                'in', 
                $channel);
            $search_map['batch_id'] = $batch_id;
            $search_map['status'] = '1';
            $succ_arr = $exec->where($search_map)->getField(
                'channel_id,channel_id,id');
            
            // 渠道详情
            $m_model = M('tchannel');
            $m_map = array(
                'node_id' => $nodId, 
                'id' => array(
                    'in', 
                    $channel));
            $m_arr = $m_model->where($m_map)->select();
            
            foreach ($m_arr as $k => $v) {
                if ($v['type'] == 1 || $v['type'] == 6) {
                    $v['download_code_img_url'] = U('LabelAdmin/ShowCode/index', 
                        array(
                            'isdown' => '1', 
                            'id' => $succ_arr[$v['id']]['id'])); // 下载的url,batch_channel的id
                    $v['qr_code_url'] = U('Label/Label/index', 
                        array(
                            'id' => $succ_arr[$v['id']]['id'])); // 二维码的url
                    $v['qr_code_src'] = U('LabelAdmin/ShowCode/index', 
                        array(
                            'id' => $succ_arr[$v['id']]['id'])); // 二维码的src
                } else {
                    $v['download_code_img_url'] = U(
                        'LabelAdmin/ChannelSetCode/Code', 
                        array(
                            'isdown' => '1', 
                            'id' => $v['id'])); // channel的id
                    $v['qr_code_url'] = U('Label/Channel/index', 
                        array(
                            'id' => $v['id']));
                    $v['qr_code_src'] = U('LabelAdmin/ChannelSetCode/code', 
                        array(
                            'id' => $v['id']));
                }
                $v['copy_url'] = U('Label/Label/index', 
                    array(
                        'id' => $succ_arr[$v['id']]['id']), '', '', true); // 完整访问的链接地址
                $carr[$v['id']] = $v;
            }
        }
        return array(
            'channelInfoArr' => $carr, 
            'channelIdToBatchChannelId' => $succ_arr);
    }

    /**
     * 是否已付款
     *
     * @param int $nodeId
     * @param int $mId
     * @return boolean
     */
    public function isPaid($nodeId, $mId) {
        $payStatus = M('tmarketing_info')->where(
            array(
                'node_id' => $nodeId, 
                'id' => $mId))->getField('pay_status');
        $isPaid = true;
        if ($payStatus === '0') {
            $isPaid = false;
        }
        return $isPaid;
    }

    /**
     * 生成活动订单
     *
     * @param string $nodeId 机构号
     * @param int $mId 活动id
     * @param int $mType 活动类型
     * @param int $response 0不需要计入发码费 //产品要求本次默认不显示发码费,等待收费模型确认(所以现在默认值是1) 1 需要发码费
     * @return Ambigous <mixed, boolean, string, unknown>
     */
    public function createOrder($nodeId, $mId, $mType, $response = 1, $assistCode = '') {
        $orderDetail = $this->getOrderDetail($nodeId, $mId, $mType);
        $mInfo = $orderDetail['mInfo'];
        $days = (int) (strtotime($mInfo['end_time']) - strtotime($mInfo['start_time']) + 1) / (24 * 60 * 60);
        
        $varifyRe = '';//优惠券核验结果结果
             
        //如果是欧洲杯活动（特例）没写在配置表里
        if ($mType == CommonConst::BATCH_TYPE_EUROCUP) {
            $orderChooseType = session('matchType' . $mId);
            if ($orderChooseType == 2) {
                $serviceConfigModel = C('EUROCUP_ONE_MATCH_ACTIVITY_TYPE_CONFIG');
                if ($assistCode) {
                    //单场比赛的订单如果有优惠的，需要进行核验，核验成功后把优惠码记到订单
                    $varifyRe = D('Order')->verifyDiscount($assistCode);
                    if ($varifyRe['res_code'] != '0000') {
                        return $varifyRe;
                    }
                }
            } else {
                $serviceConfigModel = C('EUROCUP_ALL_MATCH_ACTIVITY_TYPE_CONFIG');
            }
        } else {
            // 服务费配置
            $serviceConfigJson = D('ActivityPayConfig')->getDefaultPayConfigModelByBatchType($mType);
            $serviceConfigModel = json_decode($serviceConfigJson, true);
        }
        $serviceConfig['model'] = $serviceConfigModel;
        $exDay = $days - $serviceConfigModel['duringTime'];
        if ($exDay < 0) {
            $exDay = 0;
        }
        // 单次收费2980(30天)，超过部分每天100元
        $serviceAmount = $exDay * $serviceConfigModel['exPrice'] + $serviceConfigModel['basicPrice'];
        
        $totalAmount = $serviceAmount;
        $couponDetail = $orderDetail['couponDetail'];
        // 用户选择要发码费的加上发码费
        if ($response) {
            foreach ($couponDetail as $goodsSource) {
                $totalAmount += $goodsSource['num'] *
                     $goodsSource['config']['price'];
            }
        }
        $orderDetail['orderResponse'] = $response; // 记录营帐返回的内容
        $orderDetail['serviceConfig'] = $serviceConfig; // 把生成时的价格配置存一下,方便以后查
        $orderDetail['serviceArr'] = array(
            'num' => 1); // 活动次数.现在就1次
        $orderDetail['serviceEx'] = array(
            'num' => $exDay); // 超过60天的天数
        $orderDetail['couponDetail'] = $couponDetail;
        $orderDetail['available_days'] = $exDay ? $days : $serviceConfigModel['duringTime']; // 记录购买的天数(60天以内按60天算，超过60天按总天数算)
        $orderDetail['act_start_time'] = $mInfo['start_time']; // 记录购买时的活动开始时间
        $orderDetail['act_end_time'] = $mInfo['end_time']; // 记录购买时的活动结束时间
        //如果有核验优惠券的结果，记录优惠券编码到详情
        if (get_val($varifyRe, 'res_code', '') === '0000') {
            $orderDetail['assist_code'] = $assistCode;
        }
        $orderDetailJs = json_encode($orderDetail);
        // 该查询仅仅用于判断是否用户使用过该免费活动
        $orderType = $this->getOrderType($nodeId, $mType, $mId);
        $now = date('Y-m-d H:i:s');
        $data = array(
            'order_number' => date("YmdHis") . mt_rand(100000, 999999), 
            'm_id' => $mId, 
            'batch_type' => $mType, 
            'pay_status' => '0', 
            'add_time' => $now, 
            'node_id' => $nodeId, 
            'amount' => $totalAmount, 
            'order_type' => $orderType, 
            'detail' => $orderDetailJs);
        $re = M('tactivity_order')->where(
            array(
                'node_id' => $nodeId, 
                'batch_type' => $mType, 
                'm_id' => $mId, 
                'pay_status' => array(
                    'neq', 
                    '2')))->find();
        // 如果有时,修改订单
        if ($re) {
            $orderId = $re['id'];
            $reviseData = array(
                'amount' => $data['amount'], 
                'detail' => $data['detail']);
            $result = M('tactivity_order')->where(
                array(
                    'm_id' => $mId))->save($reviseData);
            if (false === $result) {
                log_write('修改订单出错');
                throw_exception('修改订单出错');
            }
        } else { // 增加订单
            $orderId = M('tactivity_order')->add($data);
            if ($data['order_type'] == '2') { // 如果是增加大转盘免费订单送50旺币
                                              // 送旺币,50个
                if ($data['batch_type'] == CommonConst::BATCH_TYPE_WEEL) {//大转盘
                    $giftResult = D('Wheel')->setWb($nodeId, 50,
                    date('Ymd', strtotime('+45days')), C('WB_FOR_WHEEL'));
                } elseif ($data['batch_type'] == CommonConst::BATCH_TYPE_EUROCUP) {//欧洲杯
                    $giftResult = D('Wheel')->setWb($nodeId, 50,
                    date('Ymd', strtotime(C('WB_LIMIT_FOR_EUROP_CUP'))), C('WB_FOR_EUROP_CUP'));
                }
                if (! $giftResult) {
                    log_write('订单号:' . $orderId . ',送50旺币出错');
                    throw_exception('送50旺币出错');
                }
                // 免费订单，更新marketing_info付款状态
                $mResult = M('tmarketing_info')->where(
                    array(
                        'id' => $mId))->save(
                    array(
                        'pay_status' => '1'));
                if (false === $mResult) {
                    log_write('订单号:' . $orderId . ',更新活动表付款状态失败');
                    throw_exception('更新活动表付款状态失败');
                }
            }
            if (! $orderId) {
                log_write('活动id：' . $mId . ',生成订单出错');
                throw_exception('生成订单出错');
            }
        }
        $this->addCouponDetail($orderDetail['selfCreatGoodsIdArr'], 
            CommonConst::COUPON_TYPE_SELFCREATE, $mId, $orderId);
        $this->addCouponDetail($orderDetail['fromBuyGoodsIdArr'], 
            CommonConst::COUPON_TYPE_BUY, $mId, $orderId);
        $this->addCouponDetail($orderDetail['hongBao'], 
            CommonConst::COUPON_TYPE_HB, $mId, $orderId);
        $this->addCouponDetail($orderDetail['wxGoodsIdArr'], 
            CommonConst::COUPON_TYPE_WX_CARD, $mId, $orderId);
        return $orderId;
    }

    private function addCouponDetail($goodsIdArr, $couponType, $mId, $orderId) {
        if (! empty($goodsIdArr)) {
            foreach ($goodsIdArr as $key => $value) {
                M('tactivity_order_coupon_detail')->add(
                    array(
                        'm_id' => $mId, 
                        'type' => $couponType, 
                        'goods_id' => $key, 
                        'num' => $value, 
                        'order_id' => $orderId));
            }
        }
    }

    public function getOrderInfo($orderId, $nodeId) {
        $orderInfo = M('tactivity_order')->where(
            array(
                'id' => $orderId, 
                'node_id' => $nodeId))->find();
        $orderInfo['detail'] = json_decode($orderInfo['detail'], true);
        $actStartTimestamp = strtotime($orderInfo['detail']['act_start_time']);
        $orderInfo['basic_start_time'] = date('Y-m-d', $actStartTimestamp);
        $actEndTimestamp = strtotime($orderInfo['detail']['act_end_time']);
        $duringDay = ($actEndTimestamp - $actStartTimestamp + 1) / (60 * 60 * 24);
        $serviceConfig = $orderInfo['detail']['serviceConfig'];
        $orderInfo['detail']['serviceConfig'] = $serviceConfig['model'];
        if ($duringDay <= $orderInfo['detail']['serviceConfig']['duringTime']) {
            $orderInfo['basic_end_time'] = date('Y-m-d', $actEndTimestamp);
        } else {
            $orderInfo['basic_end_time'] = date('Y-m-d', 
                $actStartTimestamp + $orderInfo['detail']['serviceConfig']['duringTime'] *
                     60 * 60 * 24 - 1);
            $orderInfo['ex_start_time'] = date('Y-m-d', 
                $actStartTimestamp + $orderInfo['detail']['serviceConfig']['duringTime'] *
                     60 * 60 * 24);
            $orderInfo['ex_end_time'] = date('Y-m-d', $actEndTimestamp);
        }
        // 总的服务费
        $orderInfo['serviceAmount'] = ($orderInfo['detail']['serviceConfig']['exPrice'] *
             $orderInfo['detail']['serviceEx']['num']) +
             $orderInfo['detail']['serviceConfig']['basicPrice'];
        // 总的发码费
        $totalSendAmount = 0;
        foreach ($orderInfo['detail']['couponDetail'] as $v) {
            $totalSendAmount += $v['num'] * $v['config']['price'];
        }
        $orderInfo['totalSendAmount'] = $totalSendAmount;
        return $orderInfo;
    }

    /**
     * 获取用户注册的email
     *
     * @param int $nodeId
     * @return Ambigous <>
     */
    public function getRegEmail($nodeId) {
        $nodeInfo = M('tnode_info')->where(
            array(
                'node_id' => $nodeId))->field('contact_eml')->find();
        return $nodeInfo['contact_eml'];
    }

    /**
     * 查看是否已经有了免费的epos
     *
     * @param unknown $nodeId
     * @return boolean
     */
    public function hasFreeEpos($nodeId) {
        // 查询是不是以前有免费的epos//pos_type 2 表示是epos,pos_status 0 表示正常, pay_type 2
        // 表示免费
        $count = M('tpos_info')->where(
            array(
                'pos_type' => '2', 
                'pos_status' => '0', 
                'pay_type' => '2', 
                'node_id' => $nodeId))->count();
        $hasFreeEpos = false;
        if ($count > 0) {
            $hasFreeEpos = true;
        }
        return $hasFreeEpos;
    }

    /**
     * 是否显示发送申请epos的框
     *
     * @param int $nodeId
     * @return boolean
     */
    public function needShowEposMail($nodeId, $batchType) {
        // 当是免费用户,且还没拥有免费epos时显示
        $isFreeUser = D('node')->getNodeVersion($nodeId);
        $hasFreeEpos = $this->hasFreeEpos($nodeId);
        $needShow = false;
        if (in_array($batchType, 
            array(
                CommonConst::BATCH_TYPE_WEEL))) {
            if ($isFreeUser && ! $hasFreeEpos) {
                $needShow = true;
            }
        }
        return $needShow;
    }

    /**
     * 检查会员招募活动是否信息完整
     *
     * @param int $nodeId
     * @param array $mInfo
     * @return array (需要补充完整的url, 提示语)
     */
    protected function checkRecruitPublish($nodeId, $mInfo) {
        $result = array();
        // 判断有没有"奖项设定信息"
        if ($mInfo['is_cj'] == 1) {
            $cjRule = M('tcj_rule')->where(
                array(
                    'batch_id' => $mInfo['id'], 
                    'node_id' => $nodeId))->find();
            $batchInfo = M('tbatch_info')->where(
                array(
                    'status' => '0', 
                    'node_id' => $nodeId, 
                    'm_id' => $mInfo['id']))->find();
            if (empty($cjRule['total_chance']) || ! $batchInfo) {
                $result[] = array(
                    'url' => U('Wmember/Member/setPrize', 
                        array(
                            'm_id' => $mInfo['id'])), 
                    'tips' => '完善奖项配置');
            }
        }
        if (empty($result)) {
            $result = true;
        }
        return $result;
    }

    /**
     * 检查七夕节是否信息完整
     *
     * @param int $nodeId
     * @param array $mInfo
     * @return array (需要补充完整的url, 提示语)
     */
    protected function checkQixiPublish($nodeId, $mInfo) {
        $result = array();
        if ($mInfo['batch_type'] == CommonConst::BATCH_TYPE_QIXI) {
            $cjRule = M('tcj_rule')->where(
                array(
                    'batch_id' => $mInfo['id'], 
                    'node_id' => $nodeId))->find();
            $batchInfo = M('tbatch_info')->where(
                array(
                    'status' => '0', 
                    'node_id' => $nodeId, 
                    'm_id' => $mInfo['id']))->find();
            if (empty($cjRule['total_chance']) || ! $batchInfo) {
                $result[] = array(
                    'url' => U('LabelAdmin/Qixi/setPrize', 
                        array(
                            'm_id' => $mInfo['id'])), 
                    'tips' => '完善奖项配置');
            }
        }
        if (empty($result)) {
            $result = true;
        }
        return $result;
    }

    /**
     * 检查大转盘是否信息完整
     *
     * @param int $nodeId
     * @param array $mInfo
     * @return array (需要补充完整的url, 提示语)
     */
    protected function checkWheelPublish($nodeId, $mInfo) {
        $result = array();
        $cjRule = M('tcj_rule')->where(
            array(
                'batch_id' => $mInfo['id'], 
                'node_id' => $nodeId))->find();
        $batchInfo = M('tbatch_info')->where(
            array(
                'status' => '0', 
                'node_id' => $nodeId, 
                'm_id' => $mInfo['id']))->find();
        if (empty($cjRule['total_chance']) || ! $batchInfo) {
            $result[] = array(
                'url' => U('LabelAdmin/DrawLotteryAdmin/setPrize', 
                    array(
                        'm_id' => $mInfo['id'])), 
                'tips' => '完善奖项配置');
        }
        // 按15940去掉这个逻辑
        // $inLimit = D('DrawLotteryAdmin')->checkPrizeLimit($nodeId,
        // $mInfo['id']);
        // if (!$inLimit) {
        // $result[] = array(
        // 'url' => U('LabelAdmin/DrawLotteryAdmin/setPrize', array(
        // 'm_id' => $mInfo['id']
        // )),
        // 'tips' => '免费活动只能配置100个奖品'
        // );
        // }
        if (empty($result)) {
            $result = true;
        }
        return $result;
    }

    protected function checkRaiseFlagPublish($nodeId, $mInfo) {
        $result = array();
        if ($mInfo['batch_type'] == CommonConst::BATCH_TYPE_RAISEFLAG) {
            $configData = unserialize($mInfo['config_data']);
            $batchInfo = M('tbatch_info')->where(
                array(
                    'status' => '0', 
                    'node_id' => $nodeId, 
                    'm_id' => $mInfo['id']))->find();
            if (! $batchInfo) {
                $result[] = array(
                    'url' => U('LabelAdmin/RaiseFlag/setPrize', 
                        array(
                            'm_id' => $mInfo['id'])), 
                    'tips' => '完善奖项配置');
            }
            if ($configData['prizeChance'] &&
                 empty($configData['prizeChance'][0]) &&
                 empty($configData['prizeChance'][1]) &&
                 empty($configData['prizeChance'][2])) {
                $result[] = array(
                    'url' => U('LabelAdmin/RaiseFlag/setPrize', 
                        array(
                            'm_id' => $mInfo['id'])), 
                    'tips' => '请填写兑换规则');
            }
        }
        if (empty($result)) {
            $result = true;
        }
        return $result;
    }

    protected function checkTwoFestivalPublish($nodeId, $mInfo) {
        $result = array();
        // 判断有没有"奖项设定信息"
        if ($mInfo['is_cj'] == 1) {
            $cjRule = M('tcj_rule')->where(
                array(
                    'batch_id' => $mInfo['id'], 
                    'node_id' => $nodeId))->find();
            $batchInfo = M('tbatch_info')->where(
                array(
                    'status' => '0', 
                    'node_id' => $nodeId, 
                    'm_id' => $mInfo['id']))->find();
            if (empty($cjRule['total_chance']) || ! $batchInfo) {
                $result[] = array(
                    'url' => U('LabelAdmin/TwoFestivalAdmin/setPrize', 
                        array(
                            'm_id' => $mInfo['id'])), 
                    'tips' => '完善奖项配置');
            }
        }
        if (empty($result)) {
            $result = true;
        }
        return $result;
    }

    protected function checkSpringMonkeyPublish($nodeId, $mInfo) {
        $result = array();
        // 判断有没有"奖项设定信息"
        if ($mInfo['is_cj'] == 1) {
            $cjRule = M('tcj_rule')->where(
                array(
                    'batch_id' => $mInfo['id'], 
                    'node_id' => $nodeId))->find();
            $batchInfo = M('tbatch_info')->where(
                array(
                    'status' => '0', 
                    'node_id' => $nodeId, 
                    'm_id' => $mInfo['id']))->find();
            if (empty($cjRule['total_chance']) || ! $batchInfo) {
                $result[] = array(
                    'url' => U('LabelAdmin/SpringMonkey/setPrize', 
                        array(
                            'm_id' => $mInfo['id'])), 
                    'tips' => '完善奖项配置');
            }
        }
        if (empty($result)) {
            $result = true;
        }
        return $result;
    }

    /**
     * 获取当前活动订单
     *
     * @param int $nodeId
     * @param int $mId
     * @return mixed
     */
    public function currentActivityOrder($nodeId, $mId) {
        // order_type为1表示普通订单不是免费的订单
        $result = M('tactivity_order')->where(
            array(
                'node_id' => $nodeId, 
                'm_id' => $mId, 
                'order_type' => '1'))->find();
        return $result;
    }

    public function getOrderDetail($nodeId, $mId, $mType) {
        //如果有营长同步过来的数据用营帐的,没有的话取默认值
        $payConfig = D('ActivityPayConfig')->getSendFee($nodeId);
        $mInfo = M('tmarketing_info')->where(
            array(
                'id' => $mId, 
                'node_id' => $nodeId, 
                'batch_type' => $mType))->field('start_time,end_time')->find();
        if (! $mInfo) {
            throw_exception('非法参数生成订单！');
        }
        $bIdArr = M('tcj_batch')->where(
            array(
                'batch_id' => $mId, 
                'status' => '1'))->getField('b_id');
        $bInfo = M('tbatch_info')->where(
            array(
                'm_id' => $mId, 
                'b_id' => array(
                    'in', 
                    $bIdArr)))->select();
        $selfCreatGoodsIdArr = array(); // 自建的goodsId数组
        $fromBuyGoodsIdArr = array(); // 采购的下标goodsId值为数量
        $hongBao = array(); // 红包
        $wxGoodsIdArr = array(); // 微信卡券
        foreach ($bInfo as $v) {
            if ($v['batch_class'] != CommonConst::GOODS_TYPE_HB && $v['batch_class'] != CommonConst::GOODS_TYPE_JF) {
                if (empty($v['card_id'])) {
                    $goodsInfo = M('tgoods_info')->where(
                        array(
                            'goods_id' => $v['goods_id']))->field('source,goods_type')->find();
                        if ($goodsInfo['source'] == CommonConst::GOODS_SOURCE_SELF_CREATE) {
                                $selfCreatGoodsIdArr[$v['goods_id']] = (isset(
                                    $selfCreatGoodsIdArr[$v['goods_id']]) ? $selfCreatGoodsIdArr[$v['goods_id']] : 0) +
                                    $v['remain_num'];
                        } elseif (in_array($goodsInfo['source'],
                                array(
                                    CommonConst::GOODS_SOURCE_BUY,
                                    CommonConst::GOODS_SOURCE_DISTRIBUTION)) &&
                                $goodsInfo['goods_type'] != CommonConst::GOODS_TYPE_HB) {
                                    $fromBuyGoodsIdArr[$v['goods_id']] = (isset(
                                        $fromBuyGoodsIdArr[$v['goods_id']]) ? $fromBuyGoodsIdArr[$v['goods_id']] : 0) +
                                        $v['remain_num'];
                                }
                                //             elseif ($goodsInfo['goods_type'] == CommonConst::GOODS_TYPE_HB) {
                                //                 $hongBao[$v['goods_id']] = (isset($hongBao[$v['goods_id']]) ? $hongBao[$v['goods_id']] : 0) +
                                //                      $v['remain_num'];
                                //             }
                } else {
                    if ($payConfig['wx'] != 0) {//多米收单用户不收取微信发码费
                        $wxGoodsIdArr[$v['card_id']] = (isset($wxGoodsIdArr[$v['card_id']]) ? $wxGoodsIdArr[$v['card_id']] : 0) +
                        $v['remain_num'];
                    }
                }
            }
                
    }
    //saleFlag本来应该再从node_info表里再查一下，这里可以通过判断$payConfig['wx']值来判断saleflag，
    $saleFlag = ($payConfig['wx'] == 0) ? 1 : 0;
// 组合成订单详情//tactivity_order的detail字段只记录每种类型(1自建卡券2采购卡券3红包4微信卡券)的数量
$couponDetail = array();
$activityOrderCouponDetailModel = M('tactivity_order_coupon_detail');
// 如果是修改的话,detail表会有记录,删除重新插入
$activityOrderCouponDetailModel->where(array(
    'm_id' => $mId))->delete();
if (! empty($selfCreatGoodsIdArr)) {
    $num = 0;
    foreach ($selfCreatGoodsIdArr as $key => $value) {
        $num += $value;
    }
    $couponDetail[CommonConst::COUPON_TYPE_SELFCREATE] = array(
        'num' => $num, 
        'config' => $this->getPayConfigByType('self', $payConfig, $saleFlag));//购买过多米收单的2毛，没有的1块
}
if (! empty($fromBuyGoodsIdArr)) {
    $num = 0;
    foreach ($fromBuyGoodsIdArr as $key => $value) {
        $num += $value;
    }
    $couponDetail[CommonConst::COUPON_TYPE_BUY] = array(
        'num' => $num, 
        'config' => $this->getPayConfigByType('buy', $payConfig, $saleFlag));
}
//红包现在都是空的（因为不收费）
if (! empty($hongBao)) {
    $num = 0;
    foreach ($hongBao as $key => $value) {
        $num += $value;
    }
    $couponDetail[CommonConst::COUPON_TYPE_HB] = array(
        'num' => $num, 
        'config' => $config['4']);
}
if (! empty($wxGoodsIdArr)) {
    $num = 0;
    foreach ($wxGoodsIdArr as $key => $value) {
        $num += $value;
    }
    $couponDetail[CommonConst::COUPON_TYPE_WX_CARD] = array(
        'num' => $num, 
        'config' => $this->getPayConfigByType('wx', $payConfig, $saleFlag));
}
$userInfo = D('UserSess', 'Service')->getUserInfo();
$batchTypeNameArr = C('BATCH_TYPE_NAME');
return array(
    'selfCreatGoodsIdArr' => $selfCreatGoodsIdArr,  // 自建卡券
    'fromBuyGoodsIdArr' => $fromBuyGoodsIdArr,  // 采购卡券
    'hongBao' => $hongBao,  // 红包
    'wxGoodsIdArr' => $wxGoodsIdArr,  // 微信卡券
    'mInfo' => $mInfo,  // 活动信息(开始时间,结束时间)
    'couponDetail' => $couponDetail,  // 对应的卡券详情(数量和配置价格)
    'userInfo' => array(
        'user_id' => $userInfo['user_id'], 
        'name' => $userInfo['user_name']),  // 操作员的信息
    'mTypeName' => $batchTypeNameArr[$mType]); // 活动名称，用作“我的订单”中检索用

}

/**
 * 没有购买打包工具的用户，大转盘和双旦祝福需要购买
 *
 * @param unknown $batchType
 * @return boolean
 */
public function isInFreeUserBuyList($batchType) {
$list = C('PAY_ACTIVITY_TYPE_ID');
return in_array($batchType, $list);
}

/**
 * 大转盘首次有免费订单,其他活动创建的定单都是普通付费订单
 *
 * @param unknown $nodeId
 * @param unknown $mType
 * @param unknown $mId
 * @return string
 */
public function getOrderType($nodeId, $mType, $mId) {
if ($mType != CommonConst::BATCH_TYPE_WEEL && $mType != CommonConst::BATCH_TYPE_EUROCUP) {//如果不是欧洲杯和大转盘直接认定是普通订单
    return CommonConst::ORDER_TYPE_WHEEL_NORMAL;
}
//如果是欧洲杯，时间超过指定时间，判定为普通订单
if ($mType == CommonConst::BATCH_TYPE_EUROCUP && time() > strtotime(C('EURO_CUP_FREE_LIMIT_TIME'))) {
    return CommonConst::ORDER_TYPE_WHEEL_NORMAL;
}
$result = M('tactivity_order')->where(
    array(
        'node_id' => $nodeId, 
        'batch_type' => $mType, 
        'm_id' => array(
            'neq', 
            $mId), 
        'order_type' => CommonConst::ORDER_TYPE_FREE))->find();
return $result ? CommonConst::ORDER_TYPE_WHEEL_NORMAL : CommonConst::ORDER_TYPE_FREE;
}

    /**
     * 获取指定价格的配置(整合数据默认的名字方便配置)
     * @param string $type 'self', 'buy', 'wx'
     * @param array $priceArr array('self' => 10, 'buy' => 22, 'wx' => '33')
     * @return mixed array('price' => xx.xx, 'name' => yy.yy)
     */
    public function getPayConfigByType($type, $priceArr, $saleFlag) {
        $defaultName = M('tactivity_pay_config')->where(array('id' => array('in', array('2', '3', '4', '5'))))->getField('id,name');
        $config = array();
        switch ($type) {
            case 'self':
                $config = array(
                    'price' => number_format($priceArr['self'],2),
                    'name' => $saleFlag ? $defaultName['2'] : $defaultName['5']
                );
                break;
            case 'buy':
                $config = array(
                    'price' => number_format($priceArr['buy'],2),
                    'name' => $defaultName['3']
                );
                break;
            case 'wx':
                $config = array(
                    'price' => number_format($priceArr['wx'],2),
                    'name' => $defaultName['4']
                );
                break;
        }
        return $config;
    }

    /**
     * 获取我的渠道
     * @param int $nodeId
     * @param int $mId
     * @return unknown
     */
    public function getAvailableChannel($nodeId, $mId = '') {
        $join = '';
        if ($mId) {
            $bcResult = M('tbatch_channel')
            ->alias('bc')
            ->field('bc.id as bcid,bc.channel_id')
            ->where(array('node_id' => $nodeId, 'batch_id' => $mId))
            ->select(false);
            $join = 'left join ' . $bcResult . ' b on b.channel_id = c.id';
        }
        
        $map1 = array('type' => CommonConst::CHANNEL_TYPE_MY_CHANNEL, 'sns_type' => '62', '_logic' => 'or');
        $map['_complex'] = $map1;
        $map['node_id'] = $nodeId;
        $result = M('tchannel')
        ->alias('c')
        ->join($join)
        ->where($map)
        ->order('sns_type desc,id desc')
        ->select();
        return $result;
    }
    
    /**
     * 把活动绑定到渠道（在tbatch_channel添加新的记录）
     * @param int $nodeId
     * @param array $channelIdArray
     * @param int $mId
     * @param int $mType
     * @return boolean
     */
    public function bindMyChannel($nodeId, $channelIdArray, $mId, $mType, $bindActivity = false) {
        if ($bindActivity) {
            $defaultActivityChannel = M('tchannel')->where(
                array('node_id' => $nodeId, 'sns_type' => '63', 'status' => '1')
                )->getField('id');
            $channelIdArray[] = $defaultActivityChannel;
        }
        $model = M('tbatch_channel');
        //已经绑定过该活动的渠道
        $channelBinded = $model->alias('bc')->where(
            array(
                'node_id' => $nodeId,
                'channel_id' => array('in', $channelIdArray),
                'batch_id' => $mId
            )
        )->getField('channel_id', true);
        $unbind = array();
        foreach ($channelIdArray as $v) {
            if (!in_array($v, $channelBinded)) {
                $unbind[] = $v;
            }
        }
        //如果是未绑定该活动的渠道添加到batch_channel表
        M()->startTrans();
        foreach ($unbind as $key => $value) {
            $result = $model->add(
                array(
                    'batch_type' => $mType, 
                    'batch_id' => $mId, 
                    'channel_id' => $value, 
                    'add_time' => date('YmdHis'), 
                    'node_id' => $nodeId, 
                    'status' => '1'
                )
            );
            if (false === $result) {
                M()->rollback();
                return false;
            }
        }
        M()->commit();
        return true;
    }
    
    /**
     * @param unknown $nodeId
     * @param string $mId
     * @param bool $includeActivityChannel 是否包含多了互动默认渠道
     * @return unknown
     */
    public function getBindedMyBatchChannelId($nodeId, $mId = '', $includeActivityChannel = true) {
        $join = '';
        if ($mId) {
            $bcResult = M('tbatch_channel')
            ->alias('bc')
            ->field('bc.id as bcid,bc.channel_id')
            ->where(array('node_id' => $nodeId, 'batch_id' => $mId))
            ->select(false);
            $join = 'right join ' . $bcResult . ' as b on b.channel_id = c.id';
        }
        $typeArr = array(CommonConst::CHANNEL_TYPE_MY_CHANNEL);
        $map = array(
                'node_id' => $nodeId, 
                'c.type' => array('in', $typeArr)
        );
        if ($includeActivityChannel) {
            $map1['c.sns_type'] = '63';
            $map1['c.type'] = array('in', $typeArr);
            $map1['_logic'] = 'or';
            $where['_complex'] = $map1;
            $where['node_id'] = $nodeId;
            $map = $where;
        }
        $result = M('tchannel')
        ->alias('c')
        ->field('b.bcid,c.name')
        ->join($join)
        ->where($map)
        ->select();
        return $result;
    }
    
    public function addCodeUrl($selectedBatchChannelInfo) {
        foreach ($selectedBatchChannelInfo as &$v) {
            $v['qr_code_src'] = U('LabelAdmin/ShowCode/index', array('id' => $v['bcid'])); 
            $v['copy_url'] = U('Label/Label/index', array('id' => $v['bcid']), '', '', true);
            $v['download_code_img_url'] = U('LabelAdmin/ShowCode/index', array(
                            'isdown' => '1', 
                            'id' => $v['bcid'])); // 下载的url,batch_channel的id
        } unset($v);
        return $selectedBatchChannelInfo;
    }
    
    /**
     * 判断是否有员工渠道
     * @param int $nodeId 机构号
     * @return number
     */
    public function hasStaffChannel($nodeId) {
        $result = M('tchannel')->where(['type' => '5', 'sns_type' => '51', 'node_id' => $nodeId, 'state' => '1'])->find();
        return $result ? 1 : 0;
    }
    
    /**
     * 欧洲杯的活动是否买过2980
     * @param unknown $nodeId
     * @return bool $hasBuy
     */
    public function hasBuyAllGame($nodeId) {
        $hasBuy = false;
        //查询最后一次的付钱订单
        $order = M('tactivity_order')
        ->where(
            [
                'node_id' => $nodeId, 
                'pay_status' => '1', //已付款
                'order_type' => '1', //普通订单
                'batch_type' => CommonConst::BATCH_TYPE_EUROCUP
            ])
        ->order('pay_time desc')
        ->find();
        if ($order) {
            $detail = json_decode($order['detail'], true);
            if ($detail['serviceConfig']['model'] == C('EUROCUP_ALL_MATCH_ACTIVITY_TYPE_CONFIG')) {
                $hasBuy = true;
            }
        }
        return $hasBuy;
    }
    
    /**
     * 查看是否有未使用的指定辅助码
     * @param int $assisId 辅助码
     * @param string $nodeId 机构号
     * @return mixed
     */
    public function checkBarCodeTrace($assisId, $nodeId) {
        //use_status 0未使用
        $re = M('tbarcode_trace')->where(['assist_number' => $assisId, 'node_id' => $nodeId])->find();
        if (!$re) {
            return ['resp_code' => '-1001', 'msg' => '优惠券无效'];
        }
        if ($re && $re['use_status'] != 0) {
            return ['resp_code' => '-1002', 'msg' => '该优惠券已被使用'];
        }
        return ['resp_code' => '0000', 'msg' => ''];
    }
    
    /**
     * 根据活动类型获取核验活动的配置
     * @param int $batchType
     * @return mixed
     */
    public function getVerifyBarcodeConfigByBatchType($batchType) {
        $config = [];
        $verifyDiscountConfig = C('VERIFY_ACTIVITY_DISCOUNT');
        foreach ($verifyDiscountConfig as $val) {
            if ($val['batch_type'] == $batchType) {
                $config = $val;
                break;
            }
        }
        return $config;
    }
}