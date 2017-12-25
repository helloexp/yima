<?php
return array(
    // 渠道分类
    'AP_CHANNEL_TYPE_ARR' => array(
        '3' => array(
            '31' => '平面媒体', 
            '32' => '线下门店', 
            '33' => '电视媒体', 
            '34' => '其他', 
            '35' => '实体门店')), 
    'AP_ISS_SEND_ID' => '00006786', 
    'AP_ISS_PLATFORM_ID' => '7017', 
    'AP_ISS_MAC_KEYS' => 'wangcai', 
    'NODE_PUBLISH_TYPE' => array(
        'v0', 
        'v0.5'),  // 不能发布展示大厅商户类型
    'NUMGOODS_NODE' => array(
        '00021275'),  // 非标 广东太古可口可乐有限公司没有门店也能创建营销品
                                              // 支付宝分润配置参数
    'FEN_RUN_ALIPAY' => array(
        'Alipay_Partner' => '2088801298644068',  // 合作身份者ID，以2088开头的16位纯数字
        'Alipay_Key' => 'zs0bcqy1qeqe2mz2eyq9tlnpmuv3v0e7',  // 安全检验码，以数字和字母组成的32位字符
        'Alipay_Seller_Email' => 'taobao@imageco.com.cn',  // 签约支付宝账号或卖家支付宝帐户
        'Alipay_default_rate' => '0.02',  // 支付宝手续费比例
                                         // 以下内容不需要修改,固定参数
        'Alipay_Service1' => 'alipay.wap.trade.create.direct',  // 接口1
        'Alipay_Service2' => 'alipay.wap.auth.authAndExecute',  // 接口2
        'Alipay_input_charset' => 'utf-8',  // 字符编码格式
        'Alipay_Security' => 'MD5',  // 签名方式 不需修改
        'Alipay_Format' => 'xml',  // http传输格式
        'Alipay_version' => '2.0',  // 版本号
        'Alipay_Pay_Expire' => '30'),  // 交易自动关闭时间,单位:分钟
    'normalUseExpress' => array(
        '顺丰速递', 
        '申通', 
        '圆通速递', 
        '韵达快运', 
        '天天快递'), 
    'PUBLISH_SEND_MAIL' => '',  // 卡券发布发送邮箱
    );


