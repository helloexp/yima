<?php

/**
 * 抽奖活动贵公用类 PS 正确的使用该方法，需要再session里面 设置 DLCommonMobile （用于唯一标示当前用户的 PS：
 * 如果以微信的方式登录 mobile为 openid；以手机号的方式抽奖，为抽奖手机号）
 *
 * @author Jeff Liu<liuwy@imageco.com.cn> Class DrawLotteryCommonAction @time
 *         2015-12-14
 */
class DrawLotteryCommonAction extends DrawLotteryBaseAction
{

    public $DLCommonMobile = null;
    // 用于唯一标示当前用户的 PS： 如果以微信的方式登录 mobile为 openid；以手机号的方式抽奖，为抽奖手机号

    /**
     *
     * @var DrawLotteryCommonService
     */
    public $DrawLotteryCommonService;

    public function _initialize()
    {
        parent::_initialize();

        $this->DrawLotteryCommonService = D('DrawLotteryCommon', 'Service');
        $this->DLCommonMobile           = $this->DrawLotteryCommonService->getMobileForAwardList($this->id);
    }

    /**
     * 查看抽奖记录
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     */
    public function awardList()
    {
        $this->cacheControl();
        $awardList = $this->DrawLotteryModel->getAwardList(
                [
                        'mobile'     => $this->DLCommonMobile,
                        'batch_id'   => $this->marketInfo['id'],
                        'batch_type' => $this->marketInfo['batch_type'],
                ]
        );

        $wechatCard     = $this->DrawLotteryCommonService->getUnfetchedWechatCard($awardList);
        $finalAwardList = $this->DrawLotteryCommonService->formatAwardList($awardList, $wechatCard);
        $gobackLink = I('gobackLink', null); // 抽奖跳转地址
        if (empty($gobackLink)) {
            $gobackLink = $this->DrawLotteryCommonService->getGobackUrlForAwardList($this->id);
        }
        $tpl      = '';
        $festival = I('get.festival');
        if ($festival) {
            $tpl = './Home/Tpl/DrawLottery/DrawLotteryCommon_festival_awardList.html';
        }
        $shareData = array();
        if (isFromWechat()) { //微信浏览器过来，需要构建分享信息
            $wechatInfo = M('tweixin_info')->where(['node_id' => $this->node_id,])->find();
            $shareData  = $this->generateWechatShareDataByAppIdAndSecret(
                    $wechatInfo['app_id'],
                    $wechatInfo['app_secret'],
                    $this->marketInfo,
                    $gobackLink
            );
        }

        $this->assign('wechartShareData', count($shareData) == 0 ? '' : json_encode($shareData));
        $this->assign('id', $this->id);
        $this->assign('awardList', $finalAwardList);
        $this->assign('id', $this->id);
        $this->assign('gobackLink', $gobackLink);
        $this->display($tpl);
    }
}
