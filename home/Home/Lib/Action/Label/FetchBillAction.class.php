<?php
// Q币/话费领取
class FetchBillAction extends Action {
    // 初始化
    public function _initialize() {
    }

    public function index() {
        $id_str = I('id_str', null);
        if (! $id_str)
            $this->msg(0, '数据错误，无法领取');
        $info = M('tphone_bills_trace')->where(
            array(
                'id_str' => $id_str))->find();
        if (! $info)
            $this->msg(0, '数据错误，无法领取');
        if ($info['end_time'] < date('YmdHis'))
            $this->msg(0, '该商品已过领取时间，无法领取');
        if ($info['status'] != '0')
            $this->msg(0, '该商品已经被领取过，无法再次领取');
            // 更新为已点击
        if ($info['click_flag'] == '0' || ! $info['click_flag'])
            M('tphone_bills_trace')->where(
                array(
                    'id_str' => $id_str))->save(
                array(
                    'click_flag' => 1));
        
        $this->assign('info', $info);
        $this->display(); // 输出模板
    }
    
    // 领取
    public function Submit() {
        $id_str = I('id_str', null);
        if (! $id_str)
            $this->msg(0, '数据错误，无法领取');
        $org_number = I('org_number', null);
        if (! $org_number)
            $this->msg(0, '中奖手机号不能为空');
        $model = M('tphone_bills_trace');
        $info = $model->where(array(
            'id_str' => $id_str))->find();
        if (! $info)
            $this->msg(0, '数据错误，无法领取');
        if ($info['status'] != '0')
            $this->msg(0, '该商品已经被领取过，无法再次领取');
        if ($org_number != substr($info['org_phone_no'], - 4))
            $this->msg(0, '输入的中奖手机号后4位不正确，请确认后重新输入');
        
        $charge_type = I('sc-2', 0, 'intval');
        // 话费给本机充
        if ($info['recharge_type'] == '0' && $charge_type == '1') {
            $recharge_number = $info['org_phone_no'];
        } else {
            // 获取充值号码
            $recharge_number = I('recharge_number', null);
            $recharge_number2 = I('recharge_number2', null);
            if ($recharge_number != $recharge_number2)
                $this->msg(0, '2次输入帐号不一致');
        }
        // 领取
        $data_arr = array(
            'recharge_number' => $recharge_number, 
            'apply_recharge_time' => date('YmdHis'), 
            'status' => '1');
        $result = $model->where(
            array(
                'id_str' => $id_str, 
                'status' => '0'))->save($data_arr);
        if ($result === false)
            $this->msg(0, '领取失败');
            // 邮件推送
        $sendTime = date('Y-m-d H:i:s');
        $g_name = M('tgoods_info')->where(
            array(
                'id' => $info['g_id']))->getField('goods_name');
        $type_name = $info['recharge_type'] == '1' ? 'Q币' : '话费';
        $content = "申请时间：{$sendTime}<br/>商户号：{$info['node_id']}<br>卡券：{$g_name}<br/>待充类型：{$type_name}<br/>待充手机号/Q号：{$recharge_number}<br/>待充金额：{$info['amount']}<br/>";
        $ps = array(
            "subject" => "Q币话费充值", 
            "content" => $content, 
            "email" => "qianwen@imageco.com.cn,chensf@imageco.com.cn,zhengxh@imageco.com.cn");
        $resp = send_mail($ps);
        if ($resp['sucess'] == '1') {
            log_write('推送话费Q币领取邮件成功:' . $content);
        } else {
            log_write('推送话费Q币领取邮件失败:' . $content);
        }
        // 领取成功
        $this->msg(1, '领取成功', $info['recharge_type']);
        exit();
    }

    /*
     * 信息提示页面 status 0 失败 1成功 info 提示信息 领取类型 0-话费 1-Q币
     */
    protected function msg($status, $info, $type = null) {
        $this->assign('status', $status);
        $this->assign('info', $info);
        $this->assign('type', $type);
        $this->display('msg');
        exit();
    }
}