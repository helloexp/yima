<?php

/*
 * Created by ys. User: Administrator Date: 2015/10/29 Time: 16:46
 */
class IntegralGoodsInfoModel extends BaseModel {
    protected $tableName = '__NONE__';
    /**
     * 新增营销活动
     */
    public function _addMarketingInfo($name, $market_price, $goods_info, 
        $delivery_flag, $node_id, $batch_type, $line_type) {
        // tmarketing_info数据创建
        // 活动开始时间为当前时间，结束时间为合同有效期时间
        $nodeInfo = M('tnode_info')->where(
            array(
                "node_id" => $node_id))->find();
        $market_data = array(
            'name' => $name, 
            'batch_type' => $batch_type, 
            'node_id' => $node_id, 
            'start_time' => date("YmdHis", time()), 
            'end_time' => '20301231235959', 
            'market_price' => $market_price, 
            'node_name' => $nodeInfo['node_name'], 
            'join_mode' => '1', 
            'is_cj' => 1,  // 是否抽奖0 否 1是
            'memo' => $goods_info, 
            'defined_one_name' => $delivery_flag,  // 1 到店领取 2 物流配送
            'status' => $line_type, 
            'add_time' => date('YmdHis'));
        
        $m_id = M('tmarketing_info')->add($market_data);
        if (! $m_id) {
            return array(
                'code' => '0009', 
                'err_msg' => '系统出错,新建营销活动失败', 
                'm_id' => '');
        }
        return array(
            'code' => '0000', 
            'err_msg' => '新建成功', 
            'm_id' => $m_id);
    }
    // 新增营销品发送信息
    public function _addBatchInfo($reqData, $m_id, $goods_id, $batchNo, $node_id, 
        $batch_type, $user_id, $line_type, $groupId, $groupTypeId) {
        // 创建batch_info数据
        $batch_data = array(
            'batch_no' => $batchNo, 
            'batch_short_name' => $reqData['name'], 
            'batch_name' => $reqData['name'], 
            'node_id' => $node_id, 
            'user_id' => $user_id, 
            // 'material_code' => $materialCode,
            'batch_class' => $batch_type, 
            'batch_type' => $batch_type, 
            'info_title' => $reqData['mms_title'], 
            'use_rule' => $reqData['mms_text'], 
//            'sms_text' => $reqData['sms_text'],
            'batch_img' => $reqData['goods_img'], 
            'batch_amt' => $reqData['batch_price'],  // 平安币
            'begin_time' => date('YmdHis'), 
            'end_time' => '20301231235959', 
            'node_pos_type' => $groupTypeId, 
            'node_pos_group' => $groupId, 
            'add_time' => date('YmdHis'), 
            'status' => $line_type, 
            'goods_id' => $goods_id, 
            'storage_num' => $reqData['goods_num'], 
            'remain_num' => $reqData['goods_num'], 
            'batch_desc' => $reqData['goods_desc'], 
            'm_id' => $m_id);
        //自定义短信内容
        if(isset($reqData['sms_text']) && !empty($reqData['sms_text'])){
            $batch_data['sms_text'] = $reqData['sms_text'];
        }
        if ($reqData['verify_time_type'] == '0') {
            $batch_data['verify_begin_date'] = $reqData['verify_begin_date'] .
                 '000000';
            $batch_data['verify_end_date'] = $reqData['verify_end_date'] .
                 '235959';
            $batch_data['verify_begin_type'] = $reqData['verify_time_type'];
            $batch_data['verify_end_type'] = $reqData['verify_time_type'];
        } elseif ($reqData['verify_time_type'] == '1') {
            $batch_data['verify_begin_date'] = $reqData['verify_begin_days'];
            $batch_data['verify_end_date'] = $reqData['verify_end_days'];
            $batch_data['verify_begin_type'] = $reqData['verify_time_type'];
            $batch_data['verify_end_type'] = $reqData['verify_time_type'];
        }
        $b_id = M('tbatch_info')->add($batch_data);
        log_write("SSSS" . M()->getLastSql());
        if (! $b_id) {
            // $this->error('系统出错,添加tbatch_info失败');
            return array(
                'code' => '0008', 
                'err_msg' => '系统出错,新建营销活动发送参数失败', 
                'b_id' => '');
        }
        return array(
            'code' => '0000', 
            'err_msg' => '新建成功', 
            'b_id' => $b_id);
    }
    // 新增营销品
    public function addGoodsEx($b_id, $m_id, $reqData, $goodsId, $node_id) {
        $goodsDataEx = array(
            'node_id' => $node_id, 
            'b_id' => $b_id, 
            'm_id' => $m_id, 
            'ecshop_classify' => $reqData['classify'], 
            // , 'is_commend' => '9'
            'show_picture1' => $reqData['resp_img1'], 
            'show_picture2' => $reqData['resp_img2'], 
            'show_picture3' => $reqData['resp_img3'], 
            'show_picture4' => $reqData['resp_img4'], 
            'show_picture5' => $reqData['resp_img5'], 
            'wap_info' => $reqData['wap_info'], 
            'goods_desc' => $reqData['goods_desc'], 
            'market_price' => $reqData['market_price'], 
            'day_buy_num' => $reqData['day_buy_num_flag'] == '0' ? - 1 : $reqData['day_buy_num'], 
            'person_buy_num' => $reqData['person_buy_num_flag'] == '0' ? - 1 : $reqData['person_buy_num'], 
            'delivery_flag' => $reqData['delivery_flag'], 
            'market_show' => $reqData['market_show'], 
            'goods_id' => $goodsId);
        $flag = M('tintegral_goods_ex')->add($goodsDataEx);
        if (! $flag) {
            // $this->error('系统出错,添加tbatch_info失败');
            return array(
                'code' => '0006', 
                'err_msg' => '系统出错,新建积分商城数据发送参数失败', 
                'b_id' => '');
        }
        return array(
            'code' => '0000', 
            'err_msg' => '新建成功', 
            'integral_id' => $flag);
    }
}