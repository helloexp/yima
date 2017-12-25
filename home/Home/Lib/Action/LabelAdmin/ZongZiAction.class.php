<?php

/**
 * 劳动最光荣 @auther zhoukai @time 2015年3月24日15:01:59 @time 2015年3月24日15:01:59
 */
class ZongZiAction extends BaseAction {

    public $_authAccessMap = '*';

    const BATCH_TYPE = 50;
    // 活动类型
    // 活动类型
    public $BATCH_TYPE;

    public $BATCH_NAME;
    // 图片路径
    public $img_path;
    // 旺币有效期，旺币值
    public $wbValidDate = '20150931';

    public $last_create_day = '20150630';

    public $wbNum = 200;

    private $free = null;

    const FREE_END_TIME = 20160630;
    // 劳动节限制c0,c1用户添加时间
    const APPLYEPOS_LABORDAY_TIME = 20150931;
    // 初始化
    public function _initialize() {
        $this->BATCH_TYPE = self::BATCH_TYPE;
        $this->BATCH_NAME = C('BATCH_TYPE_NAME.' . self::BATCH_TYPE);
        if (ACTION_NAME == 'index_pop') {
            $this->_authAccessMap = '*';
        }
        
        $this->free = D('BatchFree', 'Service')->init(
            array(
                'batch_type' => '50'));
        $free_info = $this->free->getFreeInfo();
        if ($free_info !== false) {
            $this->_authAccessMap = '*';
        }

        parent::_initialize();
        $path_arr = C('BATCH_IMG_PATH_NAME');
        $img_path = get_val($path_arr[$this->BATCH_TYPE]) . '/' . $this->node_id . '/';
        $this->img_path = $img_path;
        // 临时路径
        // 只允许仟吉创建活动
        // if($this->node_id!=C('qianji.node_id')){
        // $this->error("你没有权限开展此类型活动！");
        // }
        $tmp_path = 'Upload/img_tmp' . '/' . $this->node_id . '/';
        $this->assign('img_path', $img_path);
        $this->assign('tmp_path', $tmp_path);
        $this->assign('batch_type', $this->BATCH_TYPE);
        $this->assign('batch_name', $this->BATCH_NAME);
        $this->assign('acname', ACTION_NAME);
        $this->assign('node_id', $this->node_id);
    }

    public function afterCheckAuth() {
//         if (ACTION_NAME == 'edit') {
//             if (! $this->hasPayModule('m1')) {
//                 $this->error('尊敬的旺财用户，您需要开通营销工具模块功能或单独支付该营销工具使用费才能编辑该活动！',
//                     array(
//                         '点击开通' => U('Home/Wservice/marketToolVersion')));
//             }
//         }
//         if (ACTION_NAME == 'add') {
//             $result = M('tmarketing_info')->where(
//                 array(
//                     'node_id' => $this->nodeId,
//                     'batch_type' => self::BATCH_TYPE))->find();
//             if ($result) {
//                 $this->error('尊敬的旺财用户，您已创建了该活动！',
//                     array(
//                         '点击查看' => U('MarketActive/Activity/MarketList')));
//             }
//         }
    }
    // 首页
    public function index() {
        $model = M('tmarketing_info');
        $map = array(
            'node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"), 
            'batch_type' => $this->BATCH_TYPE);
        
        $_GET['node_id'] = I('node_id');
        if ($_GET['node_id'] != '') {
            $map['node_id '] = $_GET['node_id'];
        }
        $_GET['key'] = I('key');
        if ($_GET['key'] != '') {
            $map['name'] = array(
                'like', 
                '%' . $_GET['key'] . '%');
        }
        
        $_GET['status'] = I('status');
        if ($_GET['status'] != '') {
            $map['status'] = $_GET['status'];
        }
        $_GET['start_time'] = I('start_time');
        $_GET['end_time'] = I('end_time');
        if ($_GET['start_time'] != '' && $_GET['end_time'] != '') {
            $map['add_time'] = array(
                'BETWEEN', 
                array(
                    $_GET['start_time'] . '000000', 
                    $_GET['end_time'] . '235959'));
        }
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = $model->where($map)->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $model->where($map)
            ->order('id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        if ($list) {
            foreach ($list as $k => $v) {
                $list[$k]['is_mem_batch'] = 'N';
                if ($v['is_cj'] == '1') {
                    $rule_id = M('tcj_rule')->where(
                        array(
                            'batch_type' => $this->BATCH_TYPE, 
                            'batch_id' => $v['id'], 
                            'node_id' => $v['node_id'], 
                            'status' => '1'))->getField('id');
                    $mem_batch = M('tcj_batch')->where(
                        array(
                            'cj_rule_id' => $rule_id, 
                            'member_batch_id' => array(
                                'neq', 
                                '')))->find();
                    if ($mem_batch) {
                        $list[$k]['is_mem_batch'] = 'Y';
                    }
                }
            }
        }
        $query_arr = $model->where($map)->find();
        $node_name = $query_arr['node_name'];
        node_log("首页+端午节");
        $arr_ = C('CHANNEL_TYPE');
        $this->assign('is_cj_button', '1');
        $this->assign('arr_', $arr_);
        // 判断仟吉端午节
        $this->assign('node_name', $node_name);
        $this->assign('qianji_node_id', $this->node_id);
        $this->assign('query_list', $list);
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('nodeList', $this->getNodeTree());
        $this->display(); // 输出模板
    }
    // 添加页
    public function add() {
        $model = M('tmarketing_info');
        
        // 限制c0,c1用户登录时间限制
//         $wc_version = get_wc_version($this->node_id);
//         if ($wc_version == 'v0' || $wc_version == 'v0.5') {
//             if (date('Ymd') > self::FREE_END_TIME) {
//                 $this->error(
//                     '<p style="line-height: 25px;">对不起，本次免费活动已到期。<br>您只要成为标准版用户即可开展旺财平台的所有营销活动以及其它更多功能。<br>如果有其他疑问可以联系客服进行咨询，客服电话：400-882-7770</p>', 
//                     array(
//                         '开通标准版' => U('Home/Wservice/buywc')));
//             }
//             // 参与过限免活动的不能参与此活动
//             $node_map = array(
//                 'node_id' => $this->node_id);
//             $nodeFreeInfo = M('tbatch_free_trace')->where($node_map)->find();
//             if ($nodeFreeInfo && $nodeFreeInfo['batch_type'] != 50) {
//                 $this->error(
//                     '<p style="line-height: 25px;">对不起，本次限免活动只针对未参与过限免活动的企业认证用户。<br>您只要成为标准版用户即可开展旺财平台的所有营销活动的同时还可以有其它更多功能。<br>如果有其他疑问可以联系客服进行咨询，客服电话：400-882-7770</p>', 
//                     array(
//                         '开通标准版' => U('Home/Wservice/buywc')));
//             }
//         }
        
//         $one_map = array(
//             'batch_type' => $this->BATCH_TYPE, 
//             'node_id' => $this->node_id);
//         $info = $model->where($one_map)->count('id');
//         if ($info > 0) {
//             $this->error("只允许创建一个活动", 
//                 array(
//                     '活动列表' => U('MarketActive/Activity/MarketList')));
//         }
        
//         if ($this->wc_version == 'v0.5' && date('Ymd') > $this->last_create_day) {
//             $this->error('不能创建，限免活动截至到15年6月30号');
//         }
        
        // 获取商户名称
        $nodeInfo = M('tnode_info')->where("node_id='{$this->node_id}'")->find();
        $nodeName = $nodeInfo['node_name'];
        $this->assign('node_name', $nodeName);
        $wap_info = <<<HTML
1.用户每天登陆即可获得3次摇一摇机会，只要摇一摇就可以获得精美粽子食材<br>
2.食材一共8种，集齐8种可进行兑奖。兑奖可是100%能中奖的哦<br>
3.用户可以在微信中将食材赠送给小伙伴们，这样就可以更快集齐8种食材啦<br>
HTML;
        $this->assign('row', 
            array(
                'name' => $this->BATCH_NAME, 
                'wap_info' => $wap_info));
            
        // 未付款的时候,需要提示有没有超过60,超过部分要另外收费
        $TwoFestivalAdminModel = D('TwoFestivalAdmin');
        $needShowTips = $TwoFestivalAdminModel->needShowExTips($this->node_id, '');
        $paySettingJson = D('ActivityPayConfig')->getDefaultPayConfigModelByBatchType($this->BATCH_TYPE);
        $paySetting = json_decode($paySettingJson, true);
        $this->assign('configOneActDays', $paySetting['duringTime']);
        $this->assign('exPrice', $paySetting['exPrice']);
        $this->assign('needShowTips', $needShowTips);
        $LimitInfo = $TwoFestivalAdminModel->getLimitInfo($this->node_id, '');
        $this->assign('type', $LimitInfo['type']);
        $this->assign('freeUseLimit', $LimitInfo['freeUseLimit']);
        $this->display();
    }
    
    // 编辑提交页
    public function editSubmit() {
        $model = M('tmarketing_info');
        $data = I('post.');
        if (empty($data['name']) || empty($data['id']))
            $this->error('请填写活动名！');
        $edit_wh = array(
            'node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"), 
            'id' => $data['id']);
        $info = $model->where($edit_wh)->find();
        if (! $info)
            $this->error('未查询到记录');
        $node_id = $info['node_id'];
        $one_map = array(
            'name' => $data['name'], 
            'batch_type' => $this->BATCH_TYPE, 
            'node_id' => $node_id, 
            'id' => array(
                'neq', 
                $data['id']));
        
        $info_ = $model->where($one_map)->find();
        if ($info_) {
            $this->error("活动名称重复");
        }
        // if (empty($data['wap_info'])) {
        // $this->error('活动页面内容不能为空');
        // }
        $map = array(
            'node_id' => $node_id, 
            'id' => $data['id']);
        $data_arr = array(
            'name' => $data['name'], 
            'color' => $data['color'], 
            'wap_title' => $data['wap_title'], 
            'video_url' => $data['video_url'], 
            'start_time' => substr($data['start_time'], 0, 8) . '000000', 
            'end_time' => substr($data['end_time'], 0, 8) . '235959', 
            'memo' => $data['memo'], 
            'other_url' => $data['other_url'], 
            'wap_info' => $data['wap_info'], 
            'node_name' => $data['node_name'], 
            'is_show' => '1');
        $log_img = $data['resp_log_img'];
        $code_img = $data['resp_code_img'];
        $music = $data['resp_music'];
        
        if ($data['is_log_img'] == '1' && ! empty($log_img)) {
            $data_arr['log_img'] = $log_img;
        } else {
            if ($data['is_log_img'] == '0') {
                $data_arr['log_img'] = '';
            }
        }
        
        // 如果状态是付费中(不能让他修改时间);
        $isInPay = D('Order')->isInPay($this->node_id, $data['id']);
        if ($isInPay) {
            $this->error('订单已生成，活动时间不可更改。如需更改时间，请先到<a target="_blank" href="' .
                U('Home/ServicesCenter/myOrder') . '">我的订单</a>中取消订单。');
        }
        // 检查是否有没有超过购买的期限
        $isFreeUser = D('node')->getNodeVersion($this->node_id);
        try {
            D('TwoFestivalAdmin')->checkLimitDay($this->node_id,
                $isFreeUser, $data['id'], $data_arr['start_time'],
                $data_arr['end_time']);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
        $execute = $model->where($map)->save($data_arr);
        if ($execute === false)
            $this->error('系统错误！', 
                array(
                    '返回活动列表' => U('index')));
            
            // 完成赠送任务
            // 如果原来赠送失败了，再来一次
        $msg = '更新成功<br/>';
        $id = $info['id'];
        if ($this->wc_version == 'v0.5' && ($info['defined_two_name'] == '0' ||
             $info['defined_two_name'] == '1')) {
            $wbResult = $this->_sendWcMoney();
            if ($wbResult === false) {
                // 更新状态
                $model->where(array(
                    'id' => $id))->save(
                    array(
                        'defined_two_name' => '0'));
            } elseif ($wbResult['code'] != 0) {
                // 更新状态
                $model->where(array(
                    'id' => $id))->save(
                    array(
                        'defined_two_name' => '1'));
                log_write('赠送旺币失败' . print_r($wbResult, true));
            } else {
                // 更新状态
                $model->where(array(
                    'id' => $id))->save(
                    array(
                        'defined_two_name' => '2'));
                $msg .= '<br>' . $wbResult['msg'];
                log_write('赠送旺币成功');
            }
        }
        
        node_log($this->BATCH_NAME . '编辑|活动id:' . $data['id']);
        $this->success($msg . '<br/><br/>', 
            array(
                '返回活动列表' => U('MarketActive/Activity/MarketList', 
                    array(
                        'batchtype' => 50))));
    }
    
    // 状态修改
    public function editStatus() {
        $batchId = I('post.batch_id', null);
        $status = I('post.status', null);
        if (is_null($batchId) || is_null($status)) {
            $this->error('缺少必要参数');
        }
        $result = M('tmarketing_info')->where(
            "node_id in ( {$this->nodeIn()} ) AND id={$batchId}")->find();
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
            node_log($this->BATCH_NAME . '状态更改|活动id:' . $batchId);
            $this->success('更新成功', 
                array(
                    '返回' => U('News/index')));
        } else {
            $this->error('更新失败');
        }
    }

    public function winningExport() {
        $batchId = I('batch_id', null);
        if (is_null($batchId))
            $this->error('缺少参数');
        
        $edit_wh = array(
            'node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"), 
            'id' => $batchId);
        $nodeInfo = M('tmarketing_info')->where($edit_wh)->find();
        if (! $nodeInfo)
            $this->error('未查询到记录！');
        
        $fileName = $nodeInfo['name'] . '-' . date('Y-m-d') . '-中奖名单.csv';
        $fileName = iconv('utf-8', 'gbk', $fileName);
        
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        $start_num = 0;
        $page_count = 5000;
        $cj_title = "参与号,接收手机号,参与时间,中奖状态,奖品等级名称,奖品名称,渠道名称\r\n";
        echo $cj_title = iconv('utf-8', 'gbk', $cj_title);
        for ($j = 1; $j < 100; $j ++) {
            $page = ($j - 1) * $page_count;
            $sql = "SELECT a.mobile,case when send_mobile = '13900000000' then '' else send_mobile end send_mobile,a.add_time,a.status,a.prize_level,c.name,d.batch_name,e.name as channel_name
					FROM tcj_trace a left join tcj_batch b on a.rule_id =b.id
					left join tchannel e on a.channel_id= e.id
					left join tcj_cate c  on b.cj_cate_id= c.id
					left join tbatch_info d on b.b_id=d.id
					 WHERE a.batch_id={$batchId} AND
				     a.node_id ='" . $nodeInfo['node_id'] . "'
					 ORDER by a.status DESC LIMIT {$page},{$page_count}";
            
            $query = M()->query($sql);
            if (! $query)
                exit();
            foreach ($query as $v) {
                $cj_status = iconv('utf-8', 'gbk', 
                    $v['status'] == "1" ? '未中奖' : '中奖');
                $cj_cate = iconv('utf-8', 'gbk', $v['name']);
                $jp_name = iconv('utf-8', 'gbk', $v['batch_name']);
                $channel_name = iconv('utf-8', 'gbk', $v['channel_name']);
                echo "{$v['mobile']},{$v['send_mobile']}," .
                     date('Y-m-d H:i:s', strtotime($v['add_time'])) .
                     ",{$cj_status},{$cj_cate},{$jp_name},{$channel_name}\r\n";
            }
        }
    }
    
    // 编辑页
    public function edit() {
        $model = M('tmarketing_info');
        $id = I('get.id');
        if (empty($id))
            $this->error('错误参错');
        $map = array(
            'node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"), 
            'id' => $id);
        $query_arr = $model->where($map)->find();
        if (! $query_arr)
            $this->error('未查询到数据！');
        $this->assign('node_name', $query_arr['node_name']);
        $this->assign('row', $query_arr);
        
        // 未付款的时候,需要提示有没有超过60,超过部分要另外收费
        $TwoFestivalAdminModel = D('TwoFestivalAdmin');
        $needShowTips = $TwoFestivalAdminModel->needShowExTips($this->node_id, $id);
        $paySettingJson = D('ActivityPayConfig')->getDefaultPayConfigModelByBatchType($this->BATCH_TYPE);
        $paySetting = json_decode($paySettingJson, true);
        $this->assign('configOneActDays', $paySetting['duringTime']);
        $this->assign('exPrice', $paySetting['exPrice']);
        $this->assign('needShowTips', $needShowTips);
        $LimitInfo = $TwoFestivalAdminModel->getLimitInfo($this->node_id, $id);
        $this->assign('type', $LimitInfo['type']);
        $this->assign('freeUseLimit', $LimitInfo['freeUseLimit']);
        $this->display('add');
    }
    // 通过微信登录ID 查询用户中奖食材
    public function has_food() {
        // 获取用户的wx_user_id
        $wx_user_id = $this->wxid;
        $map = array(
            "wx_user_id" => $wx_user_id, 
            "type" => 0, 
            "node_id" => $this->node_id, 
            "batch_id" => $this->batch_id);
        $traceInfo = M("twx_zongzi_score")->where($map)->find();
        if ($traceInfo) {
            return $traceInfo;
        }
    }

    public function foodlist() {
        // 分享我的食材，URL里面带的wx_user_id
        $marketInfo = $this->marketInfo;
        $shareUrl = U('share', 
            array(
                'id' => $this->id, 
                'wx_user_id' => $this->wxid), '', '', TRUE);
        $wx_share_config = D('WeiXin', 'Service')->getWxShareConfig();
        $shareArr = array(
            'config' => $wx_share_config, 
            'link' => $shareUrl, 
            'title' => "仟吉端午全民摇摇摇，一起”粽“大奖！", 
            'desc' => "美食的最大诱惑在于用心。2015，仟吉“吉鹿”再一次踏遍全国，为您寻找美味芳粽！", 
            'imgUrl' => C('CURRENT_DOMAIN') .
                 'Home/Public/Label/Image/qianji/Item/qianji_share.jpg');
        $traceInfo = $this->has_food();
        $fond_count = 0;
        if (! empty($traceInfo)) {
            $foods_number = json_decode($traceInfo["foods_number"], true);
            if ($foods_number) {}
            foreach ($foods_number as $key => $val) {
                if ($val > 0) {
                    $fond_count ++;
                }
            }
        }
        // 判断用户是否已经领取过材料
        
        $map2 = array(
            "wx_user_id" => $this->wxid, 
            "node_id" => $this->node_id, 
            'param1' => 1, 
            'batch_id' => $this->batch_id);
        $res1 = M("twx_duanwu_trace")->where($map2)->find();
        if ($res1) {
            $this->assign("has_flag", 1);
        }
        $this->assign('wx_share_config', $wx_share_config);
        $this->assign('marketInfo', $marketInfo);
        $this->assign('shareData', $shareArr);
        $this->assign('foods_count', $fond_count);
        $this->assign('foods_count1', 8 - $fond_count);
        $this->assign('foods_number', $foods_number);
        $this->display();
    }
    
    // 添加提交页
    public function addSubmit() {
//         if ($this->wc_version == 'v0.5' && date('Ymd') > $this->last_create_day) {
//             $this->error('不能创建，限免活动截至到15年6月30号');
//         }
        
        $model = M('tmarketing_info');
        $data = I('post.');
        
        if (empty($data['name']))
            $this->error('请填写活动名称！');
            // if (empty($data['wap_title']))
            // $this->error('请填写wap页面标题！');
        if (empty($data['start_time']))
            $this->error('请填写标签可用开始时间！');
        if (empty($data['end_time']))
            $this->error('请填写标签可用结束时间！');
            // if (empty($data['wap_info']))
            // $this->error('活动页面内容不能为空');
//         $one_map = array(
//             'batch_type' => $this->BATCH_TYPE, 
//             'node_id' => $this->node_id);
//         $info = $model->where($one_map)->count('id');
//         if ($info > 0) {
//             $this->error("只允许创建一个活动");
//         }
        $data_arr = array(
            'name' => $data['name'], 
            'color' => $data['color'], 
            'wap_title' => $data['wap_title'], 
            'log_img' => $data['resp_log_img'], 
            'music' => $data['resp_music'], 
            'video_url' => $data['video_url'], 
            'start_time' => substr($data['start_time'], 0, 8) . '000000', 
            'end_time' => substr($data['end_time'], 0, 8) . '235959', 
            'memo' => $data['memo'], 
            'node_id' => $this->node_id, 
            'add_time' => date('YmdHis'), 
            'status' => '1', 
            'other_url' => $data['other_url'], 
            'wap_info' => $data['wap_info'], 
            'node_name' => $data['node_name'], 
            'is_cj' => '0', 
            'page_style' => $data['page_style'], 
            'bg_style' => $data['bg_style'], 
            'bg_pic' => $data['resp_bg_img'], 
            'batch_type' => $this->BATCH_TYPE, 
            'is_show' => '1', 
            'join_mode' => 0, 
            'member_join_flag' => 0, 
            'fans_collect_url' => $data['fans_collect_url']);
        $resp_id = $model->add($data_arr);
        if (! $resp_id) {
            $this->error('系统错误！', 
                array(
                    '返回活动列表' => U('index')));
        }
        // 顺便发布到多乐互动专用渠道上
        $bchId = D('MarketCommon')->chPublish($this->nodeId,$resp_id);
        if($bchId === false){
            $this->error('发布到渠道失败');
        }
        $ser = D('TmarketingInfo');
        $arr = array(
            'node_id' => $this->nodeId, 
            'batch_type' => $this->BATCH_TYPE, 
            'batch_id' => $resp_id);
        $ser->init($arr);
        $ser->sendBatch();
        $wbResult = $this->_sendWcMoney($this->wbNum);
        if ($wbResult !== false) {
            $this->free->addTrace();
        }
        
        $msg = '添加成功！';
        if ($wbResult === false) {
            // 更新状态
            $model->where(array(
                'id' => $resp_id))->save(
                array(
                    'defined_two_name' => '0'));
        } elseif ($wbResult['code'] != 0) {
            // 更新状态
            $model->where(array(
                'id' => $resp_id))->save(
                array(
                    'defined_two_name' => '1'));
            log_write('赠送旺币失败' . print_r($wbResult, true));
        } else {
            // 更新状态
            $model->where(array(
                'id' => $resp_id))->save(
                array(
                    'defined_two_name' => '2'));
            $msg .= '<br>' . $wbResult['msg'];
            log_write('赠送旺币成功');
        }
        
        node_log($this->BATCH_NAME . '添加|活动名:' . $data['name']);
        
        $this->success($msg . '<br><br><br>', 
            array(
                '设置抽奖' => U('LabelAdmin/ZongZiCjSet/index', 
                    array(
                        'batch_id' => $resp_id)), 
                '发布更多渠道' => U('LabelAdmin/BindChannel/index',
                    array(
                        'batch_type' => $this->BATCH_TYPE, 
                        'batch_id' => $resp_id)), 
                '返回活动列表页' => U('MarketActive/Activity/MarketList')));
    }

    /**
     * 送旺币,一定要private
     *
     * @return array
     */
    private function _sendWcMoney() {
        $free_info = $this->free->getFreeInfo();
        if ($free_info === false) {
            return false;
        }
        
        $node_id = $this->node_id;
        
        // 开始赠送旺币
        $service = D('RemoteRequest', 'Service');
        $TransactionID = date('ymdHis') . mt_rand(1000, 9999);
        $SystemID = C('YZ_SYSTEM_ID');
        $BeginTime = date('Ymd');
        $EndTime = dateformat($free_info['wb_valid_time'], 'Ymd');
        $nodeInfo = M('tnode_info')->where(
            array(
                'node_id' => $this->node_id))->find();
        $data = array(
            'SystemID' => $SystemID, 
            'TransactionID' => $TransactionID, 
            'ContractID' => $nodeInfo['contract_no'], 
            'WbType' => '1', 
            'BeginTime' => $BeginTime, 
            'EndTime' => $EndTime, 
            'ReasonID' => $free_info['wb_reason_id'],  //
            'Amount' => 0, 
            'WbNumber' => $free_info['wb_num'], 
            'Remark' => '端午节限免');
        $data = array(
            'SetWbReq' => $data);
        $yzResult = $service->requestYzServ($data);
        if (! $yzResult || ! $yzResult['Status']) {
            return array(
                'code' => '9', 
                'msg' => '赠送旺币失败,网络正忙[01]');
        }
        if ($yzResult['Status']['StatusCode'] != '0') {
            return array(
                'code' => '9', 
                'msg' => '赠送旺币失败,原因：' . $yzResult['Status']['StatusText']);
        }
        
        $this->free->addTrace();
        return array(
            'code' => '0', 
            'msg' => '<h2>已赠送您' . $free_info['wb_number'] . '旺币</h2>' .
                 '<p>旺币有效期为至' . dateformat($free_info['wb_valid_time'], 'Y-m-d') .
                 '<br/>' .
                 '可以在<a href="index.php?g=Home&m=AccountInfo&a=index">个人帐户中心</a>查看并使用</p><br>');
    }
}