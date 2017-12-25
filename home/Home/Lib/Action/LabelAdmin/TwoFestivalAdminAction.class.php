<?php

/**
 * 大转盘活动设置
 *
 * @author lwb
 */
class TwoFestivalAdminAction extends BaseAction {

    public $cjSetModel;
    
    // 初始化
    public function _initialize() {
        parent::_initialize();
        import('@.Vendor.CommonConst') or die('include file fail.');
        $this->cjSetModel = D('TwoFestivalCjSet');
    }

    public function editStatus() {
        $data = I('post.');
        $baseActivityModel = D('BaseActivity', 'Service');
        $result = $baseActivityModel->changeTheActivityStatus($data, 
            CommonConst::BATCH_TYPE_TWO_VESTIVAL, $this->nodeIn());
        if ($result) {
            node_log('抽奖活动状态更改|活动id:' . $data['batch_id']);
            $this->success('更新成功');
        } else {
            $this->error('更新失败');
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
                    'maxlen_cn' => '1000'), $error)) {
                $this->error("活动说明{$error}");
            }
            $node_name = I('post.node_name', '', 'trim');
            if (! check_str($node_name, 
                array(
                    'null' => true, 
                    'maxlen_cn' => '15'), $error)) {
                $this->error("商户名称{$error}");
            }
            
            $cj_button_text = I('post.cj_button_text', '', 'trim');
            if (! check_str($cj_button_text, 
                array(
                    'null' => true, 
                    'maxlen_cn' => '15'), $error)) {
                $this->error("商户名称{$error}");
            }
            
            $node_logo = I('post.node_logo', '', 'trim');
            $bg_pic = I('post.bg_pic', '', 'trim'); // 背景图片
            $bg_pic2 = I('post.bg_pic2', '', 'trim'); // 祝福墙背景
            $share_pic = I('post.share_pic', '', 'trim'); // 分享图标
            $share_descript = I('post.share_descript', '', 'trim');
            $share_descript = str_replace('<br>', '', my_nl2br($share_descript));
            if (! check_str($share_descript, 
                array(
                    'null' => true, 
                    'maxlen_cn' => '140'), $error)) {
                $this->error("分享描述{$error}");
            }
            $shareTitle = I('post.share_title'); // 分享标题
            $basicInfo = array(
                'sns_type' => $sns_share, 
                'act_name' => $act_name, 
                'act_time_from' => $act_time_from, 
                'act_time_to' => $act_time_to, 
                'introduce' => $introduce, 
                'node_name' => $node_name, 
                'node_logo' => $node_logo ? get_upload_url($node_logo) : '', 
                'cj_button_text' => $cj_button_text, 
                'bg_pic' => $bg_pic, 
                'bg_pic2' => $bg_pic2, 
                'share_pic' => $share_pic, 
                'share_descript' => $share_descript, 
                'share_title' => $shareTitle);
            // 判断活动名是否重复
            $baseActivityModel = D('BaseActivity', 'Service');
            $baseActivityModel->checkisactivitynamesame($basicInfo['act_name'], 
                CommonConst::BATCH_TYPE_TWO_VESTIVAL, $m_id);
            $m_id = $this->_editMarketInfo($basicInfo, $m_id);
            $this->success(
                array(
                    'm_id' => $m_id, 
                    'isReEdit' => I('isReEdit', 0)), '', true);
        }
        // 获取商户名称
        $nodeInfo = get_node_info($this->node_id);
        $node_name = $nodeInfo['node_name'];
        $node_logo = empty($nodeInfo['head_photo']) ? '__PUBLIC__/Image/wap-logo-wc.png' : get_upload_url(
            $nodeInfo['head_photo']);
        $m_id = I('get.m_id', '', 'trim');
        $act_name = '迎双蛋送祝福';
        $cjBtnText = '求祝福赢好礼';
        $bg_pic = '';
        $share_descript = '亲口向朋友送上你最诚意的祝福，让这个寒冷的冬天因为你的声音而更加温暖。';
        $shareTitle = '迎双旦晒祝福';
        if ($m_id) {
            $basicInfo = M('tmarketing_info')->where(
                array(
                    'node_id' => $this->node_id, 
                    'id' => $m_id))->find();
            $node_name = $basicInfo['node_name'];
            $node_logo = $basicInfo['log_img'];
            $configData = unserialize($basicInfo['config_data']);
            $share_descript = $configData['share_descript'];
            $shareTitle = $configData['share_title'];
            $this->assign('bg_pic2', $configData['bg_pic2']); // 祝福墙背景
            $act_name = $basicInfo['name'];
            $this->assign('act_time_from', 
                substr($basicInfo['start_time'], 0, 8)); // 活动开始时间
            $this->assign('act_time_to', substr($basicInfo['end_time'], 0, 8)); // 活动结束时间
            $this->assign('introduce', $basicInfo['wap_info']); // 活动说明
            $sns = explode('-', $basicInfo['sns_type']); // sns分享
            $this->assign('sns', $sns);
            $this->assign('m_id', $basicInfo['id']);
            // 抽奖按钮文字
            $cjBtnText = M('tcj_rule')->where(
                array(
                    'node_id' => $this->node_id, 
                    'batch_id' => $m_id, 
                    'status' => '1'))->getField('cj_button_text');
            $bg_pic = $basicInfo['bg_pic'];
            // 分享图标
            $share_pic = $basicInfo['share_pic'];
        }
        $this->assign('act_name', $act_name); // 活动名称
        $this->assign('share_title', $shareTitle);
        $this->assign('share_descript', $share_descript); // 分享描述
        $this->assign('share_pic', get_val($share_pic));
        // 未付款的时候,需要提示有没有超过60,超过部分要另外收费
        $TwoFestivalAdminModel = D('TwoFestivalAdmin');
        $needShowTips = $TwoFestivalAdminModel->needShowExTips($this->node_id, 
            $m_id);
        $paySettingJson = D('ActivityPayConfig')->getDefaultPayConfigModelByBatchType(CommonConst::BATCH_TYPE_TWO_VESTIVAL);
        $paySetting = json_decode($paySettingJson, true);
        $this->assign('configOneActDays', $paySetting['duringTime']);
        $this->assign('exPrice', $paySetting['exPrice']);
        $this->assign('needShowTips', $needShowTips);
        $this->assign('bg_pic', $bg_pic);
        $this->assign('cj_button_text', $cjBtnText);
        $isReEdit = I('isReEdit', ($m_id ? '1' : '0'));
        $this->assign('isReEdit', $isReEdit);
        $this->assign('node_name', $node_name);
        $this->assign('node_logo', $node_logo);
        $stepBar = $this->cjSetModel->getActStepsBar(ACTION_NAME, $m_id, '', 
            $isReEdit);
        $this->assign('stepBar', $stepBar); // 抽奖活动步骤条
                                            // 是否是免费用户(免费的用户需要购买活动)
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
        // 是否是免费用户
        $isFreeUser = D('node')->getNodeVersion($this->node_id);
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
        $this->assign('isWxBd', $isWxBd);
        // 选择的招募活动的名字
        $this->assign('regName', 
            $this->cjSetModel->getBindedRecruitName(
                $basicInfo['member_reg_mid'], $this->node_id));
        $stepBar = $this->cjSetModel->getActStepsBar(ACTION_NAME, $m_id, '', 
            $isReEdit);
        $this->assign('stepBar', $stepBar); // 抽奖活动步骤条
                                            // 是否已经选了微信卡券作为奖品，如果是的话参与方式不能改为手机(双但活动需求是不能选红包和卡券)
                                            // $isSelectCard =
                                            // D('TwoFestivalAdmin')->isSelectCard($this->node_id,
                                            // $m_id);
                                            // $this->assign('isSelectCard',
                                            // $isSelectCard);
                                            
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
            $total_chance = I('total_chance', '', 'trim');
            $sort = I('get.cj_cate_to_sort', array());
            $data = array(
                'cj_resp_text' => $cj_resp_text, 
                'no_award_notice' => $no_award_notice, 
                'total_chance' => $total_chance, 
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
        $this->assign('jp_arr', $cjConfig['jp_array']);
        $this->assign('cj_rule_arr', $cjConfig['cj_rule_arr']);
        $this->assign('cj_cate_arr', $cjConfig['cj_cate_arr']);
        // 奖项不能多于7的判断
        // $cateNum = count($cjConfig['cj_cate_arr']);
        // $this->assign('canShowAddBtn', ($cateNum < 7 ? true : false));
        $this->assign('m_id', $m_id);
        $isReEdit = I('isReEdit', '1');
        $this->assign('isReEdit', $isReEdit);
        $stepBar = $this->cjSetModel->getActStepsBar(ACTION_NAME, $m_id, '', 
            $isReEdit);
        $this->assign('stepBar', $stepBar); // 抽奖活动步骤条
                                            // 是否是免费用户
        $isFreeUser = D('node')->getNodeVersion($this->node_id);
        // 增加是否抽奖选项
        // $this->assign('is_cj', $result['is_cj']);
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
                                'availableSendType' => '0'))), 
                    'availableTab' => '1', 
                    'availableSourceType' => '0,1'
                            )); // 给个参数让按钮显示成下一步
        }
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
            'batch_type' => CommonConst::BATCH_TYPE_TWO_VESTIVAL, 
            'bg_pic' => $data['bg_pic'], 
            'is_show' => '1', 
            'is_cj' => '1', 
            'share_pic' => $data['share_pic']);
        if (! $m_id) { // 如果没有m_id表示增加
            $readyData['pay_status'] = '0';
            $readyData['join_mode'] = '1'; // 微信参与(双蛋活动只能微信参与)
            $readyData['config_data'] = serialize(
                array(
                    'bg_pic2' => $data['bg_pic2'], 
                    'share_descript' => $data['share_descript'], 
                    'share_title' => $data['share_title']));
            $m_id = $marketInfoModel->add($readyData);
            if (! $m_id) {
                $model->rollback();
                log_write('新增活动失败!');
                $this->error('新增活动失败!');
            }
            // 如果是新增把默认的抽奖配置填上
            $ruleParam = array(
                'batch_type' => CommonConst::BATCH_TYPE_TWO_VESTIVAL, 
                'batch_id' => $m_id, 
                'jp_set_type' => 2,  // 1单奖品2多奖品
                'node_id' => $this->node_id, 
                'add_time' => date('YmdHis'), 
                'status' => '1', 
                'phone_total_count' => '', 
                'phone_day_count' => '', 
                'phone_total_part' => '', 
                'phone_day_part' => '', 
                'cj_button_text' => $data['cj_button_text'], 
                'cj_resp_text' => '恭喜您！中奖了',  // 中奖提示信息
                'param1' => '', 
                'no_award_notice' => '很遗憾！未中奖');
            $flag = M('tcj_rule')->add($ruleParam);
            if (! $flag) {
                $model->rollback();
                log_write('新增默认抽奖失败!');
                $this->error('新增默认抽奖失败!');
            }
            $cateData = array(
                'batch_type' => CommonConst::BATCH_TYPE_TWO_VESTIVAL, 
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
            $configDataArr['bg_pic2'] = $data['bg_pic2']; // 祝福墙背景图
            $configDataArr['share_descript'] = $data['share_descript'];
            $configDataArr['share_title'] = $data['share_title'];
            $readyData['config_data'] = serialize($configDataArr);
            $flag = $marketInfoModel->where(
                array(
                    'id' => $m_id))->save($readyData);
            if (false === $flag) {
                $model->rollback();
                log_write('保存活动失败!');
                $this->error('保存活动失败!');
            }
            // 抽奖开关的文字
            $cjRuleData['cj_button_text'] = $data['cj_button_text'];
            $cjRuleResult = M('tcj_rule')->where(
                array(
                    'batch_id' => $m_id, 
                    'status' => '1', 
                    'node_id' => $this->node_id))->save($cjRuleData);
            if (false === $cjRuleResult) {
                $model->rollback();
                log_write('保存抽奖按钮文字失败!');
                $this->error('保存抽奖按钮文字失败!');
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
        $where = array(
            'id' => $m_id, 
            'node_id' => $this->node_id, 
            'batch_type' => CommonConst::BATCH_TYPE_TWO_VESTIVAL);
        $marketInfo = $marketingModel->where($where)->find();
        if (! $marketInfo) {
            $this->error('参数错误');
        }
        $isReEdit = I('isReEdit', '1');
        $this->redirect('LabelAdmin/BindChannel/index', 
            array(
                'batch_id' => $m_id, 
                'batch_type' => CommonConst::BATCH_TYPE_TWO_VESTIVAL, 
                'isReEdit' => $isReEdit));
    }
}
