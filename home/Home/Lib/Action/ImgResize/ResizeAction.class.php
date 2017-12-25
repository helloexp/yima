<?php

/**
 * Created by PhpStorm. User: JustinZhang Date: 14-2-11 Time: 上午11:06
 */
class ResizeAction extends BaseAction {

    public $UPLOAD_DIR;

    public $prefix;

    public function _initialize() {
        parent::_initialize();
        $this->UPLOAD_DIR = APP_PATH . 'Upload/img_tmp/' . $this->node_id . '/';
        $this->prefix = "thumb_";
    }

    public function index() {
        $targetWidth = I('ratioX'); // 需要的宽度
        
        $targetHeight = I('ratioY'); // 需要的高度
        
        $callback = I('callback'); // 回调函数名
        
        $bathName = I('bathName');
        
        $suggestX = I('suggestX');
        $suggestY = I('suggestY');
        
        // $oldImg = I('origin_img'); //原先保存的图片（绝对路径）
        $resizeFlag = I('resizeflag');
        
        $newImg = null; // 新图片
        
        if (! $targetHeight) {
            $targetHeight = 0;
        }
        if (! $targetWidth) {
            $targetWidth = 0;
        }
        if ($resizeFlag == null) {
            $resizeFlag = 'true';
        }
        
        /*
         * 也许有一天需要在展现上传过的图片 if($oldImg){ echo("----->".$oldImg); $newImg =
         * $this->UPLOAD_DIR.basename($oldImg); $this->move_image($oldImg ,
         * $newImg); $this->assign('origin_img',$newImg); }
         */
        
        $this->assign('callback', $callback);
        $this->assign('targetWidth', $targetWidth);
        $this->assign('targetHeight', $targetHeight);
        $this->assign('resizeflag', $resizeFlag);
        $this->assign('bathName', $bathName);
        $this->assign('suggestX', $suggestX);
        $this->assign('suggestY', $suggestY);
        
        $this->display();
    }
    
    // 文件上传
    public function upload_file() {
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
        import('@.ORG.Net.UploadFileExtension');
        $info = null;
        $upload = new UploadFileExtension(); // 实例化上传类
        $upload->maxSize = $this->maxSize;
        $upload->allowExts = $this->allowExts;
        $upload->savePath = $this->UPLOAD_DIR; // APP_PATH.'/Upload/img_tmp/'.$this->node_id.'/';//
                                               // 设置附件
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
            'imgurl' => $this->imgurl,  // 返回图片名
            'width' => $info[0]['width'], 
            'height' => $info[0]['height'], 
            'absolutepath' => $info[0]['savepath'] . $info[0]['savename']);
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
            $this->maxSize = $img_config['SIZE']; // 设置附件上传大小
            $this->allowExts = (array) explode(",", $img_config['TYPE']); // 设置附件上传类型
        }
        if ($type == "audio") {
            $this->maxSize = $audio_config['SIZE']; // 设置附件上传大小
            $this->allowExts = (array) explode(",", $audio_config['TYPE']); // 设置附件上传类型
        }
    }

    /**
     *
     * @param $thumb_image_name 裁剪后图片
     * @param $image 原图片
     * @param $width 原图片裁剪宽度
     * @param $height 原图片裁剪高度
     * @param $start_width 原图片裁剪开始X
     * @param $start_height 原图片裁剪开始Y
     * @param $scale 缩放比例，保持1为保持原图大小
     * @return 裁剪后图片位置和名称
     */
    public function resizeImg() {
        // $thumb_image_name =$_GET['thumb_image_name'];
        $image = str_replace('..', '', I('get.image'));
        $width = $_GET['width'];
        $height = $_GET['height'];
        $start_width = $_GET['start_width'];
        $start_height = $_GET['start_height'];
        $scale = $_GET['scale'];
        $targetHeight = I('targetHeight');
        $targetWidth = I('targetWidth');
        
        $shouldresizeflag = I('resizeflag', 'false'); // 是否将原图片裁剪成制定长宽，默认false
        if (! $targetHeight || ! $targetWidth) {
            $shouldresizeflag = 'false';
        }
        
        if ($image == null) {
            $this->error('原图片地址没有上传');
        }
        
        /*
         * if(!$thumb_image_name){ $this->error('裁剪图片地址没有上传'); }
         */
        
        if ($width == null) {
            $this->error('裁剪图片宽度没有上传');
        }
        if ($height == null) {
            $this->error('裁剪高度地址没有上传');
        }
        if ($start_width == null) {
            $this->error('裁剪开始X没有上传');
        }
        if ($start_height == null) {
            $this->error('裁剪开始Y没有上传');
        }
        if ($scale == null) {
            $this->error('裁剪图片缩放比例没有上传');
        }
        
        if ($targetHeight == null) {
            $this->error('图片实际高度没有上传');
        }
        if ($targetWidth == null) {
            $this->error('图片实际宽度没有上传');
        }
        
        if (is_null($shouldresizeflag)) {
            $shouldresizeflag == 'true';
        }
        
        $thumb_image_name_pure = $this->prefix . $image;
        $thumb_image_name = $this->UPLOAD_DIR . $this->prefix . $image;
        $image = $this->UPLOAD_DIR . $image; // 添加文件夹
        
        list ($imagewidth, $imageheight, $imageType) = getimagesize($image);
        
        $imageType = image_type_to_mime_type($imageType);

        $shouldresizeflag = 'false'; //强制不重设size
        /*
         * $newImageWidth = ceil($width * $scale); $newImageHeight =
         * ceil($height * $scale);
         */
        if ($shouldresizeflag == 'true') {
            // 缩放到想要的大小 默认
            $newImageWidth = $targetWidth;
            $newImageHeight = $targetHeight;
        } else {
            $newImageWidth = $width;
            $newImageHeight = $height;
        }
        // 防止内存溢出,定义最大，最小高度 1024 ，宽度 1024 大小不限制
//        $whRate = $width / $height;
//        if ($width > $height && $width > 1024) {
//            $newImageWidth = 1024;
//            $newImageHeight = $newImageWidth / $whRate;
//        } elseif ($width < $height && $height > 1024 * 5) {
//            $newImageHeight = 1024 * 5;
//            $newImageWidth = $newImageHeight * $whRate;
//        }
        
        $newImage = imagecreatetruecolor($newImageWidth, $newImageHeight);
        imagealphablending($newImage, false); // 这里很重要,意思是不合并颜色,直接用$img图像颜色替换,
        imagesavealpha($newImage, true); // 这里很重要,意思是不要丢了$thumb图像的透明色;
        
        switch ($imageType) {
            case "image/gif":
                $source = imagecreatefromgif($image);
                break;
            case "image/pjpeg":
            case "image/jpeg":
            case "image/jpg":
                $source = imagecreatefromjpeg($image);
                break;
            case "image/png":
            case "image/x-png":
                $source = imagecreatefrompng($image);
                imagesavealpha($source, true); // 这里很重要;
                break;
        }
        imagecopyresampled($newImage, $source, 0, 0, $start_width, 
            $start_height, $newImageWidth, $newImageHeight, $width, $height);
        switch ($imageType) {
            case "image/gif":
                imagegif($newImage, $thumb_image_name);
                break;
            case "image/pjpeg":
            case "image/jpeg":
            case "image/jpg":
                imagejpeg($newImage, $thumb_image_name, 90);
                break;
            case "image/png":
            case "image/x-png":
                imagepng($newImage, $thumb_image_name);
                break;
        }
        // chmod($thumb_image_name, 0777);
        $map = array(
            "msg" => "0000", 
            "error" => false, 
            "image_name" => $thumb_image_name_pure,  /*新图片名称*/
            "origin" => $image, /*原图片名称*/
            "absolutepath" => $thumb_image_name, /*新图片完整地址*/
            "width" => $newImageWidth, 
            "height" => $newImageHeight);
        // echo($thumb_image_name);
        echo json_encode($map);
        exit();
    }
    
    // 移动图片 tmp->Upload/MicroWebImg/Nodei
    private function move_image($old_image_url, $new_image_url) {
        if (substr($old_image_url, 0, 3) == '../') {
            return "图片路径非法";
        }
        $flag = copy($old_image_url, $new_image_url);
        if ($flag) {
            return true;
        } else {
            return "图片路径非法";
        }
    }
    
    // 接受美图返回的文件上传
    public function uploadFile() {
        import('@.ORG.Net.UploadFile');
        $upload = new UploadFile(); // 实例化上传类
        $upload->maxSize = 1024 * 3000; // 设置附件上传大小 3兆
        $upload->allowExts = array(
            'jpg', 
            'gif', 
            'png', 
            'jpeg'); // 设置附件上传类型
        $upload->savePath = $this->UPLOAD_DIR; // 设置附件上传目录
        if (! $upload->upload()) { // 上传错误提示错误信息
            $this->errormsg = $upload->getErrorMsg();
        } else { // 上传成功 获取上传文件信息
            $info = $upload->getUploadFileInfo();
            if ($info)
                $info = $info[0];
        }
        $arr = array(
            'msg' => '0000',  // 通信是否成功
            'error' => $this->errormsg,  // 返回错误
            'imgurl' => $info['savename'],  // 返回图片名
            'absolutepath' => $this->UPLOAD_DIR . $info['savename']);
        echo json_encode($arr);
        exit();
    }
}