<?php

//活动,渠道配置
return array(
//渠道类型
    'CHANNEL_TYPE'          => array(
        '1' => 'sns渠道',
        '2' => '二维码标签渠道',
        '3' => '爱拍渠道',
        '4' => '高级渠道',
        '5' => '其他高级渠道',
        '11' => '自定义渠道',
        '12' => '社交渠道'
    ),
    'CHANNEL_TPL'           => APP_PATH . 'Upload/staff/',
    //渠道分类
    'CHANNEL_TYPE_ARR'      => array(
        '1' => array('1' => '新浪', '2' => '腾讯', '3' => 'QQ空间', '4' => '人人网', '5' => '开心网', '6' => '豆瓣', '7' => '网易', '8' => '搜狐', '9' => '微信', '10' => '其他', '11' => '企业官网', '12' => 'O2O案例','101'=>'手机wap大转盘专用渠道','102'=>'旺分销群发消息专用渠道'),
        '2' => array('21' => 'DM单', '22' => '海报', '23' => '产品包装', '24' => '企业名片', '25' => '桌(台)卡', '26' => '其他', '27' => '旺水牌'),
        '3' => array('31' => '平面媒体', '33' => '电视媒体', '34' => '其他', '35' => '实体门店'),
        '4' => array('41' => '微信渠道', '42' => '列表', '43' => '微官网', '44' => '团购列表', '45' => '闪购列表', '46' => '旺财小店列表', '47' => '码上买列表', '48' => '海报列表', '49' => '微信投放', '411' => '平安默认渠道','412'=>'图文编辑'),
        '5' => array('51' => '员工渠道', '52' => '百度直达号渠道', '53' => '全民营销渠道', '54' => '京东团购','55'=>'DF积分商城渠道','56'=>'DF门店首页渠道','58'=>'定额红包引流渠道','59'=>'随机红包引流渠道', '510'=>'会员中心默认渠道'),
        '6' => array('61'=>'预览渠道','62'=>'旺财App','63'=>'多乐互动专用渠道'),
    ),
    //活动wap跳转地址
    'BATCH_WAP_URL'         => array(
        '0'  => 'Label/OutLink/index',
        '2'  => 'Label/News/index',
        '3'  => 'Label/Bm/index',
        '4'  => 'Label/MemberRecruit/index',//'Label/MemberRegistration/index',
        '6'  => 'Label/GroupBuy/index',
        '7'  => 'Label/NxMobile/index',
        '8'  => 'Label/ListBatch/index',
        '9'  => 'Label/Coupon/index',
        '10' => 'Label/Answers/index',
        '11' => 'Label/Special/index',
        '12' => 'Label/Valentine/index',
        '13' => 'Label/MicroWeb/index',
        '15' => 'Label/Women/index',
        '16' => 'Label/LogoGuess/index',
        '17' => 'Label/ListShop/index',
        '18' => 'Label/Mama/index',
        '19' => 'Label/Med/index',
        '20' => 'Label/Vote/index',
        '21' => 'Label/Vip/login',
        //'22' => 'ZtWorldcup/LabelMatchGuess/index',
        '23' => 'ZtWorldcup/LabelDakaHasGift/index',
        '24' => 'ZtWorldcup/LabelGoalHasGift/index',
        '25' => 'ZtWorldcup/LabelChampionGuess/index',
        '26' => 'Label/GoodsSale/index',
        '27' => 'Label/MaShangMai/index',
        '28' => 'Label/Qixi/index',
        '29' => 'Label/Store/index',
        '30' => 'Label/ZhongQiu/index',
        '31' => 'Label/Store/detail',
        '32' => 'Label/Registration/index',
        '33' => 'ReturnCommission/Index/index',
        '34' => 'Label/ShoppingPolite/index',
        '35' => 'Label/SnowBall/index',
        '36' => 'Label/Spelling/index',
        '37' => 'Label/Poster/index',
        //'38' => 'Label/PinganGoods/index',
        //'39' => 'Label/PinganExgratia/index',
        '40' => 'Label/WeixinCard/index',
        '41' => 'Label/Bonus/index',
        '42' => 'Label/Spring2015/index',
        '44' => 'Label/Dawan/index',
        '45' => 'Label/LaborDay/index',
        '46' => 'Label/MamaSjb/index',
        '49' => 'Label/DuanWu/index',
        '50' => 'Label/ZongZi/index',
        '51' => 'Label/Yunhuiwu/index',
        '52' => 'Label/MemberRecruit/index',//新会员招募活动
        '53' => 'DrawLottery/SpinTurnplate/index',//抽奖，大转盘
        '54' => 'Label/PaySendActivity/index',//付满送活动
		'55' => 'Label/ZqCut/index',
        '56' => 'Label/NationDay/index',
        '58' => 'Label/NewPoster/index', //新版海报
        '59' => 'Label/TwoFestival/index',
        '60' => 'Label/SpringMonkey/index',
        '61' => 'ZtWorldcup/LabelMatchGuess/index', //决战欧陆之巅
        '62' => 'Label/NewPictext/index', //新图文排版
		'1001' => 'Df/DFStore/index',
        '1002' => 'Df/DFStore/detail',
        '1003' => 'Label/DfStoreWeb/index',
        '1004' => 'Label/DfBm/index',
        '1009' => 'Label/IntegralShop/detail',
        '2001' => 'Label/IntegralShop/index',
        '3001' => 'Label/Wfx/activity', // 旺分销-招募分享连接
        '1026' => 'Label/GoodsSale/indexNew',   //新版电商推广页面
        '1027' => 'Label/MaShangMai/indexNew'   //新版电商推广页面

    ),
    //活动名称
    'BATCH_TYPE_NAME'       => array(
        '2'    => '抽奖',
        '3'    => '市场调研',
        '4'    => '粉丝招募',
        '6'    => '团购列表',
        '7'    => '宁夏移动',
        '8'    => '列表模板',
        '9'    => '卡券领取',
        '10'   => '有奖答题',
        '11'   => '码上有红包',
        '12'   => '天生一对',
        '13'   => '微官网',
        '14'   => '礼品派发',
        '15'   => '女人我最大',
        '16'   => '真假大冒险',
        '17'   => '门店导航',
        '18'   => '母亲节',
        '19'   => '图文编辑',
        '20'   => '投票',
        '21'   => 'Vip活动',
        '22'   => '赛事竞猜',
        '23'   => '签到有礼',
        '24'   => '进球有礼',
        '25'   => '冠军竞猜',
        '26'   => '闪购',
        '27'   => '码上买',
        '28'   => '七夕节',
        '29'   => '旺财小店',
        '30'   => '中秋节',
        '31'   => '小店商品',
        '32'   => '注册有礼',
        '33'   => '全民营销',
        '34'   => '购物有礼',
        '35'   => '圣诞节',
        '36'   => '爱拼才会赢',
        '37'   => '电子海报',
        '38'   => '商城商品',
        '39'   => '商城优惠',
        '40'   => '微信卡券投放',
        '41'   => '人品红包',
        '42'   => '打炮总动员',
        '43'   => '京东团购',
        '44'   => '谁是大腕',
        '45'   => '劳动最光荣',
        '46'   => '妈妈我爱你',
        '47'   => '定额红包',
        '49'   => '端午节',
        '50'   => '粽礼寻Ta',
        '51'=>'云会务',
        '52'   => '会员招募',
        '53'   => '幸运大转盘',
    	'54'=>'付满送',
    	'55'   => '吴刚砍树',
        '56'   => '我是升旗手',
        '58' => '电子海报',
        '59' => '双旦祝福',
    	'60'   => '金猴闹新春',
        '61'   => '决战欧陆之巅',
        '62'   => '新图文排版',
        '1001' => 'DF积分商城',// 1开头的为非标活动
        '1002' => 'DF积分商城商品',
        '1003' => 'DF门店首页',
        '1004' => 'DF会员信息收集',
        '1006' => '发布到APP',
        '1007' => '发布到个人',
    	'2002' => '工行融e购',
        '3001' => '旺分销-招募活动',//3开头的为模块单独活动，不属于营销活动
        '3002' => '微信公众号-发红包',
    ),
    //活动图片文件路径名Home/Upload/..
    'BATCH_IMG_PATH_NAME'   => array(
        '2'   => 'UploadNewsBatch',
        '3'   => 'UploadBmBatch',
        '4'   => 'UploadMemberBatch',
        '6'   => 'UploadGroupBatch',
        '8'   => 'UploadListBatch',
        '9'   => 'UploadCouponBatch',
        '10'  => 'UploadAnswerBatch',
        '11'  => 'UploadSpecialBatch',
        '12'  => 'UploadValentineBatch',
        '15'  => 'UploadWomenBatch',
        '16'  => 'UploadLogoGuessBatch',
        '18'  => 'UploadMamaBatch',
        '19'  => 'UploadMedBatch',
        '20'  => 'UploadVoteBatch',
        '21'  => 'UploadVipBatch',
        '24'  => 'UploadGoalHasGift',
        '22'  => 'UploadMatchGuess',
        '23'  => 'UploadDakaHasGift',
        '25'  => 'UploadChampionGuess',
        '26'  => 'UploadGoodsSale',
        '27'  => 'UploadMaShangMaiBatch',
        '28'  => 'UploadQixiBatch',
        '29'  => 'UploadEcshopBatch',
        '30'  => 'UploadZhongQiuBatch',
        '31'  => 'UploadShopGoodsBatch',
        '32'  => 'UploadRegistrationBatch',
        '34'  => 'UploadShoppingPoliteBatch',
        '35'  => 'UploadSnowBallBatch',
        '36'  => 'UploadSpellingBatch',
        '37'  => 'UploadPosterBatch',
        //'38'  => 'UploadPinganBatch',
        //'39'  => 'UploadPinganBatch',
        '41'  => 'UploadSaleProBatch',
        '42'  => 'UploadSpring2015Batch',
        '45'  => 'UploadSpring2015Batch',
        '46'  => 'UploadMamaSjbBatch',
        '49'  => 'UploadDuanWuBatch',
        '58'  => 'UploadPosterBatch',
        '999' => 'UploadOthers'
    ),
    //O2O案例活动默认图片
    'O2O_DEFULT_IMG'        => array(
        '2'  => 'wei_chou_jiang', //抽奖
        '3'  => 'shi_chang_diao_yan', //市场调研
        '4'  => 'fen_si_zhao_mu', //粉丝招募
        '6'  => 'batch_defult', //团购列表
        '7'  => 'batch_defult', //宁夏移动
        '8'  => 'zu_he_ying_xiao', //列表模板
        '9'  => 'you_hui_quan', //卡券领取
        '10' => 'you_jiang_da_ti', //有奖答题
        '11' => 'ma_shang_hong_bao', //码上有红包
        '12' => 'tian_sheng_yi_dui', //天生一对
        '13' => 'wei_guan_wang', //微官网
        '14' => 'li_pin_pai_fa', //礼品派发
        '15' => 'nv_ren_zui_da', //女人我最大
        '16' => 'zhen_jia_mao_xian', //真假大冒险
        '17' => 'batch_defult', //门店导航
        '18' => 'ma_ma_ai_ni', //妈妈我爱你
        '19' => 'batch_defult',
        '20' => 'batch_defult',
        '21' => 'batch_defult',
        '24' => 'batch_defult',
        '22' => 'batch_defult',
        '23' => 'batch_defult',
        '25' => 'batch_defult',
        '26' => 'batch_defult',
        '27' => 'batch_defult',
        '28' => 'qi_xi',
        '29' => 'batch_defult',
        '30' => 'batch_defult',
        '31' => 'batch_defult',
        '32' => 'batch_defult',
        '36' => 'apchy'
    ),
    'BATCH_ACTION_NAME'     => array(
            'News'               => '抽奖',
            'Bm'                 => '市场调研',
            'MemberRegistration' => '粉丝招募',
            'List'               => '列表模板',
            'Coupon'             => '卡券领取',
            'Answers'            => '有奖答题',
            'Special'            => '码上有红包',
            'Valentine'          => '天生一对',
            'MicroWeb'           => '微官网',
            'Feedback'           => '礼品派发',
            'Women'              => '女人我最大',
            'LogoGuess'          => '真假大冒险',
            'Med'                => '图文编辑',
            'Mama'               => '母亲节',
            'Vote'               => '投票',
            'ChampionGuess'      => '冠军竞猜',
            'GoodsSale'          => '商品管理',
            'MaShangMai'         => '码上买',
            'Qixi'               => '七夕节',
            'ZhongQiu'           => '中秋节',
            'Registration'       => '注册有礼',
            'ShoppingPolite'     => '购物有礼',
            'SnowBall'           => '圣诞节',
            'Spelling'           => '爱拼才会赢',
            'Spring2015'         => '打炮总动员',
            'Dawan'              => '谁是大腕',
            'LaborDay'           => '劳动最光荣',
            'MamaSjb'            => '妈妈我爱你',
            'DuanWu'             => '端午节',
            'ZongZi'             => '粽礼寻Ta',
            'Yunhuiwu'            => '云会务',
            'MemberRecruit'      => '会员招募',
            'SpinTurnplate'      => '大转盘抽奖',
            'ZqCut'              => '吴刚砍树',
            'NewPoster'          => '新版海报',
            'SpringMonkey'       => '金猴闹新春'
    ),
    //活动创建连接
    'BATCH_CREATE_URL'      => array(
        '2'  => 'index.php?g=LabelAdmin&m=News&a=add&model=event&type=draw&action=create&customer=c2',
        '3'  => 'index.php?g=LabelAdmin&m=Bm&a=add&model=event&type=survey&action=create&customer=c2',
        '4'  => 'index.php?g=LabelAdmin&m=MemberRegistration&a=add&model=event&type=recruiting&action=create&customer=c2',
        '8'  => 'index.php?g=LabelAdmin&m=List&a=index&model=event&type=combination&customer=c2',
        '9'  => 'index.php?g=LabelAdmin&m=Coupon&a=add&model=event&type=coupon&action=create&customer=c2',
        '10' => 'index.php?g=LabelAdmin&m=Answers&a=add&model=event&type=question&action=create&customer=c2',
        '11' => 'index.php?g=LabelAdmin&m=Special&a=add&model=event&type=envelope&action=create&customer=c2',
        '12' => 'index.php?g=LabelAdmin&m=Valentine&a=add&model=event&type=valentine&action=create&customer=c2',
        '13' => 'index.php?g=MicroWeb&m=Index&a=index',
        '15' => 'index.php?g=LabelAdmin&m=Women&a=add&model=event&type==38theme&action=create&customer=c2',
        '16' => 'index.php?g=LabelAdmin&m=LogoGuess&a=add&model=event&type=315theme&action=create&customer=c2',
        '17' => 'Label/ListShop/index',
        '18' => 'index.php?g=LabelAdmin&m=Mama&a=add&model=event&type=draw&action=create&customer=c2',
        '19' => 'index.php?g=LabelAdmin&m=Med&a=add&model=event&type=question&action=create&customer=c2',
        '20' => 'index.php?g=LabelAdmin&m=Vote&a=add&model=event&type=survey&action=create&customer=c2',
        '22' => 'ZtWorldcup/LabelMatchGuess/index',
        '23' => 'ZtWorldcup/LabelDakaHasGift/index',
        '24' => 'ZtWorldcup/LabelGoalHasGift/index',
        '25' => 'ZtWorldcup/LabelChampionGuess/index',
        '26' => 'index.php?g=Ecshop&m=GoodsSale&a=add',
        '27' => 'index.php?g=Ecshop&m=MaShangMai&a=add',
        '28' => 'index.php?g=LabelAdmin&m=Qixi&a=add&model=event&type=Qixi&action=create&customer=c2',
        '29' => 'index.php?g=Ecshop&m=Index&a=index',
        '30' => 'index.php?g=LabelAdmin&m=ZhongQiu&a=add&model=event&type=ZhongQiu&action=create&customer=c2',
        '31' => 'index.php?g=Ecshop&m=Index&a=index',
        '32' => 'index.php?g=LabelAdmin&m=Registration&a=add&model=event&type=registration&customer=c2',
    ),
    //营销活动列表界面
    'BATCH_LIST_URL'        => array(
        '2'  => 'LabelAdmin/News/index',
        '3'  => 'LabelAdmin/Bm/index',
        '10' => 'LabelAdmin/Answers/index',
        '20' => 'LabelAdmin/Vote/index',
        '50' => 'LabelAdmin/ZongZi/index',
        '26' => 'Ecshop/O2OHot/index?batch_type=26',
        '27' => 'Ecshop/O2OHot/index?batch_type=27',
    ),
    //营销活动编辑界面
    'BATCH_EDIT_URL'        => array(
        '2'  => 'LabelAdmin/News/edit',
        '3'  => 'LabelAdmin/Bm/edit',
        '10' => 'LabelAdmin/Answers/edit',
        '20' => 'LabelAdmin/Vote/edit',
        '26' => 'Ecshop/O2OHot/edit?batch_type=26',
        '27' => 'Ecshop/O2OHot/edit?batch_type=27',
        '19' => 'LabelAdmin/Med/edit',
    ),
    //营销活动对应的模块名,key为batch_type,为活动导航栏准备的配置
    'ACTIVITY_MODULE' => array(
        '8' => 'List',
    	'28' => 'Qixi',
    	'52' => 'Member',
    	'53' => 'DrawLotteryAdmin', 
    	'56' => 'RaiseFlag',
        '59' => 'TwoFestivalAdmin',
    	'60' => 'SpringMonkey',
    ),
    //特殊的营销活动，模块不在LabelAdmin里的
    'SP_ACTIVITY_GROUP_NAME' => array(
    	'52' => 'Wmember'
    ),
    //未购买打包营销工具的机构，需要单独购买一次活动的种类，53为大转盘，59为双旦祝福
    'PAY_ACTIVITY_TYPE_ID' => array(
    	'2', '56', '3', '44', '10', '20', '50', '12', '9', '11', '18', '28', '46', '45', '15', '53', '59', '60', '16', '61'
    ),
    //活动的默认收费配置
    'DEFAULT_PAY_ACTIVITY_TYPE_CONFIG' => array(
        //节庆模版类的收费模型
        '1' => array('56', '60', '44', '50', '45', '46', '11', '12', '15', '18', '28', '59', '16'),
        //常用模版
        '63' => array('53', '2', '3', '9', '10', '20'), 
    ),
    //欧洲杯活动单场次的收费模型
    'EUROCUP_ONE_MATCH_ACTIVITY_TYPE_CONFIG' => array(
        'duringTime' => '100', //随意写的，因为欧洲杯到7月30就结束了
        'basicPrice' => '199', 
        'exPrice' => '0', 
        'isBuySingleEuroGame' => '1'
    ),
    'EUROCUP_ALL_MATCH_ACTIVITY_TYPE_CONFIG' => array(
        'duringTime' => '100', //随意写的，因为欧洲杯到7月30就结束了
        'basicPrice' => '2980',
        'exPrice' => '0', 
        'isBuyAllEuroGame' => '1'//特意写个不一样的参数，其他配置不会有这个参数
    ),
    'O2O_BATCH_TYPE'        => array(
        '2' => '多宝电商',
        '1' => '节日营销',
        '6' => '常见营销',
        '4' => '世界杯营销',
        '5' => '微官网'
    ),
    'O2O_TYPE'              => array(
        'shop'    => '多宝电商',
        'holiday' => '节日营销',
        'general' => '常见营销',
        'cup'     => '世界杯营销',
        'fans'    => '粉丝营销',
        'site'    => '微官网'
    ),
    'O2O_CHILD_TYPE'        => array(
        "shop"    => array(
            array(
                'id'   => '29',
                'name' => '旺财小店'
            ),
            array(
                'id'   => '31',
                'name' => '小店商品'
            ),
            array(
                'id'   => '27',
                'is_new' => '1',
                'name' => '码上买'
            ),
            array(
                'id'   => '27',
                'is_new' => '2',
                'name' => '新品发售'
            ),
            array(
                'id'   => '26',
                'name' => '闪购'
            ),
            array(
            	'id'   => '55',
            	'name' => '吴刚砍树'
            )
        ),
        "holiday" => array(
            array(
                'id'   => '16',
                'name' => '真假大冒险'
            ),
            array(
                'id'   => '12',
                'name' => '天生一对'
            ),
            array(
                'id'   => '18',
                'name' => '真假大冒险'
            ),
            array(
                'id'   => '11',
                'name' => '码上有红包'
            ),
            array(
                'id'   => '15',
                'name' => '女人我最大'
            ),
            array(
                'id'   => '28',
                'name' => '七夕节'
            ),
            array(
                'id'   => '30',
                'name' => '中秋节'
            ),
            array(
                'id'   => '50',
                'name' => '天生一对'
            ),
        ),
        "general" => array(
            array(
                'id'   => '2',
                'name' => '抽奖'
            ),
            array(
                'id'   => '3',
                'name' => '市场调研'
            ),
            array(
                'id'   => '10',
                'name' => '有奖答题'
            ),
            array(
                'id'   => '20',
                'name' => '投票'
            ),
            array(
                'id'   => '9',
                'name' => '卡券领取'
            ),
            array(
                'id'   => '14',
                'name' => '礼品派发'
            ),
            array(
                'id'   => '8',
                'name' => '列表模板'
            ),
            array(
                'id'   => '36',
                'name' => '爱拼才会赢'
            )
        ),
        "cup"     => array(
            array(
                'id'   => '22',
                'name' => '赛事竞猜'
            ),
            array(
                'id'   => '23',
                'name' => '签到有礼'
            ),
            array(
                'id'   => '24',
                'name' => '进球有礼'
            ),
            array(
                'id'   => '25',
                'name' => '冠军竞猜'
            )
        ),
        "fans"    => array(
            array(
                'id'   => '4',
                'name' => '粉丝招募'
            )
        ),
        "site"    => array(
            array(
                'id'   => '13',
                'name' => '微官网'
            )
        ),
    ),
    //注册有礼活动商户使用列表(市场部商户)
    'REG_NODE_LIST'         => array(
        '00004488',
    ),
    //行业转换
    'NEW_INDUSTRY'          => array(
        "1"  => "运营商",
        //"2"  => "金融行业",
        "3"  => "互联网",
        "4"  => "媒体&艺术",
        "5"  => "物流行业",
        "6"  => "石油化工",
        "7"  => "餐饮",
        "8"  => "旅游住宿",
        "9"  => "零售百货",
        "10" => "休闲娱乐",
        "11" => "生活服务",
        "12" => "教育培训",
        "13" => "其他",
    ),
    'NEW_INDUSTRY_RELATION' => array(
        "1"  => "96,97,98",
        "2"  => "99,100,26",
        "3"  => "92,62",
        "4"  => "91,23,113",
        "5"  => "90,101",
        "6"  => "41,102,103",
        "7"  => "22,27,105,104",
        "8"  => "25,94,112",
        "9"  => "106,107,81",
        "10" => "82,109,108,61,114",
        "11" => "111,83,110,116,95",
        "12" => "115",
        "13" => "84,93,117,30",
    ),
    'GWYL_NODE'             => '00032513',
     //版本和营销活动发布设置
    'VERSION_BATCH_BIND_AUTH'=>array(
        '*'=>array(
            13, //微官网
            42, //打炮总动员
            45, //劳动最光荣,
            46, //妈妈我爱你
//            50, //劳动节
			55,
			59
        ),
        'v0'=>array(
            '37',//电子海报
            '19',//图文编辑
            '53',//大转盘
            '56'//国庆
        ),
        'v0.5'=>array(
            '37',//电子海报
            '17',//门店导航
            '19',//图文编辑
            '53',//大转盘
            '56'//国庆
        ),
    ),
    //交易大厅翼码代理卡券的商户,和后台hall.php中的$ym_node_id保持一致
    'YM_HALL_NODE_ID' => array('00023253','00023253'),
    //购买交易大厅翼码代理卡券的商户付款后通知邮箱
    'YM_HALL_SEND_MAIL' => 'zhongjb@imageco.com.cn',
    //营销活动的二维码地址
    'MARKET_CODE_PRE_URL'=>'http://10.10.1.134/wcadmin/upload',
    //大转盘充值旺币接口需要的reasonid
    'WB_FOR_WHEEL' => '1', //生产上31
    //我是升旗手充值旺币接口需要的reasonid
    'WB_FOR_RAISE_FLAG' => '1',//生产上33
    //欧洲杯竞猜活动第一次送的免费活动送的旺币的reasonid
    'WB_FOR_EUROP_CUP' => '1',//生产上37
    'WB_LIMIT_FOR_EUROP_CUP' => '20160730',//
    // 申请终端价格表
    'POS_PRICEINFO' => array(
        'EPOS_SERV_PRICE'       => 10,
        'ER6800_SERV_PRICE'     => 40,
        'ER6800_DEPOSIT_PRICE'  => 500,
        'ER6800_INSTALL_PRICE'  => 50,
        'ER1100_SERV_PRICE'     => 10,
        'ER1100_TERMINAL_PRICE' => 138,
        'GPRS_PRICE'            => 30,
        'DUOMI_SERV_PRICE'      => 30
        ),
    'wxsq_help_id' => '1551', 
    'wxsq_help_class_id' => '46',
	//活动奖品库存回退配置
	'PRIZE_STOREGE_BACK' => array(
		'BATCH_TYPE' => array('2','3','4','8','9','10','11','12','14','15','16','18','19','20','28','30','35','36','42','44','45','46','50','52','53','54','56','59','60', '61'),//能回退的活动类型
		'PRIZE_TYPE' => array('00','01','02','03','10','11','12','13','17','18','015','115'),//能回退的奖品类型 规则:tgoods_info 中的source和goods_type连接组成的数组
	), 
    'EURO_CUP_FREE_LIMIT_TIME' => '201606230500', 
    //审核用于抵扣活动订单的优惠券的配置
    'VERIFY_ACTIVITY_DISCOUNT' => [
        [
            //'node_id' => '00004488', 
            'node_id' => '00030914', 
            'batch_type' => '61', //欧洲杯
            //'pos_id' => '0001046067', 
            'pos_id' => '0001046167', 
            'm_id' => ['15223']
        ]
    ]
);
