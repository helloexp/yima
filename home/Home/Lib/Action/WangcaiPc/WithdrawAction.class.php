<?php

class WithdrawAction extends BaseAction {

    /**
     * 在线提领列表
     */
    public function withdrawList() {
        $map = array();
        $where = '1=1';
        if ($_SESSION['userSessInfo']['node_id'] == C('withDraw.createNodeId')) {
            $where .= " AND (a.from_node_id = '" .
                 $_SESSION['userSessInfo']['node_id'] . "'";
            $where .= " OR a.delivery_node_id = '" .
                 $_SESSION['userSessInfo']['node_id'] . "')";
            $this->assign('type', 'imageco');
        } else if ($_SESSION['userSessInfo']['node_id'] ==
             C('withDraw.fromNodeId')) {
            $where .= " AND a.delivery_node_id = '" .
             $_SESSION['userSessInfo']['node_id'] . "'";
        $this->assign('type', 'sellRice');
    } else {
        $where .= " AND a.delivery_node_id = '" .
             $_SESSION['userSessInfo']['node_id'] . "'";
        $this->assign('type', 'normal');
    }
    
    if ($_POST['order_id'] != '') {
        $where .= " AND bt.assist_number = '" . $_POST['order_id'] . "'";
    }
    
    $orderStatus = I('post.order_status', '0', 'string');
    if ($orderStatus == '2') {
        $where .= " AND a.order_status = '2' ";
    } elseif ($orderStatus == '1') {
        $where .= " AND a.order_status in (0,1)";
    }
    
    $orderType = I('post.order_type', '0', 'string');
    if ($orderType == '1') {
        $where .= " AND gi.source = '0'";
    } elseif ($orderType == '2') {
        $where .= " AND gi.source = '5'";
    } elseif ($orderType == '3') {
        $where .= " AND gi.source = '1'";
    }
    
    if ($_POST['delivery_status'] != '') {
        $where .= " AND a.delivery_status = '" . $_POST['delivery_status'] . "'";
    }
    
    if ($_POST['start_time'] != '' && $_POST['end_time'] != '') {
        $startTime = $_POST['start_time'] . '000000';
        $endTime = $_POST['end_time'] . '235959';
        $where .= " AND a.add_time > '" . $startTime . "' AND a.add_time < '" .
             $endTime . "'";
    }
    
    if ($_POST['codeName'] != '') {
        $where .= " AND gi.goods_name like '%" . $_POST['codeName'] . "%'";
    }
    
    import('ORG.Util.Page');
    $mapcount = M()->table('tonline_get_order a')
        ->join('tbarcode_trace bt ON a.req_seq = bt.req_seq')
        ->join('tgoods_info gi ON bt.batch_no = gi.batch_no')
        ->field('a.*, gi.goods_name, bt.assist_number')
        ->where($where)
        ->count();
    
    $Page = new Page($mapcount, 10);
    $show = $Page->show();
    
    $orderList = M()->table('tonline_get_order a')
        ->join('tbarcode_trace bt ON a.req_seq = bt.req_seq')
        ->join('tgoods_info gi ON bt.batch_no = gi.batch_no')
        ->field('a.*, gi.goods_name, gi.source, bt.assist_number')
        ->where($where)
        ->order('add_time desc')
        ->limit($Page->firstRow . ',' . $Page->listRows)
        ->select();
    foreach ($orderList as $key => $val) {
        if (preg_match('/^7063/', $val['assist_number'])) {
            $orderList[$key]['code_type'] = 'wechat';
        }
    }
    $this->assign('orderList', $orderList);
    
    $texpressModel = D('TexpressInfo');
    $expressResult = $texpressModel->getLastUsedExpress();
    $this->assign('usedExpress', $expressResult['rescent']);
    $this->assign('expressStr', $expressResult['expressStr']);
    
    $this->assign('page', $show);
    
    if (! empty($_POST)) {
        $this->assign('postData', $_POST);
    }
    $this->assign('nodeId', $_SESSION['userSessInfo']['node_id']);
    $this->display();
}

/**
 * 更新提领物流
 */
public function updateCodeDelivery() {
    $orderId = I('post.dataOrder');
    $express = I('post.delivery_company');
    $expressNumber = I('post.delivery_number');
    $saveData = array();
    $saveData['delivery_number'] = $expressNumber;
    $saveData['delivery_company'] = $express;
    $saveData['delivery_date'] = date('YmdHis');
    $saveData['delivery_status'] = '2';
    $result = M('tonline_get_order')->where(
        array(
            'delivery_node_id' => $_SESSION['userSessInfo']['node_id'], 
            'id' => $orderId))->save($saveData);
    if ($result != false) {
        $this->success('更新成功');
    } else {
        $this->success('更新失败');
    }
}

/**
 * 提领详情
 */
public function withdrowDetail() {
    $orderId = I('get.order_id');
    $orderType = I('get.type');
    
    $where = "a.id = " . $orderId;
    if ($orderType == '5' &&
         $_SESSION['userSessInfo']['node_id'] == C('withDraw.createNodeId')) {
        $where .= " AND (a.node_id = '" . $_SESSION['userSessInfo']['node_id'] .
         "' OR a.from_node_id = '" . $_SESSION['userSessInfo']['node_id'] .
         "' OR a.delivery_node_id = '" . $_SESSION['userSessInfo']['node_id'] .
         "')";
} else {
    $where .= " AND a.delivery_node_id = '" .
         $_SESSION['userSessInfo']['node_id'] . "'";
}

$boughtInfo = M()->table('tonline_get_order a')
    ->join('tbarcode_trace bt ON a.req_seq = bt.req_seq')
    ->join('tgoods_info gi ON bt.goods_id = gi.goods_id')
    ->join('tcity_code cc ON cc.path = a.receiver_citycode')
    ->join('tnode_info ti ON gi.node_id = ti.node_id')
    ->field(
    'a.*, gi.goods_name, gi.source, gi.node_id as cg_node_id, gi.goods_id, bt.assist_number, bt.req_seq, bt.phone_no as withdraw_phone, cc.province, cc.city, cc.town, ti.node_short_name, ti.contact_name, ti.contact_phone')
    ->where($where)
    ->find();

$this->assign('boughtInfo', $boughtInfo);
$this->display();
}

/**
 * 下载提领券明细
 */
public function exportCode() {
$exportConfigArray = array();
$col_data = array();
$cols_arr = array();

$col_val = htmlspecialchars_decode(I('col_list'));
$col_val = substr($col_val, 0, strlen($col_val) - 1);
$col_arr = explode("&", $col_val);

foreach ($col_arr as $k => $v) {
    $col_arr2 = explode("=", $v);
    $col_data[$col_arr2[0]] = $col_arr2[1];
}

$exportConfigArray[1] = array(
    'col_name' => 'assist_number', 
    'col_str' => '辅助字符串', 
    'col_sel' => "bt.assist_number");
$exportConfigArray[2] = array(
    'col_name' => 'add_time', 
    'col_str' => '申请时间', 
    'col_sel' => "a.add_time");
$exportConfigArray[3] = array(
    'col_name' => 'code_name', 
    'col_str' => '卡券名称', 
    'col_sel' => "gi.goods_name as code_name");
$exportConfigArray[4] = array(
    'col_name' => 'receiver_name', 
    'col_str' => '收货人姓名', 
    'col_sel' => "a.receiver_name");
$exportConfigArray[5] = array(
    'col_name' => 'receiver_phone', 
    'col_str' => '收货人手机号', 
    'col_sel' => "a.receiver_phone");
$exportConfigArray[6] = array(
    'col_name' => 'delivery_company', 
    'col_str' => '物流公司', 
    'col_sel' => "a.delivery_company");
$exportConfigArray[7] = array(
    'col_name' => 'delivery_number', 
    'col_str' => '物流单号', 
    'col_sel' => "a.delivery_number");
$exportConfigArray[8] = array(
    'col_name' => 'delivery_date', 
    'col_str' => '发出时间', 
    'col_sel' => "a.delivery_date");
$exportConfigArray[9] = array(
    'col_name' => 'delivery_status', 
    'col_str' => '配送状态', 
    'col_sel' => "a.delivery_status");
$exportConfigArray[10] = array(
    'col_name' => 'goods_name', 
    'col_str' => '商品名称', 
    'col_sel' => "gi.goods_name");
$exportConfigArray[11] = array(
    'col_name' => 'receiver_addr', 
    'col_str' => '收货地址', 
    'col_sel' => "a.receiver_addr");

$searchField = '';
foreach ($col_data as $k => $val) {
    if ($val == 1) {
        if ($k == 9) {
            $searchField .= " CASE a.delivery_status WHEN '1' THEN '待配送' WHEN '2' THEN '配送中' WHEN '3' THEN '已配送' END delivery_status,";
        } elseif ($k == 11) {
            $searchField .= "concat(ifnull(cc.province,''), ifnull(cc.city,''), ifnull(cc.town,''), a.receiver_addr) as receiver_addr,";
        } else {
            $searchField .= $exportConfigArray[$k]['col_sel'] . ',';
        }
        $cols_arr[$exportConfigArray[$k]['col_name']] = $exportConfigArray[$k]['col_str'];
    }
}

$searchField = substr($searchField, 0, strlen($searchField) - 1);

$where = " a.order_status <> 2 AND a.from_node_id = '" .
     $_SESSION['userSessInfo']['node_id'] . "'";
if ($_POST['order_id'] != '') {
    $where .= ' AND bt.assist_number = ' . $_POST['order_id'];
}

if ($_POST['delivery_status'] != '') {
    $where .= ' AND a.delivery_status = ' . $_POST['delivery_status'];
}

if ($_POST['start_time'] != '' && $_POST['end_time'] != '') {
    $where .= ' AND a.add_time > ' . $_POST['start_time'] .
         '00 AND a.add_time < ' . $_POST['end_time'] . '59';
}

if ($_POST['codeName'] != '') {
    $where .= " AND gi.goods_name = '" . $_POST['codeName'] . "'";
}

$sql = "SELECT {$searchField} FROM tonline_get_order a LEFT JOIN tbarcode_trace bt ON a.req_seq = bt.req_seq LEFT JOIN tgoods_info gi ON bt.batch_no = gi.batch_no LEFT JOIN tcity_code cc ON cc.path = a.receiver_citycode WHERE {$where}";

$oneSql = $sql . ' limit 1';
$info = M()->query($oneSql);
if (empty($info)) {
    $this->error('下载数据为空');
} else {
    if (querydata_download($sql, $cols_arr, M()) == false) {
        $this->error('下载失败');
    }
}
}

/**
 * 取消提领订单
 */
public function delWithdrowCode() {
$result = array();
$assistentNum = I('post.code');
if (preg_match('/^7063/', $assistentNum)) {
    $result['error'] = '1004';
    $result['msg'] = '此提领单为微信提领，暂不支持撤销操作，敬请谅解！';
    $this->ajaxReturn($result);
    exit();
}
$delReason = I('post.reason');
if ($delReason == '0') {
    $reasonContent = I('post.reasonContent');
} elseif ($delReason == '1') {
    $reasonContent = '消费者选择去门店提领';
}

$codeService = D('Code', 'Service');
$posVerifyInfo = M()->table("tbarcode_trace bt")->join(
    'tonline_get_order ogo ON ogo.req_seq = bt.req_seq')
    ->join('tgoods_info gi ON gi.goods_id = bt.goods_id')
    ->field(
    'ogo.pos_id, ogo.pos_seq, ogo.node_id, ogo.from_node_id, ogo.delivery_node_id, gi.source, gi.purchase_node_id')
    ->where(array(
    'bt.assist_number' => $assistentNum))
    ->find();

if (empty($posVerifyInfo)) {
    $result['error'] = '1001';
    $result['msg'] = '未查询到此订单！';
    $this->ajaxReturn($result);
    exit();
} elseif ($posVerifyInfo['pos_id'] == '' || $posVerifyInfo['pos_seq'] == '') {
    $result['error'] = '1002';
    $result['msg'] = '此订单数据异常，请联系商家！';
    $this->ajaxReturn($result);
    exit();
} else {
    if ($posVerifyInfo['source'] == '5') {
        if ($_SESSION['userSessInfo']['node_id'] !=
             $posVerifyInfo['purchase_node_id'] &&
             $_SESSION['userSessInfo']['node_id'] != C('withDraw.createNodeId')) {
            $result['error'] = '1003';
            $result['msg'] = '非常抱歉，您没有权限撤销此提领券！';
            $this->ajaxReturn($result);
            exit();
        }
    }
}
$posVerifyService = D('PosVerify', 'Service');
$cancelResult = $posVerifyService->doPosCancel($posVerifyInfo['pos_id'], 
    $posVerifyInfo['pos_seq'], $assistentNum);
if ($cancelResult === TRUE) {
    $orderSaveData = array();
    $orderSaveData['order_status'] = '2';
    $orderSaveData['del_memo'] = $reasonContent;
    $orderSaveData['del_time'] = date('YmdHis');
    $orderSaveData['del_member'] = M('tuser_info')->where(
        array(
            'node_id' => $_SESSION['userSessInfo']['node_id']))->getfield(
        'user_name');
    if (C('withDraw.createNodeId') == $_SESSION['userSessInfo']['node_id']) {
        $delCondition = array(
            'from_node_id' => $_SESSION['userSessInfo']['node_id'], 
            'pos_seq' => $posVerifyInfo['pos_seq'], 
            'pos_id' => $posVerifyInfo['pos_id']);
    } else {
        $delCondition = array(
            'delivery_node_id' => $_SESSION['userSessInfo']['node_id'], 
            'pos_seq' => $posVerifyInfo['pos_seq'], 
            'pos_id' => $posVerifyInfo['pos_id']);
    }
    M('tonline_get_order')->where($delCondition)->save($orderSaveData);
    $result['error'] = '0';
    $result['msg'] = '取消成功！';
} else {
    $result['error'] = '2001';
    $result['msg'] = $posVerifyService->errMsg;
}
$this->ajaxReturn($result);
}
}
