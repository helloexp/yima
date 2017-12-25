<?php

class WfxShopAction extends Action {

    public $pageSize = 10;

    private $nodeId;

    public function __construct() {
        parent::__construct();
        $node_id = I('node_id');
        if ($_SESSION['node_id'] == '') {
            $_SESSION['node_id'] = $node_id;
        }
        $this->nodeId = $_SESSION['node_id'];
        
        // 判断登录
        if (! isset($_SESSION['groupPhone'])) {
            redirect(
                U('Label/Member/index', 
                    array(
                        'node_id' => $node_id)));
            exit();
        }
        
        $this->assign('node_id', $_SESSION['node_id']);
    }

    /**
     * 清除cookie
     */
    public function delSelectGoods() {
        cookie('WfxBookOrder', 'null', - 1);
        echo U('Label/WfxShop/shopGoods', 
            array(
                'node_id' => $_SESSION['node_id']));
    }

    /**
     * 分销订货商品
     */
    public function shopGoods() {
        $this->assign('title', '找商品');
        
        $nodeWhere = " e.node_id='" . $this->node_id .
             "' AND b.status='0' AND ((b.end_time>='" . date('YmdHis') .
             "' AND b.begin_time<='" . date('YmdHis') .
             "') or (e.purchase_time_limit = '1' and b.end_time >= '" .
             date('YmdHis') . "'))";
        
        $searchWords = I('keywords', '0', 'string');
        
        $nowPage = empty($_GET['p']) ? 1 : $_GET['p'];
        if ($nowPage == 1) {
            cookie('WfxBookOrder', 'null', - 1);
        }
        if ($searchWords != '0') {
            $nodeWhere .= " AND b.batch_short_name like '%" . $searchWords . "%'";
            $nexUrl = U('Label/WfxShop/shopGoods', 
                array(
                    'keywords' => $searchWords, 
                    'p' => ($nowPage + 1)));
            $this->assign('searchWords', $searchWords);
        } else {
            $nexUrl = U('Label/WfxShop/shopGoods', 
                array(
                    'p' => ($nowPage + 1)));
        }
        $count = M('tecshop_goods_ex e ')->join("tbatch_info b ON b.id=e.b_id")
            ->join("tgoods_info t on t.goods_id=b.goods_id")
            ->where($nodeWhere)
            ->count();
        
        import('ORG.Util.Page');
        $Page = new Page($count, $this->pageSize);
        if ($_GET['p'] > ceil($count / $this->pageSize) && $this->isAjax()) {
            return;
        }
        
        $goods = M('tecshop_goods_ex e ')->join("tbatch_info b ON b.id=e.b_id")
            ->join("tgoods_info t on t.goods_id=b.goods_id")
            ->join('tmarketing_info tmi ON tmi.id = e.m_id')
            ->where($nodeWhere)
            ->field(
            'e.m_id, e.b_id, b.batch_short_name, b.batch_img, b.batch_amt, tmi.batch_type, t.is_sku, t.goods_id')
            ->order("e.is_commend asc,b.id desc ")
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        
        $skuService = D('Sku', 'Service');
        foreach ($goods as $key => $val) {
            if ($val['is_sku'] == '1') {
                $goodsInfo = $skuService->makeGoodsListInfo($val, 
                    $_SESSION['node_id']);
                if ($goodsInfo['market_price'] != '') {
                    $goods[$key]['batch_amt'] = $goodsInfo['market_price'];
                }
            }
        }
        
        $this->assign('nextUrl', $nexUrl);
        $this->assign('goods', $goods);
        $this->display();
    }

    /**
     * 订货商品详情
     */
    public function GoodsDetail() {
        $this->assign('title', '商品详情');
        if (cookie('WfxBookOrder') == '' || cookie('WfxBookOrder') == 'null') {
            $this->assign('goods', 'no');
        }
        $marketingInfoId = I('get.id', '0', 'string');
        $type = I('get.type', '0', 'string');
        $wfxModel = D('Wfx');
        $goodsDetail = $wfxModel->getOneSellingGoodInfo($marketingInfoId, $type);
        if ($goodsDetail['is_sku'] == '1') {
            $skuObj = D('Sku', 'Service');
            $skuInfoList = $skuObj->getSkuEcshopList($marketingInfoId, 
                $this->nodeId);
            $goods_sku_list = $skuObj->getReloadSku($skuInfoList);
            $goodsSkuDetailList = $skuObj->getSkuDetailList(
                $goods_sku_list['list']);
            $goodsSkuTypeList = $skuObj->getSkuTypeList($goodsSkuDetailList);
            $skuArray = array();
            foreach ($goodsSkuTypeList as $typeVal) {
                foreach ($goodsSkuDetailList as $detailVal) {
                    $tmpArray = array();
                    if ($typeVal['id'] == $detailVal['sku_id']) {
                        $tmpArray['id'] = $detailVal['id'];
                        $tmpArray['sku_name'] = $detailVal['sku_detail_name'];
                        $skuArray[$typeVal['sku_name']][] = $tmpArray;
                    }
                }
            }
            $this->assign('skuArray', $skuArray);
            $priceStr = '{';
            foreach ($skuInfoList as $val) {
                $priceStr .= '"' . $val['sku_detail_id'] . '": {"price": "' .
                     $val['sale_price'] . '","storage":"' . $val['remain_num'] .
                     '"}, ';
            }
            $priceStr .= '}';
        } else {
            $priceStr = '""';
        }
        $this->assign('priceStr', $priceStr);
        $this->assign('goodsDetail', $goodsDetail);
        $this->display();
    }

    /**
     * 订货商品cookie存储
     */
    public function addBookOrder() {
        $result = array(
            'error' => '0', 
            'msg' => '添加成功');
        $wfxBookOrderArray = cookie('WfxBookOrder');
        if ($wfxBookOrderArray != 'null') {
            $wfxBookOrderArray = json_decode($wfxBookOrderArray, TRUE);
            foreach ($wfxBookOrderArray as $key => $val) {
                if ($val['goodsId'] != $_POST['goodsId']) {
                    $result['error'] = '1001';
                    $result['msg'] = '请先将之前的订货单结清！';
                    $this->ajaxReturn($result);
                    exit();
                }
            }
        }
        $wfxBookOrderArray[] = $_POST;
        $wfxBookOrderArray = json_encode($wfxBookOrderArray);
        cookie('WfxBookOrder', $wfxBookOrderArray, 24 * 3600);
        $this->ajaxReturn($result);
    }

    /**
     * 订货商品列表
     */
    public function showBookOrderList() {
        $this->assign('title', '确认订单');
        $goodsArray = json_decode(cookie('WfxBookOrder'), TRUE);
        $result = array();
        
        $wfxModel = D('Wfx');
        $skuService = D('Sku', 'Service');
        foreach ($goodsArray as $val) {
            if ($val['classType'] != '') {
                if (array_key_exists($val['classType'], $result)) {
                    $result[$val['classType']]['count'] += $val['count'];
                } else {
                    $result[$val['classType']] = $val;
                    $goodsInfo = $wfxModel->getOneSkuGoodsInfo(
                        $val['classType'], $val['goodsId']);
                    $result[$val['classType']]['goods_info'] = $goodsInfo;
                    
                    $classType = $skuService->getSkuDetailList(
                        explode('#', $val['classType']));
                    $result[$val['classType']]['class_type'] = $classType;
                }
            } else {
                $tmpKeyName = $val['goodsId'] . 'goods';
                if (array_key_exists($val['goodsId'], $result)) {
                    $result[$val['goodsId']]['count'] += $val['count'];
                } else {
                    $result[$val['goodsId']]['count'] = $val['count'];
                    $goodsInfo = $wfxModel->getOneSellingGoodInfo(
                        $val['goodsId'], $val['goodsType']);
                    $goodsInfo['goods_image'] = $goodsInfo['goods_img'];
                    $result[$val['goodsId']]['goods_info'] = $goodsInfo;
                    $result[$val['goodsId']]['goods_info']['sale_price'] = $result[$val['goodsId']]['goods_info']['group_price'];
                }
            }
        }
        $this->assign('goodsInfo', $goodsInfo);
        $this->assign('goods', $result);
        
        // 地址
        $phoneAddressModel = D('TphoneAddress');
        $addrId = I('get.addr');
        if ($addrId) {
            $lastAddress = $phoneAddressModel->getDefinedPhoneAddress($addrId);
        } else {
            $lastAddress = $phoneAddressModel->getLastPhoneAddress();
        }
        $this->assign('addressInfo', $lastAddress);
        
        $selectAddrUrl = U('Label/MyAddress/addressList', 
            array(
                'type' => 'wfxBook'));
        $this->assign('selectAddrUrl', $selectAddrUrl);
        $this->display();
    }

    /**
     * 提交订单入库
     */
    public function commitBookOrder() {
        $result = array(
            'error' => '0', 
            'msg' => '添加订单成功');
        $tempBookOrderArray = array();
        
        if (is_array($_POST['goods'])) {
            $goosTempArray = $_POST['goods'];
        } else {
            $result['error'] = '1001';
            $result['msg'] = '请勿非法提交';
            $this->ajaxReturn($result);
            exit();
        }
        
        // 地址信息
        $addrId = (int) I('post.addrId');
        $phoneAddressModel = D('TphoneAddress');
        $addrInfo = $phoneAddressModel->getDefinedPhoneAddress($addrId);
        
        // 购买数量
        $countArray = explode('&', urldecode($_POST['count']));
        $countResult = array();
        foreach ($countArray as $countVal) {
            $countTemp = explode('=', $countVal);
            $countResult[$countTemp[0]] = $countTemp[1];
        }
        
        // 主订单入库
        $order_id = get_sn();
        $bookOrderSaveData = array();
        $bookOrderSaveData['order_id'] = $order_id;
        $bookOrderSaveData['order_phone'] = $_SESSION['groupPhone'];
        $bookOrderSaveData['node_id'] = $_SESSION['node_id'];
        $bookOrderSaveData['receiver_name'] = $addrInfo['user_name'];
        $bookOrderSaveData['receiver_citycode'] = $addrInfo['path'];
        $bookOrderSaveData['receiver_addr'] = $addrInfo['address'];
        $bookOrderSaveData['receiver_phone'] = $addrInfo['phone_no'];
        $bookOrderSaveData['add_time'] = date('YmdHis');
        M()->startTrans();
        $orderId = M('twfx_book_order')->add($bookOrderSaveData);
        if (! $orderId) {
            log_write('分销订货订单：' . M()->_sql());
            $result['error'] = '20001';
            $result['msg'] = '数据库锁定，无法插入数据！';
            $this->ajaxReturn($result);
            exit();
        }
        
        // 自订单及减库存操作
        $wfxModel = D('Wfx');
        foreach ($goosTempArray as $goodsVal) {
            $singleGoodsArray = array();
            
            $goodsArray = explode('*', $goodsVal);
            $isSku = M()->table("tmarketing_info tmi")->join(
                'tbatch_info tbi ON tbi.m_id = tmi.id')
                ->join('tgoods_info tgi ON tgi.goods_id = tbi.goods_id')
                ->where(array(
                'tmi.id' => $goodsArray[1]))
                ->getfield('tgi.is_sku');
            
            if ($isSku == '1') {
                $skuService = D('Sku', 'Service');
                $goodsInfo = $wfxModel->getOneSkuGoodsInfo($goodsArray[0], 
                    $goodsArray[1]);
                $classStr = '';
                $classType = $skuService->getSkuDetailList(
                    explode('#', $goodsArray[0]));
                foreach ($classType as $classVal) {
                    $classStr .= $classVal['sku_name'] . ':' .
                         $classVal['sku_detail_name'] . ';';
                }
                
                $singleGoodsArray['sku_info'] = $goodsArray[0];
                $singleGoodsArray['sku_desc'] = substr($classStr, 0, 
                    strlen($classStr) - 1);
                
                $singleGoodsArray['marketing_info_id'] = $goodsArray[1];
                $singleGoodsArray['price'] = $goodsInfo['sale_price'];
                $singleGoodsArray['amount'] = $goodsInfo['sale_price'] *
                     $countResult[$goodsArray[0]];
            } else {
                $goodsInfo = $wfxModel->getOneSellingGoodInfo($goodsArray[0]);
                $goodsInfo['tbi_storage'] = $goodsInfo['storage_num'];
                
                $singleGoodsArray['marketing_info_id'] = $goodsArray[0];
                $singleGoodsArray['price'] = $goodsInfo['group_price'];
                $singleGoodsArray['amount'] = $goodsInfo['group_price'] *
                     $countResult[$goodsArray[0]];
            }
            
            if ($goodsInfo['remain_num'] == '-1' ||
                 $countResult[$goodsArray[0]] <= $goodsInfo['remain_num']) {
                $singleGoodsArray['book_order'] = $order_id;
                $singleGoodsArray['count'] = $countResult[$goodsArray[0]];
                $singleGoodsArray['add_time'] = date('YmdHis');
                $resultId = M('twfx_book_order_info')->add($singleGoodsArray);
                if ($resultId) {
                    // 减库存
                    if ($goodsInfo['tbi_storage'] != '-1') {
                        M('tbatch_info')->where(
                            array(
                                'id' => $goodsInfo['b_id']))->setDec('remain_num', 
                            $countResult[$goodsArray[0]]);
                    }
                    
                    if ($isSku == '1' && $goodsInfo['remain_num'] != '-1') {
                        M('tecshop_goods_sku')->where(
                            array(
                                'id' => $goodsInfo['id']))->setDec('remain_num', 
                            $countResult[$goodsArray[0]]);
                    }
                    
                    // if($goodsInfo['storage_type'] == '1'){
                    // M('tgoods_info')->where(array('goods_id'=>$goodsInfo['goods_id']))->setDec('remain_num',
                    // $countResult[$goodsArray[0]]);
                    // }
                } else {
                    log_write('分销订货子订单：' . M()->_sql());
                    M()->rollback();
                    $result['error'] = '3001';
                    $result['msg'] = '数据库出错，无法写入！';
                    $this->ajaxReturn($result);
                    exit();
                }
            } else {
                M()->rollback();
                $result['error'] = '9001';
                $result['msg'] = $goodsInfo['name'] . $classStr . '库存不足；';
                $this->ajaxReturn($result);
                exit();
            }
        }
        
        cookie('WfxBookOrder', $tempBookOrderArray, - 1);
        M()->commit();
        $this->ajaxReturn($result);
    }

    public function bookOrder() {
        $orderListResult = array();
        
        $wfxModel = D('Wfx');
        $orderList = $wfxModel->getBookOrderList($this->nodeId, 
            $_SESSION['groupPhone']);
        
        foreach ($orderList as $key => $val) {
            $tempArray = array();
            
            if (! array_key_exists($val['order_id'], $orderListResult)) {
                $orderListResult[$val['order_id']] = $val;
                $orderListResult[$val['order_id']]['totalMoney'] = $val['count'] *
                     $val['price'];
                $orderListResult[$val['order_id']]['totalCount'] = $val['count'];
            } else {
                $orderListResult[$val['order_id']]['totalMoney'] += $val['count'] *
                     $val['price'];
                $orderListResult[$val['order_id']]['totalCount'] += $val['count'];
            }
            
            $tempArray = array(
                'price' => $val['price'], 
                'count' => $val['count'], 
                'skuInfo' => $val['sku_info']);
            
            if ($val['sku_info'] == '') {
                $goodInfo = $wfxModel->getOneSellingGoodInfo(
                    $val['marketing_info_id']);
                $tempArray['name'] = $goodInfo['name'];
                $tempArray['img'] = $goodInfo['goods_img'];
            } else {
                $goodInfo = $wfxModel->getOneSkuGoodsInfo($val['sku_info'], 
                    $val['marketing_info_id']);
                $tempArray['name'] = $goodInfo['name'];
                $tempArray['img'] = $goodInfo['goods_image'];
                $tempArray['sku_desc'] = $val['sku_desc'];
            }
            $orderListResult[$val['order_id']]['goodsInfo'][] = $tempArray;
        }
        $this->assign('orderList', $orderListResult);
        $this->display();
    }

    public function shippingInfo() {
        $this->assign('title', '物流信息');
        $orderId = I('get.order');
        $orderInfo = M()->table("twfx_book_order twbo")->join(
            "torder_express_info toei ON toei.order_id = twbo.order_id AND type = '4' ")
            ->where(array(
            'twbo.order_id' => $orderId))
            ->field(
            'twbo.delivery_number, twbo.delivery_company, twbo.delivery_status, toei.express_content')
            ->find();
        
        $orderInfo['express_content'] = json_decode(
            $orderInfo['express_content'], TRUE);
        $this->assign('orderInfo', $orderInfo);
        $this->display();
    }
}
