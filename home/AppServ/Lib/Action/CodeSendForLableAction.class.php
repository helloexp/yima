<?php

/**
 *  todo 要管
 * 功能：凭证发码(默认提供给游戏活动使用)
 *
 * @author wtr 时间：2013-07-07
 */
class CodeSendForLableAction extends BaseAction
{

    public $node_id;// 商户号
    public $phone_no;// 手机号
    public $pos_id;    // 终端号
    public $request_id; // 请求流水号
    public $batch_no;// 活动号
    public $batch_info_id;// tbatch_info表ID
    public $batch_id;// 发送批次
    public $send_level;// 发送级别
    public $data_from;// 数据来源
    public $user_id;// 操作员id
    public $active_flag;// 激活标志 0-全激活 9-未激活 1-条码激活，辅助码未激活
    public $ticket_seq;// 抽奖小票流水号
    public $batch_type;// 营销活动来源
    public $store_id;// 门店ID
    public $assist_number;// 微信卡券辅助码
    public $begin_use_time;// 微信卡券开始使用时间
    public $end_use_time;// 微信卡券结束使用时间
    public $is_wx_card;// 是否微信卡券 yes
    public $ims_flag;// 是否先制图后转发 yes、no
    public $channel_id;// 渠道ID
    public $df_openid;// df 微信open_id
    public $wx_open_id;// 参与者的openid
    public $fb_yhb_flag;// 翼惠宝标记
    public $fb_cmpay_flag;// 和包卡券标记
    public $print_text;// 打印内容
    public $use_rule;// 短彩信内容
    public $member_id;// 会员ID
    public $batch_desc;//微信卡券ID
    public $wx_card_id;// 营销活动id
    public $mid;// 发送备注

    private $send_num;

    /**
     * @var RemoteRequestService
     */
    private $RemoteRequestService;

    /**
     * @var MemberBehaviorModel
     */
    private $MemberBehaviorModel;

    /**
     * @var YhbSmsService
     */
    private $YhbSmsService;

    /**
     * @var CMPAYService
     */
    private $CMPAYService;

    /**
     * @var MemberInstallModel
     */
    private $MemberInstallModel;

    public function _initialize()
    {
        parent::_initialize();
        // 动态载入 CodeSendForLable 配置文件
        C('CodeSendForLable', require(CONF_PATH . 'configCodeSendForLable.php'));
        // 初始化请求参数
        $this->phone_no       = I('phone_no');
        $this->pos_id         = I('pos_id');
        $this->request_id     = I('request_id');
        $this->batch_no       = I('batch_no');
        $this->batch_info_id  = I('batch_info_id');
        $this->batch_id       = I('batch_id');
        $this->send_level     = I('send_level');
        $this->data_from      = I('data_from', 0);
        $this->user_id        = I('user_id', 0);
        $this->active_flag    = I('active_flag');
        $this->ticket_seq     = I('ticket_seq'); // 抽奖小票流水号
        $this->batch_type     = I('batch_type'); // 营销活动类型
        $this->store_id       = I('store_id'); // 门店ID
        $this->assist_number  = I('assist_number'); // 微信卡券辅助码
        $this->begin_use_time = I('begin_use_time'); // 微信卡券开始使用时间
        $this->end_use_time   = I('end_use_time'); // 微信卡券结束使用时间
        $this->is_wx_card     = I('is_wx_card'); // 是否微信卡券 yes
        $this->ims_flag       = I('ims_flag'); // 是否先制图后转发 yes、no
        $this->channel_id     = I('channel_id'); // 渠道ID
        $this->df_openid      = I('df_openid'); // 渠道ID
        $this->wx_open_id     = I('wx_open_id'); // 参与者的openid
        $this->node_id        = I('node_id');
        $this->fb_yhb_flag    = $this->node_id == C('yhb.node_id'); // 翼惠宝标记
        $this->print_text     = I('print_text'); // 打印内容
        $this->use_rule       = I('use_rule'); // 短彩信内容
        $this->member_id      = I('member_id'); // 会员ID
        $this->batch_desc     = I('batch_desc'); // 发送备注
        $this->wx_card_id     = I('wx_card_id'); //微信卡券ID
        $this->fb_cmpay_flag  = I('fb_cmpay_flag'); //翼惠宝标记

        $this->RemoteRequestService = D('RemoteRequest', 'Service');
    }

    public function run()
    {
        // 调用接口
        $info = C('CodeSendForLable');

        if (!$info) {
            // 尚未配置接口，接口不对外
            $resp_desc = "非法访问！";
            $this->returnError($resp_desc);
            exit();
        }

        // 获取接入端IP
        $ip    = $_SERVER['REMOTE_ADDR'];
        $ipArr = explode(',', $info['IMPORT_IP']);
        if (!in_array($ip, $ipArr)) {
            // IP不允许接入
            $resp_desc = "IP:" . $ip . "不允许接入";
            $this->returnError($resp_desc);
        }

        $rs = $this->check();

        if (!$rs) {
            $resp_desc = "请填写商户号、手机号码、请求流水号";
            $this->returnError($resp_desc);
        }

        // 第一步,校验是否具备发码权限
        // (1)商户校验
        $where     = "NODE_ID ='" . $this->node_id . "'";
        $node_info = M('TnodeInfo')->where($where)->find();
        if (!$node_info) {
            $this->returnError('商户不存在', '1011');
        }
        if ($node_info['status'] != '0') {
            $this->returnError('商户已停用', '1012');
        }

        if ($this->pos_id == null) {
            $pos_info = array(
                    'pos_id' => '00000000',
            );
        } else {
            $pos_info = array(
                    'pos_id' => $this->pos_id,
            );
        }

        // 第二步,取活动信息
        if (($this->batch_id != null) && ($this->batch_id > 0) && ($this->data_from != 2)) { // 批量发送数据
            // 校验活动状态
            $where         = "NODE_ID ='{$this->node_id}' and batch_no ='{$this->batch_no}'";
            $activity_info = M('TgoodsInfo')->where($where)->find();
            if (!$activity_info) {
                $this->returnError('该活动不存在', '1015');
            }
            if ($activity_info['status'] != '0') {
                $this->returnError('该活动已停用或已过期', '1116');
            }
            $goods_id   = $activity_info['goods_id'];
            $goods_info = $activity_info;
            // 取发送参数
            $where         = "NODE_ID ='" . $this->node_id . "' and batch_id ='" . $this->batch_id . "'";
            $activity_info = M('TbatchImport')->where($where)->find();
            if (!$activity_info) {
                $this->returnError('该发送批次不存在', '1015');
            }
            $activity_info["verify_end_date"]   = $activity_info["verify_end_time"];
            $activity_info["verify_end_type"]   = '0';
            $activity_info["verify_begin_date"] = $activity_info["verify_begin_time"];
            $activity_info["verify_begin_type"] = '0';
            $activity_info['use_rule']          = $activity_info['mms_notes'];
            $activity_info['batch_amt']         = $activity_info['validate_amt'];
            $activity_info['goods_id']          = $goods_id;
            $activity_info['sms_text']          = $activity_info['notes'];
            $this->batch_desc                   = $activity_info['batch_desc'];
        } else if (($this->data_from == 5) || ($this->data_from == 'W')) {
            // 粉丝卡单条 + 微信卡券投放
            $where         = "NODE_ID ='" . $this->node_id . "' and batch_no ='" . $this->batch_no . "'";
            $activity_info = M('TgoodsInfo')->where($where)->find();
            if (!$activity_info) {
                $this->returnError('该活动不存在', '1015');
            }
            if ($activity_info['status'] != '0') {
                $this->returnError('该活动已停用或已过期', '1116');
            }
            $activity_info['use_rule']   = $activity_info['mms_text'];
            $activity_info['info_title'] = $activity_info['mms_title'];
            $activity_info['batch_amt']  = $activity_info['goods_amt'];
            $goods_info                  = $activity_info;
        } else {
            $where         = "NODE_ID ='" . $this->node_id . "' and ID ='" . $this->batch_info_id . "'";
            $activity_info = M('TbatchInfo')->where($where)->find();
            if (!$activity_info) {
                $this->returnError('该活动不存在', '1015');
            }
            if ($activity_info['status'] != '0') {
                $this->returnError('该活动已停用或已过期', '1116');
            }

            $this->log('sms_text=[' . $activity_info['sms_text'] . ']');

            $where      = "NODE_ID ='" . $this->node_id . "' and goods_id ='" . $activity_info['goods_id'] . "'";
            $goods_info = M('TgoodsInfo')->where($where)->find();
            if (!$goods_info) {
                $this->returnError('该商品不存在', '1015');
            }
            if ($goods_info['status'] != '0') {
                $this->returnError('该商品已停用或已过期', '1116');
            }
        }

        // 用传入的打印内容替换 表配置的 打印内容
        if ($this->print_text != null && strlen($this->print_text) > 0) {
            $activity_info['print_text'] = $this->print_text;
        }
        // 用传入的短信内容替换 表配置的 短彩信内容
        if ($this->use_rule != null && strlen($this->use_rule) > 0) {
            $activity_info['use_rule'] = $this->use_rule;
        }

        //校验彩信标题长度如为0 则替换固定值
        if ($activity_info['info_title'] == null || strlen($activity_info['info_title']) == 0) {
            $activity_info['info_title'] = '电子券';
        }
        $this->log("print_text=[" . $this->print_text . '],use_rule=[' . $this->use_rule . ']');

        // 计算凭证截止时间
        $end_time = date("Ymd235959", strtotime($activity_info["verify_end_date"]));
        if ($activity_info["verify_end_type"] == '1') {
            $end_time = date("Ymd235959", strtotime("+" . $activity_info["verify_end_date"] . " days"));
        }

        // 计算凭证开始时间
        $begin_time = date("Ymd000000", strtotime($activity_info["verify_begin_date"]));
        if ($activity_info["verify_begin_type"] == '1') {
            $begin_time = date("Ymd000000", strtotime("+" . $activity_info["verify_begin_date"] . " days"));
        }

        // 处理营帐发送类型
        $err_num = 0;
        $err_msg = '';

        $this->send_num = 1; // 该值不能修改
        $this->pos_id   = $pos_info['pos_id'];

        for ($i = 1; $i <= $this->send_num; $i++) {
            $TransactionID                     = toiss_reqid(); // 凭证发送单号
            $barcode_trace                     = array();
            $tbarcode_trace_send['trans_type'] = '0001';
            // 第四步，请求支撑发码
            if (($this->batch_type == 3) && ($this->node_id == '00004488')) {
                $tbarcode_trace_send['trans_type'] = '0004';
                $barcode_trace['barcode_type']     = '1';
                // 非标 哈根达斯时段抽奖 发送短信
                // 替换短信内容
                $date_note                 = date('Y年m月d日');
                $activity_info['use_rule'] = str_replace("#日期#", $date_note, $activity_info['use_rule']);

                // 获取门店名称
                $where      = "store_id = '" . $this->store_id . "'";
                $store_info = M('tstore_info')->where($where)->find();
                if ($store_info) {
                    $activity_info['use_rule'] = str_replace(
                            "#门店名称#",
                            $store_info['store_name'],
                            $activity_info['use_rule']
                    );
                }
                $req_array  = array(
                        'NotifyReq' => array(
                                'TransactionID' => $TransactionID,
                                'ISSPID'        => $this->node_id,
                                'SystemID'      => C('ISS_SYSTEM_ID'),
                                'SendLevel'     => '1',
                                'Recipients'    => array(
                                        'Number' => $this->phone_no,
                                ),  // 手机号
                                'SendClass'     => 'SMS',
                                'MessageText'   => $activity_info['use_rule'],  // 短信内容
                                'Subject'       => '',
                                'ActivityID'    => $activity_info['batch_no'],
                                'ChannelID'     => '',
                                'ExtentCode'    => '',
                        ),
                );
                $resp_array = $this->RemoteRequestService->requestIssServ($req_array);
                $ret_msg    = $resp_array['NotifyRes']['Status'];
            } else if ($goods_info['goods_type'] == '8') { // 8 Q币
                $tbarcode_trace_send['trans_type'] = '0004';
                $barcode_trace['barcode_type']     = '1';
                // 8 Q币 发送短信
                // 替换短信内容 $end_time
                $end_time_note             = date("Y年m月d日", strtotime($end_time));
                $activity_info['use_rule'] = str_replace("[#END_DATE]", $end_time_note, $activity_info['use_rule']);

                // 替换短链接
                $id_str    = md5($this->phone_no . $this->getGlide());
                $short_url = $this->RemoteRequestService->shorturl(C('PHONE_BILLS_URL') . $id_str);
                $this->log(C('PHONE_BILLS_URL') . $id_str);
                $this->log($short_url);
                $activity_info['use_rule'] = str_replace("[#GET_URL]", $short_url . ' ', $activity_info['use_rule']);
                // 保存流水
                $phone_bills_trace['id_str']        = $id_str;
                $phone_bills_trace['node_id']       = $this->node_id;
                $phone_bills_trace['m_id']          = $activity_info['m_id'];
                $phone_bills_trace['g_id']          = $goods_info['id'];
                $phone_bills_trace['recharge_type'] = $goods_info['goods_type'] == '7' ? '0' : '1'; // 充值类型 0-话费 1-Q币
                $phone_bills_trace['org_phone_no']  = $this->phone_no;
                $phone_bills_trace['amount']        = $goods_info['market_price'];
                $phone_bills_trace['add_time']      = date('YmdHis');
                $phone_bills_trace['end_time']      = $end_time;
                $phone_bills_trace['short_url']     = $short_url;
                $phone_bills_trace['request_id']    = $this->request_id;
                $phone_bills_trace['status']        = '0';
                $phone_bills_trace['member_id']     = $this->getMemberId();
                $rs                                 = M('tphone_bills_trace')->add($phone_bills_trace);
                if (!$rs) {
                    $this->log(print_r($phone_bills_trace, true));
                    $this->log("记录[phone_bills_trace]失败");
                    M()->rollback();
                    $this->returnError('系统故障', '1040');
                }

                $req_array                                  = array(
                        'NotifyReq' => array(
                                'TransactionID' => $TransactionID,
                                'ISSPID'        => $this->node_id,
                                'SystemID'      => C('ISS_SYSTEM_ID'),
                                'SendLevel'     => '1',
                                'Recipients'    => array(
                                        'Number' => $this->phone_no,
                                ),  // 手机号
                                'SendClass'     => 'SMS',
                                'MessageText'   => $activity_info['use_rule'],  // 短信内容
                                'Subject'       => '',
                                'ActivityID'    => $activity_info['batch_no'],
                                'ChannelID'     => '',
                                'ExtentCode'    => '',
                        ),
                );
                $resp_array                                 = $this->RemoteRequestService->requestIssServ($req_array);
                $ret_msg                                    = $resp_array['NotifyRes']['Status'];
                $resp_array['SubmitVerifyRes']['MessageID'] = $resp_array['NotifyRes']['MessageID'];
            } else if ($goods_info['goods_type'] == '11') { // 11 马上发商品
                $use_rule = urlencode(iconv('utf8', 'gbk', $activity_info["use_rule"]));

                $info_title = urlencode(iconv('utf8', 'gbk', $activity_info["info_title"]));

                $req_data   = "cellphone=" . $this->phone_no . "&prom_key=" . $goods_info['pts_batch_key'] . '&req_seq=' . $TransactionID . '&mms_text=' . $use_rule . '&sms_text=' . $use_rule . '&mms_subject=' . $info_title . '&begin_time=' . $begin_time . '&end_time=' . $end_time;
                $resp_array = $this->RemoteRequestService->requestPtsServ($req_data);

                $resp_array['SubmitVerifyRes']['MessageID'] = $resp_array['barcode_seq'];
                $this->log(print_r($resp_array, true));
                $ret_msg['StatusCode'] = $resp_array['resp_id'];
                $ret_msg['StatusText'] = $resp_array['resp_str'];
            } else if ($goods_info['goods_type'] == '15') { // 15 流量包
                $m_id            = I('m_id', 0);
                $req_data_format = 'node_id=%s&phone_no=%s&request_id=%s&batch_info_id=%s&m_id=%s';
                if ($m_id === 0) {
                    $m_id = M('tbatch_info')->where(['id' => $this->batch_info_id])->getField('m_id');
                }
                $this->mid = $m_id;
                $req_data        = sprintf(
                        $req_data_format,
                        $this->node_id,
                        $this->phone_no,
                        $this->request_id,
                        $this->batch_info_id,
                        $m_id
                );
                $resp_array      = $this->RemoteRequestService->requestMobileDataServ($req_data);

                $resp_array['SubmitVerifyRes']['MessageID'] = $resp_array['barcode_seq'];
                $this->log('$resp_array:' . var_export($resp_array, 1));
                $ret_msg['StatusCode'] = $resp_array['resp_id'];
                $ret_msg['StatusText'] = $resp_array['resp_desc'];
                $ret_msg['Origin']     = $resp_array;
                $this->log('$ret_msg:' . var_export($ret_msg, 1));
            } else if ($goods_info['goods_type'] == '7') { // 7 话费
                $m_id            = I('m_id', 0);
                if ($m_id === 0) {
                    $m_id = M('tbatch_info')->where(['id' => $this->batch_info_id])->getField('m_id');
                }
                $this->mid = $m_id;
                $req_data_format = 'node_id=%s&phone_no=%s&request_id=%s&batch_info_id=%s&m_id=%s';
                $req_data        = sprintf(
                        $req_data_format,
                        $this->node_id,
                        $this->phone_no,
                        $this->request_id,
                        $this->batch_info_id,
                        $m_id
                );
                $resp_array      = $this->RemoteRequestService->requestMobileHFServ($req_data);

                $resp_array['SubmitVerifyRes']['MessageID'] = $resp_array['barcode_seq'];
                $this->log('$resp_array:' . var_export($resp_array, 1));
                $ret_msg['StatusCode'] = $resp_array['resp_id'];
                $ret_msg['StatusText'] = $resp_array['resp_desc'];
                $ret_msg['Origin']     = $resp_array;
                $this->log('$ret_msg:' . var_export($ret_msg, 1));
            } else if (($this->data_from == 'W') || ($this->is_wx_card == 'yes')) { // 微信卡券发码
                //判断是否朋友的券
                $card_type_info = M('twx_card_type')->where("card_id='{$this->wx_card_id}'")->find();
                if ($card_type_info['card_class'] == '2') {
                    $systemId = C('WXCARD_FRIEND_ISS_SYSTEM_ID');
                } else {
                    $systemId = C('WXCARD_ISS_SYSTEM_ID');
                }

                $req_array = array(
                        'SubmitVerifyReq' => array(
                                'SystemID'      => $systemId,
                                'ISSPID'        => $this->node_id,
                                'TransactionID' => $TransactionID,
                                'Recipients'    => ['Number' => $this->phone_no,],
                                'SendClass'     => 'IMG',
                                'Messages'      => [
                                        'Sms' => ['Text' => $activity_info['use_rule'],],
                                        'Mms' => [
                                                'Subject' => $activity_info["info_title"],
                                                'Text'    => $activity_info['use_rule'],
                                        ],
                                ],
                                'ActivityInfo'  => [
                                        'ActivityID' => $this->batch_no,
                                        'BeginTime'  => $this->begin_use_time,
                                        'EndTime'    => $this->end_use_time,
                                        'OrgTimes'   => $activity_info["validate_times"],
                                        'OrgAmt'     => $activity_info['batch_amt'],
                                        'PrintText'  => $activity_info['print_text'],
                                ],
                                'Credential'    => ['AssistNumber' => $this->assist_number,],
                                'BmpFlag'       => 2,
                                'SendLevel'     => $this->send_level,
                                'CustomArea'    => $this->request_id,
                        ),
                );
                // 20140717 chensf 增加激活标志
                if ($this->active_flag) {
                    $req_array['SubmitVerifyReq']['ActiveFLag'] = $this->active_flag;
                }
                $resp_array = $this->RemoteRequestService->requestIssServ($req_array);
                $ret_msg    = $resp_array['SubmitVerifyRes']['Status'];
                $begin_time = $this->begin_use_time;
                $end_time   = $this->end_use_time;
            } else {
                $send_type = 'SAM';
                if ($this->ims_flag == 'yes') {
                    $send_type = 'IMS';
                }

                // 翼惠宝凭证无需下发,翼惠宝指定凭证内容、打印文本
                if ($this->fb_yhb_flag) {
                    $send_type                   = 'IMS';
                    $activity_info['use_rule']   = $activity_info['batch_short_name'];
                    $activity_info['info_title'] = '翼惠宝凭证';
                    $activity_info['use_rule']   = $activity_info['batch_short_name'];
                    $activity_info['print_text'] = "您已兑换成功“{$activity_info['batch_short_name']}”，感谢您使用翼惠宝，敬请关注翼惠宝更多优惠！";
                }

                // 和包商品只制卡
                if ($this->fb_cmpay_flag == 'yes') { // 和包商品
                    $send_type = 'IMS';
                }
                // 替换短信内容
                $activity_info['use_rule'] = str_replace("#手机号#", $this->phone_no, $activity_info['use_rule']);
                $activity_info['use_rule'] = str_replace("#小票流水号#", $this->ticket_seq, $activity_info['use_rule']);
                // 非标替换 名称 $activity_info['m_id']
                $where   = 'label_id = ' . $activity_info['m_id'] . " and mobile = '" . $this->phone_no . "'";
                $bm_info = M('tbm_trace')->where($where)->find();
                if ($bm_info) {
                    $activity_info['use_rule'] = str_replace("#姓名#", $bm_info['true_name'], $activity_info['use_rule']);
                } else {
                    $activity_info['use_rule'] = str_replace("#姓名#", "", $activity_info['use_rule']);
                }

                //非自定义短信模板取使用规则填充短信内容
                if ($node_info['custom_sms_flag'] <> '1') {
                    $activity_info['sms_text'] = $activity_info['use_rule'];
                }
                //开通了自定义短信，并且短信内容为空，就拿使用规则进行填充，支撑会自动截取前40个字
                if ($node_info['custom_sms_flag'] == '1' && empty($activity_info['sms_text'])) {
                    //自定义短信模板，短信内容为空
                    $activity_info['sms_text'] = $activity_info['use_rule'];
                }

                // 非标替换 名称 $activity_info['m_id'] ----
                $req_array = array(
                        'SubmitVerifyReq' => [
                                'SystemID'      => C('ISS_SYSTEM_ID'),
                                'ISSPID'        => $this->node_id,
                                'TransactionID' => $TransactionID,
                                'Recipients'    => ['Number' => $this->phone_no,],
                                'SendClass'    => $send_type,
                                'Messages'     => [
                                        'Sms' => ['Text' => $activity_info['sms_text'],],
                                        'Mms' => [
                                                'Subject' => $activity_info["info_title"],
                                                'Text'    => $activity_info['use_rule'],
                                        ],
                                ],
                                'ActivityInfo' => [
                                        'ActivityID' => $this->batch_no,
                                        'BeginTime'  => $begin_time,
                                        'EndTime'    => $end_time,
                                        'OrgTimes'   => $activity_info["validate_times"],
                                        'OrgAmt'     => $activity_info['batch_amt'],
                                        'PrintText'  => $activity_info['print_text'],
                                ],
                                'BmpFlag'      => 2,
                                'SendLevel'    => $this->send_level,
                                'CustomArea'   => $this->request_id,
                        ],
                );
                // 20140717 chensf 增加激活标志
                if ($this->active_flag) {
                    $req_array['SubmitVerifyReq']['ActiveFLag'] = $this->active_flag;
                }
                $resp_array = $this->RemoteRequestService->requestIssServ($req_array);
                $ret_msg    = $resp_array['SubmitVerifyRes']['Status'];
            }
            // save trace
            $tbarcode_trace_send['req_seq']     = $TransactionID;
            $tbarcode_trace_send['org_req_seq'] = $TransactionID;
            $tbarcode_trace_send['phone_no']    = $this->phone_no;
            $tbarcode_trace_send['trans_time']  = date('YmdHis');
            $tbarcode_trace_send['ret_code']    = $ret_msg['StatusCode'];
            $tbarcode_trace_send['ret_desc']    = $ret_msg['StatusText'];
            // 根据发送结果 提交事务或回滚
            if (!$resp_array || ($ret_msg['StatusCode'] != '0000' && $ret_msg['StatusCode'] != '0001')) { // 发送失败 回滚
                M()->rollback();
                $this->log("发送失败 事务回滚");

                $tbarcode_trace_send['status'] = '2';
                $rs                            = M('tbarcode_trace_send')->add($tbarcode_trace_send);
                if ($rs === false) {
                    $this->log('保存发送流水失败' . M()->_sql());
                }
            } else { // 发送成功，提交事务
                M()->commit();
                $tbarcode_trace_send['status'] = '1';
                $rs                            = M('tbarcode_trace_send')->add($tbarcode_trace_send);
                if ($rs === false) {
                    $this->log('保存发送流水失败' . M()->_sql());
                }
            }

            if (empty($this->mid)) {
                $this->mid = isset($activity_info['m_id']) ? $activity_info['m_id'] : '';
            }

            if ($this->mid){
                // 增加tmarket_info 的send_count ++
                $where          = "id = " . $this->mid;
                $marketing_info = M('tmarketing_info')->where($where)->find();
                if ($marketing_info["batch_type"] == '14') { // 礼品派发
                    M('tmarketing_info')->where($where)->setInc("send_count", 1);
                }
            }

            // 第五步,记流水
            $barcode_trace['req_seq']    = $TransactionID;
            $barcode_trace['sys_seq']    = $resp_array['SubmitVerifyRes']['MessageID'];
            $barcode_trace['request_id'] = $this->request_id;
            $barcode_trace['node_id']    = $this->node_id;
            $barcode_trace['user_id']    = $this->user_id;
            $barcode_trace['pos_id']     = $this->pos_id;
            $barcode_trace['batch_no']   = $this->batch_no;
            $barcode_trace['phone_no']   = $this->phone_no;
            $barcode_trace['trans_time'] = date('YmdHis');
            $barcode_trace['ret_code']   = $ret_msg['StatusCode'];
            $barcode_trace['ret_desc']   = $ret_msg['StatusText'];
            $barcode_trace['trans_type'] = '0001';
            if (($ret_msg['StatusCode'] === '0000') || ($ret_msg['StatusCode'] === '0001')) {
                $barcode_trace['status'] = '0';
            } else {
                $barcode_trace['status'] = '3';
            }
            $barcode_trace['data_from']      = $this->data_from;
            $barcode_trace['batch_id']       = $this->batch_id;
            $barcode_trace['assist_number']  = $resp_array['SubmitVerifyRes']['AssistNumber'];
            $barcode_trace['barcode_bmp']    = $resp_array['SubmitVerifyRes']['Wbmp'];
            $barcode_trace['mms_title']      = $activity_info["info_title"];
            $barcode_trace['sms_text']       = $activity_info['sms_text'];
            $barcode_trace['mms_text']       = $activity_info['use_rule'];
            $barcode_trace['print_text']     = $activity_info['print_text'];
            $barcode_trace['prize_key']      = $this->batch_desc;
            $barcode_trace['begin_time']     = $begin_time;
            $barcode_trace['end_time']       = $end_time;
            $barcode_trace['price']          = $activity_info['batch_amt'];
            $barcode_trace['b_id']           = $this->batch_info_id;
            $barcode_trace['goods_id']       = $activity_info['goods_id'];
            $barcode_trace['channel_id']     = $this->channel_id;
            $barcode_trace['pact_price']     = $goods_info['settle_price'];
            $barcode_trace['valid_times']    = $activity_info["validate_times"];
            $barcode_trace['short_url_info'] = $resp_array['SubmitVerifyRes']['ShortUrlInfo'];
            // $barcode_trace['df_openid'] = $this->df_openid;
            $bar_id = $rs = M('TbarcodeTrace')->add($barcode_trace);
            if (!$rs) {
                $this->log(print_r($barcode_trace, true));
                $this->log("记录流水信息[tbarcode_trace]失败");
            }
            $openid_df = $this->df_openid;
            if (!empty($openid_df) && !empty($rs)) {
                // 记录trace表成功，df机构需写入tfb_dftrace_relation
                $df_arr = [
                        'barcode_id' => $rs,
                        'df_openid'  => $this->df_openid,
                        'add_time'   => date('YmdHis'),
                ];
                $df_res = M("tfb_dftrace_relation")->add($df_arr);
                if (!$df_res) {
                    $this->log(print_r($df_arr, true));
                    $this->log("记录流水信息[tfb_dftrace_relation]失败");
                }
            }
            // 记录凭证流水与用户openid的关系
            $extData = [
                    'bar_id'    => $rs,
                    'node_id'   => $this->node_id,
                    'member_id' => $this->getMemberId(),
            ];
            if (!empty($this->wx_open_id)) {
                $extData['openid'] = $this->wx_open_id;
            }
            $extRes = M("tbarcode_trace_ext")->add($extData);
            if (!$extRes) {
                $this->log(print_r($extData, true));
                $this->log("记录流水信息[tbarcode_trace_ext]失败");
            }

            // 记录行为数据
            // 添加会员行为记录
            if ($this->getMemberId() != '') {
                if (empty($this->MemberBehaviorModel)) {
                    $this->MemberBehaviorModel = D('MemberBehavior');
                }
                $bflag = $this->MemberBehaviorModel->addBehaviorType(
                        $this->getMemberId(),
                        $this->node_id,
                        2,
                        '',
                        $activity_info['m_id']
                );
                if ($bflag === false) {
                    $this->log(
                            "===MEM_DEBUG===记录会员行为数据失败[凭证下发]member_id[{$this->getMemberId()}],node_id[{$this->node_id}],1"
                    );
                }
            }

            // 翼惠宝下发通知短信
            if ($this->fb_yhb_flag && $barcode_trace['status'] == '0') {
                try {
                    if (empty($this->YhbSmsService)) {
                        $this->YhbSmsService = D('YhbSms', 'Service');
                    }
                    $this->YhbSmsService->sendBarcodeSms($bar_id);
                } catch (Exception $e) {
                    log_write("翼惠宝下发短信通知失败！" . $e->getMessage());
                    // $this->log("翼惠宝下发短信通知失败！".$e->getMessage());
                }
            }
            // 和包商品存入和包
            if ($this->fb_cmpay_flag == 'yes' && $barcode_trace['status'] == '0') {
                if (empty($this->CMPAYService)) {
                    $this->CMPAYService = D('CMPAY', 'Service');
                }
                $cardType = 1; // 暂时为1，后面要根据卡券类型做转换 TODO
                $ret      = $this->CMPAYService->cmpayAddCard(
                        $barcode_trace['phone_no'],
                        $barcode_trace['request_id'],
                        $barcode_trace['assist_number'],
                        $activity_info['batch_short_name'],
                        $cardType,
                        substr($barcode_trace['begin_time'], 0, 8),
                        substr($barcode_trace['end_time'], 0, 8),
                        $activity_info['use_rule'],
                        $bar_id
                );
                if (!$ret) {
                    log_write("和包存入失败 cmpayAddCard error");
                }
            }

            if (!$resp_array || ($ret_msg['StatusCode'] != '0000' && $ret_msg['StatusCode'] != '0001')) {
                $err_num++;
                log_write('send error:$resp_array:' . var_export($resp_array, 1));
                log_write('send error:$ret_msg:' . var_export($ret_msg, 1));
                if (isset($resp_array['resp_desc']) && $resp_array['resp_desc']) {
                    $err_msg = $resp_array['resp_desc'];
                } elseif (isset($ret_msg['StatusText']) && $ret_msg['StatusText']) {
                    $err_msg = $ret_msg['StatusText'];
                }
                log_write('err_msg: $resp_array' . var_export($resp_array, 1));
                log_write('err_msg: $ret_msg' . var_export($ret_msg, 1));
            }
        }
        // 成功数
        $succ_num = $this->send_num - $err_num;
        if ($this->batch_info_id == null) {
            $this->batch_info_id = 0;
        }
        // 第六步，记录发码统计
        $where         = "NODE_ID ='" . $this->node_id . "' and BATCH_NO ='" . $this->batch_no . "' and POS_ID ='" . $this->pos_id . "'  and b_id = " . $this->batch_info_id . " and TRANS_DATE ='" . date(
                        'Y-m-d'
                ) . "' and b_id = " . $this->batch_info_id;
        $pos_day_count = M('TposDayCount')->where($where)->find();
        if (!$pos_day_count) {
            $pos_day_count['node_id']    = $this->node_id;
            $pos_day_count['pos_id']     = $this->pos_id;
            $pos_day_count['batch_no']   = $this->batch_no;
            $pos_day_count['b_id']       = $this->batch_info_id;
            $pos_day_count['trans_date'] = date('Y-m-d');
            $pos_day_count['send_num']   = $succ_num;
            $pos_day_count['send_amt']   = $activity_info['batch_amt'] * $succ_num;
            $pos_day_count['verify_num'] = 0;
            $pos_day_count['verify_amt'] = 0;
            $pos_day_count['cancel_num'] = 0;
            $pos_day_count['cancel_amt'] = 0;
            $pos_day_count['goods_id']   = $activity_info['goods_id'];
            $rs                          = M('TposDayCount')->add($pos_day_count);
            if ($rs === false) {
                $this->log("记录统计信息[tpos_day_count]失败 " . M()->_sql() . M()->getDbError());
            }
        } else {
            $new_day_count             = array();
            $new_day_count['send_num'] = $pos_day_count['send_num'] + $succ_num;
            $new_day_count['send_amt'] = $pos_day_count['send_amt'] + $activity_info['batch_amt'] * $succ_num;
            $rs                        = M('TposDayCount')->where($where)->save($new_day_count);
            if ($rs === false) {
                $this->log("更新统计信息[tpos_day_count]失败" . M()->_sql());
            }
        }

        // 应答
        // $resp_desc ='发码数：'.$this->send_num.' 成功数:'.$succ_num;
        $resp_desc = '发码成功!';
        if ($err_num > 0) {
            $resp_desc = '发送失败原因: ' . $err_msg;
            $this->returnError($resp_desc);
        }

        $this->returnSuccess(
                $resp_desc,
                [
                        "all_num"        => $this->send_num,
                        "success_num"    => $succ_num,
                        "error_num"      => $err_num,
                        "short_url_info" => $barcode_trace['short_url_info'],
                        'barcode_id'     => $bar_id,
                ]
        );
    }

    private function check()
    {
        $this->node_id    = $_REQUEST['node_id'];
        $this->phone_no   = $_REQUEST['phone_no'];
        $this->request_id = $_REQUEST['request_id'];
        if ($this->pos_id == null) {
            $this->pos_id = '0000000000';
        }
        if ($this->node_id == '' || $this->phone_no == '' || $this->request_id == '') {
            return false;
        }

        return true;
    }

    private function getGlide()
    {
        $sql    = "SELECT _nextval('phone_bills_trace') as u FROM DUAL";
        $fruit  = M()->query($sql);
        $number = $fruit[0]['u'];

        return str_pad($number, 20, "0", STR_PAD_LEFT);
    }

    /**
     * 获取会员ID
     */
    private function getMemberId()
    {
        if ($this->member_id) {
            return $this->member_id;
        }

        if (strlen($this->phone_no) != 11 || $this->phone_no == '13900000000') {
            $this->member_id = '';

            return false;
        }

        /*
         * $marketingInfo = M() ->table('tmarketing_info a, tbatch_info b')
         * ->where("a.id = b.m_id and b.id = {$this->batch_id}") ->field('a.*')
         * ->find();
         */
        $condition     = $this->phone_no;
        $conditionType = 1; // 1手机 2翼码授权openid 3商户授权openid

        $option = ['channel_id' => $this->channel_id, 'batch_id'   => $this->mid];
        if (empty($this->MemberInstallModel)) {
            $this->MemberInstallModel = D('MemberInstall');
        }
        $result = $this->MemberInstallModel->wxTermMemberFlag(
                $this->node_id,
                $condition,
                $conditionType,
                true,
                $option
        );

        if ($result === false) {
            $this->log("getMemberInfo fail! node_id[{$this->node_id}] condition[{$condition}]");
        }

        $this->log("===MEM_DEBUG===get_member_info " . print_r($result, true));
        $this->member_id = $result['id'];
        return true;
    }
}