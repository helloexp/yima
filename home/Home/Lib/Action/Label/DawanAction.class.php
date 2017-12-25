<?php

class DawanAction extends MyBaseAction {
    // 微信用户id
    public $wxid;

    public $js_global = array();

    const BATCH_TYPE_DAWAN = 44;

    const DEFAULT_GAME_NUMBER = 3;
    // 默认次数
    const MAX_SCORE_PER_SECOND = 30;
    // 每秒频率
    const MAX_SHARE_NUM = 1000;
    // 最多分享数
    public $_tmpSessName = '';
    // 临时session变量，根据不同的营销活动id来定义
    public $_errorMsg = '';
    // 记如错误信息
    public function _initialize() {
        // 校验是否微信登录
        parent::_initialize();
        if ($this->batch_type != self::BATCH_TYPE_DAWAN) {
            $this->error(
                array(
                    'errorImg' => '__PUBLIC__/Label/Image/waperro5.png', 
                    'errorTxt' => '错误访问！', 
                    'errorSoftTxt' => '你的访问地址出错啦~'));
        }
        
        $this->_tmpSessName = '_label_dawan_sess';
        // 活动信息
        $marketInfo = $this->marketInfo;
        
        $jp_arr = $this->searchJp();
        $this->assign('marketInfo', $marketInfo);
        $this->assign('id', $this->id);
        $this->assign('jp_arr', $jp_arr);
    }
    // 首页（登录）
    public function index() {
        $userInfo = $this->_getUserInfo();
        $mobile = '';
        if ($userInfo) {
            $mobile = $userInfo['mobile'];
        }
        
        $ruleDetail = $this->_getChangciData($this->marketInfo);
        $currentRule = $ruleDetail['current'] or
             $currentRule = $ruleDetail['next'];
        $this->_getRank();
        $this->assign('mobile', $mobile);
        $this->assign('lastRule', $currentRule);
        
        $this->display();
    }
    // 玩游戏页
    public function playgame() {
        if ($this->batch_type != self::BATCH_TYPE_DAWAN) {
            $this->error(
                array(
                    'errorImg' => '__PUBLIC__/Label/Image/waperro5.png', 
                    'errorTxt' => '错误访问！', 
                    'errorSoftTxt' => '你的访问地址出错啦~'));
        }
        
        // 校验活动有效性
        $overdue = $this->checkDate();
        if ($overdue === false)
            $this->error("该活动不在有效期之内！");
            
            // 更新访问量
        import('@.Vendor.DataStat');
        $opt = new DataStat($this->id, $this->full_id);
        $opt->UpdateRecord();
        if (! $this->_getUserInfo()) {
            redirect(U('index', I('get.')));
        }
        $globalJs = array(
            'score_url' => U('gameScore', 
                array(
                    'id' => $this->id)), 
            'initgame_url' => U('initGame', 
                array(
                    'id' => $this->id)));
        $config_data = unserialize($this->marketInfo['config_data']);
        $top_score = 0;
        $location_flag = $config_data['location_flag'];
        $ruleDetail = $this->_getChangciData($this->marketInfo);
        if (! empty($ruleDetail['current'])) {
            $game_time = $ruleDetail['current']['game_time'];
            $globalJs['error_code'] = '0';
            $globalJs['error_msg'] = '';
            $globalJs['top_score'] = $top_score;
            $globalJs['location_flag'] = $location_flag;
            $globalJs['game_time'] = $game_time;
        } elseif (! empty($ruleDetail['next'])) {
            $globalJs['error_code'] = '1';
            $globalJs['error_msg'] = '下一场将在:' .
                 $ruleDetail['next']['rule_begin_time'] . '开始';
        } else {
            $globalJs['error_code'] = '1';
            $globalJs['error_msg'] = '今天游戏已结束';
        }
        
        $this->_getRank();
        $this->assign('globalJs', json_encode($globalJs));
        $this->display(); // 输出模板
    }
    
    // 查询奖品
    protected function searchJp($data = array()) {
        $jp_sql = "SELECT b.id as cid, b.name,b.score,b.min_rank,b.max_rank,c.batch_name,c.batch_img,c.remain_num FROM tcj_batch a
				LEFT JOIN tcj_cate b ON a.cj_cate_id = b.id 
				LEFT JOIN tbatch_info c ON a.b_id=c.id
				WHERE  a.status =1  AND a.batch_id ='" .
             $this->batch_id . "' order by b.id";
        $jp_arr = M()->query($jp_sql);
        return $jp_arr;
    }
    // 发送游戏分数
    public function gameScore() {
        $score = I('score', '', 'intval');
        if ($score < 0) {
            $this->error('参数错误');
        }
        
        $userInfo = $this->_getUserInfo();
        if (! $userInfo) {
            $this->error(
                array(
                    'status' => '999', 
                    'info' => '未登录'));
        }
        
        $ruleDetail = $this->_getChangciData($this->marketInfo);
        $gameTime = isset($ruleDetail['current']['game_time']) ? $ruleDetail['current']['game_time'] : 60; // 最长游戏时间
        
        if ($score > self::MAX_SCORE_PER_SECOND * $gameTime) {
            log_write("参数异常 score:" . $score);
            $this->error('非法数值!');
        }
        
        log_write(print_r(I('post.'), true));
        M()->startTrans();
        // 查看时间有效性
        $ccData = $this->_getChangciData($this->marketInfo);
        if (! $ccData) {
            $this->error('未到时间');
        }
        $currentRule = $ccData['current'];
        if (! $currentRule) {
            $this->error('活动已结束');
        }
        $batch_number = $currentRule['new_batch_number'];
        if (! $batch_number) {
            $this->error('batch_number不存在');
        }
        $start_time = date('Y-m-d ') . $currentRule['rule_begin_time'];
        $end_time = date('Y-m-d ') . $currentRule['rule_end_time'];
        // 记录游戏流水
        $data = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'wx_user_id' => $userInfo['user_id'], 
            'score' => $score, 
            'batch_number' => $batch_number, 
            'add_time' => date('YmdHis'));
        $query = M('twx_dawan_trace')->add($data);
        if (! $query) {
            M()->rollback();
            $this->error('流水记录失败');
        }
        // 插入到分数表
        $where = array(
            'wx_user_id' => $userInfo['user_id'], 
            'batch_id' => $this->batch_id, 
            'batch_number' => $batch_number);
        $info = M('twx_dawan_score')->where($where)->find();
        if (! $info) {
            // 插入
            $data = array(
                'wx_user_id' => $userInfo['user_id'], 
                'batch_id' => $this->batch_id, 
                'batch_number' => $batch_number, 
                'max_score' => $score, 
                'node_id' => $this->node_id, 
                'first_count' => 1, 
                'update_time' => date('YmdHis'), 
                'mobile' => $userInfo['mobile'], 
                'batch_channel_id' => $this->id);
            $result = M('twx_dawan_score')->data($data)->add();
            if (! $result) {
                M()->rollback();
                log_write(M()->_sql() . ';' . M()->getDbError());
                $this->error('系统正忙[02]');
            }
        } // 如果存在，并且分数更高则更新
elseif ($score > $info['max_score']) {
            $data = array(
                'max_score' => $score, 
                'batch_channel_id' => $this->id, 
                'mobile' => $userInfo['mobile']);
            $where['id'] = $info['id'];
            $result = M('twx_dawan_score')->where($where)
                ->data($data)
                ->save();
            if ($result === false) {
                M()->rollback();
                log_write(M()->_sql() . ';' . M()->getDbError());
                $this->error('系统正忙[03]');
            }
        }
        // 插入到场次表
        $where = array(
            'batch_number' => $batch_number, 
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id);
        $info = M('twx_dawan_sess')->where($where)->find();
        if (! $info) {
            $data = array(
                'batch_number' => $batch_number, 
                'node_id' => $this->node_id, 
                'batch_id' => $this->batch_id, 
                'start_time' => dateformat($start_time, 'YmdHis'), 
                'end_time' => dateformat($end_time, 'YmdHis'), 
                'add_time' => date('YmdHis'));
            $result = M('twx_dawan_sess')->add($data);
            if (! $result) {
                M()->rollback();
                log_write(M()->_sql() . ';' . M()->getDbError());
                $this->error('系统正忙[04]' . M()->_sql());
            }
        }
        
        M()->commit();
        log_write('wxid:' . $this->wxid . ',score:' . $score);
        $this->success('成功啦');
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

    /*
     * 根据手机号生成用户号 @param $autoLogin 是否直接登录
     */
    public function _getWapUserIdByMobile($mobile) {
        // 校验手机号
        if (! check_str($mobile, array(
            'strtype' => 'mobile'))) {
            $this->_errorMsg = '手机号不正确';
            return false;
        }
        $info = M('twx_wap_user')->where(
            array(
                'openid' => $mobile))->find();
        // 如果没有，自动插入表全局表
        if (! $info) {
            $userid = M('twx_wap_user')->add(
                array(
                    'openid' => $mobile, 
                    'mobile' => $mobile, 
                    'nickname' => $mobile, 
                    'user_type' => 1, 
                    'node_id' => $this->node_id, 
                    'label_id' => $this->id, 
                    'add_time' => date('YmdHis')));
        } else {
            $userid = $info['id'];
        }
        return $userid;
    }
    
    // 用手机号登录(返回json）
    public function loginByMobile($mobile) {
        // 用手机号登录(json)
        $user_id = $this->_getWapUserIdByMobile($mobile);
        if (! $user_id) {
            $this->error("登录失败：" . $this->_errorMsg);
        }
        // 保存session
        $data = array(
            'user_id' => $user_id, 
            'mobile' => $mobile);
        session($this->_tmpSessName, $data);
        $this->success('success');
    }
    
    // 获取用户信息
    public function _getUserInfo() {
        return session($this->_tmpSessName);
    }
    // 获取当前 场次 或下一场信息
    public function _getChangciData($info) {
        $data = unserialize($info['config_data']);
        $rule_detail = $data['rule_detail'];
        log_write(print_r($rule_detail, true));
        /*
         * $data = array( //场次 array( '1'=> //时间 array(
         * 'rule_begin_time'=>'10:20', 'rule_end_time'=>'10:20',
         * 'bach_number'=>1, ), '2'=> //时间 array( 'begin_time'=>'10:20',
         * 'end_time'=>'10:20', 'bach_number'=>2, ), ) );
         */
        $nowtime = date('H:i');
        $ChangciInfo = array();
        $nextInfos = array(); // 其它场次
                              // 查看当前场次时间
        foreach ($rule_detail as $v) {
            $v['new_batch_number'] = date('Ymd-') . $v['batch_number'];
            if (! $ChangciInfo && $v['rule_begin_time'] <= $nowtime &&
                 $v['rule_end_time'] >= $nowtime) {
                $ChangciInfo = $v;
            } else {
                $nextInfos[$v['rule_begin_time']] = $v;
            }
        }
        // 查询下一场时间
        ksort($nextInfos);
        $nextRuleInfo = array();
        foreach ($nextInfos as $v) {
            if (! $nextRuleInfo && $v['rule_begin_time'] >= $nowtime) {
                $nextRuleInfo = $v;
                break;
            }
        }
        // 找到最近的时间
        return array(
            'current' => $ChangciInfo, 
            'next' => $nextRuleInfo);
    }

    public function _getCateId() {
        // 排名
    }
    // 初始化游戏数据
    public function initGame() {
        $marketInfo = $this->marketInfo;
        $config_data = unserialize($marketInfo['config_data']);
        $location_flag = $config_data['location_flag'];
        if ($location_flag) {
            $latitude = I('latitude');
            $longitude = I('longitude');
            if (! $latitude || ! $longitude) {
                $this->error('获取位置失败');
            }
            $storeInfo = $this->_getStoreInfo($latitude, $longitude);
            if (! $storeInfo) {
                $this->error('附近没有有效门店');
            }
        }
        $this->success('success');
    }
    // 奖品明细
    public function details() {
        $this->_getRank();
        $this->display();
    }
    
    // 获取最近门店
    public function _getStoreInfo($lbs_x, $lbs_y) {
        $marketInfo = $this->marketInfo;
        $config_data = unserialize($marketInfo['config_data']);
        $store_list = $config_data['store_list'];
        $where = array(
            'store_id' => array(
                'in', 
                $store_list));
        $distance = 2 / 100;
        $where['_string'] = " lbs_x >($lbs_x-$distance) and lbs_x < ($lbs_x+$distance) and lbs_y > ($lbs_y-$distance) and lbs_y<($lbs_y+$distance)";
        $storeArr = M('tstore_info')->where($where)->find();
        log_write(print_r($storeArr, true) . M()->_sql());
        return $storeArr;
    }

    /*
     * 返回当前名称 return array( 'my_score'=>$my_score, //分数 'my_rank'=>$my_rank,
     * //排名 'max_score'=>$max_score,//最高分 'diff_score'=>$diff_score,//相差的分数 );
     */
    protected function _getRank() {
        $userInfo = $this->_getUserInfo();
        if (! $userInfo) {
            $this->assign('my_rank', 0);
            $this->assign('diff_score', 0);
            return false;
        }
        $ruleInfo = $this->_getChangciData($this->marketInfo);
        if (empty($ruleInfo['current'])) {
            $this->assign('my_rank', 0);
            $this->assign('diff_score', 0);
            return false;
        }
        $batch_number = $ruleInfo['current']['new_batch_number'];
        // 查询名次
        $where = array(
            'wx_user_id' => $userInfo['user_id'], 
            'batch_id' => $this->batch_id, 
            'batch_number' => $batch_number);
        $my_score = M('twx_dawan_score')->where($where)->getField('max_score') or
             $my_score = 0;
        // 查询名次
        $where = array(
            'batch_id' => $this->batch_id, 
            'batch_number' => $batch_number, 
            'max_score' => array(
                'egt', 
                $my_score));
        $my_rank = M('twx_dawan_score')->where($where)->count();
        $max_score = M('twx_dawan_score')->where($where)->getField(
            "max(max_score) a");
        $diff_score = $max_score - $my_score;
        $arr = array(
            'my_score' => $my_score, 
            'my_rank' => $my_rank, 
            'max_score' => $max_score, 
            'diff_score' => $diff_score);
        foreach ($arr as $k => $v) {
            $this->assign($k, $v);
        }
        return $arr;
    }
}