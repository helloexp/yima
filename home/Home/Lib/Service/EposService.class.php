<?php

/* EPOS */
class EposService
{

    public $opt = array();

    public $errcode = 0;

    public $resp_arr = array(
        '0000' => '交易成功',
        '1001' => '未找到该终端',
        '1002' => '未找到卡券列表',
        '1003' => '未找到营销活动',
        '1004' => '未找到发码记录',
        '1005' => '未找到指定发码记录',
        '1006' => '库存不足[01]',
        '1007' => '库存不足[02]',
        '1010' => '接口调用失败'
    );

    public function __construct()
    {
        C('LOG_PATH', LOG_PATH . 'EPOS_'); // 重新定义目志目录
    }

    /* 设置参数 */
    public function setopt()
    {}

    private function return_json($resp_id, $data)
    {
        $ret_arr = array(
            "resp_id" => $resp_id,
            "resp_desc" => $this->resp_arr[$resp_id],
            "data" => $data
        );
        $this->_log("return json " . json_encode($ret_arr));
        return json_encode($ret_arr);
    }

    public function get_batch_info_list($pos_id, $page, $like = null)
    {
        $where = "pos_id = '" . $pos_id . "' ";
        $pos_info = M()->table('tpos_info')
            ->where($where)
            ->find();
        if (! $pos_info) {
            $this->_log("get_batch_info_list find tpos_info error " . M()->_sql());
            return $this->return_json("1001", null);
        }
        if ($like != null) {
            $like_where = "and (b.material_code like '%" . $like . "%' or b.batch_short_name like '%" . $like . "%' )";
        } else {
            $like_where = '';
        }
        $end_time = "and b.end_time >" . date('YmdHis', time());
        // $where = "b.status = '0' and b.id IN (SELECT b_id FROM
        // tbatch_info_tostore_exp WHERE store_id_list like
        // '%".$pos_info['store_id']."%' OR (node_id =
        // '".$pos_info['node_id']."' AND TYPE = '1')) ". $like_where.
        // $end_time;
        $where = "(store_id_list LIKE '%" . $pos_info['store_id'] . "%' OR (c.node_id = '" . $pos_info['node_id'] . "' AND TYPE = '1')) AND b.status ='0'" . $like_where . $end_time;
        
        import('ORG.Util.Page'); // 导入分页类
        $count = M()->table('tbatch_info b')
            ->join('left join tgoods_info g on b.goods_id = g.goods_id inner join tbatch_info_tostore_exp c on b.id = c.b_id')
            ->where($where)
            ->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $batch_info_list = M()->table('tbatch_info b')
            ->join('left join tgoods_info g on b.goods_id = g.goods_id INNER JOIN tbatch_info_tostore_exp c ON b.id = c.b_id')
            ->where($where)
            ->field('b.*,g.source')
            ->order('b.id desc')
            ->page($page . ',20')
            ->select();
        if (! $batch_info_list) {
            $this->_log("get_batch_info_list find tbatch_info error " . M()->_sql());
            return $this->return_json("1002", null);
        }
        $data_arr = array();
        $data_arr['count'] = 0;
        $data_arr['page'] = $show;
        foreach ($batch_info_list as $batch_info) {
            $data['id'] = $batch_info['id'];
            $data['custom_no'] = $batch_info['material_code'];
            $data['source'] = $batch_info['source'];
            $data['batch_short_name'] = $batch_info['batch_short_name'];
            if ($batch_info['storage_num'] == - 1)
                $data['remain_num'] = - 1;
            else
                $data['remain_num'] = $batch_info['remain_num'];
            $data['end_time'] = $batch_info['end_time'];
            $data_arr['batch_info_list'][] = $data;
            $data_arr['count'] ++;
        }
        return $this->return_json("0000", $data_arr);
    }

    public function get_batch_info($pos_id, $batch_info_id)
    {
        $where = "pos_id = '" . $pos_id . "' ";
        $pos_info = M()->table('tpos_info')
            ->where($where)
            ->find();
        if (! $pos_info) {
            $this->_log("get_batch_info find tpos_info error " . M()->_sql());
            return $this->return_json("1001", null);
        }
        $where = "status = '0' and id = " . $batch_info_id;
        $batch_info = M()->table('tbatch_info')
            ->where($where)
            ->find();
        if (! $batch_info) {
            $this->_log("get_batch_info find tbatch_info error " . M()->_sql());
            return $this->return_json("1002", null);
        }
        $where = "status = '0' and goods_id = '" . $batch_info['goods_id'] . "'";
        $goods_info = M()->table('tgoods_info')
            ->where($where)
            ->find();
        if (! $goods_info) {
            $this->_log("get_batch_info find tgoods_info error " . M()->_sql());
            return $this->return_json("1002", null);
        }
        $data_arr = array();
        $data['batch_short_name'] = $batch_info['batch_short_name'];
        $data['custom_no'] = $batch_info['material_code'];
        $data['img_url'] = get_upload_url('./Home/Upload/' . $goods_info['goods_image']);
        if ($batch_info['storage_num'] == - 1)
            $data['remain_num'] = - 1;
        else
            $data['remain_num'] = $batch_info['remain_num'];
        $data['verify_end_type'] = $batch_info['verify_end_type'];
        $data['verify_end_date'] = $batch_info['verify_end_date'];
        // 取该终端发送统计数
        $where = "pos_id = '" . $pos_id . "' and b_id = " . $batch_info_id;
        $rs = M()->table('tpos_day_count')
            ->where($where)
            ->field('IFNULL(SUM(send_num), 0) as send_num_sum')
            ->find();
        if (! $rs) {
            $this->_log("get_batch_info stat error " . M()->_sql());
            return false;
        }
        $this->_log("get_batch_info stat error " . M()->_sql());
        $data['send_num_sum'] = $rs['send_num_sum'];
        return $this->return_json("0000", $data);
    }

    function send_reqid()
    {
        $data = M()->query("SELECT _nextval('epos_send_seq') as reqid FROM DUAL");
        if (! $data) {
            $this->_log('epos_send_seq fail!' . M()->_sql());
            $req = rand(1, 999999);
        } else {
            $req = $data[0]['reqid'];
        }
        return date('YmdHis') . str_pad($req, 6, '0', STR_PAD_LEFT);
    }

    public function batch_info_send($pos_id, $epos_acount, $batch_info_id, $phone_no, $send_num)
    {
        $this->_log("batch_info_send find tpos_info info $pos_id, $epos_acount, $batch_info_id, $phone_no, $send_num");
        $where = "pos_id = '" . $pos_id . "' ";
        $pos_info = M()->table('tpos_info')
            ->where($where)
            ->find();
        if (! $pos_info) {
            $this->_log("batch_info_send find tpos_info error " . M()->_sql());
            return $this->return_json("1001", null);
        }
        $where = "status = '0' and id = " . $batch_info_id;
        $batch_info = M()->table('tbatch_info')
            ->where($where)
            ->find();
        if (! $batch_info) {
            $this->_log("batch_info_send find tbatch_info error " . M()->_sql());
            return $this->return_json("1002", null);
        }
        // 获取营销活动类型
        $where = "id = " . $batch_info['m_id'];
        $marketing_info = M()->table('tmarketing_info')
            ->where($where)
            ->find();
        if (! $marketing_info) {
            $this->_log("batch_info_send find marketing_info error " . M()->_sql());
            return $this->return_json("1003", null);
        }
        // 获取发送参数
        $RemoteRequest = D('RemoteRequest', 'Service');
        $data_from = 'P';
        $data['succ_num'] = 0;
        $data['err_info'] = '';
        // 循环发送
        for ($i = 0; $i < intval($send_num); $i ++) {
            // 启动事务 锁定tbatch_info 判断库存数 扣减库存
            M()->startTrans();
            $where = "status = '0' and id = " . $batch_info_id;
            $batch_info = M('TbatchInfo')->lock(true)
                ->where($where)
                ->find();
            if ($batch_info['storage_num'] != - 1) // 非不限库存
{
                if (($batch_info['remain_num'] - 1) >= 0) {
                    $batch_info['remain_num'] --;
                    $rs = M('TbatchInfo')->where($where)->save($batch_info);
                    if ($rs === false) {
                        M()->rollback();
                        $data['err_info'] = '库存不足，发送成功' . $data['succ_num'] . '个';
                        $this->_log("batch_info_send update tbatch_info error " . M()->_sql());
                        return $this->return_json("1006", $data);
                    }
                } else {
                    M()->rollback();
                    $data['err_info'] = '库存不足，发送成功' . $data['succ_num'] . '个';
                    $this->_log("batch_info_send remain_num not enough " . $data['err_info']);
                    return $this->return_json("1007", $data);
                }
            }
            $TransactionID = $this->send_reqid();
            $req_data = "&node_id=" . $batch_info['node_id'] . "&phone_no=" . $phone_no . "&batch_no=" . $batch_info['batch_no'] . "&request_id=" . $TransactionID . "&data_from=" . $data_from . "&batch_info_id=" . $batch_info_id . "&batch_type=" . $marketing_info['batch_type'] . "&store_id=" . $pos_info['store_id'] . "&channel_id=&user_id=" . $epos_acount . "&pos_id=" . $pos_id;
            // 循环发送 requestWcAppServ($data)
            $resp_array = $RemoteRequest->requestWcAppServ($req_data);
            if (! $resp_array || ($resp_array['resp_id'] != '0000' && $resp_array['resp_id'] != '0001')) {
                $data['err_info'] = $resp_array['resp_desc'];
                $this->_log("requestWcAppServ error " . print_r($resp_array, true));
                M()->rollback();
                break;
            } else {
                $data['succ_num'] ++;
                M()->commit(); // 提交事务
            }
        }
        return $this->return_json("0000", $data);
    }

    public function get_send_list($pos_id, $page, $like = null, $status = '')
    {
        $where    = "pos_id = '" . $pos_id . "' ";
        $pos_info = M()->table('tpos_info')->where($where)->find();
        if (!$pos_info) {
            $this->_log("get_batch_info_list find tpos_info error " . M()->_sql());
            return $this->return_json("1001", null);
        }
        if ($like != null) {
            $like_where = "and (b.phone_no like '%" . $like . "%' or i.batch_short_name like '%" . $like . "%' )";
        } else {
            $like_where = '';
        }
        $where = "b.pos_id = '" . $pos_id . "'  and b.trans_type = '0001' " . $like_where;
        $where .= ' and b.status in (' . $status . ')';

        import('ORG.Util.Page'); // 导入分页类
        $count = M()->table('tbarcode_trace b')->join('left join tbatch_info i on b.b_id = i.id')->where($where)->field(
                        'b.req_seq'
                )->count();

        $Page               = new Page($count, 20);
        $show               = $Page->show();
        $barcode_trace_list = M()->table('tbarcode_trace b')->join('left join tbatch_info i on b.b_id = i.id')->where(
                        $where
                )->field(
                        'b.req_seq as req_seq,  i.batch_short_name as batch_short_name, b.phone_no as phone_no, b.trans_time as trans_time,b.status'
                )->order('b.id desc')->page($page . ',20')->select();

        if (!$barcode_trace_list) {
            $this->_log("get_send_list find tbarcode_trace error " . M()->_sql());
            return $this->return_json("1004", null);
        }
        $data_arr          = array();
        $data_arr['count'] = 0;
        $data_arr['page']  = $show;
        foreach ($barcode_trace_list as $barcode_trace) {
            $data['req_seq']          = $barcode_trace['req_seq'];
            $data['batch_short_name'] = $barcode_trace['batch_short_name'];
            $data['phone_no']         = $barcode_trace['phone_no'];
            $data['trans_time']       = $barcode_trace['trans_time'];
            $data['status']           = $barcode_trace['status'];
            $data_arr['send_list'][]  = $data;
            $data_arr['count']++;
        }
        return $this->return_json("0000", $data_arr);
    }

    public function get_send_info($pos_id, $req_seq)
    {
        $where = "pos_id = '" . $pos_id . "' ";
        $pos_info = M()->table('tpos_info p')
            ->join('LEFT JOIN tstore_info t ON p.`store_id` = t.`store_id`  LEFT JOIN  tnode_info n ON p.`node_id` = n.`node_id` ')
            ->where($where)
            ->find();
        if (! $pos_info) {
            $this->_log("get_send_info find tpos_info error " . M()->_sql());
            return $this->return_json("1001", null);
        }
        
        $where = "b.pos_id = '" . $pos_id . "' and b.trans_type = '0001' and req_seq = '" . $req_seq . "'";
        $barcode_trace = M()->table('tbarcode_trace b')
            ->join('left join tbatch_info i on b.b_id = i.id')
            ->where($where)
            ->field('b.req_seq as req_seq,  i.batch_short_name as batch_short_name, b.phone_no as phone_no, b.trans_time as trans_time, b.status as status, b.user_id as user_id')
            ->find();
        if (! $barcode_trace) {
            $this->_log("get_send_info find tbarcode_trace error " . M()->_sql());
            return $this->return_json("1004", null);
        }
        
        $data['pos_name'] = $pos_info['pos_name'];
        $data['store_name'] = $pos_info['store_name'];
        $data['node_name'] = $pos_info['node_name'];
        $data['batch_short_name'] = $barcode_trace['batch_short_name'];
        $data['req_seq'] = $barcode_trace['req_seq'];
        $data['status'] = $barcode_trace['status'];
        $data['trans_time'] = $barcode_trace['trans_time'];
        $data['phone_no'] = $barcode_trace['phone_no'];
        $data['epos_acount'] = $barcode_trace['user_id'];
        return $this->return_json("0000", $data);
    }
    // 重发
    public function resend($pos_id, $req_seq, $epos_acount)
    {
        // 校验终端号
        $where = "pos_id = '" . $pos_id . "' ";
        $pos_info = M()->table('tpos_info p')
            ->join('LEFT JOIN tstore_info t ON p.`store_id` = t.`store_id`  LEFT JOIN  tnode_info n ON p.`node_id` = n.`node_id` ')
            ->where($where)
            ->find();
        if (! $pos_info) {
            $this->_log("get_send_info find tpos_info error " . M()->_sql());
            return $this->return_json("1001", null);
        }
        
        // 校验原流水
        $where = "b.pos_id = '" . $pos_id . "' and b.status = '0' and b.trans_type = '0001' and req_seq = '" . $req_seq . "'";
        $barcode_trace = M()->table('tbarcode_trace b')
            ->join('left join tbatch_info i on b.b_id = i.id')
            ->where($where)
            ->field('b.req_seq as req_seq,  i.batch_short_name as batch_short_name, b.phone_no as phone_no, b.trans_time as trans_time, b.status as status, b.user_id as user_id, b.node_id as node_id, b.request_id as request_id')
            ->find();
        if (! $barcode_trace) {
            $this->_log("get_send_info find tbarcode_trace error " . M()->_sql());
            return $this->return_json("1004", null);
        }
        
        // 调用重发接口
        $data['err_info'] = '';
        $req_data = "a=CodeResendReq&node_id=" . $barcode_trace['node_id'] . "&request_id=" . $barcode_trace['request_id'] . "&user_id=" . $epos_acount;
        $resp_array = $this->requestWcAppServ($req_data);
        if (! $resp_array || ($resp_array['resp_id'] != '0000' && $resp_array['resp_id'] != '0001')) {
            $data['err_info'] = $resp_array['resp_desc'];
            return $this->return_json('1010', $data);
        }
        return $this->return_json("0000", $data);
    }
    
    // 撤销
    public function cancel($pos_id, $req_seq, $epos_acount)
    {
        // 校验终端号
        $where = "pos_id = '" . $pos_id . "' ";
        $pos_info = M()->table('tpos_info p')
            ->join('LEFT JOIN tstore_info t ON p.`store_id` = t.`store_id`  LEFT JOIN  tnode_info n ON p.`node_id` = n.`node_id` ')
            ->where($where)
            ->find();
        if (! $pos_info) {
            $this->_log("get_send_info find tpos_info error " . M()->_sql());
            return $this->return_json("1001", null);
        }
        
        // 校验原流水
        $where = "b.pos_id = '" . $pos_id . "' and b.status = '0' and b.trans_type = '0001' and req_seq = '" . $req_seq . "'";
        $barcode_trace = M()->table('tbarcode_trace b')
            ->join('left join tbatch_info i on b.b_id = i.id')
            ->where($where)
            ->field('b.req_seq as req_seq,  i.batch_short_name as batch_short_name, b.phone_no as phone_no, b.trans_time as trans_time, b.status as status, b.user_id as user_id, b.node_id as node_id, b.request_id as request_id')
            ->find();
        if (! $barcode_trace) {
            $this->_log("get_send_info find tbarcode_trace error " . M()->_sql());
            return $this->return_json("1004", null);
        }
        // 只能撤销当天的发码
        $transactionTime = date('Ymd', strtotime($barcode_trace['trans_time']));
        $nowTime = date('Ymd');
        if ($nowTime - $transactionTime >= 1) {
            return $this->return_json("1005", null);
        }
        
        // 调用撤销接口
        $data['err_info'] = '';
        $req_data = "a=CodeCancelByReqId&node_id=" . $barcode_trace['node_id'] . "&request_id=" . $barcode_trace['request_id'] . "&user_id=" . $epos_acount;
        $resp_array = $this->requestWcAppServ($req_data);
        if (! $resp_array || ($resp_array['resp_id'] != '0000' && $resp_array['resp_id'] != '0001')) {
            $data['err_info'] = $resp_array['resp_desc'];
            return $this->return_json('1010', $data);
        }
        return $this->return_json("0000", $data);
    }

    public function get_send_stat($pos_id)
    {
        $where = "pos_id = '" . $pos_id . "' ";
        $pos_info = M()->table('tpos_info')
            ->where($where)
            ->find();
        if (! $pos_info) {
            $this->_log("get_send_info find tpos_info error " . M()->_sql());
            return $this->return_json("1001", null);
        }
        $data = [
            'month_send_num' => 0,
            'day_send_num' => 0,
            'month_cancel_num'=>0,
            'day_cancel_num'=>0
        ];
        // 取该终端发送统计数 当月  
        $naturalMonth = date('Ym01'); // 自然月
        $where = "pos_id='{$pos_id}' and status='0' and trans_time >= '{$naturalMonth}'";
        $rs = M()->table('tbarcode_trace')
            ->where($where)
            ->field('count(id) as send_num_sum')
            ->find();
        $data['month_send_num'] = get_val($rs, 'send_num_sum', 0);
        
        //获取该终端撤销统计数 当月
        $where = "pos_id='{$pos_id}' and status='1' and trans_time >= '{$naturalMonth}'";
        $rs = M()->table('tbarcode_trace')
        ->where($where)
        ->field('count(id) as send_num_sum')
        ->find();

        log_write('获取该终端撤销统计数 当月 $rs:'. var_export($rs,1),'INFO', 'CANCEL');
        log_write('获取该终端撤销统计数 当月 sql:'. M()->_sql(),'INFO', 'CANCEL');

        $data['month_cancel_num'] = get_val($rs, 'send_num_sum', 0);
        
        // 取该终端撤销统计数 当天
        $currentDate = date('Ymd'); // 当天
        $where ="pos_id='{$pos_id}' and status='1' and trans_time >='{$currentDate}'";
        $rs = M()->table('tbarcode_trace')
        ->where($where)
        ->field('count(id) as send_num_sum')
        ->find();
        log_write('获取该终端撤销统计数 当日 $rs:'. var_export($rs,1),'INFO', 'CANCEL');
        log_write('获取该终端撤销统计数 当日 sql:'. M()->_sql(),'INFO', 'CANCEL');
        $data['day_cancel_num'] = get_val($rs, 'send_num_sum', 0);
        
        // 取该终端发送统计数 当日
        $where = "pos_id='{$pos_id}' and status='0' and trans_time >= '{$currentDate}'";
        $rs = M()->table('tbarcode_trace')
            ->where($where)
            ->field('count(id) as send_num_sum')
            ->find();
        $data['day_send_num'] = get_val($rs, 'send_num_sum', 0);
        return $this->return_json("0000", $data);
        
        
    }

    public function wcapp_Install_num($app_version)
    {
        if ($app_version) {
            $data['app_version'] = $app_version;
            $data['download_time'] = date('Ymd');
            $result = M()->table('tepos_app_install')->add($data);
        }
        if ($result) {
            return 1;
        } else {
            return 0;
        }
    }

    /*
     * public function pos_day_settle($pos_id, $settle_day) { $where = "pos_id =
     * '".$pos_id."' "; $pos_info =
     * M()->table('tpos_info')->where($where)->find(); if (!$pos_info){
     * $this->_log("get_send_info find tpos_info error ". M()->_sql()); return
     * $this->return_json("1001", null); } $data_arr = array();
     * $data_arr['count'] = 0; //取当前日结数据，有的话，直接返回 $where = "pos_id =
     * '".$pos_id."' and settle_day = '".$settle_day."'"; $day_settle_list =
     * M()->table('tepos_send_day_settle s')->join('left join tbatch_info b on
     * b.b_id = s.b_id')->where($where)->field('b.id, b.batch_short_name,
     * s.send_num')->select(); if ($day_settle_list) { //已日结过
     * foreach($day_settle_list as $day_settle){ $data['batch_short_name'] =
     * $day_settle['batch_short_name']; $data['send_num'] =
     * $day_settle['send_num']; $data_arr['count'] ++; } return
     * $this->return_json("0000", $data_arr); } //进行日结 //批量更新流水表日结标志 $sql =
     * "update tbarcode_trace set settle_day = '".$settle_day."' where pos_id =
     * '".$pos_id."' and settle_day is null and data_from = 'P' and status = '0'
     * and trans_type = '0001'"; //统计数据插入 //取该终端发送统计数 累计 $where = "pos_id =
     * '".$pos_id."'"; $rs =
     * M()->table('tpos_day_count')->where($where)->field('ifnull(SUM(send_num),
     * 0) as send_num_sum')->find(); if (!$rs) { $this->_log("get_batch_info
     * stat error ". M()->_sql()); return false; } $data['total_send_num'] =
     * $rs['send_num_sum']; //取该终端发送统计数 当日 $where = "pos_id = '".$pos_id."' and
     * trans_date = '". date('Y-m-d')."'"; $rs =
     * M()->table('tpos_day_count')->where($where)->field('ifnull(SUM(send_num),
     * 0) as send_num_sum')->find(); if (!$rs) { $this->_log("get_batch_info
     * stat error ". M()->_sql()); return false; } $data['day_send_num'] =
     * $rs['send_num_sum']; return $this->return_json("0000", $data); }
     */
    /*
     * 获取发布到旺财APP上的活动列表
     */
    public function getWcAppBatchChannelList($pos_id, $page)
    {
        $where = "pos_id = '" . $pos_id . "' ";
        $pos_info = M()->table('tpos_info')
            ->where($where)
            ->find();
        if (! $pos_info) {
            $this->_log("get_batch_info_list find tpos_info error " . M()->_sql());
            return $this->return_json("1001", null);
        }
        
        $where = "b.node_id = '00017114' AND  b.batch_type IN ('2','3') AND b.status = '1' AND m.status = '1' AND c.status = '1'
 and c.sns_type = '62'";
        import('ORG.Util.Page'); // 导入分页类
        $count = M()->table('tbatch_channel b ')
            ->join('LEFT JOIN tmarketing_info m ON b.batch_id = m.id LEFT JOIN tchannel c ON b.channel_id = c.id')
            ->where($where)
            ->count();
        
        $Page = new Page($count, 20);
        $show = $Page->show();
        $batch_channel_list = M()->table('tbatch_channel b ')
            ->join('LEFT JOIN tmarketing_info m ON b.batch_id = m.id LEFT JOIN tchannel c ON b.channel_id = c.id ')
            ->where($where)
            ->field(" CASE b.batch_type WHEN '2' THEN '2' WHEN '53' THEN '2' WHEN '3' THEN '3' END  AS batch_type, CASE b.batch_type WHEN '2' THEN '抽奖' WHEN '53' THEN '抽奖' WHEN '3' THEN '有奖答题' END  AS batch_type_name")
            ->order('b.id desc')
            ->page($page . ',20')
            ->select();
        
        if (! $batch_channel_list) {
            $this->_log("getWcAppBatchChannelList find tbatch_channel error " . M()->_sql());
            return $this->return_json("1004", null);
        }
        $data_arr = array();
        $data_arr['count'] = 0;
        $data_arr['page'] = $show;
        foreach ($batch_channel_list as $batch_channel) {
            $data['batch_name'] = $batch_channel['name'];
            $data['start_date'] = date('Y-m-d', strtotime($batch_channel['start_time']));
            $data['end_date'] = date('Y-m-d', strtotime($batch_channel['end_time']));
            $data['batch_type'] = $batch_channel['batch_type'];
            $data['batch_type_name'] = $batch_channel['batch_type_name'];
            $data['batch_url'] = $url = C('PAYGIVE_DOMAIN') . U('Label/Label/index', array(
                'id' => $batch_channel['id']
            ));
            $data_arr['send_list'][] = $data;
            $data_arr['count'] ++;
        }
        return $this->return_json("0000", $data_arr);
    }

    /* 调用AppServ发码接口 */
    public function requestWcAppServ($data)
    {
        $url = C('WC_APP_SERV_URL') or die('[WC_APP_SERV_URL]参数未设置');
        $error = '';
        $result = httpPost($url . $data, '', $error, array(
            'METHOD' => 'GET'
        ));
        return json_decode($result, true);
    }

    public function _log($msg, $level = Log::INFO)
    {
        log_write($msg, $level);
    }
}
