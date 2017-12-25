<?php

/**
 * 团购活动
 *
 * @author bao
 */
class GroupBuyAction extends BaseAction {
    
    // 活动类型
    public $BATCH_TYPE = '6';
    // 图片路径
    public $img_path;

    public function _initialize() {
        parent::_initialize();
        // 验证是否开通团购服务
        // if(!checkUserRights($this->node_id, C('NEW_CHARGE_ID'))){
        // redirect(U('Home/Introduce/groupBuy'));//跳转到服务介绍页面
        // }
        
        $path_arr = C('BATCH_IMG_PATH_NAME');
        $img_path = $path_arr[$this->BATCH_TYPE] . '/' . $this->node_id . '/';
        $this->img_path = $img_path;
        
        // 临时路径
        $tmp_path = 'Upload/img_tmp' . '/' . $this->node_id . '/';
        $this->assign('img_path', $img_path);
        $this->assign('tmp_path', $tmp_path);
    }

    public function index() {
        $model = M('tmarketing_info');
        $map = array(
            'node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"));
        
        $data = $_REQUEST;
        
        if ($data['key'] != '') {
            $map['name'] = array(
                'like', 
                '%' . $data['key'] . '%');
        }
        if ($data['status'] != '') {
            $map['status'] = $data['status'];
        }
        // 处理特殊查询字段
        $beginDate = I('start_time', null, 'mysql_real_escape_string,trim');
        if (! empty($beginDate)) {
            $map['add_time'] = array(
                'egt', 
                $beginDate . '000000');
        }
        $endDate = I('end_time', null, 'mysql_real_escape_string,trim');
        if (! empty($endDate)) {
            $map[' add_time'] = array(
                'elt', 
                $endDate . '235959');
        }
        $map['batch_type'] = 6;
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = $model->where($map)->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        
        foreach ($data as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        
        $show = $Page->show(); // 分页显示输出
        
        $list = $model->where($map)
            ->order('id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        $arr_ = C('CHANNEL_TYPE');
        // dump($list);
        $this->assign('arr_', $arr_);
        $this->assign('query_list', $list);
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }

    public function add() {
        if ($this->isPost()) {
            
            $error = '';
            $name = I('post.name', null);
            if (! check_str($name, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '20'), $error)) {
                $this->error("活动名称{$error}");
            }
            $dataInfo = M('tmarketing_info')->where(
                "node_id='{$this->nodeId}' AND name='{$name}'")->find();
            if ($dataInfo)
                $this->error('活动名称已经存在');
            $startDate = I('post.start_time', null);
            if (! check_str($startDate, 
                array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd'), $error)) {
                $this->error("活动开始时间{$error}");
            }
            $endDate = I('post.end_time', null);
            if (! check_str($endDate, 
                array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd'), $error)) {
                $this->error("活动结束时间{$error}");
            }
            if ($endDate < date('Ymd'))
                $this->error('活动截止日期不能小于当前日期');
            $groupGoodsName = I('post.group_goods_name', null);
            if (! check_str($groupGoodsName, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '20'), $error)) {
                $this->error("商品名称{$error}");
            }
            $goodsImg = I('post.resp_goods_img');
            if (! check_str($goodsImg, 
                array(
                    'null' => false), $error)) {
                $this->error("商品图片{$error}");
            }
            // 移动图片
            $img_move = move_batch_image($goodsImg, $this->BATCH_TYPE, 
                $this->node_id);
            if ($img_move !== true)
                $this->error('商品图片上传失败！');
            
            $goodsImg = $this->img_path . $goodsImg;
            
            $goodsMemo = I('post.goods_memo', null);
            if (! check_str($goodsMemo, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '200'), $error)) {
                $this->error("商品描述{$error}");
            }
            $marketPrice = I('post.market_price', null);
            if (! check_str($marketPrice, 
                array(
                    'null' => false, 
                    'strtype' => 'number', 
                    'minval' => '0'), $error)) {
                $this->error("商品市场价{$error}");
            }
            $groupPrice = I('post.group_price', null);
            if (! check_str($groupPrice, 
                array(
                    'null' => false, 
                    'strtype' => 'number', 
                    'minval' => '0'), $error)) {
                $this->error("商品团购价{$error}");
            }
            $goodsNum = I('post.goods_num', null);
            if (! check_str($goodsNum, 
                array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'minval' => '0'), $error)) {
                $this->error("商品总数{$error}");
            }
            $buyNum = I('post.buy_num', null);
            if (! check_str($buyNum, 
                array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'minval' => '0'), $error)) {
                $this->error("每人限购量{$error}");
            }
            $usingRules = I('post.using_rules');
            if (! check_str($usingRules, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '100'), $error)) {
                $this->error("彩信内容{$error}");
            }
            $mmsTitle = I('post.mms_title');
            if (! check_str($mmsTitle, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '10'), $error)) {
                $this->error("彩信标题{$error}");
            }
            // $materialCode = I('post.material_code');
            // if(!check_str($materialCode,array('null'=>true,'maxlen_cn'=>'32'),$error)){
            // $this->error("物料编号{$error}");
            // }
            $size = I('post.size', null);
            $isCodeImg = I('post.is_code_img', null);
            $respCodeImg = I('post.resp_code_img', null);
            if ($isCodeImg != '1')
                $respCodeImg = '';
            $isCj = I('post.is_cj', null);
            $snsType = I('post.sns_type');
            if (! empty($snsType)) {
                $snsType = implode('-', $snsType);
            }
            
            // dump($_POST);exit;
            // 为该商户创建列表渠道
            $channelModel = M('tchannel');
            $channelId = $channelModel->where(
                "node_id='{$this->nodeId}' AND type=4 AND sns_type=44 AND status=1")->getField(
                'id');
            if (! $channelId) {
                $data = array(
                    'name' => '团购列表', 
                    'type' => '4', 
                    'sns_type' => '44', 
                    'status' => '1', 
                    'node_id' => $this->nodeId, 
                    'add_time' => date('YmdHis'));
                $channelId = $channelModel->add($data);
                if (! $channelId)
                    $this->error('系统出错创建失败');
            }
            
            // 创建batch_info数据
            M()->startTrans();
            $data = array(
                'batch_short_name' => $groupGoodsName, 
                'batch_name' => $groupGoodsName, 
                'node_id' => $this->nodeId, 
                'user_id' => $this->userId, 
                // 'material_code' => $materialCode,
                'batch_class' => '4', 
                'info_title' => $mmsTitle, 
                'use_rule' => $usingRules, 
                'batch_amt' => $groupPrice, 
                'begin_time' => $startDate . '000000', 
                'end_time' => $endDate . '235959', 
                'send_begin_date' => $startDate . '000000', 
                'send_end_date' => $endDate . '235959', 
                'verify_begin_date' => $startDate . '000000', 
                'verify_end_date' => $endDate . '235959', 
                'verify_begin_type' => '0', 
                'verify_end_type' => '0', 
                'add_time' => date('YmdHis'));
            $bInfoId = M('tbatch_info')->data($data)->add();
            if (! $bInfoId) {
                M()->rollback();
                $this->error('系统出错,创建失败');
            }
            // tmarketing_info数据创建
            $data = array(
                'name' => $name, 
                'batch_type' => $this->BATCH_TYPE, 
                'node_id' => $this->nodeId, 
                'start_time' => $startDate . '000000', 
                'end_time' => $endDate . '235959', 
                'market_price' => $marketPrice, 
                'group_price' => $groupPrice, 
                'group_goods_name' => $groupGoodsName, 
                'goods_num' => $goodsNum, 
                'buy_num' => $buyNum, 
                'goods_img' => $goodsImg, 
                // 'size' => $size,
                // 'code_img' => $respCodeImg,
                'is_cj' => $isCj, 
                'memo' => $goodsMemo, 
                'sns_type' => $snsType, 
                'status' => '1', 
                'add_time' => date('YmdHis'));
            
            $batchId = M('tmarketing_info')->add($data);
            if (! $batchId) {
                M()->rollback();
                $this->error('系统出错,添加失败');
            }
            // 更新奖品规则
            $data = I('post.');
            if ($data['is_cj'] == '1') {
                
                $cjrulem = M('tcj_rule');
                $cdata = array(
                    'batch_type' => $this->BATCH_TYPE, 
                    'batch_id' => $batchId, 
                    'jp_set_type' => $data['jp_set_type'], 
                    'day_count' => '1', 
                    'total_chance' => $data['jp_set_type'] == '2' ? $data['total_chance'] : '', 
                    'node_id' => $this->node_id, 
                    'cj_button_text' => $data['cj_button_text'], 
                    'add_time' => date('YmdHis'), 
                    'status' => '1', 
                    'phone_total_count' => $data['phone_total_count'], 
                    'phone_day_count' => $data['phone_day_count'], 
                    'phone_total_part' => $data['phone_total_part'], 
                    'phone_day_part' => $data['phone_day_part']);
                $rulequery = $cjrulem->add($cdata);
                if (! $rulequery) {
                    M()->rollback();
                    $this->error('系统错误！', 
                        array(
                            '返回抽奖活动' => U('index')));
                }
                $cjbatchm = M('tcj_batch');
                if ($data['jp_set_type'] == '1') {
                    $bdata = array(
                        'batch_id' => $batchId, 
                        'node_id' => $this->node_id, 
                        'activity_no' => $data['jp_type'] == '1' ? $data['zc_batch_no'] : $data['wc_batch_no'], 
                        'award_origin' => $data['jp_type'], 
                        'award_level' => '1', 
                        'award_rate' => $data['chance'], 
                        'total_count' => $data['goods_count'], 
                        'day_count' => $data['day_goods_count'], 
                        'batch_type' => $this->BATCH_TYPE, 
                        'cj_rule_id' => $rulequery);
                    
                    if (! empty($data['mem_batch']) &&
                         is_array($data['mem_batch'])) {
                        $bdata['member_batch_id'] = implode('-', 
                            $data['mem_batch']);
                    }
                    
                    if (in_array('', $bdata)) {
                        M()->rollback();
                        $this->error('奖品规则设置错误！');
                    }
                    if ($data['day_goods_count'] > $data['goods_count']) {
                        M()->rollback();
                        $this->error('每日奖品限量不能大于总奖品数！');
                    }
                    $bquery = $cjbatchm->add($bdata);
                    
                    if (! $bquery) {
                        M()->rollback();
                        $this->error('奖品设置错误！');
                    }
                } elseif ($data['jp_set_type'] == '2') {
                    for ($i = 1; $i <= 3; $i ++) {
                        
                        $bdata = array(
                            'batch_id' => $batchId, 
                            'node_id' => $this->node_id, 
                            'activity_no' => $data['jp_type_' . $i] == '1' ? $data['zc_batch_no_' .
                                 $i] : $data['wc_batch_no_' . $i], 
                                'award_origin' => $data['jp_type_' . $i], 
                                'award_level' => $i, 
                                'award_rate' => $data['goods_count_' . $i], 
                                'total_count' => $data['goods_count_' . $i], 
                                'day_count' => $data['day_goods_count_' . $i], 
                                'batch_type' => $this->BATCH_TYPE, 
                                'cj_rule_id' => $rulequery);
                        
                        if (! empty($data['mem_batch' . $i]) &&
                             is_array($data['mem_batch' . $i])) {
                            $bdata['member_batch_id'] = implode('-', 
                                $data['mem_batch' . $i]);
                        }
                        
                        if (in_array('', $bdata)) {
                            M()->rollback();
                            $this->error('奖品规则设置错误！');
                        }
                        if ($data['day_goods_count_' . $i] >
                             $data['goods_count_' . $i]) {
                            M()->rollback();
                            $this->error('每日奖品限量不能大于总奖品数！');
                        }
                        $bquery = $cjbatchm->add($bdata);
                        if (! $bquery) {
                            M()->rollback();
                            $this->error('奖品规则设置错误1！');
                        }
                    }
                }
            }
            // 支撑创建活动
            $req_array = array(
                'ActivityCreateReq' => array(
                    'SystemID' => C('ISS_SYSTEM_ID'), 
                    'ISSPID' => $this->nodeId, 
                    'TransactionID' => date("YmdHis") . mt_rand(100000, 999999),  // 请求单号
                    'ActivityInfo' => array(
                        'CustomNo' => '', 
                        'ActivityName' => iconv("utf-8", "gbk", $groupGoodsName), 
                        'ActivityShortName' => iconv("utf-8", "gbk", 
                            $groupGoodsName), 
                        'BeginTime' => $startDate . '000000', 
                        'EndTime' => $endDate . '235959'), 
                    'VerifyMode' => array(
                        'UseTimesLimit' => 1, 
                        'UseAmtLimit' => 0), 
                    'GoodsInfo' => array(
                        'GoodsName' => iconv("utf-8", "gbk", $groupGoodsName), 
                        'GoodsShortName' => iconv("utf-8", "gbk", 
                            $groupGoodsName)), 
                    'DefaultParam' => array(
                        'PasswordTryTimes' => 3, 
                        'PasswordType' => '')));
            $RemoteRequest = D('RemoteRequest', 'Service');
            $resp_array = $RemoteRequest->requestIssServ($req_array);
            $ret_msg = $resp_array['ActivityCreateRes']['Status'];
            if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
                 $ret_msg['StatusCode'] != '0001')) {
                M()->rollback();
                $this->error("创建失败:{$ret_msg['StatusText']}");
            }
            $batchNo = $resp_array['ActivityCreateRes']['ActivityID'];
            // 更新tbatch_info,tmarketing_info中batch_no字段信息
            $result = M('tbatch_info')->where("id='{$bInfoId}'")->save(
                array(
                    'batch_no' => $batchNo));
            if (! $result) {
                M()->rollback();
                $this->error('系统出错,创建失败');
            }
            $result = M('tmarketing_info')->where("id='{$batchId}'")->save(
                array(
                    'member_level' => $batchNo));
            if (! $result) {
                M()->rollback();
                $this->error('系统出错,创建失败');
            }
            // 绑定渠道团购列表渠道
            $data = array(
                'batch_type' => '6', 
                'batch_id' => $batchId, 
                'channel_id' => $channelId, 
                'add_time' => date('YmdHis'), 
                'node_id' => $this->nodeId);
            $result = M('tbatch_channel')->add($data);
            if (! $result) {
                M()->rollback();
                $this->error('系统出错,创建失败');
            }
            M()->commit();
            $this->ajaxReturn(
                array(
                    'url' => U('LabelAdmin/BindChannel/index', 
                        array(
                            'batch_type' => 6, 
                            'batch_id' => $batchId))), '创建成功！', 1);
            exit();
        }
        $this->display();
    }

    public function edit() {
        $id = I('id', null, 'mysql_real_escape_string');
        if (empty($id))
            $this->error('错误参数');
        $where = array(
            'g.node_id' => $this->nodeId, 
            'g.id' => $id);
        $groupInfo = M()->table("tmarketing_info g")->field(
            'g.*,i.material_code,i.info_title,i.use_rule')
            ->join(
            "tbatch_info i ON g.member_level=i.batch_no AND g.node_id=i.node_id")
            ->where($where)
            ->find();
        
        if (empty($groupInfo))
            $this->error('未找到该活动!');
        
        if ($this->isPost()) {
            $error = '';
            $name = I('post.name', null);
            if (! check_str($name, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '20'), $error)) {
                $this->error("活动名称{$error}");
            }
            $dataInfo = M('tmarketing_info')->where(
                "node_id='{$this->nodeId}' AND name='{$name}' AND id<>'{$id}'")->find();
            if ($dataInfo)
                $this->error('活动名称已经存在');
            $startDate = I('post.start_time', null);
            if (! check_str($startDate, 
                array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd'), $error)) {
                $this->error("活动开始时间{$error}");
            }
            $endDate = I('post.end_time', null);
            if (! check_str($endDate, 
                array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd'), $error)) {
                $this->error("活动结束时间{$error}");
            }
            // $groupGoodsName = I('post.group_goods_name',null);
            // if(!check_str($groupGoodsName,array('null'=>false,'maxlen_cn'=>'20'),$error)){
            // $this->error("商品名称{$error}");
            // }
            $goodsImg = I('post.resp_goods_img');
            if (! check_str($goodsImg, 
                array(
                    'null' => false), $error)) {
                $this->error("商品图片{$error}");
            }
            $goodsMemo = I('post.goods_memo', null);
            if (! check_str($goodsMemo, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '200'), $error)) {
                $this->error("商品描述{$error}");
            }
            $marketPrice = I('post.market_price', null);
            if (! check_str($marketPrice, 
                array(
                    'null' => false, 
                    'strtype' => 'number', 
                    'minval' => '0'), $error)) {
                $this->error("商品市场价{$error}");
            }
            // $groupPrice = I('post.group_price',null);
            // if(!check_str($groupPrice,array('null'=>false,'strtype'=>'number','minval'=>'0'),$error)){
            // $this->error("商品团购价{$error}");
            // }
            $goodsNum = I('post.goods_num', null);
            if (! check_str($goodsNum, 
                array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'minval' => '0'), $error)) {
                $this->error("商品总数{$error}");
            }
            $buyNum = I('post.buy_num', null);
            if (! check_str($buyNum, 
                array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'minval' => '0'), $error)) {
                $this->error("没人限购量{$error}");
            }
            // $usingRules = I('post.using_rules');
            // if(!check_str($usingRules,array('null'=>false,'maxlen_cn'=>'100'),$error)){
            // $this->error("彩信内容{$error}");
            // }
            // $mmsTitle = I('post.mms_title');
            // if(!check_str($mmsTitle,array('null'=>false,'maxlen_cn'=>'10'),$error)){
            // $this->error("彩信标题{$error}");
            // }
            // $materialCode = I('post.material_code');
            // if(!check_str($materialCode,array('null'=>true,'maxlen_cn'=>'32'),$error)){
            // $this->error("物料编号{$error}");
            // }
            $size = I('post.size', null);
            $isCodeImg = I('post.is_code_img', null);
            $respCodeImg = I('post.resp_code_img', null);
            if ($isCodeImg != '1')
                $respCodeImg = '';
            $isCj = I('post.is_cj', null);
            $isRest = I('post.is_reset');
            $snsType = I('post.sns_type');
            if (! empty($snsType)) {
                $snsType = implode('-', $snsType);
            }
            // tmartketing_info数据创建
            $data = array(
                'name' => $name, 
                'node_id' => $this->nodeId, 
                'start_time' => $startDate . '000000', 
                'end_time' => $endDate . '235959', 
                'market_price' => $marketPrice, 
                // 'group_goods_name' => $groupGoodsName,
                'goods_num' => $goodsNum, 
                'buy_num' => $buyNum, 
                'goods_img' => $goodsImg, 
                // 'size' => $size,
                // 'code_img' => $respCodeImg,
                'memo' => $goodsMemo, 
                'sns_type' => $snsType, 
                'add_time' => date('YmdHis'));
            if ($isRest == '1') {
                $data['is_cj'] = $isCj;
            }
            M()->startTrans();
            $result = M('tmarketing_info')->where(
                "node_id='{$this->nodeId}' AND id='{$id}'")->save($data);
            // echo M()->getLastSql();exit;
            if ($result === false) {
                M()->rollback();
                $this->error('系统出错,更新失败!');
            }
            // 更新奖品规则
            if ($isCj == '1') {
                
                $cjrulem = M('tcj_rule');
                // 更新状态
                $uparr = array(
                    'node_id' => $this->nodeId, 
                    'batch_type' => '5', 
                    'batch_id' => $id);
                $up = $cjrulem->where($uparr)->save(
                    array(
                        'status' => '0'));
                if ($up === false) {
                    M()->rollback();
                    $this->error('规则状态更新失败！', 
                        array(
                            '返回抽奖活动' => U('index')));
                }
                $cdata = array(
                    'batch_type' => '5', 
                    'batch_id' => $id, 
                    'jp_set_type' => $_POST['jp_set_type'], 
                    'day_count' => '1', 
                    'total_chance' => $_POST['jp_set_type'] == '2' ? $_POST['total_chance'] : '', 
                    'node_id' => $this->nodeId, 
                    'cj_button_text' => $_POST['cj_button_text'], 
                    'status' => '1', 
                    'add_time' => date('YmdHis'));
                $rulequery = $cjrulem->add($cdata);
                if (! $rulequery) {
                    M()->rollback();
                    $this->error('系统错误！', 
                        array(
                            '返回抽奖活动' => U('index')));
                }
                
                $cjbatchm = M('tcj_batch');
                if ($_POST['jp_set_type'] == '1') {
                    $bdata = array(
                        'batch_id' => $id, 
                        'node_id' => $this->nodeId, 
                        'activity_no' => $_POST['jp_type'] == '1' ? $_POST['zc_batch_no'] : $_POST['wc_batch_no'], 
                        'award_origin' => $_POST['jp_type'], 
                        'award_level' => '1', 
                        'award_rate' => $_POST['chance'], 
                        'total_count' => $_POST['goods_count'], 
                        'day_count' => $_POST['day_goods_count'], 
                        'batch_type' => '5', 
                        'cj_rule_id' => $rulequery);
                    
                    if (in_array('', $bdata)) {
                        M()->rollback();
                        $this->error('奖品规则设置错误！');
                    }
                    if ($data['day_goods_count'] > $_POST['goods_count']) {
                        M()->rollback();
                        $this->error('每日奖品限量不能大于总奖品数！');
                    }
                    $bquery = $cjbatchm->add($bdata);
                    
                    if (! $bquery) {
                        M()->rollback();
                        $this->error('奖品设置错误！');
                    }
                } elseif ($_POST['jp_set_type'] == '2') {
                    
                    for ($i = 1; $i <= 3; $i ++) {
                        
                        $bdata = array(
                            'batch_id' => $id, 
                            'node_id' => $this->nodeId, 
                            'activity_no' => $_POST['jp_type_' . $i] == '1' ? $_POST['zc_batch_no_' .
                                 $i] : $_POST['wc_batch_no_' . $i], 
                                'award_origin' => $_POST['jp_type_' . $i], 
                                'award_level' => $i, 
                                'award_rate' => $_POST['goods_count_' . $i], 
                                'total_count' => $_POST['goods_count_' . $i], 
                                'day_count' => $_POST['day_goods_count_' . $i], 
                                'batch_type' => '5', 
                                'cj_rule_id' => $rulequery);
                        
                        if (in_array('', $bdata)) {
                            M()->rollback();
                            $this->error('奖品规则设置错误！');
                        }
                        if ($_POST['day_goods_count_' . $i] >
                             $_POST['goods_count_' . $i]) {
                            M()->rollback();
                            $this->error('每日奖品限量不能大于总奖品数！');
                        }
                        $bquery = $cjbatchm->add($bdata);
                        if (! $bquery) {
                            M()->rollback();
                            $this->error('奖品规则设置错误！');
                        }
                    }
                }
                $mq = M('tmarketing_info')->where(
                    array(
                        'id' => $id))->save(
                    array(
                        'is_cj' => $_POST['is_cj']));
                if ($mq === false) {
                    M()->rollback();
                    $this->error('抽奖设置失败！');
                }
            }
            M()->commit();
            $this->success('更新成功!');
            exit();
        }
        
        if ($groupInfo['is_cj'] == '1') {
            $cjrule = M('tcj_rule');
            $map1 = array(
                'node_id' => $this->nodeId, 
                'batch_id' => $groupInfo['id'], 
                'batch_type' => '5', 
                'status' => '1');
            $cjarr = $cjrule->where($map1)->find();
            $map1 = array(
                'node_id' => $this->nodeId, 
                'batch_id' => $groupInfo['id'], 
                'batch_type' => '5', 
                'cj_rule_id' => $cjarr['id']);
            $cjbatch = M('tcj_batch');
            $cjbatch_arr = $cjbatch->where($map1)
                ->order('award_level asc')
                ->select();
            
            $batch_name_arr = array();
            if ($cjbatch_arr) {
                $batch_map = array(
                    'node_id' => $this->nodeId);
                foreach ($cjbatch_arr as $v) {
                    $batch_map['batch_no'] = $v['activity_no'];
                    $batch_name_arr[$v['activity_no']] = M('tbatch_info')->where(
                        $batch_map)->getField('batch_short_name');
                }
            }
        }
        
        // 会员等级
        // $arr = R('Member/Member/getBatch');
        // $this->assign('batch_list', $arr);
        $this->assign('cjarr', $cjarr);
        $this->assign('cjbatch_arr', $cjbatch_arr);
        $this->assign('batch_name_arr', $batch_name_arr);
        $this->assign('row', $groupInfo);
        $this->display();
    }
    
    // 查看订单信息
    public function orderList() {
        $batchNo = I('batch_no', null, 'mysql_real_escape_string');
        if (empty($batchNo))
            $this->error('参数错误');
        $goodsName = I('goods_name', null, 'mysql_real_escape_string,trim');
        if (isset($goodsName) && $goodsName != '') {
            $where['g.group_goods_name'] = array(
                'like', 
                "%{$goodsName}%");
        }
        $orderId = I('order_id', null, 'mysql_real_escape_string,trim');
        if (! empty($orderId)) {
            $where['o.order_id'] = $orderId;
        }
        $status = I('status', null, 'mysql_real_escape_string,trim');
        if (! empty($status)) {
            $where['o.pay_status'] = $status;
        }
        $where['o.group_batch_no'] = $batchNo;
        $where['o.node_id'] = $this->nodeId;
        // $where['o.order_type'] = '2';
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = M()->table('ttg_order_info o')
            ->where($where)
            ->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
                                         // 确保分页查询条件
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        $show = $Page->show(); // 分页显示输出
        $field = array(
            'o.*,g.group_goods_name,g.group_price');
        $orderList = M()->table('ttg_order_info o')
            ->field($field)
            ->join("tmarketing_info g ON o.group_batch_no=g.member_level")
            ->where($where)
            ->order('o.add_time DESC')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        $orderStatus = array(
            '1' => '未支付', 
            '2' => '已支付');
        // dump($orderList);exit;
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('orderList', $orderList);
        $this->assign('orderStatus', $orderStatus);
        $this->assign('post', $_REQUEST);
        $this->assign('batchNo', $batchNo);
        $this->assign('empty', '<tr><td colspan="10">无数据</td></span>');
        $this->display();
    }
    
    // 数据导出
    public function export() {
        
        // 查询条件组合
        $where = "WHERE 1";
        if (! empty($_POST)) {
            $filter = array();
            $condition = array_map('trim', $_POST);
            if (isset($condition['key']) && $condition['key'] != '') {
                $filter[] = "name LIKE '%{$condition['key']}%'";
            }
            if (isset($condition['status']) && $condition['status'] != '') {
                $filter[] = "status = '{$condition['status']}'";
            }
            if (isset($condition['start_time']) && $condition['start_time'] != '') {
                $condition['start_time'] = $condition['start_time'] . ' 000000';
                $filter[] = "add_time >= '{$condition['start_time']}'";
            }
            if (isset($condition['end_time']) && $condition['end_time'] != '') {
                $condition['end_time'] = $condition['end_time'] . ' 235959';
                $filter[] = "add_time <= '{$condition['end_time']}'";
            }
        }
        if (! empty($filter)) {
            $condition = implode(' AND ', $filter);
            $where .= ' AND ' . $condition;
        }
        $sql = "SELECT name,add_time,start_time,end_time,
		CASE status WHEN '1' THEN '正常' ELSE '停用' END status,
		click_count,send_count
		FROM
		tmarketing_info {$where} AND node_id in({$this->nodeIn()})";
        $cols_arr = array(
            'name' => '活动名称', 
            'add_time' => '添加时间', 
            'start_time' => '活动开始时间', 
            'end_time' => '活动结束时间', 
            'status' => '状态', 
            'click_count' => '访问量', 
            'send_count' => '发码量');
        if (querydata_download($sql, $cols_arr, M()) == false) {
            $this->error('下载失败');
        }
    }
    
    // 状态修改
    public function editStatus() {
        $batchId = I('post.batch_id', null);
        $status = I('post.status', null);
        if (is_null($batchId) || is_null($status)) {
            $this->error('缺少必要参数');
        }
        $result = M('tmarketing_info')->where(
            "node_id='{$this->node_id}' AND id='{$batchId}'")->find();
        if (! $result) {
            $this->error('未找到该活动');
        }
        if ($status == '1') {
            $data = array(
                'id' => $batchId, 
                'status' => '1');
        } else {
            $data = array(
                'id' => $batchId, 
                'status' => '2');
        }
        $result = M('tmarketing_info')->save($data);
        if ($result) {
            $this->success('更新成功', 
                array(
                    '返回' => U('News/index')));
        } else {
            $this->error('更新失败');
        }
    }

    public function winningExport() {
        $batchId = I('batch_id', null, 'mysql_real_escape_string');
        $status = I('status', null, 'mysql_real_escape_string');
        if (is_null($batchId))
            $this->error('缺少参数');
        $sql = "SELECT mobile,add_time,
		CASE status WHEN '1' THEN '未中奖' ELSE '中奖' END status,prize_level
		FROM
		tcj_trace WHERE batch_id='{$batchId}' AND batch_type=5 AND node_id in({$this->nodeIn()})
		ORDER by status DESC";
        $countSql = "SELECT COUNT(*) as count FROM tcj_trace WHERE batch_id='{$batchId}' AND batch_type=5 AND node_id in({$this->nodeIn()})";
        $cols_arr = array(
            'mobile' => '手机号', 
            'add_time' => '中奖时间', 
            'status' => '是否中奖', 
            'prize_level' => '奖品等级');
        // 获取活动名称
        $batchName = M('tmarketing_info')->where(
            "id='{$batchId}' AND node_id in({$this->nodeIn()})")->getField(
            'name');
        $fileName = $batchName . '-' . date('Y-m-d') . '-' . '中奖名单';
        $count = M()->query($countSql);
        if ($count[0]['count'] <= 0)
            $this->error('没有中奖数据');
        if (empty($status))
            $this->ajaxReturn('', '', 1);
        if (querydata_download($sql, $cols_arr, M(), $fileName) == false) {
            $this->error('下载失败或没有中奖数据');
        }
    }
    
    // 发送团购商品
    public function sendCode() {
        $orderId = I('order_id', null, 'mysql_real_escape_string');
        $orderInfo = M('ttg_order_info')->where(
            "order_id='{$orderId}' AND node_id='{$this->nodeId}' AND order_type=0")->find();
        if (! $orderInfo)
            $this->error('未找到该订单信息');
            // 是否应经支付
        if ($orderInfo['status'] != 2)
            $this->error('该订单还未支付');
        if (! empty($orderInfo['request_id']))
            $this->error('该订单已经发过码');
        import("@.Vendor.SendCode");
        $req = new SendCode();
        $transId = date('YmdHis') . sprintf('%04s', mt_rand(0, 1000));
        $resp = $req->wc_send($this->nodeId, $this->userId, 
            $orderInfo['group_batch_no'], $orderInfo['receiver_phone'], '8', 
            $transId);
        if ($resp === true) {
            // 更新request_id
            $result = M('ttg_order_info')->where("order_id='{$orderId}'")->save(
                array(
                    'request_id' => $transId));
            $this->success('发送成功');
        } else {
            $this->error('发送失败:' . $resp);
        }
    }
    
    // 撤消团购商品
    public function cancelCode() {
        $orderId = I('order_id', null, 'mysql_real_escape_string');
        $orderInfo = M('ttg_order_info')->where(
            "order_id='{$orderId}' AND node_id='{$this->nodeId}' AND order_type=0")->find();
        if (! $orderInfo)
            $this->error('未找到该订单信息');
            // 是否应经支付
        if ($orderInfo['status'] != 2)
            $this->error('该订单还未支付');
        if (empty($orderInfo['request_id']))
            $this->error('该订单还未发码');
        import("@.Vendor.SendCode");
        $req = new SendCode();
        $resp = $req->cancelcode($this->nodeId, $this->userId, 
            $orderInfo['request_id']);
        if ($resp === true) {
            // 更新request_id
            $result = M('ttg_order_info')->where("order_id='{$orderId}'")->save(
                array(
                    'request_id' => ''));
            $this->success('撤消成功');
        } else {
            $this->error('撤消失败:' . $resp);
        }
    }
    
    // 重发团购商品
    public function resendCode() {
        $orderId = I('order_id', null, 'mysql_real_escape_string');
        $orderInfo = M('ttg_order_info')->where(
            "order_id='{$orderId}' AND node_id='{$this->nodeId}' AND order_type=0")->find();
        if (! $orderInfo)
            $this->error('未找到该订单信息');
            // 是否应经支付
        if ($orderInfo['status'] != 2)
            $this->error('该订单还未支付');
        if (empty($orderInfo['request_id']))
            $this->error('该订单还未发码');
        import("@.Vendor.SendCode");
        $req = new SendCode();
        $resp = $req->resend_send($this->node_id, $this->user_id, 
            $orderInfo['request_id']);
        if ($resp === true) {
            $this->success('重发成功');
        } else {
            $this->error('重发失败:' . $resp);
        }
    }
}