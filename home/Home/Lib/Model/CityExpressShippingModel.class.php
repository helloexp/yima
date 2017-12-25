<?php

class CityExpressShippingModel extends Model {

    protected $tableName = 'tcity_express_shipping';

    function saveOrAddData($saveData) {
        $isExist = $this->where(
            array(
                'node_id' => $_SESSION['userSessInfo']['node_id']))->getfield(
            'id');
        $saveData['edite_time'] = date('YmdHis');
        // 清除缓存
        S($_SESSION['userSessInfo']['node_id'] . 'cityExpressPrice', NULL);
        S($_SESSION['userSessInfo']['node_id'] . 'cityFeeLimit', NULL);
        if ($isExist) {
            $this->where(array(
                'id' => $isExist))->save($saveData);
        } else {
            $saveData['create_time'] = date('YmdHis');
            $this->add($saveData);
        }
    }

    function getCityExpressConfig($nodeId = false) {
        if (false === $nodeId)
            $nodeId = $_SESSION['userSessInfo']['node_id'];
        $expressRuleArray = S($nodeId . 'cityExpressPrice');
        if (empty($expressRuleArray)) {
            $cityExpressShippingModel = M('tcity_express_shipping');
            $expressRule = $cityExpressShippingModel->where(
                array(
                    'node_id' => $nodeId))->getfield('express_rule');
            $expressRuleArray = json_decode($expressRule, TRUE);
            S($nodeId . 'cityExpressPrice', $expressRuleArray, 1800); // 创建缓存文件
        }
        
        return $expressRuleArray;
    }

    /**
     * Description of SkuService 取得运费信息
     *
     * @param int $nodeId 商户唯一标识 int $orderAmt 订单总价 int $provinceCode 市级代码 int
     *            $cityCode 城市代码
     * @return array $shippingFee 返回运费
     * @author john_zeng
     */
    public function getShippingFee($nodeId, $orderAmt, $cityCode = 0) {
        $shippingFee = 0;
        $queryMap = array(
            "node_id" => $nodeId);
        $shippingConfig = $this->where($queryMap)->find();
        if ($shippingConfig['freight_free_flag'] == 1) {
            if ($orderAmt <= $shippingConfig['freight_free_limit']) {
                if (0 === $cityCode) {
                    $shippingFee = $shippingConfig[freight];
                } else {
                    $cityFee = self::getCityFee($shippingConfig['express_rule'], 
                        $cityCode, $shippingConfig[freight]);
                    $shippingFee = $cityFee;
                }
            } else {
                $shippingFee = 0.00;
            }
        } else {
            $shippingFee = isset($shippingConfig['freight']) ? $shippingConfig['freight'] : 0.00;
        }
        
        return $shippingFee;
    }

    /**
     * Description of SkuService 取得统一运费信息
     *
     * @param int $nodeId 商户唯一标识
     * @return string $expressFreight 返回统一运费
     * @author john_zeng
     */
    function getFreightConfig($nodeId = false) {
        if (false === $nodeId)
            $nodeId = $_SESSION['userSessInfo']['node_id'];
        $expressFreight = S($nodeId . 'freight');
        if (empty($expressRuleArray)) {
            $freight = $this->where(
                array(
                    'node_id' => $nodeId))->getfield('freight');
            $expressFreight = $freight;
            S($nodeId . 'freight', $expressFreight, 1800); // 创建缓存文件
        }
        
        return $expressFreight;
    }

    /**
     * Description of SkuService 取得运费信息
     *
     * @param int $orderAmt 订单总价 int $provinceCode 市级代码 int $cityCode 城市代码
     * @return array $shippingFee 返回运费
     * @author john_zeng
     */
    public function getCityFee($express_rule, $cityCode = 0, $fee) {
        if (! is_array($express_rule))
            $cityInfo = json_decode($express_rule, true);
        else
            $cityInfo = $express_rule;
        $shippingFee = 0;
        if ($cityInfo) {
            foreach ($cityInfo as $val) {
                if (strstr($val['cityCode'], $cityCode)) {
                    $shippingFee = $val['price'];
                }
            }
        }
        if (0 === $shippingFee)
            $shippingFee = $fee;
        return $shippingFee;
    }

    /**
     * Description of SkuService 取得免运费限制信息
     *
     * @param int $nodeId 商户唯一标识
     * @return array $shippingFee 返回运费
     * @author john_zeng
     */
    public function getFeeLimit($nodeId = false) {
        if (false === $nodeId)
            $nodeId = $_SESSION['userSessInfo']['node_id'];
        $feeLimit = S($nodeId . 'cityFeeLimit');
        if (empty($feeLimit)) {
            $feeLimit = $this->where(
                array(
                    'node_id' => $nodeId, 
                    'freight_free_flag' => '1'))->getfield('freight_free_limit');
            S($nodeId . 'cityFeeLimit', $feeLimit, 1800); // 创建缓存文件
        }
        
        return $feeLimit;
    }

    /**
     * Description of SkuService 更改短信通知配置
     *
     * @param int $nodeId 商户唯一标识 string $switch 短信开启关闭开关
     * @return bloor $result 返回处理信息
     * @author john_zeng
     */
    public function setSmsNotice($switch, $nodeId = false) {
        if (false === $nodeId)
            $nodeId = $_SESSION['userSessInfo']['node_id'];
        $configInfo = self::getNodeConfig($nodeId);
        // 判断是否有记录
        if (NULL === $configInfo) {
            $tmpArray = array(
                'node_id' => $nodeId);
            $this->add($tmpArray);
        }
        // 变更短息配置信息
        $result = $this->where(array(
            'node_id' => $nodeId))->save(
            array(
                'sms_notice' => $switch));
        return $result;
    }

    /**
     * Description of SkuService 获取短信配置信息
     *
     * @param int $nodeId 商户唯一标识
     * @return int $smsConfig
     * @author john_zeng
     */
    public function getNodeConfig($nodeId) {
        $smsConfig = $this->where(array(
            'node_id' => $nodeId))->getfield('sms_notice');
        return $smsConfig;
    }
    
    /**
     * Description of  获取用户收货地址信息
     *
     * @param string $phoneNum 用户手机号码
     * 
     * @return array  $adressList 用户地址信息 
     * @author john_zeng
     */
    public function getUserAdressList($phoneNum) {
        if(!isset($phoneNum)){
            return false;
        }

        $addressList = M('tphone_address')->where("user_phone='" . $phoneNum . "'")
                ->order("last_use_time desc, add_time")
                ->select();

        if (!$addressList) {
             return false;
        }else{
            $addressList = self::getCityToAdress($addressList);
        }
        
        return $addressList;
    }
    
    private function getCityToAdress($addressList) {
        foreach ($addressList as $ak => &$al) {
            $cityInfo = array();
            if ($al['path']) {
                $cityInfo = M('tcity_code')
                        ->where(array('path' => $al['path']))
                        ->field('province_code, city_code, town_code, province, city, town')
                        ->find();
                $al['province_code'] = $cityInfo['province_code'];
                $al['city_code'] = $cityInfo['city_code'];
                $al['town_code'] = $cityInfo['town_code'];
                $al['province'] = $cityInfo['province'];
                $al['city'] = $cityInfo['city'];
                $al['town'] = $cityInfo['town'];
            }
        }
        return $addressList;
    }
}
