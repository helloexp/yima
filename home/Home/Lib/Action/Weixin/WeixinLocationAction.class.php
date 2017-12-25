<?php

class WeixinLocationAction extends BaseAction {

    public $uploadPath;

    public function _initialize() {
        parent::_initialize();
        $this->uploadPath = './Home/Upload/Weixin/'; // 设置附件上传目录
    }

    /**
     * 微信智能导航
     */
    public function index() {
        $tweixinInfo  = M('tweixin_info');
        $locationInfo = [];

        $where = "node_id='" . $this->nodeId . "' ";
        $data  = $tweixinInfo-> where( $where )-> find();

        $json  = json_decode( $data['setting'], true );
        $locationInfo = $json['location'];

        if ( $locationInfo['large_image'] ) $locationInfo['large_image_url'] = $this-> _getUploadUrl( $locationInfo['large_image'] );
        if ( $locationInfo['small_image'] ) $locationInfo['small_image_url'] = $this-> _getUploadUrl( $locationInfo['small_image'] );
        $locationInfo['wxGpsFlagType'] =  empty( $locationInfo['wxGpsFlagType'] ) ? 2 : $locationInfo['wxGpsFlagType'];

        $stores = M('tstore_info')
            -> field( "`id`,`store_name`,`province`,`city`,`town`,`store_id`" )
            -> where("`node_id`={$this->nodeId} AND `type` <> 3 AND `type`<> 4 AND `wx_gps_flag`=1 ")
            -> order( '`id` DESC' )
            -> select();



        /**
         * 原始代码屏蔽
         * $pos_count = M('tpos_info')->where($where)->count();
         *
         * $this->assign('list', '1');
         * $this->assign('id', $id);
         * $this->assign('pos_count', $pos_count); // 门店总数
         * $this->assign('account_type', $locationData['account_type']);
         *
         */

        $this-> assign( 'stores', $stores ); //门店数据
        $this-> assign( 'locationInfo', $locationInfo );
        $this-> display();
    }

    /**
     * 微信智能导航保存
     */
    public function Submit() {
        /*
         * $id = I('id'); if(!$id){ $this->error("记录异常！"); }
         */
        $resp_count    = I('resp_count', '3');
        $large_image   = I('large_image_name');
        $small_image   = I('small_image_name');
        $location_flag = I('location_flag');
        $chooseType    = I('chooseType');
        $storesArr     = implode( ',', I('storeCheckStatus'));
        // $where = "node_id='".$this->nodeId.
        // "' and id = '".$id."' ";
        
        $where = "node_id='" . $this->nodeId . "'";
        
        $dao = M('tweixin_info');
        $data_arr = [
            'location' => [
                'location_flag'=> $location_flag,
                'resp_count'   => $resp_count,
                'large_image'  => $large_image,
                'small_image'  => $small_image,
                'wxGpsFlagType'=> $chooseType == 1 ? 2 : 1
            ]
        ];
        
        // 开启事务
        M()->startTrans();

        $resultSet = M('tweixin_info')
            ->where( ['node_id'=> $this->nodeId] )
            ->field("setting")
            ->find();

        $locaiton_arr  = $data_arr['location'];
        $location_data = array(
            'setting' => $this->_setJson($resultSet['setting'], $locaiton_arr));
        $query = $dao->where($where)->save($location_data);
        
        if ($query === false) {
            M()->rollback();
            $this->error("保存失败");
        }
        if( $chooseType == 0 )//选择全部门店
        {
            $query = M('tstore_info')
                -> where( "`node_id`={$this->nodeId} AND `type` <> 3 AND `type`<> 4" )
                -> save([ 'wx_gps_flag'=>1 ]);
            if ( $query === false )
            {
                M()->rollback();
                $this->error("保存失败");
            }
        }
        else //选择部分门店
        {
            $query = M('tstore_info')
                -> where( "`node_id`={$this->nodeId} AND `type` <> 3 AND `type`<> 4" )
                -> save([ 'wx_gps_flag'=>0 ]);

            if ( $query === false )
            {
                M()->rollback();
                $this->error("还原门店数据失败");
            }
            if( !empty( $storesArr ))
            {
                $query = M('tstore_info')
                    -> where( "`store_id` IN ({$storesArr}) " )
                    -> save([ 'wx_gps_flag'=>1 ]);

                if ( $query === false )
                {
                    M()->rollback();
                    $this->error("保存门店失败");
                }
            }

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
        $nodeIn      = $this-> nodeIn();
        $storesModel = D('Stores');

        if ( IS_POST )
        {
            $areaType  = I('post.city_type');
            $query_arr = (array) $storesModel-> areaFilter( $nodeIn, $areaType, $where );
            $this-> ajaxReturn( $query_arr, "查询成功", 0 );
            exit();
        }

        $result = M('tweixin_info')
            ->where( ['node_id'=> $this->nodeId] )
            ->field("setting")
            ->find();

        $setting  = json_decode( $result['setting'], true );
        $location = $setting['location'];
        $action = '';
        if( $location['wxGpsFlagType'] == 1 )
        {
            $action = 'allShow';
        }

        $getAllStores = $storesModel-> getAllStore( $nodeIn );

        $noNavigation = [];
        $Navigation = [];

        foreach ($getAllStores as $k => $v)
        {
            if ($v['wx_gps_flag'] != 1)
            {
                $noNavigation[] = $v;
            }
            else
            {
                $Navigation[] = $v;
            }
        }
        // 获取分组
        $storeGroup = $this-> getStoreGroup( $action );

        if( $action == 'allShow' )
        {
            $data = $Navigation;
        }
        else
        {
            $data = $noNavigation;
        }

        $this->assign('storeGroup', $storeGroup);
        $this->assign('allStores', $data); // 开启导航
        $this->display();
    }

    /**
     * 获取分组
     *
     * @param $where mixed 获取分组的额外条件
     * @return mixed
     */
    public function getStoreGroup( $action ) {
        $nodeId           = $this->node_id;
        $storesModel      = D('Stores');
        $storesGroupModel = D('StoresGroup');

        // 获取所有分组
        if( $action == 'allShow' )
            $allGroup = $storesGroupModel-> getPopGroupStoreId( $nodeId, '1' );
        else
            $allGroup = $storesGroupModel-> getPopGroupStoreId( $nodeId, '(c.wx_gps_flag !=1 or c.wx_gps_flag is null)' );

        // 未分组的门店
        if( $action == 'allShow' )
            $noGroup  = $storesModel-> getUnGroupedAllStore( $nodeId );
        else
            $noGroup  = $storesModel-> getUnGroupedAllStore( $nodeId, '(a.wx_gps_flag !=1 or a.wx_gps_flag is null)' );

        $search    = [];
        $storeid   = [];
        $storename = [];
        foreach ( $noGroup as $v )
        {
            $search[]    = $v['province'].$v['city'].$v['town'].$v['store_name'];
            $storeid[]   = $v['store_id'];
            $storename[] = $v['store_name'];
        }

        // 追加未分组项
        array_unshift( $allGroup,
            [
                'id'         => '-1',
                'group_name' => '未被分组', 
                'num'        => count($noGroup),
                'storeid'    => implode( ',', $storeid ),
                'storename'  => implode( ',', $storename ),
                'search'     => implode( ',', $search ),
            ]
        );

        return array_filter($allGroup);
    }
    
    // 批量地理位置门店导航
    public function changeStatus() {
        // 更新地理位置导航
        $tranDb = new Model();
        $tranDb->startTrans();
        $flag = M()->table("tweixin_info")->where(
            array(
                "node_id" => $this->nodeId))->getField('wx_stroe_flag');
        if ('0' == $flag) {
            $openFlag = M()->table("tweixin_info")->where(
                array(
                    "node_id" => $this->nodeId))->save(
                array(
                    'wx_stroe_flag' => 1));
            if (!$openFlag) {
                $tranDb->rollback();
                $this->error("开启门店导航失败");
                Log_wright("Weixin open store :" . M()->_sql());
                exit();
            }
        }

        $store_id = I('store_id', '');
        
        if ($store_id) {
            $store_id = explode(',', $store_id);
            
            // 批量创建
            foreach ($store_id as $k => $v) {
                $result = M()->table("tstore_info")->where(
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
                $this->ajaxReturn(array('info'=>'开启成功'),'JSON');
            } else {
                $this->ajaxReturn(array('info'=>'关闭成功'),'JSON');
            }
        }
    }
}