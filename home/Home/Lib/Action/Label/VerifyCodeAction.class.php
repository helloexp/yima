<?php
// 验证码
class VerifyCodeAction extends Action {

    Public function index() {
//        import('ORG.Util.Image');
//        Image::buildImageVerify($length = 4, $mode = 1, $type = 'png',
//                $width = 48, $height = 22, $verifyName = 'verify_cj');
        import('@.Service.ImageVerifyService');
        ImageVerifyService::buildImageCodeByParam('verify_cj');
    }
}