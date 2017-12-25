<?php

// CBC(建行)门店导航
class WapStoreNavigationAction extends BaseAction {
    private $service = '';
    // 初始化
    public function _initialize()
    {
        C('TMPL_ACTION_ERROR', './Home/Tpl/Label/Public_error.html');
        // parent::_initialize();

        $cbc = C("fjjh");
        $this->node_id = $cbc['node_id'];
        $this->service = D("FbCbcStoreNavigation", "Service");
        $this->service->setByNodeId($this->node_id);
    }

    //门店列表
    public function index()
    {
        $this->service->_getUserCity();

        //获取门店活动
        $activity_list = $this->service->getByStoreActivitys();

        $p = I('p', '', 'mysql_real_escape_string');
        $where = array();
        if(!empty($p)) {
            $where['p'] = $p;
        }

        $activity_id = I('activity_id', cookie("select_activity_id"), 'mysql_real_escape_string');
        if(!empty($activity_id)) {
            $where['activity_id'] = $activity_id;
            cookie("select_activity_id", $activity_id);
            foreach($activity_list as $v) {
                if($v['id'] == $activity_id) {
                    cookie("select_activity_name", $v['activity_title']);
                    break;
                }
            }
        }

        $area_city = cookie('city_area_code');
        if(!empty($area_city)) {
            $where['area_code'] = $area_city;
        }

        $area_town = cookie('town_area_code');
        if(!empty($area_town)) {
            $where['area_code'] = $area_town;
        }

        $info = $this->service->getByStoresInfo($where);
        if(!$info) {
            $this->errorByCode(-1022);
        }

        //获取城市
        $town_list = $this->service->getByStoreTowns($area_city);
        $this->assign("town_list", $town_list);        
        $this->assign("activity_list", $activity_list);
        $this->assign("lists", $info['lists']);
        $this->assign('next_page', $info['next_page']);
        $this->display("WapStoreNavigation/index");
    }

    /**
     * 城市选取页
     */
    public function selectCity()
    {
        $city_list = $this->service->getByStoreCitys();

        $this->assign("city_list", $city_list);
        $this->display("WapStoreNavigation/selectCity");
    }

    /**
     * 门店详情
     */
    public function detail()
    {
        $store_id = I('store_id', '', 'mysql_real_escape_string');
        if(empty($store_id)) {
            $this->errorByCode(-1006);
        }
        $where = array(
            'store_id' => $store_id,
            );
        $activity_id = I('activity_id', '', 'mysql_real_escape_string');
        if($activity_id) {
            $where['activity_id'] = $activity_id;
            $this->assign("activity_id", $activity_id);
        }

        $info = $this->service->getByStoreDetail($where);
        if(!$info) {
            $this->errorByCode(-1022);
        }

        session("act_id_str_".$this->node_id, $info['act_id_str']);

        $this->assign("info", $info);
        $this->display("WapStoreNavigation/detail");
    }

    /**
     * 其它门店 -- 相同活动类型(有分组限制时只显示同一分组下相同活动类型门店)
     */
    public function otherStores()
    {
        $group_id = I('group_id', '', 'mysql_real_escape_string');
        $activity_id = I('activity_id', '', 'mysql_real_escape_string');        
        $p = I('p', '', 'mysql_real_escape_string');
        if(empty($group_id)) {
            $this->errorByCode(-1006);
        }

        $where = array(
            'group_id' => $group_id,
            'act_id_str' => $act_id_str,
            );
        if(!empty($p)) {
            $where['p'] = $p;
        }

        $list = $this->service->getByOtherStores($where);
        if(!$list) {
            $this->errorByCode(-1022);
        }

        $this->assign("lists", $list['lists']);
        $this->assign('next_page', $list['next_page']);
        $this->assign("activity_id", $activity_id);
        $this->display("WapStoreNavigation/otherStores");
    }

    /**
     * 门店搜索页
     */
    public function serachStores()
    {
        $keywork = I('keywork', '', 'mysql_real_escape_string');
        $p = I('p', '', 'mysql_real_escape_string');
        $where = array();
        if(!empty($p)) {
            $where['p'] = $p;
        }

        if(!empty($keywork)) {
            $where['keywork'] = $keywork;
            $this->assign("keywork", $keywork);
        }

        $info = $this->service->getByStoresInfo($where);
        if(!$info) {
            $this->errorByCode(-1022);
        }

        $this->assign("lists", $info['lists']);
        $this->assign('next_page', $info['next_page']);
        $this->display("WapStoreNavigation/serachStores");
    }

    /**
     * 导航页面
     */
    public function drivingRoute()
    {
        $start = I('start', '', 'mysql_real_escape_string');
        $end = I('end', '', 'mysql_real_escape_string');
        $this->assign('start', $start);
        $this->assign('end', $end);

        $this->display("WapStoreNavigation/drivingRoute");
    }

    //获取经纬度转换为百度经纬度
    public function geoconv()
    {
        $point_x = $_REQUEST['x']; //纬度
        $point_y = $_REQUEST['y']; //经度
        //gps 坐标转换
        if (!empty($point_x) && !empty($point_y)) {
            $pgsurl = 'http://api.map.baidu.com/geoconv/v1/?';
            $parms = array(
                'coords' => $point_y.','.$point_x,
                'ak' => 'WRzAu3DNewWB4oeOELaczjsM',
                'output' => 'json',
            );
            $str = http_build_query($parms);
            $rs = file_get_contents($pgsurl.$str);
            $rs = (array) json_decode($rs);
            if ($rs['status'] == 0) {
                $point_y = $rs['result'][0]->x;
                $point_x = $rs['result'][0]->y;
            }
            $this->ajaxReturn(array('x' => $point_x, 'y' => $point_y, 'status' => 1), 'JSON');
        } else {
            $this->ajaxReturn(array('info' => "坐标参数错误！{$point_x}:{$point_y}", 'status' => 0), 'JSON');
        }
    }

    /**
     * 错误码
     * @param  int $code  错误提示参数请参照 /Home/ConfTest/configTipsInfo.php
     * @param  array $option 其他参数，不允许覆盖默认参数值
     */
    protected function errorByCode($code, $option=null){
        import('@.Service.TipsInfoService');
        $info=TipsInfoService::getMessageInfoErrorSoftTxtByNo($code);
        if(!empty($option)){
            $this->ajaxReturn(array_merge(array(
                    'info'=>$info,
                    'status'=>0,
            ), (array)$option));
        }
        $this->error($info);
    }

    public function delCook()
    {
        cookie('city_area_code', null);
        cookie('city_area_name', null);
        cookie('town_area_code', null);
        cookie('town_area_name', null);
        cookie('select_activity_id', null);
        cookie('select_activity_name', null);
        dump("cookie 清楚成功！");
        exit;
    }
}