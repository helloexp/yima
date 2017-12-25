<?php

/**
 * Class CommonConst
 */
class CommonConst
{

    const SNS_TYPE_SINA = 1; // 1新浪
    const SNS_TYPE_TENCENT = 2;    // 2腾讯
    const SNS_TYPE_QQZONE = 3;    // 3QQ空间
    const SNS_TYPE_RENREN = 4;    // 4人人网
    const SNS_TYPE_KAIXIN = 5;    // 5开心网
    const SNS_TYPE_DOUBAN = 6;    // 6豆瓣
    const SNS_TYPE_NETEASE = 7;    // 7网易
    const SNS_TYPE_SOHU = 8;    // 8搜狐
    const SNS_TYPE_WECHAT = 9;    // 9微信
    const SNS_TYPE_OTHER = 10;    // 10其他
    const SNS_TYPE_QIYEWEB = 11;    // 11企业官网
    const SNS_TYPE_O2O = 12;    // 12 O2O社区'
    const SNS_TYPE_HOME = 13;    // 13首页渠道
    const SNS_TYPE_DM = 21;    // 21DM单
    const SNS_TYPE_POSTER = 22;    // 22海报
    const SNS_TYPE_PACKAGE = 23;    // 23产品包装
    const SNS_TYPE_QIYECARD = 24;    // 24企业名片
    const SNS_TYPE_DESKCARD = 25;    // 25桌(台)卡
    const SNS_TYPE_26OTHER = 26;    // 26其他
    const SNS_TYPE_PRINTMEDIA = 31;    // 31平面媒体
    const SNS_TYPE_TV = 33;    // 33电视媒体
    const SNS_TYPE_34OTHER = 34;    // 34其他
    const SNS_TYPE_FACADE = 35;    // 35实体门店
    const SNS_TYPE_41WECHAT = 41;    // 41微信渠道
    const SNS_TYPE_LIST = 42;    // 42列表|43微官网|44团购列表|45商品销售列表|51员工渠道|52百度直达号渠道|53全民营销渠道|54电商推广员|56红包渠道|61预览渠道| 81微信场景码
    const SNS_TYPE_WEIHOME = 43;    // 43微官网
    const SNS_TYPE_TUANLIST = 44;    // 44团购列表
    const SNS_TYPE_SALELIST = 45;    // 45商品销售列表|51员工渠道|52百度直达号渠道|53全民营销渠道|54电商推广员|56红包渠道|61预览渠道| 81微信场景码
    const SNS_TYPE_EMPLOYEE = 51;    // 51员工渠道
    const SNS_TYPE_BAIDUNOSTOP = 52;    // 52百度直达号渠道
    const SNS_TYPE_ALLSALE = 53;    // 53全民营销渠道
    const SNS_TYPE_EMALLEXTEND = 54;    // 54电商推广员
    const SNS_TYPE_HONGBAO = 56;    // 56红包渠道
    const SNS_TYPE_WECHATSIGHT = 81;    // 81微信场景码
    const SNS_TYPE_PREVIEW = 61;    // 61预览渠道
    const SNS_TYPE_YHB = 412;    // 412翼惠宝
    const SNS_TYPE_INTEGRAL = 413;    // 积分商城专用
    const SNS_TYPE_MEMSTORENAV = 510;    // 门店导航-会员中心默认渠道
    const CHANNEL_TYPE_INTEGRAL = 5;    // 积分商城专用
    const CHANNEL_TYPE_HIGH_LEVEL = '4';    // 高级渠道

    // 参数抽奖类型
    const JOIN_VIA_MOBILE = 0;    // 抽奖参与方式（手机号）
    const JOIN_VIA_WECHAT = 1;    // 抽奖参与方式（微信号）
    const BATCH_TYPE_LIST = 8;    // 列表模板
    const BATCH_TYPE_RICH_MEDIA = 19;    // 图文编辑
    const BATCH_TYPE_GOODSSALE = 26;    // 闪购
    const BATCH_TYPE_MASHANGMAI = 27;    // 马上买
    const BATCH_TYPE_NEWSTORE = 31;    //新小店商品
    const BATCH_TYPE_QIXI = 28;    // 七夕节营销活动
    const BATCH_TYPE_STORE = 29;    // 小店
    const BATCH_TYPE_POSTER = 37;    // 电子海报
    const BATCH_TYPE_FIRECRACKER = 42;    // 春节打炮
    const BATCH_TYPE_LABORDAY = 45;    // 劳动节
    const BATCH_TYPE_MAMAWOAINI = 46;    // 妈妈我爱你
    const BATCH_TYPE_ZONGZI = 50;    // 端午节
    const BATCH_TYPE_RECRUIT = 52;    // 会员招募活动
    const BATCH_TYPE_WEEL = 53;    // 大转盘活动
    const BATCH_TYPE_ZQCUT = 55;    // 吴刚砍树
    const BATCH_TYPE_RAISEFLAG = 56;    //新版海报
    const BATCH_TYPE_NEWPOSTER = 58;    // 国庆升旗活动
    const BATCH_TYPE_TWO_VESTIVAL = 59;    // 双旦祝福
    const BATCH_TYPE_STORE_LBS = 17;    // 门店导航
    const BATCH_TYPE_SPRINGMONKEY = 60;    // 金猴闹春
    const BATCH_TYPE_EUROCUP = 61;    // 决战欧陆之巅（欧洲杯赛事竞猜）
    const BATCH_TYPE_GOODS = 1009;    // 积分商城商品
    const BATCH_TYPE_INTEGRAL = 2001;    // 积分商城
    const BATCH_TYPE_TEMPLATE = 62;              //新模板

    // 草稿类型
    const DRAFT_TYPE_LIST = '3';
    // 列表模板的草稿

    // 商品类型
    const GOODS_TYPE_YHQ = 0;    // 优惠券
    const GOODS_TYPE_DJQ = 1;    // 代金券
    const GOODS_TYPE_TLQ = 2;    // 提领券
    const GOODS_TYPE_ZKQ = 3;    // 折扣券
    const GOODS_TYPE_HF = 7;    // 话费
    const GOODS_TYPE_QB = 8;    // Q币
    const GOODS_TYPE_HGDSDZQ = 11;    // 哈根达斯卡券
    const GOODS_TYPE_HB = 12;    // 红包
    const GOODS_TYPE_JF = 14;    // 积分

    const GOODS_TYPE_LLB = 15;    //流量包

    const INTEGRAL_GOODS_TYPE = 21;    // 积分
    const WECHAT_HONGBAO_GOODS_TYPE = 22;    // 积分商城

    // 卡券来源
    const GOODS_SOURCE_SELF_CREATE = 0;    // 自建
    const GOODS_SOURCE_BUY = 1;    // 采购
    const GOODS_SOURCE_DISTRIBUTION = 4;    // 分销

    const GOODS_SOURCE_SELF_CREATE_WXHB = 6;//自建微信红包
    const GOODS_SOURCE_YIMA_CREATE_WXHB = 7;//翼码代发的微信红包

    // pos_type
    const POS_TYPE_EPOS = 2;    // pos为epos分类

    // pos的付费种类
    const POS_PAY_TYPE_PAID = 0;    // 付费的pos
    const POS_PAY_TYPE_FREE = 2;    // pos的付费种类为免费（且不是仅有条码支付业务的免费epos）

    // 和选择奖品的下拉框一致，tactivity_order_coupon_detail表的type字段
    const COUPON_TYPE_SELFCREATE = '1';    // 自建
    const COUPON_TYPE_BUY = '2';    // 采购
    const COUPON_TYPE_HB = '3';    // 红包
    const COUPON_TYPE_WX_CARD = '4';    // 微信卡券

    // 订单类型
    const ORDER_TYPE_WHEEL_NORMAL = '1';    // 大转盘普通订单(可能开通活动现在都用这个了)
    const ORDER_TYPE_FREE = '2';    // 大转盘免费订单
    const ORDER_TYPE_APPLY_POS = '3';    // 申请终端
    const ORDER_TYPE_FREE_VALIDATE = '4';    // 卡券验证助手
    const ORDER_TYPE_ONLINE_TREATY = '5';    // 在线签约
    const ORDER_TYPE_DM = '6';    // 多米收单在线签约

    // 注册来源
    const REG_FROM_APP = '7';    // 从手机APP注册
    // 积分流水扣减增加type
    const INTEGRAL_TYPE1 = '1';    // 小店购买商品获得积分
    const INTEGRAL_TYPE2 = '2';    // 积分商城兑换
    const INTEGRAL_TYPE3 = '3';    // 手动减少积分
    const INTEGRAL_TYPE4 = '4';    // 手动增加积分
    const INTEGRAL_TYPE5 = '5';    // wap端签到
    const INTEGRAL_TYPE6 = '6';    // 批量增加积分
    const INTEGRAL_TYPE7 = '7';    // 批量减少积分
    const INTEGRAL_TYPE8 = '8';    // 终端增加积分
    const INTEGRAL_TYPE9 = '9';    // 会员绑定积分合并流水
    const INTEGRAL_TYPE10 = '10';    // 线下微信支付增加积分
    const INTEGRAL_TYPE11 = '11';    // 终端积分兑换
    const INTEGRAL_TYPE12 = '12';    // 多乐互动（手机号码增加积分）
    const INTEGRAL_TYPE13 = '13';    // 多乐互动（Openid增加积分）
    const INTEGRAL_TYPE14 = '14';    // 阅读营销
    const INTEGRAL_TYPE15 = '15';    // 微信关注
    const INTEGRAL_TYPE16 = '16';    // 暂时未用
    const INTEGRAL_TYPE17 = '17';    // 积分当钱花
    const INTEGRAL_TYPE18 = '18';    // 积分退还
    const INTEGRAL_TYPE19 = '19';    // 支付宝支付增加积分
    const SEND_PRIZE_TYPE_TEXT = '0';    // 短彩信
    const SEND_PRIZE_TYPE_WX_CARD = '1'; // 微信卡券

    const SOURCE_FROM_WECHAT = 6;//微信红包

    const SOURCE_FROM_IMAGECO_AGENT = 7;//翼码代发微信红包

    //营帐传过来的chargeid
    const CHARGE_ID_EPOS = '28';//epos的chargeId

    const CHARGE_ID_E6800 = '9';//E6800的chargeId

    const CHARGE_ID_ZIYONG = '2002';//自建卡券的发码费的chargeId

    const CHARGE_ID_WEIXIN = '64';//微信卡券的发码费的chargeId

    const CHARGE_ID_YIYE = '63';//异业的发码费的chargeId

    const CHARGE_ID_ZIYONG_DM = '2103';//自建卡券的发码费(开通多米收单的)chargeId

    const CHARGE_ID_WEIXIN_DM = '2104';//微信卡券的发码费(开通多米收单的)chargeId

    //新的channel的type的值
    const CHANNEL_TYPE_MY_CHANNEL = 'a';//我的渠道
    const CHANNEL_TYPE_CODE_LABEL_CHANNEL = 'b';//二维码标签渠道
    
    //goods_storage_trace表的opt_type
    const GOODS_STORAGE_TRACE_OPT_TYPE_FOR_BATCH_BACK = '20';//活动的奖品的回退记录类型
    
    // 微信菜单之我的二维码事件的KEY
    const WEIXIN_MENU_MYQRCODE_KEY="MYQRCOE_";
    const CHANNEL_TYPE_EPOS_ID = 672587;//我的渠道
}