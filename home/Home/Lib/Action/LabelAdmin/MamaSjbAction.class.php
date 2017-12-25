<?php

/**
 * @@@ mamajie @@@ add dongdong @@@ time 2015/04/10 15:22
 */
class MamaSjbAction extends BaseAction {

    const END_FREE_TIME = '20150630';

    const REASON_ID = '29';
    // 旺币原类
    // 活动类型
    public $BATCH_TYPE = '46';
    // 图片路径
    public $img_path;
    // 旺币有效期
    public $wbValidDate = '20150630';

    public $wbNum = 200;
    
    // 初始化
    public function _initialize() {
        if (ACTION_NAME == 'index_pop') {
            $this->_authAccessMap = '*';
        }
        parent::_initialize();
        $path_arr = C('BATCH_IMG_PATH_NAME');
        $img_path = $path_arr[$this->BATCH_TYPE] . '/' . $this->node_id . '/';
        $this->img_path = $img_path;
        // 临时路径
        $tmp_path = get_upload_url('img_tmp' . '/' . $this->node_id . '/');
        $this->assign('tmp_path', $tmp_path);
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
//             if (time() > strtotime(self::END_FREE_TIME) &&
//                  (! $this->hasPayModule('m1'))) {
//                 $this->error(
//                     '对不起，本次活动免费时间已过。<br/>您只要购买营销工具礼包或单独支付该营销工具使用费即可创建该活动！', 
//                     array(
//                         '点击开通' => U('Home/Wservice/marketToolVersion')));
//             }
//             $result = M('tmarketing_info')->where(
//                 array(
//                     'node_id' => $this->nodeId, 
//                     'batch_type' => $this->BATCH_TYPE))->find();
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
        $list = $model->where($map)->select();
        
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
        // 开通免费链接EPOS，针对c0和C1用户显示
        $wc_version = get_wc_version($this->node_id);
        if ($wc_version == 'v0.5' || $wc_version == 'v0') {
            if (date('Ymd') <= self::END_FREE_TIME) {
                $model = M('tmarketing_info');
                $map = array(
                    'node_id' => $this->node_id, 
                    'batch_type' => $this->BATCH_TYPE);
                $_info = $model->where($map)->find();
                if (! empty($_info)) {
                    $this->assign('MamaSjb_apply_Epos', '1');
                }
            }
        }
        node_log("首页+母亲节");
        
        $arr_ = C('CHANNEL_TYPE');
        $this->assign('is_cj_button', '1');
        $this->assign('arr_', $arr_);
        $this->assign('query_list', $list);
        // $this->assign('page', $show);
        $this->assign('nodeList', $this->getNodeTree());
        $this->display(); // 输出模板
    }
    
    // 添加页
    public function add() {
        $model = M('tmarketing_info');
//         $send_wb_flag = 1;
//         $this->_checkFree();
//         $wc_version = get_wc_version($this->node_id);
//         // 只能赠送给c1认证用户
//         if ($wc_version != 'v0.5') {
//             $send_wb_flag = 0;
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
        $this->assign('send_wb_flag', 0);
        
        $nodeInfo = get_node_info($this->node_id);
        $this->assign('node_name', $nodeInfo['node_name']);
        $this->assign('node_logo', $nodeInfo['head_photo']);
        
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
    
    // 添加提交页
    public function addSubmit() {
        $model = M('tmarketing_info');
        $data = I('post.');
//         if ($this->_checkOneAddBatch() === true)
//             $this->error('您已经创建过该活动了。');
        
//         $this->_checkFree();
        
        $baseActivityModel = D('BaseActivity', 'Service');
        $result = $baseActivityModel->activityAddDataBaseVirefy($data, 
            $this->BATCH_TYPE, $this->img_path);
        
        $data_arr = array(
            'name' => $data['name'], 
            'color' => $data['color'], 
            'wap_title' => $data['wap_title'], 
            'log_img' => $result['log_img'], 
            'music' => $data['resp_music'], 
            'video_url' => $data['video_url'], 
            'start_time' => substr($data['start_time'], 0, 8) . '000000', 
            'end_time' => substr($data['end_time'], 0, 8) . '235959', 
            'memo' => $data['memo'], 
            'node_id' => $this->node_id, 
            'add_time' => date('YmdHis'), 
            'status' => '1', 
            // 'size'=>$data['size'],
            // 'code_img'=>$data['resp_code_img'],
            'sns_type' => $result['sns_type'], 
            'other_url' => $data['other_url'], 
            'wap_info' => $data['wap_info'], 
            'node_name' => $data['node_name'], 
            'is_cj' => '0', 
            'page_style' => $data['page_style'], 
            'bg_style' => $data['bg_style'], 
            'bg_pic' => $result['bg_img'], 
            'batch_type' => $this->BATCH_TYPE, 
            'is_show' => '1');
        $resp_id = $model->add($data_arr);
        if (! $resp_id) {
            $this->error('系统错误！', 
                array(
                    '返回母亲节' => U('MarketActive/Activity/MarketList')));
        }
        
//         $wbResult = $this->_sendWcMoney($this->wbNum);
//         if ($wbResult === false) {
//             // 更新状态
//             $model->where(array(
//                 'id' => $resp_id))->save(
//                 array(
//                     'defined_one_name' => '0'));
//         } elseif ($wbResult['code'] != 0) {
//             // 更新状态
//             $model->where(array(
//                 'id' => $resp_id))->save(
//                 array(
//                     'defined_one_name' => '1'));
//             log_write('赠送旺币失败' . print_r($wbResult, true));
//         } else {
//             // 更新状态
//             $model->where(array(
//                 'id' => $resp_id))->save(
//                 array(
//                     'defined_one_name' => '2'));
//             log_write('赠送旺币成功');
//         }
        
        node_log('母亲节添加|活动名:' . $data['name']);
        // 顺便发布到多乐互动专用渠道上
        $bchId = D('MarketCommon')->chPublish($this->nodeId,$resp_id);
        if($bchId === false){
            $this->error('发布到渠道失败');
        }
        $this->success('添加成功！', 
            array(
                '设置抽奖' => U('LabelAdmin/CjSet/index', 
                    array(
                        'batch_id' => $resp_id)), 
                '发布到更多渠道' => U('LabelAdmin/BindChannel/index',
                    array(
                        'batch_type' => $this->BATCH_TYPE, 
                        'batch_id' => $resp_id)), 
                '返回活动列表页' => U('MarketActive/Activity/MarketList')));
    }
    
    // 编辑页
    public function edit() {
        $id = I('get.id');
        if (empty($id))
            $this->error('错误参错');
        
        $baseActivityModel = D('BaseActivity', 'Service');
        $query_arr = $baseActivityModel->checkactivityexist($id, 
            $this->BATCH_TYPE, $this->nodeIn());
        
        if ($query_arr['log_img'] == '') {
            $nodeInfo = get_node_info($this->node_id);
            $this->assign('head_photo', get_upload_url($nodeInfo['head_photo']));
            $this->assign('node_logo', $nodeInfo['head_photo']);
        }
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
        $this->display();
    }
    
    // 编辑提交页
    public function editSubmit() {
        $model = M('tmarketing_info');
        $data = I('post.');
        $baseActivityModel = D('BaseActivity', 'Service');
        $baseActivityModel->checkisactivityheadinfowrite($data);
        $baseActivityModel->checkisactivitynamesame($data['name'], 
            $this->BATCH_TYPE, $data['id']);
        
        $baseActivityModel = D('BaseActivity', 'Service');
        
        // 社交分享
        if (! empty($data['sns_type'])) {
            $sns_type = $baseActivityModel->implodearray($data['sns_type']);
        } else {
            $sns_type = '';
        }
        
        $query_arr = $baseActivityModel->checkactivityexist($data['id'], 
            $this->BATCH_TYPE, $this->nodeIn());
        $node_id = $query_arr['node_id'];
        
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
            // 'size'=>$data['size'],
            'sns_type' => $sns_type, 
            'other_url' => $data['other_url'], 
            'wap_info' => $data['wap_info'], 
            'node_name' => $data['node_name'], 
            'page_style' => $data['page_style'], 
            'bg_style' => $data['bg_style'], 
            'bg_pic' => $data['resp_bg_img'], 
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
        if ($data['is_music'] == '1' && ! empty($music)) {
            $data_arr['music'] = $music;
        } else {
            if ($data['is_music'] == '0') {
                $data_arr['music'] = '';
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
                    '返回母亲节' => U('index')));
        
        node_log('母亲节编辑|活动id:' . $data['id']);
        redirect(
            U('MarketActive/Activity/MarketList'));
    }
    
    // 状态修改
    public function editStatus() {
        $data = I('post.');
        $baseActivityModel = D('BaseActivity', 'Service');
        $result = $baseActivityModel->changeTheActivityStatus($data, 
            $this->BATCH_TYPE, $this->nodeIn());
        
        if ($result) {
            node_log('母亲节状态更改|活动id:' . $data['batch_id']);
            $this->success('更新成功', 
                array(
                    '返回' => U('MamaSjb/index')));
        } else {
            $this->error('更新失败');
        }
    }
    
    // 数据导出
    public function export() {
        $status = I('status', null, 'mysql_real_escape_string');
        // 查询条件组合
        $where = "WHERE batch_type='" . $this->BATCH_TYPE . "'";
        if (! empty($_POST)) {
            $filter = array();
            $condition = array_map('trim', I('post.'));
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
    	 tmarketing_info {$where} AND node_id in ({$this->nodeIn()})";
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

    public function winningExport() {
        $batchId = I('batch_id', null, 'mysql_real_escape_string');
        $status = I('status', null, 'mysql_real_escape_string');
        if (is_null($batchId))
            $this->error('缺少参数');
        
        $edit_wh = array(
            'node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"), 
            'id' => $batchId);
        $node_id = M('tmarketing_info')->where($edit_wh)->getField('node_id');
        if (! $node_id)
            $this->error('未查询到记录！');
        
        $sql = "SELECT mobile,add_time,
			    	CASE status WHEN '1' THEN '未中奖' ELSE '中奖' END status,prize_level
			    	FROM
			    	tcj_trace WHERE batch_id='{$batchId}' AND batch_type='{$this->BATCH_TYPE}' AND node_id = '{$node_id}'
			    	ORDER by status DESC";
        $countSql = "SELECT COUNT(*) as count FROM tcj_trace WHERE batch_id='{$batchId}' AND batch_type='{$this->BATCH_TYPE}' AND node_id='{$node_id}' ";
        $cols_arr = array(
            'mobile' => '手机号', 
            'add_time' => '中奖时间', 
            'status' => '是否中奖', 
            'prize_level' => '奖品等级');
        // 获取活动名称
        $batchName = M('tmarketing_info')->where(
            "id='{$batchId}' AND node_id ='{$node_id}'")->getField('name');
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

    /**
     * 检查有没有创建活动 创建了，就返回 true
     */
    private function _checkOneAddBatch() {
        $fruit = M('tmarketing_info')->where(
            array(
                'batch_type' => 46, 
                'node_id' => $this->node_id))->find();
        
        return empty($fruit) ? false : true;
    }

    /**
     * 送旺币,一定要private
     *
     * @return array
     */
    private function _sendWcMoney($num) {
        $node_id = $this->node_id;
        $wc_version = get_wc_version($node_id);
        // 只能赠送给c1用户,认证用户增加旺币
        if ($wc_version != 'v0.5') {
            log_write($this->node_id . '[' . $wc_version . ']机构不赠送旺币');
            return false;
        }
        // 开始赠送旺币
        if ($num) {
            $service = D('RemoteRequest', 'Service');
            $TransactionID = date('ymdHis') . mt_rand(1000, 9999);
            $SystemID = C('YZ_SYSTEM_ID');
            $BeginTime = date('Ymd');
            $EndTime = $this->wbValidDate;
            $nodeInfo = get_node_info($this->node_id);
            $data = array(
                'SystemID' => $SystemID, 
                'TransactionID' => $TransactionID, 
                'ContractID' => $nodeInfo['contract_no'], 
                'WbType' => '1', 
                'BeginTime' => $BeginTime, 
                'EndTime' => $EndTime, 
                'ReasonID' => self::REASON_ID,  //
                'Amount' => 0, 
                'WbNumber' => $num, 
                'Remark' => '妈妈我爱你');
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
            return array(
                'code' => '0', 
                'msg' => '<h2>已赠送您' . $num . '旺币</h2>' . '<p>有效期为' .
                     dateformat($BeginTime, 'Y-m-d') . '到' .
                     dateformat($EndTime, 'Y-m-d') . '<br/>' .
                     '可以在<a href="index.php?g=Home&m=AccountInfo&a=index">个人帐户中心</a>查看并使用</p>');
        }
        return array(
            'code' => 1, 
            'msg' => "未送旺币");
    }

    /**
     * 首页弹框，弹到5月9号
     */
    public function index_pop() {
        if ($this->nodeQsCheckStatus != 2) {
            $this->assign('pop_ac_url', 
                U('Home/AccountInfo/index', 
                    array(
                        'show_auth' => 'true')));
            $this->assign('pop_ac_text', '马上认证');
        } else {
            $this->assign('pop_ac_url', U('LabelAdmin/MamaSjb/index'));
            $this->assign('pop_ac_text', '马上创建活动');
        }
        $this->display();
    }
    // 校验是否限免
    public function _checkFree() {
        $model = M('tmarketing_info');
        // 限制c0,c1用户登录时间限制
        $wc_version = get_wc_version($this->node_id);
        if ($wc_version == 'v0' || $wc_version == 'v0.5') {
            if (date('Ymd') > self::END_FREE_TIME) {
                $this->error(
                    '<p style="line-height: 25px;">对不起，本次免费活动已到期。<br>您只要购买营销工具礼包即可开展旺财平台的所有营销活动以及其它更多功能。<br>如果有其他疑问可以联系客服进行咨询，客服电话：400-882-7770</p>', 
                    array(
                        '购买营销工具礼包' => U('Home/Wservice/marketToolVersion')));
            }
            // 参与过春节打炮限免活动的以及劳动最光荣非c2用户无法参加此活动
            $firecrackers_map = array(
                'node_id' => $this->node_id, 
                'batch_type' => array(
                    'in', 
                    '42,45'));
            $firecrackers_times = $model->where($firecrackers_map)->find();
            if (! empty($firecrackers_times)) {
                $this->error(
                    '<p style="line-height: 25px;">对不起，本次限免活动只针对未参与过春节打炮活动以及劳动最光荣活动的企业认证用户。<br>您只要购买营销工具礼包即可开展旺财平台的所有营销活动的同时还可以有其它更多功能。<br>如果有其他疑问可以联系客服进行咨询，客服电话：400-882-7770</p>', 
                    array(
                        '购买营销工具礼包' => U('Home/Wservice/marketToolVersion')));
            }
        }
    }
}
