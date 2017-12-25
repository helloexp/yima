<?php

/**
 * Class ImageHelper image 帮助类
 *
 * @author Jeff Liu
 */
class ImageHelper {

    /**
     * wbmp转bmp,gif,jpg
     *
     * @param $data
     * @param $other
     * @return bool|string
     */
    public static function wbmp2other($data, $other) {
        $im = self::resizeImage($data, 2);
        if ($im !== false) {
            ob_start();
            switch ($other) {
                case 'gif':
                    imagegif($im);
                    break;
                case 'jpg':
                case 'jpeg':
                    imagejpeg($im);
                    break;
                case 'png':
                    imagepng($im);
                    break;
                case 'wbmp':
                    imagewbmp($im);
                    break;
                default:
                    return false;
                    break;
            }
            $newImg = ob_get_contents();
            ob_end_clean();
            return $newImg;
        } else {
            return false;
        }
    }

    /**
     * 放大图片
     *
     * @param $data
     * @param $fdbs
     * @return resource
     */
    public function resizeImage($data, $fdbs) {
        // Resize
        $source = imagecreatefromstring($data);
        $s_white_x = 0; //
        $s_white_y = 0; //
        $s_w = imagesx($source); // 原图宽度
                                 // 画布长宽
        $new_img_width = $s_w * $fdbs;
        $new_img_height = $new_img_width;
        
        $d_white_x = 0;
        $d_white_y = 0;
        // Load
        $thumb = imagecreate($new_img_width, $new_img_height);
        $red = imagecolorallocate($thumb, 255, 255, 255);
        
        imagecopyresized($thumb, $source, $d_white_x, $d_white_y, $s_white_x, 
            $s_white_y, $s_w * $fdbs, $s_w * $fdbs, $s_w, $s_w);
        return $thumb;
    }
}