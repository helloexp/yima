<?php
// 版本配置
$config = array();
// JS，css静态版本
// 计算绝对路径
$__BASE__ = 'http://' . $_SERVER['HTTP_HOST'];
//$__BASE__ = 'http://test.wangcaio2o.com';
if (dirname($_SERVER['SCRIPT_NAME']) != '\\' &&
     dirname($_SERVER['SCRIPT_NAME']) != '/') {
    $__BASE__ .= dirname($_SERVER['SCRIPT_NAME']);
}
$config['TMPL_PARSE_STRING'] = array_merge(C('TMPL_PARSE_STRING'), 
    array(
        '__VR__' => '20160421',
        '__PUBLIC__' => $__BASE__ . '/Home/Public', 
        '__HOST__' =>  $__BASE__,
        '__PUBLIC_LOCAL__' => $__BASE__ . '/Home/Public', 
        '__URL_UPLOAD__' => 'http://test.wangcaio2o.com/Home/Upload', 
        '__UPLOAD__' => 'http://test.wangcaio2o.com/Home/Upload'));

$config['SITE_TITLE'] = '旺财O2O营销平台 - 集工具、渠道、资源、管理于一体的O2O营销平台';
$config['SITE_KEYWORDS'] = '翼码旺财,O2O营销,O2O解决方案,移动互联网营销,微信营销,微信第三方,微官网';
$config['SITE_DESCRIPTION'] = '翼码旺财是上海翼码信息科技有限公司推出的O2O营销平台，为企业开展O2O营销、O2O解决方案、多宝电商、全民营销、支付宝条码支付、微信 营销，开展异业合作，打通O2O线上线下推广渠道，提供一站式O2O营销服务。';

return $config;
