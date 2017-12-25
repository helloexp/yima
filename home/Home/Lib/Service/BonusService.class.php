<?php

/*
 * 这儿封装多宝电商红包的不同使用函数 包括获取可使用红包金额，可使用红包列表，红包使用更新
 */
class BonusService {

    public $opt = array();

    public function __construct() {
    }

    /* 设置参数 */
    public function setopt() {
    }

    /* 获取可使用红包金额 */
    public function getUseBonus($node_id, $order_amt) {
        $reAmount = 0;
        if ($order_amt <= 0)
            return $reAmount;
        if (! $node_id)
            return $reAmount;
            // 查询规则
        $where = array(
            "node_id" => $node_id, 
            "status" => '1', 
            "rev_amount" => array(
                'elt', 
                $order_amt));
        
        $use_amt = M('tbonus_rules')->where($where)
            ->order('rev_amount desc')
            ->getField('use_amount');
        // 取得总规则信息
        $ruleType = D('SalePro', 'Service')->getNodeRule($node_id);
        switch ($ruleType) {
            case 1: // 不限制使用红包
                $reAmount = $order_amt;
                break;
            case 2: // 限制使用红包
                $reAmount = $use_amt;
                break;
            default: // 不使用红包
                $reAmount = 0;
                break;
        }
        return $reAmount;
    }
    
    // 获取用户红包数据
    public function getUserBonus($phone, $node_id) {
        // 查询此用户的红包
        if (! $phone) {
            return array();
        } else {
            $currentDay = date("YmdHis");
            $where = array(
                "b.node_id" => $node_id, 
                // "b.status"=>'1', //红包活动停止后已领红包仍可试用
                // "f.status"=>'1',
                "b.phone" => $phone);
            // $where['_string'] = "(b.bonus_num-b.bonus_use_num)>0 AND
            // i.bonus_end_time>='".$currentDay."' AND
            // f.end_time>='".$currentDay."'";
            $where['_string'] = "(b.bonus_num-b.bonus_use_num)>0 AND i.bonus_end_time>='" .
                 $currentDay . "'";
            $userBonusList = M()->table('tbonus_use_detail b ')
                ->field('b.*,m.amount,i.bonus_end_time')
                ->join("tbonus_detail m on m.id=b.bonus_detail_id")
                ->join("tbonus_info i on i.id=m.bonus_id")
                ->join("tmarketing_info f on f.id=b.m_id")
                ->order("f.end_time asc")
                ->where($where)
                ->select();
            return $userBonusList;
        }
    }

    /*
     * 下订单实际可减去红包金额 $bonus_use_id tbonus_use_detail id串 1,2,3,4
     */
    public function orderCutBonus($phone, $bonus_use_id) {
        if (! $phone)
            return 0;
        $detailArr = explode($bonus_use_id, ",");
        if (empty($detailArr)) {
            return 0;
        }
        $where = array(
            "b.id" => array(
                'in', 
                $bonus_use_id), 
            // "b.status"=>'1', //红包活动停止后已领红包仍可试用
            "b.phone" => $phone);
        $where['_string'] = " (b.bonus_num-b.bonus_use_num)>0 ";
        $bonusAmount = M()->table('tbonus_use_detail b ')
            ->field('sum(m.amount) as amount')
            ->join("tbonus_detail m on m.id=b.bonus_detail_id")
            ->where($where)
            ->find();
        return $bonusAmount['amount'];
    }
    
    // 使用红包，更新红包使用明细
    public function useBonus($bonus_use_id, $order_id, $cut_amt, $node_id, 
        $order_amt) {
        if ($bonus_use_id == "") {
            return false;
        }
        // 记录面额ID
        $detail_id = 0;
        $bonusUseList = explode(",", $bonus_use_id);
        if ($bonusUseList) {
            $allBonusAmt = M()->table('tbonus_use_detail t')
                ->join('tbonus_detail d ON d.id=t.bonus_detail_id')
                ->where(
                array(
                    't.id' => array(
                        'in', 
                        $bonusUseList)))
                ->getField('sum(d.amount)');
            foreach ($bonusUseList as $item => $val) {
                $bonusMap = array(
                    "t.id" => $val);
                $bonusMap['_string'] = " (t.bonus_num-t.bonus_use_num)>0 ";
                // 查询红包数据
                $useInfo = M()->table('tbonus_use_detail t')
                    ->join('tbonus_detail d ON d.id=t.bonus_detail_id')
                    ->where($bonusMap)
                    ->field('t.*,d.amount')
                    ->find();
                if ($useInfo) {
                    $detail_id = $useInfo['bonus_detail_id'];
                    $bonusData['t.bonus_use_num'] = array(
                        'exp', 
                        't.bonus_use_num+1');
                    $bonusData['t.order_id'] = $order_id;
                    $bonusData['t.bonus_amount'] = $cut_amt;
                    $bonusData['t.order_amt_per'] = $order_amt *
                         ($useInfo['amount'] / $allBonusAmt);
                    $bonusData['t.use_time'] = date('YmdHis');
                    
                    $res = M()->table('tbonus_use_detail t')
                        ->where($bonusMap)
                        ->save($bonusData);
                    if ($res === false) {
                        return false;
                    }
                    
                    // 更新使用bonus_detail
                    $detailMap = array(
                        "id" => $detail_id, 
                        "node_id" => $node_id);
                    $detailres = M('tbonus_detail')->where($detailMap)->setInc(
                        'use_num', 1);
                    
                    if ($detailres === false) {
                        return false;
                    }
                }
            }
            return true;
        } else {
            return false;
        }
    }
}

