<?php

class IndexAction extends BaseAction {

    public $content_pic = null;

    public function _initialize() {
        parent::_initialize();
        $this->uploadPath = './Home/Upload/VisualCode/'; // 设置附件上传目录
        $this->uploadTmpPath = './Home/Upload/img_tmp/'; // 设置附件上传目录
        $this->RemotePicUrl = C("CURRENT_DOMAIN") . "Home/Upload/VisualCode/";
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
            $Page->setConfig('theme', 
                '%totalRow% %header% %nowPage%/%totalPage% 页 %first% %upPage% %linkPage% %downPage% %end%');
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
        
        // 如果是URL通过接口获取短连接
        // if($content_type==1){
        // // $reqServ = D('RemoteRequest','Service'); //实例接口
        // // $rs = $reqServ->getSinaShortUrl($content);
        // // if(!$rs||$rs['Status']['StatusCode']!='0000'){
        // // $this->error("获取网址短连接失败！，失败原因：".$rs['Status']['StatusText']);
        // // }else{
        // // $content=$rs['ShortUrl'];
        // // }
        // }
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
        // $type=I('get.type', null, 'trim');
        ini_set('memory_limit', '1024M');
        // if($type == 'img')
        // {
        // if($type == "img")
        // {
        $this->maxSize = 3072 * 1024; // 设置附件上传大小
        $this->allowExts = array(
            "gif", 
            "jpg", 
            "jpeg", 
            "png"); // 设置附件上传类型
                    // }
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
        // $arr = array(
        // 'msg'=>'0000',//通信是否成功
        // 'error'=>$this->errormsg, //返回错误
        // 'imgurl'=>$this->uploadTmpPath.$this->nodeId.'/'."thumb_".$this->imgurl,//返回图片名
        // 'pic_short_path'=>$this->nodeId.'/'."thumb_".$this->imgurl,
        // 'thumb_pic'=>$thumb_path
        // );
        // echo json_encode($arr);
        // exit;
        // }
    }

    private function _getImgUrl($imgname) {
        return $this->uploadPath . $imgname;
    }
    
    // 更换背景图片
    public function change_bg_pic() {
        // $type=I('get.type', null, 'trim');
        ini_set('memory_limit', '1024M');
        // if($type == 'img')
        // {
        // if($type == "img")
        // {
        $this->maxSize = 3072 * 1024; // 设置附件上传大小
        $this->allowExts = array(
            "gif", 
            "jpg", 
            "jpeg", 
            "png"); // 设置附件上传类型
                    // }
        
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
                           // $arr
                           // =
                           // array(
                           // 'msg'=>'0000',//通信是否成功
                           // 'error'=>$this->errormsg,
                           // //返回错误
                           // 'imgurl'=>$this->uploadTmpPath.$this->nodeId.'/'."thumb_".$this->imgurl,//返回图片名
                           // 'pic_short_path'=>$this->nodeId.'/'."thumb_".$this->imgurl,
                           // 'thumb_pic'=>$thumb_path
                           // );
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
        // $arr['width'] = $width;
        // $arr['height'] = $height;
        // echo json_encode($arr);
        // exit();
        // }
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
        $remarkContent = I("remarkContent"); // 二维码备注内容
                                             // 0 网址二维码 2 微信二维码 `1 文本二维码
                                             // if($qr_type==2){
                                             // $content="";
                                             // }else{
                                             // $wechat_pic=null;
                                             // }
                                             // 将背景文件从上传临时目录拷贝至炫码上传目录
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
        
        // $example_content=C("CURRENT_DOMAIN")."index.php?g=Label&m=Qrshow&a=index";
        // 短链地址为：http://www.wangcaio2o.com/index.php?g=Label&m=Qrshow&a=index
        // $example_content='http://t.ym06.cn/tp2jktjojb';
        
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
        // 下一步保存数据，content真实内容
        $content = I("content", '', 'trim');
        $content_type = I("content_type");
        $wechat_pic = I("wechat_pic");
        $channelid = I("channelid");
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
        $remarkContent = I("remarkContent"); // 二维码备注内容
        $new_image_url = APP_PATH . 'Upload/VisualCode/' . $short_pic; // 背景图片
        list ($width, $height, $type, $attr) = getimagesize($new_image_url);
        $serv = D('VisualCode', 'Service');
        $reqArr = array(
            'image' => $new_image_url,  // 背景图片
            'content' => $content,  // 内容demo内容
            'qr_code' => $this->RemotePicUrl . $wechat_pic, 
            'qr_size' => $qr_size,  // qr码高度/宽度
            'qr_x' => $qr_x,  // QR码的左上角X坐标
            'qr_y' => $qr_y,  // QR码的左上角y坐标
            'qr_rotation' => $qr_rotation,  // QR码角度：0/90/180/270
            'output_image_width' => $width, 
            'output_image_height' => $height, 
            'cells_type' => $cells_type,  // 1方型 2圆型
            'markers_type' => $markers_type,  // 1方型 2圆型
            'gen_type' => $gen_type, 
            'effects' => I("effects"));
        
        $result_pic = I("result_pic");
        $barcodeArray = array(
            'node_id' => $this->node_id, 
            'preview_image' => $result_pic, 
            'barcode_img' => '', 
            'barcode_price' => 100, 
            'status' => 8, 
            'image_url' => $result_pic, 
            'channel_id' => $channelid, 
            'code_type' => $content_type, 
            'qrName' => $qrName, 
            'remarkContent' => $remarkContent);
        $res = $serv->saveVisualCode($reqArr, $barcodeArray);
        if (! $res) {
            $this->error("保存数据库异常！");
        }
        // $nodeInfo =
        // M('tnode_info')->field("node_name")->where("node_id='{$this->nodeId}'")->find();
        // 发送邮件
        // $content =
        // "邮件内容“新增炫码订单！炫码编号".$res."，商户名：".$nodeInfo['node_name']."。请及时在旺财后台的炫码订单下，进行处理~";
        // $ps = array(
        // "subject"=>"旺财炫码订单",
        // "content"=>$content,
        // "email"=>"7005@imageco.com.cn",
        // );
        // $resp = send_mail($ps);
        // $makeQri = $serv->getImagecoVisualCodeByID($res);
        redirect(
            "index.php?g=VisualCode&m=Index&a=finish_done&code_id=" . $res .
                 "&result_pic=" . $result_pic);
        exit();
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
    
    // 支付炫码
    // public function VisualcodePay() {
    // $id = I("id");
    // //判断订单
    // if (empty($id)) {
    // $this->error("支付失败，参数错误！");
    // }
    // //查询ID数据
    // $Codeinfo = M('tvisual_qrcode')->where(array('node_id' => $this->node_id,
    // 'id' => $id))->find();
    // if (empty($Codeinfo)) {
    // $this->error("支付失败，订单不存在！");
    // }
    // if ($Codeinfo['barcode_price'] == "" || $Codeinfo['barcode_price'] < 0) {
    // $this->error("支付失败，请联系客户经理确定价格！");
    // }
    //
    // //判断订单是否达到支付状态
    // if ($Codeinfo['status'] == 4 || $Codeinfo['status'] == 0) {
    // //取机构合约
    // $node_info = M('tnode_info')->where(array('node_id' =>
    // $this->node_id))->find();
    // if ($node_info['contract_no'] == "") {
    // $this->error("支付失败，合约号不能为空！");
    // }
    // $TransactionID = date("YmdHis") . mt_rand(100000, 999999); //请求单号
    // $req_array = array(
    // 'XCodeReq' => array(
    // 'TransactionID' => $TransactionID,
    // 'SystemID' => C('YZ_SYSTEM_ID'),
    // 'NodeID' => $this->nodeId,
    // 'ContractID' => $node_info['contract_no'],
    // 'GoodId' => $Codeinfo['id'],
    // 'RuleType' => 3010,
    // 'WcType' => 'code',
    // 'Price' => $Codeinfo['barcode_price']
    // )
    // );
    // Log::write("炫码支付，炫码ID:" . $id . ",请求支付参数==" . print_r($req_array, true));
    // $RemoteRequest = D('RemoteRequest', 'Service');
    // $result = $RemoteRequest->requestYzServ($req_array);
    // //$result['Status']['StatusCode']='0000';
    // Log::write("ID" . $id . ",炫码支付返回结果，" . print_r($result, true));
    // if ($result['Status']['StatusCode'] != '0000') {
    // //{:C('YZ_RECHARGE_URL')}&node_id={$userInfo['node_id']}&name={$userInfo.user_name}&token={$token}
    // //如果余额不足，跳转到充值页面
    // if ($result['Status']['StatusCode'] == '0015') {
    // $userService = D('UserSess', 'Service');
    // $userInfo = $userService->getUserInfo();
    // $payurl = C('YZ_RECHARGE_URL') . "&node_id=" . $this->nodeId . "&name=" .
    // $userInfo['name'] . "&token=" . $this->token;
    // $this->error("支付失败，余额不足，立即充值", array("立即充值" => $payurl));
    // } else {
    // $this->error("支付失败，错误码:" . $result['Status']['StatusCode'] . ",错误原因:" .
    // $result['Status']['StatusText']);
    // }
    // } else {
    // //支付成功去生成二维码
    // // M()->startTrans();
    // //修改支付状态
    // $data = array(
    // 'status' => '5',
    // 'pay_trans_id' => $TransactionID,
    // 'pay_time' => date("YmdHis"),
    // );
    // $result = M('tvisual_qrcode')->where("id='" . $id . "'")->save($data);
    // $serv = D('VisualCode', 'Service');
    // $res = $serv->getImagecoVisualCodeByID($id);
    // Log::write("ID" . $id . ",支付后请求制码返回结果，" . print_r($res, true));
    // //判断生成结果,如果生成失败则调用冲正接口并改回状态
    // if ($res['status'] == 1) {
    // $this->success("支付成功", 'index.php?g=VisualCode&m=Index&a=index');
    // } else {
    // //冲正支付交易
    // $NewTransactionID = date("YmdHis") . mt_rand(100000, 999999); //请求单号
    // $req_array = array(
    // 'XCodeReverseReq' => array(
    // 'TransactionID' => $NewTransactionID,
    // 'OriTransactionID' => $TransactionID,
    // 'SystemID' => C('YZ_SYSTEM_ID')
    // )
    // );
    //
    // Log::write("炫码支付冲正，炫码ID:" . $id . ",请求参数==" . print_r($req_array, true));
    // $RemoteRequest = D('RemoteRequest', 'Service');
    // $resd = $RemoteRequest->requestYzServ($req_array);
    // Log::write("炫码冲正结果，ID" . $id . ",冲正返回结果，" . print_r($resd, true));
    //
    // //记录冲正信息
    // $resdata = array(
    // 'new_transid' => $NewTransactionID,
    // 'ori_transid' => $TransactionID,
    // 'order_id' => '',
    // 'visual_id' => $id,
    // 'price' => $Codeinfo['barcode_price'],
    // 'status' => $resd['Status']['StatusCode'],
    // 'status_text' => $resd['Status']['StatusText'],
    // 'add_time' => date('YmdHis')
    // );
    // $resback = M('tvisual_reserve')->add($resdata);
    //
    // //如果冲正成功，修改订单状态待支付
    // if ($resd['Status']['StatusCode'] == '0000') {
    // //修改支付状态
    // $data = array(
    // 'status' => '4',
    // 'pay_trans_id' => '',
    // 'pay_time' => '',
    // );
    // $result = M('tvisual_qrcode')->where("id='" . $id . "'")->save($data);
    // }
    // $this->error("支付失败！", "index.php?g=VisualCode&m=Index&a=index");
    // }
    // }
    // } else {
    // if ($Codeinfo['status'] == 5) {
    // $this->error("订单已支付！");
    // } else {
    // $this->error("支付失败，订单未达到待支付状态！",
    // "index.php?g=VisualCode&m=Index&a=index");
    // }
    // }
    // }
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
}
