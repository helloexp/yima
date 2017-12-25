<?php
return array(
    // 默认错误跳转对应的模板文件
    'TMPL_ACTION_ERROR' => './Home/Tpl/Label/Public_error.html', 
    // 默认成功跳转对应的模板文件
    'TMPL_ACTION_SUCCESS' => './Home/Tpl/Label/Public_msg.html', 
    // 拍码配置
    'CHANNEL_TYPE' => array(
        '1' => '网络渠道', 
        '2' => '平面渠道', 
        '3' => '线下渠道'), 
    
    // 二维码logo大小
    'SIZE_ARR' => array(
        '1' => '默认像素', 
        '2' => '200像素*200像素', 
        '3' => '600像素*600像素', 
        '4' => '800像素*800像素', 
        '5' => '1024像素*1024像素'), 
    'SIZE_TYPE_ARR' => array(
        '1' => '160', 
        '2' => '200', 
        '3' => '600', 
        '4' => '800', 
        '5' => '1024'), 
    // sns类型
    'SNS_ARR' => array(
        '1' => '新浪微博', 
        '2' => '腾讯微博', 
        '3' => 'QQ空间', 
        '4' => '人人网'), 
    'BM_TYPE_ARR' => array(
        '1' => '姓名', 
        '2' => '手机号', 
        '3' => '性别', 
        '4' => '年龄', 
        '5' => '学历', 
        '6' => '收信地址', 
        '7' => '邮箱', 
        '8' => '公司名称', 
        '9' => '职位', 
        '10' => '自定义一', 
        '11' => '自定义二', 
        '12' => '自定义三', 
        '13' => '图片上传'),
    'UPLOAD' => APP_PATH . 'Upload/',
    // 支撑配置参数
    'ZC_SEND_ARR' => array(
        'url' => 'https://222.44.51.34:18443/iss2.do', 
        'pass' => 'wangcai', 
        'system_id' => '7017'), 
    // 奖品池配置参数
    'JPC_SEND_ARR' => array(
        'url' => 'http://222.44.51.34:9210/ppool.do', 
        'channel_id' => '0011', 
        'key' => '9751bf6b3805526415c7e8246abd81ad'), 
    // 旺财配置参数
    'WC_SEND_ARR' => array(
        'url' => 'http://test.wangcaio2o.com/AppServ/index.php?a=CodeSendForLable'), 
    
    // 支付宝配置参数
    'ALIPAY' => array(
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
    
    'BATCH_MAMA' => array(
        'DES_KEY' => '20140428143110'), 
    'CUSTOM_LOG_PATH' => '/www/php_log/label_', 
    'WG_CUT_NODE_ID' => array(
        '00026652'))?> // 吴刚砍树非标商户

