<?php

/* 返佣-全民营销 */
class ReturnCommissionService {

    public $opt = array();

    public function __construct() {
        C('LOG_PATH', LOG_PATH . 'return_deal_'); // 重新定义目志目录
    }

    /* 设置参数 */
    public function setopt() {
    }

    /*
     * 返佣 $marketing_info_id 营销活动ID PS:如果是小店推广链接，该值不传 $phone_no 被返佣的手机号码
     * $from_phone_no 导致返佣产生的用户手机号码 $return_channel 返佣产生渠道来源 0-新浪微博 1-QQ空间
     * 2-腾讯微博 3-人人网 4-微信朋友圈 9-其他 $relation_id 返佣关联流水号 如订单等 用于关联查询返佣记录
     * $trans_type 0 - 抽奖类交易 1-购买类交易 2-转发分享成功 3-页面点击流量 $trans_money 购买类交易的订单金额
     * $node_id 机构号 $ip 用户IP
     */
    public function return_commission($marketing_info_id, $phone_no, 
        $return_channel, $from_phone_no, $relation_id, $trans_type = '0', 
        $trans_money = 0, $ip = '0.0.0.0') {
        // 检查Tmarketing_info 状态
        $this->_log(
            "return_commission inparm marketing_info_id:" . $marketing_info_id .
                 " phone_no:" . $phone_no . " return_channel:" . $return_channel .
                 " from_phone_no:" . $from_phone_no . " relation_id:" .
                 $relation_id . " trans_type:" . $trans_type . " trans_money:" .
                 $trans_money . " ip:" . $ip);
        $where = "id ='" . $marketing_info_id . "' and status = '1'";
        $marketing_info = M()->table('tmarketing_info i')
            ->where($where)
            ->find();
        
        if (! $marketing_info) {
            return array(
                'code' => '1001', 
                'desc' => '未找到状态正常的营销活动');
        }
        
        // 小店推广特殊处理
        if ($marketing_info['batch_type'] == '29') {
            $now_time = date('YmdHis');
            $where = "marketing_info_id ='" . $marketing_info_id .
                 "' and return_commission_start_time <= '" . $now_time .
                 "' and return_commission_end_time >= '" . $now_time .
                 "' and return_commission_class = '2' and status = '0' ";
            $return_commission_info = M()->table('treturn_commission_info i')
                ->where($where)
                ->find();
            if (! $return_commission_info) {
                log_write(M()->_sql());
                return array(
                    'code' => '1011', 
                    'desc' => '该时间段没有小店推广计划');
            }
        } else {
            if ($marketing_info['return_commission_flag'] != '1') {
                return array(
                    'code' => '1002', 
                    'desc' => '该营销活动未设置返佣信息');
            }
            $where = "marketing_info_id =" . $marketing_info_id;
            $return_commission_info = M()->table('treturn_commission_info i')
                ->where($where)
                ->find();
            if (! $return_commission_info) {
                return array(
                    'code' => '1003', 
                    'desc' => '该营销活动未设置返佣信息');
            }
            // 根据版本进行判断 旧版本用原来的逻辑
            /*
             * if ($return_commission_info['version_flag'] != '2') { $ret_arr =
             * $this->return_commission_v1($marketing_info_id, $phone_no,
             * $return_channel, $from_phone_no, $relation_id, $trans_type,
             * $trans_money); return $ret_arr; }
             */
        }
        
        // 判断是否达成返佣条件 按照trans_type查找是否有对应的返佣达成条件 0 - 抽奖类交易 1-购买类交易 2-转发分享成功
        // 3-页面点击流量
        $return_condition = '2';
        if ($trans_type == 1) { // 购买交易
            $return_condition = '1';
        } else if ($trans_type == 2) { // 转发分享成功
            $return_condition = '3';
        } else if ($trans_type == 3) { // 页面点击流量
            $return_condition = '6';
        }
        $where = array(
            'return_condition' => $return_condition, 
            'return_commission_id' => $return_commission_info['id']);
        $return_commission_ext_info = M()->table(
            'treturn_commission_info_ext i')
            ->where($where)
            ->find();
        $return_commission_ext_id = $return_commission_ext_info ? $return_commission_ext_info['id'] : '';
        
        // 记录操作流水
        $return_commission_trace_list = array();
        $return_commission_trace_list['node_id'] = $return_commission_info['node_id'];
        $return_commission_trace_list['return_commission_id'] = $return_commission_info['id'];
        $return_commission_trace_list['return_commission_ext_id'] = $return_commission_ext_id;
        $return_commission_trace_list['marketing_info_id'] = $marketing_info_id;
        $return_commission_trace_list['add_time'] = date("YmdHis");
        $return_commission_trace_list['commission_type'] = $return_commission_info['commission_type'];
        $return_commission_trace_list['phone_no'] = $phone_no;
        $return_commission_trace_list['relation_id'] = $relation_id;
        $return_commission_trace_list['return_channel'] = $return_channel;
        $return_commission_trace_list['from_phone_no'] = $from_phone_no;
        $return_commission_trace_list['trans_amount'] = $trans_money;
        $return_commission_trace_list['ip'] = $ip;
        $return_commission_trace_list['trans_type'] = $trans_type;
        
        $rs = M()->table('treturn_commission_trace_list ')->add(
            $return_commission_trace_list);
        if (! $rs) {
            return array(
                'code' => '1005', 
                'desc' => '记录return_commission_trace_list 失败');
        }
        // 获取主键ID
        $return_commission_trace_list['id'] = $rs;
        
        if ($trans_type == 2) { // 更新转发统计
            $this->_log("return_commission 更新转发统计");
            $this->effect_stat($marketing_info_id, $return_channel, 0, 
                $phone_no);
        } else if ($trans_type == 3) { // 活动页面点击流量
                                       // 进行IP排重校验
            $where = "return_commission_id = " . $return_commission_info['id'] .
                 " and add_time like '" . date('Ymd') . "%' and phone_no = '" .
                 $phone_no . "' and ip = '" . $ip . "'";
            $count = M()->table('treturn_commission_trace_list t')
                ->where($where)
                ->count();
            if ($count > 1) {
                return array(
                    'code' => '1012', 
                    'desc' => '该IP已经参与过活动页面点击流量');
            }
            $this->effect_stat($marketing_info_id, $return_channel, 1, 
                $phone_no);
        } else if ($trans_type == 0) { // 抽奖类交易
            $this->_log("return_commission 参与统计");
            $this->effect_stat($marketing_info_id, $return_channel, 3, 
                $phone_no);
        } else if ($trans_type == 1) { // 抽奖类交易
            $this->_log("return_commission 购买统计");
            $other = array(
                'trans_money' => $trans_money);
            $this->effect_stat($marketing_info_id, $return_channel, 4, 
                $phone_no, $other);
        }
        
        // 判断是否达成返佣条件 按照trans_type查找是否有对应的返佣达成条件 0 - 抽奖类交易 1-购买类交易 2-转发分享成功
        // 3-页面点击流量
        if ($trans_type == 1) { // 购买交易
                                // $where = "return_condition ='1' and
                                // return_commission_id = " .
                                // $return_commission_info['id'];
                                // $return_commission_ext_info =
                                // M()->table('treturn_commission_info_ext
                                // i')->where($where)->find();
            if (! $return_commission_ext_info) {
                return array(
                    'code' => '1012', 
                    'desc' => '未达成参与条件');
            }
        } else if ($trans_type == 2) { // 转发分享成功
                                       // $where = "return_condition ='3' and
                                       // return_commission_id = " .
                                       // $return_commission_info['id'];
                                       // $return_commission_ext_info =
                                       // M()->table('treturn_commission_info_ext
                                       // i')->where($where)->find();
            if (! $return_commission_ext_info) {
                return array(
                    'code' => '1013', 
                    'desc' => '未达成参与条件');
            }
        } else if ($trans_type == 3) { // 页面点击流量
                                       // $where = "return_condition ='6' and
                                       // return_commission_id = " .
                                       // $return_commission_info['id'];
                                       // $return_commission_ext_info =
                                       // M()->table('treturn_commission_info_ext
                                       // i')->where($where)->find();
            if (! $return_commission_ext_info) {
                return array(
                    'code' => '1014', 
                    'desc' => '未达成参与条件');
            }
        } else { // 抽奖类交易
                 // $where = "return_condition ='2' and return_commission_id = "
                 // . $return_commission_info['id'];
                 // $return_commission_ext_info =
                 // M()->table('treturn_commission_info_ext
                 // i')->where($where)->find();
            if (! $return_commission_ext_info) {
                return array(
                    'code' => '1015', 
                    'desc' => '未达成参与条件');
            }
        }
        
        $where = "phone_no ='" . $phone_no .
             "' and trace_id is null and trans_type = '" . $trans_type .
             "' and return_commission_id = " . $return_commission_info['id'];
        $count = M()->table('treturn_commission_trace_list t')
            ->where($where)
            ->count();
        if ($count < $return_commission_ext_info['return_condition_num']) {
            return array(
                'code' => '1048', 
                'desc' => '未达成参与条件');
        }
        
        // 判断是否有参与次数限制
        if ($return_commission_ext_info['phone_limit_flag'] == 1) {
            $where = "phone_no ='" . $phone_no .
                 "' and return_commission_ext_id = " .
                 $return_commission_ext_info['id'];
            $count = M()->table('treturn_commission_trace t')
                ->where($where)
                ->count();
            if ($count >= $return_commission_ext_info['phone_limit_num']) {
                $this->_log("return_commission 该手机号码已经超过活动参与次数");
                return array(
                    'code' => '1008', 
                    'desc' => '该手机号码已经超过活动参与次数');
            }
        }
        if (($trans_type != 2) && ($trans_type != 3)) { // 分享转发+点击流量不需要做以下判断
                                                        // 判断是否自己分享自己用
            if ($phone_no == $from_phone_no) {
                return array(
                    'code' => '1009', 
                    'desc' => '不允许自己分享自己用');
            }
            // 判断是否分享返佣关系是否已存在 不允许多次返佣 2 3 10 20 抽奖、市场调研、有奖答题、投票
            // 去掉此限制 by zhoukai @2015年3月13日10:42:37（产品未有明确的需求要求这样做）
            /*
             * if ($marketing_info['batch_type'] == '2' ||
             * $marketing_info['batch_type'] == '3' ||
             * $marketing_info['batch_type'] == '10' ||
             * $marketing_info['batch_type'] == '20'){ $where = "phone_no
             * ='".$phone_no."' and from_phone_no = '".$from_phone_no."' and
             * return_commission_ext_id = ". $return_commission_ext_info['id'];
             * $count = M()->table('treturn_commission_trace
             * t')->where($where)->count(); if ($count >= 1 ){ return
             * array('code'=>'1010', 'desc'=>'返佣关系已存在'); } }
             */
        }
        // 开启事务
        M()->startTrans();
        // 保存返佣流水
        $return_commission_trace = array();
        $return_commission_trace['node_id'] = $return_commission_info['node_id'];
        $return_commission_trace['phone_no'] = $phone_no;
        $return_commission_trace['from_phone_no'] = $from_phone_no;
        $return_commission_trace['return_commission_id'] = $return_commission_info['id'];
        $return_commission_trace['return_commission_ext_id'] = $return_commission_ext_info['id'];
        $return_commission_trace['marketing_info_id'] = $return_commission_info['marketing_info_id'];
        $return_commission_trace['return_add_time'] = date("YmdHis");
        $return_commission_trace['commission_type'] = $return_commission_ext_info['commission_type'];
        switch ($return_commission_trace['commission_type']) {
            case 0: // 卡券
                    // $return_num =
                    // $return_commission_ext_info['return_goods_num'];
                $return_commission_trace['send_status'] = '0';
                // $return_content = "";
                $return_commission_trace['send_request_id'] = date("YmdHis") .
                     rand(100000, 999999); // 凭证发送单号
                $return_commission_trace['send_deal_time'] = date("YmdHis"); // 卡券发送最后处理时间
                $return_commission_trace['return_acount'] = $phone_no;
                // 查找goods_info表中对应的金额计入统计
                $where = "batch_no ='" . $return_commission_ext_info['batch_no'] .
                     "'";
                $goods_info = M()->table('tgoods_info i')
                    ->where($where)
                    ->find();
                if (! $goods_info) {
                    return array(
                        'code' => '1007', 
                        'desc' => '该营销活动卡券配置有误');
                }
                // 计数为1
                $return_num = 1;
                $return_content = $goods_info['goods_name'];
                break;
            case 1: // Q币
                $return_num = $return_commission_ext_info['return_qcoint_num'];
                $return_content = $return_num . "Q币";
                break;
            case 2: // 话费
                $return_num = $return_commission_ext_info['return_phone_charge'];
                $return_content = $return_num . "元话费";
                break;
            case 3: // 现金
                if ($return_commission_ext_info['return_money_type'] == 0) { // 指定金额
                    $return_num = $return_commission_ext_info['return_money'];
                } else {
                    // 如果是现金返佣 且按比例进行，并且份数大于1
                    if ($return_commission_ext_info['return_condition_num'] > 1) {
                        $where = "phone_no ='" . $phone_no .
                             "'  and trans_type = '1' and trace_id is null and return_commission_id = " .
                             $return_commission_info['id'];
                        $return_commission_trace_list_info = M()->table(
                            'treturn_commission_trace_list')
                            ->order("id")
                            ->limit(
                            $return_commission_ext_info['return_condition_num'])
                            ->lock(true)
                            ->where($where)
                            ->select();
                        if (count($return_commission_trace_list_info) <
                             $return_commission_ext_info['return_condition_num']) {
                            M()->rollback();
                            return array(
                                'code' => '1048', 
                                'desc' => '未达成参与条件');
                        }
                        // 计算总金额
                        $total_trans_money = 0;
                        foreach ($return_commission_trace_list_info as $return_commission_trace_list_info_tmp) {
                            $total_trans_money += $return_commission_trace_list_info_tmp['trans_amount'];
                        }
                        $return_num = $total_trans_money *
                             $return_commission_ext_info['return_money'] / 100;
                    } else
                        $return_num = $trans_money *
                             $return_commission_ext_info['return_money'] / 100;
                }
                // 判断返佣金额是否超过总金额 如超过需要停止
                if ($return_commission_ext_info['return_money_limit_flag'] == '1') {
                    $ext_where = "id =" . $return_commission_ext_info['id'];
                    $return_commission_info_tmp = M()->table(
                        'treturn_commission_info_ext i')
                        ->lock(true)
                        ->where($ext_where)
                        ->find();
                    if (! $return_commission_info_tmp) {
                        M()->rollback();
                        return array(
                            'code' => '1003', 
                            'desc' => '该营销活动未设置返佣信息');
                    }
                    // 比较金额
                    $now_stat_money = $return_num +
                         $return_commission_info_tmp['return_money_stat'];
                    if ($return_commission_ext_info['return_money_limit'] <=
                         $now_stat_money) {
                        M()->rollback();
                        // 停止该返佣活动
                        $where = "id =" . $return_commission_info['id'];
                        $update = array();
                        $update['status'] = '2';
                        $rs = M()->table('treturn_commission_info')
                            ->where($where)
                            ->save($update);
                        if (! $rs) {
                            return array(
                                'code' => '1009', 
                                'desc' => '停止该返佣活动 treturn_commission_info status 失败');
                        }
                        $this->_log(
                            "return_commission 该营销活动返佣金额已超过总金额:" . M()->_sql());
                        return array(
                            'code' => '1008', 
                            'desc' => '该营销活动返佣金额已超过总金额');
                    }
                    
                    // 更新金额统计
                    $return_commission_info_update = array();
                    $return_commission_info_update['return_money_stat'] = $now_stat_money;
                    $rs = M()->table('treturn_commission_info_ext')
                        ->where($ext_where)
                        ->save($return_commission_info_update);
                    if (! $rs) {
                        M()->rollback();
                        return array(
                            'code' => '1009', 
                            'desc' => '更新return_commission_ext_info return_money_stat 失败');
                    }
                }
                
                $return_content = $return_num . "元";
                $return_commission_trace['yima_withhold_stauts'] = '0';
                $return_commission_trace['yima_withhold_time'] = date("YmdHis"); // 翼码代扣佣金最后处理时间
                $return_commission_trace['yima_withhold_requst_id'] = date(
                    "YmdHis") . $this->yima_withhold_reqid(); // 翼码代扣佣金流水号
                $return_commission_trace['yima_withhold_commission'] = 0;
                break;
            default:
                return array(
                    'code' => '1004', 
                    'desc' => '该营销活动的返佣类型有误');
        }
        ;
        $return_commission_trace['return_num'] = $return_num;
        $return_commission_trace['return_content'] = $return_content;
        $return_commission_trace['return_status'] = '0';
        $return_commission_trace['return_channel'] = $return_channel;
        $return_commission_trace['relation_id'] = $relation_id;
        $return_commission_trace['batch_no'] = $return_commission_ext_info['batch_no'];
        $return_commission_trace['b_id'] = $return_commission_ext_info['b_id'];
        $return_commission_trace['data_from'] = 'R';
        
        $rs = M()->table('treturn_commission_trace ')->add(
            $return_commission_trace);
        if (! $rs) {
            M()->rollback();
            return array(
                'code' => '1005', 
                'desc' => '记录treturn_commission_trace 失败');
        }
        // 获取主键ID
        $return_commission_trace['id'] = $rs;
        
        $this->_log(print_r($return_commission_ext_info, true));
        // 更新操作流水
        if ($return_commission_ext_info['return_condition_num'] == 1) { // 参与条件=1
            $where = " id = " . $return_commission_trace_list['id'];
            $return_commission_trace_list_new = array();
            $return_commission_trace_list_new['trace_id'] = $return_commission_trace['id'];
            $return_commission_trace_list_new['return_commission_ext_id'] = $return_commission_ext_info['id'];
            $rs = M()->table('treturn_commission_trace_list')
                ->where($where)
                ->save($return_commission_trace_list_new);
            $this->_log(M()->_sql());
            if (! $rs) {
                M()->rollback();
                return array(
                    'code' => '1006', 
                    'desc' => '更新treturn_commission_trace_list trace_id 失败');
            }
        } else {
            $where = "phone_no ='" . $phone_no .
                 "' and trace_id is null and return_commission_id = " .
                 $return_commission_info['id'] . " and trans_type = '" .
                 $trans_type . "'";
            $return_commission_trace_list_new = array();
            $return_commission_trace_list_new['trace_id'] = $return_commission_trace['id'];
            $return_commission_trace_list_new['return_commission_ext_id'] = $return_commission_ext_info['id'];
            $rs = M()->table('treturn_commission_trace_list')
                ->order("id")
                ->limit($return_commission_ext_info['return_condition_num'])
                ->where($where)
                ->save($return_commission_trace_list_new);
            $this->_log(M()->_sql());
            if (! $rs) {
                M()->rollback();
                return array(
                    'code' => '1006', 
                    'desc' => '更新treturn_commission_trace_list trace_id 失败');
            }
        }
        
        // 统计
        $stat_info['node_id'] = $return_commission_trace['node_id'];
        $stat_info['return_commission_id'] = $return_commission_trace['return_commission_id'];
        $stat_info['return_commission_ext_id'] = $return_commission_ext_info['id'];
        $stat_info['marketing_info_id'] = $return_commission_info['marketing_info_id'];
        $stat_info['return_channel'] = $return_commission_trace['return_channel'];
        $stat_info['transmit_count'] = 0;
        $stat_info['flow_count'] = 0;
        $stat_info['stat_date'] = date("Ymd");
        $stat_info['commission_type'] = $return_commission_ext_info['commission_type'];
        $stat_info['goods_id'] = $return_commission_ext_info['goods_id'];
        $stat_info['phone_no'] = $phone_no;
        $stat_info['trans_count'] = 0;
        $stat_info['trans_money_count'] = 0;
        /*
         * if ($trans_type == 1) {//交易类 $stat_info['trans_count'] = 1;
         * $stat_info['trans_money_count'] = $trans_money; } else {
         * $stat_info['trans_count'] = 0; $stat_info['trans_money_count'] = 0; }
         */
        $stat_info['return_times'] = 1;
        $stat_info['return_amount'] = $return_num;
        if (! $this->update_return_commission_stat($stat_info)) {
            M()->rollback();
            return array(
                'code' => '1006', 
                'desc' => '记录treturn_commission_stat 失败2');
        }
        M()->commit(); // 提交事务
        /*
         * //如返佣为卡券，直接发送接口 if ($return_commission_trace['commission_type'] ==
         * '0'){ $ret = $this->send_code($return_commission_trace); if ($ret ===
         * true){ return true; }else{ return $ret; } }
         */
        return true;
    }

    /*
     * 返佣 通宝斋非标 $marketing_info_id 营销活动ID $phone_no 被返佣的手机号码 $from_phone_no
     * 导致返佣产生的用户手机号码 $return_channel 返佣产生渠道来源 0-新浪微博 1-QQ空间 2-腾讯微博 3-人人网 4-微信朋友圈
     * 9-其他 $relation_id 返佣关联流水号 如订单等 用于关联查询返佣记录 $trans_type 0 - 抽奖类交易 1-购买类交易
     * 2-转发分享成功 $trans_money 购买类交易的订单金额
     */
    public function return_commission_tbz($marketing_info_id, $phone_no, 
        $return_channel, $from_phone_no, $relation_id, $trans_type, $trans_money) {
        // 检查Tmarketing_info 状态
        $this->_log(
            "return_commission inparm marketing_info_id:" . $marketing_info_id .
                 " phone_no:" . $phone_no . " return_channel:" . $return_channel .
                 " from_phone_no:" . $from_phone_no . " relation_id:" .
                 $relation_id . " trans_type:" . $trans_type . " trans_money:" .
                 $trans_money);
        $where = "id ='" . $marketing_info_id . "' and status = '1'";
        $marketing_info = M()->table('tmarketing_info i')
            ->where($where)
            ->find();
        
        if (! $marketing_info) {
            return array(
                'code' => '1001', 
                'desc' => '未找到状态正常的营销活动');
        } else if ($marketing_info['return_commission_flag'] != '1') {
            return array(
                'code' => '1002', 
                'desc' => '该营销活动未设置返佣信息');
        }
        $where = "marketing_info_id =" . $marketing_info_id;
        $return_commission_info = M()->table('treturn_commission_info i')
            ->where($where)
            ->find();
        if (! $return_commission_info) {
            return array(
                'code' => '1003', 
                'desc' => '该营销活动未设置返佣信息');
        }
        
        $map = array(
            'return_condition' => '1', 
            'return_commission_id' => $return_commission_info['id']);
        $return_commission_info_ext = M('treturn_commission_info_ext')->where(
            $map)->find();
        if (! $return_commission_info_ext) {
            return array(
                'code' => '1003', 
                'desc' => '该营销活动未设置返佣信息');
        }
        // 记录操作流水
        $return_commission_trace_list = array();
        $return_commission_trace_list['node_id'] = $return_commission_info['node_id'];
        $return_commission_trace_list['return_commission_id'] = $return_commission_info['id'];
        $return_commission_trace_list['return_commission_ext_id'] = $return_commission_info_ext['id'];
        $return_commission_trace_list['marketing_info_id'] = $marketing_info_id;
        $return_commission_trace_list['add_time'] = date("YmdHis");
        $return_commission_trace_list['commission_type'] = $return_commission_info_ext['commission_type'];
        $return_commission_trace_list['phone_no'] = $phone_no;
        $return_commission_trace_list['relation_id'] = $relation_id;
        $return_commission_trace_list['return_channel'] = $return_channel;
        $return_commission_trace_list['from_phone_no'] = $from_phone_no;
        $return_commission_trace_list['trans_amount'] = $trans_money;
        
        $rs = M()->table('treturn_commission_trace_list ')->add(
            $return_commission_trace_list);
        if (! $rs) {
            return array(
                'code' => '1005', 
                'desc' => '记录return_commission_trace_list 失败');
        }
        // 获取主键ID
        $return_commission_trace_list['id'] = $rs;
        
        if ($trans_type == 2) { // 更新转发统计
            $this->effect_stat($marketing_info_id, $return_channel, 0, 
                $phone_no);
        } else { // 更新参与统计
            $this->effect_stat($marketing_info_id, $return_channel, 4, 
                $phone_no);
        }
        // 判断是否达成返佣条件
        if ($return_commission_info_ext['return_condition'] == 1) { // 购买默认达成
        } else {
            /*
             * if ($return_commission_info_ext['return_condition_num'] == 1)
             * {//达成 } else { $where = "phone_no ='" . $phone_no . "' and
             * trace_id is null and return_commission_id = " .
             * $return_commission_info['id']; $count =
             * M()->table('treturn_commission_trace_list
             * t')->where($where)->count(); if ($count <
             * $return_commission_info['return_condition_num']) { return
             * array('code' => '1008', 'desc' => '未达成参与条件'); } }
             */
        }
        
        // 判断是否有参与次数限制
        if ($return_commission_info['phone_limit_flag'] == 1) {
            $where = "phone_no ='" . $phone_no . "' and return_commission_id = " .
                 $return_commission_info['id'];
            $count = M()->table('treturn_commission_trace t')
                ->where($where)
                ->count();
            if ($count >= $return_commission_info['phone_limit_num']) {
                return array(
                    'code' => '1008', 
                    'desc' => '该手机号码已经超过活动参与次数');
            }
        }
        if ($trans_type != 2) { // 分享转发不需要做以下判断
                                // 判断是否自己分享自己用
            if ($phone_no == $from_phone_no) {
                return array(
                    'code' => '1009', 
                    'desc' => '不允许自己分享自己用');
            }
            // 判断是否分享返佣关系是否已存在 不允许多次返佣 2 3 10 20 抽奖、市场调研、有奖答题、投票
            /*
             * if ($marketing_info['batch_type'] == '2' ||
             * $marketing_info['batch_type'] == '3' ||
             * $marketing_info['batch_type'] == '10' ||
             * $marketing_info['batch_type'] == '20') { $where = "phone_no ='" .
             * $phone_no . "' and from_phone_no = '" . $from_phone_no . "' and
             * return_commission_id = " . $return_commission_info['id']; $count
             * = M()->table('treturn_commission_trace
             * t')->where($where)->count(); if ($count >= 1) { return
             * array('code' => '1010', 'desc' => '返佣关系已存在'); } }
             */
        }
        
        // 开启事务
        M()->startTrans();
        // 保存返佣流水
        $return_commission_trace = array();
        $return_commission_trace['node_id'] = $return_commission_info['node_id'];
        $return_commission_trace['phone_no'] = $phone_no;
        $return_commission_trace['from_phone_no'] = $from_phone_no;
        $return_commission_trace['return_commission_id'] = $return_commission_info['id'];
        $return_commission_trace['return_commission_ext_id'] = $return_commission_info_ext['id'];
        $return_commission_trace['marketing_info_id'] = $return_commission_info['marketing_info_id'];
        $return_commission_trace['return_add_time'] = date("YmdHis");
        $return_commission_trace['commission_type'] = $return_commission_info_ext['commission_type'];
        switch ($return_commission_trace['commission_type']) {
            /*
             * case 0: //卡券 $return_num =
             * $return_commission_trace['return_goods_num'];
             * $return_commission_trace['send_status'] = '0'; $return_content =
             * ""; $return_commission_trace['send_request_id'] = date("YmdHis")
             * . rand(100000, 999999); //凭证发送单号
             * $return_commission_trace['send_deal_time'] = date("YmdHis");
             * //卡券发送最后处理时间 $return_commission_trace['return_acount'] =
             * $phone_no; //查找goods_info表中对应的金额计入统计 $where = "batch_no ='" .
             * $return_commission_info['batch_no'] . "'"; $goods_info =
             * M()->table('tgoods_info i')->where($where)->find(); if
             * (!$goods_info) { return array('code' => '1007', 'desc' =>
             * '该营销活动卡券配置有误'); } //计数为1 //$return_num =
             * $goods_info['goods_amt']; $return_num = 1; $return_content =
             * $goods_info['goods_name']; break; case 1: //Q币 $return_num =
             * $return_commission_info['return_qcoint_num']; $return_content =
             * $return_num . "Q币"; break; case 2: //话费 $return_num =
             * $return_commission_info['return_phone_charge']; $return_content =
             * $return_num . "元话费"; break;
             */
            case 3: // 现金
                if ($return_commission_info_ext['return_money_type'] == 0) { // 指定金额
                    $return_num = $return_commission_info_ext['return_money'];
                } else {
                    $return_num = $trans_money *
                         $return_commission_info_ext['return_money'] / 100;
                }
                // 获取通宝斋返佣配置扩展数据
                $where = "marketing_info_id =" . $marketing_info_id;
                $fb_tbz_commission_info_list = M()->table(
                    'tfb_tbz_commission_info')
                    ->where($where)
                    ->select();
                $this->_log(print_r($fb_tbz_commission_info_list, true));
                $total_return_num = $return_num;
                $this->_log($total_return_num);
                if ($fb_tbz_commission_info_list) {
                    // 计算总金额
                    foreach ($fb_tbz_commission_info_list as $fb_tbz_commission_info) {
                        if ($return_commission_info_ext['return_money_type'] == 0) { // 指定金额
                            $total_return_num += $fb_tbz_commission_info['return_money'];
                        } else {
                            $total_return_num += $trans_money *
                                 $fb_tbz_commission_info['return_money'] / 100;
                        }
                        $this->_log($total_return_num);
                    }
                }
                
                $ext_where = array(
                    'return_commission_id' => $return_commission_info['id']);
                $info_where = array(
                    'id' => $return_commission_info['id']);
                // 判断返佣金额是否超过总金额 如超过需要停止
                if ($return_commission_info_ext['return_money_limit_flag'] == '1') {
                    $return_commission_info_tmp = M()->table(
                        'treturn_commission_info_ext i')
                        ->lock(true)
                        ->where($ext_where)
                        ->find();
                    if (! $return_commission_info_tmp) {
                        M()->rollback();
                        return array(
                            'code' => '1003', 
                            'desc' => '该营销活动未设置返佣信息');
                    }
                    // 比较金额
                    $now_stat_money = $total_return_num +
                         $return_commission_info_tmp['return_money_stat'];
                    if ($return_commission_info_tmp['return_money_limit'] <=
                         $now_stat_money) {
                        M()->rollback();
                        // 停止该返佣活动
                        $update = array();
                        $update['status'] = '2';
                        $rs = M()->table('treturn_commission_info')
                            ->where($info_where)
                            ->save($update);
                        if (! $rs) {
                            return array(
                                'code' => '1009', 
                                'desc' => '停止该返佣活动 treturn_commission_info status 失败');
                        }
                        return array(
                            'code' => '1008', 
                            'desc' => '该营销活动返佣金额已超过总金额');
                    }
                    // 更新数据
                    $return_commission_info_update = array();
                    $return_commission_info_update['return_money_stat'] = $now_stat_money;
                    $rs = M()->table('treturn_commission_info_ext')
                        ->where($ext_where)
                        ->save($return_commission_info_update);
                    if (! $rs) {
                        M()->rollback();
                        return array(
                            'code' => '1009', 
                            'desc' => '更新treturn_commission_info return_money_stat 失败');
                    }
                }
                // 循环处理多级返佣
                if ($fb_tbz_commission_info_list) {
                    // 计算总金额
                    foreach ($fb_tbz_commission_info_list as $fb_tbz_commission_info) {
                        if ($return_commission_info_ext['return_money_type'] == 0) { // 指定金额
                            $return_num_tmp = $fb_tbz_commission_info['return_money'];
                        } else {
                            $return_num_tmp = $trans_money *
                                 $fb_tbz_commission_info['return_money'] / 100;
                        }
                        $return_content = $return_num_tmp . "元";
                        $return_commission_trace['phone_no'] = $fb_tbz_commission_info['phone_no'];
                        $return_commission_trace['yima_withhold_stauts'] = '0';
                        $return_commission_trace['yima_withhold_time'] = date(
                            "YmdHis"); // 翼码代扣佣金最后处理时间
                        $return_commission_trace['yima_withhold_requst_id'] = date(
                            "YmdHis") . rand(100000, 999999); // 翼码代扣佣金流水号
                        $return_commission_trace['yima_withhold_commission'] = 0;
                        $return_commission_trace['return_num'] = $return_num_tmp;
                        $return_commission_trace['return_content'] = $return_content;
                        $return_commission_trace['return_status'] = '0';
                        $return_commission_trace['return_channel'] = $return_channel;
                        $return_commission_trace['relation_id'] = $relation_id;
                        $return_commission_trace['batch_no'] = $return_commission_info_ext['batch_no'];
                        $return_commission_trace['b_id'] = $return_commission_info_ext['b_id'];
                        $return_commission_trace['data_from'] = 'R';
                        
                        $rs = M()->table('treturn_commission_trace ')->add(
                            $return_commission_trace);
                        if (! $rs) {
                            M()->rollback();
                            return array(
                                'code' => '1005', 
                                'desc' => '记录treturn_commission_trace 失败');
                        }
                    }
                }
                $return_content = $return_num . "元";
                $return_commission_trace['yima_withhold_stauts'] = '0';
                $return_commission_trace['yima_withhold_time'] = date("YmdHis"); // 翼码代扣佣金最后处理时间
                $return_commission_trace['yima_withhold_requst_id'] = date(
                    "YmdHis") . rand(100000, 999999); // 翼码代扣佣金流水号
                $return_commission_trace['yima_withhold_commission'] = 0;
                break;
            default:
                return array(
                    'code' => '1004', 
                    'desc' => '该营销活动的返佣类型有误');
        }
        ;
        $return_commission_trace['phone_no'] = $phone_no;
        $return_commission_trace['return_num'] = $return_num;
        $return_commission_trace['return_content'] = $return_content;
        $return_commission_trace['return_status'] = '0';
        $return_commission_trace['return_channel'] = $return_channel;
        $return_commission_trace['relation_id'] = $relation_id;
        $return_commission_trace['batch_no'] = $return_commission_info_ext['batch_no'];
        $return_commission_trace['b_id'] = $return_commission_info_ext['b_id'];
        $return_commission_trace['data_from'] = 'R';
        
        $rs = M()->table('treturn_commission_trace ')->add(
            $return_commission_trace);
        if (! $rs) {
            M()->rollback();
            return array(
                'code' => '1005', 
                'desc' => '记录treturn_commission_trace 失败');
        }
        // 获取主键ID
        $return_commission_trace['id'] = $rs;
        
        // 更新操作流水
        // if (($return_commission_info_ext['return_condition'] == 1) ||
        // ($return_commission_info_ext['return_condition_num'] == 1)) {//购买 或者
        // 参与条件=1
        $where = " id = " . $return_commission_trace_list['id'];
        $return_commission_trace_list_new = array();
        $return_commission_trace_list_new['trace_id'] = $return_commission_trace['id'];
        $rs = M()->table('treturn_commission_trace_list')
            ->where($where)
            ->save($return_commission_trace_list_new);
        if (! $rs) {
            M()->rollback();
            return array(
                'code' => '1006', 
                'desc' => '更新treturn_commission_trace_list trace_id 失败');
        }
        /*
         * } else { $where = "phone_no ='" . $phone_no . "' and trace_id is null
         * and return_commission_id = " . $return_commission_info['id'];
         * $return_commission_trace_list_new = array();
         * $return_commission_trace_list_new['trace_id'] =
         * $return_commission_trace['id']; $rs =
         * M()->table('treturn_commission_trace_list')->limit($return_commission_info['return_condition_num'])->where($where)->save($return_commission_trace_list_new);
         * if (!$rs) { M()->rollback(); return array('code' => '1006', 'desc' =>
         * '更新treturn_commission_trace_list trace_id 失败'); } }
         */
        
        // 统计
        $stat_info['node_id'] = $return_commission_trace['node_id'];
        $stat_info['return_commission_id'] = $return_commission_trace['return_commission_id'];
        $stat_info['return_commission_ext_id'] = $return_commission_info_ext['id'];
        $stat_info['marketing_info_id'] = $return_commission_info['marketing_info_id'];
        $stat_info['return_channel'] = $return_commission_trace['return_channel'];
        $stat_info['transmit_count'] = 0;
        $stat_info['flow_count'] = 0;
        $stat_info['stat_date'] = date("Ymd");
        $stat_info['commission_type'] = $return_commission_info_ext['commission_type'];
        $stat_info['goods_id'] = $return_commission_info_ext['goods_id'];
        $stat_info['phone_no'] = $phone_no;
        $stat_info['trans_count'] = 0;
        $stat_info['trans_money_count'] = 0;
        $stat_info['return_times'] = 1;
        $stat_info['return_amount'] = $total_return_num;
        if (! $this->update_return_commission_stat($stat_info)) {
            M()->rollback();
            return array(
                'code' => '1006', 
                'desc' => '记录treturn_commission_stat 失败3');
        }
        M()->commit(); // 提交事务
        return true;
    }
    
    // 活动效果统计 转发/点击流量 $type 0-转发 1-点击流量 2-返佣页面流量 3-参与成功统计 4-交易统计
    public function effect_stat($marketing_info_id, $return_channel, $type, 
        $phone_no = '13900000000', $other = array()) {
        $this->_log(
            "return_commission_effect_stat_log marketing_info_id:" .
                 $marketing_info_id . " return_channel:" . $return_channel .
                 " type:" . $type);
        
        $where = "marketing_info_id =" . $marketing_info_id;
        
        $batchInfo = M('tmarketing_info')->find($marketing_info_id);
        if ($batchInfo['batch_type'] == '29') {
            $now = date('YmdHis');
            $where .= " and return_commission_start_time <= '{$now}'";
            $where .= " and return_commission_end_time >= '{$now}'";
        }
        
        $return_commission_info = M()->table('treturn_commission_info i')
            ->where($where)
            ->find();
        if (! $return_commission_info) {
            return array(
                'code' => '1003', 
                'desc' => '该营销活动未设置返佣信息');
        }
        // 开启事务
        M()->startTrans();
        if ($type == 0 && $batchInfo['batch_type'] != '29') { // 锁表更新
            $where = "id =" . $return_commission_info['m_id'];
            $marketing_info = M()->table('tmarketing_info i')
                ->lock(true)
                ->where($where)
                ->find();
            if (! $marketing_info) {
                return array(
                    'code' => '1003', 
                    'desc' => '该营销活动未设置返佣信息');
            }
            $marketing_info_new = array();
            $marketing_info_new['fb_dzn'] = $marketing_info['fb_dzn'] + 1;
            $rs = M()->table('tmarketing_info')
                ->where($where)
                ->save($marketing_info_new);
            if (! $rs) {
                M()->rollback();
                return array(
                    'code' => '1006', 
                    'desc' => '更新tmarketing_info fb_dzn 失败');
            }
        }
        
        // 统计
        $stat_info['node_id'] = $return_commission_info['node_id'];
        $stat_info['marketing_info_id'] = $return_commission_info['marketing_info_id'];
        $stat_info['return_commission_id'] = $return_commission_info['id'];
        $stat_info['return_channel'] = $return_channel;
        $stat_info['phone_no'] = $phone_no;
        
        $stat_info['trans_money_count'] = 0;
        $stat_info['return_times'] = 0;
        $stat_info['return_amount'] = 0;
        // 0-转发 1-点击流量 2-返佣页面流量 3-参与成功统计 4-购买成功交易
        
        $stat_info['transmit_count'] = 0;
        $stat_info['flow_count'] = 0;
        $stat_info['return_page_flow_count'] = 0;
        $stat_info['marketing_join_count'] = 0;
        $stat_info['trans_count'] = 0;
        if ($type == 0) {
            $stat_info['transmit_count'] = 1;
        } else if ($type == 1) {
            $stat_info['flow_count'] = 1;
        } else if ($type == 2) {
            $stat_info['return_page_flow_count'] = 1;
        } else if ($type == 3) {
            $stat_info['marketing_join_count'] = 1;
        } else if ($type == 4) {
            $stat_info['trans_count'] = 1;
            $stat_info['trans_money_count'] = isset($other['trans_money']) ? floatval(
                $other['trans_money']) : 0;
        }
        
        $stat_info['stat_date'] = date("Ymd");
        $stat_info['commission_type'] = $return_commission_info['commission_type'];
        if (! $this->update_return_commission_stat($stat_info)) {
            M()->rollback();
            return array(
                'code' => '1006', 
                'desc' => '记录treturn_commission_stat 失败' . M()->_sql());
        }
        M()->commit(); // 提交事务
        return ture;
    }
    
    // 更新返佣日统计
    private function update_return_commission_stat($stat_info) {
        if ($stat_info['return_commission_ext_id'] == null)
            $stat_info['return_commission_ext_id'] = 0;
        $where = "node_id = '{$stat_info['node_id']}' and return_commission_id = '" .
             $stat_info['return_commission_id'] .
             "' and return_commission_ext_id = " .
             $stat_info['return_commission_ext_id'] . " and stat_date = '" .
             date('Ymd') . "' and return_channel = '" .
             $stat_info['return_channel'] . "' and commission_type = '" .
             $stat_info['commission_type'] .
             "' and phone_no = '{$stat_info['phone_no']}'";
        
        $day_stat = M()->table('treturn_commission_daystat')
            ->lock(true)
            ->where($where)
            ->find();
        if (! $day_stat) {
            $rs = M()->table('treturn_commission_daystat')->add($stat_info);
            $this->_log(M()->_sql());
            if ($rs) {
                // 添加成功
                return true;
            }
            // 添加失败，有可能是因为并发，索引重复，进行更新
        }
        $day_stat_new = array();
        $day_stat_new['transmit_count'] = $day_stat['transmit_count'] +
             $stat_info['transmit_count'];
        $day_stat_new['flow_count'] = $day_stat['flow_count'] +
             $stat_info['flow_count'];
        $day_stat_new['trans_count'] = $day_stat['trans_count'] +
             $stat_info['trans_count'];
        $day_stat_new['trans_money_count'] = $day_stat['trans_money_count'] +
             $stat_info['trans_money_count'];
        $day_stat_new['return_times'] = $day_stat['return_times'] +
             $stat_info['return_times'];
        $day_stat_new['return_amount'] = $day_stat['return_amount'] +
             $stat_info['return_amount'];
        $day_stat_new['return_page_flow_count'] = $day_stat['return_page_flow_count'] +
             $stat_info['return_page_flow_count'];
        $day_stat_new['marketing_join_count'] = $day_stat['marketing_join_count'] +
             $stat_info['marketing_join_count'];
        $rs = M()->table('treturn_commission_daystat')
            ->where($where)
            ->save($day_stat_new);
        $this->_log(M()->_sql());
        if (! $rs) {
            $this->_log(M()->_sql());
            return false;
        }
        
        return true;
    }
    
    // 返佣调用发码
    private function send_code($return_commission_trace) {
        $RemoteRequest = D('RemoteRequest', 'Service');
        $TransactionID = date("YmdHis") . rand(100000, 999999); // 凭证发送单号
        
        $data_from = 'R';
        $req_data = "&node_id=" . $return_commission_trace['node_id'] .
             "&phone_no=" . $return_commission_trace['phone_no'] . "&batch_no=" .
             $return_commission_trace['batch_no'] . "&request_id=" .
             $TransactionID . "&data_from=" . $data_from . "&batch_info_id=" .
             $return_commission_trace['b_id'];
        $resp_array = $RemoteRequest->requestWcAppServ($req_data);
        
        $trace_update = array();
        $trace_update['request_id'] = $TransactionID;
        $trace_update['resp_id'] = $resp_array['resp_id'];
        $trace_update['resp_desc'] = $resp_array['resp_desc'];
        $trace_update['return_status'] = '1'; // 设返佣为已领取
        $where = "id = " . $return_commission_trace['id'];
        if (! $resp_array || ($resp_array['resp_id'] != '0000' &&
             $resp_array['resp_id'] != '0001')) {
            $rs = M()->table('treturn_commission_trace t')
                ->where($where)
                ->save($trace_update);
            if (! $rs) {
                return array(
                    'code' => '1007', 
                    'desc' => '旺财发码失败:' . $resp_array['resp_desc'], 
                    $resp_array['resp_id'] . "!更新treturn_commission_trace失败");
            }
            return array(
                'code' => '1008', 
                'desc' => '旺财发码失败:' . $resp_array['resp_desc'], 
                $resp_array['resp_id']);
        }
        $rs = M()->table('treturn_commission_trace t')
            ->where($where)
            ->save($trace_update);
        return true;
    }
    
    // 通宝斋非标 财务结算统计生成
    public function finance_stat($date) {
        $log = "\r\n开始处理通宝斋非标{$date}财务结算统计生成\r\n";
        // $yima_money_batch = 'TBZ' . $date;
        $yima_money_batch = $date;
        $tbz_node_id = "'" . implode("','", C('fb_tongbaozhai.node_id')) . "'";
        // 检测是否需要处理的数据
        $where = "yima_money_batch is null  and add_time <'" . $date . "000000'";
        $count = M()->table('treturn_get_trace t')
            ->where($where)
            ->count();
        
        if ($count == 0) {
            $log .= "没有要处理的数据 sql[" . M()->_sql() . "]";
            return $log;
        }
        // 开启事务
        M()->startTrans();
        // 批量更新返佣流水表
        $sql = "UPDATE treturn_commission_trace SET yima_money_batch = '" .
             $yima_money_batch .
             "' WHERE user_get_trace_id IN (SELECT id FROM treturn_get_trace WHERE yima_money_batch is null and add_time <'" .
             $date . "000000')";
        $rs = M()->execute($sql);
        $log .= "批量更新返佣流水表 sql[" . M()->_sql() . "]\r\n";
        if ($rs === false || $rs == 0) {
            $log .= "批量更新返佣流水表失败 sql[" . M()->_sql() . "]\r\n";
            M()->rollback();
            return $log;
        }
        // 更新提领流水表
        $where = "yima_money_batch is null  and add_time <='" . $date . "000000'";
        $trace_update = array();
        $trace_update['yima_money_batch'] = $yima_money_batch;
        $rs = M()->table('treturn_get_trace')
            ->where($where)
            ->save($trace_update);
        $log .= "更新tfb_tbz_get_trace表财务批次号 sql[" . M()->_sql() . "]";
        if (! $rs) {
            $log .= "更新tfb_tbz_get_trace表财务批次号失败 sql[" . M()->_sql() . "]";
            M()->rollback();
            return $log;
        }
        
        // 获取统计数
        $where = "yima_money_batch = '" . $yima_money_batch .
             "'  and add_time <'" . $date . "000000'";
        $stat_info = M()->table('treturn_get_trace')
            ->where($where)
            ->field(" count(*) as trans_count, sum(get_amount) as trans_amount")
            ->find();
        $log .= "统计treturn_commission_trace表笔数和金额 sql[" . M()->_sql() . "]";
        if (! $stat_info) {
            $log .= "统计treturn_commission_trace表笔数和金额失败 sql[" . M()->_sql() . "]";
            M()->rollback();
            return $log;
        }
        // 统计
        $new_stat_info = array();
        $new_stat_info['yima_money_batch'] = $yima_money_batch;
        $new_stat_info['end_time'] = $date . "000000";
        $new_stat_info['add_time'] = date('YmdHis');
        $new_stat_info['trans_count'] = $stat_info['trans_count'];
        $new_stat_info['trans_amount'] = $stat_info['trans_amount'];
        $new_stat_info['status'] = '0';
        $log .= "记录treturn_commission_batch sql[" . M()->_sql() . "]";
        $rs = M()->table('treturn_commission_batch')->add($new_stat_info);
        $log .= "记录treturn_commission_batch sql[" . M()->_sql() . "]";
        if (! $rs) {
            M()->rollback();
            $log .= "记录treturn_commission_batch失败 sql[" . M()->_sql() . "]";
            return $log;
        }
        M()->commit(); // 提交事务
        $content['petname'] = "zhoukai@imageco.com.cn";
        $content['test_title'] = "测试平台-返佣数据通知邮件";
        $content['text_content'] = "通宝斋 " . $date . "周结算日, 共有" .
             $stat_info['trans_count'] . "笔支付宝返佣记录， 需打款 " .
             $stat_info['trans_amount'] . "元";
        to_email($content);
        return $log . "交易处理成功\r\n";
    }
    
    // 日通知生成,次日发送短信通知
    public function notify_gen($date) {
        $log = $this->tbz_notify_gen($date);
        $log .= "开始处理日通知统计生成\r\n";
        $tbz_node_id = "'" . implode("','", C('fb_tongbaozhai.node_id')) . "'";
        $add_time = date("YmdHis");
        // 获取短信模板
        $where = "param_name = 'RETURN_COMMISSION_NOTIFY_NOTE'";
        $param_info = M()->table('tsystem_param t')
            ->where($where)
            ->find();
        if (! $param_info) {
            $log .= "取短信模板失败 sql[" . M()->_sql() . "]\r\n";
            return $log;
        }
        $content = $param_info['param_value'];
        // 您在转发“XXX活动/XX商品”后，成功获取了一笔返佣：5元现金。奖励将在20个工作日内发放到您的支付宝账户。访问http://eres查看您的全部返佣。
        // $content =
        // "您在转发“#MARKETING_INFO_NAME#”后，成功获取了#COMMISSION_COUNT#笔返佣：#COMMISSION_AMOUNT#元现金。奖励将在20个工作日内发放到您的支付宝账户。访问http://eres查看您的全部返佣。";
        // 插入通知数据
        $sql = "INSERT INTO treturn_commission_notify (commission_date, marketing_info_id, marketing_info_name, phone_no, commission_amount, commission_count, add_time, status, node_id) SELECT '" .
             $date .
             "', t.marketing_info_id, m.`name`, t.`phone_no` ,SUM(t.`return_num`), COUNT(*), '" .
             $add_time .
             "', '0' , t.node_id
			FROM treturn_commission_trace t LEFT JOIN tmarketing_info m ON t.`marketing_info_id` = m.id  LEFT JOIN treturn_commission_info r ON t.`return_commission_id` = r.id 
			WHERE t.`commission_type` =  '3' and r.send_notice_flag = '1' and t.`marketing_info_id` is not null and return_add_time like '" .
             $date . "%' and  t.node_id not in (" . $tbz_node_id .
             ")  GROUP BY t.marketing_info_id, t.`phone_no`";
        $rs = M()->execute($sql);
        if ($rs === false) {
            $log .= "插入treturn_commission_notify表失败 sql[" . M()->_sql() . "]\r\n";
            return $log;
        }
        $log .= "插入treturn_commission_notify表" . $rs . "条记录 sql[" . M()->_sql() .
             "]";
        // 检测是否有需要处理的数据
        $where = "status = '0' and commission_date='" . $date . "'";
        $list = M()->table('treturn_commission_notify t')
            ->where($where)
            ->select();
        if ($$list === false) {
            $log .= "查询treturn_commission_notify 失败 sql[" . M()->_sql() . "]";
            return $log;
        } else if ($list == null) {
            $log .= "没有要处理的数据 sql[" . M()->_sql() . "]";
            return $log;
        }
        foreach ($list as $value) {
            // 获取活动号
            $where = "status = '0' and batch_no is not null and node_id = '" .
                 $value['node_id'] . "'";
            $goods_info = M()->table('tgoods_info t')
                ->order("id")
                ->limit(1)
                ->where($where)
                ->find();
            if (! $goods_info) {
                $log .= "取活动号失败 sql[" . M()->_sql() . "]\r\n";
                continue;
            }
            // 处理文本
            $content = $param_info['param_value'];
            $content = str_replace("#MARKETING_INFO_NAME#", 
                $value['marketing_info_name'], $content);
            $content = str_replace("#COMMISSION_COUNT#", 
                $value['commission_count'], $content);
            $content = str_replace("#COMMISSION_AMOUNT#", 
                $value['commission_amount'], $content);
            // 发送支撑
            $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
            $ret = $this->send_notify($value['node_id'], 
                $goods_info['batch_no'], $value['phone_no'], $content, 
                $TransactionID);
            // 更新处理结果
            $trace_update = array();
            $trace_update['notes'] = $content;
            $trace_update['send_time'] = date("YmdHis");
            $trace_update['send_seq'] = $TransactionID;
            if ($ret)
                $trace_update['status'] = '1';
            else
                $trace_update['status'] = '2';
            $where = "id = " . $value['id'];
            $rs = M()->table('treturn_commission_notify')
                ->where($where)
                ->save($trace_update);
            if (! $rs) {
                $log .= "更新treturn_commission_notify失败 sql[" . M()->_sql() .
                     "]\r\n";
                return $log;
            }
        }
        return $log . "交易处理成功\r\n";
    }
    
    // 日通知生成
    public function tbz_notify_gen($date) {
        $log = "开始处理通宝斋日通知统计生成\r\n";
        $add_time = date("YmdHis");
        $tbz_node_id = "'" . implode("','", C('fb_tongbaozhai.node_id')) . "'";
        // 获取短信模板
        $where = "param_name = 'TBZ_RETURN_COMMISSION_NOTIFY_NOTE'";
        $param_info = M()->table('tsystem_param t')
            ->where($where)
            ->find();
        if (! $param_info) {
            $log .= "取短信模板失败 sql[" . M()->_sql() . "]\r\n";
            return $log;
        }
        $content = $param_info['param_value'];
        // 您在转发“XXX活动/XX商品”后，成功获取了一笔返佣：5元现金。奖励将在20个工作日内发放到您的支付宝账户。访问http://eres查看您的全部返佣。
        // $content =
        // "您在转发“#MARKETING_INFO_NAME#”后，成功获取了#COMMISSION_COUNT#笔返佣：#COMMISSION_AMOUNT#元现金。奖励将在20个工作日内发放到您的支付宝账户。访问http://eres查看您的全部返佣。";
        // 插入通知数据
        $sql = "INSERT INTO treturn_commission_notify (commission_date, marketing_info_id, marketing_info_name, phone_no, commission_amount, commission_count, add_time, status, node_id) SELECT '" .
             $date .
             "', t.marketing_info_id, m.`name`, t.`phone_no` ,SUM(t.`return_num`), COUNT(*), '" .
             $add_time .
             "', '0' , t.node_id
			FROM treturn_commission_trace t LEFT JOIN tmarketing_info m ON t.`marketing_info_id` = m.id  LEFT JOIN treturn_commission_info r ON t.`return_commission_id` = r.id 
			WHERE t.`commission_type` =  '3' and r.send_notice_flag = '1' and return_add_time like '" .
             $date . "%' and   t.node_id in (" . $tbz_node_id .
             ")   GROUP BY t.marketing_info_id, t.`phone_no`";
        $rs = M()->execute($sql);
        if ($rs === false) {
            $log .= "插入treturn_commission_notify表失败 sql[" . M()->_sql() . "]\r\n";
            return $log;
        }
        $log .= "插入treturn_commission_notify表" . $rs . "条记录 sql[" . M()->_sql() .
             "]";
        // 检测是否有需要处理的数据
        $where = "status = '0' and commission_date='" . $date . "'";
        $list = M()->table('treturn_commission_notify t')
            ->where($where)
            ->select();
        if ($$list === false) {
            $log .= "查询treturn_commission_notify 失败 sql[" . M()->_sql() . "]";
            return $log;
        } else if ($list == null) {
            $log .= "没有要处理的数据 sql[" . M()->_sql() . "]";
            return $log;
        }
        foreach ($list as $value) {
            // 获取活动号
            $where = "status = '0' and batch_no is not null and node_id = '" .
                 $value['node_id'] . "'";
            $goods_info = M()->table('tgoods_info t')
                ->order("id")
                ->limit(1)
                ->where($where)
                ->find();
            if (! $goods_info) {
                $log .= "取活动号失败 sql[" . M()->_sql() . "]\r\n";
                continue;
            }
            // 处理文本
            $content = $param_info['param_value'];
            $content = str_replace("#MARKETING_INFO_NAME#", 
                $value['marketing_info_name'], $content);
            $content = str_replace("#COMMISSION_COUNT#", 
                $value['commission_count'], $content);
            $content = str_replace("#COMMISSION_AMOUNT#", 
                $value['commission_amount'], $content);
            // 发送支撑
            $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
            $ret = $this->send_notify($value['node_id'], 
                $goods_info['batch_no'], $value['phone_no'], $content, 
                $TransactionID);
            // 更新处理结果
            $trace_update = array();
            $trace_update['notes'] = $content;
            $trace_update['send_time'] = date("YmdHis");
            $trace_update['send_seq'] = $TransactionID;
            if ($ret)
                $trace_update['status'] = '1';
            else
                $trace_update['status'] = '2';
            $where = "id = " . $value['id'];
            $rs = M()->table('treturn_commission_notify')
                ->where($where)
                ->save($trace_update);
            if (! $rs) {
                $log .= "更新treturn_commission_notify失败 sql[" . M()->_sql() .
                     "]\r\n";
                return $log;
            }
        }
        return $log . "交易处理成功\r\n";
    }

    private function send_notify($node_id, $batch_no, $phoneNo, $text, 
        $TransactionID) {
        // 通知支撑
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

    public function yima_withhold_reqid() {
        $data = M()->query(
            "SELECT _nextval('return_yima_withhold_requst_id') as reqid FROM DUAL");
        if (! $data) {
            $this->_log('yima_withhold_reqid fail!');
            $req = rand(1, 999999);
        } else {
            $seq = $data[0]['reqid'];
        }
        return str_pad($req, 6, '0', STR_PAD_LEFT);
    }

    public function _log($msg, $level = Log::INFO) {
        Log::write($msg, $level);
    }
}
