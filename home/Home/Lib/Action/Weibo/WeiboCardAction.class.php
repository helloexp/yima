<?php

class WeiboCardAction extends BaseAction {

    public $appId;

    public $appSecret;

    public $accessToken;

    public $cardService;

    public $batchType = '40';

    public $channelType = "4";

    public $channelSnsType = "49";

    public $winboInfo;
    // 由于测试平台卡券无法通过验证，此处用于标记有效卡券的状态值，在初始化中会根据当前代码平台设置
    private $auth_valid = '2';

    public function _initialize() {
        parent::_initialize();
        $this->winboInfo = M('tweibo_info')->field('id,card_uid,card_key')
            ->where(
            "card_uid is not null and card_key is not null and node_id='{$this->nodeId}'")
            ->find();
        /*
         * $this->merchantUid = '1913173431'; $this->pin = 'pEy5e2J1JGkPvEnS';
         */
        // $this->accessToken = $winboInfo['app_access_token'];
        if (! empty($this->winboInfo))
            $this->assign('is_bind', true);
        else
            $this->assign('is_bind', false);
        $service = D('WeiboCard', 'Service');
        $service->init($this->winboInfo['card_uid'], 
            $this->winboInfo['card_key']);
        $this->cardService = $service;
        // PRODUCTION_FLAG
        $this->auth_valid = C('PRODUCTION_FLAG') == 1 ? '2' : '1';
        $this->assign('auth_valid', $this->auth_valid);
    }

    public function index() {
        $map = array(
            'w.node_id' => $this->nodeId);
        $cardName = I('card_name', null, 'mysql_real_escape_string');
        if (! empty($cardName)) {
            $map['w.title'] = array(
                'like', 
                "%{$cardName}%");
        }
        
        $auth_flag = I('auth_flag', 0, 'intval');
        if ($auth_flag > 0) {
            $map['w.auth_flag'] = $auth_flag;
        }
        import("ORG.Util.Page");
        $count = M()->table("tweibo_card_type w")->where($map)->count();
        $p = new Page($count, 10);
        // 确保分页查询条件
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        $list = M()->table('tweibo_card_type w')
            ->field(
            'w.title,w.startvaliddate,w.endvaliddate,w.card_type,w.auth_flag,w.id,g.verify_begin_date,g.verify_end_date,g.verify_begin_type,g.verify_end_type,g.storage_type,g.remain_num')
            ->join('tgoods_info g ON w.goods_id=g.goods_id')
            ->where($map)
            ->order('w.id DESC')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        // 分页显示
        $page = $p->show();
        $cardType = array(
            '1' => '代金券', 
            '3' => '优惠券');
        $authFlag = array(
            '1' => '审核中', 
            '2' => '审核通过', 
            '3' => '未通过');
        $this->assign('list', $list);
        $this->assign('cardName', $cardName);
        $this->assign('cardType', $cardType);
        $this->assign('authFlag', $authFlag);
        $this->assign("page", $page);
        $this->display();
    }

    public function adduser() {
        if (IS_AJAX) {
            $merchantUid = $_REQUEST['merchantUid'];
            $pin = $_REQUEST['pin'];
            $uuid = $_REQUEST['uuid'];
            if (empty($merchantUid))
                $this->error("微博账号不能为空");
            if (empty($pin))
                $this->error("微博支付秘钥不能为空");
            if (mb_strlen($pin, 'utf8') != 16)
                $this->error("请输入微博支付16位支付秘钥");
            $data = array(
                'card_uid' => $merchantUid, 
                'card_key' => $pin, 
                'uid' => $this->userId, 
                'node_id' => $this->node_id, 
                'token' => $this->userInfo['token'], 
                'content' => '', 
                'status' => 3, 
                'add_time' => date('YmdHis'));
            if (! empty($uuid)) {
                $result = M('tweibo_info')->where(
                    array(
                        'id' => $uuid))->save(
                    array(
                        'card_uid' => $merchantUid, 
                        'card_key' => $pin));
            } else {
                $result = M('tweibo_info')->add($data);
            }
            if ($result)
                $this->success('官方微博账号绑定成功');
            else
                $this->error('官方微博账号绑定失败');
        } else {
            $this->assign('winboInfo', $this->winboInfo);
            $this->display();
        }
    }

    public function add() {
        if ($this->isPost()) {
            $goodsId = I('goods_id', null, 'mysql_real_escape_string');
            $data = array();
            $data['node_id'] = $this->node_id;
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
                        '3')));
            $goodsInfo = M('tgoods_info')->where($map)->find();
            if (! $goodsInfo)
                $this->error('未找到有效的卡券信息，请选择卡券');
            $data['goods_id'] = $goodsId;
            $error = '';
            // 商户名称
            $data['access_mode'] = 1;
            $type = $_REQUEST['type'];
            $vstartDate = $_REQUEST['vstartDate'];
            $vendDate = $_REQUEST['vendDate'];
            $contactphone = $_REQUEST['contactPhone'];
            $extension = $_REQUEST['extension'];
            if (! empty($extension))
                $data['extension'] = $extension;
            if (! empty($vstartDate))
                $data['start_date'] = date('YmdHis', strtotime($vstartDate));
            if (! empty($vendDate))
                $data['end_date'] = date('YmdHis', strtotime($vendDate));
            
            $merchantName = I('merchantName', null, 'mysql_real_escape_string');
            if (! check_str($merchantName, 
                array(
                    'maxlen_cn' => 12, 
                    'null' => false), $error)) {
                $this->error("商户名称{$error}");
            }
            $data['merchant_name'] = $merchantName;
            // 卡卷标题
            $title = I('title', null, 'mysql_real_escape_string');
            if (! check_str($title, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '85'), $error)) {
                $this->error("卡券标题{$error}");
            }
            $data['title'] = $title;
            // 副标题
            $subTitle = I('subTitle', null, 'mysql_real_escape_string');
            if (! check_str($subTitle, 
                array(
                    'null' => true, 
                    'maxlen_cn' => '85'), $error)) {
                $this->error("卡券副标题{$error}");
            }
            if (! empty($subTitle))
                $data['sub_title'] = $subTitle;
                // 有效期
            $startDate = I('startDate', null, 'mysql_real_escape_string');
            $endDate = I('endDate', null, 'mysql_real_escape_string');
            if (strtotime($startDate) > strtotime($endDate)) {
                $this->error('起始时间不能大于结束时间');
            }
            if ((strtotime($endDate) - strtotime($startDate)) > 24 * 3600 * 366)
                $this->error('有效期时间不能超过一年');
            if ($goodsInfo['verify_begin_type'] == $goodsInfo['verify_end_type'] &&
                 $goodsInfo['verify_end_type'] == 0) {
                // mysql 会根据类型比较
                if (strtotime($goodsInfo['verify_begin_date']) >
                 strtotime($startDate) ||
                 strtotime($goodsInfo['verify_end_date']) < strtotime($endDate))
                $this->error('有效期时间有误');
        } elseif ($goodsInfo['verify_begin_type'] ==
             $goodsInfo['verify_end_type'] && $goodsInfo['verify_end_type'] == 1) {
            $time1 = (strtotime($goodsInfo['verify_end_date']) -
             strtotime($goodsInfo['verify_begin_date'])) * 24 * 3600;
        $time2 = strtotime($endDate) - strtotime($startDate);
        if ($time2 > $time1)
            $this->error('有效期时间有误');
    }
    $data['startvaliddate'] = date('YmdHis', strtotime($startDate));
    $data['endvaliddate'] = date('YmdHis', strtotime($endDate));
    if (! in_array($goodsInfo['goods_type'], 
        array(
            1, 
            3)) || $goodsInfo['goods_type'] != $type)
        $this->error('错误的卡券类型，支持代金券和折扣券');
        // 卡券原价
    $data['card_type'] = $type;
    $nominalPrice = $_REQUEST['nominalPrice'];
    if (! check_str($nominalPrice, 
        array(
            'null' => false, 
            'minval' => 1, 
            'strtype' => 'number'), $error)) {
        $this->error("卡券原价{$error}");
    }
    $data['nominal_price'] = $nominalPrice;
    // 卡券售卖价
    $price = $_REQUEST['price'];
    if (! check_str($price, 
        array(
            'null' => false, 
            'minval' => 1, 
            'strtype' => 'number'), $error)) {
        $this->error("卡券售卖价{$error}");
    }
    $data['price'] = $price;
    // 卡券发行量
    $circulation = $_REQUEST['circulation'];
    if (! check_str($circulation, 
        array(
            'null' => false, 
            'strtype' => 'int'), $error)) {
        $this->error("卡券数量{$error}");
    }
    $data['circulation'] = $circulation;
    // 领券限制
    $limited = I('limited', null, 'mysql_real_escape_string');
    if (! check_str($limited, 
        array(
            'null' => false, 
            'minval' => 1, 
            'strtype' => 'int'), $error)) {
        $this->error("单个用户限购{$error}");
    }
    $data['limited'] = $limited;
    // 图片处理
    $picUrl = I('picUrl', null);
    if (! check_str($picUrl, array(
        'null' => false), $error)) {
        $this->error("商家/商品图片{$error}");
    }
    if (strpos($picUrl, 'http') === false) {
        $tempImagePath = APP_PATH . 'Upload/img_tmp/' . $this->nodeId; // 临时存放目录
        $photoImagePath = APP_PATH . 'Upload/' . $this->nodeId; // 头像存放目录
                                                                // 图片是否存在
        $photoR = is_file($photoImagePath . '/' . $picUrl);
        $tempR = is_file($tempImagePath . '/' . $picUrl);
        if (! $photoR && ! $tempR) {
            $this->error("请上传商家/商品图片");
        }
        if (! $photoR) { // 移动图片
            if (! is_dir($photoImagePath)) {
                if (! mkdir($photoImagePath, 0777))
                    $this->error('目录创建失败');
            }
            $flag = copy($tempImagePath . '/' . $picUrl, 
                $photoImagePath . '/' . $picUrl);
            if (! $flag)
                $this->error('系统出错,图片处理失败');
        }
    }
    if (! check_str($contactphone, 
        array(
            'null' => false, 
            'maxlen_cn' => 20), $error)) {
        $this->error("客服电话{$error}");
    }
    $data['contactphone'] = $contactphone;
    $data['pic_url'] = 'http://' . $_SERVER['HTTP_HOST'] . '/' .
         trim($photoImagePath, './') . '/' . $picUrl;
    $intro = $_REQUEST['intro'];
    if (! check_str($intro, 
        array(
            'null' => true, 
            'maxlen_cn' => 100), $error)) {
        $this->error("卡券介绍{$error}");
    }
    $data['intro'] = $intro;
    // 数据插入
    $cardId = M('tweibo_card_type')->add($data);
    if (! $cardId) {
        $this->error('系统出错,添加失败');
    }
    // 微博插入测试环境关闭微博插入
    $weiboResult = $this->cardService->create($cardId);
    if ($weiboResult) {
        $this->success('创建微博卡券成功', null, 
            array(
                'goUrl' => U('add_success', 
                    array(
                        'c_id' => $cardId))));
    } else {
        $result = M('tweibo_card_type')->where("id='{$cardId}'")->delete();
        if ($result === false) {
            log::write('微博卡券删除失败id:' . $cardId);
        }
        $this->error('创建微博卡券失败:' . $this->cardService->error);
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
            '0,1'));
    $goodsInfo = M('tgoods_info')->where($map)->find();
    if (! $goodsInfo) {
        $this->error('无效的卡券信息');
    }
    if ($goodsInfo['goods_type'] == 0) {
        $goodsInfo['goods_type'] = 3;
    }
    if ($goodsInfo['verify_begin_type'] == $goodsInfo['verify_end_type']) {
        $goodsInfo['verify_time_type'] = $goodsInfo['verify_begin_type'];
    } else {
        $goodsInfo['verify_time_type'] = 0;
    }
    $goodsInfo['goods_image_url'] = get_upload_url($goodsInfo['goods_image']);
    $this->assign('goodsInfo', $goodsInfo);
}
$node_info = M('tnode_info')->field('node_name,head_photo')
    ->where("node_id='{$this->nodeId}'")
    ->find();
$this->assign('nodeInfo', $node_info);
$this->display();
}

// 卡券详情
public function cardDetail() {
$id = I('id', null, 'mysql_real_escape_string');
$map = array(
    'w.id' => $id, 
    'w.node_id' => $this->nodeId);
$cardInfo = M()->table("tweibo_card_type w")->field(
    'w.*,g.verify_begin_date,g.verify_end_date,g.verify_begin_type,g.verify_end_type,g.storage_type,g.remain_num')
    ->join("tgoods_info g ON w.goods_id=g.goods_id")
    ->where($map)
    ->find();
if (! $cardInfo)
    $this->error('未找到该卡券信息');
    /*
 * //门店获取 $storeMap = array( 'store_id' =>
 * array('in',$cardInfo['store_id_list']) ); $storeList =
 * M('tstore_info')->field('store_name,address')->where($storeMap)->select();
 * //卡券颜色 $color = $this->cardService->getcolors(); //$colorVal = array();
 * foreach($color['colors'] as $v){ $colorVal[$v['name']] = $v['value']; }
 * $this->assign('color',$color); $this->assign('colorVal',$colorVal);
 */
$cardType = array(
    '1' => '代金券', 
    '3' => '优惠券');
$authFlag = array(
    '1' => '审核中', 
    '2' => '审核通过', 
    '3' => '未通过');
$this->assign('cardType', $cardType);
$this->assign('authFlag', $authFlag);
$this->assign('cardInfo', $cardInfo);
// $this->assign('storeList',$storeList);
$this->display();
}

// 微信卡券投放
public function cardSendIndex() {
$map = array(
    'w.node_id' => $this->nodeId, 
    'w.batch_type' => $this->batchType);
$batchName = I('batch_name', null, 'mysql_real_escape_string');
if (! empty($batchName)) {
    $map['w.name'] = array(
        'like', 
        "%{$batchName}%");
}
// 处理特殊查询字段
$starTime = I('start_time', null, 'mysql_real_escape_string');
if (! empty($starTime)) {
    $map['w.add_time'] = array(
        'egt', 
        $starTime . '000000');
}
$endTime = I('end_time', null, 'mysql_real_escape_string');
if (! empty($endTime)) {
    $map['w.add_time '] = array(
        'elt', 
        $endTime . '235959');
}
$status = I('status', null, 'mysql_real_escape_string');
if (isset($status) && $status != '') {
    $map['w.status'] = $status;
}
import("ORG.Util.Page");
$count = M()->table("tmarketing_info w")->where($map)->count();
$p = new Page($count, 10);
// 确保分页查询条件
foreach ($_REQUEST as $key => $val) {
    $p->parameter .= "$key=" . urlencode($val) . "&";
}
// 卡券投放默认渠道
$channelId = M('tchannel')->where(
    array(
        'node_id' => $this->node_id, 
        'type' => $this->channelType, 
        'sns_type' => $this->channelSnsType))->getField('id');
$list = M()->table("tmarketing_info w")->field(
    'w.*,n.id as labe_id,c.title,c.id as c_id,b.storage_num,b.remain_num')
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
    '2' => '停用');
$this->assign('list', $list);
$this->assign('status', $status);
$this->assign("page", $page);
$this->display();
}

// 新增卡券投放
public function cardSendAdd() {
if ($this->isPost()) {
    $error = '';
    $id = I('c_id', null, 'mysql_real_escape_string');
    $map = array(
        'w.id' => $id, 
        'w.node_id' => $this->nodeId, 
        'w.auth_flag' => $this->auth_valid, 
        'g.status' => '0');
    $cardInfo = M()->table("twx_card_type w")->join(
        "tgoods_info g ON w.goods_id=g.goods_id")
        ->where($map)
        ->find();
    if (! $cardInfo)
        $this->error('无效的卡券信息');
    $batchName = I('batch_name', null, 'mysql_real_escape_string');
    if (! check_str($batchName, 
        array(
            'null' => false, 
            'maxlen_cn' => '12'), $error)) {
        $this->error("活动名称{$error}");
    }
    // 有效期
    $startTime = I('post.start_time');
    if (! check_str($startTime, 
        array(
            'null' => false, 
            'strtype' => 'datetime', 
            'format' => 'Ymd'), $error)) {
        $this->error("活动开始日期{$error}");
    }
    $endTime = I('post.end_time');
    if (! check_str($endTime, 
        array(
            'null' => false, 
            'strtype' => 'datetime', 
            'format' => 'Ymd'), $error)) {
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
    if (! check_str($storageNum, 
        array(
            'null' => false, 
            'strtype' => 'int', 
            'minval' => '1'), $error)) {
        $this->error("投放数量{$error}");
    }
    $wapInfo = I('wap_info');
    if (! check_str($wapInfo, array(
        'null' => false), $error)) {
        $this->error("活动页面内容{$error}");
    }
    $bottonText = I('botton_text', null, 'mysql_real_escape_string');
    if (! check_str($bottonText, 
        array(
            'null' => false, 
            'maxlen_cn' => '8'), $error)) {
        $this->error("领取按钮文字{$error}");
    }
    M()->startTrans();
    $goodsInfo = M('tgoods_info')->field('storage_type,remain_num')
        ->where(
        "goods_id='{$cardInfo['goods_id']}' AND node_id= '{$this->nodeId}'")
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
        // 'put_in_num' => $storageNum,
        'add_time' => date('YmdHis'));
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
        'card_id' => $cardInfo['card_id']);
    $result = M('tbatch_info')->data($data)->add();
    if (! $result) {
        M()->rollback();
        $this->error('系统出错,活动添加失败-0001');
    }
    // 扣减库存
    $goodsM = D('Goods');
    $flag = $goodsM->storagenum_reduc($cardInfo['goods_id'], $storageNum, '', 
        '13', '');
    if ($flag === false) {
        M()->rollback();
        $this->error("添加失败,{$goodsM->getError()}");
    }
    // 卡券投放默认渠道创建
    $channelId = M('tchannel')->where(
        array(
            'node_id' => $this->node_id, 
            'type' => $this->channelType, 
            'sns_type' => $this->channelSnsType))->getField('id');
    if (! $channelId) { // 不存在则添加渠道
        $channel_arr = array(
            'name' => '微信卡券投放默认渠道', 
            'type' => $this->channelType, 
            'sns_type' => $this->channelSnsType, 
            'status' => '1', 
            'add_time' => date('YmdHis'), 
            'node_id' => $this->node_id);
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
        'node_id' => $this->node_id);
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
        'w.auth_flag' => $this->auth_valid, 
        'g.status' => '0');
    $cardInfo = M()->table("twx_card_type w")->join(
        "tgoods_info g ON w.goods_id=g.goods_id")
        ->where($map)
        ->field('w.id,w.title,g.storage_type,g.storage_num,g.remain_num')
        ->find();
    if (! $cardInfo) {
        $this->error('无效的卡券信息', null);
    }
}
$this->assign('cardInfo', $cardInfo);

$this->display();
}

// 卡券投放编辑
public function cardSendEdit() {
$id = I('id', null, 'mysql_real_escape_string');
$map = array(
    'b.id' => $id, 
    'b.status' => '1', 
    'b.node_id' => $this->node_id, 
    'b.batch_type' => $this->batchType);
$batchInfo = M()->table("tmarketing_info b")->field(
    "b.*,t.title,t.goods_id,g.storage_type,g.remain_num,i.storage_num as istorage_num,i.remain_num as iremain_num")
    ->join("tbatch_info i ON b.id=i.m_id")
    ->join('twx_card_type t ON i.card_id=t.card_id')
    ->join('tgoods_info g ON t.goods_id=g.goods_id')
    ->where($map)
    ->find();
if (! $batchInfo)
    $this->error('未找到相关活动信息');
if ($this->isPost()) {
    $batchName = I('batch_name', null, 'mysql_real_escape_string');
    if (! check_str($batchName, 
        array(
            'null' => false, 
            'maxlen_cn' => '12'), $error)) {
        $this->error("活动名称{$error}");
    }
    // 有效期
    $startTime = I('post.start_time');
    if (! check_str($startTime, 
        array(
            'null' => false, 
            'strtype' => 'datetime', 
            'format' => 'Ymd'), $error)) {
        $this->error("活动开始日期{$error}");
    }
    $endTime = I('post.end_time');
    if (! check_str($endTime, 
        array(
            'null' => false, 
            'strtype' => 'datetime', 
            'format' => 'Ymd'), $error)) {
        $this->error("活动结束日期{$error}");
    }
    if ($endTime < date('Ymd')) {
        $this->error('活动结束日期不能小于当前日期');
    }
    if (strtotime($endTime) < strtotime($startTime)) {
        $this->error('活动开始日期不能大于活动结束日期');
    }
    $storageNum = I('storage_num', null, 'mysql_real_escape_string');
    if (! check_str($storageNum, 
        array(
            'null' => false, 
            'strtype' => 'int', 
            'minval' => '1'), $error)) {
        $this->error("投放数量{$error}");
    }
    $wapInfo = I('wap_info');
    if (! check_str($wapInfo, array(
        'null' => false), $error)) {
        $this->error("活动页面内容{$error}");
    }
    $bottonText = I('botton_text', null, 'mysql_real_escape_string');
    if (! check_str($bottonText, 
        array(
            'null' => false, 
            'maxlen_cn' => '8'), $error)) {
        $this->error("领取按钮文字{$error}");
    }
    M()->startTrans();
    $goodsInfo = M('tgoods_info')->field('goods_id,storage_type,remain_num')
        ->where(
        "goods_id='{$batchInfo['goods_id']}' AND node_id= '{$this->nodeId}'")
        ->lock(true)
        ->find();
    $baInfo = M('tbatch_info')->field('id,storage_num,remain_num')
        ->where("m_id={$batchInfo['id']} AND node_id= '{$this->nodeId}'")
        ->lock(true)
        ->find();
    // 库存校验
    if ($storageNum != $baInfo['storage_num']) { // 库存更新
        if ($goodsInfo['storage_type'] == '1' &&
             $storageNum > $baInfo['storage_num'] &&
             $goodsInfo['remain_num'] < ($storageNum - $baInfo['storage_num'])) {
            M()->rollback();
            $this->error('卡券库存不足！现有库存为：' . $goodsInfo['remain_num']);
        }
        if ($storageNum < ($baInfo['storage_num'] - $baInfo['remain_num'])) {
            M()->rollback();
            $this->error('投放数量要大于已发放数量');
        }
        $batchRemainNum = $storageNum -
             ($baInfo['storage_num'] - $baInfo['remain_num']);
        $data = array(
            'remain_num' => $batchRemainNum, 
            'storage_num' => $storageNum, 
            'update_time' => date('YmdHis'));
        $result = M('tbatch_info')->where("id = '{$baInfo['id']}'")->save($data);
        if ($result === false) {
            M()->rollback();
            $this->error('库存更新失败');
        }
        $goodsM = D('Goods');
        $count = $storageNum - $baInfo['storage_num'];
        $result = $goodsM->storagenum_reduc($goodsInfo['goods_id'], $count, '', 
            '13', '');
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
        'button_text' => $bottonText);
    $result =M()->table("tmarketing_info b")->where($map)->save($data);
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
public function cardSendData() {
$map = array(
    'b.node_id' => $this->nodeId, 
    'b.status' => '0', 
    'b.trans_type' => '0001', 
    'b.wx_open_id' => array(
        'exp', 
        'is not null'));
$userName = I('user_name', null, 'mysql_real_escape_string');
if (! empty($userName)) {
    $map['u.nickname'] = array(
        'like', 
        "%{$userName}%");
}
$title = I('title', null, 'mysql_real_escape_string');
if (! empty($title)) {
    $map['t.title'] = array(
        'like', 
        "%{$title}%");
}
$batchName = I('batch_name', null, 'mysql_real_escape_string');
if (! empty($batchName)) {
    $map['m.name'] = array(
        'like', 
        "%{$batchName}%");
}
// 处理特殊查询字段
$starTime = I('start_time', null, 'mysql_real_escape_string');
if (! empty($starTime)) {
    $map['b.trans_time'] = array(
        'egt', 
        $starTime . '000000');
}
$endTime = I('end_time', null, 'mysql_real_escape_string');
if (! empty($endTime)) {
    $map['b.trans_time '] = array(
        'elt', 
        $endTime . '235959');
}
import("ORG.Util.Page");
$count = M()->table("tbarcode_trace b")->join(
    'twx_assist_number s ON b.assist_number=s.assist_number')
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

$dataList = M()->table("tbarcode_trace b")->field(
    'b.wx_open_id,u.nickname,t.title,t.card_type,m.*,b.trans_time')
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
    '3' => '优惠券');
// 分页显示
$page = $p->show();
$this->assign("page", $page);
$this->assign('cardType', $cardType);
$this->assign('dataList', $dataList);
$this->display();
}

// 投放数据下载
public function cardSendDown() {
$map = array(
    'b.node_id' => $this->nodeId, 
    'b.status' => '0', 
    'b.trans_type' => '0001', 
    'b.wx_open_id' => array(
        'exp', 
        'is not null'));
$userName = I('user_name', null, 'mysql_real_escape_string');
if (! empty($userName)) {
    $map['u.nickname'] = array(
        'like', 
        "%{$userName}%");
}
$title = I('title', null, 'mysql_real_escape_string');
if (! empty($title)) {
    $map['t.title'] = array(
        'like', 
        "%{$title}%");
}
$batchName = I('batch_name', null, 'mysql_real_escape_string');
if (! empty($batchName)) {
    $map['m.name'] = array(
        'like', 
        "%{$batchName}%");
}
// 处理特殊查询字段
$starTime = I('start_time', null, 'mysql_real_escape_string');
if (! empty($starTime)) {
    $map['b.trans_time'] = array(
        'egt', 
        $starTime . '000000');
}
$endTime = I('end_time', null, 'mysql_real_escape_string');
if (! empty($endTime)) {
    $map['b.trans_time '] = array(
        'elt', 
        $endTime . '235959');
}
$count = M()->table("tbarcode_trace b")->join(
    'twx_assist_number s ON b.assist_number=s.assist_number')
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
    $list = M()->table("tbarcode_trace b")->field(
        "b.wx_open_id,u.nickname,t.title,
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
public function selectCard() {
$map = array(
    'w.node_id' => $this->nodeId, 
    'w.auth_flag' => $this->auth_valid, 
    'g.status' => '0');
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
    ->field(
    'w.*,g.begin_time,g.end_time,g.storage_type,g.storage_num,g.remain_num')
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
    '3' => '优惠券');
$this->assign('list', $list);
$this->assign('cardType', $cardType);
$this->assign("page", $page);
$this->display();
}

// 停用活动
public function cardBatchStatus() {
$id = I('id', null, 'mysql_real_escape_string');
$status = I('status', null, 'mysql_real_escape_string');
$map = array(
    'node_id' => $this->nodeId, 
    'batch_type' => $this->batchType, 
    'id' => $id);
$batchInfo = M('tmarketing_info')->where($map)->find();
if (! $batchInfo)
    $this->error('未找到该活动');
if ($status == '1') {
    $data = array(
        'status' => '2');
} else {
    $data = array(
        'status' => '1');
}

$result = M('tmarketing_info')->where($map)->save($data);
if ($result) {
    node_log('微信投放活动状态更改|活动ID：' . $id);
    $this->success('更新成功');
} else {
    $this->error('系统出错,更新失败');
}
}
}