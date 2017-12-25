<?php

/**
 *
 * @author lwb Time 20150906
 */
class RaiseFlagModel extends Model {
    protected $tableName = '__NONE__';
    public $limitDay = '20151018';

    public function __construct() {
        parent::__construct();
        import('@.Vendor.CommonConst') or die('include file fail.');
    }

    /**
     * 设置奖项中奖概率
     *
     * @param unknown $firstPrizeChance
     * @param unknown $secondPrizeChance
     * @param unknown $thirdPrizeChance
     */
    public function setPrizeChance($nodeId, $mId, $firstPrizeChance, 
        $secondPrizeChance, $thirdPrizeChance) {
        $mInfoModel = M('tmarketing_info');
        $map = array(
            'node_id' => $nodeId, 
            'id' => $mId);
        $data_config = $mInfoModel->where($map)->getField('config_data');
        $configData = unserialize($data_config);
        $configData['prizeChance'] = array(
            $firstPrizeChance, 
            $secondPrizeChance, 
            $thirdPrizeChance);
        $configData = serialize($configData);
        $data['config_data'] = $configData;
        $result = $mInfoModel->where($map)->save($data);
        if (false === $result) {
            throw_exception('保存中奖概率失败');
        }
    }

    /**
     * 获取中奖概率
     *
     * @param unknown $nodeId
     * @param unknown $mId
     */
    public function getPrizeChance($nodeId, $mId) {
        $mInfoModel = M('tmarketing_info');
        $map = array(
            'node_id' => $nodeId, 
            'id' => $mId);
        $data_config = $mInfoModel->where($map)->getField('config_data');
        $configData = unserialize($data_config);
        return $configData['prizeChance'];
    }

    /**
     * 是否符合限免条件
     *
     * @param unknown $nodeId
     * @return multitype:string
     */
    public function fitLimit($nodeId) {
        $isFreeUser = D('node')->getNodeVersion($nodeId);
        if ($isFreeUser) {
            if (date('Ymd') > $this->limitDay) {
                return array(
                    'error_num' => '1001', 
                    'error_info' => '对不起，本次免费活动已到期。<br>您只要成为标准版用户即可开展旺财平台的所有营销活动以及其它更多功能。');
            }
            // 之前的限免活动
            $batchTypeArr = array(
                CommonConst::BATCH_TYPE_FIRECRACKER, 
                CommonConst::BATCH_TYPE_LABORDAY, 
                CommonConst::BATCH_TYPE_MAMAWOAINI, 
                CommonConst::BATCH_TYPE_ZONGZI);
            // 是否参加过任何的限免活动
            $map = array(
                'node_id' => $nodeId, 
                'batch_type' => array(
                    'in', 
                    $batchTypeArr));
            $re = M('tmarketing_info')->where($map)
                ->field('id')
                ->find();
            if ($re) {
                return array(
                    'error_num' => '1002', 
                    'error_info' => '您已参加过其他限免活动了。');
            }
        }
        return array(
            'error_num' => '0000', 
            'error_info' => '');
    }

    /**
     * 获取免费用户的时间限制
     *
     * @return string
     */
    public function getFreeUseLimit() {
        $limitDay = date('Y-m-d', strtotime($this->limitDay));
        return $limitDay;
    }

    /**
     * 有没有创建过"我是升旗手"这个活动
     *
     * @param unknown $nodeId
     */
    public function hasJoined($nodeId) {
        $result = M('tmarketing_info')->where(
            array(
                'node_id' => $nodeId, 
                'batch_type' => CommonConst::BATCH_TYPE_RAISEFLAG))
            ->field('id')
            ->find();
        if ($result) {
            throw_exception(
                '“我是升旗手”活动仅可创建一次，<a href="' . U('MarketActive/Activity/MarketList') .
                     '">点击查看</a>已创建活动');
        }
    }

    /**
     * 发旺币
     *
     * @param unknown $nodeId
     * @return unknown
     */
    public function setWb($nodeId) {
        $giftResult = D('Wheel')->setWb($nodeId, 200, '20151031', 
            C('WB_FOR_RAISE_FLAG'));
        return $giftResult;
    }

    /**
     * 是否有人参与
     *
     * @param unknown $batch_id
     */
    public function hasPeopleJoined($batch_id) {
        $cnt = (int) M('twx_national_trace')->where(
            array(
                'batch_id' => $batch_id))->count();
        if ($cnt > 0) {
            return true;
        }
        return false;
    }
}