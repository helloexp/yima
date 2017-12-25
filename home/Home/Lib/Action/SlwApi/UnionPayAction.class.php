<?php

class UnionPayAction extends Action {
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
            /*if ($this->getIP() !== C('slw.unionpay_api_ip')) {
            log_write('非法ip:'.$sign.'------'.print_r($data, true));
            $this->notifyreturn('1001');
            }*/
            /*校验token*/
            $data_str = http_build_query($data);
            $data_str .= '&token=' . C('slw.unionpay_token');
            $data_str_md5 = md5(urldecode($data_str));

            /*if ($sign != $data_str_md5) {
            log_write('签名错误:' . $sign . '------' . print_r($data, true));
            log_write('data_str:' . $data_str);
            log_write('data_str_md5:' . $data_str_md5);
            $this->notifyreturn('1003');
            }*/

            /*校验必填参数*/
            $data_key = array(
                'terminal_id',
                'merchant_id',
                'merchant_name',
                'batch_no',
                'card_no',
                'reference_no',
                'trace_no',
                'amount',
                'trans_type',
            );
            if ($data['trans_type'] == 1) {
                $data_key[] = 'old_reference_no';
            }
            if ($data['trans_type'] == 2) {
                $data_key[] = 'org_seq';
            }
            foreach ($data_key as $key) {
                if (!isset($data[$key]) || strlen($data[$key]) == 0) {
                    log_write('缺少参数:' . $key . '------' . print_r($data, true));
                    $this->notifyreturn('1002');
                }
            }
            $data['amount'] = ((int) $data['amount']) / 100;

            $pay_notifyreturn = false;
            if ($data['trans_type'] == 0) {
                $pay_notifyreturn = $this->pay_notifyreturn($data);
            }
            if ($data['trans_type'] == 1) {
                $pay_notifyreturn = $this->pay_r_notifyreturn($data);
            }
            if ($data['trans_type'] == 2) {
                $pay_notifyreturn = $this->pay_c_notifyreturn($data);
            }
            if ($pay_notifyreturn) {
                if ((int) M('tfb_slw_unionpay_trace')->where(array('trace_no' => $data['trace_no']))->count() > 0) {
                    $res = M('tfb_slw_unionpay_trace')->where(array('trace_no' => $data['trace_no']))->data($data)->save();
                    log_write('更新数据:' . print_r($data, true));
                } else {
                    $res = M('tfb_slw_unionpay_trace')->add($data);
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
        $where    = "pos_id ='" . $verify_info['terminal_id'] . "'  ";
        $pos_info = M('TposInfo')->where($where)->find();
        if (!$pos_info) {
            $this->log("未找到终端[" . $verify_info['terminal_id'] . "]");
            return false;
        }
        $pos_trace                 = array();
        $pos_trace['exchange_amt'] = $verify_info['amount'];
        $pos_trace['status']       = 0;
        $pos_trace['trans_time']   = $verify_info['gmt_create'];

        $pos_trace['user_name']          = $verify_info['card_no']; // 卡号
        $pos_trace['zfb_buyer_logon_id'] = $verify_info['card_no']; // 支付宝买家用户号
        $pos_trace['zfb_trade_no']       = $verify_info['reference_no']; // 流水号
        $pos_trace['zfb_out_trade_no']   = $verify_info['reference_no']; // 参考号

        $pos_trace['code_type'] = 901; //验证类型 100:支付宝 101:微信 901:杉德
        $pos_trace['real_amt']  = $verify_info['amount']; //实收金额
        $pos_trace['fee_amt']   = round($verify_info['amount'] * 0.372 / 100, 4); //实收金额

        $pos_trace['pos_id']      = $verify_info['terminal_id'];
        $pos_trace['pos_seq']     = $verify_info['batch_no'] . $verify_info['trace_no'];
        $pos_trace['trans_type']  = 'T';
        $pos_trace['is_canceled'] = '0';
        $pos_trace['user_id']     = '00000000'; // epos,68同步的user_id记录00000000
        $pos_trace['node_id']     = $pos_info['node_id'];
        $pos_trace['store_id']    = $pos_info['store_id'];
        $pos_trace['trans_time']  = date("YmdHis");

        // 查看是否存在源流水
        $where         = "trans_type = 'T' and zfb_out_trade_no = '" . $verify_info['reference_no'] . "'";
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
    // 支付线下支付流水同步应答处理-撤销
    private function pay_c_notifyreturn($verify_info) {
        $where    = "pos_id ='" . $verify_info['terminal_id'] . "'  ";
        $pos_info = M('TposInfo')->where($where)->find();
        if (!$pos_info) {
            $this->log("未找到终端[" . $verify_info['terminal_id'] . "]");
            return false;
        }
        $org_seq = $verify_info['batch_no'] . $verify_info['org_seq'];

        if (empty($org_seq)) {
            $this->log("未找到原流水[" . $verify_info['batch_no'] . $verify_info['org_seq'] . "]");
            return false;
        }

        $where         = "trans_type = 'T' and pos_seq = '" . $verify_info['batch_no'] . $verify_info['org_seq'] . "'";
        $ori_pos_trace = M('tzfb_offline_pay_trace')->where($where)->find();
        if (!$ori_pos_trace) {
            $this->log("未找到源流水[" . $verify_info['batch_no'] . $verify_info['org_seq'] . "]");
            return false;
        }
        // 交易成功需要更新源流水的状态
        // 更新源流水
        $new_pos_trace['is_canceled']    = '1';
        $new_pos_trace['cancel_pos_seq'] = $verify_info['batch_no'] . $verify_info['trace_no'];
        M('tzfb_offline_pay_trace')->where($where)->save($new_pos_trace);

        // 记流水
        $pos_trace                 = array();
        $pos_trace['exchange_amt'] = $verify_info['amount'];
        $pos_trace['status']       = 0;
        $pos_trace['trans_time']   = $verify_info['gmt_create'];

        $pos_trace['user_name']          = $verify_info['card_no']; // 卡号
        $pos_trace['zfb_buyer_logon_id'] = $verify_info['card_no']; // 支付宝买家用户号
        $pos_trace['zfb_trade_no']       = $verify_info['reference_no']; // 流水号
        $pos_trace['zfb_out_trade_no']   = $verify_info['reference_no']; // 参考号

        $pos_trace['code_type'] = 901; //验证类型 100:支付宝 101:微信 901:杉德
        $pos_trace['real_amt']  = '-' . $verify_info['amount']; //实收金额

        $pos_trace['pos_id']      = $verify_info['terminal_id'];
        $pos_trace['pos_seq']     = $verify_info['batch_no'] . $verify_info['trace_no'];
        $pos_trace['org_posseq']  = $verify_info['batch_no'] . $verify_info['org_seq'];
        $pos_trace['trans_type']  = 'C';
        $pos_trace['is_canceled'] = '0';
        $pos_trace['user_id']     = '00000000'; // epos,68同步的user_id记录00000000
        $pos_trace['node_id']     = $pos_info['node_id'];
        $pos_trace['store_id']    = $pos_info['store_id'];
        $pos_trace['trans_time']  = date("YmdHis");

        // 查看是否存在同一流水
        $where         = "pos_id = '" . $verify_info['terminal_id'] . "' and pos_seq = '" . $verify_info['batch_no'] . $verify_info['trace_no'] . "'";
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

    // 支付线下支付流水同步应答处理-退款
    private function pay_r_notifyreturn($verify_info) {
        $where    = "pos_id ='" . $verify_info['terminal_id'] . "'  ";
        $pos_info = M('TposInfo')->where($where)->find();
        if (!$pos_info) {
            $this->log("未找到终端[" . $verify_info['terminal_id'] . "]");
            return false;
        }
        $org_seq = $verify_info['old_reference_no'];

        if (empty($org_seq)) {
            $this->log("未找到原流水[" . $verify_info['old_reference_no'] . "]");
            return false;
        }
        /*tfb_slw_unionpay_trace*/
        $unionpay_trace = M("tfb_slw_unionpay_trace")->where(array('reference_no' => $verify_info['old_reference_no']))->find();
        if (!$unionpay_trace) {
            $this->log("未找到tfb_slw_unionpay_trace原流水[" . $verify_info['old_reference_no'] . "]");
            return false;
        } else {
            $where         = "trans_type = 'T' and pos_seq = '" . $unionpay_trace['batch_no'] . $unionpay_trace['trace_no'] . "'";
            $ori_pos_trace = M('tzfb_offline_pay_trace')->where($where)->find();
            if (!$ori_pos_trace) {
                $this->log("未找到tzfb_offline_pay_trace源流水[" . $unionpay_trace['batch_no'] . $unionpay_trace['trace_no'] . "]");
                return false;
            }
            // 交易成功需要更新源流水的状态
            // 更新源流水
            $new_pos_trace['is_canceled']    = '1';
            $new_pos_trace['cancel_pos_seq'] = $verify_info['batch_no'] . $verify_info['trace_no'];
            M('tzfb_offline_pay_trace')->where($where)->save($new_pos_trace);

        }

        // 记流水
        $pos_trace                 = array();
        $pos_trace['exchange_amt'] = $verify_info['amount'];
        $pos_trace['status']       = 0;
        $pos_trace['trans_time']   = $verify_info['gmt_create'];

        $pos_trace['user_name']          = $verify_info['card_no']; // 卡号
        $pos_trace['zfb_buyer_logon_id'] = $verify_info['card_no']; // 支付宝买家用户号
        $pos_trace['zfb_trade_no']       = $verify_info['reference_no']; // 流水号
        $pos_trace['zfb_out_trade_no']   = $verify_info['reference_no']; // 参考号

        $pos_trace['code_type'] = 901; //验证类型 100:支付宝 101:微信 901:杉德
        $pos_trace['real_amt']  = '-' . $verify_info['amount']; //实收金额

        $pos_trace['pos_id']      = $verify_info['terminal_id'];
        $pos_trace['pos_seq']     = $verify_info['batch_no'] . $verify_info['trace_no'];
        $pos_trace['org_posseq']  = $verify_info['batch_no'] . $verify_info['org_seq'];
        $pos_trace['trans_type']  = 'R';
        $pos_trace['is_canceled'] = '0';
        $pos_trace['user_id']     = '00000000'; // epos,68同步的user_id记录00000000
        $pos_trace['node_id']     = $pos_info['node_id'];
        $pos_trace['store_id']    = $pos_info['store_id'];
        $pos_trace['trans_time']  = date("YmdHis");

        // 查看是否存在同一流水
        $where         = "pos_id = '" . $verify_info['terminal_id'] . "' and pos_seq = '" . $verify_info['batch_no'] . $verify_info['trace_no'] . "'";
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
