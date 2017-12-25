<?php

/*
 * 这儿封装O2O短信使用函数 包括发送短信，将短信存入数据库等
 */
class SendNodeService {

    public function __construct() {
    }

    /**
     * Description of SkuService 取得当前用户订单配置信息
     *
     * @param
     *
     * @return sting $orderConfig 订单节点信息
     * @author john_zeng
     */
    public function getNodeConfig() {
        $orderConfig = M('tsystem_param')->where(
            array(
                'param_name' => 'SENDNOTE_ORDERID'))->getField('param_value');
        return $orderConfig;
    }

    /**
     * Description of SkuService 更新订单ID
     *
     * @param
     *
     * @return sting $orderConfig 订单节点信息
     * @author john_zeng
     */
    public function updateNodeConfig($orderId) {
        $orderConfig = M('tsystem_param')->where(
            array(
                'param_name' => 'SENDNOTE_ORDERID'))->save(
            array(
                'param_value' => $orderId));
        return $orderConfig;
    }

    /**
     * Description of SkuService 处理订单信息
     *
     * @param string $startTime 订单开始时间 string $orderId 订单节点时间信息
     * @return sting $orderConfig 订单节点信息
     * @author john_zeng
     */
    public function saveOrder($startTime, $orderTime) {
        $msg = false;
        $string = "pay_time > '{$orderTime}' and pay_time < '{$startTime}' and pay_status = '2' and pay_channel <> '4'";
        $orderList = M('ttg_order_info')->where($string)->select();
        if (is_array($orderList)) {
            foreach ($orderList as $orderInfo) {
                $data = '';
                $smsConfig = D('CityExpressShipping')->getNodeConfig(
                    $orderInfo['node_id']);
                if ('1' == $smsConfig) {
                    $data['batch_no'] = get_notes_batch_no(
                        $orderInfo['node_id']);
                    $data['batch_id'] = 0;
                    $data['node_id'] = $orderInfo['node_id'];
                    $data['request_id'] = get_request_id();
                    $data['phone_no'] = $orderInfo['receiver_phone'];
                    $data['add_time'] = date('YmdHis');
                    $data['trans_type'] = 1;
                    $url = C('CURRENT_DOMAIN') .
                         'index.php?g=Label&m=MyOrderInfo&a=index&order_id=' .
                         $orderInfo['order_id'];
                    $data['sms_notes'] = '您的订单' . $orderInfo['order_id'] .
                         '已支付成功。查看订单详情请点击：' . create_sina_short_url($url);
                    $data['send_level'] = 4;
                    $result = M('tbatch_importdetail')->add($data);
                    if (false === $result) {
                        log_write(
                            "记录短信信息失败. 订单号：{$orderInfo['order_id']}, 订单信息:{$data['sms_notes']}", 
                            'ERROR', 'SendNode');
                        return $msg;
                    } else {
                        $msg = $orderInfo['pay_time'];
                    }
                }
            }
            return $msg;
        } else {
            return $msg;
        }
    }
}
