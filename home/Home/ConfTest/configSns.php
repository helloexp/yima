<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi.cn@gmail.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------
// config.php 2013-02-25
define('CURRENT_HOST', 'http://test.wangcaio2o.com'); // 网站域名
                                               // 定义回调URL通用的URL
define('URL_CALLBACK', 
    CURRENT_HOST . '/index.php?g=LabelAdmin&m=Sns&a=sns_Callback&type=');

return array(
    // 腾讯QQ登录配置
    'THINK_SDK_QQ' => array(
        'APP_KEY' => '100485714',  // 应用注册成功后分配的 APP ID
        'APP_SECRET' => '6324b364297300cbcb1686a067b8762b',  // 应用注册成功后分配的KEY
        'CALLBACK' => URL_CALLBACK . 'qq'), 
    // 腾讯微博配置
    'THINK_SDK_TENCENT' => array(
        'APP_KEY' => '801386947',  // 应用注册成功后分配的 APP
                                                               // ID
        'APP_SECRET' => 'c0e51b30658e3f94ab8f092801c3a163',  // 应用注册成功后分配的KEY
        'CALLBACK' => URL_CALLBACK . 'tencent'), 
    // 新浪微博配置
    'THINK_SDK_SINA' => array(
        'APP_KEY' => '4294557489',  // 应用注册成功后分配的 APP
                                                             // ID
        'APP_SECRET' => '44a15d8275a371a9a47990b2dee1835f',  // 应用注册成功后分配的KEY
        'CALLBACK' => URL_CALLBACK . 'sina'), 
    // 网易微博配置
    'THINK_SDK_T163' => array(
        'APP_KEY' => 'e7b36Cbg3EgHnG41',  // 应用注册成功后分配的
                                                                   // APP
                                                                   // ID
        'APP_SECRET' => 'JGjbZkRLE954sxl3fjFgrXeBvdZmwpET',  // 应用注册成功后分配的KEY
        'CALLBACK' => URL_CALLBACK . 't163'), 
    // 人人网配置
    'THINK_SDK_RENREN' => array(
        'APP_KEY' => 'c4dd1723bd664bb3b951e69d15ad8764',  // 应用注册成功后分配的
                                                                                     // APP
                                                                                     // ID
        'APP_SECRET' => 'f349f439c11342309c10c2c8e49ffc9c',  // 应用注册成功后分配的KEY
        'AUTHORIZE' => 'scope=publish_share', 
        'CALLBACK' => URL_CALLBACK . 'renren'), 
    // 360配置
    'THINK_SDK_X360' => array(
        'APP_KEY' => '',  // 应用注册成功后分配的 APP ID
        'APP_SECRET' => '',  // 应用注册成功后分配的KEY
        'CALLBACK' => URL_CALLBACK . 'x360'), 
    // 豆瓣配置
    'THINK_SDK_DOUBAN' => array(
        'APP_KEY' => '0f0096335bf8e8b12d01c29a13eaba13',  // 应用注册成功后分配的
                                                                                     // APP
                                                                                     // ID
        'APP_SECRET' => '932a4adb083b7a93',  // 应用注册成功后分配的KEY
        'CALLBACK' => URL_CALLBACK . 'douban'), 
    // Github配置
    'THINK_SDK_GITHUB' => array(
        'APP_KEY' => '',  // 应用注册成功后分配的 APP ID
        'APP_SECRET' => '',  // 应用注册成功后分配的KEY
        'CALLBACK' => URL_CALLBACK . 'github'), 
    // Google配置
    'THINK_SDK_GOOGLE' => array(
        'APP_KEY' => '',  // 应用注册成功后分配的 APP ID
        'APP_SECRET' => '',  // 应用注册成功后分配的KEY
        'CALLBACK' => URL_CALLBACK . 'google'), 
    // MSN配置
    'THINK_SDK_MSN' => array(
        'APP_KEY' => '',  // 应用注册成功后分配的 APP ID
        'APP_SECRET' => '',  // 应用注册成功后分配的KEY
        'CALLBACK' => URL_CALLBACK . 'msn'), 
    // 点点配置
    'THINK_SDK_DIANDIAN' => array(
        'APP_KEY' => '',  // 应用注册成功后分配的 APP ID
        'APP_SECRET' => '',  // 应用注册成功后分配的KEY
        'CALLBACK' => URL_CALLBACK . 'diandian'), 
    // 淘宝网配置
    'THINK_SDK_TAOBAO' => array(
        'APP_KEY' => '',  // 应用注册成功后分配的 APP ID
        'APP_SECRET' => '',  // 应用注册成功后分配的KEY
        'CALLBACK' => URL_CALLBACK . 'taobao'), 
    // 百度配置
    'THINK_SDK_BAIDU' => array(
        'APP_KEY' => '',  // 应用注册成功后分配的 APP ID
        'APP_SECRET' => '',  // 应用注册成功后分配的KEY
        'CALLBACK' => URL_CALLBACK . 'baidu'), 
    // 开心网配置
    'THINK_SDK_KAIXIN' => array(
        'APP_KEY' => '70029048384652fa168d5db839a805de',  // 应用注册成功后分配的
                                                                                     // APP
                                                                                     // ID
        'APP_SECRET' => 'd98cf8bcc1930434ef8667639ade94de',  // 应用注册成功后分配的KEY
        'AUTHORIZE' => 'scope=create_records', 
        'CALLBACK' => URL_CALLBACK . 'kaixin'), 
    // 搜狐微博配置
    'THINK_SDK_SOHU' => array(
        'APP_KEY' => 'LEQc7fklzi92ajgCOT6S',  // 应用注册成功后分配的
                                                                       // APP ID
        'APP_SECRET' => 'T4o%7KnNd0gEBIbP4RwAga3^8nG*5bKC53#0qJyR',  // 应用注册成功后分配的KEY
        'AUTHORIZE' => 'scope=basic', 
        'CALLBACK' => URL_CALLBACK . 'sohu'), 
    
    // sns类型
    'SNS_ARR' => array(
        '1' => '新浪微博', 
        '2' => '腾讯微博', 
        '3' => 'QQ空间', 
        '4' => '人人网', 
        '5' => '开心网', 
        '6' => '豆瓣网', 
        '7' => '网易微博', 
        '8' => '搜狐微博'));