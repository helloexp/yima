<?php

/**
 * 功能：抽奖次数查询接口
 *
 * @author siht 时间：2013-07-31
 */
class AwardChancesQueryAction extends BaseAction {
    // 接口参数
    public $node_id;
    // 机构号
    public $batch_id;
    // 抽奖id
    public $phone_no;
    // 抽奖手机号
    public $cj_rule_id;

    // 抽奖规则id
    public function _initialize() {
        parent::_initialize();
        C('AwardRequest', require(CONF_PATH . 'configAwardRequest.php'));

        $this->node_id    = I('node_id'); // 机构号
        $this->batch_id   = I('batch_id'); // 抽奖id
        $this->phone_no   = I('phone_no'); // 抽奖手机号
        $this->cj_rule_id = I('cj_rule_id'); // 抽奖规则id
    }

    public function run() {
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
        $this->getChances();
    }

    // 获得抽奖次数
    private function getChances() {
        $now_time = date('YmdHis');
        $now_date = date('Ymd');
        // 判断营销活动有效期
        $marketing_info = M('tmarketing_info')->where(" status = '1' and id = " . $this->batch_id)->find();
        if (!$marketing_info) {
            // 营销活动不存在
            $this->returnError('营销活动不存在', '7002');
        } else if ($marketing_info['start_time'] > $now_time || $marketing_info['end_time'] < $now_time) {
            // 营销活动过期
            $this->returnError('营销活动过期', '7003');
        }

        // 取tcj_rule信息
        $where        = "id ='" . $this->cj_rule_id . "' and status = '1'";
        $cj_rule_info = M('tcj_rule')->where($where)->find();
        if (!$cj_rule_info) {
            $this->returnError('抽奖规则[' . $this->cj_rule_id . ']不存在', '7001');
        }
        // 统计该手机号码总参与抽奖次数
        $where              = "m_id = " . $this->batch_id . " and mobile = '" . $this->phone_no . "' and node_id = '" . $this->node_id . "' ";
        $cj_stat_info_total = M()->table('tcj_stat')->field('ifnull(sum(stat_num), 0) as stat_num_sum')->where($where)->find();
        $last_total_num     = 0;
        $last_total_num     = $cj_rule_info['phone_total_part'] - $cj_stat_info_total['stat_num_sum'];
        // 统计该手机号码日参与抽奖次数
        $where            = "m_id = " . $this->batch_id . " and mobile = '" . $this->phone_no . "' and node_id = '" . $this->node_id . "' and add_date = '" . $now_date . "'";
        $cj_stat_info_day = M()->table('tcj_stat')->field('ifnull(sum(stat_num), 0) as stat_num_sum')->where($where)->find();
        $last_total_day   = 0;
        $last_total_day   = $cj_rule_info['phone_day_part'] - $cj_stat_info_day['stat_num_sum'];

        $this->returnSuccess("查询成功", array(
                        "last_chances" => min($last_total_num, $last_total_day),
                ));
    }

    private function check() {
        if ($this->batch_id == '') {
            return array(
                    'resp_desc' => '抽奖id不能为空！',
            );

            return false;
        }

        if ($this->phone_no == '' && $this->wx_open_id == '') {
            return array(
                    'resp_desc' => '手机号和wx_open_id不能同时为空！',
            );

            return false;
        }
        if ($this->node_id == '') {
            return array(
                    'resp_desc' => '机构号不能为空！',
            );

            return false;
        }
        if ($this->cj_rule_id == '') {
            return array(
                    'resp_desc' => '抽奖规则id为空！',
            );

            return false;
        }

        return true;
    }
}

?>
