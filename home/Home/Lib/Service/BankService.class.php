<?php

class BankService {

    protected $url = "";

    protected $timeout = "";

    protected $xml = null;

    public function __construct() {
        // 交行CS服务地址
        // C('BCM_SERV_URL','http://192.168.0.175:8899');//生产环境
        C('BCM_SERV_URL', 'http://10.10.1.214:8899'); // 测试环境
                                                      // 请求超时设置
        C('BCM_REQ_TIME', '30');
        // 本公司交行账号
        C('MY_BCM_AC', '310066726018170188809');
        // 本公司账户名称
        C('MY_BCM_ACNAME', '上海华谊集团财务有限责任公司');
        // 本公司企业代码
        C('MY_BCM_CORPNO', '0000069659');
        // 本企业用户号
        C('MY_BCM_USERNO', '00003');
        $this->url = C('BCM_SERV_URL') or die('[BCM_SERV_URL]参数未设置');
        $this->timeout = C('BCM_REQ_TIME') or $this->timeout = 30; // 超时秒数
        import('@.ORG.Util.Xml') or die('@.ORG.Util.Xml导入失败');
        $this->xml = new Xml();
    }

    /**
     * [transferAccounts 对外转账汇款]
     *
     * @param [type] $data [报文信息]
     * @param string &$errorInfo [交易错误信息]
     * @return [type] [BOOL]
     */
    public function transferAccounts($data, &$errorInfo = "") {
        $resultArr = self::getResultFromApi('210201', $data);
        if ($resultArr['ap']['head']['particular_code'] != '0000') {
            $errorInfo = $resultArr['ap']['head']['particular_info'];
            return false; // 交易失败，返回false
        }
        return true; // 交易成功，返回true
    }

    /**
     * [getAccountInfo 获取账户信息]
     *
     * @param [type] $data [报文信息]
     * @param string &$errorInfo [交易错误信息]
     * @return [type] [返回false或者数组]
     */
    public function getAccountInfo(&$errorInfo = "") {
        $resultArr = self::getResultFromApi('310101', "");
        if ($resultArr['ap']['head']['particular_code'] != '0000') {
            $errorInfo = iconv('gbk', 'utf-8', 
                $resultArr['ap']['head']['ans_info']);
            return false; // 查询失败返回false
        }
        $accountInfo = explode('|', 
            iconv('gbk', 'utf-8', $resultArr['ap']['body']['serial_record']));
        $accountInfoNew = array();
        $accountInfoNew['accountName'] = $accountInfo[10]; // 户名
        $accountInfoNew['accountNo'] = $accountInfo[11]; // 账号
        $accountInfoNew['currencyCode'] = $accountInfo[12]; // 币种
        $accountInfoNew['remainBalance'] = $accountInfo[13]; // 余额
        $accountInfoNew['availableBalance'] = $accountInfo[14]; // 可用余额
        $accountInfoNew['openingDate'] = $accountInfo[15]; // 开户日期
        $accountInfoNew['accountType'] = $accountInfo[16]; // 账户类型
        $accountInfoNew['bankOfDeposit'] = $accountInfo[17]; // 开户行
        $accountInfoNew['errorInfo'] = $accountInfo[18]; // 错误信息
        $accountInfoNew['status'] = $accountInfo[19]; // 成功标志
        return $accountInfoNew;
    }

    /**
     * [getTradingInfo 查询历史交易信息]
     *
     * @param [type] $data [时间段]
     * @param string &$errorInfo [错误信息]
     * @return [type] [false或者数组]
     */
    public function getTradingInfo($data, &$errorInfo = "") {
        $resultArr = self::getResultFromApi('310301', $data);
        if ($resultArr['ap']['head']['particular_code'] != '0000') {
            $errorInfo = iconv('gbk', 'utf-8', 
                $resultArr['ap']['head']['ans_info']);
            return false; // 查询失败返回false
        }
        $accountInfo = explode('|', 
            iconv('gbk', 'utf-8', $resultArr['ap']['body']['serial_record']));
        $accountInfoNew = array();
        for ($i = 0; $i < $resultArr['ap']['body']['record_num']; $i ++) {
            $accountInfoNew[$i]['status'] = $accountInfo[($i + 1) * 24 + 0]; // 状态
            $accountInfoNew[$i]['tradingDate'] = $accountInfo[($i + 1) * 24 + 1]; // 交易日期
            $accountInfoNew[$i]['tradingTime'] = $accountInfo[($i + 1) * 24 + 2]; // 交易时间
            $accountInfoNew[$i]['typeOfService'] = $accountInfo[($i + 1) * 24 + 3]; // 业务类型
            $accountInfoNew[$i]['serialNumber'] = $accountInfo[($i + 1) * 24 + 4]; // 流水号
            $accountInfoNew[$i]['flowSeqNumber'] = $accountInfo[($i + 1) * 24 + 5]; // 流水序号
            $accountInfoNew[$i]['account'] = $accountInfo[($i + 1) * 24 + 6]; // 账号
            $accountInfoNew[$i]['accountName'] = $accountInfo[($i + 1) * 24 + 7]; // 户名
            $accountInfoNew[$i]['balancePayments'] = $accountInfo[($i + 1) * 24 +
                 8]; // 收支标志
            $accountInfoNew[$i]['currencyCode'] = $accountInfo[($i + 1) * 24 + 9]; // 币种
            $accountInfoNew[$i]['tradingAmt'] = $accountInfo[($i + 1) * 24 + 10]; // 交易金额
            $accountInfoNew[$i]['remainAmt'] = $accountInfo[($i + 1) * 24 + 11]; // 剩余金额
            $accountInfoNew[$i]['availableAmt'] = $accountInfo[($i + 1) * 24 + 12]; // 可用余额
            $accountInfoNew[$i]['otherAccount'] = $accountInfo[($i + 1) * 24 + 13]; // 对方账号
            $accountInfoNew[$i]['otherAccountName'] = $accountInfo[($i + 1) * 24 +
                 14]; // 对方户名
            $accountInfoNew[$i]['otherAdress'] = $accountInfo[($i + 1) * 24 + 15]; // 对方地址
            $accountInfoNew[$i]['otherOpenBank'] = $accountInfo[($i + 1) * 24 +
                 16]; // 对方开户行行
            $accountInfoNew[$i]['otherOpenBankName'] = $accountInfo[($i + 1) * 24 +
                 17]; // 对方开户行行
            $accountInfoNew[$i]['typeOfBill'] = $accountInfo[($i + 1) * 24 + 18]; // 票据种类
            $accountInfoNew[$i]['billNo'] = $accountInfo[($i + 1) * 24 + 19]; // 票据号码
            $accountInfoNew[$i]['billName'] = $accountInfo[($i + 1) * 24 + 20]; // 票据名称
            $accountInfoNew[$i]['billDate'] = $accountInfo[($i + 1) * 24 + 21]; // 票据签发日
            $accountInfoNew[$i]['summary'] = $accountInfo[($i + 1) * 24 + 22]; // 附言
            $accountInfoNew[$i]['bak'] = $accountInfo[($i + 1) * 24 + 23]; // 备注
        }
        
        return $accountInfoNew;
    }

    /**
     * [getTodayTradingInfo 获取当天的交易记录]
     *
     * @param string &$errorInfo [错误信息]
     * @return [type] [false或者数组]
     */
    public function getTodayTradingInfo(&$errorInfo = "") {
        $resultArr = self::getResultFromApi('310201', "");
        if ($resultArr['ap']['head']['particular_code'] != '0000') {
            $errorInfo = iconv('gbk', 'utf-8', 
                $resultArr['ap']['head']['ans_info']);
            return false; // 查询失败返回false
        }
        $accountInfo = explode('|', 
            iconv('gbk', 'utf-8', $resultArr['ap']['body']['serial_record']));
        $accountInfoNew = array();
        for ($i = 0; $i < $resultArr['ap']['body']['record_num']; $i ++) {
            $accountInfoNew[$i]['status'] = $accountInfo[($i + 1) * 24 + 0]; // 状态
            $accountInfoNew[$i]['tradingDate'] = $accountInfo[($i + 1) * 24 + 1]; // 交易日期
            $accountInfoNew[$i]['tradingTime'] = $accountInfo[($i + 1) * 24 + 2]; // 交易时间
            $accountInfoNew[$i]['typeOfService'] = $accountInfo[($i + 1) * 24 + 3]; // 业务类型
            $accountInfoNew[$i]['serialNumber'] = $accountInfo[($i + 1) * 24 + 4]; // 流水号
            $accountInfoNew[$i]['flowSeqNumber'] = $accountInfo[($i + 1) * 24 + 5]; // 流水序号
            $accountInfoNew[$i]['account'] = $accountInfo[($i + 1) * 24 + 6]; // 账号
            $accountInfoNew[$i]['accountName'] = $accountInfo[($i + 1) * 24 + 7]; // 户名
            $accountInfoNew[$i]['balancePayments'] = $accountInfo[($i + 1) * 24 +
                 8]; // 收支标志
            $accountInfoNew[$i]['currencyCode'] = $accountInfo[($i + 1) * 24 + 9]; // 币种
            $accountInfoNew[$i]['tradingAmt'] = $accountInfo[($i + 1) * 24 + 10]; // 交易金额
            $accountInfoNew[$i]['remainAmt'] = $accountInfo[($i + 1) * 24 + 11]; // 剩余金额
            $accountInfoNew[$i]['availableAmt'] = $accountInfo[($i + 1) * 24 + 12]; // 可用余额
            $accountInfoNew[$i]['otherAccount'] = $accountInfo[($i + 1) * 24 + 13]; // 对方账号
            $accountInfoNew[$i]['otherAccountName'] = $accountInfo[($i + 1) * 24 +
                 14]; // 对方户名
            $accountInfoNew[$i]['otherAdress'] = $accountInfo[($i + 1) * 24 + 15]; // 对方地址
            $accountInfoNew[$i]['otherOpenBank'] = $accountInfo[($i + 1) * 24 +
                 16]; // 对方开户行行
            $accountInfoNew[$i]['otherOpenBankName'] = $accountInfo[($i + 1) * 24 +
                 17]; // 对方开户行行
            $accountInfoNew[$i]['typeOfBill'] = $accountInfo[($i + 1) * 24 + 18]; // 票据种类
            $accountInfoNew[$i]['billNo'] = $accountInfo[($i + 1) * 24 + 19]; // 票据号码
            $accountInfoNew[$i]['billName'] = $accountInfo[($i + 1) * 24 + 20]; // 票据名称
            $accountInfoNew[$i]['billDate'] = $accountInfo[($i + 1) * 24 + 21]; // 票据签发日
            $accountInfoNew[$i]['summary'] = $accountInfo[($i + 1) * 24 + 22]; // 附言
            $accountInfoNew[$i]['bak'] = $accountInfo[($i + 1) * 24 + 23]; // 备注
        }
        
        return $accountInfoNew;
    }

    /**
     * [getRemainMoney 获取历史余额]
     *
     * @param [type] $data [时间段]
     * @param string &$errorInfo [错误信息]
     * @return [type] [false或者数组]
     */
    public function getRemainMoney($data, &$errorInfo = "") {
        $resultArr = self::getResultFromApi('310106', "");
        if ($resultArr['ap']['head']['particular_code'] != '0000') {
            $errorInfo = iconv('gbk', 'utf-8', 
                $resultArr['ap']['head']['ans_info']);
            return false; // 查询失败返回false
        }
        $accountInfo = explode('|', 
            iconv('gbk', 'utf-8', $resultArr['ap']['body']['serial_record']));
        $accountInfoNew = array();
        $accountInfoNew['accountName'] = $accountInfo[10]; // 户名
        $accountInfoNew['accountNo'] = $accountInfo[11]; // 账号
        $accountInfoNew['currencyCode'] = $accountInfo[12]; // 币种
        $accountInfoNew['remainBalance'] = $accountInfo[13]; // 余额
        $accountInfoNew['availableBalance'] = $accountInfo[14]; // 可用余额
        $accountInfoNew['openingDate'] = $accountInfo[15]; // 开户日期
        $accountInfoNew['accountType'] = $accountInfo[16]; // 账户类型
        $accountInfoNew['bankOfDeposit'] = $accountInfo[17]; // 开户行
        $accountInfoNew['errorInfo'] = $accountInfo[18]; // 错误信息
        $accountInfoNew['status'] = $accountInfo[19]; // 成功标志
        return $accountInfo;
    }

    /**
     * [getPublicHead 获取头部节点]
     *
     * @param [type] $trCode [交易类型]
     * @return [type] [xml头部]
     */
    private function getPublicHead($trCode) {
        $reqNo = time() . rand(10000, 99999);
        $trAcdt = date('Ymd');
        $trTime = date('His');
        $head = array(
            'tr_code' => $trCode,  // 交易类型
            'corp_no' => C('MY_BCM_CORPNO'),  // 企业代码
            'user_no' => C('MY_BCM_USERNO'),  // 企业用户号
            'req_no' => $reqNo,  // 发起方序号
            'tr_acdt' => $trAcdt,  // 交易年月日
            'tr_time' => $trTime,  // 交易时分秒
            'atom_tr_count' => '1',  // 原子交易数
            'channel' => '0',  // 渠道标志
            'reserved' => ''); // 保留字段
        
        return $head;
    }

    /**
     * [getBody 获取体]
     *
     * @param [type] $config [汇款信息参数]
     * @param [type] $type [交易类型]
     * @return [type] [返回体部]
     */
    private function getBody($config, $type) {
        $certNo = time() . rand(10000, 99999);
        if ($type == '310101') {
            $body = array(
                'acno' => C('MY_BCM_AC')); // 账号
        
        } else if ($type == '310201') {
            $body = array(
                'acno' => C('MY_BCM_AC')); // 账号
        
        } else if ($type == '310301') {
            $body = array(
                'acno' => C('MY_BCM_AC'),  // 账号
                'start_date' => $config['start_date'], 
                'end_date' => $config['end_date']);
        } else if ($type == '310106') {
            $body = array(
                'accNo' => C('MY_BCM_AC'),  // 账号
                'beginDate' => $config['beginDate'], 
                'endDate' => $config['endDate']);
        } else if ($type == '210201') {
            $body = array(
                'pay_acno' => C('MY_BCM_AC'),  // 付款人账号
                'pay_acname' => C('MY_BCM_ACNAME'),  // 付款人户名
                'rcv_bank_name' => $config['rcv_bank_name'],  // 收款方行名
                'rcv_acno' => $config['rcv_acno'],  // 收款人账号
                'rcv_acname' => $config['rcv_acname'],  // 收款人户名
                'rcv_exg_code' => '',  // 收款方交换号
                'rcv_bank_no' => '',  // 收款方联行号
                'cur_code' => 'CNY',  // 币种
                'amt' => $config['amt'],  // 金额
                'cert_no' => $certNo,  // 企业凭证编号
                'summary' => $config['summary'],  // 附言
                'bank_flag' => $config['bank_flag'],  // 0是同行交易，1是跨行
                'area_flag' => $config['area_flag']); // 0是同城交易，1是异地
        
        }
        return $body;
    }

    /**
     * [getResultFromApi xml报文提交取得结果]
     *
     * @param [type] $tradingType [交易类型]
     * @param [type] $data [参数]
     * @return [type] [应答报文结果]
     */
    private function getResultFromApi($tradingType, $data) {
        $header = self::getPublicHead($tradingType);
        $body = self::getBody($data, $tradingType);
        $sendData = array(
            'ap' => array(
                'head' => $header, 
                'body' => $body));
        $str = $this->xml->getXMLFromArrayNoHeader($sendData, 'gbk');
        $error = '';
        $resultXml = httpPost($this->url, $str, $error, 
            array(
                'TIMEOUT' => $this->timeout));
        $resultXml = iconv('gbk', 'utf-8', $resultXml);
        $resultArr = $this->xml->parse($resultXml);
        return $resultArr;
    }
}


