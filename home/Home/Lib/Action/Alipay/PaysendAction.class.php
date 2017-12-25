<?php

class PaysendAction extends BaseAction {
    // public $_authAccessMap = '*';
    private $BATCH_TYPE = 54;

    private $fmscharge_id = 3091;
    // 付满送模块id
    private $storageDir;

    private $useStopDir = 'useStop';
    // 二维码停用目录
    /**
     *
     * @var PayGiveClerkModel
     */
    private $PayGiveClerkModel;

    private $StoreList = array();
    // 门店列表
    public function _initialize() {
        parent::_initialize();
        // 付满送模块权限控制
        $fms_chagre = M('tnode_charge')->where(
            array(
                'status' => 0, 
                'node_id' => $this->node_id, 
                'charge_id' => $this->fmscharge_id, 
                'charge_level' => 1))->find();
        $this->assign('fms_chagre', $fms_chagre);
        //$codePay = false;
        $codePay = true;
        $openfums = false;
        $payInfo = M('tzfb_offline_pay_info')->field(
            'pay_type,check_status,status')
            ->where(array(
            'node_id' => $this->node_id))
            ->select();
        /*foreach ($payInfo as $val) {
            if ($val['status'] == '1') {
                $openfums = true;
                if (in_array($val['pay_type'], 
                    array(
                        0, 
                        1, 
                        2, 
                        5)))
                    $codePay = true;
            }
        }*/
        $this->open_status = $codePay;
        $this->assign('open_status', $codePay);
        $this->assign('openfums', $openfums);
        
        // 新增营业员用
        $this->storageDir = realpath(APP_PATH . 'Upload/Paysend') . '/' .
             $this->nodeId;
        if (! is_dir($this->storageDir)) {
            $bool = mkdir($this->storageDir, 0777, true);
        }
        
        $this->PayGiveClerkModel = D('PayGiveClerk');
    }
    // 发送邮件
    public function applypayemail(
        $to_array = array('order@imageco.com.cn','shaomin@imageco.com.cn','qiuzd@imageco.com.cn')) {
        // 2、邮件发至邮箱“yangyang@imageco.com.cn” 邮件标题“付满送模块购买申请” 邮件正文“商户名称：XXX
        // ；申请付费购买付满送模块；
        $name = $this->nodeInfo['contact_name'];
        $phone = $this->nodeInfo['contact_phone'];
        if (is_array($to_array) && ! empty($to_array)) {
            foreach ($to_array as $value) {
                $res = send_mail(
                    array(
                        'email' => $value, 
                        'subject' => '付满送模块购买申请', 
                        'content' => '商户名称：' . $this->nodeInfo['node_name'] .
                             ' ；申请付费购买付满送模块;<br/>请尽快联系客户，完成开通。联系人：'.$name.'；联系电话：'.$phone));
                if(!$res['sucess']){
                    $this->error($res['msg']);
                }
            }
            $this->success('发送成功');
        } else {
            $res = send_mail(
                array(
                    'email' => 'yangyang@imageco.com.cn', 
                    'subject' => '付满送模块购买申请', 
                    'content' => '商户名称：' . $this->nodeInfo['node_name'] .
                         ' ；申请付费购买付满送模块;<br/>请尽快联系客户，完成开通。联系人：'.$name.'；联系电话：'.$phone));
            if(!$res['sucess']){
                $this->error($res['msg']);
            }
            $this->success('发送成功');
        }
    }

    public function notice() {
        if (IS_AJAX && ! empty($_REQUEST['save'])) {
            $winModel = M('tpop_window_control');
            $data = array(
                'node_id' => $this->node_id, 
                'window_id' => 13);
            $result = $winModel->where($data)->find();
            if (! $result) {
                $addResult = $winModel->add($data);
            }
        } else {
            $winModel = M('tpop_window_control');
            $data = array(
                'node_id' => $this->node_id, 
                'window_id' => 13);
            $result = $winModel->where($data)->find();
            if (empty($result))
                $this->assign('winshow', true);
            else
                $this->assign('winshow', false);
        }
    }
    // 首页
    public function index() {
        // 取统计数
        $map = array(
            'a.type' => 7, 
            'a.node_id' => $this->node_id);
        // 'a.status'=>1,
        // 'a.begin_time'=>array('elt',date("YmdHis")),
        // 'a.end_time'=>array('egt',date("YmdHis")),

        $statInfo = M()->table("tchannel a")->where($map)
            ->field(
            'sum(a.verify_count) sumVerify,sum(a.cj_count) sumCj,sum(a.send_count) sumSend')
            ->find();
        // 取当前活动图表数据
        $map = array(
            'a.type' => 7, 
            'a.node_id' => $this->node_id, 
            'a.begin_time' => array(
                'elt', 
                date("YmdHis")), 
            'a.end_time' => array(
                'egt', 
                date("YmdHis")), 
            'a.status' => 1);
        $channelInfoList = M()->table(
            array(
                'tchannel' => 'a', 
                'tbatch_channel' => 'tc'))
            ->where('tc.channel_id = a.id')
            ->where($map)
            ->field(
            'sum(a.verify_count) sumVerify,sum(a.cj_count) sumCj,
        sum(a.send_count) sumSend, a.*')
            ->group('a.id')
            ->order('join_flag,id,status asc')
            ->limit(2)
            ->select();
        
        $channelList = array();
        foreach ($channelInfoList as $channelInfo) {
            $channel_jsChartDataVerify = array(); // fms验证量
            $channel_jsChartDataCj = array(); // fms参与量
            $channel_jsChartDataSend = array(); // fms发放量
            /*
             * $channel_data =array(
             * 'Verify'=>array(date('Ymd')=>0,date('Ymd',strtotime("-1
             * day"))=>0), 'Cj'=>array(date('Ymd')=>0,date('Ymd',strtotime("-1
             * day"))=>0), 'Send'=>array(date('Ymd')=>0,date('Ymd',strtotime("-1
             * day"))=>0) );
             */
            $whe = array(
                // 'batch_id'=>$channelInfo['batch_id'],
                'channel_id' => $channelInfo['id'], 
                'node_id' => $this->node_id);
            $channelStatis = M('tdaystat')->where($whe)
                ->field(
                "day,sum(verify_count) sumVerify,sum(send_count) sumSend,sum(cj_count) sumCj")
                ->group("day")
                ->order('day desc')
                ->select();
            if(!empty($channelStatis))
            foreach ($channelStatis as $v) {
                $channel_jsChartDataVerify[] = array(
                    $v['day'], 
                    (int) $v['sumVerify']);
                $channel_jsChartDataSend[] = array(
                    $v['day'], 
                    (int) $v['sumSend']);
                $channel_jsChartDataCj[] = array(
                    $v['day'], 
                    (int) $v['sumCj']);
            }
            
            $channelList[] = array(
                'channel_info' => $channelInfo, 
                'channel_jsChartDataVerify' => json_encode(
                    $channel_jsChartDataVerify), 
                'channel_jsChartDataSend' => json_encode(
                    $channel_jsChartDataSend), 
                'channel_jsChartDataCj' => json_encode($channel_jsChartDataCj));
        }
        $userJurisdiction = get_node_info($this->node_id, 'wc_version');
        /*
         * if($userJurisdiction == 'v0'){
         * $this->assign('userSta',$userJurisdiction);
         * $this->display('introduction'); exit;
         */
        // }else{
        // 条码支付判断
        /*判断是否有付满送*/
        $map = array(
            'node_id'   => $this->nodeId,
            'charge_id' => '3091',
            'status'    => '0'
        );
        $map['begin_time'] = array('ELT',date('YmdHis'));
        $map['end_time']   = array('EGT',date('YmdHis'));
        $fmsChargeInfo = M('tnode_charge')->where($map)->find();
        if(empty($fmsChargeInfo)){
            $this->assign('userSta','noPay');
            $this->display('introduction');
            exit();
        }
        $this->assign('userSta', $userJurisdiction);
        // 模块有效期
        $fms_chagre = M('tnode_charge')->where(
            array(
                'status' => 0, 
                'node_id' => $this->node_id, 
                'charge_id' => $this->fmscharge_id, 
                'charge_level' => 1))->find();
        // }
        $this->assign('fmsdate', $fms_chagre['end_time']);
        $this->assign('channelList', $channelList);
        $this->assign('statInfo', $statInfo);
        $this->display();
    }
    // 创建新活动
    public function addActive() {
        //$codePay = 'noPay';
        $codePay = 'hasPay';
        $payInfo = M('tzfb_offline_pay_info')->field(
            'pay_type,check_status,status')
            ->where(array(
            'node_id' => $this->node_id))
            ->select();
        /*foreach ($payInfo as $val) {
            if ($val['status'] == '1') {
                $codePay = '1';
                break;
            }
        }*/
        if ($codePay == 'noPay') {
            $this->assign('userSta', $codePay);
            $this->display('introduction');
            exit();
        }
        $tid=null;
        $rs['store_num'] = 0;
            if (! empty($tid)) {
                $rs = M('tchannel')->where(
                    array(
                        'node_id' => $this->node_id, 
                        'id' => $tid, 
                        'type' => 7))->find();
                // 总共多少门店
                $ids = M('tpay_give_store_list')->field(
                    array(
                        'group_concat(store_id)' => 'ids'))
                    ->where(
                    array(
                        'channel_id' => $tid))
                    ->find();
                $ids = trim($ids['ids'], ',');
                if (! empty($ids)) {
                    $store_num = count(explode(',', $ids));
                    $rs['store_num'] = $store_num > 0 ? $store_num : 0;
                }
                $rs['ids'] = $ids;
            }
            $this->assign('data', $rs);
            $this->display();
    }
    // 增加tpay_give_store_list表数据
    function add_store_list($tid, $Storeslist) {
        if (! empty($Storeslist)) {
            $sql = 'insert into tpay_give_store_list(channel_id,store_id) values';
            $_arr = explode(',', trim($Storeslist, ','));
            foreach ($_arr as $v) {
                $sql .= '(' . $tid . ',"' . $v . '"),';
            }
            return $rs_addstore = M()->execute(trim($sql, ','));
        } else
            return true;
    }

    function addToPrizeItem() {
        // 添加奖品
        // 查询准备添加的奖品是否是这个机构下的
        $prizeId = $_REQUEST['prizeId'];
        $goodsInfo = M('tgoods_info')->where(
            array(
                'node_id' => $this->node_id, 
                'goods_id' => $prizeId))->find();
        if (! $goodsInfo) {
            $this->error('传入参数有误', '', true);
        }
        $verify_begin_date = $goodsInfo['begin_time'];
        $verify_end_date = $goodsInfo['end_time'];
        $verify_begin_type = '0';
        $this->assign('goods_remain_num', $goodsInfo['remain_num']);
        $goods_begin_date = $goodsInfo['begin_time'];
        $goods_end_date = $goodsInfo['end_time'];
        if ($verify_begin_type == 0) {
            $verify_begin_date = substr($verify_begin_date, 0, 8);
            $verify_end_date = substr($verify_end_date, 0, 8);
        }
        $this->assign('verify_begin_date', $verify_begin_date);
        $this->assign('verify_end_date', $verify_end_date);
        $this->assign('verify_begin_type', $verify_begin_type);
        // $this->assign('b_id', $b_id);
        // $this->assign('m_id', $m_id);
        // $this->assign('prizeCateId', $prizeCateId);
        $this->assign('prizeId', $prizeId);
        $this->assign('goods_begin_date', $goods_begin_date);
        $this->assign('goods_end_date', $goods_end_date);
        $this->display('addToPrizeItem');
    }

    function taddActive() {
        if (empty($_REQUEST['tid']))
            redirect(U('Alipay/Paysend/addActive'));
        $count = M()->table(
            array(
                'tchannel' => 't', 
                'tmarketing_info' => 'ti'))
            ->where(
            array(
                't.node_id' => $this->node_id, 
                't.type' => 7, 
                't.id' => $_REQUEST['tid']))
            ->where('t.batch_id = ti.id')
            ->count();
        if ($count > 0)
            $this->error('活动已经存在！');
        $nodeInfo = get_node_info($this->node_id);
        $node_name = $nodeInfo['node_name'];
        $node_img = get_upload_url($nodeInfo['head_photo']);
        $this->assign('node_logo', $node_img);
        $this->assign('logo_value', $nodeInfo['head_photo']);
        $this->assign('node_name', $node_name);
        $this->assign('tid', $_REQUEST['tid']);
        $this->display('addActive_step2');
    }
    // 添加奖品第一步
    public function addAward() {
        $m_id = I('m_id', '');
        $prizeCateId = I('prizeCateId', '');
        $callback = I('callback', '');
        $b_id = I('b_id', '');
        if (! $b_id) { // 如果没有b_id表示添加奖品
                       // 添加奖品的第一步，选择卡券（或者红包）//具体产品自己还没设计好，先不管让他选择卡券
            $this->redirect('Common/SelectJp/indexNew', 
                array(
                    'callback' => $callback, 
                    'availableTab' => '1,5'));    // 有些活动可选的券的品种是指定的(例如付满送只能选卡券和卡券),
                                                 // 把指定的可选来源传过去，用“,”分割
                                                 // 'next_step' =>
                                                 // urlencode(U('Common/SelectJp/addToPrizeItem',
                                                 // array('m_id' => $m_id,
                                                 // 'prizeCateId' => $prizeCateId)))
                                                 // 给个参数让按钮显示成下一步
        }
    }
    /*public function addfriend_taick() {
        $m_id = I('m_id', '');
        $prizeCateId = I('prizeCateId', '');
        $callback = I('callback', '');
        $b_id = I('b_id', '');
        if (! $b_id) { // 如果没有b_id表示添加奖品
                       // 添加奖品的第一步，选择卡券（或者红包）//具体产品自己还没设计好，先不管让他选择卡券
            $this->redirect('Common/SelectJp/indexNew', 
                array(
                    'callback' => $callback, 
                    'availableTab' => '5'));    // 有些活动可选的券的品种是指定的(例如付满送只能选卡券和卡券),
                                                 // 把指定的可选来源传过去，用“,”分割
                                                 // 'next_step' =>
                                                 // urlencode(U('Common/SelectJp/addToPrizeItem',
                                                 // array('m_id' => $m_id,
                                                 // 'prizeCateId' => $prizeCateId)))
                                                 // 给个参数让按钮显示成下一步
        }
    }*/

    public function csv_h($filename) {
        header("Content-type:text/csv");
        header("Content-Type: application/force-download");
        header(
            "Content-Disposition: attachment; filename=" .
                 iconv("UTF-8", "gbk", $filename) . ".csv");
        header('Expires:0');
        header('Pragma:public');
    }

    public function downloadCsvData($csv_data = array(), $arrayhead = array()) {
        $csv_string = null;
        $csv_row = array();
        if (! empty($arrayhead)) {
            $current = array();
            foreach ($arrayhead as $item) {
                
                $current[] = iconv("UTF-8", "gbk", $item);
            }
            $csv_row[] = trim(implode(",", $current), ',');
        }
        foreach ($csv_data as $key => $csv_item) {
            $current = array();
            foreach ($csv_item as $item) {
                
                $current[] = iconv("UTF-8", "gbk", $item);
            }
            $csv_row[] = trim(implode(",", $current), ',');
        }
        $csv_string = implode("\r\n", $csv_row);
        echo $csv_string;
    }
    
    // 卡券明细
    function senddatail() { 
        $label_id = $_REQUEST['batchid'];
        $label_idarr=explode(',', $label_id);
        $label_batch_typearr=explode(',', $_REQUEST['batch_type']);
        foreach($label_idarr as $k=>$v){
              if (!empty($v)) {
                    $scausewhere.='or (a.batch_id ='.$v.' and a.batch_type='.$label_batch_typearr[$k] .')';
              }
               
        }
        if(!empty($scausewhere))
            $scausewhere=' and  ('.trim($scausewhere,'or').' ) ';
        $fromType = array(
            '100' => '条码支付', 
            '101' => '条码支付', 
            '102' => '条码支付', 
            '103' => '通联支付');
        $paytype = array(
            '1' => '支付宝', 
            '2' => '微信', 
            '3' => '翼支付', 
            '4' => '通联支付', 
            '5' => 'QQ钱包', 
            '6' => '和包', 
            '7' => '现金/刷卡');
            // if(!empty($_REQUEST['class_type']) && $_REQUEST['class_type']==2){
            $sql = "(SELECT    b.pay_type,tgc.clerk_name,  tr.wx_open_id  mobile,
                @gettype:= '卡券领取' as gettype,c.zfb_buyer_logon_id,
                 IFNULL(code_type, '103') code_type, b.`amt` exchange_amt, pos_name, 
                 b.`add_time` trans_time ,
                 tbi.batch_short_name
                from  tbatch_info  tbi,tbatch_channel a,tbarcode_trace tr
                LEFT JOIN twx_assist_number twxa ON tr.request_id = twxa.request_id
                LEFT JOIN tpay_give_order b ON twxa.relation_id = b.pay_token
                LEFT JOIN tpay_give_clerk tgc ON b.clerk_id = tgc.clerk_id  
                LEFT JOIN tzfb_offline_pay_trace c  ON b.order_id = c.zfb_out_trade_no  
                LEFT JOIN tpos_info d ON b.pos_id = d.pos_id
                WHERE   a.channel_id = ".$_REQUEST['channid'].$scausewhere." 
                 and  a.batch_id = tbi.m_id and b.status =1 and tr.b_id=tbi.id  
                 and (c.trans_type <> 'R' or c.trans_type is  null)
                  ORDER BY c.trans_time DESC)";
            // }else{
            $sql.= " union  all  (SELECT  b.pay_type,tgc.clerk_name,a.mobile ,
                @gettype:=if(tpr.receive_wx_openid=tpi.wx_open_id or  tpr.id is null or tpr.id ='','卡券领取','卡券分享') 
                 as gettype,c.zfb_buyer_logon_id, IFNULL(code_type, '103') code_type, b.`amt` exchange_amt,
                  pos_name, b.`add_time` trans_time, f.batch_short_name
    		FROM 	tcj_trace a
    		LEFT JOIN tpay_give_order b ON a.pay_token = b.pay_token
    		LEFT JOIN tpay_give_clerk tgc ON b.clerk_id = tgc.clerk_id 
    		LEFT JOIN tzfb_offline_pay_trace c  ON b.order_id = c.zfb_out_trade_no  
    		LEFT JOIN tpos_info d ON b.pos_id = d.pos_id
    		LEFT JOIN tcj_batch e ON a.rule_id = e.id
    		LEFT JOIN tbatch_info f ON e.b_id = f.id
    		LEFT JOIN tshare_prize_receive_trace tpr ON tpr.cj_trace_id = a.id
    		LEFT JOIN tshare_prize_info tpi ON tpr.share_id = tpi.id
    		  WHERE a.status=2 and  a.channel_id = ".$_REQUEST['channid'].$scausewhere." 
                  and (c.trans_type <> 'R' or c.trans_type is  null) ORDER BY c.trans_time DESC)";
           // }
// ((c.status=0  and c.trans_type='T') or
                // ( c.status is null  and c.trans_type is null  and  b.pay_type=7))  
              //   and
        

        $count = M()->query('select count(*) c1 from (' . $sql . ') c');
        
        if (!empty($_REQUEST['down']) && $_REQUEST['down'] == 1) {
            $list = M()->query($sql);
            foreach ($list as $k => $vo) {
                $list_bak[$k]['mobile'] = $vo['mobile'] ? $vo['mobile'] . "\t" : '未知';
                $list_bak[$k]['gettype'] = $vo['gettype'];
                $list_bak[$k]['zfb_buyer_logon_id'] = $vo['zfb_buyer_logon_id'] ? $vo['zfb_buyer_logon_id'] .
                     "\t" : '未知';
                $list_bak[$k]['fromType'] = $paytype[$vo['pay_type']];
                $list_bak[$k]['exchange_amt'] = ($vo['exchange_amt'] > 0) ? $vo['exchange_amt'] : 0;
                $list_bak[$k]['pos_name'] = $vo['pos_name'];
                $list_bak[$k]['trans_date'] = date('Y-m-d H:i', 
                    strtotime($vo['trans_time'])) . "\t";
                $list_bak[$k]['clerk_name'] = $vo['clerk_name'];
                $list_bak[$k]['batch_short_name'] = $vo['batch_short_name'];
            }
            $this->csv_h('付满送/' . $_REQUEST['name']);
            $this->downloadCsvData($list_bak, 
                array(
                    '领取手机号', 
                    '领取方式', 
                    '付款用户', 
                    '付款方式', 
                    '付款金额', 
                    '付款门店', 
                    '付款时间', 
                    '所属营业员', 
                    '获取奖品名称'));
        } else {
            import("ORG.Util.Page");
            $p = new Page($count[0]['c1'], 10);
            $page = $p->show();
            $list = M()->query(
                $sql . " limit " . $p->firstRow . ',' . $p->listRows);
            $this->assign('page', $page);
            $this->assign('list', $list);
            $this->assign('fromType', $fromType);
            $this->assign('paytype', $paytype);
            $this->assign('downparme', array('class_type'=>$_REQUEST['class_type'],
                                'down'=>1,
                                 'batch_type'=>$_REQUEST['batch_type'],
                                 'filename'=>$_REQUEST['filename'],
                                 'batchid'=>$_REQUEST['batchid'],
                                 'channid'=>$_REQUEST['channid']));
            
            $this->display();
        }
    }
    
    // 验证码数据
    function verifydatail() { // clerk_id
        $label_id = $_REQUEST['batchid'];
        $label_idarr=explode(',', $label_id);
        $label_batch_typearr=explode(',', $_REQUEST['batch_type']);
        foreach($label_idarr as $k=>$v){
              if (!empty($v)) {
                    $scausewhere.='or (a.batch_id ='.$v.' and a.batch_type='.$label_batch_typearr[$k] .')';
              }
               
        }
        if(!empty($scausewhere))
            $scausewhere=' and  ('.trim($scausewhere,'or').' ) ';
        $fromType = array(
            '100' => '条码支付', 
            '101' => '条码支付', 
            '102' => '条码支付', 
            '103' => '通联支付');
        $paytype = array(
            '1' => '支付宝', 
            '2' => '微信', 
            '3' => '翼支付', 
            '4' => '通联支付', 
            '5' => 'QQ钱包', 
            '6' => '和包', 
            '7' => '现金/刷卡');
            $sql = "( SELECT   b.pay_type,tgc.clerk_name ,tr.wx_open_id  mobile,
                @gettype:='卡券领取' as gettype,c.zfb_buyer_logon_id,
                 IFNULL(code_type, '103') code_type, b.`amt` exchange_amt, pos_name, 
                 b.`add_time` trans_time, tbi.batch_short_name
                from  tbatch_info  tbi,tbatch_channel a,tbarcode_trace tr
                LEFT JOIN twx_assist_number twxa ON tr.request_id = twxa.request_id
                LEFT JOIN tpay_give_order b ON twxa.relation_id = b.pay_token
                LEFT JOIN tpay_give_clerk tgc ON b.clerk_id = tgc.clerk_id  
                LEFT JOIN tzfb_offline_pay_trace c  ON b.order_id = c.zfb_out_trade_no  
                LEFT JOIN tpos_info d ON b.pos_id = d.pos_id
                WHERE   a.channel_id = ".$_REQUEST['channid'].$scausewhere." and  
                 a.batch_id = tbi.m_id and b.status =1 and tr.b_id=tbi.id  
                  and (c.trans_type <> 'R' or c.trans_type is  null)   AND tr.use_status IN ('1','2') 
                   ORDER BY c.trans_time DESC)";
        $sql .= " union  all  (SELECT    b.pay_type,tgc.clerk_name,a.mobile,
                @gettype:=if(tpr.receive_wx_openid=tpi.wx_open_id or tpr.id 
                is null or tpr.id ='','卡券领取','卡券分享')  as gettype, c.zfb_buyer_logon_id,
                IFNULL(b.pay_type, '103') code_type,b.`amt` exchange_amt,pos_name,
				b.`add_time` trans_time,f.batch_short_name 
				FROM tcj_trace a
				LEFT JOIN tpay_give_order b ON a.pay_token = b.pay_token 
				LEFT JOIN tpay_give_clerk tgc ON b.clerk_id = tgc.clerk_id 
				LEFT JOIN tzfb_offline_pay_trace c  ON b.order_id = c.zfb_out_trade_no  
				LEFT JOIN tpos_info d ON b.pos_id = d.pos_id
				LEFT JOIN tcj_batch e ON a.rule_id = e.id 
				LEFT JOIN tbatch_info f ON e.b_id = f.id 
				LEFT JOIN tbarcode_trace g ON a.request_id = g.request_id 
				LEFT JOIN tshare_prize_receive_trace tpr ON tpr.cj_trace_id = a.id
				LEFT JOIN tshare_prize_info tpi ON tpr.share_id = tpi.id
				WHERE a.status=2 and  a.channel_id = ".$_REQUEST['channid'].$scausewhere."  
                 and (c.trans_type <> 'R' or c.trans_type is  null)
				AND g.use_status IN ('1','2') 
				order by c.trans_time desc) "; //
        $count = M()->query('select count(*) c1 from (' . $sql . ') c');
        if (!empty($_REQUEST['down'])&&$_REQUEST['down'] == 1) {
            $list = M()->query($sql);
            foreach ($list as $k => $vo) {
                $list_bak[$k]['mobile'] = $vo['mobile'] ? $vo['mobile'] . "\t" : '未知';
                $list_bak[$k]['gettype'] = $vo['gettype'];
                $list_bak[$k]['zfb_buyer_logon_id'] = $vo['zfb_buyer_logon_id'] ? $vo['zfb_buyer_logon_id'] .
                     "\t" : '未知';
                $list_bak[$k]['fromType'] = $paytype[$vo['pay_type']];
                $list_bak[$k]['exchange_amt'] = ($vo['exchange_amt'] > 0) ? $vo['exchange_amt'] : 0;
                $list_bak[$k]['pos_name'] = $vo['pos_name'];
                $list_bak[$k]['trans_date'] = date('Y-m-d H:i', 
                    strtotime($vo['trans_time'])) . "\t";
                $list_bak[$k]['clerk_name'] = $vo['clerk_name'];
                $list_bak[$k]['batch_short_name'] = $vo['batch_short_name'];
            }
            $this->csv_h('付满送/' . $_REQUEST['name']);
            $this->downloadCsvData($list_bak, 
                array(
                    '领取手机号', 
                    '领取方式', 
                    '付款用户', 
                    '付款方式', 
                    '付款金额', 
                    '付款门店', 
                    '付款时间', 
                    '所属营业员', 
                    '获取奖品名称'));
        } else {
            import("ORG.Util.Page");
            $p = new Page($count[0]['c1'], 10);
            $page = $p->show();
            $list = M()->query(
                $sql . " limit " . $p->firstRow . ',' . $p->listRows);
            $this->assign('page', $page);
            $this->assign('list', $list);
            $this->assign('paytype', $paytype);
            $this->assign('fromType', $fromType);
            $this->assign('batchid', $label_id);
            $this->assign('downparme', array('class_type'=>$_REQUEST['class_type'],
                                'down'=>1,
                                 'batch_type'=>$_REQUEST['batch_type'],
                                 'filename'=>$_REQUEST['filename'],
                                 'batchid'=>$_REQUEST['batchid'],
                                 'channid'=>$_REQUEST['channid']));
            $this->display();
        }
    }

    private function _editMarketInfo($data, $m_id = '',$k) {
        global $data_bak;
        if (empty($m_id)) {
            /**
             * 新增卡券分享
             */
            $sharednum = $data_bak['sharednum'][$k];
            if (!empty($sharednum) && is_numeric($sharednum)) {
                if ($_POST['goods_count'] < $sharednum) {
                    $this->error('卡券分享的数量不能大于奖品总数');
                }
                $isshared=1;
            }
            
            $data = array(
                'name' => $data['name'], 
                'node_id' => $this->node_id, 
                'node_name' => $data['node_name'], 
                'wap_info' => $data['introduce'], 
                'log_img' => $data['node_logo'], 
                'start_time' => $data['act_time_from'], 
                'end_time' => $data['act_time_to'], 
                'add_time' => date('YmdHis'), 
                'join_mode' => 0, 
                'status' => '1', 
                'batch_type' => $this->BATCH_TYPE, 
                'is_show' => '1', 
                'is_cj' => '1',  // 是否有抽奖(在添加奖品的时候会判断是否是0,如果是0才会改为1,所以一定要设默认值)
                'defined_one_name' => $isshared, 
                'defined_two_name' => $sharednum);
            if ($_POST['jp_type'] == 1 && ! empty($_POST['wx_card_id']))
                $data['join_mode'] = 1;
            $marketInfoModel = M('tmarketingInfo');
            $m_id = $marketInfoModel->add($data);
            // file_put_contents('11.txt',M()->getLastSql());
            if (! $m_id) {
                M()->rollback();
                $this->error('新增活动失败!');
            }
            if(empty($data_bak['friend_tiack'][$k])){
            $cnRules = M('tcj_rule')->where(
                "batch_id = '{$m_id}' and status = '1'")->select();
            if (count($cnRules) < 1) {
                // 如果是新增把默认的抽奖配置填上
                $ruleParam = array(
                    'batch_type' => $this->BATCH_TYPE, 
                    'batch_id' => $m_id, 
                    'jp_set_type' => 1,  // 1单奖品2多奖品
                    'node_id' => $this->node_id, 
                    'add_time' => date('YmdHis'), 
                    'status' => '1', 
                    // 'total_chance' => 'default',
                    'phone_total_count' => 1, 
                    'phone_total_part' => 1, 
                    'phone_day_count' => 1, 
                    'phone_day_part' => 1, 
                    'cj_button_text' => '开始抽奖', 
                    'cj_resp_text' => '恭喜您！中奖了',  // 中奖提示信息
                    'cj_button_text' => "立即领取", 
                    'param1' => '', 
                    'no_award_notice' => '很遗憾！未中奖', 
                    'total_chance' => 100);
                $flag = M('tcj_rule')->add($ruleParam);
                if (! $flag) {
                    M()->rollback();
                    $this->error('新增活动失败!');
                }
             }
            }
        } //else {
           /* $map = array(
                'id' => $m_id
                );
            $marketingInfo = M('tmarketing_info')->where($map)->find();
           // $this->BATCH_TYPE = $marketingInfo['batch_type'];
        }*/
        return $m_id;
    }
/*
     * 编辑
     */
    public function activeEdit() {
        if (IS_POST) {
            $storelimit = I('storelimit');
            $openStores = I('openStores');
            $chan_id = I('chan_id');
            if (! $chan_id)
                $this->error('错误操作！请重新再试！');
            $tchanel=M('tchannel')->where('id = '.$chan_id)->find();
            if(empty($tchanel))
                 $this->error('活动不存在!'); 
            $channel_name = I('channel_name');
            if (! check_str($channel_name, 
                array(
                    'maxlen_cn' => 18))) {
                $this->error('活动名称大于18个字！');
                exit();
            }
            $isNameSame = M()->table(
                array(
                    'tchannel' => 'a', 
                    'tbatch_channel' => 'tc'))
                ->where(
                array(
                    'a.node_id' => $this->node_id, 
                    'a.type' => 7, 
                    'a.name' => $channel_name, 
                    'a.id' => array(
                        'neq', 
                        $chan_id)))
                ->where(' tc.channel_id = a.id ')
                ->count();
            if ($isNameSame > 0) {
                $this->error('活动名称重复！');
                exit();
            }
            $begin_time = I('begin_time') . '000000';
            $end_time = I('end_time') . '235959';
            if ($storelimit == 1 && empty($openStores)) {
                $this->error('请选择门店');
            }
            $memo = I('meme_m');
            if ($memo != '') {
                if (! check_str($memo, 
                    array(
                        'maxlen_cn' => 50))) {
                    $this->error('备注不能大于50个字！');
                    exit();
                }
            }
            $limit_amt = I('limit_amt'); // limit_amt
            $rule1=I('rule1');

            $deletebatch=I('deletebatch');
            //if(!empty($deletebatch)){
            $deletebatch=trim(I('deletebatch'),',');
            $_listarr=M()->table(array('tbatch_channel'=>'tc','tchannel'=>'t','tpay_give_batch_set'=>'tgs'))
                ->where('t.id=tgs.channel_id and tgs.batch_channel_id=tc.id and tc.status=1')
                ->where(array('t.node_id' => $this->node_id,'t.id'=>$chan_id,
                    'tc.id'=>array('not in',$deletebatch)))
                ->field('group_concat(tgs.limit_amt) limit_amt,group_concat(tgs.upper_limit_amt) upper_limit_amt')
                ->find();
            if(empty($_listarr['limit_amt'])){
                    if(empty($limit_amt))
                        $this->error('请添加优惠活动');
                    if(empty($rule1))
                        $this->error('请添加优惠方式'); 
                }

            // }
            $dbdownlimat=$_listarr['limit_amt'];
            $dbdownlimat=explode(',', $dbdownlimat);
            // $dbuplimat=$_listarr[0]['upper_limit_amt'];
            //$dbuplimat=explode(',', $dbuplimat);
            if (!empty($limit_amt)) {
                foreach($limit_amt as $k=> $v) {
                    if (empty($v))
                        $this->error('金额限制不能为空');
                    if($rule1[$k]=='')
                       $this->error('请添加优惠方式');   
                }
                if(count($limit_amt)  != count(array_flip($limit_amt)))
                    $this->error('金额限制不能重复');
                if(!empty($dbdownlimat))
                    $samearr=array_intersect($limit_amt,$dbdownlimat);
                    if(!empty($samearr))
                        $this->error('金额限制不能重复');
            }
            if ($meme_m != '') {
                if (! check_str($meme_m, 
                    array(
                        'maxlen_cn' => 50))) {
                    $this->error('备注不能大于50个字！');
                    exit();
                }
            }
            sort($dbdownlimat);
            if(!empty($limit_amt)){
                sort($limit_amt);
                $_limit_amt=($limit_amt[0]>$dbdownlimat[0]?$dbdownlimat[0]: $limit_amt[0]);
            }else{
                $_limit_amt=$dbdownlimat[0];
            }
            $jqHigh = 8000000.00;
            $map = array(
                'a.node_id' => $this->node_id, 
                'a.type' => 7, 
                'a.status' => 1, 
                'a.id' => array(
                    'neq', 
                    $chan_id));
            $map['_string'] = '  ((tgs.limit_amt < ' . $jqHigh .
             ' and tgs.upper_limit_amt  > ' . $_limit_amt .
             ') or (tgs.limit_amt = ' . $jqHigh . ' and tgs.upper_limit_amt  = ' .
             $_limit_amt . ')) ';
            M()->table(
                array(
                    'tchannel' => 'a', 
                    'tpay_give_batch_set'=>'tgs',
                    'tbatch_channel' => 'tc')
                );
            $openStores_arr = explode(',', $openStores);
            foreach ($openStores_arr as $v)
                $openStores_str .= '\'' . $v . '\',';
            if ($storelimit == 1) {
                M()->table(
                    array(
                        'tchannel' => 'a', 
                        'tbatch_channel' => 'tc', 
                        'tpay_give_batch_set'=>'tgs',
                        'tpay_give_store_list' => 'tg'));
                if (! empty($map['_string']))
                    $map['_string'] = $map['_string'] .
                         ' and ((tg.channel_id = a.id and  a.store_join_flag=2 and tg.store_id in (' .
                         trim($openStores_str, ',') .
                         ')) or a.store_join_flag=1) ';
                else
                    $map['_string'] = '((tg.channel_id = a.id and  a.store_join_flag=2 and tg.store_id in (' .
                         trim($openStores_str, ',') .
                         ')) or a.store_join_flag=1)';
            }
            $map['_string'].=' and tgs.channel_id=a.id and tgs.batch_channel_id =tc.id';
            $exiChannel = M()->where('tc.channel_id = a.id')
                ->field(array(
                'a.*'))
                ->where($map)
                ->group('a.id')
                ->select();
            foreach ($exiChannel as $v) {
                if ($v['end_time'] != '') {
                    if (($begin_time >= $v['begin_time'] &&
                         $begin_time <= $v['end_time']) ||
                         ($begin_time <= $v['begin_time'] &&
                         $end_time >= $v['end_time']) ||
                         ($begin_time >= $v['begin_time'] &&
                         $begin_time <= $v['end_time']) || ($end_time <=
                         $v['end_time'] && $end_time >= $v['begin_time'])) {
                        $this->error("该时间段内，你已经创建过此活动,不能重复创建！");
                        exit();
                    }
                }
            }
            $data = array(
                't.name' => $channel_name, 
                't.begin_time' => $begin_time, 
                't.end_time' => $end_time, 
                't.join_limit' => I('join_limit'), 
                't.memo' => $memo
                );
            if ($storelimit == 0)
                $data['t.store_join_flag'] = 1;
            else if ($storelimit == 1) {
                $data['t.store_join_flag'] = 2;
            }
            $m_id = M()->table(array(
                    'tchannel'=>'t',
                    'tbatch_channel'=>'tc',
                    'tpay_give_batch_set'=>'tgs')
            )->where( array(
                    't.node_id' => $this->node_id, 
                    't.type' => 7, 
                    't.id' => array(
                        'eq', 
                        $chan_id)))
                    ->where('tgs.channel_id=t.id and tgs.batch_channel_id=tc.id')
                    ->field('tc.batch_id')->find();
            M()->startTrans();
            $c_model=M()->table(
                array(
                    'tchannel'=>'t',
                    'tbatch_channel'=>'tc',
                    'tpay_give_batch_set'=>'tgs'))
                ->where("tgs.channel_id=t.id and tgs.batch_channel_id=tc.id and 
                        t.node_id='$this->node_id' and t.id=$chan_id");
            if(!empty($deletebatch)){
                    $c_model->where(array('tc.id'=>array('in',$deletebatch)));
                    $data['tc.status']=2;  
                }
            $saveChannel=$c_model->save($data); 
            if (!empty($m_id))
                M('tmarketing_info')->where(array('id'=>array('in',$m_id)))
                ->where(
                    "node_id='$this->node_id'")->save(
                    array(
                        'start_time' => $begin_time, 
                        'end_time' => $end_time)
                    );
            $rs_destore = M('tpay_give_store_list')->where(
                array(
                    'channel_id' => $chan_id))->delete();
            $rs_addstore = true;
            if ($storelimit == 1) {
                $rs_addstore = $this->add_store_list($chan_id, $openStores);
            }
            if ($saveChannel === false || $rs_destore === false ||
                 $rs_addstore === false) {
                 M()->rollback();
                 $this->error("编辑活动失败！");
            } 
            $_REQUEST['tid']=$chan_id ;
            $_POST['tid']=$_GET['tid']=$_REQUEST['tid'];
            $inputdata=$_POST;
            global $data_bak;
            $data_bak=$inputdata;
            if (empty($_REQUEST['tid'])) {
                 M()->rollback();
                $this->error('活动配置有误,请重新配置');
            }
            $tid = $_REQUEST['tid'];
            $join_mode = $tchanel['join_flag'];
            $act_name = $tchanel['name'];
            $act_time_from = $tchanel['begin_time'];
            $act_time_to = $tchanel['end_time'];
            $nodeInfo = get_node_info($this->node_id);
            $introduce = '付满送活动';//介绍
            $node_name = $nodeInfo['node_name'];
            $isnodelogo = I('isnodelogo');
            $node_logo =  $nodeInfo['head_photo'];
            if(!empty($rule1))
            foreach($rule1 as $k=>$v){    
                $basicInfo = array(
                    'join_mode' => $join_mode, 
                    'name' => $act_name, 
                    'act_time_from' => $act_time_from, 
                    'act_time_to' => $act_time_to, 
                    'introduce' => $introduce, 
                    'node_name' => $node_name, 
                    'node_logo' => get_upload_url($node_logo)
                    );
                if($v==0 && $data_bak['friend_tiack'][$k]!=1){
                       if(empty($data_bak['priseitem'][$k])){
                            M()->rollback();
                            $this->error('请先设置活动奖品');
                        }
                        parse_str(preg_replace('/amp;/', '', $data_bak['priseitem'][$k]), $myArray);
                        $_POST = array_merge($data_bak,$myArray);
                        $_REQUEST=$_GET= $_POST;
                        $batch_id = $this->_editMarketInfo($basicInfo, null,$k);
                }elseif($v==0 && $data_bak['friend_tiack'][$k]==1){
                    if(empty($data_bak['goods_id'][$k])|| empty($data_bak['wx_card_id'][$k])){
                         M()->rollback();
                         $this->error('请先设置活动奖品');
                    }
                    $_POST = array_merge($data_bak ,array('jp_type'=>1,'goods_id'=>$data_bak['goods_id'][$k],'wx_card_id'=>$data_bak['wx_card_id'][$k]));
                    $_REQUEST=$_GET= $_POST;
                    $batch_id = $this->_editMarketInfo($basicInfo, null,$k);
                }else {
                    if(empty($data_bak['mid'][$k])){
                         M()->rollback();
                         $this->error('请先设置活动奖品');
                     }
                     $batch_id = $this->_editMarketInfo($basicInfo, $data_bak['mid'][$k],$k);
                }
                $_POST['batch_id'] = $batch_id;
                $rules = array(
                'cj_cate_id' => array(
                    'null' => true, 
                    'strtype' => 'int', 
                    'minval' => '0', 
                    'name' => '奖项ID'), 
                'score' => array(
                    'null' => true, 
                    'strtype' => 'int', 
                    'name' => '兑换金币数'), 
                'min_rank' => array(
                    'null' => true, 
                    'strtype' => 'int', 
                    'name' => '最小排名'), 
                'max_rank' => array(
                    'null' => true, 
                    'strtype' => 'int', 
                    'name' => '最大排名'), 
                'sort' => array(
                    'null' => true, 
                    'strtype' => 'int', 
                    'name' => '排序'));
                $req_data = $this->_verifyReqData($rules);
                extract($req_data);
                $cj_cate_id = $req_data['cj_cate_id'];
                $cj_cate_name = "付满送奖品";
                $score = $req_data['score'];
                $min_rank = $req_data['min_rank'];
                $max_rank = $req_data['max_rank'];
                $sort = $req_data['sort']; 
                if (empty($data_bak['mid'][$k])) {
                        $this->jpAdd($k);
                }
                $_map = array(
                'id' => $batch_id
                );
                $_marketingInfo = M('tmarketing_info')->where($_map)->find();
                $_batch_type = $_marketingInfo['batch_type'];
                $rs1 = M('tbatch_channel')->add(
                array(
                'batch_id' => $batch_id, 
                'batch_type' => $_batch_type, 
                'channel_id' => $tid, 
                'add_time' => date('YmdHis'), 
                'node_id' => '' . $this->node_id, 
                'status' => 1, 
                'start_time'=>$tchanel['begin_time'],
                'end_time' => $tchanel['end_time'], 
                'label_id' => $tchanel['label_id']));
                if(!$rs1){
                    M()->rollback();
                    $this->error('编辑活动失败！');
                }
                $rs2 = M('tpay_give_batch_set') 
                ->add(
                  array(
                    'flag'=>$v,
                    'channel_id' => $tid, 
                    'batch_channel_id' => $rs1, 
                    'tpaygift'=>$data_bak['paygift'][$k],
                    'upper_limit_amt' =>$jqHigh, 
                    'limit_amt' => $data_bak['limit_amt'][$k]
                    )
                );
             if(!$rs2){
                M()->rollback();
                $this->error('编辑活动失败！');
             }
             if (empty($_REQUEST['friend_tiack'][$k]) && empty($tchanel['join_limit'])) {
                    $rs3=M('tcj_rule')->where(
                    array(
                        'batch_id' => $batch_id, 
                        'status' => 1))->save(
                    array(
                        'phone_total_count' => 0, 
                        'phone_total_part' => 0, 
                        'phone_day_count' => 0, 
                        'phone_day_part' => 0));
                    if($rs3===false){
                         M()->rollback();
                         $this->error('编辑活动失败！');
                      }        
               }
               if($data_bak['paygift'][$k]==1){
                        if(empty($paygiftdata))
                            $kk=0;
                        else
                            $kk= count($paygiftdata);
                        $paygiftdata[$kk]['card_id']=$_POST['wx_card_id'];
                        $paygiftdata[$kk]['min_cost']=$data_bak['limit_amt'][$k];
                        $paygiftdata[$kk]['max_cost'] =8000000;
                        $paygiftdata[$kk]['begin_time'] =  time($begin_time);
                        $paygiftdata[$kk]['end_time'] =time($end_time);
                    }
            }
            M()->commit();
            if(!empty($paygiftdata)) {
                         log_write('editmiao'.print_r($paygiftdata,true));
                         $weixincardserver= D('WeiXinCard','Service');
                         $weixincardserver->init_by_node_id($this->node_id);
                         $weixincardserver->paygift($paygiftdata);
                     }
            $this->success('编辑活动成功！');
            } else {
                $channel_id = I('channel_id');
                global $channelList;
                $channelList = M()->table(array('tchannel' =>'a','tpay_give_batch_set'=>'tgs','tbatch_channel'=>'b'))
                ->field('DISTINCT c.id cids, tgs.tpaygift,b.id tbatchchannel_id,tct.card_class,tgs.upper_limit_amt,tgs.flag,ti.batch_short_name,c.defined_one_name,c.defined_two_name,
                    tgs.limit_amt tlimat,a.*,b.batch_id batchId,c.name markName')
                    ->where("tgs.channel_id=a.id and   b.status=1 and  tgs.batch_channel_id=b.id and a.id=$channel_id and a.node_id='$this->node_id'")
                    ->join('tmarketing_info c on b.batch_id=c.id')
                    ->join('tbatch_info ti on ti.m_id=c.id')
                    ->join('twx_card_type tct on tct.card_id=ti.card_id')
                    ->select();
                   
                $this->assign('channel_id', $channel_id);
                $channelList[0]['store_num'] = 0;
                $ids = M('tpay_give_store_list')->field(
                    array(
                        'group_concat(store_id)' => 'ids'))
                    ->where(
                    array(
                        'channel_id' => $channel_id))
                    ->find();
                $ids = trim($ids['ids'], ',');
                if (! empty($ids)) {
                    $store_num = count(explode(',', $ids));
                    $channelList[0]['store_num'] = $store_num > 0 ? $store_num : 0;
                }
                $channelList[0]['ids'] = $ids;
                
                $this->assign('chanList', $channelList[0]);
                $this->assign('data', 
                    array(
                        'join_flag' => $channelList[0]['join_flag']));
                $this->display();
            }
    }
    function saveaddActive() {
        //tchannel start
        //log_write('paysend'.print_r($_REQUEST,true));
        $storelimit = I('storelimit');
        $openStores = I('openStores');
            
        $channel_name = I('channel_name');
        if (! check_str($channel_name, 
                array(
                    'maxlen_cn' => 18))) {
                $this->error('活动名称大于18个字！');
                exit();
            }
            $begin_time = I('begin_time') . '000000';
            $end_time = I('end_time') . '235959';
            $payInfo = M('tzfb_offline_pay_info')->field(
                'pay_type,check_status,status')
                ->where(
                array(
                    'node_id' => $this->node_id))
                ->group('pay_type')
                ->select();
            $paystr = '';
            foreach ($payInfo as $v) {
                // 支付宝
                if ($v['pay_type'] == '0') {
                    if ($v['status'] == '1') {
                        $paystr .= '1,';
                    }
                    continue;
                }
                if ($v['pay_type'] == '1') {
                    if ($v['status'] == '1') {
                        $paystr .= '2,';
                    }
                    continue;
                }
                if ($v['pay_type'] == '2') {
                    if ($v['status'] == '1') {
                        $paystr .= '3,';
                    }
                    continue;
                }
                if ($v['pay_type'] == '5') {
                    if ($v['status'] == '1') {
                        $paystr .= '5,';
                    }
                    continue;
                }
                // 通联支付
                if ($v['pay_type'] == '4') {
                    if ($v['status'] == '1') {
                         $paystr .= '4,';
                    }
                    continue;
                }
            }
           
            $join_flag= $paystr;
            /*if (empty($join_flag)) {
                $this->error('还未开通支付方式');
                exit();
            }*/
            //现金方式7
            $join_flag .= '7,';
            if ($storelimit == 1 && empty($openStores)) {
                $this->error('请选择门店');
            }
            $meme_m = I('meme_m');
            $join_limit = I('join_limit');
            $add_time = date("YmdHis");
            // //
            $map = array(
                'a.node_id' => $this->node_id, 
                'a.type' => 7);
            $isNameSame = M()->table(
                array(
                    'tchannel' => 'a', 
                    'tbatch_channel' => 'tc'))
                ->where($map)
                ->where(
                'tc.channel_id = a.id and  a.name ="' . $channel_name . '"')
                ->count();
            if ($isNameSame > 0) {
                $this->error('活动名称重复！');
                exit();
            }
            $limit_amt = I('limit_amt'); // limit_amt
            $rule1=I('rule1');
            if(empty($limit_amt) || empty($rule1))
                 $this->error('请添加优惠活动');
            foreach($limit_amt as $k=> $v) {
                if (empty($v) || $rule1[$k]=='')
                    $this->error('金额限制不能为空');
            }
            if(count($limit_amt)  != count(array_flip($limit_amt)))
                $this->error('金额限制不能重复');
            if ($meme_m != '') {
                if (! check_str($meme_m, 
                    array(
                        'maxlen_cn' => 50))) {
                    $this->error('备注不能大于50个字！');
                    exit();
                }
            }
            sort($limit_amt);
            $_limit_amt=$limit_amt[0];
            $jqHigh = 8000000.00;
            $map['_string'] = ' tgs.channel_id=a.id and tgs.batch_channel_id =tc.id and  ((tgs.limit_amt < ' . $jqHigh .
             ' and tgs.upper_limit_amt  > ' . $_limit_amt .
             ') or (tgs.limit_amt = ' . $jqHigh . ' and tgs.upper_limit_amt  = ' .
             $_limit_amt . ')) ';
            M()->table(
                array(
                    'tpay_give_batch_set'=>'tgs',
                    'tchannel' => 'a', 
                    'tbatch_channel' => 'tc')
                );
            $openStores_arr = explode(',', $openStores);
            $openStores_str = '';
            foreach ($openStores_arr as $v)
                $openStores_str .= '\'' . $v . '\',';
            if ($storelimit == 1) {
                M()->table(
                    array(
                        'tchannel' => 'a', 
                        'tbatch_channel' => 'tc', 
                        'tpay_give_batch_set'=>'tgs',
                        'tpay_give_store_list' => 'tg'));
                if (! empty($map['_string']))
                    $map['_string'] = $map['_string'] .
                         ' and ((tg.channel_id = a.id and  a.store_join_flag=2 and tg.store_id in (' .
                         trim($openStores_str, ',') .
                         ')) or a.store_join_flag=1) ';
                else
                    $map['_string'] = '((tg.channel_id = a.id and  a.store_join_flag=2 and tg.store_id in (' .
                         trim($openStores_str, ',') .
                         ')) or a.store_join_flag=1)';
            }
            $map['a.status']=1;
            $exiChannel = M()->field('a.*')
                ->where($map)
                ->group('a.id')
                ->select();
            if(!empty($exiChannel))
            foreach ($exiChannel as $v) {
                if (($begin_time >= $v['begin_time'] &&
                     $end_time <= $v['end_time']) ||
                     ($begin_time <= $v['begin_time'] &&
                     $end_time >= $v['end_time']) ||
                     ($begin_time >= $v['begin_time'] &&
                     $begin_time <= $v['end_time']) || ($end_time <=
                     $v['end_time'] && $end_time >= $v['begin_time'])) {
                     $this->error("该时间段内，你已经创建过活动,不能重复创建！");
                     exit();
                }
            }
            $data = array(
                'name' => $channel_name, 
                'type' => '7', 
                'batch_type' => $this->BATCH_TYPE, 
                'status' => 2, 
                'sns_type' => 71, 
                'node_id' => $this->node_id, 
                'begin_time' => $begin_time, 
                'end_time' => $end_time, 
                'join_flag' => $join_flag,
                'join_limit' => $join_limit, 
                'add_time' => $add_time, 
                'memo' => $meme_m
                //'upper_limit_amt' => $jqHigh, 
                //'limit_amt' => $limit_amt
                );
            //tpay_give_batch_set
            if (empty($storelimit))
                $data['store_join_flag'] = 1;
            else if ($storelimit == 1) {
                $data['store_join_flag'] = 2;
            }
            M()->startTrans();
            $rs_destore = true;
            $rs_addstore = true;
            $addChannel = M('tchannel')->add($data);
            $tid = M()->getLastInsID();
            if ($storelimit == 1) {
                $rs_addstore = $this->add_store_list($tid, $openStores);
            }
            if (!$addChannel || !$rs_destore  || !  $rs_addstore ){
                M()->rollback();
                $this->error('添加新活动失败!');
            }
            //tchannel end
            $_REQUEST['tid']=$tid ;
            $_POST['tid']=$_GET['tid']=$_REQUEST['tid'];
            $inputdata=$_POST;
            global $data_bak;
            $data_bak=$inputdata;
            if (empty($_REQUEST['tid'])) {
                 M()->rollback();
                $this->error('活动配置有误,请重新配置');
            }
            $tid = $_REQUEST['tid'];
            $tchanel = M('tchannel')->where(
                array(
                    'node_id' => $this->node_id, 
                    'id' => $tid))->find();
            if (empty($tchanel)){
                   M()->rollback();
                   $this->error('活动配置有误,请重新配置');
            }   
            $join_mode = $tchanel['join_flag'];
            $act_name = $tchanel['name'];
            $act_time_from = $tchanel['begin_time'];
            $act_time_to = $tchanel['end_time'];
            $nodeInfo = get_node_info($this->node_id);
            $introduce = '付满送活动';//介绍
            $node_name = $nodeInfo['node_name'];
            $isnodelogo = I('isnodelogo');
            $node_logo =  $nodeInfo['head_photo'];
            foreach($rule1 as $k=>$v){    
                $basicInfo = array(
                    'join_mode' => $join_mode, 
                    'name' => $act_name, 
                    'act_time_from' => $act_time_from, 
                    'act_time_to' => $act_time_to, 
                    'introduce' => $introduce, 
                    'node_name' => $node_name, 
                    'node_logo' => get_upload_url($node_logo)
                    );
                if($v==0 && $data_bak['friend_tiack'][$k]!=1){
                       if(empty($data_bak['priseitem'][$k])){
                            M()->rollback();
                            $this->error('请先设置活动奖品');
                        }
                        parse_str(preg_replace('/amp;/', '', $data_bak['priseitem'][$k]), $myArray);
                        $_POST = array_merge($data_bak,$myArray);
                        $_REQUEST=$_GET= $_POST;
                        $batch_id = $this->_editMarketInfo($basicInfo, null,$k);
                }elseif($v==0 && $data_bak['friend_tiack'][$k]==1){
                    if(empty($data_bak['goods_id'][$k])|| empty($data_bak['wx_card_id'][$k])){
                         M()->rollback();
                         $this->error('请先设置活动奖品');
                    }
                    $_POST = array_merge($data_bak ,array('jp_type'=>1,'goods_id'=>$data_bak['goods_id'][$k],'wx_card_id'=>$data_bak['wx_card_id'][$k]));
                    $_REQUEST=$_GET= $_POST;
                    $batch_id = $this->_editMarketInfo($basicInfo, null,$k);
                }else {
                    if(empty($data_bak['mid'][$k])){
                         M()->rollback();
                         $this->error('请先设置活动奖品');
                     }
                     $batch_id = $this->_editMarketInfo($basicInfo, $data_bak['mid'][$k],$k);
                }
                $_POST['batch_id'] = $batch_id;
                $rules = array(
                'cj_cate_id' => array(
                    'null' => true, 
                    'strtype' => 'int', 
                    'minval' => '0', 
                    'name' => '奖项ID'), 
                'score' => array(
                    'null' => true, 
                    'strtype' => 'int', 
                    'name' => '兑换金币数'), 
                'min_rank' => array(
                    'null' => true, 
                    'strtype' => 'int', 
                    'name' => '最小排名'), 
                'max_rank' => array(
                    'null' => true, 
                    'strtype' => 'int', 
                    'name' => '最大排名'), 
                'sort' => array(
                    'null' => true, 
                    'strtype' => 'int', 
                    'name' => '排序'));
                $req_data = $this->_verifyReqData($rules);
                extract($req_data);
                $cj_cate_id = $req_data['cj_cate_id'];
                $cj_cate_name = "付满送奖品";
                $score = $req_data['score'];
                $min_rank = $req_data['min_rank'];
                $max_rank = $req_data['max_rank'];
                $sort = $req_data['sort']; 
                if (empty($data_bak['mid'][$k])) {
                        $this->jpAdd($k);
                }
            $_map = array(
                'id' => $batch_id
                );
            $_marketingInfo = M('tmarketing_info')->where($_map)->find();
            $_batch_type = $_marketingInfo['batch_type'];
            $rs1 = M('tbatch_channel')->add(
            array(
                'batch_id' => $batch_id, 
                'batch_type' => $_batch_type,
                'channel_id' => $tid, 
                'add_time' => date('YmdHis'), 
                'node_id' => '' . $this->node_id, 
                'status' => 1, 
                'end_time' => $tchanel['end_time'], 
                'start_time'=>$tchanel['begin_time'],
                'label_id' => $tchanel['label_id'])
            );
            if(!$rs1){
             M()->rollback();
             $this->error('添加新活动失败');
            }
            $rs2 = M('tpay_give_batch_set') 
            ->add(
              array(
                'flag'=>$v,
                'channel_id' => $tid, 
                'batch_channel_id' => $rs1, 
                'upper_limit_amt' =>$jqHigh, 
                'tpaygift'=>$data_bak['paygift'][$k],
                'limit_amt' => $data_bak['limit_amt'][$k]
                )
            );
            if(!$rs2){
             M()->rollback();
             $this->error('添加新活动失败');
            }
            if (empty($_REQUEST['friend_tiack'][$k]) && empty($tchanel['join_limit'])) {
                $rs3=M('tcj_rule')->where(
                    array(
                        'batch_id' => $batch_id, 
                        'status' => 1))->save(
                    array(
                        'phone_total_count' => 0, 
                        'phone_total_part' => 0, 
                        'phone_day_count' => 0, 
                        'phone_day_part' => 0));
                if($rs3===false){
                     M()->rollback();
                     $this->error('添加新活动失败');
                        }  
            }
                if($data_bak['paygift'][$k]==1){
                     if(empty($paygiftdata))
                            $kk=0;
                        else
                            $kk= count($paygiftdata);
                        $paygiftdata[$kk]['card_id']=$_POST['wx_card_id'];
                        $paygiftdata[$kk]['min_cost']=$data_bak['limit_amt'][$k];
                        $paygiftdata[$kk]['max_cost'] =8000000;
                        $paygiftdata[$kk]['begin_time'] =  time($begin_time);
                        $paygiftdata[$kk]['end_time'] =time($end_time);
                    }
            }
            $rs4 = M('tchannel')->where(
            array(
                'id' => $tid, 
                'status' => 2, 
                'type' => '7', 
                'node_id' => $this->node_id))
            ->save(
            array(
                'status' => 1
                //'batch_id' => $batch_id
            ));
            if ($rs4) {
                M()->commit();
                if(!empty($paygiftdata)) {
                        log_write('addpausend',print_r($paygiftdata,true));
                         $weixincardserver= D('WeiXinCard','Service');
                         $weixincardserver->init_by_node_id($this->node_id);
                         $weixincardserver->paygift($paygiftdata);
                     }
                $this->success(
                    U('sredict', array(
                        'id' => $tid)));
            } else {
                M()->rollback();
                $this->error('添加新活动失败');
        }  
        
    }

    function sredict() {
        $tchanel = M('tchannel')->field('begin_time')
            ->where(
            array(
                'node_id' => $this->node_id, 
                'id' => I('id')))
            ->find();
        $this->assign('begin_time', $tchanel['begin_time']);
        $this->display('addActive_OK');
    }
    
    // 奖品添加
    public function jpAdd($k) { 
        $rules = array(
            'batch_id' => array(
                'null' => false, 
                'strtype' => 'int', 
                'name' => '营销活动号'), 
            'goods_id' => array(
                'null' => false, 
                'name' => '奖品'), 
            'js_cate_id' => array(
                'null' => true, 
                'strtype' => 'int', 
                'name' => '奖项号'), 
            'day_count' => array(
                'null' => false, 
                'strtype' => 'int', 
                'minval' => '0', 
                'name' => '奖品'), 
            'goods_count' => array(
                'null' => false, 
                'strtype' => 'int', 
                'minval' => '0', 
                'name' => '奖品'), 
            'batch_amt' => array(
                'null' => true, 
                'minval' => '0', 
                'name' => '平安币'), 
            'jp_type' => array(
                'null' => true, 
                'strtype' => 'int', 
                'name' => '奖品类型'),  // 0 卡券 1 微信券
            'score' => array(
                'null' => true, 
                'strtype' => 'int', 
                'name' => '兑换金币数'), 
            'wx_card_id' => array(
                'null' => true), 
            'min_rank' => array(
                'null' => true, 
                'strtype' => 'int', 
                'name' => '最小排名'), 
            'max_rank' => array(
                'null' => true, 
                'strtype' => 'int', 
                'name' => '最大排名'), 
            'sort' => array(
                'null' => true, 
                'strtype' => 'int', 
                'name' => '排序')
            ); 
        if(empty($_REQUEST['friend_tiack'][$k]))
            $_REQUEST['friend_tiack']=null;
        if($_REQUEST['friend_tiack'][$k]==1){
                 $rules['day_count']['null']=true;
                 $rules['goods_count']['null']=true;
        }
        // 河北太平洋保险，可不下发凭证
        if ($this->hbtpybx_flag) {
            $rules['send_type'] = array(
                'null' => false, 
                'strtype' => 'int', 
                'name' => '是否下发凭证', 
                'inarr' => array(
                    '0', 
                    '1'));
        }
        $req_data = $this->_verifyReqData($rules);
        $req_data['jp_type'] = intval($req_data['jp_type']);
        $js_cate_id = I('js_cate_id', 0, 'intval');
        
        // 营销活动校验
        $map = array(
            'id' => $req_data['batch_id'], 
            'node_id' => $this->node_id);
        // dump($_POST);
        $marketingInfo = M('tmarketing_info')->where($map)->find();
        if (! $marketingInfo) {
            M()->rollback();
            $this->error('未找到该营销活动');
        }
        if (! empty($_POST['wx_card_id']))
            $rules['jp_type'] = 1;
            // 查询商品信息
        $map = array(
            'goods_id' => $req_data['goods_id'], 
            'node_id' => $this->nodeId, 
            'status' => '0');
        $goodsInfo = M('tgoods_info')->where($map)->find();
        if (! $goodsInfo) {
            M()->rollback();
            $this->error('未找到该卡券');
        }
        // 平安非标
        if ($goodsInfo['goods_type'] == '10') {
            extract($req_data);
        } // 定额红包
        elseif ($goodsInfo['goods_type'] == '12') {
            extract($req_data);
        } // 非旺财卡券处理\非微信券
        elseif ($goodsInfo['goods_type'] != '9' && $goodsInfo['goods_type'] != '15' && intval($req_data['jp_type']) == 0) {
            $rules = array(
                'mms_title' => array(
                    'null' => true, 
                    'maxlen_cn' => '10', 
                    'name' => '彩信标题'), 
                'mms_text' => array(
                    'null' => true, 
                    'maxlen_cn' => '100', 
                    'name' => '彩信内容'), 
                'verify_time_type' => array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'name' => '验证时间类型', 
                    'inarr' => array(
                        '0', 
                        '1')));
            $req_data = array_merge($req_data, $this->_verifyReqData($rules));
            
            if ($req_data['verify_time_type'] == '0') {
                $rules = array(
                    'verify_begin_date' => array(
                        'null' => false, 
                        'strtype' => 'datetime', 
                        'format' => 'Ymd', 
                        'name' => '验证开始时间'), 
                        'verify_end_date' => array(
                        'null' => false, 
                        'strtype' => 'datetime', 
                        'format' => 'Ymd', 
                        'name' => '验证结束时间'));
            } else {
                $rules = array(
                    'verify_begin_days' => array(
                        'null' => false, 
                        'strtype' => 'int', 
                        'minval' => '0', 
                        'name' => '验证开始天数'), 
                        'verify_end_days' => array(
                        'null' => false, 
                        'strtype' => 'int', 
                        'minval' => '0', 
                        'name' => '验证结束天数'));
            }
            
            $req_data = array_merge($req_data, $this->_verifyReqData($rules));
            extract($req_data);
            
            $verify_begin_date .= '000000';
            $verify_end_date .= '235959';
            
            if ($verify_time_type == '0' && date('YmdHis') > $verify_end_date) {
                M()->rollback();
                $this->error('验证结束时间必须大于当前时间');
            }
            if ($verify_time_type == '0' && $verify_begin_date > $verify_end_date) {
                M()->rollback();
                $this->error('验证开始时间不能大于验证结束时间');
            }
        }  // 旺财卡券处理
        else { 
            extract($req_data);
            $sms_text = $goodsInfo['sms_text'];
            $verify_time_type = $goodsInfo['verify_begin_type'];
            if ($verify_time_type == '0') {
                $verify_begin_date = $goodsInfo['verify_begin_date'];
                $verify_end_date = $goodsInfo['verify_end_date'];
            } else {
                $verify_begin_days = $goodsInfo['verify_begin_date'];
                $verify_end_days = $goodsInfo['verify_end_date'];
            }
        }
        // 微信卡券
        if ($jp_type == 1) {
            $wx_card_id = $_POST['wx_card_id'];
            $map = array(
                'node_id' => $this->node_id, 
                'goods_id' => $goods_id, 
                'card_id' => $wx_card_id);
            $cardInfo = M('twx_card_type')->where($map)->find();
            if (! $cardInfo) {
                M()->rollback();
                $this->error('未找到微信卡券信息');
            }
            if ($cardInfo['card_class']!=2 && $cardInfo['auth_flag'] != '2') {
                M()->rollback();
                $this->error('微信卡券还未审核通过，无法添加为奖品！');
            }
        }
        if(empty($cardInfo))  $cardInfo=null;
        if(empty($cardInfo['card_class']) || $cardInfo['card_class']!=2){
            if ( $day_count > $goods_count){
                M()->rollback();
                $this->error('每日奖品数量不能大于奖品总数');
            }
            // 获取cj_rule信息
            $ruleList = M('tcj_rule')->where(
                "batch_id = '{$batch_id}' and status = '1'")->select();
            if (count($ruleList) > 1) {
                M()->rollback();
                $this->error('系统异常：存在多条启用的抽奖规则记录！');
            } else
                $ruleInfo = $ruleList[0];
        }
        $error = '';
        // 联盟商品，校验联盟商品的有效期和营销活动的有效期，营销活动的有效期必须包含联盟商品活动的有效期
        if ($goodsInfo['source'] == '2') {
            $m_begin_time = substr($marketingInfo['start_time'], 0, 8);
            $m_end_time = substr($marketingInfo['end_time'], 0, 8);
            $g_begin_time = substr($goodsInfo['begin_time'], 0, 8);
            $g_end_time = substr($goodsInfo['end_time'], 0, 8);
            if (max($m_begin_time, date('Ymd')) < max($g_begin_time, 
                date('Ymd')) || $m_end_time > $g_end_time) {
                M()->rollback();
                $this->error(
                    sprintf("添加失败！营销活动的有效期【%s到%s】不在该盟主商品的有效期【%s到%s】之内", 
                        dateformat($m_begin_time, 'Y-m-d'), 
                        dateformat($m_end_time, 'Y-m-d'), 
                        dateformat($g_begin_time, 'Y-m-d'), 
                        dateformat($g_end_time, 'Y-m-d')));
            }
        }
        if(empty($ruleInfo))
            $cj_rule_id ='';
        else 
            $cj_rule_id = $ruleInfo['id'];
        try {
            // 商品查询，由于涉及到库存扣减，需要锁记录
            if(empty($cardInfo['card_class']) ||  $cardInfo['card_class']!=2){
             $goodsInfo = M('tgoods_info')->where(
                "goods_id='{$goods_id}' AND node_id= '{$this->node_id}' AND status=0")
                ->lock(true)
                ->find();
            // 库存校验
            if ($goodsInfo['storage_type'] == '1') {
                if ($goods_count > $goodsInfo['remain_num']) {
                    M()->rollback();
                    $this->error("奖品数大于库存");
                }
            }
            // 盟主商品，不允许修改短彩信内容
            if ($goodsInfo['source'] == '2') {
                $mms_title = $goodsInfo['mms_title'];
                $mms_text = $goodsInfo['mms_text'];
            }
            
            // 微信卡券,且是限制库存的
            if (@$cardInfo['card_class']!=2 &&  $jp_type == 1 && @$cardInfo['quantity'] != 99999) {
                $cnt = (int) M('tbach_info')->where("card_id = '{$wx_card_id}'")->sum(
                    'storage_num');
                if ($goods_count > ($cardInfo['quantity'] - $cnt)) {
                    M()->rollback();
                    $this->error("微信卡券库存不足！");
                }
            }
            // 新增分类
            if (empty($js_cate_id)) {
                $sortMax = M('tcj_cate')->where(
                    array(
                        'batch_id' => $batch_id))->max('sort');
                $sortMax = $sortMax ? $sortMax : 0;
                $data = array(
                    'batch_type' => $this->BATCH_TYPE, 
                    'batch_id' => $marketingInfo['id'], 
                    'node_id' => $marketingInfo['node_id'], 
                    'cj_rule_id' => $cj_rule_id, 
                    'name' => I('cj_cate_name'), 
                    'add_time' => date('YmdHis'), 
                    'status' => '1', 
                    'score' => $score, 
                    'min_rank' => $min_rank, 
                    'max_rank' => $max_rank, 
                    'sort' => (intval($sortMax) + 1));
                $js_cate_id = M('tcj_cate')->add($data);
                if ($js_cate_id === false) {
                    M()->rollback();
                    $this->error('添加奖项失败！');
                }
                 }
            }
            if(empty($mms_title))
                    $mms_title='';
            if(empty($mms_text))
                    $mms_text='';
            if(empty($sms_text))
                    $sms_text='';
            $data = array(
                'batch_no' => $goodsInfo['batch_no'], 
                'batch_short_name' => $jp_type == 1 ? $cardInfo['title'] : $goodsInfo['goods_name'], 
                'batch_name' => $jp_type == 1 ? $cardInfo['title'] : $goodsInfo['goods_name'], 
                'node_id' => $this->node_id, 
                'user_id' => $this->userId, 
                'batch_class' => $goodsInfo['goods_type'], 
                'batch_type' => $goodsInfo['source'], 
                'info_title' => $mms_title?$mms_title:'', 
                'use_rule' => $mms_text?$mms_text:'', 
                'sms_text' => $sms_text?$sms_text:'', 
                'batch_img' => $goodsInfo['goods_image'], 
                'batch_amt' => $goodsInfo['goods_type'] != '10' ? $goodsInfo['goods_amt'] : $batch_amt, 
                'begin_time' => $marketingInfo['start_time'], 
                'end_time' => $marketingInfo['end_time'], 
                'send_begin_date' => $marketingInfo['start_time'], 
                'send_end_date' => $marketingInfo['end_time'], 
                'verify_begin_date' => $verify_time_type == '0' ? $verify_begin_date : $verify_begin_days, 
                'verify_end_date' => $verify_time_type == '0' ? $verify_end_date : $verify_end_days, 
                'verify_begin_type' => $verify_time_type, 
                'verify_end_type' => $verify_time_type, 
                'storage_num' => $goods_count, 
                'add_time' => date('YmdHis'), 
                'node_pos_group' => $goodsInfo['pos_group'], 
                'node_pos_type' => $goodsInfo['pos_group_type'], 
                'batch_desc' => $goodsInfo['goods_desc'], 
                'node_service_hotline' => $goodsInfo['node_service_hotline'], 
                'goods_id' => $goods_id, 
                'remain_num' => $goods_count, 
                'material_code' => $goodsInfo['customer_no'], 
                'print_text' => $goodsInfo['print_text'], 
                'm_id' => $marketingInfo['id'], 
                'validate_type' => $goodsInfo['validate_type']);
            if ($jp_type == 1)
                $data['card_id'] = $wx_card_id;
            $result = M('tbatch_info')->data($data)->add();
            if (! $result) {
                M()->rollback();
                $this->error('添加奖项失败！');
            }
            
            $b_id = $result;
            
            // cj_batch奖品处理
            if($cardInfo['card_class']!=2){
            $data = array(
                'batch_id' => $batch_id,  // '抽奖活动id'
                'node_id' => $this->node_id,  // '商户号'
                'activity_no' => $goodsInfo['batch_no'],  // '奖品活动号'
                'award_origin' => '2',  // '奖品来源 1支撑 2旺财'
                'award_rate' => $ruleList[0]['total_chance'],  // '中奖率'
                'total_count' => $goods_count,  // '奖品总数'
                'day_count' => $day_count,  // '每日奖品数'
                'batch_type' => $marketingInfo['batch_type'],  // ,
                'cj_rule_id' => $cj_rule_id,  // '抽奖规则id'
                'send_type' => '0',  // '0-下发，1-不下发'
                'status' => '1',  // '1正常 2停用'
                'cj_cate_id' => $js_cate_id,  // '抽奖类别id对应tcj_cate主键id'
                'add_time' => date('YmdHis'),  // '奖品添加时间'
                'goods_id' => $goodsInfo['goods_id'], 
                'b_id' => $b_id);
            
            if ($this->hbtpybx_flag) {
                $data['send_type'] = $send_type;
            }
            
            $cj_batch_id = M('tcj_batch')->add($data);
            if ($cj_batch_id === false) {
                M()->rollback();
                $this->error('添加奖项失败！');
            }
            // 扣减库存
            $goodsM = D('Goods');
            $flag = $goodsM->storagenum_reduc($goodsInfo['goods_id'], 
                $goods_count, $cj_batch_id, '0', '');
            if ($flag === false) {
                M()->rollback();
                $this->error($goodsM->getError());
            }
          }
        } catch (Exception $e) {
            M()->rollback();
            $this->error($e->getMessage());
        }
    }

    public function editStatus() {
        $batchId = I('post.batch_id', null);
        $status = I('post.status', null);
        if (is_null($batchId) || is_null($status)) {
            $this->error('缺少必要参数');
        }
        $result = M()->field('t.begin_time,t.store_join_flag,t.end_time,group_concat(tgs.limit_amt) downlimat,group_concat(tgs.upper_limit_amt) uplimat')
            ->table(
            array(
                'tchannel' => 't', 
                'tpay_give_batch_set'=>'tgs',
                'tbatch_channel' => 'tc'))
            ->where(
            array(
                't.node_id' => $this->node_id,
                't.id'=>$batchId
                 ))
            ->where('tgs.channel_id=t.id and tgs.batch_channel_id=tc.id and tc.status=1')
            ->find();
        if (empty($result)) {
            $this->error('未找到该活动');
        }
        $storelimit = $result['store_join_flag'];
        if ($storelimit == 2) {
            $ids = M('tpay_give_store_list')->field(
                array(
                    'group_concat(store_id)' => 'ids'))
                ->where(array(
                'channel_id' => $batchId))
                ->find();
            $openStores = $ids['ids'];
        } else
            $openStores = '';
        if ($status == 1) {
            $map = array(
                'a.node_id' => $this->node_id, 
                'a.type' => 7, 
                'tc.status'=>1,
                'a.id' => array(
                    'neq', 
                    $batchId));
            $downlimat=explode(',',trim($result['downlimat'],','));
            $uplimat=explode(',',trim($result['uplimat'],','));
            sort($downlimat);
            sort($uplimat);
            $aupper_limit_amt = $uplimat[0];
            $limit_amt = $downlimat[0]; // limit_amt
            $_string = ' ((tgs.limit_amt < ' . $aupper_limit_amt .
                 ' and tgs.upper_limit_amt  > ' . $limit_amt .
                 ') or (tgs.limit_amt = ' . $aupper_limit_amt .
                 ' and tgs.upper_limit_amt  = ' . $limit_amt . ')) ';
            M()->table(
                array(
                    'tchannel' => 'a', 
                    'tpay_give_batch_set'=>'tgs',
                    'tbatch_channel' => 'tc'));
            if(!empty($openStores)){
                $openStores_arr = explode(',', $openStores);
                foreach ($openStores_arr as $v)
                    $openStores_str .= '\'' . $v . '\',';
            }
            if ($storelimit == 2) {
                M()->table(
                    array(
                        'tchannel' => 'a', 
                        'tbatch_channel' => 'tc', 
                        'tpay_give_batch_set'=>'tgs',
                        'tpay_give_store_list' => 'tg'));
                 
                    $_string .= 'and ((tg.channel_id = a.id and  a.store_join_flag=2 and tg.store_id in (' .
                         trim($openStores_str, ',') .
                         ')) or a.store_join_flag=1)';
            }
            $exiChannel = M()->field('DISTINCT a.id as aid,a.*')
                ->where($map)
                ->where('a.status =1 and  tgs.channel_id=a.id and tgs.batch_channel_id=tc.id and '.$_string)
                ->group('a.id')
                ->select();
               
            $begin_time = $result['begin_time'];
            $end_time = $result['end_time'];
            foreach ($exiChannel as $v) {
                if (($begin_time >= $v['begin_time'] &&
                     $end_time <= $v['end_time']) ||
                     ($begin_time <= $v['begin_time'] &&
                     $end_time >= $v['end_time']) ||
                     ($begin_time >= $v['begin_time'] &&
                     $begin_time <= $v['end_time']) || ($end_time <=
                     $v['end_time'] && $end_time >= $v['begin_time'])) {
                    $this->error("该时间段内已经开启过活动！");
                    exit();
                }
            }
        }
        $rs1 = M('tchannel')->where(
            array(
                'node_id' => $this->node_id, 
                'id' => $batchId))->save(
            array(
                'status' => $status));
        // $rs2=M('tbatch_channel')->where(array('node_id' =>
        // $this->node_id,'channel_id'=>$batchId
        // ))->save(array('status'=>$status));
        if ($rs1) {
            $this->success();
        } else {
            $this->error('更新失败');
        }
    }
    // 查看更多
    public function moreActive() {
        //$codePay = 'noPay';
        $codePay = 'hasPay';
        $payInfo = M('tzfb_offline_pay_info')->field(
            'pay_type,check_status,status')
            ->where(array(
            'node_id' => $this->node_id))
            ->select();
        /*foreach ($payInfo as $val) {
            if ($val['status'] == '1') {
                $codePay = '1';
                break;
            }
        }*/
        if ($codePay == 'noPay') {
            $this->assign('userSta', $codePay);
            $this->display('introduction');
            exit();
        }
        $channelName = I('jg_name');
        $chanelFlog = I('join_flag');
        $ck_status = I('ck_status');
        $ck_timeSta = I('ck_timeSta');
        $map = array(
            't.node_id' => $this->node_id, 
            't.type' => 7);
        // 'status'=>1
        // 'begin_time'=>array('elt',date('YmdHis')),
        // 'end_time'=>array('egt',date('YmdHis'))
        
        if ($channelName != '') {
            $map['t.name'] = array(
                'like', 
                "%$channelName%");
        }
        if ($chanelFlog !== '') {
            if ($chanelFlog == 1)
                $map['t.join_flag'] = array(
                    array(
                        'like', 
                        '%1%'), 
                    array(
                        'like', 
                        '%3%'), 
                    array(
                        'like', 
                        '%2%'), 
                    'or');
            else if ($chanelFlog == 2)
                $map['t.join_flag'] = array(
                    'like', 
                    '%4%');
            else if ($chanelFlog == 3)
                $map['t.join_flag'] = array(
                    'like', 
                    '%7%');
        }
        if ($ck_status != '') {
            $map['t.status'] = $ck_status;
        }
        if ($ck_timeSta != '') {
            switch ($ck_timeSta) {
                case 1: // 进行中
                    $map['t.begin_time'] = array(
                        'elt', 
                        date("YmdHis"));
                    $map['t.end_time'] = array(
                        'egt', 
                        date("YmdHis"));
                    break;
                case 2: // 已结束
                    $map['t.end_time'] = array(
                        'lt', 
                        date("YmdHis"));
                    break;
                case 3: // 未开始
                    $map['t.begin_time'] = array(
                        'gt', 
                        date("YmdHis"));
                    break;
            }
        }
        import("ORG.Util.Page");
        $count = M()->table(
            array(
                'tchannel' => 't', 
                'tbatch_channel' => 'tc'))
            ->where('t.id = tc.channel_id ')
            ->where($map)
            ->count();
        $p = new Page($count, 8);    
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        $page = $p->show();
        $channelList = $this->getChannelList($map,$p->firstRow,$p->listRows);


           // echo  M()->getlastsql();exit;
        /*foreach ($channelList as &$v) {
            $rs = M()->table(
                array(
                    'tcj_trace' => 'a', 
                    'tbarcode_trace' => 'g'))
                ->where(
                ' a.request_id = g.request_id AND ( g.use_status IN ("1","2") and  g.status =0 )and  a.label_id=' .
                     $v['tcid'])
                ->count();
            $v['verify_count'] = $rs;
        }*/
        
        $this->assign('page', $page);
        $this->assign('post', $_REQUEST);
        $this->assign('channelList', $channelList);
        $this->display();
    }

    /**
     * @param        $map
     * @param        $limitStart
     * @param        $limitEnd
     * @param string $mapAdd  //app端需要未开始进行中已结束条件
     *
     * @return string 付满送sql
     */
    public function getChannelList($map,$limitStart,$limitEnd,$mapAdd = '')
    {
        $channelList = '';

        if($map && $limitEnd){
            //pc端
            $channelList = M()->field('t.*, tc.id tcid,twx.card_class,group_concat(tc.batch_type) batch_type_bak,
            group_concat(tc.batch_id) batch_id_bak')
                    ->table(
                            array(
                                    'tbatch_channel' => 'tc',
                                    'tchannel' => 't'))
                    ->join('tbatch_info ti on ti.m_id = t.batch_id')
                    ->join('twx_card_type twx on twx.card_id = ti.card_id')
                    ->where('t.id = tc.channel_id')
                    ->where($map)
                    ->group('t.id')
                    ->order('t.add_time DESC')
                    ->limit($limitStart . ',' . $limitEnd)
                    ->select();
        }
        if($map && $mapAdd){
            //app端
            $channelList = M()->field('t.*, tc.id tcid,twx.card_class,group_concat(tc.batch_type) batch_type_bak,
            group_concat(tc.batch_id) batch_id_bak')
                    ->table(
                            array(
                                    'tbatch_channel' => 'tc',
                                    'tchannel' => 't'))
                    ->join('tbatch_info ti on ti.m_id = t.batch_id')
                    ->join('twx_card_type twx on twx.card_id = ti.card_id')
                    ->where('t.id = tc.channel_id')
                    ->where($map)
                    ->where($mapAdd)
                    ->group('t.id')
                    ->order('t.add_time DESC')
                    ->select();
        }

        return $channelList? $channelList : '';
    }

    /**
     * APP付满送
     */
    public function wap_paysend()
    {

        $version = $this->version;
        $position = $this->position;

        if($version){

            $map = array(
                    't.node_id' => $this->node_id,
                    't.type' => 7,
                    't.id = tc.channel_id ');

            $underwayWhere = array();
            //进行中
            $underwayWhere['t.begin_time'] = array('elt', date("YmdHis"));
            $underwayWhere['t.end_time'] = array('egt', date("YmdHis"));
            $underwayList = $this->getChannelList($map,'','',$underwayWhere);

            $overWhere = array();
            // 已结束
            $overWhere['t.end_time'] = array('lt', date("YmdHis"));
            $overList = $this->getChannelList($map,'','',$overWhere);

            $notStartWhere = array();
            //未开始
            $notStartWhere['t.begin_time'] = array('gt', date("YmdHis"));
            $notStartList = $this->getChannelList($map,'','',$notStartWhere);

            $tips = '';
            if(!$underwayList && !$overList && !$notStartList){
                $tips = 0;
            }elseif($underwayList){
                $tips = 1;
            }else{
                $tips = 2;
            }
            $this->assign('tips',$tips);

            if($position){
                $this->assign('overList',$overList);
                $this->assign('notStartList',$notStartList);
            }

            $this->assign('underwayList',$underwayList);
        }
        $this->assign('version',$version);
        //$this->display();
    }

    // 详情
    public function details() {
        $channel_id = I('channel_id');
        $channelList = M()->table(array('tpay_give_batch_set'=>'tgs','tchannel'=>'a'))
            ->field('a.*,c.name markName,tgs.limit_amt,tgs.upper_limit_amt')
            ->where("a.id=$channel_id and a.node_id=$this->node_id  and tgs.channel_id=a.id and tgs.batch_channel_id=b.id and b.status=1")
            ->join('tbatch_channel b on a.id=b.channel_id')
            ->join('tmarketing_info c on b.batch_id=c.id')
            ->order('tgs.limit_amt asc')
            ->find();
        $this->assign('chanList', $channelList);
        $this->display();
    }

    /*
     * 停用/启用
     */
    public function disableActive() {
        $chan_id = I('chan_id');
        $channelStatus = M('tchannel')->where(
            array(
                'node_id' => $this->node_id, 
                'id' => $chan_id))->getField('status');
        $changeSta = $channelStatus == 1 ? 2 : 1;
        $statusStr = $channelStatus == 1 ? "停用" : "启用";
        M()->startTrans();
        $changeStatus = M('tchannel')->where(
            "node_id=$this->node_id and id=$chan_id")->save(
            array(
                'status' => $changeSta));
        if ($changeStatus > 0) {
            $changeBtcStatus = M('tbatch_channel')->where(
                array(
                    'node_id' => $this->node_id, 
                    'channel_id' => $chan_id))->save(
                array(
                    'status' => $changeSta));
            if ($changeBtcStatus > 0) {
                M()->commit();
                $this->success('已' . $statusStr);
            } else {
                M()->rollback();
                $this->error($statusStr . '失败！');
            }
        } else {
            M()->rollback();
            $this->error($statusStr . '失败！');
        }
    }
    // 选择互动模块
    public function getBatchList() {
        $id = I('id', null);
        $batchList = M('tmarketing_info')->where("id=$id and status=1")->find();
        if ($batchList) {
            $this->ajaxReturn($batchList, "此活动可用！", 1);
        } else {
            $this->error("此活动不可用！");
        }
    }

    public function salesmng() {
        /**
         * 【付满送V2.2】新增“营业员推广管理” Author: Zhaobl Task: #15733 Date: 2015/12/02
         */
        // 为避免文件冲突,则根据各自的order_id创建目录
        if (! is_dir($this->storageDir)) {
            @mkdir($this->storageDir);
        }
        if (! is_dir($this->storageDir . '/' . $this->useStopDir)) {
            @mkdir($this->storageDir . '/' . $this->useStopDir);
        }
        import('ORG.Util.Page'); // 导入分页类
        
        $this->getStoreList($type = 1);
        $name = I('param.name');
        $shop = I('param.shop') ? $this->StoreList[I('param.shop')] : '';
        
        $status = I('param.status');
        $customizeNo = I('param.customizeNo');
        
        // 为了在页面上保存以查询的条件信息
        $statusOption = array(
            '0' => '全部', 
            '1' => '正常', 
            '2' => '停用');
        
        $salesLists = $this->PayGiveClerkModel->index($this->nodeId, $name, 
            $shop, $status, $customizeNo);
        $salesListCount = count($salesLists);
        
        // 实例化分页类 传入总记录数和每页显示的记录数
        $Page = new Page($salesListCount, 10);
        // 展示数据
        $salesList = $this->PayGiveClerkModel->index($this->nodeId, $name, 
            $shop, $status, $customizeNo, $Page->firstRow, $Page->listRows);
        $show = $Page->show();
        $this->assign('page', $show);
        $this->assign('post', I('post.'));
        $this->assign('statusOption', $statusOption);
        $this->assign('salesList', $salesList);
        $this->display();
    }

    public function getStoreList($type = '') {
        /**
         * #15733
         */
        // 取门店列表
        $storeList = $this->PayGiveClerkModel->getStore('', '', $this->nodeId);
        if ($type == 1) {
            $this->StoreList[0] = '全部';
            $i = 1;
        } else {
            // 若是修改页面 不显示全部选项
            $i = 0;
        }
        foreach ($storeList as $k => $v) {
            $this->StoreList[$i] = $v;
            $i ++;
        }
        $this->assign('storeList', $this->StoreList);
    }

    public function salesmngAdd() {
        /**
         * #15733
         */
        // 新增营业员,弹窗页面
        $this->getStoreList();
        $this->display();
    }

    public function singleAdd() {
        /**
         * #15733
         */
        // 新增营业员操作
        $name = I('param.name'); // 营业员姓名
        $shop = I('param.shop'); // 门店名称
        $phone = I('param.phone_no'); // 电话号码
        $email = I('param.email'); // 邮箱
        $customizeNo = I('param.customizeNo'); // 自定义编号
        $clerk_id = $this->getClerkId(); // 取营业员ID
        
        $id = I('param.id'); // 营业员ID
        
        if (empty($id) && $name && $shop && $phone && $email && $customizeNo) {
            // 如果id为空 执行添加操作
            $result = $this->PayGiveClerkModel->singleAddInfo($clerk_id, 
                $this->nodeId, $name, $shop, $phone, $email, $customizeNo);
            if ($result) {
                // 生成二维码
                $this->mkQRCode($clerk_id, $name);
                $this->success('添加成功');
            } else {
                $this->error('添加失败');
            }
        }
        if (! empty($id) && $name && $shop && $phone && $email && $customizeNo &&
             $id) {
            // 如果id不为空 执行修改操作
            $row = $this->PayGiveClerkModel->doEditSalesInfo($this->nodeId, 
                $name, $shop, $phone, $email, $customizeNo, $id);
            if ($row) {
                $this->success('修改成功');
            } else {
                $this->error('修改失败');
            }
        }
    }

    public function mkQRCode($clerk_id, $name) {
        /**
         * #15733
         */
        // 生成二维码
        import('@.Vendor.phpqrcode.qrcode') or die('include file fail.');
        $errorCorrectionLevel = "L";
        $matrixPointSize = "4";
        $gbkName = iconv('utf-8', 'gb2312', $name);
        $logourl = $this->storageDir . '/' . $gbkName . '.png';
        QRcode::png($clerk_id, $logourl, $errorCorrectionLevel, 
            $matrixPointSize, 2);
    }

    public function getClerkId() {
        /*
         * #15733
         */
        // 获取营业员ID
        $clientId = $this->PayGiveClerkModel->getClientId($this->nodeId); // 取6位旺号
        $sysUniSeq = $this->PayGiveClerkModel->getsysUniSeq(); // 取10位sys_uni_seq
        $clerk_id = $clientId . $sysUniSeq; // 营业员ID
        return $clerk_id;
    }

    public function downLoad() {
        /*
         * #15733
         */
        // 下载走这里
        $fileType = I('param.fileType');
        
        $node_id = $this->nodeId;
        
        if ($fileType == 'batchUploadClerkTemplate') {
            // 下载新增营业员模板
            $basename = '营业员批量添加.csv';
            $fileName = 'Home/Upload/Paysend/batchUploadClerkTemplate.csv';
        }
        if ($fileType == 'downLoadSalesSingleQRCode') {
            // 下载指定员工二维码
            $name = I('param.name');
            $gbkName = iconv('utf-8', 'gb2312', $name);
            $basename = "$name.png";
            $fileName = $this->storageDir . '/' . $gbkName . '.png';
        }
        
        if ($fileType == 'downLoadSalesBatchQRCode') {
            // 批量下载员工二维码压缩文件
            $filesnames = scandir($this->storageDir . '/');
            // 为避免生成的压缩文件里存在多级项目目录 因此在./下创建以node_id命名的目录
            // 将二维码复制到新目录
            @mkdir($node_id);
            foreach ($filesnames as $k => $v) {
                if ($this->getFileType($v) == 'png') {
                    copy($this->storageDir . '/' . $v, $node_id . '/' . $v);
                }
            }
            
            // 生成压缩文件
            $zip = new ZipArchive();
            $fileName = $this->storageDir . '/' . 'ewm.zip';
            if ($zip->open($fileName, ZipArchive::OVERWRITE) === TRUE) {
                $filesnames = scandir($node_id);
                foreach ($filesnames as $k => $v) {
                    if ($this->getFileType($v) == 'png') {
                        $zip->addFile($node_id . '/' . $v);
                    }
                }
                $zip->close();
            }
            $this->deldir($node_id); // 删除目录
            $basename = '员工二维码.zip';
        }
        $fileSize = filesize($fileName);
        header("Content-type: application/octet-stream");
        header(
            "Content-Disposition: attachment; filename=" .
                 iconv('utf-8', 'gb2312', $basename));
        header('content-length:' . $fileSize);
        readfile($fileName);
    }

    public function getFileType($filename) {
        /**
         * #15733
         */
        // 返回文件后缀名
        return substr($filename, strrpos($filename, '.') + 1);
    }

    public function deldir($dir) {
        /**
         * #15733
         */
        // 删除目录
        $dh = opendir($dir);
        while ($file = readdir($dh)) {
            if ($file != "." && $file != "..") {
                $fullpath = $dir . "/" . $file;
                if (! is_dir($fullpath)) {
                    unlink($fullpath);
                } else {
                    $this->deldir($fullpath);
                }
            }
        }
        closedir($dh);
        if (rmdir($dir)) {
            return true;
        } else {
            return false;
        }
    }

    public function upload() {
        /*
         * #15733
         */
        // 批量新增营业员上传走这里
        import('ORG.Net.UploadFile') or die('导入上传包失败');
        $upload = new UploadFile();
        $upload->savePath = $this->storageDir . '/'; // 上传文件保存路径
        $info = $upload->uploadOne($_FILES['myFile']); // 调用上传方法生成信息
        $fileWay = $upload->savePath . $info[0]['savename']; // 获取生成文件路径
        
        $data = fopen($fileWay, 'a+');
        $row = 0; // 总共读取的条数
        $rowSuccess = 0; // 成功条数
        while (! feof($data)) {
            ++ $row;
            $newSalesListLine = utf8Array(fgetcsv($data, 1000)); // 循环读取
            if ($row == 1) {
                $fileField = array(
                    '营业员姓名', 
                    '所属门店', 
                    '手机号码', 
                    '邮箱', 
                    '自定义编号');
                $arrDiff = array_diff_assoc($newSalesListLine, $fileField);
                if (count($newSalesListLine) != count($fileField) ||
                     ! empty($arrDiff)) {
                    fclose($data);
                    @unlink($fileWay);
                    break;
                }
                continue;
            }
            $name = $newSalesListLine[0]; // 营业员姓名
            $shop = $newSalesListLine[1]; // 门店名称
            $phone = $newSalesListLine[2]; // 电话号码
            $email = $newSalesListLine[3]; // 邮箱
            $customizeNo = $newSalesListLine[4]; // 自定义编号
            
            $phoneVerify = "/^1[34578]\d{9}$/"; // 手机正则
            $emailVerify = "/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i"; // 邮箱正则
            $numVerify = "/\d{1,20}/"; //纯数字正则
            if ($name && preg_match($phoneVerify, $phone) &&
                 preg_match($emailVerify, $email) && preg_match($numVerify, $customizeNo)) {
                // 如果数据都合格，进来，走数据库。
                $clerk_id = $this->getClerkId();
                $result = $this->PayGiveClerkModel->singleAddInfo($clerk_id, 
                    $this->nodeId, $name, $shop, $phone, $email, $customizeNo);
                if ($result) {
                    $this->mkQRCode($clerk_id, $name);
                    ++ $rowSuccess; // 计算成功条数
                }
            }
        }
        fclose($data);
        @unlink($fileWay);
        
        $this->assign('rowSuccess', $rowSuccess);
        $this->display('salesUploadResult');
    }

    public function disposeStatus() {
        /*
         * #15733
         */
        // 处理营业员状态 停用 or 启用
        $clerk_id = I('param.id');
        $type = I('param.status');
        $arr = array(
            'useStop' => '1', 
            'useStart' => '0');
        if (isset($arr[$type])) {
            $row = $this->PayGiveClerkModel->updateStatus($clerk_id, 
                $arr[$type]);
            if ($row) {
                // 如果修改成功 则移动二维码
                $name = iconv('utf-8', 'gb2312', 
                    $this->PayGiveClerkModel->getSalesName($clerk_id));
                if ($arr[$type] == 1) {
                    // 停用
                    copy($this->storageDir . '/' . $name . '.png', 
                        $this->storageDir . '/' . $this->useStopDir . '/' . $name .
                             '.png');
                    unlink($this->storageDir . '/' . $name . '.png');
                } else {
                    // 启用
                    copy(
                        $this->storageDir . '/' . $this->useStopDir . '/' . $name .
                             '.png', $this->storageDir . '/' . $name . '.png');
                    unlink(
                        $this->storageDir . '/' . $this->useStopDir . '/' . $name .
                             '.png');
                }
                $this->success('更新成功');
            } else {
                $this->error('更新失败');
            }
        }
    }

    public function editSalesInfo() {
        /*
         * #15733
         */
        // 修改营业员信息页面
        $id = I('param.id');
        $editSingleInfo = $this->PayGiveClerkModel->editSingleInfo($id, 
            $this->nodeId);
        if (! $editSingleInfo) {
            $this->error('参数错误！');
        }
        $storeName = $this->PayGiveClerkModel->getStore(2, 
            $editSingleInfo['store_id']);
        
        $this->getStoreList();
        $this->assign('defaultStoreName', $storeName);
        $this->assign('editSalesInfo', $editSingleInfo);
        $this->display();
    }
}
