<?php

class FbMhWfxModel extends BaseModel {
    protected $tableName = '__NONE__';
    public $limit        = 0;
    public $merchant_id  = false;
    public function _initialize() {
        parent::_initialize();
    }
    /**
     * 美惠旺分销更新订单数据统计.
     *
     * @author john zhao <zhaosl@imageco.com.cn>
     *
     * @param $where =array()
     *
     * @return int
     */
    public function updateSelfDaystat($month) {
        $date_time1 = $month . '01';
        $date_time2 = $month . '31';
        $delete_sql = "DELETE tfb_mh_wfx_self_daystat WHERE order_date > '" . $date_time1 . "' AND order_date < '" . $date_time2 . "'";
        M()->query($delete_sql);

        $date_time1 = $month . '01000000';
        $date_time2 = $month . '31235959';
        $insert_sql = "INSERT INTO tfb_mh_wfx_self_daystat (node_id,saler_id,order_date,order_count,amount,bonus_amount) SELECT a.node_id, a.id AS saler_id, SUBSTR(b.update_time, 1, 8) AS order_date, COUNT(*) AS order_count , SUM(b.order_amt) AS amount , 0 AS bonus_amount FROM twfx_saler a, ttg_order_info b WHERE a.phone_no = b.order_phone AND a.node_id = b.node_id AND b.pay_status = 2 AND b.order_type=2 AND a.node_id = '" . C('meihui.node_id') . "' AND IFNULL(b.saler_id, 0) = 0 AND b.update_time > '" . $date_time1 . "' AND b.update_time < '" . $date_time2 . "' ORDER BY a.node_id, a.id, SUBSTR(b.update_time, 1, 8)";
        M()->query($insert_sql);
    }
    public function clearData($month) {
        M('tfb_mh_wfx_ranking_trace')->where(array('trace_month' => $month))->delete();
        echo M()->_sql() . '<br>';
        M('tfb_mh_wfx_recruit_trace')->where(array('trace_month' => $month))->delete();
        echo M()->_sql() . '<br>';
        M('tfb_mh_wfx_team_trace')->where(array('trace_month' => $month))->delete();
        echo M()->_sql() . '<br>';
        M('tfb_mh_wfx_trace')->where(array('trace_month' => $month))->delete();
        echo M()->_sql() . '<br>';
    }
    /**
     * 美惠旺分销增加奖励流水.
     *
     * @author john zhao <zhaosl@imageco.com.cn>
     *
     * @param $where =array()
     *
     * @return int
     */
    public function addTrace($data) {
        $where = $data;
        unset($where['trace_time']);
        unset($where['user_get_flag']);
        unset($where['amount']);
        if ((int) M('tfb_mh_wfx_trace')->where($where)->count() == 0) {
            $res = M('tfb_mh_wfx_trace')->add($data);
        } else {
            $res = M('tfb_mh_wfx_trace')->where($where)->data($data)->save();
        }

        return $res;
    }
    /**
     * 美惠旺分销获取所有的团队.
     *
     * @author john zhao <zhaosl@imageco.com.cn>
     */
    public function getAllTeam() {
        /*找门店*/
        $parent_ids = M('twfx_saler')->where(array('role' => 2, 'level' => 1, 'node_id' => C('meihui.node_id')))->getField('id', true);
        $list       = M('twfx_saler')
            ->where(array('role' => 2, 'parent_id' => array('in', $parent_ids), 'node_id' => C('meihui.node_id')))
            ->field('id')
            ->select();
        //echo M()->_sql();

        return $list;
    }
    /**
     * 美惠旺分销获取团队成员.
     *
     * @author john zhao <zhaosl@imageco.com.cn>
     */
    public function getAllTeamMember($team_leader_id) {
        $team_leader_path = M('twfx_saler')->where(array('id' => $team_leader_id))->getField('parent_path');
        $list             = M('twfx_saler')
            ->where(array('role' => 2, 'parent_path' => array('like', $team_leader_path . $team_leader_id . '%'), 'node_id' => C('meihui.node_id')))
            ->getField('id', true);
        echo '查找' . $team_leader_id . '团队成员:' . M()->_sql() . '</br>';
        $list[] = $team_leader_id;

        return $list;
    }
    /**
     * 美惠旺分销获取团队总计实收款.
     *
     * @author john zhao <zhaosl@imageco.com.cn>
     */
    public function getTeamAllAmount($team_member_ids, $month) {
        $saler_ids  = implode(',', $team_member_ids);
        $date_time1 = $month . '01';
        $date_time2 = $month . '31';
        $sql        = 'select sum(amount) as total_amount from (SELECT * FROM tfb_mhsaler_daystat UNION SELECT * FROM tfb_mh_wfx_self_daystat) tt where order_date>=' . $date_time1 . ' and order_date <=' . $date_time2 . ' and saler_id in (' . $saler_ids . ') ';
        echo '计算团队实收：';
        echo $sql . '<br>';

        $res = M()->query($sql);
        var_dump($res);
        if ($res[0]['total_amount']) {
            return $res[0]['total_amount'];
        } else {
            return 0;
        }
    }
    /**
     * 美惠旺分销获取个人实收款.
     *
     * @author john zhao <zhaosl@imageco.com.cn>
     */
    public function getMyAmount($saler_id, $month) {
        $date_time1 = $month . '01';
        $date_time2 = $month . '31';
        $sql        = 'select sum(amount) as total_amount from (SELECT * FROM tfb_mhsaler_daystat UNION SELECT * FROM tfb_mh_wfx_self_daystat) tt where order_date>=' . $date_time1 . ' and order_date <=' . $date_time2 . ' and saler_id =' . $saler_id;
        echo '计算个人实收：';
        echo $sql;
        $res = M()->query($sql);
        var_dump($res);
        if ($res[0]['total_amount']) {
            return $res[0]['total_amount'];
        } else {
            return 0;
        }
    }
    public function getMyLevel($saler_id) {
        return M('twfx_saler')->where(array('id' => $saler_id))->getField('level');
    }
    public function getMySalePercent($my_level) {
        $team_config = M('tfb_mh_wfx_config')->where(array('param_name' => 'TEAM_REWARD_CONFIG'))->getField('set_ratio');
        $team_config = json_decode($team_config, true);
        if (!empty($team_config)) {
            if ($my_level == 4 && isset($team_config['silver'])) {
                return $team_config['silver'];
            }
            if ($my_level == 3 && isset($team_config['gold_sale'])) {
                return $team_config['gold_sale'];
            }
            if ($my_level == 2 && isset($team_config['diamond_sale'])) {
                return $team_config['diamond_sale'];
            }
        } else {
            return 0;
        }
    }
    public function getMyManagePercent($my_level) {
        $team_config = M('tfb_mh_wfx_config')->where(array('param_name' => 'TEAM_REWARD_CONFIG'))->getField('set_ratio');
        $team_config = json_decode($team_config, true);
        if (!empty($team_config)) {
            if ($my_level == 4) {
                return 0;
            }
            if ($my_level == 3 && isset($team_config['gold_manage'])) {
                return $team_config['gold_manage'];
            }
            if ($my_level == 2 && isset($team_config['diamond_manage'])) {
                return $team_config['diamond_manage'];
            }
        } else {
            return 0;
        }
    }
    /**
     * 美惠旺分销获取团队奖励比例.
     *
     * @author john zhao <zhaosl@imageco.com.cn>
     */
    public function getTeamPrizePer($amount) {
        /*获取配置规则*/
        /*从大到小排序*/
        /*判断是否大于最小的一个*/
        /*循环判断是否大于比较大的一个*/
        $team_config = M('tfb_mh_wfx_config')->where(array('param_name' => 'TEAM_REWARD_CONFIG'))->getField('param_value');
        $team_config = json_decode($team_config, true);
        $temp        = array();
        $min_amount  = 99999999999999999; /*先搞一个很大的值*/
        foreach ($team_config as $config) {
            $temp[$config['month_price']] = $config['reward_rate'];
            if ($config['month_price'] <= $min_amount) {
                $min_amount = $config['month_price'];
            }
        }
        krsort($temp);
        if ($amount >= $min_amount) {
            foreach ($temp as $max_amount => $reward_rate) {
                if ($amount >= $max_amount) {
                    return $reward_rate;
                }
            }
        } else {
            return 0;
        }
    }

    /**
     * 美惠旺分销记录个人从团队奖励中获取的奖励流水.
     *
     * @author john zhao <zhaosl@imageco.com.cn>
     */
    public function addTeamTrace($data) {
        $where = $data;
        unset($where['trace_time']);
        unset($where['trace_config']);
        unset($where['amount']);
        unset($where['sales_amount']);
        if ((int) M('tfb_mh_wfx_team_trace')->where($where)->count() == 0) {
            $res = M('tfb_mh_wfx_team_trace')->add($data);
        } else {
            $res = M('tfb_mh_wfx_team_trace')->where($where)->data($data)->save();
        }
        echo '更新tfb_mh_wfx_team_trace：' . M()->_sql();

        return $res;
    }
    /**
     * 美惠旺分销获取钻石会员和金牌会员.
     *
     * @author john zhao <zhaosl@imageco.com.cn>
     */
    public function getGlodAndDiamondMember() {
        /*twfx_saler  referee_id=$saler_id   and  parent_id=$saler_id*/
        $ids = M('twfx_saler')->where(array('level' => array(array('eq', 2), array('eq', 3), 'or'), 'parent_id' => array('neq', 0), 'node_id' => C('meihui.node_id')))->getField('id', true);

        return $ids;
    }
    /**
     * 美惠旺分销获取金牌或者钻石招募的会员.
     *
     * @author john zhao <zhaosl@imageco.com.cn>
     */
    public function getMyRecruitMember($referee_id) {
        return M('twfx_saler')
            ->where(array('referee_id' => $referee_id, 'parent_id' => $referee_id, 'node_id' => C('meihui.node_id')))
            ->getField('id', true);
    }

    /**
     * 美惠旺分销获取当月销售额大于1000的id.
     *
     * @author john zhao <zhaosl@imageco.com.cn>
     *
     * @param $where =array()
     *
     * @return array
     */
    public function getRecruitEgt1000Trace($saler_ids, $month) {
        /*
        SELECT b.* FROM (
        SELECT *,
        SUM(tfb_mhsaler_daystat.amount) all_amount
        FROM
        tfb_mhsaler_daystat
        WHERE order_date >= '20150101'
        AND order_date <= '20150801'
        GROUP BY saler_id) AS b WHERE b.all_amount >1000
         */
        $otherSetValue = $this->getotherSetValue();
        $limit_amount  = $otherSetValue['recruit_limit'];
        $date_time1    = $month . '01';
        $date_time2    = $month . '31';
        $sql           = 'select b.* from (select *,sum(amount) as total_amount from (SELECT * FROM tfb_mhsaler_daystat UNION SELECT * FROM tfb_mh_wfx_self_daystat) tt where order_date>=' . $date_time1 . ' and order_date <=' . $date_time2 . ' group by saler_id) as b where b.node_id= ' . C('meihui.node_id') . ' and b.total_amount >' . $limit_amount . ' and b.saler_id in (' . implode(',', $saler_ids) . ')';

        return M()->query($sql);
    }
    /**
     * 美惠旺分销记录个人从招募人员中获取奖励的流水.
     *
     * @author john zhao <zhaosl@imageco.com.cn>
     */
    public function addRecruitTrace($data) {
        $where = $data;
        unset($where['trace_time']);
        unset($where['amount']);
        unset($where['sales_amount']);
        if ((int) M('tfb_mh_wfx_recruit_trace')->where($where)->count() == 0) {
            $res = M('tfb_mh_wfx_recruit_trace')->add($data);
        } else {
            $res = M('tfb_mh_wfx_recruit_trace')->where($where)->data($data)->save();
        }

        return $res;
    }

    /**排名参数获取*/
    /**
     * 美惠旺分销获取月份排名资格限制.
     *
     * @author john zhao <zhaosl@imageco.com.cn>
     */
    public function getJoinRankingLimit($month) {
        $sales_config = M('tfb_mh_wfx_config')->where(array('param_name' => 'SALER_REWARD_CONFIG'))->getField('param_value');
        $sales_config = json_decode($sales_config, true);

        return $sales_config['month'][(int) $month - 1];
    }
    /**排名参数获取*/
    /**
     * 美惠旺分销获取月份排名前三的salerid.
     *
     * @author john zhao <zhaosl@imageco.com.cn>
     */
    public function getRankingTopThree($month, $limit_amount) {
        $date_time1   = $month . '01';
        $date_time2   = $month . '31';
        $update_time1 = $month . '01000000';
        $update_time2 = $month . '31235959';
        $sql          = 'select b.*,(select MAX(update_time) from ttg_order_info where saler_id=b.saler_id and pay_status = 2 AND order_type=2 and update_time > ' . $update_time1 . ' AND update_time < ' . $update_time2 . ' AND node_id = b.node_id) as last_order_time from (select *,sum(amount) as total_amount from (SELECT * FROM tfb_mhsaler_daystat UNION SELECT * FROM tfb_mh_wfx_self_daystat) tt where order_date>=' . $date_time1 . ' and order_date <=' . $date_time2 . ' group by saler_id) as b
        left join twfx_saler c on c.id=b.saler_id
         where c.level>1 and b.node_id= ' . C('meihui.node_id') . ' and b.total_amount >' . $limit_amount . ' order by b.total_amount desc,last_order_time asc limit 0,3';

        return M()->query($sql);
    }
    /**
     * 美惠旺分销获取排名奖励总额.
     *
     * @author john zhao <zhaosl@imageco.com.cn>
     */
    public function getRankingAmount($ranking) {
        $sales_config  = M('tfb_mh_wfx_config')->where(array('param_name' => 'SALER_REWARD_CONFIG'))->getField('param_value');
        $sales_config  = json_decode($sales_config, true);
        $month         = $this->GetMonth(-1);
        $remain_reward = $this->getRemainReward($month);
        if ($remain_reward > 0) {
            $all_reward = $this->getAllReward();
            $sales_config['reward'][(int) $ranking] += round($remain_reward * $sales_config['reward'][(int) $ranking] / $all_reward, 2);
        }

        return $sales_config['reward'][(int) $ranking];
    }
    public function getAllReward() {
        $sales_config = M('tfb_mh_wfx_config')->where(array('param_name' => 'SALER_REWARD_CONFIG'))->getField('param_value');
        $sales_config = json_decode($sales_config, true);
        $all_reward   = 0;
        foreach ($sales_config['reward'] as $reward) {
            $all_reward += $reward;
        }

        return $all_reward;
    }
    public function addRankingTrace($data) {
        $where = $data;
        unset($where['trace_time']);
        unset($where['amount']);
        unset($where['ranking']);
        unset($where['sales_amount']);
        unset($where['last_order_time']);

        if ((int) M('tfb_mh_wfx_ranking_trace')->where($where)->count() == 0) {
            $res = M('tfb_mh_wfx_ranking_trace')->add($data);
        } else {
            $res = M('tfb_mh_wfx_ranking_trace')->where($where)->data($data)->save();
        }

        return $res;
    }
    /*自动升降级*/
    public function GetMonth($sign = 0) {
        //得到系统的年月
        $tmp_date = date('Ym');
        //切割出年份
        $tmp_year = substr($tmp_date, 0, 4);
        //切割出月份
        $tmp_mon = substr($tmp_date, 4, 2);

        $tmp_month = mktime(0, 0, 0, $tmp_mon + $sign, 1, $tmp_year);

        return date('Ym', $tmp_month);
    }
    public function getAmountEq0($date_time1, $date_time2) {
        /*
        SELECT * FROM twfx_saler t
        WHERE NOT EXISTS (SELECT * FROM tfb_mhsaler_daystat b WHERE b.`saler_id` = t.id AND b.`order_date` > '20150101' AND b.`order_count` < '20160531');
         */
        $sql = "SELECT id,parent_id,level,parent_path,audit_time FROM twfx_saler t
 WHERE NOT EXISTS (SELECT * FROM (SELECT * FROM tfb_mhsaler_daystat UNION SELECT * FROM tfb_mh_wfx_self_daystat) b WHERE b.`saler_id` = t.id AND b.`order_date` >= '" . $date_time1 . "' AND b.`order_count` <= '" . $date_time2 . "') AND node_id = '" . C('meihui.node_id') . "'";
        echo $sql;

        return M()->query($sql);
    }
    public function getAmountEgt($month, $limit_amount) {
        $date_time1 = $month . '01';
        $date_time2 = $month . '31';
        $sql        = 'select b.*,c.parent_id,c.level,c.parent_path,c.audit_time from (select *,sum(amount) as total_amount from (SELECT * FROM tfb_mhsaler_daystat UNION SELECT * FROM tfb_mh_wfx_self_daystat) tt where order_date>=' . $date_time1 . ' and order_date <=' . $date_time2 . ' group by saler_id) as b left join twfx_saler c on b.saler_id=c.id  where b.node_id= ' . C('meihui.node_id') . ' and b.total_amount >' . $limit_amount . ' order by b.total_amount desc';
        echo $sql;

        return M()->query($sql);
    }
    public function AutoProssLevel($saler_id, $parent_id, $level, $parent_path) {
        return M('twfx_saler')->where(array('id' => $saler_id))->data(array('parent_id' => $parent_id, 'level' => $level, 'parent_path' => $parent_path))->save();
    }

    public function addSalerTrace($data) {
        $res = M('tfb_mh_wfx_saler_trace')->add($data);

        return $res;
    }
    public function getRemainReward($month) {
        $remain_saler_reward = M('tfb_mh_wfx_config_permonth')->where(array('trace_month' => $month, 'reward_flag' => 0))->getField('remain_saler_reward');
        if (!$remain_saler_reward) {
            $remain_saler_reward = 0;
        }

        return $remain_saler_reward;
    }
    public function addRemainReward($remain_reward) {
        $team_config  = M('tfb_mh_wfx_config')->where(array('param_name' => 'TEAM_REWARD_CONFIG'))->getField('param_value');
        $sales_config = M('tfb_mh_wfx_config')->where(array('param_name' => 'SALER_REWARD_CONFIG'))->getField('param_value');
        $where        = array('trace_month' => date('Ym'));
        $data         = array(
            'trace_month'         => date('Ym'),
            'team_config'         => $team_config,
            'saler_config'        => $sales_config,
            'remain_saler_reward' => $remain_reward,
            'reward_flag'         => 0,
        );
        if ((int) M('tfb_mh_wfx_config_permonth')->where($where)->count() == 0) {
            $res = M('tfb_mh_wfx_config_permonth')->add($data);
        } else {
            unset($data['trace_month']);
            $res = M('tfb_mh_wfx_config_permonth')->where($where)->data($data)->save();
        }

        return $res;
    }

    /**排名参数获取*/
    /**
     * 美惠旺分销获排名日统计.
     *
     * @author john zhao <zhaosl@imageco.com.cn>
     */
    public function getRankingTop($month, $limit_amount) {
        $date_time1   = $month . '01';
        $date_time2   = $month . '31';
        $update_time1 = $month . '01000000';
        $update_time2 = $month . '31235959';
        $sql          = 'select b.*,(select MAX(update_time) from ttg_order_info where saler_id=b.saler_id and pay_status = 2 AND order_type=2 and update_time > ' . $update_time1 . ' AND update_time < ' . $update_time2 . ' AND node_id = b.node_id) as last_order_time from (select *,sum(amount) as total_amount from (SELECT * FROM tfb_mhsaler_daystat UNION SELECT * FROM tfb_mh_wfx_self_daystat) tt where order_date>=' . $date_time1 . ' and order_date <=' . $date_time2 . ' group by saler_id) as b
        left join twfx_saler c on c.id=b.saler_id
         where c.level>1 and b.node_id= ' . C('meihui.node_id') . ' and b.total_amount >' . $limit_amount . ' order by b.total_amount desc,last_order_time asc';
        echo $sql . '<br>';

        return M()->query($sql);
    }
    public function addRankingDay($data) {
        $where = $data;
        unset($where['trace_time']);
        unset($where['ranking']);
        unset($where['sales_amount']);
        unset($where['last_order_time']);

        if ((int) M('tfb_mh_wfx_ranking_day')->where($where)->count() == 0) {
            $res = M('tfb_mh_wfx_ranking_day')->add($data);
        } else {
            $res = M('tfb_mh_wfx_ranking_day')->where($where)->data($data)->save();
        }

        return $res;
    }

    public function getotherSetValue() {
        $map = array('param_name' => 'OTHER_CONFIG');
        $res = M("tfb_mh_wfx_config")->where($map)->find();
        if ($res) {
            return json_decode($res['param_value'], true);
        }
    }

    public function getDifferenceCommission($month) {
        $sql = 'select *,sub(amount) as all_amount from tfb_mh_wfx_settlement_trace where trace_month = ' . $month . 'group by saler_id,trace_month';
        return M()->query($sql);

    }
}
