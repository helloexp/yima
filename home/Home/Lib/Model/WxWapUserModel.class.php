<?php

/**
 *
 * @author lwb Time 20151119
 */
class WxWapUserModel extends Model {

    protected $tableName = 'twx_wap_user';

    public function _initialize() {
        import('@.Vendor.CommonConst') or die('include file fail.');
    }
    
    // 更新添加twx_wap_user表数据
    /**
     *
     * @param array $wxUserInfo['access_token', 'openid', 'nickname', ]
     * @param unknown $nodeId
     * @param unknown $labelId
     */
    public function setWxUserInfo($wxUserInfo, $nodeId, $labelId) {
        $count = $this->where(
            array(
                'openid' => $wxUserInfo['openid']))->count();
        $data = array(
            'access_token' => $wxUserInfo['access_token'], 
            'openid' => $wxUserInfo['openid'], 
            'nickname' => $wxUserInfo['nickname'], 
            'sex' => $wxUserInfo['sex'], 
            'province' => $wxUserInfo['province'], 
            'city' => $wxUserInfo['city'], 
            'headimgurl' => $wxUserInfo['headimgurl']);
        if ($count > 0) { // 更新操作
            $result = $this->where(
                array(
                    'openid' => $wxUserInfo['openid']))->save($data);
            if ($result === false) {
                throw_exception('更新微信用户数据失败');
            }
        } else { // 添加操作
            $data['node_id'] = $nodeId;
            $data['add_time'] = date('YmdHis');
            $data['label_id'] = $labelId;
            $result = $this->add($data);
            if ($result === false) {
                throw_exception('添加微信用户数据失败');
            }
        }
    }
}