<?php

/**
 * 抽奖
 *
 * @author Jeff Liu<liuwy@imageco.com.cn>
 */
class WechatUserInfoCommonService {

    /**
     *
     * @var DrawLotteryModel
     */
    public $DrawLotteryModel;

    public function wechatUserinfo($nodeId) {
        $userWechartDetailInfo = $this->getUserWechatDetailInfo($nodeId);
        if (empty($userWechartDetailInfo)) {
            $userWechartDetailInfo = $this->initUserWechatDetailInfo($nodeId);
        }
        return $userWechartDetailInfo;
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $nodeId
     * @return array
     */
    public function initUserWechatDetailInfo($nodeId) {
        $userWechatBaseInfo = $this->getUserWechatBaseInfo($nodeId);
        $openid = isset($userWechatBaseInfo['openid']) ? $userWechatBaseInfo['openid'] : '';
        $where = array(
            'openid' => $openid, 
            'node_id' => $nodeId, 
            'subscribe' => array(
                'neq', 
                '0'));
        if (empty($this->DrawLotteryModel)) {
            $this->DrawLotteryModel = D('DrawLottery');
        }
        $userInfo = $this->DrawLotteryModel->getWxUser($where);
        if (empty($userInfo)) {
            $userInfo = array();
        }
        $userWechartDetailInfo = array_merge($userInfo, $userWechatBaseInfo);
        $this->setUserWechatDetailInfo($nodeId, $userWechartDetailInfo);
        return $userWechartDetailInfo;
    }

    public function getUserWechatDetailInfo($nodeId) {
        return session('node_userwechat_' . $nodeId);
    }

    public function setUserWechatDetailInfo($nodeId, $userWechatDetailInfo) {
        session('node_userwechat_' . $nodeId, $userWechatDetailInfo);
    }

    public function getUserWechatBaseInfo($nodeId) {
        return session('node_wxid_' . $nodeId);
    }

    public function setUserWechatBaseInfo($nodeId, $baseInfo) {
        session('node_wxid_' . $nodeId, $baseInfo);
    }
}