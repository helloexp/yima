<?php

class ZongZiAction extends MyBaseAction {
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

    //由0下标开始的食材
    public $foods_arr0 = array();

    //由1下标开始的食材
    public $foods_arr1 = array();

    //活动名称
    public $activityName = '粽里寻Ta';

    const BATCH_TYPE_SPRING = 50;

    const DEFAULT_GAME_NUMBER = 3;
    // 默认次数
    const MAX_SCORE = 200;

    const MAX_SHARE_NUM = 1000;
    // 最多分享数
    const ZONGZI_TIME = 20150620;

    public function _initialize() {
        if (I('_sid_', '') == 'w') {
            $_SERVER['HTTP_USER_AGENT'] = 'MicroMessenger';
        }
        parent::_initialize();
        $this->_init_foods();

        // 判断是否是微信过来的
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') === false) {
            echo "请从微信访问";
            exit();
            $this->error('请使用微信扫码二维码进入活动');
        }
        $this->wap_sess_name = "zongzi_{$this->node_id}_wxid";
        if ($this->batch_type != self::BATCH_TYPE_SPRING)
            $this->error('错误访问！');
        if (ACTION_NAME == 'playGame' || ACTION_NAME == 'zongziCj' ||
             ACTION_NAME == 'foodlist' || ACTION_NAME == 'mycardlist' ||
             ACTION_NAME == 'share' || ACTION_NAME == 'send_food' ||
             ACTION_NAME == 'zengs' || ACTION_NAME == 'conversionjp') {
            $this->_zongzi_checklogin(false);
        }
        $this->wxid = session($this->wap_sess_name);
        // 活动信息
        $marketInfo = $this->marketInfo;
        // 分享信息
        $node_id = $this->node_id;
        $date = array(
            'node_id' => $node_id, 
            'batch_type' => 50);
        $openid = M("twx_wap_user")->where(
            array(
                'id' => $this->wxid))->getField('openid');
        $nodeInfo = $this->marketInfo;
        $nodeName = $this->marketInfo['node_name'];
        $wap_title = $nodeInfo['wap_title'];
        if($this->node_id==C('cnpc_gx.node_id')){
            $zongWeiXinInfo=M("tweixin_info")->where(array('node_id'=>$this->node_id))->find();
            if($zongWeiXinInfo){
                $wx_share_config= D('WeiXin', 'Service')->getWxShareConfig('', $zongWeiXinInfo['app_id'], $zongWeiXinInfo['app_secret']);
            }
        }else{
            $wx_share_config = D('WeiXin', 'Service')->getWxShareConfig();
        }
        $shareUrl = U('index', array(
            'id' => $this->id), '', '', TRUE);
        // URL中，通过MD5进行加密
        $url_final = md5($openid . time());
        $shareUrl_send = U('send_food', 
            array(
                'id' => $this->id), '', '', TRUE);
        $shareArr = array(
            'config' => $wx_share_config,
            'link' => $shareUrl,
            'title' => "粽礼寻Ta",
            'desc' => "芳粽飘香季，" . $nodeName . "为您寻找精美粽礼！",
            'imgUrl' => C('CURRENT_DOMAIN') .
                 'Home/Public/Label/Image/duanwu/Item/duanwu.jpg');
        if($this->node_id==C('cnpc_gx.node_id')){
            $shareArr['title']="摇一摇，财神到！";
            $shareArr['desc']="财神到，为您送上精美礼品！";
            $shareArr['imgUrl']=C('CURRENT_DOMAIN') . 'Home/Public/Label/Image/chunjie/Item/banner.jpg';
        }
        if($this->node_id == C('csbank.node_id')){
            $shareArr['title']= '粽礼寻卡';
            $this->activityName = '粽礼寻卡';
            $shareArr['imgUrl'] = C('CURRENT_DOMAIN') . 'Home/Public/Label/Image/duanwu/Item/csyh/duanwu.jpg';
        }
        $this->assign('node_service_hotline', get_node_info($this->node_id, 'node_service_hotline'));
        $this->assign('shareUrl_send', $shareUrl_send);
        $this->assign('url_final', $url_final);
        $this->assign('wx_share_config', $wx_share_config);
        $this->assign('nodeName', $nodeName);
        $this->assign('wap_title', $wap_title);
        $this->assign('marketInfo', $marketInfo);
        $this->assign('shareData', $shareArr);
        $this->assign('id', $this->id);
        $this->assign('wxid', $this->wxid);
        $this->assign('activityName', $this->activityName);
        $this->assign('isCsbank', $this->node_id == C('csbank.node_id'));
    }
    
    // 首页
    public function index() {
        if ($this->batch_type != self::BATCH_TYPE_SPRING)
            $this->error('错误访问！');
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
            $used_times = intval(M('twx_zongzi_trace')->where($map)->count());
            $this->assign('remain_times', 3 - $used_times);
        }
        // 访问量
        import('@.Vendor.DataStat');
        $opt = new DataStat($this->id, $this->full_id);
        $opt->UpdateRecord();
        // 告诉前端游戏参与方式
        $this->assign('join_mode', $this->marketInfo['join_mode']);
        $this->assign('info_duanwu', $this->marketInfo['wap_info']);
        $this->assign('title_duanwu', $this->marketInfo['wap_title']);
        $this->assign('id', $this->id);
        $this->assign('batch_type', $this->batch_type);
        $this->assign('batch_id', $this->batch_id);
        $this->assign('list', $list);
        $this->assign('food_arr', $this->foods_arr0);
        $this->display('index'); // 输出模板
    }
    /**
     * 根据营销活动参数判断session，获取是否登录
     *
     * @param bool $return
     */
    public function _zongzi_checklogin($return = true) {
        if (session('?' . $this->wap_sess_name))
            return session('?' . $this->wap_sess_name);
        $login = false;
        $userid = '';
        $backurl = U('', I('get.'), '', '', true);
        $backurl = urlencode($backurl);
        // 端午节活动全部走微信授权,取全局的微信服务号
        if($this->node_id==C('cnpc_gx.node_id')){
            $login = session('?node_wxid_' . $this->node_id);
            $jumpurl = U(
                    'Label/WeixinLoginNode/wechatAuthorizeAndRedirectByDefault',
                    array(
                            'id' => $this->id,
                            'type' => 1,
                            'backurl' => $backurl));
            if ($login)
                $info = session('node_wxid_' . $this->node_id);
        }else{
            $login = session('?node_wxid_global');
            $jumpurl = U('Label/WeixinLoginGlobal/index',
                    array(
                            'id' => $this->id,
                            'type' => 1,
                            'backurl' => $backurl));
            if ($login)
                $info = session('node_wxid_global');
        }
        if (! $login) {
            redirect($jumpurl);
        }
        if ($login) {
            $openid = $info['openid'];
            if($this->node_id==C('cnpc_gx.node_id')){
                $this->_checkFollow($openid);
            }
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
    
    // 中奖逻辑
    public function zongzicj() {
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
//        $this->check_user_time();
        $this->foods();
    }
    
    // 中奖食材概率逻辑
    public function foods() {
        $data = array(
            'batch_type' => $this->batch_type, 
            'node_id' => $this->node_id);
        $weixin_info = session('node_wxid_global');
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
                7, 
                8, 
                2, 
                4, 
                5, 
                6, 
                1, 
                3), 
            array(
                1, 
                7, 
                3, 
                8, 
                5, 
                6, 
                2, 
                4), 
            array(
                1, 
                2, 
                7, 
                4, 
                8, 
                6, 
                3, 
                5));
        // 用户openid的第一个数
        $number = ord(substr($weixin_info['openid'], - 1)) % 4;
        $base = $config[$number];
        $global_rate = M("tmarketing_info")->where($data)->getField(
            'defined_one_name');
        $num = mt_rand(1, 1000) <= $global_rate ? 6 : 5;
        $arr = array();
        for ($i = 0; $i <= $num; $i ++) {
            $arr = array_pad($arr, sizeof($arr) + 5, $base[$i]);
        }
        array_push($arr, $base[6], $base[7]);
        shuffle($arr);
        $out = $arr[mt_rand(0, sizeof($arr) - 1)];
        $this->add_foods($out - 1);
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
        $traceInfo = M("twx_zongzi_trace")->where($map)->count();
        if ($this->node_id == C('shiyoufb.node_id') || $this->node_id == C('csbank.node_id')) {
            if ($traceInfo >= 5) {
                exit(
                    json_encode(
                        array(
                            'info' => '您今天已经参与五次了', 
                            'status' => 0)));
            }
        } else {
            if ($traceInfo >= 3) {
                exit(
                    json_encode(
                        array(
                            'info' => '您今天已经参与三次了', 
                            'status' => 0)));
            }
        }
    }
    
    // 通过微信登录ID 查询用户中奖食材
    public function has_food() {
        // 获取用户的wx_user_id
        $wx_user_id = $this->wxid;
        $map = array(
            "wx_user_id" => $wx_user_id, 
            "node_id" => $this->node_id, 
            "batch_id" => $this->batch_id);
        
        $traceInfo = M("twx_zongzi_score")->where($map)->find();
        if ($traceInfo) {
            return $traceInfo;
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
        $res_trace = M("twx_zongzi_trace")->add($trace_food);
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
        // 获取用户是否有过食材wx_user_id
        $traceInfo = $this->has_food();
        // 如果有中食材记录
        if ($traceInfo) {
            // 取传输进来的食材
            $foods_number = json_decode($traceInfo["foods_number"], true);
            $foods_number[$food_index] = $foods_number[$food_index] + 1;
            $data = array(
                "foods_number" => json_encode($foods_number));
            $res = M("twx_zongzi_score")->where(
                array(
                    "wx_user_id" => $wx_user_id, 
                    "batch_id" => $this->batch_id, 
                    "node_id" => $this->node_id))->save($data);
            if ($res === false) {
                log_write("更新".L('FOOD_NAME')."失败");
                M()->rollback();
                exit(
                    json_encode(
                        array(
                            'info' => "更新中奖".L('FOOD_NAME')."失败！",
                            'status' => 0)));
            }
            M()->commit();
            if($this->node_id ==C('cnpc_gx.node_id')){
                $data = array(
                        'lottery' => 2,
                        'icon' => $food_index + 1);
            }else{
                $data = array(
                        'lottery' => 2,
                        'icon' => $food_index + 2);
            }
            // 返回中几个食材，查询用户有几种食材
            $traceInfo = $this->has_food();
            $fond_count = 0;
            if (! empty($traceInfo)) {
                $foods_number = json_decode($traceInfo["foods_number"], true);
                if ($foods_number) {
                    foreach ($foods_number as $key => $val) {
                        if ($val > 0) {
                            $fond_count ++;
                        }
                    }
                }
            }
            $food_end = 8 - $fond_count;
            if ($food_end == 0) {
                $msg_zongzi = "您已集齐" . $fond_count . "个".L('FOOD_NAME').",请点击立即兑换";
                $myfood = "立即兑换";
            } else {
                $msg_zongzi = "您已集齐" . $fond_count . "个".L('FOOD_NAME')."，还剩" . $food_end .
                     "个就能兑换好礼";
                $myfood = "查看我获得的".L('FOOD_NAME')."";
            }
            // zongzi_flag=1 前端领取标记
            $zongzi_arr = array(
                "food_info" => "恭喜您获得" . $this->foods_arr1[$food_index + 1],
                "data_info" => $msg_zongzi, 
                "myfood" => $myfood, 
                "zongzi_flag" => "1");
            $this->responseJson(0, $zongzi_arr, $data);
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
            
            $res = M("twx_zongzi_score")->add($data);
            if ($res === false) {
                M()->rollback();
                exit(
                    json_encode(
                        array(
                            'info' => "更新中奖".L('FOOD_NAME')."失败！",
                            'status' => 0)));
            }
            M()->commit();
            $data = array(
                'lottery' => 2, 
                'icon' => $food_index + 2);
            $msg_zongzi = "您已集齐1个".L('FOOD_NAME')."，还剩7个就能兑换好礼";
            $myfood = "查看我获得的".L('FOOD_NAME')."";
            $zongzi_arr = array(
                "food_info" => "恭喜您获得" . $this->foods_arr1[$food_index + 1],
                "data_info" => $msg_zongzi, 
                "myfood" => $myfood, 
                "zongzi_flag" => "1");
            $this->responseJson(0, $zongzi_arr, $data);
        }
    }
    
    // 集齐八个材料兑换礼品
    public function conversionjp() {
        $mobile = I("phone");
        $verify = I('yanzhengma', 0, 'trim');
        $verifySession = session('zongzi_groupCheckCode');
        // 同时要判断验证码的有效期，是否在设置的时间内
        if ($verify == "" || $mobile == "") {
            exit(
                json_encode(
                    array(
                        'info' => '手机或者验证码不能为空！', 
                        'status' => 0, 
                        'zongzi_flag' => 1)));
        }
        if (time() - $verifySession['add_time'] > $this->expiresTime) {
            exit(
                json_encode(
                    array(
                        'info' => '手机验证码已经过期！', 
                        'status' => 0, 
                        'zongzi_flag' => 1)));
        }
        if ($verify != '' && $verify != $verifySession['number']) {
            exit(
                json_encode(
                    array(
                        'info' => '请输入正确的验证码！', 
                        'status' => 0, 
                        'zongzi_flag' => 1)));
        }
        if ($mobile != '' && $mobile != $verifySession['phoneNo']) {
            exit(
                json_encode(
                    array(
                        'info' => '手机号码不正确！', 
                        'status' => 0, 
                        'zongzi_flag' => 1)));
        }
        // 判断活动还没有开始提示
        $overdue = $this->checkDate();
        if ($overdue === 1) {
            exit(
                json_encode(
                    array(
                        'info' => '活动还未开始，敬请关注！', 
                        'status' => 0, 
                        'zongzi_flag' => 1)));
        }
        
        // 获取用户的信息
        M()->startTrans();
        $wx_user_id = $this->wxid;
        $traceInfo = $this->has_food();
        
        $foods_number = json_decode($traceInfo["foods_number"], true);
        // //判断用户是否有8个材料
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
                        'info' => "您的".L('FOOD_NAME')."不够！",
                        'status' => 0, 
                        'zongzi_flag' => 1)));
        }
        foreach ($foods_number as $key => $val) {
            if ($val != 0) {
                $foods_number[$key] = $val - 1;
            }
        }
        $data = array(
            "foods_number" => json_encode($foods_number));
        $res = M("twx_zongzi_score")->where(
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
                        'status' => 0, 
                        'zongzi_flag' => 1)));
        }
        import('@.Vendor.ChouJiang');
        $choujiang = new ChouJiang($this->id, $mobile, $this->full_id);
        $resp = $choujiang->send_code();
        // 中奖提示
        if (isset($resp['resp_id']) && $resp['resp_id'] == '0000') {
            $cj_msg = "恭喜您中奖了！";
            // 奖品类别
            $cjCateId = M()->table('tcj_batch b')
                ->join('tcj_cate c ON b.cj_cate_id=c.id')
                ->join('tgoods_info d ON b.goods_id=d.goods_id')
                ->join('tbatch_info e on e.id=b.b_id')
                ->where("b.id={$resp['rule_id']}")
                ->field(
                'b.goods_id,d.goods_name,d.goods_image,d.goods_type,d.bonus_id,e.batch_amt as point')
                ->find();
            if (! $cjCateId) {
                log_write("端午节" . M()->_sql());
            }
            $respData = array(
                'data' => $cjCateId,
                'goods_name' => $cjCateId['goods_name'],
                'goods_image' => $cjCateId['goods_image'],
                'info' => $cj_msg,
                'status' => 1,
                'batch_class' => $resp['batch_class'],
                'type' => 0);
            M()->commit();
            //定额红包
            if ($cjCateId['goods_type'] == 12) {
                $bonus_use_detail_id = $resp['bonus_use_detail_id'];
                $info = M('tbonus_use_detail')->where(
                    array(
                        'id' => $bonus_use_detail_id))->find();
                if (! $info) {
                    exit(
                        json_encode(
                            array(
                                'info' => '兑换奖品失败！', 
                                'status' => 0, 
                                'zongzi_flag' => 1)));
                }
                
                $result = M('tbonus_use_detail')->where(
                    array(
                        'id' => $bonus_use_detail_id))->save(
                    array(
                        'phone' => $mobile));
                if ($result === false)
                    exit(
                        json_encode(
                            array(
                                'info' => '兑换奖品失败！', 
                                'status' => 0, 
                                'zongzi_flag' => 1)));
                else {
                    $respData['type'] = 1;
                    $respData['link_flag'] = 0;
                }
                $bonusInfo = M('tbonus_info')->where(
                    array(
                        'id' => $cjCateId['bonus_id']))->find();
                if ($bonusInfo['link_flag'] == '1') {
                    $respData['link_flag'] = 1;
                    $respData['link_url'] = $bonusInfo['link_url'];
                    $respData['button_name'] = $bonusInfo['button_name'];
                }
            }
            // 积分奖品
            if ($resp['prize_type'] == '4') {
                $respData['type'] = 4;
                $respData['point'] = (int) $cjCateId['point'];
            }
            exit(
                json_encode(
                    array(
                        'info' => $respData, 
                        'status' => 1)));
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
            // 兑奖失败，将用户食材返回
            // $this->_wx_food_add();
            if (in_array($resp['resp_id'], $fail_arr))
                $resp_msg = "您来晚了，该奖品已经被抢光了";
            else
                $resp_msg = "很遗憾领取失败";
            exit(
                json_encode(
                    array(
                        'info' => $resp_msg, 
                        'status' => 0, 
                        'zongzi_flag' => 1)));
        }
    }
    
    // 游戏
    public function playGame() {
        $this->assign('id', $this->id);
            $this->display("playGame"); // 输出模板
    }
    
    // 我的我的食材
    public function foodlist() {
        // 分享我的食材，URL里面带的wx_user_id
        $marketInfo = $this->marketInfo;
        // 分享朋友圈链接
        $traceInfo = $this->has_food();
        $fond_count = 0;
        if (! empty($traceInfo)) {
            $foods_number = json_decode($traceInfo["foods_number"], true);
            if ($foods_number) {
                foreach ($foods_number as $key => $val) {
                    if ($val > 0) {
                        $fond_count ++;
                    }
                }
            }
        }
        // 判断用户是否已经领取过材料
        $map2 = array(
            "wx_user_id" => $this->wxid, 
            "node_id" => $this->node_id, 
            'param1' => 1, 
            'batch_id' => $this->batch_id);
        $res1 = M("twx_zongzi_trace")->where($map2)->find();
        if ($res1) {
            $this->assign("has_flag", 1);
        }
        // 判断活动是否过期
        $nowtime = date('YmdHis', time());
        if ($marketInfo['start_time'] >= $nowtime) {
            // 活动还没有开始
            $subzongzi = 1;
        } elseif ($marketInfo['end_time'] <= $nowtime) {
            // 活动过期
            $subzongzi = 2;
        } elseif ($marketInfo['status'] == 2) {
            $subzongzi = 3;
        }
        $this->assign('subzongzi', $subzongzi);
        $this->assign('marketInfo', $marketInfo);
        $this->assign('foods_count', $fond_count);
        $this->assign('foods_count1', 8 - $fond_count);
        $this->assign('foods_number', $foods_number);
        $this->assign('foods_arr', array_values($this->foods_arr0));
        $this->display("foodlist");
    }
    // 查询用户所中奖奖品
    public function select_jp() {
        // 查询奖项
        $list = M()->table("tbatch_info a")->field('b.goods_image,a.batch_short_name')
            ->join("tgoods_info b ON b.goods_id=a.goods_id")
            ->join("tcj_batch c ON c.b_id=a.id")
            ->where(
            array(
                "a.node_id" => $this->node_id, 
                "a.m_id" => $this->batch_id, 
                "a.status" => 0, 
                "c.status" => 1))
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
        $traceInfo = M("twx_zongzi_score")->where($map)->find();
        // 获取中奖个数
        $fond_count = 0;
        if (! empty($traceInfo)) {
            $foods_number = json_decode($traceInfo["foods_number"], true);
            if ($foods_number) {
                foreach ($foods_number as $key => $val) {
                    if ($val > 0) {
                        $fond_count ++;
                    }
                }
            }
        }
        $map1 = array(
            'a.wx_user_id' => $wx_user_id, 
            'a.type' => 1, 
            "a.batch_id" => $this->batch_id, 
            "a.node_id" => $this->node_id);
        $list_param1 = M()->table("twx_zongzi_trace a")->field(
            'a.jp_id,d.batch_short_name,e.goods_image')
            ->join("tcj_batch c on c.cj_cate_id=a.jp_id")
            ->join("tbatch_info d on d.id=c.b_id")
            ->join("tgoods_info e on e.goods_id=d.goods_id")
            ->order("a.add_time desc")
            ->where($map1)
            ->select();
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
    
    // 赠送
    public function zengs() {
        // 减少赠送者对应的食材
        $fid = I('food_id');
        $openid_md5 = I("url_final");
        $foods_number = $this->has_food();
        $foods_number = json_decode($foods_number["foods_number"], true);
        // 如果食材为0，不让扣减
        $openid = M("twx_wap_user")->where(
            array(
                'id' => $this->wxid))->getField('openid');
        $new_openid_md5 = md5($openid . time());
        if ($foods_number[$fid - 1] == 0) {
            exit(
                json_encode(
                    array(
                        'info' => "分享成功,".L('FOOD_NAME')."扣减失败!",
                        'status' => 1, 
                        "new_openid_md5" => $new_openid_md5)));
        }
        $foods_number[$fid - 1] --;
        $foods_number = json_encode($foods_number);
        $data1 = array(
            'foods_number' => $foods_number);
        M()->startTrans();
        $res = M("twx_zongzi_score")->where(
            array(
                'wx_user_id' => $this->wxid, 
                "batch_id" => $this->batch_id, 
                "node_id" => $this->node_id))->save($data1);
        if (! $res) {
            M()->rollback();
            exit(
                json_encode(
                    array(
                        'info' => L('FOOD_NAME')."扣减失败!",
                        'status' => 0)));
        }
        // 将赠送存入流水
        $trace = array(
            "wx_user_id" => $this->wxid, 
            "status" => 1, 
            "node_id" => $this->node_id, 
            "batch_id" => $this->batch_id, 
            "add_time" => date('YmdHis', time()), 
            "food_num" => $fid, 
            "openid_md5" => $openid_md5);
        $res_trace = M("twx_zongzi_zstrace")->add($trace);
        if ($res_trace === false) {
            M()->rollback();
            exit(
                json_encode(
                    array(
                        'info' => "流水存入失败!", 
                        'status' => 0)));
        }
        M()->commit();
        exit(
            json_encode(
                array(
                    'info' => "分享成功!", 
                    'status' => 1, 
                    "new_openid_md5" => $new_openid_md5)));
    }
    
    // 领取赠送食材
    public function send_food() {
        $aid = I('food_id');
        $fid = $aid - 1;
        $url_final = I('url_final');
        if (empty($url_final)) {
            $this->error("参数错误！");
        }
        // 分享页面食材
        if($this->node_id==C('cnpc_gx.node_id')){
            $zongWeiXinInfo=M("tweixin_info")->where(array('node_id'=>$this->node_id))->find();
            $wx_share_config= D('WeiXin', 'Service')->getWxShareConfig('', $zongWeiXinInfo['app_id'], $zongWeiXinInfo['app_secret']);
        }else{
            $wx_share_config = D('WeiXin', 'Service')->getWxShareConfig();
        }
        // URL中，通过MD5进行加密
        $shareUrl_send = U('send_food', 
            array(
                'id' => $this->id, 
                'url_final' => $url_final), '', '', TRUE);
        $shareArr = array(
            'config' => $wx_share_config, 
            'link' => $shareUrl_send, 
            'title' => $this->activityName,
            'desc' => "芳粽飘香季，" . $this->node_name . "为您寻找精美粽礼！", 
            'imgUrl' => C('CURRENT_DOMAIN') .
                 'Home/Public/Label/Image/duanwu/Item/duanwu.jpg');
        if($this->node_id==C('cnpc_gx.node_id')){
            $shareArr['title']="摇一摇，财神到！";
            $shareArr['desc']="财神到，为您送上精美礼品！";
            $shareArr['imgUrl']=C('CURRENT_DOMAIN') . 'Home/Public/Label/Image/chunjie/Item/banner.jpg';
        }
        $this->assign('shareData', $shareArr);
        $nowtime = date('YmdHis', time());
        $marketInfo = $this->marketInfo;
        if ($marketInfo['start_time'] >= $nowtime ||
             $marketInfo['end_time'] <= $nowtime || $marketInfo['status'] == 2) {
            // 活动不正常
            $zswxid = M("twx_zongzi_zstrace")->where(
                "openid_md5 = '$url_final'")->find();
            $wx_image = M("twx_wap_user")->where(
                array(
                    "id" => $zswxid['wx_user_id']))->find();
            // 第几个食材
            $this->assign('food_name', $this->foods_arr1[$zswxid['food_num']]);
            $this->assign('aid', $zswxid['food_num']);
            $this->assign("wx_image ", $wx_image);
            $this->assign('nickname', $wx_image['nickname']);
            $this->assign('headimgurl', $wx_image['headimgurl']);
            $this->display('send_food');
        } else {
            // 查询食材是否被领取了
            $food_info = M('twx_zongzi_zstrace')->where(
                "openid_md5 = '$url_final'")->find();
            if ($food_info) {
                if ($food_info['status'] == 2) {
                    if ($food_info['wx_user_id'] == $this->wxid) {
                        $msg = " 不可以领自己赠送出的".L('FOOD_NAME')."哦~";
                        $this->assign('success_flag', 3);
                    } else {
                        if ($food_info['wx_zengs_id'] == $this->wxid) {
                            $msg = "表闹，已经领过啦！~";
                            $this->assign('success_flag', 2);
                        } else {
                            $msg = "呀！来晚一步，被抢光啦！";
                            $this->assign('success_flag', 4);
                        }
                    }
                } else {
                    // 食材没有被领取
                    // 自己不能领取领取的
                    if ($food_info['wx_user_id'] == $this->wxid) {
                        $msg = "不可以领自己赠送出的".L('FOOD_NAME')."哦~";
                        $this->assign('success_flag', 3);
                    } else {
                        // 领取食材(判断是否玩过游戏)
                        $has_play = M("twx_zongzi_score")->where(
                            array(
                                "node_id" => $this->node_id, 
                                "batch_id" => $this->batch_id, 
                                "wx_user_id" => $this->wxid))->find();
                        if ($has_play) {
                            $player_info = json_decode(
                                $has_play['foods_number'], true);
                            $player_info[$food_info['food_num'] - 1] ++;
                            // 存入数据库
                            M()->startTrans();
                            $res = M("twx_zongzi_score")->where(
                                array(
                                    "node_id" => $this->node_id, 
                                    "batch_id" => $this->batch_id, 
                                    "wx_user_id" => $this->wxid))->save(
                                array(
                                    'foods_number' => json_encode($player_info)));
                            if ($res === false) {
                                M()->rollback();
                                $msg = "领取".L('FOOD_NAME')."失败";
                                $this->assign('success_flag', 5);
                            }
                            if ($res) {
                                $res1 = M("twx_zongzi_zstrace")->where(
                                    array(
                                        "node_id" => $this->node_id, 
                                        "batch_id" => $this->batch_id, 
                                        "openid_md5" => $food_info['openid_md5']))->save(
                                    array(
                                        'status' => 2, 
                                        "wx_zengs_id" => $this->wxid));
                                if ($res1 === false) {
                                    M()->rollback();
                                    $msg = "领取".L('FOOD_NAME')."失败";
                                    $this->assign('success_flag', 5);
                                }
                                M()->commit();
                                $msg = $this->activityName;
                                $this->assign('success_flag', 1);
                            }
                        } else {
                            // 没有玩过游戏
                            $arr = array(
                                0, 
                                0, 
                                0, 
                                0, 
                                0, 
                                0, 
                                0, 
                                0);
                            $arr[$fid] = 1;
                            $wx_user_id = $this->wxid;
                            $data = array(
                                "wx_user_id" => $wx_user_id, 
                                "foods_number" => json_encode($arr), 
                                "node_id" => $this->node_id, 
                                "batch_id" => $this->batch_id);
                            $res = M("twx_zongzi_score")->add($data);
                            if ($res === false) {
                                M()->rollback();
                                $msg = "领取".L('FOOD_NAME')."失败";
                                $this->assign('success_flag', 5);
                            }
                            if ($res) {
                                $res1 = M("twx_zongzi_zstrace")->where(
                                    array(
                                        "node_id" => $this->node_id, 
                                        "batch_id" => $this->batch_id, 
                                        "openid_md5" => $food_info['openid_md5']))->save(
                                    array(
                                        'status' => 2, 
                                        "wx_zengs_id" => $this->wxid));
                                if ($res1 === false) {
                                    M()->rollback();
                                    $msg = "领取".L('FOOD_NAME')."失败";
                                    $this->assign('success_flag', 5);
                                }
                                M()->commit();
                                $msg = $this->activityName;
                                $this->assign('success_flag', 1);
                            }
                        }
                    }
                }
            }
        }
        // 活动不正常
        $zswxid = M("twx_zongzi_zstrace")->where("openid_md5 = '$url_final'")->find();
        $wx_image = M("twx_wap_user")->where(
            array(
                "id" => $zswxid['wx_user_id']))->find();
        // 第几个食材
        $this->assign('food_name', $this->foods_arr1[$zswxid['food_num']]);
        $this->assign('aid', $zswxid['food_num']);
        $this->assign("wx_image ", $wx_image);
        $this->assign('msg', $msg);
        $this->assign('nickname', $wx_image['nickname']);
        $this->assign('headimgurl', $wx_image['headimgurl']);
        $this->assign('food_arr', array_values($this->foods_arr0));
        $this->display('send_food');
    }

    public function sendCheckCode() {
        $phoneNo = I('phone', null);
        if (! check_str($phoneNo, 
            array(
                'null' => false, 
                'strtype' => 'mobile'), $error)) {
            $this->error("手机号{$error}");
        }
        
        if (! is_production()) {
            $zongzi_groupCheckCode = array(
                'number' => 1111, 
                'add_time' => time(), 
                'phoneNo' => $phoneNo);
            session('zongzi_groupCheckCode', $zongzi_groupCheckCode);
            exit(
                json_encode(
                    array(
                        'info' => '发送验证码成功！测试环境默认1111', 
                        'status' => 1)));
        }
        
        // 发送频率验证
        $zongzi_groupCheckCode = session('zongzi_groupCheckCode');
        if (! empty($zongzi_groupCheckCode) &&
             (time() - $zongzi_groupCheckCode['add_time']) < $this->expiresTime) {
            $this->error('动态密码发送过于频繁!');
        }
        $num = mt_rand(1000, 9999);
        
        $exptime = $this->expiresTime / 60;
        $text = "您的动态密码是：{$num}，有效期{$exptime}分钟。如非本人操作请忽略。";
        // 通知支撑
        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
                                                                   // 请求参数
        $req_array = array(
            'NotifyReq' => array(
                'TransactionID' => $TransactionID, 
                'ISSPID' => C('MOBILE_ISSPID'), 
                'SystemID' => C('ISS_SYSTEM_ID'), 
                'SendLevel' => '1', 
                'Recipients' => array(
                    'Number' => $phoneNo),  // 手机号
                'SendClass' => 'SMS', 
                'MessageText' => $text,  // 短信内容
                'Subject' => '', 
                'ActivityID' => C('ALIPAY_NOTICE_ACTIVITYID'), 
                'ChannelID' => '', 
                'ExtentCode' => ''));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $resp_array = $RemoteRequest->requestIssServ($req_array);
        $ret_msg = $resp_array['NotifyRes']['Status'];
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
             $ret_msg['StatusCode'] != '0001')) {
            $this->error('发送失败' . print_r($resp_array, true) . '0');
        }
        $zongzi_groupCheckCode = array(
            'number' => $num, 
            'add_time' => time(), 
            'phoneNo' => $phoneNo);
        session('zongzi_groupCheckCode', $zongzi_groupCheckCode);
        exit(
            json_encode(
                array(
                    'info' => '发送验证码成功！', 
                    'status' => 1)));
    }

    public function _wx_food_add() {
        $food_info = M("twx_zongzi_score")->where(
            array(
                'node_id' => $this->node_id, 
                "batch_id" => $this->batch_id, 
                "wx_user_id" => $this->wxid))->find();
        $foods_number = json_decode($food_info["foods_number"], true);
        foreach ($foods_number as $key => $val) {
            $foods_number[$key] = $val + 1;
        }
        $res = M("twx_zongzi_score")->where(
            array(
                'node_id' => $this->node_id, 
                "batch_id" => $this->batch_id, 
                "wx_user_id" => $this->wxid))->save(
            array(
                'foods_number' => json_encode($foods_number)));
        if ($res === false) {
            log_write("兑奖失败，用户".L('FOOD_NAME')."未加1");
        }
    }
    //display调用
    public function display($templateFile='') {
        if($templateFile=='index'){
            if ($this->node_id == C('zongzifb.node_id')) {
                $templateFile = "fbindex";
            }elseif($this->node_id ==C('cnpc_gx.node_id')){
                $templateFile = "gxindex";
            }
        }else if($templateFile=='playGame'){
            if ($this->node_id == C('zongzifb.node_id')) {
                $templateFile = 'fbplayGame';
            }elseif($this->node_id ==C('cnpc_gx.node_id')){
                $templateFile = 'gxplayGame';
            }
        }else if($templateFile=='foodlist'){
            if($this->node_id ==C('cnpc_gx.node_id')){
                $templateFile = 'gxfoodlist';
            }
        }else if($templateFile=='send_food'){
            if($this->node_id ==C('cnpc_gx.node_id')){
                $templateFile = 'gxsend_food';
            }
        }
        parent::display($templateFile);
    }
    /*
     * 广西石油校验是否关注公众号
     */
    public function _checkFollow($openid){
        $count = M('twx_user')->where(
                "openid = '{$openid}' and ifnull(subscribe, '0') != '0' and node_id=".$this->node_id)->count();
        if ($count <= 0) {
            if (IS_AJAX) {
                exit(
                json_encode(
                        array(
                                'info' => '请先关注公众号再参与游戏1！',
                                'status' => 1)));
            } else {
                $guideUrl = M('tweixin_info')->where("node_id='{$this->node_id}'")->getField(
                        'guide_url'); // 关注页链接
                if ($guideUrl != '') {
                    redirect($guideUrl);
                }
                $this->error('请先关注公众号再参与游戏2！');
            }
        }
    }

    public function _init_foods(){
        if($this->node_id==C('cnpc_gx.node_id')){
            L('FOOD_NAME', '福袋');
        }else{
            L('FOOD_NAME', '食材');
        }

        switch ($this->node_id){
            case C('cnpc_gx.node_id'):
                $this->foods_arr0 = array(
                    '0' => '忠--福袋',
                    '1' => '孝--福袋',
                    '2' => '仁--福袋',
                    '3' => '义--福袋',
                    '4' => '礼--福袋',
                    '5' => '智--福袋',
                    '6' => '信--福袋',
                    '7' => '爱--福袋'
                );
                break;
            case C('csbank.node_id'):
                $this->foods_arr0 = array(
                    '0' => '车位分期腐乳',
                    '1' => '车计划东坡肉',
                    '2' => '灵活分期草莓',
                    '3' => '转账支付卡蛋黄',
                    '4' => '心意通红豆',
                    '5' => '融意通排骨',
                    '6' => '芙蓉信用卡鲜肉',
                    '7' => '公务卡五谷'
                );
                break;
            default:
                $this->foods_arr0 = array(
                    '0' => '腐乳食材',
                    '1' => '东坡肉食材',
                    '2' => '草莓食材',
                    '3' => '蛋黄食材',
                    '4' => '红豆食材',
                    '5' => '酱肉排骨食材',
                    '6' => '鲜肉食材',
                    '7' => '五谷杂粮食材'
                );
        }

        foreach($this->foods_arr0 as $key=>$val){
            $this->foods_arr1[$key+1] = $val;
        }
    }
}