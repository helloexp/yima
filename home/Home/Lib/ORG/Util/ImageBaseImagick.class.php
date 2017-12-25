<?php

/**
 * 图像操作类库
 * @category    ORG
 * @package     ORG
 * @subpackage  Util
 * @author      Jeff.Liu <liuwy@imageco.com.cn>
 */
class ImageBaseImagick
{

    /**
     * todo 这个用的还是gd库的函数 需要修改为 imagick库函数
     * 取得图像信息
     * @static
     * @access   public
     *
     * @param $img
     *
     * @return mixed
     */
    public static function getImageInfo($img)
    {
        $imageInfo = getimagesize($img);
        if ($imageInfo !== false) {
            $imageType = strtolower(substr(image_type_to_extension($imageInfo[2]), 1));
            $imageSize = filesize($img);
            $info      = array(
                    "width"  => $imageInfo[0],
                    "height" => $imageInfo[1],
                    "type"   => $imageType,
                    "size"   => $imageSize,
                    "mime"   => $imageInfo['mime']
            );
            return $info;
        } else {
            return false;
        }
    }

    /**
     * todo 这个用的还是gd库的函数 需要修改为 imagick库函数
     * 为图片添加水印
     * @static public
     *
     * @param string $source   原文件名
     * @param string $water    水印图片
     * @param string $savename 添加水印后的图片名
     * @param int    $alpha    水印的透明度
     *
     * @return mixed
     */
    public static function water($source, $water, $savename = null, $alpha = 80)
    {
        //检查文件是否存在
        if (!file_exists($source) || !file_exists($water)) {
            return false;
        }

        //图片信息
        $sInfo = self::getImageInfo($source);
        $wInfo = self::getImageInfo($water);

        //如果图片小于水印图片，不生成图片
        if ($sInfo["width"] < $wInfo["width"] || $sInfo['height'] < $wInfo['height']) {
            return false;
        }

        //建立图像
        $sCreateFun = "imagecreatefrom" . $sInfo['type'];
        $sImage     = $sCreateFun($source);
        $wCreateFun = "imagecreatefrom" . $wInfo['type'];
        $wImage     = $wCreateFun($water);

        //设定图像的混色模式
        imagealphablending($wImage, true);

        //图像位置,默认为右下角右对齐
        $posY = $sInfo["height"] - $wInfo["height"];
        $posX = $sInfo["width"] - $wInfo["width"];

        //生成混合图像
        imagecopymerge($sImage, $wImage, $posX, $posY, 0, 0, $wInfo['width'], $wInfo['height'], $alpha);

        //输出图像
        $ImageFun = 'Image' . $sInfo['type'];
        //如果没有给出保存文件名，默认为原图像名
        if (!$savename) {
            $savename = $source;
            @unlink($source);
        }
        //保存图像
        $ImageFun($sImage, $savename);
        imagedestroy($sImage);
        return true;
    }

    /**
     * todo 这个用的还是gd库的函数 需要修改为 imagick库函数
     *
     * @param string $imgFile
     * @param string $text
     * @param string $x
     * @param string $y
     * @param string $alpha
     */
    public function showImg($imgFile, $text = '', $x = '10', $y = '10', $alpha = '50')
    {
        //获取图像文件信息
        //2007/6/26 增加图片水印输出，$text为图片的完整路径即可
        $info = Image::getImageInfo($imgFile);
        if ($info !== false) {
            $createFun = str_replace('/', 'createfrom', $info['mime']);
            $im        = false;
            if (is_callable($createFun)) {
                $im = $createFun($imgFile);
            }
            if ($im) {
                $ImageFun = str_replace('/', '', $info['mime']);
                //水印开始
                if (!empty($text)) {
                    $tc = imagecolorallocate($im, 0, 0, 0);
                    if (is_file($text) && file_exists($text)) {//判断$text是否是图片路径
                        // 取得水印信息
                        $textInfo   = Image::getImageInfo($text);
                        $createFun2 = str_replace('/', 'createfrom', $textInfo['mime']);
                        $waterMark  = null;
                        if (is_callable($createFun2)) {
                            $waterMark = $createFun2($text);
                        }
                        //设置水印的显示位置和透明度支持各种图片格式
                        imagecopymerge($im, $waterMark, $x, $y, 0, 0, $textInfo['width'], $textInfo['height'], $alpha);
                    } else {
                        imagestring($im, 80, $x, $y, $text, $tc);
                    }
                    //ImageDestroy($tc);
                }
                //水印结束
                if ($info['type'] == 'png' || $info['type'] == 'gif') {
                    imagealphablending($im, false); //取消默认的混色模式
                    imagesavealpha($im, true); //设定保存完整的 alpha 通道信息
                }
                Header("Content-type: " . $info['mime']);
                if (is_callable($ImageFun)) {
                    $ImageFun($im);
                }

                @ImageDestroy($im);
                return;
            }

            //保存图像
            if (issetAndNotEmpty($ImageFun) && is_callable($ImageFun)) {
                $ImageFun($sImage, $savename);
            }

            imagedestroy($sImage);
            //获取或者创建图像文件失败则生成空白PNG图片
            $im  = imagecreatetruecolor(80, 30);
            $bgc = imagecolorallocate($im, 255, 255, 255);
            $tc  = imagecolorallocate($im, 0, 0, 0);
            imagefilledrectangle($im, 0, 0, 150, 30, $bgc);
            imagestring($im, 4, 5, 5, "no pic", $tc);
            Image::output($im);
            return;
        }
    }

    /**
     * todo 这个用的还是gd库的函数 需要修改为 imagick库函数
     * 生成缩略图
     * @static
     * @access public
     *
     * @param string  $image     原图
     * @param string  $type      图像格式
     * @param string  $thumbname 缩略图文件名
     * @param string  $maxWidth  宽度
     * @param string  $maxHeight 高度
     * @param string  $position  缩略图保存目录
     * @param boolean $interlace 启用隔行扫描
     *
     * @return void|mixed
     */
    public static function thumb($image, $thumbname, $type = '', $maxWidth = 200, $maxHeight = 50, $interlace = true)
    {
        // 获取原图信息
        $info = Image::getImageInfo($image);
        if ($info !== false) {
            $srcWidth  = $info['width'];
            $srcHeight = $info['height'];
            $type      = empty($type) ? $info['type'] : $type;
            $type      = strtolower($type);
            $interlace = $interlace ? 1 : 0;
            unset($info);
            $scale = min($maxWidth / $srcWidth, $maxHeight / $srcHeight); // 计算缩放比例
            if ($scale >= 1) {
                // 超过原图大小不再缩略
                $width  = $srcWidth;
                $height = $srcHeight;
            } else {
                // 缩略图尺寸
                $width  = (int)($srcWidth * $scale);
                $height = (int)($srcHeight * $scale);
            }

            // 载入原图
            $createFun = 'ImageCreateFrom' . ($type == 'jpg' ? 'jpeg' : $type);
            if (!function_exists($createFun)) {
                return false;
            }
            $srcImg = $createFun($image);

            //创建缩略图
            if ($type != 'gif' && function_exists('imagecreatetruecolor')) {
                $thumbImg = imagecreatetruecolor($width, $height);
            } else {
                $thumbImg = imagecreate($width, $height);
            }
            //png和gif的透明处理 by luofei614
            if ('png' == $type) {
                imagealphablending($thumbImg, false);//取消默认的混色模式（为解决阴影为绿色的问题）
                imagesavealpha($thumbImg, true);//设定保存完整的 alpha 通道信息（为解决阴影为绿色的问题）
            } elseif ('gif' == $type) {
                $trnprt_indx = imagecolortransparent($srcImg);
                if ($trnprt_indx >= 0) {
                    //its transparent
                    $trnprt_color = imagecolorsforindex($srcImg, $trnprt_indx);
                    $trnprt_indx  = imagecolorallocate(
                            $thumbImg,
                            $trnprt_color['red'],
                            $trnprt_color['green'],
                            $trnprt_color['blue']
                    );
                    imagefill($thumbImg, 0, 0, $trnprt_indx);
                    imagecolortransparent($thumbImg, $trnprt_indx);
                }
            }
            // 复制图片
            if (function_exists("ImageCopyResampled")) {
                imagecopyresampled($thumbImg, $srcImg, 0, 0, 0, 0, $width, $height, $srcWidth, $srcHeight);
            } else {
                imagecopyresized($thumbImg, $srcImg, 0, 0, 0, 0, $width, $height, $srcWidth, $srcHeight);
            }

            // 对jpeg图形设置隔行扫描
            if ('jpg' == $type || 'jpeg' == $type) {
                imageinterlace($thumbImg, $interlace);
            }

            // 生成图片
            $imageFun = 'image' . ($type == 'jpg' ? 'jpeg' : $type);
            $imageFun($thumbImg, $thumbname);
            imagedestroy($thumbImg);
            imagedestroy($srcImg);
            return $thumbname;
        }
        return false;
    }

    /**
     * todo 这个用的还是gd库的函数 需要修改为 imagick库函数
     * 根据给定的字符串生成图像
     * @static
     * @access   public
     *
     * @param string  $string  字符串
     * @param array   $rgb
     * @param string  $filename
     * @param string  $type    图像格式 默认PNG
     * @param integer $disturb 是否干扰 1 点干扰 2 线干扰 3 复合干扰 0 无干扰
     * @param bool    $border  是否加边框 array(color)
     *
     * @return string
     */
    static function buildString($string, $rgb = array(), $filename = '', $type = 'png', $disturb = 1, $border = true)
    {
        $size = get_val($size, '');
        if (is_string($size)) {
            $size = explode(',', $size);
        }
        $width  = $size[0];
        $height = $size[1];
        $font   = get_val($font, '');
        if (is_string($font)) {
            $font = explode(',', $font);
        }
        $fontface = $font[0];
        $fontsize = $font[1];
        $length   = strlen($string);
        $width    = ($length * 9 + 10) > $width ? $length * 9 + 10 : $width;
        $height   = 22;
        if ($type != 'gif' && function_exists('imagecreatetruecolor')) {
            $im = @imagecreatetruecolor($width, $height);
        } else {
            $im = @imagecreate($width, $height);
        }
        if (empty($rgb)) {
            $color = imagecolorallocate($im, 102, 104, 104);
        } else {
            $color = imagecolorallocate($im, $rgb[0], $rgb[1], $rgb[2]);
        }
        $backColor   = imagecolorallocate($im, 255, 255, 255);    //背景色（随机）
        $borderColor = imagecolorallocate($im, 100, 100, 100);                    //边框色
        $pointColor  = imagecolorallocate($im, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));                 //点颜色

        @imagefilledrectangle($im, 0, 0, $width - 1, $height - 1, $backColor);
        @imagerectangle($im, 0, 0, $width - 1, $height - 1, $borderColor);
        @imagestring($im, 5, 5, 3, $string, $color);
        if (!empty($disturb)) {
            // 添加干扰
            if ($disturb = 1 || $disturb = 3) {
                for ($i = 0; $i < 25; $i++) {
                    imagesetpixel($im, mt_rand(0, $width), mt_rand(0, $height), $pointColor);
                }
            } elseif ($disturb = 2 || $disturb = 3) {
                for ($i = 0; $i < 10; $i++) {
                    imagearc(
                            $im,
                            mt_rand(-10, $width),
                            mt_rand(-10, $height),
                            mt_rand(30, 300),
                            mt_rand(20, 200),
                            55,
                            44,
                            $pointColor
                    );
                }
            }
        }
        Image::output($im, $type, $filename);
    }

    /**
     * 生成图像验证码
     * @static
     * @access public
     *
     * @param int|string $length 位数
     * @param int|string $mode   类型
     * @param string     $type   图像格式
     * @param int|string $width  宽度
     * @param int|string $height 高度
     *
     * @param string     $verifyName
     *
     * @return string
     */
    public static function buildImageVerify(
            $length = 4,
            $mode = 1,
            $type = 'png',
            $width = 48,
            $height = 22,
            $verifyName = 'verify'
    ) {
        import('ORG.Util.TpString');
        //测试环境不下发，验证码直接为1111
        $randval = TpString::randString($length, $mode);
        if (!is_production()) {
            $randval = '1111';
        }
        session($verifyName, md5($randval));
        $width = ($length * 10 + 10) > $width ? $length * 10 + 10 : $width;

        //        $r   = [225, 255, 255, 223];
        //        $g   = [225, 236, 237, 255];
        //        $b   = [225, 236, 166, 125];
        //        $key = mt_rand(0, 3);
        //
        //        $currentRed   = $r[$key];
        //        $currentGreen = $g[$key];
        //        $currentBlue  = $b[$key];

        //start ----
        /* Create a new imagick object */
        $im = new Imagick();

        /* Create new image. This will be used as fill pattern */
        //                $im->newPseudoImage($width, $height, "gradient:red-rgba({$currentRed}, {$currentGreen}, {$currentBlue}, 0.5)");
        $pseudoString = self::getPseudoString();
        $im->newPseudoImage($width, $height, $pseudoString);

        /* Create imagickdraw object */
        $draw = new ImagickDraw();

        /* Start a new pattern called "gradient" */
        $draw->pushPattern('gradient', 0, 0, $width, $height);

        /* Composite the gradient on the pattern */
        $draw->composite(Imagick::COMPOSITE_OVER, 0, 0, $width, $height, $im);

        /* Close the pattern */
        $draw->popPattern();

        /* Use the pattern called "gradient" as the fill */
        $draw->setFillPatternURL('#gradient');

        /* Set font size to 52 */
        $draw->setFontSize(20);

        /* Annotate some text */
        $draw->annotation(1, 20, $randval);

        /* Create a few random lines */
        $draw->line(rand(0, 70), rand(0, 30), rand(0, 70), rand(0, 30));
        $draw->line(rand(0, 70), rand(0, 30), rand(0, 70), rand(0, 30));
        $draw->line(rand(0, 70), rand(0, 30), rand(0, 70), rand(0, 30));
        $draw->line(rand(0, 70), rand(0, 30), rand(0, 70), rand(0, 30));
        $draw->line(rand(0, 70), rand(0, 30), rand(0, 70), rand(0, 30));
        $draw->line(rand(0, 70), rand(0, 30), rand(0, 70), rand(0, 30));
        $draw->line(rand(0, 70), rand(0, 30), rand(0, 70), rand(0, 30));

        /* Create a new canvas object and a white image */
        $canvas = new Imagick();
        $canvas->newImage($width, $height, 'white');

        /* Draw the ImagickDraw on to the canvas */
        $canvas->drawImage($draw);

        /* 1px black border around the image */
        $canvas->borderImage('black', 1, 1);

        /* Set the format */

        $canvas->setImageFormat($type);

        /* Output the image */
        self::output($canvas, $type);
        //end ----
    }

    /**
     * todo 这个用的还是gd库的函数 需要修改为 imagick库函数
     * 中文验证码
     *
     * @param int    $length
     * @param string $type
     * @param int    $width
     * @param int    $height
     * @param string $fontface
     * @param string $verifyName
     */
    public static function GBVerify(
            $length = 4,
            $type = 'png',
            $width = 180,
            $height = 50,
            $fontface = 'simhei.ttf',
            $verifyName = 'verify'
    ) {
        import('ORG.Util.TpString');
        $code  = TpString::randString($length, 4);
        $width = ($length * 45) > $width ? $length * 45 : $width;
        session($verifyName, md5($code));
        $im          = imagecreatetruecolor($width, $height);
        $borderColor = imagecolorallocate($im, 100, 100, 100);                    //边框色
        $bkcolor     = imagecolorallocate($im, 250, 250, 250);
        imagefill($im, 0, 0, $bkcolor);
        @imagerectangle($im, 0, 0, $width - 1, $height - 1, $borderColor);
        // 干扰
        for ($i = 0; $i < 15; $i++) {
            $fontcolor = imagecolorallocate($im, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            imagearc(
                    $im,
                    mt_rand(-10, $width),
                    mt_rand(-10, $height),
                    mt_rand(30, 300),
                    mt_rand(20, 200),
                    55,
                    44,
                    $fontcolor
            );
        }
        for ($i = 0; $i < 255; $i++) {
            $fontcolor = imagecolorallocate($im, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            imagesetpixel($im, mt_rand(0, $width), mt_rand(0, $height), $fontcolor);
        }
        if (!is_file($fontface)) {
            $fontface = dirname(__FILE__) . "/" . $fontface;
        }
        for ($i = 0; $i < $length; $i++) {
            $fontcolor = imagecolorallocate($im, mt_rand(0, 120), mt_rand(0, 120), mt_rand(0, 120)); //这样保证随机出来的颜色较深。
            $codex     = TpString::msubstr($code, $i, 1);
            imagettftext(
                    $im,
                    mt_rand(16, 20),
                    mt_rand(-60, 60),
                    40 * $i + 20,
                    mt_rand(30, 35),
                    $fontcolor,
                    $fontface,
                    $codex
            );
        }
        Image::output($im, $type);
    }

    /**
     * todo 这个用的还是gd库的函数 需要修改为 imagick库函数
     * 把图像转换成字符显示
     * @static
     * @access public
     *
     * @param string $image 要显示的图像
     * @param string $type  图像类型，默认自动获取
     *
     * @return string
     */
    static function showASCIIImg($image, $string = '', $type = '')
    {
        $info = Image::getImageInfo($image);
        if ($info !== false) {
            $type = empty($type) ? $info['type'] : $type;
            unset($info);
            // 载入原图
            $createFun = 'ImageCreateFrom' . ($type == 'jpg' ? 'jpeg' : $type);
            $im        = $createFun($image);
            $dx        = imagesx($im);
            $dy        = imagesy($im);
            $i         = 0;
            $out       = '<span style="padding:0px;margin:0;line-height:100%;font-size:1px;">';
            set_time_limit(0);
            for ($y = 0; $y < $dy; $y++) {
                for ($x = 0; $x < $dx; $x++) {
                    $col = imagecolorat($im, $x, $y);
                    $rgb = imagecolorsforindex($im, $col);
                    $str = empty($string) ? '*' : $string[$i++];
                    $out .= sprintf(
                            '<span style="margin:0px;color:#%02x%02x%02x">' . $str . '</span>',
                            $rgb['red'],
                            $rgb['green'],
                            $rgb['blue']
                    );
                }
                $out .= "<br>\n";
            }
            $out .= '</span>';
            imagedestroy($im);
            return $out;
        }
        return false;
    }

    /**
     * todo 这个用的还是gd库的函数 需要修改为 imagick库函数
     * 生成UPC-A条形码
     * @static
     *
     * @param        $code
     * @param string $type 图像格式
     * @param int    $lw   单元宽度
     * @param int    $hi   条码高度
     *
     * @return string
     */
    static function UPCA($code, $type = 'png', $lw = 2, $hi = 100)
    {
        static $Lencode = array(
                '0001101',
                '0011001',
                '0010011',
                '0111101',
                '0100011',
                '0110001',
                '0101111',
                '0111011',
                '0110111',
                '0001011'
        );
        static $Rencode = array(
                '1110010',
                '1100110',
                '1101100',
                '1000010',
                '1011100',
                '1001110',
                '1010000',
                '1000100',
                '1001000',
                '1110100'
        );
        $ends   = '101';
        $center = '01010';
        /* UPC-A Must be 11 digits, we compute the checksum. */
        if (strlen($code) != 11) {
            die("UPC-A Must be 11 digits.");
        }
        /* Compute the EAN-13 Checksum digit */
        $ncode = '0' . $code;
        $even  = 0;
        $odd   = 0;
        for ($x = 0; $x < 12; $x++) {
            if ($x % 2) {
                $odd += $ncode[$x];
            } else {
                $even += $ncode[$x];
            }
        }
        $code .= (10 - (($odd * 3 + $even) % 10)) % 10;
        /* Create the bar encoding using a binary string */
        $bars = $ends;
        $bars .= $Lencode[$code[0]];
        for ($x = 1; $x < 6; $x++) {
            $bars .= $Lencode[$code[$x]];
        }
        $bars .= $center;
        for ($x = 6; $x < 12; $x++) {
            $bars .= $Rencode[$code[$x]];
        }
        $bars .= $ends;
        /* Generate the Barcode Image */
        if ($type != 'gif' && function_exists('imagecreatetruecolor')) {
            $im = imagecreatetruecolor($lw * 95 + 30, $hi + 30);
        } else {
            $im = imagecreate($lw * 95 + 30, $hi + 30);
        }
        $fg = ImageColorAllocate($im, 0, 0, 0);
        $bg = ImageColorAllocate($im, 255, 255, 255);
        ImageFilledRectangle($im, 0, 0, $lw * 95 + 30, $hi + 30, $bg);
        $shift = 10;
        for ($x = 0; $x < strlen($bars); $x++) {
            if (($x < 10) || ($x >= 45 && $x < 50) || ($x >= 85)) {
                $sh = 10;
            } else {
                $sh = 0;
            }
            if ($bars[$x] == '1') {
                $color = $fg;
            } else {
                $color = $bg;
            }
            ImageFilledRectangle($im, ($x * $lw) + 15, 5, ($x + 1) * $lw + 14, $hi + 5 + $sh, $color);
        }
        /* Add the Human Readable Label */
        ImageString($im, 4, 5, $hi - 5, $code[0], $fg);
        for ($x = 0; $x < 5; $x++) {
            ImageString($im, 5, $lw * (13 + $x * 6) + 15, $hi + 5, $code[$x + 1], $fg);
            ImageString($im, 5, $lw * (53 + $x * 6) + 15, $hi + 5, $code[$x + 6], $fg);
        }
        ImageString($im, 4, $lw * 95 + 17, $hi - 5, $code[11], $fg);
        /* Output the Header and Content. */
        Image::output($im, $type);
    }


    /**
     * @param        $canvas
     * @param string $type
     */
    public static function output($canvas, $type = 'png')
    {
        header("Content-Type: image/{$type}");
        echo $canvas;
    }


    /**
     * @see http://www.imagemagick.org/script/color.php
     * @return array
     */
    public static function getColorList()
    {

        return [
                'red',
                'brown',
                'blue',
                'gray',
                'grey',
                'pink',
                'green',
                'yellow',
                'black',
                'gold',
                'cyan',
                'DarkGreen',
                'purple',
                'indigo'
        ];

        return [
                'snow',
                'snow1',
                'snow2',
                'RosyBrown1',
                'RosyBrown2',
                'snow3',
                'LightCoral',
                'IndianRed1',
                'RosyBrown3',
                'IndianRed2',
                'RosyBrown',
                'brown1',
                'firebrick1',
                'brown2',
                'IndianRed',
                'IndianRed3',
                'firebrick2',
                'snow4',
                'brown3',
                'red',
                'red1',
                'RosyBrown4',
                'firebrick3',
                'red2',
                'firebrick',
                'brown',
                'red3',
                'IndianRed4',
                'brown4',
                'firebrick4',
                'DarkRed',
                'red4',
                'maroon',
                'LightPink1',
                'LightPink3',
                'LightPink4',
                'LightPink2',
                'LightPink',
                'pink',
                'crimson',
                'pink1',
                'pink2',
                'pink3',
                'pink4',
                'PaleVioletRed4',
                'PaleVioletRed',
                'PaleVioletRed2',
                'PaleVioletRed1',
                'PaleVioletRed3',
                'LavenderBlush',
                'LavenderBlush1',
                'LavenderBlush3',
                'LavenderBlush2',
                'LavenderBlush4',
                'maroon',
                'HotPink3',
                'VioletRed3',
                'VioletRed1',
                'VioletRed2',
                'VioletRed4',
                'HotPink2',
                'HotPink1',
                'HotPink4',
                'HotPink',
                'DeepPink',
                'DeepPink1',
                'DeepPink2',
                'DeepPink3',
                'DeepPink4',
                'maroon1',
                'maroon2',
                'maroon3',
                'maroon4',
                'MediumVioletRed',
                'VioletRed',
                'orchid2',
                'orchid',
                'orchid1',
                'orchid3',
                'orchid4',
                'thistle1',
                'thistle2',
                'plum1',
                'plum2',
                'thistle',
                'thistle3',
                'plum',
                'violet',
                'plum3',
                'thistle4',
                'fuchsia',
                'magenta',
                'magenta1',
                'plum4',
                'magenta2',
                'magenta3',
                'DarkMagenta',
                'magenta4',
                'purple',
                'MediumOrchid',
                'MediumOrchid1',
                'MediumOrchid2',
                'MediumOrchid3',
                'MediumOrchid4',
                'DarkViolet',
                'DarkOrchid',
                'DarkOrchid1',
                'DarkOrchid3',
                'DarkOrchid2',
                'DarkOrchid4',
                'purple',
                'indigo',
                'BlueViolet',
                'purple2',
                'purple3',
                'purple4',
                'purple1',
                'MediumPurple',
                'MediumPurple1',
                'MediumPurple2',
                'MediumPurple3',
                'MediumPurple4',
                'DarkSlateBlue',
                'LightSlateBlue',
                'MediumSlateBlue',
                'SlateBlue',
                'SlateBlue1',
                'SlateBlue2',
                'SlateBlue3',
                'SlateBlue4',
                'GhostWhite',
                'lavender',
                'blue',
                'blue1',
                'blue2',
                'blue3',
                'MediumBlue',
                'blue4',
                'DarkBlue',
                'MidnightBlue',
                'navy',
                'NavyBlue',
                'RoyalBlue',
                'RoyalBlue1',
                'RoyalBlue2',
                'RoyalBlue3',
                'RoyalBlue4',
                'CornflowerBlue',
                'LightSteelBlue',
                'LightSteelBlue1',
                'LightSteelBlue2',
                'LightSteelBlue3',
                'LightSteelBlue4',
                'SlateGray4',
                'SlateGray1',
                'SlateGray2',
                'SlateGray3',
                'LightSlateGray',
                'LightSlateGrey',
                'SlateGray',
                'SlateGrey',
                'DodgerBlue',
                'DodgerBlue1',
                'DodgerBlue2',
                'DodgerBlue4',
                'DodgerBlue3',
                'AliceBlue',
                'SteelBlue4',
                'SteelBlue',
                'SteelBlue1',
                'SteelBlue2',
                'SteelBlue3',
                'SkyBlue4',
                'SkyBlue1',
                'SkyBlue2',
                'SkyBlue3',
                'LightSkyBlue',
                'LightSkyBlue4',
                'LightSkyBlue1',
                'LightSkyBlue2',
                'LightSkyBlue3',
                'SkyBlue',
                'LightBlue3',
                'DeepSkyBlue',
                'DeepSkyBlue1',
                'DeepSkyBlue2',
                'DeepSkyBlue4',
                'DeepSkyBlue3',
                'LightBlue1',
                'LightBlue2',
                'LightBlue',
                'LightBlue4',
                'PowderBlue',
                'CadetBlue1',
                'CadetBlue2',
                'CadetBlue3',
                'CadetBlue4',
                'turquoise1',
                'turquoise2',
                'turquoise3',
                'turquoise4',
                'cadet blue',
                'CadetBlue',
                'DarkTurquoise',
                'azure',
                'azure1',
                'LightCyan',
                'LightCyan1',
                'azure2',
                'LightCyan2',
                'PaleTurquoise1',
                'PaleTurquoise',
                'PaleTurquoise2',
                'DarkSlateGray1',
                'azure3',
                'LightCyan3',
                'DarkSlateGray2',
                'PaleTurquoise3',
                'DarkSlateGray3',
                'azure4',
                'LightCyan4',
                'aqua',
                'cyan',
                'cyan1',
                'PaleTurquoise4',
                'cyan2',
                'DarkSlateGray4',
                'cyan3',
                'cyan4',
                'DarkCyan',
                'teal',
                'DarkSlateGray',
                'DarkSlateGrey',
                'MediumTurquoise',
                'LightSeaGreen',
                'turquoise',
                'aquamarine4',
                'aquamarine',
                'aquamarine1',
                'aquamarine2',
                'aquamarine3',
                'MediumAquamarine',
                'MediumSpringGreen',
                'MintCream',
                'SpringGreen',
                'SpringGreen1',
                'SpringGreen2',
                'SpringGreen3',
                'SpringGreen4',
                'MediumSeaGreen',
                'SeaGreen',
                'SeaGreen3',
                'SeaGreen1',
                'SeaGreen4',
                'SeaGreen2',
                'MediumForestGreen',
                'honeydew',
                'honeydew1',
                'honeydew2',
                'DarkSeaGreen1',
                'DarkSeaGreen2',
                'PaleGreen1',
                'PaleGreen',
                'honeydew3',
                'LightGreen',
                'PaleGreen2',
                'DarkSeaGreen3',
                'DarkSeaGreen',
                'PaleGreen3',
                'honeydew4',
                'green1',
                'lime',
                'LimeGreen',
                'DarkSeaGreen4',
                'green2',
                'PaleGreen4',
                'green3',
                'ForestGreen',
                'green4',
                'green',
                'DarkGreen',
                'LawnGreen',
                'chartreuse',
                'chartreuse1',
                'chartreuse2',
                'chartreuse3',
                'chartreuse4',
                'GreenYellow',
                'DarkOliveGreen3',
                'DarkOliveGreen1',
                'DarkOliveGreen2',
                'DarkOliveGreen4',
                'DarkOliveGreen',
                'OliveDrab',
                'OliveDrab1',
                'OliveDrab2',
                'OliveDrab3',
                'YellowGreen',
                'OliveDrab4',
                'ivory',
                'ivory1',
                'LightYellow',
                'LightYellow1',
                'beige',
                'ivory2',
                'LightGoldenrodYellow',
                'LightYellow2',
                'ivory3',
                'LightYellow3',
                'ivory4',
                'LightYellow4',
                'yellow',
                'yellow1',
                'yellow2',
                'yellow3',
                'yellow4',
                'olive',
                'DarkKhaki',
                'khaki2',
                'LemonChiffon4',
                'khaki1',
                'khaki3',
                'khaki4',
                'PaleGoldenrod',
                'LemonChiffon',
                'LemonChiffon1',
                'khaki',
                'LemonChiffon3',
                'LemonChiffon2',
                'MediumGoldenRod',
                'cornsilk4',
                'gold',
                'gold1',
                'gold2',
                'gold3',
                'gold4',
                'LightGoldenrod',
                'LightGoldenrod4',
                'LightGoldenrod1',
                'LightGoldenrod3',
                'LightGoldenrod2',
                'cornsilk3',
                'cornsilk2',
                'cornsilk',
                'cornsilk1',
                'goldenrod',
                'goldenrod1',
                'goldenrod2',
                'goldenrod3',
                'goldenrod4',
                'DarkGoldenrod',
                'DarkGoldenrod1',
                'DarkGoldenrod2',
                'DarkGoldenrod3',
                'DarkGoldenrod4',
                'FloralWhite',
                'wheat2',
                'OldLace',
                'wheat',
                'wheat1',
                'wheat3',
                'orange',
                'orange1',
                'orange2',
                'orange3',
                'orange4',
                'wheat4',
                'moccasin',
                'PapayaWhip',
                'NavajoWhite3',
                'BlanchedAlmond',
                'NavajoWhite',
                'NavajoWhite1',
                'NavajoWhite2',
                'NavajoWhite4',
                'AntiqueWhite4',
                'AntiqueWhite',
                'tan',
                'bisque4',
                'burlywood',
                'AntiqueWhite2',
                'burlywood1',
                'burlywood3',
                'burlywood2',
                'AntiqueWhite1',
                'burlywood4',
                'AntiqueWhite3',
                'DarkOrange',
                'bisque2',
                'bisque',
                'bisque1',
                'bisque3',
                'DarkOrange1',
                'linen',
                'DarkOrange2',
                'DarkOrange3',
                'DarkOrange4',
                'peru',
                'tan1',
                'tan2',
                'tan3',
                'tan4',
                'PeachPuff',
                'PeachPuff1',
                'PeachPuff4',
                'PeachPuff2',
                'PeachPuff3',
                'SandyBrown',
                'seashell4',
                'seashell2',
                'seashell3',
                'chocolate',
                'chocolate1',
                'chocolate2',
                'chocolate3',
                'chocolate4',
                'SaddleBrown',
                'seashell',
                'seashell1',
                'sienna4',
                'sienna',
                'sienna1',
                'sienna2',
                'sienna3',
                'LightSalmon3',
                'LightSalmon',
                'LightSalmon1',
                'LightSalmon4',
                'LightSalmon2',
                'coral',
                'OrangeRed',
                'OrangeRed1',
                'OrangeRed2',
                'OrangeRed3',
                'OrangeRed4',
                'DarkSalmon',
                'salmon1',
                'salmon2',
                'salmon3',
                'salmon4',
                'coral1',
                'coral2',
                'coral3',
                'coral4',
                'tomato4',
                'tomato',
                'tomato1',
                'tomato2',
                'tomato3',
                'MistyRose4',
                'MistyRose2',
                'MistyRose',
                'MistyRose1',
                'salmon',
                'MistyRose3',
                'white',
                'gray100',
                'grey100',
                'grey100',
                'gray99',
                'grey99',
                'gray98',
                'grey98',
                'gray97',
                'grey97',
                'gray96',
                'grey96',
                'WhiteSmoke',
                'gray95',
                'grey95',
                'gray94',
                'grey94',
                'gray93',
                'grey93',
                'gray92',
                'grey92',
                'gray91',
                'grey91',
                'gray90',
                'grey90',
                'gray89',
                'grey89',
                'gray88',
                'grey88',
                'gray87',
                'grey87',
                'gainsboro',
                'gray86',
                'grey86',
                'gray85',
                'grey85',
                'gray84',
                'grey84',
                'gray83',
                'grey83',
                'LightGray',
                'LightGrey',
                'gray82',
                'grey82',
                'gray81',
                'grey81',
                'gray80',
                'grey80',
                'gray79',
                'grey79',
                'gray78',
                'grey78',
                'gray77',
                'grey77',
                'gray76',
                'grey76',
                'silver',
                'gray75',
                'grey75',
                'gray74',
                'grey74',
                'gray73',
                'grey73',
                'gray72',
                'grey72',
                'gray71',
                'grey71',
                'gray70',
                'grey70',
                'gray69',
                'grey69',
                'gray68',
                'grey68',
                'gray67',
                'grey67',
                'DarkGray',
                'DarkGrey',
                'gray66',
                'grey66',
                'gray65',
                'grey65',
                'gray64',
                'grey64',
                'gray63',
                'grey63',
                'gray62',
                'grey62',
                'gray61',
                'grey61',
                'gray60',
                'grey60',
                'gray59',
                'grey59',
                'gray58',
                'grey58',
                'gray57',
                'grey57',
                'gray56',
                'grey56',
                'gray55',
                'grey55',
                'gray54',
                'grey54',
                'gray53',
                'grey53',
                'gray52',
                'grey52',
                'gray51',
                'grey51',
                'fractal',
                'gray50',
                'grey50',
                'gray',
                'gray49',
                'grey49',
                'gray48',
                'grey48',
                'gray47',
                'grey47',
                'gray46',
                'grey46',
                'gray45',
                'grey45',
                'gray44',
                'grey44',
                'gray43',
                'grey43',
                'gray42',
                'grey42',
                'DimGray',
                'DimGrey',
                'gray41',
                'grey41',
                'gray40',
                'grey40',
                'gray39',
                'grey39',
                'gray38',
                'grey38',
                'gray37',
                'grey37',
                'gray36',
                'grey36',
                'gray35',
                'grey35',
                'gray34',
                'grey34',
                'gray33',
                'grey33',
                'gray32',
                'grey32',
                'gray31',
                'grey31',
                'gray30',
                'grey30',
                'gray29',
                'grey29',
                'gray28',
                'grey28',
                'gray27',
                'grey27',
                'gray26',
                'grey26',
                'gray25',
                'grey25',
                'gray24',
                'grey24',
                'gray23',
                'grey23',
                'gray22',
                'grey22',
                'gray21',
                'grey21',
                'gray20',
                'grey20',
                'gray19',
                'grey19',
                'gray18',
                'grey18',
                'gray17',
                'grey17',
                'gray16',
                'grey16',
                'gray15',
                'grey15',
                'gray14',
                'grey14',
                'gray13',
                'grey13',
                'gray12',
                'grey12',
                'gray11',
                'grey11',
                'gray10',
                'grey10',
                'gray9',
                'grey9',
                'gray8',
                'grey8',
                'gray7',
                'grey7',
                'gray6',
                'grey6',
                'gray5',
                'grey5',
                'gray4',
                'grey4',
                'gray3',
                'grey3',
                'gray2',
                'grey2',
                'gray1',
                'grey1',
                'black',
                'gray0',
                'grey0',
        ];
    }

    public static function getPseudoString()
    {
        $colorList    = self::getColorList();
        $colorCount   = count($colorList) - 1;
        $color1Key    = mt_rand(0, $colorCount);
        $color2Key    = mt_rand(0, $colorCount);
        $pseudoString = "gradient:{$colorList[$color1Key]}-{$colorList[$color2Key]}";

        return $pseudoString;
    }

    /**
     *
     * @param string $verifyName
     * @param string $imageCode
     * @param string $ignoreKey
     * @param string $ignoreValue
     *
     * @return bool
     */
    public static function VerifyImageCode($verifyName, $imageCode, $ignoreKey = '', $ignoreValue = '')
    {
        $realImageCode   = session($verifyName);
        $postedImageCode = md5($imageCode);
        if ($ignoreKey && $ignoreValue) {
            if (C($ignoreKey) === $ignoreValue) {
                return true;
            }
        }
        return $realImageCode === $postedImageCode;
    }
}

