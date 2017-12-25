<?php

class IndexAction extends BaseAction {

    const BATCH_TYPE_MICROWEB = 13;

    const BATCH_CHANNEL_TYPE_MICROWEB = 4;

    const BATCH_SNS_TYPE_MICRFOWEB = 43;

    const LABEL_AIPAI_TYPE_MICROWEB = 3;

    const LABEL_AIPAI_SNS_TYPE_MICROWEB = 36;

    public $upload_path;

    public $maxSize;

    public $errormsg = '';

    public $imgurl = '';

    public $allowExts = '';

    public function _initialize() {
        $this->maxSize = 1024 * 1024;
        $this->allowExts = array(
            '');
        parent::_initialize();
        $this->upload_path = './Home/Upload/MicroWebImg/' . $this->node_id . '/';
        $this->assign('BATCH_TYPE_MICROWEB', self::BATCH_TYPE_MICROWEB);
        $this->assign('BATCH_SNS_TYPE_MICRFOWEB', 
            self::BATCH_SNS_TYPE_MICRFOWEB);
    }
    
    // 获取营销活动列表
    public function index() {
        $model = M('tmarketing_info');
        $map = array(
            'node_id' => $this->node_id, 
            'batch_type' => self::BATCH_TYPE_MICROWEB);
        
        $list = $model->where($map)
            ->limit('1')
            ->select();
        
        $channel_id = $this->addchannel(); // 微官网默认渠道
                                           // 获取展示页面的label_id
        foreach ($list as &$v) {
            $v['label_id'] = get_batch_channel($v['id'], $channel_id, 
                self::BATCH_TYPE_MICROWEB, $this->node_id);
        }
        
        $this->assign('list', $list);
        $this->display(); // 输出模板
    }
    
    // 新增微官网和营销活动
    public function add() {
        $data = I('request.');
        // 配置默认营销活动和渠道
        $batch_arr = $this->addbatch(); // 微官网默认营销活动
        $channel_id = $this->addchannel(); // 微官网默认渠道
                                           // $label_id=$this->addaipaichannel();//爱拍微官网渠道
                                           // 用于获取跳转的label_id
        $model = M('tmicroweb_tpl_cfg');
        
        $map = array(
            'node_id' => $this->node_id);
        
        // 获取营销活动号
        if ($data['mw_batch_id']) {
            $map['mw_batch_id'] = $data['mw_batch_id'];
        } else {
            $map['mw_batch_id'] = $batch_arr['batch_id'];
        }
        
        /* DF逻辑处理 */
        $is_df = (C('df.node_id') == $this->node_id) ? true : false;
        if ($is_df) {
            /* 判断是否存在固定的模块 如果没有，就插入 */
            $map_select = $map;
            $map_select['is_fixed'] = 1;
            $count = $model->where($map_select)->count();
            if ($count == 0) {
                $map_select['link_type'] = 1; /* 链接类型 0营销活动1手工地址2图文链接 */
                $map_select['title'] = "DF门店";
                $map_select['link_url'] = C('CURRENT_DOMAIN') .
                     U('Df/DfWap/Dfstore_select');
                $map_select['tpl_id'] = $batch_arr['tpl_id'];
                $map_select['field_id'] = 2;
                $map_select['field_type'] = 2;
                $map_select['field_img_name'] = "df_store_ico.png";
                $map_select['field_img_color'] = "colorVal-4";
                $map_select['image_name'] = C('TMPL_PARSE_STRING.__PUBLIC__') .
                     '/Image/df/df_store_default.png';
                $map_select['add_time'] = date('YmdHis');
                $map_select['edit_time'] = date('YmdHis');
                $query = $model->add($map_select);
            }
        }
        
        $nodeInfo = get_node_info($this->node_id);
        $this->assign('head_photo', get_upload_url($nodeInfo['head_photo']));
        $this->assign('node_logo', $nodeInfo['head_photo']);
        $list = $model->where($map)
            ->order('order_id')
            ->select();
        
        $node_level = $this->getNodeLevel();
        $bg_type = $model->where(
            array(
                'field_type' => '4', 
                'node_id' => $this->node_id, 
                'tpl_id' => $batch_arr['tpl_id'], 
                'mw_batch_id' => $batch_arr['batch_id']))->getField('link_type');
        $appdesc = $model->where(
            array(
                'node_id' => $this->node_id, 
                'field_type' => '0', 
                'mw_batch_id' => $batch_arr['batch_id']))->find();
        $content = $model->where(
            array(
                'node_id' => $this->node_id, 
                'field_type' => '3', 
                'mw_batch_id' => $batch_arr['batch_id']))
            ->order('id desc')
            ->find();
        $sns_number = json_decode($content['content'], true);
        /*
         * //获取营销活动信息 $map['batch_type']=self::BATCH_TYPE_MICROWEB; $batch_info
         * = M('tmarketing_info')->where($map)->find();
         */
        // 获取展示页面的label_id
        $label_id = $this->get_batch_channel($map['mw_batch_id'], $channel_id, 
            '13');

        $list = $this->get_cfg_array($list);
        $orderByArr = array();
        foreach($list[1] as $key => $value){
            $orderByArr[$key]=$value['add_time'];
        }
        for($i=0; $i<count($orderByArr); $i++){
            array_multisort($orderByArr, SORT_ASC, $list[1]);
        }
        $this->assign('list',$list);
        $this->assign('mw_batch_id', $map['mw_batch_id']);
        $this->assign('click_count', $batch_arr['click_count']);
        $this->assign('channel_id', $channel_id);
        $this->assign('label_id', $label_id);
        $this->assign('batch_name', $batch_arr['batch_name']);
        $this->assign('tpl_id', $batch_arr['tpl_id']);
        $this->assign('bg_type', $bg_type);
        $this->assign('node_level', $node_level);
        $this->assign('appdesc', $appdesc['appdesc']);
        $this->assign('sns5', $sns_number['sns']['sns_5']);
        $this->assign('is_df', 
            (C('df.node_id') == $this->node_id) ? true : false);
        $this->assign('midNational', 
            (C('midNational.node_id') == $this->node_id) ? true : false);
        $this->assign('node_name', $batch_arr['node_name']);
        $this->display(); // 输出模板
    }
    
    // 点击保存统一调用函数
    public function Submit() {
        $data = I('request.');
        $this->checkrule($data);
        $map = array(
            'node_id' => $this->node_id, 
            'id' => $data['mw_batch_id'], 
            'batch_type' => self::BATCH_TYPE_MICROWEB);
        $data['tpl_id'] = M('tmarketing_info')->where($map)
            ->limit('1')
            ->getField('tpl_id');
        if ($data['id']) {
            $this->editSubmit($data);
        } else {
            $this->addSubmit($data);
        }
    }
    
    // 新增模版提交
    public function addSubmit($data) {
        $model = M('tmicroweb_tpl_cfg');
        $batch_model = M('tmarketing_info');
        $channel_model = M('tchannel');
        $onemap = array(
            'node_id' => $this->node_id, 
            'type' => self::BATCH_CHANNEL_TYPE_MICROWEB, 
            'sns_type' => self::BATCH_SNS_TYPE_MICRFOWEB);
        if (! $data['image_name']) {
            if ($data['tpl_id'] == '1' || $data['tpl_id'] == '5' ||
                 $data['tpl_id'] == '6' || $data['tpl_id'] == '7') {
                if ($data['field_type'] != '0') {
                    $this->error('请上传图片');
                }
            } elseif ($data['tpl_id'] == '2' || $data['tpl_id'] == '3' ||
                 $data['tpl_id'] == '4' || $data['tpl_id'] == '9') {
                if ($data['field_type'] != '0' && $data['field_type'] != '2') {
                    $this->error('请上传图片');
                }
            }
        }
        
        // 判断C0是否有权限编辑分享配置
        if (($data['phone'] || $data['sns_1'] || $data['sns_2'] || $data['sns_3'] ||
             $data['sns_4']) && $data['node_level'] == 'C0' &&
             $data['sns_cfg_flag'] == "true") {
            $this->error("机构账户权限不足，请先提升权限");
        }
        
        // 开启事务
        M()->startTrans();
        // 更新3 分享配置信息
        if ($data['sns_cfg_flag'] == "true") {
            $cfg_arr = array(
                'sns' => array(
                    'phone' => $data['phone'], 
                    'sns_1' => $data['sns_1'], 
                    'sns_2' => $data['sns_2'], 
                    'sns_3' => $data['sns_3'], 
                    'sns_4' => $data['sns_4'], 
                    'sns_5' => $data['sns_5']));
            $cfg_data = array(
                'content' => json_encode($cfg_arr), 
                'appdesc' => $data['appdesc']);
            if ($data['sns_cfg_id']) { // 更新
                $cfg_map = array(
                    'node_id' => $this->node_id, 
                    'field_id' => '3', 
                    'field_type' => '3', 
                    'tpl_id' => $data['tpl_id'], 
                    'mw_batch_id' => $data['mw_batch_id'], 
                    'id' => $data['sns_cfg_id']);
                
                $query = $model->where($cfg_map)->save($cfg_data);
                
                if ($query === false) {
                    M()->rollback();
                    $this->error('经典模版3号位置分享配置保存失败');
                } else {
                    node_log('经典模版编辑成功|位置编号:3');
                }
            } else { // 插入
                $cfg_data['node_id'] = $this->node_id;
                $cfg_data['tpl_id'] = $data['tpl_id'];
                $cfg_data['field_id'] = '3';
                $cfg_data['field_type'] = '3';
                $cfg_data['add_time'] = date('YmdHis');
                $cfg_data['mw_batch_id'] = $data['mw_batch_id'];
                $query = $model->add($cfg_data);
                if ($query) {
                    node_log('经典模版分享配置添加成功|机构号:' . $this->node_id);
                } else {
                    M()->rollback();
                    $this->error('经典模版添加失败1');
                }
            }
        }
        // 更新012等位置数据
        // 获取存在的营销活动的id
        $batch_id = $data['batch_id'];
        $batch_type = $data['batch_type'];
        // 再判断渠道号
        $channel_id = $channel_model->where($onemap)
            ->limit('1')
            ->getField('id');
        if (! ($data['tpl_id'] == '3' && $data['field_type'] == '2') &&
             $data['image_name']) {
            // 非模版3field_type2的情况 需移动图片
            // 0位置独立函数
            $image_name = $data['image_name'];
        }
        // 最后根据位置编号添加模版数据
        if ($data['field_type'] == '1') { // 1号位 3个张图轮换
            $cfgmap = array(
                'node_id' => $this->node_id, 
                'field_id' => $data['field_id'], 
                'tpl_id' => $data['tpl_id'], 
                'mw_batch_id' => $data['mw_batch_id']);
            $field1_count = $model->where($cfgmap)->count();
            if ($field1_count >= 6) {
                M()->rollback();
                $this->error("本机构1号位置已存在6条图片记录，无法添加新的记录");
            }
            // 获取图片跳转url
            if ($data['url_type'] == '0') { // 选择互动模块
                $label_id = $this->get_batch_channel($batch_id, $channel_id, 
                    $batch_type);
                $url = U('Label/Label/index', 
                    array(
                        'id' => $label_id), '', '', true);
                $link_type = 0;
                $data['content'] = '';
            } elseif ($data['url_type'] == '1') { // 手动输入
                $url = $data['link_url'];
                $batch_id = '';
                $batch_type = '';
                $link_type = 1;
                $data['content'] = '';
            } elseif ($data['url_type'] == '2') {
                $url = '';
                $batch_id = '';
                $batch_type = '';
                $link_type = 2;
            }
            $data_arr = array(
                'node_id' => $this->node_id, 
                'tpl_id' => $data['tpl_id'], 
                'field_id' => $data['field_id'], 
                'field_type' => $data['field_type'],  // 轮播图
                'title' => $data['title'], 
                'image_name' => $image_name, 
                'sumary' => $data['sumary'], 
                'batch_type' => $batch_type, 
                'batch_id' => $batch_id, 
                'link_url' => $url, 
                'add_time' => date('YmdHis'), 
                'mw_batch_id' => $data['mw_batch_id'], 
                'content' => $data['content'], 
                'link_type' => $link_type, 
                'appdesc' => $data['appdesc']);
            
            $query = $model->add($data_arr);
            if ($query) {
                node_log('经典模版添加成功|机构号:' . $this->node_id);
            } else {
                M()->rollback();
                $this->error('经典模版添加失败2');
            }
        } elseif ($data['field_type'] == '0') {
            // 0号位 logo
            $cfgmap = array(
                'node_id' => $this->node_id, 
                'field_id' => $data['field_id'], 
                'tpl_id' => $data['tpl_id'], 
                'mw_batch_id' => $data['mw_batch_id']);
            $field1_count = $model->where($cfgmap)->count();
            if ($field1_count > 0) {
                M()->rollback();
                $this->error(
                    "本机构" . $data['field_id'] . "号位置已存在1条图片记录，无法添加新的记录");
            }
            
            $data_arr = array(
                'node_id' => $this->node_id, 
                'tpl_id' => $data['tpl_id'], 
                'field_id' => $data['field_id'], 
                'field_type' => $data['field_type'],  // 单图文
                'title' => $data['name'], 
                'image_name' => $image_name, 
                'add_time' => date('YmdHis'), 
                'mw_batch_id' => $data['mw_batch_id'], 
                'appdesc' => $data['appdesc']);
            $query = $model->add($data_arr);
            if ($query) {
                // 更新tmarketing_info表中name字段
                $market_map = array(
                    'node_id' => $this->node_id, 
                    'batch_type' => self::BATCH_TYPE_MICROWEB, 
                    'id' => $data['mw_batch_id']);
                $batch_arr = array(
                    'name' => $data['name'],
                    'node_name' => $data['node_name_radio'] ? $data['name'] : ''
                );
                
                $query2 = $batch_model->where($market_map)->save($batch_arr);
                if ($query2 === false) {
                    M()->rollback();
                    $this->error('更新营销活动名称失败');
                }
                node_log('经典模版添加成功|机构号:' . $this->node_id);
            } else {
                M()->rollback();
                $this->error('经典模版添加失败3');
            }
        } elseif ($data['field_type'] == '2') {
            // 2/3/4号位 单图文
            $cfgmap = array(
                'node_id' => $this->node_id, 
                'field_id' => $data['field_id'], 
                'tpl_id' => $data['tpl_id'], 
                'mw_batch_id' => $data['mw_batch_id']);
            $field1_count = $model->where($cfgmap)->count();
            if ($field1_count >= 11) {
                M()->rollback();
                $this->error(
                    "本机构" . $data['field_id'] . "号位置已存在11条图片记录，无法添加新的记录");
            }
            // 获取图片跳转url
            if ($data['url_type'] == '0') { // 选择互动模块
                $label_id = $this->get_batch_channel($batch_id, $channel_id, 
                    $batch_type);
                $url = U('Label/Label/index', 
                    array(
                        'id' => $label_id), '', '', true);
                $link_type = 0;
            } elseif ($data['url_type'] == '1') { // 手动输入
                $url = $data['link_url'];
                $batch_id = '';
                $batch_type = '';
                $link_type = 1;
                $data['content'] = '';
            } elseif ($data['url_type'] == '2') {
                $url = '';
                $batch_id = '';
                $batch_type = '';
                $link_type = 2;
            }
            $data_arr = array(
                'node_id' => $this->node_id, 
                'tpl_id' => $data['tpl_id'], 
                'field_id' => $data['field_id'], 
                'field_type' => $data['field_type'],  // 单图文
                'title' => $data['title'], 
                'image_name' => $image_name, 
                'sumary' => $data['sumary'], 
                'batch_type' => $batch_type, 
                'batch_id' => $batch_id, 
                'link_url' => $url, 
                'add_time' => date('YmdHis'), 
                'mw_batch_id' => $data['mw_batch_id'], 
                'content' => $data['content'], 
                'link_type' => $link_type);
            if (($data['tpl_id'] == '3' || $data['tpl_id'] == '4' ||
                 $data['tpl_id'] == '9' || $data['tpl_id'] == '10' ||
                 $data['tpl_id'] == '11' || $data['tpl_id'] == '12' ||
                 $data['tpl_id'] == '16') && $data['field_type'] == '2') {
                if ($data['tpl_id'] == '10') {
                    if (! $data['colorVal']) {
                        M()->rollback();
                        $this->error('系统颜色不能为空');
                    }
                } else {
                    // 模版3 选择系统定义图片
                    if (! $data['iconVal'] || ! $data['colorVal']) {
                        M()->rollback();
                        $this->error('系统图片和颜色不能为空');
                    }
                }
                $data_arr['field_img_name'] = $data['iconVal'];
                $data_arr['field_img_color'] = $data['colorVal'];
                $data_arr['appdesc'] = $data['appdesc'];
            }
            $query = $model->add($data_arr);
            if ($query) {
                node_log('经典模版添加成功|机构号:' . $this->node_id);
            } else {
                M()->rollback();
                $this->error('经典模版添加失败4');
            }
        } else {
            M()->rollback();
            $this->error('模版位置编号错误');
        }
        M()->commit();
        // 返回结果
        $message = array(
            'respId' => 0, 
            'respStr' => '添加成功', 
            'id' => $query);
        if ($data['field_type'] == '0') {
            $message['url'] = U('MicroWeb/Index/addEditSuccess', 
                array(
                    'mw_batch_id' => $data['mw_batch_id']));
        }
        $this->success($message);
    }
    
    // 编辑模版
    public function edit() {
        $data = I('request.');
        $cfg_id = $data['id'];
        $mw_batch_id = $data['mw_batch_id'];
        if ($cfg_id) {
            $cfg_model = M('tmicroweb_tpl_cfg');
            $map = array(
                'node_id' => $this->node_id, 
                'id' => $cfg_id);
            
            $list = $cfg_model->where($map)->find();
        }
        // 图片地址
        if ($list['image_name']) {
            $list['image_url'] = get_upload_url($list['image_name']);
        }
        // 位置id
        if (! $list['field_id']) {
            $list['field_id'] = $data['field_id'];
        }
        if (! $list['field_type']) {
            $list['field_type'] = $data['field_type'];
        }
        if (! $list['tpl_id']) {
            $list['tpl_id'] = $data['tpl_id'];
        }
        // 链接类型url_type
        if (! $list['batch_id']) {
            $list['url_type'] = '0';
        } else {
            $list['url_type'] = '1';
        }
        
        $list['batch_name'] = '';
        $list['batch_type_name'] = '';
        // 获取营销活动名
        $batch_type_arr = C('BATCH_TYPE');
        $type_name_arr = C('BATCH_TYPE_NAME');
        if ($list['batch_id'] && $list['batch_type']) {
            $table_name = 'tmarketing_info';
            if ($table_name) {
                $batch_model = M($table_name);
                $batch_map = array(
                    'id' => $list['batch_id'], 
                    'batch_type' => $list['batch_type']);
                $batch_name = $batch_model->where($batch_map)
                    ->limit('1')
                    ->getField('name');
                $list['batch_name'] = $batch_name;
                $list['batch_type_name'] = $type_name_arr[$list['batch_type']];
            }
        }
        
        // 绑定微官网本身的营销活动id
        if (! $list['mw_batch_id']) {
            if (! $mw_batch_id) {
                $this->error('本身营销活动号错误');
            }
            $list['mw_batch_id'] = $mw_batch_id;
        }
        $this->assign('is_df', 
            (C('df.node_id') == $this->node_id) ? true : false);
        
        $this->assign('list', $list);
        $this->display(); // 输出模板
    }
    
    // 编辑模版提交
    public function editSubmit($data) {
        $model = M('tmicroweb_tpl_cfg');
        $channel_model = M('tchannel');
        
        $onemap = array(
            'node_id' => $this->node_id, 
            'id' => $data['id'], 
            'mw_batch_id' => $data['mw_batch_id']);
        
        // 判断数据是否存在
        $count = $model->where($onemap)->count();
        if ($count < 1) {
            $this->error('数据不存在，请核实');
        }
        
        // 判断C0是否有权限编辑分享配置
        if (($data['phone'] || $data['sns_1'] || $data['sns_2'] || $data['sns_3'] ||
             $data['sns_4']) && $data['node_level'] == 'C0' &&
             $data['sns_cfg_flag'] == "true") {
            $this->error("机构账户权限不足，请先提升权限");
        }
        // 模版12
        // 开启事务
        M()->startTrans();
        if ($data['sns_cfg_flag'] == 'true') {
            // 更新3 分享配置信息
            $cfg_arr = array(
                'sns' => array(
                    'phone' => $data['phone'], 
                    'sns_1' => $data['sns_1'], 
                    'sns_2' => $data['sns_2'], 
                    'sns_3' => $data['sns_3'], 
                    'sns_4' => $data['sns_4'], 
                    'sns_5' => $data['sns_5']));
            
            $cfg_data = array(
                'content' => json_encode($cfg_arr), 
                'appdesc' => $data['appdesc']);
            if ($data['sns_cfg_id']) { // 更新
                $cfg_map = array(
                    'node_id' => $this->node_id, 
                    'field_id' => '3', 
                    'field_type' => '3', 
                    'tpl_id' => $data['tpl_id'], 
                    'mw_batch_id' => $data['mw_batch_id'], 
                    'id' => $data['sns_cfg_id']);
                $query = $model->where($cfg_map)->save($cfg_data);
                if ($query === false) {
                    M()->rollback();
                    $this->error('经典模版3号位置分享配置保存失败');
                } else {
                    node_log('经典模版编辑成功|位置编号:3');
                }
            } else { // 插入
                $cfg_data['node_id'] = $this->node_id;
                $cfg_data['tpl_id'] = $data['tpl_id'];
                $cfg_data['field_id'] = '3';
                $cfg_data['field_type'] = '3';
                $cfg_data['add_time'] = date('YmdHis');
                $cfg_data['mw_batch_id'] = $data['mw_batch_id'];
                $cfg_data['appdesc'] = $data['appdesc'];
                $query = $model->add($cfg_data);
                if ($query) {
                    node_log('经典模版分享配置添加成功|机构号:' . $this->node_id);
                } else {
                    M()->rollback();
                    $this->error('经典模版添加失败5' . print_r($cfg_data, true));
                }
            }
        }
        // 更新012位置数据
        // 获取存在的营销活动的id和渠道号
        $batch_id = $data['batch_id'];
        $batch_type = $data['batch_type'];
        $channel_map = array(
            'node_id' => $this->node_id, 
            'type' => self::BATCH_CHANNEL_TYPE_MICROWEB, 
            'sns_type' => self::BATCH_SNS_TYPE_MICRFOWEB);
        $channel_id = $channel_model->where($channel_map)
            ->limit('1')
            ->getField('id');
        
        if (in_array($data['field_type'], 
            array(
                "1", 
                "2"), true)) {
            // 获取图片跳转url
            if ($data['url_type'] == '0') { // 选择互动模块
                $label_id = $this->get_batch_channel($batch_id, $channel_id, 
                    $batch_type);
                $url = U('Label/Label/index', 
                    array(
                        'id' => $label_id), '', '', true);
                $link_type = 0;
                $data['content'] = '';
            } elseif ($data['url_type'] == '1') { // 手动输入
                $url = $data['link_url'];
                $batch_id = '';
                $batch_type = '';
                $link_type = 1;
                $data['content'] = '';
            } elseif ($data['url_type'] == '2') {
                $url = '';
                $batch_id = '';
                $batch_type = '';
                $link_type = 2;
            }
            $data_arr = array(
                'title' => $data['title'], 
                // 'image_name'=>basename($data['image_name']),
                'sumary' => $data['sumary'], 
                'link_url' => $url, 
                'batch_id' => $batch_id, 
                'batch_type' => $batch_type, 
                'edit_time' => date('YmdHis'), 
                'content' => $data['content'],
                'link_type' => $link_type);
            // 移动图片
            if ($data['image_name']) {
                $data_arr['image_name'] = $data['image_name'];
                $data_arr['field_img_name'] = '';
                $data_arr['field_img_color'] = '';
            } else if (($data['tpl_id'] == '3' || $data['tpl_id'] == '4' ||
                 $data['tpl_id'] == '9' || $data['tpl_id'] == '10' ||
                 $data['tpl_id'] == '11' || $data['tpl_id'] == '12') &&
                 ($data['field_type'] == '2')) {
                if ($data['tpl_id'] == '10') {
                    if (! $data['colorVal']) {
                        M()->rollback();
                        $this->error('系统颜色不能为空');
                    }
                    $data_arr['field_img_name'] = '';
                    $data_arr['field_img_color'] = $data['colorVal'];
                    $data_arr['image_name'] = '';
                } else {
                    // 模版3 选择系统定义图片
                    if (! $data['iconVal'] || ! $data['colorVal']) {
                        M()->rollback();
                        $this->error('系统图片和颜色不能为空');
                    }
                    $data_arr['field_img_name'] = $data['iconVal'];
                    $data_arr['field_img_color'] = $data['colorVal'];
                    $data_arr['image_name'] = '';
                }
            }
            $data_arr['appdesc'] = $data['appdesc'];
            $query = $model->where($onemap)->save($data_arr);
            if ($query === false) {
                M()->rollback();
                $this->error('经典模版' . $data['field_id'] . '号位置编辑失败');
            } else {
                node_log('经典模版编辑成功|位置编号:' . $data['field_id']);
            }
        } elseif ($data['field_type'] == '0') {
            ;
            // 0号位 logo
            $data_arr = array(
                'title' => $data['name'], 
                'edit_time' => date('YmdHis'));
            
            // 上传为否则取消logo
            if ($data['is_log_img'] == '0') {
                $data_arr['image_name'] = '';
            } elseif ($data['is_log_img'] == 1) {
                $data_arr['image_name'] = $data['image_name'];
            }
            ;
            $data_arr['appdesc'] = $data['appdesc'];
            $query = $model->where($onemap)->save($data_arr);
            if ($query) {
                // 更新tmarketing_info表中name字段
                $market_map = array(
                    'node_id' => $this->node_id, 
                    'batch_type' => self::BATCH_TYPE_MICROWEB, 
                    'id' => $data['mw_batch_id']);
                $batch_arr = array(
                    'name' => $data['name'], 
                    'node_name' => $data['node_name_radio'] ? $data['name'] : ''
                );
                
                $query2 = M('tmarketing_info')->where($market_map)->save(
                    $batch_arr);
                if ($query2 === false) {
                    M()->rollback();
                    $this->error('更新营销活动名称失败' . print_r($market_map, true));
                }
                node_log('微官网模版编辑成功|机构号:' . $this->node_id);
            } else {
                M()->rollback();
                $this->error('微官网模版编辑失败');
            }
        } else {
            M()->rollback();
            $this->error('模版位置编号错误');
        }
        // 移动导航
        $count = count($data['gps']);
        for ($i = 0; $i < $count; $i ++) {
            M('tmicroweb_tpl_cfg')->where(
                array(
                    'id' => $data['gps'][$i]))->save(
                array(
                    'order_id' => $i));
        }
        M()->commit();
        // 返回结果
        $message = array(
            'respId' => 0, 
            'respStr' => '编辑成功');
        if ($data['field_type'] == '0') {
            $message['url'] = U('MarketActive/Tool/website');
        }
        $this->success($message);
    }
    
    // 更新微官网营销活动名
    public function updateBatchName() {
        $data = I('request.');
        if (! $data['mw_batch_id']) {
            $this->error('营销活动数据不得为空');
        }
        
        $model = M('tmarketing_info');
        $map = array(
            'node_id' => $this->node_id, 
            'batch_type' => self::BATCH_TYPE_MICROWEB, 
            'id' => $data['mw_batch_id']);
        
        $list = $model->where($map)
            ->limit('1')
            ->select();
        if (! $list)
            $this->error('未找到营销活动数据');
        $this->assign('list', $list);
        $this->display(); // 输出模板
    }

    public function updateBatchNameSubmit() {
        $data = I('request.');
        if ((! $data['name']) && (! $data['mw_batch_id'])) {
            $this->error('营销活动数据不得为空');
        }
        
        $batch_model = M('tmarketing_info');
        $onemap = array(
            'node_id' => $this->node_id, 
            'batch_type' => self::BATCH_TYPE_MICROWEB, 
            'id' => $data['mw_batch_id']);
        $batch_data = $batch_model->where($onemap)->find();
        if ($batch_data) {
            // 营销活动存在则更新
            $batch_arr = array(
                'name' => $data['name']);
            
            $query = $batch_model->where($onemap)->save($batch_arr);
            if (! $query) {
                $this->error('编辑营销活动失败');
            }
        } else {
            $this->error('营销活动号[' . $data['mw_batch_id'] . ']不存在');
        }
        
        $message = array(
            'respId' => 0, 
            'respStr' => '更新成功');
        $this->success($message);
    }
    
    // 更新默认模版号
    public function UpdateTplId() {
        $data = I('request.');
        if ((! $data['tpl_id']) || (! $data['mw_batch_id'])) {
            $this->error('模版编号或者营销活动号不得为空');
        }
        $node_level = $this->getNodeLevel();
        if ($data['tpl_id'] > 3) {
            if ($node_level == 'C0') {
                $this->error('机构账户权限不足，请先提升权限');
            }
        }
        // 开启事务
        M()->startTrans();
        $batch_model = M('tmarketing_info');
        $onemap = array(
            'node_id' => $this->node_id, 
            'batch_type' => self::BATCH_TYPE_MICROWEB, 
            'id' => $data['mw_batch_id']);
        $batch_data = $batch_model->where($onemap)->find();
        if ($batch_data) {
            // 营销活动存在则更新
            $batch_arr = array(
                'tpl_id' => $data['tpl_id']);
            
            $query = $batch_model->where($onemap)->save($batch_arr);
            if ($query === false) {
                M()->rollback();
                $this->error('编辑营销活动失败');
            }
            
            $tpl_cfg_map = array(
                'node_id' => $this->node_id, 
                'mw_batch_id' => $data['mw_batch_id']);
            $tpl_arr = array(
                'tpl_id' => $data['tpl_id'], 
                'edit_time' => date('YmdHis'));
            $query = M('tmicroweb_tpl_cfg')->where($tpl_cfg_map)->save($tpl_arr);
            if ($query === false) {
                M()->rollback();
                $this->error('编辑模版配置数据失败');
            }
        } else {
            M()->rollback();
            $this->error('营销活动号[' . $data['mw_batch_id'] . ']不存在');
        }
        M()->commit();
        $message = array(
            'respId' => 0, 
            'respStr' => '更新成功');
        $this->success($message);
    }
    
    // 更新全景背景图
    public function UpdateBgType() {
        $data = I('request.');
        if ((! $data['tpl_id']) || (! $data['mw_batch_id'])) {
            $this->error('模版编号或者营销活动号不得为空');
        }
        $node_level = $this->getNodeLevel();
        if ($data['tpl_id'] > 3) {
            if ($node_level == 'C0') {
                $this->error('机构账户权限不足，请先提升权限');
            }
        }
        // 开启事务
        M()->startTrans();
        $batch_model = M('tmarketing_info');
        $onemap = array(
            'node_id' => $this->node_id, 
            'batch_type' => self::BATCH_TYPE_MICROWEB, 
            'id' => $data['mw_batch_id']);
        $batch_data = $batch_model->where($onemap)->find();
        if ($batch_data) {
            $tpl_cfg_map = array(
                'node_id' => $this->node_id, 
                'mw_batch_id' => $data['mw_batch_id'], 
                'tpl_id' => $data['tpl_id'], 
                'field_type' => '4');
            $bg_cfg_data = M('tmicroweb_tpl_cfg')->where($tpl_cfg_map)->find();
            if ($bg_cfg_data) { // 编辑
                $bg_cfg_arr = array(
                    'image_name' => $data['bg_url'], 
                    'link_type' => $data['bg_type']);
                $query = M('tmicroweb_tpl_cfg')->where(
                    array(
                        'id' => $bg_cfg_data['id']))->save($bg_cfg_arr);
            } else { // 新增
                $bg_cfg_arr = array(
                    'node_id' => $this->node_id, 
                    'mw_batch_id' => $data['mw_batch_id'], 
                    'tpl_id' => $data['tpl_id'], 
                    'field_type' => '4', 
                    'image_name' => $data['bg_url'], 
                    'link_type' => $data['bg_type']);
                $query = M('tmicroweb_tpl_cfg')->add($bg_cfg_arr);
            }
            if ($query === false) {
                M()->rollback();
                $this->error('设置全景背景图错误');
            }
        } else {
            M()->rollback();
            $this->error('营销活动号[' . $data['mw_batch_id'] . ']不存在');
        }
        M()->commit();
        $message = array(
            'respId' => 0, 
            'respStr' => '更新成功');
        $this->success($message);
    }
    
    // 删除模版
    public function delete() {
        $model = M('tmicroweb_tpl_cfg');
        $channel_model = M('tchannel');
        // 获取递交的数据
        $data = I('request.');
        $onemap = array(
            'node_id' => $this->node_id, 
            'id' => $data['id']);
        // 判断数据是否存在
        $count = $model->where($onemap)->count();
        ;
        if ($count < 1) {
            $this->error('数据不存在，请核实');
        }
        
        $result = $model->where($onemap)->delete();
        if ($result === false) {
            $this->error('删除失败');
        }
        $message = array(
            'respId' => 0, 
            'respStr' => '删除成功');
        $this->success($message);
    }

    /*
     * //文件上传 public function upload_file(){ $type = I('get.type'); if($type ==
     * 'img' || $type == 'audio') { $this->setAstrict($type); }else{ $arr =
     * array( 'msg'=>'-111',//通信是否成功 'error'=>"未知上传类型", //返回错误
     * 'imgurl'=>$type,//返回图片名 ); echo json_encode($arr); exit; }
     * import('ORG.Net.UploadFile'); $upload = new UploadFile();// 实例化上传类
     * $upload->maxSize = $this->maxSize; $upload->allowExts = $this->allowExts;
     * $upload->savePath = APP_PATH.'/Upload/MicroWebImg/'.$this->node_id.'/';//
     * 设置附件 $upload->saveRule = time().sprintf('%04s',mt_rand(0,1000));
     * if(!$upload->upload()) {// 上传错误提示错误信息 $this->errormsg =
     * $upload->getErrorMsg(); }else{// 上传成功 获取上传文件信息 $info =
     * $upload->getUploadFileInfo(); $this->imgurl = $info[0]['savename']; }
     * $arr = array( 'msg'=>'0000',//通信是否成功 'error'=>$this->errormsg, //返回错误
     * 'imgurl'=>$this->imgurl,//返回图片名 ); echo json_encode($arr); exit; }
     * //设置类型，大小限制 public function setAstrict($type) { $img_config =
     * C('UPLOAD_IMG'); $audio_config =C('UPLOAD_AUDIO'); if($type == "img") {
     * $this->maxSize = $img_config['SIZE'] ;// 设置附件上传大小 $this->allowExts =
     * (array) explode(",", $img_config['TYPE']);// 设置附件上传类型 } if($type ==
     * "audio") { $this->maxSize = $audio_config['SIZE'] ;// 设置附件上传大小
     * $this->allowExts = (array) explode(",", $audio_config['TYPE']);//
     * 设置附件上传类型 } }
     */
    private function addbatch() {
        $batch_model = M('tmarketing_info');
        $onemap = array(
            'node_id' => $this->node_id, 
            'batch_type' => self::BATCH_TYPE_MICROWEB);
        $batch_data = $batch_model->where($onemap)->find();
        if (! $batch_data) {
            // 营销活动不存在则新增
            $batch_arr = array(
                'batch_type' => self::BATCH_TYPE_MICROWEB, 
                'name' => '微官网', 
                'status' => '1', 
                'start_time' => date('YmdHis'), 
                'end_time' => date('YmdHis', strtotime("+10 year")), 
                'add_time' => date('YmdHis'), 
                'click_count' => 0, 
                'cj_count' => 0, 
                'send_count' => 0, 
                'node_id' => $this->node_id, 
                'tpl_id' => 1, 
                'pay_status' => 1);
            
            $query = $batch_model->add($batch_arr);
            if (! $query) {
                $this->error('添加默认营销活动失败');
            }
            // 添加到O2O案例
            $ser = D('TmarketingInfo');
            $arr = array(
                'node_id' => $this->node_id, 
                'batch_type' => self::BATCH_TYPE_MICROWEB, 
                'batch_id' => $query);
            $ser->init($arr);
            $ser->sendBatch();
            
            return array(
                'batch_id' => $query, 
                'batch_name' => '微官网', 
                'tpl_id' => 1, 
                'click_count' => 0);
        } else {
            return array(
                'batch_id' => $batch_data['id'], 
                'batch_name' => $batch_data['name'], 
                'tpl_id' => $batch_data['tpl_id'], 
                'click_count' => $batch_data['click_count'],
                'node_name' => $batch_data['node_name']
            );
        }
    }

    private function addchannel() {
        $channel_model = M('tchannel');
        $onemap = array(
            'node_id' => $this->node_id, 
            'sns_type' => self::BATCH_SNS_TYPE_MICRFOWEB);
        $channel_id = $channel_model->where($onemap)
            ->limit('1')
            ->getField('id');
        if (! $channel_id) {
            // 营销活动不存在则新增
            $channel_arr = array(
                'name' => '微官网渠道', 
                'type' => self::BATCH_CHANNEL_TYPE_MICROWEB, 
                'sns_type' => self::BATCH_SNS_TYPE_MICRFOWEB, 
                'status' => '1', 
                'start_time' => date('YmdHis'), 
                'end_time' => date('YmdHis', strtotime("+1 year")), 
                'add_time' => date('YmdHis'), 
                'click_count' => 0, 
                'cj_count' => 0, 
                'send_count' => 0, 
                'node_id' => $this->node_id);
            
            $query = $channel_model->add($channel_arr);
            if (! $query) {
                $this->error('添加默认微官网渠道号失败');
            }
            return $query;
        } else {
            return $channel_id;
        }
    }

    private function addaipaichannel() {
        $channel_model = M('tchannel');
        $onemap = array(
            'node_id' => $this->node_id, 
            'type' => self::LABEL_AIPAI_TYPE_MICROWEB, 
            'sns_type' => self::LABEL_AIPAI_SNS_TYPE_MICROWEB);
        // 开启事务
        M()->startTrans();
        $label_data = $channel_model->where($onemap)
            ->limit('1')
            ->find();
        if (! $label_data) {
            // 营销活动不存在则新增
            $channel_arr = array(
                'name' => '爱拍微官网渠道', 
                'type' => self::LABEL_AIPAI_TYPE_MICROWEB, 
                'sns_type' => self::LABEL_AIPAI_SNS_TYPE_MICROWEB, 
                'status' => '1', 
                'start_time' => date('YmdHis'), 
                'end_time' => date('YmdHis', strtotime("+1 year")), 
                'add_time' => date('YmdHis'), 
                'click_count' => 0, 
                'cj_count' => 0, 
                'send_count' => 0, 
                'node_id' => $this->node_id);
            
            $query = $channel_model->add($channel_arr);
            if (! $query) {
                M()->rollback();
                $this->error('添加爱拍默认微官网渠道号失败');
            }
            $label_id = $channel_model->where($onemap)
                ->limit('1')
                ->getField('label_id');
            $channel_id = $query;
        } else {
            $label_id = $label_data['label_id'];
            $channel_id = $label_data['id'];
        }
        
        // 获取tpp_batch的id
        $pp_model = M('tpp_batch');
        $pp_map = array(
            'node_id' => $this->node_id);
        $pp_batch_id = $pp_model->where($pp_map)
            ->limit('1')
            ->getField('id');
        
        // 插入tbatch_channel表数据
        $batch_channel_model = M('tbatch_channel');
        $map = array(
            'node_id' => $this->node_id, 
            'batch_id' => $pp_batch_id, 
            'channel_id' => $channel_id, 
            'batch_type' => '5');
        $batch_channel_count = $batch_channel_model->where($map)->count();
        if ($batch_channel_count < 1) {
            $data_arr = array(
                'batch_type' => '5', 
                'batch_id' => $pp_batch_id, 
                'channel_id' => $channel_id, 
                'status' => '1', 
                'add_time' => date('YmdHis'), 
                'click_count' => 0, 
                'cj_count' => 0, 
                'send_count' => 0, 
                'node_id' => $this->node_id);
            $query = $batch_channel_model->add($data_arr);
            if ($query) {
                $query2 = M('tchannel')->where(
                    array(
                        'id' => $channel_id))->save(
                    array(
                        'batch_type' => '5', 
                        'batch_id' => $pp_batch_id));
                if ($query2 === false) {
                    M()->rollback();
                    $this->error('发布爱拍活动失败！');
                }
                node_log('创建爱拍微官网标签成功');
            } else {
                M()->rollback();
                $this->error('创建爱拍微官网标签失败');
                return false;
            }
        }
        M()->commit();
        return $label_id;
    }
    
    // 获取活动发布渠道的id
    public function get_batch_channel($batch_id, $channel_id, $batch_type) {
        if (! $channel_id)
            $this->error('渠道号获取失败');
        
        $batch_channel_model = M('tbatch_channel');
        $map = array(
            'node_id' => $this->node_id, 
            'batch_id' => $batch_id, 
            'channel_id' => $channel_id, 
            'batch_type' => $batch_type);
        $batch_channel_count = $batch_channel_model->where($map)->count();
        if ($batch_channel_count < 1) {
            $data_arr = array(
                'batch_type' => $batch_type, 
                'batch_id' => $batch_id, 
                'channel_id' => $channel_id, 
                'status' => '1', 
                'add_time' => date('YmdHis'), 
                'click_count' => 0, 
                'cj_count' => 0, 
                'send_count' => 0, 
                'node_id' => $this->node_id);
            $query = $batch_channel_model->add($data_arr);
            if ($query) {
                node_log('创建标签成功');
                return $query;
            } else {
                node_log('创建标签失败');
                $this->error('创建标签失败');
                return false;
            }
        } else {
            $id = $batch_channel_model->where($map)
                ->limit('1')
                ->getField('id');
            return $id;
        }
    }

    public function checkrule($data) {
        if ($data['field_id'] == null) {
            $this->error('位置编号不能为空');
        }
        if ($data['url_type'] == '0') {
            if ($data['batch_id'] == null || $data['batch_type'] == null) {
                $this->error('营销活动不能为空');
            }
        }
        
        return true;
    }
    
    // tpl_cfg结果集转成json报文
    private function get_cfg_array($data) {
        $result = array();
        foreach ($data as $v) {
            if ($v['field_type'] != '3') {
                if ($v['tpl_id'] == '1' || $v['tpl_id'] == '5' ||
                     $v['tpl_id'] == '6' || $v['tpl_id'] == '7' ||
                     $v['tpl_id'] == '13' || $v['tpl_id'] == '14' ||
                     $v['tpl_id'] == '15' || $v['tpl_id'] == '16') {
                    if ($v['image_name']) {
                        $image_url = $this->_getUploadUrl($v['image_name']);
                    } else {
                        $image_url = '';
                    }
                    $v['field_img_color'] = '';
                } elseif ($v['tpl_id'] == '2') {
                    if ($v['field_type'] == '1' || $v['field_type'] == '0') {
                        if ($v['image_name']) {
                            $image_url = $this->_getUploadUrl($v['image_name']);
                        } else {
                            $image_url = '';
                        }
                    } else {
                        $image_url = '';
                    }
                } elseif ($v['tpl_id'] == '8') { // 世界杯模版 直接返回制定图片
                    if ($v['field_type'] == '0') {
                        if ($v['image_name']) {
                            $image_url = $this->_getUploadUrl($v['image_name']);
                        } else {
                            $image_url = '';
                        }
                    } elseif ($v['field_type'] == '1') {
                        $image_url = '__PUBLIC__/Label/Image/worldcup/topbg-zOne.jpg';
                    } else {
                        $image_url = '';
                    }
                    // } elseif ($v['tpl_id'] == '3' || $v['tpl_id'] == '4' ||
                    // $v['tpl_id'] == '9' || $v['tpl_id'] == '10') {
                } elseif (in_array($v['tpl_id'], 
                    array(
                        '3', 
                        '4', 
                        '9', 
                        '10', 
                        '11', 
                        '12'))) {
                    if ($v['field_type'] == '1' || $v['field_type'] == '0') {
                        if ($v['image_name']) {
                            $image_url = $this->_getUploadUrl($v['image_name']);
                        } else {
                            $image_url = '';
                        }
                    } elseif ($v['field_type'] == '4') {
                        if ($v['link_type'] == '4') {
                            $image_url = $this->_getUploadUrl($v['image_name']);
                        } else {
                            $image_url = C('TMPL_PARSE_STRING.__PUBLIC__') .
                                 '/Image/wapimg/' . basename($v['image_name']);
                        }
                    } else {
                        if ($v['field_img_name']) {
                            $image_url = C('TMPL_PARSE_STRING.__PUBLIC__') .
                                 '/Label/Image/iconVal/' .
                                 basename($v['field_img_name']);
                        } else {
                            $image_url = '';
                        }
                    }
                } else {
                    $image_url = '';
                }

                $add_time =$v['add_time'];
                if (empty($add_time)) {
                    $add_time = $v['id'];
                }

                $result[$v['field_type']][] = array(
                    'id' => $v['id'], 
                    'title' => $v['title'], 
                    'field_id' => $v['field_id'], 
                    'image_name' => $v['image_name'], 
                    'image_url' => $image_url, 
                    'link_url' => $v['link_url'], 
                    'sumary' => $v['sumary'], 
                    'mw_batch_id' => $v['mw_batch_id'], 
                    'tpl_id' => $v['tpl_id'], 
                    'field_img_color' => $v['field_img_color'], 
                    'content' => $v['content'], 
                    'is_fixed' => $v['is_fixed'],
                     'add_time' => $add_time,
                );
            } else {
                if ($v['field_type'] == '3') {
                    $v['content'] = json_decode($v['content'], true);
                }
                $result['sns_cfg_id'] = $v['id'];
                $result['phone'] = $v['content']['sns']['phone'];
                $result['sns_1'] = $v['content']['sns']['sns_1'];
                $result['sns_2'] = $v['content']['sns']['sns_2'];
                $result['sns_3'] = $v['content']['sns']['sns_3'];
                $result['sns_4'] = $v['content']['sns']['sns_4'];
            }
        }
        return $result;
    }
    
    // 移动图片 tmp->Upload/MicroWebImg/Nodei
    private function move_image($image_name) {
        if (! $image_name) {
            return "需上传图片";
        }
        if (! is_dir(APP_PATH . '/Upload/MicroWebImg/' . $this->node_id)) {
            mkdir(APP_PATH . '/Upload/MicroWebImg/' . $this->node_id, 0777);
        }
        $old_image_url = APP_PATH . '/Upload/img_tmp/' . $this->node_id . '/' .
             basename($image_name);
        $new_image_url = APP_PATH . '/Upload/MicroWebImg/' . $this->node_id . '/' .
             basename($image_name);
        $flag = copy($old_image_url, $new_image_url);
        if ($flag) {
            return true;
        } else {
            return "图片路径非法" . $old_image_url . "==" . $new_image_url;
        }
    }

    private function move_image_log($image_name) {
        if (! $image_name) {
            return "需上传图片";
        }
        if (! is_dir(APP_PATH . '/Upload/MicroWebImg/' . $this->node_id)) {
            mkdir(APP_PATH . '/Upload/MicroWebImg/' . $this->node_id, 0777);
        }
        $old_image_url = APP_PATH . '/Upload/LogoImg/' . basename($image_name);
        $new_image_url = APP_PATH . '/Upload/MicroWebImg/' . $this->node_id . '/' .
             basename($image_name);
        $flag = copy($old_image_url, $new_image_url);
        if ($flag) {
            return true;
        } else {
            return "图片路径非法" . $old_image_url . "==" . $new_image_url;
        }
    }

    /**
     * RGB转 十六进制
     *
     * @param $rgb RGB颜色的字符串 如：rgb(255,255,255);
     * @return string 十六进制颜色值 如：#FFFFFF
     */
    public function RGBToHex($rgb) {
        $regexp = "/^rgb\(([0-9]{0,3})\,\s*([0-9]{0,3})\,\s*([0-9]{0,3})\)/";
        $re = preg_match($regexp, $rgb, $match);
        $re = array_shift($match);
        $hexColor = "#";
        $hex = array(
            '0', 
            '1', 
            '2', 
            '3', 
            '4', 
            '5', 
            '6', 
            '7', 
            '8', 
            '9', 
            'A', 
            'B', 
            'C', 
            'D', 
            'E', 
            'F');
        for ($i = 0; $i < 3; $i ++) {
            $r = null;
            $c = $match[$i];
            $hexAr = array();
            
            while ($c > 16) {
                $r = $c % 16;
                
                $c = ($c / 16) >> 0;
                array_push($hexAr, $hex[$r]);
            }
            array_push($hexAr, $hex[$c]);
            
            $ret = array_reverse($hexAr);
            $item = implode('', $ret);
            $item = str_pad($item, 2, '0', STR_PAD_LEFT);
            $hexColor .= $item;
        }
        return $hexColor;
    }

    public function addEditSuccess() {
        $data = I('request.');
        $tgurl = U('LabelAdmin/BindChannel/index', 
            array(
                'batch_id' => $data['mw_batch_id'], 
                'batch_type' => self::BATCH_TYPE_MICROWEB));
        // $this->success('操作成功',array('返回微官网首页'=>U('MicroWeb/Index/index'),'推广微官网'=>$tgurl));
        
        $this->success('操作成功', 
            array(
                '发布到线上线下渠道' => $tgurl, 
                '返回微官网首页' => U('MarketActive/Tool/website')));
    }

    /*
     * 根据机构信息表获取当前机构的等级 C0(基础模版，保留建站和爱拍) C1(所有模版，有悬浮窗分享，去除建站，保留爱拍)
     * C2(所有模版，有悬浮窗分享，去除建站和爱拍) CY(翼码和演示机构,具有所有权限，且前台页面保留建站和爱拍)
     */
    private function getNodeLevel() {
        $node_level = 'C0';
        
        $node_type = $this->nodeType;
        $check_status = $this->nodeQsCheckStatus;
        
        if ($node_type === null || $check_status === null) {
            $this->error('机构类型状态错误');
        } else {
            if ($node_type == '0' || $node_type == '1')
                $node_level = 'C2';
            elseif ($node_type == '3' || $node_type == '4')
                $node_level = 'CY';
            elseif ($node_type == '2' && $check_status == '2')
                $node_level = 'C1';
            else
                $node_level = 'C0';
        }
        return $node_level;
    }
    
    // 获取图片路径
    protected function _getUploadUrl($imgname) {
        $img_upload_path = $this->upload_path;
        // 旧版
        if (basename($imgname) == $imgname) {
            return $img_upload_path . $imgname;
        } else {
            return get_upload_url($imgname);
        }
    }
}

?>