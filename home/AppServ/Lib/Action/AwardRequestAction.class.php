<?php

/**
 *  todo 要管
 * 功能：抽奖接口
 *
 * @author siht 时间：2013-07-31
 */
class AwardRequestAction extends BaseAction
{
    // 接口参数
    public $node_id;// 机构号
    public $batch_id; // 抽奖id
    public $total_rate; //总中奖率
    public $award_type;    // 抽奖类型
    public $award_times;   // 单号码每日中奖数
    public $award_count;   // 单号码总中奖数
    public $day_part;    // 单号码每日参与数
    public $total_part;   // 单号码总参与数
    public $phone_no;    // 抽奖手机号
    public $label_id;    // 标签id
    public $channel_id;    // 渠道id
    public $batch_type;    // 营销活动来源
    public $ip;    // 抽奖ip
    public $cj_rule_id;    // 抽奖规则id
    public $ticket_seq;    // 抽奖小票流水号
    public $ticket_limit_num;    // 单小票允许抽奖数
    public $store_id;    // 门店编号
    public $wx_wap_ranking_id;    // 圣诞雪球抽奖积分记录id
    public $wx_cjcate_id;    // 圣诞雪球抽奖cate_id;
    public $wx_cjbatch_id;    // 指定奖品
    public $open_id;    // 平安非标
    public $wx_nick;    // 微信昵称
    public $wx_open_id;    // 微信卡券open_id
    public $wx_card_info = null;    // 微信卡券JSAPI信息
    public $join_mode;    // 活动的参与方式 0-手机号 1-微信
    public $send_mobile;    // 发奖号码
    public $send_mode;    // 发奖号码类型
    public $request_id;    // 向发码接口请求的流水号
    public $cj_trace_id;    // 抽奖流水保存的ID
    public $pay_token;    // 付满送token
    public $save_request_id;    // 向发码接口请求的流水号
    public $bonus_use_detail_id;    // tbonus_use_detail.id
    public $integral_get_id;    // tintegal_get_detail.id
    public $b_id;
    public $g_id;
    public $memberInfo = null;
    public $memberId = null;

    public $fbYhbFlag = false;    // 异步改造
    public $full_id;    // 标签列表
    public $participation_id;
    private $pay_give_order_info;    // 付满送订单信息

    public function _initialize()
    {

        parent::_initialize();
        C('AwardRequest', require(CONF_PATH . 'configAwardRequest.php'));

        $this->node_id           = I('node_id'); // 机构号
        $this->batch_id          = I('batch_id'); // 抽奖id
        $this->total_rate        = I('total_rate'); // 总中奖率
        $this->award_type        = I('award_type'); // 抽奖类型
        $this->award_times       = I('award_times', 1); // 单号码每日中奖数
        $this->award_count       = I('award_count', 0); // 单号码总中奖数
        $this->day_part          = I('day_part', 0); // 单号码每日参与数
        $this->total_part        = I('total_part', 0); // 单号码总参与数
        $this->phone_no          = I('phone_no'); // 抽奖手机号
        $this->label_id          = I('label_id'); // 标签id
        $this->channel_id        = I('channel_id'); // 渠道id
        $this->batch_type        = I('batch_type'); // 营销活动类型
        $this->ip                = I('ip'); // 抽奖ip
        $this->cj_rule_id        = I('cj_rule_id'); // 抽奖规则id
        $this->ticket_seq        = I('ticket_seq'); // 抽奖小票流水号
        $this->ticket_limit_num  = I('ticket_limit_num'); // 单小票允许抽奖数
        $this->wx_wap_ranking_id = I('wx_wap_ranking_id'); // 圣诞雪球抽奖积分记录id
        $this->wx_cjcate_id      = I('wx_cjcate_id'); // 圣诞雪球抽奖cate_id;
        $this->wx_cjbatch_id     = I('wx_cjbatch_id'); // 圣诞雪球抽奖cate_id;
        $this->open_id           = I('open_id'); // 平安非标
        $this->wx_open_id        = I('wx_open_id'); // 微信卡券open_id
        $this->wx_nick           = I('wx_nick'); // 微信昵称
        $this->pay_token         = I('pay_token'); // 付满送token
        if ($this->open_id == null) { // 平安非标，如果open_id为空 , 取wx_open_id 进行覆盖
            $this->open_id = $this->wx_open_id;
        }
        $this->fbYhbFlag        = $this->node_id == C('Yhb.node_id');
        $this->full_id          = I('full_id'); // 标签列表
        $this->participation_id = I('participation_id');
        // 获取请求流水号
        $this->save_request_id = award_reqid();
    }

    public function run()
    {
        // 加载自设置
        $info = C('AwardRequest');

        if (!$info) {
            // 尚未配置接口，接口不对外
            $resp_desc = "非法访问！";
            $this->returnError($resp_desc);
            exit();
        }

        // 获取接入端IP
        $ip    = $_SERVER['REMOTE_ADDR'];
        $ipArr = explode(',', $info['IMPORT_IP']);
        if (!in_array($ip, $ipArr)) {
            // IP不允许接入
            $resp_desc = "IP:" . $ip . "不允许接入";
            $this->returnError($resp_desc);
        }

        $rs = $this->check();
        if ($rs !== true) {
            $this->returnError($rs['resp_desc']);
        }
        // 接口全部转换为多奖品抽奖
        $this->multipleAward();
    }

    // 添加中奖日统计
    private function update_awardday($award_info)
    {
        $where = "RULE_ID ='" . $award_info['id'] . "' and trans_date = '" . date('Ymd') . "'";
        $rs    = M('taward_daytimes')->where($where)->setInc('award_times');
        if ($rs === false) { // log no exit
            $this->log("更新统计信息[taward_daytimes]失败" . M()->_sql());
            return false;
        }
        return true;
    }

    // 奖品发送
    private function send_award($awardInfo, $resp_array = [])
    {
        $RemoteRequest    = D('RemoteRequest', 'Service');
        $TransactionID    = $this->save_request_id; // 凭证发送单号
        $this->request_id = $TransactionID;

        if ($awardInfo['award_origin'] == '2') { // 发旺财
            if ($this->send_mode == '1') { // 微信卡券发送
                if ($this->wx_open_id == null) {
                    M()->rollback();
                    $this->saveCjTrace($awardInfo, 1, $resp_array['resp_desc']);
                    $this->returnError('旺财发码失败-微信卡券发码没有open_id:' . $resp_array['resp_desc'], '1301');
                }
                $data_from  = $this->batch_type + 1;
                $req_data   = "&node_id=" . $this->node_id . "&open_id=" . $this->wx_open_id . "&batch_info_id=" . $awardInfo['b_id'] . "&data_from=" . $data_from . "&request_id=" . $TransactionID;
                $resp_array = $RemoteRequest->requestWcWeiXinServ($req_data);
                $this->log("send to requestWcWeiXinServ" . print_r($req_data, true));
                $this->log("recv from requestWcWeiXinServ" . print_r($resp_array, true));
                if (!$resp_array || ($resp_array['resp_id'] != '0000' && $resp_array['resp_id'] != '0001')) {
                    M()->rollback();
                    $this->saveCjTrace($awardInfo, 1, $resp_array['resp_desc']);
                    $this->returnError('旺财发码失败1:' . $resp_array['resp_desc'], $resp_array['resp_id']);
                } else {
                    $this->wx_card_info = $resp_array['resp_data']['card_info'];
                }
            } else {
                $imsFlag     = '1';
                $sendPhoneNo = $this->phone_no;
                if ($this->join_mode == '1') { // 微信参与、手机发送需要特殊处理，先制卡，后转发
                    // 翼惠宝是同时将openid和phone_no送至抽奖接口，无需变更send_mobile&&河北平安非标
                    if (!$this->fbYhbFlag) {
                        $sendPhoneNo = $this->send_mobile = '13900000000';
                    } else {
                        $sendPhoneNo = $this->send_mobile;
                    }
                    $imsFlag = '2';
                } else {
                    $this->request_id = '';
                }
                $data_from       = $this->batch_type + 1;
                $awardOtherParam = "data_from=" . $data_from . "&ticket_seq=" . $this->ticket_seq . "&batch_type=" . $this->batch_type . "&store_id=" . $this->store_id . "&channel_id=" . $this->channel_id . "&wx_open_id=" . $this->wx_open_id . "&member_id=" . $this->memberId;
                $goodsInfo       = $this->getGoodsInfo($awardInfo['goods_id']);
                $dealFlag        = 1;
                $transType       = 1;
                if ($goodsInfo['goods_type'] == '15') { //流量包
                    $transType = 3;
                    if ($imsFlag == '2') {
                        $dealFlag = 0;
                    }
                } else if ($goodsInfo['goods_type'] == '8') {  // 8 Q币  做特殊出来 只需要将dealFlag设置为0 就行了
                    $dealFlag = 0;
                } else if ($goodsInfo['goods_type'] == '7') { // 7 话费 做特殊出来 只需要将trans_type设置为4 $dealFlag设置为0 就行了
                    $transType = 4;
                    if ($imsFlag == '2') {
                        $dealFlag = 0;
                    }
                }
                $rs = $this->saveSendAwardTrace(
                        $sendPhoneNo,
                        $transType,
                        $awardInfo,
                        $TransactionID,
                        $awardOtherParam,
                        $dealFlag,
                        $imsFlag
                );
                if ($rs === false) {
                    M()->rollback();
                    $this->saveCjTrace($awardInfo, 1, $resp_array['resp_desc']);
                    $this->returnError('旺财发码失败2:' . $resp_array['resp_desc'], $resp_array['resp_id']);
                }
            }
        }

        return true;
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param        $msg
     * @param string $dst
     */
    public function fileLog($msg, $dst = '')
    {
        if (empty($dst)) {
            if (defined('IS_WIN') && IS_WIN) {
                $dst = 'd:/openid.log';
            } else {
                $dst = '/tmp/openid.log';
            }
        }

        error_log(date('YmdHis') . '|' . $msg . PHP_EOL, 3, $dst);
    }

    /**
     * 获得goodsInfo
     *
     * @param $goodsId
     *
     * @return mixed
     */
    private function getGoodsInfo($goodsId)
    {
        static $goodsInfoList;
        if (!isset($goodsInfoList[$goodsId])) {
            $goodsInfo               = M('tgoods_info')->where(['goods_id' => $goodsId])->find();
            $goodsInfoList[$goodsId] = $goodsInfo;
        }
        return $goodsInfoList[$goodsId];
    }

    /**
     * 进入发送流水表-发码
     *
     * @param $phoneNo
     * @param $trans_type
     * @param $awardInfo
     * @param $TransactionID
     * @param $awardOtherParams
     * @param $dealFlag
     * @param $imsFlag
     *
     * @return bool
     */
    private function saveSendAwardTrace(
            $phoneNo,
            $trans_type,
            $awardInfo,
            $TransactionID,
            $awardOtherParams,
            $dealFlag,
            $imsFlag
    ) {
        $sendAwardTrace['trans_type']        = $trans_type;
        $sendAwardTrace['node_id']           = $this->node_id;
        $sendAwardTrace['phone_no']          = $phoneNo;
        $sendAwardTrace['batch_no']          = $awardInfo['activity_no'];
        $sendAwardTrace['request_id']        = $TransactionID;
        $sendAwardTrace['batch_info_id']     = $awardInfo['b_id'];
        $sendAwardTrace['award_other_param'] = $awardOtherParams;
        $sendAwardTrace['deal_flag']         = $dealFlag;
        $sendAwardTrace['ims_flag']          = $imsFlag;
        $sendAwardTrace['m_id']              = $this->batch_id;
        $sendAwardTrace['add_time']          = date('YmdHis');
        $rs                                  = M('tsend_award_trace')->add($sendAwardTrace);
        if (!$rs) {
            $this->log("进入发送流水表[tsend_award_trace]失败" . M()->_sql());
            $desc    = isset($resp_array['resp_desc']) ? $resp_array['resp_desc'] : '';
            $resp_id = isset($resp_array['resp_id']) ? $resp_array['resp_id'] : '';
            $this->saveCjTrace($awardInfo, 1, $desc);
            return $this->returnError('进入发送流水表[tsend_award_trace]失败:' . $desc, $resp_id);
        } else {
            $this->log("进入发送流水表[tsend_award_trace]成功" . M()->_sql());
            $goodsInfo = $this->getGoodsInfo($awardInfo['goods_id']);
            if ($goodsInfo) {
                if (in_array($goodsInfo['goods_type'], [22, 7, 8, 15])) {
                    $this->savePosDayCount(
                            $this->node_id,
                            $awardInfo['activity_no'],
                            '',
                            $awardInfo['b_id'],
                            date('Y-m-d'),
                            $goodsInfo,
                            1
                    );
                }
            }
        }
        return true;
    }


    /**
     * @param $node_id
     * @param $batch_no
     * @param $pos_id
     * @param $batch_info_id
     * @param $TRANS_DATE
     * @param $goodsInfo
     * @param $succ_num
     */
    private function savePosDayCount($node_id, $batch_no, $pos_id, $batch_info_id, $TRANS_DATE, $goodsInfo, $succ_num)
    {
        $where         = "NODE_ID ='" . $node_id . "' and BATCH_NO ='" . $batch_no . "' and POS_ID ='" . $pos_id . "'  and b_id = " . $batch_info_id . " and TRANS_DATE ='" . $TRANS_DATE . "' and b_id = " . $batch_info_id;
        $pos_day_count = M('TposDayCount')->where($where)->find();
        if (!$pos_day_count) {
            $pos_day_count['node_id']    = $node_id;
            $pos_day_count['pos_id']     = $pos_id;
            $pos_day_count['batch_no']   = $batch_no;
            $pos_day_count['b_id']       = $batch_info_id;
            $pos_day_count['trans_date'] = date('Y-m-d');
            $pos_day_count['send_num']   = $succ_num;
            $pos_day_count['send_amt']   = $goodsInfo['batch_amt'] * $succ_num;
            $pos_day_count['verify_num'] = 0;
            $pos_day_count['verify_amt'] = 0;
            $pos_day_count['cancel_num'] = 0;
            $pos_day_count['cancel_amt'] = 0;
            $pos_day_count['goods_id']   = $goodsInfo['goods_id'];
            $rs                          = M('TposDayCount')->add($pos_day_count);
            if ($rs === false) {
                $this->log("记录统计信息[tpos_day_count]失败 " . M()->_sql() . M()->getDbError());
            }
        } else {
            $new_day_count             = array();
            $new_day_count['send_num'] = $pos_day_count['send_num'] + $succ_num;
            $new_day_count['send_amt'] = $pos_day_count['send_amt'] + $goodsInfo['batch_amt'] * $succ_num;
            $rs                        = M('TposDayCount')->where($where)->save($new_day_count);
            if ($rs === false) {
                $this->log("更新统计信息[tpos_day_count]失败" . M()->_sql());
            }
        }
    }

    // 校验中奖数限制条件
    private function checkAwardCountRule($award_info)
    {
        // 校验奖品总数
        $where = "RULE_ID ='" . $award_info['id'] . "' and trans_date = '" . date('Ymd') . "'";
        $rs    = M('TawardDaytimes')->lock(true)->where($where)->find();
        if ($rs === false) { // 未获取到数据 回滚 记录日志退出
            M()->rollback();
            $this->log("未获取到数据 记录日志退出" . M()->_sql());
            die();
        }
        //未找到数据插入初始为0的记录进行锁定，如发生冲突则退出
        if (!$rs) {
            $award_day                = array();
            $award_day['rule_id']     = $award_info['id'];
            $award_day['trans_date']  = date('Ymd');
            $award_day['award_times'] = 0;
            $result                   = M('TawardDaytimes')->add($award_day);
            if (!$result) {
                M()->rollback();
                $this->log("插入TawardDaytimes 记录日志失败 退出" . M()->_sql());
                die();
            }
            $rs['award_times'] = 0;
        }

        // 校验当日奖品数
        if ($rs['award_times'] >= $award_info['day_count'] || $award_info['day_count'] <= 0) {
            return array(
                    'code' => '1002',
                    'desc' => '当日奖品已发完',
            );
        }

        $where = "RULE_ID ='" . $award_info['id'] . "'";
        // 校验总中奖数
        $total_count = M('TawardDaytimes')->where($where)->sum('award_times');
        if ($total_count === false) { // 未获取到数据 回滚 记录日志退出
            M()->rollback();
            $this->log("未获取到数据 记录日志退出" . M()->_sql());
            die();
        }
        if ($total_count >= $award_info['total_count'] || $award_info['total_count'] <= 0) {
            return array(
                    'code' => '1001',
                    'desc' => '奖品已发完',
            );
        }

        // 校验tbatch_info 库存
        $where      = "id =" . $award_info['b_id'];
        $batch_info = M('TbatchInfo')->where($where)->find();
        if ($batch_info === false) { // 未获取到数据 回滚 记录日志退出
            M()->rollback();
            $this->log("未获取到数据 记录日志退出" . M()->_sql());
            die();
        }
        if ($batch_info['storage_num'] != -1) { // 非不限库存
            if (($batch_info['remain_num'] - 1) >= 0) {
            } else {
                return array(
                        'code' => '1003',
                        'desc' => '奖品已发完',
                );
            }
        }

        return true;
    }

    // 校验单个手机可中奖数 因为tcj_stat已经对本次抽奖进行了计数，所以查出来的次数要-1
    private function checkAwardPhoneRule($award_info = [])
    {
        // 单日抽奖校验
        if ($this->day_part > 0) {
            $where    = "m_id = " . $this->batch_id . " and add_date = '" . date(
                            'Ymd'
                    ) . "' and mobile = '" . $this->phone_no . "'";
            $day_part = M('tcj_stat')->where($where)->lock(true)->field('SUM(stat_num) stat_count')->find();
            if ($day_part === false) { // 未获取到数据 回滚 记录日志退出
                M()->rollback();
                $this->log("未获取到数据 记录日志退出" . M()->_sql());
                die();
            }
            if ($day_part['stat_count'] - 1 >= $this->day_part) {
                M()->rollback();
                $this->returnError('当日此号码[' . $this->phone_no . ']已达抽奖上限', '1005');
            }
        }
        // 总抽奖数校验
        if ($this->total_part > 0) {
            $where      = "m_id = " . $this->batch_id . "  and mobile = '" . $this->phone_no . "'";
            $total_part = M('tcj_stat')->where($where)->lock(true)->field('SUM(stat_num) stat_count')->find();
            if ($total_part === false) { // 未获取到数据 回滚 记录日志退出
                M()->rollback();
                $this->log("未获取到数据 记录日志退出" . M()->_sql());
                die();
            }
            if ($total_part['stat_count'] - 1 >= $this->total_part) {
                M()->rollback();
                $this->returnError('此号码[' . $this->phone_no . ']已达抽奖上限', '1016');
            }
        }
        // 单日中奖校验
        if ($this->award_times > 0) {
            $where       = "m_id = " . $this->batch_id . " and add_date = '" . date(
                            'Ymd'
                    ) . "' and STATUS = '2'  and mobile = '" . $this->phone_no . "'";
            $phone_count = M('tcj_stat')->where($where)->lock(true)->field('SUM(stat_num) stat_count')->find();
            if ($phone_count === false) { // 未获取到数据 回滚 记录日志退出
                M()->rollback();
                $this->log("未获取到数据 记录日志退出" . M()->_sql());
                die();
            }
            if ($phone_count['stat_count'] >= $this->award_times) {
                M()->rollback();
                $this->saveCjTrace([], 1, '当日此号码[' . $this->phone_no . ']已达中奖上限');
                $this->returnError('当日此号码[' . $this->phone_no . ']已达中奖上限', '1003');
            }
        }
        // 总中奖校验
        if ($this->award_count > 0) {
            $where     = "m_id = " . $this->batch_id . " and STATUS = '2'  and mobile = '" . $this->phone_no . "'";
            $phone_sum = M('tcj_stat')->where($where)->lock(true)->field('SUM(stat_num) stat_count')->find();
            if ($phone_sum === false) { // 未获取到数据 回滚 记录日志退出
                M()->rollback();
                $this->log("未获取到数据 记录日志退出" . M()->_sql());
                die();
            }
            if ($phone_sum['stat_count'] >= $this->award_count) {
                M()->rollback();
                $this->saveCjTrace($award_info, 1, '此号码[' . $this->phone_no . ']已达中奖上限');
                $this->returnError('此号码[' . $this->phone_no . ']已达中奖上限', '1014');
            }
        }
    }

    // 抽奖参与统计（新增）
    private function cj_stat($cj_status, $add_time)
    {
        $add_date = substr($add_time, 0, 8);
        $where    = "node_id = '" . $this->node_id . "' and m_id = " . $this->batch_id . " and mobile = '" . $this->phone_no . "' and status = '" . $cj_status . "' and add_date = '" . $add_date . "'";
        if ($cj_status == '1') { // 未中奖
            // 查找有无记录，有则加1，无则新增
            $cj_stat_info = M('tcj_stat')->where($where)->lock(true)->find();
            if (!$cj_stat_info) {
                $cj_stat['node_id']  = $this->node_id;
                $cj_stat['m_id']     = $this->batch_id;
                $cj_stat['mobile']   = $this->phone_no;
                $cj_stat['status']   = $cj_status;
                $cj_stat['add_date'] = $add_date;
                $cj_stat['stat_num'] = 1;
                $rs                  = M('tcj_stat')->add($cj_stat);
                if ($rs === false) { // log no exit
                    $this->log("记录统计信息[tcj_stat]失败" . M()->_sql());

                    return false;
                } else {
                    return true;
                }
            }
            // 更新次数
            $rs = M()->table('tcj_stat')->where($where)->setInc('stat_num');
            if ($rs === false) { // log no exit
                $this->log("更新统计信息[tcj_stat]失败" . M()->_sql());

                return false;
            } else {
                return true;
            }
        } else if ($cj_status == '2') { // 中奖 中奖数+1 未中奖数-1
            // 查找有无记录，有则加1，无则新增
            $cj_stat_info = M('tcj_stat')->where($where)->lock(true)->find();
            if (!$cj_stat_info) {
                $cj_stat['node_id']  = $this->node_id;
                $cj_stat['m_id']     = $this->batch_id;
                $cj_stat['mobile']   = $this->phone_no;
                $cj_stat['status']   = $cj_status;
                $cj_stat['add_date'] = $add_date;
                $cj_stat['stat_num'] = 1;
                $rs                  = M('tcj_stat')->add($cj_stat);
                if ($rs === false) { // log no exit
                    $this->log("记录统计信息[tcj_stat]失败" . M()->_sql());

                    return false;
                }
            } else {
                // 更新中奖次数+1
                $rs = M()->table('tcj_stat')->where($where)->setInc('stat_num');
                if ($rs === false) { // log no exit
                    $this->log("更新统计信息[tcj_stat]失败" . M()->_sql());

                    return false;
                }
            }
            // 更新未中奖次数-1
            $where = "node_id = '" . $this->node_id . "' and m_id = " . $this->batch_id . " and mobile = '" . $this->phone_no . "' and status = '1' and add_date = '" . $add_date . "'";
            $rs    = M()->table('tcj_stat')->where($where)->setDec('stat_num');
            if ($rs === false) { // log no exit
                $this->log("更新统计信息[tcj_stat]失败" . M()->_sql());

                return false;
            } else {
                return true;
            }
        }
    }

    // 保存抽奖流水
    private function saveCjTrace($award_info, $status, $ret_msg)
    {
        $cj_trace                     = array();
        $cj_trace['label_id']         = $this->label_id;
        $cj_trace['batch_type']       = $this->batch_type;
        $cj_trace['batch_id']         = $this->batch_id;
        $cj_trace['channel_id']       = $this->channel_id;
        $cj_trace['mobile']           = $this->phone_no;
        $cj_trace['ip']               = $this->ip;
        $cj_trace['status']           = $status;
        $cj_trace['node_id']          = $this->node_id;
        $cj_trace['add_time']         = date("YmdHis");
        $cj_trace['rule_id']          = '0';
        $cj_trace['prize_level']      = '0'; // 默认0级
        $cj_trace['cj_rule_id']       = $this->cj_rule_id;
        $cj_trace['ret_msg']          = $ret_msg;
        $cj_trace['ticket_num']       = $this->ticket_seq;
        $cj_trace['cate_id']          = $this->wx_cjcate_id;
        $cj_trace['user_id']          = $this->wx_wap_ranking_id;
        $cj_trace['wx_name']          = $this->wx_nick;
        $cj_trace['join_mode']        = $this->join_mode;
        $cj_trace['send_mobile']      = $this->send_mobile;
        $cj_trace['send_mode']        = $this->send_mode;
        $cj_trace['pay_token']        = $this->pay_token; // 付满送token
        $cj_trace['request_id']       = $this->save_request_id; // 发码接口请求ID
        $cj_trace['full_id']          = $this->full_id; // full_id
        $cj_trace['participation_id'] = $this->participation_id;
        $cj_trace['b_id']             = $this->b_id;
        $cj_trace['g_id']             = $this->g_id;
        $cj_trace['member_id']        = $this->memberId;

        if ($status == '2') { // 中奖了才记录奖品等级和奖品id
            $cj_trace['rule_id']     = $award_info['id'];
            $cj_trace['prize_level'] = $award_info['award_level'];
        }

        $rs = M('TcjTrace')->add($cj_trace);
        if (!$rs) {
            $this->log(print_r($cj_trace, true));
            $this->log("记录流水信息[tcj_trace]失败");
        } else {
            $this->cj_trace_id = $rs;
        }
    }

    // 校验会员中奖资格
    private function checkAwardRange($award_info)
    {
        $arr = explode(",", $award_info['member_batch_id']);
        if (!in_array($this->memberInfo['card_id'], $arr)) {
            $this->saveCjTrace($award_info, 1, '该会员不能中该奖品');
            $this->returnError('该会员不能中该奖品', '1007');
        }
    }

    // 校验营销活动会员参与资格
    private function checkJoinRangeMarketing($award_info)
    {
        $arr = explode(",", $award_info['member_batch_id']);
        if (!in_array($this->memberInfo['card_id'], $arr)) {
            $this->saveCjTrace($award_info, 1, '不能参与该活动');
            $this->returnError('不能参与该活动', '1012');
        }
    }

    // 校验营销活动会员中奖资格
    private function checkAwardRangeMarketing($award_info)
    {
        $arr = explode(",", $award_info['member_batch_award_id']);
        if (!in_array($this->memberInfo['card_id'], $arr)) {
            $this->saveCjTrace($award_info, 1, '该会员不能中该奖品');
            $this->returnError('该会员不能中该奖品', '1009');
        }
    }

    // 获取返回奖品是否支持线上提领
    private function get_online_verify_flag($batch_info)
    {
        // 查找goods_info 获得bonus_id
        $where      = "goods_id = '" . $batch_info['goods_id'] . "'";
        $goods_info = M()->table('tgoods_info')->where($where)->field('online_verify_flag')->find();
        if (!$goods_info) {
            // 失败回滚
            M()->rollback();
            $this->log("没有状态正常的电子券" . M()->_sql());
            $this->returnError('该电子券[' . $batch_info['goods_id'] . ']不存在', '1060');
        }

        return $goods_info['online_verify_flag'];
    }

    // 定向红包处理
    private function bonus_deal($batch_info)
    {
        // 查找goods_info 获得bonus_id
        $where      = "goods_id = '" . $batch_info['goods_id'] . "'";
        $goods_info = M()->table('tgoods_info')->where($where)->find();
        if (!$goods_info) {
            // 失败回滚
            M()->rollback();
            $this->log("没有状态正常的电子券" . M()->_sql());
            $this->returnError('该电子券[' . $batch_info['goods_id'] . ']不存在', '1060');
        }
        // 查tbonus_detail
        $where        = "bonus_id = " . $goods_info['bonus_id'];
        $bonus_detail = M()->table('tbonus_detail')->where($where)->find();
        if (!$bonus_detail) {
            // 失败回滚
            M()->rollback();
            $this->log("没有状态正常的bonus_detail" . M()->_sql());
            $this->returnError('该bonus_detail[' . $goods_info['bonus_id'] . ']不存在', '1061');
        }
        // 更新数据
        $rs = M('tbonus_detail')->where($where)->setInc('get_num', 1);
        if ($rs === false) {
            // 失败回滚
            M()->rollback();
            $this->log("bonus_detail 更新失败" . M()->_sql());
            $this->returnError('bonus_detail 更新失败[' . $goods_info['bonus_id'] . ']不存在', '1062');
        }
        // 插入tbonus_use_detail
        $tbonus_use_detail['m_id']            = $bonus_detail['m_id'];
        $tbonus_use_detail['node_id']         = $bonus_detail['node_id'];
        $tbonus_use_detail['bonus_id']        = $bonus_detail['bonus_id'];
        $tbonus_use_detail['bonus_detail_id'] = $bonus_detail['id'];
        $tbonus_use_detail['bonus_num']       = 1;
        $tbonus_use_detail['bonus_use_num']   = 0;
        $tbonus_use_detail['phone']           = $this->phone_no;
        $tbonus_use_detail['status']          = '1';
        $tbonus_use_detail['request_id']      = $this->save_request_id;
        $rs                                   = M('tbonus_use_detail')->add($tbonus_use_detail);
        if ($rs === false) {
            // 失败回滚
            M()->rollback();
            $this->log("tbonus_use_detail 插入失败" . M()->_sql());
            $this->returnError('tbonus_use_detail 插入[' . $goods_info['bonus_id'] . ']不存在', '1063');
        }
        $this->bonus_use_detail_id = $rs;
    }

    // 积分奖品
    private function integral_deal($batch_info, $award_info)
    {
        $integralDetail = array(
                'm_id'           => $this->batch_id,
                'node_id'        => $this->node_id,
                'integral_num'   => $batch_info['batch_amt'],
                'status'         => '0',
                'add_time'       => date('YmdHis'),
                'request_id'     => $this->save_request_id,
                'openid'         => $this->phone_no,
                'join_member_id' => $this->memberId,
                'b_id'           => $batch_info['id'],
                'channel_id'     => $this->channel_id,
        );

        if ($this->memberId) {
            $integralDetail['member_id'] = $this->memberId;
            $integralDetail['status']    = '1';
        }

        $this->integral_get_id = M('tintegal_get_detail')->add($integralDetail);

        if ($this->integral_get_id === false) {
            log_write('增加积分的sql：' . M()->_sql());
            // 失败回滚
            M()->rollback();
            $this->log("tintegal_get_detail 插入失败" . M()->_sql());
            $this->returnError('tintegal_get_detail 插入失败', '1203');
        }

        if ($this->memberId) {
            $rs = D('IntegralPointTrace')->integralPointChange(
                    12,
                    $batch_info['batch_amt'],
                    $this->memberId,
                    $this->node_id,
                    $this->integral_get_id
            );
            if ($rs !== true) {
                M()->rollback();
                $this->saveCjTrace($award_info, 1, '增加积分失败');
                $this->returnError('增加积分失败', '1202');
            }
            // 添加积分行为数据
            $flag = D('MemberBehavior', 'Model')->addBehaviorType(
                    $this->memberId,
                    $this->node_id,
                    15,
                    $batch_info['batch_amt'],
                    $this->batch_id
            );
            if ($flag === false) {
                $this->log("===MEM_DEBUG===记录会员行为数据[抽奖流水]member_id[{$this->memberId}],node_id[{$this->node_id}],1");
            }
        }
    }

    // 付满送
    private function pay_give()
    {
        $now_time = date('YmdHis');
        // 查channel表
        // 根据channel_id从tchannel中获取store_id //校验有效期 状态 status = '1' and
        // begin_time < '" .$now_time. "' and end_time > '". $now_time ."' and
        $where        = "id = " . $this->channel_id;
        $channel_info = M()->table('tchannel')->where($where)->find();
        if (!$channel_info) {
            $this->log("没有状态正常的渠道" . M()->_sql());
            $this->returnError('该渠道[' . $this->channel_id . ']不存在', '1050');
        }
        M()->startTrans(); // 校验中奖数 起事务
        // 查tpay_give_order 获取 状态 校验
        $where               = "pay_token =  '" . $this->pay_token . "' and status = '1'";
        $pay_give_order_info = M()->table('tpay_give_order')->lock(true)->where($where)->find();
        if (!$pay_give_order_info) {
            M()->rollback();
            $this->log("没有找到状态正常的该订单" . M()->_sql());
            $this->returnError('该正常订单[' . $this->pay_token . ']不存在', '1051');
        }
        // 查tcj_trace 进行参与数量校验 修改tpay_give_order状态
        // 校验是否已经有过抽奖记录
        if ($channel_info['join_limit'] > 0) {
            if ($channel_info['join_limit'] <= $pay_give_order_info['use_times']) {
                M()->rollback();
                $this->returnError('该正常订单[' . $this->pay_token . ']已使用过', '1053');
            }
            // 更新次数
            $rs = M()->table('tpay_give_order')->where($where)->setInc('use_times');
            if ($rs === false) {
                M()->rollback();
                $this->returnError('该正常订单[' . $this->pay_token . ']系统错误', '1054');
            }
        }
        $this->pay_give_order_info = $pay_give_order_info;
        M()->commit(); // 提交事务
    }

    // 付满送中奖次数累加
    private function pay_give_stat()
    {
        $where = "pay_token =  '" . $this->pay_token . "' and status = '1'";
        // 更新次数
        $rs = M()->table('tpay_give_order')->where($where)->setInc('award_times');
        if ($rs === false) {
            M()->rollback();
            $this->returnError('该正常订单[' . $this->pay_token . ']系统错误', '1055');
        }

        // 对营业员中奖数进行累加
        if ($this->pay_give_order_info != null && $this->pay_give_order_info['clerk_id'] != null) {
            $where = "clerk_id = '{$this->pay_give_order_info['clerk_id']}'";
            // 更新次数
            $rs = M()->table('tpay_give_clerk')->where($where)->setInc('cj_cnt');
            if ($rs === false) {
                log_write("对营业员中奖数进行累加error" . M()->_sql());
            }
        }
    }

    // 多奖品均值抽奖: award_type =2
    private function multipleAward()
    {
        $add_time = date('YmdHis');
        // 圣诞抽雪球非标需求
        if (($this->wx_wap_ranking_id != null) && ($this->wx_cjcate_id != null)) {

            // 春节打炮总动员 by tr
            if ($this->batch_type == '42') {
                $wx_wap_ranking_info = M()->table('twx_firecrackers_score')->where(
                        "id = " . $this->wx_wap_ranking_id
                )->find();
            } else {  // 获取用户积分
                $wx_wap_ranking_info = M()->table('twx_wap_ranking')->where("id = " . $this->wx_wap_ranking_id)->find();
            }

            if (!$wx_wap_ranking_info) {
                $this->returnError('用户记录[' . $this->wx_wap_ranking_id . ']不存在', '7001');
            }
            // 获取奖品要求积分
            $cj_cate_info = M()->table('tcj_cate')->where("id = " . $this->wx_cjcate_id)->find();
            if (!$cj_cate_info) {
                $this->returnError('奖品配置记录[' . $this->wx_cjcate_id . ']不存在', '7001');
            }
            // 比对积分
            if ($wx_wap_ranking_info['score'] < $cj_cate_info['score']) {
                $this->returnError(
                        '用户未达到抽奖要求[' . $wx_wap_ranking_info['score'] . '][' . $cj_cate_info['score'] . "]",
                        '7001'
                );
            }
            // 校验是否已经有过抽奖记录
            $count = M('TcjTrace')->where(
                    "status = 2 and batch_id = " . $this->batch_id . " and cate_id = " . $this->wx_cjcate_id . " and user_id = " . $this->wx_wap_ranking_id
            )->count();
            if ($count === false) { // 未获取到数据 回滚 记录日志退出
                M()->rollback();
                $this->log("未获取到数据 记录日志退出" . M()->_sql());
                die();
            }
            if ($count > 0) {
                $this->returnError('用户已经抽过奖[' . $this->wx_cjcate_id . '][' . $this->wx_wap_ranking_id . "]", '7001');
            }
            $where = "CJ_RULE_ID ='" . $this->cj_rule_id . "' and status = '1' and cj_cate_id = " . $this->wx_cjcate_id;
        } else if ($this->wx_cjcate_id != null && $this->wx_cjbatch_id != null) {
            $where = "CJ_RULE_ID ='" . $this->cj_rule_id . "' and status = '1' and cj_cate_id = " . $this->wx_cjcate_id . " and id = " . $this->wx_cjbatch_id;
        } else if ($this->wx_cjcate_id != null) {
            $where = "CJ_RULE_ID ='" . $this->cj_rule_id . "' and status = '1' and cj_cate_id = " . $this->wx_cjcate_id;
        } else {
            $where = "CJ_RULE_ID ='" . $this->cj_rule_id . "' and status = '1'";
        }
        $award_List = M('TcjBatch')->where($where)->select();
        if (!$award_List) {
            $this->returnError('抽奖规则[' . M()->getLastSql() . ']不存在', '7001');
//            $this->returnError('抽奖规则[' . $this->award_id . ']不存在', '7001');
        }
        // 查找marketing_info 信息
        $marketing_info = M()->table('tmarketing_info')->where("id = " . $this->batch_id)->find();
        if (!$marketing_info) {
            $this->returnError('营销活动[' . $this->batch_id . ']不存在', '7001');
        }
        // 如果是微信参与的话替换手机号码为微信open_id
        $this->send_mobile = $this->phone_no;
        $this->join_mode   = $marketing_info['join_mode'];
        if ($marketing_info['join_mode'] == '1') { // 微信参与
            $this->phone_no = $this->wx_open_id;
        }

        $this->getMemberInfo($marketing_info);

        $selecttype = $marketing_info['select_type'];
        $selectarr  = explode('-', $selecttype);
        // 非标 batch_type=34 校验小票次数 条件换成 指定机构号+ tmarketing_info defined_one_name
        // =
        // '小票号'
        // if (($marketing_info['defined_one_name'] == '小票号')){
        // 添加会员行为记录
        if ($this->memberId) {
            $flag = D('MemberBehavior', 'Model')->addBehaviorType(
                    $this->memberId,
                    $this->node_id,
                    1,
                    '',
                    $this->batch_id
            );
            if ($flag === false) {
                $this->log("===MEM_DEBUG===记录会员行为数据[抽奖流水]member_id[{$this->memberId}],node_id[{$this->node_id}],1");
            }
        }
        if (($this->node_id == '00021275') && ($marketing_info['defined_one_name'] == '小票号') && in_array(
                        '10',
                        $selectarr
                )
        ) {
            if (!$this->ticket_seq) {
                $this->returnError('您未中奖', '1031');
            }
            $where = "batch_id = " . $this->batch_id . " and ticket_num = '" . $this->ticket_seq . "'";
            M()->startTrans();
            $fb_ticket_info = M()->table('tfb_ticket_trace')->lock(true)->where($where)->find();
            if (!$fb_ticket_info) {
                // add
                $fb_ticket_info_new               = array();
                $fb_ticket_info_new['batch_type'] = $this->batch_type;
                $fb_ticket_info_new['batch_id']   = $this->batch_id;
                $fb_ticket_info_new['ticket_num'] = $this->ticket_seq;
                $fb_ticket_info_new['use_count']  = 1;
                $rs                               = M('tfb_ticket_trace')->add($fb_ticket_info_new);
                if (!$rs) {
                    M()->rollback();
                    $this->returnError('您未中奖', '1030');
                }
                M()->commit(); // 提交事务
            } else {
                // 更新标记
                $fb_ticket_info_update              = array();
                $fb_ticket_info_update['use_count'] = $fb_ticket_info['use_count'] + 1;
                $rs                                 = M()->table('tfb_ticket_trace')->where(
                        'id = ' . $fb_ticket_info['id']
                )->save($fb_ticket_info_update);
                if ($rs === false) {
                    M()->rollback();
                    $this->log("更新状态[tfb_ticket_trace]失败");
                    $this->returnError('您未中奖', '1031');
                }
                M()->commit(); // 提交事务
                if ($fb_ticket_info['use_count'] >= $this->ticket_limit_num) {
                    $this->returnError('该小票已超过参与次数限制', '1032');
                }
                $this->returnError('该小票已超过参与次数限制', '1032');
            }
        }

        // 判断非会员标识
        if ($marketing_info['member_join_flag'] == '1') {
            if ($marketing_info['join_mode'] == '1') { // 微信参与
                $where       = "openid = '" . $this->phone_no . "' and node_id = '" . $this->node_id . "' and subscribe <> '0'";
                $member_info = M('twx_user')->where($where)->find();
                if (!$member_info) {
                    // $this->saveCjTrace($award_info, 1,'该手机用户非会员');
                    $this->returnError('该微信用户非关注会员', '1010');
                }
                // 判断营销活动上的会员等级
                if ($marketing_info['member_batch_id'] != '-1') { // 需要校验会员参与范围
                    $arr = explode(",", $marketing_info['member_batch_id']);
                    if (!in_array($member_info['group_id'], $arr)) {
                        $this->saveCjTrace(array(), 1, '不能参与该活动');
                        $this->returnError('不能参与该活动', '1012');
                    }
                }
                if ($marketing_info['member_batch_award_id'] != '-1') { // 需要校验会员中奖范围
                    $arr = explode(",", $marketing_info['member_batch_award_id']);
                    if (!in_array($member_info['group_id'], $arr)) {
                        $this->saveCjTrace(array(), 1, '该会员不能中该奖品');
                        $this->returnError('该会员不能中该奖品', '1009');
                    }
                }
            } else {
                // 手机号码参与
                if ($marketing_info['member_batch_id'] != '-1') { // 需要校验会员参与范围
                    $this->checkJoinRangeMarketing($marketing_info);
                }
                if ($marketing_info['member_batch_award_id'] != '-1') { // 需要校验会员中奖范围
                    $this->checkAwardRangeMarketing($marketing_info);
                }
            }
        }
        // 校验付满送业务
        if ($this->pay_token != null) {
            $this->pay_give();
        }
        // 统计改造 事务开始前就插入 跟抽奖逻辑的事务分开是为了不会被抽奖逻辑事务中的异常情况回滚
        M()->startTrans();
        $rs = $this->cj_stat('1', $add_time);
        if ($rs === false) {
            M()->rollback();
            $this->saveCjTrace([], 1, '抽奖次数累计错误');
            $this->returnError('抽奖次数累计失败', '8001');
        }
        M()->commit(); // 提交事务

        M()->startTrans(); // 校验中奖数 起事务
        // 校验手机当日可否抽奖
        $this->checkAwardPhoneRule();
        $award_random = mt_rand(1, 100);
        if ($award_random <= $this->total_rate) {
            $icount      = 0;
            $award_array = array();
            $max_random  = 0;
            $low_num     = 0;
            $retCode     = [];
            foreach ($award_List as &$award_info) {
                // 取有效奖品数
                $rs = $this->checkAwardCountRule($award_info);// 库存  日上限
                if ($rs === true) {
                    $max_random = $low_num + $award_info['award_rate'];

                    $award_range               = array();
                    $award_range['award_info'] = $award_info;
                    $award_range['low_num']    = $low_num;
                    $award_range['max_num']    = $max_random;

                    $award_array[$icount] = $award_range;

                    $low_num = $max_random;
                    $icount++;
                } else {
                    if (isset($retCode[$rs['code']])) {
                        $retCode[$rs['code']] += 1;
                    } else {
                        $retCode[$rs['code']] = 1;
                    }
                }
            }
            if ($icount == 0) {
                M()->rollback();
                if (isset($retCode['1002'])) {
                    $this->saveCjTrace($award_List['0'], 1, '当日奖品已发完');
                    $this->returnError('当日奖品已发完', '1002');
                }
                $this->saveCjTrace($award_List['0'], 1, '奖品已发完');
                $this->returnError('奖品已发完', '1001');
            } else { // 到这里应该必中奖了，接下来是中什么奖品的逻辑
                $inum         = 0;
                $award_random = mt_rand(1, $max_random);
                $resp_array   = [];
                for ($inum = 0; $inum < $icount; $inum++) {
                    $award_detail = $award_array[$inum];
                    if ($award_random > $award_detail['low_num'] && $award_random <= $award_detail['max_num']) {
                        $award_info = $award_detail['award_info'];

                        $where                         = "id = '" . $award_info['cj_cate_id'] . "'";
                        $award_info['member_batch_id'] = M('TcjCate')->where($where)->getField('member_batch_id');
                        if ($award_info['member_batch_id'] === false) { // 未获取到数据 回滚 记录日志退出
                            M()->rollback();
                            $this->log("未获取到数据 记录日志退出" . M()->_sql());
                            die();
                        }
                        if ($award_info['member_batch_id']) { // 需要校验会员中奖范围
                            $this->checkAwardRange($award_info);
                        }
                        // 校验tbatch_info 库存 zhengxh
                        $where      = "id =" . $award_info['b_id'];
                        $this->b_id = $award_info['b_id'];

                        $batch_info = M('TbatchInfo')->lock(true)->where($where)->find();

                        if ($batch_info === false) { // 未获取到数据 回滚 记录日志退出
                            M()->rollback();
                            $this->log("未获取到数据 记录日志退出" . M()->_sql());
                            die();
                        }
                        if ($batch_info['storage_num'] != -1) { // 非不限库存
                            if (($batch_info['remain_num'] - 1) >= 0) {
                                $batch_info['remain_num']--;
                                $rs = M('TbatchInfo')->where($where)->save($batch_info);
                                if ($rs === false) {
                                    M()->rollback();
                                    $this->saveCjTrace($award_info, 1, '更新库存失败');
                                    $this->returnError('更新库存失败', '1201');
                                }
                            } else {
                                M()->rollback();
                                $this->saveCjTrace($award_info, 1, '奖品已发完');
                                $this->returnError('奖品已发完', '1006');
                            }
                        }

                        // 奖品类型： 1 电子券 2 卡券 3 红包 4 积分奖品 5 平安币 6 微信红包
                        $prize_type = 1;
                        // 判断是否微信卡券
                        if ($batch_info['card_id'] != null) {
                            $prize_type        = 2;
                            $this->send_mode   = '1';
                            $this->send_mobile = $this->wx_open_id;
                        } else {
                            $this->send_mode = '0';
                        }

                        // 获取online_verify_flag
                        $online_verify_flag = $this->get_online_verify_flag($batch_info);

                        // 付满送业务中奖数累加
                        if ($this->pay_token != null) {
                            $this->pay_give_stat();
                        }

                        $rs = $this->update_awardday($award_info);
                        if ($rs !== true) {
                            M()->rollback();
                            $this->saveCjTrace($award_info, 1, '更新抽奖日统计失败');
                            $this->returnError('更新抽奖日统计失败', '1201');
                        }

                        // 判断是否定额红包
                        if ($batch_info['batch_class'] == '12') {
                            $prize_type = 3;
                            $this->bonus_deal($batch_info);
                            $award_info['send_type'] = '1';
                        } else if ($batch_info['batch_class'] == '14') { // 积分类商品
                            $prize_type = 4;
                            if ($marketing_info['batch_type'] == '61') {//欧洲杯活动特殊处理，处理积分的时候用手机号
                                $this->phone_no = $this->send_mobile;
                            }
                            $this->integral_deal($batch_info, $award_info);
                            $award_info['send_type'] = '1';
                        } else if ($batch_info['batch_type'] == '6' || $batch_info['batch_type'] == '7') { // 微信红包和翼码代理红包
                            $award_info['send_type'] = '1';
                            $prize_type              = 6;
                            $rs                      = $this->saveSendAwardTrace(
                                    $this->phone_no,
                                    2,
                                    $award_info,
                                    $this->save_request_id,
                                    '',
                                    1,
                                    1
                            );
                            if ($rs === false) {
                                M()->rollback();
                                $this->saveCjTrace($award_info, 1, $resp_array['resp_desc']);
                                $this->returnError('发送红包失败:' . $resp_array['resp_desc'], $resp_array['resp_id']);
                            }
                        }

                        if ($award_info['send_type'] == '0') {// 为0 才下发
                            $this->send_award($award_info, $resp_array);
                        }

                        $this->saveCjTrace($award_info, 2, '恭喜,中奖了');
                        // 统计改造
                        $rs = $this->cj_stat('2', $add_time);
                        if ($rs === false) {
                            M()->rollback();
                            $this->saveCjTrace($award_info, 1, '抽奖次数累计错误');
                            $this->returnError('抽奖次数累计失败', '8002');
                        }
                        M()->commit(); // 提交事务

                        $this->returnSuccess(
                                "恭喜,中奖了",
                                array(
                                        "rule_id"             => $award_info['id'],
                                        "batch_no"            => $award_info['activity_no'],
                                        "award_origin"        => $award_info['award_origin'],
                                        "award_level"         => $award_info['award_level'],
                                        "card_ext"            => $this->wx_card_info['card_ext'],
                                        "card_id"             => $this->wx_card_info['card_id'],
                                        "request_id"          => $this->request_id,
                                        "cj_trace_id"         => $this->cj_trace_id,
                                        "cate_id"             => $award_info['cj_cate_id'],
                                        'online_verify_flag'  => $online_verify_flag,
                                        "bonus_use_detail_id" => $this->bonus_use_detail_id,
                                        "prize_type"          => $prize_type,
                                        "batch_class"         => $batch_info['batch_class'],
                                        "member_id"           => $this->memberId,
                                        "member_phone"        => $this->memberInfo ? $this->memberInfo['phone_no'] : '',
                                        "integral_get_flag"   => $prize_type == 4 && $this->memberId ? 1 : 0,
                                        "integral_get_id"     => $this->integral_get_id,
                                )
                        );
                    }
                }
            }
        } else {
            M()->rollback();
            $this->saveCjTrace($award_List['0'], 1, '未中奖');
            $this->returnError('未中奖', '1000');
        }
    }

    private function check()
    {
        if ($this->batch_id == '') {
            return array(
                    'resp_desc' => '抽奖id不能为空！',
            );

        }
        if ($this->award_type == '') {
            return array(
                    'resp_desc' => '抽奖类型不能为空！',
            );

        }
        if ($this->award_times == '') {
            return array(
                    'resp_desc' => '单号码每日中奖数不能为空！',
            );

        }
        if ($this->phone_no == '' && $this->wx_open_id == '') {
            return array(
                    'resp_desc' => '手机号和wx_open_id不能同时为空！',
            );

        }
        if ($this->label_id == '') {
            return array(
                    'resp_desc' => '标签不能为空！',
            );

        }
        if ($this->channel_id == '') {
            return array(
                    'resp_desc' => '渠道不能为空！',
            );

        }
        if ($this->batch_type == '') {
            return array(
                    'resp_desc' => '营销活动类型为空！',
            );

        }
        if ($this->cj_rule_id == '') {
            return array(
                    'resp_desc' => '抽奖规则id为空！',
            );

        }
        if ($this->award_type == '2') { // 抽奖类型为多商品
            if ($this->total_rate == '') {
                return array(
                        'resp_desc' => '总中奖率不能为空！',
                );

            }
        }

        return true;
    }

    /**
     *
     * @param array $marketingInfo 获取会员信息
     */
    private function getMemberInfo($marketingInfo)
    {
        if ($this->memberInfo !== null) {
            return;
        }

        $memberModel   = D('MemberInstall', 'Model');
        $condition     = $this->phone_no;
        $conditionType = 1; // 1手机 2翼码授权openid 3商户授权openid
        $autoAdd       = true;
        if ($marketingInfo['join_mode'] == '1') {
            // 劳动最光荣
            if ($marketingInfo['batch_type'] == '45') {
                $conditionType = $marketingInfo['member_join_flag'] == '0' ? 2 : 3;
            } else if (in_array(
                    $marketingInfo['batch_type'],
                    array(
                            '56',
                            '59',
                    )
            )) { // 双旦和升旗手
                $configData    = unserialize($marketingInfo['config_data']);
                $conditionType = $configData['wx_auth_type'] == '0' ? 2 : 3;
            } else if ($marketingInfo['batch_type'] == '61') {
                $condition = $this->send_mobile;
            } else {
                $conditionType = 3;
            }
            $autoAdd = false;
        }

        $option = array(
                'channel_id' => $this->channel_id,
                'batch_id'   => $this->batch_id
        );
        $result = $memberModel->wxTermMemberFlag($this->node_id, $condition, $conditionType, $autoAdd, $option);
        if ($autoAdd == false && $result === false) {
            $this->log("===MEM_DEBUG===get_member_info 微信参与，不产生会员数据");

            return;
        }

        if ($result === false) {
            $this->log("getMemberInfo fail! node_id[{$this->node_id}] condition[{$condition}]");
        }

        $this->log("===MEM_DEBUG===get_member_info " . print_r($result, true));
        $this->memberInfo = $result;
        $this->memberId   = $result['id'];
    }
}