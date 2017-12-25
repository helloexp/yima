<?php

class VerifycodeAction extends Action {

    public function verify() {
//        import("ORG.Util.Image");
//        Image::buildImageVerify();

        import('@.Service.ImageVerifyService');
        ImageVerifyService::buildImageCode();
    }

    public function mobileverify() {
//        import("ORG.Util.Image");
//        Image::buildImageVerify(4, 1, 'png', 80, 37, 'moblieverify');

        import('@.Service.ImageVerifyService');
        ImageVerifyService::buildImageCodeByParam('verify_imgcode',4, 1, 'png', 80, 37);

    }

    public function bookverify() {
//        import("ORG.Util.Image");
//        Image::buildImageVerify(4, 1, 'png', 80, 37, 'bookverify');

        import('@.Service.ImageVerifyService');
        ImageVerifyService::buildImageCodeByParam('bookverify',4, 1, 'png', 80, 37);
    }

    public function supplyverify() {
//        import("ORG.Util.Image");
//        Image::buildImageVerify(4, 1, 'png', 80, 37, 'supplyverify');

        import('@.Service.ImageVerifyService');
        ImageVerifyService::buildImageCodeByParam('supplyverify',4, 1, 'png', 80, 37);
    }

    public function weiboverify() {
//        import("ORG.Util.Image");
//        Image::buildImageVerify(4, 1, 'png', 80, 37, 'weiboverify');

        import('@.Service.ImageVerifyService');
        ImageVerifyService::buildImageCodeByParam('weiboverify',4, 1, 'png', 80, 37);
    }
}