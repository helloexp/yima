<?php

/**
 * 美图秀秀上传控件，在iframe中 auther:tr
 */
class MeituAction extends BaseAction {

    public $UPLOAD_DIR;

    public function _initialize() {
        parent::_initialize();
        $this->UPLOAD_DIR = APP_PATH . 'Upload/img_tmp/' . $this->node_id . '/';
        $img_tpl = $this->UPLOAD_DIR;
        $this->prefix = "thumb_";
        $this->assign("img_tpl", $img_tpl);
    }

    public function index() {
        $targetWidth = I('ratioX'); // 比例
        $targetHeight = I('ratioY'); // 需要的高度
        $callback = I('callback'); // 回调函数名
        $suggestX = I('suggestX'); // 建议宽度
        $suggestY = I('suggestY'); // 建议高度
                                   // $oldImg = I('origin_img'); //原先保存的图片（绝对路径）
        $resizeFlag = I('resizeflag'); // 是否强允许缩放
        $menuType = I('menuType'); // 是否强允许缩放
        $needForbidWH = I('needForbidWH');
        
        $cropPresets = I('cropPresets');
        $notice = '';
        if ($cropPresets) {
            $cropPresetsArr = explode('x', $cropPresets);
            $cropWidth = $cropPresetsArr[0] * 1;
            $cropHeight = $cropPresetsArr[1] * 1;
            $cropPresets = $cropWidth . 'x' . $cropHeight;
            $notice = '建议上传图片比例为' . $cropPresets . ',如果图片不符合规格，请使用"剪裁"功能';
        }
        $this->assign('notice', $notice);
        $uploadUrl = I('uploadUrl', 
            U('ImgResize/Meitu/upload', '', '', '', true), 'urldecode'); // 绝对路径);
                                                                         // echo
                                                                         // $uploadUrl;
        $_globalJs = array(
            'width' => $targetWidth, 
            'height' => $targetHeight, 
            'callback' => $callback, 
            'resizeFlag' => $resizeFlag, 
            'uploadUrl' => $uploadUrl, 
            'cropPresets' => $cropPresets, 
            'menuType' => $menuType, 
            'needForbidWH' => $needForbidWH);
        $this->assign('_globalJs', json_encode($_globalJs));
        $this->display();
    }

    public function upload() {
        // 如果是表单模式
        print_r($_FILES);
        if ($_FILES) {
            $upfile = '';
        } else {
            $upfile = file_get_contents('input://');
        }
    }

    public function uploadFile() {
        import('ORG.Net.UploadFile');
        $upload = new UploadFile(); // 实例化上传类
        $upload->maxSize = 1024 * 1024 * 5; // 设置附件上传大小 5兆
        $upload->allowExts = array(
            'jpg', 
            'gif', 
            'png', 
            'jpeg'); // 设置附件上传类型
        $upload->savePath = $this->UPLOAD_DIR; // 设置附件上传目录
                                               // 生成缩略图
        $upload->thumb = true;
        $upload->thumbExt = 'jpg';
        $upload->thumbPrefix = '';
        $upload->thumbMaxWidth = 300; // 缩略图最大宽度
        $upload->thumbMaxHeight = 185; // 缩略图最大高度
        if (! $upload->upload()) { // 上传错误提示错误信息
            exit(
                json_encode(
                    array(
                        'msg' => $upload->getErrorMsg(), 
                        'code' => - 1)));
        } else { // 上传成功 获取上传文件信息
            $info = $upload->getUploadFileInfo();
            if ($info)
                $info = $info[0];
            if (! $info) {
                exit(
                    json_encode(
                        array(
                            'msg' => "系统正忙", 
                            'code' => - 1)));
            }
            exit(
                json_encode(
                    array(
                        'msg' => "success", 
                        'code' => 0, 
                        'data' => array(
                            'src' => $this->UPLOAD_DIR . $info['savename'], 
                            'savename' => $info['savename'], 
                            'smallsrc' => $this->UPLOAD_DIR . $info['savename'], 
                            'width' => $info['imageinfo']['width'], 
                            'height' => $info['imageinfo']['height']))));
        }
    }

    private function _getImgUrl($imgname) {
        return $this->uploadPath . $imgname;
    }
}
