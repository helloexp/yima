<?php

/**
 * 抽奖
 *
 * @author Jeff Liu @date 2016-04-13 Class DrawLotteryBase
 */
class DrawLotteryBase
{

    /**
     *
     * @var DrawLotteryBaseModel
     */
    public $DrawLotteryBaseModel;

    /**
     * @var DrawLotteryBaseService
     */
    public $DrawLotteryBaseService;

    public function __construct()
    {
        $this->DrawLotteryBaseService = D('DrawLotteryBase', 'Service');
    }

    /**
     * todo 是否还需要队列????
     *
     * @param int   $id         标签id (tbatch_channel id)
     * @param array $otherParam [phone_no, wx_open_id[, wx_nick_name, ]]
     *
     * @return mixed
     */
    public function drawLottery($id, $otherParam)
    {
        $ret = $this->DrawLotteryBaseService->drawLottery($id, $otherParam);
        return $ret;
    }

    /**
     * 抽奖发码 异步队列
     *
     * @author Jeff Liu @date 2015-06-30
     * @return array
     */
    public function sendCodeQueue()
    {
        $resp = $this->DrawLotteryBaseModel->verifyDrawLotteryCondition();

        // 不再执行更新
        if ($resp['code'] === 1) {
            // 抽奖接口
            import("@.Vendor.CjInterface");
            $req      = new CjInterface();
            $sendData = $this->DrawLotteryBaseModel->buildSendData();
            $iresp    = $req->cjSendQueue($sendData);
            if ($iresp['resp_id'] == '0000') {
                $iresp['batch_name'] = $this->DrawLotteryBaseModel->getBatchInfo(
                        "batch_no='" . $iresp['batch_no'] . "'",
                        BaseModel::SELECT_TYPE_FIELD,
                        'batch_short_name'
                );

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
                    'resp_id'  => '-1',
                    'resp_str' => '未中奖,感谢您的参与'
            );
        }
    }
}