<?php

/**
 * Class CjInterface 抽奖接口
 */
class CjInterface {

    const REDIS_QUEUE_NAME = 'award_list';

    public $_redisLink;
    // 旺财
    function cj_send($array = array()) {
        $cj_url = C('CJ_URL');
        $transId = date('YmdHis') . sprintf('%04s', mt_rand(0, 1000));
        /*
         * $strurl =
         * "&node_id=".$node_id."&phone_no=".$mobile."&batch_no=".$batch_no."&request_id=".$transId;
         * $send_url = $wc_arr['url'].$strurl;
         */
        $str = '&';
        $str .= http_build_query($array);
        /*
         * foreach($array as $k=>$v){ $str .= $k.'='.$v.'&'; } $str
         * =substr($str, 0, -1);
         */
        $send_url = $cj_url . $str;
        log_write('cj_send:' . $send_url);
        $ch = curl_init();
        $timeout = 10; // set to zero for no timeout
        curl_setopt($ch, CURLOPT_URL, $send_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $resp_info = curl_exec($ch);
        Log_write("$resp_info:" . print_r($resp_info, true));
        $resp_info = urldecode($resp_info);
        $arr = json_decode($resp_info, true);
        if (! $arr) {
            $resp_arr['resp_id'] = - 1;
            $resp_arr['resp_desc'] = "解析失败";
            return $resp_arr;
        }
        Log_write("cj_send:" . print_r($resp_info, true));
        $resp_arr = array();
        if ($arr['resp_id'] == '0000') {
            $resp_arr['resp_id'] = $arr['resp_id'];
            $resp_arr['award_level'] = $arr['resp_data']['award_level'];
            $resp_arr['batch_no'] = $arr['resp_data']['batch_no'];
            $resp_arr['rule_id'] = $arr['resp_data']['rule_id'];
            $resp_arr['transaction_id'] = $arr['resp_data']['transaction_id'];
            $resp_arr['resp_desc'] = $arr['resp_desc'];
            
            // 新增的by tr 20150211
            $resp_arr['request_id'] = $arr['resp_data']['request_id'];
            $resp_arr['bonus_use_detail_id'] = $arr['resp_data']['bonus_use_detail_id'];
            $resp_arr['cj_trace_id'] = $arr['resp_data']['cj_trace_id'];
            $resp_arr['card_ext'] = $arr['resp_data']['card_ext'];
            $resp_arr['card_id'] = $arr['resp_data']['card_id'];
            if ($resp_arr['card_id']) {
                log_write("中到了卡券------" . print_r($resp_arr, true));
            }
            // 新增20151208 by lwb
            $resp_arr['prize_type'] = $arr['resp_data']['prize_type'];
            $resp_arr['member_phone'] = $arr['resp_data']['member_phone'];
            $resp_arr['integral_get_flag'] = $arr['resp_data']['integral_get_flag'];
            $resp_arr['integral_get_id'] = $arr['resp_data']['integral_get_id'];
            $resp_arr['batch_class'] = $arr['resp_data']['batch_class'];
        } else {
            $resp_arr['resp_id'] = $arr['resp_id'];
            $resp_arr['resp_desc'] = $arr['resp_desc'];
        }
        return $resp_arr;
    }
    
    // 重发码
    function cj_resend($data = array()) {
        $url = C('WC_RESEND_ARR.url') . '&a=CodeResendReq';
        unset($data['a']);
        $error = '';
        $resp_info = httpPost($url, $data, $error, 
            array(
                'METHOD' => 'GET'));
        $resp_info = urldecode($resp_info);
        $arr = @json_decode($resp_info);
        if ($arr->resp_id == '0000') {}
        $resp_arr['resp_id'] = $arr->resp_id;
        $resp_arr['resp_desc'] = $arr->resp_desc;
        return $resp_arr;
    }
    
    // 异步发码,进到redis队列
    function cjSendQueue($data = array()) {
        $cj_url = C('CJ_URL');
        $transId = date('YmdHis') . sprintf('%04s', mt_rand(0, 1000));
        /*
         * $strurl =
         * "&node_id=".$node_id."&phone_no=".$mobile."&batch_no=".$batch_no."&request_id=".$transId;
         * $send_url = $wc_arr['url'].$strurl;
         */
        $str = '&';
        $str .= http_build_query($data);
        $send_url = $cj_url . $str;
        $redisKey = md5($transId);
        // $redisKey = $this->buildDrawLotteryQueueKey($data);//防止同一秒有多条记录生成
        $queue_data = $redisKey . $send_url;
        log_write(
            '[' . __METHOD__ . ']' . self::REDIS_QUEUE_NAME . ':' . $queue_data);
        $res = $this->getRedis();
        if (! $res) {
            return array(
                'resp_id' => '-1', 
                'resp_str' => '正在排队[01]');
        }
        $result = $res->lpush(self::REDIS_QUEUE_NAME, $queue_data);
        log_write(
            ':res->lpush:result:' .
                 ($result ? ' success' : ' error' . $res->getLastError()));
        if (! $result) {
            return array(
                'resp_id' => '-1', 
                'resp_str' => '抽奖失败');
        }
        return array(
            'resp_id' => '0000', 
            'resp_str' => 'success', 
            'data' => array(
                'key' => $redisKey));
    }

    /**
     * 同一秒数时间，相同请求，只允许有一条记录存在
     *
     * @author Jeff liu<liuwy@imageco.com.cn>
     * @param $data
     * @return string
     */
    public function buildDrawLotteryQueueKey($data) {
        $data['time'] = date('YmdHis');
        $str = http_build_query($data);
        
        return md5($str);
    }

    public function getCjResultByKey($key) {
        $res = $this->getRedis();
        $result = $res->get($key);
        $result = json_decode($result, true);
        return $result;
    }
    // 获取redis连接
    public function getRedis() {
        $this->_redisLink = new Redis();
        $config = C('REDIS') or die('CjInterface CONFIG.REDIS is undefined');
        $this->_redisLink->connect($config['host'], $config['port']);
        if (! $this->_redisLink) {
            $msg = 'redis ' . $config['host'] . ':' . $config['port'] .
                 ' is gone';
            log_write($msg);
            return false;
        }
        return $this->_redisLink;
    }
}