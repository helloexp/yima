<?php

/**
 * 爱蒂宝非标-商品下架之后编辑保存
 */
class AdbGoodsEditSaveBehavior extends AdibaoBaseBehavior {

    public function run(&$params) {
        parent::_initialize($params);
        
        if (! parent::checkIsAdb())
            return;
        
        M()->startTrans();
        if ($params['openStores'] == '' || $params['openStores'] == 0) {
            $params['openStores'] = 0;
            M("tfb_adb_goods_store")->where("b_id=" . $params['b_id'])->delete();
            $sto_data = array(
                'b_id' => $params['b_id'], 
                'store_id' => $params['openStores']);
            $sto_re = M("tfb_adb_goods_store")->add($sto_data);
            if (! $sto_re) {
                M()->rollback();
                log_write(
                    '爱蒂宝商品上架保存失败！' . print_r($sto_data, true) . M()->getDbError(), 
                    'DB ERROR');
                return parent::bReturn(1, '爱蒂宝商品上架保存失败!', null);
            }
        } else {
            M("tfb_adb_goods_store")->where("b_id=" . $params['b_id'])->delete();
            $stores_s = $params['openStores'] . ',' . '0';
            $storeArr = explode(',', $stores_s);
            foreach ($storeArr as $storeId) {
                $sto_data = array(
                    'b_id' => $params['b_id'], 
                    'store_id' => $storeId);
                $sto_re = M("tfb_adb_goods_store")->add($sto_data);
                if ($sto_re === false) {
                    M()->rollback();
                    log_write(
                        '爱蒂宝商品上架保存失败！' . print_r($sto_data, true) .
                             M()->getDbError(), 'DB ERROR');
                    return parent::bReturn(1, '爱蒂宝商品上架保存失败!', null);
                }
            }
        }
        M()->commit();
        parent::bReturn(0, '', array(
            'sto_data' => $sto_data));
    }
}