<?php
import('@.Action.Label.MyBaseAction');

class LabelMatchGuessAction extends MyBaseAction {

    const BATCH_TYPE = 61;
    // 默认类型
    const QUIZ_TYPE_SINGLE = 1;
    // 竞猜类型，单场
    public $pufa_flag;
    //引导关注页的提示
    public $tipsForFan;

    public function _initialize() {
        C(require (CONF_PATH . 'Label/config.php')); // 引入公共label配置文件配置
        parent::_initialize();
        //$this->_checkUser(true);
        // if (empty($this->wxSess['openid'])) {
        //     $this->error(
        //         array(
        //             'errorImg' => '__PUBLIC__/Label/Image/waperro7.png',
        //             'errorTxt' => '错误访问！',
        //             'errorSoftTxt' => '请用微信客户端访问'
        //         )
        //     );
        // }
        
        //增加引导页的判断
        // 查询用户
        $wxOpenid = $this->wxSess['openid'];
        $where    = ['openid' => $wxOpenid, 'node_id' => $this->node_id, 'subscribe' => ['neq', '0']];
        $userInfo = D('LabelMatchGuess')->getWxUser($where);
        $tipsForFan = [];//引导微信粉丝提示
        if (empty($userInfo)) {
            $userInfo = array();
        }
        // 判断是否粉丝或者会员
        if (!$userInfo) {
            //log_write("只有微信粉丝才能参加该活动");
            if ($this->marketInfo['fans_collect_url']) {
                $tipsForFan = [
                    'link' => urlencode($this->marketInfo['fans_collect_url']),
                    'msg' => '成为我们的粉丝后才能进行投票，是否马上加入？'
                ];
            }
        } else {
            $memberBatchId = $this->marketInfo['member_batch_id'];
            if ($memberBatchId != -1) {
                $member_batch_arr = explode(',', $memberBatchId);
                if (!in_array($userInfo['group_id'], $member_batch_arr)) {
                    $tipsForFan = [
                        'link' => '',
                        'msg' => '本活动只限部分粉丝参与，谢谢您的关注。敬请留意我们的其它活动！'
                    ];
                }
            }
        }
        
        $this->tipsForFan = $tipsForFan;
    }

    public function index() {
        if ($this->batch_type != self::BATCH_TYPE) {
            $this->error(
                array(
                    'errorImg' => '__PUBLIC__/Label/Image/waperro5.png', 
                    'errorTxt' => '错误访问！', 
                    'errorSoftTxt' => '你的访问地址出错啦~'));
        }
        
        // 访问量
        import('@.Vendor.ClickStat');
        $opt = new ClickStat();
        $id = $this->id;
        $opt->updateStat($id);
        
        // 活动
        $batchModel = M('tmarketing_info');
        $row = $batchModel->where(
            array(
                'id' => $this->batch_id))->find();
        
        // 队伍图标
        $sessionNumber = $row['defined_one_name'];
        $sessionInfo = M()->table('tworld_cup_events a')
            ->field('a.*,b.team_name team1_name, c.team_name team2_name')
            ->join('tworld_cup_team_info b on b.team_id=a.team1_id')
            ->join('tworld_cup_team_info c on c.team_id=a.team2_id')
            ->where(array(
            'a.session_id' => $sessionNumber))
            ->find();
        $this->assign('sessionInfo', $sessionInfo);
        //前端比分参数
        $this->assign('score', json_encode(['a' => $sessionInfo['team1_goal'], 'b' => $sessionInfo['team2_goal']]));
        
        // 奖品
        
        // 取奖品信息
        $prize_list = (array) M()->table("tcj_batch a")
            ->join('tbatch_info b on a.b_id = b.id')
            ->join('tcj_cate c on a.cj_cate_id = c.id')
            ->where(
            array(
                'a.batch_id' => $this->batch_id,
                'a.status' => '1'
            ))
            ->order('c.sort asc,a.id asc')
            ->field('a.id,a.cj_cate_id,b.batch_short_name, b.batch_img,c.name as cate_name,b.storage_num,b.batch_amt')
            ->select();
        $this->assign('prizeData', $prize_list);
        $prizeList = [];
        $len = count($prize_list);
        foreach ($prize_list as &$v) {
            $v['batch_img'] = get_upload_url($v['batch_img']);
            if (empty($prizeList)) {
                $prizeList[] = ['cj_cate_id' => $prize_list[0]['cj_cate_id'], 'prize' => [$prize_list[0]], 'cate_name' => $prize_list[0]['cate_name']];
            } else {
                $hasSame = false;
                foreach ($prizeList as $k => $vv) {
                    if ($vv['cj_cate_id'] == $v['cj_cate_id']) {
                        $prizeList[$k]['prize'][] = $v;
                        $hasSame = true;
                        break;
                    }
                }
                if ($hasSame == false) {
                    $cateObj = ['cj_cate_id' =>  $v['cj_cate_id'], 'prize' => [$v], 'cate_name' => $v['cate_name']];
                    $prizeList[] = $cateObj;
                }
            }
        }unset($v);
        //dump($prizeList);exit;
        $this->assign('prizeList', json_encode($prizeList));
        
        //比赛开始时间
        $beginTimeStamp = strtotime($sessionInfo['begin_time']) . '000';
        $this->assign('timeData', $beginTimeStamp);
        $result = M('tworld_cup_match_quiz')->where(
            array(
                'batch_id' => $this->batch_id,
                'wx_id' => $this->wxSess['openid']))->find();
        $this->assign('alreadySubmit', ($result ? '1' : '2'));//有没有参加过投票，1：已经参加，2：没有参加
        
        // 是否到了停止竞猜的时间：1比赛已经开始，不能竞猜了，2未开始，还能竞猜，3已经出了比分
        $hasBegun = '2';
        if ($sessionInfo['begin_time'] <= date('YmdHis')) {
            $hasBegun = '1';
        }
        if ($sessionInfo['result'] != '0') {
            $hasBegun = '3';
        }
        $this->assign('hasBegun', $hasBegun);
        
        $this->assign('choiceId', $result['team_id']);
        $this->assign('errmsg', $errmsg);
        $this->assign('id', $this->id);
        $this->assign('batch_type', $this->batch_type);
        $this->assign('batch_id', $this->batch_id);
        $this->assign('row', $row);
        //微信分享信息
        $wx_share_config = D('WeiXin', 'Service')->getWxShareConfig();
        $this->assign('wx_share_config', $wx_share_config);
        $shareUrl = U('ZtWorldcup/LabelMatchGuess/index', array('id' => $this->id), '', '', TRUE);
        $descript = '看比赛、猜胜负、赢大奖，决战欧陆之巅，带您燃情一夏！';
        $guessScore = '';
        if ($result) {
            $choiceTeamId = $result['team_id'];
            if ($choiceTeamId == '0') {
                $descript = '看比赛、猜胜负、赢大奖！'.$sessionInfo['team1_name'].'VS'.$sessionInfo['team2_name'].'，我看两队势均力敌，你呢？';
            } else {
                $choiceTeamName = $sessionInfo['team2_name'];
                if ($choiceTeamId == $sessionInfo['team1_id']) {
                    $choiceTeamName = $sessionInfo['team1_name'];
                }
                $descript = '看比赛、猜胜负、赢大奖！'.$sessionInfo['team1_name'].'VS'.$sessionInfo['team2_name'].'，我看好'.$choiceTeamName.'哦，你呢？';
            }
            $guessScore = $result['score'];
        }
        
        $this->assign('guessScore', $guessScore);
        
        $matchResult = $result ? ['id' => $choiceTeamId, 'name' => ($choiceTeamId == '0') ? '平局' : $choiceTeamName] : [];
        //前端比赛接口数据
        $this->assign('matchResult', json_encode($matchResult));
        $shareArr = array(
            'config' => $wx_share_config,
            'link' => $shareUrl,
            'title' => '燃情欧洲杯，全民大竞猜',
            // 'shareNote'=>$row['wap_info'],
            'desc' => $descript,
            'imgUrl' => C('CURRENT_DOMAIN') . 'Home/Public/Label/Image/worldcup/wx-share.png'
        );
        //$this->assign('testShareUrl', $shareUrl);
        $this->assign('shareData', $shareArr);
        
        $getPrizeRecord = ['status' => '0'];//未开始抽奖
        if ($sessionInfo['result'] != '0') {
            $getPrizeRecord['status'] = '3';//未参与
            if ($result) {
                $getPrizeRecord['status'] = '1';//抽奖未中
                $cjTrace = D('LabelMatchGuess')->getAwardList(
                    [
                        'mobile' => ['in', [$result['wx_id'], $result['phone_no']]],
                        'batch_id' => $this->batch_id,
                        'batch_type' => $this->batch_type
                    ]
                    );
                if ($cjTrace && $cjTrace[0]['status'] == '2') {
                    $jp = $cjTrace[0];
                    $getPrizeRecord['status'] = '2';//中奖了
                    $getPrizeRecord['name'] = $jp['batch_short_name'];
                    $getPrizeRecord['type'] = $this->getGoodsType($jp);
                    $img = get_upload_url($jp['batch_img']);
//                     if ($getPrizeRecord['type'] == 2) {
//                         $img = get_upload_url('./Home/Public/Label/Image/worldcup/img2.jpg');
//                     } elseif ($getPrizeRecord['type'] == 3) {
//                         $img = get_upload_url('./Home/Public/Label/Image/worldcup/img3.jpg');
//                     }
                    $getPrizeRecord['img'] = $img;
                    $getPrizeRecord['link_url'] =
                    ($getPrizeRecord['type'] == '3') ? U('Label/Member/index', ['node_id' => $row['node_id']], false, false,
                        true) : '';//积分查看链接,是积分的话就给
                    $getPrizeRecord['batch_amt'] = ($getPrizeRecord['type'] == '3') ? round($jp['batch_amt']) : $jp['batch_amt'];
                }
            }
        }
        //log_write('欧洲杯中奖信息：' . json_encode($getPrizeRecord));
        $this->assign('getPrizeRecord', json_encode($getPrizeRecord));
        
        //支持人数
        $tCount = D('LabelMatchGuess')->getSupportNo(
                $row['node_id'], 
                $this->batch_id, 
                [
                    $sessionInfo['team1_id'], 
                    $sessionInfo['team2_id']
                ],
                $sessionNumber
            );
        $this->assign('tCount', json_encode(['a' => $tCount['a_count'], 'b' => $tCount['b_count'], 'c' => $tCount['c_count']]));
        
        
        $this->assign('tipsForFan', json_encode($this->tipsForFan));//微信粉丝引导页提示
        
        $historyPrize = D('LabelMatchGuess')->getHistoryPrizeList($this->node_id, $this->wxSess['openid']);
        foreach ($historyPrize as $k => $prize) {
            $historyPrize[$k]['type'] = $this->getGoodsType(['source' => $prize['batch_type'], 'goods_type' => $prize['batch_class']]);
            $historyPrize[$k]['img'] = get_upload_url($prize['img']);
            $historyPrize[$k]['url'] = ($historyPrize[$k]['type'] == '3') ? U('Label/Member/index', ['node_id' => $this->node_id], false, false,
                        true) : '';//积分查看链接,是积分的话就给
            $historyPrize[$k]['batch_amt'] = ($historyPrize[$k]['type'] == '3') ? round($prize['batch_amt']) : $prize['batch_amt'];
        }
        $this->assign('historyPrize', json_encode($historyPrize));
        if ($row['defined_three_name'] == 1) {//猜比分模版
            $this->display('index1');
        } else {
            $this->display(); // 输出模板 猜胜负
        }
    }

    /**
     * 竞猜
     */
    public function submit() {
        $id = $this->id;
        $overdue = $this->checkDate();
        if ($overdue === false)
            $this->ajaxReturn(['code_type' => '3', 'msg' => '该活动不在有效期之内！'], "该活动不在有效期之内！", 0);
            // 是否抽奖
        $query_arr = M('tmarketing_info')->field(
            'is_cj,start_time,end_time,defined_one_name,defined_three_name,node_id')
            ->where(
            array(
                'id' => $this->batch_id, 
                'batch_type' => $this->batch_type))
            ->find();
        if ($query_arr['is_cj'] != '1')
            $this->ajaxReturn(['code_type' => '3', 'msg' => '未查询到抽奖活动！'], "未查询到抽奖活动！", 0);
        
        if (! $this->isPost()) {
            $this->ajaxReturn(['code_type' => '3', 'msg' => '非法提交！'], "非法提交！", 0);
        }
        if (!empty($this->tipsForFan)) {//是否有微信粉丝的关注提示
            $this->ajaxReturn(['code_type' => '3', 'msg' => $this->tipsForFan['msg']], "", 0);
        }
        
        // 手机
        $mobile = I('post.phone', '', 'trim');
        
        // 队伍：
        $team_id = I('post.team_id', '', 'trim');
        
        $verify = I('post.verify_code', '', 'trim');
        if (md5($verify) != session('verify_cj')) {
            $this->ajaxReturn(['code_type' => '2', 'msg' => '验证码错误！'], "验证码错误！", 0);
        }
        
        
        if (! is_numeric(trim($mobile)) || strlen(trim($mobile)) != '11')
            $this->ajaxReturn(['code_type' => '1', 'msg' => '您的手机号错误！'], "您的手机号错误！", 0);
        
        session('verify_cj', null);
        
        if (empty($id))
            $this->ajaxReturn(['code_type' => '3', 'msg' => '错误的请求！'], "错误的请求！", 0);
        
            
            // 竞猜处理
        $sessionNumber = $query_arr['defined_one_name'];
        
        $sessionInfo = M()->table('tworld_cup_events a')
            ->field('a.team1_id,a.team2_id,a.begin_time,a.result')
            ->where(array(
            'a.session_id' => $sessionNumber))
            ->find();
        if (! $sessionInfo) {
            $this->ajaxReturn(['code_type' => '3', 'msg' => '哎呀来晚了，竞猜已结束，下次赶早哦'], "哎呀来晚了，竞猜已结束，下次赶早哦", 0);
        }
        
        if ($sessionInfo['begin_time'] <= date('YmdHis') ||
             $sessionInfo['result'] != '0') {
            $this->ajaxReturn(['code_type' => '3', 'msg' => '赛事已经开始，不允许竞猜'], "赛事已经开始，不允许竞猜", 0);
        }
        
        if ($query_arr['defined_three_name'] == '0' && $team_id != '0') {//先决条件是猜胜负的活动模式，才会有队伍id
            if ($team_id != $sessionInfo['team1_id'] &&
                 $team_id != $sessionInfo['team2_id']) {
                $this->ajaxReturn(['code_type' => '3', 'msg' => '竞猜队伍错误'], "竞猜队伍错误", 0);
            }
        }
        
        //用户猜的比分
        $guessScore = '';
        if ($query_arr['defined_three_name']) {
            $guessScore = $this->checkScore();
            $teamScoreArr = explode(':', $guessScore);
            if ($teamScoreArr[0] == $teamScoreArr[1]) {
                $this->ajaxReturn(['code_type' => '3', 'msg' => '本场比赛最终结果不会出现平局哦'], "本场比赛最终结果不会出现平局哦", 0);
            }
            if ($teamScoreArr[0] > $teamScoreArr[1]) {
                $team_id = $sessionInfo['team1_id'];
            } else {
                $team_id = $sessionInfo['team2_id'];
            }
        }
        
        $count = M('tworld_cup_match_quiz')->where(
            array(
                'batch_id' => $this->batch_id, 
                'wx_id' => $this->wxSess['openid']))->count();
        if ($count > 0)
            $this->ajaxReturn(['code_type' => '3', 'msg' => '您已经参与过本场竞猜活动'], "您已经参与过本场竞猜活动", 0);
            
            // 校验竞猜队
            // 插入竞猜表
        M()->startTrans();
        $trace_data = array(
            'node_id' => $query_arr['node_id'], 
            'batch_id' => $this->batch_id, 
            'session_id' => $sessionNumber, 
            'phone_no' => $mobile, 
            'team_id' => $team_id, 
            'quiz_type' => self::QUIZ_TYPE_SINGLE, 
            'add_time' => date('YmdHis'), 
            'label_id' => $this->id,
            'wx_id' => $this->wxSess['openid'], 
            'score' => $guessScore
        );
        
        $result_id = M('tworld_cup_match_quiz')->add($trace_data);
        if ($result_id === false) {
            M()->rollback();
            $this->ajaxReturn(['code_type' => '3', 'msg' => '系统错误，请重试！'], "系统错误，请重试！", 0);
        }
        // 更新参与人数
        M('tmarketing_info')->where("id='{$this->batch_id}'")->setInc(
            'cj_count');
        // =========结束
        // tag('view_end');
        // M()->rollback();
        //查看如果是会员不用绑定，如果不是需要绑定
        addMemberByO2o($mobile, $query_arr['node_id'], $this->channel_id, $this->batch_id);
        M()->commit();
        //深圳平安非标
        $params=['node_id'=>$this->node_id,'openid'=>$this->wxSess['openid']];
        B('FbSzpaMatchGuess',$params);
//         $msg = '选择成功，我们会在比赛结束之后10分钟内进行抽奖，如果您中奖了，我们会以短信的形式通知您中奖信息!';
//         if ($this->pufa_flag)
//             $msg = '谢谢您的参与，得奖信息请留意【浦发银行信用卡-广州】世界杯大猜享活动得奖公告栏！';
        $this->ajaxReturn("success", '竞猜结果已提交成功！<br/>感谢您的参与！', 1);
    }
    
    /**
     * 根据奖品的信息返还提供给前端的奖品种类
     * @param array $jp
     * @return int $type
     */
    private function getGoodsType($jp) {
        $type = 1;//翼码卡券（用于前端区分）
        if (isset($jp['source']) && ($jp['source'] == '6' || $jp['source'] == '7')) {//自建微信红包，翼码代发红包
            $type = 2;
        } elseif ($jp['goods_type'] == '14') {//积分
            $type = 3;
        }
        return $type;
    }
    
    private function checkScore() {
        $guess_team1_score = I('guess_team1_score');
        $guess_team2_score = I('guess_team2_score');
        $error = '';
        $checkResult = check_str($guess_team1_score, ['strtype' => 'int', 'maxval' => 99, 'minval' => 0], $error);
        if ($checkResult !== true) {
            $this->ajaxReturn(['code_type' => '3', 'msg' => '提交的比分有误！'], "提交的比分有误！", 0);
        }
        $checkResult = check_str($guess_team2_score, ['strtype' => 'int', 'maxval' => 99, 'minval' => 0], $error);
        if ($checkResult !== true) {
            $this->ajaxReturn(['code_type' => '3', 'msg' => '提交的比分有误！'], "提交的比分有误！", 0);
        }
        return $guess_team1_score . ':' . $guess_team2_score;
    }


}