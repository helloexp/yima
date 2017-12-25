<?php
// 指令接口服务设置
class IndexAction extends BaseAction {

    public $alipay;

    public $node_id;

    public $alipay_public_key;
    // 公钥验签
    public $callback_url;

    public $app_id;

    public $alipay_acount;

    public $msg_type;
    // 消息流水表
    public $msg_info;
    // 消息流水表
    public $setting = array();

    public $req = array();

    public $_redisLink;

    /*
     * {"location":{"location_flag":"1","resp_count":"3","large_image":"00004488top.jpg","small_image":"00004488item.jpg"}}
     */
    public function _initialize() {
        $this->node_id = I('GET.node_id'); // 商户id
        if (C('ALIPAY_FWC.LOG_PATH'))
            C('LOG_PATH', C('ALIPAY_FWC.LOG_PATH'));
        
        C('CUSTOM_LOG_PATH', C('CUSTOM_LOG_PATH') . $this->node_id . '_');
        C('LOG_PATH', C('LOG_PATH') . $this->node_id . '_');
        log_write('node_id:' . $this->node_id);
        if (! $this->node_id) {
            echo "商户id不能为空";
            exit();
        }
    }

    /* 入口函数 */
    public function index() {
        $this->alipay = $alipay = D('AlipayFwc', 'Service');
        $alipay_info = M('tfwc_info')->where(
            array(
                "node_id" => $this->node_id))->find();
        // $alipay_json = $this->getAlipayInfoByKey('tfwc_info',$this->node_id);
        // $alipay_info = json_decode($alipay_json,true);
        log_write(print_r($alipay_info, true));
        $this->app_id = $alipay_info['app_id'];
        $this->alipay_public_key = $alipay_info['alipay_public_key'];
        $this->alipay_acount = $alipay_info['alipay_account'];
        $this->callback_url = $alipay_info['callback_url'];
        $this->setting = $alipay_info['setting'];
        if ($alipay_info['setting']) {
            $setting_tmp = str_replace('\\', '', $this->setting);
            $this->setting = json_decode($setting_tmp, true);
        }
        
        $this->alipay->init($this->app_id, $this->node_id, 
            $this->alipay_public_key, $this->setting);
        
        // 支付宝post数据
        $post_data = $_POST;
        // 去除反斜杠
        foreach ($post_data as $key => $value) {
            $post_data[$key] = stripslashes($value);
        }
        log_write('fwcdata after stripslashes:' . print_r($post_data, true));
        // 校验报文节点是否为空
        $valid_flag = $this->alipay->valid($post_data);
        if ($valid_flag === false) {
            echo "some parameter is empty.";
            log_write("服务窗报文某节点为空");
            exit();
        }
        log_write('valid data success');
        // 校验签名
        // $sign_verify = $as->rsaCheckV2
        // ($post_data,C('ALIPAY_FWC.merchant_public_key_file'));
        $sign_verify = $this->alipay->verify_sign($post_data, 
            $this->alipay_public_key);
        if (! $sign_verify) {
            /*
             * // 如果验证网关时，请求参数签名失败，则按照标准格式返回，方便在服务窗后台查看。 if
             * ($post_data['service'] == "alipay.service.check") {
             * $this->alipay->verifygw ( $post_data['biz_content'],false); }
             * else { echo "sign verfiy fail."; writeLog ( "sign verfiy fail.");
             * }
             */
            echo "sign verfiy fail.";
            log_write("sign verfiy fail.");
            exit();
        }
        log_write('fwcdata sign success');
        // biz_content=>array
        $this->req = $this->alipay->parseRequest($post_data['biz_content']);
        log_write('post_data array:' . print_r($this->req, true));
        // 校验appid
        if ($this->req['AppId'] != $alipay_info['app_id']) {
            echo "AppId error.";
            log_write(
                '支付宝报文appid和本地不一致:接口【' . $this->req['AppId'] . '】,本地【' .
                     $alipay_info['app_id'] . '】');
            exit();
        }
        // 开始解析指令
        if ($post_data['service'] == "alipay.mobile.public.message.notify") {
            // 处理收到的消息
            $this->alipay->doResp($this->req);
            $return_xml = $this->alipay->mkAckMsg($this->req['FromUserId']);
            echo $return_xml;
            exit();
        } else if ($post_data['service'] == "alipay.service.check") {
            // 处理收到的消息
            $this->alipay->verifygw($this->req, true);
            exit();
        }
        exit();
    }

    public function getAlipayInfoByKey($name, $key) {
        $res = $this->getRedis();
        $result = $res->hget($name, $key);
        return $result;
    }
    // 获取redis连接
    public function getRedis() {
        $this->_redisLink = new Redis();
        $config = C('REDIS') or die('CONFIG.REDIS is undefined');
        ;
        $this->_redisLink->connect($config['host'], $config['port']);
        return $this->_redisLink;
    }
}