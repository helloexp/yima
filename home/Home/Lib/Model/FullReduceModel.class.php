<?php

/**
 * 抽奖满减规则相关 model
 *
 * @author : John zeng<zengc@imageco.com.cn> Date: 2015/12/25
 */
class FullReduceModel extends BaseModel {
    protected $tableName = '__NONE__';
    /**
     *
     * @author : John zeng<zengc@imageco.com.cn> 获取满减规则
     * @param string $nodeId 商户唯一标识
     * @param string $mId 商品m_id
     * @param int $type 商品类型
     */
    public function getFullReduceRuleList($nodeId, $mId, $type) {
        $ruleList = array();
        $listInfo = self::getFullReduceList($nodeId);
        if ($listInfo) {
            foreach ($listInfo as $key => $val) {
                $mIdList = json_decode($val['goods_list'], true);
                if (isset($mIdList['goods_info'])) {
                    if (self::getMidInRule($mIdList['goods_info'], $type, $mId)) {
                        $ruleList[$key]['rule_data'] = json_decode(
                            $val['rule_data'], true);
                        $ruleList[$key]['goods_list'] = $mIdList['goods_info']['goods_list'];
                    }
                } else {
                    $ruleList = false;
                }
            }
        } else {
            $ruleList = false;
        }
        return $ruleList;
    }

    /**
     *
     * @author : John zeng<zengc@imageco.com.cn> 获取满减规则信息列表
     * @param string $nodeId 商户唯一标识
     */
    public function getFullReduceList($nodeId) {
        $listInfo = M('tfull_reduce_rule')->where(
            array(
                'node_id' => $nodeId, 
                'status' => '1'))->select();
        if ($listInfo) {
            return $listInfo;
        } else {
            return false;
        }
    }

    /**
     *
     * @author : John zeng<zengc@imageco.com.cn> 获取满减规则信息列表
     * @param array $info 商户选择满减商品信息
     * @param string $mId 商品m_id
     * @param int $type 商品类型
     */
    private function getMidInRule($info, $type, $mId) {
        $goodsList = explode(',', $info['goods_list']);
        $mId = explode(',', $mId);
        if (count($goodsList) > 0) {
            foreach ($mId as $val) {
                if (in_array($val, $goodsList)) {
                    return true;
                }
            }
        } else {
            return false;
        }
    }

    /**
     *
     * @author : John zeng<zengc@imageco.com.cn> 获取满减规则金额
     * @param array $info 取得的满减列表信息
     * @param array $amount 商品总金额
     * @return string $reduceAmount 满减金额
     */
    public function getReduceBonus($info, $amount) {
        // 初始满减金额为0元
        $reduceAmount = 0;
        $tmpAmount = array();
        if (is_array($amount) && count($info) > 0) {
            // 取得满减规则金额
            foreach ($amount as $key => $val) {
                // $fullAmount = ((int) $val / 100) * 100;
                $amountKey = self::getReduceDetailBonus($key, $info);
                if ('false' != $amountKey) {
                    if(isset($tmpAmount[$amountKey])){
                        $tmpAmount[$amountKey] = $tmpAmount[$amountKey] + $val;
                    }
                }
            }
            // 获取满减金额
            if (count($tmpAmount) > 0) {
                foreach ($tmpAmount as $key => $val) {
                    $key = (int) $key - 1;
                    $reduceAmount += self::getOneReduceBonus($key, $val, $info);
                }
                return $reduceAmount;
            }
        } else {
            return false;
        }
    }

    /**
     *
     * @author : John zeng<zengc@imageco.com.cn> 获取满减规则金额
     * @param array $info 取得的满减列表信息
     * @param string $mId 商品m_id
     * @return array $tmpArray 单笔需要减满减金额
     */
    private function getReduceDetailBonus($mId, $info) {
        $amountKey = 'false';
        foreach ($info as $key => $val) {
            if (in_array($mId, explode(',', $val['goods_list']))) {
                $amountKey = $key + 1;
            }
        }
        return $amountKey;
    }

    /**
     *
     * @author : John zeng<zengc@imageco.com.cn> 获取满减对应应减少金额
     * @param array $info 取得的满减列表信息
     * @param string $key $info内对应key值
     * @param string $amount 商品去零金额
     * @return array $tmpArray 单笔需要减满减金额
     */
    private function getOneReduceBonus($key, $amount, $info) {
        $min = 0;
        $max = 0;
        $returnMoney = 0;
        foreach ($info[$key]['rule_data'] as $val) {
            if ($amount >= $val['full_money']) {
                if ($val['full_money'] > $max) {
                    $max = $val['full_money'];
                    $returnMoney = $val['reduce_money'];
                }
            }
        }
        return $returnMoney;
    }
}    