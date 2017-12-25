<?php

/**
 * Use: g=LabelAdmin&m=OrderList&a=batchHandleDelivery Author: Zhaobl Date:
 * 2015/11/23
 */
class TtgOrderByCycleModel extends Model {

    public function index($node_id, $startTime, $endTime, $limitStart = '', 
        $limitEnd = '') {
        $endTime = $endTime ? $endTime : $startTime;
        if ($startTime != '') {
            $data['dispatching_date'] = array(
                'between', 
                "$startTime,$endTime");
        } elseif ($startTime == '' && $endTime != '') {
            $data['dispatching_date'] = array(
                'ELT', 
                $endTime);
        }
        $data['node_id'] = $node_id;
        $data['delivery_status'] = 1;
        $model = M('ttg_order_by_cycle');
        
        if ($limitStart == '' && $limitEnd == '') {
            $orderList = $model->where($data)->select();
        } else {
            $orderList = $model->where($data)
                ->limit($limitStart, $limitEnd)
                ->select();
        }
        
        return $orderList;
    }

    public function getCommodityName($id, $filed = 'batch_name') {
        $model = M('tbatch_info');
        $info = $model->where(array(
            'id' => $id))->find();
        return isset($info[$filed]) ? $info[$filed] : '';
    }

    public function getInfo($id, $node_id, $order_id, $b_id) {
        $model = M('ttg_order_by_cycle');
        $data['id'] = $id;
        $data['node_id'] = $node_id;
        $data['order_id'] = $order_id;
        $data['b_id'] = $b_id;
        $result = $model->where($data)->find();
        return $result;
    }

    public function orderByCycleSave($id, $node_id, $order_id, $b_id = null, $delivery_company, 
        $delivery_number) {
        $model = M('ttg_order_by_cycle');
        $data['id'] = $id;
        $data['node_id'] = $node_id;
        $data['order_id'] = $order_id;
        if (null !== $b_id)
            $data['b_id'] = $b_id;
        $model->delivery_company = $delivery_company;
        $model->delivery_number = $delivery_number;
        $model->delivery_time = date('YmdHis', time());
        $model->delivery_status = 3;
        $result = $model->where($data)->save();
        return $result;
    }

    public function getExpressCompany($company) {
        $model = M('Texpress_info');
        $data['express_name'] = $company;
        $result = $model->where($data)->find();
        return $result;
    }
}