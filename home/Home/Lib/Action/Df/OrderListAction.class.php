<?php

class OrderListAction extends BaseAction {
    
    // public $_authAccessMap = '*';
    public function _initialize() {
        parent::_initialize();
    }

    public function index() {
        $model = M('tfb_df_pointshop_order_info');
        $map = array(
            'o.node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"));
        
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
        if ($data['is_gift'] != '') {
            $map['o.is_gift'] = $data['is_gift'];
        }
        // 处理特殊查询字段
        $beginDate = I('start_time', null, 'mysql_real_escape_string,trim');
        $endDate = I('end_time', null, 'mysql_real_escape_string,trim');
        if (! empty($beginDate) && ! empty($endDate)) {
            $map['o.add_time'] = array(
                'between', 
                $beginDate . '000000,' . $endDate . '235959');
        } elseif (! empty($beginDate) && empty($endDate)) {
            $map['o.add_time'] = array(
                'egt', 
                $beginDate . '000000');
        } elseif (empty($beginDate) && ! empty($endDate)) {
            $map['o.add_time'] = array(
                'elt', 
                $endDate . '235959');
        }
        $map['o.order_type'] = '2';
        import('ORG.Util.Page'); // 导入分页类
        $mapcount =M()->table("tfb_df_pointshop_order_info o")->where($map)->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        
        foreach ($data as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        
        $show = $Page->show(); // 分页显示输出
        
        $orderList = M()
            ->table("tfb_df_pointshop_order_info o")
            ->field('o.*,c.name as channel_name')
            ->join("tbatch_channel b ON b.id=o.from_channel_id")
            ->join("tchannel c ON c.id=b.channel_id")
            ->where($map)
            ->order('o.add_time desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        $payStatus = array(
            '1' => '未支付', 
            '2' => '已支付');
        $orderStatus = array(
            '0' => '正常', 
            '1' => '取消');
        $deliveryStatus = array(
            '1' => '待配送', 
            '2' => '配送中', 
            '3' => '已配送', 
            '4' => '凭证自提');
        $receiverType = array(
            '0' => ' 凭证自提订单', 
            '1' => '物流订单');
        $gift_arr = array(
            '0' => '自消费', 
            '1' => '送礼');
        $m_id = M('tmarketing_info')->where(
            array(
                'node_id' => $this->node_id, 
                'batch_type' => '1001'))->getField('id');
        // 判断是否显示凭证操作按钮
        foreach ($orderList as &$v) {
            $count = M('torder_trace')->where(
                array(
                    'order_id' => $v['order_id']))->count();
            if ($count > 0)
                $v['code_show'] = 1;
            else
                $v['code_show'] = 0;
        }
        $this->assign('receiverType', $receiverType);
        $this->assign('payStatus', $payStatus);
        $this->assign('orderStatus', $orderStatus);
        $this->assign('deliveryStatus', $deliveryStatus);
        $this->assign('orderList', $orderList);
        $this->assign('post', $data);
        $this->assign('m_id', $m_id);
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('gift_arr', $gift_arr);
        $this->assign('empty', '<tr><td colspan="11">无数据</td></span>');
        $this->display('OrderList/index'); // 输出模板
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
        $mapcount = M()->table('tfb_df_pointshop_order_info o')
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
        $orderList = M()->table('tfb_df_pointshop_order_info o')
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
        
        // dump($orderList);exit;
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('orderList', $orderList);
        $this->assign('orderStatus', $orderStatus);
        $this->assign('deliveryStatus', $deliveryStatus);
        $this->assign('post', $_REQUEST);
        $this->assign('batchNo', $batchNo);
        $this->assign('empty', '<tr><td colspan="10">无数据</td></span>');
        $this->display('OrderList/orderList');
    }
    
    // 数据导出
    public function export() {
        // 查询条件组合
        $where = "WHERE o.node_id in (" . $this->nodeIn() .
             ") and o.order_type='2' ";
        
        if (! empty($_POST)) {
            $filter = array();
            $filter[] = "o.order_type = '2'";
            $condition = array_map('trim', $_POST);
            if (isset($condition['goods_name']) && $condition['goods_name'] != '') {
                $filter[] = "m.group_goods_name LIKE '%{$condition['goods_name']}%'";
            }
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
                $filter[] = "o.add_time >='{$condition['start_time']}000000'";
            }
            if (isset($condition['end_time']) && $condition['end_time'] != '') {
                $filter[] = "o.add_time <= '{$condition['end_time']}235959'";
            }
        }
        if (! empty($filter)) {
            $condition = implode(' AND ', $filter);
            $where .= ' AND ' . $condition;
        }
        
        $filter[] = "o.node_id in({$this->nodeIn()})";
        $count = M()
            ->table("tfb_df_pointshop_order_info o")
            ->join("tmarketing_info m ON o.batch_no=m.id")
            ->where($filter)
            ->count();
        if ($count <= 0)
            $this->error('无订单数据可下载');
            // 计算费率
        $tnodeAccount = M('Tnode_account');
        $tnodeAccountResult = $tnodeAccount->where(
            "account_type='1' AND node_id='{$this->node_id}'")->select();
        if (is_array($tnodeAccountResult)) {
            $tnodeAccountResult[0]['fee_rate'] = $tnodeAccountResult[0]['fee_rate'] ? $tnodeAccountResult[0]['fee_rate'] : 0.02;
        }
        $fee_rate = $tnodeAccountResult[0]['fee_rate'];
        
        $sql = "SELECT o.order_id,m.b_name AS batch_name,d.name AS 'from_batch_name',c.name AS from_channel_name				,o.order_phone,o.add_time,o.receiver_name,o.receiver_phone,o.receiver_addr,IFNULL(o.freight, '0.00') freight,IFNULL(bd.bonus_amount,0.00) AS bonus_amt,
				m.price,m.goods_num,o.order_amt AS amount,CASE o.receiver_type WHEN '0' THEN '凭证自提订单' WHEN '1' THEN '物流订单' ELSE '其他' END receiver_type,CASE o.pay_status WHEN '1' THEN '未支付' WHEN '2' THEN '已支付' ELSE '其他' END pay_status,
				o.pay_seq,
				CASE WHEN o.pay_channel='1' AND o.pay_status='2' THEN ROUND(o.order_amt*{$fee_rate},2) WHEN o.pay_channel<>'1' AND o.pay_status='2' THEN ROUND(o.order_amt*0.02,2) ELSE 0.00 END fee_cost,
				CASE WHEN o.pay_channel='1' AND o.pay_status='2' THEN o.order_amt-ROUND(o.order_amt*{$fee_rate},2) WHEN o.pay_channel<>'1' AND o.pay_status='2' THEN o.order_amt-ROUND(o.order_amt*0.02,2) ELSE 0.00 END real_cost,
				CASE o.pay_channel WHEN '0' THEN '银行卡' WHEN '1' THEN '支付宝' WHEN '2' THEN '银行卡' WHEN '3' THEN '微信支付' ELSE '其他' END pay_channel,
				CASE o.order_status WHEN '0' THEN '正常' WHEN '1' THEN '取消' ELSE '其他' END order_status,
				CASE o.delivery_status WHEN '1' THEN '待配送' WHEN '2' THEN '配送中' WHEN '3' THEN '已配送' ELSE '其他' END delivery_status,tt.send_seq AS send_seq
				FROM tfb_df_pointshop_order_info o
				LEFT JOIN tbonus_use_detail bd ON bd.order_id=o.order_id
				JOIN tfb_df_pointshop_order_info_ex m ON m.order_id=o.order_id
				JOIN tbatch_channel b ON b.id=o.from_channel_id
				JOIN tchannel c ON c.id=b.channel_id 
				LEFT JOIN(SELECT order_id,GROUP_CONCAT(code_trace) AS send_seq FROM torder_trace GROUP BY order_id) tt ON tt.order_id=o.order_id
				JOIN tmarketing_info d ON d.id=o.from_batch_no {$where}";
        
        $cols_arr = array(
            'order_id' => '订单号', 
            'batch_name' => '商品名', 
            'from_batch_name' => '来源活动名', 
            'from_channel_name' => '来源渠道名', 
            'receiver_type' => '订单类型', 
            'order_phone' => '下单手机号', 
            'add_time' => '下单时间', 
            'receiver_name' => '收货人姓名', 
            'receiver_phone' => '收货人手机号', 
            'receiver_addr' => '收货地址', 
            'price' => '商品单价', 
            'goods_num' => '商品数量', 
            'amount' => '订单金额', 
            'bonus_amt' => '优惠金额（红包）', 
            'freight' => '运费', 
            'pay_status' => '支付状态', 
            'fee_cost' => '支付扣费', 
            'real_cost' => '实收金额', 
            'pay_seq' => '支付流水', 
            'pay_channel' => '支付方式', 
            'order_status' => '订单状态', 
            'send_seq' => '发码流水号');
        if (querydata_download($sql, $cols_arr, M()) == false) {
            $this->error('下载失败');
        }
    }
    
    // 数据导出
    public function exportOne() {
        $order_id = I('get.order_id', null);
        if (! $order_id)
            $this->error('数据错误');
            // 查询条件组合
        $where = array(
            'o.node_id' => $this->node_id, 
            'o.order_id' => $order_id);
        $count = M()->table("tfb_df_pointshop_order_info o")
            ->join("tmarketing_info m ON o.batch_no=m.id")
            ->where($where)
            ->count();
        if ($count <= 0)
            $this->error('无订单数据可下载');
        
        $sql = "SELECT o.order_id,m.name AS batch_name,m.group_goods_name,d.name AS 'from_batch_name',c.name as from_channel_name ,o.order_phone,o.receiver_name,o.receiver_phone,o.receiver_addr,
				m.group_price,o.buy_num,o.order_amt,case o.pay_status when '1' then '未支付' when '2' then '已支付' else '其他' end pay_status,o.pay_seq,case o.pay_channel when '0' then '银行卡' when '1' then '支付宝' when '2' then '银行卡' else '其他' end pay_channel,case o.order_status when '0' then '正常' when '1' then '取消' else '其他' end order_status,case o.delivery_status when '1' then '待配送' when '2' then '配送中' when '3' then '已配送' else '其他' end delivery_status,o.send_seq
				FROM tfb_df_pointshop_order_info o
				JOIN tmarketing_info m ON o.batch_no=m.id
				JOIN tbatch_channel b ON b.id=o.from_channel_id
				JOIN tchannel c ON c.id=b.channel_id 
				JOIN tmarketing_info d ON d.id=o.from_batch_no 
				where o.node_id='{$this->node_id}' and o.order_id={$order_id}";
        
        $cols_arr = array(
            'order_id' => '订单号', 
            'batch_name' => '活动名', 
            'group_goods_name' => '商品名称', 
            'from_batch_name' => '来源活动名', 
            'from_channel_name' => '来源渠道名', 
            'order_phone' => '下单手机号', 
            'receiver_name' => '收货人姓名', 
            'receiver_phone' => '收货人手机号', 
            'receiver_addr' => '收货地址', 
            'group_price' => '商品单价', 
            'buy_num' => '订单数量', 
            'order_amt' => '订单金额', 
            'pay_status' => '支付状态', 
            'pay_seq' => '支付流水', 
            'pay_channel' => '支付方式', 
            'order_status' => '订单状态', 
            'send_seq' => '发码流水号');
        if (querydata_download($sql, $cols_arr, M()) == false) {
            $this->error('下载失败');
        }
    }
    
    // 状态修改
    public function editStatus() {
        $orderId = I('order_id', null);
        if (is_null($orderId)) {
            $this->error('缺少订单号');
        }
        $result = M('tfb_df_pointshop_order_info')->where("order_id={$orderId}")->find();
        if (! $result) {
            $this->error('未找到订单信息');
        }
        if ($this->isPost()) {
            $status = I('post.status', null);
            if (is_null($status)) {
                $this->error('缺少配送状态');
            }
            $data = array(
                'order_id' => $orderId, 
                'delivery_status' => $status);
            $ret = M('tfb_df_pointshop_order_info')->where(
                "order_id='" . $orderId . "'")->save(
                array(
                    'delivery_status' => $status));
            if ($ret !== false) {
                $message = array(
                    'respId' => 0, 
                    'respStr' => '更新成功');
                $this->success($message);
            } else {
                $this->error('更新失败' . $status . "==" . $orderId);
            }
        }
        
        $deliveryStatus = array(
            '1' => '待配送', 
            '2' => '配送中', 
            '3' => '已配送');
        $this->assign('deliveryStatus', $deliveryStatus);
        $this->assign('order_id', $result['order_id']);
        $this->assign('delivery_status', $result['delivery_status']);
        $this->display('OrderList/editStatus');
    }

    public function orderPrint() {
        $orderId = I('get.order_id');
        
        $model = M('tfb_df_pointshop_order_info');
        $map = array(
            'order_id' => $orderId);
        $orderStatus = array(
            '1' => '未支付', 
            '2' => '已支付');
        $deliveryStatus = array(
            '1' => '待配送', 
            '2' => '配送中', 
            '3' => '已配送');
        $receiverType = array(
            '0' => ' 凭证自提订单', 
            '1' => '物流订单');
        $payChannel = array(
            '1' => '支付宝', 
            '2' => '银联', 
            '3' => '微信支付');
        
        $orderInfo = M('tfb_df_pointshop_order_info')->where($map)->find();
        $orderInfoExList = M()->table("tfb_df_pointshop_order_info_ex a")
            ->join("tbatch_info b on a.b_id=b.id")
            ->where($map)
            ->field("a.*,b.batch_no")
            ->select();
        $bonusInfo = M('tbonus_use_detail')->where($map)->select();
        $memberInfo = M('tfb_df_member')->where(
            array(
                'openid' => $orderInfo['openid']))->find();
        
        if ($orderInfo['is_gift'] == '1')
            $giftInfo = M('torder_trace')->where($map)->select();
        $hav_count = count($giftInfo);
        $this->assign('payChannel', $payChannel);
        $this->assign('receiverType', $receiverType);
        $this->assign('orderInfo', $orderInfo);
        $this->assign('orderInfoExList', $orderInfoExList);
        $this->assign('orderStatus', $orderStatus);
        $this->assign('deliveryStatus', $deliveryStatus);
        $this->assign('bonusInfo', $bonusInfo);
        $this->assign('giftInfo', $giftInfo);
        $this->assign('hav_count', $hav_count);
        $this->assign('memberInfo', $memberInfo);
        $this->display('OrderList/orderPrint'); // 输出模板
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
}
