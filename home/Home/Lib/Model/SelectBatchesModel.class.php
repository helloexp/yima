<?php

/**
 *
 * @author lwb Time 20151225
 */
class SelectBatchesModel extends Model {
    protected $tableName = '__NONE__';
    public function _initialize() {
        import('@.Vendor.CommonConst') or die('include file fail.');
    }

    /**
     *
     * @param unknown $nodeId
     * @param unknown $batch_id
     * @param unknown $batch_type
     * @param unknown $channel_id
     * @return multitype:multitype:number array() Ambigous <>
     */
    public function getBindedActivityAndNewActivityTimeStamp($nodeId, $batch_id, 
        $batch_type, $channel_id, $isAdd = true) {
        $marketModel = M('tmarketing_info');
        // 新绑定的活动的活动时间
        $selectMarketinfo = $marketModel->where(
            array(
                'id' => $batch_id, 
                'batch_type' => $batch_type))
            ->field(
            array(
                'start_time', 
                'end_time', 
                'name'))
            ->find();
        $addNewSelectStartTime = strtotime($selectMarketinfo['start_time']);
        $addNewSelectEndTime = strtotime($selectMarketinfo['end_time']);
        // 如果是添加绑定
        if ($isAdd) {
            // 已经绑定的活动的使用时间
            $alreadySelected = $this->getBindedChannelTime($nodeId, $channel_id);
        } else {
            // 如果是修改绑定时间，检查的就是除了给定mId之外的绑定的时间
            $alreadySelected = $this->getBindedChannelTimeNotIncludeGivedMid(
                $nodeId, $channel_id, $batch_id);
        }
        foreach ($alreadySelected as &$v) {
            $v['start_time'] = strtotime($v['start_time']);
            $v['end_time'] = strtotime($v['end_time']);
        }
        unset($v);
        $arr = array(
            'newBind' => array(
                'start_time' => $addNewSelectStartTime, 
                'end_time' => $addNewSelectEndTime), 
            'binded' => $alreadySelected, 
            'm_name' => $selectMarketinfo['name']);
        return $arr;
    }

    /**
     * 获取渠道下已经绑定的活动的时间段
     *
     * @param string $nodeId 机构号
     * @param int $channel_id 渠道号
     * @return array()|bool
     */
    public function getBindedChannelTime($nodeId, $channel_id) {
        $batchChannelModel = M('tbatch_channel');
        // status1正常
        // batch_id = 0 是外链活动
        $alreadySelected = $batchChannelModel->where(
            array(
                'node_id' => $nodeId, 
                'channel_id' => $channel_id, 
                'status' => '1', 
                'batch_id' => array(
                    'neq', 
                    0),
                'batch_type' => array(
                    'neq',
                    0)))
            ->order('start_time asc')
            ->field(
            array(
                'start_time', 
                'end_time', 
                'batch_id'))
            ->select();
        return $alreadySelected;
    }

    /**
     * 获取渠道下不包括给定活动的其他所有活动设定的渠道开始时间和结束时间
     *
     * @param unknown $nodeId
     * @param unknown $channel_id
     * @param unknown $mId
     * @return array()|bool
     */
    public function getBindedChannelTimeNotIncludeGivedMid($nodeId, $channel_id, 
        $mId) {
        $batchChannelModel = M('tbatch_channel');
        $alreadySelected = $batchChannelModel->where(
            array(
                'node_id' => $nodeId, 
                'channel_id' => $channel_id, 
                'status' => '1',  // 1正常2停用
                'batch_id' => array(
                    'neq', 
                    $mId)))
            ->order('start_time asc')
            ->field(
            array(
                'start_time', 
                'end_time', 
                'batch_id'))
            ->select();
        return $alreadySelected;
    }

    public function editGoUrl($nodeId, $channelId, $url) {
        $re = M('tchannel')->where(
            array(
                'node_id' => $nodeId, 
                'id' => $channelId))->find();
        if (! $re) {
            throw_exception('渠道不存在');
        }
        $result = M('tchannel')->where(
            array(
                'node_id' => $nodeId, 
                'id' => $channelId))->save(
            array(
                'go_url' => $url));
        $batchChannellModel = M('tbatch_channel');
        $where = array(
            'node_id' => $nodeId, 
            'channel_id' => $channelId, 
            'batch_id' => 0, 
            'batch_type' => 0);
        // 查看tbatch_channel有没有外部链接的记录,有的话修改状态，没有的话插入一条
        $outLink = $batchChannellModel->where($where)->find();
        if ($outLink) {
            $batchChannellModel->where($where)->save(
                array(
                    'status' => '1', 
                    'start_time' => date('YmdHis'), 
                    'end_time' => ''));
        } else {
            $data = array(
                'batch_type' => 0, 
                'batch_id' => 0, 
                'channel_id' => $channelId, 
                'start_time' => date('YmdHis'), 
                'end_time' => '',
                'add_time' => Date('YmdHis'), 
                'node_id' => $nodeId, 
                'status' => '1');
            $re = $batchChannellModel->add($data);
        }
        if (false === $result) {
            throw_exception('更改外部链接错误');
        }
    }
}