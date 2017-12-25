<?php
// 图文编辑
class MedAction extends BaseAction {
    // 活动类型
    public $BATCH_TYPE = '19';
    // 图片路径
    public $img_path;
    
    // 初始化
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

    /**
     *
     * @return NodeInfoModel
     */
    private function getNodeInfoModel() {
        return D('NodeInfo');
    }
    // 检测当前商户是否开通积分模块
    private function checkOpenModule() {
        $nodeInfoModel = $this->getNodeInfoModel();
        $hasIntegralModule = $nodeInfoModel->getChannelField(
            array(
                'node_id' => $this->node_id), 'pay_module');
        $isOk = stristr($hasIntegralModule, 'm4');
        return $isOk;
    }
    
    // 首页 index()方法
    public function index() {
        // 实例化
        $model = M('tmarketing_info');
        $map = array(
            'node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"), 
            'batch_type' => $this->BATCH_TYPE);

        $data = I('request.',array());
        if (! empty($data['node_id']))
            $map['node_id '] = $data['node_id'];
        if (!empty($data['key'])) {
            $map['name'] = array(
                'like', 
                '%' . $data['key'] . '%');
        }
        if (!empty($data['status'])) {
            $map['status'] = $data['status'];
        }
        if (!empty($data['start_time']) && !empty($data['end_time'])) {
            $map['add_time'] = array(
                'BETWEEN', 
                array(
                    $data['start_time'] . '000000', 
                    $data['end_time'] . '235959'));
        }
        
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = $model->where($map)->count(); // 查询满足要求的总记录数
                                                  // echo $model->getLastSql();
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        
        foreach ($data as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        
        $show = $Page->show(); // 分页显示输出
        
        $list = $model->where($map)
            ->order('id desc')
            ->limit($Page->firstRow . ' , ' . $Page->listRows)
            ->select();
        // echo $model->getLastSql();
        if ($list) {
            foreach ($list as $k => $v) {
                $list[$k]['is_mem_batch'] = 'N';
                if ($v['is_cj'] == '1') {
                    $rule_id = M('tcj_rule')->where(
                        array(
                            'batch_type' => $this->BATCH_TYPE, 
                            'batch_id' => $v['id'], 
                            'node_id' => $v['node_id'], 
                            'status' => '1'))->getField('id');
                    $mem_batch = M('tcj_batch')->where(
                        array(
                            'cj_rule_id' => $rule_id, 
                            'member_batch_id' => array(
                                'neq', 
                                '')))->find();
                    
                    if ($mem_batch) {
                        $list[$k]['is_mem_batch'] = 'Y';
                    }
                }
            }
        }
        
        // 检测当前商户是否使用过图文编辑
        $hasData = array_filter($list);
        if (empty($hasData)) {
            $arr = array(
                'node_id' => array(
                    'exp', 
                    "in (" . $this->nodeIn() . ")"), 
                'batch_type' => $this->BATCH_TYPE);
            $hasData = $model->where($arr)->select();
            $hasData = array_filter($hasData);
            if (empty($hasData)) {
                $this->assign('hasV', 'noData');
            }
        }
        
        $arr_ = C('CHANNEL_TYPE');
        
        $this->assign('arr_', $arr_);
        $this->assign('query_list', $list);
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('nodeList', $this->getNodeTree());
        $this->display(); // 输出模板
    }
    
    // 添加页
    public function add() {
        $bm_type_arr = C('BM_TYPE_ARR');
        
        $nodeInfo = get_node_info($this->node_id);
        $this->assign('node_name', $nodeInfo['node_name']);
        $this->assign('node_logo', $nodeInfo['head_photo']);
        $nodeName = $nodeInfo['node_name'];
        // 检测当前商户是否开通积分模块
        $isOpenIntegral = $this->checkOpenModule();
        if ($isOpenIntegral) {
            $isOpenIntegral = true;
        } else {
            $isOpenIntegral = false;
        }
        $this->assign('isOpenIntegral', $isOpenIntegral);
        
        // 获取商户会员卡信息
        $mem_batch = M('tmember_batch')->where(
            array(
                'node_id' => $this->node_id, 
                'status' => '1'))
            ->order('member_level asc')
            ->select();
        $this->assign('mem_batch', $mem_batch);
        
        $this->assign('bm_type_arr', $bm_type_arr);
        $this->assign('node_name', $nodeName);
        $this->display();
    }
    
    // 添加提交页
    public function addSubmit() {
        $model = M('tmarketing_info');
        $data = I('post.');
        
        if (! empty($data['select_type'])) {
            $select_type = implode('-', $data['select_type']);
        } else {
            $select_type = '';
        }
        if (! empty($data['sns_type'])) {
            $sns_type = implode('-', $data['sns_type']);
        } else {
            $sns_type = '';
        }
        
        $one_map = array(
            'name' => get_scalar_val($data['name']),
            'batch_type' => $this->BATCH_TYPE, 
            'node_id' => $this->node_id);
        $info = $model->where($one_map)->count('id');
        if ($info > 0) {
            $this->error("活动名称重复");
        }
        /*
            // logo
            $log_img = '';
            if ($data['resp_log_img'] != '' && $data['is_logo_img'] == 1) {
                $log_img = $data['resp_log_img'];
            }
        */
        // 商户LOGO
        if ($data['is_log_img'] == '1') {
            if (empty($data['resp_log_img'])) { // 开启了商户LOG 并且没有上传LOG的时候
                $getNodeImg = M('tnode_info')->where(
                        array(
                                'node_id' => $this->node_id))->getField('head_photo');
                if (empty($getNodeImg)) { // 如果没有商户头像的时候
                    $log_img = C('TMPL_PARSE_STRING.__PUBLIC__') .
                            '/Image/wap-logo-wc.png';
                } else {
                    $log_img = C('TMPL_PARSE_STRING.__UPLOAD__') .
                            '/' . $getNodeImg;
                }
            } else {
                $imgUrl = C('TMPL_PARSE_STRING.__UPLOAD__').'/';
                $data['resp_log_img'] = str_replace($imgUrl, '', $data['resp_log_img']);
                // 使用商户上传的LOG
                $log_img = C('TMPL_PARSE_STRING.__UPLOAD__') . '/' .
                        $data['resp_log_img'];
            }
        } elseif ($data['is_log_img'] == '0') { // 关闭的时候清空字段值
            $log_img = '';
        }
        // 背景
        $bg_img = '';
        if ($data['resp_bg_img'] != '') {
            /*
             * $img_move = move_batch_image($data['resp_bg_img'],
             * $this->BATCH_TYPE, $this->node_id); if ($img_move !== true)
             * $this->error('背景图片上传失败！');
             */
            $bg_img = str_replace('..', '', $data['resp_bg_img']);
        }
        // 积分
        $data['jifen'] = array(
            'integral_sign' => 0, 
            'integral_num' => 0);
        if (! empty($data['integral'])) {
            $data['jifen'] = array(
                'integral_sign' => 1, 
                'integral_num' => $data['integral']);
        }
        // 保存单一颜色到大字段里去
        if ($data['page_style'] == 5) {
            // bg_color 单一背景色的名称
            $data['jifen']['bg_color'] = $data['card_color'];
        }
        // 保存微信分享设置
        if (! empty($data['share_title'])) {
            $data['jifen']['share_title'] = $data['share_title'];
        }
        if (! empty($data['share_introduce'])) {
            $pureStr = str_replace(' ', '', $data['share_introduce']);
            $pureStr = str_replace(PHP_EOL, '', $pureStr);
            $data['jifen']['share_introduce'] = $pureStr;
        }
        
        $data_arr = array(
            // 'name'=>$data['name'],
            // 设置 “名称”等于“标题”
            'name' => $data['wap_title'], 
            'color' => get_scalar_val($data['color']),
            'wap_title' => $data['wap_title'], 
            'wap_info' => $data['wap_info'], 
            'log_img' => $log_img, 
            // 'start_time'=>substr($data['start_time'],0,8).'000000',
            // 活动开始时间
            'start_time' => '20140429000000', 
            // 'end_time'=>substr($data['end_time'],0,8).'235959',
            // 活动结束时间
            'end_time' => '20241231235959', 
            'memo' => get_scalar_val($data['memo']),
            'select_type' => $select_type, 
            'node_id' => $this->node_id, 
            'add_time' => date('YmdHis'), 
            'status' => '1', 
            'pay_status' => '1', 
            // 'size'=>$data['size'],
            // 'code_img'=>$data['resp_code_img'],
            'sns_type' => $sns_type, 
            'chance' => get_scalar_val($data['chance']),
            'goods_count' => get_scalar_val($data['goods_count']),
            'day_goods_count' => get_scalar_val($data['day_goods_count']),
            'is_cj' => get_scalar_val($data['is_cj']),
            'jp_type' => get_scalar_val($data['jp_type']),
            'zc_batch_no' => get_scalar_val($data['zc_batch_no']),
            'wc_batch_no' => get_scalar_val($data['wc_batch_no']),
            'defined_one_name' => get_scalar_val($data['defined10']),
            'defined_two_name' => get_scalar_val($data['defined11']),
            'defined_three_name' => get_scalar_val($data['defined12']),
            'node_name' => get_scalar_val($data['node_name']),
            'page_style' => get_scalar_val($data['page_style']),
            'bg_style' => $data['bg_style'],
            'bg_pic' => $bg_img, 
            'batch_type' => $this->BATCH_TYPE, 
            'is_show' => '1', 
            'config_data' => serialize($data['jifen']));
        // 微信分享的图标
        if (! empty($data['share_pic'])) {
            $data_arr['share_pic'] = $data['share_pic'];
        }
        
        $resp_id = $model->add($data_arr);
        // 顺便发布到多乐互动专用渠道上
        $bchId = D('MarketCommon')->chPublish($this->nodeId,$resp_id);
        if($bchId === false){
            $this->error('发布到渠道失败');
        }
        // 如果$resp_id没有值，创建失败
        if (! $resp_id) {
            $this->ajaxReturn(
                array(
                    'status' => 0, 
                    'info' => '创建失败'), 'JSON');
        }
        $this->ajaxReturn(
            array(
                'status' => 1, 
                'info' => $resp_id), 'JSON');
    }
    
    // 编辑页
    public function edit() {
        $model = M('tmarketing_info');
        $id = $this->_param('id');
        if (empty($id))
            $this->error('错误参错');
        
        $map = array(
            'node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"), 
            'id' => $id);
        $query_arr = $model->where($map)->find();
        if (empty($query_arr)) {
            $this->error('错误查询');
        }
        
        if ($query_arr['log_img'] == '') {
            $nodeInfo = get_node_info($this->node_id);
            $this->assign('head_photo', get_upload_url($nodeInfo['head_photo']));
            $this->assign('node_logo', $nodeInfo['head_photo']);
        }
        
        $tquestion = M("tanswers_question");
        $tquestion_info = M("tanswers_question_info");
        $reuslt = (array)$tquestion->where("label_id = '" . $id . "'")
            ->order("sort")
            ->select();
        foreach ($reuslt as $k => $v) {
            $answers_info = $tquestion_info->where(
                "question_id = '" . $v['id'] . "'")
                ->order()
                ->select();
            if ($answers_info !== false) {
                $reuslt[$k]['answers_list'] = $answers_info;
            }
        }
        $cjarr = array();
        $cjbatch_arr = array();
        $batch_model = M('tbatch_info');
        $batch_name_arr = array();
        if ($query_arr['is_cj'] == '1') {
            $cjrule = M('tcj_rule');
            $map1 = array(
                'node_id' => $query_arr['node_id'], 
                'batch_id' => $query_arr['id'], 
                'batch_type' => $this->BATCH_TYPE, 
                'status' => '1');
            $cjarr = $cjrule->where($map1)->find();
            $map1 = array(
                'node_id' => $query_arr['node_id'], 
                'batch_id' => $query_arr['id'], 
                'batch_type' => $this->BATCH_TYPE, 
                'cj_rule_id' => $cjarr['id']);
            $cjbatch = M('tcj_batch');
            $cjbatch_arr = $cjbatch->where($map1)
                ->order('award_level asc')
                ->select();
            
            if ($cjbatch_arr) {
                $batch_map = array(
                    'node_id' => $query_arr['node_id']);
                foreach ($cjbatch_arr as $v) {
                    $batch_map['batch_no'] = $v['activity_no'];
                    $batch_name_arr[$v['activity_no']] = $batch_model->where(
                        $batch_map)->getField('batch_short_name');
                }
            }
        }
        // 检测当前商户是否开通积分模块
        $isOpenIntegral = $this->checkOpenModule();
        if ($isOpenIntegral) {
            $isOpenIntegral = true;
        } else {
            $isOpenIntegral = false;
        }
        $this->assign('isOpenIntegral', $isOpenIntegral);
        // 积分和背景色
        $serData = unserialize($query_arr['config_data']);
        $this->assign('serData', $serData);
        
        // 获取商户会员卡信息
        $mem_batch = M('tmember_batch')->where(
            array(
                'node_id' => $query_arr['node_id'], 
                'status' => '1'))
            ->order('member_level asc')
            ->select();
        $this->assign('mem_batch', $mem_batch);
        $this->assign('cjarr', $cjarr);
        $this->assign('cjbatch_arr', $cjbatch_arr);
        $this->assign('batch_name_arr', $batch_name_arr);
        $this->assign('question_row', $reuslt);
        $this->assign('row', $query_arr);
        $this->display();
    }
    
    // 编辑提交页
    public function editSubmit()
    {
        $model = M('tmarketing_info');
        $data  = I('post.');

        if (!empty($data['select_type'])) {
            $select_type = implode('-', $data['select_type']);
        } else {
            $select_type = '';
        }
        if (!empty($data['sns_type'])) {
            $sns_type = implode('-', $data['sns_type']);
        } else {
            $sns_type = '';
        }

        $edit_wh = array(
                'node_id' => array(
                        'exp',
                        "in (" . $this->nodeIn() . ")"
                ),
                'id'      => $data['id']
        );
        $markInfo = $model->where($edit_wh)->field('node_id, config_data')->find();
        if (!$markInfo) {
            $this->error('未查询到记录');
        }

        $one_map = array(
                'name'       => get_val($data,'name'),
                'batch_type' => $this->BATCH_TYPE,
                'node_id'    => $markInfo['node_id'],
                'id'         => array(
                        'neq',
                        get_val($data,'id')
                )
        );

        $info_ = $model->where($one_map)->count('id');
        if ($info_ > 0) {
            $this->error("活动名称重复");
        }

        $bm_id = $data['id'];
        $map   = array(
                'node_id' => $markInfo['node_id'],
                'id'      => $data['id']
        );

        // 背景图
        if ($data['resp_bg_img'] != '' && $data['reset_bg'] == '1') {
            /*
             * $img_move = move_batch_image($data['resp_bg_img'],
             * $this->BATCH_TYPE, $this->node_id); if ($img_move !== true)
             * $this->error('背景图片上传失败??！' . $img_move); $bg_img =
             * $this->img_path . $data['resp_bg_img'];
             */

            $bg_img = str_replace('..', '', get_val($data,'resp_bg_img'));
        } else {
            $bg_img = $data['resp_bg_img'];
        }
        //获取商品已存在信息
        $serData = unserialize($markInfo['config_data']);
        if(!$serData){
            $serData = array();
        }
        // 修改积分
        $serData['integral_sign'] = 0;
        $serData['integral_num'] = 0;
        
        if (!empty($data['integral'])) {
            $configData = $model->where($edit_wh)->getField('config_data');
            $serData    = unserialize($configData);
            if (empty($serData)) {
                $serData = array(
                        'integral_sign' => 1,
                        'integral_num'  => $data['integral']
                );
            } else {
                $serData['integral_num'] = $data['integral'];
            }
        }
        // 修改单一颜色到大字段里去
        if ($data['page_style'] == 5) {
            // bg_color 单一背景色的名称
            $serData['bg_color'] = $data['card_color'];
        }
        // 保存微信分享设置
        if (!empty($data['share_title'])) {
            $serData['share_title'] = $data['share_title'];
        }
        if (!empty($data['share_introduce'])) {
            $pureStr                    = str_replace(' ', '', $data['share_introduce']);
            $pureStr                    = str_replace(PHP_EOL, '', $pureStr);
            $serData['share_introduce'] = $pureStr;
        }
        //新版电商展示页面标识
        $serData['isShowAd'] = 1;
        $serData['isShowElAd'] = 0;

        $data_arr = array(
            // 'name'=>$data['name'],
            // 'color'=>$data['color'],
            // 'wap_title'=>$data['wap_title'],
            // 'wap_info'=>$data['wap_info'],
            // 'start_time'=>substr($data['start_time'],0,8).'000000',
            // 'end_time'=>substr($data['end_time'],0,8).'235959',
            // 'name'=>$data['name'],
            // 设置 “名称”等于“标题”
            'name' => get_val($data,'wap_title'),
            'color' => get_val($data,'color'),
            'wap_title' => get_val($data,'wap_title'),
            'wap_info' => get_val($data,'wap_info'),
            // 'start_time'=>substr($data['start_time'],0,8).'000000',
            // 活动开始时间
            'start_time' => '20140429000000',
            // 'end_time'=>substr($data['end_time'],0,8).'235959',
            // 活动结束时间
            'end_time' => '20241231235959',
            'memo' =>  get_val($data,'memo'),
            'select_type' => $select_type,
            // 'size'=>$data['size'],
            // 'code_img'=>$data['resp_code_img'],
            'sns_type' => $sns_type,
            'defined_one_name' => get_val($data,'defined10'),
            'defined_two_name' => get_val($data,'defined11'),
            'defined_three_name' => get_val($data,'defined12'),
            'node_name' => get_val($data,'node_name'),
            'page_style' => get_val($data,'page_style'),
            'bg_style' => get_val($data,'bg_style'),
            'bg_pic' => $bg_img,
            'is_show' => '1',
            'config_data' => serialize($serData));
        // 微信分享的图标
        if (! empty($data['share_pic'])) {
            $data_arr['share_pic'] = $data['share_pic'];
        }

//        $code_img = $data['resp_code_img'];
        $music = get_val($data,'resp_music');
        // 商户名称
        if ($data['node_name_radio'] == '0' || empty($data['node_name'])) {
            $data_arr['node_name'] = '';
        } else {
            $data_arr['node_name'] = $data['node_name'];
        }

        // 商户LOGO
        if ($data['is_log_img'] == '1') {
            if (empty($data['resp_log_img'])) { // 开启了商户LOG 并且没有上传LOG的时候
                $getNodeImg = M('tnode_info')->where(
                    array(
                        'node_id' => $this->node_id))->getField('head_photo');
                if (empty($getNodeImg)) { // 如果没有商户头像的时候
                    $imgUrl = C('TMPL_PARSE_STRING.__PUBLIC__') .
                         '/Image/wap-logo-wc.png';
                    $data_arr['log_img'] = $imgUrl;
                } else {
                    $data_arr['log_img'] = C('TMPL_PARSE_STRING.__UPLOAD__') .
                         '/' . $getNodeImg;
                }
            } else {
                $imgUrl = C('TMPL_PARSE_STRING.__UPLOAD__').'/';
                $imgUrl1 = C('TMPL_PARSE_STRING.__PUBLIC__').'/';
                //无上传前缀路径的
                if(!stristr($data['resp_log_img'],$imgUrl) && !stristr($data['resp_log_img'],$imgUrl1)){
                    $data_arr['log_img'] = C('TMPL_PARSE_STRING.__UPLOAD__') . '/' . $data['resp_log_img'];
                }else{
                    //商户上传的图片
                    if(stristr($data['resp_log_img'],$imgUrl)) {
                        $data['resp_log_img'] = str_replace($imgUrl, '', $data['resp_log_img']);
                        // 使用商户上传的LOG
                        $data_arr['log_img'] = C('TMPL_PARSE_STRING.__UPLOAD__') . '/' . $data['resp_log_img'];
                    }

                    //旺财LOGO
                    if(stristr($data['resp_log_img'],$imgUrl1)){
                        $data['resp_log_img'] = str_replace($imgUrl1, '', $data['resp_log_img']);
                        // 使用商户上传的LOG
                        $data_arr['log_img'] = C('TMPL_PARSE_STRING.__PUBLIC__') . '/'.$data['resp_log_img'];
                    }
                }
            }
        } else { // 关闭的时候清空字段值
            $data_arr['log_img'] = '';
        }

        // 音乐
        if (get_val($data,'is_music') == '1' && ! empty($music)) {
            $data_arr['music'] = $music;
        } else {
            if (get_val($data,'is_music') == '0') {
                $data_arr['music'] = '';
            }
        }

        $tranDb = new Model();

        $resp_id = $model->where($map)->save($data_arr);
        
        if ($resp_id === false) {
            $tranDb->rollback();
            $this->ajaxReturn(
                array(
                    'status' => 0,
                    'info' => '更新失败'), 'JSON');
        }
        $this->ajaxReturn(
            array(
                'status' => 1,
                'info' => '更新成功'), 'JSON');
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

    public function BmCountExport() {
        set_time_limit(0);
        $batchId = I('get.batch_id', null, 'intval');
        if (is_null($batchId))
            $this->error('缺少参数');
        
        $edit_wh = array(
            'node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"), 
            'id' => $batchId);
        $node_id = M('tmarketing_info')->where($edit_wh)->getField('node_id');
        if (! $node_id)
            $this->error('未查询到记录！');
        
        $bm_arr = array(
            '1' => '姓名', 
            '2' => '联系方式', 
            '3' => '性别', 
            '4' => '年龄', 
            '5' => '学历', 
            '6' => '收信地址', 
            '7' => '邮箱', 
            '8' => '公司名称', 
            '9' => '职位');
        $get_bm_arr = array(
            '1' => 'true_name', 
            '2' => 'mobile', 
            '3' => 'sex', 
            '4' => 'age', 
            '5' => 'edu', 
            '6' => 'address', 
            '7' => 'email', 
            '8' => 'company', 
            '9' => 'position', 
            '10' => 'defined_one', 
            '11' => 'defined_two', 
            '12' => 'defined_three');
        $type_arr = array(
            '1' => '单选', 
            '2' => '多选', 
            '3' => '问答');
        $d_query_arr = M('tmarketing_info')->field(
            'name,select_type,defined_one_name,defined_two_name,defined_three_name')
            ->where("id='{$batchId}' AND node_id='{$node_id}'")
            ->find();
        if (! $d_query_arr)
            $this->error('未找到该活动');
        $arr = explode('-', $d_query_arr['select_type']);
        $column_arr = array();
        foreach ($arr as $v) {
            if ($v == '10') {
                $column_arr[($get_bm_arr[$v])] = $d_query_arr['defined_one_name'];
            } elseif ($v == '11') {
                $column_arr[($get_bm_arr[$v])] = $d_query_arr['defined_two_name'];
            } elseif ($v == '12') {
                $column_arr[($get_bm_arr[$v])] = $d_query_arr['defined_three_name'];
            } else {
                $column_arr[($get_bm_arr[$v])] = $bm_arr[$v];
            }
        }
        
        $search_str = 'id,add_time,label_id,node_id,' . implode(',', 
            $get_bm_arr);
        
        $fileName = $d_query_arr['name'] . '-' . date('Y-m-d') . '-答题内容.csv';
        $fileName = iconv('utf-8', 'gb2312', $fileName);
        
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        
        $start_num = 0;
        $page_count = 5000;
        for ($j = 1; $j < 100; $j ++) {
            // 查询调查问卷流水数据
            $query = M('tanswers_trace')->field("{$search_str}")
                ->where("label_id='{$batchId}' AND node_id='{$node_id}'")
                ->order('id')
                ->limit(($j - 1) * $page_count, 5000)
                ->select();
            
            if (! $query)
                exit();
            
            $i = 0;
            // 标题
            foreach ($column_arr as $k => $v) {
                $excel_title .= $v . ",";
                $i ++;
            }
            $excel_title .= '日期' . ",";
            // 是否有问卷
            $row = M('tanswers_question')->where("label_id='{$batchId}'")->select();
            if ($row) {
                foreach ($row as $q_v) {
                    $excel_title .= $q_v['questions'] . '(' .
                         $type_arr[$q_v['type']] . ')' . ",";
                }
            }
            $excel_title = substr($excel_title, 0, - 1);
            $excel_title = iconv('utf-8', 'gbk', $excel_title);
            echo $excel_title . "\r\n";
            // 内容
            foreach ($query as $vv) {
                $excel_note = '';
                foreach ($column_arr as $k => $v) {
                    $excel_note .= $vv[$k] . ",";
                }
                $excel_note .= dateformat($vv['add_time'], 'Y-m-d') . ",";
                foreach ($row as $q_v) {
                    
                    if (empty($row))
                        break;
                    $res = M('tanswers_question_stat')->where(
                        "bm_seq_id={$vv['id']} AND question_id={$q_v['id']}")->find();
                    if ($res) {
                        $excel_note .= $res['answer_list'] . ",";
                    } else {
                        $excel_note .= "\t";
                    }
                }
                $excel_note = iconv('utf-8', 'gbk', $excel_note);
                echo $excel_note . "\r\n";
            }
        }
        exit();
    }

    public function editStatus() {
        $data = I('post.');
        $baseActivityModel = D('BaseActivity', 'Service');
        $result = $baseActivityModel->changeTheActivityStatus($data, 
            $this->BATCH_TYPE, $this->nodeIn());
        
        if ($result) {
            node_log('有奖答题活动状态更改|活动ID：' . $data['batch_id']);
            $this->success('更新成功', 
                array(
                    '返回' => U('Answers/index')));
        } else {
            $this->error('更新失败');
        }
    }
}
?>