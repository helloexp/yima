<?php

/**
 * Created by ys. User: Administrator Date: 2015/10/29 Time: 16:46
 */
class IntegralConfigNodeModel extends BaseModel {
    protected $tableName = '__NONE__';
    public function checkIntegralConfig($nodeId,$type=0) {
        $mes = array();
        $mes[0] = true;
        if (get_wc_version($nodeId) != 'v4') {
            $powers = M("tnode_info")->where(
                    array(
                            'node_id' => $nodeId))->getField('pay_module');
            if (empty($powers)) {
                $mes[0] = false;
                $mes[1] = '商户'.$nodeId.'无任何权限';
                if($type == 1){
                    return $mes;
                }else{
                    return $mes[0];
                }
            }
            $powers = explode(",", $powers);
            if (! in_array("m4", $powers)) {
                $mes[0] = false;
                $mes[1] = '商户'.$nodeId.'无m4权限';
                if($type == 1){
                    return $mes;
                }else{
                    return $mes[0];
                }
            }
        }
        $integralActive = M("tmarketing_info")->where(
                array(
                        'node_id' => $nodeId,
                        'batch_type' => CommonConst::BATCH_TYPE_INTEGRAL))->find();
        if (empty($integralActive)) {
            $mes[0] = false;
            $mes[1] = '商户'.$nodeId.'有m4权限,或者演示帐号，但是无活动！';
            if($type == 1){
                return $mes;
            }else{
                return $mes[0];
            }
        }
        $integralConfig = M("tintegral_node_config")->where(
                array(
                        'node_id' => $nodeId))->find();
        if (empty($integralConfig)) {
            $mes[0] = false;
            $mes[1] = '商户'.$nodeId.'有m4,并且有活动，但是未配置参数！';
            if($type == 1){
                return $mes;
            }else{
                return $mes[0];
            }
        }
        $mes[1] = $integralConfig;
        if($type == 1){
            return $mes;
        }else{
            return $mes[1];
        }
    }

    public function getIntegralName($nodeId) {
        $integralName = M("tintegral_node_config")->where(
            array(
                'node_id' => $nodeId))->getField('integral_name');
        if ($integralName) {
            return $integralName;
        }
    }
    // 判断openid是否为会员，如果不为会员应该提示绑定
    //type=1微信type=3支付宝
    public function checkMember($node_id, $openid ,$type) {
        $map=array(
                'a.node_id' => $node_id,
                'a.account'=>$openid
        );
        if($type==1){
            $map['a.type']='0';
        }elseif($type==3){
            $map['a.type']='1';
        }
        $isMember = M()->table("tmember_account a")
                ->join("tmember_info b on b.id=a.member_id")
                ->Field('b.*')
                ->where($map)->find();
        if (empty($isMember)) {
            $IntegralArr['StatusCode'] = '2003';
            $IntegralArr['StatusText'] = '请绑定';
            return $IntegralArr;
        }
        return $isMember;
    }
    // 判断该机构是否开通积分营销
    public function checkIntegralConfigOne($node_id) {
        $integralConfig = D('IntegralConfigNode')->checkIntegralConfig($node_id);
        if ($integralConfig != false) {
            return $integralConfig;
        }
    }
    // 做积分操作
    public function addIntegralPoint($integralConfig, $order_id, $amt, $node_id, 
        $MemberId) {
        if ($integralConfig['weixin_payment_flag'] == '1' &&
             $integralConfig['weixin_payment_rate'] > 0) {
            $change_num = intval($amt * $integralConfig['weixin_payment_rate']);
            if ($integralConfig['one_weixin_flag'] == 1) {
                if ($change_num > $integralConfig['one_weixin_payment_rate']) {
                    $change_num = $integralConfig['one_weixin_payment_rate'];
                }
            }
            $res = D("IntegralPointTrace")->integralPointChange('10', 
                $change_num, $MemberId, $node_id, $order_id, '');
            if ($res === false) {
                $IntegralArr['StatusCode'] = '2001';
                $IntegralArr['StatusText'] = '微信支付积分失败';
                return $IntegralArr;
            }
            // 增加积分成功
            return $res;
        } else {
            // 未开通积分营销
            $IntegralArr['StatusCode'] = '2014';
            $IntegralArr['StatusText'] = '该商户未开启微信支付反积分参数';
            return $IntegralArr;
        }
    }
    // 增加行为数据
    public function addBehaviorData($memberId, $node_id, $change_num, $relationId, $amt, $type) {
        $behaviorRes = D("MemberBehavior")->addBehaviorType($memberId, $node_id, $type, $change_num, $relationId, $amt);
        if ($behaviorRes === false) {
            return false;
            log_write("新增行为数据失败,member_id" . $memberId);
        }
        return true;
    }
    // 增加行为数据
    public function addBehaviorDataPoint($memberId, $node_id, $change_num, $amt) {
        $behaviorRes = D("MemberBehavior")->addBehaviorType($memberId, $node_id, 
            10, $change_num, $amt);
        if ($behaviorRes === false) {
            return false;
            log_write("新增行为数据失败,member_id" . $memberId);
        }
        return true;
    }

    public function _addIntegralWeiXinOrder($integralOrderArr, $order_id, 
        $node_id) {
        $data = array(
            'order_id' => $order_id, 
            'node_id' => $node_id, 
            'status_code' => $integralOrderArr['StatusCode'],
            'status_text' => $integralOrderArr['StatusText'],
            'integral_name' => $integralOrderArr['IntegralName'],
            'member_id' => $integralOrderArr['MemberId'],
            'member_name' => $integralOrderArr['MemberName'],
            'member_phone' => $integralOrderArr['MemberPhone'],
            'integral_change_number' => $integralOrderArr['IntegralChangeNumber'],
            'residual_integral' => $integralOrderArr['ResidualIntegral'],
            'trading_type' => $integralOrderArr['TradingType'],
            'card_type' => $integralOrderArr['CardType'],
            'amt' => $integralOrderArr['amt'], 
            'add_time' => date('YmdHis'));
        $res = M("tintegral_weixin_order")->add($data);
        if ($res === false) {
            return false;
        }
    }
}