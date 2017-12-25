<?php

/**
 * 抽奖
 *
 * @author Jeff Liu<liuwy@imageco.com.cn>
 */
class DrawLotteryCommonService
{

    /**
     * 微信卡券没有领取状态
     */
    const WECHAT_CARD_UNFETCHED = 1;

    const INTEGAL_UNFETCHED = 0;

    /**
     *
     * @var WeiXinCardService
     */
    private $WeiXinCardService;

    /**
     * @var GetRealUrlService
     */
    protected $GetRealUrlService;

    /**
     * @var BatchChannelRedisModel
     */
    private $BatchChannelRedisModel;

    private $LocalCache = array();

    /**
     * 获得微信Service
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @return Model|WeiXinCardService
     */
    public function getWeiXinCardService()
    {
        if (empty($this->WeiXinCardService)) {
            $this->WeiXinCardService = D('WeiXinCard', 'Service');
        }

        return $this->WeiXinCardService;
    }

    /**
     *
     */
    private function initMap()
    {
        $this->mapping = array(
                '1' => U(''),
        );
    }

    /**
     * 格式化奖品列表 方便tpl展示
     *
     * @param $awardList
     * @param $wechatCard
     *
     * @return array
     */
    public function formatAwardList($awardList, $wechatCard)
    {
        $finalAwardList = array();
        if ($awardList && is_array($awardList)) {
            $currentTime = date('YmdHis');
            log_write(json_encode($awardList));
            foreach ($awardList as $index => $award) {
                $aid                 = $award['twx_assist_number_id'] ? $award['twx_assist_number_id'] : 0;
                $award['showStatus'] = 0;
                if ($award['goods_type'] == CommonConst::GOODS_TYPE_HB) { // 红包
                    $phone = isset($award['phone']) ? $award['phone'] : '';
                    if ($currentTime >= $award['bonus_end_time']) { // 已过期
                        $award['showStatus'] = 'bonusExpire'; // 已过期
                    } else if (!strlen($phone) == 11 || !is_numeric($phone)) { // phone不是手机号，还没有被领取
                        $award['showStatus'] = 'bonusNonReceived'; // 未领取
                    } else if ($award['bonus_num'] > $award['bonus_use_num']) {
                        $award['showStatus'] = 'gotoUseBonus'; // 去使用
                    } else { // 已使用
                        $award['showStatus'] = 'bonushasUsed'; // 已使用
                    }
                    $endTimeStr                      = $award['bonus_end_time'];
                    $awardList['userpoint_end_time'] = $this->formateDate($endTimeStr);
                } else if ($award['card_id']) { // 微信卡券
                    if ($award['wx_status'] == DrawLotteryCommonService::WECHAT_CARD_UNFETCHED) { // 没有领取
                        $award['showStatus'] = 'wechatCardNonReceived'; // 没有领取
                    } else {
                        $award['showStatus'] = 'wechatCardHasReceived'; // 已经领取
                    }
                    $award['wxcardinfo']             = json_encode(
                            array(
                                    'card_id'  => $award['card_id'],
                                    'card_ext' => $wechatCard[$aid]['card_ext'],
                            )
                    );
                    $endTimeStr                      = $award['userpoint_end_time'];
                    $awardList['userpoint_end_time'] = $this->formateDate($endTimeStr);
                } else if ($award['goods_type'] == CommonConst::WECHAT_HONGBAO_GOODS_TYPE || $award['source'] == CommonConst::SOURCE_FROM_WECHAT || $award['source'] == CommonConst::SOURCE_FROM_IMAGECO_AGENT) { // 微信红包
                    $award['showStatus'] = 'wechatHongbaoHasReceived'; // 已经领取
                } else if ($award['integal_get_id']) { // 积分
                    if ($award['integal_status'] == DrawLotteryCommonService::INTEGAL_UNFETCHED) { // 没有领取
                        $award['showStatus'] = 'integalNonReceived';
                    } else {
                        $award['showStatus'] = 'integalHasReceived'; // 已经领取
                    }
                    $award['link_url']               = U(
                            'Label/Member/index',
                            array('node_id' => $award['integal_node_id']),
                            false,
                            false,
                            true
                    );
                    $awardList['userpoint_end_time'] = $this->formateDate('');
                } else { // 电子券
                    if ($award['send_mobile'] == '13900000000') { // 未领取
                        $award['showStatus'] = 'couponNonReceived'; // 未领取
                    } else {
                        $award['showStatus'] = 'couponHasReceived'; // 已经领取
                    }
                    $endTimeStr                      = $award['userpoint_end_time'];
                    $awardList['userpoint_end_time'] = $this->formateDate($endTimeStr);
                }
                $finalAwardList[$index] = $award;
                unset($award, $index);
            }
        }

        return $finalAwardList;
    }

    /**
     * @param $endTimeStr
     *
     * @return string
     */
    private function formateDate($endTimeStr)
    {
        $timeStr = '';
        if ($endTimeStr) {
            $pattern = '|(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})|';
            $matches = array();
            preg_match_all($pattern, $endTimeStr, $matches);
            $timeStr = sprintf(
                    '%d-%d-%d %d:%d:%d',
                    $matches[1],
                    $matches[2],
                    $matches[3],
                    $matches[4],
                    $matches[5],
                    $matches[6]
            );
        }

        return $timeStr;
    }

    /**
     *
     * @param int        $labelId    id
     * @param array      $wechatInfo 微信信息
     * @param string|int $mobile     手机号|openid
     */
    public function setMobileAndGobackUrl($labelId, $wechatInfo, $mobile)
    {
        $mobileSessionKey = $this->generateMobileSessionKey($labelId);

        if (issetAndNotEmpty($wechatInfo, 'openid')) {
            $mobileSessionValue = $wechatInfo['openid'];
        } else if (issetAndNotEmpty($wechatInfo, 'wx_open_id')) {
            $mobileSessionValue = $wechatInfo['wx_open_id'];
        } else {
            $mobileSessionValue = $mobile;
        }

        session($mobileSessionKey, $mobileSessionValue);
        cookie($mobileSessionKey, $mobileSessionValue, time() + 2592000);
    }

    public function mappingGobackUrl($labelId)
    {
        if (empty($this->GetRealUrlService)) {
            $this->GetRealUrlService = D('GetRealUrl', 'Service');
        }

        return $this->GetRealUrlService->getRealUrl($labelId);
    }

    /**
     *
     * @param int $id
     *
     * @return string
     */
    public function generateMobileSessionKey($id)
    {
        return 'DLCommonMobile:' . $id;
    }

    /**
     *
     * @param int $id
     *
     * @return string
     */
    public function generateMobileCookieKey($id)
    {
        return 'wcDlCommonMobile_' . $id;
    }

    public function generateGobackUrlSessionKey($id)
    {
        return 'gobackLink:' . $id;
    }

    public function generateMobileSessionKeyForAwardList($id)
    {
        return 'DLALCommonMobile:' . $id;
    }

    public function generateMobileCookieKeyForAwardList($id)
    {
        return 'DLALCM_' . $id;
    }

    public function setMobileForAwardList($id, $mobile)
    {
        $sessionKey = $this->generateMobileSessionKeyForAwardList($id);
        session($sessionKey, $mobile);
        $cookieKey = $this->generateMobileCookieKeyForAwardList($id);
        cookie($cookieKey, $mobile, ['expire' => '31536000',]);
        return true;
    }

    /**
     * @param int $id
     *
     * @return mixed
     */
    public function getMobileForAwardList($id)
    {
        $sessionKey = $this->generateMobileSessionKeyForAwardList($id);
        $value      = session($sessionKey);
        if (empty($value)) {
            $cookieKey = $this->generateMobileCookieKeyForAwardList($id);
            $value     = cookie($cookieKey);
        }
        if (empty($value)) {//兼容老的方式。 过一段时间需要删除。。
            $value = $this->getMobileForAwardListOld($id);
        }
        return $value;
    }

    /**
     *
     * @deprecated
     *
     * @param int $id
     *
     * @return mixed
     */
    public function getMobileForAwardListOld($id)
    {
        $sessionCookieKey = $this->generateMobileSessionKey($id);
        $value            = session($sessionCookieKey);
        if (empty($value)) {
            $value = cookie($this->generateMobileCookieKey($id));
        }

        return $value;
    }

    /**
     *
     * @param int $id
     *
     * @return mixed|string
     */
    public function getGobackUrlForAwardList($id)
    {
        $gobackLinkKey = $this->generateGobackUrlSessionKey($id);
        $value         = session($gobackLinkKey);
        log_write(__METHOD__ . ' HTTP_REFERER:' . $_SERVER['HTTP_REFERER']);
        if (empty($value)) {
            $value = $this->mappingGobackUrl($id);
            if (empty($value)) {
                $default = U('Label/News/index', ['id' => $id, 'wechat_card_js' => 1,]);
                $value   = get_val($_SERVER, 'HTTP_REFERER', $default);
            }
        }
        return $value;
    }

    /**
     * 获得id（唯一标示，可能为mobile，也可能是openid）
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param      $id
     * @param bool $cookieOnly
     *
     * @return array|mixed|null|string
     */
    public function getDrawLotteryMobile($id, $cookieOnly = false)
    {
        if ($cookieOnly) {
            $mobile = null;
        } else {
            $mobile = I('post.mobile', I('post.phone'));
        }

        if (empty($mobile)) {
            $key       = $this->generateDrawLotteryMobileCookieKey($id);
            $cookieUid = cookie($key);
            // 微信登录过
            if ($cookieUid) {
                $mobile = $cookieUid;
            }
            if ((!is_numeric(trim($mobile)) || !strlen(trim($mobile)) == 11)) { // 手机号
                return '';
            }
        }

        return $mobile;
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param $id
     *
     * @return string
     */
    public function generateDrawLotteryMobileCookieKey($id)
    {
        return '_drawLotteryMobile_' . $id;
    }

    /**
     * 获得未领取卡券列表
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param $awardList
     *
     * @return array|bool
     */
    public function getUnfetchedWechatCard($awardList)
    {
        $unfetchedWechatCardList = array();
        if (is_array($awardList) && $awardList) {
            foreach ($awardList as $award) {
                $id = $award['twx_assist_number_id'];
                if ($award['card_id'] && $award['wx_status'] == self::WECHAT_CARD_UNFETCHED) {
                    $unfetchedWechatCardList[$id] = array(
                            'id'           => $id,
                            'assistNumber' => $award['assist_number'],
                            'nodeId'       => $award['node_id'],
                            'cardBatchId'  => $award['card_batch_id'],
                            'cardId'       => $award['card_id'],
                    );
                }
            }
        }
        $finalUnfetchedWechatCardList = array();
        if ($unfetchedWechatCardList) {
            $finalUnfetchedWechatCardList = $this->getWeiXinCardService()->batchGenerateCardExt(
                    $unfetchedWechatCardList
            );
        }

        return $finalUnfetchedWechatCardList;
    }
}