<?php

class GoodsInfoAction extends IntegralAuthAction {

    public $GOODS_TYPE = CommonConst::INTEGRAL_GOODS_TYPE;

    public $BATCH_TYPE = CommonConst::BATCH_TYPE_GOODS;

    public $CHANNEL_TYPE = CommonConst::CHANNEL_TYPE_INTEGRAL;

    public $CHANNEL_SNS_TYPE = CommonConst::SNS_TYPE_INTEGRAL;

    public $img_path = "";

    public $tmp_path = "";

    public $_authAccessMap = '*';

    public $classify_arr = array();

    public function _initialize() {
        parent::_initialize();
        $path_arr = C('BATCH_IMG_PATH_NAME');
        $img_path = $path_arr[$this->BATCH_TYPE] . '/' . $this->node_id . '/';
        $this->img_path = $img_path;
        $map = array(
            'node_id' => $this->nodeId);
        $this->classify_arr = M('tintegral_classify')->where($map)->getField(
            'id,class_name', true);
        $this->assign('classify_arr', $this->classify_arr);
        // 临时路径
        $tmp_path = 'Upload/img_tmp' . '/' . $this->node_id . '/';
        $this->assign('img_path', $img_path);
        $this->assign('tmp_path', $tmp_path);
    }

    public function index() {
        $data = $_REQUEST;
        $map = array(
            'a.node_id' => $this->node_id, 
            'a.goods_type' => $this->GOODS_TYPE, 
            'b.is_delete' => '0');
        $goods_name = I('goods_name', null);
        if ($goods_name != "") {
            $map['a.goods_name'] = array(
                'like', 
                "%$goods_name%");
        }
        $pos_count_status = I('pos_count_status');
        if ($pos_count_status == '1') {
            // 未上架，tmarketing_info状态为1
            $map['c.status'] = '2';
        } elseif ($pos_count_status == '2') {
            $map['_string'] = "a.storage_type=1 and a.remain_num>=1 and c.status=1 or storage_type !='1' and c.status=1";
        } elseif ($pos_count_status == '3') {
            $map['a.storage_type'] = '1';
            $map['_string'] = "a.remain_num<1";
        }
        $mapcount = M()->table("tgoods_info a")->join(
            "tintegral_goods_ex b on b.goods_id=a.id")
            ->join("tmarketing_info c on c.id=b.m_id")
            ->where($map)
            ->count();
        import('ORG.Util.Page'); // 导入分页类
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        foreach ($data as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        $show = $Page->show(); // 分页显示输出
        $list = M()->table("tgoods_info a")
            ->field(
            "a.id,a.is_sku,a.goods_id as goodsId,a.goods_name,a.market_price,a.remain_num,c.status,a.storage_type,a.storage_num,a.exchange_num,b.ecshop_classify,b.m_id,b.goods_id")
            ->join("tintegral_goods_ex b on b.goods_id=a.id")
            ->join("tmarketing_info c on c.id=b.m_id")
            ->where($map)
            ->order('a.id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        // 计算sku商品
        foreach ($list as $key => $value) {
            if ($value['is_sku'] == '1') {
                $goods_id = $value['goodsId'];
                $sku_goods = M('tgoods_sku_integral_info')->where(
                    "goods_id = '$goods_id'")
                    ->order('market_price')
                    ->select();
                $sku_goods_end = end($sku_goods);
                if ($sku_goods[0]['market_price'] ==
                     $sku_goods_end['market_price']) {
                    $list[$key]['market_price'] = $sku_goods[0]['market_price'];
                } else {
                    $list[$key]['market_price'] = $sku_goods[0]['market_price'] .
                         "~" . $sku_goods_end['market_price'];
                }
            }
        }
        // 创建sku信息
        // $skuObj = D('IntegralSku', 'Service');
        // foreach ($list as &$val) {
        // //sku商品
        // if ("1" === $val['is_sku']) {
        // $val = $skuObj->makeGoodsListInfo($val, $this->node_id);
        // }
        // }
        $pos_status = array(
            '1' => '未上架', 
            '2' => '可兑换', 
            '3' => '已兑完');
        $this->assign("pos_status", $pos_status);
        // 查询该机构下所有的分类
        $this->assign("list", $list);
        $this->assign('page', $show); // 赋值分页输出
        $this->assign("goods_name", $goods_name);
        $this->display("GoodsInfo/index");
    }

    public function getPosData($sendType, $nodeSql, $shopList = '') {
        $getData = false;
        switch ($sendType) {
            case 1: // 全门店
                $getData = M()->query($nodeSql);
                if (! $getData)
                    $this->error('获取门店信息出错');
                break;
            case 2: // 子门店
                $shopList = explode(',', $shopList);
                if (! is_array($shopList) || empty($shopList))
                    $this->error('请选择验证门店');
                    // $shopstr = implode(',',array_unique($shopList));
                $where = array(
                    's.store_id' => array(
                        'in', 
                        array_unique($shopList)), 
                    's.node_id' => array(
                        'exp', 
                        "in ({$nodeSql})"), 
                    's.pos_range' => array(
                        'gt', 
                        '0'));
                // 获取终端号
                $getData = M()->table('tstore_info s')
                    ->field('p.pos_id,p.store_id,p.node_id')
                    ->join(
                    'tpos_info p ON s.store_id=p.store_id AND s.node_id=p.node_id')
                    ->where($where)
                    ->select();
                if (! $getData)
                    $this->error('请选择开始终端的门店');
                break;
            default:
                break;
        }
        return $getData;
    }

    public function add() {
        //短信内容
        $startUp = M('tnode_info')->where(array('node_id'=>$this->node_id))->getField('custom_sms_flag');
        if ($this->isPost()) {
            $postArr = I('post.');
            if (empty($postArr['line_type'])) {
                $this->error("缺少必要参数！");
            }
            if (I('verify_time_type', 0, 'intval') == '0') {
                // 创建优惠结束时间不能小于或者超过优惠有效期时间
                if (I('verify_end_date') < I('verify_begin_date')) {
                    $this->error("验证结束的时间不能小于验证开始时间！");
                }
            } else {
                if (I('verify_end_days') < I('verify_begin_days')) {
                    $this->error("验证结束天数不能小于验证开始天数!");
                }
            }
            $error = '';
            $name = I('post.name', null);
            // sku价格列表
            $sku_price = json_decode(
                html_entity_decode(I('post.data_price_info', null)), true);
            // sku别名列表
            $sku_name = json_decode(
                html_entity_decode(I('post.data_name_info', null)), true);
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
            // 取上传图片第一张为商品的图片
            if (I('post.resp_img1') != '') {
                $goodsImg = str_replace('..', '', I('post.resp_img1'));
            } elseif (I('post.resp_img2') != '') {
                $goodsImg = str_replace('..', '', I('post.resp_img2'));
            } elseif (I('post.resp_img3') != '') {
                $goodsImg = str_replace('..', '', I('post.resp_img3'));
            } elseif (I('post.resp_img4') != '') {
                $goodsImg = str_replace('..', '', I('post.resp_img4'));
            } elseif (I('post.resp_img5') != '') {
                $goodsImg = str_replace('..', '', I('post.resp_img5'));
            }
            if (I('post.resp_img1') == '' && I('post.resp_img2') == "" &&
                 I('post.resp_img3') == "" && I('post.resp_img4') == "" &&
                 I('post.resp_img5') == "") {
                $this->error("至少要上传一张图片！");
            }
            if (! check_str($goodsImg, 
                array(
                    'null' => false), $error)) {
                $this->error("商品图片{$error}", 
                    array(
                        '返回' => "javascript:history.go(-1)"));
            }
            // 图片处理
            $smilId = null;
            if ($goodsImg) {
                // 卡券图片移动
                $this->moveImage($goodsImg);
                $smilId = $this->getSmil($goodsImg, $name);
                if (! $smilId)
                    $this->error('创建失败:smilid获取失败');
            }
            $marketPrice = I('post.market_price', null);
            // 创建sku信息
            $skuObj = D('IntegralSku', 'Service');
            $sku_infolist = $skuObj->getSkuPrice($sku_price);
            $isSku = 0;
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
            $mmsNote = I('post.mms_text');
            $mmsTitle = I('post.mms_title','商品');
            $printText = I('post.print_text');
            if ($printText == '')
                $printText = "凭小票可兑换1份" . $name;
                
                // 支撑创建终端组
            $nodeInfo = M('tnode_info')->field(
                'node_name,client_id,node_service_hotline,posgroup_seq')
                ->where("node_id='{$this->nodeId}'")
                ->find();
            M('tnode_info')->where(
                array(
                    'node_id' => $this->node_id))->setInc('posgroup_seq'); // posgroup_seq
                                                                           // +1;
                                                                           // 门店提领
            $groupTypeId = I('shop', true);
            $goodsM = D('Goods');
            if ('2' != $groupTypeId) {
                $groupTypeId = 1;
                $sendGroupId = 0;
                $getPosData = $this->getPosData($groupTypeId, 
                    $this->nodeIn(null, true));
                if (false === $getPosData)
                    $this->error("请选择开始终端的门店");
                $dataList = $this->getDataList($groupTypeId, $getPosData);
            } else {
                $groupIdInfo = I('openStores', true);
                if (empty($groupIdInfo)) {
                    $this->error('请选择可验证的门店');
                }
                $sendGroupId = 1;
                $getPosData = $this->getPosData($groupTypeId, $this->nodeIn(), 
                    $groupIdInfo);
                if (false === $getPosData)
                    $this->error("请选择开始终端的门店");
                $dataList = $this->getDataList($groupTypeId, $getPosData);
            }
            if (false === $dataList)
                $this->error('请选择门店信息');
                // 开启事务
            $goodsM->startTrans();
            $req_array = array(
                'CreatePosGroupReq' => array(
                    'NodeId' => $this->node_id, 
                    'GroupType' => $sendGroupId, 
                    'GroupName' => str_pad($nodeInfo['client_id'], 6, '0', 
                        STR_PAD_LEFT) . $nodeInfo['posgroup_seq'], 
                    'GroupDesc' => '', 
                    'DataList' => $dataList));
            $RemoteRequest = D('RemoteRequest', 'Service');
            $resp_array = $RemoteRequest->requestIssForImageco($req_array);
            $ret_msg = $resp_array['CreatePosGroupRes']['Status'];
            if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
                 $ret_msg['StatusCode'] != '0001')) {
                log::write("创建终端组失败，原因：{$ret_msg['StatusText']}");
                $goodsM->rollback();
                $this->error('创建门店失败:' . $ret_msg['StatusText']);
            }
            $groupId = $resp_array['CreatePosGroupRes']['GroupID'];
            // 创建合约生成合约ID，用于商品修改
            // $treatyId =
            // $goodsM->zcCreateTreaty($name,$name,$groupId,$marketPrice,$this->nodeId,$this->nodeId);
            // if(false === $treatyId){
            // $goodsM->rollback();
            // $this->error ($goodsM->getError());
            // }
            switch ($groupTypeId) {
                case 1: // 全门店
                    $retMsg = $goodsM->addPosRelation(
                        $req_array['CreatePosGroupReq']['GroupName'], 
                        $this->nodeId, $sendGroupId, $getPosData, $groupId);
                    break;
                case 2: // 子门店
                    $retMsg = $goodsM->addPosRelation(
                        $req_array['CreatePosGroupReq']['GroupName'], 
                        $this->nodeId, $sendGroupId, $getPosData, $groupId);
                    break;
            }
            // 结束事务
            $goodsM->commit();
            
            // 支撑创建活动
            $req_array = array(
                'ActivityCreateReq' => array(
                    'SystemID' => C('ISS_SYSTEM_ID'), 
                    'ISSPID' => $this->node_id, 
                    'RelationID' => $this->node_id, 
                    'TransactionID' => date("YmdHis") . mt_rand(100000, 999999),  // 请求单号
                    'SmilID' => $smilId, 
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
            $RemoteRequest = D('RemoteRequest', 'Service');
            $resp_array = $RemoteRequest->requestIssForImageco($req_array);
            $ret_msg = $resp_array['ActivityCreateRes']['Status'];
            if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
                 $ret_msg['StatusCode'] != '0001')) {
                M()->rollback();
                $this->error("创建失败:{$ret_msg['StatusText']}", 
                    array(
                        '返回' => "javascript:history.go(-1)"));
            }
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
                'goods_type' => $this->GOODS_TYPE,  // 商品销售类型
                'market_price' => $marketPrice, 
                // 'goods_amt'=> $marketPrice ,
                'storage_type' => $numType, 
                'storage_num' => $goodsNum, 
                'remain_num' => $goodsNum, 
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
                'is_sku' => $isSku);
            $goodsId = M('tgoods_info')->data($goods_data)->add();
            if (! $goodsId) {
                log_write("新增礼品卡1234".M()->getLastSql());
                M()->rollback();
                $this->error('系统出错,新建商品失败', 
                    array(
                        '返回' => "javascript:history.go(-1)"));
            }
            if ($sku_infolist != false) {
                // 添加规格并重新生成规格信息
                $skuObj->checkSkuType($sku_name, $this->nodeId, $goods_id);
                // 页面newid
                $newId_list = $skuObj->id_list;
                $result = $skuObj->checkSkuPro($sku_price, $newId_list, 
                    $goods_id, $this->nodeId);
                
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
            // 创建tmarketing_info
            $delivery_flag = I('delivery_flag');
            $goodsModel = new IntegralGoodsInfoModel();
            $resM = $goodsModel->_addMarketingInfo($name, $marketPrice, 
                $goodsDesc, $delivery_flag, $this->node_id, $this->BATCH_TYPE, 
                $postArr['line_type']);
            if ($resM['code'] === '0009') {
                M()->rollback();
                $this->error($result['err_msg']);
            }
            // 创建batch_info数据
            if ($postArr['line_type'] == 1) {
                $line_type = 0;
            } else {
                $line_type = 1;
            }
            //自定义短信内容
            if($startUp == 1  && $delivery_flag == 0){
                $sms_text = I('cusMsg','');
                if(empty($sms_text)){
                    M()->rollback();
                    $this->error('短信内容不能空！');
                }else{
                    $postArr['sms_text'] = $sms_text;
                }
            }
            log_write("KKK" . $groupTypeId);
            $resB = $goodsModel->_addBatchInfo($postArr, $resM['m_id'], 
                $goods_id, $batchNo, $this->node_id, $this->BATCH_TYPE, 
                $this->user_id, $line_type, $groupId, $groupTypeId);
            if ($resB === '0008') {
                M()->rollback();
                $this->error($result['err_msg']);
            }
            //
            $resE = $goodsModel->addGoodsEx($resB['b_id'], $resM['m_id'], 
                $postArr, $goodsId, $this->node_id);
            if ($resE === '0006') {
                M()->rollback();
                $this->error($result['err_msg']);
            }
            // 创建完活动，更新下tintegral_classify里面的goods_count
            $where = array(
                'node_id' => $this->node_id, 
                'id' => $postArr['classify']);
            $inRes = M("tintegral_classify")->where($where)->find();
            if ($inRes) {
                $res = M("tintegral_classify")->where($where)->setInc(
                    'goods_count');
                if ($res === false) {
                    M()->rollback();
                    $this->error('新建商品失败');
                    log_write('integral自增长失败！');
                }
            }
            // 发布默认渠道
            $channelId = M('tchannel')->where(
                array(
                    "node_id" => $this->node_id, 
                    "type" => $this->CHANNEL_TYPE, 
                    "sns_type" => $this->CHANNEL_SNS_TYPE, 
                    "status" => "1"))->getField('id');
            $data = array(
                'batch_type' => $this->BATCH_TYPE, 
                'batch_id' => $resM['m_id'], 
                'channel_id' => $channelId, 
                'add_time' => date('YmdHis'), 
                'node_id' => $this->node_id);
            $label_id = M('tbatch_channel')->add($data);
            if ($label_id === false) {
                M()->rollback();
                $this->error("发布默认渠道失败！");
            }
            $goodsDataEx = array(
                'label_id' => $label_id);
            // 显示
            if ($postArr['tape_price_type'] == 1) {
                $goodsDataEx['tape_price_type'] = $postArr['tape_price_type'];
                $goodsDataEx['tape_price'] = $postArr['tape_price'];
            }
            $flag = M('tintegral_goods_ex')->where(
                array(
                    'm_id' => $resM['m_id']))->save($goodsDataEx);
            if ($flag === false) {
                M()->rollback();
                $this->error("保存渠道ID失败！");
            }
            M()->commit();
            node_log("新建商品成功", "新建商品成功，活动号：" . $resM['m_id']);
            $this->success('新建商品成功！', 
                array(
                    '返回商品列表' => U('index')));
        }
        //获取当前机构的资费标准
        $sendPrice = D('ActivityPayConfig')->getSendFee($this->node_id);
        $this->assign('startUp', $startUp);
        $this->assign('sendPrice', $sendPrice);
        $this->display("GoodsInfo/add");
    }

    public function getDataList($groupType, $posData) {
        switch ($groupType) {
            case 1: // 全门店
                $nodeArr = array();
                foreach ($posData as $v) {
                    $nodeArr[] = $v['node_id'];
                }
                $dataList = implode(',', $nodeArr);
                break;
            case 2: // 子门店
                $posArr = array();
                foreach ($posData as $v) {
                    if (! is_null($v['pos_id'])) {
                        $posArr[] = $v['pos_id'];
                    }
                }
                if (! $posArr)
                    $this->error('未找到有效的验证终端');
                $dataList = implode(',', $posArr);
                break;
            default:
                $dataList = false;
        }
        return $dataList;
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
        $goods_sku_list = $skuObj->getReloadSku($goods_sku_info);
        if (is_array($goods_sku_list['list']))
            $goods_sku_detail_list = $skuObj->getSkuDetailList(
                $goods_sku_list['list']);
            
            // 取得规格表信息
        if (is_array($goods_sku_detail_list))
            $goods_sku_type_list = $skuObj->getSkuTypeList(
                $goods_sku_detail_list);
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
            $skuObj->makeSkuType($goods_sku_type_list, $goods_sku_detail_list));
        $this->assign("skudetail", $skuDetail);
        $this->assign('storeList', $storeList);
        $this->assign('isSku', $isSku);
        $this->assign("goodsinfo", $goodsInfo);
        $this->display("GoodsInfo/info");
    }

    public function edit() {
        $id = I('request.id', null);
        $goodsArr = I('post.');
        if (! $id)
            $this->error('商品编号不能为空');
        $goodsInfo = M()->table("tgoods_info a")->field(
            'a.*,b.ecshop_classify,b.show_picture1,b.show_picture2,b.show_picture3,b.show_picture4,b.show_picture5,b.wap_info,b.m_id,b.b_id,c.batch_no,b.delivery_flag,b.tape_price,b.tape_price_type,c.verify_begin_date,c.verify_end_date,c.verify_begin_type,c.verify_end_type,c.sms_text,c.use_rule')
            ->join('tintegral_goods_ex b on b.goods_id=a.id')
            ->join('tbatch_info c on c.id=b.b_id')
            ->where(array(
            'a.id' => $id))
            ->find();
        if (! $goodsInfo)
            $this->error('商品数据不存在');
            // 创建sku信息
        $skuObj = D('IntegralSku', 'Service');
        // 取得商品sku数据
        $goods_sku_info = $skuObj->getSkuInfoList($goodsInfo['goods_id'], 
            $this->nodeId);
        if ($goods_sku_info) {
            $this->assign('skuFlag', '1');
        } else {
            $this->assign('skuFlag', '0');
        }
        // 分离商品表中的规格和规格值ID
        $goods_sku_list = $skuObj->getReloadSku($goods_sku_info);
        // 取得规格值表信息
        $goods_sku_detail_list = '';
        if (is_array($goods_sku_list['list']))
            $goods_sku_detail_list = $skuObj->getSkuDetailList(
                $goods_sku_list['list']);
            // 取得规格表信息
        if (is_array($goods_sku_detail_list))
            $goods_sku_type_list = $skuObj->getSkuTypeList(
                $goods_sku_detail_list);
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
        //短信内容
        $startUp = M('tnode_info')->where(array('node_id'=>$this->node_id))->getField('custom_sms_flag');
        if ($this->isPost()) {
            $error = '';
            $goodsDesc = I('post.goods_desc', null);
            if (I('verify_time_type', 0, 'intval') == '0') {
                // 创建优惠结束时间不能小于或者超过优惠有效期时间
                if (I('verify_end_date') < I('verify_begin_date')) {
                    $this->error("验证结束的时间不能小于验证开始时间！");
                }
            } else {
                if (I('verify_end_days') < I('verify_begin_days')) {
                    $this->error("验证结束天数不能小于验证开始天数!");
                }
            }
            if (! check_str($goodsDesc, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '200'), $error)) {
                $this->error("商品描述{$error}");
            }
            // sku价格列表
            $sku_price = json_decode(
                html_entity_decode(I('post.data_price_info', null)), true);
            // sku别名列表
            $sku_name = json_decode(
                html_entity_decode(I('post.data_name_info', null)), true);
            // sku商品总数
            $countNum = I('post.count_num', null);
            if (0 === count($sku_name))
                $isSku = false;
            else
                $isSku = true;
                // 是否修改sku数据
            $is_change = false;
            // 检查规格是否变动
            $is_change = $skuObj->checkArray('id', 'id', $sku_name, 
                $goods_sku_type_list);
            // 检查规格值是否变动
            if (false == $is_change) {
                $tmp_sku_info = array();
                foreach ($sku_name as $v) {
                    foreach ($v['list'] as $lv)
                        array_push($tmp_sku_info, $lv);
                }
                $is_change = $skuObj->checkArray('id', 'id', $tmp_sku_info, 
                    $goods_sku_detail_list);
            }
            // 检查商品组合是否变动
            if (false == $is_change) {
                $is_change = $skuObj->checkArray('sku_detail_id', 'id', 
                    $goods_sku_info, $sku_price);
            }
            $sku_infolist = $skuObj->getSkuPrice($sku_price);
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
            // if (true == $is_change && session('is_submit') != true) {
            // session('is_submit', true);
            // $this->error('规格，规格值已修改，该商品将下架，您确定要如此操作吗？');
            // }
            // 商品下架
            // if (session('is_submit') == true) {
            // //所有商品下架
            // $skuObj->Offline($goodsInfo['goods_id'], $this->nodeId);
            // //添加规格并重新生成规格信息
            // $skuObj->checkSkuType($sku_name, $this->nodeId,
            // $goodsInfo['goods_id'], true);
            // }
            // 释放提交事件
            // session('is_submit', false);
            // $goods_sku_detail_list
            // 检查sku数据是否修改
            // 取上传图片第一张为商品的图片
            if (I('post.resp_img1') != '') {
                $goodsImg = I('post.resp_img1');
            } elseif (I('post.resp_img2') != '') {
                $goodsImg = I('post.resp_img2');
            } elseif (I('post.resp_img3') != '') {
                $goodsImg = I('post.resp_img3');
            } elseif (I('post.resp_img4') != '') {
                $goodsImg = I('post.resp_img4');
            } elseif (I('post.resp_img5') != '') {
                $goodsImg = I('post.resp_img5');
            }
            if (I('post.resp_img1') == '' && I('post.resp_img2') == "" &&
                 I('post.resp_img3') == "" && I('post.resp_img4') == "" &&
                 I('post.resp_img5') == "") {
                $this->error("至少要上传一张图片！");
            }
            if ($goodsImg) {
                $goodsImg = str_replace('..', '', $goodsImg);
                if (! check_str($goodsImg, 
                    array(
                        'null' => false), $error)) {
                    $this->error("商品图片{$error}");
                }
            }
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
            // 图片处理
            $smilId = null;
            if ($goodsImg != $goodsInfo['goods_image']) {
                // 卡券图片移动
                $this->moveImage($goodsImg);
                $smilId = $this->getSmil($goodsImg, $goodsInfo['goods_name']);
                if (! $smilId)
                    $this->error('创建失败:smilid获取失败');
            }
            // 全门店
            if ('2' != $groupTypeId) {
                $groupTypeId = 1;
                $sendGroupId = 0;
                $getPosData = $this->getPosData($groupTypeId, 
                    $this->nodeIn(null, true));
                if (false === $getPosData)
                    $this->error("请选择有终端的门店");
                $dataList = $this->getDataList($groupTypeId, $getPosData);
            } else { // 子门店
                $groupIdInfo = I('openStores', true);
                $sendGroupId = 1;
                $getPosData = $this->getPosData($groupTypeId, $this->nodeIn(), 
                    $groupIdInfo);
                if (false === $getPosData)
                    $this->error("请选择有终端的门店");
                $dataList = $this->getDataList($groupTypeId, $getPosData);
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
                    if ($goodsInfo['pos_group_type'] == '1' ||
                         count($newStoreArr) != count($oldStoreArr) ||
                         ! empty($arrayDiff)) { // 全门店变成子门店或门店增加减少
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
            $result = $goodsM->zcModifyBatch($this->node_id, 
                $goodsInfo['batch_no'], $goodsInfo['goods_name'], 
                $goodsInfo['goods_name'], $goodsInfo['add_time'], $printText, 
                $smilId, '');
            if (false === $result) {
                $goodsM->rollback();
                log_write("YYYYYY" . $goodsM->getError());
                $this->error($goodsM->getError());
            }
            $goodsM->commit();
            // END 结束门店终端信息处理
            
            M()->startTrans();
            // 创建tgoods_info数据
            $goods_data = array(
                // 'goods_name' => I('name') ,
                'goods_desc' => $goodsDesc, 
                'goods_image' => $goodsImg, 
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
                    'update_time' => date('YmdHis'));
            
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
                $skuObj->checkSkuType($sku_name, $this->nodeId, 
                    $goodsInfo['goods_id']);
                $newId_list = $skuObj->id_list;
                $result = $skuObj->checkSkuPro($sku_price, $newId_list, 
                    $goodsInfo['goods_id'], $this->nodeId);
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
            // 修改tmarketInfo里面的name
            $mdata = array(
                // 'name' => I('name'),
                'market_price' => I('market_price'), 
                'memo' => I('goods_desc'), 
                'defined_one_name' => I('delivery_flag')); // 1 到店领取 2 物流配送
            
            $result = M('tmarketing_info')->where(
                array(
                    'id' => $goodsInfo['m_id']))->save($mdata);
            if ($result === false) {
                M()->rollback();
                $this->error("编辑商品失败001！");
            }
            // tbatch_info
            // $remain_num = $reqData['goods_num'] - ($info['storage_num'] -
            // $info['remain_num']);
            $bdata = array(
                // 'batch_short_name' => $goodsArr['name'],
                // 'batch_name' => $goodsArr['name'],
                'info_title' => get_scalar_val($goodsArr['mms_title'],'商品'),
                'use_rule' => $goodsArr['mms_text'], 
//                'sms_text' => $goodsArr['mms_text'],
                'batch_img' => $goodsImg, 
                'batch_amt' => $goodsArr['market_price'], 
                'update_time' => date('YmdHis'), 
                // 'storage_num' => $goodsArr['goods_num'],
                // 'remain_num' => $remain_num,
                'batch_desc' => $goodsArr['goods_desc']);
            if ($goodsArr['verify_time_type'] == '0') {
                $bdata['verify_begin_date'] = $goodsArr['verify_begin_date'] .
                     '000000';
                $bdata['verify_end_date'] = $goodsArr['verify_end_date'] .
                     '235959';
                $bdata['verify_begin_type'] = $goodsArr['verify_time_type'];
                $bdata['verify_end_type'] = $goodsArr['verify_time_type'];
            } elseif ($goodsArr['verify_time_type'] == '1') {
                $bdata['verify_begin_date'] = $goodsArr['verify_begin_days'];
                $bdata['verify_end_date'] = $goodsArr['verify_end_days'];
                $bdata['verify_begin_type'] = $goodsArr['verify_time_type'];
                $bdata['verify_end_type'] = $goodsArr['verify_time_type'];
            }

            if($startUp == 1){
                $sms_text = I('cusMsg','');
                if(empty($sms_text)){
                    M()->rollback();
                    $this->error('短信内容不能空！');
                }else{
                    $bdata['sms_text'] = $sms_text;
                }
            }
            $result = M('tbatch_info')->where(
                array(
                    'id' => $goodsInfo['b_id']))->save($bdata);
            if ($result === false) {
                M()->rollback();
                $this->error("编辑商品失败002");
            }
            // 更新积分商城tintegral_goods_ex
            $good_bdata = array(
                'show_picture1' => $goodsArr['resp_img1'], 
                'show_picture2' => $goodsArr['resp_img2'], 
                'show_picture3' => $goodsArr['resp_img3'], 
                'show_picture4' => $goodsArr['resp_img4'], 
                'show_picture5' => $goodsArr['resp_img5'], 
                // 'ecshop_classify'=>$goodsArr['classify'],
                'goods_desc' => $goodsArr['goods_desc'], 
                'wap_info' => $goodsArr['wap_info'], 
                // 'delivery_flag'=>I('delivery_flag'),
                'market_price' => $goodsArr['price'],
                'tape_price_type' => $goodsArr['tape_price_type'],
                'tape_price' => $goodsArr['tape_price']);
            $result = M('tintegral_goods_ex')->where(
                array(
                    'b_id' => $goodsInfo['b_id']))->save($good_bdata);
            if ($result === false) {
                M()->rollback();
                $this->error('更新商品失败！');
            }
            M()->commit();
            node_log("更新商品成功", "更新商品成功，活动号：" . $goodsInfo['m_id']);
            $this->success('更新商品成功！', 
                array(
                    '返回商品列表' => U('index')));
        }
        // 价格列表
        $skuDetail = $skuObj->makeSkuList($goods_sku_info);
        $this->assign("goodsinfo", $goodsInfo);
        $this->assign("startUp", $startUp);
        $this->assign("skutype",
            $skuObj->makeSkuType($goods_sku_type_list, $goods_sku_detail_list));
        $this->assign('storeArr', $oldStoreArr);
        $this->assign("skudetail", $skuDetail);
        $this->display("GoodsInfo/edit");
    }
    // 上架
    public function putLine() {
        $m_id = I('id', 0, 'intval');
        if ($m_id == 0)
            $this->error('参数错误！');
        
        $map = array(
            'node_id' => $this->nodeId, 
            'id' => $m_id, 
            'batch_type' => $this->BATCH_TYPE);
        $marketInfo = M('tmarketing_info')->where($map)->find();
        if (! $marketInfo)
            $this->error('参数错误！');
        
        $batchInfo = M('tbatch_info')->where("m_id = '{$m_id}'")->find();
        if ($batchInfo['status'] == '0')
            $this->error('该商品已经上架，不能上架');
        if ($batchInfo['is_delete'] != '0')
            $this->error('该订单已经删除，不能上架');
        
        M()->startTrans();
        $data = array(
            'status' => '0');
        $flag = M('tbatch_info')->where("m_id = '{$m_id}'")->save($data);
        if ($flag === false) {
            M()->rollback();
            $this->error('上架失败！');
        }
        // 修改tmarketing_info里面的status
        $m_data = array(
            'status' => '1');
        $m_flag = M('tmarketing_info')->where("id = '{$m_id}'")->save($m_data);
        if ($m_flag === false) {
            M()->rollback();
            $this->error('上架失败！');
        }
        $data = array(
            'is_commend' => '9');
        $flag = M('tintegral_goods_ex')->where("m_id = '{$m_id}'")->save($data);
        if ($flag === false) {
            M()->rollback();
            $this->error('下架失败！');
        }
        M()->commit();
        node_log("上架成功", "上架成功，活动号：" . $m_id);
        $this->success('上架成功！');
    }
    // 下架
    public function offLine() {
        $m_id = I('id', 0, 'intval');
        if ($m_id == 0)
            $this->error('参数错误！');
        
        $map = array(
            'node_id' => $this->nodeId, 
            'id' => $m_id, 
            'batch_type' => $this->BATCH_TYPE);
        $marketInfo = M('tmarketing_info')->where($map)->find();
        if (! $marketInfo)
            $this->error('参数错误！');
        
        $batchInfo = M('tbatch_info')->where("m_id = '{$m_id}'")->find();
        if ($batchInfo['status'] != '0')
            $this->error('该商品已经下架，不能在下架');
        if ($batchInfo['is_delete'] != '0')
            $this->error('该订单已经删除，不能下架');
        
        M()->startTrans();
        $data = array(
            'status' => '1');
        $flag = M('tbatch_info')->where("m_id = '{$m_id}'")->save($data);
        if ($flag === false) {
            M()->rollback();
            $this->error('下架失败！');
        }
        // 修改tmarketing_info里面的status
        $m_data = array(
            'status' => '2');
        $m_flag = M('tmarketing_info')->where("id = '{$m_id}'")->save($m_data);
        if ($m_flag === false) {
            M()->rollback();
            $this->error('下架失败！');
        }
        $data = array(
            'is_commend' => '9');
        $flag = M('tintegral_goods_ex')->where("m_id = '{$m_id}'")->save($data);
        if ($flag === false) {
            M()->rollback();
            $this->error('下架失败！');
        }
        M()->commit();
        node_log("下架成功", "下架成功，活动号：" . $m_id);
        $this->success('下架成功！');
    }
    // 删除
    public function delLine() {
        $m_id = I('id', 0, 'intval');
        if ($m_id == 0)
            $this->error('参数错误！');
        
        $map = array(
            'node_id' => $this->nodeId, 
            'id' => $m_id, 
            'batch_type' => $this->BATCH_TYPE);
        $marketInfo = M('tmarketing_info')->where($map)->find();
        if (! $marketInfo)
            $this->error('参数错误！');
        $batchInfo = M('tbatch_info')->where("m_id = '{$m_id}'")->find();
        if ($batchInfo['is_delete'] != '0')
            $this->error('该订单已经删除，不能上架');
        
        M()->startTrans();
        $data = array(
            'status' => '1', 
            'is_delete' => '1');
        $flag = M('tbatch_info')->where("m_id = '{$m_id}'")->save($data);
        if ($flag === false) {
            M()->rollback();
            $this->error('上架失败！');
        }
        // 修改tmarketing_info里面的status
        $m_data = array(
            'status' => '2');
        $m_flag = M('tmarketing_info')->where("id = '{$m_id}'")->save($m_data);
        if ($m_flag === false) {
            M()->rollback();
            $this->error('上架失败！');
        }
        $data = array(
            'is_commend' => '9', 
            'is_delete' => '1');
        $flag = M('tintegral_goods_ex')->where("m_id = '{$m_id}'")->save($data);
        if ($flag === false) {
            M()->rollback();
            $this->error('删除成功！');
        }
        M()->commit();
        node_log("删除商品成功", "删除商品成功，活动号：" . $m_id);
        $this->success('删除成功！');
    }
    // 卡券图片移动
    public function moveImage($imageName) {
        // 使用新的上传控件
        return true;
        $oldImagePath = $this->tempImagePath;
        $newImagePath = $this->numGoodsImagePath;
        // 图片是否存在
        if (! is_file($oldImagePath . '/' . $imageName)) { // 是否存在此文件
            $this->error('卡券图片未找到');
        }
        if (! is_dir($newImagePath)) { // 是否存在此目录
            if (! mkdir($newImagePath, 0777))
                $this->error('目录创建失败');
        }
        $flag = copy($oldImagePath . '/' . $imageName, 
            $newImagePath . '/' . $imageName);
        if (! $flag)
            $this->error('图片移动失败');
        return true;
    }
    // 获取smil $type 1:爱拍 2:旺财
    public function getSmil($imageName, $name, $smilId = "") {
        $imagePath = realpath(C('UPLOAD'));
        $zipFileName = date('YmdHis') . '.zip';
        import('@.ORG.Net.Zip', '', '.php') or die('导入包失败');
        $test = new zip_file($zipFileName);
        $test->set_options(
            array(
                'basedir' => $imagePath . '/template/', 
                'inmemory' => 0, 
                'recurse' => 1, 
                'storepaths' => 0, 
                'overwrite' => 1, 
                'level' => 5, 
                'name' => $zipFileName));
        $imageUrl = $imagePath . '/' . $imageName;
        if (! is_file($imageUrl)) {
            log_write('获取SmilId接口图片文件不存在' . $imageUrl);
            return false;
        }
        // 缩放图片大小要小于60k
        import('ORG.Util.Image');
        $smilUrl = Image::thumb($imageUrl, 
            dirname($imageUrl) . '/smi_' . basename($imageUrl), '', 150, 150, 
            true);
        if (! $smilUrl) {
            log_write('SmilId接口图片压缩失败');
            return false;
        }
        $imageUrl = $smilUrl;
        $smil_cfg = create_smil_cfg($imageUrl);
        if ($smil_cfg === false) {
            log_write('创建smil_cfg失败');
            return false;
        }
        // $defultSmil = $path.'/template/default.smil';
        // $imagecoImage = $path.'/template/imageco_image_b.jpg';
        $info = pathinfo($imageUrl);
        $ex = $info['extension'];
        $files = array(
            '1.' . $ex => $imageUrl, 
            'default.smil' => realpath($smil_cfg));
        $test->add_files($files);
        $test->create_archive();
        $zipPath = $imagePath . '/template/' . $zipFileName;
        $SmilZip = base64_encode(file_get_contents($zipPath));
        // 通知支撑
        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
                                                                   // 请求参数
        $req_array = array(
            'SmilAddEditReq' => array(
                'ISSPID' => $this->nodeId, 
                'PlatformID' => C('ISS_PLATFORM_ID'), 
                'TransactionID' => $TransactionID, 
                'Username' => C('ISS_SEND_USER'), 
                'Password' => C('ISS_SEND_USER_PASS'), 
                'SmilInfo' => array(
                    'SmilId' => $smilId, 
                    'SmilName' => time(), 
                    'SmilDesc' => $name, 
                    'SmilZip' => $SmilZip)));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $resp_array = $RemoteRequest->smilAddEditReq($req_array);
        @unlink($zipPath);
        @unlink($smil_cfg);
        @unlink($smilUrl);
        $ret_msg = $resp_array['SmilAddEditRes']['Status'];
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
             $ret_msg['StatusCode'] != '0001')) {
            log_write("获取SmilId失败 原因：{$ret_msg['StatusText']}");
            return false;
        }
        return $resp_array['SmilAddEditRes']['SmilId'];
    }

    /**
     * 默认的门店弹窗
     */
    public function storePopup() {
        $nodeIn = $this->nodeIn();
        $storesModel = $this->getStoresModel();
        
        // 省市区请求
        if (IS_POST) {
            $areaType = I('post.city_type');
            $query_arr = (array) $storesModel->areaFilter($nodeIn, $areaType);
            $this->ajaxReturn($query_arr, "查询成功", 0);
            exit();
        }
        
        $getAllStores = $storesModel->getAllStore($nodeIn);
        
        $this->assign('allStores', $getAllStores); // 输出门店
        $this->display();
    }
}