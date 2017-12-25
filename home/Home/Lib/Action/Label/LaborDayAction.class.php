<?php

class LaborDayAction extends MyBaseAction {
    // 跳转回来地址
    const __TRUE_BACK_URL__ = '__TRUE_BACK_URL__';
    // 微信用户id
    public $expiresTime = 600;
    // 手机验证码过期时间
    public $wxid;

    public $openId;
    // 微信openId
    public $js_global = array();

    public $game_score = array(
        100, 
        50, 
        20, 
        10, 
        0);

    public $wap_sess_name = '';

    const BATCH_TYPE_SPRING = 45;

    const DEFAULT_GAME_NUMBER = 3;
    // 默认次数
    const MAX_SCORE = 200;

    const MAX_SHARE_NUM = 1000;
    // 最多分享数
    public function _initialize() {
        if (I('_sid_', '') == 'w') {
            $_SERVER['HTTP_USER_AGENT'] = 'MicroMessenger';
            session('wxid', 1);
        }
        
        // 重置数据
        if (I('_sid_') == 'clean') {
            session('wxid', null);
            session('from_user_id', null);
            redirect(U('', array(
                'id' => $this->id)));
            exit();
        }
        parent::_initialize();
        
        $this->wap_sess_name = 'LaborDayWap_' . $this->node_id;
        if ($this->batch_type != self::BATCH_TYPE_SPRING) {
            $this->error(
                array(
                    'errorImg' => '__PUBLIC__/Label/Image/waperro5.png', 
                    'errorTxt' => '错误访问！', 
                    'errorSoftTxt' => '你的访问地址出错啦~'));
        }
        // 设置语言
        // $this->_setLang();
        
        if (ACTION_NAME == 'playGame' || ACTION_NAME == 'getPrize') {
            $this->_labor_checklogin(false);
        }
        
        $this->wxid = session($this->wap_sess_name);
        
        // 活动信息
        $marketInfo = $this->marketInfo;
        
        // 分享信息
        $uparr = C('BATCH_IMG_PATH_NAME');
        $shareUrl = U('index', array(
            'id' => $this->id), '', '', TRUE);
        $wx_share_config = D('WeiXin', 'Service')->getWxShareConfig();
        $this->assign('wx_share_config', $wx_share_config);
        $shareArr = array(
            'config' => $wx_share_config, 
            'link' => $shareUrl, 
            'title' => $marketInfo['wap_title'], 
            // 'shareNote'=>$row['wap_info'],
            'desc' => '劳动最光荣！', 
            // 'imgUrl' => C('CURRENT_DOMAIN') .
            // 'Home/Public/Label/Image/20150501/' . L('IMAGE_ICON')
            'imgUrl' => C('CURRENT_DOMAIN') .
                 'Home/Public/Label/Image/20150501/icon.png');
        $this->assign('marketInfo', $marketInfo);
        $this->assign('shareData', $shareArr);
        $this->assign('id', $this->id);
        $this->assign('wxid', $this->wxid);
    }
    
    // 首页
    public function index() {
        if ($this->batch_type != self::BATCH_TYPE_SPRING) {
            $this->error(
                array(
                    'errorImg' => '__PUBLIC__/Label/Image/waperro5.png', 
                    'errorTxt' => '错误访问！', 
                    'errorSoftTxt' => '你的访问地址出错啦~'));
        }
        // 校验活动是否在有效期中
        $stauts = $this->marketInfo;
        if ($stauts['status'] == 2) {
            $this->error('活动不在有效期中！');
        }
        // 初始化小秋
        if ($this->wxid != '') {
            $map = array(
                'wx_user_id' => $this->wxid, 
                'batch_id' => $this->batch_id, 
                'add_time' => array(
                    'like', 
                    date('Ymd') . '%'));
            $used_times = intval(M('twx_laborday_trace')->where($map)->count());
            $this->assign('remain_times', 3 - $used_times);
        }
        
        // 访问量
        import('@.Vendor.DataStat');
        $opt = new DataStat($this->id, $this->full_id);
        $opt->UpdateRecord();
        
        // 奖品显示
        $jp_arr = $this->searchJp();
        
        // 告诉前端游戏参与方式
        $this->assign('join_mode', $this->marketInfo['join_mode']);
        $this->assign('id', $this->id);
        $this->assign('batch_type', $this->batch_type);
        $this->assign('batch_id', $this->batch_id);
        $this->assign('jp_arr', $jp_arr);
        $this->display(); // 输出模板
    }
    // 登录
    public function check_code() {
        $code = I('code', 'null');
        $mobile = I('phone', 'null');
        if (empty($code) || empty($mobile)) {
            exit(
                json_encode(
                    array(
                        'info' => '提交验证码或者手机号码不能为空！', 
                        'status' => 1)));
        }
        $phoneCheckCode = session('checkCode');
        if (empty($phoneCheckCode) || $phoneCheckCode['number'] != $code) {
            exit(
                json_encode(
                    array(
                        'info' => '手机验证码不正确！', 
                        'status' => 1)));
        }
        if (time() - $phoneCheckCode['add_time'] > $this->expiresTime) {
            exit(
                json_encode(
                    array(
                        'info' => '手机验证码已经过期！', 
                        'status' => 1)));
        }
        // 手机号码和验证码正确，存储用户数据
        $map = array(
            'user_type' => '1', 
            'openid' => $mobile, 
            'mobile' => $mobile);
        // 'node_id'=>$this->node_id,
        
        $info = M('twx_wap_user')->where($map)->find();
        if (! $info) {
            $data = $map;
            $data['add_time'] = date('YmdHis');
            $data['label_id'] = $this->id;
            $data['node_id'] = $this->node_id;
            M()->startTrans();
            $member_id = M('twx_wap_user')->add($data);
            if ($member_id === false) {
                M()->rollback();
                exit(
                    json_encode(
                        array(
                            'info' => '手机登录失败！', 
                            'status' => 1)));
            }
            // 手机登录成功，应该将twx_labordayscore里面写进一条记录
            $info = M('twx_wap_user')->where($map)->find();
        }
        session('LaborDay_mobile', $mobile);
        $date = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'wx_user_id' => $info['id']);
        if (! M('twx_laborday_score')->where($date)->find()) {
            $res = M('twx_laborday_score')->add($date);
            if ($res === false) {
                M()->rollback();
                exit(
                    json_encode(
                        array(
                            'info' => '手机登录失败！', 
                            'status' => 1)));
            }
        }
        
        M()->commit();
        
        $member_id = $info['id'];
        session($this->wap_sess_name, $member_id);
        exit(
            json_encode(
                array(
                    'info' => '登录成功进入游戏界面！', 
                    'status' => 0, 
                    'return_url' => U('playGame', 
                        array(
                            'id' => $this->id)))));
    }

    /**
     * 根据营销活动参数判断session，获取是否登录
     *
     * @param bool $return
     */
    public function _labor_checklogin($return = true) {
        if (session('?' . $this->wap_sess_name) && $return)
            return true;
        $join_mode = $this->marketInfo['join_mode'];
        $member_join_flag = $this->marketInfo['member_join_flag'];
        $login = false;
        $userid = '';
        if ($join_mode == '0') {
            $login = session('?' . $this->wap_sess_name);
            if (! $login && ! $return) {
                $this->redirect("Label/LaborDay/index", 
                    array(
                        'id' => $this->id, 
                        'type' => 0));
            }
            if ($login) {
                $userid = session($this->wap_sess_name);
            }
        } else {
            $backurl = U('', I('get.'), '', '', true);
            $backurl = urlencode($backurl);
            if ($join_mode == '1' && $member_join_flag == '0') {
                $login = session('?node_wxid_global');
                $jumpurl = U('Label/WeixinLoginGlobal/index', 
                    array(
                        'id' => $this->id, 
                        'type' => 0, 
                        'backurl' => $backurl));
                if ($login)
                    $info = session('node_wxid_global');
            }
            if ($join_mode == '1' && $member_join_flag == '1') {
                $login = session('?node_wxid_' . $this->node_id);
                $jumpurl = U(
                    'Label/WeixinLoginNode/wechatAuthorizeAndRedirectByDefault', 
                    array(
                        'id' => $this->id, 
                        'type' => 0, 
                        'backurl' => $backurl));
                log_write('k12345' . $jumpurl);
                if ($login)
                    $info = session('node_wxid_' . $this->node_id);
            }
            if (! $login && ! $return) {
                redirect($jumpurl);
            }
            if ($login) {
                $openid = $info['openid'];
                // 判断是否是粉丝
                if ($join_mode == '1' && $member_join_flag == '1') {
                    $count = M('twx_user')->where(
                        "openid = '{$openid}' and ifnull(subscribe, '0') != '0'")->count();
                    if ($count != 1) {
                        if (IS_AJAX) {
                            exit(
                                json_encode(
                                    array(
                                        'info' => '请先关注公众号再参与游戏！', 
                                        'status' => 1)));
                        } else {
                            if ($this->marketInfo['fans_collect_url'] != '') {
                                redirect($this->marketInfo['fans_collect_url']);
                            }
                            $this->error('请先关注公众号再参与游戏！');
                        }
                    }
                }
                $map = array(
                    'user_type' => '0', 
                    'openid' => $openid);
                $user = M('twx_wap_user')->where($map)->find();
                M()->startTrans();
                if (! $user) {
                    $data = $map;
                    $data['add_time'] = date('YmdHis');
                    $data['label_id'] = $this->id;
                    $data['node_id'] = $this->node_id;
                    $userid = M('twx_wap_user')->add($data);
                    if ($userid === false) {
                        M()->rollback();
                        exit(
                            json_encode(
                                array(
                                    'info' => '手机登录失败！', 
                                    'status' => 1)));
                    }
                    // 将用户信息写进twx_wap_user表中，同时应该给用户生成一个sorce表格
                    $user = M('twx_wap_user')->where($map)->find();
                }
                // 将用户信息写进twx_wap_user表中，同时应该给用户生成一个sorce表格
                $map1 = array(
                    'node_id' => $this->node_id, 
                    'batch_id' => $this->batch_id, 
                    'wx_user_id' => $user['id']);
                $user1 = M('twx_laborday_score')->where($map1)->find();
                if (! $user1) {
                    $date = array(
                        'node_id' => $this->node_id, 
                        'batch_id' => $this->batch_id, 
                        'wx_user_id' => $user['id']);
                    $res = M('twx_laborday_score')->add($date);
                    if ($res === false) {
                        exit(
                            json_encode(
                                array(
                                    'info' => '微信登录失败！', 
                                    'status' => 1)));
                    }
                }
                M()->commit();
                $userid = $user['id'];
            }
        }
        session($this->wap_sess_name, $userid);
        return $login;
    }
    
    // 游戏
    public function playGame() {
        $overdue = $this->checkDate();
        if ($overdue === false)
            $this->error("该活动不在有效期之内！");
        $myscore = $this->searchMyInfo();
        // 设置js
        $this->_setGlobalJs(
            array(
                'nowcode' => $myscore['score'],  // 分数
                'game_number' => $myscore['game_number'],  // 剩下次数
                'nowball' => $myscore['game_number'] ? 3 : 0)); // 劳动数
        
        $this->assign('info', $myscore);
        $this->display();
    }
    
    // 强制刷新
    public function getInfo() {
        $myscore = $this->searchMyInfo();
        $this->success($myscore);
    }
    
    // 领取奖品
    public function getPrize() {
        $overdue = $this->checkDate();
        if ($overdue === false)
            $this->error("该活动不在有效期之内！");
        $myscore = $this->searchMyInfo();
        $jp_arr = $this->searchJp();
        // 已领奖项
        $user_id = M('twx_laborday_score')->where(
            array(
                'wx_user_id' => $this->wxid, 
                'batch_id' => $this->batch_id))->getField('id');
        $map = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'status' => '2', 
            'user_id' => $user_id);
        $cjcate_arr = M('tcj_trace')->field('cate_id')
            ->where($map)
            ->select();
        $zj_cate_arr = array();
        if ($cjcate_arr) {
            foreach ($cjcate_arr as $cjcate) {
                $zj_cate_arr[] = $cjcate['cate_id'];
            }
        }
        $this->assign('join_mode', $this->marketInfo['join_mode']);
        $this->assign('mobile', session('LaborDay_mobile'));
        $this->assign('zj_cate_arr', $zj_cate_arr);
        $this->assign('myscore', $myscore);
        $this->assign('jp_arr', $jp_arr);
        $this->display();
    }
    
    // 查询奖品
    protected function searchJp($data = array()) {
        $jp_sql = "SELECT b.id as cid, b.name,b.score ,c.batch_name,c.batch_img,c.remain_num FROM tcj_batch a
				LEFT JOIN tcj_cate b ON a.cj_cate_id = b.id 
				LEFT JOIN tbatch_info c ON a.b_id=c.id
				WHERE  a.status =1  AND a.batch_id ='" .
             $this->batch_id . "' order by b.id";
        $jp_arr = M()->query($jp_sql);
        return $jp_arr;
    }
    
    // 当前活动个人统计查询
    protected function searchMyInfo() {
        $map = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'wx_user_id' => $this->wxid);
        $map1 = $map;
        $map1['_string'] = "add_time like '" . date('Ymd') . "%'";
        $gamenumber = 3 - intval(M('twx_laborday_trace')->where($map1)->count());
        $score = intval(M('twx_laborday_score')->where($map)->getField('score'));
        $myranking = array(
            'game_number' => $gamenumber, 
            'score' => $score);
        return $myranking;
    }
    
    // 当前用户的好友，总分，总小球数
    protected function MyCount() {
        $map = array(
            'node_id' => $this->node_id, 
            'wx_user_id' => $this->wxid, 
            'batch_id' => $this->batch_id);
        $info = M('twx_laborday_score')->where($map)->find();
        
        $array = array(
            'all_score' => $info['score']);
        return $array;
    }
    
    // 发送游戏分数
    public function gameScore() {
        // 通过随机数来判断等级
        $codeRandom = mt_rand(1, 100);
        if ($codeRandom <= 10) {
            $i = 0;
        } else if ($codeRandom > 10 && $codeRandom <= 40) {
            $i = 1;
        } else if ($codeRandom > 40 && $codeRandom <= 80) {
            $i = 2;
        } else if ($codeRandom > 80 && $codeRandom <= 95) {
            $i = 3;
        } else if ($codeRandom > 95) {
            $i = 4;
        }
        $code = array(
            100, 
            50, 
            20, 
            10, 
            0);
        $score = $code[$i];
        $success_num = I('success_num', '', 'intval');
        if ($score < 0) {
            $this->responseJson(- 1, '参数错误');
        }
        M()->startTrans();
        // 能不能玩
        $map = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'wx_user_id' => $this->wxid);
        $info = M('twx_laborday_score')->where($map)
            ->lock(true)
            ->find();
        if (! $info) {
            log_write('syserror...........' . M()->_sql());
            $this->responseJson(- 1, '系统错误！');
        }
        $map2 = $map;
        $map2['_string'] = "add_time like '" . date('Ymd') . "%'";
        $traceInfo = M('twx_laborday_trace')->where($map2)->count();
        if ($traceInfo >= 3) {
            M()->rollback();
            $this->responseJson(- 1, '不能再玩了');
        }
        
        // 记录游戏流水
        $data = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'wx_user_id' => $this->wxid, 
            'score' => $score, 
            'add_time' => date('YmdHis'));
        $flag = M('twx_laborday_trace')->add($data);
        if ($flag === false) {
            M()->rollback();
            $this->responseJson(- 1, '流水记录失败');
        }
        
        $remain_score = $info['score'] + $score;
        $data = array(
            'ranking_count' => $info['ranking_count'] + $score, 
            'score' => $remain_score);
        $flag = M('twx_laborday_score')->where($map)->save($data);
        M()->commit();
        log_write('wxid:' . $this->wxid . ',score:' . $score);
        
        $map = array(
            'batch_id' => $this->batch_id);
        $jp_list = M('tcj_cate')->where($map)
            ->order('score')
            ->select();
        if (! $jp_list) {
            $this->responseJson(0, '成功啦', 
                array(
                    'score' => $score, 
                    'tag' => 0));
        }
        
        $match = 0;
        $cate_name = '';
        $tag = 1;
        $prech = 0;
        foreach ($jp_list as $key => $jp) {
            if ($jp['score'] > $remain_score) {
                $match = $jp['score'];
                $cate_name = $jp['name'];
                break;
            }
        }
        if ($match == 0) {
            $tag = 2;
        } else {
            // 相差积分''
            $prech = $match - $remain_score;
        }
        $this->responseJson(0, '成功啦', 
            array(
                'score' => $score, 
                'tag' => $tag, 
                'prech' => $prech, 
                'cate_name' => $cate_name));
    }
    
    // 领取奖品
    public function submitCj() {
        if (! $this->_labor_checklogin()) {
            $this->ajaxReturn("error", 
                '登录超时！&nbsp;&nbsp;&nbsp;<a href="' . U('index', 
                    array(
                        'id' => $this->id)) . '">返回</a>', 0);
        }
        
        $overdue = $this->checkDate();
        if ($overdue === false)
            $this->ajaxReturn("error", "该活动不在有效期之内！", 0);
        $mobile = I('mobile', NULL);
        $cate_id = I('cate_id', NULL);
        
        if (! is_numeric(trim($mobile)) || strlen(trim($mobile)) != '11') {
            $this->ajaxReturn("error", "请正确填写手机号！", 0);
        }
        
        $map = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'wx_user_id' => $this->wxid);
        
        if (empty($cate_id)) {
            $this->ajaxReturn("error", "参数错误！" . $cate_id, 0);
        }
        $map = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'id' => $cate_id);
        $score = M('tcj_cate')->where($map)->getField('score');
        $mycount = $this->MyCount();
        if ((int) $score > (int) $mycount['all_score']) {
            $this->ajaxReturn("error", "未达到该奖项的金币！", 0);
        }
        // 每个奖项只能领取一次
        $map = array(
            'wap_user_id' => $this->wxid, 
            'cate_id' => $cate_id, 
            'batch_id' => $this->batch_id);
        $cjcount = M('twx_laborday_gethis')->where($map)->count();
        if ($cjcount > 0) {
            $this->ajaxReturn("error", "该奖项只能领取一次", 0);
        }
        
        // 减去领奖的积分
        M()->startTrans();
        $save = array(
            'score' => array(
                'exp', 
                'score-' . $score));
        $query = M('twx_laborday_score')->where(
            array(
                'node_id' => $this->node_id, 
                'batch_id' => $this->batch_id, 
                'wx_user_id' => $this->wxid))->save($save);
        if ($query === false) {
            M()->rollback();
            $this->ajaxReturn("error", "金币扣减失败！", 0);
        }
        $other_arr = array(
            'wx_cjcate_id' => $cate_id);
        if ($this->marketInfo['join_mode'] == '1') {
            $sess = $this->marketInfo['member_join_flag'] ? session(
                'node_wxid_' . $this->node_id) : session('node_wxid_global');
            $other_arr['wx_open_id'] = $sess['openid'];
            $other_arr['wx_nick'] = $sess['nickname'];
        }
        
        import('@.Vendor.ChouJiang');
        $t_mobile = $this->marketInfo['join_mode'] == '1' ? '' : $mobile;
        $choujiang = new ChouJiang($this->id, $t_mobile, $this->full_id, '', 
            $other_arr);
        $resp = $choujiang->send_code();
        if ($resp['resp_id'] == '0000') {
            if ($this->marketInfo['join_mode'] == '1') {
                if (empty($resp['request_id'])) {
                    M()->rollback();
                    $this->ajaxReturn("error", '很遗憾领取失败', 0);
                }
                
                $cj_trace_id = $resp['cj_trace_id'];
                $request_id = $resp['request_id'];
                
                // 修改数据库中的手机号字段，并且调用重发接口
                $result = M('tcj_trace')->where(
                    array(
                        'id' => $cj_trace_id))->
                // 'user_id'=>$this->wxid,
                // 'cate_id'=>$cate_id
                save(array(
                    'send_mobile' => $mobile));
                // 修改发码表的字段
                $result = M('tbarcode_trace')->where(
                    array(
                        'request_id' => $request_id))->save(
                    array(
                        'phone_no' => $mobile));
                M()->commit();
                // 然后调用重发接口
                import("@.Vendor.CjInterface");
                $req = new CjInterface();
                $result = $req->cj_resend(
                    array(
                        'request_id' => $request_id, 
                        'node_id' => $this->node_id, 
                        'user_id' => '00000000'));
                if (! $result || $result['resp_id'] != '0000') {
                    M()->rollback();
                    $this->ajaxReturn("error", '很遗憾领取失败', 0);
                }
            }
            
            $data = array(
                'wap_user_id' => $this->wxid, 
                'cate_id' => $cate_id, 
                'batch_id' => $this->batch_id, 
                'node_id' => $this->node_id, 
                'add_time' => date('YmdHis'));
            $cjcount = M('twx_laborday_gethis')->add($data);
            M()->commit();
            $this->ajaxReturn("sucess", "领取成功", 1);
        } else {
            M()->rollback();
            $fail_arr = array(
                '1001', 
                '1002', 
                '1003', 
                '1006', 
                '1005', 
                '1016', 
                '1014');
            if (in_array($resp['resp_id'], $fail_arr))
                $resp_msg = "您来晚了，该奖品已经被抢光了";
            else
                $resp_msg = "很遗憾领取失败";
            
            $this->ajaxReturn("error", $resp_msg, 0);
        }
    }

    protected function _setGlobalJs($arr = array()) {
        $this->js_global = array_merge($this->js_global, $arr);
        $this->assign('js_global', json_encode($this->js_global));
    }
    
    // 响应json
    protected function responseJson($code, $msg, $data = array(), $debug = array()) {
        if (IS_AJAX) {
            header('Content-type:text/json;charset=utf-8');
        }
        $resp = array(
            'code' => $code, 
            'msg' => $msg, 
            'data' => $data);
        if (C('SHOW_PAGE_TRACE')) {
            $resp['debug'] = $debug;
        }
        log_write(print_r($resp, true), 'RESPONSE');
        echo json_encode($resp);
        tag('view_end');
        exit();
    }
}