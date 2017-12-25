<?php
// ָ��ӿڷ�������
class TestAction extends BaseAction {

    protected $wx;

    public $node_id;

    public $req;

    public $token;

    public $access_token;

    public $app_id;

    public $app_secret;

    public $user_name;

    public $response_msg_id;

    public $node_wx_id;

    public $scene_id;

    public $msg_type;

    public $msg_info;

    /*
     * {"location":{"location_flag":"1","resp_count":"3","large_image":"00004488top.jpg","small_image":"00004488item.jpg"}}
     */
    public $setting = array();

    public function _initialize() {
        C('WeixinServ', require (CONF_PATH . 'configWeixinServ.php'));
        if (C('WeixinServ.LOG_PATH'))
            C('LOG_PATH', C('WeixinServ.LOG_PATH') . "TEST_"); // ���¶���Ŀ־Ŀ¼
    }

    /* ��ں��� */
    public function index() {
        $postStr = file_get_contents('php://input');
        $this->log("΢�� component_verify_ticket :" . $postStr);
        $this->log(
            'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER["SERVER_PORT"] .
                 $_SERVER["REQUEST_URI"]);
        $wx_grant = D('WeiXinGrant', 'Service');
        $wx_grant->init();
        $res_str = $wx_grant->api_service_notify_decrypt($postStr, 
            $_REQUEST['msg_signature'], $_REQUEST['timestamp'], 
            $_REQUEST['nonce']);
        $this->log("dercypt :" . print_r($res_str, true));
    }

    public function auth() {
        $postStr = file_get_contents('php://input');
        $this->log("΢�� component_verify_ticket :" . $postStr);
        $this->log(
            'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER["SERVER_PORT"] .
                 $_SERVER["REQUEST_URI"]);
        $wx_grant = D('WeiXinGrant', 'Service');
        $wx_grant->init();
        $wx_grant->set_weixin_info($_REQUEST['node_id'], $_REQUEST['auth_code']);
        header('Location: ' . $_REQUEST['header_url']);
    }
    // ��ȡ��Ϣ����Ȼ��ת��ԭ�����ַ
    public function message() {
        $postStr = file_get_contents('php://input');
        $this->log("΢�� component_message :" . $postStr);
        $this->log("΢�� component_message app_id:" . $_REQUEST['appid']);
        $this->log(
            'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER["SERVER_PORT"] .
                 $_SERVER["REQUEST_URI"]);
        
        $wx_grant = D('WeiXinGrant', 'Service');
        $wx_grant->init();
        $res_str = $wx_grant->msg_redirect($postStr, $_REQUEST['msg_signature'], 
            $_REQUEST['timestamp'], $_REQUEST['nonce'], $_REQUEST['appid']);
        $this->log("΢�� component_message res_str :" . $res_str);
        echo $res_str;
    }

    public function jump() {
        // $url =
        // 'https://mp.weixin.qq.com/cgi-bin/componentloginpage?component_appid=wx57031ac296b60d74&pre_auth_code=Lvj22nFYxpvX51IzJELQsykgjP-VXzfCeq10CVCKSla1yBIDXHwJRsf5FqmQGfhq&redirect_uri=http%3A%2F%2Ftest.wangcaio2o.com';
        // $url =
        // 'http://222.44.51.34:3700/cgi-bin/componentloginpage?component_appid=wx57031ac296b60d74&pre_auth_code=Lvj22nFYxpvX51IzJELQsykgjP-VXzfCeq10CVCKSla1yBIDXHwJRsf5FqmQGfhq&redirect_uri=http%3A%2F%2Ftest.wangcaio2o.com';
        // $url =
        // 'http://221.181.75.20/jienu/jump.php?component_appid=wx57031ac296b60d74&pre_auth_code=Lvj22nFYxpvX51IzJELQsykgjP-VXzfCeq10CVCKSla1yBIDXHwJRsf5FqmQGfhq&redirect_uri=http%3A%2F%2Ftest.wangcaio2o.com';
        // header('Location:'.$url);
        echo '<a href="http://222.44.51.34:2333/cgi-bin/componentloginpage?component_appid=wx57031ac296b60d74&pre_auth_code=6qbCPICJawny2xGmSrvccS64WGxoTyDtSnaYSxd7oE9iQJHNAsVie2Q5zR8LRnNA&redirect_uri=http%3A%2F%2Ftest.wangcaio2o.com%2Findex.php%3Fg%3DWeixinServ%26m%3DService%26a%3Dauth%26node_id%3D00012070%26header_url%3Dhttp%253A%252F%252Ftest.wangcaio2o.com">testttttttttttttttttt</a>';
    }
    
    // ����
    public function test() {
        $postStr = file_get_contents('php://input');
        $this->log("΢�� component_message :" . $postStr);
        $this->log(
            'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER["SERVER_PORT"] .
                 $_SERVER["REQUEST_URI"]);
        
        $wx_grant = D('WeiXinCard', 'Service');
        $wx_grant->init_by_node_id('00023332');
        $this->log("΢�� component_message :" . $wx_grant->create(376));
        ;
    }

    public function test1() {
        $postStr = file_get_contents('php://input');
        $this->log("΢�� component_message :" . $postStr);
        $this->log(
            'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER["SERVER_PORT"] .
                 $_SERVER["REQUEST_URI"]);
        
        $wx_grant = D('WeiXinCard', 'Service');
        $wx_grant->init_by_node_id('00023332');
        $this->log(
            "΢�� component_message :" . $wx_grant->add_assist_number('xxxxxx', 
                '00023332', '14121526123'));
        ;
    }

    public function test2() {
        $batch_channel_info['id'] = 11;
        $pay_token = '2222';
        echo C('CURRENT_DOMAIN') . U('Label/Label/index', 
            array(
                'id' => $batch_channel_info['id'], 
                'pay_token' => $pay_token));
        die();
        $postStr = file_get_contents('php://input');
        $this->log("΢�� component_message :" . $postStr);
        $this->log(
            'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER["SERVER_PORT"] .
                 $_SERVER["REQUEST_URI"]);
        
        $wx_grant = D('WeiXinCard', 'Service');
        // $wx_grant->init_by_node_id('00018419');
        // print_r($wx_grant->create_white_user(array('xietangm','figo1668','Mansfield_young','zxh_32701075')));
        $wx_grant->init_by_node_id('00023332');
        print_r(
            $wx_grant->create_white_user(
                array(
                    'chini520', 
                    'Mansfield_young', 
                    'zxh_32701075')));
        // http://www.wangcaio2o.com/index.php?g=WeixinServ&m=Test&a=test2
        // https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=gQGD8DoAAAAAAAAAASxodHRwOi8vd2VpeGluLnFxLmNvbS9xLzUwd2MwQzdtWmVzY0RfNVVCV2VSAAIET22SVAMEAAAAAA==
    }

    public function test3() {
        $postStr = file_get_contents('php://input');
        $this->log("΢�� component_message :" . $postStr);
        $this->log(
            'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER["SERVER_PORT"] .
                 $_SERVER["REQUEST_URI"]);
        
        $wx_grant = D('WeiXinCard', 'Service');
        $wx_grant->init_by_node_id('00023332');
        // $this->log("΢�� component_message
        // :".print_r($wx_grant->create_wx_qrcode('pVTJst9wCVfARpVzCNhDTxKlywR4',
        // '7777175143013890'), true));
        $this->log(
            "΢�� component_message :" . print_r(
                $wx_grant->card_consume('gw0000101226', '7063203581217159'), 
                true));
        // http://test.wangcaio2o.com/index.php?g=WeixinServ&m=Test&a=test2
    }

    public function test_native_pay() {
        $postStr = file_get_contents('php://input');
        $this->log("΢�� test_native_pay :" . $postStr);
        $this->log(
            'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER["SERVER_PORT"] .
                 $_SERVER["REQUEST_URI"]);
        
        $wx_grant = D('WeiXinPay', 'Service');
        $wx_grant->init();
        echo $wx_grant->api_bizpayurl(
            array(
                'product_id' => '7063203581217159'));
    }

    public function test_verify() {
        $postStr = file_get_contents('php://input');
        $this->log("΢�� test_native_pay :" . $postStr);
        $this->log(
            'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER["SERVER_PORT"] .
                 $_SERVER["REQUEST_URI"]);
        
        $wx_grant = D('PosVerify', 'Service');
        $wx_grant->doPosVerify('0001027891', '6609678525569359');
        // $wx_grant->doPosReversal('0001027891',
        // '000000320508','6609678525569359');
        // $wx_grant->doPosCancel('0001027891',
        // '000000594305','6609678525569359');
        var_dump($wx_grant);
    }

    public function test_native_pay_response() {
        $postStr = file_get_contents('php://input');
        $this->log("΢�� test_native_pay :" . $postStr);
        $this->log(
            'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER["SERVER_PORT"] .
                 $_SERVER["REQUEST_URI"]);
        
        $wx_grant = D('WeiXinPay', 'Service');
        $wx_grant->init();
        /*
         * //�µ� $in_arr['body'] = 'goods_detail'; //��Ʒ���� String(32)
         * $in_arr['out_trade_no'] = '706320358121715911111'; //�̻�������
         * String(32) $in_arr['total_fee'] = '1'; //�ܽ�� int ��Ϊ��λ
         * $in_arr['spbill_create_ip'] = '222.44.51.34'; //�û��ն�IP String(16)
         * APP����ҳ֧���ύ�û���ip��Native֧�������΢��֧��API�Ļ���IP��
         * $in_arr['notify_url'] = 'http://www.baidu.com/'; //֪ͨ��ַ String(256)
         * $in_arr['trade_type'] = 'NATIVE'; //�������� String(16)
         * $in_arr['product_id'] = '7063203581217159'; //�������� String(32)
         * ����Ϊ�� $arr = $wx_grant->api_unifiedorder($in_arr); var_dump($arr );
         */
        // ��� prepay_id wx20150609141228aa03a9a87c0840811077
        // У��
        $arr = $wx_grant->api_native_notify_verify($postStr);
        $this->log(print_r($arr, true));
        // ������Ӧ
        $in_arr['prepay_id'] = 'wx20150609141228aa03a9a87c0840811077';
        $arr = $wx_grant->api_native_notify_response($in_arr);
        $this->log(print_r($arr, true));
    }

    public function test_yz() {
        $postStr = file_get_contents('php://input');
        $this->log("΢�� component_message :" . $postStr);
        $this->log(
            'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER["SERVER_PORT"] .
                 $_SERVER["REQUEST_URI"]);
        $node_id = '00002101';
        $wx_grant = D('WeiXinCard', 'Service');
        // $wx_grant->init('wx9115d215042ac916',
        // 'bd9c4bfec5eaa2f8a830eb482823ec65' ,
        // '19tVwejXMO-takA7v8aSvvlX32ayPSKxKWdI8CBjieRVqniXhIj5yC7FQziyiiiG90fc2ysJxctNIQ-kULLgk72wt7EcmKl6oninldPB-z4');
        $wx_grant->init_by_node_id($node_id);
        
        $rs = $wx_grant->store_batch_get();
        /*
         * //���µ����� foreach($rs as $v){ $store_info['wx_store_id'] =
         * $v['id']; $r = M('tstore_info')->where("node_id ='".$node_id."' and
         * wx_store_id is null and store_name = '".
         * $v['name']."'")->save($store_info); //echo M()->_sql();
         * //$wx_grant->store_delete($v['id']); }
         */
        // ѭ��ɾ��
        foreach ($rs as $v) {
            $r = M('tstore_info')->where(
                "node_id = '" . $node_id . "' and wx_store_id ='" . $v['id'] .
                     "'")->find();
            if (! $r) {
                $wx_grant->store_delete($v['id']);
            } else {
                echo $v['id'];
            }
        }
        exit();
        // �޸� pVTJst4Bo91t5wn0_QpJN4_btpO0
        $rs = M('twx_card_type')->where(
            "node_id = '" . $node_id . "' and card_id is not null")->select();
        foreach ($rs as $v) {
            $arr = explode(",", $v['store_id_list']);
            $map['store_id'] = array(
                'in', 
                $arr);
            $tstore_info_list = M('tstore_info')->where($map)->select();
            $location_id_list = array();
            if ($tstore_info_list) {
                foreach ($tstore_info_list as $t) {
                    if ($t['wx_store_id'] != null)
                        $location_id_list[] = intval($t['wx_store_id']);
                }
                $wx_grant->card_type_edit($v['card_id'], $v['card_type'], 
                    $location_id_list);
            }
        }
        // print_r($wx_grant->store_batch_get());
        // print_r($wx_grant->store_delete('287876678'));
    }

    public function gen_qrcode() {
        set_time_limit(0);
        $start = 188010000000;
        $end = 188010000499;
        $RemoteRequest = D('RemoteRequest', 'Service');
        $url = 'http://www.yima.yo/index.php?id=';
        for ($i = $start; $i <= $end; $i ++) {
            $content_url = $url . $i;
            echo $content_url;
            // ���ɶ���
            $arr = array(
                'CreateShortUrlReq' => array(
                    'SystemID' => C('ISS_SYSTEM_ID'), 
                    'TransactionID' => time() . rand(10000, 99999), 
                    'OriginUrl' => "<![CDATA[" . $content_url . "]]>"));
            $ret_arr = $RemoteRequest->GetShortUrl($arr);
            $short_url = $ret_arr['ShortUrl'];
            $filename = "/tmp/1/" . $i;
            $this->MakeCodeImg($short_url, $is_down = true, $type = '', 
                $log_dir = '', $filename, $color = '', $ap_arr = '');
            die();
        }
    }

    public function MakeCodeImg($url = '', $is_down = false, $type = '', $log_dir = '', 
        $filename = '', $color = '', $ap_arr = '') {
        if (empty($url))
            exit();
        import('@.Action.WeixinServ.phpqrcode') or die('include file fail.');
        $size_arr = C('SIZE_TYPE_ARR');
        empty($type) ? $size = 4 : $size = $size_arr[$type];
        empty($filename) ? $filename = time() . ".eps" : $filename .= '.eps';
        // $code = QRcode::png($url, $filename , '1', $size , $margin = 2,
        // false,$log_dir,$color,$ap_arr);
        $code = QRcode::eps($url, $filename, 'M', $size, 2, false, 0xFFFFFF, 
            $color_select);
    }

    public function restat_pos_day_count() {
        $postStr = file_get_contents('php://input');
        $this->log("΢�� component_message :" . $postStr);
        $this->log(
            'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER["SERVER_PORT"] .
                 $_SERVER["REQUEST_URI"]);
        $start_date = '2015-05-28';
        $end_date = '2015-06-05';
        $data_list = M('tpos_day_count')->where(
            "pos_id = '0000000000' and b_id = 0 and trans_date >= '" .
                 $start_date . "' and trans_date <= '" . $end_date . "' ")->select();
        echo M()->_sql();
        foreach ($data_list as $data) {
            $where = "trans_type = '0001' and (status = '0' or status = '1') and trans_time like '" .
                 str_replace("-", "", $data['trans_date']) .
                 "%'  and batch_no = '" . $data['batch_no'] .
                 "' and goods_id = '" . $data['goods_id'] . "'";
            $rs = M('tbarcode_trace')->where($where)
                ->field('SUM(price) as send_amt, count(*) as send_num')
                ->find();
            if (! $rs)
                echo M()->_sql();
            $new_value['send_num'] = $rs['send_num'];
            $new_value['send_amt'] = $rs['send_amt'];
            $rs = M('tpos_day_count')->where("id = " . $data['id'])->save(
                $new_value);
            if ($rs === false)
                echo M()->_sql();
        }
    }

    public function test_wfx() {
        $postStr = file_get_contents('php://input');
        $this->log("΢�� component_message :" . $postStr);
        $this->log(
            'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER["SERVER_PORT"] .
                 $_SERVER["REQUEST_URI"]);
        // echo U('Home/Login/showLogin');
        $wx_grant = D('Wfx', 'Service');
        $wx_grant->return_bonus('00026092', '1508145562326510', '254');
        // var_dump($wx_grant->get_bonus_config('00026092','140', 5233));
        // var_dump($wx_grant->get_saler_info_by_phone('00023332',
        // '13564896047'));
        // var_dump($wx_grant->get_bind_saler('00023332', '13564896047'));
        // var_dump($wx_grant->return_bonus('00023332', '1505193021765603', 5,
        // '13501831482', 'лXX')); //��Ʒ
        // var_dump($wx_grant->return_bonus('00023332', '1505199253601760', 5,
        // '13501831482', 'лXX')); //��Ʒ
    }

    public function create_code() {
        $postStr = file_get_contents('php://input');
        $this->log("΢�� component_message :" . $postStr);
        $this->log(
            'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER["SERVER_PORT"] .
                 $_SERVER["REQUEST_URI"]);
        
        $wx_grant = D('WeiXinCard', 'Service');
        $wx_grant->init_by_node_id('00023332');
        // $this->log("΢�� component_message
        // :".print_r($wx_grant->create_wx_qrcode('pVTJst9wCVfARpVzCNhDTxKlywR4',
        // '7777175143013890'), true));
        $this->log(
            "΢�� component_message :" . print_r(
                $wx_grant->create_code('7063786743808613', 
                    'oyJjks9---nOKnKlkwqR3RqL4dUQ', 
                    'pVTJst91cQj2gsbHVxYPkoM2QZPY', '', 0), true));
        $this->log(
            "΢�� component_message :" . print_r(
                $wx_grant->create_code('7063543681515697', 
                    'oyJjks9---nOKnKlkwqR3RqL4dUQ', 
                    'pVTJst91cQj2gsbHVxYPkoM2QZPY', '', 0), true));
        $this->log(
            "΢�� component_message :" . print_r(
                $wx_grant->create_code('7063617323971134', 
                    'oyJjks9---nOKnKlkwqR3RqL4dUQ', 
                    'pVTJst7U-KWQ2_a9x31jZ-FgujQU', '', 0), true));
        // http://test.wangcaio2o.com/index.php?g=WeixinServ&m=Test&a=create_code
    }

    public function get_list() {
        $postStr = file_get_contents('php://input');
        $this->log("΢�� component_message :" . $postStr);
        $this->log(
            'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER["SERVER_PORT"] .
                 $_SERVER["REQUEST_URI"]);
        set_time_limit(0);
        $wx_grant = D('WeiXinCard', 'Service');
        $wx_grant->init_by_node_id('00039540');
        $card_list = M('twx_assist_number')->where(
            "node_id = '00039540' and status = '1' ")->select();
        // $card_list = M('twx_assist_number')->where("node_id = '00039540'
        // ")->limit(100)->select();
        $count = 0;
        $err_count = 0;
        foreach ($card_list as $card_info) {
            $this->log("get card no get list scan id: " . $count + $err_count);
            $batch_info = M('tbatch_info')->where(
                "id='" . $card_info['card_batch_id'] . "'")->find();
            $result = $wx_grant->get_code_status($batch_info['card_id'], 
                $card_info['assist_number']);
            if ($result['errcode'] == 0 && $result['openid'] != null) {
                $count ++;
                $this->log(
                    "get card no get  " . $card_info['assist_number'] .
                         " openid is :" . $result['openid']);
                $this->log(
                    "reget card :" . print_r(
                        $wx_grant->create_code($card_info['assist_number'], 
                            $result['openid'], $batch_info['card_id'], '', 0), 
                        true));
            } else if ($result['errcode'] == 40056) {
                $err_count ++;
            }
        }
        $this->log("get card no get list count: " . $count);
        $this->log("get card no get list error count: " . $err_count);
        // $this->log("΢�� component_message
        // :".print_r($wx_grant->create_code('6619416954499805',
        // 'oXDmpjuGA0glUrZhS8vgvcfpHvzM', 'pXDmpjpFDQmJsDBVsxA45EKY5VQs', '',
        // 0),
        // true));
        // http://test.wangcaio2o.com/index.php?g=WeixinServ&m=Test&a=test2
    }

    public function getAllFansLists() {
        $weixin_info_list = M('tweixin_info')->where(
            "app_id IS NOT NULL AND getfans_flag = '0'")->select();
        foreach ($weixin_info_list as $weixin_info) {
            $_REQUEST['node_id'] = $weixin_info['node_id'];
            $this->log("get fanslist  :[" . $_REQUEST['node_id'] . "]");
            $this->getFansList();
        }
    }

    public function getFansList() {
        $this->wx = D('WeiXin', 'Service');
        $this->node_id = $_REQUEST['node_id'];
        
        $weixin_info = M('TweixinInfo')->where(
            "node_id='" . $this->node_id . "'")->find();
        /*
         * if ($weixin_info['getfans_flag'] == '1'){ $this->log("��˿��Ϣ��ͬ��
         * :".$this->node_id ); echo '��˿��Ϣ��ͬ��'; return; }
         */
        $weixin_info_save['getfans_flag'] = '3';
        $rs = M('TweixinInfo')->where("node_id='" . $this->node_id . "'")->save(
            $weixin_info_save);
        if ($rs === false) {
            $this->log("�����־ʧ�� :" . $this->node_id);
            echo '�����־ʧ��';
            return;
        }
        
        if ($weixin_info['app_id'] == null) {
            $this->log("ͬ������δ���� :" . $this->node_id);
            echo 'ͬ������δ����';
            return;
        }
        $this->log("start sync weixin fans :" . $this->node_id);
        set_time_limit(0);
        
        $this->access_token = $weixin_info['app_access_token'];
        $this->app_id = $weixin_info['app_id'];
        $this->app_secret = $weixin_info['app_secret'];
        $this->node_wx_id = $weixin_info['node_wx_id'];
        if ($this->app_secret == null) {
            $this->app_secret = '1';
        }
        
        $openid_list = $this->wx->getFansList($this->access_token, '');
        if ($openid_list['errcode'] == '40001' ||
             $openid_list['errcode'] == '42001' ||
             $openid_list['errcode'] == '41001') // ��Ҫ����access_token
{
            $access_token = $this->wx->getAccessToken($this->app_id, 
                $this->app_secret);
            $wx_info = array();
            $wx_info['app_access_token'] = $access_token;
            M('TweixinInfo')->where("node_id='" . $this->node_id . "'")->save(
                $wx_info);
            
            $this->access_token = $access_token;
            $openid_list = $this->wx->getFansList($this->access_token, '');
        }
        if ($openid_list['count'] > '0') {
            foreach ($openid_list['data']['openid'] as &$this->user_name) {
                $this->fansAddUpdate();
            }
            
            $newwx_info = array();
            $newwx_info['getfans_flag'] = '1';
            M('TweixinInfo')->where("node_id='" . $this->node_id . "'")->save(
                $newwx_info);
        } else {
            $this->log("get fanslist error:[" . $openid_list['errcode'] . "]");
        }
        
        while (($openid_list['next_openid'] != null) &&
             strlen($openid_list['next_openid']) > 0) {
                $openid_list = $this->wx->getFansList($this->access_token, 
                    $openid_list['next_openid']);
            if ($openid_list['count'] > '0') {
                foreach ($openid_list['data']['openid'] as &$this->user_name) {
                    $this->fansAddUpdate();
                }
                
                $newwx_info = array();
                $newwx_info['getfans_flag'] = '1';
                M('TweixinInfo')->where("node_id='" . $this->node_id . "'")->save(
                    $newwx_info);
            } else {
                $this->log(
                    "get fanslist error:[" . $openid_list['errcode'] . "]");
            }
        }
        ;
        $this->log("end sync weixin fans :" . $this->node_id);
    }

    private function fansAddUpdate() {
        $where = "openid='" . $this->user_name . "'";
        $rs = M('TwxUser')->where($where)->find();
        
        $wx_user = $this->wx->getFansInfo($this->user_name, $this->access_token);
        if ($wx_user['errcode'] == '40001' || $wx_user['errcode'] == '42001' ||
             $wx_user['errcode'] == '41001') // ��Ҫ����access_token
{
            $access_token = $this->wx->getAccessToken($this->app_id, 
                $this->app_secret);
            $wx_info = array();
            $wx_info['app_access_token'] = $access_token;
            M('TweixinInfo')->where("node_id='" . $this->node_id . "'")->save(
                $wx_info);
            
            $this->access_token = $access_token;
            $wx_user = $this->wx->getFansInfo($this->user_name, 
                $this->access_token);
        }
        if (! isset($wx_user['errcode'])) {
            $wx_user['node_id'] = $this->node_id;
            $wx_user['node_wx_id'] = $this->node_wx_id;
            $wx_user['group_id'] = 0;
            if (! $rs) {
                $rs = M('TwxUser')->add($wx_user);
            } else {
                $rs = M('TwxUser')->where($where)->save($wx_user);
            }
        } else {
            $this->log("get fans error:[" . $wx_user['errcode'] . "]");
        }
    }
    
    // ��¼��־
    protected function log($msg, $level = Log::INFO) {
        // trace('Log.'.$level.':'.$msg);
        Log::write($msg, '[' . getmypid() . ']' . $level);
    }
}
