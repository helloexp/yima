<?php
// 报名
class MapAction extends MyBaseAction {
    
    // 初始化
    public function _initialize() {
    }

    /**
     * 指定位置（坐标）显示在百度地图上
     */
    public function showPosition() {
        $this->lng = I('lng', ''); // 百度地图的经度
        $this->lat = I('lat', ''); // 百度地图的纬度
        $this->address = I('address', ''); // 地址
        $this->display();
    }
}