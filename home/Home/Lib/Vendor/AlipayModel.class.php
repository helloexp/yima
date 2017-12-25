<?php

/**
 * 支付宝即时到帐接口
 */
class AlipayModel {

    protected $alipay_config = array( // 配置信息
        'partner' => '',  // 合作身份者id，以2088开头的16位纯数字（必填）
        'key' => '',  // 安全检验码，以数字和字母组成的32位字符（必填）
        'sign_type' => 'MD5',  // 签名方式 不需修改
        'input_charset' => '',  // 字符编码格式 目前支持 gbk 或 utf-8（必填）
        'transport' => 'http');
    // 访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
    
    protected $parameter = array( // 请求参数信息
        'service' => '',  // 接口名称（必填）
        'partner' => '',  // 合作身份者id，以2088开头的16位纯数字（必填）
        'payment_type' => '1',  // 支付类型,必填不能修改
        'notify_url' => '',  // 服务器异步通知页面路径,需http://格式的完整路径，不能加?id=123这类自定义参数（必填）
        'return_url' => '',  // 页面跳转同步通知页面路径,需http://格式的完整路径，不能加?id=123这类自定义参数，不能写成http://localhost/（必填）
        'seller_email' => '',  // 卖家支付宝帐户（必填）
        'out_trade_no' => '',  // 订单号（必填）
        'subject' => '',  // 订单名称（必填）
        'total_fee' => '',  // 付款金额（必填）
        'body' => '',  // 订单描述
        'show_url' => '',  // 商品展示地址,需以http://开头的完整路径，例如：http://www.xxx.com/myorder.html
        'anti_phishing_key' => '',  // 若要使用请调用类文件submit中的query_timestamp函数
        'exter_invoke_ip' => '',  // 客户端的IP地址,非局域网的外网IP地址，如：221.0.0.1
        '_input_charset' => '');
    // //字符编码格式 目前支持 gbk 或 utf-8（必填）
    
    protected $alipay_gateway_new = 'https://mapi.alipay.com/gateway.do?';
    // 支付宝网关地址（新）
    protected $https_verify_url = 'https://mapi.alipay.com/gateway.do?service=notify_verify&';
    // HTTPS形式消息验证地址
    protected $http_verify_url = 'http://notify.alipay.com/trade/notify_query.do?';
    // HTTP形式消息验证地址
    public function __construct() {
        // 初始化配置信息
        $this->alipay_config['partner'] = C('ALIPAY.PARTNER');
        $this->alipay_config['key'] = C('ALIPAY.KEY');
        $this->alipay_config['input_charset'] = strtolower('utf-8');
        // 初始化请求参数信息
        $this->parameter['service'] = 'create_direct_pay_by_user';
        $this->parameter['partner'] = $this->alipay_config['partner'];
        // $this->parameter['notify_url'] =
        // C('CURRENT_HOST').'index.php?m=AlipayReturn&a=index';
        // $this->parameter['return_url'] =
        // C('CURRENT_HOST').'index.php?m=AlipayReturn&a=index';
        $this->parameter['seller_email'] = C('ALIPAY.SELLER_EMAIL');
        $this->parameter['_input_charset'] = $this->alipay_config['input_charset'];
    }

    /**
     * 支付流程开始
     */
    public function AlipayTo($order_sn, $subject, $total_fee, $returnUrl, 
        $notifyUrl) {
        // 根据会员id和订单id检查订单的有效性
        $orderInfo = array(
            'out_trade_no' => $order_sn,  // 订单号
            'subject' => $subject,  // 商品名称
            'total_fee' => $total_fee,  // 商品价格
            'return_url' => $returnUrl,  // 同步回调地址
            'notify_url' => $notifyUrl); // 异步回调地址
                                         
        // 配置请求信息订单信息
        foreach ($orderInfo as $k => $v) {
            if (array_key_exists($k, $this->parameter))
                $this->parameter[$k] = $v;
        }
        // 除去待签名参数数组中的空值和签名参数
        $para_filter = $this->paraFilter($this->parameter);
        
        // 对待签名参数数组排序
        $para_sort = $this->argSort($para_filter);
        
        // 生成签名结果
        $mysign = $this->buildRequestMysign($para_sort);
        
        // 签名结果与签名方式加入请求提交参数组中
        $para_sort['sign'] = $mysign;
        $para_sort['sign_type'] = strtoupper(
            trim($this->alipay_config['sign_type']));
        $url = $this->createLinkstringUrlencode($para_sort);
        header('location:' . $this->alipay_gateway_new . $url);
        exit();
    }

    /**
     * 支付宝返回验证
     */
    public function checkReturn($data) {
        if (empty($data))
            return false;
            // 生成签名结果
        $isSign = $this->getSignVeryfy($data, $data["sign"]);
        // 获取支付宝远程服务器ATN结果（验证是否是支付宝发来的消息）
        $responseTxt = 'true';
        if (! empty($data["notify_id"])) {
            $responseTxt = $this->getResponse($data["notify_id"]);
        }
        // 验证
        // $responsetTxt的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
        // isSign的结果不是true，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关
        if (preg_match("/true$/i", $responseTxt) && $isSign) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 除去数组中的空值和签名参数
     *
     * @param $para 签名参数组 return 去掉空值与签名参数后的新签名参数组
     */
    public function paraFilter($para) {
        $para_filter = array();
        foreach ($para as $key => $val) {
            if ($key == "sign" || $key == "sign_type" || $val == "")
                continue;
            else
                $para_filter[$key] = $para[$key];
        }
        return $para_filter;
    }

    /**
     * 对数组排序
     *
     * @param $para 排序前的数组 return 排序后的数组
     */
    public function argSort($para) {
        ksort($para);
        reset($para);
        return $para;
    }

    /**
     * 生成签名结果
     *
     * @param $para_sort 已排序要签名的数组 return 签名结果字符串
     */
    public function buildRequestMysign($para_sort) {
        // 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->createLinkstring($para_sort);
        
        $mysign = "";
        switch (strtoupper(trim($this->alipay_config['sign_type']))) {
            case "MD5":
                $mysign = $this->md5Sign($prestr, $this->alipay_config['key']);
                break;
            default:
                $mysign = "";
        }
        
        return $mysign;
    }

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     *
     * @param $para 需要拼接的数组 return 拼接完成以后的字符串
     */
    public function createLinkstring($para) {
        $arg = "";
        foreach ($para as $key => $val) {
            $arg .= $key . "=" . $val . "&";
        }
        // 去掉最后一个&字符
        $arg = substr($arg, 0, count($arg) - 2);
        
        // 如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }
        
        return $arg;
    }

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串，并对字符串做urlencode编码
     *
     * @param $para 需要拼接的数组 return 拼接完成以后的字符串
     */
    public function createLinkstringUrlencode($para) {
        $arg = "";
        foreach ($para as $key => $val) {
            $arg .= $key . "=" . urlencode($val) . "&";
        }
        // 去掉最后一个&字符
        $arg = substr($arg, 0, count($arg) - 2);
        
        // 如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }
        
        return $arg;
    }

    /**
     * 签名字符串
     *
     * @param $prestr 需要签名的字符串
     * @param $key 私钥 return 签名结果
     */
    public function md5Sign($prestr, $key) {
        $prestr = $prestr . $key;
        return md5($prestr);
    }

    /**
     * 验证签名
     *
     * @param $prestr 需要签名的字符串
     * @param $sign 签名结果
     * @param $key 私钥 return 签名结果
     */
    public function md5Verify($prestr, $sign, $key) {
        $prestr = $prestr . $key;
        $mysgin = md5($prestr);
        
        if ($mysgin == $sign) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取返回时的签名验证结果
     *
     * @param $para_temp 通知返回来的参数数组
     * @param $sign 返回的签名结果
     * @return 签名验证结果
     */
    public function getSignVeryfy($para_temp, $sign) {
        // 除去待签名参数数组中的空值和签名参数
        $para_filter = $this->paraFilter($para_temp);
        
        // 对待签名参数数组排序
        $para_sort = $this->argSort($para_filter);
        
        // 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->createLinkstring($para_sort);
        
        $isSgin = false;
        switch (strtoupper(trim($this->alipay_config['sign_type']))) {
            case "MD5":
                $isSgin = $this->md5Verify($prestr, $sign, 
                    $this->alipay_config['key']);
                break;
            default:
                $isSgin = false;
        }
        
        return $isSgin;
    }

    /**
     * 远程获取数据，GET模式 注意： 1.使用Crul需要修改服务器中php.ini文件的设置，找到php_curl.dll去掉前面的";"就行了
     * 2.文件夹中cacert.pem是SSL证书请保证其路径有效，目前默认路径是：getcwd().'\\cacert.pem'
     *
     * @param $url 指定URL完整路径地址
     * @param $cacert_url 指定当前工作目录绝对路径 return 远程输出的数据
     */
    public function getHttpResponseGET($url, $cacert_url) {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, 0); // 过滤HTTP头
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 显示输出结果
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true); // SSL证书认证
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); // 严格认证
        curl_setopt($curl, CURLOPT_CAINFO, $cacert_url); // 证书地址
        $responseText = curl_exec($curl);
        // var_dump( curl_error($curl) );//如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
        curl_close($curl);
        
        return $responseText;
    }

    /**
     * 远程获取数据，GET模式 注意： 1.使用Crul需要修改服务器中php.ini文件的设置，找到php_curl.dll去掉前面的";"就行了
     * 2.文件夹中cacert.pem是SSL证书请保证其路径有效，目前默认路径是：getcwd().'\\cacert.pem'
     *
     * @param $url 指定URL完整路径地址
     * @param $cacert_url 指定当前工作目录绝对路径 return 远程输出的数据
     */
    public function getHttpResponse($url, $cacert_url) {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, 0); // 过滤HTTP头
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 显示输出结果
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true); // SSL证书认证
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); // 严格认证
        curl_setopt($curl, CURLOPT_CAINFO, $cacert_url); // 证书地址
        $responseText = curl_exec($curl);
        // var_dump( curl_error($curl) );//如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
        curl_close($curl);
        
        return $responseText;
    }

    /**
     * 获取远程服务器ATN结果,验证返回URL
     *
     * @param $notify_id 通知校验ID
     * @return 服务器ATN结果 验证结果集： invalid命令参数不对 出现这个错误，请检测返回处理中partner和key是否为空 true
     *         返回正确信息 false 请检查防火墙或者是服务器阻止端口问题以及验证时间是否超过一分钟
     */
    public function getResponse($notify_id) {
        $transport = strtolower(trim($this->alipay_config['transport']));
        $partner = trim($this->alipay_config['partner']);
        $veryfy_url = '';
        if ($transport == 'https') {
            $veryfy_url = $this->https_verify_url;
        } else {
            $veryfy_url = $this->http_verify_url;
        }
        $veryfy_url = $veryfy_url . "partner=" . $partner . "&notify_id=" .
             $notify_id;
        $responseTxt = $this->getHttpResponse($veryfy_url, 
            $this->alipay_config['cacert']);
        
        return $responseTxt;
    }
}
