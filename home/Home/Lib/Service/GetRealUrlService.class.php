<?php

class GetRealUrlService
{

    /**
     * @var BatchChannelRedisModel
     */
    protected $BatchChannelRedisModel;

    /**
     * @var MarketingInfoRedisModel
     */
    protected $MarketingInfoRedisModel;

    public function getRealUrl($labelId, $salerId = null)
    {
        if (empty($this->BatchChannelRedisModel)) {
            $this->BatchChannelRedisModel = D('BatchChannelRedis');
        }
        $result = $this->BatchChannelRedisModel->getByPk($labelId);
        $result = isset($result[$labelId]) ? $result[$labelId] : $result;
        //平安营销活动全部跳转至旺财保险行业版
        if ($result) {
            $bParam = ['node_id' => $result['node_id']];
            B('BxWcRedirect', $bParam);
        }
        //判断是否推广方式访问
        if ('store_in' != session('login_type' . $result['node_id'])) {
            //获取新电商配置
            $result = $this->getEcshopChannel($result);
        }
        $config_url_arr = C('BATCH_WAP_URL');
        $url            = $config_url_arr[$result['batch_type']];
        if ($result['batch_type'] == CommonConst::BATCH_TYPE_WEEL) { //大转盘抽奖特殊处理
            if (empty($this->MarketingInfoRedisModel)) {
                $this->MarketingInfoRedisModel = D('MarketingInfoRedis');
            }
            $marketingInfo = $this->MarketingInfoRedisModel->getBySk($result['node_id'], $result['batch_id']);
            if (isset($marketingInfo['new_cj_flag']) && $marketingInfo['new_cj_flag'] == 1) {
                $url = U('DrawLottery/NewSpinTurnplate/index');
            }
        }
        // 所有字段
        $_GET['wechat_card_js'] = 1;
        $tz_arr                 = I('get.');
        if (!$url) {
            return false;
        }
        $fromPoster = I('get.fromPoster'); // 海报会有这个值
        if ($fromPoster) {
            $fromUrl    = $_SERVER["HTTP_REFERER"];
            $fromUrl    = str_replace('http://', '', $fromUrl);
            $fromUrlArr = explode('/', $fromUrl);
            if ($fromUrlArr[0] == str_replace(['http://', '/'], '', C('CURRENT_DOMAIN'))) {
                $params = explode('&', $fromUrlArr[1]);
                foreach ($params as $v) {
                    $paramValue = explode('=', $v);
                    if (isset($paramValue[0]) && $paramValue[0] == 'id') {
                        if (empty($tz_arr['full_id'])) {
                            $tz_arr['full_id'] = $paramValue[1];
                        } else {
                            $tz_arr['full_id'] = $tz_arr['full_id'] . ',' . $paramValue[1];
                        }
                        break;
                    }
                }
            }
        }
        if (empty($salerId)) {
            $salerId = cookie('saler_id');
        }
        $tz_arr['saler_id'] = $salerId;
        return U($url, $tz_arr);
    }

    /**
     * 获取电商配置
     *
     * @author John zeng<zengc@imageco.com.cn>
     *
     * @param string $mId 商品 ID
     *
     * @return array $channleId
     */
    public function getEcshopChannel($info)
    {
        if ('31' == $info['batch_type']) {
            if (empty($this->MarketingInfoRedisModel)) {
                $this->MarketingInfoRedisModel = D('MarketingInfoRedis');
            }
            $marketingInfo = $this->MarketingInfoRedisModel->getBySk($info['node_id'], $info['batch_id']);
            $marketConfig = get_val($marketingInfo, 'config_data');
            $marketConfig = unserialize($marketConfig);
            //推广广告主页
            if (isset($marketConfig['isShowAd']) && 1 === $marketConfig['isShowAd']) {
                $info['batch_type'] = '1026';
            }
            //推广电子海报主页
            if (isset($marketConfig['isShowElAd']) && 1 === $marketConfig['isShowElAd']) {
                $info['batch_type'] = '1027';
            }
        }
        return $info;
    }

}