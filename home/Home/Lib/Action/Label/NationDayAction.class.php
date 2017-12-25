<?php

class NationDayAction extends MyBaseAction {
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

    public $nation_sess_name = '';

    const BATCH_TYPE_NATION = 56;

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
        // 判断是否是微信过来的
        if (!isFromWechat()) {
            echo "请从微信访问";
            exit();
        }

        $this->nation_sess_name = "nation_{$this->node_id}_wxid";
        $marketInfo = $this->marketInfo;
        $config_data = unserialize($marketInfo['config_data']);

        if ($this->batch_type != self::BATCH_TYPE_NATION)
            $this->error('错误访问！');
        if (I('_sid_', '') == 'w') {
            session($this->nation_sess_name, 230);
            session('node_wxid_global',
                    array(
                            'openid' => 'oyJjks1sNarV1q-ckEtOnTQKe-k8'));
        }
        if (ACTION_NAME == 'playGame' || ACTION_NAME == 'nationCj' ||
                ACTION_NAME == 'foodList' || ACTION_NAME == 'mycardList' ||
                ACTION_NAME == 'share' || ACTION_NAME == 'receiveFood' ||
                ACTION_NAME == 'zengs' || ACTION_NAME == 'conversionJp') {
            $this->_nation_checklogin(false);
        }

        $this->wxid = session($this->nation_sess_name);
        // 分享信息
        $openid = M("twx_wap_user")->where(
                array(
                        'id' => $this->wxid))->getField('openid');
        $this->openId = $openid;
        $nodeName = $this->marketInfo['node_name'];
        $wap_title = $this->marketInfo['wap_title'];
        $wx_share_config = D('WeiXin', 'Service')->getWxShareConfig();
        $shareUrl = U('index', array(
                'id' => $this->id), '', '', TRUE);
        // URL中，通过MD5进行加密
        $url_final = md5($openid . time());
        $shareUrl_send = U('receiveFood',
                array(
                        'id' => $this->id), '', '', TRUE);

        $shareArr = array(
                'config' => $wx_share_config,
                'link' => $shareUrl,
                'title' => "我是升旗手",
                'desc' => "国庆来升旗，" . $nodeName . "送您精美好礼。",
                'imgUrl' => C('CURRENT_DOMAIN') .
                        'Home/Public/Label/Image/20151001/icon.png');
        if ($config_data['share_descript']) {
            $shareArr['desc'] = str_replace(
                    array(
                            "\r\n",
                            "\n"), "", $config_data['share_descript']);
        }
        if ($marketInfo['share_pic']) {
            $shareArr['imgUrl'] = $marketInfo['share_pic'];
        }
        $phone_total_count = M("tcj_rule")->where(
                array(
                        'batch_id' => $this->batch_id))->getField('phone_total_count');
        $this->assign('phone_total_count', $phone_total_count);
        $this->assign('shareUrl_send', $shareUrl_send);
        $this->assign('url_final', $url_final);
        $this->assign('wx_share_config', $wx_share_config);
        $this->assign('nodeName', $nodeName);
        $this->assign('wap_title', $wap_title);
        $this->assign('marketInfo', $marketInfo);
        $this->assign('shareData', $shareArr);
        $this->assign('id', $this->id);
        $this->assign('wxid', $this->wxid);
    }

    // 首页
    public function index() {
        if ($this->batch_type != self::BATCH_TYPE_NATION)
            $this->error('错误访问！');
        // 查询用户中奖信息
        $list = $this->select_jp();
        $list1 = $this->jpArr();
        $node_id = $this->node_id;

        // 访问量
        import('@.Vendor.DataStat');
        $opt = new DataStat($this->id, $this->full_id);
        $opt->UpdateRecord();

        // 告诉前端游戏参与方式
        $this->assign('join_mode', $this->marketInfo['join_mode']);
        $this->assign('nodeName', $this->marketInfo['node_name']);
        $this->assign('id', $this->id);
        $this->assign('batch_type', $this->batch_type);
        $this->assign('batch_id', $this->batch_id);
        $this->assign('list', $list);
        $this->assign('list1', $list1);
        $this->assign("footerUrl",
                "http://cp.wangcaio2o.com/wapzq.html?id=88765");
        if ($this->node_id == C('changsha_bank.node_id')) {
            $this->display("fbindex");
        } else {
            $this->display(); // 输出模板
        }
    }

    /**
     * 根据营销活动参数判断session，获取是否登录
     *
     * @param bool $return
     */
    public function _nation_checklogin($return = true) {
        if (session('?' . $this->nation_sess_name)) {
            return session('?' . $this->nation_sess_name);
        }
        $join_mode = $this->marketInfo['join_mode'];
        $login = false;
        $userid = '';
        $backurl = U('', I('get.'), '', '', true);
        $backurl = urlencode($backurl);
        $marketInfo = $this->marketInfo;
        $config_data = unserialize($marketInfo['config_data']);
        if ($join_mode == '1' ) {
            if (!isset($config_data['wx_auth_type']) || $config_data['wx_auth_type'] == '1') {
                $login = session('?node_wxid_' . $this->node_id);
                $jumpurl = U(
                        'Label/MyBase/wechatAuthorizeAndRedirectByDefault',
                        array(
                                'id' => $this->id,
                                'type' => 1,
                                'backurl' => $backurl));
                if ($login){
                    $info = session('node_wxid_' . $this->node_id);
                }
            }else{
                $login = session('?node_wxid_global');
                $jumpurl = U(
                        'Label/WeixinLoginGlobal/index',
                        [
                                'id'      => $this->id,
                                'type'    => 1,
                                'backurl' => $backurl
                        ]
                );
                if ($login){
                    $info = session('node_wxid_global');
                }
            }
        }

        if (! $login && ! $return && $jumpurl) {
            redirect($jumpurl);
        }
        if ($login) {
            $openid = $info['openid'];
            $this->openId = $openid;
            // 判断是否是粉丝
            if ($join_mode == '1' && $config_data['wx_auth_type'] == '1' &&
                    $marketInfo['member_batch_id'] != '-1') {
                $count = M('twx_user')->where(
                        "openid = '{$openid}' and ifnull(subscribe, '0') != '0' and node_id=" .
                        $this->node_id)->count();
                if ($count <= 0) {
                    if (IS_AJAX) {
                        exit(
                        json_encode(
                                array(
                                        'info' => '请先关注公众号再参与游戏！',
                                        'status' => 0)));
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
            if (! $user) {
                $data = $map;
                $data['add_time'] = date('YmdHis');
                $data['label_id'] = $this->id;
                $data['node_id'] = $this->node_id;
                $data['nickname'] = $info['nickname'];
                $data['access_token'] = $info['access_token'];
                $data['sex'] = $info['sex'];
                $data['province'] = $info['province'];
                $data['city'] = $info['city'];
                $data['headimgurl'] = $info['headimgurl'];
                $userid = M('twx_wap_user')->add($data);
                if ($userid === false) {
                    exit(
                    json_encode(
                            array(
                                    'info' => '手机登录失败！',
                                    'status' => 1)));
                }
                $user = M('twx_wap_user')->where($map)->find();
            }
            $userid = $user['id'];
        }
        session($this->nation_sess_name, $userid);
        return $login;
    }

    // 中奖逻辑
    public function NationDayCj() {
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
        $this->check_user_time();
        if (I('type') == 2) {
            // type=2为超时为不中奖
            $data = array(
                    'wx_user_id' => $this->wxid,
                    'type' => 4,
                    'node_id' => $this->node_id,
                    'batch_id' => $this->batch_id,
                    "add_time" => date('YmdHis', time()));
            $res = M("twx_national_trace")->add($data);
            log_write('lin260:' . M()->getLastSql());
            if ($res === false) {
                exit(
                json_encode(
                        array(
                                'info' => '存入流水失败！',
                                'status' => 0)));
            }
            exit(
            json_encode(
                    array(
                            'info' => '游戏时间已到，您未获取任何材料！',
                            'status' => 3)));
        }
        // 百分之15的概率任何材料中不了
        $this->noFoods();
        // 95%的概率中奖
        $prizeArr = $this->prizeArr();
        $res_allow = M("twx_national_score")->where(
                array(
                        'wx_user_id' => $this->wxid,
                        "batch_id" => $this->batch_id,
                        'node_id' => $this->node_id))->find();
        if (empty($res_allow)) {
            // 第一次用户参数
            $this->firstScoreTrace($prizeArr[mt_rand(1, 1000)]);
        } else {
            // 如果为老用户,并且用户解绑
            if ($res_allow['jp_score'] == 1 && $res_allow['first_material'] == 3) {
                // 为用户重新随机一个数
                $this->scoreTraceAgin($prizeArr[mt_rand(1, 1000)]);
            }
            // 不为空，按照第一次升旗数据进行随机给用户
            $this->scoreTrace();
        }
    }

    public function scoreTraceAgin($score) {
        $data = array(
                'wx_user_id' => $this->wxid,
                'node_id' => $this->node_id,
                'batch_id' => $this->batch_id,
                'jp_id' => $score,
                'type' => 0,
                "add_time" => date('YmdHis', time()));
        M()->startTrans();
        // $score为空
        if (empty($score)) {
            $data['type'] = 4;
            $res = M("twx_national_trace")->add($data);
            if ($res === false) {
                M()->rollback();
                log_write('line312分数为空，添加twx_national_trace失败' .M()->getLastSql());
                $this->error("升旗失败！");
            }
            M()->commit();
            exit(
            json_encode(
                    array(
                            'info' => '未获取到材料！',
                            'status' => 3)));
        }
        if ($score == 0) {
            // 三等奖算用户剩余的材料
            $arr = array(
                    1,
                    0,
                    1);
        } elseif ($score == 1) {
            $arr = array(
                    1,
                    0,
                    0);
            // 三等奖
        } elseif ($score == 5) {
            // 二等奖
            $arr = array(
                    1,
                    1,
                    1,
                    0,
                    0);
        } elseif ($score == 2) {
            // 一等奖
            $arr = array(
                    0,
                    0,
                    0,
                    1,
                    1,
                    1,
                    5);
        }
        // 查询用户的材料
        $map = array(
                'wx_user_id' => $this->wxid,
                "batch_id" => $this->batch_id,
                'node_id' => $this->node_id,
                'jp_score' => 1);
        $res_arr = M("twx_national_score")->where($map)->find();
        $foods_number = json_decode($res_arr["foods_number"], true);
        if ($score == 5) {
            $foods_number[3] = $foods_number[3] + 1;
        } else {
            $foods_number[$score] = $foods_number[$score] + 1;
        }
        $data1 = array(
                'first_material' => $score,
                'foods_number' => json_encode($foods_number),
                'jp_arr' => json_encode($arr),
                'jp_score' => 2,
                'first_material' => $score,
                "add_time" => date('YmdHis', time()));
        $map1 = array(
                'wx_user_id' => $this->wxid,
                "batch_id" => $this->batch_id,
                'node_id' => $this->node_id,
                'jp_score' => 1);
        $res_nation = M("twx_national_score")->where($map1)->save($data1);
        if ($res_nation === false) {
            M()->rollback();
            log_write('保存分数失败' . M()->getLastSql());
            exit(
            json_encode(
                    array(
                            'info' => '升旗失败！',
                            'status' => 0)));
        }

        $data_arr = array(
                'wx_user_id' => $this->wxid,
                'node_id' => $this->node_id,
                'batch_id' => $this->batch_id,
                'jp_id' => $score,
                'type' => 0,
                "add_time" => date('YmdHis', time()));
        $res = M("twx_national_trace")->add($data_arr);
        if ($res === false) {
            M()->rollback();
            log_write('添加twx_national_trace失败398行' . M()->getLastSql());
            exit(
            json_encode(
                    array(
                            'info' => '升旗失败！',
                            'status' => 0)));
        }
        M()->commit();
        exit(
        json_encode(
                array(
                        'info' => $score,
                        'status' => 1)));
    }

    // 百分之5的概率任何材料中不了
    public function noFoods() {
        if (mt_rand(1, 100) > 95) {
            $data = array(
                    'wx_user_id' => $this->wxid,
                    'type' => 4,
                    'node_id' => $this->node_id,
                    'batch_id' => $this->batch_id,
                    "add_time" => date('YmdHis', time()));
            $res = M("twx_national_trace")->add($data);
            if ($res === false) {
                $this->error("升旗失败！");
            }
            exit(
            json_encode(
                    array(
                            'info' => '未获得材料！',
                            'status' => 3)));
        }
    }

    // 第一次生成用户奖品数组
    public function firstScoreTrace($score) {
        $data = array(
                'wx_user_id' => $this->wxid,
                'node_id' => $this->node_id,
                'batch_id' => $this->batch_id,
                'jp_id' => $score,
                'type' => 0,
                "add_time" => date('YmdHis', time()));
        M()->startTrans();
        // $score为空
        if (empty($score)) {
            $data['type'] = 4;
            $res = M("twx_national_trace")->add($data);
            if ($res === false) {
                M()->rollback();
                $this->error("升旗失败！");
            }
            M()->commit();
            exit(
            json_encode(
                    array(
                            'info' => '很遗憾您没有中到材料！',
                            'status' => 3)));
        }
        if ($score == 0) {
            // 三等奖算用户剩余的材料
            $arr = array(
                    1,
                    0,
                    1);
        } elseif ($score == 1) {
            $arr = array(
                    1,
                    0,
                    0);
            // 三等奖
        } elseif ($score == 5) {
            // 二等奖
            $arr = array(
                    1,
                    1,
                    1,
                    0,
                    0);
        } elseif ($score == 2) {
            // 一等奖
            $arr = array(
                    0,
                    0,
                    0,
                    1,
                    1,
                    1,
                    5);
        }
        $foods_number = array(
                0,
                0,
                0,
                0);
        if ($score == 5) {
            $foods_number[3] = 1;
        } else {
            $foods_number[$score] = 1;
        }
        $date = array(
                'wx_user_id' => $this->wxid,
                'node_id' => $this->node_id,
                'batch_id' => $this->batch_id,
                'first_material' => $score,
                'foods_number' => json_encode($foods_number),
                'jp_arr' => json_encode($arr),
                'jp_score' => 2,
                'first_material' => $score,
                "add_time" => date('YmdHis', time()));
        $res_nation = M("twx_national_score")->add($date);
        if ($res_nation === false) {
            M()->rollback();
            exit(
            json_encode(
                    array(
                            'info' => '升旗失败！',
                            'status' => 0)));
        }
        // 生成流水一条
        $res = M("twx_national_trace")->add($data);
        if ($res === false) {
            M()->rollback();
            exit(
            json_encode(
                    array(
                            'info' => '升旗失败！',
                            'status' => 0)));
        }
        M()->commit();
        exit(
        json_encode(
                array(
                        'info' => $score,
                        'status' => 1)));
    }

    // 用户有中奖标识数组
    public function scoreTrace() {
        $map = array(
                'wx_user_id' => $this->wxid,
                "batch_id" => $this->batch_id,
                'node_id' => $this->node_id,
                'jp_score' => 2);
        $res_arr = M("twx_national_score")->where($map)->find();
        $jp_arr = json_decode($res_arr["jp_arr"], true);
        $suiScore = rand(0, count($jp_arr) - 1);
        $score = $jp_arr[$suiScore];
        // 摇出这个数字更新到score表中
        $foods_number = json_decode($res_arr["foods_number"], true);
        if ($jp_arr[$suiScore] == 0) {
            $foods_number[0] ++;
        } else if ($jp_arr[$suiScore] == 1) {
            $foods_number[1] ++;
        } else if ($jp_arr[$suiScore] == 2) {
            $foods_number[2] ++;
        } else if ($jp_arr[$suiScore] == 5) {
            $foods_number[3] ++;
        }
        unset($jp_arr[$suiScore]);
        // 加上新增的材料，如果用户中奖，则让用户第一次的材料置为3
        if ($res_arr['first_material'] == 0) {
            // 三等奖，判断材料够不够
            if ($foods_number[0] == 2 && $foods_number[1] == 2) {
                $jp_score = 1;
                $first_material = 3;
            }
        } elseif ($res_arr['first_material'] == 1) {
            // 三等奖，判断材料够不够
            if ($foods_number[0] == 2 && $foods_number[1] == 2) {
                $jp_score = 1;
                $first_material = 3;
            }
        } elseif ($res_arr['first_material'] == 2) {
            // 一等奖，判断材料够不够
            if ($foods_number[0] == 3 && $foods_number[1] == 3 &&
                    $foods_number[2] == 1 && $foods_number[3] == 1) {
                $jp_score = 1;
                $first_material = 3;
            }
        } elseif ($res_arr['first_material'] == 5) {
            // 二等奖，判断材料够不够
            if ($foods_number[0] == 2 && $foods_number[1] == 3 &&
                    $foods_number[3] == 1) {
                $jp_score = 1;
                $first_material = 3;
            }
        }
        $map1 = array(
                'wx_user_id' => $this->wxid,
                "batch_id" => $this->batch_id,
                'node_id' => $this->node_id);
        $date = array(
                'foods_number' => json_encode($foods_number),
                'jp_arr' => json_encode(array_values($jp_arr)));
        if (empty($jp_arr)) {
            $date['jp_score'] = 1;
            $date['first_material'] = 3;
        }
        if ($jp_score == 1 && $first_material == 3) {
            $date['jp_score'] = $jp_score;
            $date['first_material'] = $first_material;
        }
        M()->startTrans();
        $res = M("twx_national_score")->where($map1)->save($date);
        if ($res === false) {
            M()->rollback();
            exit(
            json_encode(
                    array(
                            'info' => "升旗失败",
                            'status' => 0)));
        }
        $data_arr = array(
                'wx_user_id' => $this->wxid,
                'node_id' => $this->node_id,
                'batch_id' => $this->batch_id,
                'jp_id' => $score,
                'type' => 0,
                "add_time" => date('YmdHis', time()));
        $res = M("twx_national_trace")->add($data_arr);
        if ($res === false) {
            M()->rollback();
            exit(
            json_encode(
                    array(
                            'info' => '升旗失败！',
                            'status' => 0)));
        }
        M()->commit();
        exit(
        json_encode(
                array(
                        'info' => $score,
                        'status' => 1)));
    }

    // 检测用户参与次数
    public function check_user_time() {
        // 通过openid来查询用户参与次数
        $wx_user_id = $this->wxid;
        $map = array(
                "wx_user_id" => $wx_user_id,
                "batch_id" => $this->batch_id,
                "node_id" => $this->node_id);
        $map['_string'] = "add_time like '" . date('Ymd') . "%' ";
        $traceInfo = M("twx_national_trace")->where($map)->count();
        if ($traceInfo >= 3) {
            exit(
            json_encode(
                    array(
                            'info' => '今日参与次数用完，请明天再来',
                            'status' => 0)));
        }
    }

    // 通过微信登录ID 查询用户中奖材料
    public function has_food() {
        // 获取用户的wx_user_id
        $wx_user_id = $this->wxid;
        $map = array(
                "wx_user_id" => $wx_user_id,
                "node_id" => $this->node_id,
                "batch_id" => $this->batch_id);
        $traceInfo = M("twx_national_score")->where($map)->find();
        if ($traceInfo) {
            return $traceInfo;
        }
    }

    // 游戏
    public function playGame() {
        $map = array(
                'node_id' => $this->node_id,
                'batch_id' => $this->batch_id,
                'wx_user_id' => $this->wxid);
        $map['_string'] = "add_time like '" . date('Ymd') . "%'";
        $gameTimes = 3 - intval(M('twx_national_trace')->where($map)->count());
        $this->assign('gameTimes', $gameTimes);
        $this->assign('id', $this->id);
        $this->display(); // 输出模板
    }

    // 我的我的材料
    public function foodList() {
        $traceInfo = $this->has_food();
        // 如果有中材料记录
        if ($traceInfo) {
            // 取传输进来的材料
            $foods_number = json_decode($traceInfo["foods_number"], true);
        }
        $wx_image = M("twx_wap_user")->where(
                array(
                        "id" => $this->wxid))->find();
        $this->assign("nickname", $wx_image['nickname']);
        $this->assign("headimgurl", $wx_image['headimgurl']);
        $list = $this->select_jp();
        $this->assign('list', $list);
        $this->assign('id', $this->id);
        $this->assign('foods_number', $foods_number);
        $this->display();
    }

    // 查询用户所中奖奖品
    public function select_jp() {
        // 查询奖项
        $list = M()->table("tbatch_info a")
                ->field(
                        'a.id,b.goods_image,b.goods_type,x.card_id,d.name,a.batch_short_name,d.name,d.id as tcj_cate_id')
                ->join("tgoods_info b ON b.goods_id=a.goods_id")
                ->join("twx_card_type x ON x.card_id=a.card_id")
                ->join("tcj_batch c ON c.b_id=a.id")
                ->join("tcj_cate d ON d.id=c.cj_cate_id")
                ->where(
                        array(
                                "a.node_id" => $this->node_id,
                                "a.m_id" => $this->batch_id,
                                "a.status" => 0,
                                "c.status" => 1))
                ->select();
        $list_arr = array();
        foreach ($list as $key => $val) {
            if ($val['name'] == "一等奖") {
                $list_arr[] = $val;
            }
        }
        foreach ($list as $key => $val) {
            if ($val['name'] == "二等奖") {
                $list_arr[] = $val;
            }
        }
        foreach ($list as $key => $val) {
            if ($val['name'] == "三等奖") {
                $list_arr[] = $val;
            }
        }
        return $list_arr;
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
        // 减少赠送者对应的材料
        $fid = I('food_id');
        $openid_md5 = I("url_final");
        $foods_number = $this->has_food();
        $foods_number = json_decode($foods_number["foods_number"], true);
        // 如果材料为0，不让扣减
        $openid = M("twx_wap_user")->where(
                array(
                        'id' => $this->wxid))->getField('openid');
        $new_openid_md5 = md5($openid . time());
        if ($foods_number[$fid] == 0) {
            exit(
            json_encode(
                    array(
                            'info' => "分享成功,材料扣减失败!",
                            'status' => 1,
                            "new_openid_md5" => $new_openid_md5)));
        }
        $foods_number[$fid] --;
        $foods_number = json_encode($foods_number);
        $data1 = array(
                'foods_number' => $foods_number);
        M()->startTrans();
        $res = M("twx_national_score")->where(
                array(
                        'wx_user_id' => $this->wxid,
                        "batch_id" => $this->batch_id,
                        "node_id" => $this->node_id))->save($data1);
        if (! $res) {
            M()->rollback();
            exit(
            json_encode(
                    array(
                            'info' => "材料扣减失败!",
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
        $res_trace = M("twx_national_zstrace")->add($trace);
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

    // 领取赠送材料
    public function receiveFood() {
        $aid = I('food_id');
        $fid = $aid;
        $url_final = I('url_final');
        $traceNation = M("twx_national_zstrace")->where(
                array(
                        'openid_md5' => $url_final,
                        'food_num' => $aid))->find();
        $wx_image = M("twx_wap_user")->where(
                array(
                        "id" => $traceNation['wx_user_id']))->find();
        $this->assign("nickname", $wx_image['nickname']);
        $this->assign("headimgurl", $wx_image['headimgurl']);
        if (empty($url_final)) {
            $this->error("参数错误！");
        }
        $nowtime = date('YmdHis', time());
        $marketInfo = $this->marketInfo;
        if ($marketInfo['start_time'] >= $nowtime ||
                $marketInfo['end_time'] <= $nowtime || $marketInfo['status'] == 2 ||
                empty($traceNation)) {
            $this->error();
        } else {
            // 查询材料是否被领取了
            $food_info = M('twx_national_zstrace')->where(
                    "openid_md5 = '$url_final'")->find();
            if ($food_info) {
                if ($food_info['status'] == 2) {
                    if ($food_info['wx_user_id'] == $this->wxid) {
                        $msg = " 不可以领自己赠送出的材料~<br><span>快去升旗获得更多材料吧！</span>";
                        $this->assign('success_flag', 3);
                    } else {
                        if ($food_info['wx_zengs_id'] == $this->wxid) {
                            $msg = "表闹，已经领过啦！<br><span>自己去升旗获得其它材料吧</span>";
                            $this->assign('success_flag', 2);
                        } else {
                            $msg = "哎哟！来晚一步，被抢光啦！<br><span>自己去升旗获得其它材料吧</span>";
                            $this->assign('success_flag', 4);
                        }
                    }
                } else {
                    // 材料没有被领取
                    // 自己不能领取领取的
                    if ($food_info['wx_user_id'] == $this->wxid) {
                        $msg = "不可以领自己赠送出的材料哦!<br><span>快去升旗获得其它材料吧</span>";
                        $this->assign('success_flag', 3);
                    } else {
                        // 领取材料(判断是否玩过游戏)
                        $has_play = M("twx_national_score")->where(
                                array(
                                        "node_id" => $this->node_id,
                                        "batch_id" => $this->batch_id,
                                        "wx_user_id" => $this->wxid))->find();
                        if ($has_play) {
                            $player_info = json_decode(
                                    $has_play['foods_number'], true);
                            $player_info[$fid] ++;
                            // 存入数据库
                            M()->startTrans();
                            $res = M("twx_national_score")->where(
                                    array(
                                            "node_id" => $this->node_id,
                                            "batch_id" => $this->batch_id,
                                            "wx_user_id" => $this->wxid))->save(
                                    array(
                                            'foods_number' => json_encode($player_info)));
                            if ($res === false) {
                                M()->rollback();
                                $msg = "领取材料失败";
                                $this->assign('success_flag', 5);
                            }
                            if ($res) {
                                $res1 = M("twx_national_zstrace")->where(
                                        array(
                                                "node_id" => $this->node_id,
                                                "batch_id" => $this->batch_id,
                                                "openid_md5" => $food_info['openid_md5']))->save(
                                        array(
                                                'status' => 2,
                                                "wx_zengs_id" => $this->wxid));
                                if ($res1 === false) {
                                    M()->rollback();
                                    $msg = "领取材料失败";
                                    $this->assign('success_flag', 5);
                                }
                                M()->commit();
                                $msg = "赠送给您一份材料<br><span>集齐指定材料就能获得精品好礼噢！</span>";
                                $this->assign('success_flag', 1);
                            }
                        } else {
                            // 没有玩过游戏
                            $arr = array(
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
                            $res = M("twx_national_score")->add($data);
                            if ($res === false) {
                                M()->rollback();
                                $msg = "领取材料失败";
                                $this->assign('success_flag', 5);
                            }
                            if ($res) {
                                $res1 = M("twx_national_zstrace")->where(
                                        array(
                                                "node_id" => $this->node_id,
                                                "batch_id" => $this->batch_id,
                                                "openid_md5" => $food_info['openid_md5']))->save(
                                        array(
                                                'status' => 2,
                                                "wx_zengs_id" => $this->wxid));
                                if ($res1 === false) {
                                    M()->rollback();
                                    $msg = "领取材料失败";
                                    $this->assign('success_flag', 5);
                                }
                                M()->commit();
                                $msg = "赠送给您一份材料<br><span>集齐指定材料就能获得精品好礼噢！</span>";
                                $this->assign('success_flag', 1);
                            }
                        }
                    }
                }
            }
        }
        // 活动不正常
        $zswxid = M("twx_national_zstrace")->where("openid_md5 = '$url_final'")->find();
        $wx_image = M("twx_wap_user")->where(
                array(
                        "id" => $zswxid['wx_user_id']))->find();
        // 第几个材料
        if ($fid == 3) {
            $fid = 5;
        }
        $this->assign('food_name', $fid);
        $this->assign('aid', $zswxid['food_num']);
        $this->assign("wx_image ", $wx_image);
        $this->assign('msg', $msg);
        $this->assign('nickname', $wx_image['nickname']);
        $this->assign('headimgurl', $wx_image['headimgurl']);
        $this->display();
    }

    public function sendCheckCode() {
        $phoneNo = I('phone', null);
        if (! check_str($phoneNo,
                array(
                        'null' => false,
                        'strtype' => 'mobile'), $error)) {
            $this->error("手机号{$error}");
        }
        // 发送频率验证
        $nation_groupCheckCode = session('nation_groupCheckCode');
        if (! empty($nation_groupCheckCode) &&
                (time() - $nation_groupCheckCode['add_time']) < $this->expiresTime) {
            $this->error('动态密码发送过于频繁!');
        }
        $num = mt_rand(1000, 9999);
        // $num = 1111;
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
        $nation_groupCheckCode = array(
                'number' => $num,
                'add_time' => time(),
                'phoneNo' => $phoneNo);
        session('nation_groupCheckCode', $nation_groupCheckCode);
        exit(
        json_encode(
                array(
                        'info' => '发送验证码成功！',
                        'status' => 1)));
    }

    // 我的卡券界面
    public function mycardList() {
        // 查询我的卡券1为未领取，2为领取，领取卡券需要置灰
        $map = array(
                'a.node_id' => $this->node_id,
                'a.batch_id' => $this->batch_id,
                'a.wx_user_id' => $this->wxid);
        $card_list = M()->table("twx_nationaldj_trace a")
                ->field(
                        'a.goods_name,a.b_id,z.id as relation_id,h.status as wxcard_status,b.use_status,a.request_id,a.card_num,x.link_url,s.use_time,a.cj_traceid,a.card_id,a.card_ext,a.bonus_id,a.bonus_use_detail_id,b.phone_no as barcode_phone,s.phone as bonus_phone,c.send_mobile')
                ->join('tbonus_use_detail s on s.id=a.bonus_use_detail_id')
                ->join('twx_assist_number h on h.assist_number=a.card_num')
                ->join(
                        'twx_national_relation z on z.wx_user_id=a.wx_user_id and z.node_id=a.node_id')
                ->join('tbonus_info x on x.id=a.bonus_id')
                ->join('tcj_trace c on c.id=a.cj_traceid')
                ->join('tcj_batch o on o.id=c.rule_id')
                ->join('tbatch_info g on g.id=o.b_id')
                ->join('tbarcode_trace b on b.request_id=a.request_id')
                ->where($map)
                ->select();
        $this->assign('openid', $this->openId);
        $this->assign("card_list", $card_list);
        $this->assign('id', $this->id);
        $this->display();
    }

    // 通过活动配置的中奖数生成中奖数组
    public function prizeArr() {
        $marketInfo = $this->marketInfo;
        $config_data = unserialize($marketInfo['config_data']);
        // 1.假设一等奖100名 二等奖200名 三等奖概率70%
        if ($config_data) {
            $prize_arr = array();
            // 算1等奖的2
            if ($config_data['prizeChance'][0] > 0) {
                for ($i = 1; $i <= $config_data['prizeChance'][0]; $i ++) {
                    $prize_arr[] = 2;
                }
            }
            // 算2等奖的5
            if ($config_data['prizeChance'][1] > 0) {
                for ($i = 1; $i <= $config_data['prizeChance'][1]; $i ++) {
                    $prize_arr[] = 5;
                }
            }
            if ($config_data['prizeChance'][2] > 0) {
                $score_0 = ceil($config_data['prizeChance'][2] / 2);
                $score_1 = intval($config_data['prizeChance'][2] - $score_0);
                for ($i = 1; $i <= $score_0; $i ++) {
                    $prize_arr[] = 0;
                }
                for ($i = 1; $i <= $score_1; $i ++) {
                    $prize_arr[] = 1;
                }
            }
            // 剩余的算空
            $score_k = 1000 - $config_data['prizeChance'][0] -
                    $config_data['prizeChance'][1] - $config_data['prizeChance'][2];
            if ($score_k < 1000) {
                for ($i = 1; $i <= $score_k; $i ++) {
                    $prize_arr[] = "";
                }
            }
            // 打乱数组
            shuffle($prize_arr);
            return $prize_arr;
        }
    }

    // 查询用户所中奖奖品
    public function jpArr() {
        // 查询我的卡券1为未领取，2为领取，领取卡券需要置灰
        $map = array(
                'a.node_id' => $this->node_id,
                'a.batch_id' => $this->batch_id);
        $list = M()->table("twx_nationaldj_trace a")->field(
                'a.goods_name,z.nickname,z.headimgurl')
                ->join('twx_wap_user z on z.id=a.wx_user_id')
                ->where($map)
                ->order("a.id desc")
                ->limit(50)
                ->select();
        return $list;
    }

    public function conversionJp() {
        $jp_type = I("jp_type");
        $tcj_cate_id = I("tcj_cate_id");
        $card_id = I('card_id');

        // 同时要判断验证码的有效期，是否在设置的时间内
        if ($jp_type == "" || $tcj_cate_id == "") {
            exit(
            json_encode(
                    array(
                            'info' => '缺少必要参数！',
                            'status' => 0,
                            'nation_flag' => 1)));
        }
        // 判断活动还没有开始提示
        $overdue = $this->checkDate();
        if ($overdue === 1) {
            exit(
            json_encode(
                    array(
                            'info' => '活动还未开始，敬请关注！',
                            'status' => 0,
                            'nation_flag' => 1)));
        } elseif ($overdue === 2) {
            exit(
            json_encode(
                    array(
                            'info' => '活动已经过期！',
                            'status' => 0)));
        }
        // 获取用户的信息
        M()->startTrans();
        $wx_user_id = $this->wxid;
        $map = array(
                "wx_user_id" => $wx_user_id,
                "node_id" => $this->node_id,
                "batch_id" => $this->batch_id);
        $traceInfo = M("twx_national_score")->where($map)
                ->lock(true)
                ->find();
        $cj_batch_id = M("tcj_batch")->where(
                array(
                        "cj_cate_id" => $tcj_cate_id,
                        "status" => 1))->find();
        if ($card_id) {
            // 判断下用户是否中过该类型的微信卡券
            $res = M("twx_nationaldj_trace")->where(
                    array(
                            'cj_batch_id' => $cj_batch_id['id'],
                            'node_id' => $this->node_id,
                            'batch_id' => $this->batch_id,
                            'card_id' => $card_id,
                            'openid' => $this->openId))->find();
            if ($res) {
                M()->rollback();
                exit(
                json_encode(
                        array(
                                'info' => '微信卡券作为奖品，只可领取一次！',
                                'status' => 0,
                                'nation_flag' => 1)));
            }
        }
        $foods_number = json_decode($traceInfo["foods_number"], true);
        $this->_isJp($jp_type);
        if ($jp_type == 1) {
            // 一等奖
            if ($foods_number[0] < 3 || $foods_number[1] < 3 ||
                    $foods_number[2] < 1 || $foods_number[3] < 1) {
                M()->rollback();
                exit(
                json_encode(
                        array(
                                'info' => '您的材料不足，无法兑换！',
                                'status' => 0,
                                'nation_flag' => 1)));
            }
            $foods_number[0] = $foods_number[0] - 3;
            $foods_number[1] = $foods_number[1] - 3;
            $foods_number[2] = $foods_number[2] - 1;
            $foods_number[3] = $foods_number[3] - 1;
        } elseif ($jp_type == 2) {
            // 二等奖
            if ($foods_number[0] < 2 || $foods_number[1] < 3 ||
                    $foods_number[3] < 1) {
                M()->rollback();
                exit(
                json_encode(
                        array(
                                'info' => '您的材料不足，无法兑换！',
                                'status' => 0,
                                'nation_flag' => 1)));
            }
            $foods_number[0] = $foods_number[0] - 2;
            $foods_number[1] = $foods_number[1] - 3;
            $foods_number[3] = $foods_number[3] - 1;
        } else {
            if ($foods_number[0] < 2 || $foods_number[1] < 2) {
                M()->rollback();
                exit(
                json_encode(
                        array(
                                'info' => '您的材料不足，无法兑换！',
                                'status' => 0,
                                'nation_flag' => 1)));
            }
            $foods_number[0] = $foods_number[0] - 2;
            $foods_number[1] = $foods_number[1] - 2;
        }
        $data = array(
                "foods_number" => json_encode($foods_number));
        $res = M("twx_national_score")->where(
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
                            'nation_flag' => 1)));
        }
        import('@.Vendor.ChouJiang');

        if ($this->marketInfo['join_mode'] == '1') {
            $configData = unserialize($this->marketInfo['config_data']);
            $sess = $configData['wx_auth_type'] ? session('node_wxid_' . $this->node_id) : session('node_wxid_global');
            $other_arr = array(
                    'wx_open_id' => $sess['openid'],
                    'wx_nick' => $sess['nickname'],
                    'wx_cjcate_id' => $tcj_cate_id,
                    'wx_cjbatch_id' => $cj_batch_id['id']);
        }
        $choujiang = new ChouJiang($this->id, '', $this->full_id, '', $other_arr);
        $resp = $choujiang->send_code();
        // 中奖提示
        if (isset($resp['resp_id']) && $resp['resp_id'] == '0000') {
            M()->commit();
            $cj_msg = "恭喜您中奖了！";
            // 奖品类别
            $cjCateId = M()->table('tcj_batch b')
                    ->join('tcj_cate c ON b.cj_cate_id=c.id')
                    ->join('tgoods_info d ON b.goods_id=d.goods_id')
                    ->join('tbatch_info e on e.id=b.b_id')
                    ->where("b.id={$resp['rule_id']}")
                    ->field(
                            'b.goods_id,d.goods_name,d.goods_image,d.goods_type,d.bonus_id')
                    ->find();
            if (! $cjCateId) {
                log_write("升旗手" . M()->_sql());
            }
            if ($resp['card_ext']) {
                $card_ext = json_decode($resp['card_ext'], true);
                $card_num = $card_ext['code'];
            }
            $cj_tarce_arr = array(
                    'wx_user_id' => $this->wxid,
                    'openid' => $sess['openid'],
                    'wx_cjcate_id' => $tcj_cate_id,
                    'node_id' => $this->node_id,
                    'batch_id' => $this->batch_id,
                    'cj_traceid' => $resp['cj_trace_id'],
                    'cj_batch_id' => $cj_batch_id['id'],
                    'request_id' => $resp['request_id'],
                    'card_ext' => $resp['card_ext'],
                    'card_id' => $resp['card_id'],
                    'card_num' => $card_num,
                    'goods_name' => $cjCateId['goods_name'],
                    'bonus_id' => $cjCateId['bonus_id'],
                    'b_id' => $cj_batch_id['b_id'],
                    'bonus_use_detail_id' => $resp['bonus_use_detail_id'],
                    'add_time' => date('YmdHis', time()));
            $res_nationnaldj = M("twx_nationaldj_trace")->add($cj_tarce_arr);
            if ($res_nationnaldj === false) {
                log_write('存入中奖流水表失败' . $resp['request_id']);
            }
            $relation_mobile = M("twx_national_relation")->where(
                    array(
                            'wx_user_id' => $this->wxid,
                            'node_id' => $this->node_id,
                            'batch_id' => $this->batch_id))->find();
            // 红包不走重发，红包处理逻辑
            if ($cjCateId['goods_type'] == 12) {
                $bonus_use_detail_id = $resp['bonus_use_detail_id'];
                $info = M('tbonus_use_detail')->where(
                        array(
                                'id' => $bonus_use_detail_id))->find();
                if (! $info) {
                    log_write("红包没有找到！");
                    exit(
                    json_encode(
                            array(
                                    'info' => '兑换奖品失败！',
                                    'status' => 0,
                                    'nation_flag' => 1)));
                }
                if ($relation_mobile) {
                    M()->startTrans();
                    $res_trace = M("tcj_trace")->where(
                            array(
                                    'id' => $resp['cj_trace_id']))->save(
                            array(
                                    'send_mobile' => $relation_mobile['mobile']));
                    if ($res_trace === false) {
                        M()->rollback();
                        exit(
                        json_encode(
                                array(
                                        'info' => '兑换奖品失败！',
                                        'status' => 0,
                                        'nation_flag' => 1)));
                    }
                    $result = M('tbonus_use_detail')->where(
                            array(
                                    'id' => $bonus_use_detail_id))->save(
                            array(
                                    'phone' => $relation_mobile['mobile']));
                    if ($result === false) {
                        M()->rollback();
                        exit(
                        json_encode(
                                array(
                                        'info' => '兑换奖品失败！',
                                        'status' => 0,
                                        'nation_flag' => 1)));
                    } else {
                        $respData['type'] = 1;
                        $respData['link_flag'] = 0;
                        $respData['flage'] = "flage";
                    }
                    M()->commit();
                }
                $bonusInfo = M('tbonus_info')->where(
                        array(
                                'id' => $cjCateId['bonus_id']))->find();
                if ($bonusInfo) {
                    $respData['link_flag'] = $bonusInfo['link_flag'];
                    $respData['link_url'] = $bonusInfo['link_url'];
                    $respData['button_name'] = $bonusInfo['button_name'];
                    $respData['cj_traceid'] = $resp['cj_trace_id'];
                    $respData['request_id'] = $resp['request_id'];
                    $respData['goods_name'] = $cjCateId['goods_name'];
                    $respData['bonus_use_detail_id'] = $resp['bonus_use_detail_id'];
                    $respData['bonus_page_name'] = $bonusInfo["bonus_page_name"];
                }
                exit(
                json_encode(
                        array(
                                'info' => $respData,
                                'msg' => "请输入手机号后去中奖纪录中查看领取。",
                                'status' => 1)));
            }
            // 卡券不走重发
            if (empty($resp['card_id']) && empty($resp['card_ext'])) {
                if ($relation_mobile) {
                    // 直接走重发接口
                    M()->startTrans();
                    // 修改数据库中的手机号字段，并且调用重发接口
                    $result = M('tcj_trace')->where(
                            array(
                                    'id' => $resp['cj_trace_id']))->save(
                            array(
                                    'send_mobile' => $relation_mobile['mobile']));
                    if ($result === false) {
                        M()->rollback();
                        $this->ajaxReturn("error", '很遗憾领取失败', 0);
                    }
                    // 修改发码表的字段
                    $result = M('tbarcode_trace')->where(
                            array(

                                    'request_id' => $resp['request_id']))->save(
                            array(
                                    'phone_no' => $relation_mobile['mobile']));
                    if ($result === false) {
                        M()->rollback();
                        $this->ajaxReturn("error", '很遗憾领取失败', 0);
                    }
                    M()->commit();
                    // 然后调用重发接口
                    import("@.Vendor.CjInterface");
                    $req = new CjInterface();
                    $result = $req->cj_resend(
                            array(
                                    'request_id' => $resp['request_id'],
                                    'node_id' => $this->node_id,
                                    'user_id' => '00000000'));
                    if (! $result || $result['resp_id'] != '0000') {
                        M()->rollback();
                        $this->ajaxReturn("error", '很遗憾领取失败', 0);
                    }
                    M()->commit();
                    exit(
                    json_encode(
                            array(
                                    'flage' => "flage",
                                    'status' => 1,
                                    'goods_name' => $cjCateId['goods_name'],
                                    'goods_image' => $cjCateId['goods_image'],
                                    'info' => "领取成功",
                                    'status' => 1,
                                    'type' => 0)));
                }
                $respData = array(
                        'data' => $cjCateId,
                        'goods_name' => $cjCateId['goods_name'],
                        'goods_image' => $cjCateId['goods_image'],
                        'info' => $cj_msg,
                        'status' => 1,
                        'type' => 0,
                        'cj_traceid' => $resp['cj_trace_id'],
                        'request_id' => $resp['request_id'],
                        'card_ext' => $resp['card_ext'],
                        'card_id' => $resp['card_id']);
                exit(
                json_encode(
                        array(
                                'info' => $respData,
                                'msg' => "请输入手机号，以便我们发送中奖凭证给您！",
                                'status' => 1)));
            } else {
                $respData = array(
                        'data' => $cjCateId,
                        'goods_name' => $cjCateId['goods_name'],
                        'goods_image' => $cjCateId['goods_image'],
                        'info' => $cj_msg,
                        'status' => 1,
                        'type' => 0,
                        'cj_traceid' => $resp['cj_trace_id'],
                        'request_id' => $resp['request_id'],
                        'card_ext' => $resp['card_ext'],
                        'card_id' => $resp['card_id']);
                exit(
                json_encode(
                        array(
                                'info' => $respData,
                                'status' => 1)));
            }
        } else {
            M()->rollback();
            if ($resp['resp_id'] == "1002" || $resp['resp_id'] == "1001" ||
                    $resp['resp_id'] == "1014" || $resp['resp_id'] == "1010" ||
                    $resp['resp_id'] == "1005") {
                if (isset($resp['resp_id']) && $resp['resp_id'] == '1002') {
                    $resp_msg = "很抱歉，今日奖品已发完。请明日早点来领取。";
                } elseif ($resp['resp_id'] == '1001') {
                    // 返回1001，去判断是今日已发完，还是提示奖品已经发完
                    $this->_checkDayCount($resp['resp_id'], $cj_batch_id['id']);
                } elseif ($resp['resp_id'] == '1014') {
                    $resp_msg = "很抱歉，您已达到活动领奖上限。感谢您的参与，请继续关注我们。";
                } elseif ($resp['resp_id'] == '1010') {
                    $resp_msg = "该微信用户非关注会员。";
                } elseif ($resp['resp_id'] == '1005') {
                    $resp_msg = "该号码当天已达到上线。";
                }
                exit(
                json_encode(
                        array(
                                'info' => $resp_msg,
                                'status' => 0,
                                'nation_flag' => 1)));
            }
            $fail_arr = array(
                    '1001',
                    '1002',
                    '1003',
                    '1006',
                    '1005',
                    '1016',
                    '1014');
            // 兑奖失败，将用户材料返回
            if (in_array($resp['resp_id'], $fail_arr))
                $resp_msg = "您来晚了，该奖品已经被抢光了";
            else
                $resp_msg = "很遗憾领取失败";
            exit(
            json_encode(
                    array(
                            'info' => $resp_msg,
                            'status' => 0,
                            'nation_flag' => 1)));
        }
    }

    public function reSend() {
        $cj_trace_id = I('cj_traceid');
        $request_id = I('request_id');
        if (empty($cj_trace_id)) {
            exit(
            json_encode(
                    array(
                            'info' => '参数错误！',
                            'status' => 0,
                            'nation_flag' => 1)));
        }
        $yy = M("tcj_trace")->where(
                array(
                        'mobile' => $this->openId,
                        'id' => $cj_trace_id))->find();
        if (empty($yy)) {
            exit(
            json_encode(
                    array(
                            'info' => '参数错误！',
                            'status' => 0,
                            'nation_flag' => 1)));
        }
        $bonus_use_detail_id = I('bonus_use_detail_id', null,
                'mysql_real_escape_string');
        $map = array(
                'wx_user_id' => $this->wxid,
                'node_id' => $this->node_id,
                'batch_id' => $this->batch_id);
        $relation_mobile = M("twx_national_relation")->where($map)->find();
        if (! $relation_mobile) {
            $mobile = I("phone", null, 'mysql_real_escape_string');
            $verify = I('verify', 0, 'trim');
            $verifySession = session('nation_groupCheckCode');
            if ($verify == '' || $verify != $verifySession['number']) {
                exit(
                json_encode(
                        array(
                                'info' => '请输入正确的验证码！',
                                'status' => 0,
                                'nation_flag' => 1)));
            }
            if (time() - $verifySession['add_time'] > $this->expiresTime) {
                exit(
                json_encode(
                        array(
                                'info' => '手机验证码已经过期！',
                                'status' => 0,
                                'nation_flag' => 1)));
            }
            if ($mobile != '' && $mobile != $verifySession['phoneNo']) {
                exit(
                json_encode(
                        array(
                                'info' => '手机号码不正确！',
                                'status' => 0,
                                'nation_flag' => 1)));
            }
        }
        // 红包领取逻辑
        if ($bonus_use_detail_id) {
            // 通过$bonus_use_detail_id 获取bonus_id
            $bonus_id = M("twx_nationaldj_trace")->where(
                    array(
                            'bonus_use_detail_id' => $bonus_use_detail_id))->getField(
                    'bonus_id');
            $info = M('tbonus_use_detail')->where(
                    array(
                            'id' => $bonus_use_detail_id))->find();
            if (! $info) {
                exit(
                json_encode(
                        array(
                                'info' => '兑换奖品失败！',
                                'status' => 0,
                                'nation_flag' => 1)));
            }
            if ($relation_mobile) {
                M()->startTrans();
                $result = M('tbonus_use_detail')->where(
                        array(
                                'id' => $bonus_use_detail_id))->save(
                        array(
                                'phone' => $relation_mobile['mobile']));
                if ($result === false) {
                    M()->rollback();
                    exit(
                    json_encode(
                            array(
                                    'info' => '兑换奖品失败！',
                                    'status' => 0,
                                    'nation_flag' => 1)));
                } else {
                    // 修改tcj_trace表格里面的sendmobile
                    $res_trace = M("tcj_trace")->where(
                            array(
                                    'id' => $cj_trace_id))->save(
                            array(
                                    'send_mobile' => $relation_mobile['mobile']));
                    if ($res_trace === false) {
                        M()->rollback();
                        exit(
                        json_encode(
                                array(
                                        'info' => '兑换奖品失败！',
                                        'status' => 0,
                                        'nation_flag' => 1)));
                    }
                    M()->commit();
                    $respData['type'] = 1;
                    $respData['link_flag'] = 0;
                }
            } else {
                M()->startTrans();
                $result = M('tbonus_use_detail')->where(
                        array(
                                'id' => $bonus_use_detail_id))->save(
                        array(
                                'phone' => $mobile));
                if ($result === false) {
                    M()->rollback();
                    exit(
                    json_encode(
                            array(
                                    'info' => '兑换奖品失败！',
                                    'status' => 0,
                                    'nation_flag' => 1)));
                } else {
                    $respData['type'] = 1;
                    $respData['link_flag'] = 0;
                }
                // 修改tcj_trace表格里面的sendmobile
                $res_trace = M("tcj_trace")->where(
                        array(
                                'id' => $cj_trace_id))->save(
                        array(
                                'send_mobile' => $mobile));
                if ($res_trace === false) {
                    M()->rollback();
                    exit(
                    json_encode(
                            array(
                                    'info' => '兑换奖品失败！',
                                    'status' => 0,
                                    'nation_flag' => 1)));
                }
                $map_arr_1 = array(
                        'wx_user_id' => $this->wxid,
                        'node_id' => $this->node_id,
                        'batch_id' => $this->batch_id,
                        'openid' => $this->openId,
                        'mobile' => $mobile,
                        'add_time' => date('YmdHis', time()));
                $res = M("twx_national_relation")->add($map_arr_1);
                if ($res === false) {
                    M()->rollback();
                    $this->ajaxReturn("error", '很遗憾领取失败', 0);
                }
                M()->commit();
            }
            $bonusInfo = M('tbonus_info')->where(
                    array(
                            'id' => $bonus_id))->find();
            if ($bonusInfo) {
                $respData['link_flag'] = $bonusInfo['link_flag'];
                $respData['link_url'] = $bonusInfo['link_url'];
                $respData['button_name'] = $bonusInfo['button_name'];
                $respData['flage'] = "flage";
                $respData['bonus_page_name'] = $bonusInfo["bonus_page_name"];
            }
            exit(
            json_encode(
                    array(
                            'info' => $respData,
                            'status' => 1)));
        }
        // 同时要判断验证码的有效期，是否在设置的时间内
        if ($verify == "" || $mobile == "" || $cj_trace_id == "" ||
                $request_id == "") {
            exit(
            json_encode(
                    array(
                            'info' => '手机或者验证码不能为空！',
                            'status' => 0,
                            'nation_flag' => 1)));
        }
        M()->startTrans();
        // 修改数据库中的手机号字段，并且调用重发接口
        $result = M('tcj_trace')->where(
                array(
                        'id' => $cj_trace_id))->save(
                array(
                        'send_mobile' => $mobile));
        if ($result === false) {
            M()->rollback();
            $this->ajaxReturn("error", '很遗憾领取失败', 0);
        }
        // 修改发码表的字段
        $result = M('tbarcode_trace')->where(
                array(
                        'request_id' => $request_id))->save(
                array(
                        'phone_no' => $mobile));
        if ($result === false) {
            M()->rollback();
            $this->ajaxReturn("error", '很遗憾领取失败', 0);
        }
        if (empty($relation_mobile)) {
            $map_arr = array(
                    'wx_user_id' => $this->wxid,
                    'node_id' => $this->node_id,
                    'batch_id' => $this->batch_id,
                    'openid' => $this->openId,
                    'mobile' => $mobile,
                    'add_time' => date('YmdHis', time()));
            $res = M("twx_national_relation")->add($map_arr);
            if ($res === false) {
                M()->rollback();
                $this->ajaxReturn("error", '很遗憾领取失败', 0);
            }
        }
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
        M()->commit();
        $goods_name = M()->table("tbarcode_trace a")->join(
                "tgoods_info b ON b.goods_id=a.goods_id")
                ->where(array(
                        'request_id' => $request_id))
                ->getField('b.goods_name');
        exit(
        json_encode(
                array(
                        'info' => '领取成功！',
                        'goods_name' => $goods_name,
                        'dianzi_flag' => 1,
                        'status' => 1)));
    }

    // 获取微信card_ext
    public function get_card_ext() {
        $card_num = I('card_num');
        $open_id = $this->openId;
        // 判断用户是否已经有领取卡券
        $where = array(
                'open_id' => $open_id,
                'assist_number' => $card_num,
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
                            'info' => "微信卡券作为奖品，只可领取一次！",
                            'status' => 0)));
        }
        $service = D('WeiXinCard', 'Service');
        $service->init_by_node_id($this->node_id);
        $card_ext = $service->add_assist_number($open_id,
                $assist_number['card_batch_id']);
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

    public function _isJp($jp_type) {
        $marketInfo = $this->marketInfo;
        $config_data = unserialize($marketInfo['config_data']);
        if ($jp_type == 1) {
            // 一等奖
            if ($config_data['prizeChance']['0'] == 0) {
                exit(
                json_encode(
                        array(
                                'info' => '该奖品已经兑换完！',
                                'status' => 0)));
            }
        } elseif ($jp_type == 2) {
            // 二等奖
            if ($config_data['prizeChance']['1'] == 0) {
                exit(
                json_encode(
                        array(
                                'info' => '该奖品已经兑换完！',
                                'status' => 0)));
            }
        } elseif ($jp_type == 3) {
            // 三等奖
            if ($config_data['prizeChance']['2'] == 0) {
                exit(
                json_encode(
                        array(
                                'info' => '该奖品已经兑换完！',
                                'status' => 0)));
            }
        }
    }
    // 判断是否当天奖品已经发完
    public function _checkDayCount($resp_id, $cj_batch_id) {
        if ($resp_id == '1001') {
            $resp_msg = "很抱歉，今日该奖品已发完。请明日早点来领取。";

            // 查询是否是该奖品已经全部发完
            $cjBatchInfo = M('tcj_batch')->find($cj_batch_id);
            $map = array(
                    'rule_id' => $cj_batch_id);
            $totalCount = M('taward_daytimes')->where($map)->sum('award_times');
            log_write("升旗手_checkDayCount" . M()->_sql());
            if ($totalCount >= $cjBatchInfo['total_count']) {
                $resp_msg = "很抱歉，该奖品已发完。感谢您的参与，请继续关注我们。";
            }

            exit(
            json_encode(
                    array(
                            'info' => $resp_msg,
                            'status' => 0,
                            'nation_flag' => 1)));
        }
    }
}