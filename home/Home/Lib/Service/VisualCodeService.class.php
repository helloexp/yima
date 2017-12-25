<?php

class VisualCodeService {

    /**
     * 创建二维码 $type 0 url; 1 文本内容
     */
    public function createQrImg($type, $testflag = true, $opt) {
        $cnt = $opt['content'];
        $e_cnt = 'http://t.cn/RP88HUl';
        $v = 2;
        if ($type == 0) {
            $cnt = htmlspecialchars_decode($cnt);
            $wx_flag = strtolower(substr($cnt, 0, 21)) == 'http://weixin.qq.com/';
            // 测试
            if ($testflag) {
                $cnt = $wx_flag ? 'http://t.cn/RP88HUl                        ' : 'http://t.cn/RP88HUl';
            }  // 正式
else {
                if (! $wx_flag) {
                    $cnt = create_sina_short_url($cnt);
                    if ($cnt == '' || $cnt === false)
                        throw new Exception('短链创建失败！');
                }
            }
            if ($wx_flag)
                $v = 2;
        } else {
            $len = strlen($cnt);
            if ($len > 100) {
                $v = 1;
                if ($testflag)
                    $cnt = str_pad($e_cnt, $len);
            } else {
                if ($testflag) {
                    $cnt = $len > strlen($e_cnt) ? str_pad($e_cnt, $len) : $e_cnt;
                }
            }
        }
        
        Log::write(print_r($opt, true), 'VISUAL CODE');
        Log::write(print_r($type, true), 'VISUAL CODE type');
        Log::write(print_r($testflag, true), 'VISUAL CODE testflag');
        Log::write('制码内容：' . $cnt . ' V' . $v, 'VISUAL CODE');
        
        if ($v == 1)
            import("@.ORG.Util.qrimg.qrimg");
        else
            import("@.ORG.Util.qrimg_v2.qrimg");
        
        try {
            createQrImg($cnt, $opt['bg_file'], $opt['out_file'], $opt['qr_x'], 
                $opt['qr_y'], $opt['qr_size'], $opt['qr_size'], 
                $opt['qr_rotation'], $opt['cells_type'] - 1, 
                $opt['markers_type'] - 1, $opt['effects']);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        return true;
    }

    public function saveVisualCode($reqArray, $barcodeArray) {
        $visual_qrcode = array();
        $visual_qrcode['code_img'] = $reqArray['image'];
        $visual_qrcode['content'] = htmlspecialchars_decode(
            $reqArray['content']);
        $visual_qrcode['node_id'] = $barcodeArray['node_id'];
        $visual_qrcode['preview_img'] = $barcodeArray['preview_image'];
        
        if ($barcodeArray['status'] == 8) {
            if (preg_match('/^http:\/\//', $barcodeArray['image_url']) &&
                 preg_match('/Home/', $barcodeArray['image_url'])) {
                $imagePathArray = explode('Home', $barcodeArray['image_url']);
                $visual_qrcode['barcode_img'] = './Home' . $imagePathArray['1'];
            } else {
                $visual_qrcode['barcode_img'] = $barcodeArray['barcode_img'];
            }
        } else {
            $visual_qrcode['barcode_img'] = $barcodeArray['barcode_img'];
        }
        
        $visual_qrcode['barcode_url'] = $barcodeArray['image_url'];
        $visual_qrcode['channel_id'] = $barcodeArray['channel_id'];
        $visual_qrcode['code_type'] = $barcodeArray['code_type'];
        $visual_qrcode['barcode_price'] = $barcodeArray['barcode_price'];
        $visual_qrcode['qr_name'] = $barcodeArray['qr_name'];
        $visual_qrcode['remark_content'] = $barcodeArray['remark_content'];
        $visual_qrcode['add_time'] = date('YmdHis');
        $visual_qrcode['req_array'] = json_encode($reqArray);
        if ($barcodeArray['status'] != "") {
            $visual_qrcode['status'] = $barcodeArray['status'];
        }
        $visual_qrcode['order_id'] = date("Ymd") . rand(100000, 999999);
        
        if (! $barcodeArray['id'])
            $rs = M('tvisual_qrcode')->add($visual_qrcode);
        else
            $rs = M('tvisual_qrcode')->where("id = " . $barcodeArray['id'])->save(
                $visual_qrcode);
        if (! $rs) {
            Log::write("insert tvisual_qrcode error!");
        }
        return $rs;
    }

    public function getVisualCodeByID($id) {
        $where = "id = " . $id;
        $rs = M('tvisual_qrcode')->where($where)->find();
        if (! $rs) {
            return $this->returnError("制码失败", 
                array(
                    'sys_error' => "未找到该id对应的数据"));
        }
        $reqArray = json_decode($rs['req_array'], true);
        $result = $this->generateVisualCode($reqArray);
        
        if (! $result || $result['response'] != '1') // 应答失败
{
            return $this->returnError("制码失败", 
                array(
                    'sys_error' => $result['error']));
        }
        
        $barcodeArray = array();
        $barcodeArray['image_url'] = $result['image_url'];
        $barcodeArray['channel_id'] = $result['channel_id'];
        $barcodeArray['code_type'] = $rs['code_type'];
        $barcodeArray['id'] = $id;
        
        // 拷贝图片
        if ($result['image_url'] != "") {
            $pic_content = file_get_contents($result['image_url']);
            Log::write("image_url==" . $result['image_url']);
            $im = imagecreatefromstring($pic_content);
            if ($im !== false) {
                // header('Content-Type: image/png');
                $filename = $rs['node_id'] . "/" . time() .
                     sprintf('%04s', mt_rand(0, 1000)) . ".png";
                Log::write("file==" . $filename);
                imagepng($im, "./Home/Upload/VisualCode/" . $filename);
            }
        }
        
        $barcodeArray['barcode_img'] = $filename;
        $barcodeArray['image_url'] = $result['image_url'];
        
        $rs = $this->saveVisualCode($reqArray, $barcodeArray);
        
        return $this->returnSuccess('成功', 
            array(
                'image' => $result['image'], 
                'image_url' => $result['image_url'], 
                'code_id' => $rs));
    }

    public function getImagecoVisualCodeByID($id) {
        $where = "id = " . $id;
        $rs = M('tvisual_qrcode')->where($where)->find();
        
        if (! $rs) {
            return $this->returnError("制码失败", 
                array(
                    'sys_error' => "未找到该id对应的数据"));
        }
        $reqArray = json_decode($rs['req_array'], true);
        
        $filename = time() . sprintf('%04s', mt_rand(0, 1000)) . ".png";
        
        $outfile_name = dirname(realpath($rs['barcode_url'])) . "/" . $filename;
        
        list ($width, $height, $type, $attr) = getimagesize(
            realpath($rs['barcode_url']));
        
        // import("@.ORG.Util.qrimg.qrimg");
        
        try {
            $option = array(
                'content' => $rs['content'], 
                'bg_file' => realpath($rs['code_img']), 
                'out_file' => $outfile_name, 
                'qr_x' => $reqArray['qr_x'], 
                'qr_y' => $reqArray['qr_y'], 
                'qr_size' => $reqArray['qr_size'], 
                'qr_size' => $reqArray['qr_size'], 
                'qr_rotation' => $reqArray['qr_rotation'], 
                'cells_type' => $reqArray['cells_type'], 
                'markers_type' => $reqArray['markers_type'], 
                'effects' => $reqArray['effects']);
            $ctype = 0;
            
            if ($rs['code_type'] == '0')
                $ctype = 1;
            $this->createQrImg($ctype, false, $option);
            
            $result = array(
                'image_url' => $outfile_name, 
                'response' => '1');
        } catch (Exception $e) {
            $result = null;
        }
        
        if (! $result) {
            return $this->returnError("炫码制作失败！");
        }
        
        $barcodeArray = array();
        $barcodeArray['image_url'] = $result['image_url'];
        $barcodeArray['channel_id'] = $result['channel_id'];
        $barcodeArray['code_type'] = $rs['code_type'];
        $barcodeArray['id'] = $id;
        
        $barcodeArray['barcode_img'] = dirname($rs['code_img']) . "/" . $filename;
        $barcodeArray['image_url'] = dirname($rs['code_img']) . "/" . $filename;
        
        $rs = $this->saveVisualCode($reqArray, $barcodeArray);
        
        return $this->returnSuccess('成功', 
            array(
                'image' => $result['image'], 
                'image_url' => $result['image_url'], 
                'code_id' => $rs));
    }
    
    // http get
    public function httpGet($url, $data = null, $error = null, $opt = array()) {
        $opt = array_merge(
            array(
                'TIMEOUT' => 30, 
                'METHOD' => 'GET'), $opt);
        // 创建post请求参数
        import('@.ORG.Net.FineCurl') or die('[@.ORG.Net.FineCurl]导入包失败');
        $socket = new FineCurl();
        $socket->setopt('URL', $url);
        $socket->setopt('TIMEOUT', $opt['TIMEOUT']);
        $socket->setopt('HEADER_TYPE', $opt['METHOD']);
        if (is_array($data)) {
            $data = http_build_query($data);
        }
        Log::write('请求：' . $url . '参数：' . $data, 'REMOTE');
        $result = $socket->send($data);
        $error = $socket->error();
        // 记录日志
        if ($error) {
            Log::write($error, 'ERROR');
        }
        return $result;
    }
    
    // 返回失败信息
    public function returnError($message, $opt = array()) {
        return array_merge(
            array(
                'info' => $message, 
                'status' => 0), $opt);
    }
    // 返回成功信息
    public function returnSuccess($message, $opt = array()) {
        return array_merge(
            array(
                'info' => $message, 
                'status' => 1), $opt);
    }

    /**
     * 制作QR码图片 $content 二维码内容 $bgImgFile 背景图片 $outFileName 输出文件名 $dstX, $dstY
     * 二维码图片的左上角的坐标 $dstW, $dstH 二维码图片缩放大小，不能超过背景图大小 $rotation 自定义旋转角度
     * $pixel_type 二维码点阵图片类型 0-方块 1-圆点 2-星标 $mask_type 二维码角标图片类型 0-方块 1-弧形方块
     * $bgimg_deal_type 背景图片处理类型 0-不处理 1-素描 2-黑白 3-模拟油画 4-怀旧 5-毛玻璃 6-曝光 7-浮雕
     * 8-运动模糊 9-漩涡 10-内曝 11-水波纹 notice: 需要定义 QRIMG_TMP_FILE_DIR作为二维码图片生成的临时目录
     * ps: 引入的phpqrcode_modify.php 是在phpqrcode.php上经过了小幅修改的 image目录存放需要的图片素材
     * example: //$content = "http://weixin.qq.com/r/BkystDbE82uMrXLX9xkU";
     * //$bgImgFile = "../t3.jpg"; //createQrImg($content, $bgImgFile,
     * "firstQr.png", 0, 0, 500, 500, 0, 1, 1);
     */
    /*
     * function createQrImg($content, $bgImgFile, $outFileName, $dstX, $dstY,
     * $dstW, $dstH, $rotation = 0, $pixel_type = 0, $mask_type = 0,
     * $bgimg_deal_type = 0) {
     * Log::write("createQrImg-args：".print_r(func_get_args(), true));
     * define(QRIMG_TMP_FILE_DIR, realpath('./Home/Upload/img_tmp/'));
     * import("@.ORG.Util.phpqrcode_modify"); $pixel = 20; //二维码单点像素数 $image_dir
     * = realpath("./Home/Lib/ORG/Util/qrimg/image/").'/'; $qrimg_file =
     * md5($content).".png"; QRcode::png($content,
     * QRIMG_TMP_FILE_DIR.'/'.$qrimg_file, 'H' ,1 ,0,TRUE); $fg_img = new
     * Imagick(QRIMG_TMP_FILE_DIR.'/'.$qrimg_file); $up_img = new
     * Imagick($bgImgFile); if ($mask_type == 1){ $mask_big_img = new
     * Imagick($image_dir."mask_big_circle.png"); $mask_small_img = new
     * Imagick($image_dir."mask_small_circle.png"); }else{ $mask_big_img = new
     * Imagick($image_dir."mask_big.png"); $mask_small_img = new
     * Imagick($image_dir."mask_small.png"); }
     * $mask_big_img->scaleImage($pixel*9, $pixel*9);
     * $mask_small_img->scaleImage($pixel*5, $pixel*5); if ($pixel_type == 1){
     * $white_img = new Imagick($image_dir."white_circle.png"); $black_img = new
     * Imagick($image_dir."black_circle.png"); }else if ($pixel_type == 2){
     * $white_img = new Imagick($image_dir."white_star.png"); $black_img = new
     * Imagick($image_dir."black_star.png"); }else{ $white_img = new
     * Imagick($image_dir."white.png"); $black_img = new
     * Imagick($image_dir."black.png"); } $white_img->scaleImage($pixel,
     * $pixel); $black_img->scaleImage($pixel, $pixel); $qrcode_x = $qrcode_y =
     * $fg_img->getImageWidth(); //长宽各加一 $qr_size = ($qrcode_x+2)*$pixel;
     * $bg_img = new Imagick(); $bg_img->newImage($qr_size, $qr_size, 'none',
     * 'png'); //遍历qr码 for($x = 0; $x < $qrcode_x; $x++){ for($y = 0; $y <
     * $qrcode_y; $y++){ $imagePixel = $fg_img->getImagePixelColor($x, $y); $b =
     * $imagePixel->getColorValue(imagick::COLOR_BLUE); if ($this->checkMask($x,
     * $y, $qrcode_x + 2)) continue; if (!$b){
     * $bg_img->compositeImage($black_img, $black_img->getImageCompose(), ($x +
     * 1) * $pixel, ($y + 1) * $pixel); }else{
     * $bg_img->compositeImage($white_img, $white_img->getImageCompose(), ($x +
     * 1) * $pixel, ($y + 1) * $pixel); } } }
     * $bg_img->compositeImage($mask_big_img, $mask_big_img->getImageCompose(),
     * 0, 0); $bg_img->compositeImage($mask_big_img,
     * $mask_big_img->getImageCompose(), 0, ($qrcode_x+2-9)*$pixel);
     * $bg_img->compositeImage($mask_big_img, $mask_big_img->getImageCompose(),
     * ($qrcode_x+2-9)*$pixel, 0); $bg_img->compositeImage($mask_small_img,
     * $mask_small_img->getImageCompose(), ($qrcode_x+2-10)*$pixel,
     * ($qrcode_x+2-10)*$pixel); if ($rotation != 0) $bg_img->rotateImage(new
     * ImagickPixel('none'), $rotation); $bg_img->scaleImage($dstW, $dstH);
     * switch ($bgimg_deal_type){ case 1: //素描 $up_img->sketchImage (50, 10,
     * 135); break; case 2: //黑白 $up_img->modulateImage(120, 0, 100); break;
     * case 3: //模拟油画 $up_img->oilPaintImage (3 ); break; case 4: //怀旧
     * $up_img->sepiaToneImage(80); break; case 5: //毛玻璃
     * $up_img->spreadImage(2); break; case 6: //曝光 $up_img->solarizeImage(1);
     * break; case 7: //浮雕 $up_img->shadeImage( true, 15, 45); break; case 8:
     * //运动模糊 $up_img->motionBlurImage(0, 20, 180); break; case 9: //漩涡
     * $up_img->swirlImage(180); break; case 10: //内曝
     * $up_img->implodeImage(0.5); break; case 11: //水波纹 $up_img->waveImage(2,
     * 50); break; default: break; } //$up_img->oilPaintImage (1 );
     * $up_img->compositeImage($bg_img, $bg_img->getImageCompose(), $dstX,
     * $dstY); $up_img->writeImage($outFileName); $white_img->clear();
     * $black_img->clear(); $mask_big_img->clear(); $mask_small_img->clear();
     * $up_img->clear(); $bg_img->clear(); $fg_img->clear(); $res = array();
     * $res = array( 'status'=>1, 'response' => 1, 'image_url' =>$outFileName );
     * return $res; }
     */
    function checkMask($x, $y, $size) {
        if (($x < 8) && ($y < 8))
            return true;
        if (($x < 8) && (($size - $y) < 11))
            return true;
        if ((($size - $x) < 11) && ($y < 8))
            return true;
        if (($x > ($size - 12)) && ($y > ($size - 12)) && ($x < ($size - 6)) &&
             ($y < ($size - 6)))
            return true;
        return false;
    }
}