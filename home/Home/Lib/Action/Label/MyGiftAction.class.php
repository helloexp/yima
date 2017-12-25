<?php

/*
 * 微信收礼
 */
class MyGiftAction extends Action {

    public $node_id = '';

    public $appid = '';

    public $secret = '';

    public $wxUserInfo = '';

    public function _initialize() {
        $this->appid = C('WEIXIN.appid');
        $this->secret = C('WEIXIN.secret');
    }

    public function gift_unpack() {
        // 送礼订单号
        $order_id = I('order_id', null);
        if (! $order_id)
            $this->showMsg('送礼订单错误', 0, $order_id);
        $orderInfo = M('ttg_order_info')->where(
            array(
                'order_id' => $order_id))->find();
        if (! $orderInfo)
            $this->showMsg('送礼订单获取失败', 0, $order_id);
        if ($orderInfo['order_status'] != 0)
            $this->showMsg('送礼订单状态异常，无法领取礼品', 0, $order_id);
        if ($orderInfo['pay_status'] != 2)
            $this->showMsg('送礼订单支付状态异常，无法领取礼品', 0, $order_id);
        
        $have_count = M('torder_trace')->where(
            array(
                'order_id' => $order_id))->count();
        if ($have_count >= $orderInfo['buy_num']) {
            $this->showMsg('来晚咯~礼品已领用完！', 0, $order_id);
        }
        $this->assign('order_id', $order_id);
        $this->display();
    }
    // 微信认证
    public function goAuthorize() {
        $order_id = I('order_id', null);
        $rece_phone = I('rece_phone', null);
        
        if (! $order_id)
            $this->showMsg('送礼订单错误', 0, $order_id);
        $orderInfo = M('ttg_order_info')->where(
            array(
                'order_id' => $order_id))->find();
        if (! $orderInfo)
            $this->showMsg('送礼订单获取失败', 0, $order_id);
            
            // 授权地址
        $open_url = 'https://open.weixin.qq.com/connect/oauth2/authorize?';
        // 回调地址
        $backurl = U('Label/MyGift/get_gift', 
            array(
                'order_id' => $order_id, 
                'rece_phone' => $rece_phone), '', '', TRUE);
        // 授权参数
        $opt_arr = array(
            'appid' => $this->appid, 
            'redirect_uri' => $backurl, 
            'response_type' => 'code', 
            // 'scope' => 'snsapi_base',
            'scope' => 'snsapi_userinfo');
        $link = http_build_query($opt_arr);
        $gourl = $open_url . $link . '#wechat_redirect';
        // header('location:'.$gourl);
        redirect($gourl);
    }

    /*
     * 认证返回接口
     */
    public function get_gift() {
        $show_wx = I('show_wx', 0);
        $code = I('code', null);
        $wxUserInfo = session('wxUserInfo');
        if (! $wxUserInfo['nick_name'] || ! $wxUserInfo['headimgurl']) { // 无session
            $auth_flag = 0; // 0未获取过用户信息 1 已获取过
            if ($code) { // 重新获取
                $wxUserInfo = $this->_getwxUserInfo($code);
                $auth_flag = 1;
            }
        } else
            $auth_flag = 1;
        $rece_phone = I('rece_phone', null);
        $order_id = I('order_id', null);
        if (! $order_id)
            $this->showMsg('送礼订单错误', 0, $order_id);
        $orderInfo = M('ttg_order_info')->where(
            array(
                'order_id' => $order_id))->find();
        if (! $orderInfo)
            $this->showMsg('送礼订单获取失败', 0, $order_id);
        
        $count = M('torder_trace')->where(
            array(
                'order_id' => $order_id, 
                'openid' => $wxUserInfo['openid']))->count();
        if ($count > 0) { // 已领取过
            $this->showMsg('您已领取过该礼品，无法再次领取', 2, $order_id);
        }
        
        // 送礼配置数据
        $giftData = M('ttg_order_gift')->where(
            array(
                'order_id' => $order_id))->find();
        // 商品图片和名称
        if ($orderInfo['order_type'] == '2')
            $goodsInfo = M()->table('ttg_order_info_ex e')
                ->join('tbatch_info b on b.id=e.b_id')
                ->where(array(
                'e.order_id' => $order_id))
                ->field('e.b_name,b.batch_img')
                ->find();
        else
            $goodsInfo = M('tbatch_info')->where(
                array(
                    'm_id' => $orderInfo['batch_no']))
                ->field('batch_name as b_name,batch_img')
                ->find();
        
        $wx_share_config = D('WeiXin', 'Service')->getWxShareConfig();
        $this->assign('wx_share_config', $wx_share_config);
        $shareArr = array(
            'config' => $wx_share_config, 
            'link' => U('gift_unpack', 
                array(
                    'order_id' => $order_id), '', '', TRUE), 
            'title' => "{$giftData['bless_name']}送出一份礼物", 
            'desc' => "{$giftData['bless_name']}送出一份礼物", 
            'imgUrl' => C('TMPL_PARSE_STRING.__PUBLIC__') .
                 '/Label/Image/weixin_share.jpg');
        
        $this->assign('shareData', $shareArr);
        $this->assign('giftData', $giftData);
        $this->assign('goodsInfo', $goodsInfo);
        $this->assign('order_id', $order_id);
        $this->assign('rece_phone', $rece_phone);
        $this->assign('auth_flag', $auth_flag);
        $this->assign('show_wx', $show_wx);
        $this->display();
    }

    /*
     * 发送凭证
     */
    public function send_gift() {
        $rece_phone = I('rece_phone', null);
        $order_id = I('order_id', null);
        if (! $rece_phone)
            $this->showMsg('收礼手机号错误', 0, $order_id);
        if (! $order_id)
            $this->showMsg('送礼订单错误', 0, $order_id);
            // 订单数据
        $orderModel = M("ttg_order_info");
        $orderInfo = $orderModel->alias("o")->join(
            "ttg_order_info_ex t ON t.order_id = o.order_id")
            ->join('tbatch_info b ON b.id=t.b_id')
            ->field("o.*, t.ecshop_sku_desc,b.batch_short_name,b.use_rule")
            ->where("o.order_id={$order_id}")
            ->find();
        if (! $orderInfo)
            $this->showMsg('送礼订单获取失败！', 0, $order_id);
            // 已领完
        $have_count = M('torder_trace')->where(
            array(
                'order_id' => $order_id))->count();
        if ($have_count >= $orderInfo['buy_num']) {
            $this->showMsg('来晚咯~礼品已领用完！', 0, $order_id);
        }
        // 生成发码内容
        $textInfo = array();
        $textInfo['use_rule'] .= $orderInfo['batch_short_name'];
        if (isset($orderInfo['ecshop_sku_desc'])) {
            $textInfo['use_rule'] .= '[' . $orderInfo['ecshop_sku_desc'] . ']';
            $textInfo['print_text'] .= $orderInfo['ecshop_sku_desc'];
        }
        $textInfo['use_rule'] .= $orderInfo['use_rule'];
        if ($orderInfo['order_type'] == '2')
            $ecgoodsInfo = M()->table('ttg_order_info_ex e')
                ->field('e.*,b.batch_no,b.m_id')
                ->join('tbatch_info b on b.id=e.b_id')
                ->where(array(
                'e.order_id' => $order_id))
                ->find();
        else {
            $bInfo = M('tbatch_info')->where(
                array(
                    'm_id' => $orderInfo['batch_no']))
                ->field('id,batch_no')
                ->find();
            $ecgoodsInfo = array(
                'batch_no' => $bInfo['batch_no'], 
                'b_id' => $bInfo['id'], 
                'm_id' => $orderInfo['batch_no']);
        }
        // 获取channelId
        $channelId = M('tbatch_channel')->where(
            array(
                'id' => $orderInfo['batch_channel_id']))->getField('channel_id');
        $wxUserInfo = session('wxUserInfo');
        
        $count = M('torder_trace')->where(
            array(
                'order_id' => $order_id, 
                'openid' => $wxUserInfo['openid']))->count();
        if ($count > 0) { // 已领取过
            $this->showMsg('您已领取过该礼品，无法再次领取', 2, $order_id);
        }
        $oTraceData = array(
            'openid' => $wxUserInfo['openid'], 
            'wx_headpic' => $wxUserInfo['headimgurl'], 
            'wx_nickname' => $wxUserInfo['nickname']);
        $ret = D('SalePro', 'Service')->sendCode2($orderInfo['order_id'], '2', 
            $orderInfo['node_id'], $ecgoodsInfo['batch_no'], $rece_phone, 
            $ecgoodsInfo['b_id'], $ecgoodsInfo['m_id'], $channelId, $rece_phone, 
            $textInfo, $oTraceData);
        
        $this->showMsg('领取成功', 1, $order_id);
    }

    /*
     * 短信送礼支付完成跳转页面 微信送礼支付完成跳转到get_gift
     */
    public function send_ok() {
        $order_id = I('order_id', null);
        if (! $order_id)
            $this->error('送礼订单数据错误');
        $orderInfo = M('ttg_order_info')->where(
            array(
                'order_id' => $order_id))
            ->field('order_type,batch_channel_id,batch_no,buy_num')
            ->find();
        if ($orderInfo['order_type'] == '2') {
            $goodsInfo = M()->table('ttg_order_info_ex e')
                ->join('tbatch_info b on b.id=e.b_id')
                ->where(array(
                'e.order_id' => $order_id))
                ->field('e.b_name,b.batch_img')
                ->find();
        } else
            $goodsInfo = M('tbatch_info')->where(
                array(
                    'm_id' => $orderInfo['batch_no']))
                ->field('batch_name as b_name,batch_img')
                ->find();
        
        $this->assign('orderInfo', $orderInfo);
        $this->assign('goodsInfo', $goodsInfo);
        $this->display();
    }
    
    // 获取openid
    protected function getOpenid($code) {
        if (empty($code))
            return false;
        
        $apiUrl = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' .
             $this->appid . '&secret=' . $this->secret . '&code=' . $code .
             '&grant_type=authorization_code';
        $result = $this->httpsGet($apiUrl);
        if (! $result)
            return false;
        
        $result = json_decode($result, true);
        if ($result['errcode'] != '') {
            return false;
        }
        return $result;
    }
    
    // 获取用户信息
    protected function getUser($access_token, $openid) {
        if (empty($access_token) || empty($openid)) {
            $this->error('用户数据获取参数不能为空！');
        }
        $userUrl = 'https://api.weixin.qq.com/sns/userinfo?access_token=' .
             $access_token . '&openid=' . $openid . '&lang=zh_CN';
        $error = '';
        $wxUserInfo = httpPost($userUrl, '', $error, 
            array(
                'METHOD' => 'GET'));
        $wxUserInfo = json_decode($wxUserInfo, true);
        if ($wxUserInfo['errcode'] || empty($wxUserInfo)) {
            return false;
        } else {
            return $wxUserInfo;
        }
    }
    
    // 请求接口
    protected function httpsGet($apiUrl) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $result = curl_exec($ch);
        return $result;
    }

    protected function _getwxUserInfo($code) {
        if (empty($code))
            $this->error('参数错误！');
        $result = $this->getOpenid($code);
        $access_token = $result['access_token'];
        $openid = $result['openid'];
        if ($access_token == '' || $openid == '')
            $this->error('获取授权openid失败！');
            // 获取用户信息
        $wxUserInfo = $this->getUser($access_token, $openid);
        if ($wxUserInfo === false) {
            $this->error('获取用户信息失败！');
        }
        // 记录session
        session("wxUserInfo", $wxUserInfo);
        return $wxUserInfo;
    }

    /*
     * 输出信息页面 $status 0-失败提示 1-成功提示 2-已领取过
     */
    protected function showMsg($info, $status, $order_id) {
        $orderInfo = M('ttg_order_info')->where(
            array(
                'order_id' => $order_id))
            ->field('order_type,batch_no,buy_num')
            ->find();
        // 商品图片和名称
        if ($orderInfo['order_type'] == '2')
            $goodsInfo = M()->table('ttg_order_info_ex e')
                ->join('tbatch_info b on b.id=e.b_id')
                ->where(array(
                'e.order_id' => $order_id))
                ->field('e.goods_num,e.b_name,b.batch_img')
                ->find();
        else {
            $goodsInfo = M('tbatch_info')->where(
                array(
                    'm_id' => $orderInfo['batch_no']))
                ->field('batch_name as b_name,batch_img')
                ->find();
            $goodsInfo['goods_num'] = $orderInfo['buy_num'];
        }
        $giftInfo = M('ttg_order_gift')->where(
            array(
                'order_id' => $order_id))->find();
        // 获取领取数据
        $hav_list = M('torder_trace')->where(
            array(
                'order_id' => $order_id))->select();
        $hav_count = count($hav_list);
        
        // 小店跳转地址
        $labelId = M('ttg_order_info')->where(
            array(
                'order_id' => $order_id))->getField('batch_channel_id');
        
        $this->assign('labelId', $labelId);
        $this->assign('giftInfo', $giftInfo);
        $this->assign('hav_list', $hav_list);
        $this->assign('hav_count', $hav_count);
        $this->assign('goodsInfo', $goodsInfo);
        $this->assign('info', $info);
        $this->assign('status', $status);
        $this->display('msg');
        exit();
    }
    
}