<?php

// 国庆升旗活动
class RaiseFlagAction extends BaseAction {

    public $cjSetModel;

    public $_authAccessMap = '*';
    
    // 初始化
    public function _initialize() {
        parent::_initialize();
        import('@.Vendor.CommonConst') or die('include file fail.');
        $this->cjSetModel = D('RaiseFlagCjSet');
    }

    public function editStatus() {
        $data = I('post.');
        $baseActivityModel = D('BaseActivity', 'Service');
        $result = $baseActivityModel->changeTheActivityStatus($data, 
            CommonConst::BATCH_TYPE_RAISEFLAG, $this->nodeIn());
        if ($result) {
            node_log('抽奖活动状态更改|活动id:' . $data['batch_id']);
            $this->success('更新成功');
        } else {
            $this->error('更新失败');
        }
    }

    public function _before_setActBasicInfo() {
        $m_id = I('get.m_id', '', 'trim');
        $postMid = I('m_id', '', 'trim');
        $m_id = $m_id ? $m_id : $postMid;
        if ($m_id) {
            // 如果是编辑的话
            $correct = D('MarketingInfo')->checkActivityNodeCorrect(
                $this->node_id, $m_id);
            if (! $correct) {
                $this->ajaxReturn(
                    array(
                        'msg' => '操作错误', 
                        'url' => U('Home/Index/index'), 
                        'error_code' => '1000'));
            }
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
            $introduce = I('post.introduce', '', 'trim,htmlspecialchars');
            $introduce = str_replace(PHP_EOL, '', $introduce);
            if (! check_str($introduce, 
                array(
                    'null' => true, 
                    'maxlen_cn' => '140'), $error)) {
                $this->error("活动说明{$error}");
            }
            
            $share_descript = I('post.share_descript', '', 
                'trim,htmlspecialchars');
            $introduce = str_replace(PHP_EOL, '', $introduce);
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
            $baseActivityModel->checkisactivitynamesame($basicInfo['act_name'], 
                CommonConst::BATCH_TYPE_RAISEFLAG, $m_id);
            $m_id = $this->_editMarketInfo($basicInfo, $m_id);
            $this->success(
                array(
                    'm_id' => $m_id, 
                    'isReEdit' => I('isReEdit', 0)), '', true);
        }
        // 获取商户名称
        $nodeInfo = get_node_info($this->node_id);
        $node_name = $nodeInfo['node_name'];
        $act_name = '我是升旗手';
        $node_logo = empty($nodeInfo['head_photo']) ? '__PUBLIC__/Image/wap-logo-wc.png' : get_upload_url(
            $nodeInfo['head_photo']);
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
            $this->assign('share_descript', 
                htmlspecialchars_decode($configData['share_descript'])); // 分享描述
            $act_name = $basicInfo['name'];
            $this->assign('act_time_from', 
                substr($basicInfo['start_time'], 0, 8)); // 活动开始时间
            $this->assign('act_time_to', substr($basicInfo['end_time'], 0, 8)); // 活动结束时间
            $this->assign('introduce', 
                htmlspecialchars_decode($basicInfo['wap_info'])); // 活动说明
            $sns = explode('-', $basicInfo['sns_type']); // sns分享
            $this->assign('sns', $sns);
            $this->assign('m_id', $basicInfo['id']);
        }
        $this->assign('share_pic', $share_pic);
        $isReEdit = I('isReEdit', ($m_id ? '1' : '0'));
        $this->assign('isReEdit', $isReEdit);
        $this->assign('node_name', $node_name);
        $this->assign('node_logo', $node_logo);
        $this->assign('act_name', $act_name); // 活动名称
        $stepBar = $this->cjSetModel->getActStepsBar(ACTION_NAME, $m_id, '', 
            $isReEdit);
        $this->assign('stepBar', $stepBar); // 抽奖活动步骤条
        $RaiseFlagModel = D('RaiseFlag');
        // 设置极限活动结束时间
        $freeUseLimit = $RaiseFlagModel->getFreeUseLimit();
        $this->assign('freeUseLimit', $freeUseLimit);
        // 是否是免费用户
        $isFreeUser = D('node')->getNodeVersion($this->node_id);
        $this->assign('isFreeUser', $isFreeUser);
        
        // 未付款的时候,需要提示有没有超过60,超过部分要另外收费
        $TwoFestivalAdminModel = D('TwoFestivalAdmin');
        $needShowTips = $TwoFestivalAdminModel->needShowExTips($this->node_id,
            $m_id);
        $paySettingJson = D('ActivityPayConfig')->getDefaultPayConfigModelByBatchType(CommonConst::BATCH_TYPE_RAISEFLAG);
        $paySetting = json_decode($paySettingJson, true);
        $this->assign('configOneActDays', $paySetting['duringTime']);
        $this->assign('exPrice', $paySetting['exPrice']);
        $this->assign('needShowTips', $needShowTips);
        $LimitInfo = $TwoFestivalAdminModel->getLimitInfo($this->node_id, $m_id);
        $this->assign('type', $LimitInfo['type']);
        $this->assign('freeUseLimit', $LimitInfo['freeUseLimit']);
        
        $this->display();
    }

    public function setActConfig() {
        if (IS_POST) {
            try {
                $data = $this->cjSetModel->verifyReqDataForWeel(
                    I('post.', '', ''), $this->node_id);
                $result = $this->cjSetModel->saveData($data, $this->node_id);
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
        $mem_batch = $this->cjSetModel->getMemberBatch($this->node_id);
        // 微信分组
        $user_wx_group = $this->cjSetModel->getWxGroup($this->node_id);
        // 参与和中奖的分组设定
        $result = $this->cjSetModel->getSelectedGroup(
            $basicInfo['member_batch_id'], $basicInfo['member_batch_award_id'], 
            $basicInfo['join_mode'], $mem_batch, $user_wx_group);
        $this->assign('mem_batch', $mem_batch); // 全部的手机分组
        $this->assign('user_wx_group', $user_wx_group); // 全部的微信分组
        $this->assign('phone_selected', $result['phone_selected']); // 参与的手机分组
        $this->assign('wx_selected', $result['wx_selected']); // 参与的微信分组
        $this->assign('phone_selected_zj', $result['phone_selected_zj']); // 允许中奖的手机分组
        $this->assign('wx_selected_zj', $result['wx_selected_zj']); // 允许中奖的微信分组
        $this->assign('join_mode', $basicInfo['join_mode']); // 参与方式0手机1微信
                                                             // 是否限制
        $this->assign('member_batch_id_flag', 
            ($basicInfo['member_batch_id'] == - 1 ? 0 : 1)); // 会员参与限制的开关值
        $member_zj_flag = $basicInfo['member_batch_award_id'] == - 1 ? 0 : 1; // 会员中奖限制
        $this->assign('member_zj_flag', $member_zj_flag); // 中奖限制的开关值
        $this->assign('member_reg_mid', $basicInfo['member_reg_mid']); // 绑定的会员招募活动的id
        $phone_recruit = $basicInfo['member_reg_mid'] ? 1 : 0;
        $this->assign('phone_recruit', $phone_recruit); // 是否绑定了会员招募活动的开关值
        $wx_recruit = $basicInfo['fans_collect_url'] ? 1 : 0; // 微信招募活动的开关值
        $this->assign('wx_recruit', $wx_recruit);
        $guidUrl = D('TweixinInfo')->getGuidUrl($this->node_id); // 旺财微信引导页链接
                                                                 // 如果活动中设置过微信招募活动链接显示，否则显示旺财设置的微信引导页链接
        $guidUrl = $basicInfo['fans_collect_url'] ? $basicInfo['fans_collect_url'] : $guidUrl;
        $this->assign('guidUrl', $guidUrl);
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
        // 是否是免费用户(暂时改成是否是c0用户,c0用户不能选会员分组限制)
        // $isFreeUser = D('node')->getNodeVersion($this->node_id);
        $isFreeUser = $this->node_type_name == 'c0' ? true : false;
        $this->assign('isFreeUser', $isFreeUser);
        // 活动选择的微信授权方式
        $WxAuthType = 0;
        $configData = unserialize($basicInfo['config_data']);
        if (is_array($configData) && isset($configData['wx_auth_type'])) {
            $WxAuthType = $configData['wx_auth_type'];
        }
        $this->assign('wx_auth_type', $WxAuthType);
        // 是否绑定了微信认证服务号
        $isWxBd = $this->cjSetModel->isBindWxServ($this->node_id);
        // 是否有人参与过活动，参与过的就不能更改参与使用的哪种公众号（翼码的还是自有的）
        $peopleJoinedFlag = D('RaiseFlag')->hasPeopleJoined($m_id);
        $disableWxSwitch = false;
        if (! $isWxBd || $peopleJoinedFlag) { // 如果没有绑定过微信公众号或者已经有人参与抽奖了，微信授权方式就不能够更改
            $disableWxSwitch = true;
        }
        $this->assign('disableWxSwitch', $disableWxSwitch);
        // 选择的招募活动的名字
        $this->assign('regName', 
            $this->cjSetModel->getBindedRecruitName(
                $basicInfo['member_reg_mid'], $this->node_id));
        $stepBar = $this->cjSetModel->getActStepsBar(ACTION_NAME, $m_id, '', 
            $isReEdit);
        $this->assign('stepBar', $stepBar); // 抽奖活动步骤条
        
        //微信授权的帮助链接
        $wxsqHelp = U('Home/Help/helpDetails', array('newsId' => C('wxsq_help_id'), 'classId' => C('wxsq_help_class_id')));
        $this->assign('wxsqHelp', $wxsqHelp);
        
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
        if (! $result) {
            $this->error('参数错误');
        }
        if (IS_POST) {
            $cj_resp_text = I('cj_resp_text', '', '');
            $no_award_notice = I('no_award_notice', '', '');
            $firstPrizeChance = I('first_prize_chance', '', 'trim');
            $secondPrizeChance = I('second_prize_chance', '', 'trim');
            $thirdPrizeChance = I('third_prize_chance', '', 'trim');
            try {
                D('RaiseFlag')->setPrizeChance($this->node_id, $m_id, 
                    $firstPrizeChance, $secondPrizeChance, $thirdPrizeChance);
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }
            $sort = I('get.cj_cate_to_sort', array());
            $data = array(
                'cj_resp_text' => $cj_resp_text, 
                'no_award_notice' => $no_award_notice, 
                'total_chance' => 100, 
                'sort' => $sort,  // 奖项排序
                'm_id' => $m_id);
            try {
                $this->cjSetModel->savePrizeConfig($this->node_id, $data);
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
        $cjConfig = $this->cjSetModel->getCjConfig($this->node_id, $m_id);
        // 显示的兑奖规则的名字不按sort字段影响,由原来的id字段排序,显示这部分的奖项名称
        $cjCateArr = $cjConfig['cj_cate_arr'];
        $nameArr = array();
        foreach ($cjCateArr as $v) {
            $nameArr[$v['id']] = $v;
        }
        ksort($nameArr);
        $nameArr = array_values($nameArr);
        $this->assign('cj_cate_arr_by_id_sort', $nameArr);
        
        $this->assign('jp_arr', $cjConfig['jp_array']);
        $this->assign('cj_rule_arr', $cjConfig['cj_rule_arr']);
        $this->assign('cj_cate_arr', $cjConfig['cj_cate_arr']);
        // 奖项已经在创建活动中写死3个
        $this->assign('canShowAddBtn', false);
        $this->assign('m_id', $m_id);
        $isReEdit = I('isReEdit', '1');
        $this->assign('isReEdit', $isReEdit);
        $stepBar = $this->cjSetModel->getActStepsBar(ACTION_NAME, $m_id, '', 
            $isReEdit);
        $this->assign('stepBar', $stepBar); // 抽奖活动步骤条
                                            // 获取奖项设置的概率
        $prizeChance = D('RaiseFlag')->getPrizeChance($this->node_id, $m_id);
        $this->assign('prizeChance', $prizeChance);
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
                                'prizeCateId' => $prizeCateId))), 
                    'availableTab' => '1,2', 
                    'availableSourceType' => '0,1'
                            )); // 给个参数让按钮显示成下一步
        }
    }
    
    /**
     * 查看活动状态，返回是否可以使用回退（活动状态为停止的才能奖品回退）
     */
    public function requestActivityStatus() {
        $m_id = I('m_id', '');
        $info = D('MarketInfo')->getSingleInfo(array('id' => $m_id));
        if ($info['status'] == '2') {
            $this->success();
        }
        $this->error();
    }

    private function _editMarketInfo($data, $m_id = '') {
        $isFreeUser = D('node')->getNodeVersion($this->node_id);
        $model = M();
        $model->startTrans();
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
            'batch_type' => CommonConst::BATCH_TYPE_RAISEFLAG, 
            'is_show' => '1', 
            'is_cj' => '1',  // 是否有抽奖(在添加奖品的时候会判断是否是0,如果是0才会改为1,所以一定要设默认值)
            'share_pic' => $data['share_pic'], 
            'pay_status' => '0', 
            'join_mode' => '1');
        if (! $m_id) { // 如果没有m_id表示增加
            $readyData['config_data'] = serialize(
                array(
                    'share_descript' => $data['share_descript'], 
                    'prizeChance' => array(
                        '', 
                        '', 
                        ''), 
                    'wx_auth_type' => '0'));
            $m_id = $marketInfoModel->add($readyData);
            if (! $m_id) {
                $model->rollback();
                log_write('新增活动失败!');
                $this->error('新增活动失败!');
            }
            // 如果是新增把默认的抽奖配置填上
            $ruleParam = array(
                'batch_type' => CommonConst::BATCH_TYPE_RAISEFLAG, 
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
                $model->rollback();
                log_write('新增默认抽奖失败!');
                $this->error('新增默认抽奖失败!');
            }
            $data = array(
                'batch_type' => CommonConst::BATCH_TYPE_RAISEFLAG, 
                'batch_id' => $m_id, 
                'node_id' => $this->node_id, 
                'cj_rule_id' => $flag, 
                'name' => '一等奖', 
                'add_time' => date('YmdHis'), 
                'status' => '1', 
                'sort' => '1');
            $cat_id = M('tcj_cate')->add($data);
            if (! $cat_id) {
                $model->rollback();
                log_write('新增奖项失败!');
                $this->error('新增奖项失败!');
            }
            $data = array(
                'batch_type' => CommonConst::BATCH_TYPE_RAISEFLAG, 
                'batch_id' => $m_id, 
                'node_id' => $this->node_id, 
                'cj_rule_id' => $flag, 
                'name' => '二等奖', 
                'add_time' => date('YmdHis'), 
                'status' => '1', 
                'sort' => '2');
            $cat_id = M('tcj_cate')->add($data);
            if (! $cat_id) {
                $model->rollback();
                log_write('新增奖项失败!');
                $this->error('新增奖项失败!');
            }
            $data = array(
                'batch_type' => CommonConst::BATCH_TYPE_RAISEFLAG, 
                'batch_id' => $m_id, 
                'node_id' => $this->node_id, 
                'cj_rule_id' => $flag, 
                'name' => '三等奖', 
                'add_time' => date('YmdHis'), 
                'status' => '1', 
                'sort' => '3');
            $cat_id = M('tcj_cate')->add($data);
            if (! $cat_id) {
                $model->rollback();
                log_write('新增奖项失败!');
                $this->error('新增奖项失败!');
            }
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
                $model->rollback();
                log_write('保存活动失败!');
                $this->error('保存活动失败!');
            }
        }
        $model->commit();
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
                'batch_type' => CommonConst::BATCH_TYPE_RAISEFLAG))->find();
        if (! $marketInfo) {
            $this->error('参数错误');
        }
        $isReEdit = I('isReEdit', '1');
        $this->redirect('LabelAdmin/BindChannel/index', 
            array(
                'batch_id' => $m_id, 
                'batch_type' => CommonConst::BATCH_TYPE_RAISEFLAG, 
                'isReEdit' => $isReEdit, 
                'publishGroupModule' => urlencode(
                    GROUP_NAME . '/' . MODULE_NAME)));
    }

    public function releasePrize() {
        $m_id = I('m_id');
        $prizeCateId = I('prizeCateId');
        $b_id = I('b_id');
        // 判断活动号是不是这个机构的
        $basicInfo = M('tmarketing_info')->where(
            array(
                'node_id' => $this->node_id, 
                'id' => $m_id))->find();
        if (! $basicInfo) {
            $this->error('传入参数有误', '', true);
        }
        // 查询所有奖项
        $cate_arr = M('tcj_cate')->where(
            array(
                'node_id' => $this->node_id, 
                'batch_id' => $m_id))->getField('id', true);
        // 查询传过来的奖项是不是该机构的
        if (! in_array($prizeCateId, $cate_arr)) {
            $this->error('传入参数有误', '', true);
        }
        $batch_info = M('tbatch_info')->alias('a')
            ->field(
            'b.day_count,a.remain_num,a.verify_begin_type,
    			a.verify_begin_date,a.verify_end_date,a.info_title,a.card_id,
    			a.use_rule,b.total_count,b.id as cj_batch_id,
    			c.goods_type, c.goods_amt, c.goods_id as prize_id,
    			c.remain_num as goods_remain_num,
    			c.begin_time as goods_begin_date, c.end_time as goods_end_date')
            ->join('tcj_batch b on a.id = b.b_id')
            ->join('tgoods_info c on b.goods_id = c.goods_id')
            ->where(array(
            'a.id' => $b_id))
            ->select();
        if (! $batch_info) {
            $this->error('传入参数有误', '', true);
        }
        $verify_begin_date = $batch_info[0]['verify_begin_date'];
        $verify_end_date = $batch_info[0]['verify_end_date'];
        $verify_begin_type = $batch_info[0]['verify_begin_type'];
        $goods_begin_date = $batch_info[0]['goods_begin_date']; // 奖品原来的验证开始时间
        $goods_end_date = $batch_info[0]['goods_end_date']; // 奖品原来的验证结束时间
        $cardId = $batch_info[0]['card_id']; // 微信卡券id
        $goodsType = $batch_info[0]['goods_type'];
        $goodsName = $batch_info[0]['goods_remain_num'];
        $goodsAmt = $batch_info[0]['goods_amt'];
        if ($verify_begin_type == 0) {
            $verify_begin_date = substr($verify_begin_date, 0, 8);
            $verify_end_date = substr($verify_end_date, 0, 8);
        }
        // 如果商品是q币或者话费彩短信要用固定模板且readonly
        if ($goodsType == CommonConst::GOODS_TYPE_HF ||
             $goodsType == CommonConst::GOODS_TYPE_QB) {
            if ($goodsType == CommonConst::GOODS_TYPE_HF) {
                $txtContent = '您已获得' . $goodsAmt .
                 '元手机话费，点击[#GET_URL]，提交待充值手机号，即可领取！领取截止时间：[#END_DATE]。';
            $txtTitle = $goodsAmt . '元手机话费';
        }
        if ($goodsType == CommonConst::GOODS_TYPE_QB) {
            $txtContent = '您已获得' . $goodsAmt .
                 '元Q币，点击[#GET_URL],即可领取！领取截止时间：[#END_DATE]。';
            $txtTitle = $goodsAmt . '元Q币';
        }
    }
    // 是否显示短信输入框
    $isShowMms = true;
    if ($cardId || $goodsType == CommonConst::GOODS_TYPE_HB) {
        $isShowMms = false;
    }
    $post_data = array();
    $post_data['goods_id'] = $batch_info[0]['prize_id']; // 奖品的goods_id
    $post_data['wx_card_id'] = $cardId;
    $post_data['jp_type'] = ($cardId ? 1 : 0);
    $post_data['batch_id'] = $m_id;
    $post_data['js_cate_id'] = $prizeCateId;
    $post_data['begin_date'] = $verify_begin_date;
    $post_data['end_date'] = $verify_end_date;
    $post_data['goods_begin_date'] = $goods_begin_date;
    $post_data['goods_end_date'] = $goods_end_date;
    $post_data['b_id'] = $b_id;
    $post_data['cj_batch_id'] = $batch_info[0]['cj_batch_id'];
    $post_data['day_count'] = $batch_info[0]['day_count'];
    // $post_data['goods_count'] = $batch_info[0]['total_count'] -
    // $batch_info[0]['remain_num'];
    $post_data['verify_time_type'] = $verify_begin_type;
    if ($verify_begin_type == 1) {
        $post_data['verify_begin_days'] = $verify_begin_date;
        $post_data['verify_end_days'] = $verify_end_date;
    } else {
        $post_data['verify_begin_date'] = $verify_begin_date;
        $post_data['verify_end_date'] = $verify_end_date;
    }
    if ($isShowMms) {
        $post_data['mms_title'] = $batch_info[0]['info_title'];
        $post_data['mms_text'] = $batch_info[0]['use_rule'];
    }
    $post_data['ignore_daycount'] = 1;
    
    $map = array(
        'rule_id' => $batch_info[0]['cj_batch_id']);
    $count = M('taward_daytimes')->where($map)->sum('award_times');
    if (! $count) {
        $count = 0;
    }
    $post_data['goods_count'] = $count;
    $this->success($post_data, '', true);
}

public function clearChance() {
    $mId = I('m_id');
    $prizeKey = I('key');
    $map = array(
        'node_id' => $this->node_id, 
        'id' => $mId);
    $basicInfo = M('tmarketing_info')->where($map)->find();
    if (! $basicInfo) {
        $this->error('传入参数有误', '', true);
    }
    $config_data = unserialize($basicInfo['config_data']);
    $config_data['prizeChance'][$prizeKey] = 0;
    $configSer = serialize($config_data);
    $result = M('tmarketing_info')->where($map)->save(
        array(
            'config_data' => $configSer));
    if (false === $result) {
        $this->error('修改兑换概率失败', '', true);
    }
    $this->success();
}
}
