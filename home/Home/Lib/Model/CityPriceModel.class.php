<?php

/**
 * 运费根据地区计算价格 by zc time 2015/08/13
 */
class CityPriceModel extends BaseModel {
    protected $tableName = '__NONE__';
    /**
     *
     * @param $nodeId 机构id
     * @param $cityCode 城市代码
     */
    public function cityPrice($nodeId, $cityCode) {
        $priceList = M('tcity_price')->where(
            array(
                'node_id' => $nodeId))->getField('price_rule');
        dump($priceList);
    }
}