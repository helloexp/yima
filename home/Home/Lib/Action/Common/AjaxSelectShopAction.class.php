<?php
// 根据选中城市信息查询门店
class AjaxSelectShopAction extends BaseAction {

    public function index() {
        $model = M('tstore_info');
        $where = array(
            'status' => '0');
        $nodeid = I('node_id', '', 'mysql_real_escape_string');
        if ($nodeid != '') {
            $where['node_id'] = $nodeid;
        }
        $provice = I('province', '', 'mysql_real_escape_string');
        if ($provice != '') {
            $where['province_code'] = $provice;
        }
        $cityCode = I('city', '', 'mysql_real_escape_string');
        if ($cityCode != '') {
            $where['city_code'] = $cityCode;
        }
        $townCode = I('town', '', 'mysql_real_escape_string');
        if ($townCode != '') {
            $where['town_code'] = $townCode;
        }
        
        $rows = $model->where($where)->select();
        // $shoplist = array();
        // foreach($rows as $k) {
        // $sid = $k["store_id"];
        // $shoplist[$nid]["store_id"] = $sid;
        // $shoplist[$nid]["store_name"] = $k["store_name"];
        // $shoplist[$nid]["province_code"] = $k["province_code"];
        // $shoplist[$nid]["city_code"] = $k["city_code"];
        // $shoplist[$nid]["town_code"] = $k["town_code"];
        // }
        
        $this->ajaxReturn($rows, 'json');
    }
}