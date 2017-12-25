<?php

// 翼惠宝Wap
class YhbWapAction extends Action {
    // 跳转回来地址
    const __TRUE_BACK_URL__ = '__TRUE_BACK_URL__';
    // 微信用户id
    public $wxid;
    // public $openid = 'ovMxUt0k4v7zziYTaqU_6q9OBzHI';//微信openId
    public $openid;

    public $js_global = array();

    public $wap_sess_name = '';

    public $batch_type = 2000;

    public $node_id;

    public $expiresTime = 50;
    // 手机发送间隔
    public $CodeexpiresTime = 60;
    // 手机验证码过期时间
    public function _initialize() {
        C('TMPL_ACTION_SUCCESS', './Home/Tpl/Yhb/YhbWap/Public_error.html');
        C('TMPL_ACTION_SUCCESS', './Home/Tpl/Yhb/YhbWap/Public_msg.html');
        M('tsystem_param')->find();
        $this->node_id = C('yhb.node_id');
        $this->wap_sess_name = 'node_wxid_' . $this->node_id;
        $wein_appid = session($this->wap_sess_name);
        // session(null);
        if (empty($weins_appid['openid'])) {
            $this->_yhb_checklogin();
        }
        $this->openid = $wein_appid['openid'];
        $this->wxid = session($this->wap_sess_name);
    }

    public function getone() {
        // 绑定会员
        $mobile = I('post.mobile');
        $label_id = I('get.label_id');
        
        if ($_POST) {
            $label_id = I('post.label_id');
            // 手机验证码
            $checkCode = I('post.check_code', '', 'mysql_real_escape_string');
            if (! check_str($checkCode, 
                array(
                    'null' => false), $error)) {
                $this->ajaxReturn(
                    array(
                        'status' => 0, 
                        'info' => "验证码{$error}"), 'json');
            }
            $groupChaoWaiCode = session('groupChaoWaiCode');
            if (! empty($groupChaoWaiCode) &&
                 $groupChaoWaiCode['phoneNo'] != $mobile) {
                $this->ajaxReturn(
                    array(
                        'status' => 0, 
                        'info' => "手机号码不正确" . $mobile), 'json');
            }
            if (! empty($groupChaoWaiCode) &&
                 $groupChaoWaiCode['number'] != $checkCode) {
                $this->ajaxReturn(
                    array(
                        'status' => 0, 
                        'info' => "验证码错误"), 'json');
            }
            if (time() - $groupChaoWaiCode['add_time'] > $this->CodeexpiresTime) {
                $this->ajaxReturn(
                    array(
                        'status' => 0, 
                        'info' => "验证码已经过期"), 'json');
            }
            $time = date('YmdHis', time());
            $data_member = array(
                'mobile' => $mobile, 
                'openid' => $this->openid, 
                'related_time' => $time);
            $re = M('tfb_yhb_member')->where("mobile = '$mobile'")->select();
            if ($re) {
                $this->ajaxReturn(
                    array(
                        'status' => '0', 
                        'info' => '该手机已经注册过！'), 'json');
            }
            $re_member = M('tfb_yhb_member')->add($data_member);
            if (! $re_member) {
                $this->ajaxReturn(
                    array(
                        'status' => '0', 
                        'info' => '注册失败！'), 'json');
            } else {
                $wein_appid = session($this->wap_sess_name);
                $wein_appid['att_flag'] = '1';
                session($this->wap_sess_name, $wein_appid);
                $this->ajaxReturn(
                    array(
                        'status' => '1', 
                        'info' => '注册成功！'), 'json');
            }
        }
        $this->assign('label_id', $label_id);
        $this->display('YhbWap/getone');
    }
    
    // 领取优惠券
    public function goods_exchange() {
        $label_id = I('post.label_id');
        $openid = $this->openid;
        // 判断是否满足领取条件
        $re_get = M()->table('tfb_yhb_goods a')
            ->join('tgoods_info b ON a.goods_id = b.goods_id')
            ->join('tbatch_info c ON b.goods_id = c.goods_id')
            ->join('tmarketing_info d ON c.m_id = d.id')
            ->field(
            'a.line_status,b.status,d.start_time,d.end_time,d.status as m_status')
            ->where("a.label_id = '$label_id'")
            ->find();
        
        if ($re_get['status'] == '1') {
            $this->ajaxReturn(
                array(
                    'code' => '0008', 
                    'info' => '优惠券已停用'), 'json');
        }
        if ($re_get['line_status'] == '1') {
            $this->ajaxReturn(
                array(
                    'code' => '0009', 
                    'info' => '展示商品,此优惠券暂时无法领取'), 'json');
        }
        if ($re_get['line_status'] == '2') {
            $this->ajaxReturn(
                array(
                    'code' => '0009', 
                    'info' => '优惠券已停用，无法领取'), 'json');
        }
        if ($re_get['m_status'] == '2') {
            $this->ajaxReturn(
                array(
                    'code' => '0009', 
                    'info' => '优惠券已停用，无法领取'), 'json');
        }
        $start_time = strtotime($re_get['start_time']);
        $end_time = strtotime($re_get['end_time']);
        $now = time();
        if ($start_time > $now) {
            $this->ajaxReturn(
                array(
                    'code' => '0010', 
                    'info' => '优惠券活动还未开始'), 'json');
        }
        if ($end_time < $now) {
            $this->ajaxReturn(
                array(
                    'code' => '0010', 
                    'info' => '优惠券活动已经结束'), 'json');
        }
        
        $mobile = M('tfb_yhb_member')->where("openid = '$openid'")->getField(
            'mobile');
        $re_channel = M('tbatch_channel')->where("id='$label_id'")->find();
        $map_c = array(
            'batch_type' => $this->batch_type, 
            'batch_id' => $re_channel['batch_id'], 
            'status' => '1');
        $ruleInfo = M('tcj_rule')->where($map_c)->find();
        $node_id = C('yhb.node_id');
        // 抽奖接口
        import("@.Vendor.CjInterface");
        C(include (CONF_PATH . 'Label/config.php'));
        $req = new CjInterface();
        $send_arr = array(
            'node_id' => $node_id, 
            'batch_id' => $re_channel['batch_id'],  // 活动id
            'award_type' => $ruleInfo['jp_set_type'],  // 单，多类型
            'award_times' => $ruleInfo['phone_day_count'],  // 每日限制次数
            'award_count' => $ruleInfo['phone_total_count'],  // 总总将次数
            'day_part' => $ruleInfo['phone_day_part'], 
            'total_part' => $ruleInfo['phone_total_part'], 
            'phone_no' => $mobile, 
            'label_id' => $label_id, 
            'channel_id' => $re_channel['channel_id'], 
            'batch_type' => $this->batch_type, 
            'total_rate' => $ruleInfo['total_chance'], 
            'ip' => get_client_ip(), 
            'cj_rule_id' => $ruleInfo['id'], 
            'wx_open_id' => $openid);
        $iresp = $req->cj_send($send_arr);
        if ($iresp['resp_id'] == '0000') {
            $re_num = M('tfb_yhb_goods')->where("label_id='$label_id'")->setInc(
                'get_num');
            if (! $re_num) {
                $this->ajaxReturn(
                    array(
                        'code' => '0000', 
                        'info' => '领取失败:ERROR_GET_NUM'), 'json');
            }
            $this->ajaxReturn(
                array(
                    'code' => '0000', 
                    'info' => '领取成功'), 'json');
        } else {
            $this->ajaxReturn(
                array(
                    'code' => $iresp['resp_id'], 
                    'info' => '领取失败'), 'json');
        }
    }

    public function preferential() {
        // 判断是否满足领取条件
        $goods_id = I('get.id', '', 'mysql_real_escape_string');
        $label_id = I('get.label_id', '', 'mysql_real_escape_string');
        if ($goods_id == null) {
            $goods_id = M('tfb_yhb_goods')->where("label_id = '$label_id'")->getField(
                'goods_id');
        }
        // 判断是否满足领取条件
        $re_get = M()->table('tfb_yhb_goods a')
            ->join('tgoods_info b ON a.goods_id = b.goods_id')
            ->join('tbatch_info c ON b.goods_id = c.goods_id')
            ->join('tmarketing_info d ON c.m_id = d.id')
            ->field(
            'a.line_status,b.status,d.start_time,d.end_time,d.status as m_status')
            ->where("a.goods_id = '$goods_id'")
            ->find();
        if ($re_get['status'] == '1') {
            $this->error('优惠券已停用');
        }
        if ($re_get['line_status'] == '2' && $re_get['m_status'] == '2') {
            $this->error('优惠券已停用,无法查看');
        }
        $latlng = cookie('yhb_latlng');
        // 优惠券详情
        $re_goods = M()->table('tgoods_info a')
            ->join('tfb_yhb_goods b ON a.goods_id = b.goods_id')
            ->join('tbatch_info c ON a.goods_id = c.goods_id')
            ->join('tmarketing_info d ON c.m_id = d.id')
            ->field(
            'b.label_id,a.goods_image,a.goods_name,d.start_time,d.end_time,c.remain_num,b.get_num,a.goods_desc')
            ->where("a.goods_id = '$goods_id'")
            ->find();
        $re_goods['start_time'] = date('Y-m-d', 
            strtotime($re_goods['start_time']));
        $re_goods['end_time'] = date('Y-m-d', strtotime($re_goods['end_time']));
        $re_goods['goods_id'] = $goods_id;
        if ($re_goods['storage_num'] == '-1') {
            $re_goods['storage_num'] = '不限';
        }
        // 相关门店
        $re_store = M()->table('tgoods_info a')
            ->join('tgroup_pos_relation b ON a.pos_group = b.group_id')
            ->join('tstore_info c ON b.store_id = c.store_id')
            ->join('tfb_yhb_store d ON c.store_id = d.store_id ')
            ->field(
            'c.store_name,c.address,c.lbs_x,c.lbs_y,c.store_phone,c.store_id')
            ->where("a.goods_id = '$goods_id'")
            ->select();
        if ($latlng != null) {
            $arr = explode(',', $latlng);
            $lat = $arr[0];
            $lng = $arr[1];
            foreach ($re_store as $key => $value) {
                $lat1 = $value['lbs_x'];
                $lng1 = $value['lbs_y'];
                $distance = $this->getDistance($lat, $lng, $lat1, $lng1);
                $distance = round($distance);
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
                $re_store[$key]['distance'] = $km;
            }
        }
        $wein_appid = $this->wap_sess_name;
        $wein_appid = session($wein_appid);
        $flag = $wein_appid['att_flag'];
        $re_goods['goods_desc'] = strip_tags(htmlspecialchars_decode($re_goods['goods_desc']));
        $this->assign('flag', $flag);
        $this->assign('wein_appid', $wein_appid);
        $this->assign('re_store', $re_store);
        $this->assign('re_goods', $re_goods);
        $this->display('YhbWap/preferential');
    }

    public function store() {
        $store_id = I('get.store_id');
        $distance = I('get.distance');
        $latlng = cookie('yhb_latlng');
        // 门店详情
        $re_store = M()->table('tfb_yhb_store a')
            ->join('tfb_yhb_node_info b ON a.merchant_id = b.id')
            ->join('tstore_info c ON a.store_id = c.store_id')
            ->field(
            'c.store_name,c.address,c.store_phone,b.description,c.store_pic,b.spending_av_per')
            ->where("a.store_id = '$store_id'")
            ->find();
        $re_store['distance'] = $distance;
        
        // 优惠券
        $re_goods = M()->table('tfb_yhb_goods a')
            ->join('tfb_yhb_store b ON a.merchant_id = b.merchant_id')
            ->join('tgoods_info c ON a.goods_id = c.goods_id')
            ->field(
            'a.get_num,c.goods_name,c.goods_image,c.begin_time,c.end_time,c.storage_num,c.remain_num,c.goods_id')
            ->where(
            "b.store_id = '$store_id' and c.status = '0' and a.line_status = '3'")
            ->select();
        foreach ($re_goods as $key => $value) {
            $begin_time = $value['begin_time'];
            $begin_time = strtotime($begin_time);
            $re_goods[$key]['begin_time'] = date("Y-m-d", $begin_time);
            $end_time = $value['end_time'];
            $end_time = strtotime($end_time);
            $re_goods[$key]['end_time'] = date("Y-m-d", $end_time);
        }
        // 分店信息
        $merchant_id = M('tfb_yhb_store')->where("store_id = '$store_id'")->getField(
            'merchant_id');
        $other_store = M()->table('tfb_yhb_store a')
            ->join('tstore_info b ON a.store_id = b.store_id')
            ->field(
            'b.lbs_x,b.lbs_y,b.store_name,b.address,b.store_phone,a.store_id')
            ->where(
            "a.merchant_id = '$merchant_id' and a.store_id != '$store_id'")
            ->select();
        if ($latlng != null) {
            $arr = explode(',', $latlng);
            $lat = $arr[0];
            $lng = $arr[1];
            foreach ($other_store as $key => $value) {
                $lat1 = $value['lbs_x'];
                $lng1 = $value['lbs_y'];
                if ($lat1 != '0.00000000000000000000' &&
                     $lng1 != '0.00000000000000000000') {
                    $distance = $this->getDistance($lat, $lng, $lat1, $lng1);
                    $distance = round($distance);
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
                    $other_store[$key]['distance'] = $km;
                } else {
                    $other_store[$key]['distance'] = '未知';
                }
            }
        }
        $this->assign('other_store', $other_store);
        $this->assign('re_goods', $re_goods);
        $this->assign('re_store', $re_store);
        $this->display('YhbWap/store');
    }

    /**
     * 找商户
     * 请求地址：index.php?g=Yhb&m=YhbWap&a=storefind&area_code=1,2,3&cate_code=1,2&sort_by=1&key=kfc&in_ajax=1&p=1
     * area_code:地区 cate_code:分类 sort_by:排序 0 默认 1 距离
     */
    public function storefind() {
        $gets = I('get.');
        $area_val = I('get.area_val');
        $cate_val = I('get.cate_val');
        $px_val = I('get.px_val');
        $keyword = I('get.keyword', '', mysql_real_escape_string);
        $where = array(
            '_string' => "a.store_id = b.store_id and a.village_code = c.village_code and a.line_status = '2' and a.merchant_id = d.id");
        $order = 'a.order_sort desc ,b.add_time';
        
        // 区+街道+小区
        $area_code = mysql_real_escape_string($gets['area_code']);
        if ($area_code != '') {
            $arr = explode(',', $area_code);
            $this->assign('town_code', $arr[0]);
            $this->assign('city_code', $arr[0] . $arr[1]);
            if ($arr[0])
                $where['b.town_code'] = $arr[0];
            if ($arr[1])
                $where['c.street_code'] = $arr[1];
            if ($arr[2])
                $where['c.village_code'] = $arr[2];
        }
        
        $cate_code = mysql_real_escape_string($gets['cate_code']);
        if ($cate_code != '') {
            $arr = explode(',', $cate_code);
            $where['_string'] .= " and a.merchant_id = d.id ";
            if ($arr[0])
                $where['d.parent_id'] = $arr[0];
            if ($arr[1])
                $where['d.catalog_id'] = $arr[1];
        }
        
        // 优惠券名模糊查询
        if ($keyword) {
            $where['_string'] .= " AND b.store_name like '%$keyword%'";
        }
        $sort_by = intval($gets['sort_by']);
        $latlng = cookie('yhb_latlng');
        $arr = explode(',', $latlng);
        $lat = $arr[0];
        $lng = $arr[1];
        cookie('lat', 'lat', 7200);
        cookie('lng', 'lng', 7200);
        if ($sort_by == 1 && $latlng != null) {
            $order = "(b.lbs_x - {$lat})*(b.lbs_x - {$lat})+ (b.lbs_y - {$lng})*(b.lbs_y - {$lng})";
        }
        if ($sort_by == 2) {
            $order = "d.spending_av_per desc ,b.add_time";
        }
        if ($sort_by == 3) {
            $order = "d.spending_av_per ,b.add_time";
        }
        $nowP = I('p', null, 'mysql_real_escape_string'); // 页数
        empty($nowP) ? $nowP = 1 : $nowP;
        $pageCount = 15; // 每页显示条数
        
        $list = M()->table(
            "tfb_yhb_store a, tstore_info b, tfb_yhb_node_info d, tfb_yhb_city_code c")
            ->where($where)
            ->order($order)
            ->limit(($nowP - 1) * $pageCount, $pageCount)
            ->field(
            'b.lbs_x,b.lbs_y,b.store_name,c.village,a.store_id,b.store_pic,d.spending_av_per,d.id')
            ->select();
        foreach ($list as $key => $value) {
            $store_id = $value['store_id'];
            $re_goods = M()->table('tfb_yhb_goods a')
                    ->join('tfb_yhb_store b ON a.merchant_id = b.merchant_id')
                    ->join('tgoods_info c ON a.goods_id = c.goods_id')
                    ->field(
                    'a.get_num,c.goods_name,c.goods_image,c.begin_time,c.end_time,c.storage_num,c.remain_num,c.goods_id')
                    ->where(
                    "b.store_id = '$store_id' and c.status = '0' and a.line_status = '3'")
                    ->count();
            if ($re_goods) {
                $list[$key]['goods_flag'] = '1';
            }
        }
        // 定位计算分级
        if ($list && $latlng != null) {
            foreach ($list as $key => $value) {
                $lat1 = $value['lbs_x'];
                $lng1 = $value['lbs_y'];
                if ($lat1 != '0.00000000000000000000' &&
                     $lng1 != '0.00000000000000000000') {
                    $distance = $this->getDistance($lat, $lng, $lat1, $lng1);
                    $distance = round($distance);
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
                    $list[$key]['distance'] = $km;
                } else {
                    $list[$key]['distance'] = '未知';
                }
            }
        }
        
        if ($area_val == null) {
            $area_val = "全部商区";
        }
        if ($cate_val == null) {
            $cate_val = "全部分类";
        }
        if ($sort_by == '0') {
            $sort_val = "智能排序";
        }
        if ($sort_by == '1') {
            $sort_val = "离我最近";
        }
        if ($sort_by == '2') {
            $sort_val = "人均最高";
        }
        if ($sort_by == '3') {
            $sort_val = "人均最低";
        }
        $this->assign('cate_code', $gets['cate_code']);
        $this->assign('area_code', $gets['area_code']);
        $this->assign('sort_val', $sort_val);
        $this->assign('sort_by', $sort_by);
        $this->assign('area_val', $area_val);
        $this->assign('cate_val', $cate_val);
        $this->assign('keyword', $keyword);
        $this->assign('list', $list);
        if (I('in_ajax', 0, 'intval') == 1) {
            $this->display('YhbWap/storefind_list');
        } else {
            $gets = I('get.');
            $gets['in_ajax'] = 1;
            $gets['p'] = 2;
            $nextUrl = U('', $gets);
            $this->assign('nextUrl', $nextUrl);
            $this->display('YhbWap/storefind');
        }
    }

    /**
     * 找优惠
     * 请求地址：index.php?g=Yhb&m=YhbWap&a=preferentialfind&area_code=1,2,3&cate_code=1,2&sort_by=1&key=kfc&in_ajax=1&p=1
     * area_code:地区 cate_code:分类 sort_by:排序 0 默认 1 距离
     */
    public function preferentialfind() {
        $gets = I('get.');
        $area_val = I('get.area_val');
        $cate_val = I('get.cate_val');
        $px_val = I('get.px_val');
        $keyword = I('get.keyword');
        $where = array(
            '_string' => "a.goods_id = g.goods_id and b.m_id = f.id and b.goods_id = g.goods_id and g.status = '0' and a.line_status = '3' and f.batch_type = '2000'");
        // 区+街道+小区
        $area_code = mysql_real_escape_string($gets['area_code']);
        if ($area_code != '') {
            $arr = explode(',', $area_code);
            $this->assign('town_code', $arr[0]);
            $this->assign('city_code', $arr[0] . $arr[1]);
            $town_code = $arr[0];
            $street_code = $arr[1];
            $village_code = $arr[2];
            $subquery = "SELECT * FROM tpos_group_store c, tfb_yhb_store d WHERE c.group_id = g.pos_group AND c.store_id = d.store_id ";
            if ($street_code != '') {
                $subquery .= " AND d.street_code = '{$street_code}' ";
            }
            if ($village_code != '') {
                $subquery .= " AND d.village_code = '{$village_code}'";
            }
            if ($town_code != '') {
                $subquery .= " AND d.town_code = '{$town_code}'";
            }
            $where['_string'] .= " and exists ( $subquery )";
        }
        // 分类
        $cate_code = mysql_real_escape_string($gets['cate_code']);
        if ($cate_code != '') {
            $more_tbl = "";
            $type = "select * from tfb_yhb_node_info e where a.merchant_id = e.id";
            $arr = explode(',', $cate_code);
            $parent_id = $arr[0];
            $catalog_id = $arr[1];
            $more_tbl .= 'tfb_yhb_node_info d, ';
            if ($parent_id != '') {
                $type .= " AND e.parent_id = '{$parent_id}'";
            }
            if ($catalog_id != '') {
                $type .= " AND e.catalog_id = '{$catalog_id}'";
            }
            $where['_string'] .= " and exists ($type) ";
        }
        // 排序
        $order = "a.line_status desc";
        $sort_by = intval($gets['sort_by']);
        if ($sort_by == 0) {
            $order .= ",a.order_sort desc , f.add_time";
        }
        if ($sort_by == 1) {
            $order .= ",f.add_time desc";
        }
        if ($sort_by == 2) {
            $order .= ",a.get_num desc ,f.add_time";
        }
        
        // 优惠券名模糊查询
        if ($keyword) {
            $where['_string'] .= " AND g.goods_name like '%$keyword%'";
        }
        $nowP = I('p', null, 'mysql_real_escape_string'); // 页数
        empty($nowP) ? $nowP = 1 : $nowP;
        $pageCount = 20; // 每页显示条数
        
        $list = M()->table(
            "tfb_yhb_goods a, tbatch_info b,tmarketing_info f,tgoods_info g ")
            ->where($where)
            ->order($order)
            ->limit(($nowP - 1) * $pageCount, $pageCount)
            ->field(
            'a.line_status,g.status,f.start_time,f.end_time,b.remain_num,a.get_num,a.goods_id,g.goods_name,
            b.batch_img,b.storage_num,a.order_sort,b.add_time')
            ->select();
        foreach ($list as $key => $value) {
            if ($value['remain_num'] == '-1') {
                $list[$key]['remain_num'] = '不限';
            }
            $list[$key]['start_time'] = date('Y-m-d', 
                strtotime($value['start_time']));
            $list[$key]['end_time'] = date('Y-m-d', 
                strtotime($value['end_time']));
        }
        if ($area_val == null) {
            $area_val = "全部商区";
        }
        if ($cate_val == null) {
            $cate_val = "全部分类";
        }
        if ($sort_by == '0') {
            $sort_val = "智能排序";
        }
        if ($sort_by == '1') {
            $sort_val = "最新更新";
        }
        if ($sort_by == '2') {
            $sort_val = "热门优惠";
        }
        $this->assign('cate_code', $gets['cate_code']);
        $this->assign('area_code', $gets['area_code']);
        $this->assign('sort_val', $sort_val);
        $this->assign('sort_by', $sort_by);
        $this->assign('area_val', $area_val);
        $this->assign('cate_val', $cate_val);
        $this->assign('keyword', $keyword);
        $this->assign('list', $list);
        if (I('in_ajax', 0, 'intval') == 1) {
            $this->display('YhbWap/pre_list');
        } else {
            $gets = I('get.');
            $gets['in_ajax'] = 1;
            $gets['p'] = 2;
            $nextUrl = U('', $gets);
            $this->assign('nextUrl', $nextUrl);
            $this->display('YhbWap/preferentialfind');
        }
    }

    public function voucherList() {
        $type = I('get.type', '', intval);
        $openid = $this->openid;
        $where = "d.openid = '$openid' and  a.status = '0'";
        $time = date('YmdHis', time());
        if ($type == '3') {
            $where .= " AND a.use_status != '2' AND a.end_time < '$time'";
        }
        if ($type == '0' || $type == '2') {
            $where .= " AND a.use_status = '$type' AND a.end_time > '$time'";
        }
        $nowP = I('p', null, 'mysql_real_escape_string'); // 页数
        empty($nowP) ? $nowP = 1 : $nowP;
        $pageCount = 20; // 每页显示条数
        $list = M()->table("tbarcode_trace a")->join(
            'tfb_yhb_goods b ON a.goods_id = b.goods_id')
            ->join('tgoods_info c ON a.goods_id = c.goods_id')
            ->join('tbarcode_trace_ext d ON a.id = d.bar_id')
            ->join('tbatch_info e ON a.goods_id = e.goods_id')
            ->join('tfb_yhb_node_info f ON b.merchant_id = f.id')
            ->field(
            'a.barcode_bmp,a.goods_id,a.id,a.use_status,a.end_time,c.goods_name,e.batch_img,f.merchant_short_name')
            ->group('a.id')
            ->order('trans_time Desc')
            ->where($where)
            ->limit(($nowP - 1) * $pageCount, $pageCount)
            ->select();
        if ($type == null) {
            foreach ($list as $key => $value) {
                $end_time = strtotime($value['end_time']);
                if ($end_time < time()) {
                    $list[$key]['use_status'] = 3;
                }
            }
        }
        foreach ($list as $key => $value) {
            if ($type == 3) {
                $list[$key]['use_status'] = 3;
            }
            $list[$key]['end_time'] = date('Y-m-d', 
                strtotime($value['end_time']));
        }
        
        $this->assign('type', $type);
        $this->assign('list', $list);
        if (I('in_ajax', 0, 'intval') == 1) {
            
            $this->display('YhbWap/voucher_goods');
        } else {
            $gets = I('get.');
            $gets['in_ajax'] = 1;
            $gets['p'] = 2;
            $nextUrl = U('', $gets);
            $this->assign('nextUrl', $nextUrl);
            
            $this->display('YhbWap/voucherList');
        }
    }

    public function voucher() {
        $id = I('get.id', '', 'mysql_real_escape_string');
        $openid = $this->openid;
        $latlng = cookie('yhb_latlng');
        $where = "a.id = '$id' and d.openid = '{$openid}'";
        $list = M()->table("tbarcode_trace a")->join(
            'tfb_yhb_goods b ON a.goods_id = b.goods_id')
            ->join('tgoods_info c ON a.goods_id = c.goods_id')
            ->join('tbarcode_trace_ext d ON a.id = d.bar_id')
            ->join('tbatch_info e ON a.goods_id = e.goods_id')
            ->join('tfb_yhb_node_info f ON b.merchant_id = f.id')
            ->field(
            'c.goods_desc,a.assist_number,a.barcode_bmp,a.begin_time,a.goods_id,a.id,a.use_status
            ,a.end_time,c.goods_name,f.merchant_short_name')
            ->order('trans_time Desc')
            ->where($where)
            ->find();
        
        if ($list['use_status'] == '0') {
            $list['use_status'] = '未使用';
        }
        if ($list['use_status'] == '2') {
            $list['use_status'] = '已使用';
        }
        $now = date('YmdHis', time());
        if ($list['end_time'] < $now) {
            $list['use_status'] = '已过期';
        }
        
        $list['end_time'] = date('Y.m.d', strtotime($list['end_time']));
        $list['begin_time'] = date('Y.m.d', strtotime($list['begin_time']));
        $list['barcode_bmp'] = $list['barcode_bmp'] ? 'data:image/png;base64,' .
             base64_encode(
                $this->_bar_resize(base64_decode($list['barcode_bmp']), 'png')) : '';
        $goods_id = $list['goods_id'];
        // 相关门店
        $re_store = M()->table('tgoods_info a')
            ->join('tgroup_pos_relation b ON a.pos_group = b.group_id')
            ->join('tstore_info c ON b.store_id = c.store_id')
            ->join('tfb_yhb_store d ON c.store_id = d.store_id ')
            ->field(
            'c.store_name,c.store_id,c.address,c.lbs_x,c.lbs_y,c.store_phone')
            ->where("a.goods_id = '$goods_id'")
            ->select();
        if ($latlng != null) {
            $arr = explode(',', $latlng);
            $lat = $arr[0];
            $lng = $arr[1];
            foreach ($re_store as $key => $value) {
                $lat1 = $value['lbs_x'];
                $lng1 = $value['lbs_y'];
                $distance = $this->getDistance($lat, $lng, $lat1, $lng1);
                $distance = round($distance / 1000);
                $km = $distance;
                if ($distance < '1') {
                    $km = '<1';
                }
                if ($distance > '10') {
                    $km = '>10';
                }
                $re_store[$key]['distance'] = $km;
            }
        }
        $this->assign('re_store', $re_store);
        $this->assign('list', $list);
        $this->display('YhbWap/voucher');
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
    
    // 二维数组排序
    public function multi_array_sort($multi_array, $sort_key, $sort = SORT_ASC) {
        if (is_array($multi_array)) {
            foreach ($multi_array as $row_array) {
                if (is_array($row_array)) {
                    $key_array[] = $row_array[$sort_key];
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }
        array_multisort($key_array, $sort, $multi_array);
        return $multi_array;
    }

    public function sq_info() {
        $town_code = I('post.town_code', '', 'mysql_real_escape_string');
        $area_code = I('post.area_code', '', 'mysql_real_escape_string');
        $data = array(
            'town_code' => $town_code);
        $town = M("tfb_yhb_city_code")->where($data)
            ->order('city_level asc')
            ->select();
        $list = array();
        foreach ($town as $arr) {
            if ($arr['city_level'] == '1') {
                $list[$arr['street_code']] = $arr;
            } else {
                $list[$arr['street_code']]['child'][] = $arr;
            }
        }
        $area_code = explode(',', $area_code);
        $st_code = $area_code[0] . $area_code[1] . $area_code[2];
        $this->assign('area_code', $st_code);
        $this->assign('city_code', $area_code[0] . $area_code[1]);
        $this->assign('town_code', $town_code);
        $this->assign('list', $list);
        $this->display();
    }

    public function cata_info() {
        $cate_code = I('post.cate_code', '', 'mysql_real_escape_string');
        $cata = M("tfb_yhb_catalog")->order('id asc')->select();
        $list_cata = array();
        foreach ($cata as $arr) {
            if ($arr['parent_id'] == '0') {
                $list_cata[$arr['id']] = $arr;
            } else {
                $list_cata[$arr['parent_id']]['child'][] = $arr;
            }
        }
        $cate_code = explode(',', $cate_code);
        $this->assign('cate_one', $cate_code[0]);
        $this->assign('cate_two', $cate_code[0] . $cate_code[1]);
        $this->assign('list_cata', $list_cata);
        $this->display('cata_info');
    }
    
    // 微信授权登录
    public function _yhb_checklogin() {
        if (session('?' . $this->wap_sess_name))
            return true;
        $login = false;
        $userid = '';
        $backurl = U('', I('get.'), '', '', true);
        $backurl = urlencode($backurl);
        $jumpurl = U('Yhb/YhbWeixinLoginNode/index', 
            array(
                'id' => $this->id, 
                'type' => 0, 
                'backurl' => $backurl));
        redirect($jumpurl);
    }
    
    // 手机发送验证码
    public function sendIdentifCode() {
        $phoneNo = I('bind_phone');
        if (! check_str($phoneNo, 
            array(
                'null' => false, 
                'strtype' => 'mobile'), $error)) {
            $this->error("手机号{$error}" . $phoneNo);
        }
        // 测试环境不下发，验证码直接为1111
        // if (!is_production()) {
        // $groupChaoWaiCode = array('number' => 1111, 'add_time' => time(),
        // 'phoneNo' => $phoneNo);
        // session('groupChaoWaiCode', $groupChaoWaiCode);
        // $this->ajaxReturn("success", "验证码已发送", 1);
        // }
        // 发送频率验证
        $groupChaoWaiCode = session('groupChaoWaiCode');
        if (! empty($groupChaoWaiCode) &&
             (time() - $groupChaoWaiCode['add_time']) < $this->expiresTime) {
            exit(
                json_encode(
                    array(
                        'info' => "动态密码发送过于频繁!", 
                        'status' => 0)));
        }
        $num = mt_rand(1000, 9999);
        // $YhbSms = D('YhbSms', 'Service');
        // dump($YhbSms);
        import('@.Service.YhbSmsService');
        $yhbsms = new YhbSmsService();
        $yhbsms->sendVerifyCodeSms($phoneNo, $num);
        $groupChaoWaiCode = array(
            'number' => $num, 
            'add_time' => time(), 
            'phoneNo' => $phoneNo);
        
        session('groupChaoWaiCode', $groupChaoWaiCode);
        exit(
            json_encode(
                array(
                    'info' => "动态密码已发送", 
                    'status' => 1)));
    }

    public function _bar_resize($data, $other) {
        $im = $this->_img_resize($data, 3);
        if ($im !== false) {
            ob_start();
            switch ($other) {
                case 'gif':
                    imagegif($im);
                    break;
                case 'jpg':
                case 'jpeg':
                    imagejpeg($im);
                    break;
                case 'png':
                    imagepng($im);
                    break;
                case 'wbmp':
                    imagewbmp($im);
                    break;
                default:
                    return false;
                    break;
            }
            imagedestroy($im);
            $new_img = ob_get_contents();
            ob_end_clean();
            return $new_img;
        } else {
            return false;
        }
    }

    public function _img_resize($data, $fdbs) {
        // Resize
        $source = imagecreatefromstring($data);
        $s_white_x = 0; //
        $s_white_y = 0; //
        $s_w = imagesx($source); // 原图宽度
        $new_img_width = ($s_w) * $fdbs;
        $new_img_height = $new_img_width;
        
        // 新的偏移量
        $d_white_x = ($new_img_width - $s_w * $fdbs) / 2;
        $d_white_y = $d_white_x;
        
        // Load
        $thumb = imagecreate($new_img_width, $new_img_height);
        // $red = imagecolorallocate($thumb, 255, 255, 255);
        
        imagecopyresized($thumb, $source, $d_white_x, $d_white_y, $s_white_x, 
            $s_white_y, $s_w * $fdbs, $s_w * $fdbs, $s_w, $s_w);
        return $thumb;
    }
}
