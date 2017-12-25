<?php

/**
 * 功能：活动创建
 *
 * @author siht 时间：2013-06-24
 */
class ActivityAddAction extends BaseAction {
    // 接口参数
    public $batch_short_name;
    // 活动简称
    public $batch_class;
    // 活动类型
    public $send_begin_date;
    // 发送开始时间
    public $send_end_date;
    // 发送截止时间
    public $verify_begin_date;
    // 验证开始时间
    public $verify_end_date;
    // 验证截止时间
    public $verify_begin_type;
    // 验证开始时间类型
    public $verify_end_type;
    // 验证截止时间类型
    public $join_rule;
    // 活动参与规则
    public $use_rule;
    // 活动使用规则
    public $batch_image;
    // 活动图片
    public $batch_amt;
    // 活动金额
    public $batch_discount;
    // 活动折扣
    public $info_title;
    // 彩信标题
    public $material_code;

    // 物料编码
    public function _initialize() {
        parent::_initialize();
        $this->checkLogin();
        $this->batch_short_name  = I('batch_short_name'); // 活动简称
        $this->batch_class       = I('batch_class'); // 活动类型
        $this->send_begin_date   = I('send_begin_date'); // 发送开始时间
        $this->send_end_date     = I('send_end_date'); // 发送截止时间
        $this->verify_begin_date = I('verify_begin_date'); // 验证开始时间
        $this->verify_end_date   = I('verify_end_date'); // 验证截止时间
        $this->verify_begin_type = I('verify_begin_type'); // 验证开始时间类型
        $this->verify_end_type   = I('verify_end_type'); // 验证截止时间类型
        $this->join_rule         = I('join_rule'); // 活动参与规则
        $this->use_rule          = I('use_rule'); // 活动使用规则
        $this->batch_image       = I('batch_image'); // 活动图片
        $this->batch_amt         = I('batch_amt'); // 活动金额
        $this->batch_discount    = I('batch_discount'); // 活动折扣
        $this->info_title        = I('info_title'); // 彩信标题
        $this->material_code     = I('material_code'); // 物料编码
    }

    public function run() {
        $rs = $this->check();
        if ($rs !== true) {
            $this->returnError($rs['resp_desc']);
        }

        // 1.请求支撑，创建活动
        // =========计算活动截止时间================
        // 先默认为活动发送结束时间
        $end_time = $this->send_end_date;
        // 验证结束时间类型，如果按天数,真正结束时间=活动发送结束时间+天数
        if ($this->verify_end_type == '1') {
            $end_time = date("Ymd235959", strtotime($end_time . " +" . $this->verify_end_date . " days"));
        }  // 如果按日期，就取 发送结束时间与验证结束时间的最大值
        else if ($this->send_end_date < $this->verify_end_date) {
            $end_time = $this->verify_end_date;
        }

        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号

        // 请求参数
        $req_array = array(
                'ActivityCreateReq' => array(
                        'SystemID'      => C('ISS_SYSTEM_ID'),
                        'ISSPID'        => $this->node_id,
                        'TransactionID' => $TransactionID,
                        'ActivityInfo'  => array(
                                'CustomNo'          => $this->material_code,
                                'ActivityName'      => $this->batch_short_name,
                                'ActivityShortName' => $this->batch_short_name,
                                'BeginTime'         => $this->send_begin_date,
                                'EndTime'           => $end_time,
                        ),
                        'VerifyMode'    => array(
                                'UseTimesLimit' => 1,
                                'UseAmtLimit'   => 0,
                        ),
                        'GoodsInfo'     => array(
                                'GoodsName'      => $this->batch_short_name,
                                'GoodsShortName' => $this->batch_short_name,
                        ),
                        'DefaultParam'  => array(
                                'PasswordTryTimes' => 3,
                                'PasswordType'     => '',
                                'ServiceType'      => '00',
                        ),
                ),
        );

        $RemoteRequest = D('RemoteRequest', 'Service');
        $resp_array    = $RemoteRequest->requestIssServ($req_array);
        $ret_msg       = $resp_array['ActivityCreateRes']['Status'];

        if (!$resp_array || ($ret_msg['StatusCode'] != '0000' && $ret_msg['StatusCode'] != '0001')) {
            $resp_desc = "支撑应答：" . $ret_msg['StatusText'];
            $this->returnError($resp_desc, $ret_msg['StatusCode']);
        }

        // 2.记录活动信息。
        $activity_info = array();

        $activity_info['node_id']           = $this->node_id;
        $activity_info['user_id']           = $this->user_id;
        $activity_info['pos_id']            = $this->pos_id;
        $activity_info['batch_name']        = $this->batch_short_name; // 活动名称
        $activity_info['batch_short_name']  = $this->batch_short_name; // 活动简称
        $activity_info['batch_class']       = $this->batch_class; // 活动类型
        $activity_info['material_code']     = $this->material_code; // 物料编码
        $activity_info['begin_time']        = $this->send_begin_date; // 活动开始时间
        $activity_info['end_time']          = $end_time; // 活动结束时间
        $activity_info['join_rule']         = $this->join_rule; // 活动参与规则
        $activity_info['use_rule']          = $this->use_rule; // 使用规则
        $activity_info['batch_img']         = $this->batch_image; // 活动图片
        $activity_info['send_begin_date']   = $this->send_begin_date; // 发送开始日期
        $activity_info['send_end_date']     = $this->send_end_date; // 发送结束日期
        $activity_info['verify_begin_date'] = $this->verify_begin_date; // 验证开始日期
        $activity_info['verify_end_date']   = $this->verify_end_date; // 验证结束日期
        $activity_info['verify_begin_type'] = $this->verify_begin_type; // 验证开始日期类型
        $activity_info['verify_end_type']   = $this->verify_end_type; // 验证结束日期类型
        $activity_info['batch_amt']         = $this->batch_amt; // 活动金额
        $activity_info['batch_discount']    = $this->batch_discount; // 活动折扣
        $activity_info['info_title']        = $this->info_title; // 彩信标题
        $activity_info['batch_type']        = '0'; // 游戏活动标识
        $activity_info['status']            = '0';
        $activity_info['validate_times']    = 1; // 验证次数,默认为1次
        $activity_info['batch_no']          = $resp_array['ActivityCreateRes']['ActivityID'];
        $activity_info['add_time']          = date('YmdHis');

        $activity = D("Activity", 'Service');
        $rs       = $activity->writeActivity($activity_info);
        if ($rs) {
            $resp_desc = "活动创建成功";
            $this->returnSuccess($resp_desc, array(
                            "batch_no" => $resp_array['ActivityCreateRes']['ActivityID'],
                    ));
        } else {
            $resp_desc = "活动新增入库失败";
            $this->returnError($resp_desc, '1003');
        }
    }

    private function check() {
        if ($this->node_id == '') {
            return array(
                    'resp_desc' => '商户号不能为空！',
            );

            return false;
        }
        if ($this->user_id == '') {
            return array(
                    'resp_desc' => '用户id不能为空！',
            );

            return false;
        }
        if ($this->pos_id == '') {
            return array(
                    'resp_desc' => '终端号不能为空！',
            );

            return false;
        }
        if ($this->batch_short_name == '') {
            return array(
                    'resp_desc' => '活动简称不能为空！',
            );

            return false;
        }
        if ($this->batch_class == '') {
            return array(
                    'resp_desc' => '活动类型不能为空！',
            );

            return false;
        }
        if ($this->send_begin_date == '') {
            return array(
                    'resp_desc' => '发送开始时间不能为空！',
            );

            return false;
        }
        if ($this->send_end_date == '') {
            return array(
                    'resp_desc' => '发送截止时间不能为空！',
            );

            return false;
        }
        if ($this->verify_begin_date == '') {
            return array(
                    'resp_desc' => '验证开始时间不能为空！',
            );

            return false;
        }
        if ($this->verify_end_date == '') {
            return array(
                    'resp_desc' => '验证截止时间不能为空！',
            );

            return false;
        }
        if ($this->verify_begin_type == '') {
            return array(
                    'resp_desc' => '验证开始日期类型不能为空！',
            );

            return false;
        }
        if ($this->verify_end_type == '') {
            return array(
                    'resp_desc' => '验证结束日期类型不能为空！',
            );

            return false;
        }
        if ($this->join_rule == '') {
            return array(
                    'resp_desc' => '交易规则不能为空！',
            );

            return false;
        }
        if ($this->use_rule == '') {
            return array(
                    'resp_desc' => '使用规则不能为空！',
            );

            return false;
        }
        if ($this->info_title == '') {
            return array(
                    'resp_desc' => '彩信标题不能为空！',
            );

            return false;
        }

        // 接下来校验入参长度边界
        if (strlen($this->info_title) > 20) {
            return array(
                    'resp_desc' => '彩信标题超长，最长20字节！',
            );

            return false;
        }

        // 统一下日期格式,再做判断
        $this->send_begin_date = date('Ymd000000', strtotime($this->send_begin_date)); // 发送开始时间
        $this->send_end_date   = date('Ymd235959', strtotime($this->send_end_date)); // 发送截止时间

        $begin_verify_date = date('Ymd000000', strtotime($this->verify_begin_date));
        if ($this->verify_begin_type == '1') {
            $begin_verify_date = date('Ymd000000',
                    strtotime($this->send_end_date . "+" . $this->verify_begin_date . " days"));
        }

        $end_verify_date = date('Ymd235959', strtotime($this->verify_end_date));
        if ($this->verify_end_type == '1') {
            $end_verify_date = date('Ymd235959',
                    strtotime($this->send_end_date . "+" . $this->verify_end_date . " days"));
        }

        if ($this->send_end_date < date('YmdHis')) {
            return array(
                    'resp_desc' => '发送截止日期不能小于当前日期！',
            );
        }

        if ($this->send_end_date < $this->begin_date) {
            return array(
                    'resp_desc' => '发送截止日期不能小于发送开始日期！',
            );
        }

        if ($end_verify_date < $begin_verify_date) {
            return array(
                    'resp_desc' => '验证结束日期不能小于验证开始日期！',
            );
        }
        if ($end_verify_date < $this->send_end_date) {
            return array(
                    'resp_desc' => '验证结束日期不能小于发码结束日期！',
            );
        }

        /*
         * if($this->verify_begin_type == '0'){ $this->verify_begin_date =
         * date('Ymd000000',strtotime($this->verify_begin_date)); //发送开始时间 }
         * if($this->verify_end_type == '0'){ $this->verify_end_date =
         * date('Ymd235959',strtotime($this->verify_end_date)); //发送截止时间 } if($this->send_end_date
         * < date('YmdHis')) { return array('resp_desc'=>'发送截止日期不能小于当前日期！');
         * return false; }
         * if($this->send_end_date < $this->begin_date) { return
         * array('resp_desc'=>'发送截止日期不能小于发送开始日期！'); return false; }
         * //验证结束天数不能小于验证码开时天数 if($this->verify_begin_type == '1' &&
         * $this->verify_end_type == '1' && $this->verify_begin_date >
         * $this->verify_end_date){ return
         * array('resp_desc'=>'验证结束天数不能小于验证开始天数！'); } //验证结束日期不能小于验证开始日期
         * elseif($this->verify_begin_type == '0' && $this->verify_end_type ==
         * '0' && $this->verify_begin_date > $this->verify_end_date){ return
         * array('resp_desc'=>'验证结束日期不能小于验证开始日期！'); }
         * elseif($this->verify_begin_type == '1' && $this->verify_end_type ==
         * '0' && date("Ymd000000",strtotime("+".$this->verify_begin_date."
         * days")) > $this->verify_end_date ){ return
         * array('resp_desc'=>'验证结束日期不能小于验证开始日期！'); }
         * elseif($this->verify_begin_type == '0' && $this->verify_end_type ==
         * '1' && $this->verify_begin_date >
         * date("Ymd235959",strtotime("+".$this->verify_end_date." days")) ){
         * return array('resp_desc'=>'验证结束日期不能小于验证开始日期！'); }
         */

        return true;
    }
}

?>