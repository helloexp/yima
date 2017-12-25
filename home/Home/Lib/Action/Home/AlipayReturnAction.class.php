<?php

class AlipayReturnAction extends Action {

    public function index() {
        $data = array();
        $method = ''; // 请求方法post,get
        if (! empty($_POST)) {
            $data = $_POST;
            $method = 'post';
        } elseif (! empty($_GET)) {
            $data = $_GET;
            $method = 'get';
        }
        Log::write('alipayReturn:' . print_r($data, true));
        import('@.Vendor.AlipayModel') or die('导入包AlipayModel失败');
        $alipay = new AlipayModel();
        $verify_result = $alipay->checkReturn($data);
        // $verify_result = true;//调试时打开
        if (! $verify_result) { // 验证失败,如要调试，请看alipay_notify.php页面的verifyReturn函数
            Log::write('签名错误');
            echo "fail";
            exit();
        }
        // 获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表
        // 商户订单号
        $out_trade_no = $data['out_trade_no'];
        // 支付宝交易号
        $trade_no = $data['trade_no'];
        // 交易金额
        $total_fee = $data['total_fee'];
        // 交易状态
        $trade_status = $data['trade_status'];
        if ($data['trade_status'] == 'TRADE_FINISHED' ||
             $data['trade_status'] == 'TRADE_SUCCESS') {
            // 更新订单状态
            $tb_order_list = M('torderList');
            $arr = $tb_order_list->where(
                array(
                    'order_id' => $out_trade_no))->find();
            if (! $arr) {
                Log::write('订单不存在');
                echo 'fail_订单不存在';
                exit();
            }
            if ($arr['order_status'] != '0') {
                echo 'fail_订单状态不正常';
                exit();
            }
            
            // 判断金额是否和订单中一样
            if ($total_fee != $arr['busi_amt']) {
                echo 'fail_金额不一至';
                exit();
            }
            
            // 更新订单状态
            $result = $tb_order_list->where(
                array(
                    'order_id' => $out_trade_no))->save(
                array(
                    'order_status' => 1, 
                    'update_time' => date('YmdHis')));
            if ($result === false) {
                die('fail');
            }
            // 更新商户信息
            $result = M('TnodeInfo')->where(
                array(
                    'node_id' => $arr['node_id']))->save(
                array(
                    'charge_id' => $arr['charge_id'], 
                    'update_time' => date('YmdHis')));
            
            echo 'success';
        } else { // 记录日志
            Log::write('错误请求信息');
            echo 'fail';
        }
    }
}