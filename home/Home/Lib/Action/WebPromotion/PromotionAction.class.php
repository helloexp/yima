<?php

class PromotionAction extends BaseAction {

    /**
     *
     * @var LoginService
     */
    public $LoginService;

    public function _initialize() {
        $userService = D('UserSess', 'Service');
        $userInfo = $userService->getUserInfo();
        
        $this->assign("userInfo", $userInfo);
    }

    /**
     * 新版海报 落地页跳转
     */
    public function bill()
    {
        redirectWithCode(301, 'http://cp.wangcaio2o.com/dzhb.html');
        exit;
    }
    // 推广页
    public function index() {
        $this->display();
    }
    
    // 二维码名片
    public function Qrcode() {
        $this->display();
    }

    public function Marketing() {
        header("HTTP/1.0 404 Not Found");
        $this->display('../Public/404');
        exit();
    }

    public function wapfms() {
        $tg_id = $_GET['tg_id'];
        $from_user_id = $_GET['from_user_id'];
        $this->assign('tg_id', $tg_id);
        $this->assign('from_user_id', $from_user_id);
        $this->display();
    }
    // 注册页
    public function registration() {
        $client_manager = I('post.client_manager', '', 'trim'); // 客户经理ID
        if (empty($client_manager)) {
            $client_manager = I('post.from_user_id', '', 'trim');
        }
        $tg_id = I('get.tg_id', '', 'trim'); // 推广平台的id
        if ($tg_id) {
            $this->assign('tg_id', $tg_id);
        }
        if ($this->isPost()) {
            $error = '';
            $email = I('post.email', null, 'trim');
            if (! check_str($email, 
                array(
                    'null' => false, 
                    'strtype' => 'email'), $error)) {
                $jumpUrl = array(
                    '返回' => 'javascript:history.go(-1)');
                $this->assign('jumpUrlList', $jumpUrl);
                $this->error("用户名{$error}", $jumpUrl);
            }
            $name = I('post.name', null, 'trim');
            if (! check_str($name, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '60'), $error)) {
                $jumpUrl = array(
                    '返回' => 'javascript:history.go(-1)');
                $this->assign('jumpUrlList', $jumpUrl);
                $this->error("企业名称{$error}", $jumpUrl);
            }
            $contacts = I('post.contacts', null, 'trim');
            if (! check_str($contacts, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '20'), $error)) {
                $jumpUrl = array(
                    '返回' => 'javascript:history.go(-1)');
                $this->assign('jumpUrlList', $jumpUrl);
                $this->error("业务负责人姓名{$error}", $jumpUrl);
            }
            $user_password = I('post.user_password', null, 'trim');
            if (! check_str($user_password, 
                array(
                    'null' => false, 
                    'minlen' => '6'), $error)) {
                $jumpUrl = array(
                    '返回' => 'javascript:history.go(-1)');
                $this->assign('jumpUrlList', $jumpUrl);
                $this->error("业务负责人密码{$error}", $jumpUrl);
            }
            $phoneNo = I('post.mobile', null, 'trim');
            if (! check_str($phoneNo, 
                array(
                    'null' => false, 
                    'strtype' => 'mobile'), $error)) {
                $jumpUrl = array(
                    '返回' => 'javascript:history.go(-1)');
                $this->assign('jumpUrlList', $jumpUrl);
                $this->error("业务负责人手机{$error}", $jumpUrl);
            }
            $provinceCode = I('post.province_code', null, 'trim');
            if (! check_str($provinceCode, 
                array(
                    'null' => false), $error)) {
                $jumpUrl = array(
                    '返回' => 'javascript:history.go(-1)');
                $this->assign('jumpUrlList', $jumpUrl);
                $this->error("请选择省份", $jumpUrl);
            }
            $cityCode = I('post.city_code', null, 'trim');
            if (! check_str($cityCode, 
                array(
                    'null' => false), $error)) {
                $jumpUrl = array(
                    '返回' => 'javascript:history.go(-1)');
                $this->assign('jumpUrlList', $jumpUrl);
                $this->error("请选择城市", $jumpUrl);
            }
            $townCode = I('post.town_code', null, 'trim');
            
            $tg_id = I('post.tg_id', '', 'trim');
            if (empty($tg_id)) {
                $tg_id = $_COOKIE["reg_tgxt_id"];
            }
            if (! empty($tg_id)) {
                $client_manager = 9000;
            }
            if (! check_str($tg_id, 
                array(
                    'null' => true, 
                    'maxlen' => 50), $error)) {
                $jumpUrl = array(
                    '返回' => 'javascript:history.go(-1)');
                $this->assign('jumpUrlList', $jumpUrl);
                $this->error("tg_id错误", $jumpUrl);
            }
            
            $data = array(
                'node_name' => $name, 
                'node_short_name' => mb_substr($name, 0, 9, 'utf-8'),  // 少于10个字符
                'regemail' => $email, 
                'contact_name' => $contacts, 
                'user_password' => md5($user_password), 
                'contact_phone' => $phoneNo, 
                'province_code' => $provinceCode, 
                'city_code' => $cityCode, 
                'town_code' => $townCode, 
                'reg_from' => '4', 
                'client_manager' => $client_manager, 
                'tg_id' => $tg_id);
            $service = D('NodeReg', 'Service');
            $result = $service->nodeAdd($data);
            if ($result['status']) {
                
                // 尝试触发注册相关的任务 start
                tag('reg_task_init', $result['data']);
                tag('reg_task_finish', $result['data']);
                // 尝试触发注册相关任务 end
                
                $autologininfo = base64_encode(
                    json_encode(
                        array(
                            'password' => trim($user_password), 
                            'regemail' => $email)));
                session('autologininfo', $autologininfo);
                
                $lname = I('post.landname', null, 'trim');
                if (IS_AJAX) {
                    if ($autologininfo) {
                            $LoginService = D('Login', 'Service');
                            $LoginService->ssoLogin($autologininfo);
                    }
                    switch($lname){
                        //旺分销
                        case "wangfenxiao":
                        $this->ajaxReturn(
                                array(
                                    'success' => 1, 
                                    'redirectUrl' => "index.php?g=Home&m=Land&a=success&landname={$lname}&tg_id={$tg_id}&type={$type}&auto_apply={$auto_apply}"));
                        break;
                        //全民营销
                        case "qmyx":
                        $this->ajaxReturn(
                                array(
                                    'success' => 1, 
                                    'redirectUrl' => "index.php?g=Home&m=MarketActive&a=index&landname=qmyx"));
                        break;
                        // 炫码
                        case "xuanma":
                        $this->ajaxReturn(
                        array(
                            'success' => 1, 
                            'redirectUrl' => "index.php?g=MarketActive&m=VisualCode&a=selectType"));
                        break;
                        // 炫码
                        case "xuanmaredirect":
                        $this->ajaxReturn(
                        array(
                            'success' => 1, 
                            'redirectUrl' => "index.php?g=MarketActive&m=VisualCode&a=done&istmp=1"));
                        break;
                        // 炫码
                        case "xuanmaregdirect":
                        $this->ajaxReturn(
                        array(
                            'success' => 1, 
                            'redirectUrl' => "index.php?g=MarketActive&m=VisualCode&a=selectType"));
                        break;
                        //抽奖
                        case "wcj":
                        $this->ajaxReturn(
                                array(
                                    'success' => 1, 
                                    'redirectUrl' => "index.php?g=Home&m=MarketActive&a=createNew&typelist=1"));
                        break;
                        //有奖问答
                        case "yjwd":
                        $this->ajaxReturn(
                                array(
                                    'success' => 1, 
                                    'redirectUrl' => "index.php?g=LabelAdmin&m=Answers&a=add&model=event&type=question&action=create&customer=c2"));
                        break;
                        //市场调研
                        case "scdy":
                        $this->ajaxReturn(
                                array(
                                    'success' => 1, 
                                    'redirectUrl' => "index.php?g=LabelAdmin&m=Bm&a=add&model=event&type=survey&action=create&customer=c2"));
                        break;
                        // 多乐互动
                        case "dlhd":
                        $this->ajaxReturn(
                            array(
                                'success' => 1, 
                                'redirectUrl' => "index.php?g=MarketActive&m=Activity&a=createFestival"));
                        break;
                        // 端午节
                        case "dwj":
                        $this->ajaxReturn(
                            array(
                                'success' => 1, 
                                'redirectUrl' => "index.php?g=MarketActive&m=Activity&a=createFestival"));
                        break;
                        // 卡券管理
                        case "kqgl":
                        $this->ajaxReturn(
                            array(
                                'success' => 1, 
                                'redirectUrl' => "index.php?g=WangcaiPc&m=NumGoods&a=index&landname=kqgl"));
                        break;
                        // 中秋国庆
                        case "zqgq":
                        $zqgqtype = I('zqgqtype');
                        $userService = D('UserSess', 'Service');
                        $userInfo = $userService->getUserInfo();
                        $newTime = date('Ymd');
                        if ($zqgqtype == 'zq') {
                            if ($newTime >= '20150906' && $newTime <= '20151016') {
                            
                                $dataArr = array(
                                    'node_id' => $userInfo['node_id'], 
                                    'batch_type' => '55');
                                $dataInfo = M('tmarketing_info')->where($dataArr)->find();
                                if ($dataInfo == '') {
                                    $this->ajaxReturn(
                                        array(
                                            'success' => 1, 
                                            'redirectUrl' => "index.php?g=Ecshop&m=O2OHot&a=index&batch_type=55"));
                                }
                                if ($dataInfo['status'] == '1') {
                                    $this->ajaxReturn(
                                        array(
                                            'success' => 1, 
                                            'redirectUrl' => "index.php?g=Ecshop&m=O2OHot&a=index&batch_type=55"));
                                }
                            } else {
                                $this->ajaxReturn(
                                    array(
                                        'success' => 1, 
                                        'redirectUrl' => "index.php?g=Home&m=Index&a=index"));
                            }
                        }
                        if ($zqgqtype == 'gq') {
                            $this->ajaxReturn(
                                array(
                                    'success' => 1, 
                                    'redirectUrl' => "index.php?g=MarketActive&m=Activity&a=createFestival"));
                        }
                    break;
                        //优惠劵
                        case "yhj":
                        $this->ajaxReturn(
                                array(
                                    'success' => 1, 
                                    'redirectUrl' => "index.php?g=Home&m=MarketActive&a=createNew&typelist=3"));
                        break;
                        //礼品派发
                        case "lppf":
                        $this->ajaxReturn(
                                array(
                                    'success' => 1, 
                                    'redirectUrl' => "index.php?g=Home&m=MarketActive&a=listNew&batchtype=9"));
                        break;
                        //列表模板
                        case "zhyx":
                        $this->ajaxReturn(
                                array(
                                    'success' => 1, 
                                    'redirectUrl' => "index.php?g=Home&m=MarketActive&a=listNew&liststyle=2&batchtype=8"));
                        break;
                        //投票
                        case "wtp":
                        $this->ajaxReturn(
                                array(
                                    'success' => 1, 
                                    'redirectUrl' => "index.php?g=Home&m=MarketActive&a=createNew&typelist=2"));
                        break;
                        //幸运大转盘
                        case "xydzp":
                        $this->ajaxReturn(
                                array(
                                    'success' => 1, 
                                    'redirectUrl' => "index.php?g=LabelAdmin&m=DrawLotteryAdmin&a=setActBasicInfo"));
                        break;
                        // 微信营销
                        case "wxyx":
                        $this->ajaxReturn(
                                array(
                                    'success' => 1, 
                                    'redirectUrl' => "index.php?g=Weixin&m=Weixin&a=index"));
                        break;
                        //旺财小店
                        case "wcxd":
                        $this->ajaxReturn(
                                array(
                                    'success' => 1, 
                                    'redirectUrl' => "index.php?g=Ecshop&m=Index&a=preview"));
                        break;
                        // 多米收单
                        case "dmsd":
                        $this->ajaxReturn(
                                array(
                                    'success' => 1, 
                                    'redirectUrl' => "index.php?g=Alipay&m=Index&a=index&landname=dmsd"));
                        break;
                        //QQ公众号
                        case "qqgzh":
                        $this->ajaxReturn(
                                array(
                                    'success' => 1, 
                                    'redirectUrl' => "index.php?g=QqPublicNumber&m=QqCode&a=index&landname=qqgch"));
                        break;
                        //卡券营销
                        case "kqyx":
                        $this->ajaxReturn(
                                array(
                                    'success' => 1, 
                                    'redirectUrl' => "index.php?g=WangcaiPc&m=NumGoods&a=index&landname=kqyx"));
                        break;
                        //多赢积分
                        case "dyjf":
                        $this->ajaxReturn(
                                array(
                                    'success' => 1, 
                                    'redirectUrl' => "index.php?g=Integral&m=Integral&a=integralMarketing&landname=dyjf"));
                        break;
                        //电子海报
                        case "dzhb":
                        $this->ajaxReturn(
                                array(
                                    'success' => 1, 
                                    'redirectUrl' => "index.php?g=MarketActive&m=NewPoster&a=revisePosterDataToNewNode"));
                        break;
                        //电子海报
                        case "dzhbdirect":
                            $this->ajaxReturn(
                            array(
                            'success' => 1,
                            'redirectUrl' => "index.php?g=MarketActive&m=NewPoster&a=revisePosterDataToNewNode&backToEditPoster=1"));
                            break;
                        //金猴闹新春
                        case "jhnxc":
                        $this->ajaxReturn(
                                array(
                                    'success' => 1, 
                                    'redirectUrl' => "index.php?g=Home&m=MarketActive&a=createNew&typelist=4&landname=jhnxc"));
                        break;
                        //二维码名片
                        case "ymp":
                        $this->ajaxReturn(
                                array(
                                    'success' => 1, 
                                    'redirectUrl' => "index.php?g=Wap&m=Vcard&a=index"));
                        break;
                        //微信红包
                        case "wxhb":
                        $this->ajaxReturn(
                                array(
                                    'success' => 1, 
                                    'redirectUrl' => "index.php?g=WangcaiPc&m=NumGoods&a=ymWeChatIndex&landname=wxhb"));
                        break;
                        //多景联盟
                        case "xxqd":
                        $this->ajaxReturn(
                                array(
                                    'success' => 1));
                        break;
                        //微官网
                        case "wgw":
                        $this->ajaxReturn(
                                array(
                                    'success' => 1, 
                                    'redirectUrl' => "index.php?g=MicroWeb&m=Index&a=index"));
                        break;
                        //付满送 pay 支付宝线下收单 条码支付
                        case "fms":
                        case "pay":
                        case "zfb":
                        case "tmzf":
                        $this->ajaxReturn(
                                array(
                                    'success' => 1, 
                                    'redirectUrl' => "index.php?g=Alipay&m=Index&a=info_alipay"));
                        break;
                        // 卡券商城
                        case "mall":
                        $this->ajaxReturn(
                                array(
                                    'success' => 1, 
                                    'redirectUrl' => "index.php?g=Hall&m=Index&a=index&landname=mall"));
                        break;
                        // 妈妈,我爱你(母亲节)
                        case "mama":
                        $this->ajaxReturn(
                                array(
                                    'success' => 1, 
                                    'redirectUrl' => "index.php?g=MarketActive&m=Activity&a=createFestival"));
                        break;
                         // 欧洲杯
                        case "ecup":
                        $this->ajaxReturn(
                            array(
                                'success' => 1, 
                                'redirectUrl' => "index.php?g=MarketActive&m=Activity&a=createFestival&landname=ecup"));
                        break;
                        //默认
                        default:
                        $fromurl = session('fromurl');
                            if ($fromurl) {
                                $this->ajaxReturn(
                                    array(
                                        'success' => 1,
                                        'redirectUrl' => $fromurl));
                            } else {
                                $this->ajaxReturn(
                                    array(
                                        'success' => 1,
                                        'redirectUrl' => "index.php?g=Home&m=Land&a=success&landname={$lname}&tg_id={$tg_id}"));
                        }
                    }
                } else {
                    $this->redirect(
                        "index.php?g=Home&m=Land&a=success&landname={$lname}&tg_id={$tg_id}");
                }
                exit();
            } else {
                $jumpUrl = array(
                    '返回' => 'javascript:history.go(-1)');
                $this->assign('jumpUrlList', $jumpUrl);
                $this->error($result['info'], $jumpUrl);
            }
        }
        $this->display();
    }

    /**
     * 自动登录
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $autologininfo
     * @return bool
     */
    public function autoLogin($autologininfo) {
        if (empty($this->LoginService)) {
            $this->LoginService = D('Login', 'Service');
        }
        
        return $this->LoginService->ssoLogin($autologininfo);
    }

    public function success() {
        $this->display('success');
    }

    public function fmsregistration() {
        $client_manager = I('post.client_manager', '', 'trim'); // 客户经理ID
        if (empty($client_manager)) {
            $client_manager = I('post.from_user_id', '', 'trim');
        }
        $tg_id = I('get.tg_id', '', 'trim'); // 推广平台的id
        if ($tg_id) {
            $this->assign('tg_id', $tg_id);
        }
        if ($this->isPost()) {
            $error = '';
            $email = I('post.email', null, 'trim');
            if (! check_str($email, 
                array(
                    'null' => false, 
                    'strtype' => 'email'), $error)) {
                $jumpUrl = array(
                    '返回' => 'javascript:history.go(-1)');
                $this->assign('jumpUrlList', $jumpUrl);
                $this->error("email{$error}", $jumpUrl);
            }
            $name = I('post.name', null, 'trim');
            if (! check_str($name, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '60'), $error)) {
                $jumpUrl = array(
                    '返回' => 'javascript:history.go(-1)');
                $this->assign('jumpUrlList', $jumpUrl);
                $this->error("企业名称{$error}", $jumpUrl);
            }
            $contacts = I('post.contacts', null, 'trim');
            if (! check_str($contacts, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '20'), $error)) {
                $jumpUrl = array(
                    '返回' => 'javascript:history.go(-1)');
                $this->assign('jumpUrlList', $jumpUrl);
                $this->error("联系人{$error}", $jumpUrl);
            }
            $user_password = I('post.password', null, 'trim');
            if (! check_str($user_password, 
                array(
                    'null' => false, 
                    'minlen' => '6'), $error)) {
                $jumpUrl = array(
                    '返回' => 'javascript:history.go(-1)');
                $this->assign('jumpUrlList', $jumpUrl);
                $this->error("密码{$error}", $jumpUrl);
            }
            $phoneNo = I('post.mobile', null, 'trim');
            if (! check_str($phoneNo, 
                array(
                    'null' => false, 
                    'strtype' => 'mobile'), $error)) {
                $jumpUrl = array(
                    '返回' => 'javascript:history.go(-1)');
                $this->assign('jumpUrlList', $jumpUrl);
                $this->error("手机{$error}", $jumpUrl);
            }
            $provinceCode = I('post.province_code', null, 'trim');
            if (! check_str($provinceCode, 
                array(
                    'null' => false), $error)) {
                $jumpUrl = array(
                    '返回' => 'javascript:history.go(-1)');
                $this->assign('jumpUrlList', $jumpUrl);
                $this->error("请选择省份", $jumpUrl);
            }
            $cityCode = I('post.city_code', null, 'trim');
            if (! check_str($cityCode, 
                array(
                    'null' => false), $error)) {
                $jumpUrl = array(
                    '返回' => 'javascript:history.go(-1)');
                $this->assign('jumpUrlList', $jumpUrl);
                $this->error("请选择城市", $jumpUrl);
            }
            $townCode = I('post.town_code', null, 'trim');
            $tg_id = I('post.tg_id', '', 'trim');
            if (empty($tg_id)) {
                $tg_id = $_COOKIE["reg_tgxt_id"];
            }
            if (! empty($tg_id)) {
                $client_manager = 9000;
            }
            if (! check_str($tg_id, 
                array(
                    'null' => true, 
                    'maxlen' => 50), $error)) {
                $jumpUrl = array(
                    '返回' => 'javascript:history.go(-1)');
                $this->assign('jumpUrlList', $jumpUrl);
                $this->error("tg_id错误", $jumpUrl);
            }
            $data = array(
                'node_name' => $name, 
                'node_short_name' => mb_substr($name, 0, 9, 'utf-8'), 
                'user_password' => md5($user_password), 
                'contact_phone' => $phoneNo, 
                'reg_from' => '4', 
                'client_manager' => $client_manager, 
                'contact_name' => $contacts, 
                'regemail' => $email, 
                'province_code' => $provinceCode, 
                'city_code' => $cityCode, 
                'town_code' => $townCode, 
                'tg_id' => $tg_id);
            
            $service = D('NodeReg', 'Service');
            $result = $service->nodeAdd($data);
            if ($result['status']) {
                $autologininfo = base64_encode(
                    json_encode(
                        array(
                            'password' => trim($user_password), 
                            'regemail' => $email)));
                $LoginService = D('Login', 'Service');
                $LoginService->ssoLogin($autologininfo);
                
                $autologininfo = session('autologininfo');
                if ($autologininfo) {
                    $this->LoginService = D('Login', 'Service');
                    $this->LoginService->ssoLogin($autologininfo);
                }
                
                $userService = D('UserSess', 'Service');
                $userInfo = $userService->getUserInfo();
                $this->assign("userInfo", $userInfo);
                $_s = session('autologininfo');
                if (empty($_s)) {
                    $this->assign("autologininfo", true);
                } else {
                    $this->assign("autologininfo", $_s);
                }
                
                exit(
                    json_encode(
                        array(
                            'info' => '注册成功', 
                            'status' => 1)));
            } else {
                exit(
                    json_encode(
                        array(
                            'info' => $result['info'], 
                            'status' => 2)));
            }
        }
        $this->display();
    }
}