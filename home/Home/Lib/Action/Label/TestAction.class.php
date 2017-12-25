<?php

class TestAction extends Action {

    public function __construct() {
        parent::__construct();
    }
    
    // 授权页面
    public function _getopenidIndex($type = 'snsapi_userinfo') {
        // 授权地址
        $open_url = 'https://open.weixin.qq.com/connect/oauth2/authorize?';
        // 回调地址
        $backurl = U('Label/Test/getOpenid', '', '', '', TRUE);
        // 授权参数
        $opt_arr = array(
            'appid' => 'wxccabc929493425ad', 
            'redirect_uri' => $backurl, 
            'response_type' => 'code', 
            'scope' => $type == '' ? 'snsapi_base' : 'snsapi_userinfo');
        $link = http_build_query($opt_arr);
        $gourl = $open_url . $link . '#wechat_redirect';
        redirect($gourl);
    }
    
    // 获取openid
    public function getOpenid() {
        $code = I('get.code');
        $apiUrl = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' .
             'wxccabc929493425ad' . '&secret=' .
             '8c0fd95cdb2fbbbb5d1222fa32777f99' . '&code=' . $code .
             '&grant_type=authorization_code';
        $result = $this->httpsGet($apiUrl);
        if (! $result)
            return false;
        
        $result = json_decode($result, true);
        print_r($result);
    }

    protected function httpsGet($apiUrl) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $result = curl_exec($ch);
        return $result;
    }

    public function index() {
        $this->_getopenidIndex();
    }

    public function createWhiteList() {
        $openid = I('get.openid');
        $accessToken = M('tweixin_info')->where(
            array(
                'node_id' => '00004488'))->getfield('app_access_token');
        $api_url = 'https://api.weixin.qq.com/card/testwhitelist/set?access_token=' .
             $accessToken;
        $data = array(
            'openid' => array(
                'o9RxAt21ui0v4__6RtqiRjvTRYF4', 
                'o9RxAtxtZd_IYpdYnRIzfIuwhckk', 
                'o9RxAt7ctclvjJ__ulkV9JwKlu0c', 
                'o9RxAt7VbmiJW9mRTreOsT1axXxs'));
        $post_data = json_encode($data);
        $result = httpPost($api_url, $post_data, $error, 
            array(
                'TIMEOUT' => 30));
        M()->_sql();
        print_r($result);
    }

    public function difference() {
        $accessToken = M('tweixin_info')->where(
            array(
                'node_id' => '00004488'))->getfield('app_access_token');
        dump($accessToken);
        $str = bin2hex($accessToken);
        dump($str);
        dump(hex2bin($str));
        dump(pack("H*", $str));
    }

    public function mysqlInfo() {
        M('tweixin_info')->where(
            array(
                'node_id' => '00004488'))->getfield('app_access_token');
        dump(mysql_get_host_info());
        dump(mysql_get_server_info());
        var_dump(mysql_get_proto_info());
        var_dump(mysql_get_client_info());
        var_export('ahsjkahkshajk');
    }


    public function weixinkf(){
        $node_id="00032513";
        $openid="ovMxUt6OTn3M6alIWWADTEC3pUnY";
    $params=['node_id'=>$node_id,'openid'=>$openid];
      $res=B('FbSzpaMatchGuess',$params);
      dump($res);exit;
    }


    public function weixinkfback()
    {
      $result=I('request.');
      dump($result);
      exit;
    }

    private function getSign($params = array()) 
    {
        ksort ( $params );
        $params_str = "";
        $signMsg = "";
        foreach ( $params as $key => $val ) {
            if ($key != "sign" && $key != "key"  && isset ( $val ) && @$val != "" ) {
                $params_str .= $key . "=" . $val . "&";
            }
        }
        $app_id='wx84c9fdade26453a3';
        $params_str .="key=".$app_id;
        $signMsg = strtoupper ( md5 ( $params_str ) );
        return $signMsg;
    }
    
    public function Fj114(){
            $url = 'http://test.wangcaio2o.com/index.php?g=Fj114&m=Interface&a=userLogin&telephone=13761130528&timestamp=20160119150312&opkey=2ad3d905366fb7e6071ebdbbab278ddd';
            $data = array('telephone'=>'13761130528', 'timestamp'=>'20160119150312', 'opkey'=>'2ad3d905366fb7e6071ebdbbab278ddd');
            $result = httpGet($url, $data);
//            $opt = array('TIMEOUT' => 30,'METHOD'  => 'GET');
//            // 创建post请求参数
//            import('@.ORG.Net.FineCurl') or die('[@.ORG.Net.FineCurl]导入包失败');
//            $socket = new FineCurl();
//            $socket->setopt('URL', $url);
//            $socket->setopt('TIMEOUT', $opt['TIMEOUT']);
//            $socket->setopt('HEADER_TYPE', $opt['METHOD']);
//            if (is_array($data)) {
//                $data = http_build_query($data);
//            }
//            print_r($data);
//            Log::write('请求：' . $url . '参数：' . $data, 'REMOTE');
//            $result = $socket->send($data);
//            $error  = $socket->error();
            dump($result);
    }

    public function myqrcode()
    {

    }

}
