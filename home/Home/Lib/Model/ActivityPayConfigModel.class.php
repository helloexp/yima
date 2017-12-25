<?php

/**
 *
 * @author lwb 20160127
 */
class ActivityPayConfigModel extends Model {

    protected $tableName = 'tactivity_pay_config';

    /**
     * 根据id获取活动的付款模型
     * @param int $id
     * @return mixed
     */
    public function getPayConfigModelById($id) {
        $re = $this->where(array(
            'id' => $id))->field('model')->find();
        $model = json_decode($re['model'], true);
        return $model;
    }
    
    public function getPriceConfig($where) {
        return $this->where($where)->select();
    }
    
    /**
     * 根据node_id获取发码费价格
     * @param string $nodeId
     * @return mixed
     * [
     * 'self' => 自建价格, 'buy' => '异业价格', 'wx' => '微信卡券流量价格'
     * ]
     */
    public function getSendFee($nodeId) {
        //如果有同步过来的数据就用同步过来的价格，没有的话用默认的价格
        $node = M('tnode_info')->field('sale_flag')->where(array('node_id' => $nodeId))->find();
        $saleFlag = $node['sale_flag'];//是否开通了多米收单
        //默认值
        $default = $this->getPriceConfig(array('id' => array('in', array('2', '3', '4', '5', '7'))));
        $defaultPriceById = array();
        foreach ($default as $v) {
            $defaultPriceById[$v['id']] = $v;
        }
        $price = array();
        if ($saleFlag) {//开通多米收单的价格
            $price['self'] = $defaultPriceById['5']['price'];//自建
            $price['buy'] = $defaultPriceById['7']['price'];//异业
            $price['wx'] = 0;
        } else {//未开通多米收单的价格
            $price['self'] = $defaultPriceById['2']['price'];//自建
            $price['buy'] = $defaultPriceById['3']['price'];//异业
            $price['wx'] = $defaultPriceById['4']['price'];
        }
        
        //发送自用卡券的费用,  发送微信卡券的费用,  发送异业卡券的费用
        $chargeArr = $saleFlag ? array(
            CommonConst::CHARGE_ID_ZIYONG_DM, 
            CommonConst::CHARGE_ID_YIYE, 
            CommonConst::CHARGE_ID_WEIXIN_DM,
        ) : array(
            CommonConst::CHARGE_ID_ZIYONG,
            CommonConst::CHARGE_ID_YIYE,
            CommonConst::CHARGE_ID_WEIXIN,
        );
        
        //同步过来的价格
        $synPrice = $this->getPriceConfig(
            array(
                'node_id' => $nodeId, 
                'charge_id' => array('in', $chargeArr)));
        if (!empty($synPrice)) {//如果空的返回默认值
            foreach ($synPrice as $val) {
                if ($saleFlag) {
                    if ($val['charge_id'] == CommonConst::CHARGE_ID_ZIYONG_DM) {
                        $price['self'] = $val['price'];
                    } elseif ($val['charge_id'] == CommonConst::CHARGE_ID_YIYE) {
                        $price['buy'] = $val['price'];
                    } elseif ($val['charge_id'] == CommonConst::CHARGE_ID_WEIXIN_DM) {
                        $price['wx'] = $val['price'];
                    }
                } else {
                    if ($val['charge_id'] == CommonConst::CHARGE_ID_ZIYONG) {
                        $price['self'] = $val['price'];
                    } elseif ($val['charge_id'] == CommonConst::CHARGE_ID_YIYE) {
                        $price['buy'] = $val['price'];
                    } elseif ($val['charge_id'] == CommonConst::CHARGE_ID_WEIXIN) {
                        $price['wx'] = $val['price'];
                    }
                }
            }
        }
        return $price;
    }
    
    public function getDefaultPayConfigModelByBatchType($batchType) {
        $priceModel = '';
        $defaultPayConfig = C('DEFAULT_PAY_ACTIVITY_TYPE_CONFIG');
        foreach ($defaultPayConfig as $k => $v) {//键值配的是配置表的id，值配的是对应的活动类型id
            if (in_array($batchType, $v)) {
                $priceModel = M('tactivity_pay_config')->where(array('id' => $k))->getField('model');
                break;
            }
        }
        return $priceModel;
    }
}