<?php

/**
 * 粉丝回馈
 *
 * @author bao
 */
class MemberFeedbackAction extends MemberAction {
    // protected $node_id = '00004831';
    // protected $user_id = '1234';
    public function index() {
        // 是否存在数据
        $getone = M('tmember_info')->field('id')
            ->where(array(
            'node_id' => $this->node_id))
            ->find();
        $this->assign('getone', $getone);
        $seachResutl = I('search_result');
        if ($seachResutl != '1') {
            // 获取粉丝等级
            $memberLevelList = M('tmember_batch')->field(
                'level_name,member_level')
                ->where("node_id='{$this->node_id}' AND status=1")
                ->select();
            // 获取粉丝分组
            $memberGroupList = M('tmember_group')->where(
                "node_id='{$this->nodeId}' OR id=1")->select();
            $this->assign('memberLevList', $memberLevelList);
            $this->assign('memberGroupList', $memberGroupList);
            $this->assign('count', '0');
            $this->assign('empty', '<tr><td colspan="9">无数据</td></span>');
            $this->display();
            exit();
        }
        $where = '1=1';
        $verifyWhere = '1=1';
        // 登记日期
        $addDate = I('add_date', null, 'mysql_real_escape_string');
        switch ($addDate) {
            case '2': // 一个月内
                $where .= " AND i.add_time>={$this->getDate(1)}000000";
                break;
            case '3': // 三个月内
                $where .= " AND i.add_time>={$this->getDate(3)}000000";
                break;
            case '4': // 半年内
                $where .= " AND i.add_time>={$this->getDate(6)}000000";
                break;
            case '5': // 一年内
                $where .= " AND i.add_time>={$this->getDate(12)}000000";
                break;
            case '6': // 自定义
                $beginAddDate = I('begin_add_date', null, 
                    'mysql_real_escape_string');
                if (! empty($beginAddDate)) {
                    $where .= " AND i.add_time>={$beginAddDate}000000";
                }
                $endAddDate = I('end_add_date', null, 
                    'mysql_real_escape_string');
                if (! empty($endAddDate)) {
                    $where .= " AND i.add_time<={$endAddDate}235959";
                }
                break;
        }
        // 刷卡日期
        $transDate = I('trans_date', null, 'mysql_real_escape_string');
        switch ($transDate) {
            case '2': // 一个月内
                $verifyWhere .= " AND trans_time>={$this->getDate(1)}000000";
                break;
            case '3': // 三个月内
                $verifyWhere .= " AND trans_time>={$this->getDate(3)}000000";
                break;
            case '4': // 半年内
                $verifyWhere .= " AND trans_time>={$this->getDate(6)}000000";
                break;
            case '5': // 一年内
                $verifyWhere .= " AND trans_time>={$this->getDate(12)}000000";
                break;
            case '6': // 自定义
                $beginTransDate = I('begin_trans_date', null, 
                    'mysql_real_escape_string');
                if (! empty($beginTransDate)) {
                    $verifyWhere .= " AND trans_time>={$beginTransDate}000000";
                }
                $endTransDate = I('end_trans_date', null, 
                    'mysql_real_escape_string');
                if (! empty($endTransDate)) {
                    $verifyWhere .= " AND trans_time<={$endTransDate}235959";
                }
                break;
        }
        // 刷卡次数
        $verifyNum = I('verify_num', null, 'mysql_real_escape_string');
        switch ($verifyNum) {
            case '2': // 至少一次
                $where .= " AND IFNULL(s.verify_count,0)>=1";
                break;
            case '3': // 自定义
                $beginVerifyNum = I('begin_verify_num', null, 
                    'mysql_real_escape_string');
                if (isset($beginVerifyNum) && $beginVerifyNum != '') {
                    $where .= " AND IFNULL(s.verify_count,0)>={$beginVerifyNum}";
                }
                $endVerifyNum = I('end_verify_num', null, 
                    'mysql_real_escape_string');
                if (isset($endVerifyNum) && $endVerifyNum != '') {
                    $where .= " AND IFNULL(s.verify_count,0)<={$endVerifyNum}";
                }
                break;
        }
        // 粉丝等级
        $memberLevel = I('member_level', null, 'mysql_real_escape_string');
        if (! empty($memberLevel)) {
            $where .= " AND b.member_level ={$memberLevel}";
        }
        // 粉丝分组
        $memberGroup = I('member_group', null, 'mysql_real_escape_string');
        if (! empty($memberGroup)) {
            $where .= " AND i.group_id ={$memberGroup}";
        }
        // 粉丝年龄
        $age = I('age', null, 'mysql_real_escape_string');
        switch ($age) {
            case '2': // 18～24
                $where .= " AND i.age>=18 AND i.age<=24";
                break;
            case '3': // 25~30
                $where .= " AND i.age>=25 AND i.age<=30";
                break;
            case '4': // 31~35
                $where .= " AND i.age>=31 AND i.age<=35";
                break;
            case '5': // 36~45
                $where .= " AND i.age>=36 AND i.age<=45";
                break;
            case '6': // 自定义
                $beginAge = I('begin_age', null, 'mysql_real_escape_string');
                $endAge = I('end_age', null, 'mysql_real_escape_string');
                if (! is_null($beginAge) && is_numeric($beginAge)) {
                    $where .= " AND i.age>={$beginAge}";
                }
                if (! is_null($endAge) && is_numeric($endAge)) {
                    $where .= " AND i.age<={$endAge}";
                }
                break;
        }
        // 粉丝性别
        $sex = I('sex', null, 'mysql_real_escape_string');
        switch ($sex) {
            case '2':
                $where .= " AND i.sex=1";
                break;
            case '3':
                $where .= " AND i.sex=2";
                break;
        }
        // 粉丝生日
        $Birthday = I('br_month', null, 'mysql_real_escape_string');
        switch ($Birthday) {
            case '2': // 一月份
                $where .= " AND i.month_days>='0101' AND i.month_days<='0131'";
                break;
            case '3':
                $where .= " AND i.month_days>='0201' AND i.month_days<='0231'";
                break;
            case '4':
                $where .= " AND i.month_days>='0301' AND i.month_days<='0331'";
                break;
            case '5':
                $where .= " AND i.month_days>='0401' AND i.month_days<='0431'";
                break;
            case '6':
                $where .= " AND i.month_days>='0501' AND i.month_days<='0531'";
                break;
            case '7':
                $where .= " AND i.month_days>='0601' AND i.month_days<='0631'";
                break;
            case '8':
                $where .= " AND i.month_days>='0701' AND i.month_days<='0731'";
                break;
            case '9':
                $where .= " AND i.month_days>='0801' AND i.month_days<='0831'";
                break;
            case '10':
                $where .= " AND i.month_days>='0901' AND i.month_days<='0931'";
                break;
            case '11':
                $where .= " AND i.month_days>='1001' AND i.month_days<='1031'";
                break;
            case '12':
                $where .= " AND i.month_days>='1101' AND i.month_days<='1131'";
                break;
            case '13':
                $where .= " AND i.month_days>='1201' AND i.month_days<='1231'";
                break;
            case '14': // 自定义
                $beginBirthday = I('be_br_month', null, 
                    'mysql_real_escape_string');
                if (! empty($beginBirthday)) {
                    $where .= " AND i.month_days>={$beginBirthday}";
                }
                $endBirthday = I('en_br_month', null, 
                    'mysql_real_escape_string');
                if (! empty($endBirthday)) {
                    $where .= " AND i.month_days<={$endBirthday}";
                }
                break;
        }
        
        $countSql = "SELECT COUNT(*) as count 
				            FROM tmember_info i 
				            LEFT JOIN (SELECT node_id, phone_no,COUNT(*) AS verify_count 
				                              FROM tpos_trace 
				                              WHERE trans_type='0' AND `status`='0' AND {$verifyWhere} GROUP BY node_id,phone_no ) s 
				                   ON i.node_id =s.node_id AND i.phone_no = s.phone_no 
				            LEFT JOIN tmember_batch b 
				                   ON i.node_id=b.node_id AND i.batch_no=b.batch_no 
				            WHERE i.node_id='{$this->node_id}' AND {$where}";
        import("ORG.Util.Page");
        $countInfo = M()->query($countSql);
        
        // 是否下一步
        $next = I("next");
        if ($next == '1') {
            $seachData = array();
            $countNum = $countInfo['0']['count'];
            if ($countNum <= 0) {
                $this->error('未找到符合条件的粉丝');
            }
            $seachData['verifyWhere'] = $verifyWhere;
            $seachData['where'] = $where;
            session('seachData', $seachData);
            $this->assign('countNum', $countNum);
            $this->display('selectGift');
            exit();
        }
        
        $p = new Page($countInfo['0']['count'], 20);
        // 确保分页查询条件
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        
        $sql = "SELECT i.name,i.node_id,i.phone_no,i.sex,i.birthday,i.age,i.add_time,b.level_name,IFNULL(s.verify_count,0) AS verify_count 
				            FROM tmember_info i 
				            LEFT JOIN (SELECT node_id, phone_no,COUNT(*) AS verify_count 
				                              FROM tpos_trace 
				                              WHERE trans_type='0' AND `status`='0' AND {$verifyWhere} GROUP BY node_id,phone_no ) s 
				                   ON i.node_id =s.node_id AND i.phone_no = s.phone_no 
				            LEFT JOIN tmember_batch b 
				                   ON i.node_id=b.node_id AND i.batch_no=b.batch_no 
				            WHERE i.node_id='{$this->node_id}' AND {$where}
		                    ORDER BY IFNULL(s.verify_count,0) DESC
		                    LIMIT {$p->firstRow},{$p->listRows}";
        // echo $sql;
        $memberList = M()->query($sql);
        
        // 获取粉丝等级
        $memberLevelList = M('tmember_batch')->field('level_name,member_level')
            ->where("node_id='{$this->node_id}' AND status=1")
            ->select();
        // 获取粉丝分组
        $memberGroupList = M('tmember_group')->where(
            "node_id='{$this->nodeId}' OR id=1")->select();
        $page = $p->show();
        $this->assign('memberLevList', $memberLevelList);
        $this->assign('memberGroupList', $memberGroupList);
        $this->assign('count', $countInfo['0']['count']);
        $this->assign('memberList', $memberList);
        $this->assign('sex_arr', C('SEX_ARR'));
        $this->assign('post', $_REQUEST);
        $this->assign('empty', '<tr><td colspan="9">无数据</td></span>');
        $this->assign("page", $page);
        $this->display();
    }
    
    // 礼品列表(已作废)
    public function giftList() {
        if ($this->isPost()) {
            $id = I('post.id', null, 'intval');
            if (is_null($id))
                $this->error('参数错误');
            $dataInfo = M('tbatch_info')->field('batch_no,batch_name')
                ->where("node_id='{$this->node_id}' AND id='{$id}'")
                ->find();
            if (! $dataInfo)
                $this->error('未找到该礼品数据');
            $this->ajaxReturn($dataInfo, '', 1);
            exit();
        }
        import("ORG.Util.Page");
        $count = M('tbatch_info')->where(
            "node_id='{$this->node_id}' AND batch_type=0 AND status=0")->count();
        $p = new Page($count, 20);
        $numGoodsList = M('tbatch_info')->where(
            "node_id='{$this->node_id}' AND batch_class<3 AND batch_type=0 AND status=0")
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        $page = $p->show();
        $batchClass = array(
            '0' => '优惠劵', 
            '1' => '代金券', 
            '2' => '提领券', 
            '3' => '储值卡');
        $this->assign('numGoodsList', $numGoodsList);
        $this->assign('batchClass', $batchClass);
        $this->assign('empty', '<tr><td colspan="9">无数据</td></span>');
        $this->assign("page", $page);
        $this->display();
    }
    
    // 下一步 确认礼品页面
    public function confirmGift() {
        $batchId = I('post.batch_id', null);
        $memberNum = I('post.member_num', null);
        if (is_null($batchId) || is_null($memberNum)) {
            $this->error('参数错误');
        }
        // 验证筛选条件
        $seachData = session('seachData');
        if (! is_array($seachData)) {
            $this->error('缺少必要参数');
        }
        // 验证礼品数据
        $dataInfo = M('tbatch_info')->field('batch_no,batch_name')
            ->where("node_id='{$this->node_id}' AND id='{$batchId}'")
            ->find();
        if (! $dataInfo)
            $this->error('未找到该礼品数据');
        
        $seachData['batch_id'] = $batchId;
        session('seachData', $seachData);
        $this->assign('giftInfo', $dataInfo['batch_name']);
        $this->assign('memberNum', $memberNum);
        $this->display();
    }
    
    // 发码开始
    public function sendCode() {
        $seachData = session('seachData');
        if (! is_array($seachData) || empty($seachData['batch_id'])) {
            $this->error('参数错误');
        }
        // 验证礼品数据
        $dataInfo = M('tbatch_info')->where(
            "node_id='{$this->node_id}' AND id={$seachData['batch_id']}")->find();
        if (! $dataInfo)
            $this->error('错误的礼品数据');
        $goodsInfo = M('tgoods_info')->where(
            "goods_id='{$dataInfo['goods_id']}'")->find();
        $sql = "SELECT i.phone_no 
						FROM tmember_info i 
						LEFT JOIN (SELECT node_id, phone_no,COUNT(*) AS verify_count 
						FROM tpos_trace 
						WHERE trans_type='0' AND `status`='0' AND {$seachData['verifyWhere']} GROUP BY node_id,phone_no ) s 
						ON i.node_id =s.node_id AND i.phone_no = s.phone_no 
						LEFT JOIN tmember_batch b 
						ON i.node_id=b.node_id AND i.batch_no=b.batch_no 
						WHERE i.node_id='{$this->node_id}' AND {$seachData['where']}";
        // echo $sql;exit;
        $phoneNoList = M()->query($sql);
        
        if (! $phoneNoList) {
            $this->error('未找到粉丝信息');
        }
        $phoneCount = count($phoneNoList);
        
        // 发码开始
        $importModel = M('tbatch_import');
        $importdetailModel = M('tbatch_importdetail');
        // 开启事物
        $importModel->startTrans();
        // 库存校验
        $goodsStorage = M('tgoods_info')->lock(true)
            ->field('storage_type,remain_num')
            ->where("goods_id='{$dataInfo['goods_id']}'")
            ->find();
        if ($goodsStorage['remain_num'] < $phoneCount &&
             $goodsStorage['storage_type'] == '1') {
            $importModel->rollback();
            $this->error('库存不足');
        }
        $data = array(
            'batch_no' => $dataInfo['batch_no'], 
            'user_id' => $this->user_id, 
            'node_id' => $this->node_id, 
            'total_count' => $phoneCount, 
            'add_time' => date('YmdHis'), 
            'data_from' => '7', 
            'info_title' => $dataInfo['info_title'], 
            'mms_notes' => $goodsInfo['goods_type'] == '9' ? $dataInfo['sms_text'] : $dataInfo['use_rule'], 
            'notes' => $dataInfo['sms_text'], 
            'print_text' => $dataInfo['print_text'], 
            'validate_times' => '1', 
            'validate_amt' => $dataInfo['batch_amt'], 
            'verify_begin_time' => D('Goods')->dayToDate(
                $dataInfo['verify_begin_date'], $dataInfo['verify_begin_type']), 
            'verify_end_time' => D('Goods')->dayToDate(
                $dataInfo['verify_end_date'], $dataInfo['verify_end_type']), 
            'b_id' => $dataInfo['id']);
        
        // 插入批次表数据
        $batch_id = $importModel->add($data);
        if (! $batch_id) {
            $importModel->rollback();
            $this->error('系统出错-0001');
        }
        // 插入明细表数据
        foreach ($phoneNoList as $k => $v) {
            $data = array(
                'batch_no' => $dataInfo['batch_no'], 
                'batch_id' => $batch_id, 
                'node_id' => $this->node_id, 
                'request_id' => time() . sprintf('%04s', mt_rand(0, 1000)) .
                     sprintf('%04s', $k + 1), 
                    'phone_no' => $v['phone_no'], 
                    'add_time' => date('YmdHis'));
            $result = $importdetailModel->add($data);
            if (! $result) {
                $importModel->rollback();
                $this->error('系统出错-0002');
            }
        }
        // 更新库存
        $goodsModel = D('Goods');
        $result = $goodsModel->storagenum_reduc($dataInfo['goods_id'], 
            $phoneCount, $batch_id, 9);
        if (! $result) {
            log::write(
                '信息:' . $goodsModel->getError() . 'goods_id:' .
                     $dataInfo['goods_id'] . 'num:1');
        }
        $importModel->commit();
        session('seachData', null);
        node_log("粉丝回馈，数量：" . $phoneCount . "个");
        $this->success('数据创建成功');
    }

    /**
     * 获取某个月前日期,从当前时间算(固定一个月30天)
     *
     * @param int $monthNum 指定多少个月前的日期
     */
    public function getDate($monthNum) {
        return date('Ymd', time() - ($monthNum * 30 * 24 * 3600));
    }
    
    // 粉丝回馈发码记录
    public function feedbackSendBatch() {
        $map = array(
            'i.node_id' => $this->nodeId, 
            'i.data_from' => '7');
        $batchid = I('batch_id', null, 'mysql_real_escape_string');
        if (! empty($batchid)) {
            $map['i.batch_id'] = $batchid;
        }
        $batchName = I('batch_name', '', 'mysql_real_escape_string');
        if ($batchName != '') {
            $map['b.batch_name'] = array(
                'like', 
                "%{$batchName}%");
        }
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = M()->table('tbatch_import i')
            ->join(
            'tgoods_info b ON i.batch_no = b.batch_no AND i.node_id=b.node_id')
            ->where($map)
            ->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        foreach ($_REQUEST as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        $show = $Page->show(); // 分页显示输出
        
        $list = M()->table('tbatch_import i')
            ->field('i.*,b.goods_name')
            ->join(
            'tgoods_info b ON i.batch_no = b.batch_no AND i.node_id=b.node_id')
            ->where($map)
            ->order('i.batch_id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        // dump($list);exit;
        $status = array(
            '0' => '未发码', 
            '1' => '发码中', 
            '2' => '已发码', 
            '3' => '已发码', 
            '9' => '发码失败');
        
        $this->assign('status', $status);
        $this->assign('check_status', 
            array(
                '0' => '未审核', 
                '1' => '审核通过', 
                '2' => '审核拒绝'));
        $this->assign('list', $list);
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }

    public function addBatch() {
        // 只有粉丝回馈用到这里
        $goodsId = I('post.goods_id', null, 'mysql_real_escape_string');
        $where = array(
            'goods_id' => $goodsId, 
            'node_id' => $this->node_id, 
            'goods_type' => array(
                'in', 
                '0,1,2,3,9'), 
            'source' => array(
                'in', 
                '0,1,2'), 
            'batch_no' => array(
                'exp', 
                'IS NOT NULL'), 
            'status' => 0);
        $goodsInfo = M('tgoods_info')->where($where)->find();
        if (! $goodsInfo)
            $this->error('未找到该卡券' . M()->_sql());
        $error = '';
        
        if ($goodsInfo['goods_type'] != '9') {
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
            
        } else {
            $sms_text = $goodsInfo['sms_text'];
            $verifyTimeType = $goodsInfo['verify_begin_type'];
            if ($verifyTimeType == '0') {
                $verifyBeginDate = $goodsInfo['verify_begin_date'];
                $verifyEndDate = $goodsInfo['verify_end_date'];
            } else {
                $verifyBeginDate = $goodsInfo['verify_begin_date'];
                $verifyEndDate = $goodsInfo['verify_end_date'];
            }
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
            'sms_text' => $sms_text, 
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
            'print_text' => $goodsInfo['print_text']);
        M()->startTrans();
        $batchId = M('tbatch_info')->data($data)->add();
        if (! $batchId) {
            M()->rollback();
            $this->error('数据库出错添加失败1');
        }
        M()->commit();
        $showArr = array(
            'batch_id' => $batchId, 
            'goods_name' => $goodsInfo['goods_name']);
        $this->success($showArr);
    }
}