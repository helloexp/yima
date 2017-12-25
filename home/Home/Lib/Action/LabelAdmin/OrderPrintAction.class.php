<?php

/**
 * 订单打印 商品销售、码上买
 *
 * @author bao
 */
class OrderPrintAction extends BaseAction {

    public function _initialize() {
        parent::_initialize();
        $this->TexpressInfoModel = D('TexpressInfo');
    }

    public function index() {
        $orderId = I('get.order_id');
        
        $model = M('ttg_order_info');
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
            "o.*,g.group_goods_name,
                    COUNT(cy.id) as cycle_num,
                    IFNULL(SUM(case cy.delivery_status WHEN '3' THEN 1 END),0) as send_count
                    ");
        $orderInfo = M()->table('ttg_order_info o')
            ->field($field)
            ->join("tmarketing_info g ON o.batch_no=g.id")
            ->join("ttg_order_by_cycle cy ON cy.order_id = o.order_id")
            ->where(array(
            'o.order_id' => $orderId))
            ->find();
        // 获取城市信息
        $orderInfo = D('StoreOrder')->getOrderAdress($orderInfo);
        $orderInfoExList = M('ttg_order_info_ex')->where($map)->select();
        $skuId = 0;
        $bId = 0;
        $countPrice = 0;
        foreach ($orderInfoExList as $val) {
            if (isset($val['ecshop_sku_id'])) {
                $skuId = $val['ecshop_sku_id'];
            }
            $bId = $val['b_id'];
            $countPrice = $countPrice + $val['amount'];
        }
        // 订购信息
        $cycleExInfo = '';
        $cycleType = '';
        if ($orderInfo['other_type'] == '1') {
            $tmpAarray = M()->table("tbatch_info b")->join(
                'tgoods_info g on g.goods_id = b.goods_id')
                ->field('g.config_data')
                ->where(array(
                'b.id' => $bId))
                ->find();
            $tmpAarray = json_decode($tmpAarray['config_data'], true);
            $cycleType = $tmpAarray['cycle'];
            $where = "order_id like '{$orderId}'";
            $cycleExInfo = M('ttg_order_by_cycle')->where($where)
                ->order('delivery_time desc')
                ->limit(1)
                ->find();
        }
        if ($orderInfo['is_gift'] == '1')
            $giftInfo = M('torder_trace')->where($map)->select();
        $hav_count = count($giftInfo);
        // 红包数据
        $bonusInfo = M('tbonus_use_detail')->where(
            array(
                'order_id' => $orderId))->getField('bonus_amount');
        if (! $bonusInfo)
            $bonusInfo = 0;
        $receiverType = array(
            '0' => ' 凭证自提订单', 
            '1' => '物流订单');
        $payChannel = array(
            '1' => '支付宝', 
            '2' => '银联', 
            '3' => '微信支付', 
            '4' => '货到付款');
        $orderInfo=$this->adbGetOrderInfo($orderInfo);
        $this->assign('payChannel', $payChannel);
        $this->assign('receiverType', $receiverType);
        $this->assign('orderInfo', $orderInfo);
        $this->assign('skuId', $skuId);
        $this->assign('orderInfoExList', $orderInfoExList);
        $this->assign('cycleExInfo', $cycleExInfo); // 订购配送物流信息
        $this->assign('cycleType', $cycleType); // 订购配送物流信息
        $this->assign('orderStatus', $orderStatus);
        $this->assign('deliveryStatus', $deliveryStatus);
        $this->assign('bonusInfo', $bonusInfo);
        $this->assign('goodsCount', count($orderInfoExList)); // 商品总数
        $this->assign('countPrice', $countPrice); // 商品总价
        $this->assign('giftInfo', $giftInfo);
        $this->assign('hav_count', $hav_count);
        $this->display(); // 输出模板
    }
    // 订购信息展示
    public function cycleIndex() {
        $orderId = I('get.order_id');
        
        $model = M('ttg_order_info');
        $map = array(
            'o.order_id' => $orderId);
        
        $field = array(
            "o.*,bi.batch_name,SUBSTRING_INDEX(ex.ecshop_sku_desc,'/',-1) as cycle_info,SUBSTRING_INDEX(ex.ecshop_sku_desc,'/',1) as sku_info");
        $orderInfo = M()->table('ttg_order_info o')
            ->field($field)
            ->join("ttg_order_info_ex ex ON ex.order_id=o.order_id")
            ->join("tbatch_info bi ON bi.id = ex.b_id")
            ->where($map)
            ->find();
        
        // 获取城市信息
        if (! session('cityInfo')) {
            $cityInfo = M('tcity_code')->field(
                'CONCAT(province,city,town) as city_info, path')
                ->where('town is NOT NULL')
                ->order('id')
                ->select();
        }
        
        $orderInfoExList = M('ttg_order_by_cycle')->where(
            array(
                'order_id' => $orderId))->select();
        $countPrice = 0;
        foreach ($orderInfoExList as $val) {
            $countPrice = $countPrice + $val['amount'];
        }
        $expressResult = $this->TexpressInfoModel->getLastUsedExpress(); // 获取最近使用的快递公司
                                                                         // 红包数据
        $bonusInfo = M('tbonus_use_detail')->where(
            array(
                'order_id' => $orderId))->getField('bonus_amount');
        if (! $bonusInfo)
            $bonusInfo = 0;
        
        $this->assign('usedExpress', $expressResult['rescent']);
        $this->assign('expressStr', $expressResult['expressStr']);
        $this->assign('goodsCount', count($orderInfoExList)); // 商品总数
        $this->assign('bonusInfo', $bonusInfo);
        $this->assign('countPrice', $countPrice); // 商品总价
        $this->assign('orderInfo', $orderInfo);
        $this->assign('orderInfoExList', $orderInfoExList);
        $this->display(); // 输出模板
    }

    /**
     * 分销订货订单显示
     */
    public function wfxBookOrderDetail() {
        $result = array();
        $orderId = I('get.order_id', '0', 'string');
        $orderDetail = M()->table("twfx_book_order twbo")->join(
            'twfx_book_order_info twboi ON twboi.book_order = twbo.order_id')
            ->join('tcity_code tcc ON tcc.path = twbo.receiver_citycode')
            ->join('tmarketing_info tmi ON tmi.id = twboi.marketing_info_id')
            ->where(array(
            'twbo.order_id' => $orderId))
            ->field(
            'twbo.*, twboi.sku_desc, twboi.count, twboi.amount, tcc.province, tcc.city, tcc.town, tmi.name')
            ->select();
        
        $totalPrice = 0;
        foreach ($orderDetail as $val) {
            $tempArray = array();
            $tempArray['sku_desc'] = $val['sku_desc'];
            $tempArray['count'] = $val['count'];
            $tempArray['amount'] = $val['amount'];
            $tempArray['name'] = $val['name'];
            $totalPrice += $val['amount'];
            if (empty($result)) {
                $result = $val;
                $result['receiver_addr'] = $val['province'] . $val['city'] .
                     $val['town'] . $val['receiver_addr'];
            }
            $result['goodsInfo'][] = $tempArray;
        }
        $result['total_price'] = $totalPrice;
        
        $this->assign('orderDetail', $result);
        $this->display();
    }

    /**
     * 获取爱蒂宝订单信息
     * @param  [type] $info [description]
     * @return [type]       [description]
     */
    private function adbGetOrderInfo($info){
        if($info['node_id'] != C('adb.node_id')){
            return $info;
        }
        $where['order_id']=$info['order_id'];
        $adb_info=M('tfb_adb_ecshop_order')->where($where)->find();
        if($adb_info){
            $info=array_merge($adb_info,$info);
        }
        return $info;
    }
}