<?php
// 静态文件
class StaticAction extends EmptyAction {

    public function _initialize() {
        $shareArr = array(
            'config' => D('WeiXin', 'Service')->getWxShareConfig());
        $this->assign('shareData', $shareArr);
    }
}