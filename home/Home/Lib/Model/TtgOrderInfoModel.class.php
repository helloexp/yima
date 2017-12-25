<?php

/**
 * Use: g=LabelAdmin&m=OrderList&a=orderListIndex Author: Zhaobl Date:
 * 2015/10/27
 */
class TtgOrderInfoModel extends Model {

    public function index($node_id, $startTime, $endTime, $deliveryStatus='',$customerNo='') {
        if ($startTime != '' && $endTime == '') {
            $data['o.add_time'] = array(
                'gt', 
                $startTime . '000000');
        } elseif ($startTime == '' && $endTime != '') {
            $data['o.add_time'] = array(
                'lt', 
                $endTime . '000000');
        } elseif ($startTime != '' && $endTime != '') {
            $data['o.add_time'] = array(
                'between', 
                "$startTime.'000000',$endTime.'000000'");
        }
        
        $screen['_string'] = 'o.pay_status = 1 AND o.pay_channel = 4';
        $screen['o.pay_status'] = array(
            'EQ', 
            2);
        $screen['_logic'] = 'or';
        $data['_complex'] = $screen;

        //选择来源渠道的功能 注释
        /*if ($source != '') {
            $condition['name'] = $source;
            $rows = M()->table('ttg_order_info o')
                ->join("tbatch_channel b ON b.id=o.from_channel_id")
                ->join("tchannel c ON c.id=b.channel_id")
                ->where($condition)
                ->getField('order_id', true);
            
            $model = M('ttg_order_info');
            $orderList = array();
            foreach ($rows as $k) {
                
                $data['order_id'] = $k;
                $data['node_id'] = $node_id;
                $data['receiver_type'] = 1;
                $data['delivery_status'] = 1;
                $data['order_status'] = 0;
                
                $result = $model->where($data)->select();
                if ($result != '') {
                    $orderList[] = $result[0];
                }
            }
            return $orderList;
        }*/

        //1未配送/2已配送/3全部
        switch($deliveryStatus){
            case 1:
                $data['o.delivery_status'] = '1';
                break;
            case 2:
                $data['o.delivery_status'] =array(array('eq','2'),array('eq','3'),'or');
                break;
            default:
                break;
        }



        $data['o.node_id'] = $node_id;
        $data['o.receiver_type'] = '1';
        $data['o.order_status'] = '0';
        
        $model = M('ttg_order_info');

        //商品货号
        //SELECT o.order_id,o.delivery_status,e.b_id,b.goods_id,g.customer_no FROM ttg_order_info o
        //INNER JOIN ttg_order_info_ex e ON e.order_id=o.order_id
        //INNER JOIN tbatch_info b ON b.id=e.b_id
        //INNER JOIN tgoods_info g ON g.goods_id=b.goods_id
        //WHERE o.node_id='00029350' AND g.customer_no='i365435354'
        if($customerNo != ''){
            $data['g.customer_no'] = $customerNo;
            $orderList = $model->alias('o')
                    ->join('INNER JOIN ttg_order_info_ex e ON e.order_id=o.order_id')
                    ->join('INNER JOIN tbatch_info b ON b.id=e.b_id')
                    ->join('INNER JOIN tgoods_info g ON g.goods_id=b.goods_id')
                    ->where($data)
                    ->order('o.add_time')
                    ->select();
        }else{
            $orderList = $model->alias('o')->where($data)
                    ->order('o.add_time')
                    ->select();
        }


        return $orderList;
    }

    public function getTimeCount($node_id) {
        $data['node_id'] = $node_id;
        $data['receiver_type'] = 1;
        $data['delivery_status'] = 1;
        $data['pay_status'] = array(
            array(
                'EQ', 
                2), 
            array(
                'EQ', 
                3), 
            'or');
        $data['order_status'] = 0;
        $model = M('ttg_order_info');
        $orderList = $model->where($data)
            ->order('add_time')
            ->select();
        return $orderList;
    }

    /**
     * @param $order_id
     *
     * @return mixed 根据订单号获取渠道来源
     */
    public function getSource($order_id) {
        $data['order_id'] = $order_id;
        $showSource = M()->table('ttg_order_info o')
            ->join("tbatch_channel b ON b.id=o.from_channel_id")
            ->join("tchannel c ON c.id=b.channel_id")
            ->where($data)
            ->getField('name', true);
        
        foreach ($showSource as $v) {
            if ($v != '') {
                return $v;
            }
        }
    }

    /**
     *
     * @author zhaobaolin
     * @param $orderId
     * @param string $filed
     *
     * @return mixed
     */
    public function getOrderInfoField($orderId, $filed = 'b_name') {
        $model = M('ttg_order_info_ex');
        $info = $model->where(array(
            'order_id' => $orderId))->find();
        return isset($info[$filed]) ? $info[$filed] : '';
    }

    /**
     * 获得city信息
     *
     * @author zhaobaolin
     * @param $receiverCityCode
     * @return string
     */
    public function getCity($receiverCityCode) {
        $model = M('tcity_code');
        $city = $model->where(
            array(
                'path' => $receiverCityCode))->find();
        if ($city) {
            $cityStr = $city['province'] . $city['city'] . $city['town'];
        } else {
            log_write('$city is null');
            $cityStr = '';
        }
        
        return $cityStr;
    }

    /**
     * @param $node_id
     * @param $order_id
     * @param $delivery_company
     * @param $delivery_number
     *
     * @return bool 修改运单信息
     */
    public function orderSave($node_id, $order_id, $delivery_company, $delivery_number) {
        $model = M('ttg_order_info');
        $data['node_id'] = $node_id;
        $data['order_id'] = $order_id;
        $model->delivery_company = $delivery_company;
        $model->delivery_number = number_format($delivery_number, 0, '.', '');
        $model->delivery_status = 3;
        $model->delivery_date = date('YmdHis', time());
        $result = $model->where($data)->save();
        return $result;
    }

    /**
     * @return mixed 获取所有快递公司名称
     */
    public function express() {
        $model = M('texpress_info');
        $express = $model->getField('express_name', true);
        return $express;
    }

    /**
     * @param $node_id
     *
     * @return mixed 获取该商户的所有订单
     */
    public function allOrderList($node_id) {
        $model = M('ttg_order_info');
        $result = $model->where(array(
            'node_id' => $node_id))->select();
        if(!$result){
            log_write('function allOrderList , $result is null');
        }
        return $result;
    }

    /**
     * @param $order_id
     * @param $node_id
     *
     * @return mixed 根据订单号和商户号获取订单信息
     */
    public function getInfo($order_id,$node_id) {
        $model = M('ttg_order_info');
        $result = $model->where(
            array(
                'order_id' => $order_id,'node_id' => $node_id))->select();
        if(!$result){
            log_write('function getInfo , $order_id is',$order_id,' $node_id is',$node_id,'$result is null');
        }
        return $result;
    }
}