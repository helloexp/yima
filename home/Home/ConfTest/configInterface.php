<?php
/* 各种接口参数 自动被config.php加载 */
return array(
    'SSO_INTERFACE_URL' => 'http://10.10.1.34/SSO2/index.php?m=Interface',//sso基础接口
    // SSO配置参数
    'SSO_LOGIN_URL' => 'http://10.10.1.34/SSO2/index.php?m=Interface&a=RequestLogin',  // sso登录地址
                                                                                      // 'SSO_LOGIN_URL'=>'http://app.app.imageco.cn/index.php?m=Login&a=Login',//sso登录地址
    'SSO_CHECK_URL' => 'http://10.10.1.34/SSO2/index.php?m=Interface&a=SsoCheck',  // sso校验地址
    'SSO_LOGOUT_URL' => 'http://10.10.1.34/SSO2/index.php?m=Interface&a=Logout', 
    'SSO_SYSID' => '00022', 
    'SSO_MEMCACHE_IP' => '222.44.51.34',

    'CURRENT_HOST' => 'http://test.wangcaio2o.com/',  // 网站域名
                                                     
    // 卡券交易大厅支付宝配置参数参数
    'ALIPAY' => array(
        'PARTNER' => '2088301487148084',  // 合作身份者id，以2088开头的16位纯数字
        'KEY' => '8kc7woom17kcqdt6vctd7plon6lgbvmt',  // 安全检验码，以数字和字母组成的32位字符
        'SELLER_EMAIL' => 'cardpay@imageco.com.cn'),
    // 翼码旺财-支付宝授权信息
    'ALIPAY_AUTHORY' =>array(
        'app_id'        => '2016012601122421',
        'request_url'   => 'https://openauth.alipay.com/oauth2/appToAppAuth.htm',
        'token_url'     => 'https://openapi.alipay.com/gateway.do',
        'prikey_path'   => CONF_PATH.'key/alipay_rsa_private_key.pem',
        'pubkey_path'   => CONF_PATH.'key/alipay_rsa_public_key.pem',
    ),
    // 支撑平台接口为旺财提供的查询接口
    'ISS_QUERY_POS_URL' => 'http://222.44.51.34/iss2_serv_wangcai/serv.php', 
    // SSO接口
    'SSO_REQUEST_LOGIN_URL' => 'http://10.10.1.34/SSO2/index.php?m=Interface&a=RequestLoginWangCai', 
    // SSO用户修改接口
    'SSO_USER_SERV_URL' => 'http://10.10.1.34/SSO2/index.php?m=Interface&a=User', 
    // SSO小妹账号创建接口(2014-02新增接口)
    'SSO_USER_NEW_SERV_URL' => 'http://222.44.51.34/SSO2/index.php?m=Interface&a=UserAdd', 
    
    // SSO用户修改接口
    'SSO_RESETPWD_SERV_URL' => 'http://10.10.1.34/SSO2/index.php?m=Interface&a=ResetPassword', 
    
    // SSO用户状态修改接口
    'SSO_USER_STATUS_SERV_URL' => 'http://10.10.1.34/SSO2/index.php?m=Interface&a=UserStatus', 
    
    // sso机构注册接口
    'SSO_NODE_REG_URL' => 'http://10.10.1.34/SSO2/index.php?m=Interface&a=Register', 
    
    // 支撑平台内部接口（终端激活）
    'ISS_INTERFACE_INTER' => 'http://10.10.1.34/iss2_serv/iss2_interface/serv.php', 
    // 支撑验证接口(签到，验证，撤销）
    'ISS_POS_SERV_URL' => 'http://10.10.1.34/zcpt_webpos_serv/tcs_interface.php', 
    // 线上门店激活地址
    'ONLINE_POS_ACTIVE' => 'http://222.44.51.34/WebPosProject/WebPos/index.php?m=InterfaceWangCai&a=ActivatePos&posId=', 
    // 支撑2标准接口（凭证类，活动类）
    'ISS_SEND_SERV_URL' => 'https://222.44.51.34:18443/iss2.do', 
    'ISS_SYSTEM_ID' => '7017', 
    'ISS_MAC_KEY' => 'wangcai', 
    'ISS_REQ_TIME' => 60, 
    
    // 会员中心
    'MEMBER_URL' => 'http://pts.masafa.cn/serv/client_serv/serv.php', 
    
    // 营账接口
    'YZ_SERV_URL' => 'http://10.10.1.159:80/jsxt/yzxt_interface/serv.php', 
    // 'YZ_SERV_URL'=>'http://127.0.0.1/jsxt/yzxt_interface/serv.php',
    'YZ_SYSTEM_ID' => '1000', 
    'YZ_MAC_KEY' => 'f929593b06a63ab5', 
    
    // 营账充值接口
    'YZ_RECHARGE_URL' => 'http://101.231.188.78:8083/jsxt/TENT/index.php?m=ReCharge&a=reCharge',

    //营账查询充值记录接口
    'YZ_RECORD_URL' => 'http://101.231.188.78:8083/jsxt/TENT/index.php?m=ReInfo&a=viewRecord',
    // 扣款余额不足后,调用营帐现充现扣的接口
    'DIRECT_DEDUCT_URL' => 'http://101.231.188.78:8083/jsxt/TENT/index.php?m=ActivityByReCharge&a=reCharge', 
    
    // 电子合约修改url
    'ISS_SETG00DSINFOREQ_URL' => 'http://222.44.51.34/iss2_serv/iss2_interface/serv.php', 
    // smil
    'ISS_SEND_ID' => '00006786', 
    'ISS_PLATFORM_ID' => '7033', 
    'ISS_SEND_USER' => 'ppool', 
    'ISS_SEND_USER_PASS' => 'nlppool', 
    'ISS_SEND_URL' => 'http://222.44.51.34/iss2_serv/iss2_interface/serv.php', 
    
    // pos验证接口
    'ISS_POS_URL' => 'http://222.44.51.34/iss2_serv/pos_serv_for_bts/pos_serv.php', 
    
    // 爱拍获取门店
    'STORE_ISS_PLATFORM_ID' => '7017', 
    'STORE_URL' => 'http://10.10.1.34/iss2_serv/iss2_serv_for_imageco/serv.php',
    'STORE_ISS_SEND_USER_PASS' => 'wangcai', 
    
    // 支撑2内部接口（门店添加）
    'ISS_SERV_FOR_IMAGECO' => 'http://222.44.51.34/iss2_serv/iss2_serv_for_imageco/serv.php', 
    // epos登录页
    'EPOS_LOGIN_URL' => 'http://222.44.51.34/WebPosProject/WebPos/?', 
    // 收银台付款post地址
    'YZ_CAHSIER_POST_URL' => 'http://101.231.188.78:8083/jsxt/TENT/index.php?m=Cashier', 
    // 收银台取消订单的地址
    'YZ_CANCEL_ORDER_URL' => 'http://10.10.1.159/jsxt/TENT/index.php?', 
    // 发码
    'WC_SEND_ARR' => array(
        'url' => 'http://127.0.0.1/AppServ/index.php?a=CodeSendForLable'),
    // 重发
    'WC_RESEND_ARR' => array(
        'url' => 'http://127.0.0.1/AppServ/index.php?a=CodeResendReq'), 
    // 撤消
    'WC_CANCEL_ARR' => array(
        'url' => 'http://127.0.0.1/AppServ/index.php?a=CodeCancelByReqId'),
    
    // 宁夏移动接口地址
    'MOBILE_OPEN_SERVICE_URL' => 'http://218.203.120.4:9191/fcgi-bin/g3crm', 
    'MOBILE_OPEN_MAC_KEY' => '111111', 
    // 手机验证码参数
    'MOBILE_ISSPID' => '00000243', 
    'MOBILE_ACTIVITYID' => '13101622783', 
    
	//第三方网关下发纯文本参数
	'OTHER_MOBILE_ISSPID' => '00000243',
	'OTHER_MOBILE_ACTIVITYID'=>'13101622783',
    // 工单跳转参数
    'ISS_TOKEN_LOGIN_URL' => 'http://101.231.188.78/iss2/main/login.php?act=login&token=[token]&node_id=[node_id]&node_name=[node_name]&user_id=[user_id]&user_name=[user_name]', 
    'ISS_PAGE_PROCESS_ADDTERMINALS' => 'http://101.231.188.78/iss2/node/index.php?m=Process&a=AddTerminals', 
    'ISS_PAGE_PROCESS_lIST' => 'http://101.231.188.78/iss2/node/index.php?m=Process&a=List', 
    
    // 炫码接口
    
    'VISUALCODE' => array(
        'APPRO_WX_URL' => '222.44.51.34', 
        'APPRO_WX_PORT' => '20001'), 
    'WEIXIN' => array(
        'appid' => 'wxccabc929493425ad', 
        'secret' => '8c0fd95cdb2fbbbb5d1222fa32777f99'), 
    // 'appid'=>'wx2cd8c40f859964c9',
    // 'secret'=>'99452713461eb122eb4d02a492a028db'
    // 支付宝通知活动号
    'ALIPAY_NOTICE_ACTIVITYID' => '13101622783',
    // 微信支付配置
    'WEIXINPAY' => array(
        // 'appid'=>'wx06d2717f0e7e3824',
        // 'secret'=>'c5d94546cc378e0e86bc23eef7a0abbe'
        'appid' => 'wx5acb63e448b4fc22', 
        'mch_id' => '1237207202', 
        'app_key' => '659cb45423c9c4fa3d466bb7d2d3cbd3', 
        'secret' => '18b40cd823f7e3ba4f4b69d74752016a'),
    //银联支付配置
    'UNIONPAY'=> [
        /* Test配置
        //'partner'         => '777290058110097',//商户号
        'SIGN_CERT_PATH'  => CONF_PATH.'key/acp_test_sign.pfx',
        //'VERIFY_CERT_DIR' => CONF_PATH.'key/',
        'SIGN_CERT_PWD'   => '000000',
        //'FRONT_TRANS_URL' => 'https://101.231.204.80:5000/gateway/api/frontTransReq.do',//手机网管支付地址
        //*/
        'partner'         => '802500053110607',//商户号
        'SIGN_CERT_PATH'  => CONF_PATH.'key/acp_prod_sign.pfx',
        'VERIFY_CERT_DIR' => CONF_PATH.'key/',
        'SIGN_CERT_PWD'   => '888666',
        'FRONT_TRANS_URL' => 'https://gateway.95516.com/gateway/api/frontTransReq.do',//手机网管支付地址
    ],
    // 旺财对外接口
    'WC_APP_SERV_URL' => 'http://test.wangcaio2o.com/AppServ/index.php?',
    
    // 落地页域名
    'CP_URL' => 'http://101.231.188.78:9088/tgxt', 
    //支付通道配置
    'PAYCHANNEL' => array(
        '1' => '支付宝',
        '2' => '银联',
        '3' => '支付宝',
        '4' => '和包',
    ),
    //流量包and话费配置文件
    'MOBILE_DATA'=> array(
            'appkey'=>'093470ab5fb061c193c081e1926f23a6',//云猴key
            'dahanUrl' => 'http://if.dahanbank.cn',//大汉测试'http://test.dahanbank.cn:3429/if',
            'partner_id' => '6228987261',//云猴编号
            'user' => 'AdminYim',//admin_YM大汉用户名
            'pwd'=> 'test0257',//123456大汉密码
            'sendUrl' => 'https://api.5160.com/business'//云猴流量包接口地址
    ),


    //抽奖请求的接口地址
    'CJ_URL' => 'http://test.wangcaio2o.com/AppServ/index.php?a=AwardRequest',
    // 和包支付配置
    'CMPAY' => array(
        'storeId' => '7823',     //小店ID   线上ID 57700
        'nodeId' => '00026478',     //唯一标识   线上ID 57700
        'characterSet' => '02',  // 字符集 00- GBK 01- GB2312 02- UTF-8 // 默讣 00-GBK
        'signType' => 'MD5',  // 只能是MD5, RSA
        'type' => 'DirectPayConfirm',  // 接口类型 WAPDirectPayConfirm  DirectPayConfirm
        'version' => '2.0.0',  // 版本号
        'currency' => '00',  // 币种
        'period' => '07',  // 订单有效期数量
        'periodUnit' => '02',  // 有效期单位 00- 分 01- 小时 02- 日 03- 月
        'merchantId' => '888073157340001',  // 商户编号
        'key' => '0HgNAeOyG0B3DkDuzLUwC4AYW1GX1BtEOPIlKnMl0EVYMYXweQxMUiXs1hNnbptJ',  // 商户密钥
        //'merchantId' => '888009974250007',  // 商户编号(线上)
        //'key' => '0fPk8ioyICmzUQ5iuy1db7VTf3OoEZav0SxJu4q5TGajLlW90q6MKvbklCptGPLF',  // 商户密钥(线上)
        'appId' => 'YIMA0001',  //appid (测试)
        'sendKey' => 'zZnDaLuCCvLl1CTenZlBq0HnONw0PMrH',  //发送KEY (测试)
        'getSignKeyUrl' => "http://218.75.224.28:28700/ccaweb/CCLIMCA4/2208000.dor", //测试
        'sendUrl' => 'https://ipos.10086.cn/ips/cmpayService'));// 和包接入地址




