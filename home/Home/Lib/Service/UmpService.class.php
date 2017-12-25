<?php

class UmpService {

    protected $map = null;

    public function __construct() {
        require_once ("UmpApi/mer2Plat.php");
        $this->map = new HashMap();
    }

    /**
     * $config=array( 'order_id'=>'订单号，变长32位', 'mer_date'=>'订单日期,YYYYMMDD',
     * 'amount'=>'转账金额，单位：元', 'recv_account_type'=>'账户类型：00是银行，02是U付',
     * 'recv_bank_acc_pro'=>'对私：0，对公：1', 'recv_account'=>'收款方帐号',
     * 'recv_user_name'=>'收款方户名', 'recv_gate_id'=>'收款方银行卡的所属银行,选银行卡时必填',
     * 'bank_brhname'=>'开户行支行全称，选银行卡时必填', 'purpose'=>'付款原因，摘要，选银行卡时必填',
     * ------------以上必填，以下选填---------- 'identity_type'=>'收款方证件类型，01为身份证，选填',
     * 'identity_code'=>'证件号码，选填', 'identity_holder'=>'证件持有者的真实姓名，选填',
     * 'media_type'=>'平台预留的何物，选填', 'media_id'=>'预留的手机号码，选填',
     * 'prov_name'=>'省，选填', 'city_name'=>'市，选填',
     * 'checkFlag'=>'是否实时付款，默认非实时：1，如需实时付款，要开通服务，填0，选填',
     * 'mobile_no'=>'收款方手机号码,需要开通此功能，以便发短信,选填' )
     */
    public function transferAccount($config) {
        $this->map->put('service', 'transfer_direct_req');
        $this->map->put('charset', 'UTF-8');
        $this->map->put('mer_id', '9019');
        $this->map->put('sign_type', 'RSA');
        $this->map->put('notify_url', 
            'http://' . C('DOMAIN_FIRST') .
                 '.wangcaio2o.com/index.php?g=CronJob&m=Upay&a=recv');
        $this->map->put('res_format', 'HTML');
        $this->map->put('version', '4.0');
        $this->map->put('order_id', $config['order_id']);
        $this->map->put('mer_date', $config['mer_date']);
        $this->map->put('amount', $config['amount'] * 100);
        $this->map->put('recv_account_type', $config['recv_account_type']);
        $this->map->put('recv_bank_acc_pro', $config['recv_bank_acc_pro']);
        $this->map->put('recv_account', $config['recv_account']);
        $this->map->put('recv_user_name', $config['recv_user_name']);
        $this->map->put('identity_type', $config['identity_type']); // 收款方证件类型，01为身份证
        $this->map->put('identity_code', $config['identity_code']);
        $this->map->put('identity_holder', $config['identity_holder']);
        $this->map->put('media_type', $config['media_type']);
        $this->map->put('media_id', $config['media_id']);
        $this->map->put('recv_gate_id', $config['recv_gate_id']);
        $this->map->put('purpose', $config['purpose']);
        $this->map->put('prov_name', $config['prov_name']);
        $this->map->put('city_name', $config['city_name']);
        $this->map->put('bank_brhname', $config['bank_brhname']);
        $this->map->put('checkFlag', $config['checkFlag']);
        $this->map->put('mobile_no', $config['mobile_no']);
        $ret = self::getContent($this->map, 4);
        return $ret;
    }

    /**
     * [getOrderStatus 查询付款订单状态]
     *
     * @param [type] $orderId [订单号]
     * @param [type] $merData [订单日期]
     * @return [type] [description]
     */
    public function getOrderStatus($orderId, $merData) {
        $this->map->put('service', 'transfer_query');
        $this->map->put('charset', 'UTF-8');
        $this->map->put('mer_id', '9019');
        $this->map->put('res_format', 'HTML');
        $this->map->put('version', '4.0');
        $this->map->put('sign_type', 'RSA');
        $this->map->put('order_id', $orderId);
        $this->map->put('mer_date', $merData);
        $ret = self::getContent($this->map, 3);
        return $ret;
    }

    /**
     * [getSettle 付款数据对账]
     *
     * @param string $curDay [当前日期，对账日期，默认为今日]
     * @return [type] [返回账单详情]
     */
    public function getSettle($curDay = "") {
        if (! $curDay) {
            $curDay = date('Ymd');
        }
        $this->map->put('service', 'download_settle_file');
        $this->map->put('mer_id', '9019');
        $this->map->put('version', '4.0');
        $this->map->put('sign_type', 'RSA');
        $this->map->put('settle_date', $curDay);
        $this->map->put('settle_type', 'ENPAY');
        $ret = self::getContent($this->map, 2);
        return $ret;
    }

    /**
     * [getBalance 获取余额]
     *
     * @return [type] [返回的是查询的结果（数组）]
     */
    public function getBalance() {
        $this->map->put('service', 'query_account_balance');
        $this->map->put('charset', 'UTF-8');
        $this->map->put('mer_id', '9019');
        $this->map->put('res_format', 'HTML');
        $this->map->put('version', '4.0');
        $this->map->put('sign_type', 'RSA');
        $ret = self::getContent($this->map, 1);
        return $ret;
    }

    /**
     * [getContent 获取content内容]
     *
     * @param [type] $output [数据]
     * @return [type] [查询结果（数组）]
     */
    private function getContent($map, $type) {
        $reqData = MerToPlat::makeRequestDataByGet($map);
        $url = $reqData->getUrl();
        $output = file_get_contents($url);
        $result = array();
        switch ($type) {
            case '1':
                {
                    $meta_header = "<META NAME=\"MobilePayPlatform\" CONTENT=\"";
                    $s = strpos($output, $meta_header);
                    $e = strpos($output, "\">", $s + 1);
                    $content = substr($output, $s + strlen($meta_header), 
                        $e - $s - strlen($meta_header));
                    log_write("请求联动优势：" . $url . ";返回结果：" . $content);
                    $contentTmpArr = explode('&', $content);
                    foreach ($contentTmpArr as $key => $value) {
                        $tmpArr = array();
                        $tmpArr = explode('=', $value);
                        $result[$tmpArr[0]] = $tmpArr[1];
                    }
                    unset($result['sign']); // 屏蔽签名
                    $result['bal_sign'] = intval($result['bal_sign']) * 0.01; // 余额处理
                }
                break;
            
            case '2':
                {
                    $output = iconv("GBK", "utf-8", $output);
                    log_write("请求联动优势：" . $url . ";返回结果：" . $output);
                    $content = array_filter(explode("\r\n", $output));
                    $contentTmpArr = array();
                    $count = count($content); // 数组的元素个数
                    $firstLineArr = explode(',', $content[0]); // 首行转化成数组
                    $lasgLineArr = explode(',', $content[$count - 1]); // 尾行转化成数组
                    $contentTmpArr['mer_id'] = $firstLineArr[1]; // 商户号
                    $contentTmpArr['transfer_settle_date'] = $firstLineArr[2]; // 对账日期
                    $contentTmpArr['version'] = $firstLineArr[3]; // 版本号
                    $contentTmpArr['ret_code'] = $firstLineArr[4]; // 返回码
                    $contentTmpArr['ret_msg'] = $firstLineArr[5]; // 返回信息
                    $contentTmpArr['total_count'] = ($count > 2 ? $lasgLineArr[3] : 0); // 总笔数
                    $contentTmpArr['total_amount'] = ($count > 2 ? $lasgLineArr[4] *
                         0.01 : 0); // 总金额
                    unset($content[$count - 1]);
                    unset($content[0]);
                    if (! empty($content)) {
                        foreach ($content as $key => $value) {
                            $tmpArr = array();
                            $tmpArr = explode(',', $value);
                            $contentTmpArr['detail_data'][] = array(
                                'mer_id' => $tmpArr[0],  // 商户号
                                'fun_code' => $tmpArr[1],  // 交易类型
                                'order_id' => $tmpArr[2],  // 订单号
                                'mer_date' => $tmpArr[3],  // 订单日期
                                'amount' => $tmpArr[4] * 0.01,  // 付款金额
                                'fee_amount' => $tmpArr[5] * 0.01,  // 转账手续费
                                'transfer_settle_date' => $tmpArr[6],  // 对账日期
                                'transfer_acc_date' => $tmpArr[7],  // 记账日期
                                'trans_state' => $tmpArr[8],  // 交易状态
                                'plat_trace' => $tmpArr[9]); // 联动流水号
                        
                        }
                    }
                    $result = $contentTmpArr;
                }
                break;
            
            case '3':
                {
                    $meta_header = "<META NAME=\"MobilePayPlatform\" CONTENT=\"";
                    $s = strpos($output, $meta_header);
                    $e = strpos($output, "\">", $s + 1);
                    $content = substr($output, $s + strlen($meta_header), 
                        $e - $s - strlen($meta_header));
                    log_write("请求联动优势：" . $url . ";返回结果：" . $content);
                    $contentTmpArr = explode('&', $content);
                    foreach ($contentTmpArr as $key => $value) {
                        $tmpArr = array();
                        $tmpArr = explode('=', $value);
                        $result[$tmpArr[0]] = $tmpArr[1];
                    }
                    unset($result['sign']); // 屏蔽签名
                    $result['amount'] = intval($result['amount']) * 0.01;
                    $result['fee'] = intval($result['fee']) * 0.01;
                }
                break;
            case '4':
                {
                    $meta_header = "<META NAME=\"MobilePayPlatform\" CONTENT=\"";
                    $s = strpos($output, $meta_header);
                    $e = strpos($output, "\">", $s + 1);
                    $content = substr($output, $s + strlen($meta_header), 
                        $e - $s - strlen($meta_header));
                    log_write("请求联动优势：" . $url . ";返回结果：" . $content);
                    $contentTmpArr = explode('&', $content);
                    foreach ($contentTmpArr as $key => $value) {
                        $tmpArr = array();
                        $tmpArr = explode('=', $value);
                        $result[$tmpArr[0]] = $tmpArr[1];
                    }
                    unset($result['sign']); // 屏蔽签名
                    $result['amount'] = intval($result['amount']) * 0.01;
                    $result['fee'] = intval($result['fee']) * 0.01;
                }
                break;
            default:
                
                break;
        }
        
        return $result;
    }
}


