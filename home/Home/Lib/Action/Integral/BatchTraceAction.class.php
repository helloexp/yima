<?php

class BatchTraceAction extends IntegralAuthAction {

    public $_authAccessMap = '*';

    public function _initialize() {
        parent::_initialize();
    }

    public function index() {
        $data = $_REQUEST;
        $map = array(
            'c.node_id' => array(
                'exp', 
                "in ({$this->nodeIn()})"), 
            'c.data_from' => 'I', 
            // 'c.trans_type' => '0001',
            'c.status' => '0');
        $nodeId = I('node_id', null, 'mysql_escape_string');
        if (! empty($nodeId)) {
            $map['c.node_id '] = $nodeId;
        }
        if ($data['mobile'] != '') {
            $map['c.phone_no'] = $data['mobile'];
        }
        import('ORG.Util.Page'); // 导入分页类
        $mapCount = M()->table('tbarcode_trace c')
            ->where($map)
            ->count(); // 查询满足要求的总记录数
        $Page = new Page($mapCount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        
        foreach ($data as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        
        $show = $Page->show(); // 分页显示输出
        
        $list = M()->table('tbarcode_trace c')
            ->field('c.*,g.goods_name,n.node_name,s.order_id')
            ->join('tintegral_order_trace s ON s.code_trace=c.request_id')
            ->join('tgoods_info g ON g.goods_id=c.goods_id')
            ->join('tnode_info n ON n.node_id=c.node_id')
            ->where($map)
            ->order('c.id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        $status = array(
            '0' => '成功', 
            '1' => '已撤销', 
            '3' => '失败');
        $transType = array(
            '0001' => '发码', 
            '0002' => '撤销', 
            '0003' => '重发');
        $this->assign('status', $status);
        $this->assign('transType', $transType);
        $this->assign('list', $list);
        $this->assign('nodeList', $this->getNodeTree());
        $this->assign('page', $show); // 赋值分页输出
        $this->display('BatchTrace/index'); // 输出模板
    }

    public function batchList() {
        $model = M('tbatch_import');
        $data = $_REQUEST;
        $map = array(
            'node_id' => $this->nodeId, 
            'data_from' => array(
                'in', 
                '2,6,7'));
        if ($data['batch_no'] != '') {
            $map['batch_no'] = $data['batch_no'];
        }
        if ($data['batch_id'] != '') {
            $map['batch_id'] = $data['batch_id'];
        }
        if ($data['add_time'] != '') {
            $map['add_time'] = $data['add_time'];
        }
        if ($data['data_from'] != '') {
            $map['data_from'] = $data['data_from'];
        }
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = $model->where($map)->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        
        foreach ($data as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        
        $show = $Page->show(); // 分页显示输出
        
        $list = $model->where($map)
            ->order('batch_id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        $status = array(
            '0' => '未处理', 
            '1' => '处理中', 
            '2' => '已处理', 
            '3' => '全处理', 
            '9' => '处理失败');
        
        $this->assign('status', $status);
        $this->assign('list', $list);
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }

    public function downList() {
        $batch_id = I('batch_id');
        $model = M('tbatch_importdetail');
        $map = array(
            'node_id' => $this->nodeId, 
            'batch_id' => $batch_id);
        $map['status'] = array(
            'neq', 
            1);
        
        $query = $model->where($map)->select();
        if (! $query) {
            $this->error('无下载数据！');
        }
        
        $phone_arr = array();
        
        foreach ($query as $v) {
            $phone_arr[] = array(
                $v['phone_no'], 
                iconv('utf-8', 'gb2312', $v['ret_desc']));
            // $phone_arr[][] = array();
        }
        
        header("Content-Type: application/vnd.ms-excel; charset=GB2312");
        header("Content-Disposition: attachment;filename=errorPhone.csv ");
        
        $rs = $phone_arr;
        $str = '';
        foreach ($rs as $row) {
            $str_arr = array();
            foreach ($row as $column) {
                $str_arr[] = str_replace('"', '', $column);
            }
            $str .= implode(',', $str_arr) . PHP_EOL;
        }
        echo $str;
        exit();
    }

    /**
     * 重新发送
     */
    public function reSend() {
        $id = trim(I('id'));
        $model = M("tbarcode_trace");
        // 判断是否重发5次，
        $count = $model->alias("a")->join(
            "tbarcode_trace_send b on b.org_req_seq=a.req_seq")
            ->where(
            array(
                'a.id' => $id, 
                'a.node_id' => $this->nodeId))
            ->count();
        if ($count >= 6) {
            $this->error('您已经重发5条了，无法在继续重发！');
        }
        $query_arr = $model->alias("a")->where(
            array(
                'a.id' => $id, 
                'a.node_id' => $this->nodeId))->find();
        if ($query_arr['data_from'] == '9') {
            $this->error('爱拍条码无法重发！');
        }
        if ($query_arr['trans_type'] !== '0001' && $query_arr['status'] != '0') {
            $this->error('该码信息有误，无法重发');
        }
        if ($query_arr) {
            import("@.Vendor.SendCode");
            $req = new SendCode();
            $resp = $req->resend_send($query_arr['node_id'], $this->userId, 
                $query_arr['request_id']);
            log_write("YYYYY" . print_r($resp, true));
            if ($resp === true) {
                $dataFromArr = array(
                    '卡券', 
                    '游戏', 
                    '卡券', 
                    '卡券', 
                    '卡券', 
                    '粉丝卡', 
                    7 => '粉丝礼品');
                node_log(
                    "{$dataFromArr[$query_arr['data_from']]}重发.名称：" . M(
                        'tgoods_info')->where(
                        "batch_no={$query_arr['batch_no']}")->getField(
                        'goods_name') . "手机号：{$query_arr['phone_no']}");
                $this->success('重发成功！');
            } else {
                $this->error('重发失败' . $resp);
            }
        }
        $this->error('参数错误!');
    }

    /**
     * 撤销发码
     */
    public function revocationCode() {
        $id = I('post.id', null, 'intval');
        if (empty($id)) {
            $this->error('参数错误');
        }
        $codeInfo = M('tbarcode_trace')->where(
            "node_id='{$this->nodeId}' AND id='{$id}'")->find();
        if (! $codeInfo) {
            $this->error('未找到相关数据');
        }
        if ($codeInfo['data_from'] == '9') {
            $this->error('爱拍条码无法撤销！');
        }
        if ($codeInfo['trans_type'] != '0001' && $codeInfo['status'] != '0') {
            $this->error('该码信息有误，无法撤销');
        }
        import("@.Vendor.SendCode");
        $req = new SendCode();
        $resp = $req->cancelcode($this->nodeId, $this->userId, 
            $codeInfo['request_id']);
        if ($resp === true) {
            $dataFromArr = array(
                '卡券', 
                '游戏', 
                '卡券', 
                '卡券', 
                '卡券', 
                '粉丝卡', 
                7 => '粉丝礼品');
            node_log(
                "{$dataFromArr[$codeInfo['data_from']]}撤消.名称：" . M(
                    'tgoods_info')->where("batch_no={$codeInfo['batch_no']}")->getField(
                    'goods_name') . "手机号：{$codeInfo['phone_no']}");
            $this->success('撤销成功！');
        } else {
            $this->error($resp);
        }
    }

    /**
     * 重新发码（只针对因商户余额不足等原因导致的首次发码失败的记录）
     */
    public function reGenerateCode() {
        $id = trim(I('id'));
        $order_id = trim(I('order_id'));
        if (! $id || ! $order_id) {
            $this->error('数据错误');
        }
        $model = M('tbarcode_trace');
        $query_arr = $model->where(
            array(
                'id' => $id, 
                'node_id' => $this->nodeId))->find();
        if ($query_arr['data_from'] != 'I') {
            $this->error('非多宝电商条码无法重新生成！');
        }
        if ($query_arr['trans_type'] !== '0001' && $query_arr['status'] != '3') {
            $this->error('该码信息有误，无法重新生成');
        }
        $codeTraceCount = M('torder_trace')->where(
            array(
                'order_id' => $order_id, 
                'code_trace' => $query_arr['request_id']))->count();
        if ($codeTraceCount != 1) {
            $this->error('未找到匹配订单数据，无法重发，或已重发成功，刷新页面进行查看');
        }
        
        $transId = get_request_id();
        import("@.Vendor.SendCode");
        $req = new SendCode();
        $res = $req->wc_send($this->nodeId, '', $query_arr['batch_no'], 
            $query_arr['phone_no'], '8', $transId, '', $query_arr['b_id']);
        if ($res == true) {
            // 更新code_trace到订单信息表
            $result = M('ttg_order_info_ex')->where(
                array(
                    'order_id' => $order_id, 
                    'code_trace' => $query_arr['request_id']))->save(
                array(
                    'code_trace' => $transId));
            if ($result === false) {
                log_write(
                    "订单发码成功,更新订单关联表失败;order_id:{$orderInfo['order_id']},send_seq:{$transId}");
            }
            $result = M('torder_trace')->where(
                array(
                    'order_id' => $order_id, 
                    'code_trace' => $query_arr['request_id']))->save(
                array(
                    'code_trace' => $transId));
            if ($result === false) {
                log_write(
                    "订单发码成功,更新code_trace失败;order_id:{$order_id},send_seq:{$transId}");
                $this->success('重新生成成功，更新请求号失败！');
            }
            $this->success('重新生成成功！');
        } else {
            log_write("订单发码失败,原因:{$res}");
            $this->error('重新生成失败');
        }
    }

    /**
     * 统计查询
     */
    public function downTrace() {
        $store_name = I('store_name');
        $model = M();
        $where = "node_id = '" . $this->nodeId . "' and ";
        $where_ = " and 1=1 ";
        $is_where = "  1=1 ";
        if (! empty($store_name)) {
            
            if ($store_name == 'WEB门店' || $store_name == '00000000') {
                $where_ = " and 1=2 ";
            } else {
                $where_ = " and (a.store_name like '%" . $store_name .
                     "%'  or a.store_id='%" . $store_name . "%')";
                $is_where = " 1=2 ";
            }
        }
        
        $sql = "SELECT a.store_id ,a.store_name,SUM(s_count) as s_count , SUM(p_count) as p_count ,c_count
				FROM tstore_info a  INNER JOIN
				(
					SELECT c.pos_id,d.s_count,e.p_count,g.c_count,c.store_id FROM tpos_info c
					LEFT  JOIN (SELECT COUNT(*) AS s_count,pos_id FROM tbarcode_trace where " .
             $where .
             " trans_type='0001' and data_from='I' and status='0' GROUP BY pos_id ) d
					ON d.pos_id=c.pos_id
					LEFT  JOIN (SELECT COUNT(*) AS p_count,pt.pos_id FROM tpos_trace pt LEFT JOIN tbarcode_trace bt ON bt.req_seq=pt.req_seq WHERE pt.node_id='" .
             $this->nodeId .
             "' and pt.trans_type='0' AND pt.STATUS='0' and bt.data_from='I' GROUP BY pt.pos_id)  e
					ON e.pos_id=c.pos_id
					LEFT JOIN (SELECT COUNT(*) AS c_count,a.pos_id FROM tpos_trace a LEFT JOIN tbarcode_trace abt ON abt.req_seq=a.req_seq WHERE a.node_id='" .
             $this->nodeId .
             "' and  a.trans_type='1' AND a.STATUS='0' and abt.data_from='I' GROUP BY a.pos_id)  g
					ON g.pos_id=c.pos_id
					WHERE  p_count !='' OR s_count!='' OR c_count !=0
				) f
				ON a.store_id= f.store_id   where a.node_id='{$this->nodeId}' " .
             $where_ .
             "  GROUP BY f.store_id
				UNION SELECT '00000000' AS store_id , '00000000' AS store_name ,COUNT(*) AS count1 ,'0' AS count2, '0' AS count3  FROM tbarcode_trace WHERE " .
             $where . " trans_type='0001' and status='0' and data_from='I' and " .
             $is_where . " and pos_id='00000000' having count(*)>0 ";
        
        // $query = $model->query($sql);
        $sql2 = "select count(*) as all_count from (" . $sql . ") tb";
        
        $count = $model->query($sql2);
        
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = $count[0]['all_count']; // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $Page->parameter .= "&store_name=" . urlencode($store_name) . '&';
        $show = $Page->show(); // 分页显示输出
        $list = $model->query(
            "select * from (" . $sql . ") tb limit " . $Page->firstRow . "," .
                 $Page->listRows);
        $this->assign('list', $list);
        $this->assign('page', $show); // 赋值分页输出
        $this->display('BatchTrace/downTrace');
    }
    
    // 按门店下载流水
    public function storeTraceDown() {
        $type = I('type');
        $store_id = I('store_id');
        if (empty($type) || empty($store_id)) {
            $this->error('非法参数！');
        }
        
        if ($store_id != '00000000') {
            $model = M('tpos_info');
            $map = array(
                'node_id' => $this->nodeId, 
                'store_id' => $store_id);
            $query_arr = $model->where($map)
                ->field('pos_id')
                ->select();
            if (! $query_arr) {
                $this->error('未查询到门店信息！');
            }
            
            $pos_arr = array();
            foreach ($query_arr as $v) {
                $pos_arr[] = $v['pos_id'];
            }
            $pos_str = implode(',', $pos_arr);
        } else {
            $pos_str = $store_id;
        }
        
        if ($type == 'send') {
            $sql = "select a.trans_time,a.batch_no,b.goods_name,a.phone_no from tbarcode_trace a  left join  tgoods_info b
					on a.batch_no=b.batch_no where a.node_id=b.node_id
					and a.node_id=" .
                 $this->nodeId . " and a.trans_type='0001' and a.data_from='8'
					and a.status='0' and a.pos_id in(" .
                 $pos_str . ") order by a.id desc";
            $query_arr = M()->query($sql);
            $title_arr = array(
                '日期', 
                '卡券ID', 
                '卡券名称', 
                '手机号');
            $fileName = '发送流水';
            $this->down_fun($title_arr, $query_arr, $fileName);
        } elseif ($type == 'verify') {
            $sql = "select a.trans_time,a.batch_no,b.goods_name,a.phone_no from tpos_trace a  left join  tgoods_info b
					on a.batch_no=b.batch_no where a.node_id=b.node_id
					and a.node_id=" .
                 $this->nodeId . " and a.trans_type='0' and b.goods_type='6'
					and a.status='0' and a.pos_id in(" .
                 $pos_str . ") order by a.id desc";
            $query_arr = M()->query($sql);
            $title_arr = array(
                '日期', 
                '卡券ID', 
                '卡券名称', 
                '手机号');
            $fileName = '验证流水';
            $this->down_fun($title_arr, $query_arr, $fileName);
        } elseif ($type == 'cancle') {
            $sql = "select a.trans_time,a.batch_no,b.goods_name,a.phone_no from tpos_trace a  left join  tgoods_info b
					on a.batch_no=b.batch_no where a.node_id=b.node_id
					and a.node_id=" .
                 $this->nodeId . " and a.trans_type='1' and b.goods_type='6'
					and a.status='0' and a.pos_id in(" .
                 $pos_str . ") order by a.id desc";
            
            $query_arr = M()->query($sql);
            $title_arr = array(
                '日期', 
                '卡券ID', 
                '卡券名称', 
                '手机号');
            $fileName = '撤销流水';
            $this->down_fun($title_arr, $query_arr, $fileName);
        }
    }
    
    // 下载全部流水
    public function downPost() {
        $type = I('type');
        if ($type == 'send') {
            $sql = "select a.trans_time,a.batch_no,b.goods_name,a.phone_no from tbarcode_trace a  left join  tgoods_info b
					on a.batch_no=b.batch_no where a.node_id=b.node_id
					and a.node_id=" .
                 $this->nodeId . " and a.trans_type='0001'
					and a.status='0' order by a.id desc";
            $query_arr = M()->query($sql);
            $title_arr = array(
                '日期', 
                '卡券ID', 
                '卡券名称', 
                '手机号');
            $fileName = '发送流水';
            $this->down_fun($title_arr, $query_arr, $fileName);
        } elseif ($type == 'verify') {
            $sql = "select a.trans_time,a.batch_no,b.goods_name,a.phone_no from tpos_trace a  left join  tgoods_info b
					on a.batch_no=b.batch_no where a.node_id=b.node_id
					and a.node_id=" . $this->nodeId . " and a.trans_type='0'
					and a.status='0' order by a.id desc";
            $query_arr = M()->query($sql);
            $title_arr = array(
                '日期', 
                '卡券ID', 
                '卡券名称', 
                '手机号');
            $fileName = '验证流水';
            $this->down_fun($title_arr, $query_arr, $fileName);
        } elseif ($type == 'cancle') {
            $sql = "select a.trans_time,a.batch_no,b.goods_name,a.phone_no from tpos_trace a  left join  tgoods_info b
					on a.batch_no=b.batch_no where a.node_id=b.node_id
					and a.node_id=" . $this->nodeId . " and a.trans_type='1'
					and a.status='0' order by a.id desc";
            
            $query_arr = M()->query($sql);
            $title_arr = array(
                '日期', 
                '卡券ID', 
                '卡券名称', 
                '手机号');
            $fileName = '撤销流水';
            $this->down_fun($title_arr, $query_arr, $fileName);
        }
    }

    protected function down_fun($title_arr = array(), $cont_arr = array(), $fileName = '') {
        foreach ($title_arr as $v) {
            $title .= '<td>' . $v . '</td>';
        }
        $title = '<tr>' . $title . '</tr>';
        
        $fileName = $fileName . '.xls';
        $fileName = iconv('utf-8', 'gbk', $fileName);
        // 下载
        header('Content-type: application/vnd.ms-excel');
        header(
            "Content-Disposition: attachment; filename=\"" . $fileName . "\"");
        header("Pragma", "public");
        header('Cache-Control: max-age=0');
        echo '<html>' . '<head>' .
             '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />' .
             '</head>' . '<body>' .
             '<table width="100%" border="1" align="center" style="border:solid black 1px; " cellpadding=0 cellspacing=0>' .
             $title;
        
        foreach ($cont_arr as $arr) {
            $td = '';
            $i = 0;
            foreach ($arr as $vv) {
                $i ++;
                if ($i == 1) {
                    $vv = date('Y-m-d/H:i:s', strtotime($vv));
                }
                $td .= '<td>' . $vv . '</td>';
            }
            echo $tr = '<tr>' . $td . '</tr>';
        }
        echo '</table></body></html>';
        
        exit();
    }
    
    // 验证流水
    public function posTrace() {
        $mobile = I('mobile');
        $batch_no = I('batch_no');
        $data = array();
        $map = array(
            'p.node_id' => array(
                'exp', 
                "in ({$this->nodeIn()})"), 
            'p.status' => '0', 
            'b.data_from' => 'I');
        $nodeId = I('node_id', null, 'mysql_escape_string');
        if (! empty($nodeId)) {
            $map['p.node_id '] = $nodeId;
        }
        if ($mobile != '') {
            $map['p.phone_no'] = $mobile;
        }
        // $map['g.goods_type']='6';
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = M()->table('tpos_trace p')
            ->join('tnode_info n ON p.node_id=n.node_id')
            ->join('tbarcode_trace b ON b.req_seq=p.req_seq')
            ->join('tintegral_order_trace o ON o.code_trace=b.request_id')
            ->join('tgoods_info g ON g.batch_no=p.batch_no')
            ->where($map)
            ->count();
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        
        foreach ($data as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        
        $show = $Page->show(); // 分页显示输出
        
        $list = M()->table('tpos_trace p')
            ->field('p.*,n.node_name,g.goods_name,o.order_id')
            ->join('tnode_info n ON p.node_id=n.node_id')
            ->join('tbarcode_trace b ON b.req_seq=p.req_seq')
            ->join('tintegral_order_trace o ON o.code_trace=b.request_id')
            ->join('tgoods_info g ON g.batch_no=p.batch_no')
            ->where($map)
            ->order('id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        $status = array(
            '0' => '成功', 
            '1' => '失败', 
            '2' => '冲正');
        $transType = array(
            '0' => '验证', 
            '1' => '撤销', 
            '2' => '冲正', 
            '3' => '电话验证', 
            '9001' => '异常验证');
        // $this->assign('batch_arr',$batch_arr);
        $this->assign('status', $status);
        $this->assign('transType', $transType);
        $this->assign('list', $list);
        $this->assign('mobile', $mobile);
        $this->assign('nodeId', $nodeId);
        $this->assign('nodeList', $this->getNodeTree());
        $this->assign('page', $show); // 赋值分页输出
        $this->display('BatchTrace/posTrace'); // 输出模板
    }
    
    // 爱拍发码流水
    public function aiIndex() {
        $name = I('name', null, 'mysql_real_escape_string');
        if (isset($name) && $name != '') {
            $where['i.batch_short_name'] = array(
                'like', 
                "%{$name}%");
        }
        $phone = I('mobile', null, 'mysql_real_escape_string');
        if (! empty($phone)) {
            $where['r.recv_phone'] = $phone;
        }
        $where['r.shop_id'] = $this->nodeId;
        $where['i.batch_class'] = '5';
        import("ORG.Util.Page");
        $count = M()->table('tpp_send_record_info r')
            ->join('tbatch_info i ON r.prize_id=i.id')
            ->where($where)
            ->count();
        $p = new Page($count, 10);
        // 确保分页查询条件
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        $sendData = M()->table('tpp_send_record_info r')
            ->field('i.batch_short_name,r.recv_phone,r.send_time,r.send_status')
            ->join('tbatch_info i ON r.prize_id=i.id')
            ->where($where)
            ->order('r.send_time DESC')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        // echo M()->getLastSql();
        $page = $p->show();
        $status = array(
            '0' => '未发送', 
            '1' => '发送中', 
            '2' => '发送失败', 
            '3' => '发送成功', 
            '4' => '发送超时');
        $this->assign('status', $status);
        $this->assign('sendData', $sendData);
        $this->assign('post', $_REQUEST);
        $this->assign("page", $page);
        $this->assign('empty', '<tr><td colspan="5">无数据</td></span>');
        $this->display();
    }
    
    // 爱拍验证流水
    public function aiVerify() {
        $name = I('name', null, 'mysql_real_escape_string');
        if (isset($name) && $name != '') {
            $where['i.batch_short_name'] = array(
                'like', 
                "%{$name}%");
        }
        $phone = I('mobile', null, 'mysql_real_escape_string');
        if (! empty($phone)) {
            $where['r.recv_phone'] = $phone;
        }
        $where['r.shop_id'] = $this->nodeId;
        $where['r.use_status'] = '2';
        $where['i.batch_class'] = '5';
        import("ORG.Util.Page");
        $count = M()->table('tpp_send_record_info r')
            ->join('tbatch_info i ON r.prize_id=i.id')
            ->where($where)
            ->count();
        $p = new Page($count, 10);
        // 确保分页查询条件
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        $verifyData = M()->table('tpp_send_record_info r')
            ->field('i.batch_short_name,r.recv_phone,r.send_time,r.send_status')
            ->join('tbatch_info i ON r.prize_id=i.id')
            ->where($where)
            ->order('r.use_time DESC')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        $page = $p->show();
        // $status =
        // array('0'=>'未发送','1'=>'发送中','2'=>'发送失败','3'=>'发送成功','4'=>'发送超时');
        // $this->assign('status',$status);
        $this->assign('verifyData', $verifyData);
        $this->assign('post', $_REQUEST);
        $this->assign("page", $page);
        $this->assign('empty', '<tr><td colspan="5">无数据</td></span>');
        $this->display();
    }
    
    // 下载流水数据
    public function aiPaiDownList() {
        $where = '1=1';
        $name = I('name', null, 'mysql_real_escape_string');
        if (isset($name) && $name != '') {
            $where .= "like '%{$name}%'";
        }
        $where .= ' AND r.shop_id=' . $this->nodeId;
        
        $sql = "SELECT r.channel_id,c.name FROM tpp_send_record_info r LEFT JOIN tchannel c ON r.channel_id=c.id  WHERE {$where} GROUP BY r.channel_id";
        $countSql = "SELECT count(*) as count FROM ({$sql}) a";
        $count = M()->query($countSql);
        import("ORG.Util.Page");
        $p = new Page($count[0]['count'], 10);
        // 确保分页查询条件
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        $sql .= " limit {$p->firstRow},{$p->listRows}";
        $dataList = M()->query($sql);
        
        // 获取每个渠道的发码量,验码量
        foreach ($dataList as $k => $v) {
            $dataList[$k]['send_num'] = M('tpp_send_record_info')->where(
                "channel_id={$v['channel_id']} AND shop_id='{$this->nodeId}' AND send_status=3")->count();
            $dataList[$k]['verify_num'] = M('tpp_send_record_info')->where(
                "channel_id={$v['channel_id']} AND shop_id='{$this->nodeId}' AND use_status=2")->count();
        }
        $page = $p->show();
        $this->assign('dataList', $dataList);
        $this->assign('post', $_REQUEST);
        $this->assign("page", $page);
        $this->assign('empty', '<tr><td colspan="5">无数据</td></span>');
        $this->display();
    }

    public function aiPaiTraceDown() {
        $type = I('type');
        switch ($type) {
            case 'send':
                $where = array(
                    'shop_id' => $this->nodeId, 
                    'send_status' => '3');
                $query_arr = M()->table('tpp_send_record_info r')
                    ->field('r.send_time,i.batch_short_name,r.recv_phone')
                    ->join('tbatch_info i ON r.prize_id=i.id')
                    ->where($where)
                    ->order('r.send_time DESC')
                    ->select();
                if (! $query_arr) {
                    $this->error('没有数据可供下载');
                }
                $title_arr = array(
                    '日期', 
                    '卡券名称', 
                    '手机号');
                $fileName = '爱拍卡券发送流水';
                $this->down_fun($title_arr, $query_arr, $fileName);
                break;
            case 'verify':
                $where = array(
                    'shop_id' => $this->nodeId, 
                    'use_status' => '2');
                $query_arr = M()->table('tpp_send_record_info r')
                    ->field('r.use_time,i.batch_short_name,r.recv_phone')
                    ->join('tbatch_info i ON r.prize_id=i.id')
                    ->where($where)
                    ->order('r.use_time DESC')
                    ->select();
                if (! $query_arr) {
                    $this->error('没有数据可供下载');
                }
                $title_arr = array(
                    '日期', 
                    '卡券名称', 
                    '手机号');
                $fileName = '爱拍卡券验证流水';
                $this->down_fun($title_arr, $query_arr, $fileName);
                break;
            default:
                $this->error('未知的下载类型');
        }
    }
    
    // 单个渠道下载流水
    public function aiPaioneDown() {
        $type = I('type');
        $channelId = I('channel_id', null, 'mysql_real_escape_string');
        if (empty($channelId)) {
            $this->error('参数错误');
        }
        switch ($type) {
            case 'send':
                $where = array(
                    'shop_id' => $this->nodeId, 
                    'send_status' => '3', 
                    'channel_id' => $channelId);
                $query_arr = M()->table('tpp_send_record_info r')
                    ->field('r.send_time,c.name,r.recv_phone')
                    ->join('tchannel c ON r.channel_id=c.id')
                    ->where($where)
                    ->order('r.send_time DESC')
                    ->select();
                if (! $query_arr) {
                    $this->error('没有数据可供下载');
                }
                $title_arr = array(
                    '日期', 
                    '门店名称', 
                    '手机号');
                $fileName = '爱拍卡券发送流水';
                $this->down_fun($title_arr, $query_arr, $fileName);
                break;
            case 'verify':
                $where = array(
                    'shop_id' => $this->nodeId, 
                    'use_status' => '2', 
                    'channel_id' => $channelId);
                $query_arr = M()->table('tpp_send_record_info r')
                    ->field('r.use_time,c.name,r.recv_phone')
                    ->join('tchannel c ON r.channel_id=c.id')
                    ->where($where)
                    ->order('r.use_time DESC')
                    ->select();
                if (! $query_arr) {
                    $this->error('没有数据可供下载');
                }
                $title_arr = array(
                    '日期', 
                    '门店名称', 
                    '手机号');
                $fileName = '爱拍卡券验证流水';
                $this->down_fun($title_arr, $query_arr, $fileName);
                break;
            default:
                $this->error('未知的下载类型');
        }
    }
}