<?php

class AiPaiOrderInfoAction extends MyBaseAction {

    public function _initialize() {
    }
    
    // 爱拍用户订单列表
    public function showAiPaiOrderInfo() {
        
        // 判断是否爱拍来源
        $aiPaiPhoneNo = I('get.aipaiphoneno');
        $aiPaiSessionId = I('get.sessionId');
        if ($aiPaiPhoneNo && $aiPaiSessionId) {
            $flag = $this->checkMemberLogin($aiPaiSessionId);
            if ($flag) {
                log::write("checkMemberLogin success");
                session('groupPhone', $aiPaiPhoneNo);
                $empty = "您还没有购买过我们的商品";
            } else
                log::write("checkMemberLogin fail");
        } else
            $empty = "您还没有登录或者登录已超时，请重新登录";
        $where = array(
            'o.order_phone' => session('groupPhone'));
        $nowP = I('p', null, mysql_real_escape_string); // 页数
        empty($nowP) ? $nowP = 1 : $nowP;
        $pageCount = 10; // 每页显示条数
        $field = array(
            'o.*,g.group_goods_name,g.group_price');
        $orderList = M()->table('ttg_order_info o')
            ->field($field)
            ->join("tmarketing_info g ON o.group_batch_no=g.member_level")
            ->where($where)
            ->order('o.add_time DESC')
            ->limit(($nowP - 1) * $pageCount, $pageCount)
            ->select();
        
        $status = array(
            '1' => '未支付', 
            '2' => '已支付');
        $ajax = I('get.ajax', null);
        if ($ajax == 1) {
            $str = '';
            if ($orderList) {
                foreach ($orderList as $v) {
                    $payUrl = '"' . U('Label/PayMent/OrderPay', 
                        array(
                            'order_id' => $v['order_id'])) . '"';
                    $v['status'] == 1 ? $payStr = "<a href='javascript:void(0);' onClick='javascript:link_to({$payUrl});'>支付</a>" : $payStr = '';
                    $str .= '<li>
								<div class="orderList-title">' .
                         $v['group_goods_name'] . $payStr .
                         '</div>
								<div class="orderList-con">
									<p>
									   <span>下单时间:' .
                         dateformat($v['add_time'], 'Y-m-d H:i:s') .
                         '</span>
							           <span class="ml20">收货手机号码:' .
                         $v['receiver_phone'] . '</span>
									</p>
							        <p><span>支付状态:' .
                         $status[$v['status']] . '</span></p>
							        <p>
							           <span>购买数量:' . $v['buy_num'] . '</span>
							           <span class="ml20">商品单价:' .
                         $v['group_price'] . '元</span>
							           <span class="ml20">共支付:' .
                         $v['order_amt'] . '元</span>
							        </p>
							    </div>
							  </li>';
                }
            }
            header("Content-type: text/html; charset=utf-8");
            echo $str;
            exit();
        }
        
        // dump($orderList);exit;
        $this->assign('orderList', $orderList);
        $this->assign('status', $status);
        $this->assign('id', $id);
        $this->assign('empty', $empty);
        $this->display();
    }
}