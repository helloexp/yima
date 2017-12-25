<?php

/**
 * 工单、
 *
 * @author zhengxd 2014年12月05日
 */
class StoreService {

    public $error = '';

    //public $LoguserID;

    public function __construct() {

    }
    public function aaa(){
        return 12334;
    }

    public function add($getPost,$res) {

        $store = '/Public/Image/defilt.png';
        /* $accs = strstr($getPost['store_pic'],$store);
                   var_dump($accs);
        exit;*/
        // var_dump($getPost['store_pic']);exit;
        // 门店简称
        if (mb_strlen($getPost['store_short_name'], 'utf8') > 10) {
            //$this->error('门店简称不得大于10个字');
            return '门店简称不得大于10个字';exit;
        }

        //福建建行非标处理开始
        if ($res['fjcbcFlag']) {
            if (mb_strlen($getPost['store_name'], 'utf8') > 30) {
               // $this->error('门店详称不得大于30个字');
                return '门店详称不得大于30个字';exit;
            }
        }
        //福建建行非标处理结束

        // 门店详情地址
        if (empty($getPost['address'])) {
           // $this->error('门店地址不能为空');
            return '门店地址不能为空';exit;
        }

        // 门店联系电话
        if ($getPost['store_phone'] != '') {
            if (!is_numeric(str_replace('-', '', $getPost['store_phone']))) {
               // $this->error('门店电话不是纯数字或者-,有非法字符');
                return '门店电话不是纯数字或者-,有非法字符';exit;
            }
        }

        // 姓名
        if (empty($getPost['principal_name'])) {
            //$this->error('请输入姓名');
            return '请输入姓名';exit;
        }

        // 手机
        if (empty($getPost['principal_phone'])) {
            //$this->error('请输入手机号');
            return '请输入手机号';exit;
        } else {
            // 正则验证手机号
            if (preg_match("/^1[3458]{1}\d{9}$/",
                            $getPost['principal_phone']) != 1) {
                //$this->error('请输入正确的手机号');
                return '请输入正确的手机号';exit;
            }
        }

        // 邮箱
        if (!preg_match('/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/',
                $getPost['principal_email'])) {
           // $this->error('邮箱格式不对');
            return '邮箱格式不对';exit;
        }
        //echo  C('TMPL_PARSE_STRING.__PUBLIC__/Label/Image/Shop/dog.png');

        if (strstr($getPost['store_pic'], $store)) {
            $storeimg = C('STATIC_DOMAIN') . '/Home/Public/Image/defilt.png';
        } else {
            $storeimg = C('STATIC_DOMAIN') . '/Home/Upload/'.$getPost['store_pic'];
        }

        // 整理数据，发往支撑
        $req_arr = array();
        $req_arr['SystemID'] = C('ISS_SYSTEM_ID');
        $req_arr['TransactionID'] = time().mt_rand('1000', '9999');
        $req_arr['ISSPID'] = $res['node_id'];
        $req_arr['UserId'] = $res['userId'];
        $req_arr['Url'] = '<![CDATA[旺财会员账户中心]]>';
        $req_arr['CustomNo'] = get_val($getPost, 'custom_no');
        $req_arr['StoreName'] = $getPost['store_short_name'];
        $req_arr['StoreShortName'] = $getPost['store_short_name'];
        $req_arr['ContactName'] = $getPost['principal_name'];
        $req_arr['ContactTel'] = $getPost['principal_phone'];
        $req_arr['ContactPhone'] = $getPost['store_phone'];
        $req_arr['ContactEmail'] = $getPost['principal_email'];
        $req_arr['ImgUrl'] = $storeimg;
        //  var_dump($req_arr['ImgUrl'] );exit;
        // 未被使用到
        // $is_log_img = I('is_log_img', 0, 'intval');
        $req_arr['RegionInfo'] = array(
                'Province' => $getPost['province_code'],
                'City' => $getPost['city_code'],
                'Town' => $getPost['town_code'],
                'Address' => $getPost['address'], );
        $cityInfo = M('tcity_code')->where(
                array(
                        'city_level' => 3,
                        'province_code' => $getPost['province_code'],
                        'city_code' => $getPost['city_code'],
                        'town_code' => $getPost['town_code'], ))->find();
        $qian = array(
                '/\s/',
                '/ /',
                '/\t/',
                '/\n/',
                '/\r/', );
        $hou = array(
                '',
                '',
                '',
                '',
                '', );
        $str = preg_replace($qian, $hou,
                $cityInfo['province'].$cityInfo['city'].$cityInfo['town'].
                $getPost['address']);
        $xyUrl = $this->addUrl($str);
        // DF非标逻辑
        $req_arr['store_pic'] = get_val($getPost, 'store_pic');
        $req_arr['store_introduce'] = get_val($getPost, 'store_introduce');

        $req_result = D('RemoteRequest', 'Service')->requestIssServ(
                array(
                        'CreateStoreExReq' => $req_arr, ));
        $respStatus = isset($req_result['CreateStoreExRes']) ? $req_result['CreateStoreExRes']['Status'] : $req_result['Status'];
        if ($respStatus['StatusCode'] != '0000') {
            $msg = $respStatus['StatusText'] ? $respStatus['StatusText'] : '创建门店失败';
            //$this->error($msg);
            return $msg;exit;
        }
        $respData = $req_result['CreateStoreExRes'];
        $store_id = $respData['StoreId'];
        if (!$store_id) {
            //$this->error('创建支撑门店失败');
            return '创建支撑门店失败';exit;
        }
        // 查询门店号是否存在
        if (M('tstore_info')->where(array('store_id' => $store_id))->count()) {
           // $this->error('门店号['.$store_id.']已经存在。');
            return '门店号['.$store_id.']已经存在。';exit;
        }
        M()->startTrans();

        //获取机构微信智能导航是否设置了全部门店
        $result = M('tweixin_info')
                ->where(['node_id' => $res['nodeId']])
                ->field('setting')
                ->find();
        $json = json_decode($result['setting'], true);
        $wxGpsFlagType = empty($json['location']['wxGpsFlagType']) ? 2 : $json['location']['wxGpsFlagType'];

        // 开始记录到门店表
        $data = array(
                'store_id' => $store_id,
                'node_id' => $res['node_id'],
                'store_name' => $getPost['store_short_name'],
                'store_short_name' => $getPost['store_short_name'],
                'store_desc' => $getPost['store_name'],
                'province_code' => $getPost['province_code'],
                'city_code' => $getPost['city_code'],
                'town_code' => $getPost['town_code'],
                'address' => $getPost['address'],
                'post_code' => $getPost['post_code'],
                'principal_name' => $getPost['principal_name'],
                'principal_position' => $getPost['principal_position'],
            // 'principal_tel'=>$getPost['principal_tel'],
                'principal_phone' => $getPost['principal_phone'],
                'principal_email' => $getPost['principal_email'],
                'custom_no' => $getPost['custom_no'],
                'memo' => $getPost['memo'],
                'status' => 0,
                'add_time' => date('YmdHis'),
                'store_phone' => $getPost['store_phone'],
                'store_email' => $getPost['store_email'],
                'busi_time' => $getPost['busi_time'],
                'store_pic' => $getPost['store_pic'],
                'business_code' => $getPost['business_code'],
                'wx_gps_flag' => $wxGpsFlagType == 1 ? 1 : 0,
                'store_introduce' => $getPost['store_introduce'], );
        if (!empty($xyUrl['x']) && !empty($xyUrl['y'])) {
            $data['lbs_x'] = $xyUrl['x'];
            $data['lbs_y'] = $xyUrl['y'];
        }
        // 情况是:支撑同步先到了，旺财门店入库（主键重复）异常，故先delete
        M('tstore_info')->where("store_id={$store_id}")->delete();

        //福建建行非标处理开始
        if ($res['fjcbcFlag']) {
            $data['store_desc'] = $getPost['store_name'];
        }
        //福建建行非标处理结束

        $result = M('tstore_info')->add($data);
        if (!$result) {
            Log::write(print_r($data, true).M()->getDbError(), 'DB ERROR');
            //$this->error('创建门店失败');
            return '创建门店失败';exit;
        }

        // 添加到分组里去
        if (!empty($getPost['groupId'])) {
            $addGroup = D('StoresGroup')->editStoreInGroup(
                    $getPost['groupId'], $store_id);
            if (!$addGroup) {
               // $this->error('添加到分组错误');
                return '添加到分组错误';exit;
            }
        }
        // 爱蒂宝
        if ($res['node_id'] == C('adb.node_id')) {
            $param = array(
                    'node_id' => $res['node_id'],
                    'page_name' => $getPost['store_short_name'],
                    'store_id' => $store_id, );
            B('AdbStoreAdd', $param);
        }
        M()->commit();
        node_log("【门店管理】门店添加，门店号：{$store_id}"); // 记录日志


        return 'success';
    }

   //门店列表
    public function storeList($nodeId,$nodeIn,$node_id){

        // 终端开通状态
        $res['pos_status'] = array(
                '1' => '未开通终端',
                '2' => '已开通Epos',
                '3' => '已开通实体机具', );

        $dao = M('tstore_info');
        // 按机构树数据隔离
        $where = 'a.node_id in ('.$this->nodeIn().') AND a.type NOT IN (3,4)';

        // 按机构号查询
        if ($node_id && $node_id != $this->nodeId) {
            $where .= ' and a.node_id in ('.$this->nodeIn($node_id).')';
        }
        // 门店名查询
        if (I('store_name') != '') {
            $where .= " and a.store_name like '%".I('store_name')."%'";
        }
        // 负责人
        if (I('principal_name') != '') {
            $where .= " and a.principal_name like '%".I('principal_name').
                    "%'";
        }
        // 终端状态类型，未开通终端
        if (I('pos_count_status') == '1') {
            $where .= ' and a.pos_count = 0';
        } // 已开通终端
        elseif (I('pos_count_status') == '2') {
            $where .= ' and c.pos_type in(0,2) and a.pos_count > 0';
        } elseif (I('pos_count_status') == '3') {
            $where .= ' and c.pos_type not in(0,2) and a.pos_count > 0';
        }

        // 业务受理环境
        if (I('pos_range') != '' && in_array(I('pos_range'),
                        array(
                                '0',
                                '1',
                                '2', ), true)) {
            $where .= " and a.pos_range = '".I('pos_range')."'";
        }

        if (I('province') != '') {
            $where .= " and a.province_code = '".I('province')."'";
        }

        if (I('city') != '') {
            $where .= " and a.city_code = '".I('city')."'";
        }

        if (I('town') != '') {
            $where .= " and a.town_code = '".I('town')."'";
        }

        if (I('jg_name_email') != '') {
            $where .= " and a.principal_email = '".I('jg_name_email')."'";
        }

        $noLocation = I('get.noLocation');
        if ($noLocation == 'true') {
            $where .= ' and (a.lbs_x = 0 or a.lbs_x is NULL) and (a.lbs_y = 0 or a.lbs_y is NULL) and gps_flag = 0';
        }


        if (I('downtype') == '1') {
            $queryList = M('tstore_info')->table('tstore_info a')
                    ->join('tnode_info b on b.node_id=a.node_id')
                    ->join('tpos_info c on a.store_id=c.store_id')
                    ->field(
                            'DISTINCT a.store_name,a.province,a.city,a.town,a.address,a.principal_name,a.principal_tel,a.principal_email')
                    ->where($where)
                    ->order('a.id desc')
                    ->select();
            foreach ($queryList as $key => $value) {
                $queryList[$key]['address'] = $value['province'].$value['city'].
                        $value['town'].$value['address'];
                unset($queryList[$key]['province']);
                unset($queryList[$key]['city']);
                unset($queryList[$key]['town']);
            }
            $rowTitle = array(
                    '门店简称',
                    '门店地址',
                    '门店负责人',
                    '负责人电话',
                    '负责人Email', );
            $res['storeDown']['status'] = 1;
            $res['storeDown']['queryList'] = $queryList;
            $res['storeDown']['rowTitle'] = $rowTitle;
           // $this->storeDown($queryList, $rowTitle);
           return $res;
            exit();
        }


        import('ORG.Util.Page'); // 导入分页类
        if (in_array(I('pos_count_status'),
                array(
                        2,
                        3, ))) {
            $dao->join('tpos_info c on a.store_id=c.store_id');
        }

        $count = $dao->table('tstore_info a')->field(
                array(
                        'count(DISTINCT a.id)' => 'tp_count', ))->where($where)->find(); // 查询满足要求的总记录数
        $count = $count['tp_count'];
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $res['pageShow'] = $Page->show(); // 分页显示输出
        if (in_array(I('pos_count_status'),
                array(
                        2,
                        3, ))) {
            $dao->join('tpos_info c on a.store_id=c.store_id');
        }
        $res[''] = $Page->listRows;
        $res['from'] = $Page->firstRow.
        $queryList = $dao->table('tstore_info a')
                ->join('tnode_info b on b.node_id=a.node_id')
                ->field('DISTINCT a.id aid,a.*,b.node_name')
                ->where($where)
                ->order('a.id desc')
                ->limit($Page->firstRow.','.$Page->listRows)
                ->select();
        // 获取当前机构的所有下级机构
        $res['queryList'] = $queryList;
        $res['nodeList'] = M('tnode_info')->field('node_id,node_name,parent_id')->where(
                "node_id IN({$nodeIn})")->select();

        // 当前机构下的门店总数
        $wh = array(
                '_string' => 'a.node_id in ('.$nodeIn.
                        ') AND a.type NOT IN (3,4)', );

        $storeInfo = D('GroupShop')->getStoreInfo('', '', $nodeId);
        $res['storeCount'] = count($storeInfo) - 1; //减掉一个'全部'

        // 当前机构下开通终端的门店数
        $wh['c.pos_status'] = 0;
        $res['storePosCount'] = $dao->field(
                array(
                        'count(DISTINCT a.id)' => 'tp_count', ))
                ->table('tstore_info a')
                ->join('tpos_info c on c.store_id=a.store_id')
                ->where($wh)
                ->find();

        //获取当前机构下有优惠活动的门店数
        $where = array(
                'i.node_id' => $nodeId,
                'i.status' => '0',
                'i.start_time' => array('elt', date('YmdHis')),
                'i.end_time' => array('egt', date('YmdHis')),
        );

        $res['storeActivityList'] = M()->table('tstore_activity_info')->alias('i')->field('i.store_join_flag')->where($where)->select();

        $storeJoinCount = '0';
        foreach ($res['storeActivityList'] as $value) {
            //如果有全门店活动 则门店数等于有活动的门店数
            if ($value['store_join_flag'] == 1) {
                $storeJoinCount = $res['storeCount'];
                break;
            }
        }
        if (!$storeJoinCount) {
            //如果没有全门店活动 则查有活动的门店 取门店数量
            $where['i.store_join_flag'] = '2';

            $activityStores = M('tstore_activity_info')->alias('i')
                    ->join('INNER JOIN tstore_activity_relation r ON r.activity_id=i.id ')
                    ->where($where)->group('r.store_id')->select();
            $res['storeJoinCount'] = count($activityStores);
        }

        return $res;
    }






    public function addUrl($strUrl)
    {
        $fields = array(
                'output' => 'json',
                'ak' => '96b4191b34ecf02a727747aaf0eedcbb',
                'address' => $strUrl, );
        $ch = curl_init();
        ob_start();
        curl_setopt($ch, CURLOPT_URL, 'http://api.map.baidu.com/geocoder/v2/');
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_exec($ch);
        $data = ob_get_clean();
        curl_close($ch);
        $dataArr = json_decode($data);
        $info = array(
                'y' => $dataArr->result->location->lng,
                'x' => $dataArr->result->location->lat,
                'precise' => $dataArr->result->precise, );

        return $info;
    }
//门店编辑
    public function edit($nodeId, $posId, $storeId) {

        return false;
    }



//创建卡券

    public function addNumGoods($nodeId,$nodeIns,$nodeIn,$userId){

        // 商户信息
        $nodeInfo = M('tnode_info')->field('node_name,client_id,node_service_hotline,posgroup_seq')
                ->where("node_id='{$nodeId}'")
                ->find();
        $goodsModel = D('Goods');

            //初始化变量
            $error = '';
            $onlineVerify = '0';
            //数据验证
            $name = I('post.name');
            if (! check_str($name,array('null' => false,'maxlen_cn' => '24'), $error)) {
                //$this->error("卡券名称{$error}");
                return "卡券名称{$error}";
                exit;
            }
            // 卡券数量
            $goodsNum = I('post.goods_num');
            if (! check_str($goodsNum,array('null' => false,'strtype' => 'int','minval' => '1','maxval' => '9999999'), $error)) {
                //$this->error("卡券数量{$error}");
                return "卡券数量{$error}";
                exit;
            }
            // 卡券价格信息
            $data = array();
            $type = I('post.type', null);
            switch ($type) {
                case '0': // 优惠券
                    break;
                case '1': // 代金卷
                    $price = I('post.price');
                    if (! check_str($price,array('null' => false,'strtype' => 'number','minval' => '0'), $error)) {
                        //$this->error("减免金额{$error}");
                        return "减免金额{$error}";
                        exit;
                    }
                    $data['goods_amt'] = $price;
                    $validateType = I('post.validate_type');
                    if (! check_str($validateType,array('null' => false,'strtype' => 'int','minval' => '0','maxval' => '1'), $error)) {
                        //$this->error("核销限制{$error}");
                        return "核销限制{$error}";
                        exit;
                    }
                    $data['validate_type'] = $validateType;
                    break;
                case '2': // 提领券
                    $onlineVerify = I('post.online_verify_flag');
                    // 是否支持线上门店
                    $onlineStoreInfo = M('tstore_info')->where(array('node_id' => $nodeId,'status' => 0,'type' => 3))->find();
                    if ($onlineVerify == 1 && empty($onlineStoreInfo)) {
                        //$this->error("您尚未开通线上门店");
                        return "您尚未开通线上门店";
                        exit;
                    }
                    break;
                case '3': // 折扣券
                    $discount = I('post.discount');
                    if (! check_str($discount,array('null' => false,'strtype' => 'int','minval' => '1','maxval' => '100'), $error)) {
                        //$this->error("折扣信息{$error}");
                        return "折扣信息{$error}";
                        exit;
                    }
                    $data['goods_discount'] = $discount;
                    break;
                default:
                    //$this->error('未知的卡券类型');
                    return "未知的卡券类型";
                    exit;
            }

            // 类目
            // $goodsCate= I('post.cate2');
            // if(!check_str($goodsCate,array('null'=>false),$error)){
            // $this->error("类目{$error}");
            // }

            // 门店处理
            $shop = I('post.shop');
            if ($shop == '0') {
                // $this->error('请选择验证方式');
                return "请选择验证方式";
                exit;
            }
            $storeList = I('post.openStores', '');
            // 打印小票
            $printText = I('post.print_text');
            if ($onlineVerify == '1' && empty($storeList)) {
                if (! check_str($printText,array('null' => true,'maxlen_cn' => '100'), $error)) {
                    //$this->error("打印小票内容{$error}");
                    return "打印小票内容{$error}";
                    exit;
                }
            } else {
                if (! check_str($printText,array('null' => false,'maxlen_cn' => '100'), $error)) {
                    //$this->error("打印小票内容{$error}");
                    return "打印小票内容{$error}";
                    exit;
                }
            }
            switch ($shop) {
                case 1: // 全门店
                    $groupType = 0;
                    $nodeList = M()->query($nodeIns);
                    $nodeArr = array();
                    foreach ($nodeList as $v) {
                        $nodeArr[] = $v['node_id'];
                    }
                    $dataList = implode(',', $nodeArr);
                    break;
                case 2: // 子门店
                    $groupType = 1;
                    // 获取所有终端列表
                    empty($storeList) ? $shopList = array() : $shopList = explode(
                            ',', $storeList);
                    if ($onlineVerify == '1') { // 线上门店处理
                        $shopList[] = $onlineStoreInfo['store_id'];
                    }
                    if (! is_array($shopList) || empty($shopList)){
                        return "请选择核验门店";
                        exit;
                    }
                    //$this->error('请选择核验门店');
                    $where = array(
                            's.store_id' => array('in',array_unique($shopList)),
                            's.node_id' => array('exp',"in ({$nodeIn})"),
                            's.pos_range' => array('gt','0')
                    );
                    // 获取终端号
                    $posData = M()->table('tstore_info s')
                            ->field('p.pos_id,p.store_id,p.node_id')
                            ->join('tpos_info p ON s.store_id=p.store_id AND s.node_id=p.node_id')
                            ->where($where)
                            ->select();
                    $posArr = array();
                    foreach ($posData as $v) {
                        if (! is_null($v['pos_id'])) {
                            $posArr[] = $v['pos_id'];
                        }
                    }
                    if (! $posArr)
                        //$this->error('未找到有效的验证终端');
                    {
                        return "未找到有效的验证终端";
                        exit;
                    }
                    $dataList = implode(',', $posArr);
                    break;
                default:
                    //$this->error("请选择卡券可验证门店");
                    return "请选择卡券可验证门店";
                    break;
            }
            // 卡券图片
            $goodImage = I('post.img_resp');
            if (! check_str($goodImage,array('null' => false,'maxlen_cn' => '100'), $error)) {
                // $this->error("请上传卡券图片");
                return "请上传卡券图片";
                exit;
            }

            // 同步微信卡券数据校验
            $isCreatWx = I('is_createWx');
            if ($isCreatWx) {
                $wxDefaultDetail = '';
                $wxLeastCost = '0';
                $wxReduceCost = '0';
                $wxGift = '';
                $wxDiscount = '';
                $isBindWx = D('WeelCjSet')->isBindCertWxAccount($nodeId);
                if (! $isBindWx){
                    //$this->error('微信公众号还未授权绑定至旺财,无法创建微信卡券');
                    return "微信公众号还未授权绑定至旺财,无法创建微信卡券";
                    exit;
                }
                // 使用方式 1-投放 2-预存
                $wxStoreMode = I('useType', null, 'mysql_real_escape_string');
                if (! check_str($wxStoreMode,array('null' => false), $error)) {
                    //$this->error("请选择微信卡券使用方式");
                    return "请选择微信卡券使用方式";
                    exit;
                }
                // 商户名称
                $wxNodeName = I('node_name', null, 'mysql_real_escape_string');
                if (! check_str($wxNodeName,array('null' => false,'maxlen_cn' => '12'), $error)) {
                    //$this->error("微信卡券商家名称{$error}");
                    return "微信卡券商家名称{$error}";
                    exit;
                }
                // 卡卷标题
                $wxTitle = I('title', null, 'mysql_real_escape_string');
                if (! check_str($wxTitle,array('null' => false,'maxlen_cn' => '9'), $error)) {
                    //$this->error("微信卡券标题{$error}");
                    return "微信卡券标题{$error}";
                    exit;
                }
                // 副标题
                $wxSubTitle = I('sub_title', null, 'mysql_real_escape_string');
                if (! check_str($wxSubTitle,
                        array('null' => ture,'maxlen_cn' => '18'), $error)) {
                    // $this->error("微信卡券副标题{$error}");
                    return "微信卡券副标题{$error}";
                    exit;
                }
                switch ($type) {
                    case '0': // 优惠券
                        $wxDefaultDetail = I('default_detail');
                        if (! check_str($wxDefaultDetail,array('null' => false,'maxlen_cn' => '500'), $error)) {
                            // $this->error("微信卡券优惠详情{$error}");
                            return "微信卡券优惠详情{$error}";
                            exit;
                        }
                        $wxGoodsType = '3';
                        break;
                    case '1': // 代金卷
                        $wxLeastCost = I('least_cost');
                        if (! check_str($wxLeastCost,array('null' => true,'strtype' => 'number','minval' => '0'), $error)) {
                            //$this->error("微信卡券抵扣条件{$error}");
                            return "微信卡券抵扣条件{$error}";
                            exit;
                        }
                        // 减免金额不能为0
                        $wxReduceCost = I('reduce_cost');
                        if (! check_str($wxReduceCost,array('null' => true,'strtype' => 'number','minval' => '1'), $error)) {
                            // $this->error("微信卡券减免金额要大于0");
                            return "微信卡券减免金额要大于0";
                            exit;
                        }
                        $wxGoodsType = '1';
                        break;
                    case '2': // 提领券
                        $wxGift = I('gift');
                        if (! check_str($wxGift,array('null' => false,'maxlen_cn' => '100'), $error)) {
                            // $this->error("微信卡券礼品内容{$error}");
                            return "微信卡券礼品内容{$error}";
                            exit;
                        }
                        $wxGoodsType = '2';
                        $wxSendDetail = I('post.send_withdrow_detail');
                        if ($wxSendDetail == 1) {
                            $isSendWithDrowDetail = '1';
                        }
                        break;
                    case '3': // 折扣券
                        $wxDiscount = I('discount', null,'mysql_real_escape_string');
                        if (! check_str($wxDiscount,array('null' => true,'strtype' => 'number','minval' => '0'), $error)) {
                            //$this->error("微信卡券折扣额度{$error}");
                            return "微信卡券折扣额度{$error}";
                            exit;
                        }
                        $wxGoodsType = '0';
                        break;
                    default:
                        //$this->error('未知的微信卡券类型');
                        return "未知的微信卡券类型";
                        exit;
                }
                // 卡券颜色
                $wxCardColor = I('card_color', null, 'mysql_real_escape_string');
                if (! check_str($wxCardColor,array('null' => false), $error)) {
                    // $this->error("请选择微信卡券颜色");
                    return "请选择微信卡券颜色";
                    exit;
                }
                // 领券限制
                $wxGetLimit = I('get_limit', null, 'mysql_real_escape_string');
                if (! check_str($wxGetLimit,array('null' => false,'strtype' => 'int','minval' => '1'), $error)) {
                    // $this->error("微信卡券领券限制{$error}");
                    return "微信卡券领券限制{$error}";
                    exit;
                }
                // 用户分享
                $wxCanGiveFriend = I('can_give_friend', null,
                        'mysql_real_escape_string');
                if (! check_str($wxCanGiveFriend,array('null' => false,'strtype' => 'int','minval' => '1','maxval' => '2'), $error)) {
                    //$this->error("微信卡券用户分享信息有误");
                    return "微信卡券用户分享信息有误";
                    exit;
                }
                // 销券设置
                $wxCodeType = I('code_type', null, 'mysql_real_escape_string');
                if (! check_str($wxCodeType,array('null' => false,'strtype' => 'int','minval' => '1','maxval' => '3'), $error)) {
                    //$this->error("微信卡券销券设置信息有误");
                    return "微信卡券销券设置信息有误";
                    exit;
                }
                // 使用须知
                $wxDescription = I('description');
                if (! check_str($wxDescription,array('null' => false,'maxlen_cn' => '500'), $error)) {
                    // $this->error("微信卡券使用须知{$error}");
                    return "微信卡券使用须知{$error}";
                    exit;
                }
                // 客服电话
                $wxServicePhone = I('service_phone', null,'mysql_real_escape_string');
                // 图片处理
                $wxNodeImg = I('node_img', null);

                // 卡券日期处理
                $wxBeginDate = I('post.start_time');
                if (! check_str($wxBeginDate,array('null' => false,'strtype' => 'datetime','format' => 'Ymd'), $error)) {
                    //$this->error("微信卡券使用开始时间日期{$error}");
                    return "微信卡券使用开始时间日期{$error}";
                    exit;
                }
                $wxEndDate = I('post.end_time');
                if (! check_str($wxEndDate,
                        array('null' => false,'strtype' => 'datetime','format' => 'Ymd'), $error)) {
                    // $this->error("微信卡券使用结束时间日期{$error}");
                    return "微信卡券使用结束时间日期{$error}";
                    exit;
                }
                if ($wxEndDate < $wxBeginDate) {
                    //   $this->error('微信卡券有效期开始日期不能大于结束时间');
                    return "微信卡券有效期开始日期不能大于结束时间";
                    exit;

                }
                $dateBeginTimestamp = strtotime($wxBeginDate . '000000');
                $dateEndTimestamp = strtotime($wxEndDate . '235959');

                $quantity = $goodsNum;
            }

            // 支撑创建终端组
            M('tnode_info')->where("node_id='{$nodeId}'")->setInc(
                    'posgroup_seq'); // posgroup_seq
            // +1;
            M()->startTrans();
            $req_array = array(
                    'CreatePosGroupReq' => array(
                            'NodeId' => $nodeId,
                            'GroupType' => $groupType,
                            'GroupName' => str_pad($nodeInfo['client_id'], 6, '0',STR_PAD_LEFT) . $nodeInfo['posgroup_seq'],
                            'GroupDesc' => '',
                            'DataList' => $dataList)
            );
            $RemoteRequest = D('RemoteRequest', 'Service');
            $resp_array = $RemoteRequest->requestIssForImageco($req_array);
            $ret_msg = $resp_array['CreatePosGroupRes']['Status'];
            if (! $resp_array || ($ret_msg['StatusCode'] != '0000' && $ret_msg['StatusCode'] != '0001')) {
                log_write("创建终端组失败，原因：{$ret_msg['StatusText']}");
                //  $this->error('创建门店失败:' . $ret_msg['StatusText']);
                return '创建门店失败:' . $ret_msg['StatusText'];
                exit;
            }
            $groupId = $resp_array['CreatePosGroupRes']['GroupID'];
            // 插入终端组信息
            $num = M('tpos_group')->where("group_id='{$groupId}' AND node_id='{$nodeId}'")->count();
            if ($num == '0') { // 不存在终端组去创建
                $groupData = array( // tpos_group
                        'node_id' => $nodeId,
                        'group_id' => $groupId,
                        'group_name' => $req_array['CreatePosGroupReq']['GroupName'],
                        'group_type' => $groupType,
                        'status' => '0'
                );
                $result = M('tpos_group')->add($groupData);
                if (! $result) {
                    M()->rollback();
                    // $this->error('终端数据创建失败');
                    return "终端数据创建失败";
                    exit;
                }
                switch ($groupType) { // tgroup_pos_relation
                    case 0: // 全商户
                        foreach ($nodeList as $v) {
                            $data_1 = array(
                                    'group_id' => $groupId,
                                    'node_id' => $v['node_id']
                            );
                            $result = M('tgroup_pos_relation')->add($data_1);
                            if (! $result) {
                                M()->rollback();
                                // $this->error('终端数据创建失败');
                                return "终端数据创建失败";
                                exit;
                            }
                        }
                        break;
                    case 1: // 终端型
                        foreach ($posData as $v) {
                            $data_2 = array(
                                    'group_id' => $groupId,
                                    'node_id' => $v['node_id'],
                                    'store_id' => $v['store_id'],
                                    'pos_id' => $v['pos_id']
                            );
                            $result = M('tgroup_pos_relation')->add($data_2);
                            if (! $result) {
                                M()->rollback();
                                // $this->error('终端数据创建失败');
                                return "终端数据创建失败";
                                exit;
                            }
                        }
                        break;
                }
            }
            // 创建合约
            $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 流水号
            $req_array = array(
                    'CreateTreatyReq' => array(
                            'SystemID' => C('ISS_SYSTEM_ID'),
                            'RequestSeq' => $TransactionID,
                            'ShopNodeId' => $nodeId,
                            'BussNodeId' => $nodeId,
                            'TreatyName' => $name,
                            'TreatyShortName' => $name,
                            'StartTime' => date('YmdHis'),
                            'EndTime' => '20301231235959',
                            'GroupId' => $groupId,
                            'GoodsName' => $name,
                            'GoodsShortName' => $name,
                            'SalePrice' => empty($data['goods_amt']) ? 0 : $data['goods_amt'])
            );
            $RemoteRequest = D('RemoteRequest', 'Service');
            $resp_array = $RemoteRequest->requestIssForImageco($req_array);
            $ret_msg = $resp_array['CreateTreatyRes']['Status'];
            if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&$ret_msg['StatusCode'] != '0001')) {
                M()->rollback();
                log_write("创建合约失败，原因：{$ret_msg['StatusText']}");
                //$this->error('创建合约失败:' . $ret_msg['StatusText']);
                return '创建合约失败:' . $ret_msg['StatusText'];
                exit;
            }
            $treatyId = $resp_array['CreateTreatyRes']['TreatyId']; // 合约id
            // 支撑创建活动
            $smilId = $goodsModel->getSmil($goodImage, $name, $nodeId);
            if (! $smilId) {
                return $goodsModel->getError();
                exit;
            }//$this->error($goodsModel->getError());
            // 创建活动
            $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
            // 请求参数
            $req_array = array(
                    'ActivityCreateReq' => array(
                            'SystemID' => C('ISS_SYSTEM_ID'),
                            'ISSPID' => $nodeId,
                            'RelationID' => $nodeId,
                            'TransactionID' => $TransactionID,
                            'SmilID' => $smilId,
                            'ActivityInfo' => array(
                                    'CustomNo' => '',
                                    'ActivityName' => $name,
                                    'ActivityShortName' => $name,
                                    'BeginTime' => date('YmdHis'),
                                    'EndTime' => '20301231235959',
                                    'UseRangeID' => $groupId,
                                    'SpecialTag' => $onlineVerify == '1' ? '01' : ''
                            ),
                            'VerifyMode' => array(
                                    'UseTimesLimit' => ! empty($validateType) && $validateType == 1 ? 0 : 1,
                                    'UseAmtLimit' => ! empty($validateType) && $validateType == 1 ? 1 : 0),
                            'GoodsInfo' => array(
                                    'pGoodsId' => $treatyId
                            ),
                            'DefaultParam' => array(
                                    'PasswordTryTimes' => 3,
                                    'PasswordType' => '',
                                    'PrintText' => $printText
                            )
                    )
            );
            $RemoteRequest = D('RemoteRequest', 'Service');
            $resp_array = $RemoteRequest->requestIssForImageco($req_array);
            $ret_msg = $resp_array['ActivityCreateRes']['Status'];
            if (! $resp_array || ($ret_msg['StatusCode'] != '0000' && $ret_msg['StatusCode'] != '0001')) {
                //$this->error("活动创建失败:{$ret_msg['StatusText']}");
                return "活动创建失败:{$ret_msg['StatusText']}";
                exit;
            }
            $batchNo = $resp_array['ActivityCreateRes']['Info']['ActivityID'];

            // tgoods_info数据添加
            $goodsId = get_goods_id();
            $data['goods_id'] = $goodsId;
            $data['batch_no'] = $batchNo;
            $data['goods_name'] = $name;
            // $data['goods_desc'] = $goodsDesc;
            $data['goods_image'] = $goodImage;
            $data['node_id'] = $nodeId;
            $data['user_id'] = $userId;
            $data['goods_type'] = $type;
            // $data['market_price'] = $marketPrice;
            $data['storage_type'] = 1;
            $data['storage_num'] = $goodsNum;
            $data['remain_num'] = $goodsNum;
            $data['print_text'] = $printText;
            // $data['begin_time'] = $goodsBeginDate.'000000';
            // $data['end_time'] = $goodsEndDate.'235959';
            // $data['verify_begin_date'] = $goodsBeginDate.'000000';
            // $data['verify_end_date'] = $goodsEndDate.'235959';
            // $data['verify_begin_type'] = 0;
            // $data['verify_end_type'] = 0;
            $data['add_time'] = date('YmdHis');
            // $data['goods_cat'] = $goodsCate;
            $data['p_goods_id'] = $treatyId;
            $data['pos_group'] = $groupId;
            $data['online_verify_flag'] = $onlineVerify == '1' ? 1 : 0;
            $data['pos_group_type'] = $shop;
            // 撒湾非标
            $isSaiWan = I('post.is_saiWsan', '0', 'string');
            if ($isSaiWan == '1' && $nodeId == C('withDraw.createNodeId')) {
                $data['source'] = '5';
                $data['purchase_node_id'] = C('withDraw.fromNodeId');
            }

            $id = M('tgoods_info')->data($data)->add();

            $wxCardStatus = '0'; // 卡券创建状态 0-失败 1-成功
            $wxMsg = ''; // 卡券创建成功或错误信息
            if ($id) {
                M()->commit();
                if ($isCreatWx) { // 微信卡券添加
                    $wxData = array(
                            'node_id' => $nodeId,
                            'user_id' => $userId,
                            'goods_id' => $goodsId,
                            'card_type' => $wxGoodsType,
                            'logo_url' => get_upload_url($wxNodeImg),
                            'code_type' => $wxCodeType,
                            'brand_name' => $wxNodeName,
                            'title' => $wxTitle,
                            'sub_title' => $wxSubTitle,
                            'color' => $wxCardColor,
                            'notice' => '使用时向营业员出示二维码或辅助码',
                            'service_phone' => $wxServicePhone,
                            'description' => $wxDescription,
                            'get_limit' => $wxGetLimit,
                            'can_give_friend' => $wxCanGiveFriend,
                            'date_type' => '1',
                            'date_begin_timestamp' => $dateBeginTimestamp,
                            'date_end_timestamp' => $dateEndTimestamp,
                            'quantity' => $quantity,
                            'gift' => $wxGift,
                            'default_detail' => $wxDefaultDetail,
                            'least_cost' => $wxLeastCost,
                            'reduce_cost' => $wxReduceCost,
                            'discount' => $wxDiscount,
                            'store_type' => '酒店',
                            'add_time' => date("YmdHis"),
                            'store_mode' => $wxStoreMode,
                            'send_withdrow_detail' => $isSendWithDrowDetail);
                    if ($wxStoreMode == '2') {
                        $wxData['store_modify_num'] = $quantity;
                    }
                    // 数据插入
                    $wxCardModel = D('WeixinCard');
                    M()->startTrans();
                    $resutl = $wxCardModel->addWxCard($wxData);
                    if ($resutl) {
                        M()->commit();
                        $wxCardStatus = '1';
                        $wxMsg = '微信卡券创建成功';
                    } else {
                        M()->rollback();
                        $wxMsg = $wxCardModel->getError();
                        return $wxMsg;
                        exit;
                    }
                }
                node_log("创建卡券，类型：" . $type . "，名称：" . $name);
                $ajaxData['goods_type'] = $type;
                $ajaxData['goods_id'] = $goodsId;
                $ajaxData['isCreatWx'] = $isCreatWx;
                $ajaxData['wxCardStatus'] = $wxCardStatus;
                $ajaxData['wxMsg'] = $wxMsg;
                // $this->success('创建成功', '', $ajaxData);
                $res['status'] = "success";
                $res['ajaxData'] = $ajaxData;
                return $res;
                exit();
            } else {
                M()->rollback();
                //$this->error('系统出错,卡券创建失败');
                return "系统出错,卡券创建失败";
                exit;
            }
            exit();





    }















    private function getGlide() {

    }

    private function iniJobTable($arr) {

    }
}
