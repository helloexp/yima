<?php

/* 裁剪图片 */
class crop_image {

    var $filep;

    var $imageSource;

    var $viewPortW;

    var $viewPortH;

    var $imageW;

    var $imageH;

    var $selectorX;

    var $selectorY;

    var $imageRotate;

    var $imageX;

    var $imageY;

    var $selectorW;

    var $selectorH;

    public function __construct($filep, $imageSource, $viewPortW, $viewPortH, 
        $imageW, $imageH, $selectorX, $selectorY, $imageRotate, $imageX, $imageY, 
        $selectorW, $selectorH) {
        $this->filep = $filep;
        $this->imageSource = $imageSource;
        $this->viewPortW = $viewPortW;
        $this->viewPortH = $viewPortH;
        $this->imageW = $imageW;
        $this->imageH = $imageH;
        $this->selectorX = $selectorX;
        $this->selectorY = $selectorY;
        $this->imageRotate = $imageRotate;
        $this->imageX = $imageX;
        $this->imageY = $imageY;
        $this->selectorW = $selectorW;
        $this->selectorH = $selectorH;
    }

    public function crop() {
        $imageSource = $this->imageSource;
        $filep = $this->filep;
        $viewPortW = $this->viewPortW;
        $viewPortH = $this->viewPortH;
        $imageW = $this->imageW;
        $imageH = $this->imageH;
        
        $selectorX = $this->selectorX;
        $selectorY = $this->selectorY;
        
        $imageRotate = $this->imageRotate;
        
        $imageX = $this->imageX;
        $imageY = $this->imageY;
        
        $selectorW = $this->selectorW;
        $selectorH = $this->selectorH;
        
        list ($width, $height) = getimagesize($imageSource);
        $viewPortW = $viewPortW;
        $viewPortH = $viewPortH;
        $pWidth = $imageW;
        $pHeight = $imageH;
        $ext = end(explode(".", $imageSource));
        switch ($ext) {
            case "png":
                
                $image = imagecreatefrompng($imageSource);
                break;
            case "jpeg":
                
                $image = imagecreatefromjpeg($imageSource);
                break;
            case "jpg":
                
                $image = imagecreatefromjpeg($imageSource);
                break;
            case "gif":
                
                $image = imagecreatefromgif($imageSource);
                break;
        }
        // $function = $this->returnCorrectFunction($ext);
        // $image = $function($imageSource);
        $width = imagesx($image);
        $height = imagesy($image);
        // Resample
        $image_p = imagecreatetruecolor($pWidth, $pHeight);
        $this->setTransparency($image, $image_p, $ext);
        imagecopyresampled($image_p, $image, 0, 0, 0, 0, $pWidth, $pHeight, 
            $width, $height);
        imagedestroy($image);
        $widthR = imagesx($image_p);
        $hegihtR = imagesy($image_p);
        
        $selectorX = $selectorX;
        $selectorY = $selectorY;
        
        if ($imageRotate) {
            $angle = 360 - $imageRotate;
            $image_p = imagerotate($image_p, $angle, 0);
            
            $pWidth = imagesx($image_p);
            $pHeight = imagesy($image_p);
            
            // print $pWidth."---".$pHeight;
            
            $diffW = abs($pWidth - $widthR) / 2;
            $diffH = abs($pHeight - $hegihtR) / 2;
            
            $imageX = ($pWidth > $widthR ? $imageX - $diffW : $imageX + $diffW);
            $imageY = ($pHeight > $hegihtR ? $imageY - $diffH : $imageY + $diffH);
        }
        
        $dst_x = $src_x = $dst_y = $src_y = 0;
        
        if ($imageX > 0) {
            $dst_x = abs($imageX);
        } else {
            $src_x = abs($imageX);
        }
        if ($imageY > 0) {
            $dst_y = abs($imageY);
        } else {
            $src_y = abs($imageY);
        }
        
        $viewport = imagecreatetruecolor($viewPortW, $viewPortH);
        $this->setTransparency($image_p, $viewport, $ext);
        
        imagecopy($viewport, $image_p, $dst_x, $dst_y, $src_x, $src_y, $pWidth, 
            $pHeight);
        imagedestroy($image_p);
        
        $selector = imagecreatetruecolor($selectorW, $selectorH);
        $this->setTransparency($viewport, $selector, $ext);
        imagecopy($selector, $viewport, 0, 0, $selectorX, $selectorY, 
            $viewPortW, $viewPortH);
        
        $file = $filep . time() . "." . $ext;
        $this->parseImage($ext, $selector, $file);
        imagedestroy($viewport);
        return $file;
    }

    public function determineImageScale() {
        $scalex = $this->targetWidth / $this->sourceWidth;
        $scaley = $this->targetHeight / $this->sourceHeight;
        return min($scalex, $scaley);
    }

    public function returnCorrectFunction($ext) {
        $function = "";
        switch ($ext) {
            case "png":
                $function = "imagecreatefrompng";
                break;
            case "jpeg":
                $function = "imagecreatefromjpeg";
                break;
            case "jpg":
                $function = "imagecreatefromjpeg";
                break;
            case "gif":
                $function = "imagecreatefromgif";
                break;
        }
        return $function;
    }

    public function parseImage($ext, $img, $file = null) {
        switch ($ext) {
            case "png":
                imagepng($img, ($file != null ? $file : ''));
                break;
            case "jpeg":
                imagejpeg($img, ($file ? $file : ''), 90);
                break;
            case "jpg":
                imagejpeg($img, ($file ? $file : ''), 90);
                break;
            case "gif":
                imagegif($img, ($file ? $file : ''));
                break;
        }
    }

    public function setTransparency($imgSrc, $imgDest, $ext) {
        if ($ext == "png" || $ext == "gif") {
            $trnprt_indx = imagecolortransparent($imgSrc);
            // If we have a specific transparent color
            if ($trnprt_indx >= 0) {
                // Get the original image's transparent color's RGB values
                $trnprt_color = imagecolorsforindex($imgSrc, $trnprt_indx);
                // Allocate the same color in the new image resource
                $trnprt_indx = imagecolorallocate($imgDest, 
                    $trnprt_color['red'], $trnprt_color['green'], 
                    $trnprt_color['blue']);
                // Completely fill the background of the new image with
                // allocated color.
                imagefill($imgDest, 0, 0, $trnprt_indx);
                // Set the background color for new image to transparent
                imagecolortransparent($imgDest, $trnprt_indx);
            } // Always make a transparent background color for PNGs that don't
              // have one allocated already
            elseif ($ext == "png") {
                // Turn off transparency blending (temporarily)
                imagealphablending($imgDest, true);
                // Create a new transparent color for image
                $color = imagecolorallocatealpha($imgDest, 0, 0, 0, 127);
                // Completely fill the background of the new image with
                // allocated color.
                imagefill($imgDest, 0, 0, $color);
                // Restore transparency blending
                imagesavealpha($imgDest, true);
            }
        }
    }
}