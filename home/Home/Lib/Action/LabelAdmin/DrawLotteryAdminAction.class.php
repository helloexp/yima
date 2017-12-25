<?php

/**
 * 大转盘活动设置
 *
 * @author lwb
 */
class DrawLotteryAdminAction extends BaseAction {

    /**
     * @var WeelCjSetModel
     */
    public $cjSetModel;
    /**
     * @var DrawLotteryBaseService
     */
    private $DrawLotteryBaseService;

    /**
     * @var BaseActivityService
     */
    private $BaseActivityService;

    /**
     * @var DrawLotteryAdminModel
     */
    private $DrawLotteryAdminModel;

    /**
     * @var ActivityPayConfigModel
     */
    private $ActivityPayConfigModel;

    /**
     * @var NodeModel
     */
    private $NodeMode;
    
    // 初始化
    public function _initialize() {
        parent::_initialize();
        import('@.Vendor.CommonConst') or die('include file fail.');
        $this->DrawLotteryBaseService = D('DrawLotteryBase', 'Service');
        $this->BaseActivityService = D('BaseActivity', 'Service');
        $this->cjSetModel = D('WeelCjSet');
        $this->DrawLotteryAdminModel = D('DrawLotteryAdmin');
        $this->ActivityPayConfigModel = D('ActivityPayConfig');
        $this->NodeMode = D('node');
    }

    public function editStatus() {
        $data = I('post.');
        $result = $this->BaseActivityService->changeTheActivityStatus($data, CommonConst::BATCH_TYPE_WEEL, $this->nodeIn());
        if ($result) { // 更新redis缓存
            $this->DrawLotteryBaseService->generateDrawLotteryFinalDataWithoutStorage($data['batch_id']);
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
            $introduce = str_replace("\n", '', str_replace("\r", '', str_replace("\r\n", '', $introduce)));
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
            // 判断活动名是否重复
            $this->BaseActivityService->checkisactivitynamesame($basicInfo['act_name'], CommonConst::BATCH_TYPE_WEEL, $m_id);
            $m_id = $this->_editMarketInfo($basicInfo, $m_id);

            $this->success(['m_id' => $m_id, 'isReEdit' => I('isReEdit', 0)], '', true);
        }
        // 获取商户名称
        $nodeInfo = get_node_info($this->node_id);
        $act_name = '幸运大转盘';
        $node_name = $nodeInfo['node_name'];
        $node_logo = empty($nodeInfo['head_photo']) ? '__PUBLIC__/Image/wap-logo-wc.png' : get_upload_url($nodeInfo['head_photo']);
        $share_pic = $node_logo;
        $m_id = I('get.m_id', '', 'trim');
        if ($m_id) {
            $basicInfo = M('tmarketing_info')->where(['node_id' => $this->node_id,  'id' => $m_id])->find();
            $node_name = $basicInfo['node_name'];
            $node_logo = $basicInfo['log_img'];
            $share_pic = $basicInfo['share_pic'];
            $configData = unserialize($basicInfo['config_data']);
            $this->assign('share_descript', $configData['share_descript']); // 分享描述
            $act_name = $basicInfo['name'];
            $this->assign('act_time_from', 
                substr($basicInfo['start_time'], 0, 8)); // 活动开始时间
            $this->assign('act_time_to', substr($basicInfo['end_time'], 0, 8)); // 活动结束时间
            $this->assign('introduce', $basicInfo['wap_info']); // 活动说明
            $sns = explode('-', $basicInfo['sns_type']); // sns分享
            $this->assign('sns', $sns);
            $this->assign('m_id', $basicInfo['id']);
            $this->assign('batch_type', $basicInfo['batch_type']);
        }
        $this->assign('act_name', $act_name); // 活动名称
        $this->assign('share_pic', $share_pic);
        $isReEdit = I('isReEdit', ($m_id ? '1' : '0'));
        $this->assign('isReEdit', $isReEdit);
        $this->assign('node_name', $node_name);
        $this->assign('node_logo', $node_logo);
        $stepBar = $this->cjSetModel->getActStepsBar(ACTION_NAME, $m_id, '', 
            $isReEdit);
        $this->assign('stepBar', $stepBar); // 抽奖活动步骤条
                                            // 未付款的时候,需要提示有没有超过60,超过部分要另外收费
        $needShowTips = $this->DrawLotteryAdminModel->needShowExTips($this->node_id, $m_id);
        $paySettingJson = $this->ActivityPayConfigModel->getDefaultPayConfigModelByBatchType(CommonConst::BATCH_TYPE_WEEL);
        $paySetting = json_decode($paySettingJson, true);
        $this->assign('configOneActDays', $paySetting['duringTime']);
        $this->assign('exPrice', $paySetting['exPrice']);
        $this->assign('needShowTips', $needShowTips);
        // 第一次活动的订单信息
        $firstFreeActivity = $this->DrawLotteryAdminModel->getFirstFreeActivity($this->node_id);
        // 是否是免费用户
        $isFreeUser = $this->NodeMode->getNodeVersion($this->node_id);
        // 当前活动订单的信息
        $currentOrderinfo = $this->DrawLotteryAdminModel->getFreeUserOrderInfo($this->node_id, $m_id);
        $type = '2'; // 2表示普通没有限制时,时间选择的样子
        if ($currentOrderinfo && $currentOrderinfo['pay_status'] == '1' &&
             $basicInfo['start_time'] &&
             time() > strtotime($basicInfo['start_time']) ||
             (! $firstFreeActivity || $m_id == $firstFreeActivity['m_id']) &&
             $isFreeUser) { // 付了款的，并且当前时间超过活动开始时间时或第一次创建免费活动时
                 
            $type = '1'; // 表示付款了的,控件需要disabled
        }
        if (! $firstFreeActivity && $isFreeUser) { // 创建免费活动时,开始时间是固定的今天
            $this->assign('act_time_from', date('Ymd'));
            $this->assign('need_show_tips', '1'); // 创建免费活动,有免费活动45天的提示
        }
        
        if ((! $firstFreeActivity || $m_id == $firstFreeActivity['m_id']) && $isFreeUser) {
            $this->assign('isFreeActivity', '1');
        }
        
        //如果是免费的活动，创建之后的开始时间是固定的创建时候的时间
        if ($firstFreeActivity && $m_id == $firstFreeActivity['m_id'] && $isFreeUser) {
            $firstActivityStartTime = date('Y-m-d', strtotime($basicInfo['start_time']));
            $this->assign('firstActivityStartTime', $firstActivityStartTime);
        } else {
            $this->assign('firstActivityStartTime', date('Y-m-d'));
        }
        
        $this->assign('type', $type);
        // 计算出能设置的极限日期(免费活动的极限日期,是发布时的活动开始日期加45天，付费活动的极限日期是下订单时的活动开始时间+购买的时间)
        $freeUseLimit = $this->DrawLotteryAdminModel->getLimit($firstFreeActivity['m_id'], $m_id, $this->node_id, get_val($basicInfo,'start_time',''));
        $this->assign('freeUseLimit', $freeUseLimit);
        $this->display();
    }

    public function setActConfig() {
        if (IS_POST) {
            try {
                $data = $this->cjSetModel->verifyReqDataForWeel(I('post.', '', ''), $this->node_id);
                $result = $this->cjSetModel->saveData($data, $this->node_id);
                if ($result) {
                    $this->DrawLotteryBaseService->modifyCjRule($data['m_id']);
                    $this->success();
                }
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }
        }
        $m_id = I('get.m_id', '');
        // 免费用户发布以后不能编辑
        // $canEdit = D('DrawLotteryAdmin')->canEdit($this->node_id, $m_id);
        // $this->assign('canEdit', $canEdit);
        $isReEdit = I('get.isReEdit', '1'); // 用来控制按钮是下一步还是保存
        $marketInfoModel = M('tmarketingInfo');
        $basicInfo = $marketInfoModel->where(
            array(
                'node_id' => $this->node_id, 
                'id' => $m_id))->find();
        $this->assign('batch_type', $basicInfo['batch_type']);
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
        $this->assign('isWxBd', $isWxBd);

        // 选择的招募活动的名字
        $this->assign('regName', 
            $this->cjSetModel->getBindedRecruitName(
                $basicInfo['member_reg_mid'], $this->node_id));
        $stepBar = $this->cjSetModel->getActStepsBar(ACTION_NAME, $m_id, '', 
            $isReEdit);
        $this->assign('stepBar', $stepBar); // 抽奖活动步骤条
                                            // 是否已经选了微信卡券作为奖品，如果是的话参与方式不能改为手机
        $isSelectCard = $this->DrawLotteryAdminModel->isSelectCard($this->node_id,
            $m_id);
        //是否已经选了微信红包作为奖品，如果是的话参与方式不能改为手机
        $isSelectWxHb = $this->DrawLotteryAdminModel->isSelectWxHb($this->node_id, $m_id);
        //为了前端不用改，这里直接用isSelectCard统一判断
        $isSelectCard = ($isSelectCard || $isSelectWxHb) ? 1 : 0;
        $this->assign('isSelectCard', $isSelectCard);
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
        $this->assign('batch_type', $result['batch_type']);
        // $canEdit = D('DrawLotteryAdmin')->canEdit($this->node_id, $m_id);
        if (! $result) {
            $this->error('参数错误');
        }
//        if(IS_GET) {
//            // 更新redis缓存
//            $this->DrawLotteryBaseService->updateDbRemainNumFromRedis($m_id);
//        }
        if (IS_POST) {
            $cj_resp_text = I('cj_resp_text', '', '');
            $no_award_notice = I('no_award_notice', '', '');
            $total_chance = I('total_chance', '', 'trim');
            $sort = I('get.cj_cate_to_sort', array());
            $cusMsg = I('cusMsg','');
            $data = array(
                'cj_resp_text' => $cj_resp_text, 
                'no_award_notice' => $no_award_notice, 
                'total_chance' => $total_chance, 
                'sort' => $sort,  // 奖项排序
                'm_id' => $m_id,
                'cusMsg' => $cusMsg
            );
            // if (!$canEdit) {
            // $this->error('不能编辑');
            // }
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
            // 更新redis缓存
//            $this->DrawLotteryBaseService->generateDrawLotteryFinalDataWithoutStorage($m_id);
            $this->DrawLotteryBaseService->modifyCjRule($m_id);
            $this->DrawLotteryBaseService->modifyCjCateList($m_id);
            $this->success('提交成功','',array('bchId'=>$bchId));
        }
        $cjConfig = $this->cjSetModel->getCjConfig($this->node_id, $m_id);
        $this->assign('jp_arr', $cjConfig['jp_array']);
        $this->assign('cj_rule_arr', $cjConfig['cj_rule_arr']);
        $this->assign('cj_cate_arr', $cjConfig['cj_cate_arr']);
        // 奖项不能多于7的判断
        $cateNum = count($cjConfig['cj_cate_arr']);
        $this->assign('canShowAddBtn', ($cateNum < 7 ? true : false));
        $this->assign('m_id', $m_id);
        $isReEdit = I('isReEdit', '1');
        $this->assign('isReEdit', $isReEdit);
        $stepBar = $this->cjSetModel->getActStepsBar(ACTION_NAME, $m_id, '', 
            $isReEdit);
        $this->assign('stepBar', $stepBar); // 抽奖活动步骤条
                                            // 免费用户发布成功后不能编辑
                                            // $this->assign('canEdit',
                                            // $canEdit);
                                            
        // 第一次活动的订单信息
        $firstFreeActivity = $this->DrawLotteryAdminModel->getFirstFreeActivity(
            $this->node_id);
        // 是否是免费用户
        $isFreeUser = $this->NodeMode->getNodeVersion($this->node_id);
        $firstCreateTips = false;
        if ($isFreeUser && ! $firstFreeActivity) { // 免费用户第一次创建大转盘活动时
            $firstCreateTips = true;
        }
        $this->assign('firstCreateTips', $firstCreateTips);
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
                    'availableTab' => '1,2,3,4', 
                    'availableSourceType' => '0,1'
                            )); // 给个参数让按钮显示成下一步
        }
    }

    private function _editMarketInfo($data, $m_id = '') {
        $isFreeUser = $this->NodeMode->getNodeVersion($this->node_id);
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
            'batch_type' => CommonConst::BATCH_TYPE_WEEL, 
            'is_show' => '1', 
            'is_cj' => '1',  // 是否有抽奖(在添加奖品的时候会判断是否是0,如果是0才会改为1,所以一定要设默认值)
            'share_pic' => $data['share_pic']);
        if (! $m_id) { // 如果没有m_id表示增加
            $add_flag = true;
            $readyData['pay_status'] = '0'; // 初始值为0，付费用户有发布的权利不用把值变为1
            $readyData['config_data'] = serialize(
                array(
                    'share_descript' => $data['share_descript']));
            $readyData['new_cj_flag'] = '1';//2016-04-19添加，新建活动走新抽奖模式
            $m_id = $marketInfoModel->add($readyData);
            if (! $m_id) {
                $model->rollback();
                log_write('新增活动失败!');
                $this->error('新增活动失败!');
            }
            // 如果是新增把默认的抽奖配置填上
            $ruleParam = array(
                'batch_type' => CommonConst::BATCH_TYPE_WEEL, 
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
            $cateData = array(
                'batch_type' => CommonConst::BATCH_TYPE_WEEL, 
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
            if ($isFreeUser) {
                // 有没有创建过默认门店(需求改变，有了验证助手之后不要创建门店了)
                // $alreadyCreate =
                // D('DrawLotteryAdmin')->hasSpActivity($this->node_id,
                // CommonConst::BATCH_TYPE_WEEL);
                // if (!$alreadyCreate) {
                // try {
                // //创建门店
                // $storeId =
                // D('DrawLotteryAdmin')->createDefaultFreeStore($this->node_id,
                // $this->user_id, $model);
                // } catch (Exception $e) {
                // $model->rollback();
                // $this->error($e->getMessage());
                // }
                // }
            }

        } else {
            $add_flag = false;
            // 增加判断免费用户发布后不能编辑的逻辑
            // $canEdit = D('DrawLotteryAdmin')->canEdit($this->node_id, $m_id);
            // if (!$canEdit) {
            // $this->error('发布成功后不能编辑');
            // }
            
            // 如果状态是付费中(不能让他修改时间);
            $isInPay = D('Order')->isInPay($this->node_id, $m_id);
            if ($isInPay) {
                $this->error(
                    '订单已生成，活动时间不可更改。如需更改时间，请先到<a target="_blank" href="' .
                         U('Home/ServicesCenter/myOrder') . '">我的订单</a>中取消订单。');
            }
            
            // 当前活动订单的信息
            $currentOrderinfo = $this->DrawLotteryAdminModel->getFreeUserOrderInfo(
                $this->node_id, $m_id);
            // 第一次活动的订单信息
            $firstFreeActivity = $this->DrawLotteryAdminModel->getFirstFreeActivity(
                $this->node_id);
            // 付了款的并且当前时间超过活动开始时间时,或者第一次免费的
            if ($isFreeUser && $currentOrderinfo &&
                 $currentOrderinfo['pay_status'] == '1' ||
                 $firstFreeActivity['m_id'] == $m_id) {
                // 活动已经开始时，就不能修改开始时间，判断结束时间是否在极限时间之内
                $startTime = M('tmarketing_info')->where(
                    array(
                        'id' => $m_id))->getField('start_time');
                if (time() > strtotime($startTime)) {
                    
                    // 检查要保存的时间是不是极限时间之前//用这个getLimit的条件是有当前活动的信息
                    $freeUseLimit = $this->DrawLotteryAdminModel->getLimit($firstFreeActivity['m_id'], $m_id, $this->node_id, $startTime);
                    if (strtotime($data['act_time_to']) >
                         strtotime($freeUseLimit)) {
                        $this->error('超出保存时间范围!');
                    }
                } else { // 活动未开始时，判断修改的活动时长是否在购买的时长范围内
                    $limitDuringDay = $this->DrawLotteryAdminModel->getLimitDuringTime(
                        $firstFreeActivity['m_id'], $m_id, $this->node_id);
                    $readyDuringDay = (int) ((strtotime($readyData['end_time']) -
                         strtotime($readyData['start_time']) + 1) / 86400);
                    if ($limitDuringDay < $readyDuringDay) {
                        $this->error('超出保存时间范围!');
                    }
                }
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
        
        // 刷新redis缓存
//        if ($add_flag) {
//            $this->DrawLotteryBaseService->generateDrawLotteryFinalDataWithStorage($m_id);
//        } else {
//            $this->DrawLotteryBaseService->generateDrawLotteryFinalDataWithoutStorage($m_id);
//        }
        $this->DrawLotteryBaseService->modifyMarkertingInfo($m_id);

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
                'batch_type' => CommonConst::BATCH_TYPE_WEEL))->find();
        if (! $marketInfo) {
            $this->error('参数错误');
        }
        $isReEdit = I('isReEdit', '1');
        $this->redirect('LabelAdmin/BindChannel/index', 
            array(
                'batch_id' => $m_id, 
                'batch_type' => CommonConst::BATCH_TYPE_WEEL, 
                'isReEdit' => $isReEdit));
    }
}
