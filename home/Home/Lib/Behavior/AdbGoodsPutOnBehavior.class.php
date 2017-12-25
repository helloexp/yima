<?php

/**
 * 爱蒂宝非标-商品上架成功之后，接收门店信息
 */
class AdbGoodsPutOnBehavior extends AdibaoBaseBehavior {

    public function run(&$params) {
        parent::_initialize($params);
        
        if (! parent::checkIsAdb())
            return;
        
        M()->startTrans();
        if ($params['stores'] == '') {
            $stores = 0;
            $sto_data = array(
                'b_id' => $params['b_id'], 
                'store_id' => $stores);
            $sto_re = M("tfb_adb_goods_store")->add($sto_data);
            if (! $sto_re) {
                M()->rollback();
                log_write(
                    '爱蒂宝商品信息保存失败！' . print_r($sto_data, true) . M()->getDbError(), 
                    'DB ERROR');
                return parent::bReturn(1, '爱蒂宝商品信息保存失败!', null);
            }
        } else {
            $stores_s = $params['stores'] . ',' . '0';
            $storeArr = explode(',', $stores_s);
            foreach ($storeArr as $storeId) {
                $sto_data = array(
                    'b_id' => $params['b_id'], 
                    'store_id' => $storeId);
                $sto_re = M("tfb_adb_goods_store")->add($sto_data);
                if ($sto_re === false) {
                    M()->rollback();
                    log_write(
                        '爱蒂宝商品信息保存失败！' . print_r($sto_data, true) .
                             M()->getDbError(), 'DB ERROR');
                    return parent::bReturn(1, '爱蒂宝商品信息保存失败!', null);
                }
            }
        }
        M()->commit();
        parent::bReturn(0, '', array(
            'page_id' => $page_id));
    }
}