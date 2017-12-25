<?php

class WeixinCardAction extends BaseAction
{

    public $appId;

    public $appSecret;

    public $accessToken;

    public $cardService;

    public $batchType = '40';

    public $channelType = "4";

    public $channelSnsType = "49";
    // 由于测试平台卡券无法通过验证，此处用于标记有效卡券的状态值，在初始化中会根据当前代码平台设置
    private $auth_valid = '2';

    public function _initialize()
    {
        parent::_initialize();
        $map = array(
        	'node_id' => $this->nodeId,
        	'account_type' => array('in', ['2','4']),
        	'status' => '0'
        );
        $winxinInfo = M('tweixin_info')->where($map)->find();       
        //创建朋友的券测试数据
        /*
        if(ACTION_NAME == 'addWxCardFriend' || ACTION_NAME == 'downFriendCode'){
            $winxinInfo = array(
                'app_id' => 'wx5acb63e448b4fc22',
                'app_secret' => '18b40cd823f7e3ba4f4b69d74752016a',
                'app_access_token' => 'h-gwzv7rn-3v6rHk1t8MJnH5jCDycQbbbeRiHZ_YCHMBrXOUhLBz1_e2aSVUiZSoxuOjhRSGiRkVCSir1NXptivQ7VGTnX1AniNPBJ7q8kCvalFuYvUVnsnyVX1r6mpUQIGbAHDCUS'
            );
        }
        */
        $this->appId = $winxinInfo['app_id'];
        $this->appSecret = $winxinInfo['app_secret'];
        $this->accessToken = $winxinInfo['app_access_token'];
        $service = D('WeiXinCard', 'Service');
        $service->init($this->appId, $this->appSecret, $this->accessToken);
        $this->cardService = $service;
        $this->auth_valid = C('PRODUCTION_FLAG') == 1 ? '2' : '1';
        $this->assign('auth_valid', $this->auth_valid);
    }

    public function index()
    {
        $map = array(
            'w.node_id' => $this->nodeId,
            'card_class' => '1'
        );
        $cardName = I('card_name', null, 'mysql_real_escape_string');
        if (! empty($cardName)) {
            $map['w.title'] = array(
                'like',
                "%{$cardName}%"
            );
        }
        $storeMode = I('store_mode', null, 'mysql_real_escape_string');
        if (! empty($storeMode)) {
            $map['w.store_mode'] = $storeMode;
        }
        
        $auth_flag = I('auth_flag', 0, 'intval');
        if ($auth_flag > 0) {
            $map['w.auth_flag'] = $auth_flag;
        }
        
        import("ORG.Util.Page");
        $count = M()->table("twx_card_type w")->where($map)->count();
        $p = new Page($count, 10);
        // 确保分页查询条件
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        $list = M()->table('twx_card_type w')
            ->field('w.*,g.storage_type,g.remain_num')
            ->join('tgoods_info g ON w.goods_id=g.goods_id')
            ->where($map)
            ->order('w.id DESC')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        // 分页显示
        $page = $p->show();
        $cardType = array(
            '0' => '折扣券',
            '1' => '代金券',
            '2' => '提领券',
            '3' => '优惠券'
        );
        $authFlag = array(
            '1' => '审核中',
            '2' => '审核通过',
            '3' => '未通过'
        );
        $storeMode = array(
            '1' => '多乐互动奖品',
            '2' => '微信公众号发放'
        );
        $this->assign('count', $count);
        $this->assign('list', $list);
        $this->assign('cardType', $cardType);
        $this->assign('authFlag', $authFlag);
        $this->assign('storeMode', $storeMode);
        $this->assign("page", $page);
        $this->display();
    }

    /**
     *
     * @var WeelCjSetModel
     */
    private $WeelCjSetModel;

    /**
     * 验证微信卡券添加状态
     * 添加微信卡券必须满足一下条件
     * 1,开通微信公众号
     * 2,开通微信卡包
     * 
     * @author zhaobaolin
     */
    public function verifyWeixinCardAddStatus()
    {
        $node_id = $this->node_id;
        if (empty($this->WeelCjSetModel)) {
            $this->WeelCjSetModel = D('WeelCjSet');
        }
        // 判断是否开通微信卡包
        $result = $this->WeelCjSetModel->isBindWxCard($node_id);
        $resultData = array(
            'data' => '',
            'status' => 0
        );
        $resultData['data'] = 'needBindWechatService';
        $resultData['status'] = 2;
        // $this->ajaxReturn($resultData, 'json');
        if ($result == 0) { // 未开通卡包
            $node_id = $this->node_id;
            // 判断是否绑定微信公众号
            $result = $this->WeelCjSetModel->isBindWxServ($node_id);
            if ($result == 0) { // 未开通微信
                $resultData['data'] = 'needBindWechatService';
                $resultData['status'] = 2;
            } else {
                $resultData['data'] = 'needBindWechatCard';
                $resultData['status'] = 3;
            }
        } else { // 卡包已经开通
            $resultData['data'] = 'success';
            $resultData['status'] = 1;
        }
        $this->ajaxReturn($resultData, 'json');
    }

    /**
     * 微信卡券添加(已弃用)
     */
    public function add()
    {
        if ($this->isPost()) {
            $goodsId = I('goods_id', null, 'mysql_real_escape_string');
            $map = array(
                'node_id' => $this->node_id,
                'status' => '0',
                'source' => '0',
                'goods_id' => $goodsId,
                'goods_type' => array(
                    'in',
                    array(
                        '0',
                        '1',
                        '2',
                        '3'
                    )
                )
            );
            $goodsInfo = M('tgoods_info')->where($map)->find();
            if (! $goodsInfo)
                $this->error('未找到有效的卡券信息');
            $error = '';
            // 商户名称
            $nodeName = I('node_name', null, 'mysql_real_escape_string');
            if (! check_str($nodeName, array(
                'null' => false,
                'maxlen_cn' => '12'
            ), $error)) {
                $this->error("商户名称{$error}");
            }
            // 库存模式 1-投放 2-预存
            $store_mode = I('useType', null, 'mysql_real_escape_string');
            if (! check_str($store_mode, array(
                'null' => false
            ), $error)) {
                $this->error("请选择库存模式");
            }
            // 卡卷标题
            $title = I('title', null, 'mysql_real_escape_string');
            if (! check_str($title, array(
                'null' => false,
                'maxlen_cn' => '9'
            ), $error)) {
                $this->error("卡券{$error}");
            }
            // 副标题
            $subTitle = I('sub_title', null, 'mysql_real_escape_string');
            if (! check_str($subTitle, array(
                'null' => ture,
                'maxlen_cn' => '18'
            ), $error)) {
                $this->error("卡券副标题{$error}");
            }
            $goodsType = ''; // 微信卡券的类型
            $isSendWithDrowDetail = '0';
            switch ($goodsInfo['goods_type']) {
                case '3': //
                    $discount = I('discount', null, 'mysql_real_escape_string');
                    if (! check_str($discount, array(
                        'null' => true,
                        'strtype' => 'number',
                        'minval' => '0'
                    ), $error)) {
                        $this->error("折扣额度{$error}");
                    }
                    $goodsType = '0';
                    break;
                case '1': // 代金券
                    $leastCost = I('least_cost', null, 'mysql_real_escape_string');
                    if (! check_str($leastCost, array(
                        'null' => true,
                        'strtype' => 'number',
                        'minval' => '0'
                    ), $error)) {
                        $this->error("抵扣条件{$error}");
                    }
                    // 减免金额不能为0
                    if (! check_str($goodsInfo['goods_amt'], array(
                        'null' => true,
                        'strtype' => 'number',
                        'minval' => '1'
                    ), $error)) {
                        $this->error("减免金额要大于0");
                    }
                    $goodsType = '1';
                    break;
                case '2': // 提领券
                    $gift = I('gift');
                    if (! check_str($gift, array(
                        'null' => false,
                        'maxlen_cn' => '100'
                    ), $error)) {
                        $this->error("礼品内容{$error}");
                    }
                    $goodsType = '2';
                    $sendDetail = I('post.send_withdrow_detail');
                    if ($sendDetail == 1) {
                        $isSendWithDrowDetail = '1';
                    }
                    break;
                case '0': // 优惠券
                    $defaultDetail = I('default_detail');
                    if (! check_str($defaultDetail, array(
                        'null' => false,
                        'maxlen_cn' => '500'
                    ), $error)) {
                        $this->error("优惠详情{$error}");
                    }
                    $goodsType = '3';
                    break;
                default:
                    $this->error('错误的卡券类型');
            }
            // 卡券颜色
            $cardColor = I('card_color', null, 'mysql_real_escape_string');
            if (! check_str($cardColor, array(
                'null' => false
            ), $error)) {
                $this->error("请选择卡券颜色");
            }
            // 领券限制
            $getLimit = I('get_limit', null, 'mysql_real_escape_string');
            if (! check_str($getLimit, array(
                'null' => false,
                'strtype' => 'int',
                'minval' => '1'
            ), $error)) {
                $this->error("领券限制{$error}");
            }
            // 用户分享
            $canGiveFriend = I('can_give_friend', null, 'mysql_real_escape_string');
            if (! check_str($canGiveFriend, array(
                'null' => false,
                'strtype' => 'int',
                'minval' => '1',
                'maxval' => '2'
            ), $error)) {
                $this->error("用户分享信息有误");
            }
            // 销券设置
            $codeType = I('code_type', null, 'mysql_real_escape_string');
            if (! check_str($codeType, array(
                'null' => false,
                'strtype' => 'int',
                'minval' => '1',
                'maxval' => '3'
            ), $error)) {
                $this->error("销券设置信息有误");
            }
            // 操作提示
            $notice = I('notice', null, 'mysql_real_escape_string');
            if (! check_str($notice, array(
                'null' => false,
                'maxlen_cn' => '16'
            ), $error)) {
                $this->error("操作提示{$error}");
            }
            // 使用须知
            $description = I('description');
            if (! check_str($description, array(
                'null' => false,
                'maxlen_cn' => '500'
            ), $error)) {
                $this->error("使用须知{$error}");
            }
            // 客服电话
            $servicePhone = I('service_phone', null, 'mysql_real_escape_string');
            // 图片处理
            $nodeImg = I('node_img', null);
            
            // 卡券日期处理
            $beginDate = I('post.start_time');
            if (! check_str($beginDate, array(
                'null' => false,
                'strtype' => 'datetime',
                'format' => 'Ymd'
            ), $error)) {
                $this->error("使用开始时间日期{$error}");
            }
            $endDate = I('post.end_time');
            if (! check_str($endDate, array(
                'null' => false,
                'strtype' => 'datetime',
                'format' => 'Ymd'
            ), $error)) {
                $this->error("使用结束时间日期{$error}");
            }
            if ($endDate < $beginDate) {
                $this->error('有效期开始日期不能大于结束时间');
            }
            $dateBeginTimestamp = strtotime($beginDate . '000000');
            $dateEndTimestamp = strtotime($endDate . '235959');
            
            $quantity = $goodsInfo['storage_num'] == '-1' ? '100000' : $goodsInfo['remain_num'];
            // 数据插入
            $data = array(
                'node_id' => $this->nodeId,
                'goods_id' => $goodsInfo['goods_id'],
                'card_type' => $goodsType,
                'logo_url' => get_upload_url($nodeImg),
                'code_type' => $codeType,
                'brand_name' => $nodeName,
                'title' => $title,
                'sub_title' => $subTitle,
                'color' => $cardColor,
                'notice' => $notice,
                'service_phone' => $servicePhone,
                'description' => $description,
                'get_limit' => $getLimit,
                'can_give_friend' => $canGiveFriend,
                'date_type' => '1',
                'date_begin_timestamp' => $dateBeginTimestamp,
                'date_end_timestamp' => $dateEndTimestamp,
                'quantity' => $quantity,
                'gift' => $gift,
                'default_detail' => $defaultDetail,
                'least_cost' => $leastCost,
                'reduce_cost' => $goodsInfo['goods_amt'],
                'discount' => $discount,
                'store_type' => '酒店',
                'add_time' => date("YmdHis"),
                'store_mode' => $store_mode,
                'send_withdrow_detail' => $isSendWithDrowDetail
            );
            
            if ($store_mode == '2') {
                $data['store_modify_num'] = $quantity;
            }
            M()->startTrans();
            // 数据插入
            $cardId = M('twx_card_type')->add($data);
            if (! $cardId) {
                M()->rollback();
                $this->error('系统出错,添加失败');
            }
            // 微信插入
            $weixinResult = $this->cardService->create($cardId);
            if ($weixinResult) {
                if ($store_mode == '2') {
                    $cardInfo = M()->table("twx_card_type w")->join("tgoods_info g ON w.goods_id=g.goods_id")
                        ->where(array(
                        'w.id' => $cardId
                    ))
                        ->find();
                    // 活动插入
                    $data = array(
                        'batch_type' => $this->batchType,
                        'card_id' => $cardInfo['card_id'],
                        'node_id' => $this->nodeId,
                        'add_time' => date('YmdHis')
                    );
                    
                    $batchId = M('tmarketing_info')->add($data);
                    if (! $batchId) {
                        M()->rollback();
                        $this->error('系统出错,活动添加失败');
                    }
                    // tbatch_info数据插入
                    $data = array(
                        'batch_no' => $cardInfo['batch_no'],
                        'batch_short_name' => $cardInfo['goods_name'],
                        'batch_name' => $cardInfo['goods_name'],
                        'node_id' => $this->nodeId,
                        'user_id' => $this->userId,
                        'batch_class' => $cardInfo['goods_type'],
                        'batch_type' => $cardInfo['source'],
                        'use_rule' => $cardInfo['mms_text'],
                        'batch_img' => $cardInfo['goods_image'],
                        'info_title' => $cardInfo['mms_title'],
                        'batch_amt' => $cardInfo['goods_amt'],
                        'begin_time' => $cardInfo['begin_time'],
                        'end_time' => $cardInfo['end_time'],
                        'add_time' => date('YmdHis'),
                        'node_pos_group' => $cardInfo['pos_group'],
                        'node_pos_type' => $cardInfo['pos_group_type'],
                        'batch_desc' => $cardInfo['goods_desc'],
                        'node_service_hotline' => $cardInfo['node_service_hotline'],
                        'goods_id' => $cardInfo['goods_id'],
                        'storage_num' => $quantity,
                        'remain_num' => 0,
                        'material_code' => $cardInfo['customer_no'],
                        'print_text' => $cardInfo['print_text'],
                        'm_id' => $batchId,
                        'validate_type' => $cardInfo['validate_type'],
                        'card_id' => $cardInfo['card_id']
                    );
                    $result = M('tbatch_info')->data($data)->add();
                    if (! $result) {
                        M()->rollback();
                        $this->error('系统出错,活动添加失败-0001');
                    }
                    // 扣减库存
                    $goodsM = D('Goods');
                    $flag = $goodsM->storagenum_reduc($cardInfo['goods_id'], $quantity, '', '13', '');
                    if ($flag === false) {
                        M()->rollback();
                        $this->error("添加失败,{$goodsM->getError()}");
                    }
                }
                M()->commit();
                $this->success('创建成功', null, array(
                    'goUrl' => U('add_success', array(
                        'c_id' => $cardId
                    ))
                ));
            } else {
                $result = M('twx_card_type')->where("id='{$cardId}'")->delete();
                if ($result === false) {
                    log::write('微信卡券删除失败id:' . $cardId);
                }
                $this->error('创建失败:' . $this->cardService->error);
            }
            exit();
        }
        $rGoodsId = I('r_goods_id', null, 'mysql_real_escape_string');
        if (! empty($rGoodsId)) {
            $map = array(
                'node_id' => $this->node_id,
                'status' => '0',
                'source' => '0',
                'goods_id' => $rGoodsId,
                'goods_type' => array(
                    'in',
                    '0,1,2,3'
                )
            );
            $goodsInfo = M('tgoods_info')->where($map)->find();
            if (! $goodsInfo) {
                $this->error('无效的卡券信息');
            }
            $this->assign('goodsInfo', $goodsInfo);
        }
        // 商家信息
        $node_info = M('tnode_info')->field('node_name,head_photo')
            ->where("node_id='{$this->nodeId}'")
            ->find();
        $color = $this->cardService->getcolors();
        $this->assign('nodeInfo', $node_info);
        $this->assign('color', $color['colors']);
        $this->display();
    }
    
    // 卡券详情
    public function cardDetail()
    {
        $id = I('id', null, 'mysql_real_escape_string');
        $map = array(
            'w.id' => $id,
            'w.node_id' => $this->nodeId
        );
        $cardInfo = M()->table("twx_card_type w")->field('w.*,g.verify_begin_date,g.verify_end_date,g.verify_begin_type,g.verify_end_type,g.storage_type,g.remain_num')
            ->join("tgoods_info g ON w.goods_id=g.goods_id")
            ->where($map)
            ->find();
        if (! $cardInfo)
            $this->error('未找到该卡券信息');
            // 门店获取
        $storeMap = array(
            'store_id' => array(
                'in',
                $cardInfo['store_id_list']
            )
        );
        $storeList = M('tstore_info')->field('store_short_name,address')
            ->where($storeMap)
            ->select();
        // 卡券颜色
        $color = $this->cardService->getcolors();
        $colorVal = array();
        foreach ($color['colors'] as $v) {
            $colorVal[$v['name']] = $v['value'];
        }
        $storeMode = array(
            '1' => '投放',
            '2' => '预存'
        );
        $this->assign('storeMode', $storeMode);
        $this->assign('color', $color);
        $this->assign('colorVal', $colorVal);
        $this->assign('cardInfo', $cardInfo);
        $this->assign('storeList', $storeList);
        $this->display();
    }

    /**
     * 微信卡券补充库存
     */
    public function addStorageNum()
    {
        $id = I('id', 0, 'intval');
        $addNum = I('addNum', 0, 'intval');
        $wxCardInfo = M('twx_card_type')->where(array(
            'id' => $id
        ))->find();
        if (! $wxCardInfo)
            $this->error('无效的数据');
        $wxCardModel = D('WeixinCard');
        M()->startTrans();
        $result = $wxCardModel->addStorageNum($id, $addNum);
        if (! $result) {
            M()->rollback();
            $this->error($wxCardModel->getError());
        }
        // 同步增加翼码卡券库存
        $goodsModel = D('Goods');
        $flag = $goodsModel->storagenum_reduc($wxCardInfo['goods_id'], - 1 * $addNum, '', 3, '微信卡券库存增加同步增加', - 1 * $addNum);
        if (! $flag) {
            M()->rollback();
            $this->error('翼码卡券库存增加失败');
        }
        M()->commit();
        $this->success('库存增加成功');
    }
    
    // 微信卡券投放
    public function cardSendIndex()
    {
        $map = array(
            'w.node_id' => $this->nodeId,
            'w.batch_type' => $this->batchType,
            'c.store_mode' => 1
        );
        $batchName = I('batch_name', null, 'mysql_real_escape_string');
        if (! empty($batchName)) {
            $map['w.name'] = array(
                'like',
                "%{$batchName}%"
            );
        }
        // 处理特殊查询字段
        $starTime = I('start_time', null, 'mysql_real_escape_string');
        if (! empty($starTime)) {
            $map['w.add_time'] = array(
                'egt',
                $starTime . '000000'
            );
        }
        $endTime = I('end_time', null, 'mysql_real_escape_string');
        if (! empty($endTime)) {
            $map['w.add_time '] = array(
                'elt',
                $endTime . '235959'
            );
        }
        $status = I('status', null, 'mysql_real_escape_string');
        if (isset($status) && $status != '') {
            $map['w.status'] = $status;
        }
        import("ORG.Util.Page");
        // 卡券投放默认渠道
        $channelId = M('tchannel')->where(array(
                'node_id' => $this->node_id,
                'type' => $this->channelType,
                'sns_type' => $this->channelSnsType
        ))->getField('id');

        $count = M()->table("tmarketing_info w")
                ->join(' tbatch_info b ON w.id=b.m_id ')
                ->join('twx_card_type c ON b.card_id=c.card_id')
                ->join('tbatch_channel n ON n.batch_id=w.id and n.channel_id=' . $channelId)
                ->where($map)->count();
        $p = new Page($count, 10);
        // 确保分页查询条件
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        $list = M()->table("tmarketing_info w")->field('w.*,n.id as labe_id,c.title,c.id as c_id,b.storage_num,b.remain_num')
            ->join('tbatch_info b ON w.id=b.m_id')
            ->join('twx_card_type c ON b.card_id=c.card_id')
            ->join('tbatch_channel n ON n.batch_id=w.id and n.channel_id=' . $channelId)
            ->where($map)
            ->order('w.add_time DESC')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        // 分页显示
        $page = $p->show();
        $status = array(
            '1' => '正常',
            '2' => '停用'
        );
        $this->assign('list', $list);
        $this->assign('status', $status);
        $this->assign("page", $page);
        $this->display();
    }
    
    // 新增卡券投放
    public function cardSendAdd()
    {
        if ($this->isPost()) {
            $error = '';
            $id = I('c_id', null, 'mysql_real_escape_string');
            $map = array(
                'w.id' => $id,
                'w.node_id' => $this->nodeId,
                'w.auth_flag' => array(
                    'in',
                    '1,2'
                ),
                'g.status' => '0'
            );
            
            $cardInfo = M()->table("twx_card_type w")->join("tgoods_info g ON w.goods_id=g.goods_id")
                ->where($map)
                ->find();
            
            if (! $cardInfo)
                $this->error('无效的卡券信息');
            $batchName = I('batch_name', null, 'mysql_real_escape_string');
            if (! check_str($batchName, array(
                'null' => false,
                'maxlen_cn' => '12'
            ), $error)) {
                $this->error("活动名称{$error}");
            }
            // 有效期
            $startTime = I('post.start_time');
            if (! check_str($startTime, array(
                'null' => false,
                'strtype' => 'datetime',
                'format' => 'Ymd'
            ), $error)) {
                $this->error("活动开始日期{$error}");
            }
            $endTime = I('post.end_time');
            if (! check_str($endTime, array(
                'null' => false,
                'strtype' => 'datetime',
                'format' => 'Ymd'
            ), $error)) {
                $this->error("活动结束日期{$error}");
            }
            if ($endTime < date('Ymd')) {
                $this->error('活动结束日期不能小于当前日期');
            }
            if (strtotime($endTime) < strtotime($startTime)) {
                $this->error('活动开始日期不能大于活动结束日期');
            }
            // 要不要校验活动日期和卡券日期?
            
            $storageNum = I('storage_num', null, 'mysql_real_escape_string');
            if (! check_str($storageNum, array(
                'null' => false,
                'strtype' => 'int',
                'minval' => '1'
            ), $error)) {
                $this->error("投放数量{$error}");
            }
            $wapInfo = I('wap_info');
            if (! check_str($wapInfo, array(
                'null' => false
            ), $error)) {
                $this->error("活动页面内容{$error}");
            }
            $bottonText = I('botton_text', null, 'mysql_real_escape_string');
            if (! check_str($bottonText, array(
                'null' => false,
                'maxlen_cn' => '8'
            ), $error)) {
                $this->error("领取按钮文字{$error}");
            }
            M()->startTrans();
            $goodsInfo = M('tgoods_info')->field('storage_type,remain_num')
                ->where("goods_id='{$cardInfo['goods_id']}' AND node_id= '{$this->nodeId}'")
                ->lock(true)
                ->find();
            // 库存校验
            if ($goodsInfo['storage_type'] == '1') {
                if ($storageNum > $goodsInfo['remain_num']) {
                    M()->rollback();
                    $this->error("卡券库存不足！");
                }
            }
            // 活动插入
            $data = array(
                'batch_type' => $this->batchType,
                'card_id' => $cardInfo['card_id'],
                // 'member_level' => $cardInfo['batch_no'],
                'node_id' => $this->nodeId,
                'name' => $batchName,
                'start_time' => $startTime . '000000',
                'end_time' => $endTime . '235959',
                'wap_info' => $wapInfo,
                'button_text' => $bottonText,
                'pay_status'=>1,
                // 'put_in_num' => $storageNum,
                'add_time' => date('YmdHis')
            );
            $batchId = M('tmarketing_info')->add($data);
            if (! $batchId) {
                M()->rollback();
                $this->error('系统出错,活动添加失败');
            }
            // tbatch_info数据插入
            $data = array(
                'batch_no' => $cardInfo['batch_no'],
                'batch_short_name' => $cardInfo['goods_name'],
                'batch_name' => $cardInfo['goods_name'],
                'node_id' => $this->nodeId,
                'user_id' => $this->userId,
                'batch_class' => $cardInfo['goods_type'],
                'batch_type' => $cardInfo['source'],
                'use_rule' => $cardInfo['mms_text'],
                'batch_img' => $cardInfo['goods_image'],
                'info_title' => $cardInfo['mms_title'],
                'batch_amt' => $cardInfo['goods_amt'],
                'begin_time' => $cardInfo['begin_time'],
                'end_time' => $cardInfo['end_time'],
                'add_time' => date('YmdHis'),
                'node_pos_group' => $cardInfo['pos_group'],
                'node_pos_type' => $cardInfo['pos_group_type'],
                'batch_desc' => $cardInfo['goods_desc'],
                'node_service_hotline' => $cardInfo['node_service_hotline'],
                'goods_id' => $cardInfo['goods_id'],
                'storage_num' => $storageNum,
                'remain_num' => $storageNum,
                'material_code' => $cardInfo['customer_no'],
                'print_text' => $cardInfo['print_text'],
                'm_id' => $batchId,
                'validate_type' => $cardInfo['validate_type'],
                'card_id' => $cardInfo['card_id']
            );
            $result = M('tbatch_info')->data($data)->add();
            if (! $result) {
                M()->rollback();
                $this->error('系统出错,活动添加失败-0001');
            }
            // 扣减库存
            $goodsM = D('Goods');
            $flag = $goodsM->storagenum_reduc($cardInfo['goods_id'], $storageNum, '', '13', '');
            if ($flag === false) {
                M()->rollback();
                $this->error("添加失败,{$goodsM->getError()}");
            }
            // 卡券投放默认渠道创建
            $channelId = M('tchannel')->where(array(
                'node_id' => $this->node_id,
                'type' => $this->channelType,
                'sns_type' => $this->channelSnsType
            ))->getField('id');
            if (! $channelId) { // 不存在则添加渠道
                $channel_arr = array(
                    'name' => '微信卡券投放默认渠道',
                    'type' => $this->channelType,
                    'sns_type' => $this->channelSnsType,
                    'status' => '1',
                    'add_time' => date('YmdHis'),
                    'node_id' => $this->node_id
                );
                $channelId = M('tchannel')->add($channel_arr);
                if (! $channelId) {
                    M()->rollback();
                    $this->error('微信卡券投放默认渠道创建失败');
                }
            }
            // 发布到卡券投放默认渠道
            $data = array(
                'batch_type' => $this->batchType,
                'batch_id' => $batchId,
                'channel_id' => $channelId,
                'add_time' => date('YmdHis'),
                'node_id' => $this->node_id
            );
            $result = M('tbatch_channel')->add($data);
            if (! $result) {
                M()->rollback();
                $this->error('系统出错,发布默认渠道失败');
            }
            M()->commit();
            $this->success('活动创建成功');
            exit();
        }
        
        $id = I('c_id', 0, 'intval');
        $cardInfo = array();
        if ($id > 0) {
            $map = array(
                'w.id' => $id,
                'w.node_id' => $this->nodeId,
                'w.auth_flag' => array(
                    'in',
                    '1,2'
                ),
                'g.status' => '0'
            );
            $cardInfo = M()->table("twx_card_type w")->join("tgoods_info g ON w.goods_id=g.goods_id")
                ->where($map)
                ->field('w.id,w.title,g.storage_type,g.storage_num,g.remain_num')
                ->find();
            if (! $cardInfo) {
                $this->error('无效的卡券信息', null);
            }
            $cardInfo['goods_name'] = $cardInfo['title'];
        }
        $this->assign('cardInfo', $cardInfo);
        
        $this->display();
    }
    
    // 卡券投放编辑
    public function cardSendEdit()
    {
        $id = I('id', null, 'mysql_real_escape_string');
        $map = array(
            'b.id' => $id,
            'b.status' => '1',
            'b.node_id' => $this->node_id,
            'b.batch_type' => $this->batchType
        );
        $batchInfo = M()->table("tmarketing_info b")->field("b.*,t.title,t.goods_id,g.storage_type,g.remain_num,i.storage_num as istorage_num,i.remain_num as iremain_num")
            ->join("tbatch_info i ON b.id=i.m_id")
            ->join('twx_card_type t ON i.card_id=t.card_id')
            ->join('tgoods_info g ON t.goods_id=g.goods_id')
            ->where($map)
            ->find();
        if (! $batchInfo)
            $this->error('未找到相关活动信息');
        if ($this->isPost()) {
            $batchName = I('batch_name', null, 'mysql_real_escape_string');
            if (! check_str($batchName, array(
                'null' => false,
                'maxlen_cn' => '12'
            ), $error)) {
                $this->error("活动名称{$error}");
            }
            // 有效期
            $startTime = I('post.start_time');
            if (! check_str($startTime, array(
                'null' => false,
                'strtype' => 'datetime',
                'format' => 'Ymd'
            ), $error)) {
                $this->error("活动开始日期{$error}");
            }
            $endTime = I('post.end_time');
            if (! check_str($endTime, array(
                'null' => false,
                'strtype' => 'datetime',
                'format' => 'Ymd'
            ), $error)) {
                $this->error("活动结束日期{$error}");
            }
            if ($endTime < date('Ymd')) {
                $this->error('活动结束日期不能小于当前日期');
            }
            if (strtotime($endTime) < strtotime($startTime)) {
                $this->error('活动开始日期不能大于活动结束日期');
            }
            $storageNum = I('storage_num', null, 'mysql_real_escape_string');
            if (! check_str($storageNum, array(
                'null' => false,
                'strtype' => 'int',
                'minval' => '1'
            ), $error)) {
                $this->error("投放数量{$error}");
            }
            $wapInfo = I('wap_info');
            if (! check_str($wapInfo, array(
                'null' => false
            ), $error)) {
                $this->error("活动页面内容{$error}");
            }
            $bottonText = I('botton_text', null, 'mysql_real_escape_string');
            if (! check_str($bottonText, array(
                'null' => false,
                'maxlen_cn' => '8'
            ), $error)) {
                $this->error("领取按钮文字{$error}");
            }
            M()->startTrans();
            $goodsInfo = M('tgoods_info')->field('goods_id,storage_type,remain_num')
                ->where("goods_id='{$batchInfo['goods_id']}' AND node_id= '{$this->nodeId}'")
                ->lock(true)
                ->find();
            $baInfo = M('tbatch_info')->field('id,storage_num,remain_num')
                ->where("m_id={$batchInfo['id']} AND node_id= '{$this->nodeId}'")
                ->lock(true)
                ->find();
            // 库存校验
            if ($storageNum != $baInfo['storage_num']) { // 库存更新
                if ($goodsInfo['storage_type'] == '1' && $storageNum > $baInfo['storage_num'] && $goodsInfo['remain_num'] < ($storageNum - $baInfo['storage_num'])) {
                    M()->rollback();
                    $this->error('卡券库存不足！现有库存为：' . $goodsInfo['remain_num']);
                }
                if ($storageNum < ($baInfo['storage_num'] - $baInfo['remain_num'])) {
                    M()->rollback();
                    $this->error('投放数量要大于已发放数量');
                }
                $batchRemainNum = $storageNum - ($baInfo['storage_num'] - $baInfo['remain_num']);
                $data = array(
                    'remain_num' => $batchRemainNum,
                    'storage_num' => $storageNum,
                    'update_time' => date('YmdHis')
                );
                $result = M('tbatch_info')->where("id = '{$baInfo['id']}'")->save($data);
                if ($result === false) {
                    M()->rollback();
                    $this->error('库存更新失败');
                }
                $goodsM = D('Goods');
                $count = $storageNum - $baInfo['storage_num'];
                $result = $goodsM->storagenum_reduc($goodsInfo['goods_id'], $count, '', '13', '');
                if (! $result) {
                    M()->rollback();
                    $this->error($goodsM->getError());
                }
            }
            $data = array(
                'name' => $batchName,
                'start_time' => $startTime . '000000',
                'end_time' => $endTime . '235959',
                'wap_info' => $wapInfo,
                'button_text' => $bottonText
            );
            $result = M()->table("tmarketing_info b")->where($map)->save($data);
            if ($result === false) {
                M()->rollback();
                $this->error('系统出错,编辑失败');
            }
            
            M()->commit();
            $this->success('编辑成功');
            exit();
        }
        $this->assign('batchInfo', $batchInfo);
        $this->display();
    }
    // 投放数据
    public function cardSendData()
    {
        $map = array(
            'b.node_id' => $this->nodeId,
            'b.status' => '0',
            'b.trans_type' => '0001',
            'b.wx_open_id' => array(
                'exp',
                'is not null'
            ),
            't.store_mode' => 1
        );
        $userName = I('user_name', null, 'mysql_real_escape_string');
        if (! empty($userName)) {
            $map['u.nickname'] = array(
                'like',
                "%{$userName}%"
            );
        }
        $title = I('title', null, 'mysql_real_escape_string');
        if (! empty($title)) {
            $map['t.title'] = array(
                'like',
                "%{$title}%"
            );
        }
        $batchName = I('batch_name', null, 'mysql_real_escape_string');
        if (! empty($batchName)) {
            $map['m.name'] = array(
                'like',
                "%{$batchName}%"
            );
        }
        // 处理特殊查询字段
        $starTime = I('start_time', null, 'mysql_real_escape_string');
        if (! empty($starTime)) {
            $map['b.trans_time'] = array(
                'egt',
                $starTime . '000000'
            );
        }
        $endTime = I('end_time', null, 'mysql_real_escape_string');
        if (! empty($endTime)) {
            $map['b.trans_time '] = array(
                'elt',
                $endTime . '235959'
            );
        }
        import("ORG.Util.Page");
        $count = M()->table("tbarcode_trace b")->join('twx_assist_number s ON b.assist_number=s.assist_number')
            ->join('tbatch_info       i ON s.card_batch_id=i.id')
            ->join('tmarketing_info   m ON i.m_id=m.id')
            ->join('twx_card_type     t ON i.card_id=t.card_id')
            ->join('twx_user          u ON b.wx_open_id=u.openid')
            ->where($map)
            ->count();
        $p = new Page($count, 10);
        // 确保分页查询条件
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        
        $dataList = M()->table("tbarcode_trace b")->field('b.wx_open_id,u.nickname,t.title,t.card_type,m.name,b.trans_time')
            ->join('twx_assist_number s ON b.assist_number=s.assist_number')
            ->join('tbatch_info       i ON s.card_batch_id=i.id')
            ->join('tmarketing_info   m ON i.m_id=m.id')
            ->join('twx_card_type     t ON i.card_id=t.card_id')
            ->join('twx_user          u ON b.wx_open_id=u.openid')
            ->where($map)
            ->order('b.trans_time DESC')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        $cardType = array(
            '0' => '折扣券',
            '1' => '代金券',
            '2' => '提领券',
            '3' => '优惠券'
        );
        // 分页显示
        $page = $p->show();
        $this->assign("page", $page);
        $this->assign('cardType', $cardType);
        $this->assign('dataList', $dataList);
        $this->display();
    }
    
    // 投放数据下载
    public function cardSendDown()
    {
        $map = array(
            'b.node_id' => $this->nodeId,
            'b.status' => '0',
            'b.trans_type' => '0001',
            'b.wx_open_id' => array(
                'exp',
                'is not null'
            ),
            't.store_mode' => 1
        );
        $userName = I('user_name', null, 'mysql_real_escape_string');
        if (! empty($userName)) {
            $map['u.nickname'] = array(
                'like',
                "%{$userName}%"
            );
        }
        $title = I('title', null, 'mysql_real_escape_string');
        if (! empty($title)) {
            $map['t.title'] = array(
                'like',
                "%{$title}%"
            );
        }
        $batchName = I('batch_name', null, 'mysql_real_escape_string');
        if (! empty($batchName)) {
            $map['m.name'] = array(
                'like',
                "%{$batchName}%"
            );
        }
        // 处理特殊查询字段
        $starTime = I('start_time', null, 'mysql_real_escape_string');
        if (! empty($starTime)) {
            $map['b.trans_time'] = array(
                'egt',
                $starTime . '000000'
            );
        }
        $endTime = I('end_time', null, 'mysql_real_escape_string');
        if (! empty($endTime)) {
            $map['b.trans_time '] = array(
                'elt',
                $endTime . '235959'
            );
        }
        $count = M()->table("tbarcode_trace b")->join('twx_assist_number s ON b.assist_number=s.assist_number')
            ->join('tbatch_info       i ON s.card_batch_id=i.id')
            ->join('tmarketing_info   m ON i.m_id=m.id')
            ->join('twx_card_type     t ON i.card_id=t.card_id')
            ->join('twx_user          u ON b.wx_open_id=u.openid')
            ->where($map)
            ->count();
        if ($count == 0)
            $this->error('未查询到记录！');
        $fileName = '投放数据.csv';
        $fileName = iconv('utf-8', 'gb2312', $fileName);
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        $start_num = 0;
        $page_count = 5000;
        $title = "openId,粉丝名称,卡券名称,卡券类型,活动名称,领取时间\r\n";
        echo $title = iconv('utf-8', 'gbk', $title);
        for ($j = 1; $j < 100; $j ++) {
            $page = ($j - 1) * $page_count;
            $list = M()->table("tbarcode_trace b")->field("b.wx_open_id,u.nickname,t.title,
                                                   CASE t.card_type WHEN '0' THEN '折扣券' WHEN '1' THEN '代金券' WHEN '2' THEN '礼品券' WHEN '3' THEN '优惠券' ELSE '未知' END card_type,
                                                   m.name,
                                                   LEFT(b.trans_time,8) as trans_time
                                                  ")
                ->join('twx_assist_number s ON b.assist_number=s.assist_number')
                ->join('tbatch_info       i ON s.card_batch_id=i.id')
                ->join('tmarketing_info   m ON i.m_id=m.id')
                ->join('twx_card_type     t ON i.card_id=t.card_id')
                ->join('twx_user          u ON b.wx_open_id=u.openid')
                ->where($map)
                ->order('b.trans_time DESC')
                ->limit($page, $page_count)
                ->select();
            if (! $list)
                exit();
            foreach ($list as $v) {
                $nickName = iconv('utf-8', 'gbk', $v['nickname']);
                $title = iconv('utf-8', 'gbk', $v['title']);
                $cardType = iconv('utf-8', 'gbk', $v['card_type']);
                $batchName = iconv('utf-8', 'gbk', $v['name']);
                echo "{$v['wx_open_id']},{$nickName},{$title},{$cardType},{$batchName},{$v['trans_time']}\r\n";
            }
        }
    }
    
    // 投放选择卡券
    public function selectCard()
    {
        $map = array(
            'w.node_id' => $this->nodeId,
            'w.auth_flag' => $this->auth_valid,
            'g.status' => '0'
        );
        import("ORG.Util.Page");
        $count = M()->table("twx_card_type w")->join('tgoods_info g ON w.goods_id=g.goods_id')
            ->where($map)
            ->count();
        $p = new Page($count, 10);
        // 确保分页查询条件
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        $list = M()->table('twx_card_type w')
            ->field('w.*,g.begin_time,g.end_time,g.storage_type,g.storage_num,g.remain_num')
            ->join('tgoods_info g ON w.goods_id=g.goods_id')
            ->where($map)
            ->order('add_time DESC')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        // 分页显示
        $page = $p->show();
        $cardType = array(
            '0' => '折扣券',
            '1' => '代金券',
            '2' => '提领券',
            '3' => '优惠券'
        );
        $this->assign('list', $list);
        $this->assign('cardType', $cardType);
        $this->assign("page", $page);
        $this->display();
    }
    
    // 停用活动
    public function cardBatchStatus()
    {
        $id = I('id', null, 'mysql_real_escape_string');
        $status = I('status', null, 'mysql_real_escape_string');
        $map = array(
            'node_id' => $this->nodeId,
            'batch_type' => $this->batchType,
            'id' => $id
        );
        $batchInfo = M('tmarketing_info')->where($map)->find();
        if (! $batchInfo)
            $this->error('未找到该活动');
        if ($status == '1') {
            $data = array(
                'status' => '2'
            );
        } else {
            $data = array(
                'status' => '1'
            );
        }
        
        $result = M('tmarketing_info')->where($map)->save($data);
        if ($result) {
            node_log('微信投放活动状态更改|活动ID：' . $id);
            $this->success('更新成功');
        } else {
            $this->error('系统出错,更新失败');
        }
    }

    /**
     * 添加测试白名单添加
     */
    public function testWhiteListAdd()
    {
        $card_white_list = I('wxId', '');
        
        if ('' == $card_white_list) {
            $this->error("请输入微信号");
        }
        
        $result = M('tweixin_info')->where(array(
            'node_id' => $this->node_id
        ))->getField('card_white_list');
        $arr = explode(',', $result);
        
        if (count($arr) > 10) {
            $this->ajaxReturn(count($arr), '最多只可添加10个白名单', 0);
        }
        
        foreach ($arr as $v) {
            if ($v == $card_white_list) {
                $this->ajaxReturn($result, '微信号已存在', 0);
            }
        }
        
        $result .= $card_white_list . ',';
        
        $info = M('tweixin_info')->where(array(
            'node_id' => $this->node_id
        ))->save(array(
            'card_white_list' => $result
        ));
        
        if ($info) {
            $arr = explode(',', $result);
            $wx_card = D('WeiXinCard', 'Service');
            $wx_card->init_by_node_id($this->node_id);
            $wx_card->create_white_user($arr);
            $this->ajaxReturn($info, '添加成功', 1);
        } else {
            $this->ajaxReturn($info, '添加失败', 0);
        }
    }

    /**
     * 测试白名单列表
     */
    public function testWhiteList()
    {
        $result = M('tweixin_info')->where(array(
            'node_id' => $this->node_id
        ))->getField('card_white_list');
        $result = explode(',', $result);
        $result = json_encode($result);
        
        if ($result) {
            $this->ajaxReturn($result, '白名单列表获取成功', 1);
        } else {
            $this->ajaxReturn($result, '白名单列表获取失败', 0);
        }
    }

    /**
     * 测试白名单删除
     */
    public function testWhiteListDelete()
    {
        $id = I('wxId', '');
        if ($id || $id == '0') {
            $result = M('tweixin_info')->where(array(
                'node_id' => $this->node_id
            ))->getField('card_white_list');
            $arr = explode(',', $result);
            unset($arr[$id]);
            $result = implode(',', $arr);
            $info = M('tweixin_info')->where(array(
                'node_id' => $this->node_id
            ))->save(array(
                'card_white_list' => $result
            ));
            
            $wx_card = D('WeiXinCard', 'Service');
            $wx_card->init_by_node_id($this->node_id);
            
            $wx_card->create_white_user(array(
                'chini520',
                'Mansfield_young'
            ));
            if ($info) {
                $wx_card = D('WeiXinCard', 'Service');
                $wx_card->init_by_node_id($this->node_id);
                $wx_card->create_white_user($arr);
                $this->ajaxReturn($info, '删除成功', 1);
            } else {
                $this->ajaxReturn($info, '删除失败', 0);
            }
        }
    }

    /**
     * 朋友的券列表
     */
    public function wxFriendCardIndex()
    {
        $map = array(
            'w.node_id' => $this->nodeId,
            'card_class' => '2'
        );
        $cardName = I('card_name', null, 'mysql_real_escape_string');
        if (! empty($cardName)) {
            $map['w.title'] = array(
                'like',
                "%{$cardName}%"
            );
        }
        $storeMode = I('store_mode', null, 'mysql_real_escape_string');
        if (! empty($storeMode)) {
            $map['w.store_mode'] = $storeMode;
        }
        
        $auth_flag = I('auth_flag', 0, 'intval');
        if ($auth_flag > 0) {
            $map['w.auth_flag'] = $auth_flag;
        }
        
        import("ORG.Util.Page");
        $count = M()->table("twx_card_type w")->where($map)->count();
        $p = new Page($count, 10);
        // 确保分页查询条件
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        $list = M()->table('twx_card_type w')
            ->field('w.*,g.storage_type,g.remain_num')
            ->join('tgoods_info g ON w.goods_id=g.goods_id')
            ->where($map)
            ->order('w.id DESC')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        // 分页显示
        $page = $p->show();
        $cardType = array(
            '0' => '折扣券',
            '1' => '代金券',
            '2' => '提领券',
            '3' => '优惠券'
        );
        $authFlag = array(
            '1' => '审核中',
            '2' => '审核通过',
            '3' => '未通过'
        );
        $storeMode = array(
            '1' => '投放',
            '2' => '预存'
        );
        $this->assign('count', $count);
        $this->assign('list', $list);
        $this->assign('cardType', $cardType);
        $this->assign('authFlag', $authFlag);
        $this->assign('storeMode', $storeMode);
        $this->assign("page", $page);
        $this->display();
    }

    /**
     * 添加微信卡券朋友的券
     */
    public function addWxCardFriend()
    {
        $map = array(
                'node_id' => $this->nodeId,
                'account_type' => array('in', ['2','4']),
                'status' => '0'
        );
        $winxinInfo = M('tweixin_info')->where($map)->find();
        // 微信已认证服务号并且状态正常的
        if (!$winxinInfo) {
            $this->error("请先配置微信公众账号。", array('立即绑定' => U('Weixin/Weixin/index')));
        }

        if ($this->ispost()) {
            $error = '';
            $goodsId = I('goods_id',null,'mysql_real_escape_string');
            $map = array(
                'node_id' => $this->node_id,
                'status' => '0',
                'source' => '0',
                'goods_id' => $goodsId,
                'goods_type' => array('in',array('1','2'))
            );
            $goodsInfo = M('tgoods_info')->where($map)->find();
            if(!$goodsInfo) $this->error('未找到有效的卡券信息');
            //商户名称
            $nodeName = I('node_name',null,'mysql_real_escape_string');
            if(!check_str($nodeName,array('null'=>false,'maxlen_cn'=>'12'),$error)){
                $this->error("商户名称{$error}");
            }
            // 商家logo
            $nodeImg = I('node_img', null);
            if (! check_str($nodeImg, array(
                'null' => false
            ), $error)) {
                $this->error("请上传商户logo");
            }
            $cardType = $goodsInfo['goods_type'];
            switch ($cardType) {
                case '1': // 代金券
                    $giftTitle = $goodsInfo['goods_amt'].'元代金券';
                    break;
                case '2': // 提领券
                          // 提领商品名称
                    $giftTitle = I('gift_title');
                    if (! check_str($giftTitle, array(
                        'null' => false,
                        'maxlen_cn' => '6'
                    ), $error)) {
                        $this->error("提领商品名称{$error}");
                    }
                    // 提领数量
                    $giftNum = I('gift_num');
                    if (! check_str($giftNum, array(
                        'null' => true,
                        'strtype' => 'int',
                        'minval' => '1',
                        'maxval' => '999'
                    ), $error)) {
                        $this->error("提领数量{$error}");
                    }
                    // 提领单位
                    $giftUnit = I('gift_unit');
                    if (! check_str($giftUnit, array(
                        'null' => true,
                        'maxlen_cn' => '2'
                    ), $error)) {
                        $this->error("提领单位{$error}");
                    }
                    // 提领详情
                    $gift = I('gift');
                    if (! check_str($gift, array(
                        'null' => false,
                        'maxlen_cn' => '100'
                    ), $error)) {
                        $this->error("礼品内容{$error}");
                    }
                    break;
                default:
                    $this->error('未知的卡券类型');
            }
            // 卡券颜色
            $cardColor = I('card_color', null, 'mysql_real_escape_string');
            if (! check_str($cardColor, array(
                'null' => false
            ), $error)) {
                $this->error("请选择卡券颜色");
            }
            // 卡券日期处理
            $beginDate = I('post.start_time');
            if (! check_str($beginDate, array(
                'null' => false,
                'strtype' => 'datetime',
                'format' => 'Ymd'
            ), $error)) {
                $this->error("使用开始时间日期{$error}");
            }
            $endDate = I('post.end_time');
            if (! check_str($endDate, array(
                'null' => false,
                'strtype' => 'datetime',
                'format' => 'Ymd'
            ), $error)) {
                $this->error("使用结束时间日期{$error}");
            }
            if ($endDate < $beginDate) {
                $this->error('有效期开始日期不能大于结束时间');
            }
            $dateBeginTimestamp = strtotime($beginDate . '000000');
            $dateEndTimestamp = strtotime($endDate . '235959');
            $day = ($dateEndTimestamp-$dateBeginTimestamp)/3600/24;
            if($day > 90) $this->error('有效期不能大于90天');
            // 可用时段
            $timeLimit = I('time_limit');
            if (! empty($timeLimit))
                $timeLimit = implode(',', $timeLimit);
                // 简介
            $abstract = I('abstract');
            if (! check_str($abstract, array(
                'null' => false,
                'maxlen_cn' => '30'
            ), $error)) {
                $this->error("简介{$error}");
            }
            // 封面图片
            $iconUrlList = I('icon_url_list');
            if (! check_str($iconUrlList, array(
                'null' => false
            ), $error)) {
                $this->error("请上传封面图片");
            }
            // 使用须知
            $description = I('description');
            if (! check_str($description, array(
                'null' => false,
                'maxlen_cn' => '1000'
            ), $error)) {
                $this->error("使用须知{$error}");
            }
            // 图文介绍
            $textImageList = I('text_image_list');
            $textContentList = I('text_content_list');
            $textImageArr = array();
            for ($i = 0; $i < count($textImageList); $i ++) {
                if (! check_str($textContentList[$i], array('null' => false,'maxlen_cn' => '5000'), $error)) {
                    $this->error("图文介绍说明{$error}");
                }
                if (! check_str($textImageList[$i], array('null' => false), $error)) {
                    $this->error("请上传图文介绍图片");
                }
                $textImageArr[$i]['image'] = $textImageList[$i];
                $textImageArr[$i]['content'] = $textContentList[$i];
            }
            // 自定义入口
            $customType = I('custom_type');
            if ($customType == '1') {
                // 入口名称
                $customUrlName = I('custom_url_name');
                if (! check_str($customUrlName, array(
                    'null' => false,
                    'maxlen_cn' => '5'
                ), $error)) {
                    $this->error("入口名称{$error}");
                }
                // 引导语
                $customUrlSubTitle = I('custom_url_sub_title');
                if (! check_str($customUrlSubTitle, array(
                    'null' => true,
                    'maxlen_cn' => '9'
                ), $error)) {
                    $this->error("引导语{$error}");
                }
                // 跳转链接
                $customUrlType = I('custom_url_type');
                $customUrl = '';
                switch ($customUrlType) {
                    case '1': // 网页链接
                        $customGoUrl = I('custom_go_url');
                        if (! check_str($customGoUrl, array(
                            'null' => false
                        ), $error)) {
                            $this->error("请填写网页链接");
                        }
                        $customUrl = $customGoUrl;
                        break;
                    case '2': // 图文链接
                        $materialIdSelected = I('material_id_selected', null, 'mysql_real_escape_string');
                        $materialLink = M('twx_material')->where("node_id='{$this->nodeId}' and id='{$materialIdSelected}'")->getField('material_link');
                        if (empty($materialLink))
                            $this->error('请选择图文消息');
                        $customUrl = $materialLink;
                        break;
                    default:
                        $this->error('未知的点击跳转类型');
                }
            }
            // 卡券验证方式
            $codeType = I('code_type');
            if (! check_str($codeType, array(
                'null' => false,
                'strtype' => 'int',
                'minval' => '1',
                'maxval' => '3'
            ), $error)) {
                $this->error("卡券验证方式");
            }
            // 卡券验证后赠送
            $isConsumeShare = I('isConsumeShare');
            if ($isConsumeShare == '1') { // 赠送
                $consumeShareType = '0'; // 赠送类型
                switch ($consumeShareType) {
                    case '0': // 赠送一张相同的券
                        $consumeShareSelfNum = '1';
                        break;
                    case '1': // 赠送其他的券
                        $shareCardId = I('shareCardId');
                        $where = array(
                            'node_id' => $this->nodeId,
                            'card_class' => '2',
                            'auth_flag' => '2'
                        );
                        $cardId = M('twx_card_type')->where($where)->getField('card_id');
                        if (empty($cardId)) {
                            $this->error('无效的赠送卡券');
                        }
                        $consumeShareCardList = $cardId;
                        break;
                    default:
                        $this->error('未知的赠送类型');
                }
            }
            // 添加数据
            $data = array(
                'node_id' => $this->nodeId,
                'card_class' => '2',
                'goods_id' => $goodsInfo['goods_id'],
                'card_type' => $cardType,
                'title' => $giftTitle,
                'logo_url' => $nodeImg,
                'code_type' => $codeType,
                'brand_name' => $nodeName,
                'gift' => $gift,
                'color' => $cardColor,
                'description' => $description,
                'use_custom_code' => '2',
                'date_type' => '1',
                'date_begin_timestamp' => $dateBeginTimestamp,
                'date_end_timestamp' => $dateEndTimestamp,
                'url_name_type' => $customUrlName,
                'custom_url' => $customUrl,
                'gift_num' => $giftNum,
                'gift_unit' => $giftUnit,
                'reduce_cost' => $goodsInfo['goods_amt'],
                'add_time' => date('YmdHis'),
                'abstract' => $abstract,
                'icon_url_list' => $iconUrlList,
                'store_type' => '酒店',
                'text_image_list' => json_encode($textImageArr),
                'time_limit' => $timeLimit,
                'custom_url_sub_title' => $customUrlSubTitle,
                'consume_share_self_num' => $consumeShareSelfNum,
                'consume_share_card_list' => $consumeShareCardList
            );
            M()->startTrans();
            // 数据插入
            $id = M('twx_card_type')->add($data);
            if (!$id) {
                M()->rollback();
                $this->error('系统出错,添加失败');
            }
            // 微信插入
            $cardId = $this->cardService->friendCardAdd($id);
            if (! $cardId) {
                M()->rollback();
                $this->error('卡券创建失败:' . $this->cardService->error);
            }
            //暂时默认投放,以后投放功能添加朋友的券的时候删掉该处
            $mData = array(
                'batch_type' => '40',
                'node_id' => $data['node_id'],
                'add_time' => date('YmdHis')
            );
            $batchId = M('tmarketing_info')->add($mData);
            if (!$batchId) {
                M()->rollback();
                $this->error('系统出错,微信卡券活动创建失败');
            }
            // tbatch_info数据插入
            $bData = array(
                'batch_no' => $goodsInfo['batch_no'],
                'batch_short_name' => $goodsInfo['goods_name'],
                'batch_name' => $goodsInfo['goods_name'],
                'node_id' => $data['node_id'],
                'user_id' => $data['user_id'],
                'batch_class' => $goodsInfo['goods_type'],
                'batch_type' => $goodsInfo['source'],
                'use_rule' => $goodsInfo['mms_text'],
                'batch_img' => $goodsInfo['goods_image'],
                'info_title' => $goodsInfo['mms_title'],
                'batch_amt' => $goodsInfo['goods_amt'],
                'add_time' => date('YmdHis'),
                'node_pos_group' => $goodsInfo['pos_group'],
                'node_pos_type' => $goodsInfo['pos_group_type'],
                'batch_desc' => $goodsInfo['goods_desc'],
                'node_service_hotline' => $goodsInfo['node_service_hotline'],
                'goods_id' => $goodsInfo['goods_id'],
                'print_text' => $goodsInfo['print_text'],
                'm_id' => $batchId,
                'validate_type' => $goodsInfo['validate_type'],
                'card_id' => $cardId
            );
            $result = M('tbatch_info')->data($bData)->add();
            if (! $result) {
                M()->rollback();
                $this->error('系统出错,微信卡券活动添加失败-0001');
            }
            M()->commit();
            $this->success('创建成功');
            exit();
        }
        $color = $this->cardService->getcolors();
        $this->assign('color', $color['colors']);
        $this->display();
    }

    /**
     * 朋友的券详情
     */
    public function friendCardDetail()
    {
        $id = I('id');
        $wxCardInfo = M()->table("twx_card_type c")->field("c.*,g.goods_name")
            ->join("tgoods_info g ON c.goods_id=g.goods_id")
            ->where("c.node_id='{$this->nodeId}' and c.id='{$id}'")
            ->find();
        if (! $wxCardInfo)
            $this->error('数据未找到');
        //图文列表处理
        $wxCardInfo['text_image_list'] = json_decode($wxCardInfo['text_image_list'],true);
        $this->assign('wxCardInfo', $wxCardInfo);
        $this->display();
    }
    
    public function downFriendCode(){
        $id = I('id');
        $cardInfo = M('twx_card_type')->where("node_id='{$this->nodeId}' and id='{$id}'")->find();
        if (!$cardInfo) $this->error('数据未找到');
        $resutl = $this->cardService->create_wx_qrcode($cardInfo['card_id']);
        if($resutl['errcode'] != '0') $this->error($resutl['errmsg']);
        $makecode = new MakeCode();
        $fileName = $cardInfo['title'];
        $makecode->MakeCodeImg($resutl['url'], true, '1', '',$fileName, '', '', true);
        
    }
    
    /**
     * 微信朋友的券测试函数
     */
    public function friendCardTest(){
        $assist_number = '358442032088';
        $wx_open_id = 'oEVlMswim4TvlrcA05hVlbLzfaRg';
        $card_id = 'pVTJst3F64SJKBLA7yzvGWenUEBg';
        $resutl = $this->cardService->create_code($assist_number,$wx_open_id,$card_id);
        if($resutl) echo 'ok';
    }
}