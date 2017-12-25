<?php

class SendCode {
    // 旺财 $batch_no(粉丝卡batch_no,其他则是tbatch_info的id)
    function wc_send($node_id, $user_id, $batch_no, $mobile, $dataFrom = '0', 
        $transId = null, $activeFlag = null, $batchInfoId = null, $channel_id = null, 
        $other = array()) {
        $wc_arr = C('WC_SEND_ARR');
        if (is_null($transId))
            $transId = get_request_id();
        $strurl = "&node_id=" . $node_id . "&user_id=" . $user_id . "&phone_no=" .
             $mobile . "&batch_no=" . $batch_no . "&request_id=" . $transId .
             "&data_from=" . $dataFrom . "&active_flag=" . $activeFlag .
             "&batch_info_id=" . $batchInfoId . "&channel_id=" . $channel_id;
        log_write('旺财发码请求：$other:' . var_export($other,1), 'INFO', 'SendCode');
        if ($other)
            $strurl .= '&' . http_build_query($other);
        $send_url = $wc_arr['url'] . $strurl;
        log_write('旺财发码请求：$send_url:' . $send_url, 'INFO', 'SendCode');
        
        $ch = curl_init();
        $timeout = 10; // set to zero for no timeout
        curl_setopt($ch, CURLOPT_URL, $send_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $resp_info = curl_exec($ch);
        log_write("旺财发码请求：" . $send_url, 'INFO', 'SendCode');
        log_write('旺财发码返回：origin: $resp_info' .$resp_info, 'INFO', 'SendCode');
        $resp_info = urldecode($resp_info);
        $arr = json_decode($resp_info, true);
        log_write('旺财发码返回：JSON $resp_info' . var_export($arr,1), 'INFO', 'SendCode');
        if ($arr['resp_id'] == '0000') {
            return true;
        } else {
            return $arr['resp_desc'];
        }
    }

    function resend_send($node_id, $user_id, $transId) {
        $wc_arr = C('WC_RESEND_ARR');
        $strurl = "&node_id=" . $node_id . "&user_id=" . $user_id .
             "&request_id=" . $transId;
        $send_url = $wc_arr['url'] . $strurl;
        
        $ch = curl_init();
        $timeout = 10; // set to zero for no timeout
        curl_setopt($ch, CURLOPT_URL, $send_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $resp_info = curl_exec($ch);
        log_write("旺财重发请求：" . $send_url, 'INFO', 'SendCode');
        
        $resp_info = urldecode($resp_info);
        $arr = @json_decode($resp_info);
        log_write("旺财发码返回：" . print_r($resp_info, true), 'INFO', 'SendCode');
        if ($arr->resp_id == '0000') {
            return true;
        } else {
            return $arr->resp_desc;
        }
    }
    // 转发接口
    function sendto_send($node_id, $user_id, $transId, $phone_no) {
        $wc_arr = C('WC_RESEND_ARR');
        $strurl = "&node_id=" . $node_id . "&user_id=" . $user_id .
             "&request_id=" . $transId . "&phone_no=" . $phone_no;
        $send_url = $wc_arr['url'] . $strurl;
        
        $ch = curl_init();
        $timeout = 10; // set to zero for no timeout
        curl_setopt($ch, CURLOPT_URL, $send_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $resp_info = curl_exec($ch);
        log_write("旺财重发请求：" . $send_url, 'INFO', 'SendCode');
        
        $resp_info = urldecode($resp_info);
        $arr = @json_decode($resp_info);
        log_write("旺财发码返回：" . print_r($resp_info, true), 'INFO', 'SendCode');
        if ($arr->resp_id == '0000') {
            return true;
        } else {
            return $arr->resp_desc;
        }
    }
    // 撤销 $cancel_flag 标识 0 使用中的码不可以撤消 1 使用中的码可以撤消
    function cancelcode($node_id, $user_id, $transId, $cancel_flag = '0') {
        $wc_arr = C('WC_CANCEL_ARR');
        $strurl = "&node_id=" . $node_id . "&user_id=" . $user_id .
             "&request_id=" . $transId . "&cancel_flag=" . $cancel_flag;
        $send_url = $wc_arr['url'] . $strurl;
        
        $ch = curl_init();
        $timeout = 10; // set to zero for no timeout
        curl_setopt($ch, CURLOPT_URL, $send_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $resp_info = curl_exec($ch);
        log_write("凭证撤销请求：" . $send_url, 'INFO', 'SendCode');
        $resp_info = urldecode($resp_info);
        $arr = @json_decode($resp_info);
        log_write("凭证撤销返回：" . print_r($resp_info, true), 'INFO', 'SendCode');
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
    // 奖品池
    function jpc_send($phone, $p_code = '', $c_code = '') {
        $jpc_arr = C('JPC_SEND_ARR');
        $url = $jpc_arr['url'];
        $channel_id = $jpc_arr['channel_id'];
        $key = $jpc_arr['key'];
        $method = 'request_send_prize';
        $timestamp = date('YmdHis');
        $sign_method = 'md5';
        $v = '1.0';
        $send_type = '1';
        $lbs_flag = '0';
        $arr = array(
            'channel_id' => $channel_id, 
            'method' => $method, 
            'timestamp' => $timestamp, 
            'sign_method' => $sign_method, 
            'v' => $v, 
            'phone' => $phone, 
            'send_type' => $send_type, 
            'lbs_flag' => $lbs_flag);
        if ($p_code != '' && $c_code != '') {
            $arr['lbs_flag'] = '2';
            $arr['province_code'] = $p_code;
            $arr['city_code'] = $c_code;
        }
        ksort($arr);
        $str = ''; // 请求字符串
        $str2 = ''; //
        foreach ($arr as $k => $v) {
            $str .= $k . '=' . $v . '&';
            $str2 .= $k . $v;
        }
        $str = substr($str, 0, - 1);
        
        // 签名
        $md5 = md5($key . $str2 . $key);
        
        $send_str = $str . '&sign=' . $md5;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // 我们在POST数据哦！
        curl_setopt($ch, CURLOPT_POST, 1);
        // 把post的变量加上
        curl_setopt($ch, CURLOPT_POSTFIELDS, $send_str);
        $output = curl_exec($ch);
        curl_close($ch);
        $resp_xml = urldecode($output);
        // return $resp_xml;
        log_write("奖品池请求：" . print_r($send_str, true) . "。" . $url, 'INFO', 
            'SendCode');
        log_write("奖品池返回：" . print_r($resp_xml, true), 'INFO', 'SendCode');
        
        import('@.Vendor.Xml');
        $xml = new Xml();
        $xml->setopt('ENCODING', 'UTF-8');
        $xml->parse($resp_xml);
        
        $error = $xml->error();
        if ($error) {
            log_write("解析奖品池返回：" . print_r($error, true), 'INFO', 'SendCode');
            return false;
        }
        $code = $xml->getValue('request_send_prize_resp->status->code');
        $info = $xml->getValue('request_send_prize_resp->status->text');
        // 返回交易流水号
        $trade_seq = $xml->getValue('request_send_prize_resp->trade_seq');
        
        if ($code == '0000')
            return $trade_seq;
        else
            return false;
    }

    function zc_send($sp_id, $batch_no, $mobile) {
        $zc_arr = C('ZC_SEND_ARR');
        $transId = date('YmdHis') . sprintf('%04s', mt_rand(0, 1000));
        $req_xml = '<?xml version="1.0" encoding="GBK" ?>' .
             '<SimpleSubmitVerifyReq>' . '<TransactionID>' . $transId .
             '</TransactionID>' . '<ISSPID>' . $sp_id . '</ISSPID>' .
             '<SystemID>' . $zc_arr['system_id'] . '</SystemID>' .
             '<DataFrom></DataFrom>' . '<BatchSeq></BatchSeq>' .
             '<SendLevel></SendLevel>' . '<IpSrc></IpSrc>' . '<Recipients>' .
             '<Number>' . $mobile . '</Number>' . '</Recipients>' .
             '<ActivityInfo>' . '<ActivityID>' . $batch_no . '</ActivityID>' .
             '</ActivityInfo>' . '</SimpleSubmitVerifyReq>';
        log_write("支撑请求XML：" . print_r($req_xml, true), 'INFO', 'SendCode');
        $mac = md5($zc_arr['pass'] . $req_xml . $zc_arr['pass']);
        $req_xml = 'xml=' . urlencode($req_xml) . '&mac=' . $mac;
        
        import("@.Vendor.Curl");
        $curl = new Curl($zc_arr['url']);
        $curl->setopt(CURLOPT_SSL_VERIFYHOST, 0);
        $curl->setopt(CURLOPT_SSL_VERIFYPEER, 0);
        
        $resp_xml = $curl->sendmsg($req_xml);
        $curl->close();
        
        log_write("支撑请求：" . print_r($req_xml, true), 'INFO', 'SendCode');
        
        $arr = array();
        parse_str($resp_xml, $arr);
        $resp_xml = str_replace("\\", "", $arr['xml']);
        
        import("@.Vendor.Xml");
        $xmlparser = new Xml();
        $xmlparser->setopt('ENCODING', 'UTF-8');
        $xmlparser->parse($resp_xml);
        log_write("支撑返回：" . print_r($resp_xml, true), 'INFO', 'SendCode');
        
        $error = $xmlparser->error();
        if ($error) {
            return false;
        }
        
        $ret_id = $xmlparser->getValue(
            'SimpleSubmitVerifyRes->Status->StatusCode');
        $ret_str = $xmlparser->getValue(
            'SimpleSubmitVerifyRes->Status->StatusText');
        
        if ($ret_id == '0000')
            return true;
        else
            return $ret_str;
    }
    
    // 旺财 $batch_no(粉丝卡batch_no,其他则是tbatch_info的id)
    function wcSendNew($node_id, $user_id, $batch_no, $mobile, $dataFrom = '0', 
        $transId = null, $activeFlag = null, $batchInfoId = null, $channel_id = null, 
        $other = array()) {
        if (is_null($transId))
            $transId = get_request_id();
        
        $batchInfo = M('tbatch_info as b')
                ->join('tmarketing_info as m on m.id = b.m_id')
                ->where("b.id = {$batchInfoId}")
                ->field('m.batch_type, m.id')
		->find();
        //会员ID        
        $member_id = $_SESSION['store_mem_id'.$node_id]['user_id'];
        //openId
        $wxOpenId = '';
        if (session('?merWxUserInfo')) {
            $wxUserInfo = session('merWxUserInfo');
            if (! $wxUserInfo)
                $wxUserInfo = session('wxUserInfo');
            $wxOpenId = $wxUserInfo['openid'];
        }

        $data = [
            'trans_type' => 1,
            'node_id' => $node_id,
            'phone_no' => $mobile,
            'batch_no' => $batch_no,
            'request_id' => $transId,
            'batch_info_id' => $batchInfoId,
            'ims_flag' => '1',
            'add_time' => date('YmdHis'),
            'm_id' => $batchInfo['id'],
            'award_other_param' => 'data_from=8&ticket_seq=&batch_type='.$batchInfo['batch_type'].'&store_id=&channel_id='.$channel_id.'&pingan_open_id=&use_rule='.$other['use_rule'].'&fb_cmpay_flag='.$other['fb_cmpay_flag'].'&print_text='.$other['print_text'].'&wx_open_id='.$wxOpenId.'&member_id=' .$member_id,
            'deal_flag' => '1'    //0为不发送，1为准备发送
        ];
	log_write('sendCodeInfo:'. json_encode($data));
        $result = M('tsend_award_trace')->add($data);
        if ($result) {
            return true;
        } else {
            return '发码失败';
        }
    }
}
?>