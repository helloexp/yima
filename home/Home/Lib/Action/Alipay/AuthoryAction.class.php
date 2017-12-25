<?php

/**
 * Created by PhpStorm.
 * User: wangy
 * Date: 2016/3/9
 * Time: 13:49
 */
class AuthoryAction extends BaseAction
{
    private $app_id;

    public function _initialize()
    {
        parent::_initialize();
        // 翼码旺财的企业应用id
        $this->app_id = C('ALIPAY_AUTHORY')['app_id'];
    }

    /**
     * 此页面无需判断权限
     */
    public function beforeCheckAuth()
    {
        $this->_authAccessMap = '*';
    }

    /**
     * 前往支付宝授权页面
     */
    public function index()
    {
        // 支付宝授权的请求地址
        $alipay_url = C('ALIPAY_AUTHORY')['request_url'];
        // 回调地址
        $payType = I('pay_type');
        if (empty($payType)) {
            $payType = 'main';
        }
        $callback_url = U('Alipay/Authory/callback_url', ['pay_type' => $payType], false, false, true);
        // 请求的参数
        $param = array(
                'app_id'       => $this->app_id,
                'redirect_uri' => $callback_url,
        );

        $url = $alipay_url . '?' . http_build_query($param);

        if(I('type') == 'wap'){
            //APP来的
            if($this->position){
                import('@.Vendor.MakeCode') or die('include file fail.');
                $makecode = new MakeCode();
                $logourl = '';
                $color = '000000';
                if(I('down') == '1'){
                    //下载二维码
                    $filename = "支付宝授权";
                    $makecode->MakeCodeImg($url, true, '', $logourl,$filename, $color);
                }else{
                    $makecode->MakeCodeImg($url, false, '', $logourl, '', $color);
                }
            }
        }else{
            // 跳转到支付宝授权页面
            redirect($url);
        }

    }

    /**
     * 支付宝授权回调地址
     */
    public function callback_url()
    {
        // 获取token
        $res = self::get_app_auth_token(I('get.app_auth_code'));
        // 如果获取成功，则插入到tzfb_offline_pay_info表
        if ($res['code'] == '10000') {
            if (!self::save_token($res)) {
                log_write('授权失败:x001原因：'.M()->_sql());
                $this->error('授权失败:x001!');exit;
            }
            $this->success('您已授权成功',array('关闭'=>'javascript:window.close();'));
        }else{
            $this->error('授权失败:x003!');
        }
    }

    /**
     * (获取商户应用token)
     *
     * @param $app_auth_code [应用授权码]
     *
     * @return array
     */
    private function get_app_auth_token($app_auth_code)
    {
        import('@.Vendor.Alipay.AopClient', '', '.php');
        import('@.Vendor.Alipay.SignData', '', '.php');
        import('@.Vendor.Alipay.request.AlipayOpenAuthTokenAppRequest', '', '.php');

        $aop                        = new AopClient ();
        $aop->gatewayUrl            = C('ALIPAY_AUTHORY')['token_url'];
        $aop->appId                 = $this->app_id;
        $aop->rsaPrivateKeyFilePath = C('ALIPAY_AUTHORY')['prikey_path'];
        $aop->alipayPublicKey       = C('ALIPAY_AUTHORY')['pubkey_path'];
        $aop->apiVersion            = '1.0';
        $aop->postCharset           = 'UTF-8';
        $aop->format                = 'json';

        $request      = new AlipayOpenAuthTokenAppRequest ();
        $post_content = array(
                'grant_type' => 'authorization_code',
                'code'       => $app_auth_code
        );
        $request->setBizContent($post_content);

        $response = $aop->execute($request, null)->alipay_open_auth_token_app_response;

        log_write('商户' . $this->nodeId . '支付宝获取token返回结果：' . print_r($response, true));

        return (array)$response;
    }

    private function save_token($data)
    {
        $payType = I('pay_type');
        if (empty($payType) || $payType == 'main') {
            $payType = 0;
        } else {
            $payType = 50;
        }

        $map = array(
                'node_id'   => $this->nodeId,
                'pay_type'  => $payType
        );
        M()->startTrans();
        $zfbAccount = isset($data['user_id']) ? $data['user_id'] : '';
        if ($payType == 50 && $zfbAccount) { //支付宝子帐号
            $map['zfb_account'] = $zfbAccount;
        }
        $res = M('tzfb_offline_pay_info')->where($map)->find();

        if (!empty($res)) {
            $saveData = array(
                    'apply_data'   => json_encode($data),
                    'check_status' => '1',
                    'sign_status'  => '1',
                    'status'       => '1',
                    'auth_flag'    => '2',
                    'zfb_account' => $zfbAccount,
            );
            $saveRet = M('tzfb_offline_pay_info')->where($map)->save($saveData);
            if($saveRet === false){
                M()->rollback();
                return false;
            }
        } else {
            $data_arr = array(
                    'add_time'     => date('YmdHis'),
                    'node_name'    => $this->nodeInfo['node_name'],
                    'node_mail'    => $this->nodeInfo['contact_eml'],
                    'check_status' => '1',
                    'sign_status'  => '1',
                    'status'       => '1',
                    'auth_flag'    => '2',
                    'apply_data'   => json_encode($data),
                    'zfb_account' => $zfbAccount,
                    'open_time' => date('YmdHis'),
            );
            $addRet = M('tzfb_offline_pay_info')->add(array_merge($data_arr, $map));
            if(!$addRet){
                M()->rollback();
                return false;
            }
        }
        if(!self::notice_yz($data)){
            M()->rollback();
            return false;
        }
        M()->commit();
        return true;
    }

    private function notice_yz($data)
    {
        $TransactionID = date("His") . mt_rand(100000, 999999);
        $req_arr       = array(
                'AlipaySellerSetReq' => array(
                        'TransactionID' => $TransactionID,
                        'SystemID'      => C('YZ_SYSTEM_ID'),
                        'ContractID'    => $this->nodeInfo['contract_no'],
                        'Token'         => $data['app_auth_token']
                )
        );
        $RemoteRequest = D('RemoteRequest', 'Service');
        $result        = $RemoteRequest->requestYzServ($req_arr);
        if ($result['Status']['StatusCode'] != '0000') {
            log_write($this->nodeId . '通知营帐token失败' . print_r($result, true));
            return false;
        }
        log_write($this->nodeId . '通知营帐token成功' . print_r($result, true));
        return true;
    }
    private function sendEmail($res){
        $email_arr = array(
                "yangyang@imageco.com.cn",
                "zhengfj@imageco.com.cn",
                "yangbs@imageco.com.cn"
        );
        // 邮箱发送
        $contents = "商户【" . $this->nodeInfo['node_name'] . "】<br/>现申请支付宝授权；请尽快联系客户，完成开通。
                联系电话：" . $this->nodeInfo['contact_phone'] . "<br/>日期：" . date(
                        'Y-m-d H:i:s'
                ) . "<br/>商户的token信息如下：结算号（" . $this->nodeInfo['contract_no'] . "）token=>" . json_encode($res);
        foreach ($email_arr as $email) {
            $ps = array(
                    "subject" => "“支付宝授权开通申请”",
                    "content" => $contents,
                    "email"   => $email
            );
            send_mail($ps);
        }

    }
}