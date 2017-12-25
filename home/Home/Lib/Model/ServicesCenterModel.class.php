<?php

/**
 * 商户业务中心管理
 *
 * @author bao
 */
class ServicesCenterModel extends Model {

    protected $tableName = 'tmarketing_info';

    public function _initialize() {
        import('@.Vendor.CommonConst') or die('include file fail.');
    }

    /**
     * 获取当前用户的所有服务
     *
     * @param int $node_id 机构ID
     * @param array $argument 查询条件
     * @param bool $isPage 是否仅查分页总记录数
     * @param int $firstRow limit查询的起始条件
     * @param int $listRows limit查询的起始条件
     * @return Mixed
     * @author wang pan
     */
    public function getindent($node_id, $argument, $isPage, $firstRow, $listRows) {
        
        // 拼凑查询条件
        $where = array(
            'i.node_id' => $node_id, 
            'o.pay_status' => '1');
        switch (count($argument)) {
            case '1':
                $where['i.name'] = array(
                    'like', 
                    '%' . $argument['name'] . '%');
                break;
            case '2':
                $where['o.add_time'] = array(
                    'between', 
                    array(
                        $argument['badd_time'] . '000000', 
                        $argument['eadd_time'] . '000000'));
                break;
            case '3':
                $where['i.name'] = array(
                    'like', 
                    '%' . $argument['name'] . '%');
                $where['o.add_time'] = array(
                    'between', 
                    array(
                        $argument['badd_time'] . '000000', 
                        $argument['eadd_time'] . '000000'));
                break;
            default:
                // $where;
                break;
        }
        
        // 连表
        $join = ' i inner join tactivity_order o on i.id=o.m_id';
        
        // 拿到总页数
        if ($isPage) {
            return $count = $this->join($join)
                ->where($where)
                ->count();
        }
        
        // 取到当前用户的所有订单
        $ServiceInfo = $this->field(
            'i.id,i.name,i.batch_type,o.amount,o.add_time,o.pay_way')
            ->join($join)
            ->where($where)
            ->limit($firstRow . ',' . $listRows)
            ->order('o.add_time desc')
            ->select();
        
        foreach ($ServiceInfo as $key => $value) {
            if ($value['pay_way'] == '1') {
                $ServiceInfo[$key]['pay_way'] = '余额付款';
            } else {
                $ServiceInfo[$key]['pay_way'] = '现充现付';
            }
        }
        
        // 活动类型
        $batch_type_name = C('BATCH_TYPE_NAME');
        foreach ($ServiceInfo as $key => $value) {
            $ServiceInfo[$key]['batch_type'] = $batch_type_name[$value['batch_type']];
        }
        
        return $ServiceInfo;
    }

    /**
     * 查询指定服务的信息
     *
     * @param int $node_id 机构ID
     * @param int $serviceId 活动ID
     * @return string(JSON数据) | bool
     */
    public function servicedetails($node_id, $serviceId) {
        $details = $this->field(
            'i.start_time,i.end_time,i.batch_type,o.detail,o.amount')
            ->join(' i inner join tactivity_order  o on o.m_id=i.id')
            ->where(
            array(
                'i.node_id' => $node_id, 
                'm_id' => $serviceId))
            ->find();
        
        if ($details) {
            $batch_type_name = C('BATCH_TYPE_NAME'); // 获取所有活动类型名
            $details['batch_type'] = $batch_type_name[$details['batch_type']];
            
            $start_time = date("Y-m-d", strtotime($details['start_time']));
            $end_time = date("Y-m-d", strtotime($details['end_time']));
            
            $detailInfo = isset($details['detail']) ? $details['detail'] : '';
            $amount = isset($details['amount']) ? $details['amount'] : 0.00;
            $detailInfo = json_decode($detailInfo, true);
            $detailInfo['start_time'] = $start_time;
            $detailInfo['end_time'] = $end_time;
            $detailInfo['act_price'] = $amount;
            
            return json_encode($detailInfo);
        } else {
            return false;
        }
    }

    /**
     *
     * @param $nodeId 机构号
     * @param $orderType 订单类型:1活动订单3申请终端订单
     * @param $beginTime 搜索的开始时间（支付时间）时间戳
     * @param $endTime 搜索的结束时间（支付时间）时间戳
     * @param string $keyword 关键词
     * @param $payStatus 支付状态 0未支付1已支付
     * @param $limit 数据的起始和结束的位置,(当false 返回记录条数)
     * @return array 订单列表数据| int(当$limit = false 返回记录条数)
     */
    public function getMyOrder($nodeId, $orderType, $beginTime, $endTime, 
        $keyword, $payStatus, $limit = false) {
        $map['node_id'] = $nodeId;
        $map['order_type'] = $orderType;
        $timeMap = array();
        if ($beginTime) {
            $timeMap[] = array(
                'egt', 
                $beginTime);
        }
        if ($endTime) {
            $timeMap[] = array(
                'elt', 
                $endTime);
        }
        if (! empty($timeMap)) {
            $map['unix_timestamp(pay_time)'] = $timeMap;
        }
        if ($keyword) {
            $jsonKeyword = json_encode($keyword); // 为了使他转成unicode
            $jsonKeyword = str_replace("\\", '_', $jsonKeyword);
            $jsonKeyword = trim($jsonKeyword, "\"");
            $where['detail'] = array(
                'like', 
                array(
                    '%' . $keyword . '%', 
                    '%' . $jsonKeyword . '%'), 
                'or');
            $where['order_number'] = array(
                'like', 
                '%' . $keyword . '%');
            $where['amount'] = array(
                'like', 
                '%' . $keyword . '%');
            $where['_logic'] = 'or';
            $map['_complex'] = $where;
        }
        if ($payStatus != '') {
            $map['pay_status'] = $payStatus;
            if ($payStatus == '0') {
                // 因为3也是待支付，我也是醉了
                $map['pay_status'] = array(
                    'in', 
                    array(
                        '0', 
                        '3'));
            }
        }
        $orderModel = M('tactivity_order');
        if (false === $limit) {
            return $orderModel->where($map)->count();
        }
        $orderList = $orderModel->field(
            array(
                'id', 
                'order_number', 
                "case when pay_time = '0000-00-00 00:00:00' then '' else pay_time end as pay_time", 
                'order_type', 
                "case when order_type = 1 then '开通多乐互动' when order_type = 5 then '在线签约' when order_type = 6 then '多米收单' when order_type = 3 then '申请终端' else '' end as order_type_name", 
                'pay_status', 
                "case when pay_status = 0 then '待支付' when pay_status = 1 then '已支付' when pay_status = 2 then '已取消' when pay_status = 3 then '待支付' else '' end as pay_status_name", 
                'amount'))
            ->where($map)
            ->limit($limit)
            ->order('id desc')
            ->select();
        return $orderList;
    }

    /**
     *
     * @param $nodeId 机构号
     * @param $orderId 订单id
     * @return array('template' => 模板路径, 'data' => 数据)
     */
    public function getOrderDetail($nodeId, $orderId) {
        $orderModel = M('tactivity_order');
        $order = $orderModel->where(
            array(
                'node_id' => $nodeId, 
                'id' => $orderId))->find();
        if (! $order) {
            throw_exception('没有找到该订单');
        }
        switch ($order['order_type']) {
            // 大转盘通用订单,以后很多活动貌似都是这个模型
            case CommonConst::ORDER_TYPE_WHEEL_NORMAL:
                $detail = $this->getWheelOrderDetail($order, $nodeId);
                break;
            case CommonConst::ORDER_TYPE_ONLINE_TREATY:
                $detail = $this->getOnlineTreatyOrderDetail($order, $nodeId);
                break;
            case CommonConst::ORDER_TYPE_APPLY_POS:
                $detail = $this->getPosOrderDetail($order, $nodeId);
                break;
            case CommonConst::ORDER_TYPE_DM:
                $detail = $this->getDMOrderDetail($order, $nodeId);
                break;
        }
        return $detail;
    }

    /**
     *
     * @param $order 订单array
     * @return array('template' => 模板路径, 'data' => 数据)
     */
    protected function getWheelOrderDetail($order, $nodeId) {
        $mInfo = M('tmarketing_info')->where(
            array(
                'id' => $order['m_id']))
            ->field(
            array(
                'name', 
                'start_time', 
                'end_time', 
                'batch_type'))
            ->find();
        $orderInfo = D('BindChannel')->getOrderInfo($order['id'], $nodeId);
        $orderInfo['act_start_time'] = date('Y-m-d', 
            strtotime($mInfo['start_time']));
        $orderInfo['act_end_time'] = date('Y-m-d', 
            strtotime($mInfo['end_time']));
        $batchTypeNameArr = C('BATCH_TYPE_NAME');
        $data = array(
            'orderInfo' => $order, 
            'orderNumber' => $order['order_number'], 
            'orderTypeName' => $batchTypeNameArr[$mInfo['batch_type']], 
            'ret_detail' => $order['ret_detail'] ? json_decode(
                $order['ret_detail'], true) : '', 
            'add_time' => date('Y-m-d H:i:s', strtotime($order['add_time'])), 
            'payTime' => $order['pay_time'] == '0000-00-00 00:00:00' ? '' : date(
                'Y-m-d H:i:s', strtotime($order['pay_time'])), 
            'activityName' => $mInfo['name']);
        $data = array_merge($orderInfo, $data);
        $data['couponDetailLength'] = count(
            $orderInfo['detail']['couponDetail']);
        $result = array(
            // 模板路径
            'template' => 'myOrderDetailWheel', 
            'data' => $data);
        return $result;
    }

    /**
     *
     * @param $order 订单array
     * @return array('template' => 模板路径, 'data' => 数据)
     */
    protected function getOnlineTreatyOrderDetail($order, $nodeId) {
        $detail = json_decode($order['detail'], true);
        $data = array(
            'orderTypeName' => '在线签约', 
            'orderInfo' => $order, 
            'orderDetail' => $detail, 
            'ret_detail' => $order['ret_detail'] ? json_decode(
                $order['ret_detail'], true) : '', 
            'add_time' => date('Y-m-d H:i:s', strtotime($order['add_time'])), 
            'payTime' => $order['pay_time'] == '0000-00-00 00:00:00' ? '' : date(
                'Y-m-d H:i:s', strtotime($order['pay_time'])));
        $result = array(
            // 模板路径
            'template' => 'myOrderDetailOnlineTreaty', 
            'data' => $data);
        return $result;
    }

    /**
     *
     * @param $order 订单array
     * @return array('template' => 模板路径, 'data' => 数据)
     */
    protected function getPosOrderDetail($order, $nodeId) {
        $detail = json_decode($order['detail'], true);
        $data = array(
            'orderTypeName' => '申请终端', 
            'orderInfo' => $order, 
            'orderDetail' => $detail, 
            'ret_detail' => $order['ret_detail'] ? json_decode(
                $order['ret_detail'], true) : '', 
            'add_time' => date('Y-m-d H:i:s', strtotime($order['add_time'])), 
            'payTime' => $order['pay_time'] == '0000-00-00 00:00:00' ? '' : date(
                'Y-m-d H:i:s', strtotime($order['pay_time'])));
        if ($data['orderDetail']['buyerInfo']) {
            // 处理地址信息
            $buyerInfo = $data['orderDetail']['buyerInfo'];
            $preAddr = M('tcity_code')->field(
                'concat(province," ",city," ",town," ") as city')
                ->where(
                array(
                    'path' => $buyerInfo['province_code'] .
                         $buyerInfo['city_code'] . $buyerInfo['town_code']))
                ->find();
            $data['cityInfo'] = $preAddr['city'] . $buyerInfo['address_more'];
            $data['buyer_name'] = $buyerInfo['buyer_name'];
            $data['buyer_phone'] = $buyerInfo['buyer_phone'];
            $data['cityInfo'] = $preAddr['city'];
            $data['address_more'] = $buyerInfo['address_more'];
            if ($order['delivery_info']) {
                $data['delivery_info'] = json_decode($order['delivery_info'], 
                    true);
            }
        }
        $result = array(
            // 模板路径
            'template' => 'myOrderDetailPos', 
            'data' => $data);
        return $result;
    }
    /**
     *
     * @param $order 订单array
     * @return array('template' => 模板路径, 'data' => 数据)
     */
    protected function getDMOrderDetail($order, $nodeId) {
        $detail = json_decode($order['detail'], true);
        $data = array(
            'orderTypeName' => '多米收单', 
            'orderInfo' => $order, 
            'orderDetail' => $detail, 
            'ret_detail' => $order['ret_detail'] ? json_decode(
                $order['ret_detail'], true) : '', 
            'add_time' => date('Y-m-d H:i:s', strtotime($order['add_time'])), 
            'payTime' => $order['pay_time'] == '0000-00-00 00:00:00' ? '' : date(
                'Y-m-d H:i:s', strtotime($order['pay_time'])));
        $result = array(
            // 模板路径
            'template' => 'myOrderDetailDM', 
            'data' => $data);
        return $result;
    }
}