<?php
$config = array(
    'LOG_PATH' => LOG_PATH . 'LogAlipayServ_',  // 日志路径+文件名前缀,
    'CUSTOM_LOG_PATH' => LOG_PATH . 'LogAlipayServ_',  // 日志路径+文件名前缀,
    'ALIPAY_FWC' => array(
        'alipay_public_key_file' => CONF_PATH . "key/alipay_rsa_public_key.pem", 
        'merchant_private_key_file' => CONF_PATH . "key/rsa_private_key.pem", 
        'merchant_public_key_file' => CONF_PATH . "key/rsa_public_key.pem", 
        'charset' => "GBK", 
        'gatewayUrl' => "https://openapi.alipay.com/gateway.do", 
        'LOG_PATH' => LOG_PATH . 'LogAlipayServ_',  // 日志路径+文件名前缀,
        'CUSTOM_LOG_PATH' => LOG_PATH . 'LogAlipayServ_')); // 日志路径+文件名前缀,

return $config;