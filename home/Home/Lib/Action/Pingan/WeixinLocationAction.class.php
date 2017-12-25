<?php

class WeixinLocationAction extends BaseAction {

    public $uploadPath;
	
	public $_authAccessMap = '*';

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
            $locationInfo['large_image_url'] = $this->_getUploadUrl(
                $locationInfo['large_image']);
        }
        if ($locationInfo['small_image']) {
            $locationInfo['small_image_url'] = $this->_getUploadUrl(
                $locationInfo['small_image']);
        }
        
        $this->assign('locationInfo', $locationInfo); // 地理位置配置信息
        $this->assign('list', '1');
        $this->assign('id', $id);
        $this->assign('pos_count', $pos_count); // 门店总数
        $this->assign('account_type', $locationData['account_type']);
        $this->display();
    }
    
    // 地理位置回复配置提交
    public function Submit() {
        /*
         * $id = I('id'); if(!$id){ $this->error("记录异常！"); }
         */
        $resp_count = I('resp_count', '3');
        $large_image = I('large_image_name');
        $small_image = I('small_image_name');
        $location_flag = I('location_flag');
        
        // $where = "node_id='".$this->nodeId.
        // "' and id = '".$id."' ";
        
        $where = "node_id='" . $this->nodeId . "'";
        
        $dao = M('tweixin_info');
        $data_arr = array(
            'location' => array(
                'location_flag' => $location_flag, 
                'resp_count' => $resp_count, 
                'large_image' => $large_image, 
                'small_image' => $small_image));
        
        // 开启事务
        M()->startTrans();
        
        $resultSet = M('tweixin_info')->where("node_id='" . $this->nodeId . "'")
            ->field("setting")
            ->find();
        $locaiton_arr = $data_arr['location'];
        $location_data = array(
            'setting' => $this->_setJson($resultSet['setting'], $locaiton_arr));
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
    
    // 获取文件的后缀名
    private function get_extension($file) {
        return end(explode('.', $file));
    }
    
    // 获取图片路径
    protected function _getUploadUrl($imgname) {
        // 旧版
        if (basename($imgname) == $imgname) {
            return $this->uploadPath . '/location/' . $imgname;
        } else {
            return get_upload_url($imgname);
        }
    }
    
    // 移动图片 Upload/img_tmp->Upload/Weixin/node_id
    private function move_image($image_name, $new_name) {
        return;
        if (! $image_name) {
            return "需上传图片";
        }
        if (! is_dir(APP_PATH . '/Upload/Weixin/location/')) {
            mkdir(APP_PATH . '/Upload/Weixin/location/', 0777);
        }
        $old_image_url = C('UPLOAD') . $image_name;
        $new_image_url = APP_PATH . '/Upload/Weixin/location/' .
             basename($new_name);
        $flag = copy($old_image_url, $new_image_url);
        if ($flag) {
            return true;
        } else {
            return "图片路径非法" . $old_image_url . "==" . $new_image_url;
        }
    }

    /**
     *
     * @return StoresModel
     */
    private function getStoresModel() {
        if (empty($this->storesModel)) {
            $this->storesModel = D('Stores');
        }
        return $this->storesModel;
    }

    /**
     *
     * @return StoresGroupModel
     */
    private function getStoresGroupModel() {
        if (empty($this->storesGroupModel)) {
            $this->storesGroupModel = D('StoresGroup');
        }
        return $this->storesGroupModel;
    }
    
    // 门店导航
    public function loadStoreAdd() {
        
        /* 门店 */
        $nodeIn = $this->nodeIn();
        $storesModel = $this->getStoresModel();
        
        if (IS_POST) {
            $where = "(a.wx_gps_flag <>1 or a.wx_gps_flag is null)";
            $areaType = I('post.city_type');
            $query_arr = (array) $storesModel->areaFilter($nodeIn, $areaType, 
                $where);
            $this->ajaxReturn($query_arr, "查询成功", 0);
            exit();
        }
        
        $getAllStores = $storesModel->getAllStore($nodeIn);
        
        $noNavigation = array();
        $navigation = array();
        $nogps = array();
        $navigationFlag = array();
        $flag = 0;
        
        foreach ($getAllStores as $k => $v) {
            
            if ($v['gps_flag'] == '0') {
                if ($v['lbs_x'] == 0 && $v['lbs_y'] == 0) {
                    $nogps[] = $v;
                } else {
                    $noNavigation[] = $v;
                }
            }
            if ($v['wx_gps_flag'] != 1) {
                $navigation[] = $v;
            }
            if ($v['gps_flag'] != '1') {
                $navigationFlag[] = $v;
                $flag ++;
            }
        }
        // 获取分组
        $storeGroup = $this->getStoreGroup();
        $this->assign('storeGroup', $storeGroup);
        
        $this->assign('allStores', $navigation); // 开启导航
        $this->assign('nogps', $nogps); // 无导航坐标需配置
        $this->assign('noNavigation', $noNavigation); // 未开启导航
        $this->assign('navigationFlag', $navigationFlag); // 开启导航
        $this->assign('flag', $flag); // 开启导航数量
        $this->display();
    }

    /**
     * 获取分组
     *
     * @param $where mixed 获取分组的额外条件
     * @return mixed
     */
    public function getStoreGroup() {
        $nodeId = $this->node_id;
        $storesModel = $this->getStoresModel();
        $storesGroupModel = $this->getStoresGroupModel();
        
        // 获取所有分组
        $allGroup = $storesGroupModel->getPopGroupStoreId($nodeId, 
            '(c.wx_gps_flag !=1 or c.wx_gps_flag is null)');
        
        // 未分组的门店
        $noGroup = $storesModel->getUnGroupedAllStore($nodeId, 
            '(a.wx_gps_flag !=1 or a.wx_gps_flag is null)');
        $noGroupArr = array();
        foreach ($noGroup as $key => $value) {
            $noGroupArr[] = $value['store_id'];
        }
        // 追加未分组项
        array_unshift($allGroup, 
            array(
                'id' => '-1', 
                'group_name' => '未被分组', 
                'num' => count($noGroupArr), 
                'storeId' => implode(',', $noGroupArr)));
        
        return array_filter($allGroup);
    }
    
    // 批量地理位置门店导航
    public function changeStatus() {
        // 更新地理位置导航
        $tranDb = new Model();
        $tranDb->startTrans();
        $flag = M()->table("tweixin_info w")->where(
            array(
                "node_id" => $this->nodeId))->getField('wx_gps_flag');
        if ('0' == $flag) {
            $openFlag = M()->table("tweixin_info w")->where(
                array(
                    "node_id" => $this->nodeId))->save(
                array(
                    'wx_gps_flag' => 1));
        }
        
        if ($openFlag) {
            $tranDb->rollback();
            $this->error("开启门店导航失败");
            Log_wright("Weixin open store :" . M()->_sql());
            exit();
        }
        
        $store_id = I('store_id', '');
        
        if ($store_id) {
            $store_id = explode(',', $store_id);
            
            // 批量创建
            foreach ($store_id as $k => $v) {
                $result = M()->table("tstore_info si")->where(
                    array(
                        'store_id' => $v))->save(
                    array(
                        'wx_gps_flag' => 1));
                if (! $result) {
                    $tranDb->rollback();
                    $this->error("门店导航失败");
                    Log_wright("Weixin store save SQL:" . M()->_sql());
                    exit();
                }
            }
            $tranDb->commit();
            $this->ajaxReturn(1, "添加成功", 1);
        }
    }
    
    // 关闭地理位置智能导航
    public function flagLocation() {
        $location_flag = I('flag', 0);
        
        $resp_count = I('resp_count', '3');
        $large_image = I('large_image_name');
        $small_image = I('small_image_name');
        
        $where = "node_id='" . $this->nodeId . "'";
        $dao = M('tweixin_info');
        $data_arr = array(
            'location' => array(
                'location_flag' => $location_flag, 
                'resp_count' => $resp_count, 
                'large_image' => $large_image, 
                'small_image' => $small_image));
        
        $resultSet = M('tweixin_info')->where("node_id='" . $this->nodeId . "'")
            ->field("setting")
            ->find();
        $locaiton_arr = $data_arr['location'];
        $location_data = array(
            'setting' => $this->_setJson($resultSet['setting'], $locaiton_arr));
        $query = $dao->where($where)->save($location_data);
        
        if ($query) {
            if (1 == $location_flag) {
                $this->ajaxReturn(1, '开启成功', 1);
            } else {
                $this->ajaxReturn(0, '关闭成功', 1);
            }
        }
    }
}