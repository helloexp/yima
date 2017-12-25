<?php
//
class VoucherNumAction extends BaseAction {

    public function _initialize() {
        parent::_initialize();
    }

    public function formatDateNode($startDate, $endDate, $nodeCount = 5, 
        $format = 'Y-m-d') {
        $begin = strtotime($startDate);
        $end = strtotime($endDate);
        $days = floor(($end - $begin) / (24 * 3600));
        $node = $nodeCount - 1; // 日期节点数
        $dateArr = array(
            date($format, $begin));
        if ($days <= $node) {
            // 一天一个节点
            for ($i = 0; $i < $days; $i ++) {
                $begin += 24 * 3600;
                $dateArr[] = date($format, $begin);
            }
        } else {
            $nodeDays = floor($days / $node); // 每个节点之间的天数
            $remainder = $days % $node; // 余数
            for ($i = 0; $i < $node; $i ++) {
                if ($i == $node - 1) {
                    $nodeDays += $remainder;
                }
                $begin += $nodeDays * 24 * 3600;
                $dateArr[] = date($format, $begin);
            }
        }
        return $dateArr;
    }
    
    // 综合数据统计
    public function index() {
        $beginDate = I('begin_date', null);
        $endDate = I('end_date', null);
        $type = I('type', null);
        // 判断开始结束日期和统计类型
        empty($beginDate) ? $beginDate = date('Y-m-01', time()) : $beginDate; // 为空获得本月第一天日期
        empty($endDate) ? $endDate = date('Y-m-t', time()) : $endDate; // 为空获得本月最后一天日期
        empty($type) ? $type = 1 : $type;
        if (strtotime($beginDate) > strtotime($endDate)) {
            $this->error('开始日期不能大于结束日期');
        }
        // echo $beginDate.'::'.$endDate.'::'.$type;
        // 计算节点日期
        $nodeDate = $this->formatDateNode($beginDate, $endDate, 15);
        $couponSum = array(); // 优惠券数量
        $vouchersSum = array(); // 代金券数量
        $physicalSum = array(); // 实物券数量
        $valueCardSum = array(); // 消费券
        $blocSum = array(); // 旺财联盟商品
        
        switch ($type) {
            case '1': // 发码
                $fieldType = 'send_num';
                $type = '1';
                break;
            case '2': // 验码
                $fieldType = 'verify_num';
                $type = '2';
                break;
            case '3': // 撤销
                $fieldType = 'cancel_num';
                $type = '3';
                break;
            default:
                $this->error('未知的统计类型');
        }
        // 计算各时间节点，不同类型活动的发码，验码，撤销数量
        foreach ($nodeDate as $k => $v) {
            // 第一天的发码量从零开始
            if ($k == 0) {
                // 优惠券数量
                $couponSum[] = '0';
                // 代金券数量
                $vouchersSum[] = '0';
                // 实物券数量
                $physicalSum[] = '0';
                // 消费券数量
                $valueCardSum[] = '0';
                // 旺财联盟商品
                $blocSum[] = '0';
                continue;
            }
            $where = array(
                'i.node_id' => array(
                    'exp', 
                    "in ({$this->nodeIn()})"), 
                // 'i.batch_no' => array('neq',''),
                'c.trans_date ' => array(
                    'gt', 
                    $nodeDate[$k - 1]), 
                'c.trans_date' => array(
                    'elt', 
                    $v));
            $nodeId = I('node_id', null, 'mysql_escape_string');
            if (! empty($nodeId))
                $where['i.node_id '] = $nodeId;
                // 优惠券数量
            $couponWhere = array(
                'i.goods_type' => 0, 
                'i.source' => array(
                    'in', 
                    '0,1'));
            $count = M()->table('tgoods_info i')
                ->join('tpos_day_count c ON i.goods_id = c.goods_id')
                ->where(array_merge($couponWhere, $where))
                ->sum("c.{$fieldType}");
            $couponSum[] = is_null($count) ? 0 : $count;
            // 代金券数量
            $vouchersWhere = array(
                'i.goods_type' => 1, 
                'i.source' => array(
                    'in', 
                    '0,1'));
            $count = M()->table('tgoods_info i')
                ->join('tpos_day_count c ON i.goods_id = c.goods_id')
                ->where(array_merge($vouchersWhere, $where))
                ->sum("c.{$fieldType}");
            $vouchersSum[] = is_null($count) ? 0 : $count;
            // 实物券数量
            $physicalWhere = array(
                'i.goods_type' => 2, 
                'i.source' => array(
                    'in', 
                    '0,1'));
            $count = M()->table('tgoods_info i')
                ->join('tpos_day_count c ON i.goods_id = c.goods_id')
                ->where(array_merge($physicalWhere, $where))
                ->sum("c.{$fieldType}");
            $physicalSum[] = is_null($count) ? 0 : $count;
            // 消费券券数量
            $valueCardWhere = array(
                'i.goods_type' => 3, 
                'i.source' => array(
                    'in', 
                    '0,1'));
            $count = M()->table('tgoods_info i')
                ->join('tpos_day_count c ON i.goods_id = c.goods_id')
                ->where(array_merge($valueCardWhere, $where))
                ->sum("c.{$fieldType}");
            $valueCardSum[] = is_null($count) ? 0 : $count;
            
            // 旺财联盟营销品
            $blocWhere = array(
                'i.goods_type' => 1, 
                'i.source' => 2);
            $count = M()->table('tgoods_info i')
                ->join('tpos_day_count c ON i.goods_id = c.goods_id')
                ->where(array_merge($blocWhere, $where))
                ->sum("c.{$fieldType}");
            $blocSum[] = is_null($count) ? 0 : $count;
        }
        $typeInfo = array(
            '1' => '发码量', 
            '2' => '验码量', 
            '3' => '撤销量');
        $nodeDateStr = "'" . implode("','", $nodeDate) . "'";
        $couponSum = implode(',', $couponSum);
        $vouchersSum = implode(',', $vouchersSum);
        $physicalSum = implode(',', $physicalSum);
        $valueCardSum = implode(',', $valueCardSum);
        $blocSum = implode(',', $blocSum);
        
        $this->assign('nodeDate', $nodeDateStr);
        $this->assign('couponSum', $couponSum);
        $this->assign('vouchersSum', $vouchersSum);
        $this->assign('physicalSum', $physicalSum);
        $this->assign('valueCardSum', $valueCardSum);
        $this->assign('blocSum', $blocSum);
        
        $this->assign('beginDate', $nodeDate[0]);
        $this->assign('endDate', $nodeDate[count($nodeDate) - 1]);
        $this->assign('type', $type);
        $this->assign('typeInfo', $typeInfo);
        $this->assign('nodeList', $this->getNodeTree());
        $this->display();
    }
    
    // 使用统计
    public function statis() {
        $nodeName = I('snode_name'); // 所属商户
        $noName = I('fnode_name'); // 分销商户
        $begin_time = I('begin_time');
        $end_time = I('end_time');
        $sql = "SELECT b.goods_name,b.trans_date,b.node_name AS 所属商户,a.node_name AS 发码商户,b.send_total_cnt,b.verify_total_cnt FROM tnode_info a,(
    	-- 取自用和别人分销给我的代金券数据
    	SELECT a.`goods_name`,b.`trans_date`,c.`node_name`,a.`node_id`,b.`send_total_cnt`,b.`verify_total_cnt` FROM tgoods_info a
    	LEFT JOIN tgoods_stat b ON a.id = b.g_id
    	LEFT JOIN tnode_info c ON a.`node_id` = c.`node_id`
    	WHERE a.goods_type = '1' AND a.node_id = '00017192' AND b.`trans_date`>= '20140101' AND b.trans_date < '20141106'
  UNION ALL
    	  -- 取我分销给别人的代金券数据
    	  SELECT a.`goods_name`,b.`trans_date`,c.`node_name`,a.node_id,b.`send_total_cnt`,b.`verify_total_cnt` FROM tgoods_info a
    	  LEFT JOIN tgoods_stat b ON a.id = b.g_id
    	  LEFT JOIN tnode_info c ON a.`purchase_node_id` = c.`node_id`
    	  WHERE a.goods_type = '1' AND a.purchase_node_id = '00017192' AND b.`trans_date`>= '20140101' AND b.trans_date < '20141106' ) b
  WHERE a.node_id = b.node_id";
        
        $map = array(
            'a.goods_type' => array(
                'exp', 
                '=1'), 
            'a.node_id' => $this->nodeId, 
            'b.trans_date' => array(
                array(
                    'egt', 
                    '20140101'), 
                array(
                    'elt', 
                    date('Ymd', time()))));
        $sql1 = M()->table("tgoods_info a")
            ->where($map)
            ->field(
            'a.goods_name, b.trans_date, c.node_name, a.node_id, b.send_total_cnt,b.verify_total_cnt')
            ->join('inner join tgoods_stat b ON a.id = b.g_id')
            ->join('inner join tnode_info c ON a.node_id = c.node_id')
            ->buildSql();
        $whe = array(
            'a.goods_type' => array(
                'exp', 
                '=1'), 
            'a.purchase_node_id' => $this->nodeId, 
            'b.trans_date' => array(
                array(
                    'egt', 
                    '20140101'), 
                array(
                    'elt', 
                    date('Ymd', time()))));
        $sql2 = M()->table("tgoods_info a")
            ->where($whe)
            ->field(
            'a.goods_name, b.trans_date, c.node_name, a.node_id, b.send_total_cnt,b.verify_total_cnt')
            ->join('inner join tgoods_stat b ON a.id = b.g_id')
            ->join('inner join tnode_info c ON a.purchase_node_id = c.node_id')
            ->buildSql();
        
        $last = array(
            'a.node_id' => array(
                'exp', 
                '=b.node_id'));
        if ($begin_time != '') {
            $last['b.trans_date'] = array(
                'egt', 
                $begin_time);
        }
        if ($end_time != '') {
            $last['b.trans_date'] = array(
                'elt', 
                $end_time);
        }
        if ($begin_time != '' && $end_time != '') {
            $last['b.trans_date'] = array(
                array(
                    'egt', 
                    $begin_time), 
                array(
                    'elt', 
                    $end_time));
        }
        if ($nodeName != '') {
            $last['b.node_name'] = array(
                'like', 
                "%$nodeName%");
        }
        if ($noName != '') {
            $last['a.node_name'] = array(
                'like', 
                "%$noName%");
        }
        import("ORG.Util.Page");
        $count = M()->table("tnode_info a,($sql1 union all $sql2) b")
            ->where($last)
            ->count();
        $p = new Page($count, 5);
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        $page = $p->show();
        $sql3 = M()->table("tnode_info a,($sql1 union all $sql2) b")
            ->where($last)
            ->field(
            'b.goods_name,b.trans_date,b.node_name AS anoname,a.node_name AS bnoname,b.send_total_cnt,b.verify_total_cnt')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->order('b.trans_date desc')
            ->select();
        $this->assign('list', $sql3);
        $this->assign('post', $_REQUEST);
        $this->assign('page', $page);
        $this->display();
    }
    
    // 流水查询
    public function searcher() {
        $data = $_REQUEST;
        $map = array(
            'c.node_id' => array(
                'exp', 
                "in ({$this->nodeIn()})"), 
            'c.data_from' => array(
                'in', 
                '0,1,2,3,4,6,7,9'), 
            'g.goods_type' => 1);
        $nodeId = I('node_id', null, 'mysql_escape_string');
        if (! empty($nodeId))
            $map['c.node_id '] = $nodeId;
        if ($data['mobile'] != '') {
            $map['c.phone_no'] = $data['mobile'];
        }
        if ($data['batch_no'] != '') {
            $map['c.batch_no'] = $data['batch_no'];
        }
        if ($data['batch_id'] != '') {
            $map['c.batch_id'] = $data['batch_id'];
        }
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = M()->table('tbarcode_trace c')
            ->join('tnode_info n ON c.node_id=n.node_id')
            ->join('tgoods_info g ON c.batch_no=g.batch_no')
            ->where($map)
            ->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        foreach ($data as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        $show = $Page->show(); // 分页显示输出
        $list = M()->table('tbarcode_trace c')
            ->field('c.*,n.node_name')
            ->join('tnode_info n ON c.node_id=n.node_id')
            ->join('tgoods_info g ON c.batch_no=g.batch_no')
            ->where($map)
            ->order('c.id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        $batch_arr = array();
        $batch = M('tgoods_info');
        if ($list) {
            foreach ($list as $v) {
                $batch_arr[$v['batch_no']] = $batch->where(
                    array(
                        'goods_id' => $v['goods_id']))->getField('goods_name');
            }
        }
        $status = array(
            '0' => '成功', 
            '1' => '已撤销', 
            '3' => '失败');
        $transType = array(
            '0001' => '发码', 
            '0002' => '撤销', 
            '0003' => '重发');
        $this->assign('batch_arr', $batch_arr);
        $this->assign('status', $status);
        $this->assign('transType', $transType);
        $this->assign('list', $list);
        $this->assign('nodeList', $this->getNodeTree());
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }

    /**
     * 撤销发码
     */
    public function revocationCode() {
        $id = I('get.id', null);
        if (empty($id))
            $this->error('参数错误');
        $codeInfo = M('tbarcode_trace')->where(
            "node_id='{$this->nodeId}' AND id={$id}")->find();
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
                '营销品', 
                '游戏', 
                '营销品', 
                '营销品', 
                '营销品', 
                '粉丝卡', 
                7 => '粉丝礼品');
            node_log(
                "{$dataFromArr[$codeInfo['data_from']]}撤消.名称：" . M(
                    'tgoods_info')->where("goods_id={$codeInfo['goods_id']}")->getField(
                    'goods_name') . "手机号：{$codeInfo['phone_no']}");
            $this->ajaxReturn($resp, "撤销成功！", 1);
        } else {
            $this->ajaxReturn($resp, "撤销失败！", 0);
        }
    }
    // 验证流水
    public function verification() {
        $mobile = I('mobile');
        $batch_no = I('batch_no');
        $data = array();
        $map = array(
            'p.node_id' => array(
                'exp', 
                "in ({$this->nodeIn()})"), 
            'p.status' => '0');
        $nodeId = I('node_id', null, 'mysql_escape_string');
        if (! empty($nodeId))
            $map['p.node_id '] = $nodeId;
        if ($mobile != '') {
            $map['p.phone_no'] = $mobile;
        }
        if ($batch_no != '') {
            $map['p.batch_no'] = $batch_no;
            $data[] = $batch_no;
        }
        
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = M()->table('tpos_trace p')
            ->where($map)
            ->count();
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        
        foreach ($data as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        
        $show = $Page->show(); // 分页显示输出
        
        $list = M()->table('tpos_trace p')
            ->field('p.*,n.node_name')
            ->join('tnode_info n ON p.node_id=n.node_id')
            ->where($map)
            ->order('id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        $batch_arr = array();
        $batch = M('tgoods_info');
        if ($list) {
            foreach ($list as $v) {
                
                $batch_arr[$v['batch_no']] = $batch->where(
                    array(
                        'goods_id' => $v['goods_id']))->getField('goods_name');
            }
        }
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
        $this->assign('batch_arr', $batch_arr);
        $this->assign('status', $status);
        $this->assign('transType', $transType);
        $this->assign('list', $list);
        $this->assign('nodeList', $this->getNodeTree());
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
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
             $where . "  trans_type='1' AND STATUS='0' GROUP BY pos_id)  g
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
}