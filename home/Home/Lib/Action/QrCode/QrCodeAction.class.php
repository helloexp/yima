<?php

class QrCodeAction extends Action {

    public function getQrCode() {
        $url = I('srcUrl', '');
        if (! $url) {
            return;
        }
        import('@.Vendor.MakeCode') or die('include file fail.');
        $makecode = new MakeCode();
        $makecode->MakeCodeImg($url);
        exit();
    }
}