<?php

/**
 * 话费发送接口
 *
 * @author bao
 */
class MobileBillAction extends Action {

    //大汉流量包请求url
    protected $dahanUrl ;//= "http://test.dahanbank.cn:3429/if/FCPhoneBillOrderServlet"; //测试地址
    //FCPhoneBillOrderServlet  正式下单地址  http://if.dahanbank.cn/FCPhoneBillOrderServlet
    //大汉账号
    protected $user ; //= "admin_YM"; 'AdminYim';//
    //大汉密码
    protected $pwd ; //= "123456"; 'test0257';//

    public $sendMobileBillService;

    public function __construct() {
        parent::__construct();
        $this->user = C('MOBILE_DATA')['user'];//"admin_YM";//
        $this->pwd = 1212;//C('MOBILE_DATA')['pwd'];//"123456"; //
        $this->dahanUrl = C('MOBILE_DATA')['dahanUrl'].'/FCPhoneBillOrderServlet'; //"http://test.dahanbank.cn:3429/if/FCPhoneBillOrderServlet";//
        $this->sendMobileBillService = D('MobileData', 'Service');
    }


    public function sendMobileBill() {

                    $node_id = I('node_id', '0', 'string');
                    $this->sendMobileBillService->checkNotNull($node_id, 'node_id');

                    $phone_no = I('phone_no', '0', 'string');
                    $this->sendMobileBillService->checkNotNull($phone_no, 'phone_no');

                    $request_id = I('request_id', '0', 'string');
                    $this->sendMobileBillService->checkNotNull($request_id, 'request_id');

                    $batch_info_id = I('batch_info_id', '0', 'string');
                    $this->sendMobileBillService->checkNotNull($batch_info_id, 'batch_info_id');

                    $m_id = I('m_id', '0', 'string');
                    $this->sendMobileBillService->checkNotNull($m_id, 'm_id');

                    $shopInfo = M('tnode_info')->where(array('node_id' => $node_id))->getField('status');

                    if ($shopInfo['status'] == '1') {
                        $this->ajaxReturn(['resp_id' => -1023, 'resp_desc' => '账号已停用'], 'json');
                    } elseif($shopInfo['status'] == '2') {
                        $this->ajaxReturn(['resp_id' => -1024, 'resp_desc' => '您的帐号已注销'], 'json');
                    }
                    $mobile_type = M('tphone_type')->where(array('phone_no_head' => substr($phone_no,0,3)))->getField('phone_type');
                    if(!$mobile_type){
                        $this->ajaxReturn(['resp_id' => -1025, 'resp_desc' => '手机号格式有误'], 'json');
                    }

                    //通过bid查询tgoodsinfo的id再根据gid查询套餐信息
                    $goods_id = M('tbatch_info')->where(array('id' => $batch_info_id))->getField('goods_id');

                    $g_info = M('tgoods_info')->where(array('goods_id' => $goods_id))->field('id,source,market_price,purchase_goods_id')->find();

                    if($g_info['source'] == 0){
                        $bill_package = intval($g_info['market_price']);
                    }elseif($g_info['source'] == 1){
                        $bill_package1 = M('tgoods_info')->where(array('goods_id' => $g_info['purchase_goods_id']))->getField('market_price');
                        $bill_package = intval($bill_package1);
                    }else{
                        $this->ajaxReturn(['resp_id' => -1100, 'resp_desc' => '没有该卡券'], 'json');
                    }

                    if(!$goods_id){
                        $this->ajaxReturn(['resp_id' => -1101, 'resp_desc' => '没有该商品'], 'json');
                    }

                    if(!$goods_id){
                        //$sendData['b_id'] = $batch_info_id;
                        $this->ajaxReturn(['resp_id' => -1027, 'resp_desc' => '活动不存在'], 'json');
                    }
                    $this->nodeId = $node_id;
                    $sendData['node_id'] =	$node_id;
                    $sendData['phone_no'] = $phone_no;
                    $sendData['status'] = '-';
                    $sendData['request_id'] = $request_id;
                    $sendData['goods_id'] = $goods_id;
                    $sendData['bill_package'] = $bill_package;
                    $sendData['b_id'] = $batch_info_id;
                    $sendData['m_id'] = $m_id;
                    $sendData['add_time'] = date('YmdHis');
/*        $sendData['node_id'] =	"000000";
        $sendData['phone_no'] = "18616617615";
        $sendData['status'] = '-';
        $sendData['request_id'] = "12323";
        $sendData['goods_id'] = '2332';
        $sendData['bill_package'] = 10;
        $sendData['b_id'] = 1231;
        $sendData['m_id'] = 1212;
        $sendData['add_time'] = date('YmdHis');*/

            //流水表插入记录
            $mobileId = M('tmobile_bill_send_trace')->add($sendData);
            if(!$mobileId){
                $this->ajaxReturn(['resp_id' => -1104, 'resp_desc' => '插入流水失败'], 'json');
            }
                $sendInfo['price'] = $bill_package;
                $sendInfo['sign'] = $this->dhsign($phone_no);//$phone_no);
                $sendInfo['account'] = $this->user;
                $sendInfo['mobiles'] = $phone_no; //$phone_no;
                $sendInfo['timestamp'] = $this->mtime();
                $sendInfo['clientOrderId'] = 'imagecoMobileBill'.$mobileId;
                $resutl = httpPost($this->dahanUrl,json_encode($sendInfo));
                $res = json_decode($resutl);
               // var_dump($res);exit;
                if($res->resultCode == '00'){
                    $sendInfoSave['ret_code'] = $res->resultCode;
                    $sendInfoSave['ret_msg'] = $res->resultMsg;
                    $sendInfoSave['status'] = 0;
                    //clientOrderId
                    $sendInfoSave['transaction_id'] = $res->clientOrderId;//订单号使用云猴订单号字段
                    M('tmobile_bill_send_trace')->where(array('id' => $mobileId))->save($sendInfoSave);
                    $this->ajaxReturn(['resp_id' => '0000', 'resp_desc' => '发送成功'], 'json');

                }else{
                    $sendInfoSave['ret_code'] = $res->resultCode;
                    $sendInfoSave['ret_msg'] = $res->resultMsg;
                    $sendInfoSave['status'] = 1;
                    $sendInfoSave['transaction_id'] = $res->clientOrderId;
                    M('tmobile_bill_send_trace')->where(array('id' => $mobileId))->save($sendInfoSave);
                    $this->ajaxReturn(['resp_id' => $res->resultCode, 'resp_desc' => $res->resultMsg], 'json');
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



}