<?php
return array(
    // 支撑配置参数
    'ZC_SEND_ARR' => array(
        'url' => 'https://222.44.51.34:18443/iss2.do', 
        'pass' => '2413', 
        'system_id' => '1131'), 
    // 奖品池配置参数
    'JPC_SEND_ARR' => array(
        'url' => 'http://222.44.51.34:9210/ppool.do', 
        'channel_id' => '0001', 
        'key' => 'fb9b752be7fa34b2fed34bc97a93d96f'), 
    
    // 二维码logo大小
    'SIZE_ARR' => array(
        '1' => '默认像素', 
        '2' => '200像素*200像素', 
        '3' => '600像素*600像素', 
        '4' => '800像素*800像素', 
        '5' => '1024像素*1024像素'), 
    // sns类型
    'SNS_ARR' => array(
        '1' => '新浪微博', 
        '2' => '腾讯微博', 
        '3' => 'QQ空间', 
        '4' => '人人网', 
        '5' => '开心网', 
        '6' => '豆瓣网', 
        '7' => '网易微博', 
        '8' => '搜狐微博'), 
    'SNS_SDK' => array(
        '1' => 'SINA', 
        '2' => 'TENCENT', 
        '3' => 'QQ', 
        '4' => 'RENREN', 
        '5' => 'KAIXIN', 
        '6' => 'DOUBAN', 
        '7' => 'T163', 
        '8' => 'SOHU'), 
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
        '13' => '图片上传',
        '10' => '自定义一',
        '11' => '自定义二',
        '12' => '自定义三'),
    'SIZE_TYPE_ARR' => array(
        '1' => '160', 
        '2' => '200', 
        '3' => '600', 
        '4' => '800', 
        '5' => '1024'), 
    'UPLOAD_IMG' => array(
        'SIZE' => 1024 * 1024, 
        'TYPE' => 'jpg,gif,png,jpeg'), 
    'UPLOAD_AUDIO' => array(
        'SIZE' => 1024 * 1024, 
        'TYPE' => 'mp3'), 
    'SEX_ARR' => array(
        '0' => '男', 
        '1' => '女', 
        '2' => '其他'), 
    // 各大分享平台注册网址
    'SNS_URL' => array(
        '1' => 'http://weibo.com/', 
        '2' => 'http://t.qq.com/', 
        '3' => 'http://user.qzone.qq.com/', 
        '4' => 'renren.com', 
        '5' => 'kaixin001.com', 
        '6' => 'douban.com', 
        '7' => '', 
        '8' => ''), 
    // 爱拍赢大奖相关
    'upload_logo_dir' => './Home/Public/Image/res/', 
    'label_base_url' => 'http://222.44.51.34/gCenter/index.php?label_id=', 
    // 'label_dl_res_dir' => APP_PATH . 'Public/Image/res/',
    'label_dl_cfg' => array(
        'default' => array(
            'logo_file' => 'default_logo.png', 
            'bg_file' => 'default_bg.png', 
            'point_size' => 7, 
            'qr_position' => array(
                'x' => 385, 
                'y' => 320), 
            'qr_resize' => array(
                'x' => 21, 
                'y' => 21), 
            'word_position' => array(
                'x' => 380, 
                'y' => 655), 
            'font' => 'msyh.ttf', 
            'font2' => 'arial.ttf', 
            'font_size' => '24', 
            'font_color' => array(
                'r' => 183, 
                'g' => 116, 
                'b' => 67))), 
    
    // 市场调研-信息采集字段-允许选择“图片上传”的机构
    'DM_PICUPLOAD_NODEIDS' => array(
        '00006571', 
        '00011490', 
        '00023952', 
        '00004488'), 
    'SEND_EMAIL' => 'yangyang@imageco.com.cn', 
    
    'normalUseExpress' => array(
        '顺丰速递', 
        '申通', 
        '圆通速递', 
        '韵达快运', 
        '天天快递'))?>