<?php

/**
 * Class SzpaService
 * 深圳平安非标相关逻辑
 */
class SzpaService
{

    //母亲节活动，wxid处理
    public function getMamaWxid($wxUserInfo, $node_id, $label_id)
    {
        $openid = $wxUserInfo['openid'];
        $wxarr  = M('twx_wap_user')->where([
            'openid' => $openid
        ])->find();
        if ($wxarr['id']) {
            return $wxarr['id'];
        }

        $in_arr = [
            'node_id'    => $node_id,
            'label_id'   => $label_id,
            'add_time'   => date('YmdHis'),
            'nickname'   => get_val($wxUserInfo, 'nickname'),
            'sex'        => get_val($wxUserInfo, 'sex'),
            'province'   => get_val($wxUserInfo, 'province'),
            'city'       => get_val($wxUserInfo, 'city'),
            'headimgurl' => get_val($wxUserInfo, 'headimgurl'),
            'openid'     => $openid
        ];
        $wxid   = M('twx_wap_user')->add($in_arr);
        if ( ! $wxid) {
            return false;
        }

        return $wxid;
    }
}


