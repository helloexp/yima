<?php

/**
 * 微信支付模块
 *
 * @author chensf
 */
class PayCMAction extends BaseAction {

    public $orderData = array();

    public $groupPhone = '';

    public $notifyUrl = '';
    // 异步通知地址
    public $callbackUrl = '';
    // 同步通知地址
    public $pay_fee_rate = 0;
    // 支付费率
    public $split_min_day = 4;
    // 分润天数
    public $characterSet = '';
    // 字符集
    public $signType = '';
    // 签名方式
    public $type = '';
    // 接口类型
    public $version = '';
    // 版本号
    public $currency = '';
    // 币种
    public $period = '';
    // 订单有效期数量
    public $periodUnit = '';
    // 有效期单位
    public $node_short_name = '';

    public $merchantId;

    public $key;

    public $CMpayService;

    public function _initialize() {
        $this->callbackUrl = U('Label/PayCM/verifyReturn', '', '', '', TRUE);
        $this->notifyUrl = C('CURRENT_HOST') . 'cmpay_notify.php';
        $this->CMpayService = D('CMpay', 'Service');
        $this->merchantId = C('CMPAY.merchantId');
        $this->characterSet = C('CMPAY.characterSet');
        $this->signType = C('CMPAY.signType');
        $this->key = C('CMPAY.key');
        $this->type = C('CMPAY.type');
        $this->version = C('CMPAY.version');
        $this->currency = C('CMPAY.currency');
        $this->period = C('CMPAY.period');
        $this->periodUnit = C('CMPAY.periodUnit');
        // 判断是否登录
        $this->groupPhone = session('groupPhone');

    }

    public function OrderPay($orderId = null) {
        
        $orderId = empty($orderId) ? I('order_id', null, 
            'mysql_real_escape_string') : $orderId;
        if (empty($orderId)) {
            $this->error('参数错误');
        }
        // 获取订单信息
        $this->orderData = D('StoreOrder')->getOrderInfo($orderId);
        if(false === $this->orderData){
            $msg = D('StoreOrder')->getError();
            $this->error($msg);
        }
        $this->cmpay($orderId);
    }

    /**
     * 联动优势支付
     */
    public function cmpay($orderId) {
        $requestData = array(
            'characterSet' => $this->characterSet,  // 字符集
            'callbackUrl' => $this->callbackUrl,  // 页面返回地址
            'notifyUrl' => $this->notifyUrl,  // 后台通知地址
            'ipAddress' => get_client_ip(),  // IP地址
            'merchantId' => $this->merchantId,  // 商户编号
            'requestId' => get_sn(),  // 商户请求号
            'signType' => $this->signType,  // 签名方式
            'type' => $this->type,  // 接口类型
            'version' => $this->version,  // 版本号
            'amount' => ceil($this->orderData['order_amt'] *  100), //订单金额
            //'amount' => 1,  // 订单金额
            'bankAbbr' => '', 
            'currency' => $this->currency,  // 币种
            'orderDate' => date('Ymd'),  // 订单提交日期
            'orderId' => $orderId,  // 商户订单号
            'merAcDate' => date('Ymd'),  // 商户会计日期
            'period' => $this->period,  // 订单有效期数量
            'periodUnit' => $this->periodUnit,  // 有效期单位
            'merchantAbbr' => '',  // 商户展示名称
            'productDesc' => $this->CMpayService->encodeString('商品描述01'),  // 商品描述
            'productId' => $this->CMpayService->encodeString('商品描述01'),  // 所购买商品的编号
            'productName' => $this->CMpayService->encodeString($this->orderData['group_goods_name']),  // 商品名称
            'productNum' => $this->orderData['buy_num'],  // 商品数量
            'reserved1' => $this->CMpayService->encodeString('商品描述01'),  // 商户保留字段1
            'reserved2' => $this->CMpayService->encodeString('商品描述01'),  // 商户保留字段2
            'userToken' => $this->orderData['receiver_phone'],  // 待支付手机号或和包账户昵称
            'showUrl' => '',  // 商品展示短链接地址
            'couponsFlag' => ''); // 营销工具使用控制 00 使用全部营销工具(默 讣) 10丌支持使用电子券
                                  // 20丌支持代金券 30-丌支持积分 40-丌支持所有营销工具
        log_write('send: Info=>' . json_encode($requestData));
        $signData = implode('', $requestData); // 组织签名数据
        
        log_write('send INFO: data=>' . $signData);
        $requestData["hmac"] = $this->CMpayService->MD5sign($signData); // 取得hmac数据
                                                                        // http请求到手机支付平台
        $sTotalString = $this->CMpayService->postData($requestData);
        $recv = $sTotalString["MSG"];
        $recvArray = $this->CMpayService->parseRecv($recv);
        
        $code = $recvArray["returnCode"];
        log_write('return INFO: ' . json_encode($recvArray));
        if ($code != "000000") {
            echo "code:" . $code . "</br>msg:" .
                 $this->CMpayService->decodeString($recvArray["message"]);
            exit();
        } else {
            $vfsign = $recvArray["merchantId"] . $recvArray["requestId"] .
                 $recvArray["signType"] . $recvArray["type"] .
                 $recvArray["version"] . $recvArray["returnCode"] .
                 $recvArray["message"] . $recvArray["payUrl"];
            $hmac = $this->CMpayService->MD5sign($vfsign);
            $vhmac = $recvArray["hmac"];
            if ($hmac != $vhmac) {
                echo "验证签名失败!";
                exit();
            } else {
                $payUrl = $recvArray["payUrl"];
                // 返回url处理
                log_write('信息发送成功: data=>' . json_encode($recvArray));
                $rpayUrl = $this->CMpayService->parseUrl($payUrl);
                redirect($rpayUrl['url']);
            }
        }
    }

    /**
     * 处理联动同步回传数据 只判断交易状态trade_state 跳转页面
     */
    public function verifyReturn() {
        log_write("verifyReturn:" . $_SERVER["REQUEST_URI"]);
        
        // parse_str($_SERVER['QUERY_STRING'], $data);
        $data = I('post.');
        log_write("verifyReturn INFO:" . json_encode($data));
        // 接收手机支付平台后台通知数据start
        
        // 组装签字符串
        $signData = $this->CMpayService->returnSignData($data);
        
        // MD5方式签名
        $hmac = $this->CMpayService->MD5sign($signData);
        
        // 此处000000仅代表程序无错误。订单是否支付成功是以支付结果（status）为准
        if ($data["returnCode"] != 000000) {
            echo $data["returnCode"] .
                 $this->CMpayService->decodeString($data["message"]);
        }
        if ($hmac != $data["hmac"]) {
            log_write("[error]:验签失败");
        } else {
            
            $status = $data["returnCode"];
            $result = false;
            if('000000' == $status)
            {
                $result = true;
            }
            //订单处理
            D('StoreOrder')->getVerifyOrderInfo($data["orderId"], 6, $result);
            exit;
        }
        
    }

    /**
     * 处理联动优势异步回传数据 验证该回传数据是否是联动优势 取的联动优势回传的支付状态 如果支付成功则返回订单信息进行生成凭证的后续工作
     * 如果支付失败则返回FALSE
     */
    public function verifyNotify() {
        log_write("verifyNotify:" . $_SERVER["REQUEST_URI"]);
        // parse_str($_SERVER['QUERY_STRING'], $data);
        $data = I('post.');
        log_write("verifyNotify INFO:" . json_encode($data));
        // 接收手机支付平台后台通知数据start
        
        if ($data["returnCode"] != 000000) {
            // 此处表示后台通知产生错误
            log_write("verifyNotify Return Info： 和包支付失败返回接口交易失败信息" . $this->CMpayService->decodeString($data["message"]) . ' 失败交易码：' . $data["returnCode"]);
            echo "fail";
            exit();
        }
        
        // 组装签字符串
        $signData = $this->CMpayService->returnSignData($data);
        
        $hmac = $this->CMpayService->MD5sign($signData);
        if ($hmac != $data["hmac"]) {
            // 此处无法信息数据来自手机支付平台
            log_write("verifyNotify Return Info:验证加密码失败");
            echo "fail";
        } else {
            log_write("verifyNotify PAY INFO:" . json_encode($data));
            $info = array();
            $info['order_seq'] = $data['orderId']; // 外部交易号
            
            $info['transaction_id'] = $data['payNo']; // 交易号
            
            $result = D('StoreOrder')->verifyOrderInfo($info['order_seq'], 6, $data['transaction_id'], false);
            if (true == $result) {
                echo "success";
                // tag('view_end');
                log_write("[success]和包支付，订单号 ：" . $data['order_seq'] . " 支付成功!");
            } else {
                echo "fail";
                log_write("[error]" . D('StoreOrder')->getError());
            }
            exit();
        }
    }
    
    /**
     * 和包支付页面
     */
    public function payInfo() {
        $id = I('id');
        $pageId = I('pageId');
        // 更新访问量
        import('@.Vendor.DataStat');
        $opt = new DataStat($id, $this->full_id);
        $opt->recordSeq();

        //查找商品信息
        $Info = M('tecshop_goods_ex')->field('b_id, m_id')
            ->where("label_id='" . $id . "'")
            ->find();
        if(!$Info){
            $this->error('商品不存在');
        }
        
        $bId = $Info['b_id'];
        $mId = $Info['m_id'];
        $where = array(
            "e.b_id" => $bId, 
            "e.m_id" => $mId,
            'b.status' => 0,
            'b.end_time' => array(
                'egt',date('YmdHis')
            ));
        $goodsInfo = M('tecshop_goods_ex e ')->join(
            "tbatch_info b ON b.id=e.b_id")
            ->join('tgoods_info gi ON gi.goods_id = b.goods_id')
            ->join('tmarketing_info m ON b.m_id = m.id')
            ->where($where)
            ->order("e.is_commend,b.id desc ")
            ->field(
            'e.*, b.*, gi.config_data, gi.is_sku, gi.is_order, m.batch_type as store_type')
            ->find();

        if(!$goodsInfo){
            $this->error('商品已下架');
        }

        // 取得门店信息
        $goodsM = D('Goods');
        $storeList = $goodsM->getGoodsShop($goodsInfo['goods_id'], true, nodeIn($goodsInfo['node_id']));
        $goodsM->recentLookGoods($this->id);
        //门店类型 1普通门店 2系统自建默认门店 3线上门店 4验证助手默认门店
        //排除3与4
        foreach($storeList as $k=>$v){
            if($v['type'] == 3 || $v['type'] == 4 || $v['type'] == 2){
                unset($storeList[$k]);
            }
        }
        shuffle($storeList);//删除键名重新排序，
        
        $area = $this->get_user_city();//获取用户所在省市
        
        $userData = array();//存放该城市的所有门店数据
        foreach($storeList as $k=>$v){
            if($v['city'] == $area['city']){
                $userData[] = $v;
            }
        }
        $this->setStoreList($storeList);
        
        $provinces = array();//下拉框中的省
        foreach($storeList as $v=>$k){
            $provinces[]=$k['province'];
        }
        
        $provinces = array_flip(array_flip($provinces)); //去空值
        $provinces=array_filter ($provinces);//去重复值

        $this->assign('provinces',$provinces);//所有的省
        $this->assign('userArea',$area);//用户所在省市
        $this->assign('userData',$userData);//所在城市的所有数据
        $this->assign('goodsInfo', $goodsInfo);
        $this->assign('pageId', $pageId);
        $this->assign('batchChannelId', $id);
        $this->display();
    }

    public function  setStoreList($storeList)
    {
        import('@.Vendor.RedisHelper');
        $this->redis = RedisHelper::getInstance();
        $this->redis->set('storeList:'.$this->node_id, $storeList);
    }

    public function getStoreList()
    {
        import('@.Vendor.RedisHelper');
        $this->redis = RedisHelper::getInstance();
        return $this->redis->get('storeList:'.$this->node_id);
    }

    public function __destruct() {
        unset($this->CMpayService);
    }
    
    
    public function get_user_city()
    {
        static $city_code = null;
    
        if($city_code != null)
            return $city_code;
    
            //$userIp = GetIP();
            $userIp = get_client_ip();
            $userIp = '114.80.166.240';

            //2秒超时
            $ctx = stream_context_create(array(
                    'http' => array(
                    'timeout' => 2
                )
            ));
    
            $result = @file_get_contents('http://int.dpool.sina.com.cn/iplookup/iplookup.php?ip='.$userIp, 0, $ctx);

            $result = iconv('GBK', 'UTF-8', $result);
            if($result === false){
                return false;
            }

            $info = explode("\t", $result);
            if($info[0] == -1 || !isset($info[5])){
                return false;
            }

            $area['pro'] = $info[4];
            $area['city'] = $info[5].'市';

/*                    $info = D('tcity_code')->where("city_level = '2' and city like '{$city}%'")->limit(1)->select();

                if(!$info)
                    return false;

                    $city_code = $info[0]['city_code']; */
            return $area;
    }
}
