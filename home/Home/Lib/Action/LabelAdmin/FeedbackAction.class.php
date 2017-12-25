<?php

/**
 * 礼品派发活动
 *
 * @author bao
 */
class FeedbackAction extends BaseAction {
    
    // 活动类型
    public $BATCH_TYPE = '14';

    public function index() {
        $model = M('tmarketing_info');
        $map = array(
            'node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"), 
            're_type' => '0', 
            'batch_type' => $this->BATCH_TYPE);
        $nodeId = I('node_id', null, 'mysql_real_escape_string');
        if (! empty($nodeId)) {
            $map['node_id '] = $nodeId;
        }
        $name = I('key', '', 'mysql_escape_string');
        if ($name != '')
            $map['name'] = array(
                'like', 
                '%' . $name . '%');
        $status = I('status', null, 'mysql_escape_string');
        if (! empty($status))
            $map['status'] = $status;
            // 处理特殊查询字段
        $beginDate = I('start_time', null, 'mysql_real_escape_string,trim');
        if (! empty($beginDate)) {
            $map['add_time'] = array(
                'egt', 
                $beginDate . '000000');
        }
        $endDate = I('end_time', null, 'mysql_real_escape_string,trim');
        if (! empty($endDate)) {
            $map[' add_time'] = array(
                'elt', 
                $endDate . '235959');
        }
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = $model->where($map)->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        
        foreach ($_REQUEST as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        
        $show = $Page->show(); // 分页显示输出
        
        $list = $model->where($map)
            ->order('id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        // 发码 验码 撤销
        foreach ($list as $k => $v) {
            $sql = "SELECT SUM(send_num) AS count FROM tpos_day_count WHERE node_id = '{$v['node_id']}' AND b_id IN (SELECT id FROM tbatch_info WHERE m_id = '{$v['id']}' )";
            $sendNum = M()->query($sql);
            $list[$k]['send_num'] = $sendNum[0]['count'] ? $sendNum[0]['count'] : 0;
        }
        $arr_ = C('CHANNEL_TYPE');
        // dump($list);
        node_log("首页+礼品派发");
        $this->assign('arr_', $arr_);
        $this->assign('query_list', $list);
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('nodeList', $this->getNodeTree());
        $this->display(); // 输出模板
    }

    public function add() {
        if ($this->isPost()) {
            $error = '';
            $name = I('post.name', null);
            if (! check_str($name, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '20'), $error)) {
                $this->error("活动名称{$error}");
            }
            $dataInfo = M('tmarketing_info')->where(
                "node_id='{$this->nodeId}' AND name='{$name}'")->find();
            if ($dataInfo)
                $this->error('活动名称已经存在');
            $startDate = I('post.start_time', null);
            if (! check_str($startDate, 
                array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd'), $error)) {
                $this->error("活动开始时间{$error}");
            }
            $endDate = I('post.end_time', null);
            if (! check_str($endDate, 
                array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd'), $error)) {
                $this->error("活动结束时间{$error}");
            }
            $goodsId = I('post.goods_id', null, 'mysql_real_escape_string');
            $goodsInfo = M('tgoods_info')->where(
                "goods_id='{$goodsId}' AND node_id='{$this->nodeId}' AND status=0")->find();
            if (! $goodsInfo)
                $this->error('未找到该卡券');
                
                // 使用时间
            if ($goodsInfo['goods_type'] != '9') {
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
                        if (strtotime($verifyEndDate) <
                             strtotime($verifyBeginDate)) {
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
            }  // 旺财卡券特殊处理
else {
                $sms_text = $goodsInfo['sms_text'];
                $verifyTimeType = $goodsInfo['verify_begin_type'];
                $verifyBeginDate = $goodsInfo['verify_begin_date'];
                $verifyEndDate = $goodsInfo['verify_end_date'];
            }
            // tmarketing_info数据添加
            $marketData = array(
                'batch_type' => $this->BATCH_TYPE, 
                'name' => $name, 
                'start_time' => $startDate . '000000', 
                'end_time' => $endDate . '235959', 
                'status' => '1', 
                'node_id' => $this->nodeId, 
                'add_time' => date('YmdHis'));
            $marketId = M('tmarketing_info')->add($marketData);
            if (! $marketId) {
                M()->rollback();
                $this->error('数据库出错添加失败1');
            }
            $data = array(
                'batch_no' => $goodsInfo['batch_no'], 
                'batch_short_name' => $goodsInfo['goods_name'], 
                'batch_name' => $goodsInfo['goods_name'], 
                'node_id' => $this->nodeId, 
                'user_id' => $this->userId, 
                'batch_class' => $goodsInfo['goods_type'], 
                'batch_type' => $goodsInfo['source'], 
                'use_rule' => $usingRules, 
                'batch_img' => $goodsInfo['goods_image'], 
                'info_title' => $mmsTitle, 
                'sms_text' => $sms_text, 
                'batch_amt' => $goodsInfo['goods_amt'], 
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
                'm_id' => $marketId, 
                'validate_type' => $goodsInfo['validate_type']);
            M()->startTrans();
            $batchId = M('tbatch_info')->data($data)->add();
            if (! $batchId) {
                M()->rollback();
                $this->error('数据库出错添加失败2');
            }
            M()->commit();
            $this->success('活动创建成功');
            exit();
        }
        $this->display();
    }

    public function edit() {
        $id = I('id', null, 'mysql_real_escape_string');
        if (empty($id))
            $this->error('错误参错');
        $where = array(
            'node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"), 
            'id' => $id, 
            're_type' => '0', 
            'batch_type' => $this->BATCH_TYPE);
        $feedbackInfo = M('tmarketing_info')->where($where)->find();
        if (empty($feedbackInfo))
            $this->error('未找到该活动!');
        $batchInfo = M('tbatch_info')->where(
            "m_id='{$feedbackInfo['id']}' AND node_id='{$this->nodeId}'")->find();
        
        $map = array(
            'goods_id' => $batchInfo['goods_id']);
        $goodsInfo = M('tgoods_info')->where($map)->find();
        // 表单提交
        if ($this->isPost()) {
            $error = '';
            $name = I('post.name', null);
            if (! check_str($name, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '20'), $error)) {
                $this->error("活动名称{$error}");
            }
            $dataInfo = M('tmarketing_info')->where(
                "node_id='{$this->nodeId}' AND name='{$name}' AND id<>{$id}")->find();
            if ($dataInfo)
                $this->error('活动名称已经存在');
            $startDate = I('post.start_time', null);
            if (! check_str($startDate, 
                array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd'), $error)) {
                $this->error("活动开始时间{$error}");
            }
            $endDate = I('post.end_time', null);
            if (! check_str($endDate, 
                array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd'), $error)) {
                $this->error("活动结束时间{$error}");
            }
            
            if ($goodsInfo['goods_type'] != '9') {
                // 使用时间
                $verifyTimeType = $batchInfo['verify_begin_type'];
                switch ($verifyTimeType) {
                    case 0:
                        $verifyEndDate = I('post.verify_end_date');
                        if (! check_str($verifyEndDate, 
                            array(
                                'null' => false, 
                                'strtype' => 'datetime', 
                                'format' => 'Ymd'), $error)) {
                            $this->error("使用结束时间日期{$error}");
                        }
                        if ($verifyEndDate < date('Ymd')) {
                            $this->error('使用结束时间不能小于当前时间');
                        }
                        $verifyEndDate .= '235959';
                        if ($verifyEndDate < $batchInfo['verify_end_date'])
                            $this->error('使用结束时间不能缩短,只能延长');
                        break;
                    case 1:
                        $verifyEndDate = I('post.verify_end_days');
                        if (! check_str($verifyEndDate, 
                            array(
                                'null' => false, 
                                'strtype' => 'int'), $error)) {
                            $this->error("使用结束天数{$error}");
                        }
                        if ($verifyEndDate < $batchInfo['verify_end_date']) {
                            $this->error('使用结束天数不能缩短,只能延长');
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
            }
            // 数据库更新
            M()->startTrans();
            if ($goodsInfo['goods_type'] != '9') {
                $data = array(
                    'use_rule' => $usingRules, 
                    'info_title' => $mmsTitle, 
                    'verify_end_date' => $verifyEndDate, 
                    'status' => '0');
                $result = M('tbatch_info')->where("id={$batchInfo['id']}")->save(
                    $data);
                if ($result === false) {
                    M()->rollback();
                    $this->error('数据出错,更新失败1');
                }
            }
            // tmarketing_info
            $data = array(
                'name' => $name, 
                'start_time' => $startDate . '000000', 
                'end_time' => $endDate . '235959');
            $resutl = M('tmarketing_info')->where("id='{$id}'")->save($data);
            if ($result === false) {
                M()->rollback();
                $this->error('数据出错,更新失败2');
            }
            M()->commit();
            node_log("礼品派发活动编辑，名称：" . $feedbackInfo['name']);
            $this->success('更新成功');
            exit();
        }
        $this->assign('feedbackInfo', $feedbackInfo);
        $this->assign('batchInfo', $batchInfo);
        $this->assign('goodsInfo', $goodsInfo);
        $this->display();
    }
    
    // 状态修改
    public function editStatus() {
        $batchId = I('post.batch_id', null);
        $status = I('post.status', null);
        if (is_null($batchId) || is_null($status)) {
            $this->error('缺少必要参数');
        }
        $map = array(
            'node_id' => array(
                'exp', 
                "in ({$this->nodeIn()})"), 
            'id' => $batchId, 
            'batch_type' => $this->BATCH_TYPE);
        $result = M('tmarketing_info')->where($map)->find();
        if (! $result) {
            $this->error('未找到该活动');
        }
        if ($status == '1') {
            $data = array(
                'id' => $batchId, 
                'status' => '1');
        } else {
            $data = array(
                'id' => $batchId, 
                'status' => '2');
        }
        $result = M('tmarketing_info')->where($map)->save($data);
        if ($result) {
            node_log(
                "礼品派发活动" . $status == 1 ? '开启' : '停用' . "。名称：" . $result['name']);
            $this->success('更新成功');
        } else {
            $this->error('更新失败');
        }
    }
    
    // 活动状态检查
    public function checkStatus() {
        $id = I('id', null, 'mysql_real_escape_string');
        $status = M('tmarketing_info')->where(
            "node_id in({$this->nodeIn()}) AND id='{$id}'")->getField('status');
        if ($status == 1) {
            $this->success('该活动正常');
        } else {
            $this->error('该活动已经停用');
        }
    }
}