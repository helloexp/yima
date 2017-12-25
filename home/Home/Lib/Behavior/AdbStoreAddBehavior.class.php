<?php

/**
 * 爱蒂宝非标-门店添加成功之后，创建门店单页
 */
class AdbStoreAddBehavior extends AdibaoBaseBehavior {

    public function run(&$params) {
        parent::_initialize($params);
        
        if (! parent::checkIsAdb())
            return;
        
        M()->startTrans();
        
        $array_p = '{"module":[{"initialise":"true","name":"Pro","list":[],"checkproPic":"3","checkproBtn":"true","checkproName": "true","checkproPrice":"true"}]}';
        $page_data = array(
            'node_id' => $params['node_id'], 
            'page_type' => 5, 
            'page_name' => $params['page_name'], 
            'status' => 2, 
            'page_content' => $array_p, 
            'add_time' => date('YmdHis'));
        $page_id = M("tecshop_page_sort")->add($page_data);
        if (! $page_id) {
            M()->rollback();
            log_write(
                '爱蒂宝创建门店成功,门店首页初始化失败!' . print_r($page_data, true) .
                     M()->getDbError(), 'DB ERROR');
            return parent::bReturn(1, '创建门店成功,门店首页初始化失败!', null);
            ;
        }
        $adb_store_page_data = array(
            'store_id' => $params['store_id'], 
            'page_id' => $page_id);
        $result = M("tfb_adb_store_page")->add($adb_store_page_data);
        if (! $result) {
            M()->rollback();
            log_write(
                '创建门店成功,门店初始化失败!' . print_r($adb_store_page_data, true) .
                     M()->getDbError(), 'DB ERROR');
            return parent::bReturn(1, '创建门店成功,门店初始化失败!', null);
        }
        
        M()->commit();
        
        parent::bReturn(0, '', array(
            'page_id' => $page_id));
    }
}