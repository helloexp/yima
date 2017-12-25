<?php

/**
 *
 * @author Jeff Liu<liuwy@imageco.com.cn> Class DrawLotteryCommonAction @time
 */
class TopNInfoAction extends BaseAction
{

    /**
     *
     * @var TopNInfoService
     */
    public $TopNInfoService;

    public $needCheckLogin = false;
    protected $needCheckUserPower = false;

    public function _initialize()
    {
        parent::_initialize();
        $this->TopNInfoService = D('TopNInfo', 'Service');
    }

    /**
     * @return string
     */
    public function getTopOneUrl()
    {
        $return = $this->TopNInfoService->getTopN();
        echo $return;
        exit;
    }

    public function getTop10Id()
    {
        import('@.Vendor.RankHelper');
        $RankHelper = RankHelper::getInstance();
        $list = $RankHelper->getTop10();
        echo json_encode($list);exit;
    }
}
