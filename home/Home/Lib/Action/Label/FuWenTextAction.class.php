<?php

/**
 * 微信富文本页面
 */
class FuWenTextAction extends BaseAction
{
    protected $checkLoginReturn = true;
    public $_authAccessMap = '*';
    protected $needCheckUserPower = false;
    // 首页
    public function index()
    {
        $id = I('id', 0);
        if (0 == $id) {
            $this->error('页面展示错误');
        }
        
        $content = M('twx_material_ex')->where(
            array(
                'material_id' => $id))->getField('material_text');
        $material = M('twx_material')->where(
            array(
                'id' => $id))->find();
        if (basename($material['material_img']) == $material['material_img']) {
            $img_url = './Home/Upload/Weixin/' . $material['material_img'];
        } else {
            $img_url = get_upload_url($material['material_img']);
        }
        $title = M('tweixin_info')->where(
            array(
                'node_id' => $material['node_id']))->getField('weixin_code');
        $content = htmlspecialchars_decode($content);
        $this->assign('show_cover_pic', $material['show_cover_pic']);
        $this->assign('content', $content);
        $this->assign('img_url', $img_url);
        $this->assign('title', $title);
        
        $this->display(); // 输出模板
    }

    public function hpyb()
    {
        $id = I('id');
        $callback = I('callback', '');
        $code = I('code', '');
        $info = M('twx_message')->field(' a.*,b.config_data ')->join(' a inner join tmarketing_info b on a.m_id=b.id')->where('a.id=' . $id)->find();
        log_write('分享页面所需数据:'.M()->getLastSql());
        $nodeId = 0;
        if (isset($info['node_id']) && $info['node_id']) {
            $nodeId = $info['node_id'];
        } else {
            return false;
        }
        $weixin_info = M('tweixin_info')->where(
            array(
                'node_id' => $nodeId))->find();
        $wechatUserInfo = $this->getWechatUserInfo($nodeId);
        if (empty($wechatUserInfo)) {
            $currentUrl = U('Label/FuWenText/hpyb', I('get.'), false, false, true);
            $get = I('get.');
            $get['node_id'] = $nodeId;
            $apiCallbackUrl = U('Label/FuWenText/callbackAndRedirectByNodeId', $get, false, false, true);
            $this->wechatAuthorizeByNodeId($nodeId, 0, $currentUrl, $apiCallbackUrl);
        }
        $openId = $wechatUserInfo['openid'];
        $user_info = M('twx_user')->where(
            array(
                'node_id' => $info['node_id'], 
                'openid' => $openId))->find();
        $shareData = D('WeiXin', 'Service')->getWxShareConfig('', $weixin_info['app_id'], $weixin_info['app_secret']);
        $shareConfig = unserialize($info['config_data']);
        if(stristr($shareConfig['share_logo'],'http://') === false){
            $shareConfig['share_logo'] = 'http://test.wangcaio2o.com/Home/Upload/'.$shareConfig['share_logo'];
            //上线后改地址
//            $shareConfig['share_logo'] = 'http://www.wangcaio2o.com/Home/Upload/'.$shareConfig['share_logo'];
        }

        $this->assign('shareData', $shareData);
        $this->assign('weixin_info', $weixin_info);
        $this->assign('user_info', $user_info);
        $this->assign('info', $info);
        $this->assign('id', $id);
        $this->assign('share', $shareConfig);           //分享配置
        $this->display();
    }

    public function shareCount()
    {
        $id = I('id', '');
        M('twx_message')->where('id=' . $id)->setInc('share_num');
    }
}
