<?php

class WeixinChannelAction extends BaseAction {

    public $uploadPath;

    public function _initialize() {
        if (ACTION_NAME == 'showCode') {
            return;
        }
        parent::_initialize();
        $this->uploadPath = './Home/Upload/Weixin/Qrcode/'; // 设置附件上传目录
                                                                // C('LABEL_ADMIN',include(CONF_PATH.'LabelAdmin/config.php'));
    }
    
    // 推广主页
    public function index() {
        $node_id = $this->node_id;
        // 查询访问量
        $channelArr = M()->table('twx_qrchannel a')
            ->join('tchannel b on b.id=a.channel_id')
            ->field(
            "a.channel_id,a.click_count,a.subscribe_count,a.add_time,b.name channel_name,b.type channel_type,b.sns_type")
            ->where(array(
            'a.node_id' => $node_id))
            ->select();
        $channel_type_arr = C('CHANNEL_TYPE_ARR');
        foreach ($channelArr as &$v) {
            $v['sns_type_name'] = show_defined_text($v['sns_type'], 
                $channel_type_arr[$v['channel_type']]);
        }
        unset($v);
        $this->assign('channelArr', $channelArr);
        $this->display();
    }
    // 发布到渠道列表
    public function channelList() {
        // 渠道
        $node_id = $this->nodeId;
        $channel_arr = C('CHANNEL_TYPE');
        $query_arr = array();
        $map = array(
            'node_id' => $node_id);
        $model = M('tchannel');
        $c_count = $model->where($map)->count();
        
        if ($c_count == 0) {
            $this->error('请先添加渠道！', 
                array(
                    '去添加' => U('Channel/add')));
        }
        
        // 已发布的
        $batch_channel = M('tweixin_channel');
        $batch_map = array(
            'node_id' => $node_id, 
            'status' => '1');
        $change_channel = $batch_channel->where($batch_map)->getField(
            'channel_id', true);
        
        // 已绑定的渠道
        foreach ($channel_arr as $k => $v) {
            if ($k == '3' || $k == '4')
                continue;
            $map['type'] = $k;
            $query_arr[$k] = $model->where($map)
                ->field('id,name,type,sns_type,batch_type,batch_id')
                ->select();
            if (! $query_arr[$k]) {
                unset($query_arr[$k]);
            }
        }
        
        $this->assign('change_channel', $change_channel);
        $this->assign('channel_arr', $channel_arr);
        $this->assign('query_arr', $query_arr);
        $this->display();
    }
    
    // 发布到渠道提交
    public function bindChannel() {
        $node_id = $this->nodeId;
        $channel = I('channel');
        if (! $channel) {
            $this->error('请选择渠道');
        }
        // 查询公众账号配置
        $weixinInfo = M('tweixin_info')->where(
            array(
                'node_id' => $node_id))->find();
        // 1.校验公众账号
        if (! $weixinInfo || ! $weixinInfo['app_id'] ||
             ! $weixinInfo['app_secret']) {
            $this->error("请先配置微信公众账号。", 
                array(
                    '确定' => U('Weixin/Weixin/index')));
        }
        // 去微信获取token
        $wxService = D('WeiXinQrcode', 'Service');
        $wxService->init($weixinInfo['app_id'], $weixinInfo['app_secret'], 
            $weixinInfo['app_access_token']);
        // 绑定成功渠道id
        foreach ($channel as $k => $v) {
            // 判断该渠道是否应绑定
            $_vInfo = M('tchannel')->where(
                array(
                    'node_id' => $node_id, 
                    'id' => $v))->find();
            if (! $_vInfo) {
                $this->error("渠道号【" . $v . "】不存在！");
            }
            // 查看是否绑定过渠道
            $wcInfo = M('twx_qrchannel')->where(
                array(
                    'node_id' => $node_id, 
                    'channel_id' => $v))
                ->field("scene_id,id")
                ->find();
            if (! $wcInfo || ! $wcInfo['scene_id']) {
                $scene_id = D('TweixinInfo')->getSceneId($node_id);
            } else {
                $scene_id = $wcInfo['scene_id'];
            }
            if (! $scene_id) {
                $this->error("生成 scene_id 失败");
            }
            // 去微信接口获取图片内容
            $qrResult = $wxService->getQrcodeImg(
                array(
                    'scene_id' => $scene_id));
            // 更新accessToken
            if ($weixinInfo['app_access_token'] != $wxService->accessToken) {
                $query = M('tweixin_info')->where(
                    array(
                        'node_id' => $node_id))->save(
                    array(
                        'app_access_token' => $wxService->accessToken));
            }
            
            // 如果失败
            if ($qrResult['status'] != '1') {
                $this->error(
                    "获取推广二维码失败，原因：" . $qrResult['errcode'] . ':' .
                         $qrResult['errmsg']);
            }
            $code_img = $qrResult['img_content'];
            // 生成静态文件
            if ($code_img) {
                file_put_contents(
                    $this->uploadPath . $node_id . '_' . $scene_id . '.jpg', 
                    $code_img);
            }
            $code_img = base64_encode($code_img);
            // 更新
            if ($wcInfo) {
                $data = array(
                    'code_img' => $code_img, 
                    'scene_id' => $scene_id);
                $query = M('twx_qrchannel')->where(
                    array(
                        'id' => $wcInfo['id']))->save($data);
            }  // 添加
else {
                $data = array(
                    'scene_id' => $scene_id, 
                    'code_img' => $code_img, 
                    'channel_id' => $v, 
                    'add_time' => date('YmdHis'), 
                    'node_id' => $node_id);
                $query = M('twx_qrchannel')->add($data);
            }
            if ($query === false) {
                M()->rollback();
                Log::write('twx_qrchannel:' . print_r($data, true));
                $this->error("发布失败,渠道号:" . $v);
            }
        }
        node_log('微信推广');
        $query = M()->commit();
        // 查询发布渠道
        $qrChannelArr = M()->table('twx_qrchannel a ')
            ->join('tchannel b on b.id=a.channel_id')
            ->field("a.*,b.name channel_name,b.type channel_type,b.sns_type")
            ->where(array(
            'a.node_id' => $node_id))
            ->select();
        // 处理一下地址
        foreach ($qrChannelArr as &$v) {
            $v['qr_img_url'] = $this->uploadPath . $node_id . '_' .
                 $v['scene_id'] . '.jpg';
        }
        unset($v);
        $this->assign('qrChannelArr', $qrChannelArr);
        $this->assign('carr', $carr);
        $this->display('succMsg');
    }
    // 显示图片
    public function showCode() {
        $id = I('get.id');
        do {
            $default_img_content = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAAkCAYAAABIdFAMAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAHhJREFUeNo8zjsOxCAMBFB/KEAUFFR0Cbng3nQPw68ArZdAlOZppPFIBhH5EAB8b+Tlt9MYQ6i1BuqFaq1CKSVcxZ2Acs6406KUgpt5/LCKuVgz5BDCSb13ZO99ZOdcZGvt4mJjzMVKqcha68iIePB86GAiOv8CDADlIUQBs7MD3wAAAABJRU5ErkJggg%3D%3D';
            if (! $id) {
                $img_content = $default_img_content;
                break;
            }
            $img_content = M('twx_qrchannel')->where("id='$id'")->getField(
                'code_img');
            if (! $img_content) {
                $img_content = $default_img_content;
                exit();
            }
        }
        while (0);
        $isdown = I('get.isdown');
        // 下载
        if ($isdown) {
            $filename = 'qrcode_scene' . $id . '.jpg';
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . $filename);
        } else {
            header('Content-Type:image/png');
        }
        echo base64_decode($img_content);
        exit();
    }
}