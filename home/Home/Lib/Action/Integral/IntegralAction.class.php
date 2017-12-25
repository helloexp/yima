<?php

class IntegralAction extends IntegralAuthAction {

    public $BATCH_TYPE = CommonConst::BATCH_TYPE_INTEGRAL;
    // 旺财小店 tmarket类型
    public $CHANNEL_TYPE = CommonConst::CHANNEL_TYPE_INTEGRAL;

    public $CHANNEL_SNS_TYPE = CommonConst::SNS_TYPE_INTEGRAL;

    public $_authAccessMap = '*';

    public function _initialize() {
        parent::_initialize();
        $allArticle = D('TymNews')->getArticleTitle(76, false, 0, 3);
        $this->assign("allArticle", $allArticle);
    }

    public function index() {
        $channelInfo = M('tchannel')->where(
            array(
                'node_id' => $this->node_id, 
                'type' => $this->CHANNEL_TYPE, 
                'sns_type' => $this->CHANNEL_SNS_TYPE))->find();
        if (! $channelInfo) { // 不存在则添加渠道
                              // 营销活动不存在则新增
            $channel_arr = array(
                'name' => '积分商城', 
                'type' => $this->CHANNEL_TYPE, 
                'sns_type' => $this->CHANNEL_SNS_TYPE, 
                'status' => '1', 
                'start_time' => date('YmdHis'), 
                'end_time' => date('YmdHis', strtotime("+1 year")), 
                'add_time' => date('YmdHis'), 
                'click_count' => 0, 
                'cj_count' => 0, 
                'send_count' => 0, 
                'node_id' => $this->node_id);
            $cid = M('tchannel')->add($channel_arr);
            if (! $cid) {
                $this->error('添加默认积分商城渠道号失败');
            }
            node_log("添加默认积分商城渠道号", "添加默认积分商城渠道号" . $cid, "添加默认积分商城渠道号");
        }
        $marketInfo = M('tmarketing_info')->where(
            array(
                'batch_type' => $this->BATCH_TYPE, 
                'node_id' => $this->node_id))->find();
        if (! $marketInfo) { // 不存在则自动新建该营销活动和渠道
            $m_arr = array(
                'batch_type' => $this->BATCH_TYPE, 
                'name' => '积分商城', 
                'status' => '1', 
                'start_time' => date('YmdHis'), 
                'end_time' => date('YmdHis', strtotime("+10 year")), 
                'add_time' => date('YmdHis'), 
                'click_count' => 0, 
                'cj_count' => 0, 
                'send_count' => 0, 
                'node_id' => $this->node_id, 
                'batch_come_from' => session('batch_from') ? session(
                    'batch_from') : '1');
            
            $m_id = M('tmarketing_info')->add($m_arr);
            if (! $m_id) {
                $this->error('添加积分商城失败');
            }
            node_log("添加积分商城活动", "添加积分商城活动" . $m_id, "添加积分商城活动");
        }
        $integral_url = M("tintegral_node_config")->where(
            array(
                'node_id' => $this->node_id))->getField('integral_url');
        if (! $integral_url) {
            $m_info = M('tmarketing_info')->where(
                array(
                    'node_id' => $this->node_id, 
                    'batch_type' => $this->BATCH_TYPE))->find();
            $channel_id = M('tchannel')->where(
                array(
                    'node_id' => $this->node_id, 
                    'type' => $this->CHANNEL_TYPE, 
                    'sns_type' => $this->CHANNEL_SNS_TYPE))->getField('id');
            $label_id = get_batch_channel($m_info['id'], $channel_id, 
                $this->BATCH_TYPE, $this->node_id);
            $integral_url = make_short_url(
                U('Label/Label/index', 
                    array(
                        'id' => $label_id), '', '', true));
            $this->integralConfigTable($integral_url);
        }
        redirect(U('Integral/Integral/integralMarketing'));
    }

    public function preview() {
        // 小店的m_id
        $m_info = M('tmarketing_info')->where(
            array(
                'node_id' => $this->node_id, 
                'batch_type' => $this->BATCH_TYPE))->find();
        // $logo = M('tecshop_banner')->where(array('m_id' => $m_info['id'],
        // 'node_id' => $this->node_id, 'ban_type' => '1'))->find();
        // $logo_url = get_upload_url($logo['img_url']);
        // 店铺地址
        $channel_id = M('tchannel')->where(
            array(
                'node_id' => $this->node_id, 
                'type' => $this->CHANNEL_TYPE, 
                'sns_type' => $this->CHANNEL_SNS_TYPE))->getField('id');
        $label_id = get_batch_channel($m_info['id'], $channel_id, 
            $this->BATCH_TYPE, $this->node_id);
        $today = date('Ymd');
        $yesterday = date('Ymd', strtotime("-1 day"));
        $batch_type = $this->BATCH_TYPE;
        $batch_id = $m_info['id'];
        $_get = I('get.');
        // 查询
        $map = array(
            'batch_type' => $batch_type, 
            'batch_id' => $batch_id);
        $type = I('type');
        if (empty($type)) {
            $type = '1';
        }
        if ($type == 1) {
            $_get['begin_date'] = $begin_date = I('begin_date', 
                dateformat("-7 days", 'Ymd'));
            $_get['end_date'] = $end_date = I('end_date', 
                dateformat("0 days", 'Ymd'));
            // 查询日期
            $map['day'] = array();
            if ($begin_date != '') {
                $map['day'][] = array(
                    'EGT', 
                    $begin_date);
            }
            if ($end_date != '') {
                $map['day'][] = array(
                    'ELT', 
                    $end_date);
            }
        } elseif ($type == '2') {
            $_get['begin_date'] = $begin_date = I('begin_date', 
                dateformat("-30 days", 'Ymd'));
            $_get['end_date'] = $end_date = I('end_date', 
                dateformat("0 days", 'Ymd'));
            // 查询日期
            $map['day'] = array();
            if ($begin_date != '') {
                $map['day'][] = array(
                    'EGT', 
                    $begin_date);
            }
            if ($end_date != '') {
                $map['day'][] = array(
                    'ELT', 
                    $end_date);
            }
        } elseif ($type == '3') {
            $_get['begin_date'] = $begin_date = I('begin_date', 
                dateformat("-1 year", 'Ymd'));
            $_get['end_date'] = $end_date = I('end_date', 
                dateformat("0 days", 'Ymd'));
            // 查询日期
            $map['day'] = array();
            if ($begin_date != '') {
                $map['day'][] = array(
                    'EGT', 
                    $begin_date);
            }
            if ($end_date != '') {
                $map['day'][] = array(
                    'ELT', 
                    $end_date);
            }
        } elseif ($type == '4') {
            $_get['begin_date'] = $begin_date = I('startTime');
            $_get['end_date'] = $end_date = I('endTime');
            // 查询日期
            $map['day'] = array();
            if ($begin_date != '') {
                $map['day'][] = array(
                    'EGT', 
                    $begin_date);
            }
            if ($end_date != '') {
                $map['day'][] = array(
                    'ELT', 
                    $end_date);
            }
        }
        $this->assign('startTime', $begin_date);
        $this->assign('endTime', $end_date);
        $shop_jsChartDataClick = array(); // 小店PV访问量
        $shop_jsChartDataOrder = array(); // 小店订单数
        $shop_jsChartDataAmt = array(); // 小店销售额
        $shop_data = array(
            'PV' => array(
                date('Ymd') => 0, 
                date('Ymd', strtotime("-1 day")) => 0), 
            'order' => array(
                date('Ymd') => 0, 
                date('Ymd', strtotime("-1 day")) => 0), 
            'saleamt' => array(
                date('Ymd') => 0, 
                date('Ymd', strtotime("-1 day")) => 0));
        
        // 小店访问量
        $pv_arr = M('Tdaystat')->where($map)
            ->field("batch_type,batch_id,day,sum(click_count) click_count")
            ->order('day')
            ->group("day")
            ->select();
        // 小店-计算出JS值
        foreach ($pv_arr as $v) {
            $shop_jsChartDataClick[$v['day']] = array(
                $v['day'], 
                $v['click_count'] * 1);
            if ($v['day'] == $today)
                $shop_data['PV'][$today] = $v['click_count'] * 1;
            if ($v['day'] == $yesterday)
                $shop_data['PV'][$yesterday] = $v['click_count'] * 1;
        }
        // 小店订单数
        $order_map = array(
            // 'order_type' => '2',
            'order_status' => '0', 
            'pay_status' => '2', 
            'node_id' => $this->node_id);
        // 小店查询日期
        $order_map['add_time'] = array();
        if ($begin_date != '') {
            $order_map['add_time'][] = array(
                'EGT', 
                $begin_date . '000000');
        }
        if ($end_date != '') {
            $order_map['add_time'][] = array(
                'ELT', 
                $end_date . '235959');
        }
        $order_arr = M("tintegral_order_info")->field(
            "count(order_id) as order_count,substr(add_time,'1',8) as day,sum(order_amt) as order_amt")
            ->where($order_map)
            ->group("substr(add_time,1,8)")
            ->select();
        
        // 小店-计算出JS值
        foreach ($order_arr as $v) {
            $shop_jsChartDataOrder[$v['day']] = array(
                $v['day'], 
                $v['order_count'] * 1);
            $shop_jsChartDataAmt[$v['day']] = array(
                $v['day'], 
                $v['order_amt'] * 1);
            
            if ($v['day'] == $today) {
                $shop_data['order'][$today] = $v['order_count'] * 1;
                $shop_data['saleamt'][$today] = $v['order_amt'] * 1;
            }
            if ($v['day'] == $yesterday) {
                $shop_data['order'][$yesterday] = $v['order_count'] * 1;
                $shop_data['saleamt'][$yesterday] = $v['order_amt'] * 1;
            }
        }
        // 商品数量
        $goods_count = M()->table("tintegral_goods_ex e")->join(
            'tbatch_info b on b.id=e.b_id')
            ->where(
            array(
                'e.node_id' => $this->node_id, 
                'b.status' => '0'))
            ->count();
        if (is_array($shop_jsChartDataClick)) {
            foreach ($shop_jsChartDataClick as $kk => $vv) {
                if (! isset($shop_jsChartDataOrder[$kk])) {
                    $shop_jsChartDataOrder[$kk] = array(
                        $vv[0], 
                        0);
                }
                if (! isset($shop_jsChartDataAmt[$kk])) {
                    $shop_jsChartDataAmt[$kk] = array(
                        $vv[0], 
                        0);
                }
            }
        }
        // $integral_url=M("tintegral_node_config")->where(array('node_id'=>$this->node_id))->getField('integral_url');
        $integralInfo = M("tintegral_node_config")->where(
            array(
                'node_id' => $this->node_id))
            ->Field('integral_url,qcode,logo_flag,logo_path,integral_shop_name')
            ->find();
        if (! $integralInfo['integral_url']) {
            $integral_url = make_short_url(
                U('Label/Label/index', 
                    array(
                        'id' => $label_id), '', '', true));
            $this->integralConfigTable($integral_url);
            $integralInfo = M("tintegral_node_config")->where(
                array(
                    'node_id' => $this->node_id))
                ->Field(
                'integral_url,qcode,logo_flag,logo_path,integral_shop_name')
                ->find();
        }
        if ($integralInfo['logo_flag'] == '1') {
            $this->assign('logo_url', $integralInfo['logo_path']);
        } else {
            $this->assign('logo_url', $this->nodeInfo['head_photo']);
        }
        if ($integralInfo['integral_shop_name']) {
            $this->assign('integral_shop_name', 
                $integralInfo['integral_shop_name']);
        } else {
            $this->assign('integral_shop_name', "积分商城");
        }
        if (I('down_type') == '1') {
            $fileName = '积分商城统计数量.csv';
            $fileName = iconv('utf-8', 'gbk', $fileName);
            header("Content-type: text/plain");
            header("Accept-Ranges: bytes");
            header("Content-Disposition: attachment; filename=" . $fileName);
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Pragma: no-cache");
            header("Expires: 0");
            $cj_title = "日期,PV,订单数,积分\r\n";
            echo $cj_title = iconv('utf-8', 'gbk', $cj_title);
            $integralDownData = array();
            foreach ($pv_arr as $key => $val) {
                $integralDownData[$val['day']]['click_count'] = $val['click_count'];
            }
            foreach ($order_arr as $key => $val) {
                $integralDownData[$val['day']]['order_count'] = $val['order_count'];
                $integralDownData[$val['day']]['order_amt'] = $val['order_amt'];
            }
            foreach ($integralDownData as $key => $v) {
                $line = "{$key},{$v['click_count']}," . $v['order_count'] .
                     ",{$v['order_amt']}\r\n";
                echo iconv('utf-8', 'gbk', $line);
            }
            exit();
        }
        $this->assign('type', $type);
        $this->assign('integral_url', $integralInfo['integral_url']);
        $this->assign('integralCode', $integralInfo['qcode']);
        ksort($shop_jsChartDataAmt);
        ksort($shop_jsChartDataOrder);
        $this->assign('goods_count', $goods_count);
        $this->assign('m_info', $m_info);
        $this->assign('_get', $_get);
        $this->assign('shop_jsChartDataClick', 
            json_encode(array_values($shop_jsChartDataClick)));
        $this->assign('shop_jsChartDataOrder', 
            json_encode(array_values($shop_jsChartDataOrder)));
        $this->assign('shop_jsChartDataAmt', 
            json_encode(array_values($shop_jsChartDataAmt)));
        $this->assign('shop_data', $shop_data);
        $this->assign('today', $today);
        $this->assign('yesterday', $yesterday);
        $this->assign('label_id', $label_id);
        $this->display("Integral/preview");
    }

    public function integralCodeShow($integral_url) {
        import('@.Vendor.phpqrcode.phpqrcode');
        echo QRcode::png($integral_url);
        exit();
    }

    public function codeshow() {
        import('@.Vendor.phpqrcode.phpqrcode');
        $url = htmlspecialchars_decode((urldecode(I("get.url"))));
        echo QRcode::png($url);
        exit();
    }

    public function move_pic($piclist) {
        $picArr = explode(",", $piclist);
        if (! empty($picArr)) {
            foreach ($picArr as $k => $val) {
                // 判断目录是否存在img_tmp
                if (strpos($val, 'img_tmp') !== false) {
                    $img_move = move_batch_image($val, $this->BATCH_TYPE, 
                        $this->node_id);
                    if ($img_move !== true) {
                        return $img_move;
                    }
                }
            }
        }
        return true;
    }
    // 油豆查询
    public function IntegralSelect() {
        $data = $_REQUEST;
        if (empty($beginDate) && empty($endDate)) {
            $beginDate = dateformat("-30 days", 'Ymd');
            $endDate = dateformat("0 days", 'Ymd');
            // 查询日期
            $map['o.trace_time'] = array();
            if ($beginDate != '') {
                $map['o.trace_time'][] = array(
                    'EGT', 
                    $beginDate . "000000");
            }
            if ($endDate != '') {
                $map['o.trace_time'][] = array(
                    'ELT', 
                    $endDate . "235959");
            }
        }
        if ($data['IntegralSelectType'] == 1) {
            $map = array(
                'o.node_id' => $this->node_id);
            if ($data['id'] != "") {
                $map['o.id'] = $data['id'];
            }
            // 处理特殊查询字段
            $beginDate = I('start_time', null, 'mysql_real_escape_string,trim');
            $endDate = I('end_time', null, 'mysql_real_escape_string,trim');
            if (! empty($beginDate) && ! empty($endDate)) {
                // $integralStartTime=strtotime($beginDate.'000000');
                // $integralEndTime=strtotime($endDate.'235959');
                // $hour=intval($integralEndTime-$integralStartTime+1)/86400;
                // if($hour>31 || $hour<0){
                // $this->error("您输入的手机超过了30天或不合法！");
                // }
                $map['o.trace_time'] = array(
                    'between', 
                    $beginDate . '000000,' . $endDate . '235959');
            } elseif (! empty($beginDate) && empty($endDate)) {
                $map['o.trace_time'] = array(
                    'egt', 
                    $beginDate . '000000');
            } elseif (empty($beginDate) && ! empty($endDate)) {
                $map['o.trace_time'] = array(
                    'elt', 
                    $endDate . '235959');
            }
            if ($data['member_id'] != "") {
                $map['o.member_id'] = $data['member_id'];
            }
            if ($data['integralType'] == '0') {
                if ($data['point_type'] != "" && $data['point_type'] != "12") {
                    $map['o.type'] = array(
                        'in', 
                        $data['point_type']);
                } elseif ($data['point_type'] == 12) {
                    $map['o.type'] = array(
                        'in', 
                        '12,13');
                } else {
                    $map['o.type'] = array(
                        'in', 
                        '1,4,5,6,8,10,12,13,14,15,18,19');
                }
            } elseif ($data['integralType'] == '1') {
                if ($data['point_type']) {
                    $map['o.type'] = array(
                        'in', 
                        $data['point_type']);
                } else {
                    $map['o.type'] = array(
                        'in', 
                        '2,3,7,11,17');
                }
            }
            if ($data['phone'] != "") {
                $map['a.phone_no'] = $data['phone'];
            }
            if (I('downType') == '1') {
                $list = M()->table('tintegral_point_trace o')
                    ->join('tmember_info a on a.id=o.member_id')
                    ->where($map)
                    ->field('o.*,a.id as member_id,a.phone_no,a.name')
                    ->order('o.trace_time desc')
                    ->select();
                $fileName = '积分流水明细.csv';
                $fileName = iconv('utf-8', 'gbk', $fileName);
                header("Content-type: text/plain");
                header("Accept-Ranges: bytes");
                header("Content-Disposition: attachment; filename=" . $fileName);
                header(
                    "Cache-Control: must-revalidate, post-check=0, pre-check=0");
                header("Pragma: no-cache");
                header("Expires: 0");
                $integral_name = M("tintegral_node_config")->where(
                    array(
                        'node_id' => $this->node_id))->getField('integral_name');
                if ($integral_name == "") {
                    $integral_name = "积分";
                }
                $cj_title = "交易流水号,手机号码,姓名," . $integral_name . ",所属类型\r\n";
                echo $cj_title = iconv('utf-8', 'gbk', $cj_title);
                foreach ($list as $key => $v) {
                    
                    $type = $this->integralExchangeChinese($v['type']);
                    if ($v['type'] == '2' || $v['type'] == '3' ||
                         $v['type'] == '7' || $v['type'] == '11' ||
                         $v['type'] == '16') {
                        $change_num = $integral_name . " -" . $v['change_num'];
                        $line = "{$v['id']}," . $v['phone_no'] . "," . $v['name'] .
                             "," . $change_num . ",{$type}\r\n";
                    } else {
                        $change_num = $integral_name . " +" . $v['change_num'];
                        $line = "{$v['id']}," . $v['phone_no'] . "," . $v['name'] .
                             "," . $change_num . ",{$type}\r\n";
                    }
                    echo iconv('utf-8', 'gbk', $line);
                }
                exit();
            }
            import('ORG.Util.Page'); // 导入分页类
            $mapcount = M()->table('tintegral_point_trace o')
                ->join('tmember_info a on a.id=o.member_id')
                ->where($map)
                ->count(); // 查询满足要求的总记录数
            $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
            foreach ($data as $key => $val) {
                $Page->parameter .= "&$key=" . urlencode($val) . '&';
            }
            $show = $Page->show(); // 分页显示输出
            $list = M()->table('tintegral_point_trace o')
                ->join('tmember_info a on a.id=o.member_id')
                ->where($map)
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->field('o.*,a.id as member_id,a.phone_no,a.name')
                ->order('o.trace_time desc')
                ->select();
            $this->assign('integralType', $data['integralType']);
            $this->assign('point_type', $data['point_type']);
            $this->assign('queryList', $list);
            $this->assign('page', $show); // 赋值分页输出
        }
        $this->assign('start_time', $beginDate);
        $this->assign('end_time', $endDate);
        $integralTypeList = array(
            '0' => "赠送积分", 
            '1' => "扣减积分");
        $this->assign('integralTypeList', $integralTypeList);
        $this->display("Integral/IntegralSelect");
    }

    public function integralExchangeChinese($typeScore) {
        $type = array(
            '1' => '在线购物', 
            '4' => '手动增加积分', 
            '5' => '每日签到', 
            '6' => '批量增加积分', 
            '8' => '线下消费', 
            // '9'=>'会员绑定积分合并',
            '10' => '微信支付',
            '12' => '参与活动中奖', 
            '13' => '参与活动中奖', 
            '14' => '浏览图文编辑页面', 
            '15' => '关注微信公众号', 
            '2' => '积分商城兑换', 
            '3' => '手动减少积分', 
            '7' => '批量减少积分', 
            '11' => '线下积分兑换', 
            '17' => '线上购物抵扣', 
            '18' => '订单撤销',
            '19' => '支付宝支付');
        return $type[$typeScore];
    }
    // 增加积分或减少积分
    public function integralType() {
        $integralType = I("integralType");
        if ($integralType == '0') {
            $type = array(
                '1' => '在线购物', 
                '4' => '手动增加积分', 
                '5' => '每日签到', 
                '6' => '批量增加积分', 
                '8' => '线下消费', 
                // '9'=>'会员绑定积分合并',
                '10' => '微信支付', 
                '12' => '参与活动中奖', 
                '14' => '浏览图文编辑页面', 
                '15' => '关注微信公众号', 
                '18' => '订单撤销',
                '19' => '支付宝支付');
        } else if ($integralType == '1') {
            $type = array(
                '2' => '积分商城兑换', 
                '3' => '手动减少积分', 
                '7' => '批量减少积分', 
                '11' => '线下积分兑换', 
                '17' => '线上购物抵扣');
        } elseif ($integralType == '') {
            exit(json_encode(array(
                'status' => 0)));
        }
        exit(
            json_encode(
                array(
                    'info' => $type, 
                    'status' => 1)));
    }

    /* 积分兑换设置 */
    public function pointSet() {
        $rate = I('rate');
        if (empty($rate)) {
            $this->error("返豆参数配置错误！");
        }
        $map = array(
            'node_id' => $this->node_id);
        $sync_flag = M('tintegral_node_config')->where($map)->find();
        if (! $sync_flag) {
            $data = array(
                'node_id' => $this->node_id, 
                'rate' => $rate);
            $rs = M('tintegral_node_config')->add($data);
            if ($rs === false) {
                $this->error("存储失败！");
            }
            node_log("积分值兑换添加记录", "积分值兑换添加记录" . $rs, "积分值兑换添加记录");
            $this->success("设置成功！");
        }
        // 如果已经有了，则update
        $data = array(
            'rate' => $rate);
        $result = M('tintegral_node_config')->where($map)->save($data);
        if ($result === false) {
            $this->error("设置失败！");
        }
        node_log("更新积分值兑换", "更新积分值兑换" . $result);
        $this->success("设置成功！");
    }
    // 积分商城配置页面设置
    public function integralSetConfig() {
        $postArr = I('post.');
        if ($postArr) {
            if ($postArr['integral_shop_name'] == '') {
                $this->error("商城名称不能为空");
            }
            $data = array(
                'integral_shop_name' => $postArr['integral_shop_name']);
            if ($postArr['logo_flag'] != '') {
                $data['logo_flag'] = $postArr['logo_flag'];
            }
            if ($postArr['resp_img1'] != '') {
                $data['logo_path'] = $postArr['resp_img1'];
            }
            if ($postArr['integral_describe'] != '') {
                $data['integral_describe'] = $postArr['integral_describe'];
            }
            if ($postArr['integral_sharpic'] != '') {
                $data['integral_sharpic'] = $postArr['integral_sharpic'];
            }
            $res = M("tintegral_node_config")->where(
                array(
                    'node_id' => $this->node_id))->find();
            if ($res) {
                $result = M("tintegral_node_config")->where(
                    array(
                        'node_id' => $this->node_id))->save($data);
                if ($result === false) {
                    $this->error("保存失败");
                }
            } else {
                $result = M("tintegral_node_config")->add($data);
                if ($result === false) {
                    $this->error("保存失败");
                }
            }
            node_log("积分商城配置修改", "积分商城配置修改");
            $this->success("保存成功！");
        }
        $list = M("tintegral_node_config")->where(
            array(
                'node_id' => $this->node_id))->find();
        $this->assign('list', $list);
        $this->assign('head_photo', $this->nodeInfo['head_photo']);
        $this->display("Integral/integralSetConfig");
    }
    // 积分扣减列表
    public function integralUpdate() {
        if (I("dataType") == '1') {
            // 基本信息字段
            $where = array(
                'a.node_id' => $this->node_id, 
                '_string' => "phone_no is not NULL");
            $memberName = I('member_name', null);
            if ($memberName) {
                $where['a.name'] = $memberName;
                $this->assign('member_name', $memberName);
            }
            
            $memberPhone = I('member_phone', null);
            if ($memberPhone) {
                $where['a.phone_no'] = $memberPhone;
                $this->assign('member_phone', $memberPhone);
            }
            
            $memberSex = I('member_sex', null);
            if ($memberSex) {
                $where['a.sex'] = $memberSex;
                $this->assign('member_sex', $memberSex);
            }
            
            $nickName = I('nickname', null); // 微信昵称
            if ($nickName) {
                $where['a.nickname'] = $nickName;
                $this->assign('nickname', $nickName);
            }
            
            $bStart = I('birthday_start', null);
            if ($bStart) {
                $where['a.birthday'][] = array(
                    "EGT", 
                    $bStart);
                $this->assign('birthday_start', $bStart);
            }
            
            $bEnd = I('birthday_end', null);
            if ($bEnd) {
                $where['a.birthday'][] = array(
                    "ELT", 
                    $bEnd);
                $this->assign('birthday_end', $bEnd);
            }
            
            $channel_name = I('channel_name', null);
            if ($channel_name) {
                $where['n.name'] = array(
                    'like', 
                    '%' . $channel_name . '%');
                $this->assign('channel_name', $channel_name);
            }
            $label_name = I('bqValue', null);
            if ($label_name) {
                $arr = explode(",", $label_name);
                array_pop($arr);
                $this->assign('arr', $arr);
                $where['_string'] = "and EXISTS(select * from tmember_label_ex l, tmember_label s where a.id = l.member_id and s.id = l.label_id and s.label_name in(";
                foreach ($arr as $k => $v) {
                    $where['_string'] .= "'" . $v . "'";
                    if ($k != (count($arr) - 1)) {
                        $where['_string'] .= ",";
                    }
                }
                $where['_string'] .= "))";
                $this->assign('label_name', $label_name);
            }
            $member_label = I('member_label', null); // 标签信息
            if ($member_label) {
                $where['e.label_id'] = $member_label;
                $this->assign('member_label', $member_label);
            }
            
            $member_cards = I('member_cards', null);
            if ($member_cards) {
                $where['a.card_id'] = $member_cards;
                $this->assign('member_cards', $member_cards);
            }
            
            $integral_point1 = I('integral_point1', null);
            if ($integral_point1) {
                
                $where['a.point'][] = array(
                    "EGT", 
                    $integral_point1);
                $this->assign('integral_point1', $integral_point1);
            }
            $integral_point2 = I('integral_point2', null);
            if ($integral_point2) {
                $where['a.point'][] = array(
                    "ELT", 
                    $integral_point2);
                $this->assign('integral_point2', $integral_point2);
            }
            $province = I('province', null);
            if ($province) {
                $map['a.citycode'] = array(
                    'like', 
                    $province . "%");
            }
            $city = I('city', null);
            if ($city) {
                $map['a.citycode'] = array(
                    'like', 
                    $province . $city . "%");
            }
            $town = I('town', null);
            if ($town) {
                $map['a.citycode'] = $province . $city . $town;
            }
            
            // 特殊信息字段
            $join_cnt1 = I('join_cnt1', null);
            if ($join_cnt1) {
                $where['m.join_total'][] = array(
                    "EGT", 
                    $join_cnt1);
                $this->assign("join_cnt1", $join_cnt1);
            }
            $join_cnt2 = I('join_cnt2', null);
            if ($join_cnt2) {
                $where['m.join_total'][] = array(
                    "ELT", 
                    $join_cnt2);
                $this->assign("join_cnt2", $join_cnt2);
            }
            
            $send_cnt1 = I('send_cnt1', null);
            if ($send_cnt1) {
                $where['m.send_total'][] = array(
                    "EGT", 
                    $send_cnt1);
                $this->assign("send_cnt1", $send_cnt1);
            }
            $send_cnt2 = I('send_cnt2', null);
            if ($send_cnt2) {
                $where['m.send_total'][] = array(
                    "ELT", 
                    $send_cnt2);
                $this->assign("send_cnt2", $send_cnt2);
            }
            
            $verify_cnt1 = I('verify_cnt1', null);
            if ($verify_cnt1) {
                $where['m.verify_total'][] = array(
                    "EGT", 
                    $verify_cnt1);
                $this->assign("verify_cnt1", $verify_cnt1);
            }
            $verify_cnt2 = I('verify_cnt2', null);
            if ($verify_cnt2) {
                $where['m.verify_total'][] = array(
                    "ELT", 
                    $verify_cnt2);
                $this->assign("verify_cnt2", $verify_cnt2);
            }
            
            $shop_line1 = I('shop_line1', null);
            if ($shop_line1) {
                $where['m.shop_total'][] = array(
                    "EGT", 
                    $shop_line1);
                $this->assign("shop_line1", $shop_line1);
            }
            $shop_line2 = I('shop_line2', null);
            if ($shop_line2) {
                $where['m.shop_total'][] = array(
                    "ELT", 
                    $shop_line2);
                $this->assign("shop_line2", $shop_line2);
            }
            
            $shop_down1 = I('shop_down1', null);
            if ($shop_down1) {
                $where['m.shopline_total'][] = array(
                    "EGT", 
                    $shop_down1);
                $this->assign("shop_down1", $shop_down1);
            }
            $shop_down2 = I('shop_down2', null);
            if ($shop_down2) {
                $where['m.shopline_total'][] = array(
                    "ELT", 
                    $shop_down2);
                $this->assign("shop_down2", $shop_down2);
            }
            
            $data = $_REQUEST;
            import('ORG.Util.Page'); // 导入分页类
            $member = M("tmember_info");
            $count_member = $member->alias("a")->join(
                    'tmember_activity_total m on m.member_id = a.id')
                    ->join('tmember_cards g on g.id=a.card_id')
                    ->join('tchannel n on n.id=a.channel_id')
                    ->where($where)
                    ->count();

            //选择的每页显示条数
            $paging = array('10' => '10', '50' => '50', '100' => '100');

            $optNumber = I('optNumber', null);

            if ($optNumber) {
                $num = $optNumber;
                $cfgData = session('cfgData');
                $cfgData['integralPaging'] = $num;
                $datas['cfg_data'] = serialize($cfgData);
                M('tnode_info')->where("node_id = $this->nodeId")->save($datas);
            }else{
                $result = M('tnode_info')->where("node_id = $this->nodeId")->getField('cfg_data');

                if($result){
                    $cfgData = unserialize($result);
                    session('cfgData',$cfgData);
                    if(isset($cfgData['integralPaging']) && $cfgData['integralPaging']){
                        $pagingNumber = $cfgData['integralPaging'];
                    }else{
                        $pagingNumber = 10;
                    }
                }else{
                    session('cfgData','');
                    $pagingNumber = 10;
                }

                $num = $pagingNumber;
            }
            $this->assign('optNumber', $num);

            $Page = new Page($count_member, $num);
            $show = $Page->show(); // 分页显示输出
            $list = $member->alias("a")->field(
                    'a.*,m.join_total,m.send_total,m.verify_total,m.shop_total,m.shopline_total,n.name channel_name,g.card_name')
                    ->join('tmember_activity_total m on m.member_id = a.id')
                    ->join('tmember_cards g on g.id=a.card_id')
                    ->join('tchannel n on n.id=a.channel_id')
                    ->where($where)
                    ->order('a.add_time desc')
                    ->limit($Page->firstRow . ',' . $Page->listRows)
                    ->select();
        }
        $member = new MemberInstallModel();
        $member_labels = $member->getListLabels($this->node_id);
        $member_lgroup = array();
        foreach ($member_labels as $key => $val) {
            $member_lgroup[$val['id']] = $val['label_name'];
        }
        $this->assign("member_lgroup", $member_lgroup);
        
        $member_cards = $member->getMemberCards($this->node_id);
        $member_cgroup = array();
        foreach ($member_cards as $key => $val) {
            $member_cgroup[$val['id']] = $val['card_name'];
        }
        $this->assign("member_cgroup", $member_cgroup);
        $this->assign('memberData', $list);
        $this->assign('sex_list', 
            $sex_list = array(
                '1' => '男', 
                '2' => '女'));
        $this->assign('page', $show);
        $this->assign('paging', $paging);
        $this->display("Integral/integralUpdate");
    }
    // 手动积分扣减
    public function integralAdd() {
        $type = I('type');
        $point = I('point');
        $id = I('id');
        if ($type == '') {
            $this->error("缺少必要参数！");
        }
        if ($point == '') {
            $this->error("缺少必要参数！");
        }
        if ($id == '') {
            $this->error("缺少必要参数！");
        }
        $integralTrace = new IntegralPointTraceModel();
        M()->startTrans();
        if ($type == 1) {
            // 积分增加
            $res = $integralTrace->integralPointChange(4, $point, $id, 
                $this->node_id, '', '');
            if ($res === false) {
                M()->rollback();
                $this->error("积分增加失败!");
            }
            // 增加行为数据
            $res = D("MemberBehavior")->addBehaviorType($id, $this->node_id, 5, 
                $point, $this->user_name);
            if ($res === false) {
                M()->rollback();
                $this->error("行为增加失败!");
            }
            node_log("积分商城积分增加", "积分商城积分增加");
        } elseif ($type == 2) {
            $res = $integralTrace->integralPointChange(3, $point, $id, 
                $this->node_id, '', '');
            if ($res === false) {
                M()->rollback();
                $this->error("积分扣减失败");
            }
            $res = D("MemberBehavior")->addBehaviorType($id, $this->node_id, 6, 
                $point, $this->user_name);
            if ($res === false) {
                M()->rollback();
                $this->error("行为增加失败!");
            }
            node_log("积分商城积分减少", "积分商城积分减少");
        } elseif ($type == 6) {
            $res = $integralTrace->integralBatchPointChange($type, $point, $id, 
                $this->node_id);
            if ($res === false) {
                M()->rollback();
                $this->error("批量增加积分失败");
            }
            $member_arr = explode(',', $id);
            $member_count = count($member_arr);
            for ($i = 0; $i < $member_count; $i ++) {
                $res = D("MemberBehavior")->addBehaviorType($member_arr[$i], 
                    $this->node_id, 5, $point, $this->user_name);
                if ($res === false) {
                    M()->rollback();
                    $this->error("行为增加失败!" . $i);
                }
            }
            node_log("批量积分商城积分增加", "批量积分商城积分增加");
        } elseif ($type == 7) {
            $res = $integralTrace->integralBatchPointChange($type, $point, $id, 
                $this->node_id);
            if ($res === false) {
                M()->rollback();
                $this->error("批量扣减积分失败");
            }
            $member_arr = explode(',', $id);
            $member_count = count($member_arr);
            for ($i = 0; $i < $member_count; $i ++) {
                $res = D("MemberBehavior")->addBehaviorType($member_arr[$i], 
                    $this->node_id, 6, $point, $this->user_name);
                if ($res === false) {
                    M()->rollback();
                    $this->error("行为增加失败!" . $i);
                }
            }
            node_log("批量积分商城积分减少", "批量积分商城积分减少");
        }
        M()->commit();
        $this->success("积分操作成功！");
    }
    // 积分获取规则
    public function getPointGz() {
        $list = M("tintegral_node_config")->where(
            array(
                'node_id' => $this->node_id))->find();
        $this->assign('list', $list);
        $this->display("Integral/getPointGz");
    }

    public function integralPointSet() {
        $id = I("id");
        if (empty($id)) {
            $this->error("缺少会员ID");
        }
        $type = I("type");
        if (empty($type)) {
            $this->error("缺少增加类型");
        }
        // type=1为单条增加和减少 type=2代表批量增加减少
        if ($type == '1') {
            $mType = '1';
            $zType = '1';
            $jType = '2';
        }
        if ($type == '2') {
            $mType = '6';
            $zType = '6';
            $jType = '7';
        }
        $member_arr = explode(',', $id);
        $member_count = count($member_arr);
        $this->assign("member_count", $member_count);
        $this->assign("id", $id);
        $this->assign("mType", $mType);
        $this->assign("zType", $zType);
        $this->assign("jType", $jType);
        $this->display("Integral/integralPointSet");
    }
    // 生成商户配置表
    public function integralConfigTable($integralUrl) {
        $res = M("tintegral_node_config")->where(
            array(
                'node_id' => $this->node_id))->find();
        if (empty($res)) {
            $qCode = $this->makeMemberCode($integralUrl);
            $res = M("tintegral_node_config")->add(
                array(
                    'node_id' => $this->node_id, 
                    'integral_url' => $integralUrl, 
                    'integral_name' => '积分', 
                    'qcode' => $qCode));
            if ($res === false) {
                $this->error("初始化生成商户配置表失败");
            }
            node_log("初始化生成商户配置表", "初始化生成商户配置表");
        }
    }
    // 根据会员id二维码生成
    public function makeMemberCode($integralUrl) {
        import('@.Vendor.phpqrcode.phpqrcode') or die('include file fail.');
        $path = APP_PATH . 'Upload/MemberCode/';
        $name = $this->node_id;
        if (! file_exists($path)) {
            mkdir($path, 0777);
        }
        // 纠错级别：L、M、Q、H
        $level = 'L';
        // 点的大小：1到10,用于手机端4就可以了
        $ecc = 'H';
        $size = 10;
        $filename = $path . $name . '.png';
        QRcode::png($integralUrl, $filename, $ecc, $size, 0, false);
        $mc_str = base64_encode(file_get_contents($filename));
        if (file_exists($filename)) {
            unlink($filename);
        }
        return $mc_str;
    }
    // 修改配置表状态
    public function integralChangeStatus() {
        $type = I('type');
        $integralType = I('integral_type');
        if ($type == '' || empty($integralType)) {
            $this->error("缺少必要参数");
        }
        $data = array();
        if ($integralType == '1') {
            if ($type == '0') {
                $data['shop_online_flag'] = 0;
            } elseif ($type == '1') {
                $data['shop_online_flag'] = 1;
            }
        } elseif ($integralType == '2') {
            if ($type == '0') {
                $data['shop_line_flag'] = 0;
            } elseif ($type == '1') {
                $data['shop_line_flag'] = 1;
            }
        } elseif ($integralType == '3') {
            if ($type == '0') {
                $data['day_sign_flag'] = 0;
            } elseif ($type == '1') {
                $data['day_sign_flag'] = 1;
            }
        } elseif ($integralType == '4') {
            if ($type == '0') {
                $data['7day_sign_flag'] = 0;
            } elseif ($type == '1') {
                $data['7day_sign_flag'] = 1;
            }
        } elseif ($integralType == '5') {
            if ($type == '0') {
                $data['weixin_payment_flag'] = 0;
            } elseif ($type == '1') {
                $data['weixin_payment_flag'] = 1;
            }
        } elseif ($integralType == '6') {
            if ($type == '0') {
                $data['zhifubao_payment_flag'] = 0;
            } elseif ($type == '1') {
                $data['zhifubao_payment_flag'] = 1;
            }
        } elseif ($integralType == '7') {
            if ($type == '0') {
                $data['weixin_guanzhu_flag'] = 0;
            } elseif ($type == '1') {
                $data['weixin_guanzhu_flag'] = 1;
            }
        }
        $res = M("tintegral_node_config")->where(
            array(
                'node_id' => $this->node_id))->find();
        if (empty($res)) {
            $data['node_id'] = $this->node_id;
            $result = M("tintegral_node_config")->add($data);
            if ($result === false) {
                $this->error("操作失败！");
            }
            node_log("生成商户配置表", "生成商户配置表");
        }
        $result = M("tintegral_node_config")->where(
            array(
                'node_id' => $this->node_id))->save($data);
        if ($result === false) {
            $this->error("操作失败！");
        }
        node_log("更新商户配置表", "更新商户配置表");
        $this->success("操作成功！");
    }
    // 修改修改配置表数据
    public function integralChangeData() {
        $integralType = I('type');
        if ($integralType == '') {
            $this->error("缺少必要参数");
        }
        $getPoint = I('getpoint');
        if ($getPoint) {
            if (is_numeric($getPoint) == false) {
                $this->error("您输入的不为数字");
            }
        }
        $maxPoint = I('maxpoint');
        if ($maxPoint) {
            if (is_numeric($getPoint) == false || is_numeric($maxPoint) == false) {
                $this->error("您输入的不为数字");
            }
        }
        $data = array();
        if ($integralType == '1') {
            $data['shop_online_rate'] = $getPoint;
            $data['one_online_rate'] = $maxPoint;
            $data['shop_online_flag'] = '1';
        } elseif ($integralType == '2') {
            $data['shop_line_rate'] = $getPoint;
            $data['one_line_rate'] = $maxPoint;
            $data['shop_line_flag'] = '1';
        } elseif ($integralType == '3') {
            $data['day_sign_rate'] = $getPoint;
            $data['day_sign_flag'] = '1';
        } elseif ($integralType == '4') {
            $data['7day_sign_rate'] = $getPoint;
            $data['7day_sign_flag'] = '1';
        } elseif ($integralType == '5') {
            $data['weixin_payment_rate'] = $getPoint;
            $data['one_weixin_payment_rate'] = $maxPoint;
            $data['weixin_payment_flag'] = '1';
        } elseif ($integralType == '6') {
            $data['zhifubao_payment_rate'] = $getPoint;
            $data['one_zhifubao_payment_rate'] = $maxPoint;
            $data['zhifubao_payment_flag'] = '1';
        } elseif ($integralType == '7') {
            $data['weixin_guanzhu_rate'] = $getPoint;
            $data['weixin_guanzhu_flag'] = '1';
        }
        $res = M("tintegral_node_config")->where(
            array(
                'node_id' => $this->node_id))->find();
        if (empty($res)) {
            $data['node_id'] = $this->node_id;
            $result = M("tintegral_node_config")->add($data);
            if ($result === false) {
                $this->error("操作失败！");
            }
            node_log("生成商户配置表", "生成商户配置表");
        }
        $result = M("tintegral_node_config")->where(
            array(
                'node_id' => $this->node_id))->save($data);
        if ($result === false) {
            $this->error("操作失败！");
        }
        node_log("更新商户配置表", "更新商户配置表");
        $this->success("操作成功！");
    }
    // 积分营销
    public function integralMarketing() {
        $integralData = M("tintegral_stat")->where(
            array(
                'node_id' => $this->node_id))
            ->field(
            'sum(integral_add) as integral_add_total,sum(integral_reduce) as integral_reduce_total')
            ->
        // ->order("trans_date desc")
        find();
        // 查询当前昨天的兑换和赠送数
        $integralDataDay = M("tintegral_stat")->where(
            array(
                'node_id' => $this->node_id, 
                'trans_date' => dateformat("-1 days", 'Ymd')))
            ->field('integral_add,integral_reduce')
            ->find();
        // 查询拥有积分人数
        $member_count = M("tmember_info")->where(
            array(
                'node_id' => $this->node_id, 
                "_string" => "point>0", 
                'status' => '0'))->count();
        $this->assign('member_count', $member_count);
        $this->assign('newsList', $this->helpList());
        $this->assign('integralData', $integralData);
        $this->assign('openFlag', 1);
        $this->assign('integralDataDay', $integralDataDay);
        $this->display("Integral/integralMarketing");
    }

    /**
     * 查询第三级分类下的所有帮助
     */
    public function helpList() {
        $newsList = M("tym_news")->where(
            array(
                'class_id' => '19'))->select();
        return $newsList;
    }
    // 积分商城数据统计
    public function integralData() {
        // 默认近7天的数据
        $type = I('type');
        if ($type == '') {
            $type = 1;
        }
        $where = '';
        if ($type == 1) {
            $startTime = I('begin_date', dateformat("-7 days", 'Ymd'));
            $endTime = I('end_date', dateformat("0 days", 'Ymd'));
            $date = '"' . date('Ymd', strtotime('-7 day')) . '"';
            $where = $date . '< trans_date and trans_date < "' . date('Ymd') .
                 '"';
        }
        if ($type == 2) {
            $startTime = I('begin_date', dateformat("-30 days", 'Ymd'));
            $endTime = I('end_date', dateformat("0 days", 'Ymd'));
            $date = '"' . date('Ymd', strtotime('-30 day')) . '"';
            $where = $date . '< trans_date and trans_date < "' . date('Ymd') .
                 '"';
        }
        if ($type == 3) {
            $date = '"' . date('Ymd', strtotime('-1 year')) . '"';
            $startTime = I('begin_date', dateformat("-1 year", 'Ymd'));
            $endTime = I('end_date', dateformat("0 days", 'Ymd'));
            $fields2 = array(
                'sum(integral_add)' => 'integral_add', 
                'sum(integral_reduce)' => 'integral_reduce', 
                'DATE_FORMAT(trans_date,"%Y%m")' => 'date');
            $where = $date . '< trans_date and trans_date < "' . date('Ymd') .
                 '"';
        }
        if ($type == 4) {
            $startTime = I("startTime");
            $endTime = I("endTime");
            $where = "trans_date>=" . $startTime . " and trans_date<=" . $endTime;
            // $where=$startTime.'=< trans_date and trans_date <= '.$endTime;
        }
        // $where .= " and type in ('1','4','5')";
        // $dataWeek=M("tintegral_point_trace")->where($where)->sum("change_num");
        if ($type == 1 || $type == 2) {
            $mData = M("tintegral_stat")->field(
                'trans_date,integral_add,integral_reduce')
                ->where(
                array(
                    'node_id' => $this->node_id, 
                    '_string' => $where))
                ->order('trans_date asc')
                ->select();
        } elseif ($type == 3) {
            $mData = M("tintegral_stat")->field($fields2)
                ->where(
                array(
                    'node_id' => $this->node_id, 
                    '_string' => $where))
                ->group('date')
                ->order('date asc')
                ->select();
        } else {
            // 自选日期操作
            $mData = M("tintegral_stat")->field(
                'trans_date,integral_add,integral_reduce')
                ->where(
                array(
                    'node_id' => $this->node_id, 
                    '_string' => $where))
                ->order('trans_date asc')
                ->select();
        }
        if ($type == 3) {
            foreach ($mData as $v) {
                if ($type == 3) {
                    $mDataAdd[$v['date']] = $v['integral_add'];
                    $mDataReduce[$v['date']] = $v['integral_reduce'];
                    $dataArray[] = $v['date'];
                }
            }
        } elseif ($type == 1 || $type == 2) {
            foreach ($mData as $v) {
                $mDataAdd[$v['trans_date']] = $v['integral_add'];
                $mDataReduce[$v['trans_date']] = $v['integral_reduce'];
                $dataArray[] = $v['trans_date'];
            }
        } else {
            // 自选日期操作
            foreach ($mData as $v) {
                $mDataAdd[$v['trans_date']] = $v['integral_add'];
                $mDataReduce[$v['trans_date']] = $v['integral_reduce'];
                $dataArray[] = $v['trans_date'];
            }
        }
        $yData = '';
        $xData11 = '';
        $xData12 = '';
        foreach ($dataArray as $v) {
            $xData11 .= $mDataAdd[$v] > 0 ? $mDataAdd[$v] . ',' : '0,';
            $xData12 .= $mDataReduce[$v] > 0 ? $mDataReduce[$v] . ',' : '0,';
            $yData .= "'" . $v . "',";
        }
        $xyData = array(
            'yData' => trim($yData, ','), 
            'xData11' => trim($xData11, ','), 
            'xData12' => trim($xData12, ','));
        $this->assign("startTime", $startTime);
        $this->assign("endTime", $endTime);
        $maArr = $this->memberStatistics();
        if ($maArr === false) {
            $this->assign("flag", 1);
        }
        $this->assign('maArr', $maArr[0][0]);
        $this->assign('maArr1', $maArr[0][1]);
        $this->assign('integralScoreArr', $maArr[1]);
        $integralShengYu = 100 - $maArr[0][0]['integral1'] -
             $maArr[0][0]['integral2'] - $maArr[0][0]['integral3'] -
             $maArr[0][0]['integral4'] - $maArr[0][0]['integral5'] -
             $maArr[0][0]['integral6'] - $maArr[0][0]['integral7'];
        $integralShengYu = number_format($integralShengYu, 2, '.', ' ');
        $this->assign('integralScoreArr2', $integralShengYu);
        $this->assign('type', $type);
        $this->assign("xyData", $xyData);
        $this->display("Integral/integralData");
    }

    public function memberStatistics() {
        // 查询会员最大积分和最小积分
        $memberPointConpare = M("tmember_info")->where(
            array(
                'node_id' => $this->node_id, 
                '_string' => 'point>0'))
            ->field("min(point) as pointMin,max(point) as pointMax")
            ->find();
        if (empty($memberPointConpare)) {
            return false;
        }
        if ((intval($memberPointConpare['pointMax']) /
             intval($memberPointConpare['pointMin'])) < 8) {
            return false;
        }
        // $memberPointConpare['pointMax']=16;
        // 大于8，起码可以除尽
        $integralScore = intval($memberPointConpare['pointMax'] / 8);
        for ($i = 1; $i <= 7; $i ++) {
            $integralScoreArr[$i] = $integralScore * $i;
        }
        $sql = "SELECT COUNT(*) as member_count,
			  SUM(IFNULL(CASE WHEN POINT <=" .
             $integralScoreArr[1] . " THEN 1 ELSE 0 END,0))  AS integral1,
			  SUM(IFNULL(CASE WHEN POINT>" .
             $integralScoreArr[1] . " AND POINT <=" . $integralScoreArr[2] . " THEN 1 ELSE 0 END,0))  AS integral2,
			  SUM(IFNULL(CASE WHEN POINT>" .
             $integralScoreArr[2] . " AND POINT <=" . $integralScoreArr[3] . " THEN 1 ELSE 0 END,0))  AS integral3,
			  SUM(IFNULL(CASE WHEN POINT>" .
             $integralScoreArr[3] . " AND POINT <=" . $integralScoreArr[4] . " THEN 1 ELSE 0 END,0))  AS integral4,
			  SUM(IFNULL(CASE WHEN POINT>" .
             $integralScoreArr[4] . " AND POINT <=" . $integralScoreArr[5] . " THEN 1 ELSE 0 END,0))  AS integral5,
			  SUM(IFNULL(CASE WHEN POINT>" .
             $integralScoreArr[5] . " AND POINT <=" . $integralScoreArr[6] . " THEN 1 ELSE 0 END,0))  AS integral6,
			  SUM(IFNULL(CASE WHEN POINT>" .
             $integralScoreArr[6] . " AND POINT <=" . $integralScoreArr[7] .
             " THEN 1 ELSE 0 END,0))  AS integral7,
			  SUM(IFNULL(CASE WHEN POINT>" .
             $integralScoreArr[7] . "  THEN 1 ELSE 0 END,0))  AS integral8
			FROM `tmember_info` WHERE node_id='$this->node_id'
		";
        $list = M()->query($sql);
        $maArr = array();
        foreach ($list[0] as $key => $val) {
            if ($key != "member_count") {
                $maArr[$key] = number_format(
                    $val / $list[0]['member_count'] * 100, 2, '.', ' ');
                // $maArr[$key]=ceil($val/$list[0]['member_count']*100);
            }
        }
        $dataA = array();
        $dataA[] = $maArr;
        $dataA[] = $list[0];
        return array(
            $dataA, 
            $integralScoreArr);
    }

    public function integralSet() {
        $getArr = I('get.');
        if ($getArr) {
            $this->assign('getArr', $getArr);
        }
        $this->display('Integral/integralSet');
    }

    public function integralUpdateSet() {
        $postArr = I('post.');
        // 在线商城购物支付
        if ($postArr['type'] == '1') {
            if ($postArr['rate'] == '') {
                $this->error('不能为空');
            }
            if ($postArr['flag'] == '1') {
                if ($postArr['one_rate'] == '') {
                    $this->error('不能为空');
                }
                $data['one_online_rate_flag'] = $postArr['flag'];
                $data['one_online_rate'] = $postArr['one_rate'];
                if (intval($data['one_online_rate']) < intval($postArr['rate'])) {
                    $this->error("单次上限值，必须大于单次获取！");
                }
            } else {
                $data['one_online_rate_flag'] = $postArr['flag'];
            }
            $data['shop_online_rate'] = $postArr['rate'];
            $data['shop_online_flag'] = '1';
        }
        // 线下刷卡消费1元
        if ($postArr['type'] == '2') {
            if ($postArr['rate'] == '') {
                $this->error('不能为空');
            }
            if ($postArr['flag'] == '1') {
                if ($postArr['one_rate'] == '') {
                    $this->error('不能为空');
                }
                $data['one_line_rate_flag'] = $postArr['flag'];
                $data['one_line_rate'] = $postArr['one_rate'];
                if (intval($data['one_line_rate']) < intval($postArr['rate'])) {
                    $this->error("单次上限值，必须大于单次获取！");
                }
            } else {
                $data['one_line_rate_flag'] = $postArr['flag'];
            }
            $data['shop_line_rate'] = $postArr['rate'];
            $data['shop_line_flag'] = '1';
        }
        // 微信条码支付1元
        if ($postArr['type'] == '3') {
            if ($postArr['rate'] == '') {
                $this->error('不能为空');
            }
            if ($postArr['flag'] == '1') {
                if ($postArr['one_rate'] == '') {
                    $this->error('不能为空');
                }
                $data['one_weixin_flag'] = $postArr['flag'];
                $data['one_weixin_payment_rate'] = $postArr['one_rate'];
                if (intval($data['one_weixin_payment_rate']) <
                     intval($postArr['rate'])) {
                    $this->error("单次上限值，必须大于单次获取！");
                }
            } else {
                $data['one_weixin_flag'] = $postArr['flag'];
            }
            $data['weixin_payment_rate'] = $postArr['rate'];
            $data['weixin_payment_flag'] = '1';
        }
        // 支付宝支付1元
        if ($postArr['type'] == '4') {
            if ($postArr['rate'] == '') {
                $this->error('不能为空');
            }
            if ($postArr['flag'] == '1') {
                if ($postArr['one_rate'] == '') {
                    $this->error('不能为空');
                }
                $data['one_zhifubao_flag'] = $postArr['flag'];
                $data['one_zhifubao_payment_rate'] = $postArr['one_rate'];
                if (intval($data['one_zhifubao_payment_rate']) <
                     intval($postArr['rate'])) {
                    $this->error("单次上限值，必须大于单次获取！");
                }
            } else {
                $data['one_zhifubao_flag'] = $postArr['flag'];
            }
            $data['zhifubao_payment_rate'] = $postArr['rate'];
            $data['zhifubao_payment_flag'] = '1';
        }
        if ($postArr['type'] == '5') {
            if ($postArr['rate'] == '') {
                $this->error('不能为空');
            }
            $data['day_sign_rate'] = $postArr['rate'];
            $data['day_sign_flag'] = 1;
        }
        if ($postArr['type'] == '6') {
            if ($postArr['rate'] == '') {
                $this->error('不能为空');
            }
            $data['7day_sign_rate'] = $postArr['rate'];
            $data['7day_sign_flag'] = 1;
        }
        if ($postArr['type'] == '7') {
            if ($postArr['rate'] == '') {
                $this->error('不能为空');
            }
            $data['weixin_guanzhu_rate'] = $postArr['rate'];
            $data['weixin_guanzhu_flag'] = 1;
        }
        $res = M("tintegral_node_config")->where(
            array(
                'node_id' => $this->node_id))->find();
        if (empty($res)) {
            $data['node_id'] = $this->node_id;
            $result = M("tintegral_node_config")->add($data);
            if ($result === false) {
                $this->error("操作失败！");
            }
            node_log("生成商户配置表", "生成商户配置表");
        }
        $result = M("tintegral_node_config")->where(
            array(
                'node_id' => $this->node_id))->save($data);
        if ($result === false) {
            $this->error("操作失败！");
        }
        node_log("更新商户配置表", "更新商户配置表");
        $this->success("操作成功！");
    }

    public function integralChangeName() {
        $integralName = I('integralName');
        $rule['maxlen_cn'] = 3;
        $checkLength = check_str($integralName, $rule);
        if ($checkLength === false) {
            $this->error("字数不能超长！");
        }
        M()->startTrans();
        if ($integralName) {
            $res = M("tintegral_node_config")->where(
                array(
                    'node_id' => $this->node_id))->save(
                array(
                    'integral_name' => $integralName));
            if ($res === false) {
                M()->rollback();
                $this->error("修改失败1！");
            }
        }
        // 修改积分奖品的名称
        $res = M("tgoods_info")->where(
            array(
                "node_id" => $this->node_id, 
                'goods_type' => CommonConst::GOODS_TYPE_JF))->save(
            array(
                'goods_name' => $integralName));
        if ($res === false) {
            M()->rollback();
            $this->error("修改失败2！");
        }
        M()->commit();
        session('userSessIntegralName', $integralName);
        $this->success("保存成功！");
    }

    /**
     * Description of SkuService 积分当钱花设置
     *
     * @param
     *
     * @author john_zeng
     */
    public function integralRuleList() {
        $map['status'] = array(
            'neq', 
            '3');
        $map['node_id'] = $this->node_id;
        // 取得总规则
        $ruleServer = D('SalePro', 'Service');
        $ruleType = $ruleServer->getNodeRule($this->node_id, 
            'tintegral_rule_main');
        $list = M('tintegral_rules')->where($map)->select();
        // 获取积分兑换比例
        $exchangeInfo = M('tintegral_exchange')->where(
            array(
                'node_id' => $this->node_id))
            ->field('intergral')
            ->find();
        
        $this->assign('ruleType', $ruleType);
        $this->assign('intergral', $exchangeInfo['intergral']);
        $this->assign('query_list', $list);
        $this->display("Integral/intergralRuleList");
    }

    public function changeStatus() {
        $id = I('id', '', 'trim');
        $status = I('status', '', 'trim');
        if ($status == "" || $id == "") {
            $this->error('参数错误！');
        }
        
        $where = array(
            'id' => $id);
        
        $data = array(
            'status' => $status);
        $res = M("tintegral_rules")->where($where)->save($data);
        if ($res !== false) {
            
            $this->success("积分使用规则状态更改成功！");
        } else {
            $this->error("积分使用规则状态更改失败！");
        }
    }

    public function ruledelete() {
        $id = I('id', '', 'trim');
        if (! $id) {
            $this->error('参数错误！');
        }
        $where = array(
            'id' => $id);
        
        $data = array(
            'status' => '3');
        $res = M("tintegral_rules")->where($where)->save($data);
        if ($res !== false) {
            
            $this->success("红包使用规则状态删除成功！");
        } else {
            $this->error("红包使用规则状态删除失败！");
        }
    }

    /**
     * Description of SkuService 更改积分使用规则
     *
     * @author john_zeng
     */
    public function integralRuleChangeType() {
        $typeId = I('type', '', 'trim') ? (int) I('type', '', 'trim') : 0;
        // 实例化模块
        $saleProModel = D('SalePro', 'Service');
        // 添加积分总规则
        $res = $saleProModel->addNodeRule($this->node_id, $typeId, 
            'tintegral_rule_main');
        if (false === $res) {
            $this->error($saleProModel->getError());
        } else {
            $this->success("积分规则更改成功！");
        }
    }

    /**
     * Description of SkuService 添加积分兑换规则
     *
     * @author john_zeng
     */
    public function addRuleSubmit() {
        $ruleInfo = I("info", '', 'trim');
        $rulePoint = (int) I("rulePoint", '', 'trim');
        $ruleInfo = json_decode($ruleInfo, true);
        if (is_array($ruleInfo)) {
            // 事务开始
            M()->startTrans();
            // 添加积分兑换比例
            $reslt = M('tintegral_exchange')->where(
                array(
                    'node_id' => $this->node_id))->find();
            if ($reslt) {
                $resltInfo = M('tintegral_exchange')->where(
                    array(
                        'node_id' => $this->node_id))->save(
                    array(
                        'intergral' => $rulePoint, 
                        'add_time' => date('YmdHis')));
                if (! $resltInfo) {
                    M()->rollback();
                    $this->error('保存积分使用规则失败！');
                }
            } else {
                $resltInfo = M('tintegral_exchange')->add(
                    array(
                        'intergral' => $rulePoint, 
                        'node_id' => $this->node_id, 
                        'money' => 1, 
                        'add_time' => date('YmdHis')));
                if (! $resltInfo) {
                    M()->rollback();
                    $this->error('保存积分使用规则失败！');
                }
            }
            foreach ($ruleInfo as $val) {
                if ($val['rev_amount'] < $val['use_amount'])
                    $this->error('抵扣金额不得大于订单金额！', 
                        array(
                            '返回列表' => U('index')));
                $bdata = array(
                    'node_id' => $this->node_id, 
                    'rule_name' => '积分规则', 
                    'rev_amount' => (int) $val['rev_amount'], 
                    'use_amount' => (int) $val['use_amount'], 
                    'rule_memo' => "订单满" . $val['rev_amount'] . "元，最多可抵扣" .
                         $val['rev_amount'] . "元", 
                        'status' => '1', 
                        'add_time' => date('YmdHis'));
                // 查找规则是否存在
                $ruleid = M('tintegral_rules')->where(
                    array(
                        'id' => $val['newid'], 
                        'node_id' => $this->node_id))->find();
                if ($ruleid) {
                    $ruleRet = M('tintegral_rules')->where(
                        array(
                            'id' => $val['newid'], 
                            'node_id' => $this->node_id))->save($bdata);
                } else {
                    $ruleRet = M('tintegral_rules')->data($bdata)->add();
                }
                if (! $ruleRet) {
                    M()->rollback();
                    $this->error('保存积分使用规则失败！');
                }
            }
            M()->commit();
            $this->success('保存积分使用规则成功！');
        } else {
            $this->error("保存积分规则状态失败！");
        }
    }
    // 判断搜索条件添加
    public function selLabelFlag() {
        $label_name = I("label_name", '');
        
        $result = D("MemberInstall")->judgedLabelFlag($this->node_id, 
            $label_name);
        if (! $result) {
            $this->error("标签不存在");
        } else {
            $this->success("标签存在");
        }
    }

    /**
     * @return TmemberMsgModel
     */
    private function getTmemberMsgModel(){
    	return D('TmemberMsg');
    }
    /**
     * 消息列表
     */
    public function sysMessageList(){
    	$memberMsgModel = $this->getTmemberMsgModel();
    	$nodeId = $this->node_id;
    	$where = array('node_id'=>$nodeId,'reader'=>2,'status'=>2);
    	$search = '';
    	if(IS_POST){
    		$search = I('post.search','');
    		if(!empty($search)){
    			$where['title'] = array('like',$search);
    		}
    	}
    	//制作分页
    	import('ORG.Util.Page');// 导入分页类
    	$count = M('tmember_msg')->where($where)->count();
    	$p    = new Page($count, 10);
    	$page = $p->show();
    	$allMessage = $memberMsgModel->getAllMessage($nodeId, $search, $p->firstRow, $p->listRows);
    	$this->assign('search',$search);
    	$this->assign('page',$page);
    	$this->assign('allMessage',$allMessage);
    	$this->display('Integral/sysMessageList');
    }

    /**
     * 添加消息，含页面显示
     */
    public function addSysMessage(){
    	$nodeId = $this->node_id;
        $memberCard = M('tmember_cards')->where(array('node_id'=>$nodeId))->select();
    	if(IS_POST){
    		$memberMsgModel = $this->getTmemberMsgModel();
    		$postData = I('post.');
    		if(empty($postData['sendTitle'])){
    			$this->ajaxReturn(array('status'=>0,'info'=>'请填写标题'),'JSON');
    		}
    		if(empty($postData['sendContent'])){
    			$this->ajaxReturn(array('status'=>0,'info'=>'请填写消息内容'),'JSON');
    		}
            //会员卡
            if($postData['sendType'] === '0'){
                foreach($memberCard as $key => $value){
                    $postData['sendGroup'][] = $value['id'];
                }
            }
    		$userInfo = session('userSessInfo');
    		$addData = array(
    				'node_id'       => $nodeId,
    				'msg_type'      => 1,
    				'title'         => $postData['sendTitle'],
    				'content'       => $postData['sendContent'],
    				'reader'        => 2,
    				'reader_list'   => implode(',',$postData['sendGroup']),
    				'add_time'      => date('YmdHis'),
    				'status'        => 2,
    				'user_id'       => $userInfo['user_id']
    		);
    		//添加
    		$isOk = $memberMsgModel->addMessage($addData);
    		if($isOk){
    			$this->ajaxReturn(array('status'=>1,'添加成功'),'JSON');
    		}
    		$this->ajaxReturn(array('status'=>0,'添加失败'),'JSON');
    	}

    	$this->assign('memberCard',$memberCard);
    	$this->display('Integral/addSysMessage');
    }

    /*
     * 修改消息
     */
    public function editSysMessage(){
    	$memberMsgModel = $this->getTmemberMsgModel();
    	$nodeId = $this->node_id;
    	$msgId = I('get.id','');
    	$msgInfo = $memberMsgModel->getRowMessage($nodeId, $msgId);
        $memberCard = M('tmember_cards')->where(array('node_id'=>$nodeId))->select();
    	if(IS_POST){
    		$postData = I('post.');
    		if(empty($postData['sendTitle'])){
    			$this->ajaxReturn(array('status'=>0,'info'=>'请填写标题'),'JSON');
    		}
    		if(empty($postData['sendContent'])){
    			$this->ajaxReturn(array('status'=>0,'info'=>'请填写消息内容'),'JSON');
    		}
            //会员卡
            if($postData['sendType'] === '0'){
                foreach($memberCard as $key => $value){
                    $postData['sendGroup'][] = $value['id'];
                }
            }
    		$userInfo = session('userSessInfo');
    		$postData = array(
    				'title'         => $postData['sendTitle'],
    				'content'       => $postData['sendContent'],
    				'reader_list'   => implode(',',$postData['sendGroup']),
    				'user_id'       => $userInfo['user_id'],
    		);
    		$isOk = $memberMsgModel->editMessage($nodeId, $msgId, $postData);
    		if($isOk){
    			$this->ajaxReturn(array('status'=>1,'info'=>'修改成功'),'JSON');
    		}else{
    			$this->ajaxReturn(array('status'=>0,'info'=>'修改失败'),'JSON');
    		}
    	}
        //会员卡转成数组
        $card = explode(',',$msgInfo['reader_list']);
    	$this->assign('card',json_encode($card));
    	$this->assign('memberCard',$memberCard);
    	$this->assign('msgInfo',$msgInfo);
    	$this->display('Integral/editSysMessage');

    }

    /**
     * 删除消息
     */
    public function delSysMessage(){
    	$memberMsgModel = $this->getTmemberMsgModel();
    	$nodeId = $this->node_id;
    	$id = I('get.id','');
    	if(empty($id)){
    		$this->error('删除错误！');
    	}
    	$result = $memberMsgModel->delMsg($nodeId,$id);
    	if($result){
    		$this->redirect('sysMessageList');
    	}else{
    		$this->error('删除错误！');
    	}
    }

    /**
     * 指定消息详情   弹窗
     */
    public function sysMsgDetail(){
    	$memberMsgModel = $this->getTmemberMsgModel();
    	$nodeId = $this->node_id;
    	$id = I('get.mId');
    	$rowMsgInfo = $memberMsgModel->getRowMessage($nodeId, $id);
    	$this->assign('rowMsgInfo',$rowMsgInfo);
    	$this->display('Integral/sysMsgDetail');
    }
    /**
     * @return IntegralChangeNoticeSetModel
     */
    public function getIntegralChangeNoticeSetModel(){
    	return D('IntegralChangeNoticeSet');
    }

    public function autoSendMessage(){

        $this->display('Integral/autoSendMessage');
    }
    //会员卡的消息
    public function assignJsonData(){
        $nodeId                  = $this->node_id;
        $integralChangeNoticeSet = $this->getIntegralChangeNoticeSetModel();
        $allTemplates          = $integralChangeNoticeSet->allTemplate($nodeId);

        //空模板
        $emptyTemplate = array(
                'system_msg'            => 0,
                'system_msg_templet'    => '',
                'sms_msg'               => 0,
                'sms_msg_templet'       => '',
                'wx_msg'                => 0,
                'wx_msg_templet'        => '',
                'isFirstSet'            => 1         //是否第一次设置   1:是  0:不是
        );
        //会员卡
        $memberCardData = $emptyTemplate;
        //获得积分
        $plusIntegralData = $emptyTemplate;
        //消耗积分
        $reduceIntegralData = $emptyTemplate;
        foreach($allTemplates as $key => $value){
            //会员卡
            if($value['notice_type'] == 1){
                $memberCardData = $value;
                $memberCardData['system_msg_templet'] = json_decode($memberCardData['system_msg_templet'],true);
                $memberCardData['sms_msg_templet'] = json_decode($memberCardData['sms_msg_templet'],true);
                $memberCardData['wx_msg_templet'] = json_decode($memberCardData['wx_msg_templet'],true);
                $memberCardData['isFirstSet'] = 0;
            }
            //获得积分
            if($value['notice_type'] == 2){
                $plusIntegralData = $value;
                $plusIntegralData['system_msg_templet'] = json_decode($plusIntegralData['system_msg_templet'],true);
                $plusIntegralData['sms_msg_templet'] = json_decode($plusIntegralData['sms_msg_templet'],true);
                $plusIntegralData['wx_msg_templet'] = json_decode($plusIntegralData['wx_msg_templet'],true);
                $plusIntegralData['isFirstSet'] = 0;
            }
            //消耗积分
            if($value['notice_type'] == 3){
                $reduceIntegralData = $value;
                $reduceIntegralData['system_msg_templet'] = json_decode($reduceIntegralData['system_msg_templet'],true);
                $reduceIntegralData['sms_msg_templet'] = json_decode($reduceIntegralData['sms_msg_templet'],true);
                $reduceIntegralData['wx_msg_templet'] = json_decode($reduceIntegralData['wx_msg_templet'],true);
                $reduceIntegralData['isFirstSet'] = 0;
            }
        }
        $this->ajaxReturn(array('status'=>1,'info'=>array('memberCardData'=>$memberCardData,'plusIntegralData'=>$plusIntegralData,'reduceIntegralData'=>$reduceIntegralData)),'JSON');
    }
    /*
    //会员卡的消息
    public function memberCard(){
    	$nodeId                  = $this->node_id;
    	$integralChangeNoticeSet = $this->getIntegralChangeNoticeSetModel();
    	$memberCardData          = $integralChangeNoticeSet->messageTemplate($nodeId,1);
    	//是否第一次设置   1:是  0:不是
    	$isFirstSet = 0;
    	$verify = array_filter($memberCardData);
    	if(empty($verify)){
    		$isFirstSet = 1;
    		$memberCardData = array(
    				'system_msg'            => 0,
    				'system_msg_templet'    => '',
    				'sms_msg'               => 0,
    				'sms_msg_templet'       => '',
    				'wx_msg'                => 0,
    				'wx_msg_templet'        => array(
    						'weChatTemplateId'  => '',
    						'welcome'           => '',
    						'end'               => ''
    				)
    		);
    	}else{
    		$memberCardData['system_msg_templet'] = json_decode($memberCardData['system_msg_templet'],true);
    		$memberCardData['sms_msg_templet'] = json_decode($memberCardData['sms_msg_templet'],true);
    		$memberCardData['wx_msg_templet'] = json_decode($memberCardData['wx_msg_templet'],true);
    	}

    	$this->assign('isFirstSet',$isFirstSet);
    	$this->assign('memberCardData',$memberCardData);
    	$this->display('Integral/memberCard');
    }
    */
    //增加积分的消息
    public function plusIntegral(){
    	$nodeId                  = $this->node_id;
    	$integralChangeNoticeSet = $this->getIntegralChangeNoticeSetModel();
    	$plusIntegralData          = $integralChangeNoticeSet->messageTemplate($nodeId,2);
    	//是否第一次设置   1:是  0:不是
    	$isFirstSet = 0;
    	$verify = array_filter($plusIntegralData);
    	if(empty($verify)){
    		$isFirstSet = 1;
    		$plusIntegralData = array(
    				'system_msg'            => 0,
    				'system_msg_templet'    => '',
    				'sms_msg'               => 0,
    				'sms_msg_templet'       => '',
    				'wx_msg'                => 0,
    				'wx_msg_templet'        => array(
    						'weChatTemplateId'  => '',
    						'welcome'           => '',
    						'end'               => ''
    				)
    		);
    	}else{
    		$plusIntegralData['system_msg_templet'] = json_decode($plusIntegralData['system_msg_templet'],true);
    		$plusIntegralData['sms_msg_templet'] = json_decode($plusIntegralData['sms_msg_templet'],true);
    		$plusIntegralData['wx_msg_templet'] = json_decode($plusIntegralData['wx_msg_templet'],true);
    	}

    	$this->assign('isFirstSet',$isFirstSet);
    	$this->assign('plusIntegralData',$plusIntegralData);
    	$this->display('Integral/plusIntegral');
    }
    //消耗积分的消息
    public function reduceIntegral(){
    	$nodeId                  = $this->node_id;
    	$integralChangeNoticeSet = $this->getIntegralChangeNoticeSetModel();
    	$reduceIntegralData          = $integralChangeNoticeSet->messageTemplate($nodeId,3);
    	//是否第一次设置   1:是  0:不是
    	$isFirstSet = 0;
    	$verify = array_filter($reduceIntegralData);
    	if(empty($verify)){
    		$isFirstSet = 1;
    		$reduceIntegralData = array(
    				'system_msg'            => 0,
    				'system_msg_templet'    => '',
    				'sms_msg'               => 0,
    				'sms_msg_templet'       => '',
    				'wx_msg'                => 0,
    				'wx_msg_templet'        => array(
    						'weChatTemplateId'  => '',
    						'welcome'           => '',
    						'end'               => ''
    				)
    		);
    	}else{
    		$reduceIntegralData['system_msg_templet'] = json_decode($reduceIntegralData['system_msg_templet'],true);
    		$reduceIntegralData['sms_msg_templet'] = json_decode($reduceIntegralData['sms_msg_templet'],true);
    		$reduceIntegralData['wx_msg_templet'] = json_decode($reduceIntegralData['wx_msg_templet'],true);
    	}

    	$this->assign('isFirstSet',$isFirstSet);
    	$this->assign('reduceIntegralData',$reduceIntegralData);
    	$this->display('Integral/reduceIntegral');
    }
    //自动发送设置   提交表单
    public function autoSendMessageSubmit()
    {
        $integralChangeNoticeSet = $this->getIntegralChangeNoticeSetModel();
        $data                    = $this->verifySendMessageSubmit();
        $nodeId                  = $this->node_id;
        //入库数据
        if ($data['isFirst'] == 1) {
            $isOk = $integralChangeNoticeSet->addMessageTemplate($data['data']);
            if ($isOk) {
                $this->ajaxReturn(array('status' => 1, 'info' => '添加成功'));
            } else {
                $this->ajaxReturn(array('status' => 0, 'info' => '添加失败'));
            }
        }else{
            $isOk   = $integralChangeNoticeSet->modMessageTemplate($nodeId, $data['data']['notice_type'], $data['data']);
            if ($isOk === false) {
                $this->ajaxReturn(array('status' => 0, 'info' => '修改失败'));
            }
            $this->ajaxReturn(array('status' => 1, 'info' => '修改成功'));
        }

    }

    // 验证自动发送消息的模板数据
    public function verifySendMessageSubmit()
    {
        $nodeId   = $this->node_id;
        $postData = I('post.');
        //开始验证数据
        if (in_array($postData['msgType'], array(1, 2, 3)) === false) {
            $this->ajaxReturn(array('status' => 0, 'info' => '模板设置错误'), 'JSON');
        }
        if ($postData['sysMsgStatus'] == '') {
            $this->ajaxReturn(array('status' => 0, 'info' => '消息状态错误'), 'JSON');
        }
        if ($postData['sysMsgStatus'] == 1) {
            if (empty($postData['sysMsgContent'])) {
                $this->ajaxReturn(array('status' => 0, 'info' => '系统消息内容不能为空'), 'JSON');
            }
        } else {
            $postData['sysMsgStatus'] = 0;
        }
        //因发送短信资费问题，暂时所有的短信消息全部关闭
        $postData['smsMessageStatus'] = 0;
        /*
        if ($postData['smsMessageStatus'] == '') {
            $this->ajaxReturn(array('status' => 0, 'info' => '短信状态错误'), 'JSON');
        }
        */
        if ($postData['weChatTemplateStatus'] == '') {
            $this->ajaxReturn(array('status' => 0, 'info' => '微信模板状态错误'), 'JSON');
        }
        if ($postData['weChatTemplateStatus'] == 1) {
            if (empty($postData['weChatTemplateId'])) {
                $this->ajaxReturn(array('status' => 0, 'info' => '请填写微信模板ID'), 'JSON');
            }
        }
        //存储模板的基础数据
        $data = array(
                'node_id'            => $nodeId,
                'notice_type'        => $postData['msgType'],
                'system_msg'         => $postData['sysMsgStatus'],
                'system_msg_templet' => '',
                'sms_msg'            => $postData['smsMessageStatus'],
                'sms_msg_templet'    => '',
                'wx_msg'             => $postData['weChatTemplateStatus'],
                'wx_msg_templet'     => ''
        );
        /***********会员中心的消息*********/
        if ($postData['sysMsgStatus'] == 1) {
            $data['system_msg_templet'] = json_encode($postData['sysMsgContent']);
        }
        /***********短信模板的消息*********/
        if($postData['smsMessageStatus'] == 1){
            $integralName = M('tintegral_node_config')->where(array('node_id'=>$this->node_id))->getField('integral_name');
            if(empty($integralName)){
                $integralName = '积分';
            }
            if($postData['msgType'] == 3){
                $data['sms_msg_templet'] = json_encode("<-姓名->您使用<-使用积分值->{$integralName}。");
            }elseif($postData['msgType'] == 2){
                $data['sms_msg_templet'] = json_encode("<-姓名->您获得<-获得积分值->{$integralName}。");
            }else{
                $data['sms_msg_templet'] = json_encode("<-姓名->您的会员卡已经升级到<-会员卡名称->。");
            }
        }
        /***********微信模板的消息*********/
        if($postData['weChatTemplateStatus'] == 1){
            $leftData = array();
            //具体内容部分
            $allKeyVal = explode(',',$postData['allName']);
            $allKeyVal = array_flip($allKeyVal);
            foreach($allKeyVal as $key => $value){
                if(isset($postData['left_'.$key])){
                    $leftData[$key] = $postData['left_'.$key];
                }
            }
            $contentKeyVal = array_intersect_key($postData,$allKeyVal);

            $weChatTemplate = array(
                'weChatTemplateId'  => trim($postData['weChatTemplateId']),
                'content'           => array('contentKeyVal'=>$contentKeyVal,'leftData'=>$leftData,'title'=>$postData['title'],'allName'=>$postData['allName'])
            );
            //当欢迎语为固定文字的时候
            if(!empty($postData['fixed'])){
                $weChatTemplate['content']['fixed'] = $postData['fixed'];
            }
            $data['wx_msg_templet'] = json_encode($weChatTemplate);
        }
        
        return array('isFirst' => $postData['isFirstSet'], 'data' => $data);
    }
    /**
     * @return WeiXinSendService
     */
    public function getWinXinService(){
        return D('WeiXinSend', 'Service');
    }
    /**
     * 整理微信消息模板字符串，供前台页面使用
     * @return mixed
     */
    public function cardingStr(){
        $templateId = I('templateId','');         //模板ID
        $nodeId = $this->node_id;
        $weiXinService = $this->getWinXinService();
        $templateList = $weiXinService->getMessageList($nodeId);
        //匹配上的模板
        $rowTemplate = '';
        //模板标题
        $title = '';
        foreach($templateList['template_list'] as $key => $value){
            if($value['template_id'] == $templateId){
                $rowTemplate = $value['content'];
                $title = $value['title'];
            }
        }
        if(empty($rowTemplate)){
            log_write('templateId:'.$templateId);
            $this->ajaxReturn(array('status'=>0,'info'=>'获取模板错误！'),'JSON');
        }
        $repStr = str_replace("\n\n","\n",$rowTemplate);
        $repStr = str_replace("\r\n","\n",$repStr);
        $contArr = explode("\n", $repStr);
        //保存欢迎语和结束语   并删除
        $firstStr = $contArr[0];
        array_shift($contArr);        //头部出栈
        $lastStr = end($contArr);
        array_pop($contArr);          //末尾出栈

        //所有输入框中的name属性值
        $nameArr = array();
        //结果数组
        $result = array();
//      //欢迎语
        preg_match('/(.*)\{\{(.*)\.DATA}}(.*)/',$firstStr,$resultArr);
        if(!empty($resultArr[2])){          //当有固定内容时
            $result['welcome']['value'] = '';
            $result['welcome']['name'] = $resultArr[2];
            $nameArr[] = $resultArr[2];
        }else{

            if(empty($resultArr[1]) && empty($resultArr[3])){
                $result['welcome']['value'] = $firstStr;
            }else{
                $result['welcome']['value'] = $resultArr[1].$resultArr[3];
            }
            $result['welcome']['name'] = '';
        }
        //结束语
        preg_match('/(.*)\{\{(.*)\.DATA}}(.*)/',$lastStr,$resultArr);
        $result['last'] = array('value'=>'','name'=>$resultArr[2]);
        $nameArr[] = $resultArr[2];

        foreach($contArr as $key => $value){
            $data = str_replace("：",":",$value);
            $data = explode(':',$data);
            //整理冒号左边的内容
            preg_match('/(.*)\{\{(.*)\.DATA}}(.*)/',$data[0],$resultArr);
            if(empty($resultArr[1]) && empty($resultArr[3])){          //当左边没有可控内容的时候
                if(empty($resultArr[2])){
                    $result['list'][$key]['left'] = $data[0];
                }else{
                    $result['list'][$key]['left'] = '';
                }
            }else{
                $result['list'][$key]['left'] = $resultArr[1].$resultArr[3];
            }

            //整理冒号右边的内容
            if(!empty($data[1])){
                preg_match('/.*\{\{(.*)\..*/',$data[1],$resultArr);
                $result['list'][$key]['right'] = $resultArr[1];
                $nameArr[] = $resultArr[1];
            }else{   //容错
                $result['list'][$key]['right'] = '';
            }
        }

        $this->ajaxReturn(array('status'=>1,'info'=>$result,'allName'=>implode(',',$nameArr),'title'=>$title),'JSON');
        exit;
    }


}