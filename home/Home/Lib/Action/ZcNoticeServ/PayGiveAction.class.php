<?php

/* 主动通知接口 */
class PayGiveAction extends Action {

    public $ReqArr;

    public $transType;

    public $responseType;

    public $TradeNo;

    public $payType;//支付渠道 1支付宝支付 2微信支付

    /**
     * @var MemberBehaviorModel
     */
    public $MemberBehaviorModel;

    /**
     * @var IntegralConfigNodeModel
     */
    public $IntegralConfigNodeModel;

    public $INTEGRAL_BATCH_TYPE = CommonConst::BATCH_TYPE_INTEGRAL;
    // 积分商城活动类型
    public function index() {
        $this->MemberBehaviorModel = D('MemberBehavior');
        $this->IntegralConfigNodeModel = D('IntegralConfigNode');
        C('LOG_PATH', C('LOG_PATH') . "PayGive_"); // 重新定义目志目录
        $reqXml = file_get_contents('php://input');
        import('@.ORG.Util.Xml') or die('[@.ORG.Util.Xml]导入失败');
        $xml = new Xml();
        log_write('【'.__LINE__.'】'.$reqXml . 'REQUEST');
        $this->ReqArr = $xml->parse($reqXml);//数组 array('请求标识'=>请求内容)
        $this->transType = $xml->getRootName();//请求标识 BuyOverGiveGiftReq
        log_write('【'.__LINE__.'】请求标识为：'.$this->transType);
        if ($this->transType == 'BuyOverGiveGiftReq') { // 付满送接口
            $this->responseType = 'BuyOverGiveGiftRes'; //设置响应标识
            $req_arr = $this->ReqArr['BuyOverGiveGiftReq'];//拿到请求内容
            $this->TradeNo = $req_arr['TradeNo'];//拿出支付流水号
            switch($req_arr['PayType']){
                case 1:
                    $this->payType = '支付宝支付';
                    break;
                case 2:
                    $this->payType = '微信支付';
                    break;
            }
            log_write('【'.__LINE__.'】支付方式为：'.$this->payType);
            $this->paygive($req_arr);
        } else {
            $this->responseType = 'ErrorRes';
            $this->notifyreturn('1000', '', '', '', '', '');
        }
    }

    // 付满送接口
    private function paygive($req_arr) {
        // 查找是否已有数据，YES 直接返回 原URL
        $where = "order_id ='" . $req_arr['TradeNo'] . "' and pay_type = '" .
                $req_arr['PayType'] . "'";
        $order_info = M('tpay_give_order')->where($where)->find();//这里要去表里查一下付满送里订单是否存在
        log_write('【'.__LINE__.'】'.M()->getlastsql());

        if ($req_arr['PayType'] == 2 || $req_arr['PayType'] == 1) {
            //如果是微信支付 支付宝支付 查看之前有没有送给积分 如果已经有记录了，那说明之前这笔订单已经处理过了
            //返回 tintegral_weixin_order 表中的数据
            $weiXinOrderInfo = $this->selectWeiXinOrder($req_arr['NodeID'], $req_arr['TradeNo']);
            if ($weiXinOrderInfo) {
                log_write('【'.__LINE__.'】该订单已经进行过积分处理');
                //如果这笔订单已经处理过了 直接走
                $this->notifyreturn('0000', '交易成功', '', '', '',
                        $weiXinOrderInfo);
            }
        }

        if ($order_info) {
            //如果该订单存在  直接返回 原URL
            log_write('【'.__LINE__.'】'."这是付满送订单");
            $channel_info = M('tchannel')->where('id = ' . $order_info['channel_id'])->find();

            if (! $channel_info) {
                //如果tchannel表中没有数据
                log_write('【'.__LINE__.'】'."没有符合的渠道记录..." . M()->_sql());
                $this->notifyreturn('1001', '没有符合的渠道记录', '', '', '',
                        $weiXinOrderInfo);
            }
            $this->notifyreturn('0000', '交易成功', $order_info['short_url'],
                    mb_substr($channel_info['memo'], 0, 50, "utf8"), '',
                    $weiXinOrderInfo);
        }
        $pay_token = md5($req_arr['TradeNo'] . $req_arr['PayType']);
        $now_time = date('YmdHis');

        //以下是付满送逻辑
        // 判断是否开通付满送权限
        $where = "node_id = '" . $req_arr['NodeID'] .
                "'
AND charge_id = '3091' AND STATUS = '0' AND charge_level = '1' AND begin_time <= '" .
                $now_time . "' AND end_time >= '" . $now_time . "'";
        $node_charge_info = M('tnode_charge')->where($where)->find();
        if (! $node_charge_info) {
            log_write('【'.__LINE__.'】'."未开通付满送权限..." . M()->_sql());
            $this->notifyreturn('1005', '未开通付满送权限', '', '', $req_arr, '');
        }
        // 查找门店号
        $store_info = M('tpos_info')->where(
                "pos_id = '" . $req_arr['PosID'] . "'")
                ->field('store_id')
                ->find();
        if (! $store_info) {
            log_write('【'.__LINE__.'】'."没有符合的终端记录..." . M()->_sql());
            $this->notifyreturn('1004', '没有符合的终端记录', '', '', $req_arr, '');
        }
        $store_id = $store_info['store_id'];

        // 查找符合业务逻辑的tchannel记录
        $where = "c.node_id = '" . $req_arr['NodeID'] .
                "' and c.status = '1' and tc.status=1  and c.begin_time < '" . $now_time .
                "' and c.end_time > '" . $now_time .
                //"' and (join_flag = '0' or join_flag like '%" . $req_arr['PayType'] . "%')
                "' and tgs.limit_amt <=" . $req_arr['PayAmt'] / 100 .
                " and tgs.channel_id = c.id and tc.id=tgs.batch_channel_id ".
                " and tgs.upper_limit_amt > " . $req_arr['PayAmt'] / 100 .
                " and (store_join_flag = '1' or (store_join_flag = '2' and p.store_id = '" .
                $store_id . "'))";
        $channel_info = M()->table(array('tpay_give_batch_set'=>'tgs',
                                         'tbatch_channel'=>'tc', 'tchannel'=>'c'))
                ->join("left join tpay_give_store_list p on c.id = p.channel_id")
                ->field("c.join_limit,c.id as id, c.status, c.begin_time, c.end_time, memo, tgs.batch_channel_id tgsbatch_id,
                 tgs.upper_limit_amt tgsuplimat,tgs.limit_amt tgslowlimat")
                ->where($where)
                ->order('tgs.limit_amt desc')
                ->find();

        log_write('【'.__LINE__.'】'.M()->getlastsql());
        if (! $channel_info) {
            log_write('【'.__LINE__.'】'."没有符合的渠道记录..." . M()->_sql());
            $this->notifyreturn('1001', '没有符合的渠道记录', '', '', $req_arr, '');
        }
        // 查找tbatch_channel记录 前端确保一个合适的渠道只有一个记录
        $where = "status = '1'  and id= ".$channel_info['tgsbatch_id'];
        $batch_channel_info = M('tbatch_channel')->where($where)->find();
        if (! $batch_channel_info) {
            log_write('【'.__LINE__.'】'."返回没有符合的渠道活动记录..." . M()->_sql());
        }
        $cardClass=M()->table(array('tbatch_info'=>'t','twx_card_type'=>'tc'))
                ->field('tc.card_class,tc.card_id,t.id')->where(array('t.m_id'=>$batch_channel_info['batch_id']))
                ->where('t.card_id=tc.card_id')
                ->find();
        if($cardClass['card_class']!=2){
            log_write('【'.__LINE__.'】'.'pengyoude  quan');
            // 生成URL
            // http://test.wangcaio2o.com/index.php?g=Label&m=Label&a=index&id=7587
            $url = C('PAYGIVE_DOMAIN') . U('Label/Label/index',
                            array(
                                    'id' => $batch_channel_info['id'],
                                    'pay_token' => $pay_token));
            // 转短链接
            $RemoteRequest = D('RemoteRequest', 'Service');
            $arr = array(
                    'CreateShortUrlReq' => array(
                            'SystemID' => C('ISS_SYSTEM_ID'),
                            'TransactionID' => time() . rand(10000, 99999),
                            'OriginUrl' => "<![CDATA[$url]]>"));
            $short_url_arr = $RemoteRequest->GetShortUrl($arr);
            log_write('【'.__LINE__.'】'."短链生成成功..." . print_r($short_url_arr, true));
            if ($short_url_arr['Status']['StatusCode'] !== '0000') {
                // 返回短链生成失败
                log_write('【'.__LINE__.'】'."短链生成失败..." . $url);
                $this->notifyreturn('1003', '短链生成失败', '', '', $req_arr, '');
            } else
                $short_url = $short_url_arr['ShortUrl'];
        }else{
            $weixincardserver= D('WeiXinCard','Service');
            $weixincardserver->init_by_node_id($req_arr['NodeID']);
            // $weixincardserver->getToken();
            $outhid=$weixincardserver->getOutId('1', $pay_token, $channel_info['id'], $batch_channel_info['id'], $batch_channel_info['batch_id'], $cardClass['id']);
            $short_url=$weixincardserver->create_wx_qrcode($cardClass['card_id'],'',$channel_info['join_limit'],$outhid);
            $short_url=$short_url['url'];
        }
        // 更新营业员打印次数
        $where = "clerk_id = '" . $req_arr['ClerkId'] . "'";
        $rs = M()->table('tpay_give_clerk')
                ->where($where)
                ->setInc('print_cnt');
        if ($rs === false) { // log no exit
            log_write('【'.__LINE__.'】'."更新营业员打印次数失败..." . M()->_sql());
        }
        // 插入流水表
        $new_order_info['node_id'] = $req_arr['NodeID'];
        $new_order_info['order_id'] = $req_arr['TradeNo'];
        $new_order_info['pos_id'] = $req_arr['PosID'];
        $new_order_info['pos_seq'] = $req_arr['PosSeq'];
        $new_order_info['pay_type'] = $req_arr['PayType'];
        $new_order_info['clerk_id'] = $req_arr['ClerkId'];
        $new_order_info['add_time'] = $now_time;
        $new_order_info['pay_token'] = $pay_token;
        $new_order_info['amt'] = $req_arr['PayAmt']/100;
        $new_order_info['status'] = '1';
        $new_order_info['use_times'] = 0;
        $new_order_info['short_url'] = $short_url;
        $new_order_info['channel_id'] = $channel_info['id'];
        $new_order_info['batch_channel_id'] = $batch_channel_info['id'];

        $rs = M('tpay_give_order')->add($new_order_info);
        if ($rs === false) {
            log_write('【'.__LINE__.'】'.print_r($new_order_info, true));
            log_write('【'.__LINE__.'】'."记录流水信息[new_order_info]失败" . M()->_sql());
            $this->notifyreturn('1004', '记录流水信息失败', '', '', $req_arr, '');
        }
        $this->notifyreturn('0000', '交易成功', $short_url,
                mb_substr($channel_info['memo'], 0, 50, "utf8"), $req_arr, '');
    }
    // 做积分操作
    public function addIntegralPoint($integralConfig, $order_id, $amt, $node_id,
            $MemberId,$paytype) {
        if($paytype == 2){
            //微信支付
            if ($integralConfig['weixin_payment_flag'] == '1' &&
                    $integralConfig['weixin_payment_rate'] > 0) {
                $change_num = intval($amt * $integralConfig['weixin_payment_rate']);
                if($change_num>0){
                    if ($integralConfig['one_weixin_flag'] == 1) {
                        if ($change_num > $integralConfig['one_weixin_payment_rate']) {
                            $change_num = $integralConfig['one_weixin_payment_rate'];
                        }
                    }
                    $res = D("IntegralPointTrace")->integralPointChange('10',
                            $change_num, $MemberId, $node_id, $order_id, '');
                    if ($res === false) {
                        $IntegralArr['StatusCode'] = '2001';
                        $IntegralArr['StatusText'] = $this->payType.'积分失败';
                        return $IntegralArr;
                    }
                    // 增加积分成功
                    return $res;
                }
            } else {
                // 未开通积分营销
                $IntegralArr['StatusCode'] = '2014';
                $IntegralArr['StatusText'] = '该商户未开启微信支付反积分参数';
                return $IntegralArr;
            }
        }elseif($paytype == 1){
            //支付宝支付
            if ($integralConfig['zhifubao_payment_flag'] == '1' &&
                    $integralConfig['zhifubao_payment_rate'] > 0) {
                $change_num = intval($amt * $integralConfig['zhifubao_payment_rate']);
                if($change_num>0){
                    if ($integralConfig['one_zhifubao_flag'] == 1) {
                        if ($change_num > $integralConfig['one_zhifubao_payment_rate']) {
                            $change_num = $integralConfig['one_zhifubao_payment_rate'];
                        }
                    }
                    $res = D("IntegralPointTrace")->integralPointChange('19',
                            $change_num, $MemberId, $node_id, $order_id, '');
                    if ($res === false) {
                        $IntegralArr['StatusCode'] = '2001';
                        $IntegralArr['StatusText'] = $this->payType.'积分失败';
                        return $IntegralArr;
                    }
                    // 增加积分成功
                    return $res;
                }
            } else {
                // 未开通积分营销
                $IntegralArr['StatusCode'] = '2014';
                $IntegralArr['StatusText'] = '该商户未开启支付宝支付反积分参数';
                return $IntegralArr;
            }
        }else{
            $IntegralArr['StatusCode'] = '2014';
            $IntegralArr['StatusText'] = '该商户未开启反积分参数';
            return $IntegralArr;
        }

    }
    // 增加行为数据
    public function addBehaviorData($memberId, $node_id, $change_num,
            $relationId, $amt,$behavior = '10') {
        $behaviorRes = $this->MemberBehaviorModel->addBehaviorType($memberId, $node_id,
                $behavior, $change_num, $relationId, $amt);
        if ($behaviorRes === false) {
            return false;
        }
        return true;
    }

    public function _addIntegralWeiXinOrder($integralOrderArr, $order_id,
            $node_id) {
        $data = array(
                'order_id' => $order_id,
                'node_id' => $node_id,
                'status_sode' => $integralOrderArr['StatusCode'],
                'status_text' => $integralOrderArr['StatusText'],
                'integral_name' => $integralOrderArr['IntegralName'],
                'member_id' => $integralOrderArr['MemberId'],
                'member_name' => $integralOrderArr['MemberName'],
                'member_phone' => $integralOrderArr['MemberPhone'],
                'integral_change_number' => $integralOrderArr['IntegralChangeNumber'],
                'residual_integral' => $integralOrderArr['ResidualIntegral'],
                'trading_type' => $integralOrderArr['TradingType'],
                'card_type' => $integralOrderArr['CardType'],
                'amt' => $integralOrderArr['amt'],
                'type' => $integralOrderArr['type'],
                'add_time' => date('YmdHis'));
        $res = M("tintegral_weixin_order")->add($data);
        if ($res === false) {
            return false;
        }
    }

    /**
     * @param $node_id      商户号
     * @param $amt          支付金额
     * @param $openid       OpenId
     * @param $order_id     订单号
     * @param $posSeq       终端流水
     *
     * @return array
     */
    public function newGivePoint($node_id, $amt, $openid, $order_id, $posSeq,$paytype) {
        switch($paytype){
            case 1:
                $type = '3';//支付宝
                $behavior = '19';
                break;
            case 2:
                $type = '1';//微信
                $behavior = '10';
                break;
        }

        // 判断是不是会员 $isMember返回会员信息
        $isMember = $this->IntegralConfigNodeModel->checkMember($node_id, $openid,$type);
        if ($isMember['StatusCode'] == '2003') {
            //如果不是会员 退出
            log_write('【'.__LINE__.'】不是会员,提示绑定.');
            return $isMember;
        }
        $integralConfig = $this->IntegralConfigNodeModel->checkIntegralConfig($node_id);
        if (!$integralConfig) {
            log_write('【'.__LINE__.'】该商户未配置积分规则');
            $IntegralArr['StatusCode'] = '2014';
            $IntegralArr['StatusText'] = '该商户未开通积分营销或未配置积分规则';
            return $IntegralArr;
        }
        M()->startTrans();
            // 开通积分营销
            $IntegralArr = $this->addIntegralPoint($integralConfig,
                    $order_id, $amt, $node_id, $isMember['id'],$paytype);
            if ($IntegralArr['StatusCode'] != '') {
                M()->rollback();
                log_write('【'.__LINE__.'】'.$IntegralArr['StatusText'] );
                return $IntegralArr;
            }else{
                log_write('【'.__LINE__.'】'.$this->payType."增加积分成功");
            }

        if ($IntegralArr) {
            $change_num = $IntegralArr['change_num'];
        }
        $BehaviorData = $this->addBehaviorData($isMember['id'], $node_id,
                $change_num, $posSeq, $amt,$behavior);
        if ($BehaviorData === false) {
            M()->rollback();
            log_write('【'.__LINE__.'】'."添加行为记录失败,member_id" . $isMember['id']);
            $IntegralArr['StatusCode'] = '2001';
            $IntegralArr['StatusText'] = $this->payType.'增加积分失败';
            return $IntegralArr;
        }
        $integralName = $this->_integralName($node_id);

        $integralOrderArr = array(
                'order_id' => $order_id,
                'node_id' => $node_id,
                'StatusCode' => '0000',
                'StatusText' => $this->payType.'增加积分成功',
                'IntegralName' => $integralName,
                'MemberId' => $isMember['member_num'],
                'MemberName' => $this->replaceMemberName($isMember['name']),
                'MemberPhone' => $isMember['phone_no'],
                'IntegralChangeNumber' => $IntegralArr['change_num']>0?$IntegralArr['change_num']:0,
                'ResidualIntegral' => $IntegralArr['after_num']>0?$IntegralArr['after_num']:0,
                'TradingType' => $this->payType,
                'CardType' => $this->getMemberCardName($isMember['id'], $node_id, 1),
                'amt' => $amt,
                'type'=>$type,
                'add_time' => date('YmdHis'));
        $res = $this->_addIntegralWeiXinOrder($integralOrderArr, $order_id,
                $node_id);
        if ($res === false) {
            M()->rollback();
            log_write('【'.__LINE__.'】'."记录积分处理订单失败");
            $IntegralArr['StatusCode'] = '2001';
            $IntegralArr['StatusText'] = $this->payType.'增加积分失败';
            return $IntegralArr;
        }
        M()->commit();
        return $integralOrderArr;
    }

    /**
     * 通知应答
     * @param        $resp_id       0000
     * @param        $resp_desc     交易成功
     * @param string $url           http://weixin.qq.com/q/okwIINbmAut7QqvXEWeR
     * @param string $memo          付满送 活动备注
     * @param        $req_arr       空
     * @param        $IntegralArr   tintegral_weixin_order表中的记录
     */
    private function notifyreturn($resp_id, $resp_desc, $url = '', $memo = '',
            $req_arr, $IntegralArr) {
        if ($IntegralArr == '') {
            //如果没有送积分
            if ($req_arr['PayType'] == 2 || $req_arr['PayType'] == 1) {
                //微信支付 支付宝支付
                $weiXinOrderInfoEx=$this->selectWeiXinOrderEx($req_arr['NodeID'], $req_arr['TradeNo']);
                if($weiXinOrderInfoEx){
                    //如果ex表里有订单 那么进行增加积分操作
                    $IntegralArr = $this->newGivePoint($req_arr['NodeID'],
                            $req_arr['PayAmt']/100, $req_arr['OpenId'],
                            $req_arr['TradeNo'], $req_arr['PosID'],$req_arr['PayType']);
                    if($IntegralArr && !isset($IntegralArr['StatusCode'])){
                        log_write('【'.__LINE__.'】积分增加成功，行为记录添加成功，order表记录成功，全部成功.' );
                    }
                }else{
                    //如果ex表里没订单 那么就给他添加订单
                    $weiXinOrderStatus = $this->integralWeiXinOrderEx($req_arr);
                    if ($weiXinOrderStatus === false) {
                        log_write('【'.__LINE__.'】'.$this->payType."订单插入失败..." . M()->_sql());
                    }else{
                        //如果订单添加成功 那么进行增加积分操作
                        $IntegralArr = $this->newGivePoint($req_arr['NodeID'],
                                $req_arr['PayAmt']/100, $req_arr['OpenId'],
                                $req_arr['TradeNo'], $req_arr['PosID'],$req_arr['PayType']);
                        if($IntegralArr && !isset($IntegralArr['StatusCode'])){
                            log_write('【'.__LINE__.'】积分增加成功，行为记录添加成功，order表记录成功，全部成功.' );
                        }
                    }
                }
            }
        }
        $resp_xml = '<?xml version="1.0" encoding="gbk"?>
		<' . $this->responseType . '>
		     <StatusCode>' . $resp_id . '</StatusCode>
		     <StatusText>' .
                iconv('utf8', 'gbk', $resp_desc) . '</StatusText>
		     <TradeNo>' . $this->TradeNo . '</TradeNo>
		     <URLInfo><![CDATA[' . $url . ']]></URLInfo>
		     <Memo>' .
                iconv('utf8', 'gbk', $memo) . '</Memo>
		     <MemberIntegral>
		         <Status>
		             <StatusCode>' .
                $IntegralArr['StatusCode'] . '</StatusCode>
		             <StatusText>' .
                iconv('utf8', 'gbk', $IntegralArr['StatusText']) .
                '</StatusText>
	             </Status>
		     <IntegralName>' .
                iconv('utf8', 'gbk', $IntegralArr['IntegralName']) .
                '</IntegralName>
		     <IntegralChangeNumber>' .
                $IntegralArr['IntegralChangeNumber'] . '</IntegralChangeNumber>
		     <MemberID>' .
                $IntegralArr['MemberId'] .
                '</MemberID>
		     <MemberName>' .
                iconv('utf8', 'gbk', $IntegralArr['MemberName']) .
                '</MemberName>
		     <ResidualIntegral>' .
                $IntegralArr['ResidualIntegral'] . '</ResidualIntegral>
		     <MemberPhone>' .
                $IntegralArr['MemberPhone'] .
                '</MemberPhone>
		     <TradingType>' .
                iconv('utf8', 'gbk', $IntegralArr['TradingType']) .
                '</TradingType>
		     <CardType>' .
                iconv('utf8', 'gbk', $IntegralArr['CardType']) . '</CardType>
		     </MemberIntegral>
       </' . $this->responseType . '>';
        log_write('【'.__LINE__.'】'.$resp_xml . 'RESPONSE');
        ob_end_clean();
        echo $resp_xml;
        exit();
    }
    // 记录日志
    protected function log($msg, $level = Log::INFO) {
        trace('Log.' . $level . ':' . $msg);
        Log::write($msg, $level);
    }
    // 商城名称
    public function _integralName($node_id) {
        $integral_name = M("tintegral_node_config")->where(
                array(
                        'node_id' => $node_id))->getField('integral_name');
        return $integral_name;
    }

    /**
     * 通过订单号查询返回数据
     * @param $node_id 商户号
     * @param $order_id 订单号
     *
     * @return mixed
     */
    public function selectWeiXinOrder($node_id, $order_id) {
        $weiXinOrderInfo = M("tintegral_weixin_order")->where(
                array(
                        'node_id' => $node_id,
                        'order_id' => $order_id))->find();
        if ($weiXinOrderInfo) {
            $IntegralArr = array(
                    'StatusCode' => $weiXinOrderInfo['status_code'],
                    'StatusText' => $weiXinOrderInfo['status_text'],
                    'IntegralName' => $weiXinOrderInfo['integral_name'],
                    'IntegralChangeNumber' => $weiXinOrderInfo['integral_change_number']>0?$weiXinOrderInfo['integral_change_number']:0,
                    'MemberId' => $weiXinOrderInfo['member_num'],
                    'MemberName' => $weiXinOrderInfo['member_name'],
                    'MemberPhone' => $weiXinOrderInfo['member_phone'],
                    'ResidualIntegral' => $weiXinOrderInfo['residua_integral']>0?$weiXinOrderInfo['residua_integral']:0,
                    'TradingType' => $weiXinOrderInfo['trading_type'],
                    'CardType' => $weiXinOrderInfo['card_type']);
            return $IntegralArr;
        }
    }
    // 通过订单号查询返回数据
    public function selectWeiXinOrderEx($node_id, $order_id) {
        $weiXinOrderInfoEx = M("tintegral_weixin_order_ex")->where(
                array(
                        'node_id' => $node_id,
                        'order_id' => $order_id))->find();
        if ($weiXinOrderInfoEx) {
            return $weiXinOrderInfoEx;
        }
    }
    // 替换会员名称后几位
    public function replaceMemberName($memberName) {
        if ($memberName) {
            $str = '';
                $str =  substr($memberName,0,3);
                $len = (strlen($memberName)-3)/3;
                for($i=0;$i<$len;$i++){
                    $str .= '*';
                }
            return $str;
        }
    }
    // 非付满送的微信支付需要处理
    public function integralWeiXinOrderEx($req_arr) {
        $data = array(
                'node_id' => $req_arr['NodeID'],
                'order_id' => $req_arr['TradeNo'],
                'amt' => $req_arr['PayAmt']/100,
                'pos_id' => $req_arr['PosID'],
                'open_id' => $req_arr['OpenId'],
                'pos_seq' => $req_arr['PosSeq'],
                'add_time' => date('YmdHis'),
                'pay_type' => $req_arr['PayType']);
        log_write('【'.__LINE__.'】'.$this->payType."金额" . print_r($data, true));
        $res = M("tintegral_weixin_order_ex")->add($data);
        if ($res === false) {
            log_write('【'.__LINE__.'】'.$this->payType."订单插入失败..." . M()->_sql());
        }
    }
    // 获取会员卡类型
    public function getMemberCardName($member_id, $node_id, $type) {
        $map = array(
                'a.node_id' => $node_id,
                'a.id' => $member_id);
        $memberCardName = M()->table("tmember_info a")->join(
                'tmember_cards c on c.id=a.card_id')
                ->where($map)
                ->field('c.card_name,c.receipt')
                ->find();
        if ($type == 2) {
            return $memberCardName;
        } else {
            return $memberCardName['card_name'];
        }
    }
}
