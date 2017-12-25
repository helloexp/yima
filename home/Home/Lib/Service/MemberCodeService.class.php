<?php

class MemberCodeService {
    // 旺财
    function cancelcode($node_id, $user_id, $transId) {
        $wc_arr = C('WC_CANCEL_ARR');
        $strurl = "&node_id=" . $node_id . "&user_id=" . $user_id .
             "&request_id=" . $transId;
        $send_url = $wc_arr['url'] . $strurl;
        
        $ch = curl_init();
        $timeout = 10; // set to zero for no timeout
        curl_setopt($ch, CURLOPT_URL, $send_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $resp_info = curl_exec($ch);
        Log::write("凭证撤销请求：" . $send_url);
        
        $resp_info = urldecode($resp_info);
        $arr = @json_decode($resp_info);
        Log::write("凭证撤销返回：" . print_r($resp_info, true));
        if (in_array($arr->resp_id, 
            array(
                '0000', 
                '0001', 
                '9101', 
                '9102', 
                '9002', 
                '9003'))) {
            return true;
        } else {
            return $arr->resp_desc;
        }
    }
    // 旺财
    function sendCode($node_id, $batch_no, $mobile, $transId) {
        $wc_arr = C('WC_SEND_ARR');
        // $transId = date('YmdHis').sprintf('%04s',mt_rand(0,1000));
        $strurl = "&node_id=" . $node_id . "&phone_no=" . $mobile . "&batch_no=" .
             $batch_no . "&request_id=" . $transId;
        $send_url = $wc_arr['url'] . $strurl;
        
        $ch = curl_init();
        $timeout = 10; // set to zero for no timeout
        curl_setopt($ch, CURLOPT_URL, $send_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $resp_info = curl_exec($ch);
        Log::write("旺财发码请求：" . $send_url);
        
        $resp_info = urldecode($resp_info);
        $arr = @json_decode($resp_info);
        Log::write("旺财发码返回：" . print_r($resp_info, true));
        if ($arr->resp_id == '0000') {
            return true;
        } else {
            return $arr->resp_desc;
        }
    }
}
?>