<?php

/**
 * Class 门店导航
 *
 * @author 陈赛疯
 */
class ListShopAction extends MyBaseAction {

    private $model;
    const BATCH_TYPE_SHOPGPS = 17;

    public function _initialize() {
        parent::_initialize();
        $this->model = D("StoreNavigation", "Model");
        $this->model->setByNodeId($this->nodeId);
        $this->assign('BATCH_TYPE_SHOPGPS', self::BATCH_TYPE_SHOPGPS);
    }

    function plocation() {
        $this->assign('slng', $_REQUEST['lng']);
        $this->assign('slat', $_REQUEST['lat']);
        $this->assign('lng', $_REQUEST['endLng']);
        $this->assign('lat', $_REQUEST['endLat']);
        $this->assign('cityName', $_REQUEST['cityName']);
        $this->assign('des_city', $_REQUEST['des_city']);
        $this->display();
    }

    public function index(){
        // 判断预览时间是否过期
        if (false === $this->checkCjEndtime()) {
            $this->error(
                    array(
                            'errorImg' => '__PUBLIC__/Label/Image/waperro6.png',
                            'errorTxt' => '预览时间已过期！',
                            'errorSoftTxt' => '您访问的预览地址30分钟内有效，现已超时啦~'));
        }


        $this->assign('id', $_REQUEST['id']);
        $this->assign('wechat_card_js', $_REQUEST['wechat_card_js']);
        $this->assign('mapUrl', $this->getSosoMapUrl());
        $this->display(); // 输出模板
    }

    function getcity() {
        $point_x = $_REQUEST['point_x']; // 纬度
        $point_y = $_REQUEST['point_y']; // 经度大
                                         // gps 坐标转换
        if (! empty($point_x) && ! empty($point_y)) {
            $pgsurl = "http://api.map.baidu.com/geocoder/v2/?";
            $parms = array(
                'coordtype' => 'wgs84ll', 
                'location' => $point_x . ',' . $point_y, 
                'ak' => '96b4191b34ecf02a727747aaf0eedcbb', 
                'output' => 'json');
            $str = http_build_query($parms);
            $rs = file_get_contents($pgsurl . $str);
            // file_put_contents(dirname(__FILE__).'/11.txt',$pgsurl.$str);
            $rs = (array) json_decode($rs);
            if ($rs['status'] == 0) {
                echo json_encode(
                    array(
                        'lat' => $rs['result']->location->lat, 
                        'lng' => $rs['result']->location->lng, 
                        'status' => 1, 
                        'info' => $rs['result']->addressComponent->city));
            } else {
                echo json_encode(array(
                    'status' => 0));
            }
        } else
            echo json_encode(array(
                'status' => 0));
    }

    function ajaxgetist() {

        log_write('提交过来的数据:['.print_r($_REQUEST,true).']');

        import('@.Vendor.DataStat');
        $point_x = $_REQUEST['point_x']; // 纬度
        $point_y = $_REQUEST['point_y']; // 经度

        //gps 坐标转换
        if(!empty($point_x) && !empty($point_y)){
            $pgsurl="http://api.map.baidu.com/geoconv/v1/?";
            $parms=array(
                'coords'=>$point_y.','.$point_x,
                'ak'=>'96b4191b34ecf02a727747aaf0eedcbb',
                'output'=>'json'
            );
            $str=http_build_query($parms);
            $rs=file_get_contents($pgsurl.$str);
            $rs=(array)json_decode($rs);
            if($rs['status']==0){
                $point_y=$rs['result'][0]->x;
                $point_x=$rs['result'][0]->y;
            }
        }

        $id = $this->id;
        $batch_id = $this->batch_id;
        $opt = new DataStat($this->id, $this->full_id);
        $opt->UpdateRecord();
        $page = $_REQUEST['page'];
        if ($page < 1){
            $page = 1;
        }
            // 获取门店数据
        $model = M('tstore_info');
        
        // 按机构树数据隔离
        $where = "a.node_id in (" . $this->nodeIn() . ")";
        $node_id = $this->node_id;
        // 按机构号查询
        if ($node_id != $this->nodeId) {
            $where = " a.node_id in (" . $this->nodeIn($node_id) . ")";
        }
        if (I('province') != '' & I('province') != 0) {
            $where .= " and a.province_code = '" . I('province') . "'";
        }
        
        if (I('city') != '' && I('city') != 0) {
            $where .= " and a.city_code = '" . I('city') . "'";
        }
        
        if (I('town') != '' && I('town') != 0) {
            $where .= " and a.town_code = '" . I('town') . "'";
        }

        if(I('storeName') != ''){
            $where .= "and a.store_name LIKE '%".I('storeName')."%'";
        }

        $join= '';
        if(I('youhui') == 1){
            $whe['node_id'] = $this->node_id;
            $whe['status'] = '0';
            $whe['store_join_flag'] = '1';
            $res = M('tstore_activity_info')->where($whe)->find();
            if(!$res){
                $join = ' INNER JOIN tstore_activity_relation r ON r.store_id=a.store_id ';
            }
        }

        $order = '';
        if($point_x && $point_y){
            $order = "((a.lbs_x - {$point_x})*(a.lbs_x - {$point_x})+ (a.lbs_y - {$point_y})*(a.lbs_y - {$point_y})) asc";
        }

        $where .= " and a.store_id in (select store_id from tstore_navigation_item where m_id=".$batch_id." ) ";
        $queryList = $model->table('tstore_info a')
            ->join('LEFT JOIN tnode_info b on b.node_id=a.node_id')
            ->join($join)
            ->field(
            'a.store_phone, a.id,a.store_name,a.address,a.principal_tel,a.lbs_x,a.lbs_y,b.node_name,a.area_code,a.store_pic,a.store_id')
            ->where($where)
            ->order($order)
            ->group('id')
            ->select();
        //->limit(($page - 1) * 10, 10)


        $allCount = $model->table('tstore_info a')
                ->join('LEFT JOIN tnode_info b on b.node_id=a.node_id')
                ->join($join)
                ->field(
                        'a.id')
                ->where($where)
                ->group('id')
                ->select();

        $allCount = count($allCount);

        $cityData = array();
        $destinations = '';
        $date_time=date("YmdHis");
        foreach ($queryList as &$v) {
            $v['city_town'] = $cityData[] =  D('CityCode')->getCityTown($v['area_code']);
            $destinations .= trim($v['lbs_x'], '0') . "," .
                 trim($v['lbs_y'], '0') . "|";
            //拼活动信息
            $v['activity_info'] = M()->query("SELECT z.* FROM
(
SELECT i.id,i.activity_type,i.activity_title,i.activity_desc,i.store_join_flag,i.activity_image,r.store_id,i.start_time,i.end_time
FROM tstore_activity_relation r
LEFT JOIN tstore_activity_info i ON i.id=r.activity_id
WHERE r.store_id='".$v['store_id']."' AND i.start_time < '".$date_time."' AND i.end_time > '".$date_time."'
UNION
SELECT id,activity_type,activity_title,activity_desc,store_join_flag,activity_image,NULL,start_time,end_time FROM `tstore_activity_info` WHERE node_id='".$this->node_id."' AND STATUS ='0' AND  store_join_flag = 1 AND start_time < '".$date_time."' AND end_time > '".$date_time."'
) z GROUP BY z.id DESC  LIMIT 0,2 ");

            //兼容图片字段的老数据
            if(strpos ($v['store_pic'],$this->node_id.'/') === false){
                $v['store_pic'] = '';
            }

        }
        // &$list 查询的数据数组(包含目的地lbx_x,lbs_y)$lat 当前位置lat $lng 当前位置lng
        if($point_x && $point_y){
            $this->checkByStoreDistance($queryList,$point_x,$point_y);
            $sortArr = [];
            foreach($queryList as $k => &$v){
                $distance  = $this->getDistance($v['lbs_x'],$v['lbs_y'],$point_x,$point_y);
                $v['path_text'] = $this->getKm($distance);
                $sortArr[] = $distance;
            }

            array_multisort($sortArr, SORT_ASC, $queryList);
        }

        $node_name = empty($this->marketInfo['wap_title']) ? M('tnode_info')->where(
            "node_id = '{$this->node_id}'")
            ->limit('1')
            ->getField('node_short_name') : $this->marketInfo['wap_title'];

        /*$arr = array(
                array(
                        'page' => $page + 1,
                        'id' => $id,
                        'store_count' => $store_count,
                        'node_name' => $node_name,
                        'list' => $queryList));
        echo "<pre>";
        print_r($arr);
        echo "</pre>";
        exit;*/

        echo json_encode(
            array(
                'page' => $page + 1,
                'id' => $id,
                'store_count' => $allCount,
                'node_name' => $node_name,
                'list' => $queryList));
    }

    /**
     * 计算两点之间距离
     */
    private function checkByStoreDistance(&$list, $lat, $lng)
    {
        foreach ($list as $k => $v) {
            $distance = $this->getDistance($lat, $lng, $v['lbs_x'], $v['lbs_y']);
            $distance = round($distance);
            $km = '';
            if ($distance < 1000) {
                if ($distance < 100) {
                    $km = '<100m';
                } else {
                    $km = $distance.'m';
                }
            }
            if ($distance >= 1000 && $distance <= 10000) {
                $dis_re = $distance / 1000;
                $dis_re = round($dis_re, 2);
                $km = $dis_re.'km';
            }
            if ($distance > 10000) {
                $km = '>10km';
            }

            $list[$k]['distance'] = $km;
        }
    }

    /**
     *  @desc 根据两点间的经纬度计算距离
     *
     *  @param float $lat 纬度值
     *  @param float $lng 经度值
     */
    private function getDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6367000;
        $lat1 = ($lat1 * pi()) / 180;
        $lng1 = ($lng1 * pi()) / 180;
        $lat2 = ($lat2 * pi()) / 180;
        $lng2 = ($lng2 * pi()) / 180;
        $calcLongitude = $lng2 - $lng1;
        $calcLatitude = $lat2 - $lat1;
        $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
        $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
        $calculatedDistance = $earthRadius * $stepTwo;

        return round($calculatedDistance);
    }

    public function getKm($distance)
    {
        $km = '';
        if ($distance < 1000) {
            if ($distance < 100) {
                $km = '<100m';
            } else {
                $km = $distance.'m';
            }
        }
        if ($distance >= 1000 && $distance <= 10000) {
            $dis_re = $distance / 1000;
            $dis_re = round($dis_re, 2);
            $km = $dis_re.'km';
        }
        if ($distance > 10000) {
            $km = '>10km';
        }

        return $km;
    }

    /**
     * 门店详情
     */
    public function storeDetils()
    {
        $store_id = substr(I('store_id'),2);//坑爹的JS 为了不丢失0 在前面拼了个点号 后面拼了个9
        $store_id = substr($store_id,0,strlen($store_id)-1);
        $date_time=date("YmdHis");

        if(empty($store_id)) {
            $this->error('请求出错');
        }
        $store_info=M("tstore_info")
            ->where(array('store_id'=>$store_id))
            ->field("store_id,store_name,province,city,town,lbs_x,lbs_y,store_pic,address,store_phone,node_id")->find();

        if(strpos ($store_info['store_pic'],$this->node_id.'/') === false){
            $store_info['store_pic'] = '';
        }

        $sql = "SELECT z.* FROM
(
SELECT i.id,i.activity_type,i.activity_title,i.activity_desc,i.store_join_flag,i.activity_image,r.store_id,i.start_time,i.end_time
FROM tstore_activity_relation r
LEFT JOIN tstore_activity_info i ON i.id=r.activity_id
WHERE r.store_id='".$store_info['store_id']."' AND i.start_time < '".$date_time."' AND i.end_time > '".$date_time."'
UNION
SELECT id,activity_type,activity_title,activity_desc,store_join_flag,activity_image,NULL,start_time,end_time FROM `tstore_activity_info`
WHERE node_id='".$this->node_id."' AND STATUS ='0' AND  store_join_flag = 1 AND start_time <  '".$date_time."' AND end_time >  '".$date_time."'
) z GROUP BY z.id DESC ";
        $all_activity = M()->query($sql);

        $store_info['activity']=$all_activity;

        $this->assign('id',I('id'));
        $this->assign("info",$store_info);
        $this->display();
    }

    public function nodeIn($nodeId = null) {
        static $_node_full_id = array();
        if ($nodeId) {
            if (isset($_node_full_id[$nodeId])) {
                $path = $_node_full_id[$nodeId];
            } elseif ($nodeId == $this->nodeId) {
                $path = $this->nodePath;
            } else {
                $path = M('tnode_info')->where(
                    array(
                        'node_id' => $nodeId))->getField('full_id');
                $_node_full_id[$nodeId] = $path;
            }
        } else {
            $path = $this->nodePath;
        }
        if (! $path) {
            return "'" . $this->nodeId . "'";
        }
        return "select node_id from tnode_info where full_id like '" . $path .
             "%'";
    }
    
    // 获取soso地图url
    /**
     * 获取搜搜地图地址 $startLng 起点经度 $startLat 起点纬度 $endLng 终点经度 $endLat 终点纬度 $key
     * 起点地址||终点地址
     */
    public function getSosoMapUrl($opt = array()) {
        $url = 'http://map.wap.soso.com/x/?';
        $opt = array_merge(
            array(
                'type' => 'drive', 
                'cond' => 1, 
                'traffic' => 'close', 
                'welcomeChange' => 1, 
                'welcomeClose' => 1, 
                'startLng' => null, 
                'startLat' => null, 
                'endLng' => null, 
                'endLat' => null, 
                'key' => ''), $opt);
        $url .= http_build_query($opt, '');
        return $url;
    }
}
 