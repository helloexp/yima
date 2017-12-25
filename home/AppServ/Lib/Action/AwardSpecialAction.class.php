<?php

/**
 * 功能：特殊抽奖接口
 *
 * @author siht 时间：2013-07-31
 */
class AwardSpecialAction extends BaseAction {
    // 接口参数
    public $award_type;
    // 特殊抽奖类型
    public $session_id;
    // 世界杯session_id
    public $total_count;

    // 总中奖数
    public function _initialize() {
        parent::_initialize();
        C('AwardRequest', require(CONF_PATH . 'configAwardRequest.php')); // 统一借用抽奖接口的限制

        $this->award_type   = I('award_type'); // 抽奖类型
        $this->session_id   = I('session_id'); // 抽奖id
        $this->$total_count = 0;
    }

    public function run() {
        // 加载自设置
        $info = C('AwardRequest');

        if (!$info) {
            // 尚未配置接口，接口不对外
            $resp_desc = "非法访问！";
            $this->returnError($resp_desc, '9000');
            exit();
        }

        // 获取接入端IP
        $ip    = $_SERVER['REMOTE_ADDR'];
        $ipArr = explode(',', $info['IMPORT_IP']);
        if (!in_array($ip, $ipArr)) {
            // IP不允许接入
            $resp_desc = "IP:" . $ip . "不允许接入";
            $this->returnError($resp_desc, '9001');
        }

        $rs = $this->check();
        if ($rs !== true) {
            $this->returnError($rs['resp_desc'], '9002');
        }

        if ($this->award_type == '1') // 世界杯抽奖
        {
            $this->wordCupAward();
        } else {
            $this->returnError('未中奖', '0002');
            // $this->returnError('未中奖', '7004');
        }
    }

    // 奖品发送
    private function send_award($batch_info, $award_info) {
        $channelId = M('tbatch_channel')->where(['id' => $award_info['label_id']])->getField('channel_id');
        // 抽奖接口
        $send_arr = array(
            'node_id' => $batch_info['node_id'],
            'batch_id' => $batch_info['id'],  // 活动id
            'award_type' => '2',  // 单，多类型
            'award_times' => $award_info['phone_day_count'],  // 每日限制次数
            'award_count' => $award_info['phone_total_count'],  // 总总将次数
            'day_part' => $award_info['phone_day_part'],
            'total_part' => $award_info['phone_total_part'],
            'phone_no' => $award_info['phone_no'],
            'label_id' => $award_info['label_id'],
            'channel_id' => $channelId,
            'batch_type' => $batch_info['batch_type'],
            'total_rate' => $award_info['total_chance'],
            'ip' => get_client_ip(),
            'cj_rule_id' => $award_info['cj_rule_id'],
            'full_id' => '',
            'wx_open_id' => $award_info['wx_id']
        );
        $iresp = $this->remoteCj($send_arr);
        log_write('欧洲杯返回结果：' . var_export($iresp, true));
        if ($iresp['resp_id'] == '0000') {
            // 查询奖品名称
            $iresp['batch_name'] = M('tbatch_info')->where(
                "batch_no='" . $iresp['batch_no'] . "'")->getField(
                    'batch_short_name');
            $cj_trace_id = $iresp['cj_trace_id'];
            $request_id = $iresp['request_id'];
            // 修改数据库中的手机号字段，并且调用重发接口
            $result = M('tcj_trace')->where(['id' => $cj_trace_id])->save(['send_mobile' => $award_info['phone_no']]);
            log_write('欧洲杯修改tcj_trace：$cj_trace_id = ' 
                . $cj_trace_id . ',send_mobile=' . $award_info['phone_no'] 
                . ',$result = ' . var_export($result, true));
            if ($result) {
                log_write('进入result成功');
                $sendAwardTrace = M('tsend_award_trace')->where(array('request_id' => $request_id))->find();
                if (
                    (isset($iresp['batch_class']) && ($iresp['batch_class'] == '7' || $iresp['batch_class'] == '8')) //话费、q币
                    || isset($sendAwardTrace['trans_type']) && $sendAwardTrace['trans_type'] == '3' //流量包
                    ) {
                    log_write('进入1');
                    $sendAwardRe = M('tsend_award_trace')
                    ->where(['request_id' => $request_id])
                    ->save(['deal_flag' => 1, 'phone_no' => $award_info['phone_no']]);
                    log_write('afterCjTrace:type = hf&Qb&llb,result = ' . $sendAwardRe . ',request_id = ' . $request_id);
                } elseif ($iresp['prize_type'] == '4') {//积分
                    log_write('进入2');
                    $memberModel = D('MemberInstall', 'Model');
                    $jfRe = $memberModel->receiveIntegal(
                            $batch_info['node_id'],
                            $iresp['integral_get_id'], 
                            $award_info['phone_no']
                        ); // 返回true或者false
                    log_write('afterCjTrace:type = jf,result = ' . $jfRe . ',request_id = ' . $request_id);
                } else if ($iresp['prize_type'] == '6') {
                    
                } else {
                    log_write('进入3');
                    $recendRe = $this->cjResend(
                        $request_id, $batch_info['node_id']
                    );
                    if ($recendRe['resp_id'] == '0000') {
                        $barCodeRe = M('tbarcode_trace')
                        ->where(['request_id' => $request_id])
                        ->save(['phone_no' => $award_info['phone_no']]);
                        
                        $sendAwardRe = M('tsend_award_trace')->where(['request_id' => $request_id])
                        ->save(['deal_flag' => 1, 'phone_no' => $award_info['phone_no']]);
                    }
                    log_write('afterCjTrace:type = kq,result = ' . var_export($recendRe, true) . ',request_id = ' . $request_id);
                }
            }
        } else {
            if ($iresp['resp_id'] == '1005'
                || $iresp['resp_id'] == '1016'
                || $iresp['resp_id'] == '1001' //奖品已发完
                || $iresp['resp_id'] == '1003') {//当日上限
                    //strstr($haystack, $needle);
                    $start = strpos($iresp['resp_desc'], '[');
                    $end = strpos($iresp['resp_desc'], ']');
                    $length = $end - $start;
                    $str = substr($iresp['resp_desc'], ($start + 1), ($length - 1));
            }
        }
        if ($iresp['resp_id'] == '0000') {
            $iresp['result'] = '2';//对应tworld_cup_match_quiz,中奖
        } else {
            $iresp['result'] = '1';//未中奖
        }
        return $iresp;
    }
    
    private function remoteCj($array = array()) {
        $info = C('AwardRequest');
        $cj_url = $info['CJ_URL'];
        $transId = date('YmdHis') . sprintf('%04s', mt_rand(0, 1000));
        /*
         * $strurl =
         * "&node_id=".$node_id."&phone_no=".$mobile."&batch_no=".$batch_no."&request_id=".$transId;
         * $send_url = $wc_arr['url'].$strurl;
         */
        $str = '&';
        $str .= http_build_query($array);
        /*
         * foreach($array as $k=>$v){ $str .= $k.'='.$v.'&'; } $str
         * =substr($str, 0, -1);
         */
        $send_url = $cj_url . $str;
        log_write('cj_send:' . $send_url);
        $ch = curl_init();
        $timeout = 10; // set to zero for no timeout
        curl_setopt($ch, CURLOPT_URL, $send_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $resp_info = curl_exec($ch);
        Log_write("$resp_info:" . print_r($resp_info, true));
        $resp_info = urldecode($resp_info);
        $arr = json_decode($resp_info, true);
        if (! $arr) {
            $resp_arr['resp_id'] = - 1;
            $resp_arr['resp_desc'] = "解析失败";
            return $resp_arr;
        }
        Log_write("cj_send:" . print_r($resp_info, true));
        $resp_arr = array();
        if ($arr['resp_id'] == '0000') {
            $resp_arr['resp_id'] = $arr['resp_id'];
            $resp_arr['award_level'] = $arr['resp_data']['award_level'];
            $resp_arr['batch_no'] = $arr['resp_data']['batch_no'];
            $resp_arr['rule_id'] = $arr['resp_data']['rule_id'];
            $resp_arr['transaction_id'] = $arr['resp_data']['transaction_id'];
            $resp_arr['resp_desc'] = $arr['resp_desc'];
            $resp_arr['request_id'] = $arr['resp_data']['request_id'];
            $resp_arr['bonus_use_detail_id'] = $arr['resp_data']['bonus_use_detail_id'];
            $resp_arr['cj_trace_id'] = $arr['resp_data']['cj_trace_id'];
            $resp_arr['card_ext'] = $arr['resp_data']['card_ext'];
            $resp_arr['card_id'] = $arr['resp_data']['card_id'];
            if ($resp_arr['card_id']) {
                log_write("中到了卡券------" . print_r($resp_arr, true));
            }
            $resp_arr['prize_type'] = $arr['resp_data']['prize_type'];
            $resp_arr['member_phone'] = $arr['resp_data']['member_phone'];
            $resp_arr['integral_get_flag'] = $arr['resp_data']['integral_get_flag'];
            $resp_arr['integral_get_id'] = $arr['resp_data']['integral_get_id'];
            $resp_arr['batch_class'] = $arr['resp_data']['batch_class'];
        } else {
            $resp_arr['resp_id'] = $arr['resp_id'];
            $resp_arr['resp_desc'] = $arr['resp_desc'];
        }
        return $resp_arr;
    }
    
    private function cjResend($requestId, $nodeId) {
        $info = C('AwardRequest');
        $url = $info['CODE_RESEND_URL'];
        log_write('$url = '. $url . ',$requestId = ' . $requestId . ',$nodeId = ' . $nodeId);
        $error = '';
        $resp_info = httpPost(
            $url, 
            [
                'request_id' => $requestId, 
                'node_id' => $nodeId, 
                'user_id' => '00000000', 
            ], 
            $error,
            [
                'METHOD' => 'GET'
            ]
        );
        $resp_info = urldecode($resp_info);
        log_write('$resp_info = '. $resp_info);
        $arr = @json_decode($resp_info);
        $resp_arr['resp_id'] = $arr->resp_id;
        $resp_arr['resp_desc'] = $arr->resp_desc;
        return $resp_arr;
    }

    // 世界杯竞猜发送
    private function wordCupAwardSend($batch_type, $world_cup_events) {
        $where = "batch_type ='" . $batch_type . "' and status = '1'";
        if ($batch_type == '61') // 赛事竞猜
        {
            $where .= " and defined_one_name ='" . $this->session_id . "'";
            $quiz_type = '1';
        } else {
            $quiz_type = '0';
        }

        $batch_list = M('tmarketing_info')->where($where)->select();

        $team_id = $world_cup_events['team1_id'];
        if ($world_cup_events['result'] == '2') // 2队胜
        {
            $team_id = $world_cup_events['team2_id'];
        } else if ($world_cup_events['result'] == '3') // 平局
        {
            $team_id = 0;
        }
        if (!$batch_list) // 无竞猜活动
        {
            $this->returnError('尚无渠道活动', '0003');
        } else {

            /*
             * foreach($batch_list as $batch_info) { $where = "batch_id =
             * '".$batch_info['id']."'"; $cj_rule =
             * M('tcj_rule')->where($where)->find(); $where = "cj_rule_id =
             * '".$cj_rule['id']."'"; $award_list =
             * M('tcj_batch')->where($where)->select();
             * foreach($award_list as $award_info) { $where = "batch_id =
             * '".$batch_info['id']."' and quiz_type = '".$quiz_type."' and
             * result = 0 and team_id = '".$team_id."'"; $quiz_list =
             * M('tworld_cup_match_quiz')->WHERE($where)->ORDER("RAND()")->LIMIT(0,$award_info['total_count'])->SELECT();//随机取中奖名单。
             * if(!$quiz_list) { //$this->returnError('0003','无人竞猜成功'); } else {
             * foreach($quiz_list as $quiz_info) { $award_info['phone_no']=
             * $quiz_info['phone_no']; //if($award_info['send_type'] == '0')//为0
             * 才下发 if($award_info['node_id'] !='00023287')//浦发银行不发码 $resp_array
             * = $this->send_award($batch_info,$award_info);//发奖品
             * $where = "batch_id = '".$batch_info['id']."' and phone_no = '".
             * $quiz_info['phone_no']."'"; $quiz_new = array();
             * $quiz_new['result'] = $resp_array['result'];
             * $quiz_new['resp_info'] =
             * $resp_array['resp_id'].$resp_array['resp_desc'];
             * $quiz_new['activity_no'] = $award_info['activity_no'];
             * M('tworld_cup_match_quiz')->where($where)->save($quiz_new);
             * if($resp_array['result'] == '2')//发送成功 更新统计
             * $this->updateStat($quiz_info['label_id']); } } } $where =
             * "batch_id = '".$batch_info['id']."' and quiz_type = 1 and result
             * = 0";//发放完毕将剩余竞猜人员改成未中奖 $quiz_new = array(); $quiz_new['result']
             * = '1';
             * M('tworld_cup_match_quiz')->where($where)->save($quiz_new); }
             */
            foreach ($batch_list as $batch_info) {
                $where      = "batch_id = '" . $batch_info['id'] . "'";
                $cj_rule    = M('tcj_rule')->where($where)->find();
                $where      = "cj_rule_id = '" . $cj_rule['id'] . "'";
                $award_list = M('tcj_batch')
                ->alias('cjb')
                ->field('cjb.*,b.batch_type as source,b.batch_class,b.batch_amt,
                    cr.phone_day_count,cr.phone_total_count,cr.phone_day_part,cr.phone_total_part,cr.total_chance')
                ->join('tbatch_info b on b.id = cjb.b_id')
                ->join('tcj_rule cr on cr.id = cjb.cj_rule_id')
                ->where($where)
                ->select();
                $icount      = 0;
                $award_array = array();
                $max_random  = 0;
                $low_num     = 0;
                foreach ($award_list as $award_info) // 组织奖品信息
                {
                    $max_random = $low_num + $award_info['total_count'];

                    $award_range               = array();
                    $award_range['award_info'] = $award_info;
                    $award_range['low_num']    = $low_num;
                    $award_range['max_num']    = $max_random;

                    $award_array[$icount] = $award_range;

                    $low_num = $max_random;
                    $icount++;
                }

                /*
                 * $this->log('===============begin==================');
                 * $this->log(print_r($award_array,true));
                 * $this->log('===============end==================');
                 */
                if ($batch_info['defined_three_name']) {//0为猜胜负 1为猜比分
                    $score = $world_cup_events['team1_goal'] . ':' . $world_cup_events['team2_goal'];
                    $quiz_list = M('tworld_cup_match_quiz')->where(
                            [
                                'batch_id' => $batch_info['id'], 
                                'quiz_type' => $quiz_type, 
                                'result' => 0, 
                                'score' => $score
                            ]
                        )->order("RAND()")
                        ->limit(0, $max_random)
                        ->select();
                } else {
                    // 随机中奖人员
                    $where     = "batch_id = '" . $batch_info['id'] . "' and quiz_type = '" . $quiz_type . "' and result = 0 and team_id = '" . $team_id . "'";
                    $quiz_list = M('tworld_cup_match_quiz')->WHERE($where)->ORDER("RAND()")->LIMIT(0, $max_random)->SELECT();
                }
                log_write('欧洲杯随机名单：' . var_export($quiz_list, true));
                $rand_range = range(1, $max_random);
                foreach ($quiz_list as $quiz_info) {
                    $n        = mt_rand(0, count($rand_range) - 1);
                    $rand_num = $rand_range[$n];
                    unset($rand_range[$n]);
                    for ($inum = 0; $inum < $icount; $inum++) {
                        $award_detail = $award_array[$inum];
                        $rand_range   = array_values($rand_range);
                        if ($rand_num > $award_detail['low_num'] && $rand_num <= $award_detail['max_num']) {
                            $this->log('cj_num[' . $inum . ']=>' . $rand_num);
                            $award_xinxi             = $award_detail['award_info'];
                            $award_xinxi['phone_no'] = $quiz_info['phone_no'];
                            $award_xinxi['wx_id'] = $quiz_info['wx_id'];
                            $award_xinxi['label_id'] = $quiz_info['label_id'];
                            // 发奖品
                            log_write('发奖品数据batch_info：' . var_export($batch_info, true));
                            log_write('发奖品数据award_xinxi：' . var_export($award_xinxi, true));
                            $resp_array = $this->send_award($batch_info, $award_xinxi);
                            // 发奖品
                            $where                   = "batch_id = '" . $batch_info['id'] . "' and wx_id = '" . $quiz_info['wx_id'] . "'";
                            $quiz_new                = array();
                            $quiz_new['result']      = $resp_array['result'];
                            $quiz_new['resp_info']   = $resp_array['resp_id'] . $resp_array['resp_desc'];
                            $quiz_new['activity_no'] = $award_xinxi['activity_no'];
                            M('tworld_cup_match_quiz')->where($where)->save($quiz_new);
                            if ($resp_array['result'] == '2') // 发送成功 更新统计
                            {
                                $this->updateStat($quiz_info['label_id']);
                            }
                        }
                    }
                }
                $where              = "batch_id = '" . $batch_info['id'] . "' and quiz_type = 1 and result = 0"; // 发放完毕将剩余竞猜人员改成未中奖
                $quiz_new           = array();
                $quiz_new['result'] = '1';
                $tworldCupMatchQuizModel = M('tworld_cup_match_quiz');
                $tworldCupMatchQuizModel->startTrans();
                $updateLeftData = $tworldCupMatchQuizModel->where($where)->save($quiz_new);
                if ($updateLeftData !== false) {
                    $tworldCupMatchQuizModel->commit();
                } else {
                    log_write('更新剩余参与人员记录失败：活动id=' . $batch_info['id'] . ',$updateLeftData = ' . var_export($updateLeftData, true) . ',sql:' . M()->_sql());
                }
                log_write('更新剩余参与人员记录：活动id=' . $batch_info['id'] . ',$updateLeftData = ' . var_export($updateLeftData, true) . ',sql:' . M()->_sql());
            }
        }
    }

    // 世界杯抽奖 ：award_type =1
    private function wordCupAward() {
        $where            = "session_id ='" . $this->session_id . "'";
        $world_cup_events = M('tworld_cup_events')->where($where)->find();
        if (!$world_cup_events) {
            $this->returnError('场次id[' . $this->session_id . ']不存在', '7001');
        }

//         if ($this->session_id == '64') // 先处理冠军竞猜
//         {
//             $this->wordCupAwardSend('25', $world_cup_events);
//         }
        // else
        // {
        $this->wordCupAwardSend('61', $world_cup_events); // 赛事竞猜
        // }
        $this->returnSuccess("竞猜结束", array(
                        "session_id" => $this->session_id,
                ));
    }

    private function check() {
        if ($this->award_type == '') {
            return array(
                    'resp_desc' => '抽奖类型不能为空！',
            );

            return false;
        }
        if ($this->award_type == '1') {
            if ($this->session_id == '') {
                return array(
                        'resp_desc' => '比赛场次不能为空！',
                );

                return false;
            }
        }

        return true;
    }

    // 更新统计信息
    private function updateStat($label_id) {
        M()->startTrans(); // 起事务
        // 更新标签数据
        $where         = "id ='" . $label_id . "'";
        $batch_channel = M('tbatch_channel')->where($where)->find();
        if (!$batch_channel) {
            M()->rollback();
            $this->log("获取标签信息[tbatch_channel]-[" . $label_id . "]失败");

            return false;
        }
        $rs = M('tbatch_channel')->where($where)->save(array(
                'send_count' => $batch_channel['send_count'] + 1,
        ));

        // 更新活动信息
        $where          = "id = '" . $batch_channel['batch_id'] . "'";
        $marketing_info = M('tmarketing_info')->where($where)->find();
        if (!$marketing_info) {
            M()->rollback();
            $this->log("获取活动信息[tmarketing_info]-[" . $batch_channel['batch_id'] . "]失败");

            return false;
        }
        $rs = M('tmarketing_info')->where($where)->save(array(
                'send_count' => $marketing_info['send_count'] + 1,
        ));

        // 更新日统计数据
        $where    = "batch_id = '" . $batch_channel['batch_id'] . "' and channel_id = '" . $batch_channel['channel_id'] . "' and day = '" . date('Ymd') . "'";
        $day_stat = M('tdaystat')->where($where)->find();
        if (!$day_stat) // 日统计,没找到则新增
        {
            $daystat               = array();
            $daystat['batch_id']   = $batch_channel['batch_id'];
            $daystat['batch_type'] = $batch_channel['batch_type'];
            $daystat['channel_id'] = $batch_channel['channel_id'];
            $daystat['node_id']    = $batch_channel['node_id'];
            $daystat['send_count'] = 1;
            M('tdaystat')->add($daystat);
            M()->commit(); // 提交事务
            return true;
        }
        $rs = M('tdaystat')->where($where)->save(array(
                'send_count' => $marketing_info['send_count'] + 1,
        ));

        M()->commit(); // 提交事务
        return true;
    }
}

?>