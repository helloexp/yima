<?php

class MyOrderInfoModel extends BaseModel {
    protected $tableName = '__NONE__';
    public function OrderInfoShow($order_id) {
        $model = M('ttg_order_info');
        $result = $model->where(
            array(
                'order_id' => $order_id))->find();
        
        $arr = array(
            1 => '未支付', 
            2 => '已支付', 
            3 => '货到付款');
        foreach ($arr as $k => $v) {
            if ($result['pay_status'] == $k) {
                $result['pay_status'] = $v;
            }
        }
        $arr = array(
            1 => '待配送', 
            2 => '配送中', 
            3 => '已配送', 
            4 => '发码自提');
        foreach ($arr as $k => $v) {
            if ($result['delivery_status'] == $k) {
                $result['delivery_status'] = $v;
            }
        }
        // 格式化价格
        $result['bonus_use_amt'] = number_format($result['bonus_use_amt'], 2);
        $result['order_amt'] = number_format($result['order_amt'], 2);
        return $result;
    }

    public function OrderInfo_ex($order_id) {
        $model = M("ttg_order_info_ex");
        $result = $model->alias("a")->join('tbatch_info b on a.b_id = b.id')
            ->where(array(
            'order_id' => $order_id))
            ->field('a.*,b.batch_amt,b.batch_img,b.node_id')
            ->select();
        return $result;
    }
}