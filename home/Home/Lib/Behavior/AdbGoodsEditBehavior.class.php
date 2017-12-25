<?php

/**
 * 爱蒂宝非标-商品下架之后编辑
 */
class AdbGoodsEditBehavior extends AdibaoBaseBehavior {

    public function run(&$params) {
        parent::_initialize($params);
        
        if (! parent::checkIsAdb())
            return;
        $map = array();
        $map['tags.b_id'] = $params['id'];
        $map['tags.store_id'] = array(
            'neq', 
            '0');
        $count = M()->table("tfb_adb_goods_store tags")->where($map)->count();
        $stores = M()->table("tfb_adb_goods_store tags")->field("tags.store_id")->where(
            $map)->select();
        $stores_data = array();
        foreach ($stores as $store) {
            $stores_data[] = $store['store_id'];
        }
        $stores_datas = join(',', $stores_data);
        parent::bReturn(0, '', 
            array(
                'count' => $count, 
                'stores_data' => $stores_datas));
    }
}