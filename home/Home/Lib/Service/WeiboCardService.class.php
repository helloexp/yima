<?php
// 微信卡券接口
class WeiboCardService {

    public $appId;

    public $appSecret;

    public $accessToken;

    public $error = '';
    // 初始化
    public function init($merchantUid, $pin) {
        $this->merchantUid = $merchantUid;
        $this->pin = $pin;
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
            Log::write('save tweixin_info setToken ' . M()->_sql(), 'error');
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
            $result = httpPost($apiUrl, '',$error,
                array(
                    'TIMEOUT' => 30));
            if ($result) {
                break;
            } else {
                Log::write($error, 'error');
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
        $post_data = $data_arr;
        Log::write($api_uri, 'wbcard');
        Log::write($post_data, 'wbcard');
        
        // 判断网络
        $error = '';
        for ($i = 0; $i < 10; $i ++) {
            $result_str = httpPost($api_uri, $post_data, $error, 
                array(
                    'TIMEOUT' => 30));
            if ($result_str) {
                break;
            } else {
                Log::write($error, 'error');
            }
            usleep(500 * 1000);
        }
        Log::write($result_str, 'wbcard');
        $result = json_decode($result_str, true);
        $this->error = $result['msg'];
        return $result;
    }
    // 获取JSAPI ticket
    public function get_jsapi_ticket($weixin_info) {
        $api_url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=wx_card';
        // 获取本地表ticket 如果超时则重新获取
        if (($weixin_info['jsapi_ticket'] == null) ||
             ((time() > $weixin_info['jsapi_ticket_get_time']))) {
            Log::write($api_url, 'wbcard');
            Log::write($post_data, 'wbcard');
            $result = $this->send($api_url, $data_arr);
            if ($result['errcode'] == '0') {
                $weixin_info_update['jsapi_ticket'] = $result['ticket'];
                $weixin_info_update['jsapi_ticket_get_time'] = time() + 3600;
                $rs = M()->table('tweixin_info')
                    ->where("app_id = '" . $this->appId . "'")
                    ->save($weixin_info_update);
                if ($rs === false) {
                    Log::write(
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
    // 获取颜色列表
    /*
     * { "errcode":0, "errmsg":"ok", "colors":[
     * {"name":"Color010","value":"#61ad40"},
     * {"name":"Color020","value":"#169d5c"},
     * {"name":"Color030","value":"#239cda"} ] }
     */
    public function getcolors() {
        $api_url = 'https://api.weixin.qq.com/card/create';
        Log::write($api_url, 'wbcard');
        Log::write($post_data, 'wbcard');
        
        // $result = $this->send($api_url, $data_arr);
        $result_str = '{"errcode":0,"errmsg":"ok","colors":[{"name":"Color010","value":"#55bd47"},{"name":"Color020","value":"#10ad61"},{"name":"Color030","value":"#35a4de"},{"name":"Color040","value":"#3d78da"},{"name":"Color050","value":"#9058cb"},{"name":"Color060","value":"#de9c33"},{"name":"Color070","value":"#ebac16"},{"name":"Color080","value":"#f9861f"},{"name":"Color081","value":"#f08500"},{"name":"Color090","value":"#e75735"},{"name":"Color100","value":"#d54036"},{"name":"Color101","value":"#cf3e36"}]}';
        $result = json_decode($result_str, true);
        
        return $result;
    }
    // 创建卡券
    public function create($wb_card_type_id) {
        // 正式环境url
        $api_url = 'http://vi.e.weibo.com/interface/external/coupon/add';
        // 测试环境url
        // $api_url = 'http://api.c.weibo.com/interface/external/coupon/add';
        $wb_card_type_info = M()->table('tweibo_card_type')
            ->where("id = " . $wb_card_type_id)
            ->find();
        // 基础矫验数据
        $base_info['merchantUid'] = $this->merchantUid;
        $base_info['ts'] = time();
        $base_info['s'] = hash_hmac('sha1', 
            $this->merchantUid . $base_info['ts'], $this->pin);
        // 优惠券数据
        $base_info['merchantName'] = $wb_card_type_info['merchant_name'];
        $base_info['accessMode'] = $wb_card_type_info['access_mode'];
        $base_info['outerId'] = $wb_card_type_info['goods_id'];
        $base_info['type'] = $wb_card_type_info['card_type'];
        $base_info['title'] = $wb_card_type_info['title'];
        $base_info['subTitle'] = $wb_card_type_info['sub_title'];
        $base_info['picUrl'] = $wb_card_type_info['pic_url'];
        $base_info['startDate'] = date('Y-m-d H:i:s', 
            strtotime($wb_card_type_info['start_date']));
        $base_info['endDate'] = date('Y-m-d H:i:s', 
            strtotime($wb_card_type_info['end_date']));
        $base_info['contactPhone'] = $wb_card_type_info['contactphone'];
        $base_info['extension'] = $wb_card_type_info['extension'];
        $base_info['startValidDate'] = date('Y-m-d H:i:s', 
            strtotime($wb_card_type_info['startvaliddate']));
        $base_info['endValidDate'] = date('Y-m-d H:i:s', 
            strtotime($wb_card_type_info['endvaliddate']));
        $base_info['nominalPrice'] = round($wb_card_type_info['nominal_price'], 
            2);
        $base_info['price'] = round($wb_card_type_info['price'], 2);
        $base_info['circulation'] = (int) $wb_card_type_info['circulation'];
        $base_info['limited'] = (int) $wb_card_type_info['limited'];
        $base_info['intro'] = $wb_card_type_info['intro'];
        // $base_info['memberRight'] = $wb_card_type_info['member_right'];
        // $base_info['suitStore'] = $wb_card_type_info['suit_store'];
        $goods_info = M()->table('tgoods_info')
            ->where("goods_id = '" . $wb_card_type_info['goods_id'] . "'")
            ->find();
        if (empty($goods_info)) {
            $this->error = '商品卡券不存在';
            M('tweibo_card_type')->where("id = " . $wb_card_type_id)->save(
                array(
                    'status' => 1, 
                    'msg' => '商品卡券不存在'));
            return false;
        }
        $result = $this->send($api_url, $base_info);
        if ($result['code'] == '100000') {
            $rs = M()->table('tweibo_card_type')
                ->where("id = " . $wb_card_type_id)
                ->save(
                array(
                    'status' => 2, 
                    'error_code' => $result['code'], 
                    'msg' => $result['msg'], 
                    'card_id' => $result['data']['couponId']));
            if (! $rs) {
                Log::write(
                    "更新微博卡券card_id错误" . $wb_card_type_id . 'care_id:' .
                         $result['data']['couponId']);
            }
            return true;
        } else {
            M()->table('tweibo_card_type')
                ->where("id = " . $wb_card_type_id)
                ->save(
                array(
                    'status' => 1, 
                    'error_code' => $result['code'], 
                    'msg' => $result['msg']));
            return false;
        }
    }
    // 卡券审核通知处理
    public function card_type_audit($node_id, $card_id, $event) {
        Log::write(
            'card_type_audit :' . $node_id . '|' . $card_id . '|' . $event, 
            'wbcard');
        
        if ($event == 'card_pass_check') {
            $wb_card_type_info['auth_flag'] = '2';
        } else if ($event == 'card_not_pass_check') {
            $wb_card_type_info['auth_flag'] = '3';
        }
        $rs = M()->table('twx_card_type')
            ->where(
            "node_id = '" . $node_id . "' and card_id = '" . $card_id . "'")
            ->save($wb_card_type_info);
        if (! $rs) {
            Log::write("update card_type_audit audit data error" . M()->_sql());
            ;
        }
    }
    
    // 拉取门店列表
    public function store_batch_get() {
        $api_url = 'https://api.weixin.qq.com/card/location/batchget';
        $data_arr['offset'] = 0;
        $data_arr['count'] = 100;
        
        $store_list = array();
        
        Log::write($api_url, 'wbcard');
        Log::write($post_data, 'wbcard');
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
        
        Log::write($api_url, 'wbcard');
        Log::write(print_r($store_arr, true), 'wbcard');
        
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
        Log::write(print_r($local_store_array, true));
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
        Log::write(print_r($no_sync_store_list, true));
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
            $sync_store_list = $this->store_batch_add($sync_store_arr, 
                $sync_store_list);
            Log::write(print_r($sync_store_list, true));
            if (count($sync_store_list) > 0) {
                // 更新数据库
                foreach ($sync_store_list as $k => $v) {
                    $new_store_array[$k] = $v;
                    $store_info['wx_store_id'] = $v;
                    $rs = M()->table('tstore_info')
                        ->where("store_id = '" . $k . "'")
                        ->save($store_info);
                    if (! $rs) {
                        Log::write("更新门店数据错误" . M()->_sql());
                        ;
                    }
                }
            }
        }
        Log::write("new_store_array:" . print_r($new_store_array, true));
        // die;
        return $new_store_array;
    }
    
    // 获取微信卡券领取二维码
    public function create_wx_qrcode($card_id, $code) {
        $api_url = 'https://api.weixin.qq.com/card/qrcode/create';
        $data_arr['action_name'] = 'QR_CARD';
        $data_arr['action_info']['card']['card_id'] = $card_id;
        $data_arr['action_info']['card']['code'] = $code;
        Log::write($api_url, 'wbcard');
        Log::write($post_data, 'wbcard');
        
        $result = $this->send($api_url, $data_arr);
        
        return $result;
    }
    
    // 新建辅助码 重复利用辅助码可能会有无法预测的问题，每次都新建，后期再考虑清除未使用数据
    public function create_white_user($user_name_arr) {
        $api_url = 'https://api.weixin.qq.com/card/testwhitelist/set';
        $data_arr['username'] = $user_name_arr;
        Log::write($api_url, 'wbcard');
        Log::write($post_data, 'wbcard');
        
        $result = $this->send($api_url, $data_arr);
        
        return $result;
    }
    // 新建辅助码
    public function add_assist_number($open_id, $batch_info_id) {
        Log::write('create assist_number :' . $open_id, 'wbcard');
        
        $assist_number = M()->table('twx_assist_number')
            ->where(
            "open_id = '" . $open_id . "' and card_batch_id = " . $batch_info_id .
                 " and status = '1'")
            ->find();
        // 锁定库存
        // 开启事务
        M()->startTrans();
        $wx_goods_info = M()->table('tbatch_info i')
            ->lock(true)
            ->where("id = " . $batch_info_id)
            ->find();
        if (! $wx_goods_info) {
            Log::write("cant find tbatch_info" . M()->_sql());
            M()->rollback();
            $this->error = '系统错误[01]';
            return false;
        }
        // 查找appsercet;
        $weixin_info = M()->table('tweixin_info')
            ->where("node_id = '" . $wx_goods_info['node_id'] . "'")
            ->find();
        if (! $weixin_info) {
            Log::write("cant find weixin_info" . M()->_sql());
            M()->rollback();
            $this->error = '系统错误[02]';
            return false;
        }
        $jsapi_ticket = $this->get_jsapi_ticket($weixin_info);
        if ($jsapi_ticket === false) {
            Log::write("cant get jsapi_ticket" . M()->_sql());
            $this->error = '系统错误[05]';
            return false;
        }
        
        $timestamp = time();
        if (! $assist_number) {
            // 扣减
            if ($wx_goods_info['storage_num'] != - 1) { // 限制库存
                if ($wx_goods_info['remain_num'] < 1) {
                    Log::write(
                        "storage_num less than send_num [" .
                             $wx_goods_info['storage_num'] . '][' .
                             ($wx_goods_info['remain_num']) . ']');
                    M()->rollback();
                    $this->error = '卡券已发完';
                    return false;
                }
            }
            Log::write('111' . $open_id, 'wbcard');
            $wx_goods_info_tmp = array();
            $wx_goods_info_tmp['remain_num'] = $wx_goods_info['remain_num'] - 1;
            $rs = M()->table('tbatch_info')
                ->where("id = " . $batch_info_id)
                ->save($wx_goods_info_tmp);
            if (! $rs) {
                Log::write("update tbatch_info remain_num error" . M()->_sql());
                M()->rollback();
                $this->error = '系统错误[03]';
                return false;
            }
            $i = 0;
            for ($i = 0; $i < 10; $i ++) {
                $assist_number['assist_number'] = '7063' . rand(1000, 9999) .
                     rand(1000, 9999) . rand(1000, 9999);
                $assist_number['status'] = '1';
                $assist_number['open_id'] = $open_id;
                $assist_number['node_id'] = $wx_goods_info['node_id'];
                $assist_number['batch_no'] = $wx_goods_info['batch_no'];
                $assist_number['card_batch_id'] = $batch_info_id;
                $rs = M()->table('twx_assist_number')->add($assist_number);
                if ($rs !== false) {
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
                    Log::write("sign_src " . $sign_src);
                    $sign = sha1($sign_src);
                    // $card_ext =
                    // '{\"code\":\"'.$assist_number['assist_number'].'\",\"openid\":\"\",\"timestamp\":\"'.$timestamp.'\",\"signature\":\"'.$sign.'\"}';
                    $card_ext = '{"code":"' . $assist_number['assist_number'] .
                         '","openid":"","timestamp":"' . $timestamp .
                         '","signature":"' . $sign . '"}';
                    Log::write("codeext " . $card_ext);
                    return $card_ext;
                }
            }
            // rollback;
            Log::write("insert into twx_assist_number error " . M()->_sql());
            M()->rollback();
            $this->error = '系统错误[04]';
            return false;
        } else {
            M()->rollback();
            // 计算签名
            $sign_arr = array(
                $jsapi_ticket, 
                $assist_number['assist_number'], 
                $timestamp, 
                $wx_goods_info['card_id']);
            sort($sign_arr, SORT_STRING);
            $sign_src = implode('', $sign_arr);
            Log::write("sign_src " . $sign_src);
            $sign = sha1($sign_src);
            $card_ext = '{"code":"' . $assist_number['assist_number'] .
                 '","openid":"","timestamp":"' . $timestamp . '","signature":"' .
                 $sign . '"}';
            Log::write("codeext " . $card_ext);
            return $card_ext;
        }
    }
    
    // 新建辅助码 内部抽奖接口使用，不扣减库存
    public function add_assist_number_nostore($open_id, $batch_info_id, 
        $data_from) {
        Log::write('create assist_number :' . $open_id, 'wbcard');
        
        $assist_number = M()->table('twx_assist_number')
            ->where(
            "open_id = '" . $open_id . "' and card_batch_id = " . $batch_info_id .
                 " and status = '1'")
            ->find();
        $wx_goods_info = M()->table('tbatch_info i')
            ->where("id = " . $batch_info_id)
            ->find();
        if (! $wx_goods_info) {
            Log::write("cant find tbatch_info" . M()->_sql());
            $this->error = '系统错误[01]';
            return false;
        }
        // 查找appsercet;
        $weixin_info = M()->table('tweixin_info')
            ->where("node_id = '" . $wx_goods_info['node_id'] . "'")
            ->find();
        if (! $weixin_info) {
            Log::write("cant find weixin_info" . M()->_sql());
            $this->error = '系统错误[02]';
            return false;
        }
        $jsapi_ticket = $this->get_jsapi_ticket($weixin_info);
        if ($jsapi_ticket === false) {
            Log::write("cant get jsapi_ticket" . M()->_sql());
            $this->error = '系统错误[05]';
            return false;
        }
        
        $timestamp = time();
        if (! $assist_number) {
            $i = 0;
            for ($i = 0; $i < 10; $i ++) {
                $assist_number['assist_number'] = '7063' . rand(1000, 9999) .
                     rand(1000, 9999) . rand(1000, 9999);
                $assist_number['status'] = '1';
                $assist_number['open_id'] = $open_id;
                $assist_number['node_id'] = $wx_goods_info['node_id'];
                $assist_number['batch_no'] = $wx_goods_info['batch_no'];
                $assist_number['card_batch_id'] = $batch_info_id;
                $assist_number['data_from'] = $data_from;
                $rs = M()->table('twx_assist_number')->add($assist_number);
                if ($rs !== false) {
                    // 计算签名
                    $sign_arr = array(
                        $jsapi_ticket, 
                        $assist_number['assist_number'], 
                        $timestamp, 
                        $wx_goods_info['card_id']);
                    sort($sign_arr, SORT_STRING);
                    $sign_src = implode('', $sign_arr);
                    Log::write("sign_src " . $sign_src);
                    $sign = sha1($sign_src);
                    // $card_ext =
                    // '{\"code\":\"'.$assist_number['assist_number'].'\",\"openid\":\"\",\"timestamp\":\"'.$timestamp.'\",\"signature\":\"'.$sign.'\"}';
                    $card_ext = '{"code":"' . $assist_number['assist_number'] .
                         '","openid":"","timestamp":"' . $timestamp .
                         '","signature":"' . $sign . '"}';
                    Log::write("codeext " . $card_ext);
                    return array(
                        "card_ext" => $card_ext, 
                        "card_id" => $wx_goods_info['card_id']);
                }
            }
            // rollback;
            Log::write("insert into twx_assist_number error " . M()->_sql());
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
            Log::write("sign_src " . $sign_src);
            $sign = sha1($sign_src);
            $card_ext = '{"code":"' . $assist_number['assist_number'] .
                 '","openid":"","timestamp":"' . $timestamp . '","signature":"' .
                 $sign . '"}';
            Log::write("codeext " . $card_ext);
            return array(
                "card_ext" => $card_ext, 
                "card_id" => $wx_goods_info['card_id']);
        }
    }
    
    // 调用code_send_forlabel生成凭证
    public function create_code($assist_number, $wx_open_id, $card_id, 
        $friend_user_open_id, $is_by_friend) {
        if ($is_by_friend == '1') // 转赠，不处理
            return true;
            // 找出原记录
        $wx_assist_number_info = M()->table('twx_assist_number ')
            ->where("assist_number = '" . $assist_number . "'")
            ->find();
        if (! $wx_assist_number_info) {
            Log::write("找不到原辅助码" . $assist_number . $wx_open_id);
            return false;
        }
        // 计算有效期
        $card_type_info = M()->table('twx_card_type ')
            ->where(
            "node_id = '" . $wx_assist_number_info['node_id'] .
                 "' and card_id = '" . $card_id . "'")
            ->find();
        if (! $card_type_info) {
            Log::write("找不到卡券" . M()->_sql());
            return false;
        }
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
        $phone_no = '13900000000';
        $req_data = "&node_id=" . $wx_assist_number_info['node_id'] .
             "&phone_no=" . $phone_no . "&batch_no=" .
             $wx_assist_number_info['batch_no'] . "&request_id=" . $TransactionID .
             "&data_from=" . $data_from . "&assist_number=" . $assist_number .
             "&begin_use_time=" . $begin_use_time . "&end_use_time=" .
             $end_use_time . "&is_wx_card=yes";
        $resp_array = $RemoteRequest->requestWcAppServ($req_data);
        if (! $resp_array || ($resp_array['resp_id'] != '0000' &&
             $resp_array['resp_id'] != '0001')) {
            Log::write("旺财发码失败" . $assist_number . $resp_array['resp_id']);
            return false;
        }
        // 更新barcode_trace
        $barcode_trace_info['wx_open_id'] = $wx_open_id;
        $rs = M()->table('tbarcode_trace ')
            ->where("assist_number = '" . $assist_number . "'")
            ->save($barcode_trace_info);
        if (! $rs) {
            Log::write("更新凭证表数据错误" . M()->_sql());
            return false;
        }
        
        // 更新twx_assist_number
        $wx_assist_number_info = array();
        $wx_assist_number_info['status'] = '2';
        $rs = M()->table('twx_assist_number ')
            ->where("assist_number = '" . $assist_number . "'")
            ->save($wx_assist_number_info);
        if (! $rs) {
            Log::write("更新twx_assist_number数据错误" . M()->_sql());
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
            Log::write('can not find card_type ' . $goods_id, 'wbcard');
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
}