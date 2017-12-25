<?php
// 抽奖
class CjNewAction extends CjAction {

    public function _initialize() {
        if (ACTION_NAME == 'getCjResult') {
            return;
        }
        parent::_initialize();
        $this->_checkUser(false);
    }

    /*
     * 以异步队列方式进行抽奖
     */
    public function submitQueue() {
        $id = $this->id;
        $overdue = $this->checkDate();
        // 是否抽奖
        $query_arr = $this->marketInfo;
        if ($query_arr['is_cj'] != '1')
            $this->ajaxReturn("error", "未查询到抽奖活动！", 0);
        
        if (! $this->isPost()) {
            $this->ajaxReturn("error", "非法提交！", 0);
        }
        $mobile = I('post.mobile');
        $pay_token = I('pay_token', null);
        $cj_check_flag = I('post.cj_check_flag');
        $check_code = I('post.check_code');
        
        if (empty($id)) {
            $this->ajaxReturn("error", "错误的请求！", 0);
        }
        // 如果参加方式是微信号
        if ($this->marketInfo['join_mode'] == '1') {
            if (empty($this->wxSess['openid'])) {
                $this->ajaxReturn("error", "请在微信中参加", 0);
            }
        }  // 手机号
else {
            if (! is_numeric(trim($mobile)) || strlen(trim($mobile)) != '11') {
                $this->ajaxReturn("error", "请正确填写手机号！", 0);
            }
            $this->_userCookieMobile($mobile);
            
            // 二维码名片无需验证码
            if ($id != C('VCARD_ACTIVITY_NUMBER')) {
                if (session('verify_cj') != md5(I('post.verify'))) {
                    $this->ajaxReturn("error", "验证码错误！", 0);
                }
            }
        }
        
        session('verify_cj', null);
        
        $is_update = false;
        if ($cj_check_flag == '1') {
            if ($check_code == '') {
                $this->ajaxReturn("error", "错误的参与码！", 0);
            }
            
            // 校验是否存在
            $checkm = M('tcode_verify');
            $cmap = array(
                'batch_id' => $this->batch_id, 
                'batch_type' => $this->batch_type, 
                'status' => '0', 
                'verify_code' => strtolower($check_code));
            $query = $checkm->where($cmap)->find();
            if ($query) {
                $is_update = true;
            } else {
                $this->ajaxReturn("error", "错误的参与码！", 0);
            }
        }
        import('@.Vendor.ChouJiang');
        $other = array();
        // 如果微信登录过
        // 校验使用的用户
        $this->_checkUser();
        $wxUserInfo = $this->getwxUserInfo();
        if ($wxUserInfo) {
            $other = array(
                'wx_open_id' => $wxUserInfo['openid'], 
                'pay_token'=> $pay_token,
                'wx_nick' => $wxUserInfo['nickname']);
        }
        $choujiang = new ChouJiang($id, $mobile, $this->full_id, null, $other);
        $resp = $choujiang->sendCodeQueue();
        log_write('sendCodeQueue()' . print_r($resp, true));
        if ($resp['resp_id'] != '0000') {
            $this->responseJson(- 1, $resp['resp_str']);
            return;
        }
        if ($is_update === true) {
            $checkm->where(array(
                'id' => $query['id']))->save(
                array(
                    'status' => '1'));
        }
        $respData = $resp['data'];
        $data = array(
            'msgid' => $respData['key'], 
            'url' => U('getCjResult', 
                array(
                    'key' => $respData['key'])));
        $this->responseJson('1001', 'success', $data);
    }
    
    // 根据抽奖码发送奖品(手机号)
    public function getPrize() {
        $cj_code = I('cj_code');
        $phone = I('phone');
        $activityID = I('id');
        if (! $phone) {
            $this->error("手机号不能为空");
        }
        if (! $cj_code) {
            $this->error("系统正忙");
        }
        // 校验一下中奖码是否正确
        $tmpCjData = session('_TmpChouJian_');
        log_write(
            '开始领奖:' . $cj_code . ', phone_no:' . $phone . ',' .
                 print_r($tmpCjData, true));
        if (! $tmpCjData || $tmpCjData['cj_code'] != $cj_code) {
            log_write('领奖失败：' . $cj_code . ' ' . print_r($tmpCjData, true));
            $this->error("领奖失败，中奖码无效");
        }
        $cj_trace_id = $tmpCjData['cj_trace_id'];
        $request_id = $tmpCjData['request_id'];
        // 修改数据库中的手机号字段，并且调用重发接口
        $result = M('tcj_trace')->where(
            array(
                'id' => $cj_trace_id))->save(
            array(
                'send_mobile' => $phone));
        // 修改发码表的字段
        $result = M('tbarcode_trace')->where(
            array(
                'request_id' => $request_id))->save(
            array(
                'phone_no' => $phone));
        
        // 然后调用重发接口
        import("@.Vendor.CjInterface");
        $req = new CjInterface();
        $result = $req->cj_resend(
            array(
                'request_id' => $request_id, 
                'node_id' => $this->node_id, 
                'user_id' => '00000000'));
        if (! $result || $result['resp_id'] != '0000') {
            log_write('领奖失败：' . $result['resp_desc'], 'cj_resend');
            $this->error("领奖失败");
        } else if ($activityID == C('VCARD_ACTIVITY_NUMBER')) {
            M('tvisiting_card')->where(array(
                'phone_no'))->save(
                array(
                    'is_win_prize' => 1));
        }
        
        // 清除中奖码,以免重复提交
        session('_TmpChouJian_', null);
        log_write("领奖成功");
        $this->success("领奖成功");
    }
    
    // 查询调用抽奖异步结果
    public function getCjResult() {
        import("@.Vendor.CjInterface");
        $cjInterface = new CjInterface();
        $key = I('get.key', I('post.key'));
        log_write('redis key:' . $key);
        $result = $cjInterface->getCjResultByKey($key);
        /*
         * //todo debug 中了微信卡券 $result = array( 'resp_id'=>'0000',
         * 'resp_desc'=>'微信卡券', 'resp_data'=>array( 'card_id'=>'card_id_1',
         * 'card_ext'=>'card_ext_1', ), );
         */
        
        /*
         * //模拟卡券 $result = array( 'resp_id'=>'0000', 'resp_desc'=>'模拟卡券',
         * 'resp_data'=>array( 'request_id'=>'12345678', ), );
         */
        
        if (! $result) {
            $this->responseJson(1001, 'waiting');
            return;
        }
        log_write('result:' . print_r($result, true));
        if ($result['resp_id'] != '0000') {
            // 如果是被限制都统一叫未中奖
            if ($result['resp_id'] == '1003') {
                $result['resp_desc'] = '对不起，未中奖';
            }
            $this->responseJson($result['resp_id'], $result['resp_desc']);
            return;
        }
        // 中了手机凭证奖品
        $resp = $result['resp_data'];
        if (! empty($resp['request_id'])) 
        // || !empty($resp['cj_trace_id'])
        {
            log_write(print_r($resp, true));
            // 临时中奖码，需要响应给前台，校验一下正确性用的，校验正确后失效,以免直接暴露 request_id和
            // cj_trace_id,用完以后清空
            $cj_code = time() . mt_rand(100, 999);
            session('_TmpChouJian_', 
                array(
                    'cj_code' => $cj_code, 
                    'request_id' => $resp['request_id'], 
                    'cj_trace_id' => $resp['cj_trace_id'], 
                    'card_ext' => $resp['card_ext'], 
                    'card_id' => $resp['card_id']));
            $result['resp_data']['cj_code'] = $cj_code;
        }
        // 返回结果
        $this->responseJson(0, $result['resp_desc'], $result['resp_data']);
    }
    // 测试抽奖队列
    public function testCjQueue() {
        import("@.Vendor.CjInterface");
        $cjInterface = new CjInterface();
        $arr = array();
        $result = $cjInterface->cjSendQueue(array(
            'id' => 1));
        $this->ajaxReturn($result);
    }
}