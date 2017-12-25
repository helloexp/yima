<?php

/**
 *
 * @author lwb Time 20150828
 */
class MarketActiveModel extends Model {

    protected $tableName = 'tmarketing_active';

    public function _initialize() {
        import('@.Vendor.CommonConst') or die('include file fail.');
    }

    /**
     * 是否显示付费角标
     *
     * @param unknown $nodeId
     * @param unknown $batchType
     * @return boolean
     */
    public function hasFreeActivity($nodeId, $batchType) {
        // 如果不在购买活动的列表以内不用付费角标
        if (! D('BindChannel')->isInFreeUserBuyList($batchType)) {
            return false;
        }
        // 买了营销工具打包的不用显示
        $hasPay = D('node')->hasPayModule('m1', $nodeId);
        if ($hasPay) {
            return false;
        }
        if ($batchType == CommonConst::BATCH_TYPE_WEEL || $batchType == CommonConst::BATCH_TYPE_EUROCUP) {
            $re = M('tactivity_order')->where(
                array(
                    'node_id' => $nodeId, 
                    'order_type' => CommonConst::ORDER_TYPE_FREE, 
                    'batch_type' => $batchType))
                ->field('id')
                ->find();
            if (! $re) {
                return false;
            }
        }
        return true;
    }

    /**
     * 是否显示付费的图标
     *
     * @param string $nodeId 机构号
     * @param int $batchType 活动类型
     * @param boolean $isFreeUser 是否是免费用户
     * @return boolean 是否显示付费的图标
     */
    public function needShowPayIcon($nodeId, $batchType, $isFreeUser) {
        $needShow = false;
        if ($isFreeUser) {
            // 查询有没有指定类型的免费订单
            $needShow = $this->hasFreeActivity($nodeId, $batchType);
        }
        return $needShow;
    }

    /**
     *
     * @param string $nodeId 机构号
     * @param int $batchType 活动类型
     * @param boolean $isFreeUser 是否是免费用户
     * @param string $priceModel 收费模型(自定义的收费模型)
     * @return array('comment'=>前端突出显示收费概要的文字, 'explain' => 对该文字的解释说明)
     */
    public function getComment($nodeId, $batchType, $isFreeUser, $priceModel = '') {
        //example:$priceModel = '{"basicPrice":"2980","duringTime":"60","exPrice":"30"}';
        if (empty($priceModel)) {
            $priceModel = D('ActivityPayConfig')->getDefaultPayConfigModelByBatchType($batchType);
        }
        $comment = '';
        $explain = '';
        $isInFreeUserBuyList = D('BindChannel')->isInFreeUserBuyList($batchType);
        if ($isInFreeUserBuyList) {
            if ($isFreeUser) {
                $priceModel = json_decode($priceModel, true);
                $comment = '￥' . $priceModel['basicPrice'] . '（' .
                     $priceModel['duringTime'] . '天' . '）';
                $batchTypeNameArr = C('BATCH_TYPE_NAME');
                $batchTypeName = $batchTypeNameArr[$batchType];
                $explain = '<div class="explain">' . $batchTypeName .
                     '模块服务费以套餐形式按次收取，套餐价<em class="redfont">' .
                     $priceModel['basicPrice'] .
                     '元</em>，使用时长<em class="redfont">' .
                     $priceModel['duringTime'] .
                     '天</em>。活动时长超出<em class="redfont">' .
                     $priceModel['duringTime'] .
                     '天</em>的部分将以<em class="redfont">' . $priceModel['exPrice'] .
                     '元/天</em>，按天数收取。</div>';
                if ($batchType == CommonConst::BATCH_TYPE_WEEL) {
                    // 查询有没有指定类型的免费订单
                    $hasFreeOrder = $this->hasFreeActivity($nodeId, $batchType);
                    if (! $hasFreeOrder) {
                        $comment = '首次免费';
                        $explain = '<div class="explain">' . $batchTypeName .
                             '模块可免费创建一次（<em class="redfont">最长45天</em>），创建成功即送：<br />1、50旺币用于奖品发码，有效期<em class="redfont">45天</em>。<br />2、卡券验证助手用于奖品核验。</div>';
                    }
                }
            }
        }
        
        return array(
            'comment' => $comment, 
            'explain' => $explain);
    }

    /**
     * 根据活动编号获取编辑界面的url
     *
     * @param unknown $batch_id
     * @param unknown $batch_type
     * @return string
     */
    public function getEditUrl($batch_id, $batch_type) {
        if (empty($batch_type)) {
            $batch_type = M('tmarketing_info')->where(
                array(
                    'id' => $batch_id))->getField('batch_type');
        }
        $url = $this->where(
            array(
                'batch_type' => $batch_type))
            ->field('batch_create_url')
            ->find();
        $editUrl = U('Home/MarketActive/editUrl', 
            array(
                'actionName' => urlencode($url['batch_create_url']), 
                'id' => $batch_id));
        return $editUrl;
    }
}