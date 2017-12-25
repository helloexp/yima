<?php

/**
 * 团购活动
 *
 * @author bao
 */
class GroupBuyAction extends MyBaseAction {

    public $expiresTime = 60;
    // 手机验证码过期时间
    public $batch_type = 6;
    // 团购列表页
    public function index() {
        
        // 标签
        $model = M('tbatch_channel');
        $id = I('id', null, 'mysql_real_escape_string');
        $map = array(
            'id' => $id, 
            'batch_type' => $this->batch_type);
        $result = $model->where($map)->find();
        if (! $result) {
            $this->error(
                array(
                    'errorImg' => '__PUBLIC__/Label/Image/waperro5.png', 
                    'errorTxt' => '错误访问！', 
                    'errorSoftTxt' => '你的访问地址出错啦~'));
        }
        // 活动
        $batchModel = M('tmarketing_info');
        $goodsInfo = $batchModel->where(
            array(
                'id' => $result['batch_id']))->find();
        // 计算剩余天数
        $time = (strtotime($goodsInfo['end_time']) - time());
        $day = floor($time / (60 * 60 * 24));
        $hour = floor(($time % (60 * 60 * 24)) / (60 * 60));
        // 更新点击数
        import('@.Vendor.DataStat');
        $opt = new DataStat($this->id, $this->full_id);
        $opt->UpdateRecord();
        // dump($goodsInfo);
        $this->assign('day', $day);
        $this->assign('hour', $hour);
        $this->assign('expiresTime', $this->expiresTime);
        $this->assign('id', $id);
        $this->assign('goodsInfo', $goodsInfo);
        $this->display();
    }
    
    // 登录
    public function loginPhone() {
        $phoneNo = I('post.phone', null, 'mysql_real_escape_string');
        if (! check_str($phoneNo, 
            array(
                'null' => false, 
                'strtype' => 'mobile'), $error)) {
            $this->ajaxReturn(array(
                'type' => 'phone'), "手机号{$error}", 0);
        }
        // 手机验证码
        $checkCode = I('post.check_code', null);
        if (! check_str($checkCode, array(
            'null' => false), $error)) {
            $this->ajaxReturn(array(
                'type' => 'pass'), "验证码{$error}", 0);
        }
        $groupCheckCode = session('groupCheckCode');
        if (empty($groupCheckCode) && $groupCheckCode['number'] != $checkCode)
            $this->ajaxReturn(array(
                'type' => 'pass'), '手机验证码不正确', 0);
        if (time() - $groupCheckCode['add_time'] > $this->expiresTime)
            $this->ajaxReturn(array(
                'type' => 'pass'), '手机验证码已经过期', 0);
            // 记录session
        session('groupPhone', $phoneNo);
        $this->success('登录成功');
    }
    
    // 订单信息页面
    public function orderInfo() {
        
        // 判断是否登录
        if (! session('?groupPhone')) {
            $this->error('您还没有登录');
        }
        // 标签
        $model = M('tbatch_channel');
        $id = I('id', null, 'mysql_real_escape_string');
        $map = array(
            'id' => $id, 
            'batch_type' => $this->batch_type);
        $result = $model->where($map)->find();
        if (! $result)
            $this->error('错误的参数！');
            // 活动
        $batchModel = M('tmarketing_info');
        $goodsInfo = $batchModel->where(
            array(
                'id' => $result['batch_id']))->find();
        // 商品总数和最多购买总数和已经卖出去商品数据处理
        $lastNum = $goodsInfo['goods_num'] - $goodsInfo['sell_num']; // 剩余商品
        if ($lastNum <= 0)
            $this->error('该商品已售完');
        if ($this->isPost()) {
            $error = '';
            $goodsNum = I('post.goods_num');
            if (! check_str($goodsNum, 
                array(
                    'null' => false, 
                    'strtype' => 'int'), $error)) {
                $this->error("购买数量{$error}");
            }
            if ($goodsNum > $lastNum)
                $this->error('你购买的数量已经超过了该商品现在剩余的数量了');
                // 收货人手机号
            $consigneePhone = I('post.consignee_phone');
            if (! check_str($consigneePhone, 
                array(
                    'null' => false, 
                    'strtype' => 'mobile'), $error)) {
                $this->error("手机号{$error}");
            }
            // 支付方式
            
            // 生成订单
            $data = array(
                'order_id' => date('ymd') . substr(time(), - 5) .
                     substr(microtime(), 2, 5), 
                    'order_type' => '0', 
                    'order_phone' => session('groupPhone'), 
                    'from_batch_no' => $goodsInfo['id'], 
                    'group_batch_no' => $goodsInfo['member_level'], 
                    'node_id' => $goodsInfo['node_id'], 
                    'order_amt' => $goodsInfo['group_price'] * $goodsNum, 
                    'receiver_phone' => $consigneePhone, 
                    'buy_num' => $goodsNum, 
                    'batch_channel_id' => $id, 
                    'status' => '1', 
                    'add_time' => date('YmdHis'));
            $result = M('ttg_order_info')->add($data);
            if (! $result)
                $this->error('系统出错,订单创建失败');
                // 去支付
            $payModel = A('Label/PayMent');
            $payModel->OrderPay($data['order_id']);
            exit();
        }
        $lastNum > $goodsInfo['buy_num'] && $goodsInfo['buy_num'] != 0 ? $buyNum = $goodsInfo['buy_num'] : $buyNum = $lastNum;
        $this->assign('buyNum', $buyNum);
        $this->assign('goodsInfo', $goodsInfo);
        $this->assign('id', $id);
        $this->display();
    }
    
    // 用户订单查看
    public function showOrderInfo() {
        // 判断是否登录
        if (! session('?groupPhone')) {
            $this->error('您还没有登录');
        }
        // 标签
        $model = M('tbatch_channel');
        $id = I('id', null, 'mysql_real_escape_string');
        $map = array(
            'id' => $id, 
            'batch_type' => $this->batch_type);
        $result = $model->where($map)->find();
        if (! $result)
            $this->error('错误的参数！');
        $where = array(
            'o.group_phone' => session('groupPhone'), 
            'o.node_id' => $result['node_id']);
        // 'o.order_type' => '2'
        
        $nowP = I('p', null, mysql_real_escape_string); // 页数
        empty($nowP) ? $nowP = 1 : $nowP;
        $pageCount = 10; // 每页显示条数
        $field = array(
            'o.*,g.group_goods_name,g.group_price');
        $orderList = M()->table('ttg_order_info o')
            ->field($field)
            ->join("tmarketing_info g ON o.group_batch_no=g.batch_no")
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
        $this->display();
    }
    
    // 商户活动列表页
    public function showBatchList() {
        // 标签
        $model = M('tbatch_channel');
        $id = I('id', null, 'mysql_real_escape_string');
        $map = array(
            'id' => $id, 
            'batch_type' => $this->batch_type);
        $result = $model->where($map)->find();
        if (! $result)
            $this->error('错误的参数！');
            // 活动
        $batchModel = M('tmarketing_info');
        $where = array(
            'node_id' => $result['node_id'], 
            'batch_type' => '6', 
            'status' => '1', 
            'end_time' => array(
                'gt', 
                date('YmdHis')));
        
        $nowP = I('p', null, mysql_real_escape_string); // 页数
        empty($nowP) ? $nowP = 1 : $nowP;
        $pageCount = 10; // 每页显示条数
        $goodsList = $batchModel->where($where)
            ->order('add_time DESC')
            ->limit(($nowP - 1) * $pageCount, $pageCount)
            ->select();
        // 该商户团购列表渠道id
        $channelId = M('tchannel')->where(
            "node_id={$result['node_id']} AND type=4 AND sns_type=44")->getField(
            'id');
        // 处理剩余日期
        foreach ($goodsList as $k => $v) {
            // 计算剩余天数
            $time = (strtotime($v['end_time']) - time());
            $day = floor($time / (60 * 60 * 24));
            $hour = floor(($time % (60 * 60 * 24)) / (60 * 60));
            $goodsList[$k]['goods_time'] = "<p>距离截止时间仅剩<span>{$day}天{$hour}小时</span></p>";
            // 构建活动列表渠道链接
            $goodsList[$k]['url_id'] = $model->where(
                "node_id={$result['node_id']} AND batch_type='{$this->batch_type}' AND batch_id={$v['id']} AND channel_id={$channelId}")->getField(
                'id');
        }
        $ajax = I('get.ajax', null);
        if ($ajax == 1) {
            $str = '';
            if ($goodsList) {
                foreach ($goodsList as $v) {
                    $detialUrl = '"' . U('Label/GroupBuy/index', 
                        array(
                            'id' => $v['url_id'])) . '"';
                    $detialStr = "<a href='javascript:link_to({$detialUrl});'>";
                    $str .= $detialStr . '<li>
								<div class="commodityList-img"><img src="' .
                         get_upload_url($v['goods_img']) . '"/></div>
								<div class="commodityList-con">
									<div class="commodityList-title">' .
                         $v['group_goods_name'] . '</div>
									<div class="commodityList-time"><i class="icon-time"></i>' .
                         $v['goods_time'] . '</div>
									<div class="commodityList-time">
										<i class="icon-number"></i><p>已售<span>' .
                         $v['sell_num'] .
                         '份</span><span class="ml20">&nbsp;</span>仅剩<span>' .
                         ($v['goods_num'] - $v['sell_num']) . '份</span></p>
									</div>
									<div class="commodityList-price"><p>￥' .
                         $v['group_price'] . '元</p><s>市场价￥' . $v['market_price'] . '元</s></div>
							    </div>
							  </li></a>';
                }
            }
            header("Content-type: text/html; charset=utf-8");
            echo $str;
            exit();
        }
        // dump($goodsList);exit;
        $this->assign('goodsList', $goodsList);
        $this->assign('id', $id);
        $this->display();
    }
    
    // 手机发送验证码
    public function sendCheckCode() {
        $phoneNo = I('post.phone', null);
        if (! check_str($phoneNo, 
            array(
                'null' => false, 
                'strtype' => 'mobile'), $error)) {
            $this->error("手机号{$error}");
        }
        // 发送频率验证
        $groupCheckCode = session('groupCheckCode');
        if (! empty($groupCheckCode) &&
             (time() - $groupCheckCode['add_time']) < $this->expiresTime) {
            $this->error('验证码发送过于频繁!');
        }
        $num = mt_rand(1000, 9999);
        // 通知支撑
        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
                                                                   // 请求参数
        $req_array = array(
            'NotifyReq' => array(
                'TransactionID' => $TransactionID, 
                'ISSPID' => '00000243', 
                'SystemID' => C('ISS_SYSTEM_ID'), 
                'SendLevel' => '1', 
                'Recipients' => array(
                    'Number' => $phoneNo),  // 手机号
                'SendClass' => 'MMS', 
                'MessageText' => $num,  // 短信内容
                'Subject' => '', 
                'ActivityID' => '13101622783', 
                'ChannelID' => '', 
                'ExtentCode' => ''));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $resp_array = $RemoteRequest->requestIssServ($req_array);
        // dump($resp_array);exit;
        $ret_msg = $resp_array['NotifyRes']['Status'];
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
             $ret_msg['StatusCode'] != '0001')) {
            $this->error('发送失败');
        }
        $groupCheckCode = array(
            'number' => $num, 
            'add_time' => time());
        session('groupCheckCode', $groupCheckCode);
        $this->success('验证码已发送');
    }
    
    // 登录判断
    public function checkPhoneLogin() {
        // 判断是否登录
        if (! session('?groupPhone')) {
            $this->ajaxReturn('', '', 0);
        } else {
            $this->ajaxReturn('', '', 1);
        }
    }
}