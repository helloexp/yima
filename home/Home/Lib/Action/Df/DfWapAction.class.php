<?php

/**
 * Created by PhpStorm. User: Administrator Date: 2015/5/4 Time: 16:48
 */
class DfWapAction extends Action {
    // 跳转回来地址
    const __TRUE_BACK_URL__ = '__TRUE_BACK_URL__';
    // 微信用户id
    public $wxid;

    public $openId;
    // 微信openId
    public $js_global = array();

    public $wap_sess_name = '';

    const BATCH_TYPE_SPRING = 1001;
    // 会员卡
    public $expiresTime = 600;

    public $id = '';

    public $df_userinfo;

    public $node_id;

    public $df_openid = "";

    public function _initialize() {
        $this->node_id = C('df.node_id');
        // 设置积分商城ID
        $this->id = C('df.id');
        $this->wap_sess_name = 'node_wxid_' . $this->node_id;
        // 定义一个数组
        $wein_appid = session($this->wap_sess_name);
        if ($_GET['_SID_'] == 'w') {
            $wein_appid['openid'] = "oGLqQs3iabSmZlrhL5uLiptl4257";
            session($this->wap_sess_name, $wein_appid['openid']);
        }
        if (empty($wein_appid['openid'])) {
            $this->_df_checklogin();
        }
        // 跳转到积分商城ID
        $this->assign("id", $this->id);
        $this->wxid = session($this->wap_sess_name);
        $this->df_openid = $wein_appid['openid'];
    }

    public function Dfmember_card() {
        // 通过授权用户openid查询用户积分信息
        $this->check_weixinlogin();
        $memberl_ist = M("tfb_df_member")->where(
            array(
                'openid' => $this->df_openid))->find();
        if (empty($memberl_ist) || empty($memberl_ist['mobile'])) {
            $this->redirect("Df/DfWap/DfLogin_index");
        }
        $this->assign('list', $memberl_ist);
        $this->display("Dfwap/Dfmember_card");
    }
    
    // 注册
    public function DfLogin_index() {
        $this->check_weixinlogin();
        $memberl_ist = M("tfb_df_member")->where(
            array(
                'openid' => $this->df_openid))->find();
        if ($memberl_ist && $memberl_ist['mobile']) {
            $this->redirect("Df/DfWap/Dfmember_card");
        }
        $this->display("Dfwap/DfLogin_index");
    }
    
    // 积分商城
    public function Dfscore_shop() {
        $this->display("Dfwap/Dfscore_shop");
    }
    
    // 积分商城详情
    public function Dfscore_shopinfo() {
        $this->display("Dfwap/Dfscore_shopinfo");
    }
    
    // 我的优惠券
    public function DfCode() {
        $this->check_weixinlogin();
        import('ORG.Util.Page'); // 导入分页类
        $where = array();
        $where['a.df_openid'] = $this->df_openid;
        $where['t.node_id'] = $this->node_id;
        // 商品销售不显示在我的优惠中
        $status = I('status', '', 'trim');
        if ($status !== '' && in_array($status, 
            array(
                0, 
                2))) {
            if ($status == 2) {
                $where['t.use_status'] = $status;
                $this->assign("card_status", $status);
            }
            if ($status == 0) {
                $where[] = 't.use_status = 0 or t.use_status = 1';
                $where[] = "t.end_time >= " . date('YmdHis') . "";
            }
        }
        
        if (3 == $status) {
            $this->assign("card_status", $status);
            $where[] = "t.end_time < " . date('YmdHis') . "";
        }
        $where[] = "t.id=a.barcode_id";
        $count = M()->table("tfb_dftrace_relation a,tbarcode_trace t")
            ->field(
            't.goods_id,t.assist_number,t.barcode_bmp,t.use_status,t.begin_time,t.end_time,g.id,g.goods_name,g.goods_type,g.status')
            ->join('tgoods_info g ON g.goods_id=t.goods_id')
            ->order('t.trans_time Desc')
            ->where($where)
            ->count();
        $Page = new Page($count, 10);
        if ($_GET['p'] > ceil($count / 10) && $this->isAjax())
            return;
        
        $laberLists = M()->table("tfb_dftrace_relation a,tbarcode_trace t")
            ->join('tgoods_info g ON g.goods_id=t.goods_id')
            ->field(
            't.goods_id,t.assist_number,t.barcode_bmp,t.use_status,t.id,t.begin_time,t.end_time,g.goods_name,g.goods_type,g.status')
            ->order('t.trans_time Desc')
            ->where($where)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        // 组装下一页url
        $nowPage = empty($_GET['p']) ? 1 : $_GET['p'];
        $nexUrl = U('Df/DfWap/DfCode', 
            array(
                'status' => $status, 
                'p' => ($nowPage + 1)));
        foreach ($laberLists as $k => $v) {
            // 过期设置状态为3
            if (($v['end_time'] < date('YmdHis'))) {
                $laberLists[$k]['use_status'] = 3;
            }
            
            // 商品类型 0-优惠券 1-代金券 2-实物券 3-储值卡(弃用) 6-商品销售 9-翼码免费卡券 10-积分
            switch ($v['goods_type']) {
                case 0:
                    $laberLists[$k]['goods_type'] = "优惠券";
                    break;
                case 1:
                    $laberLists[$k]['goods_type'] = "代金券";
                    break;
                case 2:
                    $laberLists[$k]['goods_type'] = "实物券";
                    break;
                case 3:
                    $laberLists[$k]['goods_type'] = "储值卡";
                    break;
                case 6:
                    $laberLists[$k]['goods_type'] = "商品销售";
                    break;
                case 9:
                    $laberLists[$k]['goods_type'] = "翼码免费卡券";
                    break;
                case 10:
                    $laberLists[$k]['goods_type'] = "积分";
                    break;
                default:
                    break;
            }
        }
        $this->assign('status', $status);
        $this->assign('lists', $laberLists);
        $this->assign('nextUrl', $nexUrl);
        $this->display("Dfwap/DfCode");
    }

    public function DfCodeinfo() {
        $this->check_weixinlogin();
        $where['a.df_openid'] = $this->df_openid;
        $id = I('id', 0, 'intval');
        if (0 != $id) {
            $where['t.id'] = $id;
        }
        $where['t.node_id'] = $this->node_id;
        $where[] = "t.id=a.barcode_id";
        $laberInfo = M()->table("tfb_dftrace_relation a,tbarcode_trace t")
            ->join('tgoods_info g ON g.goods_id=t.goods_id')
            ->field(
            'g.pos_group,g.pos_group_type,g.node_id,t.goods_id,t.assist_number,t.barcode_bmp,t.use_status,t.id,g.goods_name,g.goods_type,g.mms_text,g.status,t.begin_time,t.end_time,g.goods_image')
            ->where($where)
            ->find();
        if ($laberInfo['pos_group_type'] == '2') {
            // 可用门店
            $storeInfo = M()->table("tgroup_pos_relation t")->join(
                'tstore_info o ON o.store_id=t.store_id')
                ->where(
                array(
                    't.group_id' => $laberInfo['pos_group']))
                ->field('o.store_name,o.address')
                ->select();
        } elseif ($laberInfo['pos_group_type'] == '1') {
            $storeInfo = M('tstore_info')->where(
                array(
                    'node_id' => $laberInfo['node_id'], 
                    'pos_count' => array(
                        'gt', 
                        '0')))
                ->field('store_name,address')
                ->select();
        }
        
        switch ($laberInfo['goods_type']) {
            case 0:
                $laberInfo['goods_type'] = "优惠券";
                break;
            case 1:
                $laberInfo['goods_type'] = "代金券";
                break;
            case 2:
                $laberInfo['goods_type'] = "实物券";
                break;
            case 3:
                $laberInfo['goods_type'] = "储值卡";
                break;
            case 6:
                $laberInfo['goods_type'] = "商品销售";
                break;
            case 9:
                $laberInfo['goods_type'] = "翼码免费卡券";
                break;
            case 10:
                $laberInfo['goods_type'] = "积分";
                break;
            default:
                break;
        }
        $laberInfo['barcode_bmp'] = $laberInfo['barcode_bmp'] ? 'data:image/png;base64,' .
             base64_encode(
                $this->_bar_resize(base64_decode($laberInfo['barcode_bmp']), 
                    'png')) : '';
        $this->assign('store', $storeInfo);
        $this->assign("id", $id);
        $this->assign('info', $laberInfo);
        $this->display("Dfwap/DfCodeinfo");
    }
    
    // 门店城市显示
    public function Dfstore_select() {
        $province_list = M("tstore_info")->where(
            array(
                "node_id" => $this->node_id, 
                '_string' => "province != ''"))->getField(
            "province,province_code", true);
        // 获取所有门店的信息,筛选出门店的所在城市
        $this->assign("province_list", $province_list);
        $this->display("Dfwap/Dfstore_select");
    }
    
    // 门店列表
    public function Dfstore_list() {
        import('ORG.Util.Page'); // 导入分页类
        $province = I('province');
        if (empty($province)) {
            $this->error("非法操作！");
        }
//        $allow_arr= C('df_store_id');
        $map = array(
            't.node_id' => $this->node_id, 
            't.province_code' => $province
//            '_string'=>"t.store_id in (".$allow_arr.")"
        );
        // 分页和拼装next页面url
        $count = M()->table("tstore_info t")->join(
            'tmarketing_info a on a.defined_one_name=t.id')
            ->where($map)
            ->count();
        $Page = new Page($count, 10);
        if ($_GET['p'] > ceil($count / 10) && $this->isAjax())
            return;
        $store_list = M()->table("tstore_info t")->join(
            'tmarketing_info a on a.defined_one_name=t.id')
            ->where($map)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        // 组装下一页url
        $nowPage = empty($_GET['p']) ? 1 : $_GET['p'];
        $nexUrl = U('Df/DfWap/Dfstore_list', 
            array(
                'province' => $province, 
                'p' => ($nowPage + 1)));
        foreach ($store_list as &$v) {
            $v['city_town'] = D('CityCode')->getCityTown($v['area_code']);
            // 经纬度转换（改用百度地图，取消转换）
            $newLbs = map_baidu_to_GCJ($v['lbs_x'], $v['lbs_y']);
            $v['lbs_x'] = $newLbs['lat'];
            $v['lbs_y'] = $newLbs['lng'];
        }
        unset($v);
        $this->assign("store_list", $store_list);
        $this->assign('nextUrl', $nexUrl);
        $this->assign('mapUrl', $this->getSosoMapUrl());
        $this->display("Dfwap/Dfstore_list");
    }
    
    // 倒计时
    public function Dftime_less() {
        $this->display("Dfwap/Dftime_less");
    }
    
    // DF所有活动列表
    public function ListBatch_index() {
        // $batch_type=C('df').WAPDF_BATCH_TYPE;
        import('ORG.Util.Page'); // 导入分页类
        $map = array(
            't.node_id' => $this->node_id, 
            '_string' => "t.batch_type in (2, 3, 9, 10, 11, 12, 15, 18, 20, 22, 24, 25, 28, 30, 35, 44, 45, 46, 49, 50, 53, 55, 56, 59, 60, 1004)  and t.end_time>= '" .
                 date('YmdHis') . "'", 
                't.status' => 1);
        $map[] = "exists(SELECT * FROM tbatch_channel a where a.id !='' and a.batch_id=t.id and IFNULL(a.end_time,'') = '')";
        $count = M()->table("tmarketing_info t")->field(
            "t.wap_title,t.wap_info,a.id,SELECT MIN(a.id) FROM tbatch_channel a WHERE a.batch_id = t.id AND a.status = '1' AND a.id !='' AND IFNULL(a.end_time,'') = '')")
            ->where($map)
            ->count();
        $Page = new Page($count, 10);
        if ($_GET['p'] > ceil($count / 10) && $this->isAjax())
            return;
        $list = M()->table("tmarketing_info t")->field(
            "t.wap_title,t.wap_info,t.batch_type,(SELECT MIN(a.id) FROM tbatch_channel a WHERE a.batch_id = t.id AND a.status = '1' AND a.id !='' and IFNULL(a.end_time,'') = '') as dfid")
            ->where($map)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        if ($list) {
            foreach ($list as &$info) {
                $info['wap_info'] = strip_tags($info['wap_info']);
                if (mb_strlen($info['wap_info'], 'utf-8') > 100) {
                    $info['wap_info'] = mb_substr($info['wap_info'], 0, 100, 
                        'utf-8') . '……';
                }
            }
        }
        $nowPage = empty($_GET['p']) ? 1 : $_GET['p'];
        $nexUrl = U('Df/DfWap/ListBatch_index', 
            array(
                'p' => ($nowPage + 1)));
        $this->assign('nextUrl', $nexUrl);
        $this->assign('list', $list);
        $this->display("Dfwap/ListBatch_index");
    }

    /**
     * @手机发送动态密码
     */
    public function sendCheckCode() {
        /*
         * //图片校验码 $verify = I('post.verify',null,'mysql_real_escape_string');
         * if(session('verify') != md5($verify)) { $this->error("图片动态密码错误"); }
         */
        $phoneNo = I('phone', null);
        if (! check_str($phoneNo, 
            array(
                'null' => false, 
                'strtype' => 'mobile'), $error)) {
            $this->error("手机号{$error}");
        }
        // 发送频率验证
        $df_groupCheckCode = session('df_groupCheckCode');
        if (! empty($df_groupCheckCode) &&
             (time() - $df_groupCheckCode['add_time']) < $this->expiresTime) {
            $this->error('动态密码发送过于频繁!');
        }
        $num = mt_rand(1000, 9999);
        $num='1111';
        $exptime = $this->expiresTime / 60;
        $text = "您的动态密码是：{$num}，有效期{$exptime}分钟。如非本人操作请忽略。";
        // 通知支撑
        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
                                                                   // 请求参数
        $req_array = array(
            'NotifyReq' => array(
                'TransactionID' => $TransactionID, 
                'ISSPID' => C('MOBILE_ISSPID'), 
                'SystemID' => C('ISS_SYSTEM_ID'), 
                'SendLevel' => '1', 
                'Recipients' => array(
                    'Number' => $phoneNo),  // 手机号
                'SendClass' => 'SMS', 
                'MessageText' => $text,  // 短信内容
                'Subject' => '', 
                'ActivityID' => C('ALIPAY_NOTICE_ACTIVITYID'), 
                'ChannelID' => '', 
                'ExtentCode' => ''));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $resp_array = $RemoteRequest->requestIssServ($req_array);
        // dump($resp_array);exit;
        $ret_msg = $resp_array['NotifyRes']['Status'];
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
             $ret_msg['StatusCode'] != '0001')) {
            $this->error('发送失败' . print_r($resp_array, true) . '0');
        }
        $df_groupCheckCode = array(
            'number' => $num, 
            'add_time' => time(), 
            'phoneNo' => $phoneNo);
        session('df_groupCheckCode', $df_groupCheckCode);
        exit(
            json_encode(
                array(
                    'info' => '发送验证码成功！', 
                    'status' => 1)));
        // 'num'=>$num
        
    }
    // DF注册
    public function df_reg() {
        $this->check_weixinlogin();
        if ($this->isPost()) {
            $name = I('name', '', 'trim');
            $phone = I('phone', 0, 'trim');
            $sex = I('sex');
            $birthday = I('birthday', 0, 'trim');
            $verify = I('verify', 0, 'trim');
            $verifySession = session('df_groupCheckCode');
            // 同时要判断验证码的有效期，是否在设置的时间内
            if (time() - $verifySession['add_time'] > $this->expiresTime) {
                $this->error("手机验证码已经过期！");
            }
            if ($verify == '' || $verify != $verifySession['number']) {
                $this->error("请输入正确的验证码");
            }
            if ($phone == '' || $phone != $verifySession['phoneNo']) {
                $this->error("手机号码不正确！");
            }
            if ($sex != 0 && $sex != 1) {
                $this->error("性别不能为空！");
            }
            if ($birthday == '') {
                $this->error("生日不能为空！");
            }
            if ('' != $name && 0 != $phone && 0 != $verify && 0 != $birthday) {
                // 通过生日得到用户星座
                $data = array(
                    'name' => $name, 
                    'birthday' => str_replace("-", '', "$birthday"), 
                    'sex' => $sex, 
                    'horoscope' => getConstellation(
                        substr(str_replace("-", '', "$birthday"), 4, 2), 
                        substr(str_replace("-", '', "$birthday"), 6, 2), 1), 
                    'mobile' => $phone, 
                    'openid' => $this->df_openid, 
                    'add_time' => date('YmdHis', time()));
                try {
                    M()->startTrans();
                    // 如果传输过来的手机号码已经注册过会员，则提示用户无法注册
                    $success_1 = M("tfb_df_member")->where(
                        array(
                            'mobile' => $phone))->find();
                    if ($success_1) {
                        throw new Exception('该手机号码已经被其他用户注册过，请重新提交！');
                    }
                    // 会员重复注册，需要修改会员信息
                    // open_id从授权里面获取
                    $success_has = M("tfb_df_member")->where(
                        array(
                            'openid' => $this->df_openid))->find();
                    if ($success_has && ! empty($success_has['mobile'])) {
                        throw new Exception('您已经注册过会员了，无法重新注册！');
                    }
                    // 通过post手机号查询导入会员是否有记录
                    $res_import = M("tfb_df_member_import")->where(
                        array(
                            'mobile' => $phone, 
                            'status' => 0))->find();
                    if ($res_import) {
                        if ($res_import['point']) {
                            $data['point'] = $res_import['point'];
                            // 生成积分增加流水记录
                            $import_save_new_3 = array(
                                'openid' => $this->df_openid, 
                                'type' => 3,  // 会员导入减少积分
                                'before_num' => $res_import['point'], 
                                'change_num' => $res_import['point'], 
                                'after_num' => 0, 
                                'relation_id' => $res_import['id'], 
                                'trace_time' => date('YmdHis', time()), 
                                'remark' => "DF注册积分减少");
                            $res_import_z = M("tfb_df_point_trace")->add(
                                $import_save_new_3);
                            if ($res_import_z === false) {
                                throw new Exception('注册失败，会员信息更新失败！');
                            }
                            // inport扣减积分，应生成积分增加流水
                            $import_save_new_4 = array(
                                'openid' => $this->df_openid, 
                                'type' => 4,  // 会员增加积分
                                'before_num' => 0, 
                                'change_num' => $res_import['point'], 
                                'after_num' => $res_import['point'], 
                                'relation_id' => $res_import['id'], 
                                'trace_time' => date('YmdHis', time()), 
                                'remark' => "DF注册积分增加");
                            $res_import_a = M("tfb_df_point_trace")->add(
                                $import_save_new_4);
                            if ($res_import_a === false) {
                                throw new Exception('注册失败，会员信息更新失败！');
                            }
                        }
                        // 如果有记录，则执行将inport表里面的数据复制过来
                        $data['city_code'] = $res_import['city_code'];
                        $data['add_time'] = date('YmdHis', time());
                        $data['point'] = $res_import['point'];
                        if ($success_has) {
                            $success_df = M("tfb_df_member")->where(
                                array(
                                    'openid' => $this->df_openid))->save($data);
                        } else {
                            $success_df = M("tfb_df_member")->add($data);
                        }
                        if ($success_df === false) {
                            throw new Exception('注册失败，请重新注册！');
                        }
                        // 同时更新import表里面的状态
                        $date = array(
                            'status' => 1, 
                            'point' => 0);
                        $success_df_status = M("tfb_df_member_import")->where(
                            array(
                                'mobile' => $phone))->save($date);
                        if ($success_df_status === false) {
                            throw new Exception('注册失败，导入会员状态修改失败！');
                        }
                    } else {
                        // 用户手机号码，为飞导入会员里面的
                        if ($success_has) {
                            $success_df = M("tfb_df_member")->where(
                                array(
                                    'openid' => $this->df_openid))->save($data);
                        } else {
                            $success_df = M("tfb_df_member")->add($data);
                        }
                        if ($success_df === false) {
                            throw new Exception('注册失败，会员信息失败！');
                        }
                    }
                    M()->commit();
                    $this->success('注册成功');
                } catch (Exception $e) {
                    M()->rollback();
                    $this->error("注册失败,原因：" . $e->getMessage());
                }
            }
        }
    }
    
    // 修改会员手机号码
    public function change_phone() {
        $this->check_weixinlogin();
        // 初始化对用户进行隐私授权，如果没有Openid则需要自动获取
        if ($this->isPost()) {
            $phone = I('phone', 0, 'trim');
            $verify = I('verify', 0, 'trim');
            $verifySession = session('df_groupCheckCode');
            // 同时要判断验证码的有效期，是否在设置的时间内
            if (time() - $verifySession['add_time'] > $this->expiresTime) {
                $this->error("手机验证码已经过期！");
            }
            if ($verify == '' || $verify != $verifySession['number']) {
                $this->error("请输入正确的验证码");
            }
            if ($phone == '' || $phone != $verifySession['phoneNo']) {
                $this->error("手机号码不正确！");
            }
            try {
                M()->startTrans();
                // 会员重复注册，需要修改会员信息
                // open_id从授权里面获取
                $success_has = M("tfb_df_member")->where(
                    array(
                        'openid' => $this->df_openid))->find();
                if (! $success_has) {
                    throw new Exception('您不是会员，无法修改用户信息！');
                }
                if ($success_has['mobile'] == $phone) {
                    throw new Exception('您修改的号码和您注册号码一致！');
                }
                // open_id从授权里面获取
                $success_has_3 = M("tfb_df_member")->where(
                    array(
                        'mobile' => $phone))->find();
                if ($success_has_3) {
                    throw new Exception('您输入的手机号码已经是会员，请重新输入！');
                }
                // 查询以前的会员信息是否为import里面的会员，如果为import会员，则需要使用修改状态
                $old_member_find = M("tfb_df_member_import")->where(
                    array(
                        'mobile' => $success_has['mobile'], 
                        'status' => 1))->find();
                if ($old_member_find) {
                    $date = array(
                        'status' => 0);
                    // 修改老会员状态修改为0
                    $old_member_find_1 = M("tfb_df_member_import")->where(
                        array(
                            'mobile' => $success_has['mobile']))->save($date);
                    if ($old_member_find_1 === false) {
                        throw new Exception('注册失败，会员信息更新失败！');
                    }
                }
                
                // 判断用户传输的手机号码是否为导入表格中的新会员
                $new_member_find = M("tfb_df_member_import")->where(
                    array(
                        'mobile' => $phone, 
                        'status' => 0))->find();
                // 如果有，需要将导入表格中的import里面会员信息更新到用户表格中
                if ($new_member_find) {
                    $date = array(
                        'status' => 1, 
                        'point' => 0);
                    // 如果传输过来的手机号码为export表格里面会员，修改import的会员状态
                    $change_df_status = M("tfb_df_member_import")->where(
                        array(
                            'mobile' => $phone))->save($date);
                    if ($change_df_status === false) {
                        throw new Exception('注册失败，会员信息更新失败！');
                    }
                    // member表格里面增加积分
                    if ($new_member_find['point']) {
                        $data['point'] = $new_member_find['point'];
                        // 生成积分增加流水记录
                        $import_save_new_3 = array(
                            'openid' => $this->df_openid, 
                            'type' => 3,  // 会员导入减少积分
                            'before_num' => $new_member_find['point'], 
                            'change_num' => $new_member_find['point'], 
                            'after_num' => 0, 
                            'relation_id' => $new_member_find['id'], 
                            'trace_time' => date('YmdHis', time()), 
                            'remark' => "原始会员积分同步（原手机号" .
                                 $new_member_find['mobile'] . "）");
                        $res_import_z = M("tfb_df_point_trace")->add(
                            $import_save_new_3);
                        if ($res_import_z === false) {
                            throw new Exception('注册失败，会员信息更新失败！');
                        }
                        // inport扣减积分，应生成积分增加流水
                        $import_save_new_4 = array(
                            'openid' => $this->df_openid, 
                            'type' => 4,  // 会员增加积分
                            'before_num' => $success_has['point'], 
                            'change_num' => $new_member_find['point'], 
                            'after_num' => $new_member_find['point'] +
                                 intval($success_has['point']), 
                                'relation_id' => $new_member_find['id'], 
                                'trace_time' => date('YmdHis', time()), 
                                'remark' => "原始会员积分同步（原手机号" .
                                 $success_has['mobile'] . "）");
                        $res_import_a = M("tfb_df_point_trace")->add(
                            $import_save_new_4);
                        if ($res_import_a === false) {
                            throw new Exception('注册失败，会员信息更新失败！');
                        }
                    }
                    // 积分扣减完毕后，需要更新会员信息，此会员信息为新会员
                    $data['city_code'] = $new_member_find['city_code'];
                    $data['add_time'] = date('YmdHis', time());
                    $data['mobile'] = $new_member_find['mobile'];
                    $data['point'] = intval($new_member_find['point']) +
                         intval($success_has['point']);
                    $success_df = M("tfb_df_member")->where(
                        array(
                            'openid' => $this->df_openid))->save($data);
                    if ($success_df === false) {
                        throw new Exception('注册失败，请重新注册！');
                    }
                } else {
                    $data_new_wap = array(
                        'mobile' => $phone);
                    $success_df_n = M("tfb_df_member")->where(
                        array(
                            'openid' => $this->df_openid))->save($data_new_wap);
                    if ($success_df_n === false) {
                        throw new Exception('修改手机号码失败，请重新修改！');
                    }
                }
                M()->commit();
                $this->success('修改成功');
            } catch (Exception $e) {
                M()->rollback();
                $this->error("修改失败,原因：" . $e->getMessage());
            }
        }
    }

    public function _bar_resize($data, $other) {
        $im = $this->_img_resize($data, 3);
        if ($im !== false) {
            ob_start();
            switch ($other) {
                case 'gif':
                    imagegif($im);
                    break;
                case 'jpg':
                case 'jpeg':
                    imagejpeg($im);
                    break;
                case 'png':
                    imagepng($im);
                    break;
                case 'wbmp':
                    imagewbmp($im);
                    break;
                default:
                    return false;
                    break;
            }
            imagedestroy($im);
            $new_img = ob_get_contents();
            ob_end_clean();
            return $new_img;
        } else {
            return false;
        }
    }

    public function _img_resize($data, $fdbs) {
        // Resize
        $source = imagecreatefromstring($data);
        $s_white_x = 0; //
        $s_white_y = 0; //
        $s_w = imagesx($source); // 原图宽度
        $new_img_width = ($s_w) * $fdbs;
        $new_img_height = $new_img_width;
        
        // 新的偏移量
        $d_white_x = ($new_img_width - $s_w * $fdbs) / 2;
        $d_white_y = $d_white_x;
        
        // Load
        $thumb = imagecreate($new_img_width, $new_img_height);
        // $red = imagecolorallocate($thumb, 255, 255, 255);
        
        imagecopyresized($thumb, $source, $d_white_x, $d_white_y, $s_white_x, 
            $s_white_y, $s_w * $fdbs, $s_w * $fdbs, $s_w, $s_w);
        return $thumb;
    }
    
    // 微信授权登录
    public function _df_checklogin() {
        if (session('?' . $this->wap_sess_name))
            return true;
        $login = false;
        $userid = '';
        $backurl = U('', I('get.'), '', '', true);
        $backurl = urlencode($backurl);
        $jumpurl = U('Df/DFWeixinLoginNode/index', 
            array(
                'id' => $this->id, 
                'type' => 0, 
                'backurl' => $backurl));
        redirect($jumpurl);
    }

    public function check_weixinlogin() {
        $dfInfo = $this->wxid;
        if (empty($dfInfo['openid'])) {
            $this->_df_checklogin();
        } else {
            return;
        }
    }
    
    // 跳转到门店微官网
    public function location() {
        // 获取门店的门店号
        $store_id = I('store_id');
        if (empty($store_id)) {
            $this->error('门店号不能为空！');
        }
        $defined_one_name = M("tstore_info")->where(
            array(
                'store_id' => $store_id))->getField('id');
        $m_id = M("tmarketing_info")->where(
            array(
                'defined_one_name' => $defined_one_name))->getField('id');
        $model = M('tbatch_channel');
        $map = array(
            'batch_id' => $m_id);
        $tbatch_id = $model->where($map)->getField('id');
        $config_url_arr = C('BATCH_WAP_URL');
        $url = $config_url_arr['1003'];
        // 标签
        // 所有字段
        $_GET['wechat_card_js'] = 1;
        $tz_arr = array(
            'id' => $tbatch_id);
        if (! $url)
            $this->error('error url ！');
        $this->redirect(U($url, $tz_arr));
    }
    
    // 微信关注后跳转到门店微官网
    public function location_wx() {
        // 获取门店的门店号
        // 接收微官网关注的跳转的fav_store_id的值
        if (I('fav_store_id') != '') {
            $defined_one_name = I('fav_store_id');
        }
        $m_id = M("tmarketing_info")->where(
            array(
                'defined_one_name' => $defined_one_name))->getField('id');
        $model = M('tbatch_channel');
        $map = array(
            'batch_id' => $m_id);
        $tbatch_id = $model->where($map)->getField('id');
        $config_url_arr = C('BATCH_WAP_URL');
        $url = $config_url_arr['1003'];
        // 标签
        // 所有字段
        $_GET['wechat_card_js'] = 1;
        $tz_arr = array(
            'id' => $tbatch_id);
        if (! $url)
            $this->error('error url ！');
        $this->redirect(U($url, $tz_arr));
    }
    // 获取soso地图url
    /**
     * 获取搜搜地图地址 $startLng 起点经度 $startLat 起点纬度 $endLng 终点经度 $endLat 终点纬度 $key
     * 起点地址||终点地址
     */
    public function getSosoMapUrl($opt = array()) {
        $url = 'http://map.wap.soso.com/x/?';
        $opt = array_merge(
            array(
                'type' => 'drive', 
                'cond' => 1, 
                'traffic' => 'close', 
                'welcomeChange' => 1, 
                'welcomeClose' => 1, 
                'startLng' => null, 
                'startLat' => null, 
                'endLng' => null, 
                'endLat' => null, 
                'key' => ''), $opt);
        $url .= http_build_query($opt, '');
        return $url;
    }
    
    // 我的门店
    public function mystore() {
        $this->check_weixinlogin();
        // 判断用户是否已经关注过门店，如果已经关注过，跳转到指定门店
        $df_openid = session('node_wxid_' . $this->node_id);
        if ($df_openid != "") {
            $openid = $df_openid['openid'];
            $defined_one_name = M("tfb_df_member")->where(
                array(
                    "openid" => $openid))->getField('fav_store_id');
            if (! empty($defined_one_name)) {
                $this->location_wxweb($defined_one_name);
            } else {
                $this->redirect("Df/DfWap/Dfstore_select");
            }
        } else {
            $this->redirect("Df/DfWap/Dfstore_select");
        }
    }
    
    // 微信关注后跳转到门店微官网
    public function location_wxweb($defined_one_name) {
        // 获取门店的门店号
        // 接收微官网关注的跳转的fav_store_id的值
        if (empty($defined_one_name)) {
            $this->error("门店标号为空！");
        }
        $m_id = M("tmarketing_info")->where(
            array(
                'defined_one_name' => $defined_one_name))->getField('id');
        $model = M('tbatch_channel');
        $map = array(
            'batch_id' => $m_id);
        $tbatch_id = $model->where($map)->getField('id');
        $config_url_arr = C('BATCH_WAP_URL');
        $url = $config_url_arr['1003'];
        // 标签
        // 所有字段
        $_GET['wechat_card_js'] = 1;
        $tz_arr = array(
            'id' => $tbatch_id);
        if (! $url)
            $this->error('error url ！');
        $this->redirect(U($url, $tz_arr));
    }
    
    // 地图
    public function Dfstore_plocation() {
        $this->assign('slng', $_REQUEST['lng']);
        $this->assign('slat', $_REQUEST['lat']);
        $this->assign('lng', $_REQUEST['endLng']);
        $this->assign('lat', $_REQUEST['endLat']);
        $this->assign('cityName', $_REQUEST['cityName']);
        $this->assign('des_city', $_REQUEST['des_city']);
        $this->display("Dfwap/Dfstore_plocation");
    }

    public function BaiduMap() {
        $location_url = I("location_url");
        $arr = file_get_contents($location_url);
        $arr = json_decode($arr, true);
        if ($arr) {
            $city_url = "http://api.map.baidu.com/geocoder/v2/?ak=WRzAu3DNewWB4oeOELaczjsM&callback=&output=json&pois=0&location=" .
                 $arr['result'][0]['y'] . "," . $arr['result'][0]['x'];
            $city_url_1 = "http://api.map.baidu.com/geocoder/v2/?ak=WRzAu3DNewWB4oeOELaczjsM&callback=&output=json&pois=0&location=" .
                 $arr['result'][1]['y'] . "," . $arr['result'][1]['x'];
            $city_arr = json_decode(file_get_contents($city_url), true);
            $city_arr_1 = json_decode(file_get_contents($city_url_1), true);
            if ($city_arr && $city_arr_1) {
                exit(
                    json_encode(
                        array(
                            'info' => $arr, 
                            'status' => 1, 
                            "city_start" => $city_arr['result']['addressComponent']['city'], 
                            "city_end" => $city_arr_1['result']['addressComponent']['city'])));
            } else {
                exit(
                    json_encode(
                        array(
                            'info' => "定位失败", 
                            'status' => 0)));
            }
        } else {
            exit(
                json_encode(
                    array(
                        'info' => "定位失败", 
                        'status' => 0)));
        }
    }
}
