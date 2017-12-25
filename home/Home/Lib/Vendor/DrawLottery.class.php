<?php

/**
 * 抽奖
 *
 * @author Jeff Liu @date 2015-06-30 Class DrawLottery
 */
class DrawLottery {

    /**
     *
     * @var DrawLotteryModel
     */
    public $drawLotteryModel;

    private $id;

    /**
     *
     * @param int $id 标签id
     * @param int $mobile 手机号
     * @param null $fullId
     * @param null $ticketSeq 非标流水号
     * @param array $otherParam 其他参数
     */
    public function __construct($id, $mobile, $fullId = null, $ticketSeq = null, 
        $otherParam = array()) {
        $this->drawLotteryModel = D('DrawLottery');
        $this->drawLotteryModel->initParam($id, $mobile, $fullId, $ticketSeq, 
            $otherParam);
        
        $this->today = date('Ymd'); // 用于统计
        $this->id = $id;
        
        if (empty($this->fullId)) {
            $this->fullId = $id;
        }
        $fullIdList = explode(',', $this->fullId);
        $fullIdWhere = array(
            'id' => array(
                'in', 
                $fullIdList));
        $farr = M('tbatch_channel')->where($fullIdWhere)->select();
        
        $channelIdList = array();
        foreach ($farr as $fv) {
            $channelIdList[] = $fv['channel_id'];
        }
        $this->channelList = $channelIdList;
    }

    /**
     * 抽奖发码 异步队列
     *
     * @author Jeff Liu @date 2015-06-30
     * @return array
     */
    public function sendCodeQueue() {
        $resp = $this->drawLotteryModel->verifyDrawLotteryCondition();
        
        // 不再执行更新
        if ($resp['code'] === 1) {
            // 抽奖接口
            import("@.Vendor.CjInterface");
            $req = new CjInterface();
            $sendData = $this->drawLotteryModel->buildSendData();
            $iresp = $req->cjSendQueue($sendData);
            if ($iresp['resp_id'] == '0000') {
                $iresp['batch_name'] = $this->drawLotteryModel->getBatchInfo(
                    "batch_no='" . $iresp['batch_no'] . "'", 
                    BaseModel::SELECT_TYPE_FIELD, 'batch_short_name');
                
                return $iresp;
            } else {
                return $iresp;
            }
        } else {
            // 无抽奖资格
            $resErrMsg = C('tipsInfo.' . $resp['code'] . '.errorSoftTxt', null);
            if (empty($resErrMsg)) {
                $resErrMsg = C('tipsInfo.default.errorSoftTxt', null);
            }
            log_write('活动错误：' . print_r($resErrMsg, true));
            
            return array(
                'resp_id' => '-1', 
                'resp_str' => '未中奖,感谢您的参与');
        }
    }
    
    //
    private $optType;
    // 统计需要更新的字段
    private $today;
    // 今天
    
    /**
     * todo 这个可以放在 异步操作 tmarketing_info tbatch_channel 读写操作 更新抽奖统计
     *
     * @param $optType
     * @return bool
     */
    protected function updateRecord($optType, $originCondition) {
        if (empty($optType)) {
            return false;
        }
        $this->optType = $optType;
        $where = array(
            'label_id' => $originCondition['id'], 
            'node_id' => $originCondition['node_id'], 
            'full_id' => $originCondition['full_id'], 
            'day' => $this->today);
        $this->updateDayData($where);
        $where = array(
            'label_id' => $originCondition['id'], 
            'node_id' => $originCondition['node_id'], 
            'full_id' => $originCondition['full_id'], 
            'day' => $this->today);
        $this->updateBatch($where);
        $where = array(
            'label_id' => $originCondition['id'], 
            'node_id' => $originCondition['node_id'], 
            'full_id' => $originCondition['full_id'], 
            'day' => $this->today);
        $this->updateChannel($where);
        $where = array(
            'label_id' => $originCondition['id'], 
            'node_id' => $originCondition['node_id'], 
            'full_id' => $originCondition['full_id'], 
            'day' => $this->today);
        $this->updateLabel($where);
        
        return true;
    }

    /**
     * 更新日统计
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $where
     * @return bool
     */
    protected function updateDayData($where) {
        $arr = array(
            'label_id' => $this->id, 
            'node_id' => $where['node_id'], 
            'full_id' => $where['full_id'], 
            'day' => $this->today);
        return M('tdaystat')->where($arr)->setInc($this->optType, 1);
    }
    
    // 更新活动统计数量
    protected function updateBatch($where) {
        $wh = array(
            'node_id' => $where['node_id'], 
            'id' => $where['batch_id']);
        M('tmarketing_info')->where($wh)->setInc($this->optType, 1);
    }
    
    // 更新渠道统计数量
    protected function updateChannel($where) {
        $wh = array(
            'node_id' => $where['node_id'], 
            'id' => array(
                'in', 
                $this->channelList));
        M('tchannel')->where($wh)->setInc($this->optType, 1);
    }
    
    // 更新标签统计数量
    protected function updateLabel($where) {
        $wh = array(
            'node_id' => $where['node_id'], 
            'id' => $this->id);
        M('tbatch_channel')->where($wh)->setInc($this->optType, 1);
    }
}