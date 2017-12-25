<?php
// 非标商户配置
return array(
    // 非标通宝斋
    'pingan' => array(
        'node_id' => '00023952',
        'zc_pingan_tz' => 'http://bx-dev.wangcaio2o.com/index.php?g=ZcNoticeServ&m=Index&a=index',
    ),
    'gansu' => array(
        'node_id' => '00038893',
        'role_id' => '205',
    ),
    'meihui' => array(
        'node_id' => '00039673',
    ),
    'fb_tongbaozhai' => array(
        'node_id' => array(
            '00017172',
            '00017292',
            '00004488', ), ),
    'changsha_bank' => array(
        'node_id' => '00004488', ),
    'df' => array(
        'node_id' => '00026412',
        'id' => '8015',
        'appid' => 'wx980104963176cb81',
        'secret' => '34ed5d62e8c4890070ac99af415dd760',
        'store_admin_role_id' => 37,
        'WAPDF_BATCH_TYPE' => array(
            '2',
            '3',
            '10',
            '1004', ),
        'WAPDF_BATCH_NAME' => array(
            '2' => '抽奖',
            '3' => '市场调研',
            '10' => '有奖答题',
            '1004' => 'DF会员信息收集', ),
        'WAPDF_BATCH_URL' => array(
            '2' => 'Label/News/index',
            '3' => 'Label/Bm/index',
            '10' => 'Label/Answers/index',
            '1004' => 'Label/DfBm/index', ), ),
    'df_store_id' => '01028928',
    'codeStrNumber' => array(
        'a',
        'b',
        'c',
        'd',
        'e',
        'f',
        'g',
        'h',
        'i',
        'j',
        'k',
        'm',
        'n',
        'p',
        'q',
        'r',
        's',
        't',
        'u',
        'v',
        'w',
        'x',
        'y',
        'z', ),
    // 京东团购入口机构号判断
    'access_jd_node_id' => array(
        '00004488', ),
    // 博雅非标
    'fb_boya' => array(
        'node_id' => '00026153',
        'appid' => 'wx86ca8f1a337ef6cd',
        'secret' => '28ca792b48b60e655c54f8d6449eee73', ),
    // 河北太平洋保险
    'hbtpybx' => array(
        'node_id' => '00026474', ),
    'onlinesee' => array(
        'node_id' => '00027096', ),
    // 甘肃太平洋保险
    'gstpybx' => array(
        'node_id' => '000264740', ),
    // 山西太平洋
    'sxtpybx' => array(
        'node_id' => '000264740', ),
    // 挪车
    'chaowai' => array(
        'node_id' => '00004488', ),
    'onlinesee' => array(
        'node_id' => '00027096', ),
    'qianji' => array(
        'node_id' => '00023332', ),

    'tangshan' => array(
        'node_id' => '00023332', ),
    // 【非标V1.0.0_C12】河北平安人寿，
    'hebei_pars' => array(
        // 营销活动ID
        'batch_id' => array(
            5322,
            7203, ),
        // 界面元素对应的问题ID
        'resp_arr' => array(
            5322 => array(
                // 每月负担
                'monthly_amount' => 2496,
                // 照顾年限
                'years' => 2497,
                // 收益率
                'yield' => 2498,
                // 按揭及债务
                'mortgage_and_debt' => 2499,
                // 寿险保额
                'has_amount' => 2500, ),
            7203 => array(
                // 每月负担
                'monthly_amount' => 2532,
                // 照顾年限
                'years' => 2528,
                // 收益率
                'yield' => 2529,
                // 按揭及债务
                'mortgage_and_debt' => 2530,
                // 寿险保额
                'has_amount' => 2531, ), ), ),
    'zongzifb' => array(
        'node_id' => '00023332', ),
    'shiyoufb' => array(
        'node_id' => '00004488', ),
    'onlineExper' => '2222',
    // 设置微信所有菜单功能商户ID
    'weixin_menu_all' => array(
        '00004488',
        '00026412', ),
    // 设置微信菜单类型
    'weixin_menu_type' => array(
        3 => array(
            'title' => '扫码推事件',
            'value' => 'scancode_push', ), ),
    'adminUploadImgUrl' => 'http://test.wangcaio2o.com/wcadmin/upload/', // 后台上传显示地址

    // 翼惠宝非标参数
    'yhb' => array(
        'node_id' => '00029693',
        'domain' => array('yhb.wangcaio2o.com', ),
        'smsconfig' => array(
            'company' => 'ONLINESH',
            'account' => 'shequyouhuibao',
            'password' => '123456', ),
        // 我的优惠卷地址
        'myVoucher' => 'http://test.wangcaio2o.com/index.php?g=Yhb&m=YhbWap&a=voucherList',
        'allow_gm' => array(
            'Home-AccountInfo',
            'Home-NodeUserNew',
            'Home-EditPwd',
            'Home-AccountAuth',
            'Home-ServicesCenter',
            'Weixin',
            'ImgResize-Upload',
            'Common-common',
            'LabelAdmin-SelectBatches',
            'LabelAdmin-DownCj',
            'LabelAdmin-Chart',
            'LabelAdmin-ShowCode',
            'LabelAdmin-CjRuleList',
            'Common-SelectJp',
            'WangcaiPc-NumGoods', ),
        'yhb_sms' => array(
            'username' => 'shrxxx@shrxxx',
            'password' => '*UXS32W(',
            'subid' => '',
            'msgtype' => '1',
            'url' => 'http://211.147.224.154:13013/cgi-bin/sendsms?', ), ),
    /*广西石油非标参数*/
    'cnpc_gx' => array(
        'node_id' => '00030260',
        'domain' => array(
            'gxsy.wangcaio2o.com', ), ),

    'defaultBankName' => array(
        '中国工商银行',
        '中国农业银行',
        '招商银行',
        '中国建设银行',
        '中国银行',
        '中国邮政储蓄银行',
        '浦发银行',
        '广发银行',
        '中国民生银行',
        '平安银行',
        '中国光大银行',
        '兴业银行',
        '中信银行',
        '交通银行',
        '上海银行',
        '渤海银行',
        '南昌银行',
        '上海农商银行',
        '包商银行',
        '广东南粤银行',
        '北京银行',
        '华夏银行',
        '江苏银行', ),
    // 微官网非标
    'midNational' => array(
        'node_id' => '00004488', ),

    // 大连H5定制界面
    'dlh5' => array(
        'cert_mid' => 8159,
        'mid' => 40097, ),

    // 萨湾金谷（卖大米）的提领订单通知参数
    'sawanjingu' => array(
        'node_id' => '00026092',
        'send_mail' => 'chengs@imageco.com.cn',
        'send_mail2' => 'tech@imageco.com.cn', ),
    'withDraw' => array(
        'fromNodeId' => '00026092',
        'createNodeId' => '00030804', ),
    // 云南平安
    'yunnan' => array(
        'batch_id' => '8730', ),
    // 金猴闹春非标
    'SpringMonkeyChannelId' => '16028',
    // 非标用户特定 【非标V1.1.0_C15】鱼旨寿司 redmin 15348
    'fishsush' => array(
        'mid' => array(
            '42268',
            '42314',
            '8539', ),
        'address' => array(
            '42268',
            '8539', ), ),
    // 非标用户特定 【非标V1.1.0_C15】爱蒂宝 redmin 15348
    'aidibao' => array(
        'mid' => array(
            '9152',
            '10173', ), ),
    // 提现通知人员
    'mailto' => array(
        'notice' => array(
            'yangxin@imageco.com.cn',
//            'lutt@imageco.com.cn',   //测试环境财务的邮件无需发送
            'lish@imageco.com.cn',
            'zengc@imageco.com.cn',
            'wangjin@imageco.com.cn',
            'liujl@imageco.com.cn', ),
        'sendOne' => 'chenjq@imageco.com.cn',
        'sendName' => '陈嘉琦',
    ),
    // 山东平安
    'shandong' => array(
        'batch_id' => '10271', ),
    // 电商模板
    'o2oTpl' => array(
        'tplId' => array(
            '1',
            '3',
            '4', ), ),
    // 双蛋非标用户机构号
    'shuangdan_fb_nodeid' => '00034960',
    //微信助手 市场部春节活动非标开发
    'new_year_node_id' => '00026652',
    //微信多客服
    'weixinKf' => array(
        'node_id' => array(
            //爱蒂宝
            '00034974',
        ),
    ),
    // 爱蒂宝
    'adb' => array(
        'node_id' => '00034974',
        'attention_href' => 'http://open.weixin.qq.com/qr/code/?username=gh_9c4c05da5b2d',
        'city_sort' => array(
            '01' => array(
                'pc_id' => '01',
                'sort' => 0,
            ),
            '09' => array(
                'pc_id' => '09',
                'sort' => 1,
            ),
            '19' => array(
                'pc_id' => '19',
                'sort' => 2,
            ),
            '23' => array(
                'pc_id' => '23',
                'sort' => 3,
            ),
            '02' => array(
                'pc_id' => '02',
                'sort' => 4,
            ),
            '16' => array(
                'pc_id' => '16',
                'sort' => 5,
            ),
            '26' => array(
                'pc_id' => '26',
                'sort' => 6,
            ),
            '10' => array(
                'pc_id' => '10',
                'sort' => 7,
            ),
            '08' => array(
                'pc_id' => '08',
                'sort' => 8,
            ),
            '07' => array(
                'pc_id' => '07',
                'sort' => 9,
            ),
            '12' => array(
                'pc_id' => '12',
                'sort' => 10,
            ),
            '13' => array(
                'pc_id' => '13',
                'sort' => 11,
            ),
            '11' => array(
                'pc_id' => '11',
                'sort' => 12,
            ),
        ),
    ),
    //厦门银行非标
    'XMYH' => array(
        'node_id' => '00026652',
        'm_id' => array('12216', '12596', '12604', '12622', '12670', '12672'),
    ),
    //北京光平非标
    'GpEye' => array(
        'node_id' => '00038586',
        'roles' => array('203' => '门店技师', '202' => '门店管理员'),
        'store_admin_role_id' => 202,
        'store_user_role_id' => 203,
    ),
    'NEW_POSTER_VISITER' => array(
        'node_id' => '00039573',
        'email' => 'xiaolu_new67@sina.com',
        'password' => '111111',
    ),
    // 'NEW_VISUALCODE_VISITER' => array(
    //     'node_id' => '00040133',
    //     'email' => 'xiaolu_new68@sina.com',
    //     'password' => '111111'
    // ),
    'NEW_VISUALCODE_VISITER' => array(
        'node_id' => '00039573',
        'email' => 'xiaolu_new67@sina.com',
        'password' => '111111',
    ),
    //深圳平安母亲节定制
    'szpa' => array(
        'node_id' => '00032513',
        'mamaBatchId' => array('14130'),
        'ecupLotteryBatchId' => 123,
        'mamaBatchGoto' => 'http://www.wangcaio2o.com/index.php?&g=Label&m=Bm&a=index&id=167775&wechat_card_js=1',
        'matchguess_group_id' => '607', //欧洲杯粉丝分组ID
    ),
    //旺分销 会员等级(美惠非标)
    'wfx_level' => array(
        '2' => '钻石会员',
        '3' => '金牌会员',
        '4' => '银牌会员',
    ),
    'gssy' => array('batch_id' => '14485'),
    // 'fjjh'=>array('node_id'=>'00032513'),
    'csbank' => array(
        'node_id' => '00039673',
    ),
    'fjjh' => ['node_id' => '00023332'],
    //微信菜单添加自定义事件
    'WEIXIN_MENU_EVENTS' => [
        'list' => [ //事件列表
            'myqrcode' => [ //对应的事件
                'response_class' => 8, //事件类型
                'name' => '我的二维码', //事件名称
                'background' => INDEX_PATH.'/Home/Public/Label/Image/Lnsy/qrcodebg.jpg'//背景图
            ],
        ],
        '00034974' => ['myqrcode'], //所持有的事件功能
    ],
    'lnsy' => ['node_id' => '00034974'],
    /*商旅文非标配置*/
    'slw' => array(
        'token' => 'abc',
        'api_ip' => '192.168.0.1',
        'unionpay_token' => 'abc',
        'unionpay_api_ip' => '192.168.0.1',
    ),
    'zggk' => array(
            'node_id' => '00041473',
            'domain' => array('zggk.wangcaio2o.com',),
    ),
);
