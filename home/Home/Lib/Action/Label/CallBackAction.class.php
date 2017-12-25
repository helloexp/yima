<?php

class CallBackAction extends Action {

    public $userInfo = array();

    public function _initialize() {
        $userInfo = session('cjUserInfo');
        if (! $userInfo)
            $this->error('未登录！');
        
        $this->userInfo = $userInfo;
    }

    public function callback() {
        $userInfo = $this->userInfo;
        $type_arr = array(
            'sina' => '0', 
            'tencent' => '2', 
            'renren' => '3');
        $type = I('type');
        $phone = I('phone');
        $conf = $this->snsconfig($type);
        $code = I('code');
        
        if ($type == 'sina') {
            $error = I('error', '');
            if ($error != '') {
                $this->success(
                    "分享失败! <a href='" . U('Label/Label/index', 
                        array(
                            'id' => I('id'))) . "'>返回</a>");
                exit();
            }
        }
        
        $marketingInfo = M('tmarketing_info')->find($userInfo['batch_id']);
        if (! $marketingInfo) {
            $this->error("分享失败,错误代码[01]");
        }
        
        $map = array(
            'marketing_info_id' => $userInfo['batch_id']);
        // 小店特殊处理
        if ($marketingInfo['batch_type'] == '29') {
            $map['return_commission_start_time'] = array(
                'elt', 
                date('YmdHis'));
            $map['return_commission_end_time'] = array(
                'egt', 
                date('YmdHis'));
        }
        $returnInfo = M('treturn_commission_info')->where($map)->find();
        if (! $returnInfo) {
            $this->error("分享失败,错误代码[02]");
        }
        
        $sns_id = $returnInfo['label_id'];
        $sns_note = $returnInfo['share_note'];
        
        $sns_note_r = $sns_note;
        $sns_note_arr = explode('--', $sns_note_r);
        $sns_k = array_rand($sns_note_arr);
        $sns_content = $sns_note_arr[$sns_k];
        
        $bg_arr = array(
            '1' => 'topbg-sOne.png', 
            '2' => 'topbg-sTwo.png', 
            '4' => 'topbg-sThree.png', 
            '5' => 'topbg-sFour.png');
        if ($marketingInfo['batch_type'] == '26' ||
             $marketingInfo['batch_type'] == '27') {
            $imgUrl = M()->table("tbatch_info b")->join(
                'tgoods_info g ON g.goods_id=b.goods_id')
                ->where(
                array(
                    'b.m_id' => $userInfo['batch_id']))
                ->getField('g.goods_image');
            $img_url = C('CURRENT_DOMAIN') . "Home/Upload/" . $imgUrl;
        } else if ($marketingInfo['batch_type'] == '29') {
            if (isset($userInfo['gl_id']) && $userInfo['gl_id'] > 0) {
                $gl_id = $userInfo['gl_id'];
                $sns_id = $gl_id;
                $map = array(
                    'label_id' => $gl_id);
                $img_url = M('tecshop_goods_ex')->where($map)->getField(
                    'show_picture1');
                $img_url = C('CURRENT_DOMAIN') . "Home/Upload/" . $img_url;
            } else {
                $logoWhere = array(
                    'ban_type' => 1, 
                    'm_id' => $userInfo['batch_id']);
                $logoInfo = M('tecshop_banner')->where($logoWhere)->find();
                $img_url = C('CURRENT_DOMAIN') . "Home/Upload/" .
                     $logoInfo['img_url'];
            }
        } else {
            if ($marketingInfo['bg_style'] == '3') {
                $img_url = C('CURRENT_DOMAIN') . "Home/Upload/" .
                     $marketingInfo['bg_pic'];
            } else {
                $img_url = C('CURRENT_DOMAIN') . "Home/Public/Label/Image/" .
                     $bg_arr[$marketingInfo['bg_style']];
            }
        }
        
        // 分享二维码图片地址
        $awh = array(
            'id' => $sns_id, 
            'from_user_id' => $userInfo['user_id'], 
            'from_type' => $type_arr[$type]);
        
        // 加载ThinkOauth类并实例化一个对象
        import("ORG.ThinkSDK.ThinkOauth");
        $sns = ThinkOauth::getInstance($type, $conf);
        
        // 腾讯微博还需其他参数
        $extend = null;
        if ($type == 'tencent') {
            $extend = array(
                'openid' => I('openid'), 
                'openkey' => I('openkey'));
        }
        
        // 获取token
        $token = $sns->getAccessToken($code, $extend, $conf);
        
        // 发布内容
        $sns = ThinkOauth::getInstance($type, $token, $conf);
        if ($marketingInfo['batch_type'] == '26' ||
             $marketingInfo['batch_type'] == '27') {
            $shareTitle = $marketingInfo['group_goods_name'] .
             U('Label/Label/index', $awh, '', '', true);
    } else {
        $shareTitle = $marketingInfo['name'] .
             U('Label/Label/index', $awh, '', '', true);
    }
    $shareTitle = $sns_content . U('Label/Label/index', $awh, '', '', true);
    if ($type == 'sina') {
        $data = array(
            'status' => $shareTitle, 
            'url' => $img_url);
        $data = $sns->call('statuses/upload_url_text', $data, 'POST');
        if ($data['error_code'] == 0) {
            $this->snsLog($type_arr[$type]);
        } else {
            $this->error("分享失败:{$data['error']}");
        }
    } elseif ($type == 'tencent') {
        $data = array(
            'content' => $shareTitle, 
            'pic_url' => $img_url);
        $data = $sns->call('t/add_pic_url', $data, 'POST');
        if ($data['ret'] == 0) {
            $this->snsLog($type_arr[$type]);
        } else {
            $this->error("分享失败:{$data['msg']}");
        }
    }
}

// 开始授权
public function authorize() {
    $type_arr = array(
        '0' => 'sina', 
        '2' => 'tencent', 
        '3' => 'renren');
    $type = I('type');
    if (in_array($type, $type_arr))
        $this->error('错误的授权参数！' . $type);
    
    $conf = $this->snsconfig($type_arr[$type]);
    import("ORG.ThinkSDK.ThinkOauth");
    $sns = ThinkOauth::getInstance($type_arr[$type], $conf);
    
    // 跳转到授权页面
    redirect($sns->getRequestCodeURL($conf));
}

// 配置参数
public function snsconfig($type) {
    $userInfo = $this->userInfo;
    $backurl = C('CURRENT_DOMAIN') .
         'index.php?g=Label&m=CallBack&a=callback&id=' . $userInfo['label_id'] .
         '&phone=' . $userInfo['phone_no'];
    
    $config = C("THINK_SDK_{$type}");
    $conf = array(
        'app_key' => $config['APP_KEY'],  // 应用注册成功后分配的 APP ID
        'app_secret' => $config['APP_SECRET'],  // 应用注册成功后分配的KEY
        'callback' => $backurl . '&type=' . $type);
    if ($type == 'renren') {
        $conf['AUTHORIZE'] = 'scope=publish_share';
    }
    return $conf;
}

// 记录分享日志
public function snsLog($type) {
    $userarr = $this->userInfo;
    $this->assign('jb_label_id', $userarr['label_id']);
    $this->success(
        "分享成功! <a href='" . U('Label/Label/index', 
            array(
                'id' => $userarr['label_id'])) . "'>返回</a>");
}

// 微信分享
public function wxsn() {
    $this->snsLog('4');
}
}