<?php

/*
 * 单品送礼
 */
class PresentAction extends MyBaseAction {

    public $node_short_name = '';

    public $wx_flag;

    public $bonusService;

    public $wfxService;

    public $saler_id = '';

    public function _initialize() {
        parent::_initialize();
        $this->node_short_name = get_node_info($this->node_id, 
            'node_short_name');
        // 红包服务
        $this->bonusService = D('Bonus', 'Service');
        // 旺分销
        $this->wfxService = D('Wfx', 'Service');
        // 判断是否是微信中打开 0 否 1是
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') === false) {
            $wx_flag = 0;
        } else {
            $wx_flag = 1;
        }
        // 旺分销 链接上带的销售员id
        $saler_id = I('saler_id');
        $this->saler_id = $saler_id;
        $this->assign('saler_id', $saler_id);
        $this->wx_flag = $wx_flag;
        $this->assign('wx_flag', $wx_flag);
        $this->assign('node_short_name', $this->node_short_name);
        $this->assign('id', $this->id);
    }

    /*
     * 送礼给他
     */
    public function send_gift() {
        $goods_id = I("goods_id", null);
        if (! $goods_id)
            $this->error('商品数据不能为空');
        
        $skuInfo = I("sku_info"); // sku信息
        $marketInfo = $this->marketInfo;
        // 判断是否sku商品
        if ('' != $skuInfo) {
            // 将传输进入的，号替换为#号
            $skuObj = D('Sku', 'Service');
            $skuIdInfo = $skuObj->replaceArray($skuInfo, '&', ',', '#');
            $skuList = implode(',', $skuIdInfo);
            $filter[] = "g.sku_detail_id in ('" . $skuList . "')";
            $filter[] = "b.m_id = " . $this->batch_id;
            $batchInfo = M()->table("tbatch_info b")->where($filter)
                ->join("tecshop_goods_sku s ON s.b_id=b.id")
                ->join("tgoods_sku_info g ON g.id = s.skuinfo_id")
                ->field(
                'b.batch_name,b.batch_img,s.sale_price as batch_amt, s.storage_num, s.remain_num')
                ->find();
            $batchInfo['sku_name'] = $skuObj->getSkuNameList($skuList);
        } else {
            $batchInfo = M('tbatch_info')->where(
                array(
                    'm_id' => $this->batch_id))
                ->field('batch_name,batch_img,batch_amt,storage_num,remain_num')
                ->find();
        }
        $goodsInfo = array_merge($marketInfo, $batchInfo);
        if (! $goodsInfo)
            $this->error('未找到商品数据');
            // if($goodsInfo['defined_one_name'] != '0')
            // $this->error('送礼功能仅支持自提商品');
            // 判断最多可购买的份数
        if (($goodsInfo['buy_num'] == 0) &&
             ($goodsInfo['defined_three_name'] == - 1)) {
            $buy_limit = 99;
        } elseif (($goodsInfo['buy_num'] != 0) &&
             ($goodsInfo['defined_three_name'] == - 1)) {
            $buy_limit = min(99, $goodsInfo['buy_num']);
        } elseif (($goodsInfo['buy_num'] == 0) &&
             ($goodsInfo['defined_three_name'] != - 1)) {
            $buy_limit = min(99, $goodsInfo['defined_three_name']);
        } elseif (($goodsInfo['buy_num'] != 0) &&
             ($goodsInfo['defined_three_name'] != - 1)) {
            $buy_limit = min($goodsInfo['buy_num'], 
                $goodsInfo['defined_three_name']);
        } else
            $buy_limit = 99;
        
        if ($goodsInfo['storage_num'] != - 1)
            $buy_limit = min($buy_limit, $goodsInfo['remain_num']);
            
            // 分销员信息
        $salerInfo = $this->wfxService->get_bind_saler($this->node_id, 
            session('groupPhone'), $this->batch_id, $this->saler_id);
        $errcode = $this->wfxService->errcode;
        
        $this->assign('buy_limit', $buy_limit);
        $this->assign('goodsInfo', $goodsInfo);
        $this->assign('skuInfo', $skuInfo);
        $this->assign('id', $this->id);
        $this->assign('errcode', $errcode);
        $this->assign('salerInfo', $salerInfo);
        $this->display();
    }

    /*
     * 送礼订单确认
     */
    public function pay_confirm() {
        // 判断是否登录
        if (! session('?groupPhone')) {
            $this->error('您还没有登录');
        }
        
        $bless_name = I('bless_name', null);
        $bless_msg = I('bless_msg', null);
        $gift_type = I('gift_type', 0, 'intval'); // 1 微信送礼 2短信
        $gift_num = I('gift_num', 0, 'intval');
        $rece_phone = I('rece_phone', array());
        $skuInfo = I("skuInfo"); // sku信息
        
        if ($bless_name == '')
            $this->error('您的称呼不能为空');
        if ($bless_msg == '')
            $this->error('祝福语不能为空');
        if (! in_array($gift_type, 
            array(
                1, 
                2)))
            $this->error('送礼方式类型错误');
        if ($gift_num < 1 && $gift_type == 1)
            $this->error('送礼分数错误');
        if ($gift_type == 2)
            $gift_num = count($rece_phone);
            // 把送礼配置数据存cookie
        $cookie_arr = array(
            'bless_msg' => $bless_msg, 
            'bless_name' => $bless_name, 
            'gift_type' => $gift_type, 
            'gift_num' => $gift_num, 
            'rece_p_list' => implode("|", $rece_phone));
        cookie('gift_cookie', $cookie_arr, 86400); // 24小时有效
        $marketInfo = $this->marketInfo;
        if ('' != $skuInfo) {
            // 将传输进入的，号替换为#号
            $skuObj = D('Sku', 'Service');
            $skuIdInfo = $skuObj->replaceArray($skuInfo, '&', ',', '#');
            $skuList = implode(',', $skuIdInfo);
            $filter[] = "g.sku_detail_id in ('" . $skuList . "')";
            $filter[] = "b.m_id = " . $this->batch_id;
            $batchInfo = M()->table("tbatch_info b")->where($filter)
                ->join("tecshop_goods_sku s ON s.b_id=b.id")
                ->join("tgoods_sku_info g ON g.id = s.skuinfo_id")
                ->field(
                'b.batch_name,b.batch_img,s.sale_price as batch_amt, s.storage_num, s.remain_num')
                ->find();
            $batchInfo['sku_name'] = $skuObj->getSkuNameList($skuList);
        } else {
            $batchInfo = M('tbatch_info')->where(
                array(
                    'm_id' => $this->batch_id))
                ->field('batch_name,batch_img,batch_amt,storage_num,remain_num')
                ->find();
        }
        $goodsInfo = array_merge($marketInfo, $batchInfo);
        if (! $goodsInfo)
            $this->error('未找到商品数据');
            
            // 获取可用红包金额和红包数据
            // 计算可用多少红包
        $userBonus = $this->bonusService->getUseBonus($this->node_id, 
            $goodsInfo['batch_amt'] * $gift_num);
        // 可用使用的红包明细
        $userBounsList = $this->bonusService->getUserBonus(
            session('groupPhone'), $this->node_id);
        
        // 获取可支付通道
        $hasPayChannel = 0;
        $payChannelInfo = M('tnode_account')->where(
            array(
                'node_id' => $this->node_id))->getField('account_type,status');
        foreach ($payChannelInfo as $v => $k) {
            if ($k == 1)
                $hasPayChannel = 1;
        }
        // 分销员信息
        $salerInfo = $this->wfxService->get_bind_saler($this->node_id, 
            session('groupPhone'), $this->batch_id, $this->saler_id);
        $errcode = $this->wfxService->errcode;
        if ($errcode == 0 && ! $salerInfo) {
            $saler_phone = I('saler_phone', null);
            if ($saler_phone) {
                $salerInfo = $this->wfxService->get_saler_info_by_phone(
                    $this->node_id, $saler_phone);
            }
        }
        // 取得总规则信息
        $ruleType = D('SalePro', 'Service')->getNodeRule($this->node_id);
        $this->assign('ruleType', $ruleType); // 红包总规则
        $this->assign('errcode', $errcode);
        $this->assign('salerInfo', $salerInfo);
        $this->assign('saler_phone', $saler_phone);
        $this->assign('hasPayChannel', $hasPayChannel);
        $this->assign('payChannelInfo', $payChannelInfo);
        $this->assign('userBonus', $userBonus);
        $this->assign('sku_name', $sku_name);
        $this->assign('userBounsList', $userBounsList);
        $this->assign('goodsInfo', $goodsInfo);
        $this->assign('bless_name', $bless_name);
        $this->assign('bless_msg', $bless_msg);
        $this->assign('skuInfo', $skuInfo);
        $this->assign('gift_type', $gift_type);
        $this->assign('gift_num', $gift_num);
        $this->assign('rece_phone', $rece_phone);
        $this->assign('rece_phone_list', implode("|", $rece_phone));
        $this->assign('id', $this->id);
        $this->display();
    }

    /*
     * 送礼支付
     */
    public function pay_gift() {
        // 判断是否登录
        if (! session('?groupPhone')) {
            $this->error('您还没有登录');
        }
        $bless_name = I('bless_name', null);
        $bless_msg = I('bless_msg', null);
        $gift_type = I('gift_type', 0, 'intval'); // 1 微信送礼 2短信
        $gift_num = I('gift_num', 0, 'intval');
        $rece_phone = I('rece_phone', ''); // phone1|phone2|phone3
        $skuInfo = I("skuInfo"); // sku信息
                                 // 使用的红包
        $bonus_use_id = I("bonus_use_id");
        $memo = I("memo");
        $pay_channel = I("payment_type"); // 1 支付宝 2联动优势
        
        if ($bless_name == '')
            $this->error('您的称呼不能为空');
        if ($bless_msg == '')
            $this->error('祝福语不能为空');
        if (! in_array($gift_type, 
            array(
                1, 
                2)))
            $this->error('送礼方式类型错误');
        if ($gift_num < 1)
            $this->error('送礼分数错误');
        
        $marketInfo = $this->marketInfo;
        if ('' != $skuInfo) {
            // 将传输进入的，号替换为#号
            $skuObj = D('Sku', 'Service');
            $skuIdInfo = $skuObj->replaceArray($skuInfo, '&', ',', '#');
            $skuList = implode(',', $skuIdInfo);
            $filter[] = "g.sku_detail_id in ('" . $skuList . "')";
            $filter[] = "b.m_id = " . $this->batch_id;
            $batchInfo = M()->table("tbatch_info b")->where($filter)
                ->join("tecshop_goods_sku s ON s.b_id=b.id")
                ->join("tgoods_sku_info g ON g.id = s.skuinfo_id")
                ->field(
                'b.batch_name,b.batch_img,s.sale_price as batch_amt, s.storage_num, s.remain_num')
                ->find();
            $batchInfo['sku_name'] = $skuObj->getSkuNameList($skuList);
        } else {
            $batchInfo = M('tbatch_info')->where(
                array(
                    'm_id' => $this->batch_id))
                ->field('batch_name,batch_img,batch_amt,storage_num,remain_num')
                ->find();
        }
        $goodsInfo = array_merge($marketInfo, $batchInfo);
        if (! $goodsInfo)
            $this->error('未找到商品数据');
        
        $totalAmt = 0;
        $receive_phone = session('groupPhone');
        $order_id = date('ymd') . substr(time(), - 3) . substr(microtime(), 2, 
            7);
        
        if (! empty($this->batch_id)) {
            $result = $this->_checkGoods($goodsInfo, '0', $gift_num);
            if ($result['code'] != '0000')
                $this->error($result['msg']);
            $totalAmt = $goodsInfo['batch_amt'] * $gift_num;
            
            M()->startTrans(); // 起事务
                               
            // 判断如果有可使用的红包则订单金额减去红包金额，如果红包金额小于红包可使用金额则减去金额，否则减去最大可以使用金额
            if ($bonus_use_id != "") {
                // 计算订单可减去金额
                $reAmount = $this->bonusService->orderCutBonus(
                    session('groupPhone'), $bonus_use_id);
                // 如果累计的红包金额满足最大的使用红包金额，则减去最大红包金额，否则减去红包金额累计
                $maxAmount = $this->bonusService->getUseBonus($this->node_id, 
                    $totalAmt);
                if ($reAmount < $maxAmount) {
                    $cutAmount = $reAmount;
                } else {
                    $cutAmount = $maxAmount;
                }
            }
            $wxOpenId = '';
            // 判断是否微信订单
            if ($this->wx_flag == 1) {
                $wxUserInfo = session('merWxUserInfo');
                if (! $wxUserInfo)
                    $wxUserInfo = session('wxUserInfo');
                $wxOpenId = $wxUserInfo['openid'];
            }
            $ip = GetIP();
            // 入库主订单
            $data = array(
                'order_id' => $order_id, 
                'order_type' => '0', 
                'from_channel_id' => $this->id, 
                'from_batch_no' => $this->batch_id, 
                'batch_no' => $this->batch_id, 
                'group_batch_no' => $goodsInfo['batch_no'], 
                'order_phone' => session('groupPhone'), 
                'buy_num' => $gift_num, 
                'batch_channel_id' => $this->id, 
                'node_id' => $this->node_id, 
                'order_amt' => $totalAmt - $cutAmount, 
                'add_time' => date('YmdHis'), 
                'receiver_type' => '0', 
                // 'receiver_name'=>trim($receive_name),
                // 'receiver_addr'=>trim($address),
                'receiver_phone' => trim($receive_phone), 
                'pay_channel' => $pay_channel, 
                'order_status' => '0', 
                'memo' => $memo, 
                'add_time' => date('YmdHis'), 
                'freight' => 0.00, 
                'order_ip' => $ip, 
                'is_gift' => '1', 
                'price' => $goodsInfo['batch_amt'], 
                'bonus_use_amt' => $cutAmount,  // 新增红包使用金额在订单表中 添加人：曾成
                'openId' => $wxOpenId); // 新增微信OPENID在订单表中 添加人：曾成
                                        
            // 分销员信息
            $salerInfo = $this->wfxService->get_bind_saler($this->node_id, 
                session('groupPhone'), $this->batch_id, $this->saler_id);
            $errcode = $this->wfxService->errcode;
            // 分销员
            if ($errcode == 0) {
                if ($salerInfo)
                    $data['saler_id'] = $salerInfo['id'];
                else {
                    // 根据前端传入的手机号
                    $saler_phone = I('saler_phone');
                    if ($saler_phone) {
                        $sInfo = $this->wfxService->get_saler_info_by_phone(
                            $this->node_id, $saler_phone);
                        if ($sInfo)
                            $data['saler_id'] = $sInfo['id'];
                    }
                }
            }
            $result = M('ttg_order_info')->add($data);
            if ($result) {
                // 批量减红包
                if ($bonus_use_id != "") {
                    $resok = $this->bonusService->useBonus($bonus_use_id, 
                        $order_id, $cutAmount, $this->node_id, 
                        $data['order_amt']);
                    if (! $resok) {
                        M()->rollback();
                        $this->error("订单生成失败，红包扣减异常！");
                    }
                }
                // 判断是否减库存
                if ($goodsInfo['storage_num'] != - 1) {
                    $query_arr = M('tbatch_info')->where(
                        array(
                            'm_id' => $this->batch_id))->setDec('remain_num', 
                        $data['buy_num']);
                }
                
                // 新增送礼表 ttg_order_gift
                $gift_arr = array(
                    'order_id' => $order_id, 
                    'bless_msg' => $bless_msg, 
                    'bless_name' => $bless_name, 
                    'rece_phone_list' => $rece_phone, 
                    'gift_type' => $gift_type);
                $res = M('ttg_order_gift')->add($gift_arr);
                if ($res === false) {
                    M()->rollback();
                    $this->error("订单生成失败，送礼订单配置异常！");
                }
                
                // 将单品购买加入到订单表中
                $child_order_id = date('ymd') . substr(time(), - 3) .
                     substr(rand(11111, 99999)) . $bId;
                $row['order_id'] = $data['order_id'];
                $row['trade_no'] = $child_order_id;
                $row['b_id'] = $goodsInfo['b_id'];
                $row['b_name'] = $goodsInfo['name'];
                $row['goods_num'] = $gift_num;
                $row['price'] = $goodsInfo['batch_amt'];
                $row['amount'] = $gift_num * $goodsInfo['batch_amt'];
                $row['receiver_type'] = '0';
                if (! empty($skuInfo)) {
                    $skuInfoList = $skuObj->makeSkuOrderInfo($skuList, 
                        $goodsInfo['b_id']);
                    if ($skuInfoList) {
                        $row['ecshop_sku_id'] = $skuInfoList['id'];
                        $row['ecshop_sku_desc'] = $skuInfoList['sku_name'];
                    }
                }
                $res = M('ttg_order_info_ex')->add($row);
                if ($res === false) {
                    M()->rollback();
                    $this->error("订单生成失败，送礼订单配置异常！");
                }
            } else {
                M()->rollback();
                $this->error("订单生成失败，主订单入库异常！");
            }
            M()->commit(); // 提交事务
            cookie('gift_cookie', null); // 去掉缓存cookie
                                         // 判断是否免支付订单 红包抵扣
            if (0 == $data['order_amt']) {
                $saleModel = D('SalePro', 'Service');
                $result = $saleModel->OrderPay($data['order_id'], $pay_type);
                if ($result == 'success') {
                    // 油豆信息
                    $sourceInfo = D('MemberInstall')->orderPay($order_id, 
                        $this->node_id, $this->id);
                    A('Label/PayMent')->showMsgInfo('购买成功', 1, $this->id, 
                        $order_id, $this->node_id, $this->node_short_name);
                } else {
                    A('Label/PayMent')->showMsgInfo('购买失败', 0, $this->id, 
                        $order_id, $this->node_id, $this->node_short_name);
                }
            } else {
                // 去支付
                if ($pay_channel == '2') {
                    // 去支付
                    $payModel = A('Label/PayUnion');
                    $payModel->OrderPay($order_id);
                } elseif ($pay_channel == '1') {
                    if ($this->wx_flag == 1) {
                        // 微信中用支付宝支付则跳转到中转页面
                        redirect(
                            U('Label/PayConfirm/index', 
                                array(
                                    'order_id' => $order_id, 
                                    'id' => $this->id)));
                    } else {
                        $payModel = A('Label/PayMent');
                        $payModel->OrderPay($order_id);
                    }
                } elseif ($pay_channel == '3') {
                    // 微信支付
                    $payModel = A('Label/PayWeixin');
                    $payModel->goAuthorize($order_id);
                }
                exit();
            }
        } else {
            $this->error(
                "商品数据为空，返回,<a href='index.php?g=Label&m=Label&a=index&id=" .
                     $this->id . "'>商品首页</a>");
        }
    }

    /*
     * 校验商品库存和购买限制 $garr 商品数据arr $delivery 送货方式 $buycount 购买数量
     */
    private function _checkGoods($garr, $delivery, $buycount) {
        // 库存
        if (($garr['storage_num'] != - 1) && ($garr['remain_num'] < $buycount))
            return array(
                'code' => '0001', 
                'msg' => '商品库存不足');
            
            // 日购买限制 0 不限
        if ($garr['buy_num'] > 0) {
            $daySaleNum = M('ttg_order_info')->where(
                "batch_no ={$garr['id']} and add_time like '" . date('Ymd') .
                     "%' and order_status=0")->sum('buy_num');
            if (! $daySaleNum)
                $daySaleNum = 0;
            if ($buycount > ($garr['buy_num'] - $daySaleNum))
                return array(
                    'code' => '0002', 
                    'msg' => '购买份数超过商品日限购份数');
        }
        
        // 个人购买限制 -1不限
        if ($garr['defined_three_name'] >= 0) {
            $totalBuyNum = M('ttg_order_info')->where(
                "batch_no ={$garr['id']} and order_phone ='" .
                     session('groupPhone') . "' and order_status=0")->sum(
                'buy_num');
            if (! $totalBuyNum)
                $totalBuyNum = 0;
            if ($buycount > ($garr['defined_three_name'] - $totalBuyNum))
                return array(
                    'code' => '0003', 
                    'msg' => '购买份数超过个人限购份数');
        }
        // 送货方式
        if (($garr['defined_one_name'] != '0-1') &&
             ($garr['defined_one_name'] != $delivery))
            return array(
                'code' => '0004', 
                'msg' => '送货方式错误');
        
        return array(
            'code' => '0000', 
            'msg' => '成功');
    }
    // 分销员判断
    public function checkSaler() {
        $saler_phone = I('saler_phone', null);
        if (! $saler_phone)
            $this->error("分销员手机号不存在，<br />请检查分销员手机号");
        
        $salerInfo = $this->wfxService->get_saler_info_by_phone($this->node_id, 
            $saler_phone);
        if (! $salerInfo)
            $this->error("分销员不存在，<br />请检查分销员");
        else
            $this->success("分销员存在，<br />可继续购买");
    }
}