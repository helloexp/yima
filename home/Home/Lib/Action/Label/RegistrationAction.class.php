<?php
// 注册有礼
class RegistrationAction extends MyBaseAction {

    const REG_FROM_LABEL = 2;

    public $js_global = array();

    public $regSess = array();

    public $_userid = '';

    public $is_wk = '';

    public function _initialize() {
        if (ACTION_NAME == 'imgCode' || ACTION_NAME == 'sendSmsCode') {
            return;
        }
        parent::_initialize();
        $userid = I('get.userid', I('post.userid'), 'trim');
        $wid = I('get.wid', '', 'trim'); // 旺号
        if ($wid) {
            $userid = $wid;
        }
        $wkRegBatchId = M('tsystem_param')->where(
            array(
                'param_name' => 'WK_REG_BATCH_ID'))->getField('param_value');
        if ($this->batch_id == $wkRegBatchId) {
            $this->is_wk = true;
        }
        $this->js_global = array(
            'url' => array(
                'regSubmit' => U('Label/Registration/regSubmit')), 
            'userid' => $userid,  // 这两个值要一直传递下去
            'id' => $this->id,  // 这两个值要一直传递下去
            'landname' => I('request.landname'));
        $this->_setGlobalJs($this->js_global);
        $this->_userid = $userid;
        $this->assign('userid', $userid);
        $this->regSess = session('WapRegSess') or $this->regSess = array();
        /*
         * to-do debug $this->regSess = array( 'user_id'=>1,
         * 'node_id'=>'00000000', 'mobile'=>'13482121286', );
         */
    }

    protected function _setGlobalJs($arr = array()) {
        $this->js_global = array_merge($this->js_global, $arr);
        $this->assign('js_global', json_encode($this->js_global));
    }

    public function index() {
        $id = $this->id;
        if ($this->batch_type != '32') {
            $this->error(
                array(
                    'errorImg' => '__PUBLIC__/Label/Image/waperro5.png', 
                    'errorTxt' => '错误访问！', 
                    'errorSoftTxt' => '你的访问地址出错啦~'));
        }
        
        // 访问量
        import('@.Vendor.DataStat');
        $opt = new DataStat($this->id, $this->full_id);
        $opt->UpdateRecord();
        $paramArr = array(
            'id' => $id, 
            'userid' => $this->_userid, 
            'landname' => I('landname'));
        $this->redirect('Label/Registration/reg', $paramArr);
    }

    public function reg() {
        $cmId = $this->_userid; // 客户经理编号
        $landname = I("landname");
        if ($cmId && strlen($cmId) > 12) {
            $cmId = '';
        }
        /*
         * //特殊判断跳转 if($cmId == '6670'){
         * redirect('http://www.wangcaio2o.com/index.php?&g=Label&m=Bm&a=index&id=24563');
         * }
         */
        $this->assign('is_wk', $this->is_wk); // 是否是旺客
        $this->assign('landname', $landname); // landname
        $this->assign('is_cj', $this->marketInfo['is_cj']);
        $this->assign('cmId', $cmId);
        $this->display();
    }
    
    // 注册提交
    public function regSubmit() {
        /*
         * header('Access-Control-Allow-Origin: *');
         * header('Access-Control-Allow-Methods: GET, POST');
         */
        $data = array();
        $data['landname'] = trim(I('post.landname')); // 落地页名称
        $data['node_name'] = trim(I('post.node_name')); // 机构名称
        $data['node_short_name'] = mb_substr(I('post.node_name'), 0, 9, 'utf-8'); // 机构简称
        $data['regemail'] = trim(I('post.user_name')); // 注册邮箱
        $data['user_password'] = md5(trim(I('post.user_password'))); // 用户密码
        $data['contact_name'] = trim(I('post.contact_name')); // 联系人名
        $mobile = $data['contact_phone'] = trim(I('post.contact_phone')); // 联系电话
        $data['province_code'] = trim(I('post.province_code')); // 省代号
        $data['city_code'] = trim(I('post.city_code')); // 市代号
        
        $data['client_manager'] = trim(I('post.client_manager')); // 客户经理号
        $data['reg_from'] = self::REG_FROM_LABEL;
        $data['channel_id'] = $this->channel_id;
        
        $data['tg_id'] = $_COOKIE["reg_tgxt_id"];
        
        $service = D('NodeReg', 'Service');
        $result = $service->nodeAdd($data);
        if (! $result || ! $result['status']) {
            $this->responseJson(- 1, '注册失败:' . $result['info']);
        }
        $resultData = $result['data'];
        log_write(print_r($result, true));
        if ($resultData['user_id'] == '' || $resultData['node_id'] == '') {
            $this->responseJson(- 1, '注册失败02');
        }
        // 注册成功
        
        // 活动
        $batchModel = M('tmarketing_info');
        // 更新注册数量
        $batchModel->where("id='{$this->batch_id}'")->setInc('member_sum');
        
        $codeInfo = "恭喜您已成为旺财用户，企业旺号{$resultData['client_id']}，同时您还获得了新手大礼包一份，礼包必须在三天内通过电脑登陆旺财官网www.wangcaiO2O.com确认后才可生效！快去领取吧！"; // 短信内容
                                                                                                                                   
        // 发送注册短信(是旺客时，不发短信)
        if (! $this->is_wk) {
            $RemoteRequest = D('RemoteRequest', 'Service');
            $resp_array = $RemoteRequest->smsSend($mobile, $codeInfo);
        }
        $respData = array(
            'url' => U('Label/Registration/result', 
                array(
                    'id' => $this->id, 
                    'landname' => $data['landname'], 
                    'regemail' => $data['regemail'])));
        session('WapRegSess', 
            array(
                'user_id' => $resultData['user_id'], 
                'node_id' => $resultData['node_id'], 
                'mobile' => $mobile, 
                'id' => $this->id));
        
        // 尝试触发注册相关的任务 start
        tag('reg_task_init', $result['data']);
        tag('reg_task_finish', $result['data']);
        // 尝试触发注册相关任务 end
        
        $this->responseJson(0, 'success', $respData);
    }
    
    // 发送手机验证码//该函数停用#9749
    public function sendSmsCode() {
        $mobile = I('contact_phone');
        $img_code = I('img_code');
        if ($img_code == '' || session('verify_imgcode') != md5($img_code)) {
            $this->responseJson(- 1, "图片验证码错误");
        }
        session('verify_imgcode', null);
        $code = mt_rand(100000, 999999);
        $content = $code . '（旺财平台验证码，十分钟内有效），请在页面中输入以完成验证，如非本人操作，请忽略本短信';
        log_write("会员注册：");
        log_write(
            implode("\n", 
                array(
                    '手机号' => $mobile, 
                    '验证码' => $code)));
        session('reg_sms_code', $code); // 记录session
        $result = D('RemoteRequest', 'Service')->smsSend($mobile, $content);
        if (! $result) {
            $this->responseJson(- 1, "发送失败");
            log_write("发送失败" . print_r($result, true));
        }
        $this->responseJson(0, "发送成功");
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

    public function imgCode() {
//        import('ORG.Util.Image');
//        Image::buildImageVerify($length = 4, $mode = 1, $type = 'png',
//            $width = 48, $height = 22, $verifyName = 'verify_imgcode');

        import('@.Service.ImageVerifyService');
        ImageVerifyService::buildImageCodeByParam('verify_imgcode');
    }
    
    // 结果页
    public function result() {
        // 如果没有session，则跳转到首页
        if (! $this->regSess || $this->regSess['user_id'] == '') {
            redirect(
                U('Label/Registration/index', 
                    array(
                        "id" => $this->id)));
        }
        
        // 活动
        $batchModel = M('tmarketing_info');
        // $row = $batchModel->where(array('id'=>$this->batch_id))->find();
        $row = $this->marketInfo;
        $query_arr = explode('-', $row['select_type']);
        // 抽奖配置表
        $cj_text = '';
        if ($row['is_cj'] == '1') {
            $model_c = M('tcj_rule');
            $map_c = array(
                'batch_type' => $this->batch_type, 
                'batch_id' => $this->batch_id, 
                'status' => '1');
            $cj_rule_query = $model_c->field('cj_button_text,cj_check_flag')
                ->where($map_c)
                ->find();
            // 抽奖文字配置
            $cj_text = $cj_rule_query['cj_button_text'];
            // 判断是否显示参与码
            $cj_check_flag = $cj_rule_query['cj_check_flag'];
        } else {
            $cj_check_flag = 0;
        }
        
        $userid = I('get.userid', null, 'trim');
        $this->assign('landname', I('get.landname'));
        $this->assign('regemail', I('get.regemail'));
        $this->assign('id', $this->id);
        $this->assign('query_arr', $query_arr);
        $this->assign('row', $row);
        $this->assign('cj_text', $cj_text);
        $this->assign('cj_check_flag', $cj_check_flag);
        $this->assign('batch_type', $this->batch_type);
        $this->assign('batch_id', $this->batch_id);
        $this->assign('mobile', $this->regSess['mobile']);
        $this->assign('is_wk', $this->is_wk); // 是否是旺客
        
        $this->display();
    }
    // 领取注册大礼包.json
    public function getGift() {
        if (! $this->regSess || $this->regSess['user_id'] == '') {
            $this->error("未注册");
        }
        // 领取任务
        $user_id = $this->regSess['user_id'];
        $task = D('Task', 'Service')->getTask('WapRegTask');
        if (! $task) {
            $this->responseJson(- 1, '领取任务已结束');
        }
        $result = $task->addGift($user_id);
        if (! $result || $result['code']) {
            $this->responseJson($result['code'], $result['msg']);
        }
        $this->responseJson(0, 'success');
    }
}