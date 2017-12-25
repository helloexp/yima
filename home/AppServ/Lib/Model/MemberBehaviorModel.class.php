<?php

/*
 * Created by ys. User: Administrator Date: 2015/11/26 Time: 16:46
 */

class MemberBehaviorModel extends Model {
    protected $tableName = '__NONE__';
    /**
     * 会员行为数据增加新增合并 joinTotal:参与活动 sendTotal:接收卡券，
     * verifyTotal:验证数，shopTotal:线上购物数， shopLineTotal:线下购物数
     */
    /**
     * 会员行为数据增加新增合并 type=1为会员参与活动，type=2为会员参与活动并中奖下发卡券 type=3为验证数，type=4为会员线上购物数
     * type=5为手动增积分，type=6手动减积分 type=7为每日签到积分，type=8连续签到7天获得积分
     * type=9积分商城，type=10 线下交易获取积分 type=11 付现金增加积分 type=12线下兑换扣减积分
     * type=13关注微信公众号 type14活动参与中红包 type15活动中奖并中积分卡券 type=1,type=2 $relationId
     */
    public function addBehaviorType(
            $memberId,
            $nodeId,
            $type,
            $point = '',
            $relationId = '',
            $amt = ''
    ) {
        log_write("进来了么？");
        if (empty($memberId) || empty($type) || empty($nodeId)) {
            log_write("会员号或商户号或类型或者备注为空" . "会员号：" . $memberId . "类型：" . $type . '机构号：' . $nodeId,
                    '关联ID：' . $relationId);

            return false;
        }
        $behaviorArr = array(
                'member_id'  => $memberId,
                'node_id'    => $nodeId,
                'type'       => $type,
                'amt'        => $amt,
                'trace_time' => date('YmdHis'),
        );
        // type=1为会员参与活动
        if ($type == 1) {
            $behaviorArr['relation_id'] = $relationId;
            $behaviorArr['change_num']  = '1';
            $behaviorArr['remark']      = "活动名称：" . $this->getActivityName($relationId);
            $res                        = $this->addBehaviorData($memberId, $nodeId, 1);
            if ($res === false) {
                log_write("新增行为数据失败！");

                return false;
            }
        }
        // type=2为会员参与活动并中奖下发卡券
        if ($type == 2) {
            $behaviorArr['relation_id'] = $relationId;
            $behaviorArr['remark']      = "活动名称：" . $this->getActivityName($relationId);
            $behaviorArr['change_num']  = '1';
            $res                        = $this->addBehaviorData($memberId, $nodeId, '', 1);
            if ($res === false) {
                log_write("新增行为数据失败！");

                return false;
            }
        }
        // type=3为验证数
        if ($type == 3) {
            $behaviorArr['relation_id'] = $relationId;
            $behaviorArr['remark']      = "终端名称：" . $this->getPosName($relationId);
            $behaviorArr['change_num']  = '1';
            $res                        = $this->addBehaviorData($memberId, $nodeId, '', '', 1);
            if ($res === false) {
                log_write("新增行为数据失败！");

                return false;
            }
        }
        // type=4为会员线上购物数
        if ($type == 4) {
            $behaviorArr['relation_id'] = $relationId;
            $behaviorArr['remark']      = "支付金额：" . $amt;
            $behaviorArr['change_num']  = $point;
            $res                        = $this->addBehaviorData($memberId, $nodeId, '', '', '', 1);
            if ($res === false) {
                log_write("新增行为数据失败！");

                return false;
            }
        }
        // type=5为手动增积分
        if ($type == 5) {
            $behaviorArr['change_num']  = $point;
            $behaviorArr['relation_id'] = $relationId;
            $behaviorArr['remark']      = "操作员：" . $relationId;
        }
        // type=6手动减积分
        if ($type == 6) {
            $behaviorArr['change_num']  = $point;
            $behaviorArr['relation_id'] = $relationId;
            $behaviorArr['remark']      = "操作员：" . $relationId;
        }
        // type=7为每日签到积分
        if ($type == 7) {
            $behaviorArr['change_num'] = $point;
            $behaviorArr['remark']     = "每日签到";
        }
        // type=8连续签到7天获得积分
        if ($type == 8) {
            $behaviorArr['change_num'] = $point;
            $behaviorArr['remark']     = "连续签到7日";
        }
        // type=9线上（积分商城）兑换扣减积分（终端）
        if ($type == 9) {
            $behaviorArr['relation_id'] = $relationId;
            $behaviorArr['remark']      = "活动名称：" . $this->getActivityName($relationId);
            $behaviorArr['change_num']  = $point;
        }
        // type=10 线下交易获取积分(微信支付)
        if ($type == 10) {
            $behaviorArr['relation_id'] = $relationId;
            $behaviorArr['change_num']  = $point;
            $behaviorArr['remark']      = "终端名称：" . $this->getPosName($relationId);
            $res                        = $this->addBehaviorData($memberId, $nodeId, '', '', '', '', 1);
            if ($res === false) {
                log_write("新增行为数据失败！");

                return false;
            }
        }
        // type=11 付现金增加积分
        if ($type == 11) {
            $behaviorArr['change_num']  = $point;
            $behaviorArr['relation_id'] = $relationId;
            $behaviorArr['remark']      = "支付现金:" . $amt;
            $res                        = $this->addBehaviorData($memberId, $nodeId, '', '', '', '', 1);
            if ($res === false) {
                log_write("新增行为数据失败！");

                return false;
            }
        }
        // type=12线下兑换扣减积分
        if ($type == 12) {
            $behaviorArr['change_num']  = $point;
            $behaviorArr['relation_id'] = $relationId;
            $behaviorArr['remark']      = "终端名称：" . $this->getPosName($relationId);
        }
        // type=13关注微信公众号
        if ($type == 13) {
            $behaviorArr['change_num'] = $point;
            $behaviorArr['remark']     = "关注微信公众号";
        }
        // type=14红包
        if ($type == 14) {
            $bounsArr                   = $this->getBounsName($relationId);
            $behaviorArr['relation_id'] = $relationId;
            $behaviorArr['remark']      = "红包名称:" . $bounsArr['bonus_page_name'] . "红包金额：" . $bounsArr['bonus_amount'];
        }
        // type15活动中奖并中积分奖品
        if ($type == 15) {
            log_write("积分奖品!进来了？yyyyy");
            $behaviorArr['relation_id'] = $relationId;
            $behaviorArr['change_num']  = $point;
            $behaviorArr['remark']      = "活动名称：" . $this->getActivityName($relationId);
            if ($res === false) {
                log_write("新增行为数据失败！");

                return false;
            }
        }
        // type16参与富媒体营销活动
        if ($type == 16) {
            $behaviorArr['relation_id'] = $relationId;
            $behaviorArr['change_num']  = $point;
            $behaviorArr['remark']      = "活动名称：" . $this->getActivityName($relationId);
            if ($res === false) {
                log_write("新增行为数据失败！");

                return false;
            }
        }
        $res = M("tintegral_behavior_trace")->add($behaviorArr);
        log_write("行为流水sql:".M()->getLastSql());
        if ($res === false) {
            log_write("记录行为数据失败！" . print_r($behaviorArr, true));

            return false;
        }

        return true;
    }

    public function addBehaviorData(
            $memberId,
            $nodeId,
            $joinTotal = 0,
            $sendTotal = 0,
            $verifyTotal = 0,
            $shopTotal = 0,
            $shopLineTotal = 0
    ) {
        if (empty($memberId) || empty($nodeId)) {
            log_write($memberId . "会员ID或者nodeId为空！");

            return false;
        }
        $BehaviorInfo = M("tmember_activity_total")->where(array(
                'node_id'   => $nodeId,
                'member_id' => $memberId,
        ))->find();
        $BehaviorData = array(
                'node_id'        => $nodeId,
                'join_total'     => $joinTotal,
                'member_id'      => $memberId,
                'send_total'     => $sendTotal,
                'verify_total'   => $verifyTotal,
                'shop_total'     => $shopTotal,
                'shopline_total' => $shopLineTotal,
                'trans_date'     => date('YmdHis'),
        );
        if (empty($BehaviorInfo)) {
            // 做新增处理
            $res = M("tmember_activity_total")->add($BehaviorData);
        } else {
            // 做更新处理
            $res = $this->_updateBehavior($BehaviorData, $BehaviorInfo);
        }
        if ($res === false) {
            log_write($memberId . "新增或者更新行为数据失败！");

            return false;
        }

        return true;
    }

    // 新增个人行为数据
    public function _addNewBehavior($BehaviorData) {
        $activityInfo = $this->behaviorDataMerge($BehaviorData['member_id'], $BehaviorData['node_id']);
        if ($activityInfo) {
            if ($activityInfo['join_total']) {
                $BehaviorData['join_total'] = $BehaviorData['join_total'] + $activityInfo['join_total'];
            }
            if ($activityInfo['send_total']) {
                $BehaviorData['send_total'] = $BehaviorData['send_total'] + $activityInfo['send_total'];
            }
            if ($activityInfo['verify_total']) {
                $BehaviorData['verify_total'] = $BehaviorData['verify_total'] + $activityInfo['verify_total'];
            }
            $BehaviorData['status'] = 2;
        }
        $BehaviorData['trans_date'] = date('YmdHis');
        $res                        = M("tmember_activity_total")->add($BehaviorData);
        if ($res === false) {
            log_write($BehaviorData['member_id'] . "新增行为数据失败！");

            return false;
        }

        return true;
    }

    // 更新用户行为数据
    public function _updateBehavior($BehaviorData, $BehaviorInfo) {
        if ($BehaviorData['join_total']) {
            $data['join_total'] = array(
                    'exp',
                    'join_total+1',
            );
        }
        if ($BehaviorData['send_total']) {
            $data['send_total'] = array(
                    'exp',
                    'send_total+1',
            );
        }
        if ($BehaviorData['verify_total']) {
            $data['verify_total'] = array(
                    'exp',
                    'verify_total+1',
            );
        }
        if ($BehaviorData['shop_total']) {
            $data['shop_total'] = array(
                    'exp',
                    'shop_total+1',
            );
        }
        if ($BehaviorData['shopline_total']) {
            $data['shopline_total'] = array(
                    'exp',
                    'shopline_total+1',
            );
        }
        $res = M("tmember_activity_total")->where(array(
                'member_id' => $BehaviorInfo['member_id'],
        ))->save($data);
        if ($res === false) {
            log_write($BehaviorData['member_id'] . "新增行为数据失败！");

            return false;
        }

        return true;
    }

    // 数据合并
    public function behaviorDataMerge($memberId, $nodeId) {
        // 判断是否有行为数据
        $map       = array(
                'node_id'   => $nodeId,
                'member_id' => $memberId,
                'status'    => 1,
        );
        $mergeFlag = M("tmember_activity_total")->where($map)->find();
        if ($mergeFlag) {
            $activityInfo = M("tmember_activity_stat")->where(array(
                    'node_id'   => $nodeId,
                    'member_id' => $memberId,
            ))->getField('sum(join_cnt) join_total,sum(send_cnt) send_total,sum(verify_cnt) verify_total,sum(shop_cnt)');
            if ($activityInfo) {
                return $activityInfo;
            }
        }
    }

    // 通过m_id获取活动名称
    public function getActivityName($mId) {
        $activityName = M("tmarketing_info")->where(array(
                'id' => $mId,
        ))->getField('name');
        if ($activityName) {
            return $activityName;
        }
    }

    public function getPosName($posId) {
        $posName = M("tpos_info")->where(array(
                'pos_id' => $posId,
        ))->getField('pos_name');
        if ($posName) {
            return $posName;
        }
    }

    public function getBounsName($id) {
        $getBonusName = M()->table("tbonus_use_detail a")->join('tbonus_info b on b.id=a.bonus_id')->where(array(
                'a.id' => $id,
        ))->getField('b.bonus_page_name,b.bonus_amount');
        if (!$getBonusName) {
            log_write("查询不到红包名称！红包id：" . $id);
        }

        return $getBonusName;
    }
}