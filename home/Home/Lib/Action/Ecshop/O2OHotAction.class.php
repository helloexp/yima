<?php

/*
 * 单品销售
 */
class O2OHotAction extends BaseAction {

    public $isInterGral = false;
    // 积分权限
    public function _initialize() {
        parent::_initialize();
        // 判断积分权限
        $this->isInterGral = $this->_hasIntegral($this->node_id);
        // 取得积分规则信息
        $intergralType = D('SalePro', 'Service')->getNodeRule($this->node_id, 
            'tintegral_rule_main');
        if ('0' === $intergralType)
            $this->isInterGral = false;
        $this->assign('isIntergral', $this->isInterGral); // 订购权限
    }

    public function firstIndex() {
        $today = date('Ymd');
        $yesterday = date('Ymd', strtotime("-1 day"));
        $batch_type = array(
            'in', 
            '26,27,55');
        $_get = I('get.');
        // 查询
        $_get['begin_date'] = $begin_date = I('begin_date', 
            dateformat("-30 days", 'Ymd'));
        $_get['end_date'] = $end_date = I('end_date', 
            dateformat("0 days", 'Ymd'));
        $map = array(
            'batch_type' => $batch_type, 
            'node_id' => $this->node_id);
        
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
        
        $shop_jsChartDataClick = array(); // 单品PV访问量
        $shop_jsChartDataOrder = array(); // 单品订单数
        $shop_jsChartDataAmt = array(); // 单品销售额
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
        
        // 单品访问量
        $pv_arr = M('Tdaystat')->where($map)->field(
            "day,sum(click_count) click_count")->group("day")->select();
        // 单品-计算出JS值
        foreach ($pv_arr as $v) {
            $shop_jsChartDataClick[$v['day']] = array(
                $v['day'], 
                $v['click_count'] * 1);
            if ($v['day'] == $today)
                $shop_data['PV'][$today] = $v['click_count'] * 1;
            if ($v['day'] == $yesterday)
                $shop_data['PV'][$yesterday] = $v['click_count'] * 1;
        }
        // 单品订单数
        $order_map = array(
            'order_type' => '0', 
            'order_status' => '0', 
            'pay_status' => '2', 
            'node_id' => $this->node_id);
        $order_map['batch_no'] = array(
            'exp', 
            "in (select id from tmarketing_info where batch_type in (26,27,55) and node_id='{$this->node_id}')");
        // 单品查询日期
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
        $order_arr = M("ttg_order_info")->field(
            "count(order_id) as order_count,substr(add_time,'1',8) as day,sum(order_amt) as order_amt")->where(
            $order_map)->group("substr(add_time,1,8)")->select();
        
        // 单品-计算出JS值
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
        ksort($shop_jsChartDataAmt);
        ksort($shop_jsChartDataOrder);
        // 单品数量
        $todayTime = date('YmdHis', time());
        $goods_count = M('tmarketing_info')->where(
            array(
                'node_id' => $this->node_id, 
                'status' => '1', 
                'batch_type' => array(
                    'in', 
                    '26,27,55'), 
                'start_time' => array(
                    'ELT', 
                    $todayTime), 
                'end_time' => array(
                    'EGT', 
                    $todayTime)))->count();
        $this->assign('_get', $_get);
        $this->assign('goods_count', $goods_count);
        $this->assign('shop_jsChartDataClick', 
            json_encode(array_values($shop_jsChartDataClick)));
        $this->assign('shop_jsChartDataOrder', 
            json_encode(array_values($shop_jsChartDataOrder)));
        $this->assign('shop_jsChartDataAmt', 
            json_encode(array_values($shop_jsChartDataAmt)));
        $this->assign('shop_data', $shop_data);
        $this->assign('today', $today);
        $this->assign('yesterday', $yesterday);
        $this->display();
    }

    public function singleOrder() {
        $batch_type = I('get.batch_type', 
            array(
                'in', 
                '26,27'), 'trim,htmlspecialchars');
        $isNew = I('get.is_new', null, 'trim,htmlspecialchars');
        if ($batch_type == "") {
            $batch_type = array(
                'in', 
                '26,27');
        }
        // 更新订单通知表 把未读记录更新成已读
        $notice_data = array(
            "status" => 1);
        $result = M('torder_notice')->where(
            "node_id = '" . $this->node_id . "'")->save($notice_data);
        
        $model = M('ttg_order_info');
        $map = array(
            'm.node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"), 
            'm.batch_type' => $batch_type, 
            'c.sns_type' => array(
                'neq', 
                '53'));
        empty($isNew) or $map['m.is_new'] = $isNew;
        $data = $_REQUEST;
        
        if ($data['order_id'] != '') {
            $map['o.order_id'] = $data['order_id'];
        }
        if ($data['pay_status'] != '') {
            $map['o.pay_status'] = $data['pay_status'];
        }
        if ($data['order_status'] != '') {
            $map['o.order_status'] = $data['order_status'];
        }
        if ($data['delivery_status'] != '') {
            $map['o.delivery_status'] = $data['delivery_status'];
        }
        if ($data['receiver_type'] != '') {
            $map['o.receiver_type'] = $data['receiver_type'];
        }
        if ($data['saler_id'] != '') {
            $map['sc.id'] = $data['saler_id'];
        }
        if ($data['is_gift'] != '') {
            $map['o.is_gift'] = $data['is_gift'];
        }
        if ($data['rece_phone'] != '') {
            $map['o.receiver_phone'] = $data['rece_phone'];
        }
        if ($data['order_phone'] != '') {
            $map['o.order_phone'] = $data['order_phone'];
        }
        if ($data['channel_name'] != '') {
            $map['c.name'] = array(
                'like', 
                '%' . $data['channel_name'] . '%');
        }
        // 处理特殊查询字段
        $beginDate = I('start_time', null, 'mysql_real_escape_string,trim');
        $endDate = I('end_time', null, 'mysql_real_escape_string,trim');
        if (! empty($beginDate) && ! empty($endDate)) {
            $map['o.add_time'] = array(
                'between', 
                $beginDate . '00,' . $endDate . '59');
        } elseif (! empty($beginDate) && empty($endDate)) {
            $map['o.add_time'] = array(
                'egt', 
                $beginDate . '00');
        } elseif (empty($beginDate) && ! empty($endDate)) {
            $map['o.add_time'] = array(
                'elt', 
                $endDate . '59');
        }
        $err_flag = I('err_flag', 0, 'intval'); // 0 正常订单 1错误订单
                                                // 判断是否有因为商户黑名单，无余额等原因导致发码失败的订单
        $err_count = M()->table('ttg_order_info o')
            ->join('torder_trace ot on ot.order_id=o.order_id')
            ->join('tbarcode_trace b on b.request_id=ot.code_trace')
            ->where(
            array(
                'o.node_id' => $this->node_id, 
                'b.trans_type' => '0001', 
                'b.status' => '3'))
            ->count();
        // 判断是否错误订单页面
        if ($err_flag == 1) {
            if ($err_count == 0)
                $err_flag = 0;
            else {
                $errOrderList = array();
                $errOrderList = M()->table('torder_trace o')
                    ->field('o.order_id')
                    ->join('tbarcode_trace b on b.request_id=o.code_trace')
                    ->where(
                    array(
                        'b.node_id' => $this->node_id, 
                        'b.trans_type' => '0001', 
                        'b.status' => '3'))
                    ->select();
                $errOrderList = array_valtokey($errOrderList, '', 'order_id');
                // var_dump($errOrderList);
                $map['o.order_id'] = array(
                    'in', 
                    $errOrderList);
            }
        }
        
        import('ORG.Util.Page'); // 导入分页类
        $field = array(
            'o.*,m.group_goods_name,m.group_price');
        $mapcount = M()->table('ttg_order_info o')
            ->field($field)
            ->join("tbatch_channel b ON b.id=o.batch_channel_id")
            ->join("tchannel c ON c.id=b.channel_id")
            ->join("tmarketing_info m ON o.batch_no=m.id")
            ->join("tmember_info sc ON sc.id=o.parm1 and sc.node_id=o.node_id")
            ->where($map)
            ->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
                                         // 查询闪购，码上买，新品推广的订单数,以及总订单数
        $sgmap = $msmmap = $xpmap = $map;
        $sgmap['m.batch_type'] = '26';
        $sgmap['m.is_new'] = '1';
        $msmmap['m.batch_type'] = '27';
        $msmmap['m.is_new'] = '1';
        $xpmap['m.batch_type'] = '27';
        $xpmap['m.is_new'] = '2';
        $sgcount = M()->table('ttg_order_info o')
            ->field($field)
            ->join("tmarketing_info m ON o.batch_no=m.id")
            ->join("tmember_info sc ON sc.id=o.parm1 and sc.node_id=o.node_id")
            ->where($sgmap)
            ->count();
        $msmcount = M()->table('ttg_order_info o')
            ->field($field)
            ->join("tmarketing_info m ON o.batch_no=m.id")
            ->join("tmember_info sc ON sc.id=o.parm1 and sc.node_id=o.node_id")
            ->where($msmmap)
            ->count();
        $xpcount = M()->table('ttg_order_info o')
            ->field($field)
            ->join("tmarketing_info m ON o.batch_no=m.id")
            ->join("tmember_info sc ON sc.id=o.parm1 and sc.node_id=o.node_id")
            ->where($xpmap)
            ->count();
        foreach ($data as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        
        $show = $Page->show(); // 分页显示输出
        
        $field2 = array(
            'o.*,m.group_goods_name,m.is_new,m.batch_type,m.group_price,c.name as channel_name,sc.name as petname');
        $orderList = M()->table('ttg_order_info o')
            ->field($field2)
            ->join("tmarketing_info m ON o.batch_no=m.id")
            ->join("tbatch_channel b ON b.id=o.batch_channel_id")
            ->join("tchannel c ON c.id=b.channel_id")
            ->join("tmember_info sc ON sc.id=o.parm1 and sc.node_id=o.node_id")
            ->where($map)
            ->order('add_time desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        $salerInfo = M('tmember_info')->where(
            array(
                'node_id' => $this->node_id))->getField('id,name');
        $payStatus = array(
            '1' => '未支付', 
            '2' => '已支付');
        $orderStatus = array(
            '0' => '正常', 
            '1' => '已过期', 
            '2' => '已取消');
        $deliveryStatus = array(
            '1' => '待配送', 
            '2' => '配送中', 
            '3' => '已配送', 
            '4' => '凭证自提');
        $receiverType = array(
            '0' => ' 凭证自提订单', 
            '1' => '物流订单');
        $marketType = array(
            '26' => '闪购', 
            '27' => '码上买', 
            '272' => '新品发售');
        $gift_arr = array(
            '0' => '自消费', 
            '1' => '送礼');
        // print_r($orderList);exit;
        if (! empty($orderList)) {
            foreach ($orderList as $k => $v) {
                if ($v['is_new'] == '2') {
                    $orderList[$k]['batch_type'] = "272";
                }
            }
        }
        // if($this->node_id == '00004488')
        if (in_array($this->node_id, C('fb_tongbaozhai.node_id'), true)) {
            $tongbaozhai_flag = 1;
            $empty = '<tr><td colspan="10">无数据</td></span>';
        } else {
            $tongbaozhai_flag = 0;
            $empty = '<tr><td colspan="9">无数据</td></span>';
        }
        // batch_type与is_new向页面传值
        if (is_array($batch_type)) {
            $batch_type = null;
        }
        
        $this->assign('err_flag', $err_flag);
        $this->assign('err_count', $err_count);
        $this->assign('payStatus', $payStatus);
        $this->assign('marketType', $marketType);
        $this->assign('orderStatus', $orderStatus);
        $this->assign('nbatch_type', $batch_type);
        $this->assign('deliveryStatus', $deliveryStatus);
        $this->assign('receiverType', $receiverType);
        $this->assign('orderList', $orderList);
        $this->assign('sgcount', $sgcount);
        $this->assign('msmcount', $msmcount);
        $this->assign('xpcount', $xpcount);
        $this->assign('allcount', $sgcount + $msmcount + $xpcount);
        $this->assign('salerInfo', $salerInfo);
        $this->assign('nis_new', $isNew);
        $this->assign('post', $data);
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('empty', $empty);
        $this->assign('tongbaozhai_flag', $tongbaozhai_flag);
        $this->assign('gift_arr', $gift_arr);
        $this->display();
    }

    public function exportOrder() {
        $batch_type = I('get.batch_type', "", 'trim,htmlspecialchars');
        $isNew = I('get.is_new', "", 'trim,htmlspecialchars');
        // 查询条件组合
        $where = "WHERE o.node_id in (" . $this->nodeIn() .
             ") and c.sns_type != '53'";
        if (! empty($_POST)) {
            $filter = array();
            $condition = array_map('trim', $_POST); /*
                                                     * if(isset($condition['goods_name'])
                                                     * &&
                                                     * $condition['goods_name']
                                                     * != ''){ $filter[] =
                                                     * "m.group_goods_name LIKE
                                                     * '%{$condition['goods_name']}%'";
                                                     * }
                                                     */
            if (isset($condition['order_id']) && $condition['order_id'] != '') {
                $filter[] = "o.order_id = '{$condition['order_id']}'";
            }
            if (isset($condition['pay_status']) && $condition['pay_status'] != '') {
                $filter[] = "o.pay_status = '{$condition['pay_status']}'";
            }
            if (isset($condition['delivery_status']) &&
                 $condition['delivery_status'] != '') {
                $filter[] = "o.delivery_status = '{$condition['delivery_status']}'";
            }
            if (isset($condition['order_status']) &&
                 $condition['order_status'] != '') {
                $filter[] = "o.order_status = '{$condition['order_status']}'";
            }
            if (isset($condition['receiver_type']) &&
                 $condition['receiver_type'] != '') {
                $filter[] = "o.receiver_type = '{$condition['receiver_type']}'";
            }
            if (isset($condition['start_time']) && $condition['start_time'] != '') {
                $filter[] = "o.add_time >='{$condition['start_time']}00'";
            }
            if (isset($condition['end_time']) && $condition['end_time'] != '') {
                $filter[] = "o.add_time <= '{$condition['end_time']}59'";
            }
            if (isset($condition['saler_id']) && $condition['saler_id'] != '') {
                $filter[] = "sc.id = '{$condition['saler_id']}'";
            }
            if (isset($condition['rece_phone']) && $condition['rece_phone'] != '') {
                $filter[] = "o.receiver_phone = '{$condition['rece_phone']}'";
            }
            if (isset($condition['order_phone']) &&
                 $condition['order_phone'] != '') {
                $filter[] = "o.order_phone = '{$condition['order_phone']}'";
            }
            if (isset($condition['channel_name']) &&
                 $condition['channel_name'] != '') {
                $filter[] = "c.name LIKE '%{$condition['channel_name']}%'";
            }
        }
        if (! empty($filter)) {
            $condition = implode(' AND ', $filter);
            $where .= ' AND ' . $condition;
        }
        
        $filter[] = "o.node_id in({$this->nodeIn()})";
        $count = M()->table("ttg_order_info o")->join(
            "tmember_info sc ON sc.id=o.parm1 and sc.node_id=o.node_id")
            ->join("tbatch_channel b ON b.id=o.batch_channel_id")
            ->join("tchannel c ON c.id=b.channel_id")
            ->where($filter)
            ->count();
        if ($count <= 0)
            $this->error('无订单数据可下载');
        if ($batch_type == "") {
            $whereNew = $where . ' AND m.batch_type in (26,27) ';
            $where .= ' AND d.batch_type in (26,27) ';
        } else {
            $whereNew = $where . ' AND m.batch_type =' . $batch_type . " ";
            $where .= ' AND d.batch_type =' . $batch_type . " ";
        }
        if (! empty($isNew)) {
            $whereNew = $whereNew . 'AND m.is_new =' . $isNew . " ";
            $where .= 'AND d.is_new =' . $isNew . " ";
        }
        
        /*
         * $sql = "SELECT o.order_id,m.name AS
         * batch_name,m.group_goods_name,d.name AS 'from_batch_name',c.name as
         * from_channel_name
         * ,o.order_phone,o.add_time,o.receiver_name,o.receiver_phone,o.receiver_addr,
         * m.group_price,o.buy_num,o.order_amt,case o.pay_status when '1' then
         * '未支付' when '2' then '已支付' else '其他' end pay_status,o.pay_seq,case
         * o.pay_channel when '0' then '银行卡' when '1' then '支付宝' when '2' then
         * '银行卡' else '其他' end pay_channel,case o.order_status when '0' then
         * '正常' when '1' then '取消' else '其他' end order_status,case
         * o.delivery_status when '1' then '待配送' when '2' then '配送中' when '3'
         * then '已配送' else '其他' end delivery_status,o.send_seq FROM
         * ttg_order_info o JOIN tmarketing_info m ON o.batch_no=m.id JOIN
         * tbatch_channel b ON b.id=o.batch_channel_id JOIN tchannel c ON
         * c.id=b.channel_id JOIN tmarketing_info d ON d.id=o.from_batch_no
         * {$where}";
         */
        $tnodeAccount = M('Tnode_account');
        $tnodeAccountResult = $tnodeAccount->where(
            "account_type='1' AND node_id='{$this->node_id}'")->select();
        if (is_array($tnodeAccountResult)) {
            $tnodeAccountResult[0]['fee_rate'] = $tnodeAccountResult[0]['fee_rate'] ? $tnodeAccountResult[0]['fee_rate'] : 0.02;
        }
        $fee_rate = $tnodeAccountResult[0]['fee_rate'];
        if (in_array($this->node_id, C('fb_tongbaozhai.node_id'), true)) {
            $sql = "(SELECT o.order_id,m.name AS batch_name,m.group_goods_name,d.name AS 'from_batch_name',c.name AS from_channel_name ,o.order_phone,o.add_time,o.delivery_company,o.delivery_number,o.parm1,sc.name as petname,o.receiver_name,o.receiver_phone,o.receiver_addr,ifnull(o.freight, '0.00') freight,
			m.group_price,o.buy_num, o.order_amt,IFNULL(bd.bonus_amount,0.00) AS bonus_amt,
			CASE o.receiver_type WHEN '0' THEN '凭证自提订单' WHEN '1' THEN '物流订单' ELSE '其他' END receiver_type,
			CASE o.pay_status WHEN '1' THEN '未支付' WHEN '2' THEN '已支付' ELSE '其他' END pay_status,o.pay_seq,
			CASE WHEN o.pay_status='2' THEN ROUND(o.order_amt*IFNULL(na.fee_rate,0.02),2) ELSE 0.00 END fee_cost,
				CASE WHEN o.pay_status='2' THEN o.order_amt-ROUND(o.order_amt*IFNULL(na.fee_rate,0.02),2) ELSE 0.00 END real_cost,
			CASE o.pay_channel WHEN '0' THEN '银行卡' WHEN '1' THEN '支付宝' WHEN '2' THEN '银行卡' WHEN '3' THEN '微信支付' ELSE '其他' END pay_channel,
			CASE o.order_status WHEN '0' THEN '正常' WHEN '1' THEN '取消' ELSE '其他' END order_status,
			CASE o.delivery_status WHEN '1' THEN '待配送' WHEN '2' THEN '配送中' WHEN '3' THEN '已配送' ELSE '其他' END delivery_status,tt.send_seq AS send_seq
			FROM ttg_order_info o
			LEFT JOIN tbonus_use_detail bd ON bd.order_id=o.order_id
			JOIN tmarketing_info m ON o.batch_no=m.id
			JOIN tbatch_channel b ON b.id=o.batch_channel_id
			JOIN tchannel c ON c.id=b.channel_id 
			LEFT JOIN(SELECT order_id,GROUP_CONCAT(code_trace) AS send_seq FROM torder_trace GROUP BY order_id) tt ON tt.order_id=o.order_id
			LEFT JOIN tmember_info sc ON sc.id=o.parm1 and sc.node_id=o.node_id 
			JOIN tmarketing_info d ON d.id=o.from_batch_no LEFT JOIN tnode_account na ON na.node_id=o.node_id AND na.account_type=o.pay_channel {$whereNew} AND o.order_type='0')
			UNION
			(SELECT o.order_id,m.b_name AS batch_name,m.b_name AS group_goods_name,d.name AS 'from_batch_name',c.name AS from_channel_name ,o.order_phone,o.add_time,o.delivery_company,o.delivery_number,o.parm1,sc.name as petname,o.receiver_name,o.receiver_phone,o.receiver_addr,ifnull(o.freight, '0.00') freight,
			m.price AS group_price,m.goods_num AS buy_num,o.order_amt AS order_amt,IFNULL(bd.bonus_amount,0.00) AS bonus_amt,
			CASE o.receiver_type WHEN '0' THEN '凭证自提订单' WHEN '1' THEN '物流订单' ELSE '其他' END receiver_type,
			CASE o.pay_status WHEN '1' THEN '未支付' WHEN '2' THEN '已支付' ELSE '其他' END pay_status,o.pay_seq,
			CASE WHEN o.pay_status='2' THEN ROUND(o.order_amt*IFNULL(na.fee_rate,0.02),2) ELSE 0.00 END fee_cost,
				CASE WHEN o.pay_status='2' THEN o.order_amt-ROUND(o.order_amt*IFNULL(na.fee_rate,0.02),2) ELSE 0.00 END real_cost,
			CASE o.pay_channel WHEN '0' THEN '银行卡' WHEN '1' THEN '支付宝' WHEN '2' THEN '银行卡' WHEN '3' THEN '微信支付' ELSE '其他' END pay_channel,
			CASE o.order_status WHEN '0' THEN '正常' WHEN '1' THEN '取消' ELSE '其他' END order_status,
			CASE o.delivery_status WHEN '1' THEN '待配送' WHEN '2' THEN '配送中' WHEN '3' THEN '已配送' ELSE '其他' END delivery_status,tt.send_seq AS send_seq
			FROM ttg_order_info o
			LEFT JOIN tbonus_use_detail bd ON bd.order_id=o.order_id
			LEFT JOIN ttg_order_info_ex m ON m.order_id=o.order_id
			JOIN tbatch_channel b ON b.id=o.batch_channel_id
			JOIN tchannel c ON c.id=b.channel_id 
			JOIN tmarketing_info d ON d.id=o.from_batch_no 
			LEFT JOIN(SELECT order_id,GROUP_CONCAT(code_trace) AS send_seq FROM torder_trace GROUP BY order_id) tt ON tt.order_id=o.order_id
			LEFT JOIN tmember_info sc ON sc.id=o.parm1 and sc.node_id=o.node_id LEFT JOIN tnode_account na ON na.node_id=o.node_id AND na.account_type=o.pay_channel {$where} AND o.order_type='2')";
        } else
            $sql = "(SELECT o.order_id,m.name AS batch_name,m.group_goods_name,d.name AS 'from_batch_name',c.name AS from_channel_name ,o.order_phone,o.add_time,o.delivery_company,o.delivery_number,o.receiver_name,o.receiver_phone,o.receiver_addr,ifnull(o.freight, '0.00') AS freight,
			m.group_price,o.buy_num,o.order_amt,IFNULL(bd.bonus_amount,0.00) AS bonus_amt,
			CASE o.receiver_type WHEN '0' THEN '凭证自提订单' WHEN '1' THEN '物流订单' ELSE '其他' END receiver_type,
			CASE o.pay_status WHEN '1' THEN '未支付' WHEN '2' THEN '已支付' ELSE '其他' END pay_status,o.pay_seq,
			CASE WHEN o.pay_status='2' THEN ROUND(o.order_amt*IFNULL(na.fee_rate,0.02),2) ELSE 0.00 END fee_cost,
				CASE WHEN o.pay_status='2' THEN o.order_amt-ROUND(o.order_amt*IFNULL(na.fee_rate,0.02),2) ELSE 0.00 END real_cost,		
			CASE o.pay_channel WHEN '0' THEN '银行卡' WHEN '1' THEN '支付宝' WHEN '2' THEN '银行卡' WHEN '3' THEN '微信支付' ELSE '其他' END pay_channel,
			CASE o.order_status WHEN '0' THEN '正常' WHEN '1' THEN '取消' ELSE '其他' END order_status,
			CASE o.delivery_status WHEN '1' THEN '待配送' WHEN '2' THEN '配送中' WHEN '3' THEN '已配送' ELSE '其他' END delivery_status,tt.send_seq AS send_seq
			FROM ttg_order_info o
			LEFT JOIN tbonus_use_detail bd ON bd.order_id=o.order_id
			JOIN tmarketing_info m ON o.batch_no=m.id
			JOIN tbatch_channel b ON b.id=o.batch_channel_id
			JOIN tchannel c ON c.id=b.channel_id 
			LEFT JOIN(SELECT order_id,GROUP_CONCAT(code_trace) AS send_seq FROM torder_trace GROUP BY order_id) tt ON tt.order_id=o.order_id
			JOIN tmarketing_info d ON d.id=o.from_batch_no LEFT JOIN tnode_account na ON na.node_id=o.node_id AND na.account_type=o.pay_channel 
			{$whereNew} AND o.order_type='0')
			UNION
			(SELECT o.order_id,m.b_name AS batch_name,m.b_name AS group_goods_name,d.name AS 'from_batch_name',c.name AS from_channel_name ,o.order_phone,o.add_time,o.delivery_company,o.delivery_number,o.receiver_name,o.receiver_phone,o.receiver_addr,ifnull(o.freight, '0.00') AS freight,
			m.price AS group_price,m.goods_num AS buy_num,o.order_amt AS order_amt,IFNULL(bd.bonus_amount,0.00) AS bonus_amt,
			CASE o.receiver_type WHEN '0' THEN '凭证自提订单' WHEN '1' THEN '物流订单' ELSE '其他' END receiver_type,
			CASE o.pay_status WHEN '1' THEN '未支付' WHEN '2' THEN '已支付' ELSE '其他' END pay_status,o.pay_seq,
			CASE WHEN o.pay_status='2' THEN ROUND(o.order_amt*IFNULL(na.fee_rate,0.02),2) ELSE 0.00 END fee_cost,
				CASE WHEN o.pay_status='2' THEN o.order_amt-ROUND(o.order_amt*IFNULL(na.fee_rate,0.02),2) ELSE 0.00 END real_cost,	
			CASE o.pay_channel WHEN '0' THEN '银行卡' WHEN '1' THEN '支付宝' WHEN '2' THEN '银行卡' WHEN '3' THEN '微信支付' ELSE '其他' END pay_channel,
			CASE o.order_status WHEN '0' THEN '正常' WHEN '1' THEN '取消' ELSE '其他' END order_status,
			CASE o.delivery_status WHEN '1' THEN '待配送' WHEN '2' THEN '配送中' WHEN '3' THEN '已配送' ELSE '其他' END delivery_status,tt.send_seq AS send_seq
			FROM ttg_order_info o
			LEFT JOIN tbonus_use_detail bd ON bd.order_id=o.order_id
			LEFT JOIN ttg_order_info_ex m ON m.order_id=o.order_id
			JOIN tbatch_channel b ON b.id=o.batch_channel_id
			JOIN tchannel c ON c.id=b.channel_id 
			LEFT JOIN(SELECT order_id,GROUP_CONCAT(code_trace) AS send_seq FROM torder_trace GROUP BY order_id) tt ON tt.order_id=o.order_id
			JOIN tmarketing_info d ON d.id=o.from_batch_no 
			LEFT JOIN tnode_account na ON na.node_id=o.node_id AND na.account_type=o.pay_channel {$where} AND o.order_type='2'
			)";
        $cols_arr = array(
            'order_id' => '订单号', 
            'batch_name' => '活动名', 
            'group_goods_name' => '商品名称', 
            'from_batch_name' => '来源活动名', 
            'from_channel_name' => '来源渠道名', 
            'receiver_type' => '订单类型', 
            'order_phone' => '下单手机号', 
            'add_time' => '下单时间', 
            'delivery_company' => '快递公司', 
            'delivery_number' => '物流单号', 
            'receiver_name' => '收货人姓名', 
            'receiver_phone' => '收货人手机号', 
            'receiver_addr' => '收货地址', 
            'group_price' => '商品单价', 
            'buy_num' => '商品数量', 
            'order_amt' => '订单金额', 
            'bonus_amt' => '优惠金额（红包）', 
            'freight' => '运费', 
            'pay_status' => '支付状态', 
            'fee_cost' => '支付扣费', 
            'real_cost' => '实收金额', 
            'pay_seq' => '支付流水', 
            'pay_channel' => '支付方式', 
            'order_status' => '订单状态', 
            'send_seq' => '发码流水号');
        if (in_array($this->node_id, C('fb_tongbaozhai.node_id'), true)) {
            $cols_arr['parm1'] = '销售员ID';
            $cols_arr['petname'] = '销售员姓名';
        }
        if (querydata_download($sql, $cols_arr, M()) == false) {
            $this->error('下载失败');
        }
    }

    public function dataAnalyse() {
        $batch_type = I('get.batch_type', "", 'trim,htmlspecialchars');
        if ('55' != $batch_type) {
            $this->powerCheck();
        }
        $isNew = I('get.is_new', "", 'trim,htmlspecialchars');
        if ($batch_type == "")
            $batch_type = array(
                'in', 
                '26,27');
        $today = date('Ymd');
        $yesterday = date('Ymd', strtotime("-1 day"));
        $_get = I('get.');
        // 查询
        $_get['begin_date'] = $begin_date = I('begin_date', 
            dateformat("-30 days", 'Ymd'));
        $_get['end_date'] = $end_date = I('end_date', 
            dateformat("0 days", 'Ymd'));
        $map = array(
            'd.batch_type' => $batch_type, 
            'd.node_id' => $this->node_id);
        if ($isNew != "") {
            $map['m.is_new'] = $isNew;
        }
        // 查询日期
        $map['d.day'] = array();
        if ($begin_date != '') {
            $map['d.day'][] = array(
                'EGT', 
                $begin_date);
        }
        if ($end_date != '') {
            $map['d.day'][] = array(
                'ELT', 
                $end_date);
        }
        
        $shop_jsChartDataClick = array(); // 单品PV访问量
        $shop_jsChartDataOrder = array(); // 单品订单数
        $shop_jsChartDataAmt = array(); // 单品销售额
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
        
        // 单品访问量
        $pv_arr = M()->table("Tdaystat d")->where($map)
            ->field("d.day day,sum(d.click_count) click_count")
            ->join("RIGHT JOIN tmarketing_info m ON m.id=d.batch_id")
            ->group("d.day")
            ->select();
        // 单品-计算出JS值
        foreach ($pv_arr as $v) {
            $shop_jsChartDataClick[$v['day']] = array(
                $v['day'], 
                $v['click_count'] * 1);
            if ($v['day'] == $today)
                $shop_data['PV'][$today] = $v['click_count'] * 1;
            if ($v['day'] == $yesterday)
                $shop_data['PV'][$yesterday] = $v['click_count'] * 1;
        }
        // 单品订单数
        $order_map = array(
            'order_type' => '0', 
            'order_status' => '0', 
            'pay_status' => '2', 
            'node_id' => $this->node_id);
        // 单品查询日期
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
        $todayTime = date('YmdHis', time());
        if ($batch_type == '26') {
            $preTitle = "闪购-";
            $order_map['batch_no'] = array(
                'exp', 
                "in (select id from tmarketing_info where batch_type='26' and node_id='{$this->node_id}')");
            // 单品数量
            $goods_count = M('tmarketing_info')->where(
                array(
                    'node_id' => $this->node_id, 
                    'status' => '1', 
                    'batch_type' => $batch_type, 
                    'is_new' => '1', 
                    'start_time' => array(
                        'ELT', 
                        $todayTime), 
                    'end_time' => array(
                        'EGT', 
                        $todayTime)))->count();
        } else if ($batch_type == '27') {
            if ($isNew == '2') {
                $preTitle = "新品发售-";
                $order_map['batch_no'] = array(
                    'exp', 
                    "in (select id from tmarketing_info where batch_type='27' and node_id='{$this->node_id}' and is_new='2')");
                // 单品数量
                $goods_count = M('tmarketing_info')->where(
                    array(
                        'node_id' => $this->node_id, 
                        'status' => '1', 
                        'batch_type' => $batch_type, 
                        'is_new' => $isNew, 
                        'start_time' => array(
                            'ELT', 
                            $todayTime), 
                        'end_time' => array(
                            'EGT', 
                            $todayTime)))->count();
            } else {
                $preTitle = "码上买-";
                $order_map['batch_no'] = array(
                    'exp', 
                    "in (select id from tmarketing_info where batch_type='27' and node_id='{$this->node_id}')");
                // 单品数量
                $goods_count = M('tmarketing_info')->where(
                    array(
                        'node_id' => $this->node_id, 
                        'status' => '1', 
                        'batch_type' => $batch_type, 
                        'is_new' => $isNew, 
                        'start_time' => array(
                            'ELT', 
                            $todayTime), 
                        'end_time' => array(
                            'EGT', 
                            $todayTime)))->count();
            }
        } else if ($batch_type == '55') {
            $preTitle = "吴刚砍树-";
            $order_map['batch_no'] = array(
                'exp', 
                "in (select id from tmarketing_info where batch_type='55' and node_id='{$this->node_id}')");
            // 单品数量
            $goods_count = M('tmarketing_info')->where(
                array(
                    'node_id' => $this->node_id, 
                    'status' => '1', 
                    'batch_type' => $batch_type, 
                    'is_new' => $isNew, 
                    'start_time' => array(
                        'ELT', 
                        $todayTime), 
                    'end_time' => array(
                        'EGT', 
                        $todayTime)))->count();
        } else {
            $preTitle = "";
            $order_map['batch_no'] = array(
                'exp', 
                "in (select id from tmarketing_info where batch_type in (26,27,55) and node_id='{$this->node_id}')");
            // 单品数量
            $goods_count = M('tmarketing_info')->where(
                array(
                    'node_id' => $this->node_id, 
                    'status' => '1', 
                    'batch_type' => $batch_type, 
                    'start_time' => array(
                        'ELT', 
                        $todayTime), 
                    'end_time' => array(
                        'EGT', 
                        $todayTime)))->count();
        }
        $order_arr = M("ttg_order_info")->field(
            "count(order_id) as order_count,substr(add_time,'1',8) as day,sum(order_amt) as order_amt")->where(
            $order_map)->group("substr(add_time,1,8)")->select();
        
        // 单品-计算出JS值
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
        ksort($shop_jsChartDataOrder);
        ksort($shop_jsChartDataAmt);
        // 还原batch_type的值，方便传值到页面
        if (is_array($batch_type)) {
            $batch_type = "";
        }
        $this->assign('_get', $_get);
        $this->assign('goods_count', $goods_count);
        $this->assign('batch_type_flag', $batch_type);
        $this->assign('is_new', $isNew);
        $this->assign('preTitle', $preTitle);
        $this->assign('shop_jsChartDataClick', 
            json_encode(array_values($shop_jsChartDataClick)));
        $this->assign('shop_jsChartDataOrder', 
            json_encode(array_values($shop_jsChartDataOrder)));
        $this->assign('shop_jsChartDataAmt', 
            json_encode(array_values($shop_jsChartDataAmt)));
        $this->assign('shop_data', $shop_data);
        $this->assign('today', $today);
        $this->assign('yesterday', $yesterday);
        $this->display();
    }

    public function index() {
        $_post = $_REQUEST;
        // 爆款销售
        $batch_type = I('batch_type', null, 'trim,intval');
        if (! $batch_type)
            $batch_type = 27; // 默认码上买类型
        if ('55' != $batch_type) {
            $this->powerCheck();
        }
        // else{
        // $this->error('尊敬的旺财用户，该活动已停用！');
        // }
        $key = I('key', null, 'trim');
        $batch_name = I('batch_name', null, 'trim');
        $begin_time = I('begin_time', null, 'trim');
        $is_new = I('is_new', null, 'trim');
        if ($is_new == "") {
            $is_new = 1;
        }
        $end_time = I('end_time', null, 'trim');
        $batch_status = I('batch_status', null, 'trim,intval');
        if (! $batch_name && $key)
            $batch_name = $key;
        $map = array(
            'm.node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"), 
            'm.batch_type' => $batch_type);
        if ($batch_name) {
            $map['m.name'] = array(
                'like', 
                '%' . $batch_name . '%');
        }
        if ($begin_time) {
            $map['m.start_time'] = array(
                'egt', 
                $begin_time . '000000');
        }
        if ($end_time) {
            $map['m.end_time'] = array(
                'elt', 
                $end_time . '235959');
        }
        if ($batch_status) {
            $map['m.status'] = $batch_status;
        }
        if ($is_new && $batch_type == '27') {
            $map['m.is_new'] = $is_new;
        }
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = M()->table("tmarketing_info m")->where($map)->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        if(isset($data)){
            foreach ($data as $key => $val) {
                $Page->parameter .= "&$key=" . urlencode($val) . '&';
            }
        }
        $show = $Page->show(); // 分页显示输出
        
        $list = M()->table("tmarketing_info m")->field('m.*,b.storage_num,b.remain_num')
            ->join('tbatch_info b on b.m_id=m.id')
            ->where($map)
            ->order('m.id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        // 补充销售部分数据
        foreach ($list as &$v) {
            // 锁定数量
            $lock_count = M('ttg_order_info')->where(
                array(
                    'node_id' => $this->node_id, 
                    'batch_no' => $v['id'], 
                    'order_status' => '0', 
                    'pay_status' => '1'))->sum('buy_num');
            $sale_count = M('ttg_order_info')->where(
                array(
                    'node_id' => $this->node_id, 
                    'batch_no' => $v['id'], 
                    'order_status' => '0', 
                    'pay_status' => '2'))->sum('buy_num');
            if (! $lock_count) {
                $v['lock_num'] = 0;
            } else {
                $v['lock_num'] = $lock_count;
            }
            
            if (! $sale_count) {
                $v['sale_num'] = 0;
            } else {
                $v['sale_num'] = $sale_count;
            }
        }
        // 为闪购和码上买创建独立自渠道
        if (in_array($batch_type, 
            array(
                '26', 
                '27'))) {
            $channelModel = M('tchannel');
            if ($batch_type == '26') {
                $sns_type = 45;
                $name = '闪购默认渠道';
            } else {
                $sns_type = 47;
                $name = '码上买默认渠道';
            }
            
            $channelId = $channelModel->where(
                array(
                    'node_id' => $this->node_id, 
                    'type' => '4', 
                    'sns_type' => $sns_type, 
                    'status' => '1', 
                    'sns_type' => array(
                        'neq', 
                        '53')))->getField('id');
            if (! $channelId) {
                $data = array(
                    'name' => $name, 
                    'type' => '4', 
                    'sns_type' => $sns_type, 
                    'status' => '1', 
                    'node_id' => $this->node_id, 
                    'add_time' => date('YmdHis'));
                $channelId = $channelModel->add($data);
                if (! $channelId)
                    $this->error('系统出错创建渠道失败');
            }
        }
        $batchStatusArr = array(
            '1' => '正常', 
            '2' => '停用');
        $empty = '<tr><td colspan="9">未找到数据</td></span>';
        $this->assign('list', $list);
        $this->assign('batch_type', $batch_type);
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('batchStatusArr', $batchStatusArr);
        $this->assign('post', $_post);
        $this->assign('empty', $empty);
        $this->assign('is_new', $is_new);
        $this->assign('hotLine', 
            get_node_info($this->nodeId, 'node_service_hotline')); // 吴刚砍树活动认证版和标准版特殊处理
        $this->display();
    }
    
    // 数据导出
    public function export() {
        if ('55' != $batch_type) {
            $this->powerCheck();
        }
        // 查询条件组合
        $where = "WHERE 1=1 ";
        if (! empty($_POST)) {
            $filter = array();
            $condition = array_map('trim', $_POST);
            if (isset($condition['batch_type']) && $condition['batch_type'] != '') {
                $filter[] = "t.batch_type = '{$condition['batch_type']}'";
            }
            if (isset($condition['batch_name']) && $condition['batch_name'] != '') {
                $filter[] = "t.name LIKE '%{$condition['batch_name']}%'";
            }
            if (isset($condition['batch_status']) &&
                 $condition['batch_status'] != '') {
                $filter[] = "t.status = '{$condition['batch_status']}'";
            }
            if (isset($condition['begin_time']) && $condition['begin_time'] != '') {
                $condition['start_time'] = $condition['begin_time'] . '000000';
                $filter[] = "t.start_time >= '{$condition['begin_time']}'";
            }
            if (isset($condition['end_time']) && $condition['end_time'] != '') {
                $condition['end_time'] = $condition['end_time'] . '235959';
                $filter[] = "t.end_time <= '{$condition['end_time']}'";
            }
            if (isset($condition['is_new'])) {
                $filter[] = "t.is_new = '{$condition['is_new']}'";
            }
        }
        if (! empty($filter)) {
            $condition = implode(' AND ', $filter);
            $where .= ' AND ' . $condition;
        }
        $filter[] = "t.node_id in({$this->nodeIn()})";
        $count = M()->table("tmarketing_info t")->where($filter)->count();
        if ($count <= 0)
            $this->error('无订单数据可下载');
        
        $sql = "SELECT t.name,t.add_time,t.start_time,t.end_time, CASE t.status WHEN '1' THEN '正常' ELSE '停用' END STATUS, t.click_count,
				IFNULL((SELECT SUM(o.buy_num) FROM ttg_order_info o WHERE o.batch_no=t.id AND o.pay_status='2' AND o.order_status='0'),0) AS sell_num 
				FROM tmarketing_info t 
				{$where} AND node_id in({$this->nodeIn()})";
        $cols_arr = array(
            'name' => '活动名称', 
            'add_time' => '添加时间', 
            'start_time' => '活动开始时间', 
            'end_time' => '活动结束时间', 
            'status' => '状态', 
            'click_count' => '访问量', 
            'sell_num' => '销量');
        if (querydata_download($sql, $cols_arr, M()) == false) {
            $this->error('下载失败');
        }
    }
    
    // 活动类型图表 不分渠道
    public function Chart() {
        $_get = I('get.');
        // 查询
        $_get['begin_date'] = $begin_date = I('begin_date', 
            dateformat("-30 days", 'Ymd'));
        $_get['end_date'] = $end_date = I('end_date', 
            dateformat("0 days", 'Ymd'));
        $batch_type = I('batch_type', null, 'intval');
        if ($batch_type == '2627') {
            $batch_type = array(
                'in', 
                '26,27');
        }
        if (! $batch_type)
            $this->error("参数错误");
        $map = array(
            'batch_type' => $batch_type, 
            'node_id' => $this->node_id);
        
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
        
        $jsChartDataClick = array(); // PV访问量
        $jsChartDataOrder = array(); // 订单数
        $jsChartDataAmt = array(); // 销售额
        if ($batch_type == '29') {
            // 小店的m_id
            $m_id = M('tmarketing_info')->where(
                array(
                    'node_id' => $this->node_id, 
                    'batch_type' => '29'))->getField('id');
            $map['batch_id'] = $m_id;
            // 小店访问量
            $pv_arr = M('Tdaystat')->where($map)->field(
                "batch_type,batch_id,day,sum(click_count) click_count")->group(
                "day")->select();
            // 小店-计算出JS值
            foreach ($pv_arr as $v) {
                $jsChartDataClick[] = array(
                    strtotime($v['day'] . " UTC") * 1000, 
                    $v['click_count'] * 1);
            }
            // 小店订单数
            $order_map = array(
                'order_type' => '2', 
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
            $order_arr = M("ttg_order_info")->field(
                "count(order_id) as order_count,substr(add_time,'1',8) as day,sum(order_amt) as order_amt")->where(
                $order_map)->group("substr(add_time,1,8)")->select();
            
            // 小店-计算出JS值
            foreach ($order_arr as $v) {
                $jsChartDataOrder[] = array(
                    strtotime($v['day'] . " UTC") * 1000, 
                    $v['order_count'] * 1);
                $jsChartDataAmt[] = array(
                    strtotime($v['day'] . " UTC") * 1000, 
                    $v['order_amt'] * 1);
            }
        } else { // 单品销售
                 // date_default_timezone_set('UTC');
            $map = array(
                'batch_type' => $batch_type, 
                'node_id' => $this->node_id);
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
            $pv_arr2 = M('Tdaystat')->where($map)->field(
                "day,sum(click_count) click_count")->group("day")->select();
            // 单品销售-计算出JS值
            foreach ($pv_arr2 as $v) {
                $jsChartDataClick[] = array(
                    strtotime($v['day'] . " UTC") * 1000, 
                    $v['click_count'] * 1);
            }
            // 单品订单数
            $order_map2 = array(
                'order_type' => '0', 
                'order_status' => '0', 
                'pay_status' => '2', 
                'node_id' => $this->node_id);
            // 闪购查询日期
            $order_map2['add_time'] = array();
            if ($begin_date != '') {
                $order_map2['add_time'][] = array(
                    'EGT', 
                    $begin_date . '000000');
            }
            if ($end_date != '') {
                $order_map2['add_time'][] = array(
                    'ELT', 
                    $end_date . '235959');
            }
            $order_map2['batch_no'] = array(
                'exp', 
                "in (select id from tmarketing_info where batch_type in (26,27) and node_id ='{$this->node_id}')");
            $order_arr2 = M("ttg_order_info")->field(
                "count(order_id) as order_count,substr(add_time,'1',8) as day,sum(order_amt) as order_amt")->where(
                $order_map2)->group("substr(add_time,1,8)")->select();
            // 闪购-计算出JS值
            foreach ($order_arr2 as $v) {
                $jsChartDataOrder[] = array(
                    strtotime($v['day'] . " UTC") * 1000, 
                    $v['order_count'] * 1);
                $jsChartDataAmt[] = array(
                    strtotime($v['day'] . " UTC") * 1000, 
                    $v['order_amt'] * 1);
            }
            // 还原batch_type的值为2627，方便传回页面判断
            $batch_type = '2627';
        }
        
        $batchTypeArr = array(
            '2627' => '单品销售', 
            '29' => '旺财小店');
        $this->assign('jsChartDataClick', json_encode($jsChartDataClick));
        $this->assign('jsChartDataOrder', json_encode($jsChartDataOrder));
        $this->assign('jsChartDataAmt', json_encode($jsChartDataAmt));
        $this->assign('begin_date', $begin_date);
        $this->assign('end_date', $end_date);
        $this->assign('batch_type', $batch_type);
        $this->assign('_get', $_get);
        $this->assign('batchTypeArr', $batchTypeArr);
        $this->display();
    }
    
    // 活动渠道分析图表
    public function channelChart() {
        $channel_type_arr = C('CHANNEL_TYPE');
        $statusArr = array(
            '1' => '正常', 
            '2' => '停用');
        
        // 类型
        $batch_type = I('batch_type');
        $is_new = I('is_new');
        // 活动号
        $batch_id = I('batch_id');
        // 小店 取小店的m_id
        if ($batch_type == '29') {
            $m_info = M('tmarketing_info')->where(
                array(
                    'node_id' => $this->node_id, 
                    'batch_type' => $batch_type))->find();
            $batch_id = $m_info['id'];
            $batch_name = $m_info['name'];
        } else
            // 获取活动名
            $batch_name = M('tmarketing_info')->where(
                "id='" . $batch_id . "' and batch_type='" . $batch_type . "'")->getField(
                'name');
        if (! $batch_type)
            $this->error('未查询到记录！');
        $model = M()->table('tbatch_channel a');
        $where = " a.node_id in({$this->nodeIn()}) and b.sns_type != '53'";
        $where .= " and a.batch_type = '" . $batch_type . "'";
        $where .= " and a.batch_id = '" . $batch_id . "'";
        $list = M()->Table('tbatch_channel a')
            ->field(
            'a.id,a.batch_type,a.channel_id,a.id,a.channel_id,a.click_count,a.send_count,a.add_time,a.status,b.name,b.type,b.sns_type')
            ->join("tchannel b ON a.channel_id=b.id")
            ->where($where)
            ->select();
        foreach ($list as &$vo) {
            $count = M()->table("ttg_order_info t")->field('sum(t.buy_num)')->join(
                'tbatch_channel b ON b.id=t.batch_channel_id')->where(
                array(
                    't.batch_no' => $batch_id, 
                    't.pay_status' => '2', 
                    't.order_status' => '0', 
                    'b.id' => $vo['id']))->sum('buy_num');
            if ($count)
                $vo['order_num'] = $count;
            else
                $vo['order_num'] = 0;
        }
        $this->assign('batch_id', $batch_id);
        $this->assign('is_new', $is_new);
        $this->assign('batch_type', $batch_type);
        $this->assign('batch_name', $batch_name);
        $this->assign('channel_type_arr', $channel_type_arr);
        $this->assign('query_list', $list);
        $this->assign('statusArr', $statusArr);
        
        $this->display(); // 输出模板
    }
    
    // 点击数报表
    public function clickChart() {
        $batch_type = I('batch_type');
        $batch_id = I('batch_id');
        $channel_id = I('channel_id');
        if (empty($batch_type) || empty($batch_id))
            $this->error("活动类型或活动编号不能为空！");
        
        $_get = I('get.');
        // 查询
        $_get['begin_date'] = $begin_date = I('begin_date', 
            dateformat("-30 days", 'Ymd'));
        $_get['end_date'] = $end_date = I('end_date', 
            dateformat("0 days", 'Ymd'));
        // 获取活动名
        $batch_name = M('tmarketing_info')->where(
            "id='" . $batch_id . "' and batch_type='" . $batch_type . "'")->getField(
            'name');
        $model = M('Tdaystat');
        $map = array(
            'batch_type' => $batch_type, 
            'batch_id' => $batch_id);
        if ($channel_id != '') {
            $map['channel_id'] = $channel_id;
        }
        
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
        $query_arr = $model->where($map)->field(
            "batch_type,batch_id,day,sum(click_count) click_count,sum(send_count) send_count")->group(
            "day")->select();
        
        foreach ($query_arr as &$vo) {
            $count = M()->table("ttg_order_info o")->where(
                array(
                    'o.batch_no' => $vo['batch_id'], 
                    'o.add_time' => array(
                        'like', 
                        "%{$vo['day']}%"), 
                    'o.pay_status' => '2', 
                    'o.order_status' => '0', 
                    'o.batch_channel_id' => array(
                        "exp", 
                        "in (SELECT bc.id FROM tbatch_channel bc WHERE bc.channel_id={$channel_id} AND bc.batch_id={$vo['batch_id']} AND bc.batch_type={$vo['batch_type']})")))->sum(
                'o.buy_num');
            if ($count)
                $vo['send_count'] = $count;
            else
                $vo['send_count'] = 0;
        }
        // 计算出JS值
        $jsChartDataClick = array();
        $jsChartDataSend = array();
        foreach ($query_arr as $v) {
            $jsChartDataClick[] = array(
                $v['day'], 
                $v['click_count'] * 1);
            $jsChartDataSend[] = array(
                $v['day'], 
                $v['send_count'] * 1);
        }
        $this->assign('_get', $_get);
        $this->assign('jsChartDataClick', json_encode($jsChartDataClick));
        $this->assign('jsChartDataSend', json_encode($jsChartDataSend));
        $this->assign('batch_type', $batch_type);
        $this->assign('query_list', $query_arr);
        $this->assign('batch_name', $batch_name);
        
        $this->display();
    }

    public function newpro() {
        $batch_type = I('batch_type');
        if ($batch_type == "") {
            $batch_type = 27;
        }
        $this->assign('batch_type', $batch_type);
        $this->display();
    }

    public function newadd() {
        $batch_type = I('batch_type');
        if ($batch_type == "") {
            $batch_type = 27;
        }
        $this->assign('batch_type', $batch_type);
        //短信内容
        $startUp = M('tnode_info')->where(array('node_id'=>$this->node_id))->getField('custom_sms_flag');
        $this->assign('startUp', $startUp);
        //获取当前机构的资费标准
        $sendPrice = D('ActivityPayConfig')->getSendFee($this->node_id);
        $this->assign('sendPrice', $sendPrice);
        $this->display();
    }

    public function newedit() {
        $id = I('id', null, 'mysql_real_escape_string'); // echo "$id";die;
        if (empty($id))
            $this->error('错误参数');
        $map = array(
            "g.id" => $id, 
            "g.node_id" => $this->node_id);
        $row = M()->table("tmarketing_info g")->field(
            'g.*,i.info_title,i.use_rule,i.verify_end_date,i.verify_end_type,i.sms_text,u.id as goods_id ,i.storage_num,u.goods_name, u.is_order,u.remain_num,u.storage_type,u.goods_type,u.goods_id as goods_no')
            ->join("tbatch_info i ON g.id=i.m_id AND g.node_id=i.node_id")
            ->join("tgoods_info u on u.goods_id = i.goods_id")
            ->where($map)
            ->find();
        if (! $row) {
            $this->error('码上买活动未找到');
        }
        // 商户名称
        $nodeName = M('tnode_info')->field(' node_name,custom_sms_flag ')->where("node_id='{$this->node_id}'")->getField('');
        $this->assign('row', $row);
        $this->assign('node_name', $nodeName['node_name']);
        $this->assign('startUp', $nodeName['custom_sms_flag']);        //自定义短信

        // 创建sku信息
        $skuObj = D('Sku', 'Service');
        unset($map);
        $skuInfoList = $skuObj->getSkuEcshopList($id, $this->nodeId);
        // 判断是否有sku数据
        $goodsSkuTypeList = '';
        $goodsSkuDetailList = '';
        $skuDetail = '';
        $isCycle = $row['is_order']; // 是否订购
        
        if (NULL === $skuInfoList) {
            $isSku = false;
        } else {
            $isSku = true;
            // 分离商品表中的规格和规格值ID
            $goods_sku_list = $skuObj->getReloadSku($skuInfoList);
            // 取得规格值表信息
            if (is_array($goods_sku_list['list']))
                $goodsSkuDetailList = $skuObj->getSkuDetailList(
                    $goods_sku_list['list']);
                // 取得规格表信息
            if (is_array($goodsSkuDetailList))
                $goodsSkuTypeList = $skuObj->getSkuTypeList($goodsSkuDetailList);
                
                // 价格列表
            $skuDetail = $skuObj->makeSkuList($skuInfoList);
        }
        
        $newmap = array(
            "m_id" => $id, 
            "node_id" => $this->node_id);
        // 获取送礼信息
        $sendGift = D('MarketInfo')->getSendGiftTage($row);
        $this->assign('sendGift', $sendGift);
        // 查询模板数据
        $pageInfo = M('tecshop_goods_new')->where($newmap)->find();
        $this->assign('isCycle', $isCycle); // 是否订购
        $this->assign("skuDetail", $skuDetail);
        $this->assign("skutype", 
            $skuObj->makeSkuType($goodsSkuTypeList, $goodsSkuDetailList));
        $this->assign('isSku', $isSku);
        $this->assign('pageInfo', $pageInfo);
        $this->display();
    }

    public function newpageSubmit() {
        $datastr = I("datastr", "", "trim");
        if (ini_get("magic_quotes_gpc") == "1") {
            $datastr = stripslashes($datastr);
        }
        
        $pageInfo = json_decode($datastr, true);
        if (empty($pageInfo)) {
            $returnstr = '{"status":"1","msg":"JSON数据格式错误！"}';
            echo $returnstr;
            exit();
        }
        
        $m_id = I('m_id');
        $new_id = I('new_id');
        if ($m_id == "" || $datastr == "") {
            $returnstr = '{"status":"1","msg":"参数数据错误！"}';
            echo $returnstr;
            exit();
        }
        
        if ($new_id != "") {
            
            $update_data = array(
                "page_content" => $datastr);
            $Where['id'] = $new_id;
            $res = M('tecshop_goods_new')->where($Where)->save($update_data);
            
            if ($res !== false) {
                
                $returnstr = '{"status":"0","msg":"更新成功！"}';
                echo $returnstr;
                exit();
            } else {
                $returnstr = '{"status":"1","msg":"更新失败！"}';
                echo $returnstr;
                exit();
            }
        } else {
            
            $insert_data = array(
                "node_id" => $this->node_id, 
                "m_id" => $m_id, 
                "page_content" => $datastr, 
                "add_time" => date('YmdHis'));
            $res = M('tecshop_goods_new')->add($insert_data);
            if ($res) {
                $returnstr = '{"status":"0","msg":"保存成功！"}';
                echo $returnstr;
                exit();
            } else {
                $returnstr = '{"status":"1","msg":"保存失败，入库数据异常！"}';
                echo $returnstr;
                exit();
            }
        }
    }

    public function powerCheck() {
        $node_info = M('tnode_info')->where(
            array(
                'node_id' => $this->node_id))->find();
        if (! $node_info['receive_phone']) {
            $this->error("您的接受通知手机号为空，请至多宝电商配置处补齐。", 
                array(
                    '返回' => 'javascript:history.go(-1)', 
                    '去配置' => U('Ecshop/BusiOption/index')));
        }
        $account_info = M('tnode_account')->where(
            array(
                'node_id' => $this->node_id))->select();
        if (! $account_info) {
            $this->error("您的收款账户信息不完整，请至多宝电商配置处添加", 
                array(
                    '返回' => 'javascript:history.go(-1)', 
                    '去配置' => U('Ecshop/BusiOption/index')));
        }
    }

    /**
     * 门店分析
     *
     * @return [type] [description]
     */
    public function storeChart() {
        $_post = I('post.');
        // 查询
        $_post['begin_date'] = $begin_date = I('begin_date', 
            dateformat("-30 days", 'Ymd'));
        $_post['end_date'] = $end_date = I('end_date', 
            dateformat("0 days", 'Ymd'));
        // 类型
        $batch_type = I('batch_type');
        // 小店 取小店的m_id
        if ($batch_type == '29') {
            $m_info = M('tmarketing_info')->where(
                array(
                    'node_id' => $this->node_id, 
                    'batch_type' => $batch_type))->find();
            $batch_id = $m_info['id'];
            $batch_name = $m_info['name'];
        }
        $map = array(
            'toi.pay_status' => '2', 
            'toi.order_status' => '0', 
            'toi.node_id' => $this->node_id, 
            'toi.order_type' => '2');
        $jsChartDataOrder = array(); // 订单数
        $map['toi.add_time'] = array();
        if ($begin_date != '') {
            $map['toi.add_time'][] = array(
                'EGT', 
                $begin_date . '000000');
        }
        if ($end_date != '') {
            $map['toi.add_time'][] = array(
                'ELT', 
                $end_date . '235959');
        }
        $list = M()->Table('tfb_adb_ecshop_order taeo')
            ->field(
            "sum(toi.buy_num) as order_num,ifnull(ti.store_short_name,'爱蒂宝总店') as store_name,substr(toi.add_time,'1',8) as day,taeo.order_store_id")
            ->join("ttg_order_info toi ON taeo.order_id=toi.order_id")
            ->join("tstore_info ti on taeo.order_store_id=ti.store_id")
            ->where($map)
            ->group("ti.store_short_name")
            ->select();
        foreach ($list as &$v) {
            if (! $v['order_num']) {
                $v['order_num'] = 0;
            }
        }
        $this->assign('query_list', $list);
        $this->assign('batch_name', $batch_name);
        $this->assign('batch_type', $batch_type);
        $this->assign('begin_date', $begin_date);
        $this->assign('end_date', $end_date);
        $this->assign('_post', $_post);
        $this->display(); // 输出模板
    }

    /**
     * 点击报表
     */
    public function storeClickStat() {
        // 查询
        $begin_date = I('begin_date', 
            dateformat('Ymd'));
        $end_date = I('end_date', 
            dateformat('Ymd'));
        // 类型
        $batch_type = I('batch_type');
        $order_store_id = I("order_store_id");
        // 小店 取小店的m_id
        if ($batch_type == '29') {
            $m_info = M('tmarketing_info')->where(
                array(
                    'node_id' => $this->node_id, 
                    'batch_type' => $batch_type))->find();
            $batch_id = $m_info['id'];
            $batch_name = $m_info['name'];
        }
        $map = array(
            'toi.pay_status' => '2', 
            'toi.order_status' => '0', 
            'toi.node_id' => $this->node_id, 
            'toi.order_type' => '2', 
            'taeo.order_store_id' => $order_store_id);
        $jsChartDataOrder = array(); // 订单数
        $map['toi.add_time'] = array();
        if ($begin_date != '') {
            $map['toi.add_time'][]= array(
                'EGT', 
                $begin_date . '000000');
        }
        if ($end_date != '') {
            $map['toi.add_time'][] = array(
                'ELT', 
                $end_date . '235959');
        }
        $list = M()->Table('tfb_adb_ecshop_order taeo')
            ->field(
            "sum(toi.buy_num) as order_count,substr(toi.add_time,'1',8) as day")
            ->join("ttg_order_info toi ON taeo.order_id=toi.order_id")
            ->where($map)
            ->group("substr(toi.add_time,1,8)")
            ->select();
        foreach ($list as &$v) {
            $jsChartDataOrder[] = array(
                strtotime($v['day'] . " UTC") * 1000, 
                $v['order_count'] * 1);
        }
        $this->assign('query_list', $list);
        $this->assign('jsChartDataOrder', json_encode($jsChartDataOrder));
        $this->assign('order_store_id', $order_store_id);
        $this->assign('batch_name', $batch_name);
        $this->assign('batch_type', $batch_type);
        $this->assign('begin_date', $begin_date);
        $this->assign('end_date', $end_date);
        $this->display(); // 输出模板
    }
}