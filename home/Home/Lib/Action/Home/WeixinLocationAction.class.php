<?php

class WeixinLocationAction extends BaseAction {

    public $uploadPath;

    public function _initialize() {
        parent::_initialize();
        $this->uploadPath = './Home/Upload/Weixin/'; // 设置附件上传目录
    }
    
    // 地理位置回复配置
    public function index() {
        $where = "node_id='" . $this->nodeId . "' ";
        $dao = M('tweixin_info');
        $locationInfo = array();
        $locationData = $dao->where($where)->find();
        $pos_count = M('tpos_info')->where($where)->count();
        
        $id = $locationData['id'];
        $locationInfo = json_decode($locationData['setting'], true);
        $locationInfo = $locationInfo['location'];
        
        if ($locationInfo['large_image']) {
            $locationInfo['large_image_url'] = APP_PATH .
                 '/Upload/Weixin/location/' . $locationInfo['large_image'];
        }
        if ($locationInfo['small_image']) {
            $locationInfo['small_image_url'] = APP_PATH .
                 '/Upload/Weixin/location/' . $locationInfo['small_image'];
        }
        
        $this->assign('locationInfo', $locationInfo); // 地理位置配置信息
        $this->assign('list', '1');
        $this->assign('id', $id);
        $this->assign('pos_count', $pos_count); // 门店总数
        $this->display();
    }
    
    // 地理位置回复配置提交
    public function Submit() {
        $id = I('id');
        if (! $id) {
            $this->error("记录异常！");
        }
        $resp_count = I('resp_count', '3');
        $large_image = I('large_image_name');
        $small_image = I('small_image_name');
        $location_flag = I('location_flag');
        
        $new_large_image = $this->nodeId . 'top.' .
             $this->get_extension($large_image);
        $new_small_image = $this->nodeId . 'item.' .
             $this->get_extension($small_image);
        
        $where = "node_id='" . $this->nodeId . "' and id = '" . $id . "' ";
        $dao = M('tweixin_info');
        $data_arr = array(
            'location' => array(
                'location_flag' => $location_flag, 
                'resp_count' => $resp_count));
        // 'large_image'=>basename($new_large_image),
        // 'small_image'=>basename($new_small_image)
        
        // 开启事务
        M()->startTrans();
        // 移动图片
        if (strpos($large_image, "thumb_") !== false) {
            $flag = $this->move_image($large_image, $new_large_image);
            if (! ($flag === true)) {
                M()->rollback();
                $this->error($flag);
            }
            $data_arr['location']['large_image'] = basename($new_large_image);
        } else {
            $data_arr['location']['large_image'] = basename($large_image);
        }
        if (strpos($small_image, "thumb_") !== false) {
            $flag = $this->move_image($small_image, $new_small_image);
            if (! ($flag === true)) {
                M()->rollback();
                $this->error($flag);
            }
            $data_arr['location']['small_image'] = basename($new_small_image);
        } else {
            $data_arr['location']['small_image'] = basename($small_image);
        }
        
        $location_data = array(
            'setting' => json_encode($data_arr));
        $query = $dao->where($where)->save($location_data);
        if ($query === false) {
            M()->rollback();
            $this->error("保存失败");
        }
        M()->commit();
        $message = array(
            'respId' => 0, 
            'respStr' => '保存成功', 
            'id' => $query);
        $this->success($message);
    }
    
    // 移动图片 Upload/img_tmp->Upload/Weixin/node_id
    private function move_image($image_name, $new_name) {
        if (! $image_name) {
            return "需上传图片";
        }
        if (! is_dir(APP_PATH . '/Upload/Weixin/location/')) {
            mkdir(APP_PATH . '/Upload/Weixin/location/', 0777);
        }
        $old_image_url = APP_PATH . '/Upload/img_tmp/' . $this->node_id . '/' .
             basename($image_name);
        $new_image_url = APP_PATH . '/Upload/Weixin/location/' .
             basename($new_name);
        $flag = rename($old_image_url, $new_image_url);
        if ($flag) {
            return true;
        } else {
            return "图片路径非法" . $old_image_url . "==" . $new_image_url;
        }
    }
    // 获取文件的后缀名
    private function get_extension($file) {
        return end(explode('.', $file));
    }
}