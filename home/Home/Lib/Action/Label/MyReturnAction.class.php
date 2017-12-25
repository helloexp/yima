<?php

class MyReturnAction extends Action {

    private $withdraw_minval = 5;

    public $label_id = 0;

    public function _initialize() {
        header("HTTP/1.0 404 Not Found");
        $this->display('../Public/404');
        exit();
        $this->expiresTime = 120; // 手机发送间隔
        $this->CodeexpiresTime = 600; // 手机动态密码过期时间
        $this->assign('expiresTime', $this->expiresTime);
        $node_id = I("node_id", NULL);
        $id = I("id");
        // 通宝斋非标
        $tbz_node_id = I('tbz_node_id', NULL);
        $fb_node = C('fb_tongbaozhai.node_id');
        
        if (! empty($tbz_node_id) && in_array($tbz_node_id, $fb_node)) {
            session('tbz_node_id', $tbz_node_id);
        } else {
            if (! empty($node_id)) {
                session('tbz_node_id', null);
            }
            
            if (empty($tbz_node_id) && ! session('?tbz_node_id')) {
                session('tbz_node_id', null);
            }
        }
        
        if ($id != "") {
            session("id", $id);
            $this->assign('id', $id);
        }
        
        // 查询机构信息
        $node_model = M('tnode_info');
        $map = array(
            'node_id' => $node_id);
        $nodeInfo = $node_model->where($map)->find();
        
        $this->node_id = $node_id;
        $this->node_name = $nodeInfo['node_name'];
        $this->node_short_name = $nodeInfo['node_short_name'];
        $this->label_id = I('id');
        $this->assign('node_name', $nodeInfo['node_name']);
        $this->assign('node_short_name', $nodeInfo['node_short_name']);
        $this->assign('withdraw_minval', $this->withdraw_minval);
        $this->assign('label_id', $this->label_id);
    }
    
    // 入口
    public function index() {
        if (session('groupPhone') != "") {
            if (session('?tbz_node_id')) {
                header(
                    "location:index.php?g=Label&m=MyReturn&a=mygift&tbz_node_id=" .
                         session('tbz_node_id'));
            } else {
                header("location:index.php?g=Label&m=MyReturn&a=mygift");
            }
        }
        
        // 查询logo信息
        $node_model = M('tecshop_banner');
        $map = array(
            'node_id' => $this->node_id, 
            'ban_type' => 2);
        $logoInfo = $node_model->where($map)->find();
        
        session("login_title", $logoInfo['biaoti']);
        
        $this->assign('login_title', $logoInfo['biaoti']);
        $this->assign('img_url', $logoInfo['img_url']);
        $this->assign('node_id', $this->node_id);
        $this->display();
    }
    
    // 手机发送动态密码
    public function sendCheckCode() {
        
        // 图片校验码
        /*
         * $verify = I('post.verify',null,'mysql_real_escape_string');
         * if(session('verify') != md5($verify)) { $this->error("图片动态密码错误"); }
         */
        $phoneNo = I('post.phone', null);
        if (! check_str($phoneNo, 
            array(
                'null' => false, 
                'strtype' => 'mobile'), $error)) {
            $this->error("手机号{$error}");
        }
        // 发送频率验证
        $groupCheckCode = session('groupCheckCode');
        if (! empty($groupCheckCode) &&
             (time() - $groupCheckCode['add_time']) < $this->expiresTime) {
            $this->error('动态密码发送过于频繁!');
        }
        $num = mt_rand(1000, 9999);
        $exptime = $this->CodeexpiresTime / 60;
        $text = "您在{$this->node_short_name}商户的动态密码是：{$num}，有效期{$exptime}分钟。如非本人操作请忽略。";
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
        // dump($resp_array);exit;
        $ret_msg = $resp_array['NotifyRes']['Status'];
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
             $ret_msg['StatusCode'] != '0001')) {
            $this->error('发送失败');
        }
        $groupCheckCode = array(
            'number' => $num, 
            'add_time' => time(), 
            'phoneNo' => $phoneNo);
        session('groupCheckCode', $groupCheckCode);
        $this->success('动态密码已发送');
    }
    
    // 登录
    public function loginPhone() {
        $node_id = I('post.node_id', null, 'mysql_real_escape_string');
        $phoneNo = I('post.phone', null, 'mysql_real_escape_string');
        if (! check_str($phoneNo, 
            array(
                'null' => false, 
                'strtype' => 'mobile'), $error)) {
            $this->ajaxReturn(array(
                'type' => 'phone'), "手机号{$error}", 0);
        }
        // 手机动态密码
        $checkCode = I('post.check_code', null);
        if (! check_str($checkCode, array(
            'null' => false), $error)) {
            $this->ajaxReturn(array(
                'type' => 'pass'), "动态密码{$error}", 0);
        }
        $groupCheckCode = session('groupCheckCode');
        if (! empty($groupCheckCode) && $groupCheckCode['phoneNo'] != $phoneNo)
            $this->ajaxReturn(array(
                'type' => 'phone'), '手机号不正确', 0);
        if (! empty($groupCheckCode['number']) &&
             $groupCheckCode['number'] != $checkCode)
            $this->ajaxReturn(array(
                'type' => 'pass'), '动态密码不正确', 0);
        if (time() - $groupCheckCode['add_time'] > $this->CodeexpiresTime)
            $this->ajaxReturn(array(
                'type' => 'pass'), '动态密码已经过期', 0);
            
            // 非标操作
        $fb_node = C('fb_tongbaozhai.node_id');
        if (session('?tbz_node_id') && in_array(session('tbz_node_id'), 
            $fb_node)) {
            $wh = array(
                'node_id' => session('tbz_node_id'), 
                'phone_no' => $phoneNo);
            $query = M('tmember_info_tmp')->where($wh)->find();
            if (! $query) {
                $data = array(
                    'node_id' => session('tbz_node_id'), 
                    'phone_no' => $phoneNo, 
                    'add_time' => date('YmdHis'));
                $insert = M('tmember_info_tmp')->add($data);
                if (! $insert) {
                    $this->ajaxReturn(
                        array(
                            'type' => 'phone'), '登录失败！', 0);
                }
            }
        }
        // 记录session
        session('groupPhone', $phoneNo);
        $this->success('登录成功');
    }

    public function mygift() {
        // 查询未领取和已领取
        if (! session('?groupPhone')) {
            $this->error(
                '您还没有登录,<a href="' . U('Label/Label/index', 
                    array(
                        'id' => $this->label_id)) . '">返回</a>');
        }
        
        $phone_no = session('groupPhone');
        
        // 非标处理
        $userfb = session('tbz_node_id');
        $istbfb = false;
        if (! empty($userfb)) {
            $fb_node = C('fb_tongbaozhai.node_id');
            if (in_array($userfb, $fb_node)) {
                $istbfb = true;
            }
        }
        
        if ($istbfb === true) {
            $sumList = M('treturn_commission_trace')->field(
                'user_get_flag,sum(return_num) as returnnum')
                ->where(
                "phone_no='" . $phone_no .
                     "'  AND commission_type=3  and node_id ='" . $userfb . "' ")
                ->order("id desc")
                ->group('user_get_flag')
                ->select();
        } else {
            $sumList = M('treturn_commission_trace')->field(
                'return_status,sum(return_num) as returnnum')
                ->where(
                "phone_no='" . $phone_no .
                     "' AND (return_status=0 OR return_status=1) AND commission_type=3")
                ->order("id desc")
                ->group('return_status')
                ->select();
        }
        
        $remain = 0;
        $have = 0;
        
        if (! empty($sumList)) {
            foreach ($sumList as $k => $v) {
                // 非标
                if ($istbfb === true) {
                    if ($v['user_get_flag'] == '0') {
                        $remain = $remain + $v['returnnum'];
                    } else {
                        $have = $have + $v['returnnum'];
                    }
                } else {
                    if ($v['return_status'] == '0') {
                        $remain = $remain + $v['returnnum'];
                    } else {
                        $have = $have + $v['returnnum'];
                    }
                }
            }
        }
        
        // 查询列表数据
        if ($istbfb === true) {
            $detailList = M()->table("treturn_commission_trace a")->field(
                'a.*,c.name,t.commission_name')
                ->
            // ->join('tbatch_info b on a.b_id=b.id')
            join('tmarketing_info c on c.id=a.marketing_info_id')
                ->join(
                'treturn_commission_info t on t.id=a.return_commission_id')
                ->where(
                "a.phone_no='" . $phone_no .
                     "' AND (a.commission_type=3) and a.node_id='" . $userfb .
                     "'  ")
                ->order("a.id desc")
                ->select();
        } else {
            $detailList = M()->table("treturn_commission_trace a")->field(
                'a.*,c.name,t.commission_name')
                ->
            // ->join('tbatch_info b on a.b_id=b.id')
            join('tmarketing_info c on c.id=a.marketing_info_id')
                ->join(
                'treturn_commission_info t on t.id=a.return_commission_id')
                ->where(
                "a.phone_no='" . $phone_no .
                     "' AND (a.return_status=0 OR a.return_status=1) AND (a.commission_type=0 OR a.commission_type=3)")
                ->order("a.id desc")
                ->select();
        }
        
        // echo M('treturn_commission_trace a')->getLastSql();
        // 查询判断是否已经绑定支付宝
        
        if ($istbfb === true) {
            $bindcount = M('tmember_info_tmp')->where(
                "phone_no='" . $phone_no .
                     "' AND alipay_acount is not null and node_id='" . $userfb .
                     "' ")
                ->order("id desc")
                ->select();
        } else {
            $bindcount = M('tmember_info_tmp')->where(
                "phone_no='" . $phone_no . "' AND alipay_acount is not null ")
                ->order("id desc")
                ->select();
        }
        $bindpay = "";
        $bindname = "";
        if (! empty($bindcount)) {
            foreach ($bindcount as $k => $v) {
                if ($v['alipay_acount'] != "") {
                    $bindpay = $v['alipay_acount'];
                    $bindname = $v['alipay_name'];
                    break;
                }
            }
        }
        
        if (! empty($detailList)) {
            foreach ($detailList as $p => $kl) {
                
                // 营销活动关联条码
                if ($kl['commission_type'] != '3') {
                    $row = M('tbarcode_trace')->field('barcode_bmp')
                        ->where(
                        " trans_type='0001' AND status='0' and request_id='" .
                             $kl['send_request_id'] . "'")
                        ->find();
                    $detailList[$p]['barcode_bmp'] = $row['barcode_bmp'] ? 'data:image/png;base64,' .
                         base64_encode(
                            $this->_bar_resize(
                                base64_decode($row['barcode_bmp']), 'png')) : '';
                }
            }
        }
        // 支付宝转账费用
        $zfb_money = 0.00;
        $remain = (float) $remain;
        if ($remain > 0 && $remain < 20000) {
            $zfb_money = 0.5;
        } else if ($remain >= 20000 && $remain < 50000) {
            $zfb_money = 1.00;
        } else if ($remain >= 50000) {
            $zfb_money = 3.00;
        }
        
        $this->assign('istbfb', $istbfb);
        $this->assign('zfb_money', $zfb_money);
        $this->assign('bindpay', $bindpay);
        $this->assign('bindname', $bindname);
        $this->assign('remain', $remain);
        $this->assign('have', $have);
        $this->assign('detailList', $detailList);
        $this->display();
    }
    
    // 非标金额提领
    public function getMoney() {
        
        // 查询未领取和已领取
        if (! session('?groupPhone')) {
            $this->error('您还没有登录');
        }
        
        $phone_no = session('groupPhone');
        
        $userfb = session('tbz_node_id');
        if (! $userfb) {
            $this->error('您还没有登录');
        }
        // 非标判断
        $fb_node = C('fb_tongbaozhai.node_id');
        if (! in_array($userfb, $fb_node)) {
            $this->error('提领失败！');
        }
        
        // 支付宝账号
        $alipay_acount = M('tmember_info_tmp')->where(
            array(
                'node_id' => $userfb, 
                'phone_no' => $phone_no))->getField('alipay_acount');
        if (! $alipay_acount) {
            $this->error('未填写支付宝账号！');
        }
        
        if ($_POST) {
            M()->startTrans();
            $wh = " and node_id ='" . $userfb . "'  and user_get_flag = '0' ";
            $sumList = M('treturn_commission_trace')->field(
                'user_get_flag,sum(return_num) as returnnum')
                ->where(
                "phone_no='" . $phone_no . "' AND commission_type=3 {$wh}")
                ->order("id desc")
                ->group('user_get_flag')
                ->select();
            if (! $sumList) {
                M()->rollback();
                $this->error('未查询到返佣记录！');
            }
            
            $remain = (float) $sumList[0]['returnnum'];
            if ($remain <= 0.00) {
                M()->rollback();
                $this->error('提领失败！提领金额' . $sumList[0]['returnnum']);
            }
            $old_money = I('old_money');
            if ((float) $old_money != $old_money) {
                M()->rollback();
                $this->error('请重新确认！');
            }
            
            // 支付宝转账费用
            $zfb_money = 0.00;
            
            if ($remain > 0.00 && $remain < 20000.00) {
                $zfb_money = 0.5;
            } else if ($remain >= 20000.00 && $remain < 50000.00) {
                $zfb_money = 1.00;
            } else if ($remain >= 50000.00) {
                $zfb_money = 3.00;
            }
            
            // 实际提领金额
            $last_money = (float) ($remain - $zfb_money);
            if ($last_money <= 0) {
                M()->rollback();
                $this->error('提领金额小于手续费！');
            }
            
            $in_arr = array(
                'phone_no' => $phone_no, 
                'money_amount' => $sumList[0]['returnnum'], 
                'commission_charge' => $zfb_money, 
                'get_amount' => $last_money, 
                'alipay_acount' => $alipay_acount, 
                'add_time' => date('YmdHis'));
            $seq_id = M('tfb_tbz_get_trace')->add($in_arr);
            if (! $seq_id) {
                M()->rollback();
                $this->error('提领失败！');
            }
            
            $where = array(
                'node_id' => $userfb, 
                'phone_no' => $phone_no, 
                'commission_type' => '3', 
                'user_get_flag' => '0');
            $data = array(
                'user_get_flag' => '1', 
                'user_get_trace_id' => $seq_id, 
                'return_acount' => $alipay_acount);
            $isupdate = M('treturn_commission_trace')->where($where)->save(
                $data);
            if ($isupdate === false) {
                M()->rollback();
                $this->error('系统更新流水失败！');
            }
            M()->commit();
            $returl = "location:index.php?g=Label&m=MyReturn&a=mygift&tbz_node_id=" .
                 session("tbz_node_id");
            header($returl);
        }
    }

    public function bindalipay() {
        // 判断是否登录
        if (! session('?groupPhone')) {
            $this->error('您还没有登录');
        }
        $aliaccount = I("aliaccount");
        $alipay_name = I("alipay_name");
        if ($aliaccount == "") {
            $this->error('支付宝账号不能为空！');
        }
        if ($alipay_name == "") {
            $this->error('支付宝姓名不能为空！');
        }
        
        // 更新member_info
        // alipay_acount
        $where = array(
            "phone_no" => session('groupPhone'));
        $data = array(
            "alipay_acount" => $aliaccount, 
            "alipay_name" => $alipay_name);
        $upok = M("tmember_info_tmp")->where($where)->save($data);
        if ($upok === false) {
            $this->error('更新系统错误');
        } else {
            if (session('?tbz_node_id')) {
                $returl = "location:index.php?g=Label&m=MyReturn&a=mygift&tbz_node_id=" .
                     session("node_id");
            } else {
                $returl = "location:index.php?g=Label&m=MyReturn&a=mygift&node_id=" .
                     session("node_id");
            }
            if (IS_AJAX) {
                $this->success('支付宝账号绑定成功！');
            } else {
                header($returl);
            }
        }
    }

    public function _bar_resize($data, $other) {
        $im = $this->_img_resize($data, 3);
        if ($im !== false) {
            ob_start();
            switch ($other) {
                case 'gif':
                    imagegif($im);
                    break;
                case 'jpg':
                case 'jpeg':
                    imagejpeg($im);
                    break;
                case 'png':
                    imagepng($im);
                    break;
                case 'wbmp':
                    imagewbmp($im);
                    break;
                default:
                    return false;
                    break;
            }
            imagedestroy($im);
            $new_img = ob_get_contents();
            ob_end_clean();
            return $new_img;
        } else {
            return false;
        }
    }

    public function logout() {
        if (session('groupPhone') != "") {
            session('groupPhone', null);
        }
        $id = I("id");
        $id = empty($id) ? session('id') : $id;
        
        $url = "location:index.php?g=Label&m=MyReturn&a=index";
        
        header($url);
    }

    public function _img_resize($data, $fdbs) {
        // Resize
        $source = imagecreatefromstring($data);
        $s_white_x = 0; //
        $s_white_y = 0; //
        $s_w = imagesx($source); // 原图宽度
        $new_img_width = ($s_w) * $fdbs;
        $new_img_height = $new_img_width;
        
        // 新的偏移量
        $d_white_x = ($new_img_width - $s_w * $fdbs) / 2;
        $d_white_y = $d_white_x;
        
        // Load
        $thumb = imagecreate($new_img_width, $new_img_height);
        // $red = imagecolorallocate($thumb, 255, 255, 255);
        
        imagecopyresized($thumb, $source, $d_white_x, $d_white_y, $s_white_x, 
            $s_white_y, $s_w * $fdbs, $s_w * $fdbs, $s_w, $s_w);
        return $thumb;
    }
    
    // 输出信息页面
    protected function showMsg($info, $status, $id, $node_short_name) {
        $this->assign('id', $id);
        $this->assign('status', $status);
        $this->assign('info', $info);
        $this->assign('node_short_name', $node_short_name);
        $this->display('msg');
        exit();
    }

    public function gotoPay() {
        $order_id = I("order_id");
        if ($order_id == "") {
            $this->error("参数错误");
        }
        $payModel = A('Label/PayMent');
        $payModel->OrderPay($order_id);
        exit();
    }

    /**
     *
     * @param int $i 0 可提 1 累计 2 已提
     * @return mixed
     */
    public function _get_return_amount($i = 0) {
        $phone = session('groupPhone');
        $map = array(
            'phone_no' => $phone, 
            'commission_type' => '3');
        switch ($i) {
            case 0:
                // $map['return_status'] = array('in','0,1');
                $map['user_get_flag'] = '0';
                break;
            case 1:
                break;
            case 2:
                // $map['return_status'] = array('in','0,1');
                $map['user_get_flag'] = '1';
                break;
        }
        $amount = M('treturn_commission_trace')->where($map)->sum('return_num');
        return (float) $amount;
    }

    /**
     * 我的返佣，主页面
     */
    public function my_return() {
        // 登录判断
        if (! session('?groupPhone')) {
            $this->error(
                '您还没有登录,<a href="' . U('Label/Label/index', 
                    array(
                        'id' => $this->label_id)) . '">返回</a>');
        }
        
        $phone = session('groupPhone');
        $where = array(
            'phone_no' => $phone, 
            '_string' => "ifnull(alipay_acount, '') != ''");
        $member_info = M("tmember_info_tmp")->where($where)->find();
        $this->assign('bindpay', $member_info['alipay_acount']);
        $this->assign('bindname', $member_info['alipay_name']);
        
        $this->assign('remain_amount', $this->_get_return_amount(0));
        $this->assign('all_amount', $this->_get_return_amount(1));
        $this->assign('get_amount', $this->_get_return_amount(2));
        $this->display();
    }

    /**
     * 提现操作
     */
    public function withdraw() {
        if (! IS_POST) {
            $this->error('22');
        }
        // 查询未领取和已领取
        if (! session('?groupPhone')) {
            $this->error('您还没有登录');
        }
        
        $phone_no = session('groupPhone');
        
        // 支付宝账号
        $map = array(
            // 'node_id' => $userfb,
            'phone_no' => $phone_no, 
            'commission_type' => '3');
        $alipay_acount = M('tmember_info_tmp')->where($map)->getField(
            'alipay_acount');
        if (! $alipay_acount) {
            $this->error('未填写支付宝账号！');
        }
        
        M()->startTrans();
        $map = array(
            'phone_no' => $phone_no, 
            'return_status' => array(
                'in', 
                '0'), 
            'commission_type' => '3', 
            'user_get_flag' => '0');
        $info = M('treturn_commission_trace')->where($map)
            ->field('sum(return_num) remain, max(id) max_id')
            ->find();
        $remain = $info['remain'];
        $max_trace_id = $info['max_id'];
        
        if ($remain < $this->withdraw_minval) {
            $this->error(
                '您当前可提现余额为：' . $remain . ',不足' . $this->withdraw_minval .
                     '元，不能提现！' . print_r($info, true));
        }
        
        /*
         * $old_money = I('old_money'); if ((float)$old_money != $remain) {
         * M()->rollback(); $this->error('请重新确认！'); }
         */
        
        // 支付宝转账费用
        $zfb_money = 0.00;
        
        if ($remain > 0.00 && $remain < 20000.00) {
            $zfb_money = 0.5;
        } else if ($remain >= 20000.00 && $remain < 50000.00) {
            $zfb_money = 1.00;
        } else if ($remain >= 50000.00) {
            $zfb_money = 3.00;
        }
        
        // 实际提领金额
        $last_money = (float) ($remain - $zfb_money);
        if ($last_money <= 0) {
            M()->rollback();
            $this->error('提领金额小于手续费！');
        }
        
        $in_arr = array(
            'phone_no' => $phone_no, 
            'money_amount' => $remain, 
            'commission_charge' => $zfb_money, 
            'get_amount' => $last_money, 
            'alipay_acount' => $alipay_acount, 
            'add_time' => date('YmdHis'));
        $seq_id = M('treturn_get_trace')->add($in_arr);
        if (! $seq_id) {
            M()->rollback();
            $this->error('提领失败！');
        }
        
        $where = array(
            'phone_no' => $phone_no, 
            'commission_type' => '3', 
            'user_get_flag' => '0', 
            'id' => array(
                'elt', 
                $max_trace_id));
        $data = array(
            'user_get_flag' => '1', 
            'user_get_trace_id' => $seq_id, 
            'return_account' => $alipay_acount);
        $isupdate = M('treturn_commission_trace')->where($where)->save($data);
        if ($isupdate === false) {
            M()->rollback();
            $this->error('系统更新流水失败！');
        }
        M()->commit();
        $this->error('提现成功！');
    }

    /**
     * 我的返佣凭证
     */
    public function ajax_return_code() {
        // 登录判断
        if (! session('?groupPhone')) {
            $this->error(
                '您还没有登录,<a href="' . U('Label/Label/index', 
                    array(
                        'id' => $this->label_id)) . '">返回</a>');
        }
        
        $phone_no = session('groupPhone');
        $map = array(
            'a.phone_no' => $phone_no, 
            'a.commission_type' => '0', 
            'e.trans_type' => '0001', 
            'e.status' => '0', 
            '_string' => "a.return_commission_id = b.id and b.node_id = c.node_id and a.send_request_id = e.request_id and ifnull(a.send_request_id, '') !='' ");
        
        $p = I('p', 1, 'intval');
        $count = M()->table(
            'treturn_commission_trace a, treturn_commission_info b, tnode_info c, tbatch_info d, tbarcode_trace e')
            ->where($map)
            ->count();
        $sql1 = M()->_sql();
        import('ORG.Util.Page'); // 导入分页类
        $page_size = 10;
        $Page = new Page($count, $page_size);
        if ($_GET['p'] > ceil($count / $page_size) && $this->isAjax()) {
            $this->success('success', null, 
                array(
                    'list' => array(), 
                    'nextp' => 0));
            exit();
        }
        
        $map['_string'] .= " and a.b_id = d.id and a.send_request_id = e.request_id and ifnull(a.send_request_id, '') !=''";
        $list = M()->table(
            'treturn_commission_trace a, treturn_commission_info b, tnode_info c, tbatch_info d, tbarcode_trace e')
            ->where($map)
            ->field(
            'a.id, a.return_add_time, a.return_num, a.return_status, a.send_request_id, b.commission_name, c.node_short_name,d.batch_short_name, e.use_status')
            ->order('a.id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        if ($list) {
            foreach ($list as &$row) {
                $row['return_add_time'] = dateformat($row['return_add_time'], 
                    'Y-m-d H:i');
                $row['return_num'] = (float) $row['return_num'];
            }
        }
        $nextp = $p + 1;
        if (count($list) < $page_size || $count == $p * $page_size) {
            $nextp = 0;
        }
        $this->success('success', '', 
            array(
                'list' => $list, 
                'count' => $count, 
                'nextp' => $nextp, 
                'sql' => M()->_sql()));
    }

    /**
     * 获取单条凭证详情
     */
    public function ajax_return_code_detail() {
        // 登录判断
        if (! session('?groupPhone')) {
            $this->error(
                '您还没有登录,<a href="' . U('Label/Label/index', 
                    array(
                        'id' => $this->label_id)) . '">返回</a>');
        }
        
        $phone_no = session('groupPhone');
        
        $map = array(
            'a.phone_no' => $phone_no, 
            'a.id' => I('id', 0, 'intval'), 
            'a.commission_type' => '0', 
            'e.trans_type' => '0001', 
            'e.status' => '0', 
            '_string' => 'a.node_id = c.node_id and a.b_id = d.id and a.send_request_id = e.request_id');
        
        $info = M()->table(
            'treturn_commission_trace a, tnode_info c, tbatch_info d, tbarcode_trace e')
            ->where($map)
            ->field(
            'c.node_short_name, d.batch_short_name, d.batch_name as goods_name, e.*')
            ->find();
        if (! $info) {
            $this->error('未找到凭证信息！' . M()->_sql());
        }
        
        $info['barcode_bmp'] = $info['barcode_bmp'] ? 'data:image/png;base64,' .
             base64_encode(
                $this->_bar_resize(base64_decode($info['barcode_bmp']), 'png')) : '';
        $info['begin_time'] = dateformat($info['begin_time'], 'Y-m-d H:i');
        $info['end_time'] = dateformat($info['end_time'], 'Y-m-d H:i');
        $this->success('success', '', 
            array(
                'detail' => $info));
    }

    /**
     * 绑定支付宝账号
     */
    public function bind_zfbaccount() {
        $this->display();
    }

    /**
     * 现金返佣详情
     */
    public function ajax_return_money() {
        // 登录判断
        if (! session('?groupPhone')) {
            $this->error(
                '您还没有登录,<a href="' . U('Label/Label/index', 
                    array(
                        'id' => $this->label_id)) . '">返回</a>');
        }
        
        $phone_no = session('groupPhone');
        $map = array(
            'a.phone_no' => $phone_no, 
            'a.commission_type' => '3', 
            '_string' => 'a.return_commission_id = b.id and b.node_id = c.node_id');
        
        $p = I('p', 1, 'intval');
        $count = M()->table(
            'treturn_commission_trace a, treturn_commission_info b, tnode_info c')
            ->where($map)
            ->count();
        
        import('ORG.Util.Page'); // 导入分页类
        $page_size = 10;
        $Page = new Page($count, $page_size);
        if ($_GET['p'] > ceil($count / $page_size) && $this->isAjax()) {
            $this->success('success', null, 
                array(
                    'list' => array(), 
                    'nextp' => 0));
            exit();
        }
        
        $list = M()->table(
            'treturn_commission_trace a, treturn_commission_info b, tnode_info c')
            ->where($map)
            ->field(
            "a.return_add_time, a.return_num, case when a.user_get_flag = '0' then 0 when a.user_get_flag = '1' and a.return_status = '0' then 1 when a.user_get_flag = '1' and a.return_status = '1' then 2 end status , b.commission_name, c.node_short_name")
            ->order('a.id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        $status_arr = array(
            '未领取', 
            '已领取', 
            '已下发');
        if ($list) {
            foreach ($list as &$row) {
                $row['return_add_time'] = dateformat($row['return_add_time'], 
                    'Y-m-d H:i');
                $row['return_num'] = (float) $row['return_num'];
                $row['status'] = (int) $row['status'];
                $row['status_txt'] = $status_arr[$row['status']];
            }
        }
        
        $nextp = $p + 1;
        if (count($list) < $page_size || $count == $p * $page_size) {
            $nextp = 0;
        }
        $this->success('success', '', 
            array(
                'list' => $list, 
                'count' => $count, 
                'nextp' => $nextp));
    }
}
