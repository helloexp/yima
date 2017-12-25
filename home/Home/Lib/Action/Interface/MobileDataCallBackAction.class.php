<?php
class MobileDataCallBackAction extends Action
{
    //密钥
    protected $appKey = '093470ab5fb061c193c081e1926f23a6';
    public function __construct() {
        parent::__construct();
        //log_write(print_r($_GET, TRUE));
    }
    //云猴回调
    public function sendMobileCallback()
    {
        $signInfo['out_trade_no'] = I('out_trade_no', '');
        if ($signInfo['out_trade_no'] == '') {
            $this->ajaxReturn(['return_code' => 2000, 'return_msg' => 'out_trade_no NOT FOUND'], 'json');
        }
        $signInfo['transaction_id'] = I('transaction_id', '');
        if ($signInfo['transaction_id'] == '') {
            $this->ajaxReturn(['return_code' => 2001, 'return_msg' => 'transaction_id NOT FOUND'], 'json');
        }
        $signInfo['order_state'] = I('order_state', '');
        if ($signInfo['order_state'] == '') {
            $this->ajaxReturn(['return_code' => 2002, 'return_msg' => 'order_state NOT FOUND'], 'json');
        }
        $err_msg = I('err_msg', '');
        if ($err_msg !== '') {
            $signInfo['err_msg'] = $sendInfoSave['ret_msg'] = $err_msg;
        }

        //获取签名

        /*$sign = I('sign', '0', 'string');
        $this->MobileDataService->checkNotNull($sign, 'sign');*/

        log_write("MobileDataCallbackCallLog:out_trade_no：{$signInfo['out_trade_no']}，transaction_id：{$signInfo['transaction_id']}，order_state:{$signInfo['order_state']},err_msg:{$signInfo['err_msg']}");

        /*if($sign !== $this->sign($signInfo)) {
             $this->ajaxReturn(['return_code'=>'2003','return_msg'=>'签名错误']);
         }*/
        //var_dump($signInfo);exit;
        $where['id'] = $signInfo['out_trade_no'];
        $where['transaction_id'] = $signInfo['transaction_id'];
        $where['_logic'] = 'or';
        if ($signInfo['order_state'] == 0) {
            $sendInfoSave['status'] = 2;
            $sendInfoSave['ret_time'] = date('YmdHis');
            M('tmobile_date_send_trace')->where(
                   $where
            )->save($sendInfoSave);
                $this->ajaxReturn('success', 'EVAL');
        }
         else{
             $sendInfoSave['status'] = 3;
             M('tmobile_date_send_trace')->where($where)->save($sendInfoSave);
             $this->ajaxReturn('success', 'EVAL');
         }

    }

//大汉回调
    public function dhsendMobileCallback(){


        $get_data = file_get_contents("php://input");
        $signInfo = json_decode($get_data, true);//json转化为数组
        log_write("MobileDataCallbackCallLog:clientOrderId：{$signInfo['clientOrderId']}，phone_no：{$signInfo['phone_no']}，status:{$signInfo['status']},reportTime:{$signInfo['reportTime']},errorCode:{$signInfo['errorCode']},errorDesc:{$signInfo['errorDesc']}"."callback_josn:".$get_data);

        if(substr($signInfo['clientOrderId'],0,17) == 'imagecoMobileBill'){
            $model = M('tmobile_bill_send_trace');
        }else{
            $model = M('tmobile_date_send_trace');
        }

        $where['phone_no'] = $signInfo['phone_no'];
        $where['transaction_id'] = $signInfo['clientOrderId'];
        if ($signInfo['status'] == 0) {
            $sendInfoSave['status'] = 2;
            $sendInfoSave['ret_msg'] = $signInfo['errorDesc'];
            $sendInfoSave['ret_time'] = $this->wctime($signInfo['reportTime']);//date('YmdHis');
            $resutlSend = $model->where(
                    $where
            )->save($sendInfoSave);

            if ($resutlSend != false) {
                $this->ajaxReturn(['resultCode'=>'0000','resultMsg'=>'处理成功！'],'json');
            }else{
                $this->ajaxReturn(['return_code'=>'1111','resultMsg'=>'客户端订购 id或手机号错误'],'json');
            }

        }
        else{
            $sendInfoSave['status'] = 3;
            $sendInfoSave['ret_msg'] = $signInfo['errorDesc'];
            M('tmobile_date_send_trace')->where($where)->save($sendInfoSave);
            $this->ajaxReturn(['return_code'=>'20000','return_msg'=>$signInfo['errorDesc'] ]);
        }

    }
    //大汉时间转换
    protected function wctime($time){
        $wctime = date("YmdHis",strtotime($time)); //大汉时间格式 May 19, 2015 2:08:04 PM
        return $wctime;
    }


    protected function sign($data) {
        $ss = '';
        foreach ($data as $index => $item) {
            $ss .= $index.'='.$item.'&';
        }
        $signSrc = $ss . 'key=' . $this->appKey;
        return $signSrc;
    }


}


?>