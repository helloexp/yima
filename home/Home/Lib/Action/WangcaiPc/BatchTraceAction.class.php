<?php

class BatchTraceAction extends BaseAction {

    public function index() {
        $map = array(
            'c.node_id' => array(
                'exp', 
                "in ({$this->nodeIn()})"), 
            'g.goods_type' => array(
                'in', 
                '0,1,2,3,11'), 
            'g.source' => array(
                'in', 
                '0,1,4'));
        
        $seachStatus = 0; // 更多筛选状态
        $name = I('name', null, 'mysql_real_escape_string');
        if (! empty($name)) {
            $map['g.goods_name'] = array(
                'like', 
                "%{$name}%");
        }
        $batchType = I('batch_type', null, 'mysql_real_escape_string');
        if (! empty($batchType)) {
            $map['m.batch_type'] = $batchType;
        }
        $mobile = I('mobile', null, 'mysql_real_escape_string');
        if (! empty($mobile)) {
            $map['c.phone_no'] = $mobile;
        }
        $btransTime = I('btrans_time', null, 'mysql_real_escape_string');
        if (empty($btransTime) && ! $this->isPost() && empty($_REQUEST['p'])) {
            $bdate = date("Ymd", strtotime("-1 week")); // 最多取一个星期前的数据
            $map['c.trans_time'] = array(
                'egt', 
                $bdate . '000000');
            $_REQUEST['btrans_time'] = $bdate;
        } else if (! empty($btransTime)) {
            $map['c.trans_time'] = array(
                'egt', 
                $btransTime . '000000');
        }
        $etransTime = I('etrans_time', null, 'mysql_real_escape_string');
        if (! empty($etransTime)) {
            $map['c.trans_time '] = array(
                'elt', 
                $etransTime . '235959');
        }
        $checkDay = DateDiff($etransTime, $btransTime, 'd');
        if ($checkDay > 30)
            $this->error('查询条件:交易时间间隔必须要小于30天');
        $nodeId = I('node_id', null, 'mysql_escape_string');
        if (! empty($nodeId)) {
            $map['c.node_id '] = $nodeId;
            $seachStatus = 1;
        }
        $codeStatus = I('code_status', null, 'mysql_real_escape_string');
        if (isset($codeStatus) && $codeStatus != '') {
            $map['c.status'] = $codeStatus;
            $seachStatus = 1;
        }
        $sendType = I('send_type', null, 'mysql_real_escape_string');
        if ($sendType == '1') {
            $map['c.wx_open_id'] = array(
                'exp', 
                'is null');
            $seachStatus = 1;
        } elseif ($sendType == '2') {
            $map['c.wx_open_id'] = array(
                'exp', 
                'is not null');
            $seachStatus = 1;
        }
        $codeTransType = I('trans_type', null, 'mysql_real_escape_string');
        if (! empty($codeTransType)) {
            $map['c.trans_type'] = $codeTransType;
            $seachStatus = 1;
        }
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = M()->table("tbarcode_trace c")->join(
            'tnode_info n ON c.node_id=n.node_id')
            ->join('tgoods_info g ON c.goods_id=g.goods_id')
            ->join('tbatch_info b ON c.b_id=b.id')
            ->join('tmarketing_info m ON b.m_id=m.id')
            ->where($map)
            ->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        
        foreach ($_REQUEST as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        
        $show = $Page->show(); // 分页显示输出
        $list = M()->table("tbarcode_trace c")->field(
            'c.id,c.wx_open_id,c.node_id,c.data_from,c.trans_time,c.trans_type,c.phone_no,c.status,m.batch_type,n.node_name,g.goods_name,c.resend_allow_flag')
            ->join('tnode_info n ON c.node_id=n.node_id')
            ->join('tgoods_info g ON c.goods_id=g.goods_id')
            ->join('tbatch_info b ON c.b_id=b.id')
            ->join('tmarketing_info m ON b.m_id=m.id')
            ->where($map)
            ->order('c.trans_time desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        
        $batchTypeName = C('BATCH_TYPE_NAME');
        $status = array(
            '0' => '成功', 
            '1' => '已撤销', 
            '3' => '失败');
        $transType = array(
            '0001' => '发码', 
            '0002' => '撤销'); // 重发不记流水
        $this->assign('status', $status);
        $this->assign('transType', $transType);
        $this->assign('list', $list);
        $this->assign('batchTypeName', $batchTypeName);
        $this->assign('nodeList', $this->getNodeTree());
        $this->assign('seachStatus', $seachStatus);
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }

    public function sendCodeDetail() {
        $id = I('id', null, 'mysql_real_escape_string');
        /*************************基本信息*********************/
        $map = array(
            'c.node_id' => array(
                'exp', 
                "in ({$this->nodeIn()})"), 
            'c.data_from' => array(
                'in', 
                '0,1,2,3,4,6,7,8,9'),
            'g.goods_type' => array(
                'in', 
                '0,1,2,3,11'), 
            'g.source' => array(
                'in', 
                '0,1,4'), 
            'c.id' => $id);
        $basicInfo = M()->table("tbarcode_trace c")->field(
            'c.id,c.wx_open_id,c.node_id,c.req_seq,c.data_from,c.trans_time,c.trans_type,c.phone_no,c.status,c.use_status,c.req_seq,m.batch_type,m.name,n.node_name,g.goods_name')
            ->join('tnode_info n ON c.node_id=n.node_id')
            ->join('tgoods_info g ON c.goods_id=g.goods_id')
            ->join('tbatch_info b ON c.b_id=b.id')
            ->join('tmarketing_info m ON b.m_id=m.id')
            ->where($map)
            ->find();
        if (! $basicInfo) {
            $this->error('该发码信息未找到');
        }
        /*************************发送流水*********************/
        $sendFlow = M('tbarcode_trace_send')->where(array('org_req_seq'=>$basicInfo['req_seq']))->select();

        /*************************验证流水*********************/
        $verifyFlow = M('tpos_trace')->field('a.*,b.store_name')->where(array('req_seq'=>$basicInfo['req_seq']))->join(' a LEFT JOIN tpos_info b ON a.pos_id=b.pos_id ')->select();

        $batchTypeName = C('BATCH_TYPE_NAME');
        //交易状态
        $status = array(
            '0' => '成功', 
            '1' => '已撤销', 
            '3' => '失败');
        //交易类型
        $transType = array(
            '0001' => '发码', 
            '0002' => '撤销'); // 重发不记流水
        //使用状态
        $useStatus = array(
            '未使用', '使用中', '已使用'
        );
        //送达状态
        $sendStatus = array(
                '发送中', '发送成功，递送手机未知', '发送失败', '送达手机成功', '送达手机失败'
        );
        //验证流水的状态
        $verifyStatus = array('0'=>'成功','1'=>'失败','2'=>'冲正','6'=>'支付宝支付待确认');

        $this->assign('status', $status);
        $this->assign('transType', $transType);
        $this->assign('useStatus', $useStatus);
        $this->assign('data', $basicInfo);
        $this->assign('batchTypeName', $batchTypeName);
        $this->assign('sendFlow', $sendFlow);
        $this->assign('sendStatus', $sendStatus);
        $this->assign('verifyFlow', $verifyFlow);
        $this->assign('verifyStatus', $verifyStatus);
        $this->display();
    }

    public function downParcodeTrace() {
        $map = array(
            'c.node_id' => array(
                'exp', 
                "in ({$this->nodeIn()})"), 
            'g.goods_type' => array(
                'in', 
                '0,1,2,3,11'), 
            'g.source' => array(
                'in', 
                '0,1,4'));
        $name = I('name', null, 'mysql_real_escape_string');
        if (! empty($name)) {
            $map['g.goods_name'] = array(
                'like', 
                "%{$name}%");
        }
        $btransTime = I('btrans_time', null, 'mysql_real_escape_string');
        if (! empty($btransTime)) {
            $map['c.trans_time'] = array(
                'egt', 
                $btransTime . '000000');
        }
        $etransTime = I('etrans_time', null, 'mysql_real_escape_string');
        if (! empty($etransTime)) {
            $map['c.trans_time '] = array(
                'elt', 
                $etransTime . '235959');
        }
        $batchType = I('batch_type', null, 'mysql_real_escape_string');
        if (! empty($batchType)) {
            $map['m.batch_type'] = $batchType;
        }
        $mobile = I('mobile', null, 'mysql_real_escape_string');
        if (! empty($mobile)) {
            $map['c.phone_no'] = $mobile;
        }
        $nodeId = I('node_id', null, 'mysql_escape_string');
        if (! empty($nodeId)) {
            $map['c.node_id '] = $nodeId;
        }
        $codeStatus = I('code_status', null, 'mysql_real_escape_string');
        if (isset($codeStatus) && $codeStatus != '') {
            $map['c.status'] = $codeStatus;
        }
        $sendType = I('send_type', null, 'mysql_real_escape_string');
        if ($sendType == '1') {
            $map['c.wx_open_id'] = array(
                'exp', 
                'is null');
        } elseif ($sendType == '2') {
            $map['c.wx_open_id'] = array(
                'exp', 
                'is not null');
        }
        $codeTransType = I('trans_type', null, 'mysql_real_escape_string');
        if (! empty($codeTransType)) {
            $map['c.trans_type'] = $codeTransType;
        }
        $mapcount = M()->table("tbarcode_trace c")->join(
            'tnode_info n ON c.node_id=n.node_id')
            ->join('tgoods_info g ON c.goods_id=g.goods_id')
            ->join('tbatch_info b ON c.b_id=b.id')
            ->join('tmarketing_info m ON b.m_id=m.id')
            ->where($map)
            ->count(); // 查询满足要求的总记录数
        if ($mapcount == 0)
            $this->error('未查询到记录！');
        $batchTypeName = C('BATCH_TYPE_NAME');
        $status = array(
            '0' => '成功', 
            '1' => '已撤销', 
            '3' => '失败');
        $transType = array(
            '0001' => '发码', 
            '0002' => '撤销', 
            '0003' => '重发');
        $fileName = '发码明细.csv';
        $fileName = iconv('utf-8', 'gb2312', $fileName);
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        $start_num = 0;
        $page_count = 5000;
        $cj_title = "券名,业务,交易时间,交易类型,下发通道,手机号,交易状态,所属商户\r\n";
        echo $cj_title = iconv('utf-8', 'gbk', $cj_title);
        for ($j = 1; $j < 100; $j ++) {
            $page = ($j - 1) * $page_count;
            $list = M()->table("tbarcode_trace c")->field(
                "c.wx_open_id,c.trans_time,c.trans_type,c.phone_no,c.status,m.batch_type,n.node_name,g.goods_name")
                ->join('tnode_info n ON c.node_id=n.node_id')
                ->join('tgoods_info g ON c.goods_id=g.goods_id')
                ->join('tbatch_info b ON c.b_id=b.id')
                ->join('tmarketing_info m ON b.m_id=m.id')
                ->where($map)
                ->order('c.id desc')
                ->limit($page, $page_count)
                ->select();
            if (! $list)
                exit();
            foreach ($list as $v) {
                $dGoodsName = iconv('utf-8', 'gbk', $v['goods_name']);
                $dBatchType = iconv('utf-8', 'gbk', 
                    $batchTypeName[$v['batch_type']]);
                $dTransTime = dateformat($v['trans_time'], 'Y-m-d');
                $dTransType = iconv('utf-8', 'gbk', 
                    $transType[$v['trans_type']]);
                $dSendType = iconv('utf-8', 'gbk', 
                    empty($v['wx_open_id']) ? '运营商' : '微信卡券');
                $dStatus = iconv('utf-8', 'gbk', $status[$v['status']]);
                $dNodeName = iconv('utf-8', 'gbk', $v['node_name']);
                echo "{$dGoodsName},{$dBatchType},{$dTransTime},{$dTransType},{$dSendType},{$v['phone_no']},{$dStatus},{$dNodeName}\r\n";
            }
        }
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
        if (! $query)
            $this->error('无下载数据！');
        
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

    public function reSend() {
        $id = trim(I('id'));
        $model = M('tbarcode_trace');
        $query_arr = $model->where(
            array(
                'id' => $id, 
                'node_id' => $this->nodeId))->find();
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
                        "goods_id={$query_arr['goods_id']}")->getField(
                        'goods_name') . "手机号：{$query_arr['phone_no']}");
                $this->success('重发成功！');
            } else {
                $this->error('重发失败' . $resp);
            }
        }
    }

    /**
     * 撤销发码
     */
    public function revocationCode() {
        $id = I('id', null, 'intval');
        if (empty($id))
            $this->error('参数错误');
        $codeInfo = M('tbarcode_trace')->where(
            "node_id='{$this->nodeId}' AND id='{$id}'")->find();
        if (! $codeInfo)
            $this->error('未找到相关数据');
        if ($codeInfo['data_from'] == '9') {
            $this->error('爱拍条码无法撤销！');
        }
        if ($codeInfo['trans_type'] != '0001' && $codeInfo['status'] != '0')
            $this->error('该码信息有误，无法撤销');
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
                    'tgoods_info')->where("goods_id={$codeInfo['goods_id']}")->getField(
                    'goods_name') . "手机号：{$codeInfo['phone_no']}");
            $this->success('撤销成功！');
        } else {
            $this->error($resp);
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
             $where . " trans_type='0001' and status='0' GROUP BY pos_id ) d
					ON d.pos_id=c.pos_id
					LEFT  JOIN (SELECT COUNT(*) AS p_count,pos_id FROM tpos_trace WHERE " .
             $where . "  trans_type='0' AND STATUS='0'  GROUP BY pos_id)  e
					ON e.pos_id=c.pos_id
					LEFT JOIN (SELECT COUNT(*) AS c_count,pos_id FROM tpos_trace WHERE " .
             $where .
             "  trans_type='1' AND STATUS='0' GROUP BY pos_id)  g
					ON g.pos_id=c.pos_id
					WHERE  p_count !='' OR s_count!='' OR c_count !=0
				) f
				ON a.store_id= f.store_id   where a.node_id='{$this->nodeId}' " .
             $where_ .
             "  GROUP BY f.store_id
				UNION SELECT '00000000' AS store_id , '00000000' AS store_name ,COUNT(*) AS count1 ,'0' AS count2, '0' AS count3  FROM tbarcode_trace WHERE " .
             $where . " trans_type='0001' and status='0' and " . $is_where .
             " and pos_id='00000000' having count(*)>0 ";
        
        $query = $model->query($sql);
        $sql2 = "select count(*) as all_count from (" . $sql . ") tb";
        
        $count = $model->query($sql2);
        
        $model = M('tstore_info');
        $map = array(
            'node_id' => $this->nodeId, 
            'store_name' => array(
                'NEQ', 
                ''));
        
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = $count[0]['all_count']; // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $Page->parameter .= "&store_name=" . urlencode($store_name) . '&';
        $show = $Page->show(); // 分页显示输出
        $list = $model->query(
            "select * from (" . $sql . ") tb limit " . $Page->firstRow . "," .
                 $Page->listRows);
        // $list = $model->where($map)->order('id
        // desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show); // 赋值分页输出
        $this->display();
    }
    
    // 按门店下载流水
    public function storeTraceDown() {
        $type = I('type');
        $store_id = I('store_id');
        if (empty($type) || empty($store_id))
            $this->error('非法参数！');
        
        if ($store_id != '00000000') {
            $model = M('tpos_info');
            $map = array(
                'node_id' => $this->nodeId, 
                'store_id' => $store_id);
            $query_arr = $model->where($map)
                ->field('pos_id')
                ->select();
            if (! $query_arr)
                $this->error('未查询到门店信息！');
            
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
					on a.goods_id=b.goods_id where a.node_id=b.node_id
					and a.node_id=" .
                 $this->nodeId . " and a.trans_type='0001'
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
					on a.goods_id=b.goods_id where a.node_id=b.node_id
					and a.node_id=" . $this->nodeId . " and a.trans_type='0'
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
					on a.goods_id=b.goods_id where a.node_id=b.node_id
					and a.node_id=" . $this->nodeId . " and a.trans_type='1'
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
					on a.goods_id=b.goods_id where a.node_id=b.node_id
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
					on a.goods_id=b.goods_id where a.node_id=b.node_id
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
					on a.goods_id=b.goods_id where a.node_id=b.node_id
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
                if ($i == 1)
                    $vv = date('Y-m-d/H:i:s', strtotime($vv));
                $td .= '<td>' . $vv . '</td>';
            }
            echo $tr = '<tr>' . $td . '</tr>';
        }
        echo '</table></body></html>';
        
        exit();
    }
    
    // 验证流水
    function posTrace() {
        $map = array(
            'p.status' => array(
                'in', 
                '0,1'), 
            'g.goods_type' => array(
                'in', 
                '0,1,2,3,11'));
        $seachStatus = 0; // 更多筛选状态
        $name = I('goods_name', null, 'mysql_real_escape_string');
        if (! empty($name)) {
            $map['g.goods_name'] = array(
                'like', 
                "%{$name}%");
        }
        $mobile = I('mobile', null, 'mysql_real_escape_string');
        if (! empty($mobile)) {
            $map['p.phone_no'] = $mobile;
        }
        $storeName = I('store_name', null, 'mysql_real_escape_string');
        if (! empty($storeName)) {
            $map['s.store_name'] = array(
                'like', 
                "%{$storeName}%");
        }
        $posName = I('pos_name', null, 'mysql_real_escape_string');
        if (! empty($posName)) {
            $map['i.pos_name'] = array(
                'like', 
                "%{$posName}%");
        }
        $nodeId = I('node_id', null, 'mysql_escape_string');
        if (! empty($nodeId)) {
            $map['n.node_id '] = $nodeId;
            $seachStatus = 1;
        }
        $codeStatus = I('code_status', null, 'mysql_real_escape_string');
        if (isset($codeStatus) && $codeStatus != '') {
            $map['p.status '] = $codeStatus;
            $seachStatus = 1;
        }
        $bPrice = I('b_price', null, 'mysql_real_escape_string');
        if (! empty($bPrice)) {
            $map['p.exchange_amt'] = array(
                'egt', 
                $bPrice);
            $seachStatus = 1;
        }
        $ePrice = I('e_price', null, 'mysql_real_escape_string');
        if (! empty($ePrice)) {
            $map['p.exchange_amt '] = array(
                'elt', 
                $ePrice);
            $seachStatus = 1;
        }
        $btransTime = I('btrans_time', null, 'mysql_real_escape_string');
        if (empty($btransTime) && ! $this->isPost() && empty($_REQUEST['p'])) {
            $bdate = date("Ymd", strtotime("-1 week")); // 最多取一个星期前的数据
            $map['p.trans_time'] = array(
                'egt', 
                $bdate . '000000');
            $_REQUEST['btrans_time'] = $bdate;
        } else if (! empty($btransTime)) {
            $map['p.trans_time'] = array(
                'egt', 
                $btransTime . '000000');
        }
        $etransTime = I('etrans_time', null, 'mysql_real_escape_string');
        if (! empty($etransTime)) {
            $map['p.trans_time '] = array(
                'elt', 
                $etransTime . '235959');
        }
        $checkDay = DateDiff($etransTime, $btransTime, 'd');
        if ($checkDay > 30)
            $this->error('查询条件:交易时间间隔必须要小于30天');
        $source = I('source');
        switch ($source) {
            case '0':
                $map['p.node_id'] = array(
                    'exp', 
                    "in ({$this->nodeIn()})");
                $map['g.source'] = '0';
                $seachStatus = 1;
                break;
            case '1':
                $map['p.node_id'] = array(
                    'exp', 
                    "in ({$this->nodeIn()})");
                $map['g.source'] = '1';
                $seachStatus = 1;
                break;
            case '2': // 分销给我的
                $map['p.node_id'] = array(
                    'exp', 
                    "in ({$this->nodeIn()})");
                $map['g.source'] = '4';
                $seachStatus = 1;
                break;
            case '3': // 分销给他的
                $map['p.pos_node_id'] = array(
                    'exp', 
                    "in ({$this->nodeIn()})");
                $map['g.source'] = '4';
                $map['p.node_id'] = array(
                    'exp', 
                    "not in ({$this->nodeIn()})");
                $seachStatus = 1;
                break;
            default: // 全部
                     // 判断该商户是不是定向销售的供应商
                $isMypartner = M('tsale_node_relation')->where(
                    "node_id='{$this->nodeId}'")->count();
                if ($isMypartner > 0) {
                    $map['_string'] = "((g.source IN(0,1,4) AND p.node_id IN({$this->nodeIn()})) OR (g.source=4 AND p.node_id NOT IN({$this->nodeIn()}) AND p.pos_node_id IN({$this->nodeIn()})))";
                } else {
                    $map['g.source'] = array(
                        'in', 
                        '0,1,4');
                    $map['p.node_id'] = array(
                        'exp', 
                        "in ({$this->nodeIn()})");
                }
                $seachStatus = 1;
        }
        import('ORG.Util.Page'); // 导入分页类
        if ($this->node_id == C("cnpc_gx.node_id")) {
            $label_id = I('label_id');
            if (! empty($label_id)) {
                $map['_string'] = "EXISTS (SELECT * FROM tfb_cnpcgx_store_label lb WHERE lb.`label_id` = " .
                     $label_id . " AND lb.`store_id` = s.id)";
            }
        }
        $mapcount = M()->table("tpos_trace p")->join(
            'tnode_info n ON p.node_id=n.node_id')
            ->join('tgoods_info g ON p.goods_id=g.goods_id')
            ->join('tpos_info i ON p.pos_id=i.pos_id')
            ->where($map)
            ->count();
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        
        foreach ($_REQUEST as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        
        $show = $Page->show(); // 分页显示输出
        
        $list = M()->table("tpos_trace p")->field(
            'g.goods_name,g.source,p.id,p.trans_time,p.node_id,p.pos_node_id,p.trans_type,p.phone_no,p.exchange_amt,p.status,s.store_short_name,i.pos_name,n.node_name')
            ->join('tnode_info n ON p.node_id=n.node_id')
            ->join('tgoods_info g ON p.goods_id=g.goods_id')
            ->join('tpos_info i ON p.pos_id=i.pos_id')
            ->join('tstore_info s ON i.store_id=s.store_id')
            ->where($map)
            ->order('p.trans_time desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        $status = array(
            '0' => '成功', 
            '1' => '失败');
        $transType = array(
            '0' => '验证', 
            '1' => '撤销', 
            '2' => '冲正', 
            '3' => '电话验证', 
            '9001' => '异常验证', 
            'T' => '支付宝支付', 
            'R' => '支付宝退款');
        $sourceType = array(
            '0' => '自建', 
            '1' => '采购', 
            '2' => '分销(给我的)', 
            '3' => '分销(给他人的)');
        if ($this->node_id == C("cnpc_gx.node_id")) {
            /* 标签 */
            $label_list = M()->table("tfb_cnpcgx_storelabel a")
                ->order('a.id desc')
                ->field("a.id,a.label")
                ->select();
            $this->assign('label_list', $label_list);
        }
        // dump($list);
        $this->assign('status', $status);
        $this->assign('transType', $transType);
        $this->assign('list', $list);
        $this->assign('nodeList', $this->getNodeTree());
        $this->assign('seachStatus', $seachStatus);
        $this->assign('sourceType', $sourceType);
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }

    public function posDetail() {
        $id = I('id', null, 'mysql_real_escape_string');
        $map = array(
            'p.status' => array(
                'in', 
                '0,1'), 
            'g.goods_type' => array(
                'in', 
                '0,1,2,3,11'), 
            'p.id' => $id, 
            '_string' => "((g.source IN(0,1,4) AND p.node_id IN({$this->nodeIn()})) OR (g.source=4 AND p.node_id NOT IN({$this->nodeIn()}) AND p.pos_node_id IN({$this->nodeIn()})))");
        $data = M()->table("tpos_trace p")->field(
            'g.goods_name,g.source,p.trans_time,p.pos_seq,p.node_id,p.pos_node_id,p.trans_type,p.phone_no,p.exchange_amt,p.status,s.store_short_name,i.pos_name,i.pos_id,i.pos_type,i.pos_status,n.node_name')
            ->join('tnode_info n ON p.node_id=n.node_id')
            ->join('tgoods_info g ON p.goods_id=g.goods_id')
            ->join('tpos_info i ON p.pos_id=i.pos_id')
            ->join('tstore_info s ON i.store_id=s.store_id')
            ->where($map)
            ->find();
        if (! $data)
            $this->error('该验码信息未找到');
        $status = array(
            '0' => '成功', 
            '1' => '失败');
        $transType = array(
            '0' => '验证', 
            '1' => '撤销', 
            '2' => '冲正', 
            '3' => '电话验证', 
            '9001' => '异常验证', 
            'T' => '支付宝支付', 
            'R' => '支付宝退款');
        $posType = array(
            '0' => '旺财', 
            '1' => '6800', 
            '2' => 'epos', 
            '9' => '其他');
        $posStatus = array(
            '0' => '正常', 
            '1' => '欠费', 
            '2' => '停机保号', 
            '3' => '注销', 
            '4' => '过期');
        $sourceType = array(
            '0' => '自建', 
            '1' => '采购', 
            '2' => '分销(给我的)', 
            '3' => '分销(给他人的)');
        $this->assign('status', $status);
        $this->assign('posType', $posType);
        $this->assign('posStatus', $posStatus);
        $this->assign('transType', $transType);
        $this->assign('data', $data);
        $this->assign('sourceType', $sourceType);
        $this->display(); // 输出模板
    }

    public function downPosTrace() {
        $map = array(
            'p.status' => array(
                'in', 
                '0,1'), 
            'g.goods_type' => array(
                'in', 
                '0,1,2,3,11'));
        $name = I('goods_name', null, 'mysql_real_escape_string');
        if (! empty($name)) {
            $map['g.goods_name'] = array(
                'like', 
                "%{$name}%");
        }
        $mobile = I('mobile', null, 'mysql_real_escape_string');
        if (! empty($mobile)) {
            $map['p.phone_no'] = $mobile;
        }
        $storeName = I('store_name', null, 'mysql_real_escape_string');
        if (! empty($storeName)) {
            $map['s.store_name'] = array(
                'like', 
                "%{$storeName}%");
        }
        $posName = I('pos_name', null, 'mysql_real_escape_string');
        if (! empty($posName)) {
            $map['i.pos_name'] = array(
                'like', 
                "%{$posName}%");
        }
        $nodeId = I('node_id', null, 'mysql_escape_string');
        if (! empty($nodeId)) {
            $map['n.node_id '] = $nodeId;
        }
        $codeStatus = I('code_status', null, 'mysql_real_escape_string');
        if (isset($codeStatus) && $codeStatus != '') {
            $map['p.status '] = $codeStatus;
        }
        $bPrice = I('b_price', null, 'mysql_real_escape_string');
        if (! empty($bPrice)) {
            $map['p.exchange_amt'] = array(
                'egt', 
                $bPrice);
        }
        $ePrice = I('e_price', null, 'mysql_real_escape_string');
        if (! empty($ePrice)) {
            $map['p.exchange_amt '] = array(
                'elt', 
                $ePrice);
        }
        $btransTime = I('btrans_time', null, 'mysql_real_escape_string');
        if (! empty($btransTime)) {
            $map['p.trans_time'] = array(
                'egt', 
                $btransTime . '000000');
        }
        $etransTime = I('etrans_time', null, 'mysql_real_escape_string');
        if (! empty($etransTime)) {
            $map['p.trans_time '] = array(
                'elt', 
                $etransTime . '235959');
        }
        $source = I('source');
        switch ($source) {
            case '0':
                $map['p.node_id'] = array(
                    'exp', 
                    "in ({$this->nodeIn()})");
                $map['g.source'] = '0';
                break;
            case '1':
                $map['p.node_id'] = array(
                    'exp', 
                    "in ({$this->nodeIn()})");
                $map['g.source'] = '1';
                break;
            case '2': // 分销给我的
                $map['p.node_id'] = array(
                    'exp', 
                    "in ({$this->nodeIn()})");
                $map['g.source'] = '4';
                break;
            case '3': // 分销给他的
                $map['p.pos_node_id'] = array(
                    'exp', 
                    "in ({$this->nodeIn()})");
                $map['g.source'] = '4';
                $map['p.node_id'] = array(
                    'exp', 
                    "not in ({$this->nodeIn()})");
                break;
            default: // 全部
                     // 判断该商户是不是定向销售的供应商
                $isMypartner = M('tsale_node_relation')->where(
                    "node_id='{$this->nodeId}'")->count();
                if ($isMypartner > 0) {
                    $map['_string'] = "((g.source IN(0,1,4) AND p.node_id IN({$this->nodeIn()})) OR (g.source=4 AND p.node_id NOT IN({$this->nodeIn()}) AND p.pos_node_id IN({$this->nodeIn()})))";
                } else {
                    $map['g.source'] = array(
                        'in', 
                        '0,1,4');
                    $map['p.node_id'] = array(
                        'exp', 
                        "in ({$this->nodeIn()})");
                }
        }
        $mapcount = M()->table("tpos_trace p")->join(
            'tnode_info n ON p.node_id=n.node_id')
            ->join('tgoods_info g ON p.goods_id=g.goods_id')
            ->join('tpos_info i ON p.pos_id=i.pos_id')
            ->join('tstore_info s ON i.store_id=s.store_id')
            ->where($map)
            ->count();
        if ($mapcount == 0)
            $this->error('未查询到记录！');
        $status = array(
            '0' => '成功', 
            '1' => '失败');
        $transType = array(
            '0' => '验证', 
            '1' => '撤销', 
            '2' => '冲正', 
            '3' => '电话验证', 
            '9001' => '异常验证');
        $fileName = '核销明细.csv';
        $fileName = iconv('utf-8', 'gb2312', $fileName);
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        $start_num = 0;
        $page_count = 5000;
        $title = "券名,交易时间,交易类型,门店,终端,手机号,交易金额,交易状态,所属商户\r\n";
        echo $title = iconv('utf-8', 'gbk', $title);
        for ($j = 1; $j < 100; $j ++) {
            $page = ($j - 1) * $page_count;
            $list = M()->table("tpos_trace p")->field(
                "g.goods_name,p.trans_time,p.trans_type,p.phone_no,p.exchange_amt,p.status,s.store_name,i.pos_name,n.node_name")
                ->join('tnode_info n ON p.node_id=n.node_id')
                ->join('tgoods_info g ON p.goods_id=g.goods_id')
                ->join('tpos_info i ON p.pos_id=i.pos_id')
                ->join('tstore_info s ON i.store_id=s.store_id')
                ->where($map)
                ->order('p.id desc')
                ->limit($page, $page_count)
                ->select();
            if (! $list)
                exit();
            foreach ($list as $v) {
                $dGoodsName = iconv('utf-8', 'gbk', $v['goods_name']);
                $dTransTime = dateformat($v['trans_time'], 'Y-m-d');
                $dTransType = iconv('utf-8', 'gbk', 
                    $transType[$v['trans_type']]);
                $dStoreName = iconv('utf-8', 'gbk', $v['store_name']);
                $dPosName = iconv('utf-8', 'gbk', $v['pos_name']);
                $dStatus = iconv('utf-8', 'gbk', $status[$v['status']]);
                $dNodeName = iconv('utf-8', 'gbk', $v['node_name']);
                echo "{$dGoodsName},{$dTransTime},{$dTransType},{$dStoreName},{$dPosName},{$v['phone_no']},{$v['exchange_amt']},{$dStatus},{$dNodeName}\r\n";
            }
        }
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
                if (! $query_arr)
                    $this->error('没有数据可供下载');
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
                if (! $query_arr)
                    $this->error('没有数据可供下载');
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
        if (empty($channelId))
            $this->error('参数错误');
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
                if (! $query_arr)
                    $this->error('没有数据可供下载');
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
                if (! $query_arr)
                    $this->error('没有数据可供下载');
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

    public function failTrace() {
        if ($_SESSION['userSessInfo']['node_id'] == '00023332') {
            $map = array(
                'c.node_id' => array(
                    'exp', 
                    "in ({$this->nodeIn()})"), 
                'g.goods_type' => array(
                    'in', 
                    '0,1,2,3,11'), 
                'g.source' => array(
                    'in', 
                    '0,1,4'));
            $name = I('name', null, 'mysql_real_escape_string');
            if (! empty($name)) {
                $map['g.goods_name'] = array(
                    'like', 
                    "%{$name}%");
            }
            $mobile = I('mobile', null, 'mysql_real_escape_string');
            if (! empty($mobile)) {
                $map['c.phone_no'] = $mobile;
            }
            $btransTime = I('btrans_time', null, 'mysql_real_escape_string');
            if (empty($btransTime) && ! $this->isPost() && empty($_REQUEST['p'])) {
                $bdate = date("Ymd", strtotime("-1 week")); // 最多取一个星期前的数据
                $map['c.trans_time'] = array(
                    'egt', 
                    $bdate . '000000');
                $_REQUEST['btrans_time'] = $bdate;
            } else if (! empty($btransTime)) {
                $map['c.trans_time'] = array(
                    'egt', 
                    $btransTime . '000000');
            }
            $etransTime = I('etrans_time', null, 'mysql_real_escape_string');
            if (! empty($etransTime)) {
                $map['c.trans_time '] = array(
                    'elt', 
                    $etransTime . '235959');
            }
            $map['c.send_status'] = array(
                'neq', 
                '3');
            $map['c.status'] = '0';
            import('ORG.Util.Page'); // 导入分页类
            $mapcount = M()->table("tbarcode_trace c")->join(
                'tgoods_info g ON c.goods_id=g.goods_id')
                ->where($map)
                ->count(); // 查询满足要求的总记录数
            $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
            
            foreach ($_REQUEST as $key => $val) {
                $Page->parameter .= "&$key=" . urlencode($val) . '&';
            }
            
            $show = $Page->show(); // 分页显示输出
            $list = M()->table("tbarcode_trace c")->field(
                'c.trans_time,c.phone_no,g.goods_name,c.send_status,c.id')
                ->join('tgoods_info g ON c.goods_id=g.goods_id')
                ->where($map)
                ->order('c.trans_time desc')
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->select();
            
            $send_status = array(
                '0' => '发送中', 
                '1' => '发送成功，递送手机未知', 
                '2' => '发送失败', 
                '3' => '送达手机成功', 
                '4' => '送达手机失败');
            
            foreach ($list as &$val) {
                $val['send_status'] = $send_status[$val['send_status']];
            }
            
            $this->assign('mapcount', $mapcount);
            $this->assign('list', $list);
            $this->assign('page', $show); // 赋值分页输出
            $this->display(); // 输出模板
        } else {
            $this->redirect('index');
        }
    }

    public function downFailTrace() {
        $map = array(
            'c.node_id' => array(
                'exp', 
                "in ({$this->nodeIn()})"), 
            'g.goods_type' => array(
                'in', 
                '0,1,2,3,11'), 
            'g.source' => array(
                'in', 
                '0,1,4'));
        $name = I('name', null, 'mysql_real_escape_string');
        if (! empty($name)) {
            $map['g.goods_name'] = array(
                'like', 
                "%{$name}%");
        }
        $btransTime = I('btrans_time', null, 'mysql_real_escape_string');
        if (! empty($btransTime)) {
            $map['c.trans_time'] = array(
                'egt', 
                $btransTime . '000000');
        }
        $etransTime = I('etrans_time', null, 'mysql_real_escape_string');
        if (! empty($etransTime)) {
            $map['c.trans_time '] = array(
                'elt', 
                $etransTime . '235959');
        }
        $mobile = I('mobile', null, 'mysql_real_escape_string');
        if (! empty($mobile)) {
            $map['c.phone_no'] = $mobile;
        }
        $map['c.send_status'] = array(
            'neq', 
            '3');
        $map['c.status'] = '0';
        $mapcount = M()->table("tbarcode_trace c")->join(
            'tgoods_info g ON c.goods_id=g.goods_id')
            ->join('tbatch_info b ON c.b_id=b.id')
            ->where($map)
            ->count(); // 查询满足要求的总记录数
        if ($mapcount == 0)
            $this->error('未查询到记录！');
        $batchTypeName = C('BATCH_TYPE_NAME');
        $status = array(
            '0' => '发送中', 
            '1' => '发送成功，递送手机未知', 
            '2' => '发送失败', 
            '3' => '送达手机成功', 
            '4' => '送达手机失败');
        $fileName = '送达失败明细.csv';
        $fileName = iconv('utf-8', 'gb2312', $fileName);
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        $start_num = 0;
        $page_count = 5000;
        $cj_title = "卡券名称,发送时间,手机号,送达状态\r\n";
        echo $cj_title = iconv('utf-8', 'gbk', $cj_title);
        for ($j = 1; $j < 100; $j ++) {
            $page = ($j - 1) * $page_count;
            $list = M()->table("tbarcode_trace c")->field(
                "c.trans_time,c.phone_no,g.goods_name,c.send_status")
                ->join('tgoods_info g ON c.goods_id=g.goods_id')
                ->where($map)
                ->order('c.id desc')
                ->limit($page, $page_count)
                ->select();
            if (! $list)
                exit();
            foreach ($list as $v) {
                $dGoodsName = iconv('utf-8', 'gbk', $v['goods_name']);
                $dTransTime = dateformat($v['trans_time'], 'Y-m-d');
                $dSendStatus = iconv('utf-8', 'gbk', $status[$v['send_status']]);
                echo "{$dGoodsName},{$dTransTime},{$v['phone_no']},{$dSendStatus}\r\n";
            }
        }
    }

    public function batchSend() {
        $id = I('id', '', 'mysql_real_escape_string');
        $mapcount = I('mapcount', '', 'mysql_real_escape_string');
        if ($id) {
            $id = substr($id, 0, - 1);
            $list = M()->table("tbarcode_trace c")->field(
                'c.trans_time,c.phone_no,g.goods_name,c.send_status,c.batch_no,c.request_id')
                ->join('tgoods_info g ON c.goods_id=g.goods_id')
                ->join('tbatch_info b ON c.b_id=b.id')
                ->where('c.id in (' . $id . ')')
                ->select();
        } else {
            $map = array(
                'c.node_id' => array(
                    'exp', 
                    "in ({$this->nodeIn()})"), 
                'g.goods_type' => array(
                    'in', 
                    '0,1,2,3,11'), 
                'g.source' => array(
                    'in', 
                    '0,1,4'));
            $name = I('name', null, 'mysql_real_escape_string');
            if (! empty($name)) {
                $map['g.goods_name'] = array(
                    'like', 
                    "%{$name}%");
            }
            $mobile = I('mobile', null, 'mysql_real_escape_string');
            if (! empty($mobile)) {
                $map['c.phone_no'] = $mobile;
            }
            $btransTime = I('btrans_time', null, 'mysql_real_escape_string');
            if (! empty($btransTime)) {
                $map['c.trans_time'] = array(
                    'egt', 
                    $btransTime . '000000');
            }
            $etransTime = I('etrans_time', null, 'mysql_real_escape_string');
            if (! empty($etransTime)) {
                $map['c.trans_time '] = array(
                    'elt', 
                    $etransTime . '235959');
            }
            $map['c.send_status'] = array(
                'neq', 
                '3');
            $map['c.status'] = '0';
            $list = M()->table("tbarcode_trace c")->field(
                'c.trans_time,c.phone_no,g.goods_name,c.send_status,c.batch_no,c.request_id')
                ->join('tgoods_info g ON c.goods_id=g.goods_id')
                ->join('tbatch_info b ON c.b_id=b.id')
                ->where($map)
                ->select();
        }
        
        $data['user_id'] = $_SESSION['userSessInfo']['user_id'];
        $data['node_id'] = $_SESSION['userSessInfo']['node_id'];
        $data['total_count'] = $mapcount;
        $data['send_level'] = '5';
        $data['add_time'] = date('YmdHis');
        $data['data_from'] = '2';
        $data['trans_type'] = '2';
        $batch_id = M('tbatch_import')->add($data);
        foreach ($list as $val) {
            $detail['batch_no'] = $val['batch_no'];
            $detail['batch_id'] = $batch_id;
            $detail['node_id'] = $_SESSION['userSessInfo']['node_id'];
            $detail['request_id'] = $val['request_id'];
            $detail['orirequest_id'] = $val['request_id'];
            $detail['phone_no'] = $val['phone_no'];
            $detail['add_time'] = date('YmdHis');
            $detail['trans_type'] = '2';
            M('tbatch_importdetail')->add($detail);
        }
        if ($batch_id) {
            $message = '操作成功';
        } else {
            $message = '操作失败';
        }
        $this->ajaxReturn($message);
    }
}


