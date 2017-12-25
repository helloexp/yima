<?php

class ShippingService {

    /**
     *
     * @param string $node_id 机构号
     * @param string $code 省份代码城市代码 5位
     * @param int $weight 商品总质量
     * @return array array('errorCode'=>'', 'msg'=> , 'price'=>)
     */
    public function index($node_id, $code, $weight = 1) {
        if (! is_int($weight)) {
            $result['errorCode'] = '20001';
            $result['msg'] = 'the type of weight is not right';
            return $result;
            exit();
        }
        
        $result = array();
        $cityExpressShippingModel = M('tcity_express_shipping');
        $shippingConfigArray = $cityExpressShippingModel->where(
            array(
                'node_id' => $node_id))->find();
        if ($shippingConfigArray['express_rule'] != '') {
            $shippingArray = json_decode($shippingConfigArray['express_rule'], 
                TRUE);
            foreach ($shippingArray as $val) {
                $str = 'asdk,' . $val['cityCode'];
                if (strpos($str, $code)) {
                    $result['errorCode'] = '0';
                    $result['msg'] = 'new express rule';
                    $result['price'] = $val['price'] +
                         ($weight - 1) * $val['secondPrice'];
                }
            }
            if (empty($result)) {
                $result = $this->_checkDefaultExpress($shippingConfigArray, 
                    $weight);
            }
        } else {
            $result = $this->_checkDefaultExpress($shippingConfigArray, $weight);
        }
        return $result;
    }

    /**
     *
     * @param array $shippingConfigArray 运费设置
     * @return array array('errorCode'=>'', 'msg'=> , 'price'=>)
     */
    private function _checkDefaultExpress($shippingConfigArray, $weight) {
        if ($shippingConfigArray['freight'] != '') {
            $result['errorCode'] = '0';
            $result['msg'] = 'old default';
            $result['price'] = $shippingConfigArray['freight'] +
                 ($weight - 1) * $shippingConfigArray['second_freight'];
            return $result;
        } else {
            $result['errorCode'] = '10001';
            $result['msg'] = 'unset shipping';
            return $result;
        }
    }

    /**
     *
     * @param array $existCityArray
     * @return string
     */
    function formatExistExpressConfig($existCityArray) {
        foreach ($existCityArray as $key => $existVal) {
            $existVal['checked'] = 'checked';
            if ($existVal['province'] == '上海' || $existVal['province'] == '北京' ||
                 $existVal['province'] == '浙江' || $existVal['province'] == '江苏') {
                $isCheckArray[$existVal['province']] = 'all';
                $tmpArray[$existVal['province']][] = $existVal;
            } else {
                $isCheckArray[$existVal['province']] = 'all';
                $result[$existVal['province']][] = $existVal;
            }
        }
        
        $backArray['tmp'] = $tmpArray;
        $backArray['result'] = $result;
        $backArray['isCheckArray'] = $isCheckArray;
        return $backArray;
    }

    /**
     *
     * @param type $cityExpressInfoArray
     * @param type $cityArray
     * @param type $tmpArray
     * @param type $result
     * @return type
     */
    function unformatExpressConfig($cityExpressInfoArray, $cityArray, $tmpArray, 
        $result, $isCheckArray) {
        if ($cityExpressInfoArray) {
            $existCityCode = 'kfd';
            foreach ($cityExpressInfoArray as $cityCodeVal) {
                $existCityCode .= ',' . $cityCodeVal['cityCode'];
            }
        }
        foreach ($cityArray as $val) {
            if (! strpos($existCityCode, $val['city_code'], 0) ||
                 $existCityCode == '') {
                if ($isCheckArray[$val['province']] != '') {
                    $isCheckArray[$val['province']] = 'half';
                }
                if ($val['province'] == '上海' || $val['province'] == '北京' ||
                     $val['province'] == '浙江' || $val['province'] == '江苏') {
                    $tmpArray[$val['province']][] = $val;
                } else {
                    $result[$val['province']][] = $val;
                }
            }
        }
        if (! empty($tmpArray)) {
            $result = array_reverse($tmpArray) + $result;
        }
        $backArray['result'] = $result;
        $backArray['isCheckArray'] = $isCheckArray;
        return $backArray;
    }
}
