<?php

class SpellingAction extends MyBaseAction {

    public $wxid;

    public $expiresTime = 600;

    public $jishu = 30;

    public function _initialize() {
        // edit by tr
        $this->error(
            array(
                'errorImg' => '__PUBLIC__/Label/Image/waperro1.png', 
                'errorTxt' => '该活动已取消！', 
                'errorSoftTxt' => '活动已经结束啦~'));
        exit();
        
        $useragent = $_SERVER['HTTP_USER_AGENT'];
        if (strpos($useragent, 'MicroMessenger') === false &&
             strpos($useragent, 'Windows Phone') === false) {
            $this->error(
                array(
                    'errorImg' => '__PUBLIC__/Label/Image/waperro7.png', 
                    'errorTxt' => '请使用微信浏览器！', 
                    'errorSoftTxt' => '亲，这个页面要用微信浏览器打开哦~'));
        }
        // session('wxid',49);
        parent::_initialize();
        
        if (! session('?spellingwxid')) {
            $from_user_id = I('from_user_id', NULL);
            $dutys_id_ = I('duty_id');
            $this->redirect(
                U('Label/SpellingWeixinLogin/index', 
                    array(
                        'id' => $this->id, 
                        'from_user_id' => $from_user_id, 
                        'duty_id' => $dutys_id_)));
        }
        
        $this->wxid = session('spellingwxid');
        // $this->wxid =56;
        $from_user_id = I('from_user_id');
        $dutys_id = I('duty_id');
        
        if ($from_user_id != '' && $this->wxid != $from_user_id) {
            session('spellingfrom_user_id', $from_user_id);
        }
        
        $duty_info = M('wx_spelling_duty')->where(
            array(
                'wxid' => $this->wxid, 
                'batch_id' => $this->batch_id))->find();
        $batch_info = M('tmarketing_info')->field(
            'name,wap_info,defined_one_name,defined_two_name,start_time,end_time,log_img')
            ->where(array(
            'id' => $this->batch_id))
            ->find();
        
        $hanzi = $batch_info['defined_one_name'];
        $harr_ = explode('-', $hanzi);
        $harr = array_filter($harr_);
        $this->HANZI_DATA = $harr;
        
        $this->range = $batch_info['defined_two_name'];
        
        $this->start_time = $batch_info['start_time'];
        $this->end_time = $batch_info['end_time'];
        $this->log_img = $batch_info['log_img'];
        if (empty($duty_info)) {
            if ($_SERVER['HTTP_USER_AGENT'] != 'Mozilla/4.0') {
                $duty_id = $this->add_duty($dutys_id, 
                    $batch_info['defined_one_name']);
            }
        } else {
            $duty_id = $duty_info['id'];
        }
        // 当前任务ID
        $this->dutys_ID = $duty_id;
        
        if ($dutys_id != '' && $dutys_id != $duty_id) {
            // 给来源得字
            $dezi = $this->isDeZi($dutys_id);
            if ($dezi === false) {
                $haodezi = $this->stehinge($dutys_id);
            }
        }
        
        if ($dutys_id == '' || $dutys_id != $duty_id) {
            $shareUrl = U('Label/Spelling/index', 
                array(
                    'id' => $this->id, 
                    'from_user_id' => $this->wxid, 
                    'duty_id' => $duty_id), '', '', TRUE);
            redirect($shareUrl);
        }
        
        $this->assign('id', $this->id);
        $this->assign('wxid', $this->wxid);
        $this->assign('duty_id', $duty_id);
        $this->assign('log_img', $this->log_img);
    }

    public function index() {
        if ($this->batch_type != '36') {
            $this->error(
                array(
                    'errorImg' => '__PUBLIC__/Label/Image/waperro5.png', 
                    'errorTxt' => '错误访问！', 
                    'errorSoftTxt' => '你的访问地址出错啦~'));
        }
        import('@.Vendor.DataStat');
        $opt = new DataStat($this->id, $this->full_id);
        $opt->UpdateRecord();
        
        $jp = $this->searchJp();
        $this->assign('jp', $jp);
        $this->assign('start_time', $this->start_time);
        $this->assign('end_time', $this->end_time);
        $this->display();
    }

    protected function searchJp() {
        $jp_sql = "SELECT b.id as cid, b.name,b.score ,c.batch_name,c.batch_img,c.remain_num FROM tcj_batch a 
				LEFT JOIN tcj_cate b ON a.cj_cate_id = b.id 
				LEFT JOIN tbatch_info c ON a.b_id=c.id
				WHERE  a.status =1  AND a.batch_id ='" .
             $this->batch_id . "' order by b.id";
        $jp_arr = M()->query($jp_sql);
        return $jp_arr;
    }

    /**
     * @ 显示的页面
     */
    public function show() {
        $is_dezi = M('wx_spelling_running')->where(
            array(
                'dutyid' => $this->dutys_ID, 
                'wxid' => $this->wxid))->select();
        $done = $this->is_done();
        
        if ($done['num'] == '0' && empty($done['info'])) {
            $this->jiangli();
            exit();
        }
        if ($is_dezi) {
            $this->show_off();
            exit();
        } else {
            $this->show_ago();
            exit();
        }
    }

    private function show_ago() {
        $this->assign('m_info', $this->HANZI_DATA);
        $this->display('show_ago');
    }

    private function show_off() {
        $done = $this->is_done();
        $number = $this->ping_zi_number();
        $data = $this->HANZI_DATA;
        $data = array_values($data);
        $this->assign('done', $done['num']);
        $this->assign('keys', $done['key']);
        $this->assign('number', $number);
        $this->assign('m_info', $data);
        $this->display('show_off');
    }

    private function jiangli() {
        $done = $this->is_done();
        $data = $this->HANZI_DATA;
        $res = $this->is_dejiang();
        $number = $this->ping_zi_number();
        $this->assign('res', $res);
        $this->assign('done', $done['num']);
        $this->assign('number', $number);
        $this->assign('m_info', $data);
        $this->assign('dem_info', $done['info']);
        $this->display('jiangli');
    }

    /**
     * @当前微信用户是否为$dutyId任务邻过字了 @参数：任务ID
     */
    protected function isDeZi($dutyId) {
        $is_dezi = M('wx_spelling_running')->where(
            array(
                'dutyid' => $dutyId, 
                'wxid' => $this->wxid))->find();
        if ($is_dezi) {
            return $is_dezi['hanzi'];
        } else {
            return false;
        }
    }

    public function gopingzi_() {
        $dezi = $this->isDeZi($this->dutys_ID);
        if ($dezi) {
            // 领过字了
            $this->assign('mydezi', $dezi);
            $this->assign('haodezi', null);
            $this->display();
            exit();
        }
        
        // if(session('?dutys_id')){
        // $dutyID=session('dutys_id');
        // $mydezi=$this->stehinge($this->dutys_ID);
        // $haodezi=$this->stehinge($dutyID);
        // //$arr=$this->_dezi($dutyID);
        // $this->assign('mydezi',$mydezi);
        // $this->assign('haodezi',$haodezi);
        // $this->display();exit;
        
        // }else{
        $mydezi = $this->stehinge($this->dutys_ID);
        // $zi=$this->my_dezi();
        $this->assign('mydezi', $mydezi);
        $this->assign('haodezi', null);
        $this->display();
        exit();
        // }
    }

    public function my_dezi() {
        $res = $this->is_one($this->dutys_ID);
        if ($res === true) {
            $outacome = $this->stehinge_one($this->dutys_ID);
        } else {
            $outacome = $this->stehinge_two($this->dutys_ID);
        }
        
        return $outacome;
    }

    public function _dezi($dutyID) {
        $res = $this->is_one($dutyID);
        if ($res === true) {
            $outacome1 = $this->stehinge_one($dutyID);
        } else {
            $outacome1 = $this->stehinge_two($dutyID);
        }
        
        $res2 = $this->is_one($this->dutys_ID);
        if ($res2 === true) {
            $outacome2 = $this->stehinge_one($this->dutys_ID);
        } else {
            $outacome2 = $this->stehinge_two($this->dutys_ID);
        }
        
        return array(
            'mydezi' => $outacome2, 
            'haodezi' => $outacome1);
    }

    /**
     * @创建任务
     */
    protected function add_duty($from_user_id, $hanzi) {
        $arr = array(
            'wxid' => $this->wxid, 
            'batch_id' => $this->batch_id, 
            'add_time' => date('YmdHis'), 
            'step' => '0', 
            'form_user_id' => $from_user_id);
        
        $harr_ = explode('-', $hanzi);
        $harr = array_filter($harr_);
        $count = array_count_values($harr);
        foreach ($count as $k => $v) {
            if ($v == 1) {
                $hinge = $k;
                break;
            }
        }
        $arr['hinge'] = $hinge;
        $add = M('wx_spelling_duty')->add($arr);
        // $this->add_stehinge($add);
        return $add;
    }

    public function add_stehinge($add) {
        $hanZi = $this->HANZI_DATA;
        $chance = $this->range;
        $duty_info = M('wx_spelling_duty')->where(
            array(
                'batch_id' => $this->batch_id, 
                'id' => $add))->find();
        $hanZi_len = count($hanZi);
        $hanZi_info = array();
        
        foreach ($hanZi as $k => $v) {
            if ($v == $duty_info['hinge'])
                continue;
            for ($i = 1; $i <= $this->jishu; $i ++) {
                $hanZi_info[] = $v;
            }
        }
        
        if ($chance == '4')
            $range1 = 1;
        if ($chance == '3')
            $range1 = 3;
        if ($chance == '2')
            $range1 = 5;
        if ($chance == '1')
            $range1 = 8;
        if ($chance == '0')
            $range1 = 10;
        for ($i = 1; $i <= $range1; $i ++) {
            $hanZi_info[] = $duty_info['hinge'];
        }
        return $hanZi_info;
    }
    // 得字的任务ID
    public function stehinge($dutyID) {
        $hanziInfo = $this->add_stehinge($dutyID);
        $count = count($hanziInfo);
        $deZi = $hanziInfo[rand(0, $count - 1)];
        
        $data_ = $this->HANZI_DATA;
        $data_ = array_values($data_);
        foreach ($data_ as $keys => $value) {
            if ($value == $deZi) {
                $hanzi_key = $keys;
                break;
            }
        }
        $array = array(
            'dutyid' => $dutyID, 
            'wxid' => $this->wxid, 
            'hanzi' => $deZi, 
            'hanzi_key' => $hanzi_key, 
            'add_time' => date('YmdHis'));
        $res = M('wx_spelling_running')->add($array);
        if (! $res) {
            return false;
        }
        return $deZi;
    }
    
    // 首次得字
    protected function stehinge_one($dutyID) {
        $duty_info = M('wx_spelling_duty')->where(
            array(
                'batch_id' => $this->batch_id, 
                'id' => $dutyID))->find();
        $chance = $this->range;
        if ($chance <= rand(0, 4)) {
            $set_zi = $duty_info['hinge'];
        } else {
            // $set_zi=$this->HANZI_DATA[];
            foreach ($this->HANZI_DATA as $k => $v) {
                if ($v == $duty_info['hinge']) {
                    $key = $k;
                    break;
                }
            }
            $data = $this->HANZI_DATA;
            unset($data[$key]);
            shuffle($data);
            $set_zi = $data[rand(0, count($data) - 1)];
        }
        $data_ = $this->HANZI_DATA;
        $data_ = array_values($data_);
        foreach ($data_ as $keys => $value) {
            if ($value == $set_zi) {
                $hanzi_key = $keys;
                break;
            }
        }
        
        $array = array(
            'dutyid' => $dutyID, 
            'wxid' => $this->wxid, 
            'hanzi' => $set_zi, 
            'hanzi_key' => $hanzi_key, 
            'add_time' => date('YmdHis'));
        $res = M('wx_spelling_running')->add($array);
        if (! $res) {
            return false;
        }
        return $set_zi;
    }
    
    // 第二次得字
    protected function stehinge_two($dutyID) {
        $isde_hinge = $this->isde_hinge($dutyID);
        if ($isde_hinge === true) {
            $data = $this->HANZI_DATA;
            shuffle($data);
            $set_zi = $data[rand(0, count($data) - 1)];
            $data_ = $this->HANZI_DATA;
            $data_ = array_values($data_);
            foreach ($data_ as $keys => $value) {
                if ($value == $set_zi) {
                    $hanzi_key = $keys;
                    break;
                }
            }
            $array = array(
                'dutyid' => $dutyID, 
                'wxid' => $this->wxid, 
                'hanzi_key' => $hanzi_key, 
                'hanzi' => $set_zi, 
                'add_time' => date('YmdHis'));
            $res = M('wx_spelling_running')->add($array);
            if (! $res) {
                return false;
            }
            return $set_zi;
        } else {
            $one_dezi = $this->stehinge_one($dutyID);
            return $one_dezi;
        }
    }

    /**
     * @ 是否首次得字 @ 参数：任务ID
     */
    protected function is_one($dutyID) {
        $duty_ = M('wx_spelling_duty')->where(
            array(
                'id' => $dutyID))->find();
        
        // if(!$duty_) return false;
        
        $dezi = M('wx_spelling_running')->where(
            array(
                'dutyid' => $dutyID))->find();
        if ($dezi) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @ 关键字是否已经得到 @ 参数：任务ID
     */
    protected function isde_hinge($dutyID) {
        $duty_ = M('wx_spelling_duty')->where(
            array(
                'id' => $dutyID))->find();
        
        if (! $duty_)
            return false;
        
        $dezi = M('wx_spelling_running')->where(
            array(
                'dutyid' => $dutyID))->select();
        
        $array = array();
        if ($dezi) {
            foreach ($dezi as $k => $v) {
                $array[] = $v['hanzi'];
            }
        }
        if (in_array($duty['hinge'], $array)) {
            return true;
        } else {
            return false;
        }
    }
    
    // 任务来源
    protected function form_() {
        $dutys_id = session('dutys_id');
        $data_form = M('wx_spelling_duty')->where(
            array(
                'id' => $dutys_id))->find();
        if ($data_form) {
            if ($data_form['form_user_id'] != $this->wxid) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * @ 我的拼字进程
     */
    public function ping_zi_jincheng() {
        $done = $this->is_done();
        $arr = array(
            'R.dutyid' => $this->dutys_ID);
        // '_string'=>"R.wxid != $this->wxid",
        
        $jincheng = M()->table('wx_spelling_running R')
            ->field('R.hanzi,R.add_time,U.nickname,U.headimgurl,R.wxid')
            ->join('wx_spelling_user U ON R.wxid=U.id')
            ->where($arr)
            ->select();
        
        $this->assign('done', $done['num']);
        $this->assign('dem_info', $done['info']);
        $this->assign('jincheng', $jincheng);
        $this->assign('wxid', $this->wxid);
        $this->display();
    }

    /**
     * @ 有多少好友帮我领字
     */
    protected function ping_zi_number() {
        $arr = array(
            'dutyid' => $this->dutys_ID, 
            '_string' => "wxid != $this->wxid");
        
        $number = M()->table('wx_spelling_running')
            ->where($arr)
            ->count();
        
        return $number;
    }

    /**
     * @ 完成进度
     */
    protected function is_done() {
        // 拼字配置
        $hanzi_ago = $this->HANZI_DATA;
        
        $dexi_off = array();
        $dexi_off_key = array();
        
        $dezi = M('wx_spelling_running')->where(
            array(
                'dutyid' => $this->dutys_ID))->select();
        
        if (! $dezi)
            return count($hanzi_ago);
        foreach ($dezi as $key => $value) {
            $dexi_off[] = $value['hanzi'];
            $dexi_off_key[] = $value['hanzi_key'];
        }
        
        $repeat = array_count_values($hanzi_ago);
        $dexi_off_ = array_count_values($dexi_off);
        
        if (in_array('2', $repeat)) {
            $num = array();
            foreach ($repeat as $k => $v) {
                if (isset($dexi_off_[$k])) {
                    if (($v - $dexi_off_[$k]) > 0) {
                        $num[] = $k;
                    }
                } else {
                    $num[] = $k;
                    if ($v == 2) {
                        $num[] = $k;
                    }
                }
            }
            
            return array(
                'num' => count($num), 
                'info' => $num, 
                'key' => $dexi_off_key);
        } else {
            $diff = array_diff($hanzi_ago, $dexi_off);
            return array(
                'num' => count($diff), 
                'info' => $diff, 
                'key' => $dexi_off_key);
        }
    }

    /**
     * @ 发送动态验证玛
     */
    public function sendCheckCode() {
        $phoneNo = I('post.phone', null);
        if (! check_str($phoneNo, 
            array(
                'null' => false, 
                'strtype' => 'mobile'), $error)) {
            $this->error("手机号{$error}");
        }
        
        $groupCheckCode = session('groupCheckCode');
        if (! empty($groupCheckCode) &&
             (time() - $groupCheckCode['add_time']) < $this->expiresTime) {
            $this->error('动态密码发送过于频繁!');
        }
        $num = mt_rand(1000, 9999);
        $exptime = $this->expiresTime / 60;
        $text = "您本次的动态验证码是：{$num}，有效期{$exptime}分钟。如非本人操作请忽略。";
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
        $groupCheckCode = array(
            'number' => $num, 
            'add_time' => time(), 
            'phoneNo' => $phoneNo);
        session('groupCheckCode', $groupCheckCode);
        $this->success('动态密码已发送');
    }

    public function dejiang() {
        $overdue = $this->checkDate();
        
        if ($overdue === false)
            $this->error("该活动不在有效期之内！");
        
        $done = $this->is_done();
        if ($done['num'] != '0')
            $this->error("非法操作或系统错误！");
        
        $res = $this->is_dejiang();
        if ($res === true)
            $this->error("奖品已领取!!!");
        
        if ($this->isPost()) {
            
            if ($overdue === false)
                exit(
                    json_encode(
                        array(
                            'code' => '1', 
                            'code_text' => '活动已过期')));
            if ($res === true)
                exit(
                    json_encode(
                        array(
                            'code' => '1', 
                            'code_text' => '奖品已领取')));
            $data = I('post.');
            
            if (! is_numeric(trim($data['iphone'])) ||
                 strlen(trim($data['iphone'])) != '11') {
                exit(
                    json_encode(
                        array(
                            'code' => '1', 
                            'code_text' => '手机号格式不对')));
            }
            
            if ($data['codenumber'] == '') {
                exit(
                    json_encode(
                        array(
                            'code' => '1', 
                            'code_text' => '验证码不能为空')));
            }
            
            $phoneCheckCode = session('groupCheckCode');
            
            if ($phoneCheckCode['number'] != $data['codenumber'] ||
                 $phoneCheckCode['phoneNo'] != $data['iphone'])
                exit(
                    json_encode(
                        array(
                            'code' => '1', 
                            'code_text' => '验证码错误')));
            if (time() - $phoneCheckCode['add_time'] > $this->expiresTime)
                exit(
                    json_encode(
                        array(
                            'code' => '1', 
                            'code_text' => '验证码过期了')));
            
            import('@.Vendor.ChouJiang');
            $choujiang = new ChouJiang($this->id, $data['iphone'], 
                $this->full_id, '', '');
            $resp = $choujiang->send_code();
            if ($resp['resp_id'] == '0000') {
                
                $duty_info = M('wx_spelling_duty')->where(
                    array(
                        'wxid' => $this->wxid, 
                        'batch_id' => $this->batch_id))->save(
                    array(
                        'step' => '1'));
                
                $res = $this->ini_member($data['iphone']);
                
                exit(
                    json_encode(
                        array(
                            'code' => '0', 
                            'code_text' => '成功召唤奖品！请注意查看手机。')));
            } else {
                if ($resp['resp_id'] == '1002') {
                    exit(
                        json_encode(
                            array(
                                'code' => '1', 
                                'code_text' => '今日奖品已被抢完，大侠明日请早！')));
                } else if ($resp['resp_id'] == '1001') {
                    exit(
                        json_encode(
                            array(
                                'code' => '1', 
                                'code_text' => '所有奖品已被抢完。')));
                } else {
                    exit(
                        json_encode(
                            array(
                                'code' => '1', 
                                'code_text' => '领取奖品失败！请确保信息正确后，与客服联系。')));
                }
            }
        }
        
        $this->display();
    }

    public function is_dejiang() {
        $duty_info = M('wx_spelling_duty')->where(
            array(
                'wxid' => $this->wxid, 
                'batch_id' => $this->batch_id))->find();
        if ($duty_info['step'] == '1') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @ 添加粉丝
     */
    public function ini_member($number) {
        $memberModel = M('tmember_info_tmp');
        $arr = array(
            'name' => '', 
            'node_id' => $this->node_id, 
            'phone_no' => $number, 
            'sex' => '1', 
            'status' => '0', 
            'add_time' => date('YmdHis'), 
            'channel_id' => $this->channel_id, 
            'batch_id' => $this->batch_id, 
            'join_num' => '1');
        $res = $memberModel->add($arr);
        
        if ($res) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @ 测试添加粉丝
     */
    public function test_ini() {
        $iphone = I('iphone');
        $res = $this->ini_member($iphone);
        dump($res);
    }

    /**
     * @ 测试完成功任务
     */
    public function test() {
        for ($i = 1; $i < 2000; $i ++) {
            $mydezi = $this->stehinge($this->dutys_ID);
            $done = $this->is_done();
            if ($done['num'] == '0' && empty($done['info'])) {
                echo 'Hi,完成进度！共花费了' . $i . '次';
                break;
            }
        }
    }
}