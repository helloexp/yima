<?php

/* 赛事竞猜 */
class MatchGuessAction extends BaseAction {
    // 活动类型
    public $BATCH_TYPE;
    // 图片路径
    public $img_path;
    
    // 初始化
    public function _initialize() {
        parent::_initialize();
        import('@.Vendor.CommonConst') or die('include file fail.');
        $this->BATCH_TYPE = CommonConst::BATCH_TYPE_EUROCUP;
        $this->assign('batch_type', $this->BATCH_TYPE);
        $path_arr = C('BATCH_IMG_PATH_NAME');
        $img_path = $path_arr[$this->BATCH_TYPE] . '/' . $this->node_id . '/';
        $this->img_path = $img_path;
        
        // 临时路径
        $tmp_path = 'Upload/img_tmp' . '/' . $this->node_id . '/';
        $this->assign('img_path', $img_path);
        $this->assign('tmp_path', $tmp_path);
    }
    
    // 新增
    public function add() {
        $nodeInfo = get_node_info($this->node_id);
        $this->assign('node_name', $nodeInfo['node_name']);
        $this->assign('node_logo', $nodeInfo['head_photo']);
        
        $nodeName = $nodeInfo['node_name'];
        node_log('赛事竞猜|商户名:' . $nodeName);
        $this->assign('row', 
            array(
                'wap_info' => '请预测当场比赛的胜负平结果，猜中即有可能获得商家提供的相关奖品！', 
                'node_name' => $nodeName));
        $this->assign('isReEdit', '0');
        $this->display();
    }
    
    // 新增提交
    public function addSubmit() {
        $model = M('tmarketing_info');
        $data = I('post.');
        
        // 初始化默认参数
        $sessionNumber = I('post.sessionNumber');
        if (! $sessionNumber) {
            $this->error('请先选择比赛场次');
        }
        // 查询场次信息
        $sessionInfo = M('tworld_cup_events')->find($sessionNumber);
        if (! $sessionInfo) {
            $this->error("场次不存在");
        }
        $sessionTitle = I('post.sessionTitle');
        if ($sessionInfo['begin_time'] <= date('YmdHis')) {
            $this->error("该场次比赛已经开始，开始时间为:" . $sessionInfo['begin_time']);
        }
        
        $data['start_time'] = date('YmdHis');
        $data['end_time'] = C('WB_LIMIT_FOR_EUROP_CUP') . '235959';
        
        // 奖品设置
        $data['jp_type'] = '0';
        $data['chance'] = '0';
        $data['day_goods_count'] = '0';
        
        if (empty($data['name']))
            $this->error('请填写活动名称！');
        
        $data['wap_title'] = $data['name'];
        if (empty($data['wap_title']))
            $this->error('请填写wap页面标题！');
            // if(empty($data['start_time']))
            // $this->error('请填写标签可用开始时间！');
            // if(empty($data['end_time']))
            // $this->error('请填写标签可用结束时间！');
        if (empty($data['wap_info']))
            $this->error('活动页面内容不能为空');
        
        $one_map = array(
            'name' => $data['name'], 
            'batch_type' => $this->BATCH_TYPE, 
            'node_id' => $this->node_id);
        $info = $model->where($one_map)->count('id');
        if ($info > 0) {
            $this->error("活动名称重复");
        }
        
        // 场次是否重复
        $one_map = array(
            'defined_one_name' => $sessionNumber, 
            'batch_type' => $this->BATCH_TYPE, 
            'node_id' => $this->node_id, 
            'status' => '1');
        $count = $model->where($one_map)->find();
        if ($count > 0) {
            $this->error("该活动不支持同一场比赛开多次活动，请选择其他场次的比赛！");
        }
        
        // logo
        if ($data['resp_log_img'] != '' && $data['is_logo_img'] == 1) {
            $log_img = $data['resp_log_img'];
        }
        $hasBuyAllGame = D('BindChannel')->hasBuyAllGame($this->node_id);
        $initPlayMode = ($sessionInfo['events_type'] > 1) ? 1 : 0;//从四分之一开始支持猜比分
        $data_arr = array(
            'name' => $data['name'], 
            'color' => $data['color'], 
            'wap_title' => $data['wap_title'], 
            'log_img' => $log_img, 
            'music' => $data['resp_music'], 
            'video_url' => $data['video_url'], 
            'start_time' => substr($data['start_time'], 0, 8) . '000000', 
            'end_time' => substr($data['end_time'], 0, 8) . '235959', 
            'memo' => $data['memo'], 
            'node_id' => $this->node_id, 
            'add_time' => date('YmdHis'), 
            'pay_status' => $hasBuyAllGame ? '1' : '0',//未付款（买了营销活动打包的不用认定这个字段）
            'status' => '1', 
            'config_data' => serialize(['wx_auth_type' => '0']), //默认微信翼码授权
            'join_mode' => '1', //微信参与
            'size' => $data['size'], 
            'code_img' => $data['resp_code_img'], 
            'sns_type' => '', 
            'other_url' => $data['other_url'], 
            'wap_info' => $data['wap_info'], 
            'node_name' => $data['node_name'], 
            'is_cj' => '1', 
            'page_style' => $data['page_style'], 
            'bg_style' => $data['bg_style'], 
            'bg_pic' => $data['resp_bg_img'], 
            'batch_type' => $this->BATCH_TYPE, 
            'is_show' => '1', 
            // 自定义字段
            'defined_one_name' => $sessionNumber,  // 场次号
            'defined_two_name' => $sessionTitle, 
            'defined_three_name' => $initPlayMode,
        );
        // 开启事物
        $tranDb = M();
        $tranDb->startTrans();
        
        $resp_id = $model->add($data_arr);
        if (! $resp_id) {
            $tranDb->rollback();
            $this->error('系统错误！[01]', 
                array(
                    '返回活动列表' => U('MarketActive/Activity/index')));
        }
        
        // 如果是新增把默认的抽奖配置填上
        $ruleParam = array(
            'batch_type' => CommonConst::BATCH_TYPE_EUROCUP,
            'batch_id' => $resp_id,
            'jp_set_type' => 2,  // 1单奖品2多奖品
            'node_id' => $this->node_id,
            'add_time' => date('YmdHis'),
            'status' => '1',
            'phone_total_count' => '',
            'phone_day_count' => '',
            'phone_total_part' => '',
            'phone_day_part' => '',
            'cj_button_text' => '开始抽奖',
            'cj_resp_text' => '恭喜您！中奖了',  // 中奖提示信息
            'param1' => '',
            'total_chance' => '100',
            'no_award_notice' => '很遗憾！未中奖');
        $flag = M('tcj_rule')->add($ruleParam);
        if (! $flag) {
            $model->rollback();
            log_write('新增默认抽奖失败!');
            $this->error('新增默认抽奖失败!');
        }
        
        
        $cateData = array(
            'batch_type' => CommonConst::BATCH_TYPE_EUROCUP,
            'batch_id'   => $resp_id,
            'node_id'    => $this->node_id,
            'cj_rule_id' => $flag,
            'name'       => '一等奖',
            'add_time'   => date('YmdHis'),
            'status'     => '1',
            'sort'       => '1');
        $cat_id = M('tcj_cate')->add($cateData);
        if (!$cat_id) {
            $model->rollback();
            $this->error('新增默认抽奖失败!');
        }
        
        $tranDb->commit();
        $this->redirect('ZtWorldcup/MatchGuess/setPrize',['isReEdit' => '0', 'm_id' => $resp_id]);
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
        $isReEdit = I('isReEdit', '1');
        $this->assign('isReEdit', $isReEdit);
        $this->display('add');
    }
    
    // 编辑提交页
    public function editSubmit() {
        $model = M('tmarketing_info');
        $data = I('post.');
        
        // 初始化默认参数
        $sessionNumber = I('post.sessionNumber');
        if (! $sessionNumber) {
            $this->error('请先选择比赛场次');
        }
        $sessionTitle = I('post.sessionTitle');
        // 查询场次信息
        $sessionInfo = M('tworld_cup_events')->find($sessionNumber);
        if (! $sessionInfo) {
            $this->error("场次不存在");
        }
        if ($sessionInfo['begin_time'] <= date('YmdHis')) {
            $this->error("该场次比赛已经开始，开始时间为:" . $sessionInfo['begin_time']);
        }
        
        $data['start_time'] = date('YmdHis');
        $data['end_time'] = C('WB_LIMIT_FOR_EUROP_CUP') . '235959';
        
        if (empty($data['name']) || empty($data['id']))
            $this->error('请填写活动名！');
        
        $baseActivityModel = D('BaseActivity', 'Service');
        $baseActivityModel->checkisactivitynamesame($data['name'], 
            $this->BATCH_TYPE, $data['id']);
        $query_arr = $baseActivityModel->checkactivityexist($data['id'], 
            $this->BATCH_TYPE, $this->nodeIn());
        $node_id = $query_arr['node_id'];
        
        if (empty($data['wap_info']))
            $this->error('活动页面内容不能为空');
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
            'size' => $data['size'], 
            'other_url' => $data['other_url'], 
            'wap_info' => $data['wap_info'], 
            'node_name' => $data['node_name'], 
            'page_style' => $data['page_style'], 
            'bg_style' => $data['bg_style'], 
            'bg_pic' => $data['resp_bg_img'], 
            'is_show' => '1'
        );
        // 'defined_one_name'=>$sessionNumber,
        // 'defined_two_name'=>$sessionTitle,
        
        $log_img = $data['resp_log_img'];
        $code_img = $data['resp_code_img'];
        $music = $data['resp_music'];
        if ($data['is_code_img'] == '1' && ! empty($code_img)) {
            $data_arr['code_img'] = $code_img;
        } else {
            if ($data['is_code_img'] == '0') {
                $data_arr['code_img'] = '';
            }
        }
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
        
        $execute = $model->where($map)->save($data_arr);
        
        if ($execute === false)
            $this->error('系统错误！', 
                array(
                    '返回活动列表' => U('MarketActive/Activity/index')));
        node_log('赛事竞猜编辑|活动id:' . $data['id']);
        
        // 如果状态是付费中(不能让他修改时间);
        $isInPay = D('Order')->isInPay($this->node_id, $m_id);
        if ($isInPay) {
            $this->error(
                '订单已生成，活动时间不可更改。如需更改时间，请先到<a target="_blank" href="' .
                U('Home/ServicesCenter/myOrder') . '">我的订单</a>中取消订单。');
        }
        
        $isReEdit = I('isReEdit');
        if ($isReEdit) {
            $this->redirect('MarketActive/Activity/index');
        } else {
            $this->redirect('ZtWorldcup/MatchGuess/setPrize',['isReEdit' => '0', 'm_id' => $data['id']]);
        }
    }
    
    // 状态修改
    public function editStatus() {
        $data = I('post.');
        
        // 启用活动时，相同活动判断
        $status = I('post.status', null);
        if ($status == 1) {
            $dataInfo = M('tmarketing_info')->where(
                array(
                    'batch_type' => $this->BATCH_TYPE, 
                    'node_id' => $this->node_id, 
                    'status' => '1', 
                    'defined_one_name' => $result['defined_one_name'], 
                    'id' => array(
                        'neq', 
                        $data['batch_id'])))->find();
            if ($dataInfo) {
                $this->error('该场次已经有一个赛事竞猜活动正在进行中，如果需要启用新的活动替换现有活动，请先停用现有活动！');
            }
        }
        
        $baseActivityModel = D('BaseActivity', 'Service');
        $result = $baseActivityModel->changeTheActivityStatus($data, 
            $this->BATCH_TYPE, $this->nodeIn());
        
        if ($result) {
            node_log('赛事竞猜状态更改|活动id:' . $data['batch_id']);
            $this->success('更新成功', 
                array(
                    '返回' => U('MarketActive/Activity/index')));
        } else {
            $this->error('更新失败');
        }
    }

    public function winningExport() {
        $batchId = I('batch_id', null, 'intval');
        $status = I('status', null, 'mysql_real_escape_string');
        if (is_null($batchId))
            $this->error('缺少参数');
        $sql = "SELECT a.phone_no ,a.add_time,
		(CASE a.result 
		WHEN '0' THEN '未抽奖' 
		WHEN '1' THEN '未中奖'
		WHEN '2' THEN '已中奖'
		WHEN '3' THEN '发码失败'
		ELSE '未知' END) result,
		b.batch_name,batch_short_name,ch.id channel_id , ch.name channel_name, a.name
		FROM tworld_cup_match_quiz a
		left join tbatch_info b on b.batch_no=a.activity_no and a.activity_no is not null and a.activity_no <>''
		LEFT JOIN tbatch_channel c ON c.id = a.label_id
		LEFT JOIN tchannel ch ON ch.id = c.channel_id
		WHERE a.batch_id='{$batchId}' AND a.node_id='{$this->nodeId}' AND a.quiz_type=1";
        
        $countSql = "SELECT COUNT(*) as count FROM tworld_cup_match_quiz a WHERE a.batch_id='{$batchId}' AND a.node_id='{$this->nodeId}' AND a.quiz_type=1 ";
        $cols_arr = array(
            'phone_no' => '手机号', 
            'add_time' => '中奖时间', 
            'result' => '抽奖结果', 
            'batch_short_name' => '奖品', 
            'channel_id' => '渠道号', 
            'channel_name' => '渠道名称');
        
        if ($this->pufa_flag)
            $cols_arr['name'] = '姓名';
            // 获取活动名称
        $batchName = M('tmarketing_info')->where(
            "id='{$batchId}' AND node_id in({$this->nodeIn()})")->getField(
            'name');
        $fileName = $batchName . '-' . date('Y-m-d') . '-' . '中奖名单';
        $fileName = str_replace(' ', '-', $fileName);
        $count = M()->query($countSql);
        if ($count[0]['count'] <= 0)
            $this->error('没有查询到中奖数据');
        if (empty($status))
            $this->ajaxReturn('', '', 1);
        if (querydata_download($sql, $cols_arr, M(), $fileName) == false) {
            $this->error('下载失败');
        }
    }
    
    // 参与名单
    public function joinExport() {
        $batchId = I('batch_id', null, 'mysql_real_escape_string');
        $status = I('status', null, 'mysql_real_escape_string');
        if (is_null($batchId))
            $this->error('缺少参数');
        $sql = "SELECT w.phone_no,IFNULL(t.team_name,'平局') as team_name,w.add_time,
		ch.id channel_id , ch.name channel_name, w.name
		FROM tworld_cup_match_quiz w
		LEFT JOIN tworld_cup_team_info t ON w.team_id=t.team_id
		LEFT JOIN tbatch_channel c ON c.id = w.label_id
		LEFT JOIN tchannel ch ON ch.id = c.channel_id
		 WHERE w.batch_id='{$batchId}' AND w.node_id='{$this->nodeId}' AND w.quiz_type=1";
        $countSql = "SELECT COUNT(*) as count FROM tworld_cup_match_quiz WHERE batch_id='{$batchId}' AND node_id='{$this->nodeId}' AND quiz_type=1";
        $cols_arr = array(
            'phone_no' => '手机号', 
            'team_name' => '竞猜的队伍', 
            'add_time' => '参与时间', 
            'channel_id' => '渠道号', 
            'channel_name' => '渠道名称');
        
        if ($this->pufa_flag)
            $cols_arr['name'] = '姓名';
            // 获取活动名称
        $batchName = M('tmarketing_info')->where(
            "id='{$batchId}' AND node_id in({$this->nodeIn()})")->getField(
            'name');
        $fileName = $batchName . '-' . date('Y-m-d') . '-' . '参与用户名单';
        $count = M()->query($countSql);
        if ($count[0]['count'] <= 0)
            $this->error('没有查询到相关数据');
        if (empty($status))
            $this->ajaxReturn('', '', 1);
        if (querydata_download($sql, $cols_arr, M(), $fileName) == false) {
            $this->error('下载失败');
        }
    }
    
    /**
     * 设置奖项
     */
    public function setPrize() {
        $m_id = I('m_id', '');
        $result = M('tmarketing_info')->where(
            array(
                'node_id' => $this->node_id,
                'id' => $m_id))->find();
        $this->assign('batch_type', $result['batch_type']);
        if (! $result) {
            $this->error('参数错误');
        }
        if (IS_POST) {
            $cj_resp_text = I('cj_resp_text', '', '');
            $no_award_notice = I('no_award_notice', '', '');
            $total_chance = I('total_chance', '', 'trim');
            $sort = I('get.cj_cate_to_sort', array());
            $isLimit = I('is_limit');
            $memberBatchId = I('member_batch_id');
            $fansCollectUrl = I('fans_collect_url');
            $playMode = I('play_mode');
            try {
                $data = D('MatchGuessCjSet')->verifyReqDataForWeel(
                        [
                            'is_limit' => $isLimit, 
                            'member_batch_id' => $memberBatchId, 
                            'fans_collect_url' => $fansCollectUrl, 
                            'defined_three_name' => $playMode
                        ], 
                        $this->node_id, 
                        $m_id
                    );
                $re = M('tmarketing_info')->where(
                    ['id' => $m_id, 'node_id' => $this->node_id]
                    )->save($data);
                if (false === $re) {
                    $this->error('保存活动数据失败');
                }
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }

            $cusMsg = I('cusMsg','');
            $data = array(
                'cj_resp_text' => $cj_resp_text,
                'no_award_notice' => $no_award_notice,
                'total_chance' => $total_chance,
                'sort' => $sort,  // 奖项排序
                'm_id' => $m_id,
                'cusMsg' => $cusMsg
            );
            try {
                D('CjSet')->savePrizeConfig($this->node_id, $data);
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }
            // 顺便发布到多乐互动专用渠道上
            $bchId = D('MarketCommon')->chPublish($this->nodeId,$m_id);
            if($bchId === false){
                $this->error('发布到渠道失败');
            }
            $this->success('提交成功','',array('bchId'=>$bchId));
        }
        $cjConfig = D('CjSet')->getCjConfig($this->node_id, $m_id);
        $this->assign('jp_arr', $cjConfig['jp_array']);
        $this->assign('cj_rule_arr', $cjConfig['cj_rule_arr']);
        $this->assign('cj_cate_arr', $cjConfig['cj_cate_arr']);
        // 奖项不能多于7的判断
        $cateNum = count($cjConfig['cj_cate_arr']);
        $this->assign('canShowAddBtn', ($cateNum < 7 ? true : false));
        $this->assign('m_id', $m_id);
        $isReEdit = I('isReEdit', '1');
        $this->assign('isReEdit', $isReEdit);
        $this->assign('firstCreateTips', $firstCreateTips);
        
        // 活动选择的微信授权方式
        $WxAuthType = 0;
        $configData = unserialize($result['config_data']);
        if (is_array($configData) && isset($configData['wx_auth_type'])) {
            $WxAuthType = $configData['wx_auth_type'];
        }
        $this->assign('wx_auth_type', $WxAuthType);
        // 是否绑定了微信认证服务号
        $isWxBd = D('CjSet')->isBindWxServ($this->node_id);
        $this->assign('isWxBd', $isWxBd);
        
        $isSelectCard = D('OrderActivityAdmin')->isSelectCard($this->node_id,
            $m_id);
        //是否已经选了微信红包作为奖品，如果是的话参与方式不能改为手机
        $isSelectWxHb = D('OrderActivityAdmin')->isSelectWxHb($this->node_id, $m_id);
        //为了前端不用改，这里直接用isSelectCard统一判断
        $isSelectCard = ($isSelectCard || $isSelectWxHb) ? 1 : 0;
        $this->assign('isSelectCard', $isSelectCard);
        //微信授权的帮助链接
        $wxsqHelp = U('Home/Help/helpDetails', array('newsId' => C('wxsq_help_id'), 'classId' => C('wxsq_help_class_id')));
        $this->assign('wxsqHelp', $wxsqHelp);
        
        // 微信分组
        $user_wx_group = D('CjSet')->getWxGroup($this->node_id);
        // 参与和中奖的分组设定
        $joinGroupConfig = D('CjSet')->getSelectedGroup(
            $result['member_batch_id'], $result['member_batch_award_id'],
            1, [], $user_wx_group);
        $this->assign('wx_selected', $joinGroupConfig['wx_selected']); // 参与的微信分组
        $this->assign('user_wx_group', $user_wx_group); // 全部的微信分组
        //$this->assign('wx_selected_zj', $joinGroupConfig['wx_selected_zj']); // 允许中奖的微信分组
        
        $wx_recruit = $result['fans_collect_url'] ? 1 : 0; // 微信招募活动的开关值
        $this->assign('wx_recruit', $wx_recruit);
        $guidUrl = D('TweixinInfo')->getGuidUrl($this->node_id); // 旺财微信引导页链接
        // 如果活动中设置过微信招募活动链接显示，否则显示旺财设置的微信引导页链接
        $guidUrl = $result['fans_collect_url'] ? $result['fans_collect_url'] : $guidUrl;
        $this->assign('guidUrl', $guidUrl);
        $this->assign('member_batch_id_flag',
            ($result['member_batch_id'] == - 1 ? 0 : 1)); // 会员参与限制的开关值
        
        //活动形式 0猜输赢 1猜比分
        $this->assign('playMode', $result['defined_three_name']);
        //是否有人参与过活动
        $hasGuess = M('tworld_cup_match_quiz')->where(['node_id' => $this->node_id, 'batch_id' => $m_id])->find();
        $sessionInfo = M('tworld_cup_events')->where(['session_id' => $result['defined_one_name']])->find();
        $canSwitchPlayMode = '1';
        if ($hasGuess || $sessionInfo['events_type'] <= 1) {//如果有人参与过或者是八分之一比赛之前的比赛，开关不可用
            $canSwitchPlayMode = '2';
        }
        $this->assign('canSwitchPlayMode', $canSwitchPlayMode);
        $this->display();
    }
    
    /**
     * 添加奖品第一步选择奖品
     */
    public function addAward() {
        $m_id = I('m_id', '');
        $prizeCateId = I('prizeCateId', '');
        $b_id = I('b_id', '');
    
        if (! $b_id) { // 如果没有b_id表示添加奖品
            $this->redirect('Common/SelectJp/indexNew',
                array(
                    'next_step' => urlencode(
                        U('Common/SelectJp/addToPrizeItem',
                            array(
                                'm_id' => $m_id,
                                'prizeCateId' => $prizeCateId, 
                                'availableSendType' => '0'
                            ))),
                    'availableTab' => '1,3,4',
                    'availableSourceType' => '0,1'
                )); // 给个参数让按钮显示成下一步
        }
    }
    
    public function changeAuthType() {
        $mId = I('m_id');
        $hasGuess = M('tworld_cup_match_quiz')->where(['node_id' => $this->node_id, 'batch_id' => $mId])->find();
        if ($hasGuess) {
            $this->error('已有人参与投票了，无法切换');
        }
        $wxAuthType = I('wx_auth_type', 0);
        $configData = M('tmarketing_info')->where(['id' => $mId, 'node_id' => $this->node_id])->getField('config_data');
        $configData = unserialize($configData);
        $configData['wx_auth_type'] = $wxAuthType;
        $config = serialize($configData);
        $data = [
            'config_data' => $config, 
            'fans_collect_url' => '',
            'member_batch_id' => -1,
            'member_join_flag' => 0
        ];
        M('tmarketing_info')->where(['id' => $mId, 'node_id' => $this->node_id])->save($data);
        $this->success();
    }
    
    public function downloadGuess() {
        $batchId = I('batch_id', null);
        if (is_null($batchId))
            $this->error('缺少参数');
        $nodeInfo = M('tmarketing_info')->where(['id' => $batchId, 'node_id' => $this->node_id])->find();
        if (! $nodeInfo)
            $this->error('未查询到记录！');

        $fileName = $nodeInfo['name'] . '-' . date('Y-m-d') . '-竞猜结果.csv';
        $fileName = iconv('utf-8', 'gbk', $fileName);
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        $start_num = 0;
        import('ORG.Util.Page'); // 导入分页类
        $map = [
            'node_id' => $this->node_id, 
            'batch_id' => $batchId, 
        ];
        $count = M('tworld_cup_match_quiz')->where($map)->count(); // 查询满足要求的总记录数
        $mapcount = 5000;
        $cj_title = "参与手机号,预测竞猜结果,参与时间\r\n";
        echo $cj_title = iconv('utf-8', 'gbk', $cj_title);
        for ($j = 1; $j < 100; $j ++) {
            $page = ($j - 1) * $mapcount;
            $list = D('MatchGuess')->getGuessList($this->node_id, $batchId, $page . ',' . $mapcount);
		    foreach ($list as $v) {
		        $cj_status = $v['team_name'];
		        $date = date('Y-m-d H:i:s', strtotime($v['add_time']));
		        if ($nodeInfo['defined_three_name'] == '1') {
		            $cj_status = $v['team1_name'] . ' ' . $v['score'] . ' ' . $v['team2_name'];
		        }
		        $cj_status = iconv('utf-8', 'gbk', $cj_status);
		        echo "{$v['phone_no']},{$cj_status},{$date}\r\n";
		    }
        }
    }
}
