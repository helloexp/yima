<?php

class WeixinCardModel extends Model {

    protected $tableName = 'twx_card_type';

    /**
     * 创建微信卡券
     *
     * @param unknown $data 微信卡券数据
     * @return boolean
     */
    public function addWxCard($data) {
        $cardId = $this->add($data);
        if (! $cardId) {
            $this->error = '系统出错,微信卡券创建失败';
            return false;
        }
        // 微信插入
        $service = D('WeiXinCard', 'Service');
        $winxinInfo = M('tweixin_info')->where("node_id='{$data['node_id']}'")->find();
        $service->init($winxinInfo['app_id'], $winxinInfo['app_secret'], 
            $winxinInfo['app_access_token']);
        $weixinResult = $service->create($cardId);
        if (! $weixinResult) {
            M('twx_card_type')->where("id='{$cardId}'")->delete();
            $this->error = $service->error;
            return false;
        }
        if ($data['store_mode'] == '2') {
            $cardInfo = M()->table("twx_card_type w")->join(
                "tgoods_info g ON w.goods_id=g.goods_id")
                ->where(array(
                'w.id' => $cardId))
                ->find();
            // 活动插入
            $mData = array(
                'batch_type' => '40', 
                'card_id' => $cardInfo['card_id'], 
                'node_id' => $data['node_id'], 
                'add_time' => date('YmdHis'));
            $batchId = M('tmarketing_info')->add($mData);
            if (! $batchId) {
                $this->error = '系统出错,微信卡券活动创建失败';
                return false;
            }
            // tbatch_info数据插入
            $bData = array(
                'batch_no' => $cardInfo['batch_no'], 
                'batch_short_name' => $cardInfo['goods_name'], 
                'batch_name' => $cardInfo['goods_name'], 
                'node_id' => $data['node_id'], 
                'user_id' => $data['user_id'], 
                'batch_class' => $cardInfo['goods_type'], 
                'batch_type' => $cardInfo['source'], 
                'use_rule' => $cardInfo['mms_text'], 
                'batch_img' => $cardInfo['goods_image'], 
                'info_title' => $cardInfo['mms_title'], 
                'batch_amt' => $cardInfo['goods_amt'], 
                'begin_time' => $cardInfo['begin_time'], 
                'end_time' => $cardInfo['end_time'], 
                'add_time' => date('YmdHis'), 
                'node_pos_group' => $cardInfo['pos_group'], 
                'node_pos_type' => $cardInfo['pos_group_type'], 
                'batch_desc' => $cardInfo['goods_desc'], 
                'node_service_hotline' => $cardInfo['node_service_hotline'], 
                'goods_id' => $cardInfo['goods_id'], 
                'storage_num' => $data['quantity'], 
                'remain_num' => 0, 
                'material_code' => $cardInfo['customer_no'], 
                'print_text' => $cardInfo['print_text'], 
                'm_id' => $batchId, 
                'validate_type' => $cardInfo['validate_type'], 
                'card_id' => $cardInfo['card_id']);
            $result = M('tbatch_info')->data($bData)->add();
            if (! $result) {
                $this->error = '系统出错,微信卡券活动添加失败-0001';
                return false;
            }
            // 扣减库存
            $goodsM = D('Goods');
            $flag = $goodsM->storagenum_reduc($cardInfo['goods_id'], 
                $data['quantity'], '', '13', '');
            if ($flag === false) {
                $this->error = "库存扣减失败,{$goodsM->getError()}";
                return false;
            }
        }
        return true;
    }

    /**
     * 检查翼码卡券是否已同步创建了微信卡券
     *
     * @param unknown $goodsId
     */
    public function checkSyncFornumGoods($goodsId) {
        $cardInfo = $this->where("goods_id='{$goodsId}' and auth_flag<3")
            ->order('id DESC')
            ->find();
        if (empty($cardInfo)) {
            $this->error = '未找到有效的微信卡券数据';
            return false;
        }
        return $cardInfo;
    }

    /**
     * 微信卡券补充库存
     *
     * @param $id twx_card_type id
     * @param $num 补充库存数
     */
    public function addStorageNum($id, $num) {
        $wxCardInfo = $this->where(array(
            'id' => $id))->find();
        if (empty($wxCardInfo)) {
            $this->error = '未找到有效的微信卡券数据';
            return false;
        }
        if ($wxCardInfo['code_store_flag'] == 1) {
            $this->error = '请稍后再试';
            return false;
        }
        $data = array(
            'code_store_flag' => '0', 
            'id' => $id, 
            'quantity' => $wxCardInfo['quantity'] + $num);
        if ($wxCardInfo['code_store_flag'] == 0) { // 未处理状态
            $data['store_modify_num'] = $wxCardInfo['store_modify_num'] + $num;
        } elseif ($wxCardInfo['code_store_flag'] == 2) { // 已处理状态
            $data['store_modify_num'] = $num;
        }
        $flag = $this->save($data);
        if ($flag === false) {
            $this->error = '系统出错,微信卡券数据更新失败';
            return false;
        }
        return true;
    }
}