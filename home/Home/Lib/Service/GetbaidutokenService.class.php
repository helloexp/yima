<?php

/**
 *
 * @author zhengxd 2014-11-20 13:41:23
 */
class GetbaidutokenService {

    public function gettoken() {
        $row = M('tbaidu_token')->where(
            array(
                'token_type' => '1'))->find();
        if (empty($row)) {
            return false;
        }
        if (((strtotime(date('Ymd')) - strtotime($row['update_time'])) /
             (60 * 60 * 24)) < 28) {
            return $row['baidu_token'];
        } else {
            
            return $this->gobaidu();
        }
    }

    public function gobaidu() {
        $outacom = M('tbaidu_token')->where(
            array(
                'token_type' => '2'))->find();
        if (empty($outacom)) {
            return false;
        }
        $data = array(
            'grant_type' => 'refresh_token', 
            'refresh_token' => $outacom['baidu_token'], 
            'client_id' => 'NIMhAtANqtpisqE8TwWnXOcp', 
            'client_secret' => 'PsIpBWylEaS9LSO6oKOuUORwBI4spNz8');
        $url = "https://openapi.baidu.com/oauth/2.0/token";
        // 测试关闭
        // $res=$this->send($url,1,$data);
        // $res=array(
        // 'refresh_token'=>'888888888123888',
        // 'access_token'=>'好测试kbkll666666666645688',
        // );
        if (empty($res['refresh_token']) || empty($res['access_token'])) {
            return false;
        }
        $arr_refresh = array(
            'baidu_token' => $res['refresh_token']);
        $arr_access = array(
            'baidu_token' => $res['access_token'], 
            'update_time' => date('Ymd'));
        M()->startTrans();
        $refresh = M('tbaidu_token')->where(
            array(
                'token_type' => '2'))->save($arr_refresh);
        if ($refresh === false) {
            M()->rollback();
            return false;
        }
        $access = M('tbaidu_token')->where(
            array(
                'token_type' => '1'))->save($arr_access);
        if ($access === false) {
            M()->rollback();
            return false;
        }
        
        M()->commit();
        return $res['access_token'];
    }

    /**
     * 功能：模拟请求 默认：https get 请求
     */
    public function send($url, $type = 0, $data = '') {
        $url = urldecode($url);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, $type);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // https
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1); // https
        $data = curl_exec($curl);
        curl_close($curl);
        
        Log::write("百度直达号请求：" . $url);
        
        $arr = @json_decode($data, true);
        Log::write("百度直达号返回：" . print_r($arr, true));
        
        return $arr;
    }
}