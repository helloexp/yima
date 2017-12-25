<?php

/**
 * 团购活动
 *
 * @author bao
 */
class OrderListAction extends IntegralAuthAction {

    public $_authAccessMap = '*';

    public $BATCH_TYPE = CommonConst::BATCH_TYPE_GOODS;

    public function _initialize() {
        parent::_initialize();
    }

    public function index() {
        // 更新订单通知表 把未读记录更新成已读
        $model = M('tintegral_order_info');
        $map = array(
            'o.node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"));
        
        $data = $_REQUEST;
        
        if ($data['order_id'] != '') {
            $map['o.order_id'] = $data['order_id'];
        }
        if ($data['integralShopName'] != '') {
            $map['o.goods_name'] = array(
                'like', 
                '%' . $data['integralShopName'] . '%');
            $this->assign('integralShopName', $data['integralShopName']);
        }
        if ($data['memberName'] != '') {
            $map['sc.name'] = array(
                'like', 
                '%' . $data['memberName'] . '%');
            $this->assign('memberName', $data['memberName']);
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
        $err_count = M()->table('tintegral_order_info o')
            ->join('tintegral_order_trace ot on ot.order_id=o.order_id')
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
                $errOrderList = M()->table('tintegral_order_trace o')
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
            'o.*,m.group_goods_name,m.group_price,sc.point');
        $mapcount = M()->table('tintegral_order_info o')
            ->field($field)
            ->join("tbatch_channel b ON b.id=o.batch_channel_id")
            ->join("tchannel c ON c.id=b.channel_id")
            ->join("tmarketing_info m ON o.batch_no=m.id")
            ->join(
            "tmember_info sc ON sc.id=o.member_id and sc.node_id=o.node_id")
            ->where($map)
            ->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        
        foreach ($data as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        
        $show = $Page->show(); // 分页显示输出
        $field2 = array(
            'o.*,m.group_goods_name,m.batch_type,m.group_price,c.name as channel_name,sc.name as petname,sc.point');
        $orderList = M()->table('tintegral_order_info o')
            ->field($field2)
            ->join("tmarketing_info m ON o.batch_no=m.id")
            ->join("tbatch_channel b ON b.id=o.batch_channel_id")
            ->join("tchannel c ON c.id=b.channel_id")
            ->join(
            "tmember_info sc ON sc.id=o.member_id and sc.node_id=o.node_id")
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
        $cashStatus = array(
            '0' => '未提现', 
            '1' => '已申请', 
            '2' => '已完成', 
            '9' => '未提现');
        $deliveryStatus = array(
            '1' => '待配送', 
            '3' => '已配送', 
            '4' => '凭证自提');
        $receiverType = array(
            '0' => ' 凭证自提订单', 
            '1' => '物流订单');
        // $marketType =
        // array('26'=>'闪购','27'=>'码上买','29'=>'旺财小店','31'=>'小店商品','55'=>'吴刚砍树');
        $gift_arr = array(
            '0' => '自消费', 
            '1' => '送礼');
        
        $texpressModel = D('TexpressInfo');
        $expressResult = $texpressModel->getLastUsedExpress();
        $this->assign('usedExpress', $expressResult['rescent']);
        $this->assign('expressStr', $expressResult['expressStr']);
        $this->assign('err_flag', $err_flag);
        $this->assign('err_count', $err_count);
        $this->assign('payStatus', $payStatus);
        // $this->assign('marketType',$marketType);
        $this->assign('orderStatus', $orderStatus);
        $this->assign('cashStatus', $cashStatus);
        $this->assign('deliveryStatus', $deliveryStatus);
        $this->assign('receiverType', $receiverType);
        $this->assign('orderList', $orderList);
        $this->assign('salerInfo', $salerInfo);
        $this->assign('post', $data);
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('gift_arr', $gift_arr);
        $this->display("OrderList/index");
    }
    // 查看订单信息
    public function orderList() {
        $batchNo = I('batch_no', null, 'mysql_real_escape_string');
        if (empty($batchNo))
            $this->error('参数错误');
        $goodsName = I('goods_name', null, 'mysql_real_escape_string,trim');
        if (isset($goodsName) && $goodsName != '') {
            $where['g.group_goods_name'] = array(
                'like', 
                "%{$goodsName}%");
        }
        $orderId = I('order_id', null, 'mysql_real_escape_string,trim');
        if (! empty($orderId)) {
            $where['o.order_id'] = $orderId;
        }
        $status = I('status', null, 'mysql_real_escape_string,trim');
        if (! empty($status)) {
            $where['o.pay_status'] = $status;
        }
        $where['o.group_batch_no'] = $batchNo;
        $where['o.node_id'] = $this->nodeId;
        // $where['o.order_type'] = '2';
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = M()->table('ttg_order_info o')
            ->where($where)
            ->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
                                         // 确保分页查询条件
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        $show = $Page->show(); // 分页显示输出
        $field = array(
            'o.*,g.group_goods_name,g.group_price');
        $orderList = M()->table('ttg_order_info o')
            ->field($field)
            ->join("tmarketing_info g ON o.batch_no=g.id")
            ->where($where)
            ->order('o.add_time DESC')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        $orderStatus = array(
            '1' => '未支付', 
            '2' => '已支付');
        $deliveryStatus = array(
            '1' => '待配送', 
            '2' => '配送中', 
            '3' => '已配送');
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('orderList', $orderList);
        $this->assign('orderStatus', $orderStatus);
        $this->assign('deliveryStatus', $deliveryStatus);
        $this->assign('post', $_REQUEST);
        $this->assign('batchNo', $batchNo);
        $this->assign('empty', '<tr><td colspan="10">无数据</td></span>');
        $this->display();
    }
    // 数据导出
    public function export() {
        // 查询条件组合
        $where = "WHERE o.node_id in (" . $this->nodeIn() . ") ";
        if (! empty($_POST)) {
            $filter = array();
            $condition = array_map('trim', $_POST);
            /*
             * if(isset($condition['goods_name']) && $condition['goods_name'] !=
             * ''){ $filter[] = "m.group_goods_name LIKE
             * '%{$condition['goods_name']}%'"; }
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
        $count = M()->table("tintegral_order_info o")->join(
            "tbatch_channel b ON b.id=o.batch_channel_id")
            ->join("tchannel c ON c.id=b.channel_id")
            ->where($filter)
            ->count();
        if ($count <= 0)
            $this->error('无订单数据可下载');
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
        $col_data = array();
        ;
        $col_val = htmlspecialchars_decode(I('col_list'));
        $col_arr = explode("&", $col_val);
        foreach ($col_arr as $k => $v) {
            $col_arr2 = explode("=", $v);
            $col_data[$col_arr2[0]] = $col_arr2[1];
        }
        // 初始化结果集
        $col_all = array(
            '1' => array(
                'col_name' => 'order_id', 
                'col_str' => '订单号', 
                'col_sel' => "o.order_id"), 
            '2' => array(
                'col_name' => 'add_time', 
                'col_str' => '下单时间', 
                'col_sel' => "o.add_time"), 
            '3' => array(
                'col_name' => 'order_phone', 
                'col_str' => '下单手机号', 
                'col_sel' => "o.order_phone"), 
            '4' => array(
                'col_name' => 'order_amt', 
                'col_str' => '订单金额', 
                'col_sel' => "o.order_amt"), 
            '5' => array(
                'col_name' => 'batch_name', 
                'col_str' => '商品名称', 
                'col_sel' => "m.name as batch_name"), 
            '6' => array(
                'col_name' => 'receiver_type', 
                'col_str' => '订单类型', 
                'col_sel' => "CASE o.receiver_type WHEN '0' THEN '凭证自提订单' WHEN '1' THEN '物流订单' ELSE '其他' END receiver_type"), 
            // '7'=>array('col_name'=>'is_gift','col_str'=>'订单用途','col_sel'=>"CASE
            // o.is_gift WHEN '0' THEN '自消费' WHEN '1' THEN '送礼' ELSE '其他' END
            // is_gift"),
            '8' => array(
                'col_name' => 'delivery_company', 
                'col_str' => '快递公司', 
                'col_sel' => "o.delivery_company"), 
            '9' => array(
                'col_name' => 'delivery_number', 
                'col_str' => '物流单号', 
                'col_sel' => "o.delivery_number"), 
            '10' => array(
                'col_name' => 'delivery_date', 
                'col_str' => '配送时间', 
                'col_sel' => "o.delivery_date"), 
            '11' => array(
                'col_name' => 'receiver_name', 
                'col_str' => '收货人姓名', 
                'col_sel' => "o.receiver_name"), 
            '12' => array(
                'col_name' => 'receiver_phone', 
                'col_str' => '收货人手机号', 
                'col_sel' => "o.receiver_phone"), 
            '13' => array(
                'col_name' => 'receiver_addr', 
                'col_str' => '收货地址', 
                'col_sel' => "o.receiver_addr as addr"), 
            // '14'=>array('col_name'=>'bonus_amt','col_str'=>'优惠金额（红包）','col_sel'=>"o.bonus_use_amt
            // AS bonus_amt"),
            // '15'=>array('col_name'=>'freight','col_str'=>'运费','col_sel'=>"IFNULL(o.freight,
            // '0.00') freight"),
            // '16'=>array('col_name'=>'pay_status','col_str'=>'支付状态','col_sel'=>"CASE
            // o.pay_status WHEN '1' THEN '未支付' WHEN '2' THEN '已支付' ELSE '其他'
            // END pay_status"),
            // '17'=>array('col_name'=>'fee_cost','col_str'=>'支付扣费','col_sel'=>"CASE
            // WHEN o.pay_status='2' THEN
            // ROUND(o.order_amt*IFNULL(na.fee_rate,0.02),2) ELSE 0.00 END
            // fee_cost"),
            '18' => array(
                'col_name' => 'real_cost', 
                'col_str' => '实收金额', 
                'col_sel' => "CASE WHEN o.pay_status='2' THEN o.order_amt-ROUND(o.order_amt*IFNULL(na.fee_rate,0.02),2) ELSE 0.00 END real_cost"), 
            // '19'=>array('col_name'=>'pay_channel','col_str'=>'支付方式','col_sel'=>"CASE
            // o.pay_channel WHEN '0' THEN '银行卡' WHEN '1' THEN '支付宝' WHEN '2'
            // THEN '银行卡' WHEN '3' THEN '微信支付' ELSE '其他' END pay_channel"),
            // '20'=>array('col_name'=>'order_status','col_str'=>'订单状态','col_sel'=>"CASE
            // o.order_status WHEN '0' THEN '正常' WHEN '1' THEN '取消' ELSE '其他'
            // END order_status"),
            '20' => array(
                'col_name' => 'integral_sku_desc', 
                'col_str' => '商品规格', 
                'col_sel' => "x.integral_sku_desc"), 
            // '21'=>array('col_name'=>'send_seq','col_str'=>'发码流水号','col_sel'=>"tt.send_seq
            // AS send_seq"),
            '22' => array(
                'col_name' => 'goods_name', 
                'col_str' => '商品', 
                'col_sel' => "CASE o.order_type WHEN '0' THEN CONCAT(m.name,'￥','*',o.buy_num) WHEN '2' THEN (SELECT GROUP_CONCAT(CONCAT(e.b_name,'￥','*',e.goods_num) SEPARATOR '+') FROM tintegral_order_info_ex e WHERE e.order_id=o.order_id) END goods_name"), 
            // '23'=>array('col_name'=>'saler_name','col_str'=>'销售员','col_sel'=>"saler.name
            // as saler_name"),
            // '24'=>array('col_name'=>'super_saler_name','col_str'=>'上级经销商','col_sel'=>"super.name
            // as super_saler_name"),
            '25' => array(
                'col_name' => 'buy_num', 
                'col_str' => '商品总数', 
                'col_sel' => "o.buy_num"));
        // '26'=>array('col_name'=>'channel_name','col_str'=>'渠道来源','col_sel'=>"c.name
        // as channel_name"),
        // '27'=>array('col_name'=>'openid','col_str'=>'是否为微信粉丝','col_sel'=>"CASE
        // wx.openid WHEN '' THEN '否' ELSE '是' END openid"),
        // '28'=>array('col_name'=>'remarkname','col_str'=>'粉丝标签','col_sel'=>"CASE
        // wx.openid WHEN '' THEN '' ELSE wx.remarkname END remarkname"),
        
        // 获取结果字段集
        $select_def = "o.order_id,o.add_time,o.order_phone,o.order_amt, concat(ifnull(cc.province,''), ifnull(cc.city,''), ifnull(cc.town,''), o.receiver_addr) as receiver_addr"; // 默认必选字段
        $select_add = '';
        $cols_arr = array();
        foreach ($col_data as $kk => $vv) {
            if ($vv == '1') {
                $select_add .= ',' . $col_all[$kk]['col_sel'];
                $cols_arr[$col_all[$kk]['col_name']] = $col_all[$kk]['col_str'];
            }
        }
        
        $select_v = $select_def . $select_add;
        $sql = "select " . $select_v . " FROM tintegral_order_info o
				LEFT JOIN tmarketing_info m ON o.batch_no=m.id
				LEFT JOIN tintegral_order_info_ex x ON x.order_id=o.order_id
				LEFT JOIN tbatch_channel b ON b.id=o.batch_channel_id
				LEFT JOIN tchannel c ON c.id=b.channel_id 
              LEFT JOIN tcity_code cc ON cc.path=o.receiver_citycode
				LEFT JOIN tnode_account na ON na.node_id=o.node_id AND na.account_type=o.pay_channel
              LEFT JOIN twx_user wx ON wx.node_id=o.node_id and wx.openid = o.openId
				{$where}";
        if (querydata_download($sql, $cols_arr, M()) == false) {
            $this->error('下载失败');
        }
    }
    
    // 根据订单号 获取凭证记录
    public function orderOption() {
        $orderId = I('order_id', null);
        if (! $orderId)
            $this->error('数据错误');
        $orderInfo = M()->table("tintegral_order_info o")->field(
            'o.*,m.group_goods_name')
            ->join('tmarketing_info m on m.id=o.batch_no')
            ->where(array(
            'o.order_id' => $orderId))
            ->find();
        // 获取旺财小店的子订单列表
        $orderInfoExList = M('tintegral_order_info_ex')->where(
            array(
                'order_id' => $orderId))->select();
        // 旺财小店获取凭证列表
        foreach ($orderInfoExList as &$v) {
            $v['barcodeInfo'] = M()->table("tintegral_order_trace o")->field('o.*,b.*')
                ->join('tbarcode_trace b on b.request_id=o.code_trace')
                ->where(
                array(
                    'o.order_id' => $orderId, 
                    'o.b_id' => $v['b_id'], 
                    'b.trans_type' => '0001'))
                ->select();
        }
        $this->assign('orderInfo', $orderInfo);
        $this->assign('orderInfoExList', $orderInfoExList);
        // $this->assign('barcodeInfo',$barcodeInfo);
        $this->display("OrderList/orderOption");
    }

    /*
     * public function status_change(){ $node_id = $this->nodeId;
     * $status=I('status'); //开启渠道插入渠道 if($status==1){ //更新状态启用 $data = array(
     * 'auto_print'=>1 ); $result =
     * M('tnode_info')->where("node_id='".$node_id."'")->save($data); //关闭渠道
     * }else{ //更新状态启用 $data = array( 'auto_print'=>0 ); $result =
     * M('tnode_info')->where("node_id='".$node_id."'")->save($data); }
     * if($result){ echo "1"; }else{ echo "2"; } exit; }
     */
    public function saleList() {
        $payChannelArray = array(
            '1' => '支付宝', 
            '2' => '联动优势', 
            '3' => '微信');
        // 计算费率
        $tnodeAccount = M('Tnode_account');
        $tnodeAccountResult = $tnodeAccount->where(
            "account_type='1' AND node_id='{$this->node_id}'")->select();
        if (is_array($tnodeAccountResult)) {
            $tnodeAccountResult[0]['fee_rate'] = $tnodeAccountResult[0]['fee_rate'] ? $tnodeAccountResult[0]['fee_rate'] : 0.02;
        }
        $fee_rate = $tnodeAccountResult[0]['fee_rate'];
        // 当天时间
        $todayYmd = date('Ymd', $_SERVER['REQUEST_TIME']);
        $todayY_m_d = date('Y-m-d', $_SERVER['REQUEST_TIME']);
        // 查询条件---时间
        $beginDate = I('start_time', null, 'mysql_real_escape_string,trim');
        $endDate = I('end_time', null, 'mysql_real_escape_string,trim');
        if (! empty($beginDate) && ! empty($endDate)) {
            $map['o.update_time'] = array(
                'between', 
                $beginDate . '000000,' . $endDate . '235959');
        } elseif (! empty($beginDate) && empty($endDate)) {
            $map['o.update_time'] = array(
                'egt', 
                $beginDate . '000000');
        } elseif (empty($beginDate) && ! empty($endDate)) {
            $map['o.update_time'] = array(
                'elt', 
                $endDate . '235959');
        }
        // 查询条件---支付通道
        $pay_channel = I('post.pay_channel');
        if (! empty($pay_channel) || $pay_channel == "0") {
            $map['o.pay_channel'] = I('post.pay_channel');
        }
        // 必须是已经支付的
        $map['o.pay_status'] = 2;
        // 必须是当前的登录机构
        $map['o.node_id'] = array(
            'exp', 
            "in (" . $this->nodeIn() . ")");
        import('ORG.Util.Page'); // 导入分页类
                                 // 查询的字段
        $field = array(
            "DATE_FORMAT(o.update_time,'%Y-%m-%d') AS trans_time,SUM(o.order_amt) AS order_amt,ifnull(SUM(o.freight),0.00) AS frieght,IFNULL(b.bonus_amount,0.00) AS bonus_amt,
			CASE WHEN o.pay_channel='1' THEN SUM(ROUND(o.order_amt*{$fee_rate},2)) WHEN o.pay_channel<>'1' THEN SUM(ROUND(o.order_amt*0.02,2)) END rate_amt, 
			CASE WHEN o.pay_channel='1' THEN SUM(o.order_amt-ROUND(o.order_amt*{$fee_rate},2)) WHEN o.pay_channel<>'1' THEN SUM(o.order_amt-ROUND(o.order_amt*0.02,2)) END act_amt,
			CASE WHEN o.pay_channel='1' THEN '支付宝' WHEN o.pay_channel='2' THEN '联动优势' WHEN o.pay_channel='3' THEN '微信' ELSE '其他' END pay_channel");
        
        // 查询的全部销售额日报表的总数
        $field1 = M()->table('ttg_order_info o')
            ->field($field)
            ->join(
            '(SELECT order_id,bonus_amount from tbonus_use_detail group by order_id HAVING order_id>0) b ON b.order_id=o.order_id')
            ->group('SUBSTR(o.update_time,1,8),o.pay_channel')
            ->where($map)
            ->select();
        $mapcount = count($field1);
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
                               // 开始查询，全部销售额日报表
        $field2 = M()->table('ttg_order_info o')
            ->field($field)
            ->join(
            '(SELECT order_id,bonus_amount from tbonus_use_detail group by order_id HAVING order_id>0) b ON b.order_id=o.order_id')
            ->group('SUBSTR(o.update_time,1,8),o.pay_channel')
            ->where($map)
            ->order('SUBSTR(o.update_time,1,8) desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        // 00004488 91机构
        // 查询当日数据
        $todayWhere['o.pay_status'] = '2';
        $todayWhere['o.node_id'] = $this->node_id;
        $todayWhere['o.update_time'] = array(
            'between', 
            $todayYmd . '000000,' . $todayYmd . '235959');
        $field3 = array();
        for ($i = 1; $i < 4; $i ++) {
            $todayWhere['o.pay_channel'] = $i;
            $field3[$i] = M()->table('ttg_order_info o')
                ->field($field)
                ->join(
                '(SELECT order_id,bonus_amount from tbonus_use_detail group by order_id HAVING order_id>0) b ON b.order_id=o.order_id')
                ->group('SUBSTR(o.update_time,1,8),o.pay_channel')
                ->where($todayWhere)
                ->select();
            
            if (! is_array($field3[$i][0])) {
                $field3[$i] = array(
                    'trans_time' => $todayY_m_d . "(今日)", 
                    'order_amt' => '0.00', 
                    'frieght' => '0.00', 
                    'bonus_amt' => '0.00', 
                    'rate_amt' => '0.00', 
                    'act_amt' => '0.00', 
                    'pay_channel' => $payChannelArray[$i]);
            } else {
                $field3[$i] = $field3[$i][0];
                $field3[$i]['trans_time'] = $todayY_m_d . "(今日)";
                $field3[$i]['pay_channel'] = $payChannelArray[$i];
            }
        }
        $this->assign('isPlayToday', $Page->firstRow);
        $this->assign('todayList', $field3);
        $this->assign('saleList', $field2);
        $this->assign('payChannelArray', $payChannelArray);
        $this->assign('pay_channel', I('post.pay_channel'));
        $this->assign('page', $show);
        $this->display();
    }

    public function saleExport() {
        // 查询条件组合
        $where = "o.node_id in (" . $this->nodeIn() . ") AND o.pay_status='2' ";
        
        if (! empty($_POST)) {
            $filter = array();
            $condition = array_map('trim', $_POST);
            
            if (isset($condition['pay_channel']) &&
                 $condition['pay_channel'] != '') {
                $filter[] = "o.pay_channel = '{$condition['pay_channel']}'";
            }
            if (isset($condition['start_time']) && $condition['start_time'] != '') {
                $filter[] = "o.update_time >='{$condition['start_time']}000000'";
            }
            if (isset($condition['end_time']) && $condition['end_time'] != '') {
                $filter[] = "o.update_time <= '{$condition['end_time']}235959'";
            }
        }
        if (! empty($filter)) {
            $condition = implode(' AND ', $filter);
            $where .= ' AND ' . $condition;
        }
        
        $filter[] = "o.node_id in({$this->nodeIn()}) AND o.pay_status='2'";
        $count = M()->table("ttg_order_info o")->join(
            "(SELECT order_id,bonus_amount from tbonus_use_detail group by order_id HAVING order_id>0) b ON b.order_id=o.order_id")
            ->where($filter)
            ->count();
        if ($count <= 0)
            $this->error('无订单数据可下载');
        
        $tnodeAccount = M('Tnode_account');
        $tnodeAccountResult = $tnodeAccount->where(
            "account_type='1' AND node_id='{$this->node_id}'")->select();
        if (is_array($tnodeAccountResult)) {
            $tnodeAccountResult[0]['fee_rate'] = $tnodeAccountResult[0]['fee_rate'] ? $tnodeAccountResult[0]['fee_rate'] : 0.02;
        }
        $fee_rate = $tnodeAccountResult[0]['fee_rate'];
        $sql = "select DATE_FORMAT(o.update_time,'%Y-%m-%d') AS trans_time,SUM(o.order_amt) AS order_amt,ifnull(SUM(o.freight),0.00) AS frieght,IFNULL(b.bonus_amount,0.00) AS bonus_amt,
			CASE WHEN o.pay_channel='1' THEN SUM(ROUND(o.order_amt*{$fee_rate},2)) WHEN o.pay_channel<>'1' THEN SUM(ROUND(o.order_amt*0.02,2)) END rate_amt, 
			CASE WHEN o.pay_channel='1' THEN SUM(o.order_amt-ROUND(o.order_amt*{$fee_rate},2)) WHEN o.pay_channel<>'1' THEN SUM(o.order_amt-ROUND(o.order_amt*0.02,2)) END act_amt,
			CASE WHEN o.pay_channel='1' THEN '支付宝' WHEN o.pay_channel='2' THEN '联动优势' WHEN o.pay_channel='3' THEN '微信' ELSE '其他' END pay_channel
			FROM ttg_order_info o
			LEFT JOIN (SELECT order_id,bonus_amount from tbonus_use_detail group by order_id HAVING order_id>0) b ON b.order_id=o.order_id
			WHERE {$where}
			GROUP BY SUBSTR(o.update_time,1,8),o.pay_channel
			ORDER BY SUBSTR(o.update_time,1,8) desc";
        $cols_arr = array(
            'trans_time' => '日期', 
            'order_amt' => '订单金额', 
            'frieght' => '运费', 
            'bonus_amt' => '优惠金额（红包）', 
            'rate_amt' => '支付扣费', 
            'act_amt' => '实收金额', 
            'pay_channel' => '支付通道');
        if (querydata_download($sql, $cols_arr, M()) == false) {
            $this->error('下载失败');
        }
    }
    
    // 订单物流更新
    public function updateDelivery() {
        $orderId = I('order_id', null);
        if (is_null($orderId)) {
            $this->error('缺少订单号');
        }
        $result = M('tintegral_order_info')->where(
            array(
                'order_id' => $orderId))->find();
        if (! $result) {
            $this->error('未找到订单信息');
        }
        
        $status = I('delivery_status', null);
        if (is_null($status)) {
            $this->error('缺少配送状态');
        }
        // 获取物流公司、单号、时间
        $number = I('delivery_number', null);
        if (is_null($number)) {
            $this->error('缺少物流单号');
        }
        $company = I('delivery_company', null);
        if (is_null($company)) {
            $this->error('缺少物流公司');
        } else {
            $expressInfoModel = M('TexpressInfo');
            $expressInfo = $expressInfoModel->where(
                array(
                    'express_name' => $company))->find();
            if (empty($expressInfo)) {
                $this->error(' 请填写正确的物流公司名称');
                exit();
            }
        }
        $d_date = date('YmdHis');
        $data = array(
            'delivery_status' => $status, 
            'delivery_number' => $number, 
            'delivery_company' => $company, 
            'delivery_date' => $d_date);
        $result = M('tintegral_order_info')->where(
            array(
                'order_id' => $orderId))->save($data);
        if ($result !== false) {
            $recentExpress = cookie('recentExpress');
            if ($recentExpress == '') {
                $recentExpress = array(
                    $company);
            } elseif (in_array($company, $recentExpress)) {
                unset($recentExpress[array_search($company, $recentExpress)]);
                array_unshift($recentExpress, $company);
            } elseif (! (in_array($company, $recentExpress))) {
                array_unshift($recentExpress, $company);
            }
            cookie('recentExpress', $recentExpress, 365 * 24 * 3600);
            $orderExpressInfoModel = M('TorderExpressInfo');
            $orderExpressInfoModel->where(
                array(
                    'order_id' => $orderId))->save(
                array(
                    'check_time' => '20140102030405'));
            $this->success('更新成功');
        } else {
            $this->error('更新失败');
        }
    }
    
    // 货到付款订单确认/取消 status 1 取消 2确认到货
    public function deliOrderConfirm() {
        $orderId = I('order_id', null);
        $status = I('status', null);
        if (is_null($orderId)) {
            $this->error('处理失败：缺少订单号');
        }
        if (! $status) {
            $this->error('处理失败：订单确认状态不能为空');
        }
        if (! in_array($status, 
            array(
                '1', 
                '2'))) {
            $this->error('处理失败：订单确认状态错误');
        }
        $result = M('tintegral_order_info')->where(
            array(
                'order_id' => $orderId))->find();
        if (! $result) {
            $this->error('处理失败：未找到订单信息');
        }
        if ($result['pay_channel'] != '4') {
            $this->error('处理失败：订单支付方式非货到付款');
        }
        if ($result['pay_status'] != '1') {
            $this->error('处理失败：订单非未支付状态');
        }
        
        M()->startTrans();
        if ($status == '1') {
            // 取消订单
            // 更新订单状态
            $order_arr = array(
                'order_status' => '2', 
                'update_time' => date('YmdHis'));
            $ret = M('tintegral_order_info')->where(
                array(
                    'order_id' => $orderId))->save($order_arr);
            if ($ret === false) {
                M()->rollback();
                $this->error('处理失败：订单状态更新失败');
            }
            // 吴刚砍树活动twx_cuttree_info订单状态更新
            $buyStatus = M('twx_cuttree_info')->where("order_id={$orderId}")->getField(
                'buy_status');
            if (! empty($buyStatus) && $buyStatus == '1') {
                $res = M('twx_cuttree_info')->where(
                    "order_id={$orderInfo['order_id']}")->save(
                    array(
                        'buy_status' => '0', 
                        'order_id' => ''));
                if ($res === false) {
                    M()->rollback();
                    $this->error('处理失败：cut订单状态更新失败');
                }
            }
            // 回滚库存
            if ($result['order_type'] == '0') { // 单品订单
                $ret = M('tbatch_info')->where(
                    array(
                        'm_id' => $result['batch_no'], 
                        'storage_num' => array(
                            'neq', 
                            '-1')))->setInc('remain_num', $result['buy_num']);
                if ($ret === false) {
                    M()->rollback();
                    $this->error("处理失败：库存更新失败");
                }
            } elseif ($result['order_type'] == '2') { // 小店订单
                $exorderList = M('ttg_order_info_ex')->where(
                    array(
                        'order_id' => $orderId))->select();
                if (! $exorderList) {
                    M()->rollback();
                    $this->error("处理失败：子订单数据错误");
                }
                foreach ($exorderList as $v) {
                    $ret = M('tbatch_info')->where(
                        array(
                            'id' => $v['b_id'], 
                            'storage_num' => array(
                                'neq', 
                                '-1')))->setInc('remain_num', $v['goods_num']);
                    if ($ret === false) {
                        M()->rollback();
                        $this->error("处理失败：小店子订单商品库存回滚失败");
                    }
                }
            }
            // 回滚红包
            // 更新红包
            $bonusInfo = M('tbonus_use_detail')->where(
                array(
                    'order_id' => $orderId))->select();
            if ($bonusInfo) {
                foreach ($bonusInfo as $vv) {
                    $result = M('tbonus_detail')->where(
                        array(
                            'id' => $vv['bonus_detail_id'], 
                            'node_id' => $this->node_id))->setDec('use_num', 
                        $vv['bonus_use_num']);
                    if ($result === false) {
                        M()->rollback();
                        $this->error("处理失败：红包使用数量统计回滚失败");
                    }
                }
                // 统一更新他bonus_use_detail
                $result = M('tbonus_use_detail')->where(
                    array(
                        'order_id' => $orderId))->save(
                    array(
                        'order_id' => '', 
                        'bonus_use_num' => 0, 
                        'bonus_amount' => 0, 
                        'use_time' => '', 
                        'order_amt_per' => 0));
                if ($result === false) {
                    M()->rollback();
                    $this->error("处理失败：红包使用明细回滚失败");
                }
            }
        } elseif ($status == '2') {
            // 确认到货
            // 更新订单状态
            $order_arr = array(
                'order_status' => '0', 
                'pay_status' => '2', 
                'pay_channel' => '4', 
                'pay_seq' => date('YmdHis') . $this->user_id, 
                'update_time' => date('YmdHis'));
            $ret = M('tintegral_order_info')->where(
                array(
                    'order_id' => $orderId))->save($order_arr);
            if ($ret === false) {
                M()->rollback();
                $this->error('处理失败：订单状态更新失败');
            }
            // 吴刚砍树活动twx_cuttree_info订单状态更新
            $buyStatus = M('twx_cuttree_info')->where(
                "order_id={$orderInfo['order_id']}")->getField('buy_status');
            if (! empty($buyStatus) && $buyStatus == '1') {
                $res = M('twx_cuttree_info')->where(
                    "order_id={$orderInfo['order_id']}")->save(
                    array(
                        'buy_status' => '2'));
                if ($res === false) {
                    M()->rollback();
                    $this->error('处理失败：cut订单状态更新失败');
                }
            }
        }
        
        // 分销触发
        if ($result['saler_id']) {
            $wfxService = D('Wfx', 'Service');
            ;
            $wfxService->return_bonus($result['node_id'], $result['order_id'], 
                $result['saler_id']);
        }
        M()->commit();
        $this->success('处理成功');
    }

    function checkExpressName() {
        $expressName = I('post.express');
        $expressNameStr = mb_substr($expressName, 0, 2, 'utf-8');
        $expressInfoModel = M('TexpressInfo');
        $condition = $expressNameStr . '%';
        $expressTableName = $expressInfoModel->where(
            array(
                'express_name' => array(
                    'like', 
                    $condition)))->getfield('express_name');
        $this->ajaxReturn($expressTableName);
    }

    public function orderPrint() {
        $orderId = I('get.order_id');
        
        $model = M('tintegral_order_info');
        $map = array(
            'order_id' => $orderId);
        $orderStatus = array(
            '1' => '未支付', 
            '2' => '已支付');
        $deliveryStatus = array(
            '1' => '待配送', 
            '2' => '配送中', 
            '3' => '已配送');
        
        $field = array(
            'o.*,g.group_goods_name');
        $orderInfo = M()->table('tintegral_order_info o')
            ->field($field)
            ->join("tmarketing_info g ON o.batch_no=g.id")
            ->where($map)
            ->find();
        // 获取城市信息
        if ($orderInfo['receiver_citycode']) {
            $cityInfo = M('tcity_code')->where(
                array(
                    'path' => $orderInfo['receiver_citycode']))
                ->field(
                'province_code, city_code, town_code, province, city, town')
                ->find();
            $orderInfo['province_code'] = $cityInfo['province_code'];
            $orderInfo['city_code'] = $cityInfo['city_code'];
            $orderInfo['town_code'] = $cityInfo['town_code'];
            $orderInfo['province'] = $cityInfo['province'];
            $orderInfo['city'] = $cityInfo['city'];
            $orderInfo['town'] = $cityInfo['town'];
        }
        $orderInfoExList = M('tintegral_order_info_ex')->where($map)->select();
        $skuId = 0;
        foreach ($orderInfoExList as $val) {
            if (isset($val['integral_sku_id'])) {
                $skuId = $val['integral_sku_id'];
            }
        }
        if ($orderInfo['is_gift'] == '1')
            $giftInfo = M("tintegral_order_trace")->where($map)->select();
        $hav_count = count($giftInfo);
        $bonusInfo = M('tbonus_use_detail')->where($map)->select();
        $receiverType = array(
            '0' => ' 凭证自提订单', 
            '1' => '物流订单');
        $payChannel = array(
            '1' => '支付宝', 
            '2' => '银联', 
            '3' => '微信支付', 
            '4' => '货到付款');
        $this->assign('payChannel', $payChannel);
        $this->assign('receiverType', $receiverType);
        $this->assign('orderInfo', $orderInfo);
        $this->assign('skuId', $skuId);
        $this->assign('orderInfoExList', $orderInfoExList);
        $this->assign('orderStatus', $orderStatus);
        $this->assign('deliveryStatus', $deliveryStatus);
        $this->assign('bonusInfo', $bonusInfo);
        $this->assign('giftInfo', $giftInfo);
        $this->assign('hav_count', $hav_count);
        $this->display("OrderList/print");
    }
}