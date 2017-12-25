<?php

class AiPaiAction extends BaseAction {

    public function _initialize() {
        parent::_initialize();
        $this->error('爱拍赢大奖营销活动已停用！');
    }

    public function index() {
        $check_statusArr = array(
            "0" => "正常", 
            "1" => "停用");
        $model = D('tpp_batch');
        $wh_arr['node_id'] = array(
            'exp', 
            "in ({$this->nodeIn()})");
        import("ORG.Util.Page"); // 导入分页类
        $count = $model->where($wh_arr)->count();
        $p = new Page($count, 10);
        // 确保分页查询条件
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        $list = $model->where($wh_arr)
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        // 分页显示
        $page = $p->show();
        node_log("首页+爱拍赢大奖");
        $this->assign('list', $list);
        $this->assign("page", $page);
        $this->display();
    }

    public function prize_add_back() {
        $nodeInfo = M('tnode_info')->where("node_id='{$this->nodeId}'")->find();
        if (! $nodeInfo) {
            $this->error('未找到该商户信息');
        }
        $storeInfo = M('tstore_info')->where("node_id='{$this->nodeId}'")->find();
        if (! $nodeInfo) {
            $this->display(APP_PATH . '/Tpl/Home/Store_index.html');
            die();
        }
        $action = I('get.action');
        $actions = I('get.aipai');
        if ($action == 'add' && $actions == '') {
            if (($nodeInfo['check_status'] == '0' ||
                 $nodeInfo['check_status'] == '1') &&
                 $nodeInfo['node_type'] == '2') {
                $this->assign("node_service_hotline", 
                    $nodeInfo['node_service_hotline']);
                $this->assign("node_short_name", $nodeInfo['node_short_name']);
                $this->assign("check_status", $nodeInfo['check_status']);
                $this->display("node");
                die();
            } else {
                $wc_batch_no = $_POST['wc_batch_no'];
                if ($wc_batch_no[0] == '') {
                    $this->error("参数错误");
                }
                $node = M('tpp_batch')->where('node_id = ' . $this->nodeId)->find();
                $tranDb = new Model();
                $tranDb->startTrans();
                foreach ($wc_batch_no as $key => $val) {
                    $limit_inventory_flag = $_POST['limit_inventory_flag' . $val];
                    if ($limit_inventory_flag == '0') {
                        $total_inventory = '0';
                    } else {
                        $total_inventory = $_POST['total_inventory'][$key];
                    }
                    $limit_per_day_flag = $_POST['limit_per_day_flag' . $val];
                    if ($limit_per_day_flag == '0') {
                        $per_day_send_amount = '0';
                    } else {
                        $per_day_send_amount = $_POST['per_day_send_amount'][$key];
                    }
                    $limit_single_phone_flag = $_POST['limit_single_phone_flag' .
                         $val];
                    if ($limit_single_phone_flag == '0') {
                        $single_phone_send_amount = '0';
                    } else {
                        $single_phone_send_amount = $_POST['single_phone_send_amount'][$key];
                    }
                    $data = array(
                        'limit_inventory_flag' => $limit_inventory_flag, 
                        'total_inventory' => $total_inventory, 
                        'remain_inventory' => $total_inventory, 
                        'limit_per_day_flag' => $limit_per_day_flag, 
                        'per_day_send_amount' => $per_day_send_amount, 
                        'limit_single_phone_flag' => $limit_single_phone_flag, 
                        'single_phone_send_amount' => $single_phone_send_amount);
                    $r = M('tpp_prize_info')->where(' id = ' . $val)->save(
                        $data);
                    if ($r === false) {
                        $tranDb->rollback();
                        $this->error('新增卡券失败！');
                    }
                    $datas = array(
                        'batch_id' => $node['id'], 
                        'prize_id' => $val);
                    $rs = M('tpp_batch_prize')->add($datas);
                    if ($rs === false) {
                        $tranDb->rollback();
                        $this->error('新增卡券时绑定爱拍渠道失败！');
                    }
                }
                node_log("卡券基础配置，活动号：" . $wc_batch_no[0]);
                $tranDb->commit();
                $this->success("添加成功！新增卡券将会在一个工作日内审核完成并提交上线。", 
                    array(
                        '返回爱拍赢大奖' => U('index'), 
                        '发布渠道' => U('publish_activity')));
            }
        }
        if ($actions == 'node') {
            $node_short_name = strip_tags(trim($_POST['node_short_name']));
            $resp_log_img = strip_tags(trim($_POST['resp_log_img']));
            if ($node_short_name == '' || $resp_log_img == '') {
                $this->error('参数错误');
            }
            $node_service_hotline = strip_tags(
                trim($_POST['node_service_hotline']));
            $data = array(
                "node_short_name" => $node_short_name, 
                "check_status" => '3', 
                "node_license_img" => $resp_log_img, 
                "node_service_hotline" => $node_service_hotline);
            
            M('tnode_info')->where('node_id = ' . $this->nodeId)->save($data);
            node_log("爱拍活动配置卡券时补填商户营业执照");
            $message = "补填商户营业执照成功！请点击确定继续新增卡券";
            $this->assign("message", $message);
            $this->display(APP_PATH . '/Tpl/Public/Public_msgArtdialog.html');
            exit();
        }
        $modedl = M('tpp_batch_prize');
        import("ORG.Util.Page"); // 导入分页类
        $sqls = "SELECT count(*) as coun
				FROM tbatch_info a, tpp_prize_info b 
				WHERE (a.batch_class = '5' or a.batch_type = '2') AND a.id = b.id and a.node_id = '{$this->nodeId}'
				and a.check_status = '1'
				AND ( 
				EXISTS (SELECT 1 FROM tpp_batch_prize c WHERE c.prize_id = a.id) 
				OR 
				b.check_status = '1' 
				)";
        $count = $modedl->query($sqls); // 查询满足要求的总记录数
        $count = $count[0]['coun'];
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $sql = "SELECT a.batch_short_name,a.begin_time,a.end_time,a.batch_no,b.limit_inventory_flag,b.total_inventory,b.limit_per_day_flag,b.per_day_send_amount,b.limit_single_phone_flag,b.single_phone_send_amount,b.check_refuse_reason,b.check_status 
				FROM tbatch_info a, tpp_prize_info b 
				WHERE (a.batch_class = '5' or a.batch_type = '2') AND a.id = b.id and a.node_id = '{$this->nodeId}'
				and a.check_status = '1'
				AND EXISTS (SELECT 1 FROM tpp_batch_prize c WHERE c.prize_id = a.id)
				limit " . $Page->firstRow . "," .
             $Page->listRows;
        $list = $modedl->query($sql);
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        /*
         * $list =
         * $modedl->field('tbatch_info.id,check_status')->join('tpp_batch on
         * tpp_batch_prize.batch_id = tpp_batch.id') ->join('tbatch_info on
         * tpp_batch_prize.prize_id = tbatch_info.id') ->join('tpp_prize_info on
         * tbatch_info.id = tpp_prize_info.id')->union("select id, check_status
         * from tpp_prize_info") ->where('tpp_batch.node_id =
         * '.$this->nodeId)->limit ( $Page->firstRow . ',' . $Page->listRows
         * )->select(); //echo $modedl->getlastsql();
         */
        $this->assign('list', $list);
        $this->assign('page', $show); // 赋值分页输出
        $check_statusArr = array(
            "0" => "审核中", 
            "1" => "审核拒绝", 
            "2" => "审核通过");
        $this->assign('check_statusArr', $check_statusArr);
        $store_info = M('tstore_info')->where("node_id='{$this->nodeId}'")->find();
        $this->assign('store_info', $store_info);
        $this->display();
    }

    public function sendrecord() {
        $use_statusArr = array(
            "0" => "未使用", 
            "1" => "使用中", 
            "2" => "已使用");
        
        $model = D('tbarcode_trace');
        import("ORG.Util.Page"); // 导入分页类
        $wh_arr['_string'] = ' 1=1 ';
        
        $wh_arr['tbarcode_trace.node_id'] = $this->nodeId;
        
        // 卡券名称
        $prize_name = $this->_post('prize_name', 'trim,strip_tags');
        if ($prize_name != '')
            $wh_arr['_string'] .= " and batch_short_name like '%" . $prize_name .
                 "%'";
            
            // 手机号
        $recv_phone = $this->_post('recv_phone', 'trim,strip_tags');
        if ($recv_phone != '')
            $wh_arr['phone_no'] = $recv_phone;
            
            // 发码时间
        $send_begin_time = $this->_post('send_begin_time', 
            'date_clean,trim,strip_tags');
        if ($send_begin_time != '') {
            $wh_arr['_string'] .= " and trans_time >= '{$send_begin_time}000000'";
        }
        $send_end_time = $this->_post('send_end_time', 
            'date_clean,trim,strip_tags');
        if ($send_end_time != '') {
            $wh_arr['_string'] .= " and trans_time <= '{$send_end_time}235959'";
        }
        
        // 验证状态
        $use_status = $this->_post('use_status', 'trim');
        if ($use_status != '')
            $wh_arr['use_status'] = $use_status;
        $wh_arr['tbarcode_trace.prize_source'] = '4';
        $wh_arr['tbarcode_trace.data_from'] = '9';
        $wh_arr['tbarcode_trace.status'] = array(
            'in', 
            '0,1');
        $count = $model->join(
            'tbatch_info on tbatch_info.batch_no=tbarcode_trace.batch_no')
            ->join(
            'tpp_channel_info on tpp_channel_info.channel_id=tbarcode_trace.channel_id')
            ->where($wh_arr)
            ->count(); // 查询满足要求的总记录数
        $Page = new Page($count, 8); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
                               
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $list = $model->join(
            'tbatch_info on tbatch_info.batch_no=tbarcode_trace.batch_no')
            ->join(
            'tpp_channel_info on tpp_channel_info.channel_id=tbarcode_trace.channel_id')
            ->where($wh_arr)
            ->order('trans_time desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        $this->assign('list', $list);
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('use_status', $use_statusArr);
        $this->display();
    }

    function stopgoods() {
        $batchNo = $this->_post('batch_no');
        if (empty($batchNo))
            $this->error('参数错误');
            // 获取活动信息
        $activityInfo = M('tbatch_info')->where("batch_no='{$batchNo}' ")->find();
        if (! $activityInfo) {
            $this->error('未找到该卡券信息');
        }
        // 删除本地绑定关系
        $goodsModel = M('tpp_batch_prize');
        $result = $goodsModel->where(" prize_id='{$activityInfo['id']}'")->delete();
        if (! $result) {
            $this->error("系统错误，停用失败");
        }
        node_log("停用爱拍卡券，活动号：" . $batchNo);
        $this->success('停用成功');
    }

    /*
     * function startgoods(){ $batchNo = $this->_post('batch_no');
     * if(empty($batchNo)) $this->error('参数错误'); //获取活动信息 $activityInfo =
     * M('tbatch_info')->where("id=".$batchNo)->find(); if(!$activityInfo){
     * $this->error('未找到该卡券信息'); } if($activityInfo['status'] !=1)
     * $this->error('该卡券已经开启或已经过期'); //更新本地状态 $goodsModel = M('tbatch_info');
     * $data = array( 'status' => '0' ); $result =
     * $goodsModel->where("id=".$batchNo)->save($data); if(!$result){
     * $this->error("系统错误，开启失败"); } $this->success('开启成功'); }
     */
    function prize_down_report() {
        $use_statusArr = array(
            "0" => "未使用", 
            "1" => "使用中", 
            "2" => "已使用");
        $shop_id = $this->nodeId;
        $where = " WHERE 1=1 ";
        // 卡券名称
        $prize_name = $this->_post('prize_name', 'trim,strip_tags');
        if ($prize_name != "") {
            $where .= " and tbatch_info.batch_short_name like '%" . $prize_name .
                 "%'";
        }
        if ($shop_id != "")
            $where .= " and tbarcode_trace.node_id ='" . $shop_id . "'";
            // 手机号
        $recv_phone = $this->_post('recv_phone', 'trim,strip_tags');
        if ($recv_phone != "") {
            $where .= " and tbarcode_trace.phone_no ='" . $recv_phone . "'";
        }
        
        // 发码时间
        $send_begin_time = $this->_post('send_begin_time', 
            'date_clean,trim,strip_tags');
        if ($send_begin_time != "") {
            $where .= " and tbarcode_trace.trans_time >='" . $send_begin_time .
                 "000000'";
        }
        
        $send_end_time = $this->_post('send_end_time', 
            'date_clean,trim,strip_tags');
        if ($send_end_time != "") {
            $where .= " and tbarcode_trace.trans_time <='" . $send_end_time .
                 "235959'";
        }
        
        // 验证状态
        $use_status = $this->_post('use_status', 'trim');
        if ($use_status != "") {
            $where .= " and tbarcode_trace.use_status ='" . $use_status . "'";
        }
        
        $where .= " and tbarcode_trace.prize_source ='4'";
        $where .= " and tbarcode_trace.data_from ='9'";
        $mysql = M('tbarcode_trace');
        import('@.ORG.Net.querydata');
        $class = new QueryData();
        
        $cols_arr = array(
            'batch_short_name' => '卡券名称', 
            'end_time' => array(
                'name' => '结束日期', 
                'callback' => array(
                    'dateformat', 
                    array(
                        '_VALUE_', 
                        'Y-m-d H:i:s'))), 
            'channel_name' => '渠道名称', 
            'phone_no' => '接收手机号', 
            'use_status' => array(
                'name' => '验证状态', 
                'callback' => array(
                    'getArr_value', 
                    array(
                        $use_statusArr, 
                        '_VALUE_'))), 
            'trans_time' => array(
                'name' => '发码日期', 
                'callback' => array(
                    'dateformat', 
                    array(
                        '_VALUE_', 
                        'Y-m-d H:i:s'))), 
            'use_time' => array(
                'name' => '验证时间', 
                'callback' => array(
                    'dateformat', 
                    array(
                        '_VALUE_', 
                        'Y-m-d H:i:s'))));
        $sql = "SELECT tbatch_info.batch_short_name,tbarcode_trace.end_time,tpp_channel_info.channel_name,tbarcode_trace.phone_no,tbarcode_trace.trans_time,tbarcode_trace.use_status,  tbarcode_trace.use_time FROM tbarcode_trace 
		LEFT JOIN tbatch_info on tbatch_info.batch_no=tbarcode_trace.batch_no 
		LEFT JOIN tpp_channel_info on tpp_channel_info.channel_id=tbarcode_trace.channel_id " .
             $where . " ORDER BY trans_time DESC ";
        
        $class->downloadData($sql, $cols_arr, $mysql);
        if ($class->downloadData($sql, $cols_arr, $mysql) == false) {
            Log::write('数据导出失败');
            $this->error('导出失败！');
        }
        node_log("爱拍活动出奖管理数据导出");
        exit();
    }

    function send_reporter() {
        $nodeId = I('node_id', null, 'mysql_real_escape_string');
        if (empty($nodeId))
            $this->error('参数错误');
        $shop_id = $nodeId;
        $model = D('tbatch_info');
        $where = " WHERE 1=1 ";
        $where1 = "";
        $where2 = "";
        if ($shop_id != "")
            $where .= " and p.node_id ='" . $shop_id .
                 "' and p.node_id IN({$this->nodeIn()})";
            // 发码时间
        $start_time = $this->_post('start_time', 'date_clean,trim,strip_tags');
        empty($start_time) ? $start_time = date('Ym01', time()) : $start_time; // 为空获得本月第一天日期
        if ($start_time != "") {
            $where .= " and DATE_FORMAT(p.trans_date,'%Y%m%d' ) >='" .
                 $start_time . "'";
        }
        $end_time = $this->_post('end_time', 'date_clean,trim,strip_tags');
        empty($end_time) ? $end_time = date('Ymt', time()) : $end_time; // 为空获得本月最后一天日期
        if ($end_time != "") {
            $where .= " and DATE_FORMAT(p.trans_date,'%Y%m%d' ) <='" . $end_time .
                 "'";
        }
        $where .= " and (b.batch_class='5' or b.batch_type = '2')";
        // 发码
        $sql = "select sum(send_num) as newcount,trans_date  as stime from tpos_day_count as p join tbatch_info as b on p.batch_no = b.batch_no  " .
             $where . "  group by DATE_FORMAT(trans_date,'%Y%m%d')";
        $sendData = $model->query($sql);
        $sendData = array_valtokey($sendData, 'stime', 'newcount');
        // 验码
        $sql = "select sum(verify_num) as newcount,trans_date as stime from tpos_day_count as p join tbatch_info as b on p.batch_no = b.batch_no where " .
             $where . "  group by DATE_FORMAT(trans_date,'%Y%m%d')";
        $verifyData = $model->query($sql);
        $verifyData = array_valtokey($verifyData, 'stime', 'newcount');
        $num = DateDiff($end_time, $start_time, 'd');
        $fortime = $start_time;
        $d_arr = array();
        $s_arr = array();
        $v_arr = array();
        for ($i = 0; $i <= $num; $i ++) {
            $day = date("Y-m-d", strtotime("+{$i} day", strtotime($fortime)));
            $d_arr[] = $day;
            $s_arr[] = isset($sendData[$day]) ? $sendData[$day] : 0;
            $v_arr[] = isset($verifyData[$day]) ? $verifyData[$day] : 0;
        }
        
        $this->assign('start_time', date("Y-m-d", strtotime($start_time)));
        $this->assign('end_time', date("Y-m-d", strtotime($end_time)));
        $this->assign('date_str', "'" . implode("','", $d_arr) . "'");
        $this->assign('send_str', implode(',', $s_arr));
        $this->assign('verify_str', implode(',', $v_arr));
        $this->display();
    }

    function publish_activity() {
        $batch_type = '5';
        $channel_sns_type = array(
            "31" => "平面媒体", 
            "32" => "线下门店", 
            "33" => "电视媒体", 
            "34" => "其他", 
            "35" => "实体门店");
        $nodeId = $this->nodeId;
        $model = M('tpp_batch');
        $batch_name = $model->where('node_id = ' . $nodeId)->find();
        $this->assign('batch_name', $batch_name['name']);
        $models = M('tchannel');
        $channel = $models->where(' type = 3 and node_id = ' . $nodeId)
            ->GROUP('sns_type')
            ->select();
        $this->assign('channel', $channel);
        $batch_channel = M('tbatch_channel')->where(
            "batch_type = '5' and node_id = '{$nodeId}'")->select();
        $channel_info = $models->where(" type = '3' and node_id = '{$nodeId}'")->select();
        foreach ($channel_info as $key => $val) {
            foreach ($batch_channel as $keys => $vals) {
                if ($vals['channel_id'] == $val['id']) {
                    $channel_info[$key]['cd'] = 1;
                    break;
                } else {
                    $channel_info[$key]['cd'] = 0;
                }
            }
        }
        $this->assign('batch_type', $batch_type);
        $this->assign('batch_id', $batch_name['id']);
        $this->assign('channel_info', $channel_info);
        $this->assign('channel_sns_type', $channel_sns_type);
        $this->display();
    }

    function submitBind() {
        $channel = $_POST['channel'];
        if ($_POST['batch_type'] == '' || $_POST['batch_id'] == '')
            $this->error('错误参数！');
        $batch_type = $_POST['batch_type'];
        $batch_id = $_POST['batch_id'];
        $batch_arr = C('BATCH_TYPE');
        if (! $channel) {
            $this->error('请选择渠道');
        }
        // 活动
        $model = M('tpp_batch');
        $map = array(
            'node_id' => $this->nodeId, 
            'id' => $batch_id);
        $batch_id = $model->where($map)->getField('id');
        if (! $batch_id) {
            $this->error('未创建活动！');
        }
        $exec = M('tbatch_channel');
        // 开启事物
        $data = array();
        $tranDb = new Model();
        $tranDb->startTrans();
        // 绑定成功渠道id
        foreach ($channel as $k => $v) {
            $data = array(
                'batch_type' => $batch_type, 
                'batch_id' => $batch_id, 
                'channel_id' => $v, 
                'add_time' => date('YmdHis'), 
                'node_id' => $this->nodeId, 
                'status' => '1');
            
            $query = $exec->add($data);
            if (! $query) {
                $tranDb->rollback();
                $this->error('更新渠道失败！');
            } else {
                $dataa = array(
                    'batch_id' => $batch_id, 
                    'batch_type' => $batch_type);
                $querys = M('tchannel')->where(
                    array(
                        'node_id' => $this->nodeId, 
                        'id' => $v))->save($dataa);
                if ($querys === false) {
                    $tranDb->rollback();
                    $this->error('更新渠道失败！');
                }
            }
        }
        $tranDb->commit();
        node_log("爱拍营销活动绑定渠道");
        if ($channel) {
            // 显示页面
            $search_map['channel_id'] = array(
                'in', 
                $channel);
            $search_map['batch_id'] = $batch_id;
            $search_map['status'] = '1';
            $succ_arr = $exec->where($search_map)
                ->field('id,channel_id')
                ->select();
            // 渠道详情
            $m_model = M('tchannel');
            $m_map = array(
                'node_id' => $this->nodeId, 
                'id' => array(
                    'in', 
                    $channel));
            $m_arr = $m_model->where($m_map)->select();
            foreach ($m_arr as $k => $v) {
                $carr[$v['id']] = $v;
            }
        }
        $this->assign('succ_arr', $succ_arr);
        $this->assign('carr', $carr);
        $this->display('succMsg');
    }

    function batch_list() {
        $model = D('tbatch_info');
        import("ORG.Util.Page"); // 导入分页类
        $count = $model->join(
            "tpp_prize_info on tbatch_info.id = tpp_prize_info.id ")
            ->where(
            " tbatch_info.node_id='{$this->nodeId}' and (batch_class='5' or batch_type = '2') and (tpp_prize_info.check_status='0' or tpp_prize_info.check_status='2') and tbatch_info.id not in (select prize_id from tpp_batch_prize) ")
            ->count(); // 查询满足要求的总记录数
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
                               
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $list = $model->join(
            "tpp_prize_info on tbatch_info.id = tpp_prize_info.id ")
            ->where(
            " tbatch_info.node_id='{$this->nodeId}' and (batch_class='5' or batch_type = '2') and (tpp_prize_info.check_status='0' or tpp_prize_info.check_status='2') and tbatch_info.id not in (select prize_id from tpp_batch_prize) ")
            ->order("tbatch_info.add_time desc")
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        $this->assign('list', $list);
        $this->assign('page', $show); // 赋值分页输出
        $this->display();
    }

    function create_batch() {
        // 可验证门店数量
        $storeNum = M('tstore_info')->where(
            "node_id='{$this->nodeId}' AND status='0'")->count();
        // 用户类型
        $node_info = M('tnode_info')->field('node_name,node_service_hotline')
            ->where("node_id='{$this->nodeId}'")
            ->find();
        $this->assign('storeNum', $storeNum);
        $this->assign('node_info', $node_info);
        $this->display();
    }

    function business() {
        $node_img = $_GET['node_type'];
        import('ORG.Net.UploadFile');
        $upload = new UploadFile(); // 实例化上传类
        $upload->maxSize = 1024 * 1024;
        $upload->savePath = './Home/Upload/business/'; // 设置附件
        $upload->allowExts = array(
            'gif', 
            'jpg', 
            'jpeg', 
            'bmp', 
            'png');
        if (! $node_img) {
            $upload->supportMulti = false;
            $upload->thumb = true;
            $upload->thumbMaxWidth = 150;
            $upload->thumbMaxHeight = 150;
            $upload->thumbRemoveOrigin = true;
        }
        
        if (! $upload->upload()) { // 上传错误提示错误信息
            $this->errormsg = $upload->getErrorMsg();
        } else { // 上传成功 获取上传文件信息
            node_log("上传营业执照图片");
            $info = $upload->getUploadFileInfo();
            $this->imgurl = $info[0]['savename'];
        }
        $arr = array(
            'msg' => '0000',  // 通信是否成功
            'error' => $this->errormsg,  // 返回错误
            'imgurl' => $this->imgurl); // 返回图片名
        
        echo json_encode($arr);
        exit();
    }

    /**
     * 将2个日期间天数平均分成若干日期节点
     *
     * @param $startDate 开始日期
     * @param $endDate 结束日期
     * @param int $nodeCount 日期节点个数
     * @param string 返回数据的时间格式
     * @return array
     */
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

    function prize_node() {
        if ($_GET['va']) {
            $nodeInfo = M('tnode_info')->where("node_id='{$this->nodeId}'")->find();
            if (! $nodeInfo) {
                $this->error('未找到该商户信息');
            }
            if (($nodeInfo['check_status'] == '0' ||
                 $nodeInfo['check_status'] == '1') &&
                 $nodeInfo['node_type'] == '2') {
                echo 1;
                die();
            } else {
                echo 2;
                die();
            }
        }
    }

    function batch_regular() {
        $this->display();
    }
}