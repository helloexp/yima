<?php

// 获取可用门店
class AvailableStoreAction extends MyBaseAction {

    public function _initialize() {
    }

    public function index() {
        $group_id = I('group_id', '');
        
        $this->assign('group_id', $group_id);
        $this->display();
    }

    public function availableStoreArr() {
        $group_id = I('group_id', '');
        $key = I('keyword', null);
        
        if ($group_id == '') {
            header("Content-type: text/html; charset=utf-8");
            $this->ajaxReturn(
                array(
                    'status' => 0, 
                    'msg' => '访问出错！'), 'JSON');
            exit();
        }
        
        $group_type = M('tpos_group')->where(
            array(
                'group_id' => $group_id))->getField('group_type');
        if ($group_type == NULL) {
            header("Content-type: text/html; charset=utf-8");
            $this->ajaxReturn(
                array(
                    'status' => 0, 
                    'msg' => '访问出错！'), 'JSON');
            exit();
        }
        
        $storeWhere = 's.store_id = t.store_id';
        if ($group_type == '0') {
            $storeWhere = 's.node_id = t.node_id';
        }
        
        $where = " t.group_id = " . $group_id;
        
        if ($key)
            $where .= " and s.store_name like '%" . $key . "%' ";
        
        $lat = cookie('lat'); // 纬度
        $lng = cookie('lon'); // 经度
        if ($lat != null) {
            $order_arr = "(s.lbs_x - {$lat})*(s.lbs_x - {$lat})+ (s.lbs_y - {$lng})*(s.lbs_y - {$lng}) ";
        }
        $lat_f = 0;
        if ($lat == '' || $lng == '') {
            $lat_f = 1;
            $order_arr = 's.store_id desc';
        }
        
        // 区别对待上户型终端组和门店型终端组
        // $storeMap = $group_type == '0' ? 's.node_id = t.node_id' :
        // 's.store_id = t.store_id';
        
        // 分页
        $nowP = I('p', null, 'mysql_real_escape_string'); // 页数
        empty($nowP) ? $nowP = 1 : $nowP;
        $pageCount = 10; // 每页显示条数
        $storeList = M()->table("tgroup_pos_relation t")->field(
            't.group_id,t.store_id,s.store_name,s.store_pic,s.address,s.lbs_x,s.lbs_y,s.area_code,s.store_phone')
            ->join('tstore_info s ON ' . $storeWhere)
            ->where($where)
            ->group('s.store_id')
            ->order($order_arr)
            ->limit(($nowP - 1) * $pageCount, $pageCount)
            ->select();
        
        foreach ($storeList as $ke => $v) {
            $storeList[$ke]['city_town'] = D('CityCode')->getCityTown(
                $v['area_code']);
            $distance = $this->getDistance($lat, $lng, $v['lbs_x'], $v['lbs_y']);
            $distance = round($distance);
            $km = "";
            if ($distance < 1000) {
                if ($distance < 100) {
                    $km = '<100m';
                } else {
                    $km = $distance . 'm';
                }
            }
            if ($distance >= 1000 && $distance <= 10000) {
                $dis_re = $distance / 1000;
                $dis_re = round($dis_re, 2);
                $km = $dis_re . 'km';
            }
            if ($distance > 10000) {
                $km = '>10km';
            }
            
            $storeList[$ke]['distance'] = $km;
        }
        
        $nextUrl = U('Label/AvailableStore/availableStoreArr') . '&group_id=' .
             $group_id . '&p=' . ($nowP + 1);
        
        $str = '';
        if ($storeList) {
            foreach ($storeList as $vo) {
                $str .= '<div class="box">
                        <a href="javascript:void(0);">
                            <img src="' .
                     C('UPLOAD') . $vo['store_pic'] . '" />
                            <div class="proItem-msg proItem-msg2">
                                <h1><p class="l">' .
                     $vo['store_name'] . '</p>';
                if ($vo['lbs_x'] != 0 && $lat != null) {
                    $str .= '<p class="r">' . $vo['distance'] . '</p>';
                }
                $str .= '</h1>
                                <h3>' .
                     $vo['address'] . '</h3>
                                <h2><a href="tel:' .
                     $vo['store_phone'] . '" class="l"><i></i>' .
                     $vo['store_phone'] . '</a>';
                if ($vo['lbs_x'] != 0 && $lng != null) {
                    $str .= '<a href="javascript:void(0);" id="gps_url_' .
                         $vo['store_id'] . '" class="gps_url gr' .
                         $vo['store_id'] . ' r" lat ="' . $vo['lbs_x'] .
                         '" lng = "' . $vo['lbs_y'] . '" data-addr="' .
                         $vo['address'] . '"><input type="hidden" value="' .
                         $vo['city_town']['city'] .
                         '" class="des_city"/><i></i>我要去</a>';
                }
                $str .= '</h2>
                            </div>
                        </a>
                    </div>';
            }
        }
        
        $nextUrlStr = '';
        if (count($storeList) == 10) {
            $nextUrlStr = '<div class="get-more" data-url="' . $nextUrl . '" style="padding:10px 0;">
                        <a href="javascript:void(0);" style="margin:0 15px; background: #ffffff; display:block; height:36px; border:solid 1px #ddd; border-radius:5px; line-height:36px; color:#999;">更多门店&nbsp;></a>
                    </div>';
        }
        
        header("Content-type: text/html; charset=utf-8");
        $this->ajaxReturn(
            array(
                'nextUrl' => $nextUrlStr, 
                'str' => $str, 
                'town_str' => $town_str), 'JSON');
        exit();
    }

    public function geoconv() {
        $point_x = $_REQUEST['x']; // 纬度
        $point_y = $_REQUEST['y']; // 经度
                                   // gps 坐标转换
        if (! empty($point_x) && ! empty($point_y)) {
            $pgsurl = "http://api.map.baidu.com/geoconv/v1/?";
            $parms = array(
                'coords' => $point_y . ',' . $point_x, 
                'ak' => 'WRzAu3DNewWB4oeOELaczjsM', 
                'output' => 'json');
            $str = http_build_query($parms);
            $rs = file_get_contents($pgsurl . $str);
            $rs = (array) json_decode($rs);
            if ($rs['status'] == 0) {
                $point_y = $rs['result'][0]->x;
                $point_x = $rs['result'][0]->y;
            }
            $this->ajaxReturn(
                array(
                    'x' => $point_x, 
                    'y' => $point_y, 
                    'status' => 1), 'JSON');
        } else {
            $this->ajaxReturn(
                array(
                    'info' => "坐标参数错误！{$point_x}:{$point_y}", 
                    'status' => 0), 'JSON');
        }
    }

    /**
     * 根据两点间的经纬度计算距离
     *
     * @param float $lat 纬度值
     * @param float $lng 经度值
     */
    public function getDistance($lat1, $lng1, $lat2, $lng2) {
        $earthRadius = 6367000;
        $lat1 = ($lat1 * pi()) / 180;
        $lng1 = ($lng1 * pi()) / 180;
        $lat2 = ($lat2 * pi()) / 180;
        $lng2 = ($lng2 * pi()) / 180;
        $calcLongitude = $lng2 - $lng1;
        $calcLatitude = $lat2 - $lat1;
        $stepOne = pow(sin($calcLatitude / 2), 2) +
             cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
        $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
        $calculatedDistance = $earthRadius * $stepTwo;
        return round($calculatedDistance);
    }
}