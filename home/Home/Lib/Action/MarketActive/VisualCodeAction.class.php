<?php

class VisualCodeAction extends MarketBaseAction {

    public $content_pic = null;

    public function _initialize() {
        $this->needCheckUserPower = false;
        parent::_initialize();
        $this->uploadPath = './Home/Upload/VisualCode/'; // 设置附件上传目录
        $this->uploadTmpPath = './Home/Upload/img_tmp/'; // 设置附件上传目录
        $this->RemotePicUrl = C("CURRENT_DOMAIN") . "Home/Upload/VisualCode/";
        $TempLoginService = D('TempLogin', 'Service');
        $isTempUser = $TempLoginService->isTempUser($this->node_id);
        if($isTempUser == 1)
        {
            $userSessInfo = array('node_id'=>$this->nodeId,'token'=>'0000001');
            session('userSessInfo',$userSessInfo);
        }
        $this->assign('isTempUser',$isTempUser);
    }
    // 首页列表
    public function index() {
        $show = I('get.show', 0, 'string');
        $where = "node_id='" . $this->node_id . "'";
        $begin_date = I("begin_date");
        $end_date = I("end_date");
        $order_id = I("order_id");
        $code_id = I("code_id");
        $status = I('status');
        $this->assign('code_id', $code_id);
        $this->assign('status', $status);
        
        $seleIn = M('tvisual_qrcode');
        $cont = $seleIn->where($where)->count();
        if ($cont <= 0) {
            $queryData = '';
        } else {
            if ($begin_date != '' && $end_date != '' && $code_id == '') {
                if ($end_date < $begin_date) {
                    $this->error('查询开始日期大于结束日期！');
                }
                $where .= " and add_time >='" .
                     dateformat($begin_date, "Ymd000000") . "'";
                $where .= " and add_time <='" .
                     dateformat($end_date, "Ymd235959") . "'";
            }
            
            if ($order_id != "" && $code_id == '') {
                $where .= " and order_id='" . $order_id . "'";
            }
            
            if ($code_id != "") {
                $where .= " and id=" . $code_id;
            }
            
            if ($status == 2) {
                $where .= " and show_flag = '1'";
            } else {
                $where .= " and show_flag = '0'";
            }
            
            $totalNum = M('tvisual_qrcode')->where($where)->count();
            import("ORG.Util.Page"); // 导入分页类
            $Page = new Page($totalNum, 10);
            //$Page->setConfig('theme', '%totalRow% %header% %nowPage%/%totalPage% 页 %first% %upPage% %linkPage% %downPage% %end%');
            $pageShow = $Page->show();
            
            $queryData = M('tvisual_qrcode')->order("id desc")
                ->where($where)
                ->limit($Page->firstRow, $Page->listRows)
                ->select();
        }
        $this->assign('order_id', $order_id);
        $this->assign('code_id', $code_id);
        $this->assign('pageShow', $pageShow);
        $this->assign('queryData', $queryData);
        if (empty($queryData) && (($begin_date != '' && $end_date != '') ||
             $order_id != "" || $code_id != "" || $status)) {
            $this->assign('search', 'search');
        }
        $this->display();
    }
    
    // 选择码的类型
    public function selectType() {
        $this->display();
    }
    
    // 提交选择类型
    public function submitType() {
        $qr_type = I("qr_type"); // 1 网址二维码 2 微信二维码 0 文本二维码
        if ($qr_type == 1) {
            $this->display("url");
        } elseif ($qr_type == 2) {
            $this->display("wechat");
        } else {
            $this->display("text");
        }
    }
    
    // 上传背景图像
    public function setimg() {
        $channelid = I("channelid");
        $content_type = I("content_type");
        $content = I("content", '', 'trim'); // 输入框的内容,输入的长地址; 或微信二维码图片的内容（地址）
        $qrName = I("qrName"); // 二维码名称
        $remarkContent = I("remarkContent"); // 二维码备注内容
        if ($channelid != "") {
            $content_type = 1;
            $content = U('Label/Channel/index@' . $_SERVER['HTTP_HOST'],
                array(
                    'id' => $channelid));

        }
        
        $wechat_pic = I("wechat_pic");
        $this->assign("qrName", $qrName);
        $this->assign("remarkContent", $remarkContent);
        $this->assign("wechat_pic", $wechat_pic);
        $this->assign("channelid", $channelid);
        $this->assign("content", $content);
        $this->assign("content_type", $content_type);
        $this->display('design');
    }
    
    // 解析二维码图片的内容
    public function _appor_wx($url) { // 加下划线内部访问
        ini_set('magic_quotes_runtime', 0); // 配置php.ini文件的属性
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $isConnectSuccess = socket_connect($socket, C('APPRO_WX_URL'), 
            C('APPRO_WX_PORT'));
        if ($isConnectSuccess == false) {
            $returnResult = array(
                'error' => '连接不成功', 
                'msg' => '002');
            return $returnResult;
        }
        
        $file = $url; // 临时路径
        $file_buf = file_get_contents($file); // 打开文件，读取内容，转换成字符串
        $file_len = filesize($file);
        $pad_file_len = str_pad($file_len, 10, "0", STR_PAD_LEFT);
        socket_write($socket, $pad_file_len);
        socket_write($socket, $file_buf);
        $result = socket_read($socket, 1024);
        $status = substr($result, 0, 1);
        if ($status == '0') {
            $returnResult = array(
                'msg' => '0000', 
                'content' => substr($result, 1));
            return $returnResult;
        } else {
            $returnResult = array(
                'msg' => '0001', 
                'error' => '二维码解析失败');
            return $returnResult;
        }
    }
    
    // 微信二维码图片处理
    public function upLoad_wx_pic() {
        $type = I('get.type', null, 'trim');
        ini_set('memory_limit', '1024M');
        if ($type == 'img') {
            $this->maxSize = 3072 * 1024; // 设置附件上传大小
            $this->allowExts = array(
                "gif", 
                "jpg", 
                "jpeg", 
                "png"); // 设置附件上传类型
            
            import('ORG.Net.UploadFile');
            $upload = new UploadFile(); // 实例化上传类
            $upload->maxSize = $this->maxSize;
            $upload->thumb = true;
            $upload->thumbMaxWidth = 990;
            $upload->thumbMaxHeight = 640;
            $upload->allowExts = $this->allowExts;
            $upload->savePath = $this->uploadTmpPath . $this->nodeId . '/'; // 设置附件
            $upload->saveRule = time() . sprintf('%04s', mt_rand(0, 1000));
            
            if (! $upload->upload()) { // 上传错误提示错误信息
                $this->errormsg = $upload->getErrorMsg();
            } else { // 上传成功 获取上传文件信息
                $info = $upload->getUploadFileInfo();
                $this->imgurl = $info[0]['savename'];
            }
            
            $thumb_path = $this->uploadTmpPath . $this->nodeId . '/' . "thumb_" .
                 $this->imgurl;
            $result = $this->_appor_wx($thumb_path);
            
            if ($result['msg'] != '0000') {
                echo json_encode($result);
            } else {
                $arr = array(
                    'msg' => '0000',  // 通信是否成功
                    'error' => $this->errormsg,  // 返回错误
                    'imgurl' => $this->uploadTmpPath . $this->nodeId . '/' .
                         "thumb_" . $this->imgurl,  // 返回图片名
                        'pic_short_path' => $this->nodeId . '/' . "thumb_" .
                         $this->imgurl, 
                        'thumb_pic' => $thumb_path, 
                        'content' => $result['content']);
                echo json_encode($arr);
            }
        }
    }
    
    // 上传图片并裁减缩略图
    public function upload_bg_pic() {
        ini_set('memory_limit', '1024M');
        $this->maxSize = 3072 * 1024; // 设置附件上传大小
        $this->allowExts = array(
            "gif", 
            "jpg", 
            "jpeg", 
            "png"); 
        import('ORG.Net.UploadFile');
        $upload = new UploadFile(); // 实例化上传类
        $upload->maxSize = $this->maxSize;
        $upload->thumb = true;
        $upload->thumbMaxWidth = 990;
        $upload->thumbMaxHeight = 640;
        $upload->allowExts = $this->allowExts;
        $upload->savePath = $this->uploadTmpPath . $this->nodeId . '/'; // 设置附件
        $upload->saveRule = time() . sprintf('%04s', mt_rand(0, 1000));
        if (! $upload->upload()) { // 上传错误提示错误信息
            $this->errormsg = $upload->getErrorMsg();
        } else { // 上传成功 获取上传文件信息
            $info = $upload->getUploadFileInfo();
            $this->imgurl = $info[0]['savename'];
        }
        $thumb_path = $this->uploadTmpPath . $this->nodeId . '/' . "thumb_" .
             $this->imgurl; // 背景图片
        exit(
            json_encode(
                array(
                    'info' => array(
                        'msg' => '0000',  // 通信是否成功
                        'fileId' => $result, 
                        'imgName' => $this->imgurl, 
                        'imgUrl' => $this->_getImgUrl($info[0]['savename']), 
                        'error' => $this->errormsg,  // 返回错误
                        'imgurl' => $this->uploadTmpPath . $this->nodeId . '/' .
                             "thumb_" . $this->imgurl,  // 返回图片名
                            'pic_short_path' => $this->nodeId . '/' . "thumb_" .
                             $this->imgurl, 
                            'thumb_pic' => $thumb_path), 
                    'status' => 0)));
    }

    private function _getImgUrl($imgname) {
        return $this->uploadPath . $imgname;
    }
    
    // 更换背景图片
    public function change_bg_pic() {
        ini_set('memory_limit', '1024M');
        
        $this->maxSize = 3072 * 1024; // 设置附件上传大小
        $this->allowExts = array(
            "gif", 
            "jpg", 
            "jpeg", 
            "png"); 
        import('ORG.Net.UploadFile');
        $upload = new UploadFile(); // 实例化上传类
        $upload->maxSize = $this->maxSize;
        $upload->thumb = true;
        $upload->thumbMaxWidth = 990;
        $upload->thumbMaxHeight = 640;
        $upload->allowExts = $this->allowExts;
        $upload->savePath = $this->uploadTmpPath . $this->nodeId . '/'; // 设置附件
        $upload->saveRule = time() . sprintf('%04s', mt_rand(0, 1000));
        if (! $upload->upload()) { // 上传错误提示错误信息
            $this->errormsg = $upload->getErrorMsg();
        } else { // 上传成功 获取上传文件信息
            $info = $upload->getUploadFileInfo();
            $this->imgurl = $info[0]['savename'];
        }
        
        $thumb_path = $this->uploadTmpPath . $this->nodeId . '/' . "thumb_" .
             $this->imgurl; 
        list ($width, $height, $type, $attr) = getimagesize($thumb_path);
        exit(
            json_encode(
                array(
                    'info' => array(
                        'msg' => '0000',  // 通信是否成功
                        'fileId' => $result, 
                        'imgName' => $this->imgurl, 
                        'imgUrl' => $this->_getImgUrl($info[0]['savename']), 
                        'error' => $this->errormsg,  // 返回错误
                        'imgurl' => $this->uploadTmpPath . $this->nodeId . '/' .
                             "thumb_" . $this->imgurl,  // 返回图片名
                            'pic_short_path' => $this->nodeId . '/' . "thumb_" .
                             $this->imgurl, 
                            'thumb_pic' => $thumb_path, 
                            'width' => $width, 
                            'height' => $height), 
                    'status' => 0)));
    }
    
    // 制作qr
    public function design() {
        $content = I("content", '', 'trim');
        $channelid = I("channelid");
        $content_pic = I("content_pic");
        $content_pic = get_upload_url($content_pic);
        $short_pic = I("short_pic");
        $wechat_pic = I("wechat_pic");
        $qrName = I("qrName"); // 二维码名称
        $remarkContent = I("remarkContent"); // 二维码备注内容
        $content_type = I("content_type"); // 1网址二维码 2 微信二维码 0 文本二维码
        $this->assign("wechat_pic", $wechat_pic);
        $this->assign("content", $content);
        $this->assign("channelid", $channelid);
        $this->assign("short_pic", $short_pic);
        $this->assign("content_pic", $content_pic);
        $this->assign("content_type", $content_type);
        $this->assign("qrName", $qrName);
        $this->assign("remarkContent", $remarkContent);
        $this->display();
    }
    
    // 提交qr预览
    public function submit_design() {
        $content = I("content", '', 'trim');
        $channelid = I("channelid");
        $qr_type = I("content_type");
        $wechat_pic = I("wechat_pic");
        $content_pic = I("content_pic");
        $short_pic = I("short_pic");
        $qr_size = I("qr_size");
        $qr_x = I("qr_x");
        $qr_y = I("qr_y");
        $qr_rotation = I("qr_rotation");
        $cells_type = I("cells_type");
        $markers_type = I("markers_type");
        $gen_type = I("gen_type");
        $effects = I("effects");
        $qrName = I("qrName"); // 二维码名称
        $remarkContent = I("remarkContent"); 
        if ($short_pic != "") {
            $short_pic = str_replace('..', '', $short_pic);
            // 用新的图片上传工具 by tr
            if (! is_dir(APP_PATH . '/Upload/VisualCode')) {
                mkdir(APP_PATH . '/Upload/VisualCode', 0777);
            }
            if (! is_dir(APP_PATH . '/Upload/VisualCode/' . $this->node_id)) {
                mkdir(APP_PATH . '/Upload/VisualCode/' . $this->node_id, 0777);
            }
            
            if (preg_match('/^http:\/\//', $content_pic) &&
                 preg_match('/Home/', $content_pic)) {
                $old_image_url = C('UPLOAD') . $short_pic;
            } else {
                $old_image_url = C('UPLOAD') . $short_pic;
            }
            
            $new_image_url = APP_PATH . '/Upload/VisualCode/' . $short_pic;
            $flag = copy($old_image_url, $new_image_url);
            if (! $flag) {
                $new_image_url = $old_image_url;
            }
        } else {
            $arr = array(
                'status' => '0',  // 通信是否成功
                'error' => "制码失败，背景图片不存在");
            echo json_encode($arr);
            exit();
        }
        
        list ($width, $height, $type, $attr) = getimagesize($new_image_url);
        $serv = D('VisualCode', 'Service');
        $outname = time() . '.png';
        $outfile_name = realpath($this->uploadPath . $this->node_id) . '/' .
             $outname;
        
        $c_type = $qr_type == 0 ? 1 : 0; // 0为文本，1为网址，2为微信
        $option = array(
            'content' => $content, 
            'bg_file' => $new_image_url, 
            'out_file' => $outfile_name, 
            'qr_x' => $qr_x, 
            'qr_y' => $qr_y, 
            'qr_size' => $qr_size, 
            'qr_rotation' => $qr_rotation, 
            'cells_type' => $cells_type, 
            'markers_type' => $markers_type, 
            'effects' => $effects);
        
        try {
            $serv->createQrImg($c_type, false, $option);
        } catch (Exception $e) {
            $arr = array(
                'status' => '0',  // 通信是否成功
                'error' => "制码失败[02]" . $e->getMessage());
            echo json_encode($arr);
            exit();
        }
        
        $arr = array(
            'status' => '1',  // 通信是否成功
            'imgurl' => C('TMPL_PARSE_STRING.__URL_UPLOAD__') . '/VisualCode/' .
                 $this->node_id . '/' . $outname,  // 返回图片名
                'code_id' => '');
        echo json_encode($arr);
        exit();
    }

    public function example() {
        $this->display();
    }

    public function done() {
        $requestInfo = I('request.');
        if(get_val($requestInfo,'istmp') == 1)// 游客用户
        {
            $cookie = cookie('prexuanma');
            if(empty($cookie))
                $this->error('您已超时，请重新体验');
            $requestInfo = json_decode(S($cookie),true);
            if(empty($requestInfo))
                $this->error('您已超时，请重新体验');
            cookie('prexuanma',null);
            S($cookie,null,5);
        }
        // 下一步保存数据，content真实内容
        $content      = get_val($requestInfo,'content','');
        $content_type = get_val($requestInfo,'content_type','');
        $wechat_pic   = get_val($requestInfo,'wechat_pic','');
        $channelid    = get_val($requestInfo,'channelid','');
        $content_pic  = get_val($requestInfo,'content_pic','');
        $short_pic    = get_val($requestInfo,'short_pic','');
        $qr_size      = get_val($requestInfo,'qr_size','');
        $qr_x         = get_val($requestInfo,'qr_x','');
        $qr_y         = get_val($requestInfo,'qr_y','');
        $qr_rotation  = get_val($requestInfo,'qr_rotation','');
        $cells_type   = get_val($requestInfo,'cells_type','');
        $markers_type = get_val($requestInfo,'markers_type','');
        $gen_type     = get_val($requestInfo,'gen_type','');
        $effects      = get_val($requestInfo,'effects','');
        $qrName       = get_val($requestInfo,'qrName',''); // 二维码名称
        if($channelid != ""){
            $model = M('tmarketing_info');
            $qrName = $model->join('tbatch_channel T on T.batch_id=tmarketing_info.id')
                    ->where('T.id='.$channelid)
                    ->getField('tmarketing_info.name');
        }
        $remarkContent = get_val($requestInfo,'remarkContent',''); // 二维码备注内容
        $new_image_url = APP_PATH . 'Upload/VisualCode/' . $short_pic; // 背景图片
        list ($width, $height, $type, $attr) = getimagesize($new_image_url);
        $serv = D('VisualCode', 'Service');
        $reqArr = array(
            'image'               => $new_image_url,  // 背景图片
            'content'             => $content,  // 内容demo内容
            'qr_code'             => $this->RemotePicUrl . $wechat_pic, 
            'qr_size'             => $qr_size,  // qr码高度/宽度
            'qr_x'                => $qr_x,  // QR码的左上角X坐标
            'qr_y'                => $qr_y,  // QR码的左上角y坐标
            'qr_rotation'         => $qr_rotation,  // QR码角度：0/90/180/270
            'output_image_width'  => $width, 
            'output_image_height' => $height, 
            'cells_type'          => $cells_type,  // 1方型 2圆型
            'markers_type'        => $markers_type,  // 1方型 2圆型
            'gen_type'            => $gen_type, 
            'effects'             => get_val($requestInfo,'effects',''));
        
        $result_pic = get_val($requestInfo,'result_pic','');
        $barcodeArray = array(
            'node_id'        => $this->node_id, 
            'preview_image'  => $result_pic, 
            'barcode_img'    => '', 
            'barcode_price'  => 100, 
            'status'         => 8, 
            'image_url'      => $result_pic, 
            'channel_id'     => $channelid, 
            'code_type'      => $content_type, 
            'qr_name'        => $qrName, 
            'remark_content' => $remarkContent);
        $res = $serv->saveVisualCode($reqArr, $barcodeArray);
        if (! $res) {
            $this->error("保存数据库异常！");
        }
        redirect(
            "index.php?g=MarketActive&m=VisualCode&a=finish_done&code_id=" . $res .
                 "&result_pic=" . $result_pic);
        exit();
    }
    public function cookiedone(){
        $requestInfo = I('POST.');
        if(empty($requestInfo))
            $this->error('数据有误，请重新提交');
        $cookieStr = date('YmdHis').mt_rand(10000,99999);
        cookie('prexuanma',$cookieStr,5*60);
        S($cookieStr,json_encode($requestInfo),5*60);
        if(C('NEW_VISUALCODE_VISITER.node_id') != $this->nodeId)
            $islogin = 1;
        else
            $islogin = 0;
        $this->success('提交成功','',['islogin'=>$islogin]);
    }
    public function finish_done() {
        $result_pic = I("result_pic");
        $code_id = I("code_id");
        if ($result_pic == "" || $code_id == "") {
            $this->error("参数错误！");
        }
        $this->assign("result_pic", $result_pic);
        $this->assign("code_id", $code_id);
        $this->display();
    }
    
    // 本地下载码
    public function down_local_code() {
        $code_id = I("code_id");
        if (empty($code_id)) {
            $this->error("参数错误！");
        }
        $visualCodeModel = M('TvisualQrcode');
        $Codeinfo = $visualCodeModel->where(
            array(
                'node_id' => $this->node_id, 
                'id' => $code_id))->find();
        if (empty($Codeinfo)) {
            $this->error("没找到该炫码！");
        }
        
        // 兼容之前付费版本
        if (($Codeinfo['status'] != 5 && $Codeinfo['status'] != 8) ||
             $Codeinfo['barcode_img'] == '') {
            $qrCodeImageContentArray = json_decode($Codeinfo['req_array'], true);
            $qrCodeImageContentArray['bg_file'] = $Codeinfo['code_img'];
            
            $outname = time() . '.png';
            $outfile_name = realpath($this->uploadPath . $this->node_id) . '/' .
                 $outname;
            
            $qrCodeImageContentArray['out_file'] = $outfile_name;
            
            $serv = D('VisualCode', 'Service');
            $makeQrImageResult = $serv->createQrImg($Codeinfo['code_type'], 
                false, $qrCodeImageContentArray);
            
            if ($makeQrImageResult) {
                $visualCodeModel->where(
                    array(
                        'node_id' => $this->node_id, 
                        'id' => $code_id))->save(
                    array(
                        'status' => 8, 
                        'barcode_img' => $outfile_name, 
                        'barcode_url' => $outfile_name));
                echo file_get_contents($outname);
                exit();
            }
        }
        
        if ($Codeinfo['barcode_img'] != "" || $Codeinfo['barcode_url'] != "") {
            $filename = iconv('utf-8', 'gbk', 'down.png');
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . $filename);
            header('Content-Type: image/png');
            if ($Codeinfo['barcode_img'] != "") {
                if (file_exists($Codeinfo['barcode_img']))
                    echo file_get_contents($Codeinfo['barcode_img']);
                else if (file_exists(
                    $this->uploadPath . $Codeinfo['barcode_img']))
                    echo file_get_contents(
                        $this->uploadPath . $Codeinfo['barcode_img']);
                else
                    $this->error("显示图片异常！");
            } else {
                if (file_exists($Codeinfo['barcode_url']))
                    echo file_get_contents($Codeinfo['barcode_url']);
                else
                    $this->error("显示图片异常！");
            }
        } else {
            $this->error("本地炫码图片不存在");
        }
    }
 
    public function VisualcodeDel() {
        $id = I('post.id');
        if ($id == '')
            $this->error('参数错误！');
        $map = array(
            'node_id' => $this->node_id, 
            'id' => $id);
        $flag = M('tvisual_qrcode')->where($map)->save(
            array(
                'show_flag' => 1));
        $result['error'] = 0;
        $result['url'] = U('index');
        $this->ajaxReturn($result);
    }

    public function addBasicInfoForTempUser()
    {
        $cookieInfo = cookie('newVisualCodeVisiter'.'Node');
        if (!$cookieInfo) {
            $this->error('生成临时用户失败，用户可能未开启cookie');
        }
        redirect(U('MarketActive/VisualCode/selectType'));
    }
}
