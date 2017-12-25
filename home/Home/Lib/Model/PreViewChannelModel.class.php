<?php

/**
 * 此模块主要用于生成预览渠道的二维码
 */
class PreViewChannelModel extends BaseModel {
    protected $tableName = '__NONE__';
    public function __construct() {
        parent::__construct();
        import("@.Vendor.CommonConst");
    }

    public function getPreviewChannelCode($nodeId, $batchId, $batchType) {
        // 检查是否存在"默认预览"（渠道）
        $channelId = M('tchannel')->where(
            array(
                'type' => 1, 
                'sns_type' => 61, 
                'node_id' => $nodeId, 
                'status' => 1))->getField('id');
        if (! $channelId) {
            $data = array(
                'type' => 1, 
                'name' => '默认预览', 
                'sns_type' => 61, 
                'status' => 1, 
                'node_id' => $nodeId, 
                'add_time' => date('YmdHis'), 
                'send_count' => 0);
            $channelId = M('tchannel')->add($data);
            if (empty($channelId)) {
                throw_exception("默认预览渠道创建失败");
            }
        }
        // 预览时间
        $previewmTime = M('tsystem_param')->where(
            array(
                'param_name' => 'BATCH_PREVIEW_TIME'))->getField('param_value');
        $bcId = M('tbatch_channel')->where(
            array(
                'node_id' => $nodeId, 
                'batch_type' => $batchType, 
                'batch_id' => $batchId, 
                'channel_id' => $channelId))->getField('id');
        if (empty($bcId)) {
            $data = array(
                'batch_type' => $batchType, 
                'batch_id' => $batchId, 
                'channel_id' => $channelId, 
                'add_time' => date('YmdHis'), 
                'node_id' => $nodeId, 
                'status' => 1, 
                'end_time' => date('YmdHis', 
                    strtotime(date('YmdHis')) + 60 * $previewmTime));
            
            // 绑定渠道
            $bcId = M('tbatch_channel')->add($data);
            if (! $bcId) {
                throw_exception("默认预览渠道创建失败");
            }
        } else {
            if (false ===
                 M('tbatch_channel')->where(
                    array(
                        'id' => $bcId))->setField('end_time', 
                    date('YmdHis', 
                        strtotime(date('YmdHis')) + 60 * $previewmTime))) {
                throw_exception("默认预览渠道创建失败");
            }
        }
        
        if ($bcId) {
            return U('LabelAdmin/ShowCode/index', 
                array(
                    'id' => $bcId));
        }
    }
}