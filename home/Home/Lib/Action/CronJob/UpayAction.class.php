<?php

class UpayAction extends Action {

    public function send() {
        // 获取所有的待转账的订单
        $list = M('ttransfer_trace')->where(
            array(
                'status' => '1'))->select();
        $Ump = D('Ump', 'Service');
        if (! empty($list)) {
            foreach ($list as $k => $v) {
                $arrRet = $Ump->transferAccount($v);
                log_write(
                    '订单号：' . $v['order_id'] . '转账返回的数据为' . print_r($arrRet, 
                        true));
                
                if ($arrRet['ret_code'] == '0000') {
                    $data = array();
                    $data['fee'] = $arrRet['fee'];
                    $data['fee_type'] = $arrRet['fee_type'];
                    $data['status'] = 2;
                    $data['ret_code'] = $arrRet['ret_code'];
                    $data['ret_msg'] = $arrRet['ret_msg'];
                    $data['trade_no'] = $arrRet['trade_no'];
                    $data['trade_state'] = $arrRet['trade_state'];
                    M('ttransfer_trace')->where(
                        array(
                            'order_id' => $v['order_id']))->save($data);
                } else {
                    $accountInfo = M()->table('tnode_cash_trace o')->join(
                        'tnode_info n ON o.node_id = n.node_id')
                        ->join(
                        'ttransfer_trace f ON f.order_id = o.pay_order_id')
                        ->join('tnode_cash c on c.node_id = o.node_id')
                        ->field(
                        'o.fee, n.client_id,n.node_name,c.account_bank,c.account_name')
                        ->where(
                        array(
                            'o.pay_order_id' => $v['order_id']))
                        ->find();
                    
                    // 提现订单修改状态 失败
                    $title = "【警告】今天有一笔提现申请转账失败！";
                    $content = "您有一笔提现申请转账失败，详细内容如下<br />";
                    $content .= "提现申请号：{$v['order_id']}<br />";
                    $content .= "U付返回流水号：{$data['trade_no']}<br />";
                    $content .= "申请提现金额：" . $v['amount'] + $v['fee'] . "<br />";
                    $content .= "手续费：{$accountInfo['fee']}<br />";
                    $content .= "实际打款金额：{$v['amount']}<br />";
                    $content .= "收款账号：{$v['recv_account']}<br />";
                    $content .= "收款人姓名：{$accountInfo['account_name']}<br />";
                    $content .= "收款银行：{$accountInfo['account_bank']}<br />";
                    $content .= "收款银行支行：{$v['bank_brhname']}<br />";
                    $content .= "企业旺号：{$accountInfo['client_id']}<br />";
                    $content .= "商户名称：{$accountInfo['node_name']}<br />";
                    $content .= "申请日期：" . date('Y年m月d日 H:i:s') . "<br />";
                    $content .= "提现状态：提交U付转账失败<br />";
                    $content .= "请有关人员及时处理，以避免照成不必要的纠纷。<br />";
                    self::sendNotice($v['order_id'], $arrRet, $content, $title);
                    M('ttransfer_trace')->where(
                        array(
                            'order_id' => $v['order_id']))->save(
                        array(
                            'status' => 4));
                    M('tnode_cash_trace')->where(
                        array(
                            'pay_order_id' => $v['order_id']))->save(
                        array(
                            'trans_status' => 1));
                }
            }
        }
    }

    public function recv() {
        $mer_id = I('get.mer_id');
        $sign_type = I('get.sign_type');
        $sign = I('get.sign');
        $version = I('get.version');
        $order_id = I('get.order_id');
        $mer_date = I('get.mer_date');
        $ret_code = I('get.ret_code');
        $ret_msg = I('get.ret_msg');
        $trade_no = I('get.trade_no');
        $trade_state = I('get.trade_state');
        $amount = I('get.amount');
        $transfer_settle_date = I('get.transfer_settle_date');
        $transfer_date = I('get.transfer_date');
        $content = "";
        log_write(
            '订单号：' . $order_id . '在U付异步通知过来的数据为' . print_r(I('get.'), true));
        if ($ret_code == '0000') {
            M('ttransfer_trace')->where(
                array(
                    'order_id' => $order_id))->save(
                array(
                    'status' => 3));
            // 提现订单修改状态 成功
            M('tnode_cash_trace')->where(
                array(
                    'pay_order_id' => $order_id))->save(
                array(
                    'trans_status' => 5));
            M('ttg_order_info')->where(
                array(
                    'order_id' => $order_id))->save(
                array(
                    'extract_status' => 2));
        } else {
            $tmpInfo = array(
                'ret_code' => $ret_code, 
                'mer_date' => $mer_date, 
                'order_id' => $order_id, 
                'mer_id' => $mer_id, 
                'ret_msg' => $ret_msg, 
                'sign' => $sign);
            $accountInfo = M()->table("tnode_cash_trace o")->join(
                'tnode_info n ON o.node_id = n.node_id')
                ->join('ttransfer_trace f ON f.order_id = o.pay_order_id')
                ->join('tnode_cash c on c.node_id = o.node_id')
                ->field(
                'f.*, o.fee, n.client_id,n.node_name,c.account_bank,c.account_name')
                ->where(
                array(
                    'o.pay_order_id' => $order_id))
                ->find();
            if ($accountInfo) {
                // 提现订单修改状态 失败
                $title = "【警告】今天有一笔提现申请转账处理失败！";
                $sendInfo = "您有一笔提现申请转账失败，详细内容如下<br />";
                $sendInfo .= "提现申请号：{$order_id}<br />";
                $sendInfo .= "U付返回流水号：{$trade_no}<br />";
                $sendInfo .= "申请提现金额：" . $accountInfo['amount'] + $v['fee'] .
                     "<br />";
                $sendInfo .= "手续费：{$accountInfo['fee']}<br />";
                $sendInfo .= "实际打款金额：{$accountInfo['amount']}<br />";
                $sendInfo .= "收款账号：{$accountInfo['recv_account']}<br />";
                $sendInfo .= "收款人姓名：{$accountInfo['account_name']}<br />";
                $sendInfo .= "收款银行：{$accountInfo['account_bank']}<br />";
                $sendInfo .= "收款银行支行：{$accountInfo['bank_brhname']}<br />";
                $sendInfo .= "企业旺号：{$accountInfo['client_id']}<br />";
                $sendInfo .= "商户名称：{$accountInfo['node_name']}<br />";
                $sendInfo .= "银行返回日期：" . date('Y年m月d日 H:i:s') . "<br />";
                $sendInfo .= "提现状态：提交银行返回转账信息失败<br />";
                $sendInfo .= "请有关人员及时处理，以避免照成不必要的纠纷。<br />";
                
                self::sendNotice($order_id, $tmpInfo, $sendInfo, $title);
                M('ttransfer_trace')->where(
                    array(
                        'order_id' => $order_id))->save(
                    array(
                        'status' => 4));
                // 提现订单修改状态 失败
                M('tnode_cash_trace')->where(
                    array(
                        'pay_order_id' => $order_id))->save(
                    array(
                        'trans_status' => 1));
            }
        }
        $content .= 'mer_date=' . $mer_date;
        $content .= 'mer_id=' . $mer_id;
        $content .= 'order_id=' . $order_id;
        $content .= 'ret_code=' . $ret_code;
        $content .= 'ret_msg=' . $ret_msg;
        $content .= 'sign_type=' . $sign_type;
        $content .= 'version=' . $version;
        $content .= 'sign=' . $sign;
        echo '<METANAME="MobilePayPlatform" CONTENT="' . $content . '"/>';
    }

    private function sendNotice($orderId, $returnInfo, $sendInfo = null, 
        $title = null) {
        $info = M('ttransfer_trace')->where(
            array(
                'order_id' => $orderId))->find();
        $pri_file = date('ymd') . '0001';
        $cols_arr = array(
            'id' => 'ID', 
            'account_name' => '商户流水号', 
            'cash_money' => '提现金额', 
            'bank_type' => '账户类型', 
            'account_no' => '收款帐号', 
            'account_bank_ex' => '支行信息', 
            'bank_code' => '银行编码');
        querydata_download($listInfo, $cols_arr, null, null, $pri_file, true);
        $msg = '自动提现失败，订单号：' . $orderId . '转账失败,失败原因:' . json_encode(
            $returnInfo);
        $content['petname'] = C('mailto.sendOne');
        $content['CC'] = C('mailto.notice');
        // $content['CC'] =
        // 'yangxin@imageco.com.cn;lutt@imageco.com.cn;zengc@imageco.com.cn;zhenggf@imageco.com.cn;wangjin@imageco.com.cn;liujl@imageco.com.cn';
        
        if (null === $sendInfo) {
            $sendInfo = "自动提现失败：" . date('Y-m-d H:i:s') . "订单号:" . $orderId .
                 "转账失败,失败原因请查看日志";
        }
        if (null === $title) {
            $title = "自动提现失败：" . date('Y-m-d H:i:s') . "订单号:" . $orderId .
                 "转账失败,失败原因请查看日志";
        }
        $content['test_title'] = $title;
        $content['text_content'] = $sendInfo;
        // $content['add_file'] = $this->file_path.$pub_file;
        // $content['add_file'] =
        // array($this->file_path.$pub_file,$this->file_path.$pri_file);
        $rs = to_email($content);
        log_write(
            "send mail to " . $content['petname'] . " CC to " . $content['CC'] .
                 " result[" . $rs . "]");
        log_write($msg, 'error');
    }
}
