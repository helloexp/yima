<?php

/**
 * Class YhbSmsService Desc 翼惠宝短信通道 kk@2015-08-07 14:02:58
 */
class YhbSmsService {
    // 公共请求参数
    private $public_data;
    // 地址
    private $send_url;
    // 短信
    CONST SEND_TYPE_SMS = 2;
    // 彩信
    CONST SEND_TYPE_MMS = 1;
    
    // 链接数太多
    CONST RESULT_RESP_MORE_CONN = - 10008;
    // 未找到匹配的返回码
    CONST RESULT_ERR_CODE = - 99999;

    CONST RESULT_ERR_MSG = '未匹配到返回码';

    public function __construct() {
        $this->public_data = array(
            'company' => C('Yhb.smsconfig.company'), 
            'account' => C('Yhb.smsconfig.account'), 
            'password' => C('Yhb.smsconfig.password'));
        $this->send_url = C('Yhb.smsconfig.url');
    }

    /**
     *
     * @param $mobile
     * @param $content
     * @param string $extnum
     * @return bool
     */
    public function smssend($mobile, $content, $extnum = '') {
        $data = array_merge($this->public_data, 
            array(
                'mobile' => $mobile, 
                'content' => $content, 
                'extnum' => $extnum, 
                'type' => self::SEND_TYPE_SMS));
        $i = 0;
        do {
            $i ++;
            $result = (int) httpPost($this->send_url, $data);
            
            if ($result > 0) {
                return true;
            }
            
            // 如果返回标志为【已经到了发送连接数目的上限】则重试
            if ($result == self::RESULT_RESP_MORE_CONN) {
                usleep(rand(0, 1000) * 1000);
                continue;
            }
        }
        while ($i < 3);
        
        if (isset($code_arr[(string) $result])) {
            $code_arr = C('Yhb.smsconfig.result_code');
            $result_msg = $code_arr[(string) $result];
        } else {
            $result = self::RESULT_ERR_CODE;
            $result_msg = self::RESULT_ERR_MSG;
        }
        throw_exception('[' . $result . ']' . $result_msg);
    }

    /**
     *
     * @param $req_seq tbarcode_trace.req_seq
     * @return bool 若在测试环境，则请求测试支撑发纯文本短信
     */
    public function sendBarcodeSms($barID) {
        $barInfo = M('tbarcode_trace')->where(
            array(
                'id' => $barID))->find();
        if (! $barInfo) {
            throw_exception('未找到凭证');
        }
        // $tpl =
        // '您已获得“[GOODS_NAME]”、有效期[BEGIN_TIME]-[END_TIME]，串码标号：[ASS_NUMBER]，优惠券详情请查看【翼惠宝】';
        // 我的优惠卷地址
        $url = C('Yhb.myVoucher');
        $tel = "您已申请获得“[GOODS_NAME]”、有效期[BEGIN_TIME]-[END_TIME]，详情请点击 " . $url .
             "【翼惠宝】";
        $batchInfo = M('tbatch_info')->where(
            array(
                'id' => $barInfo['b_id']))->find();
        if (! $batchInfo) {
            throw_exception('未找到奖品信息');
        }
        
        $begin_time = dateformat($barInfo['verify_begin_date'], 'Y/n/j');
        $end_time = dateformat($barInfo['verify_end_date'], 'Y/n/j');
        
        $tpl = str_ireplace(
            array(
                '[GOODS_NAME]', 
                '[BEGIN_TIME]', 
                '[END_TIME]', 
                '[ASS_NUMBER]'), 
            array(
                $batchInfo['batch_short_name'], 
                $begin_time, 
                $end_time, 
                $barInfo['assist_number']), $tpl);
        
        $res = $this->send($tpl, $barInfo['phone_no']);
        return $res;
    }

    /**
     * 验证码
     *
     * @param [type] $barID [description]
     * @return [type] [description]
     */
    public function sendVerifyCodeSms($phone, $code) {
        if (empty($phone) || empty($code)) {
            return false;
        }
        $tpl = '感谢您使用翼惠宝，请在30分钟内使用此验证码，验证码：' . $code;
        $res = $this->send($tpl, $phone);
        return $res;
    }

    /**
     * 发送
     *
     * @param string $tpl 短信文本模板
     * @param string $phone 手机号码
     * @return boll 若在测试环境，则请求测试支撑发纯文本短信
     */
    private function send($tpl, $phone) {
        $res = Yhb_sms($phone, $tpl);
        return $res;
        /*
         * if (is_production()) { return $this->smssend($phone, $tpl); } else {
         * //通知支撑 $TransactionID = date("YmdHis") . mt_rand(100000, 999999);
         * //请求单号 //请求参数 $req_array = array( 'NotifyReq' => array(
         * 'TransactionID' => $TransactionID, 'ISSPID' => C('MOBILE_ISSPID'),
         * 'SystemID' => C('ISS_SYSTEM_ID'), 'SendLevel' => '1', 'Recipients' =>
         * array( 'Number' => $phone //手机号 ), 'SendClass' => 'MMS',
         * 'MessageText' => $tpl,//短信内容 'Subject' => '', 'ActivityID' =>
         * C('MOBILE_ACTIVITYID'), 'ChannelID' => '', 'ExtentCode' => '' ) );
         * $RemoteRequest = D('RemoteRequest', 'Service'); $resp_array =
         * $RemoteRequest->requestIssServ($req_array); $ret_msg =
         * $resp_array['NotifyRes']['Status']; if (!$resp_array ||
         * ($ret_msg['StatusCode'] != '0000' && $ret_msg['StatusCode'] !=
         * '0001')) { throw_exception('发送失败!'); } }
         */
    }
}