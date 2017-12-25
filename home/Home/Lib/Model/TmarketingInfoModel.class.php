<?php

/* 在线活动自动发布 */
class TmarketingInfoModel extends Model
{
    public $node_id;
    // 机构号
    public $batch_type;
    // 活动类型
    public $batch_id;
    // 活动id
    public $channel_id;
    // 渠道id
    public $label_id;
    // 标签号
    public $type = 1;
    // 渠道类型
    public $sns_type = 12;
    // 渠道二级类型
    public $sns_my_type = 13;
    // 渠道我创建的活动

    // 初始化
    public function init($opt = array())
    {
        $this->batch_id = $opt['batch_id'];
        $this->batch_type = $opt['batch_type'];
        $this->node_id = $opt['node_id'];
    }

    // 校验活动
    public function checkBatch()
    {
        // 校验机构类型
        $node_type = M('tnode_info')->where(
            array(
                'node_id' => $this->node_id, ))->getField('node_type');
        if ($node_type == '0' || $node_type == '1') {
            $query = M('tmarketing_info')->where(
                array(
                    'id' => $this->batch_id,
                    'batch_type' => $this->batch_type, ))->find();
            if ($query) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    // 校验渠道
    public function checkChannel()
    {
        $map = array(
            'node_id' => $this->node_id,
            'type' => $this->type,
            'sns_type' => $this->sns_type, );
        $query = M('tchannel')->where($map)->find();
        if (!$query) {
            $channelArr = array(
                'name' => 'O2O活动',
                'type' => $this->type,
                'sns_type' => $this->sns_type,
                'status' => '1',
                'node_id' => $this->node_id,
                'add_time' => date('YmdHis'), );
            $cquery = M('tchannel')->add($channelArr);
            if (empty($cquery)) {
                // $this->channel_id = $cquery;

                return false;
            }
        }
    }

    // 校验渠道
    public function checkMyChannel()
    {
        $map = array(
            'node_id' => $this->node_id,
            'type' => $this->type,
            'sns_type' => $this->sns_my_type, );
        $query = M('tchannel')->where($map)->find();
        if ($query) {
            $this->channel_id = $query['id'];
        } else {
            $channelArr = array(
                'name' => '我创建的活动',
                'type' => $this->type,
                'sns_type' => $this->sns_my_type,
                'status' => '1',
                'node_id' => $this->node_id,
                'add_time' => date('YmdHis'), );
            $cquery = M('tchannel')->add($channelArr);
            if ($cquery) {
                $this->channel_id = $cquery;
            }
        }
        if (empty($this->channel_id)) {
            return false;
        }
    }

    // 是否已生成
    public function checkBatchChannel()
    {
        $map = array(
            'node_id' => $this->node_id,
            'channel_id' => $this->channel_id,
            'batch_id' => $this->batch_id,
            'batch_type' => $this->batch_type, );
        $query = M('tbatch_channel')->where($map)->find();
        if ($query) {
            return false;
        }
    }

    // 生成标签号
    public function setLabelId()
    {
        // 获取商户客户号
        $client_id = M('tnode_info')->where(
            array(
                'node_id' => $this->node_id, ))->getField('client_id');
        if (!$client_id) {
            return false;
        }

        $seq_id = 'client_'.$client_id;
        $seq_str = sprintf('%06s', $client_id);

        $sql = "SELECT _nextval('".$seq_id."') as label_id";
        $label_arr = M()->query($sql);

        if ($label_arr[0]['label_id'] == '0') {
            return false;
        }

        $label_id = $label_arr[0]['label_id'];
        $label_id = $seq_str.sprintf('%06s', $label_id);
        $this->label_id = $label_id;
    }
    // 发布活动
    public function sendBatch()
    {
        $checkb = $this->checkBatch();

        if ($checkb === false) {
            return array(
                '0' => '未查询到活动', );
        }

        $checkc = $this->checkChannel();
        if ($checkc === false) {
            return array(
                '0' => '未查询到渠道', );
        }

        // $checkmy = $this->checkMyChannel();
        // if($checkmy === false)
        // return array('0'=>'未查询到渠道');

        /*
     * $checkbc = $this->checkBatchChannel(); if($checkbc === false) return
     * array('0'=>'该活动已绑定在线渠道！'); $checkl = $this->setLabelId(); if($checkl
     * === false ) return array('0'=>'标签号生成失败！'); $data = array();
     * $data['batch_type'] = $this->batch_type; $data['batch_id'] =
     * $this->batch_id; $data['channel_id'] =$this->channel_id;
     * $data['add_time'] = Date('YmdHis'); $data['node_id'] =
     * $this->node_id; $data['label_id']= $this->label_id; $query =
     * M('tbatch_channel')->add($data); if($query) return true; else return
     * array('0'=>'发布失败！');
     */
    }

    /**
     * 修改页面标题.
     *
     * @param $nodeId string 商户编号
     * @param $name string 页面标题
     * @param $type number 活动类型
     *
     * @return bool
     */
    public function editWapTitle($nodeId, $type, $name, $id)
    {
        $where = array(
            'node_id' => $nodeId,
            'batch_type' => $type,
            'id' => $id,
        );
        //$result = $this->where($where)->setField('wap_title', $name);
        $result = $this->where($where)->data(array('name' => $name, 'wap_title' => $name))->save();

        return $result;
    }
}
