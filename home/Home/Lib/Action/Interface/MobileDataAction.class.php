<?php

/**
 * 流量包发送接口
 *
 * @author bao
 */
class MobileDataAction extends Action {
    // 错误信息
    public $error = '';
    // 错误码
    public $errCode = '';
    // 流水号
    protected $mchId = '';

    protected $partner_id ; //= '6228987261';
    //密钥
    protected $appKey ; //= '093470ab5fb061c193c081e1926f23a6';
    // 商户号
    protected $nodeId = '';
    //大汉流量包请求url
    protected $dahanUrl ; //= "http://test.dahanbank.cn:3429/if/FCOrderServlet"; //测试地址
    //http://if.dahanbank.cn/FCOrderServlet  正式下单地址
    //大汉账号
    protected $user ; //= "admin_YM";
    //大汉密码
    protected $pwd ; //= "123456";
    /**
     * @var MobileDataService
     */
    public $MobileDataService;

    // 流量包接口请求url
    protected $sendUrl ; //"https://api.5160.com/business";

    public function __construct() {
        parent::__construct();
        log_write(print_r($_GET, TRUE));
        $this->partner_id = C('MOBILE_DATA')['partner_id'];
        $this->appKey = C('MOBILE_DATA')['appkey'];
        $this->dahanUrl = C('MOBILE_DATA')['dahanUrl'].'/FCOrderServlet';
        $this->user = C('MOBILE_DATA')['user'];
        $this->pwd = C('MOBILE_DATA')['pwd'];
        $this->sendUrl = C('MOBILE_DATA')['sendUrl'];
        $this->MobileDataService = D('MobileData', 'Service');
    }


    public function sendMobileData() {
        $mobileType = 0;//M('tsystem_param')->where(['param_name'=>'MOBILE_SEND_CHANNEL'])->getField('param_value');
           $node_id = I('node_id', '0', 'string');
            $this->MobileDataService->checkNotNull($node_id, 'node_id');
            $this->nodeId = $node_id;
            $phone_no = I('phone_no', '0', 'string');
            $this->MobileDataService->checkNotNull($phone_no, 'phone_no');
            $request_id = I('request_id', '0', 'string');
            $this->MobileDataService->checkNotNull($request_id, 'request_id');
            $batch_info_id = I('batch_info_id', '0', 'string');
            $this->MobileDataService->checkNotNull($batch_info_id, 'batch_info_id');
            $m_id = I('m_id', '0', 'string');
            $this->MobileDataService->checkNotNull($m_id, 'm_id');
            $shopInfo = M('tnode_info')->where(array('node_id' => $node_id))->getField('status');
            if ($shopInfo['status'] == '0') {
                //$sendData['node_id'] =	$node_id;

            } elseif ($shopInfo['status'] == '1') {
                $this->ajaxReturn(['resp_id' => -1023, 'resp_desc' => '账号已停用'], 'json');
            } else {
                $this->ajaxReturn(['resp_id' => -1024, 'resp_desc' => '请核对您的帐号'], 'json');
            }
            $mobile_type = M('tphone_type')->where(array('phone_no_head' => substr($phone_no,0,3)))->getField('phone_type');
            if(!$mobile_type){
                $this->ajaxReturn(['resp_id' => -1025, 'resp_desc' => '手机号格式有误'], 'json');
            }
            //通过bid查询tgoodsinfo的id再根据gid查询套餐信息
            $goods_id = M('tbatch_info')->where(array('id' => $batch_info_id))->getField('goods_id');
            $g_info = M('tgoods_info')->where(array('goods_id' => $goods_id))->field('id,source,purchase_goods_id')->find();

            if($g_info['source'] == 0){
                $g_id = $g_info['id'];
            }elseif($g_info['source'] == 1){
                $g_id = M('tgoods_info')->where(array('goods_id' => $g_info['purchase_goods_id']))->getField('id');
            }else{
                $this->ajaxReturn(['resp_id' => -1100, 'resp_desc' => '没有该卡券'], 'json');
            }

            if(!$goods_id){
                $this->ajaxReturn(['resp_id' => -1101, 'resp_desc' => '没有该商品'], 'json');
            }
            $mobileInfo = M('tmobile_data_param')->where(['g_id' => $g_id, 'mobile_type' => $mobile_type])->field('id,data_package,custom_goods_id')->find();
            if(!$mobileInfo){
                $this->ajaxReturn(['resp_id' => -1102, 'resp_desc' => '查询流量包错误'], 'json');
            }
            if(!$goods_id){
                $this->ajaxReturn(['resp_id' => -1027, 'resp_desc' => '活动不存在'], 'json');
            }
            $this->nodeId = $node_id;
            $sendData['mobile_type'] = $mobile_type;
            $sendData['node_id'] =	$node_id;
            $sendData['phone_no'] = $phone_no;
            $sendData['status'] = '-';
            $sendData['request_id'] = $request_id;
            $sendData['goods_id'] = $goods_id;
            $sendData['data_package'] = $mobileInfo['data_package'];
            $sendData['b_id'] = $batch_info_id;
            $sendData['m_id'] = $m_id;
            $sendData['add_time'] = date('YmdHis');
            $sendData['send_channel'] = $mobileType;
            //流水表插入记录
            $mobileId = M('tmobile_date_send_trace')->add($sendData);
            if(!$mobileId){
                $this->ajaxReturn(['resp_id' => -1104, 'resp_desc' => '插入流水失败'], 'json');
            }

          if($mobileType ==0){
              //云猴
              $sendInfo['goods_id'] = $mobileInfo['custom_goods_id'];//"030000002000";//
              $sendInfo['nonce_str'] = $this->getNonceStr();
              $sendInfo['notify_url'] = C('CURRENT_DOMAIN').'index.php?g=Interface&m=MobileDataCallBack&a=sendMobileCallback';//不能用大U
              $sendInfo['out_trade_no'] = 'imageco'.$mobileId;//date('YmdHis').mt_rand(0,100);
              $sendInfo['partner_id'] = $this->partner_id;
              $sendInfo['phone'] = $phone_no;//18616617615;//

              $url = $this->sendUrl.'/postorder';
              $sendInfo['sign'] = $this->sign($sendInfo);

              $params = http_build_query($sendInfo);

              //$resutl = $this->curlPostSsl($url, $params,$mobileInfo['id']);
              log_write("MobileDataCallLog:{$node_id}-".print_r($params,1)." result:".var_export($resutl,1));
              $resutl = httpPost($url,$params);
              if ($resutl) { //成功
                  log_write("MobileDataBackLog:{$node_id}-{$resutl}");
                  $res = json_decode($resutl);
                  if ( $res->return_code === 0) {
                      $sendInfoSave['ret_msg'] = $this->ret_msg;
                      $sendInfoSave['ret_code'] = $res->return_code;
                      $sendInfoSave['transaction_id'] = $res->transaction_id;
                      $sendInfoSave['send_channel'] = 0;
                      $sendInfoSave['status'] = 0;
                      M('tmobile_date_send_trace')->where(array('id' => $mobileId))->save($sendInfoSave);

                      $this->ajaxReturn(['resp_id' => '0000', 'resp_desc' => '发送成功'], 'json');
                  } else {
                      $sendInfoSave['ret_msg'] = $this->ret_msg;
                      $sendInfoSave['ret_code'] = $res->return_code;
                      $sendInfoSave['status'] = 1;
                      M('tmobile_date_send_trace')->where(array('id' => $mobileId))->save($sendInfoSave);
                      $this->ajaxReturn(['resp_id' => $res->return_code, 'resp_desc' => $res->return_msg], 'json');
                  }
              } else {

                  $sendInfoTwo['nonce_str'] = $this->getNonceStr();
                  $sendInfoTwo['partner_id'] = $this->partner_id;
                  $sendInfoTwo['trade_no'] = 'imageco'.$mobileId;
                  $sendInfoTwo['sign'] = $this->sign($sendInfo);
                  $urlTwo = $this->sendUrl."queryorderbytradeno?".urldecode(http_build_query($sendInfo));
                  log_write("MobileDataGetCallLog:{$node_id}-{$urlTwo}");
                  $ch = curl_init();
                  curl_setopt($ch, CURLOPT_URL, $urlTwo);
                  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    // 要求结果为字符串且输出到屏幕上
                  curl_setopt($ch, CURLOPT_HEADER, 0); // 不要http header 加快效率
                  curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
                  curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                  $output = curl_exec($ch);
                  curl_close($ch);

                  if($output){
                      $outputInfo = json_decode($output, true);
                      log_write("MobileDataGetCallLog:{$node_id}-{$outputInfo}");
                      if($outputInfo->return_code == 0 ){
                          if($outputInfo->order_state == 0 || $outputInfo->order_state == 1){
                              $saveSend['transaction_id'] = $outputInfo->transaction_id;;
                              $saveSend['status'] = 0;
                              $saveSend['send_channel'] = 0;
                              M('tmobile_date_send_trace')->where(array('id' => $mobileId))->save($saveSend);
                              $this->ajaxReturn(['resp_id' => -1028, 'resp_desc' => $outputInfo], 'json');
                          }
                      }else{
                          $this->ajaxReturn(['resp_id' => -1029, 'resp_desc' => '查询订单失败'], 'json');
                      }
                  }else{
                      $this->ajaxReturn(['resp_id' => -1030, 'resp_desc' => '发送失败'], 'json');
                  }
              }
              exit;
            }elseif($mobileType ==1){ //大汉
                $sendInfo['packageSize'] = $mobileInfo['data_package'];
                $sendInfo['sign'] = $this->dhsign($phone_no);
                $sendInfo['account'] = $this->user;
                $sendInfo['mobiles'] = $phone_no;//18616617615;
                $sendInfo['clientOrderId'] = 'imageco'.$mobileId;
                $sendInfo['timestamp'] = $this->mtime();
                $resutl = httpPost($this->dahanUrl,json_encode($sendInfo));
              //网络超时查询订单  FCCheckOrderExistedServlet
              if($resutl === false ){
                    $mobileCheck['account'] = $this->user;
                    $mobileCheck['sign'] = md5($this->user.md5($this->pwd));
                    $mobileCheck['clientOrderId'] = 'imageco'.$mobileId;
                  $dhurl = $this->dahanUrl.'/FCCheckOrderExistedServlet?'.urldecode(http_build_query($mobileCheck));
                  log_write("dahanurl:-{$dhurl}");
                    $mobileCheckRes = httpGet($dhurl);
                    $mbres = json_decode($mobileCheckRes);
                  if($mbres){
                      if($mbres->resultMsg == "0"){
                          $mbsave['status'] = 2;
                      }elseif($mbres->resultMsg == "1"){
                          $mbsave['status'] = 0;
                      }else{
                          $mbsave['status'] = 3;
                      }
                      M('tmobile_date_send_trace')->where(array('id' => $mobileId))->save($mbsave);
                  }
                  $this->ajaxReturn(['resp_id' => '0000', 'resp_desc' => '发送成功'], 'json');
                  exit;
              }
                $res = json_decode($resutl);
              $sendInfoSave['send_channel'] = 1;
                if($res->resultCode == '00'){
                    $sendInfoSave['ret_code'] = $res->resultCode;
                    $sendInfoSave['ret_msg'] = $res->resultMsg;
                    $sendInfoSave['status'] = 0;
                    $sendInfoSave['transaction_id'] = $res->clientOrderId;//订单号使用云猴订单号字段
                    M('tmobile_date_send_trace')->where(array('id' => $mobileId))->save($sendInfoSave);
                    $this->ajaxReturn(['resp_id' => '0000', 'resp_desc' => '发送成功'], 'json');

                }else{
                    $sendInfoSave['ret_code'] = $res->resultCode;
                    $sendInfoSave['ret_msg'] = $res->resultMsg;
                    $sendInfoSave['status'] = 1;
                    $sendInfoSave['transaction_id'] = $res->clientOrderId;//订单号使用云猴订单号字段
                    M('tmobile_date_send_trace')->where(array('id' => $mobileId))->save($sendInfoSave);
                    $this->ajaxReturn(['resp_id' => '1111', 'resp_desc' => $res->resultMsg], 'json');
                }
                exit;
            }
        exit;
    }

    //大汉签名
    protected function dhsign($phone){
       $sign = md5($this->user.md5($this->pwd).$this->mtime().$phone );
        return $sign;
    }
//时间转换为我们的时间格式
    protected function wctime($time){
        $wctime = date("YmdHis",strtotime($time)); //大汉时间格式 May 19, 2015 2:08:04 PM
        return $wctime;
    }


//获取毫秒时间戳
    protected function mtime()
    {
        list($tmp1, $tmp2) = explode(' ', microtime());
        $msec =  (float)sprintf('%.0f', (floatval($tmp1) + floatval($tmp2)) * 1000);
        return $msec;
    }

    /**
     * 获取签名
     *
     * @param array $data
     *
     */
    protected function sign($data) {
        $ss = '';
        foreach ($data as $index => $item) {
            $ss .= $index.'='.$item.'&';
        }
        $signSrc = $ss . 'key=' . $this->appKey;
        $sign = md5($signSrc);
        log_write('origin:$signSrc:'.$signSrc . ' md5:'.$signSrc);
        return $sign;
    }

    /**
     * 获取流量包接口中nonce_str字段
     */
    public function getNonceStr() {
        $str = '1234567890abcdefghijklmnopqrstuvwxyz';
        $t1 = '';
        for ($i = 0; $i < 19; $i ++) {
            $j = rand(0, 35);
            $t1 .= $str[$j];
        }
        return $t1;
    }


}