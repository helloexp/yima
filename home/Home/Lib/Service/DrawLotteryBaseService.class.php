<?php

/**
 * 抽奖  .......     Based On Redis
 *
 * @author Jeff Liu<liuwy@imageco.com.cn> 2016-03-15
 */
import('@.Service.BaseService') or die('@.Service.BaseService 导入包失败');

class DrawLotteryBaseService extends BaseService
{
    private $inited = false;
    // 接口参数
    public $node_id;// 机构号
    public $batch_id; // 抽奖id
    public $total_rate; //总中奖率
    public $day_win_times;// 单号码每日中奖数
    public $total_win_times;// 单号码总中奖数
    public $day_part;// 单号码每日参与数
    public $total_part;// 单号码总参与数
    public $phone_no;// 抽奖手机号
    public $label_id;    // 标签id
    public $channel_id;    // 渠道id
    public $batch_type;    // 营销活动来源
    public $ip;    // 抽奖ip
    public $cj_rule_id;    // 抽奖规则id
    public $ticket_seq;    // 抽奖小票流水号
    public $ticket_limit_num;    // 单小票允许抽奖数
    public $store_id;    // 门店编号
    public $wx_wap_ranking_id;    // 圣诞雪球抽奖积分记录id
    public $wx_cjcate_id;    // 圣诞雪球抽奖cate_id;
    public $wx_cjbatch_id;    // 指定奖品
    public $open_id;    // 平安非标
    public $wx_nick;    // 微信昵称
    public $wx_open_id;    // 微信卡券open_id
    public $wx_card_info = null;    // 微信卡券JSAPI信息
    public $join_mode;    // 活动的参与方式 0-手机号 1-微信
    public $send_mobile;    // 发奖号码
    public $send_mode;    // 发奖号码类型
    public $request_id;    // 向发码接口请求的流水号
    public $cj_trace_id;    // 抽奖流水保存的ID
    public $pay_token;    // 付满送token
    public $save_request_id;    // 向发码接口请求的流水号
    public $bonus_use_detail_id;    // tbonus_use_detail.id
    public $integral_get_id;    // tintegal_get_detail.id
    public $pos_id;
    public $pos_serial;
    public $b_id;
    public $g_id;
    public $memberInfo = null;
    public $memberId = null;

    private $currentDateStr = 0;//当前时间
    private $currentDate24HLeftSeconds = 0;//当日距离24点的剩余秒数

    public $fbYhbFlag = false;    // 异步改造
    public $full_id;    // 标签列表
    public $participation_id;
    private $pay_give_order_info; // 付满送订单信息

    const ERROR_RESPONSE_ID = '9999';
    const AWARD_OPENED = 1; //任务开启了。
    const WECHAT_JOIN = '1';//微信方式参与
    const MEMBER_JOIN_RESTRICTION = '1'; //限制会员

    /**
     * @var MemberInstallModel
     */
    public $MemberInstallModel;

    /**
     * @var MemberBehaviorModel
     */
    public $MemberBehaviorModel;

    /**
     * @var RemoteRequestService
     */
    public $RemoteRequestService;

    /**
     * @var IntegralPointTraceModel
     */
    public $IntegralPointTraceModel;

    /**
     * @var BatchChannelRedisModel
     */
    private $BatchChannelRedisModel;

    /**
     * @var NodeInfoRedisModel
     */
    private $NodeInfoRedisModel;

    /**
     * @var DrawLotteryWhiteBlackListRedisModel
     */
    private $DrawLotteryWhiteBlackListRedisModel;

    /**
     * @var WxUserModel
     */
    public $WxUserModel;

    /**
     * @var CjTraceModel
     */
    public $CjTraceModel;

    private $drawLotteryFinalData = [];

    private $drawLotteryMainKey = '';

    /**
     * @var RedisHelper
     */
    private $RedisHelper;

    private $otherParam = [];

    public function __construct()
    {
        import('@.Vendor.RedisHelper');
        $this->RedisHelper = RedisHelper::getInstance();
    }

    /**
     * @param bool $verify
     * @param bool $initOnce
     *
     * @return bool
     */
    public function _initialize($verify = true, $initOnce = false)
    {
        if ($initOnce && $this->inited) {//只初始化一次且已经初始化了
            return true;
        }

        $this->drawLotteryFinalData = $this->getDrawLotteryFinalData($this->batch_id);
        if (empty($this->drawLotteryFinalData)) {
            return $this->returnError('数据获取失败!');
        }
        if ($verify) {
            $verifyInitDataStatus = $this->verifyInitData();
            if ($verifyInitDataStatus !== true) {
                return $verifyInitDataStatus;
            }
        }

        $this->currentDateStr            = date('Ymd');
        $this->currentDate24HLeftSeconds = strtotime('+1day 00:00:00') - time();

        $this->inited = true;

        return true;
    }

    /**
     * 抽奖入口
     *
     * @param int   $labelId
     * @param array $otherParam ['phone_no', 'wx_open_id',[]] 等其他一些自定义参数
     *
     * @return mixed
     */
    public function drawLottery($labelId, $otherParam = array())
    {
        $this->BatchChannelRedisModel = D('BatchChannelRedis');
        $labelInfo                    = $this->BatchChannelRedisModel->getByPkWithoutKey($labelId);
        if ($labelInfo) {
            $otherParam['channel_id'] = get_val($labelInfo, 'channel_id');
            $otherParam['batch_type'] = get_val($labelInfo, 'batch_type');

            $this->batch_id   = get_val($labelInfo, 'batch_id');
            $this->label_id   = $labelId;
            $this->otherParam = $otherParam;

            $initStatus = $this->_initialize();
            if ($initStatus !== true) { //初始化数据的时候出现了错误
                return $initStatus;
            }
            return $this->multipleAward();
        } else {
            return $this->returnErrorByErrno(9901, ['id' => $labelId]);
        }
    }

    public function initializeOnce($labelId)
    {
        if ($this->inited) {
            return true;
        }
        $this->BatchChannelRedisModel = D('BatchChannelRedis');
        $labelInfo                    = $this->BatchChannelRedisModel->getByPkWithoutKey($labelId);
        if ($labelInfo) {
            $this->batch_id = get_val($labelInfo, 'batch_id');
            $this->label_id = $labelId;
            $initStatus     = $this->_initialize(false, true);
            return $initStatus;
        } else {
            return $this->returnErrorByErrno(9901, ['id' => $labelId]);
        }
    }

    /**
     * todo 这个可以再修改好之后 直接存储到redis里面。 。。。
     *
     * @param $labelId
     *
     * @return array|null
     */
    public function getCjInfo($labelId)
    {
        $ret = $this->initializeOnce($labelId);
        if ($ret === true) {
            $cjRule = $this->getCjRule();
            // 抽奖文字配置
            $cjText = $cjRule['cj_button_text'];
            // 奖品
            $prizeList = $this->getDynamicAwardList();
            if (empty($prizeList)) {
                return ['code' => '1', 'msg' => '未设置奖品'];
            }
            $finalPrizeList  = [];
            $staticAwardList = $this->getStaticAwardList();
            foreach ($staticAwardList as $index => $item) {
                $dynamicAwardId   = $item['b_id'];
                $dynamicAwardInfo = $prizeList[$dynamicAwardId];
                $finalPrizeList[] = [
                        'cj_cate_id' => $item['cj_cate_id'],
                        'batch_name' => $dynamicAwardInfo['batch_name'],
                        'batch_img'  => get_upload_url($dynamicAwardInfo['batch_img']),
                ];
            }
            // 分类
            $cjCateList  = [];
            $finalCjRule = [];
            if ($cjRule) {
                $finalCjRule['id']               = get_val($cjRule, 'id');
                $finalCjRule['total_chance']     = get_val($cjRule, 'total_chance');
                $finalCjRule['cj_button_text']   = get_val($cjRule, 'cj_button_text');
                $finalCjRule['cj_check_flag']    = get_val($cjRule, 'cj_check_flag');
                $finalCjRule['phone_total_part'] = get_val($cjRule, 'phone_total_part');
                $finalCjRule['phone_day_part']   = get_val($cjRule, 'phone_day_part');
                $cjCateList                      = $this->getCjCateList();
            }
            // 处理页面奖项奖项
            $cjCateId        = [];
            $cjCateName      = '';
            $finalCjCateList = [];
            foreach ($cjCateList as $v) {
                $cjCateId[] = $v['id'];
                $cjCateName .= '"' . $v['name'] . '",';
                $finalCjCateList[] = ['id'   => $v['id'],'name' => $v['name'],];
            }

            $return = array(
                    'code' => '0000',
                    'msg'  => 'success',
                    'data' => [
                            'cj_cate_id'   => implode(',', $cjCateId),
                            'cj_cate_name' => trim($cjCateName, ','),
                            'total_chance' => $cjRule['total_chance'],
                            'cj_text'      => $cjText,
                            'cj_rule'      => $finalCjRule,
                            'prizeList'    => $finalPrizeList,
                            'cjCateList'   => $finalCjCateList
                    ]
            );
            return $return;
        } else {
            return null;
        }
    }

    /**
     * @author Jeff.Liu<liuwy@imageco.com.cn>
     *
     * @param $mId
     *
     * @return int|mixed|null
     */
    public function setMarketingInfo($mId)
    {
        $drawLotteryMainKey = $this->generateMainKey($mId);
        $marketingInfo      = $this->getFormatedMarketingInfoFormDb($mId);
        return $this->RedisHelper->hSet($drawLotteryMainKey, 'marketingInfo', $marketingInfo);
    }

    public function modifyCjRule($mId)
    {
        $this->drawLotteryMainKey = $this->generateMainKey($mId);
        if ($mId) {
            $staticPersonDayWinTimesKey                = $this->getStaticPersonDayWinTimesKey($mId);
            $staticPersonTotalWinTimesKey              = $this->getStaticPersonTotalWinTimesKey($mId);
            $staticPersonDayPartTimesKey               = $this->getStaticPersonDayPartTimesKey($mId);
            $staticPersonTotalPartTimesKey             = $this->getStaticPersonTotalPartTimesKey($mId);
            $cjRule                                    = M('tcj_rule')->where(['batch_id' => $mId])->find();
            $finalData['cjRule']                       = $cjRule;
            $finalData[$staticPersonDayWinTimesKey]    = get_val($cjRule, 'phone_day_count');    //个人日中奖次数
            $finalData[$staticPersonTotalWinTimesKey]  = get_val($cjRule, 'phone_total_count'); //个人总中奖次数
            $finalData[$staticPersonDayPartTimesKey]   = get_val($cjRule, 'phone_day_part');     //个人日参与次数
            $finalData[$staticPersonTotalPartTimesKey] = get_val($cjRule, 'phone_total_part'); //个人总参与次数
            return $this->RedisHelper->hMset($this->drawLotteryMainKey, $finalData);
        }
        return null;
    }

    public function modifyCjCateList($mId)
    {
        if ($mId) {
            $drawLotteryMainKey = $this->generateMainKey($mId);
            $cjCateList         = $this->getFormatedcjCateListFromDb($mId);
            return $this->RedisHelper->hSet($drawLotteryMainKey, 'cjCateList', $cjCateList);
        }
        return null;
    }

    public function modifyMarkertingInfo($mId)
    {
        if ($mId) {
            $marketingInfo            = $this->getFormatedMarketingInfoFormDb($mId);
            $this->drawLotteryMainKey = $this->generateMainKey($mId);
            return $this->RedisHelper->hSet($this->drawLotteryMainKey, 'marketingInfo', $marketingInfo);
        }
        return null;
    }

    public function modifySinglePrizeWithOtherInfo($mId, $batchInfoId, $cjBatchId)
    {
        $this->modifyMarkertingInfo($mId);
        $this->modifyCjCateList($mId);
        $this->modifyCjRule($mId);
        $this->modifySinglePrize($mId, $batchInfoId, $cjBatchId);
    }

    /**
     * @param $mId
     * @param $batchInfoId
     * @param $cjBatchId
     *
     * @return mixed
     */
    public function modifySinglePrize($mId, $batchInfoId, $cjBatchId)
    {
        if ($mId && $batchInfoId && $cjBatchId) {
            $key        = 'generateDrawLotteryFinalData:' . $mId;
            $identifier = $this->RedisHelper->acquireLockWithTimeoutByLua($key, 10, 20);//添加锁，防并发
            if ($identifier) {
                $this->drawLotteryMainKey = $this->generateMainKey($mId);
                $bonusDetailList          = [];
                $batchInfo                = M('tbatch_info')->where(['id' => $batchInfoId])->find();
                $cjBatchInfo              = M('tcj_batch')->where(['id' => $cjBatchId])->find();
                $finalGoodsInfoList       = $this->RedisHelper->hGet($this->drawLotteryMainKey, 'goodsInfoList');
                $finalBatchInfoList       = $this->RedisHelper->hGet($this->drawLotteryMainKey, 'dynamicAwardList');
                $finalCjBatchList         = $this->RedisHelper->hGet($this->drawLotteryMainKey, 'staticAwardList');
                if (empty($finalCjBatchList)) {
                    $finalCjBatchList = [];
                }
                if (empty($finalBatchInfoList)) {
                    $finalBatchInfoList = [];
                }
                if (empty($finalGoodsInfoList)) {
                    $finalGoodsInfoList = [];
                }

                if (is_array($batchInfo)) {
                    $finalBatchInfoList[$batchInfoId] = $batchInfo;
                    if (isset($batchInfo['goods_id']) && $batchInfo['goods_id']) {
                        $goodsInfo = M('tgoods_info t1')->field('t1.*,t2.id as bonus_detail_id')->join(
                                'tbonus_detail t2 ON t1.bonus_id=t2.bonus_id'
                        )->where('t1.goods_id ="' . $batchInfo['goods_id'] . '"')->find();
                        if (isset($goodsInfo['bonus_detail_id']) && $goodsInfo['bonus_detail_id']) {
                            $bonusDetailId = $goodsInfo['bonus_detail_id'];
                            if ($bonusDetailId) {
                                $tmpBonusDetailList = M('tbonus_detail')->where(['id' => $bonusDetailId])->find();
                                foreach ($tmpBonusDetailList as $index => $item) {
                                    $bonusDetailList[$item['bonus_id']] = $item;
                                }
                            }
                        }
                    }
                }

                $status = get_val($cjBatchInfo, 'status');
                if ($status == self::AWARD_OPENED) { //只保存开启的奖品
                    $finalCjBatchList[$cjBatchId] = $cjBatchInfo;
                }

                $data['static:day_win_times:' . $cjBatchId]   = get_val($cjBatchInfo, 'day_count');   //奖品日中奖次数（静态）
                $data['static:total_win_times:' . $cjBatchId] = get_val($cjBatchInfo, 'total_count'); //奖品总中奖次数（静态）
                $data['static:storage_num:' . $cjBatchId]     = get_val($cjBatchInfo, 'total_count');  //奖品库存数（静态）
                //PS: 进入编辑页面页面的时候 不要忘记调用$this->updateDbRemainNumFromRedis($mId)
                $remainStorage                             = get_val($batchInfo, 'remain_num');
                $data['dynamic:storage_num:' . $cjBatchId] = $remainStorage;  //奖品库存数（动态）

                $goods_id                      = get_val($goodsInfo, 'goods_id');
                $finalGoodsInfoList[$goods_id] = $goodsInfo;

                $data['staticAwardList']  = $finalCjBatchList;
                $data['dynamicAwardList'] = $finalBatchInfoList;
                $data['goodsInfoList']    = $finalGoodsInfoList;
                $data['bonusDetailList']  = $bonusDetailList;
                $return                   = $this->RedisHelper->hMset($this->drawLotteryMainKey, $data);

                $this->RedisHelper->releaseLockByLua('generateDrawLotteryFinalData:' . $mId, $identifier);//释放锁
                return $return;
            }

        }
        return null;
    }

    /**
     * 检验是否符合抽奖条件
     *
     * @return bool
     */
    public function verifyDrawLotteryBlockStatus()
    {
        if (empty($this->NodeInfoRedisModel)) {
            $this->NodeInfoRedisModel = D('NodeInfoRedis');
        }
        // 黑白名单 start
        $nodeInfo               = $this->NodeInfoRedisModel->getByPkWithoutKey($this->node_id);
        $drawLotteryBlockStatus = get_val($nodeInfo, 'cj_white_black');
        if ($drawLotteryBlockStatus) {
            if (empty($this->DrawLotteryWhiteBlackListRedisModel)) {
                $this->DrawLotteryWhiteBlackListRedisModel = D('DrawLotteryWhiteBlackListRedis');
            }
            $cjWhiteBlackList = $this->DrawLotteryWhiteBlackListRedisModel->getBySk($this->node_id, $this->phone_no);
            if ($drawLotteryBlockStatus == '2') {
                if ($cjWhiteBlackList) {
                    return $this->returnErrorByErrno(9911);
                }
            } elseif ($drawLotteryBlockStatus == '1') {
                if (!$cjWhiteBlackList) {
                    return $this->returnErrorByErrno(9911);
                }
            }
        }
        // 黑白名单 end

        return true;
    }

    /**
     * @return mixed
     */
    public function getCjRule()
    {
        return $this->drawLotteryFinalData['cjRule'];
    }

    private function initProperty()
    {
        $marketingInfo           = $this->getMarketingInfo();
        $cjRule                  = $this->getCjRule();
        $this->node_id           = get_val($marketingInfo, 'node_id'); // 机构号
        $this->total_rate        = get_val($cjRule, 'total_chance'); // 总中奖率
        $this->day_win_times     = get_val($cjRule, 'phone_day_count', 1);// 单号码每日中奖数
        $this->total_win_times   = get_val($cjRule, 'phone_total_count', 0);// 单号码总中奖数
        $this->day_part          = get_val($cjRule, 'phone_day_part', 0);// 单号码每日参与数
        $this->total_part        = get_val($cjRule, 'phone_total_part', 0);// 单号码总参与数
        $this->cj_rule_id        = get_val($cjRule, 'id');// 抽奖规则id
        $this->phone_no          = get_val($this->otherParam, 'phone_no'); // 抽奖手机号
        $this->channel_id        = get_val($this->otherParam, 'channel_id'); // 渠道id
        $this->batch_type        = get_val($this->otherParam, 'batch_type'); // 营销活动类型
        $this->ip                = get_val($this->otherParam, 'ip', get_client_ip()); // 抽奖ip
        $this->ticket_seq        = get_val($this->otherParam, 'ticket_seq'); // 抽奖小票流水号
        $this->ticket_limit_num  = get_val($this->otherParam, 'ticket_limit_num'); // 单小票允许抽奖数
        $this->wx_wap_ranking_id = get_val($this->otherParam, 'wx_wap_ranking_id'); // 圣诞雪球抽奖积分记录id
        $this->wx_cjcate_id      = get_val($this->otherParam, 'wx_cjcate_id'); // 圣诞雪球抽奖cate_id;
        $this->wx_cjbatch_id     = get_val($this->otherParam, 'wx_cjbatch_id'); // 圣诞雪球抽奖cate_id;
        $this->open_id           = get_val($this->otherParam, 'open_id'); // 平安非标
        $this->wx_open_id        = get_val($this->otherParam, 'wx_open_id'); // 微信卡券open_id
        $this->wx_nick           = get_val($this->otherParam, 'wx_nick'); // 微信昵称
        $this->pay_token         = get_val($this->otherParam, 'pay_token'); // 付满送token
        $this->pos_id            = get_val($this->otherParam, 'pos_id'); // 终端号
        $this->pos_serial        = get_val($this->otherParam, 'pos_serial'); // 机身号
        if ($this->open_id == null) { // 平安非标，如果open_id为空 , 取wx_open_id 进行覆盖
            $this->open_id = $this->wx_open_id;
        }
        $this->fbYhbFlag        = $this->node_id == C('Yhb.node_id');
        $this->full_id          = get_val($this->otherParam, 'full_id'); // 标签列表
        $this->participation_id = get_val($this->otherParam, 'participation_id');
        // 获取请求流水号
        $this->save_request_id = genreateSerialNumber('award_send_seq');
    }

    /**
     * 验证正确性
     */
    private function verifyInitData()
    {
        $checkStatus = $this->check();
        if ($checkStatus !== true) {
            return $checkStatus;
        }

        $verifyMarketingInfoStatus = $this->verifyMarketingInfo();
        if ($verifyMarketingInfoStatus !== true) {
            return $verifyMarketingInfoStatus;
        }

        $verifyCjRuleStatus = $this->verifyCjRule();
        if ($verifyCjRuleStatus !== true) {
            return $verifyCjRuleStatus;
        }
        $verifyDrawLotteryBlockStatus = $this->verifyDrawLotteryBlockStatus();
        if ($verifyDrawLotteryBlockStatus !== true) {
            return $verifyDrawLotteryBlockStatus;
        }

        $staticAwardList = $this->getStaticAwardList();

        if (!$staticAwardList) {
            $ret = $this->initStaticAwardList($this->batch_id);
            if (!$ret) {
                return $this->returnErrorByErrno(9909, ['cj_rule_id' => $this->cj_rule_id]);
            }
        }
        foreach ($staticAwardList as $staticAwardInfo) {
            $bId              = isset($staticAwardInfo['b_id']) ? $staticAwardInfo['b_id'] : null;
            $dynamicAwardInfo = $this->getDynamicAwardInfo($bId);
            if (empty($dynamicAwardInfo)) {
                $ret = $this->initSingleDynamicAwardInfo($bId);
                if (!$ret) {
                    return $this->returnErrorByErrno(9910, ['cj_rule_id' => $this->cj_rule_id]);
                }
            } else {
                $goods_info = $this->getGoodsInfo($dynamicAwardInfo['goods_id']);
                if (!$goods_info) {
                    $this->log("没有状态正常的电子券" . M()->_sql());
                    $ret = $this->initSingleGoodsInfo($dynamicAwardInfo['goods_id']);
                    if (!$ret) {
                        return $this->returnErrorByErrno(1060, $dynamicAwardInfo);
                    }
                } else {
                    if (isset($goods_info['bonus_id']) && $goods_info['bonus_id']) {
                        $bonus_detail = $this->getBonusDetail($goods_info['bonus_id']);
                        if (!$bonus_detail) {
                            $ret = $this->initSingleBonusInfo($goods_info['bonus_id']);
                            if (!$ret) {
                                return $this->returnErrorByErrno(1061, $goods_info);
                            }
                        }
                    }
                }
            }
        }
        // 查找marketing_info 信息
        $marketingInfo = $this->getMarketingInfo();
        if (!$marketingInfo) {
            return $this->returnErrorByErrno(9912, ['cj_rule_id' => $this->cj_rule_id]);//原来是7001
        }

        return true;
    }

    protected function initStaticAwardList($mId)
    {
        $cjBatchList = M('tcj_batch')->where(['batch_id' => $mId])->select();
        if ($cjBatchList) {
            $finalCjBatchList = [];
            foreach ($cjBatchList as $index => $item) {
                $id = get_val($item, 'id');
                if ($id) {
                    $finalCjBatchList[$id] = $item;
                }
            }
            $this->RedisHelper->hSet($this->drawLotteryMainKey, 'staticAwardList', $finalCjBatchList);
            return true;
        } else {
            return false;
        }
    }


    /**
     * @param $batchInfoId
     *
     * @return bool
     */
    protected function initSingleDynamicAwardInfo($batchInfoId)
    {
        $batchInfo = M('tbatch_info')->where(['id' => $batchInfoId])->find();
        if ($batchInfo) {
            $batchInfoList = $this->getDynamicAwardList();
            if (empty($batchInfoList)) {
                $batchInfoList = [];
            }
            $batchInfoList[$batchInfoId] = $batchInfo;
            $this->RedisHelper->hSet($this->drawLotteryMainKey, 'dynamicAwardList', $batchInfoList);
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $goodsId
     *
     * @return bool
     */
    protected function initSingleGoodsInfo($goodsId)
    {
        $goodsInfo = M('tgoods_info')->where(['goods_id' => $goodsId])->find();
        if ($goodsInfo) {
            $goodsInfoList = $this->getGoodsInfoList();
            if (empty($goodsInfoList)) {
                $goodsInfoList = [];
            }
            $goodsInfoList[$goodsId] = $goodsInfo;
            $this->RedisHelper->hSet($this->drawLotteryMainKey, 'goodsInfoList', $goodsInfoList);
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $bonusId
     *
     * @return bool
     */
    protected function initSingleBonusInfo($bonusId)
    {
        $detailInfo = M('tbonus_detail')->where(['id' => $bonusId])->find();
        if ($detailInfo) {
            $bonusDetailList = $this->getBonusDetailList();
            if (empty($bonusDetailList)) {
                $bonusDetailList = [];
            }
            $bonusDetailList[$bonusId] = $detailInfo;
            $this->RedisHelper->hSet($this->drawLotteryMainKey, 'bonusDetailList', $bonusDetailList);
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return bool|string
     */
    protected function verifyCjRule()
    {
        $cjRule = $this->getCjRule();
        if (empty($cjRule['jp_set_type'])) {
            return $this->returnErrorByErrno(9908);
        }
        return true;
    }

    /**
     * 校验活动信息是否有效
     * @author Jeff.Liu<liuwy@imageco.com.cn>
     * @return bool|string
     */
    protected function verifyMarketingInfo()
    {
        $marketingInfo = $this->getMarketingInfo();
        if (empty($marketingInfo)) {
            return $this->returnErrorByErrno(9901, ['id' => $this->batch_id]);
        }

        if ($marketingInfo['join_mode']) { // 微信号参与
            $otherarr = $this->otherParam;
            if (empty($otherarr['wx_open_id'])) {
                return $this->returnErrorByErrno(9902);
            }
            if (empty($otherarr['wx_nick'])) {
                return $this->returnErrorByErrno(9903);
            }
        } else { // 手机号参与 校验手机号
            if (empty($this->phone_no) || !is_numeric($this->phone_no) || strlen($this->phone_no) != '11') {
                return $this->returnErrorByErrno(9904);
            }
        }

        $currentTime = date('YmdHis');
        $startTime   = $marketingInfo['start_time'];
        $endTime     = $marketingInfo['end_time'];
        if ($currentTime < $startTime || $currentTime > $endTime) {
            return $this->returnErrorByErrno(9905);
        }

        if ($marketingInfo['status'] != '1') {
            $res['code'] = -1027;
            return $this->returnErrorByErrno(9906);
        }
        if ($marketingInfo['is_cj'] == '0') {
            return $this->returnErrorByErrno(9907);
        }

        return true;
    }


    /**
     * @param        $msg
     * @param string $level
     */
    protected function log($msg, $level = Log::INFO)
    {
        log_write($msg, $level);
    }

    /**
     *
     * 获得抽奖相关数据(如果数据不存在，尝试初始化相关数据到redis)
     *
     * @param $mId
     *
     * @return array
     */
    public function getDrawLotteryFinalData($mId)
    {
        $data = [
                'cjRule',//抽奖规则
                'staticAwardList',//奖品信息 marketingInfo和tcj_batch 为1：n的关系(包含奖品相关信息，初始库存等信息)
                'dynamicAwardList',//奖品信息 marketingInfo和tbatch_info 为1：n的关系(包含奖品相关信息，动态库存等信息)
                'goodsInfoList',//奖品涉及到的商品信息
                'bonusDetailList',//奖品 - 红包相关信息
                'marketingInfo',//抽奖信息
                'cjCateList',//抽奖分类
        ];

        $this->drawLotteryMainKey = $this->generateMainKey($mId);
        $return                   = $this->RedisHelper->hMGet($this->drawLotteryMainKey, $data);
        if (empty($return) || ($return['marketingInfo'] === false)) {
            $return = $this->generateDrawLotteryFinalDataWithStorage($mId);
        }

        $this->initProperty();

        return $return;
    }

    /**
     * 构建存储redis的key
     *
     * @param $mId
     *
     * @return string
     */
    private function generateMainKey($mId)
    {
        return 'drawlottery:' . $mId;
    }

    /**
     * 获得当前抽奖用户标识 ID
     * @return null
     */
    public function getPersonId()
    {
        $personId = $this->memberId;
        if (empty($personId)) {
            $personId = $this->phone_no;
        }
        return $personId;
    }

    /**
     * 构建抽奖数据（包含 动态库存）
     *
     * @param $mId
     *
     * @return array|null
     */
    public function generateDrawLotteryFinalDataWithStorage($mId)
    {
        return $this->generateDrawLotteryFinalData($mId, true);
    }

    /**
     * 构建抽奖数据（不包含 动态库存）
     *
     * @param $mId
     *
     * @return array|null
     */
    public function generateDrawLotteryFinalDataWithoutStorage($mId)
    {
        return $this->generateDrawLotteryFinalData($mId, false);
    }

    /**
     * 构建抽奖数据
     * 奖品日中奖次数 static:day_win_times:$bid     -- 对应 tcj_batch的 day_count
     * 奖品总中奖次数 static:total_win_times:$bid   -- 对应 tcj_batch的 total_count
     * 奖品库存数     static:storage_num:$bid       -- 对应 tcj_batch的 award_rate
     * 个人日中奖次数 static:person_day_win_times:$mId     -- 对应 cj_rule的 phone_day_count
     * 个人总中奖次数 static:person_total_win_times:$mId   -- 对应 cj_rule的 phone_total_count
     * 个人日参与次数 static:person_day_part_times:$mId    -- 对应 cj_rule的 phone_day_part
     * 个人总参与次数 static:person_total_part_times:$mId  -- 对应 cj_rule的 phone_total_part
     *
     * @param int  $mId
     * @param bool $withStorage
     *
     * @return array|null
     */
    protected function generateDrawLotteryFinalData($mId, $withStorage)
    {
        //添加锁，防并发
        $identifier = $this->RedisHelper->acquireLockWithTimeoutByLua('generateDrawLotteryFinalData:' . $mId, 10, 20);
        if ($identifier) {//获取锁成功
            $this->drawLotteryMainKey = $this->generateMainKey($mId);
            $data                     = [
                    'cjRule'           => [],//抽奖规则
                    'staticAwardList'  => [],//奖品信息 marketingInfo和tcj_batch 为1：n的关系(包含奖品相关信息，初始库存等信息)
                    'dynamicAwardList' => [],//奖品信息 marketingInfo和tbatch_info 为1：n的关系(包含奖品相关信息，动态库存等信息)
                    'goodsInfoList'    => [],//奖品涉及到的商品信息
                    'bonusDetailList'  => [],//奖品 - 红包相关信息
                    'marketingInfo'    => [],//抽奖信息
                    'cjCateList'       => [],//抽奖分类
            ];

            $bonusDetailList   = [];
            $bonusDetailIdList = [];
            if ($mId) {
                $marketingInfo      = $this->getFormatedMarketingInfoFormDb($mId);
                $cjCateList         = $this->getFormatedcjCateListFromDb($mId);
                $cjBatchList        = M('tcj_batch')->where(['batch_id' => $mId])->select();
                $cjRule             = M('tcj_rule')->where(['batch_id' => $mId])->find();
                $batchInfoList      = M('tbatch_info')->where(['m_id' => $mId])->select();
                $goodsInfoList      = [];
                $finalBatchInfoList = [];
                if (is_array($batchInfoList)) {
                    $goodsIdList = [];
                    foreach ($batchInfoList as $index => $item) {
                        $goodsId = isset($item['goods_id']) ? $item['goods_id'] : -1;
                        if ($goodsId != -1) {
                            $goodsIdList[] = $goodsId;
                        }

                        $id                      = get_val($item, 'id');
                        $finalBatchInfoList[$id] = $item;
                        unset($batchInfoList[$index], $item);
                    }

                    if ($goodsIdList) {
                        $goodsInfoList = M('tgoods_info t1')->field('t1.*,t2.id as bonus_detail_id')->join(
                                'tbonus_detail t2 ON t1.bonus_id=t2.bonus_id'
                        )->where('t1.goods_id IN("' . implode('","', $goodsIdList) . '")')->select();
                        foreach ($goodsInfoList as $index => $goodsInfo) {
                            if (isset($goodsInfo['bonus_detail_id']) && $goodsInfo['bonus_detail_id']) {
                                $bonusDetailIdList[] = $goodsInfo['bonus_detail_id'];
                            }
                        }

                        if ($bonusDetailIdList) {
                            $where              = 'id IN(' . implode(',', $bonusDetailIdList) . ')';
                            $tmpBonusDetailList = M('tbonus_detail')->where($where)->select();
                            foreach ($tmpBonusDetailList as $index => $item) {
                                $bonusDetailList[$item['bonus_id']] = $item;
                            }
                        }
                    }
                }

                $finalCjBatchList = [];
                foreach ($cjBatchList as $i => $item) {
                    $id     = get_val($item, 'id');
                    $status = get_val($item, 'status');
                    if ($status == self::AWARD_OPENED) { //只保存开启的奖品
                        $finalCjBatchList[$id] = $item;
                    }
                    $bId              = get_val($item, 'b_id');
                    $currentBatchInfo = get_val($finalBatchInfoList, $bId);

                    $data['static:day_win_times:' . $id]   = get_val($item, 'day_count');   //奖品日中奖次数（静态）
                    $data['static:total_win_times:' . $id] = get_val($item, 'total_count'); //奖品总中奖次数（静态）
                    if ($withStorage) {
                        $data['static:storage_num:' . $id] = get_val($item, 'total_count');  //奖品库存数（静态）
                        //PS: 进入编辑页面页面的时候 不要忘记调用$this->updateDbRemainNumFromRedis($mId)
                        $remainStorage                      = get_val($currentBatchInfo, 'remain_num');
                        $data['dynamic:storage_num:' . $id] = $remainStorage;  //奖品库存数（动态）
                    }
                    unset($cjBatchList[$i], $item, $i);
                }

                $finalGoodsInfoList = [];
                foreach ($goodsInfoList as $i => $item) {
                    $goods_id                      = get_val($item, 'goods_id');
                    $finalGoodsInfoList[$goods_id] = $item;
                    unset($goodsInfoList[$i], $item, $i);
                }

                $data['marketingInfo']                          = $marketingInfo;
                $data['cjCateList']                             = $cjCateList;
                $data['staticAwardList']                        = $finalCjBatchList;
                $data['cjRule']                                 = $cjRule;
                $data['dynamicAwardList']                       = $finalBatchInfoList;
                $data['goodsInfoList']                          = $finalGoodsInfoList;
                $data['bonusDetailList']                        = $bonusDetailList;
                $data['static:person_day_win_times:' . $mId]    = get_val($cjRule, 'phone_day_count');    //个人日中奖次数
                $data['static:person_total_win_times:' . $mId]  = get_val($cjRule, 'phone_total_count'); //个人总中奖次数
                $data['static:person_day_part_times:' . $mId]   = get_val($cjRule, 'phone_day_part');     //个人日参与次数
                $data['static:person_total_part_times:' . $mId] = get_val($cjRule, 'phone_total_part'); //个人总参与次数

                $this->RedisHelper->hMset($this->drawLotteryMainKey, $data);
                $this->RedisHelper->releaseLockByLua('generateDrawLotteryFinalData:' . $mId, $identifier);//释放锁
                return $data;
            }
        }

        return null;
    }


    /**
     * 组建商品相关数据
     * @author Jeff.Liu<liuwy@imageco.com.cn>
     *
     * @param $mId
     * @param $finalData
     * @param $withStorage
     *
     * @return array
     */
    public function assembleGoodsRelationData($mId, &$finalData, $withStorage)
    {
        $bonusDetailList    = [];
        $bonusDetailIdList  = [];
        $cjBatchList        = M('tcj_batch')->where(['batch_id' => $mId])->select();
        $batchInfoList      = M('tbatch_info')->where(['m_id' => $mId])->select();
        $goodsInfoList      = [];
        $finalBatchInfoList = [];
        if (is_array($batchInfoList)) {
            $goodsIdList = [];
            foreach ($batchInfoList as $index => $item) {
                $goodsId = isset($item['goods_id']) ? $item['goods_id'] : -1;
                if ($goodsId != -1) {
                    $goodsIdList[] = $goodsId;
                }

                $id                      = get_val($item, 'id');
                $finalBatchInfoList[$id] = $item;
                unset($batchInfoList[$index], $item);
            }

            if ($goodsIdList) {
                $goodsInfoList = M('tgoods_info t1')->field('t1.*,t2.id as bonus_detail_id')->join(
                        'tbonus_detail t2 ON t1.bonus_id=t2.bonus_id'
                )->where('t1.goods_id IN("' . implode('","', $goodsIdList) . '")')->select();
                foreach ($goodsInfoList as $index => $goodsInfo) {
                    if (isset($goodsInfo['bonus_detail_id']) && $goodsInfo['bonus_detail_id']) {
                        $bonusDetailIdList[] = $goodsInfo['bonus_detail_id'];
                    }
                }

                if ($bonusDetailIdList) {
                    $where              = 'id IN(' . implode(',', $bonusDetailIdList) . ')';
                    $tmpBonusDetailList = M('tbonus_detail')->where($where)->select();
                    foreach ($tmpBonusDetailList as $index => $item) {
                        $bonusDetailList[$item['bonus_id']] = $item;
                    }
                }
            }
        }

        $finalCjBatchList = [];
        foreach ($cjBatchList as $i => $item) {
            $id     = get_val($item, 'id');
            $status = get_val($item, 'status');
            if ($status == self::AWARD_OPENED) { //只保存开启的奖品
                $finalCjBatchList[$id] = $item;
            }
            $bId                                   = get_val($item, 'b_id');
            $currentBatchInfo                      = get_val($finalBatchInfoList, $bId);
            $staticSingleGoodsDayWinTimes          = $this->getStaticSingleGoodsDayWinTimesKey($id);
            $staticSingleGoodsTotalWinTimes        = $this->getStaticSingleGoodsTotalWinTimesKey($id);
            $data[$staticSingleGoodsDayWinTimes]   = get_val($item, 'day_count');   //奖品日中奖次数（静态）
            $data[$staticSingleGoodsTotalWinTimes] = get_val($item, 'total_count'); //奖品总中奖次数（静态）
            if ($withStorage) {
                $staticStorageNum        = $this->getStaticStorageNumKey($id);
                $data[$staticStorageNum] = get_val($item, 'total_count');  //奖品库存数（静态）
                $dynamicStorageNum       = $this->getDynamicStorageNumKey($id);
                //PS: 进入编辑页面页面的时候 不要忘记调用$this->updateDbRemainNumFromRedis($mId)
                $remainStorage            = get_val($currentBatchInfo, 'remain_num');
                $data[$dynamicStorageNum] = $remainStorage;  //奖品库存数（动态）
            }
            unset($cjBatchList[$i], $item, $i);
        }

        $finalGoodsInfoList = [];
        foreach ($goodsInfoList as $i => $item) {
            $goods_id                      = get_val($item, 'goods_id');
            $finalGoodsInfoList[$goods_id] = $item;
            unset($goodsInfoList[$i], $item, $i);
        }

        $finalData['staticAwardList']  = $finalCjBatchList;
        $finalData['dynamicAwardList'] = $finalBatchInfoList;
        $finalData['goodsInfoList']    = $finalGoodsInfoList;
        $finalData['bonusDetailList']  = $bonusDetailList;
        return $finalData;
    }

    /**
     * 组建tmarketing_info相关数据
     * @author Jeff.Liu<liuwy@imageco.com.cn>
     *
     * @param $mId
     * @param $finalData
     *
     * @return array
     */
    public function assembleCjCateList($mId, &$finalData)
    {
        $cjCateList              = $this->getFormatedcjCateListFromDb($mId);
        $finalData['cjCateList'] = $cjCateList;
        return $finalData;
    }

    /**
     * 组建tmarketing_info相关数据
     * @author Jeff.Liu<liuwy@imageco.com.cn>
     *
     * @param $mId
     * @param $finalData
     *
     * @return array
     */
    public function assembleMarketingInfo($mId, &$finalData)
    {
        $marketingInfo              = $this->getFormatedMarketingInfoFormDb($mId);
        $finalData['marketingInfo'] = $marketingInfo;
        return $finalData;
    }

    /**
     * 组建tcj_rule相关数据
     * @author Jeff.Liu<liuwy@imageco.com.cn>
     *
     * @param $mId
     * @param $finalData
     */
    public function assembleCjRule($mId, &$finalData)
    {
        $this->drawLotteryMainKey = $this->generateMainKey($mId);
        if ($mId) {
            $staticPersonDayWinTimesKey                = $this->getStaticPersonDayWinTimesKey($mId);
            $staticPersonTotalWinTimesKey              = $this->getStaticPersonTotalWinTimesKey($mId);
            $staticPersonDayPartTimesKey               = $this->getStaticPersonDayPartTimesKey($mId);
            $staticPersonTotalPartTimesKey             = $this->getStaticPersonTotalPartTimesKey($mId);
            $cjRule                                    = M('tcj_rule')->where(['batch_id' => $mId])->find();
            $finalData['cjRule']                       = $cjRule;
            $finalData[$staticPersonDayWinTimesKey]    = get_val($cjRule, 'phone_day_count');    //个人日中奖次数
            $finalData[$staticPersonTotalWinTimesKey]  = get_val($cjRule, 'phone_total_count'); //个人总中奖次数
            $finalData[$staticPersonDayPartTimesKey]   = get_val($cjRule, 'phone_day_part');     //个人日参与次数
            $finalData[$staticPersonTotalPartTimesKey] = get_val($cjRule, 'phone_total_part'); //个人总参与次数
        }

        return $finalData;
    }

    public function getStaticPersonDayWinTimesKey($mId)
    {
        return 'static:person_day_win_times:' . (int)$mId;
    }

    public function getStaticPersonTotalWinTimesKey($mId)
    {
        return 'static:person_total_win_times:' . (int)$mId;
    }

    public function getStaticPersonDayPartTimesKey($mId)
    {
        return 'static:person_day_part_times:' . (int)$mId;
    }

    public function getStaticPersonTotalPartTimesKey($mId)
    {
        return 'static:person_total_part_times:' . (int)$mId;
    }

    public function getStaticSingleGoodsDayWinTimesKey($id)
    {
        return 'static:day_win_times:' . (int)$id;
    }

    public function getStaticSingleGoodsTotalWinTimesKey($id)
    {
        return 'static:total_win_times:' . (int)$id;
    }

    public function getStaticStorageNumKey($id)
    {
        return 'static:storage_num:' . (int)$id;
    }

    public function getDynamicStorageNumKey($id)
    {
        return 'dynamic:storage_num:' . (int)$id;
    }

    /**
     * 获得格式化好的marketingInfo 数据
     * @author Jeff.Liu<liuwy@imageco.com.cn>
     *
     * @param $mId
     *
     * @return mixed
     */
    public function getFormatedMarketingInfoFormDb($mId)
    {
        $marketingInfo = M('tmarketing_info')->where(['id' => $mId])->find();
        $memberBatchId = isset($marketingInfo['member_batch_id']) ? $marketingInfo['member_batch_id'] : -1;
        if ($memberBatchId != -1) {
            $memberBatchId = explode(',', $memberBatchId);
        }
        $marketingInfo['member_batch_id'] = $memberBatchId;
        $memberBatchAwardId               = isset($marketingInfo['member_batch_award_id']) ? $marketingInfo['member_batch_award_id'] : -1;
        if ($memberBatchAwardId != -1) {
            $memberBatchAwardId = explode(',', $memberBatchAwardId);
        }
        $marketingInfo['member_batch_award_id'] = $memberBatchAwardId;
        return $marketingInfo;
    }

    /**
     * @param $mId
     *
     * @return array|mixed
     */
    public function getFormatedcjCateListFromDb($mId)
    {
        $cjCateList = M('tcj_cate')->where(['batch_id' => $mId])->select();
        if (is_array($cjCateList)) {
            foreach ($cjCateList as $index => $item) {
                $memberBatchId = isset($item['member_batch_id']) ? $item['member_batch_id'] : -1;
                if ($memberBatchId != -1) {
                    $memberBatchId = explode(',', $memberBatchId);
                }
                $cjCateList[$index]['member_batch_id'] = $memberBatchId;
            }
        }
        return $cjCateList;
    }

    /**
     * 将redis中的剩余库存刷新到db对应的表中 (再编辑页面奖品页面 最开始的时候调用 -- 确保显示的数据是正确的)
     * @author Jeff Liu<liuwy@imageco.com.cn> 2016-03-15
     *
     * @param $mId
     *
     * @return boolean
     */
    public function updateDbRemainNumFromRedis($mId)
    {
        $this->drawLotteryMainKey = $this->generateMainKey($mId);
        if ($mId) {
            $cjBatchList = M('tcj_batch')->where(['batch_id' => $mId])->select();

            foreach ($cjBatchList as $item) {
                $id                = get_val($item, 'id');
                $bId               = get_val($item, 'b_id');
                $dynamicStorageNum = $this->RedisHelper->hGet($this->drawLotteryMainKey, 'dynamic:storage_num:' . $id);
                if (is_numeric($dynamicStorageNum)) {
                    M('tbatch_info')->where(['id' => $bId])->save(['remain_num' => $dynamicStorageNum]);
                }
            }
            return true;
        }
        return true;
    }

    /**
     *
     * @param int   $mId     活动id（tmarketing_info id）
     * @param int   $bId     id（tbatch_info id）
     * @param array $storage ['remain_num' => 剩余库存, 'storage_num' => 总投入库存] //这些数据都是已经计算好了的
     *
     * @return bool
     */
    public function updateDbStorage($mId, $bId, $storage)
    {
        $dynamicStorageNum = $storage['remain_num'];
        $totalStorageNum   = $storage['storage_num'];
        if ($dynamicStorageNum > $totalStorageNum || $dynamicStorageNum < 0 || $totalStorageNum < 0) { //数据不合法
            return false;
        }
        M('tbatch_info')->where(['id' => $bId])->save(
                ['storage_num' => $totalStorageNum, 'remain_num' => $dynamicStorageNum]
        );
        M('tcj_batch')->where(['b_id' => $bId])->save(
                ['total_count' => $totalStorageNum, 'award_rate' => $totalStorageNum]
        );

        //重新初始化redis里面的数据 （除了库存之外的动态数据并没有初始化）
        $this->generateDrawLotteryFinalDataWithStorage($mId);

        return $this->generateDrawLotteryFinalDataWithStorage($mId);
    }


    /**
     * 奖品发送
     *
     * @param $awardInfo
     *
     * @return bool
     */
    private function send_award($awardInfo)
    {
        if (empty($this->RemoteRequestService)) {
            $this->RemoteRequestService = D('RemoteRequest', 'Service');
        }
        $this->request_id = $this->save_request_id; // 凭证发送单号

        if ($awardInfo['award_origin'] == '2') { // 发旺财
            if ($this->send_mode == '1') { // 微信卡券发送
                if ($this->wx_open_id == null) {
                    $this->saveCjTrace($awardInfo, 1, 'wx_open_id is null');
                    return $this->returnErrorByErrno(1301);
                }
                $data_from = $this->batch_type + 1;
                //$req_data  = "&node_id=" . $this->node_id . "&open_id=" . $this->wx_open_id . "&batch_info_id=" . $award_info['b_id'] . "&data_from=" . $data_from . "&request_id=" . $TransactionID;
                //$resp_array = $this->RemoteRequestService->requestWcWeiXinServ($req_data);//todo 修改以前的逻辑 curl毛线啊
                $resp_array = $this->requestWcWeiXinServ(
                        $this->node_id,
                        $this->wx_open_id,
                        $awardInfo['b_id'],
                        $data_from,
                        $this->request_id
                );
                if (!$resp_array || ($resp_array['resp_id'] != '0000' && $resp_array['resp_id'] != '0001')) {
                    $this->saveCjTrace($awardInfo, 1, '旺财发码失败1:' . $resp_array['resp_desc']);
                    return $this->returnError('旺财发码失败1:' . $resp_array['resp_desc'], $resp_array['resp_id']);
                } else {
                    $this->wx_card_info = $resp_array['resp_data']['card_info'];
                }
            } else {
                $TransactionID = $this->request_id;
                $imsFlag       = '1';
                $sendPhoneNo   = $this->phone_no;
                if ($this->join_mode == '1') { // 微信参与、手机发送需要特殊处理，先制卡，后转发
                    // 翼惠宝是同时将openid和phone_no送至抽奖接口，无需变更send_mobile&&河北平安非标
                    if (!$this->fbYhbFlag) {
                        $sendPhoneNo = $this->send_mobile = '13900000000';
                    } else {
                        $sendPhoneNo = $this->send_mobile;
                    }
                    $imsFlag = '2';
                } else {
                    $this->request_id = '';
                }
                $data_from       = $this->batch_type + 1;
                $awardOtherParam = "data_from=" . $data_from . "&ticket_seq=" . $this->ticket_seq . "&batch_type=" . $this->batch_type . "&store_id=" . $this->store_id . "&channel_id=" . $this->channel_id . "&wx_open_id=" . $this->wx_open_id . "&member_id=" . $this->memberId;

                $goodsInfo = $this->getGoodsInfo($awardInfo['goods_id']);
                $dealFlag  = 1;
                $transType = 1;
                if ($goodsInfo['goods_type'] == '15') { //流量包
                    $transType = 3;
                    if ($imsFlag == '2') {
                        $dealFlag = 0;
                    }
                } else if ($goodsInfo['goods_type'] == '8') {  // 8 Q币  做特殊出来 只需要将dealFlag设置为0 就行了
                    $dealFlag = 0;
                } else if ($goodsInfo['goods_type'] == '7') { // 7 话费 做特殊出来 只需要将trans_type设置为4 $dealFlag设置为0 就行了
                    $transType = 4;
                    if ($imsFlag == '2') {
                        $dealFlag = 0;
                    }
                }
                $rs = $this->saveSendAwardTrace(
                        $sendPhoneNo,
                        $transType,
                        $awardInfo,
                        $TransactionID,
                        $awardOtherParam,
                        $dealFlag,
                        $imsFlag
                );
                if ($rs === false) {
                    $desc    = isset($resp_array['resp_desc']) ? $resp_array['resp_desc'] : '';
                    $resp_id = isset($resp_array['resp_id']) ? $resp_array['resp_id'] : '';
                    $this->saveCjTrace($awardInfo, 1, '旺财发码失败2:' . $desc);
                    return $this->returnError('旺财发码失败2:' . $desc, $resp_id);
                }
            }
        }

        return true;
    }

    /**
     * 进入发送流水表-发码
     *
     * @param $phone
     * @param $transType
     * @param $awardInfo
     * @param $requestId
     * @param $otherParams
     * @param $dealFlag
     * @param $imsFlag
     *
     * @return bool
     */
    private function saveSendAwardTrace($phone, $transType, $awardInfo, $requestId, $otherParams, $dealFlag, $imsFlag)
    {
        $sendAwardTrace['trans_type']        = $transType;
        $sendAwardTrace['node_id']           = $this->node_id;
        $sendAwardTrace['phone_no']          = $phone;
        $sendAwardTrace['batch_no']          = $awardInfo['activity_no'];
        $sendAwardTrace['request_id']        = $requestId;
        $sendAwardTrace['batch_info_id']     = $awardInfo['b_id'];
        $sendAwardTrace['award_other_param'] = $otherParams;
        $sendAwardTrace['deal_flag']         = $dealFlag;
        $sendAwardTrace['ims_flag']          = $imsFlag;
        $sendAwardTrace['m_id']              = $this->batch_id;
        $sendAwardTrace['add_time']          = date('YmdHis');
        $rs                                  = M('tsend_award_trace')->add($sendAwardTrace);
        if (!$rs) {
            $this->log("进入发送流水表[tsend_award_trace]失败" . M()->_sql() . ' db error:' . M()->getDbError());
            $desc    = isset($resp_array['resp_desc']) ? $resp_array['resp_desc'] : '';
            $resp_id = isset($resp_array['resp_id']) ? $resp_array['resp_id'] : '';
            $this->saveCjTrace($awardInfo, 1, '进入发送流水表[tsend_award_trace]失败:' . M()->getDbError());
            return $this->returnError('进入发送流水表[tsend_award_trace]失败:' . M()->getDbError(), $resp_id);
        } else {
            $goodsInfo = $this->getGoodsInfo($awardInfo['goods_id']);
            if ($goodsInfo) {
                if (in_array($goodsInfo['goods_type'], [22, 7, 8, 15])) {
                    $this->savePosDayCount(
                            $this->node_id,
                            $awardInfo['activity_no'],
                            '',
                            $awardInfo['b_id'],
                            date('Y-m-d'),
                            $goodsInfo,
                            1
                    );
                }
            }
        }
        return true;
    }

    /**
     * @param $nodeId
     * @param $batchNo
     * @param $posId
     * @param $batchInfoId
     * @param $transDate
     * @param $goodsInfo
     * @param $succNum
     */
    private function savePosDayCount($nodeId, $batchNo, $posId, $batchInfoId, $transDate, $goodsInfo, $succNum)
    {
        $where         = "NODE_ID ='" . $nodeId . "' and BATCH_NO ='" . $batchNo . "' and POS_ID ='" . $posId . "'  and b_id = " . $batchInfoId . " and TRANS_DATE ='" . $transDate . "' and b_id = " . $batchInfoId;
        $pos_day_count = M('TposDayCount')->where($where)->find();
        if (!$pos_day_count) {
            $pos_day_count['node_id']    = $nodeId;
            $pos_day_count['pos_id']     = $posId;
            $pos_day_count['batch_no']   = $batchNo;
            $pos_day_count['b_id']       = $batchInfoId;
            $pos_day_count['trans_date'] = date('Y-m-d');
            $pos_day_count['send_num']   = $succNum;
            $pos_day_count['send_amt']   = $goodsInfo['batch_amt'] * $succNum;
            $pos_day_count['verify_num'] = 0;
            $pos_day_count['verify_amt'] = 0;
            $pos_day_count['cancel_num'] = 0;
            $pos_day_count['cancel_amt'] = 0;
            $pos_day_count['goods_id']   = $goodsInfo['goods_id'];
            $rs                          = M('TposDayCount')->add($pos_day_count);
            if ($rs === false) {
                $this->log("记录统计信息[tpos_day_count]失败 " . M()->_sql() . M()->getDbError());
            }
        } else {
            $new_day_count             = array();
            $new_day_count['send_num'] = $pos_day_count['send_num'] + $succNum;
            $new_day_count['send_amt'] = $pos_day_count['send_amt'] + $goodsInfo['batch_amt'] * $succNum;
            $rs                        = M('TposDayCount')->where($where)->save($new_day_count);
            if ($rs === false) {
                $this->log("更新统计信息[tpos_day_count]失败" . M()->_sql());
            }
        }
    }

    /**
     * 校验中奖数限制条件
     *
     * @param $staticAwardInfo
     *
     * @return array|bool
     */
    private function checkAwardCountRule($staticAwardInfo)
    {
        $scriptFiles = [
                $this->getLuaFilePath('tonumber_with_default.lua'),
                $this->getLuaFilePath('check_award_count_rule.lua'),
        ];
        $ret         = $this->RedisHelper->loadScriptWithAliasAndExec(
                ['alias' => 'checkAwardCountRule', 'luaFiles' => $scriptFiles],
                [$this->generateMainKey($this->batch_id), $staticAwardInfo['id'], $this->currentDateStr]
        );
        // 单日抽奖校验
        switch ($ret) {
            case 2: // -- 奖品日中奖次数达到上限
                return $this->returnErrorByErrno(1006, $staticAwardInfo);//原来是1005 已经重复了。。
            case 3: // -- 奖品总中奖次数达到上限
            case 4: //  -- 奖品库存不足
                return $this->returnErrorByErrno(1017, $staticAwardInfo);
            default:
                return true;
        }
    }

    /**
     * 获得lua脚本所在目录
     *
     * @param string $fileName
     *
     * @return string
     */
    private function getLuaFilePath($fileName = 'tonumber_with_default.lua')
    {
        $path = APP_PATH . '/Common/';
        return $path . $fileName;
    }

    /**
     * 验证抽奖规则并更新对应的数据
     * @return bool|string
     */
    private function verifyPersonDrawLotteryRuleAndUpdate()
    {
        $scriptFiles = [
                $this->getLuaFilePath('tonumber_with_default.lua'),
                $this->getLuaFilePath('verify_person_drawLottery_rule_and_update.lua'),
        ];
        $personId    = $this->getPersonId();
        $ret         = $this->RedisHelper->loadScriptWithAliasAndExec(
                ['alias' => 'verifyPersonDrawLotteryRuleAndUpdate', 'luaFiles' => $scriptFiles],
                [
                        $this->generateMainKey($this->batch_id),
                        $this->batch_id,
                        $personId,
                        $this->currentDateStr,
                        $this->currentDate24HLeftSeconds,
                ]
        );
        // 单日抽奖校验
        switch ($ret) {
            case 2: // -- 达到当日抽奖上限
                return $this->returnErrorByErrno(1005, ['phone_no' => $this->phone_no]);
            case 3: // -- 达到抽奖上限
                return $this->returnErrorByErrno(1016, ['phone_no' => $this->phone_no]);
            case 4: //  -- 到达当日中奖上限
                $msg = '当日此号码[' . $this->phone_no . ']已达中奖上限';
                $this->saveCjTrace([], 1, $msg);
                return $this->returnErrorByErrno(1003, ['phone_no' => $this->phone_no]);
            case 5: // -- 达到中奖上限
                $msg = '此号码[' . $this->phone_no . ']已达中奖上限';
                $this->saveCjTrace([], 1, $msg);
                return $this->returnErrorByErrno(1014, ['phone_no' => $this->phone_no]);
            default:
                return true;
        }
    }



    /**
     * 保存抽奖流水
     *
     * @param $awardInfo
     * @param $status
     * @param $retMsg
     */
    private function saveCjTrace($awardInfo, $status, $retMsg)
    {
        if (empty($this->CjTraceModel)) {
            $this->CjTraceModel = D('CjTrace');
        }
        $cjTrace                     = [];
        $cjTrace['label_id']         = $this->label_id;
        $cjTrace['batch_type']       = $this->batch_type;
        $cjTrace['batch_id']         = $this->batch_id;
        $cjTrace['channel_id']       = $this->channel_id;
        $cjTrace['mobile']           = $this->phone_no;
        $cjTrace['ip']               = $this->ip;
        $cjTrace['status']           = $status;
        $cjTrace['node_id']          = $this->node_id;
        $cjTrace['add_time']         = date('YmdHis');
        $cjTrace['rule_id']          = '0';
        $cjTrace['prize_level']      = '0'; // 默认0级
        $cjTrace['cj_rule_id']       = $this->cj_rule_id;
        $cjTrace['ret_msg']          = $retMsg;
        $cjTrace['ticket_num']       = $this->ticket_seq;
        $cjTrace['cate_id']          = $this->wx_cjcate_id;
        $cjTrace['user_id']          = $this->wx_wap_ranking_id;
        $cjTrace['wx_name']          = $this->wx_nick;
        $cjTrace['join_mode']        = $this->join_mode;
        $cjTrace['send_mobile']      = $this->send_mobile;
        $cjTrace['send_mode']        = $this->send_mode;
        $cjTrace['pay_token']        = $this->pay_token; // 付满送token
        $cjTrace['request_id']       = $this->save_request_id; // 发码接口请求ID
        $cjTrace['full_id']          = $this->full_id; // full_id
        $cjTrace['participation_id'] = $this->participation_id;
        $cjTrace['b_id']             = $this->b_id;
        $cjTrace['g_id']             = $this->g_id;
        $cjTrace['member_id']        = $this->memberId;

        if ($status == '2') { // 中奖了才记录奖品等级和奖品id
            $cjTrace['rule_id']     = $awardInfo['id'];
            $cjTrace['prize_level'] = $awardInfo['award_level'];
        }

        $rs = $this->CjTraceModel->add($cjTrace);

        if ($rs === false) {
            $this->log(print_r($cjTrace, true));
            $this->log("记录流水信息[tcj_trace]失败");
        } else {
            $this->cj_trace_id = $rs;
        }
    }

    /**
     * 校验会员中奖资格
     *
     * @param $staticAwardInfo
     *
     * @return bool|string
     */
    private function checkAwardRange($staticAwardInfo)
    {
        if ($staticAwardInfo['member_batch_id'] != -1) {
            if (!in_array($this->memberInfo['card_id'], $staticAwardInfo['member_batch_id'])) {
                $this->saveCjTrace($staticAwardInfo, 1, '该会员不能中该奖品');
                return $this->returnErrorByErrno(1007);
            }
        }

        return true;
    }

    /**
     * 校验营销活动会员参与资格
     *
     * @param $marketingInfo
     *
     * @return bool|string
     */
    private function checkJoinRangeMarketing($marketingInfo)
    {
        if ($marketingInfo['member_batch_id'] != -1) {
            if (!in_array($this->memberInfo['card_id'], $marketingInfo['member_batch_id'])) {
                $this->saveCjTrace($marketingInfo, 1, '不能参与该活动');
                return $this->returnErrorByErrno(1012);
            }
        }

        return true;
    }

    /**
     * 校验营销活动会员中奖资格
     *
     * @param $marketingInfo
     *
     * @return bool|string
     */
    private function checkAwardRangeMarketing($marketingInfo)
    {
        if ($marketingInfo['member_batch_award_id'] != '-1') {
            if (!in_array($this->memberInfo['card_id'], $marketingInfo['member_batch_award_id'], true)) {
                $this->saveCjTrace($marketingInfo, 1, '该会员不能中该奖品');
                return $this->returnErrorByErrno(1009);
            }
        }

        return true;
    }

    /**
     * 获取返回奖品是否支持线上提领
     *
     * @param $dynamicAwardInfo
     *
     * @return mixed
     */
    private function getOnlineVerifyFlag($dynamicAwardInfo)
    {
        // 查找goods_info 获得bonus_id
        $goods_info = $this->getGoodsInfo($dynamicAwardInfo['goods_id']);
        return $goods_info['online_verify_flag'];
    }

    /**
     * 定向红包处理
     *
     * @param $batchInfo
     *
     * @return bool|mixed|string
     */
    private function bonusDeal($batchInfo)
    {
        // 查找goods_info 获得bonus_id
        $goodsInfo = $this->getGoodsInfo($batchInfo['goods_id']);
        // 查tbonus_detail
        $bonusDetail = $this->getBonusDetail($goodsInfo['bonus_id']);
        // 更新数据
        $this->increaseBonusDetailGetNum($goodsInfo);
        // 插入tbonus_use_detail
        $tbonusUseDetail['m_id']            = $bonusDetail['m_id'];
        $tbonusUseDetail['node_id']         = $bonusDetail['node_id'];
        $tbonusUseDetail['bonus_id']        = $bonusDetail['bonus_id'];
        $tbonusUseDetail['bonus_detail_id'] = $bonusDetail['id'];
        $tbonusUseDetail['bonus_num']       = 1;
        $tbonusUseDetail['bonus_use_num']   = 0;
        $tbonusUseDetail['phone']           = $this->phone_no;
        $tbonusUseDetail['status']          = '1';
        $tbonusUseDetail['request_id']      = $this->save_request_id;

        $rs = $this->addBonusUseDetail($tbonusUseDetail);
        if ($rs !== true) {
            return $rs;
        }
        return true;
    }

    /**
     * @param $data
     *
     * @return bool|string
     */
    public function addBonusUseDetail($data)
    {
        $rs = M('tbonus_use_detail')->add($data);
        if ($rs === false) {
            $this->log('tbonus_use_detail 插入失败' . M()->_sql());
            return $this->returnErrorByErrno(1063, $data);
        } else {
            $this->bonus_use_detail_id = $rs;
        }
        return true;
    }

    /**
     * 积分奖品
     *
     * @param $batchInfo
     * @param $awardInfo
     *
     * @return bool|string
     */
    private function integralDeal($batchInfo, $awardInfo)
    {
        $integralDetail = array(
                'm_id'           => $this->batch_id,
                'node_id'        => $this->node_id,
                'integral_num'   => $batchInfo['batch_amt'],
                'status'         => '0',
                'add_time'       => date('YmdHis'),
                'request_id'     => $this->save_request_id,
                'openid'         => $this->phone_no,
                'join_member_id' => $this->memberId,
                'b_id'           => $batchInfo['id'],
                'channel_id'     => $this->channel_id,
        );

        if ($this->memberId) {
            $integralDetail['member_id'] = $this->memberId;
            $integralDetail['status']    = '1';
        }

        $this->integral_get_id = M('tintegal_get_detail')->add($integralDetail);
        if ($this->integral_get_id === false) {
            $this->log('tintegal_get_detail 插入失败' . M()->_sql());
            return $this->returnError('tintegal_get_detail 插入失败', '1203');
        }

        if ($this->memberId) {
            if (empty($this->IntegralPointTraceModel)) {
                $this->IntegralPointTraceModel = D('IntegralPointTrace');
            }
            $rs = $this->IntegralPointTraceModel->integralPointChange(
                    12,
                    $batchInfo['batch_amt'],
                    $this->memberId,
                    $this->node_id,
                    $this->integral_get_id
            );
            if ($rs === false) {
                $this->saveCjTrace($awardInfo, 1, '增加积分失败');
                return $this->returnError('增加积分失败', '1202');
            }
            // 添加积分行为数据
            if (empty($this->MemberBehaviorModel)) {
                $this->MemberBehaviorModel = D('MemberBehavior');
            }
            $flag = $this->MemberBehaviorModel->addBehaviorType(
                    $this->memberId,
                    $this->node_id,
                    15,
                    $batchInfo['batch_amt'],
                    $this->batch_id
            );
            if ($flag === false) {
                $this->log("===MEM_DEBUG===记录会员行为数据[抽奖流水]member_id[{$this->memberId}],node_id[{$this->node_id}],1");
            }
        }

        return true;
    }

    /**
     * 付满送
     */
    private function pay_give()
    {
        $where        = "id = " . $this->channel_id;
        $channel_info = M('tchannel')->where($where)->find();
        if (!$channel_info) {
            $this->log("没有状态正常的渠道" . M()->_sql());
            return $this->returnError('该渠道[' . $this->channel_id . ']不存在', '1050');
        }
        M()->startTrans(); // 校验中奖数 起事务
        // 查tpay_give_order 获取 状态 校验
        $where               = "pay_token =  '" . $this->pay_token . "' and status = '1'";
        $pay_give_order_info = M('tpay_give_order')->lock(true)->where($where)->find();
        if (!$pay_give_order_info) {
            M()->rollback();
            $this->log('没有找到状态正常的该订单' . M()->_sql());
            return $this->returnErrorByErrno(1051, ['pay_token' => $this->pay_token]);
        }
        // 查tcj_trace 进行参与数量校验 修改tpay_give_order状态
        // 校验是否已经有过抽奖记录
        if ($channel_info['join_limit'] > 0) {
            if ($channel_info['join_limit'] <= $pay_give_order_info['use_times']) {
                M()->rollback();
                return $this->returnErrorByErrno(1053, ['pay_token' => $this->pay_token]);
            }
            // 更新次数
            $rs = M('tpay_give_order')->where($where)->setInc('use_times');
            if ($rs === false) {
                M()->rollback();
                return $this->returnErrorByErrno(1054, ['pay_token' => $this->pay_token]);
            }
        }
        $this->pay_give_order_info = $pay_give_order_info;
        M()->commit(); // 提交事务

        return true;
    }

    /**
     * 付满送中奖次数累加
     */
    private function pay_give_stat()
    {
        M()->startTrans();
        $where = "pay_token =  '" . $this->pay_token . "' and status = '1'";
        // 更新次数
        $rs = M('tpay_give_order')->where($where)->setInc('award_times');
        if ($rs === false) {
            M()->rollback();
            return $this->returnErrorByErrno(1055, ['pay_token' => $this->pay_token]);
        }

        // 对营业员中奖数进行累加
        if ($this->pay_give_order_info != null && $this->pay_give_order_info['clerk_id'] != null) {
            $where = "clerk_id = '{$this->pay_give_order_info['clerk_id']}'";
            // 更新次数
            $rs = M('tpay_give_clerk')->where($where)->setInc('cj_cnt');
            if ($rs === false) {
                $this->log('对营业员中奖数进行累加error' . M()->_sql());
            }
        }

        return true;
    }

    /**
     * @return mixed
     */
    private function getStaticAwardList()
    {
        return $this->drawLotteryFinalData['staticAwardList'];
    }

    /**
     * @return mixed
     */
    private function getDynamicAwardList()
    {
        return $this->drawLotteryFinalData['dynamicAwardList'];
    }

    /**
     * @param $bId
     *
     * @return null
     */
    private function getDynamicAwardInfo($bId)
    {
        $dynamicAwardList = $this->getDynamicAwardList();
        return isset($dynamicAwardList[$bId]) ? $dynamicAwardList[$bId] : null;
    }

    /**
     *
     * @param     $goodsInfo
     * @param int $storageNum
     *
     * @return bool
     */
    private function increaseBonusDetailGetNum($goodsInfo, $storageNum = 1)
    {
        $where = 'bonus_id = ' . $goodsInfo['bonus_id'];
        $rs    = M('tbonus_detail')->where($where)->setInc('get_num', $storageNum);
        if ($rs === false) {
            $this->log('bonus_detail 更新失败' . M()->_sql());
            return $this->returnErrorByErrno(1062, $goodsInfo);
        }

        return true;
    }

    /**
     * @param $cjCateId
     *
     * @return null
     */
    public function getCjCate($cjCateId)
    {
        return isset($this->drawLotteryFinalData['cjCateList'][$cjCateId]) ? $this->drawLotteryFinalData['cjCateList'][$cjCateId] : null;
    }

    /**
     * @return BatchChannelRedisModel
     */
    public function getCjCateList()
    {
        return isset($this->drawLotteryFinalData['cjCateList']) ? $this->drawLotteryFinalData['cjCateList'] : null;
    }

    /**
     * @param $goodsId
     *
     * @return null
     */
    public function getGoodsInfo($goodsId)
    {
        return isset($this->drawLotteryFinalData['goodsInfoList'][$goodsId]) ? $this->drawLotteryFinalData['goodsInfoList'][$goodsId] : null;
    }

    /**
     * @return null
     */
    public function getGoodsInfoList()
    {
        return isset($this->drawLotteryFinalData['goodsInfoList']) ? $this->drawLotteryFinalData['goodsInfoList'] : null;
    }

    /**
     * @param $bonusDetailId
     *
     * @return null
     */
    public function getBonusDetail($bonusDetailId)
    {
        return isset($this->drawLotteryFinalData['bonusDetailList'][$bonusDetailId]) ? $this->drawLotteryFinalData['bonusDetailList'][$bonusDetailId] : null;
    }

    public function getBonusDetailList()
    {
        return isset($this->drawLotteryFinalData['bonusDetailList']) ? $this->drawLotteryFinalData['bonusDetailList'] : null;
    }

    /**
     * @return mixed
     */
    private function getMarketingInfo()
    {
        return $this->drawLotteryFinalData['marketingInfo'];
    }

    /**
     *
     * @param $marketingInfo
     *
     * @return bool|string
     */
    private function fb00021275($marketingInfo)
    {
        $selectType = get_val($marketingInfo, 'select_type');
        $selectarr  = explode('-', $selectType);
        if (($this->node_id == '00021275') && ($marketingInfo['defined_one_name'] == '小票号') && in_array(
                        '10',
                        $selectarr
                )
        ) {
            if (!$this->ticket_seq) {
                return $this->returnErrorByErrno(1031);
            }
            $where = "batch_id = " . $this->batch_id . " and ticket_num = '" . $this->ticket_seq . "'";
            M()->startTrans();
            $fbTicketInfo = M('tfb_ticket_trace')->lock(true)->where($where)->find();
            if (!$fbTicketInfo) {// add
                $fbTicketInfoNew               = [];
                $fbTicketInfoNew['batch_type'] = $this->batch_type;
                $fbTicketInfoNew['batch_id']   = $this->batch_id;
                $fbTicketInfoNew['ticket_num'] = $this->ticket_seq;
                $fbTicketInfoNew['use_count']  = 1;
                $rs                            = M('tfb_ticket_trace')->add($fbTicketInfoNew);
                if (!$rs) {
                    M()->rollback();
                    return $this->returnErrorByErrno(1030);
                }
                M()->commit(); // 提交事务
            } else {
                // 更新标记
                $fbTicketInfoUpdate              = [];
                $fbTicketInfoUpdate['use_count'] = $fbTicketInfo['use_count'] + 1;

                $rs = M('tfb_ticket_trace')->where('id = ' . $fbTicketInfo['id'])->save($fbTicketInfoUpdate);
                if ($rs === false) {
                    M()->rollback();
                    $this->log("更新状态[tfb_ticket_trace]失败");
                    return $this->returnErrorByErrno(1031);
                }
                M()->commit(); // 提交事务
                if ($fbTicketInfo['use_count'] >= $this->ticket_limit_num) {
                    return $this->returnErrorByErrno(1032);
                }
            }
        }

        return true;
    }

    /**
     * @return mixed
     */
    public function multipleAward()
    {
        // 查找marketing_info 信息
        $marketingInfo = $this->getMarketingInfo();

        // 如果是微信参与的话替换手机号码为微信open_id
        $this->send_mobile = $this->phone_no;
        $this->join_mode   = get_val($marketingInfo, 'join_mode');
        if ($this->join_mode == self::WECHAT_JOIN) { // 微信参与
            $this->phone_no = $this->wx_open_id;
        }

        //获得会员信息
        $this->getMemberInfo($marketingInfo);

        $ret = $this->fb00021275($marketingInfo);
        if ($ret !== true) {
            return $ret;
        }

        if ($this->memberId) {// 添加会员行为记录
            $this->addBehaviorType();
        }

        // 判断非会员标识
        $verifyMemberFlagStatus = $this->verifyMemberFlag($marketingInfo);
        if ($verifyMemberFlagStatus !== true) {
            return $verifyMemberFlagStatus;
        }

        // 校验付满送业务
        if ($this->pay_token != null) {
            $payGiveStatus = $this->pay_give();
            if ($payGiveStatus !== true) {
                return $payGiveStatus;
            }
        }

        //验证当前用户日参与次数、总参与次数、日中奖次数、总中奖次数是否合法，如果合法，更新用户日/总参与次数
        $verifyPersonDrawLotteryRuleAndUpdateStatus = $this->verifyPersonDrawLotteryRuleAndUpdate();
        if ($verifyPersonDrawLotteryRuleAndUpdateStatus !== true) {
            return $verifyPersonDrawLotteryRuleAndUpdateStatus;
        }

        $staticAwardList = $this->getStaticAwardList();

        return $this->doDrawLottery($staticAwardList);
    }

    /**
     * @param $marketingInfo
     *
     * @return bool|string
     */
    private function verifyMemberFlag($marketingInfo)
    {
        if ($marketingInfo['member_join_flag'] == self::MEMBER_JOIN_RESTRICTION) { //限制会员
            if ($marketingInfo['join_mode'] == self::WECHAT_JOIN) { // 微信参与
                if (empty($this->WxUserModel)) {
                    $this->WxUserModel = D('WxUser');
                }
                $memberInfo = $this->WxUserModel->getBySk($this->node_id, $this->phone_no);
                if (!$memberInfo || (!isset($memberInfo['subscribe']) || $memberInfo['subscribe'] == 0)) {
                    return $this->returnErrorByErrno(1010);
                }
                // 判断营销活动上的会员等级
                if ($marketingInfo['member_batch_id'] != '-1') { // 需要校验会员参与范围
                    if (!in_array($memberInfo['group_id'], $marketingInfo['member_batch_id'])) {
                        $this->saveCjTrace(array(), 1, '不能参与该活动');
                        return $this->returnErrorByErrno(1012);
                    }
                }
                if ($marketingInfo['member_batch_award_id'] != '-1') { // 需要校验会员中奖范围
                    if (!in_array($memberInfo['group_id'], $marketingInfo['member_batch_award_id'])) {
                        $this->saveCjTrace(array(), 1, '该会员不能中该奖品');
                        return $this->returnErrorByErrno(1009);
                    }
                }
            } else {  // 手机号码参与
                $checkJoinRangeMarketingStatus = $this->checkJoinRangeMarketing($marketingInfo);
                if ($checkJoinRangeMarketingStatus !== true) {
                    return $checkJoinRangeMarketingStatus;
                }

                $checkAwardRangeMarketingStatus = $this->checkAwardRangeMarketing($marketingInfo);
                if ($checkAwardRangeMarketingStatus !== true) {
                    return $checkAwardRangeMarketingStatus;
                }
            }
        }

        return true;
    }

    /**
     *
     */
    private function addBehaviorType()
    {
        if (empty($this->MemberBehaviorModel)) {
            $this->MemberBehaviorModel = D('MemberBehavior');
        }
        $flag = $this->MemberBehaviorModel->addBehaviorType($this->memberId, $this->node_id, 1, '', $this->batch_id);
        if ($flag === false) {
            $this->log("===MEM_DEBUG===记录会员行为数据[抽奖流水]member_id[{$this->memberId}],node_id[{$this->node_id}],1");
        }
    }

    /**
     * @param $staticAwardList
     *
     * @return mixed
     */
    private function doDrawLottery($staticAwardList)
    {
        $awardRandom = mt_rand(1, 100);
        if ($awardRandom <= $this->total_rate) { //可以中奖
            $icount     = 0;
            $awardArray = [];
            $maxRandom  = 0;
            $lowNum     = 0;
            $retCode    = [];
            foreach ($staticAwardList as &$staticAward) { //获取有效奖品数
                //校验奖品日中奖次数、奖品总中奖次数、奖品storage
                $rs = $this->checkAwardCountRule($staticAward);
                if ($rs === true) {
                    $maxRandom                = $lowNum + $staticAward['award_rate'];
                    $awardRange               = [];
                    $awardRange['award_info'] = $staticAward;
                    $awardRange['low_num']    = $lowNum;
                    $awardRange['max_num']    = $maxRandom;

                    $awardArray[$icount] = $awardRange;

                    $lowNum = $maxRandom;
                    $icount++;
                } else {
                    if (isset($retCode[$rs['code']])) {
                        $retCode[$rs['code']] += 1;
                    } else {
                        $retCode[$rs['code']] = 1;
                    }
                }
            }
            $rs = true;
            if ($icount == 0) {
                if (isset($retCode['1002'])) {
                    $this->saveCjTrace($staticAwardList['0'], 1, '当日奖品已发完');
                    return $this->returnErrorByErrno(1002);
                }
                $this->saveCjTrace($staticAwardList['0'], 1, '所有奖品已发完');
                return $this->returnErrorByErrno(1001);
            } else { // 到这里应该必中奖了，接下来是中什么奖品的逻辑
                $awardRandom = mt_rand(1, $maxRandom);

                for ($inum = 0; $inum < $icount; $inum++) {
                    $awardDetail = $awardArray[$inum];
                    if ($awardRandom > $awardDetail['low_num'] && $awardRandom <= $awardDetail['max_num']) { //中奖
                        $staticAward = $awardDetail['award_info'];

                        $cjCate                         = $this->getCjCate($staticAward['cj_cate_id']);
                        $staticAward['member_batch_id'] = isset($cjCate['member_batch_id']) ? $cjCate['member_batch_id'] : false;
                        if ($staticAward['member_batch_id']) { // 需要校验会员中奖范围
                            $checkAwardRangeStatus = $this->checkAwardRange($staticAward);
                            if ($checkAwardRangeStatus !== true) {
                                return $checkAwardRangeStatus;
                            }
                        }
                        // 校验tbatch_info 库存
                        $this->b_id = $staticAward['b_id'];
                        $goodsInfo  = $this->getGoodsInfo($staticAward['goods_id']);
                        $this->g_id = $goodsInfo['id'];

                        //扣库存
                        $ret = $this->decreaseStorageAndSpecialTimes($this->batch_id, 1, $staticAward);
                        if ($ret !== true) {
                            return $ret;
                        }

                        $dynamicAwardInfo = $this->getDynamicAwardInfo($staticAward['b_id']);
                        // 奖品类型： 1 电子券 2 卡券 3 红包 4 积分奖品 5 平安币 6 微信红包
                        $prizeType = 1;
                        // 判断是否微信卡券
                        if ($dynamicAwardInfo['card_id'] != null) {
                            $prizeType         = 2;
                            $this->send_mode   = '1';
                            $this->send_mobile = $this->wx_open_id;
                        } else {
                            $this->send_mode = '0';
                        }

                        // 付满送业务中奖数累加
                        if ($this->pay_token != null) {
                            $this->pay_give_stat();
                        }

                        if ($dynamicAwardInfo['batch_class'] == '12') { //定额红包
                            $prizeType       = 3;
                            $bonusDealStatus = $this->bonusDeal($dynamicAwardInfo);
                            if ($bonusDealStatus !== true) {
                                return $bonusDealStatus;
                            }
                            $staticAward['send_type'] = '1';
                        } else if ($dynamicAwardInfo['batch_class'] == '14') { //积分类商品
                            $prizeType          = 4;
                            $integralDealStatus = $this->integralDeal($dynamicAwardInfo, $staticAward);
                            if ($integralDealStatus !== true) {
                                return $integralDealStatus;
                            }
                            $staticAward['send_type'] = '1';
                        } else if ($dynamicAwardInfo['batch_type'] == '6' || $dynamicAwardInfo['batch_type'] == '7') { //微信红包和翼码代理红包
                            $staticAward['send_type'] = '1';
                            $prizeType                = 6;

                            $requestId = $this->save_request_id;
                            $rs = $this->saveSendAwardTrace($this->phone_no,'2',$staticAward,$requestId,'','1','1');
                            if ($rs !== true) {
                                return $rs;
                            }
                        }

                        if ($staticAward['send_type'] == '0') { // 为0 才下发
                            $sendAwardStatus = $this->send_award($staticAward);
                            if ($sendAwardStatus !== true) {
                                return $sendAwardStatus;
                            }
                        }

                        $this->saveCjTrace($staticAward, 2, '恭喜,中奖了');

                        return $this->returnSuccess(
                                '恭喜,中奖了',
                                [
                                        'rule_id'             => $staticAward['id'],
                                        'goods_id'            => $staticAward['goods_id'],
                                        'batch_type'          => $staticAward['batch_type'],
                                        'cj_batch_id'         => $staticAward['id'],
                                        'batch_no'            => $staticAward['activity_no'],
                                        'award_origin'        => $staticAward['award_origin'],
                                        'award_level'         => $staticAward['award_level'],
                                        'card_ext'            => $this->wx_card_info['card_ext'],
                                        'card_id'             => $this->wx_card_info['card_id'],
                                        'request_id'          => $this->request_id,
                                        'cj_trace_id'         => $this->cj_trace_id,
                                        'cate_id'             => $staticAward['cj_cate_id'],
                                        'online_verify_flag'  => $this->getOnlineVerifyFlag($dynamicAwardInfo),
                                        'bonus_use_detail_id' => $this->bonus_use_detail_id,
                                        'prize_type'          => $prizeType,
                                        'batch_class'         => $dynamicAwardInfo['batch_class'],
                                        'member_id'           => $this->memberId,
                                        'member_phone'        => $this->memberInfo ? $this->memberInfo['phone_no'] : '',
                                        'integral_get_flag'   => $prizeType == 4 && $this->memberId ? 1 : 0,
                                        'integral_get_id'     => $this->integral_get_id,
                                ],
                                'EVAL',
                                true
                        );
                    }
                }
                return $rs;
            }
        } else { //未中奖
            $this->saveCjTrace($staticAwardList['0'], 1, '未中奖');
            return $this->returnErrorByErrno(1000);
        }
    }

    /**
     * 个人日中奖次数、个人总中奖次数、当前奖品日中奖次数、当前奖品总中奖次数
     *
     * @param $mId
     * @param $storage
     * @param $staticAwardInfo
     *
     * @return mixed|null
     */
    private function decreaseStorageAndSpecialTimes($mId, $storage, $staticAwardInfo)
    {
        $bId         = get_val($staticAwardInfo, 'id', 0);
        $scriptFiles = [
                $this->getLuaFilePath('tonumber_with_default.lua'),
                $this->getLuaFilePath('decrease_storage_and_upadate_special_times.lua'),
        ];
        $lockName    = 'decreaseStorageAndSpecialTimes:' . $bId;
        $identifier  = $this->RedisHelper->acquireLockWithTimeoutByLua($lockName, 10, 20);//添加锁
        $ret         = 0;
        if ($identifier) {
            $personId = $this->getPersonId();
            $ret      = $this->RedisHelper->loadScriptWithAliasAndExec(
                    ['alias' => 'decreaseStorageAndSpecialTimes', 'luaFiles' => $scriptFiles],
                    [
                            $this->generateMainKey($mId),
                            $mId,
                            $personId,
                            $bId,
                            $this->currentDateStr,
                            $this->currentDate24HLeftSeconds
                    ],
                    [-$storage]
            );
            $this->RedisHelper->releaseLockByLua($lockName, $identifier);//释放锁
        }

        if ($ret != 1) {
            switch ($ret) {
                case 2: //个人当日中奖次数达到上限
                    $msg   = '个人当日中奖次数达到上限';
                    $errno = 1003;
                    break;
                case 3: //个人总中奖次数达到上限
                    $msg   = '个人总中奖次数达到上限';
                    $errno = 1014;
                    break;
                case 4: //奖品日中奖次数达到上限
                    $errno = 1018;
                    $msg   = '奖品日中奖次数达到上限';
                    break;
                case 5: //奖品总中奖次数达到上限
                    $errno = 1019;
                    $msg   = '奖品总中奖次数达到上限';
                    break;
                case 6: //奖品库存不足
                    $errno = 1020;
                    $msg   = '奖品库存不足';
                    break;
                case 0: //获取锁失败
                    $errno = 1202;
                    $msg   = '库存更新失败(1202)';
                    break;
                default:
                    $errno = 1201;
                    $msg   = '库存更新失败';
            }
            $this->saveCjTrace($staticAwardInfo, 1, $msg);
            return $this->returnErrorByErrno($errno, $staticAwardInfo);
        } else {//成功的时候 才会扣
            //扣db中的库存
            $currentStorgeNum = $this->getDynamicStorageNum($mId, $staticAwardInfo['id']);
            M('tbatch_info')->where(['id' => $staticAwardInfo['b_id']])->save(['remain_num' => $currentStorgeNum]);
        }

        return true;
    }

    /**
     * @param $mId
     * @param $id
     *
     * @return int
     */
    private function getDynamicStorageNum($mId, $id)
    {
        $key        = $this->getDynamicStorageNumKey($id);
        $storageNum = $this->RedisHelper->hGet($this->generateMainKey($mId), $key);
        return (int)$storageNum;
    }

    /**
     * 检查基础信息正确性
     */
    private function check()
    {
        if ($this->batch_id == '') {
            return $this->returnError('抽奖id不能为空！');
        }
        if ($this->day_win_times == '') {
            return $this->returnError('单号码每日中奖数不能为空！');
        }
        if ($this->phone_no == '' && $this->wx_open_id == '') {
            return $this->returnError('手机号和wx_open_id不能同时为空！');
        }
        if ($this->label_id == '') {
            return $this->returnError('标签ID不能为空！');
        }
        if ($this->channel_id == '') {
            return $this->returnError('渠道不能为空！');
        }
        if ($this->batch_type == '') {
            return $this->returnError('营销活动类型为空！');
        }
        if ($this->cj_rule_id == '') {
            return $this->returnError('抽奖规则id为空！');
        }
        if ($this->total_rate == '') {
            return $this->returnError('总中奖率不能为空！');
        }
        return true;
    }

    /**
     * @param array $marketingInfo 获取会员信息
     */
    private function getMemberInfo($marketingInfo)
    {
        if (is_null($this->memberInfo)) {
            $this->MemberInstallModel = D('MemberInstall', 'Model');
            $condition                = $this->phone_no;
            $conditionType            = 1; // 1手机 2翼码授权openid 3商户授权openid
            $autoAdd                  = true;
            if ($marketingInfo['join_mode'] == self::WECHAT_JOIN) {
                if ($marketingInfo['batch_type'] == '45') {// 劳动最光荣
                    $conditionType = $marketingInfo['member_join_flag'] == '0' ? 2 : 3;
                } else if (in_array($marketingInfo['batch_type'], array('56', '59',))) { // 双旦和升旗手
                    $configData    = unserialize($marketingInfo['config_data']);
                    $conditionType = $configData['wx_auth_type'] == '0' ? 2 : 3;
                } else {
                    $conditionType = 3;
                }
                $autoAdd = false;
            }

            $option = array('channel_id' => $this->channel_id, 'batch_id' => $this->batch_id);
            $result = $this->MemberInstallModel->wxTermMemberFlag(
                    $this->node_id,
                    $condition,
                    $conditionType,
                    $autoAdd,
                    $option
            );
            if ($autoAdd == false && $result === false) {
                $this->log("===MEM_DEBUG===get_member_info 微信参与，不产生会员数据");
                return;
            }

            if ($result === false) {
                $this->log("getMemberInfo fail! node_id[{$this->node_id}] condition[{$condition}]");
            } else {
                $this->memberInfo = $result;
                $this->memberId   = $result['id'];
            }
        }
    }
}