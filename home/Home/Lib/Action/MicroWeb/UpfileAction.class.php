<?php
// 文件上传
class UpfileAction extends BaseAction {

    public $errormsg = '';

    public $imgurl = '';

    public $maxSize;

    public $allowExts;

    public function _initialize() {
        $this->_checkLogin();
    }

    public function index() {
        $type = I('get.type');
        if ($type == 'img' || $type == 'audio') {
            $this->setAstrict($type);
        } else {
            $arr = array(
                'msg' => '-111',  // 通信是否成功
                'error' => "未知上传类型",  // 返回错误
                'imgurl' => $type); // 返回图片名
            
            echo json_encode($arr);
            exit();
        }
        import('ORG.Net.UploadFile');
        $upload = new UploadFile(); // 实例化上传类
        $upload->maxSize = $this->maxSize;
        $upload->allowExts = $this->allowExts;
        $upload->savePath = APP_PATH . '/Upload/'; // 设置附件
        $upload->saveRule = time() . sprintf('%04s', mt_rand(0, 1000));
        if (! $upload->upload()) { // 上传错误提示错误信息
            $this->errormsg = $upload->getErrorMsg();
        } else { // 上传成功 获取上传文件信息
            $info = $upload->getUploadFileInfo();
            $this->imgurl = $info[0]['savename'];
        }
        $arr = array(
            'msg' => '0000',  // 通信是否成功
            'error' => $this->errormsg,  // 返回错误
            'imgurl' => $this->imgurl); // 返回图片名
        
        echo json_encode($arr);
        exit();
    }

    /*
     * 设置类型，大小限制；
     */
    public function setAstrict($type) {
        $img_config = C('UPLOAD_IMG');
        $audio_config = C('UPLOAD_AUDIO');
        if ($type == "img") {
            $this->maxSize = $audio_config['SIZE']; // 设置附件上传大小
            $this->allowExts = (array) explode(",", $img_config['TYPE']); // 设置附件上传类型
        }
        if ($type == "audio") {
            $this->maxSize = $audio_config['SIZE']; // 设置附件上传大小
            $this->allowExts = (array) explode(",", $audio_config['TYPE']); // 设置附件上传类型
        }
    }

    /**
     * 富文本编辑器图片上传
     */
    public function editoImageSave() {
        $this->setAstrict('img');
        import('ORG.Net.UploadFile');
        $upload = new UploadFile(); // 实例化上传类
        $upload->maxSize = $this->maxSize;
        $upload->allowExts = $this->allowExts;
        $upload->savePath = APP_PATH . '/Upload/'; // 设置附件
        $upload->saveRule = time() . sprintf('%04s', mt_rand(0, 1000));
        if (! $upload->upload()) { // 上传错误提示错误信息
            $this->errormsg = $upload->getErrorMsg();
            echo "{'url':'','title':'','original':'','state':'{$this->errormsg}'}";
            exit();
        } else { // 上传成功 获取上传文件信息
            $info = $upload->getUploadFileInfo();
            $this->imgurl = $info[0]['savename'];
            echo "{'url':'{$this->imgurl}','title':'','original':'','state':'SUCCESS'}";
            exit();
        }
    }

    /**
     * 宁夏移动富文本编辑器图片上传
     */
    public function editoImageMobileSave() {
        $this->setAstrict('img');
        import('ORG.Net.UploadFile');
        $upload = new UploadFile(); // 实例化上传类
        $upload->maxSize = $this->maxSize;
        $upload->allowExts = $this->allowExts;
        $upload->savePath = APP_PATH . '/Upload/mobile/'; // 设置附件
        $upload->saveRule = time() . sprintf('%04s', mt_rand(0, 1000));
        if (! $upload->upload()) { // 上传错误提示错误信息
            $this->errormsg = $upload->getErrorMsg();
            echo "{'url':'','title':'','original':'','state':'{$this->errormsg}'}";
            exit();
        } else { // 上传成功 获取上传文件信息
            $info = $upload->getUploadFileInfo();
            $this->imgurl = $info[0]['savename'];
            echo "{'url':'{$this->imgurl}','title':'','original':'','state':'SUCCESS'}";
            exit();
        }
    }

    /**
     * 富文本编辑器图片远程抓取
     */
    public function getRemoteImage() {
        // 远程抓取图片配置
        $config = array(
            "savePath" => APP_PATH . '/Upload/',  // 保存路径
            "allowFiles" => array(
                ".gif", 
                ".png", 
                ".jpg", 
                ".jpeg", 
                ".bmp"),  // 文件允许格式
            "maxSize" => 1000); // 文件大小限制，单位KB
        
        $uri = htmlspecialchars($_POST['upfile']);
        $uri = str_replace("&amp;", "&", $uri);
        
        // 忽略抓取时间限制
        set_time_limit(0);
        // ue_separate_ue ue用于传递数据分割符号
        $imgUrls = explode("ue_separate_ue", $uri);
        $tmpNames = array();
        foreach ($imgUrls as $imgUrl) {
            // http开头验证
            if (strpos($imgUrl, "http") !== 0) {
                array_push($tmpNames, "error");
                continue;
            }
            // 获取请求头
            $heads = get_headers($imgUrl);
            // 死链检测
            if (! (stristr($heads[0], "200") && stristr($heads[0], "OK"))) {
                array_push($tmpNames, "error");
                continue;
            }
            
            // 格式验证(扩展名验证和Content-Type验证)
            $fileType = strtolower(strrchr($imgUrl, '.'));
            if (! in_array($fileType, $config['allowFiles']) ||
                 stristr($heads['Content-Type'], "image")) {
                array_push($tmpNames, "error");
                continue;
            }
            
            // 打开输出缓冲区并获取远程图片
            ob_start();
            $context = stream_context_create(
                array(
                    'http' => array(
                        'follow_location' => false))); // don't follow redirects

            
            // 请确保php.ini中的fopen wrappers已经激活
            readfile($imgUrl, false, $context);
            $img = ob_get_contents();
            ob_end_clean();
            
            // 大小验证
            $uriSize = strlen($img); // 得到图片大小
            $allowSize = 1024 * $config['maxSize'];
            if ($uriSize > $allowSize) {
                array_push($tmpNames, "error");
                continue;
            }
            // 创建保存位置
            $savePath = $config['savePath'];
            if (! file_exists($savePath)) {
                mkdir("$savePath", 0777);
            }
            // 写入文件
            $tmpName = $savePath . rand(1, 10000) . time() .
                 strrchr($imgUrl, '.');
            try {
                $fp2 = @fopen($tmpName, "a");
                fwrite($fp2, $img);
                fclose($fp2);
                array_push($tmpNames, basename($tmpName));
            } catch (Exception $e) {
                array_push($tmpNames, "error");
            }
        }
        /**
         * 返回数据格式 { 'url' : '新地址一ue_separate_ue新地址二ue_separate_ue新地址三',
         * 'srcUrl': '原始地址一ue_separate_ue原始地址二ue_separate_ue原始地址三'， 'tip' :
         * '状态提示' }
         */
        echo "{'url':'" . implode("ue_separate_ue", $tmpNames) .
             "','tip':'远程图片抓取成功！','srcUrl':'" . $uri . "'}";
    }

    /**
     * 宁夏移动富文本编辑器图片远程抓取
     */
    public function getRemoteMobileImage() {
        // 远程抓取图片配置
        $config = array(
            "savePath" => APP_PATH . '/Upload/mobile/',  // 保存路径
            "allowFiles" => array(
                ".gif", 
                ".png", 
                ".jpg", 
                ".jpeg", 
                ".bmp"),  // 文件允许格式
            "maxSize" => 1000); // 文件大小限制，单位KB
        
        $uri = htmlspecialchars($_POST['upfile']);
        $uri = str_replace("&amp;", "&", $uri);
        
        // 忽略抓取时间限制
        set_time_limit(0);
        // ue_separate_ue ue用于传递数据分割符号
        $imgUrls = explode("ue_separate_ue", $uri);
        $tmpNames = array();
        foreach ($imgUrls as $imgUrl) {
            // http开头验证
            if (strpos($imgUrl, "http") !== 0) {
                array_push($tmpNames, "error");
                continue;
            }
            // 获取请求头
            $heads = get_headers($imgUrl);
            // 死链检测
            if (! (stristr($heads[0], "200") && stristr($heads[0], "OK"))) {
                array_push($tmpNames, "error");
                continue;
            }
            
            // 格式验证(扩展名验证和Content-Type验证)
            $fileType = strtolower(strrchr($imgUrl, '.'));
            if (! in_array($fileType, $config['allowFiles']) ||
                 stristr($heads['Content-Type'], "image")) {
                array_push($tmpNames, "error");
                continue;
            }
            
            // 打开输出缓冲区并获取远程图片
            ob_start();
            $context = stream_context_create(
                array(
                    'http' => array(
                        'follow_location' => false))); // don't follow redirects

            
            // 请确保php.ini中的fopen wrappers已经激活
            readfile($imgUrl, false, $context);
            $img = ob_get_contents();
            ob_end_clean();
            
            // 大小验证
            $uriSize = strlen($img); // 得到图片大小
            $allowSize = 1024 * $config['maxSize'];
            if ($uriSize > $allowSize) {
                array_push($tmpNames, "error");
                continue;
            }
            // 创建保存位置
            $savePath = $config['savePath'];
            if (! file_exists($savePath)) {
                mkdir("$savePath", 0777);
            }
            // 写入文件
            $tmpName = $savePath . rand(1, 10000) . time() .
                 strrchr($imgUrl, '.');
            try {
                $fp2 = @fopen($tmpName, "a");
                fwrite($fp2, $img);
                fclose($fp2);
                array_push($tmpNames, basename($tmpName));
            } catch (Exception $e) {
                array_push($tmpNames, "error");
            }
        }
        /**
         * 返回数据格式 { 'url' : '新地址一ue_separate_ue新地址二ue_separate_ue新地址三',
         * 'srcUrl': '原始地址一ue_separate_ue原始地址二ue_separate_ue原始地址三'， 'tip' :
         * '状态提示' }
         */
        echo "{'url':'" . implode("ue_separate_ue", $tmpNames) .
             "','tip':'远程图片抓取成功！','srcUrl':'" . $uri . "'}";
    }
    
    // 渠道二维码中间LOGO
    public function channelLogo() {
        $type = I('get.type');
        if ($type == 'img') {
            $this->setAstrict($type);
        } else {
            $arr = array(
                'msg' => '-111',  // 通信是否成功
                'error' => "未知上传类型" . $type,  // 返回错误
                'imgurl' => $type); // 返回图片名
            
            echo json_encode($arr);
            exit();
        }
        import('ORG.Net.UploadFile');
        $upload = new UploadFile(); // 实例化上传类
        $upload->maxSize = $this->maxSize;
        $upload->allowExts = $this->allowExts;
        $upload->savePath = APP_PATH . '/Upload/channel/'; // 设置附件
        $upload->saveRule = time() . sprintf('%04s', mt_rand(0, 1000));
        if (! $upload->upload()) { // 上传错误提示错误信息
            $this->errormsg = $upload->getErrorMsg();
        } else { // 上传成功 获取上传文件信息
            $info = $upload->getUploadFileInfo();
            $this->imgurl = $info[0]['savename'];
        }
        $arr = array(
            'msg' => '0000',  // 通信是否成功
            'error' => $this->errormsg,  // 返回错误
            'imgurl' => $this->imgurl); // 返回图片名
        
        echo json_encode($arr);
        exit();
    }
    
    // 宁夏移动
    public function mobile_bg() {
        $type = $_REQUEST['type'];
        if ($type == 'img') {
            $this->setAstrict($type);
        } else {
            $arr = array(
                'msg' => '-111',  // 通信是否成功
                'error' => "未知上传类型" . $type,  // 返回错误
                'imgurl' => $type); // 返回图片名
            
            echo json_encode($arr);
            exit();
        }
        import('ORG.Net.UploadFile');
        $upload = new UploadFile(); // 实例化上传类
        $upload->maxSize = $this->maxSize;
        $upload->allowExts = $this->allowExts;
        $upload->savePath = APP_PATH . '/Upload/mobile/'; // 设置附件
        $upload->saveRule = time() . sprintf('%04s', mt_rand(0, 1000));
        if (! $upload->upload()) { // 上传错误提示错误信息
            $this->errormsg = $upload->getErrorMsg();
        } else { // 上传成功 获取上传文件信息
            $info = $upload->getUploadFileInfo();
            $this->imgurl = $info[0]['savename'];
        }
        $arr = array(
            'msg' => '0000',  // 通信是否成功
            'error' => $this->errormsg,  // 返回错误
            'imgurl' => $this->imgurl); // 返回图片名
        
        echo json_encode($arr);
        exit();
    }
    
    // wap背景图
    public function wap_bg() {
        $type = I('get.type');
        if ($type == 'img') {
            $this->setAstrict($type);
        } else {
            $arr = array(
                'msg' => '-111',  // 通信是否成功
                'error' => "未知上传类型" . $type,  // 返回错误
                'imgurl' => $type); // 返回图片名
            
            echo json_encode($arr);
            exit();
        }
        import('ORG.Net.UploadFile');
        $upload = new UploadFile(); // 实例化上传类
        $upload->maxSize = $this->maxSize;
        $upload->allowExts = $this->allowExts;
        $upload->savePath = APP_PATH . '/Upload/wapBg/'; // 设置附件
        $upload->saveRule = time() . sprintf('%04s', mt_rand(0, 1000));
        if (! $upload->upload()) { // 上传错误提示错误信息
            $this->errormsg = $upload->getErrorMsg();
        } else { // 上传成功 获取上传文件信息
            $info = $upload->getUploadFileInfo();
            $this->imgurl = $info[0]['savename'];
        }
        $arr = array(
            'msg' => '0000',  // 通信是否成功
            'error' => $this->errormsg,  // 返回错误
            'imgurl' => $this->imgurl); // 返回图片名
        
        echo json_encode($arr);
        exit();
    }
    
    // 列表背景图
    // wap背景图
    public function listImg() {
        $type = I('get.type');
        if ($type == 'img') {
            $this->setAstrict($type);
        } else {
            $arr = array(
                'msg' => '-111',  // 通信是否成功
                'error' => "未知上传类型" . $type,  // 返回错误
                'imgurl' => $type); // 返回图片名
            
            echo json_encode($arr);
            exit();
        }
        import('ORG.Net.UploadFile');
        $upload = new UploadFile(); // 实例化上传类
        $upload->maxSize = $this->maxSize;
        $upload->allowExts = $this->allowExts;
        $upload->savePath = APP_PATH . '/Upload/listImg/'; // 设置附件
        $upload->saveRule = time() . sprintf('%04s', mt_rand(0, 1000));
        if (! $upload->upload()) { // 上传错误提示错误信息
            $this->errormsg = $upload->getErrorMsg();
        } else { // 上传成功 获取上传文件信息
            $info = $upload->getUploadFileInfo();
            $this->imgurl = $info[0]['savename'];
        }
        $arr = array(
            'msg' => '0000',  // 通信是否成功
            'error' => $this->errormsg,  // 返回错误
            'imgurl' => $this->imgurl); // 返回图片名
        
        echo json_encode($arr);
        exit();
    }
}