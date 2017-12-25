<?php

/**
 * Use: g=CronJob&m=CronMail&a=mailInform; Author: Zhaobl Date: 2015/10/20
 */
class TonlineGetOrderModel extends BaseModel {

    function index($delivery_node_id) {
        $data['delivery_node_id'] = $delivery_node_id;
        $data['order_status'] = 0;
        $model = M('tonline_get_order');
        $result = $model->where($data)->find();
        
        $status = $result ? 1 : 0;
        return $status;
    }
}