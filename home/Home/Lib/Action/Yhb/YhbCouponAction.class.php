<?php

/**
 * 平安商品、优惠券管理
 */
class YhbCouponAction extends YhbAction {

    public $_authAccessMap = '*';

    public $GOODS_TYPE_GOODS = 2;
    // 商品类型 营销品类型
    public $GOODS_TYPE_COUPON = 0;
    // 商品类型 营销品类型
    public $BATCH_TYPE_GOODS = 38;
    // 商品类型 tmarketing_info
    public $BATCH_TYPE_COUPON = 2000;
    // 优惠类型 tmarketing_info
    public $CHANNEL_TYPE = "4";

    public $CHANNEL_SNS_TYPE = "";
    // 平安默认渠道类型
    public $img_path = "";

    public $tmp_path = "";

    public function _initialize() {
        parent::_initialize();
        import('@.Vendor.CommonConst');
        $this->CHANNEL_SNS_TYPE = CommonConst::SNS_TYPE_YHB;
        $path_arr = C('BATCH_IMG_PATH_NAME');
        $img_path = $path_arr[$this->BATCH_TYPE_GOODS] . '/' . $this->node_id .
             '/';
        $this->img_path = $img_path;
        $townCode = $this->townCode();
        $this->assign('townCode_list', $townCode);
        // 临时路径
        $tmp_path = 'Upload/img_tmp' . '/' . $this->node_id . '/';
        $this->assign('img_path', $img_path);
        $this->assign('tmp_path', $tmp_path);
    }

    /**
     * 优惠排序
     */
    public function chg_order() {
        $goods_id = I('goods_id', null);
        $order = I('order', 0, 'intval');
        if (is_null($goods_id) || is_null($order)) {
            $this->error('缺少必要参数');
        }
        $result = M()->table("tfb_yhb_goods m")->where(
            array(
                "m.goods_id" => $goods_id))->find();
        if (! $result) {
            $this->error('未找到该记录');
        }
        $flag = M('tfb_yhb_goods')->where(
            array(
                "goods_id" => $goods_id))->save(
            array(
                'order_sort' => $order));
        if ($flag === false) {
            $this->error('排序失败！请重试');
        }
        $this->success('更新成功');
    }

    /**
     * 商品上架
     */
    public function goods_online() {
        if ($this->check_is_admin($this->node_id) == true) {
            $this->error("对不起，您没有权限操作");
        }
        $goods_id = I('goods_id');
        $status = I('status');
        if (empty($goods_id) || empty($status)) {
            $this->error("缺少必要参数");
        }
        // 查询商品或者活动是否过期
        $goods_info = M("tgoods_info")->where(
            array(
                'goods_id' => $goods_id))->find();
        // 判断商品有没有过期,结束时间小于当前时间视为过期
        if ($goods_info['end_time'] < date("YmdHis", time())) {
            $this->error("活动已经过期，无法上架！");
        }
        $data = array(
            'line_status' => $status);
        $res = M("tfb_yhb_goods")->where(
            array(
                'goods_id' => $goods_id, 
                'node_id' => $this->node_id))->save($data);
        if ($res === false) {
            $this->error("优惠上架失败！");
        }
        $this->success("优惠上架成功");
    }

    /**
     * 商品下架
     */
    public function goods_offline() {
        if ($this->check_is_admin($this->node_id) == true) {
            $this->error("对不起，您没有权限操作");
        }
        $goods_id = I('goods_id');
        $status = I('status');
        if (empty($goods_id) || empty($status)) {
            $this->error("缺少必要参数");
        }
        $data = array(
            'line_status' => $status);
        $res = M("tfb_yhb_goods")->where(
            array(
                'goods_id' => $goods_id, 
                'node_id' => $this->node_id))->save($data);
        if ($res === false) {
            $this->error("优惠下架失败！");
        }
        $this->success("优惠下架成功");
    }

    public function coupon() {
        $online = I('online', null);
        $map = array(
            'm.node_id' => $this->node_id);
        if ($online == '1')
            $map['pmc.shelf_status'] = '1';
        
        $data = $_REQUEST;
        if ($data['key'] != '') {
            $map['m.name'] = array(
                'like', 
                '%' . $data['key'] . '%');
        }
        if ($data['status'] != '') {
            $map['pg.line_status'] = $data['status'];
        }
        if ($data['merchant_short_name'] != '') {
            $map['pm.merchant_short_name'] = array(
                'like', 
                '%' . $data['merchant_short_name'] . '%');
        }
        if ($data['cate_id'] != '') {
            $map['pm.cate_id'] = $data['cate_id'];
        }
        if ($data['org_code'] != '') {
            $map['pm.city_code'] = $data['org_code'];
        }
        // 商户查询
        if ($data['town_code'] != '') {
            $map['stra.town_code'] = $data['town_code'];
        }
        // 街道
        if ($data['street_code'] != '') {
            $map['strb.street_code'] = $data['street_code'];
        }
        // 小区
        if ($data['village_code'] != '') {
            $map['strb.village_code'] = $data['village_code'];
        }
        $map['stra.city_level'] = 1;
        
        if ($data['parent_id'] != '') {
            $map['plc.parent_id'] = $data['parent_id'];
        }
        if ($data['catalog_id'] != '') {
            $map['pc.id'] = $data['catalog_id'];
        }
        // 判断权限，商户各自查看自己的门店
        if ($this->check_is_admin($this->node_id) === false) {
            $map['pm.id'] = $this->merchant_id;
        }
        $map['m.batch_type'] = $this->BATCH_TYPE_COUPON;
        if (I("down_type") == "down") {
            $this->coupon_Download($map);
        }
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = M()->table("tmarketing_info m")->join('tbatch_info b on b.m_id=m.id')
            ->join('tgoods_info g on g.goods_id=b.goods_id')
            ->join('tfb_yhb_goods pg on pg.goods_id=g.goods_id')
            ->join('tgroup_pos_relation reg on reg.group_id=g.pos_group')
            ->join('tstore_info stb on stb.store_id=reg.store_id')
            ->join('tfb_yhb_store strb on strb.store_id=stb.store_id')
            ->join(
            'tfb_yhb_city_code stra on stra.street_code=strb.street_code')
            ->join('tfb_yhb_mconfig pmc on pmc.mid=m.id')
            ->join('tfb_yhb_node_info pm on pm.id=pg.merchant_id')
            ->join('tfb_yhb_catalog pc on pc.id=pm.catalog_id')
            ->join('tfb_yhb_catalog plc on plc.parent_id=pm.parent_id')
            ->where($map)
            ->count('distinct(g.goods_id)'); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        foreach ($data as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        $show = $Page->show(); // 分页显示输出
        $list = M()->table("tmarketing_info m")->field(
            'm.*,g.storage_num,g.remain_num,g.goods_name,g.goods_id,g.storage_type,g.status as gstatus,pm.id as merchant_id,pm.merchant_short_name,pm.status as merchant_status,pc.catalog_name,pm.city_code as org_code,pmc.shelf_status,pmc.last_apply_time,pg.goods_id as goods_id,pg.order_sort,pg.line_status,pm.catalog_id,pm.parent_id')
            ->join('tbatch_info b on b.m_id=m.id')
            ->join('tgoods_info g on g.goods_id=b.goods_id')
            ->join('tgroup_pos_relation reg on reg.group_id=g.pos_group')
            ->join('tstore_info stb on stb.store_id=reg.store_id')
            ->join('tfb_yhb_store strb on strb.store_id=stb.store_id')
            ->join(
            'tfb_yhb_city_code stra on stra.street_code=strb.street_code')
            ->join('tfb_yhb_goods pg on pg.goods_id=g.goods_id')
            ->join('tfb_yhb_node_info pm on pm.id=pg.merchant_id')
            ->join('tfb_yhb_catalog pc on pc.id=pm.catalog_id')
            ->join('tfb_yhb_catalog plc on plc.parent_id=pm.parent_id')
            ->join('tfb_yhb_mconfig pmc on pmc.mid=m.id')
            ->where($map)
            ->group("g.goods_id")
            ->order('m.add_time desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        $channelModel = M('tchannel');
        $channelId = $channelModel->where(
            array(
                "node_id" => $this->nodeId, 
                "type" => $this->CHANNEL_TYPE, 
                "sns_type" => $this->CHANNEL_SNS_TYPE, 
                "status" => 1))->getField('id');
        if (! $channelId) {
            $data = array(
                'name' => '翼惠宝默认渠道', 
                'type' => $this->CHANNEL_TYPE, 
                'sns_type' => $this->CHANNEL_SNS_TYPE, 
                'status' => '1', 
                'node_id' => $this->node_id, 
                'add_time' => date('YmdHis'));
            $channelId = $channelModel->add($data);
            if (! $channelId)
                $this->error('系统出错创建渠道失败');
        }
        
        $cate_arr = M('tfb_yhb_cate')->field('id,name')->getField('id,name');
        $city_arr = M('tcity_code')->where(
            array(
                'path' => array(
                    'in', 
                    $this->pn_in_org), 
                'city_level' => '2'))->getField('path,city');
        $status = array(
            '0' => '正常', 
            '1' => '停用', 
            '2' => '过期');
        $shelfStatus = array(
            '1' => '待上架', 
            '2' => '停用', 
            '3' => '已上架');
        $delivery = array(
            '1' => '到店领取', 
            '2' => '物流配送');
        // 商户分类
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
        $this->assign('status', $status);
        $this->assign('shelfStatus', $shelfStatus);
        $this->assign('delivery', $delivery);
        $this->assign('city_arr', $city_arr);
        $this->assign('cate_arr', $cate_arr);
        $this->assign('list', $list);
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('post', $data);
        $this->assign('online', $online);
        $this->display("Coupon/Yhb_coupon");
    }
    // 优惠展示区
    public function couponlist() {
        $online = I('online', null);
        $map = array(
            'm.node_id' => $this->node_id);
        if ($online == '1')
            $map['pmc.shelf_status'] = '1';
        
        $data = $_REQUEST;
        if ($data['key'] != '') {
            $map['m.name'] = array(
                'like', 
                '%' . $data['key'] . '%');
        }
        if ($data['status'] != '') {
            $map['pg.line_status'] = $data['status'];
        } else {
            $map['pg.line_status'] = '3';
        }
        if ($data['merchant_short_name'] != '') {
            $map['pm.merchant_short_name'] = array(
                'like', 
                '%' . $data['merchant_short_name'] . '%');
        }
        if ($data['cate_id'] != '') {
            $map['pm.cate_id'] = $data['cate_id'];
        }
        if ($data['org_code'] != '') {
            $map['pm.city_code'] = $data['org_code'];
        }
        if ($data['town_code'] != '') {
            $map['stra.town_code'] = $data['town_code'];
        }
        // 街道
        if ($data['street_code'] != '') {
            $map['strb.street_code'] = $data['street_code'];
        }
        // 小区
        if ($data['village_code'] != '') {
            $map['strb.village_code'] = $data['village_code'];
        }
        $map['stra.city_level'] = 1;
        
        if ($data['parent_id'] != '') {
            $map['plc.parent_id'] = $data['parent_id'];
        }
        if ($data['catalog_id'] != '') {
            $map['pc.id'] = $data['catalog_id'];
        }
        // 判断权限，商户各自查看自己的门店
        if ($this->check_is_admin($this->node_id) === false) {
            $map['pm.id'] = $this->merchant_id;
        }
        if ($data['order_sort'] != '') {
            if ($data['order_sort'] == 1) {
                $order = "pg.order_sort asc, m.add_time desc";
            } else {
                $order = "pg.order_sort desc, m.add_time desc";
            }
        } else {
            $order = "m.add_time desc";
        }
        $map['m.batch_type'] = $this->BATCH_TYPE_COUPON;
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = M()->table("tmarketing_info m")->join('tbatch_info b on b.m_id=m.id')
            ->join('tgoods_info g on g.goods_id=b.goods_id')
            ->join('tfb_yhb_goods pg on pg.goods_id=g.goods_id')
            ->join('tgroup_pos_relation reg on reg.group_id=g.pos_group')
            ->join('tstore_info stb on stb.store_id=reg.store_id')
            ->join('tfb_yhb_store strb on strb.store_id=stb.store_id')
            ->join(
            'tfb_yhb_city_code stra on stra.street_code=strb.street_code')
            ->join('tfb_yhb_mconfig pmc on pmc.mid=m.id')
            ->join('tfb_yhb_node_info pm on pm.id=pg.merchant_id')
            ->join('tfb_yhb_catalog pc on pc.id=pm.catalog_id')
            ->join('tfb_yhb_catalog plc on plc.parent_id=pm.parent_id')
            ->where($map)
            ->count('distinct(g.goods_id)'); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        foreach ($data as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        $show = $Page->show(); // 分页显示输出
        $list = M()->table("tmarketing_info m")->field(
            'm.*,b.storage_num,b.remain_num,g.goods_name,g.goods_id,g.storage_type,g.status as gstatus,pm.id as merchant_id,pm.merchant_short_name,pm.status as merchant_status,pc.catalog_name,pm.city_code as org_code,pmc.shelf_status,pmc.last_apply_time,pg.goods_id as goods_id,pg.order_sort,pg.line_status,pm.catalog_id,pm.parent_id')
            ->join('tbatch_info b on b.m_id=m.id')
            ->join('tgoods_info g on g.goods_id=b.goods_id')
            ->join('tgroup_pos_relation reg on reg.group_id=g.pos_group')
            ->join('tstore_info stb on stb.store_id=reg.store_id')
            ->join('tfb_yhb_store strb on strb.store_id=stb.store_id')
            ->join(
            'tfb_yhb_city_code stra on stra.street_code=strb.street_code')
            ->join('tfb_yhb_goods pg on pg.goods_id=g.goods_id')
            ->join('tfb_yhb_node_info pm on pm.id=pg.merchant_id')
            ->join('tfb_yhb_catalog pc on pc.id=pm.catalog_id')
            ->join('tfb_yhb_catalog plc on plc.parent_id=pm.parent_id')
            ->join('tfb_yhb_mconfig pmc on pmc.mid=m.id')
            ->where($map)
            ->group("g.goods_id")
            ->order($order)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        $channelModel = M('tchannel');
        $channelId = $channelModel->where(
            array(
                "node_id" => $this->nodeId, 
                "type" => $this->CHANNEL_TYPE, 
                "sns_type" => $this->CHANNEL_SNS_TYPE, 
                "status" => 1))->getField('id');
        if (! $channelId) {
            $data = array(
                'name' => '翼惠宝默认渠道', 
                'type' => $this->CHANNEL_TYPE, 
                'sns_type' => $this->CHANNEL_SNS_TYPE, 
                'status' => '1', 
                'node_id' => $this->node_id, 
                'add_time' => date('YmdHis'));
            $channelId = $channelModel->add($data);
            if (! $channelId)
                $this->error('系统出错创建渠道失败');
        }
        
        $cate_arr = M('tfb_yhb_cate')->field('id,name')->getField('id,name');
        $city_arr = M('tcity_code')->where(
            array(
                'path' => array(
                    'in', 
                    $this->pn_in_org), 
                'city_level' => '2'))->getField('path,city');
        $status = array(
            '0' => '正常', 
            '1' => '停用', 
            '2' => '过期');
        $shelfStatus = array(
            '1' => '正常展示', 
            '2' => '下架', 
            '3' => '已上架');
        $delivery = array(
            '1' => '到店领取', 
            '2' => '物流配送');
        // 商户分类
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
        $this->assign('status', $status);
        $this->assign('shelfStatus', $shelfStatus);
        $this->assign('delivery', $delivery);
        $this->assign('city_arr', $city_arr);
        $this->assign('cate_arr', $cate_arr);
        $this->assign('list', $list);
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('post', $data);
        $this->assign('online', $online);
        $this->display("Coupon/Yhb_couponlist");
    }

    /**
     * 优惠券添加
     */
    public function coupon_add() {
        // 获取所属片区的商户信息
        $merchant_list = M('tfb_yhb_node_info')->where(
            array(
                'id' => $this->merchant_id))->getField('id,merchant_name');
        if ($this->check_is_admin($this->node_id) == true) {
            $this->error("对不起，您没有权限操作");
        }
        if (IS_POST) {
            $error = '';
            // 数据校验
            $rules = array(
                'name' => array(
                    'null' => false, 
                    'name' => '优惠名称'), 
                'start_time' => array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd', 
                    'name' => '优惠开始时间'), 
                'end_time' => array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd', 
                    'name' => '优惠结束时间'), 
                'verify_time_type' => array(
                    'null' => false, 
                    'name' => '商品使用时间类型', 
                    'inarr' => array(
                        '0', 
                        '1')),  // 商品使用时间类型
                'merchant_id' => array(
                    'null' => false, 
                    'name' => '商户名称'), 
                'goods_num' => array(
                    'null' => false, 
                    'strtype' => 'number', 
                    'name' => '优惠总数'), 
                'goods_img' => array(
                    'null' => false, 
                    'name' => '商品图片'), 
                'goods_desc' => array(
                    'null' => false, 
                    'maxlen_cn' => '200', 
                    'name' => '优惠简述'), 
                'goods_info' => array(
                    'null' => false, 
                    'maxlen_cn' => '10000', 
                    'name' => '优惠详情'));
            // 'mms_title' => array('null' => false, 'maxlen_cn' => '10', 'name'
            // => '电子凭证彩信标题'),
            // 'mms_info' => array('null' => false, 'maxlen_cn' => '100', 'name'
            // => '电子凭证彩信内容'),
            // 'print_text' => array('null' => false, 'maxlen_cn' => '100',
            // 'name' => '打印小票内容'),
            
            if (I('verify_time_type', 0, 'intval') == '0') {
                // 创建优惠结束时间不能小于或者超过优惠有效期时间
                if (I('verify_end_date') < I('start_time')) {
                    $this->error("验证结束的时间不能小于优惠开始时间");
                }
                if (I('verify_end_date') < I('end_time')) {
                    $this->error("验证结束的时间不能小于优惠结束时间");
                }
                $rules['verify_begin_date'] = array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd', 
                    'name' => '验证开始时间');
                $rules['verify_end_date'] = array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd', 
                    'name' => '验证结束时间');
            } else {
                if (I('verify_end_days') < I('verify_begin_days')) {
                    $this->error("验证结束天数不能小于验证开始天数!");
                }
                $rules['verify_begin_days'] = array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'minval' => '0', 
                    'name' => '验证开始天数');
                $rules['verify_end_days'] = array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'minval' => '0', 
                    'name' => '验证结束天数');
            }
            $reqData = $this->_verifyReqData($rules);
            // 获取门店号
            $storelist = implode(',', I('storeid'));
            if (! $storelist)
                $this->error('可用门店不得为空');
                
                // 获取商户信息和所属商户信息
            $nodeInfo = M('tnode_info')->where(
                array(
                    "node_id" => $this->node_id))->find();
            $merchantInfo = M("tfb_yhb_node_info")->where(
                array(
                    'id' => $reqData['merchant_id']))->find();
            $reqData['pa_org_code'] = $merchantInfo['city_code'];
            // 优惠默认下发凭证
            $reqData['delivery_flag'] = '1';
            M()->startTrans();
            try {
                // 添加申请表
                $result = $this->_addApply($reqData, $storelist, '0');
                if ($result['code'] != '0000')
                    throw new Exception($result['err_msg']);
                
                $map = array(
                    'id' => $result['apply_id']);
                $info = M('tfb_yhb_apply')->where($map)->find();
                if (! $info)
                    $this->error('数据不存在');
                    // 审核通过 则添加至tgoods_info tbatch_info tmarketing_info以及发布渠道
                    // 优惠券
                $goods_type = $this->GOODS_TYPE_COUPON;
                $batch_type = $this->BATCH_TYPE_COUPON;
                // 获取商户信息和所属商户信息
                $nodeInfo = M('tnode_info')->where(
                    array(
                        "node_id" => $this->node_id))->find();
                $merchantInfo = M('tfb_yhb_node_info')->where(
                    array(
                        'id' => $info['merchant_id']))->find();
                $yhb_goods_arr = $result = $this->_addGoodsInfo($info, 
                    $nodeInfo, $merchantInfo, $goods_type);
                if ($result['code'] != '0000')
                    throw new Exception($result['err_msg']);
                else {
                    $goods_id = $result['goods_id'];
                    $iss_batch_no = $result['iss_batch_no'];
                }
                // 添加营销活动
                $result = $this->_addMarketingInfo($info, $nodeInfo, 
                    $merchantInfo, $batch_type);
                if ($result['code'] != '0000')
                    throw new Exception($result['err_msg']);
                else
                    $m_id = $result['m_id'];
                    
                    // 添加营销品
                $result = $this->_addBatchInfo($info, $nodeInfo, $m_id, 
                    $goods_id, $iss_batch_no, $goods_type);
                if ($result['code'] != '0000')
                    throw new Exception($result['err_msg']);
                else
                    $b_id = $result['b_id'];
                    // 设置抽奖
                $result = $this->_setCj($m_id, $batch_type, $iss_batch_no, 
                    $info['goods_num'], $b_id, $goods_id);
                if ($result['code'] != '0000')
                    throw new Exception($result['err_msg']);
                    
                    // 发布至平安默认渠道
                $channelId = M('tchannel')->where(
                    array(
                        "node_id" => $this->node_id, 
                        "type" => $this->CHANNEL_TYPE, 
                        "sns_type" => $this->CHANNEL_SNS_TYPE, 
                        "status" => "1"))->getField('id');
                $data = array(
                    'batch_type' => $batch_type, 
                    'batch_id' => $m_id, 
                    'channel_id' => $channelId, 
                    'add_time' => date('YmdHis'), 
                    'node_id' => $this->node_id);
                $result = M('tbatch_channel')->add($data);
                if ($result === false)
                    throw new Exception("发布默认渠道失败");
                $goods_res = M("tfb_yhb_goods")->where(
                    array(
                        'id' => $yhb_goods_arr['yhb_id']))->save(
                    array(
                        'label_id' => $result));
                if ($goods_res === false)
                    throw new Exception("更新到goods扩展表失败");
            } catch (Exception $e) {
                M()->rollback();
                $this->error($e->getMessage());
            }
            M()->commit();
            $this->success('新建优惠成功！', 
                array(
                    '返回优惠列表' => U('goods_apply', 
                        array(
                            'apply_type' => '0'))));
        }
        
        $queryList = M('tstore_info')->where(
            array(
                "yhb_flage" => $this->merchant_id))->select();
        foreach ($queryList as $v) {
            $str .= '<input type="checkbox" name="storeid[]" value="' .
                 $v['store_id'] . '"><p class="ml5 choosetext2">' .
                 $v['store_name'] . '</p>';
        }
        $this->assign('str', $str);
        $this->assign('merchant_list', $merchant_list);
        $this->display("Coupon/Yhb_coupon_add");
    }

    // 图片移动
    public function moveImage($imageName) {
        // 使用新的上传控件
        return true;
        $oldImagePath = $this->tempImagePath;
        $newImagePath = $this->numGoodsImagePath;
        // 图片是否存在
        if (! is_file($oldImagePath . '/' . $imageName)) { // 是否存在此文件
            $this->error('卡券图片未找到');
        }
        if (! is_dir($newImagePath)) { // 是否存在此目录
            if (! mkdir($newImagePath, 0777))
                $this->error('目录创建失败');
        }
        $flag = copy($oldImagePath . '/' . $imageName, 
            $newImagePath . '/' . $imageName);
        if (! $flag)
            $this->error('图片移动失败');
        return true;
    }

    /**
     * 优惠编辑 不展示的才可以编辑
     */
    public function coupon_edit() {
        if ($this->check_is_admin($this->node_id) == true) {
            $this->error("对不起，您没有权限操作");
        }
        $id = I('id', null);
        if (! $id)
            $this->error('数据错误');
        $info = M()->table("tmarketing_info m")->field(
            'm.id,m.start_time,m.end_time,m.name,m.defined_one_name,m.memo,b.storage_num,b.id as bbatch_id,b.remain_num,b.info_title,b.use_rule,g.goods_id,g.goods_image,g.goods_name,g.goods_image,g.pos_group,g.batch_no,g.goods_amt,g.add_time,g.p_goods_id,g.market_price,g.goods_desc,g.print_text,g.verify_begin_date,g.verify_end_date,g.verify_begin_type,g.verify_end_type,pm.merchant_name,pm.city_code as org_code,pmc.shelf_status')
            ->join('tbatch_info b on b.m_id=m.id')
            ->join('tgoods_info g on g.goods_id=b.goods_id')
            ->join('tfb_yhb_goods pg on pg.goods_id=g.goods_id')
            ->join('tfb_yhb_node_info pm on pm.id=pg.merchant_id')
            ->join('tfb_yhb_mconfig pmc on pmc.mid=m.id')
            ->where(array(
            'm.id' => $id))
            ->find();
        if (! $info)
            $this->error('数据不存在');
        if ($info['shelf_status'] == '1')
            $this->error('优惠已上架，无法编辑');
            // 编辑提交
        if (IS_POST) {
            $newstore_array = I('post.');
            $where = array(
                's.store_id' => array(
                    'in', 
                    $newstore_array['store_id']), 
                's.node_id' => array(
                    'exp', 
                    "in ({$this->nodeIn()})"));
            // 's.pos_range' => array('gt','0')

            $posData = M()->table("tstore_info s")->field(
                'p.pos_id,p.store_id,p.node_id')
                ->join(
                'tpos_info p ON s.store_id=p.store_id AND s.node_id=p.node_id')
                ->where($where)
                ->select();
            $error = '';
            // 数据校验
            $rules = array(
                'name' => array(
                    'null' => false, 
                    'name' => '商品名称'), 
                'start_time' => array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd', 
                    'name' => '商品开始时间'), 
                'end_time' => array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd', 
                    'name' => '商品结束时间'), 
                'verify_time_type' => array(
                    'null' => false, 
                    'name' => '商品使用时间类型', 
                    'inarr' => array(
                        '0', 
                        '1')),  // 商品使用时间类型
                               // 'delivery_flag' =>
                               // array('null'=>false,'name'=>'配送方式',
                               // 'inarr'=>array('1','2')), //1 到店领取 2 物流配送
                               // 'batch_price' =>
                               // array('null'=>false,'strtype'=>'number',
                               // 'name'=>'平安币'),
                               // 'market_price' =>
                               // array('null'=>false,'strtype'=>'number',
                               // 'name'=>'市场价'),
                'goods_num' => array(
                    'null' => false, 
                    'strtype' => 'number', 
                    'name' => '商品总数'), 
                'goods_img' => array(
                    'null' => false, 
                    'name' => '商品图片'), 
                'goods_desc' => array(
                    'null' => false, 
                    'maxlen_cn' => '200', 
                    'name' => '商品简述'), 
                'goods_info' => array(
                    'null' => false, 
                    'maxlen_cn' => '10000', 
                    'name' => '商品详情'));
            // 'mms_title' => array('null' => false, 'maxlen_cn' => '10', 'name'
            // => '电子凭证彩信标题'),
            // 'mms_info' => array('null' => false, 'maxlen_cn' => '100', 'name'
            // => '电子凭证彩信内容'),
            // 'print_text' => array('null' => false, 'maxlen_cn' => '100',
            // 'name' => '打印小票内容'),
            
            if (I('verify_time_type', 0, 'intval') == '0') {
                // 创建优惠结束时间不能小于或者超过优惠有效期时间
                if (I('verify_end_date') < I('start_time')) {
                    $this->error("验证结束的时间不能小于优惠开始时间");
                }
                if (I('verify_end_date') < I('end_time')) {
                    $this->error("验证结束时间不能小于活动结束时间!");
                }
                $rules['verify_begin_date'] = array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd', 
                    'name' => '验证开始时间');
                $rules['verify_end_date'] = array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd', 
                    'name' => '验证结束时间');
            } else {
                if (I('verify_end_days') < I('verify_begin_days')) {
                    $this->error("验证结束天数不能小于验证开始天数!");
                }
                $rules['verify_begin_days'] = array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'minval' => '0', 
                    'name' => '验证开始天数');
                $rules['verify_end_days'] = array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'minval' => '0', 
                    'name' => '验证结束天数');
            }
            $reqData = $this->_verifyReqData($rules);
            if ($reqData['goods_num'] <
                 ($info['storage_num'] - $info['remain_num']))
                $this->error('库存必须大于已兑换数量' . $reqData['goods_num']);
                // 移动图片
            M()->startTrans();
            try {
                // tgoods_info
                $gdata = array(
                    'goods_name' => $reqData['name'], 
                    'goods_desc' => $reqData['goods_desc'], 
                    'goods_image' => $reqData['goods_img'], 
                    'market_price' => $reqData['market_price'], 
                    'goods_amt' => $reqData['batch_price'], 
                    // 'mms_title' => $reqData['mms_title'],
                    // 'mms_text' => $reqData['mms_info'],
                    // 'sms_text' => $reqData['mms_info'],
                    // 'print_text' => $reqData['print_text'],
                    'begin_time' => $reqData['start_time'] . '000000', 
                    'end_time' => $reqData['end_time'] . '235959', 
                    // 'verify_begin_date' => $reqData['start_time'].'000000',
                    // 'verify_end_date' => $reqData['end_time'].'235959',
                    'update_time' => date('YmdHis'));
                if ($reqData['verify_time_type'] == '0') {
                    $gdata['verify_begin_date'] = $reqData['verify_begin_date'] .
                         '000000';
                    $gdata['verify_end_date'] = $reqData['verify_end_date'] .
                         '235959';
                    $gdata['verify_begin_type'] = $reqData['verify_time_type'];
                    $gdata['verify_end_type'] = $reqData['verify_time_type'];
                } elseif ($reqData['verify_time_type'] == '1') {
                    $gdata['verify_begin_date'] = $reqData['verify_begin_days'];
                    $gdata['verify_end_date'] = $reqData['verify_end_days'];
                    $gdata['verify_begin_type'] = $reqData['verify_time_type'];
                    $gdata['verify_end_type'] = $reqData['verify_time_type'];
                }
                // tbatch_info
                $remain_num = $reqData['goods_num'] -
                     ($info['storage_num'] - $info['remain_num']);
                $bdata = array(
                    'batch_short_name' => $reqData['name'], 
                    'batch_name' => $reqData['name'], 
                    // 'info_title' => $reqData['mms_title'],
                    // 'use_rule' => $reqData['mms_info'],
                    // 'sms_text' => $reqData['mms_info'],
                    'batch_img' => $reqData['goods_img'], 
                    'batch_amt' => $reqData['batch_price'],  // 平安币
                    'begin_time' => $reqData['start_time'] . '000000', 
                    'end_time' => $reqData['end_time'] . '235959', 
                    // 'verify_begin_date' => $reqData['start_time'].'000000',
                    // 'verify_end_date' => $reqData['end_time'].'235959',
                    'update_time' => date('YmdHis'), 
                    'storage_num' => $reqData['goods_num'], 
                    'remain_num' => $remain_num, 
                    'batch_desc' => $reqData['goods_desc']);
                if ($reqData['verify_time_type'] == '0') {
                    $bdata['verify_begin_date'] = $reqData['verify_begin_date'] .
                         '000000';
                    $bdata['verify_end_date'] = $reqData['verify_end_date'] .
                         '235959';
                    $bdata['verify_begin_type'] = $reqData['verify_time_type'];
                    $bdata['verify_end_type'] = $reqData['verify_time_type'];
                } elseif ($reqData['verify_time_type'] == '1') {
                    $bdata['verify_begin_date'] = $reqData['verify_begin_days'];
                    $bdata['verify_end_date'] = $reqData['verify_end_days'];
                    $bdata['verify_begin_type'] = $reqData['verify_time_type'];
                    $bdata['verify_end_type'] = $reqData['verify_time_type'];
                }
                $result = M('tbatch_info')->where(
                    array(
                        'm_id' => $info['id']))->save($bdata);
                if ($result === false)
                    throw new Exception("更新发送参数失败");
                    
                    // tmarketing_info
                $mdata = array(
                    'name' => $reqData['name'], 
                    'start_time' => $reqData['start_time'] . '000000', 
                    'end_time' => $reqData['end_time'] . '235959', 
                    'market_price' => $reqData['market_price'], 
                    'group_price' => $reqData['batch_price'], 
                    'memo' => $reqData['goods_info'], 
                    'defined_one_name' => $reqData['delivery_flag']); // 1 到店领取
                                                                      // 2 物流配送
                
                $result = M('tmarketing_info')->where(
                    array(
                        'id' => $info['id']))->save($mdata);
                if ($result === false)
                    throw new Exception("更新活动失败");
                    // 更新tcj_batch里面的day_count和total_count
                $day_data = array(
                    'day_count' => $reqData['goods_num'], 
                    'total_count' => $reqData['goods_num'], 
                    'award_rate' => $reqData['goods_num']);
                $map_where = array(
                    "node_id" => $this->node_id, 
                    'goods_id' => $info['goods_id'], 
                    'b_id' => $info['bbatch_id'], 
                    'batch_type' => '2000');
                $result = M("tcj_batch")->where($map_where)->save($day_data);
                if ($result === false)
                    throw new Exception("更新活动失败");
                    // 新增门店逻辑
                    // //获取有效的门店和过滤非法$shopList
                $newStoreArr = array();
                foreach ($posData as $v) {
                    $newStoreArr[] = $v['store_id'];
                }
                $newStoreArr = array_unique($newStoreArr);
                $oldStoreArr = array();
                $storeData = M('tgroup_pos_relation')->field('store_id')
                    ->where("group_id={$info['pos_group']}")
                    ->select();
                foreach ($storeData as $v) {
                    $oldStoreArr[] = $v['store_id'];
                }
                // 图片处理
                $smilId = null;
                if ($reqData['goods_img'] != $info['goods_image']) {
                    // 卡券图片移动
                    $this->moveImage($reqData['goods_img']);
                    $smilId = $this->getSmil($reqData['goods_img'], 
                        $info['goods_name']);
                    if (! $smilId)
                        $this->error('创建失败:smilid获取失败');
                }
                $goodsModel = D('Goods');
                $oldStoreArr = array_unique($oldStoreArr);
                $arrayDiff = array_diff($newStoreArr, $oldStoreArr);
                if (count($newStoreArr) != count($oldStoreArr) ||
                     ! empty($arrayDiff)) {
                    // 删除合约，创建电子合约
                    $groupId = $goodsModel->zcModifyStore($this->nodeId, 
                        $info['p_goods_id'], '2', implode(',', $newStoreArr));
                    if (! $groupId) {
                        $goodsModel->rollback();
                        $this->error($goodsModel->getError());
                    }
                    $num = M('tpos_group')->where(
                        "group_id='{$groupId}' AND node_id='{$this->nodeId}'")->count();
                    if ($num != '0') { // 删除旧合约
                        $result = M('tpos_group')->where(
                            "group_id='{$groupId}' AND node_id={$this->nodeId}")->delete();
                        if ($result === false) {
                            $goodsModel->rollback();
                            $this->error('数据出错,旧合约删除失败01');
                        }
                        $result = M('tgroup_pos_relation')->where(
                            "group_id='{$groupId}' AND node_id={$this->nodeId}")->delete();
                        if ($result === false) {
                            $goodsModel->rollback();
                            $this->error('数据出错,旧合约删除失败02');
                        }
                    }
                    // 创建新合约
                    $groupData = array( // tpos_group
                        'node_id' => $this->nodeId, 
                        'group_id' => $groupId, 
                        'group_name' => $this->nodeId . '终端型-终端组', 
                        'group_type' => '1', 
                        'status' => '0');
                    $result = M('tpos_group')->add($groupData);
                    if (! $result) {
                        $goodsModel->rollback();
                        $this->error('终端数据创建失败03');
                    }
                    foreach ($posData as $v) {
                        $data_2 = array(
                            'group_id' => $groupId, 
                            'node_id' => $v['node_id'], 
                            'store_id' => $v['store_id'], 
                            'pos_id' => $v['pos_id']);
                        $result = M('tgroup_pos_relation')->add($data_2);
                        if (! $result) {
                            $goodsModel->rollback();
                            $this->error('终端数据创建失败04');
                        }
                    }
                }
                $result = $goodsModel->zcModifyBatch($this->nodeId, 
                    $info['batch_no'], $reqData['name'], $reqData['name'], 
                    $info['add_time'], '', $smilId);
                if (! $result) {
                    $goodsModel->rollback();
                    $this->error($goodsModel->getError());
                }
                $gdata['pos_group'] = $groupId;
                // $result = M('tgoods_info')->where(array('goods_id' =>
                // $info['goods_id']))->save($gdata);
                // $this->yhb_AddStore($goodsData,$newstore_array,$posData,$rules);
                $result = M('tgoods_info')->where(
                    array(
                        'goods_id' => $info['goods_id']))->save($gdata);
                if ($result === false)
                    throw new Exception("更新营销品失败");
            } catch (Exception $e) {
                M()->rollback();
                $this->error($e->getMessage());
            }
            M()->commit();
            $this->success('编辑成功！', 
                array(
                    '返回商品列表' => U('goods')));
        }
        // 获取可用门店数据
        $queryList = M()->table("tgroup_pos_relation t")->field(
            's.store_name,s.store_id')
            ->join('tstore_info s ON s.store_id=t.store_id')
            ->where(array(
            't.group_id' => $info['pos_group']))
            ->select();
        $yhb_storehas = array();
        foreach ($queryList as $val) {
            $yhb_storehas[] = $val['store_id'];
        }
        $yhballList = M()->table("tstore_info s")->field('s.store_name,s.store_id')
            ->join('tfb_yhb_store y ON y.store_id=s.store_id')
            ->where(
            array(
                'y.merchant_id' => $this->merchant_id))
            ->select();
        $yhb_storearr = array();
        foreach ($yhballList as $val) {
            if (in_array($val['store_id'], $yhb_storehas)) {
                $str .= "<input type='checkbox' name='store_id[]' checked='checked' value='{$val['store_id']}'><p class='ml5 choosetext2'>" .
                     $val['store_name'] . '</p>';
            } else {
                $str .= "<input type='checkbox' name='store_id[]'  value='{$val['store_id']}'><p class='ml5 choosetext2'>" .
                     $val['store_name'] . '</p>';
            }
        }
        
        $delivery = array(
            '1' => '到店领取', 
            '2' => '物流配送');

        $info['goods_desc'] = strip_tags(htmlspecialchars_decode($info['goods_desc']));
        $this->assign('delivery', $delivery);
        // $this->assign('type', $type);
        $this->assign('info', $info);
        $this->assign('str', $str);
        $this->display("Coupon/Yhb_coupon_edit");
    }

    // 获取smil $type 1:爱拍 2:旺财
    public function getSmil($imageName, $name, $smilId = "") {
        $imagePath = realpath(C('UPLOAD'));
        $zipFileName = date('YmdHis') . '.zip';
        import('@.ORG.Net.Zip', '', '.php') or die('导入包失败');
        $test = new zip_file($zipFileName);
        $test->set_options(
            array(
                'basedir' => $imagePath . '/template/', 
                'inmemory' => 0, 
                'recurse' => 1, 
                'storepaths' => 0, 
                'overwrite' => 1, 
                'level' => 5, 
                'name' => $zipFileName));
        $imageUrl = $imagePath . '/' . $imageName;
        if (! is_file($imageUrl)) {
            log_write('获取SmilId接口图片文件不存在' . $imageUrl);
            return false;
        }
        // 缩放图片大小要小于60k
        import('ORG.Util.Image');
        $smilUrl = Image::thumb($imageUrl, 
            dirname($imageUrl) . '/smi_' . basename($imageUrl), '', 150, 150, 
            true);
        if (! $smilUrl) {
            log_write('SmilId接口图片压缩失败');
            return false;
        }
        $imageUrl = $smilUrl;
        $smil_cfg = create_smil_cfg($imageUrl);
        if ($smil_cfg === false) {
            log_write('创建smil_cfg失败');
            return false;
        }
        // $defultSmil = $path.'/template/default.smil';
        // $imagecoImage = $path.'/template/imageco_image_b.jpg';
        $info = pathinfo($imageUrl);
        $ex = $info['extension'];
        $files = array(
            '1.' . $ex => $imageUrl, 
            'default.smil' => realpath($smil_cfg));
        $test->add_files($files);
        $test->create_archive();
        $zipPath = $imagePath . '/template/' . $zipFileName;
        $SmilZip = base64_encode(file_get_contents($zipPath));
        // 通知支撑
        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
                                                                   // 请求参数
        $req_array = array(
            'SmilAddEditReq' => array(
                'ISSPID' => $this->nodeId, 
                'PlatformID' => C('ISS_PLATFORM_ID'), 
                'TransactionID' => $TransactionID, 
                'Username' => C('ISS_SEND_USER'), 
                'Password' => C('ISS_SEND_USER_PASS'), 
                'SmilInfo' => array(
                    'SmilId' => $smilId, 
                    'SmilName' => time(), 
                    'SmilDesc' => $name, 
                    'SmilZip' => $SmilZip)));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $resp_array = $RemoteRequest->smilAddEditReq($req_array);
        @unlink($zipPath);
        @unlink($smil_cfg);
        @unlink($smilUrl);
        $ret_msg = $resp_array['SmilAddEditRes']['Status'];
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
             $ret_msg['StatusCode'] != '0001')) {
            log_write("获取SmilId失败 原因：{$ret_msg['StatusText']}");
            return false;
        }
        return $resp_array['SmilAddEditRes']['SmilId'];
    }

    /**
     * 新增商品/优惠申请表
     */
    public function _addApply($reqData, $storelist, $apply_type) {
        $data = array(
            'node_id' => $this->node_id, 
            'apply_type' => $apply_type, 
            'name' => $reqData['name'], 
            'start_time' => $reqData['start_time'], 
            'end_time' => $reqData['end_time'], 
            'merchant_id' => $reqData['merchant_id'], 
            'store_list' => $storelist, 
            'delivery_flag' => $reqData['delivery_flag'], 
            'batch_price' => $reqData['batch_price'], 
            'market_price' => $reqData['market_price'], 
            'goods_img' => $reqData['goods_img'], 
            'goods_num' => $reqData['goods_num'], 
            'goods_desc' => $reqData['goods_desc'], 
            'goods_info' => $reqData['goods_info'], 
            'mms_title' => $reqData['mms_title'], 
            'mms_info' => $reqData['mms_info'], 
            'print_text' => $reqData['print_text'], 
            'pa_org_code' => $reqData['pa_org_code'], 
            'apply_time' => date('YmdHis'), 
            'status' => '0');
        if ($reqData['verify_time_type'] == '0') {
            $data['verify_begin_date'] = $reqData['verify_begin_date'] . '000000';
            $data['verify_end_date'] = $reqData['verify_end_date'] . '235959';
            $data['verify_begin_type'] = $reqData['verify_time_type'];
            $data['verify_end_type'] = $reqData['verify_time_type'];
        } elseif ($reqData['verify_time_type'] == '1') {
            $data['verify_begin_date'] = $reqData['verify_begin_days'];
            $data['verify_end_date'] = $reqData['verify_end_days'];
            $data['verify_begin_type'] = $reqData['verify_time_type'];
            $data['verify_end_type'] = $reqData['verify_time_type'];
        }
        $result = M('tfb_yhb_apply')->add($data);
        if ($result === false) {
            $this->error("优惠申请保存失败！");
        }
        // $this->goods_applyCheck($result);
        // 优惠申请保存成功，创建营销活动，渠道
        if (! $result) {
            return array(
                'code' => '0001', 
                'err_msg' => '系统出错,添加申请信息出错');
        }
        return array(
            'code' => '0000', 
            'err_msg' => '新建成功', 
            'apply_id' => $result);
    }

    /**
     * 新增营销品基础信息
     */
    public function _addGoodsInfo($reqData, $nodeInfo, $merchantInfo, 
        $goods_type) {
        // 支撑创建终端组
        $res = M('tnode_info')->where(
            array(
                'node_id' => $this->node_id))->setInc('posgroup_seq'); // posgroup_seq
                                                                       // +1;
        if ($res === false) {
            log::write("修改tnode_info里面的值错误");
        }
        $posList = M('tpos_info')->field('pos_id,store_id')
            ->where(
            array(
                'store_id' => array(
                    'in', 
                    $reqData['store_list'])))
            ->select();
        $dataList = implode(',', array_valtokey($posList, '', 'pos_id'));
        $req_array = array(
            'CreatePosGroupReq' => array(
                'NodeId' => $this->node_id, 
                'GroupType' => '1',  // 0 全部门店 1子门店
                'GroupName' => str_pad($nodeInfo['client_id'], 6, '0', 
                    STR_PAD_LEFT) . $nodeInfo['posgroup_seq'], 
                'GroupDesc' => '', 
                'DataList' => $dataList));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $resp_array = $RemoteRequest->requestIssForImageco($req_array);
        $ret_msg = $resp_array['CreatePosGroupRes']['Status'];
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
             $ret_msg['StatusCode'] != '0001')) {
            log::write("创建终端组失败，原因：{$ret_msg['StatusText']}");
            // $this->error('创建门店失败:'.$ret_msg['StatusText']);
            return array(
                'code' => '0001', 
                'err_msg' => '创建门店失败:' . $ret_msg['StatusText'], 
                'goods_id' => '');
        }
        $groupId = $resp_array['CreatePosGroupRes']['GroupID'];
        
        // 插入终端组信息
        $num = M('tpos_group')->where(
            array(
                'group_id' => $groupId))->count();
        if ($num == '0') { // 不存在终端组去创建
            $data = array( // tpos_group
                'node_id' => $this->node_id, 
                'group_id' => $groupId, 
                'group_name' => $req_array['CreatePosGroupReq']['GroupName'], 
                'group_type' => '1', 
                'status' => '0');
            $result = M('tpos_group')->add($data);
            if (! $result) {
                // $this->error('终端数据创建失败');
                return array(
                    'code' => '0002', 
                    'err_msg' => '终端数据创建失败', 
                    'goods_id' => '');
            }
            foreach ($posList as $v) {
                $data = array(
                    'group_id' => $groupId, 
                    'node_id' => $this->node_id, 
                    'store_id' => $v['store_id'], 
                    'pos_id' => $v['pos_id']);
                $result = M('tgroup_pos_relation')->add($data);
                if (! $result) {
                    // $this->error('终端数据创建失败');
                    return array(
                        'code' => '0003', 
                        'err_msg' => '终端关联数据创建失败', 
                        'goods_id' => '');
                }
            }
        }
        $goods_id = get_goods_id();
        
        // 创建tgoods_info数据
        $goods_data = array(
            'goods_id' => $goods_id, 
            'goods_name' => $reqData['name'], 
            'goods_desc' => $reqData['goods_desc'], 
            'goods_image' => $reqData['goods_img'], 
            'node_id' => $this->nodeId, 
            'user_id' => $this->userId, 
            'pos_group' => $groupId, 
            'pos_group_type' => '2',  // 1全部门店 2 子门店
            'goods_type' => $goods_type, 
            'market_price' => $reqData['market_price'], 
            'goods_amt' => $reqData['batch_price'], 
            'storage_type' => 1, 
            'storage_num' => $reqData['goods_num'], 
            'remain_num' => $reqData['goods_num'], 
            'mms_title' => $reqData['mms_title'], 
            'mms_text' => $reqData['mms_info'], 
            'sms_text' => $reqData['mms_info'], 
            'print_text' => $reqData['print_text'], 
            'validate_times' => 1, 
            'begin_time' => $reqData['start_time'] . '000000', 
            'end_time' => $reqData['end_time'] . '235959', 
            // 'send_begin_date' => $startDate.'000000',
            // 'send_end_date' => $endDate.'235959',
            'verify_begin_date' => $reqData['verify_begin_date'], 
            'verify_end_date' => $reqData['verify_end_date'], 
            'verify_begin_type' => $reqData['verify_begin_type'], 
            'verify_end_type' => $reqData['verify_end_type'], 
            'add_time' => date('YmdHis'), 
            'status' => '0', 
            'source' => '0');
        
        // 平安营销品关联表
        $pa_data = array(
            'goods_id' => $goods_id, 
            'merchant_id' => $merchantInfo['id'], 
            'org_code' => $this->pa_org_code, 
            'order_sort' => '((select max(order_sort) from tfb_yhb_goods)+1)', 
            'line_status' => 1, 
            'node_id' => $this->node_id);
        $ret = M('tfb_yhb_goods')->add($pa_data);
        if ($ret === false) {
            return array(
                'code' => '0005', 
                'err_msg' => '系统出错,新建商品关联数据失败', 
                'goods_id' => $goods_id, 
                'yhb_id' => $ret);
        }
        
        // 支撑创建活动
        $req_array = array(
            'ActivityCreateReq' => array(
                'SystemID' => C('ISS_SYSTEM_ID'), 
                'ISSPID' => $this->node_id, 
                'RelationID' => $this->node_id, 
                'TransactionID' => date("YmdHis") . mt_rand(100000, 999999),  // 请求单号
                'ActivityInfo' => array(
                    'CustomNo' => '', 
                    'ActivityName' => iconv("utf-8", "gbk", $reqData['name']), 
                    'ActivityShortName' => iconv("utf-8", "gbk", 
                        $reqData['name']), 
                    'BeginTime' => date('Ymd') . '000000', 
                    'EndTime' => '20301231235959', 
                    'UseRangeID' => $groupId), 
                'VerifyMode' => array(
                    'UseTimesLimit' => 1, 
                    'UseAmtLimit' => 0), 
                'GoodsInfo' => array(
                    'GoodsName' => iconv("utf-8", "gbk", $reqData['name']), 
                    'GoodsShortName' => iconv("utf-8", "gbk", $reqData['name'])), 
                'DefaultParam' => array(
                    'PasswordTryTimes' => 3, 
                    'PasswordType' => '')));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $resp_array = $RemoteRequest->requestIssForImageco($req_array);
        $ret_msg = $resp_array['ActivityCreateRes']['Status'];
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
             $ret_msg['StatusCode'] != '0001')) {
            // $this->error("创建失败:{$ret_msg['StatusText']}",array('返回'=>"javascript:history.go(-1)"));
            return array(
                'code' => '0006', 
                'err_msg' => '创建失败:' . $ret_msg['StatusText'], 
                'goods_id' => '');
        }
        $goods_data['p_goods_id'] = $resp_array['ActivityCreateRes']['Info']['pGoodsId'];
        $goodsId = M('tgoods_info')->data($goods_data)->add();
        if (! $goodsId) {
            // $this->error('系统出错,新建商品失败',array('返回'=>"javascript:history.go(-1)"));
            return array(
                'code' => '0004', 
                'err_msg' => '系统出错,新建商品失败', 
                'goods_id' => '');
        }
        $batchNo = $resp_array['ActivityCreateRes']['Info']['ActivityID'];
        $result = M('tgoods_info')->where("id={$goodsId}")->save(
            array(
                'batch_no' => $batchNo));
        if (! $result) {
            // $this->error('系统出错,更新支撑活动号失败',array('返回'=>"javascript:history.go(-1)"));
            return array(
                'code' => '0007', 
                'err_msg' => '系统出错,更新支撑活动号失败', 
                'goods_id' => '', 
                'iss_batch_no' => '');
        }
        
        return array(
            'code' => '0000', 
            'err_msg' => '新建成功', 
            'goods_id' => $goods_id, 
            'iss_batch_no' => $batchNo, 
            'yhb_id' => $ret);
    }

    /**
     * 新增营销品发送信息
     */
    public function _addBatchInfo($reqData, $nodeInfo, $m_id, $goods_id, 
        $iss_batch_no, $goods_type) {
        // 创建batch_info数据
        $batch_data = array(
            'batch_no' => $iss_batch_no, 
            'batch_short_name' => $reqData['name'], 
            'batch_name' => $reqData['name'], 
            'node_id' => $this->node_id, 
            'user_id' => $this->user_id, 
            // 'material_code' => $materialCode,
            'batch_class' => $goods_type,  // 翼惠宝商品类型
            'info_title' => $reqData['mms_title'], 
            'use_rule' => $reqData['mms_info'], 
            'sms_text' => $reqData['mms_info'], 
            'batch_img' => $reqData['goods_img'], 
            'batch_amt' => $reqData['batch_price'],  // 平安币
            'begin_time' => $reqData['start_time'] . '000000', 
            'end_time' => $reqData['end_time'] . '235959', 
            // 'send_begin_date' => $startDate.'000000',
            // 'send_end_date' => $endDate.'235959',
            'verify_begin_date' => $reqData['verify_begin_date'], 
            'verify_end_date' => $reqData['verify_end_date'], 
            'verify_begin_type' => $reqData['verify_begin_type'], 
            'verify_end_type' => $reqData['verify_end_type'], 
            'add_time' => date('YmdHis'), 
            'status' => '0', 
            'goods_id' => $goods_id, 
            'storage_num' => $reqData['goods_num'], 
            'remain_num' => $reqData['goods_num'], 
            'batch_desc' => $reqData['goods_desc'], 
            'm_id' => $m_id);
        $b_id = M('tbatch_info')->add($batch_data);
        if (! $b_id) {
            // $this->error('系统出错,添加tbatch_info失败');
            return array(
                'code' => '0008', 
                'err_msg' => '系统出错,新建营销活动发送参数失败', 
                'b_id' => '');
        }
        return array(
            'code' => '0000', 
            'err_msg' => '新建成功', 
            'b_id' => $b_id);
    }

    /**
     * 新增营销活动
     */
    public function _addMarketingInfo($reqData, $nodeInfo, $merchantInfo, 
        $batch_type) {
        // tmarketing_info数据创建
        $market_data = array(
            'name' => $reqData['name'], 
            'batch_type' => $batch_type, 
            'node_id' => $this->node_id, 
            'start_time' => $reqData['start_time'] . '000000', 
            'end_time' => $reqData['end_time'] . '235959', 
            'market_price' => $reqData['market_price'], 
            'group_price' => $reqData['batch_price'], 
            'node_name' => $nodeInfo['node_name'], 
            'join_mode' => '1', 
            // 'group_goods_name' => $goodsInfo['goods_name'],
            // 'goods_num' => $goodsNum,
            // 'buy_num' => $buyNum,
            // 'goods_img' => $goodsInfo['goods_image'],
            // 'size' => $size,
            // 'code_img' => $respCodeImg,
            'is_cj' => 1,  // 是否抽奖0 否 1是
            'memo' => $reqData['goods_info'], 
            // 'sns_type' => $snsType,
            // 'defined_one_name' => $reqData['delivery_flag'], //1 到店领取 2 物流配送
            // 'defined_two_name'=>$showFlag,
            // 'defined_three_name'=>$buyCont,
            'status' => '1', 
            'add_time' => date('YmdHis'));
        
        $m_id = M('tmarketing_info')->add($market_data);
        if (! $m_id) {
            // $this->error('系统出错,添加marketing_info失败');
            return array(
                'code' => '0009', 
                'err_msg' => '系统出错,新建营销活动失败', 
                'm_id' => '');
        }
        
        // 插入关联表
        $pa_mdata = array(
            'merchant_id' => $reqData['merchant_id'], 
            'mid' => $m_id, 
            'shelf_status' => '0');
        $pa_mid = M('tfb_yhb_mconfig')->add($pa_mdata);
        if (! $pa_mid) {
            // $this->error('系统出错,添加marketing_info失败');
            return array(
                'code' => '0010', 
                'err_msg' => '系统出错,新建营销活动关联数据失败', 
                'm_id' => $m_id);
        }
        return array(
            'code' => '0000', 
            'err_msg' => '新建成功', 
            'm_id' => $m_id);
    }

    /*
     * 设置抽奖 手机号单次 100%中奖
     */
    public function _setCj($m_id, $batch_type, $iss_batch_no, $goods_num, $b_id, 
        $goods_id) {
        if ($batch_type == '2000') {
            $cj_button_text = "领取优惠";
            $cj_resp_text = "恭喜您！成功领取优惠";
        }
        
        // tcj_rule
        $cj_data = array(
            'total_chance' => 100, 
            'cj_button_text' => $cj_button_text, 
            'phone_total_count' => 0, 
            'phone_day_count' => 1, 
            'phone_total_part' => 0, 
            'phone_day_part' => 0, 
            'cj_resp_text' => $cj_resp_text, 
            'batch_id' => $m_id, 
            'cj_phone_type' => 1, 
            'param1' => 0);
        
        $ruleList = M('tcj_rule')->where(
            array(
                "batch_id" => $m_id, 
                "status" => '1'))->select();
        // 新增
        if (! $ruleList) {
            $data = array(
                'batch_type' => $batch_type, 
                'batch_id' => $m_id, 
                'jp_set_type' => 2, 
                'node_id' => $this->node_id, 
                'add_time' => date('YmdHis'), 
                'status' => '1', 
                'total_chance' => $cj_data['total_chance'], 
                'phone_total_count' => $cj_data['phone_total_count'], 
                'phone_day_count' => $cj_data['phone_day_count'], 
                'phone_total_part' => $cj_data['phone_total_part'], 
                'phone_day_part' => $cj_data['phone_day_part'], 
                'cj_button_text' => $cj_data['cj_button_text'], 
                'cj_resp_text' => $cj_data['cj_resp_text'], 
                'param1' => $cj_data['param1']);
            $flag = M('tcj_rule')->add($data);
            if ($flag === false)
                return array(
                    'code' => '0011', 
                    'err_msg' => '保存失败！新建规则数据失败');
            $rule_id = $flag;
        } else {
            if (count($ruleList) > 1)
                return array(
                    'code' => '0012', 
                    'err_msg' => '系统异常：存在多条启用的抽奖规则记录！');
            $ruleInfo = $ruleList[0];
            
            // 编辑
            $data = array(
                'total_chance' => $cj_data['total_chance'], 
                'phone_total_count' => $cj_data['phone_total_count'], 
                'phone_day_count' => $cj_data['phone_day_count'], 
                'phone_total_part' => $cj_data['phone_total_part'], 
                'phone_day_part' => $cj_data['phone_day_part'], 
                'cj_button_text' => $cj_data['cj_button_text'], 
                'cj_resp_text' => $cj_data['cj_resp_text'], 
                'param1' => $cj_data['param1']);
            $flag = M('tcj_rule')->where(
                array(
                    "id" => $ruleInfo['id']))->save($data);
            if ($flag === false)
                return array(
                    'code' => '0013', 
                    'err_msg' => '保存失败！保存规则数据失败');
            $rule_id = $ruleInfo['id'];
        }
        
        // tcj_cate
        $cate_data = array(
            'batch_type' => $batch_type, 
            'batch_id' => $m_id, 
            'node_id' => $this->node_id, 
            'cj_rule_id' => $rule_id, 
            'name' => '默认奖项', 
            'add_time' => date('YmdHis'), 
            'status' => '1');
        $cate_id = M('tcj_cate')->add($cate_data);
        if ($cate_id === false)
            return array(
                'code' => '0014', 
                'err_msg' => '保存失败！保存规则等级数据失败');
            // tcj_batch
        $cjBatchList = M('tcj_batch')->where(
            array(
                'batch_id' => $m_id, 
                'batch_type' => $batch_type, 
                'cj_rule_id' => $rule_id))->select();
        // 新增
        if (! $cjBatchList) {
            $data = array(
                'batch_id' => $m_id,  // '抽奖活动id'
                'node_id' => $this->node_id,  // '商户号'
                'activity_no' => $iss_batch_no,  // '奖品活动号'
                'award_origin' => '2',  // '奖品来源 1支撑 2旺财'
                'award_rate' => $goods_num,  // '中奖率'
                'total_count' => $goods_num,  // '奖品总数'
                'day_count' => $goods_num,  // '每日奖品数'
                'batch_type' => $batch_type,  // ,
                'cj_rule_id' => $rule_id,  // '抽奖规则id'
                'send_type' => '0',  // '0-下发，1-不下发'
                'status' => '1',  // '1正常 2停用'
                'cj_cate_id' => $cate_id,  // '抽奖类别id对应tcj_cate主键id'
                'add_time' => date('YmdHis'),  // '奖品添加时间'
                'goods_id' => $goods_id, 
                'b_id' => $b_id);
            $cj_batch_id = M('tcj_batch')->add($data);
            if ($cj_batch_id === false)
                return array(
                    'code' => '0015', 
                    'err_msg' => '保存失败！新建规则奖品数据失败');
            return array(
                'code' => '0000', 
                'err_msg' => '保存成功！新建规则奖品数据成功');
        } else {
            if (count($cjBatchList) > 1)
                return array(
                    'code' => '0016', 
                    'err_msg' => '系统异常：存在多条启用的抽奖规则记录！');
            $cjBatchList = $cjBatchList[0];
            $data = array(
                'award_rate' => $goods_num,  // '中奖率'
                'total_count' => $goods_num,  // '奖品总数'
                'day_count' => $goods_num,  // '每日奖品数'
                'batch_type' => $batch_type,  // ,
                'cj_rule_id' => $rule_id,  // '抽奖规则id'
                'send_type' => '0',  // '0-下发，1-不下发'
                'status' => '1',  // '1正常 2停用'
                'cj_cate_id' => $cate_id); // '抽奖类别id对应tcj_cate主键id'
            
            $flag = M('tcj_batch')->save($data);
            if ($flag === false)
                return array(
                    'code' => '0017', 
                    'err_msg' => '保存失败！保存规则奖品数据失败');
            return array(
                'code' => '0000', 
                'err_msg' => '保存成功！保存规则奖品数据成功');
        }
    }

    /**
     * 更具规则校验提交参数
     */
    public function _verifyReqData($rules, $return = false, $method = 'post') {
        if (! is_array($rules))
            return;
        $error = '';
        $req_data = array();
        foreach ($rules as $k => $v) {
            $value = I($method . '.' . $k);
            if (! check_str($value, $v, $error)) {
                $msg = $v['name'] . $error;
                if ($return)
                    return $msg;
                else
                    $this->error($msg);
            }
            
            $req_data[$k] = $value;
        }
        return $req_data;
    }

    /**
     * 获取商户可用门店列表（有终端的）
     */
    public function pos_list() {
        $merchant_id = I('merchant_id');
        if (! $merchant_id)
            $this->error('请先选择商户');
        $str = '';
        $queryList = M('tstore_info')->where(
            array(
                "yhb_flage" => $merchant_id))->select();
        foreach ($queryList as $v) {
            $str .= '<input type="checkbox" name="storeid[]" value="' .
                 $v['store_id'] . '"><p class="ml5 choosetext2">' .
                 $v['store_name'] . '</p>';
        }
        echo $str;
        exit();
    }

    // 活动启用与停用
    public function change_start() {
        $batchId = I('post.batch_id', null);
        $status = I('post.status', null);
        if (is_null($batchId) || is_null($status)) {
            $this->error('缺少必要参数');
        }
        $result = M()->table("tmarketing_info m")->join('tbatch_info b on b.m_id=m.id')
            ->join('tgoods_info g on g.goods_id=b.goods_id')
            ->where(
            array(
                "m.node_id" => $this->node_id, 
                "m.id" => $batchId))
            ->find();
        if (! $result) {
            $this->error('未找到该记录');
        }
        if ($status == '1') { // 上架
                              // 上架前先判断该商品是否已停用 tgoods_info
            $data = array(
                'status' => '1');
            $tgoods_status = array(
                'status' => 0);
            $batch_status = array(
                'status' => '0');
            // 启用时，判断商户状态是否正常
            $merchant_id = I('merchant_id');
            if (empty($merchant_id)) {
                $this->error("商户ID不能为空！");
            }
            $this->storeStatus($merchant_id);
        } else {
            $data = array(
                'status' => '2');
            $tgoods_status = array(
                'status' => 1);
            $batch_status = array(
                'status' => '1');
        }
        M()->startTrans();
        $result_t = M('tmarketing_info')->where(
            array(
                "id" => $batchId))->save($data);
        if ($result_t === false) {
            M()->rollback();
            $this->error('更新失败!');
        }
        // 停用tbatch_info里面的状态
        $result_t_1 = M('tbatch_info')->where(
            array(
                "m_id" => $batchId))->save($batch_status);
        if ($result_t_1 === false) {
            M()->rollback();
            $this->error('更新失败!');
        }
        // 更新tgood_info里面的状态
        $result_g = M('tgoods_info')->where(
            array(
                "goods_id" => $result['goods_id'], 
                "node_id" => $this->node_id))->save($tgoods_status);
        if ($result_g === false) {
            M()->rollback();
            $this->error('更新失败!');
        }
        if ($status == '1') { // 上架
                              // 上架前先判断该商品是否已停用 tgoods_info
            $data_yhb = array(
                'line_status' => '1');
        } else {
            $data_yhb = array(
                'line_status' => '2');
        }
        $res_yhb = M("tfb_yhb_goods")->where(
            array(
                'goods_id' => $result['goods_id'], 
                "node_id" => $this->node_id))->save($data_yhb);
        if ($res_yhb === false) {
            M()->rollback();
            $this->error("更新失败!");
        }
        M()->commit();
        $this->success("更新成功!");
    }
    // 优惠所在门店详情
    public function couponStoreView() {
        $goods_id = I("goods_id");
        if ($goods_id == "") {
            $this->error("goods_id参数错误");
        }
        $m_id = I('id');
        if ($m_id == "") {
            $this->error("m_id参数错误");
        }
        // 接收tmarketing_info里面的id
        $batch_info = M("tbatch_info")->where(
            array(
                'm_id' => $m_id))->find();
        
        $tgoods_info = M("tgoods_info")->where(
            array(
                "goods_id" => $goods_id, 
                "node_id" => $this->node_id))->find();
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = $store_arr = M()->table("tgroup_pos_relation  a")->join(
            'tstore_info b on b.store_id=a.store_id')
            ->where(
            array(
                "a.group_id" => $tgoods_info['pos_group']))
            ->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $data = $_REQUEST;
        foreach ($data as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        $show = $Page->show(); // 分页显示输出
        $store_arr = M()->table("tgroup_pos_relation  a")->join(
            'tstore_info b on b.store_id=a.store_id')
            ->where(
            array(
                "a.group_id" => $tgoods_info['pos_group']))
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        $this->assign('batch_info', $batch_info);
        $this->assign('store_arr', $store_arr);
        $this->assign('page', $show);
        $this->display("Coupon/Yhb_coupon_StoreView");
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
    // 优惠下载
    public function download() {
        $map = array(
            'm.node_id' => $this->node_id);
        $map['_string'] = "pg.line_status !='3'";
        if (I('key') != '') {
            $map['m.name'] = array(
                'like', 
                '%' . I('key') . '%');
        }
        if (I('status') != '') {
            $map['pg.line_status'] = I('status');
        }
        if (I('merchant_name') != '') {
            $map['pm.merchant_name'] = array(
                'like', 
                '%' . I('merchant_name') . '%');
        }
        // 商户查询
        if (I('town_code') != '') {
            $map['stra.town_code'] = I('town_code');
        }
        // 街道
        if (I('street_code') != '') {
            $map['strb.street_code'] = I('street_code');
        }
        // 小区
        if (I('village_code') != '') {
            $map['strb.village_code'] = I('village_code');
        }
        $map['stra.city_level'] = 1;
        if (I('parent_id') != '') {
            $map['pm.parent_id'] = I('parent_id');
        }
        if (I('catalog_id_ok') != '') {
            $map['pc.id'] = I('catalog_id_ok');
        }
        // 判断权限，商户各自查看自己的门店
        if ($this->check_is_admin($this->node_id) === false) {
            $map['pm.id'] = $this->merchant_id;
        }
        $catalog_array = $this->catalog_array;
        $map['m.batch_type'] = $this->BATCH_TYPE_COUPON;
        $count = M()->table("tmarketing_info m")->join('tbatch_info b on b.m_id=m.id')
            ->join('tgoods_info g on g.goods_id=b.goods_id')
            ->join('tfb_yhb_goods pg on pg.goods_id=g.goods_id')
            ->join('tgroup_pos_relation reg on reg.group_id=g.pos_group')
            ->join('tstore_info stb on stb.store_id=reg.store_id')
            ->join('tfb_yhb_store strb on strb.store_id=stb.store_id')
            ->join(
            'tfb_yhb_city_code stra on stra.street_code=strb.street_code')
            ->join('tfb_yhb_mconfig pmc on pmc.mid=m.id')
            ->join('tfb_yhb_node_info pm on pm.id=pg.merchant_id')
            ->join(
            'tfb_yhb_catalog pc on pc.id=pm.catalog_id or pc.parent_id=pm.parent_id')
            ->where($map)
            ->count(); // 查询满足要求的总记录数
        if ($count <= 0)
            $this->error('无订单数据可下载');
        $list = M()->table("tmarketing_info m")->field(
            'm.*,b.storage_num,b.remain_num,g.goods_name,g.goods_id,g.storage_type,g.status as gstatus,pm.id as merchant_id,pm.merchant_name,pm.status as merchant_status,pc.catalog_name,pm.city_code as org_code,pmc.shelf_status,pmc.last_apply_time,pg.goods_id as goods_id,pg.order_sort,pg.line_status,pm.catalog_id,pm.parent_id')
            ->join('tbatch_info b on b.m_id=m.id')
            ->join('tgoods_info g on g.goods_id=b.goods_id')
            ->join('tgroup_pos_relation reg on reg.group_id=g.pos_group')
            ->join('tstore_info stb on stb.store_id=reg.store_id')
            ->join('tfb_yhb_store strb on strb.store_id=stb.store_id')
            ->join(
            'tfb_yhb_city_code stra on stra.street_code=strb.street_code')
            ->join('tfb_yhb_goods pg on pg.goods_id=g.goods_id')
            ->join('tfb_yhb_node_info pm on pm.id=pg.merchant_id')
            ->join('tfb_yhb_catalog pc on pc.id=pm.catalog_id')
            ->join('tfb_yhb_mconfig pmc on pmc.mid=m.id')
            ->where($map)
            ->order('pg.order_sort desc, m.add_time desc')
            ->select();
        $fileName = date("YmdHis") . "-翼惠宝优惠列表.csv";
        $fileName = iconv('utf-8', 'gbk', $fileName);
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        $title = "优惠名称,商户名称,分类,子类,总数,已领取,上架状态\r\n";
        echo $title = iconv('utf-8', 'gbk', $title);
        if ($list) {
            foreach ($list as $vo) {
                if ($vo['line_status'] == '1') {
                    $vo['line_status'] = "展示";
                } elseif ($vo['line_status'] == '2') {
                    $vo['line_status'] = "停用";
                } else {
                    $vo['line_status'] = "上线";
                }
                $vo['parent_id'] = $catalog_array[$vo['parent_id']];
                $vo['catalog_id'] = $catalog_array[$vo['catalog_id']];
                $exit_num = $vo['storage_num'] - $vo['remain_num'];
                iconv_arr('utf-8', 'gbk', $vo);
                echo "=\"{$vo['name']}\",=\"{$vo['merchant_name']}\",=\"{$vo['parent_id']}\",=\"{$vo['catalog_id']}\",=\"{$vo['storage_num']}\",=\"{$exit_num}\",=\"{$vo['line_status']}\"" .
                     "\r\n";
            }
            exit();
        }
    }
    // 优惠下载
    public function coupon_Download($map) {
        $catalog_array = $this->catalog_array;
        $count = M()->table("tmarketing_info m")->join('tbatch_info b on b.m_id=m.id')
            ->join('tgoods_info g on g.goods_id=b.goods_id')
            ->join('tfb_yhb_goods pg on pg.goods_id=g.goods_id')
            ->join('tgroup_pos_relation reg on reg.group_id=g.pos_group')
            ->join('tstore_info stb on stb.store_id=reg.store_id')
            ->join('tfb_yhb_store strb on strb.store_id=stb.store_id')
            ->join(
            'tfb_yhb_city_code stra on stra.street_code=strb.street_code')
            ->join('tfb_yhb_mconfig pmc on pmc.mid=m.id')
            ->join('tfb_yhb_node_info pm on pm.id=pg.merchant_id')
            ->join('tfb_yhb_catalog pc on pc.id=pm.catalog_id')
            ->join('tfb_yhb_catalog plc on plc.parent_id=pm.parent_id')
            ->where($map)
            ->count('distinct(g.goods_id)'); // 查询满足要求的总记录数
        if ($count <= 0)
            $this->error('无订单数据可下载');
        $list = M()->table("tmarketing_info m")->field(
            'm.*,b.storage_num,b.remain_num,g.goods_name,g.goods_id,g.storage_type,g.status as gstatus,pm.id as merchant_id,pm.merchant_name,pm.status as merchant_status,pc.catalog_name,pm.city_code as org_code,pmc.shelf_status,pmc.last_apply_time,pg.goods_id as goods_id,pg.order_sort,pg.line_status,pm.catalog_id,pm.parent_id')
            ->join('tbatch_info b on b.m_id=m.id')
            ->join('tgoods_info g on g.goods_id=b.goods_id')
            ->join('tgroup_pos_relation reg on reg.group_id=g.pos_group')
            ->join('tstore_info stb on stb.store_id=reg.store_id')
            ->join('tfb_yhb_store strb on strb.store_id=stb.store_id')
            ->join(
            'tfb_yhb_city_code stra on stra.street_code=strb.street_code')
            ->join('tfb_yhb_goods pg on pg.goods_id=g.goods_id')
            ->join('tfb_yhb_node_info pm on pm.id=pg.merchant_id')
            ->join('tfb_yhb_catalog pc on pc.id=pm.catalog_id')
            ->join('tfb_yhb_catalog plc on plc.parent_id=pm.parent_id')
            ->join('tfb_yhb_mconfig pmc on pmc.mid=m.id')
            ->where($map)
            ->order('pg.order_sort desc, m.add_time desc')
            ->group('g.goods_id')
            ->select();
        $fileName = date("YmdHis") . "-翼惠宝优惠列表.csv";
        $fileName = iconv('utf-8', 'gbk', $fileName);
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        $title = "优惠名称,商户名称,分类,子类,总数,已领取,上架状态\r\n";
        echo $title = iconv('utf-8', 'gbk', $title);
        if ($list) {
            foreach ($list as $vo) {
                if ($vo['line_status'] == '1') {
                    $vo['line_status'] = "展示";
                } elseif ($vo['line_status'] == '2') {
                    $vo['line_status'] = "停用";
                } else {
                    $vo['line_status'] = "上线";
                }
                $vo['parent_id'] = $catalog_array[$vo['parent_id']];
                $vo['catalog_id'] = $catalog_array[$vo['catalog_id']];
                $exit_num = $vo['storage_num'] - $vo['remain_num'];
                iconv_arr('utf-8', 'gbk', $vo);
                echo "=\"{$vo['name']}\",=\"{$vo['merchant_name']}\",=\"{$vo['parent_id']}\",=\"{$vo['catalog_id']}\",=\"{$vo['storage_num']}\",=\"{$exit_num}\",=\"{$vo['line_status']}\"" .
                     "\r\n";
            }
            exit();
        }
    }
}
