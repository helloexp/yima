<?php

/**
 * 会员卡申领
 *
 * @author bao
 */
class MemberRegistrationAction extends BaseAction {
    // 活动类型
    public $BATCH_TYPE = '4';
    // 图片路径
    public $img_path;

    public function _initialize() {
        parent::_initialize();
        $path_arr = C('BATCH_IMG_PATH_NAME');
        $img_path = $path_arr[$this->BATCH_TYPE] . '/' . $this->node_id . '/';
        $this->img_path = $img_path;
        
        // 临时路径
        $tmp_path = 'Upload/img_tmp' . '/' . $this->node_id . '/';
        $this->assign('img_path', $img_path);
        $this->assign('tmp_path', $tmp_path);
    }

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
        $arr_ = C('CHANNEL_TYPE');
        // dump($list);
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
                "node_id='{$this->nodeId}' AND name='{$name}' and batch_type='{$this->BATCH_TYPE}'")->find();
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
            $showNodeName = I('post.node_name', null);
            if (! check_str($showNodeName, 
                array(
                    'null' => true, 
                    'maxlen_cn' => '20'), $error)) {
                $this->error("商户名称{$error}");
            }
            $wapInfo = I('post.wap_info', null);
            if (! check_str($wapInfo, 
                array(
                    'null' => true, 
                    'maxlen_cn' => '200'), $error)) {
                $this->error("活动页面内容{$error}");
            }
            $isSend = I('post.is_send', null);
            if (! check_str($isSend, 
                array(
                    'null' => false), $error)) {
                $this->error('请选择是否下发会员卡');
            }
            $memo = I('post.memo', null);
            if (! check_str($wapInfo, 
                array(
                    'null' => ture, 
                    'maxlen_cn' => '200'), $error)) {
                $this->error("活动备注{$error}");
            }
            $memberLevel = I('post.member_level', null, 
                'mysql_real_escape_string');
            if (! check_str($memberLevel, 
                array(
                    'null' => false), $error)) {
                $this->error('请选择招募会员权益');
            }
            // 该会员等级是否有效
            $memberLevelInfo = M('tmember_batch')->where(
                "node_id='{$this->nodeId}' AND batch_no='{$memberLevel}' AND status=1")->find();
            if (! $memberLevelInfo)
                $this->error('要招募的粉丝权益不存在');
                // 信息采集字段 1姓名 2生日 3性别 4下拉框
            $field_1 = '';
            $field_2 = '';
            if (I('post.field_name') == '1') {
                $field_1 .= '1-';
                $field_2 .= I('post.field_name_p') . '-';
            }
            if (I('post.field_birthday') == '1') {
                $field_1 .= '2-';
                $field_2 .= I('post.field_birthday_p') . '-';
            }
            if (I('post.field_sex') == '1') {
                $field_1 .= '3-';
                $field_2 .= I('post.field_sex') . '-';
            }
            if (I('post.field_select') == '1') {
                $field_1 .= '4-';
                $field_2 .= '1-';
                $selectQ = I('post.select_q', null, 'mysql_real_escape_string');
                if (! check_str($selectQ, 
                    array(
                        'null' => false), $error)) {
                    $this->error('请填写下拉框标题');
                }
                $selectA = I('post.select_a', null, 'mysql_real_escape_string');
                if (! check_str($selectA, 
                    array(
                        'null' => false), $error)) {
                    $this->error('下拉框选项不能为空');
                }
            }
            $fieldType = substr($field_1, 0, - 1) . ',' .
                 substr($field_2, 0, - 1);
            $size = I('post.size', null);
            $isCodeImg = I('post.is_code_img', null);
            $respCodeImg = I('post.resp_code_img', null);
            if ($isCodeImg != '1')
                $respCodeImg = '';
            $snsType = I('post.sns_type');
            if (! empty($snsType)) {
                $snsType = implode('-', $snsType);
            }
            
            // 背景
            $bg_img_name = I('post.resp_bg_img', null);
            if ($bg_img_name) {
                $bg_img = $bg_img_name;
            }
            $data = array(
                'name' => $name, 
                'node_id' => $this->nodeId, 
                'node_name' => $showNodeName, 
                'select_type' => $fieldType, 
                'wap_info' => $wapInfo, 
                'member_level' => $memberLevel, 
                'is_send' => $isSend, 
                'start_time' => $startDate . '000000', 
                'end_time' => $endDate . '235959', 
                // 'size' => $size,
                // 'code_img' => $respCodeImg,
                'is_cj' => '0', 
                'memo' => $memo, 
                'sns_type' => $snsType, 
                'add_time' => date('YmdHis'), 
                'page_style' => I('post.page_style'), 
                'bg_style' => I('post.bg_style'), 
                'bg_pic' => $bg_img, 
                'status' => '1', 
                'batch_type' => $this->BATCH_TYPE, 
                'is_show' => '1');
            M()->startTrans();
            $batchId = M('tmarketing_info')->add($data);
            if (! $batchId) {
                M()->rollback();
                $this->error('系统出错,添加失败');
            }
            if (I('post.field_select') == '1' && isset($selectQ) &&
                 $selectQ != '') { // select问题插入
                $data = array(
                    'label_id' => $batchId, 
                    'questions' => $selectQ, 
                    'type' => '1');
                $questionId = M('tanswers_question')->add($data);
                if (! $questionId) {
                    M()->rollback();
                    $this->error('系统出错,添加失败');
                }
                foreach ($selectA as $v) {
                    $data = array(
                        'question_id' => $questionId, 
                        'answers' => $v);
                    $result = M('tanswers_question_info')->add($data);
                    if (! $result) {
                        M()->rollback();
                        $this->error('系统出错,添加失败');
                    }
                }
            }
            M()->commit();
            $ser = D('TmarketingInfo');
            $arr = array(
                'node_id' => $this->nodeId, 
                'batch_type' => $this->BATCH_TYPE, 
                'batch_id' => $batchId);
            $ser->init($arr);
            $ser->sendBatch();
            node_log("粉丝招募活动创建。名称：" . $name);
            $this->ajaxReturn(
                array(
                    'url' => U('LabelAdmin/BindChannel/index', 
                        array(
                            'batch_type' => $this->BATCH_TYPE, 
                            'batch_id' => $batchId)), 
                    'gocj' => U('LabelAdmin/CjSet/index', 
                        array(
                            'batch_id' => $batchId))), '添加成功！', 1);
            exit();
        }
        node_log("首页+粉丝招募");
        // 获取商户名称
        $nodeName = M('tnode_info')->where("node_id='{$this->node_id}'")->getField(
            'node_name');
        $this->assign('node_name', $nodeName);
        // 会员等级
        $arr = R('Member/Member/getBatch');
        $this->assign('member_off', '1'); // 控制抽奖是否显示[粉丝专享]
        $this->assign('batch_list', $arr);
        $this->display();
    }
    
    // 编辑
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
        $memberReInfo = M('tmarketing_info')->where($where)->find();
        if (empty($memberReInfo))
            $this->error('未找到该活动!');
        $node_id = $memberReInfo['node_id'];
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
                "node_id='{$node_id}' and batch_type='{$this->BATCH_TYPE}' AND name='{$name}' AND id<>{$id}")->find();
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
            $showNodeName = I('post.node_name', null);
            if (! check_str($showNodeName, 
                array(
                    'null' => true, 
                    'maxlen_cn' => '20'), $error)) {
                $this->error("商户名称{$error}");
            }
            $wapInfo = I('post.wap_info', null);
            if (! check_str($wapInfo, 
                array(
                    'null' => true, 
                    'maxlen_cn' => '200'), $error)) {
                $this->error("活动页面内容{$error}");
            }
            $isSend = I('post.is_send', null);
            if (! check_str($isSend, 
                array(
                    'null' => false), $error)) {
                $this->error('请选择是否下发会员卡');
            }
            $memo = I('post.memo', null);
            if (! check_str($wapInfo, 
                array(
                    'null' => ture, 
                    'maxlen_cn' => '200'), $error)) {
                $this->error("活动备注{$error}");
            }
            $memberLevel = I('post.member_level', null, 
                'mysql_real_escape_string');
            if (! check_str($memberLevel, 
                array(
                    'null' => false), $error)) {
                $this->error('请选择招募会员等级');
            }
            // 该会员等级是否有效
            $memberLevelInfo = M('tmember_batch')->where(
                "node_id='{$node_id}' AND batch_no='{$memberLevel}' AND status=1")->find();
            if (! $memberLevelInfo)
                $this->error('要招募的粉丝权益不存在');
                // 信息采集字段
            $field_1 = '';
            $field_2 = '';
            if (I('post.field_name') == '1') {
                $field_1 .= '1-';
                $field_2 .= I('post.field_name_p') . '-';
            }
            if (I('post.field_birthday') == '1') {
                $field_1 .= '2-';
                $field_2 .= I('post.field_birthday_p') . '-';
            }
            if (I('post.field_sex') == '1') {
                $field_1 .= '3-';
                $field_2 .= I('post.field_sex') . '-';
            }
            if (I('post.field_select') == '1') {
                $field_1 .= '4-';
                $field_2 .= '1-';
                $selectQ = I('post.select_q', null, 'mysql_real_escape_string');
                if (! check_str($selectQ, 
                    array(
                        'null' => false), $error)) {
                    $this->error('请填写下拉框标题');
                }
                $selectA = I('post.select_a', null, 'mysql_real_escape_string');
                if (! check_str($selectA, 
                    array(
                        'null' => false), $error)) {
                    $this->error('下拉框选项不能为空');
                }
            }
            $fieldType = substr($field_1, 0, - 1) . ',' .
                 substr($field_2, 0, - 1);
            // echo $fieldType;exit;
            $size = I('post.size', null);
            $isCodeImg = I('post.is_code_img', null);
            $respCodeImg = I('post.resp_code_img', null);
            if ($isCodeImg != '1')
                $respCodeImg = '';
            
            $snsType = I('post.sns_type');
            if (! empty($snsType)) {
                $snsType = implode('-', $snsType);
            }
            // 背景图
            $resp_bg_img = I('post.resp_bg_img', null);
            $reset_bg = I('post.reset_bg', null);
            if ($resp_bg_img && $reset_bg == '1') {
                $bg_img = $resp_bg_img;
            } else {
                $bg_img = $resp_bg_img;
            }
            $data = array(
                'name' => $name, 
                'node_id' => $node_id, 
                'node_name' => $showNodeName, 
                'select_type' => $fieldType, 
                'wap_info' => $wapInfo, 
                'member_level' => $memberLevel, 
                'is_send' => $isSend, 
                'start_time' => $startDate . '000000', 
                'end_time' => $endDate . '235959', 
                // 'size' => $size,
                // 'code_img' => $respCodeImg,
                'sns_type' => $snsType, 
                'memo' => $memo, 
                'page_style' => I('post.page_style'), 
                'bg_style' => I('post.bg_style'), 
                'bg_pic' => $bg_img, 
                'is_show' => '1');
            M()->startTrans();
            $result = M('tmarketing_info')->where(
                "node_id='{$node_id}' AND id='{$id}'")->save($data);
            if ($result === false) {
                M()->rollback();
                $this->error('系统出错,更新失败!');
            }
            // 删除下拉框问题和答案
            $oldSelectQ = M('tanswers_question')->where("label_id='{$id}'")->find();
            if ($oldSelectQ) {
                $r1 = M('tanswers_question')->where("label_id='{$id}'")->delete();
                $r2 = M('tanswers_question_info')->where(
                    "question_id='{$oldSelectQ['id']}'")->delete();
                if (! $r1 || ! $r2) {
                    M()->rollback();
                    $this->error('系统出错,更新失败!');
                }
            }
            // 重新插入下拉框问题答案
            if (I('post.field_select') == '1' && isset($selectQ) &&
                 $selectQ != '') { // select问题插入
                $data = array(
                    'label_id' => $id, 
                    'questions' => $selectQ, 
                    'type' => '1');
                $questionId = M('tanswers_question')->add($data);
                if (! $questionId) {
                    M()->rollback();
                    $this->error('系统出错,添加失败');
                }
                foreach ($selectA as $v) {
                    $data = array(
                        'question_id' => $questionId, 
                        'answers' => $v);
                    $result = M('tanswers_question_info')->add($data);
                    if (! $result) {
                        M()->rollback();
                        $this->error('系统出错,添加失败');
                    }
                }
            }
            M()->commit();
            
            node_log("粉丝招募活动修改。名称：" . $name);
            $this->success('更新成功!');
            exit();
        }
        // 信息采集字段
        if (! empty($memberReInfo['select_type'])) {
            $field = explode(',', $memberReInfo['select_type']);
            $type_1 = array_flip(explode('-', $field[0]));
            $type_2 = explode('-', $field[1]);
            $this->assign('type_1', $type_1);
            $this->assign('type_2', $type_2);
        }
        // 获取下拉框问题
        if (isset($type_1[4])) {
            $selectQ = M('tanswers_question')->where(
                "label_id='{$memberReInfo['id']}'")->find();
            $selectA = M('tanswers_question_info')->where(
                "question_id='{$selectQ['id']}'")->select();
            $this->assign('selectQ', $selectQ);
            $this->assign('selectA', $selectA);
        }
        // 会员等级
        $arr = R('Member/Member/getBatch');
        $this->assign('batch_list', $arr);
        $this->assign('member_off', '1'); // 控制抽奖是否显示[粉丝专享]
        $this->assign('row', $memberReInfo);
        $this->display();
    }

    public function weiXinIndex() {
        $model = M('tmarketing_info');
        $map = array(
            'node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"), 
            'batch_type' => $this->BATCH_TYPE, 
            're_type' => '1');
        $list = $model->where($map)
            ->order('id desc')
            ->find();
        $this->assign('list', $list);
        $this->display(); // 输出模板
    }

    public function weiXinAdd() {
        
        // 判断唯一
        $result = M('tmarketing_info')->where(
            "node_id='{$this->nodeId}' AND batch_type={$this->BATCH_TYPE} AND re_type='1'")->find();
        if ($result)
            redirect(
                U('LabelAdmin/MemberRegistration/weiXinEdit', 
                    array(
                        'id' => $result['id'])));
        if ($this->isPost()) {
            
            $error = '';
            $showNodeName = I('post.node_name', null);
            if (! check_str($showNodeName, 
                array(
                    'null' => true, 
                    'maxlen_cn' => '20'), $error)) {
                $this->error("商户名称{$error}");
            }
            $wapInfo = I('post.wap_info', null);
            if (! check_str($wapInfo, 
                array(
                    'null' => true, 
                    'maxlen_cn' => '200'), $error)) {
                $this->error("活动页面内容{$error}");
            }
            $isSend = I('post.is_send', null);
            if (! check_str($isSend, 
                array(
                    'null' => false), $error)) {
                $this->error('请选择是否下发会员卡');
            }
            $memo = I('post.memo', null);
            if (! check_str($wapInfo, 
                array(
                    'null' => ture, 
                    'maxlen_cn' => '200'), $error)) {
                $this->error("活动备注{$error}");
            }
            $memberLevel = I('post.member_level', null, 
                'mysql_real_escape_string');
            if (! check_str($memberLevel, 
                array(
                    'null' => false), $error)) {
                $this->error('请选择招募会员等级');
            }
            // 该会员等级是否有效
            $memberLevelInfo = M('tmember_batch')->where(
                "node_id='{$this->nodeId}' AND batch_no='{$memberLevel}' AND status=1")->find();
            if (! $memberLevelInfo)
                $this->error('要招募的粉丝权益不存在');
                // 信息采集字段 1姓名 2生日 3性别
            $field_1 = '';
            $field_2 = '';
            if (I('post.field_name') == '1') {
                $field_1 .= '1-';
                $field_2 .= I('post.field_name_p') . '-';
            }
            if (I('post.field_birthday') == '1') {
                $field_1 .= '2-';
                $field_2 .= I('post.field_birthday_p') . '-';
            }
            if (I('post.field_sex') == '1') {
                $field_1 .= '3-';
                $field_2 .= I('post.field_sex') . '-';
            }
            $fieldType = substr($field_1, 0, - 1) . ',' .
                 substr($field_2, 0, - 1);
            $size = I('post.size', null);
            $isCodeImg = I('post.is_code_img', null);
            $respCodeImg = I('post.resp_code_img', null);
            if ($isCodeImg != '1')
                $respCodeImg = '';
            $snsType = I('post.sns_type');
            if (! empty($snsType)) {
                $snsType = implode('-', $snsType);
            }
            // 背景
            $bg_img_name = I('post.resp_bg_img', null);
            if ($bg_img_name) {
                $img_move = move_batch_image($bg_img_name, $this->BATCH_TYPE, 
                    $this->node_id);
                if ($img_move !== true)
                    $this->error('背景图片上传失败！');
                $bg_img = $this->img_path . $bg_img_name;
            }
            $data = array(
                'name' => '微信会员招募', 
                're_type' => '1', 
                'node_id' => $this->nodeId, 
                'node_name' => $showNodeName, 
                'select_type' => $fieldType, 
                'wap_info' => $wapInfo, 
                'member_level' => $memberLevel, 
                'is_send' => $isSend, 
                'start_time' => '20130101000000', 
                'end_time' => '20300101235959', 
                // 'size' => $size,
                // 'code_img' => $respCodeImg,
                'is_cj' => '0', 
                'memo' => $memo, 
                'sns_type' => $snsType, 
                'add_time' => date('YmdHis'), 
                'page_style' => I('post.page_style'), 
                'bg_style' => I('post.bg_style'), 
                'bg_pic' => $bg_img, 
                'batch_type' => $this->BATCH_TYPE, 
                'is_show' => '1');
            $batchId = M('tmarketing_info')->add($data);
            if (! $batchId) {
                $this->error('系统出错,添加失败');
            }
            
            $ser = D('TmarketingInfo');
            $arr = array(
                'node_id' => $this->nodeId, 
                'batch_type' => $this->BATCH_TYPE, 
                'batch_id' => $batchId);
            $ser->init($arr);
            $ser->sendBatch();
            $this->success('配置成功');
            exit();
        }
        // 获取商户名称
        $nodeName = M('tnode_info')->where("node_id='{$this->node_id}'")->getField(
            'node_name');
        $this->assign('node_name', $nodeName);
        // 会员等级
        $arr = R('Member/Member/getBatch');
        $this->assign('member_off', '1'); // 控制抽奖是否显示[粉丝专享]
        $this->assign('batch_list', $arr);
        $this->display();
    }

    public function weiXinEdit() {
        $id = I('id', null, 'mysql_real_escape_string');
        if (empty($id))
            $this->error('错误参错');
        $where = array(
            'node_id' => $this->nodeId, 
            'id' => $id, 
            'batch_type' => $this->BATCH_TYPE, 
            're_type' => '1');
        $memberReInfo = M('tmarketing_info')->where($where)->find();
        if (empty($memberReInfo['select_type']))
            $this->error('未找到该活动!');
        if ($this->isPost()) {
            $error = '';
            $showNodeName = I('post.node_name', null);
            if (! check_str($showNodeName, 
                array(
                    'null' => true, 
                    'maxlen_cn' => '20'), $error)) {
                $this->error("商户名称{$error}");
            }
            $wapInfo = I('post.wap_info', null);
            if (! check_str($wapInfo, 
                array(
                    'null' => true, 
                    'maxlen_cn' => '200'), $error)) {
                $this->error("活动页面内容{$error}");
            }
            $isSend = I('post.is_send', null);
            if (! check_str($isSend, 
                array(
                    'null' => false), $error)) {
                $this->error('请选择是否下发会员卡');
            }
            $memo = I('post.memo', null);
            if (! check_str($wapInfo, 
                array(
                    'null' => ture, 
                    'maxlen_cn' => '200'), $error)) {
                $this->error("活动备注{$error}");
            }
            $memberLevel = I('post.member_level', null, 
                'mysql_real_escape_string');
            if (! check_str($memberLevel, 
                array(
                    'null' => false), $error)) {
                $this->error('请选择招募会员等级');
            }
            // 该会员等级是否有效
            $memberLevelInfo = M('tmember_batch')->where(
                "node_id='{$this->nodeId}' AND batch_no='{$memberLevel}' AND status=1")->find();
            if (! $memberLevelInfo)
                $this->error('要招募的粉丝权益不存在');
                // 信息采集字段
            $field_1 = '';
            $field_2 = '';
            if (I('post.field_name') == '1') {
                $field_1 .= '1-';
                $field_2 .= I('post.field_name_p') . '-';
            }
            if (I('post.field_birthday') == '1') {
                $field_1 .= '2-';
                $field_2 .= I('post.field_birthday_p') . '-';
            }
            if (I('post.field_sex') == '1') {
                $field_1 .= '3-';
                $field_2 .= I('post.field_sex') . '-';
            }
            $fieldType = substr($field_1, 0, - 1) . ',' .
                 substr($field_2, 0, - 1);
            // echo $fieldType;exit;
            $size = I('post.size', null);
            $isCodeImg = I('post.is_code_img', null);
            $respCodeImg = I('post.resp_code_img', null);
            if ($isCodeImg != '1')
                $respCodeImg = '';
            
            $snsType = I('post.sns_type');
            if (! empty($snsType)) {
                $snsType = implode('-', $snsType);
            }
            
            // 背景图
            $resp_bg_img = I('post.resp_bg_img', null);
            $reset_bg = I('post.reset_bg', null);
            if ($resp_bg_img && $reset_bg == '1') {
                $img_move = move_batch_image($resp_bg_img, $this->BATCH_TYPE, 
                    $this->node_id);
                if ($img_move !== true)
                    $this->error('背景图片上传失败！' . $img_move);
                $bg_img = $this->img_path . $resp_bg_img;
            } else {
                $bg_img = $resp_bg_img;
            }
            
            $data = array(
                'node_id' => $this->nodeId, 
                'node_name' => $showNodeName, 
                'select_type' => $fieldType, 
                'wap_info' => $wapInfo, 
                'member_level' => $memberLevel, 
                'is_send' => $isSend, 
                // 'size' => $size,
                // 'code_img' => $respCodeImg,
                'sns_type' => $snsType, 
                'memo' => $memo, 
                'page_style' => I('post.page_style'), 
                'bg_style' => I('post.bg_style'), 
                'bg_pic' => $bg_img);
            
            $result = M('tmarketing_info')->where(
                "node_id='{$this->nodeId}' AND id='{$id}'")->save($data);
            if ($result === false) {
                $this->error('系统出错,更新失败!');
            }
            
            $this->success('更新成功!');
            exit();
        }
        // 信息采集字段
        if (! empty($memberReInfo['select_type'])) {
            $field = explode(',', $memberReInfo['select_type']);
            $type_1 = array_flip(explode('-', $field[0]));
            $type_2 = explode('-', $field[1]);
            $this->assign('type_1', $type_1);
            $this->assign('type_2', $type_2);
        }
        
        // 会员等级
        $arr = R('Member/Member/getBatch');
        $this->assign('batch_list', $arr);
        $this->assign('member_off', '1'); // 控制抽奖是否显示[粉丝专享]
        
        $this->assign('row', $memberReInfo);
        $this->display();
    }
    
    // 数据导出
    public function export() {
        
        // 查询条件组合
        $where = "WHERE batch_type='" . $this->BATCH_TYPE . "'";
        if (! empty($_POST)) {
            $filter = array();
            $condition = array_map('trim', $_POST);
            if (isset($condition['key']) && $condition['key'] != '') {
                $filter[] = "name LIKE '%{$condition['key']}%'";
            }
            if (isset($condition['status']) && $condition['status'] != '') {
                $filter[] = "status = '{$condition['status']}'";
            }
            if (isset($condition['start_time']) && $condition['start_time'] != '') {
                $condition['start_time'] = $condition['start_time'] . ' 000000';
                $filter[] = "add_time >= '{$condition['start_time']}'";
            }
            if (isset($condition['end_time']) && $condition['end_time'] != '') {
                $condition['end_time'] = $condition['end_time'] . ' 235959';
                $filter[] = "add_time <= '{$condition['end_time']}'";
            }
        }
        if (! empty($filter)) {
            $condition = implode(' AND ', $filter);
            $where .= ' AND ' . $condition;
        }
        $sql = "SELECT name,add_time,start_time,end_time,
		CASE status WHEN '1' THEN '正常' ELSE '停用' END status,
		click_count,send_count
		FROM
		tmarketing_info {$where} AND node_id in({$this->nodeIn()})";
        $cols_arr = array(
            'name' => '活动名称', 
            'add_time' => '添加时间', 
            'start_time' => '活动开始时间', 
            'end_time' => '活动结束时间', 
            'status' => '状态', 
            'click_count' => '访问量', 
            'send_count' => '发码量');
        if (querydata_download($sql, $cols_arr, M()) == false) {
            $this->error('下载失败');
        }
    }
    
    // 状态修改
    public function editStatus() {
        $batchId = I('post.batch_id', null);
        $status = I('post.status', null);
        if (is_null($batchId) || is_null($status)) {
            $this->error('缺少必要参数');
        }
        $result = M('tmarketing_info')->where(
            "node_id in({$this->nodeIn()}) AND id='{$batchId}'")->find();
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
        $result = M('tmarketing_info')->save($data);
        if ($result) {
            node_log(
                "粉丝招募活动" . $status == 1 ? '开启' : '停用' . "。名称：" . $result['name']);
            $this->success('更新成功', 
                array(
                    '返回' => U('News/index')));
        } else {
            $this->error('更新失败');
        }
    }

    public function winningExport() {
        $batchId = I('batch_id', null, 'intval');
        $status = I('status', null, 'mysql_real_escape_string');
        if (is_null($batchId))
            $this->error('缺少参数');
        $sql = "SELECT mobile,add_time,
		CASE status WHEN '1' THEN '未中奖' ELSE '中奖' END status,prize_level
		FROM
		tcj_trace WHERE batch_id='{$batchId}' AND batch_type={$this->BATCH_TYPE} AND node_id in({$this->nodeIn()})
		ORDER by status DESC";
        $countSql = "SELECT COUNT(*) as count FROM tcj_trace WHERE batch_id='{$batchId}' AND batch_type={$this->BATCH_TYPE} AND node_id in({$this->nodeIn()})";
        $cols_arr = array(
            'mobile' => '手机号', 
            'add_time' => '中奖时间', 
            'status' => '是否中奖', 
            'prize_level' => '奖品等级');
        // 获取活动名称
        $batchName = M('tmarketing_info')->where(
            "id='{$batchId}' AND node_id in({$this->nodeIn()}")->getField('name');
        $fileName = $batchName . '-' . date('Y-m-d') . '-' . '中奖名单';
        $count = M()->query($countSql);
        if ($count[0]['count'] <= 0)
            $this->error('没有中奖数据');
        if (empty($status))
            $this->ajaxReturn('', '', 1);
        if (querydata_download($sql, $cols_arr, M(), $fileName) == false) {
            $this->error('下载失败或没有中奖数据');
        }
    }

    public function memberExport() {
        $batchId = I('batch_id', null, 'intval');
        $map = array(
            'node_id' => $this->nodeId, 
            'batch_id' => $batchId);
        $count = M('tmember_info')->where($map)->count();
        if ($count == 0)
            $this->error('未查询到相关信息');
        $fileName = '招募结果.csv';
        $fileName = iconv('utf-8', 'gb2312', $fileName);
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        $start_num = 0;
        $page_count = 5000;
        $cj_title = "手机号,姓名,出生日期,性别,自定义下拉框标题,自定义下拉框选项\r\n";
        echo $cj_title = iconv('utf-8', 'gbk', $cj_title);
        for ($j = 1; $j < 100; $j ++) {
            $page = ($j - 1) * $page_count;
            $list = M()->table('tmember_info')
                ->field(
                "phone_no,IFNULL(name,'--') as name,IFNULL(birthday,'--') as birthday,
					    		 CASE sex WHEN '1' THEN '男' WHEN '2' THEN '女' ELSE '--' END sex,
					    		 IFNULL(select_q,'--') as select_q,
					    		IFNULL(select_a,'--') as select_a
					    		")
                ->where($map)
                ->order('id desc')
                ->limit($page, $page_count)
                ->select();
            if (! $list)
                exit();
            foreach ($list as $v) {
                $name = iconv('utf-8', 'gbk', $v['name']);
                $sex = iconv('utf-8', 'gbk', $v['sex']);
                $selectQ = iconv('utf-8', 'gbk', $v['select_q']);
                $selectA = iconv('utf-8', 'gbk', $v['select_a']);
                echo "{$v['phone_no']},{$name},{$v['birthday']},{$sex},{$selectQ},{$selectA}\r\n";
            }
        }
    }
}