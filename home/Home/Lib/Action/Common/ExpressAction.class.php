<?php

/**
 * 物流查询
 */
class ExpressAction extends Action {

    public function updateShippingInfo() {
        $orderID = I('post.order');
        $type = I('post.type', '4', 'string');
        $exist = 0;
        $isExist = M('torder_express_info')->where(
            array(
                'order_id' => $orderID, 
                'type' => $type))
            ->field('check_time, status')
            ->find();
        if ($isExist) {
            $exist = 1;
        }
        $intervalTime = M('tsystem_param')->where(
            array(
                'param_name' => 'EXPRESS_QUERY_TIME'))->getfield('param_value');
        $dateTime = strtotime(dateformat($isExist['check_time'], 'Y-m-d H:i:s')) +
             $intervalTime * 60;
        if (empty($isExist) || ($dateTime < time() && $isExist['status'] == '0')) {
            switch ($type) {
                case '4':
                    $shippingInfo = M('twfx_book_order')->where(
                        array(
                            'order_id' => $orderID))
                        ->field('delivery_company, delivery_number')
                        ->find();
                    break;
                case '5':
                    $shippingInfo = M('tintegral_order_info')->where(
                        array(
                            'order_id' => $orderID))
                        ->field('delivery_company, delivery_number')
                        ->find();
                default:
                    break;
            }
            
            if (empty($shippingInfo)) {
                $result['error'] = '1001';
                $result['msg'] = '未找到订单';
                $this->ajaxReturn($result);
                exit();
            }
            
            $expressName = mb_substr($shippingInfo['delivery_company'], 0, 2, 
                'utf-8'); // 此处为了兼容之前的数据
            $expressNameCode = M('texpress_info')->where(
                array(
                    'express_name' => array(
                        'like', 
                        '%' . $expressName . '%')))->getfield('query_str');
            $expressServiceModel = D('Express', 'Service');
            $result = $expressServiceModel->index($orderID, 
                $_SESSION['node_id'], $exist, $expressNameCode, 
                $shippingInfo['delivery_number'], $type);
        } else {
            $result = array(
                'error' => '0');
        }
        $this->ajaxReturn($result);
    }
}
