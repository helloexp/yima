<?php

/**
 * 计费项数据操作
 *
 * @author bao
 */
class TchargeInfoModel extends Model {

    /**
     * 获取指定计费项信息
     *
     * @param $chargeId 计费项id
     * @return 计费项信息
     */
    public function getChargeInfo($chargeId) {
        return $this->where("charge_id='{$chargeId}'")->find();
    }

    /**
     * 获取礼包单项中的套餐信息
     *
     * @param $chargeId 礼包id
     * @return arr 套餐信息
     */
    public function getPackagesInfo($chargeId) {
        $packagesInfo = $this->Table('tcharge_relation r')
            ->join('tcharge_info i ON r.relation_id=i.charge_id')
            ->where("r.charge_id='{$chargeId}' AND i.charge_type=1")
            ->find();
        return $packagesInfo;
    }

    /**
     * 获取指定类型的所有计费项信息
     *
     * @param $type 计费项类型 0-礼包 1-套餐 2-服务
     * @param $chargeLevel 计费项所属级别 1 商户 2 终端 null所有
     * @return 所有指定类型的计费项信息
     */
    public function getChargeInfoByType($type, $chargeLevel = null) {
        is_null($chargeLevel) ? $where = array(
            'charge_type' => $type) : $where = array(
            'charge_type' => $type, 
            'charge_level' => $chargeLevel);
        return $chargeList = $this->where($where)->select();
    }

    /**
     * 得到指定商户下的所有商户服务或商户购买终端服务的开通状态
     *
     * @param $nodeId 商户号
     * @param $chargeLevel 计费项所属级别 1 商户 2 终端
     * @param $status 业务状态 0 正常 1 停用
     * @return 所有服务的开通状态
     */
    public function getServiceStatus($nodeId, $chargeLevel, $status = 0) {
        // 获取所有服务
        $allServiceInfo = $this->getChargeInfoByType(2, $chargeLevel);
        if ($allServiceInfo) {
            // 获取已开通的服务
            $onServiceInfo = M('tnode_charge')->field('charge_id')
                ->where(
                "node_id={$nodeId} AND status={$status} AND charge_level={$chargeLevel}")
                ->select();
            if (! $onServiceInfo) {
                return $allServiceInfo;
            }
        }
    }
}
























