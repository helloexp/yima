<?php

class DuanWuAction extends MyBaseAction {
    // 跳转回来地址
    const __TRUE_BACK_URL__ = '__TRUE_BACK_URL__';
    // 微信用户id
    public $expiresTime = 600;
    // 手机验证码过期时间
    public $wxid;

    public $openId = "";
    // 微信openId
    public $js_global = array();

    public $wap_sess_name = '';

    const BATCH_TYPE_SPRING = 49;

    const DEFAULT_GAME_NUMBER = 3;
    // 默认次数
    const MAX_SCORE = 200;

    const MAX_SHARE_NUM = 1000;
    // 最多分享数
    const DUANWU_TIME = 20150620;

    public function _initialize() {
        if (I('_sid_', '') == 'w') {
            $_SERVER['HTTP_USER_AGENT'] = 'MicroMessenger';
        }
        parent::_initialize();
        // 判断是否是微信过来的
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') === false) {
            echo "请从微信访问";
            exit();
            // $this->error('请使用微信扫码二维码进入活动');
        }
        $this->wap_sess_name = 'node_wxid_' . $this->node_id;
        if ($this->batch_type != self::BATCH_TYPE_SPRING)
            $this->error('错误访问！');
        if (ACTION_NAME == 'playGame' || ACTION_NAME == 'duanwuCj' ||
             ACTION_NAME == 'foodlist' || ACTION_NAME == 'mycardlist' ||
             ACTION_NAME == 'share') {
            $this->_duanwu_checklogin(false);
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
            'title' => "仟吉端午全民摇摇摇，一起”粽“大奖！", 
            'desc' => "美食的最大诱惑在于用心。2015，仟吉“吉鹿”再一次踏遍全国，为您寻找美味芳粽！", 
            'imgUrl' => C('CURRENT_DOMAIN') .
                 'Home/Public/Label/Image/qianji/Item/qianji_share.jpg');
        $this->assign('marketInfo', $marketInfo);
        $this->assign('shareData', $shareArr);
        $this->assign('id', $this->id);
        $this->assign('wxid', $this->wxid);
    }
    
    // 首页
    public function index() {
        if ($this->batch_type != self::BATCH_TYPE_SPRING)
            $this->error('错误访问！');
        $food_arr = array(
            '0' => '腐乳食材', 
            '1' => '东坡肉食材', 
            '2' => '草莓食材', 
            '3' => '蛋黄食材', 
            '4' => '红豆食材', 
            '5' => '酱肉排骨食材', 
            '6' => '鲜肉食材', 
            '7' => '五谷杂粮食材');
        // 查询用户中奖信息
        $list = $this->select_jp();
        // 初始化小秋
        if ($this->wxid != '') {
            $map = array(
                'wx_user_id' => $this->wxid, 
                'batch_id' => $this->batch_id, 
                "node_id" => $this->node_id, 
                'add_time' => array(
                    'like', 
                    date('Ymd') . '%'));
            $used_times = intval(M('twx_duanwu_trace')->where($map)->count());
            $this->assign('remain_times', 3 - $used_times);
        }
        // 访问量
        import('@.Vendor.DataStat');
        $opt = new DataStat($this->id, $this->full_id);
        $opt->UpdateRecord();
        // 告诉前端游戏参与方式
        $this->assign('join_mode', $this->marketInfo['join_mode']);
        $this->assign('id', $this->id);
        $this->assign('batch_type', $this->batch_type);
        $this->assign('batch_id', $this->batch_id);
        $this->assign('list', $list);
        $this->assign('food_arr', $food_arr);
        $this->display(); // 输出模板
    }

    /**
     * 根据营销活动参数判断session，获取是否登录
     *
     * @param bool $return
     */
    public function _duanwu_checklogin($return = true) {
        if (session('?' . $this->wap_sess_name) && $return)
            return true;
        $join_mode = $this->marketInfo['join_mode'];
        $member_join_flag = $this->marketInfo['member_join_flag'];
        $login = false;
        $userid = '';
        $backurl = U('', I('get.'), '', '', true);
        $backurl = urlencode($backurl);
        // 微信参与并且非粉丝才会去授权
        if ($join_mode == '1' && $member_join_flag == '0') {
            // 取全局的微信服务号
            $login = session('?node_wxid_global');
            $jumpurl = U('Label/WeixinLoginGlobal/index', 
                array(
                    'id' => $this->id, 
                    'type' => 1, 
                    'backurl' => $backurl));
            if ($login)
                $info = session('node_wxid_global');
        }
        if (! $login && ! $return) {
            redirect($jumpurl);
        }
        if ($login) {
            $openid = $info['openid'];
            $map = array(
                'user_type' => '0', 
                'openid' => $openid);
            $user = M('twx_wap_user')->where($map)->find();
            M()->startTrans();
            if (! $user) {
                $data = $map;
                $data['nickname'] = $info['nickname'];
                $data['access_token'] = $info['access_token'];
                $data['sex'] = $info['sex'];
                $data['province'] = $info['province'];
                $data['city'] = $info['city'];
                $data['headimgurl'] = $info['headimgurl'];
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
            M()->commit();
            $userid = $user['id'];
        }
        session($this->wap_sess_name, $userid);
        return $login;
    }
    
    // 响应json
    protected function responseJson($code, $msg, $data = array(), $card = array(), 
        $debug = array()) {
        if (IS_AJAX) {
            header('Content-type:text/json;charset=utf-8');
        }
        $resp = array(
            'code' => $code, 
            'msg' => $msg, 
            'data' => $data, 
            'card' => $card);
        if (C('SHOW_PAGE_TRACE')) {
            $resp['debug'] = $debug;
        }
        echo json_encode($resp);
        exit();
    }
    
    // 端午节中奖逻辑
    public function duanwuCj() {
        // 检测活动在不在有效期
        $overdue = $this->checkDate();
        if ($overdue === 1) {
            exit(
                json_encode(
                    array(
                        'info' => '活动还未开始，敬请关注！', 
                        'status' => 0)));
        } elseif ($overdue === 2) {
            exit(
                json_encode(
                    array(
                        'info' => '活动已经过期！', 
                        'status' => 0)));
        }
        // 判断用户参与的次数
        // $this->check_user_time();
        // 生成100个随机数，并将数据打乱
        $duanwu = range(1, 100);
        shuffle($duanwu);
        // 定义材料数组，微信卡券数组，仟吉大奖
        $duanwu_cailiao = array_slice($duanwu, 0, 69);
        $duanwu_weixin = array_slice($duanwu, 69, 30);
        $qianji_array = array_slice($duanwu, - 1, 1);
        // 用户随机生成一个数
        $player_scorce = mt_rand(1, 100);
        // 判断用户生成的数，是否在材料中
        if (in_array($player_scorce, $duanwu_cailiao)) {
            $this->foods();
        }
        if (in_array($player_scorce, $duanwu_weixin)) {
            // 中的微信卡券，如果中过，必须中食材，或者中微信卡券，也会中食材
            $this->_weixincard();
        }
        if (in_array($player_scorce, $qianji_array)) {
            // 判断用户是否领取过该奖品
            $map2 = array(
                "wx_user_id" => $this->wxid, 
                "node_id" => $this->node_id, 
                'param1' => 2, 
                'batch_id' => $this->batch_id);
            $res1 = M("twx_duanwu_trace")->where($map2)->find();
            if ($res1) {
                $this->foods();
            }
            // 如果用户当天中过大奖，让用户随机中食材
            $this->_has_weixin();
            // 当天中了卡券，不让中大奖，并且大奖只让用一次,
            $this->_bigjp();
        }
    }
    
    // 中奖食材概率逻辑
    public function foods() {
        $config = array(
            array(
                1, 
                2, 
                3, 
                4, 
                5, 
                6, 
                7, 
                8), 
            array(
                1, 
                2, 
                5, 
                6, 
                7, 
                8, 
                3, 
                4), 
            array(
                2, 
                3, 
                5, 
                6, 
                7, 
                8, 
                1, 
                4), 
            array(
                1, 
                3, 
                4, 
                6, 
                7, 
                8, 
                2, 
                5));
        // 用户openid的第一个数
        $weixin_info = session('node_wxid_global');
        $number = ord($weixin_info['openid']) % 4;
        $base = $config[$number];
        $arr1 = array_slice($base, 0, 6);
        $arr2 = array_slice($base, 6, 2);
        $tmp_arr = range(1, 100);
        if ($tmp_arr[mt_rand(1, 100)] > 98) {
            $out = $arr2[mt_rand(0, 1)];
        } else {
            $out = $arr1[mt_rand(0, 5)];
        }
        // 判断用户是否加上该食材，如果是8个，则重新抽奖，如果为8个，最难中的，也重新抽奖
        $this->_nojp($out);
        $this->add_foods($out - 1);
    }
    
    // 中奖微信卡券的方法
    public function _weixincard() {
        // 查询用户当天是否中到了微信卡券
        $this->_has_weixin();
        // 通过m_id查询batch_info_id
        $map = array(
            "batch_type" => 49, 
            'batch_id' => $this->batch_id, 
            "node_id" => $this->node_id, 
            '_string' => "name = '现金券'");
        $cj_cate_id = M("tcj_cate")->where($map)->getField("id");
        $card_list = M("tcj_batch")->where(
            array(
                "cj_cate_id" => $cj_cate_id, 
                'status' => 1, 
                "node_id" => $this->node_id))->select();
        // 如果所有卡券的状态不正常，给用户食材
        if (! $card_list) {
            $this->foods();
        }
        $weixin_1 = mt_rand(0, count($card_list) - 1);
        // 如果当天限额了次数
        $day_count = $card_list[$weixin_1]['day_count'];
        // 查询所有当天该奖品中奖数量，通过$cj_cate_id在trace表里面统计此时
        $map3 = array(
            "cj_cate_id " => $cj_cate_id, 
            "batch_id" => $this->batch_id, 
            "node_id" => $this->node_id);
        $map3['_string'] = "add_time like '" . date('Ymd') . "%'";
        $trace_count = M("twx_duanwu_trace")->where($map3)->count();
        // 如果当天中奖
        if ($trace_count >= $day_count) {
            $this->foods();
        }
        // 此为用户中奖的信息
        $cj_cate_id = $card_list[$weixin_1]['cj_cate_id'];
        $cj_batch_id = $card_list[$weixin_1]['id'];
        // 随机用户中哪个奖品，查询用户中的奖品在库存中是否还有,status状态为0，正常
        $map3 = array(
            "node_id" => $this->node_id, 
            "id" => $card_list[$weixin_1]['b_id'], 
            "status" => 0);
        $remain_num = M("tbatch_info")->where($map3)->getField('remain_num');
        // 如果库存不足,让用户中食材
        if ($remain_num <= 0) {
            $this->foods();
        }
        $this->returnCjWx($cj_cate_id, $cj_batch_id, '');
    }
    
    // 检测用户参与次数
    public function check_user_time() {
        // 通过openid来查询用户参与次数
        $wx_user_id = $this->wxid;
        $map = array(
            "wx_user_id" => $wx_user_id, 
            "batch_id" => $this->batch_id, 
            "node_id" => $this->node_id);
        $map['_string'] = "add_time like '" . date('Ymd') .
             "%' and ifnull(param1, '') != 1";
        $traceInfo = M("twx_duanwu_trace")->where($map)->count();
        if ($traceInfo >= 3) {
            exit(
                json_encode(
                    array(
                        'info' => '您今天已经参与三次了', 
                        'status' => 0)));
        }
    }
    
    // 判断用户当天是否中过微信卡券
    public function _has_weixin() {
        // 获取用户的wx_user_id
        $wx_user_id = $this->wxid;
        // 判断用户如果中了10张优惠券，则让用户中食材
        $map1 = array(
            "wx_user_id" => $wx_user_id, 
            "node_id" => $this->node_id, 
            "type" => 1, 
            "param1" => "", 
            'batch_id' => $this->batch_id);
        $trace_count = M("twx_duanwu_trace")->where($map1)->count();
        if ($trace_count >= 10) {
            $this->foods();
        }
        $map = array(
            "wx_user_id" => $wx_user_id, 
            "node_id" => $this->node_id, 
            "type" => 1, 
            'batch_id' => $this->batch_id);
        $map['_string'] = "add_time like '" . date('Ymd') .
             "%' and ifnull(param1, '') != '1'";
        $traceInfo = M("twx_duanwu_trace")->where($map)->find();
        // 如果当天有中微信卡券的记录，让用户一定中食材
        if ($traceInfo) {
            $this->foods();
        }
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
        $traceInfo = M("twx_duanwu_score")->where($map)->find();
        if ($traceInfo) {
            return $traceInfo;
        }
    }
    
    // 中微信卡券需要存入数据流水
    public function addcard_trace($cj_batch_id, $param1, $trace_id) {
        $wx_user_id = $this->wxid;
        // 开启事物
        $trace_food = array(
            "wx_user_id" => $wx_user_id, 
            "type" => 1, 
            "node_id" => $this->node_id, 
            'jp_id' => $cj_batch_id, 
            "batch_id" => $this->batch_id, 
            'param1' => $param1, 
            'cj_traceid' => $trace_id, 
            "add_time" => date('YmdHis', time()));
        $res_trace = M("twx_duanwu_trace")->add($trace_food);
        if ($res_trace === false) {
            log_write("[qjdw]存入中奖流水失败" . $trace_food);
        }
    }
    
    // 中食材，需存入数据
    public function add_foods($food_index) {
        // 进来表示用户已经中奖,将用户中奖信息，写入中奖流水表
        $wx_user_id = $this->wxid;
        // 开启事物
        M()->startTrans();
        $trace_food = array(
            "wx_user_id" => $wx_user_id, 
            "type" => 0, 
            "node_id" => $this->node_id, 
            'jp_id' => $food_index, 
            "batch_id" => $this->batch_id, 
            "add_time" => date('YmdHis', time()));
        $res_trace = M("twx_duanwu_trace")->add($trace_food);
        if ($res_trace === false) {
            M()->rollback();
            log_write("存入中奖流水失败");
            exit(
                json_encode(
                    array(
                        'info' => '存入中奖流水失败！', 
                        'type' => 1, 
                        'status' => 0)));
        }
        $food_arr = array(
            '1' => '腐乳', 
            '2' => '东坡肉', 
            '3' => '草莓', 
            '4' => '蛋黄', 
            '5' => '红豆', 
            '6' => '酱肉排骨', 
            '7' => '鲜肉', 
            '8' => '五谷杂粮');
        // 获取用户是否有过食材wx_user_id
        $traceInfo = $this->has_food();
        // 如果有中食材记录
        if ($traceInfo) {
            // 取传输进来的食材
            $foods_number = json_decode($traceInfo["foods_number"], true);
            $foods_number[$food_index] = $foods_number[$food_index] + 1;
            $data = array(
                "foods_number" => json_encode($foods_number));
            $res = M("twx_duanwu_score")->where(
                array(
                    "wx_user_id" => $wx_user_id, 
                    "batch_id" => $this->batch_id, 
                    "node_id" => $this->node_id))->save($data);
            if ($res === false) {
                log_write("更新食材失败");
                M()->rollback();
                exit(
                    json_encode(
                        array(
                            'info' => '更新中奖食材失败！', 
                            'status' => 0)));
            }
            M()->commit();
            $data = array(
                'lottery' => 2, 
                'icon' => $food_index + 2);
            $this->responseJson(0, "恭喜您中的" . $food_arr[$food_index + 1], $data);
        } else {
            $arr = array(
                0, 
                0, 
                0, 
                0, 
                0, 
                0, 
                0, 
                0);
            $arr[$food_index] = 1;
            $wx_user_id = $this->wxid;
            $data = array(
                "wx_user_id" => $wx_user_id, 
                "foods_number" => json_encode($arr), 
                "node_id" => $this->node_id, 
                "batch_id" => $this->batch_id);
            
            $res = M("twx_duanwu_score")->add($data);
            if ($res === false) {
                M()->rollback();
                exit(
                    json_encode(
                        array(
                            'info' => '更新中奖食材失败！', 
                            'status' => 0)));
            }
            M()->commit();
            $data = array(
                'lottery' => 2, 
                'icon' => $food_index + 2);
            $this->responseJson(0, '恭喜您中的' . $food_arr[$food_index + 1], $data);
        }
    }
    
    // 中卡券代金券逻辑
    public function returnCjWx($wx_cjcate_id, $cj_batch_id, $param1) {
        log_write("开按微信登录抽奖");
        import('@.Vendor.ChouJiang');
        $wxUserInfo = $this->getWxUserInfo();
        // if (!$wxUserInfo) {
        // $this->error("请从微信登录");
        // }
        //
        $mobile = '';
        import('@.Vendor.ChouJiang');
        // 微信openid可以获取得到
        $wxUserInfo = session('node_wxid_global');
        $other = array(
            'wx_open_id' => $wxUserInfo['openid'], 
            'wx_nick' => $wxUserInfo['nickname'], 
            'wx_cjcate_id' => $wx_cjcate_id, 
            'wx_cjbatch_id' => $cj_batch_id);
        $choujiang = new ChouJiang($this->id, $mobile, $this->full_id, '', 
            $other);
        $resp = $choujiang->send_code();
        // 中奖提示
        $cjrule = M('tcj_rule');
        if (isset($resp['resp_id']) && $resp['resp_id'] == '0000') {
            $cj_msg = "";
            // 奖品类别
            $cjCateId = M()->table('tcj_batch b')
                ->join('tcj_cate c ON b.cj_cate_id=c.id')
                ->where(
                "b.id={$resp['rule_id']} and b.batch_id=" . $this->batch_id)
                ->getField('c.id');
            if (! $cjCateId) {
                log_write(M()->_sql());
            }
            $respData = array(
                'data' => $cjCateId, 
                'info' => $cj_msg, 
                'status' => 1);
            // 中了微信卡券
            if (! empty($resp['card_ext'])) {
                $respData['card_ext'] = $resp['card_ext'];
                $respData['card_id'] = $resp['card_id'];
                $data = array(
                    "lottery" => 1, 
                    "icon" => 1);
                $card = array(
                    'card_id' => $resp['card_id'], 
                    "card_ext" => $resp['card_ext']);
                $this->addcard_trace($cj_batch_id, $param1, 
                    $resp['cj_trace_id']);
                if ($param1) {
                    $data = array(
                        "lottery" => 1, 
                        "icon" => 0);
                    $this->responseJson(0, '恭喜您获取了179大礼盒', $data, $card);
                } else {
                    $this->responseJson(0, '恭喜您获取了代金券', $data, $card);
                }
            }
        } else {
            // 未中奖提示文字（如果用户没有中奖，一定让用户中食材）
            log_write("仟吉奖品完成领取完成返回" . $resp['resp_id']);
            $this->foods();
        }
    }
    
    // 中大奖逻辑
    public function _bigjp() {
        // 查询是否中大奖，如果中，让用户中食材
        $map1 = array(
            "wx_user_id" => $this->wxid, 
            "param1" => 2, 
            "batch_id" => $this->batch_id, 
            "node_id" => $this->node_id);
        $has_bjp = M("twx_duanwu_trace")->where($map1)->find();
        if ($has_bjp) {
            // 如果有值，让用户中食材
            $this->foods();
        }
        // 中大奖标识179元礼盒
        $param1 = 2;
        // 大奖奖品为
        $map = array(
            "batch_type" => 49, 
            "node_id" => $this->node_id, 
            '_string' => "name like '礼盒'", 
            'batch_id' => $this->batch_id);
        $cj_cate_id = M("tcj_cate")->where($map)->getField("id");
        // 大奖也进行随机
        $big_list = M("tcj_batch")->where(
            array(
                "cj_cate_id" => $cj_cate_id, 
                'status' => 1, 
                "node_id" => $this->node_id))->select();
        if (! $big_list) {
            $this->foods();
        }
        $weixin_1 = mt_rand(0, count($big_list) - 1);
        // 此为用户中奖的信息
        $cj_batch_id = $big_list[$weixin_1]['id'];
        $cj_cate_id = $big_list[$weixin_1]['cj_cate_id'];
        // 随机用户中哪个奖品，查询用户中的奖品在库存中是否还有,status状态为0，正常
        $map3 = array(
            "node_id" => $this->node_id, 
            "id" => $big_list[$weixin_1]['b_id'], 
            "status" => 0);
        $remain_num = M("tbatch_info")->where($map3)->getField('remain_num');
        // 如果库存不足,让用户中食材
        if ($remain_num <= 0) {
            $this->foods();
        }
        $this->returnCjWx($cj_cate_id, $cj_batch_id, $param1);
    }
    // 集齐八个材料兑换礼品
    public function conversionjp() {
        // 判断活动还没有开始提示
        $overdue = $this->checkDate();
        if ($overdue === 1) {
            exit(
                json_encode(
                    array(
                        'info' => '活动还未开始，敬请关注！', 
                        'status' => 0)));
        }
        // 判断当前时间爱你是否为6月26日，如果大于6月26日，无法领取
        $this_time = date('Ymd');
        if ($this_time > self::DUANWU_TIME) {
            exit(
                json_encode(
                    array(
                        'info' => '活动已经结束，无法兑换该奖品！', 
                        'status' => 0)));
        }
        // 判断用户是否领取过该奖品
        $map2 = array(
            "wx_user_id" => $this->wxid, 
            "node_id" => $this->node_id, 
            'param1' => 1, 
            'batch_id' => $this->batch_id);
        $res1 = M("twx_duanwu_trace")->where($map2)->find();
        if ($res1) {
            exit(
                json_encode(
                    array(
                        'info' => '您已经领取过该奖品啦！', 
                        'status' => 0)));
        }
        // 获取用户的信息
        M()->startTrans();
        $wx_user_id = $this->wxid;
        $traceInfo = $this->has_food();
        $foods_number = json_decode($traceInfo["foods_number"], true);
        // 判断用户是否有8个材料
        $food_count = "";
        foreach ($foods_number as $key => $val) {
            if ($val != 0) {
                $food_count ++;
            }
        }
        if ($food_count < 8) {
            exit(
                json_encode(
                    array(
                        'info' => '您的食材不够！', 
                        'status' => 0)));
        }
        foreach ($foods_number as $key => $val) {
            if ($val != "") {
                $foods_number[$key] = $val - 1;
            }
        }
        $data = array(
            "foods_number" => json_encode($foods_number));
        $res = M("twx_duanwu_score")->where(
            array(
                "wx_user_id" => $wx_user_id, 
                "batch_id" => $this->batch_id, 
                "node_id" => $this->node_id))->save($data);
        if ($res === false) {
            M()->rollback();
            exit(
                json_encode(
                    array(
                        'info' => '兑换奖品失败！', 
                        'status' => 0)));
        }
        // param1为1 为用户兑奖
        $param1 = 1;
        // 兑换奖品为
        $map = array(
            "batch_type" => 49, 
            "node_id" => $this->node_id, 
            '_string' => "name = '139礼盒'", 
            'batch_id' => $this->batch_id);
        $cj_cate_id = M("tcj_cate")->where($map)->getField("id");
        // 兑换奖品也进行随机（如果只有一个，则百分百中奖）
        $big_list = M("tcj_batch")->where(
            array(
                "cj_cate_id" => $cj_cate_id, 
                "status" => 1))->select();
        if (! $big_list) {
            M()->rollback();
            exit(
                json_encode(
                    array(
                        'info' => '该奖品已经被领取完啦！', 
                        'status' => 0)));
        }
        $weixin_1 = mt_rand(0, count($big_list) - 1);
        // 此为用户中奖的信息
        $cj_batch_id = $big_list[$weixin_1]['id'];
        $cj_cate_id = $big_list[$weixin_1]['cj_cate_id'];
        // //生成流水trace表param1为用户兑奖
        $trace_food = array(
            "wx_user_id" => $wx_user_id, 
            "jp_id" => $cj_batch_id, 
            "type" => 1, 
            "node_id" => $this->node_id, 
            "batch_id" => $this->batch_id, 
            'param1' => 1, 
            "add_time" => date('YmdHis', time()));
        $res_trace = M("twx_duanwu_trace")->add($trace_food);
        if ($res_trace === false) {
            M()->rollback();
            exit(
                json_encode(
                    array(
                        'info' => '存入中奖流水失败！', 
                        'type' => 1, 
                        'status' => 0)));
        }
        $this->returnCjWx8($cj_cate_id, $cj_batch_id, $res_trace);
    }
    // 我的卡券界面
    public function mycardlist() {
        // 查询我的卡券1为未领取，2为领取，领取卡券需要置灰
        $weixin_info = session('node_wxid_global');
        $card_list = M()->table("twx_assist_number a")->field(
            'a.status,a.card_batch_id,b.batch_short_name,b.card_id,b.batch_img')
            ->join('tbatch_info b on a.card_batch_id=b.id')
            ->where(
            array(
                "a.open_id" => $weixin_info['openid'], 
                'a.node_id' => $this->node_id, 
                'm_id' => $this->batch_id))
            ->order("a.status")
            ->select();
        $this->assign("card_list", $card_list);
        $this->assign('id', $this->id);
        $this->display();
    }
    // 游戏
    public function playGame() {
        // $overdue = $this->checkDate();
        // if ($overdue === false)
        // $this->error("该活动不在有效期之内！");
        $this->assign('id', $this->id);
        $this->display();
    }
    // 我的我的食材
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
    // 获取微信card_ext
    public function get_card_ext() {
        if (date('Ymd') > self::DUANWU_TIME) {
            exit(
                json_encode(
                    array(
                        'info' => '活动已经过期,无法领取卡券！', 
                        'status' => 0)));
        }
        $card_batch_id = I('card_batch_id');
        $open_id = session('node_wxid_global.openid');
        // 判断用户是否已经有领取卡券
        $where = array(
            'open_id' => $open_id, 
            'card_batch_id' => $card_batch_id, 
            'status' => 1);
        // 这条可以查找 用户有没有未领取的记录
        $assist_number = M()->table('twx_assist_number')
            ->where($where)
            ->find();
        log_write(M()->_sql(), 'SQL');
        if (! $assist_number) {
            exit(
                json_encode(
                    array(
                        'info' => "您已经领取过该卡券！", 
                        'status' => 0)));
        }
        $batchInfo = M('tbatch_info')->where(
            array(
                'id' => $assist_number['card_batch_id']))->find();
        log_write(M()->_sql(), 'SQL');
        
        $service = D('WeiXinCard', 'Service');
        $service->init_by_node_id($this->node_id);
        $card_ext = $service->add_assist_number($open_id, $card_batch_id);
        if (! empty($card_ext)) {
            exit(
                json_encode(
                    array(
                        'info' => $card_ext, 
                        'status' => 1)));
        } else {
            exit(
                json_encode(
                    array(
                        'info' => "获取微信card_ext失败！", 
                        'status' => 0)));
        }
    }
    // 集齐8种材料兑换卡券
    public function returnCjWx8($wx_cjcate_id, $cj_batch_id, $trace_id) {
        log_write("开按微信登录抽奖");
        import('@.Vendor.ChouJiang');
        // $wxUserInfo = $this->getWxUserInfo();
        // if (!$wxUserInfo) {
        // $this->error("请从微信登录");
        // }
        
        $mobile = '';
        import('@.Vendor.ChouJiang');
        // 微信openid可以获取得到
        $wxUserInfo = session('node_wxid_global');
        $other = array(
            'wx_open_id' => $wxUserInfo['openid'], 
            'wx_nick' => $wxUserInfo['nickname'], 
            'wx_cjcate_id' => $wx_cjcate_id, 
            'wx_cjbatch_id' => $cj_batch_id);
        $choujiang = new ChouJiang($this->id, $mobile, $this->full_id, '', 
            $other);
        $resp = $choujiang->send_code();
        // 中奖提示
        $cjrule = M('tcj_rule');
        if (isset($resp['resp_id']) && $resp['resp_id'] == '0000') {
            $cj_msg = "";
            // 奖品类别
            $cjCateId = M()->table('tcj_batch b')
                ->join('tcj_cate c ON b.cj_cate_id=c.id')
                ->where("b.id={$resp['rule_id']}")
                ->getField('c.id');
            if (! $cjCateId) {
                log_write(M()->_sql());
            }
            $respData = array(
                'data' => $cjCateId, 
                'info' => $cj_msg, 
                'status' => 1);
            // 中了微信卡券
            if (! empty($resp['card_ext'])) {
                $respData['card_ext'] = $resp['card_ext'];
                $respData['card_id'] = $resp['card_id'];
                $data = array(
                    "lottery" => 1, 
                    "icon" => 1);
                $card = array(
                    'card_id' => $resp['card_id'], 
                    "card_ext" => $resp['card_ext']);
                
                M('twx_duanwu_trace')->where(
                    array(
                        'id' => $trace_id))->save(
                    array(
                        'cj_traceid' => $resp['cj_trace_id']));
                M()->commit();
                $this->responseJson(0, '恭喜您获取了139礼盒', $data, $card);
            }
        } elseif (isset($resp['resp_id']) && $resp['resp_id'] == '1001') {
            M()->rollback();
            exit(
                json_encode(
                    array(
                        'info' => "对不起，礼盒已经被领取完了，您来晚了！", 
                        'status' => 0)));
        } elseif (isset($resp['resp_id']) && $resp['resp_id'] == '1006') {
            M()->rollback();
            exit(
                json_encode(
                    array(
                        'info' => "对不起，礼盒已经被领取完了，您来晚了！", 
                        'status' => 0)));
        } else {
            M()->rollback();
            exit(
                json_encode(
                    array(
                        'info' => "兑换失败！", 
                        'status' => 0)));
        }
    }
    // 查询用户所中奖奖品
    public function select_jp() {
        $list = M()->table("twx_duanwu_trace a")->field(
            'a.jp_id,b.nickname,b.headimgurl,d.batch_short_name')
            ->join("twx_wap_user b on b.id=a.wx_user_id")
            ->join("tcj_batch c on c.id=a.jp_id")
            ->join("tbatch_info d on d.id=c.b_id")
            ->where(array(
            "a.node_id" => $this->node_id))
            ->order("a.id desc")
            ->limit(50)
            ->select();
        return $list;
    }
    // 分享页面
    public function share() {
        // 获取用户的wx_user_id
        $wx_user_id = I('wx_user_id');
        // 查询用户的图片
        $name = M("twx_wap_user")->where(
            array(
                "id" => $wx_user_id, 
                "node_id" => $this->node_id))->select();
        $map = array(
            "wx_user_id" => $wx_user_id, 
            "type" => 0, 
            "batch_id" => $this->batch_id, 
            "node_id" => $this->node_id);
        $traceInfo = M("twx_duanwu_score")->where($map)->find();
        // 获取中奖个数
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
        $map1 = array(
            'a.wx_user_id' => $wx_user_id, 
            'a.type' => 1, 
            "a.batch_id" => $this->batch_id, 
            "a.node_id" => $this->node_id);
        $list_param1 = M()->table("twx_duanwu_trace a")->field(
            'a.jp_id,d.batch_short_name,e.goods_image')
            ->join("tcj_batch c on c.cj_cate_id=a.jp_id")
            ->join("tbatch_info d on d.id=c.b_id")
            ->join("tgoods_info e on e.goods_id=d.goods_id")
            ->order("a.add_time desc")
            ->where($map1)
            ->select();
        // $list_param1=M("twx_duanwu_trace")->where(array('wx_user_id'=>$wx_user_id,'param1'=>1))->select();
        $this->assign("foods_number", $fond_count);
        $this->assign("foods_number1", 8 - $fond_count);
        $this->assign('list_param1', $list_param1);
        $this->assign('name', $name);
        $this->display();
    }
    // 校验活动是否过期
    public function checkDate($showFlag = false) {
        $query_arr = $this->marketInfo;
        if (! empty($query_arr['start_time']) && ! empty($query_arr['end_time'])) {
            $this_time = date('YmdHis');
            if ($this_time < $query_arr['start_time']) {
                return 1;
            }
            if ($this_time > $query_arr['end_time']) {
                return 2;
            }
        }
        return true;
    }
    
    // 判断如果用户
    public function _nojp($out) {
        $traceInfo = $this->has_food();
        // 如果有中食材记录
        if ($traceInfo) {
            // 食材总数
            $count_total = "";
            // 食材为7
            $count_7 = "";
            // 中过食材的array
            $food_array = array();
            // 取传输进来的食材
            $foods_number = json_decode($traceInfo["foods_number"], true);
            foreach ($foods_number as $key => $val) {
                if ($val) {
                    $count_total ++;
                    // 中过食材的ayyay
                    $food_array[$key] = $key;
                } else {
                    // 哪个食材为没有值
                    $count_7 = $key + 1;
                }
            }
            $food_arr = array(
                '1' => '腐乳', 
                '2' => '东坡肉', 
                '3' => '草莓', 
                '4' => '蛋黄', 
                '5' => '红豆', 
                '6' => '酱肉排骨', 
                '7' => '鲜肉', 
                '8' => '五谷杂粮');
            if ($count_total == 7) {
                // 判断传入的食材和中的食材进行比较
                if ($count_7 == $out) {
                    // 随机用户已经中过的食材
                    $food_index = array_rand($food_array);
                    // 用户中的哪个食材，存入流水，更新score表
                    M()->startTrans();
                    $trace_food = array(
                        "wx_user_id" => $this->wxid, 
                        "type" => 0, 
                        "node_id" => $this->node_id, 
                        'jp_id' => $food_index, 
                        "batch_id" => $this->batch_id, 
                        "add_time" => date('YmdHis', time()));
                    $res_trace = M("twx_duanwu_trace")->add($trace_food);
                    if ($res_trace === false) {
                        M()->rollback();
                        log_write("存入中奖流水失败");
                        exit(
                            json_encode(
                                array(
                                    'info' => '存入中奖流水失败！', 
                                    'type' => 1, 
                                    'status' => 0)));
                    }
                    // 更新中奖食材
                    $foods_number[$food_index] = $foods_number[$food_index] + 1;
                    $data = array(
                        "foods_number" => json_encode($foods_number));
                    $res = M("twx_duanwu_score")->where(
                        array(
                            "wx_user_id" => $this->wxid, 
                            "batch_id" => $this->batch_id, 
                            "node_id" => $this->node_id))->save($data);
                    if ($res === false) {
                        log_write("更新食材失败");
                        M()->rollback();
                        exit(
                            json_encode(
                                array(
                                    'info' => '更新中奖食材失败！', 
                                    'status' => 0)));
                    }
                    M()->commit();
                    $data = array(
                        'lottery' => 2, 
                        'icon' => $food_index + 2);
                    $this->responseJson(0, "恭喜您中的" . $food_arr[$food_index + 1], 
                        $data);
                }
            } elseif ($count_total == 8) {
                $config = array(
                    array(
                        1, 
                        2, 
                        3, 
                        4, 
                        5, 
                        6, 
                        7, 
                        8), 
                    array(
                        1, 
                        2, 
                        5, 
                        6, 
                        7, 
                        8, 
                        3, 
                        4), 
                    array(
                        2, 
                        3, 
                        5, 
                        6, 
                        7, 
                        8, 
                        1, 
                        4), 
                    array(
                        1, 
                        3, 
                        4, 
                        6, 
                        7, 
                        8, 
                        2, 
                        5));
                // 用户openid的第一个数
                $weixin_info = session('node_wxid_global');
                $number = ord($weixin_info['openid']) % 4;
                $base = $config[$number];
                $arr1 = array_slice($base, 0, 6);
                // 随机用户已经中过的食材
                $food_index = $arr1[array_rand($arr1)] - 1;
                // 用户中的哪个食材，存入流水，更新score表
                M()->startTrans();
                $trace_food = array(
                    "wx_user_id" => $this->wxid, 
                    "type" => 0, 
                    "node_id" => $this->node_id, 
                    'jp_id' => $food_index, 
                    "batch_id" => $this->batch_id, 
                    "add_time" => date('YmdHis', time()));
                $res_trace = M("twx_duanwu_trace")->add($trace_food);
                if ($res_trace === false) {
                    M()->rollback();
                    log_write("存入中奖流水失败");
                    exit(
                        json_encode(
                            array(
                                'info' => '存入中奖流水失败！', 
                                'type' => 1, 
                                'status' => 0)));
                }
                // 更新中奖食材
                $foods_number[$food_index] = $foods_number[$food_index] + 1;
                $data = array(
                    "foods_number" => json_encode($foods_number));
                $res = M("twx_duanwu_score")->where(
                    array(
                        "wx_user_id" => $this->wxid, 
                        "batch_id" => $this->batch_id, 
                        "node_id" => $this->node_id))->save($data);
                if ($res === false) {
                    log_write("更新食材失败");
                    M()->rollback();
                    exit(
                        json_encode(
                            array(
                                'info' => '更新中奖食材失败！', 
                                'status' => 0)));
                }
                M()->commit();
                $data = array(
                    'lottery' => 2, 
                    'icon' => $food_index + 2);
                $this->responseJson(0, "恭喜您中的" . $food_arr[$food_index + 1], 
                    $data);
            }
        }
    }
    // 抽奖流水报错
    public function ajaxerror() {
        // 接收报错的字符窜
        $resp = I("resp");
        log_write("qianji前端返回" . $resp);
    }
}