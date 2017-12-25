<?php

/**
 * 微信红包发送接口
 *
 * @author bao
 */
class WeixinRedPackService {

    public $error = '';
    // 错误信息
    public $errCode = '';
    // 错误码
    protected $mchId = '';
    // 微信支付分配的商户号
    protected $wxappId = '';
    // 微信分配的公众账号ID（企业号corpid即为此appId）。接口传入的所有appid应该为公众号的appid（在mp.weixin.qq.com申请的），不能为APP的appid（在open.weixin.qq.com申请的）
    protected $appKey = '';
    // 支付秘钥
    protected $nodeId = '';

    protected $apiclientCert = '';
    // 商户证书
    protected $apiclientKey = '';
    // 证书秘钥
    protected $sendUrl = "https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack";

    // 红包接口请求url
    public function init($nodeId) {
        $map = ['node_id'=>$nodeId,'status'=>'1','bonus_flag'=>'1'];
        $wxConfig = M('tnode_wxpay_config')->where($map)->find();
        if (!$wxConfig) {
            log_write("WeixinRedPackLog:{$nodeId}商户微信信息未配置");
        }
        if (empty($wxConfig['apiclient_cert']) || empty($wxConfig['apiclient_key'])) {
            log_write("WeixinRedPackLog:{$nodeId}商户证书或证书秘钥未配置");
        }
        $this->mchId         = $wxConfig['mch_id'];
        $this->wxappId       = $wxConfig['appid'];
        $this->appKey        = $wxConfig['paykey'];
        $this->nodeId        = $nodeId;
        $this->apiclientCert = $wxConfig['apiclient_cert'];
        $this->apiclientKey  = $wxConfig['apiclient_key'];
    }

    /**
     * 发送前业务处理
     *
     * @param unknown $openId
     * @param string  $mchBillno
     * @param string  $nodeName
     */
    public function sendPack($openId, $bId, $mId, $requestId) {
        // 获取红包信息
        $redPackInfo = M('tbatch_info')->where("id='{$bId}' and batch_class='22'")->find();
        if (!$redPackInfo) {
            $this->error = "未找到红包信息";
            log_write("WeixinRedPackLog:{$this->nodeId}未找到红包信息b_id{$bId}");

            return false;
        }
        // 活动名称
        $tmarName = M('tmarketing_info')->where("id='{$mId}'")->getField('name');
        if (!$tmarName) {
            $this->error = "未找到活动";
            log_write("WeixinRedPackLog:{$this->nodeId}未找到活动m_id{$mId}");

            return false;
        }
        $sendData = array( // 接口参数全必填
                'nonce_str'    => $this->getNonceStr(),
            // 随机字符串，不长于32位
                'mch_billno'   => $this->getMchBillno(),
            // 商户订单号（每个订单号必须唯一组成：mch_id+yyyymmdd+10位一天内不能重复的数字。接口根据商户订单号支持重入，如出现超时可再调用
                'mch_id'       => $this->mchId,
            // 微信支付分配的商户号
                'wxappid'      => $this->wxappId,
            // 微信分配的公众账号ID
                'send_name'    => $redPackInfo['material_code'],
            // 红包发送者名称
                're_openid'    => $openId,
            // 接受红包的用户用户在wxappid下的openid
                'total_amount' => $redPackInfo['batch_amt'] * 100,
            // 付款金额，单位分
                'total_num'    => '1',
            // 红包发放总人数
                'wishing'      => $redPackInfo['print_text'],
            // 红包祝福语
                'client_ip'    => '192.168.0.1',
            // 调用接口的机器Ip地址
                'act_name'     => $redPackInfo['batch_name'],
            // 活动名称
                'remark'       => $redPackInfo['batch_desc'],
        ); // 备注信息

        /*
         * $sendData = array(//接口参数全必填 'nonce_str' => $this->getNonceStr(),
         * //随机字符串，不长于32位 'mch_billno' => $this->getMchBillno(),
         * //商户订单号（每个订单号必须唯一组成：mch_id+yyyymmdd+10位一天内不能重复的数字。接口根据商户订单号支持重入，如出现超时可再调用
         * 'mch_id' => $this->mchId, //微信支付分配的商户号 'wxappid' => $this->wxappId,
         * //微信分配的公众账号ID 'send_name' => '翼码旺财', //红包发送者名称 're_openid' =>
         * 'oVTJst7Ug6p8WDrbvTuQGJlvHG94', //接受红包的用户用户在wxappid下的openid
         * 'total_amount' => '100', //付款金额，单位分 'total_num' => '1', //红包发放总人数
         * 'wishing' => '恭喜发财', //红包祝福语 'client_ip' => '192.168.0.1',
         * //调用接口的机器Ip地址 'act_name' => '送红包啦', //活动名称 'remark' => '红包来啦' //备注信息
         * );
         */
        // dump($sendData);exit;
        $result = $this->sendBegin($sendData);
        // 流水数据
        $traceData = array(
                'node_id'    => $this->nodeId,
                'openid'     => $openId,
                'bonus_amt'  => $redPackInfo['batch_amt'],
                'add_time'   => date('YmdHis'),
                'request_id' => $requestId,
                'b_id'       => $bId,
                'm_id'       => $mId,
                'mch_billno' => $sendData['mch_billno'],
                'goods_id'   => $redPackInfo['goods_id'],
        );
        if ($result) {
            $traceData['status'] = '0';
            M('twx_bonus_send_trace')->add($traceData); // 记录成功流水
            return $result;
        } else { // 由于抽奖逻辑那里使用事务这里返回false会回滚所以失败记录流水会插入不进去
            $traceData['status']     = '1';
            $traceData['wx_ret_msg'] = $this->error;
            $sqlReautl               = M('twx_bonus_send_trace')->add($traceData); // 记录失败流水
            return false;
        }
    }

    /**
     * 发送
     *
     * @param unknown $data
     */
    public function sendBegin($data) {
        $sendData = array( // 接口参数全必填
                'nonce_str'    => $data['nonce_str'],
            // 随机字符串，不长于32位
                'mch_billno'   => $data['mch_billno'],
            // 商户订单号（每个订单号必须唯一组成：mch_id+yyyymmdd+10位一天内不能重复的数字。接口根据商户订单号支持重入，如出现超时可再调用
                'mch_id'       => $this->mchId,
            // 微信支付分配的商户号
                'wxappid'      => $this->wxappId,
            // 微信分配的公众账号ID
                'send_name'    => $data['send_name'],
            // 红包发送者名称
                're_openid'    => $data['re_openid'],
            // 接受红包的用户用户在wxappid下的openid
                'total_amount' => $data['total_amount'],
            // 付款金额，单位分
                'total_num'    => $data['total_num'],
            // 红包发放总人数
                'wishing'      => $data['wishing'],
            // 红包祝福语
                'client_ip'    => $data['client_ip'],
            // 调用接口的机器Ip地址
                'act_name'     => $data['act_name'],
            // 活动名称
                'remark'       => $data['remark'],
        ); // 备注信息

        // 获取签名
        $sendData['sign'] = $this->sign($sendData);
        $xmlStr           = $this->arraytoXml($sendData);
        log_write("WeixinRedPackLog:{$this->nodeId}-{$xmlStr}");
        // 发送报文
        $resutl = $this->curlPostSsl($this->sendUrl, $xmlStr);
        if ($resutl) {
            return $resutl;
        } else {
            return false;
        }
    }

    /**
     * 数组组装xml
     */
    protected function arraytoXml($data) {
        $xml = "<xml>";
        foreach ($data as $key => $val) {
            $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
        }
        $xml .= "</xml>";

        return $xml;
    }

    /**
     * 获取签名
     *
     * @param unknown $data
     *
     */
    protected function sign($data) {
        foreach ($data as $k => $v) {
            if ($v == null) {
                unset($data[$k]);
            }
        }
        ksort($data, SORT_STRING);
        $signSrc = urldecode(http_build_query($data));
        $signSrc = $signSrc . '&key=' . $this->appKey;
        $sign    = strtoupper(md5($signSrc));

        return $sign;
    }

    /**
     * 报文发送
     */
    protected function curlPostSsl($url, $vars, $second = 30, $aHeader = array()) {
        $ch = curl_init();
        // 超时时间
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // 这里设置代理，如果有的话
        // curl_setopt($ch,CURLOPT_PROXY, '10.206.30.98');
        // curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        // 以下两种方式需选择一种

        // 第一种方法，cert 与 key 分别属于两个.pem文件
        // 默认格式为PEM，可以注释
        // curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
        // curl_setopt($ch,CURLOPT_SSLCERT,getcwd().'/cert.pem');
        // 默认格式为PEM，可以注释
        // curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
        // curl_setopt($ch,CURLOPT_SSLKEY,getcwd().'/private.pem');

        // 第二种方式，两个文件合成一个.pem文件
        // curl_setopt($ch,CURLOPT_SSLCERT,getcwd().'/all.pem');
        $certFile = $this->createCertFile($this->apiclientCert, $this->apiclientKey);
        curl_setopt($ch, CURLOPT_SSLCERT, $certFile);

        if (count($aHeader) >= 1) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);
        }

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);
        for ($i = 0; $i < 3; $i++) {
            $data     = curl_exec($ch);
            $errorStr = curl_error($ch);
            if ($this->error_code != 7 && $this->error_code != 28) {
                break;
            }
            log_write("WeixinRedPackLog:{$this->nodeId},$errorStr");
            sleep(3);
        }

        if ($data) {
            curl_close($ch);
            import('@.ORG.Util.Xml') or die('@.ORG.Util.Xml导入失败');
            $xml = new Xml();
            log_write("WeixinRedPackLog:{$this->nodeId},{$data}");
            $arr = $xml->parse($data);
            $arr = $xml->getAll();
            array_walk_recursive($arr, 'utf8Str');
            if ($arr['xml']['return_code'] == 'SUCCESS') {
                return $arr['xml'];
            } else {
                $this->errCode = $arr['xml']['return_code'];
                $this->error   = $arr['xml']['return_msg'];

                return false;
            }
        } else {
            $this->error = "WeixinRedPackLog:{$this->nodeId}接口请求失败,errorStr:{$errorStr}";
            curl_close($ch);

            return false;
        }
    }

    /**
     * 获取红包接口中nonce_str字段
     */
    public function getNonceStr() {
        $str = '1234567890abcdefghijklmnopqrstuvwxyz';
        for ($i = 0; $i < 30; $i++) {
            $j = rand(0, 35);
            $t1 .= $str[$j];
        }

        return $t1;
    }

    /**
     * 获取红包接口商户订单号
     */
    public function getMchBillno() {
        return $this->mchId . date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5);
    }

    /**
     * 创建微信红包证书文件
     *
     * @param $apiclientCert //微信商户证书
     * @param $apiclientKey  //证书秘钥
     */
    protected function createCertFile($apiclientCert, $apiclientKey) {
        $fileName = RUNTIME_PATH . 'redpack/' . $this->nodeId . '.pem';
        if (!is_file($fileName) || ceil((time() - filemtime($fileName)) / 3600) > 24) { // 24小时重新生成文件
            $dir = dirname($fileName);
            // 目录不存在则创建
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $result = file_put_contents($fileName, $this->apiclientCert . $this->apiclientKey);
            if (!$result) {
                $this->error = '证书写入文件失败';
                log_write("WeixinRedPackLog:{$this->nodeId},证书写入文件失败");

                return false;
            }
        }

        return $fileName;
    }
}