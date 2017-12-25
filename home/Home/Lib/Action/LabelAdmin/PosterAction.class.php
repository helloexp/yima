<?php

/**
 * 电子海报 -Poster
 *
 * @author chensf
 */
class PosterAction extends BaseAction {
    
    // 活动类型
    public $BATCH_TYPE = '37';

    public $CHANNEL_TYPE = "4";

    public $CHANNEL_SNS_TYPE = "48";
    // 图片路径
    public $img_path;

    public $marketInfoModel = "";

    public function _initialize() {
        parent::_initialize();
        
        $path_arr = C('BATCH_IMG_PATH_NAME');
        $img_path = $path_arr[$this->BATCH_TYPE] . '/' . $this->node_id . '/';
        $this->img_path = $img_path;
        
        // 临时路径
        $tmp_path = 'Upload/img_tmp' . '/' . $this->node_id . '/';
        $this->assign('img_path', $img_path);
        $this->assign('tmp_path', $tmp_path);
        $this->marketInfoModel = M('tmarketing_info');
    }

    public function index() {
        $model = $this->marketInfoModel;
        $map = array(
            'node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"));
        $data = $_REQUEST;
        
        if ($data['key'] != '') {
            $map['name'] = array(
                'like', 
                '%' . $data['key'] . '%');
        }
        if ($data['status'] != '') {
            $map['status'] = $data['status'];
        }
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
        $map['batch_type'] = $this->BATCH_TYPE;
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = $model->where($map)->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        
        foreach ($data as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        
        $show = $Page->show(); // 分页显示输出
        
        $list = $model->where($map)
            ->order('id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        foreach ($list as $key => $value) {
            $mainParam = json_decode($value['memo'], true);
            if (isset($mainParam['cover_img'])) {
                $list[$key]['cover_img'] = get_upload_url(
                    $mainParam['cover_img']);
            } else {
                $list[$key]['cover_img'] = C('TMPL_PARSE_STRING.__PUBLIC__') .
                     '/Image/poster/poster.png';
            }
        }
        
        $channelInfo = M('tchannel')->where(
            array(
                'node_id' => $this->node_id, 
                'type' => $this->CHANNEL_TYPE, 
                'sns_type' => $this->CHANNEL_SNS_TYPE))->find();
        if (! $channelInfo) { // 不存在则添加渠道
                              // 营销活动不存在则新增
            $channel_arr = array(
                'name' => '电子海报默认渠道', 
                'type' => $this->CHANNEL_TYPE, 
                'sns_type' => $this->CHANNEL_SNS_TYPE, 
                'status' => '1', 
                'start_time' => date('YmdHis'), 
                'end_time' => date('YmdHis', strtotime("+1 year")), 
                'add_time' => date('YmdHis'), 
                'click_count' => 0, 
                'cj_count' => 0, 
                'send_count' => 0, 
                'node_id' => $this->node_id);
            
            $cid = M('tchannel')->add($channel_arr);
            if (! $cid) {
                $this->error('添加电子海报默认渠道号失败');
            }
        }
        $this->assign('query_list', $list);
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }

    public function add() {
        $id = I('id', null, 'mysql_real_escape_string');
        if ($id) { // 如果有传marketing表的id过来表示是编辑
            $marketingInfo = $this->_checkIsSelfPoster($id);
            // 创建组装模板和参数
            $this->_assignTpl($marketingInfo);
        }
        $where['add_time'] = array(
            'lt', 
            date('Y-m-d H:i:s', (time() - 86400)));
        $where['type'] = 1;
        M('tdraft')->where($where)->delete();
        // 如果有草稿的覆盖掉前面assign的值
        $poster_data = M('tdraft')->where(
            array(
                'node_id' => $this->node_id, 
                'type' => 1))->getField('content');
        if ($poster_data) {
            $poster_data = json_decode($poster_data, true);
            if ($poster_data['m_id'] == $id) {
                $mainAndList = $this->_constructModule($poster_data);
                $main = json_decode($mainAndList['memo'], true);
                $main['list'] = $mainAndList['page_tpl_params'];
                $module = json_encode($main);
                $this->assign('module', $module);
                $this->assign('batch_id', $poster_data['batch_id']);
                $this->assign('batch_type', $poster_data['batch_type']);
                $this->assign('m_id', $poster_data['m_id']);
                $this->assign('activity_name', $poster_data['title']);
            }
        }
        // 否则是添加新的海报活动
        // 生成m_id和t_id和page_number，{"参数对应"=>"参数内容"}
        if (IS_POST) {
            $data = $this->checkPostData();
            $m_data = array(
                'name' => $data['name'],  // 活动名称
                'start_time' => date('YmdHis'), 
                'end_time' => '20301231235959', 
                'node_id' => $this->node_id,  // 商户id
                'add_time' => date('YmdHis'),  // 增加时间
                'node_name' => $this->node_name,  // 商户名称
                'batch_type' => $this->BATCH_TYPE,  // 活动类型
                'memo' => $data['memo'],  // 总的海报活动的参数json
                'pay_status' => 1);
            M()->startTrans();
            if (I('m_id', null, 'mysql_real_escape_string')) { // 如果有活动id（即marketing_info表的id，现在同时被做batch_id来用）
                $id = I('m_id', null, 'mysql_real_escape_string');
                $map = array(
                    "id" => $id, 
                    'node_id' => $this->node_id,  // 商户id
                    'batch_type' => $this->BATCH_TYPE); // 活动类型
                                                        
                // 先查一下
                $marketingInfo = $this->_checkIsSelfPoster($id);
                $updateMarketingResult = $this->marketInfoModel->where($map)->save(
                    $m_data);
                // 更新marketing_templet_ext表,删除自动保存的草稿
                $this->_updateMarketingTpl($id, $data['page_tpl_params'], true);
                M()->commit();
                $this->ajaxReturn(
                    array(
                        'status' => '1', 
                        'info' => '海报修改成功！', 
                        'm_id' => $id)); // todo还没测
            } else {
                // 出入营销活动表
                $batchId = $this->marketInfoModel->add($m_data);
                // 更新marketing_templet_ext表
                $this->_updateMarketingTpl($batchId, $data['page_tpl_params']);
                M()->commit();
                $this->ajaxReturn(
                    array(
                        'status' => '1', 
                        'info' => '海报创建成功！', 
                        'm_id' => $batchId)); // todo还没测
            }
        }
        $this->display();
    }
    
    // 保存成功画面
    public function saveSuccess() {
        $batch_id = I('batch_id');
        $map = array(
            'id' => $batch_id, 
            'node_id' => $this->node_id);
        $batchInfo = $this->marketInfoModel->where($map)->find();
        if (! $batchInfo) {
            $this->error('无效链接！');
        }
        $this->assign('batch_id', $batch_id);
        $this->assign('batch_type', $this->BATCH_TYPE);
        $edit_url = 'LabelAdmin/Poster/add';
        $this->assign('edit_url', $edit_url);
        $labelId = $this->getLabelIdAndTryAddLabelInfo($batch_id, $this->BATCH_TYPE); // 获取默认渠道id
        $this->assign('labelId', $labelId);
        $this->assign('isPoster', 1);
        $this->display('./Public_addSubmit');
    }

    public function createPosterLogo() {
        $labelId = I('labelId');
        import('@.Vendor.phpqrcode.phpqrcode') or die('include file fail.');
        $codeText = 'http://' . $_SERVER['HTTP_HOST'] . U('Label/Poster/index', 
            array(
                'id' => $labelId));
        QRcode::png($codeText);
    }

    public function preview() {
        $this->display();
    }

    public function ajaxGetLabelId() {
        $batchType = I('batchType');
        $batchId = I('batchId');
        $labelId = $this->getLabelIdAndTryAddLabelInfo($batchId, $batchType);
        $this->success($labelId, '', true);
    }
    
    // 新品推荐提交
    public function addSubmit() {
        $data = I("post.");
        $model = M('tmarketing_info');
        if (empty($data['name']))
            $this->error('请填写活动名称！');
            /*
         * if(empty($data['start_time'])) $this->error('请填写活动起始时间！');
         * if(empty($data['end_time'])) $this->error('请填写活动结束时间！');
         * if($data['end_time'] < date('Ymd')) $this->error('活动截止日期不能小于当前日期');
         */
        
        $datastr = I("request.memo");
        
        if ($data['batch_id'] != '' && $data['batch_type'] != '') {
            $channel_id = M('tchannel')->where(
                array(
                    'node_id' => $this->node_id, 
                    'type' => $this->CHANNEL_TYPE, 
                    'sns_type' => $this->CHANNEL_SNS_TYPE))->getField('id');
            $label_id = get_batch_channel($data['batch_id'], $channel_id, 
                $data['batch_type'], $this->node_id);
        } else
            $label_id = "";
        
        $one_map = array(
            'name' => $data['name'], 
            'batch_type' => $this->BATCH_TYPE, 
            'node_id' => $this->node_id);
        $info = $model->where($one_map)->count('id');
        if ($info > 0) {
            $this->error("活动名称重复");
        }
        // 分享描述
        if ($data['share_note'] == '')
            $this->error('请填写分享描述！');
            // 预览图
        if ($data['img_resp'] == '') {
            $this->error('请上传分享预览图片！');
        }
        // 移动图片
        $img_move = move_batch_image($data['img_resp'], $this->BATCH_TYPE, 
            $this->node_id);
        if ($img_move !== true)
            $this->error('预览图片上传失败！' . $data['img_resp'], 
                array(
                    '返回' => "javascript:history.go(-1)"));
        
        $share_pic = $this->img_path . $data['img_resp'];
        
        // 二维码logo
        $isCodeImg = I('post.is_code_img', null);
        $respCodeImg = I('post.resp_code_img', null);
        if ($isCodeImg != '1')
            $respCodeImg = '';
        
        M()->startTrans();
        $data_arr = array(
            'name' => $data['name'],  // 活动名称
                                     // 'start_time'=>substr($data['start_time'],0,8).'000000',//活动开始时间
                                     // 'end_time'=>substr($data['end_time'],0,8).'235959',//活动结束时间
            'start_time' => date('YmdHis'), 
            'end_time' => '20301231235959', 
            'node_id' => $this->node_id,  // 商户id
            'add_time' => date('YmdHis'),  // 增加时间
            'status' => '1',  // 活动状态默认正常
            'node_name' => $this->node_name,  // 商户名称
                                             // 'size'=>$data['size'],//生成二维码的大小
                                             // 'code_img'=>$respCodeImg,//二维码路径logo
                                             // 'page_style'=>$data['page_style'],//活动页面风格
            'batch_type' => $this->BATCH_TYPE,  // 活动类型
                                               // 'defined_one_name'=>$data['btnTitle'],
                                               // //按钮名称
                                               // 'defined_two_name'=>$data['loop'],
                                               // //是否循环 0开启 1不开启
                                               // 'defined_three_name'=>$data['vague'],
                                               // //首页模糊 0开启 1不开启
            'memo' => $datastr, 
            'defined_one_name' => $label_id, 
            'defined_two_name' => $data['batch_id'], 
            'defined_three_name' => $data['batch_type'], 
            'defined_four_name' => $data['share_note'], 
            'share_pic' => $share_pic);
        $batchId = $model->add($data_arr); // echo $resp_id;die;
        if (! $batchId) {
            M()->rollback();
            $this->error('电子海报创建失败！');
        }
        
        // 发布至默认电子海报渠道
        
        $channelId = M('tchannel')->where(
            array(
                "node_id" => $this->nodeId, 
                "type" => $this->CHANNEL_TYPE, 
                "sns_type" => $this->CHANNEL_SNS_TYPE, 
                "status" => "1"))->getField('id');
        $data = array(
            'batch_type' => $this->BATCH_TYPE, 
            'batch_id' => $batchId, 
            'channel_id' => $channelId, 
            'add_time' => date('YmdHis'), 
            'node_id' => $this->node_id);
        $result = M('tbatch_channel')->add($data);
        if (! $result) {
            M()->rollback();
            $this->error('系统出错,发布默认渠道失败');
        }
        
        M()->commit();
        $arr = array(
            'status' => '1', 
            'info' => '海报创建成功！', 
            'm_id' => $batchId);
        echo json_encode($arr);
        exit();
    }
    
    // 编辑提交
    public function editSubmit() {
        $id = intval(I("post.id", ''));
        $data = I("post.");
        $model = M('tmarketing_info');
        $where = array(
            'node_id' => $this->nodeId, 
            'batch_type' => $this->BATCH_TYPE, 
            'id' => $id);
        $count = $model->where($where)->count();
        if ($count < 1)
            $this->error('未找到电子海报信息!');
        
        if (empty($data['name']))
            $this->error('请填写电子海报名称！');
            /*
         * if(empty($data['start_time'])) $this->error('请填写电子海报起始时间！');
         * if(empty($data['end_time'])) $this->error('请填写电子海报结束时间！');
         * if($data['end_time'] < date('Ymd')) $this->error('活动截止日期不能小于当前日期');
         */
        $one_map = array(
            'name' => $data['name'], 
            'batch_type' => $this->BATCH_TYPE, 
            'node_id' => $this->node_id, 
            'id' => array(
                'neq', 
                $id));
        $info = $model->where($one_map)->count('id');
        if ($info > 0) {
            $this->error("电子海报名称重复");
        }
        if ($data['batch_id'] != '' && $data['batch_type'] != '') {
            $channel_id = M('tchannel')->where(
                array(
                    'node_id' => $this->node_id, 
                    'type' => $this->CHANNEL_TYPE, 
                    'sns_type' => $this->CHANNEL_SNS_TYPE))->getField('id');
            $label_id = get_batch_channel($data['batch_id'], $channel_id, 
                $data['batch_type'], $this->node_id);
        } else
            $label_id = "";
        
        $datastr = I("request.memo");
        // 分享描述
        if ($data['share_note'] == '')
            $this->error('请填写分享描述！');
            // 预览图
        if ($data['img_resp'] == '') {
            $this->error('请上传分享预览图片！');
        }
        // 移动图片
        $img_move = move_batch_image(basename($data['img_resp']), 
            $this->BATCH_TYPE, $this->node_id);
        if ($img_move !== true)
            $this->error('预览图片上传失败！' . basename($data['img_resp']), 
                array(
                    '返回' => "javascript:history.go(-1)"));
        
        $share_pic = $this->img_path . basename($data['img_resp']);
        
        // 二维码logo
        $isCodeImg = I('post.is_code_img', null);
        $respCodeImg = I('post.resp_code_img', null);
        if ($isCodeImg != '1')
            $respCodeImg = '';
        M()->startTrans();
        $data_arr = array(
            'name' => $data['name'],  // 活动名称
                                     // 'start_time'=>$data['start_time'].'000000',//活动开始时间
                                     // 'end_time'=>$data['end_time'].'235959',//活动结束时间
                                     // 'start_time'=>date('YmdHis'),
                                     // 'end_time'=>'20301231235959',
            'memo' => $datastr, 
            // 'add_time'=>date('YmdHis'),//增加时间
            // 'status'=>'1',//活动状态默认正常
            'node_name' => $data['node_name'],  // 商户名称
                                               // 'size'=>$data['size'],//生成二维码的大小
                                               // 'code_img'=>$respCodeImg,//二维码路径logo
            'defined_one_name' => $label_id, 
            'defined_two_name' => $data['batch_id'], 
            'defined_three_name' => $data['batch_type'], 
            'defined_four_name' => $data['share_note'], 
            'share_pic' => $share_pic);
        $mape = array(
            "id" => $id, 
            'node_id' => $this->node_id,  // 商户id
            'batch_type' => $this->BATCH_TYPE); // 活动类型
        
        $resp = $model->where($mape)->save($data_arr);
        if ($resp === false) {
            M()->rollback();
            $this->error('电子海报活动更新失败！', 
                array(
                    '返回电子海报' => U('index')));
        }
        
        // 更新成功
        M()->commit();
        $this->success('更新成功！', array(
            '返回电子海报' => U('index')));
        
        /*
         * $arr = array( 'status'=>'1', 'info'=>'编辑成功', 'm_id'=>$data['id'],
         * 'new_id'=>$data['new_id'], ); echo json_encode($arr); exit;
         */
    }
    
    // 编辑码上买
    public function edit() {
        // 将要编辑的活动ID
        $id = I('id', null, 'mysql_real_escape_string'); // echo "$id";die;
        if (empty($id))
            $this->error('错误参数');
        $map = array(
            "id" => $id, 
            "batch_type" => $this->BATCH_TYPE, 
            "node_id" => $this->node_id);
        $row = M("tmarketing_info")->where($map)->find();
        if (! $row) {
            $this->error('电子海报未找到');
        }
        $datastr = htmlspecialchars_decode($row['memo']);
        // 商户名称
        $nodeName = M('tnode_info')->where("node_id='{$this->node_id}'")->getField(
            'node_name');
        $this->assign('datastr', $datastr);
        $this->assign('row', $row);
        $this->assign('node_name', $nodeName);
        $this->display();
    }
    
    // 数据导出
    public function export() {
        
        // 查询条件组合
        $where = "WHERE batch_type='" . $this->BATCH_TYPE . "' ";
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
                $condition['start_time'] = $condition['start_time'] . '000000';
                $filter[] = "start_time >= '{$condition['start_time']}'";
            }
            if (isset($condition['end_time']) && $condition['end_time'] != '') {
                $condition['end_time'] = $condition['end_time'] . '235959';
                $filter[] = "end_time <= '{$condition['end_time']}'";
            }
        }
        if (! empty($filter)) {
            $condition = implode(' AND ', $filter);
            $where .= ' AND ' . $condition;
        }
        
        $filter[] = "batch_type=" . $this->BATCH_TYPE;
        $filter[] = "node_id in({$this->nodeIn()})";
        $count = M('tmarketing_info')->where($filter)->count();
        if ($count <= 0)
            $this->error('无订单数据可下载');
        
        $sql = "SELECT name,add_time,start_time,end_time,
		CASE status WHEN '1' THEN '正常' ELSE '停用' END status,
		click_count
		FROM
		tmarketing_info {$where} AND node_id in({$this->nodeIn()})";
        $cols_arr = array(
            'name' => '活动名称', 
            'add_time' => '添加时间', 
            'start_time' => '活动开始时间', 
            'end_time' => '活动结束时间', 
            'status' => '状态', 
            'click_count' => '访问量');
        if (querydata_download($sql, $cols_arr, M()) == false) {
            $this->error('下载失败');
        }
    }
    
    // 状态修改
    public function editBatchStatus() {
        $batchId = I('post.batch_id', null, 'intval');
        $status = I('post.status', null);
        if (is_null($batchId) || is_null($status)) {
            $this->error('缺少必要参数');
        }
        $result = M('tmarketing_info')->where(
            "node_id in ({$this->nodeIn()}) AND id='{$batchId}'")->find();
        if (! $result) {
            $this->error('未找到该活动');
        }
        if ($status == '1') {
            $data = array(
                'status' => '1');
        } else {
            $data = array(
                'status' => '2');
        }
        $result = M('tmarketing_info')->where("id='{$batchId}'")->save($data);
        if ($result) {
            node_log('投票活动状态更改|活动ID：' . $batchId);
            $this->success('更新成功', 
                array(
                    '返回' => U('Vote/index')));
        } else {
            $this->error('更新失败');
        }
    }
    // 状态修改
    public function editStatus() {
        $orderId = I('order_id', null);
        if (is_null($orderId)) {
            $this->error('缺少订单号');
        }
        $result = M('ttg_order_info')->where("order_id='{$orderId}'")->find();
        if (! $result) {
            $this->error('未找到订单信息');
        }
        if ($this->isPost()) {
            $status = I('post.status', null);
            if (is_null($status)) {
                $this->error('缺少配送状态');
            }
            $data = array(
                'order_id' => $orderId, 
                'delivery_status' => $status);
            $result = M('ttg_order_info')->save($data);
            if ($result) {
                $message = array(
                    'respId' => 0, 
                    'respStr' => '更新成功');
                $this->success($message);
            } else {
                $this->error('更新失败');
            }
        }
        
        $deliveryStatus = array(
            '1' => '待配送', 
            '2' => '配送中', 
            '3' => '已配送');
        $this->assign('deliveryStatus', $deliveryStatus);
        $this->assign('order_id', $result['order_id']);
        $this->assign('delovery_status', $result['delivery_status']);
        $this->display();
    }

    public function winningExport() {
        $batchId = I('batch_id', null, 'mysql_real_escape_string');
        $status = I('status', null, 'mysql_real_escape_string');
        if (is_null($batchId))
            $this->error('缺少参数');
        $sql = "SELECT mobile,add_time,
		CASE status WHEN '1' THEN '未中奖' ELSE '中奖' END status,prize_level
		FROM
		tcj_trace WHERE batch_id='{$batchId}' AND batch_type=5 AND node_id in({$this->nodeIn()})
		ORDER by status DESC";
        $countSql = "SELECT COUNT(*) as count FROM tcj_trace WHERE batch_id='{$batchId}' AND batch_type=5 AND node_id in({$this->nodeIn()})";
        $cols_arr = array(
            'mobile' => '手机号', 
            'add_time' => '中奖时间', 
            'status' => '是否中奖', 
            'prize_level' => '奖品等级');
        // 获取活动名称
        $batchName = M('tmarketing_info')->where(
            "id='{$batchId}' AND node_id in({$this->nodeIn()})")->getField(
            'name');
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

    public function removePosterData() {
        $this->_removePosterDraft();
        $this->ajaxReturn(array(
            'status' => 1));
    }

    private function checkPostData() {
        $id = I('m_id', '');
        $data = I("post.", '', 'mysql_real_escape_string');
        $draft = json_encode($data);
        $where = array(
            'node_id' => $this->node_id, 
            'type' => 1);
        $hasDraft = M('tdraft')->where($where)->find();
        $draftOk = '';
        if ($hasDraft) {
            $draftOk = M('tdraft')->where($where)->save(
                array(
                    'content' => $draft, 
                    'add_time' => date('Y-m-d H:i:s')));
        } else {
            $draftOk = M('tdraft')->add(
                array(
                    'node_id' => $this->node_id, 
                    'content' => $draft, 
                    'add_time' => date('Y-m-d H:i:s'), 
                    'type' => 1));
        }
        $auto = I('get.auto', '');
        if ($auto) {
            if ($draftOk) {
                $this->ajaxReturn(
                    array(
                        'status' => '1'));
            } else {
                $this->ajaxReturn(
                    array(
                        'status' => '-1'));
            }
        }
        if (empty($data['title'])) {
            $this->error('请填写活动名称！', '', true);
        }
        $one_map = array(
            'name' => $data['title'], 
            'batch_type' => $this->BATCH_TYPE, 
            'node_id' => $this->node_id);
        if ($id) { // 如果是修改的话,需要查除了这个活动之外有没有重复的名字
            $one_map['id'] = array(
                'neq', 
                $id);
        }
        $activity_quantity = M('tmarketing_info')->where($one_map)->count('id');
        if ($activity_quantity > 0) {
            $this->error("活动名称重复", '', true);
        }
        $readyData = $this->_constructModule($data);
        
        if ($readyData['page_tpl_params'] == array()) {
            $this->error('至少添加一页');
        }
        return $readyData;
    }

    private function _assignTpl($mInfo) {
        $memo = json_decode($mInfo['memo'], true);
        $model = M('tmarketing_templet_ext');
        $result = $model->field(array(
            'page_content'))
            ->where(array(
            'm_id' => $mInfo['id']))
            ->order('page_number asc')
            ->select();
        $data = array();
        foreach ($result as $value) {
            $data[] = json_decode($value['page_content'], true);
        }
        $memo['list'] = $data;
        $module = json_encode($memo);
        $this->assign('module', $module);
        $this->assign('batch_id', $memo['batch_id']);
        $this->assign('batch_type', $memo['batch_type']);
        $this->assign('m_id', $mInfo['id']);
        $this->assign('activity_name', $mInfo['name']);
    }

    /**
     *
     * @param int $mId marketingInfo的id
     * @param array $content
     * @param boolean $needSelect 是否需要在marketingInfo查一下原来的记录
     */
    private function _updateMarketingTpl($mId, $content, $needSelect = false) {
        $model = M('tmarketing_templet_ext');
        $page_number_arr = $model->where(
            array(
                'm_id' => $mId))->getField('page_number', true);
        foreach ($content as $key => $value) {
            $t_id = str_replace('page', '', $value['page']); // 模板id
            $pageContent = json_encode($value);
            if ($needSelect) {
                $page_number = $key + 1;
                $result = $model->where(
                    array(
                        'm_id' => $mId, 
                        'page_number' => ($key + 1)))->find();
                $pageContentArr = '';
                if ($result) { // 如果有这一页
                    $pageContentArr = json_decode($result['page_content'], true);
                    // 用数组排查有没有删除的页面
                    $searchResult = array_search($page_number, $page_number_arr);
                    if ($searchResult !== false) {
                        unset($page_number_arr[$searchResult]);
                    }
                }
                if ($pageContentArr) {
                    if ($pageContentArr != $value) { // 如果有这一页并且这一页的内容没更新了
                        $updateResult = $model->where(
                            array(
                                'id' => $result['id']))->save(
                            array(
                                't_id' => $t_id, 
                                'page_content' => $pageContent));
                    } else {
                        $updateResult = 1;
                    }
                } else { // 没有这一页的话就增加
                    $updateResult = $model->add(
                        array(
                            'm_id' => $mId, 
                            't_id' => $t_id, 
                            'page_content' => $pageContent, 
                            'page_number' => ($key + 1)));
                }
            } else {
                $updateResult = $model->add(
                    array(
                        'm_id' => $mId, 
                        't_id' => $t_id, 
                        'page_content' => $pageContent, 
                        'page_number' => ($key + 1)));
            }
            if (! $updateResult) {
                M()->rollback();
                $this->error('电子海报活动更新失败！');
            }
        }
        // 删除已删除的页面
        if (count($page_number_arr) > 0) {
            $delResult = $model->where(
                array(
                    'm_id' => $mId, 
                    'page_number' => array(
                        'in', 
                        $page_number_arr)))->delete();
            if (! $delResult) {
                M()->rollback();
                $this->error('电子海报活动删除失败！');
            }
        }
        $labelId = $this->getLabelIdAndTryAddLabelInfo($mId, $this->BATCH_TYPE);
        // 删除草稿
        $this->_removePosterDraft();
    }

    private function _checkIsSelfPoster($id) {
        // 通过node_id,查一下是不是他自己的活动
        $map = array(
            "id" => $id, 
            'node_id' => $this->node_id,  // 商户id
            'batch_type' => $this->BATCH_TYPE); // 活动类型
        
        $marketingInfo = $this->marketInfoModel->field(
            array(
                'id', 
                'name', 
                'memo'))
            ->where($map)
            ->find();
        if (! $marketingInfo) {
            $this->error('您没有查看此活动的权限', '', true);
        }
        return $marketingInfo;
    }

    public function updateMemo() {
        $marketingModel = $this->marketInfoModel;
        $marketingExtModel = M('tmarketing_templet_ext');
        $result = $marketingModel->where(
            array(
                'batch_type' => $this->BATCH_TYPE, 
                'memo' => array(
                    'like', 
                    "%quot%")))->select();
        if ($result) {
            M()->startTrans();
            foreach ($result as $key => $value) {
                $memo = htmlspecialchars_decode($value['memo']);
                $memo = json_decode($memo, true);
                $mainParam = $memo['module'][0];
                $list = $mainParam['list'];
                foreach ($list as $k => $v) {
                    $data = array();
                    $data['m_id'] = $value['id'];
                    $data['t_id'] = 1;
                    $page_content['imgtype'] = 0;
                    $page_content['page'] = 'page1';
                    $page_content['img'] = $v['img'];
                    $page_content['animate'] = 0;
                    $data['page_content'] = json_encode($page_content);
                    $data['page_number'] = $k;
                    $result1 = $marketingExtModel->add($data);
                    if (! $result1) {
                        M()->rollback();
                        $this->error('插入marketing_tpl_ext有误');
                    }
                }
                $mainParamNew = array();
                $mainParamNew['initialise'] = 'true';
                $mainParamNew['imgtype'] = 2;
                $mainParamNew['title'] = $value['name'] ? $value['name'] : '';
                $appPath = APP_PATH;
                $appPath = substr($appPath, 1);
                $mainParamNew['cover_img'] = C('TMPL_PARSE_STRING.__PUBLIC__') .
                     '/Image/poster/poster.png';
                $mainParamNew['share_descript'] = $value['defined_four_name'] ? $value['defined_four_name'] : '';
                $mainParamNew['share_img'] = $value['share_pic'] ? $value['share_pic'] : '';
                $mainParamNew['music'] = $mainParam['music'] ? $mainParam['music'] : '';
                $mainParamNew['urlType'] = $mainParam['urlType'];
                $mainParamNew['urlTitle'] = $mainParam['urlTitle'] ? $mainParam['urlTitle'] : '';
                $mainParamNew['loop'] = $mainParam['loop'];
                $mainParamNew['vague'] = $mainParam['vague'];
                $mainParamNew['batch_id'] = $value['defined_two_name'];
                $mainParamNew['batch_type'] = $value['defined_three_name'];
                $mainParamNew['label_id'] = $value['defined_one_name'];
                $mainParamJson = json_encode($mainParamNew);
                $result2 = $marketingModel->where(
                    array(
                        'id' => $value['id']))->save(
                    array(
                        'memo' => $mainParamJson));
                if (! $result2) {
                    M()->rollback();
                    $this->error('修改marketing_info表有误');
                }
            }
            M()->commit();
        }
    }

    private function _constructModule($data) {
        // 是否显示按钮
        if ($data['btn_switch'] == 0) {
            $data['btnTitle'] = '';
        }
        $labelId = $this->getLabelIdAndTryAddLabelInfo($data['batch_id'], $data['batch_type']);
        // 活动主参数
        $main = array(
            'initialise' => 'true', 
            "imgtype" => "2", 
            'title' => str_replace("''", "'", $data['title']), 
            'cover_img' => $data['cover_img'],  // 海报活动封面图
            'share_descript' => str_replace("''", "'", $data['share_note']), 
            'share_img' => $data['img_resp'], 
            'music' => $data['music'], 
            'btn_switch' => $data['btn_switch'],  // 有无按钮
            'btnTitle' => $data['btnTitle'], 
            'url' => ($data['urlType'] == 4) ? $data['url2'] : $data['url1'], 
            'urlType' => $data['urlType'],  // 4表示活动链接,0表示自定义链接
            'urlTitle' => $data['urlTitle'], 
            'loop' => $data['loop'], 
            'vague' => $data['vague'] ? 1 : 0, 
            'batch_id' => $data['batch_id'],  // 按钮链接的活动id
            'batch_type' => $data['batch_type'],  // 按钮链接的活动类型
            'label_id' => $labelId);
        $readyData = array(
            'memo' => json_encode($main), 
            'name' => $main['title']); // marketing_info表需要，所以再给一下
                                       
        // 处理每页的模板参数
        $total_params_arr = json_decode($data['memo'], true);
        // 先要处理一下memo数据防止里面的模板参数为空
        $list = $total_params_arr['module'][0]['list'];
        foreach ($list as $key => $value) {
            foreach ($value as $k => $v) {
                $result1 = preg_match('/title/', $k);
                $result2 = preg_match('/text/', $k);
                if ($result1 || $result2) {
                    $list[$key][$k] = str_replace("''", "'", $v);
                }
            }
        }
        $readyData['page_tpl_params'] = $list;
        return $readyData;
    }

    private function _removePosterDraft() {
        M('tdraft')->where(
            array(
                'node_id' => $this->node_id, 
                'type' => 1))->delete();
    }
}