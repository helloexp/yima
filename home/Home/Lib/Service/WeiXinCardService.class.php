<?php
// 微信卡券接口
class WeiXinCardService {

    public $appId;

    public $appSecret;

    public $accessToken;

    public $error = '';
    // 初始化
    public function init($appId, $appSecret, $accessToken = '') {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->accessToken = $accessToken;
    }

    public function init_by_node_id($node_id) {
        $wx_node_info = M()->table('tweixin_info')
            ->where("node_id = '" . $node_id . "'")
            ->find();
        if ($wx_node_info) {
            $this->appId = $wx_node_info['app_id'];
            $this->appSecret = $wx_node_info['app_secret'];
            $this->accessToken = $wx_node_info['app_access_token'];
        }
    }

    public function setToken($accessToken) {
        $this->accessToken = $accessToken;
        $wx_node_info['app_access_token'] = $accessToken;
        $rs = M()->table('tweixin_info')
            ->where("app_id = '" . $this->appId . "'")
            ->save($wx_node_info);
        if (! $rs) {
            log_write('save tweixin_info setToken ' . M()->_sql(), 'error');
        }
    }
    // 获取token
    public function getToken() {
        // 判断是否授权模式
        $wx_grant = D('WeiXinGrant', 'Service');
        $wx_grant->init();
        $result = $wx_grant->refresh_weixin_token_by_appid($this->appId);
        if ($result !== false) {
            $this->setToken($result);
            return array(
                'errcode' => 0, 
                'access_token' => $result);
        }
        
        $apiUrl = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' .
             $this->appId . '&secret=' . $this->appSecret . '';
        $error = '';
        for ($i = 0; $i < 10; $i ++) {
            $result = httpPost($apiUrl, '', $error,['TIMEOUT' => 30]);
            if ($result) {
                break;
            } else {
                log_write($error, 'error');
            }
            usleep(500 * 1000);
        }
        // $result =
        // '{"access_token":"VRrMAUaH3KOPFfJYM6g9ZiSLgBTTuqjiXdkx3qrxKz6bduGlbZ3f7usQkApFhASXSyzbCHliQp80lvoo6y4FKrL_MGbHvtEn7vJNj3Li6uzhwM1aehTQWl-ZpA83MAAl3-3Frr-PPjQR9dvyA55xHQ","expires_in":7200}'
        // ;-- 调试用
        $result = json_decode($result, true);
        $accessToken = $result['access_token'];
        $this->setToken($accessToken);
        return $result;
    }
    // 发送报文
    public function send($api_uri, $data_arr) {
        $accessToken = $this->accessToken;
        if (strpos($api_uri, '?') === false)
            $api_url = $api_uri . '?access_token=' . $accessToken;
        else
            $api_url = $api_uri . '&access_token=' . $accessToken;
        $post_data = json_encode($data_arr);
        $post_data = $this->unicodeDecode($post_data);
        log_write($api_url, 'wxcard');
        log_write($post_data, 'wxcard');
        // 判断网络
        $error = '';
        for ($i = 0; $i < 10; $i ++) {
            $result_str = httpPost($api_url, $post_data, $error, 
                array(
                    'TIMEOUT' => 30));
            if ($result_str) {
                break;
            } else {
                log_write($error, 'error');
            }
            usleep(500 * 1000);
        }
        
        log_write($result_str, 'wxcard');
        $result = json_decode($result_str, true);
        
        if ($result['errcode'] == '40001' || $result['errcode'] == '42001' ||
             $result['errcode'] == '41001') {
            $this->getToken();
            // $api_url = $api_uri.'?access_token='.$this->accessToken;
            if (strpos($api_uri, '?') === false)
                $api_url = $api_uri . '?access_token=' . $this->accessToken;
            else
                $api_url = $api_uri . '&access_token=' . $this->accessToken;
                // 判断网络
            $error = '';
            for ($i = 0; $i < 3; $i ++) {
                $result_str = httpPost($api_url, $post_data, $error, 
                    array(
                        'TIMEOUT' => 30));
                if ($result_str) {
                    break;
                } else {
                    log_write($error, 'error');
                }
                usleep(500 * 1000);
            }
            
            log_write($result_str, 'wxcard');
            $result = json_decode($result_str, true);
        }
        $this->error = "[" . $result['errcode'] . "]" . $result['errmsg'];
        return $result;
    }
    // 获取JSAPI ticket
    public function get_jsapi_ticket($weixin_info) {
        $api_url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=wx_card';
        // 获取本地表ticket 如果超时则重新获取
        if (($weixin_info['jsapi_ticket'] == null) ||
             ((time() > $weixin_info['jsapi_ticket_get_time']))) {
            log_write($api_url, 'wxcard');
            $data_arr = array();
            $result = $this->send($api_url, $data_arr);
            if ($result['errcode'] == '0') {
                $weixin_info_update['jsapi_ticket'] = $result['ticket'];
                $weixin_info_update['jsapi_ticket_get_time'] = time() + 3600;
                $rs = M()->table('tweixin_info')
                    ->where("app_id = '" . $this->appId . "'")
                    ->save($weixin_info_update);
                if ($rs === false) {
                    log_write(
                        'save tweixin_info get_jsapi_ticket ' . M()->_sql(), 
                        'error');
                    return false;
                } else
                    return $weixin_info_update['jsapi_ticket'];
            } else
                return false;
        } else {
            return $weixin_info['jsapi_ticket'];
        }
    }

    /**
     * 把辅助码存到微信卡券上去
     *
     * @param $card_id
     * @param $codeList
     * @return mixed
     */
    private function save_code_to_weixin($card_id, $codeList) {
        $api_url = 'http://api.weixin.qq.com/card/code/deposit';
        $data_arr['card_id'] = $card_id;
        $data_arr['code'] = $codeList;
        
        log_write($api_url, 'wxcard');
        $result = $this->send($api_url, $data_arr);
        if (isset($result['errcode']) && $result['errcode'] == 0) {
            return true;
        }
        return false;
    }

    /**
     * 修改微信公众号卡券库存
     *
     * @param $card_id
     * @param $count
     * @return mixed
     */
    public function modify_stock($card_id, $count) {
        $api_url = 'https://api.weixin.qq.com/card/modifystock';
        $data_arr['card_id'] = $card_id;
        if ($count > 0) {
            $data_arr['increase_stock_value'] = $count;
        } else {
            $data_arr['reduce_stock_value'] = abs($count);
        }
        log_write($api_url, 'wxcard');
        $result = $this->send($api_url, $data_arr);
        if (isset($result['errcode']) && $result['errcode'] == 0) {
            return true;
        }
        return false;
    }

    /**
     * 创建投放二维码-单个
     *
     * @param $card_id
     * @param $code
     * @param $outerId
     * @return mixed
     */
    public function create_wxcard_qrcode($cardId, $code, $outerId=0) {
        $api_url = 'https://api.weixin.qq.com/card/qrcode/create';
        $data_arr['action_name'] = 'QR_CARD';
        $data_arr['action_info']['card']['card_id'] = $cardId;
        $data_arr['action_info']['card']['code'] = $code;
        $data_arr['action_info']['card']['outer_id'] = $outerId;

        $result = $this->send($api_url, $data_arr);
        if (isset($result['errcode']) && $result['errcode'] == 0) {
            return $result['url'];
        }
        return false;
    }

    //支付时推送卡券接口
    public function paygift($reqdata){
             $api_url = 'https://api.weixin.qq.com/card/paygiftcard/set';
             foreach($reqdata  as $k=>$v){
                 $data_arr['card_rule_list'][$k]['card_id'] = $v['card_id'];
                 //$data_arr['card_rule_list']['mch_id']= $code;
                 $data_arr['card_rule_list'][$k]['min_cost'] = $v['min_cost'];
                 $data_arr['card_rule_list'][$k]['max_cost'] = $v['max_cost'];
                 $data_arr['card_rule_list'][$k]['begin_time'] = $v['begin_time'];
                 $data_arr['card_rule_list'][$k]['end_time'] = $v['end_time'];
             }
             $data_arr['is_open']=true;
             $result = $this->send($api_url, $data_arr);
             if (isset($result['errcode']) && $result['errcode'] == 0) {
                    return true;
             }
             return false;
    }

    /**
     * 创建投放二维码-多个
     *
     * @param $card_id
     * @param $codeArr
     * @param $outerId
     * @return mixed
     */
    public function create_wxcard_qrcode_multiple($cardId, $codeArr, $outerId=0) {
        $api_url = 'https://api.weixin.qq.com/card/qrcode/create';
        $data_arr['action_name'] = 'QR_MULTIPLE_CARD';
        $cardInfo['card_id'] = $cardId;
        $cardInfo['is_unique_code'] = true;
        $cardInfo['outer_id'] = $outerId;
        foreach ($codeArr as $code) {
            $cardInfo['code'] = $code;
            $data_arr['action_info']['multiple_card']['card_list'][] = $cardInfo;
        }
        $result = $this->send($api_url, $data_arr);
        if (isset($result['errcode']) && $result['errcode'] == 0) {
            return $result['url'];
        }
        return false;
    }
    
    // 获取颜色列表
    /*
     * { "errcode":0, "errmsg":"ok", "colors":[
     * {"name":"Color010","value":"#61ad40"},
     * {"name":"Color020","value":"#169d5c"},
     * {"name":"Color030","value":"#239cda"} ] }
     */
    public function getcolors() {
        $api_url = 'https://api.weixin.qq.com/card/create';
        log_write($api_url, 'wxcard');
        
        // $result = $this->send($api_url, $data_arr);
        $result_str = '{"errcode":0,"errmsg":"ok","colors":[{"name":"Color010","value":"#55bd47"},{"name":"Color020","value":"#10ad61"},{"name":"Color030","value":"#35a4de"},{"name":"Color040","value":"#3d78da"},{"name":"Color050","value":"#9058cb"},{"name":"Color060","value":"#de9c33"},{"name":"Color070","value":"#ebac16"},{"name":"Color080","value":"#f9861f"},{"name":"Color081","value":"#f08500"},{"name":"Color090","value":"#e75735"},{"name":"Color100","value":"#d54036"},{"name":"Color101","value":"#cf3e36"}]}';
        $result = json_decode($result_str, true);
        
        return $result;
    }
    // 创建卡券
    public function create($wx_card_type_id) {
        $api_url = 'https://api.weixin.qq.com/card/create';
        $wx_card_type_info = M()->table('twx_card_type')
            ->where("id = " . $wx_card_type_id)
            ->find();
        // 基础数据
        $base_info['logo_url'] = $wx_card_type_info['logo_url'];
        switch ($wx_card_type_info['code_type']) {
            case '1':
                $base_info['code_type'] = 'CODE_TYPE_TEXT';
                break;
            case '2':
                $base_info['code_type'] = 'CODE_TYPE_BARCODE';
                break;
            case '3':
                $base_info['code_type'] = 'CODE_TYPE_QRCODE';
                break;
            default:
                $base_info['code_type'] = 'CODE_TYPE_TEXT';
                break;
        }
        $base_info['brand_name'] = $wx_card_type_info['brand_name'];
        $base_info['title'] = $wx_card_type_info['title'];
        $base_info['sub_title'] = $wx_card_type_info['sub_title'];
        $base_info['color'] = $wx_card_type_info['color'];
        $base_info['notice'] = $wx_card_type_info['notice'];
        $base_info['service_phone'] = $wx_card_type_info['service_phone'];
        $base_info['source'] = $wx_card_type_info['source'];
        $base_info['description'] = $wx_card_type_info['description'];
        $base_info['use_limit'] = $wx_card_type_info['use_limit'];
        $base_info['get_limit'] = $wx_card_type_info['get_limit'];
        
        if ($wx_card_type_info['use_custom_code'] == '1')
            $base_info['use_custom_code'] = true;
        else
            $base_info['use_custom_code'] = false;
        
        if ($wx_card_type_info['bind_openid'] == '1')
            $base_info['bind_openid'] = true;
        else
            $base_info['bind_openid'] = false;
        
        if ($wx_card_type_info['can_share'] == '1')
            $base_info['can_share'] = true;
        else
            $base_info['can_share'] = false;
        
        if ($wx_card_type_info['can_give_friend'] == '1')
            $base_info['can_give_friend'] = true;
        else
            $base_info['can_give_friend'] = false;
            // 是否预存模式 需要设置get_custom_code_mode 并且库存设置为0
        if ($wx_card_type_info['store_mode'] == '2') {
            $base_info['get_custom_code_mode'] = 'GET_CUSTOM_CODE_MODE_DEPOSIT';
            $wx_card_type_info['quantity'] = 0;
        }
        
        $base_info['date_info']['type'] = intval(
            $wx_card_type_info['date_type']);
        $base_info['date_info']['begin_timestamp'] = intval(
            $wx_card_type_info['date_begin_timestamp']);
        $base_info['date_info']['end_timestamp'] = intval(
            $wx_card_type_info['date_end_timestamp']);
        $base_info['date_info']['fixed_term'] = intval(
            $wx_card_type_info['date_fixed_timestamp']);
        $base_info['date_info']['fixed_begin_term'] = intval(
            $wx_card_type_info['date_fixed_begin_timestamp']);
        $base_info['sku']['quantity'] = intval($wx_card_type_info['quantity']);
        $base_info['url_name_type'] = $wx_card_type_info['url_name_type'];
        $base_info['custom_url'] = $wx_card_type_info['custom_url'];
        // 取终端组号 和类型
        $goods_info = M()->table('tgoods_info')
            ->where("goods_id = '" . $wx_card_type_info['goods_id'] . "'")
            ->find();
        if (! $goods_info) {
            log_write(
                "find goods_info error" . $wx_card_type_info['goods_id'] .
                     M()->_sql());
            return false;
        }
        if ($goods_info['pos_group_type'] == '2') { // 普通终端组
            $store_id_arr = M()->table('tgroup_pos_relation')
                ->where("group_id = '" . $goods_info['pos_group'] . "'")
                ->field('store_id')
                ->select();
        } else { // 商户终端组
            $store_id_arr = M()->table('tstore_info')
                ->where("node_id = '" . $goods_info['node_id'] . "'")
                ->field('store_id')
                ->select();
        }
        if (count($store_id_arr) > 0) {
            // 取出门店数据
            $local_store_array = array();
            $store_id_save_arr = array();
            foreach ($store_id_arr as $store_id_tmp) {
                $store_id = $store_id_tmp['store_id'];
                $store_info = M()->table('tstore_info')
                    ->where("store_id = '" . $store_id . "'")
                    ->find();
                if ($store_info) {
                    $local_store_array[$store_id] = $store_info['wx_store_id'];
                    $store_id_save_arr[] = $store_id;
                }
            }
            
            $sync_store_arr = $this->store_sync($local_store_array, 
                $wx_card_type_info['store_type']);
            if ($sync_store_arr) {
                foreach ($sync_store_arr as $k => $v)
                    $base_info['location_id_list'][] = intval($v);
            }
            // 保存终端列表
            if (count($store_id_save_arr) > 0) {
                $store_id_list = implode(",", $store_id_save_arr);
                $save_tmp['store_id_list'] = $store_id_list;
                M()->table('twx_card_type')
                    ->where("id = " . $wx_card_type_id)
                    ->save($save_tmp);
            }
        }
        if ($wx_card_type_info['card_type'] == '0') { // 折扣券
            $wx_card_data['card']['card_type'] = 'DISCOUNT';
            $wx_card_data['card']['discount']['base_info'] = $base_info;
            $wx_card_data['card']['discount']['discount'] = $wx_card_type_info['discount'];
        } else if ($wx_card_type_info['card_type'] == '1') { // 代金券
            $wx_card_data['card']['card_type'] = 'CASH';
            $wx_card_data['card']['cash']['base_info'] = $base_info;
            $wx_card_data['card']['cash']['least_cost'] = $wx_card_type_info['least_cost'] *
                 100;
            $wx_card_data['card']['cash']['reduce_cost'] = $wx_card_type_info['reduce_cost'] *
                 100;
        } else if ($wx_card_type_info['card_type'] == '2') { // 实物券即提领券
            $wx_card_data['card']['card_type'] = 'GIFT';
            if ($goods_info['online_verify_flag'] == '1') {
                $base_info['custom_url_name'] = '线上领取';
                $base_info['custom_url'] = U('Label/Withdraw/wechatBridge', '', 
                    '', '', TRUE);
            }
            $wx_card_data['card']['gift']['base_info'] = $base_info;
            $wx_card_data['card']['gift']['gift'] = $wx_card_type_info['gift'];
        } else if ($wx_card_type_info['card_type'] == '3') { // 优惠券 对应微信通用券
            $wx_card_data['card']['card_type'] = 'GENERAL_COUPON';
            $wx_card_data['card']['general_coupon']['base_info'] = $base_info;
            $wx_card_data['card']['general_coupon']['default_detail'] = $wx_card_type_info['default_detail'];
        }
        $result = $this->send($api_url, $wx_card_data);
        if ($result['errcode'] == '0') {
            $wx_card_info['card_id'] = $result['card_id'];
            $rs = M()->table('twx_card_type')
                ->where("id = " . $wx_card_type_id)
                ->save($wx_card_info);
            if (! $rs) {
                log_write(
                    "更新卡券card_id错误" . $wx_card_type_id . $result['card_id']);
            }
            return true;
        }
        return false;
    }

    /**
     * 微信卡券朋友的券添加
     *
     * @param $id twx_card_type表id
     */
    public function friendCardAdd($id) {
        $api_url = 'https://api.weixin.qq.com/card/create';
        $wxCardInfo = M()->table('twx_card_type')
            ->where("id = " . $id)
            ->find();
        
        // 基础数据+++++++++++++++++++
        
        $baseInfo = array();
        $result = $this->uploadeImgToWx(C('UPLOAD') . $wxCardInfo['logo_url']); // 上传商户logo
        if (isset($result['errcode'])) {
            $this->error = '商户logo上传失败:' . $result['errmsg'];
            return false;
        }
        $baseInfo['logo_url'] = $result['url'];
        switch ($wxCardInfo['code_type']) {
            case '1':
                $baseInfo['code_type'] = 'CODE_TYPE_TEXT';
                break;
            case '2':
                $baseInfo['code_type'] = 'CODE_TYPE_BARCODE';
                break;
            case '3':
                $baseInfo['code_type'] = 'CODE_TYPE_QRCODE';
                break;
            default:
                $baseInfo['code_type'] = 'CODE_TYPE_TEXT';
                break;
        }
        $baseInfo['brand_name'] = $wxCardInfo['brand_name'];
        $baseInfo['color'] = $wxCardInfo['color'];
        $baseInfo['description'] = $wxCardInfo['description'];
        $baseInfo['date_info']['type'] = 'DATE_TYPE_FIX_TIME_RANGE';
        $baseInfo['date_info']['begin_timestamp'] = intval(
            $wxCardInfo['date_begin_timestamp']);
        $baseInfo['date_info']['end_timestamp'] = intval(
            $wxCardInfo['date_end_timestamp']);
        $baseInfo['can_share'] = false;
        $baseInfo['can_give_friend'] = false;
        // 门店数据数据
        $goodsInfo = M()->table('tgoods_info')
            ->where("goods_id = '" . $wxCardInfo['goods_id'] . "'")
            ->find();
        if (! $goodsInfo) {
            log_write(
                "find goods_info error" . $wxCardInfo['goods_id'] . M()->_sql());
            return false;
        }
        if ($goodsInfo['pos_group_type'] == '2') { // 普通终端组
            $storeIdArr = M()->table('tgroup_pos_relation')
                ->where("group_id = '" . $goodsInfo['pos_group'] . "'")
                ->field('store_id')
                ->select();
        } else { // 商户终端组
            $storeIdArr = M()->table('tstore_info')
                ->where("node_id = '" . $goodsInfo['node_id'] . "'")
                ->field('store_id')
                ->select();
        }
        if (count($storeIdArr) > 0) {
            $localStoreArray = array();
            $storeIdSaveArr = array();
            foreach ($storeIdArr as $v) {
                $storeInfo = M()->table('tstore_info')
                    ->where("store_id = '" . $v['store_id'] . "'")
                    ->find();
                if ($storeInfo) {
                    $localStoreArray[$v['store_id']] = $storeInfo['wx_store_id'];
                    $storeIdSaveArr[] = $v['store_id'];
                }
            }
            $syncStoreArr = $this->store_sync($localStoreArray,$wxCardInfo['store_type']);
            if ($syncStoreArr) {
                foreach ($syncStoreArr as $k => $v)
                    $baseInfo['location_id_list'][] = intval($v);
            }
            if (count($storeIdSaveArr) > 0) { // 保存终端列表
                $storeIdList = implode(",", $storeIdSaveArr);
                $saveTmp['store_id_list'] = $storeIdList;
                M()->table('twx_card_type')
                    ->where("id = " . $id)
                    ->save($saveTmp);
            }
        }
        
        $baseInfo['custom_url_name'] = $wxCardInfo['url_name_type'];
        $baseInfo['custom_url'] = $wxCardInfo['custom_url'];
        $baseInfo['custom_url_sub_title'] = $wxCardInfo['custom_url_sub_title'];
        
        // 高级数据+++++++++++++++++
        
        $advancedInfo = array();
        $advancedInfo['abstract']['abstract'] = $wxCardInfo['abstract'];
        $result = $this->uploadeImgToWx(
            C('UPLOAD') . $wxCardInfo['icon_url_list']); // 封面图片上传
        if (isset($result['errcode'])) {
            $this->error = '封面图片上传失败:' . $result['errmsg'];
            return false;
        }
        $advancedInfo['abstract']['icon_url_list'][] = $result['url'];
        // 图文介绍
        $imageTextArr = json_decode($wxCardInfo['text_image_list'], true);
        foreach ($imageTextArr as $k => $v) {
            // 上传图片
            $result = $this->uploadeImgToWx(C('UPLOAD') . $v['image']); // 封面图片上传
            if (isset($result['errcode'])) {
                $this->error = '图文介绍图片上传失败:' . $result['errmsg'];
                return false;
            }
            $advancedInfo['text_image_list'][$k]['image_url'] = $result['url'];
            $advancedInfo['text_image_list'][$k]['text'] = $v['content'];
        }
        // 可用时段
        $timeLimit = $wxCardInfo['time_limit'];
        if (! empty($timeLimit)) {
            $timeLimit = explode(',', $timeLimit);
            foreach ($timeLimit as $v) {
                $advancedInfo['time_limit'][]['type'] = $v;
            }
        }
        // 核销赠送处理
        if (! empty($wxCardInfo['consume_share_self_num'])) {
            $advancedInfo['consume_share_self_num'] = $wxCardInfo['consume_share_self_num'];
        }
        if (! empty($wxCardInfo['consume_share_card_list'])) {
            $advancedInfo['consume_share_card_list'][] = $wxCardInfo['consume_share_card_list'];
        }
        $advancedInfo['share_friends'] = true;
        
        $wxCardData = array();
        switch ($wxCardInfo['card_type']) {
            case '1': // 代金券
                $wxCardData['card']['card_type'] = 'CASH';
                $wxCardData['card']['cash']['base_info'] = $baseInfo;
                $wxCardData['card']['cash']['advanced_info'] = $advancedInfo;
                $wxCardData['card']['cash']['least_cost'] = 0;
                $wxCardData['card']['cash']['reduce_cost'] = $wxCardInfo['reduce_cost'] *
                     100;
                break;
            case '2': // 提领券
                $wxCardData['card']['card_type'] = 'GIFT';
                $wxCardData['card']['gift']['base_info'] = $baseInfo;
                $wxCardData['card']['gift']['advanced_info'] = $advancedInfo;
                $wxCardData['card']['gift']['gift_name'] = $wxCardInfo['title'];
                $wxCardData['card']['gift']['gift_num'] = intval($wxCardInfo['gift_num']);
                $wxCardData['card']['gift']['gift_unit'] = $wxCardInfo['gift_unit'];
                $wxCardData['card']['gift']['gift'] = $wxCardInfo['gift'];
                break;
            default:
                $this->error = '未知的卡券类型';
                return false;
        }
//         dump($wxCardData);
//         exit();
        // $post_data = json_encode($wxCardData);
        // dump($post_data);exit;
        $result = $this->send($api_url, $wxCardData);
        if ($result['errcode'] == '0') {
            $rs = M()->table('twx_card_type')
                ->where("id = " . $id)
                ->save(array(
                'card_id' => $result['card_id']));
            if (! $rs) {
                log_write("更新卡券card_id错误" . $id . $result['card_id']);
            }
            return $result['card_id'];
        }
        $this->error = "[{$result['errcode']}]" . $result['errmsg'];
        return false;
    }
    
    // 卡券审核通知处理
    public function card_type_audit($node_id, $card_id, $event) {
        log_write(
            'card_type_audit :' . $node_id . '|' . $card_id . '|' . $event, 
            'wxcard');
        
        if ($event == 'card_pass_check') {
            $wx_card_type_info['auth_flag'] = '2';
        } else if ($event == 'card_not_pass_check') {
            $wx_card_type_info['auth_flag'] = '3';
        }
		$tryCount = 0;
		$maxTryCount = 3;
		//遇到审核更新失败，怀疑是同步报文比更新数据库更先到，加上重试机制
		do{
			
			$info  =  M()->table('twx_card_type')
				->where("node_id = '" . $node_id . "' and card_id = '" . $card_id . "'")
				->find();
			if ($info) {
				break;
			}
			usleep(500000);//sleep 0.5s
			log_write("info not found update card_type_audit audit data error" . M()->_sql() );
			$tryCount++;
		}while($tryCount < $maxTryCount);
		if (empty($info)) {
			log_write("info not found update card_type_audit audit data error final" . M()->_sql() );
		}
		$rs = M()->table('twx_card_type')
		->where(
		"node_id = '" . $node_id . "' and card_id = '" . $card_id . "'")
		->save($wx_card_type_info);
		if ($rs === false) { //sql执行失败
			log_write("update error update card_type_audit audit data error" . M()->_sql() . ' db error:' . M()->getDbError());
		} else if ($rs === 0) {//没有影响(不需要更新)
			log_write("unneed update update card_type_audit audit data error" . M()->_sql() );
		}
    }
    
    // 拉取门店列表
    public function get_code_status($card_id, $assist_number) {
        $api_url = 'https://api.weixin.qq.com/card/code/get';
        $data_arr['code'] = $assist_number;
        $data_arr['card_id'] = $card_id;
        
        log_write($api_url, 'wxcard');
        $result = $this->send($api_url, $data_arr);
        return $result;
    }
    
    // 拉取门店列表
    public function store_batch_get() {
        $api_url = 'https://api.weixin.qq.com/card/location/batchget';
        $data_arr['offset'] = 0;
        $data_arr['count'] = 100;
        
        $store_list = array();
        
        log_write($api_url, 'wxcard');
        do {
            $result = $this->send($api_url, $data_arr);
            $store_list = array_merge_recursive($store_list, 
                $result['location_list']);
            $data_arr['offset'] += $result['count'];
        }
        while ($result['count'] >= $data_arr['count']);
        
        return $store_list;
    }
    // 创建门店
    public function store_batch_add($store_arr, $sync_store_list) {
        $api_url = 'https://api.weixin.qq.com/card/location/batchadd';
        
        log_write($api_url, 'wxcard');
        log_write(print_r($store_arr, true), 'wxcard');
        
        $result = $this->send($api_url, $store_arr);
        
        if ($result['errcode'] == 0) {
            $wx_store_id_list = $result['location_id_list'];
            $i = 0;
            foreach ($sync_store_list as $k => $v) {
                $sync_store_list[$k] = $wx_store_id_list[$i];
                $i ++;
            }
        } else {
            return null;
        }
        return $sync_store_list;
    }
    // 门店同步处理
    // $local_store_array store_id => weixin_location_id $store_type 门店类型
    public function store_sync($local_store_array, $store_type) {
        $new_store_array = array();
        $no_sync_store_list = array();
        // 拉取微信门店列表
        $wx_store_list = $this->store_batch_get();
        // 比对列表
        foreach ($local_store_array as $store_id => $weixin_location_id) {
            // 未同步过
            if (($weixin_location_id == null) || ($weixin_location_id == ""))
                $no_sync_store_list[$store_id] = "";
                // 检查同步过但未通过审核的
            $flag = false;
            foreach ($wx_store_list as $wx_store) {
                if ($weixin_location_id == $wx_store['id']) {
                    $flag = true;
                    $new_store_array[$store_id] = $wx_store['id'];
                    break;
                }
            }
            if ($flag === false) {
                $no_sync_store_list[$store_id] = "";
            }
        }
        $sync_store_list = array();
        foreach ($no_sync_store_list as $store_id => $weixin_location_id) {
            // 组成报文节点
            $where = "store_id = '" . $store_id . "'";
            $store_info = M()->table('tstore_info')
                ->where($where)
                ->find();
            if ($store_info) {
                $sync_store_list[$store_id] = "";
                $sync_sotre['business_name'] = $store_info['store_name'];
                $sync_sotre['province'] = $store_info['province'];
                $sync_sotre['city'] = $store_info['city'];
                $sync_sotre['district'] = $store_info['town'];
                $sync_sotre['address'] = $store_info['address'];
                $sync_sotre['telephone'] = $store_info['store_phone'];
                $sync_sotre['category'] = $store_type;
                if ($store_info['lbs_y'] > 0 && $store_info['lbs_x'] > 0) {
                    $tmp_point = array(
                        $store_info['lbs_y'], 
                        $store_info['lbs_x']);
                    $turn_point = $this->bd2gcj($tmp_point);
                    $store_info['lbs_y'] = $turn_point[0];
                    $store_info['lbs_x'] = $turn_point[1];
                }
                $sync_sotre['longitude'] = $store_info['lbs_y'];
                $sync_sotre['latitude'] = $store_info['lbs_x'];
                $sync_store_arr['location_list'][] = $sync_sotre;
            }
        }
        if (count($sync_store_arr['location_list']) > 0) {
            // 批量请求添加门店
            $sync_store_list = $this->store_batch_add($sync_store_arr,$sync_store_list);
            if (count($sync_store_list) > 0) {
                // 更新数据库
                foreach ($sync_store_list as $k => $v) {
                    $new_store_array[$k] = $v;
                    $store_info = array();
                    $store_info['wx_store_id'] = $v;
                    $rs = M()->table('tstore_info')
                        ->where("store_id = '" . $k . "'")
                        ->save($store_info);
                    if (! $rs) {
                        log_write("更新门店数据错误" . M()->_sql());
                        ;
                    }
                }
            }
        }
        log_write("new_store_array:" . print_r($new_store_array, true));
        // die;
        return $new_store_array;
    }
    
    // 获取微信卡券领取二维码
    public function create_wx_qrcode($card_id, $code,$is_unique_code='',$outer_id='') {
        $api_url = 'https://api.weixin.qq.com/card/qrcode/create';
        $data_arr['action_name'] = 'QR_CARD';
        $data_arr['action_info']['card']['card_id'] = $card_id;
        $data_arr['action_info']['card']['code'] = $code;
        if($is_unique_code==1)
            $data_arr['action_info']['card']['is_unique_code'] = true;
        if(!empty($outer_id))
        $data_arr['action_info']['card']['outer_id'] = $outer_id;
        log_write($api_url, 'wxcard');
        
        $result = $this->send($api_url, $data_arr);
        
        return $result;
    }
    
    // 新建辅助码 重复利用辅助码可能会有无法预测的问题，每次都新建，后期再考虑清除未使用数据
    public function create_white_user($user_name_arr) {
        $api_url = 'https://api.weixin.qq.com/card/testwhitelist/set';
        $data_arr['username'] = $user_name_arr;
        log_write($api_url, 'wxcard');
        
        $result = $this->send($api_url, $data_arr);
        
        return $result;
    }

    /**
     * 获取消息接口的卡券回复消息体
     *
     * @param $card_id
     * @param $outer_id  string   场景值
     */
    public function get_resp_card_ext($node_id, $card_id, $outer_id = 0) {
        // 查找appsercet;
        $weixin_info = M()->table('tweixin_info')
            ->where("node_id = '" . $node_id . "'")
            ->find();
        if (! $weixin_info) {
            log_write("cant find weixin_info" . M()->_sql());
            $this->error = '系统错误[02]';
            return false;
        }
        $jsapi_ticket = $this->get_jsapi_ticket($weixin_info);
        if ($jsapi_ticket === false) {
            log_write("cant get jsapi_ticket" . M()->_sql());
            $this->error = '系统错误[05]';
            return false;
        }
        $timestamp = time();
        $sign_arr = array(
            $jsapi_ticket, 
            $timestamp, 
            $card_id);
        sort($sign_arr, SORT_STRING);
        $sign_src = implode('', $sign_arr);
        log_write("sign_src " . $sign_src);
        $sign = sha1($sign_src);
        // $card_ext =
        // '{\"code\":\"'.$assist_number['assist_number'].'\",\"openid\":\"\",\"timestamp\":\"'.$timestamp.'\",\"signature\":\"'.$sign.'\"}';
        $card_ext = '{"code":"","openid":"","timestamp":"' . $timestamp .
             '","signature":"' . $sign . '","outer_id":"'.$outer_id.'"}';
        log_write("codeext " . $card_ext);
        return $card_ext;
    }
    // 新建辅助码
    public function add_assist_number($open_id, $batch_info_id) {
        log_write(__METHOD__ . ' create assist_number :' . $open_id, 'wxcard');
        
        $assist_number = M()->table('twx_assist_number')
            ->where(
            "open_id = '" . $open_id . "' and card_batch_id = " . $batch_info_id .
                 " and status = '1'")
            ->find();
        $wx_goods_info = M()->table('tbatch_info i')
            ->where("id = " . $batch_info_id)
            ->find();
        if (! $wx_goods_info) {
            log_write(__METHOD__ . " cant find tbatch_info" . M()->_sql());
            $this->error = '系统错误[01]';
            return false;
        }
        // 查找appsercet;
        $weixin_info = M()->table('tweixin_info')
            ->where("node_id = '" . $wx_goods_info['node_id'] . "'")
            ->find();
        if (! $weixin_info) {
            log_write(__METHOD__ . " cant find weixin_info" . M()->_sql());
            $this->error = '系统错误[02]';
            return false;
        }
        $jsapi_ticket = $this->get_jsapi_ticket($weixin_info);
        if ($jsapi_ticket === false) {
            log_write(__METHOD__ . " cant get jsapi_ticket" . M()->_sql());
            $this->error = '系统错误[05]';
            return false;
        }
        
        $timestamp = time();
        if (! $assist_number) {
            // 锁定库存
            // 开启事务
            M()->startTrans();
            $wx_goods_info = M()->table('tbatch_info i')
                ->lock(true)
                ->where("id = " . $batch_info_id)
                ->find();
            if ($wx_goods_info === false) {
                log_write(__METHOD__ . " cant find tbatch_info" . M()->_sql());
                M()->rollback();
                $this->error = '系统错误[01]';
                return false;
            }
            // 扣减
            if ($wx_goods_info['storage_num'] != - 1) { // 限制库存
                if ($wx_goods_info['remain_num'] < 1) {
                    log_write(
                        __METHOD__ . " storage_num less than send_num [" .
                             $wx_goods_info['storage_num'] . '][' .
                             ($wx_goods_info['remain_num']) . ']');
                    M()->rollback();
                    $this->error = '卡券已发完';
                    return false;
                }
            }
            log_write(__METHOD__ . ' 111' . $open_id, 'wxcard');
            $wx_goods_info_tmp = array();
            $wx_goods_info_tmp['remain_num'] = $wx_goods_info['remain_num'] - 1;
            $rs = M()->table('tbatch_info')
                ->where("id = " . $batch_info_id)
                ->save($wx_goods_info_tmp);
            if (! $rs) {
                log_write(
                    __METHOD__ . " update tbatch_info remain_num error" .
                         M()->_sql());
                M()->rollback();
                $this->error = '系统错误[03]';
                return false;
            }
            $i = 0;
            for ($i = 0; $i < 10; $i ++) {
                $assist_number['assist_number'] = C('CARD_ASSIST_PRE_NUMBER') . rand(1000, 9999) .
                     rand(1000, 9999) . rand(1000, 9999);
                $assist_number['status'] = '1';
                $assist_number['open_id'] = $open_id;
                $assist_number['node_id'] = $wx_goods_info['node_id'];
                $assist_number['batch_no'] = $wx_goods_info['batch_no'];
                $assist_number['card_batch_id'] = $batch_info_id;
                $assist_number['add_time'] = date('YmdHis');
                $rs = M()->table('twx_assist_number')->add($assist_number);
                if ($rs !== false) {
                    // 增加twx_card_type get_card_num 数量
                    if ($this->add_get_card_num($wx_goods_info['card_id']) ===
                         false) {
                        log_write(
                            __METHOD__ . " update tbatch_info remain_num error" .
                             M()->_sql());
                        M()->rollback();
                        $this->error = '系统错误[06]';
                        return false;
                    }
                    // commit;
                    M()->commit();
                    // 计算签名
                    $sign_arr = array(
                        $jsapi_ticket, 
                        $assist_number['assist_number'], 
                        $timestamp, 
                        $wx_goods_info['card_id']);
                    sort($sign_arr, SORT_STRING);
                    $sign_src = implode('', $sign_arr);
                    log_write(__METHOD__ . " sign_src " . $sign_src);
                    $sign = sha1($sign_src);
                    // $card_ext =
                    // '{\"code\":\"'.$assist_number['assist_number'].'\",\"openid\":\"\",\"timestamp\":\"'.$timestamp.'\",\"signature\":\"'.$sign.'\"}';
                    $card_ext = '{"code":"' . $assist_number['assist_number'] .
                         '","openid":"","timestamp":"' . $timestamp .
                         '","signature":"' . $sign . '"}';
                    log_write(__METHOD__ . " codeext " . $card_ext);
                    return $card_ext;
                }
            }
            // rollback;
            log_write(
                __METHOD__ . " insert into twx_assist_number error " .
                     M()->_sql());
            M()->rollback();
            $this->error = '系统错误[04]';
            return false;
        } else {
            // 计算签名
            $sign_arr = array(
                $jsapi_ticket, 
                $assist_number['assist_number'], 
                $timestamp, 
                $wx_goods_info['card_id']);
            sort($sign_arr, SORT_STRING);
            $sign_src = implode('', $sign_arr);
            log_write(__METHOD__ . " sign_src " . $sign_src);
            $sign = sha1($sign_src);
            $card_ext = '{"code":"' . $assist_number['assist_number'] .
                 '","openid":"","timestamp":"' . $timestamp . '","signature":"' .
                 $sign . '"}';
            log_write(__METHOD__ . " codeext " . $card_ext);
            return $card_ext;
        }
    }
    
    // 新建辅助码 内部抽奖接口使用，不扣减库存 旧的，可能已经废弃
    public function add_assist_number_nostore($open_id, $batch_info_id, 
        $data_from) {
        log_write('create assist_number :' . $open_id, 'wxcard');
        
        $assist_number = M()->table('twx_assist_number')
            ->where(
            "open_id = '" . $open_id . "' and card_batch_id = " . $batch_info_id .
                 " and status = '1'")
            ->find();
        $wx_goods_info = M()->table('tbatch_info i')
            ->where("id = " . $batch_info_id)
            ->find();
        if (! $wx_goods_info) {
            log_write("cant find tbatch_info" . M()->_sql());
            $this->error = '系统错误[01]';
            return false;
        }
        // 查找appsercet;
        $weixin_info = M()->table('tweixin_info')
            ->where("node_id = '" . $wx_goods_info['node_id'] . "'")
            ->find();
        if (! $weixin_info) {
            log_write("cant find weixin_info" . M()->_sql());
            $this->error = '系统错误[02]';
            return false;
        }
        $jsapi_ticket = $this->get_jsapi_ticket($weixin_info);
        if ($jsapi_ticket === false) {
            log_write("cant get jsapi_ticket" . M()->_sql());
            $this->error = '系统错误[05]';
            return false;
        }
        
        $timestamp = time();
        if (! $assist_number) {
            $i = 0;
            for ($i = 0; $i < 10; $i ++) {
                $assist_number['assist_number'] = C('CARD_ASSIST_PRE_NUMBER') . rand(1000, 9999) .
                     rand(1000, 9999) . rand(1000, 9999);
                $assist_number['status'] = '1';
                $assist_number['open_id'] = $open_id;
                $assist_number['node_id'] = $wx_goods_info['node_id'];
                $assist_number['batch_no'] = $wx_goods_info['batch_no'];
                $assist_number['card_batch_id'] = $batch_info_id;
                $assist_number['data_from'] = $data_from;
                $assist_number['add_time'] = date('YmdHis');
                $rs = M()->table('twx_assist_number')->add($assist_number);
                if ($rs !== false) {
                    // 增加twx_card_type get_card_num 数量
                    if ($this->add_get_card_num($wx_goods_info['card_id']) ===
                         false) {
                        log_write(
                            "update tbatch_info remain_num error" . M()->_sql());
                        $this->error = '系统错误[06]';
                        return false;
                    }
                    // 计算签名
                    $sign_arr = array(
                        $jsapi_ticket, 
                        $assist_number['assist_number'], 
                        $timestamp, 
                        $wx_goods_info['card_id']);
                    sort($sign_arr, SORT_STRING);
                    $sign_src = implode('', $sign_arr);
                    log_write("sign_src " . $sign_src);
                    $sign = sha1($sign_src);
                    // $card_ext =
                    // '{\"code\":\"'.$assist_number['assist_number'].'\",\"openid\":\"\",\"timestamp\":\"'.$timestamp.'\",\"signature\":\"'.$sign.'\"}';
                    $card_ext = '{"code":"' . $assist_number['assist_number'] .
                         '","openid":"","timestamp":"' . $timestamp .
                         '","signature":"' . $sign . '"}';
                    log_write("codeext " . $card_ext);
                    return array(
                        "card_ext" => $card_ext, 
                        "card_id" => $wx_goods_info['card_id']);
                }
            }
            // rollback;
            log_write("insert into twx_assist_number error " . M()->_sql());
            $this->error = '系统错误[04]';
            return false;
        } else {
            // 计算签名
            $sign_arr = array(
                $jsapi_ticket, 
                $assist_number['assist_number'], 
                $timestamp, 
                $wx_goods_info['card_id']);
            sort($sign_arr, SORT_STRING);
            $sign_src = implode('', $sign_arr);
            log_write("sign_src " . $sign_src);
            $sign = sha1($sign_src);
            $card_ext = '{"code":"' . $assist_number['assist_number'] .
                 '","openid":"","timestamp":"' . $timestamp . '","signature":"' .
                 $sign . '"}';
            log_write("codeext " . $card_ext);
            return array(
                "card_ext" => $card_ext, 
                "card_id" => $wx_goods_info['card_id']);
        }
    }
    
    // 新建辅助码 内部抽奖接口使用，不扣减库存
    public function add_assist_number_nostore_for_award($open_id, $batch_info_id, 
        $data_from, $request_id) {
        log_write('create assist_number :' . $open_id, 'wxcard');
        
        // $assist_number = M()->table('twx_assist_number')->where("open_id =
        // '".$open_id ."' and card_batch_id = ".$batch_info_id." and status =
        // '1'")->find();
        $wx_goods_info = M()->table('tbatch_info i')
            ->where("id = " . $batch_info_id)
            ->find();
        if (! $wx_goods_info) {
            log_write("cant find tbatch_info" . M()->_sql());
            $this->error = '系统错误[01]';
            return false;
        }
        // 查找appsercet;
        $weixin_info = M()->table('tweixin_info')
            ->where("node_id = '" . $wx_goods_info['node_id'] . "'")
            ->find();
        if (! $weixin_info) {
            log_write("cant find weixin_info" . M()->_sql());
            $this->error = '系统错误[02]';
            return false;
        }
        $jsapi_ticket = $this->get_jsapi_ticket($weixin_info);
        if ($jsapi_ticket === false) {
            log_write("cant get jsapi_ticket" . M()->_sql());
            $this->error = '系统错误[05]';
            return false;
        }
        
        $timestamp = time();
        $i = 0;
        for ($i = 0; $i < 10; $i ++) {
            $assist_number['assist_number'] = C('CARD_ASSIST_PRE_NUMBER') . rand(1000, 9999) .
                 rand(1000, 9999) . rand(1000, 9999);
            $assist_number['status'] = '1';
            $assist_number['open_id'] = $open_id;
            $assist_number['node_id'] = $wx_goods_info['node_id'];
            $assist_number['batch_no'] = $wx_goods_info['batch_no'];
            $assist_number['card_batch_id'] = $batch_info_id;
            $assist_number['data_from'] = $data_from;
            $assist_number['add_time'] = date('YmdHis');
            $assist_number['request_id'] = $request_id;
            $rs = M()->table('twx_assist_number')->add($assist_number);
            if ($rs !== false) {
                // 增加twx_card_type get_card_num 数量
                if ($this->add_get_card_num($wx_goods_info['card_id']) === false) {
                    log_write(
                        "update tbatch_info remain_num error" . M()->_sql());
                    $this->error = '系统错误[06]';
                    return false;
                }
                // 计算签名
                $sign_arr = array(
                    $jsapi_ticket, 
                    $assist_number['assist_number'], 
                    $timestamp, 
                    $wx_goods_info['card_id']);
                sort($sign_arr, SORT_STRING);
                $sign_src = implode('', $sign_arr);
                log_write("sign_src " . $sign_src);
                $sign = sha1($sign_src);
                // $card_ext =
                // '{\"code\":\"'.$assist_number['assist_number'].'\",\"openid\":\"\",\"timestamp\":\"'.$timestamp.'\",\"signature\":\"'.$sign.'\"}';
                $card_ext = '{"code":"' . $assist_number['assist_number'] .
                     '","openid":"","timestamp":"' . $timestamp .
                     '","signature":"' . $sign . '"}';
                log_write("codeext " . $card_ext);
                return array(
                    "card_ext" => $card_ext, 
                    "card_id" => $wx_goods_info['card_id']);
            }
        }
        // rollback;
        log_write("insert into twx_assist_number error " . M()->_sql());
        $this->error = '系统错误[04]';
        return false;
    }

    /**
     * 新建辅助码 微信代发卡券创建的时候由定时任务处理使用, 库存由界面进行扣减
     *
     * @param $batch_info_id
     * @return bool|string 辅助码
     */
    public function add_assist_number_for_create($wx_goods_info) {
        $open_id = "13900000001";
        log_write('create assist_number :' . $open_id, 'wxcard');
        
        $i = 0;
        for ($i = 0; $i < 10; $i ++) {
            $assist_number['assist_number'] = C('CARD_ASSIST_PRE_NUMBER') . rand(1000, 9999) .
                 rand(1000, 9999) . rand(1000, 9999);
            $assist_number['status'] = '1';
            $assist_number['open_id'] = $open_id;
            $assist_number['node_id'] = $wx_goods_info['node_id'];
            $assist_number['batch_no'] = $wx_goods_info['batch_no'];
            $assist_number['card_batch_id'] = $wx_goods_info['id'];
            $assist_number['add_time'] = date('YmdHis');
            $assist_number['data_from'] = 'W';
            $rs = M()->table('twx_assist_number')->add($assist_number);
            if ($rs !== false) {
                return $assist_number['assist_number'];
            }
        }
        log_write("insert into twx_assist_number error " . M()->_sql());
        $this->error = '系统错误[04]';
        return false;
    }

    /**
     * 向微信卡券批量添加辅助码，完成之后修改库存，该方法内部有事务
     *
     * @param $batch_info_id tbatch_info.id
     * @param $count 添加的数量
     * @return bool
     */
    public function batch_add_assist_number_for_create($card_id, $count) {
        // 开启事务
        M()->startTrans();
        // 查找默认的tbatch_info
        $wx_goods_info = M()->table('tbatch_info i')
            ->where("card_id = '" . $card_id . "'")
            ->find();
        if (! $wx_goods_info) {
            log_write("cant find tbatch_info" . M()->_sql());
            $this->error = '系统错误[01]';
            return false;
        }
        
        $tmp_count = 0;
        $result = array();
        $assist_number_arr = array();
        for ($i = 0; $i < $count; $i ++) {
            if ($tmp_count >= 100) {
                $tmp_count = 0;
                $result = $this->save_code_to_weixin($card_id, 
                    $assist_number_arr);
                unset($assist_number_arr);
                if ($result === false) {
                    log_write("save_code_to_weixin error");
                    M()->rollback();
                    $this->error = '系统错误[02]';
                    return false;
                }
            }
            $assist_number = $this->add_assist_number_for_create($wx_goods_info);
            if ($assist_number === false) {
                log_write("add_assist_number_for_create error" . M()->_sql());
                M()->rollback();
                $this->error = '系统错误[03]';
                return false;
            }
            $assist_number_arr[] = $assist_number;
            $tmp_count ++;
        }
        if ($tmp_count > 0) {
            $result = $this->save_code_to_weixin($card_id, $assist_number_arr);
            if ($result === false) {
                log_write("save_code_to_weixin error");
                M()->rollback();
                $this->error = '系统错误[04]';
                return false;
            }
        }
        // 修改库存 增加库存数
        $result = $this->modify_stock($card_id, $count);
        if ($result === false) {
            log_write("modify_stock error");
            M()->rollback();
            $this->error = '系统错误[05]';
            return false;
        }
        M()->commit();
        return true;
    }
    // 获得用于卡券发放的outid 参考twx_card_outid表
    public function getOutId($type, $relation_id, $channle_id, $batch_channel_id, $m_id, $b_id){
        $saveArr['type'] = $type;
        $saveArr['relation_id'] = $relation_id;
        $saveArr['channel_id'] = $channle_id;
        $saveArr['batch_channel_id'] = $batch_channel_id;
        $saveArr['m_id'] = $m_id;
        $saveArr['b_id'] = $b_id;
        $rs = M()->table('twx_card_outid')->add($saveArr);
        if ($rs === false){
            log_write("getOutId error! " . M()->_sql());
            return false;
        }else{
            return $rs;
        }
    }
    // 调用code_send_forlabel生成凭证
    public function create_code($assist_number, $wx_open_id, $card_id,$friend_user_open_id, $is_by_friend,$OuterId='') {
        // 转赠，不处理
        if ($is_by_friend == '1') {
            return true;
        }
        $card_type_info = M()->table('twx_card_type')
            ->where("card_id='{$card_id}'")
            ->find();
        if (! $card_type_info) {
            log_write("找不到卡券" . M()->_sql());
            return false;
        }
        $channel_id = 0;
        $relation_id = 0;
        if(!empty($OuterId) && $OuterId > 0) {
            //查找outid记录
            $twx_card_outid = M()->table('twx_card_outid')->where('id ='.$OuterId)->find();
            if ($twx_card_outid === false || $twx_card_outid == null){
                log_write("找不到outid" . M()->_sql());  
            }else{
                $channel_id = $twx_card_outid['channel_id'];
                $relation_id = $twx_card_outid['relation_id'];
                if ($twx_card_outid['b_id'] != null){
                    $batchInfo = M('tbatch_info')->where("id={$twx_card_outid['b_id']}")->find();
                }
            }
        }  
        //朋友的券处理
        if($card_type_info['card_class'] == '2'){
            //朋友的券只允许投放一次
            $batchInfo = M('tbatch_info')->field("id,batch_no")->where("card_id='{$card_id}'")->find();
            if(!empty($OuterId) && $OuterId > 0) {
                $channel_id = $twx_card_outid['channel_id'];
                $relation_id = $twx_card_outid['relation_id'];
                $batchInfo['id'] = $twx_card_outid['b_id'];
            }  
                    
            //twx_assist_number添加数据
            $nData = array(
                'assist_number' => $assist_number,
                'open_id' => $wx_open_id,
                'node_id' => $card_type_info['node_id'],
                'status' => '1',
                'batch_no' => $batchInfo['batch_no'],
                'card_batch_id' => $batchInfo['id'],
                'channel_id' => $channel_id,
                'relation_id' => $relation_id,
                'add_time' => date('YmdHis')
            );
            $result = M('twx_assist_number')->add($nData);
            if(!$result){
                log_write("朋友的券辅助码创建失败" . M()->_sql());
                return false;
            }
        }
        
        $wx_assist_number_info = M()->table('twx_assist_number')
            ->where("assist_number = '" . $assist_number . "'")
            ->find();
        if (! $wx_assist_number_info) {
            log_write("找不到原辅助码" . $assist_number . $wx_open_id);
            return false;
        }
		if ($wx_assist_number_info['status']== '2')
		{
			log_write("该卡券已领取成功！！！" . $assist_number . $wx_open_id);
			return true;
		}

        // 计算有效期
        if ($card_type_info['date_type'] == 1) {
            $begin_use_time = date('YmdHis', 
                $card_type_info['date_begin_timestamp']);
            $end_use_time = date('YmdHis', 
                $card_type_info['date_end_timestamp']);
        } else {
            $begin_use_time = date('YmdHis', 
                time() +
                     3600 * 24 * $card_type_info['date_fixed_begin_timestamp']);
            $end_use_time = date('Ymd', 
                time() + 3600 * 24 * $card_type_info['date_fixed_timestamp']) .
                 '235959';
        }
        // 调用发码
        $RemoteRequest = D('RemoteRequest', 'Service');
        $TransactionID = date("YmdHis") . rand(100000, 999999); // 凭证发送单号
        if ($wx_assist_number_info['data_from'] == null) {
            $data_from = 'W';
        } else {
            $data_from = $wx_assist_number_info['data_from'];
        }
        if ($wx_assist_number_info['request_id'] != null) {
            $TransactionID = $wx_assist_number_info['request_id'];
        }
        
        $phone_no = '13900000000';
        $req_data = "&node_id=" . $wx_assist_number_info['node_id'] .
             "&phone_no=" . $phone_no . "&batch_no=" .
             $wx_assist_number_info['batch_no'] . "&request_id=" . $TransactionID .
             "&data_from=" . $data_from . "&assist_number=" . $assist_number .
             "&begin_use_time=" . $begin_use_time . "&end_use_time=" .
             $end_use_time . "&is_wx_card=yes&batch_info_id=" .
             $wx_assist_number_info['card_batch_id']."&wx_card_id=".$card_id."&channel_id=".$channel_id;
        ;
        $resp_array = $RemoteRequest->requestWcAppServ($req_data);
        log_write('发码内容' . $req_data);
        log_write(print_r($resp_array, true));
        if (! $resp_array || ($resp_array['resp_id'] != '0000' &&
             $resp_array['resp_id'] != '0001')) {
            log_write("旺财发码失败" . $assist_number . $resp_array['resp_desc']);
            return false;
        }
        
        // 更新barcode_trace
        $barcode_trace_info['wx_open_id'] = $wx_open_id;
        $rs = M()->table('tbarcode_trace ')
            ->where("assist_number = '" . $assist_number . "'")
            ->save($barcode_trace_info);
        if (! $rs) {
            log_write("更新凭证表数据错误" . M()->_sql());
            return false;
        }
        //统计twx_card_stat
        $batchInfo = M('tbatch_info')->where("id={$wx_assist_number_info['card_batch_id']}")->find();
        $this->wx_card_get_stat($batchInfo, date('Ymd'));
        
        // 更新twx_assist_number
        $wx_assist_number_info = array();
        $wx_assist_number_info['status'] = '2';
        $wx_assist_number_info['request_id'] = $TransactionID;
        $wx_assist_number_info['channel_id'] = $channel_id;
        $wx_assist_number_info['relation_id'] = $relation_id;
        $rs = M()->table('twx_assist_number ')
            ->where("assist_number = '" . $assist_number . "'")
            ->save($wx_assist_number_info);
        if (! $rs) {
            log_write("更新twx_assist_number数据错误" . M()->_sql());
            return false;
        }
        // 如果是预存模式的话增加twx_card_type get_card_num 数量
        if ($card_type_info['store_mode'] == '2') {
            return $this->add_get_card_num($card_type_info['card_id']);
        }
        if ($card_type_info['card_class'] == '2') {
            $tiid=$twx_card_outid['b_id'];
        //更新朋友券领取数据
            // 't.verify_count'=>array('exp','t.verify_count+1')
        M()->table(array('tbatch_channel'=>'tc','tchannel'=>'t'))
            ->where('t.id =tc.channel_id and tc.id='
                    .$twx_card_outid['batch_channel_id'])
            ->save(array('tc.send_count'=>array('exp','tc.send_count+1'),
                't.click_count'=>array('exp','t.click_count+1'),
                't.send_count'=>array('exp','t.send_count+1'),
                'tc.click_count'=>array('exp','tc.click_count+1')
           ));
        log_write('miao'.M()->getlastsql());
        }
        return true;
    }

    // 抽奖参与统计（新增）
    private function wx_card_get_stat($batch_info, $add_date)
    {
        $where = "b_id = " . $batch_info['id'] . " and trans_date = '" . $add_date . "'";
        // 查找有无记录，有则加1，无则新增
        $get_stat_info = M('twx_card_stat')->where($where)->find();
        if (!$get_stat_info) {
            $get_stat['node_id'] = $batch_info['node_id'];
            $get_stat['m_id'] = $batch_info['m_id'];
            $get_stat['b_id'] = $batch_info['id'];
            $get_stat['trans_date'] = $add_date;
            $get_stat['get_stat_num'] = 1;
            $rs = M('twx_card_stat')->add($get_stat);
            if ($rs === false) { // log no exit
                $this->log("记录统计信息[twx_card_stat]失败" . M()->_sql());
                return false;
            } else {
                return true;
            }
        }
        // 更新次数
        $rs = M()->table('twx_card_stat')->where($where)->setInc('get_stat_num');
        if ($rs === false) { // log no exit
            $this->log("更新统计信息[twx_card_stat]失败" . M()->_sql());
            return false;
        } else {
            return true;
        }
    }
    //
    /**
     *
     * @param $card_id
     * @return bool @增加twx_card_type get_card_num 数量+1
     */
    private function add_get_card_num($card_id) {
        $rs = M()->table('twx_card_type ')
            ->where("card_id = '" . $card_id . "'")
            ->setInc('card_get_num');
        log_write('qqqqqqqqqqqqq');
        if ($rs === false) {
            log_write("add_get_card_num error" . M()->_sql());
            return false;
        }
        return true;
    }
    // 卡券核销
    public function card_consume($goods_id, $assist_number) {
        // 获取card_id
        $card_type_info = M()->table('twx_card_type ')
            ->where("goods_id = '" . $goods_id . "'")
            ->find();
        if (! $card_type_info) {
            log_write('can not find card_type ' . $goods_id, 'wxcard');
            return false;
        }
        $api_url = 'https://api.weixin.qq.com/card/code/consume';
        $post_data['code'] = $assist_number;
        $post_data['card_id'] = $card_type_info['card_id'];
        
        $result = $this->send($api_url, $post_data);
        
        if ($result['errcode'] == '0') {
            return true;
        } else {
            return false;
        }
    }

    public function bd2gcj($locs) {
        $x_pi = 3.14159265358979324 * 3000.0 / 180.0;
        $x = $locs[1] - 0.0065;
        $y = $locs[0] - 0.006;
        $z = sqrt($x * $x + $y * $y) - 0.00002 * sin($y * $x_pi);
        $theta = atan2($y, $x) - 0.000003 * cos($x * $x_pi);
        $locs[1] = $z * cos($theta);
        $locs[0] = $z * sin($theta);
        return $locs;
    }
    // unicode字符转可见
    public function unicodeDecode($name) {
        return preg_replace_callback('/\\\\u([0-9a-f]{4})/i', 
            create_function('$matches', 
                'return mb_convert_encoding(pack("H*", $matches[1]), "utf-8", "UCS-2BE");'), 
            $name);
    }

    /**
     * 获得还没有领取的卡券信息
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $needWxLogin
     * @param $nodeId
     * @param $id
     * @return array|bool
     */
    public function getUnfetchedCard($needWxLogin, $nodeId, $id) {
        if ($needWxLogin) {
            $wxInfo = $this->getWxUserInfo($nodeId);
            $open_id = $wxInfo['openid'];
            $sql = "SELECT a.id FROM tbatch_info a INNER JOIN tbatch_channel b ON a.m_id = b.batch_id WHERE " .
                 " b.id = " . $id . " and a.card_id is not null";
            log_write($sql, 'SQL');
            $batchids = M()->query($sql);
            log_write(print_r($batchids, true), 'batchids');
            $batchids = array_valtokey($batchids, 'id', 'id');
            if (! $batchids) {
                return false;
            }
            $where = array(
                'open_id' => $open_id, 
                'card_batch_id' => array(
                    'in', 
                    $batchids), 
                'status' => 1);
            // 这条可以查找 用户有没有未领取的记录
            $assist_number = M()->table('twx_assist_number')
                ->where($where)
                ->find();
            log_write(M()->_sql(), 'SQL');
            if (! $assist_number) {
                return false;
            }
            $batchInfo = M('tbatch_info')->where(
                array(
                    'id' => $assist_number['card_batch_id']))->find();
            log_write(M()->_sql(), 'SQL');
            $service = D('WeiXinCard', 'Service');
            $service->init_by_node_id($this->node_id);
            // $service->init($this->appId,$this->appSecret,$this->accessToken);
            $card_ext = $service->add_assist_number($open_id, 
                $assist_number['card_batch_id']);
            $card_id = $batchInfo['card_id'];
            return array(
                'card_ext' => $card_ext, 
                'card_id' => $card_id);
        }
        return false;
    }

    private $wxUserInfo = array();

    /**
     * 用户微信信息 PS:方法依赖于 的session数据。 如果 $_SESSION['node_wxid_'.$nodeId]
     * 这个就不能正常的取到数据
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $nodeId
     */
    public function getWxUserInfo($nodeId)
    {
        if (!isset($this->wxUserInfo[$nodeId]) || empty($this->wxUserInfo[$nodeId])) {
            $wxSess = session('node_wxid_' . $nodeId);
            if ($wxSess) {
                $openid = isset($wxSess['openid']) ? $wxSess['openid'] : '';
                $where  = ['openid'    => $openid,'node_id'   => $nodeId, 'subscribe' => ['neq','0']];
                $userInfo = M('twx_user')->where($where)->find() or $userInfo = [];
                $this->wxUserInfo[$nodeId] = array_merge($userInfo, $wxSess);
            } else {
                $this->wxUserInfo[$nodeId] = array();
            }
        }
        return $this->wxUserInfo[$nodeId];
    }

    /**
     * 微信卡券 code 解码
     *
     * @param string $encryptCode 微信卡券返回参数
     * @return array 微信卡券数据返回
     */
    public function decodeWechatCardCode($encryptCode) {
        $api_url = 'https://api.weixin.qq.com/card/code/decrypt';
        $data['encrypt_code'] = $encryptCode;
        $result = $this->send($api_url, $data);
        return $result;
    }

    private $initedWechatInfo = array();

    public function initByNodeId($nodeId) {
        if (isset($this->initedWechatInfo[$nodeId])) {
            $wechatNodeInfo = $this->initedWechatInfo[$nodeId];
        } else {
            $wechatNodeInfo = M()->table('tweixin_info')
                ->where("node_id = '" . $nodeId . "'")
                ->find();
            if ($wechatNodeInfo) {
                $this->initedWechatInfo[$nodeId] = array(
                    'appId' => $wechatNodeInfo['app_id'], 
                    'appSecret' => $wechatNodeInfo['app_secret'], 
                    'accessToken' => $wechatNodeInfo['app_access_token']);
                $wechatNodeInfo = $this->initedWechatInfo[$nodeId];
            }
        }
        $this->appId = $wechatNodeInfo['appId'];
        $this->appSecret = $wechatNodeInfo['appSecret'];
        $this->accessToken = $wechatNodeInfo['accessToken'];
    }

    /**
     *
     * @param $unfetchedWechatCardList
     * @return array
     */
    public function batchGenerateCardExt($unfetchedWechatCardList) {
        $finalUnfetchedWechatCardList = array();
        foreach ($unfetchedWechatCardList as $key => $unfetchedWechatCard) {
            $currentNodeId = isset($unfetchedWechatCard['nodeId']) ? $unfetchedWechatCard['nodeId'] : '';
            if ($currentNodeId) {
                if (! isset($this->initedWechatInfo[$currentNodeId])) {
                    $this->initByNodeId($currentNodeId);
                }
                $wxInfo = $this->getWxUserInfo($currentNodeId);
                $openId = isset($wxInfo['openid']) ? $wxInfo['openid'] : '';
                $cardExt = $this->add_assist_number($openId, 
                    $unfetchedWechatCard['cardBatchId']);
                $unfetchedWechatCard['card_ext'] = $cardExt;
            }
            $finalUnfetchedWechatCardList[$key] = $unfetchedWechatCard;
        }
        
        return $finalUnfetchedWechatCardList;
    }

    /**
     * 上传图片到微信服务器,返回图片地址
     *
     * @param $imgUrl 图片路径
     */
    public function uploadeImgToWx($imgUrl) {
        $api_uri = 'https://api.weixin.qq.com/cgi-bin/media/uploadimg';
        $data_arr['buffer'] = '@' . realpath($imgUrl);
        $api_url = $api_uri . '?access_token=' . $this->accessToken;
        log_write($api_url, 'wxcard');
        // 创建post请求参数
        import('@.ORG.Net.FineCurl') or die('[@.ORG.Net.FineCurl]导入包失败');
        $socket = new FineCurl();
        $socket->setopt('URL', $api_url);
        $socket->setopt('TIMEOUT', '30');
        $socket->setopt('CURLOPT_POST', 1);
        $result = $socket->send($data_arr);
        $error = $socket->error();
        $result = json_decode($result, true);
        // token过期处理
        if ($result['errcode'] == '40001' || $result['errcode'] == '42001' ||
             $result['errcode'] == '41001') {
            $aa = $this->getToken();
            $api_url = $api_uri . '?access_token=' . $this->accessToken;
            $socket = new FineCurl();
            $socket->setopt('URL', $api_url);
            $socket->setopt('TIMEOUT', '30');
            $socket->setopt('CURLOPT_POST', 1);
            $result = $socket->send($data_arr);
            $result = json_decode($result, true);
        }
        return $result;
    }
}