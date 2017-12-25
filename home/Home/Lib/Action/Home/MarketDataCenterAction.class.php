<?php

/**
 * 营销数据中心
 *
 * @author bao
 */
class MarketDataCenterAction extends BaseAction {

    public function _initialize() {
        parent::_initialize();
        if ($this->nodeType == 2) {
            $this->display(
                './Home/Tpl/Introduce/Introduce_noPowerDataCenter.html');
            exit();
            redirect(U('Home/Introduce/datacenter')); // 跳转到服务介绍页面
        }
    }
    
    // 营销活动统计
    public function activityCount() {
        // 营销活动数统计
        // 拍码阅读活动总数
        $newCount = M('tmarketing_info')->where(
            "node_id='{$this->nodeId}' and batch_type= '2'")->count();
        // 拍码调研活动总数
        $bmCount = M('tmarketing_info')->where(
            "node_id='{$this->nodeId}' and batch_type= '3'")->count();
        // 粉丝招募
        $memberCount = M('tmarketing_info')->where(
            "node_id='{$this->nodeId}' and batch_type= '4'")->count();
        // 爱拍
        $aiPaiCount = 1;
        // 优惠券
        $couponCount = M('tmarketing_info')->where(
            "node_id='{$this->nodeId}' and batch_type= '9'")->count();
        // 有奖答题
        $answersCount = M('tmarketing_info')->where(
            "node_id='{$this->nodeId}' and batch_type= '10'")->count();
        
        $activitySum = "['抽奖',{$newCount}],['市场调研',{$bmCount}],['粉丝招募',{$memberCount}],['爱拍',{$aiPaiCount}],['优惠券',{$couponCount}],['有奖答题',{$answersCount}]";
        
        // 拍码阅读进行中活动
        $newingCount = M('tmarketing_info')->where(
            "node_id='{$this->nodeId}' and batch_type='2' AND status=1")->count();
        // 拍码调研进行中活动
        $bmingCount = M('tmarketing_info')->where(
            "node_id='{$this->nodeId}' and batch_type='3' AND status=1")->count();
        // 粉丝招募进行中活动
        $meingCount = M('tmarketing_info')->where(
            "node_id='{$this->nodeId}' and batch_type= '4' AND status=1")->count();
        // 爱拍
        $aiingCount = 1;
        // 优惠券进行中活动
        $couponingCount = M('tmarketing_info')->where(
            "node_id='{$this->nodeId}' and batch_type= '9' AND status=1")->count();
        // 有奖答题进行中活动
        $answersingCount = M('tmarketing_info')->where(
            "node_id='{$this->nodeId}' and batch_type= '10' AND status=1")->count();
        
        $this->assign('activitySum', $activitySum); // 图表统计数据
        $this->assign('totalCount', 
            ($newCount + $bmCount + $memberCount + $aiPaiCount));
        $this->assign('totalingCount', 
            ($newingCount + $bmingCount + $meingCount + $aiingCount +
                 $couponingCount + $answersingCount));
        
        // 访问量统计
        // 拍码阅读访问量统计
        $newVisitCount = M('tmarketing_info')->where(
            "node_id='{$this->nodeId}' and batch_type= '2'")->sum('click_count');
        $newVisitCount = empty($newVisitCount) ? 0 : $newVisitCount;
        // 拍码调研访问量统计
        $bmVisitCount = M('tmarketing_info')->where(
            "node_id='{$this->nodeId}' and batch_type= '3'")->sum('click_count');
        $bmVisitCount = empty($bmVisitCount) ? 0 : $bmVisitCount;
        // 粉丝招募访问量统计
        $meVisitCount = M('tmarketing_info')->where(
            "node_id='{$this->nodeId}' and batch_type= '4'")->sum('click_count');
        $meVisitCount = empty($meVisitCount) ? 0 : $meVisitCount;
        // 爱拍
        $aiVisitCount = M('tpp_batch')->where("node_id='{$this->nodeId}'")->sum(
            'visit_num');
        $aiVisitCount = empty($aiVisitCount) ? 0 : $aiVisitCount;
        // 优惠券访问量统计
        $couponVisitCount = M('tmarketing_info')->where(
            "node_id='{$this->nodeId}' and batch_type= '9'")->sum('click_count');
        $couponVisitCount = empty($couponVisitCount) ? 0 : $couponVisitCount;
        // 有奖答题访问量统计
        $answersVisitCount = M('tmarketing_info')->where(
            "node_id='{$this->nodeId}' and batch_type= '10'")->sum('click_count');
        $answersVisitCount = empty($answersVisitCount) ? 0 : $answersVisitCount;
        
        if ($newVisitCount == 0 && $bmVisitCount == 0 && $meVisitCount == 0 &&
             $aiVisitCount == 0 && $couponVisitCount == 0 &&
             $answersVisitCount == 0) {
            $this->assign('visitsum', 1);
        }
        
        $visitCount = "['抽奖',{$newVisitCount}],['市场调研',{$bmVisitCount}],['粉丝招募',{$meVisitCount}],['爱拍',{$aiVisitCount}],['优惠券',{$couponVisitCount}],['有奖答题',{$answersVisitCount}]";
        $this->assign('visitCount', $visitCount);
        
        // 中奖人数统计
        // 拍码阅读发码量统计
        $newWinCount = M('tmarketing_info')->where(
            "node_id='{$this->nodeId}' and batch_type= '2'")->sum('send_count');
        $newWinCount = empty($newWinCount) ? 0 : $newWinCount;
        // 拍码调研发码量统计
        $bmWinCount = M('tmarketing_info')->where(
            "node_id='{$this->nodeId}' and batch_type= '3'")->sum('send_count');
        $bmWinCount = empty($bmWinCount) ? 0 : $bmWinCount;
        // 粉丝招募发码量统计
        $meWinCount = M('tmarketing_info')->where(
            "node_id='{$this->nodeId}' and batch_type= '4'")->sum('send_count');
        $meWinCount = empty($meWinCount) ? 0 : $meWinCount;
        // 爱拍
        $aiWinCount = M('tpp_batch')->where("node_id='{$this->nodeId}'")->sum(
            'send_num');
        $aiWinCount = empty($aiWinCount) ? 0 : $aiWinCount;
        // 优惠券
        $couponWinCount = M('tmarketing_info')->where(
            "node_id='{$this->nodeId}' and batch_type= '9'")->sum('send_count');
        $couponWinCount = empty($couponWinCount) ? 0 : $couponWinCount;
        // 有奖答题
        $answersWinCount = M('tmarketing_info')->where(
            "node_id='{$this->nodeId}' and batch_type= '10'")->sum('send_count');
        $answersWinCount = empty($answersWinCount) ? 0 : $answersWinCount;
        if ($newWinCount == 0 && $bmWinCount == 0 && $meWinCount == 0 &&
             $aiWinCount == 0 && $couponWinCount == 0 && $answersWinCount == 0) {
            $this->assign('winsnum', 1);
        }
        
        $winCount = "['抽奖',{$newWinCount}],['市场调研',{$bmWinCount}],['粉丝招募',{$meWinCount}],['爱拍',{$aiWinCount}],['优惠券',{$couponWinCount}],['有奖答题',{$answersWinCount}]";
        $this->assign('winCount', $winCount);
        
        // 参与人数统计
        // 拍马阅读参与人数统计
        $newJoinCount = M('tcj_trace')->where(
            "node_id='{$this->nodeId}' AND batch_type=2")->count();
        // 拍码调研参与人数统计
        $bmJoinCount = M('tcj_trace')->where(
            "node_id='{$this->nodeId}' AND batch_type=3")->count();
        // 粉丝招募参与人数统计
        $meJoinCount = M('tcj_trace')->where(
            "node_id='{$this->nodeId}' AND batch_type=4")->count();
        // 爱拍参与人数
        $aijoinCount = M('tbatch_channel')->where(
            "node_id='{$this->nodeId}' AND batch_type=5")->sum('cj_count');
        $aijoinCount = empty($aijoinCount) ? 0 : $aijoinCount;
        // 优惠券参与人数统计
        $couponJoinCount = M('tcj_trace')->where(
            "node_id='{$this->nodeId}' AND batch_type=9")->count();
        // 有奖答题参与人数统计
        $answersJoinCount = M('tcj_trace')->where(
            "node_id='{$this->nodeId}' AND batch_type=10")->count();
        
        if ($newJoinCount == 0 && $bmJoinCount == 0 && $meJoinCount == 0 &&
             $aijoinCount == 0 && $couponJoinCount == 0 && $answersJoinCount == 0) {
            $this->assign('goodsnum', 1);
        }
        
        $joinCount = "['抽奖',{$newJoinCount}],['市场调研',{$bmJoinCount}],['粉丝招募',{$meJoinCount}],['爱拍',{$aijoinCount}],['优惠券',{$couponJoinCount}],['有奖答题',{$aijoinCount}]";
        
        node_log("首页+营销数据中心");
        $this->assign('joinCount', $joinCount);
        
        $this->display();
    }
    
    // 卡券数据统计
    public function numgoodsCount() {
        // 数字化商品数量统计
        // 优惠券数量
        $numGoodsCount = M('tgoods_info')->field('goods_type,COUNT(*) as count')
            ->where("node_id='{$this->nodeId}'")
            ->group('goods_type')
            ->select();
        $couponCount = 0;
        $vouchersCount = 0;
        $physicalCount = 0;
        foreach ($numGoodsCount as $v) {
            switch ($v['goods_type']) {
                case 0:
                    $couponCount = $v['count'];
                    break;
                case 1:
                    $vouchersCount = $v['count'];
                    break;
                case 2:
                    $physicalCount = $v['count'];
                    break;
            }
        }
        if ($couponCount == 0 && $vouchersCount == 0 && $physicalCount == 0) {
            $this->assign('numgoods', 1);
        }
        $numgoodsNum = "['优惠券',{$couponCount}],['代金券',{$vouchersCount}],['提领券',{$physicalCount}]";
        $this->assign('numgoodsNum', $numgoodsNum);
        // 优惠券，代金券，实物卷发码量
        // 优惠券发码量
        $couponSendNum = M()->table('tbatch_info i')
            ->join('tpos_day_count c ON i.batch_no = c.batch_no')
            ->where("i.node_id='{$this->nodeId}' AND i.batch_class=0")
            ->sum("c.send_num");
        $couponSendNum = empty($couponSendNum) ? 0 : $couponSendNum;
        // 代金券数量
        $vouchersSendNum = M()->table('tbatch_info i')
            ->join('tpos_day_count c ON i.batch_no = c.batch_no')
            ->where("i.node_id='{$this->nodeId}' AND i.batch_class=1")
            ->sum("c.send_num");
        $vouchersSendNum = empty($vouchersSendNum) ? 0 : $vouchersSendNum;
        // 提领券数量
        $physicalSendNum = M()->table('tbatch_info i')
            ->join('tpos_day_count c ON i.batch_no = c.batch_no')
            ->where("i.node_id='{$this->nodeId}' AND i.batch_class=2")
            ->sum("c.send_num");
        $physicalSendNum = empty($physicalSendNum) ? 0 : $physicalSendNum;
        
        if ($couponSendNum == 0 && $vouchersSendNum == 0 && $physicalSendNum == 0) {
            $this->assign('sendnumgoods', 1);
        }
        
        $sendNum = "['优惠券',{$couponSendNum}],['代金券',{$vouchersSendNum}],['提领券',{$physicalSendNum}]";
        $this->assign('sendNum', $sendNum);
        
        // 优惠券，代金券，实物卷验码量
        // 优惠券发码量
        $couponVerifyNum = M()->table('tbatch_info i')
            ->join('tpos_day_count c ON i.batch_no = c.batch_no')
            ->where("i.node_id='{$this->nodeId}' AND i.batch_class=0")
            ->sum("c.verify_num");
        $couponVerifyNum = empty($couponVerifyNum) ? 0 : $couponVerifyNum;
        // 代金券数量
        $vouchersVerifyNum = M()->table('tbatch_info i')
            ->join('tpos_day_count c ON i.batch_no = c.batch_no')
            ->where("i.node_id='{$this->nodeId}' AND i.batch_class=1")
            ->sum("c.verify_num");
        $vouchersVerifyNum = empty($vouchersVerifyNum) ? 0 : $vouchersVerifyNum;
        // 提领券数量
        $physicalVerifyNum = M()->table('tbatch_info i')
            ->join('tpos_day_count c ON i.batch_no = c.batch_no')
            ->where("i.node_id='{$this->nodeId}' AND i.batch_class=2")
            ->sum("c.verify_num");
        $physicalVerifyNum = empty($physicalVerifyNum) ? 0 : $physicalVerifyNum;
        
        if ($couponVerifyNum == 0 && $vouchersVerifyNum == 0 &&
             $physicalVerifyNum == 0) {
            $this->assign('verifynumgoods', 1);
        }
        
        $verifyNum = "['优惠券',{$couponVerifyNum}],['代金券',{$vouchersVerifyNum}],['提领券',{$physicalVerifyNum}]";
        $this->assign('verifyNum', $verifyNum);
        
        // 优惠券，代金券，实物卷撤消量
        // 优惠券撤消量
        $couponcanCelNum = M()->table('tbatch_info i')
            ->join('tpos_day_count c ON i.batch_no = c.batch_no')
            ->where("i.node_id='{$this->nodeId}' AND i.batch_class=0")
            ->sum("c.cancel_num");
        $couponcanCelNum = empty($couponcanCelNum) ? 0 : $couponcanCelNum;
        // 代金券撤消数量
        $vouchersCancelNum = M()->table('tbatch_info i')
            ->join('tpos_day_count c ON i.batch_no = c.batch_no')
            ->where("i.node_id='{$this->nodeId}' AND i.batch_class=1")
            ->sum("c.cancel_num");
        $vouchersCancelNum = empty($vouchersCancelNum) ? 0 : $vouchersCancelNum;
        // 提领券撤消数量
        $physicalCancelNum = M()->table('tbatch_info i')
            ->join('tpos_day_count c ON i.batch_no = c.batch_no')
            ->where("i.node_id='{$this->nodeId}' AND i.batch_class=2")
            ->sum("c.cancel_num");
        $physicalCancelNum = empty($physicalCancelNum) ? 0 : $physicalCancelNum;
        
        if ($couponcanCelNum == 0 && $vouchersCancelNum == 0 &&
             $physicalCancelNum == 0) {
            $this->assign('cancelnumgoods', 1);
        }
        
        $cancelNum = "['优惠券',{$couponcanCelNum}],['代金券',{$vouchersCancelNum}],['提领券',{$physicalCancelNum}]";
        $this->assign('cancelNum', $cancelNum);
        
        $this->display();
    }
    
    // 渠道统计
    public function chancelCount() {
        $channelData = M('tchannel')->field('name,click_count')
            ->where("node_id='{$this->nodeId}'")
            ->select();
        // echo M()->getLastSql();exit;
        $channelData = array_sort($channelData, 'click_count');
        $clickCount = '';
        if ($channelData) {
            foreach ($channelData as $k => $v) {
                if ($v['click_count'] != 0) {
                    $clickCount .= "['{$v['name']}',{$v['click_count']}],";
                }
            }
        }
        $this->assign('clickCount', substr($clickCount, 0, - 1));
        // 渠道参与数统计
        $channelData = M()->table('tchannel c')
            ->field('c.name,COUNT(*) as count')
            ->join("tcj_trace t ON c.id=t.channel_id")
            ->where("c.node_id='{$this->nodeId}'")
            ->group('c.id')
            ->select();
        
        $joinCount = '';
        $joinnum = 0;
        if ($channelData) {
            foreach ($channelData as $k => $v) {
                $joinnum += $v['count'];
                if ($k == 0) {
                    $joinCount .= "['{$v['name']}',{$v['count']}]";
                    continue;
                }
                $joinCount .= ",['{$v['name']}',{$v['count']}]";
            }
        }
        if ($joinnum == 0) {
            $this->assign('joinnum', 1);
        }
        $this->assign('joinCount', $joinCount);
        // 渠道中奖数统计
        $channelData = M()->table('tchannel c')
            ->field('c.name,COUNT(*) as count')
            ->join("tcj_trace t ON c.id=t.channel_id")
            ->where("c.node_id='{$this->nodeId}' AND t.status=2")
            ->group('c.id')
            ->select();
        // echo M()->getLastSql();exit;
        $winCount = '';
        $winnum = 0;
        if ($channelData) {
            foreach ($channelData as $k => $v) {
                $winnum += $v['count'];
                if ($k == 0) {
                    $winCount .= "['{$v['name']}',{$v['count']}]";
                    continue;
                }
                $winCount .= ",['{$v['name']}',{$v['count']}]";
            }
        }
        if ($winnum == 0) {
            $this->assign('winnum', 1);
        }
        // echo $winCount;exit;
        $this->assign('winCount', $winCount);
        
        $this->display();
    }
}
