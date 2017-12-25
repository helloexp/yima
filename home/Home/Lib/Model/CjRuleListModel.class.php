<?php

/**
 *
 * @author lwb Time 20160414
 */
class CjRuleListModel extends Model {
    protected $tableName = '__NONE__';
    
    public function _initialize() {
        parent::_initialize();
        import('@.Vendor.CommonConst') or die('include file fail.');
    }
    
    /**
     * 获取奖品的总览列表数据
     * @param string $nodeId
     * @param int $mId
     * @return mixed
     */
    public function getJpList($nodeId, $mId) {
        $today = date('Ymd');
        $result = M('tcj_batch')
        ->field('cc.name,bi.batch_class,bi.batch_type,bi.batch_short_name,cjb.total_count,
            ifnull(cr.award_times, 0) as award_times,
            cjb.day_count,(bi.storage_num-bi.remain_num) as zj_count, bi.remain_num')
        ->alias('cjb')
        ->join('left join tbatch_info bi on bi.id = cjb.b_id and cjb.batch_id = bi.m_id')
        ->join('left join tcj_cate cc on cc.id = cjb.cj_cate_id')
        ->join("left join (select * from taward_daytimes td where td.trans_date = '{$today}') cr on cr.rule_id = cjb.id")
        ->where(
            array(
                'bi.m_id' => $mId,
                'cjb.node_id' => $nodeId
            )
        )
        ->select();
        return $result;
    }
    
    public function getTraceStatus($requestId, $batch_class = '', $goods_id = '') {
        $statusTxt = '';
        if ($batch_class == CommonConst::GOODS_TYPE_HF || $batch_class == CommonConst::GOODS_TYPE_QB) {//话费，q币
            $status = M('tphone_bills_trace')->where(array('request_id' => $requestId))->getField('status');
            switch ($status) {
                case 0:
                    $statusTxt = '未领取';
                    break;
                case 1:
                    $statusTxt = '申请领取';
                    break;
                case 2:
                    $statusTxt = '发放成功';
                    break;
                case 3:
                    $statusTxt = '发放失败';
                    break;
                case 4:
                    $statusTxt = '已失效';
                    break;
            }
        } elseif ($batch_class == CommonConst::GOODS_TYPE_HB) {//小店红包
            $bonusRes = M('tbonus_use_detail')->where(array('request_id' => $requestId))->find();
            $leftNum = $bonusRes['bonus_num'] - $bonusRes['bonus_use_num'];
            if ($leftNum == $bonusRes['bonus_num']) {
                $statusTxt = '未使用';
            } elseif ($leftNum == 0) {
                $statusTxt = '已使用';
            } else {
                $statusTxt = '使用中';
            }
        } elseif ($batch_class == CommonConst::GOODS_TYPE_JF) {//积分 
            $statusTxt = '已领取';
        } elseif ($batch_class == CommonConst::GOODS_TYPE_LLB) {//流量包
            $traceInfo = M('tmobile_date_send_trace')
            ->field('status,phone_no')
            ->where(array('request_id' => $requestId))
            ->order('add_time desc')
            ->find();
            switch ($traceInfo['status']) {
                case 0:
                    $statusTxt = '请求成功';
                    break;
                case 1:
                    $statusTxt = '请求失败';
                    break;
                case 2:
                    $statusTxt = '充值成功';
                    break;
                case 3:
                    $statusTxt = '充值失败';
                    break;
            }
            if (empty($traceInfo) || (isset($traceInfo['phone_no']) && $traceInfo['phone_no'] == '13900000000')) {
                $statusTxt = '中奖未领取';
            }
        } elseif ($batch_class == 0) {
            $source = M('tgoods_info')->where(array('goods_id' => $goods_id))->getField('source');
            //微信红包时
            if ($source == CommonConst::GOODS_SOURCE_SELF_CREATE_WXHB || $source == CommonConst::GOODS_SOURCE_YIMA_CREATE_WXHB) {
               $status = M('twx_bonus_send_trace')->where(array('request_id' => $requestId))->getField('status');
               switch ($status) {
                   case 0:
                       $statusTxt = '发放中';
                       break;
                   case 1:
                       $statusTxt = '发放失败';
                       break;
                   case 3:
                       $statusTxt = '已发放待领取';
                       break;
                   case 4:
                       $statusTxt = '已领取';
                       break;
                   case 5:
                       $statusTxt = '已退款';
                       break;
               }
            } else {
                $statusTxt = $this->getBarcodeTraceStatusTxt($requestId);
            }
        } else {
            $statusTxt = $this->getBarcodeTraceStatusTxt($requestId);
        }
        return $statusTxt;
    }
    
    /**
     * 获取奖品的回退记录
     * @param string $nodeId
     * @param int $mId
     * @param bool $getCount（返回回退记录数）
     * @return mixed
     */
    public function getBackRecord($nodeId, $mId, $getCount = false) {
        import("ORG.Util.Page");
        $cjBatch = M('tcj_batch')
        ->where(['node_id' => $nodeId, 'batch_id' => $mId])
        ->getField('id', true);
        $trace = '';
        $count = 0;
        if (!empty($cjBatch)) {
            $count = M('tgoods_storage_trace')
            ->alias('gst')
            ->where(
                [
                    'gst.relation_id' => ['in', $cjBatch],
                    'gst.opt_type' => CommonConst::GOODS_STORAGE_TRACE_OPT_TYPE_FOR_BATCH_BACK,
                    'gst.node_id' => $nodeId
                ]
            )//活动回退奖品的类型
            ->count();
            //如果要返回统计个数
            if ($getCount) {
                return $count;
            }
            
            $p = new Page($count, 8);
            foreach ($_REQUEST as $key => $val) {
                $p->parameter[$key] = urlencode($val); // 赋值给Page
            }
            
            
            $trace = M('tgoods_storage_trace')
            ->alias('gst')
            ->field('bi.batch_short_name,gst.change_num,gst.add_time,gst.user_id,ui.user_name,ui.true_name,gi.goods_id')
            ->join('tuser_info as ui on gst.user_id = ui.user_id')
            ->join('tcj_batch cb on cb.id = gst.relation_id')
            ->join('tbatch_info bi on bi.id = cb.b_id and bi.m_id = ' . $mId)
            ->join('tgoods_info gi on gst.goods_id = gi.id')
            ->where(
                [
                    'gst.relation_id' => ['in', $cjBatch],
                    'gst.opt_type' => CommonConst::GOODS_STORAGE_TRACE_OPT_TYPE_FOR_BATCH_BACK,
                    'gst.node_id' => $nodeId
                ]
            )//活动回退奖品的类型
            ->limit($p->firstRow, $p->listRows)
            ->select();
        } else {
            //如果要返回统计个数
            if ($getCount) {
                return $count;
            }
            $p = new Page(0, 8);
        }
        return array(
            'trace' => $trace, 
            'p' => $p
        );
    }
    
    public function getPrizeSetUrl($mId) {
        $batchType = M('tmarketing_info')->where(['id' => $mId])->getField('batch_type');
        switch ($batchType) {
            case '9'://优惠券
                $url = '';
                break;
            case '45'://劳动节
                $url = U('LabelAdmin/LaborDayCjSet/index',array('batch_id'=>$mId));
                break;
            case '44'://大腕
                $url = U('LabelAdmin/Dawan/cjset',array('batch_id'=>$mId));
                break;
            case '28'://七夕
                $url = U('LabelAdmin/Qixi/setPrize',array('m_id'=>$mId));
                break;
            case '53'://大转盘
                $url = U('LabelAdmin/DrawLotteryAdmin/setPrize',array('m_id'=>$mId));
                break;
            case '56'://升旗手
                $url = U('LabelAdmin/RaiseFlag/setPrize',array('m_id'=>$mId));
                break;
            case '59'://双蛋
                $url = U('LabelAdmin/TwoFestivalAdmin/setPrize',array('m_id'=>$mId));
                break;
            case '60'://金猴闹新春
                $url = U('LabelAdmin/SpringMonkey/setPrize',array('m_id'=>$mId));
                break;
            default:
                $url = U('LabelAdmin/CjSet/index',array('batch_id'=>$mId));
                break;
        }
        return $url;
    }
    
    /**
     * 获取barcode_trace的状态
     * @param str $requestId
     * @return int
     */
    private function getBarcodeTraceStatus($requestId) {
        $result = M('tbarcode_trace')
        ->field('use_status')
        ->where(array('request_id' => $requestId))
        ->order('id desc')
        ->find();
        return $result['use_status'];
    }
    
    /**
     * 获取普通卡券（barcode_trace）的使用状态
     * @param int $requestId
     * @return string
     */
    private function getBarcodeTraceStatusTxt($requestId) {
        $status = $this->getBarcodeTraceStatus($requestId);
        $statusTxt = '';
        switch ($status) {
            case 0:
                $statusTxt = '未使用';
                break;
            case 1:
                $statusTxt = '使用中';
                break;
            case 2:
                $statusTxt = '已使用';
                break;
        }
        return $statusTxt;
    }
}