<?php

class Fj114Model extends Model {
    protected $tableName = '__NONE__';
    /**
     * 查询电子券
     * @param type $phone
     * @return type
     */
    public function getGoodsList($phone){
        $goodsIdList = M('tfb_fjguaji_shop_info')
            ->where(array('phone_no'=>$phone))
            ->getfield('goods_list');
        return $goodsIdList;
    }
}