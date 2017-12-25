<?php

/* 主动通知接口 */
class PreValidAction extends Action {

    public $ReqArr;

    public $transType;

    public $responseType;

    public $ori_pos_trace;

    public $verify_info;

    public $responseDesc = array(
        '0000' => '交易成功', 
        '1000' => '交易类型错误', 
        '2001' => '流水重复', 
        '2002' => '未找到原终端流水', 
        '2003' => '原终端流水状态不正常', 
        '2004' => '原终端流水非验证交易', 
        '2005' => '未知交易类型', 
        '2006' => '未找到委托流水', 
        '2007' => '未找到终端', 
        '2008' => '未找到商品', 
        '2009' => '未找到原分销商品', 
        '2010' => '未找到分销协议', 
        '2011' => '未到协议开始时间', 
        '2012' => '协议已结束', 
        '2013' => '超过最大协议金额限制', 
        '2014' => '系统故障', 
        '2017' => '记录终端流水错误', 
        '2018' => '修改凭证状态错误', 
        '2019' => '更新源终端流水错误', 
        '2022' => '预校验错误');

    public function index() {
        $reqXml = file_get_contents('php://input');
        import('@.ORG.Util.Xml') or die('[@.ORG.Util.Xml]导入失败');
        $xml = new Xml();
        
        log_write($reqXml, 'REQUEST');
        $this->ReqArr = $xml->parse($reqXml);
        $this->transType = $xml->getRootName();
        
        if ($this->transType == 'VerifyRequest') { // 预校验
            $this->responseType = 'VerifyRespose';
            $this->pre_valid();
        } else {
            $this->responseType = 'ErrorRes';
            $this->notifyreturn('1000');
        }
    }
    
    // 预校验
    private function pre_valid() {
        $this->verify_info = $this->ReqArr['VerifyRequest'];
        
        // 一、查流水信息
        $where = "pos_id ='" . $this->verify_info['PosId'] . "' and pos_seq = '" .
             $this->verify_info['PosSeq'] . "'";
        $pos_trace = M('TposTrace')->where($where)->find();
        if ($pos_trace) {
            log_write("流水重复...");
            $this->notifyreturn('2001');
        }
        // 二、查找原委托流水的req_seq
        if ($this->verify_info['TransType'] == '1') { // 验证
            $req_seq = $this->verify_info['OriVerifySeq'];
            $this->verify_info['trans_type'] = '0';
            $where = "req_seq ='" . $this->verify_info['OriVerifySeq'] . "'";
        } else { // 冲正、撤销
            $this->verify_info['OrgPosSeq'] = $this->verify_info['OriVerifySeq'];
            $where = "pos_id ='" . $this->verify_info['PosId'] .
                 "' and pos_seq = '" . $this->verify_info['OriVerifySeq'] . "'";
            $this->ori_pos_trace = M('TposTrace')->where($where)->find();
            if ($this->verify_info['TransType'] == '2') { // 冲正
                $this->verify_info['trans_type'] = '2';
                if (! $this->ori_pos_trace) {
                    log_write(
                        "未找到原终端流水[" . $this->verify_info['OriVerifySeq'] .
                             "] 允许冲正");
                    $this->notifyreturn('0000');
                }
                if ($this->ori_pos_trace['status'] != '0') {
                    log_write(
                        "原终端流水状态不正常[" . $this->verify_info['OriVerifySeq'] .
                             "] 允许冲正");
                    $this->notifyreturn('0000');
                }
            } else if ($this->verify_info['TransType'] == '3') {
                $this->verify_info['trans_type'] = '1';
                if (! $this->ori_pos_trace) {
                    log_write(
                        "未找到原终端流水[" . $this->verify_info['OriVerifySeq'] .
                             "] 撤销失败");
                    $this->notifyreturn('2002');
                }
                if ($this->ori_pos_trace['status'] != '0') {
                    log_write(
                        "原终端流水状态不正常[" . $this->verify_info['OriVerifySeq'] .
                             "] 撤销失败");
                    $this->notifyreturn('2003');
                }
                if (($this->ori_pos_trace['trans_type'] != '0') &&
                     ($this->ori_pos_trace['trans_type'] != '3')) {
                    log_write(
                        "原终端流水非验证交易[" . $this->verify_info['OriVerifySeq'] .
                         "] 撤销失败");
                    $this->notifyreturn('2004');
                }
            } else {
                log_write(
                    "未知交易类型[" . $this->verify_info['OriVerifySeq'] . "] 处理失败");
                $this->notifyreturn('2005');
            }
            $req_seq = $this->ori_pos_trace['req_seq'];
            $where = "req_seq ='" . $req_seq . "'";
        }
        
        // 三、查找原委托流水
        $barcode_info = M('TbarcodeTrace')->where($where)->find();
        if (! $barcode_info) {
            log_write("未找到委托流水[" . $req_seq . "]");
            $this->notifyreturn('2006');
        }
        // 非标 判断京东流水预校验
        if ($barcode_info['data_from'] == 'J') {
            // 根据交易类型做处理 //判断状态是否已使用
            if ($this->verify_info['TransType'] == '1') { // 验证交易
                                                          // 核销操作
                $jd = D('Jd', 'Service');
                $jd->init($barcode_info['node_id']);
                $result = $jd->card_consume($barcode_info['request_id']);
                log_write(
                    "jd_consume[" . $barcode_info['request_id'] . "][" .
                         print_r($result, true) . "]");
                if ($result['deal_result'] == 1) {
                    $this->nodo_notify_return('0000');
                } else if ($result['deal_result'] == 2) { // 需要冲正，不返回数据
                    exit();
                } else {
                    $this->nodo_notify_return('1000', 
                        '京东核销失败[' . $jd->error . ']');
                }
            } else if ($this->verify_info['TransType'] == '3') { // 撤销交易 走京东冲正交易
                                                                 // 撤销操作
                $jd = D('Jd', 'Service');
                $jd->init($barcode_info['node_id']);
                $result = $jd->card_consume_reset($barcode_info['request_id']);
                log_write(
                    "jd_consume[" . $barcode_info['request_id'] . "][" .
                         print_r($result, true) . "]");
                if ($result['deal_result'] == 1) {
                    $this->nodo_notify_return('0000');
                } else if ($result['deal_result'] == 2) { // 需要冲正，不返回数据
                    exit();
                } else {
                    $this->nodo_notify_return('1000', 
                        '京东撤销失败[' . $jd->error . ']');
                }
            } else if ($this->verify_info['TransType'] == '2') { // 冲正交易 走京东冲正交易
                                                                 // 冲正操作
                if (($this->ori_pos_trace['trans_type'] != '0') &&
                     ($this->ori_pos_trace['trans_type'] != '3')) { // 非验证冲正，不处理，直接返回成功
                    $this->nodo_notify_return('0000');
                }
                $jd = D('Jd', 'Service');
                $jd->init($barcode_info['node_id']);
                $result = $jd->card_consume_reset($barcode_info['request_id']);
                log_write(
                    "jd_consume[" . $barcode_info['request_id'] . "][" .
                         print_r($result, true) . "]");
                if ($result['deal_result'] == 1) {
                    $this->nodo_notify_return('0000');
                } else if ($result['deal_result'] == 2) { // 需要冲正，不返回数据
                    exit();
                } else {
                    $this->nodo_notify_return('1000', 
                        '京东冲正失败[' . $jd->error . ']');
                }
            }
        }
        // 其他数据全部返回成功
        $this->nodo_notify_return('0000');
        
        $req_seq = $barcode_info['req_seq'];
        $this->verify_info['goods_id'] = $barcode_info['goods_id'];
        $this->verify_info['req_seq'] = $req_seq;
        $this->verify_info['ActivityID'] = $barcode_info['batch_no'];
        $this->verify_info['PhoneNo'] = $barcode_info['phone_no'];
        $this->verify_info['BUSI_ISSPID'] = $barcode_info['node_id'];
        $this->verify_info['price'] = $barcode_info['price'];
        
        // 四、查终端信息
        $where = "pos_id ='" . $this->verify_info['PosId'] . "'";
        $pos_info = M('TposInfo')->where($where)->find();
        
        if (! $pos_info) {
            log_write("未找到终端[" . $this->verify_info['PosId'] . "]");
            $this->notifyreturn('2007');
        }
        $this->verify_info['ISSPID'] = $pos_info['node_id'];
        
        // 检查goods_info
        $where = "goods_id = '" . $this->verify_info['goods_id'] . "'";
        $goods_info = M('TgoodsInfo')->where($where)->find();
        if (! $goods_info) {
            log_write("未找到商品[" . $this->verify_info['goods_id'] . "]");
            $this->notifyreturn('2008');
        }
        // 金额处理
        if ($this->verify_info['TransAmt'] == 0) { // 次数验证，金额取barcode_trace
            $this->verify_info['Amt'] = $barcode_info['price'];
            // 按协议价结算
            $this->verify_info['settle_price'] = $goods_info['settle_price'];
            if ($this->verify_info['RemainTimes'] == 0) {
                $this->verify_info['Status'] = '3'; // 已使用
            } else if ($this->verify_info['RemainTimes'] ==
                 $barcode_info['valid_times']) {
                $this->verify_info['Status'] = '0'; // 未使用
            } else {
                $this->verify_info['Status'] = '2'; // 使用中
            }
        } else {
            $this->verify_info['Amt'] = $this->verify_info['TransAmt'] / 100;
            // 按比例额计算
            $this->verify_info['settle_price'] = $this->verify_info['TransAmt'] /
                 100 * ($goods_info['settle_price'] / $barcode_info['price']);
            if ($this->verify_info['RemainAmt'] == 0) {
                $this->verify_info['Status'] = '3'; // 已使用
            } else if ($this->verify_info['RemainAmt'] == $barcode_info['price']) {
                $this->verify_info['Status'] = '0'; // 未使用
            } else {
                $this->verify_info['Status'] = '2'; // 已使用
            }
        }
        
        // 开启事务 开始交易处理
        M()->startTrans();
        if ($goods_info['source'] == '4') {
            if (! $this->pre_distribution_check($goods_info)) {
                log_write("预校验失败[" . $this->verify_info['goods_id'] . "]");
                $this->notifyreturn('2022');
            }
        }
        // 校验完毕 保存流水 wpos需要特殊处理
        if ($pos_info['pos_type'] == '0') { // 旺财终端
            $this->wpos_save_trace();
        } else {
            $this->zcpt_save_trace();
        }
        // 提交事务 返回
        $this->notifyreturn('0000');
    }
    // 分销预校验
    private function pre_distribution_check($goods_info) {
        // 通过分销卡券 查找原机构号
        $where = "goods_id = '" . $goods_info['purchase_goods_id'] . "'";
        $pre_goods_info = M('TgoodsInfo')->where($where)->find();
        if (! $pre_goods_info) {
            log_write("未找到原分销商品[" . $goods_info['goods_id'] . "]");
            $this->notifyreturn('2009');
        }
        
        // 查找分销协议
        $where = "node_id = '" . $pre_goods_info['node_id'] .
             "' and relation_node_id = '" . $goods_info['node_id'] . "'";
        $sale_relation = M('TsaleRelation')->lock(true)
            ->where($where)
            ->find();
        if (! $sale_relation) {
            log_write("未找到分销协议[" . $pre_goods_info['node_id'] . "]");
            $this->notifyreturn('2010');
        }
        
        if ($sale_relation['control_type'] == '2') {
            // 校验有效期
            $now = date('YmdHis');
            if ($now < $sale_relation['begin_time']) {
                log_write("未到协议开始时间[" . $sale_relation['begin_time'] . "]");
                $this->notifyreturn('2011');
            }
            if ($now > $sale_relation['end_time']) {
                log_write("协议已结束[" . $sale_relation['end_time'] . "]");
                $this->notifyreturn('2012');
            }
            // 计算金额变化
            $change_amt = 0;
            
            if ($this->verify_info['TransType'] == '1') { // 验证
                $change_amt = $this->verify_info['settle_price'];
            } else if ($this->verify_info['TransType'] == '3') { // 撤销
                $change_amt = 0 - $this->verify_info['settle_price'];
            } else if ($this->verify_info['TransType'] == '2') { // 冲正
                if ($this->ori_pos_trace['trans_type'] == '1') { // 冲正撤销
                    $change_amt = $this->verify_info['settle_price'];
                } else { // 冲正验证
                    $change_amt = 0 - $this->verify_info['settle_price'];
                }
            }
            // 当前未结算金额
            $now_amt = $change_amt + $sale_relation['not_settle_amt'];
            if ($change_amt > 0) { // 正交易
                                   // 校验最大金额限制
                if ($now_amt > $sale_relation['max_amt']) {
                    log_write(
                        "超过最大金额限制[" . $change_amt . "|" .
                             $sale_relation['not_settle_amt'] . "|" .
                             $sale_relation['max_amt'] . "]");
                    $this->notifyreturn('2013');
                }
            }
            // 更新处理结果
            $update = array();
            $update['not_settle_amt'] = $now_amt;
            $rs = M()->table('tsale_relation')
                ->where($where)
                ->save($update);
            if (! $rs) {
                log_write(
                    "更新tsale_relation not_settle_amt失败 sql[" . M()->_sql() . "]");
                $this->notifyreturn('2014');
            }
            // 不管控
            if ($sale_relation['control_flag'] == '0') {
                return true;
            }
            if ($change_amt > 0) { // 正交易
                                   // 校验剩余金额是否超过预警金额 峰值-预警值 = 可未结算的值
                if (($now_amt >=
                     ($sale_relation['max_amt'] - $sale_relation['warning_amt'])) &&
                     ($sale_relation['not_settle_amt'] <
                     ($sale_relation['max_amt'] - $sale_relation['warning_amt']))) {
                    log_write("超过预警金额[" . $sale_relation['warning_amt'] . "]");
                    // 发送预警短信 获取短信模板
                    $where = "param_name = 'WARNING_NOTIFY_NOTE'";
                    $param_info = M()->table('tsystem_param t')
                        ->where($where)
                        ->find();
                    if (! $param_info) {
                        log_write("取预警短信发送失败 sql[" . M()->_sql() . "]");
                        // $this->notifyreturn('2015');
                    }
                    
                    // 获取活动号
                    $where = "status = '0' and batch_no is not null and node_id = '" .
                         $pre_goods_info['node_id'] . "'";
                    $tmp_goods_info = M()->table('tgoods_info t')
                        ->order("id")
                        ->limit(1)
                        ->where($where)
                        ->find();
                    if (! $tmp_goods_info) {
                        log_write("取预警短信发送活动号失败 sql[" . M()->_sql() . "]");
                        // $this->notifyreturn('2016');
                    }
                    // 发送短信
                    // 翼码旺财提醒，您与“#NODE_NAME#”合作伙伴的预警条件已满足，请及时处理。
                    if (($sale_relation['party_a_phone'] != null) &&
                         strlen($sale_relation['party_a_phone']) == 11) {
                        $content = $param_info['param_value'];
                        $where = "node_id  = '" . $pre_goods_info['node_id'] .
                             "'";
                        $node_info = M()->table('tnode_info t')
                            ->where($where)
                            ->find();
                        if (! $node_info) {
                            log_write("取预警短信机构信息失败 sql[" . M()->_sql() . "]");
                        } else {
                            $content = str_replace("#NODE_NAME#", 
                                $node_info['node_name'], $content);
                            $this->send_notify($pre_goods_info['node_id'], 
                                $tmp_goods_info['batch_no'], 
                                $sale_relation['party_a_phone'], $content);
                        }
                    }
                    if (($sale_relation['party_b_phone'] != null) &&
                         strlen($sale_relation['party_b_phone']) == 11) {
                        $content = $param_info['param_value'];
                        $where = "node_id  = '" . $goods_info['node_id'] . "'";
                        $node_info = M()->table('tnode_info t')
                            ->where($where)
                            ->find();
                        if (! $node_info) {
                            log_write("取预警短信机构信息失败 sql[" . M()->_sql() . "]");
                        } else {
                            $content = str_replace("#NODE_NAME#", 
                                $node_info['node_name'], $content);
                            $this->send_notify($pre_goods_info['node_id'], 
                                $tmp_goods_info['batch_no'], 
                                $sale_relation['party_b_phone'], $content);
                        }
                    }
                }
            }
            return true;
        } else {
            log_write("无需校验 [" . $sale_relation['control_type'] . "]");
            return true;
        }
        return false;
    }

    private function wpos_save_trace() {
        // 更新流水信息
        $where = "pos_id ='" . $this->verify_info['PosId'] . "' and pos_seq = '" .
             $this->verify_info['PosSeq'] . "'";
        
        $pos_trace['req_seq'] = $this->verify_info['req_seq'];
        $pos_trace['syn_seq'] = $this->verify_info['ReqSeq'];
        $pos_trace['ori_syn_seq'] = $this->verify_info['OriVerifySeq'];
        $pos_trace['goods_id'] = $this->verify_info['goods_id'];
        $rs = M('TposTrace')->where($where)->save($pos_trace);
        if (! $rs) {
            log_write("wpos更新终端流水[TposTrace]失败");
        }
        
        // 更新凭证信息
        $req_seq = $this->verify_info['req_seq']; // 源请求流水
        $where = "req_seq = '" . $req_seq . "'";
        
        $barcode_trace['use_status'] = '0';
        if ($verify_info['Status'] == '2') {
            $barcode_trace['use_status'] = '1';
        } else if ($verify_info['Status'] == '3') {
            $barcode_trace['use_status'] = '2';
        }
        $rs = M('TbarcodeTrace')->where($where)->save($barcode_trace);
        if ($rs === false) {
            log_write("wpos更新条码流水[TbarcodeTrace]失败");
        }
    }
    
    // 保存流水
    private function zcpt_save_trace() {
        
        // 获取交易类型
        $trans_type = $this->verify_info['TransType']; // 交易类型
        $req_seq = $this->verify_info['req_seq']; // 请求流水
        $org_seq = $this->verify_info['OriVerifySeq']; // 源请求流水
        
        if ($trans_type == '1') // 验证
{
            // 记流水
            $pos_trace = array();
            $pos_trace['pos_id'] = $this->verify_info['PosId'];
            $pos_trace['pos_seq'] = $this->verify_info['PosSeq'];
            $pos_trace['trans_type'] = '0';
            $pos_trace['status'] = '0';
            $pos_trace['is_canceled'] = '0';
            $pos_trace['exchange_amt'] = $this->verify_info['Amt'];
            $pos_trace['trans_time'] = $this->verify_info['TransTime'];
            $pos_trace['batch_no'] = $this->verify_info['ActivityID'];
            $pos_trace['ret_code'] = 0;
            $pos_trace['ret_desc'] = '验证成功';
            $pos_trace['user_id'] = '00000000'; // epos,68同步的user_id记录00000000
            $pos_trace['user_name'] = '00000000'; // epos,68同步的user_id记录00000000
            $pos_trace['phone_no'] = $this->verify_info['PhoneNo'];
            $pos_trace['assistant_no_back'] = '0000';
            $pos_trace['assistant_no_md5'] = '00000000000000000000000000000000';
            $pos_trace['code_type'] = '008'; // 支撑预校验
            $pos_trace['stat_id'] = 0;
            $pos_trace['node_id'] = $this->verify_info['ISSPID'];
            $pos_trace['busi_node_id'] = $this->verify_info['BUSI_ISSPID'];
            $pos_trace['req_seq'] = $req_seq;
            $pos_trace['syn_seq'] = $this->verify_info['ReqSeq'];
            $pos_trace['goods_id'] = $this->verify_info['goods_id'];
            
            $rs = M('TposTrace')->Add($pos_trace);
            if (! $rs) {
                log_write("记录终端流水[TposTrace]失败");
                $this->notifyreturn('2017');
            }
            
            // 更改凭证信息
            $where = "req_seq = '" . $req_seq . "'";
            
            $barcode_trace['use_status'] = '2';
            if ($this->verify_info['Status'] == '2') {
                $barcode_trace['use_status'] = '1';
            }
            $barcode_trace['use_time'] = $this->verify_info['TransTime'];
            $rs = M('TbarcodeTrace')->where($where)->save($barcode_trace);
            if (! $rs) {
                log_write("修改凭证状态[TbarcodeTrace]失败" . M()->_sql());
                $this->notifyreturn('2018');
            }
            // 统计
            $where = "NODE_ID ='" . $this->verify_info['ISSPID'] .
                 "' and BATCH_NO ='" . $this->verify_info['ActivityID'] .
                 "' and POS_ID ='" . $this->verify_info['PosId'] .
                 "' and TRANS_DATE ='" . date('Y-m-d') . "'";
            $pos_day_count = M('TposDayCount')->where($where)->find();
            if (! $pos_day_count) {
                $pos_day_count['node_id'] = $this->verify_info['ISSPID'];
                $pos_day_count['pos_id'] = $this->verify_info['PosId'];
                $pos_day_count['batch_no'] = $this->verify_info['ActivityID'];
                $pos_day_count['trans_date'] = date('Y-m-d');
                $pos_day_count['send_num'] = 0;
                $pos_day_count['send_amt'] = 0;
                $pos_day_count['verify_num'] = 1;
                $pos_day_count['verify_amt'] = $this->verify_info['Amt'];
                $pos_day_count['cancel_num'] = 0;
                $pos_day_count['cancel_amt'] = 0;
                $pos_day_count['goods_id'] = $this->verify_info['goods_id'];
                $rs = M('TposDayCount')->add($pos_day_count);
                if (! $rs) {
                    log_write("记录统计信息[tpos_day_count]失败");
                }
            } else {
                $new_day_count = array();
                $new_day_count['verify_num'] = $pos_day_count['verify_num'] + 1;
                $new_day_count['verify_amt'] = $pos_day_count['verify_amt'] +
                     $this->verify_info['Amt'];
                $new_day_count['goods_id'] = $this->verify_info['goods_id'];
                $rs = M('TposDayCount')->where($where)->save($new_day_count);
                if (! $rs) {
                    log_write("更新统计信息[tpos_day_count]失败");
                }
            }
        } else if ($trans_type == '3') // 撤销
{
            $where = "pos_id ='" . $this->verify_info['PosId'] .
                 "' and pos_seq = '" . $this->verify_info['OriVerifySeq'] . "'";
            // 更新源流水
            $new_pos_trace['is_canceled'] = '1';
            $rs = M('TposTrace')->where($where)->save($new_pos_trace);
            if (! $rs) {
                log_write("更新源终端流水[TposTrace]失败");
                $this->notifyreturn('2019');
            }
            
            // 记流水
            $pos_trace = array();
            $pos_trace['pos_id'] = $this->verify_info['PosId'];
            $pos_trace['pos_seq'] = $this->verify_info['PosSeq'];
            $pos_trace['org_posseq'] = $org_seq;
            $pos_trace['trans_type'] = '1';
            $pos_trace['status'] = '0';
            $pos_trace['is_canceled'] = '0';
            $pos_trace['exchange_amt'] = $this->verify_info['Amt'];
            $pos_trace['trans_time'] = $this->verify_info['TransTime'];
            $pos_trace['batch_no'] = $this->verify_info['ActivityID'];
            $pos_trace['ret_code'] = 0;
            $pos_trace['ret_desc'] = '撤销成功';
            $pos_trace['user_id'] = '00000000'; // epos,68同步的user_id记录00000000
            $pos_trace['user_name'] = '00000000'; // epos,68同步的user_id记录00000000
            $pos_trace['phone_no'] = $this->verify_info['PhoneNo'];
            $pos_trace['assistant_no_back'] = '0000';
            $pos_trace['assistant_no_md5'] = '00000000000000000000000000000000';
            $pos_trace['code_type'] = '007'; // 流水同步数据
            $pos_trace['stat_id'] = 0;
            $pos_trace['node_id'] = $this->verify_info['ISSPID'];
            $pos_trace['req_seq'] = $req_seq;
            $pos_trace['syn_seq'] = $this->verify_info['ReqSeq'];
            $pos_trace['ori_syn_seq'] = $org_seq;
            $pos_trace['goods_id'] = $this->verify_info['goods_id'];
            
            $rs = M('TposTrace')->Add($pos_trace);
            if (! $rs) {
                log_write("记录终端流水[TposTrace]失败");
                $this->notifyreturn('2017');
            }
            
            // 更改凭证信息
            $where = "req_seq = '" . $req_seq . "'";
            
            $barcode_trace['use_status'] = '0';
            if ($this->verify_info['Status'] == '2') {
                $barcode_trace['use_status'] = '1';
            }
            $rs = M('TbarcodeTrace')->where($where)->save($barcode_trace);
            if ($rs === false) {
                log_write("修改凭证状态[TbarcodeTrace]失败");
                $this->notifyreturn('2018');
            }
            // 统计
            $where = "NODE_ID ='" . $this->verify_info['ISSPID'] .
                 "' and BATCH_NO ='" . $this->verify_info['ActivityID'] .
                 "' and POS_ID ='" . $this->verify_info['PosId'] .
                 "' and TRANS_DATE ='" . date('Y-m-d') . "'";
            $pos_day_count = M('TposDayCount')->where($where)->find();
            if (! $pos_day_count) {
                $pos_day_count['node_id'] = $this->verify_info['ISSPID'];
                $pos_day_count['pos_id'] = $this->verify_info['PosId'];
                $pos_day_count['batch_no'] = $this->verify_info['ActivityID'];
                $pos_day_count['trans_date'] = date('Y-m-d');
                $pos_day_count['send_num'] = 0;
                $pos_day_count['send_amt'] = 0;
                $pos_day_count['verify_num'] = 0;
                $pos_day_count['verify_amt'] = 0;
                $pos_day_count['cancel_num'] = 1;
                $pos_day_count['cancel_amt'] = $this->verify_info['Amt'];
                $pos_day_count['goods_id'] = $this->verify_info['goods_id'];
                $rs = M('TposDayCount')->add($pos_day_count);
                if (! $rs) {
                    log_write("记录统计信息[tpos_day_count]失败");
                }
            } else {
                $new_day_count = array();
                $new_day_count['cancel_num'] = $pos_day_count['cancel_num'] + 1;
                $new_day_count['cancel_amt'] = $pos_day_count['cancel_amt'] +
                     $this->verify_info['Amt'];
                $new_day_count['goods_id'] = $this->verify_info['goods_id'];
                $rs = M('TposDayCount')->where($where)->save($new_day_count);
                if (! $rs) {
                    log_write("更新统计信息[tpos_day_count]失败");
                }
            }
        } else if ($trans_type == '2') // 冲正
{
            // 先获取冲正源流水
            $where = "pos_id ='" . $this->verify_info['PosId'] .
                 "' and pos_seq = '" . $this->verify_info['OriVerifySeq'] . "'";
            // 更新源流水
            $new_pos_trace['status'] = '2';
            $rs = M('TposTrace')->where($where)->save($new_pos_trace);
            if (! $rs) {
                log_write("更新源终端流水[TposTrace]失败");
                $this->notifyreturn('2019');
            }
            
            // 记流水
            $pos_trace = array();
            $pos_trace['pos_id'] = $this->verify_info['PosId'];
            $pos_trace['pos_seq'] = $this->verify_info['PosSeq'];
            $pos_trace['org_posseq'] = $org_seq;
            $pos_trace['trans_type'] = '2';
            $pos_trace['status'] = '0';
            $pos_trace['is_canceled'] = '0';
            $pos_trace['exchange_amt'] = $this->verify_info['Amt'];
            $pos_trace['trans_time'] = $this->verify_info['TransTime'];
            $pos_trace['batch_no'] = $this->verify_info['ActivityID'];
            $pos_trace['ret_code'] = 0;
            $pos_trace['ret_desc'] = '冲正成功';
            $pos_trace['user_id'] = '00000000'; // epos,68同步的user_id记录00000000
            $pos_trace['user_name'] = '00000000'; // epos,68同步的user_id记录00000000
            $pos_trace['phone_no'] = $this->verify_info['PhoneNo'];
            $pos_trace['assistant_no_back'] = '0000';
            $pos_trace['assistant_no_md5'] = '00000000000000000000000000000000';
            $pos_trace['code_type'] = '007'; // 流水同步数据
            $pos_trace['stat_id'] = 0;
            $pos_trace['node_id'] = $this->verify_info['ISSPID'];
            $pos_trace['req_seq'] = $req_seq;
            $pos_trace['syn_seq'] = $this->verify_info['ReqSeq'];
            $pos_trace['ori_syn_seq'] = $org_seq;
            $pos_trace['goods_id'] = $this->verify_info['goods_id'];
            
            $rs = M('TposTrace')->Add($pos_trace);
            if (! $rs) {
                log_write("记录终端流水[TposTrace]失败");
                $this->notifyreturn('2017');
            }
            
            // 更改凭证信息
            $where = "req_seq = '" . $req_seq . "'";
            
            $barcode_trace['use_status'] = '0';
            if ($this->verify_info['Status'] == '2') {
                $barcode_trace['use_status'] = '1';
            } else if ($this->verify_info['Status'] == '3') {
                $barcode_trace['use_status'] = '2';
            }
            $rs = M('TbarcodeTrace')->where($where)->save($barcode_trace);
            if ($rs === false) {
                log_write("修改凭证状态[TbarcodeTrace]失败");
                $this->notifyreturn('2018');
            }
            // 统计
            $where = "NODE_ID ='" . $this->verify_info['ISSPID'] .
                 "' and BATCH_NO ='" . $this->verify_info['ActivityID'] .
                 "' and POS_ID ='" . $this->verify_info['PosId'] .
                 "' and TRANS_DATE ='" . date('Y-m-d') . "'";
            $pos_day_count = M('TposDayCount')->where($where)->find();
            if (! $pos_day_count) {
                $pos_day_count['node_id'] = $this->verify_info['ISSPID'];
                $pos_day_count['pos_id'] = $this->verify_info['PosId'];
                $pos_day_count['batch_no'] = $this->verify_info['ActivityID'];
                $pos_day_count['trans_date'] = date('Y-m-d');
                $pos_day_count['send_num'] = 0;
                $pos_day_count['send_amt'] = 0;
                $pos_day_count['verify_num'] = - 1;
                $pos_day_count['verify_amt'] = 0 - $this->verify_info['Amt'];
                $pos_day_count['cancel_num'] = 0;
                $pos_day_count['cancel_amt'] = 0;
                $pos_day_count['goods_id'] = $this->verify_info['goods_id'];
                if ($this->ori_pos_trace['trans_type'] == '1') // 撤销冲正
{
                    $pos_day_count['verify_num'] = 0;
                    $pos_day_count['verify_amt'] = 0;
                    $pos_day_count['cancel_num'] = - 1;
                    $pos_day_count['cancel_amt'] = 0 - $this->verify_info['Amt'];
                }
                $rs = M('TposDayCount')->add($pos_day_count);
                if (! $rs) {
                    log_write("记录统计信息[tpos_day_count]失败");
                }
            } else {
                $new_day_count = array();
                if ($this->ori_pos_trace['trans_type'] == '0') // 验证冲正
{
                    $new_day_count['verify_num'] = $pos_day_count['verify_num'] -
                         1;
                    $new_day_count['verify_amt'] = $pos_day_count['verify_amt'] -
                         $this->verify_info['Amt'];
                } else // 撤销冲正
{
                    $new_day_count['cancel_num'] = $pos_day_count['cancel_num'] -
                         1;
                    $new_day_count['cancel_amt'] = $pos_day_count['cancel_amt'] -
                         $this->verify_info['Amt'];
                }
                $new_day_count['goods_id'] = $this->verify_info['goods_id'];
                $rs = M('TposDayCount')->where($where)->save($new_day_count);
                if (! $rs) {
                    log_write("更新统计信息[tpos_day_count]失败");
                }
            }
        }
    }
    // 发通知短信
    private function send_notify($node_id, $batch_no, $phoneNo, $text) {
        $TransactionID = date("YmdHis") . mt_rand(100000, 999999);
        // 请求参数
        $req_array = array(
            'NotifyReq' => array(
                'TransactionID' => $TransactionID, 
                'ISSPID' => $node_id, 
                'SystemID' => C('ISS_SYSTEM_ID'), 
                'SendLevel' => '1', 
                'Recipients' => array(
                    'Number' => $phoneNo),  // 手机号
                'SendClass' => 'SMS', 
                'MessageText' => $text,  // 短信内容
                'Subject' => '', 
                'ActivityID' => $batch_no, 
                'ChannelID' => '', 
                'ExtentCode' => ''));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $resp_array = $RemoteRequest->requestIssServ($req_array);
        // dump($resp_array);exit;
        $ret_msg = $resp_array['NotifyRes']['Status'];
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
             $ret_msg['StatusCode'] != '0001')) {
            return false;
        }
        return true;
    }
    // 通知应答
    private function notifyreturn($resp_id = '0000') {
        if ($resp_id !== '0000') {
            // 事务回滚
            M()->rollback();
            // 记流水
            $pos_trace = array();
            $pos_trace['pos_id'] = $this->verify_info['PosId'];
            $pos_trace['pos_seq'] = $this->verify_info['PosSeq'];
            $pos_trace['org_posseq'] = $this->verify_info['OrgPosSeq'];
            $pos_trace['trans_type'] = $this->verify_info['trans_type'];
            $pos_trace['status'] = '1';
            $pos_trace['is_canceled'] = '0';
            $pos_trace['exchange_amt'] = $this->verify_info['Amt'];
            $pos_trace['trans_time'] = $this->verify_info['TransTime'];
            $pos_trace['batch_no'] = $this->verify_info['ActivityID'];
            $pos_trace['ret_code'] = $resp_id;
            $pos_trace['ret_desc'] = $responseDesc[$resp_id];
            $pos_trace['user_id'] = '00000000'; // epos,68同步的user_id记录00000000
            $pos_trace['user_name'] = '00000000'; // epos,68同步的user_id记录00000000
            $pos_trace['phone_no'] = $this->verify_info['PhoneNo'];
            $pos_trace['assistant_no_back'] = '0000';
            $pos_trace['assistant_no_md5'] = '00000000000000000000000000000000';
            $pos_trace['code_type'] = '008'; // 预校验数据
            $pos_trace['stat_id'] = 0;
            $pos_trace['node_id'] = $this->verify_info['ISSPID'];
            $pos_trace['req_seq'] = $this->verify_info['req_seq'];
            $pos_trace['syn_seq'] = $this->verify_info['ReqSeq'];
            $pos_trace['ori_syn_seq'] = $org_seq;
            $pos_trace['goods_id'] = $this->verify_info['goods_id'];
            M('TposTrace')->Add($pos_trace);
        } else {
            M()->commit(); // 提交事务
        }
        
        $resp_xml = '<?xml version="1.0" encoding="gbk"?><' . $this->responseType .
             '><ReqSeq>' . $this->verify_info['ReqSeq'] . '</ReqSeq><Status>' .
             $resp_id . '</Status><StatusText>' .
             iconv('utf8', 'gbk', $this->responseDesc[$resp_id]) .
             '</StatusText></' . $this->responseType . '>';
        echo $resp_xml;
        log_write($resp_xml, 'RESPONSE');
        exit();
    }

    private function nodo_notify_return($resp_id = '0000', $resp_desc = '预校验成功') {
        $resp_xml = '<?xml version="1.0" encoding="gbk"?><' . $this->responseType .
             '><ReqSeq>' . $this->verify_info['ReqSeq'] . '</ReqSeq><Status>' .
             $resp_id . '</Status><StatusText>' .
             iconv('utf8', 'gbk', $resp_desc) . '</StatusText></' .
             $this->responseType . '>';
        echo $resp_xml;
        log_write($resp_xml, 'RESPONSE');
        exit();
    }

}