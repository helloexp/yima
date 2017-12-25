<?php
// wap数据展示页面
class IndexAction extends BaseAction {

    public $_authAccessMap = '*';
    // 权限映射表
    public $wxid;

    public $node_id;

    public $WxConfig;

    public function _initialize() {
        $this->WxConfig = D('WeiXin', 'Service')->getWxShareConfig();
        
        if (! session('?Mwxid')) {
            $this->redirect(U('Mobile/WeixinLogin/index'));
        }
        $this->wxid = session('Mwxid');
        
        // 绑定免登陆
        $info = M('twx_user_wc_mobile')->where(
            array(
                'open_id' => $this->wxid))->find();
        
        if (! $info) {
            parent::_initialize();
        } else {
            $this->node_id = $info['node_id'];
            $this->wxid = $info['open_id'];
        }
        
        /*
         * if (!in_array(ACTION_NAME, array('login'))){ $this->_checkLogin(); }
         */
        
        /* ==营销活动数据START== */
        // 总访问量
        $click_counts_n = M('tmarketing_info')->where(
            array(
                'node_id' => $this->node_id))->sum('click_count');
        
        // 粉丝总数
        $member_counts_n = M('tmember_info')->where(
            array(
                'node_id' => $this->node_id))->count();
        // 卡券发送/验证数
        $sql = "SELECT SUM(send_num) as send_num,sum(verify_num) as verify_num  FROM tpos_day_count WHERE node_id= '" .
             $this->node_id . "'";
        $result_n = M()->query($sql);
        
        $click_counts_n = $click_counts_n ? $click_counts_n : 0;
        $send_num_n = $result_n[0]['send_num'] ? $result_n[0]['send_num'] : 0;
        
        $today = date("Ymd");
        $week = date("Ymd", strtotime("-1 month"));
        
        // 今天
        $click_counts_y = M('tmarketing_info')->where(
            array(
                'node_id' => $this->node_id, 
                'add_time' => array(
                    'egt', 
                    $today . '000000')))->sum('click_count');
        $wh_today = " and trans_date = '" . date('Y-m-d') . "'";
        $result_y = M()->query($sql . $wh_today);
        
        $send_num_y = $result_y[0]['send_num'] ? $result_y[0]['send_num'] : 0;
        $click_counts_y = $click_counts_y ? $click_counts_y : 0;
        
        // 一月
        $click_counts_by = M('tmarketing_info')->where(
            array(
                'node_id' => $this->node_id, 
                'add_time' => array(
                    'egt', 
                    $week . '000000')))->sum('click_count');
        $wh_week = "and trans_date >= '" . date('Y-m-d', strtotime('-1 month')) .
             "'";
        $result_by = M()->query($sql . $wh_week);
        // echo M()->_sql();
        $click_counts_by = $click_counts_by ? $click_counts_by : 0;
        $send_num_by = $result_by[0]['send_num'] ? $result_by[0]['send_num'] : 0;
        
        $verify_num = $result_n[0]['send_num'] ? $result_n[0]['verify_num'] : 0;
        
        // 正在进行的活动
        // $this_time = date('YmdHis');
        // $batchNum = M('tmarketing_info')->where("node_id='{$this->nodeId}'
        // and status=1 and start_time<='".$this_time."' and
        // end_time>='".$this_time."' and id in( select batch_id from
        // tbatch_channel where node_id='{$this->nodeId}' and status =
        // 1)")->count();
        
        // 累计开展的活动
        $batch_all_n = M('tmarketing_info')->where(
            array(
                'node_id' => $this->node_id))->count();
        $batch_all_y = M('tmarketing_info')->where(
            array(
                'node_id' => $this->node_id, 
                'add_time' => array(
                    'egt', 
                    $today . '000000')))->count();
        $batch_all_by = M('tmarketing_info')->where(
            array(
                'node_id' => $this->node_id, 
                'add_time' => array(
                    'egt', 
                    $week . '000000')))->count();
        
        /*
         * //营销互动人次 $arr_n = M('tmarketing_info')->field('sum(cj_count) as
         * count')->where(array('node_id'=>$this->node_id))->find(); $arr_y =
         * M('tmarketing_info')->field('sum(cj_count) as
         * count')->where(array('node_id'=>$this->node_id,'add_time'=>array('egt',$week.'000000')))->find();
         * $arr_by = M('tmarketing_info')->field('sum(cj_count) as
         * count')->where(array('node_id'=>$this->node_id,'add_time'=>array('egt',$today.'000000')))->find();
         * $hd_count_n = $arr_n['count'] ? $arr_n['count'] : 0; $hd_count_y =
         * $arr_y['count'] ? $arr_y['count'] : 0; $hd_count_by =
         * $arr_by['count'] ? $arr_by['count'] : 0;
         */
        
        $this->assign('aclick_counts_n', $click_counts_n);
        $this->assign('amember_counts_n', $member_counts_n);
        $this->assign('asend_num_n', $send_num_n);
        $this->assign('aclick_counts_y', $click_counts_y);
        $this->assign('asend_num_y', $send_num_y);
        $this->assign('aclick_counts_by', $click_counts_by);
        $this->assign('asend_num_by', $send_num_by);
        $this->assign('averify_num', $verify_num);
        // $this->assign('abatchNum',$batchNum);
        $this->assign('abatch_all_n', $batch_all_n);
        $this->assign('abatch_all_y', $batch_all_y);
        $this->assign('abatch_all_by', $batch_all_by);
        // $this->assign('ahd_count_y',$hd_count_y);
        // $this->assign('ahd_count_n',$hd_count_n);
        // $this->assign('ahd_count_by',$hd_count_by);
        /* ==营销活动数据END== */
        
        /* ==多宝电商数据START== */
        // 累计访问量
        $o2o_all_click_n = M('tmarketing_info')->where(
            array(
                'node_id' => $this->node_id, 
                'batch_type' => array(
                    'in', 
                    '26,27,29')))->getField('sum(click_count)');
        // 累计订单数
        $o2o_order_count_n = M('ttg_order_info')->where(
            array(
                'node_id' => $this->node_id, 
                'order_status' => '0', 
                'pay_status' => '2'))->count();
        // 累计成交额
        $o2o_order_amount_n = M('ttg_order_info')->where(
            array(
                'node_id' => $this->node_id, 
                'order_status' => '0', 
                'pay_status' => '2'))->getField('sum(order_amt)');
        if (! $o2o_order_amount_n)
            $o2o_order_amount_n = 0;
            
            // 今天
        $o2o_all_click_y = M('tmarketing_info')->where(
            array(
                'node_id' => $this->node_id, 
                'add_time' => array(
                    'egt', 
                    $today . '000000'), 
                'batch_type' => array(
                    'in', 
                    '26,27,29')))->getField('sum(click_count)');
        if (! $o2o_all_click_y)
            $o2o_all_click_y = 0;
        $o2o_order_count_y = M('ttg_order_info')->where(
            array(
                'node_id' => $this->node_id, 
                'add_time' => array(
                    'egt', 
                    $today . '000000'), 
                'order_status' => '0', 
                'pay_status' => '2'))->count();
        if (! $o2o_order_count_y)
            $o2o_order_count_y = 0;
        $o2o_order_amount_y = M('ttg_order_info')->where(
            array(
                'node_id' => $this->node_id, 
                'add_time' => array(
                    'egt', 
                    $today . '000000'), 
                'order_status' => '0', 
                'pay_status' => '2'))->getField('sum(order_amt)');
        if (! $o2o_order_amount_y)
            $o2o_order_amount_y = 0;
            
            // 一月
        $o2o_all_click_by = M('tmarketing_info')->where(
            array(
                'node_id' => $this->node_id, 
                'add_time' => array(
                    'egt', 
                    $week . '000000'), 
                'batch_type' => array(
                    'in', 
                    '26,27,29')))->getField('sum(click_count)');
        if (! $o2o_all_click_by)
            $o2o_all_click_by = 0;
        $o2o_order_count_by = M('ttg_order_info')->where(
            array(
                'node_id' => $this->node_id, 
                'add_time' => array(
                    'egt', 
                    $week . '000000'), 
                'order_status' => '0', 
                'pay_status' => '2'))->count();
        if (! $o2o_order_count_by)
            $o2o_order_count_by = 0;
        $o2o_order_amount_by = M('ttg_order_info')->where(
            array(
                'node_id' => $this->node_id, 
                'add_time' => array(
                    'egt', 
                    $week . '000000'), 
                'order_status' => '0', 
                'pay_status' => '2'))->getField('sum(order_amt)');
        
        if (! $o2o_order_amount_by)
            $o2o_order_amount_by = 0;
        
        $this->assign('o2o_all_click_n', $o2o_all_click_n);
        $this->assign('o2o_order_count_n', $o2o_order_count_n);
        $this->assign('o2o_order_amount_n', $o2o_order_amount_n);
        $this->assign('o2o_all_click_y', $o2o_all_click_y);
        $this->assign('o2o_order_count_y', $o2o_order_count_y);
        $this->assign('o2o_order_amount_y', $o2o_order_amount_y);
        $this->assign('o2o_all_click_by', $o2o_all_click_by);
        $this->assign('o2o_order_count_by', $o2o_order_count_by);
        $this->assign('o2o_order_amount_by', $o2o_order_amount_by);
        /* ==多宝电商数据END== */
        
        /* ==条码支付数据STATR== */
        
        $stat_arr = M('tzfb_day_stat')->where(
            array(
                'node_id' => $this->node_id))
            ->Field(
            'sum(verify_cnt) count,sum(verify_amt) count_,sum(cancel_cnt) cancel_cnt,sum(cancel_amt) cancel_amt')
            ->find();
        $count_num_n = $stat_arr['count'] ? $stat_arr['count'] : '0';
        $count_pay_n = $stat_arr['count_'] ? $stat_arr['count_'] : '0';
        // $cancel_cnt_n = $stat_arr['cancel_cnt'] ? $stat_arr['cancel_cnt'] :
        // '0';
        $cancel_amt_n = $stat_arr['cancel_amt'] ? $stat_arr['cancel_amt'] : '0';
        
        $stat_arr_y = M('tzfb_day_stat')->where(
            array(
                'node_id' => $this->node_id, 
                'trans_date' => array(
                    'egt', 
                    $today)))
            ->Field(
            'sum(verify_cnt) count,sum(verify_amt) count_,sum(cancel_cnt) cancel_cnt,sum(cancel_amt) cancel_amt')
            ->find();
        
        $count_num_y = $stat_arr_y['count'] ? $stat_arr_y['count'] : '0';
        $count_pay_y = $stat_arr_y['count_'] ? $stat_arr_y['count_'] : '0';
        $cancel_amt_y = $stat_arr_y['cancel_amt'] ? $stat_arr_y['cancel_amt'] : '0';
        
        $stat_arr_by = M('tzfb_day_stat')->where(
            array(
                'node_id' => $this->node_id, 
                'trans_date' => array(
                    'egt', 
                    $week)))
            ->Field(
            'sum(verify_cnt) count,sum(verify_amt) count_,sum(cancel_cnt) cancel_cnt,sum(cancel_amt) cancel_amt')
            ->find();
        
        $count_num_by = $stat_arr_by['count'] ? $stat_arr_by['count'] : '0';
        $count_pay_by = $stat_arr_by['count_'] ? $stat_arr_by['count_'] : '0';
        $cancel_amt_by = $stat_arr_by['cancel_amt'] ? $stat_arr_by['cancel_amt'] : '0';
        
        $this->assign('count_num_n', $count_num_n);
        $this->assign('count_pay_n', $count_pay_n);
        $this->assign('cancel_amt_n', $count_pay_n - $cancel_amt_n);
        $this->assign('count_num_y', $count_num_y);
        $this->assign('count_pay_y', $count_pay_y);
        $this->assign('cancel_amt_y', $count_pay_y - $cancel_amt_y);
        $this->assign('count_num_by', $count_num_by);
        $this->assign('count_pay_by', $count_pay_by);
        $this->assign('cancel_amt_by', $count_pay_by - $cancel_amt_by);
        /* ==条码支付数据END== */
        
        $this->assign('nodeInfo', get_node_info($this->node_id));
        $this->assign('coinInfo', $this->getAccountInfo());
        $this->assign('wbInfo', $this->getWbInfo(2));
        $this->assign('shareData', $this->WxConfig);
    }
    
    // 查询用户余额和流量
    public function getAccountInfo() {
        // 查询商户账户余额和流量
        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
                                                                   // 商户余额流浪报文参数
        $req_array = array(
            'QueryShopInfoReq' => array(
                'SystemID' => C('YZ_SYSTEM_ID'), 
                'NodeID' => $this->node_id, 
                'TransactionID' => $TransactionID, 
                'ContractID' => $this->contractId));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $AccountInfo = $RemoteRequest->requestYzServ($req_array);
        
        if (! $AccountInfo || ($AccountInfo['Status']['StatusCode'] != '0000' &&
             $AccountInfo['Status']['StatusCode'] != '0001')) {
            $AccountInfo = array();
        }
        return $AccountInfo;
    }
    
    // 查询用户名旺币余额和流水 $type 1获取所有wb信息 2只获取wb余额
    public function getWbInfo($type = 1) {
        // 创建接口对象
        $RemoteRequest = D('RemoteRequest', 'Service');
        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
        $clientId = M('tnode_info')->where(
            array(
                'node_id' => $this->node_id))->getField('client_id');
        
        // 商户服务信息报文参数
        $req_array = array(
            'QueryWbListReq' => array(
                'SystemID' => C('YZ_SYSTEM_ID'), 
                'TransactionID' => $TransactionID, 
                'ClientID' => $clientId));
        $nodeWbInfo = $RemoteRequest->requestYzServ($req_array);
        // dump($nodeWbInfo);
        $nodeWbList = array();
        $nodeWbList['wbOver'] = '0';
        if (! empty($nodeWbInfo['WbList'])) {
            if (isset($nodeWbInfo['WbList'][0])) {
                $nodeWbList['list'] = $nodeWbInfo['WbList'];
            } else {
                $nodeWbList['list'][] = $nodeWbInfo['WbList'];
            }
        }
        if (! empty($nodeWbList['list'])) {
            foreach ($nodeWbList['list'] as $k => $v) {
                if (date('YmdHis') > $v['EndTime']) {
                    $nodeWbList['list'][$k]['WbListId'] .= '(已过期)';
                } elseif (date('YmdHis') < $v['BeingTime']) {
                    $nodeWbList['list'][$k]['WbListId'] .= '(未开始)';
                } else {
                    $nodeWbList['wbOver'] += $v['WbCurBalance'];
                }
            }
        }
        if ($type == 2)
            unset($nodeWbList['list']);
        return $nodeWbList;
    }

    /* 校验是否登录 */
    public function _checkLogin($return = false) {
        if (in_array(ACTION_NAME, array(
            'login'))) {
            $this->display('login');
            exit();
        }
        $user = D('UserSess', 'Service');
        $token = $this->_get('token');
        $resp = $user->loginByToken($token);
        
        if (! $user->isLogin()) {
            if ($return) {
                return false;
            } else {
                // $this->redirect('Home/Login/index');
                if (IS_GET) {
                    session('fromurl', 
                        U(GROUP_NAME . '/' . MODULE_NAME . '/' . ACTION_NAME, 
                            I('get.')));
                }
                if (I('IS_AJAX', 0, 'intval') == 1) {
                    $data = array(
                        'info' => "您尚未登录或登录已超时：" . $user->getErrorMsg(), 
                        'status' => 1, 
                        'rcode' => '900', 
                        'url' => array(
                            '请立即登录' => "index.php?g=Mobile&m=Index&a=login"));
                    $this->ajaxReturn($data, 'json');
                } else {
                    $this->error("您尚未登录或登录已超时：" . $user->getErrorMsg(), 
                        array(
                            '请立即登录' => "index.php?g=Mobile&m=Index&a=login"), 
                        array(
                            'rcode' => '900'));
                }
            }
        }
        return true;
    }

    public function login() {
        // 绑定后免登陆
        $this->display('all');
    }
    
    // 综合
    public function all() {
        $info = M('twx_user_wc_mobile')->where(
            array(
                'open_id' => $this->wxid))->find();
        if (! $info) {
            $data = array(
                'open_id' => $this->wxid, 
                'node_id' => $this->node_id);
            M('twx_user_wc_mobile')->add($data);
        }
        
        $this->display();
    }
    
    // 多乐互动
    public function active() {
        $begin_time = date('Ymd', strtotime("-30 days"));
        $end_time = date('Ymd');
        $wh = "where r1.node_id = '" . $this->node_id . "'";
        $day_group = "%Y-%m-%d";
        $wh .= " and r1.day >= '" . $begin_time . "'";
        $wh .= " and r1.day <= '" . $end_time . "'";
        
        $sql = "SELECT SUM(r1.click_count) AS  pv,SUM(r1.uv_count) AS uv,DATE_FORMAT(r1.day,'{$day_group}') AS t1  FROM tdaystat  r1  {$wh}  GROUP BY DATE_FORMAT(r1.day,'{$day_group}') order by r1.id asc";
        $list = M()->query($sql);
        if ($list) {
            $pv_arr = array();
            $uv_arr = array();
            $c_str = '';
            foreach ($list as $v) {
                $pv_arr[] = $v['pv'];
                $uv_arr[] = $v['uv'];
                $c_str .= "'" . $v['t1'] . "'" . ',';
            }
            $pv_str = implode(',', $pv_arr);
            $uv_str = implode(',', $uv_arr);
        }
        $this->assign('c_str', $c_str);
        $this->assign('pv_str', $pv_str);
        $this->assign('uv_str', $uv_str);
        $this->display();
    }

    public function alipay() {
        // 支付信息表
        $payInfo = M('tzfb_offline_pay_info')->field(
            'pay_type,check_status,status')
            ->where(array(
            'node_id' => $this->node_id))
            ->select();
        // 设置默认权限 0未开通 1开通
        $flag_arr = array(
            'zfb_flag' => '0',  // 是否开通 0未开通 1已开通 2停用
            'zfb_check' => '9',  // 是否为审核拒绝 0未审核 1审核通过 2审核拒绝 9 未申请
            'wx_flag' => '0', 
            'wx_check' => '9', 
            'first_flag' => '0'); // 0 首次开通 1 单开通zfb 2 单开通wx 3 zfb,wx都开通
        
        foreach ($payInfo as $v) {
            // 支付宝
            if ($v['pay_type'] == '0') {
                $flag_arr['zfb_flag'] = $v['status'];
                $flag_arr['zfb_check'] = $v['check_status'];
            }
            // 微信
            if ($v['pay_type'] == '1') {
                $flag_arr['wx_flag'] = $v['status'];
                $flag_arr['wx_check'] = $v['check_status'];
            }
        }
        if ($flag_arr['zfb_check'] == '1' && $flag_arr['wx_check'] == '1')
            $flag_arr['first_flag'] = '3';
        elseif ($flag_arr['zfb_check'] != '1' && $flag_arr['wx_check'] == '1')
            $flag_arr['first_flag'] = '2';
        elseif ($flag_arr['zfb_check'] == '1' && $flag_arr['wx_check'] != '1')
            $flag_arr['first_flag'] = '1';
        else
            $flag_arr['first_flag'] = '0';
        $this->assign('flag_arr', $flag_arr);
        $this->assign('type_name', $this->node_type_name);
        // $this->assign('zfb_account', $res['zfb_account']);
        // $this->assign('contact_name', $res['contact_name']);
        // $this->assign('contact_phone', $res['contact_phone']);
        
        // if ($flag_arr['first_flag'] != '0') {
        $stat_arr = M('tzfb_day_stat')->where(
            array(
                'node_id' => $this->node_id))
            ->Field(
            'sum(verify_cnt) count,sum(verify_amt) count_,sum(cancel_cnt) cancel_cnt,sum(cancel_amt) cancel_amt')
            ->find();
        
        $count = $stat_arr['count'] ? $stat_arr['count'] : '0';
        $count_ = $stat_arr['count_'] ? $stat_arr['count_'] : '0';
        $cancel_cnt = $stat_arr['cancel_cnt'] ? $stat_arr['cancel_cnt'] : '0';
        $cancel_amt = $stat_arr['cancel_amt'] ? $stat_arr['cancel_amt'] : '0';
        $begin = date('Ymd', strtotime('-7 days'));
        $end = date('Ymd');
        $type = 0;
        $jsChartDataAmt = $this->_getChartData($begin, $end, $type);
        
        $x_arr = array(
            '0' => '支付宝和微信', 
            '1' => '支付宝', 
            '2' => '微信');
        
        $this->assign('jsChartDataAmt', json_encode($jsChartDataAmt));
        $this->assign('begin', $begin);
        $this->assign('end', $end);
        $this->assign('x_arr', $x_arr);
        $this->assign('type', $type);
        $this->assign('count', $count);
        $this->assign('count_', $count_);
        $this->assign('cancel_cnt', $cancel_cnt);
        $this->assign('cancel_amt', $cancel_amt);
        // }
        $this->display();
    }

    public function o2o() {
        $channelInfo = M('tchannel')->where(
            array(
                'node_id' => $this->node_id, 
                'type' => '4', 
                'sns_type' => '46'))->find();
        if (! $channelInfo) { // 不存在则添加渠道
            $channel_arr = array(
                'name' => '旺财小店默认渠道', 
                'type' => '4', 
                'sns_type' => '46', 
                'status' => '1', 
                'start_time' => date('YmdHis'), 
                'end_time' => date('YmdHis', strtotime("+1 year")), 
                'add_time' => date('YmdHis'), 
                'click_count' => 0, 
                'cj_count' => 0, 
                'send_count' => 0, 
                'node_id' => $this->node_id);
            
            $cid = M('tchannel')->add($channel_arr);
            if (! $cid) {
                $this->error('初始化旺财小店默认渠道失败');
            }
        }
        $marketInfo = M('tmarketing_info')->where(
            array(
                'batch_type' => '29', 
                'node_id' => $this->node_id))->find();
        if (! $marketInfo) { // 不存在则自动新建该多乐互动
            $m_arr = array(
                'batch_type' => '29', 
                'name' => '旺财小店', 
                'status' => '1', 
                'start_time' => date('YmdHis'), 
                'end_time' => date('YmdHis', strtotime("+10 year")), 
                'add_time' => date('YmdHis'), 
                'click_count' => 0, 
                'cj_count' => 0, 
                'send_count' => 0, 
                'node_id' => $this->node_id, 
                'batch_come_from' => session('batch_from') ? session(
                    'batch_from') : '1');
            
            $m_id = M('tmarketing_info')->add($m_arr);
            if (! $m_id) {
                $this->error('初始化旺财小店失败');
            }
        }
        
        // 累计访问量
        $all_click = M('tmarketing_info')->where(
            array(
                'node_id' => $this->node_id, 
                'batch_type' => array(
                    'in', 
                    '26,27,29')))->getField('sum(click_count)');
        // 累计订单数
        $order_count = M('ttg_order_info')->where(
            array(
                'node_id' => $this->node_id, 
                'order_status' => '0', 
                'pay_status' => '2'))->count();
        // 累计成交额
        $order_amount = M('ttg_order_info')->where(
            array(
                'node_id' => $this->node_id, 
                'order_status' => '0', 
                'pay_status' => '2'))->getField('sum(order_amt)');
        if (! $order_amount)
            $order_amount = 0;
            // 正在销售的商品
        $sale_goods = M('tgoods_info')->where(
            array(
                'node_id' => $this->node_id, 
                'goods_type' => '6'))->count();
        
        // 获取收款账户信息
        $model = M('tnode_account');
        $nodeAccountInfo = $model->where(
            array(
                'node_id' => $this->node_id))->select();
        $nodeAccountInfo = array_valtokey($nodeAccountInfo, 'account_type');
        // 小店的m_id
        $m_id = $marketInfo['id'];
        // $m_id =
        // M('tmarketing_info')->where(array('node_id'=>$this->node_id,'batch_type'=>'29'))->getField('id');
        
        // 店铺地址
        $channel_id = M('tchannel')->where(
            array(
                'node_id' => $this->node_id, 
                'type' => '4', 
                'sns_type' => '46'))->getField('id');
        $label_id = get_batch_channel($m_id, $channel_id, '29', $this->node_id);
        
        $today = date('Ymd');
        $yesterday = date('Ymd', strtotime("-1 day"));
        $batch_type = '29';
        $batch_id = $m_id;
        $_get = I('get.');
        // 查询
        $_get['begin_date'] = $begin_date = I('begin_date', 
            dateformat("-30 days", 'Ymd'));
        
        $_get['end_date'] = $end_date = I('end_date', 
            dateformat("0 days", 'Ymd'));
        $map = array(
            'batch_type' => $batch_type, 
            'batch_id' => $batch_id, 
            'node_id' => $this->node_id);
        
        // 查询日期
        $map['day'] = array();
        if ($begin_date != '') {
            $map['day'][] = array(
                'EGT', 
                $begin_date);
        }
        if ($end_date != '') {
            $map['day'][] = array(
                'ELT', 
                $end_date);
        }
        
        $shop_jsChartDataClick = array(); // 小店PV访问量
        $shop_jsChartDataOrder = array(); // 小店订单数
        $shop_jsChartDataAmt = array(); // 小店销售额
        $shop_data = array(
            'PV' => array(
                date('Ymd') => 0, 
                date('Ymd', strtotime("-1 day")) => 0), 
            'order' => array(
                date('Ymd') => 0, 
                date('Ymd', strtotime("-1 day")) => 0), 
            'saleamt' => array(
                date('Ymd') => 0, 
                date('Ymd', strtotime("-1 day")) => 0));
        
        // 小店访问量
        $pv_arr = M('Tdaystat')->where($map)
            ->field("batch_type,batch_id,day,sum(click_count) click_count")
            ->group("day")
            ->select();
        // 小店-计算出JS值
        foreach ($pv_arr as $v) {
            $shop_jsChartDataClick[$v['day']] = array(
                $v['day'], 
                $v['click_count'] * 1);
            if ($v['day'] == $today)
                $shop_data['PV'][$today] = $v['click_count'] * 1;
            if ($v['day'] == $yesterday)
                $shop_data['PV'][$yesterday] = $v['click_count'] * 1;
        }
        // 小店订单数
        $order_map = array(
            'order_type' => '2', 
            'order_status' => '0', 
            'pay_status' => '2', 
            'node_id' => $this->node_id);
        // 小店查询日期
        $order_map['add_time'] = array();
        if ($begin_date != '') {
            $order_map['add_time'][] = array(
                'EGT', 
                $begin_date . '000000');
        }
        if ($end_date != '') {
            $order_map['add_time'][] = array(
                'ELT', 
                $end_date . '235959');
        }
        $order_arr = M("ttg_order_info")->field(
            "count(order_id) as order_count,substr(add_time,'1',8) as day,sum(order_amt) as order_amt")
            ->where($order_map)
            ->group("substr(add_time,1,8)")
            ->select();
        
        // 小店-计算出JS值
        foreach ($order_arr as $v) {
            $shop_jsChartDataOrder[$v['day']] = array(
                $v['day'], 
                $v['order_count'] * 1);
            $shop_jsChartDataAmt[$v['day']] = array(
                $v['day'], 
                $v['order_amt'] * 1);
            
            if ($v['day'] == $today) {
                $shop_data['order'][$today] = $v['order_count'] * 1;
                $shop_data['saleamt'][$today] = $v['order_amt'] * 1;
            }
            if ($v['day'] == $yesterday) {
                $shop_data['order'][$yesterday] = $v['order_count'] * 1;
                $shop_data['saleamt'][$yesterday] = $v['order_amt'] * 1;
            }
        }
        
        // 小店
        foreach ($shop_jsChartDataClick as $k => $v) {
            if (! $shop_jsChartDataOrder[$k])
                $shop_jsChartDataOrder[$k] = array(
                    $v[0], 
                    0);
            if (! $shop_jsChartDataAmt[$k])
                $shop_jsChartDataAmt[$k] = array(
                    $v[0], 
                    0);
        }
        // 按日期重新排序
        ksort($shop_jsChartDataOrder);
        ksort($shop_jsChartDataAmt);
        
        $this->assign('today', $today);
        $this->assign('yesterday', $yesterday);
        $this->assign('begin_date', $_get['begin_date']);
        $this->assign('end_date', $_get['end_date']);
        $this->assign('all_click', $all_click);
        $this->assign('order_count', $order_count);
        $this->assign('order_amount', $order_amount);
        $this->assign('sale_goods', $sale_goods);
        $this->assign('nodeAccountInfo', $nodeAccountInfo);
        $this->assign('shop_jsChartDataClick', 
            json_encode(array_values($shop_jsChartDataClick)));
        $this->assign('shop_jsChartDataOrder', 
            json_encode(array_values($shop_jsChartDataOrder)));
        $this->assign('shop_jsChartDataAmt', 
            json_encode(array_values($shop_jsChartDataAmt)));
        $this->assign('shop_data', $shop_data);
        
        $this->display();
    }

    /**
     * 获取chart数据 $begin 开始时间 $end 结束时间 $type 类型 0 zfb+wx 1 zfb 2 wx
     */
    protected function _getChartData($begin, $end, $type = 0) {
        $chartData = array();
        $map = array(
            'node_id' => $this->node_id);
        $map['trans_date'] = array();
        if ($begin != '') {
            $map['trans_date'][] = array(
                'EGT', 
                $begin);
        }
        if ($end != '') {
            $map['trans_date'][] = array(
                'ELT', 
                $end);
        }
        if ($type == '1')
            $map['from_type'] = '0';
        elseif ($type == '2')
            $map['from_type'] = '1';
        
        $statInfo = M('tzfb_day_stat')->where($map)
            ->field('trans_date,sum(verify_amt) verify_amt')
            ->group("trans_date")
            ->select();
        foreach ($statInfo as $v) {
            $chartData[] = array(
                $v['trans_date'], 
                $v['verify_amt'] * 1);
        }
        
        return $chartData;
    }

    /**
     * 获取chart数据 供页面ajax提交 $begin 开始时间 $end 结束时间 $type 类型 0 zfb+wx 1 zfb 2 wx
     */
    public function getChartInfo() {
        $days = I('days', 7);
        $type = I('type', 0);
        
        $begin = date('Ymd', strtotime('-' . $days . ' days'));
        $end = date('Ymd');
        $return = array(
            'info' => $this->_getChartData($begin, $end, $type), 
            'begin' => $begin, 
            'end' => $end);
        $this->ajaxReturn($return, 'json');
    }
}