<?php
// 二维码图片
class ShowCodeAction extends BaseAction {

    public $id;

    public $isdown;

    public $showsize;

    public $size = '1';

    public $fileName = '';

    public $filePath = '';

    public function _initialize() {
        $this->id = I('id');
        $this->isdown = I('isdown');
    }

    public function download($id = '', $isdown, $filePath) {
        $this->id = $id;
        $this->isdown = $isdown;
        $this->filePath = $filePath;
        $this->index();
    }
    
    // 首页二维码显示
    public function index() {
        if (empty($this->id)) {
            return false;
        }
        $filePath = $this->filePath;
        $preview = I('get.preview');
        $from_user_id = I('from_user_id');
        $from_type = I('from_type');
        
        $model = M('tbatch_channel');
        $map = array(
            'id' => $this->id);
        $result = $model->where($map)->find();

        if (! $result)
            return false;
        
        $label_id = $result['label_id'];
        $ap_arr = array(
            'label_id' => $label_id); // 标签ID
                                      
        // 活动图片，没有的话启用企业图片
        $model2 = M('tmarketing_info');
        $map2 = array(
            'id' => $result['batch_id'], 
            'batch_type' => $result['batch_type']);
        $query_arr = $model2->where($map2)->find();

        if (! empty($query_arr['code_img'])) {
            $imageurl = get_upload_url(
                './Home/Upload/' . $query_arr['code_img']);
        } else {
            $nodeInfoModel = M('tnode_info');
            $headLogo = $nodeInfoModel->where(
                array(
                    'node_id' => $query_arr['node_id']))
                ->field('head_photo')
                ->find();
            $imageurl = get_upload_url($headLogo['head_photo']);
        }
        if ($query_arr['size'] != '') {
            $this->size = $query_arr['size'];
        }
        
        import('@.Vendor.MakeCode') or die('include file fail.');
        $makecode = new MakeCode();
        $wh_arr = array();
        if ($result['batch_type'] == '26' || $result['batch_type'] == '27') {
            $wh_arr['id'] = $this->id;
            $wh_arr['goodssale'] = '1';
        } else {
            $wh_arr = array(
                'id' => $this->id);
            if ($from_user_id && $from_type) {
                $wh_arr['from_user_id'] = $from_user_id;
                $wh_arr['from_type'] = $from_type;
            }
        }
        
        $url = U('Label/Label/index', $wh_arr, '', '', true);
        
        if ($this->isdown == '1') {
            // 获取渠道名称
            $channelName = M('tchannel')->where("id={$result['channel_id']}")->getField(
                'name');
            // 二维码图片名称
            $fileName = $query_arr['name'] . '-' . $channelName . '-' . $this->id;
            $this->fileName = $fileName;
            if ($filePath) {
                $fileName = mb_convert_encoding($fileName, "GBK", "UTF-8");
            }
            $fileName = $filePath ? $filePath . $fileName : $fileName;
            $saveandprint = $filePath ? false : true; // 如果有“$filePath”，表示不需要打印输出
            $makecode->MakeCodeImg($url, true, $this->size, $imageurl, 
                $fileName, '', $ap_arr, $saveandprint);
        } else {
            $makecode->MakeCodeImg($url, false, '', $imageurl, '', '', $ap_arr);
        }
    }

    /**
     * 非线上渠道生成二维码
     *
     * @param int $id channelId
     * @param bool $isdown
     * @param string $filePath
     * @param string $mName 活动名称
     * @param string $batchChannelId
     * @return boolean
     */
    public function Code($id = '', $isdown = '', $filePath = '', $mName = '', 
        $batchChannelId = '') {
        if (empty($id))
            return false;
        $model = M('tchannel');
        $query_arr = $model->where(array(
            'id' => $id))->find();
        if (! $query_arr)
            return false;
        ! empty($query_arr['qr_size']) ? $size = $query_arr['qr_size'] : $size = '';
        ! empty($query_arr['qr_color']) ? $color = $query_arr['qr_color'] : $color = '';
        // $logourl = $this->codeimagepreg($query_arr['logo_img']);
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
        }
        
        import('@.Vendor.MakeCode') or die('include file fail.');
        $makecode = new MakeCode();
        if ($isdown == '1') {
            $fileName = $mName . '-' . $query_arr['name'] . '-' . $batchChannelId;
            $this->fileName = $fileName;
            if ($filePath) {
                $fileName = mb_convert_encoding($fileName, "GBK", "UTF-8");
            }
            $saveandprint = $filePath ? false : true; // 如果有“$filePath”，表示不需要打印输出
            $fileName = $filePath ? $filePath . $fileName : $fileName;
            $makecode->MakeCodeImg($url, true, $size, '', $fileName, $color, 
                $ap_arr, $saveandprint);
        } else {
            $makecode->MakeCodeImg($url, false, $size = '', '', '', $color, 
                $ap_arr);
        }
    }
    
    // //二维码压图兼容问题
    // private function codeimagepreg($logo){
    // //正则判断兼容之前数据
    // if(preg_match("/$this->nodeId/",$logo)){
    // $logourl = get_upload_url($logo);
    // }else{
    // $logourl = './Home/Upload/channel/'.$logo;
    // }
    // return $logourl;
    // }
}