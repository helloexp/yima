<?php
//
class VoucherAction extends BaseAction {

    public $numGoodsImagePath;
    // 营销品图片存放路径
    public $tempImagePath;
    // 临时图片存放路径
    public $_authAccessMap = '*';

    public function _initialize() {
        parent::_initialize();
        $this->numGoodsImagePath = APP_PATH . 'Upload/NumGoods/' . $this->nodeId;
        $this->tempImagePath = APP_PATH . 'Upload/img_tmp/' . $this->nodeId;
    }

    public function index() {
        $nodename = M('tnode_info')->where("node_id='$this->nodeId'")->getField(
            'node_name');
        import("ORG.Util.Page");
        $goodsname = I('goodsname');
        $begintime = I('badd_time');
        $endtime = I('eadd_time');
        $status = I('status');
        $map = array();
        if ($goodsname != '') {
            $map['goods_name'] = array(
                'like', 
                "%$goodsname%");
        }
        if ($begintime != '' && $endtime == '') {
            $btime = $begintime . '000000';
            $map['add_time'] = array(
                'egt', 
                $btime);
        }
        if ($endtime != '' && $begintime == '') {
            $etime = $endtime . '235959';
            $map['add_time'] = array(
                'elt', 
                $etime);
        }
        if ($begintime != '' && $endtime != '') {
            $btime = $begintime . '000000';
            $etime = $endtime . '235959';
            $map['add_time'] = array(
                array(
                    'egt', 
                    $btime), 
                array(
                    'elt', 
                    $etime));
        }
        if ($status != '') {
            switch ($status) {
                case 0:
                    $map['status'] = 0;
                    break;
                case 1:
                    $map['status'] = 1;
                    break;
                case 2:
                    $map['status'] = 2;
                    break;
            }
        }
        $nodeid = $this->node_id;
        $map['node_id'] = $nodeid;
        $map['goods_type'] = '1';
        $map['source'] = array(
            'eq', 
            '0');
        $count = M()->table("tgoods_info g")->where($map)->count();
        $p = new Page($count, 6);
        // 确保分页查询条件
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        $page = $p->show();
        $arr = M()->table('tgoods_info g')
            ->where($map)
            ->limit($p->firstRow . ',' . $p->listRows)
            ->order('add_time DESC')
            ->select();
        foreach ($arr as $k => $v) {
            $id = M('tgoods_info')->where(
                array(
                    'goods_id' => $v["purchase_goods_id"]))->getfield('node_id');
            $name = M("tnode_info")->where(
                array(
                    "node_id" => $id))->getfield('node_name');
            $arr[$k]['node_name'] = $name;
        }
        $statusClass = array(
            '0' => '正常', 
            '2' => '过期');
        $this->assign('statusClass', $statusClass);
        $this->assign('page', $page);
        $this->assign('nodename', $nodename);
        $this->assign('node_id', $nodeid);
        $this->assign('nodeList', $this->getNodeTree());
        $this->assign('post', $_REQUEST);
        $this->assign('list', $arr);
        $this->display();
    }
    // 采购
    public function purchaseList() {
        $map['b.source'] = array(
            'in', 
            '1,4');
        $map['b.goods_type'] = 1;
        $name = I('name', null, 'mysql_real_escape_string');
        if (! empty($name)) {
            $map['b.goods_name'] = array(
                'like', 
                "%{$name}%");
        }
        // 处理特殊查询字段
        $baddTime = I('badd_time', null, 'mysql_real_escape_string');
        if (! empty($baddTime)) {
            $map['b.add_time'] = array(
                'egt', 
                $baddTime . '000000');
        }
        $eaddTime = I('eadd_time', null, 'mysql_real_escape_string');
        if (! empty($eaddTime)) {
            $map['b.add_time '] = array(
                'elt', 
                $eaddTime . '235959');
        }
        $status = I('status', null, 'mysql_real_escape_string');
        if (isset($status) && $status != '') {
            $map['b.status'] = $status;
        }
        $nodeId = I('node_id', null, 'mysql_real_escape_string');
        if (! empty($nodeId)) {
            $map['b.node_id '] = $nodeId;
        }
        
        $map['b.node_id'] = array(
            'exp', 
            "in ({$this->nodeIn()})");
        import("ORG.Util.Page");
        $count = M()->table("tgoods_info g")->where($map)->count();
        $p = new Page($count, 10);
        // 确保分页查询条件
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        $list = M()->table('tgoods_info b')
            ->field('b.*,n.node_name')
            ->join('tnode_info n ON b.node_id=n.node_id')
            ->where($map)
            ->order('add_time DESC')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        // 分页显示
        $page = $p->show();
        
        $status = array(
            '0' => '正常', 
            '1' => '停用', 
            '2' => '过期');
        node_log("首页+卡券库+发布卡券");
        $this->getNodeTree();
        $this->assign('batchClass', $batchClass);
        $this->assign('status', $status);
        $this->assign('list', $list);
        $this->assign('nodeList', $this->getNodeTree());
        $this->assign('post', $_REQUEST);
        $this->assign("page", $page);
        $this->display();
    }
    
    // 旺财联盟信息列表(别的商户与申请加入盟主的列表)
    public function blocList() {
        $map = array(
            'b.node_id' => array(
                'exp', 
                "in ({$this->nodeIn()})"), 
            'b.goods_type' => '1', 
            'b.source' => '2');
        $name = I('name', null, 'mysql_real_escape_string');
        if (! empty($name)) {
            $map['b.goods_name'] = array(
                'like', 
                "%{$name}%");
        }
        // 处理特殊查询字段
        $baddTime = I('badd_time', null, 'mysql_real_escape_string');
        if (! empty($baddTime)) {
            $map['b.add_time'] = array(
                'egt', 
                $baddTime . '000000');
        }
        $eaddTime = I('eadd_time', null, 'mysql_real_escape_string');
        if (! empty($eaddTime)) {
            $map['b.add_time '] = array(
                'elt', 
                $eaddTime . '235959');
        }
        $checkStatus = I('status', null, 'mysql_real_escape_string');
        if (isset($checkStatus) && $checkStatus != '') {
            $map['b.check_status'] = $checkStatus;
        }
        $nodeId = I('node_id', null, 'mysql_real_escape_string');
        if (! empty($nodeId)) {
            $map['b.node_id '] = $nodeId;
        }
        import("ORG.Util.Page");
        $count = M()->table("tgoods_info g")->where($map)->count();
        $p = new Page($count, 10);
        // 确保分页查询条件
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        $list = M()->table('tgoods_info b')
            ->field(
            'b.*,(SELECT COUNT(*) FROM tbatch_relation r WHERE r.goods_id=b.goods_id AND r.node_id=b.node_id AND r.status=0 AND join_type in(0,2)) as join_num')
            ->where($map)
            ->order('b.add_time desc,join_num desc')
            ->select();
        // 分页显示
        $page = $p->show();
        $checkStatus = array(
            '0' => '正在审核', 
            '1' => '审核通过', 
            '2' => '审核拒绝');
        node_log("首页+异业联盟+旺财联盟列表");
        $this->assign('checkStatus', $checkStatus);
        $this->assign('list', $list);
        $this->assign('nodeList', $this->getNodeTree());
        $this->assign('post', $_REQUEST);
        $this->assign("page", $page);
        $this->display();
    }
    
    // 营销品图片移动
    public function moveImage($imageName) {
        $oldImagePath = $this->tempImagePath;
        $newImagePath = $this->numGoodsImagePath;
        // 图片是否存在
        if (! is_file($oldImagePath . '/' . $imageName)) {
            $this->error('营销品图片未找到');
        }
        if (! is_dir($newImagePath)) {
            if (! mkdir($newImagePath, 0777))
                $this->error('目录创建失败');
        }
        Log::write(
            "picpicpic:" . $oldImagePath . '/' . $imageName . ":::" .
                 $newImagePath . '/' . $imageName);
        $flag = copy($oldImagePath . '/' . $imageName, 
            $newImagePath . '/' . $imageName);
        if (! $flag)
            $this->error('图片移动失败');
        return true;
    }

    public function formatDateNode($startDate, $endDate, $nodeCount = 5, 
        $format = 'Y-m-d') {
        $begin = strtotime($startDate);
        $end = strtotime($endDate);
        $days = floor(($end - $begin) / (24 * 3600));
        $node = $nodeCount - 1; // 日期节点数
        $dateArr = array(
            date($format, $begin));
        if ($days <= $node) {
            // 一天一个节点
            for ($i = 0; $i < $days; $i ++) {
                $begin += 24 * 3600;
                $dateArr[] = date($format, $begin);
            }
        } else {
            $nodeDays = floor($days / $node); // 每个节点之间的天数
            $remainder = $days % $node; // 余数
            for ($i = 0; $i < $node; $i ++) {
                if ($i == $node - 1) {
                    $nodeDays += $remainder;
                }
                $begin += $nodeDays * 24 * 3600;
                $dateArr[] = date($format, $begin);
            }
        }
        return $dateArr;
    }
    // ajax获取营销品类目
    public function ajaxGoodsCate() {
        $id = I('id', null, 'mysql_real_escape_string');
        $cateData = M('tgoods_category')->where("parent_code={$id}")->select();
        if ($cateData) {
            $this->ajaxReturn($cateData, '', 1);
        }
        return null;
    }
    
    // 创建卡券
    public function addVoucher() {
        node_log("首页+创建营销品");
        // 可验证门店数量
        $storeNum = M('tstore_info')->where(
            "node_id IN({$this->nodeIn()}) AND pos_count>0 AND status=0")->count();
        // 商户信息
        $nodeInfo = M('tnode_info')->field(
            'node_name,client_id,node_service_hotline,posgroup_seq')
            ->where("node_id='{$this->nodeId}'")
            ->find();
        // 类型为卡券
        $types = array(
            '1' => '卡券');
        if ($this->isPost()) {
            if ($storeNum == '0')
                $this->error('您还没有创建门店和申请验证核销终端');
            $error = '';
            /* 数据验证 */
            $name = I('post.name');
            if (! check_str($name, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '24'), $error)) {
                $this->error("营销品名称{$error}");
            }
            // 卡券有效期
            $goodsBeginDate = I('post.goods_begin_date');
            if (! check_str($goodsBeginDate, 
                array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd'), $error)) {
                $this->error("营销品有效期开始日期{$error}");
            }
            $goodsEndDate = I('post.goods_end_date');
            if (! check_str($goodsEndDate, 
                array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd'), $error)) {
                $this->error("营销品有效期结束日期{$error}");
            }
            if ($goodsEndDate < date('Ymd')) {
                $this->error('营销品有效期结束日期不能小于当前日期');
            }
            if (strtotime($goodsEndDate) < strtotime($goodsBeginDate)) {
                $this->error('营销品有效期开始日期不能大于营销品有效期结束日期');
            }
            // 卡券核销类型
            $validateType = I('post.validate_type');
            if (! check_str($validateType, 
                array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'minval' => '0', 
                    'maxval' => '1'), $error)) {
                $this->error("卡券核销限制{$error}");
            }
            // 卡券数量
            $numType = I('post.num_type');
            if ($numType == 2) {
                $goodsNum = I('post.goods_num');
                if (! check_str($goodsNum, 
                    array(
                        'null' => false, 
                        'strtype' => 'int', 
                        'minval' => '1'), $error)) {
                    $this->error("营销品数量{$error}");
                }
            } else {
                $goodsNum = '-1';
            }
            // 卡券价格信息
            $data = array();
            $type = 1;
            if ($type == 1) {
                $price = I('post.price');
                if (! check_str($price, 
                    array(
                        'null' => false, 
                        'strtype' => 'number', 
                        'minval' => '0'), $error)) {
                    $this->error("卡券面值信息{$error}");
                }
                $data['goods_amt'] = $price;
            }
            // 卡券市场价格
            $marketPrice = I('post.market_price');
            if (! check_str($marketPrice, 
                array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'minval' => '1'), $error)) {
                $this->error("卡券市场价格{$error}");
            }
            // 类目
            $goodsCate = I('post.cate2');
            if (! check_str($marketPrice, 
                array(
                    'null' => false), $error)) {
                $this->error("类目{$error}");
            }
            // 打印小票
            $printText = I('post.print_text');
            if (! check_str($printText, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '100'), $error)) {
                $this->error("打印小票内容{$error}");
            }
            
            // 门店处理
            $shop = I('post.shop');
            switch ($shop) {
                case 1: // 全门店
                    $groupType = 0;
                    $nodeList = M()->query($this->nodeIn(null, true));
                    $nodeArr = array();
                    foreach ($nodeList as $v) {
                        $nodeArr[] = $v['node_id'];
                    }
                    $dataList = implode(',', $nodeArr);
                    break;
                case 2: // 子门店
                    $groupType = 1;
                    // 获取所有终端列表
                    $shopList = I('post.shop_id', null);
                    if (! is_array($shopList) || empty($shopList))
                        $this->error('请选择验证门店');
                        // $shopstr = implode(',',array_unique($shopList));
                    $where = array(
                        's.store_id' => array(
                            'in', 
                            array_unique($shopList)), 
                        's.node_id' => array(
                            'exp', 
                            "in ({$this->nodeIn()})"), 
                        's.pos_range' => array(
                            'gt', 
                            '0'));
                    // 获取终端号
                    
                    $posData = M()->table('tstore_info s')
                        ->field('p.pos_id,p.store_id,p.node_id')
                        ->join(
                        'tpos_info p ON s.store_id=p.store_id AND s.node_id=p.node_id')
                        ->where($where)
                        ->select();
                    if (! $posData)
                        $this->error('获取门店信息出错');
                    $posArr = array();
                    foreach ($posData as $v) {
                        $posArr[] = $v['pos_id'];
                    }
                    $dataList = implode(',', $posArr);
                    break;
                default:
                    $this->error("请选择营销品可验证门店");
            }
            // 卡券图片
            $goodImage = I('post.img_resp');
            if (! check_str($goodImage, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '100'), $error)) {
                $this->error("请上传卡券图片");
            }
            // 使用时间
            $verifyTimeType = I('post.verify_time_type');
            switch ($verifyTimeType) {
                case 0:
                    $verifyBeginDate = I('post.verify_begin_date');
                    if (! check_str($verifyBeginDate, 
                        array(
                            'null' => false, 
                            'strtype' => 'datetime', 
                            'format' => 'Ymd'), $error)) {
                        $this->error("使用开始时间日期{$error}");
                    }
                    $verifyEndDate = I('post.verify_end_date');
                    if (! check_str($verifyEndDate, 
                        array(
                            'null' => false, 
                            'strtype' => 'datetime', 
                            'format' => 'Ymd'), $error)) {
                        $this->error("使用结束时间日期{$error}");
                    }
                    if (strtotime($verifyEndDate) < strtotime($verifyBeginDate)) {
                        $this->error('使用开始时间日期不能大于使用结束时间日期');
                    }
                    $verifyBeginDate .= '000000';
                    $verifyEndDate .= '235959';
                    break;
                case 1:
                    $verifyBeginDate = I('post.verify_begin_days');
                    if (! check_str($verifyBeginDate, 
                        array(
                            'null' => false, 
                            'strtype' => 'int'), $error)) {
                        $this->error("使用开始天数{$error}");
                    }
                    $verifyEndDate = I('post.verify_end_days');
                    if (! check_str($verifyEndDate, 
                        array(
                            'null' => false, 
                            'strtype' => 'int'), $error)) {
                        $this->error("使用结束天数{$error}");
                    }
                    if ($verifyEndDate < $verifyBeginDate) {
                        $this->error('使用开始天数不能大于使用结束天数');
                    }
                    break;
                default:
                    $this->error('未知的使用时间类型');
            }
            // 发送指引
            $joinRule = I('post.join_rule');
            if (! check_str($joinRule, 
                array(
                    'null' => true, 
                    'maxlen_cn' => '100'), $error)) {
                $this->error("手动发送营销品指引{$error}");
            }
            // 备注
            $goodsDesc = I('post.goods_desc');
            if (! check_str($joinRule, 
                array(
                    'null' => true, 
                    'maxlen_cn' => '200'), $error)) {
                $this->error("卡券描述{$error}");
            }
            // 物料编码
            $materialCode = I('post.material_code');
            if (! check_str($materialCode, 
                array(
                    'null' => true, 
                    'maxlen_cn' => '30'), $error)) {
                $this->error("财务物料编码{$error}");
            }
            // 企业服务热线
            $node_hotline = I('post.node_service_hotline');
            if (! check_str($node_hotline, 
                array(
                    'null' => true, 
                    'maxlen_cn' => '18'), $error)) {
                $this->error("企业服务热线{$error}");
            }
            // 彩信标题内容
            $mmsTitle = I('post.mms_title');
            if (! check_str($mmsTitle, 
                array(
                    'null' => true, 
                    'maxlen_cn' => '10'), $error)) {
                $this->error("彩信标题{$error}");
            }
            // 彩信内容
            $mmsText = I('post.mms_text');
            if (! check_str($mmsText, 
                array(
                    'null' => true, 
                    'maxlen_cn' => '100'), $error)) {
                $this->error("彩信内容{$error}");
            }
            // 营销品图片移动
            $this->moveImage($goodImage);
            $goodsImg = 'NumGoods/' . $this->nodeId . '/' . $goodImage;
            
            // 支撑创建终端组
            M('tnode_info')->where("node_id='{$this->nodeId}'")->setInc(
                'posgroup_seq'); // posgroup_seq
                                 // +1;
            M()->startTrans();
            $req_array = array(
                'CreatePosGroupReq' => array(
                    'NodeId' => $this->nodeId, 
                    'GroupType' => $groupType, 
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
                $this->error('创建门店失败:' . $ret_msg['StatusText']);
            }
            $groupId = $resp_array['CreatePosGroupRes']['GroupID'];
            // 插入终端组信息
            $num = M('tpos_group')->where("group_id='{$groupId}'")->count();
            if ($num == '0') { // 不存在终端组去创建
                $data = array( // tpos_group
                    'node_id' => $this->nodeId, 
                    'group_id' => $groupId, 
                    'group_name' => $req_array['CreatePosGroupReq']['GroupName'], 
                    'group_type' => $groupType, 
                    'status' => '0');
                $result = M('tpos_group')->add($data);
                if (! $result) {
                    M()->rollback();
                    $this->error('终端数据创建失败');
                }
                switch ($groupType) { // tgroup_pos_relation
                    case 0: // 全商户
                        foreach ($nodeList as $v) {
                            $data = array(
                                'group_id' => $groupId, 
                                'node_id' => $v['node_id']);
                            $result = M('tgroup_pos_relation')->add($data);
                            if (! $result) {
                                M()->rollback();
                                $this->error('终端数据创建失败');
                            }
                        }
                        break;
                    case 1: // 终端型
                        foreach ($posData as $v) {
                            $data = array(
                                'group_id' => $groupId, 
                                'node_id' => $v['node_id'], 
                                'store_id' => $v['store_id'], 
                                'pos_id' => $v['pos_id']);
                            $result = M('tgroup_pos_relation')->add($data);
                            if (! $result) {
                                M()->rollback();
                                $this->error('终端数据创建失败');
                            }
                        }
                        break;
                }
            }
            // 创建合约
            $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 流水号
            $req_array = array(
                'CreateTreatyReq' => array(
                    'SystemID' => C('ISS_SYSTEM_ID'), 
                    'RequestSeq' => $TransactionID, 
                    'ShopNodeId' => $this->nodeId,  // 被分销的
                    'BussNodeId' => $this->nodeId,  // 接受方
                    'TreatyName' => $name, 
                    'TreatyShortName' => $name, 
                    'StartTime' => date('YmdHis'), 
                    'EndTime' => '20301231235959', 
                    'GroupId' => $groupId,  // 被分销
                    'GoodsName' => $name, 
                    'GoodsShortName' => $name, 
                    'SalePrice' => empty($data['goods_amt']) ? 0 : $data['goods_amt']));
            $RemoteRequest = D('RemoteRequest', 'Service');
            $resp_array = $RemoteRequest->requestIssForImageco($req_array);
            $ret_msg = $resp_array['CreateTreatyRes']['Status'];
            if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
                 $ret_msg['StatusCode'] != '0001')) {
                M()->rollback();
                log::write("创建合约失败，原因：{$ret_msg['StatusText']}");
                $this->error('创建合约失败:' . $ret_msg['StatusText']);
            }
            $treatyId = $resp_array['CreateTreatyRes']['TreatyId']; // 合约id
                                                                    // 支撑创建活动
            $smilId = $this->getSmil($goodsImg, $name);
            if (! $smilId)
                $this->error('创建失败:smilid获取失败');
                // 创建活动
            $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
                                                                       // 请求参数
            $req_array = array(
                'ActivityCreateReq' => array(
                    'SystemID' => C('ISS_SYSTEM_ID'), 
                    'ISSPID' => $this->nodeId,  // 接受
                    'RelationID' => $this->nodeId,  // 被分销
                    'TransactionID' => $TransactionID, 
                    'SmilID' => $smilId, 
                    'ActivityInfo' => array(
                        'CustomNo' => '', 
                        'ActivityName' => $name, 
                        'ActivityShortName' => $name, 
                        'BeginTime' => date('YmdHis'), 
                        'EndTime' => '20301231235959', 
                        'UseRangeID' => $groupId), 
                    'VerifyMode' => array(
                        'UseTimesLimit' => ! empty($validateType) &&
                             $validateType == 1 ? 0 : 1, 
                            'UseAmtLimit' => ! empty($validateType) &&
                             $validateType == 1 ? 1 : 0), 
                    'GoodsInfo' => array(
                        'pGoodsId' => $treatyId), 
                    'DefaultParam' => array(
                        'PasswordTryTimes' => 3, 
                        'PasswordType' => '', 
                        'PrintText' => $printText)));
            $RemoteRequest = D('RemoteRequest', 'Service');
            $resp_array = $RemoteRequest->requestIssForImageco($req_array);
            $ret_msg = $resp_array['ActivityCreateRes']['Status'];
            if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
                 $ret_msg['StatusCode'] != '0001')) {
                $this->error("活动创建失败:{$ret_msg['StatusText']}");
            }
            $batchNo = $resp_array['ActivityCreateRes']['Info']['ActivityID'];
            
            // tgoods_info数据添加
            $goodsId = get_goods_id();
            $data['goods_id'] = $goodsId;
            $data['batch_no'] = $batchNo;
            $data['goods_name'] = $name;
            $data['goods_desc'] = $goodsDesc;
            $data['goods_image'] = $goodsImg;
            $data['node_id'] = $this->nodeId;
            $data['user_id'] = $this->userId;
            $data['goods_type'] = $type;
            $data['market_price'] = $marketPrice;
            $data['storage_type'] = $numType == 2 ? 1 : 0;
            $data['storage_num'] = $numType == 2 ? $goodsNum : - 1;
            $data['remain_num'] = $numType == 2 ? $goodsNum : - 1;
            $data['mms_title'] = $mmsTitle;
            $data['mms_text'] = $mmsText;
            $data['print_text'] = $printText;
            $data['begin_time'] = $goodsBeginDate . '000000';
            $data['end_time'] = $goodsEndDate . '235959';
            $data['verify_begin_date'] = $verifyBeginDate;
            $data['verify_end_date'] = $verifyEndDate;
            $data['verify_begin_type'] = $verifyTimeType;
            $data['verify_end_type'] = $verifyTimeType;
            $data['join_rule'] = $joinRule;
            $data['customer_no'] = $materialCode;
            $data['node_service_hotline'] = $node_hotline;
            $data['add_time'] = date('YmdHis');
            $data['goods_cat'] = $goodsCate;
            $data['p_goods_id'] = $treatyId;
            $data['pos_group'] = $groupId;
            $data['pos_group_type'] = $shop;
            $data['validate_type'] = $validateType;
            $id = M('tgoods_info')->data($data)->add();
            if ($id) {
                // 自动发布到展示大厅
                $isPublish = I('is_publish');
                if (in_array($this->wc_version, C('NODE_PUBLISH_TYPE')) &&
                     $isPublish == '1') {
                    // thall_goods数据添加
                    $data = array(
                        'batch_short_name' => $name, 
                        'batch_name' => $name, 
                        'node_id' => $this->nodeId, 
                        'user_id' => $this->userId, 
                        'use_rule' => $goodsDesc, 
                        'batch_img' => 'NumGoods/' . $this->nodeId . '/' .
                         $goodImage, 
                        'batch_amt' => $marketPrice, 
                        'begin_time' => date('Ymd000000'), 
                        'end_time' => $goodsEndDate . '235959', 
                        'add_time' => date('YmdHis'), 
                        'node_pos_group' => $groupId, 
                        'node_pos_type' => $shop, 
                        'batch_desc' => $goodsDesc, 
                        'goods_id' => $goodsId);
                    $batchId = M('thall_goods')->add($data);
                    if (! $batchId) {
                        M()->rollback();
                        $this->error('数据库错误,发布失败');
                    }
                }
                M()->commit();
                node_log("创建营销品，类型：" . $types[$type] . "，名称：" . $name);
                $this->success('创建成功!');
                exit();
            } else {
                M()->rollback();
                $this->error('创建失败!');
            }
            exit();
        }
        $goodsCate = M('tgoods_category')->where("level=1")->select();
        
        $this->assign('goodsCate', $goodsCate);
        $this->assign('type', $types);
        $this->assign('storeNum', $storeNum);
        $this->assign('nodeTyoe', $this->node_type_name);
        $this->display();
    }
    
    // 营销品编辑
    public function voucherEdit() {
        $goodsId = I('goods_id', 'mysql_real_escape_string');
        $goodsData = M('tgoods_info')->where(
            "node_id='{$this->nodeId}' AND goods_id='{$goodsId}'")->find();
        if (! $goodsData)
            $this->error('未找到该商品');
        if ($this->isPost()) {
            // 营销品有效期(只能延长)
            $goodsEndDate = I('post.goods_end_date');
            if (! check_str($goodsEndDate, 
                array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd'), $error)) {
                $this->error("卡券有效期结束日期{$error}");
            } elseif ($goodsEndDate < date('Ymd')) {
                $this->error('卡券有效期结束日期不能小于当前日期');
            } elseif (strtotime($goodsEndDate) <
                 strtotime($goodsData['begin_time'])) {
                $this->error('卡券有效期开始日期不能大于卡券有效期结束日期');
            } elseif (strtotime($goodsEndDate . '235959') <
                 strtotime($goodsData['end_time'])) {
                $this->error('有效期结束时间不能小于修改之前的结束时间');
            }
            // 打印小票
            $printText = I('post.print_text');
            if (! check_str($printText, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '100'), $error)) {
                $this->error("打印小票内容{$error}");
            }
            // 使用时间
            $verifyTimeType = I('post.verify_time_type');
            switch ($verifyTimeType) {
                case 0:
                    $verifyBeginDate = I('post.verify_begin_date');
                    if (! check_str($verifyBeginDate, 
                        array(
                            'null' => false, 
                            'strtype' => 'datetime', 
                            'format' => 'Ymd'), $error)) {
                        $this->error("使用开始时间日期{$error}");
                    }
                    $verifyEndDate = I('post.verify_end_date');
                    if (! check_str($verifyEndDate, 
                        array(
                            'null' => false, 
                            'strtype' => 'datetime', 
                            'format' => 'Ymd'), $error)) {
                        $this->error("使用结束时间日期{$error}");
                    }
                    if (strtotime($verifyEndDate) < strtotime($verifyBeginDate)) {
                        $this->error('使用开始时间日期不能大于使用结束时间日期');
                    }
                    $verifyBeginDate .= '000000';
                    $verifyEndDate .= '235959';
                    break;
                case 1:
                    $verifyBeginDate = I('post.verify_begin_days');
                    if (! check_str($verifyBeginDate, 
                        array(
                            'null' => false, 
                            'strtype' => 'int'), $error)) {
                        $this->error("使用开始天数{$error}");
                    }
                    $verifyEndDate = I('post.verify_end_days');
                    if (! check_str($verifyEndDate, 
                        array(
                            'null' => false, 
                            'strtype' => 'int'), $error)) {
                        $this->error("使用结束天数{$error}");
                    }
                    if ($verifyEndDate < $verifyBeginDate) {
                        $this->error('使用开始天数不能大于使用结束天数');
                    }
                    break;
                default:
                    $this->error('未知的使用时间类型');
            }
            // 彩信标题内容
            $mmsTitle = I('post.mms_title');
            if (! check_str($mmsTitle, 
                array(
                    'null' => true, 
                    'maxlen_cn' => '10'), $error)) {
                $this->error("彩信标题{$error}");
            }
            // 彩信内容
            $mmsText = I('post.mms_text');
            if (! check_str($mmsText, 
                array(
                    'null' => true, 
                    'maxlen_cn' => '100'), $error)) {
                $this->error("彩信内容{$error}");
            }
            // 支撑电子合约修改
            // if(!empty($goodsData['p_goods_id']) &&
            // strcmp($goodsData['add_time'], $printText) !== 0){
            // $result = $this->setGoodsInfoReq($goodsData['p_goods_id'],
            // $printText, $goodsData['add_time']);
            // if(!$result) $this->error('旺财电子合约号更新失败');
            // }
            $data = array(
                'mms_title' => $mmsTitle, 
                'mms_text' => $mmsText, 
                'print_text' => $printText, 
                'begin_time' => $goodsData['begin_time'], 
                'end_time' => $goodsEndDate . '235959', 
                'status' => '0', 
                'verify_begin_date' => $verifyBeginDate, 
                'verify_end_date' => $verifyEndDate, 
                'verify_begin_type' => $verifyTimeType, 
                'verify_end_type' => $verifyTimeType);
            $result = M('tgoods_info')->where("goods_id='{$goodsId}'")->save(
                $data);
            if ($result === false) {
                $this->error('数据出错,更新失败');
            }
            node_log("卡券编辑，名称：" . $goodsData['goods_name']);
            $this->success('卡券编辑成功');
            exit();
        }
        $this->assign('goodsData', $goodsData);
        $this->display();
    }
    
    // 卡券添加
    public function addNumGoods() {
        node_log("首页+创建卡券");
        // 可验证门店数量
        $storeNum = M('tstore_info')->where(
            "node_id IN({$this->nodeIn()}) AND pos_count>0 AND status=0")->count();
        // 商户信息
        $nodeInfo = M('tnode_info')->field(
            'node_name,client_id,node_service_hotline,posgroup_seq')
            ->where("node_id='{$this->nodeId}'")
            ->find();
        // 卡券可类型
        $types = array(
            '0' => '优惠券', 
            '1' => '卡券', 
            '2' => '提领券');
        if ($this->isPost()) {
            if ($storeNum == '0')
                $this->error('您还没有创建门店和申请验证核销终端');
            $error = '';
            /* 数据验证 */
            $name = I('post.name');
            if (! check_str($name, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '24'), $error)) {
                $this->error("卡券名称{$error}");
            }
            // 卡券有效期
            $goodsBeginDate = I('post.goods_begin_date');
            if (! check_str($goodsBeginDate, 
                array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd'), $error)) {
                $this->error("卡券有效期开始日期{$error}");
            }
            $goodsEndDate = I('post.goods_end_date');
            if (! check_str($goodsEndDate, 
                array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd'), $error)) {
                $this->error("卡券有效期结束日期{$error}");
            }
            if ($goodsEndDate < date('Ymd')) {
                $this->error('卡券有效期结束日期不能小于当前日期');
            }
            if (strtotime($goodsEndDate) < strtotime($goodsBeginDate)) {
                $this->error('卡券有效期开始日期不能大于卡券有效期结束日期');
            }
            // 卡券数量
            $numType = I('post.num_type');
            if ($numType == 2) {
                $goodsNum = I('post.goods_num');
                if (! check_str($goodsNum, 
                    array(
                        'null' => false, 
                        'strtype' => 'int', 
                        'minval' => '1'), $error)) {
                    $this->error("卡券数量{$error}");
                }
            } else {
                $goodsNum = '-1';
            }
            // 卡券价格信息
            $data = array();
            $type = 1;
            $price = I('post.price');
            if (! check_str($price, 
                array(
                    'null' => false, 
                    'strtype' => 'number', 
                    'minval' => '0'), $error)) {
                $this->error("卡券面值信息{$error}");
            }
            $data['goods_amt'] = $price;
            $validateType = I('post.validate_type');
            if (! check_str($validateType, 
                array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'minval' => '0', 
                    'maxval' => '1'), $error)) {
                $this->error("卡券核销限制{$error}");
            }
            $data['validate_type'] = $validateType;
            // 卡券市场价
            $marketPrice = I('post.market_price');
            if (! check_str($marketPrice, 
                array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'minval' => '1'), $error)) {
                $this->error("卡券市场价格{$error}");
            }
            // 类目
            $goodsCate = I('post.cate2');
            if (! check_str($marketPrice, 
                array(
                    'null' => false), $error)) {
                $this->error("类目{$error}");
            }
            // 打印小票
            $printText = I('post.print_text');
            if (! check_str($printText, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '100'), $error)) {
                $this->error("打印小票内容{$error}");
            }
            // 门店处理
            $shop = I('post.shop');
            switch ($shop) {
                case 1: // 全门店
                    $groupType = 0;
                    $nodeList = M()->query($this->nodeIn(null, true));
                    $nodeArr = array();
                    foreach ($nodeList as $v) {
                        $nodeArr[] = $v['node_id'];
                    }
                    $dataList = implode(',', $nodeArr);
                    break;
                case 2: // 子门店
                    $groupType = 1;
                    // 获取所有终端列表
                    $shopList = I('post.shop_id', null);
                    if (! is_array($shopList) || empty($shopList))
                        $this->error('请选择验证门店');
                        // $shopstr = implode(',',array_unique($shopList));
                    $where = array(
                        's.store_id' => array(
                            'in', 
                            array_unique($shopList)), 
                        's.node_id' => array(
                            'exp', 
                            "in ({$this->nodeIn()})"), 
                        's.pos_range' => array(
                            'gt', 
                            '0'));
                    // 获取终端号
                    $posData = M()->table('tstore_info s')
                        ->field('p.pos_id,p.store_id,p.node_id')
                        ->join(
                        'tpos_info p ON s.store_id=p.store_id AND s.node_id=p.node_id')
                        ->where($where)
                        ->select();
                    if (! $posData)
                        $this->error('获取门店信息出错');
                    $posArr = array();
                    foreach ($posData as $v) {
                        $posArr[] = $v['pos_id'];
                    }
                    $dataList = implode(',', $posArr);
                    break;
                default:
                    $this->error("请选择卡券可验证门店");
            }
            // 卡券图片
            $goodImage = I('post.img_resp');
            if (! check_str($goodImage, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '100'), $error)) {
                $this->error("请上传卡券图片");
            }
            // 使用时间
            $verifyTimeType = I('post.verify_time_type');
            switch ($verifyTimeType) {
                case 0:
                    $verifyBeginDate = I('post.verify_begin_date');
                    if (! check_str($verifyBeginDate, 
                        array(
                            'null' => false, 
                            'strtype' => 'datetime', 
                            'format' => 'Ymd'), $error)) {
                        $this->error("使用开始时间日期{$error}");
                    }
                    $verifyEndDate = I('post.verify_end_date');
                    if (! check_str($verifyEndDate, 
                        array(
                            'null' => false, 
                            'strtype' => 'datetime', 
                            'format' => 'Ymd'), $error)) {
                        $this->error("使用结束时间日期{$error}");
                    }
                    if (strtotime($verifyEndDate) < strtotime($verifyBeginDate)) {
                        $this->error('使用开始时间日期不能大于使用结束时间日期');
                    }
                    $verifyBeginDate .= '000000';
                    $verifyEndDate .= '235959';
                    break;
                case 1:
                    $verifyBeginDate = I('post.verify_begin_days');
                    if (! check_str($verifyBeginDate, 
                        array(
                            'null' => false, 
                            'strtype' => 'int'), $error)) {
                        $this->error("使用开始天数{$error}");
                    }
                    $verifyEndDate = I('post.verify_end_days');
                    if (! check_str($verifyEndDate, 
                        array(
                            'null' => false, 
                            'strtype' => 'int'), $error)) {
                        $this->error("使用结束天数{$error}");
                    }
                    if ($verifyEndDate < $verifyBeginDate) {
                        $this->error('使用开始天数不能大于使用结束天数');
                    }
                    break;
                default:
                    $this->error('未知的使用时间类型');
            }
            /*
             * $verifyData = $this->checkVerifyDate();
             * if(strtotime($verifyData['begin_time']) <
             * strtotime($goodsBeginDate) || strtotime($verifyData['end_time'])
             * > strtotime($goodsEndDate.'235959')){
             * $this->error('卡券发送和使用时间必须在卡券有效期范围内'); }
             */
            // 发送指引
            $joinRule = I('post.join_rule');
            if (! check_str($joinRule, 
                array(
                    'null' => true, 
                    'maxlen_cn' => '100'), $error)) {
                $this->error("手工发送卡券指引{$error}");
            }
            // 卡券描述
            $goodsDesc = I('post.goods_desc');
            if (! check_str($joinRule, 
                array(
                    'null' => true, 
                    'maxlen_cn' => '200'), $error)) {
                $this->error("卡券描述{$error}");
            }
            // 物料编码
            $materialCode = I('post.material_code');
            if (! check_str($materialCode, 
                array(
                    'null' => true, 
                    'maxlen_cn' => '30'), $error)) {
                $this->error("财务物料编码{$error}");
            }
            // 企业服务热线
            $node_hotline = I('post.node_service_hotline');
            if (! check_str($node_hotline, 
                array(
                    'null' => true, 
                    'maxlen_cn' => '18'), $error)) {
                $this->error("企业服务热线{$error}");
            }
            // 彩信标题内容
            $mmsTitle = I('post.mms_title');
            if (! check_str($mmsTitle, 
                array(
                    'null' => true, 
                    'maxlen_cn' => '10'), $error)) {
                $this->error("彩信标题{$error}");
            }
            // 彩信内容
            $mmsText = I('post.mms_text');
            if (! check_str($mmsText, 
                array(
                    'null' => true, 
                    'maxlen_cn' => '100'), $error)) {
                $this->error("彩信内容{$error}");
            }
            // 短信内容
            // $smsTtext = I('post.sms_text');
            // if(!check_str($smsTtext,array('null'=>false,'maxlen_cn'=>'100'),$error)){
            // $this->error("短信内容{$error}");
            // }
            // 卡券图片移动
            $this->moveImage($goodImage);
            $goodsImg = 'NumGoods/' . $this->nodeId . '/' . $goodImage;
            // 支撑创建终端组
            M('tnode_info')->where("node_id='{$this->nodeId}'")->setInc(
                'posgroup_seq'); // posgroup_seq
                                 // +1;
            M()->startTrans();
            $req_array = array(
                'CreatePosGroupReq' => array(
                    'NodeId' => $this->nodeId, 
                    'GroupType' => $groupType, 
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
                $this->error('创建门店失败:' . $ret_msg['StatusText']);
            }
            $groupId = $resp_array['CreatePosGroupRes']['GroupID'];
            // 插入终端组信息
            $num = M('tpos_group')->where("group_id='{$groupId}'")->count();
            if ($num == '0') { // 不存在终端组去创建
                $data = array( // tpos_group
                    'node_id' => $this->nodeId, 
                    'group_id' => $groupId, 
                    'group_name' => $req_array['CreatePosGroupReq']['GroupName'], 
                    'group_type' => $groupType, 
                    'status' => '0');
                $result = M('tpos_group')->add($data);
                if (! $result) {
                    M()->rollback();
                    $this->error('终端数据创建失败');
                }
                switch ($groupType) { // tgroup_pos_relation
                    case 0: // 全商户
                        foreach ($nodeList as $v) {
                            $data = array(
                                'group_id' => $groupId, 
                                'node_id' => $v['node_id']);
                            $result = M('tgroup_pos_relation')->add($data);
                            if (! $result) {
                                M()->rollback();
                                $this->error('终端数据创建失败');
                            }
                        }
                        break;
                    case 1: // 终端型
                        foreach ($posData as $v) {
                            $data = array(
                                'group_id' => $groupId, 
                                'node_id' => $v['node_id'], 
                                'store_id' => $v['store_id'], 
                                'pos_id' => $v['pos_id']);
                            $result = M('tgroup_pos_relation')->add($data);
                            if (! $result) {
                                M()->rollback();
                                $this->error('终端数据创建失败');
                            }
                        }
                        break;
                }
            }
            // 创建合约
            $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 流水号
            $req_array = array(
                'CreateTreatyReq' => array(
                    'SystemID' => C('ISS_SYSTEM_ID'), 
                    'RequestSeq' => $TransactionID, 
                    'ShopNodeId' => $this->nodeId, 
                    'BussNodeId' => $this->nodeId, 
                    'TreatyName' => $name, 
                    'TreatyShortName' => $name, 
                    'StartTime' => date('YmdHis'), 
                    'EndTime' => '20301231235959', 
                    'GroupId' => $groupId, 
                    'GoodsName' => $name, 
                    'GoodsShortName' => $name));
            $RemoteRequest = D('RemoteRequest', 'Service');
            $resp_array = $RemoteRequest->requestIssForImageco($req_array);
            $ret_msg = $resp_array['CreateTreatyRes']['Status'];
            if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
                 $ret_msg['StatusCode'] != '0001')) {
                M()->rollback();
                log::write("创建合约失败，原因：{$ret_msg['StatusText']}");
                $this->error('创建合约失败:' . $ret_msg['StatusText']);
            }
            $treatyId = $resp_array['CreateTreatyRes']['TreatyId']; // 合约id
                                                                    // 支撑创建活动
            $smilId = $this->getSmil($goodsImg, $name);
            if (! $smilId)
                $this->error('创建失败:smilid获取失败');
                // 创建活动
            $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
                                                                       // 请求参数
            $req_array = array(
                'ActivityCreateReq' => array(
                    'SystemID' => C('ISS_SYSTEM_ID'), 
                    'ISSPID' => $this->nodeId, 
                    'RelationID' => $this->nodeId, 
                    'TransactionID' => $TransactionID, 
                    'SmilID' => $smilId, 
                    'ActivityInfo' => array(
                        'CustomNo' => '', 
                        'ActivityName' => $name, 
                        'ActivityShortName' => $name, 
                        'BeginTime' => date('YmdHis'), 
                        'EndTime' => '20301231235959', 
                        'UseRangeID' => $groupId), 
                    'VerifyMode' => array(
                        'UseTimesLimit' => ! empty($validateType) &&
                             $validateType == 1 ? 0 : 1, 
                            'UseAmtLimit' => ! empty($validateType) &&
                             $validateType == 1 ? 1 : 0), 
                    'GoodsInfo' => array(
                        'pGoodsId' => $treatyId), 
                    'DefaultParam' => array(
                        'PasswordTryTimes' => 3, 
                        'PasswordType' => '', 
                        'PrintText' => $printText)));
            $RemoteRequest = D('RemoteRequest', 'Service');
            $resp_array = $RemoteRequest->requestIssForImageco($req_array);
            $ret_msg = $resp_array['ActivityCreateRes']['Status'];
            if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
                 $ret_msg['StatusCode'] != '0001')) {
                $this->error("活动创建失败:{$ret_msg['StatusText']}");
            }
            $batchNo = $resp_array['ActivityCreateRes']['Info']['ActivityID'];
            
            // tgoods_info数据添加
            $goodsId = get_goods_id();
            $data['goods_id'] = $goodsId;
            $data['batch_no'] = $batchNo;
            $data['goods_name'] = $name;
            $data['goods_desc'] = $goodsDesc;
            $data['goods_image'] = $goodsImg;
            $data['node_id'] = $this->nodeId;
            $data['user_id'] = $this->userId;
            $data['goods_type'] = $type;
            $data['market_price'] = $marketPrice;
            $data['storage_type'] = $numType == 2 ? 1 : 0;
            $data['storage_num'] = $numType == 2 ? $goodsNum : - 1;
            $data['remain_num'] = $numType == 2 ? $goodsNum : - 1;
            $data['mms_title'] = $mmsTitle;
            $data['mms_text'] = $mmsText;
            $data['print_text'] = $printText;
            $data['begin_time'] = $goodsBeginDate . '000000';
            $data['end_time'] = $goodsEndDate . '235959';
            $data['verify_begin_date'] = $verifyBeginDate;
            $data['verify_end_date'] = $verifyEndDate;
            $data['verify_begin_type'] = $verifyTimeType;
            $data['verify_end_type'] = $verifyTimeType;
            $data['join_rule'] = $joinRule;
            $data['customer_no'] = $materialCode;
            $data['node_service_hotline'] = $node_hotline;
            $data['add_time'] = date('YmdHis');
            $data['goods_cat'] = $goodsCate;
            $data['p_goods_id'] = $treatyId;
            $data['pos_group'] = $groupId;
            $data['pos_group_type'] = $shop;
            
            $id = M('tgoods_info')->data($data)->add();
            if ($id) {
                // 自动发布到展示大厅
                $isPublish = I('is_publish');
                if (in_array($this->wc_version, C('NODE_PUBLISH_TYPE')) &&
                     $isPublish == '1') {
                    // thall_goods数据添加
                    $data = array(
                        'batch_short_name' => $name, 
                        'batch_name' => $name, 
                        'node_id' => $this->nodeId, 
                        'user_id' => $this->userId, 
                        'use_rule' => $goodsDesc, 
                        'batch_img' => 'NumGoods/' . $this->nodeId . '/' .
                         $goodImage, 
                        'batch_amt' => $marketPrice, 
                        'begin_time' => date('Ymd000000'), 
                        'end_time' => $goodsEndDate . '235959', 
                        'add_time' => date('YmdHis'), 
                        'node_pos_group' => $groupId, 
                        'node_pos_type' => $shop, 
                        'batch_desc' => $goodsDesc, 
                        'goods_id' => $goodsId);
                    $batchId = M('thall_goods')->add($data);
                    if (! $batchId) {
                        M()->rollback();
                        $this->error('数据库错误,发布失败');
                    }
                }
                M()->commit();
                node_log("创建卡券，类型：" . $types[$type] . "，名称：" . $name);
                $this->success('创建成功');
                exit();
            } else {
                M()->rollback();
                $this->error('创建失败');
            }
            exit();
        }
        $goodsCate = M('tgoods_category')->where("level=1")->select();
        $this->assign('goodsCate', $goodsCate);
        $this->assign('type', $types);
        $this->assign('storeNum', $storeNum);
        $this->assign('nodeTyoe', $this->node_type_name); // 商户类型
        $this->display();
    }

    /*
     * 下载
     */
    public function export() {
        // 查询条件组合
        $where = "WHERE 1";
        if (! empty($_POST)) {
            $filter = array();
            $condition = array_map('trim', $_POST);
            if (isset($condition['batch_class']) &&
                 $condition['batch_class'] != '') {
                $filter[] = "i.goods_type = '{$condition['batch_class']}'";
            } else {
                $filter[] = "i.goods_type IN(0,1,2,3)";
            }
            if (isset($condition['status']) && $condition['status'] != '') {
                $filter[] = "i.status = '{$condition['status']}'";
            }
            if (isset($condition['badd_time']) && $condition['badd_time'] != '') {
                $condition['badd_time'] = $condition['badd_time'] . ' 000000';
                $filter[] = "i.add_time >= '{$condition['badd_time']}'";
            }
            if (isset($condition['eadd_time']) && $condition['eadd_time'] != '') {
                $condition['eadd_time'] = $condition['eadd_time'] . ' 235959';
                $filter[] = "i.add_time <= '{$condition['eadd_time']}'";
            }
            if (! empty($condition['node_id'])) {
                $filter['i.node_id '] = $condition['node_id'];
            }
        }
        if (! empty($filter)) {
            $condition = implode(' AND ', $filter);
            $where .= ' AND ' . $condition;
        }
        $sql = "SELECT
    		i.goods_name,i.add_time,i.begin_time,i.end_time,
    		CASE i.goods_type WHEN '0' THEN '优惠券' WHEN '1' THEN '卡券' ELSE '提领券' END goods_type,
    		CASE i.status WHEN '0' THEN '正常' WHEN '1' THEN '停用' ELSE '过期' END status
    		FROM
    		tgoods_info i {$where} AND i.source=0 AND i.node_id in (" .
             $this->nodeIn() . ")";
        $cols_arr = array(
            'goods_name' => '卡券名称', 
            'add_time' => '添加时间', 
            'begin_time' => '活动开始时间', 
            'end_time' => '活动结束时间', 
            'goods_type' => '类型', 
            'status' => '状态');
        if (querydata_download($sql, $cols_arr, M()) == false) {
            $this->error('下载失败');
        }
    }
    
    // 盟主卡券详情
    public function blocDetail() {
        $goodsId = I('goods_id', 'mysql_real_escape_string');
        $map = array(
            'goods_id' => $goodsId, 
            'goods_type' => '1', 
            'source' => '2', 
            'node_id' => $this->nodeId);
        $list = M()->table('tgoods_info')
            ->where($map)
            ->find();
        if (! $list)
            $this->error('未找到该联盟信息');
            // 查找城市
        $citylist = M()->table('tcity_code t')
            ->field('GROUP_CONCAT(t.city) as city')
            ->where(
            "t.city_level='2' and t.city_code in(SELECT city_code FROM tgoods_bloc_city WHERE goods_id='{$list['goods_id']}')")
            ->find();
        $list['city_str'] = $citylist['city'];
        // 时间计算
        $now = date('Y-m-d');
        $end_time = $list['recruit_end_date'];
        $day = floor((strtotime($end_time) - strtotime($now)) / 86400);
        if ($day < 0) {
            $day = "已结束";
        } else {
            $day = $day . "天";
        }
        $list['day'] = $day;
        
        import("ORG.Util.Page");
        // 申请列表
        $joinCount = M('tbatch_relation')->where(
            "goods_id='{$goodsId}' AND node_id='{$this->nodeId}' AND join_type=0")->count();
        $joinP = new Page($joinCount, 10);
        // 获得该卡券的加盟商户信息
        $joinList = M()->table('tbatch_relation r')
            ->field('r.*,n.node_name')
            ->join("tnode_info n ON r.req_node_id=n.node_id")
            ->where(
            "r.goods_id='{$goodsId}' AND r.node_id='{$this->nodeId}' AND r.join_type=0")
            ->order('add_time DESC,status')
            ->limit($joinP->firstRow . ',' . $joinP->listRows)
            ->select();
        // 邀请列表
        C('VAR_PAGE', 'yp');
        $yCount = M('tbatch_relation')->where(
            "goods_id='{$goodsId}' AND node_id='{$this->nodeId}' AND join_type=2")->count();
        $yp = new Page($yCount, 10);
        // 获得该卡券的加盟商户信息
        $yList = M()->table('tbatch_relation r')
            ->field('r.*,n.node_name')
            ->join("tnode_info n ON r.req_node_id=n.node_id")
            ->where(
            "r.goods_id='{$goodsId}' AND r.node_id='{$this->nodeId}' AND r.join_type=2")
            ->order('add_time DESC,status')
            ->limit($yp->firstRow . ',' . $yp->listRows)
            ->select();
        // 分页显示
        $joinPage = $joinP->show();
        $yPage = $yp->show();
        $this->assign('list', $list);
        $this->assign('joinList', $joinList);
        $this->assign('yList', $yList);
        $this->assign('joinPage', $joinPage);
        $this->assign('yPage', $yPage);
        $this->display();
    }

    public function storageTrace() {
        $id = I('goods_id', '', 'trim,intval');
        $map = array(
            'goods_id' => $id, 
            'node_id' => $this->nodeId);
        
        $count = M('tgoods_storage_trace')->where($map)->count();
        
        import('ORG.Util.Page'); // 导入分页类
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show();
        
        $queryList = (array) M('tgoods_storage_trace')->where($map)
            ->order('add_time desc')
            ->limit($Page->firstRow, $Page->listRows)
            ->select();
        
        $this->assign('queryList', $queryList);
        $this->assign('page', $show);
        $this->assign('opt_type_arr', 
            array(
                '0' => '营销活动调用', 
                '1' => '活动释放', 
                '2' => '商品下架', 
                '3' => '库存增加', 
                '4' => '卡券采购', 
                '5' => '采购需求主动供货', 
                '6' => '奖品增加库存', 
                '7' => '奖品扣减库存', 
                '8' => '礼品派发（批量）', 
                '10' => '礼品派发(单条)', 
                '11' => '小店商品上架', 
                '12' => '小店商品库存调整', 
                '9' => '粉丝回馈'));
        $this->display();
    }

    /**
     * 库存变动流水详情
     */
    public function storageTraceView() {
        $id = I('id', '', 'trim,intval');
        $map = array(
            'id' => $id, 
            'node_id' => $this->nodeId);
        
        $traceInfo = M('tgoods_storage_trace')->where($map)->find();
        if (! $traceInfo)
            $this->error('参数错误！');
        
        $this->assign('traceInfo', $traceInfo);
        $relation_id = $traceInfo['relation_id'];
        
        switch ($traceInfo['opt_type']) {
            case 0:
            case 6:
            case 7:
                // 营销活动调用
                $cjBatchInfo = M('tcj_batch')->where("id = '{$relation_id}'")->find();
                $marketingInfo = M('tmarketing_info')->where(
                    "id = '{$cjBatchInfo['batch_id']}'")->find();
                $batchInfo = M('tbatch_info')->where(
                    "batch_no = '{$cjBatchInfo['activity_no']}'")->find();
                
                $this->assign('cjBatchInfo', $cjBatchInfo);
                $this->assign('marketingInfo', $marketingInfo);
                $this->assign('batchInfo', $batchInfo);
                break;
            case 1:
                // 活动释放
                break;
            case 2:
                // 商品下架
                break;
            case 3:
                // 库存增加
                break;
            case 4:
                // 卡券采购
                $this->redirect('Hall/Admin/GoodsBookView', 
                    array(
                        'id' => $relation_id));
                break;
            case 5:
                // 供货
                $this->redirect('Hall/Admin/GoodsSupplyView', 
                    array(
                        'id' => $relation_id));
                break;
            case 8:
                // 礼品派发（批量）
                $sendInfo = M('tbatch_import')->where(
                    "batch_id = '{$relation_id}'")->find();
                $batchInfo = M('tbatch_info')->where(
                    "id = '{$sendInfo['b_id']}'")->find();
                $marketingInfo = M('tmarketing_info')->where(
                    "id = '{$batchInfo['m_id']}'")->find();
                $this->assign('sendInfo', $sendInfo);
                $this->assign('marketingInfo', $marketingInfo);
                break;
            case 10:
                // 礼品派发（单条）
                
                break;
            case 9:
                // 粉丝回馈
                $sendInfo = M('tbatch_import')->where(
                    "batch_id = '{$relation_id}'")->find();
                $this->assign('sendInfo', $sendInfo);
                break;
            default:
                // code...
                break;
        }
        $this->assign('traceInfo', $traceInfo);
        $this->assign('opt_type_arr', 
            array(
                '0' => '营销活动调用', 
                '1' => '活动释放', 
                '2' => '商品下架', 
                '3' => '库存增加', 
                '4' => '卡券采购', 
                '5' => '采购需求主动供货', 
                '6' => '奖品增加库存', 
                '7' => '奖品扣减库存', 
                '8' => '礼品派发(批量)', 
                '10' => '礼品派发(单条)', 
                '9' => '粉丝回馈'));
        $this->display();
    }
    
    // 获取smil $type 1:爱拍 2:旺财
    public function getSmil($imageName, $name, $smilId = "", $node_id = null) {
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
            log::write('获取SmilId接口图片文件不存在' . $imageUrl);
            return false;
        }
        // 缩放图片大小要小于60k
        import('ORG.Util.Image');
        $smilUrl = Image::thumb($imageUrl, 
            dirname($imageUrl) . '/smi_' . basename($imageUrl), '', 150, 150, 
            true);
        if (! $smilUrl) {
            log::write('SmilId接口图片压缩失败');
            return false;
        }
        $imageUrl = $smilUrl;
        $smil_cfg = create_smil_cfg($imageUrl);
        if ($smil_cfg === false) {
            log::write('创建smil_cfg失败');
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
                'ISSPID' => $node_id === null ? $this->nodeId : $node_id, 
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
            log::write("获取SmilId失败 原因：{$ret_msg['StatusText']}");
            return false;
        }
        return $resp_array['SmilAddEditRes']['SmilId'];
    }
    
    // 获取单个活动的验码,发码,撤销量(只有粉丝卡再用这个,营销品要用的话参数要改造成goods_id)
    public function getBatchCodeNum($batch_no) {
        $infoArr = array();
        // 发码量
        $where = array(
            'batch_no' => $batch_no, 
            'trans_type' => '0001', 
            'status' => '0');
        $sendNum = M('tbarcode_trace')->where($where)->count();
        $infoArr['send_num'] = $sendNum;
        // 验码量
        $where = array(
            'batch_no' => $batch_no, 
            'trans_type' => '0', 
            // 'is_canceled' => '0',
            'status' => '0');
        $verifyNum = M('tpos_trace')->where($where)->count();
        $infoArr['verifyNum'] = $verifyNum;
        // 撤销量
        $where = array(
            'batch_no' => $batch_no, 
            'trans_type' => '1', 
            // 'is_canceled' => '0',
            'status' => '0');
        $recNum = M('tpos_trace')->where($where)->count();
        $infoArr['recNum'] = $recNum;
        return $infoArr;
    }
    
    // 卡券详情
    public function voucherDetail() {
        $goodsId = I('get.goods_id', null);
        if (is_null($goodsId)) {
            $this->error('参数错误');
        }
        $goodsInfo = M('tgoods_info')->where(
            "goods_id='{$goodsId}' AND node_id IN({$this->nodeIn()})")->find();
        if (! $goodsInfo)
            $this->error('未找到该营销品信息');
        $codeInfo = $this->getBatchCodeNum($batchInfo['batch_no']);
        $batchInfo = array_merge($batchInfo, $codeInfo);
        // 获取发布信息
        $publishData = M('thall_goods')->where("goods_id='{$goodsId}'")->select();
        $status = array(
            '0' => '正常', 
            '1' => '停用', 
            '2' => '过期');
        $checkStatus = array(
            '0' => '未审核', 
            '1' => '审核通过', 
            '2' => '审核拒绝');
        $goodsType = array(
            '0' => '优惠劵', 
            '1' => '卡券', 
            '2' => '实物券', 
            '3' => '购物卡');
        $this->assign('goodsInfo', $goodsInfo);
        $this->assign('publishData', $publishData);
        $this->assign('status', $status);
        $this->assign('checkStatus', $checkStatus);
        $this->assign('goodsType', $goodsType);
        $this->display();
    }
    
    // 补库存
    public function addStorageNum() {
        $id = I('id', 0, 'intval');
        $addNum = I('addNum', 0, 'intval');
        if ($addNum == 0)
            $this->error('增加库存数不能为0');
        M()->startTrans();
        $goods_info = M('tgoods_info')->lock(true)
            ->where(
            array(
                'node_id' => $this->nodeId, 
                'id' => $id))
            ->find();
        if (! $goods_info) {
            M()->rollback();
            $this->error('参数错误！错误的商品号！');
        }
        
        if ($goods_info['storage_type'] == 0 || $goods_info['storage_num'] == - 1) {
            M()->rollback();
            $this->error('参数错误！库存不限的商品不能增加库存');
        }
        
        // 处理库存-开始事务
        do {
            // 变更商品表库存
            $data = array(
                'storage_num' => $goods_info['storage_num'] + $addNum, 
                'remain_num' => $goods_info['remain_num'] + $addNum);
            $flag = M('tgoods_info')->where(
                array(
                    'id' => $id))->save($data);
            if ($flag === false) {
                M()->rollback();
                log::write("库存增加失败，原因：" . M()->error());
                $this->error('库存增加失败，请重试！');
            }
            
            // 记录变更流水
            $data = array(
                'node_id' => $this->node_id, 
                'goods_id' => $id, 
                'change_num' => $addNum, 
                'pre_num' => $goods_info['remain_num'], 
                'current_num' => $goods_info['remain_num'] + $addNum, 
                'opt_type' => '3', 
                'relation_id' => $this->user_id, 
                // 'opt_desc' => printf(""),
                'add_time' => date('YmdHis'));
            $flag = M('tgoods_storage_trace')->add($data);
            if ($flag === false) {
                M()->rollback();
                log::write("库存增加失败，原因：" . M()->error());
                $this->error('库存增加失败，请重试！');
            }
        }
        while (0);
        
        M()->commit();
        
        node_log(
            "营销品库存增加，原库存【{$goods_info['storage_num']}】，增加数【{$addNum}】，新库存【" .
                 ($goods_info['storage_num'] + $addNum) . "】", 
                print_r($_POST, true));
        
        $this->success('库存增加成功！');
    }
    
    // 单个活动凭证统计 （详情）
    public function oneCodeCount() {
        $goodsId = I('goods_id', null, 'mysql_real_escape_string');
        $beginDate = I('begin_date', null);
        $endDate = I('end_date', null);
        $type = I('type', null);
        $goodsInfo = M('tgoods_info')->where(
            "goods_id='{$goodsId}' AND node_id IN({$this->nodeIn()})")->find();
        if (! $goodsInfo)
            $this->error('未找到该营销品信息');
            // 判断开始结束日期和统计类型
        if (empty($beginDate)) {
            $beginDate = $goodsInfo['add_time'];
        }
        if (empty($endDate)) {
            $endDate = date('YmdHis');
        }
        if (strtotime($beginDate) > strtotime($endDate)) {
            $this->error('开始日期不能大于结束日期');
        }
        is_null($type) ? $type = 1 : $type;
        // echo $beginDate.'::'.$endDate.'::'.$type;
        // 计算节点日期
        $nodeDate = $this->formatDateNode($beginDate, $endDate, 15);
        $codeCountArr = array();
        switch ($type) {
            case '1': // 发码
                $fieldType = 'send_num';
                $type = '1';
                break;
            case '2': // 验码
                $fieldType = 'verify_num';
                $type = '2';
                break;
            case '3': // 撤销
                $fieldType = 'cancel_num';
                $type = '3';
                break;
            default:
                $this->error('未知的统计类型');
        }
        // 计算各时间节点，不同类型活动的发码，验码，撤销数量
        foreach ($nodeDate as $k => $v) {
            // 第一天的发码量从零开始
            if ($k == 0) {
                $codeCountArr[] = '0';
                continue;
            }
            $where = array(
                'i.node_id' => $this->nodeId, 
                'i.goods_id' => $goodsInfo['goods_id'], 
                'c.trans_date ' => array(
                    'egt', 
                    $nodeDate[$k - 1]),  // 第一天到每个节点的数量
                'c.trans_date' => array(
                    'lt', 
                    $v));
            // 优惠券数量
            $count = M()->table('tgoods_info i')
                ->join('tpos_day_count c ON i.goods_id = c.goods_id')
                ->where($where)
                ->sum("c.{$fieldType}");
            $codeCountArr[] = is_null($count) ? '0' : $count;
        }
        $typeInfo = array(
            '1' => '发码量', 
            '  c2' => '验码量', 
            '3' => '撤销量');
        $nodeDateStr = "'" . implode("','", $nodeDate) . "'";
        $codeCountArr = implode(',', $codeCountArr);
        $this->assign('nodeDate', $nodeDateStr);
        $this->assign('codeCountArr', $codeCountArr);
        $this->assign('type', $type);
        $this->assign('typeInfo', $typeInfo);
        
        $this->assign('beginDate', $nodeDate[0]);
        $this->assign('endDate', $nodeDate[count($nodeDate) - 1]);
        $this->assign('goodsId', $goodsId);
        $this->assign('bt_name', $goodsInfo['goods_name']);
        $this->display();
    }

    /**
     * 卡券发布到异业联盟
     */
    public function publish() {
        $this->checkNodeQs(true);
        $goodsId = I('goods_id', 'mysql_real_escape_string');
        $goodsData = M('tgoods_info')->where("goods_id='{$goodsId}'")->find();
        if (! $goodsData)
            $this->error('未找到该商品或该商品已停用或过期', U('index'));
        if ($goodsData['end_time'] < date('YmdHis', time())) {
            $this->error("此卡券已过期，无法参与活动！", 
                array(
                    '返回列表' => U('index')));
            // echo
            // "<script>art.dialog('{:U('Hall/Voucher/voucherEdit?goods_id='.$goodsId)}',{width:
            // 900, height: 600,title:'卡券编辑',lock: true});</script>";
        }
        if ($this->isPost()) { // 是否提交了
            $error = '';
            // 截止时间
            $endDate = I('post.show_end_date');
            if (! check_str($endDate, 
                array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd'), $error)) {
                $this->error("展示截止日期{$error}");
            }
            if (strtotime() > strtotime($goodsData['end_time']) ||
                 strtotime($endDate) < strtotime($goodsData['begin_time'])) {
                $this->error('展示日期要在营销品有效日期之内');
            }
            // 采购价
            $priceType = I('show_price_type');
            switch ($priceType) {
                case 1:
                    $batchAmt = 0;
                    break;
                case 2:
                    $batchAmt = I('post.show_price');
                    if (! check_str($batchAmt, 
                        array(
                            'null' => false, 
                            'strtype' => 'number', 
                            'minval' => '0'), $error)) {
                        $this->error("营销品市场采购价{$error}");
                    }
                    break;
                case 3:
                    $batchAmt = 0;
                    break;
                default:
                    $this->error('请选择营销品价格');
            }
            // 图片
            $goodsImage = I('post.show_img_resp');
            if (! check_str($goodsImage, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '100'), $error)) {
                $this->error("请上传营销品图片");
            }
            // 描述
            $batchDesc = I('post.show_batch_desc');
            if (! check_str($batchDesc, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '200'), $error)) {
                $this->error("营销品描述{$error}");
            }
            // 图片移动
            if (is_file($this->tempImagePath . '/' . $goodsImage)) {
                $this->moveImage($goodsImage);
                $goodsImage = 'NumGoods/' . $this->nodeId . '/' . $goodsImage;
            } else {
                $goodsImage = $goodsData['goods_image'];
            }
            // thall_goods数据添加
            $data = array(
                'batch_short_name' => $goodsData['goods_name'], 
                'batch_name' => $goodsData['goods_name'], 
                'node_id' => $this->nodeId, 
                'user_id' => $this->userId, 
                'use_rule' => $batchDesc, 
                'batch_img' => $goodsImage, 
                'batch_amt' => $batchAmt, 
                'begin_time' => $goodsData['begin_time'], 
                'end_time' => $endDate . '235959', 
                'add_time' => date('YmdHis'), 
                'node_pos_group' => $goodsData['pos_group'], 
                'node_pos_type' => $goodsData['pos_group_type'], 
                'batch_desc' => $batchDesc, 
                'goods_id' => $goodsId);
            // 展示大厅添加价格面议
            if ($priceType == 3)
                $data['param1'] = '1';
            $batchId = M('thall_goods')->add($data);
            if (! $batchId) {
                $this->error('数据库错误,发布失败');
            }
            node_log("营销品发布，发布类型：异业联盟中心，名称：" . $goodsData['goods_name']);
            $this->success('发布成功');
            exit();
        }
        $id = $this->node_id;
        $arr = M()->table('tsale_relation s')
            ->where(
            array(
                's.node_id' => $id, 
                's.check_status' => 1))
            ->field('s.*,n.node_name')
            ->join('tnode_info n ON s.relation_node_id=n.node_id')
            ->order('s.add_time desc')
            ->select();
        $this->assign('list', $arr);
        $this->assign('goodsData', $goodsData);
        $this->display();
    }
    
    // 卡券发布到分销
    public function distribution() {
        // $this->checkNodeQs(true);
        $type = 1;
        $goodsId = I('goods_id'); // 卡券商品号
        $node_id = I('re_node_id'); // 所属商户，即被分销的乙方商户机构号
                                    // 判断所选择的商户是否已经分销过
        $map = array(
            'purchase_goods_id' => $goodsId, 
            'node_id' => $node_id, 
            'source' => 4);
        $count = M('tgoods_info')->where($map)->count();
        if ($count > 0) {
            $this->error("此卡券已经发货给此用户，请勿重复发货！", U('publish'));
        }
        $settle_price = I("settle_price"); // 结算价格
        $begintime = I('start_time') . "000000";
        $endtime = I('end_time') . "235959";
        if (! check_str($node_id, array(
            'null' => false), $error)) {
            $this->error("采购方{$error}");
        }
        if (! check_str($settle_price, array(
            'null' => false), $error)) {
            $this->error("结算价格{$error}");
        }
        $timesta = M()->table('tsale_relation')
            ->where(
            array(
                'node_id' => $this->nodeId, 
                'relation_node_id' => $node_id, 
                'check_status' => 1))
            ->find();
        if ($begintime < $timesta['begin_time']) {
            $this->error("使用期限不能早于双方协议的开始时间！");
        }
        if ($endtime > $timesta['end_time']) {
            $this->error("使用期限不能晚于双方协议的结束时间！");
        }
        $goodsData = M('tgoods_info')->where(
            "node_id='{$this->nodeId}' AND goods_id='{$goodsId}' AND status=0")->find();
        if (! $goodsData)
            $this->error('未找到该商品或该商品已停用或过期');
        $goods_Id = get_goods_id();
        if ($this->isPost()) {
            $error = '';
            // 图片
            $goodsImage = I('post.ai_img_resp');
            /*
             * if(!check_str($goodsImage,array('null'=>false,'maxlen_cn'=>'100'),$error)){
             * $this->error("请上传卡券图片"); }
             */
            
            // 图片移动
            if (is_file($this->tempImagePath . '/' . $goodsImage)) {
                $this->moveImage($goodsImage);
                $goodsImg = 'NumGoods/' . $this->nodeId . '/' . $goodsImage;
            } else {
                $goodsImg = $goodsData['goods_image'];
            }
            
            // 是否限制
            $numType = I('post.num_type');
            if ($numType == 2) {
                // 分销数量
                $goodsNum = I('post.goods_num');
                if (! check_str($goodsNum, 
                    array(
                        'null' => false, 
                        'strtype' => 'int', 
                        'minval' => '1'), $error)) {
                    $this->error("卡券数量{$error}");
                }
                if ($goodsData['storage_num'] != - 1 &&
                     $goodsData['remain_num'] < $goodsNum) {
                    $this->error("发货数量不能大于卡券的总数！");
                }
            } else {
                $storge = M('tgoods_info')->where(
                    array(
                        'goods_id' => $goodsId))->getField('storage_num');
                if ($storge != - 1) {
                    $this->error("此卡券必须限制发货数量！");
                }
                $goodsNum = '-1';
            }
            
            // 创建合约
            /*
             * $TransactionID = date("YmdHis").mt_rand(100000,999999);//流水号
             * $req_array = array( 'CreateTreatyReq'=>array( 'SystemID' =>
             * C('ISS_SYSTEM_ID'), 'RequestSeq' => $TransactionID, 'ShopNodeId'
             * => $this->node_id, 'BussNodeId' => $node_id, 'TreatyName' =>
             * $goodsData['goods_name'], 'TreatyShortName' =>
             * $goodsData['goods_name'], 'StartTime' => date('YmdHis'),
             * 'EndTime' => '20301231235959', 'GroupId' =>
             * $goodsData['pos_group'], 'GoodsName' => $goodsData['goods_name'],
             * 'GoodsShortName' => $goodsData['goods_name'], 'SalePrice' =>
             * empty($goodsData['goods_amt']) ? 0 : $goodsData['goods_amt'] ) );
             * $RemoteRequest = D('RemoteRequest','Service'); $resp_array =
             * $RemoteRequest->requestIssForImageco($req_array); $ret_msg =
             * $resp_array['CreateTreatyRes']['Status'];
             * if(!$resp_array||($ret_msg['StatusCode'] != '0000' &&
             * $ret_msg['StatusCode'] != '0001')) { M()->rollback();
             * log::write("创建合约失败，原因：{$ret_msg['StatusText']}");
             * $this->error('创建合约失败:'.$ret_msg['StatusText']); } $treatyId =
             * $resp_array['CreateTreatyRes']['TreatyId'];//合约id
             */
            // 支撑创建活动
            M()->startTrans();
            $smilId = $this->getSmil($goodsImg, $goodsData['goods_name'], '', 
                $node_id);
            if (! $smilId)
                $this->error('创建失败:smilid获取失败');
                // 创建活动
            $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
                                                                       // 请求参数
            $req_array = array(
                'ActivityCreateReq' => array(
                    'InterfaceAuth' => '', 
                    'InterfacePwd' => '', 
                    'SystemID' => C('ISS_SYSTEM_ID'), 
                    'ISSPID' => $node_id, 
                    'RelationID' => $this->node_id, 
                    'TransactionID' => $TransactionID, 
                    'SmilID' => $smilId, 
                    'ModelCode' => '', 
                    'ActivityInfo' => array(
                        'CustomNo' => '', 
                        'ActivityName' => $goodsData['goods_name'], 
                        'ActivityShortName' => $goodsData['goods_name'], 
                        'ActivityServiceType' => '04', 
                        'UseRangeID' => $goodsData['pos_group'], 
                        'BeginTime' => $begintime, 
                        'EndTime' => $endtime, 
                        'PrintTimes' => 1), 
                    'VerifyMode' => array(
                        'UseTimesLimit' => ! empty($goodsData['validate_type']) &&
                             $goodsData['validate_type'] == 1 ? 0 : 1, 
                            'UseAmtLimit' => ! empty(
                                $goodsData['validate_type']) &&
                             $goodsData['validate_type'] == 1 ? 1 : 0), 
                    'PGoodsInfo' => array(
                        'PGoodsPrice' => $settle_price, 
                        'PGoodsPrintText' => ''), 
                    'GoodsInfo' => array(
                        'pGoodsId' => '', 
                        'GoodsName' => $goodsData['goods_name'], 
                        'GoodsShortName' => $goodsData['goods_name'], 
                        'GoodsImg' => '', 
                        'GoodsType' => '', 
                        'SalePrice' => $settle_price), 
                    'DefaultParam' => array(
                        'SendClass' => '', 
                        'Messages' => array(
                            'Sms' => array(
                                'Text' => ''), 
                            'Mms' => array(
                                'Text' => '', 
                                'Subject' => '')), 
                        'PrintText' => $goodsData['print_text'], 
                        'UseTimes' => '', 
                        'UseAmt' => '', 
                        'PasswordTryTimes' => 3, 
                        'PasswordType' => '')));
            $RemoteRequest = D('RemoteRequest', 'Service');
            $resp_array = $RemoteRequest->requestIssForImageco($req_array);
            $ret_msg = $resp_array['ActivityCreateRes']['Status'];
            if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
                 $ret_msg['StatusCode'] != '0001')) {
                M()->rollback();
                log::write("活动创建失败！原因" . $ret_msg['StatusText']);
                $this->error("活动创建失败:{$ret_msg['StatusText']}");
            }
            $batchNo = $resp_array['ActivityCreateRes']['Info']['ActivityID'];
            
            // tgoods_info数据添加
            $data = array(
                'goods_id' => $goods_Id, 
                'goods_name' => $goodsData['goods_name'], 
                'goods_desc' => $goodsData['goods_desc'], 
                'goods_image' => $goodsImg, 
                'node_id' => $node_id, 
                'user_id' => $this->userId, 
                'pos_id' => $goodsData['pos_id'],  // 终端号
                'pos_group' => $goodsData['pos_group'],  // 终端组号
                'pos_group_type' => $goodsData['pos_group_type'], 
                'goods_type' => $goodsData['goods_type'], 
                'market_price' => $goodsData['market_price'], 
                'goods_amt' => $goodsData['goods_amt'], 
                'goods_discount' => $goodsData['goods_discount'], 
                'storage_type' => $numType == 2 ? 1 : 0, 
                'storage_num' => $numType == 2 ? $goodsNum : - 1, 
                'remain_num' => $numType == 2 ? $goodsNum : - 1, 
                'customer_no' => $goodsData['customer_no'], 
                'mms_title' => $goodsData['mms_title'], 
                'mms_text' => $goodsData['mms_text'], 
                'settle_price' => $settle_price, 
                'sms_text' => $goodsData['sms_text'], 
                'print_text' => $goodsData['print_text'], 
                'validate_times' => $goodsData['validate_times'], 
                'begin_time' => $begintime, 
                'end_time' => $endtime, 
                'send_begin_date' => $goodsData['send_begin_date'], 
                
                'send_end_date' => $goodsData['send_end_date'], 
                'verify_begin_date' => $begintime, 
                'verify_end_date' => $endtime, 
                'verify_begin_type' => $goodsData['verify_begin_type'], 
                'verify_end_type' => $goodsData['verify_end_type'], 
                'add_time' => date('YmdHis'), 
                
                // 'update_time'=>$goodsData['update_time'],
                'status' => $goodsData['status'], 
                'join_rule' => $goodsData['join_rule'], 
                'node_service_hotline' => $goodsData['node_service_hotline'], 
                // 'p_goods_id'=>$treatyId,
                'p_goods_id' => $goodsData['p_goods_id'], 
                'pp_p_goods_id' => $goodsData['pp_p_goods_id'], 
                
                'goods_cat' => $goodsData['goods_cat'], 
                'bloc_name' => $goodsData['bloc_name'], 
                'recruit_end_date' => $goodsData['recruit_end_date'], 
                'source' => 4, 
                'purchase_goods_id' => $goodsData['goods_id'], 
                'purchase_type' => $goodsData['purchase_type'], 
                
                'purchase_relation_id' => $goodsData['purchase_relation_id'], 
                'purchase_batch_id' => $goodsData['purchase_batch_id'], 
                'batch_no' => $batchNo, 
                'check_status' => $goodsData['check_status'], 
                'check_time' => $goodsData['check_time'], 
                'check_memo' => $goodsData['check_memo'], 
                'check_user' => $goodsData['check_user'], 
                'purchase_node_id' => $goodsData['node_id'], 
                'validate_type' => $goodsData['validate_type']);
            $batchId = M('tgoods_info')->data($data)->add();
            // 修改原本用户数据
            if ($batchId) {
                if ($goodsData['storage_type'] == 1 && $numType == 2) {
                    $status = M('tgoods_info')->where(
                        array(
                            'goods_id' => $goodsId))->setDec('remain_num', 
                        $goodsNum);
                    // 记录变更流水
                    $data = array(
                        'node_id' => $this->node_id, 
                        'goods_id' => $goodsData['id'], 
                        'change_num' => $goodsNum, 
                        'pre_num' => $goodsData['remain_num'], 
                        'current_num' => $goodsData['remain_num'] - $goodsNum, 
                        'opt_type' => '13', 
                        'relation_id' => $goods_Id, 
                        'add_time' => date('YmdHis'));
                    $flag = M('tgoods_storage_trace')->add($data);
                    node_log("卡券发布，发布类型：分销，名称：" . $goodsData['goods_name']);
                }
                M()->commit();
                $this->success('发布成功！');
            } else {
                M()->rollback();
                $this->error('数据库错误,发布失败');
            }
        }
    }
}
