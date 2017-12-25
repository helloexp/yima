<?php

// 首页
class FfmpegtestAction extends Action {

    public function test() {
        system(
            "ffmpeg -i /www/wangcai2/Home/Upload/UploadVoice/00004488/2015/11/21/20151121153245949400.amr /www/wangcai2/Home/Upload/UploadVoice/00004488/2015/11/21/fff.mp3");
    }
}