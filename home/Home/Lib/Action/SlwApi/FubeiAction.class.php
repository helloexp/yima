<?php

class FubeiAction extends Action {
    public $responseDesc = array(
        '0'    => '处理成功',
        '1001' => '请求ip拒绝接入',
        '1002' => '缺少参数',
        '1003' => '签名错误',
        '1004' => '非Post请求',
        '1005' => '系统错误',
    );
    public function index() {
        log_write('发起请求');
        if (IS_POST) {
            log_write('请求数据data:' . file_get_contents('php://input'));
            $post_data = json_decode(file_get_contents('php://input'), true);
            $sign      = $post_data['sign'];
            $data      = $post_data['data'];
            ksort($data);
            /*校验ip*/
            /*if ($this->getIP() !== C('slw.api_ip')) {
            log_write('非法ip:'.$sign.'------'.print_r($data, true));
            $this->notifyreturn('1001');
            }*/
            /*校验token*/
            $data_str = http_build_query($data);
            $data_str .= '&token=' . C('slw.token');
            $data_str_md5 = md5(urldecode($data_str));

            /*if ($sign != $data_str_md5) {
            log_write('签名错误:' . $sign . '------' . print_r($data, true));
            log_write('data_str:' . $data_str);
            log_write('data_str_md5:' . $data_str_md5);
            $this->notifyreturn('1003');
            }*/

            /*校验必填参数*/
            $data_key = array(
                'pos_id',
                'out_trace_no',
                'trade_no',
                'trade_status',
                'seller_id',
                'seller_mail',
                'total_amount',
                'receipt_amount',
                'invoice_amount',
                'buyer_pay_amount',
                'gmt_create',
                'gmt_payment',
            );
            foreach ($data_key as $key) {
                if (!isset($data[$key]) || strlen($data[$key]) == 0) {
                    log_write('缺少参数:' . $key . '------' . print_r($data, true));
                    $this->notifyreturn('1002');
                }
            }
            $pay_notifyreturn = false;
            $pos_seq          = '';
            $temp_data        = M()->query("SELECT CONCAT('F', _nextval('slw_fb_pos_seq')) as pos_seq FROM DUAL");
            if (!$temp_data) {
                log_write('slw_fb_pos_seq fail!' . print_r($data, true));
                $this->notifyreturn('1005');

            } else {
                $pos_seq = $temp_data[0]['pos_seq'];
            }
            $data['pos_seq'] = $pos_seq;

            if ($data['trade_status'] == 'TRADE_SUCCESS') {
                $pay_notifyreturn = $this->pay_notifyreturn($data);

            }
            if ($data['trade_status'] == 'TRADE_REFUND') {
                $pay_notifyreturn = $this->pay_r_notifyreturn($data);

            }

            if ($pay_notifyreturn) {
                if ((int) M('tfb_slw_fubei_trace')->where(array('out_trace_no' => $data['out_trace_no']))->count() > 0) {
                    $res = M('tfb_slw_fubei_trace')->where(array('out_trace_no' => $data['out_trace_no']))->data($data)->save();
                    log_write('更新数据:' . print_r($data, true));
                } else {
                    $res = M('tfb_slw_fubei_trace')->add($data);
                    log_write('新增数据:' . print_r($data, true));
                }
            }

            if ($res === false) {
                log_write('系统错误:' . print_r($data, true));
                $this->notifyreturn('1005');
            } else {
                $this->notifyreturn('0');
            }
        } else {
            log_write('非Post请求');
            $this->notifyreturn('1004');
        }
    }
    // 通知应答
    private function notifyreturn($errno = '0') {
        $meta = array('meta' => array('errno' => $errno, 'msg' => $this->responseDesc[$errno]));
        echo json_encode($meta);
        log_write(json_encode($meta), 'RESPONSE');
        exit();
    }
    private function getIP() {
        static $realip;
        if (isset($_SERVER)) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $realip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $realip = $_SERVER['HTTP_CLIENT_IP'];
            } else {
                $realip = $_SERVER['REMOTE_ADDR'];
            }
        } else {
            if (getenv('HTTP_X_FORWARDED_FOR')) {
                $realip = getenv('HTTP_X_FORWARDED_FOR');
            } elseif (getenv('HTTP_CLIENT_IP')) {
                $realip = getenv('HTTP_CLIENT_IP');
            } else {
                $realip = getenv('REMOTE_ADDR');
            }
        }

        return $realip;
    }
    // 支付线下支付流水同步应答处理
    private function pay_notifyreturn($verify_info) {
        $where    = "pos_id ='" . $verify_info['pos_id'] . "'  ";
        $pos_info = M('TposInfo')->where($where)->find();
        if (!$pos_info) {
            $this->log("未找到终端[" . $verify_info['pos_id'] . "]");
            return false;
        }
        $pos_trace                 = array();
        $pos_trace['exchange_amt'] = $verify_info['total_amount'];
        $pos_trace['status']       = 0;
        $pos_trace['trans_time']   = date('YmdHis', strtotime($verify_info['gmt_create']));
        /*$pos_trace['ret_code']           = $verify_info['SysRetCode'];
        $pos_trace['ret_desc']           = $verify_info['SysRetDesc'];*/
        $pos_trace['user_name']          = $verify_info['buyer_email']; // 支付宝买家账号
        $pos_trace['zfb_buyer_logon_id'] = $verify_info['buyer_id']; // 支付宝买家用户号
        $pos_trace['zfb_trade_no']       = $verify_info['trade_no']; // 支付宝流水号
        $pos_trace['zfb_out_trade_no']   = $verify_info['out_trace_no']; // 支付宝商户订单号
        //$pos_trace['zfb_out_pos_seq']    = $verify_info['OutPosSeq']; // 支付宝外部商户流水号
        //$pos_trace['zfb_frate']          = $verify_info['Frate']; // 费率
        $pos_trace['zfb_coupon_fee'] = $verify_info['coupon_amount']; // 支付红包
        $this->log('CouponFee=[' . $verify_info['coupon_amount'] . ']');

        $pos_trace['mcard_fee']     = $verify_info['mcard_amount']; // 商户店铺卡金额
        $pos_trace['mdiscount_fee'] = $verify_info['mdiscount_amount']; // 商户优惠券金额
        $pos_trace['mcoupon_fee']   = $verify_info['mcoupon_amount']; // 商户红包金额
        $pos_trace['point_fee']     = $verify_info['point_amount']; // 支付宝积分金额
        $pos_trace['discount_fee']  = $verify_info['discount_amount']; // 支付宝折扣券金额

        $pos_trace['code_type'] = 100; //验证类型 100:支付宝 101:微信
        //$pos_trace['syn_seq']   = $verify_info['ReqSeq'];
        $pos_trace['fee_amt']  = $verify_info['alipay_fee']; //业务手续费（支付宝线下支付）
        $pos_trace['real_amt'] = $verify_info['receipt_amount_ex_fee']; //实收金额

        $pos_trace['pos_id']       = $verify_info['pos_id'];
        $pos_trace['pos_seq']      = $verify_info['pos_seq'];
        $pos_trace['trans_type']   = 'T';
        $pos_trace['is_canceled']  = '0';
        $pos_trace['user_id']      = '00000000'; // epos,68同步的user_id记录00000000
        $pos_trace['node_id']      = $pos_info['node_id'];
        $pos_trace['store_id']     = $pos_info['store_id'];
        $pos_trace['shop_account'] = $verify_info['seller_mail'];

        // 查看是否存在源流水
        $where         = "trans_type = 'T' and zfb_out_trade_no = '" . $verify_info['out_trace_no'] . "'";
        $ori_pos_trace = M('tzfb_offline_pay_trace')->where($where)->find();
        if (!$ori_pos_trace) {
            // 记流水
            $rs = M('tzfb_offline_pay_trace')->Add($pos_trace);
            if (!$rs) {
                $this->log("保存流水失败[tzfb_offline_pay_trace]失败" . M()->_sql());
                return false;
            }
        } else {
            //存在折更新流水
            $rs = M('tzfb_offline_pay_trace')->where($where)->save($pos_trace);
            if ($rs === false) {
                $this->log("保存流水失败[tzfb_offline_pay_trace]失败");
                return false;
            }
        }
        return true;
    }
    // 支付线下支付流水同步应答处理-退款
    private function pay_r_notifyreturn($verify_info) {
        $where    = "pos_id ='" . $verify_info['pos_id'] . "'  ";
        $pos_info = M('TposInfo')->where($where)->find();
        if (!$pos_info) {
            $this->log("未找到终端[" . $verify_info['pos_id'] . "]");
            return false;
        }

        if (empty($verify_info['out_trace_no'])) {
            $this->log("未找到原流水[" . $verify_info['out_trace_no'] . "]");
            return false;
        }

        $where         = "trans_type = 'T' and zfb_out_trade_no = '" . $verify_info['out_trace_no'] . "'";
        $ori_pos_trace = M('tzfb_offline_pay_trace')->where($where)->find();
        if (!$ori_pos_trace) {
            $this->log("未找到源流水[" . $verify_info['out_trace_no'] . "]");
            return false;
        }
        // 交易成功需要更新源流水的状态
        // 更新源流水
        $new_pos_trace['is_canceled']    = '1';
        $new_pos_trace['cancel_pos_seq'] = $verify_info['out_trace_no'];
        M('tzfb_offline_pay_trace')->where($where)->save($new_pos_trace);

        // 记流水
        $pos_trace                 = array();
        $pos_trace['pos_id']       = $verify_info['pos_id'];
        $pos_trace['pos_seq']      = $verify_info['pos_seq'];
        $pos_trace['trans_type']   = 'R';
        $pos_trace['status']       = '0';
        $pos_trace['is_canceled']  = '0';
        $pos_trace['exchange_amt'] = $verify_info['total_amount'];
        $pos_trace['trans_time']   = date('YmdHis', strtotime($verify_info['gmt_create']));
        //$pos_trace['ret_code']           = $verify_info['SysRetCode'];
        //$pos_trace['ret_desc']           = $verify_info['SysRetDesc'];
        $pos_trace['user_id']            = '00000000'; // epos,68同步的user_id记录00000000
        $pos_trace['user_name']          = $verify_info['buyer_email']; // 支付宝买家账号
        $pos_trace['zfb_buyer_logon_id'] = $verify_info['buyer_id']; // 支付宝买家用户号
        $pos_trace['zfb_trade_no']       = $verify_info['trade_no']; // 支付宝流水号
        $pos_trace['zfb_out_trade_no']   = $verify_info['out_trace_no']; // 支付宝商户订单号
        //$pos_trace['zfb_out_pos_seq']    = $verify_info['OutPosSeq']; // 支付宝外部商户流水号
        //$pos_trace['zfb_frate']          = $verify_info['Frate']; // 费率
        $pos_trace['zfb_coupon_fee'] = $verify_info['coupon_amount']; // 退款红包

        $pos_trace['mcard_fee']     = $verify_info['mcard_amount']; // 商户店铺卡金额
        $pos_trace['mdiscount_fee'] = $verify_info['mdiscount_amount']; // 商户优惠券金额
        $pos_trace['mcoupon_fee']   = $verify_info['mcoupon_amount']; // 商户红包金额
        $pos_trace['point_fee']     = $verify_info['point_amount']; // 支付宝积分金额
        $pos_trace['discount_fee']  = $verify_info['discount_amount']; // 支付宝折扣券金额

        $pos_trace['code_type'] = 100;
        //$pos_trace['syn_seq']    = $verify_info['ReqSeq'];
        $pos_trace['fee_amt']  = $verify_info['alipay_fee'];
        $pos_trace['real_amt'] = '-' . $verify_info['receipt_amount_ex_fee'];

        $pos_trace['org_posseq']   = 'FB' . $verify_info['out_trace_no'] . '0';
        $pos_trace['node_id']      = $pos_info['node_id'];
        $pos_trace['store_id']     = $pos_info['store_id'];
        $pos_trace['shop_account'] = $verify_info['seller_mail'];

        // 查看是否存在同一流水
        $where         = "pos_id = '" . $verify_info['TerminalId'] . "' and pos_seq = '" . $verify_info['TerminalSeq'] . "'";
        $old_pos_trace = M('tzfb_offline_pay_trace')->where($where)->find();
        if (!$old_pos_trace) {
            $rs = M('tzfb_offline_pay_trace')->Add($pos_trace);
            if (!$rs) {
                $this->log("新增流水失败[tzfb_offline_pay_trace]失败" . M()->_sql());
                return false;
            }
        } else {
            $rs = M('tzfb_offline_pay_trace')->where($where)->save($pos_trace);
            if ($rs === false) {
                $this->log("保存流水失败[tzfb_offline_pay_trace]失败" . M()->_sql());
                return false;
            }
        }
        return true;
    }
    // 记录日志
    protected function log($msg, $level = Log::INFO) {
        trace('Log.' . $level . ':' . $msg);
        log_write($msg, $level);
    }
}
