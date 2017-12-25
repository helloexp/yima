<?php
// 商品上下架
class GoodsPutOnAction extends BaseAction {

    public $mk_price_arr = array();

    public $city_arr = array();

    public $classify_arr = array();

    public $goods_status_arr = array(
        '0' => '已上架', 
        '1' => '已下架', 
        '2' => '已过期');

    public $img_path;
    // 卡券图片存放路径
    public $store_mid = '';

    private $goodsQrcodeDir;//二维码存放路径

    public $isInterGral = false;
    // 积分权限
    public function _initialize() {
        parent::_initialize();

        // 上架管理存放二维码用
        if (! is_dir(APP_PATH . 'Upload/goods_qrcode/'.$this->nodeId)) {
            mkdir(APP_PATH . 'Upload/goods_qrcode/'.$this->nodeId, 0777, true);
        }
        $this->goodsQrcodeDir = APP_PATH . 'Upload/goods_qrcode' . '/' .
                $this->nodeId;

        $map = array(
            'node_id' => $this->nodeId);
        $this->classify_arr = M('tecshop_classify')->where($map)->order(
            'sort asc')->getField('id, class_name', true);
        $this->assign('classify_arr', $this->classify_arr);
        
        // 判断积分权限
        $this->isInterGral = $this->_hasIntegral($this->node_id);
        // 取得积分规则信息
        $intergralType = D('SalePro', 'Service')->getNodeRule($this->node_id, 
            'tintegral_rule_main');
        if ('0' === $intergralType)
            $this->isInterGral = false;
        
        $this->img_path = C('BATCH_IMG_PATH_NAME.31') . '/' . $this->nodeId . '/';
        $this->store_mid = M('tmarketing_info')->where(
            "node_id = '{$this->nodeId}' and batch_type = '29'")->getField('id');
        $this->assign('isIntergral', $this->isInterGral); // 订购权限
        $this->assign('store_mid', $this->store_mid);
        $this->assign('goods_status_arr', $this->goods_status_arr);
    }
    
    // 上架管理
    public function index() {
        $goods_name = I('goods_name', '');
        $min_price = I('min_price', '');
        $max_price = I('max_price', '');
        $min_date = I('begin_date', '');
        $max_date = I('end_date', '');
        $status = I('status', '');
        $classify = I('classify', '');
        $sort_field = I('sort_field', '');
        $sort_arr = array(
            'sale_num', 
            'remain_num', 
            'lock_num', 
            'click_count');
        $sort_sql = 'status asc , id desc';
        if ($sort_field != '') {
            do {
                $arr = explode('|', $sort_field);
                if (count($arr) != 2)
                    break;
                if (! in_array($arr[0], $sort_arr))
                    break;
                $sort_sql = $arr[0] . ($arr[1] == 'asc' ? ' asc' : ' desc');
            }
            while (0);
        }
        
        $where = array(
            'a.node_id' => $this->nodeId, 
            'T.is_delete' => 0,  // 显示未删除的信息
            '_string' => ' 1=1 ');
        
        if ($goods_name != "")
            $where['T.batch_name'] = array(
                'exp', 
                "like '%$goods_name%'");
        
        if ($min_price != '')
            $where['T.batch_amt'] = array(
                'egt', 
                $min_price);
        
        if ($max_price != '')
            $where['T.batch_amt'] = array(
                'elt', 
                $max_price);
        
        if ($min_date != '')
            $where['_string'] .= "and if(t.update_time is null or t.update_time = '', t.add_time, t.update_time) >= '{$min_date}000000'";
        
        if ($max_date != '')
            $where['_string'] .= "and if(t.update_time is null or t.update_time = '', t.add_time, t.update_time) <= '{$max_date}000000'";
        
        if ($status != '')
            $where['T.status'] = $status;
        
        if ($classify != '')
            $where['a.ecshop_classify'] = $classify;
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = M()->table('tecshop_goods_ex a')
            ->field('a.*,T.*')
            ->join('tbatch_info T ON a.node_id=T.node_id and t.id = a.b_id')
            ->where($where)
            ->count();
        
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
                                         // $subQuery =
                                         // M()->table('tecshop_goods_ex a')
                                         // ->field("a.ecshop_classify,
                                         // a.label_id, T.*,
                                         // m.click_count,g.goods_image, "
                                         // . "(SELECT sum(oe.goods_num) FROM
                                         // ttg_order_info o, ttg_order_info_ex
                                         // oe WHERE o.node_id = t.node_id AND
                                         // o.order_id = oe.order_id AND oe.b_id
                                         // = a.b_id AND o.order_type = '2' AND
                                         // o.order_status = '0' AND
                                         // o.pay_status IN ('2','3')) as
                                         // sale_num, "
                                         // . "(SELECT sum(oe.goods_num) FROM
                                         // ttg_order_info o, ttg_order_info_ex
                                         // oe WHERE o.node_id = t.node_id AND
                                         // o.order_id = oe.order_id AND oe.b_id
                                         // = a.b_id AND o.order_type = '2' AND
                                         // o.order_status = '0' AND
                                         // o.pay_status IN ('1')) as lock_num")
                                         // ->join('tbatch_info T ON
                                         // a.node_id=T.node_id and t.id =
                                         // a.b_id')
                                         // ->join('tgoods_info g on T.goods_id
                                         // = g.goods_id')
                                         // ->join('tmarketing_info m on m.id =
                                         // a.m_id')
                                         // ->where($where)
                                         // ->buildSql();
        
        $subQuery = M()->table('tecshop_goods_ex a')
            ->field(
            "a.ecshop_classify, a.label_id, T.*, m.click_count, m.integral_flag,g.goods_image,x.sale_num, g.is_sku ")
            ->join(
            "(SELECT o.node_id, oe.b_id,SUM(CASE WHEN o.pay_status IN('2','3') THEN oe.goods_num END) sale_num FROM ttg_order_info o, ttg_order_info_ex oe WHERE o.order_id = oe.order_id AND o.order_type = '2' AND o.order_status = '0' GROUP BY o.node_id, oe.b_id) X ON  x.node_id = a.node_id AND x.b_id = a.b_id")
            ->join('tbatch_info T ON a.node_id=T.node_id and t.id = a.b_id')
            ->join('tgoods_info g on T.goods_id =  g.goods_id')
            ->join('tmarketing_info m on m.id =  a.m_id')
            ->where($where)
            ->buildSql();
        
        $list = M()->table($subQuery . ' a')->order($sort_sql)->limit(
            $Page->firstRow . ',' . $Page->listRows)->select();
        $show = $Page->show(); // 分页显示输出
                               // 创建sku信息
        $skuObj = D('Sku', 'Service');
        foreach ($list as &$val) {
            // sku商品
            if ("1" === $val['is_sku']) {
                $val = $skuObj->makeGoodsListInfo($val, $this->node_id, true, 
                    $val['id'], $val['m_id']);
            }
        }
        // 获取商品分类
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('list', $list); // 赋值分页输出
        $this->display();
    }

    public function putOn() {
        $goodsId = I('goods_id');
        $goodsInfo = false;
        if (! empty($goodsId)) {
            $goodsInfo = M('tgoods_info')->where(
                "goods_id='{$goodsId}' and node_id='{$this->nodeId}'")->find();
            if (! $goodsInfo)
                $this->error('未找到该卡券');
        }
        $adb_flag = 0;
        if ($this->node_id == C('adb.node_id')) {
            $adb_flag = 1;
            $store_id=0;
            $this->assign('adb_flag', $adb_flag);
            $this->assign('store_id', $store_id);
        }
        $node_info = M('tnode_info')->where(
            array(
                'node_id' => $this->node_id))->find();
        $this->assign('node_info', $node_info);
        $this->assign('goodsInfo', json_encode($goodsInfo));
        //获取当前机构的资费标准
        $sendPrice = D('ActivityPayConfig')->getSendFee($this->node_id);
        $this->assign('sendPrice', $sendPrice);
        $this->display();
    }

    public function Offline() {
        $m_id = I('id', 0, 'intval');
        if ($m_id == 0)
            $this->error('参数错误！');
        
        $map = array(
            'node_id' => $this->nodeId, 
            'id' => $m_id, 
            'batch_type' => '31');
        $marketInfo = M('tmarketing_info')->where($map)->find();
        if (! $marketInfo)
            $this->error('参数错误！');
        
        $batchInfo = M('tbatch_info')->where("m_id = '{$m_id}'")->find();
        if ($batchInfo['status'] != '0')
            $this->error('该订单已经下线或过期，不能下架');
        if ($batchInfo['is_delete'] != '0')
            $this->error('该订单已经删除，不能下架');
        
        M()->startTrans();
        $data = array(
            'status' => '1');
        $flag = M('tbatch_info')->where("m_id = '{$m_id}'")->save($data);
        if ($flag === false)
            $this->error('下架失败！');
            
            // //创建sku信息
            // $skuObj = D('Sku', 'Service');
            // unset($map);
            // $skuInfoList = $skuObj->getSkuEcshopList($m_id, $this->nodeId);
            // //判断是否有sku数据
            // if($skuInfoList){
            // //返回sku库存
            // $result = $skuObj->returnGoodsNum($skuInfoList);
            // if($result === false){
            // M()->rollback();
            // $this->error('系统出错，sku商品返回库存失败');
            // }
            // }
        $data = array(
            'is_commend' => '9');
        $flag = M('tecshop_goods_ex')->where("m_id = '{$m_id}'")->save($data);
        if ($flag === false) {
            M()->rollback();
            $this->error('下架失败！');
        }
        
        M()->commit();
        
        $this->ajaxReturn(1);
        // $this->success('下架成功！');
    }

    public function Edit() {
        $m_id = I('id', 0, 'intval');
        $puton_flag = I('puton_flag', 0, 'intval');
        
        if ($m_id == 0)
            $this->error('参数错误！');
        
        $map = array(
            'node_id' => $this->nodeId, 
            'id' => $m_id, 
            'batch_type' => '31');
        $marketInfo = M('tmarketing_info')->where($map)->find();
        if (! $marketInfo)
            $this->error('参数错误！');
        
        $goodsInfo = M('tbatch_info')->where("m_id = '{$m_id}'")->find();
        if ($goodsInfo['status'] == '0')
            $this->error('商品已启用，不能编辑');
            /*
         * if($goodsInfo['status'] == '2') $this->error('商品已过期，不能编辑',
         * U('index'));
         */
        if ($goodsInfo['is_delete'] == '1')
            $this->error('商品已删除，不能编辑');
            // 爱蒂宝
        if ($this->node_id == C('adb.node_id')) {
            $adb_flag = 1;
            $store_id=0;
            $param = array(
                'node_id' => $this->node_id, 
                'id' => $goodsInfo['id']);
            B('AdbGoodsEdit', $param);
            $this->assign('adbb_id', $goodsInfo['id']);
            $this->assign('store_id', $store_id);
            $this->assign('adb_flag', $adb_flag);
            $this->assign('count', $param['return']['data']['count']);
            $this->assign('stores', $param['return']['data']['stores_data']);
        }
        $goodsInfoEx = M('tecshop_goods_ex')->where("m_id = '{$m_id}'")->find();
        // 创建sku信息
        $skuObj = D('Sku', 'Service');
        unset($map);
        $goodsListInfo = M('tgoods_info')->where(
            "goods_id = '{$goodsInfo['goods_id']}'")->field('is_sku, is_order')->find();
        $skuInfoList = $skuObj->getSkuEcshopList($m_id, $this->nodeId);
        // 判断是否有sku数据
        $goodsSkuTypeList = '';
        $goodsSkuDetailList = '';
        $skuDetail = '';
        $isCycle = $goodsListInfo['is_order']; // 是否订购
        
        if (NULL === $skuInfoList) {
            if ('1' === $goodsListInfo['is_sku'])
                $this->error("该商品已添加规格信息，请重新上架！", 
                    array(
                        '返回' => 'javascript:history.go(-1)', 
                        '重新上架' => U('Ecshop/GoodsPutOn/putOn')));
            $isSku = false;
        } else {
            $isSku = true;
            // 分离商品表中的规格和规格值ID
            $goods_sku_list = $skuObj->getReloadSku($skuInfoList);
            // 取得规格值表信息
            if (is_array($goods_sku_list['list']))
                $goodsSkuDetailList = $skuObj->getSkuDetailList(
                    $goods_sku_list['list']);
                
                // 取得规格表信息
            if (is_array($goodsSkuDetailList))
                $goodsSkuTypeList = $skuObj->getSkuTypeList($goodsSkuDetailList);
                
                // 价格列表
            $skuDetail = $skuObj->makeSkuList($skuInfoList);
        }
        //自定义短信内容
        $startUp = M('tnode_info')->where(array('node_id'=>$this->node_id))->getField('custom_sms_flag');
        if ($this->isPost()) {
            $rules = array(
                'goods_desc' => array(
                    'null' => false, 
                    'maxlen_cn' => '200', 
                    'name' => '商品描述'),
                'saleProportion' => array(
                    'null' => true, 
                    'strtype' => 'number', 
                    'name' => '销售比例'),
                'wap_info' => array(
                    'null' => false, 
                    'maxlen_cn' => '10000', 
                    'name' => '商品描述详情'), 
                'resp_img1' => array(
                    'null' => true, 
                    'name' => '第一张图片'), 
                'resp_img2' => array(
                    'null' => true, 
                    'name' => '第一张图片'), 
                'resp_img3' => array(
                    'null' => true, 
                    'name' => '第一张图片'), 
                'classify' => array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'name' => '所属分组', 
                    'inarr' => array_keys($this->classify_arr)), 
                
                // 'delivery_flag' => array('null'=>false,'name'=>'配送方式'),
                // 粉丝专享配置
                'member_join_flag' => array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'name' => '购买对象限制', 
                    'inarr' => array(
                        '0', 
                        '1')), 
                'fans_collect_url' => array(
                    'null' => true, 
                    'strtype' => 'string', 
                    'name' => '引导页链接'), 
                'purchase_time_limit' => array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'name' => '是否限时购买', 
                    'inarr' => array(
                        '0', 
                        '1')), 
                'batch_amt' => array(
                    'null' => true, 
                    'strtype' => 'number', 
                    'name' => '销售价格'), 
                'storage_type' => array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'name' => '总库存', 
                    'inarr' => array(
                        '0', 
                        '1')), 
                'market_show' => array(
                    'null' => true, 
                    'strtype' => 'int', 
                    'name' => '商品市场价格', 
                    'inarr' => array(
                        '0', 
                        '1')), 
                'day_buy_num_flag' => array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'name' => '商品每日限购', 
                    'inarr' => array(
                        '0', 
                        '1')), 
                'person_buy_num_flag' => array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'name' => '每人限购', 
                    'inarr' => array(
                        '0', 
                        '1')), 
                'bonus_flag' => array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'name' => '参与人品红包', 
                    'inarr' => array(
                        '0', 
                        '1')), 
                'integral_flag' => array(
                    'null' => true, 
                    'strtype' => 'int', 
                    'name' => '参与积分购物', 
                    'inarr' => array(
                        '0', 
                        '1')), 
                'deli_pay_flag' => array(
                    'null' => true, 
                    'strtype' => 'int', 
                    'name' => '支持货到付款', 
                    'inarr' => array(
                        '0', 
                        '1')), 
                // sku附加项
                'is_sku' => array(
                    'null' => true, 
                    'name' => '是否有sku信息'), 
                'data_price_info' => array(
                    'null' => true, 
                    'name' => 'sku价格列表'), 
                'count_num' => array(
                    'null' => true, 
                    'name' => 'sku总库存'));
            $reqData = $this->_verifyReqData($rules);
            //$reqData['is_sku'] = '0';
            //销售比例处理
            if($reqData['saleProportion'] < 1){
                $reqData['saleProportion'] = 1;
            }
            // sku提交
            if ('1' === $reqData['is_sku']) {
                $skuMarkPrice = json_decode(
                    html_entity_decode($reqData['data_price_info']), true);
                if (! $skuMarkPrice) {
                    $this->error("该商品并不是sku商品");
                }
            } else {
                if ('' === $reqData['batch_amt'])
                    $this->error("请输入销售价格");
                if ('' === $reqData['market_show'])
                    $this->error("请输入商品市场价格");
            }
            $time_fmt = 'Y-m-d H:i:s';
            $rules = array(
                'begin_date' => array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => $time_fmt, 
                    'name' => '商品销售开始时间'), 
                'end_date' => array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => $time_fmt, 
                    'name' => '商品销售结束时间'));
            $reqData = array_merge($reqData, $this->_verifyReqData($rules));
            
            // 微信粉丝专享判断
            if (($reqData['member_join_flag'] == '1') &&
                 ! $reqData['fans_collect_url'])
                $this->error("购买对象限制为微信粉丝时请填入引导页链接");
                
                // 判断积分是否开启
            if (! $this->isInterGral) {
                $reqData['integral_flag'] = 0;
            }
            $reqData['delivery_flag'] = I('delivery_flag');
            // 非商品类营销品 写死自提方式
            if ($goodsInfo['batch_class'] != '6')
                $reqData['delivery_flag'] = array(
                    '0');
            $rules = array();
            if (! empty($reqData['delivery_flag'])) {
                $reqData['delivery_flag'] = implode('-', 
                    $reqData['delivery_flag']);
            } else
                $this->error("未选择是否配送");
                // 凭证有效期校验
            $a = strstr($reqData['delivery_flag'], '0');
            if ($a !== false) {
                /*
                $rules['mms_title'] = array(
                    'null' => false, 
                    'maxlen_cn' => '10', 
                    'name' => '彩信标题');
                */
                $rules['mms_info'] = array(
                    'null' => false, 
                    'maxlen_cn' => '100', 
                    'name' => '商品使用说明');
                $rules['send_gift'] = array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'name' => '支持送礼', 
                    'inarr' => array(
                        '0', 
                        '1'));
                $rules['verify_time_type'] = array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'name' => '验证时间', 
                    'inarr' => array(
                        '0', 
                        '1'));
                
                if (I('verify_time_type', 0, 'intval') == '0') {
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
            }

            if ('1' === $reqData['is_sku']) {
                // 检查商品库存数
                $returnInfo = $skuObj->checkRemainNum($skuMarkPrice, 
                    $skuInfoList);
                if (false === $returnInfo['msg'])
                    $this->error($returnInfo['info']);
                if ('-1' == $reqData['count_num']) {
                    $reqData['storage_num'] = - 1;
                    $reqData['storage_type'] = 0;
                } else {
                    $reqData['storage_type'] = 1;
                    $reqData['storage_num'] = (int) $reqData['count_num'];
                }
            }

            // 库存
            if ($reqData['storage_type'] == '1' && '1' != $reqData['is_sku']) {
                $minStorageNum = M()->table("ttg_order_info_ex oe")->join(
                    'ttg_order_info o ON o.order_id=oe.order_id')->where(
                    array(
                        'o.order_status' => '0', 
                        'oe.b_id' => $goodsInfo['id']))->getField(
                    'sum(oe.goods_num)');
                $rules['storage_num'] = array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'minval' => $minStorageNum, 
                    'name' => '总库存');
            }
            
            // 市场价格
            if ($reqData['market_show'] == '1')
                $rules['market_price'] = array(
                    'null' => false, 
                    'strtype' => 'number', 
                    'minval' => 0, 
                    'name' => '商品市场价格');
                // 每日限购
            if ($reqData['day_buy_num_flag'] == '1')
                $rules['day_buy_num'] = array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'minval' => '1', 
                    'name' => '商品每日限购');
                // 每人限购
            if ($reqData['person_buy_num_flag'] == '1')
                $rules['person_buy_num'] = array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'minval' => '1', 
                    'name' => '每人限购');
            
            $reqData = array_merge($reqData, $this->_verifyReqData($rules));

            $reqData['verify_begin_date'] = dateformat($reqData['verify_begin_date'], 'Ymd', '000000');
            $reqData['verify_end_date'] = dateformat($reqData['verify_end_date'], 'Ymd', '235959');
            // 订购商品不允许货到付款和自提
            if ('2' == $isCycle) {
                $reqData['deli_pay_flag'] = 0;
                $reqData['delivery_flag'] = 1;
            }
            // 上架 则活动结束时间必须大于当前时间
            $e_time = dateformat($reqData['end_date'], 'YmdHis');
            if (false === $e_time || $e_time < date('YmdHis')){
                $this->error("上架时商品销售结束时间必须大于等于当前时间");
            }    

                // 凭证结束时间必须大于活动结束时间
            if ($reqData['delivery_flag'] != '1') {
                if ((I('verify_time_type', 0, 'intval') == '0') && (date_clean($reqData['end_date']) > date_clean($reqData['verify_end_date'])))
                    $this->error("上架时商品兑换结束时间必须大于等于销售结束时间");
            }
            
            // 关联商品处理
            $rgoods_ids = I('rgoods_ids');
            if ($rgoods_ids != '') {
                $arr = explode(',', $rgoods_ids);
                $cnt = count($arr);
                foreach ($arr as $vId) {
                    $skuInfoList = $skuObj->getSkuEcshopList($vId, 
                        $this->nodeId);
                    // 判断是否有sku数据
                    if ($skuInfoList) {
                        $this->error('关联商品目前暂不支持SKU商品！');
                    }
                }
                if ($cnt > 8)
                    $this->error('关联商品最多只能选择8个！');
                
                $cnt2 = M()->table('tecshop_goods_ex a, tmarketing_info b')->where(
                    "b.batch_type = '31' and a.m_id = b.id and b.id in ({$rgoods_ids})")->count();
                if ($cnt != $cnt2)
                    $this->error('关联商品有误！');
            }
            
            // 图片处理
            $img_arr = array(
                'img1' => '', 
                'img2' => '', 
                'img3' => '');
            $i = 0;
            foreach ($img_arr as $k => &$v) {
                ++ $i;
                $tmp_name = $reqData['resp_' . $k];
                if ($tmp_name != '')
                    $v = $tmp_name;
            }
            unset($v);
            
            M()->startTrans();
            // =====================tmarketing_info更新============================
            $reqData['begin_date'] = dateformat($reqData['begin_date'], 'YmdHis');
            $reqData['end_date'] = dateformat($reqData['end_date'], 'YmdHis');
            
            $marketData = array(
                'start_time' => $reqData['begin_date'], 
                'end_time' => $reqData['end_date'], 
                'member_join_flag' => $reqData['member_join_flag'], 
                'fans_collect_url' => $reqData['fans_collect_url'], 
                'bonus_flag' => $reqData['bonus_flag'], 
                'deli_pay_flag' => strstr($reqData['delivery_flag'], '1') ? $reqData['deli_pay_flag'] : 0,  // 物流支持货到付款
                'integral_flag' => $reqData['integral_flag'], 
                'config_data' => strstr($reqData['delivery_flag'], '0') ||
                     $reqData['delivery_flag'] == '0' ? serialize(
                        array(
                            'send_gift' => $reqData['send_gift'])) : serialize(
                        array(
                            'send_gift' => '0')),  // 送礼开关
                    'status' => $puton_flag == 1 ? 1 : 2);
            $flag = M('tmarketing_info')->where("id = '{$marketInfo['id']}'")->save($marketData);

            if ($flag === false) {
                M()->rollback();
                $this->error('商品信息保存失败3！');
            }
            // =====================库存处理============================
            $goodsInfo = M('tbatch_info')->where("m_id = '{$m_id}'")->find();
            $goodsM = D('Goods');
            if ('1' != $reqData['is_sku']) {
                $flag = $goodsM->adjust_batch_storagenum($goodsInfo['id'], 
                    $reqData['storage_num'], $m_id, '12');
                if ($flag === false) {
                    M()->rollback();
                    $this->error($goodsM->getError());
                }
            }
            // =====================tbatch_info更新==========================
            $batchData = array();
            $batchData['batch_amt'] = $reqData['batch_amt'];
            $batchData['begin_time'] = dateformat($reqData['begin_date'], 'YmdHis');
            $batchData['end_time'] = dateformat($reqData['end_date'], 'YmdHis');
            
            if ($a !== false) {
                $batchData['info_title'] = get_scalar_val($reqData['mms_title'],'商品');
                $batchData['use_rule'] = $reqData['mms_info'];
                $batchData['verify_begin_date'] = $reqData['verify_time_type'] ==
                     '0' ? dateformat($reqData['verify_begin_date'], 'Ymd', '000000') : $reqData['verify_begin_days'];
                $batchData['verify_end_date'] = $reqData['verify_time_type'] ==
                     '0' ? dateformat($reqData['verify_end_date'], 'Ymd', '235959') : $reqData['verify_end_days'];
                $batchData['verify_begin_type'] = $reqData['verify_time_type'];
                $batchData['verify_end_type'] = $reqData['verify_time_type'];
                // 上架 则活动兑换时间必须大于当前时间
                $veTime = dateformat($batchData['verify_end_date'], 'Ymd', '235959');
                if (false === $veTime || $veTime < date('YmdHis')){
                    $this->error("上架时商品兑换结束时间必须大于等于当前时间");
                }   
            }
            if ($reqData['storage_type'] == '0') {
                $batchData['storage_num'] = - 1;
                $batchData['remain_num'] = - 1;
            } else {
                $batchData['storage_num'] = $reqData['storage_num'];
                $batchData['remain_num'] = $reqData['storage_num'] -
                     $minStorageNum;
            }
            if ($puton_flag == 1) {
                $batchData['status'] = '0';
            }
            if($startUp == 1  && in_array($goodsInfo['goods_type'],array('6'))){
                $sms_text = I('cusMsg','');
                if(empty($sms_text)){
                    M()->rollback();
                    $this->error('短信内容不能空！');
                }else{
                    $batchData['sms_text'] = $sms_text;
                }
            }

            if ($batchData) {
                $flag = M('tbatch_info')->where("id = '{$goodsInfo['id']}'")->save(
                    $batchData);
                if ($flag === false) {
                    M()->rollback();
                    $this->error('商品信息保存失败1！');
                }
            }
            
            // =====================tecshop_goods_ex更新==========================
            //销售比例
            if($reqData['saleProportion'] > 0){
                $configArray['saleProportion'] =  $reqData['saleProportion'];
            }else{
                $configArray['saleProportion'] =  1;
            }
            $exData = array(
                'goods_desc' => $reqData['goods_desc'], 
                'ecshop_classify' => $reqData['classify'], 
                'wap_info' => $reqData['wap_info'], 
                'show_picture1' => $img_arr['img1'] ? $img_arr['img1'] : $goodsDataEx['show_picture1'], 
                'show_picture2' => $img_arr['img2'] ? $img_arr['img2'] : $goodsDataEx['show_picture2'], 
                'show_picture3' => $img_arr['img3'] ? $img_arr['img3'] : $goodsDataEx['show_picture3'], 
                'relation_goods' => $rgoods_ids, 
                'market_price' => $reqData['market_price'], 
                'day_buy_num' => $reqData['day_buy_num_flag'] == '0' ? - 1 : $reqData['day_buy_num'], 
                'person_buy_num' => $reqData['person_buy_num_flag'] == '0' ? - 1 : $reqData['person_buy_num'], 
                'market_show' => $reqData['market_show'], 
                'delivery_flag' => $reqData['delivery_flag'], 
                'config_data' => json_encode($configArray),
                'purchase_time_limit' => $reqData['purchase_time_limit']);
            $flag = M('tecshop_goods_ex')->where("id = '{$goodsInfoEx['id']}'")->save(
                $exData);
            if ($flag === false) {
                M()->rollback();
                $this->error('商品信息保存失败2！' . M()->_sql());
            }
            
            // =====================tmarketing_change_trace记录编辑流水==========================
            $changeData = array(
                'batch_id' => $marketInfo['id'], 
                'batch_type' => '31', 
                'name' => $marketInfo['name'], 
                'start_time' => dateformat($reqData['begin_date'], 'YmdHis'), 
                'end_time' => dateformat($reqData['end_date'], 'YmdHis'), 
                'memo' => $reqData['goods_desc'], 
                'wap_info' => $reqData['wap_info'], 
                'defined_one_name' => $reqData['delivery_flag'], 
                // 'defined_two_name'=>$showFlag,
                'defined_three_name' => $reqData['person_buy_num_flag'] == '0' ? - 1 : $reqData['person_buy_num'], 
                'market_price' => $reqData['market_show'] == '1' ? $reqData['market_price'] : 0, 
                'group_price' => $reqData['batch_amt'], 
                'goods_num' => $reqData['storage_type'] == '1' ? $reqData['storage_num'] : - 1, 
                'buy_num' => $reqData['day_buy_num_flag'] == '0' ? 0 : $reqData['day_buy_num'], 
                'verify_begin_date' => $reqData['verify_time_type'] == '0' ? dateformat($reqData['verify_begin_date'], 'Ymd', '000000') : $reqData['verify_begin_days'], 
                'verify_end_date' => $reqData['verify_time_type'] == '0' ? dateformat($reqData['verify_end_date'], 'Ymd', '235959') : $reqData['verify_end_days'], 
                'verify_begin_type' => $reqData['verify_time_type'], 
                'verify_end_type' => $reqData['verify_time_type'], 
                'modify_time' => date('YmdHis'), 
                'modify_type' => '1', 
                'oper_id' => $this->user_id);
            $flag = M('tmarketing_change_trace')->add($changeData);
            if ($flag === false) {
                M()->rollback();
                $this->error('商品信息编辑记录保存失败！' . M()->_sql());
            }
            // 添加sku上架信息 ADD SKU START
            if ('1' === $reqData['is_sku']) {
                // 创建sku链接
                $skuObj = D('Sku', 'Service');
                $info = $skuObj->addGoodsSkuInfo($skuMarkPrice, $this->nodeId, 
                    $m_id, $goodsInfo['id'], true);
                if (false == $info) {
                    M()->rollback();
                    $this->error($skuObj->getError());
                }
            }
            // ADD SKU END
            M()->commit();
            // 爱蒂宝
            if ($this->node_id == C('adb.node_id')) {
                $adbb_id = I("adbb_id");
                $openStores = I("openStores");
                $param = array(
                    'node_id' => $this->node_id, 
                    'b_id' => $adbb_id, 
                    'openStores' => $openStores);
                B('AdbGoodsEditSave', $param);
            }
            node_log('旺财小店商品编辑', print_r($reqData, true));
            // $this->success('商品编辑成功！'.($puton_flag == 1 ? '商品上架成功！': ''));
            $this->ajaxReturn(1);
            exit();
        }
        
        $basegoodsInfo = M('tgoods_info')->where(
            "goods_id = '{$goodsInfo['goods_id']}'")->find();
        
        // 关联商品
        $rgoodsList = array();
        if ($goodsInfoEx['relation_goods'] != '') {
            $rgoodsList = M()->table(
                'tecshop_goods_ex a, tmarketing_info b, tbatch_info c, tgoods_info g')->where(
                "b.batch_type = '31' and a.m_id = b.id and a.b_id = c.id and c.goods_id = g.goods_id and b.id in ({$goodsInfoEx['relation_goods']})")->field(
                'b.id, g.goods_name, g.goods_image')->select();
        }
        $this->assign('id', $m_id);
        // 获取送礼信息
        $sendGift = D('MarketInfo')->getSendGiftTage($marketInfo);
        $this->assign('sendGift', $sendGift);
        $node_info = M('tnode_info')->where(
            array(
                'node_id' => $this->node_id))->find();
        $this->assign('node_info', $node_info);
        $this->assign('startUp', $startUp);
        $this->assign('cusMsg', $goodsInfo['sms_text']);
        $this->assign('rgoodsList', $rgoodsList);
        $this->assign('marketInfo', $marketInfo);
        $this->assign('goodsInfo', $goodsInfo);
        $this->assign('isCycle', $isCycle); // 是否订购
        $this->assign('goodsInfoEx', $goodsInfoEx);
        $this->assign('goodsInfoExConfig', json_decode($goodsInfoEx['config_data'], true));
        $this->assign("skuDetail", $skuDetail);
        $this->assign("skutype", 
            $skuObj->makeSkuType($goodsSkuTypeList, $goodsSkuDetailList));
        $this->assign('isSku', $isSku);
        $this->assign('basegoodsInfo', $basegoodsInfo);
        $this->assign('puton_flag', $puton_flag);
        $this->display();
    }

    public function deleteAjax() {
        // 数据库中增加新字段 is_delete
        M()->startTrans();
        $batch_id = I('id');
        $data['is_delete'] = 1;
        $map = array(
            'm_id' => $batch_id, 
            'status' => array(
                'neq', 
                '0'), 
            'node_id' => $this->node_id);
        // 因为过期没有处理is_commend字段，这里要同步处理一下
        $tecshopData = array(
            'is_commend' => '9');
        $flag = M('tecshop_goods_ex')->where("m_id = '{$batch_id}'")->save(
            $tecshopData);
        if ($flag === false) {
            M()->rollback();
            $this->ajaxReturn(0, "删除失败！", 0);
        }
        // 如果是上架中的，不能被删除
        $res = M("tbatch_info")->where($map)->save($data);
        if ($res) {
            M()->commit();
            $this->ajaxReturn(1, "删除成功！", 1);
        } else {
            M()->rollback();
            $this->ajaxReturn(0, "删除失败,商品正在上架中！", 0);
        }
    }

    public function View() {
        $m_id = I('id', 0, 'intval');
        $puton_flag = I('puton_flag', 0, 'intval');
        $preview_flag = I('preview_flag', 0, 'intval');
        if ($m_id == 0)
            $this->error('参数错误！');
        
        $map = array(
            'node_id' => $this->nodeId, 
            'id' => $m_id, 
            'batch_type' => '31');
        $marketInfo = M('tmarketing_info')->where($map)->find();
        if (! $marketInfo)
            $this->error('参数错误！');
        
        $goodsInfo = M('tbatch_info')->where("m_id = '{$m_id}'")->find();
        if($_GET['adbtest']== 1){
            dump($goodsInfo);exit;
        }
        $goodsInfoEx = M('tecshop_goods_ex')->where("m_id = '{$m_id}'")->find();
        $basegoodsInfo = M('tgoods_info')->where(
            "goods_id = '{$goodsInfo['goods_id']}'")->find();
        if ($this->nodeId == C('adb.node_id')) {
            $storesInfo = M()->table("tfb_adb_goods_store tags")->field(
                "ifnull(ti.store_name,'爱蒂宝总店') as store_name,ti.principal_name,ti.address,ti.store_phone")->join(
                "tstore_info ti on ti.store_id=tags.store_id")->where(
                "tags.b_id='{$goodsInfo['id']}'")->select();
            $this->assign('storesInfo', $storesInfo);
        }
        
        // 关联商品
        $rgoodsList = array();
        if ($goodsInfoEx['relation_goods'] != '') {
            $rgoodsList = M()->table(
                'tecshop_goods_ex a, tmarketing_info b, tbatch_info c, tgoods_info g')->where(
                "b.batch_type = '31' and a.m_id = b.id and a.b_id = c.id and c.goods_id = g.goods_id and b.id in ({$goodsInfoEx['relation_goods']})")->field(
                'b.id, g.goods_name, g.goods_image')->select();
        }
        
        // 创建sku信息
        $skuObj = D('Sku', 'Service');
        unset($map);
        $skuInfoList = $skuObj->getSkuEcshopList($m_id, $this->nodeId);
        // 判断是否有sku数据
        $goodsSkuTypeList = '';
        $goodsSkuDetailList = '';
        $skuDetail = '';
        
        if (NULL === $skuInfoList) {
            $isSku = false;
        } else {
            $isSku = true;
            // 分离商品表中的规格和规格值ID
            $goods_sku_list = $skuObj->getReloadSku($skuInfoList);
            // 取得规格值表信息
            if (is_array($goods_sku_list['list']))
                $goodsSkuDetailList = $skuObj->getSkuDetailList(
                    $goods_sku_list['list']);
                
                // 取得规格表信息
            if (is_array($goodsSkuDetailList))
                $goodsSkuTypeList = $skuObj->getSkuTypeList($goodsSkuDetailList);
                
                // 价格列表
            $skuDetail = $skuObj->makeSkuList($skuInfoList);
        }
        
        // 获取送礼信息
        $sendGift = D('MarketInfo')->getSendGiftTage($marketInfo);
        $this->assign('sendGift', $sendGift);
        
        $this->assign("skutype", 
            $skuObj->makeSkuType($goodsSkuTypeList, $goodsSkuDetailList));
        $this->assign("skuDetail", $skuDetail);
        $this->assign('isSku', $isSku);
        
        $this->assign('rgoodsList', $rgoodsList);
        $this->assign('marketInfo', $marketInfo);
        $this->assign('goodsInfo', $goodsInfo);
        $this->assign('goodsInfoEx', $goodsInfoEx);
        $this->assign('basegoodsInfo', $basegoodsInfo);
        $this->assign('puton_flag', $puton_flag);
        $this->assign('m_id', $m_id);
        $tpl_name = null;
        if ($preview_flag == 1) {
            $tpl_name = 'putOnPreview';
        }

        $sql = "SELECT add_time FROM `tbatch_channel` WHERE batch_id=$m_id";
        $result = M()->query($sql);
        $time = substr($result['0']['add_time'],4,10);//截取时间中的月日时分秒
        $QRcodeUrl = $this->goodsQrcodeDir . '/' . $time . '.png'; //这个是二维码的路径

        $this->assign('qrcodeTime',$time);
        $this->assign('url',$QRcodeUrl);
        $this->display($tpl_name);
    }

    public function putOnSubmit() {
        // 数据校验
        // $classify_arr = M('tecshop_classify')->where("node_id =
        // '{$this->nodeId}'")->getField('id', NULL);
        $rules = array(
            'goods_id' => array(
                'null' => false, 
                'name' => '营销活动号'), 
            'classify' => array(
                'null' => false, 
                'strtype' => 'int', 
                'name' => '所属分组', 
                'inarr' => array_keys($this->classify_arr)), 
            'batch_amt' => array(
                'null' => true, 
                'strtype' => 'number', 
                'name' => '销售价格'),
            'saleProportion' => array(
                'null' => true, 
                'strtype' => 'number', 
                'name' => '销售比例'),
            'storage_type' => array(
                'null' => false, 
                'strtype' => 'int', 
                'name' => '总库存', 
                'inarr' => array(
                    '0', 
                    '1')), 
            'market_show' => array(
                'null' => true, 
                'strtype' => 'int', 
                'name' => '商品市场价格', 
                'inarr' => array(
                    '0', 
                    '1')), 
            'day_buy_num_flag' => array(
                'null' => false, 
                'strtype' => 'int', 
                'name' => '商品每日限购', 
                'inarr' => array(
                    '0', 
                    '1')), 
            'person_buy_num_flag' => array(
                'null' => false, 
                'strtype' => 'int', 
                'name' => '每人限购', 
                'inarr' => array(
                    '0', 
                    '1')), 
            'bonus_flag' => array(
                'null' => false, 
                'strtype' => 'int', 
                'name' => '参与人品红包', 
                'inarr' => array(
                    '0', 
                    '1')), 
            'deli_pay_flag' => array(
                'null' => false, 
                'strtype' => 'int', 
                'name' => '支持货到付款', 
                'inarr' => array(
                    '0', 
                    '1')), 
            'integral_flag' => array(
                'null' => true, 
                'strtype' => 'int', 
                'name' => '参与积分购物', 
                'inarr' => array(
                    '0', 
                    '1')), 
            // 'delivery_flag' => array('null'=>false,'name'=>'配送方式'),
            // 粉丝专享配置
            'member_join_flag' => array(
                'null' => false, 
                'strtype' => 'int', 
                'name' => '购买对象限制', 
                'inarr' => array(
                    '0', 
                    '1')), 
            'fans_collect_url' => array(
                'null' => true, 
                'strtype' => 'string', 
                'name' => '引导页链接'), 
            'purchase_time_limit' => array(
                'null' => false, 
                'strtype' => 'int', 
                'name' => '是否限时购买', 
                'inarr' => array(
                    '0', 
                    '1')), 
            
            'goods_desc' => array(
                'null' => false, 
                'maxlen_cn' => '200', 
                'name' => '商品描述'), 
            'wap_info' => array(
                'null' => false/*,'maxlen_cn'=>'10000'*/, 'name' => '商品描述详情'), 
            
            'resp_img1' => array(
                'null' => false, 
                'name' => '第一张图片'), 
            'resp_img2' => array(
                'null' => true, 
                'name' => '第一张图片'), 
            'resp_img3' => array(
                'null' => true, 
                'name' => '第一张图片'), 
            // sku附加项
            'is_sku' => array(
                'null' => true, 
                'name' => '是否有sku信息'), 
            'data_price_info' => array(
                'null' => true, 
                'name' => 'sku价格列表'), 
            'count_num' => array(
                'null' => true, 
                'name' => 'sku总库存'));
        
        $reqData = $this->_verifyReqData($rules);
        //销售比例处理
        if($reqData['saleProportion'] < 1){
            $reqData['saleProportion'] = 1;
        }
        // sku提交
        if ('true' === $reqData['is_sku']) {
            $skuMarkPrice = json_decode(
                html_entity_decode($reqData['data_price_info']), true);
            if (! $skuMarkPrice) {
                $this->error("该商品并不是sku商品");
            }
        } else {
            if ('' === $reqData['batch_amt'])
                $this->error("请输入销售价格");
            if ('' === $reqData['market_show'])
                $this->error("请输入商品市场价格");
        }
        // 判断积分是否开启
        if (! $this->isInterGral) {
            $reqData['integral_flag'] = 0;
        }
        $time_fmt = 'Y-m-d H:i:s';
        $rules = array(
            'begin_date' => array(
                'null' => false, 
                'strtype' => 'datetime', 
                'format' => $time_fmt, 
                'name' => '商品销售开始时间'), 
            'end_date' => array(
                'null' => false, 
                'strtype' => 'datetime', 
                'format' => $time_fmt, 
                'name' => '商品销售结束时间'));
        $reqData = array_merge($reqData, $this->_verifyReqData($rules));
            $reqData['begin_date'] = dateformat($reqData['begin_date'], 'YmdHis');
            $reqData['end_date'] = dateformat($reqData['end_date'], 'YmdHis');
        // 微信粉丝专享判断
        if (($reqData['member_join_flag'] == '1') &&
             ! $reqData['fans_collect_url'])
            $this->error("购买对象限制为微信粉丝时请填入引导页链接");
        
        $reqData['delivery_flag'] = I('delivery_flag');
        
        // if($reqData['end_date'] > '20140930')
        // $this->error('商品销售结束日期不能大于9月30日');
        // 自提才需要配置短彩信内容、配置验证时间
        $rules = array();
        if (! empty($reqData['delivery_flag'])) {
            $reqData['delivery_flag'] = implode('-', $reqData['delivery_flag']);
        } else
            $this->error("未选择是否配送");
        $a = strstr($reqData['delivery_flag'], '0');
        if ($a !== false) {
            $rules['verify_time_type'] = array(
                'null' => false, 
                'strtype' => 'int', 
                'name' => '验证时间', 
                'inarr' => array(
                    '0', 
                    '1'));
            /*
            $rules['mms_title'] = array(
                'null' => false, 
                'maxlen_cn' => '10', 
                'name' => '彩信标题');
            */
            $rules['mms_info'] = array(
                'null' => false, 
                'maxlen_cn' => '100', 
                'name' => '商品使用说明');
            $rules['send_gift'] = array(
                'null' => false, 
                'strtype' => 'int', 
                'name' => '支持送礼', 
                'inarr' => array(
                    '0', 
                    '1'));
            
            if (I('verify_time_type', 0, 'intval') == '0') {
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
        }
        if ('true' === $reqData['is_sku']) {
            if ('-1' == $reqData['count_num']) {
                $reqData['storage_num'] = - 1;
                $reqData['storage_type'] = 0;
            } else {
                $reqData['storage_type'] = 1;
                $reqData['storage_num'] = (int) $reqData['count_num'];
            }
        } else {
            // 库存
            if ($reqData['storage_type'] == '1')
                $rules['storage_num'] = array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'minval' => '1', 
                    'name' => '总库存');
        }
        // 市场价格
        if ($reqData['market_show'] == '1')
            $rules['market_price'] = array(
                'null' => false, 
                'strtype' => 'number', 
                'minval' => 0, 
                'name' => '商品市场价格');
            // 每日限购
        if ($reqData['day_buy_num_flag'] == '1')
            $rules['day_buy_num'] = array(
                'null' => false, 
                'strtype' => 'int', 
                'minval' => '1', 
                'name' => '商品每日限购');
            // 每人限购
        if ($reqData['person_buy_num_flag'] == '1')
            $rules['person_buy_num'] = array(
                'null' => false, 
                'strtype' => 'int', 
                'minval' => '1', 
                'name' => '每人限购');
        
        $reqData = array_merge($reqData, $this->_verifyReqData($rules));
        $reqData['verify_begin_date'] = dateformat($reqData['verify_begin_date'], 'Ymd', '000000');
        $reqData['verify_end_date'] = dateformat($reqData['verify_end_date'], 'Ymd', '235959');
 
        // 凭证有效期校验
        
        if ($a !== false) {
            if ($reqData['verify_time_type'] == '0' &&
                 $reqData['verify_begin_date'] > $reqData['verify_end_date'])
                $this->error('验证结束时间不能大于验证开始时间');
            if ($reqData['verify_time_type'] == '1' &&
                 $reqData['verify_begin_days'] > $reqData['verify_end_days'])
                $this->error('验证结束时间不能大于验证开始时间');
        }
        
        // 上架 则活动结束时间必须大于当前时间
        $e_time = date_clean($reqData['end_date']);
        if (false === $e_time || $e_time < date('YmdHis'))
            $this->error("上架时商品销售结束时间必须大于等于当前时间", U('index'));
            
            // 凭证结束时间必须大于活动结束时间
        if ($reqData['delivery_flag'] != '1') {
            if ((I('verify_time_type', 0, 'intval') == '0') &&
                 (date_clean($reqData['end_date']) >
                 date_clean($reqData['verify_end_date'])))
                $this->error("上架时商品兑换结束时间必须大于等于销售结束时间", U('index'));
        }
        // 查询渠道信息
        $map = array(
            'node_id' => $this->nodeId, 
            'type' => 4, 
            'sns_type' => 46);
        $channelInfo = M('tchannel')->where($map)->find();
        if (! $channelInfo)
            $this->error('没有旺财小店渠道！');
            
            // 关联商品处理
        $rgoods_ids = I('rgoods_ids');
        if ($rgoods_ids != '') {
            $arr = explode(',', $rgoods_ids);
            $cnt = count($arr);
            if ($cnt > 8)
                $this->error('关联商品最多只能选择8个！');
            
            $cnt2 = M()->table('tecshop_goods_ex a, tmarketing_info b')->where(
                "b.batch_type = '31' and a.m_id = b.id and b.id in ({$rgoods_ids})")->count();
            if ($cnt != $cnt2)
                $this->error('关联商品有误！');
        }
        
        // 图片处理
        $img_arr = array(
            'img1' => '', 
            'img2' => '', 
            'img3' => '');
        $i = 0;
        foreach ($img_arr as $k => &$v) {
            ++ $i;
            $tmp_name = $reqData['resp_' . $k];
            if ($tmp_name != '')
                $v = $tmp_name;
        }
        unset($v);

        //自定义短信内容（开关）
        $startUp = M('tnode_info')->where(array('node_id'=>$this->node_id))->getField('custom_sms_flag');
        M()->startTrans();
        try {
            // 商品初步校验
            $map = array(
                'node_id' => $this->nodeId, 
                'goods_id' => $reqData['goods_id']);
            // 'goods_type' => '6'
            
            $goodsInfo = M('tgoods_info')->where($map)->find();
            if (! $goodsInfo)
                throw new Exception('商品信息错误！');
                // 验证是否为电子卷商品 电子卷商品只能自提
            if ('6' != $goodsInfo['goods_type'])
                $reqData['delivery_flag'] = 0;
                // 库存校验
            if ($goodsInfo['storage_type'] == '1' &&
                 $reqData['storage_type'] == '0')
                throw new Exception("该商品的总库存不能设置为不限！");
            if ($goodsInfo['storage_type'] == '1' &&
                 $reqData['goods_count'] > $goodsInfo['remain_num'])
                throw new Exception("商品库存不足！");
                // 订购商品不允许货到付款和自提
            if ('2' == $goodsInfo['is_order']) {
                $reqData['deli_pay_flag'] = 0;
                $reqData['delivery_flag'] = 1;
            }
            
            $marketData = array(
                'batch_id' => '0', 
                'batch_type' => '31', 
                'node_id' => $goodsInfo['node_id'], 
                'name' => $goodsInfo['goods_name'], 
                'start_time' => $reqData['begin_date'], 
                'end_time' => $reqData['end_date'], 
                'add_time' => date('YmdHis'), 
                'wap_info' => $reqData['wap_info'], 
                'status' => '1', 
                'member_join_flag' => $reqData['member_join_flag'], 
                'fans_collect_url' => $reqData['fans_collect_url'], 
                'bonus_flag' => $reqData['bonus_flag'], 
                'config_data' => strstr($reqData['delivery_flag'], '0') ||
                     $reqData['delivery_flag'] == '0' ? serialize(
                        array(
                            'send_gift' => $reqData['send_gift'])) : serialize(
                        array(
                            'send_gift' => '0')),  // 送礼开关
                    'deli_pay_flag' => strstr($reqData['delivery_flag'], '1') ? $reqData['deli_pay_flag'] : 0,  // 物流支持货到付款
                    'integral_flag' => $reqData['integral_flag']);
            
            $m_id = M('tmarketing_info')->add($marketData);
            if ($m_id === false)
                throw new Exception("营销活动保存失败！");
                
                // 扣减库存
            if ($reqData['storage_type'] == '1') {
                $goodsM = D('Goods');
                $flag = $goodsM->storagenum_reduc($goodsInfo['goods_id'], 
                    $reqData['storage_num'], $m_id, '11', '');
                if ($flag === false)
                    throw new Exception($goodsM->getError());
            }
            
            $goodsData = array(
                'batch_no' => $goodsInfo['batch_no'], 
                'batch_short_name' => $goodsInfo['goods_name'], 
                'batch_name' => $goodsInfo['goods_name'], 
                'node_id' => $goodsInfo['node_id'], 
                'user_id' => $this->user_id, 
                'batch_class' => $goodsInfo['goods_type'], 
                'batch_type' => '0', 
                'batch_img' => $goodsInfo['goods_image'], 
                'validate_times' => '1', 
                'batch_amt' => $reqData['batch_amt'], 
                'begin_time' => $reqData['begin_date'], 
                'end_time' => $reqData['end_date'], 
                'send_begin_date' => $reqData['begin_date'], 
                'send_end_date' => $reqData['end_date'], 
                'add_time' => date('YmdHis'), 
                'node_pos_group' => $goodsInfo['pos_group'], 
                'node_pos_type' => $goodsInfo['pos_group_type'], 
                'batch_desc' => $goodsInfo['goods_desc'], 
                'goods_id' => $reqData['goods_id'], 
                'storage_num' => $reqData['storage_type'] == '1' ? $reqData['storage_num'] : - 1, 
                'remain_num' => $reqData['storage_type'] == '1' ? $reqData['storage_num'] : - 1, 
                'print_text' => $goodsInfo['print_text'], 
                'm_id' => $m_id, 
                'validate_type' => $goodsInfo['validate_type'],
                'info_title' => get_scalar_val($reqData['mms_title'],'商品'));
            
            if ($a !== false) {
                $goodsData = array_merge($goodsData, 
                    array(
//                        'info_title' => get_scalar_val($reqData['mms_title'],'商品'),
                        'use_rule' => $reqData['mms_info'], 
                        'verify_begin_date' => $reqData['verify_time_type'] ==
                             '0' ? dateformat($reqData['verify_begin_date'], 'Ymd', '000000') : $reqData['verify_begin_days'], 
                            'verify_end_date' => $reqData['verify_time_type'] ==
                             '0' ? dateformat($reqData['verify_end_date'], 'Ymd', '235959') : $reqData['verify_end_days'], 
                            'verify_begin_type' => $reqData['verify_time_type'], 
                            'verify_end_type' => $reqData['verify_time_type']));
            }
            //自定义短信内容
            if($startUp == 1 && in_array($goodsInfo['goods_type'],array('6'))){
                $sms_text = I('cusMsg','');
                if(empty($sms_text)){
                    throw new Exception('短信内容不能空！');
                }else{
                    $goodsData['sms_text'] = $sms_text;
                }
            }
            
            $b_id = M('tbatch_info')->add($goodsData);
            if ($b_id === false)
                throw new Exception("商品信息保存失败！");
                // 爱蒂宝
            if ($this->node_id == C('adb.node_id')) {
                $stores = I("openStores", '');
                $param = array(
                    'node_id' => $this->node_id, 
                    'stores' => $stores, 
                    'b_id' => $b_id);
                B('AdbGoodsPutOn', $param);
            }
            if ($goodsInfo['goods_type'] != '6') {
                $reqData['delivery_flag'] = '0';
            }
            
            //销售比例
            if($reqData['saleProportion'] > 0){
                $configArray['saleProportion'] =  $reqData['saleProportion'];
            }else{
                $configArray['saleProportion'] =  1;
            }
            
            $goodsDataEx = array(
                'node_id' => $this->nodeId, 
                'b_id' => $b_id, 
                'm_id' => $m_id, 
                'ecshop_classify' => $reqData['classify'], 
                'relation_goods' => $relation_goods, 
                // , 'is_commend' => '9'
                'show_picture1' => $img_arr['img1'], 
                'show_picture2' => $img_arr['img2'], 
                'show_picture3' => $img_arr['img3'], 
                'wap_info' => $reqData['wap_info'], 
                'goods_desc' => $reqData['goods_desc'], 
                'market_price' => $reqData['market_price'], 
                'day_buy_num' => $reqData['day_buy_num_flag'] == '0' ? - 1 : $reqData['day_buy_num'], 
                'person_buy_num' => $reqData['person_buy_num_flag'] == '0' ? - 1 : $reqData['person_buy_num'], 
                'delivery_flag' => $reqData['delivery_flag'], 
                'market_show' => $reqData['market_show'], 
                'relation_goods' => $rgoods_ids, 
                'config_data' => json_encode($configArray),
                'purchase_time_limit' => $reqData['purchase_time_limit']);
            $flag = M('tecshop_goods_ex')->add($goodsDataEx);
            if ($flag === false)
                throw new Exception("商品信息保存失败！");
                // 添加sku上架信息
            if ('true' === $reqData['is_sku']) {
                // 创建sku链接
                $skuObj = D('Sku', 'Service');
                $info = $skuObj->addGoodsSkuInfo($skuMarkPrice, $this->nodeId, 
                    $m_id, $b_id);
                if (false == $info) {
                    M()->rollback();
                    $this->error($skuObj->getError());
                }
            }
            // 记录编辑流水
            $changeData = array(
                'batch_id' => $m_id, 
                'batch_type' => '31', 
                'name' => $goodsInfo['goods_name'], 
                'start_time' => $reqData['begin_date'], 
                'end_time' => $reqData['end_date'], 
                'memo' => $reqData['goods_desc'], 
                'wap_info' => $reqData['wap_info'], 
                'defined_one_name' => $reqData['delivery_flag'], 
                // 'defined_two_name'=>$showFlag,
                'defined_three_name' => $reqData['person_buy_num_flag'] == '0' ? - 1 : $reqData['person_buy_num'], 
                'market_price' => $reqData['market_price'], 
                'group_price' => $reqData['batch_amt'], 
                'goods_num' => $reqData['storage_type'] == '1' ? $reqData['storage_num'] : - 1, 
                'buy_num' => $reqData['day_buy_num_flag'] == '0' ? 0 : $reqData['day_buy_num'], 
                'verify_begin_date' => $reqData['verify_time_type'] == '0' ? dateformat($reqData['verify_begin_date'], 'Ymd', '000000') : $reqData['verify_begin_days'], 
                'verify_end_date' => $reqData['verify_time_type'] == '0' ? dateformat($reqData['verify_end_date'], 'Ymd', '235959') : $reqData['verify_end_days'], 
                'verify_begin_type' => $reqData['verify_time_type'], 
                'verify_end_type' => $reqData['verify_time_type'], 
                'modify_time' => date('YmdHis'), 
                'modify_type' => '0', 
                'oper_id' => $this->user_id);
            $flag = M('tmarketing_change_trace')->add($changeData);
            if ($flag === false)
                throw new Exception("商品信息编辑记录保存失败！");
            
            $batchChannelData = array(
                'batch_type' => '31', 
                'batch_id' => $m_id, 
                'channel_id' => $channelInfo['id'], 
                'add_time' => date('YmdHis'), 
                'node_id' => $this->nodeId);
            $label_id = M('tbatch_channel')->add($batchChannelData);
            if ($label_id === false)
                throw new Exception("商品信息保存失败！");
            
            $goodsDataEx = array(
                'label_id' => $label_id);
            $flag = M('tecshop_goods_ex')->where("b_id = '$b_id'")->save(
                $goodsDataEx);
            if ($flag === false)
                throw new Exception("商品渠道信息保存失败！");
        } 

        catch (Exception $e) {
            M()->rollback();
            $this->error($e->getMessage());
        }
        M()->commit();
        node_log('旺财小店商品上架', print_r($reqData, true));
        // $this->success('商品上架成功');
        $this->ajaxReturn(1);
    }

    public function QRCode() {
        /**
         * Task : #16557 上架成功提示页面添加商品二维码，并添加下载功能；订单详情页添加商品二维码 Author: Zhaobl
         * Date: 2015/12/25
         */
        $id = I('id');
        $status = I('status'); // 如果是1 就是新上架成功的 id是真货
        $preview = I('preview'); // 若有 则是页面预览进来的
        $isdown = I('isdown'); // 若有 则是下载
        $filename = I('name');
        $goodsName = I('goodsName');//商品名称
        $time = I('qrcodeTime');
        
        import('@.Vendor.MakeCode') or die('include file fail.');
        $makecode = new MakeCode();
        
        if ($status == 1) {
            $qrcodeId = $id;
        } else {
            $sql = "SELECT a.id FROM tbatch_channel a
        LEFT JOIN tchannel b ON a.`channel_id` = b.`id`
        WHERE a.batch_type = '31' AND sns_type = '46' AND a.status = '1' AND a.batch_id = '$id'";
            
            $result = M()->query($sql);
            $qrcodeId = $result['0']['id'];
        }

        $url = $this->getQRcodeUrl($qrcodeId);
        
        if ($preview == 1) {
            header("Location: $url");
            exit();
        }
        $logourl = '';
        $color = '000000';
        if ($isdown == '1') {
            // 下载二维码
            $makecode->MakeCodeImg($url, true, '', $logourl,$filename.'_'.$time, $color);
        } else {
            // 展示二维码
            $makecode->MakeCodeImg($url, false, '', $logourl, '', $color);
        }
    }

    /**
     * 二维码初始化 将所有商户的所有商品统统生成二维码保存在服务器
     *
     * 上生产后 记得撤掉
     */
    public function qrcodeInitialize()
    {

        $sql = "SELECT * FROM
( SELECT l.add_time AS times,a.ecshop_classify,a.label_id,T.*,m.click_count,m.integral_flag,g.goods_image,x.sale_num,x.lock_num,g.is_sku FROM tecshop_goods_ex a LEFT JOIN
(SELECT o.node_id,oe.b_id,SUM(CASE WHEN o.pay_status IN('2','3') THEN oe.goods_num END) sale_num,SUM(CASE WHEN o.pay_status IN ('1') THEN oe.goods_num END) lock_num FROM ttg_order_info o,ttg_order_info_ex oe
WHERE o.order_id = oe.order_id AND o.order_type = '2' AND o.order_status = '0' GROUP BY o.node_id,oe.b_id) X
ON x.node_id = a.node_id AND x.b_id = a.b_id LEFT JOIN tbatch_info T ON a.node_id=T.node_id AND t.id = a.b_id LEFT JOIN tgoods_info g ON T.goods_id = g.goods_id LEFT JOIN tmarketing_info m ON m.id = a.m_id LEFT JOIN tbatch_channel l ON l.batch_id=m.id
WHERE ( T.is_delete = 0 )
) a ORDER BY STATUS ASC , id DESC";
        $result = M()->query($sql);

        if (! is_dir(APP_PATH . 'Upload/goods_qrcode')) {
            mkdir(APP_PATH . 'Upload/goods_qrcode', 0777, true);
        }
        $nodeidArr = array();//存放所有的node_id
        foreach($result as $k=>$v){
            $nodeidArr[] = $v['node_id'];
        }
        $nodeidArr = array_flip(array_flip($nodeidArr)); //去空值
        $nodeidArr=array_filter ($nodeidArr);//去重复值

        ini_set("max_execution_time", "0");//设置脚本最大执行时间为无限
        foreach($nodeidArr as $k=>$v){
            if (! is_dir(APP_PATH . 'Upload/goods_qrcode/'.$v)) {
                mkdir(APP_PATH . 'Upload/goods_qrcode/'.$v, 0777, true);
            }
            $qrcodeDir = APP_PATH . 'Upload/goods_qrcode/'.$v;
            foreach($result as $kk => $vv){
                if($v == $vv['node_id']){
                    $url = $this->getQRcodeUrl($vv['label_id']);//获取要存入二维码中的Url

                    $qrcodeId = $vv['label_id'];
                    $time = substr($vv['times'],4,10);

                    // 生成二维码存放在服务器
                    import('@.Vendor.phpqrcode.qrcode') or die('include file fail.');
                    $errorCorrectionLevel = "L";
                    $matrixPointSize = "4";

                    //$gbkName = iconv('utf-8', 'gb2312', $filename);
                    $logourl = $qrcodeDir . '/' . $time . '.png'; //这个是存放二维码的路径
                    //在服务器生成二维码
                    QRcode::png($url, $logourl, $errorCorrectionLevel,
                            $matrixPointSize, 2);

                    $logourl = $qrcodeDir . '/' . $time . '.png'; //这个是存放二维码的路径

                    $exModel = M("tecshop_goods_ex");
                    $data['goods_qrcode_path'] = $logourl;
                    $where['label_id'] = $qrcodeId;
                    $exModel -> where("label_id = $qrcodeId") -> save($data); //将二维码路径存入表中
                }
            }
        }
        $this->success('初始化完成，请从生产上将此脚本删除。');
    }

    public function downLoadZip()
    {
            // 批量下载员工二维码压缩文件
        $node_id = $this->nodeId;
        $filesnames = scandir($this->goodsQrcodeDir .'/');

        // 为避免生成的压缩文件里存在多级项目目录 因此在./下创建以node_id命名的目录
        // 将二维码复制到新目录 如果目录存在 则删掉 重建
        if(is_dir($node_id)){
            $this->deldir($node_id);
        }
        if(!mkdir($node_id,0777)){
            $this->error('网络错误！');
        }


        foreach ($filesnames as $k => $v) {

            if ($this->getFileType($v) == 'png' && strlen($v) == 14 ) {
                //如果后缀名为Png 并且文件为14位（10位文件名+1个点+3位后缀名）
                $v = iconv('gb2312', 'utf-8', $v);

                //根据创建时间拿出商品名
                $sql = "SELECT b.name FROM `tbatch_channel` a LEFT JOIN `tmarketing_info` b ON a.batch_id=b.id  WHERE  a.add_time LIKE '%".substr($v,0,10)."' ";
                $result = M()->query($sql);
                $name = $result ['0']['name'];
                copy($this->goodsQrcodeDir . '/' . $v, $node_id . '/' . iconv('utf-8', 'gb2312', $name).'_'.$v);
            }
        }

        // 生成压缩文件
        $zip = new ZipArchive();
        $fileName = $this->goodsQrcodeDir . '/' . 'ewm.zip';
        if ($zip->open($fileName, ZipArchive::OVERWRITE) === TRUE) {
            $filesnames = scandir($node_id);
            foreach ($filesnames as $k => $v) {
                if ($this->getFileType($v) == 'png') {
                    $zip->addFile($node_id . '/' . $v);
                }
            }
            $zip->close();
        }
        $this->deldir($node_id); // 删除目录
        $basename = '商品二维码.zip';

        $fileSize = filesize($fileName);
        header("Content-type: application/octet-stream");
        header(
                "Content-Disposition: attachment; filename=" .
                iconv('utf-8', 'gb2312', $basename));
        header('content-length:' . $fileSize);
        readfile($fileName);
    }

    public function getFileType($filename) {
        // 返回文件后缀名
        return substr($filename, strrpos($filename, '.') + 1);
    }

    public function deldir($dir) {
        // 删除目录
        $dh = opendir($dir);
        while ($file = readdir($dh)) {
            if ($file != "." && $file != "..") {
                $fullpath = $dir . "/" . $file;
                if (! is_dir($fullpath)) {
                    unlink($fullpath);
                } else {
                    $this->deldir($fullpath);
                }
            }
        }
        closedir($dh);
        if (rmdir($dir)) {
            return true;
        } else {
            return false;
        }
    }

    public function getQRcodeUrl($qrcodeId)
    {
        //检测要存入二维码中的url是否是全路径 不是全路径则拼接成全路径返回
        $url = U('Label/Store/detail',
                array(
                        'id' => $qrcodeId), false, false, true); // 要存入二维码的URL全路径

        $location = strpos($url, 'ttp://');
        if ($location != 1) {
            // 查找http在不在里面 在里面则为全路径 不在里面则拼接路径
            $url = 'http://' . $_SERVER['HTTP_HOST'] . $url;
        }
        return $url;
    }

    public function mkQRCode($url, $filename,$qrcodeId,$time) {
        // 上架成功的时候生成二维码存放在服务器
        import('@.Vendor.phpqrcode.qrcode') or die('include file fail.');
        $errorCorrectionLevel = "L";
        $matrixPointSize = "4";

        $logourl = $this->goodsQrcodeDir . '/' . $time . '.png'; //这个是存放二维码的路径
        //在服务器生成二维码
        QRcode::png($url, $logourl, $errorCorrectionLevel,
                $matrixPointSize, 2);

        $logourl = $this->goodsQrcodeDir . '/' . $time . '.png'; //这个是存放二维码的路径

        $exModel = M("tecshop_goods_ex");
        $data['goods_qrcode_path'] = $logourl;
        $where['label_id'] = $qrcodeId;
        $exModel -> where("label_id = $qrcodeId") -> save($data); //将二维码路径存入表中

    }

    public function successPage() {
        // 上架成功的页面
        $sql = "SELECT * FROM `tbatch_channel` WHERE node_id = '$this->nodeId' ORDER BY id DESC LIMIT 1";
        $result = M()->query($sql);
        $qrcodeId = $result['0']['id'];
        $time = substr($result['0']['add_time'],4,10);//截取时间中的月日时分秒
        
        $filename = $this->getShopName($qrcodeId);//根据id查询出商品名

        $url = $this->getQRcodeUrl($qrcodeId);//获取要存入二维码中的Url
        $this->mkQRCode($url,$filename,$qrcodeId,$time);//生成二维码存放在服务器

        $this->assign('qrcodeTime',$time);
        $this->assign('name', $filename);
        $this->assign('id', $qrcodeId);
        $this->display();
    }

    public function editResult() {
        // 二次上架成功的页面
        $id = I('id');
        if (! $id) {
            $this->error('参数有误');
        }
        
        $sql = "SELECT a.id FROM tbatch_channel a
        LEFT JOIN tchannel b ON a.`channel_id` = b.`id`
        WHERE a.batch_type = '31' AND sns_type = '46' AND a.status = '1' AND a.batch_id = '$id'";
        $result = M()->query($sql);
        $qrcodeId = $result['0']['id'];

        $filename = $this->getShopName($qrcodeId);
        $this->assign('name', $filename);
        $this->assign('id', $id);
        $this->display();
    }

    public function getShopName($qrcodeId) {
        // 查商品名
        $getNameSql = "SELECT tbatch_channel.batch_id FROM tbatch_channel LEFT JOIN tchannel ON channel_id= tchannel.id WHERE tbatch_channel.id='$qrcodeId'
";
        $result = M()->query($getNameSql);
        $batch_id = $result['0']['batch_id'];
        $getNameSql2 = "SELECT name FROM tmarketing_info WHERE node_id='$this->nodeId' AND id='$batch_id'";
        $result = M()->query($getNameSql2);
        $filename = $result['0']['name'];
        return $filename;
    }
    
    // 选择关联商品
    public function SelectRgoods() {
        $edit_id = I('edit_id');
        import('ORG.Util.Page'); // 导入分页类
        $isSku = I('is_sku') ? true : false; // 是否关联商品处理
        if ($this->node_id == C('adb.node_id')) {
            $store_id = I("store_id");
            $mapcount = D('Goods')->getGoodsInfoForSelect($this->node_id, $edit_id,false,false,$store_id);
        }else{
            $mapcount = D('Goods')->getGoodsInfoForSelect($this->node_id, $edit_id,false,$isSku);
        }      
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $limit = $Page->firstRow . ',' . $Page->listRows;
        if ($this->node_id == C('adb.node_id')) {
            $adb_flag = 1;
            $store_id = I("store_id");
            $list = D('Goods')->getGoodsInfoForSelect($this->node_id, $edit_id, $limit, $isSku, $store_id);
            $this->assign('adb_flag', $adb_flag);
        } else {
            $list = D('Goods')->getGoodsInfoForSelect($this->node_id, $edit_id, $limit, $isSku);
        }
        
        $show = $Page->show(); // 分页显示输出
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('list', $list); // 赋值分页输出
        $this->display();
    }

    public function ClassifyAdd() {
        $rules = array(
            'name' => array(
                'null' => false, 
                'maxlen_cn' => '6', 
                'name' => '分类名称'));
        $reqData = $this->_verifyReqData($rules);
        
        $model = M('tecshop_classify');
        $data = array(
            'node_id' => $this->nodeId, 
            'class_name' => $reqData['name'], 
            'add_time' => date('YmdHis'));
        if (in_array('', $data, true))
            $this->error('必要参数为空！');
        
        $map = array(
            'node_id' => $this->nodeId, 
            'class_name' => $reqData['name']);
        $cnt = $model->where($map)->find();
        if ($cnt > 0)
            $this->error('分类名称重复');
        
        $map = array(
            'node_id' => $this->nodeId);
        $data['sort'] = $model->where($map)->max('sort') + 1;
        
        $id = $model->add($data);
        if ($id === false)
            $this->error('分类添加失败！');
        
        node_log('添加旺财小店分类', print_r($data, true));
        $this->ajaxReturn(array(
            'id' => $id), '分类创建成功！', 1);
    }

    public function _verifyReqData($rules, $return = false, $method = 'post', $value_array = array()) {
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

    public function checkwxyx() {
        $info = M('tweixin_info')->where(['node_id' => $this->node_id, 'auth_flag' => '1'])->find();
        if ($info)
            $this->success('成功', array('type' => $info['account_type']));
        else
            $this->error('失败');
    }
}