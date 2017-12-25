<?php

class ValueCardsAction extends BaseAction {

    public function _initialize() {
        parent::_initialize();
        // 验证是否开通储值卡服务
        if (! checkUserRights($this->nodeId, C('CARDS_CHARGE_ID'))) {
            $this->error('您还没有开通该卡券服务'); // 跳转到服务介绍页面
        }
    }

    public function index() {
        $cardModel = M('tstored_card');
        $name = I('cards_name', null, 'mysql_real_escape_string,trim');
        if (isset($name) && $name != '') {
            $map['card_desc'] = array(
                'like', 
                "%{$name}%");
        }
        // 处理特殊查询字段
        $beginDate = I('begin_date', null, 'mysql_real_escape_string,trim');
        if (! empty($beginDate)) {
            $map['add_time'] = array(
                'egt', 
                $beginDate . '000000');
        }
        $endDate = I('end_date', null, 'mysql_real_escape_string,trim');
        if (! empty($endDate)) {
            $map[' add_time'] = array(
                'elt', 
                $endDate . '235959');
        }
        $map['node_id'] = $this->nodeId;
        import("ORG.Util.Page");
        $count = $cardModel->where($map)->count();
        $p = new Page($count, 10);
        // 确保分页查询条件
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        $list = $cardModel->where($map)
            ->order('add_time DESC')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        // dump($list);
        // echo $cardModel->getLastSql();exit;
        // 获取发码量等数据
        if ($list) {
            foreach ($list as $k => $v) {
                $codeInfo = R('WangcaiPc/NumGoods/getBatchCodeNum', 
                    array(
                        $v['batch_no']));
                $list[$k] = array_merge($list[$k], $codeInfo);
            }
        }
        // dump($list);
        // 分页显示
        $page = $p->show();
        $this->assign('list', $list);
        $this->assign('post', $_REQUEST);
        $this->assign("page", $page);
        $this->assign('empty', '<tr><td colspan="8">无数据</td></span>');
        $this->display();
    }

    function cardsAdd() {
        if ($this->isPost()) {
            $error = '';
            // 数据验证
            
            $name = I('post.cards_name');
            if (! check_str($name, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '10'), $error)) {
                $this->error("名称{$error}");
            }
            $sendBeginTime = I('post.send_begin_date');
            if (! check_str($sendBeginTime, 
                array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd'), $error)) {
                $this->error("发行日期开始时间{$error}");
            }
            $sendBeginTime .= '000000';
            $sendEndTime = I('post.send_end_date');
            if (! check_str($sendEndTime, 
                array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd'), $error)) {
                $this->error("发行日期结束时间{$error}");
            }
            if ($sendEndTime < date('Ymd')) {
                $this->error('发行日期结束时间不能小于当前日期');
            }
            $sendEndTime .= '235959';
            if (strtotime($sendEndTime) < strtotime($sendBeginTime)) {
                $this->error('发行日期结束时间不能小于发行日期开始时间');
            }
            // 验码开始时间验证
            $verifyBeginType = I('post.verify_begin_type');
            switch ($verifyBeginType) {
                case '0':
                    $verifyBeginDate = I('post.verify_begin_date');
                    if (! check_str($verifyBeginDate, 
                        array(
                            'null' => false, 
                            'strtype' => 'datetime', 
                            'format' => 'Ymd'), $error)) {
                        $this->error("使用日期开始时间{$error}");
                    }
                    $verifyBeginDate .= '000000';
                    break;
                case '1':
                    $verifyBeginDate = I('post.verify_begin_days');
                    if (! check_str($verifyBeginDate, 
                        array(
                            'null' => false, 
                            'strtype' => 'int', 
                            'minval' => '1'), $error)) {
                        $this->error("验码开始时间天数{$error}");
                    }
                    break;
                default:
                    $this->error('请填写使用日期信息');
            }
            // 验码结束时间验证
            $verifyEndType = I('post.verify_end_type');
            switch ($verifyEndType) {
                case '0':
                    $verifyEndDate = I('post.verify_end_date');
                    if (! check_str($verifyEndDate, 
                        array(
                            'null' => false, 
                            'strtype' => 'datetime', 
                            'format' => 'Ymd'), $error)) {
                        $this->error("使用日期结束时间{$error}");
                    }
                    $verifyEndDate .= '235959';
                    // 验码结束日期要大于发码结束日期
                    if (substr($verifyEndDate, 0, 8) < substr($sendEndTime, 0, 
                        8)) {
                        $this->error('使用日期结束时间要大于发行日期结束时间');
                    }
                    // 验码开始时间和结束时间验证
                    switch ($verifyBeginType) {
                        case '0': // 结束日期不能大于开始日期
                            if ($verifyEndDate < $verifyBeginDate) {
                                $this->error('使用日期结束时间不能小于使用日期开始时间');
                            }
                            break;
                        case '1':
                            // 验码开始时间=今天+验码开始的天数
                            if (date('Ymd000000', 
                                time() + $verifyBeginDate * 24 * 3600) >
                                 $verifyBeginDate) {
                                $this->error('验码开始时间的天数不能大于验码结束时间');
                            }
                    }
                    break;
                case '1':
                    $verifyEndDate = I('post.verify_end_days');
                    if (! check_str($verifyEndDate, 
                        array(
                            'null' => false, 
                            'strtype' => 'int', 
                            'minval' => '1'), $error)) {
                        $this->error("验码结束时间天数{$error}");
                    }
                    // 验码结束日期要大于发码结束日期
                    if (date('Ymd', time() + $verifyEndDate * 24 * 3600) <
                         substr($sendEndTime, 0, 8)) {
                        $this->error('使用日期结束时间要大于发行日期结束时间');
                    }
                    // 验码开始时间和结束时间验证
                    switch ($verifyBeginType) {
                        case '0':
                            // 验码结束时间=今天+验码结束的天数
                            if (date('Ymd235959', 
                                time() + $verifyEndDate * 24 * 3600) <
                                 $verifyBeginDate) {
                                $this->error('验码结束时间的天数不能小于验码开始时间');
                            }
                            break;
                        case '1':
                            if ($verifyEndDate < $verifyBeginDate) {
                                $this->error('验码结束时间的天数不能小于验码开始时间的天数');
                            }
                    }
                    break;
                default:
                    $this->error('请填写使用日期结束时间信息');
            }
            
            $price = I('post.card_amt');
            if (! check_str($price, 
                array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'minval' => '10', 
                    'maxval' => '2000'), $error)) {
                $this->error("储值卡面值{$error}");
            }
            $joinRule = I('post.join_rule');
            if (! check_str($joinRule, 
                array(
                    'null' => false), $error)) {
                $this->error("使用规则{$error}");
            }
            $mmsTitle = I('post.mms_title');
            if (! check_str($mmsTitle, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '10'), $error)) {
                $this->error("彩信标题{$error}");
            }
            $usingRules = I('post.using_rules');
            if (! check_str($usingRules, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '100'), $error)) {
                $this->error("彩信内容{$error}");
            }
            // 计算活动开始时间和结束时间
            $beginTime = $sendBeginTime;
            switch ($verifyEndType) {
                case '0': // 类型为日期：活动结束时间=发码结束时间和验码结束时间两者之间的最大值
                    strtotime($sendEndTime) > strtotime($verifyEndDate) ? $endTime = $sendEndTime : $endTime = $verifyEndDate;
                    break;
                case '1': // 类型为天数：活动结束时间=发码开始时间+验码结束时间的天数
                    $endTime = date('Ymd235959', 
                        strtotime($sendEndTime) + ($verifyEndDate * 24 * 3600));
            }
            if ($endTime < date('Ymd000000')) {
                $this->error('截至日期不能小于当前日期');
            }
            // 通知支撑
            $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
                                                                       // 请求参数
            $req_array = array(
                'ActivityCreateReq' => array(
                    'SystemID' => C('ISS_SYSTEM_ID'), 
                    'ISSPID' => $this->nodeId, 
                    'TransactionID' => $TransactionID, 
                    'ActivityInfo' => array(
                        'CustomNo' => '', 
                        'ActivityName' => iconv("utf-8", "gbk", $name), 
                        'ActivityShortName' => iconv("utf-8", "gbk", $name), 
                        'BeginTime' => $beginTime, 
                        'EndTime' => $endTime), 
                    'VerifyMode' => array(
                        'UseTimesLimit' => 0, 
                        'UseAmtLimit' => 1), 
                    'GoodsInfo' => array(
                        'GoodsName' => iconv("utf-8", "gbk", $name), 
                        'GoodsShortName' => iconv("utf-8", "gbk", $name)), 
                    'DefaultParam' => array(
                        'PasswordTryTimes' => 3, 
                        'PasswordType' => '', 
                        'ServiceType' => '02')));
            $RemoteRequest = D('RemoteRequest', 'Service');
            $resp_array = $RemoteRequest->requestIssServ($req_array);
            // dump($resp_array);exit;
            $ret_msg = $resp_array['ActivityCreateRes']['Status'];
            if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
                 $ret_msg['StatusCode'] != '0001')) {
                $this->error("添加失败:{$ret_msg['StatusText']}");
            }
            // 本地数据添加(tbatch_info数据添加)
            $data = array(
                'batch_no' => $resp_array['ActivityCreateRes']['ActivityID'], 
                'node_id' => $this->nodeId, 
                'user_id' => $this->userId, 
                'batch_class' => '3', 
                'batch_short_name' => $name, 
                'batch_name' => $name, 
                'begin_time' => $beginTime, 
                'end_time' => $endTime, 
                'info_title' => $mmsTitle, 
                'join_rule' => $joinRule, 
                'use_rule' => $usingRules, 
                'batch_amt' => $price, 
                'send_begin_date' => $sendBeginTime, 
                'send_end_date' => $sendEndTime, 
                'verify_begin_date' => $verifyBeginDate, 
                'verify_end_date' => $verifyEndDate, 
                'verify_begin_type' => $verifyBeginType, 
                'verify_end_type' => $verifyEndType, 
                'add_time' => date('YmdHis'));
            $batchModel = M('tbatch_info');
            $batchModel->startTrans();
            $result = $batchModel->data($data)->add();
            if (! $result) {
                Log::write('储值卡添加：tbatch_info表数据插入失败');
                $batchModel->rollback();
                $this->error('添加失败');
            }
            // tstored_card数据插入
            $data = array(
                'batch_no' => $resp_array['ActivityCreateRes']['ActivityID'], 
                'card_no' => uniqid(), 
                'node_id' => $this->nodeId, 
                'card_desc' => $name, 
                'begin_time' => $beginTime, 
                'end_time' => $endTime, 
                'card_amt' => $price, 
                'add_time' => date('YmdHis'));
            $resutl = M('tstored_card')->data($data)->add();
            if (! $result) {
                Log::write('储值卡添加：tstored_card表数据插入失败');
                $batchModel->rollback();
                $this->error('添加失败');
            } else {
                $batchModel->commit();
                $this->success('添加成功', 
                    array(
                        '返回储值卡列表' => U('ValueCards/index')));
            }
            exit();
        }
        $this->display();
    }
    
    // 储值卡详情
    public function cardsDetail() {
        $batchNo = I('get.batch_no', null, 'mysql_real_escape_string');
        if (empty($batchNo))
            $this->error('参数错误');
        $dataInfo = M('tbatch_info')->where(
            "node_id IN({$this->nodeIn()}) AND batch_no='{$batchNo}' AND batch_class=3")->find();
        // dump($dataInfo);exit;
        if (! $dataInfo)
            $this->error('未找到该购物卡信息');
            // 获取发卡量,累计消费金额等数据
        $sendInfo = M('tpos_day_count')->field(
            'SUM(send_num) as send_num,SUM(send_amt) as send_amt,SUM(verify_num) as verify_num,SUM(verify_amt) as verify_amt,SUM(cancel_num) as cancel_num,SUM(cancel_amt) as cancel_amt')
            ->where("batch_no='{$batchNo}'")
            ->find();
        
        $dataInfo = array_merge($dataInfo, $sendInfo);
        // dump($dataInfo);
        $this->assign('dataInfo', $dataInfo);
        $this->display();
    }
    
    // 日消费
    public function daysConsume() {
        $batchNo = I('get.batch_no', null, 'mysql_real_escape_string');
        if (empty($batchNo))
            $this->error('参数错误');
        $map = array(
            'node_id' => $this->nodeId, 
            'batch_no' => $batchNo);
        $cardsInfo = M('tbatch_info')->where(
            "node_id IN({$this->nodeIn()}) AND batch_no='{$batchNo}' AND batch_class=3")->find();
        if (! $cardsInfo)
            $this->error('未找到该购物卡信息');
        $beginDate = I('begin_date', null, 'mysql_real_escape_string,trim');
        if (! empty($beginDate)) {
            $map['trans_date'] = array(
                'egt', 
                dateformat($beginDate, 'Y-m-d'));
        }
        $endDate = I('end_date', null, 'mysql_real_escape_string,trim');
        if (! empty($endDate)) {
            $map['trans_date '] = array(
                'elt', 
                dateformat($endDate, 'Y-m-d'));
        }
        $map['verify_num'] = array(
            'gt', 
            '0');
        import("ORG.Util.Page");
        $count = M('tpos_day_count')->where("batch_no='{$batchNo}'")->count(
            'DISTINCT trans_date');
        $p = new Page($count, 10);
        // 确保分页查询条件
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        $dataList = M('tpos_day_count')->field(
            'trans_date,SUM(verify_num) as verify_num,SUM(verify_amt) as verify_amt')
            ->where("batch_no='{$batchNo}'")
            ->group('trans_date')
            ->order('trans_date DESC')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        // echo M()->getLastSql();
        // 图表显示
        if ($dataList) {
            $beginDate = I('begin_date', null, 'mysql_real_escape_string');
            $endDate = I('end_date', null, 'mysql_real_escape_string');
            if (empty($beginDate)) {
                // 获取该活动的验码开始日期
                $beginDate = M('tpos_day_count')->where(
                    "batch_no='{$batchNo}' AND verify_num>0")
                    ->order('trans_date ASC')
                    ->limit(1)
                    ->getField('trans_date');
            }
            if (empty($endDate)) {
                // 获取该活动的验码开始日期
                $endDate = M('tpos_day_count')->where(
                    "batch_no='{$batchNo}' AND verify_num>0")
                    ->order('trans_date DESC')
                    ->limit(1)
                    ->getField('trans_date');
            }
            if ($endDate < $beginDate)
                $this->error('结束日期不能大于开始日期');
            $nodeDate = R('WangcaiPc/NumGoods/formatDateNode', 
                array(
                    $beginDate, 
                    $endDate, 
                    15));
            $verifyNumArr = array();
            $verifyAmtArr = array();
            // 计算各时间节点的验码量、验码金额
            foreach ($nodeDate as $k => $v) {
                // 第一天的发码量从零开始
                $where = array(
                    'batch_no' => $batchNo, 
                    'trans_date ' => $v);
                $dataInfo = M('tpos_day_count')->field(
                    'SUM(verify_num) as verify_num,SUM(verify_amt) as verify_amt')
                    ->where($where)
                    ->group('trans_date')
                    ->find();
                if ($dataInfo) {
                    $verifyNumArr[] = $dataInfo['verify_num'];
                    $verifyAmtArr[] = $dataInfo['verify_amt'];
                } else {
                    $verifyNumArr[] = '0';
                    $verifyAmtArr[] = '0.00';
                }
            }
            $nodeDateStr = "'" . implode("','", $nodeDate) . "'";
            $verifyNumStr = implode(',', $verifyNumArr);
            $verifyAmtArr = implode(',', $verifyAmtArr);
            $this->assign('nodeDateStr', $nodeDateStr);
            $this->assign('verifyNumStr', $verifyNumStr);
            $this->assign('verifyAmtArr', $verifyAmtArr);
        }
        
        // 分页显示
        $page = $p->show();
        $this->assign('dataList', $dataList);
        $this->assign('cardsInfo', $cardsInfo);
        $this->assign('beginDate', $nodeDate[0]);
        $this->assign('endDate', $nodeDate[count($nodeDate) - 1]);
        $this->assign('post', $_REQUEST);
        $this->assign("page", $page);
        $this->assign('empty', '<tr><td colspan="3">无数据</td></span>');
        $this->display();
    }
    
    // 查看验证明细
    public function verifyDetail() {
        $batchNo = I('get.batch_no', null, 'mysql_real_escape_string');
        if (empty($batchNo))
            $this->error('参数错误');
        $cardsInfo = M('tbatch_info')->where(
            "node_id IN({$this->nodeIn()}) AND batch_no='{$batchNo}' AND batch_class=3")->find();
        if (! $cardsInfo)
            $this->error('未找到该购物卡信息');
        $where = array(
            'batch_no' => $batchNo, 
            'trans_type' => array(
                'in', 
                '0,1'), 
            'status' => '0');
        import("ORG.Util.Page");
        $count = M('tpos_trace')->where($where)->count();
        $p = new Page($count, 10);
        // 确保分页查询条件
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        $dataList = M('tpos_trace')->field(
            'trans_time,phone_no,exchange_amt,trans_type,status')
            ->where($where)
            ->order('trans_time DESC')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        // 分页显示
        $page = $p->show();
        $transType = array(
            '验证', 
            '撤消');
        $status = array(
            '成功', 
            '失败', 
            '冲正');
        $this->assign('dataList', $dataList);
        $this->assign('cardsInfo', $cardsInfo);
        $this->assign("transType", $transType);
        $this->assign("status", $status);
        $this->assign("page", $page);
        $this->assign('empty', '<tr><td colspan="5">无数据</td></span>');
        $this->display();
    }
    
    // 发码流水记录记录
    public function sendCodeDetail() {
        $batchNo = I('get.batch_no', null, 'mysql_real_escape_string');
        if (empty($batchNo))
            $this->error('参数错误');
        $cardsInfo = M('tbatch_info')->where(
            "node_id IN({$this->nodeIn()}) AND batch_no='{$batchNo}' AND batch_class=3")->find();
        if (! $cardsInfo)
            $this->error('未找到该购物卡信息');
        $where = array(
            'batch_no' => $batchNo, 
            'data_from' => '6');
        $barcodeModel = M('tbarcode_trace');
        import("ORG.Util.Page");
        $count = $barcodeModel->where($where)->count();
        $p = new Page($count, 10);
        // 确保分页查询条件
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        
        $dataList = $barcodeModel->field(
            'id,phone_no,trans_time,trans_type,status')
            ->where($where)
            ->order('trans_type ASC,status ASC,trans_time DESC')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        // echo $barcodeModel->getLastSql();exit;
        // 分页显示
        $page = $p->show();
        $status = array(
            '0' => '成功', 
            '1' => '已撤销', 
            '3' => '失败');
        $transType = array(
            '0001' => '发码', 
            '0002' => '撤销', 
            '0003' => '重发');
        $this->assign('dataList', $dataList);
        $this->assign('cardsInfo', $cardsInfo);
        $this->assign('status', $status);
        $this->assign('transType', $transType);
        $this->assign("page", $page);
        $this->assign('empty', '<tr><td colspan="5">无数据</td></span>');
        $this->display();
    }
    
    // 发码批量查询
    public function sendBatch() {
        $batchNo = I('batch_no');
        if (empty($batchNo))
            $this->error('参数错误');
        $timportModel = M('tbatch_import');
        $cardsInfo = M('tbatch_info')->where(
            "node_id IN({$this->nodeIn()}) AND batch_no='{$batchNo}' AND batch_class=3")->find();
        if (! $cardsInfo)
            $this->error('未找到该购物卡信息');
        $where = array(
            'batch_no' => $batchNo, 
            'data_from' => '6');
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = $timportModel->where($where)->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        
        $show = $Page->show(); // 分页显示输出
        
        $list = $timportModel->where($where)
            ->order('add_time desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        $status = array(
            '0' => '未处理', 
            '1' => '处理中', 
            '2' => '已处理', 
            '3' => '全处理', 
            '9' => '处理失败');
        
        $this->assign('cardsInfo', $cardsInfo);
        $this->assign('status', $status);
        $this->assign('list', $list);
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }
}