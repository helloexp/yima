<?php

class IntegralShopAction extends MyBaseAction {
    // 微信用户id
    public $openid = "";
    // 微信openId
    public $expiresTime = 120;

    public $CodeexpiresTime = 600;

    public $member_id = '';

    public $_authAccessMap = '*';

    public $label_id = '';

    public function _initialize() {
        parent::_initialize();
        $this->_integralName();
        $this->label_id = I('get.id', '', intval);
        $node_id = $this->node_id;
        $integralInfo = M('tintegral_node_config')->where(
            "node_id = '$node_id'")->find();
        $wx_share_config = D('WeiXin', 'Service')->getWxShareConfig();
        $shareUrl = U('index', array(
            'id' => $this->id), '', '', TRUE);
        $shareArr = array(
            'config' => $wx_share_config, 
            'link' => $shareUrl, 
            'title' => $integralInfo['integral_shop_name'], 
            'desc' => $integralInfo['integral_describe'], 
            'imgUrl' => '__UPLOAD__/' . $integralInfo['integral_sharpic']);
        $this->assign('label_id', $this->label_id);
        $this->assign('shareData', $shareArr);
        $wxopen = session('wxUserInfo');
        $this->openid = $wxopen['openid'];
        $user_info = session('store_mem_id' . $this->node_id);
        $this->member_id = $user_info['user_id'];
    }

    /**
     * 首页
     */
    public function index() {
        $member_id = $this->member_id;
        $cata_id = I('get.cata', '', 'intval');
        $order_id = I('get.order', '', 'intval');
        $search_name = I('get.search', '', 'mysql_real_escape_string');
        $nowP = I('p', null, 'mysql_real_escape_string');
        $integral_obj = D('Integral');
        $node_id = $this->node_id;
        $label_id = $this->label_id;
        // 商品信息
        $goods_info = $integral_obj->goodsInfo($cata_id, $order_id, 
            $search_name, $node_id, $nowP);
        // 更新访问量
        import('@.Vendor.DataStat');
        $opt = new DataStat($label_id, $this->full_id);
        $opt->UpdateRecord();
        // 分类名字
        $cata_name = $integral_obj->cata($cata_id);
        
        // 首页标题
        $integralInfo = M("tintegral_node_config")->where(
            array(
                'node_id' => $node_id))->getField('integral_shop_name');
        if ($integralInfo) {
            $this->assign('integral_shop_name', $integralInfo);
        } else {
            $this->assign('integral_shop_name', "积分商城");
        }
        $this->assign('list', $goods_info);
        $this->assign('order_id', $order_id);
        $this->assign('cata', $cata_name);
        $this->assign('cata_id', $cata_id);
        $this->assign('search_name', $search_name);
        $this->assign('node_id', $node_id);
        if (I('in_ajax', 0, 'intval') == 1) {
            $this->display('list');
        } else {
            $gets = I('get.');
            $gets['in_ajax'] = 1;
            $gets['p'] = 2;
            $nextUrl = U('', $gets);
            $this->assign('nextUrl', $nextUrl);
            $this->display();
        }
    }

    /**
     * 商品分类
     */
    public function cata() {
        $node_id = $this->node_id;
        $cata_obj = D('Integral');
        $cata_all = $cata_obj->cataAll($node_id);
        
        $this->assign('cate_re', $cata_all);
        $this->display();
    }
    
    // 商品详情
    public function detail() {
        $label_id = $this->label_id;
        $ge_id = I('ge_id', null);
        $group_id = I('group_id');
        $goods_id = I('get.goods_id');
        if ($ge_id == null && $group_id == '') {
            $this->error("访问出错");
        }
        
        // 更新访问量
        import('@.Vendor.DataStat');
        $opt = new DataStat($label_id, $this->full_id);
        $opt->UpdateRecord();
        
        $goodsInfo = M('tintegral_goods_ex')->where("id = '$ge_id'")->find();
        $mid = $this->batch_id;
        // 是否sku商品
        $skuObj = D('IntegralSku', 'Service');
        $skuInfoList = $skuObj->getSkuEcshopList($goods_id, $this->node_id);
        // 判断是否有sku数据
        $goodsSkuTypeList = '';
        $goodsSkuDetailList = '';
        $skuDetail = '';
        $goodsInfo['sku'] = array();
        $isSku = false;
        
        if (NULL === $skuInfoList) {
            $isSku = false;
            $goodsInfo = M()->table("tintegral_goods_ex a")->join(
                'tgoods_info b ON a.goods_id = b.id')
                ->field(
                'b.goods_name, a.*, b.remain_num ,b.goods_image ,b.exchange_num')
                ->where("b.goods_id = '$goods_id'")
                ->find();
            $goodsInfo['market_price'] = floor($goodsInfo['market_price']);
        } else {
            $isSku = true;
            // 分离商品表中的规格和规格值ID
            $goods_sku_list = $skuObj->getReloadSku($skuInfoList);
            // 取得规格值表信息
            if (is_array($goods_sku_list['list']))
                $goodsSkuDetailList = $skuObj->getSkuDetailList(
                    $goods_sku_list['list']);
                // 取得规格表信息
            if (is_array($goodsSkuDetailList))
                $goodsSkuTypeList = $skuObj->getSkuTypeList($goodsSkuDetailList);
                // 价格列表
            $skuDetail = $skuObj->makeSkuList($skuInfoList);
            // 取得sku价格
            $goodsInfo = $skuObj->makeGoodsListInfo($goodsInfo, $this->nodeId, 
                true, $bid);
            $tgoods_id = $goodsInfo['goods_id'];
            $re = M('tgoods_info')->where("id = '$tgoods_id'")
                ->field('goods_name,exchange_num')
                ->find();
            $goodsInfo['goods_name'] = $re['goods_name'];
            $goodsInfo['exchange_num'] = $re['exchange_num'];
            
            $sku_goods = M('tgoods_sku_integral_info')->where(
                array(
                    'goods_id' => $goods_id, 
                    'node_id' => $this->node_id))
                ->order('market_price')
                ->select();
            $sku_goods_end = end($sku_goods);
            $goodsInfo['market_price'] = floor($sku_goods[0]['market_price']) .
                 "~" . floor($sku_goods_end['market_price']);
        }
        $de_flag = M()->table("tintegral_goods_ex a")->where("id = '{$ge_id}'")->getField(
            'delivery_flag');
        $goodsInfo['delivery_flag'] = $de_flag;
        $this->assign('goodsInfo', $goodsInfo);
        $this->assign('isSku', $isSku);
        $this->assign("skuDetail", $skuDetail);
        $this->assign('group_id', $group_id);
        $this->assign('node_id', $this->node_id);
        $this->assign("skutype", 
            $skuObj->makeSkuType($goodsSkuTypeList, $goodsSkuDetailList));
        $this->display(); // 输出模板
    }

    /**
     * 领取商品
     */
    public function orderConfirm() {
        $integral_id = I('get.integral_id', '', intval); // 商品id(tintegral_goods_ex)
        $delivery = I('get.delivery', 0); // 配送方式
        $sku_id = I('get.sku_info');
        $sku_info = I('get.sku_info');
        $price = I('get.price');
        $label_id = $this->label_id;
        $field = '';
        $goods_info = M()->table("tintegral_goods_ex a")->join(
            'tgoods_info b ON a.goods_id = b.id')
            ->where("a.id = '$integral_id'")
            ->field("a.wap_info,b.goods_name,a.market_price")
            ->find();
        
        if ($sku_info != null) {
            // sku信息
            $date['id'] = array(
                'in', 
                $sku_info);
            $sku_info_re = M('tgoods_sku_integral_detail')->where($date)->select();
            foreach ($sku_info_re as $key => $value) {
                $id = $value['sku_id'];
                $re = M('tgoods_integral_sku')->where("id = '$id'")->find();
                $sku_info_re[$key]['sku_id'] = $re['sku_name'];
            }
            $this->assign('sku_info', $sku_info);
            $this->assign('sku_info_list', $sku_info_re);
        }
        
        // 物流
        if ($delivery == '1') {
            $phoneAddressModel = D('Integral');
            $allAddress = $phoneAddressModel->getAllPhoneAddress(
                session('groupPhone'));
            $this->assign('address', $allAddress);
        }
        
        // 获取会员详细信息
        $cataObj = D('Integral');
        $wx_info = $cataObj->user_info($this->member_id);
        $point = $wx_info['point'] - $price;
        if ($point < 0) {
            $point_flag = '1';
        }
        $this->assign('point_flag', $point_flag);
        // 剩余积分
        $this->assign('point', $point);
        // 价钱
        $this->assign('market_price', $price);
        // 电话
        $this->assign('mobile', session('groupPhone'));
        // 商品名称
        $this->assign('goods_name', $goods_info['goods_name']);
        // 商品描述
        $this->assign('wx_info', $wx_info);
        $this->assign('wap_info', 
            htmlspecialchars_decode($goods_info['wap_info']));
        $this->assign('expiresTime', $this->expiresTime);
        $this->assign('node_id', $this->node_id);
        $this->assign('integral_id', $integral_id);
        $this->assign('delivery', $delivery);
        $this->display();
    }

    /**
     * 发码处理
     */
    protected function sendCode($orderId, $orderType, $nodeId, $issBatchNo, 
        $phone, $bId, $mId, $gift_phone = null, $appid_array, 
        $tintegral_order_info_id) {
        // 发码
        $transId = get_request_id();
        import("@.Vendor.SendCode");
        $req = new SendCode();
        $res = $req->wc_send($nodeId, '', $issBatchNo, $phone, 'I', $transId, 
            '', $bId, null, $appid_array);
        log_write("13545204021:{$res}");
        if ($res == ture) {
            $order_trace_data = array(
                'order_id' => $orderId, 
                'm_id' => $mId, 
                'b_id' => $bId, 
                'code_trace' => $transId, 
                'gift_phone' => $gift_phone, 
                'openid' => $this->openid, 
                'trans_time' => date('YmdHis', time()));
            $result = M('tintegral_order_trace')->add($order_trace_data);
            if (! $result) {
                log_write(
                    "积分商城订单发码成功,更新send_seq失败;order_id:{$orderId},send_seq:{$transId}");
            }
            return ture;
        } else {
            log_write("订单发码失败,原因:{$res}");
            return false;
        }
    }

    /**
     * 提交订单
     */
    public function subOrder() {
        $label_id = $this->label_id;
        $phoneNo = I('post.phone', null, 'mysql_real_escape_string');
        $error = '手机号或者验证码错误！';
        if (! check_str($phoneNo, 
            array(
                'null' => false, 
                'strtype' => 'mobile'), $error)) {
            $this->ajaxReturn(
                array(
                    'type' => 'phone', 
                    'info' => "手机号{$error}", 
                    'status' => '5'));
        }
        // 手机动态密码
        $checkCode = I('post.check_code', null);
        if (! check_str($checkCode, array(
            'null' => false), $error)) {
            $this->ajaxReturn(
                array(
                    'type' => 'pass', 
                    'info' => "动态密码{$error}", 
                    'status' => '5'));
        }
        $groupCheckCode = session('groupCheckCode');
        if (! empty($groupCheckCode) && $groupCheckCode['phoneNo'] != $phoneNo)
            $this->ajaxReturn(
                array(
                    'type' => 'phone', 
                    'info' => "手机号不正确", 
                    'status' => '5'));
        if (! empty($groupCheckCode['number']) &&
             $groupCheckCode['number'] != $checkCode)
            $this->ajaxReturn(
                array(
                    'type' => 'pass', 
                    'info' => "动态密码不正确", 
                    'status' => '5'));
        if (time() - $groupCheckCode['add_time'] > $this->CodeexpiresTime)
            $this->ajaxReturn(
                array(
                    'type' => 'pass', 
                    'info' => "动态密码已经过期", 
                    'status' => '5'));
            
            // 商品ID
        $integral_id = I('post.integral_id', '');
        // 价钱
        $deduction = I('post.market_price');
        $delivery = I('post.delivery');
        $receiver_name = I('post.receive_name');
        $receive_address = I('post.receive_address');
        $receiver_phone = I('post.or_phone');
        $province_code = I('post.province_code');
        $city_code = I('post.city_code');
        $town_code = I('post.town_code');
        $all_path = $province_code . $city_code . $town_code;
        $sku_detail_id = I('post.sku_info');
        $c_phone = I('post.phone');
        $sku_info = strtr($sku_detail_id, ",", "#");
        // 会员信息
        $where = array(
            'node_id' => $this->node_id, 
            'phone_no' => session('groupPhone'));
        $member = M("tmember_info")->where($where)->find();
        // 积分不足判断
        if ($member['point'] < $deduction) {
            log_write(L('INTEGRAL_NAME') . "不足！");
            exit(
                json_encode(
                    array(
                        'status' => 0, 
                        'msg' => L('INTEGRAL_NAME') . "不足！")));
        }
        
        $goods = M()->table("tintegral_goods_ex a")->join(
            'tgoods_info b ON a.goods_id = b.id')
            ->join('tbatch_info c ON b.batch_no = c.batch_no')
            ->where("a.id = '$integral_id'")
            ->field(
            'c.id,b.goods_id,b.storage_num,b.is_sku,b.remain_num,b.goods_name,b.market_price')
            ->find();
        if (! $goods) {
            log_write(L("未找到对应的商品!"));
            exit(
                json_encode(
                    array(
                        'status' => 0, 
                        'msg' => '未找到对应的商品')));
        }
        $bid = $goods['id'];
        M()->startTrans();
        // 校验商品存不存在
        if ($sku_detail_id) {
            // sku信息商品处理
            $date['id'] = array(
                'in', 
                $sku_detail_id);
            $ecshop_sku_desc = M('tgoods_sku_integral_detail')->where($date)->select();
            if (! $ecshop_sku_desc) {
                $this->error("您选择的规格不存在！");
            }
            foreach ($ecshop_sku_desc as $key => $value) {
                $ecshop_sku_desc_name .= '/' .
                     $ecshop_sku_desc[$key]['sku_detail_name'];
            }
            $ecshop_sku_desc_name = substr($ecshop_sku_desc_name, 1);
            $row['integral_sku_id'] = $skuIntegralInfo['id'];
            $row['integral_sku_desc'] = $ecshop_sku_desc_name;
        }
        // 查询商品剩余数量
        if ($goods['is_sku'] == 1) {
            $skuIntegralInfo = M("tgoods_sku_integral_info")->where(
                array(
                    'sku_detail_id' => $sku_info))->find();
            if ($skuIntegralInfo['remain_num'] > 0) {
                // SKU库存扣减
                $sku_res = M("tgoods_sku_integral_info")->where(
                    "sku_detail_id='$sku_info'")->setDec('remain_num');
                if (! $sku_res) {
                    M()->rollback();
                    log_write("sku扣减库存失败！");
                    exit(
                        json_encode(
                            array(
                                'status' => 0, 
                                'msg' => 'sku扣减库存失败！')));
                }
            } elseif ($skuIntegralInfo['remain_num'] != "-1" &&
                 $skuIntegralInfo['remain_num'] <= '0') {
                M()->rollback();
                log_write("库存不足！");
                exit(
                    json_encode(
                        array(
                            'status' => 0, 
                            'msg' => '库存不足！')));
            }
        } else {
            if ($goods['remain_num'] > 0) {
                $data = array(
                    'remain_num' => array(
                        'exp', 
                        "remain_num-1"));
                $res = M("tgoods_info")->where(
                    array(
                        'goods_id' => $goods['goods_id']))->save($data);
                if (! $res) {
                    M()->rollback();
                    log_write(
                        "{:L('INTEGRAL_NAME')}商城扣减库存失败，{:L('INTEGRAL_NAME')}流水id=" .
                             $res);
                    exit(
                        json_encode(
                            array(
                                'status' => 0, 
                                'msg' => '扣减库存失败！')));
                }
            } elseif ($goods['remain_num'] != '-1' &&
                 $skuIntegralInfo['remain_num'] <= '0') {
                M()->rollback();
                log_write("库存不足！");
                exit(
                    json_encode(
                        array(
                            'status' => 0, 
                            'msg' => '库存不足！')));
            }
        }
        // 库存扣减成功tgoods_info 增加兑换量
        $goodsId = $goods['goods_id'];
        $res = M("tgoods_info")->where("goods_id='$goodsId'")->setInc(
            'exchange_num');
        if (! $res) {
            M()->rollback();
            log_write(
                "{:L('INTEGRAL_NAME')}商城已兑换商品数量增加失败，{:L('INTEGRAL_NAME')}流水id=" .
                     $res);
            exit(
                json_encode(
                    array(
                        'status' => 0, 
                        'msg' => L('INTEGRAL_NAME') . "商城已兑换商品数量增加失败")));
        }
        // 添加订单记录(参考storeAction下direct_order_save()中的订单添加)
        $order_id = date('ymd') . substr(time(), - 3) . substr(microtime(), 2, 
            7);
        $reesidual_integral = $member['point'] - $deduction;
        $order_data = array(
            'order_id' => $order_id, 
            'member_id' => $member['id'], 
            'order_type' => $delivery, 
            'from_channel_id' => $label_id, 
            'from_batch_no' => $this->batch_id, 
            'batch_no' => $this->batch_id, 
            'buy_num' => '1', 
            'order_amt' => $deduction, 
            'batch_channel_id' => $label_id, 
            'node_id' => $this->node_id, 
            'add_time' => date('YmdHis'), 
            'order_status' => '0', 
            'order_phone' => $c_phone, 
            'receiver_citycode' => $all_path, 
            'delivery_status' => '4', 
            'pay_status' => '2', 
            'goods_name' => $goods['goods_name'], 
            'reesidual_integral' => $reesidual_integral, 
            'parm1' => I('post.memo'), 
            'receiver_phone' => $receiver_phone);
        // 物流
        if ($delivery == '1') {
            $order_data['receiver_type'] = $delivery;
            $order_data['receiver_name'] = trim($receiver_name);
            $order_data['receiver_addr'] = trim($receive_address);
            $order_data['delivery_status'] = '1';
        }
        
        $tintegral_order_info_id = M("tintegral_order_info")->add($order_data);
        if ($tintegral_order_info_id === false) {
            M()->rollback();
            log_write("添加订单失败！");
            exit(
                json_encode(
                    array(
                        'status' => 0, 
                        'msg' => '添加订单失败！')));
        }
        // 子订单处理
        $child_order_id = date('ymd') . substr(time(), - 3) .
             substr(rand(11111, 99999)) . $bid;
        $row['order_id'] = $order_id;
        $row['trade_no'] = $child_order_id;
        $row['b_id'] = $bid;
        $row['b_name'] = $goods['goods_name'];
        $row['goods_num'] = '1';
        $row['price'] = $deduction;
        $row['amount'] = $deduction;
        $row['receiver_type'] = $delivery;
        $res = M('tintegral_order_info_ex')->add($row);
        if (! $res) {
            M()->rollback();
            log_write("添加子订单失败！");
            exit(
                json_encode(
                    array(
                        'status' => 0, 
                        'msg' => '添加子订单失败！')));
        }
        // 扣出会员积分
        $integral = D('IntegralPointTrace', 'Model');
        $res = $integral->integralPointChange(2, $deduction, $member['id'], 
            $this->node_id, $order_id);
        if (! $res) {
            M()->rollback();
            log_write("减" . L('INTEGRAL_NAME') . "失败！");
            exit(
                json_encode(
                    array(
                        'status' => 0, 
                        'msg' => "减" . L('INTEGRAL_NAME') . "失败！")));
        }
        // 增加行为数据
        $res = D("MemberBehavior")->addBehaviorType($member['id'], 
            $this->node_id, 9, $deduction, $this->batch_id);
        if ($res === false) {
            log_write("新增行为数据失败！会员ID：" . $member['id']);
        }
        // 自提
        if ($delivery == '0') {
            // 定单流水生成后，对用户进行支撑发码
            $re = M()->table("tintegral_goods_ex a")->join(
                'tbatch_info b ON a.m_id = b.m_id')
                ->field('b.id,b.batch_no')
                ->where("a.id = '$integral_id'")
                ->find();
            $mid = $this->id;
            log_write("积分活动ID" . $mid);
            $batch_no = $re['batch_no'];
            $bid = $re['id'];
            $appid_array = array(
                'df_openid' => $this->openid);
            $order_data['receiver_type'] = $delivery;
            $status = $this->sendCode($order_id, '', $this->node_id, $batch_no, 
                session('groupPhone'), $bid, $mid, $gift_phone = null, 
                $appid_array, $tintegral_order_info_id);
            if ($status == false) {
                M()->rollback();
                log_write("发码失败！");
                exit(
                    json_encode(
                        array(
                            'status' => 0, 
                            'msg' => '发码失败！')));
            }
        }
        M()->commit();
        session('groupCheckCode', null);
        exit(
            json_encode(
                array(
                    'status' => 1, 
                    'msg' => '恭喜你，兑换成功！')));
    }

    /**
     * Description of SkuService 生成静态页面规格值信息
     *
     * @param array $skuIdInfo sku上架id detail值 int $bId tbatch_info的id int $mId
     *            tmarketing_info的id
     * @return array b_id对应的skuId和中文信息
     * @author john_zeng
     */
    public function makeSkuOrderInfo($skuIdInfo, $bId = 0, $mId = 0) {
        $filter[] = "g.sku_detail_id in ('" . $skuIdInfo . "')";
        if ($bId > 0)
            $filter[] = "s.b_id = " . $bId;
        if ($mId > 0)
            $filter[] = "s.m_id = " . $mId;
        $goodsInfo = M()->table("tecshop_goods_sku s")->join(
            'tgoods_sku_info g on s.skuinfo_id = g.id')
            ->field('s.id, s.sale_price, g.sku_detail_id')
            ->where($filter)
            ->find();
        if ($goodsInfo) {
            $skuName = $this->getSkuTypeName($goodsInfo['sku_detail_id']);
            $goodsInfo['sku_name'] = $skuName;
            $goodsInfo['batch_amt'] = $goodsInfo['sale_price'];
        }
        return $goodsInfo;
    }

    /**
     * Description of SkuService 取得上架sku商品规格中文名
     *
     * @param string $strId skudetail ID
     * @return string 规格中文信息
     * @author john_zeng
     */
    public function getSkuTypeName($strId) {
        $tmp_array = explode('#', $strId);
        $tmp_info = array();
        if (is_array($tmp_array)) {
            foreach ($tmp_array as $val) {
                $skuInfo = M('tgoods_sku_integral_detail')->where(
                    array(
                        'id' => $val))
                    ->field('sku_detail_name')
                    ->find();
                $tmp_info[] = $skuInfo['sku_detail_name'];
            }
        }
        
        return implode('/', $tmp_info);
    }

    /**
     * 发送验证码
     */
    public function sendCheckCode() {
        // 图片校验码
        /*
         * $verify = I('post.verify',null,'mysql_real_escape_string');
         * if(session('verify') != md5($verify)) { $this->error("图片动态密码错误"); }
         */
        $phoneNo = I('post.phone', null);
        if (! check_str($phoneNo, 
            array(
                'null' => false, 
                'strtype' => 'mobile'), $error)) {
            $this->error("手机号{$error}");
        }
        // 测试环境不下发，验证码直接为1111
        if (! is_production()) {
            $groupCheckCode = array(
                'number' => 1111, 
                'add_time' => time(), 
                'phoneNo' => $phoneNo);
            session('groupCheckCode', $groupCheckCode);
            $this->ajaxReturn("success", "验证码已发送", 1);
        }
        // 发送频率验证
        $groupCheckCode = session('groupCheckCode');
        if (! empty($groupCheckCode) &&
             (time() - $groupCheckCode['add_time']) < $this->expiresTime) {
            $this->error('动态密码发送过于频繁!');
        }
        $num = mt_rand(1000, 9999);
        $exptime = $this->CodeexpiresTime / 60;
        $text = "您在{$this->node_short_name}商户的动态密码是：{$num}，有效期{$exptime}分钟。如非本人操作请忽略。";
        // 通知支撑
        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
                                                                   // 请求参数
        $req_array = array(
            'NotifyReq' => array(
                'TransactionID' => $TransactionID, 
                'ISSPID' => C('MOBILE_ISSPID'), 
                'SystemID' => C('ISS_SYSTEM_ID'), 
                'SendLevel' => '1', 
                'Recipients' => array(
                    'Number' => $phoneNo),  // 手机号
                'SendClass' => 'SMS', 
                'MessageText' => $text,  // 短信内容
                'Subject' => '', 
                'ActivityID' => C('ALIPAY_NOTICE_ACTIVITYID'), 
                'ChannelID' => '', 
                'ExtentCode' => ''));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $resp_array = $RemoteRequest->requestIssServ($req_array);
        $ret_msg = $resp_array['NotifyRes']['Status'];
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
             $ret_msg['StatusCode'] != '0001')) {
            $this->error('发送失败');
        }
        $groupCheckCode = array(
            'number' => $num, 
            'add_time' => time(), 
            'phoneNo' => $phoneNo);
        session('groupCheckCode', $groupCheckCode);
        $this->success('动态密码已发送');
    }

    public function sku_kc() {
        $sku_detail_id = I('post.sku_info');
        $sku_info = strtr($sku_detail_id, ",", "#");
        $skuIntegralInfo = M("tgoods_sku_integral_info")->where(
            array(
                'sku_detail_id' => $sku_info))->find();
        if ($skuIntegralInfo['remain_num'] != "-1" &&
             $skuIntegralInfo['remain_num'] <= '0') {
            exit(
                json_encode(
                    $mess = array(
                        'info' => '库存不足', 
                        'status' => '0')));
        }
        exit(
            json_encode(
                $mess = array(
                    'info' => '提交订单', 
                    'status' => '1')));
    }
    
    // 商城名称
    public function _integralName() {
        $integral_name = M("tintegral_node_config")->where(
            array(
                'node_id' => $this->node_id))->getField('integral_name');
        if ($integral_name) {
            L('INTEGRAL_NAME', $integral_name);
        } else {
            L('INTEGRAL_NAME', '积分');
        }
    }
}

   