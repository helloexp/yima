<?php

/**
 * 功能： 绑定新渠道 用途： 活动渠道引流统计 Created by PhpStorm. User: Mac Date: 9/23/15 Time:
 * 1:29 PM
 */
class NewBindChannelModel extends BaseModel {
    // $batchType 类型 1 网络(线上) 2 线下 3爱拍 4 高级（微信）5其他高级渠道 6 全民营销渠道 7 付满送 8 微信
    // $sns_type二级分类
    // 1新浪|2腾讯|3QQ空间|4|人人网|5开心网|6豆瓣|7网易|8搜狐|9微信|10其他|11企业官网|13|首页渠道|12O2O社区'|21DM单|22海报|23产品包装|24企业名片|25桌(台)卡|26其他'|31平面媒体|33电视媒体|34其他|35实体门店|41微信渠道|42列表|43微官网|44团购列表|45商品销售列表|51员工渠道|52百度直达号渠道|53全民营销渠道|54电商推广员|56红包渠道|61预览渠道|
    // 81微信场景码| 82微信模板消息绑定引流
    public function __construct() {
        parent::__construct();
        import("@.Vendor.CommonConst");
    }

    /**
     *
     * @param $nodeId
     * @param $channelId
     * @param $name 渠道名称
     * @param $type 类型 1 网络(线上) 2 线下 3爱拍 4 高级（微信）5其他高级渠道 6 全民营销渠道 7 付满送 8 微信
     * @param $sns_type 二级分类
     *            1新浪|2腾讯|3QQ空间|4|人人网|5开心网|6豆瓣|7网易|8搜狐|9微信|10其他|11企业官网|13|首页渠道|12O2O社区'|21DM单|22海报|23产品包装|24企业名片|25桌(台)卡|26其他'|31平面媒体|33电视媒体|34其他|35实体门店|41微信渠道|42列表|43微官网|44团购列表|45商品销售列表|51员工渠道|52百度直达号渠道|53全民营销渠道|54电商推广员|56红包渠道|61预览渠道|
     *            81微信场景码| 82微信模板消息绑定引流
     * @return $url 新绑定渠道id生成的url活动地址
     */
    public function getNewChannelUrl($nodeId, $channelId, $name, $type, 
        $sns_type) {
        // 获取活动channel_id
        $batchInfo = M('tbatch_channel')->where(
            array(
                'node_id' => $nodeId, 
                'id' => $channelId))
            ->field('channel_id,batch_type,batch_id')
            ->find();
        
        $data = array(
            'type' => $type, 
            'name' => $name, 
            'sns_type' => $sns_type, 
            'status' => 1, 
            'node_id' => $nodeId, 
            'add_time' => date('YmdHis'), 
            'send_count' => 0);
        $channelId = M('tchannel')->add($data);
        if (empty($channelId)) {
            throw_exception("渠道创建失败");
        }
        
        if ($channelId) {
            $data = array(
                'batch_type' => $batchInfo['batch_type'], 
                'batch_id' => $batchInfo['batch_id'], 
                'channel_id' => $channelId, 
                'add_time' => date('YmdHis'), 
                'node_id' => $nodeId, 
                'status' => 1);
            
            // 绑定新渠道
            $batchNewId = M('tbatch_channel')->add($data);
            if (! $batchNewId) {
                throw_exception("绑定渠道创建失败");
            } else {
                return U('Label/Label/index', 
                    array(
                        'id' => $batchNewId), '', '', true);
            }
        } else {
            throw_exception("绑定渠道创建失败");
        }
    }
}