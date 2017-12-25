<?php
// 特殊IMEI号控制,手动载入
return array(
    // 特殊IMEI号版本控制
    "TEST_IMEI" => array(
        'TEST_VERSION' => array(
            '869715012548650'
        ) // 堂民旺财
,
        'RELEASE_VERSION' => array(
            '869715012548874'
        ) // 老吴的旺财

    ),
    "TEST_VERSION" => array(
        "ANDROID_VER" => "10745", // 最新版本号
        "UPGRADE_FLAG" => 1, // 升级标识 1选择升级 2 强制升级
        "ANDROID_URL" => "http://222.44.51.34/test/WangCaiBeta2.0.5.fullscreen.beta.apk", // 升级地址
        "UPGRADE_TEXT" => "V2.0.5 版本更新说明：\n" . " 更新功能：\n " . "扫码全屏测试 \n " . "升级功能测试中，本窗口可能一直都存在，请大家选择升级到最新版\n"
    ),
    
    // 生产平台版本号
    "RELEASE_VERSION" => array(
        "ANDROID_VER" => "10745", // 最新版本号
        "UPGRADE_FLAG" => 1, // 升级标识 1选择升级 2 强制升级
        "ANDROID_URL" => "http://222.44.51.34/test/apk/WangCaiBeta2.0.5.release.apk", // 升级地址
        "UPGRADE_TEXT" => "V2.0.5.release 版本更新说明：\n" . " 更新功能：\n " . "本版本为生产平台版 \n " . "升级功能测试中，本窗口可能一直都存在，请大家选择升级到最新版\n"
    )
)
;