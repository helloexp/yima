<?php

// 选择旺财奖品活动
class SelectJpAction extends BaseAction {

    public function index() {
        $model = M('tgoods_info');
        $map = array(
            'node_id' => $this->node_id, 
            'goods_type' => array(
                'in', 
                '0,1,2,3,9'), 
            'source' => array(
                'in', 
                '0,1,2,4'), 
            'batch_no' => array(
                'exp', 
                'IS NOT NULL'), 
            'status' => 0);
        $goodsType = I('goods_type', null);
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = $model->where($map)->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        
        $list = $model->where($map)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->order('add_time DESC')
            ->select();
        $this->assign('query_list', $list);
        $this->assign('page', $show); // 赋值分页输出
        $type = I('type');
        $ygid = I('ygid');
        if (empty($type)) {
            $this->display(); // 输出模板
        } else if (! empty($ygid) && $type == '2') {
            $this->assign('ygid', $ygid);
            $this->display('spoil'); // 输出模板
        } else {
            $this->display('feedbackJp'); // 礼品派发
        }
    }
    
    // 创建活动
    public function addBatch() {
        $goodsId = I('post.goods_id', null, 'mysql_real_escape_string');
        $where = array(
            'goods_id' => $goodsId, 
            'node_id' => $this->node_id, 
            'goods_type' => array(
                'in', 
                '0,1,2,3'), 
            'source' => array(
                'in', 
                '0,1,2,4'), 
            'batch_no' => array(
                'exp', 
                'IS NOT NULL'), 
            'status' => 0);
        $goodsInfo = M('tgoods_info')->where($where)->find();
        if (! $goodsId)
            $this->error('未找到该卡券');
        $error = '';
        // 使用时间
        $verifyTimeType = I('post.verify_time_type');
        switch ($verifyTimeType) {
            case 0:
                $verifyBeginDate = I('post.verify_begin_date');
                if (! check_str($verifyBeginDate, 
                    array(
                        'null' => false, 
                        'strtype' => 'datetime', 
                        'format' => 'Ymd'), $error)) {
                    $this->error("使用开始时间日期{$error}");
                }
                $verifyEndDate = I('post.verify_end_date');
                if (! check_str($verifyEndDate, 
                    array(
                        'null' => false, 
                        'strtype' => 'datetime', 
                        'format' => 'Ymd'), $error)) {
                    $this->error("使用结束时间日期{$error}");
                }
                if (strtotime($verifyEndDate) < strtotime($verifyBeginDate)) {
                    $this->error('使用开始时间日期不能大于使用结束时间日期');
                }
                $verifyBeginDate .= '000000';
                $verifyEndDate .= '235959';
                break;
            case 1:
                $verifyBeginDate = I('post.verify_begin_days');
                if (! check_str($verifyBeginDate, 
                    array(
                        'null' => false, 
                        'strtype' => 'int'), $error)) {
                    $this->error("使用开始天数{$error}");
                }
                $verifyEndDate = I('post.verify_end_days');
                if (! check_str($verifyEndDate, 
                    array(
                        'null' => false, 
                        'strtype' => 'int'), $error)) {
                    $this->error("使用结束天数{$error}");
                }
                if ($verifyEndDate < $verifyBeginDate) {
                    $this->error('使用开始天数不能大于使用结束天数');
                }
                break;
            default:
                $this->error('未知的使用时间类型');
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
        $data = array(
            'batch_no' => $goodsInfo['batch_no'], 
            'batch_short_name' => $goodsInfo['goods_name'], 
            'batch_name' => $goodsInfo['goods_name'], 
            'node_id' => $this->nodeId, 
            'user_id' => $this->userId, 
            'batch_class' => $goodsInfo['goods_type'], 
            'batch_type' => $goodsInfo['source'], 
            'join_rule' => 'fensihuikui', 
            'use_rule' => $usingRules, 
            'batch_img' => $goodsInfo['goods_image'], 
            'info_title' => $mmsTitle, 
            'batch_amt' => $goodsInfo['goods_amt'], 
            'batch_discount' => $goodsInfo['goods_discount'], 
            'begin_time' => $goodsInfo['begin_time'], 
            'end_time' => $goodsInfo['end_time'], 
            'verify_begin_date' => $verifyBeginDate, 
            'verify_end_date' => $verifyEndDate, 
            'verify_begin_type' => $verifyTimeType, 
            'verify_end_type' => $verifyTimeType, 
            'add_time' => date('YmdHis'), 
            'node_pos_group' => $goodsInfo['pos_group'], 
            'node_pos_type' => $goodsInfo['pos_group_type'], 
            'batch_desc' => $goodsInfo['goods_desc'], 
            'node_service_hotline' => $goodsInfo['node_service_hotline'], 
            'goods_id' => $goodsId, 
            'storage_num' => '-1', 
            'remain_num' => '-1', 
            'material_code' => $goodsInfo['customer_no'], 
            'print_text' => $goodsInfo['print_text'], 
            'validate_type' => $goodsInfo['validate_type']);
        M()->startTrans();
        $batchId = M('tbatch_info')->data($data)->add();
        if (! $batchId) {
            M()->rollback();
            $this->error('数据库出错添加失败1');
        }
        M()->commit();
        $showArr = array(
            'batch_id' => $batchId, 
            'batch_no' => $goodsInfo['batch_no'], 
            'goods_name' => $goodsInfo['goods_name']);
        $this->ajaxReturn($showArr, '', 1);
    }
    
    // 获取商品信息
    public function getGoodsInfo() {
        $goodsId = I('goods_id', null, 'mysql_real_escape_string');
        $goodsInfo = M('tgoods_info')->field(
            'goods_id,begin_time,end_time,goods_name,send_begin_date,send_end_date,verify_begin_date,verify_end_date,verify_begin_type,verify_end_type,mms_title,mms_text,goods_type,sms_text')
            ->where("goods_id='{$goodsId}' AND status=0")
            ->find();
        if (! $goodsInfo)
            $this->error('未找到该卡券信息');
        $goodsInfo['begin_time'] = dateformat($goodsInfo['begin_time'], 'Y-m-d');
        $goodsInfo['end_time'] = dateformat($goodsInfo['end_time'], 'Y-m-d');
        $this->ajaxReturn($goodsInfo, '', 1);
    }
    
    // 发送奖品
    public function addspoil() {
        $ygid = I('post.staff');
        $goods_id = I('post.goods_id');
        $mms_title = I('post.mms_title');
        $using_rules = I('post.using_rules');
        $verify_begin_date = I('post.verify_begin_date'); // 验证开始时间
        $verify_end_date = I('post.verify_end_date'); // 验证结束时间
        if ($verify_begin_date != '') {
            $begin = $verify_begin_date;
            $end = $verify_end_date;
        }
        $verify_begin_days = I('post.verify_begin_days'); // 验证开始时间（天数）
        $verify_end_days = I('post.verify_end_days'); // 验证结束时间（天数）
        if ($verify_begin_days != '') {
            $begin = date('Ymd', 
                (strtotime(date('Ymd')) + (24 * 60 * 60 * ($verify_begin_days))));
            $end = date('Ymd', 
                (strtotime(date('Ymd')) + (24 * 60 * 60 * ($verify_end_days))));
        }
        
        $id = $this->node_id;
        if ($ygid == '' && $goods_id == '')
            $this->error("参数错误");
        $outcome = M('tstaff_channel')->field('phone')
            ->where(array(
            'id' => $ygid))
            ->find();
        $result = M('tgoods_info')->field(
            'batch_no,print_text,market_price,sms_text,validate_times')
            ->where(array(
            'goods_id' => $goods_id))
            ->find();
        $phone = $outcome['phone'];
        $error = "手机号格式不正确";
        $res = check_str($phone, array(
            'strtype' => 'mobile'), $error);
        if ($res === false)
            $this->error("参数错误$error");
        $arr1 = array(
            'mms_notes' => $using_rules, 
            'info_title' => $mms_title, 
            'data_from' => 'A', 
            'status' => 0, 
            'send_level' => 1, 
            'total_count' => 1, 
            'user_id' => $this->user_id, 
            'node_id' => $id, 
            'print_text' => $result['print_text'], 
            'validate_amt' => $result['market_price'], 
            'notes' => $result['sms_text'], 
            'batch_no' => $result['batch_no'], 
            'validate_times' => $result['validate_times'], 
            'add_time' => date("YmdHis"), 
            'verify_begin_time' => $begin . '000000', 
            'verify_end_time' => $end . '235959');
        $tranDb = new Model();
        $tranDb->startTrans();
        $row1 = M('tbatch_import')->add($arr1);
        if (! $row1) {
            $tranDb->rollback();
            $this->error('发送失败！！！');
        }
        $arr2 = array(
            'batch_no' => $result['batch_no'], 
            'request_id' => date("YmdHis") . mt_rand(100000, 999999), 
            'phone_no' => $phone, 
            'batch_id' => $row1, 
            'node_id' => $id, 
            'status' => '0', 
            'add_time' => date("YmdHis"));
        $row2 = M('tbatch_importdetail')->add($arr2);
        if (! $row2) {
            $tranDb->rollback();
            $this->error('发送失败！！！');
        }
        $tranDb->commit();
        echo "<script>parent.art.dialog.list['ount'].close()</script>";
        exit();
    /**
     * import ( "@.Vendor.SendCode" ); $transId =
     * date('YmdHis').sprintf('%04s',mt_rand(0,1000)); $req = new SendCode();
     * $res =
     * $req->wc_send($id,'',$result['batch_no'],$outcome['phone'],'A',$transId);
     * if($res === true){ echo
     * "<script>parent.art.dialog.list['ount'].close()</script>"; exit; }else{
     * $this->error("发送失败1.$res"); }
     */
    }
}
