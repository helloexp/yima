<?php

/*
 * 校验渠道有效性 @auther 徐应松 @last edit by tr @
 */

class CheckStatus
{

    public $resp_msg = '';

    public $batchChannelInfo = array();
    // 标签信息
    public $channelInfo = array();
    // 渠道信息
    public $marketInfo = array();
    // 活动信息
    public $nodeId = '';

    /**
     * @var AuthAccessService
     */
    public $AuthAccessService;

    public function __construct()
    {
        import('@.Service.TipsInfoService');
        $this->AuthAccessService = D('AuthAccess', 'Service');
    }

    // 获取标签详情
    public function checkId($id)
    {
        $model                  = M('tbatch_channel');
        $map                    = ['id' => $id];
        $this->batchChannelInfo = $result = $model->where($map)->find();
        //log_write('手机端查询渠道是否存在:'.M()->_sql());    //如不再使用请别开放，造成错乱信息，请在需要提示错误的地方输出
        if (!$result) {
            $this->resp_msg = TipsInfoService::getMessageInfoErrorSoftTxtByNo(-1038);
            return $this->resp_msg;
        }
        $this->nodeId = $result['node_id'];

        //平安营销活动全部跳转至旺财保险行业版---begin
        $bParam = ['node_id' => $this->nodeId];
        B('BxWcRedirect', $bParam);
        //平安营销活动全部跳转至旺财保险行业版---end

        if ($result['status'] != 1) {
            $this->resp_msg = TipsInfoService::getMessageInfoErrorSoftTxtByNo(-1037);
            return $this->resp_msg;
        }
        // 查询渠道
        $chMap = ['node_id' => $result['node_id'], 'id' => $result['channel_id']];

        $this->channelInfo = $channelInfo = M('tchannel')->where($chMap)->find();
        if ($channelInfo['status'] != '1') {
            $this->resp_msg = TipsInfoService::getMessageInfoErrorSoftTxtByNo(-1037);
            return $this->resp_msg;
        }
        // 外链的时候
        if ($result['batch_id'] == 0 && $result['batch_type'] == 0 && !empty($channelInfo['go_url'])) {
            $this->marketInfo = [
                    'id'           => $result['batch_id'],
                    'batch_type'   => $result['batch_type'],
                    'redirect_url' => $channelInfo['go_url']
            ];
            return true;
        }

        // 查询活动
        $map = ['id' => $result['batch_id'], 'batch_type' => $result['batch_type']];

        $this->marketInfo = $marketInfo = M('tmarketing_info')->where($map)->find();

        if (!$marketInfo) {
            $this->resp_msg = TipsInfoService::getMessageInfoErrorSoftTxtByNo(-1024);
            return $this->resp_msg;
        }

        // 查询该渠道是否为首页渠道，如果首页渠道不用校验
        if ($channelInfo['sns_type'] == '13' && $channelInfo['type'] == '1') {
            return true;
        }

        // 判断是O2O案例渠道的，活动状态停用可访问，渠道取消不可访问
        if ($channelInfo['sns_type'] == '12') {
            return true;
        }

        if ($marketInfo['status'] == '2') {
            $this->resp_msg = TipsInfoService::getMessageInfoErrorSoftTxtByNo(-1038);
            return $this->resp_msg;
        }

        if ($marketInfo['is_halt'] == '1') {
            $this->resp_msg = TipsInfoService::getMessageInfoErrorSoftTxtByNo(-1038);
            return $this->resp_msg;
        }

        $previewChannelId = D('Channel')->getPreviewChannelId($this->nodeId); // 当前机构的预览渠道
        $channelId        = $this->batchChannelInfo['channel_id'] ? $this->batchChannelInfo['channel_id'] : 0;
        $previewChannelId = (int)$previewChannelId;
        $channelId        = (int)$channelId;
        if ($previewChannelId == $channelId) { // 如果是预览渠道的，访问超时的时候
            if ($this->batchChannelInfo['end_time'] < date('YmdHis')) {
                $this->resp_msg = TipsInfoService::getMessageInfoErrorSoftTxtByNo(-1069);
                return $this->resp_msg;
            }
        }

        // 没购买过打包营销工具的机构,并且还没有付费的活动不能被访问
        if (!$this->checkActivityHasAuth() && $this->AuthAccessService->needVerifyBindChannelPower(
                        $marketInfo['batch_type']
                )
        ) {
            $this->resp_msg = TipsInfoService::getMessageInfoErrorSoftTxtByNo(-1068);
            return $this->resp_msg;
        }
        return true;
    }

    /**
     * 不是预览渠道的，没购买过打包营销工具的机构,并且还没有付费的活动不能被访问
     *
     * @return boolean
     */
    protected function checkActivityHasAuth()
    {
        $nodeInfo = get_node_info($this->nodeId);
        // 体验账号不校验 || 所有活动访问权限
        if ($nodeInfo['wc_version'] == 'v4' || $this->hasPayModule('m5', $nodeInfo)) {
            return true;
        }
        $previewChannelId = D('Channel')->getPreviewChannelId($this->nodeId); // 当前机构的预览渠道
        $channelId        = $this->batchChannelInfo['channel_id'] ? $this->batchChannelInfo['channel_id'] : 0;
        $previewChannelId = (int)$previewChannelId;
        $channelId        = (int)$channelId;
        if ($previewChannelId !== $channelId) { // 如果不是预览渠道的
            if ($this->batchChannelInfo['batch_type'] == '54') {
                // 付满送权限
                $nodeChargeInfo = M('tnode_charge')->where(['node_id' => $this->nodeId, 'charge_id' => '3091'])->find();
                if (!empty($nodeChargeInfo) && ($nodeChargeInfo['end_time'] > date('YmdHis'))) {
                    return true;
                }
            }
            // 如果开通了多宝电商权限，那么下面的活动都可以访问
            if ($this->hasPayModule('m2', $nodeInfo) && in_array(
                            $this->batchChannelInfo['batch_type'],
                            ['26', '27', '29', '31', '41', '55']
                    )
            ) {
                return true;
            }
            // 如果开通了积分商城权限，那么下面的活动都可以访问
            if ($this->hasPayModule('m4', $nodeInfo) && in_array($this->batchChannelInfo['batch_type'], ['2001'])) {
                return true;
            }
            if (!(($nodeInfo['wc_version'] == 'v9') && $this->hasPayModule('m1', $nodeInfo))) { // 如果不是"购买打包营销模块"的机构的
                $bRet = D('BindChannel')->isPaid($this->nodeId, $this->batchChannelInfo['batch_id']);
                if (!$bRet) { // 如果没有付款
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * 有没有购买过指定的模块
     *
     * @param String $strModule 给定的模块，用‘,’分割，例：'m1,m2'
     * @param String $nodeInfo  机构信息（需包含wc_version，pay_module）
     *
     * @return boolean
     */
    protected function hasPayModule($strModule, $nodeInfo)
    {
        if ($nodeInfo['wc_version'] == 'v4') {
            return true;
        }
        $strModule = trim($strModule, ',');
        $arrModule = explode(',', $strModule);
        $payModule = explode(',', trim($nodeInfo['pay_module'], ','));
        if (empty($payModule)) {
            return false;
        }
        foreach ($arrModule as $k => $v) {
            if (!in_array($v, $payModule)) {
                return false;
            }
        }
        return true;
    }
}