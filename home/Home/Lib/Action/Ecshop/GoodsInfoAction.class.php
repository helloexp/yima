<?php

class GoodsInfoAction extends BaseAction {

    public $GOODS_TYPE = "6";

    public $BATCH_TYPE = "31";

    public $img_path = "";

    public $tmp_path = "";

    /**
     *
     * @var GoodsInfoModel
     */
    public $GoodsInfoModel;

    /**
     * @var SkuService
     */
    public $SkuService;

    public $picturePath = '';

    //新增函数
    public $classify_arr = array();
    public $goods_status_arr = array('0' => '已上架', '1' => '已下架', '2' => '已过期','3' =>'已售罄');

    //渠道类型
    public static $channelType = 4;
    public static $channelSnsType= 46;

    //积分权限
    public $isInterGral = false;

    //开通自定义短信的标志
    public $startUp = 0;

    public $orderArray = array(
        '1' => '月',
        '2' => '周',
        '3' => '日');

    public function _initialize() {
        parent::_initialize();
        // 判断用户权限
        $hasEcshop = $this->_hasMoonDayEcshop();
        $node_info = M('tnode_info')->where(
            array(
                'node_id' => $this->node_id))->find();
        //自定义短信标志
        $this->startUp = $node_info['custom_sms_flag'];

        $path_arr = C('BATCH_IMG_PATH_NAME');
        $img_path = $path_arr[$this->BATCH_TYPE] . '/' . $this->node_id . '/';
        $this->img_path = $img_path;

        //查询分类信息
        $map = array('node_id' => $this->nodeId);
        $this->classify_arr = M('tecshop_classify')->where($map)->order('sort asc')->getField('id, class_name', true);
        $this->assign('classify_arr', $this->classify_arr);
        //实例化对象
        $this->GoodsInfoModel = D('GoodsInfo');
        $this->SkuService = D('Sku', 'Service');
        //传递nodeId值
        $this->GoodsInfoModel->nodeId = $this->node_id;
        $this->SkuService->nodeId = $this->node_id;
        $this->GoodsInfoModel->userId = $this->userId;
        //商品状态
        $this->assign('goods_status_arr', $this->goods_status_arr);
        // 临时路径
        $tmp_path = 'Upload/img_tmp' . '/' . $this->node_id . '/';
        $this->picturePath = C('TMPL_PARSE_STRING.__UPLOAD__');
        $this->assign('picturePath', $this->picturePath);

        // 判断积分权限
        $this->isInterGral = $this->_hasIntegral($this->node_id);
        // 取得积分规则信息
        $intergralType = D('SalePro', 'Service')->getNodeRule($this->node_id, 'tintegral_rule_main');
        if ('0' === $intergralType){
            $this->isInterGral = false;
        }

        $this->assign('isIntergral', $this->isInterGral); // 积分使用权限
        $this->assign('img_path', $img_path);
        $this->assign('tmp_path', $tmp_path);
    }

    public function index() {
        $data = $_REQUEST;

        $map = array(
            'node_id' => $this->node_id,
            'goods_type' => 6);

        $goods_name = I('goods_name', null);
        if ($goods_name != "") {
            $map['goods_name'] = array(
                'like',
                "%$goods_name%");
        }

        $goodsCustomer = I('goods_customer', null);
        if ($goodsCustomer != '') {
            $map['customer_no'] = array(
                'like',
                "%$goodsCustomer%");
            $this->assign('goods_customer', $goodsCustomer);
        }

        $mapcount = M('tgoods_info')->where($map)->count();
        import('ORG.Util.Page'); // 导入分页类
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        foreach ($data as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        $show = $Page->show(); // 分页显示输出
        $list = M('tgoods_info')->where($map)
            ->order('id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        // 创建sku信息
        $skuObj = D('Sku', 'Service');
        foreach ($list as &$val) {
            // sku商品
            if ("1" === $val['is_sku']) {
                $val = $skuObj->makeGoodsListInfo($val, $this->node_id);
            }
        }
        if ($this->node_id == C("cnpc_gx.node_id")) {
            $merchantInfo = M()->table("tfb_cnpcgx_goods a")
                ->join('tfb_cnpcgx_node_info b on a.merchant_id=b.id')
                ->field('a.goods_no,b.merchant_name')
                ->select();
            $merchant = array();
            foreach ($merchantInfo as $vo) {
                $merchant[$vo['goods_no']] = $vo['merchant_name'];
            }
            $this->assign("merchant", $merchant);
        }
        $this->assign("list", $list);
        $this->assign('page', $show); // 赋值分页输出
        $this->assign("goods_name", $goods_name);
        $this->display();
    }

    public function add() {
        if ($this->isPost()) {
            $error = '';
            $name = I('post.name', null);
            //检查商品名称是否符合规则
            $rulesInfo = '/[\'.,:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/';
            if(preg_match($rulesInfo, $name)){
                $this->error("商品名称含有特殊字符，提交失败");
            }
            // sku价格列表
            $skuPrice = json_decode(
                html_entity_decode(I('post.data_price_info', null)), true);
            // sku别名列表
            $skuName = json_decode(
                html_entity_decode(I('post.data_name_info', null)), true);
            $isOrder = I('post.dg', null); // 是否为订购商品 1 为非订购 2 为订购
            $msgConfig = 0; // 默认自动发送短信关闭
            $sendTime = ''; // 短信告知时间默认为空
            $configData = ''; // 大数据字段
            $isSku = 0;
            // 订单信息
            if ('2' === $isOrder) {
                // 自动短信通知开关
                if ('on' === I('msgConfig', null)) {
                    $msgConfig = 1;
                    $sendTime = (int) I('sendTime', null); // 短信告知时间
                }
                $orderType = D('Sku', 'Service')->getCycleType($skuName);
                if (false === $orderType)
                    $this->error("请配置订购周期",
                        array(
                            '返回' => "javascript:history.go(-1)"));
                // 将订购信息放入大字段中，以cycle为订购节点
                $configData = array(
                    'cycle' => array(
                        'cycle_type' => $orderType,  // 配送周期
                        'cycle_notice_falg' => $msgConfig,  // 短信告知
                        'cycle_notice_before_day' => (int) $sendTime)); // 短信告知时间

                $isSku = 1; // 将订购作为SKU商品处理
            }
            $countNum = I('post.count_num', null);
            if (! check_str($name,
                array(
                    'null' => false,
                    'maxlen_cn' => '32'), $error)) {
                $this->error("商品名称{$error}",
                    array(
                        '返回' => "javascript:history.go(-1)"));
            }
            $dataInfo = M('tgoods_info')->where(
                "node_id='{$this->nodeId}' AND name='{$name}' and goods_type='" .
                $this->GOODS_TYPE . "'")->find();
            if ($dataInfo)
                $this->error('商品名称已经存在');
            $goodsDesc = I('post.goods_desc', null);
            if (! check_str($goodsDesc,
                array(
                    'null' => false,
                    'maxlen_cn' => '200'), $error)) {
                $this->error("商品描述{$error}",
                    array(
                        '返回' => "javascript:history.go(-1)"));
            }
            /*
             * $startDate = I('post.start_time',null);
             * if(!check_str($startDate,array('null'=>false,'strtype'=>'datetime','format'=>'Ymd'),$error)){
             * $this->error("活动开始时间{$error}"); } $endDate =
             * I('post.end_time',null);
             * if(!check_str($endDate,array('null'=>false,'strtype'=>'datetime','format'=>'Ymd'),$error)){
             * $this->error("活动结束时间{$error}"); } if($endDate < date('Ymd'))
             * $this->error('活动截止日期不能小于当前日期'); if($endDate > '20140930')
             * $this->error('活动截止日期不能大于9月30日');
             */
            $goodsImg = str_replace('..', '', I('post.img_resp'));
            if (! check_str($goodsImg,
                array(
                    'null' => false), $error)) {
                $this->error("商品图片{$error}",
                    array(
                        '返回' => "javascript:history.go(-1)"));
            }
            /*
             * //移动图片 $img_move = move_batch_image($goodsImg,31,$this->node_id);
             * if($img_move !==true)
             * $this->error('商品图片上传失败！'.$goodsImg,array('返回'=>"javascript:history.go(-1)"));
             * $goodsImg = $this->img_path .$goodsImg;
             */
            /*
             * $groupGoodsName = I('post.group_goods_name',null);
             * if(!check_str($groupGoodsName,array('null'=>false,'maxlen_cn'=>'20'),$error)){
             * $this->error("商品名称{$error}"); } $goodsMemo =
             * I('post.goods_memo',null);
             * if(!check_str($goodsMemo,array('null'=>false,'maxlen_cn'=>'200'),$error)){
             * $this->error("商品描述{$error}"); }
             */
            $marketPrice = I('post.market_price', null);
            // 创建sku信息
            $skuObj = D('Sku', 'Service');
            $sku_infolist = $skuObj->getSkuPrice($skuPrice);
            if ($sku_infolist != false) {
                $marketPrice = $sku_infolist['price'];
                $isSku = 1;
                if ('-1' == $countNum) {
                    $goodsNum = - 1;
                    $numType = 0;
                } else {
                    $numType = 1;
                    $goodsNum = (int) $countNum;
                }
            } else {
                if (! check_str($marketPrice,
                    array(
                        'null' => false,
                        'strtype' => 'number',
                        'minval' => '0'), $error)) {
                    $this->error("商品价格{$error}",
                        array(
                            '返回' => "javascript:history.go(-1)"));
                }
                $numType = I('post.num_type', null);
                $goodsNum = I('post.goods_num', null);
                if ($numType == 0)
                    $goodsNum = - 1;
                else {
                    if (! check_str($goodsNum,
                        array(
                            'null' => false,
                            'strtype' => 'int',
                            'minval' => '0'), $error)) {
                        $this->error("商品总数{$error}",
                            array(
                                '返回' => "javascript:history.go(-1)"));
                    }
                }
            }
            /*
             * $deliveryFlag = I('post.delivery_flag',null); if($deliveryFlag ==
             * null) $deliveryFlag = 0; $sendFlag = I('post.send_flag',null);
             * if($sendFlag == null) $sendFlag = 0;
             * if(!check_str($mmsNote,array('null'=>false,'maxlen_cn'=>'100'),$error)){
             * $this->error("彩信内容{$error}"); }
             * if(!check_str($mmsTitle,array('null'=>false,'maxlen_cn'=>'10'),$error)){
             * $this->error("彩信标题{$error}"); } $printText =
             * I('post.print_text');
             * if(!check_str($printText,array('null'=>false,'maxlen_cn'=>'100'),$error)){
             * $this->error("彩信标题{$error}"); }
             */
            $mmsNote = I('post.mms_text');
            $mmsTitle = I('post.mms_title');
            $printText = I('post.print_text');
            if ($printText == '')
                $printText = "凭小票可兑换1份" . $name;

            $groupTypeId = I('shop', true);
            $groupIdInfo = I('openStores', true);
            if ('2' != $groupTypeId) {
                $nodeIn = $this->nodeIn(null, true);
            }else{
                $nodeIn = $this->nodeIn();
            }

            // 开启事务
            M()->startTrans();
            //创建终端组
            $groupId = D('GoodsInfo')->createSupport($name, $nodeIn, $groupTypeId, $groupIdInfo);
            if(false === $groupId){
                M()->rollback();
                $this->error(D('GoodsInfo')->getError());
            }
            // //结束事务
            // $goodsM->commit();
            // 支撑创建活动
            $req_array = array(
                'ActivityCreateReq' => array(
                    'SystemID' => C('ISS_SYSTEM_ID'),
                    'ISSPID' => $this->node_id,
                    'RelationID' => $this->node_id,
                    'TransactionID' => date("YmdHis") . mt_rand(100000, 999999),  // 请求单号
                    'ActivityInfo' => array(
                        'CustomNo' => '',
                        'ActivityName' => iconv("utf-8", "gbk", $name),
                        'ActivityShortName' => iconv("utf-8", "gbk", $name),
                        'BeginTime' => date('Ymd') . '000000',
                        'EndTime' => '20301231235959',
                        'UseRangeID' => $groupId),
                    'VerifyMode' => array(
                        'UseTimesLimit' => 1,
                        'UseAmtLimit' => 0),
                    'GoodsInfo' => array(
                        'GoodsName' => iconv("utf-8", "gbk", $name),
                        'GoodsShortName' => iconv("utf-8", "gbk", $name)),
                    'DefaultParam' => array(
                        'PasswordTryTimes' => 3,
                        'PasswordType' => '')));
            $resp_array = D('GoodsInfo')->createActivity($req_array);
            $batchNo = $resp_array['ActivityCreateRes']['Info']['ActivityID'];
            // 取得合约号
            $treatyId = isset(
                $resp_array['ActivityCreateRes']['Info']['pGoodsId']) ? $resp_array['ActivityCreateRes']['Info']['pGoodsId'] : 0;
            if (0 === $treatyId) {
                M()->rollback();
                $this->error("创建合约失败",
                    array(
                        '返回' => "javascript:history.go(-1)"));
            }
            $goods_id = get_goods_id();
            // 创建tgoods_info数据
            $goods_data = array(
                'goods_id' => $goods_id,
                'goods_name' => $name,
                'goods_desc' => $goodsDesc,
                'goods_image' => $goodsImg,
                'node_id' => $this->nodeId,
                'user_id' => $this->userId,
                'pos_group' => $groupId,
                'pos_group_type' => $groupTypeId,
                'goods_type' => '6',  // 商品销售类型
                'market_price' => $marketPrice,
                // 'goods_amt'=> $marketPrice ,
                'storage_type' => $numType,
                'storage_num' => $goodsNum,
                'remain_num' => $goodsNum,
                'customer_no' => I('post.customer_no', '', 'string'),
                'mms_title' => $mmsTitle,
                'mms_text' => $mmsNote,
                'sms_text' => $mmsNote,
                'print_text' => $printText,
                'p_goods_id' => $treatyId,
                'validate_times' => 1,
                'begin_time' => date('Ymd') . '000000',
                'end_time' => '20301231235959',
                // 'send_begin_date' => $startDate.'000000',
                // 'send_end_date' => $endDate.'235959',
                // 'verify_begin_date' => $startDate.'000000',
                // 'verify_end_date' => $verify_end_time,
                // 'verify_begin_type' => '0',
                // 'verify_end_type' => $verify_time_type,
                'add_time' => date('YmdHis'),
                'status' => '0',
                'source' => '0',
                'is_sku' => $isSku,
                'is_order' => $isOrder,
                'config_data' => empty($configData) ? '' : json_encode(
                    $configData));
            $goodsId = M('tgoods_info')->data($goods_data)->add();
            if (! $goodsId) {
                M()->rollback();
                $this->error('系统出错,新建商品失败',
                    array(
                        '返回' => "javascript:history.go(-1)"));
            }
            if ($sku_infolist != false) {
                // 添加规格并重新生成规格信息
                $skuObj->checkSkuType($skuName, $this->nodeId, $goods_id);

                // 页面newid
                $newId_list = $skuObj->id_list;
                $result = $skuObj->checkSkuPro($skuPrice, $goods_id);

                if ($result['msg'] === false) {
                    M()->rollback();
                    $this->error($result['content']);
                }
            }
            $result = M('tgoods_info')->where("id={$goodsId}")->save(
                array(
                    'batch_no' => $batchNo));
            if (! $result) {
                M()->rollback();
                $this->error('系统出错,更新支撑活动号失败',
                    array(
                        '返回' => "javascript:history.go(-1)"));
            }
            /*
             * //更新tgoods_info库存 $goodsM = D('Goods'); $flag =
             * $goodsM->storagenum_reduc($goodsInfo['goods_id'], $goodsNum,
             * $bInfoId, '0', '新增扫码购'); if($flag === false){ M()->rollback();
             * $this->error('系统出错,更新tgoods_info库存失败'); }
             */
            /* 广西石油的话，更新对应商家 */
            if ($this->node_id == C("cnpc_gx.node_id")) {
                $this->update_cnpc_goods($goods_id, I('post.merchant_id'));
            }
            // 事务结束
            M()->commit();
            $this->success('新建商品成功！',
                array(
                    '返回商品列表' => U('index')));
        }
        if ($this->node_id == C("cnpc_gx.node_id")) {
            $merchantInfo = M()->table("tfb_cnpcgx_node_info")
                ->field('id,merchant_name')
                ->where("status=1")
                ->select();
            $this->assign("merchantInfo", $merchantInfo);
        }

        $this->display();
    }

    public function info() {
        $id = I('get.id', null);
        if (! $id)
            $this->error('商品编号不能为空');
        $goodsInfo = M('tgoods_info')->where(
            array(
                'id' => $id))->find();
        if (! $goodsInfo)
            $this->error('商品数据不存在');
        // 订购信息
        $cycleInfo = '';
        if ('2' == $goodsInfo['is_order']) {
            $cycleConfig = json_decode($goodsInfo['config_data'], true);
            if (isset($cycleConfig['cycle'])) {
                $cycleInfo = $cycleConfig['cycle'];
            }
        }
        // 创建sku信息
        $skuObj = D('Sku', 'Service');
        // 取得商品sku数据
        $goods_sku_info = $skuObj->getSkuInfoList($goodsInfo['goods_id'],
            $this->nodeId);
        if ($goods_sku_info) {
            $isSku = true;
        } else {
            $isSku = false;
        }
        // 分离商品表中的规格和规格值ID
        $goodsSkuList = $skuObj->getReloadSku($goods_sku_info);
        if (is_array($goodsSkuList['list']))
            $goodsSkuDetailList = $skuObj->getSkuDetailList(
                $goodsSkuList['list']);

        // 取得规格表信息
        if (is_array($goodsSkuDetailList)) {
            if (isset($cycleInfo['cycle_type'])) // 取得订购类型
                $goodsSkuTypeList = $skuObj->getSkuTypeList($goodsSkuDetailList,
                    $cycleInfo['cycle_type']);
            else
                $goodsSkuTypeList = $skuObj->getSkuTypeList($goodsSkuDetailList);
        }
        // 价格列表
        $skuDetail = $skuObj->makeSkuList($goods_sku_info);
        // 取得门店信息
        $storeList = '';
        $goodsM = D('Goods');
        if ('2' == $goodsInfo['pos_group_type']) {
            $storeList = $goodsM->getGoodsShop($goodsInfo['goods_id'], true,
                $this->nodeIn());
        }
        if ($this->node_id == C("cnpc_gx.node_id")) {
            $merchantInfo = M()->table("tfb_cnpcgx_goods a")
                ->join('tfb_cnpcgx_node_info b on a.merchant_id=b.id')
                ->field('a.goods_no,b.merchant_name')
                ->select();
            $merchant = array();
            foreach ($merchantInfo as $vo) {
                $merchant[$vo['goods_no']] = $vo['merchant_name'];
            }
            $this->assign("merchant", $merchant);
        }
        $this->assign("skutype",
            $skuObj->makeSkuType($goodsSkuTypeList, $goodsSkuDetailList));
        $this->assign("skudetail", $skuDetail);
        $this->assign('storeList', $storeList);
        $this->assign('cycleInfo', $cycleInfo); // 订购配置信息
        $this->assign('isSku', $isSku);
        $this->assign("goodsinfo", $goodsInfo);
        $this->display();
    }

    public function edit() {
        $id = i('request.id', null);
        if (! $id)
            $this->error('商品编号不能为空');
        $goodsInfo = M('tgoods_info')->where(
            array(
                'id' => $id))->find();
        if (! $goodsInfo)
            $this->error('商品数据不存在');

        // 订购信息
        $cycleInfo = '';
        if ('2' == $goodsInfo['is_order']) {
            $cycleConfig = json_decode($goodsInfo['config_data'], true);
            if (isset($cycleConfig['cycle'])) {
                $cycleInfo = $cycleConfig['cycle'];
            }
        }
        // 创建sku信息
        $skuObj = D('Sku', 'Service');
        // 取得商品sku数据
        $goods_sku_info = $skuObj->getSkuInfoList($goodsInfo['goods_id'], $this->nodeId);
        // 分离商品表中的规格和规格值ID
        $goodsSkuList = $skuObj->getReloadSku($goods_sku_info);
        // 取得规格值表信息
        $goodsSkuDetailList = '';
        if (is_array($goodsSkuList['list']))
            $goodsSkuDetailList = $skuObj->getSkuDetailList(
                $goodsSkuList['list']);

        // 取得规格表信息
        if (is_array($goodsSkuDetailList)) {
            if (isset($cycleInfo['cycle_type'])) // 取得订购类型
                $goodsSkuTypeList = $skuObj->getSkuTypeList($goodsSkuDetailList,
                    $cycleInfo['cycle_type']);
            else
                $goodsSkuTypeList = $skuObj->getSkuTypeList($goodsSkuDetailList);
        }

        // 获取门店信息
        if ($goodsInfo['pos_group_type'] == '2') {
            $oldStoreArr = array();
            $storeData = M('tgroup_pos_relation')->field('store_id')
                ->where("group_id={$goodsInfo['pos_group']}")
                ->select();
            foreach ($storeData as $v) {
                $oldStoreArr[] = $v['store_id'];
            }
            $oldStoreArr = array_unique($oldStoreArr);
        }
        if ($this->isPost()) {
            $error = '';
            /*
             * $name = I('post.name',null);
             * if(!check_str($name,array('null'=>false,'maxlen_cn'=>'32'),$error)){
             * $this->error("活动名称{$error}"); } $dataInfo =
             * M('tgoods_info')->where("node_id='{$this->nodeId}' AND
             * name='{$name}' and goods_type='".$this->GOODS_TYPE."'")->find();
             * if($dataInfo) $this->error('活动名称已经存在');
             */
            $goodsDesc = I('post.goods_desc', null);
            if (! check_str($goodsDesc,
                array(
                    'null' => false,
                    'maxlen_cn' => '200'), $error)) {
                $this->error("商品描述{$error}");
            }
            // sku价格列表
            $skuPrice = json_decode(
                html_entity_decode(I('post.data_price_info', null)), true);
            // sku别名列表
            $skuName = json_decode(
                html_entity_decode(I('post.data_name_info', null)), true);
            // sku商品总数
            $countNum = I('post.count_num', null);
            if (0 === count($skuName))
                $isSku = false;
            else
                $isSku = true;
            // 是否修改sku数据
            $is_change = false;
            // 检查规格是否变动
            $is_change = $skuObj->checkArray('id', 'id', $skuName,
                $goodsSkuTypeList);
            // 检查规格值是否变动
            if (false == $is_change) {
                $tmp_sku_info = array();
                foreach ($skuName as $v) {
                    foreach ($v['list'] as $lv)
                        array_push($tmp_sku_info, $lv);
                }
                $is_change = $skuObj->checkArray('id', 'id', $tmp_sku_info,
                    $goodsSkuDetailList);
            }
            // 检查商品组合是否变动
            if (false == $is_change) {
                $is_change = $skuObj->checkArray('sku_detail_id', 'id',
                    $goods_sku_info, $skuPrice);
            }
            if (true == $is_change && session('is_submit') != true) {
                session('is_submit', true);
                $this->error('规格，规格值已修改，该商品将下架，您确定要如此操作吗？');
            }
            // 商品下架
            if (session('is_submit') == true) {
                // 所有商品下架
                $skuObj->Offline($goodsInfo['goods_id'], $this->nodeId);
                // 添加规格并重新生成规格信息
                $skuObj->checkSkuType($skuName, $goodsInfo['goods_id'], true);
            }
            // 释放提交事件
            session('is_submit', false);
            // $goodsSkuDetailList
            // 检查sku数据是否修改
            /*
             * $startDate = I('post.start_time',null);
             * if(!check_str($startDate,array('null'=>false,'strtype'=>'datetime','format'=>'Ymd'),$error)){
             * $this->error("活动开始时间{$error}"); } $endDate =
             * I('post.end_time',null);
             * if(!check_str($endDate,array('null'=>false,'strtype'=>'datetime','format'=>'Ymd'),$error)){
             * $this->error("活动结束时间{$error}"); } if($endDate < date('Ymd'))
             * $this->error('活动截止日期不能小于当前日期'); if($endDate > '20140930')
             * $this->error('活动截止日期不能大于9月30日');
             */

            $goodsImg = I('post.img_resp');
            $isOrder = I('post.dg', null); // 是否为订购商品 1 为非订购 2 为订购
            $msgConfig = 0; // 默认自动发送短信关闭
            $sendTime = ''; // 短信告知时间默认为空
            $configData = ''; // 大数据字段
            // 订单信息
            if ('2' === $isOrder) {
                // 自动短信通知开关
                if ('on' === I('msgConfig', null)) {
                    $msgConfig = 1;
                    $sendTime = (int) I('sendTime', null); // 短信告知时间
                }
                $orderType = D('Sku', 'Service')->getCycleType($skuName);
                if (false === $orderType)
                    $this->error("请配置订购周期",
                        array(
                            '返回' => "javascript:history.go(-1)"));
                // 将订购信息放入大字段中，以cycle为订购节点
                $configData = array(
                    'cycle' => array(
                        'cycle_type' => $orderType,  // 配送周期
                        'cycle_notice_falg' => $msgConfig,  // 短信告知
                        'cycle_notice_before_day' => (int) $sendTime)); // 短信告知时间

                $isSku = true; // 将订购作为SKU商品处理
            }
            if ($goodsImg) {
                $goodsImg = str_replace('..', '', $goodsImg);
                if (! check_str($goodsImg,
                    array(
                        'null' => false), $error)) {
                    $this->error("商品图片{$error}");
                }

                // 采用新的图片控件 by tr
                /*
                 * //移动图片 $img_move =
                 * move_batch_image($goodsImg,31,$this->node_id); if($img_move
                 * !==true) $this->error('商品图片上传失败！'.$goodsImg);
                 */

                // $goodsImg = $this->img_path .$goodsImg;
            }
            /*
             * $groupGoodsName = I('post.group_goods_name',null);
             * if(!check_str($groupGoodsName,array('null'=>false,'maxlen_cn'=>'20'),$error)){
             * $this->error("商品名称{$error}"); } $goodsImg =
             * I('post.resp_goods_img');
             * if(!check_str($goodsImg,array('null'=>false),$error)){
             * $this->error("商品图片{$error}"); } //移动图片 $img_move =
             * move_batch_image($goodsImg,$this->BATCH_TYPE,$this->node_id);
             * if($img_move !==true) $this->error('商品图片上传失败！'); $goodsImg =
             * $this->img_path .$goodsImg; $goodsMemo =
             * I('post.goods_memo',null);
             * if(!check_str($goodsMemo,array('null'=>false,'maxlen_cn'=>'200'),$error)){
             * $this->error("商品描述{$error}"); }
             */
            $sku_infolist = $skuObj->getSkuPrice($skuPrice);
            if ($sku_infolist != false) {
                $marketPrice = $sku_infolist['price'];
                $isSku = 1;
                if ('-1' == $countNum) {
                    $goodsNum = - 1;
                    $numType = 0;
                } else {
                    $numType = 1;
                    $goodsNum = (int) $countNum;
                }
            } else {
                $marketPrice = I('post.market_price', null);
                if (! check_str($marketPrice,
                    array(
                        'null' => false,
                        'strtype' => 'number',
                        'minval' => '0'), $error)) {
                    $this->error("商品价格{$error}");
                }
                $numType = I('post.num_type', null);
                $goodsNum = I('post.goods_num', null);
                if ($numType == 0)
                    $goodsNum = - 1;
                else {
                    if (! check_str($goodsNum,
                        array(
                            'null' => false,
                            'strtype' => 'int',
                            'minval' => '0'), $error)) {
                        $this->error("商品总数{$error}");
                    }
                }
            }
            /*
             * $deliveryFlag = I('post.delivery_flag',null); if($deliveryFlag ==
             * null) $deliveryFlag = 0; $sendFlag = I('post.send_flag',null);
             * if($sendFlag == null) $sendFlag = 0;
             * if(!check_str($mmsNote,array('null'=>false,'maxlen_cn'=>'100'),$error)){
             * $this->error("彩信内容{$error}"); }
             * if(!check_str($mmsTitle,array('null'=>false,'maxlen_cn'=>'10'),$error)){
             * $this->error("彩信标题{$error}"); }
             * if(!check_str($printText,array('null'=>false,'maxlen_cn'=>'100'),$error)){
             * $this->error("彩信标题{$error}"); }
             */
            $mmsNote = I('post.mms_text');
            $mmsTitle = I('post.mms_title');
            $printText = I('post.print_text');
            if ($printText == '')
                $printText = "凭小票可兑换1份" . $goodsInfo['goods_name'];
            // 判断库存是否足够
            if ($numType != 0) {
                if ($goodsNum < 0) { // 减少库存
                    // if($goodsInfo['remain_num'] <
                    // ($goodsInfo['storage_num'] - $goodsNum))
                    $this->error('库存设置有误');
                }
            }

            // START 处理门店终端信息
            // 支撑创建终端组
            $nodeInfo = M('tnode_info')->field(
                'node_name,client_id,node_service_hotline,posgroup_seq')
                ->where("node_id='{$this->nodeId}'")
                ->find();
            M('tnode_info')->where(
                array(
                    'node_id' => $this->nodeId))->setInc('posgroup_seq'); // posgroup_seq
            // +1;
            // 门店提领
            $groupTypeId = I('shop', true);
            $goodsM = D('Goods');
            // 全门店
            if ('2' != $groupTypeId) {
                $groupTypeId = 1;
                $sendGroupId = 0;
                $getPosData = $goodsM->getPosData($groupTypeId,
                    $this->nodeIn(null, true));
                if (false === $getPosData)
                    $this->error($goodsM->getError());
                $dataList = $goodsM->getDataList($groupTypeId, $getPosData);
            } else { // 子门店
                $groupIdInfo = I('openStores', true);
                $sendGroupId = 1;
                $getPosData = $goodsM->getPosData($groupTypeId, $this->nodeIn(),
                    $groupIdInfo);
                if (false === $getPosData)
                    $this->error($goodsM->getError());
                $dataList = $goodsM->getDataList($groupTypeId, $getPosData);
            }
            if (false === $dataList)
                $this->error('请选择门店信息');
            // 开启事务
            $goodsM->startTrans();
            switch ($groupTypeId) {
                case '1':
                    if ($goodsInfo['pos_group_type'] == '2') { // 子门店变为全门店
                        $groupId = $goodsM->zcModifyStore($this->nodeId,
                            $goodsInfo['p_goods_id'], '4');
                        if (! $groupId) {
                            $goodsM->rollback();
                            $this->error($goodsM->getError());
                        }
                        // 新建合约
                        $retMsg = $goodsM->addPosRelation(
                            $this->nodeId . '子门店-全门店', $this->nodeId,
                            $sendGroupId, $getPosData, $groupId);
                        if (false === $retMsg) {
                            $goodsM->rollback();
                            $this->error($goodsM->getError());
                        }
                    }
                    break;
                case '2':
                    $newStoreArr = array();
                    foreach ($getPosData as $v) {
                        $newStoreArr[] = $v['store_id'];
                    }
                    $newStoreArr = array_unique($newStoreArr);
                    $arrayDiff = array_diff($newStoreArr, $oldStoreArr);
                    if ($goodsInfo['pos_group_type'] == '1' || count($newStoreArr) != count($oldStoreArr) || ! empty($arrayDiff)) { // 全门店变成子门店或门店增加减少
                        $groupId = $goodsM->zcModifyStore($this->nodeId,
                            $goodsInfo['p_goods_id'], '2',
                            implode(',', $newStoreArr));
                        if (! $groupId) {
                            $goodsM->rollback();
                            $this->error($goodsM->getError());
                        }
                        // 新建合约
                        $nodeList = M()->query($this->nodeIn(null, true));
                        $retMsg = $goodsM->addPosRelation(
                            $this->nodeId . '子门店-子门店', $this->nodeId,
                            $sendGroupId, $getPosData, $groupId, true);
                        if (false === $retMsg) {
                            $goodsM->rollback();
                            $this->error($goodsM->getError());
                        }
                    }
                    break;
            }
            $goodsM->commit();
            // END 结束门店终端信息处理

            M()->startTrans();
            // 创建tgoods_info数据
            $goods_data = array(
                // 'goods_name' => $name ,
                'goods_desc' => $goodsDesc,
                // 'goods_image' => $goodsImg ,
                'market_price' => $marketPrice,
                // 'goods_amt'=> $marketPrice ,
                'storage_type' => $numType,
                'storage_num' => $goodsInfo['storage_num'] +
                    ($goodsNum - $goodsInfo['remain_num']),
                'remain_num' => $goodsNum,
                'pos_group' => $groupId,
                'pos_group_type' => $groupTypeId,
                'mms_title' => $mmsTitle,
                'mms_text' => $mmsNote,
                'sms_text' => $mmsNote,
                'print_text' => $printText,
                'is_sku' => $isSku ? 1 : 0,
                'update_time' => date('YmdHis'),
                'config_data' => empty($configData) ? '' : json_encode(
                    $configData));
            $goods_data['customer_no'] = I('post.customer_no', '0', 'string');
            $goods_data['customer_no'] = get_val($goods_data, 'customer_no', '');
            if ($goodsImg) {
                // by tr
                $goods_data['goods_image'] = $goodsImg;
            }
            if ($numType == 0)
                $goods_data['remain_num'] = - 1;
            $result = M('tgoods_info')->where(
                array(
                    'id' => $id))->save($goods_data);
            if ($result === false) {
                M()->rollback();
                $this->error('系统出错,更新商品失败');
            }
            // 页面newid
            if ($sku_infolist != false) {
                // 添加规格并重新生成规格信息
                $skuObj->checkSkuType($skuName, $goodsInfo['goods_id']);
                $newId_list = $skuObj->id_list;
                $result = $skuObj->checkSkuPro($skuPrice, $goodsInfo['goods_id'], $this->nodeId);
                if ($result['msg'] === false) {
                    M()->rollback();
                    $this->error($result['content']);
                }
            }
            if ($numType != 0) {
                // 更新tgoods_info库存
                $goodsM = D('Goods');
                $flag = $goodsM->storagenum_reduc($goodsInfo['goods_id'],
                    $goodsNum, $goodsInfo['id'], '0', '编辑卡券', '0', true);
                if ($flag === false) {
                    M()->rollback();
                    $this->error('系统出错,更新tgoods_info库存失败');
                }
            }
            /* 广西石油的话，更新对应商家 */
            if ($this->node_id == C("cnpc_gx.node_id")) {
                $this->update_cnpc_goods($goodsInfo['goods_id'],
                    I('post.merchant_id'));
            }
            M()->commit();
            $this->success('更新商品成功！',
                array(
                    '返回商品列表' => U('index')));
        }
        // 价格列表
        $skuDetail = $skuObj->makeSkuList($goods_sku_info);
        $this->assign("goodsinfo", $goodsInfo);
        $this->assign("cycleInfo", $cycleInfo);
        $this->assign("skutype",
            $skuObj->makeSkuType($goodsSkuTypeList, $goodsSkuDetailList));
        $this->assign('storeArr', $oldStoreArr);
        $this->assign("skudetail", $skuDetail);
        if ($this->node_id == C("cnpc_gx.node_id")) {
            $merchantInfo = M()->table("tfb_cnpcgx_node_info")
                ->field('id,merchant_name')
                ->where("status=1")
                ->select();
            $this->assign("merchantInfo", $merchantInfo);
            $merchant_id = M()->table('tfb_cnpcgx_goods')
                ->where("goods_no='" . $goodsInfo['goods_id'] . "'")
                ->field('merchant_id')
                ->find();
            $this->assign("merchant_id", $merchant_id);
        }
        $this->display();
    }

    private function update_cnpc_goods($goods_no, $merchant_id) {
        $count = M()->table('tfb_cnpcgx_goods')
            ->where("goods_no='" . $goods_no . "'")
            ->count();
        if ($count == 0) {
            $data = array(
                'goods_no' => $goods_no,
                'merchant_id' => $merchant_id);
            $info_id = M()->table('tfb_cnpcgx_goods')->add($data);
            if ($info_id) {
                return TRUE;
            }
        } else {
            $data = array(
                'merchant_id' => $merchant_id);
            $flag = M()->table('tfb_cnpcgx_goods')
                ->where("goods_no='" . $goods_no . "'")
                ->save($data);
            if ($flag === false) {
                return false;
            } else {
                return TRUE;
            }
        }
    }
    /*****
     * 电商改版  2016-03-07
     *  @auth john_zeng
     */
    //商品上架列表
    public function indexNew(){
        $goodsName = I('goodsName', '');
        $minPrice = I('minPrice', '');
        $maxPrice = I('maxPrice', '');
        $minDate = I('beginDate', '');
        $maxDate = I('endDate', '');
        //$status = I('status', '');
        $classify = I('classify', '');
        $sortField = I('sortField', '');

        //2016-06-12 商品管理优化：按照商品状态显示
        $status = I('status', 0, 'intval');
        $this->assign('status',$status);

        $sortArr = array(
            'saleNum',
            'remainNum',
            'lockNum');
        $sortSql = 'status asc , id desc';
        if ($sortField != '') {
            do {
                $arr = explode('|', $sortField);
                if (count($arr) != 2)
                    break;
                if (! in_array($arr[0], $sort_arr))
                    break;
                $sort_sql = $arr[0] . ($arr[1] == 'asc' ? ' asc' : ' desc');
            }
            while (0);
        }

        $where = array(
            't.node_id' => $this->nodeId,
            't.is_delete' => 0,  // 显示未删除的信息
            'm.batch_type' => array('in',['26','27','31']));

        // =====================2016-06-12 商品管理优化：按照商品状态显示 START==========================
        if ($status == 3) {
            //已售罄查询条件
            $where['t.remain_num'] = array('lt',1);
            $where['t.status'] = 0;
        }
        elseif($status == 1){
            //已下架查询条件
            $where['_string'] = "t.status='1' OR t.status='2'";
        }
        else{
            //已上架查询条件
            $where['t.status'] = $status;
            $where['t.remain_num'] = array('gt',0);
        }

        // =====================2016-06-12 商品管理优化：按照商品状态显示 END==========================

        if ($goodsName != ""){
            $where['t.batch_name'] = array('exp', "like '%$goodsName%'");
        }
        if ($minPrice != ''){
            $where['t.batch_amt'] = array('egt', $minPrice);
        }
        if ($maxPrice != ''){
            $where['t.batch_amt'] = array('elt', $maxPrice);
        }
        //?为何是读取的添加时间和商品修改时间，而不是用户自己设定的时间
        if ($minDate != ''){
            $where['_string'] .= "and if(t.update_time is null or t.update_time = '', t.add_time, t.update_time) >= '{$minDate}000000'";
        }
        if ($maxDate != ''){
            $where['_string'] .= "and if(t.update_time is null or t.update_time = '', t.add_time, t.update_time) <= '{$maxDate}000000'";
        }
        //2016-06-12 商品管理优化：按照商品状态显示
        /*if ($status != ''){
            if('3' == $status){
                $where['t.remain_num'] = array('eq', '0');
            }else{
                $where['t.status'] = $status;
            }
        }*/
        if ($classify != ''){
            $where['_string'] .= "FIND_IN_SET ({$classify}, a.ecshop_classify)";
        }
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = M()->table('tbatch_info as t')
            ->field('a.*,t.*')
            ->join('tecshop_goods_ex as a ON a.node_id=t.node_id and t.id = a.b_id')
            ->join('tmarketing_info as m ON m.id=t.m_id')
            ->where($where)
            ->count();

        $Page = new Page($mapcount, 10); // 实例化分页类

        $subQuery = M('tbatch_info as t')
            ->field(
                "a.ecshop_classify, a.config_data as config_info, a.label_id, a.goods_qrcode_path, t.*, m.click_count, m.integral_flag, m.batch_type as typs,g.goods_image, g.is_sku, c.id as channel_id")
            ->join('tecshop_goods_ex as a ON a.node_id=t.node_id and t.id = a.b_id')
            ->join('tgoods_info as g on t.goods_id =  g.goods_id')
            ->join('tmarketing_info as m on m.id =  t.m_id')
            ->join('tbatch_channel as c on c.batch_id =  m.id and c.batch_type = m.batch_type')
            ->where($where)
            ->order('t.id DESC')
            ->group('t.node_id,t.id')
            ->buildSql();

        $list = M()->table($subQuery . ' a')->order($sort_sql)->limit(
            $Page->firstRow . ',' . $Page->listRows)->select();

        $show = $Page->show(); // 分页显示输出

        // 创建sku信息
        foreach ($list as $key => &$val) {
            // sku商品
            if ("1" === $val['is_sku']) {
                $val = $this->SkuService->makeGoodsListInfo($val, $val['m_id']);
                if(false == $val){
                    unset($list[$key]);
                }
            }
            if(in_array($val['typs'], ['26','27'])){
                $val['id'] = $this->GoodsInfoModel->getOdlEcshopChannel($val['m_id'], $val['typs'], $this->GoodsInfoModel->nodeId);
            }
            //计算售卖商品数
            $val['lock_num'] = $val['lock_num'] < 0 ? 0 : $val['lock_num'];
            $val['sellNum'] = (int)($val['storage_num'] - $val['remain_num'] - $val['lock_num']);
            if($val['sellNum'] < 0){
                $val['sellNum'] = 0;
            }

            //将售卖完的商品设置为已售罄
            if('0' == $val['remain_num']){
                $val['status'] = '3';
            }
        }

        // 获取商品分类1
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('list', $list); // 赋值分页输出
        $this->assign('goodsName', $goodsName); // 赋值分页输出
        $this->display();

    }
    //商品添加页面
    public function addNew(){
        //获取商品分组信息
        log_write('in addNew');
        $classify = $this->GoodsInfoModel->getNodeClassify();
        log_write('node_ID信息：' .$this->node_id );
        if($this->node_id == C('meihui.node_id')) {
            //获取会员等级信息
            $vipGroup = C('wfx_level');
            $this->assign('vipGroup', $vipGroup);
        }
        log_write('短信息：' .$this->node_id );
        if ($this->node_id == C('adb.node_id')) {
            $adb_flag = 1;
            $store_id=0;
            $this->assign('store_id', $store_id);
            $this->assign('adb_flag', $adb_flag);
        }
        //获取商户是否开通自定义短信标志
        $this->assign('startUp',$this->startUp);
        log_write('页面信息：' .$this->node_id );
        $this->assign('classify', $classify); // 商品分组信息
        $this->display();
    }
    //商品添加提交页面(新增)
    public function addSumbit(){
        //验证提交信息
        $reqData = $this->checkPostInfo();
        //取得商品终端信息
        if ('2' != $reqData['shop']) {
            $nodeIn = $this->nodeIn(null, true);
        }else{
            $nodeIn = $this->nodeIn();
        }

        // 查询渠道信息
        $map = array(
            'node_id' => $this->nodeId,
            'type' => self::$channelType,
            'sns_type' => self::$channelSnsType);

        $channelInfo = M('tchannel')->where($map)->find();
        if (! $channelInfo){
            $this->error('没有旺财小店渠道！');
        }
        // 关联商品处理
        $rgoodsIds = I('rgoodsIds');
        if ('' != $rgoodsIds) {
            $this->GoodsInfoModel->checkRelationGoods($rgoodsIds);
        }
        //检查商品名称是否重复
        $reqId = M('tmarketing_info')->where(array('name'=>$reqData['goodsName'], 'node_id'=>$this->node_id))->getField('id');
        if($reqId > 0){
            $this->error('商品名称重复，请更换！');
        }
        //数据插入
        M()->startTrans();
        // =====================tecshop_classify商品分组信息==========================
        $reqData['group'] = $this->GoodsInfoModel->reloadClassify($reqData['group']);
        if(false === $reqData['group']){
            //数据回退
            M()->rollback();
            $this->error($this->GoodsInfoModel->getError());
        }

        // =====================tgoods_info更新及门店信息==========================
        $goodsId = $this->GoodsInfoModel->addGoodsInfo($reqData, $nodeIn);
        if(false === $goodsId){
            //数据回退
            M()->rollback();
            $this->error($this->GoodsInfoModel->getError());
        }
        // =====================MarketInfo更新==========================
        //添加MarketInfo信息
        $marketData = array(
            'batch_id' => '0',
            'batch_type' => $this->GoodsInfoModel->batchType,
            'node_id' => $this->nodeId,
            'name' => $reqData['goodsName'],
            'start_time' => $reqData['beginTime'],
            'end_time' => $reqData['endTime'],
            'add_time' => date('YmdHis'),
            'bonus_flag' => (int)$reqData['bonusFlag'],
            'integral_flag' => (int)$reqData['integralFlag'],
            'member_join_flag' => $reqData['memberJoinFlag'],  // 微信绑定
            'wap_info' => $reqData['wapInfo'],
            'status' => '1',
            'config_data' => $reqData['sendGift'] == '1' ? serialize(array('send_gift' => '1')) : serialize(array('send_gift' => '0')),  // 送礼开关
            'deli_pay_flag' => $reqData['deliPayFlag']  // 物流支持货到付款
        );

        $mId = $this->GoodsInfoModel->insertMarketInfo($marketData);
        if(false === $mId){
            //数据回退
            M()->rollback();
            $this->error('营销活动保存失败');
        }
        // =====================BatchInfo更新==========================
        //添加活动商品关联表信息（原商品上架表）
        $batchData = array(
            'batch_no' => $this->GoodsInfoModel->batchNo,
            'batch_short_name' => $reqData['goodsName'],
            'batch_name' => $reqData['goodsName'],
            'node_id' => $this->node_id,
            'user_id' => $this->user_id,
            'batch_class' => $this->GoodsInfoModel->goodsTypes,
            'batch_type' => $this->GoodsInfoModel->batchType,
            'batch_img' => $reqData['goodsImg'][0],
            'validate_times' => '1',
            'batch_amt' => $reqData['batchAmt'],
            'begin_time' => $reqData['beginTime'],
            'end_time' => $reqData['endTime'],
            'send_begin_date' => $reqData['beginTime'],
            'send_end_date' => $reqData['endTime'],
            'add_time' => date('YmdHis'),
            'node_pos_group' => $this->GoodsInfoModel->groupId,
            'node_pos_type' => $reqData['shop'],
            'batch_desc' => $reqData['goodsDesc'],
            'goods_id' => $goodsId,
            'storage_num' => $reqData['salesNum'],
            'remain_num' => $reqData['salesNum'],
            'print_text' => $reqData['printText'],
            'use_rule' => $reqData['smsText'],
            'verify_begin_date' => $reqData['verifyTimeType'] == '0' ? $reqData['verifyBeginDate'] : $reqData['verifyBeginDays'],
            'verify_end_date' => $reqData['verifyTimeType'] == '0' ? $reqData['verifyEndDate'] : $reqData['verifyEndDays'],
            'verify_begin_type' => $reqData['verifyTimeType'],
            'verify_end_type' => $reqData['verifyTimeType'],
            'm_id' => $mId,
            'info_title' => get_scalar_val($reqData['goodsName'],'商品')
        );
        //自定义短信
        if($reqData['deliveryFlag'] == '0' || $reqData['deliveryFlag'] == '0-1') {        // 门店提领 或 门店提领+物流配送
            if ($this->startUp == 1) {
                $sms_text = I('cusMsg', '');
                if (empty($sms_text)) {
                    M()->rollback();
                    $this->error('短信内容不能为空');
                } else {
                    $batchData['sms_text'] = $sms_text;
                }
            }
        }
        $bId = $this->GoodsInfoModel->insertBatchInfo($batchData);
        if(false === $bId){
            //数据回退
            M()->rollback();
            $this->error('商品信息保存失败');
        }

        // =====================SKU更新==========================
        //初始化变量
        $newIdList = false;
        //插入数据到SKU_INFO
        if (true === $reqData['isSku']) {
            //赋值goodsId
            $this->SkuService->goodsId = $goodsId;
            // 添加规格并重新生成规格信息
            $result = $this->SkuService->checkSkuType($reqData['skuName']);
            if(!$result){
                $this->error('规格信息添加失败');
            }
            // 页面newid
            $newIdList = $this->SkuService->id_list;
            //取得已插入数据库是的skuid信息
            foreach ($reqData['skuMarkPrice'] as &$v){
                $v['newid'] = $this->SkuService->changeSkuId($newIdList, $v['newid']);
            }
            // =====================更新tgoods_sku_info数据信息==========================
            $result = $this->SkuService->checkSkuPro($reqData['skuMarkPrice'], $goodsId);
            if ($result['msg'] === false) {
                M()->rollback();
                $this->error($result['content']);
            }
            // =====================更新tecshop_goods_sku数据信息==========================
            //插入sku售卖数据
            $result = $this->SkuService->addGoodsSkuInfo($reqData['skuMarkPrice'], $mId, $bId);
            if (false == $result) {
                M()->rollback();
                $this->error($this->SkuService->getError());
            }
        }else{
            //添加普通商品信息
            $result = $this->SkuService->addNomorlGoodsInfo($reqData, $mId, $bId);
            if(false === $result){
                M()->rollback();
                $this->error($this->SkuService->getError());
            }
        }

        //=======会员优惠 保存===========================
        if ($this->node_id == C('meihui.node_id')) {
            foreach($reqData['discountPrice'] as $k=>$v) {
                $discount = array(
                    'node_id' => $this->node_id,
                    'goods_id' => $goodsId,
                    'vip_type' => $reqData['discountType'][$k],
                    'vip_discount' => $v,
                );
                $result = D('MeiHui', 'Model')->addByVipDiscount($discount);
                if($result === false) {
                    node_log('美惠创建商品会员优惠保存失败', print_r($discount, true));
                }
            }
        }

        // =====================爱蒂宝非标更新==========================
        //爱帝宝非标
        if ($this->node_id == C('adb.node_id')) {
                $stores = I("openStores", '');
            $param = array(
                'node_id' => $this->node_id,
                'stores' => $stores,
                'b_id' => $bId);
            B('AdbGoodsPutOn', $param);
        }

        // =====================GoodsEx更新==========================
        //组合商品扩展大字段信息
        $configArray = [];
        if('1' == $reqData['fristBuyNumFlag']){
            $configArray['fristBuyNum'] =  $reqData['fristBuyNum'];
        }
        //是否显示市场价格
        if('1' == $reqData['isShowPrice']){
            $configArray['isShowPrice'] =  $reqData['isShowPrice'];
        }
        //是否显示销量
        if('1' == $reqData['isShowSalesNum']){
            $configArray['isShowSalesNum'] =  $reqData['isShowSalesNum'];
        }
        //是否显示销售时间
        if('1' == $reqData['isShowSalesTime']){
            $configArray['isShowSalesTime'] =  $reqData['isShowSalesTime'];
        }
        //销售比例
        if($reqData['saleProportion'] > 0){
            $configArray['saleProportion'] =  $reqData['saleProportion'];
        }else{
            $configArray['saleProportion'] =  1;
        }
        $configArray['showImgs'] = $reqData['goodsImg'];
        $goodsDataEx = array(
            'node_id' => $this->nodeId,
            'b_id' => $bId,
            'm_id' => $mId,
            'ecshop_classify' => implode(',', $reqData['group']),
            'wap_info' => $reqData['wapInfo'],
            'goods_desc' => $reqData['goodsDesc'],
            'market_price' => $reqData['batchAmt'],
            'day_buy_num' => $reqData['dayBuyNumFlag'] == '0' ? - 1 : $reqData['dayBuyNum'],
            'person_buy_num' => $reqData['personBuyNumFlag'] == '0' ? - 1 : $reqData['personBuyNum'],
            'delivery_flag' => $reqData['deliveryFlag'],
            'market_show' => (int)$reqData['isShowPrice'],
            'relation_goods' => $rgoodsIds,
            'purchase_time_limit' => isset($reqData['purchaseTimeLimit']) ? $reqData['purchaseTimeLimit'] : 0,
            'config_data' => json_encode($configArray)
        );
        $flag = $this->GoodsInfoModel->insertGoodsExInfo($goodsDataEx);
        if(false === $flag){
            //数据回退
            M()->rollback();
            $this->error('商品扩展信息保存失败');
        }
        // =====================MarketChangeTrace更新==========================
        // 记录编辑流水
        $changeData = array(
            'batch_id' => $mId,
            'batch_type' => '31',
            'name' => $reqData['goodsName'],
            'start_time' => $reqData['beginTime'],
            'end_time' => $reqData['endTime'],
            'memo' => $reqData['goodsDesc'],
            'wap_info' => $reqData['wapInfo'],
            'defined_one_name' => $reqData['deliveryFlag'],
            'market_price' => $reqData['marketPrice'],
            'group_price' => $reqData['batchAmt'],
            'goods_num' => $reqData['salesNum'],
            'buy_num' => $reqData['dayBuyNumFlag'] == '0' ? 0 : $reqData['dayBuyNum'],
            'defined_three_name' => $reqData['personBuyNumFlag'] == '0' ? - 1 : $reqData['personBuyNum'],
            'verify_begin_date' => $reqData['verifyTimeType'] == '0' ? $reqData['verifyBeginDate'] : $reqData['verifyBeginDays'],
            'verify_end_date' => $reqData['verifyTimeType'] == '0' ? $reqData['verifyEndDate'] : $reqData['verifyEndDays'],
            'verify_begin_type' => $reqData['verifyTimeType'],
            'verify_end_type' => $reqData['verifyTimeType'],
            'modify_time' => date('YmdHis'),
            'modify_type' => '0',
            'oper_id' => $this->user_id,
            'CONFIG_DATA'=>'');
        $flag = $this->GoodsInfoModel->insertMarketChangeTraceInfo($changeData);
        if ($flag === false){
            M()->rollback();
            $this->error('商品信息编辑记录保存失败');
        }

        // =====================BatchChannel更新==========================
        //添加渠道信息
        $batchChannelData = array(
            'batch_type' => '31',
            'batch_id' => $mId,
            'channel_id' => $channelInfo['id'],
            'add_time' => date('YmdHis'),
            'node_id' => $this->nodeId);
        $labelId = $this->GoodsInfoModel->insertBatchChannel($batchChannelData);
        if ($labelId === false){
            M()->rollback();
            $this->error('商品渠道信息保存失败');
        }

        // =====================tecshop_goods_ex 渠道更新==========================
        $goodsDataEx = array('label_id' => $labelId);
        $flag = M('tecshop_goods_ex')->where("b_id = '$bId'")->save($goodsDataEx);
        if ($flag === false){
            M()->rollback();
            throw new Exception("商品渠道信息更新失败！");
        }
        //提交数据
        node_log('旺财小店商品上架', print_r($reqData, true));
        // $this->success('商品上架成功');
        M()->commit();
        $this->ajaxReturn(1, "商品上架成功！", 1);
    }

    //查看商品详情
    public function infoNew() {
        $mId = I('id', 0, 'intval');
        if ($mId == 0){
            $this->error('参数错误！');
        }
        $map = array(
            'node_id' => $this->nodeId,
            'id' => $mId);
        $marketInfo = M('tmarketing_info')->where($map)->field('deli_pay_flag, integral_flag, bonus_flag, config_data')->find();
        if(!$marketInfo){
            $this->error('商品不存在，请去创建');
        }
        $goodsInfo = M('tbatch_info')->where("m_id = '{$mId}'")->find();
        //判读商品是否已上架
        if (!$goodsInfo){
            $this->error('商品不存在，请去创建');
        }
        // 爱蒂宝非标
        if ($this->node_id == C('adb.node_id')) {
            $adbFlag = 1;
            $storeId=0;
            $param = array('node_id' => $this->node_id, 'id' => $goodsInfo['id']);
            B('AdbGoodsEdit', $param);
            $this->assign('adbb_id', $goodsInfo['id']);
            $this->assign('store_id', $storeId);
            $this->assign('adb_flag', $adbFlag);
            $this->assign('count', $param['return']['data']['count']);
            $this->assign('stores', $param['return']['data']['stores_data']);
        }

        // 美惠非标
        if ($this->node_id == C('meihui.node_id')) {
            $this->assign('vipGroup', C('wfx_level'));

            $discount = D('MeiHui', 'Model')->getByVipDiscount($this->node_id, $goodsInfo['goods_id']);
            $this->assign('discount', $discount);
        }
        //获取商品扩展信息
        $goodsInfoEx = M('tecshop_goods_ex')->where("m_id = '{$mId}'")->find();
        //获取sku商品标识
        $isSku = $this->SkuService->getIsSkuInfo($goodsInfo['goods_id']);
        //默认普通商品
        $skuInfoList = false;
        // 判断是否有sku数据
        $goodsSkuTypeList = '';
        $goodsSkuDetailList = '';
        $skuDetail = '';

        if('1' == $isSku){
            //判断复制的商品是否是上架商品
            $skuInfoList = $this->SkuService->getSkuEcshopList($mId, $this->nodeId, $goodsInfo['status']);
            // 分离商品表中的规格和规格值ID
            $goodsSkuList = $this->SkuService->getReloadSku($skuInfoList);
            // 取得规格值表信息
            if (is_array($goodsSkuList['list'])){
                $goodsSkuDetailList = $this->SkuService->getSkuDetailList($goodsSkuList['list']);
            }
            // 取得规格表信息
            if (is_array($goodsSkuDetailList)){
                //重新排列规格信息
                $goodsSkuDetailList = $this->SkuService->getNewSkuTypeList($goodsSkuDetailList, $goodsSkuList);
                $goodsSkuTypeList = $this->SkuService->getSkuTypeList($goodsSkuDetailList);
                $goodsSkuTypeList = $this->SkuService->getReloadSkuType($goodsSkuTypeList, $goodsSkuDetailList);
            }
            // 价格列表
            $skuDetail = $this->SkuService->makeSkuList($skuInfoList);
        }

        $basegoodsInfo = M('tgoods_info')->where("goods_id = '{$goodsInfo['goods_id']}'")->find();

        // 关联商品
        $rgoodsList = array();
        if ($goodsInfoEx['relation_goods'] != '') {
            $rgoodsList = M()->table(
                'tecshop_goods_ex a, tmarketing_info b, tbatch_info c, tgoods_info g')->where(
                "b.batch_type = '31' and a.m_id = b.id and a.b_id = c.id and c.goods_id = g.goods_id and b.id in ({$goodsInfoEx['relation_goods']})")->field(
                'b.id, g.goods_name, g.goods_image')->select();
        }
        //解析图片
        //var_dump($basegoodsInfo);die;
//        $imgList = $goodsInfoEx;

        //获取物流，自提订单配置
        $deliverFlag = array();
        if($goodsInfoEx['delivery_flag']){
            $deliverFlag = $this->GoodsInfoModel->getDeliverInfo($goodsInfoEx['delivery_flag']);
        }
        $this->assign('id', $mId);
        //格式化时间
        $goodsInfo['begin_time'] = dateformat($goodsInfo['begin_time'], 'Y-m-d');
        $goodsInfo['end_time'] = dateformat($goodsInfo['end_time'], 'Y-m-d');
        if('0' == $goodsInfo['verify_begin_type']){
            $goodsInfo['verify_begin_date'] = dateformat($goodsInfo['verify_begin_date'], 'Ymd');
            $goodsInfo['verify_end_date'] = dateformat($goodsInfo['verify_end_date'], 'Ymd');
        }
        //初始化变量
        $oldStoreArr = array();
        // 获取门店信息
        if ($goodsInfo['node_pos_type'] == '2') {
            $storeData = M('tgroup_pos_relation')
                ->field('store_id')
                ->where("group_id={$goodsInfo['node_pos_group']}")
                ->select();
            foreach ($storeData as $v) {
                $oldStoreArr[] = $v['store_id'];
            }
            $oldStoreArr = array_unique($oldStoreArr);
        }

        // 获取送礼信息
        $sendGift = D('MarketInfo')->getSendGiftTage($marketInfo);
        //获取商品分组信息
        $classify = $this->GoodsInfoModel->getNodeClassify();
        //获取当前使用的商品分组信息
        $useClassify = $this->GoodsInfoModel->getUseClassify($goodsInfoEx['ecshop_classify']);

        $this->assign('classify', $classify); // 商品分组信息
        $this->assign('useClassify', $useClassify);  //当前使用的商品分组信息
        $this->assign('sendGift', $sendGift);
        $this->assign('deliverFlag', $deliverFlag);  //物流信息
        $this->assign('goodsInfo', $goodsInfo);
        $this->assign('rgoodsList', $rgoodsList);   //关商品信息
        $this->assign("skuDetail", $skuDetail);    //规格商品信息
        $this->assign("skutype", $this->SkuService->makeSkuType($goodsSkuTypeList, $goodsSkuDetailList));
        $this->assign('isSku', $isSku);
        $this->assign('goodsInfoEx', $goodsInfoEx);
        $this->assign('goodsInfoExConfig', json_decode($goodsInfoEx['config_data'], true));
        $this->assign('storeArr', $oldStoreArr);   //获取子门店信息
        $this->assign('marketInfo', $marketInfo);    //货到付款配置
        $this->assign('basegoodsInfo', $basegoodsInfo);
        $this->display($tpl_name);
    }

    //推荐模板
    public function introduceModel(){
        $mId = I('id', 0, 'intval');
        if ($mId == 0){
            $this->error('参数错误！');
        }
        //获取tmarketing_info的信息
        $marketInfo = M('tmarketing_info as m')
            ->join('tbatch_info as b on b.m_id = m.id')
            ->field('m.*,b.batch_amt,b.batch_img, b.goods_id')
            ->where(array('m.id'=>$mId))->find();

        if(NULL === $marketInfo){
            $this->error('商品不存在');
        }
        //获取分组信息
        $classifyInfo = $this->GoodsInfoModel->getGoodsClassify($mId);
        if(false === $classifyInfo){
            $this->error('商品信息读取失败');
        }
        //获取sku商品标识
        $isSku = $this->SkuService->getIsSkuInfo($marketInfo['goods_id']);
        //获取商品价格
        if('1' === $isSku){
            $marketInfo = $this->SkuService->makeGoodsListInfo($marketInfo, $mId, '');
        }
        //获取商品渠道ID
        $labelId = M('tecshop_goods_ex')->where(['m_id'=>$mId])->getField('label_id');
        $this->assign('row', $marketInfo);
        // 积分和背景色
        $serData = unserialize($marketInfo['config_data']);
        $this->assign('serData', $serData);
        $this->assign('classifyInfo', $classifyInfo);
        $this->assign('label_id', $labelId);
        $this->assign('mId', $mId);
        $this->display();
    }
    //电子海报编辑
    public function editElectronic(){
        $mId = I('id', 0, 'intval');
        if ($mId == 0){
            $this->error('参数错误！');
        }
        //获取tmarketing_info的信息
        $marketInfo = M('tmarketing_info')->where(array('id'=>$mId))->field('config_data')->find();
        if(NULL === $marketInfo){
            $this->error('商品不存在');
        }
        //商品简介
        $goodsMemo = I('goods_memo', '', 'htmlspecialchars');
        //商品背景图
        $sharePic = I('share_pic');
        $data = array('memo'=>$goodsMemo, 'share_pic'=>$sharePic);
        $data['config_data'] = unserialize($marketInfo['config_data']);
        //新版电商电子海报展示页面标识
        $data['config_data']['isShowElAd'] = 1;
        $data['config_data']['isShowAd'] = 0;
        $data['config_data'] = serialize($data['config_data']);
        $result = M('tmarketing_info')->where(array('m_id'=>$mId))->data($data)->save();
        if(!$result){
            $this->error('更新信息失败');
        }
        $this->ajaxReturn(1, "保存成功！", 1);
        exit();
    }
    //电子海报
    public function electronicPoster(){
        $mId = I('id', 0, 'intval');
        if ($mId == 0){
            $this->error('参数错误！');
        }
        //获取tmarketing_info的信息
        $marketInfo = M('tmarketing_info')->where(array('id'=>$mId))->find();
        if(NULL === $marketInfo){
            $this->error('商品不存在');
        }
        //获取分组信息
        $classifyInfo = $this->GoodsInfoModel->getGoodsClassify($mId);
        if(false === $classifyInfo){
            $this->error('商品信息读取失败');
        }
        //获取商品价格
        $marketInfo = $this->SkuService->makeGoodsListInfo($marketInfo, $mId);

        $this->assign('classifyInfo', $classifyInfo);
        $this->assign('row', $marketInfo);
        $this->assign('mId', $mId);
        $this->display();
    }

    //商品编辑1
    public function editNew() {
        $mId = I('id', 0, 'intval');
        //是否复制模板
        $isCopy = I('isCopy');
        $putonFlag = I('puton_flag', 0, 'intval');

        if ($mId == 0){
            $this->error('参数错误！');
        }
        $map = array(
            'node_id' => $this->nodeId,
            'id' => $mId,
            'batch_type' => '31');
        $marketInfo = M('tmarketing_info')->where($map)->field('deli_pay_flag, member_join_flag, integral_flag, bonus_flag, config_data')->find();

        if (! $marketInfo){
            $this->error('参数错误！');
        }
        $goodsInfo = M('tbatch_info')->where("m_id = '{$mId}'")->find();
        //判读商品是否已上架
        //2016-07-06 商品管理优化：商品上架可修改规定的字段
        /*if ($goodsInfo['status'] == '0' && 'copy' != $isCopy){
            $this->error('商品已启用，不能编辑');
        } */

        // 爱蒂宝非标
        if ($this->node_id == C('adb.node_id')) {
            $adbFlag = 1;
            $storeId=0;
            $param = array('node_id' => $this->node_id, 'id' => $goodsInfo['id']);
            B('AdbGoodsEdit', $param);
            $this->assign('adbb_id', $goodsInfo['id']);
            $this->assign('store_id', $storeId);
            $this->assign('adb_flag', $adbFlag);
            $this->assign('count', $param['return']['data']['count']);
            $this->assign('stores', $param['return']['data']['stores_data']);
        }
        // 美惠非标
        if ($this->node_id == C('meihui.node_id')) {
            $this->assign('vipGroup', C('wfx_level'));

            $discount = D('MeiHui', 'Model')->getByVipDiscount($this->node_id, $goodsInfo['goods_id']);
            $this->assign('discount', $discount);
        }
        //获取商品扩展信息
        $goodsInfoEx = M('tecshop_goods_ex')->where("m_id = '{$mId}'")->find();
        //获取sku商品标识
        $isSku = $this->SkuService->getIsSkuInfo($goodsInfo['goods_id']);

        //默认普通商品
        $skuInfoList = false;
        // 判断是否有sku数据
        $goodsSkuTypeList = '';
        $goodsSkuDetailList = '';
        $skuDetail = '';
        if('1' == $isSku){
            //判断复制的商品是否是上架商品
            $status = false;
            //if('copy' === $isCopy && $goodsInfo['status'] == '0' ){
            if ($goodsInfo['status'] == '0') {
                $status = true;
            }
            $skuInfoList = $this->SkuService->getSkuEcshopList($mId, $this->nodeId, $status);
            // 分离商品表中的规格和规格值ID
            $goodsSkuList = $this->SkuService->getReloadSku($skuInfoList);
            // 取得规格值表信息
            if (is_array($goodsSkuList['list'])){
                $goodsSkuDetailList = $this->SkuService->getSkuDetailList($goodsSkuList['list']);
            }
            // 取得规格表信息
            if (is_array($goodsSkuDetailList)){
                //重新排列规格信息
                $goodsSkuDetailList = $this->SkuService->getNewSkuTypeList($goodsSkuDetailList, $goodsSkuList);
                $goodsSkuTypeList = $this->SkuService->getSkuTypeList($goodsSkuDetailList);
                $goodsSkuTypeList = $this->SkuService->getReloadSkuType($goodsSkuTypeList, $goodsSkuDetailList);
            }
            // 价格列表
            $skuDetail = $this->SkuService->makeSkuList($skuInfoList);
        }

        $basegoodsInfo = M('tgoods_info')->where("goods_id = '{$goodsInfo['goods_id']}'")->find();

        // 关联商品
        $rgoodsList = array();
        if ($goodsInfoEx['relation_goods'] != '') {
            $rgoodsList = M()->table(
                'tecshop_goods_ex a, tmarketing_info b, tbatch_info c, tgoods_info g')->where(
                "b.batch_type = '31' and a.m_id = b.id and a.b_id = c.id and c.goods_id = g.goods_id and b.id in ({$goodsInfoEx['relation_goods']})")->field(
                'b.id, g.goods_name, g.goods_image')->select();
        }
        //解析图片
        //var_dump($basegoodsInfo);die;
//        $imgList = $goodsInfoEx;

        //获取物流，自提订单配置
        $deliverFlag = $this->GoodsInfoModel->getDeliverInfo($goodsInfoEx['delivery_flag']);
        $this->assign('id', $mId);
        //格式化时间
        $goodsInfo['begin_time'] = dateformat($goodsInfo['begin_time'], 'Y-m-d H:i:s');
        $goodsInfo['end_time'] = dateformat($goodsInfo['end_time'], 'Y-m-d H:i:s');
        if('0' == $goodsInfo['verify_begin_type']){
            $goodsInfo['verify_begin_date'] = dateformat($goodsInfo['verify_begin_date'], 'Ymd');
            $goodsInfo['verify_end_date'] = dateformat($goodsInfo['verify_end_date'], 'Ymd');
        }
        //初始化变量
        $oldStoreArr = array();
        // 获取门店信息
        if ($goodsInfo['node_pos_type'] == '2') {
            $storeData = M('tgroup_pos_relation')
                ->field('store_id')
                ->where("group_id={$goodsInfo['node_pos_group']}")
                ->select();
            foreach ($storeData as $v) {
                $oldStoreArr[] = $v['store_id'];
            }
            $oldStoreArr = array_unique($oldStoreArr);
        }

        // 获取送礼信息
        $sendGift = D('MarketInfo')->getSendGiftTage($marketInfo);

        //获取商品分组信息
        $classify = $this->GoodsInfoModel->getNodeClassify();
        //获取当前使用的商品分组信息
        $useClassify = $this->GoodsInfoModel->getUseClassify($goodsInfoEx['ecshop_classify']);

        //获取商户是否开通自定义短信标志
        $this->assign('startUp',$this->startUp);

        $this->assign('classify', $classify); // 商品分组信息
        $this->assign('useClassify', $useClassify);  //当前使用的商品分组信息
        $this->assign('sendGift', $sendGift);
        $this->assign('deliverFlag', $deliverFlag);  //物流信息
        $this->assign('goodsInfo', $goodsInfo);
        $this->assign('rgoodsList', $rgoodsList);   //关商品信息
        $this->assign("skuDetail", $skuDetail);    //规格商品信息
        $this->assign("skutype", $this->SkuService->makeSkuType($goodsSkuTypeList, $goodsSkuDetailList));
        $this->assign('isSku', $isSku);
        $this->assign('goodsInfoEx', $goodsInfoEx);
        $this->assign('goodsInfoExConfig', json_decode($goodsInfoEx['config_data'], true));
        $this->assign('storeArr', $oldStoreArr);   //获取子门店信息
        $this->assign('marketInfo', $marketInfo);    //货到付款配置
        $this->assign('basegoodsInfo', $basegoodsInfo);
        if('copy' === $isCopy){
            $this->assign('isCopy', $isCopy);   //是否拷贝模块
        }
        if ($goodsInfo['status'] == 0 && $goodsInfo['remain_num'] > 0){
            $this->display('editNew1');
        }
        else{
            $this->display();
        }
    }

    public function editSumbit(){
        $mId = I('id', 0, 'intval');

        if ($mId == 0){
            $this->error('参数错误！');
        }
        //验证提交信息
        $reqData = $this->checkPostInfo();
        // 关联商品处理
        $rgoodsIds = I('rgoodsIds');
        if('' != $rgoodsIds){
            $this->GoodsInfoModel->checkRelationGoods($rgoodsIds, false);
        }
        //取得商品终端信息
        if ('2' != $reqData['shop']) {
            $nodeIn = $this->nodeIn(null, true);
        }else{
            $nodeIn = $this->nodeIn();
        }

        //获取商品信息
        $batchInfo = M('tbatch_info as b')
            ->join('tmarketing_info as m on m.id = b.m_id')
            ->where(array('b.m_id'=>$mId))
            ->field('b.node_pos_group,b.goods_id,b.node_pos_type,m.config_data')
            ->find();

        if(!$batchInfo){
            $this->error('商品信息有误');
            return false;
        }

        //数据提交准备
        M()->startTrans();
        // =====================tecshop_classify商品分组信息==========================
        $reqData['group'] = $this->GoodsInfoModel->reloadClassify($reqData['group']);

        if(false === $reqData['group']){
            //数据回退
            M()->rollback();
            $this->error($this->GoodsInfoModel->getError());
        }
        // =====================更新门店信息==========================
        //创建终端组
        $result = $this->GoodsInfoModel->createSupport($reqData, $nodeIn, $batchInfo, 'edit');
        if(false === $result){
            M()->rollback();
            $this->error($this->GoodsInfoModel->getError());
        }
        // =====================goods_info更新终端id==========================
        $goodsData = array(
            'pos_group_type'=>$reqData['shop'],   //门店类型
            'goods_image'=> $reqData['goodsImg'][0],    //更改图片信息
            'pos_group'=> $this->GoodsInfoModel->groupId,    //子门店信息
            'market_price' => $reqData['isShowPrice'] == '1' ? $reqData['marktPrice'] : '',    //市场价格
            'is_sku' => $reqData['isSku'] === true ? '1' : '0',       //是否sku商品
        );

        $result = M('tgoods_info')
            ->where(array('goods_id'=>$batchInfo['goods_id']))
            ->save($goodsData);
        if(false === $result){
            M()->rollback();
            $this->error('更新商品的终端信息失败');
        }
        //添加MarketInfo信息
        // =====================market_info更新==========================
        //获取旧的配置信息
        $configInfo = unserialize($batchInfo['config_data']);
        $configInfo['send_gift'] = $reqData['sendGift'];
        $marketData = array(
            'start_time' => $reqData['beginTime'],
            'end_time' => $reqData['endTime'],
            'bonus_flag' => (int)$reqData['bonusFlag'],
            'integral_flag' => (int)$reqData['integralFlag'],
            'wap_info' => $reqData['wapInfo'],    //商品详情
            'deli_pay_flag' => $reqData['deliPayFlag'],  // 物流支持货到付款
            'member_join_flag' => $reqData['memberJoinFlag'],  // 微信绑定
            'config_data' => serialize($configInfo)
        );

        //修改信息
        $result = $this->GoodsInfoModel->insertMarketInfo($marketData, $mId);
        if(false === $result){
            //数据回退
            M()->rollback();
            $this->error('营销活动保存失败');
        }

        // =====================tbatch_info更新==========================
        //获取商品原始信息
        $oldInfo = M('tbatch_info')->where(array('node_id'=>$this->node_id, 'm_id'=>$mId))->field('id,storage_num,remain_num')->find();
        //商品ID
        $bId = $oldInfo['id'];
        $storageNum = $oldInfo['storage_num'];
        //计算商品总库存
        if('-1' != $storageNum){
            $storageNum = $reqData['salesNum'] + ($oldInfo['storage_num'] - $oldInfo['remain_num']);
        }

        $batchData = array();
        $batchData['batch_amt'] = $reqData['batchAmt'];
        $batchData['begin_time'] = $reqData['beginTime'];
        $batchData['end_time'] = $reqData['endTime'];
        $batchData['batch_img'] = $reqData['goodsImg'][0];
        $batchData['use_rule'] = $reqData['smsText'];
        $batchData['print_text'] = $reqData['printText'];
        $batchData['batch_desc'] = $reqData['goodsDesc'];
        $batchData['verify_begin_date'] = $reqData['verifyTimeType'] == '0' ? $reqData['verifyBeginDate'] : $reqData['verifyBeginDays'];
        $batchData['verify_end_date'] = $reqData['verifyTimeType'] == '0' ? $reqData['verifyEndDate'] : $reqData['verifyEndDays'];
        $batchData['verify_begin_type'] = $reqData['verifyTimeType'];
        $batchData['verify_end_type'] = $reqData['verifyTimeType'];
        $batchData['storage_num'] = $storageNum;
        $batchData['remain_num'] = $reqData['salesNum'];
        $batchData['node_pos_group'] = $this->GoodsInfoModel->groupId;
        $batchData['node_pos_type'] = $reqData['shop'];
        //自定义短信
        if($reqData['deliveryFlag'] == '0' || $reqData['deliveryFlag'] == '0-1') {        // 门店提领 或 门店提领+物流配送
            if ($this->startUp == 1) {
                $sms_text = I('cusMsg', '');
                if (empty($sms_text)) {
                    M()->rollback();
                    $this->error('短信内容不能为空');
                } else {
                    $batchData['sms_text'] = $sms_text;
                }
            }
        }

        $result = $this->GoodsInfoModel->insertBatchInfo($batchData, $bId);
        if(false === $result){
            log_write('插入BatchInfo_Info数据失败: ' . M()->getDbError());
            //数据回退
            M()->rollback();
            $this->error('商品信息保存失败');
        }

        // =====================SKU更新==========================
        //初始化变量
        $newIdList = false;
        //获取商品ID
        $goodsId = $batchInfo['goods_id'];
        //传值
        $this->SkuService->goodsId = $goodsId;
        $result = $this->SkuService->checkGoodsDiff($reqData);
        if(false === $result){
            M()->rollback();
            $this->error($this->SkuService->getError());
        }
        //插入数据到SKU_INFO
        if (true === $reqData['isSku']) {
            // 页面newid
            $newIdList = $this->SkuService->id_list;
            //取得已插入数据库是的skuid信息
            foreach ($reqData['skuMarkPrice'] as &$v){
                if(count($newIdList) > 0){
                    $v['newid'] = $this->SkuService->changeSkuId($newIdList, $v['newid']);
                }else{
                    $v['newid'] = '';
                }
            }
            // =====================更新tgoods_sku_info数据信息==========================
            $result = $this->SkuService->checkSkuPro($reqData['skuMarkPrice'],$goodsId);
            if ($result['msg'] === false) {
                M()->rollback();
                $this->error($result['content']);
            }
            // =====================更新tecshop_goods_sku数据信息==========================
            //插入sku售卖数据
            $result = $this->SkuService->addGoodsSkuInfo($reqData['skuMarkPrice'], $mId, $bId, 'edit');
            if (false == $result) {
                M()->rollback();
                $this->error($this->SkuService->getError());
            }
            // =====================更新tgoods_info数据信息==========================
            $result = $this->GoodsInfoModel->insertGoodsInfo(array('is_sku'=>'1'), $goodsId);
            if (false === $result) {
                M()->rollback();
                $this->error($this->GoodsInfoModel->getError());
            }
        }else{
            //添加普通商品信息
            $result = $this->SkuService->addNomorlGoodsInfo($reqData, $mId, $bId, 'edit');
            if(false === $result){
                M()->rollback();
                $this->error($this->SkuService->getError());
            }
        }
        // =====================tecshop_goods_ex更新==========================
        //获取商品扩展ID
        $goodsInfoExId = M('tecshop_goods_ex')->where("m_id = '{$mId}'")->getField('id');

        //组合商品扩展大字段信息
        $configArray = [];
        if('1' == $reqData['fristBuyNumFlag']){
            $configArray['fristBuyNum'] =  $reqData['fristBuyNum'];
        }
        //是否显示销量
        if('1' == $reqData['isShowSalesNum']){
            $configArray['isShowSalesNum'] =  $reqData['isShowSalesNum'];
        }

        //是否显示销售时间
        if('1' == $reqData['isShowSalesTime']){
            $configArray['isShowSalesTime'] =  $reqData['isShowSalesTime'];
        }
        //销售比例
        if($reqData['saleProportion'] > 0){
            $configArray['saleProportion'] =  $reqData['saleProportion'];
        }else{
            $configArray['saleProportion'] =  1;
        }

        $configArray['showImgs'] = $reqData['goodsImg'];
        $exData = array(
            'goods_desc' => $reqData['goodsDesc'],
            'ecshop_classify' => implode(',', $reqData['group']),
            'wap_info' => $reqData['wapInfo'],
            'relation_goods' => $rgoodsIds,
            'market_price' => $reqData['batchAmt'],
            'day_buy_num' => $reqData['dayBuyNumFlag'] == '0' ? - 1 : $reqData['dayBuyNum'],
            'person_buy_num' => $reqData['personBuyNumFlag'] == '0' ? - 1 : $reqData['personBuyNum'],
            'market_show' => (int)$reqData['isShowPrice'],
            'delivery_flag' => $reqData['deliveryFlag'],
            'purchase_time_limit' => isset($reqData['purchaseTimeLimit']) ? $reqData['purchaseTimeLimit'] : 0,
            'config_data' => json_encode($configArray)
        );

        $flag = $this->GoodsInfoModel->insertGoodsExInfo($exData, $goodsInfoExId);
        if(false === $flag){
            //数据回退
            M()->rollback();
            $this->error('商品扩展信息保存失败');
        }

        // =====================tmarketing_change_trace记录编辑流水==========================
        $changeData = array(
            'batch_id' => $mId,
            'batch_type' => '31',
            'name' => $reqData['goodsName'],
            'start_time' => $reqData['beginTime'],
            'end_time' => $reqData['endTime'],
            'memo' => $reqData['goodsDesc'],
            'wap_info' => $reqData['wapInfo'],
            'defined_one_name' => $reqData['deliveryFlag'],
            'defined_three_name' => $reqData['personBuyNumFlag'] == '0' ? - 1 : $reqData['personBuyNum'],
            'market_price' => $reqData['marketPrice'],
            'group_price' => $reqData['batchAmt'],
            'goods_num' => $reqData['salesNum'],
            'buy_num' => $reqData['dayBuyNumFlag'] == '0' ? 0 : $reqData['dayBuyNum'],
            'verify_begin_date' => $reqData['verifyTimeType'] == '0' ? $reqData['verifyBeginDate'] : $reqData['verifyBeginDays'],
            'verify_end_date' => $reqData['verifyTimeType'] == '0' ? $reqData['verifyEndDate'] : $reqData['verifyEndDays'],
            'verify_begin_type' => $reqData['verifyTimeType'],
            'verify_end_type' => $reqData['verifyTimeType'],
            'modify_time' => date('YmdHis'),
            'modify_type' => '1',
            'oper_id' => $this->user_id);
        $flag = $this->GoodsInfoModel->insertMarketChangeTraceInfo($changeData);
        if ($flag === false){
            M()->rollback();
            $this->error('商品信息编辑记录保存失败');
        }

        //2016-06-08 商品管理优化：添加“保存并上架”按钮功能
        $type = I("submit_type",0,"intval");
        if ($type != 0){
            // =====================tbatch_info更新==========================
            $data = array('status' => '0');
            $flag = M('tbatch_info')->where("m_id = '{$mId}'")->save($data);
            if ($flag === false){
                M()->rollback();
                $this->error('上架失败！');
            }
            //获取商品ID
            $goodsId = M('tbatch_info')->where("m_id = '{$mId}'")->getField('goods_id');
            //获取sku商品标识
            $isSku = $this->SkuService->getIsSkuInfo($goodsId);
            //判断是否SKU商品
            if('1' === $isSku){
                //获取可以上架的规格商品信息
                $goodsSkuInfo = M('tgoods_sku_info')->where(array('goods_id'=>$goodsId,'node_id'=>$this->node_id,'status'=>'0'))->field('id')->select();
                //取得可上架商品关联ID
                if(!$goodsSkuInfo){
                    M()->rollback();
                    $this->error('商品信息有误，该商品无法重新上架！');
                }
                $idList = implode(',',array_multi2single($goodsSkuInfo));
                $map = array('m_id'=>$mId, 'skuinfo_id'=>array('in', $idList), 'status'=>'1');
                $result = M('tecshop_goods_sku')->where($map)->save($data);
                if ($result === false){
                    M()->rollback();
                    $this->error('上架失败！');
                }
            }else{
                //普通商品上架查找原普通上架信息
                $map = array('m_id'=>$mId, 'skuinfo_id'=>0, 'status'=>'1');
                $id = M('tecshop_goods_sku')->where($map)->order('id desc')->limit(1)->getField('id');
                //原规格商品变普通商品，重新生成普通商品信息
                if(NULL === $id){
                    M()->rollback();
                    $this->error('商品信息有误，请重新上架！');
                }else{
                    $flag = M('tecshop_goods_sku')->where($map)->save($data);
                    if ($flag === false){
                        M()->rollback();
                        $this->error('上架失败！');
                    }
                }

            }
        }

        M()->commit();
        //=======会员优惠 保存===========================
        if ($this->node_id == C('meihui.node_id')) {
            foreach($reqData['discountPrice'] as $k=>$v) {
                $discount = array(
                    'node_id' => $this->node_id,
                    'goods_id' => $goodsId,
                    'vip_type' => $reqData['discountType'][$k],
                    'vip_discount' => $v,
                );
                $result = D('MeiHui', 'Model')->addByVipDiscount($discount);
                if($result === false) {
                    node_log('美惠创建商品会员优惠更新失败', print_r($discount, true));
                }
            }
        }
        // 爱蒂宝
        if ($this->node_id == C('adb.node_id')) {
            $adbb_id = I("adbb_id");
            $openStores = I("openStores");
            $param = array(
                'node_id' => $this->node_id,
                'b_id' => $adbb_id,
                'openStores' => $openStores);
            B('AdbGoodsEditSave', $param);
        }
        node_log('旺财小店商品编辑', print_r($reqData, true));
        // $this->success('商品编辑成功！'.($puton_flag == 1 ? '商品上架成功！': ''));
        $this->ajaxReturn(1, "商品编辑成功！", 1);
    }

    //商品下架
    public function Offline() {
        $mId = I('id', 0, 'intval');
        if ($mId == 0)
            $this->error('参数错误！');

        $map = array(
            'node_id' => $this->nodeId,
            'id' => $mId,
            'batch_type' => '31');
        $marketInfo = M('tmarketing_info')->where($map)->find();
        if (! $marketInfo)
            $this->error('参数错误！');

        $batchInfo = M('tbatch_info')->where("m_id = '{$mId}'")->find();
        if ($batchInfo['status'] != '0'){
            $this->error('该订单已经下线或过期，不能下架');
        }
        if ($batchInfo['is_delete'] != '0'){
            $this->error('该订单已经删除，不能下架');
        }

        M()->startTrans();
        // =====================tbatch_info更新==========================
        $data = array('status' => '1');
        $flag = M('tbatch_info')->where("m_id = '{$mId}'")->save($data);
        if (false === $flag){
            M()->rollback();
            $this->error('下架失败！');
        }
        $data = array('is_commend' => '9');
        $flag = M('tecshop_goods_ex')->where("m_id = '{$mId}'")->save($data);
        if ($flag === false) {
            M()->rollback();
            $this->error('下架失败1！');
        }
        //商品下架
        $data = array('status' => '1');
        $flag = M('tecshop_goods_sku')->where("m_id = '{$mId}'")->save($data);
        if ($flag === false) {
            M()->rollback();
            $this->error('下架失败2！');
        }

        M()->commit();

        $this->ajaxReturn(1);
        // $this->success('下架成功！');
    }

    //商品上架
    public function Shelves() {
        $mId = I('id', 0, 'intval');
        if ($mId == 0){
            $this->error('参数错误！');
        }
        $map = array(
            'node_id' => $this->nodeId,
            'id' => $mId,
            'batch_type' => '31');
        $marketInfo = M('tmarketing_info')->where($map)->find();
        if (! $marketInfo){
            $this->error('参数错误！');
        }
        $batchInfo = M('tbatch_info')->where("m_id = '{$mId}'")->find();
        if ($batchInfo['status'] == '0'){
            $this->error('该订单已经上线，不能重复上线');
        }
        if ($batchInfo['is_delete'] != '0'){
            $this->error('该订单已经删除，不能上架');
        }

        M()->startTrans();
        // =====================tbatch_info更新==========================
        $data = array('status' => '0');
        $flag = M('tbatch_info')->where("m_id = '{$mId}'")->save($data);
        if ($flag === false){
            M()->rollback();
            $this->error('上架失败！');
        }
        //获取商品ID
        $goodsId = M('tbatch_info')->where("m_id = '{$mId}'")->getField('goods_id');
        //获取sku商品标识
        $isSku = $this->SkuService->getIsSkuInfo($goodsId);
        //判断是否SKU商品
        if('1' === $isSku){
            //获取可以上架的规格商品信息
            $goodsSkuInfo = M('tgoods_sku_info')->where(array('goods_id'=>$goodsId,'node_id'=>$this->node_id,'status'=>'0'))->field('id')->select();
            //取得可上架商品关联ID
            if(!$goodsSkuInfo){
                M()->rollback();
                $this->error('商品信息有误，该商品无法重新上架！');
            }
            $idList = implode(',',array_multi2single($goodsSkuInfo));
            $map = array('m_id'=>$mId, 'skuinfo_id'=>array('in', $idList), 'status'=>'1');
            $result = M('tecshop_goods_sku')->where($map)->save($data);
            if ($result === false){
                M()->rollback();
                $this->error('上架失败！');
            }
        }else{
            //普通商品上架查找原普通上架信息
            $map = array('m_id'=>$mId, 'skuinfo_id'=>0, 'status'=>'1');
            $id = M('tecshop_goods_sku')->where($map)->order('id desc')->limit(1)->getField('id');
            //原规格商品变普通商品，重新生成普通商品信息
            if(NULL === $id){
                M()->rollback();
                $this->error('商品信息有误，请重新上架！');
            }else{
                $flag = M('tecshop_goods_sku')->where($map)->save($data);
                if ($flag === false){
                    M()->rollback();
                    $this->error('上架失败！');
                }
            }

        }

        M()->commit();

        $this->ajaxReturn(1);
        // $this->success('下架成功！');
    }

    //2016-06-12 商品管理优化：添加删除商品功能
    public function Delete() {
        $mId = I('id', 0, 'intval');
        if ($mId == 0)
            $this->error('参数错误！');

        $map = array(
            'node_id' => $this->nodeId,
            'id' => $mId,
            'batch_type' => '31');
        $marketInfo = M('tmarketing_info')->where($map)->find();
        if (! $marketInfo)
            $this->error('参数错误！');

        $batchInfo = M('tbatch_info')->where("m_id = '{$mId}'")->find();
        if ($batchInfo['status'] == '0'){
            $this->error('该订单已经上架，不能删除');
        }
        if ($batchInfo['is_delete'] != '0'){
            $this->error('该订单已经删除，不能下架');
        }

        M()->startTrans();
        // =====================tbatch_info更新==========================
        $data = array('is_delete' => '1');
        $flag = M('tbatch_info')->where("m_id = '{$mId}'")->save($data);
        if (false === $flag){
            M()->rollback();
            $this->error('删除失败！');
        }

        M()->commit();

        $this->ajaxReturn(1);
        // $this->success('下架成功！');
    }

    //2016-06-14 商品管理优化：批量操作
    public function batchEdit()
    {
        $type = I("type", 0, "intval");//批量操作类型：0代表批量下架；1代表批量上架；2代表批量删除
        $ids = I("post.ids");//选中的商品的编号
        if (empty($ids)) {
            $this->error('参数错误！');
        }
        $map = array(
            'node_id' => $this->nodeId,
            'm_id' => array('in', $ids),
            'batch_type' => '31'
        );
        $marketInfo = M('tmarketing_info')->where($map)->select();
        if (!$marketInfo) {
            $this->error('参数错误！');
        }

        if ($type == 0) {//下架
            //查询条件
            $where = array('status' => '1', 'is_delete' => '1', '_logic' => 'OR');
            $map1['_complex'] = $where;
            $map1['m_id'] = array('in', $ids);
            $offCount = M('tbatch_info')->where($map1)->count();
            if ($offCount > 0) {
                $this->error('选中的订单中有已经删除或已下架订单，不能重复操作');
            }

            M()->startTrans();
            // =====================tbatch_info更新==========================
            $data1 = array('status' => '1');
            $where1['m_id'] = array('in', $ids);
            $flag1 = M('tbatch_info')->where($where1)->save($data1);
            if ($flag1 === false) {
                M()->rollback();
                $this->error('下架失败！');
            }
            $data1 = array('is_commend' => '9');
            $flag1 = M('tecshop_goods_ex')->where($where1)->save($data1);
            if ($flag1 === false) {
                M()->rollback();
                $this->error('下架失败1！');
            }
            $data1 = array('status' => '1');
            $flag1 = M('tecshop_goods_sku')->where($where1)->save($data1);
            if ($flag1 === false) {
                M()->rollback();
                $this->error('下架失败2！');
            }

            M()->commit();
        } elseif ($type == 1) {//上架
            $where = array('status' => '0', 'is_delete' => '1', '_logic' => 'OR');
            $map2['_complex'] = $where;
            $map2['m_id'] = array('in', $ids);
            $sheCount = M('tbatch_info')->where($map2)->count();
            if ($sheCount > 0) {
                $this->error('选中的订单中有已经删除或已上架订单，不能重复操作');
            }
            M()->startTrans();
            // =====================tbatch_info更新==========================
            $data2 = array('status' => '0');
            $where2['m_id'] = array('in', $ids);
            $flag2 = M('tbatch_info')->where($where2)->save($data2);
            if ($flag2 == false) {
                M()->rollback();
                $this->error('上架失败！');
            }
            $arr_ids = explode(',',$ids);
            foreach ($arr_ids as $mId){
                //获取商品ID
                $goodsId = M('tbatch_info')->where("m_id = '{$mId}'")->getField('goods_id');
                //获取sku商品标识
                $isSku = $this->SkuService->getIsSkuInfo($goodsId);
                //判断是否SKU商品
                if('1' === $isSku){
                    //获取可以上架的规格商品信息
                    $goodsSkuInfo = M('tgoods_sku_info')->where(array('goods_id'=>$goodsId,'node_id'=>$this->node_id,'status'=>'0'))->field('id')->select();
                    //取得可上架商品关联ID
                    if(!$goodsSkuInfo){
                        M()->rollback();
                        $this->error('商品信息有误，该商品无法重新上架！');
                    }
                    $idList = implode(',',array_multi2single($goodsSkuInfo));
                    $map = array('m_id'=>$mId, 'skuinfo_id'=>array('in', $idList), 'status'=>'1');
                    $result = M('tecshop_goods_sku')->where($map)->save($data2);
                    if ($result === false){
                        M()->rollback();
                        $this->error('上架失败！');
                    }
                }else{
                    //普通商品上架查找原普通上架信息
                    $map = array('m_id'=>$mId, 'skuinfo_id'=>0, 'status'=>'1');
                    $id = M('tecshop_goods_sku')->where($map)->order('id desc')->limit(1)->getField('id');
                    //原规格商品变普通商品，重新生成普通商品信息
                    if(NULL === $id){
                        M()->rollback();
                        $this->error('商品信息有误，请重新上架！');
                    }else{
                        $flag = M('tecshop_goods_sku')->where($map)->save($data2);
                        if ($flag === false){
                            M()->rollback();
                            $this->error('上架失败！');
                        }
                    }

                }
            }

            M()->commit();
        }
        elseif ($type == 2) {//删除商品
            $where = array('is_delete' => '1', 'm_id' => array('in', $ids));
            $delCount = M('tbatch_info')->where($where)->count();
            if ($delCount > 0){
                $this->error('选中的订单中有已经删除或已上架订单，不能重复操作');
            }
            M()->startTrans();
            // =====================tbatch_info更新==========================
            $data3 = array('is_delete'=>'1');
            $where3['m_id'] = array('in',$ids);
            $flag3 = M('tbatch_info')->where($where3)->save($data3);
            if ($flag3 === false){
                M()->rollback();
                $this->error('删除失败！');
            }

            M()->commit();
        }
        elseif($type == 3){//2016-06-15 批量修改(修改分组)
            $cids =I('post.cids');//选中的分组编号
            //修改前对选中商品的判断
            $where = array('is_delete' => '1', '_logic' => 'OR');
            $map2['_complex'] = $where;
            $map2['m_id'] = array('in', $ids);
            $claCount = M('tbatch_info')->where($map2)->count();
            if ($claCount > 0){
                $this->error('选中的订单中有已经删除或已上架订单，不能修改分组');
            }

            M()->startTrans();
            // =====================tecshop_goods_ex更新==========================
            $data4 = array('ecshop_classify'=>implode(',',$cids));
            $where4['m_id'] = array('in',$ids);
            $flag4 = M('tecshop_goods_ex')->where($where4)->save($data4);
            $sql = M('tecshop_goods_ex')->getLastSql();
            if ($flag4 === false){
                M()->rollback();
                $this->error('分组修改失败！');
            }

            M()->commit();
        }

        $this->ajaxReturn(1);
    }

    //2016-06-29 商品管理优化：批量修改分组弹框
    public function addGroup() {
        //获取商品分组信息
        $classify = $this->GoodsInfoModel->getNodeClassify();
        $this->assign('classify',$classify);
        $this->display();
    }


    //检查商品提交信息
    public function checkPostInfo(){
        $rules = array(
            //商品基本内容
            'goodsName' => $this->GoodsInfoModel->_verifyInfo(true, '商品名称', array('maxlen_cn'=>24)),
            'goodsImg' => $this->GoodsInfoModel->_verifyInfo(true, '第一张图片'),
            'customerNo' => $this->GoodsInfoModel->_verifyInfo(true, '商品编号', array('maxlen_cn'=>24)),
            'goodsDesc' => $this->GoodsInfoModel->_verifyInfo(false, '商品描述', array('maxlen_cn'=>200)),
            'classify' => $this->GoodsInfoModel->_verifyInfo(true, '所属分组', array('inarr'=>array_keys($this->classify_arr), 'strtype'=>'int')),
            'discountType' => $this->GoodsInfoModel->_verifyInfo(true, '会员类型', array('strtype'=>'number')),
            'discountPrice' => $this->GoodsInfoModel->_verifyInfo(true, '会员优惠', array('strtype'=>'number')),
            'saleProportion' => $this->GoodsInfoModel->_verifyInfo(false, '销售比例', array('strtype'=>'int')),

            //商品设置
            'batchAmt' => $this->GoodsInfoModel->_verifyInfo(false, '销售价格', array('strtype'=>'number')),
            'isShowPrice' => $this->GoodsInfoModel->_verifyInfo(true, '是否显示市场价', array('inarr'=>array('0', '1'), 'strtype'=>'int')),
            'marktPrice' => $this->GoodsInfoModel->_verifyInfo(true, '市场价格', array('strtype'=>'number')),
            'salesNum' => $this->GoodsInfoModel->_verifyInfo(false, '出售数量', array('strtype'=>'int')),
            'isShowSalesNum' => $this->GoodsInfoModel->_verifyInfo(false, '是否显示销售数量', array('inarr'=>array('0', '1'), 'strtype'=>'int')),
            'isShowSalesTime' => $this->GoodsInfoModel->_verifyInfo(false, '是否显示销售时间', array('inarr'=>array('0', '1'), 'strtype'=>'int')),
            'beginTime' => $this->GoodsInfoModel->_verifyInfo(false, '商品销售开始时间', array('format'=>'Y-m-d H:i:s', 'strtype'=>'datetime')),
            'endTime' => $this->GoodsInfoModel->_verifyInfo(false, '商品销售结束时间', array('format'=>'Y-m-d H:i:s', 'strtype'=>'datetime')),

            //配送设置
            'deliveryFlag' => $this->GoodsInfoModel->_verifyInfo(true, '物流配送', array('inarr'=>array('0', '1'), 'strtype'=>'int')),
            'storeFlag' => $this->GoodsInfoModel->_verifyInfo(true, '门店提领', array('inarr'=>array('0', '1'), 'strtype'=>'int')),
            'deliPayFlag' => $this->GoodsInfoModel->_verifyInfo(false, '支持货到付款', array('inarr'=>array('0', '1'), 'strtype'=>'int')),
            'sendGift' => $this->GoodsInfoModel->_verifyInfo(false, '支持送礼', array('inarr'=>array('0', '1'), 'strtype'=>'int')),
            //门店信息
            'shop' => $this->GoodsInfoModel->_verifyInfo(true, '是否子门店'),
            'openStores' => $this->GoodsInfoModel->_verifyInfo(true, '门店列表'),

            //促销设置
            'integralFlag' => $this->GoodsInfoModel->_verifyInfo(false, '积分购买', array('inarr'=>array('0', '1'), 'strtype'=>'int')),
            'bonusFlag' => $this->GoodsInfoModel->_verifyInfo(false, '红包购买', array('inarr'=>array('0', '1'), 'strtype'=>'int')),
            'dayBuyNumFlag' => $this->GoodsInfoModel->_verifyInfo(false, '商品每日限购', array('inarr'=>array('0', '1'), 'strtype'=>'int')),
            'dayBuyNum' => $this->GoodsInfoModel->_verifyInfo(false, '起购数量', array('strtype'=>'int')),
            'personBuyNumFlag' => $this->GoodsInfoModel->_verifyInfo(false, '每人限购', array('inarr'=>array('0', '1'), 'strtype'=>'int')),
            'personBuyNum' => $this->GoodsInfoModel->_verifyInfo(false, '起购数量', array('strtype'=>'int')),
            'fristBuyNumFlag' => $this->GoodsInfoModel->_verifyInfo(false, '起购数量', array('inarr'=>array('0', '1'), 'strtype'=>'int')),
            'fristBuyNum' => $this->GoodsInfoModel->_verifyInfo(false, '起购数量', array('strtype'=>'int')),
            // 粉丝专享配置
            'memberJoinFlag' => $this->GoodsInfoModel->_verifyInfo(false, '购买对象限制', array('inarr'=>array('0', '1'), 'strtype'=>'int')),

            //详情设置
            'wapInfo' => $this->GoodsInfoModel->_verifyInfo(false, '商品描述详情'),
            //商品分组信息
            'group' => $this->GoodsInfoModel->_verifyInfo(true, '商品分组'),
            // sku附加项
            'isSku' => $this->GoodsInfoModel->_verifyInfo(true, '是否有sku信息'),
            'dataPriceInfo' => $this->GoodsInfoModel->_verifyInfo(true, 'sku价格列表'),
            'countNum' => $this->GoodsInfoModel->_verifyInfo(true, 'sku总库存'));

        $reqData = $this->_verifyReqData($rules);

        //时间格式化
        $reqData['beginTime'] = dateformat($reqData['beginTime'], 'YmdHis');
        $reqData['endTime'] = dateformat($reqData['endTime'], 'YmdHis');
        // 微信粉丝专享判断    ----询问产品如何处理
//        if (($reqData['memberJoinFlag'] == '1') &&
//             ! $reqData['fans_collect_url'])
//            $this->error("购买对象限制为微信粉丝时请填入引导页链接");
        // 自提才需要配置短彩信内容、配置验证时间
        $rules = array();
        //检查商品名称是否符合规则
        $rulesInfo = '/[\'.,:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/';
        if(preg_match($rulesInfo, $reqData['goodsName'])){
            $this->error("商品名称含有特殊字符，提交失败");
        }

        //配送方式检查
        if('1' == $reqData['storeFlag'] && '1' == $reqData['deliveryFlag']){
            $reqData['deliveryFlag'] = '0-1';
        }else{
            if(!$reqData['storeFlag'] && !$reqData['deliveryFlag']){
                $this->error("请选择是否配送方式");
            }elseif('1' == $reqData['deliveryFlag']){
                $reqData['deliveryFlag'] = '1';
            }else{
                $reqData['deliveryFlag'] = '0';
            }
        }
        //图片处理
        $reqData['goodsImg'] = str_replace('..', '', $reqData['goodsImg']);
        if (! check_str($reqData['goodsImg'], array('null' => false), $error)) {
            $this->error("商品图片{$error}", array('返回' => "javascript:history.go(-1)"));
        }
        //处理限购值
        if('0' == $reqData['fristBuyNumFlag']){
            $reqData['fristBuyNum'] = '';
        }
        if('0' == $reqData['dayBuyNumFlag']){
            $reqData['dayBuyNum'] = '';
        }
        if('0' == $reqData['personBuyNumFlag']){
            $reqData['personBuyNum'] = '';
        }
        //限购判断处理
        if('1' == $reqData['dayBuyNumFlag'] && $reqData['dayBuyNum'] > $reqData['salesNum']){
            $this->error("日限购数不能大于商品售卖数量", array('返回' => "javascript:history.go(-1)"));
        }
        //判断起购商品是否正确
        if ('1' == $reqData['fristBuyNumFlag']) {
            if($reqData['fristBuyNum'] > $reqData['salesNum']){
            $this->error("起购数不能大于商品售卖数量", array('返回' => "javascript:history.go(-1)"));
        }
            if ('1' == $reqData['personBuyNumFlag']) {
                if($reqData['fristBuyNum'] > $reqData['personBuyNum']){
            $this->error("起购数不能大于个人限购商品数量", array('返回' => "javascript:history.go(-1)"));
        }
            }
            if('1' == $reqData['dayBuyNumFlag']){
                if ($reqData['fristBuyNum'] > $reqData['dayBuyNum'] ) {
            $this->error("起购数不能大于当日限购商品数量", array('返回' => "javascript:history.go(-1)"));
        }
            }
        }

        //判断个人限购是否正确
        if('1' == $reqData['personBuyNumFlag'] && $reqData['personBuyNum'] > $reqData['salesNum']){
            $this->error("个人限购数不能大于商品售卖数量", array('返回' => "javascript:history.go(-1)"));
        }
        //初始化变量
        if (!empty($reqData['storeFlag'])) {
            $rules['verifyTimeType'] = $this->GoodsInfoModel->_verifyInfo(false, '验证时间', array('inarr'=>array('0', '1'), 'strtype'=>'int'));

            $rules['smsText'] = $this->GoodsInfoModel->_verifyInfo(false, '卡劵说明',array('maxlen_cn'=>200));
            $rules['printText'] = $this->GoodsInfoModel->_verifyInfo(false, '卡劵说明',array('maxlen_cn'=>100));
            $rules['sendGift'] = $this->GoodsInfoModel->_verifyInfo(false, '支持送礼', array('inarr'=>array('0', '1'), 'strtype'=>'int'));

            if (I('verifyTimeType', 0, 'intval') == '0') {
                $rules['verifyBeginDate'] = $this->GoodsInfoModel->_verifyInfo(false, '验证开始时间', array('format'=>'Ymd', 'strtype'=>'datetime'));
                $rules['verifyEndDate'] = $this->GoodsInfoModel->_verifyInfo(false, '验证结束时间', array('format'=>'Ymd', 'strtype'=>'datetime'));
            } else {
                $rules['verifyBeginDays'] = $this->GoodsInfoModel->_verifyInfo(false, '验证开始天数', array('minval'=>'0', 'strtype'=>'int'));
                $rules['verifyEndDays'] = $this->GoodsInfoModel->_verifyInfo(false, '验证结束天数', array('minval'=>'0', 'strtype'=>'int'));
            }
            //数据验证
            $reqData = array_merge($reqData, $this->_verifyReqData($rules));
            $reqData['verifyTimeType'] = $reqData['verifyTimeType'] == '' ? 0 : $reqData['verifyTimeType'];
            //时间格式化
            $reqData['verifyBeginDate'] = dateformat($reqData['verifyBeginDate'], 'Ymd', '000000');
            $reqData['verifyEndDate'] = dateformat($reqData['verifyEndDate'], 'Ymd', '235959');
            if ($reqData['verifyTimeType'] == '0' && $reqData['verifyBeginDate'] > $reqData['verifyEndDate']){
                $this->error('验证结束时间不能大于验证开始时间');
            }
            if ($reqData['verifyTimeType'] == '1' && $reqData['verifyBeginDays'] > $reqData['verifyEndDays']){
                $this->error('验证结束时间不能大于验证开始时间');
            }
            // 凭证结束时间必须大于活动结束时间
            if ($reqData['verifyTimeType'] == '0'  && $reqData['endTime'] > $reqData['verifyEndDate']){
                $this->error("上架时商品兑换结束时间必须大于等于销售结束时间", U('index'));
            }
        }

        // 上架 则活动结束时间必须大于当前时间
        if ($reqData['endTime'] < date('YmdHis')){
            $this->error("上架时商品销售结束时间必须大于等于当前时间", U('index'));
        }
        //物流商品不支持送礼
        if('1' == $reqData['deliveryFlag']){
            $reqData['sendGift'] = '0';
        }
        //判断是否SKU商品
        //2016-06-21 数据中的双引号被转译，需要将转译符“\”去掉
        $reqData['skuMarkPrice'] = json_decode($reqData['dataPriceInfo'], true);

        if(count($reqData['skuMarkPrice']) > 0){
            $reqData['isSku'] = true;
            $reqData['salesNum'] =  $reqData['countNum'];
            //2016-06-21 数据中的双引号被转译，需要将转译符“\”去掉
            $reqData['skuName'] = json_decode(I('post.dataNameInfo', null, false), true);
            foreach ($reqData['skuMarkPrice'] as $v){
                if('' == $v['price']){
                    $this->error("请输入商品价格", U('index'));
                }
                if($v['price'] <= 0){
                    $this->error("商品价格必须大于0元", U('index'));
                }
                if(0 > ((int)$v['num'])){
                    $this->error("上架商品数量必须大于1", U('index'));
                }
            }
        }else{
            $reqData['isSku'] = false;
            if ('' === $reqData['batchAmt']){
                $this->error("请输入销售价格");
            }
            if($reqData['batchAmt'] <= 0){
                $this->error("商品价格必须大于0元，提交失败");
            }
        }

        //判断商品数目
        if($reqData['salesNum'] < 1){
            $this->error("商品数量不正确，请更正");
        }

        if ('1' === $reqData['isShowPrice'] && '' == $reqData['marktPrice']){
            $this->error('请输入商品市场价格');
            return false;
        }
        //处理商品分组信息
        if(count($reqData['group']) < 1){
            $this->error('请选择商品分组');
            return false;
        }

        return $reqData;
    }

    //将原goodPutOn的方法转移到这边
    public function successPage() {
        // 上架成功的页面
        $channelInfo = $this->GoodsInfoModel->getTbatchChannelId();
        if(!$channelInfo){
            $this->error('当前渠道信息有误');
        }
        $qrcodeId = $channelInfo['id'];
        $time = substr($channelInfo['add_time'],4,10);//截取时间中的月日时分秒

        $filename = $this->GoodsInfoModel->getShopName($qrcodeId);//根据id查询出商品名

        $url = $this->GoodsInfoModel->getQRcodeUrl($qrcodeId);//获取要存入二维码中的Url
        $result = $this->GoodsInfoModel->mkQRCode($url,$time,$qrcodeId);//生成二维码存放在服务器
        if(!$result){
            $this->error('生成二维码数据异常');
        }
        $this->assign('qrcodeTime',$time);
        $this->assign('name', $filename);
        $this->assign('id', $qrcodeId);
        $this->display();
    }

    public function editResult() {
        // 二次上架成功的页面
        $id = I('id');
        if (! $id) {
            $this->error('参数有误');
        }
        //获取二维码ID
        $qrcodeId = $this->GoodsInfoModel->getQrcodeId($id);

        $filename = $this->GoodsInfoModel->getShopName($qrcodeId);
        $this->assign('name', $filename);
        $this->assign('id', $id);
        $this->display();
    }

    // 编辑模板提交页
    public function editModelInfo()
    {
        $model = M('tmarketing_info');
        $data  = I('post.');

        if (!empty($data['select_type'])) {
            $select_type = implode('-', $data['select_type']);
        } else {
            $select_type = '';
        }
        if (!empty($data['sns_type'])) {
            $sns_type = implode('-', $data['sns_type']);
        } else {
            $sns_type = '';
        }

        $edit_wh = array(
            'node_id' => array(
                'exp',
                "in (" . $this->nodeIn() . ")"
            ),
            'id'      => $data['id']
        );
        $markInfo = $model->where($edit_wh)->field('node_id, config_data')->find();
        if (!$markInfo) {
            $this->error('未查询到记录');
        }

        $one_map = array(
            'name'       => get_val($data,'name'),
            'batch_type' => $this->BATCH_TYPE,
            'node_id'    => $markInfo['node_id'],
            'id'         => array(
                'neq',
                get_val($data,'id')
            )
        );

        $info_ = $model->where($one_map)->count('id');
        if ($info_ > 0) {
            $this->error("活动名称重复");
        }

        $bm_id = $data['id'];
        $map   = array(
            'node_id' => $markInfo['node_id'],
            'id'      => $data['id']
        );

        // 背景图
        if ($data['resp_bg_img'] != '' && $data['reset_bg'] == '1') {
            $bg_img = str_replace('..', '', get_val($data,'resp_bg_img'));
        } else {
            $bg_img = $data['resp_bg_img'];
        }
        //获取商品已存在信息
        $serData = unserialize($markInfo['config_data']);
        if(!$serData){
            $serData = array();
        }
        // 修改积分
        $serData['integral_sign'] = 0;
        $serData['integral_num'] = 0;

        if (!empty($data['integral'])) {
            $configData = $model->where($edit_wh)->getField('config_data');
            $serData    = unserialize($configData);
            if (empty($serData)) {
                $serData = array(
                    'integral_sign' => 1,
                    'integral_num'  => $data['integral']
                );
            } else {
                $serData['integral_num'] = $data['integral'];
            }
        }
        // 修改单一颜色到大字段里去
        if ($data['page_style'] == 5) {
            // bg_color 单一背景色的名称
            $serData['bg_color'] = $data['card_color'];
        }
        // 保存微信分享设置
        if (!empty($data['share_title'])) {
            $serData['share_title'] = $data['share_title'];
        }
        if (!empty($data['share_introduce'])) {
            $pureStr                    = str_replace(' ', '', $data['share_introduce']);
            $pureStr                    = str_replace(PHP_EOL, '', $pureStr);
            $serData['share_introduce'] = $pureStr;
        }
        //新版电商展示页面标识
        $serData['isShowAd'] = 1;
        $serData['isShowElAd'] = 0;

        $data_arr = array(
            'color' => get_val($data,'color'),
            'wap_title' => get_val($data,'wap_title'),
            'wap_info' => get_val($data,'wap_info'),
            'memo' =>  get_val($data,'memo'),
            'select_type' => $select_type,
            'sns_type' => $sns_type,
            'defined_one_name' => get_val($data,'defined10'),
            'defined_two_name' => get_val($data,'defined11'),
            'defined_three_name' => get_val($data,'defined12'),
            'node_name' => get_val($data,'node_name'),
            'page_style' => get_val($data,'page_style'),
            'bg_style' => get_val($data,'bg_style'),
            'bg_pic' => $bg_img,
            'is_show' => '1',
            'config_data' => serialize($serData));
        // 微信分享的图标
        if (! empty($data['share_pic'])) {
            $data_arr['share_pic'] = $data['share_pic'];
        }

//        $code_img = $data['resp_code_img'];
        $music = get_val($data,'resp_music');
        // 商户名称
        if ($data['node_name_radio'] == '0' || empty($data['node_name'])) {
            $data_arr['node_name'] = '';
        } else {
            $data_arr['node_name'] = $data['node_name'];
        }

        // 商户LOGO
        if ($data['is_log_img'] == '1') {
            if (empty($data['resp_log_img'])) { // 开启了商户LOG 并且没有上传LOG的时候
                $getNodeImg = M('tnode_info')->where(
                    array(
                        'node_id' => $this->node_id))->getField('head_photo');
                if (empty($getNodeImg)) { // 如果没有商户头像的时候
                    $imgUrl = C('TMPL_PARSE_STRING.__PUBLIC__') .'/Image/wap-logo-wc.png';
                    $data_arr['log_img'] = $imgUrl;
                } else {
                    $data_arr['log_img'] = C('TMPL_PARSE_STRING.__UPLOAD__') . '/' . $getNodeImg;
                }
            } else {
                $imgUrl = C('TMPL_PARSE_STRING.__UPLOAD__').'/';
                $imgUrl1 = C('TMPL_PARSE_STRING.__PUBLIC__').'/';
                //无上传前缀路径的
                if(!stristr($data['resp_log_img'],$imgUrl) && !stristr($data['resp_log_img'],$imgUrl1)){
                    $data_arr['log_img'] = C('TMPL_PARSE_STRING.__UPLOAD__') . '/' . $data['resp_log_img'];
                }else{
                    //商户上传的图片
                    if(stristr($data['resp_log_img'],$imgUrl)) {
                        $data['resp_log_img'] = str_replace($imgUrl, '', $data['resp_log_img']);
                        // 使用商户上传的LOG
                        $data_arr['log_img'] = C('TMPL_PARSE_STRING.__UPLOAD__') . '/' . $data['resp_log_img'];
                    }

                    //旺财LOGO
                    if(stristr($data['resp_log_img'],$imgUrl1)){
                        $data['resp_log_img'] = str_replace($imgUrl1, '', $data['resp_log_img']);
                        // 使用商户上传的LOG
                        $data_arr['log_img'] = C('TMPL_PARSE_STRING.__PUBLIC__') . '/'.$data['resp_log_img'];
                    }
                }
            }
        } else { // 关闭的时候清空字段值
            $data_arr['log_img'] = '';
        }

        // 音乐
        if (get_val($data,'is_music') == '1' && ! empty($music)) {
            $data_arr['music'] = $music;
        } else {
            if (get_val($data,'is_music') == '0') {
                $data_arr['music'] = '';
            }
        }

        $tranDb = new Model();

        $resp_id = $model->where($map)->save($data_arr);

        if ($resp_id === false) {
            $tranDb->rollback();
            $this->ajaxReturn(
                array(
                    'status' => 0,
                    'info' => '更新失败'), 'JSON');
        }
        $this->ajaxReturn(
            array(
                'status' => 1,
                'info' => '更新成功'), 'JSON');
    }

    public function checkwxyx() {
        $info = M('tweixin_info')->where(['node_id' => $this->node_id, 'auth_flag' => '1'])->find();
        if ($info)
            $this->success('成功', array('type' => $info['account_type']));
        else
            $this->error('失败');
    }

    /**
     * 美惠非标 删除指定会员优惠
     */
    public function delVipDiscount()
    {
        $id = I("id", "");
        if(empty($id)) {
            $this->error("参数错误！");
        }

        $result = D('MeiHui', 'Model')->delByVipDiscount($id);
        if($result === false) {
            $this->error('美惠商品删除会员优惠失败！');
        }
        $this->success('美惠商品删除会员优惠');
    }
}
