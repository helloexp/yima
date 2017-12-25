<?php

/**
 *
 * @author lwb Time 20150720
 */
class RaiseFlagCjSetModel extends CjSetModel {
    protected $tableName = '__NONE__';
    const WX_ACCOUNT_TYPE_CERTIFIED = '4';
    // 已认证的服务号
    public function _initialize() {
        import('@.Vendor.CommonConst') or die('include file fail.');
    }

    /**
     *
     * @param array $requestedValue 需要验证的数据
     * @param string $nodeId
     * @return array $result 经过验证的数据
     */
    public function verifyReqDataForWeel($requestedValue, $nodeId) {
        $rule = array(
            'join_mode' => array(
                'null' => true, 
                'strtype' => 'int', 
                'minval' => '0', 
                'maxval' => '1', 
                'name' => '参与方式'), 
            'phone_total_count' => array(
                'null' => false, 
                'strtype' => 'int', 
                'minval' => '0', 
                'name' => '每个手机总中奖次数'), 
            'phone_day_count' => array(
                'null' => false, 
                'strtype' => 'int', 
                'minval' => '0', 
                'name' => '每个手机每天中奖次数'), 
            'phone_total_part' => array(
                'null' => false, 
                'strtype' => 'int', 
                'minval' => '0', 
                'name' => '每个手机总抽奖次数'), 
            'phone_day_part' => array(
                'null' => false, 
                'strtype' => 'int', 
                'minval' => '0', 
                'name' => '每个手机每天抽奖次数'), 
            'm_id' => array(
                'null' => false, 
                'strtype' => 'int', 
                'name' => '营销活动号'), 
            'is_limit' => array(
                'null' => false, 
                'strtype' => 'int', 
                'minval' => '0', 
                'maxval' => '1', 
                'name' => '参与限制'), 
            'is_limit_zj' => array(
                'null' => false, 
                'strtype' => 'int', 
                'minval' => '0', 
                'maxval' => '1', 
                'name' => '中奖限制'), 
            'fans_collect_url' => array(
                'null' => true, 
                'maxlen' => '250', 
                'name' => '微信粉丝招募活动url'), 
            'member_reg_mid' => array(
                'null' => true, 
                'name' => '会员招募活动id'), 
            'member_batch_id' => array(
                'null' => true, 
                'name' => '允许参加活动的会员分组id'), 
            'member_batch_id_zj' => array(
                'null' => true, 
                'name' => '允许中奖的会员分组id'), 
            'wx_auth_type' => array(
                'null' => false, 
                'strtype' => 'int', 
                'minval' => '0', 
                'maxval' => '1', 
                'name' => '微信授权'));
        try {
            $result = parent::verifyReqData($rule, $requestedValue);
            if ($result['phone_day_count'] > 0 &&
                 $result['phone_total_count'] > 0 &&
                 $result['phone_day_count'] > $result['phone_total_count']) {
                throw_exception('日中奖次数不能大于总中奖次数');
            }
            if ($result['phone_day_part'] > 0 && $result['phone_total_part'] > 0 &&
                 $result['phone_day_part'] > $result['phone_total_part']) {
                throw_exception('日参与次数不能大于总参与次数');
            }
            $batchInfo = M('tmarketing_info')->where(
                array(
                    'id' => $result['m_id'], 
                    'node_id' => $nodeId))->find();
            if (! $batchInfo) {
                throw_exception('未找到该营销活动');
            }
            // 如果参与方式变更了，需要判断是否已经有参与记录，如果有则不允许变更！
            if ($batchInfo['join_mode'] != $result['join_mode']) {
                $cnt = (int) M('tcj_trace')->where(
                    array(
                        'batch_id' => $result['m_id']))->count();
                if ($cnt > 0) {
                    throw_exception('营销活动已经有抽奖记录了，不允许变更参与方式');
                }
            }
            
            $result['member_join_flag'] = 0; // 总的限制和不限制的标识，初始值为不限
            $result['member_batch_id'] = ($result['is_limit'] == 1) ? $result['member_batch_id'] : 0;
            $result['member_batch_id_zj'] = ($result['is_limit_zj'] == 1) ? $result['member_batch_id_zj'] : 0;
            if ($result['join_mode'] == '0') {
                if ($result['is_limit'] == '1' &&
                     ! empty($result['member_reg_mid'])) {
                    $map = array(
                        'm.node_id' => $nodeId, 
                        'm.batch_type' => CommonConst::BATCH_TYPE_RECRUIT, 
                        'm.status' => '1', 
                        'm.re_type' => '0', 
                        'm.start_time' => array(
                            'elt', 
                            date('YmdHis')), 
                        'm.end_time' => array(
                            'egt', 
                            date('YmdHis')), 
                        'm.id' => $result['member_reg_mid'], 
                        'c.channel_id' => array(
                            'exp', 
                            'is not null'), 
                        'c.status' => '1');
                    $count = M()->table("tmarketing_info m")->join(
                        'tbatch_channel c ON m.id=c.batch_id')
                        ->where($map)
                        ->count();
                    if (! $count) {
                        throw_exception('无效的会员招募活动');
                    }
                }
                if ($result['member_batch_id'] != '' &&
                     $result['member_batch_id'] != '0') {
                    $arr = explode($result['member_batch_id']);
                    $map = array(
                        'node_id' => $nodeId, 
                        'status' => '1', 
                        'id' => array(
                            'in', 
                            $arr));
                    $cnt = M('tmember_batch')->where($map)->count();
                    if ($cnt != count($arr)) {
                        throw_exception('无效的活动分组');
                    }
                }
                if ($result['member_batch_id_zj'] != '' &&
                     $result['$member_batch_id_zj'] != '0') {
                    $arr = explode($result['member_batch_id_zj']);
                    $map = array(
                        'node_id' => $nodeId, 
                        'status' => '1', 
                        'id' => array(
                            'in', 
                            $arr));
                    $cnt = M('tmember_batch')->where($map)->count();
                    if ($cnt != count($arr)) {
                        throw_exception('无效的中奖活动分组');
                    }
                }
                $result['fans_collect_url'] = '';
            } elseif ($result['join_mode'] == 1) { // 参与方式：微信号
                $result['member_reg_mid'] = '';
            }
            // 由于微信分组的“未分组”的id为0，与原来数据库中member_batch_id为0时表示“不限制”重复，所以现在改为-1表示不限制，0表示微信分组的未分组
            // 为了不影响其他页面传送过来的逻辑，在存数据库之前改这两个值
            if ($result['member_batch_id'] === 0) {
                $result['member_batch_id'] = - 1;
            }
            if ($result['member_batch_id_zj'] === 0) {
                $result['member_batch_id_zj'] = - 1;
            }
            // 如果参与限制或中奖限制有一个有限制，总的member_join_flag就为有限制
            if ($result['member_batch_id'] !== - 1 ||
                 $result['member_batch_id_zj'] !== - 1) {
                $result['member_join_flag'] = 1;
            }
            // 组装config_data
            $configDataArr = unserialize($batchInfo['config_data']);
            $peopleJoinedFlag = D('RaiseFlag')->hasPeopleJoined(
                $batchInfo['id']);
            if ($peopleJoinedFlag == true) {
                if ($configDataArr['wx_auth_type'] != $result['wx_auth_type']) {
                    throw_exception('营销活动已经有人参与了，不允许变更微信授权方式');
                }
            }
            $configDataArr['wx_auth_type'] = $result['wx_auth_type'];
            $result['config_data'] = serialize($configDataArr);
        } catch (Exception $e) {
            throw_exception($e->getMessage());
        }
        return $result;
    }

    /**
     * 更新tmarketing_info和tcj_rule
     *
     * @param array $data
     * @param string $nodeId
     * @return boolean
     */
    public function saveData($data, $nodeId) {
        $marketingData = array(
            'join_mode' => $data['join_mode'], 
            'member_batch_id' => $data['member_batch_id'], 
            'member_join_flag' => $data['member_join_flag'], 
            'member_reg_mid' => $data['member_reg_mid'], 
            'member_batch_award_id' => $data['member_batch_id_zj'], 
            'fans_collect_url' => $data['fans_collect_url'], 
            'config_data' => $data['config_data']);
        M()->startTrans();
        $flag = M('tmarketing_info')->where(
            array(
                'id' => $data['m_id'], 
                'node_id' => $nodeId))->save($marketingData);
        if (false === $flag) {
            M()->rollback();
            throw_exception('抽奖形式或粉丝专享内容更新失败');
        }
        $ruleList = M('tcj_rule')->where(
            array(
                'batch_id' => $data['m_id'], 
                'status' => '1'))->select();
        if (! $ruleList) {
            M()->rollback();
            throw_exception('系统异常：创建活动时没有保存抽奖规则！');
        }
        if (count($ruleList) > 1) {
            M()->rollback();
            throw_exception('系统异常：存在多条启用的抽奖规则记录！');
        }
        $ruleData = array(
            'phone_total_count' => $data['phone_total_count'], 
            'phone_day_count' => $data['phone_day_count'], 
            'phone_total_part' => $data['phone_total_part'], 
            'phone_day_part' => $data['phone_day_part']);
        $flag = M('tcj_rule')->where(
            array(
                'id' => $ruleList[0]['id']))->save($ruleData);
        if (false === $flag) {
            M()->rollback();
            throw_exception('参与和中奖次数更新失败');
        }
        M()->commit();
        node_log("保存活动配置成功", print_r($data, TRUE));
        return true;
    }

    /**
     * 是否绑定了微信认证服务号
     *
     * @param string $nodeId
     * @return boolean
     */
    public function isBindWxServ($nodeId) {
        $isBind = false;
        $weixinInfo = M('tweixin_info')->where(
            array(
                'node_id' => $nodeId))->find();
        // 微信已认证服务号并且状态正常的
        if ($weixinInfo &&
             $weixinInfo['account_type'] == self::WX_ACCOUNT_TYPE_CERTIFIED &&
             $weixinInfo['status'] == '0') {
            $isBind = true;
        }
        return $isBind;
    }
}