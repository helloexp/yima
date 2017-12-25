<?php
// 二维码图片
class ChannelSetCodeAction extends BaseAction {
    
    // 二维码压图兼容问题
    private function codeimagepreg($logo) {
        // 正则判断兼容之前数据
        if (preg_match("/$this->nodeId/", $logo)) {
            $logourl = get_upload_url($logo);
            if (strpos($logourl, 'com')) {
                $logoUrlArray = explode('com', $logourl);
                $logourl = '.' . $logoUrlArray[1];
            }
        } else {
            $logourl = './Home/Upload/channel/' . $logo;
        }
        return $logourl;
    }
    
    // 设置二维码
    public function index() {
        $color = I('color');
        $logo = I('logo');
        $id = I('id');
        if (! empty($id)) {
            // 二维码内容
            $url = U('Label/Channel/index', 
                array(
                    'id' => $id), '', '', true);
        } else {
            $url = '设置二维码';
        }
        import('@.Vendor.MakeCode') or die('include file fail.');
        $makecode = new MakeCode();
        $logourl = $this->codeimagepreg($logo);
        $makecode->MakeCodeImg($url, false, '', $logourl, '', $color);
    }
    
    // 渠道二维码
    public function Code() {
        $type  = I('get.type','','trim');
        //我的渠道
        if($type == 'a'){
            $model = M('tbatch_channel');
        //二维码渠道
        } else if($type == 'b'){
            $model = M('tchannel');
        }  else {
            $this->error('参数错误');
        }


        $id = I('get.id');
        $isdown = I('get.isdown');
        if (empty($id))
            return false;
        $query_arr = $model->where(
            array(
                'node_id' => array(
                    'exp', 
                    "in (" . $this->nodeIn() . ")"), 
                'id' => $id))
            ->find();

        if (! $query_arr)
            return false;
        ! empty($query_arr['qr_size']) ? $size = $query_arr['qr_size'] : $size = '';
        ! empty($query_arr['qr_color']) ? $color = $query_arr['qr_color'] : $color = '';
        $logourl = $this->codeimagepreg($query_arr['logo_img']);
        // 查询渠道标签
        $label_id = $query_arr['label_id'];
        // 二维码内容
        if ($query_arr['type'] == '3') {
            if (! $label_id)
                return false;
            
            $url = C('label_base_url') . $label_id;
            $color = '000000';
            if ($query_arr['qr_type'] == '2') {
                $ap_arr = array(
                    'is_ap' => '1',  // 是否是爱拍 1是 2否
                    'label_id' => $label_id); // 标签ID
            
            } else {
                $ap_arr = array(
                    'label_id' => $label_id); // 标签ID
            
            }
        } else {
            $ap_arr = array(
                'label_id' => $label_id); // 标签ID
            $url = U('Label/Channel/index', 
                array(
                    'id' => $id), '', '', true);
            if ($type == 'a') {
                $url = U('Label/Label/index', 
                array(
                    'id' => $id), '', '', true);
            }
        }

        import('@.Vendor.MakeCode') or die('include file fail.');
        $makecode = new MakeCode();
        if ($isdown == '1') {
            $filename = $query_arr['name'];
            $makecode->MakeCodeImg($url, true, $size, $logourl, $filename, 
                $color, $ap_arr);
        } else {
            $makecode->MakeCodeImg($url, false, $size = '', $logourl, '', 
                $color, $ap_arr);

        }

    }

    public function codeUrl() {
        $id = I('id');
        if (empty($id))
            return false;
        $model = M('tchannel');
        $query_arr = $model->where(
            array(
                'node_id' => array(
                    'exp', 
                    "in (" . $this->nodeIn() . ")"), 
                'id' => $id))
            ->find();
        if (! $query_arr)
            return false;
            // 二维码内容
        if ($query_arr['type'] == '3') {
            $label_id = $query_arr['label_id'];
            if (! $label_id)
                return false;
            
            $url = C('label_base_url') . $label_id;
        } else {
            $url = U('Label/Channel/index', 
                array(
                    'id' => $id), '', '', true);
        }
        redirect($url);
    }
}