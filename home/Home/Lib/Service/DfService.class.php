<?php

/**
 * df非标逻辑 User: kk
 */
class DfService {

    const SYS_PARAM_RATE = 'DF_MONEY_TO_POINT';

    /**
     * 订单支付后，判断用户类型，处理积分增加
     */
    public function pay_notify($order_id, $order_phone, $order_amt, $orderInfo) {
        $df_rate = M('tsystem_param')->where(
            "param_name = '" . self::SYS_PARAM_RATE . "'")->getField(
            'param_value');
        if (! $df_rate) {
            log_write('DF订单支付成功，但未设置金额转换成积分的比率，暂无处理！订单号：[' . $order_id . ']');
            return;
        }
        $memberInfo = M('tfb_df_member')->where("mobile = '{$order_phone}'")
            ->lock(true)
            ->find();
        if ($memberInfo) {
            $openid = $memberInfo['openid'];
            $change_num = intval($order_amt * $df_rate);
            $data = array(
                'openid' => $openid, 
                'type' => '1', 
                'before_num' => $memberInfo['point'], 
                'change_num' => $change_num, 
                'after_num' => $memberInfo['point'] + $change_num, 
                'relation_id' => $order_id, 
                'trace_time' => date('YmdHis'), 
                'amount' => $order_amt, 
                'remark' => "微信小店购买商品兑换积分");
            $flag = M('tfb_df_point_trace')->add($data);
            if ($flag === false) {
                log_write('DF订单支付成功，积分流水处理失败！参数：[' . print_r($data, true) . ']');
                return;
            }
            $data = array(
                'point' => array(
                    'exp', 
                    "point + {$change_num}"));
            $flag = M('tfb_df_member')->where("mobile = '{$order_phone}'")->save(
                $data);
            if ($flag === false) {
                log_write(
                    'DF订单支付成功，积分流水处理失败！参数：[' . print_r($data, true) . M()->_sql() .
                         ']');
                return;
            }
        }
    }

    /**
     * 绑定手机号
     */
    public function bind_phone($member_id, $mobile) {
    }
}