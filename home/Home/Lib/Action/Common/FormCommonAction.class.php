<?php

/**
 * 抽奖活动贵公用类 PS 正确的使用该方法，需要再session里面 设置 DLCommonMobile （用于唯一标示当前用户的 PS：
 * 如果以微信的方式登录 mobile为 openid；以手机号的方式抽奖，为抽奖手机号）
 *
 * @author Jeff Liu<liuwy@imageco.com.cn> Class DrawLotteryCommonAction @time
 */
class FormCommonAction extends BaseAction {

    /**
     *
     * @var FormCommonService
     */
    public $FormCommonService;

    public function _initialize() {
        parent::_initialize();
        $this->FormCommonService = D('FormCommon', 'Service');
    }

    /**
     * 保存表单数据
     */
    public function submit() {

        $data    = array();
        $where   = array();
        $result  = $this->FormCommonService->saveForm($data, $where);
        $result1 = $this->FormCommonService->saveForm($data, $where);
    }
}
