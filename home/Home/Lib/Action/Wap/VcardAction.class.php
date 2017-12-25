<?php

/*
 * 二维码名片 write by wangsong mail skyshappiness@gmail.com
 */
class VcardAction extends Action {

    public $is_weixin = '0';

    public $mobileType;

    public $appid;

    public $secret;

    protected $wechatUserInfo;

    protected $path;

    public function __construct() {
        parent::__construct();
        /* 测试账号 */
        $this->appid = C('WEIXIN.appid');
        $this->secret = C('WEIXIN.secret');
        $this->checkmobiletype();
        $this->path = C('UPLOAD') . 'Vcard/';
        if (ACTION_NAME !== 'callback' && ACTION_NAME != '_getopenidIndex' &&
             ACTION_NAME != 'getOpenid' && ACTION_NAME != 'getUser' &&
             ACTION_NAME != 'httpsGet' && ACTION_NAME != 'checkmobiletype' &&
             ACTION_NAME != 'viewFriend') {
            if (($this->is_weixin == '1' && $_SESSION['valid'] < time()) ||
             ($this->is_weixin == '1' && $_SESSION['openid'] == '')) {
            $this->_getopenidIndex();
        }
    }
    $_SESSION['valid'] = time() + 1800;
}

// 授权页面
public function _getopenidIndex($type = 'snsapi_userinfo') {
    // 授权地址
    $open_url = 'https://open.weixin.qq.com/connect/oauth2/authorize?';
    // 回调地址
    $backurl = U('Wap/Vcard/callback', '', '', '', TRUE);
    // 授权参数
    $opt_arr = array(
        'appid' => $this->appid, 
        'redirect_uri' => $backurl, 
        'response_type' => 'code', 
        'scope' => $type == '' ? 'snsapi_base' : 'snsapi_userinfo');
    $link = http_build_query($opt_arr);
    $gourl = $open_url . $link . '#wechat_redirect';
    redirect($gourl);
}

// 回调
public function callback() {
    $code = I('code', null);
    if (empty($code)) {
        $this->error('参数错误！');
    }
    
    $result = $this->getOpenid($code);
    if (array_key_exists('errcode', $result)) {
        $this->error('获取授权openid失败！');
    } else {
        $access_token = $result['access_token'];
        $_SESSION['openid'] = $result['openid'];
        $_SESSION['valid'] = time() + 1800;
    }
    
    // 获取用户信息
    $wxUserInfo = $this->getUser($access_token, $_SESSION['openid']);
    if ($wxUserInfo) {
        $twxWapUserModel = M('TwxWapUser');
        $ifUserInfoExist = $twxWapUserModel->where(
            array(
                'openid' => $wxUserInfo['openid']))->find();
        $wxUserInfo['access_token'] = $access_token;
        if ($ifUserInfoExist) {
            $twxWapUserModel->where(
                array(
                    'openid' => $wxUserInfo['openid']))->save($wxUserInfo);
        } else {
            $twxWapUserModel->add($wxUserInfo);
        }
    }
    redirect(
        U('Wap/Vcard/index', 
            array(
                'openid' => $_SESSION['openid'])));
}

// 获取openid
protected function getOpenid($code) {
    if (empty($code)) {
        return false;
    }
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
        $this->error('参数不能为空！');
    }
    $userUrl = 'https://api.weixin.qq.com/sns/userinfo?access_token=' .
         $access_token . '&openid=' . $openid . '&lang=zh_CN';
    $wxUserInfo = $this->httpsGet($userUrl);
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


function checkmobiletype() {
    $str = $_SERVER['HTTP_USER_AGENT'];
    // 判断微信浏览器
    if (strpos($str, 'MicroMessenger')) {
        $this->is_weixin = '1';
    }
    
    // 判断手机操作系统及其他
    if (stristr($str, 'Android ')) {
        if (stristr($str, 'Windows Phone')) {
            $this->mobileType = 'WindowsPhone';
        } else {
            $this->mobileType = 'Android';
        }
    } else if (stristr($str, 'iPhone ')) {
        $this->mobileType = 'iphone';
    } else {
        $this->mobileType = 'unknow';
    }
}

public function index() {
    if ($this->is_weixin == '1' && $_SESSION['openid'] != '') {
        $twxUserInfo = $this->_UseOpenidGetTwxUserInfo($_SESSION['openid']);
        $tvisitingCardModel = M('TvisitingCard');
        $isExist = $tvisitingCardModel->where(
            array(
                'twx_user_id' => $twxUserInfo['id']))->getfield('phone_no');
        if ($isExist) {
            redirect(
                U('Wap/Vcard/viewUser', 
                    array(
                        'mobile' => $isExist, 
                        'openid' => $_SESSION['openid'])));
        } else {
            redirect(
                U('add', 
                    array(
                        'openid' => $_SESSION['openid'])));
        }
    }
    $this->display();
}

public function add() {
    if ($_SESSION['openid']) {
        $this->assign('is_wechat', 'y');
        $this->assign('openid', $_SESSION['openid']);
        $wechatUserInfo = $this->_UseOpenidGetTwxUserInfo($_SESSION['openid']);
        $this->assign('wechat_user_info', $wechatUserInfo);
    } else {
        $this->assign('phone', $mobile);
    }
    D('WeiXin', 'Service')->getWxShareConfig();
    $this->display();
}

public function modify() {
    $this->display();
}

public function infoDetails() {
    $searchCondition = array();
    
    $mobile = I('mobile');
    $isMobile = check_str($mobile, array(
        'strtype' => 'mobile'));
    if (! $isMobile) {
        $this->error('请填写正确的手机号码');
    } else {
        $searchCondition['phone_no'] = $mobile;
    }
    
    $ID = I('get.id');
    if ($ID == '' || ($this->is_weixin != '1' && $_SESSION['vcard_phone'] == '')) {
        $varifyCode = I('post.varify_code', '0', 'string');
        $reg_sms_code = session('reg_sms_code');
        if (! $reg_sms_code || $reg_sms_code != $varifyCode) {
            redirect(U('Wap/Vcard/modify'));
            exit();
        } else {
            $_SESSION['vcard_phone'] = $mobile;
        }
    } else {
        $searchCondition['id'] = $ID;
    }
    
    if ($this->is_weixin != '1' && $_SESSION['vcard_phone'] != '') {
        if ($mobile != $_SESSION['vcard_phone']) {
            $this->error('手机号码有误！');
        }
    }
    
    if ($_SESSION['openid'] != '' && $this->is_weixin == '1') {
        $twxUserInfo = $this->_UseOpenidGetTwxUserInfo($_SESSION['openid']);
        if ($twxUserInfo) {
            $searchCondition['twx_user_id'] = $twxUserInfo['id'];
            $this->assign('wechat_user_info', $twxUserInfo);
            $this->assign('is_wechat', 'y');
        }
    }
    
    $TvisitingCardModel = M('TvisitingCard');
    $vcardInfo = $TvisitingCardModel->where($searchCondition)
        ->field('data, id')
        ->find();
    
    if (! $vcardInfo) {
        redirect(U('Wap/Vcard/add'));
        exit();
    }
    $vcardInfoArray = json_decode($vcardInfo['data'], TRUE);
    $this->assign('data', $vcardInfoArray);
    $this->assign('mark', $vcardInfo['id']);
    D('WeiXin', 'Service')->getWxShareConfig();
    $this->display();
}

function viewUser() {
    $mobile = I('get.mobile');
    $isMobile = check_str($mobile, array(
        'strtype' => 'mobile'));
    if ($isMobile == FALSE) {
        $this->error('手机号码不正确');
        exit();
    }
    $vcardInfo = M('tvisiting_card')->where(
        array(
            'phone_no' => $mobile))
        ->field('id , is_win_prize, image_path')
        ->find();
    D('WeiXin', 'Service')->getWxShareConfig();
    $this->_wechatShareInfo($vcardInfo['id']);
    $randNumber = rand(1, 200);
    $imgePathEnd = '_user.png?v=' . $randNumber;
    if ($this->mobileType == 'iphone') {
        $qrcodeUrl = file_exists(
            get_upload_url($mobile . 'ios', $vcardInfo['image_path'], 
                $imgePathEnd)) ? get_upload_url($mobile . 'ios', 
            $vcardInfo['image_path'], $imgePathEnd) : get_upload_url($mobile, 
            $vcardInfo['image_path'], $imgePathEnd);
    } else {
        $qrcodeUrl = get_upload_url($mobile, $vcardInfo['image_path'], 
            $imgePathEnd);
    }
    $url = $_SERVER['HTTP_REFERER'];
    if (strpos($url, 'add') || strpos($url, 'infoDetails')) {
        $is_add = true;
    }
    
    $this->assign('is_wechat', $this->is_weixin);
    $this->assign('id', $vcardInfo['id']);
    $this->assign('mobile', $mobile);
    $this->assign('is_add', $is_add);
    $this->assign('is_win_prize', $vcardInfo['is_win_prize']);
    $this->assign('vcard_activity_id', C('VCARD_ACTIVITY_NUMBER'));
    $this->assign('viewQrUrl', $qrcodeUrl);
    $editUrl = U('Wap/Vcard/infoDetails', 
        array(
            'id' => $vcardInfo['id'], 
            'mobile' => $mobile, 
            'openid' => $_SESSION['openid']));
    $this->assign('editUrl', $editUrl);
    $this->display();
}

function viewFriend() {
    $ID = I('id');
    D('WeiXin', 'Service')->getWxShareConfig();
    $this->_wechatShareInfo($ID);
    $TvisitingCardModel = M('TvisitingCard');
    $randNumber = rand(1, 100);
    $userInfo = $TvisitingCardModel->where(array(
        'id' => $ID))
        ->field('phone_no, image_path')
        ->find();
    $imgePathEnd = '_friend.png?v=' . $randNumber;
    if ($this->mobileType == 'iphone') {
        $fileExist = file_exists(
            get_upload_url($mobile . 'ios', $vcardInfo['image_path'], 
                $imgePathEnd));
        if ($fileExist) {
            $url = get_upload_url($userInfo['phone_no'] . 'ios', 
                $userInfo['image_path'], $imgePathEnd);
        } else {
            $url = get_upload_url($userInfo['phone_no'], 
                $userInfo['image_path'], $imgePathEnd);
        }
    } else {
        $url = get_upload_url($userInfo['phone_no'], $userInfo['image_path'], 
            $imgePathEnd);
    }
    
    $wxUserModel = M("TwxWapUser a");
    
    $ifViewerHasVcard = $wxUserModel->alias("a")->join(
        'RIGHT JOIN tvisiting_card b ON b.twx_user_id = a.id')
        ->where(array(
        'a.openid' => $_SESSION['openid']))
        ->field('b.id')
        ->find();
    if ($ifViewerHasVcard) {
        $this->assign('hasVcard', "Y");
    } else {
        $this->assign('hasVcard', "N");
    }
    $this->assign('mobile_type', $this->mobileType);
    $this->assign('imgurl', $url);
    $this->display();
}

function help() {
    $this->display();
}

function _createvcard($ID) {
    $TvisitingCardModel = M('TvisitingCard');
    $vcardInfo = $TvisitingCardModel->where(array(
        'id' => $ID))
        ->field('card_data, address , phone_no, data, image_path')
        ->find();
    $nameArray = json_decode($vcardInfo['data'], TRUE);
    if ($vcardInfo) {
        if ($vcardInfo['image_path'] == '' || $vcardInfo['image_path'] == NULL) {
            $year = date('Y');
            $month = date('m');
            $day = date('d');
            if (! is_dir($this->path . $year)) {
                mkdir($this->path . $year, 0777, true);
            }
            if (! is_dir($this->path . $year . '/' . $month)) {
                mkdir($this->path . $year . '/' . $month, 0777, true);
            }
            if (! is_dir($this->path . $year . '/' . $month . '/' . $day)) {
                mkdir($this->path . $year . '/' . $month . '/' . $day, 0777, 
                    true);
            }
            $imagePath = 'Vcard/' . $year . '/' . $month . '/' . $day;
            $TvisitingCardModel->where(array(
                'id' => $ID))->save(
                array(
                    'image_path' => $imagePath));
            $vcardInfo['image_path'] = $imagePath;
        }
        $vcardQrcodePath = C('UPLOAD') . $vcardInfo['image_path'] . '/' .
             $vcardInfo['phone_no'];
        $vcardQrcodePath2 = C('UPLOAD') . $vcardInfo['image_path'] . '/' .
             $vcardInfo['phone_no'] . 'ios';
        $vcardQrcodePathPng = $vcardQrcodePath . '.png';
        $vcardQrcodePathPng2 = $vcardQrcodePath2 . '.png';
        log_write($vcardQrcodePath);
        log_write($vcardQrcodePath2);
        if (file_exists($vcardQrcodePathPng)) {
            unlink($vcardQrcodePathPng);
        }
        if (file_exists($vcardQrcodePathPng2)) {
            unlink($vcardQrcodePathPng2);
        }
        import('@.Vendor.MakeCode') or die('include file fail.');
        $makecode = new MakeCode();
        
        $address = explode(';;;', $vcardInfo['address']);
        
        $result[] = $makecode->MakeCodeImg(
            $vcardInfo['card_data'] . $address[0], TRUE, $size = '3', '', 
            $vcardQrcodePath);
        $result[] = $makecode->MakeCodeImg(
            $vcardInfo['card_data'] . $address[1], TRUE, $size = '3', '', 
            $vcardQrcodePath2);
    }
}

// 发送手机验证码
public function sendSmsCode() {
    $mobile = I('phone');
    $isMobile = check_str($mobile, array(
        'strtype' => 'mobile'));
    if ($isMobile == FALSE) {
        $this->ajaxReturn(
            array(
                'code' => - 1, 
                'msg' => "请填写正确的手机号码"));
        exit();
    }
    
    $code = mt_rand(100000, 999999);
    $content = $code . '（旺财平台验证码，十分钟内有效），请在页面中输入以完成验证，如非本人操作，请忽略本短信';
    log_write("二维码名片发送验证码：");
    log_write(
        implode("\n", 
            array(
                '手机号' => $mobile, 
                '验证码' => $code)));
    session('reg_sms_code', $code); // 记录session
    $result = D('RemoteRequest', 'Service')->smsSend($mobile, $content);
    if (! $result) {
        $this->ajaxReturn(
            array(
                'code' => - 1, 
                'msg' => "发送失败"));
        log_write("发送失败" . print_r($result));
    }
    $this->ajaxReturn(
        array(
            'code' => 0, 
            'msg' => "发送成功", 
            'varify' => $code));
}

function addsubmit() {
    $postData = I('post.');
    $result = $this->_makeVcardStr($postData);
    if ($result['error'] > 0) {
        $this->ajaxReturn($result);
        exit();
    } else {
        $str = $result['str'];
    }
    
    $data = array();
    $data['phone_no'] = $postData['only_phone'];
    $data['add_time'] = date('YmdHis');
    $data['card_data'] = $str;
    $data['address'] = $result['address'];
    $year = date('Y');
    $month = date('m');
    $day = date('d');
    if (! is_dir($this->path . $year)) {
        mkdir($this->path . $year, 0777, true);
    }
    if (! is_dir($this->path . $year . '/' . $month)) {
        mkdir($this->path . $year . '/' . $month, 0777, true);
    }
    if (! is_dir($this->path . $year . '/' . $month . '/' . $day)) {
        mkdir($this->path . $year . '/' . $month . '/' . $day, 0777, true);
    }
    $data['image_path'] = 'Vcard/' . $year . '/' . $month . '/' . $day;
    $data['data'] = json_encode($_POST);
    if ($_SESSION['openid'] != '') {
        $twxUserInfo = $this->_UseOpenidGetTwxUserInfo($_SESSION['openid']);
        if ($twxUserInfo) {
            $data['twx_user_id'] = $twxUserInfo['id'];
        }
    }
    
    $tvisitingCardModel = M('TvisitingCard');
    $isExist = $tvisitingCardModel->where(
        array(
            'phone_no' => $postData['only_phone']))->getfield('id');
    if ($isExist) {
        if ($this->is_weixin == 1) {
            $tvisitingCardModel->where(
                array(
                    'phone_no' => $postData['only_phone'], 
                    'twx_user_id' => $twxUserInfo['id']))->save($data);
            $this->_createvcard($isExist);
        } else {
            $result['error'] = 2002;
            $result['msg'] = '已存在此号码的二维码名片，请使用修改二维码名片功能！';
            $this->ajaxReturn($result);
            exit();
        }
    } else {
        $successID = $tvisitingCardModel->add($data);
        if ($successID) {
            if ($this->is_weixin != '1') {
                $_SESSION['vcard_phone'] = $postData['only_phone'];
            }
            $this->_createvcard($successID);
        } else {
            $this->error('创建失败，请重新操作');
        }
    }
    $result['error'] = 0;
    $this->ajaxReturn($result);
}

function editsubmit() {
    $postData = I('post.');
    $result = $this->_makeVcardStr($postData);
    if ($result['error'] > 0) {
        $this->ajaxReturn($result);
    }
    
    $data = array();
    $data['phone_no'] = $postData['only_phone'];
    $data['card_data'] = $result['str'];
    $data['address'] = $result['address'];
    $data['data'] = json_encode($_POST);
    $tvisitingCardModel = M('TvisitingCard');
    if ($this->is_weixin == '1') {
        $twxUserInfo = $this->_UseOpenidGetTwxUserInfo($_SESSION['openid']);
        $tvisitingCardModel->where(
            array(
                'id' => $postData['id'], 
                'twx_user_id' => $twxUserInfo['id']))->save($data);
    } else {
        if ($postData['only_phone'] != $_SESSION['vcard_phone']) {
            $result['error'] = 1001;
            $result['msg'] = '手机号码有错误，请重新提交';
            $this->ajaxReturn($result);
        }
        
        $tvisitingCardModel->where(
            array(
                'phone_no' => $postData['only_phone']))->save($data);
    }
    
    $this->_createvcard($postData['id']);
}

function _makeVcardStr($data) {
    $returnResult = array();
    if ($data == '') {
        $returnResult['error'] = 10001;
        $returnResult['msg'] = 'data empty';
        return $returnResult;
        exit();
    }
    
    $name = $data['name'];
    if ($data['name'] == '') {
        $returnResult['error'] = 30001;
        $returnResult['msg'] = '请输入中文名字';
        return $returnResult;
        exit();
    } elseif (! preg_match('/^[\x80-\xff]{3,12}$/', $name)) {
        $returnResult['error'] = 30001;
        $returnResult['msg'] = '请输入中文名字';
        return $returnResult;
        exit();
    } else {
        $str = "BEGIN:VCARD\r\nFN:" . $data['name'];
        $str .= "\r\nTEL;CELL:" . $onlyMobile;
    }
    
    $onlyMobile = $data['only_phone'];
    if (! check_str($onlyMobile, array(
        'strtype' => 'mobile'))) {
        $returnResult['error'] = 20001;
        $returnResult['msg'] = '请输入正确的手机号码';
        return $returnResult;
        exit();
    } else {
        $result = $this->_addInfoToVcardStr($onlyMobile, 'TEL;cell:');
        $str .= $result;
    }
    
    $homeTel = $data['homeTel'];
    if ($homeTel != '') {
        $result = $this->_addInfoToVcardStr($homeTel, 'TEL;HOME:');
        $str .= $result;
    }
    
    $mobile = $data['mobile'];
    if ($mobile != '') {
        $result = $this->_addInfoToVcardStr($mobile, 'TEL;WORK:');
        $str .= $result;
    }
    
    $onlyTel = $data['phone'];
    if ($onlyTel != '') {
        $result = $this->_addInfoToVcardStr($onlyTel, 'TEL;pref:');
        $str .= $result;
    }
    
    $fax = $data['fax'];
    if ($fax != '') {
        $result = $this->_addInfoToVcardStr($fax, 'TEL;FAX:');
        $str .= $result;
    }
    
    $personEmail = $data['email'];
    if ($personEmail != '') {
        if (! check_str($personEmail, 
            array(
                'strtype' => 'email'))) {
            $returnResult['error'] = 40001;
            $returnResult['msg'] = '请填写正确的邮箱';
            return $returnResult;
            exit();
        }
        $result = $this->_addInfoToVcardStr($personEmail, 'EMAIL;INTERNET:');
        $str .= $result;
    } else {
        $returnResult['error'] = 40002;
        $returnResult['msg'] = '请务必填写个人邮箱';
        return $returnResult;
        exit();
    }
    
    $workEmail = $data['companyEmail'];
    if ($workEmail != '') {
        if (! check_str($workEmail, 
            array(
                'strtype' => 'email'))) {
            $returnResult['error'] = 40001;
            $returnResult['msg'] = '请填写正确的工作邮箱';
            return $returnResult;
            exit();
        }
        $result = $this->_addInfoToVcardStr($workEmail, 'EMAIL;INTERNET:');
        $str .= $result;
    }
    
    $otherEmail = $data['other_email'];
    if ($otherEmail != '') {
        foreach ($otherEmail as $val) {
            if (! check_str($val, 
                array(
                    'strtype' => 'email'))) {
                $returnResult['error'] = 40001;
                $returnResult['msg'] = '请填写正确的其他邮箱';
                return $returnResult;
                exit();
            }
        }
        $result = $this->_addInfoToVcardStr($otherEmail, 'EMAIL;INTERNET:');
        $str .= $result;
    }
    
    $companyName = $data['company_name'];
    preg_match_all('/[\x80-\xff]{3}/', $companyName, $match);
    if (count($match[0]) < 8) {
        $returnResult['error'] = 50001;
        $returnResult['msg'] = '请填写公司全称';
        $this->ajaxReturn($returnResult);
        exit();
    } else {
        $result = $this->_addInfoToVcardStr($companyName, 'ORG:');
        $str .= $result;
    }
    
    $position = $data['position'];
    if ($position != '') {
        $result = $this->_addInfoToVcardStr($position, 'ROLE:');
        $str .= $result;
    }
    
    $homePage = $data['home_page'];
    if ($homePage != '') {
        $result = $this->_addInfoToVcardStr($homePage, 'URL:');
        $str .= $result;
    }
    
    $remark = $data['remark'];
    if ($remark != '') {
        if (! check_str($remark, array(
            'maxlen_cn' => 50))) {
            $returnResult['error'] = 70001;
            $returnResult['msg'] = '备注请小于50个字';
            return $returnResult;
            exit();
        }
        $result = $this->_addInfoToVcardStr($remark, 'NOTE:');
        $str .= $result;
    }
    
    $province = $data['province'];
    if ($province == '') {
        $returnResult['error'] = 60001;
        $returnResult['msg'] = '请选择省份';
        $this->ajaxReturn($returnResult);
        exit();
    }
    
    $city = $data['city'];
    if ($city == '') {
        $returnResult['error'] = 60001;
        $returnResult['msg'] = '请选择城市';
        $this->ajaxReturn($returnResult);
        exit();
    }
    
    $addr = $data['addr'];
    $cityCodeModel = M('TcityCode');
    $province = $cityCodeModel->where(
        array(
            'province_code' => $province))->getField('province');
    $city = $cityCodeModel->where(array(
        'city_code' => $city))->getField('city');
    // iphone地址
    $address2 = $this->_addInfoToVcardStr(
        ';;' . $addr . ';' . $city . ';' . $province, 'ADR;WORK:') .
         "\r\nEND:VCARD";
    // 其他地址
    $address = $this->_addInfoToVcardStr($province . $city . $addr, 'ADR;WORK:') .
         "\r\nEND:VCARD";
    $returnResult['address'] = $address . ';;;' . $address2;
    
    if ((strlen($str) > 320) || (strlen($str2) > 320)) {
        $returnResult['error'] = 80001;
        $returnResult['msg'] = '您填写的字数过多，请删减字数，以免影响二维码的识别';
        return $returnResult;
        exit();
    } else {
        $returnResult['error'] = 0;
        $returnResult['str'] = $str;
        return $returnResult;
    }
}

function _addInfoToVcardStr($data, $type) {
    $str = '';
    if (is_array($data)) {
        foreach ($data as $key => $val) {
            $str .= "\r\n" . $type . $val;
        }
    } else {
        $str .= "\r\n" . $type . $data;
    }
    return $str;
}

function _UseOpenidGetTwxUserInfo($openid) {
    $twxUserModel = M('TwxWapUser');
    $wxUserInfo = $twxUserModel->field('id')
        ->where(array(
        'openid' => $openid))
        ->field('id, headimgurl, sex')
        ->find();
    if ($wxUserInfo) {
        return $wxUserInfo;
    } else {
        return FALSE;
    }
}

function _wechatShareInfo($ID) {
    $vcardInfo = M('tvisiting_card')->where(array(
        'id' => $ID))->getfield('data');
    $vcardUserInfo = json_decode($vcardInfo, TRUE);
    $this->assign('name', $vcardUserInfo['name']);
    
    $shareDesc = '姓名：' . $vcardUserInfo['name'];
    if ($vcardUserInfo['company_name'] != '') {
        $shareDesc .= '\n单位：' . $vcardUserInfo['company_name'];
    }
    $shareDesc .= '\n查看更多';
    $shareImgUrl = $vcardUserInfo['head_img'];
    $shareUrl = 'http://' . $_SERVER['HTTP_HOST'] . U('Wap/Vcard/viewFriend', 
        array(
            'id' => $ID));
    
    $this->assign('shareUrl', $shareUrl);
    $this->assign('shareDesc', $shareDesc);
    $this->assign('shareImgUrl', $shareImgUrl);
}

public function getPic() {
    $result = array();
    $phone = I('phone');
    if (! check_str($phone, array(
        'strtype' => 'mobile'))) {
        $result['error'] = 10001;
        $result['msg'] = '请输入正确的手机号码';
        return $result;
        exit();
    }
    
    $type = I('type');
    $visitingCardModel = M('TvisitingCard');
    $userInfo = $visitingCardModel->where(
        array(
            'phone_no' => $phone))
        ->field('data, image_path')
        ->find();
    $userInfoArray = json_decode($userInfo['data'], TRUE);
    
    $style = I('style');
    if ($style == 'add') {
        if ($this->mobileType == 'iphone') {
            $phone = $phone . 'ios';
        }
    }
    if ($style == 'user') {
        if ($this->mobileType != 'iphone') {
            $phone = $phone . 'ios';
        }
    }
    if ($style == 'friendios') {
        $phone = $phone . 'ios';
    }
    
    $qrcodeUrl = C('UPLOAD') . $userInfo['image_path'] . '/' . $phone . '.png';
    
    import("@.ORG.Util.qrimg_v2.phpqrcode_modify");
    header('Content-Type: text/html; charset=utf-8');
    
    $topText = $userInfoArray['name'] . '的云名片';
    $topLen = mb_strlen($topText, 'utf8');
    
    $waterImg = new Imagick($qrcodeUrl);
    $waterImgWidth = $waterImg->getImageWidth();
    $waterImageHeight = $waterImg->getImageHeight();
    
    $imagick = new Imagick("./Home/Public/Label/Image/wap_ebc/vcard_bg.jpg");
    $width = $imagick->getImageWidth();
    $height = $imagick->getImageHeight();
    
    $topStyle['font_size'] = 30;
    $topStyle['fill_color'] = '#FFFFFF';
    $topStyle['font'] = './Home/Public/Image/res/msyh.ttf';
    $topX = ($width - $topLen * 30) / 2;
    $topY = $topStyle['font_size'] + 45;
    $this->_addText($imagick, $topText, $topX, $topY, 0, $topStyle);
    $saveUrl = C('UPLOAD') . $userInfo['image_path'] . '/' . $phone;
    if ($type == 'user') {
        $bottomText = "长按指纹，保存云名片至手机相册。";
        $saveUrl = $saveUrl . '_user.png';
    } else {
        $bottomText = "长按指纹，识别图中二维码\r\n保存联系方式至手机通讯录。";
        $saveUrl = $saveUrl . '_friend.png';
    }
    $bottomLen = mb_strlen($bottomText, 'utf8');
    $bottomStyle['font_size'] = 24;
    $bottomStyle['fill_color'] = '#C1C1C1';
    $bottomStyle['font'] = './Home/Public/Image/res/msyh.ttf';
    if ($type == 'user') {
        $bottomX = ($width - 22 * $bottomLen) / 2;
        $bottomY = $height - $bottomStyle['font_size'] - 30;
    } else {
        $bottomX = ($width - 11 * $bottomLen) / 2;
        $bottomY = $height - $bottomStyle['font_size'] - 45;
    }
    $this->_addText($imagick, $bottomText, $bottomX, $bottomY, 0, $bottomStyle);
    $waterImg->thumbnailImage(290, 290);
    $imagick->compositeImage($waterImg, $waterImg->getImageCompose(), 15, 138);
    header('Content-type: ' . strtolower($imagick->getImageFormat()));
    $waterImg->clear();
    $imagick->writeImage($saveUrl);
    $imagick->clear();
}

public function _addText(& $imagick, $text, $x = 0, $y = 0, $angle = 0, 
    $style = array()) {
    $draw = new ImagickDraw();
    if (isset($style['font_size']))
        $draw->setFontSize($style['font_size']);
    if (isset($style['fill_color']))
        $draw->setFillColor($style['fill_color']);
    if (isset($style['under_color']))
        $draw->setTextUnderColor($style['under_color']);
    if (isset($style['font_family']))
        $draw->setfontfamily($style['font_family']);
    if (isset($style['font']))
        $draw->setfont($style['font']);
    $draw->settextencoding('UTF-8');
    if (strtolower($imagick->getImageFormat()) == 'gif') {
        foreach ($imagick as $frame) {
            $frame->annotateImage($draw, $x, $y, $angle, $text);
        }
    } else {
        $imagick->annotateImage($draw, $x, $y, $angle, $text);
    }
}
}
