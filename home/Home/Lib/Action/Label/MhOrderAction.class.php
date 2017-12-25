<?php

class MhOrderAction extends MyOrderAction {
    public function _initialize() {
        parent::_initialize();
        $type_array = array(
            '0' => '未发放',
            '1' => '已发放');
        $ol_node_id = I('get.node_id');
        if (! session('?groupPhone') || session('cc_node_id') == null) {
                $this->_clearMemberSess();
                $surl = urlencode(
                    '/index.php?g=Label&m=Member&a=index&node_id=' . $ol_node_id);
                $url = U('O2OLogin/index') . "&id=" . $label_id . "&node_id=" .
                     $ol_node_id . "&backcall=bclick&surl=" . $surl;
                redirect($url);
            }
            if ($ol_node_id != session('cc_node_id')) {
                $this->_clearMemberSess();
                $surl = urlencode(
                    '/index.php?g=Label&m=Member&a=index&node_id=' . $ol_node_id);
                $url = U('O2OLogin/index') . "&id=" . $label_id . "&node_id=" .
                     $ol_node_id . "&backcall=bclick&surl=" . $surl;
                redirect($url);
            }
            $re = M('tmember_info')->where(
                array(
                    'phone_no' => session('groupPhone'), 
                    'node_id' => $ol_node_id))->select();
            if (! $re) {
                $this->_clearMemberSess();
                $surl = urlencode(
                    '/index.php?g=Label&m=Member&a=index&node_id=' . $ol_node_id);
                $url = U('O2OLogin/index') . "&id=" . $label_id . "&node_id=" .
                     $ol_node_id . "&backcall=bclick&surl=" . $surl;
                redirect($url);
            }
        $this->assign('type_array', $type_array);
    }

     //美惠分销
    function mhMyCommission() {
        $node_id   = I('get.node_id');
        $user_info = session('store_mem_id' . $this->node_id);
        $member_id = $user_info['user_id'];
        if ($node_id == null) {
            $node_id = session("node_id");
        }
        $saler_id = $_SESSION['twfxSalerID'];
        //个人资料
        $cataObj = D('Integral');
        $user_info = $cataObj->user_info($member_id);
        if ($user_info['name'] == null) {
            $user_info['name'] = $user_info['nickname'];
        }
        $saler_info = M('twfx_saler')->where(array('id' => $saler_id, 'node_id' => $node_id))->find();
        $level_arr = array(
                    '2' => '钻石',
                    '3' => '金牌',
                    '4' => '银牌'
                    );
        if (in_array($saler_info['level'], array('1'))) {
            $this->error('门店无法登陆');
        }
        $wfxSalerModel = M('twfx_saler');
        $infoArray = $wfxSalerModel->join(
            'twfx_node_info ON twfx_node_info.node_id = twfx_saler.node_id')
            ->where(
            array(
                'twfx_saler.id' => $saler_id, 
                'twfx_saler.node_id' => $node_id, 
                'twfx_saler.phone_no' => $_SESSION['groupPhone']))
            ->field(
            'twfx_saler.bank_account, twfx_saler.bank_name, twfx_node_info.settle_type, twfx_node_info.lowest_get_money, twfx_node_info.account_type, twfx_saler.alipay_account, twfx_node_info.customer_bind_flag')
            ->find();

        $this->assign('customer', $infoArray['customer_bind_flag']);
        
        if ($infoArray['account_type'] == '1') {
            $this->assign('account', $infoArray['alipay_account']);
            $this->assign('accountType', '支付宝帐');
            $this->assign('bankArray', 0);
        } elseif ($infoArray['account_type'] == '2') {
            $time = floor(strlen($infoArray['bank_account']) / 4);
            $accountStr = '';
            for ($i = 0; $i <= $time; $i ++) {
                $accountStr .= substr($infoArray['bank_account'], $i * 4, 4) .
                     ' ';
            }
            
            $key = array_search($infoArray['bank_name'], C('defaultBankName'));
            $bankArray = C('defaultBankName');
            unset($bankArray[$key]);
            array_unshift($bankArray, $infoArray['bank_name']);
            $this->assign('bankArray', json_encode($bankArray));
            $this->assign('account', $accountStr);
            $this->assign('bankName', $infoArray['bank_name']);
            $this->assign('accountType', '银行卡卡');
        }
        $this->assign('commissionType', $infoArray['settle_type']);
        $this->assign('lowestMoney', $infoArray['lowest_get_money']);



        $wfxCustomerRelationModel = M('twfx_customer_relation');
        $customerCount = $wfxCustomerRelationModel->where(
            array(
                'node_id'  => $node_id, 
                'saler_id' => $saler_id, 
                'status'   => '1'))->count();
        $this->assign('customerCount', $customerCount);
        $member_id = $_SESSION['store_mem_id'.$node_id]['user_id'];

        
        //提成
        // $searchCondition             = array();
        // $searchCondition['node_id']  = $node_id;
        // $searchCondition['saler_id'] = $saler_id;
        
        // $wfxTraceModel = M('twfx_trace');
        // $totalCommission = $wfxTraceModel->where($searchCondition)->sum(
        //     'bonus_amount');
        // if (empty($totalCommission)) {
        //     $totalCommission = $this->_getPriceFormat();
        // }
        // $this->assign('totalCommission', explode('.', $totalCommission));

        // $searchCondition['user_get_flag'] = '3';
        // $commission = $wfxTraceModel->where($searchCondition)->sum(
        //     'bonus_amount');
        
        // if (empty($commission)) {
        //     $commission = $this->_getPriceFormat();
        // }
        // $this->assign('commission', explode('.', $commission));

        // $recruitStatus = M('tmarketing_info')->where(
        //     array(
        //         'node_id'    => $_SESSION['node_id'], 
        //         'batch_type' => '3001'))->getfield('status');
        // $this->assign('recruitStatus', $recruitStatus);

        // $searchCondition['user_get_flag'] = '0';
        // $thisMonthBonusCommission = $wfxTraceModel->where($searchCondition)->sum(
        //     'bonus_amount');
        // if (empty($thisMonthBonusCommission)) {
        //     $thisMonthBonusCommission = $this->_getPriceFormat();
        // }
        // $this->assign('remain_amount', $thisMonthBonusCommission);

        // $thisMonth = date("Ym") . '00000000,' . date("Ym") . '31235959';
        // $searchCondition['add_time'] = array(
        //     'between', 
        //     $thisMonth);
        // $searchCondition['user_get_flag'] = array(
        //     'neq', 
        //     '100');
        // $thisMonthCommission = $wfxTraceModel->where($searchCondition)->sum(
        //     'bonus_amount');
        
        // if (empty($thisMonthCommission)) {
        //     $thisMonthCommission = $this->_getPriceFormat();
        // }
        // $this->assign('thisMonthCommission', explode('.', $thisMonthCommission));

        // $saleCount = $wfxSalerModel->where(
        //     array(
        //         'parent_id' => $saler_id, 
        //         'role'      => '2', 
        //         'node_id'   => $node_id, 
        //         'status'    => array(
        //             'neq', 
        //             '5')))->count();
        // $this->assign('saleCount', $saleCount);
        


        // $time = date('Ym',strtotime("-1 month"));
        $time = date('Ym',time());
        //差异提成
        //上月总数
        $saler_sum = M('tfb_mh_wfx_settlement_trace')->where(array('saler_id' => $saler_id, 'trace_month' => $time))->sum('amount');
        if (empty($saler_sum)) {
            $saler_sum = '0.00';
        }
        $this->assign('saler_sum',$saler_sum);
        //累计总数
        $all_saler_amount = M('tfb_mh_wfx_trace')->where(array('saler_id' => $saler_id, 'trace_type' => 'D'))->sum('amount');
        if (empty($all_saler_amount)) {
            $all_saler_amount = '0.00';
        }
        $this->assign('all_saler_amount',$all_saler_amount);
        //已发总数
        $all_get_amount = M('tfb_mh_wfx_trace')->where(array('saler_id' => $saler_id, 'trace_type' => 'D','user_get_flag' => '1'))->sum('amount');
        if (empty($all_get_amount)) {
            $all_get_amount = '0.00';
        }
        $this->assign('all_get_amount',$all_get_amount);
        //团队奖金
        $team_info = M('tfb_mh_wfx_trace')->where(array('saler_id' => $saler_id, 'trace_type' => 'B'))->select();
        
        foreach ($team_info as $key => $value) {
            //上个月奖金
            if ($time == $value['trace_month']) {
                $last_month_bonus += $value['amount'];
            }

            //累计总奖金
            $all_bonus += $value['amount'];

            //已发放总奖金
            if ($value['user_get_flag'] === '1') {
                $get_bonus += $value['amount'];
            }
        }

        //招募奖励
        $recruit_info = M('tfb_mh_wfx_trace')->where(array('saler_id' => $saler_id, 'trace_type' => 'A'))->select();
        foreach ($recruit_info as $key => $value) {
            //上个月奖金
            if ($time == $value['trace_month']) {
                $recruit += $value['amount'];
            }

            //累计总奖金
            $all_recruit += $value['amount'];

            //已发放总奖金
            if ($value['user_get_flag'] === '1') {
                $get_recruit += $value['amount'];
            }
        }

        //当月销售额排名
        $now        = date('Ym',time());
        $this->meiHuiModel = D('FbMhWfx', 'Model');
        $month=$this->meiHuiModel->GetMonth(-1);
        $sales   =   $this->meiHuiModel->getRemainReward($month)+$this->meiHuiModel->getAllReward();
        $all_sales = M('tfb_mh_wfx_trace')->where(array('saler_id' => $saler_id, 'trace_type' => 'C'))->sum('amount');
        $get_sales = M('tfb_mh_wfx_trace')->where(array('saler_id' => $saler_id, 'trace_type' => 'C', 'user_get_flag'=>1))->count('amount');
        if($last_month_bonus == null){
            $last_month_bonus = 0;
        }
        if ($all_bonus == null) {
            $all_bonus = 0;
        }
        if ($get_bonus == null) {
            $get_bonus = 0;            
        }
        if($recruit == null){
            $recruit = 0;
        }
        if ($all_recruit == null) {
            $all_recruit = 0;
        }
        if ($get_recruit == null) {
            $get_recruit = 0;            
        }if($sales == null){
            $sales = 0;
        }
        if ($all_sales == null) {
            $all_sales = 0;
        }
        if ($get_sales == null) {
            $get_sales = 0;            
        }
        
        //名次
        $sales_ranking = M('tfb_mh_wfx_ranking_day')->where(array('saler_id' => $saler_id, 'trace_month' => $now))->getField('ranking');
        if ($sales_ranking == null) {
            $sales_ranking = '未上榜';
        }
        if ($_SESSION['twfxRole'] == '1') {
            $customerUrl = U('Label/MyOrder/dealerCustomer', 
                array(
                    'node_id' => $this->node_id));
        } elseif ($_SESSION['twfxRole'] == '2') {
            $undeliveryBookOrderCount = M('twfx_book_order')->where(
                array(
                    'node_id' => $this->node_id, 
                    'order_phone' => $_SESSION['groupPhone'], 
                    'delivery_status' => '0'))->count();
            $this->assign('bookCount', $undeliveryBookOrderCount);
            $customerUrl = U('Label/MyOrder/myCustomer', 
                array(
                    'node_id' => $this->node_id));
        }
        $this->assign('customerUrl', $customerUrl);
        $this->assign('sales_ranking', $sales_ranking);
        $this->assign('get_sales', $get_sales);
        $this->assign('sales', $sales);
        $this->assign('all_sales', $all_sales);
        $this->assign('get_recruit', $get_recruit);
        $this->assign('all_recruit', $all_recruit);
        $this->assign('recruit', $recruit);
        $this->assign('last_month_bonus', $last_month_bonus);
        $this->assign('all_bonus', $all_bonus);
        $this->assign('get_bonus', $get_bonus);
        $this->assign('saler_info', $saler_info);
        $this->assign('level_arr', $level_arr);
        $this->assign('user_info', $user_info);
        $this->assign('node_id', $node_id);
        $this->display();
    }

    //个人详情
    function mhMyDetails(){
        $all_day    = date('t',time());
        $day        = date('d',time());
        $remain_day = $all_day - $day;
        $thisMonth  = date("Ym") . '00000000,' . date("Ym") . '31235959';
        $node_id    = $_SESSION['node_id'];
        //当前实收金额
        $data = array(
            'saler_id'   => $_SESSION['twfxSalerID'],
            'node_id'    => $node_id,
            'order_type' => '2',
            'add_time'   => array('between', $thisMonth)
            );
        $amount = M('ttg_order_info')->where($data)->sum('order_amt');

        if ($amount == null) {
            $amount = 0;
        }
        //未消费天数
        $last_time = M('ttg_order_info')->where($data)->getfield('max(update_time)');
        if ($last_time == null) {
            $last_time = M('twfx_saler')->where(array('id' => $_SESSION['twfxSalerID']))->getfield('audit_time');
        }
        $last_time = strtotime($last_time);
        $now_time  = time();
        $day       = floor(($now_time - $last_time)/3600/24);
        $data_user = array(
            'id'      => $_SESSION['twfxSalerID'],
            'node_id' => $node_id
            );
        //当前等级
        $saler_level = M('twfx_saler')->where($data_user)->getField('level');
        $this->assign('day', $day);
        $this->assign('saler_level', $saler_level);
        $this->assign('amount', $amount);
        $this->assign('remain_day', $remain_day);
        $this->display();
    }

    //招募
    function recruit(){
        $saler_id            = $_SESSION['twfxSalerID'];
        $nowP                = I('p', null, 'mysql_real_escape_string');
        empty($nowP) ? $nowP = 1 : $nowP;
        $pageCount           = 5; // 每页显示条数
        $limit               = ($nowP - 1) * $pageCount . ',' . $pageCount;
        $all_trace = M('tfb_mh_wfx_trace')->where(array('saler_id' => $saler_id, 'trace_type' => 'A'))->order('trace_time desc')->limit($limit)->select();
        foreach ($all_trace as $key => $value) {
            $all_month[] = $value['trace_month'];
        }
        $trace_details = M('tfb_mh_wfx_recruit_trace a')
                        ->join('twfx_saler b ON a.saler_id = b.id')
                        ->field('a.*,b.phone_no')
                        ->where(array('a.trace_month' => array('in',$all_month), 'a.referee_id' => $saler_id))->select();
        foreach ($all_trace as $key => $value) {
            $all_trace[$key]['trace_time'] = date('Y年m月',strtotime($value['trace_month'].'01000000'));
            foreach ($trace_details as $key1 => $value1) {
                if ($value['trace_month'] == $value1['trace_month']) {
                    $value1['trace_time'] = date('Y-m-d H:i:s', strtotime($value1['trace_time']));
                    $all_trace[$key]['trace_details'][] = $value1;
                }
            }
        }
        $type_array = array(
            '0' => '未发放',
            '1' => '已发放');
        if (I('in_ajax', 0, 'intval') == 1) {
            foreach ($all_trace as $key => $value) {
            echo '<div class="box"><div class="detail month">
                    <div class="name">
                       <h3>'.$value['trace_time'].'</h3>
                    </div>
                    <div class="status">
                        <p class="on">￥'.$value['amount'].'</p>
                        <p class="on">'.$type_array[$value['user_get_flag']].'</p>
                    </div>
                </div>';
            foreach ($value['trace_details'] as $key => $vo) {
                echo '<div class="detail bbn">
                        <div class="name">
                            <p>'.$vo['phone_no'].'</p>
                            <p class="time">'.$vo['trace_time'].'</p>
                        </div>
                        <div class="status">
                            <p class="on">￥'.$vo['amount'].'</p>
                        </div>
                    </div>
                    <div class="m-gap"></div></div> ';
                }
            }
        } else {
            $gets            = I('get.');
            $gets['in_ajax'] = 1;
            $gets['p']       = 2;
            $nextUrl = U('', $gets);
            $this->assign('nextUrl', $nextUrl);
            $this->assign('all_trace', $all_trace);
            $this->display();
        }
    }

    //团队
    function team(){
        $saler_id            = $_SESSION['twfxSalerID'];
        $nowP                = I('p', null, 'mysql_real_escape_string');
        empty($nowP) ? $nowP = 1 : $nowP;
        $pageCount           = 6; // 每页显示条数
        $limit               = ($nowP - 1) * $pageCount . ',' . $pageCount;

        $team_info = M('tfb_mh_wfx_trace')->where(array('saler_id' => $saler_id, 'trace_type' => 'B'))->order('trace_time desc')->limit($limit)->select();
        foreach ($team_info as $key => $value) {
            $team_info[$key]['trace_month'] = date('Y年m月',strtotime($value['trace_month'].'01000000'));
        }
        $type_array = array(
            '0' => '未发放',
            '1' => '已发放');
        if (I('in_ajax', 0, 'intval') == 1) {
            foreach ($team_info as $key => $value) {
            echo '<div class="box">
                    <div class="detail bbn">
                        <div class="name">
                            <p>'.$value['trace_month'].'</p>
                        </div>
                        <div class="status">
                            <p class="on">￥'.$value['amount'].'</p>
                            <p class="on">'.$type_array[$value['user_get_flag']].'</p>
                        </div>
                    </div>
                    <div class="m-gap"></div>
                </div>';
            }
        } else {
            $gets            = I('get.');
            $gets['in_ajax'] = 1;
            $gets['p']       = 2;
            $nextUrl         = U('', $gets);
            $this->assign('nextUrl', $nextUrl);
            $this->assign('team_info', $team_info);
            $this->display();
        }
    }

    //差额提成详情
    function commissionDetails(){
        $saler_id            = $_SESSION['twfxSalerID'];
        $nowP                = I('p', null, 'mysql_real_escape_string');
        empty($nowP) ? $nowP = 1 : $nowP;
        $pageCount           = 5; // 每页显示条数
        $limit               = ($nowP - 1) * $pageCount . ',' . $pageCount;
        $all_trace = M('tfb_mh_wfx_trace')->where(array('saler_id' => $saler_id, 'trace_type' => 'D'))->order('trace_time desc')->limit($limit)->select();
        foreach ($all_trace as $key => $value) {
            $all_month[] = $value['trace_month'];
        }
        $trace_details = M('tfb_mh_wfx_settlement_trace a')
                        ->join('twfx_saler b ON a.saler_id = b.id')
                        ->field('a.*,b.phone_no')
                        ->where(array('a.trace_month' => array('in',$all_month), 'a.saler_id' => $saler_id))->select();
        foreach ($all_trace as $key => $value) {
            $all_trace[$key]['trace_time'] = date('Y年m月',strtotime($value['trace_month'].'01000000'));
            foreach ($trace_details as $key1 => $value1) {
                if ($value['trace_month'] == $value1['trace_month']) {
                    $value1['trace_time'] = date('Y-m-d H:i:s', strtotime($value1['trace_time']));
                    $all_trace[$key]['trace_details'][] = $value1;
                }
            }
        }
        $type_array = array(
            '0' => '未发放',
            '1' => '已发放');
        if (I('in_ajax', 0, 'intval') == 1) {
            foreach ($all_trace as $key => $value) {
            echo '<div class="box"><div class="detail month">
                    <div class="name">
                       <h3>'.$value['trace_time'].'</h3>
                    </div>
                    <div class="status">
                        <p class="on">￥'.$value['amount'].'</p>
                        <p class="on">'.$type_array[$value['user_get_flag']].'</p>
                    </div>
                </div>';
            foreach ($value['trace_details'] as $key => $vo) {
                echo '<div class="detail bbn">
                        <div class="name">
                            <p>'.$vo['phone_no'].'</p>
                            <p class="time">'.$vo['trace_time'].'</p>
                        </div>
                        <div class="status">
                            <p class="on">￥'.$vo['amount'].'</p>
                        </div>
                    </div>
                    <div class="m-gap"></div></div> ';
                }
            }
        } else {
            $gets            = I('get.');
            $gets['in_ajax'] = 1;
            $gets['p']       = 2;
            $nextUrl = U('', $gets);
            $this->assign('nextUrl', $nextUrl);
            $this->assign('all_trace', $all_trace);
            $this->display();
        }
    }

    //销售额
    function sales(){
        $saler_id            = $_SESSION['twfxSalerID'];
        $nowP                = I('p', null, 'mysql_real_escape_string');
        empty($nowP) ? $nowP = 1 : $nowP;
        $pageCount           = 6; // 每页显示条数
        $limit               = ($nowP - 1) * $pageCount . ',' . $pageCount;
        $sales_info = M('tfb_mh_wfx_trace')->where(array('saler_id' => $saler_id, 'trace_type' => 'C'))->order('trace_time desc')->limit($limit)->select();
        foreach ($sales_info as $key => $value) {
            $sales_info[$key]['trace_month'] = date('Y年m月',strtotime($value['trace_month'].'01000000'));
        }

        $type_array = array(
            '0' => '未发放',
            '1' => '已发放');
        if (I('in_ajax', 0, 'intval') == 1) {
            foreach ($sales_info as $key => $value) {
            echo '<div class="box">
                        <div class="detail bbn">
                            <div class="name">
                                <p>'.$value['trace_month'].'</p>
                            </div>
                            <div class="status">
                                <p class="on">￥'.$value['amount'].'</p>
                                <p class="on">'.$type_array[$value['user_get_flag']].'</p>
                            </div>
                        </div>
                        <div class="m-gap"></div>
                </div>';
            }
        } else {
            $gets            = I('get.');
            $gets['in_ajax'] = 1;
            $gets['p']       = 2;
            $nextUrl         = U('', $gets);
            $this->assign('nextUrl', $nextUrl);
            $this->assign('sales_info', $sales_info);
            $this->display();
        }
    }

    public function _clearMemberSess(){
        session('groupPhone',null);
        session('cc_node_id',null);
        session('store_mem_id'.$this->node_id, null);
    }
}
    
 
