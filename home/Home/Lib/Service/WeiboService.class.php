<?php

class WeiboService {

    public $config;

    public function __construct() {
        $this->config = require (CONF_PATH . 'configWeibo.php');
    }

    public function getSinaApi() {
        require_once (LIB_PATH . 'Vendor/SinaWeibo/saetv2.ex.class.php');
        $config = $this->config['SINA_WEIBO'];
        return new SaeTOAuthV2($config['WB_AKEY'], $config['WB_SKEY']);
    }

    public function getSinaConfig() {
        $config = $this->config['SINA_WEIBO'];
        return $config;
    }
}