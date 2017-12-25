<script src="http://res.wx.qq.com/open/js/jweixin-1.0.0.js" type="text/javascript"></script>
<script type="text/javascript">
    wx.config({
        debug: false,
        appId: '{$shareData["appId"]}',  //必填，公众号的唯一标识
        timestamp: '{$shareData["timestamp"]}', // 必填，生成签名的时间戳
        nonceStr: '{$shareData["nonceStr"]}', // 必填，生成签名的随机串
        signature:'{$shareData["signature"]}',
        jsApiList: [
            'hideMenuItems',
        ]
    });

    wx.ready(function () {
        wx.hideMenuItems({
            menuList: [
                'menuItem:share:timeline', // 分享到朋友圈
                'menuItem:share:facebook',
                'menuItem:share:appMessage',
                'menuItem:share:qq',
                'menuItem:share:weiboApp'
            ],
        });
    });
</script>