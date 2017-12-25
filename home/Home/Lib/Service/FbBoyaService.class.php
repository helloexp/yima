<?php

/*
 * 这儿封装博雅非标业务的一些功能函数 通过传入的手机号或者openid等信息获取推广员 以及更新tecshop_promotion_member
 * 的memberId
 */
class FbBoyaService {

    public $opt = array();

    public function __construct() {
    }

    /* 设置参数 */
    public function setopt() {
    }
    
    // 已知openid 获取推广员 微信中
    public function getPromotionByOpenid($openid, $node_id, $memId) {
        if (! $openid || ! $node_id || ! $memId)
            return array(
                'code' => '0001', 
                'msg' => '数据错误', 
                'prom_id' => 0);
            // twx_user id
        $wxu_id = M('twx_user')->where(
            array(
                'openid' => $openid, 
                'node_id' => $node_id))->getField('id');
        if (! $wxu_id)
            return array(
                'code' => '0002', 
                'msg' => '获取微信用户数据错误', 
                'prom_id' => 0);
        
        $proInfo = M('tecshop_promotion_member')->where(
            array(
                'wx_user_id' => $wxu_id, 
                'status' => '0'))->find();
        if (! $proInfo)
            return array(
                'code' => '0003', 
                'msg' => '获取微信用户推广员数据错误', 
                'prom_id' => 0);
            // 更新memeber_id
        if (! $proInfo['member_id'] && $memId) {
            $ret = M('tecshop_promotion_member')->where(
                array(
                    'id' => $proInfo['id']))->save(
                array(
                    'member_id' => $memId));
        }
        
        return array(
            'code' => '0000', 
            'msg' => '获取推广员id成功', 
            'prom_id' => $proInfo['promotion_id'], 
            'echopPromotionMemberID' => $proInfo['id']);
    }
    
    // 已知手机 获取推广员 浏览器中
    public function getPromotionByPhone($phone, $node_id, $memId) {
        if (! $phone || ! $node_id || ! $memId)
            return array(
                'code' => '0001', 
                'msg' => '数据错误', 
                'prom_id' => 0);
        
        $proInfo = M('tecshop_promotion_member')->where(
            array(
                'member_id' => $memId, 
                'status' => '0'))->find();
        if (! $proInfo)
            return array(
                'code' => '0002', 
                'msg' => '获取手机用户推广员数据错误', 
                'prom_id' => 0);
        
        if ($proInfo['status'] == '1')
            return array(
                'code' => '0000', 
                'msg' => '获取新推广员id成功', 
                'prom_id' => $proInfo['new_promotion_id']);
        else
            return array(
                'code' => '0000', 
                'msg' => '获取推广员id成功', 
                'prom_id' => $proInfo['promotion_id'], 
                'echopPromotionMemberID' => $proInfo['id']);
    }
    
    // 第一次登录 更新memberid
    public function updateTecshopProMId($openid, $node_id, $memId) {
        if (! $openid || ! $node_id || ! $memId)
            return array(
                'code' => '0001', 
                'msg' => '数据错误');
            // twx_user id
        $wxu_id = M('twx_user')->where(
            array(
                'openid' => $openid, 
                'node_id' => $node_id))->getField('id');
        if (! $wxu_id)
            return array(
                'code' => '0002', 
                'msg' => '获取微信用户数据错误');
        
        $proInfo = M('tecshop_promotion_member')->where(
            array(
                'wx_user_id' => $wxu_id, 
                'status' => '0'))->find();
        if (! $proInfo)
            return array(
                'code' => '0003', 
                'msg' => '获取微信用户推广员数据错误');
            // 更新memeber_id
        $ret = M('tecshop_promotion_member')->where(
            array(
                'id' => $proInfo['id']))->save(
            array(
                'member_id' => $memId));
        if ($ret === false)
            return array(
                'code' => '0004', 
                'msg' => '更新微信用户推广员数据错误');
        else
            return array(
                'code' => '0000', 
                'msg' => '更新微信用户推广员数据成功');
    }
}

