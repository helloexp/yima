<?php
// 七夕节活动
class QixiAction extends BaseAction {
    // 活动类型
    public $BATCH_TYPE;
    // 图片路径
    public $img_path;

    public $cjSetModel;
    
    // 初始化
    public function _initialize() {
        parent::_initialize();
        import('@.Vendor.CommonConst') or die('include file fail.');
        $path_arr = C('BATCH_IMG_PATH_NAME');
        $this->BATCH_TYPE = CommonConst::BATCH_TYPE_QIXI;
        $img_path = $path_arr[$this->BATCH_TYPE] . '/' . $this->node_id . '/';
        $this->img_path = $img_path;
        
        // 临时路径
        $tmp_path = 'Upload/img_tmp' . '/' . $this->node_id . '/';
        $this->assign('img_path', $img_path);
        $this->assign('tmp_path', $tmp_path);
        $this->cjSetModel = D('QixiCjSet');
    }
    
    // 首页
    public function index() {
        $model = M('tmarketing_info');
        $map = array(
            'node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"), 
            'batch_type' => $this->BATCH_TYPE);
        
        $data = I('request.');
        if (! empty($data['node_id']))
            $map['node_id '] = $data['node_id'];
        if ($data['key'] != '') {
            $map['name'] = array(
                'like', 
                '%' . $data['key'] . '%');
        }
        if ($data['status'] != '') {
            $map['status'] = $data['status'];
        }
        
        if ($data['start_time'] != '' && $data['end_time'] != '') {
            $map['add_time'] = array(
                'BETWEEN', 
                array(
                    $data['start_time'] . '000000', 
                    $data['end_time'] . '235959'));
        }
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
        node_log("首页+七夕节");
        
        $arr_ = C('CHANNEL_TYPE');
        $this->assign('is_cj_button', '1');
        $this->assign('arr_', $arr_);
        $this->assign('query_list', $list);
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('nodeList', $this->getNodeTree());
        $this->display(); // 输出模板
    }
    
    // 添加页
    public function add() {
        $nodeInfo = get_node_info($this->node_id);
        $this->assign('node_name', $nodeInfo['node_name']);
        $this->assign('node_logo', $nodeInfo['head_photo']);
        $this->display();
    }
    
    // 添加提交页
    public function addSubmit() {
        $model = M('tmarketing_info');
        $data = I('post.');
        
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
                    '返回七夕节' => U('index')));
        }
        
        $ser = D('TmarketingInfo');
        $arr = array(
            'node_id' => $this->nodeId, 
            'batch_type' => $this->BATCH_TYPE, 
            'batch_id' => $resp_id);
        $ser->init($arr);
        $ser->sendBatch();
        node_log('七夕节添加|活动名:' . $data['name']);
        $this->success('添加成功！', 
            array(
                '设置抽奖' => U('LabelAdmin/CjSet/index', 
                    array(
                        'batch_id' => $resp_id)), 
                '发布到线上线下渠道' => U('LabelAdmin/BindChannel/index', 
                    array(
                        'batch_type' => $this->BATCH_TYPE, 
                        'batch_id' => $resp_id)), 
                '返回活动列表页' => U('index')));
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
        $query_arr = $baseActivityModel->checkactivityexist($data['id'], 
            $this->BATCH_TYPE, $this->nodeIn());
        $node_id = $query_arr['node_id'];
        
        // 社交分享
        if (! empty($data['sns_type'])) {
            $sns_type = $baseActivityModel->implodearray($data['sns_type']);
        } else {
            $sns_type = '';
        }
        
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
        
        $execute = $model->where($map)->save($data_arr);
        
        if ($execute === false)
            $this->error('系统错误！', 
                array(
                    '返回七夕节' => U('index')));
        
        node_log('七夕节编辑|活动id:' . $data['id']);
        redirect(
            U('Home/MarketActive/listNew', 
                array(
                    'batchtype' => $this->BATCH_TYPE)));
    }
    
    // 状态修改
    public function editStatus() {
        $data = I('post.');
        $baseActivityModel = D('BaseActivity', 'Service');
        $result = $baseActivityModel->changeTheActivityStatus($data, 
            $this->BATCH_TYPE, $this->nodeIn());
        
        if ($result) {
            node_log('七夕节状态更改|活动id:' . $data['batch_id']);
            $this->success('更新成功', 
                array(
                    '返回' => U('News/index')));
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
     * 设置活动基础信息
     */
    public function setActBasicInfo() {
        if (IS_POST) {
            $m_id = I('post.m_id', '', 'trim');
            $sns_share = I('post.sns_share', '', 'trim');
            $act_name = I('post.act_name', '', 'trim');
            $act_time_from = I('post.act_time_from', '', 'trim');
            if (! check_str($act_time_from, 
                array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd'), $error)) {
                $this->error("活动开始时间{$error}");
            }
            $act_time_to = I('post.act_time_to', '', 'trim');
            if (! check_str($act_time_to, 
                array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd'), $error)) {
                $this->error("活动开始时间{$error}");
            }
            $introduce = I('post.introduce', '', 'trim');
            if (! check_str($introduce, 
                array(
                    'null' => true, 
                    'maxlen_cn' => '140'), $error)) {
                $this->error("活动说明{$error}");
            }
            
            $share_descript = I('post.share_descript', '', 'trim');
            if (! check_str($share_descript, 
                array(
                    'null' => true, 
                    'maxlen_cn' => '140'), $error)) {
                $this->error("分享描述{$error}");
            }
            
            $node_name = I('post.node_name', '', 'trim');
            if (! check_str($node_name, 
                array(
                    'null' => true, 
                    'maxlen_cn' => '15'), $error)) {
                $this->error("商户名称{$error}");
            }
            $node_logo = I('post.node_logo', '', 'trim');
            $share_pic = I('post.share_pic', '', 'trim'); // 分享图标
            $basicInfo = array(
                'sns_type' => $sns_share, 
                'act_name' => $act_name, 
                'act_time_from' => $act_time_from, 
                'act_time_to' => $act_time_to, 
                'introduce' => $introduce, 
                'node_name' => $node_name, 
                'node_logo' => get_upload_url($node_logo), 
                'share_descript' => $share_descript, 
                'share_pic' => get_upload_url($share_pic));
            $baseActivityModel = D('BaseActivity', 'Service');
            $baseActivityModel->checkisactivitynamesame($basicInfo['node_name'], 
                CommonConst::BATCH_TYPE_QIXI);
            $m_id = $this->_editMarketInfo($basicInfo, $m_id);
            $this->success(
                array(
                    'm_id' => $m_id, 
                    'isReEdit' => I('isReEdit', 0)), '', true);
        }
        // 获取商户名称
        $nodeInfo = get_node_info($this->node_id);
        $node_name = $nodeInfo['node_name'];
        $node_logo = get_upload_url($nodeInfo['head_photo']);
        $share_pic = $node_logo;
        $m_id = I('get.m_id', '', 'trim');
        if ($m_id) {
            $basicInfo = M('tmarketing_info')->where(
                array(
                    'node_id' => $this->node_id, 
                    'id' => $m_id))->find();
            $node_name = $basicInfo['node_name'];
            $node_logo = $basicInfo['log_img'];
            $share_pic = $basicInfo['share_pic'];
            $configData = unserialize($basicInfo['config_data']);
            $this->assign('share_descript', $configData['share_descript']); // 分享描述
            $this->assign('act_name', $basicInfo['name']); // 活动名称
            $this->assign('act_time_from', 
                substr($basicInfo['start_time'], 0, 8)); // 活动开始时间
            $this->assign('act_time_to', substr($basicInfo['end_time'], 0, 8)); // 活动结束时间
            $this->assign('introduce', $basicInfo['wap_info']); // 活动说明
            $sns = explode('-', $basicInfo['sns_type']); // sns分享
            $this->assign('sns', $sns);
            $this->assign('m_id', $basicInfo['id']);
        }
        
        // 未付款的时候,需要提示有没有超过60,超过部分要另外收费
        $TwoFestivalAdminModel = D('TwoFestivalAdmin');
        $needShowTips = $TwoFestivalAdminModel->needShowExTips($this->node_id,
            $m_id);
        $paySettingJson = D('ActivityPayConfig')->getDefaultPayConfigModelByBatchType($this->BATCH_TYPE);
        $paySetting = json_decode($paySettingJson, true);
        $this->assign('configOneActDays', $paySetting['duringTime']);
        $this->assign('exPrice', $paySetting['exPrice']);
        $this->assign('needShowTips', $needShowTips);
        $LimitInfo = $TwoFestivalAdminModel->getLimitInfo($this->node_id, $m_id);
        $this->assign('type', $LimitInfo['type']);
        $this->assign('freeUseLimit', $LimitInfo['freeUseLimit']);
        
        $isReEdit = I('isReEdit', ($m_id ? '1' : '0'));
        $stepBar = $this->cjSetModel->getActStepsBar(ACTION_NAME, $m_id, '', 
            $isReEdit);
        $this->assign('stepBar', $stepBar);
        $this->assign('share_pic', $share_pic);
        $this->assign('isReEdit', $isReEdit);
        $this->assign('node_name', $node_name);
        $this->assign('node_logo', $node_logo);
        $this->display();
    }

    public function setActConfig() {
        $cjSetModel = $this->cjSetModel;
        if (IS_POST) {
            try {
                $data = $cjSetModel->verifyReqDataForWeel(I('post.', '', ''), 
                    $this->node_id);
                $result = $cjSetModel->saveData($data, $this->node_id);
                if ($result) {
                    $this->success();
                }
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }
        }
        $m_id = I('get.m_id', '');
        $isReEdit = I('get.isReEdit', '1'); // 用来控制按钮是下一步还是保存
        $marketInfoModel = M('tmarketingInfo');
        $basicInfo = $marketInfoModel->where(
            array(
                'node_id' => $this->node_id, 
                'id' => $m_id))->find();
        // 会员分组
        $mem_batch = $cjSetModel->getMemberBatch($this->node_id);
        // 微信分组
        $user_wx_group = $this->cjSetModel->getWxGroup($this->node_id);
        // 参与和中奖的分组设定
        $result = $cjSetModel->getSelectedGroup($basicInfo['member_batch_id'], 
            $basicInfo['member_batch_award_id'], $basicInfo['join_mode'], 
            $mem_batch, $user_wx_group);
        $this->assign('mem_batch', $mem_batch); // 全部的手机分组
        $this->assign('phone_selected', $result['phone_selected']); // 参与的手机分组
        $this->assign('phone_selected_zj', $result['phone_selected_zj']); // 允许中奖的手机分组
        $this->assign('wx_selected_zj', $result['wx_selected_zj']); // 允许中奖的微信分组
                                                                    // 是否限制
        $this->assign('member_batch_id_flag', 
            ($basicInfo['member_batch_id'] == - 1 ? 0 : 1)); // 会员参与限制的开关值
        $member_zj_flag = $basicInfo['member_batch_award_id'] == - 1 ? 0 : 1; // 会员中奖限制
        $this->assign('member_zj_flag', $member_zj_flag); // 中奖限制的开关值
        $this->assign('member_reg_mid', $basicInfo['member_reg_mid']); // 绑定的会员招募活动的id
        $phone_recruit = $basicInfo['member_reg_mid'] ? 1 : 0;
        $this->assign('phone_recruit', $phone_recruit); // 是否绑定了会员招募活动的开关值
        $this->assign('m_id', $m_id);
        $this->assign('isReEdit', $isReEdit); // 是否是重新编辑
                                              // 未中奖提示语，日中奖，总中奖，日参数
        $cj_rule_arr = M('tcj_rule')->where(
            array(
                'node_id' => $this->node_id, 
                'batch_id' => $m_id, 
                'status' => '1'))->find();
        $cj_rule_arr['no_award_notice'] = explode('|', 
            $cj_rule_arr['no_award_notice']);
        $this->assign('cj_rule_arr', $cj_rule_arr);
        // 是否是免费用户
        $isFreeUser = D('node')->getNodeVersion($this->node_id);
        $this->assign('isFreeUser', $isFreeUser);
        // 选择的招募活动的名字
        $this->assign('regName', 
            $cjSetModel->getBindedRecruitName($basicInfo['member_reg_mid'], 
                $this->node_id));
        $stepBar = $cjSetModel->getActStepsBar(ACTION_NAME, $m_id, '', 
            $isReEdit);
        $this->assign('stepBar', $stepBar);
        $this->display();
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
        $cjSetModel = $this->cjSetModel;
        if (IS_POST) {
            $cj_resp_text = I('cj_resp_text', '', '');
            $no_award_notice = I('no_award_notice', '', '');
            $total_chance = I('total_chance', '', 'trim');
            $sort = I('get.cj_cate_to_sort', array());
            $data = array(
                'cj_resp_text' => $cj_resp_text, 
                'no_award_notice' => $no_award_notice, 
                'total_chance' => $total_chance, 
                'sort' => $sort,  // 奖项排序
                'm_id' => $m_id);
            try {
                $cjSetModel->savePrizeConfig($this->node_id, $data);
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }
            //不发布到多乐互动专用渠道
            $notCreateDlhdChannel = I('not_create_dlhd_channel');
            if (!$notCreateDlhdChannel) {
                // 顺便发布到多乐互动专用渠道上
                $ret = D('MarketCommon')->checkIsPublish($this->nodeId,$m_id);
                if(!$ret){
//                     $this->error('尊敬的旺财用户，您只要购买营销工具礼包或单独支付该营销工具使用费即可发布该活动！');
                    $this->error('尊敬的旺财用户，您没有使用该功能的权限！');
                }
                $bchId = D('MarketCommon')->chPublish($this->nodeId,$m_id);
                if($bchId === false){
                    $this->error('发布到渠道失败');
                }
            }
            $this->success('提交成功','',array('bchId'=>$bchId));
        }
        $cjConfig = $cjSetModel->getCjConfig($this->node_id, $m_id);
        $this->assign('jp_arr', $cjConfig['jp_array']);
        $this->assign('cj_rule_arr', $cjConfig['cj_rule_arr']);
        $this->assign('cj_cate_arr', $cjConfig['cj_cate_arr']);
        $this->assign('m_id', $m_id);
        $isReEdit = I('isReEdit', '1');
        $this->assign('isReEdit', $isReEdit);
        $stepBar = $cjSetModel->getActStepsBar(ACTION_NAME, $m_id, '', 
            $isReEdit);
        $this->assign('stepBar', $stepBar);
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
                       // 添加奖品的第一步，选择卡券（或者红包）//具体产品自己还没设计好，先不管让他选择卡券
            $this->redirect('Common/SelectJp/indexNew', 
                array(
                    'next_step' => urlencode(
                        U('Common/SelectJp/addToPrizeItem', 
                            array(
                                'm_id' => $m_id, 
                                'prizeCateId' => $prizeCateId))), 
                    'availableTab' => '1,2', 
                    'availableSourceType' => '0,1'
                            )); // 给个参数让按钮显示成下一步
        }
    }

    private function _editMarketInfo($data, $m_id = '') {
        $isFreeUser = D('node')->getNodeVersion($this->node_id);
        M()->startTrans();
        $marketInfoModel = M('tmarketingInfo');
        $readyData = array(
            'name' => $data['act_name'], 
            'node_id' => $this->nodeId, 
            'node_name' => $data['node_name'], 
            'wap_info' => $data['introduce'], 
            'log_img' => $data['node_logo'], 
            'start_time' => $data['act_time_from'] . '000000', 
            'end_time' => $data['act_time_to'] . '235959', 
            'sns_type' => $data['sns_type'], 
            'add_time' => date('YmdHis'), 
            'status' => '1', 
            'batch_type' => CommonConst::BATCH_TYPE_QIXI, 
            'is_show' => '1', 
            'is_cj' => '1',  // 是否有抽奖(在添加奖品的时候会判断是否是0,如果是0才会改为1,所以一定要设默认值)
            'share_pic' => $data['share_pic'], 
            'join_mode' => '0'); // 手机参与
        
        if (! $m_id) { // 如果没有m_id表示增加
            $readyData['config_data'] = serialize(
                array(
                    'share_descript' => $data['share_descript']));
            $m_id = $marketInfoModel->add($readyData);
            if (! $m_id) {
                M()->rollback();
                log_write('新增活动失败!');
                $this->error('新增活动失败!');
            }
            // 如果是新增把默认的抽奖配置填上
            $ruleParam = array(
                'batch_type' => CommonConst::BATCH_TYPE_QIXI, 
                'batch_id' => $m_id, 
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
                'no_award_notice' => '很遗憾！未中奖');
            $flag = M('tcj_rule')->add($ruleParam);
            if (! $flag) {
                M()->rollback();
                log_write('新增默认抽奖失败!');
                $this->error('新增默认抽奖失败!');
            }
            $cateData = array(
                'batch_type' => CommonConst::BATCH_TYPE_QIXI, 
                'batch_id'   => $m_id, 
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
            node_log('七夕节添加|活动id:' . $m_id);
        } else {
            // 如果状态是付费中(不能让他修改时间);
            $isInPay = D('Order')->isInPay($this->node_id, $m_id);
            if ($isInPay) {
                $this->error('订单已生成，活动时间不可更改。如需更改时间，请先到<a target="_blank" href="' .
                         U('Home/ServicesCenter/myOrder') . '">我的订单</a>中取消订单。');
            }
            // 检查是否有没有超过购买的期限
            try {
                D('TwoFestivalAdmin')->checkLimitDay($this->node_id,
                    $isFreeUser, $m_id, $readyData['start_time'],
                    $readyData['end_time']);
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }
            
            $configData = $marketInfoModel->where(
                array(
                    'id' => $m_id))->getField('config_data');
            $configDataArr = unserialize($configData);
            $configDataArr['share_descript'] = $data['share_descript'];
            $readyData['config_data'] = serialize($configDataArr);
            $flag = $marketInfoModel->where(
                array(
                    'id' => $m_id))->save($readyData);
            if (false === $flag) {
                M()->rollback();
                log_write('保存活动失败!');
                $this->error('保存活动失败!');
            }
            node_log('七夕节编辑|活动id:' . $m_id);
        }
        M()->commit();
        return $m_id;
    }

    /**
     * 发布活动
     */
    public function publish() {
        // 查询活动
        $marketingModel = M('tmarketing_info');
        $m_id = I('m_id');
        $marketInfo = $marketingModel->where(
            array(
                'id' => $m_id, 
                'node_id' => $this->node_id, 
                'batch_type' => CommonConst::BATCH_TYPE_QIXI))->find();
        if (! $marketInfo) {
            $this->error('参数错误');
        }
        $isReEdit = I('isReEdit', '1');
        $this->redirect('LabelAdmin/BindChannel/index', 
            array(
                'batch_id' => $m_id, 
                'batch_type' => CommonConst::BATCH_TYPE_QIXI, 
                'isReEdit' => $isReEdit, 
                'publishGroupModule' => GROUP_NAME . '/' . MODULE_NAME));
    }
}
