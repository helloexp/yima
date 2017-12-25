<?
import("@.ORG.Util.qrimg_v2.phpqrcode_modify");
define('QRIMG_TMP_FILE_DIR', C('DOWN_TEMP'));
define('QRIMG_SIZE', 29);
define('QRIMG_ADJUST_LEVEL', 'M');

/**
 * 制作QR码图片 $content 二维码内容 $bgImgFile 背景图片 $outFileName 输出文件名 $dstX, $dstY
 * 二维码图片的左上角的坐标 $dstW, $dstH 二维码图片缩放大小，不能超过背景图大小 $rotation 自定义旋转角度 0 , 90 , 180,
 * 270 $pixel_type 二维码点阵图片类型 0-方块 1-圆点 2-星标 $mask_type 二维码角标图片类型 0-方块 1-弧形方块
 * $bgimg_deal_type 背景图片处理类型 0-不处理 1-素描 2-黑白 3-模拟油画 4-怀旧 5-毛玻璃 6-曝光 7-浮雕 8-运动模糊
 * 9-漩涡 10-内曝 11-水波纹 notice: 需要定义 QRIMG_TMP_FILE_DIR作为二维码图片生成的临时目录 ps:
 * 引入的phpqrcode_modify.php 是在phpqrcode.php上经过了小幅修改的 image目录存放需要的图片素材 example:
 * //$content = "http://weixin.qq.com/r/BkystDbE82uMrXLX9xkU"; //$bgImgFile =
 * "../t3.jpg"; //createQrImg($content, $bgImgFile, "firstQr.png", 0, 0, 500,
 * 500, 0, 1, 1); //$content = "HTTP://T.YM06.CN/TP2JKTJO";
 */
/*
 * $content = "http://Q8R.HK/UF1\x00ZXH"; $content = "http://Q8R.HK/UF1";
 * $content = "HTTP://T.CN/RvnMxIk"; $bgImgFile = "5.jpg"; createQrImg($content,
 * $bgImgFile, "1.png", 0, 0, 700, 700, 0, 0, 1, 0, 4);
 */
/*
 * createQrImg($content, "black.png", "1.png", 0, 0, 512, 512, 0, 0, 2, 0);
 * createQrImg($content, "white.png", "2.png", 0, 0, 512, 512, 0, 0, 2, 0);
 * createQrImg($content, "left.png", "3.png", 0, 0, 512, 512, 0, 0, 2, 0);
 * createQrImg($content, "right.png", "4.png", 0, 0, 512, 512, 0, 0, 2, 0);
 */
function createQrImg($content, $bgImgFile, $outFileName, $dstX, $dstY, $dstW, 
    $dstH, $rotation = 0, $pixel_type = 0, $mask_type = 0, $bgimg_deal_type = 0, 
    $adjust_level = 1) {
    C('ADJUST_LEVEL', $adjust_level);
    $pixel = 20; // 二维码单点像素数
    $image_dir = "./image/";
    $image_dir = dirname(__FILE__) . '/image/';
    $qrimg_file = md5($content) . ".png";
    
    $up_img = new Imagick($bgImgFile);
    switch ($bgimg_deal_type) {
        case 1:
            // 素描
            $up_img->sketchImage(50, 10, 135);
            break;
        case 2:
            // 黑白
            $up_img->modulateImage(120, 0, 100);
            break;
        case 3:
            // 模拟油画
            $up_img->oilPaintImage(3);
            break;
        case 4:
            // 怀旧
            $up_img->sepiaToneImage(80);
            break;
        case 5:
            // 毛玻璃
            $up_img->spreadImage(2);
            break;
        case 6:
            // 曝光
            $up_img->solarizeImage(1);
            break;
        case 7:
            // 浮雕
            $up_img->shadeImage(true, 15, 45);
            break;
        case 8:
            // 运动模糊
            $up_img->motionBlurImage(0, 20, 180);
            break;
        case 9:
            // 漩涡
            $up_img->swirlImage(180);
            break;
        case 10:
            // 内曝
            $up_img->implodeImage(0.5);
            break;
        case 11:
            // 水波纹
            $up_img->waveImage(2, 50);
            break;
        default:
            break;
    }
    // 获取背景矩阵
    getBgColorMatrix($up_img, $dstX, $dstY, $dstW, QRIMG_SIZE + 2, QRIMG_SIZE, 
        $rotation);
    // print_r($globalBgMatrix, false);
    // gen QR
    // if (!file_exists(QRIMG_TMP_FILE_DIR.$qrimg_file))
    QRcode::png($content, QRIMG_TMP_FILE_DIR . $qrimg_file, QRIMG_ADJUST_LEVEL, 
        1, 0, TRUE);
    $fg_img = new Imagick(QRIMG_TMP_FILE_DIR . $qrimg_file);
    
    if ($mask_type == 1) {
        $mask_big_img = new Imagick($image_dir . "mask_big_circle.png");
        $mask_small_img = new Imagick($image_dir . "mask_small_circle.png");
    } else if ($mask_type == 2) {
        $mask_big_img = new Imagick($image_dir . "mask_big_circle2.png");
        $mask_small_img = new Imagick($image_dir . "mask_small_circle2.png");
    } else {
        $mask_big_img = new Imagick($image_dir . "mask_big.png");
        $mask_small_img = new Imagick($image_dir . "mask_small.png");
    }
    $mask_big_img->scaleImage($pixel * 9, $pixel * 9);
    $mask_small_img->scaleImage($pixel * 5, $pixel * 5);
    if ($pixel_type == 1) {
        $white_img = new Imagick($image_dir . "white_circle.png");
        $black_img = new Imagick($image_dir . "black_circle.png");
    } else if ($pixel_type == 2) {
        $white_img = new Imagick($image_dir . "white_star.png");
        $black_img = new Imagick($image_dir . "black_star.png");
    } else {
        $white_img = new Imagick($image_dir . "white.png");
        $black_img = new Imagick($image_dir . "black.png");
    }
    $white_img->scaleImage($pixel, $pixel);
    $black_img->scaleImage($pixel, $pixel);
    
    $qrcode_x = $qrcode_y = $fg_img->getImageWidth();
    // 长宽各加一
    $qr_size = ($qrcode_x + 2) * $pixel;
    $bg_img = new Imagick();
    $bg_img->newImage($qr_size, $qr_size, 'none', 'png');
    
    // 遍历qr码
    $count = 0;
    for ($x = 0; $x < $qrcode_x; $x ++) {
        for ($y = 0; $y < $qrcode_y; $y ++) {
            $imagePixel = $fg_img->getImagePixelColor($x, $y);
            $b = $imagePixel->getColorValue(imagick::COLOR_BLUE);
            if (! $b) {
                $black = true;
            } else {
                $black = false;
            }
            if (checkMask($x, $y, $qrcode_x + 2))
                continue;
            
            if ($black) {
                $bg_img->compositeImage($black_img, 
                    $black_img->getImageCompose(), ($x + 1) * $pixel, 
                    ($y + 1) * $pixel);
            } else {
                $bg_img->compositeImage($white_img, 
                    $white_img->getImageCompose(), ($x + 1) * $pixel, 
                    ($y + 1) * $pixel);
            }
        }
    }
    $bg_img->compositeImage($mask_big_img, $mask_big_img->getImageCompose(), 0, 
        0);
    $bg_img->compositeImage($mask_big_img, $mask_big_img->getImageCompose(), 0, 
        ($qrcode_x + 2 - 9) * $pixel);
    $bg_img->compositeImage($mask_big_img, $mask_big_img->getImageCompose(), 
        ($qrcode_x + 2 - 9) * $pixel, 0);
    $bg_img->compositeImage($mask_small_img, $mask_small_img->getImageCompose(), 
        ($qrcode_x + 2 - 10) * $pixel, ($qrcode_x + 2 - 10) * $pixel);
    
    if ($rotation != 0)
        $bg_img->rotateImage(new ImagickPixel('none'), $rotation);
    $bg_img->scaleImage($dstW, $dstH);
    
    $up_img->compositeImage($bg_img, $bg_img->getImageCompose(), $dstX, $dstY);
    $up_img->writeImage($outFileName);
    
    $white_img->clear();
    $black_img->clear();
    $mask_big_img->clear();
    $mask_small_img->clear();
    $up_img->clear();
    $bg_img->clear();
    $fg_img->clear();
    
    @unlink(QRIMG_TMP_FILE_DIR . $qrimg_file);
}

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

function getBgColor($img, $x, $y, $dstX, $dstY, $dstW, $srcW) {
    $scale_rate = $dstW / $srcW;
    $pixel = $img->getImagePixelColor(
        round(($x + 1) * $scale_rate + $scale_rate / 2) + $dstX, 
        round(($y + 1) * $scale_rate + $scale_rate / 2) + $dstY);
    /**
     * * test $test_img = new Imagick("./r.jpg"); $test_img->scaleImage(10, 10);
     * $img->compositeImage($test_img, $test_img->getImageCompose(), round(($x +
     * 1)*$scale_rate + $scale_rate/2) + $dstX , round(($y + 1)*$scale_rate +
     * $scale_rate/2) + $dstY); *************
     */
    $c = $pixel->getColor(false);
    $colorY = 0.299 * $c['r'] + 0.587 * $c['g'] + 0.114 * $c['b'];
    return $colorY;
}
// $rotation 0, 90, 180, 270
function getBgColorMatrix($img, $dstX, $dstY, $dstW, $srcW, $qrcode_size, 
    $rotation = 0) {
    // global $globalBgMatrix;
    $globalBgMatrix = C('globalBgMatrix');
    $scale_rate = $dstW / $srcW;
    for ($x = 1; $x <= $qrcode_size; $x ++) {
        for ($y = 1; $y <= $qrcode_size; $y ++) {
            switch ($rotation) {
                case 0:
                    $tmpX = round(($x + 1) * $scale_rate + $scale_rate / 2) +
                         $dstX;
                    $tmpY = round(($y + 1) * $scale_rate + $scale_rate / 2) +
                         $dstY;
                    break;
                case 90:
                    $tmpX = round(
                        ($qrcode_size - $y) * $scale_rate + $scale_rate / 2) +
                         $dstY;
                    $tmpY = round(($x + 1) * $scale_rate + $scale_rate / 2) +
                         $dstX;
                    break;
                case 180:
                    $tmpX = round(
                        ($qrcode_size - $x) * $scale_rate + $scale_rate / 2) +
                         $dstX;
                    $tmpY = round(
                        ($qrcode_size - $y) * $scale_rate + $scale_rate / 2) +
                         $dstY;
                    break;
                case 270:
                    $tmpX = round(($y + 1) * $scale_rate + $scale_rate / 2) +
                         $dstY;
                    $tmpY = round(
                        ($qrcode_size - $x) * $scale_rate + $scale_rate / 2) +
                         $dstX;
                    break;
                default:
                    $tmpX = round(($x + 1) * $scale_rate + $scale_rate / 2) +
                         $dstX;
                    $tmpY = round(($y + 1) * $scale_rate + $scale_rate / 2) +
                         $dstY;
                    break;
            }
            $pixel = $img->getImagePixelColor($tmpX, $tmpY);
            $c = $pixel->getColor(false);
            $colorY = 0.299 * $c['r'] + 0.587 * $c['g'] + 0.114 * $c['b'];
            if ($colorY > 127)
                $globalBgMatrix[$x][$y] = 0;
            else {
                $globalBgMatrix[$x][$y] = 1;
                // $test_img = new Imagick("./r.jpg");
                // $test_img->scaleImage(10, 10);
                // $img->compositeImage($test_img, $test_img->getImageCompose(),
                // round(($x + 1)*$scale_rate + $scale_rate/2) + $dstX ,
                // round(($y +
                // 1)*$scale_rate + $scale_rate/2) + $dstY);
            }
        }
    }
    C('globalBgMatrix', $globalBgMatrix);
}

function checkBorder($x, $y) {
    // write dead version 3 M
    $qr_size = 29;
    if (($x < 9) && ($y < 9))
        return true;
    if (($x < 9) && ($y > 20))
        return true;
    if (($x > 20) && ($y < 9))
        return true;
    if ($x < 0)
        return true;
    if ($y < 0)
        return true;
    if ($x > $qr_size - 1)
        return true;
    if ($y > $qr_size - 1)
        return true;
    return false;
}

function checkSmallMask($x, $y) {
    // write dead version 3 M
    $qr_size = 29;
    if (($x > 19) && ($x < 25) && ($y > 19) && ($y < 25))
        return true;
    if (($x == 6) || ($y == 6))
        return true;
    return false;
}
// true 向上 false 向下
function stepByWord(&$x, &$y, &$direction) {
    $count = 8;
    $wordPoint = array();
    while ($count > 0) {
        if ($x < 0)
            break;
        if (($x == 8) && ($y == 28)) {
            $y = 20;
        }
        if (checkBorder($x, $y)) {
            if ($x % 2 == 0) {
                $x -= 2;
            } else {
                $x --;
            }
            if ($direction) {
                $y ++;
            } else {
                $y --;
            }
            $direction = ! $direction;
            continue;
        }
        if (! checkSmallMask($x, $y)) {
            $count --;
            $wordPoint[] = array(
                $x, 
                $y);
        }
        if ($x % 2 == 0) {
            $x --;
        } else {
            $x ++;
            if ($direction) {
                $y --;
            } else {
                $y ++;
            }
        }
    }
    if ($count == 0)
        return $wordPoint;
    else
        return false;
}
