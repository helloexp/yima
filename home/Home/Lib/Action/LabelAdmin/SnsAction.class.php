<?php

class SnsAction extends BaseAction {

    public $succ_arr = array();

    public function index() {
        $batchId = I('get.batch_id', null, 'intval');
        $channelId = I('get.channel_id', null, 'intval');
        $batchType = I('get.batch_type', null);
        $label_id = I('get.label_id', null);
        if (is_null($batchId) || is_null($channelId) || is_null($batchType)) {
            die('缺少必要参数！');
        }
        $lebelInfo = M('tbatch_channel')->field("id")
            ->where(
            "batch_type='{$batchType}' AND batch_id='{$batchId}' AND channel_id='{$channelId}'")
            ->find();
        if (! $lebelInfo) {
            die('未找到该活动渠道!');
        }
        // 分享类型
        $snsType = M('tchannel')->where(
            "node_id='{$this->node_id}' AND id='{$channelId}'")->getField(
            'sns_type');
        switch ($snsType) {
            case '1':
                $titleName = '分享到新浪微博';
                break;
            case '2':
                $titleName = '分享到腾讯微博';
                break;
            case '3':
                $titleName = '分享到QQ空间';
                break;
            case '4':
                $titleName = '分享到人人网';
                break;
            case '5':
                $titleName = '分享到开心网';
                break;
            case '6':
                $titleName = '分享到豆瓣网';
                break;
            case '7':
                $titleName = '分享到网易微博';
                break;
            case '8':
                $titleName = '分享到搜狐微博';
                break;
            default:
                $titleName = '分享';
        }
        
        $config_url_arr = C('BATCH_WAP_URL');
        $url = $config_url_arr[$batchType];
        if (! $url)
            die('未知该活动类型！');
        $batchMode = M('tmarketing_info');
        $wapUrl = CURRENT_HOST . U($url, "id={$lebelInfo['id']}");
        
        $batchInfo = $batchMode->where(
            "node_id='{$this->node_id}' AND id='{$batchId}'")->find();
        if (! $batchInfo)
            die('未找到该活动！');
        $ajaxData = array(
            'status' => '1', 
            'batchName' => $batchInfo['name'], 
            'channelId' => $channelId, 
            'batchUrl' => $wapUrl, 
            'titleName' => $titleName, 
            'label_id' => $label_id, 
            'snsType' => $snsType);
        $this->assign('query_arr', $ajaxData);
        $this->display();
    }

    function otherSns() {
        $batchId = I('post.batch_id', null);
        $channelId = I('post.channel_id', null);
        $batchType = I('post.batch_type', null);
        if (is_null($batchId) || is_null($channelId) || is_null($batchType)) {
            $this->error('缺少必要参数');
        }
        $lebelInfo = M('tbatch_channel')->field("id")
            ->where(
            "batch_type='{$batchType}' AND batch_id='{$batchId}' AND channel_id='{$channelId}'")
            ->find();
        if (! $lebelInfo) {
            $this->error('未找到该活动渠道');
        }
        // 分享类型
        $snsType = M('tchannel')->where(
            "node_id='{$this->node_id}' AND id='{$channelId}'")->getField(
            'sns_type');
        switch ($snsType) {
            case '1':
                $titleName = '分享到新浪微博';
                break;
            case '2':
                $titleName = '分享到腾讯微博';
                break;
            case '3':
                $titleName = '分享到QQ空间';
                break;
            case '4':
                $titleName = '分享到人人网';
                break;
            case '5':
                $titleName = '分享到开心网';
                break;
            case '6':
                $titleName = '分享到豆瓣网';
                break;
            case '7':
                $titleName = '分享到网易微博';
                break;
            case '8':
                $titleName = '分享到搜狐微博';
                break;
            default:
                $titleName = '分享';
        }
        // 获取活动信息
        $config_url_arr = C('BATCH_WAP_URL');
        $url = $config_url_arr[$batchType];
        if (! $url)
            $this->error('未知该活动类型！');
        $batchMode = M('tmarketing_info');
        $wapUrl = CURRENT_HOST . U($url, "id={$lebelInfo['id']}");
        
        $batchInfo = $batchMode->where(
            "node_id='{$this->node_id}' AND id='{$batchId}'")->find();
        if (! $batchInfo)
            $this->error('未找到该活动');
        $ajaxData = array(
            'status' => '1', 
            'batchName' => $batchInfo['name'], 
            'channelId' => $channelId, 
            'batchUrl' => $wapUrl, 
            'titleName' => $titleName);
        $this->ajaxReturn($ajaxData);
    }

    /**
     * 检查渠道是否已绑定
     *
     * @param $channelId 渠道id
     * @return 1已绑定 2已过期 3未绑定
     */
    public function checkBinding($channelId) {
        // 是否是有效渠道
        $result = M('tchannel')->where(
            "node_id in ({$this->nodeIn()}) AND id='{$channelId}'")->find();
        if (! $result)
            $this->error('未找到该渠道');
        if ($result['type'] != '1')
            $this->error('该渠道不是互联网渠道,无法绑定');
            // 是否已绑定
        $data = M('tsns_node')->where(
            "node_id='{$this->node_id}' AND channel_id='{$channelId}'")->find();
        if ($data && $data['type'] == $result['sns_type']) {
            $time = time() - $data['update_time'];
            if ($time < $data['expires_in']) {
                return '1';
            }
            return '2';
        }
        return '3';
    }

    /**
     * 一键发布
     *
     * @param $channelId 渠道id
     * @param $bacthId 活动id
     */
    public function putOut() {
        header('Content-type:text/html;charset=utf8');
        $content = trim($_POST['content']);
        $channelId = I('post.channel_id', null);
        $id = I('post.label_id', null);
        $pic_url = I('post.pic_url', null);
        if (! isset($content) && $content != '')
            $this->error('请输入分享内容');
        if (is_null($channelId) || is_null($id))
            $this->error('缺少必要参数');
        $result = $this->checkBinding($channelId);
        if ($result != '1') {
            $this->error('noBind');
        }
        // 分享类型
        $snsType = M('tchannel')->where(
            "node_id='{$this->node_id}' AND id='{$channelId}'")->getField(
            'sns_type');
        $data = M('tsns_node')->where(
            "node_id='{$this->node_id}' AND channel_id='{$channelId}'")->find();
        $picUrl = U('LabelAdmin/ShowCode/index', 'id=' . $channelId);
        // 加载ThinkOauth类并实例化一个对象
        import("ORG.ThinkSDK.ThinkOauth");
        $sns = ThinkOauth::getInstance(C("SNS_SDK.$snsType"), $data);
        switch ($snsType) {
            case 1: // 新浪
                
                if (! empty($pic_url)) {
                    $img_url = CURRENT_HOST . '/Home/Upload/img_tmp/' .
                         $this->node_id . '/' . $pic_url;
                } else {
                    $img_url = CURRENT_HOST .
                         U('LabelAdmin/ShowCode/index', 'id=' . $id);
                }
                $data = array(
                    'status' => $content, 
                    'url' => $img_url);
                $data = $sns->call('statuses/upload_url_text', $data, 'POST');
                if ($data['error_code'] == 0) {
                    $this->success('分享成功');
                } else {
                    $this->error("分享失败:{$data['error']}");
                }
                break;
            case 2: // 腾讯
                $data = array(
                    'content' => $content, 
                    'pic_url' => CURRENT_HOST .
                         U('LabelAdmin/ShowCode/index', 'id=' . $id));
                $data = $sns->call('t/add_pic_url', $data, 'POST');
                if ($data['ret'] == 0) {
                    $this->success('分享成功');
                } else {
                    $this->error("分享失败:{$data['msg']}");
                }
                break;
            case 3: // qq空间
                $batchName = I('post.batch_name', null);
                $batchUrl = $_POST['batch_url'];
                if (is_null($batchName) || empty($batchUrl)) {
                    $this->error('缺少参数');
                }
                $data = array(
                    'title' => $batchName, 
                    'url' => $batchUrl, 
                    'comment' => $content, 
                    'summary' => '欢迎大家点击上面的链接或用手机扫描二维码来参与我们的活动哦~~~', 
                    'images' => CURRENT_HOST .
                         U('LabelAdmin/ShowCode/index', 'id=' . $id), 
                        'nswb' => '1');
                $data = $sns->call('share/add_share', $data, 'POST');
                if ($data['ret'] == 0) {
                    $this->success('分享成功');
                } else {
                    $this->error("分享失败:{$data['msg']}");
                }
                break;
            case 4: // 人人
                
                $data = array(
                    'comment' => $content, 
                    'url' => CURRENT_HOST .
                         U('LabelAdmin/ShowCode/index', 'id=' . $id));
                $data = $sns->call('/v2/share/url/put', $data, 'POST');
                dump($data);
                exit();
                if (! isset($data['error'])) {
                    $this->success('分享成功');
                } else {
                    $this->error("分享失败:{$data['error']['message']}");
                }
                break;
            case 5: // 开心
                    
                // 开心网验证图片路径的特殊性，必须要先生成一张图片
                $uploadDir = C('UPLOAD');
                import('@.Vendor.phpqrcode.phpqrcode') or
                     die('include file fail.');
                $url = U('Label/Label/index', 
                    array(
                        'id' => $id), '', '', true);
                $filename = $uploadDir . 'share_' . time() . '.png';
                QRcode::png($url, $filename, '', '2', $margin = 2, false);
                $data = array(
                    'content' => $content, 
                    'picurl' => get_upload_url($filename)); // 这里写生产上的路径,测试上调试完要改回来
                
                $data = $sns->call('records/add', $data, 'POST');
                if (is_file($filename)) {
                    unlink($filename);
                }
                if (! empty($data['rid'])) {
                    $this->success('分享成功');
                } else {
                    $this->error("分享失败:{$data['error']}");
                }
                break;
            case '6': // 豆瓣
                
                $uploadDir = C('UPLOAD');
                import('@.Vendor.phpqrcode.phpqrcode') or
                     die('include file fail.');
                $url = U('Label/Label/index', 
                    array(
                        'id' => $id), '', '', true);
                $filename = $uploadDir . 'share_' . time() . '.png';
                QRcode::png($url, $filename, '', '2', $margin = 2, false);
                
                // 豆瓣不支持png，将png重命名为jpeg
                /*
                 * if(is_file($filename)){ $ext = strripos($filename,'.');
                 * $newFile = substr($filename,0,$ext).'.jpeg';
                 * if(rename($filename, $newFile)){ $filename = $newFile; } }
                 */
                
                if (is_file($filename)) {
                    $outPut = substr($filename, 0, strripos($filename, '.')) .
                         '.jpeg';
                    $image = imagecreatefrompng($filename);
                    imagejpeg($image, $outPut);
                    imagedestroy($image);
                    unlink($filename);
                    $filename = $outPut;
                }
                
                $data = array(
                    'text' => $content, 
                    // 7.16.0不支持此写方法,测试环境用该写法
                    // 'image' => '@'.realpath($filename)
                    // CURL 7.19.7支持此写法，生产环境用该写法
                    'image' => '@' . realpath($filename) . ';type=image/jpeg');
                $data = $sns->call('shuo/v2/statuses/', $data, 'POST', true);
                // dump($data);exit;
                if (is_file($filename)) {
                    unlink($filename);
                }
                if ($data && empty($data['msg'])) {
                    $this->success('分享成功');
                } else {
                    $this->error("分享失败:{$data['msg']}");
                }
                break;
            case '7': // 网易微博
                      // 网易微博必需要先上传一张图片，得到图片地址在发布带图片的微博
                $uploadDir = C('UPLOAD');
                import('@.Vendor.phpqrcode.phpqrcode') or
                     die('include file fail.');
                $url = U('Label/Label/index', 
                    array(
                        'id' => $id), '', '', true);
                $filename = $uploadDir . 'share_' . time() . '.png';
                QRcode::png($url, $filename, '', '2', $margin = 2, false);
                $data = array(
                    'pic' => '@' . realpath($filename));
                $data = $sns->call('statuses/upload', $data, 'POST', true);
                if (is_file($filename)) {
                    unlink($filename);
                }
                if ($data['error_code'] != 0) {
                    $this->error("分享失败:{$data['error']}");
                }
                // 获取的图片地址
                $uploadImageUrl = $data['upload_image_url'];
                $data = array(
                    'status' => $content . $uploadImageUrl);
                $data = $sns->call('statuses/update', $data, 'POST');
                if ($data['error_code'] == 0) {
                    $this->success('分享成功');
                } else {
                    $this->error("分享失败:{$data['error']}");
                }
                break;
            case '8': // 搜狐微博
                $data = array(
                    'text' => $content, 
                    'rec_image' => CURRENT_HOST .
                         U('LabelAdmin/ShowCode/index', 'id=' . $id));
                $data = $sns->call('shuo/v2/statuses/', $data, 'POST');
                if ('success' == $data['message'] && ! empty($data['data'])) {
                    $this->success('分享成功');
                } else {
                    $this->error("分享失败:{$data['message']}");
                }
                break;
            default:
                $this->error('未知的分享类型');
        }
    }

    /**
     * 授权开始
     *
     * @param $channelId 渠道id
     */
    public function authorize() {
        $channelId = I('get.channel_id');
        $this->checkBinding($channelId);
        // $result = $this->checkBinding($channelId);
        // if($result == '1') $this->error('该渠道已经绑定');
        $snsType = M('tchannel')->where(
            "node_id='{$this->node_id}' AND id='{$channelId}'")->getField(
            'sns_type');
        $snsData = array(
            'channel_id' => $channelId, 
            'type' => $snsType);
        session('sns', $snsData);
        // 加载ThinkOauth类并实例化一个对象
        import("ORG.ThinkSDK.ThinkOauth");
        $sns = ThinkOauth::getInstance(C("SNS_SDK.$snsType"));
        // echo $sns->getRequestCodeURL();exit;
        // 跳转到授权页面
        redirect($sns->getRequestCodeURL());
    }
    
    // 授权回调地址绑定渠道
    public function sns_Callback($type = null, $code = null) {
        header('Content-type:text/html;charset=UTF-8');
        (empty($type) || empty($code)) && $this->error('参数错误');
        
        // 加载ThinkOauth类并实例化一个对象
        import("ORG.ThinkSDK.ThinkOauth");
        $sns = ThinkOauth::getInstance($type);
        
        // 腾讯微博需传递的额外参数
        $extend = null;
        if ($type == 'tencent') {
            $extend = array(
                'openid' => $this->_get('openid'), 
                'openkey' => $this->_get('openkey'));
        }
        
        // 请妥善保管这里获取到的Token信息，方便以后API调用
        // 调用方法，实例化SDK对象的时候直接作为构造函数的第二个参数传入
        // 如： $qq = ThinkOauth::getInstance('qq', $token);
        $token = $sns->getAccessToken($code, $extend);
        if (is_array($token)) { // 授权数据插入
            
            /**
             * ********只用于平台上线审核，该处代码与主逻辑无关可以删除************
             */
            if (session('snscheck')) {
                session('snscheck', null);
                switch ($type) {
                    case 'qq':
                        $qq = ThinkOauth::getInstance('qq', $token);
                        $data = $qq->call('user/get_user_info');
                        if ($data['ret'] != 0) {
                            throw_exception("获取腾讯QQ用户信息失败：{$data['msg']}");
                        }
                        $userInfo['userName'] = $data['nickname'];
                        $userInfo['userPic'] = $data['figureurl_qq_2'];
                        session('snsUserInfo', $userInfo);
                        break;
                    case 'renren':
                        $renren = ThinkOauth::getInstance('renren', $token);
                        $data = $renren->call('/v2/user/get');
                        if (isset($data['error'])) {
                            throw_exception("获取人人网用户信息失败：{$data['error_msg']}");
                        }
                        // dump($data);exit;
                        $userInfo['userName'] = $data['response']['name'];
                        $userInfo['userPic'] = $data['response']['avatar']['0']['url'];
                        session('snsUserInfo', $userInfo);
                        break;
                    case 'douban':
                        $douban = ThinkOauth::getInstance('douban', $token);
                        $data = $douban->call('v2/user/~me');
                        if (! empty($data['code'])) {
                            throw_exception("获取豆瓣用户信息失败：{$data['msg']}");
                        }
                        // dump($data);exit;
                        $userInfo['userName'] = $data['name'];
                        $userInfo['userPic'] = $data['avatar'];
                        session('snsUserInfo', $userInfo);
                        break;
                    case 't163':
                        $t163 = ThinkOauth::getInstance('T163', $token);
                        $data = $t163->call('users/show');
                        if ($data['error_code'] != 0) {
                            throw_exception("获取网易微博用户信息失败：{$data['error']}");
                        }
                        // dump($data);exit;
                        $userInfo['userName'] = $data['name'];
                        $userInfo['userPic'] = $data['profile_image_url'];
                        session('snsUserInfo', $userInfo);
                        break;
                }
                
                $this->redirect('Home/Login/index');
            }
            /**
             * ********只用于平台上线审核，该处代码与主逻辑无关可以删除************
             */
            
            $snsData = session('sns');
            $time = time();
            $snsNode = M('tsns_node');
            $id = $snsNode->where(
                "node_id='{$this->node_id}' AND channel_id={$snsData['channel_id']}")->getField(
                'id');
            $data = array(
                'node_id' => $this->node_id, 
                'channel_id' => $snsData['channel_id'], 
                'type' => $snsData['type'], 
                'name' => $token['name'], 
                'access_token' => $token['access_token'], 
                'expires_in' => $token['expires_in'], 
                'openid' => $token['openid'], 
                'openkey' => $token['openkey'], 
                'create_time' => $time, 
                'update_time' => $time);
            session('sns', null);
            if ($id) { // 更新
                $result = $snsNode->where("id='{$id}'")->save($data);
                if ($result) {
                    $this->assign('mes', '1');
                } else {
                    $this->assign('mes', '0');
                }
            } else { // 添加
                $result = $snsNode->add($data);
                if ($result) {
                    $this->assign('mes', '1');
                } else {
                    $this->assign('mes', '0');
                }
            }
        } else { // 授权失败
            $this->assign('mes', '0');
        }
        $this->display('bindMessage');
    }

    /**
     * ********只用于平台上线审核，该方法与主逻辑无关可以删除************
     */
    public function authorizeSina() {
        $type = I('get.type');
        session('snscheck', true);
        // 加载ThinkOauth类并实例化一个对象
        import("ORG.ThinkSDK.ThinkOauth");
        $sns = ThinkOauth::getInstance($type);
        // echo $sns->getRequestCodeURL();exit;
        // 跳转到授权页面
        redirect($sns->getRequestCodeURL());
    }

    public function loginOut() {
        header('Content-type:text/html;charset=UTF-8');
        if (session('?snsUserInfo')) {
            session('snsUserInfo', null);
        }
        redirect(U('Home/Login/index'), 2, '正在退出，请稍后~~');
    }
}

















