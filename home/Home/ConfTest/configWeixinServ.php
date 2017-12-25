<?php
/* 接口配置（手动载入）转移支/WeixinServ下吧 */
$IMG_URL = 'http://test.wangcaio2o.com/Home/Upload/Weixin/'; // 图片发布路径
$PUBLIC_IMG_URL = 'http://test.wangcaio2o.com/Home/Public/Image/WeixinServ/'; // 图片共公路径
return array(
    'IMG_URL' => $IMG_URL,  // 图片发布路径
    'LOG_PATH' => LOG_PATH . 'LogWeixinServ_',  // 日志路径+文件名前缀,
    'CUSTOM_LOG_PATH' => LOG_PATH . 'LogWeixinServ_',  // 日志路径+文件名前缀,
    'SID_TIME' => 25,  // 微信sid更新周期 单位 分,
    'YIMA_WEIXIN_NODE_ID' => '00004506',  // 生产平台cpyfy@imageco.com.cn机构号00018419,测试00004506
                                         // 位置事件图片
    'LOCATION_IMG' => array(
        'default' => array(
            'top' => $PUBLIC_IMG_URL . 'location/wangcai_top.jpg',  // 顶部图片
            'list' => $PUBLIC_IMG_URL . 'location/wangcai_list.png'),  // 列表
                                                                      // 喜临门
        '00011046' => array(
            'top' => $PUBLIC_IMG_URL . 'location/xlm_top.jpg',  // 顶部图片
            'list' => $PUBLIC_IMG_URL . 'location/xlm_list.png'),  // 列表
                                                                  // 旺财
        '00011046' => array(
            'top' => $PUBLIC_IMG_URL . 'location/wangcai_top.png',  // 顶部图片
            'list' => $PUBLIC_IMG_URL . 'location/wangcai_list.png'))); // 列表

