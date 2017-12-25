<?php

/**
 * 功能：特殊抽奖接口
 *
 * @author siht 时间：2013-07-31
 */
class AwardPreSpecialAction extends BaseAction {
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

        $this->award_type  = I('award_type'); // 抽奖类型
        $this->session_id  = I('session_id'); // 抽奖id
        $this->total_count = 0;
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
        $RemoteRequest = D('RemoteRequest', 'Service');
        $TransactionID = date("YmdHis") . rand(100000, 999999); // 凭证发送单号
        $data_from     = $batch_info['batch_type'] + 1;
        $req_data      = "&node_id=" . $batch_info['node_id'] . "&phone_no=" . $award_info['phone_no'] . "&batch_no=" . $award_info['activity_no'] . "&request_id=" . $TransactionID . "&data_from=" . $data_from;
        $resp_array    = $RemoteRequest->requestWcAppServ($req_data);
        if (!$resp_array || ($resp_array['resp_id'] != '0000' && $resp_array['resp_id'] != '0001')) {
            $resp_array['result'] = '3';

            // $this->returnError('旺财发码失败:'.$resp_array['resp_desc'],
            // $resp_array['resp_id']);
        } else {
            $resp_array['result'] = '2';
        }

        return $resp_array;
    }

    // 世界杯竞猜发送
    private function wordCupAwardSend($batch_type, $world_cup_events) {
        $where = "batch_type ='" . $batch_type . "' and status = '1'";
        if ($batch_type == '22') // 赛事竞猜
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
                $award_list = M('tcj_batch')->where($where)->select();

                $icount      = 0;
                $award_array = array();
                $max_random  = 0;
                $low_num     = 0;

                $quiz_new = array();
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

                    if ($award_info['award_level'] == '1') {
                        $quiz_new['award1_total'] = $award_info['total_count'];
                    } else if ($award_info['award_level'] == '2') {
                        $quiz_new['award2_total'] = $award_info['total_count'];
                    } else if ($award_info['award_level'] == '3') {
                        $quiz_new['award3_total'] = $award_info['total_count'];
                    }
                }

                $quiz_new['session_id'] = $this->session_id;
                $quiz_new['batch_id']   = $batch_info['id'];
                $quiz_new['node_id']    = $batch_info['node_id'];
                M('tworld_cup_match_quiz_pre')->add($quiz_new);

                /*
                 * $this->log('===============begin==================');
                 * $this->log(print_r($award_array,true));
                 * $this->log('===============end==================');
                 */

                // 随机中奖人员
                $where     = "batch_id = '" . $batch_info['id'] . "' and quiz_type = '" . $quiz_type . "' and result = 0 and team_id = '" . $team_id . "'";
                $quiz_list = M('tworld_cup_match_quiz')->WHERE($where)->ORDER("RAND()")->LIMIT(0,
                        $max_random)->SELECT();

                $rand_range = range(1, $max_random);

                foreach ($quiz_list as $quiz_info) {
                    $n        = mt_rand(0, count($rand_range) - 1);
                    $rand_num = $rand_range[$n];
                    unset($rand_range[$n]);
                    for ($inum = 0; $inum < $icount; $inum++) {
                        $award_detail = $award_array[$inum];
                        $rand_range   = array_values($rand_range);
                        if ($rand_num > $award_detail['low_num'] && $rand_num <= $award_detail['max_num']) {

                            $award_xinxi             = $award_detail['award_info'];
                            $award_xinxi['phone_no'] = $quiz_info['phone_no'];

                            $where = "node_id = '" . $batch_info['node_id'] . "' and session_id ='" . $this->session_id . "' and batch_id = '" . $batch_info['id'] . "'";

                            $q_r       = M('tworld_cup_match_quiz_pre')->where($where)->find();
                            $quiz_new1 = array();
                            if ($award_xinxi['award_level'] == '1') {
                                $quiz_new1['award1'] = $q_r['award1'] + 1;
                            } else if ($award_xinxi['award_level'] == '2') {
                                $quiz_new1['award2'] = $q_r['award2'] + 1;;
                            } else if ($award_xinxi['award_level'] == '3') {
                                $quiz_new1['award3'] = $q_r['award3'] + 1;
                            }
                            M('tworld_cup_match_quiz_pre')->where($where)->save($quiz_new1);
                        }
                    }
                }
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

        /*
         * if($this->session_id == '64') //先处理冠军竞猜 {
         * $this->wordCupAwardSend('25',$world_cup_events); } else {
         */
        $this->wordCupAwardSend('22', $world_cup_events); // 赛事竞猜
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