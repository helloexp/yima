<?php
/* 接口配置（自动载入） */
return array(
    // 支撑平台接口为旺财提供的查询接口
    'ISS_QUERY_POS_URL' => 'http://222.44.51.34/iss2_serv_wangcai/serv.php',
    // SSO接口
    'SSO_REQUEST_LOGIN_URL' => 'http://222.44.51.34/SSO2/index.php?m=Interface&a=RequestLoginWangCai',
    // SSO用户修改接口
    'SSO_USER_SERV_URL' => 'http://222.44.51.34/SSO2/index.php?m=Interface&a=User',
    
    // 支撑平台内部接口（终端激活）
    'ISS_INTERFACE_INTER' => 'http://222.44.51.34/iss2_serv/iss2_interface/serv.php',
    // 支撑验证接口(签到，验证，撤销）
    'ISS_POS_SERV_URL' => 'http://222.44.51.34/zcpt_webpos_serv/tcs_interface.php',
    
    // 支撑2标准接口（凭证类，活动类）
    'ISS_SEND_SERV_URL' => 'https://222.44.51.34:18443/iss2.do',
    'ISS_SYSTEM_ID' => '7017',
    'WXCARD_ISS_SYSTEM_ID' => '7063',
	'WXCARD_FRIEND_ISS_SYSTEM_ID' => '6620',
    'ISS_MAC_KEY' => 'wangcai',
    'ISS_REQ_TIME' => 60,
    
    // 营账接口
    // 'YZ_SERV_URL'=>'http://116.228.158.210:8083/jsxt/yzxt_interface/serv.php',
    'YZ_SERV_URL' => 'http://10.10.1.159:80/jsxt/yzxt_interface/serv.php',
    'YZ_SYSTEM_ID' => '1000',
    'YZ_MAC_KEY' => 'f929593b06a63ab5',
    
    'ISS_SERV_FOR_IMAGECO' => 'http://222.44.51.34/iss2_serv/iss2_serv_for_imageco/serv.php',
    'PHONE_BILLS_URL' => 'http://test.wangcaio2o.com/index.php?g=Label&m=FetchBill&a=index&id_str=',
    
    // appserv�ӿ�
    'WC_APPSERV_URL' => 'http://127.0.0.1/AppServ/index.php?a=CodeSendForLable',
    // 旺财微信卡券接口
    'WC_WXCARD_URL' => 'http://127.0.0.1/index.php?g=WeixinServ&m=WeiXinCard&a=add_assist_number',
    
    // 旺财微信红包接口
    'WC_WXREDPACK_URL' => 'http://127.0.0.1/index.php?g=WeixinServ&m=RedPack&a=sendRedPack',
    
    // 帮助文件查看
    'WEB_HELP_URL' => 'http://www.baidu.com/',

	// 流量包接口
    'MOBILE_DATA_URL' => 'http://127.0.0.1/index.php?g=Interface&m=MobileData&a=sendMobileData',

    // 文本下发参数
    'MOBILE_ISSPID' => '00000243',
    'MOBILE_ACTIVITYID' => '13101622783'
)
;
