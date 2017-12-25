<?php

/**
 *
 * @author lwb Time 20150720
 */
class MatchGuessCjSetModel extends CjSetModel {
    protected $tableName = '__NONE__';
    const WX_ACCOUNT_TYPE_CERTIFIED = '4';
    const WX_ACCOUNT_TYPE_CERTIFIED_SUBSCRIPTION = '2';
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
    public function verifyReqDataForWeel($requestedValue, $nodeId, $mId) {
        $rule = array(
                'is_limit' => array(
                    'null' => false,
                    'strtype' => 'int',
                    'minval' => '0',
                    'maxval' => '1',
                    'name' => '参与限制'),
                'fans_collect_url' => array(
                    'null' => true,
                    'maxlen' => '250',
                    'name' => '微信粉丝招募活动url'),
                'member_batch_id' => array(
                    'null' => true,
                    'name' => '允许参加活动的会员分组id'), 
                'defined_three_name' => [
                    'null' => false, 
                    'strtype' => 'int',
                    'minval' => '0',
                    'maxval' => '1',
                    'name' => '活动形式'
                ]
            );
        try {
            $result = parent::verifyReqData($rule, $requestedValue);
            $batchInfo = M('tmarketing_info')->where(
                array(
                    'id' => $mId, 
                    'node_id' => $nodeId))->find();
            if (! $batchInfo) {
                throw_exception('未找到该营销活动');
            }
            $hasGuess = M('tworld_cup_match_quiz')->where(['node_id' => $nodeId, 'batch_id' => $mId])->find();
            if ($hasGuess) {
                throw_exception('已有人参与投票了，无法切换');
            }
            $result['member_join_flag'] = 0; // 总的限制和不限制的标识，初始值为不限
            $result['member_batch_id'] = ($result['is_limit'] == 1) ? $result['member_batch_id'] : 0;
            //$result['member_batch_id_zj'] = ($result['is_limit_zj'] == 1) ? $result['member_batch_id_zj'] : 0;
            $result['member_reg_mid'] = '';
            // 由于微信分组的“未分组”的id为0，与原来数据库中member_batch_id为0时表示“不限制”重复，所以现在改为-1表示不限制，0表示微信分组的未分组
            // 为了不影响其他页面传送过来的逻辑，在存数据库之前改这两个值
            if ($result['member_batch_id'] === 0) {
                $result['member_batch_id'] = - 1;
            }
//             if ($result['member_batch_id_zj'] === 0) {
//                 $result['member_batch_id_zj'] = - 1;
//             }
            // 如果参与限制或中奖限制有一个有限制，总的member_join_flag就为有限制
            if ($result['member_batch_id'] !== - 1) {
                $result['member_join_flag'] = 1;
            }
        } catch (Exception $e) {
            throw_exception($e->getMessage());
        }
        return $result;
    }

    /**
     * @var DrawLotteryBaseService
     */
    private $DrawLotteryBaseService;

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
        // 更新redis缓存
        if (empty($this->DrawLotteryBaseService)) {
            $this->DrawLotteryBaseService = D('DrawLotteryBase', 'Service');
        }
        $this->DrawLotteryBaseService->generateDrawLotteryFinalDataWithStorage($data['m_id']);
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

    /**
     * 是否绑定了认证的订阅号或服务号
     * @param $node_id
     * @return mixed
     */
    public function isBindCertWxAccount($nodeId) {
        $isBind = false;
        $weixinInfo = M('tweixin_info')->where(
            array(
                'node_id' => $nodeId))->find();
        // 微信已认证号并且状态正常的
        if ($weixinInfo
            && ($weixinInfo['account_type'] == self::WX_ACCOUNT_TYPE_CERTIFIED || $weixinInfo['account_type'] == self::WX_ACCOUNT_TYPE_CERTIFIED_SUBSCRIPTION)
            && $weixinInfo['status'] == '0') {
            $isBind = true;
        }
        return $isBind;
    }

    public function isBindWxCard($node_id) {
        $data = M('tweixin_info');
        // select count(*) from tweixin_info where node_id = 'xxx' and status =
        // '0' 结果>0就通过，=0就不过
        $result = $data->where(
            array(
                'node_id' => $node_id, 
                'status' => '0'))->count();
        return $result;
    }
}