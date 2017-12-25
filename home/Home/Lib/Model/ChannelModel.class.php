<?php

/**
 * 渠道相关 model
 *
 * @author : Jeff Liu<liuwy@imageco.com.cn> Date: 2015/09/08
 */
class ChannelModel extends BaseModel {

    protected $tableName = 'tchannel';

    /**
     * 获得制定条件渠道内容
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $where
     * @return mixed
     */
    public function getChannelInfo($where) {
        return $this->getData($this->tableName, $where, 
            BaseModel::SELECT_TYPE_ONE);
    }

    /**
     * 获得指定条件制定field内容
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $where
     * @param $field
     * @return mixed
     */
    public function getChannelField($where, $field) {
        return $this->getData($this->tableName, $where, 
            BaseModel::SELECT_TYPE_FIELD, $field);
    }

    /**
     * 获得当前商户预览渠道
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $nodeId
     * @return mixed
     */
    public function getPreviewChannelId($nodeId) {
        import('@.Vendor.CommonConst') or die('include file fail.');
        $where = array(
            'type' => 1, 
            'sns_type' => CommonConst::SNS_TYPE_PREVIEW, 
            'node_id' => $nodeId, 
            'status' => 1);
        return $this->getChannelField($where, 'id');
    }

    /**
     * 根据当前时间判断进哪个活动，如果channel表的活动不是当前的，更新channel表
     *
     * @param array $result tchannel表的一条数据
     * @return int $label_id batchChannel表的id
     */
    public function getBatchChannelIdByCurrentTime($result) {
        // 标签
        $mod = M('tbatch_channel');
        if ($result['go_url']) {
            // 目前就单个外链
            $label_id = $mod->where(
                array(
                    'batch_type' => 0, 
                    'batch_id' => 0, 
                    'channel_id' => $result['id']))->getField('id');
            return $label_id; // 为空的话，数据可能不对
        }
        // 如果当前时间不在有效期之内,或者没有填有效期的,找到对应时间的渠道填上有效期
        $now = date('YmdHis');
        $label_id = '';
        if ($now < $result['begin_time'] || $now > $result['end_time'] ||
             empty($result['begin_time']) || empty($result['end_time'])) {
            $where = array(
                'channel_id' => $result['id'], 
                'status' => '1');
            $bachChannelArr = $mod->where($where)
                ->order('start_time asc')
                ->field(
                array(
                    'start_time', 
                    'end_time', 
                    'id'))
                ->select();
            if (! $bachChannelArr)
                $this->error('暂时没有进行的活动哦！');
            $now = date('YmdHis');
            // 取时间范围以内的活动
            foreach ($bachChannelArr as $v) {
                if ($now >= $v['start_time'] && $now <= $v['end_time']) {
                    $label_id = $v['id'];
                    break;
                }
            }
            // 如果有绑定的活动但是当前时间都不在范围内,取最靠近当前时间的前一个活动进入，如果之前的没有就取第一个
            if (empty($label_id)) {
                $length = count($bachChannelArr);
                for ($i = $length - 1; $i >= 0; $i --) {
                    if ($now > $bachChannelArr[$i]['end_time']) {
                        $label_id = $bachChannelArr[$i]['id'];
                        break;
                    }
                }
            }
            if (empty($label_id)) {
                $label_id = $bachChannelArr[0]['id'];
            }
            
            $batchChannelRecord = $mod->where(
                array(
                    'id' => $label_id))
                ->field('batch_type,batch_id,start_time,end_time')
                ->find();
            // 如果之前所取的活动和判断下来要取的活动不是同一个
            if ($result['batch_id'] != $batchChannelRecord['batch_id']) {
                // 更新channel表
                $this->where(
                    array(
                        'id' => $result['id']))->save(
                    array(
                        'batch_type' => $batchChannelRecord['batch_type'], 
                        'batch_id' => $batchChannelRecord['batch_id'], 
                        'begin_time' => $batchChannelRecord['start_time'], 
                        'end_time' => $batchChannelRecord['end_time']));
            }
        } else {
            $label_id = $mod->where(
                array(
                    'batch_type' => $result['batch_type'], 
                    'batch_id' => $result['batch_id'], 
                    'channel_id' => $result['id'], 
                    'status' => '1'))->getField('id');
        }
        // 正常情况下$label_id不可能为空，除非数据不对
        return $label_id;
    }
}