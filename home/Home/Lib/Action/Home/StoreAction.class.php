<?php

/**
 * 门店管理 Author：zhengxd 2015/03/09 12:53.
 */
class StoreAction extends BaseAction
{
    const NUMBER_PER_PAGE = 10;
    // 每页显示10条
    const BATCH_TYPE_SHOPGPS = 17;

    const TIME_EPOS_FREE_END = '20150630';

    public $baidu_url = 'http://api.map.baidu.com/geocoder/v2/?ak=WRzAu3DNewWB4oeOELaczjsM&output=json&address=';

    public $uploadPath;

    public $file_error;

    public $fjcbcFlag = false;

    /**
     * @var GroupShopModel;
     */
    public $GroupShopModel;

    public function _initialize()
    {
        parent::_initialize();
        $this->GroupShopModel = D('GroupShop');
        $this->uploadPath = './Home/Upload/Store/'; // 设置附件上传目录
        import('@.Vendor.CommonConst') or die('include file fail.');

        $this->fjcbcFlag = $this->node_id == C('fjjh.node_id');
        $this->assign('fjcbcFlag', $this->fjcbcFlag);
    }

    // 介绍宣传页
    public function show($content, $charset = '', $contentType = '', $prefix = '')
    {
        $this->display();
    }

    // 单独定位
    public function plocation()
    {
        $this->assign('slng', $_REQUEST['lng']);
        $this->assign('slat', $_REQUEST['lat']);
        $this->assign('lng', $_REQUEST['endLng']);
        $this->assign('lat', $_REQUEST['endLat']);
        $this->assign('cityName', $_REQUEST['cityName']);
        $this->assign('des_city', $_REQUEST['des_city']);
        $this->display();
    }

    public function index()
    {
        // 终端开通状态
        $pos_status = array(
            '1' => '未开通终端',
            '2' => '已开通Epos',
            '3' => '已开通实体机具', );

        $dao = M('tstore_info');
        // 按机构树数据隔离
        $where = 'a.node_id in ('.$this->nodeIn().') AND a.type NOT IN (3,4)';
        $node_id = I('node_id');
        $IsOpen = I('IsOpen');

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
            $queryList = $dao->table('tstore_info a')
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

            $this->storeDown($queryList, $rowTitle);
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
        $pageShow = $Page->show(); // 分页显示输出
        if (in_array(I('pos_count_status'),
            array(
                2,
                3, ))) {
            $dao->join('tpos_info c on a.store_id=c.store_id');
        }

        $queryList = $dao->table('tstore_info a')
            ->join('tnode_info b on b.node_id=a.node_id')
            ->field('DISTINCT a.id aid,a.*,b.node_name')
            ->where($where)
            ->order('a.id desc')
            ->limit($Page->firstRow.','.$Page->listRows)
            ->select();
        // 获取当前机构的所有下级机构
        $nodeList = M('tnode_info')->field('node_id,node_name,parent_id')->where(
            "node_id IN({$this->nodeIn()})")->select();

        // 当前机构下的门店总数
        $wh = array(
            '_string' => 'a.node_id in ('.$this->nodeIn().
            ') AND a.type NOT IN (3,4)', );

        $storeInfo = $this->GroupShopModel->getStoreInfo('', '', $this->nodeId);
        $storeCount = count($storeInfo) - 1; //减掉一个'全部'

        // 当前机构下开通终端的门店数
        $wh['c.pos_status'] = 0;
        $storePosCount = $dao->field(
            array(
                'count(DISTINCT a.id)' => 'tp_count', ))
            ->table('tstore_info a')
            ->join('tpos_info c on c.store_id=a.store_id')
            ->where($wh)
            ->find();

        //获取当前机构下有优惠活动的门店数
        $where = array(
               'i.node_id' => $this->nodeId,
                'i.status' => '0',
                'i.start_time' => array('elt', date('YmdHis')),
                'i.end_time' => array('egt', date('YmdHis')),
        );

        $storeActivityList = M()->table('tstore_activity_info')->alias('i')->field('i.store_join_flag')->where($where)->select();

        $storeJoinCount = '0';
        foreach ($storeActivityList as $value) {
            //如果有全门店活动 则门店数等于有活动的门店数
            if ($value['store_join_flag'] == 1) {
                $storeJoinCount = $storeCount;
                break;
            }
        }
        if (!$storeJoinCount) {
            //如果没有全门店活动 则查有活动的门店 取门店数量
            $where['i.store_join_flag'] = '2';

            $activityStores = M('tstore_activity_info')->alias('i')
                    ->join('INNER JOIN tstore_activity_relation r ON r.activity_id=i.id ')
                    ->where($where)->group('r.store_id')->select();
            $storeJoinCount = count($activityStores);
        }

        $this->assign('storeJoinCount', $storeJoinCount);
        $this->assign('storeActivityList', $storeActivityList);
        $this->assign('queryList', $queryList);
        $this->assign('pageShow', $pageShow);
        $this->assign('storeCount', $storeCount);
        $this->assign('storePosCount', $storePosCount['tp_count']);
        $this->assign('pos_status', $pos_status);
        $this->assign('IsOpen', $IsOpen);
        $this->assign('node_id', $node_id ? $node_id : $this->nodeId);
        $this->assign('node_list', $nodeList);
        $this->assign('is_df',
            (C('df.node_id') == $this->node_id) ? true : false);
        $this->display();
    }

    // 门店添加
    public function add()
    {
        // 分组Model
        $storeGroupModel = $this->getStoresGroupModel();

        // 必须传值机构号
        $node_id = I('node_id', $this->nodeId);
        $nodeInfo = M('tnode_info')->where(
            array(
                'node_id' => array(
                    'exp',
                    'in ('.$this->nodeIn().") and node_id = '".$node_id.
                    "'", ), ))->find();
        if (!$nodeInfo) {
            $this->error('您没有操作该商户的权限。');
        }

        // 判断是否允许添加该机构
        if (IS_POST) {
            // 获取表单数据
            $getPost = I('post.');
           // $dog = '/Label/Image/Shop/dog.png';
            $store = '/Public/Image/defilt.png';
           /* $accs = strstr($getPost['store_pic'],$store);
                      var_dump($accs);
           exit;*/
           // var_dump($getPost['store_pic']);exit;
            // 门店简称
            if (mb_strlen($getPost['store_short_name'], 'utf8') > 10) {
                $this->error('门店简称不得大于10个字');
            }

            //福建建行非标处理开始
            if ($this->fjcbcFlag) {
                if (mb_strlen($getPost['store_name'], 'utf8') > 30) {
                    $this->error('门店详称不得大于30个字');
                }
            }
            //福建建行非标处理结束

            // 门店详情地址
            if (empty($getPost['address'])) {
                $this->error('门店地址不能为空');
            }

            // 门店联系电话
            if ($getPost['store_phone'] != '') {
                if (!is_numeric(str_replace('-', '', $getPost['store_phone']))) {
                    $this->error('门店电话不是纯数字或者-,有非法字符');
                }
            }

            // 姓名
            if (empty($getPost['principal_name'])) {
                $this->error('请输入姓名');
            }

            // 手机
            if (empty($getPost['principal_phone'])) {
                $this->error('请输入手机号');
            } else {
                // 正则验证手机号
                if (preg_match("/^1[3458]{1}\d{9}$/",
                    $getPost['principal_phone']) != 1) {
                    $this->error('请输入正确的手机号');
                }
            }

            // 邮箱
            if (!preg_match('/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/',
                $getPost['principal_email'])) {
                $this->error('邮箱格式不对');
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
            $req_arr['ISSPID'] = $node_id;
            $req_arr['UserId'] = $this->userId;
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
                $this->error($msg);
            }
            $respData = $req_result['CreateStoreExRes'];
            $store_id = $respData['StoreId'];
            if (!$store_id) {
                $this->error('创建支撑门店失败');
            }
            // 查询门店号是否存在
            if (M('tstore_info')->where(array('store_id' => $store_id))->count()) {
                $this->error('门店号['.$store_id.']已经存在。');
            }
            M()->startTrans();

            //获取机构微信智能导航是否设置了全部门店
            $result = M('tweixin_info')
                ->where(['node_id' => $this->nodeId])
                ->field('setting')
                ->find();
            $json = json_decode($result['setting'], true);
            $wxGpsFlagType = empty($json['location']['wxGpsFlagType']) ? 2 : $json['location']['wxGpsFlagType'];

            // 开始记录到门店表
            $data = array(
                'store_id' => $store_id,
                'node_id' => $node_id,
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
            if ($this->fjcbcFlag) {
                $data['store_desc'] = $getPost['store_name'];
            }
            //福建建行非标处理结束

            $result = M('tstore_info')->add($data);
            if (!$result) {
                Log::write(print_r($data, true).M()->getDbError(), 'DB ERROR');
                $this->error('创建门店失败');
            }

            // 添加到分组里去
            if (!empty($getPost['groupId'])) {
                $addGroup = $storeGroupModel->editStoreInGroup(
                    $getPost['groupId'], $store_id);
                if (!$addGroup) {
                    $this->error('添加到分组错误');
                }
            }
            // 爱蒂宝
            if ($node_id == C('adb.node_id')) {
                $param = array(
                    'node_id' => $this->node_id,
                    'page_name' => $getPost['store_short_name'],
                    'store_id' => $store_id, );
                B('AdbStoreAdd', $param);
            }
            M()->commit();
            node_log("【门店管理】门店添加，门店号：{$store_id}"); // 记录日志
            $this->success('门店添加成功',
                array(
                    '返回列表页' => U('index'), ));
            exit();
        }
        // 输出分组门店
        $group = $storeGroupModel->getPopGroupStoreId($this->node_id, true);

        $this->assign('storeGroup', $group);
        $this->assign('nodeInfo', $nodeInfo);
        $this->display();
    }

    // 门店编辑
    public function edit()
    {
        // 分组Model
        $storeGroupModel = $this->getStoresGroupModel();
        $id = I('id');
        $info = M('tstore_info')->where(
            'node_id in ('.$this->nodeIn().") and id='$id'")->find();
        if ($info['store_pic']) {
            $info['image_url'] = $this->uploadPath.$info['store_pic'];
        }
        // 兼容旧版本
        if (empty($info['principal_phone'])) {
            $info['principal_phone'] = $info['principal_tel'];
        }
        // 必须传值机构号
        $node_id = $info['node_id'];
        $nodeInfo = M('tnode_info')->where(
            array(
                'node_id' => array(
                    'exp',
                    'in ('.$this->nodeIn().") and node_id = '".$node_id.
                    "'", ), ))->find();
        if (!$nodeInfo) {
            $this->error('您没有操作该商户的权限。');
        }
        if (IS_POST) {
            // 获取表单数据
            $getPost = I('post.');
            $store = '/Public/Image/defilt.png';
            /*
             * //移动图片image_name $storeimg = $info['store_pic']; $is_log_img =
             * I('is_log_img', 0, 'intval'); if($is_log_img == 1){ $image_name =
             * I('image_name'); //图片新名称 更换方式 原取门店号 现换成年月日时分秒 $new_image
             * =$node_id.'_'.date('YmdHis').'.'.$this->get_extension($image_name);
             * if( $info['store_pic'] != $image_name){ $flag =
             * $this->move_image($image_name,$new_image); if(!($flag === true)){
             * $this->error($flag); } $storeimg=basename($new_image); } }else{
             * $storeimg = ''; }
             */
            // 门店简称
            if (mb_strlen($getPost['store_short_name'], 'utf8') > 10) {
                $this->error('门店简称不得大于10个字');
            }

            // 门店详情地址
            if (empty($getPost['address'])) {
                $this->error('门店地址不能为空');
            }

            // 门店联系电话
            if ($getPost['store_phone'] != '') {
                if (!is_numeric(str_replace('-', '', $getPost['store_phone']))) {
                    $this->error('门店电话不是纯数字或者-,有非法字符');
                }
            }

            // 姓名
            if (empty($getPost['principal_name'])) {
                $this->error('请输入姓名');
            }

            // 手机
            if (empty($getPost['principal_phone'])) {
                $this->error('请输入手机号');
            } else {
                // 正则验证手机号
                if (preg_match("/^1[3458]{1}\d{9}$/", I('principal_phone')) != 1) {
                    $this->error('请输入正确的手机号');
                }
            }

            // 邮箱
            if (!preg_match('/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/',
                $getPost['principal_email'])) {
                $this->error('邮箱格式不对');
            }

            if (strstr($getPost['store_pic'], $store)) {
                $storeimg = C('STATIC_DOMAIN') . '/Home/Public/Image/defilt.png';
            } else {
                $storeimg = C('STATIC_DOMAIN') . '/Home/Upload/'.$getPost['store_pic'];
            }

            M()->startTrans();
            // 接收数据 发送报文至支撑
            $req_arr = array();
            $req_arr['SystemID'] = C('ISS_SYSTEM_ID');
            $req_arr['TransactionID'] = time().mt_rand('1000', '9999');
            $req_arr['ISSPID'] = $node_id;
            $req_arr['UserId'] = $this->userId;
            $req_arr['StoreID'] = $info['store_id'];

            if ($getPost['store_short_name'] != $info['store_short_name']) {
                $req_arr['StoreName'] = $getPost['store_short_name'];
                $req_arr['StoreShortName'] = $getPost['store_short_name'];
            }
            if (($getPost['province_code'].$getPost['city_code'].
                $getPost['town_code']) != $info['area_code']) {
                $req_arr['StoreCityID'] = $getPost['province_code'].
                    $getPost['city_code'].$getPost['town_code'];
            }
            // 重新定位数据
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

            if (!empty($xyUrl['x']) && !empty($xyUrl['y'])) {
                $req_arr['StoreLbs'] = $xyUrl['x'].','.$xyUrl['y'];
            } else {
                $req_arr['StoreLbs'] = '0,0';
            }

            if ($getPost['address'] != $info['address']) {
                $req_arr['StoreAddress'] = $getPost['address'];
            }
            if ($getPost['principal_name'] != $info['principal_name']) {
                $req_arr['ContactName'] = $getPost['principal_name'];
            }
            if ($getPost['principal_email'] != $info['principal_email']) {
                $req_arr['PrincipalEmail'] = $getPost['principal_email'];
            }
            if ($getPost['principal_phone'] != $info['principal_phone']) {
                $req_arr['ContactPhone'] = $getPost['principal_phone'];
            }
            if ($getPost['store_pic'] != $info['store_pic']){
                $req_arr['ImgUrl'] = $storeimg;
            }
            /*
             * //负责人职务，但已被废弃 if($getPost['principal_position'] !=
             * $info['principal_position']){ $req_arr['principalPosition'] =
             * $getPost['principal_position']; }
             */
            // 门店电话
            if ($getPost['store_phone'] != $info['store_phone']) {
                $req_arr['principalTel'] = $getPost['store_phone'];
            }
            $EposInfo = D('Epos')->getPosId($node_id, $info['store_id']);
            if ($EposInfo['pos_type'] == '2') {
                $EposInfo['pos_type'] = '3';
            }
            $req_arr['PosID'] = get_val($EposInfo, 'pos_id', '');
            // $req_arr['PosType'] = $EposInfo['pos_type'];  旺财不会修改终端类型，去掉这个字段
            $storeOldGroupId = M('tgroup_store_relation')->field('store_group_id')->where(['store_id' => $info['store_id']])->find();
            $req_result = D('RemoteRequest', 'Service')->requestIssServ(
                array(
                    'MotifyPosStoreReq' => $req_arr, ));
            $respStatus = isset($req_result['MotifyPosStoreRes']) ? $req_result['MotifyPosStoreRes']['Status'] : $req_result['Status'];
            if ($respStatus['StatusCode'] != '0000') {
                M()->rollback();
                $this->error('修改门店失败');
            }

            //通知支撑 ===============start=======================
            $params = [];
            $params['operate'] = 'storeEdit';
            $params['oldGroupId'] = $storeOldGroupId['store_group_id'];
            $params['newGroupId'] = $getPost['groupId'];
            $params['node_id'] = $node_id;
            $params['store_id'] = $info['store_id'];
            $behaviorReturn = BR('UpdateStoreGroup', $params);
            file_debug($params, 'edit store $params', 'request.log');
            file_debug($behaviorReturn, 'edit store $behaviorReturn', 'request.log');
            if ($behaviorReturn['status'] == 0) {
                //通知支撑报错
                M()->rollback();
                $this->error($behaviorReturn['msg']);
            }
            //通知支撑   ===============end=======================

            // 开始记录到门店表
            $data = array(
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
                'store_phone' => $getPost['store_phone'],
                'store_email' => $getPost['store_email'],
                'busi_time' => $getPost['busi_time'],
                // 'busi_start_time'=>I('busi_start_time'),
                // 'busi_end_time'=>I('busi_end_time'),
                'store_pic' => $getPost['store_pic'],
                'business_code' => $getPost['business_code'],
                'store_introduce' => $getPost['store_introduce'], );
            if (!empty($xyUrl['x']) && !empty($xyUrl['y'])) {
                $data['lbs_x'] = $xyUrl['x']; // 纬度
                $data['lbs_y'] = $xyUrl['y']; // 经度
            }
            $result = M('tstore_info')->where("id='".$info['id']."'")->save(
                $data);
            file_debug($result, 'edit store $result', 'request.log');
            file_debug(M('tstore_info')->_sql(), 'edit store sql', 'request.log');
            // echo M()->_sql();
            // 修改分组
            if (!empty($getPost['groupId'])) {
                $editGroup = $storeGroupModel->editStoreInGroup($getPost['groupId'],
                    $info['store_id']);
                if ($editGroup != true) {
                    $this->error('修改分组失败');
                }
            }

            if ($result === false) {
                Log::write(print_r($data, true).M()->getDbError(), 'DB ERROR');
                $this->error('修改门店失败');
            }
            M()->commit();
            node_log("【门店管理】门店修改，ID:{$info['id']}"); // 记录日志
            if ($info['principal_email'] != $getPost['principal_email']) {
                $this->success("已重置该门店下EPOS终端的登录密码，并发送到{$getPost['principal_email']}", array('href' => 'javascript:parent.location.reload();'));
            } else {
                $this->success('门店修改成功', array('href' => 'javascript:parent.location.reload();'));
            }
            exit();
        }
        // 输出分组
        $storeGroup = $storeGroupModel->getPopGroupStoreId($this->node_id, true);
        if ($info['province_code']) {
            $info['fulladdress'] = $info['province'].$info['city'].$info['town'].$info['address'];
        }
        //门店图片
        if ($info['store_pic']=='' || $info['store_pic']==null){
            $info['store_pic'] =C('STATIC_DOMAIN') . '/Home/Public/Image/defilt.png';
        }
        file_debug($storeGroup, '$storeGroup', 'request.log');
        file_debug($info, '$info', 'request.log');
        $this->assign('storeGroup', $storeGroup);
        $this->assign('nodeInfo', $nodeInfo);
        $this->assign('info', $info);
        $this->display();
    }
// 门店详情
    public function view()
    {
        $id = I('id');
        $info = M('tstore_info')->where(
            'node_id in ('.$this->nodeIn().") and id='$id'")->find();

        $posList = M('tpos_info')->where(
            array(
                'node_id' => array(
                    'exp',
                    "in ({$this->nodeIn()})", ),
                'store_id' => $info['store_id'], ))->select();
        if ($info) {
            $info['img_url'] = APP_PATH.'/Upload/Store/'.$info['store_pic'];
        }

        $storeChannel = M('tchannel')->field('id,name,add_time')->where(
            array(
                'store_id' => $info['store_id'], ))->select();

        $businuss_circle = M('tcity_code')->where(
            "province_code='".$info['province_code']."' and city_code='".
            $info['city_code']."' and town_code='".$info['town_code'].
            "' and business_circle_code='".$info['business_code']."'")->limit(
            '1')->getField('business_circle');

        if ($this->node_id == C('cnpc_gx.node_id')) {
            $labellist = M()->table('tfb_cnpcgx_store_label a')->join(
                'tfb_cnpcgx_storelabel b on a.label_id=b.id')->where(
                'a.store_id='.$id)->field('b.label')->select();
            $this->assign('labellist', $labellist);
        }
        // 获取分组名称
        $storeGroup = $this->getStoresGroupModel();
        $groupName = $storeGroup->groupName($this->node_id, $info['store_id']);
        if (empty($groupName)) {
            $groupName = '未分组';
        }

        $this->assign('groupName', $groupName);
        $this->assign('businuss_circle', $businuss_circle);
        $this->assign('posList', $posList);
        $this->assign('info', $info);
        $this->assign('storeChannel', $storeChannel);
        $this->display('view');
    }

// 移动图片 Upload/img_tmp->Upload/Weixin/node_id
    private function move_image($image_name, $new_name)
    {
        $image_name = str_replace('..', '', $image_name);
        $new_name = str_replace('..', '', $new_name);
        if (!$image_name) {
            return '需上传图片';
        }
        if (!is_dir(APP_PATH.'/Upload/Store/')) {
            mkdir(APP_PATH.'/Upload/Store/', 0777);
        }
        $old_image_url = APP_PATH.'/Upload/img_tmp/'.$this->node_id.'/'.
        basename($image_name);
        $new_image_url = APP_PATH.'/Upload/Store/'.basename($new_name);
        $flag = rename($old_image_url, $new_image_url);
        if ($flag) {
            return true;
        } else {
            return '图片路径非法'.$old_image_url.'=='.$new_image_url;
        }
    }
// 获取文件的后缀名
    private function get_extension($file)
    {
        return end(explode('.', $file));
    }

// //申请全业务eops终端商户类型校验
    // public function checkEposNodeType(){
    // //无法开通全业务epos的行业类型
    // $tradeType = array('21','26','41','90');
    // $nodeTrade =
    // M('tnode_info')->where("node_id='{$this->nodeId}'")->getField('trade_type');
    // if($this->node_type_name == 'staff' || ($this->node_type_name == 'c2' &&
    // !in_array($nodeTrade, $tradeType))){
    // return true;
    // }
    // return false;
    // }
    /**
     * 以表格形式下载数据.
     *
     * @param $queryList array 数据内容
     * @param $rowTitle array 首行标题
     */

// 下载门店信息
    public function storeDown($queryList, $rowTitle)
    {
        $fileName = 'storeInfo.csv';
        $fileName = iconv('utf-8', 'gb2312', $fileName);
        header('Content-type: text/plain');
        header('Accept-Ranges: bytes');
        header('Content-Disposition: attachment; filename='.$fileName);
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        if (!is_array($queryList) || empty($queryList) || !is_array($rowTitle) ||
            empty($rowTitle)) {
            exit();
        }
        $isKey = array_keys($queryList[0]);

        // 输出标题行
        for ($i = 0; $i <= count($isKey); ++$i) {
            echo iconv('utf-8', 'gbk', $rowTitle[$i]).',';
        }
        echo "\r\n";
        // 输出数据行
        foreach ($queryList as $key => $value) {
            for ($i = 0; $i <= count($isKey); ++$i) {
                echo iconv('utf-8', 'gbk', $value[$isKey[$i]]).',';
            }
            echo "\r\n";
        }
    }

    public function location()
    {
        $id = I('id');
        $lat = I('lat');
        $lng = I('lng');
        if ($id == '') {
            die('error[0001]');
        }

        if (empty($lat) === false && empty($lng) === false) {
            $data = array(
                'lbs_y' => $lng,
                'lbs_x' => $lat, );
            $fruit = M('tstore_info')->where(
                'node_id in ('.$this->nodeIn().") and id='$id'")->save($data);
            if ($fruit) {
                die(
                    json_encode(
                        array(
                            'codeId' => '0000',
                            'codeText' => '重新绑定定位成功', )));
            }

            $result = M('tstore_info')->where($data)->where(
                'node_id in ('.$this->nodeIn().") and id='$id'")->count();
            if ($result > 0) {
                die(
                    json_encode(
                        array(
                            'codeId' => '0000',
                            'codeText' => '重新绑定定位成功', )));
            }

            die(
                json_encode(
                    array(
                        'codeId' => '0000',
                        'codeText' => '定位失败，请刷新重试', )));
        }
        $info = M('tstore_info')->where(
            'node_id in ('.$this->nodeIn().") and id='$id'")->find();
        $this->assign('info', $info);
        $this->display();
    }

    public function channellist()
    {
        $channelType = C('CHANNEL_TYPE_ARR');

        $where = array(
            '_string' => 'a.node_id in ('.$this->nodeIn().')',
            'b.type' => '2', );
        // 条件筛选
        $StoreName = I('get.jg_name');
        $province = I('get.province');
        $city = I('get.city');
        $town = I('get.town');
        $getchannelType = I('get.channelType');

        if ($StoreName != '') {
            $where['a.store_name'] = array(
                'like',
                "%$StoreName%", );
        }

        if ($province != '') {
            $where['a.province_code'] = $province;
        }

        if ($city != '') {
            $where['a.city_code'] = $city;
        }

        if ($town != '') {
            $where['a.town_code'] = $town;
        }

        if ($getchannelType != '') {
            $where['b.sns_type'] = $getchannelType;
        }
        // 打包下载二维码
        if (I('xiatype') == 1) {
            // 因文件可能太多，导致超时，故放开脚本运行时间
            set_time_limit(0);
            // 如果没有这个文件夹，创建之
            if (!is_dir(APP_PATH.'Upload/downcodeImg')) {
                mkdir(APP_PATH.'Upload/downcodeImg', 7777);
            }

            $list = M()->table('tstore_info a')
                ->field(
                    'b.id,b.name,b.type,b.sns_type,a.store_name,a.id as storeId')
                ->join('RIGHT JOIN tchannel b on b.store_id=a.store_id')
                ->where($where)
                ->order('b.id desc')
                ->select();
            // dump($list);die;
            $downname = APP_PATH.'Upload/downcodeImg/ChannelQRcode'.date('Ymd').
                '.zip';
            $zip = new ZipArchive();
            if ($zip->open($downname, ZIPARCHIVE::CREATE) !== true) {
                exit('无法打开文件，或者文件创建失败');
            }
            foreach ($list as $k => $v) {
                $this->getCode($v['id'], $v['name']);
            }

            foreach ($list as $v) {
                $filename = iconv('utf-8', 'gbk', $v['name']);
                $zip->addFile(APP_PATH.'Upload/downcodeImg/'.$filename.'.png');
            }
            $zip->close();

            header('Cache-Control: max-age=0');
            header('Content-Description: File Transfer');
            header(
                'Content-disposition: attachment; filename='.basename($downname));
            header('Content-Type: application/zip');
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: '.filesize($downname));
            @readfile($downname);
        }

        import('ORG.Util.Page');
        $count = M()->table('tstore_info a')
            ->field('b.id,b.name,b.type,b.sns_type,a.store_name')
            ->join('RIGHT JOIN tchannel b on b.store_id=a.store_id')
            ->where($where)
            ->count();
        $Page = new Page($count, 10);
        $pageShow = $Page->show();

        $list = M()->table('tstore_info a')
            ->field('b.id,b.name,b.type,b.sns_type,a.store_name,a.id as storeId')
            ->join('RIGHT JOIN tchannel b on b.store_id=a.store_id')
            ->where($where)
            ->limit($Page->firstRow.','.$Page->listRows)
            ->order('b.id desc')
            ->select();

        $this->assign('list', $list);
        $this->assign('channelType', $channelType);
        $this->assign('page', $pageShow);
        $this->display();
    }

// 批量添加渠道
    public function channelShow()
    {
        // 展示页
        $channelType = C('CHANNEL_TYPE_ARR');

        $nodeIn = $this->nodeIn();
        $storesModel = $this->getStoresModel();

        if (IS_POST) {
            $areaType = I('post.city_type');
            $query_arr = (array) $storesModel->areaFilter($nodeIn, $areaType);
            $this->ajaxReturn($query_arr, '查询成功', 0);
            exit();
        }
        // 获取分组
        $storeGroup = $this->storeGroup(false);
        $this->assign('storeGroup', $storeGroup);

        // 获取门店
        $list = $storesModel->getAllStore($nodeIn);

        $this->assign('allStores', $list);
        $this->assign('channelType', $channelType['2']);
        $this->display();
    }

    public function channelAdd()
    {
        // 添加
        if ($this->isPost()) {
            $channelType = C('CHANNEL_TYPE_ARR');
            $nodeIn = $this->nodeIn();
            $storesModel = $this->getStoresModel();
            $arrChannelKey = array_flip($channelType['2']);
            $storeChannel = I('POST.jg_name');
            $channelType = I('POST.channelType');
            $storeCheckStatus = I('POST.storeCheckStatus');

            // 参数校验
            if (empty($storeChannel) || mb_strlen($storeChannel, 'utf-8') > 5) {
                die(
                    json_encode(
                        array(
                            'status' => '0',
                            'info' => '参数错误[nume error]', )));
            }

            if (in_array($channelType, $arrChannelKey) === false) {
                die(
                    json_encode(
                        array(
                            'status' => '0',
                            'info' => '渠道类型[type error]', )));
            }

            $model = M('tchannel');
            $arr = array(
                'type' => '2',
                'sns_type' => $channelType,
                'status' => '1',
                'node_id' => $this->node_id,
                'add_time' => date('YmdHis'), );
            $list = $storesModel->getAllStore($nodeIn);

            if ($list === false) {
                die(
                    json_encode(
                        array(
                            'status' => '0',
                            'info' => '未创建门店[error]', )));
            }

            if (empty($storeCheckStatus)) {
                die(
                    json_encode(
                        array(
                            'status' => '0',
                            'info' => '未选择门店[error]', )));
            }

            M()->startTrans();
            $storeCheckStatus = explode(',', $storeCheckStatus);
            foreach ($list as $v) {
                if (in_array($v['store_id'], $storeCheckStatus)) {
                    $arr['name'] = $storeChannel.'-'.$v['store_short_name'];
                    $arr['address'] = $v['address'];
                    $arr['province_code'] = $v['province_code'];
                    $arr['city_code'] = $v['city_code'];
                    $arr['town_code'] = $v['town_code'];
                    $arr['store_id'] = $v['store_id'];
                    $fruit = $model->add($arr);
                    if ($fruit === false) {
                        M()->rollback();
                        die(
                            json_encode(
                                array(
                                    'status' => '0',
                                    'info' => '创建渠道[error]', )));
                    }
                }
            }
            M()->commit();

            die(
                json_encode(
                    array(
                        'status' => '1',
                        'info' => '创建渠道成功', )));
        }
    }

    private function getCode($id, $filename)
    {
        $url = U('Label/Channel/index', array(
            'id' => $id, ), '', '', true);
        $filename = iconv('utf-8', 'gbk', $filename);
        $this->MakeCodeImg($url, true, $size, $logourl, $filename, $color, $ap_arr);
    }

    private function MakeCodeImg($url = '', $is_down = false, $type = '', $log_dir = '',
        $filename = '', $color = '', $ap_arr = '')
    {
        if (empty($url)) {
            exit();
        }

        import('@.Vendor.phpqrcode.phpqrcode') or die('include file fail.');
        $size_arr = C('SIZE_TYPE_ARR');
        empty($type) ? $size = 4 : $size = $size_arr[$type];
        empty($filename) ? $filename = time().'.png' : $filename .= '.png';

        $code = QRcode::png($url, 'Home/Upload/downcodeImg/'.$filename, '0',
            $size, $margin = true, $saveandprint = false, $log_dir, $color, $ap_arr);
    }
    /**
     * 初始化门店导航列表.
     */
    public function navigation_list()
    {
        $where = array('node_id' => $this->node_id, 'batch_type' => self::BATCH_TYPE_SHOPGPS);
        if (I('title') != '') {
            $where['wap_title'] = array('like', I('title').'%');
        }
        $count = M('tmarketing_info')->where($where)->count(); // 查询满足要求的总记录数
        import('ORG.Util.Page'); // 导入分页类
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $pageShow = $Page->show(); // 分页显示输出
        $queryList = M('tmarketing_info')
            ->where($where)
            ->order('id desc')
            ->limit($Page->firstRow.','.$Page->listRows)
            ->select();

        $this->assign('queryList', $queryList);
        $this->assign('pageShow', $pageShow);

        $this->display();
    }

    /**
     * 初始化门店导航页面.
     */
    public function navigation()
    {
        $m_id = I('get.m_id', 'intval');
        if ($m_id > 0) {
            $marketInfo = $this->addbatch($m_id);
        } else {
            $marketInfo = array(
                'batch_id' => 0,
                'batch_name' => '门店导航营销活动',
                'click_count' => 0, );
        }
        $wapTitle = empty($marketInfo['wap_title']) ? $this->nodeInfo['node_short_name'] : $marketInfo['wap_title'];

        $nodeIn = $this->nodeIn();
        $nodeIn = $this->getNodeIn($nodeIn);
        $storesModel = $this->getStoresModel();

        // 获取到已被开通的门店
        $navigation = $storesModel->getOpenedStores($nodeIn, $m_id);

        // 弹出二维码的标识
        $isPopup = I('get.isPopup');
        if ($isPopup) {
            $this->assign('isPopup', $isPopup);
        }
        $OpenedStores = M('tstore_navigation_item')->where(array('m_id' => $m_id))->getField('store_id', true);

        $this->assign('notice', cookie('notice'));
        $this->assign('navigation', $navigation);

        $this->assign('count', count($navigation));
        $this->assign('wap_title', $wapTitle); // 页面标题
        $this->assign('batch_arr', $marketInfo); // 发布到渠道
        $this->assign('opened_stores', implode(',', $OpenedStores));
        $this->assign('opened_stores_count', count($OpenedStores));

        $this->display();
    }

    /**
     * 初始化 设置 门店导页面.
     */
    public function toNavigation()
    {
        $nodeIn = $this->nodeIn();
        $storesModel = $this->getStoresModel();

        if (IS_POST) {
            $areaType = I('post.city_type');
            $where = array(
                'a.gps_flag' => '0',
                'a.lbs_x' => array(
                    'NEQ',
                    0, ),
                'a.lbs_y' => array(
                    'NEQ',
                    0, ), );
            $query_arr = (array) $storesModel->areaFilter($nodeIn, $areaType,
                $where);
            $this->ajaxReturn($query_arr, '查询成功', 0);
            exit();
        }

        $where = 'a.gps_flag = 0';

        $getAllStores = $storesModel->getAllStore($nodeIn, $where);

        $nonGPS = array();
        $noNavigation = array();

        // 从 未开通的门店中把未定位的门店分离出来
        foreach ($getAllStores as $key => $value) {
            if ($value['lbs_x'] == 0 || $value['lbs_y'] == 0) {
                $nonGPS[] = $value;
            } else {
                $noNavigation[] = $value;
            }
        }
        // 拿分组
        $storeGroup = $this->storeGroup('toNavigation');
        $this->assign('storeGroup', $storeGroup);

        $nonGPS = count($nonGPS);
        $this->assign('allStores', $noNavigation); // 未开通的门店
        $this->assign('nonGPS', $nonGPS); // 未定位的门店
        $this->display();
    }

    /**
     * @var TmarketingInfoModel
     */
    private $TmarketingInfoModel;

    /**
     * 设置导航门店.
     */
    public function setNavigation()
    {
        $storesModel = $this->getStoresModel();

        $this->TmarketingInfoModel = D('TmarketingInfo');

        $nodeIn = $this->nodeIn();

        $store_ids = I('post.');

        if ($store_ids['batch_id'] == 0) {
            $marketInfo = $this->addbatch($m_id);
            $store_ids['batch_id'] = $marketInfo['batch_id'];
        }

        if ($store_ids['notice'] == 'true') {
            cookie('notice', $store_ids['notice'], 60 * 60 * 24 * 365);
        }

        // 修改页面标题
        $editWapTitle = $this->TmarketingInfoModel->editWapTitle($this->node_id, self::BATCH_TYPE_SHOPGPS, $store_ids['titleName'], $store_ids['batch_id']);
        $del = M('tstore_navigation_item')->where(array('m_id' => $store_ids['batch_id']))->delete();
        $data = array();
        if ($store_ids['openStores'] == 'allStores') {
            $getUnOpenedStores = $storesModel->getStoresList($nodeIn,1);
            // 获取所有已定位但未开通的门店
            foreach ($getUnOpenedStores as $key => $value) {
                if ($value['lbs_x'] != 0 && $value['lbs_y'] != 0) {
                    $data[] = array('m_id' => $store_ids['batch_id'], 'store_id' => $value['store_id']);
                }
            }
        } else {
            $disable_store = explode(',', $store_ids['closeStores']);
            foreach (explode(',', $store_ids['openStores']) as $store_id) {
                if (!in_array($store_id, $disable_store)) {
                    $data[] = array('m_id' => $store_ids['batch_id'], 'store_id' => $store_id);
                }
            }
        }

        $add = M('tstore_navigation_item')->addALL($data);

        // 加入导航
        //$add = $storesModel->openLocationStore($nodeIn, $store_ids['openStores']);

        // 不加入导航
        //$del = $storesModel->closeLocationStore($nodeIn, $store_ids['closeStores']);

        if ($add || $del || $editWapTitle) {
            $this->ajaxReturn(array('status' => true, 'info' => $marketInfo), 'JSON');
        } else {
            $this->ajaxReturn(array('status' => false), 'JSON');
        }
    }

    /**
     * 按默认条件获取门店分组.
     *
     * @param $where mixed 获取分组的额外条件
     *
     * @return mixed
     */
    public function storeGroup($where = false)
    {
        $nodeId = $this->node_id;
        $storesModel = $this->getStoresModel();
        $storesGroupModel = $this->getStoresGroupModel();

        // false 为取所有分组
        if (!$where) {
            $getGroupWhere = true;
            $getUnGroupWhere = true;
        } else {
            if ($where == 'toNavigation') {
                $getGroupWhere = ' c.gps_flag = 0 and (c.lbs_x != 0 or c.lbs_x is null) and (c.lbs_y != 0 or c.lbs_y is null)';
                $getUnGroupWhere = ' a.gps_flag = 0 and (a.lbs_x != 0 or a.lbs_x is null) and (a.lbs_y != 0 or a.lbs_y is null)';
            }
        }
        // 获取所有分组
        $allGroup = $storesGroupModel->getPopGroupStoreId($nodeId, $getGroupWhere);

        // 未分组的门店
        $noGroup = $storesModel->getUnGroupedAllStore($nodeId, $getUnGroupWhere);
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
                'storeid' => implode(',', $noGroupArr), ));

        return array_filter($allGroup);
    }

    /**
     * 默认的门店弹窗.
     */
    public function storePopup()
    {
        $nodeIn = $this->nodeIn();
        $storesModel = $this->getStoresModel();

        // 省市区请求
        if (IS_POST) {
            $areaType = I('post.city_type');
            $query_arr = (array) $storesModel->areaFilter($nodeIn, $areaType);
            $this->ajaxReturn($query_arr, '查询成功', 0);
            exit();
        }
        // 爱蒂宝
        if ($this->node_id == C('adb.node_id')) {
            $adb_flag = 1;
            $this->assign('adb_flag', $adb_flag);
        }
        // 拿分组
        $storeGroup = $this->storeGroup(false);
        $this->assign('storeGroup', $storeGroup);

        $getAllStores = $storesModel->getAllStore($nodeIn);

        $this->assign('allStores', $getAllStores); // 输出门店

        // $this->display('../Public/Public_StoresAdnAddress');
        $this->display();
    }

    private function editTitle($title)
    {
        $lookTitle = M('Tnode_info')->where(
            array(
                'node_id' => $this->node_id, ))->getField('node_short_name');
        // 没有更改
        if ($lookTitle == $title) {
            return true;
        }

        $fruit = M('Tmarketing_info')->where(
            array(
                'node_id' => $this->node_id,
                'batch_type' => self::BATCH_TYPE_SHOPGPS, ))->save(array('wap_title' => $title));

        if ($fruit === false) {
            return false;
        }

        return true;
    }

    public function UpdateGps()
    {
        $checkType = I('get.checktype');
        $checkNumber = I('get.checkNumber');

        if (in_array($checkType, array(
            '0',
            '1', )) === false) {
            die(
                json_encode(
                    array(
                        'codeId' => '0001',
                        'codeText' => '参数错误[error]', )));
        }

        if ($this->editTitle(I('get.wep_title')) === false) {
            die(
                json_encode(
                    array(
                        'codeId' => '0006',
                        'codeText' => '页面标题保存失败', )));
        }

        if ($checkType == '0' && empty($checkNumber) == true) {
            die(
                json_encode(
                    array(
                        'codeId' => '0000',
                        'codeText' => '页面标题保存成功', )));
        }

        // 按机构树数据隔离
        $where = 'a.node_id in ('.$this->nodeIn().')';
        $queryList = M()->table('tstore_info a')
            ->join('tnode_info b on b.node_id=a.node_id')
            ->field('a.*,b.node_name')
            ->where($where)
            ->order('a.id desc')
            ->select();

        if ($queryList === false) {
            die(
                json_encode(
                    array(
                        'codeId' => '00003',
                        'codeText' => '未创建门店[error]', )));
        }

        $data = array(
            'gps_flag' => '1', );
        $nodata_arr = array();
        // 可先
        if ($checkType == '0') {
            if (empty($checkNumber)) {
                die(
                    json_encode(
                        array(
                            'codeId' => '0002',
                            'codeText' => '没有选择门店[error]', )));
            }

            $checkNumber = explode(',', $checkNumber);
            $data_arr = array();
            foreach ($queryList as $v) {
                if ($v['gps_flag'] == '0') {
                    if (in_array($v['store_id'], $checkNumber)) {
                        $data_arr[] = $v['id'];
                    }
                } else {
                    if (in_array($v['store_id'], $checkNumber)) {
                        $nodata_arr[] = $v['id'];
                    }
                }
            }
            if (!empty($data_arr)) {
                $flag = M('tstore_info')->where(
                    array(
                        'id' => array(
                            'in',
                            $data_arr, ), ))->save($data);
            }
        }
        // 所有
        if ($checkType == '1') {
            $checkNumber = explode(',', $checkNumber);
            foreach ($queryList as $v) {
                if ($v['gps_flag'] == '1') {
                    if (in_array($v['store_id'], $checkNumber)) {
                        $nodata_arr[] = $v['id'];
                    }
                }
            }
            $flag = M('tstore_info')->where(
                array(
                    'node_id' => $this->node_id,
                    '_string' => 'lbs_x !=0 and lbs_y !=0 ', ))->save($data);
        }
        if (!empty($nodata_arr)) {
            $fruit = M('tstore_info')->where(
                array(
                    'id' => array(
                        'in',
                        $nodata_arr, ), ))->save(array(
                'gps_flag' => 0, ));
        }

        if ($flag === false) {
            die(
                json_encode(
                    array(
                        'codeId' => '0005',
                        'codeText' => '门店更新失败[error]', )));
        }

        die(
            json_encode(
                array(
                    'codeId' => '0000',
                    'codeText' => '门店更新成功', )));
    }

    public function navigation_static()
    {
        $channel_type_arr = C('CHANNEL_TYPE');
        $statusArr = array(
            '1' => '正常',
            '2' => '停用', );
        // 类型
        $batch_type = self::BATCH_TYPE_SHOPGPS;

        $batch_model = M('tmarketing_info');
        $onemap = array(
            'node_id' => $this->node_id,
            'batch_type' => self::BATCH_TYPE_SHOPGPS, );
        $batch_data = $batch_model->where($onemap)->order('id desc')->find();
        // 活动号
        $batch_id = $batch_data['id'];

        // 获取活动名
        $batch_name = M('tmarketing_info')->where(
            "id='".$batch_id."' and batch_type ='".$batch_type."'")->getField(
            'name');
        $model = M()->table('tbatch_channel a');
        $where = " a.node_id in({$this->nodeIn()})";
        $where .= " and a.batch_type = '".$batch_type."'";
        $where .= " and a.batch_id = '".$batch_id."'";
        $list = M()->Table('tbatch_channel a')
            ->field(
                'a.id,a.batch_type,a.channel_id,a.id,a.channel_id,a.click_count,a.send_count,a.add_time,a.status,b.name,b.type,b.sns_type')
            ->join('tchannel b ON a.channel_id=b.id')
            ->where($where)
            ->select();

        $this->assign('batch_id', $batch_id);
        $this->assign('batch_type', $batch_type);
        $this->assign('batch_name', $batch_name);
        $this->assign('channel_type_arr', $channel_type_arr);
        $this->assign('query_list', $list);
        $this->assign('statusArr', $statusArr);

        $this->display();
    }

// 终端接入
    public function terminal()
    {
        $where = array(
            'jk_type' => 0,
            'node_id' => $this->node_id,
            'ym_status' => 0, );
        $terInfo = M('twc_interface')->where($where)->order('id desc')->find();
        if ($this->isPost()) {
            $data = I('POST.');
            $arr = array(
                'servicer',
                'servicernumber',
                'skill',
                'skillnumber',
                'allege', );
            foreach ($arr as $v) {
                if (array_key_exists($v, $data) === false) {
                    die(
                        json_encode(
                            array(
                                'codeId' => '0001',
                                'codeText' => '参数错误[error]', )));
                }
            }

            extract($data);
            if (empty($servicer)) {
                die(
                    json_encode(
                        array(
                            'codeId' => '0002',
                            'codeText' => '参数错误[error]', )));
            }

            if (empty($servicernumber)) {
                die(
                    json_encode(
                        array(
                            'codeId' => '0003',
                            'codeText' => '参数错误[error]', )));
            }

            if (empty($skill)) {
                die(
                    json_encode(
                        array(
                            'codeId' => '0004',
                            'codeText' => '参数错误[error]', )));
            }

            if (empty($skillnumber)) {
                die(
                    json_encode(
                        array(
                            'codeId' => '0005',
                            'codeText' => '参数错误[error]', )));
            }

            if (empty($allege)) {
                die(
                    json_encode(
                        array(
                            'codeId' => '0006',
                            'codeText' => '参数错误[error]', )));
            }

            $array = array(
                'node_id' => $this->node_id,
                'jk_type' => 0,
                'servicer' => $servicer,
                'servicernumber' => $servicernumber,
                'skill' => $skill,
                'skillnumber' => $skillnumber,
                'allege' => $allege,
                'add_time' => date('YmdHis'), );
            $fruit = M('twc_interface')->add($array);
            if ($fruit === false) {
                die(
                    json_encode(
                        array(
                            'codeId' => '0007',
                            'codeText' => '申请入库失败[error]', )));
            }

            // 发邮件
            $nodeInfo = get_node_info($this->node_id);
            $content = '<BR/>申请商户: '.$nodeInfo['node_name'];
            $content .= '<BR/>旺号: '.$nodeInfo['client_id'];
            $content .= '<BR/>联系人: '.$nodeInfo['contact_name'];
            $content .= '<BR/>联系电话: '.$nodeInfo['contact_tel'].'|'.
                $servicernumber;
            $content .= '<BR/>接入设备: '.$allege;
            $content .= "<BR/>处理说明: 登录旺财后台→接口接入申请→门店终端接口申请→找到对应的记录，并点击'审核'";
            $arr = array(
                'subject' => $nodeInfo['node_name'].'申请门店终端接入，请至旺财后台处理',
                'content' => $content,
                'email' => 'yulf@imageco.com.cn', );
            send_mail($arr);
            die(
                json_encode(
                    array(
                        'codeId' => '0000',
                        'codeText' => '申请成功', )));
        }

        $this->assign('terInfo', $terInfo);
        $this->display();
    }

    private function addbatch($m_id)
    {
        $batch_model = M('tmarketing_info');
        $onemap = array(
            'node_id' => $this->node_id,
            'batch_type' => self::BATCH_TYPE_SHOPGPS,
            'id' => $m_id,
        );
        $batch_data = $batch_model->where($onemap)->find();
        if (!$batch_data) {
            // 营销活动不存在则新增
            $batch_arr = array(
                'batch_type' => self::BATCH_TYPE_SHOPGPS,
                'name' => '门店导航默认营销活动',
                'wapTitle' => '门店导航营销活动',
                'status' => '1',
                'start_time' => date('YmdHis'),
                //'end_time' => date('YmdHis', strtotime('+1 year')),
                'add_time' => date('YmdHis'),
                'click_count' => 0,
                'cj_count' => 0,
                'send_count' => 0,
                'node_id' => $this->node_id, );

            $query = $batch_model->add($batch_arr);
            if (!$query) {
                $this->error('添加门店导航默认营销活动失败');
            }

            return array(
                'batch_id' => $query,
                'batch_name' => '门店导航默认营销活动',
                'click_count' => 0, );
        } else {
            return array(
                'batch_id' => $batch_data['id'],
                'batch_name' => $batch_data['name'],
                'click_count' => $batch_data['click_count'],
                'wap_title' => $batch_data['wap_title'], );
        }
    }

    /**
     * [Wapply_terminal 我的终端-申请验证终端].
     */
    public function Wapply_terminal()
    {
        // 获取开通门店的统计
        $countRes = D('Stores')->getStoreCountWithPos($this->nodeId);
        session('arrStoreSess', null);
        // 获取申请终端费用
        $priceModel = D('PosPayPrice');
        $priceModel->SetSpecialPrice($this->nodeId);
        $this->assign('pm', $priceModel);
        $this->assign('countRes', $countRes);
        $this->assign('saleFlag', $this->nodeInfo['sale_flag']);
        $this->display();
    }

    /**
     * [storeEpos 门店申请终端的对话框].
     *
     * @return [type] [description]
     */
    public function storeEpos()
    {
        $nodePayType = M('tnode_info')->getFieldByNode_id($this->nodeId, 'pay_type');
        if ($nodePayType == 0 && get_wc_version() != 'v4') {
            $this->error('后付费用户不可以申请终端');
        }
        $posType = I('postype');
        in_array($posType, array(
            '1',
            '2',
            '3', )) or $this->error('参数错误');
        $condition['a.node_id'] = $this->nodeId;
        $condition['a.type'] = array(
            'NOT IN',
            '3,4', );
        // 省市区查询
        if (I('province_code') != '') {
            $condition['a.province_code '] = I('province_code');
        }
        if (I('city_code') != '') {
            $condition['a.city_code'] = I('city_code');
        }
        if (I('town_code') != '') {
            $condition['a.town_code'] = I('town_code');
        }

        $eposInfo = M()->table('tstore_info a')->where($condition)->order('a.id desc')->select();
        if ($posType == 1) {
            $posname = 'ER6800';
        } elseif ($posType == 2) {
            $posname = 'EPOS';
        } elseif ($posType == 3) {
            $posname = 'ER1100';
        }
        // 查看是否有session
        $arrStoreSess = session('arrStoreSess');
        if (!empty($arrStoreSess)) {
            $this->assign('checktype', $arrStoreSess['checktype']);
            $this->assign('functype', $arrStoreSess['functype']);
            $this->assign('storeCheckStatus', $arrStoreSess['storeCheckStatus']);
            $this->assign('gprs', $arrStoreSess['gprs']);
            $this->assign('buyerInfo', $arrStoreSess['buyerInfo']);
        }
        // 获取申请终端费用
        $priceModel = D('PosPayPrice');
        $priceModel->SetSpecialPrice($this->nodeId);

        $this->assign('pm', $priceModel);
        $this->assign('eposInfo', $eposInfo);
        $this->assign('postype', $posType);
        $this->assign('posname', $posname);
        $this->display();
    }

    /**
     * [storePosAjax 选择门店及pos类型下一步保存信息].
     *
     * @return [type] [description]
     */
    public function storePosAjax()
    {
        $checkType = I('get.checkType');
        $funcType = I('get.funcType');
        $posType = I('get.posType');
        $storesId = I('get.storesId');
        $gprs = I('get.gprs', '0');

        if (empty($storesId)) {
            $this->error('请选择至少一家门店');
        }
        $buyerInfo = array();
        if ($posType == '3') {
            $province_code = I('province_code');
            $city_code = I('city_code');
            $town_code = I('town_code');
            $address_more = I('address_more');
            $buyer_name = I('buyer_name');
            $buyer_phone = I('buyer_phone');
            ($province_code && $city_code && $town_code && $address_more) or
            $this->error('请填写正确、详细的地址信息');
            empty($buyer_name) && $this->error('收货人不得为空');
            empty($buyer_phone) && $this->error('手机号码不得为空');
            $buyerInfo = array(
                'province_code' => $province_code,
                'city_code' => $city_code,
                'town_code' => $town_code,
                'address_more' => $address_more,
                'buyer_name' => $buyer_name,
                'buyer_phone' => $buyer_phone, );
        }

        $arrStoreSess = array(
            'postype' => $posType,
            'functype' => $funcType,
            'storeCheckStatus' => $storesId,
            'buyerInfo' => $buyerInfo,
            'gprs' => $gprs, );
        $payInfo = D('Stores')->getNeedPayMoney($this->nodeId, $arrStoreSess);
        $arrStoreSess['payInfo'] = $payInfo;
        // 把相关信息保存在session中
        session('arrStoreSess', $arrStoreSess);
        $this->success('保存成功');
    }

    /**
     * [payPosAjax 需要付费的详情].
     *
     * @return [type] [description]
     */
    public function storeEposPay()
    {
        $arrStoreSess = session('arrStoreSess');
        if (empty($arrStoreSess)) {
            $this->display('Wapply_terminal');
            exit();
        }
        if ($arrStoreSess['payInfo']['posName'] == 'ER1100') {
            // 处理地址信息
            $buyerInfo = $arrStoreSess['payInfo']['buyerInfo'];
            $preAddr = M('tcity_code')->field(
                'concat(province," ",city," ",town," ") as city')->where(
                array(
                    'path' => $buyerInfo['province_code'].$buyerInfo['city_code'].
                    $buyerInfo['town_code'], ))->find();
            $this->assign('cityInfo', $preAddr['city'].$buyerInfo['address_more']);
            $this->assign('buyer_name', $buyerInfo['buyer_name']);
            $this->assign('buyer_phone', $buyerInfo['buyer_phone']);
            $this->assign('cityInfo', $preAddr['city']);
            $this->assign('address_more', $buyerInfo['address_more']);
        }
        // 获取申请终端费用
        $priceModel = D('PosPayPrice');
        $priceModel->SetSpecialPrice($this->nodeId);

        $this->assign('pm', $priceModel);
        $this->assign('payInfo', $arrStoreSess['payInfo']);
        $this->display();
    }

    /**
     * [goPayPos 前往支付].
     *
     * @return [type] [description]
     */
    public function goPayPos()
    {
        $arrPayInfo = session('arrStoreSess'); // 从session中获取支付详情

        if (empty($arrPayInfo)) {
            redirect(
                U('Home/ServicesCenter/myOrder',
                    array(
                        'order_type' => 3, )));
        }

        $nOrderNumber = date('YmdHis').mt_rand('100000', '999999'); // 订单号

        $arrOrderData = array();
        $arrOrderData['order_number'] = $nOrderNumber;
        $arrOrderData['pay_status'] = 0;
        $arrOrderData['add_time'] = date('YmdHis');
        $arrOrderData['pay_time'] = 0;
        $arrOrderData['node_id'] = $this->nodeId;
        $arrOrderData['order_type'] = 3;
        $arrOrderData['pay_way'] = 1;
        $arrOrderData['amount'] = $arrPayInfo['payInfo']['amount'];
        $arrOrderData['detail'] = json_encode($arrPayInfo);
        $nRet = M('tactivity_order')->add($arrOrderData);

        if ($nRet) {
            session('arrStoreSess', null); // 清空支付详情的session
            $gourl = U('Home/ServicesCenter/goCashier',
                array(
                    'orderId' => $nRet, ));
            $this->success('订单生成成功', '', array(
                'gourl' => $gourl, ));
        } else {
            $this->error('订单生成失败');
        }
    }

    /**
     * [confirmPayPos 确认开通].
     *
     * @return [type] [description]
     */
    public function confirmPayPos()
    {
        $FlowInfo = $this->getAccountInfo();
        $needPayInfo = D('Stores')->getNeedPayMoney($this->nodeId);
        $accountprice = $FlowInfo['AccountPrice'] ? $FlowInfo['AccountPrice'] : 0;
        $wbover = $FlowInfo['WbPrice'] ? $FlowInfo['WbPrice'] : 0;
        $needPayMoney = $needPayInfo['amount'];
        $needRealMoney = $needPayInfo['realAmount'];
        if (($accountprice + $wbover) < $needPayMoney) {
            $this->error('余额不足', '',
                array(
                    'err' => 1,
                    'errinfo' => '付款失败，请检查您的账户余额。', ));
        }
        if ($accountprice < $needRealMoney) {
            $this->error('余额不足', '',
                array(
                    'err' => 1,
                    'errinfo' => '付款失败，请检查您的账户余额。', ));
        }
        // 付款
        try {
            $bRet = D('Stores')->payPosByBatch($this->nodeId);
        } catch (Exception $e) {
            $this->error('扣款失败', '',
                array(
                    'err' => 2,
                    'errinfo' => $e->getMessage(), ));
        }
        // 给相关人员发送短信通知
        D('Stores')->sendEmail($this->nodeId);
        $this->success('付款成功！客户稍后将与您联系，安排上门安装及调试。');
    }
    public function updownRecord()
    {
        $posId = I('get.pos_id');
        $this->assign('list', D('Stores')->getRecord($this->nodeId, $posId));
        $this->display();
    }

    public function raisePos()
    {
        $nodePayType = M('tnode_info')->getFieldByNode_id($this->nodeId, 'pay_type');
        if ($nodePayType == 0 && get_wc_version() != 'v4') {
            $this->error('后付费用户不可以申请终端');
        }
        $storeId = I('get.sId');
        $posId = I('get.posId');
        $posStatus = M('tpos_info')->where(
            array(
                'node_id' => $this->nodeId, ))->getFieldByPos_id($posId, 'pos_status');
        if ($posStatus != 0) {
            $this->error('终端状态有误，无法升级');
        }
        $nRet = D('Stores')->raisePos($this->nodeId, $posId, $storeId);
        if (!$nRet) {
            $this->success('升级成功！');
        } else {
            $this->error($nRet);
        }
    }

    public function wcAddpos()
    {
        $posType = I('postype');
        if (in_array($posType, array(
            '1',
            '2',
            '3', )) === false) {
            $this->error('参数错误[POSTYPE ERROR]');
        }

        $checkType = I('checktype');
        if (in_array($checkType, array(
            '0',
            '1', )) === false) {
            $this->error('参数错误[error1]');
        }

        $checkNumber = I('checkNumber');

        $where = 'a.node_id in ('.$this->nodeIn().')';
        $queryList = M()->table('tstore_info a')
            ->join('tnode_info b on b.node_id=a.node_id')
            ->field('a.*,b.node_name')
            ->where($where)
            ->order('a.id desc')
            ->select();
        if ($queryList === false) {
            $this->error('未创建门店[error]');
        }

        $storePos = array();
        // 可先
        if ($checkType == '1') {
            if (empty($checkNumber)) {
                $this->error('没有选择门店[error]');
            }

            $storePos = explode(',', $checkNumber);
        }

        // 所有
        if ($checkType == '0') {
            foreach ($queryList as $v) {
                if ($v['pos_type'] == $posType && $v['pos_count'] > 0) {
                    $ePos[] = $v;
                } else {
                    $storePos[] = $v['store_id'];
                }
            }
        }
        if (empty($storePos)) {
            $this->error('门店信息[error]');
        }

        if ($posType == '2') {
            $posType = 'EposAll';
        }
        // 判断用户类型v0.5客户，开通劳动节活动，并且未参加打炮活动,可创建免费EPOS
        $wc_version = get_wc_version($this->node_id);
        if ($wc_version == 'v0.5') {
            if ($this->_FreePosCreateNotice()) {
                $posType = 'FreeEpos';
            }
        }
        // 现在是否是限免活动的逻辑改为：1：是免费用户2：未参加过
        // （CommonConst::BATCH_TYPE_FIRECRACKER, CommonConst::BATCH_TYPE_LABORDAY,
        // CommonConst::BATCH_TYPE_MAMAWOAINI,
        // CommonConst::BATCH_TYPE_ZONGZI）
        // 3：并且限制在10月17日之前 4：创建了我是升旗手活动
        $isFreeEpos = $this->isFreeEpos();
        if ($isFreeEpos) {
            $posType = 'FreeEpos';
        }
        // 我是升旗手临时增加的免费epos逻辑
        $isFreeUser = D('node')->getNodeVersion($this->node_id);
        if ($isFreeUser) {
            $eposCoun = M('tpos_info')->where(
                array(
                    'node_id' => $this->node_id, ))->count();
            if ($eposCoun > 0) {
                $this->error(
                    '您只能创建一个免费ePos，想要更多ePos需要签约成为付费客户。<br/>查看<a href="'.
                    U('Store/myEpos').'" target="_blank">我的ePos</a>，<a href="'.
                    U('Home/Wservice/buywc').'" target="_blank">在线签约</a>');
            }
            if (time() > strtotime('2015-10-16')) {
                $this->error(
                    'ePos免费申请活动已结束，想要更多ePos需要成为付费客户。<br/><a href="'.
                    U('Home/Wservice/buywc').'" target="_blank">在线签约</a>');
            }
        }
        if ($posType == 'FreeEpos') {
            if (count($storePos) > 1) {
                $this->error('只能创建一台免费终端！');
            }
        }
        // 增加门店开始
        foreach ($storePos as $k => $v) {
            $this->eposAdd($v, $posType);
        }
        $this->success('门店验证终端已创建成功。请到邮箱中查收用户名和密码');
    }

    public function wcAdd6800()
    {
        $posType = I('postype');
        if (in_array($posType, array(
            '1',
            '2',
            '3', )) === false) {
            $this->error('参数错误[POSTYPE ERROR]');
        }

        $checkType = I('checktype');
        if (in_array($checkType, array(
            '0',
            '1', )) === false) {
            $this->error('参数错误[error]');
        }

        $checkNumber = I('checkNumber');

        $where = 'a.node_id in ('.$this->nodeIn().')';
        $queryList = M()->table('tstore_info a')
            ->join('tnode_info b on b.node_id=a.node_id')
            ->field('a.*,b.node_name')
            ->where($where)
            ->order('a.id desc')
            ->select();
        if ($queryList === false) {
            $this->error('未创建门店[error]');
        }

        $storePos = array();
        // 可先
        if ($checkType == '1') {
            if (empty($checkNumber)) {
                $this->error('没有选择门店[error]');
            }

            $storePos = explode(',', $checkNumber);
        }

        // 所有
        if ($checkType == '0') {
            foreach ($queryList as $v) {
                if ($v['pos_type'] == $posType && $v['pos_count'] > 0) {
                    $ePos[] = $v;
                } else {
                    $storePos[] = $v['store_id'];
                }
            }
        }
        if (empty($storePos)) {
            $this->error('门店信息[error]');
        }

        $fruit = D('StoreJob', 'Service');
        $result = $fruit->jobAdd($this->node_id, $storePos);
        // $this->success(print_r($result,true));
        if ($result === true) {
            $this->success('申请信息已提交，客服稍后联系您预约上门安装！');
        }

        $this->error('申请失败： '.$fruit->error);
    }

    private function eposAdd($store_id, $type)
    {
        $storeInfo = M('tstore_info')->where(
            array(
                'store_id' => $store_id,
                'node_id' => array(
                    'exp',
                    "in ({$this->nodeIn()})", ), ))->find();
        if (!$storeInfo) {
            $this->error('门店不存在');
        }
        $node_id = $storeInfo['node_id'];
        // 判断是否已有正常的ePos
        $countEpos = M('tpos_info')->where(
            array(
                'store_id' => $storeInfo['store_id'],
                'node_id' => $node_id,
                'pos_type' => '2',
                'pos_status' => '0', ))->count();
        if ($countEpos) {
            $this->error('该门店已开通过Epos。');
        }
        // 校验一下是否允许开免费EPOS
        if ($type == 'EposSpring2015') {
            if (!$this->checkEposNodeType($type)) {
                $this->error('您不允许开设此类型终端');
            }
        }
        // 校验一下是否允许开免费EPOS
        if ($type == 'EposLaborDay') {
            if (!$this->checkEposNodeType($type)) {
                $this->error('您不允许开设此类型终端');
            }
        }
        // 接收表单传值
        $req_arr = array();
        $pos_name = $storeInfo['store_short_name'];
        $req_arr['SystemID'] = C('ISS_SYSTEM_ID');
        $req_arr['TransactionID'] = time().mt_rand('1000', '9999');
        $req_arr['ISSPID'] = $node_id;
        $req_arr['StoreID'] = $storeInfo['store_id'];
        $req_arr['PosGroupID'] = '';
        $req_arr['PosFlag'] = 0;
        $req_arr['PosType'] = 3;
        $req_arr['PosName'] = $pos_name;
        $req_arr['PosShortName'] = $pos_name;
        if ($type == 'EposAi') {
            // 爱拍终端

            // 免费的哟
            $req_arr['PosPayFlag'] = 1;
        } elseif ($type == 'FreeEpos') {
            // 免费的哟
            $req_arr['PosPayFlag'] = 2;
        }
        $req_arr['UserID'] = $this->userId;

        // $req_arr['ProduceFlag'] = 0;
        // $req_arr['PosType'] = 3;
        // $req_arr['PosPayFlag'] = 1;
        // $req_arr['PosGroupID'] = '';

        /*
         * $req_arr['StoreName'] = $storeInfo['store_name'); $req_arr['CustomNo'] =
         * $storeInfo['custom_no'); $req_arr['ProvinceCode'] =
         * $storeInfo['province_code'); $req_arr['CityCode'] =
         * $storeInfo['city_code'); $req_arr['TownCode'] = $storeInfo['town_code');
         */
        // $req_arr['PrincipalName'] = $storeInfo['principal_name'];
        // $req_arr['PrincipalTel'] = $storeInfo['principal_tel'];
        // $req_arr['PrincipalEmail'] = I('principal_email');
        // $req_arr['Address'] = $storeInfo['address'];
        // $req_arr['LAT'] = $storeInfo['lat'];
        // $req_arr['LNG'] = $storeInfo['lan'];
        $req_result = D('RemoteRequest', 'Service')->requestIssServ(
            array(
                'PosCreateReq' => $req_arr, ));
        $respStatus = isset($req_result['PosCreateRes']) ? $req_result['PosCreateRes']['Status'] : $req_result;
        if ($respStatus['StatusCode'] != '0000') {
            $this->error($respStatus['StatusText']);
        }
        $respData = $req_result['PosCreateRes'];

        // $store_id = $respData['StoreID'];
        $pos_id = $respData['PosID'];

        if (!$pos_id) {
            $this->error('创建支撑终端失败');
        }

        M()->startTrans();

        // 创建终端
        $data = array(
            'pos_id' => $pos_id,
            'node_id' => $node_id,
            'pos_name' => $pos_name,
            'pos_short_name' => $pos_name,
            'pos_serialno' => $pos_id,
            'store_id' => $storeInfo['store_id'],
            'store_name' => $storeInfo['store_name'],
            'login_flag' => 0,
            'pos_type' => '2',
            'is_activated' => 0,
            'pos_status' => 0,
            'add_time' => date('YmdHis'), );
        if ($type == 'EposAi') {
            // 爱拍终端

            // 免费
            $data['pay_type'] = 1;
        } elseif ($type == 'EposSpring2015') {
            // 春节打炮

            // 免费2
            $data['pay_type'] = 2;
        } elseif ($type == 'EposLaborDay') {
            // 劳动节

            // 免费3
            $data['pay_type'] = 2;
        } else {
            $data['pay_type'] = 0;
        }
        $result = M('tpos_info')->add($data);
        if (!$result) {
            log_write(print_r($data, true).M()->getDbError(), 'DB ERROR');
            $this->error('创建终端失败,原因：'.M()->getDbError());
        }
        // 更新pos_range
        if ($storeInfo['pos_range'] == '0') {
            if ($type == 'EposAi') {
                // 爱拍终端
                $data = array(
                    'pos_range' => '1',
                    'pos_count' => 1, );
            } else {
                $data = array(
                    'pos_range' => '2',
                    'pos_count' => 1, );
            }
            $result = M('tstore_info')->where("store_id={$storeInfo['store_id']}")->save(
                $data);
            if (!$result) {
                log_write(print_r($data, true).M()->getDbError(), 'DB ERROR');
                $this->error('创建终端失败,原因：'.M()->getDbError());
            }
        }

        M()->commit();
        node_log('【门店管理】门店验证终端创建'); // 记录日志

        $url = U('Store/index');
    }

// 申请全业务eops终端商户类型校验
    private function checkEposNodeType($eposType = 'epos')
    {
        // 如果是
        if ($eposType == 'EposSpring2015') {
            $wc_version = get_wc_version($this->nodeId);
            if ($wc_version == 'v0' || $wc_version == 'v0.5') {
                return true;
            }

            return false;
        }
        // 如果是劳动节
        if ($eposType == 'EposLaborDay') {
            $wc_version = get_wc_version($this->nodeId);
            if ($wc_version == 'v0.5') {
                return true;
            }

            return false;
        }
        // 无法开通全业务epos的行业类型
        $tradeType = array(
            '21',
            '26',
            '41',
            '90', );
        $nodeTrade = M('tnode_info')->where("node_id='{$this->nodeId}'")->getField(
            'trade_type');
        if ($this->node_type_name == 'staff' ||
            ($this->node_type_name == 'c2' && !in_array($nodeTrade, $tradeType))) {
            return true;
        }

        return false;
    }

    public function myEpos()
    {
        $onlineStore = M('tstore_info')->where(
            array(
                'node_id' => $this->nodeId,
                'type' => 3, ))->find();
        $ticketHelper = M('tstore_info')->where(
            array(
                'node_id' => $this->nodeId,
                'type' => 4, ))->find();

        $pay_status = array(
            '0' => '正常',
            '1' => '欠费',
            '2' => '停机保号',
            '3' => '注销',
            '4' => '过期',
            '5' => '激活中', );
        $this->assign('pay_status', $pay_status);
        $this->assign('post', I('post.'));

        $where = 'p.pos_status not  in (4) and p.node_id in ('.$this->nodeIn().
            ') AND p.pos_type  in(0,2) ';
        $model = M();

        if (!empty($onlineStore)) {
            $where .= ' AND p.store_id != '.$onlineStore['store_id'];
        }
        if (!empty($ticketHelper)) {
            $where .= ' AND p.store_id != '.$ticketHelper['store_id'];
        }

        //门店名称
        if (I('jg_name') != '') {
            $where .= " AND p.store_name like '%".I('jg_name')."%'";
        }

        //终端号
        if (I('pos_id') != '') {
            $where .= " and p.pos_id = '".I('pos_id')."'";
        }

        //负责人
        if (I('principal_name') != '') {
            $where .= " and s.principal_name like '%".I('principal_name')."%'";
        }

        //状态
        if (I('pay_status') != '') {
            $where .= " and p.pos_status = '".I('pay_status')."'";
        }

        if (I('province') != '') {
            $where .= " and s.province_code = '".I('province')."'";
        }

        if (I('city_code') != '') {
            $where .= " and s.city_code = '".I('city_code')."'";
        }

        if (I('town_code') != '') {
            $where .= " and s.town_code = '".I('town_code')."'";
        }
        // 数据下载
        if (I('get.downtype') == 1) {
            $list = $model->table('tpos_info p')
                ->field(
                    's.store_short_name,s.province,s.city,s.town,s.address,p.pos_id,p.add_time,p.func_type,p.pos_status')
                ->join('tstore_info s ON p.store_id=s.store_id')
                ->where($where)
                ->order('p.id desc')
                ->select();
            foreach ($list as $key => $value) {
                if ($value['func_type'] == 1) {
                    $list[$key]['func_type'] = '仅可受理条码支付';
                } else {
                    $list[$key]['func_type'] = '可验证凭证、受理条码支付';
                }
                $list[$key]['pos_status'] = $pay_status[$value['pos_status']];
                $list[$key]['add_time'] = date('Y-m-d',
                    strtotime($value['add_time']));
                $list[$key]['address'] = $value['province'].$value['city'].
                    $value['town'].$value['address'];
                unset($list[$key]['province']);
                unset($list[$key]['city']);
                unset($list[$key]['town']);
            }
            $tableTitle = array(
                '门店简称',
                '所在省市区',
                '终端号',
                '开通日期',
                '终端功能',
                '状态', );

            $this->storeDown($list, $tableTitle);
            exit();
        }

        import('ORG.Util.Page');

        $count = $model->table('tpos_info p')
            ->field('p.*')
            ->join('tstore_info s ON p.store_id=s.store_id')
            ->where($where)
            ->count();

        $Page = new Page($count, 8);
        $pageShow = $Page->show();

        $list = $model->table('tpos_info p')
            ->field(
                'p.*,s.province,s.city,s.town,s.principal_email,s.province_code,s.city_code,s.town_code,s.store_short_name')
            ->join('tstore_info s ON p.store_id=s.store_id')
            ->where($where)
            ->order('p.id desc')
            ->limit($Page->firstRow.','.$Page->listRows)
            ->select();
        // 当月剩余天数计算
        $nRemainDayCount = date('Ymd',
            strtotime(
                date('YmdHis', strtotime(date('Ym01000000').' +1 month')).
                ' -1 day')) - date('Ymd');
        $this->assign('price', $nRemainDayCount * (30 / 30));
        $this->assign('page', $pageShow);
        $this->assign('list', $list);
        $this->assign('posStatus', $pay_status);
        $this->display();
    }

    public function myTool()
    {
        $pay_status = array(
            '0' => '正常',
            '1' => '欠费',
            '2' => '停机保号',
            '3' => '注销',
            '4' => '过期',
            '5' => '激活中', );
        $this->assign('pay_status', $pay_status);
        $this->assign('post', I('post.'));

        $posJob = array(
            '1' => '未受理',
            '2' => '已撤销',
            '3' => '受理中',
            '4' => '已拒绝',
            '5' => '已结束',
            '6' => '回退', );

        $where = 'p.node_id in ('.$this->nodeIn().
            ') AND p.pos_type not in(0,2)  AND s.pos_count>0';

        //门店名称
        if (I('jg_name') != '') {
            $where .= " AND p.store_name like '%".I('jg_name')."%'";
        }

        //终端号
        if (I('pos_id') != '') {
            $where .= " and p.pos_id = '".I('pos_id')."'";
        }

        //负责人
        if (I('principal_name') != '') {
            $where .= " and s.principal_name like '%".I('principal_name')."%'";
        }

        //状态
        if (I('pay_status') != '') {
            $where .= " and p.pos_status = '".I('pay_status')."'";
        }

        if (I('province') != '') {
            $where .= " and s.province_code = '".I('province')."'";
        }

        if (I('city_code') != '') {
            $where .= " and s.city_code = '".I('city_code')."'";
        }

        if (I('town_code') != '') {
            $where .= " and s.town_code = '".I('town_code')."'";
        }
        $model = M();

        // 数据下载
        if (I('get.downtype') == 1) {
            $list = $model->table('tpos_info p')
                ->field(
                    's.store_short_name,s.province,s.city,s.town,s.address,p.pos_id,p.add_time,p.pos_status,p.func_type')
                ->join('tstore_info s ON p.store_id=s.store_id')
                ->where($where)
                ->order('p.id desc')
                ->select();

            foreach ($list as $key => $value) {
                if ($value['func_type'] == 1) {
                    $list[$key]['func_type'] = '仅可受理条码支付';
                } else {
                    $list[$key]['func_type'] = '可验证凭证、受理条码支付';
                }

                $list[$key]['pos_status'] = $pay_status[$value['pos_status']];
                $list[$key]['add_time'] = date('Y-m-d',
                    strtotime($value['add_time']));
                $list[$key]['address'] = $value['province'].$value['city'].
                    $value['town'].$value['address'];
                unset($list[$key]['province']);
                unset($list[$key]['city']);
                unset($list[$key]['town']);
            }
            $tableTitle = array(
                '门店简称',
                '所在省市区',
                '终端号',
                '开通日期',
                '状态',
                '终端功能', );

            $this->storeDown($list, $tableTitle);
            exit();
        }

        import('ORG.Util.Page');

        $count = $model->table('tpos_info p')
            ->field('p.*')
            ->join('tstore_info s ON p.store_id=s.store_id')
            ->where($where)
            ->order('p.id desc')
            ->count();

        $Page = new Page($count, 8);
        $pageShow = $Page->show();

        $list = $model->table('tpos_info p')
            ->field(
                'p.*,s.principal_email,s.province_code,s.city_code,s.town_code,s.store_short_name,s.province,s.city,s.town')
            ->join('tstore_info s ON p.store_id=s.store_id')
            ->where($where)
            ->order('p.id desc')
            ->limit($Page->firstRow.','.$Page->listRows)
            ->select();
        // 当月剩余天数计算
        $nRemainDayCount = date('Ymd',
            strtotime(
                date('YmdHis', strtotime(date('Ym01000000').' +1 month')).
                ' -1 day')) - date('Ymd');
        $this->assign('price', ceil($nRemainDayCount * (70 / 30)));
        $this->assign('page', $pageShow);
        $this->assign('list', $list);
        $this->assign('posJob', $posJob);
        $this->assign('posStatus', $pay_status);
        $this->display();
    }

    public function storeNumber()
    {
        import('ORG.Util.Page');
        $start_time = I('start_time');
        $end_time = I('end_time');
        $province_code = I('province_code');
        $city_code = I('city_code');
        $town_code = I('town_code');
        $wh = '';
        if ($start_time != '') {
            $start_time = date('Y-m-d', strtotime($start_time));
            $wh .= " and c.trans_date > $start_time";
        }
        if ($end_time != '') {
            $end_time = date('Y-m-d', strtotime($end_time));
            $wh .= " and c.trans_date < $end_time";
        }
        if ($province_code != '') {
            $wh .= " and a.province_code = $province_code";
        }

        if ($city_code != '') {
            $wh .= " and a.city_code = $city_code";
        }

        if ($town_code != '') {
            $wh .= " and a.town_code = $town_code";
        }

        // $sql="SELECT s.store_name,IFNULL(c.fcount,0) as fcount from
        // tstore_info s
        // LEFT JOIN (SELECT store_id,SUM(send_count) as fcount FROM tchannel
        // WHERE type=2 GROUP BY store_id ) c
        // ON s.store_id=c.store_id WHERE s.node_id in (".$this->nodeIn().")
        // ORDER BY c.fcount DESC limit 10";
        $sql = 'SELECT a.store_short_name store_name,IFNULL(SUM(c.total_cnt),0) as fcount FROM tstore_info a
                LEFT JOIN tchannel b ON a.store_id=b.store_id and a.node_id = b.node_id
 				LEFT JOIN tgoods_send_stat c ON c.channel_id=b.id and a.node_id = c.node_id
				WHERE a.node_id in ('.$this->nodeIn().')
				GROUP BY a.store_id
				ORDER BY  fcount DESC limit 10';
        $result = M()->query($sql);

        // $storeCount=M('tstore_info s')->WHERE("s.node_id in (".$this->nodeIn().")
        // $wh")->COUNT();
        $storeCountsql = 'SELECT a.store_short_name store_name,IFNULL(SUM(c.total_cnt),0) as fcount FROM tstore_info a
                LEFT JOIN tchannel b ON a.store_id=b.store_id and a.node_id = b.node_id
 				LEFT JOIN tgoods_send_stat c ON c.channel_id=b.id and a.node_id = c.node_id
				WHERE a.node_id in ('.$this->nodeIn().") $wh
				GROUP BY a.store_id
				ORDER BY  fcount DESC";
        $storeCount = M()->query($storeCountsql);
        $storeCount = count($storeCount);
        $Page = new Page($storeCount, 8);
        $pageShow = $Page->show();
        // $listsql="SELECT s.store_name,IFNULL(c.fcount,0) as fcount from
        // tstore_info s
        // LEFT JOIN (SELECT store_id,SUM(send_count) as fcount FROM tchannel WHERE
        // type=2 GROUP BY store_id ) c
        // ON s.store_id=c.store_id WHERE s.node_id in (".$this->nodeIn().") $wh
        // ORDER BY c.fcount DESC limit ".$Page->firstRow.','.$Page->listRows;
        $listsql = 'SELECT a.store_short_name store_name,IFNULL(SUM(c.total_cnt),0) as fcount FROM tstore_info a
                LEFT JOIN tchannel b ON a.store_id=b.store_id and a.node_id = b.node_id
 				LEFT JOIN tgoods_send_stat c ON c.channel_id=b.id and a.node_id = c.node_id
				WHERE a.node_id in ('.$this->nodeIn().") $wh
				GROUP BY a.store_id
				ORDER BY  fcount DESC limit ".
        $Page->firstRow.','.$Page->listRows;
        $listresult = M()->query($listsql);

        $storeSendMaNumber = array();
        $storeSendMaName = array();
        foreach ($result as $v) {
            $storeSendMaNumber[] = (int) $v['fcount'];
            $storeSendMaName[] = $v['store_name'];
        }

        $this->assign('_chartData',
            json_encode(
                array(
                    'storeSendMaNumber' => $storeSendMaNumber,
                    'storeSendMaName' => $storeSendMaName, )));
        $this->assign('pageShow', $pageShow);
        $this->assign('listresult', $listresult);
        $this->display();
    }

    public function storeNumber2()
    {
        import('ORG.Util.Page');
        $start_time2 = I('start_time2');
        $end_time2 = I('end_time2');
        $province_code2 = I('province_code2');
        $city_code2 = I('city_code2');
        $town_code2 = I('town_code2');
        $wh2 = '';
        if ($start_time2 != '') {
            $start_time2 = date('Y-m-d', strtotime($start_time2));
            $wh2 .= " and c.trans_date > '$start_time2'";
        }
        if ($end_time2 != '') {
            $end_time2 = date('Y-m-d', strtotime($end_time2));
            $wh2 .= " and c.trans_date < '$end_time2'";
        }
        if ($province_code2 != '') {
            $wh2 .= " and a.province_code = $province_code2";
        }

        if ($city_code2 != '') {
            $wh2 .= " and a.city_code = $city_code2";
        }

        if ($town_code2 != '') {
            $wh2 .= " and a.town_code = $town_code2";
        }

        // $verifySql="SELECT s.store_name,IFNULL(z.scount,0) as scount FROM
        // tstore_info s
        // LEFT JOIN (SELECT store_id,SUM(scount) as scount from (SELECT
        // p.store_id,c.vcount as scount FROM tpos_info p
        // LEFT JOIN (SELECT pos_id,SUM(verify_num) as vcount FROM
        // tpos_day_count GROUP BY pos_id) c ON p.pos_id=c.pos_id) v GROUP BY
        // store_id) z ON s.store_id=z.store_id
        // WHERE s.node_id in (".$this->nodeIn().") ORDER BY z.scount DESC limit
        // 10";
        $verifySql = 'SELECT  a.store_id,a.store_short_name store_name,IFNULL(SUM(verify_num),0) scount FROM tstore_info  a
				LEFT JOIN tpos_info b ON a.store_id = b.store_id
				LEFT JOIN tpos_day_count c ON b.pos_id=c.pos_id
				WHERE a.node_id in ('.$this->nodeIn().')
				GROUP BY a.store_id,a.store_short_name
				ORDER BY scount DESC limit 10';
        $verifyResult = M()->query($verifySql);

        $storeCountsql = 'SELECT  a.store_id,a.store_short_name store_name,IFNULL(SUM(verify_num),0) scount FROM tstore_info  a
				LEFT JOIN tpos_info b ON a.store_id = b.store_id
				LEFT JOIN tpos_day_count c ON b.pos_id=c.pos_id
				WHERE a.node_id in ('.$this->nodeIn().") $wh2
				GROUP BY a.store_id,a.store_short_name
				ORDER BY scount DESC";

        $storeCount2 = M()->query($storeCountsql);
        $storeCount2 = count($storeCount2);
        C('VAR_PAGE', 'pr');
        $P = new Page($storeCount2, 8);
        $pShow = $P->show();
        // $listverifySql="SELECT s.store_name,IFNULL(z.scount,0) as scount FROM
        // tstore_info s
        // LEFT JOIN (SELECT store_id,SUM(scount) as scount from (SELECT
        // p.store_id,c.vcount as scount FROM tpos_info p
        // LEFT JOIN (SELECT pos_id,SUM(verify_num) as vcount FROM tpos_day_count
        // GROUP BY pos_id) c ON p.pos_id=c.pos_id) v GROUP BY store_id) z ON
        // s.store_id=z.store_id
        // WHERE s.node_id in (".$this->nodeIn().") $wh2 ORDER BY z.scount DESC
        // limit ".$P->firstRow.','.$P->listRows;
        $listverifySql = 'SELECT  a.store_id,a.store_short_name store_name,IFNULL(SUM(verify_num),0) scount FROM tstore_info  a
				LEFT JOIN tpos_info b ON a.store_id = b.store_id
				LEFT JOIN tpos_day_count c ON b.pos_id=c.pos_id
				WHERE a.node_id in ('.$this->nodeIn().") $wh2
				GROUP BY a.store_id,a.store_short_name
				ORDER BY scount DESC limit ".
        $P->firstRow.','.$P->listRows;
        $listverifyResult = M()->query($listverifySql);

        $storeVerifyMaNumber = array();
        $storeVerifyMaName = array();
        foreach ($verifyResult as $v) {
            $storeVerifyMaNumber[] = (int) $v['scount'];
            $storeVerifyMaName[] = $v['store_name'];
        }

        $this->assign('_chartData',
            json_encode(
                array(
                    'storeVerifyMaNumber' => $storeVerifyMaNumber,
                    'storeVerifyMaName' => $storeVerifyMaName, )));
        $this->assign('pShow', $pShow);
        $this->assign('listverifyResult', $listverifyResult);
        $this->display();
    }

    public function static_active()
    {
        $where = array(
            '_string' => 'a.node_id in ('.$this->nodeIn().')', );

        import('ORG.Util.Page');

        $sql = '(SELECT type,store_id,SUM(click_count) as ccount,COUNT(0) AS fcount,sum(z) bcount FROM tchannel c
    			LEFT JOIN (SELECT z,channel_id FROM (SELECT channel_id,COUNT(0) AS z FROM tbatch_channel b where b.node_id in ('.
        $this->nodeIn().') GROUP BY b.channel_id ) r) as f ON c.id=f.channel_id
   				WHERE c.type=2 and c.node_id in ('.$this->nodeIn().
            ') GROUP BY c.store_id)';

        $count = M()->table('tstore_info a')
            ->field(
                'a.store_name,a.province_code,a.city_code,a.town_code,IFNULL(fcount,0),IFNULL(bcount,0),IFNULL(ccount,0)')
            ->join("LEFT JOIN $sql e ON a.store_id=e.store_id ")
            ->where($where)
            ->count();
        $Page = new Page($count, 12);
        $pageShow = $Page->show();

        $list = M()->table('tstore_info a')
            ->field(
                'a.province,a.city,a.town,a.store_name,a.province_code,a.city_code,a.town_code,IFNULL(fcount,0) fcount,IFNULL(bcount,0) bcount, IFNULL(ccount,0) ccount')
            ->join("LEFT JOIN $sql e ON a.store_id=e.store_id ")
            ->where($where)
            ->order('a.id desc')
            ->limit($Page->firstRow.','.$Page->listRows)
            ->select();
        // echo M()->_sql();
        $this->assign('page', $pageShow);
        $this->assign('list', $list);
        $this->display();
    }

    public function storePosStatus()
    {
        $sId = I('get.sId');
        $pId = I('get.posId');
        $sStatus = I('get.sStatus');
        // 参数校验
        if (empty($sId)) {
            die(
                json_encode(
                    array(
                        'codeId' => '0001',
                        'codeText' => 'Parameter error', )));
        }

        if (empty($pId)) {
            die(
                json_encode(
                    array(
                        'codeId' => '0001',
                        'codeText' => 'Parameter error', )));
        }

        if (in_array($sStatus, array(
            '0',
            '2', )) === false) {
            die(
                json_encode(
                    array(
                        'codeId' => '0001',
                        'codeText' => 'Illegal error', )));
        }

        // 校验本地状态
        $storePosStatus = M('tpos_info')->where(
            array(
                'pos_id' => $pId,
                'store_id' => $sId, ))->getField('pos_status');
        if ($sStatus != $storePosStatus) {
            die(
                json_encode(
                    array(
                        'codeId' => '0001',
                        'codeText' => '未知错误', )));
        }

        // 开始调用接口
        $arr = array(
            'TransactionID' => date('YmdHis').mt_rand(100000, 999999),
            'SystemID ' => C('ISS_SYSTEM_ID'),
            'NodeId' => $this->node_id,
            'UserId' => $this->node_id,
            'StoreId' => $sId,
            'PosId' => $pId,
            'EnablelFlag' => $sStatus == 0 ? 0 : 1, );

        $reqResult = D('RemoteRequest', 'Service')->requestIssForImageco(
            array(
                'PosStatusModifyReq' => $arr, ));
        $codeResult = $reqResult['PosStatusModifyRes']['Status']['StatusCode'];
        // 修改失败
        if ($codeResult != '0000') {
            die(
                json_encode(
                    array(
                        'codeId' => '0002',
                        'codeText' => '修改EPOS状态失败'.print_r($reqResult, true), )));
        }

        $posStatus = $sStatus == 0 ? 2 : 0;
        $posText = $sStatus == 0 ? '停用' : '启用';
        $result = M('tpos_info')->where(
            array(
                'pos_id' => $pId,
                'store_id' => $sId, ))->save(
            array(
                'pos_status' => $posStatus, ));
        if ($result === false) {
            die(
                json_encode(
                    array(
                        'codeId' => '0003',
                        'codeText' => '修改EPOS状态失败', )));
        }

        node_log("【门店管理】终端$posText");
        die(
            json_encode(
                array(
                    'codeId' => '0000',
                    'codeText' => '修改EPOS状态成功', )));
    }

    public function storePosApply()
    {
        $sId = I('get.sId');
        $pId = I('get.posId');
        $sStatus = I('get.sStatus');

        if (empty($sId)) {
            die(
                json_encode(
                    array(
                        'codeId' => '0001',
                        'codeText' => 'Parameter error', )));
        }

        if (empty($pId)) {
            die(
                json_encode(
                    array(
                        'codeId' => '0001',
                        'codeText' => 'Parameter error', )));
        }

        if ($sStatus != 0) {
            die(
                json_encode(
                    array(
                        'codeId' => '0001',
                        'codeText' => 'Illegal error', )));
        }

        $storePosStatus = M('tpos_info')->where(
            array(
                'pos_id' => $pId,
                'store_id' => $sId, ))->getField('pos_status');
        if ($sStatus != $storePosStatus || $storePosStatus != 0) {
            die(
                json_encode(
                    array(
                        'codeId' => '0001',
                        'codeText' => '未知错误', )));
        }

        $fruit = D('StoreJob', 'Service');
        $result = $fruit->jobApply($this->node_id, $pId, $sId);
        // die(json_encode(array('codeId'=>'0003','codeText'=>print_r($result,true))));
        if ($result === true) {
            $upDate = M('tpos_info')->where(
                array(
                    'pos_id' => $pId,
                    'store_id' => $sId,
                    'node_id' => $this->node_id, ))->save(
                array(
                    'applyStatus' => 1, ));
            node_log('【门店管理】终端撤机');
            die(
                json_encode(
                    array(
                        'codeId' => '0000',
                        'codeText' => '提交申请成功，请耐心等待', )));
        }
        die(
            json_encode(
                array(
                    'codeId' => '0002',
                    'codeText' => '提交申请失败： '.$fruit->error, )));
    }

    public function getError()
    {
        $fileName = 'shibai.csv';
        header('Content-type: text/plain');
        header('Accept-Ranges: bytes');
        header('Content-Disposition: attachment; filename='.$fileName);
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        $batchNo = I('get.batchNo');
        $sblist = M('tstorebatchapply')->where(
            array(
                'fileName' => $batchNo,
                'node_id' => $this->node_id,
                'status' => 0, ))->select();
        $title = "行号,门店名称,门店简称,省,市,区,详细地址,门店联系电话,负责人,负责人手机,负责人邮箱,门店分组,失败原因\r\n";
        echo $title_ = iconv('utf-8', 'gbk', $title);
        if ($sblist) {
            foreach ($sblist as $v) {
                echo $v['lineNumber'].',',
                iconv('utf-8', 'gbk', $v['StoreName']).',',
                iconv('utf-8', 'gbk', $v['StoreShortName']).',',
                iconv('utf-8', 'gbk', $v['province_code']).',',
                iconv('utf-8', 'gbk', $v['city_code']).',',
                iconv('utf-8', 'gbk', $v['town_code']).',',
                iconv('utf-8', 'gbk', $v['address']).',',
                iconv('utf-8', 'gbk', $v['storeTel']).',',
                iconv('utf-8', 'gbk', $v['ContactName']).',',
                iconv('utf-8', 'gbk', $v['ContactTel']).',',
                iconv('utf-8', 'gbk', $v['ContactEmail']).',',
                iconv('utf-8', 'gbk', $v['group_name']).',',
                iconv('utf-8', 'gbk', $v['statusPs'])."\r\n";
            }
        }
    }

    public function setError($str, $batchNo = '')
    {
        if ($batchNo != '') {
            $sbNumber = M('tstorebatchapply')->where(
                array(
                    'fileName' => $batchNo,
                    'node_id' => $this->node_id,
                    'status' => 0, ))->count();
            if ($sbNumber > 0) {
                $str .= ',失败'.$sbNumber.
                    "条，<a href='index.php?g=Home&m=Store&a=getError&batchNo=".
                    $batchNo."'>查看详细</a>";
            }
        }
        $this->assign('str', $str);
        $this->display('setError');
        exit();
    }

    public function batchApply()
    {
        C('STORE_TPL_APPLY', APP_PATH.'Upload/store_apply/');
        if (IS_POST) {
            import('ORG.Net.UploadFile') or die('导入上传包失败');
            $upload = new UploadFile();
            $upload->maxSize = 3145728;
            $upload->savePath = C('STORE_TPL_APPLY');
            $info = $upload->uploadOne($_FILES['staff']);
            $flieWay = $upload->savePath.$info['savepath'].$info[0]['savename'];
            $row = 0;
            $filename = explode('.', pathinfo($flieWay, PATHINFO_BASENAME));
            if (pathinfo($flieWay, PATHINFO_EXTENSION) != 'csv') {
                @unlink($flieWay);
                $this->setError('文件类型不符合');
            }
            if (($handle = fopen($flieWay, 'rw')) !== false) {
                while (($arr = fgetcsv($handle, 1000, ',')) !== false) {
                    ++$row;
                    $arr = utf8Array($arr);

                    if ($row == 1) {
                        $fileField = array(
                            '门店简称',
                            '省',
                            '市',
                            '区',
                            '详细地址',
                            '门店联系电话',
                            '负责人',
                            '负责人手机',
                            '负责人邮箱',
                            '门店分组', );
                        $arrDiff = array_diff_assoc($arr, $fileField);
                        if (count($arr) != count($fileField) || !empty($arrDiff)) {
                            fclose($handle);
                            @unlink($flieWay);
                            $this->setError(
                                '文件第'.$row.'行字段不符合要求,请确保与下载模板文件中第一行的字段保持一致!');
                        }
                        continue;
                    }
                    if (mb_strlen($arr[9], 'utf8') >= 9) {
                        $this->setError('文件第'.$row.'行门店分组字数最多8个字');
                        continue;
                    }

                    $array = array();
                    $array['StoreName'] = $arr[0];
                    $array['StoreShortName'] = $arr[0];
                    $array['province_code'] = $arr[1];
                    $array['city_code'] = $arr[2];
                    $array['town_code'] = $arr[3];
                    $array['address'] = $arr[4];
                    $array['storeTel'] = $arr[5];
                    $array['ContactName'] = $arr[6];
                    $array['ContactTel'] = $arr[7];
                    $array['ContactEmail'] = $arr[8];
                    $array['group_name'] = $arr[9];
                    $result = $this->checkFileContent($array);
                    $array['applytime'] = date('YmdHis');
                    $array['node_id'] = $this->node_id;
                    $array['lineNumber'] = $row;

                    $array['fileName'] = $filename[0];
                    if ($result === false) {
                        $array['statusPs'] = $this->file_error;
                        $fruit = M('tstorebatchapply')->add($array);
                        continue;
                    }

                    $req_result = D('RemoteRequest', 'Service')->requestIssServ(
                        array(
                            'CreateStoreReq' => $result, ));
                    $respStatus = isset($req_result['CreateStoreRes']) ? $req_result['CreateStoreRes']['Status'] : $req_result['Status'];
                    if ($respStatus['StatusCode'] != '0000') {
                        $array['statusPs'] = $respStatus['StatusText'];
                        $fruit = M('tstorebatchapply')->add($array);
                        continue;
                    }
                    $respData = $req_result['CreateStoreRes'];
                    $store_id = $respData['StoreId'];
                    if (!$store_id) {
                        $array['statusPs'] = '创建支撑门店失败';
                        $fruit = M('tstorebatchapply')->add($array);
                        continue;
                    }

                    if (M('tstore_info')->where(
                        array(
                            'store_id' => $store_id, ))->count()) {
                        $array['statusPs'] = '门店号['.$store_id.']已经存在。';
                        $fruit = M('tstorebatchapply')->add($array);
                        continue;
                    }
                    $result['store_phone'] = $array['storeTel'];
                    $result['store_id'] = $store_id;
                    $storeId = $this->addStoreTable($result,
                        $arr[2].$arr[3].$arr[4].$arr[5]);
                    if ($storeId) {
                        // 设置门店分组
                        $storeGroupModel = $this->getStoresGroupModel();
                        $setGroup = $storeGroupModel->setGroup($this->node_id,
                            $arr[9], $store_id);
                        if ($setGroup['status'] == 1) {
                            $array['status'] = 1;
                        } else {
                            $array['statusPs'] = $setGroup['info'];
                        }
                    } else {
                        $array['statusPs'] = '门店入库异常';
                    }
                    $fruit = M('tstorebatchapply')->add($array);
                }
            }
            @fclose($handle);
            // $sbNumber=M('tstorebatchapply')->where(array('fileName'=>$filename[0],'node_id'=>$this->node_id,'status'=>0))->count();
            $this->setError('导入门店成功', $filename[0]);
        }

        $this->display();
    }

    private function getGlide()
    {
        $sql = "SELECT _nextval('sys_uni_seq') as u FROM DUAL";
        $fruit = M()->query($sql);
        $number = $fruit[0]['u'];

        return str_pad($number, 20, '0', STR_PAD_LEFT);
    }

    private function checkFileContent($arr)
    {
        $data = array();

        if ($arr['StoreShortName'] == '') {
            $this->file_error = '门店简称为空';

            return false;
        }
        if ($arr['province_code'] == '') {
            $this->file_error = '门店省份为空';

            return false;
        }
        if ($arr['city_code'] == '') {
            $this->file_error = '门店城市为空';

            return false;
        }
        /*
         * if($arr['town_code']== '') { $this->file_error='门店区[县]为空'; return false;
         * }
         */
        if ($arr['address'] == '') {
            $this->file_error = '门店详细地址为空';

            return false;
        }
        /*
         * if($arr['storeTel']== '') { $this->file_error='门店联系电话为空'; return false; }
         */
        if ($arr['ContactName'] == '') {
            $this->file_error = '门店负责人为空';

            return false;
        }
        if ($arr['ContactTel'] == '') {
            $this->file_error = '门店负责人电话为空';

            return false;
        }
        if ($arr['ContactEmail'] == '') {
            $this->file_error = '门店负责人邮箱为空';

            return false;
        }
        if (!preg_match('/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/',
            $arr['ContactEmail'])) {
            $this->file_error = '门店负责人邮箱格式不对';

            return false;
        }
        $prow = M('tcity_code')->where(
            array(
                '_string' => "province like '%".$arr['province_code']."%'", ))->getfield(
            'province_code');
        if ($prow == '') {
            $this->file_error = '门店省份没找到';

            return false;
        }

        $crow = M('tcity_code')->where(
            array(
                '_string' => "city like '%".$arr['city_code']."%'", ))->getfield(
            'city_code');
        if ($crow == '') {
            $this->file_error = '门店城市没找到';

            return false;
        }
        /*
         * $trow=M('tcity_code')->where(array('_string'=>"town like
         * '%".$arr['town_code']."%'"))->getfield('town_code'); if($trow == '') {
         * $this->file_error='门店区[县]没找到'; return false; }
         */
        $req_arr = array();
        $req_arr['SystemID'] = C('ISS_SYSTEM_ID');
        $req_arr['TransactionID'] = time().mt_rand('1000', '9999');
        $req_arr['ISSPID'] = $this->node_id;
        $req_arr['UserId'] = $this->userId;
        $req_arr['Url'] = '<![CDATA[旺财会员账户中心]]>';
        $req_arr['CustomNo'] = I('custom_no');
        $req_arr['StoreName'] = $arr['StoreShortName'];
        $req_arr['StoreShortName'] = $arr['StoreShortName'];
        $req_arr['ContactName'] = $arr['ContactName'];
        $req_arr['ContactTel'] = $arr['ContactTel'];
        $req_arr['ContactEmail'] = $arr['ContactEmail'];
        $req_arr['RegionInfo'] = array(
            'Province' => $prow,
            'City' => $crow,
            'Town' => $trow,
            'Address' => $arr['address'], );

        return $req_arr;
    }

    private function addStoreTable($arr, $strCity)
    {
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
        $str = preg_replace($qian, $hou, $strCity);
        $xyUrl = $this->addUrl($str);
        $data = array(
            'store_id' => $arr['store_id'],
            'node_id' => $this->node_id,
            'store_name' => $arr['StoreShortName'],
            'store_short_name' => $arr['StoreShortName'],
            // 'store_desc'=>I('store_name'),
            'province_code' => $arr['RegionInfo']['Province'],
            'city_code' => $arr['RegionInfo']['City'],
            'town_code' => $arr['RegionInfo']['Town'],
            'address' => $arr['RegionInfo']['Address'],
            'principal_name' => $arr['ContactName'],
            'lbs_x' => $xyUrl['x'],
            'lbs_y' => $xyUrl['y'],
            // 'principal_position'=>$arr['store_id'],
            'principal_tel' => $arr['ContactTel'],
            // 'principal_phone'=>I('principal_phone'),
            'principal_email' => $arr['ContactEmail'],
            'status' => 0,
            'add_time' => date('YmdHis'),
            'store_phone' => $arr['store_phone'], );

        return M('tstore_info')->add($data);
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

    public function _FreePosCreateNotice()
    {
        $info = $this->_hasFreePosCreatePower(true);
        // 如果当前没有限免活动
        if (!$info['currFreeInfo']) {
            return array(
                'notice' => '', );
        }

        $free_end_day = dateformat($info['currFreeInfo']['pos_end_time'], 'Y年m月d日');
        // 如果当前机构没有参与限免的记录
        if (!$info['nodeFreeInfo']) {
            $batch_type = $info['currFreeInfo']['batch_type'];
            $batch_name = C('BATCH_TYPE_NAME.'.$batch_type);
            $batch_list_url = U(C('BATCH_LIST_URL.'.$batch_type));

            return array(
                'notice' => '请先创建"'.$batch_name.'"活动，<a href="'.$batch_list_url.
                '">立即创建</a>',
                'free_end_day' => $free_end_day, );
        }

        // 如果当前机构已经参与以前的限免活动
        if ($info['nodeFreeInfo']['add_time'] < $info['currFreeInfo']['begin_time']) {
            $batch_type = $info['nodeFreeInfo']['batch_type'];
            $batch_name = C('BATCH_TYPE_NAME.'.$batch_type);

            return array(
                'notice' => '您参与过'.$batch_name.',请开通<a href="'.
                U('Home/Wservice/buywc').'">标准版</a>', );
        } else {
            return array(
                'free_end_day' => $free_end_day, );
        }
    }

    public function _hasFreePosCreatePower($return = false)
    {
        static $currFreeInfo = null;
        static $nodeFreeInfo = null;
        static $posCount = null;
        if ($return) {
            return array(
                'currFreeInfo' => $currFreeInfo,
                'nodeFreeInfo' => $nodeFreeInfo,
                'posCount' => $posCount, );
        }
        $now = date('YmdHis');
        // 获取当前时间段的限免活动
        if ($currFreeInfo === null) {
            $currFreeInfo = M('tbatch_free_set')->where(
                "'{$now}' between begin_time and end_time")->find();
        }
        if (!$currFreeInfo) {
            return false;
        }

        // 判断商户是否有参与过限免活动
        $node_map = array(
            'node_id' => $this->node_id, );
        if ($nodeFreeInfo === null) {
            $nodeFreeInfo = M('tbatch_free_trace')->where($node_map)->find();
        }

        if (!$nodeFreeInfo) {
            return true;
        }

        if ($nodeFreeInfo['add_time'] >= $currFreeInfo['begin_time'] &&
            $nodeFreeInfo['end_time'] <= $currFreeInfo['end_time']) {
            // 判断商户是否已经有终端
            if ($posCount === null) {
                $posCount = M('tpos_info')->where($node_map)->count();
            }

            if ($posCount > 0) {
                return false;
            }

            return true;
        } else {
            return false;
        }
    }

    private function isFreeEpos()
    {
        $isFreeUser = D('node')->getNodeVersion($this->node_id);
        // 控制显示“申请免费epos” 还是普通的“申请epos”
        $freeEpos = false;
        $posCount = M('tpos_info')->where(
            array(
                'node_id' => $this->node_id, ))->count();
        if ($posCount == 0 && $isFreeUser && time() < strtotime('2015-10-17')) {
            $freeEpos = true;
        }

        return $freeEpos;
    }

    public function checkMoney()
    {
        $wc_version = get_wc_version();
        if ($wc_version == 'v4') {
            $this->success('余额充足');
        }
        $FlowInfo = $this->getAccountInfo();
        $nodeWbList = $this->getWbInfo();
        $accountprice = $FlowInfo['AccountPrice'] ? $FlowInfo['AccountPrice'] : 0;
        $wbover = $nodeWbList['wbOver'] ? $nodeWbList['wbOver'] : 0;
        $totalMoney = $accountprice + $wbover;
        $needMoney = floor(
            (strtotime(date('Ym01', strtotime('+1 month'))) - strtotime(date('Ymd'))) /
            (3600 * 24));
        if ($totalMoney >= 30 || $totalMoney >= $needMoney) {
            $this->success('余额充足');
        } else {
            $this->error('余额不足');
        }
    }

    public function onlineStore()
    {
        $storeModel = D('Stores');
        if ($this->isPost()) {
            $type = I('get.type');
            // 检查恶意参数，1表示申请，2表示停用，3表示启用
            in_array($type,
                array(
                    '1',
                    '2',
                    '3', )) or $this->error('参数有误');
            if ($type == '1') {
                try {
                    // 判断是否有线上门店，若否，则开之
                    $onlineStoreInfo = $storeModel->getOnlineStore($this->nodeId);
                    if (empty($onlineStoreInfo)) {
                        $storeModel->zcCreateOnlineStore($this->nodeId);
                    } else {
                        log_write($this->nodeId.'已经申请过线上门店，此时却再次申请而不是启用，请检查数据库');
                        $this->error('您已申请开通过线上门店！');
                    }
                    // 判断是否创建epos，若否，则开之
                    $eposInfo = $storeModel->getEpos($this->nodeId);
                    if (empty($eposInfo)) {
                        $storeModel->zcCreateEpos($this->nodeId);
                    } else {
                        log_write(
                            $this->nodeId.'已经申请过线上门店终端pos，此时却再次申请而不是启用，请检查数据库');
                        $this->error('您已申请开通了线上门店终端，请勿重复申请');
                    }
                } catch (Exception $e) {
                    $this->error($e->getMessage());
                }
                $this->success('提交申请成功');
            } elseif ($type == '3') {
                try {
                    $onlineStoreInfo = $storeModel->makeActiveEnable($this->nodeId,
                        $this->userInfo['user_id']);
                } catch (Exception $e) {
                    $this->error($e->getMessage());
                }
                $this->success('启用成功');
            } else {
                try {
                    $onlineStoreInfo = $storeModel->stopOnline($this->nodeId,
                        $this->userInfo['user_id']);
                } catch (Exception $e) {
                    $this->error($e->getMessage());
                }
                $this->success('停用成功');
            }
        }
        // 查询线上门店是否存在
        $storeInfo = M('tstore_info')->where(
            array(
                'node_id' => $this->nodeId,
                'type' => '3', ))->find();
        // 线上门店不存在，表示还没有申请
        if (empty($storeInfo)) {
            $this->assign('isAppling', 1); // 未申请
        } else {
            // 线上门店的状态是1，表示已经被停用了
            if ($storeInfo['status'] == '1') {
                $this->assign('isAppling', 2); // 已停用
            } else {
                // 线上门店状态正常，去查询终端是否存在
                $posInfo = M('tpos_info')->where(
                    array(
                        'node_id' => $this->nodeId,
                        'store_id' => $storeInfo['store_id'], ))->find();
                if (empty($posInfo)) {
                    // 如果门店的创建时间已经过去了10分钟
                    if ((time() - strtotime($storeInfo['add_time'])) > 10 * 60) {
                        $this->assign('isAppling', 3); // 申请失败
                    } else {
                        $this->assign('isAppling', 5); // 申请中
                    }
                } else {
                    $this->assign('isAppling', 4); // 已开通
                }
            }
        }
        $this->display();
    }

    /**
     * @return StoresModel
     */
    private function getStoresModel()
    {
        if (empty($this->storesModel)) {
            $this->storesModel = D('Stores');
        }

        return $this->storesModel;
    }

    /**
     * @return StoresGroupModel
     */
    private function getStoresGroupModel()
    {
        if (empty($this->storesGroupModel)) {
            $this->storesGroupModel = D('StoresGroup');
        }

        return $this->storesGroupModel;
    }
    /**
     * 获取子商户的所有nodeId.
     *
     * @param string $str 多数是个SQL语句
     *
     * @return mixed
     */
    public function getNodeIn($str)
    {
        if (stripos($str, 'from')) {
            $result = M()->query($str);
            $nodeArr = array();
            foreach ($result as $key => $value) {
                $nodeArr[] = $value['node_id'];
            }
            $str = implode(',', $nodeArr);
        }

        return $str;
    }

    /**
     * 门店分组.
     */
    public function group()
    {
        $groupName = I('get.groupName', '');
        $storeModel = $this->getStoresModel();
        $nodeIn = $this->nodeIn();
        $nodeIn = $this->getNodeIn($nodeIn);
        // 制作分页
        import('ORG.Util.Page');
        if (empty($groupName)) {
            $countStores = $storeModel->getAllStores($nodeIn, '', true);
        } else {
            $countStores = $storeModel->getAllStores($nodeIn, $groupName,
                true);
        }
        $p = new Page($countStores, self::NUMBER_PER_PAGE);
        $page = $p->show();

        // 获取分组信息并发送
        if (empty($groupName)) {
            $allGroups = $storeModel->getAllStores($nodeIn, '', false,
                $p->firstRow, $p->listRows);
        } else {
            $allGroups = $storeModel->getAllStores($nodeIn, $groupName,
                false, $p->firstRow, $p->listRows);
            $this->assign('groupName', $groupName);
        }

        $this->assign('allGroups', $allGroups);
        $this->assign('page', $page);
        $this->display();
    }

    /**
     * 添加分组 (名称).
     */
    public function addGroupName()
    {
        $storesGroupModel = $this->getStoresGroupModel();
        $getPost = I('post.');

        $isOk = $storesGroupModel->addGroupName($this->node_id,
            $getPost['groupName']);

        $this->ajaxReturn($isOk, 'JSON');
    }

    /**
     * 修改分组 (名称).
     */
    public function modifyGroupName()
    {
        $receive = I('post.'); // 修改的时候是带着分组ID来的

        $storesGroupModel = $this->getStoresGroupModel();

        $isOk = $storesGroupModel->modGroupName($this->node_id, $receive);

        $this->ajaxReturn($isOk, 'JSON');
    }

    /**
     * 初始化编辑门店页面 （新增）.
     */
    public function initializeEditGroup()
    {
        $isNewAdd = I('get.newAdd');

        if ($isNewAdd == 'true') {
            $storesModel = $this->getStoresModel();

            $gid = I('get.id');
            $nodeIn = $this->nodeIn();
            $nodeIn = $this->getNodeIn($nodeIn);
            $nowInGroup = $storesModel->getNowInGroupStore($nodeIn, $gid);

            $groupName = $nowInGroup['0']['group_name'];
            if (empty($nowInGroup['0']['store_id'])) {
                $nowInGroup = array();
            }

            $this->assign('nowInGroup', $nowInGroup);
            $this->assign('groupName', $groupName);
            $this->assign('countStores', count($nowInGroup));
            $this->assign('groupId', $gid);
        } else {
            $this->assign('nowInGroup', array());
            $this->assign('countStores', 0);
        }

        $this->assign('noticeGroup', cookie('noticeGroup'));
        $this->assign('newAdd', $isNewAdd);
        $this->display();
    }

    /**
     * 未被分组的所有门店.
     */
    public function noGroupStore()
    {
        $gid = I('get.id');
        $storesModel = $this->getStoresModel();
        $nodeIn = $this->nodeIn();
        $nodeIn = $this->getNodeIn($nodeIn);
        $getAllStores = $storesModel->getUnGroupedAllStore($nodeIn);

        if (IS_POST) {
            $where = array();
            foreach ($getAllStores as $key => $value) {
                $where[] = $value['store_id'];
            }

            $where = array(
                'a.store_id' => array(
                    'in',
                    implode(',', $where), ), );

            $areaType = I('post.city_type');
            $query_arr = (array) $storesModel->areaFilter($nodeIn, $areaType,
                $where);
            $this->ajaxReturn($query_arr, '查询成功', 0);
            exit();
        }
        $this->assign('gid', $gid);
        $this->assign('allStores', $getAllStores);
        $this->display('./Home/Tpl/Home/Store_storePopup.html');
    }

    /**
     * 编辑分组门店.
     */
    public function editGroup()
    {
        $posData = I('post.');
        $nodeId = $this->node_id;
        $nodeIn = $this->nodeIn();
        $nodeIn = $this->getNodeIn($nodeIn);
        $storesGroupModel = $this->getStoresGroupModel();

        if (empty($posData['groupName'])) {
            $this->ajaxReturn(
                array(
                    'status' => 0,
                    'info' => '分组名称不能为空', ), 'JSON');
        }
        if ($posData['notice'] == 'true') {
            cookie('noticeGroup', $posData['notice'], 60 * 60 * 24 * 365);
        }

        //通知支撑 ===============start=======================
        $params = [];
        $params['operate'] = 'editGroup';
        $params['groupId'] = I('gid');
        $params['addStoreIdList'] = I('addStoreId');
        $params['delStoreIdList'] = I('delStoreId');
        $params['node_id'] = $this->node_id;
        $behaviorReturn = BR('UpdateStoreGroup', $params);
        file_debug($params, 'edit store $params', 'request.log');
        file_debug($behaviorReturn, 'edit store $behaviorReturn', 'request.log');
        $params = null;
        if ($behaviorReturn['status'] == 0) {
            //通知支撑报错
            M()->rollback();
            $this->error($behaviorReturn['msg']);
        }
        //通知支撑   ===============end=======================

        // 编辑分组名称
        if ($posData['gid'] == 'false') {
            $editName = $storesGroupModel->addGroupName($nodeId,
                $posData['groupName']);
            $posData['gid'] = $editName;
        } else {
            $editName = $storesGroupModel->modGroupName($nodeIn, $posData);
        }
        if (!is_numeric($editName)) {
            $this->ajaxReturn(
                array(
                    'status' => 0,
                    'info' => '修改分组名称失败', ), 'JSON');
        }

        $add = true;
        $del = true;
        // 开启事务
        $tranDb = new Model();
        $tranDb->startTrans();
        // 添加所有门店
        if ($posData['addStoreId'] == 'allStores') {
            $addStore = array();
            $storesModel = $this->getStoresModel();
            $getUnGroupStore = $storesModel->getUnGroupedAllStore($nodeIn);
            if (!empty($getUnGroupStore)) {
                foreach ($getUnGroupStore as $key => $value) {
                    $addStore[] = $value['store_id'];
                }
                $add = $storesGroupModel->addGroupStore(implode(',', $addStore),
                    $posData['gid']);
            } else {
                $add = false;
            }
        }

        // 添加
        if (!empty($posData['addStoreId']) && $posData['addStoreId'] != 'allStores') {
            $add = $storesGroupModel->addGroupStore($posData['addStoreId'],
                $posData['gid']);
        }
        // 删除
        if (!empty($posData['delStoreId'])) {
            $del = $storesGroupModel->delGroupStore($posData['delStoreId']);
        }

        if (!$add || !$del) {
            $tranDb->rollback();
            $this->ajaxReturn(
                array(
                    'status' => 0,
                    'info' => '操作失败', ), 'JSON');
        }

        $tranDb->commit();
        $this->ajaxReturn(array(
            'status' => 1, ), 'JSON');
    }

    /**
     * 删除分组.
     */
    public function delGroup()
    {
        $groupId = I('get.groupId');
        $storesGroupModel = $this->getStoresGroupModel();
        $nodeIn = $this->nodeIn();
        $nodeIn = $this->getNodeIn($nodeIn);
        // 检测还有多少个门店在该分组内
        $result = $storesGroupModel->delGroup($nodeIn, $groupId);

        switch ($result) {
            case StoresGroupModel::DEL_GROUP_STATUS_NON: // 不能删除
                $this->ajaxReturn('分组内有门店，请先删除!', 'JSON');
                break;
            case StoresGroupModel::DEL_GROUP_STATUS_SUCCESS: // 删除成功
                $this->ajaxReturn(true, 'JSON');
                break;
            default: // 删除失败
                $this->ajaxReturn($result, 'JSON');
        }
    }
    /**
     * 门店活动.
     */
    public function active()
    {
        $s_time = I('s_time');
        $this->assign('s_time', $s_time);

        $e_time = I('e_time');
        $this->assign('e_time', $e_time);

        $activity_type = I('activity_type');
        $this->assign('activity_type', $activity_type);

        $keyWords = I('keyWords');
        $this->assign('keyWords', $keyWords);

        $wh = array(
                'node_id' => $this->nodeId,
                'status' => '0',
        );

        //创建时间筛选
        if ($s_time && $e_time) {
            $wh['add_time'] = array('between', date('Ymd', strtotime($s_time)).'000000,'.date('Ymd', strtotime($e_time)).'235959');
        } elseif ($s_time && !$e_time) {
            $wh['add_time'] = array('gt', date('Ymd', strtotime($s_time)).'000000');
        } elseif (!$s_time && $e_time) {
            $wh['add_time'] = array('lt', date('Ymd', strtotime($e_time)).'235959');
        } else {
            $seven = date('Ymd', strtotime('-7 day')).'000000';
            $wh['add_time'] = array('gt', $seven);
            $this->assign('s_time', date('Y-m-d', strtotime('-7 day')));
            $this->assign('e_time', date('Y-m-d', time()));
        }

        $wh['end_time'] = array('gt', getTime(1));

        if ($activity_type !== '') {
            $wh['activity_type'] = $activity_type;
        }

        if ($keyWords != '') {
            $wh['activity_title'] = array('like', '%'.$keyWords.'%');
        }

        import('ORG.Util.Page'); // 导入分页类
        $count = M()->table('tstore_activity_info')->field(
                array('count(DISTINCT id)' => 'tp_count'))->where($wh)->find(); // 查询满足要求的总记录数
        $count = $count['tp_count'];

        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $pageShow = $Page->show(); // 分页显示输出

        $activityList = M()->table('tstore_activity_info')
                ->where($wh)
                ->order('id desc')
                ->limit($Page->firstRow.','.$Page->listRows)
                ->select();

        // 全部门店时，当前机构下的门店总数
        $where = array(
                '_string' => 'node_id in ('.$this->nodeId.
                        ') AND type NOT IN (3,4)', );
        $storeCount = M()->table('tstore_info')->where($where)->count();

        $activity_type_Arr = array(
                '1' => '优惠',
                '2' => '打折',
                '3' => '促销',
                '4' => '服务',
        );

        foreach ($activityList as $k => &$v) {
            if ($v['store_join_flag'] == '1') {
                $v['count'] = $storeCount;
            } elseif ($v['store_join_flag'] == '2') {
                $count = M('tstore_activity_relation')->where('activity_id = '.$v['id'])->count();
                $v['count'] = $count;
            } else {
                $v['count'] = '0';
            }
            $v['activity_type'] = $activity_type_Arr[$v['activity_type']];
        }

        $this->assign('storeCount', $storeCount);
        $this->assign('activity_type_Arr', $activity_type_Arr);
        $this->assign('pageShow', $pageShow);
        $this->assign('ActivityList', $activityList);
        $this->display();
    }

    /**
     * 添加新活动.
     */
    public function activeAdd()
    {
        if (IS_POST) {
            // 获取表单数据
            $getPost = I('post.');

            // 活动标题
            if (empty($getPost['activity_title'])) {
                $this->error('活动标题不能为空');
            } elseif (mb_strlen($getPost['activity_title'], 'utf8') > 10) {
                $this->error('活动标题不得大于12个字');
            }

            // 活动类型
            if (empty($getPost['activity_type'])) {
                $this->error('活动类型不能为空');
            }

            // 活动有效期，开始时间
            if (empty($getPost['start_time'])) {
                $this->error('请输入活动开始时间');
            }
            $start_time = date('Ymd', strtotime($getPost['start_time'])).'000000';
            // 活动有效期，截止时间
            if (empty($getPost['end_time'])) {
                $this->error('请输入活动截止时间');
            }
            $end_time = date('Ymd', strtotime($getPost['end_time'])).'235959';
            // 活动详情
            if (empty($getPost['activity_desc'])) {
                $this->error('请输入活动详情');
            }
            //活动图片
           /* if (empty($getPost['batch_img'])) {
                $this->error('请添加活动图片');
            }*/

//http://static.wangcaio2o.com/Home/Upload/..
            $zhichengImg = '<![CDATA[';
            $image = '';

            if (!empty($getPost['batch_img'])) {
                foreach ($getPost['batch_img'] as $k => $v) {
                    if (strpos($v,'http://test.wangcaio2o.com/Home/Upload/') === false) {       //上线改地址
                        $getPost['batch_img'][$k] = 'http://test.wangcaio2o.com/Home/Upload/'.$v;
//                        $zhichengImg .= 'http://static.wangcaio2o.com/Home/Upload/'.$v.'|';
//                        $image .= $v.'|';
                    }
                }

                $imgArr = implode('|', $getPost['batch_img']);
                $zhichengImg = $imgArr;
                $image = $imgArr;
                $zhichengImg = '<![CDATA['.$zhichengImg.']]>';//发给支撑用
            } else {
                $zhichengImg = '';
                $image = '';
            }


           if ($getPost['checktypeStore'] == '1') {        //1 -所有门店  2-指定门店
               $store_join_flag = 1;
           } else {
               $store_join_flag = 2;
           }

//            if ($getPost['active_type']=='add'){
//                $store_join_flag = 1;
//            }

            $data = array(
                    'node_id' => $this->nodeId,
                    'activity_title' => $getPost['activity_title'],
                    'activity_type' => $getPost['activity_type'],
                    'activity_desc' => $getPost['activity_desc'],
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'store_join_flag' => $store_join_flag,
                    'batch_img' => $getPost['batch_img'],
                    'add_time' => date('YmdHis'),
                    'activity_image' => $image,
                    'status'    => 0,
            );

            if ($getPost['active_type'] == 'edit') {
                //如果是从修改提交过来的 则先删除门店关联
                if (!$getPost['activity_id']) {
                    $this->error('非法操作');
                }
                $del['activity_id'] = $getPost['activity_id'];
                $whe['id'] = $getPost['activity_id'];
                //$whe['id'] = '128';
                M('tstore_activity_relation')->where($del)->delete();
                $result = M('tstore_activity_info')->where($whe)->save($data);
                $result = $getPost['activity_id'];
               // var_dump($result);exit;
                $return['info'] = '门店活动修改';
            } else {
//                $data['status'] = '0';
                $result = M('tstore_activity_info')->add($data);
                $return['info'] = '门店活动添加';
            }

            if ($result) {
                // 整理数据，发往支撑
                $TransactionID = date('YmdHis').mt_rand(100000, 999999);
                $req_arr = array(
                        'StoreActivityInfoSetReq' => array(
                                'TransactionID' => $TransactionID,
                                'ActivityID' => $result,
                                'NodeID' => $this->nodeId,
                                'ActivityTitle' => $getPost['activity_title'],
                                'ActivityType' => $getPost['activity_type'],
                                'ActivityDesc' => '<![CDATA['.$getPost['activity_desc'].']]>',
                                'StartTime' => $start_time,
                                'EndTime' => $end_time,
                                'Status' => '0',
                                'StoreJoinFlag' => $store_join_flag,
                                'StoreList' => implode('|', explode(',', $getPost['openStores'])),
                                'ImagesList' => $zhichengImg, ),
                );

                $remoteSsoService = D('RemoteRequest', 'Service');
                $requestUrl = C('ISS_SERV_FOR_IMAGECO');
                $resp = $remoteSsoService->requestSsoServ($req_arr, $requestUrl);

                if ($store_join_flag == 2) {
                    $whe['id'] = '';
                    $flagArr = explode(',', $getPost['openStores']);
                    $whe['activity_id'] = $result;
                    foreach ($flagArr as $k => $v) {
                        $whe['store_id'] = $v;
                        M('tstore_activity_relation')->add($whe);
                        //echo M()->_sql();
                    }
                }

                $return['status'] = '1';
                $return['info'] = $return['info'].'成功';
                $this->ajaxReturn($return);
            } else {
                $return['status'] = '0';
                $return['info'] = $return['info'].'失败';
                $this->ajaxReturn($return);
            }
        }
    }

    /*
     * 编辑活动
     */
    public function activeEdit()
    {
        $this->assign('type', 'add');
        $opened_stores_count = 0;
        $checktypeStore = 1;     //默认初始页面为选中所有门店
        if ($_GET['id']) {
            //显示原内容
            $find['id'] = $_GET['id'];
            $activity = M('tstore_activity_info')->where($find)->find();//活动信息
            if(!$activity){
                $this->error('请求有误');
            }
            $imgArr = array();
            if ($activity['activity_image']) {
                $imgArr = explode('|', $activity['activity_image']);
            }
            $navigation = array();
            $OpenedStores = array();

            $checktypeStore = $activity['store_join_flag'];

            if($checktypeStore == '2'){
                //指定门店
                $OpenedStores = M('tstore_activity_relation')->where(array('activity_id' => $_GET['id']))->getField('store_id', true);

                $storeModel = M('tstore_info');
                foreach ($OpenedStores as $k => $v) {
                    $store['store_id'] = $v;
                    $navigation[] = $storeModel->where($store)->find();//门店信息
                }
            }



            $this->assign('imgArr', $imgArr);
            $this->assign('navigation', $navigation);
            $this->assign('opened_stores', implode(',', $OpenedStores));
            $opened_stores_count = count($OpenedStores);
            $this->assign('activity', $activity);
            $this->assign('type', 'edit');
            $this->assign('activity_id', $_GET['id']);
        }

        $this->assign('checktypeStore',$checktypeStore);
        $this->assign('opened_stores_count', $opened_stores_count);
        $this->display();
    }

    /**
     *  门店活动详情.
     */
    public function activeDetils()
    {
        $id = I('id');
        if (!$id) {
            $this->error('操作有误');
        }
        $wh['id'] = $id;
        $activityData = M('tstore_activity_info')->where($wh)->find();

        $where = array(
                '_string' => 'node_id in ('.$this->nodeId.
                        ') AND type NOT IN (3,4)', );

        if ($activityData['store_join_flag'] == '1') {
            $storeArr = M()->table('tstore_info')->where($where)->select();
        } else {
            $storeArr = M('tstore_info')->alias('t')
                    ->join('inner join tstore_activity_relation r on r.store_id=t.store_id')
                    ->where('r.activity_id = '.$activityData['id'])
                    ->select();
        }

        $activity_type_Arr = array(
                '1' => '优惠',
                '2' => '打折',
                '3' => '促销',
                '4' => '服务',
        );

        foreach ($activityData as $k => &$v) {
            if ($k == 'activity_type') {
                $v = $activity_type_Arr[$v];
            }
            if ($k == 'start_time') {
                $v = getTime(2, $v);
            }
            if ($k == 'end_time') {
                $v = getTime(2, $v);
            }
        }

        $this->assign('count', count($storeArr));
        $this->assign('activityData', $activityData);
        $this->assign('storeArr', $storeArr);
        $this->display();
    }

    /**
     * 查看活动适用门店.
     */
    public function storeInfo()
    {
        $id = I('id');
        $type = I('type');
        if (!$id || !$type) {
            $this->error('操作有误');
        }

        import('ORG.Util.Page'); // 导入分页类

        $where = array('_string' => 'node_id in ('.$this->nodeId.') AND type NOT IN (3,4)');

        if ($type == '1') {
            $count = M()->table('tstore_info')->where($where)->count();
        } else {
            $count = M('tstore_info')->alias('t')
                    ->join('inner join tstore_activity_relation r on r.store_id=t.store_id')
                    ->where('r.activity_id = '.$id)
                    ->count();
        }

        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $pageShow = $Page->show(); // 分页显示输出

        if ($type == '1') {
            $storeArr = M()->table('tstore_info')->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();
        } else {
            $storeArr = M('tstore_info')->alias('t')
                    ->join('inner join tstore_activity_relation r on r.store_id=t.store_id')
                    ->where('r.activity_id = '.$id)
                    ->limit($Page->firstRow.','.$Page->listRows)
                    ->select();
        }

        $this->assign('storeArr', $storeArr);
        $this->assign('pageShow', $pageShow);
        $this->display();
    }

    /*
     * 选择指定门店
     */
    public function toActivity()
    {
        $nodeIn = $this->nodeIn();
        $nodeIn = $this->getNodeIn($nodeIn);
        $storesModel = $this->getStoresModel();
        $where = array(
                '_string' => 'node_id in ('.$this->nodeIn().
                        ') AND type NOT IN (3,4)', );


        $naviSta = '';
        if(I('type') == 9){
            //导航设置进来的 没定位的不要
            $naviSta = 1;
            $where['_string'] .=  ' AND lbs_x >0 AND lbs_y > 0';

            $no['node_id'] = $this->node_id;
            $no['type'] = 1;
            $no['lbs_x'] = array('elt',0);
            $no['lbs_y'] = array('elt',0);
            $count = M('tstore_info')->where($no)->count();
            $this->assign('nonGPS',$count);
        }

        // 获取门店
        $getAllStores = M()->table('tstore_info')->where($where)->select();

        //var_dump($getAllStores);exit;

        $this->assign('allStores', $getAllStores);

        $type = I('type');
        $type == 'member' ? $where['a.pos_range'] = array(
                'gt',
                '1', ) : $where['a.pos_range'] = array(
                'gt',
                '0', );

        if (IS_POST) {
            $areaType = I('post.city_type');
            $query_arr = (array) $storesModel->areaFilter($nodeIn, $areaType);
            $this->ajaxReturn($query_arr, '查询成功', 0);
            exit();
        }
        // 获取分组
        if(I('sta') == 1 || I('type') == 9){
            //新增门店活动进来的 编辑导航进来的
            $storeGroup = $this->showStoreGroup($naviSta);
            $this->assign('type',9);
        }else{
            $storeGroup = $this->getStoreGroup($type);
        }

        $this->assign('storeGroup', $storeGroup);

        $this->display('./Home/Tpl/Home/Store_storePopup.html');
    }

    public function showStoreGroup($naviSta = '')
    {
        /* SELECT * FROM tgroup_store_relation r
 LEFT JOIN tstore_group g ON g.id=r.store_group_id
 LEFT JOIN tstore_info i ON i.store_id=r.store_id
 WHERE i.node_id='00040495' AND i.type = 1*/
        $storesGroupModel = $this->getStoresGroupModel();

        $whe['i.node_id'] = $this->node_id;
        $whe['i.type'] = 1;
        if($naviSta == 1){
            $whe['i.lbs_x'] = array('gt',0);
            $whe['i.lbs_y'] = array('gt',0);
        }
        //所有分组信息
        $allGroup = $storesGroupModel->getGroupInfo($whe);

        //所有已分组门店
        $allGroupStore = $storesGroupModel->getIsGroup($whe);

        //所有门店
        $allStore = M('tstore_info')->alias('i')->where($whe)->select();

        $new = array();
        $all = array();

        foreach ($allGroupStore as $key => $value) {
            $new[] = $value['store_id'];
        }

        foreach ($allStore as $key => $value) {
            $all[] = $value['store_id'];
        }
        $reu = array_diff($all, $new);


        //未分组门店
        $noGroupArr = M('tstore_info')
                ->where(array('node_id' => array('in',$this->node_id), 'store_id' => array('in', $reu)))
                ->field('store_id')->select();

        $noGroupStore = array();
        foreach ($noGroupArr as $key => $value) {
            $noGroupStore[] = $value['store_id'];
        }

        // 追加未分组项
        array_unshift($allGroup,
                array(
                        'id' => '-1',
                        'group_name' => '未被分组',
                        'num' => $noGroupStore ? count($noGroupStore) : 0,
                        'storeId' => implode(',', $noGroupStore), ));

        return array_filter($allGroup);
    }

    /**
     * 获取分组.
     *
     * @param $type string 会员的筛选条件
     *
     * @return mixed
     */
    public function getStoreGroup($type)
    {
        $nodeId = $this->node_id;
        $storesModel = $this->getStoresModel();
        $storesGroupModel = $this->getStoresGroupModel();

        if ($type == 'member') {
            $getGroupWhere = ' c.status = 0 and c.pos_range >1 and userTable.pos_status=0 ';
            $getUnGroupWhere = ' a.status = 0 and a.pos_range >1 and userTable.pos_status=0 ';
        } else {
            $getGroupWhere = ' c.status = 0 and c.pos_range >0 and userTable.pos_status=0 ';
            $getUnGroupWhere = ' a.status = 0 and a.pos_range >0 and userTable.pos_status=0 ';
        }
        // 获取所有分组
        $allGroup = $storesGroupModel->getPopGroupStoreId($nodeId,
                $getGroupWhere, 'tpos_info');

        // 未分组的门店
        $noGroup = $storesModel->getUnGroupedAllStore($nodeId, $getUnGroupWhere,
                'tpos_info');
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
                        'storeId' => implode(',', $noGroupArr), ));

        return array_filter($allGroup);
    }
    /**
     *申请终端的门店弹窗.
     */
    public function posStorePop()
    {
        $nodeIn = $this->nodeIn();
        $nodeIn = $this->getNodeIn($nodeIn);
        $storesModel = $this->getStoresModel();

        // 省市区请求
        if (IS_POST) {
            $areaType = I('post.city_type');
            $query_arr = (array) $storesModel->areaFilter($nodeIn, $areaType);
            $this->ajaxReturn($query_arr, '查询成功', 0);
            exit();
        }
        // 获取到已被开通的门店
        //$navigation = $storesModel->getOpenedStores($nodeIn, $m_id);
        //$storeInfo = $this->GroupShopModel->getStoreInfo('','',$this->nodeId, false);
        //dump($storeInfo);exit;

        // 拿分组
        $storeGroup = $this->storeGroup(false);
        $this->assign('storeGroup', $storeGroup);

        $activityId = I('get.id');

        //$activity = M()->table('tstore_activity_info')->find($activityId);//活动信息
        $OpenedStores = [];
        $OpenedStoresStr = '';
        if ($activityId) {
            $OpenedStores = M('tstore_activity_relation')->where(array('activity_id' => $activityId))->getField('store_id', true);
            $OpenedStoresStr = implode(',', $OpenedStores);
            $this->assign('OpenedStoresStr', $OpenedStoresStr);
        }

        $this->assign('OpenedStores', $OpenedStores);

        $getAllStores = $storesModel->getAllStore($nodeIn, array(), 'tpos_info', "GROUP_CONCAT(IFNULL(userTable.pos_type,'a')) as ispos");

        $this->assign('allStores', $getAllStores); // 输出门店

        $this->display();
    }
}
