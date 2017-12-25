<?
import("@.ORG.Util.qrimg.phpqrcode_modify");
// define(QRIMG_TMP_FILE_DIR, dirname(__FILE__) . "./tmp/");
define(QRIMG_TMP_FILE_DIR, C('DOWN_TEMP'));

/**
 * 制作QR码图片 $content 二维码内容 $bgImgFile 背景图片 $outFileName 输出文件名 $dstX, $dstY
 * 二维码图片的左上角的坐标 $dstW, $dstH 二维码图片缩放大小，不能超过背景图大小 $rotation 自定义旋转角度 $pixel_type
 * 二维码点阵图片类型 0-方块 1-圆点 2-星标 $mask_type 二维码角标图片类型 0-方块 1-弧形方块 notice: 需要定义
 * QRIMG_TMP_FILE_DIR作为二维码图片生成的临时目录 ps: 引入的phpqrcode_modify.php
 * 是在phpqrcode.php上经过了小幅修改的 image目录存放需要的图片素材 example: //$content =
 * "http://weixin.qq.com/r/BkystDbE82uMrXLX9xkU"; //$bgImgFile = "../t3.jpg";
 * //createQrImg($content, $bgImgFile, "firstQr.png", 0, 0, 500, 500, 0, 1, 1);
 */
function createQrImg($content, $bgImgFile, $outFileName, $dstX, $dstY, $dstW, 
    $dstH, $rotation = 0, $pixel_type = 0, $mask_type = 0) {
    $pixel = 20; // 二维码单点像素数
    $image_dir = dirname(__FILE__) . '/image/';
    $qrimg_file = md5($content) . ".png";
    QRcode::png($content, QRIMG_TMP_FILE_DIR . $qrimg_file, 'L', 1, 0, TRUE);
    $fg_img = new Imagick(QRIMG_TMP_FILE_DIR . $qrimg_file);
    $up_img = new Imagick($bgImgFile);
    if ($mask_type == 1) {
        $mask_big_img = new Imagick($image_dir . "mask_big_circle.png");
        $mask_small_img = new Imagick($image_dir . "mask_small_circle.png");
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
    for ($x = 0; $x < $qrcode_x; $x ++) {
        for ($y = 0; $y < $qrcode_y; $y ++) {
            $imagePixel = $fg_img->getImagePixelColor($x, $y);
            $b = $imagePixel->getColorValue(imagick::COLOR_BLUE);
            if (checkMask($x, $y, $qrcode_x + 2))
                continue;
            if (! $b) {
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
    // $up_img->oilPaintImage (1 );
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
