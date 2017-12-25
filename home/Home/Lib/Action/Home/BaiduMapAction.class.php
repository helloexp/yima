<?php

class BaiduMapAction extends BaseAction {

    public $uploadPath;

    const CHANNEL_TYPE_BD = '5';
    // 百度地图
    const CHANNEL_SNS_TYPE_BD = '42';
    // 百度地图发布类型
    public function _initialize() {
        parent::_initialize();
    }

    public function open_channel() {
        $node_id = $this->nodeId;
        $status = I('status');
        
        $channelinfo = M('tchannel')->where(
            array(
                'node_id' => $node_id, 
                'type' => self::CHANNEL_TYPE_BD))->find();
        
        // 开启渠道插入渠道
        if ($status == 1) {
            
            // 如果为空则插入
            if (empty($channelinfo)) {
                $result = M('tchannel')->add(
                    array(
                        'name' => '百度地图渠道', 
                        'type' => self::CHANNEL_TYPE_BD, 
                        'sns_type' => self::CHANNEL_SNS_TYPE_BD, 
                        'status' => '1', 
                        'node_id' => $node_id, 
                        'add_time' => date('YmdHis')));
            } else {
                // 更新状态启用
                $data = array(
                    'status' => 1);
                $result = M('tchannel')->where(
                    "id='" . $channelinfo['id'] . "' and node_id='" . $node_id .
                         "'")->save($data);
            }
            
            // 关闭渠道
        } else {
            
            // 如果不为空更新状态为2,空的话插2
            if (! empty($channelinfo)) {
                $data = array(
                    'status' => 2);
                $result = M('tchannel')->where(
                    "id='" . $channelinfo['id'] . "' and node_id='" . $node_id .
                         "'")->save($data);
            } else {
                
                $result = M('tchannel')->add(
                    array(
                        'name' => '百度地图渠道', 
                        'type' => self::CHANNEL_TYPE_BD, 
                        'sns_type' => self::CHANNEL_SNS_TYPE_BD, 
                        'status' => '2', 
                        'node_id' => $node_id, 
                        'add_time' => date('YmdHis')));
            }
        }
        
        if ($result) {
            echo "1";
        } else {
            echo "2";
        }
        exit();
    }
    
    // 发布活动
    public function BatchAdd() {
        
        // 判断商户有没有百度渠道，如果没有渠道或者渠道停用，需要开启或插入渠道
        $node_id = $this->nodeId;
        $channelinfo = M('tchannel')->where(
            array(
                'node_id' => $node_id, 
                'type' => self::CHANNEL_TYPE_BD))->find();
        
        if ($channelinfo) {
            
            // 获取显示门店信息
            $dao = M('tstore_info');
            // 按机构树数据隔离
            $where = "a.node_id in (" . $this->nodeIn() . ")";
            $node_id = I('node_id');
            // 按机构号查询
            if ($node_id && $node_id != $this->nodeId) {
                $where .= " and a.node_id in (" . $this->nodeIn($node_id) . ")";
            }
            // 门店名查询
            if (I('store_name') != '') {
                $where .= " and a.store_name like '%" . I('store_name') . "%'";
            }
            // 负责人
            if (I('principal_name') != '') {
                $where .= " and a.principal_name like '%" . I('principal_name') .
                     "%'";
            }
            // 终端状态类型，未开通终端
            if (I('pos_count_status') == '0') {
                $where .= " and a.pos_count = 0";
            } // 已开通终端
elseif (I('pos_count_status') == '1') {
                $where .= " and a.pos_count >= 1";
            }
            // 业务受理环境
            if (I('pos_range') != '' && in_array(I('pos_range'), 
                array(
                    '0', 
                    '1', 
                    '2'), true)) {
                $where .= " and a.pos_range = '" . I('pos_range') . "'";
            }
            
            if (I('province') != '') {
                $where .= " and a.province_code = '" . I('province') . "'";
            }
            
            if (I('city') != '') {
                $where .= " and a.city_code = '" . I('city') . "'";
            }
            
            if (I('town') != '') {
                $where .= " and a.town_code = '" . I('town') . "'";
            }
            
            import('ORG.Util.Page'); // 导入分页类
            $count = $dao->table('tstore_info a')
                ->where($where)
                ->count(); // 查询满足要求的总记录数
            $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
            $pageShow = $Page->show(); // 分页显示输出
            
            $queryList = $dao->table('tstore_info a')
                ->join('tnode_info b on b.node_id=a.node_id')
                ->field('a.*,b.node_name')
                ->where($where)
                ->order('a.id desc')
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->select();
            
            // 获取当前机构的所有下级机构
            $nodeList = M('tnode_info')->field('node_id,node_name,parent_id')
                ->where("node_id IN({$this->nodeIn()})")
                ->select();
            
            $this->assign('queryList', $queryList);
            $this->assign('pageShow', $pageShow);
            $this->assign('count', $count);
            $this->assign('node_id', $node_id ? $node_id : $this->nodeId);
            $this->assign('node_list', $nodeList);
        }
        
        // 获取当前机构的所有下级机构
        $nodeList = M('tnode_info')->field('node_id,node_name,parent_id')
            ->where("node_id IN({$this->nodeIn()})")
            ->select();
        $this->assign('node_list', $nodeList);
        $this->assign('channelinfo', $channelinfo);
        
        $this->display();
    }
    
    // 查询已发布活动
    public function ViewBaiduList() {
        
        // 判断商户有没有百度渠道，如果没有渠道或者渠道停用，需要开启或插入渠道
        $node_id = $this->nodeId;
        $channelinfo = M('tchannel')->where(
            array(
                'node_id' => $node_id, 
                'type' => self::CHANNEL_TYPE_BD))->find();
        
        if ($channelinfo) {
            
            // 获取显示门店信息
            $dao = M('tbaidu_info');
            // 按机构树数据隔离
            $where = "a.node_id in (" . $this->nodeIn() . ")";
            $node_id = I('node_id');
            // 按机构号查询
            if ($node_id && $node_id != $this->nodeId) {
                $where .= " and a.node_id in (" . $this->nodeIn($node_id) . ")";
            }
            // 活动名查询
            if (I('batch_name') != '') {
                $where .= " and a.batch_name like '%" . I('batch_name') . "%'";
            }
            
            import('ORG.Util.Page'); // 导入分页类
            $count = $dao->table('tbaidu_info a')
                ->where($where)
                ->count(); // 查询满足要求的总记录数
            $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
            $pageShow = $Page->show(); // 分页显示输出
            
            $queryList = $dao->table('tbaidu_info a')
                ->join(
                'tbatch_channel c on a.batch_id=c.batch_id and a.channel_id=c.channel_id')
                ->join('tstore_info d on d.store_id=a.store_id')
                ->field('a.*,c.click_count,c.send_count,d.store_name')
                ->where($where)
                ->order('a.id desc')
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->select();
        } else {
            $this->error("百度渠道不存在或已停用！");
        }
        
        $this->assign('pageShow', $pageShow);
        $this->assign('queryList', $queryList);
        $this->assign('channelinfo', $channelinfo);
        $this->display();
    }

    public function BatchEdit() {
        $batch_id = I("batch_id");
        $id = I("id");
        $batch_type = I("batch_type");
        $node_id = $this->nodeId;
        $where = "a.node_id in (" . $this->nodeId . ")";
        $where .= " AND a.id='" . $id . "' AND  a.batch_id='" . $batch_id .
             "' AND batch_type='" . $batch_type . "'";
        $BaiduInfo =M()->table("tbaidu_info a")->field('a.*')
            ->where($where)
            ->find();
        
        $this->assign('node_id', $node_id);
        $this->assign('id', $id);
        $this->assign('batch_id', $batch_id);
        $this->assign('batch_type', $batch_type);
        $this->assign('BaiduInfo', $BaiduInfo);
        $this->assign('batch_id', $batch_id);
        $this->assign('batch_type', $batch_type);
        $this->display();
    }
    
    // 提交编辑
    public function submitBatchEdit() {
        $id = I('id');
        $node_id = $this->nodeId;
        $batch_name = I('batch_name');
        $batch_desc = I('batch_desc');
        $batch_id = I('batch_id');
        $batch_type = I('batch_type');
        $batch_pic = I('batch_pic');
        $batch_pic_old = I('batch_pic_old');
        
        if (empty($batch_id)) {
            $this->error('活动不能为空！');
        }
        
        if (empty($batch_name)) {
            $this->error('活动名不能为空！');
        }
        if (empty($batch_desc)) {
            $this->error('活动描述不能为空！');
        }
        
        // 绑定渠道
        $channel_id = $this->_getChannelId();
        if (empty($channel_id)) {
            
            $this->error("百度渠道不存在或已停用！");
        }
        $geturl = $this->_getLabelUrl($batch_type, $batch_id);
        
        if ($batch_pic != "" && $batch_pic_old != $batch_pic) {
            
            $flag = $this->move_image($batch_pic);
            if (! ($flag === true)) {
                $this->error($flag);
            }
            $picname = basename($batch_pic);
        } else {
            $picname = $batch_pic_old;
        }
        
        // 判断活动是否存在
        
        $dao = M('tbaidu_info');
        $data = array(
            'batch_name' => $batch_name, 
            'batch_pic' => $picname, 
            'batch_desc' => $batch_desc);
        $result = $dao->where(
            "id='" . $id . "' and node_id='" . $this->nodeId . "'")->save($data);
        
        if ($result) {
            $this->success('编辑成功');
        } else {
            
            $this->error("编辑活动失败，入库异常！");
        }
    }
    // 上下架活动
    public function ChangeStatus() {
        
        // 百度活动id
        $id = I('id');
        $status = I('status');
        
        $dao = M('tbaidu_info');
        $data = array(
            'status' => $status);
        $result = $dao->where("id='" . $id . "'")->save($data);
        
        if ($result) {
            echo "1";
        } else {
            echo "2";
        }
        exit();
    }

    public function PublishBatch() {
        
        // 绑定渠道
        $channel_id = $this->_getChannelId();
        if (empty($channel_id)) {
            
            $this->error("百度渠道不存在或已停用！");
        }
        
        $batch_type = I('batch_type', '2');
        $search = array();
        $pageShow = '';
        $queryList = $this->selectActivityBatch();
        
        $batch_name = $this->_getBatchType($batch_type);
        $this->assign('batch_name', $batch_name);
        $this->assign('batch_type', $batch_type);
        $this->assign('queryList', $queryList);
        $this->assign('page', $pageShow);
        $this->display();
    }
    
    // 选择活动发布
    public function selectBatch() {
        $batch_id = I("batch_id");
        $batch_type = I("batch_type");
        
        $this->assign('batch_id', $batch_id);
        $this->assign('batch_type', $batch_type);
        $this->display();
    }
    
    // 显示发布活动提交页面
    public function submitBatch() {
        $batch_id = I("batch_id");
        $batch_type = I("batch_type");
        $open_type = I("open_type"); // 弹出框打开来源1渠道列表 2渠道发布活动
                                     
        // 判断渠道是否停用
        $channel_id = $this->_getChannelId();
        if (empty($channel_id)) {
            
            $this->assign('channel_flag', 1);
            $this->display();
            exit();
        }
        
        // 查询活动信息
        $batchInfo = $this->_getBatchInfo($batch_type, $batch_id);
        
        $this->assign('batch_id', $batch_id);
        $this->assign('batch_type', $batch_type);
        $this->assign('open_type', $open_type);
        $this->assign('batchInfo', $batchInfo);
        $this->display();
    }
    // 查看活动数据统计
    public function viewData() {
        
        // 查询渠道
        $channelid = $this->_getChannelId();
        $node_id = $this->nodeId;
        $model = M('tbatch_channel');
        $map = array(
            'node_id' => $node_id, 
            'channel_id' => $channelid);
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = $model->where($map)->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $model->where($map)
            ->order('id DESC')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        
        if ($list) {
            foreach ($list as $k => $v) {
                $mod = M('tbaidu_info');
                $query = $mod->where(
                    array(
                        'node_id' => $v['node_id'], 
                        'batch_id' => $v['batch_id'], 
                        'batch_type' => $v['batch_type']))->getField('batch_name');
                $list[$k]['name'] = $query;
            }
        }
        
        $batch_name = M('tchannel')->where(
            array(
                'node_id' => $node_id, 
                'id' => $channelid))->getField('name');
        $this->assign('batch_id', $batch_id);
        $this->assign('batch_type', $batch_type);
        $this->assign('batch_name', $batch_name);
        $this->assign('channel_type_arr', $channel_type_arr);
        $this->assign('query_list', $list);
        $this->assign('statusArr', $statusArr);
        
        $this->display(); // 输出模板
    }
    
    // 发布活动入库
    public function publishBatchFinal() {
        $channelid = $this->_getChannelId();
        
        if (empty($channelid)) {
            $this->error("百度渠道不存在或已停用！");
        }
        
        $node_id = $this->nodeId;
        $batch_name = I('batch_name');
        $batch_desc = I('batch_desc');
        $batch_id = I('batch_id');
        $batch_type = I('batch_type');
        $batch_pic = I('batch_pic');
        $open_type = I('open_type');
        
        if (empty($batch_id)) {
            $this->error('活动不能为空！');
        }
        
        if (empty($batch_name)) {
            $this->error('活动名不能为空！');
        }
        if (empty($batch_desc)) {
            $this->error('活动描述不能为空！');
        }
        
        // 判断活动是否存在或者已停用
        
        // 绑定渠道
        $channel_id = $this->_getChannelId();
        if (empty($channel_id)) {
            
            $this->error("百度渠道不存在或已停用！");
        }
        $geturl = $this->_getLabelUrl($batch_type, $batch_id);
        
        $flag = $this->move_image($batch_pic);
        if (! ($flag === true)) {
            $this->error($flag);
        }
        
        $picname = basename($batch_pic);
        
        // 查找门店
        $allStore = M('tstore_info')->where("node_id='$node_id' and status='0'")->select();
        
        if (! empty($allStore)) {
            
            M()->startTrans(); // 开启事务
            foreach ($allStore as $sk => $sinfo) {
                
                // 判断活动是否已经存在
                $baiduInfo = M('tbaidu_info')->where(
                    "node_id='" . $node_id . "' and store_id='" .
                         $sinfo['store_id'] . "' and batch_id='" . $batch_id .
                         "'")->find();
                
                if (empty($baiduInfo)) {
                    // 插入百度活动
                    $result = M('tbaidu_info')->add(
                        array(
                            'node_id' => $node_id, 
                            'batch_id' => $batch_id, 
                            'batch_type' => $batch_type, 
                            'batch_name' => $batch_name, 
                            'batch_pic' => $picname, 
                            'batch_desc' => $batch_desc, 
                            'store_id' => $sinfo['store_id'], 
                            'channel_id' => $channel_id, 
                            'status' => 1, 
                            'add_time' => date('YmdHis')));
                    
                    if (empty($result)) {
                        M()->rollback();
                        $this->error("发布活动失败，入库异常！");
                    }
                } else {
                    
                    M()->rollback();
                    $this->error("发布活动失败，活动已经发布，你可以编辑活动！");
                }
            }
            // 提交事务
            M()->commit();
            $this->success('活动发布到百度地图渠道成功！');
        }
    }
    
    // 移动图片 tmp->Upload/Baidu/nodeid
    private function move_image($image_name) {
        if (! $image_name) {
            return "请上传图片";
        }
        if (! is_dir(APP_PATH . '/Upload/Baidu')) {
            mkdir(APP_PATH . '/Upload/Baidu', 0777);
        }
        if (! is_dir(APP_PATH . '/Upload/Baidu/' . $this->node_id)) {
            mkdir(APP_PATH . '/Upload/Baidu/' . $this->node_id, 0777);
        }
        $old_image_url = APP_PATH . '/Upload/img_tmp/' . $this->node_id . '/' .
             basename($image_name);
        $new_image_url = APP_PATH . '/Upload/Baidu/' . $this->node_id . '/' .
             basename($image_name);
        $flag = copy($old_image_url, $new_image_url);
        if ($flag) {
            return true;
        } else {
            return "图片路径非法" . $old_image_url . "==" . $new_image_url;
        }
    }
    
    // 文件上传
    public function uploadFile() {
        import('ORG.Net.UploadFile');
        $upload = new UploadFile(); // 实例化上传类
        $upload->maxSize = 1024 * 1000; // 设置附件上传大小 1兆
        $upload->allowExts = array(
            'jpg', 
            'gif', 
            'png', 
            'jpeg'); // 设置附件上传类型
        $upload->savePath = $this->uploadPath; // 设置附件上传目录
        if (! $upload->upload()) { // 上传错误提示错误信息
            exit(
                json_encode(
                    array(
                        'info' => $upload->getErrorMsg(), 
                        'status' => 1)));
        } else { // 上传成功 获取上传文件信息
            $info = $upload->getUploadFileInfo();
            if ($info)
                $info = $info[0];
                // 添加到素材表
            $dao = M('Twx_material');
            $data = array(
                'node_id' => $this->nodeId, 
                'material_title' => $info['name'], 
                'material_img' => $info['savename'], 
                'material_size' => sprintf('%0.1f', $info['size'] / 1000), 
                'material_summary' => $material_summary, 
                'material_desc' => $material_desc, 
                'material_link' => '', 
                'parent_id' => '0', 
                'material_level' => 0, 
                'material_type' => 3, 
                'add_time' => date('YmdHis'));
            $result = $dao->add($data);
            if (! $result) {
                
                exit(
                    json_encode(
                        array(
                            'info' => "系统正忙", 
                            'status' => 1)));
            }
            exit(
                json_encode(
                    array(
                        'info' => array(
                            'fileId' => $result, 
                            'imgName' => $info['savename'], 
                            'imgUrl' => $this->_getImgUrl($info['savename'])), 
                        'status' => 0)));
        }
    }
    
    // 根据fileId,或者fileName获取图片
    public function getUploadImage() {
        return;
        $fileId = I('fileId');
        $fileName = I('fileName');
        $dao = M('twx_material');
        if ($fileId) {
            $where = array(
                'id' => $fileId);
        } else {
            $where = array(
                'material_img' => $fileName);
        }
        $info = $dao->where($where)->find();
        if (! $info) {
            die('图片不存在');
        }
        import('ORG.Util.Image');
        Image::showImg($this->uploadPath . $info['material_img']);
    }

    private function _getImgUrl($imgname) {
        return $this->uploadPath . $imgname;
    }
    
    // 选择互动模块
    public function selectActivityBatch() {
        $batch_type = I('batch_type', '2');
        $search = array();
        $pageShow = '';
        $queryList = $this->_getBatchList($batch_type, $search, $pageShow);
        $id = I('id');
        if (! empty($id)) {
            $mod = M('tchannel');
            $query = $mod->where(
                array(
                    'node_id' => array(
                        'exp', 
                        "in (" . $this->nodeIn() . ")"), 
                    'id' => $id))
                ->find();
            if (! $query)
                $this->error('错误参数');
            
            $this->assign('id', $id);
        }
        
        $batch_name = $this->_getBatchType($batch_type);
        $this->assign('batch_name', $batch_name);
        $this->assign('batch_type', $batch_type);
        $this->assign('queryList', $queryList);
        $this->assign('page', $pageShow);
        $this->display();
    }
    
    // 获取活动名称
    private function _getBatchType($batch_type) {
        $type_name = '';
        $type_name_arr = C('BATCH_TYPE_NAME');
        $type_name = $type_name_arr[$batch_type];
        if (! $type_name)
            $this->error('未知活动类型:' . $batch_type);
        return $type_name;
    }
    // 获取活动内容列表
    public function _getBatchList($batch_type, $search = array(), &$pageShow = null) {
        $dao = M('tmarketing_info');
        $search['node_id'] = $this->nodeId;
        $search['batch_type'] = $batch_type;
        if ($batch_type == '4') {
            $search['re_type'] = '0';
        }
        $totalNum = $dao->where($search)->count();
        import("ORG.Util.Page"); // 导入分页类
        $Page = new Page($totalNum, 10);
        $Page->setConfig('theme', 
            '%totalRow% %header% %nowPage%/%totalPage% 页 %first% %upPage% %linkPage% %downPage% %end%');
        $pageShow = $Page->show();
        $queryData = $dao->field("id batch_id,name,start_time,end_time")
            ->where($search)
            ->order("id desc")
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        // 获取活动类型
        $type_name = $this->_getBatchType($batch_type);
        if (! $queryData)
            $queryData = array();
        foreach ($queryData as &$v) {
            $v['info'] = $type_name . ' ＞ ' . $v['name'];
        }
        unset($v);
        
        return $queryData;
    }
    
    // 创建并获取渠道ID
    private function _getChannelId() {
        $node_id = $this->nodeId;
        $info = M('tchannel')->where(
            array(
                'node_id' => $node_id, 
                'type' => self::CHANNEL_TYPE_BD))->find();
        // 如果没有，则创建
        if (! $info) {
            $result = M('tchannel')->add(
                array(
                    'name' => '百度地图渠道', 
                    'type' => self::CHANNEL_TYPE_BD, 
                    'sns_type' => self::CHANNEL_SNS_TYPE_BD, 
                    'status' => '1', 
                    'node_id' => $node_id, 
                    'add_time' => date('YmdHis')));
            if (! $result) {
                return false;
            }
            $channelId = $result;
        } else {
            
            // 停用
            if ($info['status'] == 2) {
                return false;
            }
            $channelId = $info['id'];
        }
        if (! $channelId)
            return false;
        return $channelId;
    }
    
    // 创建标签地址
    private function _getLabelUrl($batch_type, $batch_id) {
        // http://222.44.51.34/wangcai_new/index.php?g=Label&m=News&a=index&id=634
        // 判断batch_type有效性，以及得到标签模块action名
        $m = 'Label';
        switch ($batch_type) {
            case '4': // 会员粉丝招幕(特殊处理,不需要跳到标签)
                $m = 'MemberRegistration';
                break;
        }
        $channel_id = $this->_getChannelId();
        $node_id = $this->nodeId;
        if (! $channel_id) {
            return false;
        }
        // 判断是否已经生成过标签
        $labelInfo = M('tbatch_channel')->where(
            "node_id='$node_id'
		and batch_type='$batch_type'
		and batch_id='$batch_id'
		and channel_id='$channel_id'")->find();
        
        if (! $labelInfo) {
            // 插入到活动标签表 batch_channel
            $labelId = M('tbatch_channel')->add(
                array(
                    'batch_type' => $batch_type, 
                    'batch_id' => $batch_id, 
                    'channel_id' => $channel_id, 
                    'add_time' => date('YmdHis'), 
                    'node_id' => $node_id, 
                    'status' => '1'));
        } else {
            $labelId = $labelInfo['id'];
        }
        if (! $labelId)
            return false;
        $url = U('Label/' . $m . '/index/', 
            array(
                'id' => $labelId), '', '', true);
        return $url;
    }
    
    // 获取活动内容
    private function _getBatchInfo($batch_type, $batch_id) {
        $batch_info = M('tmarketing_info')->where(
            "node_id='" . $this->nodeId . "' and id='" . $batch_id . "'")->find();
        return $batch_info;
    }

    public function getActivityLabelUrl() {
        $batch_type = I('batch_type');
        $batch_id = I('batch_id');
        
        $url = $this->_getLabelUrl($batch_type, $batch_id);
        $this->success(array(
            'url' => $url), '', true);
    }
}