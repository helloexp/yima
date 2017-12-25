<?php

/* 主动通知接口 */
class MemberIntegralAction extends Action {

    public $ReqArr;

    public $transType;

    public $responseType;

    public $TradeNo;

    /**
     * @var IntegralConfigNodeModel
     */
    public $IntegralConfigNodeModel;

    /**
     * @var MemberBehaviorModel;
     */
    public $MemberBehaviorModel;

    public $INTEGRAL_BATCH_TYPE = CommonConst::BATCH_TYPE_INTEGRAL;
    protected $tableName = '__NONE__';
    // 积分商城活动类型
    public function index() {
        $this->IntegralConfigNodeModel = D('IntegralConfigNode');
        $this->MemberBehaviorModel = D('MemberBehavior');
        $this->checkIp();
        C('LOG_PATH', C('LOG_PATH') . "MemberIntegral_"); // 重新定义目志目录
        $reqXml = file_get_contents('php://input');
        import('@.ORG.Util.Xml') or die('[@.ORG.Util.Xml]导入失败');
        $xml = new Xml();
        log_write('【'.__LINE__.'】'.$reqXml . 'REQUEST');
        $this->ReqArr = $xml->parse($reqXml);
        $this->transType = $xml->getRootName();
        log_write('【'.__LINE__.'】请求标识为：'.$this->transType);
        if ($this->transType == 'MemberShipCardBindingReq') {
            $req_arr = $this->ReqArr['MemberShipCardBindingReq'];
            $this->TradeNo = $req_arr['TradeNo'];
            log_write('【'.__LINE__.'】会员卡绑定接口...');
            $this->memberShipCardBinding($req_arr);
        } elseif ($this->transType == 'MemberShipCardVerificationReq') {
            $req_arr = $this->ReqArr['MemberShipCardVerificationReq'];
            log_write('【'.__LINE__.'】会员卡验证接口...');
            $this->memberShipCardVerification($req_arr);
        } elseif ($this->transType == 'IncreaseMemberIntegralReq') {
            $req_arr = $this->ReqArr['IncreaseMemberIntegralReq'];
            log_write('【'.__LINE__.'】积分增加接口...');
            $this->increaseMemberIntegral($req_arr);
        } elseif ($this->transType == 'MemberIntegralExchangeReq') {
            $req_arr = $this->ReqArr['MemberIntegralExchangeReq'];
            log_write('【'.__LINE__.'】积分兑换接口...');
            $this->memberIntegralExchange($req_arr);
        }elseif ($this->transType == 'MemberCarListReq') {
            $req_arr = $this->ReqArr['MemberCarListReq'];
            log_write('【'.__LINE__.'】获取会员卡接口...');
            $this->memberCarList($req_arr);
        }elseif ($this->transType == 'MemberCreateReq') {
            $req_arr = $this->ReqArr['MemberCreateReq'];
            log_write('【'.__LINE__.'】会员新增接口...');
            $this->addMember($req_arr);
        } else {
            log_write('【'.__LINE__.'】请求标识不通过');
            $IntegralArr['StatusCode'] = '2010';
            $IntegralArr['StatusText'] = '参数错误';
            $this->notifyreturn($IntegralArr, 0);
        }
    }
    
    // 检测订单
    public function checkOrder($orderId, $nodeId) {
        $order_info = M('tpay_give_order')->where(
            array(
                'order_id' => $orderId, 
                'node_id' => $nodeId))->find();
        if (! $order_info) {
            // 不存在交易信息
            $order_info = M('tintegral_weixin_order_ex')->where(
                array(
                    'order_id' => $orderId, 
                    'node_id' => $nodeId))->find();
            if (! $order_info) {
                $IntegralArr['StatusCode'] = '2007';
                $IntegralArr['StatusText'] = '查询不到该订单';
                $this->notifyReturn($IntegralArr, 1);
            }
        }
        if ($order_info['pay_type'] != '2' && $order_info['pay_type'] != '1') {
            $IntegralArr['StatusCode'] = '2007';
            $IntegralArr['StatusText'] = '查询不到该订单';
            $this->notifyReturn($IntegralArr, 1);
        }
        return $order_info;
    }
    
    // 绑定接口
    public function memberShipCardBinding($req_arr) {

        $this->checkPosNode($req_arr['PosID'], $req_arr['NodeID'], 1);
        log_write('【'.__LINE__.'】终端号检测已通过');
        $integralName = $this->_integralName($req_arr['NodeID']);
        $order_info = $this->checkOrder($req_arr['TradeNo'], $req_arr['NodeID']);
        log_write('【'.__LINE__.'】订单号检测已通过'.print_r($order_info,true));
        $map = array(
            'node_id' => $req_arr['NodeID'], 
            'phone_no' => $req_arr['MemberPhone']);
        $isMember = M("tmember_info")->where($map)->find();
        log_write('【'.__LINE__.'】查询tmember_info表:'.M()->getlastsql());
        // 输入手机号码已经为会员
        M()->startTrans();

        $integralConfig = $this->IntegralConfigNodeModel->checkIntegralConfig($req_arr['NodeID']);//返回tintegral_node_config表的数据
//        if($integralConfig[0] == false){
//            log_write('【'.__LINE__.'】'.$integralConfig[1]);
//            M()->rollback();checkIntegralConfig
//            $IntegralArr['StatusCode'] = '2008';
//            $IntegralArr['StatusText'] = '绑定失败！';
//            $this->notifyReturn($IntegralArr, 3);
//        }else{
//            $integralConfig = $integralConfig[1];
//        }
        log_write("integralConfig".print_r($integralConfig,true));
        log_write("order_info".print_r($order_info,true));
        if ($isMember) {
            log_write('【'.__LINE__.'】'.'该手机号已经是会员,正在进行添加行为记录操作...');
            $returnArr = $this->isMember($isMember, $integralConfig, 
                $order_info, $req_arr, $integralName);
            if ($returnArr === false) {
                M()->rollback();
                $IntegralArr['StatusCode'] = '2008';
                $IntegralArr['StatusText'] = '绑定失败！';
                $this->notifyReturn($IntegralArr, 3);
            }
        } else {
            log_write('【'.__LINE__.'】'.'该手机号不是会员,正在进行绑定操作...');
            $returnArr = $this->isNotMember($integralConfig, $order_info, 
                $req_arr, $integralName);
            if ($returnArr === false) {
                M()->rollback();
                $IntegralArr['StatusCode'] = '2008';
                $IntegralArr['StatusText'] = '绑定失败！';
                $this->notifyReturn($IntegralArr, 3);
            }
            log_write('【'.__LINE__.'】'.'正在进行order表记录操作...');
        }
        $res = $this->_addIntegralWeiXinOrder($returnArr, 
            $order_info['order_id'], $req_arr['NodeID']);
        if ($res === false) {
            log_write('【'.__LINE__.'】'.'order表记录出错,事务回滚,绑定失败.');
            M()->rollback();
            $IntegralArr['StatusCode'] = '2008';
            $IntegralArr['StatusText'] = '绑定失败！';
            $this->notifyReturn($IntegralArr, 3);
        }
        M()->commit();
        log_write('【'.__LINE__.'】'.'操作完成,会员卡绑定成功.');
        $this->notifyReturn($returnArr, 1);
    }
    
    // 会员验证接口
    public function memberShipCardVerification($req_arr) {
        $this->checkPosNode($req_arr['PosID'], $req_arr['NodeID'], 2);
        if ($req_arr['MemberType'] == 0) {
            $map = array(
                'node_id' => $req_arr['NodeID'], 
                'member_num' => $req_arr['MemberNum']);
        } elseif ($req_arr['MemberType'] == 1) {
            $map = array(
                'node_id' => $req_arr['NodeID'], 
                'phone_no' => $req_arr['MemberPhone']);
        }
        $memberInfo = M("tmember_info")->where($map)->find();
        if ($memberInfo) {
            $integralName = $this->_integralName($req_arr['NodeID']);
            $cardInfo = $this->getMemberCardName($memberInfo['id'], 
                $req_arr['NodeID'], '2');
            $yearMonth='';
            if($memberInfo['birthday']){
                $yearMonth=substr($memberInfo['birthday'], 0, 4)  .substr($memberInfo['birthday'], 4, 2);
            }
            $IntegralArr = array(
                'StatusCode' => '0000', 
                'StatusText' => '查询成功', 
                'MemberID' => $memberInfo['member_num'], 
                'MemberText' => $cardInfo['receipt'], 
                'MemberPhone' => $memberInfo['phone_no'],
                'MemberName' => $this->replaceMemberName($memberInfo['name']),
                'MemberSex' => $memberInfo['sex'],
                'MemberYearMonth' => $yearMonth,
                'IntegralName' => $integralName,
                'TradingType' => "验证会员卡", 
                'CardType' => $cardInfo['card_name'],
                'ResidualIntegral' => $memberInfo['point']>0?$memberInfo['point']:0);
        } else {
            $IntegralArr = array(
                'StatusCode' => '2003', 
                'StatusText' => '未找到会员信息');
            $this->notifyReturn($IntegralArr, 2);
        }
        $this->notifyReturn($IntegralArr, 2);
    }

    // 是会员 进行积分操作 添加行为记录
    public function isMember($isMember, $integralConfig, $order_info, $req_arr, 
        $integralName) {
            if ($integralConfig) {
                if($order_info['pay_type'] == 1 && $integralConfig['zhifubao_payment_flag'] == '1'){
                    //支付宝支付
                    $change_num = intval($order_info['amt'] * $integralConfig['zhifubao_payment_rate']);
                    if($change_num>0){
                        if ($integralConfig['one_zhifubao_flag'] == 1) {
                            if ($change_num >
                                    $integralConfig['one_zhifubao_payment_rate']) {
                                $change_num = $integralConfig['one_zhifubao_payment_rate'];
                            }
                        }
                        $IntegralPointTrace = new IntegralPointTraceModel();
                        $integralInfo = $IntegralPointTrace->integralPointChange(
                                '19', $change_num, $isMember['id'], $req_arr['NodeID'],
                                '', '');
                        if ($integralInfo === false) {
                            log_write('【'.__LINE__.'】'."支付宝支付积分增加失败！");
                            return false;
                        }
                        log_write('【'.__LINE__.'】'."支付宝支付积分增加成功！正在新增行为数据...");
                    }
                    $sta = '1';
                    $TradingType = '支付宝支付';
                    $type = '3';
                }
                if($order_info['pay_type'] == 2 && $integralConfig['weixin_payment_flag'] == '1'){
                    //微信支付
                    $change_num = intval($order_info['amt'] * $integralConfig['weixin_payment_rate']);
                    if($change_num>0){
                        if ($integralConfig['one_weixin_flag'] == 1) {
                            if ($change_num >
                                    $integralConfig['one_weixin_payment_rate']) {
                                $change_num = $integralConfig['one_weixin_payment_rate'];
                            }
                        }
                        $IntegralPointTrace = new IntegralPointTraceModel();
                        $integralInfo = $IntegralPointTrace->integralPointChange(
                                '10', $change_num, $isMember['id'], $req_arr['NodeID'],
                                '', '');
                        if ($integralInfo === false) {
                            log_write('【'.__LINE__.'】'."微信支付积分增加失败！");
                            return false;
                        }
                        log_write('【'.__LINE__.'】'."微信支付积分增加成功！正在新增行为数据...");
                    }
                    $TradingType = '微信支付';
                    $type = '1';
                }

            }
            $map=array(
                    'member_id'=>$isMember['id'],
                    'node_id'=>$req_arr['NodeID']
            );
            if($order_info['pay_type'] == 1){
                $behavior = '19';
                $map['type']=1;
            }else{
                $map['type']=0;
                $behavior = '10';
            }
            $hasData=M("tmember_account")->where($map)->find();
            if($hasData){
                $updateStatus=M("tmember_account")->where($map)->save(array('account'=>$req_arr['OpenId']));
            }else{
                $saveData=$map;
                $saveData['account']=$req_arr['OpenId'];
                $saveData['add_time']=date('YmdHis');
                $updateStatus=M("tmember_account")->add($saveData);
            }
            if ($updateStatus === false) {
                log_write('【'.__LINE__.'】'."account更新或新增失败,正在事务回滚...");
                return false;
            }
            $res = $this->IntegralAddBehavior($isMember['id'], 
                $req_arr['NodeID'], $change_num, $req_arr['PosID'], 
                $order_info['amt'],$behavior);
            if ($res === false) {
                log_write('【'.__LINE__.'】'."新增行为数据失败！");
                return false;
            }
        log_write('【'.__LINE__.'】'."新增行为数据成功！");
        // 积分增加成功
        $str = '';
        if($isMember['name']){
            $str =  substr($isMember['name'],0,3);
            $len = (strlen($isMember['name'])-3)/3;
            for($i=0;$i<$len;$i++){
                $str .= '*';
            }
        }

        $data = array(
            'order_id' => $order_info['order_id'], 
            'node_id' => $req_arr['NodeID'], 
            'StatusCode' => '0000', 
            'StatusText' => '绑定成功！', 
            'IntegralName' => $integralName, 
            'MemberId' => $isMember['member_num'], 
            'MemberName' => $str,
            'MemberPhone' => $isMember['phone_no'], 
            'IntegralChangeNumber' => $integralInfo['change_num']>0?$integralInfo['change_num']:0,
            'ResidualIntegral' => $integralInfo['after_num']>0?$integralInfo['after_num']:0,
            'TradingType' => $TradingType,
            'CardType' => $this->getMemberCardName($isMember['id'], 
                $req_arr['NodeID'], 1), 
            'amt' => $order_info['amt'], 
            'add_time' => date('YmdHis'), 
            'type' => $type,
            'PhoneIsMerber' => '1',
        );
        return $data;
    }
    
    // 不是会员
    public function isNotMember($integralConfig, $order_info, $req_arr, 
        $integralName) {
        $memberInfo = $this->addMemberOne($req_arr['NodeID'], 
            $req_arr['MemberPhone'], $req_arr['OpenId'], '', 
            $order_info['channel_id'],$order_info['pay_type']);
        if ($memberInfo == '' || $memberInfo===false) {
            log_write('【'.__LINE__.'】'."新增会员卡失败" );
            return false;
        }
        log_write('【'.__LINE__.'】'."新增会员卡成功,正在进行增加积分操作..." );

        if($order_info['pay_type'] == '2' && $integralConfig['weixin_payment_flag'] == '1'){
            //微信支付
                $change_num = intval($order_info['amt'] * $integralConfig['weixin_payment_rate']);
                if($change_num>0){
                    if ($integralConfig['one_weixin_flag'] == 1) {
                        if ($change_num > $integralConfig['one_weixin_payment_rate']) {
                            $change_num = $integralConfig['one_weixin_payment_rate'];
                        }
                    }
                    $IntegralPointTrace = new IntegralPointTraceModel();
                    $integralInfo = $IntegralPointTrace->integralPointChange('10',
                            $change_num, $memberInfo['id'], $req_arr['NodeID'], $order_info['order_id'], '');
                    if ($integralInfo === false) {
                        log_write('【'.__LINE__.'】'."微信支付积分增加失败！");
                        return false;
                    }
                    log_write('【'.__LINE__.'】'."微信支付积分增加成功！正在新增行为数据...");
                }
            $TradingType = '微信支付';
            $type = '1';
        }

        if($order_info['pay_type'] == '1' && $integralConfig['zhifubao_payment_flag'] == '1'){
            //支付宝支付
                $change_num = intval($order_info['amt'] * $integralConfig['zhifubao_payment_rate']);
                if($change_num>0){
                    if ($integralConfig['one_zhifubao_flag'] == 1) {
                        if ($change_num > $integralConfig['one_zhifubao_payment_rate']) {
                            $change_num = $integralConfig['one_zhifubao_payment_rate'];
                        }
                    }
                    $IntegralPointTrace = new IntegralPointTraceModel();
                    $integralInfo = $IntegralPointTrace->integralPointChange('19',
                            $change_num, $memberInfo['id'], $req_arr['NodeID'], $order_info['order_id'], '');
                    if ($integralInfo === false) {
                        log_write('【'.__LINE__.'】'."支付宝支付积分增加失败！");
                        return false;
                    }
                    log_write('【'.__LINE__.'】'."支付宝支付积分增加成功！正在新增行为数据...");
                }
            $TradingType = '支付宝支付';
            $type = '3';
        }
        if($order_info['pay_type'] == 1){
            $behavior = '19';
        }else{
            $behavior = '10';
        }
        $res = $this->IntegralAddBehavior($memberInfo['id'], $req_arr['NodeID'], 
            $change_num, $req_arr['PosID'], $order_info['amt'],$behavior);
        if ($res === false) {
            log_write('【'.__LINE__.'】'."新增行为数据失败！");
            return false;
        }
        log_write('【'.__LINE__.'】'."新增行为数据成功！");
        // 积分增加成功
        $str = '';
        if($memberInfo['name']){
            $str =  substr($memberInfo['name'],0,3);
            $len = (strlen($memberInfo['name'])-3)/3;
            for($i=0;$i<$len;$i++){
                $str .= '*';
            }
        }
        $data = array(
            'order_id' => $order_info['order_id'], 
            'node_id' => $req_arr['NodeID'], 
            'StatusCode' => '0000', 
            'StatusText' => '绑定成功！', 
            'IntegralName' => $integralName, 
            'MemberId' => $memberInfo['member_num'], 
            'MemberName' => $str,
            'MemberPhone' => $memberInfo['phone_no'], 
            'IntegralChangeNumber' => $integralInfo['change_num']>0?$integralInfo['change_num']:0,
            'ResidualIntegral' => $integralInfo['after_num']>0?$integralInfo['after_num']:0,
            'TradingType' => $TradingType,
            'CardType' => $this->getMemberCardName($memberInfo['id'], 
                $req_arr['NodeID'], 1), 
            'amt' => $order_info['amt'], 
            'add_time' => date('YmdHis'), 
            'type' => $type,
            'PhoneIsMerber' => '0',
        );
        return $data;
    }
    
    // 增加积分接口
    public function increaseMemberIntegral($req_arr) {
        $this->checkPosNode($req_arr['PosID'], $req_arr['NodeID'], 3);
        $this->checkIntegralStart($req_arr['NodeID'], 3);
        if ($req_arr['MemberType'] == 0) {
            $map = array(
                'node_id' => $req_arr['NodeID'], 
                'member_num' => $req_arr['MemberNum']);
        } elseif ($req_arr['MemberType'] == 1) {
            $map = array(
                'node_id' => $req_arr['NodeID'], 
                'phone_no' => $req_arr['MemberPhone']);
        }
        $memberInfo = M("tmember_info")->where($map)->find();
        if ($memberInfo) {
            $nodeConfig = M("tintegral_node_config")->where(
                array(
                    'node_id' => $req_arr['NodeID']))->find();
            if ($nodeConfig['shop_line_flag'] == '1' &&
                 $nodeConfig['shop_line_rate'] != '') {
                $change_num = intval(
                    $req_arr['ConsumptionAmount'] * $nodeConfig['shop_line_rate']);
                if ($nodeConfig['one_line_rate_flag'] == '1') {
                    if ($change_num > $nodeConfig['one_line_rate']) {
                        $change_num = $nodeConfig['one_line_rate'];
                    }
                }
            } else {
                $IntegralArr['StatusCode'] = '2015';
                $IntegralArr['StatusText'] = '未开启线下积分兑换规则';
                $this->notifyReturn($IntegralArr, 3);
            }
            // 积分增加操作
            // 调用积分增加接口
            M()->startTrans();
            $IntegralPointTrace = new IntegralPointTraceModel();
            if($change_num>0){
                $result = $IntegralPointTrace->integralPointChange('8', $change_num,
                        $memberInfo['id'], $req_arr['NodeID'], $req_arr['PosSeq'], '');
                if ($result === false) {
                    M()->rollback();
                    $IntegralArr['StatusCode'] = '2006';
                    $IntegralArr['StatusText'] = '终端增加积分失败';
                    $this->notifyReturn($IntegralArr, 3);
                }
            }
            $integralName = $this->_integralName($req_arr['NodeID']);
            $integralOrderArr = array(
                'StatusCode' => '0000', 
                'StatusText' => '增加积分成功', 
                'IntegralName' => $integralName,
                'IntegralChangeNumber' => $change_num>0?$change_num:0,
                'MemberID' => $memberInfo['member_num'],
                'MemberName' => $this->replaceMemberName($memberInfo['name']),
                "ResidualIntegral" => ($memberInfo['point'] + $change_num)>0?$memberInfo['point'] + $change_num:0,
                'MemberPhone' => $memberInfo['phone_no'],
                'CardType' => $this->getMemberCardName($memberInfo['id'],
                    $req_arr['NodeID'], '1'),
                'TradingType' => '积分增加',
                'amt' => $req_arr['ConsumptionAmount'],
                'type' => 2);
            $weiXinOrder = $this->_addIntegralWeiXinOrder($integralOrderArr, '',
                $req_arr['NodeID']);
            if ($weiXinOrder === false) {
                M()->rollback();
                $IntegralArr['StatusCode'] = '2006';
                $IntegralArr['StatusText'] = '终端增加积分失败';
                $this->notifyReturn($IntegralArr, 3);
            }
            // 增加行为数据
            $res = $this->MemberBehaviorModel->addBehaviorType($memberInfo['id'],
                $req_arr['NodeID'], 11, $change_num, $req_arr['PosID'], 
                $req_arr['ConsumptionAmount']);
            if ($res === false) {
                log_write("新增行为数据失败！");
            }
            $IntegralArr = array(
                'StatusCode' => '0000', 
                'StatusText' => '增加积分成功', 
                'IntegralName' => $integralName, 
                'IntegralChangeNumber' => $change_num>0?$change_num:0,
                'MemberID' => $memberInfo['member_num'], 
                'MemberName' => $this->replaceMemberName($memberInfo['name']), 
                "ResidualIntegral" => ($memberInfo['point'] + $change_num)>0?$memberInfo['point'] + $change_num:0,
                'MemberPhone' => $memberInfo['phone_no'], 
                'CardType' => $this->getMemberCardName($memberInfo['id'], 
                    $req_arr['NodeID'], '1'), 
                'TradingType' => '积分增加', 
                'ConsumptionAmount' => $req_arr['ConsumptionAmount']);
            M()->commit();
        } else {
            $IntegralArr = array(
                'StatusCode' => '2003', 
                'StatusText' => '未找到会员信息');
        }
        $this->notifyReturn($IntegralArr, 3);
    }
    
    // 积分兑换接口
    private function notifyReturn($IntegralArr, $type) {
        // type=1 为绑定接口 type=2为验证接口 type=3为积分增加接口 type=4为积分扣减接口
        $returnArr=array(
                'StatusCode' => '',
                'StatusText' => '',
                'MemberID' => '',
                'MemberText' => '',
                'MemberPhone' => '',
                'MemberName' => '',
                'IntegralName' => '',
                'TradingType' => "",
                'CardType' => '',
                'ResidualIntegral' =>'',
                'IntegralChangeNumber'=>''
        );
        $IntegralArr=array_merge($returnArr,$IntegralArr);
        if ($type == 0) {
            $resp_xml = "<?xml version='1.0' encoding='gbk'?>
				<ErrorResponse >
				    <Status>
			                <StatusCode>" .
                    iconv('utf8', 'gbk', $IntegralArr['StatusCode']) . "</StatusCode>
			                <StatusText>" .
                    iconv('utf8', 'gbk', $IntegralArr['StatusText']) . "</StatusText>
			        </Status>
			   </ErrorResponse>";
        }
        if ($type == 1) {
            $resp_xml = "<?xml version='1.0' encoding='gbk'?>
				<MemberShipCardBindingRes>
				    <Status>
			                <StatusCode>" .
                    iconv('utf8', 'gbk', $IntegralArr['StatusCode']) . "</StatusCode>
			                <StatusText>" .
                    iconv('utf8', 'gbk', $IntegralArr['StatusText']) . "</StatusText>
			        </Status>
			         <IntegralName>" .
                    iconv('utf8', 'gbk', $IntegralArr['IntegralName']) . "</IntegralName>
			         <IntegralChangeNumber>" .
                    iconv('utf8', 'gbk', $IntegralArr['IntegralChangeNumber']) . "</IntegralChangeNumber>
			         <MemberID>" .
                    $IntegralArr['MemberId'] .
                    "</MemberID>
			         <MemberName>" .
                    iconv('utf8', 'gbk', $IntegralArr['MemberName']) .
                    "</MemberName>
			         <ResidualIntegral>" .
                    $IntegralArr['ResidualIntegral'] . "</ResidualIntegral>
			         <TradingType>" .
                    iconv('utf8', 'gbk', $IntegralArr['TradingType']) .
                    "</TradingType>
			         <CardType>" .
                    iconv('utf8', 'gbk', $IntegralArr['CardType']) . "</CardType>
			        <MemberPhone>" .
                    $IntegralArr['MemberPhone'] . "</MemberPhone>
                     <PhoneIsMerber>" .
                    $IntegralArr['PhoneIsMerber'] . "</PhoneIsMerber>
			   </MemberShipCardBindingRes>";

        }
        if ($type == 2) {
            $resp_xml = "<?xml version='1.0' encoding='gbk'?>
				<MemberShipCardVerificationRes>
				    <Status>
			                <StatusCode>" . $IntegralArr['StatusCode'] . "</StatusCode>
			                <StatusText>" . iconv('utf8', 'gbk', $IntegralArr['StatusText']) . "</StatusText>
                    </Status>
			        <MemberID>" . $IntegralArr['MemberID'] . "</MemberID>
			        <MemberText>" . iconv('utf8', 'gbk', $IntegralArr['MemberText']) . "</MemberText>
			        <MemberPhone>" . $IntegralArr['MemberPhone'] . "</MemberPhone>
			        <MemberName>" . iconv('utf8', 'gbk', $IntegralArr['MemberName']) . "</MemberName>
			        <IntegralName>" . iconv('utf8', 'gbk', $IntegralArr['IntegralName']) . "</IntegralName>
			        <MemberSex>" . $IntegralArr['MemberSex'] . "</MemberSex>
			        <MemberYearMonth>" . $IntegralArr['MemberYearMonth'] . "</MemberYearMonth>
                    <IntegralName>" . iconv('utf8', 'gbk', $IntegralArr['IntegralName']) . "</IntegralName>
			         <TradingType>" . iconv('utf8', 'gbk', $IntegralArr['TradingType']) . "</TradingType>
			         <ResidualIntegral>" . $IntegralArr['ResidualIntegral'] . "</ResidualIntegral>
			         <CardType>" . iconv('utf8', 'gbk', $IntegralArr['CardType']) . "</CardType>
			   </MemberShipCardVerificationRes>";
        }
        if ($type == 3) {
            $resp_xml = "<?xml version='1.0' encoding='gbk'?>
				<IncreaseMemberIntegralRes>
				    <Status>
			                <StatusCode>" .
                    iconv('utf8', 'gbk', $IntegralArr['StatusCode']) . "</StatusCode>
			                <StatusText>" .
                    iconv('utf8', 'gbk', $IntegralArr['StatusText']) . "</StatusText>
			        </Status>
			        <IntegralName>" .
                    iconv('utf8', 'gbk', $IntegralArr['IntegralName']) .
                    "</IntegralName>
			        <IntegralChangeNumber>" .
                    $IntegralArr['IntegralChangeNumber'] . "</IntegralChangeNumber>
			        <MemberID>" .
                    $IntegralArr['MemberID'] .
                    "</MemberID>
			        <MemberName>" .
                    iconv('utf8', 'gbk', $IntegralArr['MemberName']) .
                    "</MemberName>
			        <ResidualIntegral>" .
                    $IntegralArr['ResidualIntegral'] . "</ResidualIntegral>
			        <MemberPhone>" .
                    $IntegralArr['MemberPhone'] .
                    "</MemberPhone>
			         <CardType>" .
                    iconv('utf8', 'gbk', $IntegralArr['CardType']) . "</CardType>
			         <TradingType>" .
                    iconv('utf8', 'gbk', $IntegralArr['TradingType']) .
                    "</TradingType>
			         <ConsumptionAmount>" .
                    $IntegralArr['ConsumptionAmount'] . "</ConsumptionAmount>
			   </IncreaseMemberIntegralRes>";
        }
        if ($type == 4) {
            $resp_xml = "<?xml version='1.0' encoding='gbk'?>
				<MemberIntegralExchangeRes>
				    <Status>
			                <StatusCode>" .
                    iconv('utf8', 'gbk', $IntegralArr['StatusCode']) . "</StatusCode>
			                <StatusText>" .
                    iconv('utf8', 'gbk', $IntegralArr['StatusText']) .
                    "</StatusText>
			        </Status>
					<MemberName>" .
                    iconv('utf8', 'gbk', $IntegralArr['MemberName']) .
                    "</MemberName>
					<CardType>" .
                    iconv('utf8', 'gbk', $IntegralArr['CardType']) .
                    "</CardType>
					<TradingType>" .
                    iconv('utf8', 'gbk', $IntegralArr['TradingType']) . "</TradingType>
					<IntegralChangeNumber>" .
                    iconv('utf8', 'gbk', $IntegralArr['IntegralChangeNumber']) .
                    "</IntegralChangeNumber>
					<ResidualIntegral>" .
                    $IntegralArr['ResidualIntegral'] .
                    "</ResidualIntegral>
					<IntegralName>" .
                    iconv('utf8', 'gbk', $IntegralArr['IntegralName']) . "</IntegralName>
					<MemberID>" .
                    $IntegralArr['MemberID'] . "</MemberID>
					<MemberPhone>" .
                    $IntegralArr['MemberPhone'] . "</MemberPhone>
			   </MemberIntegralExchangeRes>";
        }
        if ($type == 5) {
            import('@.ORG.Util.Xml') or die('@.ORG.Util.Xml导入失败');
            $xml = new Xml();
            $resp_xml = $xml->getXMLFromArray($IntegralArr, 'gbk');
        }
        if ($type == 6) {
            $resp_xml = "<?xml version='1.0' encoding='gbk'?>
				<MemberCreateRes>
				    <Status>
			                <StatusCode>" . $IntegralArr['StatusCode'] . "</StatusCode>
			                <StatusText>" . iconv('utf8', 'gbk', $IntegralArr['StatusText']) . "</StatusText>
                    </Status>
			   </MemberCreateRes>";
        }
        log_write($resp_xml . 'RESPONSE');
        echo $resp_xml;
        exit();
    }

    public function memberIntegralExchange($req_arr) {
        $this->checkPosNode($req_arr['PosID'], $req_arr['NodeID'], 4);
        $this->checkIntegralStart($req_arr['NodeID'], 4);
        $map = array(
            'node_id' => $req_arr['NodeID'],
            'member_num' => $req_arr['MemberID']);
        $memberInfo = M("tmember_info")->where($map)->find();
        if ($memberInfo) {
            if ($req_arr['IntegralChangeNumber'] > $memberInfo['point']) {
                $IntegralArr = array(
                    'StatusCode' => '2004',
                    'StatusText' => '您的积分不足！');
                $this->notifyReturn($IntegralArr, 4);
            }
            // 终端扣减积分
            M()->startTrans();
            // 调用积分兑换接口
            $IntegralPointTrace = new IntegralPointTraceModel();
            $result = $IntegralPointTrace->integralPointChange('11',
                $req_arr['IntegralChangeNumber'], $memberInfo['id'],
                $req_arr['NodeID'], $req_arr['PosSeq'], '');
            if ($result === false) {
                M()->rollback();
                $IntegralArr['StatusCode'] = '2005';
                $IntegralArr['StatusText'] = '积分兑换失败';
                $this->notifyReturn($IntegralArr, 4);
            }
            // 增加行为数据
            $res = $this->MemberBehaviorModel->addBehaviorType($memberInfo['id'],
                $req_arr['NodeID'], 12, $req_arr['IntegralChangeNumber'],
                $req_arr['PosID']);
            if ($res === false) {
                log_write("新增行为数据失败！");
            }
            M()->commit();
            $integralName = $this->_integralName($req_arr['NodeID']);
            $IntegralArr = array(
                'StatusCode' => '0000',
                'StatusText' => '兑换兑换成功',
                'MemberName' => $this->replaceMemberName($memberInfo['name']),
                'CardType' => $this->getMemberCardName($memberInfo['id'],
                    $req_arr['NodeID'], '1'),
                'TradingType' => '积分扣减',
                'IntegralChangeNumber' => $req_arr['IntegralChangeNumber'],
                "ResidualIntegral" => $result['after_num'],
                'IntegralName' => $integralName,
                'MemberID' => $memberInfo['member_num'],
                'MemberPhone' => $memberInfo['phone_no']);
        } else {
            $IntegralArr = array(
                'StatusCode' => '2003',
                'StatusText' => '未找到会员信息');
        }
        $this->notifyReturn($IntegralArr, 4);
    }
    // 通知应答

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

    public function _addIntegralWeiXinOrder($integralOrderArr, $order_id, 
        $node_id) {
        $data = array(
            'order_id' => $order_id,
            'node_id' => $node_id,
            'status_code' => $integralOrderArr['StatusCode'],
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
            'add_time' => date('YmdHis'),
            'type' => $integralOrderArr['type']);
        $res = M("tintegral_weixin_order")->add($data);
        if ($res === false) {
            return false;
        }
    }

    public function checkPosNode($pos, $node_id, $type) {
        if (empty($pos) || empty($node_id)) {
            $IntegralArr['StatusCode'] = '2011';
            $IntegralArr['StatusText'] = '缺少必要参数';
            $this->notifyreturn($IntegralArr, $type);
        }
        $res = M("tpos_info")->where(
            array(
                'node_id' => $node_id, 
                'pos_id' => $pos))->find();
        if (empty($res)) {
            log_write('【'.__LINE__.'】'.'旺财不存在此终端号');
            if($type==5){
                $IntegralArr = array(
                        'MemberCarListRes' => array(
                                'Status' =>array('StatusCode'=>'2012','StatusText'=>"旺财不存在此终端号"),
                                'CardType' => '',
                        ));
            }else{
                $IntegralArr['StatusCode'] = '2012';
                $IntegralArr['StatusText'] = '旺财不存在此终端号';
            }
            $this->notifyreturn($IntegralArr, $type);
        }
    }
    // 校验订单号
    public function checkOrderId($orderId, $node_id) {
        $res = M("tintegral_weixin_order")->where(
            array(
                'node_id' => $node_id, 
                'order_id' => $orderId))->find();
        if ($res) {
            $IntegralArr = array(
                'StatusCode' => $res['status_code'], 
                'StatusText' => $res['status_text'], 
                'IntegralName' => $res['integral_name'], 
                'IntegralChangeNumber' => $res['integral_change_number'], 
                'MemberId' => $res['member_num'], 
                'MemberName' => $res['member_name'], 
                'MemberPhone' => $res['member_phone'], 
                'ResidualIntegral' => $res['residua_integral'], 
                'TradingType' => $res['trading_type'], 
                'CardType' => $res['card_type']);
            $this->notifyReturn($IntegralArr, 1);
        }
    }
    // 校验传输过来的POS终端号是否为该机构商户
    
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

    public function checkIp() {
        // 获取接入端IP
        $ip = $_SERVER['REMOTE_ADDR'];
        if ($ip != C('zc_ip') && $ip !="10.10.1.34" && $ip !="10.10.1.130") {
            // IP不允许接入
            $resp_desc = "IP:" . $ip . "不允许接入";
            $IntegralArr['StatusCode'] = '2013';
            $IntegralArr['StatusText'] = $resp_desc;
            $this->notifyreturn($IntegralArr, 0);
        }
    }
    
    // 校验是否开通积分商城
    public function checkIntegralStart($node_id, $type) {
        if (get_wc_version($node_id) != 'v4') {
            $powers = M("tnode_info")->where(
                array(
                    'node_id' => $node_id))->getField('pay_module');
            if (empty($powers)) {
                $IntegralArr['StatusCode'] = '2009';
                $IntegralArr['StatusText'] = '暂未开通多赢积分模块，请至联系商务开通。';
                $this->notifyreturn($IntegralArr, $type);
            }
            $powers = explode(",", $powers);
            if (! in_array("m4", $powers)) {
                $IntegralArr['StatusCode'] = '2009';
                $IntegralArr['StatusText'] = '暂未开通多赢积分模块，请至联系商务开通。';
                $this->notifyreturn($IntegralArr, $type);
            }
        }
        $res = M("tmarketing_info")->where(
            array(
                'node_id' => $node_id, 
                'batch_type' => $this->INTEGRAL_BATCH_TYPE))->find();
        if (empty($res)) {
            $IntegralArr['StatusCode'] = '2009';
            $IntegralArr['StatusText'] = '暂未开通多赢积分模块，请至联系商务开通。';
            $this->notifyreturn($IntegralArr, $type);
        }
    }
    
    // 替换会员名称后几位
    public function replaceMemberName($memberName) {
        if ($memberName) {
            return substr_replace($memberName, "*", 3);
        }
    }
    
    // 非付满送的微信支付需要处理
    public function integralWeiXinOrderEx($req_arr, $memberId) {
        $data = array(
            'node_id' => $req_arr['NodeID'], 
            'amt' => $req_arr['ConsumptionAmount'], 
            'pos_id' => $req_arr['PosID'], 
            'open_id' => $memberId, 
            'pos_seq' => $req_arr['PosSeq'], 
            'add_time' => date('YmdHis'), 
            'type' => '2');
        $res = M("tintegral_weixin_order_ex")->add($data);
        if ($res === false) {
            return false;
            log_write("微信支付订单插入失败..." . M()->_sql());
        }
        return true;
    }
    
    // 新增行为数据
    public function IntegralAddBehavior($memberId, $nodeId, $change_num, 
        $relationId, $amt,$behavior) {
        log_write('【'.__LINE__.'】$behavior 是'.$behavior);
        $behaviorDataStatus = $this->IntegralConfigNodeModel->addBehaviorData(
            $memberId, $nodeId, $change_num, $relationId, $amt,$behavior);
        if ($behaviorDataStatus === false) {
            $IntegralArr = array(
                'StatusCode' => '2008', 
                'StatusText' => '绑定会员失败');
            $this->notifyReturn($IntegralArr, 1);
        }
    }

    /**
     * @param $nodeId           商户号
     * @param $memberPhone      手机号
     * @param $openId           openId
     * @param $changeNum        空
     * @param $channelId        channel_id
     * @param $payType          支付方式
     * @return bool
     */
    public function addMemberOne($nodeId, $memberPhone, $openId, $changeNum, 
        $channelId,$payType='') {
        $memberAdd = D('MemberInstall', 'Model');
        // 生成会员卡编号
        $option = array(
            'channel_id' => $channelId, 
            'openid' => $openId, 
            'point' => $changeNum);

        //请求报文传来的payType 1为支付宝支付 2为微信支付
        //channel表中的id  1为微信支付 2为支付宝支付
        //因此做以下调换
        if($payType == 1){
            $status = 2;
        }
        if($payType == 2){
            $status = 1;
        }
        $MemberInfo = $memberAdd->wxTermMemberFlagByIntegral($nodeId, $memberPhone, 1,
            true, $option,$status);
        if (empty($MemberId) === false) {
            return false;
        }
        return $MemberInfo;
    }
    /*
     * 会员卡查询接口
     */
    public function memberCarList($req_arr){
        $this->checkPosNode($req_arr['PosID'], $req_arr['NodeID'], 5);
        $list=M("tmember_cards")->where(array('node_id'=>$req_arr['NodeID']))->select();
        $cardList=array();
        if($list){
            foreach($list as $key=>$val){
                //$cardList[$val['id']]=$val['card_name'];
                $cardList[]=array('CardId'=>$val['id'],'CardName'=>$val['card_name']);
            }
        }
        if($cardList){
            $status=array('StatusCode'=>'0000','StatusText'=>"查询成功");
        }else{
            $status=array('StatusCode'=>'2017','StatusText'=>"未查询到会员卡");
        }
        $req_array = array(
                'MemberCarListRes' => array(
                        'Status' => $status,
                        'CardType' => array('Item'=>$cardList),
                        ));
        $this->notifyReturn($req_array, '5');
    }
    /*
     * 新增会员接口
     */
    public function addMember($req_arr){
        $res=M("tmember_info")->where(array('node_id'=>$req_arr['NodeID'],'phone_no'=>$req_arr['MemberPhone']))->find();
        if($res){
            $IntegralArr = array(
                    'StatusCode' => '2018',
                    'StatusText' => '输入手机号码已经为会员！');
            $this->notifyReturn($IntegralArr, 6);
        }
        //判断会员卡是否存在
        $cardsStatus=M("tmember_cards")->where(array('id'=>$req_arr['CardId'],'node_id'=>$req_arr['NodeID']))->find();
        if(!$cardsStatus){
            $IntegralArr = array(
                    'StatusCode' => '2017',
                    'StatusText' => '未查询到该会员卡！');
            $this->notifyReturn($IntegralArr, 6);
        }
        $labelName=M("tpos_info")->where(array('node_id'=>$req_arr['NodeID'],'pos_id'=>$req_arr['PosID']))->getField('pos_short_name');
        if(!$labelName){
            $IntegralArr = array(
                    'StatusCode' => '2012',
                    'StatusText' => '旺财上无此终端号！');
            $this->notifyReturn($IntegralArr, 6);
        }
        $labelName=$labelName."(".$req_arr['PosID'].")";
        $labelStatus=M("tmember_label")->where(array('label_name'=>$labelName,'node_id'=>$req_arr['NodeID']))->getField('id');
        M()->startTrans();
        if(!$labelStatus){
            $data=array(
                    'label_name'=>$labelName,
                    'node_id'=>$req_arr['NodeID'],
                    'add_time'=>date('YmdHis')
            );
            $labelStatus=M("tmember_label")->add($data);
            if(!$labelStatus){
                $IntegralArr = array(
                        'StatusCode' => '2019',
                        'StatusText' => 'EPOS添加会员标签失败！');
                M()->rollback();
                $this->notifyReturn($IntegralArr, 6);
            }
        }
        $data = array(
                'node_id' => $req_arr['NodeID'],
                'phone_no' => $req_arr['MemberPhone'],
                'sex' => '1',
                'years' => date('Y'),
                'month_days' => date('md'),
                'status' => '0',
                'add_time' => date('YmdHis'),
                'channel_id' => CommonConst::CHANNEL_TYPE_EPOS_ID,
                'card_id' =>$req_arr['CardId']);
        $res=M("tmember_info")->add($data);
        if ($res === false) {
            $IntegralArr = array(
                    'StatusCode' => '2016',
                    'StatusText' => '新增会员失败！');
            M()->rollback();
            $this->notifyReturn($IntegralArr, 6);
        }
        //新增会员与标签的关系
        $labelArr=array(
            'node_id'=>$req_arr['NodeID'],
            'member_id'=>$res,
            'label_id'=>$labelStatus
        );
        $memberLabelEx=M("tmember_label_ex")->add($labelArr);
        if($memberLabelEx===false){
            $IntegralArr = array(
                    'StatusCode' => '2019',
                    'StatusText' => 'EPOS添加会员标签失败！');
            M()->rollback();
            $this->notifyReturn($IntegralArr, 6);
        }
        M()->commit();
        $IntegralArr = array(
                'StatusCode' => '0000',
                'StatusText' => '添加成功！');
        $this->notifyReturn($IntegralArr, 6);
    }
}