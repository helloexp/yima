<?php
// 抽奖活动预览
class CjSetShowAction extends BaseAction {

    public $batch_id;

    public $showsize;

    public $batch_type;

    public $size = '1';

    public $id;

    private $previewBatchType = array(19,58);

    public function _initialize() {
        parent::_initialize();
        $this->batch_id = I('batch_id');
        $this->batch_type = I('batch_type');
    }

    /**
     *
     */
    public function preview() {
        $ret = $this->code(true);
        $this->ajaxReturn(0, $ret, 1);
    }

    /* 付费用户有预览功能 */
    public function version($isAjax = true) {
        // 判断是否为付费用户
        if (in_array($this->node_type_name,
            array(
                'c0',
                'c1'))) {
            $payType = 0; // 未付费用户
        } else {
            $payType = 1; // 付费用户
        }
        if ($isAjax) {
            if (0 == $payType) {
                $this->ajaxReturn(0, "只有付费用户可以使用预览功能", 0);
            } else {
                $this->ajaxReturn(0, "二维码展示", '1');
            }
        } else {
            return $payType;
        }
    }

    public function code($ret = false) {
        if (empty($this->batch_id))
            return false;
        $id = M('tchannel')->where(
            array(
                'type' => 1,
                'sns_type' => 61,
                'node_id' => $this->nodeId,
                'status' => 1))->getField('id');

        if (! $id) {
            $data = array(
                'type' => 1,
                'name' => '默认预览',
                'sns_type' => 61,
                'status' => 1,
                'node_id' => $this->nodeId,
                'add_time' => date('YmdHis'),
                'send_count' => 0,
                'batch_id' => $this->batch_id);
            $result = M('tchannel')->add($data);
            if ($result) {
                $id = M('tchannel')->where(
                    array(
                        'batch_id' => $this->batch_id))->getField('id');
            }
        }

        // 预览时间
        $previewmTime = M('tsystem_param')->where(
            array(
                'param_name' => 'BATCH_PREVIEW_TIME'))->getField('param_value');

        $channelId = M('tbatch_channel')->where(
            array(
                'batch_id' => $this->batch_id,
                'channel_id' => $id))->getField('id');

        if (! $channelId) {
            $data = array(
                'batch_type' => $this->batch_type,
                'batch_id' => $this->batch_id,
                'channel_id' => $id,
                'add_time' => date('YmdHis'),
                'node_id' => $this->nodeId,
                'status' => 1,
                'end_time' => date('YmdHis',
                    strtotime(date('YmdHis')) + 60 * $previewmTime));

            // 绑定渠道
            $result = M('tbatch_channel')->add($data);
            if (! $result)
                return false;
            $channelId = $result;
        } else {
            $result = M('tbatch_channel')->where(
                array(
                    'batch_id' => $this->batch_id,
                    'id' => $channelId))->save(
                array(
                    'end_time' => date('YmdHis',
                        strtotime(date('YmdHis')) + 60 * $previewmTime)));
        }

        if ($channelId) {
            if ($ret) {
                return U('LabelAdmin/ShowCode/index', array('id' => $channelId));
            } else {
                $this->redirect('LabelAdmin/ShowCode/index', array('id' => $channelId));
            }
        }
    }
}

?>