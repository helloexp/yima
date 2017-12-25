<?php

/**
 * 门店管理 Author：ys 2015/09/16 12:53
 */
class YhbStoreAction extends YhbAction {

    public $_authAccessMap = '*';

    public $baidu_url = "http://api.map.baidu.com/geocoder/v2/?ak=WRzAu3DNewWB4oeOELaczjsM&output=json&address=";

    public $uploadPath;

    public $file_error;

    public $yhb_street_code = "yhb_street";

    public $yhb_village_code = "yhb_village";

    public function _initialize() {
        parent::_initialize();
        $this->uploadPath = './Home/Upload/Store/'; // 设置附件上传目录
        $townCode = $this->townCode();
        $this->assign('townCode_list', $townCode);
    }
    
    // 介绍宣传页
    public function show() {
        $this->display();
    }
    // 单独定位
    function plocation() {
        $this->assign('slng', $_REQUEST['lng']);
        $this->assign('slat', $_REQUEST['lat']);
        $this->assign('lng', $_REQUEST['endLng']);
        $this->assign('lat', $_REQUEST['endLat']);
        $this->assign('cityName', $_REQUEST['cityName']);
        $this->assign('des_city', $_REQUEST['des_city']);
        $this->display();
    }

    public function index() {
        // 终端开通状态
        $pos_status = array(
            '1' => '未展示', 
            '2' => '展示中');
        $data = $_REQUEST;
        $dao = M('tstore_info');
        // 按机构树数据隔离
        $where = "a.node_id in (" . $this->nodeIn() . ")";
        $node_id = I('node_id');
        $IsOpen = I('IsOpen');
        
        // 按机构号查询
        if ($node_id && $node_id != $this->nodeId) {
            $where .= " and a.node_id in (" . $this->nodeIn($node_id) . ")";
        }
        // 门店名查询
        if (I('store_name') != '') {
            $where .= " and a.store_name like '%" . I('store_name') . "%'";
        }
        // 商户名称
        if (I('merchant_short_name') != '') {
            $where .= " and d.merchant_short_name like '%" .
                 I('merchant_short_name') . "%'";
        }
        // 负责人
        if (I('principal_name') != '') {
            $where .= " and a.principal_name like '%" . I('principal_name') .
                 "%'";
        }
        // 终端正常和下线
        if (I('pos_count_status') == '1') {
            $where .= " and tc.line_status = 1";
        }
        if (I('pos_count_status') == '2') {
            $where .= " and tc.line_status = 2";
        }
        // 业务受理环境
        if (I('pos_range') != '' && in_array(I('pos_range'), 
            array(
                '0', 
                '1', 
                '2'), true)) {
            $where .= " and a.pos_range = '" . I('pos_range') . "'";
        }
        $where .= " and a.province_code = '09'";
        $where .= " and a.city_code = '021'";
        if (I('town_code') != '') {
            $where .= " and a.town_code = '" . I('town_code') . "'";
            $this->assign('town_code', I('town_code'));
        }
        // 街道streetcode
        if (I('street_code') != '') {
            $where .= " and tc.street_code = '" . I('street_code') . "'";
        }
        // 街道villagecode
        if (I('village_code') != '') {
            $where .= " and tc.village_code = '" . I('village_code') . "'";
        }
        // 美食分类
        if (I('parent_id') != '') {
            $where .= " and d.parent_id = '" . I('parent_id') . "'";
        }
        // 子类查询catalog_id
        if (I('catalog_id') != '') {
            $where .= " and d.catalog_id = '" . I('catalog_id') . "'";
        }
        if (I('jg_name_email') != '') {
            $where .= " and a.principal_email = '" . I('jg_name_email') . "'";
        }
        
        if (I('downtype') == '1') {
            $queryList = $dao->table('tstore_info a')
                ->join('tnode_info b on b.node_id=a.node_id')
                ->join('tpos_info c on a.store_id=c.store_id')
                ->field('DISTINCT a.id aid, a.*,b.node_name')
                ->where($where)
                ->order('a.id desc')
                ->select();
            $this->storeDown($queryList);
            exit();
        }
        if ($data['order_sort'] != '') {
            if ($data['order_sort'] == 1) {
                $order = "tc.order_sort asc";
            } else {
                $order = "tc.order_sort desc";
            }
        } else {
            $order = "a.id desc";
        }
        // 判断权限，商户各自查看自己的门店
        if ($this->check_is_admin($this->node_id) === false) {
            $where .= " and d.id = " . $this->merchant_id;
        }
        import('ORG.Util.Page'); // 导入分页类
        $dao->join('tpos_info c on a.store_id=c.store_id');
        $count = $dao->table('tstore_info a')
            ->join('tnode_info b on b.node_id=a.node_id')
            ->join('tfb_yhb_store tc on tc.store_id=a.store_id')
            ->join('tpos_info ph on ph.store_id=tc.store_id')
            ->join('tfb_yhb_node_info d on d.id=tc.merchant_id')
            ->field(
            array(
                'count(DISTINCT a.id)' => 'tp_count'))
            ->where($where)
            ->find(); // 查询满足要求的总记录数
        $count = $count['tp_count'];
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $pageShow = $Page->show(); // 分页显示输出
        $dao->join('tpos_info c on a.store_id=c.store_id');
        $queryList = $dao->table('tstore_info a')
            ->join('tnode_info b on b.node_id=a.node_id')
            ->join('tfb_yhb_store tc on tc.store_id=a.store_id')
            ->join('tpos_info ph on ph.store_id=a.store_id')
            ->join('tfb_yhb_node_info d on d.id=tc.merchant_id')
            ->field(
            'a.id aid,a.*,b.node_name,tc.order_sort,tc.line_status,ph.pos_id')
            ->group('aid')
            ->where($where)
            ->order($order)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        // 获取当前机构的所有下级机构
        $nodeList = M('tnode_info')->field('node_id,node_name,parent_id')
            ->where("node_id IN({$this->nodeIn()})")
            ->select();
        // 当前机构下的门店总数
        $wh = array(
            "_string" => "a.node_id in (" . $this->nodeIn() . ")");
        $storeCount = $dao->table('tstore_info a')
            ->where($wh)
            ->count();
        // 当前机构下开通终端的门店数
        $wh['c.pos_status'] = 0;
        $wh['a.pos_count'] = array(
            'gt', 
            '0');
        $storePosCount = $dao->field(
            array(
                'count(DISTINCT a.id)' => 'tp_count'))
            ->table('tstore_info a')
            ->join('tpos_info c on c.store_id=a.store_id')
            ->where($wh)
            ->find();
        $parentInfo = M('tfb_yhb_catalog')->field('id,catalog_name')
            ->where("parent_id=0 and is_delete=0")
            ->select();
        $catalogInfo = array();
        foreach ($parentInfo as $v) {
            $catalogInfo[$v['id']] = M('tfb_yhb_catalog')->field(
                'id,catalog_name')
                ->where("parent_id=" . $v['id'] . " and is_delete=0")
                ->select();
        }
        $this->assign("catalogInfo", $catalogInfo);
        $this->assign("parentInfo", $parentInfo);
        $shelfStatus = array(
            '1' => '未展示', 
            '2' => '展示中');
        $this->assign('shelfStatus', $shelfStatus);
        $this->assign('queryList', $queryList);
        $this->assign('pageShow', $pageShow);
        $this->assign('storeCount', $storeCount);
        $this->assign('storePosCount', $storePosCount['tp_count']);
        $this->assign('pos_status', $pos_status);
        $this->assign('IsOpen', $IsOpen);
        $this->assign('post', $data);
        $this->assign('node_id', $node_id ? $node_id : $this->nodeId);
        $this->assign('node_list', $nodeList);
        $this->display("Store/YhbStore_index");
    }

    public function storeList() {
        // 终端开通状态
        $pos_status = array(
            '1' => '正常', 
            '2' => '展示中');
        $data = $_REQUEST;
        $dao = M('tstore_info');
        // 按机构树数据隔离
        $where = "a.node_id in (" . $this->nodeIn() . ")";
        $node_id = I('node_id');
        $IsOpen = I('IsOpen');
        
        // 按机构号查询
        if ($node_id && $node_id != $this->nodeId) {
            $where .= " and a.node_id in (" . $this->nodeIn($node_id) . ")";
        }
        // 门店名查询
        if (I('store_name') != '') {
            $where .= " and a.store_name like '%" . I('store_name') . "%'";
        }
        // 商户名称
        if (I('merchant_short_name') != '') {
            $where .= " and d.merchant_short_name like '%" .
                 I('merchant_short_name') . "%'";
        }
        // 负责人
        if (I('principal_name') != '') {
            $where .= " and a.principal_name like '%" . I('principal_name') .
                 "%'";
        }
        // 终端正常和下线
        if (I('pos_count_status') == '1') {
            $where .= " and tc.line_status = 1";
        }
        if (I('pos_count_status') == '2') {
            $where .= " and tc.line_status = 2";
        }
        // 业务受理环境
        if (I('pos_range') != '' && in_array(I('pos_range'), 
            array(
                '0', 
                '1', 
                '2'), true)) {
            $where .= " and a.pos_range = '" . I('pos_range') . "'";
        }
        $where .= " and a.province_code = '09'";
        $where .= " and a.city_code = '021'";
        if (I('town_code') != '') {
            $where .= " and a.town_code = '" . I('town_code') . "'";
            $this->assign('town_code', I('town_code'));
        }
        // 街道streetcode
        if (I('street_code') != '') {
            $where .= " and tc.street_code = '" . I('street_code') . "'";
        }
        // 街道villagecode
        if (I('village_code') != '') {
            $where .= " and tc.village_code = '" . I('village_code') . "'";
        }
        // 美食分类
        if (I('parent_id') != '') {
            $where .= " and d.parent_id = '" . I('parent_id') . "'";
        }
        // 子类查询catalog_id
        if (I('catalog_id') != '') {
            $where .= " and d.catalog_id = '" . I('catalog_id') . "'";
        }
        if (I('jg_name_email') != '') {
            $where .= " and a.principal_email = '" . I('jg_name_email') . "'";
        }
        
        if (I('downtype') == '1') {
            $queryList = $dao->table('tstore_info a')
                ->join('tnode_info b on b.node_id=a.node_id')
                ->join('tpos_info c on a.store_id=c.store_id')
                ->field('DISTINCT a.id aid, a.*,b.node_name')
                ->where($where)
                ->order('a.id desc')
                ->select();
            $this->storeDown($queryList);
            exit();
        }
        if ($data['order_sort'] != '') {
            if ($data['order_sort'] == 1) {
                $order = "tc.order_sort asc";
            } else {
                $order = "tc.order_sort desc";
            }
        } else {
            $order = "a.id desc";
        }
        // 判断权限，商户各自查看自己的门店
        if ($this->check_is_admin($this->node_id) === false) {
            $where .= " and d.id = " . $this->merchant_id;
        }
        $where .= " and tc.line_status=2";
        import('ORG.Util.Page'); // 导入分页类
        $dao->join('tpos_info c on a.store_id=c.store_id');
        $count = $dao->table('tstore_info a')
            ->join('tnode_info b on b.node_id=a.node_id')
            ->join('tfb_yhb_store tc on tc.store_id=a.store_id')
            ->join('tpos_info ph on ph.store_id=tc.store_id')
            ->join('tfb_yhb_node_info d on d.id=tc.merchant_id')
            ->field(
            array(
                'count(DISTINCT a.id)' => 'tp_count'))
            ->where($where)
            ->find(); // 查询满足要求的总记录数
        $count = $count['tp_count'];
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $pageShow = $Page->show(); // 分页显示输出
        $dao->join('tpos_info c on a.store_id=c.store_id');
        $queryList = $dao->table('tstore_info a')
            ->join('tnode_info b on b.node_id=a.node_id')
            ->join('tfb_yhb_store tc on tc.store_id=a.store_id')
            ->join('tpos_info ph on ph.store_id=a.store_id')
            ->join('tfb_yhb_node_info d on d.id=tc.merchant_id')
            ->field(
            'a.id aid,a.*,b.node_name,tc.order_sort,tc.line_status,ph.pos_id')
            ->group('aid')
            ->where($where)
            ->order($order)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        // 获取当前机构的所有下级机构
        $nodeList = M('tnode_info')->field('node_id,node_name,parent_id')
            ->where("node_id IN({$this->nodeIn()})")
            ->select();
        // 当前机构下的门店总数
        $wh = array(
            "_string" => "a.node_id in (" . $this->nodeIn() . ")");
        $storeCount = $dao->table('tstore_info a')
            ->where($wh)
            ->count();
        // 当前机构下开通终端的门店数
        $wh['c.pos_status'] = 0;
        $wh['a.pos_count'] = array(
            'gt', 
            '0');
        $storePosCount = $dao->field(
            array(
                'count(DISTINCT a.id)' => 'tp_count'))
            ->table('tstore_info a')
            ->join('tpos_info c on c.store_id=a.store_id')
            ->where($wh)
            ->find();
        $parentInfo = M('tfb_yhb_catalog')->field('id,catalog_name')
            ->where("parent_id=0 and is_delete=0")
            ->select();
        $catalogInfo = array();
        foreach ($parentInfo as $v) {
            $catalogInfo[$v['id']] = M('tfb_yhb_catalog')->field(
                'id,catalog_name')
                ->where("parent_id=" . $v['id'] . " and is_delete=0")
                ->select();
        }
        $this->assign("catalogInfo", $catalogInfo);
        $this->assign("parentInfo", $parentInfo);
        $shelfStatus = array(
            '1' => '正常', 
            '2' => '展示中');
        $this->assign('shelfStatus', $shelfStatus);
        $this->assign('queryList', $queryList);
        $this->assign('pageShow', $pageShow);
        $this->assign('storeCount', $storeCount);
        $this->assign('storePosCount', $storePosCount['tp_count']);
        $this->assign('pos_status', $pos_status);
        $this->assign('IsOpen', $IsOpen);
        $this->assign('post', $data);
        $this->assign('node_id', $node_id ? $node_id : $this->nodeId);
        $this->assign('node_list', $nodeList);
        $this->assign('is_df', 
            (C('df.node_id') == $this->node_id) ? true : false);
        $this->display("Store/YhbStore_list");
    }
    // 门店添加
    public function add() {
        // 必须传值机构号
        $node_id = I('node_id', $this->nodeId);
        if ($this->check_is_admin($this->node_id) === false) {
            $this->error("对不起，您没有权限操作");
        }
        $nodeInfo = M("tfb_yhb_node_info")->select();
        if (! $nodeInfo) {
            $this->error("该机构下无商户，请创建商户在创建门店。");
        }
        // 判断是否允许添加该机构
        if ($this->isPost()) {
            // 判断负责人手机
            if (I('merchant_code') == "") {
                $this->error("商户号不能为空！");
            }
            if (I("town_code") == "") {
                $this->error("区号不能为空不能为空！");
            }
            if (I("street_code") == "") {
                $this->error("街道不能为空不能为空！");
            }
            if (I("village_code") == "") {
                $this->error("小区不能为空不能为空！");
            }
            if (I('principal_phone') != '') {
                if (is_numeric(I('principal_phone'))) {
                    if (strlen(I('principal_phone')) != 11)
                        $this->error("负责人手机必须是11位的纯数字");
                } else
                    $this->error("负责人手机必须是11位的纯数字");
            }
            // 判断负责人电话
            if (I('principal_tel') != '') {
                if (strlen(I('principal_tel')) > 10) {
                    if (! is_numeric(str_replace('-', '', I('principal_tel'))))
                        $this->error("负责人电话不是纯数字或者-,有非法字符");
                } else
                    $this->error("负责人电话不足11位");
            }
            // 判断门店电话
            if (I('store_phone') != '') {
                if (strlen(I('store_phone')) > 9) {
                    if (! is_numeric(str_replace('-', '', I('store_phone'))))
                        $this->error("门店电话不是纯数字或者-,有非法字符");
                } else
                    $this->error("门店电话不足10位");
            }
            if (! preg_match('/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/', 
                I('principal_email')))
                $this->error("邮箱格式不对");
                // 接收表单传值
                // 门店简称，查询该商户上是否有已经创建过的门店
            $store_short_name = I('store_short_name');
            $yhb_map = array(
                'node_id' => $this->nodeId, 
                'store_name' => $store_short_name);
            $yhb_store = M("tstore_info")->where($yhb_map)->find();
            if ($yhb_store) {
                $this->error("门店名称重复，请重新填写！");
            }
            $req_arr = array();
            $req_arr['SystemID'] = C('ISS_SYSTEM_ID');
            $req_arr['TransactionID'] = time() . mt_rand('1000', '9999');
            $req_arr['ISSPID'] = $node_id;
            $req_arr['UserId'] = $this->userId;
            $req_arr['Url'] = '<![CDATA[旺财会员账户中心]]>';
            $req_arr['CustomNo'] = I('custom_no');
            $req_arr['StoreName'] = I('store_short_name');
            $req_arr['StoreShortName'] = I('store_short_name');
            $req_arr['ContactName'] = I('principal_name');
            $req_arr['ContactTel'] = I('principal_tel');
            $req_arr['ContactEmail'] = I('principal_email');
            $is_log_img = I('is_log_img', 0, 'intval');
            $req_arr['RegionInfo'] = array(
                'Province' => I('province_code'), 
                'City' => I('city_code'), 
                'Town' => I('town_code'), 
                'Address' => I('address'));
            $cityInfo = M('tcity_code')->where(
                array(
                    'city_level' => 3, 
                    'province_code' => I('province_code'), 
                    'city_code' => I('city_code'), 
                    'town_code' => I('town_code')))->find();
            $qian = array(
                '/\s/', 
                '/ /', 
                '/\t/', 
                '/\n/', 
                '/\r/');
            $hou = array(
                "", 
                "", 
                "", 
                "", 
                "");
            $str = preg_replace($qian, $hou, 
                $cityInfo['province'] . $cityInfo['city'] . $cityInfo['town'] .
                     I('address'));
            $xyUrl = $this->addUrl($str);
            // DF非标逻辑
            $req_arr['store_pic'] = I('store_pic');
            $req_arr['store_introduce'] = I('store_introduce');
            $req_result = D('RemoteRequest', 'Service')->requestIssServ(
                array(
                    'CreateStoreReq' => $req_arr));
            $respStatus = isset($req_result['CreateStoreRes']) ? $req_result['CreateStoreRes']['Status'] : $req_result['Status'];
            if ($respStatus['StatusCode'] != '0000') {
                $msg = $respStatus['StatusText'] ? $respStatus['StatusText'] : '创建门店失败';
                $this->error($msg);
            }
            $respData = $req_result['CreateStoreRes'];
            $store_id = $respData['StoreId'];
            if (! $store_id) {
                $this->error('创建支撑门店失败');
            }
            // 查询门店号是否存在
            if (M('tstore_info')->where(
                array(
                    'store_id' => $store_id))->count()) {
                $this->error('门店号[' . $store_id . ']已经存在。');
            }
            
            M()->startTrans();
            $storeimg = I('store_pic');
            // 开始记录到门店表
            $data = array(
                'store_id' => $store_id, 
                'node_id' => $node_id, 
                'store_name' => I('store_short_name'), 
                'store_short_name' => I('store_short_name'), 
                'store_desc' => I('store_name'), 
                'province_code' => I('province_code'), 
                'city_code' => I('city_code'), 
                'town_code' => I('town_code'), 
                'address' => I('address'), 
                'post_code' => I('post_code'), 
                'principal_name' => I('principal_name'), 
                'principal_position' => I('principal_position'), 
                'principal_tel' => I('principal_tel'), 
                'principal_phone' => I('principal_phone'), 
                'principal_email' => I('principal_email'), 
                'custom_no' => I('custom_no'), 
                'memo' => I('memo'), 
                'status' => 0, 
                'add_time' => date('YmdHis'), 
                'store_phone' => I('store_phone'), 
                'store_email' => I('store_email'), 
                'busi_time' => I('busi_time'), 
                'store_pic' => $storeimg, 
                'business_code' => I('business_code'), 
                'store_introduce' => I('store_introduce'));
            // 翼惠宝非标逻辑
            if (I('merchant_code') != "") {
                $data['yhb_flage'] = I('merchant_code');
            }
            if (! empty($xyUrl['x']) && ! empty($xyUrl['y'])) {
                $data['lbs_x'] = $xyUrl['x'];
                $data['lbs_y'] = $xyUrl['y'];
            }
            // 情况是:支撑同步先到了，旺财门店入库（主键重复）异常，故先delete
            M('tstore_info')->where("store_id={$store_id}")->delete();
            $result = M('tstore_info')->add($data);
            if (! $result) {
                Log::write(print_r($data, true) . M()->getDbError(), 'DB ERROR');
                $this->error('创建门店失败');
            }
            // 通过录入成功的id找到store_id
            $yhb_store_id = M("tstore_info")->where(
                array(
                    'id' => $result))->getField('store_id');
            // 向翼惠宝tfb_yhb_store添加关联数据
            $merchant_id = I('merchant_code');
            $town_code = I("town_code");
            $street_code = I("street_code");
            $village_code = I("village_code");
            $store_data = array(
                'store_id' => $yhb_store_id, 
                'merchant_id' => $merchant_id, 
                'town_code' => $town_code, 
                "street_code" => $street_code, 
                "village_code" => $village_code, 
                'node_id' => $this->node_id);
            $res_yhb = M("tfb_yhb_store")->add($store_data);
            if ($res_yhb === false) {
                M()->rollback();
                log_write("存入关联tfb_yhb_store表失败");
                $this->error("存入关联tfb_yhb_store表失败");
            }
            M()->commit();
            // 创建门店成功后，直接创建epos
            $this->wcAddpos($yhb_store_id);
            node_log("【门店管理】门店添加，门店号：{$store_id}"); // 记录日志
            $this->success("门店添加成功", 
                array(
                    '返回列表页' => U('index')));
            exit();
        }
        $townCode = $this->townCode();
        $this->assign('townCode_list', $townCode);
        $this->assign('nodeInfo', $nodeInfo);
        $this->display("Store/YhbStore_add");
    }
    
    // 门店编辑
    public function edit() {
        if ($this->check_is_admin($this->node_id) === false) {
            $this->error("对不起，您没有权限操作");
        }
        $id = I('id');
        $info = M()->table("tstore_info m")->join(
            'tfb_yhb_store b on b.store_id=m.store_id')
            ->where("m.node_id in (" . $this->nodeIn() . ") and m.id='$id'")
            ->find();
        if ($info['store_pic']) {
            $info['image_url'] = $this->uploadPath . $info['store_pic'];
        }
        // 必须传值机构号
        $node_id = $info['node_id'];
        $nodeInfo = M("tfb_yhb_node_info")->select();
        if (! $nodeInfo) {
            $this->error("您没有操作该商户的权限。");
        }
        if ($this->isPost()) {
            // 移动图片image_name
            $storeimg = $info['store_pic'];
            $is_log_img = I('is_log_img', 0, 'intval');
            // 判断负责人手机
            if (I('principal_phone') != '') {
                if (is_numeric(I('principal_phone'))) {
                    if (strlen(I('principal_phone')) != 11)
                        $this->error("负责人手机必须是11位的纯数字");
                } else
                    $this->error("负责人手机必须是11位的纯数字");
            }
            // 判断负责人电话
            if (I('principal_tel') != '') {
                if (strlen(I('principal_tel')) > 10) {
                    if (! is_numeric(str_replace('-', '', I('principal_tel'))))
                        $this->error("负责人电话不是纯数字或者-,有非法字符");
                } else
                    $this->error("负责人电话不足11位");
            }
            
            // 判断门店电话
            if (I('store_phone') != '') {
                if (strlen(I('store_phone')) > 10) {
                    if (! is_numeric(str_replace('-', '', I('store_phone'))))
                        $this->error("门店电话不是纯数字或者-,有非法字符");
                } else
                    $this->error("门店电话不足10位");
            }
            if (! preg_match('/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/', 
                I('principal_email')))
                $this->error("邮箱格式不对");
            if (I("town_code") == "") {
                $this->error("区号不能为空不能为空！");
            }
            if (I("street_code") == "") {
                $this->error("街道不能为空不能为空！");
            }
            if (I("village_code") == "") {
                $this->error("小区不能为空不能为空！");
            }
            M()->startTrans();
            // 接收数据 发送报文至支撑
            $req_arr = array();
            $req_arr['SystemID'] = C('ISS_SYSTEM_ID');
            $req_arr['TransactionID'] = time() . mt_rand('1000', '9999');
            $req_arr['ISSPID'] = $node_id;
            $req_arr['UserId'] = $this->userId;
            $req_arr['StoreID'] = $info['store_id'];
            
            if (I('store_short_name') != $info['store_short_name']) {
                $req_arr['StoreName'] = I('store_short_name');
                $req_arr['StoreShortName'] = I('store_short_name');
            }
            if ((I('province_code') . I('city_code') . I('town_code')) !=
                 $info['area_code']) {
                $req_arr['StoreCityID'] = I('province_code') . I('city_code') .
                 I('town_code');
        }
        // //重新定位数据
        $cityInfo = M('tcity_code')->where(
            array(
                'city_level' => 3, 
                'province_code' => I('province_code'), 
                'city_code' => I('city_code'), 
                'town_code' => I('town_code')))->find();
        $qian = array(
            '/\s/', 
            '/ /', 
            '/\t/', 
            '/\n/', 
            '/\r/');
        $hou = array(
            "", 
            "", 
            "", 
            "", 
            "");
        $str = preg_replace($qian, $hou, 
            $cityInfo['province'] . $cityInfo['city'] . $cityInfo['town'] .
                 I('address'));
        $xyUrl = $this->addUrl($str);
        if (! empty($xyUrl['x']) && ! empty($xyUrl['y'])) {
            $req_arr['StoreLbs'] = $xyUrl['x'] . ',' . $xyUrl['y'];
        } else
            $req_arr['StoreLbs'] = '0,0';
        if (I('address') != $info['address']) {
            $req_arr['StoreAddress'] = I('address');
        }
        if (I('principal_name') != $info['principal_name']) {
            $req_arr['ContactName'] = I('principal_name');
        }
        if (I('principal_email') != $info['principal_email']) {
            $req_arr['PrincipalEmail'] = I('principal_email');
        }
        if (I('principal_tel') != $info['principal_tel']) {
            $req_arr['principalTel'] = I('principal_tel');
        }
        if (I('principal_phone') != $info['principal_phone']) {
            $req_arr['principalPhone'] = I('principal_phone');
        }
        if (I('principal_position') != $info['principal_position']) {
            $req_arr['principalPosition'] = I('principal_position');
        }
        $req_result = D('RemoteRequest', 'Service')->requestIssServ(
            array(
                'MotifyPosStoreReq' => $req_arr));
        $respStatus = isset($req_result['MotifyPosStoreRes']) ? $req_result['MotifyPosStoreRes']['Status'] : $req_result['Status'];
        if ($respStatus['StatusCode'] != '0000') {
            M()->rollback();
            $this->error('修改门店失败');
        }
        // 开始记录到门店表
        $data = array(
            'store_name' => I('store_short_name'), 
            'store_short_name' => I('store_short_name'), 
            'store_desc' => I('store_name'), 
            'province_code' => I('province_code'), 
            'city_code' => I('city_code'), 
            'town_code' => I('town_code'), 
            'address' => I('address'), 
            'post_code' => I('post_code'), 
            'principal_name' => I('principal_name'), 
            'principal_position' => I('principal_position'), 
            'principal_tel' => I('principal_tel'), 
            'principal_phone' => I('principal_phone'), 
            'principal_email' => I('principal_email'), 
            'custom_no' => I('custom_no'), 
            'memo' => I('memo'), 
            'store_phone' => I('store_phone'), 
            'store_email' => I('store_email'), 
            'busi_time' => I('busi_time'), 
            // 'busi_start_time'=>I('busi_start_time'),
            // 'busi_end_time'=>I('busi_end_time'),
            'store_pic' => I('store_pic'), 
            // 'business_code'=>I('business_code'),
            'store_introduce' => I('store_introduce'), 
            'yhb_flage' => I('merchant_code'));
        if (! empty($xyUrl['x']) && ! empty($xyUrl['y'])) {
            $data['lbs_x'] = $xyUrl['x']; // 纬度
            $data['lbs_y'] = $xyUrl['y']; // 经度
        }
        $result = M('tstore_info')->where("id='" . $info['id'] . "'")->save(
            $data);
        if ($result === false) {
            M()->rollback();
            Log::write(print_r($data, true) . M()->getDbError(), 'DB ERROR');
            $this->error('修改门店失败');
        }
        // 修改门店与tfb_yhb_store对应表关系
        if (I("town_code") == "") {
            $this->error("区号不能为空不能为空！");
        }
        if (I("street_code") == "") {
            $this->error("街道不能为空不能为空！");
        }
        if (I("village_code") == "") {
            $this->error("小区不能为空不能为空！");
        }
        $town_code = I('town_code');
        $merchant_id = I('merchant_code');
        $street_code = I('street_code');
        $village_code = I('village_code');
        $yhb_data = array(
            'merchant_id' => $merchant_id, 
            'town_code' => $town_code, 
            'street_code' => $street_code, 
            'village_code' => $village_code);
        $yhb_res = M("tfb_yhb_store")->where(
            array(
                'store_id' => $info['store_id']))->save($yhb_data);
        if ($yhb_res === false) {
            M()->rollback();
            $this->error('修改门店失败！');
        }
        M()->commit();
        node_log("【门店管理】门店修改，ID:{$info['id']}"); // 记录日志
        $this->success("修改门店成功", 
            array(
                'href' => 'javascript:parent.location.reload();'));
        exit();
    }
    if ($info['province_code']) {
        $info['fulladdress'] = D('CityCode')->getAreaText(
            $info['province_code'] . $info['city_code'] . $info['town_code']) .
             $info['address'];
    }
    // 通过store_id查询所在区域下的所有街道，和默认街道下对应的小区
    $merchant_arr = array();
    foreach ($nodeInfo as $key => $val) {
        $merchant_arr[$val['id']] = $val['merchant_name'];
    }
    $this->assign('merchant_arr', $merchant_arr);
    $this->assign('nodeInfo', $nodeInfo);
    $this->assign('town_code', $info['town_code']);
    $this->assign('street_code', $info['street_code']);
    $this->assign('village_code', $info['village_code']);
    $this->assign('info', $info);
    $this->display("Store/YhbStore_edit");
}
// 门店详情
public function view() {
    $id = I('id');
    $info = M()->table("tstore_info m")->field('m.*,b.QR_CODE,c.street,c.village')
        ->join('tfb_yhb_store b on b.store_id=m.store_id')
        ->join('tfb_yhb_city_code c on c.village_code=b.village_code')
        ->where("m.node_id in (" . $this->nodeIn() . ") and m.id='$id'")
        ->find();
    $qr_data = array(
        'store_id' => $info['store_id'],
        'store_name' => $info['store_name']
        );
    if ($info['QR_CODE'] == null || !file_exists($info['QR_CODE'])) {
        $re = $this->_qrcode($qr_data);
        $info['QR_CODE'] = $re;
    }
    $posList = M('tpos_info')->where(
        array(
            'node_id' => array(
                'exp', 
                "in ({$this->nodeIn()})"), 
            'store_id' => $info['store_id']))->select();
    if ($info) {
        $info['img_url'] = APP_PATH . '/Upload/Store/' . $info['store_pic'];
    }
    $storeChannel = M('tchannel')->field('id,name,add_time')
        ->where(array(
        "store_id" => $info['store_id']))
        ->select();
    $this->assign('posList', $posList);
    $this->assign('info', $info);
    $this->assign('storeChannel', $storeChannel);
    $this->display('Store/YhbStore_view');
}

//下载图片
public function downImage(){
    $store_id = I('get.store_id');
    $path_file = M('tfb_yhb_store')->where("store_id = '{$store_id}'")->getField('QR_CODE');
    $file_array = explode('/', $path_file);
    $file_name = end($file_array);
    $file = fopen($path_file,"r");
    //返回的文件类型
    Header("Content-type: application/octet-stream");
    //按照字节大小返回
    Header("Accept-Ranges: bytes");
    //返回文件的大小
    Header("Accept-Length: ".filesize($path_file));
    //这里对客户端的弹出对话框，对应的文件名
    Header("Content-Disposition: attachment; filename=".$file_name);
    //修改之前，一次性将数据传输给客户端
    echo fread($file, filesize($path_file));
    //修改之后，一次只传输1024个字节的数据给客户端
    //向客户端回送数据
    $buffer=1024;//
    //判断文件是否读完
    while (!feof($file)) {
        //将文件读入内存
        $file_data=fread($file,$buffer);
        //每次向客户端回送1024个字节的数据
        echo $file_data;
    }
    fclose($file);
    exit;
}

//生产二维码
public function _qrCode($arr) {
        import('@.Vendor.phpqrcode.phpqrcode') or die('include file fail.');
        $path = APP_PATH . 'Upload/yhbstore/qrcode/';
        $id = $arr['store_id'];
        $url = CURRENT_HOST . "/index.php?g=Yhb&m=YhbWap&a=store&store_id=".$id;
        if (! file_exists($path)) {
            mkdir($path, 0777, ture);
        }
        $ecc = 'H';
        $size = 10;
        $filename = $path . $arr['store_name'] . '.png';
        $keyarray = array(
            'qr_code' => $filename);
        QRcode::png($url, $filename, $ecc, $size, 0, false);
        $re = M('tfb_yhb_store')->where("store_id = '{$id}'")->save($keyarray);
        if ($re === false) {
            return false;
        }else{
            return $filename;
        }
    }

// 移动图片 Upload/img_tmp->Upload/Weixin/node_id
private function move_image($image_name, $new_name) {
    $image_name = str_replace('..', '', $image_name);
    $new_name = str_replace('..', '', $new_name);
    if (! $image_name) {
        return "需上传图片";
    }
    if (! is_dir(APP_PATH . '/Upload/Store/')) {
        mkdir(APP_PATH . '/Upload/Store/', 0777);
    }
    $old_image_url = APP_PATH . '/Upload/img_tmp/' . $this->node_id . '/' .
         basename($image_name);
    $new_image_url = APP_PATH . '/Upload/Store/' . basename($new_name);
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
// 下载门店信息
public function storeDown($queryList) {
    $fileName = 'storeInfo.csv';
    $fileName = iconv('utf-8', 'gb2312', $fileName);
    header("Content-type: text/plain");
    header("Accept-Ranges: bytes");
    header("Content-Disposition: attachment; filename=" . $fileName);
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Pragma: no-cache");
    header("Expires: 0");
    $title_ = "门店简称,门店负责人,负责人电话,负责人Email\r\n";
    $title_ = iconv('utf-8', 'gbk', $title_);
    
    if ($queryList) {
        foreach ($queryList as $key => $value) {
            echo iconv('utf-8', 'gbk', $value['store_name']) . ',';
            echo iconv('utf-8', 'gbk', $value['principal_name']) . ',';
            echo iconv('utf-8', 'gbk', $value['principal_tel']) . ',';
            echo iconv('utf-8', 'gbk', $value['principal_email']) . "\r\n";
        }
    }
}

public function location() {
    $id = I('id');
    $lat = I('lat');
    $lng = I('lng');
    if ($id == '')
        die("error[0001]");
    if (empty($lat) === false && empty($lng) === false) {
        $data = array(
            'lbs_y' => $lng, 
            'lbs_x' => $lat);
        $fruit = M('tstore_info')->where(
            "node_id in (" . $this->nodeIn() . ") and id='$id'")
            ->save($data);
        if ($fruit)
            die(
                json_encode(
                    array(
                        'codeId' => '0000', 
                        'codeText' => '重新绑定定位成功')));
        $result = M('tstore_info')->where($data)
            ->where("node_id in (" . $this->nodeIn() . ") and id='$id'")
            ->count();
        if ($result > 0)
            die(
                json_encode(
                    array(
                        'codeId' => '0000', 
                        'codeText' => '重新绑定定位成功')));
        die(
            json_encode(
                array(
                    'codeId' => '0000', 
                    'codeText' => '定位失败，请刷新重试')));
    }
    $info = M('tstore_info')->where(
        "node_id in (" . $this->nodeIn() . ") and id='$id'")
        ->find();
    $this->assign('info', $info);
    $this->display("Store/YhbStore_location");
}

public function channellist() {
    $channelType = C('CHANNEL_TYPE_ARR');
    
    $where = array(
        '_string' => "a.node_id in (" . $this->nodeIn() . ")", 
        'b.type' => '2');
    // 条件筛选
    $StoreName = I('get.jg_name');
    $province = I('get.province');
    $city = I('get.city');
    $town = I('get.town');
    $getchannelType = I('get.channelType');
    
    if ($StoreName != '') {
        $where['a.store_name'] = array(
            'like', 
            "%$StoreName%");
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
        if (! is_dir(APP_PATH . "Upload/downcodeImg")) {
            mkdir(APP_PATH . "Upload/downcodeImg", 7777);
        }
        
        $list = M()->table('tstore_info a')
            ->field(
            'b.id,b.name,b.type,b.sns_type,a.store_name,a.id as storeId')
            ->join('RIGHT JOIN tchannel b on b.store_id=a.store_id')
            ->where($where)
            ->order('b.id desc')
            ->select();
        // dump($list);die;
        $downname = APP_PATH . "Upload/downcodeImg/ChannelQRcode" . date('Ymd') .
             ".zip";
        $zip = new ZipArchive();
        if ($zip->open($downname, ZIPARCHIVE::CREATE) !== TRUE) {
            exit('无法打开文件，或者文件创建失败');
        }
        foreach ($list as $k => $v) {
            $this->getCode($v['id'], $v['name']);
        }
        
        foreach ($list as $v) {
            $filename = iconv("utf-8", "gbk", $v['name']);
            $zip->addFile(APP_PATH . "Upload/downcodeImg/" . $filename . '.png');
        }
        $zip->close();
        
        header("Cache-Control: max-age=0");
        header("Content-Description: File Transfer");
        header(
            'Content-disposition: attachment; filename=' . basename($downname));
        header("Content-Type: application/zip");
        header("Content-Transfer-Encoding: binary");
        header('Content-Length: ' . filesize($downname));
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
        ->limit($Page->firstRow . ',' . $Page->listRows)
        ->order('b.id desc')
        ->select();
    
    $this->assign('list', $list);
    $this->assign('channelType', $channelType);
    $this->assign('page', $pageShow);
    $this->display();
}

// 批量添加渠道
public function channeladd() {
    // 展示页
    $channelType = C('CHANNEL_TYPE_ARR');
    
    $where = array(
        '_string' => "node_id in (" . $this->nodeIn() . ")");
    $list = M('tstore_info')->field(
        'store_short_name,store_name,id,store_id,province_code,city_code,town_code,address')
        ->where($where)
        ->order('id desc')
        ->select();
    // 添加
    if ($this->isPost()) {
        $arrChannelKey = array_flip($channelType['2']);
        $storeChannel = I('POST.jg_name');
        $channelType = I('POST.channelType');
        $storeNumber = I('POST.storeNumber');
        $storeCheckStatus = I('POST.storeCheckStatus');
        // 参数校验
        if (empty($storeChannel) || mb_strlen($storeChannel, 'utf-8') > 5)
            die(
                json_encode(
                    array(
                        'status' => '00001', 
                        'info' => '参数错误[nume error]')));
        if (in_array($channelType, $arrChannelKey) === false)
            die(
                json_encode(
                    array(
                        'status' => '00002', 
                        'info' => '渠道类型[type error]')));
        if (in_array($storeNumber, 
            array(
                '1', 
                '2')) === false)
            die(
                json_encode(
                    array(
                        'status' => '00003', 
                        'info' => '未知错误[unknown error]')));
        
        $model = M('tchannel');
        $arr = array(
            'type' => '2', 
            'sns_type' => $channelType, 
            'status' => '1', 
            'node_id' => $this->node_id, 
            'add_time' => date('YmdHis'));
        if ($storeNumber == '1') {
            if ($list === false)
                die(
                    json_encode(
                        array(
                            'status' => '00004', 
                            'info' => '未创建门店[error]')));
            
            M()->startTrans();
            foreach ($list as $v) {
                $arr['name'] = $storeChannel . '-' . $v['store_short_name'];
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
                                'status' => '00005', 
                                'info' => '创建渠道[error]')));
                }
            }
            M()->commit();
        }
        
        if ($storeNumber == '2') {
            if ($list === false)
                die(
                    json_encode(
                        array(
                            'status' => '00004', 
                            'info' => '未创建门店[error]')));
            if (empty($storeCheckStatus))
                die(
                    json_encode(
                        array(
                            'status' => '00004', 
                            'info' => '未选择门店[error]')));
            
            M()->startTrans();
            foreach ($list as $v) {
                if (in_array($v['store_id'], $storeCheckStatus)) {
                    $arr['name'] = $storeChannel . '-' . $v['store_short_name'];
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
                                    'status' => '00005', 
                                    'info' => '创建渠道[error]')));
                    }
                }
            }
            M()->commit();
        }
        die(
            json_encode(
                array(
                    'status' => '00000', 
                    'info' => '创建渠道成功')));
    }
    
    $this->assign('list', $list);
    $this->assign('channelType', $channelType['2']);
    $this->display();
}

private function getCode($id, $filename) {
    $url = U('Label/Channel/index', array(
        'id' => $id), '', '', true);
    $filename = iconv("utf-8", "gbk", $filename);
    $this->MakeCodeImg($url, true, '', '', $filename, '', '');
}

private function MakeCodeImg($url = '', $is_down = false, $type = '', $log_dir = '', 
    $filename = '', $color = '', $ap_arr = '') {
    if (empty($url))
        exit();
    
    import('@.Vendor.phpqrcode.phpqrcode') or die('include file fail.');
    $size_arr = C('SIZE_TYPE_ARR');
    empty($type) ? $size = 4 : $size = $size_arr[$type];
    empty($filename) ? $filename = time() . ".png" : $filename .= '.png';
    
    $code = QRcode::png($url, "Home/Upload/downcodeImg/" . $filename, '0', 
        $size, $margin = true, $saveandprint = false, $log_dir, $color, $ap_arr);
}

public function UpdateGps() {
    $checkType = I('get.checktype');
    $checkNumber = I('get.checkNumber');
    
    if (in_array($checkType, array(
        '0', 
        '1')) === false)
        die(
            json_encode(
                array(
                    'codeId' => '0001', 
                    'codeText' => '参数错误[error]')));
    
    if ($this->editTitle(I('get.wep_title')) === false)
        die(
            json_encode(
                array(
                    'codeId' => '0006', 
                    'codeText' => '页面标题保存失败')));
    
    if ($checkType == '0' && empty($checkNumber) == true)
        die(
            json_encode(
                array(
                    'codeId' => '0000', 
                    'codeText' => '页面标题保存成功')));
        
        // 按机构树数据隔离
    $where = "a.node_id in (" . $this->nodeIn() . ")";
    $queryList = M()->table('tstore_info a')
        ->join('tnode_info b on b.node_id=a.node_id')
        ->field('a.*,b.node_name')
        ->where($where)
        ->order('a.id desc')
        ->select();
    
    if ($queryList === false)
        die(
            json_encode(
                array(
                    'codeId' => '00003', 
                    'codeText' => '未创建门店[error]')));
    
    $data = array(
        'gps_flag' => '1');
    $nodata_arr = array();
    // 可先
    if ($checkType == '0') {
        if (empty($checkNumber))
            die(
                json_encode(
                    array(
                        'codeId' => '0002', 
                        'codeText' => '没有选择门店[error]')));
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
        if (! empty($data_arr))
            $flag = M('tstore_info')->where(
                array(
                    'id' => array(
                        'in', 
                        $data_arr)))->save($data);
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
                '_string' => "lbs_x !=0 and lbs_y !=0 "))->save($data);
    }
    if (! empty($nodata_arr)) {
        $fruit = M('tstore_info')->where(
            array(
                'id' => array(
                    'in', 
                    $nodata_arr)))->save(array(
            'gps_flag' => 0));
    }
    
    if ($flag === false)
        die(
            json_encode(
                array(
                    'codeId' => '0005', 
                    'codeText' => '门店更新失败[error]')));
    die(
        json_encode(
            array(
                'codeId' => '0000', 
                'codeText' => '门店更新成功')));
}

// 终端接入
public function terminal() {
    $where = array(
        'jk_type' => 0, 
        'node_id' => $this->node_id, 
        'ym_status' => 0);
    $terInfo = M('twc_interface')->where($where)
        ->order('id desc')
        ->find();
    if ($this->isPost()) {
        $data = I('POST.');
        $arr = array(
            'servicer', 
            'servicernumber', 
            'skill', 
            'skillnumber', 
            'allege');
        foreach ($arr as $v) {
            if (array_key_exists($v, $data) === false) {
                die(
                    json_encode(
                        array(
                            'codeId' => '0001', 
                            'codeText' => '参数错误[error]')));
            }
        }
        
        extract($data);
        if (empty($servicer))
            die(
                json_encode(
                    array(
                        'codeId' => '0002', 
                        'codeText' => '参数错误[error]')));
        if (empty($servicernumber))
            die(
                json_encode(
                    array(
                        'codeId' => '0003', 
                        'codeText' => '参数错误[error]')));
        if (empty($skill))
            die(
                json_encode(
                    array(
                        'codeId' => '0004', 
                        'codeText' => '参数错误[error]')));
        if (empty($skillnumber))
            die(
                json_encode(
                    array(
                        'codeId' => '0005', 
                        'codeText' => '参数错误[error]')));
        if (empty($allege))
            die(
                json_encode(
                    array(
                        'codeId' => '0006', 
                        'codeText' => '参数错误[error]')));
        $array = array(
            'node_id' => $this->node_id, 
            'jk_type' => 0, 
            'servicer' => $servicer, 
            'servicernumber' => $servicernumber, 
            'skill' => $skill, 
            'skillnumber' => $skillnumber, 
            'allege' => $allege, 
            'add_time' => date('YmdHis'));
        $fruit = M('twc_interface')->add($array);
        if ($fruit === false)
            die(
                json_encode(
                    array(
                        'codeId' => '0007', 
                        'codeText' => '申请入库失败[error]')));
            // 发邮件
        $nodeInfo = get_node_info($this->node_id);
        $content = '<BR/>申请商户: ' . $nodeInfo['node_name'];
        $content .= '<BR/>旺号: ' . $nodeInfo['client_id'];
        $content .= '<BR/>联系人: ' . $nodeInfo['contact_name'];
        $content .= '<BR/>联系电话: ' . $nodeInfo['contact_tel'] . '|' .
             $servicernumber;
        $content .= '<BR/>接入设备: ' . $allege;
        $content .= "<BR/>处理说明: 登录旺财后台→接口接入申请→门店终端接口申请→找到对应的记录，并点击'审核'";
        $arr = array(
            'subject' => $nodeInfo['node_name'] . '申请门店终端接入，请至旺财后台处理', 
            'content' => $content, 
            'email' => 'yulf@imageco.com.cn');
        send_mail($arr);
        die(
            json_encode(
                array(
                    'codeId' => '0000', 
                    'codeText' => '申请成功')));
    }
    
    $this->assign('terInfo', $terInfo);
    $this->display();
}

public function Wapply_terminal() {
    $where = array(
        '_string' => "a.node_id in (" . $this->nodeIn() . ")");
    $where['c.pos_type'] = 2;
    $where['a.pos_count'] = array(
        'gt', 
        '0');
    $eposCount = M()->table('tstore_info a')
        ->join('tpos_info c on a.store_id=c.store_id')
        ->field('a.*')
        ->where(
        array(
            'pos_status' => array(
                'not in', 
                '4')))
        ->where($where)
        ->order('a.id desc')
        ->count();
    
    $where['c.pos_type'] = 1;
    $pos6800Count = M()->table('tstore_info a')
        ->join('tpos_info c on a.store_id=c.store_id')
        ->field('a.*')
        ->where($where)
        ->order('a.id desc')
        ->count();
    $zfbInfo = M('tzfb_offline_pay_info')->where(
        array(
            "node_id" => $this->node_id, 
            "status" => 1))->count();
    // c1用户
    $wc_version = get_wc_version($this->node_id);
    $apply_error_text = ''; // 申请错误提示
    if ($wc_version == 'v0.5') {
        if ($this->_hasFreePosCreatePower()) {
            $epos_free_flag = 1;
            $this->assign('wc_version', $wc_version);
            $this->assign('epos_free_flag', $epos_free_flag);
            $freeInfo = $this->_FreePosCreateNotice();
            $apply_error_text = $freeInfo['notice'];
            if (isset($freeInfo['free_end_day'])) {
                $free_end_day = $freeInfo['free_end_day'];
                $this->assign('free_end_day', $free_end_day);
            }
        } else {
            $apply_error_text = '您无权限[01],立即<a href="' . U(
                'Home/Wservice/buywc') . '">开通</a>';
        }
    } elseif (! $this->_hasStandard()) {
        $apply_error_text = '您无权限[02],立即<a href="' . U('Home/Wservice/buywc') .
             '">开通</a>';
    }
    $codepay = M('tzfb_offline_pay_info')->field('GROUP_CONCAT(status) flag')
        ->where(array(
        'node_id' => $this->node_id))
        ->find();
    if (! empty($codepay)) {
        if (strpos($codepay['flag'], '1') !== false)
            $apply_error_text = '';
    }
    $this->assign('apply_error_text', $apply_error_text);
    $this->assign('userTrue1', $wc_version == 'v0.5' ? true : false);
    $this->assign('userTrue', $this->_hasStandard() ? true : false);
    $this->assign('zfbTrue', $zfbInfo > 0 ? true : false);
    $zfbInfo = M('tzfb_offline_pay_info')->where(
        array(
            "node_id" => $this->node_id, 
            "status" => 1))->count();
    $this->assign('userTrue', $this->_hasStandard() ? true : false);
    $this->assign('zfbTrue', $zfbInfo > 0 ? true : false);
    $this->assign('eposCount', $eposCount);
    $this->assign('pos6800Count', $pos6800Count);
    $this->display();
}

Public function storeEpos() {
    $posType = I('postype');
    if (in_array($posType, array(
        '1', 
        '2')) === false) {
        exit('参数错误[POSTYPE ERROR]');
    }
    $where = array(
        '_string' => "a.node_id in (" . $this->nodeIn() . ")");
    
    if (I('province_code') != '') {
        $where['a.province_code '] = I('province_code');
    }
    
    if (I('city_code') != '') {
        $where['a.city_code'] = I('city_code');
    }
    
    if (I('town_code') != '') {
        $where['a.town_code'] = I('town_code');
    }
    
    $eposCount = M()->table('tstore_info a')
        ->join('tpos_info c on a.store_id=c.store_id')
        ->field('a.*,c.pos_type')
        ->where($where)
        ->order('a.id desc')
        ->select();
    $noEpos = array();
    $ePos = array();
    foreach ($eposCount as $v) {
        if ($v['pos_type'] == $posType && $v['pos_count'] > 0) {
            $ePos[] = $v;
        } else {
            $noEpos[] = $v;
        }
    }
    // echo count($noEpos);
    $this->assign('noEpos', $noEpos);
    $this->assign('ePos', $ePos);
    $this->display();
}
// 单个旺财申请epos
public function wcAddpos($store_id) {
    if (empty($store_id)) {
        $this->error('没有选择门店[error]');
    }
    // 增加门店开始
    $this->eposAdd($store_id, 2);
}

public function wcAdd6800() {
    $posType = I('postype');
    if (in_array($posType, array(
        '1', 
        '2')) === false)
        $this->error('参数错误[POSTYPE ERROR]');
    $checkType = I('checktype');
    if (in_array($checkType, array(
        '0', 
        '1')) === false)
        $this->error('参数错误[error]');
    $checkNumber = I('checkNumber');
    
    $where = "a.node_id in (" . $this->nodeIn() . ")";
    $queryList = M()->table('tstore_info a')
        ->join('tnode_info b on b.node_id=a.node_id')
        ->field('a.*,b.node_name')
        ->where($where)
        ->order('a.id desc')
        ->select();
    if ($queryList === false)
        $this->error('未创建门店[error]');
    
    $storePos = array();
    // 可先
    if ($checkType == '1') {
        
        if (empty($checkNumber))
            $this->error('没有选择门店[error]');
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
    if (empty($storePos))
        $this->error('门店信息[error]');
    
    $fruit = D('StoreJob', 'Service');
    $result = $fruit->jobAdd($this->node_id, $storePos);
    // $this->success(print_r($result,true));
    if ($result === true)
        $this->success('申请信息已提交，客服稍后联系您预约上门安装！');
    
    $this->error('申请失败： ' . $fruit->error);
}

private function eposAdd($store_id, $type) {
    $storeInfo = M('tstore_info')->where(
        array(
            'store_id' => $store_id, 
            'node_id' => array(
                'exp', 
                "in ({$this->nodeIn()})")))->find();
    if (! $storeInfo) {
        $this->error("门店不存在");
    }
    $node_id = $storeInfo['node_id'];
    // 判断是否已有正常的ePos
    $countEpos = M('tpos_info')->where(
        array(
            'store_id' => $storeInfo['store_id'], 
            'node_id' => $node_id, 
            'pos_type' => '2', 
            'pos_status' => '0'))->count();
    if ($countEpos) {
        $this->error("该门店已开通过Epos。");
    }
    // 接收表单传值
    $req_arr = array();
    $pos_name = $storeInfo['store_short_name'];
    $req_arr['SystemID'] = C('ISS_SYSTEM_ID');
    $req_arr['TransactionID'] = time() . mt_rand('1000', '9999');
    $req_arr['ISSPID'] = $node_id;
    $req_arr['StoreID'] = $storeInfo['store_id'];
    $req_arr['PosGroupID'] = '';
    $req_arr['PosFlag'] = 0;
    $req_arr['PosType'] = 3;
    $req_arr['PosName'] = $pos_name;
    $req_arr['PosShortName'] = $pos_name;
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
            'PosCreateReq' => $req_arr));
    $respStatus = isset($req_result['PosCreateRes']) ? $req_result['PosCreateRes']['Status'] : $req_result;
    if ($respStatus['StatusCode'] != '0000') {
        $this->error($respStatus['StatusText']);
    }
    $respData = $req_result['PosCreateRes'];
    
    // $store_id = $respData['StoreID'];
    $pos_id = $respData['PosID'];
    
    if (! $pos_id) {
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
        'add_time' => date('YmdHis'));
    if ($type == 'EposAi') // 爱拍终端
{
        // 免费
        $data['pay_type'] = 1;
    } elseif ($type == 'EposSpring2015') // 春节打炮
{
        // 免费2
        $data['pay_type'] = 2;
    } elseif ($type == 'EposLaborDay') // 劳动节
{
        // 免费3
        $data['pay_type'] = 2;
    } else {
        $data['pay_type'] = 0;
    }
    $result = M('tpos_info')->add($data);
    if (! $result) {
        log_write(print_r($data, true) . M()->getDbError(), 'DB ERROR');
        $this->error('创建终端失败,原因：' . M()->getDbError());
    }
    // 更新pos_range
    if ($storeInfo['pos_range'] == '0') {
        if ($type == 'EposAi') { // 爱拍终端
            $data = array(
                'pos_range' => '1');
        } else {
            $data = array(
                'pos_range' => '2');
        }
        $result = M('tstore_info')->where("store_id={$storeInfo['store_id']}")->save(
            $data);
        if (! $result) {
            log_write(print_r($data, true) . M()->getDbError(), 'DB ERROR');
            $this->error('创建终端失败,原因：' . M()->getDbError());
        }
    }
    M()->commit();
    node_log("【门店管理】门店验证终端创建"); // 记录日志
    $this->success("门店验证终端已创建成功。请到邮箱中查收用户名和密码");
}
// 批量添加epos
private function eposAdd_pl($store_id, $type) {
    $storeInfo = M('tstore_info')->where(
        array(
            'store_id' => $store_id, 
            'node_id' => array(
                'exp', 
                "in ({$this->nodeIn()})")))->find();
    if (! $storeInfo) {
        $this->error("门店不存在");
    }
    $node_id = $storeInfo['node_id'];
    // 判断是否已有正常的ePos
    $countEpos = M('tpos_info')->where(
        array(
            'store_id' => $storeInfo['store_id'], 
            'node_id' => $node_id, 
            'pos_type' => '2', 
            'pos_status' => '0'))->count();
    if ($countEpos) {
        $this->error("该门店已开通过Epos。");
    }
    // 接收表单传值
    $req_arr = array();
    $pos_name = $storeInfo['store_short_name'];
    $req_arr['SystemID'] = C('ISS_SYSTEM_ID');
    $req_arr['TransactionID'] = time() . mt_rand('1000', '9999');
    $req_arr['ISSPID'] = $node_id;
    $req_arr['StoreID'] = $storeInfo['store_id'];
    $req_arr['PosGroupID'] = '';
    $req_arr['PosFlag'] = 0;
    $req_arr['PosType'] = 3;
    $req_arr['PosName'] = $pos_name;
    $req_arr['PosShortName'] = $pos_name;
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
            'PosCreateReq' => $req_arr));
    $respStatus = isset($req_result['PosCreateRes']) ? $req_result['PosCreateRes']['Status'] : $req_result;
    if ($respStatus['StatusCode'] != '0000') {
        $this->error($respStatus['StatusText']);
    }
    $respData = $req_result['PosCreateRes'];
    
    // $store_id = $respData['StoreID'];
    $pos_id = $respData['PosID'];
    
    if (! $pos_id) {
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
        'add_time' => date('YmdHis'));
    if ($type == 'EposAi') // 爱拍终端
{
        // 免费
        $data['pay_type'] = 1;
    } elseif ($type == 'EposSpring2015') // 春节打炮
{
        // 免费2
        $data['pay_type'] = 2;
    } elseif ($type == 'EposLaborDay') // 劳动节
{
        // 免费3
        $data['pay_type'] = 2;
    } else {
        $data['pay_type'] = 0;
    }
    $result = M('tpos_info')->add($data);
    if (! $result) {
        log_write(print_r($data, true) . M()->getDbError(), 'DB ERROR');
        $this->error('创建终端失败,原因：' . M()->getDbError());
    }
    M()->commit();
    node_log("【门店管理】门店验证终端创建"); // 记录日志
}

// 申请全业务eops终端商户类型校验
private function checkEposNodeType($eposType = 'epos') {
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
        '90');
    $nodeTrade = M('tnode_info')->where("node_id='{$this->nodeId}'")->getField(
        'trade_type');
    if ($this->node_type_name == 'staff' ||
         ($this->node_type_name == 'c2' && ! in_array($nodeTrade, $tradeType))) {
        return true;
    }
    return false;
}

public function myEpos() {
    $posStatus = array(
        '0' => '正常', 
        '1' => '欠费', 
        '2' => '停机保号', 
        '3' => '注销');
    $where = "p.pos_status not  in (4) and p.node_id in (" . $this->nodeIn() .
         ") AND p.pos_type  in(0,2) AND s.pos_count>0";
    
    if (I('jg_name') != '') {
        $where .= " AND p.store_name like '%" . I('jg_name') . "%'";
    }
    
    if (I('principal_name') != '') {
        $where .= " and s.principal_name like '%" . I('principal_name') . "%'";
    }
    
    if (I('province') != '') {
        $where .= " and s.province_code = '" . I('province') . "'";
    }
    
    if (I('city_code') != '') {
        $where .= " and s.city_code = '" . I('city_code') . "'";
    }
    
    if (I('town_code') != '') {
        $where .= " and s.town_code = '" . I('town_code') . "'";
    }
    
    $model = M();
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
        'p.*,s.principal_email,s.province_code,s.city_code,s.town_code,s.store_short_name')
        ->join('tstore_info s ON p.store_id=s.store_id')
        ->where($where)
        ->order('p.id desc')
        ->limit($Page->firstRow . ',' . $Page->listRows)
        ->select();
    
    $this->assign('page', $pageShow);
    $this->assign('list', $list);
    $this->assign('posStatus', $posStatus);
    $this->display();
}

public function myTool() {
    $posStatus = array(
        '0' => '正常', 
        '1' => '欠费', 
        '2' => '停机保号', 
        '3' => '注销', 
        '4' => '过期');
    $posJob = array(
        '1' => '未受理', 
        '2' => '已撤销', 
        '3' => '受理中', 
        '4' => '已拒绝', 
        '5' => '已结束', 
        '6' => '回退');
    
    $where = "p.node_id in (" . $this->nodeIn() .
         ") AND p.pos_type not in(0,2)  AND s.pos_count>0";
    
    if (I('jg_name') != '') {
        $where .= " AND p.store_name like '%" . I('jg_name') . "%'";
    }
    
    if (I('principal_name') != '') {
        $where .= " and s.principal_name like '%" . I('principal_name') . "%'";
    }
    
    if (I('province') != '') {
        $where .= " and s.province_code = '" . I('province') . "'";
    }
    
    if (I('city_code') != '') {
        $where .= " and s.city_code = '" . I('city_code') . "'";
    }
    
    if (I('town_code') != '') {
        $where .= " and s.town_code = '" . I('town_code') . "'";
    }
    
    $model = M();
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
        'p.*,s.principal_email,s.province_code,s.city_code,s.town_code,s.store_short_name')
        ->join('tstore_info s ON p.store_id=s.store_id')
        ->where($where)
        ->order('p.id desc')
        ->limit($Page->firstRow . ',' . $Page->listRows)
        ->select();
    
    $this->assign('page', $pageShow);
    $this->assign('list', $list);
    $this->assign('posJob', $posJob);
    $this->assign('posStatus', $posStatus);
    $this->display();
}

Public function storeNumber() {
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
    if ($province_code != '')
        $wh .= " and a.province_code = $province_code";
    if ($city_code != '')
        $wh .= " and a.city_code = $city_code";
    if ($town_code != '')
        $wh .= " and a.town_code = $town_code";
        
        // $sql="SELECT s.store_name,IFNULL(c.fcount,0) as fcount from
        // tstore_info s
        // LEFT JOIN (SELECT store_id,SUM(send_count) as fcount FROM tchannel
        // WHERE type=2 GROUP BY store_id ) c
        // ON s.store_id=c.store_id WHERE s.node_id in (".$this->nodeIn().")
        // ORDER BY c.fcount DESC limit 10";
    $sql = "SELECT a.store_short_name store_name,IFNULL(SUM(c.total_cnt),0) as fcount FROM tstore_info a
                LEFT JOIN tchannel b ON a.store_id=b.store_id
 				LEFT JOIN tgoods_send_stat c ON c.channel_id=b.id
				WHERE a.node_id in (" . $this->nodeIn() . ")
				GROUP BY a.store_id
				ORDER BY  fcount DESC limit 10";
    $result = M()->query($sql);
    
    // $storeCount=M('tstore_info s')->WHERE("s.node_id in (".$this->nodeIn().")
    // $wh")->COUNT();
    $storeCountsql = "SELECT a.store_short_name store_name,IFNULL(SUM(c.total_cnt),0) as fcount FROM tstore_info a
                LEFT JOIN tchannel b ON a.store_id=b.store_id
 				LEFT JOIN tgoods_send_stat c ON c.channel_id=b.id
				WHERE a.node_id in (" . $this->nodeIn() . ") $wh
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
    $listsql = "SELECT a.store_short_name store_name,IFNULL(SUM(c.total_cnt),0) as fcount FROM tstore_info a
                LEFT JOIN tchannel b ON a.store_id=b.store_id
 				LEFT JOIN tgoods_send_stat c ON c.channel_id=b.id
				WHERE a.node_id in (" . $this->nodeIn() . ") $wh
				GROUP BY a.store_id
				ORDER BY  fcount DESC limit " .
         $Page->firstRow . ',' . $Page->listRows;
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
                'storeSendMaName' => $storeSendMaName)));
    $this->assign('pageShow', $pageShow);
    $this->assign('listresult', $listresult);
    $this->display();
}

Public function storeNumber2() {
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
    if ($province_code2 != '')
        $wh2 .= " and a.province_code = $province_code2";
    if ($city_code2 != '')
        $wh2 .= " and a.city_code = $city_code2";
    if ($town_code2 != '')
        $wh2 .= " and a.town_code = $town_code2";
        
        // $verifySql="SELECT s.store_name,IFNULL(z.scount,0) as scount FROM
        // tstore_info s
        // LEFT JOIN (SELECT store_id,SUM(scount) as scount from (SELECT
        // p.store_id,c.vcount as scount FROM tpos_info p
        // LEFT JOIN (SELECT pos_id,SUM(verify_num) as vcount FROM
        // tpos_day_count GROUP BY pos_id) c ON p.pos_id=c.pos_id) v GROUP BY
        // store_id) z ON s.store_id=z.store_id
        // WHERE s.node_id in (".$this->nodeIn().") ORDER BY z.scount DESC limit
        // 10";
    $verifySql = "SELECT  a.store_id,a.store_short_name store_name,IFNULL(SUM(verify_num),0) scount FROM tstore_info  a
				LEFT JOIN tpos_info b ON a.store_id = b.store_id
				LEFT JOIN tpos_day_count c ON b.pos_id=c.pos_id
				WHERE a.node_id in (" . $this->nodeIn() . ")
				GROUP BY a.store_id,a.store_short_name
				ORDER BY scount DESC limit 10";
    $verifyResult = M()->query($verifySql);
    
    $storeCountsql = "SELECT  a.store_id,a.store_short_name store_name,IFNULL(SUM(verify_num),0) scount FROM tstore_info  a
				LEFT JOIN tpos_info b ON a.store_id = b.store_id
				LEFT JOIN tpos_day_count c ON b.pos_id=c.pos_id
				WHERE a.node_id in (" . $this->nodeIn() . ") $wh2
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
    $listverifySql = "SELECT  a.store_id,a.store_short_name store_name,IFNULL(SUM(verify_num),0) scount FROM tstore_info  a
				LEFT JOIN tpos_info b ON a.store_id = b.store_id
				LEFT JOIN tpos_day_count c ON b.pos_id=c.pos_id
				WHERE a.node_id in (" . $this->nodeIn() . ") $wh2
				GROUP BY a.store_id,a.store_short_name
				ORDER BY scount DESC limit " .
         $P->firstRow . ',' . $P->listRows;
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
                'storeVerifyMaName' => $storeVerifyMaName)));
    $this->assign('pShow', $pShow);
    $this->assign('listverifyResult', $listverifyResult);
    $this->display();
}

Public function static_active() {
    $where = array(
        '_string' => "a.node_id in (" . $this->nodeIn() . ")");
    
    import('ORG.Util.Page');
    
    $sql = "(SELECT type,store_id,SUM(click_count) as ccount,COUNT(0) AS fcount,sum(z) bcount FROM tchannel c
    			LEFT JOIN (SELECT z,channel_id FROM (SELECT channel_id,COUNT(0) AS z FROM tbatch_channel b where b.node_id in (" .
         $this->nodeIn() . ") GROUP BY b.channel_id ) r) as f ON c.id=f.channel_id
   				WHERE c.type=2 and c.node_id in (" . $this->nodeIn() .
         ") GROUP BY c.store_id)";
    
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
        'a.store_name,a.province_code,a.city_code,a.town_code,IFNULL(fcount,0) fcount,IFNULL(bcount,0) bcount, IFNULL(ccount,0) ccount')
        ->join("LEFT JOIN $sql e ON a.store_id=e.store_id ")
        ->where($where)
        ->order('a.id desc')
        ->limit($Page->firstRow . ',' . $Page->listRows)
        ->select();
    // echo M()->_sql();
    $this->assign('page', $pageShow);
    $this->assign('list', $list);
    $this->display();
}

public function storePosStatus() {
    $sId = I('get.sId');
    $pId = I('get.posId');
    $sStatus = I('get.sStatus');
    // 参数校验
    if (empty($sId))
        die(
            json_encode(
                array(
                    'codeId' => '0001', 
                    'codeText' => 'Parameter error')));
    if (empty($pId))
        die(
            json_encode(
                array(
                    'codeId' => '0001', 
                    'codeText' => 'Parameter error')));
    if (in_array($sStatus, array(
        '0', 
        '2')) === false)
        die(
            json_encode(
                array(
                    'codeId' => '0001', 
                    'codeText' => 'Illegal error')));
        // 校验本地状态
    $storePosStatus = M('tpos_info')->where(
        array(
            'pos_id' => $pId, 
            'store_id' => $sId))->getField('pos_status');
    if ($sStatus != $storePosStatus)
        die(
            json_encode(
                array(
                    'codeId' => '0001', 
                    'codeText' => '未知错误')));
        
        // 开始调用接口
    $arr = array(
        'TransactionID' => date('YmdHis') . mt_rand(100000, 999999), 
        'SystemID ' => C('ISS_SYSTEM_ID'), 
        'NodeId' => $this->node_id, 
        'UserId' => $this->node_id, 
        'StoreId' => $sId, 
        'PosId' => $pId, 
        'EnablelFlag' => $sStatus == 0 ? 0 : 1);
    
    $reqResult = D('RemoteRequest', 'Service')->requestIssForImageco(
        array(
            'PosStatusModifyReq' => $arr));
    $codeResult = $reqResult['PosStatusModifyReq']['Status']['StatusCode'];
    // 修改失败
    if ($codeResult != '0000')
        die(
            json_encode(
                array(
                    'codeId' => '0002', 
                    'codeText' => '修改EPOS状态失败' . print_r($reqResult, true))));
    
    $posStatus = $sStatus == 0 ? 2 : 0;
    $posText = $sStatus == 0 ? '停用' : '启用';
    $result = M('tpos_info')->where(
        array(
            'pos_id' => $pId, 
            'store_id' => $sId))->save(
        array(
            'pos_status' => $posStatus));
    if ($result === false)
        die(
            json_encode(
                array(
                    'codeId' => '0003', 
                    'codeText' => '修改EPOS状态失败')));
    node_log("【门店管理】终端$posText");
    die(
        json_encode(
            array(
                'codeId' => '0000', 
                'codeText' => '修改EPOS状态成功')));
}

public function storePosApply() {
    $sId = I('get.sId');
    $pId = I('get.posId');
    $sStatus = I('get.sStatus');
    
    if (empty($sId))
        die(
            json_encode(
                array(
                    'codeId' => '0001', 
                    'codeText' => 'Parameter error')));
    if (empty($pId))
        die(
            json_encode(
                array(
                    'codeId' => '0001', 
                    'codeText' => 'Parameter error')));
    if ($sStatus != 0)
        die(
            json_encode(
                array(
                    'codeId' => '0001', 
                    'codeText' => 'Illegal error')));
    
    $storePosStatus = M('tpos_info')->where(
        array(
            'pos_id' => $pId, 
            'store_id' => $sId))->getField('pos_status');
    if ($sStatus != $storePosStatus || $storePosStatus != 0)
        die(
            json_encode(
                array(
                    'codeId' => '0001', 
                    'codeText' => '未知错误')));
    
    $fruit = D('StoreJob', 'Service');
    $result = $fruit->jobApply($this->node_id, $pId, $sId);
    // die(json_encode(array('codeId'=>'0003','codeText'=>print_r($result,true))));
    if ($result === true) {
        $upDate = M('tpos_info')->where(
            array(
                'pos_id' => $pId, 
                'store_id' => $sId, 
                'node_id' => $this->node_id))->save(
            array(
                'applyStatus' => 1));
        node_log("【门店管理】终端撤机");
        die(
            json_encode(
                array(
                    'codeId' => '0000', 
                    'codeText' => '提交申请成功，请耐心等待')));
    }
    die(
        json_encode(
            array(
                'codeId' => '0002', 
                'codeText' => '提交申请失败： ' . $fruit->error)));
}

public function getError() {
    $fileName = 'shibai.csv';
    header("Content-type: text/plain");
    header("Accept-Ranges: bytes");
    header("Content-Disposition: attachment; filename=" . $fileName);
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Pragma: no-cache");
    header("Expires: 0");
    $batchNo = I('get.batchNo');
    $sblist = M('tstorebatchapply')->where(
        array(
            'fileName' => $batchNo, 
            'node_id' => $this->node_id, 
            'status' => 0))->select();
    $title = "行号,门店名称,门店简称,省,市,区,详细地址,门店联系电话,负责人,负责人手机,负责人邮箱,失败原因\r\n";
    echo $title_ = iconv('utf-8', 'gbk', $title);
    if ($sblist) {
        foreach ($sblist as $v) {
            echo $v['lineNumber'] . ",";
            echo iconv('utf-8', 'gbk', $v['StoreName']) . ",";
            echo iconv('utf-8', 'gbk', $v['StoreShortName']) . ",";
            echo iconv('utf-8', 'gbk', $v['province_code']) . ",";
            echo iconv('utf-8', 'gbk', $v['city_code']) . ",";
            echo iconv('utf-8', 'gbk', $v['town_code']) . ",";
            echo iconv('utf-8', 'gbk', $v['address']) . ",";
            echo iconv('utf-8', 'gbk', $v['storeTel']) . ",";
            echo iconv('utf-8', 'gbk', $v['ContactName']) . ",";
            echo iconv('utf-8', 'gbk', $v['ContactTel']) . ",";
            echo iconv('utf-8', 'gbk', $v['ContactEmail']) . ",";
            echo iconv('utf-8', 'gbk', $v['statusPs']) . "\r\n";
        }
    }
}

public function setError($str, $batchNo = '') {
    if ($batchNo != '') {
        $sbNumber = M('tstorebatchapply')->where(
            array(
                'fileName' => $batchNo, 
                'node_id' => $this->node_id, 
                'status' => 0))->count();
        if ($sbNumber > 0)
            $str .= ",失败" . $sbNumber .
                 "条，<a href='index.php?g=Yhb&m=YhbStore&a=getError&batchNo=" .
                 $batchNo . "'>查看详细</a>";
    }
    $this->assign('str', $str);
    $this->display('Store/YhbStore_setError');
    exit();
}

public function batchApply() {
    C('STORE_TPL_APPLY', APP_PATH . 'Upload/store_apply/');
    if ($this->isPost()) {
        import('ORG.Net.UploadFile') or die('导入上传包失败');
        $upload = new UploadFile();
        $upload->maxSize = 3145728;
        $upload->savePath = C('STORE_TPL_APPLY');
        $info = $upload->uploadOne($_FILES['staff']);
        $flieWay = $upload->savePath . $info['savepath'] . $info[0]['savename'];
        $row = 0;
        $filename = explode('.', pathinfo($flieWay, PATHINFO_BASENAME));
        if (pathinfo($flieWay, PATHINFO_EXTENSION) != 'csv') {
            @unlink($flieWay);
            $this->setError('文件类型不符合');
        }
        if (($handle = fopen($flieWay, "rw")) !== FALSE) {
            while (($arr = fgetcsv($handle, 1000, ",")) !== FALSE) {
                ++ $row;
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
                        '负责人邮箱');
                    $arrDiff = array_diff_assoc($arr, $fileField);
                    if (count($arr) != count($fileField) || ! empty($arrDiff)) {
                        fclose($handle);
                        @unlink($flieWay);
                        $this->setError(
                            '文件第' . $row . '行字段不符合要求,请确保与下载模板文件中第一行的字段保持一致!');
                    }
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
                        'CreateStoreReq' => $result));
                $respStatus = isset($req_result['CreateStoreRes']) ? $req_result['CreateStoreRes']['Status'] : $req_result['Status'];
                if ($respStatus['StatusCode'] != '0000') {
                    $array['statusPs'] = $respStatus['StatusText'];
                    $fruit = M('tstorebatchapply')->add($array);
                    continue;
                }
                $respData = $req_result['CreateStoreRes'];
                $store_id = $respData['StoreId'];
                if (! $store_id) {
                    $array['statusPs'] = '创建支撑门店失败';
                    $fruit = M('tstorebatchapply')->add($array);
                    continue;
                }
                
                if (M('tstore_info')->where(
                    array(
                        'store_id' => $store_id))->count()) {
                    $array['statusPs'] = '门店号[' . $store_id . ']已经存在。';
                    $fruit = M('tstorebatchapply')->add($array);
                    continue;
                }
                $result['store_phone'] = $array['storeTel'];
                $result['store_id'] = $store_id;
                if ($this->addStoreTable($result, 
                    $arr[2] . $arr[3] . $arr[4] . $arr[5])) {
                    // 导入store_info成功
                    $this->eposAdd_pl($store_id, 2);
                    $array['status'] = 1;
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
    
    $this->display("Store/YhbStore_batchApply");
}

private function getGlide() {
    $sql = "SELECT _nextval('sys_uni_seq') as u FROM DUAL";
    $fruit = M()->query($sql);
    $number = $fruit[0]['u'];
    return str_pad($number, 20, "0", STR_PAD_LEFT);
}

private function checkFileContent($arr) {
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
    if ($arr['storeTel'] == '') {
        $this->file_error = '门店联系电话为空';
        return false;
    }
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
    if (! preg_match('/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/', 
        $arr['ContactEmail'])) {
        $this->file_error = '门店负责人邮箱格式不对';
        return false;
    }
    $prow = M('tcity_code')->where(
        array(
            '_string' => "province like '%" . $arr['province_code'] . "%'"))->getfield(
        'province_code');
    if ($prow == '') {
        $this->file_error = '门店省份没找到';
        return false;
    }
    
    $crow = M('tcity_code')->where(
        array(
            '_string' => "city like '%" . $arr['city_code'] . "%'"))->getfield(
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
    $req_arr['TransactionID'] = time() . mt_rand('1000', '9999');
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
        'Address' => $arr['address']);
    return $req_arr;
}

private function addStoreTable($arr, $strCity) {
    $qian = array(
        '/\s/', 
        '/ /', 
        '/\t/', 
        '/\n/', 
        '/\r/');
    $hou = array(
        "", 
        "", 
        "", 
        "", 
        "");
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
        'store_phone' => $arr['store_phone']);
    return M('tstore_info')->add($data);
}

public function addUrl($strUrl) {
    $fields = array(
        'output' => 'json', 
        'ak' => '96b4191b34ecf02a727747aaf0eedcbb', 
        'address' => $strUrl);
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
        'precise' => $dataArr->result->precise);
    return $info;
}

public function _FreePosCreateNotice() {
    $info = $this->_hasFreePosCreatePower(true);
    // 如果当前没有限免活动
    if (! $info['currFreeInfo'])
        return array(
            'notice' => '');
    
    $free_end_day = dateformat($info['currFreeInfo']['pos_end_time'], 'Y年m月d日');
    // 如果当前机构没有参与限免的记录
    if (! $info['nodeFreeInfo']) {
        $batch_type = $info['currFreeInfo']['batch_type'];
        $batch_name = C('BATCH_TYPE_NAME.' . $batch_type);
        $batch_list_url = U(C('BATCH_LIST_URL.' . $batch_type));
        return array(
            'notice' => '请先创建"' . $batch_name . '"活动，<a href="' . $batch_list_url .
                 '">立即创建</a>', 
                'free_end_day' => $free_end_day);
    }
    
    // 如果当前机构已经参与以前的限免活动
    if ($info['nodeFreeInfo']['add_time'] < $info['currFreeInfo']['begin_time']) {
        $batch_type = $info['nodeFreeInfo']['batch_type'];
        $batch_name = C('BATCH_TYPE_NAME.' . $batch_type);
        return array(
            'notice' => '您参与过' . $batch_name . ',请开通<a href="' .
                 U('Home/Wservice/buywc') . '">标准版</a>');
    } else {
        return array(
            'free_end_day' => $free_end_day);
    }
}

public function _hasFreePosCreatePower($return = false) {
    static $currFreeInfo = null;
    static $nodeFreeInfo = null;
    static $posCount = null;
    if ($return) {
        return array(
            'currFreeInfo' => $currFreeInfo, 
            'nodeFreeInfo' => $nodeFreeInfo, 
            'posCount' => $posCount);
    }
    $now = date('YmdHis');
    // 获取当前时间段的限免活动
    if ($currFreeInfo === null) {
        $currFreeInfo = M('tbatch_free_set')->where(
            "'{$now}' between begin_time and end_time")->find();
    }
    if (! $currFreeInfo)
        return false;
        // 判断商户是否有参与过限免活动
    $node_map = array(
        'node_id' => $this->node_id);
    if ($nodeFreeInfo === null)
        $nodeFreeInfo = M('tbatch_free_trace')->where($node_map)->find();
    if (! $nodeFreeInfo)
        return true;
    if ($nodeFreeInfo['add_time'] >= $currFreeInfo['begin_time'] &&
         $nodeFreeInfo['end_time'] <= $currFreeInfo['end_time']) {
        // 判断商户是否已经有终端
        if ($posCount === null)
            $posCount = M('tpos_info')->where($node_map)->count();
        if ($posCount > 0)
            return false;
        return true;
    } else {
        return false;
    }
}
// 城市商区管理
public function YhbCity() {
    $arr = I('get.');
    if ($arr) {
        $map = array();
        if ($arr['province_code']) {
            $map['province_code'] = $arr['province_code'];
        }
        if ($arr['city_code']) {
            $map['city_code'] = $arr['city_code'];
        }
        if ($arr['town_code']) {
            $map['town_code'] = $arr['town_code'];
            $this->assign('town_code', $arr['town_code']);
        }
        if ($arr['province_code'] && $arr['city_code'] && $arr['town_code']) {
            // $town_name=$this->getTownName($arr['province_code'],$arr['city_code'],$arr['town_code']);
            // $this->assign("town_name",$town_name);
        }
    }
    $data = $_REQUEST;
    import('ORG.Util.Page'); // 导入分页类
    $map['city_level'] = 1;
    $map['node_id'] = $this->node_id;
    $mapcount = M("tfb_yhb_city_code")->where($map)->count();
    $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
    foreach ($data as $key => $val) {
        $Page->parameter .= "&$key=" . urlencode($val) . '&';
    }
    $show = $Page->show(); // 分页显示输出
    $city_arr = M("tfb_yhb_city_code")->where($map)
        ->limit($Page->firstRow . ',' . $Page->listRows)
        ->select();
    foreach($city_arr as $kk=>&$vv){
        $vv['address']=D('CityCode')->getAreaText($vv['province_code'].$vv['city_code'].$vv['town_code']);
    }
    $this->assign('page', $show); // 赋值分页输出
    $this->assign('post', $data);
    $this->assign('city_arr', $city_arr);
    $this->display("Store/YhbStore_city");
}
// 新增商圈
public function streetAdd() {
    if ($this->check_is_admin($this->node_id) === false) {
        $this->error("对不起，您没有权限操作！");
    }
    $province_code = I('province_code');
    $city_code = I('city_code');
    $town_code = I('town_code');
    $street = I('street');
    if ($province_code && $city_code && $town_code && $street) {
        $map = array(
            'street' => $street, 
            'node_id' => $this->node_id);
        $res = M("tfb_yhb_city_code")->where($map)->find();
        if ($res) {
            $this->error("该商圈已经存在！");
        }
        $street_code = M()->query(
            "SELECT _nextval('$this->yhb_street_code') FROM DUAL");
        $data = array(
            "province_code" => $province_code, 
            "city_code" => $city_code, 
            "town_code" => $town_code, 
            'street' => $street, 
            'city_level' => 1, 
            'street_code' => $street_code[0]["_nextval('$this->yhb_street_code')"], 
            'node_id' => $this->node_id);
        $res = M("tfb_yhb_city_code")->add($data);
        if ($res === false) {
            $this->error("操作失败！");
        }
        $this->success("操作成功！");
    }
    $this->display("Store/YhbStore_addstreet");
}
// 增加小区
public function addVillage() {
    if ($this->check_is_admin($this->node_id) === false) {
        $this->error("对不起，您没有权限操作");
    }
    $village = I('village');
    $street_code = I('street_code');
    if ($village && $street_code) {
        $yhb_has = M("tfb_yhb_city_code")->where(
            array(
                "street_code" => $street_code, 
                'village' => $village, 
                'node_id' => $this->node_id))->find();
        if ($yhb_has) {
            $this->error("商圈下的该小区已经存在，无法添加小区");
        }
        // 添加商圈下的小区
        $street_info = M("tfb_yhb_city_code")->where(
            array(
                'city_level' => 1, 
                "street_code" => $street_code, 
                'node_id' => $this->node_id))->find();
        $village_code = M()->query(
            "SELECT _nextval('$this->yhb_village_code') FROM DUAL");
        $data = array(
            'province_code' => $street_info['province_code'], 
            'city_code' => $street_info['city_code'], 
            'town_code' => $street_info['town_code'], 
            'street_code' => $street_info['street_code'], 
            'street' => $street_info['street'], 
            'city_level' => 2, 
            'village' => $village, 
            "village_code" => $village_code[0]["_nextval('$this->yhb_village_code')"], 
            'node_id' => $this->node_id);
        $city_res = M("tfb_yhb_city_code")->add($data);
        if ($city_res === false) {
            $this->error("添加小区失败！");
        } else {
            $this->success("添加小区成功！");
        }
    }
    $this->assign("street_code", $street_code);
    $this->display("Store/YhbStore_addvillage");
}
// 浏览小区列表
public function villageView() {
    $street_code = I("street_code");
    if (empty($street_code)) {
        $this->error("商圈code不能为空");
    }
    // 通过street_code获取商圈名称
    $street_map = array(
        'street_code' => $street_code, 
        'city_level' => 1, 
        'node_id' => $this->node_id);
    $street = M("tfb_yhb_city_code")->where($street_map)->getField('street');
    $map = array(
        'street_code' => $street_code, 
        'city_level' => 2, 
        'node_id' => $this->node_id);
    $data = $_REQUEST;
    import('ORG.Util.Page'); // 导入分页类
    $mapcount = M("tfb_yhb_city_code")->where($map)->count();
    $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
    foreach ($data as $key => $val) {
        $Page->parameter .= "&$key=" . urlencode($val) . '&';
    }
    $this->assign('street', $street);
    $show = $Page->show(); // 分页显示输出
    $list = M("tfb_yhb_city_code")->where($map)
        ->limit($Page->firstRow . ',' . $Page->listRows)
        ->order('id desc')
        ->select();
    foreach($list as $kk=>&$vv){
        $vv['address']=D('CityCode')->getAreaText($vv['province_code'].$vv['city_code'].$vv['town_code']);
    }
    $this->assign('list', $list);
    $this->assign('page', $show); // 赋值分页输出
    $this->display("Store/YhbStore_villageView");
}
// 编辑商圈
public function editStreet() {
    if ($this->check_is_admin($this->node_id) === false) {
        $this->error("对不起，您没有权限操作");
    }
    $street_code = I("street_code");
    if (empty($street_code)) {
        $this->error("商圈ID不能为空");
    }
    $street_list = M("tfb_yhb_city_code")->where(
        array(
            "street_code" => $street_code, 
            "city_level" => 1, 
            'node_id' => $this->node_id))->find();
    $this->assign("street_list", $street_list);
    $this->assign("street_code", $street_code);
    $this->display("Store/YhbStore_editStreet");
}
// 保存修改的商圈
public function saveStreet() {
    if ($this->check_is_admin($this->node_id) === false) {
        $this->error("对不起，您没有权限操作");
    }
    $street = I("street");
    if (empty($street)) {
        $this->error("修改商圈不能为空！");
    }
    // 查询修改的商圈名称是否有重复
    $store_has = M("tfb_yhb_city_code")->where(
        array(
            "street" => $street, 
            'node_id' => $this->node_id))->find();
    if ($store_has) {
        $this->error("该商圈名称已经存在！");
    }
    $street_code = I("street_code");
    if (empty($street_code)) {
        $this->error("商圈ID不能为空！");
    }
    $data = array(
        "street" => $street);
    $res = M("tfb_yhb_city_code")->where(
        array(
            "street_code" => $street_code, 
            "city_level" => 1, 
            'node_id' => $this->node_id))->save($data);
    if ($res === false) {
        $this->error("修改商圈名称失败!");
    }
    $this->success("修改商圈名称成功!");
}
// 修改小区名称
public function editVillage() {
    if ($this->check_is_admin($this->node_id) === false) {
        $this->error("对不起，您没有权限操作");
    }
    $id = I("id");
    $village = I("village");
    if (empty($village)) {
        $this->error("修改的小区名称不能为空！");
    }
    // 查询修改的商圈名称是否有重复
    $store_has = M("tfb_yhb_city_code")->where(
        array(
            "village" => $village, 
            'node_id' => $this->node_id))->find();
    if ($store_has) {
        $this->error("该小区名称已经存在！");
    }
    if (empty($id)) {
        $this->error("参数错误！");
    }
    $data = array(
        'village' => $village);
    $res = M("tfb_yhb_city_code")->where(
        array(
            "id" => $id, 
            'node_id' => $this->node_id))->save($data);
    if ($res === false) {
        $this->error("修改小区失败");
    }
    $this->success("修改成功！");
}
// 城市编号
public function townCode() {
    // 默认是上海，上海市
    $province_code = "09";
    $city_code = "021";
    $model = M('tcity_code');
    $map = array(
        'city_level' => '3', 
        'province_code' => $province_code, 
        'city_code' => $city_code);
    $query_arr = $model->where($map)->select();
    $town_arr = array();
    foreach ($query_arr as $key => $val) {
        $town_arr[$val['town_code']] = $val['town'];
    }
    return $town_arr;
}

public function street_info() {
    $town_code = I("town_code");
    if ($town_code == "") {
        $this->error("城市区号不能为空！");
    }
    $map = array(
        'province_code' => "09", 
        'city_code' => "021", 
        'town_code' => $town_code, 
        "city_level" => '1', 
        'node_id' => $this->node_id);
    $street_info = M("tfb_yhb_city_code")->where($map)->select();
    if (empty($street_info)) {
        exit(
            json_encode(
                array(
                    'info' => "该区域内无商圈", 
                    'status' => 0)));
    }
    exit(
        json_encode(
            array(
                'info' => $street_info, 
                'status' => 1)));
}
// 通过商圈code获取小区信息
public function village_info() {
    $street_code = I("street_code");
    if ($street_code == "") {
        $this->error("城市区号不能为空！");
    }
    $map = array(
        'province_code' => "09", 
        'city_code' => "021", 
        'street_code' => $street_code, 
        "city_level" => '2', 
        'node_id' => $this->node_id);
    $village_info = M("tfb_yhb_city_code")->where($map)->select();
    if (empty($village_info)) {
        exit(
            json_encode(
                array(
                    'info' => "该区域内无小区", 
                    'status' => 0)));
    }
    exit(
        json_encode(
            array(
                'info' => $village_info, 
                'status' => 1)));
}

/**
 * 门店排序
 */
public function chg_order() {
    if ($this->check_is_admin($this->node_id) === false) {
        $this->error("对不起，您没有权限操作");
    }
    $store_id = I('store_id', null);
    $order = I('order', 0, 'intval');
    if (is_null($store_id) || is_null($order)) {
        $this->error('缺少必要参数');
    }

    $result = M()->table("tfb_yhb_store m")->where(
        array(
            "m.store_id" => $store_id))->find();
    
    if (! $result) {
        $this->error('未找到该记录');
    }
    
    $flag = M('tfb_yhb_store')->where(
        array(
            "store_id" => $store_id))->save(
        array(
            'order_sort' => $order));
    if ($flag === false) {
        $this->error('排序失败！请重试');
    }
    
    $this->success('更新成功');
}
// 下线
public function downLine() {
    if ($this->check_is_admin($this->node_id) === false) {
        $this->error("对不起，您没有权限操作");
    }
    $store_id = I("store_id");
    if ($store_id == "") {
        $this->error("缺少必要参数！");
    }
    $line_status = I('line_status');
    if ($line_status == "") {
        $this->error("缺少必要参数！");
    }
    if ($line_status == '1') { // 上架
                               // 上架前先判断该商品是否已停用 tgoods_info
        $data = array(
            'line_status' => '1');
    } else if ($line_status == '2') {
        $data = array(
            'line_status' => '2');
    }
    $res = M("tfb_yhb_store")->where(
        array(
            'store_id' => $store_id))->save($data);
    if ($res === false) {
        $this->error('操作失败,请重试!');
    }
    $this->success("操作成功！");
}
// 删除商圈信息
public function deleteStreet() {
    if ($this->check_is_admin($this->node_id) === false) {
        $this->error("对不起，您没有权限操作");
    }
    $street_code = I('street_code');
    if (empty($street_code)) {
        $this->error("商圈code不能为空");
    }
    $map = array(
        'street_code' => $street_code, 
        'node_id' => $this->node_id, 
        'city_level' => 2);
    $res = M("tfb_yhb_city_code")->where($map)->find();
    if ($res) {
        $this->error("无法删除该商圈，请先删除该商圈下面的小区");
    }
    $map_arr = array(
        'street_code' => $street_code, 
        'node_id' => $this->node_id, 
        'city_level' => 1);
    $res_delete = M("tfb_yhb_city_code")->where($map_arr)->delete();
    if (! $res_delete) {
        $this->error("删除商圈信息失败");
    }
    $this->success("删除商圈信息成功！");
}
// 删除商圈信息
public function deleteVillage() {
    if ($this->check_is_admin($this->node_id) === false) {
        $this->error("对不起，您没有权限操作");
    }
    $village_code = I('village_code');
    if (empty($village_code)) {
        $this->error("城市code不能为空");
    }
    // 删除小区之前，判断小区下面是否有门店
    $map_1 = array(
        'village_code' => $village_code, 
        'node_id' => $this->node_id);
    $res = M("tfb_yhb_store")->where($map_1)->find();
    if ($res) {
        $this->error("该小区已有门店，无法删除");
    }
    $map = array(
        'village_code' => $village_code, 
        'node_id' => $this->node_id, 
        'city_level' => 2);
    $res_delete = M("tfb_yhb_city_code")->where($map)->delete();
    if (! $res_delete) {
        $this->error("删除小区失败");
    }
    $this->success("删除小区成功！");
}
// 通过区号来获取区域名称
public function getTownName($province_code, $city_code, $town_code) {
    $map = array(
        'province_code' => $province_code, 
        'city_code' => $city_code, 
        'town_code' => $town_code);
    $town = M("tcity_code")->where($map)->getField("town");
    if ($town) {
        return $town;
    }
}
// 单个EPOS开设
public function eposAddOne() {
    $store_id = I("store_id");
    $type = 2;
    $storeInfo = M('tstore_info')->where(
        array(
            'store_id' => $store_id, 
            'node_id' => $this->node_id))->find();
    if (! $storeInfo) {
        $this->error("门店不存在");
    }
    $node_id = $storeInfo['node_id'];
    // 判断是否已有正常的ePos
    $countEpos = M('tpos_info')->where(
        array(
            'store_id' => $storeInfo['store_id'], 
            'node_id' => $node_id, 
            'pos_type' => '2', 
            'pos_status' => '0'))->count();
    if ($countEpos) {
        $this->error("该门店已开通过Epos。");
    }
    // 接收表单传值
    $req_arr = array();
    $pos_name = $storeInfo['store_short_name'];
    $req_arr['SystemID'] = C('ISS_SYSTEM_ID');
    $req_arr['TransactionID'] = time() . mt_rand('1000', '9999');
    $req_arr['ISSPID'] = $node_id;
    $req_arr['StoreID'] = $storeInfo['store_id'];
    $req_arr['PosGroupID'] = '';
    $req_arr['PosFlag'] = 0;
    $req_arr['PosType'] = 3;
    $req_arr['PosName'] = $pos_name;
    $req_arr['PosShortName'] = $pos_name;
    $req_arr['UserID'] = $this->userId;
    $req_result = D('RemoteRequest', 'Service')->requestIssServ(
        array(
            'PosCreateReq' => $req_arr));
    $respStatus = isset($req_result['PosCreateRes']) ? $req_result['PosCreateRes']['Status'] : $req_result;
    if ($respStatus['StatusCode'] != '0000') {
        $this->error($respStatus['StatusText']);
    }
    $respData = $req_result['PosCreateRes'];
    
    // $store_id = $respData['StoreID'];
    $pos_id = $respData['PosID'];
    
    if (! $pos_id) {
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
        'add_time' => date('YmdHis'), 
        'yhb_flage' => $this->merchant_id);
    if ($type == 'EposAi') // 爱拍终端
{
        // 免费
        $data['pay_type'] = 1;
    } elseif ($type == 'EposSpring2015') // 春节打炮
{
        // 免费2
        $data['pay_type'] = 2;
    } elseif ($type == 'EposLaborDay') // 劳动节
{
        // 免费3
        $data['pay_type'] = 2;
    } else {
        $data['pay_type'] = 0;
    }
    $result = M('tpos_info')->add($data);
    if (! $result) {
        log_write(print_r($data, true) . M()->getDbError(), 'DB ERROR');
        $this->error('创建终端失败,原因：' . M()->getDbError());
    }
    // 更新pos_range
    if ($storeInfo['pos_range'] == '0') {
        if ($type == 'EposAi') { // 爱拍终端
            $data = array(
                'pos_range' => '1');
        } else {
            $data = array(
                'pos_range' => '2');
        }
        $result = M('tstore_info')->where("store_id={$storeInfo['store_id']}")->save(
            $data);
        if (! $result) {
            log_write(print_r($data, true) . M()->getDbError(), 'DB ERROR');
            $this->error('创建终端失败,原因：' . M()->getDbError());
        }
    }
    M()->commit();
    node_log("【门店管理】门店验证终端创建"); // 记录日志
    $this->success("门店验证终端已创建成功。请到邮箱中查收用户名和密码");
}
}