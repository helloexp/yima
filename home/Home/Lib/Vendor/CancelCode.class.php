<?php

class CancelCode {
    // 旺财
    function wc_cancelcode($node_id, $user_id, $transId) {
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
        if ($arr->resp_id == '0000') {
            return true;
        } else {
            return $arr->resp_desc;
        }
    }
}
?>