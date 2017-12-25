<?php

class MobileDataService {
    protected $appKey = '093470ab5fb061c193c081e1926f23a6';

    public function __construct() {
        
    }

    /**
     * 检查字段是否为空或校验手机格式
     * @param type $checkVariable
     * @param type $configArray
     * @return boolean 
     */
    public function checkNotNull($checkVariable, $name, $configArray = NULL) {

        switch ($checkVariable) {
            case '0':
                echo $name.'参数错误，或未设置';
                exit;
                break;
            default:
                if ($name == 'phone_no' || $name == 'isdn') {
                    if (!check_str($checkVariable, array('maxlen' => '20', 'minlen'=>'1'))) {
                        
                        echo $name.'输入错误';
                        exit;
                    }
                }elseif($name == 'package'){
                    if(!in_array($checkVariable, $configArray)){
                        echo $name.'输入错误';
                        exit;
                    }
                }
                return TRUE;
                break;
        }
    }


    public function getNonceStr() {
        $str = '1234567890abcdefghijklmnopqrstuvwxyz';
        $t1 = '';
        for ($i = 0; $i < 19; $i ++) {
            $j = rand(0, 35);
            $t1 .= $str[$j];
        }
        return $t1;
    }

    /**
     * 获取签名
     *
     * @param array $data
     *
     */
    public function sign($data) {
        $ss = '';
        foreach ($data as $index => $item) {
            $ss .= $index.'='.$item.'&';
        }
        $signSrc = $ss . 'key=' . $this->appKey;
        $sign = md5($signSrc);
        log_write('origin:$signSrc:'.$signSrc . ' md5:'.$sign);
        return $sign;
    }


}
