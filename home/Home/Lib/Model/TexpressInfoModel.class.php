<?php

class TexpressInfoModel extends Model {

    /**
     * 获取最近使用的快递
     */
    function getLastUsedExpress() {
        $result = array();
        $expressInfoModel = M('texpress_info');
        $allExpress = $expressInfoModel->field('express_name')->select();
        foreach ($allExpress as $value) {
            $allExpressArray[] = $value['express_name'];
        }
        $expressName = C('normalUseExpress');
        $diffExpress = array_diff($allExpressArray, $expressName);
        $expressName = array_merge($expressName, $diffExpress);
        $recentExpress = cookie('recentExpress');
        if (empty($recentExpress)) {
            $recentExpress = $expressName;
        } else {
            $result['rescent'] = $recentExpress[0];
            $diffExpress = array_diff($expressName, $recentExpress);
            $recentExpress = array_merge($recentExpress, $diffExpress);
        }
        
        $expressStr = '';
        foreach ($recentExpress as $val) {
            $expressStr .= '"' . $val . '",';
        }
        $result['expressStr'] = $expressStr;
        return $result;
    }
}
