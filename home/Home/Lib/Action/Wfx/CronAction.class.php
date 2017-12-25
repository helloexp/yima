<?php

class CronAction extends Action {
    public $_authAccessMap = '*';
    public $meiHuiModel    = '';
    public function _initialize() {
        $this->meiHuiModel = D('FbMhWfx', 'Model');
    }

    public function index() {
        /*计算团队奖励*/
        $teams = $this->meiHuiModel->getAllTeam();
        echo '---------------------------------------------<br>';
        echo '总计团队:' . count($teams) . '个<br>';
        /*echo '<pre> ';
        var_dump($teams);
        echo '</pre>';*/
        $month = $this->meiHuiModel->GetMonth(0); //测试的时候开启
        //$month = $this->meiHuiModel->GetMonth(-1);//生产的时候开启
        /*先更新下个人订单统计表*/
        $this->meiHuiModel->updateSelfDaystat($month);
        /*清除数据*/
        $this->meiHuiModel->clearData($month);
        $has_prossed = array();
        foreach ($teams as $vo) {
            $teammembers = $this->meiHuiModel->getAllTeamMember($vo['id']);
            echo '---------------------------------------------<br>';
            echo $vo['id'] . '团队成员:' . count($teammembers) . '个,' . implode(',', $teammembers) . '<br>';
            /*echo '<pre> ';
            var_dump($teammembers);
            echo '</pre>';*/
            $TeamAllAmount = 0;
            $TeamAllAmount = $this->meiHuiModel->getTeamAllAmount($teammembers, $month);
            if ($TeamAllAmount == 0) {
                continue;
            }

            echo '---------------------------------------------<br>';
            echo $vo['id'] . '团队总销售额:<br>';
            echo '<pre> ';
            var_dump($TeamAllAmount);
            echo '</pre>';
            $TeamPrizePer    = 0;
            $TeamPrizePer    = $this->meiHuiModel->getTeamPrizePer($TeamAllAmount);
            $team_all_reward = 0;
            $team_all_reward = round($TeamAllAmount * $TeamPrizePer / 100, 2);
            foreach ($teammembers as $teammember) {
                if (in_array($teammember, $has_prossed)) {
                    continue;
                }
                M()->startTrans();
                $trace_config                         = array();
                $trace_config['TeamAllAmount']        = $TeamAllAmount;
                $trace_config['TeamPrizePer']         = $TeamPrizePer;
                $trace_config['team_all_reward']      = $team_all_reward;
                $my_amount                            = 0;
                $my_amount                            = $this->meiHuiModel->getMyAmount($teammember, $month);
                $trace_config['my_amount']            = $my_amount;
                $my_level                             = $this->meiHuiModel->getMyLevel($teammember);
                $trace_config['my_level']             = $my_level;
                $sale_percent                         = $this->meiHuiModel->getMySalePercent($my_level);
                $trace_config['sale_percent']         = $sale_percent;
                $manage_percent                       = $this->meiHuiModel->getMyManagePercent($my_level);
                $trace_config['manage_percent']       = $manage_percent;
                $my_contribution_rate                 = round($my_amount / $TeamAllAmount, 6);
                $trace_config['my_contribution_rate'] = $my_contribution_rate;
                echo '---------------------------------------------<br>';
                echo $teammember . '团队成员计算参数:<br>';
                echo '<pre> ';
                var_dump($trace_config);
                echo '</pre>';
                /*个人贡献率=个人实收金额/(总团队所有成员实收金额总和)
                团队贡献率=(所在团队所有成员实收金额总和)/ (总团队所有成员实收金额总和)
                 */
                $my_reward = 0;
                if ($my_level == 2) {
                    /*钻石会员奖励*/
                    /*钻石会员：(个人提成=64%；管理提成=20% 后台可设置)
                    团队总奖金 * 64% * 个人贡献率 + 团队总奖金 * 20% * 团队贡献率（100%）
                     */
                    $my_reward = ($team_all_reward * $sale_percent * $my_contribution_rate) / 100 + ($team_all_reward * $manage_percent) / 100;
                } elseif ($my_level == 3) {
                    /*金牌会员奖励*/
                    /*金牌会员：(个人提成=64%；管理提成=16% 后台可设置)
                    团队总奖金 * 64% * 个人贡献率 + 团队总奖金 * 16% * 团队贡献率
                     */
                    $my_teammembers            = $this->meiHuiModel->getAllTeamMember($teammember);
                    $MyTeamAllAmount           = $this->meiHuiModel->getTeamAllAmount($my_teammembers, $month);
                    $my_team_contribution_rate = round($MyTeamAllAmount / $TeamAllAmount, 4);
                    $my_reward                 = ($team_all_reward * $sale_percent * $my_contribution_rate) / 100 + ($team_all_reward * $manage_percent * $my_team_contribution_rate) / 100;

                    $trace_config['MyTeamAllAmount']           = $MyTeamAllAmount;
                    $trace_config['my_team_contribution_rate'] = $my_team_contribution_rate;
                } elseif ($my_level == 4) {
                    /*银牌会员奖励*/
                    /**银牌会员:   (个人提成=64% 后台可设置)
                    团队总奖金 * 64% * 个人贡献率*/
                    $my_reward = ($team_all_reward * $sale_percent * $my_contribution_rate) / 100;
                }
                if ($my_reward > 0) {
                    echo '---------------------------------------------<br>';
                    echo $teammember . '获得团队奖励:<br>';
                    echo '<pre> ';
                    var_dump($my_reward);
                    echo '</pre>';
                } else {
                    continue;
                }
                /*把计算出的结果存到数据库*/
                $data = array();
                $data = array(
                    'saler_id'     => $teammember,
                    'sales_amount' => $my_amount,
                    'amount'       => $my_reward,
                    'trace_month'  => $month,
                    'trace_time'   => date('YmdHis'),
                    'trace_config' => json_encode($trace_config),
                );
                echo '---------------------------------------------<br>';
                echo '团队计算结果:<br>';
                echo '<pre> ';
                var_dump($data);
                echo '</pre>';
                $has_prossed[] = $teammember;
                $this->meiHuiModel->addTeamTrace($data);
                M()->commit();
                $data = array();
                $data = array(
                    'trace_type'    => 'B', /*A 招募奖励B团队奖励C 排行奖励*/
                    'trace_subtype' => 'B1', /*A1 招募奖励B1 团队 C1 个人冠军C2 个人亚军C3 个人季军*/
                    'trace_month' => $month,
                    'saler_id'      => $teammember,
                    'trace_time'    => date('YmdHis'),
                    'user_get_flag' => 0,
                    'amount'        => $my_reward,
                );
                $this->meiHuiModel->addTrace($data);
                M()->commit();
            }
        }
        /*计算招募奖励*/
        $ids = $this->meiHuiModel->getGlodAndDiamondMember();
        echo '------------招募奖励计算------------------------<br>';
        echo 'GlodAndDiamondMember:<br>';
        echo '<pre> ';
        var_dump($ids);
        echo '</pre>';
        foreach ($ids as $id) {
            $saler_ids = $this->meiHuiModel->getMyRecruitMember($id);
            echo 'MyRecruitMember:<br>';
            echo '<pre>';
            var_dump($saler_ids);
            echo '</pre>';
            if (!empty($saler_ids)) {
                $RecruitEgt1000 = $this->meiHuiModel->getRecruitEgt1000Trace($saler_ids, $month);
                echo 'RecruitEgt1000:<br>';
                echo '<pre>';
                var_dump($RecruitEgt1000);
                echo '</pre>';
                $amount = 0;
                foreach ($RecruitEgt1000 as $vo) {
                    $data = array(
                        'saler_id'     => $vo['saler_id'],
                        'referee_id'   => $id,
                        'sales_amount' => $vo['total_amount'],
                        'amount'       => 20,
                        'trace_month'  => $month,
                        'trace_time'   => date('YmdHis'),
                    );
                    $this->meiHuiModel->addRecruitTrace($data);
                    $amount = $amount + 20;
                }
                $data = array(
                    'trace_type'    => 'A', /*A 招募奖励B团队奖励C 排行奖励*/
                    'trace_subtype' => 'A1', /*A1 招募奖励B1 团队 C1 个人冠军C2 个人亚军C3 个人季军*/
                    'trace_month' => $month,
                    'saler_id'      => $id,
                    'trace_time'    => date('YmdHis'),
                    'user_get_flag' => 0,
                    'amount'        => $amount,
                );
                $this->meiHuiModel->addTrace($data);
            }
        }
        /*计算排名奖励*/
        $limit_amount = $this->meiHuiModel->getJoinRankingLimit(date('m'));
        $top_three    = $this->meiHuiModel->getRankingTopThree($month, $limit_amount);
        //$last_month = $this->meiHuiModel->GetMonth(-1);
        $last_month   = $this->meiHuiModel->GetMonth(0);
        $total_reward = $this->meiHuiModel->getAllReward() + $this->meiHuiModel->getRemainReward($last_month);

        echo '------------排名奖励计算------------------------<br>';
        echo '前三名:<br>';
        echo '<pre> ';
        var_dump($top_three);
        echo '</pre>';
        $consume_reward = 0;
        foreach ($top_three as $key => $vo) {
            $RankingAmount = $this->meiHuiModel->getRankingAmount($key);
            $consume_reward += $RankingAmount;
            $data = array(
                'saler_id'        => $vo['saler_id'],
                'ranking'         => $key + 1,
                'sales_amount'    => $vo['total_amount'],
                'amount'          => $RankingAmount,
                'trace_month'     => $month,
                'trace_time'      => date('YmdHis'),
                'last_order_time' => $vo['last_order_time'],
            );
            $this->meiHuiModel->addRankingTrace($data);
            $data = array(
                'trace_type'    => 'C', /*A 招募奖励B团队奖励C 排行奖励*/
                'trace_subtype' => 'C' . ($key + 1), /*A1 招募奖励B1 团队 C1 个人冠军C2 个人亚军C3 个人季军*/
                'trace_month' => $month,
                'saler_id'      => $vo['saler_id'],
                'trace_time'    => date('YmdHis'),
                'user_get_flag' => 0,
                'amount'        => $RankingAmount,
            );
            $this->meiHuiModel->addTrace($data);
        }
        /*把剩下的奖金记录到日志里面*/
        $remain_reward = $total_reward - $consume_reward;
        $this->meiHuiModel->addRemainReward($remain_reward);
        /*
         * 处理差价提成逻辑
         */
        $list = $this->meiHuiModel->getDifferenceCommission($month);
        foreach ($list as $vo) {
            $data = array(
                'trace_type'    => 'D', /*A 招募奖励B团队奖励C 排行奖励 D差价提成*/
                'trace_subtype' => 'D1', /*A1 招募奖励B1 团队 C1 个人冠军C2 个人亚军C3 个人季军 D1差价提成*/
                'trace_month' => $month,
                'saler_id'      => $vo['saler_id'],
                'trace_time'    => date('YmdHis'),
                'user_get_flag' => 0,
                'amount'        => $vo['all_amount'],
            );
            $this->meiHuiModel->addTrace($data);

        }
        /*自动升降级*/
        $date_time1 = $this->meiHuiModel->GetMonth(-3) . '01';
        $date_time2 = $this->meiHuiModel->GetMonth(0) . '31';

        /*处理降级*/
        $salers = $this->meiHuiModel->getAmountEq0($date_time1, $date_time2);
        foreach ($salers as $vo) {
            /*成为会员满3个月才执行降级*/
            $audit_time = $vo['audit_time'];
            if ($audit_time > $date_time1 . '000000') {
                continue;
            }

            /*银牌会员的降级*/
            if ($vo['parent_id'] > 0 && $vo['level'] == 4) {
                $parent_path     = explode(',', $vo['parent_path']);
                $new_parent_path = '0,' . $parent_path[1] . ',';
                $this->meiHuiModel->AutoProssLevel($vo['id'], $parent_path[1], $vo['level'], $new_parent_path); /*直接把parent_id修改为门店id*/
                $data = array(
                    'saler_id'    => $vo['id'],
                    'trace_time'  => date('YmdHis'),
                    'change_type' => 0,
                    'change_flag' => 1,
                    'old_value'   => ($vo['level']) . ':' . $vo['parent_id'],
                    'new_value'   => ($vo['level']) . ':' . $parent_path[1],
                );
                if ($data['old_value'] != $data['new_value']) {
                    $this->meiHuiModel->addSalerTrace($data);
                }
            }
            /*金牌会员降级*/
            if ($vo['parent_id'] > 0 && $vo['level'] == 3) {
                $parent_path = explode(',', $vo['parent_path']);
                /*处理他下面的银牌会员  金下的银下挂到金的上级*/
                $my_teammembers = $this->meiHuiModel->getAllTeamMember($vo['id']);
                foreach ($my_teammembers as $v) {
                    if ($vo['id'] != $v['id']) {
                        $this->meiHuiModel->AutoProssLevel($v['id'], $vo['parent_id'], $v['level'], $vo['parent_path']);
                        $data = array(
                            'saler_id'    => $vo['id'],
                            'trace_time'  => date('YmdHis'),
                            'change_type' => 0,
                            'change_flag' => 0,
                            'old_value'   => ($v['level']) . ':' . $v['parent_id'],
                            'new_value'   => ($v['level']) . ':' . $vo['parent_id'],
                        );
                        $this->meiHuiModel->addSalerTrace($data);
                    }
                }
                /*处理金牌会员  脱离钻  下挂到门店*/
                $new_parent_path = '0,' . $parent_path[1] . ',';
                $this->meiHuiModel->AutoProssLevel($vo['id'], $parent_path[1], $vo['level'] + 1, $new_parent_path);
                $data = array(
                    'saler_id'    => $vo['id'],
                    'trace_time'  => date('YmdHis'),
                    'change_type' => 0,
                    'change_flag' => 1,
                    'old_value'   => ($vo['level']) . ':' . $vo['parent_id'],
                    'new_value'   => ($vo['level'] + 1) . ':' . $parent_path[1],
                );
                $this->meiHuiModel->addSalerTrace($data);
            }
            /*钻石会员降级*/
            if ($vo['parent_id'] > 0 && $vo['level'] == 2) {
                /*处理下面的金牌和银牌会员*/
                $my_teammembers = $this->meiHuiModel->getAllTeamMember($vo['id']);
                foreach ($my_teammembers as $v) {
                    if ($vo['id'] != $v['id']) {
                        $new_parent_path = str_replace(',' . $vo['id'] . ',', ',', $v['parent_path']);
                        if ($v['level'] == 3) {
                            $parent_id = $vo['parent_id'];
                        } elseif ($v['level'] == 4) {
                            $parent_id = $v['parent_id'];
                        }
                        $this->meiHuiModel->AutoProssLevel($v['id'], $parent_id, $v['level'], $new_parent_path);
                        if ($v['level'] == 3) {
                            $data = array(
                                'saler_id'    => $vo['id'],
                                'trace_time'  => date('YmdHis'),
                                'change_type' => 0,
                                'change_flag' => 0,
                                'old_value'   => ($v['level']) . ':' . $v['parent_id'],
                                'new_value'   => ($v['level']) . ':' . $parent_id,
                            );
                            $this->meiHuiModel->addSalerTrace($data);
                        }
                    }
                }
                /*处理钻石会员*/
                $this->meiHuiModel->AutoProssLevel($vo['id'], $vo['parent_id'], $vo['level'] + 1, $vo['parent_path']);
                $data = array(
                    'saler_id'    => $vo['id'],
                    'trace_time'  => date('YmdHis'),
                    'change_type' => 0,
                    'change_flag' => 1,
                    'old_value'   => ($vo['level']) . ':' . $vo['parent_id'],
                    'new_value'   => ($vo['level'] + 1) . ':' . $vo['parent_id'],
                );
                $this->meiHuiModel->addSalerTrace($data);
            }

            # code...
        }

        /*处理升级*/
        /*pid不变的情况   1.门店下的会员升级     2.钻石会员下银牌会员升级*/
        //$month          = $this->meiHuiModel->GetMonth(-1);
        $month          = $this->meiHuiModel->GetMonth(0); //配合测试取本月数据
        $otherSetValue  = $this->meiHuiModel->getotherSetValue();
        $limit_amount_3 = $otherSetValue['update_amount_limit_3'];
        $limit_amount_4 = $otherSetValue['update_amount_limit_4'];
        if ($limit_amount_3 > $limit_amount_4) {
            $limit_amount = $limit_amount_4;
        } else {
            $limit_amount = $limit_amount_3;
        }

        $salers = $this->meiHuiModel->getAmountEgt($month, $limit_amount);
        echo "处理升级会员";
        echo "<pre>";
        var_dump($salers);
        echo "</pre>";
        foreach ($salers as $vo) {
            /*银牌会员升级*/
            if ($vo['parent_id'] > 0 && $vo['level'] == 4) {
                /*判断限额*/
                if ($vo['total_amount'] < $limit_amount_4) {
                    echo $vo['total_amount'] . "未达到银牌升级限额" . $limit_amount_4 . "<br>";
                    continue;
                }
                $parent_path = explode(',', $vo['parent_path']);
                if (count($parent_path) == 3) {
                    /*门店 银升金*/
                    $new_parent_path = '0,' . $parent_path[1] . ',';
                    $parent_id       = $parent_path[1];
                }
                if (count($parent_path) == 4) {
                    /*钻石 银升金*/
                    $new_parent_path = $vo['parent_path'];
                    $parent_id       = $vo['parent_id'];
                }
                if (count($parent_path) == 5) {
                    /*金牌 银升金*/
                    $new_parent_path = str_replace(',' . $vo['parent_id'] . ',', ',', $vo['parent_path']);
                    $parent_id       = $parent_path[2];
                }
                $this->meiHuiModel->AutoProssLevel($vo['saler_id'], $parent_id, $vo['level'] - 1, $new_parent_path);
                $data = array(
                    'saler_id'    => $vo['saler_id'],
                    'trace_time'  => date('YmdHis'),
                    'change_type' => 0,
                    'change_flag' => 2,
                    'old_value'   => ($vo['level']) . ':' . $parent_id,
                    'new_value'   => ($vo['level'] - 1) . ':' . $parent_id,
                );
                $this->meiHuiModel->addSalerTrace($data);
            }
            /*金牌会员升级*/
            if ($vo['parent_id'] > 0 && $vo['level'] == 3) {
                /*判断限额*/
                if ($vo['total_amount'] < $limit_amount_3) {
                    echo $vo['total_amount'] . "未达到金牌升级限额" . $limit_amount_3 . "<br>";
                    continue;
                }
                $parent_path = explode(',', $vo['parent_path']);
                /*金牌下面的银牌会员   path改变*/
                $my_teammembers = $this->meiHuiModel->getAllTeamMember($vo['id']);
                foreach ($my_teammembers as $v) {
                    if ($vo['id'] != $v['id']) {
                        $new_parent_path = str_replace(',' . $vo['parent_id'] . ',', ',', $v['parent_path']);
                        $parent_id       = $v['parent_id'];
                        $this->meiHuiModel->AutoProssLevel($v['id'], $parent_id, $v['level'], $new_parent_path);
                    }
                }
                /*处理金的升级*/
                $new_parent_path = '0,' . $parent_path[1] . ',';
                $parent_id       = $parent_path[1];
                $this->meiHuiModel->AutoProssLevel($vo['saler_id'], $parent_id, $vo['level'] - 1, $new_parent_path);
                $data = array(
                    'saler_id'    => $vo['saler_id'],
                    'trace_time'  => date('YmdHis'),
                    'change_type' => 0,
                    'change_flag' => 2,
                    'old_value'   => ($vo['level']) . ':' . $parent_id,
                    'new_value'   => ($vo['level'] - 1) . ':' . $parent_id,
                );
                $this->meiHuiModel->addSalerTrace($data);
            }
            /*钻石会员无法升级*/
        }
    }
    public function day_ranking() {
        $month    = $this->meiHuiModel->GetMonth(0);
        $day_list = $this->meiHuiModel->getRankingTop($month, 0);
        echo '<pre>';
        var_dump($day_list);
        echo '</pre>';
        foreach ($day_list as $key => $vo) {
            $data = array(
                'saler_id'        => $vo['saler_id'],
                'ranking'         => $key + 1,
                'sales_amount'    => $vo['total_amount'],
                'trace_month'     => $month,
                'trace_time'      => date('YmdHis'),
                'last_order_time' => $vo['last_order_time'],
            );
            $this->meiHuiModel->addRankingDay($data);
        }
    }
}
