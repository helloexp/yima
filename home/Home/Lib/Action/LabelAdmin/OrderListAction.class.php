<?php

/**
 * 团购活动
 *
 * @author bao
 */
class OrderListAction extends BaseAction {

    private $fileNameList = array(
        'pendingDispatchOrderList' => array(
            'realFileName' => 'pendingDispatchOrderList.csv', 
            'downloadFileName' => '待配送订单列表.csv'), 
        'operateFailureExpressNoList' => array(
            'realFileName' => 'operateFailureExpressNoList.csv', 
            'downloadFileName' => '录入失败运单列表.csv'), 
        'batchHandleOrderList' => array(
            'realFileName' => 'batchHandleOrderList.csv', 
            'downloadFileName' => '批处理配送运单.csv'), 
        'batchHandleFailureOrderList' => array(
            'realFileName' => 'batchHandleFailureOrderList.csv', 
            'downloadFileName' => '批处理配送失败运单.csv')
    );

    /**
     *
     * @var TtgOrderInfoModel
     */
    public $TtgOrderInfoModel;

    /**
     *
     * @var TtgOrderByCycleModel
     */
    public $TtgOrderByCycle;

    /**
     *
     * @var TexpressInfoModel
     */
    public $TexpressInfoModel;

    public function _initialize() {
        parent::_initialize();
        
        if (defined('RUNTIME_PATH')) {
            $this->storageDir = realpath(RUNTIME_PATH . 'Temp') . '/' .
                 $this->node_id;
        } else {
            $this->storageDir = realpath(APP_PATH . 'Runtime/Temp') . '/' .
                 $this->node_id;
        }
        $this->TexpressInfoModel = D('TexpressInfo');
        $this->TtgOrderByCycle = D('TtgOrderByCycle');
        $this->TtgOrderInfoModel = D('TtgOrderInfo');
        
        // 判断是否显示分销订货订单
        if (! $this->hasPayModule('m3') && $this->wc_version != 'v4') {
            $this->assign('WfxBookOrderMenu', 'no');
        }
    }

    public function beforeCheckAuth() {
        $this->_authAccessMap = '*';
    }

    public function index() {
        
        // 更新订单通知表 把未读记录更新成已读
        $notice_data = array(
            "status" => 1);
        $result = M('torder_notice')->where(
            "node_id = '" . $this->node_id . "'")->save($notice_data);
        $result = M('tmessage_stat')->where(
            array(
                'node_id' => $this->node_id, 
                'message_type' => '4'))->save(
            array(
                'new_message_cnt' => 0));
        $model = M('ttg_order_info');
        $map = array(
            'o.node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"), 
            'o.other_type' => 0); // 非订购订单
        
        $data = $_REQUEST;
        
        if (isset($data['order_id']) && $data['order_id'] != '') {
            $map['o.order_id'] = $data['order_id'];
        }
        if (isset($data['pay_status']) && $data['pay_status'] != '') {
            $map['o.pay_status'] = $data['pay_status'];
        }
      /*  if (isset($data['order_status']) && $data['order_status'] != '') {
            $map['o.order_status'] = $data['order_status'];
        }
	*/	
        if (isset($data['delivery_status']) && $data['delivery_status'] != '') {
            $map['o.delivery_status'] = $data['delivery_status'];
        }
        if (isset($data['goods_no']) && $data['goods_no'] != '') {
            $map['tgi.customer_no'] = $data['goods_no'];
        }
        if (isset($data['receiver_type']) && $data['receiver_type'] != '') {
            $map['o.receiver_type'] = $data['receiver_type'];
        }
        if (in_array($this->node_id, C('fb_tongbaozhai.node_id'), true)) {
            if ($data['saler_id'] != '') {
                $map['sc.id'] = $data['saler_id'];
            }
        }
        // 博雅非标
        if ($this->node_id == C('fb_boya.node_id')) {
            if ($data['saler_name'] != '') {
                $parm1 = M()->table('tecshop_promotion_member m')
                    ->join(
                    'tecshop_promotion p ON p.promotion_id=m.promotion_id')
                    ->where(
                    array(
                        'p.petname' => array(
                            'like', 
                            '%' . $data['saler_name'] . '%')))
                    ->getField('GROUP_CONCAT(m.id)');
                $map['o.parm1'] = array(
                    'in', 
                    $parm1);
            }
        }
        // 爱蒂宝
        if ($this->node_id == C('adb.node_id')) {
            if ($data['order_store']!= '') {
                $store_ids = M()->table("tstore_info tis")->where(
                    array(
                        'tis.node_id' => $this->node_id, 
                        'tis.store_name' => array(
                            'like', 
                            '%' . $data['order_store'] . '%')))->getField(
                    'GROUP_CONCAT(tis.store_id)');
                if ($store_ids != '') {
                    $map['taeo.order_store_id'] = array(
                        'in', 
                        $store_ids);
                } else {
                    if ($data['order_store'] == '总店' ||
                         $data['order_store'] == '爱蒂宝总店' ||
                         $data['order_store'] == '爱蒂宝') {
                        $map['taeo.order_store_id'] = 0;
                    }
                }
            }
            if ($data['deliver_store']!= '') {
                $map['ti.store_short_name'] = array(
                    'like', 
                    '%' . $data['deliver_store'] . '%');
            }
        }
        if (isset($data['is_gift']) && $data['is_gift'] != '') {
            $map['o.is_gift'] = $data['is_gift'];
        }
        if (isset($data['rece_phone']) && $data['rece_phone'] != '') {
            $map['o.receiver_phone'] = $data['rece_phone'];
        }
        if (isset($data['order_phone']) && $data['order_phone'] != '') {
            $map['o.order_phone'] = $data['order_phone'];
        }
        if (isset($data['channel_name']) && $data['channel_name'] != '') {
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
            if ($err_count == 0) {
                $err_flag = 0;
            } else {
                $errOrderList = array();
                $errOrderList = M()->table('torder_trace o')
                    ->field('o.order_id')
                    ->join('tbarcode_trace b on b.request_id=o.code_trace')
                    ->where(
                    array(
                        'b.node_id' => $this->node_id, 
                        'b.trans_type' => '0001', 
                        'b.status' => '3', 
                        'other_type' => '0'))
                    ->select();
                $errOrderList = array_valtokey($errOrderList, '', 'order_id');
                $map['o.order_id'] = array(
                    'in', 
                    $errOrderList);
            }
        }
        
        import('ORG.Util.Page'); // 导入分页类
        $field = array(
            'o.*,m.group_goods_name,m.group_price');
        $mapcount = M()->table('ttg_order_info o')
            ->join("tbatch_channel b ON b.id=o.batch_channel_id")
            ->join("ttg_order_info_ex ttoie ON ttoie.order_id = o.order_id")
            ->join("tbatch_info tbi ON tbi.id = ttoie.b_id")
            ->join("tgoods_info tgi ON tbi.goods_id = tgi.goods_id")
            ->join("tchannel c ON c.id=b.channel_id")
            ->join("tmarketing_info m ON o.batch_no=m.id")
            ->join("tmember_info sc ON sc.id=o.parm1 and sc.node_id=o.node_id")
            ->where($map)
            ->count('DISTINCT(o.order_id)'); // 查询满足要求的总记录数
        
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        
        foreach ($data as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        //初始化变量
        $provinceData = array();
        $adb_flag = null;
        $boya_flag = null;
        
        $show = $Page->show(); // 分页显示输出
        if ($this->node_id == C('fb_boya.node_id')) {
            $field2 = array(
                'o.*,m.group_goods_name,m.batch_type,m.group_price,c.name as channel_name,p.petname');
            $orderList = M()->table('ttg_order_info o')
                ->field($field2)
                ->join("tmarketing_info m ON o.batch_no=m.id")
                ->join("ttg_order_info_ex ttoie ON ttoie.order_id = o.order_id")
                ->join("tbatch_info tbi ON tbi.id = ttoie.b_id")
                ->join("tgoods_info tgi ON tbi.goods_id = tgi.goods_id")
                ->join("tbatch_channel b ON b.id=o.batch_channel_id")
                ->join("tchannel c ON c.id=b.channel_id")
                ->join("tecshop_promotion_member sc ON sc.id=o.parm1")
                ->join("tecshop_promotion p ON p.promotion_id=sc.promotion_id")
                ->where($map)
                ->order('add_time desc')
                ->group('o.order_id DESC')
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->select();
        } elseif ($this->node_id == C('adb.node_id')) {
            $field2 = array(
                "o.*,m.group_goods_name,m.batch_type,m.group_price,c.name as channel_name,taeo.order_store_id,taeo.deliver_store_id,taeo.deliver_period,taeo.deliver_period2,ti.store_short_name,ti.province,IFNULL(tis.store_name,'爱蒂宝总店') AS store_name,IFNULL(t.store_name,' ') AS self_store_name");
            $orderList = M()->table('ttg_order_info o')
                ->field($field2)
                ->join("tmarketing_info m ON o.batch_no=m.id")
                ->join("tbatch_channel b ON b.id=o.batch_channel_id")
                ->join("tchannel c ON c.id=b.channel_id")
                ->join("tfb_adb_ecshop_order taeo on taeo.order_id=o.order_id")
                ->join("tstore_info ti on taeo.deliver_store_id=ti.store_id")
                ->join("tstore_info tis on taeo.order_store_id=tis.store_id")
                ->join("tstore_info t on taeo.self_store_id=t.store_id")
                ->where($map)
                ->order('add_time desc')
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->select();
            $provinces=M()->table("tfb_adb_store_page tasp")->join("tstore_info ti on tasp.store_id=ti.store_id")->where(array('ti.node_id'=>$this->node_id))->getField('GROUP_CONCAT(ti.province_code)');
            $provinceData=M()->table("tcity_code tc")->field("tc.province")->where(array('tc.province_code'=>array('in',$provinces),'tc.city_level'=>'1'))->select();
        }else {
            $field2    = array('o.*,m.group_goods_name,m.batch_type,m.group_price,c.name as channel_name,sc.name as petname');
            $orderList = M()->table('ttg_order_info o')
                ->field($field2)
                ->join("tmarketing_info m ON o.batch_no=m.id")
                ->join("ttg_order_info_ex ttoie ON ttoie.order_id = o.order_id")
                ->join("tbatch_info tbi ON tbi.id = ttoie.b_id")
                ->join("tgoods_info tgi ON tbi.goods_id = tgi.goods_id")
                ->join("tbatch_channel b ON b.id=o.batch_channel_id")
                ->join("tchannel c ON c.id=b.channel_id")
                ->join(
                "tmember_info sc ON sc.id=o.parm1 and sc.node_id=o.node_id")
                ->where($map)
                ->order('add_time desc')
                ->group('o.order_id DESC')
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->select();
        }
        
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
        $marketType = array(
            '26' => '闪购', 
            '27' => '码上买', 
            '29' => '旺财小店', 
            '31' => '小店商品', 
            '55' => '吴刚砍树');
        $gift_arr = array(
            '0' => '自消费', 
            '1' => '送礼');
        
        if (in_array($this->node_id, C('fb_tongbaozhai.node_id'), true)) {
            $tongbaozhai_flag = 1;
            $empty = '<tr><td colspan="11">无数据</td></span>';
        } else {
            $tongbaozhai_flag = 0;
            $empty = '<tr><td colspan="10">无数据</td></span>';
        }
        if ($this->node_id == C('fb_boya.node_id')) {
            $boya_flag = 1;
            $empty = '<tr><td colspan="11">无数据</td></span>';
        } else {
            $tongbaozhai_flag = 0;
            $empty = '<tr><td colspan="10">无数据</td></span>';
        }
        if ($this->node_id == C('adb.node_id')) {
            $adb_flag = 1;
            $empty = '<tr><td colspan="14">无数据</td></span>';
        } else {
            $tongbaozhai_flag = 0;
            $empty = '<tr><td colspan="10">无数据</td></span>';
        }

        $texpressModel = D('TexpressInfo');
        $expressResult = $texpressModel->getLastUsedExpress();
        $this->assign('usedExpress', $expressResult['rescent']);
        $this->assign('expressStr', $expressResult['expressStr']);
        $this->assign('err_flag', $err_flag);
        $this->assign('provinces',json_encode($provinceData));//机构号下所有门店所在省份
        $this->assign('err_count', $err_count);
        $this->assign('payStatus', $payStatus);
        $this->assign('marketType', $marketType);
        $this->assign('orderStatus', $orderStatus);
        $this->assign('cashStatus', $cashStatus);
        $this->assign('deliveryStatus', $deliveryStatus);
        $this->assign('receiverType', $receiverType);
        $this->assign('orderList', $orderList);
        $this->assign('salerInfo', $salerInfo);
        $this->assign('post', $data);
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('empty', $empty);
        $this->assign('tongbaozhai_flag', $tongbaozhai_flag);
        $this->assign('adb_flag', $adb_flag);
        $this->assign('boya_flag', $boya_flag);
        $this->assign('gift_arr', $gift_arr);
        $this->display(); // 输出模板
    }
    
    // 查看订单信息
    public function orderList() {
        $batchNo = I('batch_no', null, 'mysql_real_escape_string');
        if (empty($batchNo)) {
            $this->error('参数错误');
        }
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
        
        // dump($orderList);exit;
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
            
            if (isset($condition['order_id']) && $condition['order_id'] != '') {
                $filter[] = "o.order_id = '{$condition['order_id']}'";
            }
            if (isset($condition['other_type'])) {
                $filter[] = "o.other_type = '{$condition['other_type']}'";
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
            // 博雅非标
            if (isset($condition['saler_name']) && $condition['saler_name'] != '') {
                $parm1 = M()->table('tecshop_promotion_member m')
                    ->join(
                    'tecshop_promotion p ON p.promotion_id=m.promotion_id')
                    ->where(
                    array(
                        'p.petname' => array(
                            'like', 
                            '%' . $condition['saler_name'] . '%')))
                    ->getField('GROUP_CONCAT(m.id)');
                $filter[] = "o.parm1 in (" . $parm1 . ")";
            }
            // 爱蒂宝
            if ($this->node_id == C('adb.node_id')) {
                if (isset($condition['order_store']) &&
                     $condition['order_store'] != '') {
                    $stores_id=M("tstore_info")->where(array('store_short_name'=>array('like','%'.$condition['order_store'].'%'),'node_id'=>$this->node_id))->getField('GROUP_CONCAT(store_id)');
                    if($stores_id){
                        $filter[] ="taeo.order_store_id in (".$stores_id.")";
                    }elseif($condition['order_store']=='爱蒂宝'||$condition['order_store']=='爱蒂宝总店'||$condition['order_store']=='总店'){
                        $filter[] ="taeo.order_store_id =0";
                    }
                }
                if (isset($condition['deliver_store']) &&
                     $condition['deliver_store'] != '') {
                    $stores_id=M("tstore_info")->where(array('store_short_name'=>array('like','%'.$condition['deliver_store'].'%'),'node_id'=>$this->node_id))->getField('GROUP_CONCAT(store_id)');
                    $filter[] = "taeo.deliver_store_id in (".$stores_id.")";
                }
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
        if (in_array($this->node_id, C('fb_tongbaozhai.node_id'), true)) {
            $count = M()->table("ttg_order_info o")->join(
                "tmember_info sc ON sc.id=o.parm1 and sc.node_id=o.node_id")
                ->join("tbatch_channel b ON b.id=o.batch_channel_id")
                ->join("tchannel c ON c.id=b.channel_id")
                ->where($filter)
                ->count();
        } elseif($this->node_id==C('adb.node_id')){
            $count = M()->table("ttg_order_info o")->join(
                "tfb_adb_ecshop_order taeo on taeo.order_id=o.order_id")
                ->where($filter)
                ->count();
        }else {
            $count = M()->table("ttg_order_info o")->join(
                "tbatch_channel b ON b.id=o.batch_channel_id")
                ->join("tchannel c ON c.id=b.channel_id")
                ->where($filter)
                ->count();
        }
        // echo M()->_sql();
        if ($count <= 0) {
            $this->error('无订单数据可下载');
        }
        
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
            '7' => array(
                'col_name' => 'is_gift', 
                'col_str' => '订单用途', 
                'col_sel' => "CASE o.is_gift WHEN '0' THEN '自消费' WHEN '1' THEN '送礼' ELSE '其他' END is_gift"), 
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
            '14' => array(
                'col_name' => 'bonus_amt', 
                'col_str' => '优惠金额（红包）', 
                'col_sel' => "o.bonus_use_amt AS bonus_amt"), 
            '15' => array(
                'col_name' => 'freight', 
                'col_str' => '运费', 
                'col_sel' => "IFNULL(o.freight, '0.00') freight"), 
            '16' => array(
                'col_name' => 'pay_status', 
                'col_str' => '支付状态', 
                'col_sel' => "CASE o.pay_status WHEN '1' THEN '未支付' WHEN '2' THEN '已支付' ELSE '其他' END pay_status"), 
            '17' => array(
                'col_name' => 'fee_cost', 
                'col_str' => '支付扣费', 
                'col_sel' => "o.fee_amt as fee_cost"), 
            '18' => array(
                'col_name' => 'real_cost', 
                'col_str' => '实收金额', 
                'col_sel' => "CASE WHEN o.pay_status='2' THEN o.order_amt-o.fee_amt ELSE 0.00 END real_cost"), 
            '19' => array(
                'col_name' => 'pay_channel', 
                'col_str' => '支付方式', 
                'col_sel' => "CASE o.pay_channel WHEN '0' THEN '银行卡' WHEN '1' THEN '支付宝' WHEN '2' THEN '银行卡' WHEN '3' THEN '微信支付' ELSE '其他' END pay_channel"), 
            '20' => array(
                'col_name' => 'order_status', 
                'col_str' => '订单状态', 
                'col_sel' => "CASE o.order_status WHEN '0' THEN '正常' WHEN '1' THEN '取消' ELSE '其他' END order_status"), 
            // '21'=>array('col_name'=>'send_seq','col_str'=>'发码流水号','col_sel'=>"tt.send_seq
            // AS send_seq"),
            '22' => array(
                'col_name' => 'goods_name', 
                'col_str' => '商品', 
                'col_sel' => "CASE o.order_type WHEN '0' THEN CONCAT(m.name,'￥',o.price,'*',o.buy_num) WHEN '2' THEN (SELECT GROUP_CONCAT(CONCAT(e.b_name,'￥',e.price,'*',e.goods_num) SEPARATOR '+') FROM ttg_order_info_ex e WHERE e.order_id=o.order_id) END goods_name"), 
            '23' => array(
                'col_name' => 'saler_name', 
                'col_str' => '销售员', 
                'col_sel' => "saler.name as saler_name"), 
            '24' => array(
                'col_name' => 'super_saler_name', 
                'col_str' => '上级经销商', 
                'col_sel' => "super.name as super_saler_name"), 
            '25' => array(
                'col_name' => 'buy_num', 
                'col_str' => '商品总数', 
                'col_sel' => "o.buy_num"), 
            '26' => array(
                'col_name' => 'channel_name', 
                'col_str' => '渠道来源', 
                'col_sel' => "c.name as channel_name"), 
            '27' => array(
                'col_name' => 'openid', 
                'col_str' => '是否为微信粉丝', 
                'col_sel' => "CASE wx.openid WHEN '' THEN '否' ELSE '是' END openid"), 
            '28' => array(
                'col_name' => 'remarkname', 
                'col_str' => '粉丝标签', 
                'col_sel' => "CASE wx.openid WHEN '' THEN '' ELSE wx.remarkname END remarkname"), 
            '29' => array(
                'col_name' => 'point_amt', 
                'col_str' => '积分抵扣金额', 
                'col_sel' => "o.point_use_amt AS point_amt"), 
            '30' => array(
                'col_name' => 'store_name', 
                'col_str' => '所属门店', 
                'col_sel' => "IFNULL(tis.store_name,'爱蒂宝总店') AS store_name"), 
            '31' => array(
                'col_name' => 'store_short_name', 
                'col_str' => '配送门店', 
                'col_sel' => "ti.store_short_name AS store_short_name"), 
            '32' => array(
                'col_name' => 'deliver_period', 
                'col_str' => '配送时间', 
                'col_sel' => "taeo.deliver_period AS deliver_period"),
            '33' => array(
                'col_name' => 'deliver_period2', 
                'col_str' => '自提时间', 
                'col_sel' => "taeo.deliver_period2 AS deliver_period2"),
            '34' => array(
                'col_name' => 'self_store_name', 
                'col_str' => '自提门店', 
                'col_sel' => "IFNULL(t.store_name,' ') AS self_store_name"),
            '35' => array(
                'col_name' => 'contacts', 
                'col_str' => '自提联系人', 
                'col_sel' => "taeo.contacts AS contacts"),
            '36' => array(
                'col_name' => 'sku_name', 
                'col_str' => '商品规格', 
                'col_sel' => "(SELECT GROUP_CONCAT(CONCAT(e.b_name,'(',e.ecshop_sku_desc,')','￥',e.price,'*',e.goods_num) SEPARATOR '+') FROM ttg_order_info_ex e WHERE e.order_id=o.order_id) as sku_name "),
            );
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
        if (in_array($this->node_id, C('fb_tongbaozhai.node_id'), true)) {
            
            $sql = "select " . $select_v . ",o.parm1,sc.name as petname FROM ttg_order_info o
				LEFT JOIN tmarketing_info m ON o.batch_no=m.id
				LEFT JOIN tbatch_channel b ON b.id=o.batch_channel_id
				LEFT JOIN tchannel c ON c.id=b.channel_id
                                LEFT JOIN tcity_code cc ON cc.path=o.receiver_citycode
				LEFT JOIN tnode_account na ON na.node_id=o.node_id AND na.account_type=o.pay_channel
				LEFT JOIN tmember_info sc ON sc.id=o.parm1 and sc.node_id=o.node_id
                                LEFT JOIN twfx_saler saler ON saler.id=o.saler_id and saler.node_id=o.node_id
                                LEFT JOIN twfx_saler super ON super.id=saler.parent_id
                                LEFT JOIN twx_user wx ON wx.node_id=o.node_id and wx.openid = o.openId
				{$where}";
        } elseif ($this->node_id == C('fb_boya.node_id')) {
            $sql = "select " . $select_v . ",o.parm1,p.petname as petname FROM ttg_order_info o
				LEFT JOIN tmarketing_info m ON o.batch_no=m.id
				LEFT JOIN tbatch_channel b ON b.id=o.batch_channel_id
				LEFT JOIN tchannel c ON c.id=b.channel_id 
                                LEFT JOIN tcity_code cc ON cc.path=o.receiver_citycode
				LEFT JOIN tnode_account na ON na.node_id=o.node_id AND na.account_type=o.pay_channel
				LEFT JOIN tecshop_promotion_member sc ON sc.id=o.parm1
				LEFT JOIN tecshop_promotion p ON p.promotion_id=sc.promotion_id
                                LEFT JOIN twfx_saler saler ON saler.id=o.saler_id and saler.node_id=o.node_id
                                LEFT JOIN twfx_saler super ON super.id=saler.parent_id
                                LEFT JOIN twx_user wx ON wx.node_id=o.node_id and wx.openid = o.openId
				{$where}";
        } elseif ($this->node_id == C('adb.node_id')) {
            $sql = "select " . $select_v . " FROM ttg_order_info o
                LEFT JOIN tmarketing_info m ON o.batch_no=m.id
                LEFT JOIN tbatch_channel b ON b.id=o.batch_channel_id
                LEFT JOIN tchannel c ON c.id=b.channel_id 
                LEFT JOIN tfb_adb_ecshop_order taeo ON taeo.order_id=o.order_id
                LEFT JOIN tstore_info tis ON tis.store_id=taeo.order_store_id
                LEFT JOIN tstore_info ti ON ti.store_id=taeo.deliver_store_id
                LEFT JOIN tstore_info t ON t.store_id=taeo.self_store_id
                                LEFT JOIN tcity_code cc ON cc.path=o.receiver_citycode
                LEFT JOIN tnode_account na ON na.node_id=o.node_id AND na.account_type=o.pay_channel
                                LEFT JOIN twfx_saler saler ON saler.id=o.saler_id and saler.node_id=o.node_id
                                LEFT JOIN twfx_saler super ON super.id=saler.parent_id
                                LEFT JOIN twx_user wx ON wx.node_id=o.node_id and wx.openid = o.openId
                {$where}";
        } else {
            $sql = "select " . $select_v . " FROM ttg_order_info o
				LEFT JOIN tmarketing_info m ON o.batch_no=m.id
				LEFT JOIN tbatch_channel b ON b.id=o.batch_channel_id
				LEFT JOIN tchannel c ON c.id=b.channel_id
                                LEFT JOIN tcity_code cc ON cc.path=o.receiver_citycode
				LEFT JOIN tnode_account na ON na.node_id=o.node_id AND na.account_type=o.pay_channel
                                LEFT JOIN twfx_saler saler ON saler.id=o.saler_id and saler.node_id=o.node_id
                                LEFT JOIN twfx_saler super ON super.id=saler.parent_id
                                LEFT JOIN twx_user wx ON wx.node_id=o.node_id and wx.openid = o.openId
				{$where}";
        }
        if (in_array($this->node_id, C('fb_tongbaozhai.node_id'), true)) {
            $cols_arr['parm1'] = '销售员ID';
            $cols_arr['petname'] = '销售员姓名';
        }
        if ($this->node_id == C('fb_boya.node_id')) {
            $cols_arr['parm1'] = '推广员ID';
            $cols_arr['petname'] = '推广员姓名';
        }
        log_write($sql);
        if (querydata_download($sql, $cols_arr, M()) == false) {
            $this->error('下载失败');
        }
    }
    
    // 根据订单号 获取凭证记录
    public function orderOption() {
        $orderId = I('order_id', null);
        if (! $orderId) {
            $this->error('数据错误');
        }
        
        $orderInfo = M()->table("ttg_order_info o")->field('o.*,m.group_goods_name')
            ->join('tmarketing_info m on m.id=o.batch_no')
            ->where(array(
            'o.order_id' => $orderId))
            ->find();
		/*$req_seq = M()->table("ttg_order_info o")->field('o.*')
			->join('tpos_trace p on p.node_id=o.node_id')
			->where(o.req_seq>0)
			->select();
			if($req_seq['req_seq']!=''){
				echo 123;exit;
			}
		*/
        /*$info=M()->table('tbarcode_trace b')->field('b.trans_time,ti.store_name')
                ->join('tstore_info ti on ti.node_id=b.node_id')
                ->where("use_status=2 AND b.status=0 AND ret_code='0000' and ti.node_id = '{$this->node_id}' and b.node_id = '{$this->node_id}'"
                )
                ->select();*/
        //获取订单 order_trace信息
        $codeTrace = M('torder_trace tt')
               // ->join('ttg_order_info ttoi on tt.order_id=ttoi.order_id')
               // ->where(array('tt.order_id'=>$orderId,'ttoi.pay_status'=>'2','ttoi.order_status'=>'0'))->getField('tt.code_trace');
                 ->where(array('order_id'=>$orderId))->getField('code_trace');
        //获取订单 req_seq信息
        $reqSeq = M('tbarcode_trace')->where(array('request_id'=>$codeTrace))->getField('req_seq');

        //获取pos_seq,trace_time,pos_id
        $posId = M('tpos_trace')->where(array('req_seq'=>$reqSeq))->getField('pos_id');
       $transTime = M('tpos_trace')->where(array('req_seq'=>$reqSeq))->getField('trans_time');
       

        //获取store_id
        $storeId = M('tpos_info')->where(array('pos_id'=>$posId))->getField('store_id');

        //获取store_name
        $storeName = M('tstore_info')->where(array('store_id'=>$storeId))->getfield('store_name');
        $address=M('tstore_info')->where(array('store_id'=>$storeId))->getField('address');
        //var_dump($address);exit;

        // 获取旺财小店的子订单列表
        if ($orderInfo['order_type'] == 2) {
            $orderInfoExList = M('ttg_order_info_ex')->where(
                array(
                    'order_id' => $orderId))->select();
            
            // 旺财小店获取凭证列表
            foreach ($orderInfoExList as &$v) {
                $v['barcodeInfo'] = M()->table("torder_trace o")->field('o.*,b.*')
                    ->join('tbarcode_trace b on b.request_id=o.code_trace')
                    ->where(
                    array(
                        'o.order_id' => $orderId, 
                        'o.b_id' => $v['b_id'], 
                        'b.trans_type' => '0001'))
                    ->select();
            }
        } else {
            // 闪购码上买获取凭证列表
            $barcodeInfo = M()->table("torder_trace o")->field('o.*,b.*')
                ->join('tbarcode_trace b on b.request_id=o.code_trace')
                ->where(
                array(
                    'o.order_id' => $orderId, 
                    'b.trans_type' => '0001'))
                ->select();
        }
        $this->assign('orderInfo', $orderInfo);
        $this->assign('orderInfoExList', $orderInfoExList);
        $this->assign('barcodeInfo', $barcodeInfo);
       // $this->assign('info',$info);
        $this->assign('transTime',$transTime);
        $this->assign('storeName',$storeName);
        $this->assign('address',$address);
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
        if ($count <= 0) {
            $this->error('无订单数据可下载');
        }
        
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
        if($this->node_id==C('adb.node_id')){
            $deliver_store_id=I("deliver_store_id",'');
            $data2   = array(
                'taeo.deliver_store_id'=> $deliver_store_id,
            );
        }
            $data   = array(
                'toi.delivery_number'  => $number,
                'toi.delivery_company' => $company,
                'toi.delivery_date'    => date('YmdHis'),
            );       
        $type = I('type', 'normal', 'string');
        if ($type == 'wfxBookOrder') {
            $data['delivery_status'] = '1';
            $isExist = M('twfx_book_order')->where(
                array(
                    'order_id' => $orderId))->find();
            if (! $isExist) {
                $this->error('未找到订单信息');
            }
            $result = M('twfx_book_order')->where(
                array(
                    'order_id' => $orderId))->save($data);
        } else {
            $result = M('ttg_order_info')->where(
                array(
                    'order_id' => $orderId))->find();
            if (! $result) {
                $this->error('未找到订单信息');
            }
            
            $status = I('delivery_status', null);
            if (is_null($status)) {
                $this->error('缺少配送状态');
            }
            
            $data['toi.delivery_status'] = $status;
            if($this->node_id==C('adb.node_id')){
                $result = M()->table("ttg_order_info toi")->where(array('toi.order_id' => $orderId))->save($data);
                $result2=M()->table("tfb_adb_ecshop_order taeo")->where(array('taeo.order_id' => $orderId))->save($data2);
            }else{
                $result = M()->table("ttg_order_info toi")->where(array('toi.order_id' => $orderId))->save($data);
            }
        }
        if($this->node_id==C('adb.node_id')){
            if($result2 === false){
                $this->error('更新失败');
            }
        }
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
        $result = M('ttg_order_info')->where(
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
            $ret = M('ttg_order_info')->where(
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
                $res = M('twx_cuttree_info')->where("order_id={$orderId}")->save(
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
            $ret = M('ttg_order_info')->where(
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
                $res = M('twx_cuttree_info')->where("order_id={$orderId}")->save(
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
        // 增加用户积分
        $orderInfo = M('ttg_order_info')->where(
            array(
                'order_id' => $orderId))->find();
        // 油豆信息
        $sourceInfo = D('MemberInstall')->orderPay($orderInfo['order_id'], 
            $orderInfo['node_id'], $orderInfo['batch_channel_id']);
        if (! $sourceInfo)
            log_write("添加用户积分失败");
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

    public function orderListIndex($customerNo='',$deliveryStatus='',$startTime = '', $endTime = '') {
        /**
         * 批量录入快递单号 Author: Zhaobl Task: #14432 Date: 2015/10/27
         */
        $node_id = $this->node_id;
        
        // 为避免文件冲突,则根据各自的order_id创建目录
        if (! is_dir($this->storageDir)) {
            @mkdir($this->storageDir);
        }
        
        $path = $this->storageDir . '/' .
             $this->fileNameList['pendingDispatchOrderList']['realFileName'];
        // 若文件存在,则删掉.
        if (file_exists($path)) {
            unlink($path);
        }

        $orderList = $this->TtgOrderInfoModel->index($node_id, $startTime,
            $endTime, $deliveryStatus,$customerNo); // 取订单

        log_write('node_id is',$node_id,'$path is',$path);
        //如果是筛选按钮过来的 返回条数 并把数据存入session 把生成文件的动作移走
        session("order_status" . $node_id,$deliveryStatus);
        session("order_List" . $node_id, $orderList);
        session("order_path" . $node_id, $path);
        echo count($orderList);
        exit;

    }

    public function showTimeCount() {
        /*
         * 寻找起始位置请在本页搜索单号:#14432 打开页面走这一段
         */
        $node_id = $this->node_id;
        /*
         * 这里主要是取最早的一批订单时间和订单条数与渠道来源 需求已改 怕以后产品反悔 所以注释观察
        $orderList = $this->TtgOrderInfoModel->index($node_id, $startTime = '', 
            $endTime = ''); // 取待配送订单
        
        $orderListCount = count($orderList) ? count($orderList) : 0; // 订单总条数*/

        /*if ($orderList[0]['add_time'] == '') {
            $orderListTime = '';
        } else {
            $orderListTime = date('Y年m月d日', 
                strtotime($orderList[0]['add_time'])); // 最早的订单时间
            $showTimeUse = date('Ymd', strtotime($orderList[0]['add_time']));
        }
        $showSource = array();
        foreach ($orderList as $v) {
            $sourceList = $this->TtgOrderInfoModel->getSource($v['order_id']); // 取渠道来源
            $showSource[] = $sourceList;
        }
        $showSource = array_unique($showSource); // 删除重复值
        foreach ($showSource as $k => $v) {
            if (! $v)
                unset($showSource[$k]); // 删除空值
        }*/


        // 将此用户的所有订单保存在session中 当用户上传时可以判断订单是否合法
        $order_id_List = '';
        $allOrderList = $this->TtgOrderInfoModel->allOrderList($node_id);
        for ($i = 0; $i < count($allOrderList); $i ++) {
            $order_id_List .= $allOrderList[$i]['order_id'] . ',';
        }

        session("order_id_List" . $node_id, $order_id_List);

        /*$this->assign('showTimeUse', $showTimeUse);
        $this->assign('source', $showSource);
        $this->assign("Times", $orderListTime);
        $this->assign("count", $orderListCount);*/
        $this->display('orderListIndex');
    }

    public function saveExportFile($path, $orderList) {
        /*
         * 寻找起始位置请在本页搜索单号:#14432
         */
        $order_type = array(
            0 => '单品订单', 
            2 => '旺财小店订单');
        $delivery_status = array(
            1 => '待配送', 
            2 => '配送中', 
            3 => '已配送', 
            4 => '发码自提');
        $pay_channel = array(
            1 => '支付宝', 
            2 => '联动优势', 
            3 => '微信', 
            4 => '货到付款', 
            5 => '测试环境模拟支付');
        
        $fp = fopen($path, 'a+');
        
        $line = "订单号,下单时间,商品,订单类型,配送状态,支付方式,收货人,收货人手机号,收货地址,物流公司,物流单号";
        
        fputcsv($fp, explode(',', iconv('utf-8', 'gb2312', $line))); // 写入头行
        foreach ($orderList as $k => $v) {
            $b_name = $this->TtgOrderInfoModel->getOrderInfoField(
                $v['order_id']); // 取商品名
            $city = $this->TtgOrderInfoModel->getCity($v['receiver_citycode']); // 取省市区
            
            $line = "\t" . $v['order_id'] . "\t" . ',' . "\t" .
                 date('Y/m/d H:i', strtotime($v['add_time'])) . ',' . $b_name .
                 ',' . $order_type[$v['order_type']] . ',' .
                 $delivery_status[$v['delivery_status']] . ',' .
                 $pay_channel[$v['pay_channel']] . ',' . $v['receiver_name'] .
                 ',' . "\t" . $v['receiver_phone'] . "\t" . ',' . $city .
                 $v['receiver_addr'] . ',' .$v['delivery_company']. ',' . 'yd:'.$v['delivery_number'];
            
            fputcsv($fp, explode(',', iconv('utf-8', 'gb2312', $line))); // 逐行写入
        }
        fclose($fp);
    }

    public function orderTime() {
        /*
         * 寻找起始位置请在本页搜索单号:#14432
         */
        // 点击筛选后跳这里
        $customerNo = '';
        $deliveryStatus = '';
        $startTime = '';
        $endTime = '';
        if ($this->isPost()) {
            $data = $_REQUEST;
            $customerNo = $_REQUEST['produce_code']; //商品货号
            $deliveryStatus = $_REQUEST['order_status'];//运单状态
            $startTime = $_REQUEST['start_time'];//开始时间
            $endTime = $_REQUEST['end_time'];//结束时间
            log_write('node_id is',$node_id,' $customerNo is',$customerNo,', $deliveryStatus is',$deliveryStatus,', $startTime is',$startTime,', $endTime is',$endTime);
            $this->orderListIndex($customerNo,$deliveryStatus,$startTime, $endTime);
        }
    }

    public function downLoadOrderList()
    {
        //点击下载订单跳这里 取session数据写入文件
        $node_id = $this->nodeId;
        $order_status = session("order_status" . $node_id);
        $path = session("order_path" . $node_id);
        $orderList = session("order_List" . $node_id);

        if(!$path || !$orderList){
            log_write('node_id is',$node_id,' $path and $orderList is null');
            exit;
        }
        $this->saveExportFile($path, $orderList); // 生成文件
        echo $order_status;
        exit;
    }

    public function downLoad() {
        /*
         * 寻找起始位置请在本页搜索单号:#14432
         */
        $realFile = '';
        $basename = '';
        // 下载走这里
        $fileType = I('get.fileType');
        $fileStatus = I('get.fileStatus');
        if (isset($this->fileNameList[$fileType])) {
            switch($fileStatus){
                case 1:
                    $basename = '待配送订单列表.csv';
                    break;
                case 2:
                    $basename = '已配送订单列表.csv';
                    break;
                case 3:
                    $basename = '全部订单列表.csv';
                    break;
                default:
                    $basename = $this->fileNameList[$fileType]['downloadFileName'];
                    break;
            }
            $realFile = $this->fileNameList[$fileType]['realFileName'];
            $fileName = "$this->storageDir/$realFile";
        } else {
            $basename = '物流公司列表.docx';
            $fileName = 'Home/Upload/orderList/expressCompanyList.docx';
        }
        $fileSize = filesize($fileName);
        
        setcookie("filesize", $fileSize, time() + 24 * 3600 * 30);
        header("Content-type: application/octet-stream");
        header(
            "Content-Disposition: attachment; filename=" .
                 iconv('utf-8', 'gb2312', $basename));
        header('content-length:' . $fileSize);
        readfile($fileName);
    }

    public function upLoad() {
        /*
         * 寻找起始位置请在本页搜索单号:#14432
         */
        // 上传走这里
        $node_id = $this->node_id;
        
        import('ORG.Net.UploadFile') or die('导入上传包失败');
        $upload = new UploadFile();
        $upload->savePath = $this->storageDir . '/'; // 上传文件保存路径
        $info = $upload->uploadOne($_FILES['myFile']); // 调用上传方法生成信息
        $fileWay = $upload->savePath . $info[0]['savename']; // 获取生成文件路径
                                                             
        // 如果上传的文件和下载的文件大小相等 则代表用户并未录入任何信息 果断拦截
        if (filesize($fileWay) == $_COOKIE['filesize']) {
            $this->error('请完成运单信息的录入再上传文件!');
        }
        
        if (strtolower($info[0]['extension']) != 'csv') {
            @unlink($fileWay);
            $this->error('文件类型不符合');
        }
        
        $data = fopen($fileWay, 'a+');
        
        $orderListAllNum = 0; // 记录订单条数
        $changeSucceedNum = 0; // 记录修改条数
        $express = $this->TtgOrderInfoModel->express(); // 取快递公司名单
        $errorOrderList = array(); // 定义失败列表
        
        while (! feof($data)) {
            
            $orderListLine = fgetcsv($data, 1000); // 循环读取
            $order_id = str_replace('	', '', $orderListLine[0]); // 去除csv带有的特殊字符
                                                                 
            // 订单列必须为纯数字才视作数据处理
            if (is_numeric($order_id) && strlen($order_id) != 11) {
                $delivery_company = str_replace(' ', '', 
                    iconv('gb2312', 'utf-8', $orderListLine['9'])); // 快递公司
                $delivery_number = str_replace(' ', '', 
                    iconv('gb2312', 'utf-8', $orderListLine['10'])); // 快递单号
                
                if (strpos($delivery_number, ':') != 2) {
                    $orderListLine['11'] = "请不要删除'yd:'";
                    $errorOrderList[] = $orderListLine;
                } else {
                    $delivery_numbers = explode('yd:', $delivery_number);
                    $delivery_number = $delivery_numbers[1];
                    
                    if ($delivery_company == '' || $delivery_number == '') {
                        $orderListLine['11'] = "物流公司或物流单号未填写";
                        $errorOrderList[] = $orderListLine;
                    } else {
                        if (in_array($delivery_company, $express)) {
                            // 如果物流公司符合规定 则进来
                            $orderListDownLoad = session('order_id_List' . $node_id);
                            $orderListDownLoad = explode(',', $orderListDownLoad);
                            if (in_array($order_id, $orderListDownLoad)) {
                                // 如果订单号存在于session中 则进来
                                $info = $this->TtgOrderInfoModel->getInfo($order_id,$node_id);
                                if ($delivery_company == $info[0]['delivery_company'] && $delivery_number == $info[0]['delivery_number']) {
                                    // 如果上传的物流公司与物流单号等于于从数据库取出的物流公司与物流单号 则认为重复提交
                                    // 计算为成功条数
                                    $changeSucceedNum ++;
                                } else {
                                    // 如果不相等 则认为是新数据 走数据库
                                    $result = $this->TtgOrderInfoModel->orderSave($node_id, $order_id, $delivery_company, $delivery_number);
                                    if (! $result) {
                                        // 如果数据库插入失败 一般不会出现此种情况 除非是数据库突然挂了
                                        $this->error('请重新下载新的订单列表进行录入');
                                    } else {
                                        $changeSucceedNum ++;
                                    }
                                }
                            } else {
                                // 如果订单号不存在于session中 则证明订单号被用户修改过
                                $orderListLine['11'] = "订单号有误";
                                $errorOrderList[] = $orderListLine;
                            }
                        } else {
                            $orderListLine['11'] = "物流公司名称与系统不符，请查询《物流公司列表》";
                            $errorOrderList[] = $orderListLine;
                        }
                    }
                }
                $orderListAllNum ++;
            }
        }
        fclose($data);
        @unlink($fileWay);
        
        $this->assign('orderListNum', $orderListAllNum); // 总条数
         // 如果总条数大于成功条数
        if ($orderListAllNum > $changeSucceedNum) {
            $this->errorOrderList($errorOrderList); // 调用失败列表方法
            $this->assign('status', 1);
            $this->assign('alterNum', $changeSucceedNum); // 成功数
        } else {
            // 若录入成功,则调用方法将目录删掉.
            $this->deldir($this->storageDir);
            $this->assign('status', 2);
        }
        $this->display('orderList_fall');
    }

    public function deldir($dir) {
        /*
         * 寻找起始位置请在本页搜索单号:#14432
         */
        // 删除目录
        $dh = opendir($dir);
        while ($file = readdir($dh)) {
            if ($file != "." && $file != "..") {
                $fullpath = $dir . "/" . $file;
                if (! is_dir($fullpath)) {
                    unlink($fullpath);
                } else {
                    $this->deldir($fullpath);
                }
            }
        }
        closedir($dh);
        if (rmdir($dir)) {
            return true;
        } else {
            return false;
        }
    }

    public function errorOrderList($errorOrderList) {
        /*
         * 寻找起始位置请在本页搜索单号:#14432
         */
        
        // 订单更新失败的 不合格的 都往这扔
        $realFileName = $this->fileNameList['operateFailureExpressNoList']['realFileName'];
        $path = $this->storageDir . '/' . $realFileName;
        
        if (file_exists($path)) {
            unlink($path);
        }
        $fp = fopen($path, 'a+');
        
        $line = "订单号,下单时间,商品,订单类型,配送状态,支付方式,收货人,收货人手机号,收货地址,物流公司,物流单号,失败原因";
        fputcsv($fp, explode(',', iconv('utf-8', 'gb2312', $line))); // 写入头行
        
        foreach ($errorOrderList as $v) {
            $valueLine = "";
            foreach ($v as $key => $value) {
                // 失败原因字段有点特殊 转码会消失 因此进行以下判断
                if ($key != 11) {
                    $valueLine .= $value . ",";
                    $line = iconv('gb2312', 'utf-8', $valueLine);
                } else {
                    $line .= $v[$key];
                }
            }
            fputcsv($fp, explode(',', iconv('utf-8', 'gb2312', $line)));
        }
        fclose($fp);
    }
    // 订购订单
    public function cycleOrderIndex() {
        
        // 更新订单通知表 把未读记录更新成已读
        $notice_data = array(
            "status" => 1);
        $result = M('torder_notice')->where(
            "node_id = '" . $this->node_id . "'")->save($notice_data);
        $result = M('tmessage_stat')->where(
            array(
                'node_id' => $this->node_id, 
                'message_type' => '4'))->save(
            array(
                'new_message_cnt' => 0));
        $model = M('ttg_order_info');
        $map = array(
            'o.node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"), 
            'o.other_type' => 1); // 订购订单
        
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
        if (in_array($this->node_id, C('fb_tongbaozhai.node_id'), true)) {
            if ($data['saler_id'] != '') {
                $map['sc.id'] = $data['saler_id'];
            }
        }
        // 博雅非标
        if ($this->node_id == C('fb_boya.node_id')) {
            if ($data['saler_name'] != '') {
                $parm1 = M()->table('tecshop_promotion_member m')
                    ->join(
                    'tecshop_promotion p ON p.promotion_id=m.promotion_id')
                    ->where(
                    array(
                        'p.petname' => array(
                            'like', 
                            '%' . $data['saler_name'] . '%')))
                    ->getField('GROUP_CONCAT(m.id)');
                $map['o.parm1'] = array(
                    'in', 
                    $parm1);
            }
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
            if ($err_count == 0) {
                $err_flag = 0;
            } else {
                $errOrderList = array();
                $errOrderList = M()->table('torder_trace o')
                    ->field('o.order_id')
                    ->join('tbarcode_trace b on b.request_id=o.code_trace')
                    ->where(
                    array(
                        'b.node_id' => $this->node_id, 
                        'b.trans_type' => '0001', 
                        'b.status' => '3', 
                        'other_type' => '1'))
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
        
        foreach ($data as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        
        $show = $Page->show(); // 分页显示输出
        if ($this->node_id == C('fb_boya.node_id')) {
            $field2 = array(
                "o.*,m.group_goods_name,m.batch_type,m.group_price,c.name as channel_name,p.petname,cy.goods_name,
                SUBSTRING_INDEX(ex.ecshop_sku_desc,'/',-1) as cycle_info,
                COUNT(cy.id) as cycle_num,
                SUM(case cy.delivery_status WHEN '3' THEN 1 END) as send_count,
                SUM(case cy.delivery_status WHEN '3' THEN cy.order_cash END) as get_cash");
            $orderList = M()->table('ttg_order_info o')
                ->field($field2)
                ->join("tmarketing_info m ON o.batch_no=m.id")
                ->join("tbatch_channel b ON b.id=o.batch_channel_id")
                ->join("tchannel c ON c.id=b.channel_id")
                ->join("ttg_order_info_ex ex ON ex.order_id=o.order_id")
                ->join("ttg_order_by_cycle cy ON cy.order_id = o.order_id")
                ->join("tecshop_promotion_member sc ON sc.id=o.parm1")
                ->join("tecshop_promotion p ON p.promotion_id=sc.promotion_id")
                ->where($map)
                ->group('order_id')
                ->order('add_time desc')
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->select();
        } else {
            $field2 = array(
                "o.*,m.group_goods_name,m.batch_type,m.group_price,c.name as channel_name,sc.name as petname,cy.goods_name,
                SUBSTRING_INDEX(ex.ecshop_sku_desc,'/',-1) as cycle_info,
                COUNT(cy.id) as cycle_num,
                SUM(case cy.delivery_status WHEN '3' THEN 1 END) as send_count,
                SUM(case cy.delivery_status WHEN '3' THEN cy.order_cash END) as get_cash");
            $orderList = M()->table('ttg_order_info o')
                ->field($field2)
                ->join("tmarketing_info m ON o.batch_no=m.id")
                ->join("tbatch_channel b ON b.id=o.batch_channel_id")
                ->join("tchannel c ON c.id=b.channel_id")
                ->join("ttg_order_info_ex ex ON ex.order_id=o.order_id")
                ->join("ttg_order_by_cycle cy ON cy.order_id = o.order_id")
                ->join(
                "tmember_info sc ON sc.id=o.parm1 and sc.node_id=o.node_id")
                ->where($map)
                ->group('order_id')
                ->order('add_time desc')
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->select();
        }
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
        $marketType = array(
            '26' => '闪购', 
            '27' => '码上买', 
            '29' => '旺财小店', 
            '31' => '小店商品', 
            '55' => '吴刚砍树');
        $gift_arr = array(
            '0' => '自消费', 
            '1' => '送礼');
        // 处理订单数
        // if($this->node_id == '00004488')
        if (in_array($this->node_id, C('fb_tongbaozhai.node_id'), true)) {
            $tongbaozhai_flag = 1;
            $empty = '<tr><td colspan="11">无数据</td></span>';
        } else {
            $tongbaozhai_flag = 0;
            $empty = '<tr><td colspan="10">无数据</td></span>';
        }
        if ($this->node_id == C('fb_boya.node_id')) {
            $boya_flag = 1;
            $empty = '<tr><td colspan="11">无数据</td></span>';
        } else {
            $tongbaozhai_flag = 0;
            $empty = '<tr><td colspan="10">无数据</td></span>';
        }
        
        $texpressModel = D('TexpressInfo');
        $expressResult = $texpressModel->getLastUsedExpress();
        $this->assign('usedExpress', $expressResult['rescent']);
        $this->assign('expressStr', $expressResult['expressStr']);
        
        $this->assign('err_flag', $err_flag);
        $this->assign('err_count', $err_count);
        $this->assign('payStatus', $payStatus);
        $this->assign('marketType', $marketType);
        $this->assign('orderStatus', $orderStatus);
        $this->assign('cashStatus', $cashStatus);
        $this->assign('deliveryStatus', $deliveryStatus);
        $this->assign('receiverType', $receiverType);
        $this->assign('orderList', $orderList);
        $this->assign('salerInfo', $salerInfo);
        $this->assign('post', $data);
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('empty', $empty);
        $this->assign('tongbaozhai_flag', $tongbaozhai_flag);
        $this->assign('boya_flag', $boya_flag);
        $this->assign('gift_arr', $gift_arr);
        $this->display(); // 输出模板
    }
    
    // 订购订单物流更新
    public function updateOrderDelivery() {
        $orderId = I('order_id', null);
        if (is_null($orderId)) {
            $this->error('缺少订单号');
        }
        $result = M('ttg_order_info')->where(
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
        $result = M('ttg_order_info')->where(
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
    // 修改订购订单价格
    public function updateCyclePrice() {
        $orderId = I('order_id', null);
        if (is_null($orderId)) {
            $this->error('缺少订单号');
        }
        // 订购价格
        $cyclePrice = round(floatval(I('cyclePrice', null)), 2);
        $data = array(
            'order_amt' => $cyclePrice);
        $result = M('ttg_order_info')->where(
            array(
                'order_id' => $orderId, 
                'pay_status' => '1'))->save($data);
        if (! $result) {
            $this->error('价格修改失败，请检查订单是否存在或订单已支付');
        }
        $this->success('价格修改成功');
    }
    // 备货信息
    public function stockData() {
        $date = date('Ymd');
        $field = 'count(id) as goods_num,goods_name';
        $where = "delivery_status = '1' AND node_id = '{$this->node_id}'";
        $goodsName = I('goods_name', null);
        $isDownload = I('is_download', null);
        if (! empty($goodsName))
            $where .= " AND goods_name like '%{$goodsName}%'";
        $startTime = I('start_time', null);
        $endTime = I('end_time', null);
        if (! empty($startTime) || ! empty($endTime)) {
            if ($startTime)
                $where .= " AND dispatching_date >= '{$startTime}'";
            if ($endTime)
                $where .= " AND dispatching_date <= '{$endTime}'";
        } else {
            $where .= " AND dispatching_date = '{$date}'";
        }
        $cycleInfo = M('ttg_order_by_cycle')->field($field)
            ->where($where)
            ->group('goods_name')
            ->select();
        // 下载信息
        if ('1' === $isDownload) {
            $cols_arr = array(
                'goods_name' => '备货商品名称', 
                'goods_num' => '备货商品数量');
            querydata_download($cycleInfo, $cols_arr);
        } else {
            $this->assign('nowDate', $date);
            $this->assign('startTime', $startTime);
            $this->assign('endTime', $endTime);
            $this->assign('cycleInfo', $cycleInfo);
            $this->assign('post', $_REQUEST);
            $this->display(); // 输出模板
        }
    }

    public function batchHandleDelivery() {
        /**
         * 【多宝电商V3.0.6】会员订购商品-物流批量处理 Author: Zhaobl Task: #15212 Date:
         * 2015/11/23
         */
        import('ORG.Util.Page'); // 导入分页类
        
        $node_id = $this->node_id;
        // 为避免文件冲突,则根据各自的order_id创建目录
        if (! is_dir($this->storageDir)) {
            @mkdir($this->storageDir);
        }
        $path = $this->storageDir . '/' .
             $this->fileNameList['batchHandleOrderList']['realFileName'];
        // 若文件存在,则删掉.
        if (file_exists($path)) {
            unlink($path);
        }
        
        $startTime = I('param.start_time') ? I('param.start_time') : date('Ymd', 
            time());
        $endTime = I('param.end_time') ? I('param.end_time') : '';
        
        // 用来生成文件的数据
        $orderList = $this->TtgOrderByCycle->index($node_id, $startTime, 
            $endTime);
        $orderListShow = $this->orderListShowData($orderList);
        
        $orderListCount = count($orderListShow); // 总条数
                                                 
        // 实例化分页类 传入总记录数和每页显示的记录数
        $Page = new Page($orderListCount, 10);
        
        // 用来展示的分页数据
        $orderList = $this->TtgOrderByCycle->index($node_id, $startTime, 
            $endTime, $Page->firstRow, $Page->listRows);
        $orderListShowPageData = $this->orderListShowData($orderList);
        
        // 分页显示输出
        $show = $Page->show();
        
        $this->createBatchHandleOrderListFile($path, $orderListShow); // 生成CSV文件
        
        $expressResult = $this->TexpressInfoModel->getLastUsedExpress(); // 获取最近使用的快递公司
        
        $this->assign('usedExpress', $expressResult['rescent']);
        $this->assign('expressStr', $expressResult['expressStr']);
        
        $this->assign('page', $show);
        $this->assign('orderListShow', $orderListShowPageData);
        $this->assign('orderListCount', $orderListCount);
        $this->assign('showStartTime', $startTime);
        $this->assign('showEndTime', $endTime);
        $this->display();
    }

    public function orderListShowData($orderList) {
        /**
         * 寻找起始位置请在本页搜索单号:#15212
         */
        // 组装数据
        $orderListShow = array();
        $i = 0;
        foreach ($orderList as $k => $v) {
            $orderListShow[$i]['id'] = $v['id']; // id号
            $orderListShow[$i]['receiver_tel'] = $v['receiver_tel']; // 手机号
            $orderListShow[$i]['order_id'] = $v['order_id'] . '_' . $v['b_id']; // 订单号
            $orderListShow[$i]['delivery_time'] = $v['delivery_time'] ? date(
                'Y/m/d', strtotime($v['delivery_time'])) : '未配送'; // 快递单日期
            $b_name = $this->TtgOrderByCycle->getCommodityName($v['b_id']); // 取商品名
            $orderListShow[$i]['b_name'] = $b_name; // 商品
            $orderListShow[$i]['commodity_number'] = 1; // 数量
            $orderListShow[$i]['receiver_name'] = $v['receiver_name']; // 收货人
            $city = $this->TtgOrderInfoModel->getCity($v['receiver_citycode']); // 取省市区
            $orderListShow[$i]['receiver_addr'] = $city . $v['receiver_addr']; // 收货地址
            $orderListShow[$i]['delivery_company'] = $v['delivery_company']; // 物流公司
            $orderListShow[$i]['delivery_number'] = $v['delivery_number']; // 物流单号
            $i ++;
        }
        return $orderListShow;
    }

    public function createBatchHandleOrderListFile($path, $orderListShow) {
        /*
         * 寻找起始位置请在本页搜索单号:#15212
         */
        $fp = fopen($path, 'a+');
        
        $line = "编号,收货人手机号,订单号,快递单日期,商品名称,数量,收货人,收货地址,物流公司,物流单号";
        fputcsv($fp, explode(',', iconv('utf-8', 'gb2312', $line))); // 写入头行
        foreach ($orderListShow as $k => $v) {
            $line = $v['id'] . "\t" . ',' . $v['receiver_tel'] . "\t" . ',' .
                 $v['order_id'] . ',' . $v['delivery_time'] . "\t" . ',' .
                 $v['b_name'] . ',' . $v['commodity_number'] . ',' .
                 $v['receiver_name'] . ',' . $v['receiver_addr'] . ',' .
                 $v['delivery_company'] . ',' . 'yd:' . $v['delivery_number'];
            
            fputcsv($fp, explode(',', iconv('utf-8', 'gb2312', $line))); // 逐行写入
        }
        fclose($fp);
    }

    public function batchHandleWindow() {
        /*
         * 寻找起始位置请在本页搜索单号:#15212
         */
        // 批处理上传的弹窗页面
        $this->display();
    }

    public function batchUploadOrderList() {
        /*
         * 寻找起始位置请在本页搜索单号:#15212
         */
        // 上传走这里
        $node_id = $this->node_id;
        
        import('ORG.Net.UploadFile') or die('导入上传包失败');
        $upload = new UploadFile();
        $upload->savePath = $this->storageDir . '/'; // 上传文件保存路径
        $info = $upload->uploadOne($_FILES['staff']); // 调用上传方法生成信息
        $fileWay = $upload->savePath . $info[0]['savename']; // 获取生成文件路径
        
        if (strtolower($info[0]['extension']) != 'csv') {
            @unlink($fileWay);
            $this->error('文件类型不符合');
        }
        
        $data = fopen($fileWay, 'a+');
        
        $orderListAllNum = 0; // 记录订单条数
        $changeSucceedNum = 0; // 记录修改条数
        $express = $this->TtgOrderInfoModel->express(); // 取快递公司名单
        $errorOrderList = array(); // 定义失败列表
        
        while (! feof($data)) {
            
            $orderListLine = fgetcsv($data, 1000); // 循环读取
            $id = str_replace('	', '', $orderListLine[0]); // 去除csv带有的特殊字符
            $orderId = explode('_', $orderListLine[2]); // 订单号按下划线拆分成数组
            $order_id = $orderId[0]; // order_id
            $b_id = $orderId[1]; // b_id
                                 
            // 订单列必须为纯数字才视作数据处理
            if (is_numeric($order_id) && is_numeric($b_id) && is_numeric($id)) {
                $delivery_company = str_replace(' ', '', 
                    iconv('gb2312', 'utf-8', $orderListLine['8'])); // 快递公司
                $delivery_number = str_replace(' ', '', 
                    iconv('gb2312', 'utf-8', $orderListLine['9'])); // 快递单号
                
                if (strpos($delivery_number, ':') != 2) {
                    $orderListLine['10'] = "请不要删除'yd:'";
                    $errorOrderList[] = $orderListLine;
                } else {
                    $delivery_numbers = explode('yd:', $delivery_number);
                    $delivery_number = $delivery_numbers[1];
                    
                    if ($delivery_company == '' || $delivery_number == '') {
                        $orderListLine['10'] = "物流公司或物流单号未填写";
                        $errorOrderList[] = $orderListLine;
                    } else {
                        if (in_array($delivery_company, $express)) {
                            // 如果物流公司符合规定 则进来
                            $info = $this->TtgOrderByCycle->getInfo($id, 
                                $node_id, $order_id, $b_id);
                            if ($delivery_company == $info['delivery_company'] &&
                                 $delivery_number == $info['delivery_number']) {
                                
                                // 如果上传的物流公司与物流单号等于于从数据库取出的物流公司与物流单号 则认为重复提交
                                // 计算为成功条数
                                $changeSucceedNum ++;
                            } else {
                                // 如果不相等 则认为是新数据 走数据库
                                $result = $this->TtgOrderByCycle->orderByCycleSave($id, 
                                    $node_id, $order_id, $b_id, 
                                    $delivery_company, $delivery_number);
                                if (! $result) {
                                    // 如果数据库插入失败
                                    $orderListLine['10'] = "订单号错误";
                                    $errorOrderList[] = $orderListLine;
                                } else {
                                    $changeSucceedNum ++;
                                }
                            }
                        } else {
                            $orderListLine['10'] = "物流公司名称与系统不符，请查询《物流公司列表》";
                            $errorOrderList[] = $orderListLine;
                        }
                    }
                }
                $orderListAllNum ++;
            }
        }
        fclose($data);
        @unlink($fileWay);
        
        $this->assign('orderListNum', $orderListAllNum); // 总条数
                                                         // 如果总条数大于成功条数
        if ($orderListAllNum > $changeSucceedNum) {
            $this->batchHandleErrorOrderList($errorOrderList); // 调用失败列表方法
            $this->assign('status', 1);
            $this->assign('alterNum', $changeSucceedNum); // 成功数
        } else {
            // 若录入成功,则调用方法将目录删掉.
            $this->deldir($this->storageDir);
            $this->assign('status', 2);
        }
        $this->display('batchHandleResult');
    }

    public function batchHandleErrorOrderList($errorOrderList) {
        /*
         * 寻找起始位置请在本页搜索单号:#15212
         */
        
        // 订单更新失败的 不合格的 都往这扔
        $realFileName = $this->fileNameList['batchHandleFailureOrderList']['realFileName'];
        $path = $this->storageDir . '/' . $realFileName;
        
        if (file_exists($path)) {
            unlink($path);
        }
        $fp = fopen($path, 'a+');
        $line = "编号,收货人手机号,订单号,快递单日期,商品名称,数量,收货人,收货地址,物流公司,物流单号,失败原因";
        fputcsv($fp, explode(',', iconv('utf-8', 'gb2312', $line))); // 写入头行
        
        foreach ($errorOrderList as $v) {
            $valueLine = "";
            foreach ($v as $key => $value) {
                // 失败原因字段有点特殊 转码会消失 因此进行以下判断
                if ($key != 10) {
                    $valueLine .= $value . ",";
                    $line = iconv('gb2312', 'utf-8', $valueLine);
                } else {
                    $line .= $v[$key];
                }
            }
            fputcsv($fp, explode(',', iconv('utf-8', 'gb2312', $line)));
        }
        fclose($fp);
    }

    public function singleUpdate() {
        /*
         * 寻找起始位置请在本页搜索单号:#15212
         */
        // 单条更新
        $node_id = $this->node_id;
        $id = I('id', null);
        if (empty($id)) {
            $this->error('缺少id');
        }
        
        $orderIds = I('order_id', null);
        if (empty($orderIds)) {
            $this->error('缺少订单号');
        }
        
        $orderId = explode('_', $orderIds); // 订单号按下划线拆分成数组
        $order_id = $orderId[0]; // order_id
        $b_id = $orderId[1]; // b_id
                             
        // 获取物流公司、单号、时间
        $number = I('delivery_number', null);
        if (is_null($number)) {
            $this->error('缺少物流单号');
        }
        $company = I('delivery_company', null);
        if (is_null($company)) {
            $this->error('缺少物流公司');
        } else {
            $expressInfo = $this->TtgOrderByCycle->getExpressCompany($company);
            if (empty($expressInfo)) {
                $this->error(' 请填写正确的物流公司名称');
                exit();
            }
        }
        // 进行插入动作
        $result = $this->TtgOrderByCycle->orderByCycleSave($id, $node_id, $order_id, $b_id, 
            $company, $number);
        
        if (! $result) {
            // 如果数据库插入失败
            $this->error('更新失败');
        } else {
            $this->success('更新成功');
        }
    }

    public function bookOrderList() {
        $additionalCondition = array();
        if (I('order_id') != '') {
            $additionalCondition['tbo.order_id'] = I('post.order_id', '0', 
                'string');
        }
        
        if (I('order_phone') != '') {
            $additionalCondition['tbo.order_phone'] = I('post.order_phone', '0', 
                'string');
        }
        
        if (I('delivery_status') != '') {
            $additionalCondition['tbo.delivery_status'] = I(
                'post.delivery_status', '0', 'string');
        }
        
        if (I('start_time') != '' && I('end_time') !== '') {
            $startTime = I('post.start_time') . '000000';
            $endTime = I('post.end_time') . '235959';
            $additionalCondition['tbo.add_time'] = array(
                array(
                    'gt', 
                    $startTime), 
                array(
                    'lt', 
                    $endTime));
        }
        
        $this->assign('post', $_REQUEST);
        $additionalCondition['tbo.node_id'] = $this->nodeId;
        
        import('ORG.Util.Page');
        $count = M()->table("twfx_book_order tbo")->join(
            'twfx_saler tws ON tws.node_id = tbo.node_id AND tws.phone_no = tbo.order_phone')
            ->where($additionalCondition)
            ->count();
        $Page = new Page($count, 10);
        
        $orderList = M()->table("twfx_book_order tbo")->join(
            'twfx_saler tws ON tws.node_id = tbo.node_id AND tws.phone_no = tbo.order_phone')
            ->join(
            'twfx_book_order_info twboi ON twboi.book_order = tbo.order_id')
            ->join('tmarketing_info tmi ON tmi.id = twboi.marketing_info_id')
            ->group('tbo.order_id')
            ->where($additionalCondition)
            ->field(
            'tbo.order_id, tbo.delivery_status, tbo.add_time, tbo.receiver_name, tbo.receiver_phone,  tbo.order_phone, tws.name as tws_name, tmi.name')
            ->order('tbo.add_time DESC, tbo.order_phone ASC')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        foreach ($orderList as $key => $val) {
            $goodInfo = M('twfx_book_order_info')->where(
                array(
                    'book_order' => $val['order_id']))->select();
            $orderList[$key]['goodsInfo'] = $goodInfo;
        }
        $show = $Page->show();
        $this->assign('page', $show);
        $this->assign('orderList', $orderList);
        
        $texpressModel = D('TexpressInfo');
        $expressResult = $texpressModel->getLastUsedExpress();
        $this->assign('usedExpress', $expressResult['rescent']);
        $this->assign('expressStr', $expressResult['expressStr']);
        
        $this->display();
    }

    public function bookOrderExport() {
        $where = 'twbo.node_id = ' . $this->nodeId;
        if ($_POST['order_id'] != '') {
            $where .= ' AND twbo.order_id = ' . I('post.order_id');
        }
        if ($_POST['delivery_status'] != '') {
            $where .= ' AND twbo.delivery_status = ' . I('post.delivery_status');
        }
        if ($_POST['order_phone'] != '') {
            $where .= ' AND twbo.order_phone = ' . I('post.order_phone');
        }
        if ($_POST['start_time'] != '' && $_POST['end_time'] != '') {
            $where .= ' AND twbo.add_time > ' . $_POST['start_time'] .
                 '000000 AND twbo.add_time < ' . $_POST['end_time'] . '235959 ';
        }
        $fieldConArray = array(
            '1' => array(
                'col_name' => 'order_id', 
                'col_str' => '订单号', 
                'col_sel' => "twbo.order_id"), 
            '2' => array(
                'col_name' => 'add_time', 
                'col_str' => '下单时间', 
                'col_sel' => "twbo.add_time"), 
            '3' => array(
                'col_name' => 'order_phone', 
                'col_str' => '下单手机号', 
                'col_sel' => "twbo.order_phone"), 
            '5' => array(
                'col_name' => 'batch_name', 
                'col_str' => '商品名称', 
                'col_sel' => "tmi.name as batch_name"), 
            '8' => array(
                'col_name' => 'delivery_company', 
                'col_str' => '快递公司', 
                'col_sel' => "twbo.delivery_company"), 
            '9' => array(
                'col_name' => 'delivery_number', 
                'col_str' => '物流单号', 
                'col_sel' => "twbo.delivery_number"), 
            '10' => array(
                'col_name' => 'delivery_date', 
                'col_str' => '配送时间', 
                'col_sel' => "twbo.delivery_date"), 
            '11' => array(
                'col_name' => 'receiver_name', 
                'col_str' => '收货人姓名', 
                'col_sel' => "twbo.receiver_name"), 
            '12' => array(
                'col_name' => 'receiver_phone', 
                'col_str' => '收货人手机号', 
                'col_sel' => "twbo.receiver_phone"), 
            '13' => array(
                'col_name' => 'receiver_addr', 
                'col_str' => '收货地址', 
                'col_sel' => "concat(ifnull(tcc.province,''), ifnull(tcc.city,''), ifnull(tcc.town,''), twbo.receiver_addr) as receiver_addr"));
        
        $field = '';
        $nameArray = array();
        $fieldData = htmlspecialchars_decode(I('col_list'));
        $fieldData = explode("&", $fieldData);
        
        foreach ($fieldData as $k => $v) {
            $tempFieldArray = explode("=", $v);
            if ($tempFieldArray[1] == '1' && $tempFieldArray[0] != '5') {
                $field .= $fieldConArray[$tempFieldArray[0]]['col_sel'] . ',';
                $nameArray[$fieldConArray[$tempFieldArray[0]]['col_name']] = $fieldConArray[$tempFieldArray[0]]['col_str'];
            } elseif ($tempFieldArray[1] == '1' && $tempFieldArray[0] == '5') {
                $field .= " (SELECT GROUP_CONCAT(CONCAT(tmi.name,ifnull(twboi.sku_desc,'')) SEPARATOR '+') as goods from twfx_book_order_info twboi LEFT JOIN tmarketing_info tmi ON tmi.id = twboi.marketing_info_id  WHERE twbo.order_id = twboi.book_order)  as batch_name,";
                $nameArray['batch_name'] = '商品名称';
            }
        }
        
        $field = substr($field, 0, strlen($field) - 1);
        
        $sql = 'SELECT ' . $field . " FROM twfx_book_order twbo 
                LEFT JOIN tcity_code tcc ON tcc.path = twbo.receiver_citycode 
                WHERE {$where}";
        if (querydata_download($sql, $nameArray, M()) == false) {
            $this->error('下载失败');
        }
    }
    public function selectProvince(){
        $province=I("province",'');
        $map=array();
        $map['ti.node_id']=$this->node_id;
        $map['tps.status']=1;
        if($province){
            $map['ti.province']=$province;
        }
        $stores=M()->table("tfb_adb_store_page tasp")->field("ti.store_id,ti.store_name")->join("tstore_info ti on tasp.store_id=ti.store_id")->join("tecshop_page_sort tps on tps.id=tasp.page_id")->where($map)->select();
        echo json_encode($stores);
    }

}
