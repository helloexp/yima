<?php

class GoodsAction extends CnpcGXAction {

    public $_authAccessMap = '*';

    public function _initialize() {
        parent::_initialize();
    }

    public function check_is_self() {
        $goods_no = I('request.goods_no');
        $count = M()->table('tfb_cnpcgx_goods a,tfb_cnpcgx_node_info b')
            ->where(
            "a.goods_no='" . trim($goods_no) .
                 "' and a.merchant_id=b.id and b.type=1")
            ->count();
        if ($count > 0) {
            echo "1";
        } else {
            echo "0";
        }
    }
}