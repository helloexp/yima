<?php

/**
 * 营销活动奖品处理：奖项添加、奖品添加、抽奖基础控制 kk@2014年7月8日11:46:49
 */
class SelectJpAction extends BaseAction {
    protected $tableName = '__NONE__';
    private $self_flag = false;
    // 多版本控制，
    private $hbtpybx_flag = false;

    /**
     * @var DrawLotteryBaseService
     */
    private $DrawLotteryBaseService;

    public function _initialize() {
        parent::_initialize();
        $this->hbtpybx_flag = $this->node_id == C('hbtpybx.node_id') ||
             $this->node_id == C('gstpybx.node_id') ||
             $this->node_id == C('sxtpybx.node_id');
        $this->assign('self_flag', $this->self_flag);
        import('@.Vendor.CommonConst') or die('include file fail.');
    }

    public function index() {
        $goodsModel = D('Goods');
        $sourceType = array(
            '0' => '自建', 
            '1' => '采购', 
            '3' => '微信卡券', 
            '4' => '采购', 
            '6' => '微信红包');
        $selectType = $goodsModel->getGoodsType('0,1,2,3,7,8,11,15'); // 卡券类型
        $sourceSelectStatus = '1'; // 是否显示来源搜索下拉框,当只有一种来源时隐藏掉
        $goodsTypeSelectStatus = '1'; // 是否显示卡券类型下拉框
        $mapGoodsType = "0,1,2,3,7,8,11,15,22"; // tgoods_info表筛选数据
        $map = "1=1";
        $show_source = I('show_source', null); // 显示指定来源的卡券 格式
                                               // show_sorurce='0,1,3,4',为空显示所有
        $show_type = I('show_type', null); // 显示指定卡券类型 格式
                                           // show_type='0,1,3,4',为空显示所有
        $store_mode = I('store_mode', null); // 微信卡券类型 1 投放 2 预存（只用于群发与自动回复）
        if ($store_mode == '2') {
            $time = time();
            $mapStoreMode = " and c.store_mode = 2 and c.auth_flag = 2 and c.date_begin_timestamp < " .
                 $time . " and c.date_end_timestamp >" . $time;
            $fieldStoreMode1 = ",0 as logo_url,0 as date_type,0 as date_begin_timestamp,0 as date_end_timestamp,0 as date_fixed_timestamp,0 as date_fixed_begin_timestamp,0 as color";
            $fieldStoreMode2 = ",c.logo_url,c.date_type,c.date_begin_timestamp,c.date_end_timestamp,c.date_fixed_timestamp,c.date_fixed_begin_timestamp,c.color";
        } else {
            $mapStoreMode = " and c.store_mode = 1 and c.auth_flag in('1','2')";
            $fieldStoreMode1 = "";
            $fieldStoreMode2 = "";
        }
        if (! empty($show_source) || $show_source == '0') {
            $map .= " and tg.source in({$show_source})";
            $showArr = explode(',', $show_source);
            $sourceType = array_intersect_key($sourceType, array_flip($showArr));
            if (count($showArr) == '1') {
                switch ($showArr[0]) {
                    case '3':
                        unset($selectType['7']);
                        unset($selectType['8']);
                        unset($selectType['11']);
                        break;
                    case '0':
                        unset($selectType['7']);
                        unset($selectType['8']);
                        unset($selectType['11']);
                        break;
                }
                $sourceSelectStatus = '0';
            }
        }
        if (! empty($show_type) || $show_type == '0') {
            $map .= " and tg.goods_type in({$show_type})";
            $typeArr = explode(',', $show_type);
            $selectType = array_intersect_key($selectType, array_flip($typeArr));
            if (count($typeArr) == '1') {
                $goodsTypeSelectStatus = '0';
            }
        }
        if ($show_type == '12') {
            $goodsTypeSelectStatus = '0';
            $mapGoodsType = "0,1,2,3,7,8,12";
        }
        $source = I('source', null, 'mysql_real_escape_string');
        switch ($source) {
            case '0':
                $map .= " and tg.source=0";
                unset($selectType['7']);
                unset($selectType['8']);
                unset($selectType['11']);
                break;
            case '1':
                $map .= " and tg.source in('1','4')";
                break;
            case '3':
                $map .= " and tg.source=3";
                unset($selectType['7']);
                unset($selectType['8']);
                unset($selectType['11']);
                break;
        }
        $goodsType = I('goods_type', null, 'mysql_real_escape_string');
        if ($goodsType == '6') {
            $mapGoodsType = '6';
            $map .= " and tg.source=0";
            $sourceSelectStatus = '0';
            $goodsTypeSelectStatus = '0';
        } elseif ($goodsType != '') {
            $map .= " and tg.goods_type={$goodsType}";
        }
        $goodsName = I('goods_name', null, 'mysql_real_escape_string');
        if ($goodsName != '') {
            $map .= " and tg.goods_name like '%{$goodsName}%'";
        }
        
        $getFieldArr = array(
            0 => "g.id,0 as quantity,0 as card_get_num,g.goods_id as card_id,g.goods_id,g.`goods_name`,g.is_order,g.goods_type,g.`source`,1 as auth_flag,g.add_time,g.pos_group_type,g.pos_group,
				g.storage_type,g.storage_num,g.remain_num,g.validate_type,g.market_price,g.goods_amt,g.goods_discount,g.goods_image,
				g.node_id,g.mms_title,g.mms_text,g.sms_text,g.print_text,case when g.verify_begin_type = g.verify_end_type then g.verify_begin_type else 0 end verify_time_type,
			    g.verify_begin_date, g.verify_end_date,g.goods_desc{$fieldStoreMode1}", 
            1 => "c.id as id,c.quantity,c.card_get_num,c.card_id,i.goods_id AS goods_id,c.title AS goods_name,1 as is_order,CASE c.card_type WHEN '0' THEN '3' WHEN '3' THEN '0' ELSE c.card_type END AS goods_type,'3' AS source,c.auth_flag as auth_flag,c.add_time,i.pos_group_type,i.pos_group,
				i.storage_type,i.storage_num,i.remain_num,i.validate_type,i.market_price,i.goods_amt,i.goods_discount,
				i.goods_image,i.node_id,i.mms_title,i.mms_text,i.sms_text,i.print_text,case when i.verify_begin_type = i.verify_end_type then i.verify_begin_type else 0 end verify_time_type,
			    i.verify_begin_date, i.verify_end_date,i.goods_desc{$fieldStoreMode2}");
        $table = "(
					(SELECT {$getFieldArr[0]} FROM tgoods_info g WHERE g.`goods_type` IN ({$mapGoodsType}) AND g.`source` IN ('0','1','4','6') AND g.status = 0 AND g.`node_id` = '{$this->nodeId}')
    				    UNION ALL 
    				(SELECT {$getFieldArr[1]} FROM twx_card_type c LEFT JOIN tgoods_info i ON c.goods_id = i.goods_id WHERE c.card_class='1' AND c.node_id = '{$this->nodeId}' AND i.status = 0 {$mapStoreMode})
  				  )";
        import("ORG.Util.Page");
        $countSql = "select count(*) as count from {$table} tg where {$map} limit 1";
        $count = M()->query($countSql);
        $p = new Page($count[0]['count'], 8);
        $listSql = "select *,CASE WHEN tg.pos_group_type=1 THEN (SELECT COUNT(*) FROM tstore_info WHERE node_id='{$this->nodeId}' AND pos_count>0 AND STATUS=0)
	 							  WHEN tg.pos_group_type=2 THEN (SELECT COUNT(*) FROM tgroup_pos_relation WHERE group_id=tg.pos_group) 
                                  END AS store_num
		            from {$table} as tg where {$map} order by tg.add_time desc limit {$p->firstRow},$p->listRows";
        $list = M()->query($listSql) or $list = array();
        // 处理一下图片
        foreach ($list as &$v) {
            $v['goods_image_url'] = get_upload_url($v['goods_image']);
        }
        unset($v);
        // echo M()->getLastSql();
        // 是否有门店
        $storeNum = M('tstore_info')->where(
            "node_id='{$this->nodeId}' and status=0")->count();
        // 是否有可验证门店
        $posStoreNum = M('tstore_info')->where(
            "node_id='{$this->nodeId}' AND pos_count>0 AND status=0")->count();
        
        $sourceColor = array( // 来源颜色
            '0' => 'tp1', 
            '1' => 'tp2', 
            '3' => 'tp3', 
            '4' => 'tp2');
        $goodsHelp = U('Home/Help/helpConter', 
            array(
                'type' => 7, 
                'left' => 'dzq'));

        // 获取卡券是否可以发送微信卡券
        $wxModel = M('twx_card_type');
        foreach ($list as &$v) {
            $wxData = $wxModel->where(array('goods_id' => $v['goods_id']))->find();
            $v['wx_flag'] = $wxData['card_id'];
        }

        // 招募活动改动了添加奖品的步骤，第一步为选择卡券或定额红包
        $next_step = I('next_step', '', '');
        if ($next_step) {
            $this->assign('next_step', urldecode($next_step));
        }
        foreach ($_REQUEST as $key => $val) {
            $p->parameter[$key] = urlencode($val); // 赋值给Page
        }
        // 分页显示
        // 新增sku数据查询
        $skuObj = D('Sku', 'Service');
        foreach ($list as &$vals) {
            // 取得商品sku数据
            $goods_sku_info = $skuObj->getSkuInfoList($vals['goods_id'], 
                $this->nodeId);
            if (NULL != $goods_sku_info) {
                // 分离商品表中的规格和规格值ID
                $goods_sku_list = $skuObj->getReloadSku($goods_sku_info);
                // 取得规格值表信息
                $goods_sku_detail_list = '';
                if (is_array($goods_sku_list['list']))
                    $goods_sku_detail_list = $skuObj->getSkuDetailList(
                        $goods_sku_list['list']);
                    
                    // 取得规格表信息
                if (is_array($goods_sku_detail_list))
                    $goods_sku_type_list = $skuObj->getSkuTypeList(
                        $goods_sku_detail_list);
                    // 规格值排列值
                $skutype = $skuObj->makeSkuType($goods_sku_type_list, 
                    $goods_sku_detail_list);
                // 价格列表
                $skudetail = $skuObj->makeSkuList($goods_sku_info);
                $vals['is_sku'] = true;
                $vals['skutype'] = $skutype;
                $vals['skudetail'] = $skudetail;
            } else {
                $vals['is_sku'] = false;
            }
        }
        $page = $p->show();
        $this->assign('list', $list);
        $this->assign("page", $page);
        $this->assign('sourceType', $sourceType);
        $this->assign('goodsType', $goodsType);
        $this->assign('selectType', $selectType);
        $this->assign('stroeNum', $storeNum);
        $this->assign('sourceColor', $sourceColor);
        $this->assign('posStoreNum', $posStoreNum);
        $this->assign('sourceSelectStatus', $sourceSelectStatus);
        $this->assign('goodsTypeSelectStatus', $goodsTypeSelectStatus);
        $this->assign('goodsHelp', $goodsHelp);
        $this->assign('show_type', $show_type);
        // 显示时,切换到"创建和采购卡券"的部分
        $this->assign('showSetElec', I('get.showSetElec', ''));
        $this->display();
    }

    /**
     * 新的选奖品的框
     */
    public function indexNew() {
        $this->assign('actionName', I('get.action',''));
        $goodsModel = D('Goods');
        $selectJpModel = D('SelectJp');
        // $sourceType =
        // array('0'=>'自建卡券','1'=>'采购卡券','2'=>'红包','3'=>'微信卡券');//显示的来源下拉框
        $tabList = array(
            '1' => '卡券', 
            '2' => '定额红包', 
            '3' => '积分',
            '4' => '微信红包', 
            '5' => '朋友的券'
        );
        // 下一步的url
        $next_step = I('next_step', '', '');
        if ($next_step) {
            $this->assign('next_step', urldecode($next_step));
        }
        $mId = $selectJpModel->getMIdByUrl($next_step);
        //判断是否显示微信红包的tab
        $mInfo = $selectJpModel->getJoinModeAndWxAuthType($this->node_id, $mId);
        if ($mInfo && $mInfo['join_mode'] != '1') {//如果不能使用微信红包，不显示
            unset($tabList['4']);
        }
        $availableTab = I('availableTab'); // 特殊指定有些tab显示,有些不显示（例：'1,2'）
        $tabList = $selectJpModel->getAvailable($tabList, $availableTab);
        $tabListKeys = array_keys($tabList);
        // 如果没有开通积分营销的,要去掉积分营销的tab
        $hasPay = $this->hasPayModule('m4');
        if (! $hasPay) {
            $k = array_search('3', $tabListKeys);
            if (false !== $k) {
                unset($tabList[$tabListKeys[$k]]);
                unset($tabListKeys[$k]);
            }
        }
        $this->assign('tabList', $tabList);
        $selectTabValue = I('selectTabValue', $tabListKeys[0]); // 默认选中的是第一个tab
        $this->assign('selectTabValue', $selectTabValue);
        $sourceType = array(
            '' => '来源', 
            '0' => '自建', 
            '1' => '采购',
            '3'=> '微信卡券',
            );
        $availableSourceType = I('availableSourceType'); // 特殊指定来源值
        $sourceType = $selectJpModel->getAvailable($sourceType,$availableSourceType);
        $availableSendType = I('availableSendType');
        
        $this->assign('availableSendType', $availableSendType);
        $prizeCateId = $selectJpModel->getCateIdByUrl($next_step);
        $this->assign('prizeCateId', $prizeCateId);
        $this->assign('mId', $mId);
        $this->assign('sourceType', $sourceType);
        
        $sourceTypeKeys = array_keys($sourceType);
        $show_source = I('source', $sourceTypeKeys[0]); // 显示指定来源的卡券
        $show_type = I('goods_type'); // 显示指定卡券类型
        $goodsName = I('goods_name', null, 'mysql_real_escape_string');
        $availableGoodsType = I('availableGoodsType');
        
        $selectType = $selectJpModel->getProvidedGoodsType($show_source, 
            $availableGoodsType); // 第二个下拉框的选项
                                  // 所有的goods_type的key和中文意思
        $allProvidedGoodsType = $selectJpModel->getAllProvidedGoodsType(); // 所有的种类的数字下标和对应的中文意思
        $this->assign('allProvidedGoodsType', $allProvidedGoodsType);
        //如果是定额红包,
        if ($selectTabValue == '2') {
            $show_source = '2';
        } elseif ($selectTabValue == '4') {
            //如果是微信红包，来源有两种（1自有公众号的微信红包，2翼码待发的红包）//sourceType分别为6和7
            $show_source = '4';
        }
        $wxGoods = I('wxGoods', 0);
        if($show_source == '3'){
            $wxGoods = 1;
        }
        $this->assign('wxGoods', $wxGoods);
        //当tab切换成朋友的券的时候
        if ($selectTabValue == '5') {
            $resp = $selectJpModel->getFreindCard($this->node_id, $goodsName);
        } else {
            $storeMode = I('storeMode','');
            if(!empty($storeMode) && $show_source != '4'){
                $this->weiXinDisplay($storeMode,$selectType,$show_type,$goodsName);
                exit;
            }
            $wxAuthType = get_val($mInfo['wx_auth_type']);
            $resp = $selectJpModel->getSourceAndType($this->node_id, $show_source,
                $show_type, $goodsName, $wxAuthType, $wxGoods); // 按照传过来的参数组合成筛选条件
            $this->assign('storeMode', $storeMode);
        }
        
        //start这一段王严加的表示默认没有任何券的时候出现界面让用户设置默认电子券
        $respRet = $selectJpModel->getSourceAndType($this->node_id, '', 
            '', ''); // 按照传过来的参数组合成筛选条件
        if(empty($respRet['result']))
        {
            $this->display('noCard');
            exit;
        }
        //end这一段王严加的表示默认没有任何券的时候出现界面让用户设置默认电子券
        $list = $resp['result'];
        $p = $resp['p'];
        $this->assign('batch_type', isset($mInfo['batch_type']) ? $mInfo['batch_type'] : '');//特殊的逻辑，欧洲杯活动不显示每日奖品限量
        // 处理一下图片
        foreach ($list as &$v) {
            $v['goods_image_url'] = get_upload_url($v['goods_image']);
            //微信参与方式才显示微信logo
            if ($mInfo && $mInfo['join_mode'] == '1') {
                if ($mInfo['batch_type'] == CommonConst::BATCH_TYPE_EUROCUP) {//特殊的逻辑，欧洲杯活动不能用微信卡券
                    $v['wx_flag'] = 0;
                    $v['card_over_time'] = 0;
                    
                } else {
                    // 处理卡券的标志logo
                    $wxFlagAndOverTime = $selectJpModel->getWxFlag($v['goods_id'], $mInfo['endTime']);
                    $v['wx_flag'] = $wxFlagAndOverTime['hasCard'];
                    $v['card_over_time'] = $wxFlagAndOverTime['overTime'];
                }
            } else {
                $v['wx_flag'] = 0;
                $v['card_over_time'] = 0;
            }
        }
        unset($v);
        // 是否有门店
        $storeNum = M('tstore_info')->where(
            "node_id='{$this->nodeId}' and status=0")->count();
        // 是否有可验证门店
        $posStoreNum = M('tstore_info')->where(
            "node_id='{$this->nodeId}' AND pos_count>0 AND status=0")->count();
        $sourceColor = $selectJpModel->getSourceColor($show_source); // 来源颜色
        $goodsHelp = U('Home/Help/helpConter', 
            array(
                'type' => 7, 
                'left' => 'dzq'));
        foreach ($_REQUEST as $key => $val) {
            $p->parameter[$key] = urlencode($val); // 赋值给Page
        }
        // 分页显示
        // 新增sku数据查询
        $skuObj = D('Sku', 'Service');
        $goodsInfoIdArr = array(); // 存放这一页的goods_info的id
        foreach ($list as &$vals) {
            // 取得商品sku数据
            $goods_sku_info = $skuObj->getSkuInfoList($vals['goods_id'], 
                $this->nodeId);
            if (NULL != $goods_sku_info) {
                // 分离商品表中的规格和规格值ID
                $goods_sku_list = $skuObj->getReloadSku($goods_sku_info);
                // 取得规格值表信息
                $goods_sku_detail_list = '';
                if (is_array($goods_sku_list['list'])) {
                    $goods_sku_detail_list = $skuObj->getSkuDetailList(
                        $goods_sku_list['list']);
                }
                // 取得规格表信息
                if (is_array($goods_sku_detail_list)) {
                    $goods_sku_type_list = $skuObj->getSkuTypeList(
                        $goods_sku_detail_list);
                }
                // 规格值排列值
                $skutype = $skuObj->makeSkuType($goods_sku_type_list, 
                    $goods_sku_detail_list);
                // 价格列表
                $skudetail = $skuObj->makeSkuList($goods_sku_info);
                $vals['is_sku'] = true;
                $vals['skutype'] = $skutype;
                $vals['skudetail'] = $skudetail;
            } else {
                $vals['is_sku'] = false;
            }
            if (! in_array($vals['id'], $goodsInfoIdArr)) {
                $goodsInfoIdArr[] = $vals['id'];
            }
            //微信红包给默认图片
            if (in_array($vals['source'], [CommonConst::GOODS_SOURCE_SELF_CREATE_WXHB, CommonConst::GOODS_SOURCE_YIMA_CREATE_WXHB])) {
                $vals['goods_image'] = './Home/Public/Image/eTicket/wechat_bf.png';
            } else {
                $vals['goods_image'] = './Home/Upload/'. $vals['goods_image'];
            }
        }
        if (! empty($goodsInfoIdArr)) {
            // 可验门店数（key为tgoods_info的id）
            $checkedStoreNum = M('tgoods_info')->alias('tg')
                ->where(
                array(
                    'tg.id' => array(
                        'in', 
                        $goodsInfoIdArr)))
                ->getField(
                'tg.id,CASE WHEN tg.pos_group_type=1 THEN (SELECT COUNT(*) FROM tstore_info WHERE node_id=' .
                     $this->nodeId . ' AND pos_count>0 AND STATUS=0 AND type in(1,2))
				WHEN tg.pos_group_type=2 THEN (SELECT COUNT(*) FROM tgroup_pos_relation WHERE group_id=tg.pos_group)
				END AS store_num');
        }
        $page = $p->show();
        // 是否需要提示创建门店和epos，创建过免费订单以后需要提示（目前只有免费用户第一次创建大转盘的时候会创建免费订单）
        $needRemind = D('SelectJp')->remindCreate($this->node_id, $next_step);
        $this->assign('needRemind', $needRemind);
        $this->assign('list', $list);
        $this->assign("page", $page);
        $this->assign('show_source', $show_source); // 传过来的第一个下拉框的值
        $this->assign('sourceType', $sourceType);
        $this->assign('selectType', $selectType);
        $this->assign('stroeNum', $storeNum);
        $this->assign('sourceColor', $sourceColor);
        $this->assign('posStoreNum', $posStoreNum);
        $this->assign('goodsHelp', $goodsHelp);
        $this->assign('show_type', $show_type);
        $this->assign('checkedStoreNum', $checkedStoreNum);
        $this->display();
    }

    /**
     * 剥离微信数据
     * @param   string  $storeMode    预存  ||  投放
     * @param   array   $selectType   第二个下拉框
     * @param   string  $show_type    卡券类型
     */
    public function weiXinDisplay($storeMode, $selectType,$show_type = '0,1,2,3',$like = ''){
        $field = ' (a.quantity-a.card_get_num) remain_num,a.goods_id,a.card_id,a.card_class,a.title goods_name,a.date_type,a.date_begin_timestamp,a.date_end_timestamp,a.date_fixed_timestamp,a.date_fixed_begin_timestamp,a.store_mode, ';
        $field .= ' b.source,b.goods_image,b.goods_type,b.status,b.storage_type ';
        $where = array('a.node_id'=>$this->nodeId,'a.auth_flag'=>2,'b.goods_type'=>array('IN',$show_type),'b.status'=>0);
        $where['a.store_mode'] = 2;
        $where['a.quantity-a.card_get_num'] = array('GT',0);
        $where['_string'] = ' CASE a.date_type WHEN 1 THEN a.date_end_timestamp >='.time().' END ';

        if(!empty($like)){
            $where['b.goods_name'] = array('like',"%".$like."%");
        }
        $join = ' a LEFT JOIN tgoods_info b ON a.goods_id = b.goods_id';

        //分页
        import("ORG.Util.Page");
        $count = M('twx_card_type')->join($join)->where($where)->count();
        $p = new Page($count, 8);
        foreach ($_REQUEST as $key => $val) {
            $p->parameter[$key] = urlencode($val); // 赋值给Page
        }
        $list = M('twx_card_type')->field($field)->join($join)->where($where)->limit($p->firstRow,$p->listRows)->order(' a.add_time desc')->select();
        $page = $p->show();

        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('selectType', $selectType);
        $this->assign('storeMode', $storeMode);
        $this->display('indexNew');
    }

    public function getGoodsTypeArr() {
        $show_source = I('source');
        $availableSourceType = I('availableSourceType');
        $availableGoodsType = I('availableGoodsType');
        $selectType = D('SelectJp')->getProvidedGoodsType($show_source, 
            $availableGoodsType); // 第二个下拉框的选项
        $this->success(array(
            'selectType' => $selectType));
    }

    public function _index_wxc() {
        $map = array(
            'a.node_id' => $this->node_id, 
            'a.goods_id' => array(
                'exp', 
                '=b.goods_id'), 
            'b.status' => '0', 
            'a.auth_flag' => '2');
        
        $mapcount = M()->table('twx_card_type a, tgoods_info b')
            ->where($map)
            ->count();
        
        import('ORG.Util.Page');
        $Page = new Page($mapcount, 10);
        $show = $Page->show();
        
        $list = (array) M()->table('twx_card_type a, tgoods_info b')
            ->where($map)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->field(
            "a.card_id, a.card_type, a.quantity, a.id, b.goods_id, b.goods_type , a.title as goods_name,
			b.goods_desc, b.goods_image, b.goods_amt , b.goods_discount, b.market_price, b.node_id,
			b.goods_type, b.storage_num, b.mms_title, b.mms_text, b.sms_text,
			b.print_text, b.begin_time, b.end_time, 'wxc' source,
			case when a.quantity = 99999 then '0' else '1' end storage_type,
			case when a.quantity = 99999 then '不限' else (a.quantity - (select ifnull(sum(storage_num),0) from tbatch_info where card_id = a.card_id)) end remain_num")
            ->order('a.add_time DESC')
            ->select();
        
        foreach ($list as &$goods) {
            $goods['goods_image_url'] = get_upload_url($goods['goods_image']);
        }
        $this->assign('query_list', $list);
        $this->assign('page', $show);
        
        $this->assign('source', 'wxc');
        $this->assign('tab', '');
        $this->display('index');
    }
    
    // 奖品基础配置更新
    public function jpRuleSave() {
        $rules = array(
            'total_chance' => array(
                'null' => false, 
                'strtype' => 'int', 
                'name' => '中奖总概率'), 
            'cj_button_text' => array(
                'null' => true, 
                'maxlen_cn' => '6', 
                'name' => '抽奖按钮文字'), 
            'phone_total_count' => array(
                'null' => false, 
                'strtype' => 'int', 
                'minval' => '0', 
                'name' => '每个手机总中奖次数'), 
            'phone_day_count' => array(
                'null' => false, 
                'strtype' => 'int', 
                'minval' => '0', 
                'name' => '每个手机每天中奖次数'), 
            'phone_total_part' => array(
                'null' => false, 
                'strtype' => 'int', 
                'minval' => '0', 
                'name' => '每个手机总抽奖次数'), 
            'phone_day_part' => array(
                'null' => false, 
                'strtype' => 'int', 
                'minval' => '0', 
                'name' => '每个手机每天抽奖次数'), 
            'cj_resp_text' => array(
                'null' => true, 
                'maxlen_cn' => '50', 
                'name' => '抽奖返回提示文字'), 
            'batch_id' => array(
                'null' => false, 
                'strtype' => 'int', 
                'name' => '营销活动号'), 
            'cj_phone_type' => array(
                'null' => true, 
                'strtype' => 'int', 
                'name' => '抽奖形式'), 
            'param1' => array(
                'null' => true, 
                'strtype' => 'int', 
                'minval' => '0', 
                'name' => '小票参与次数'), 
            'no_award_notice' => array(
                'null' => false, 
                'name' => '未中奖提示内容'));
        
        $req_data = $this->_verifyReqData($rules);
        try {
            $req_data['join_mode'] = I('jointype', 0, 'intval');
            if ($req_data['join_mode'] != 0 && $req_data['join_mode'] != 1) {
                throw_exception('参与方式错误！');
            }
            $req_data['member_join_flag'] = I('member_join_flag', null);
            $req_data['member_reg_mid'] = I('member_reg_mid', null);
            $req_data['member_batch_id'] = I('member_batch_id', '');
            $req_data['member_batch_id_zj'] = I('member_batch_id_zj', '');
            $req_data['version'] = I('version', 0, 'intval');
            if ($req_data['version'] == '3') {
                $rules = array(
                    'is_limit' => array(
                        'null' => false, 
                        'strtype' => 'int', 
                        'minval' => '0', 
                        'maxval' => '1', 
                        'name' => '参与限制'), 
                    'is_limit_zj' => array(
                        'null' => false, 
                        'strtype' => 'int', 
                        'minval' => '0', 
                        'maxval' => '1', 
                        'name' => '中奖限制'), 
                    'fans_collect_url' => array(
                        'null' => true, 
                        'maxlen' => '250', 
                        'name' => '微信粉丝招募活动url'));
                $req_data = array_merge($req_data, 
                    $this->_verifyReqData($rules));
            }
            $this->_jpRuleSave($req_data);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
        $this->success('奖品配置保存成功！');
    }

    /**
     * 为营销活动初始化抽奖规则
     */
    public function _jpRuleInit($batch_id) {
        $data = array(
            'join_mode' => 0, 
            'cj_phone_type' => 1, 
            'total_chance' => 0, 
            'cj_button_text' => '开始抽奖', 
            'phone_total_count' => 1, 
            'phone_day_count' => 1, 
            'phone_total_part' => 1, 
            'phone_day_part' => 1, 
            'cj_resp_text' => '恭喜您！中奖了', 
            'batch_id' => $batch_id, 
            'param1' => '', 
            'no_award_notice' => '很遗憾！未中奖', 
            'member_join_flag' => 0, 
            'member_batch_id' => 0, 
            'member_batch_id_zj' => 0);
        
        try {
            $id = $this->_jpRuleSave($data);
            return $id;
        } catch (Exception $e) {
            throw_exception('抽奖规则初始化失败！');
        }
    }
    
    // 奖品基础配置更新
    public function _jpRuleSave($req_data) {
        extract($req_data);
        if ($phone_day_count > 0 && $phone_total_count > 0 &&
             $phone_day_count > $phone_total_count)
            throw_exception('日中奖次数不能大于总中奖次数');
        if ($phone_day_part > 0 && $phone_total_part > 0 &&
             $phone_day_part > $phone_total_part)
            throw_exception('日参与次数不能大于总参与次数');
        
        if (! $batch_id)
            throw_exception('营销活动号不正确！');
            
            // 营销活动校验
        $batchInfo = M('tmarketing_info')->where(
            "id = '{$batch_id}' and node_id = '{$this->nodeId}'")->find();
        if (! $batchInfo)
            throw_exception('未找到该营销活动');
            // 校验奖品是否有微信红包
        if ($this->checkJpRedPack($batch_id) && verify_scalar_val($join_mode, '1', '!=')) {
            $this->error('奖品里含有微信红包,参与方式必须为微信号');
        }
        // 粉丝专属校验
        // $memberJoinFlag = I('member_join_flag',null);
        // $memberRegMid = '';
        
        if (verify_scalar_val($member_join_flag, '1') && verify_scalar_val($version, 0)) {
            // $member_reg_mid = I('member_reg_mid',null);
            $map = array(
                'm.node_id' => $this->nodeId, 
                'm.batch_type' => '52',  // 会员招募活动的type
                'm.status' => '1', 
                'm.re_type' => '0', 
                'm.start_time' => array(
                    'elt', 
                    date('YmdHis')), 
                'm.end_time' => array(
                    'egt', 
                    date('YmdHis')), 
                'm.id' => $member_reg_mid, 
                'c.channel_id' => array(
                    'exp', 
                    'is not null'), 
                'c.status' => '1');
            $count = M()->table("tmarketing_info m")->join(
                'tbatch_channel c ON m.id=c.batch_id')
                ->where($map)
                ->count();

            if (! $count)
                throw_exception('无效的粉丝招募活动');
        }
        if (isset($version) && $version == 0) { // 设置老版本新增这两字段的默认值
            $member_batch_id = - 1;
            $member_batch_id_zj = - 1;
        }
        
        if (isset($version) && $version == 3) {
            $member_join_flag = $is_limit;
            $member_batch_id = ($is_limit == 1) ? $member_batch_id : 0;
            $member_batch_id_zj = ($is_limit_zj == 1) ? $member_batch_id_zj : 0;
            //$fans_collect_url = '';
            $member_reg_mid = '';
            $join_mode = get_scalar_val($join_mode, 0);
            // 参与方式：手机号
            if ($join_mode == 0) {
                if ($member_batch_id != '' && $member_batch_id != '0') {
                    $arr = explode($member_batch_id);
                    $map = array(
                        'node_id' => $this->node_id, 
                        'status' => '1', 
                        'id' => array(
                            'in', 
                            $arr));
                    $cnt = M('tmember_batch')->where($map)->count();
                    if ($cnt != count($arr)) {
                        throw_exception('无效的活动分组');
                    }
                }
                if ($member_batch_id_zj != '' && $member_batch_id_zj != '0') {
                    $arr = explode($member_batch_id_zj);
                    $map = array(
                        'node_id' => $this->node_id, 
                        'status' => '1', 
                        'id' => array(
                            'in', 
                            $arr));
                    $cnt = M('tmember_batch')->where($map)->count();
                    if ($cnt != count($arr)) {
                        throw_exception('无效的中奖活动分组');
                    }
                }
                $fans_collect_url = '';
            }
            
            
        }
        
        M()->startTrans();
        // 更新抽奖形式和粉丝专享
        
        // 由于微信分组的“未分组”的id为0，与原来数据库中member_batch_id为0时表示“不限制”重复，所以现在改为-1表示不限制，0表示微信分组的未分组
        // 为了不影响其他页面传送过来的逻辑，在存数据库之前改这两个值
        if ($member_batch_id === 0) {
            $member_batch_id = - 1;
        }
        if ($member_batch_id_zj === 0) {
            $member_batch_id_zj = - 1;
        }
        
        if ($member_batch_id !== - 1 || $member_batch_id_zj !== - 1) {
            $member_join_flag = 1;
        }
        
        if (($batchInfo['cj_phone_type'] != $cj_phone_type &&
             ! empty($cj_phone_type)) ||
             $batchInfo['member_join_flag'] != $member_join_flag ||
             $batchInfo['member_reg_mid'] != $member_reg_mid ||
             $batchInfo['join_mode'] != $join_mode ||
             $batchInfo['member_batch_id'] != $member_batch_id ||
             $batchInfo['member_batch_award_id'] != $member_batch_id_zj ||
             $batchInfo['fans_collect_url'] != $fans_collect_url) {
            $data = array(
                'cj_phone_type' => $cj_phone_type, 
                'join_mode' => $join_mode, 
                'member_batch_id' => $member_batch_id, 
                'member_join_flag' => $member_join_flag, 
                'member_reg_mid' => $member_reg_mid, 
                'member_batch_award_id' => $member_batch_id_zj, 
                'fans_collect_url' => $fans_collect_url);
            
            // 如果参与方式变更了，需要判断是否已经有参与记录，如果有则不允许变更！
            if ($batchInfo['join_mode'] != $join_mode) {
                $cnt = (int) M('tcj_trace')->where("batch_id = '{$batch_id}'")->count();
                if ($cnt > 0) {
                    throw_exception('营销活动已经有抽奖记录了，不允许变更参与方式');
                }
                
                $data['join_mode'] = $join_mode;
            }
            
            $flag = M('tmarketing_info')->where(
                "id = '{$batch_id}' and node_id = '{$this->nodeId}'")->save(
                $data);
            if ($flag === false) {
                M()->rollback();
                throw_exception('抽奖形式或粉丝专享内容更新失败');
            }
        }
        $ruleList = M('tcj_rule')->where(
            "batch_id = '{$batch_id}' and status = '1'")->select();
        // 新增
        if (! $ruleList) {
            $data = array(
                'batch_type' => $batchInfo['batch_type'], 
                'batch_id' => $batch_id, 
                'jp_set_type' => 2, 
                'node_id' => $batchInfo['node_id'], 
                'add_time' => date('YmdHis'), 
                'status' => '1', 
                'total_chance' => $total_chance, 
                'phone_total_count' => $phone_total_count, 
                'phone_day_count' => $phone_day_count, 
                'phone_total_part' => $phone_total_part, 
                'phone_day_part' => $phone_day_part, 
                'cj_button_text' => $cj_button_text, 
                'cj_resp_text' => $cj_resp_text, 
                'param1' => $param1, 
                'no_award_notice' => substr($no_award_notice, 0, - 1));
            
            $flag = M('tcj_rule')->add($data);
            if ($flag === false) {
                M()->rollback();
                throw_exception('保存失败！');
            }
            M()->commit();
            node_log("奖品基础配置更新", print_r($req_data, TRUE));
            
            return $flag;
        } else {
            if (count($ruleList) > 1) {
                M()->rollback();
                throw_exception('系统异常：存在多条启用的抽奖规则记录！');
            }
            $ruleInfo = $ruleList[0];
            
            // 编辑
            $data = array(
                'total_chance' => $total_chance, 
                'phone_total_count' => $phone_total_count, 
                'phone_day_count' => $phone_day_count, 
                'phone_total_part' => $phone_total_part, 
                'phone_day_part' => $phone_day_part, 
                'cj_button_text' => $cj_button_text, 
                'cj_resp_text' => $cj_resp_text, 
                'param1' => $param1, 
                'no_award_notice' => substr($no_award_notice, 0, - 1));
            $flag = M('tcj_rule')->where("id = '{$ruleInfo['id']}'")->save(
                $data);
            if ($flag === false) {
                M()->rollback();
                throw_exception('保存失败！');
            }
            M()->commit();
            node_log("奖品基础配置更新", print_r($req_data, TRUE));
            return true;
        }
    }
    
    // 创建奖品等级
    public function jpLevelSave() {
        $rules = array(
            'cj_cate_id' => array(
                'null' => true, 
                'strtype' => 'int', 
                'minval' => '0', 
                'name' => '奖项ID'), 
            'cj_cate_name' => array(
                'null' => false, 
                'maxlen_cn' => '30', 
                'name' => '奖项名称'), 
            'batch_id' => array(
                'null' => false, 
                'strtype' => 'int', 
                'name' => '营销活动号'), 
            'score' => array(
                'null' => true, 
                'strtype' => 'int', 
                'name' => '兑换金币数'), 
            'min_rank' => array(
                'null' => true, 
                'strtype' => 'int', 
                'name' => '最小排名'), 
            'max_rank' => array(
                'null' => true, 
                'strtype' => 'int', 
                'name' => '最大排名'), 
            'sort' => array(
                'null' => true, 
                'strtype' => 'int', 
                'name' => '排序'));
        
        $req_data = $this->_verifyReqData($rules);
        extract($req_data);
        $batch_id = $req_data['batch_id'];
        $cj_cate_id = $req_data['cj_cate_id'];
        $cj_cate_name = $req_data['cj_cate_name'];
        $score = $req_data['score'];
        $min_rank = $req_data['min_rank'];
        $max_rank = $req_data['max_rank'];
        $sort = $req_data['sort'];
        
        $member_id = I('post.member_id', '', 'trim,mysql_real_escape_string');
        
        if (! $batch_id)
            $this->error('营销活动号不正确！');
            
            // 营销活动校验
        $batchInfo = M('tmarketing_info')->where(
            "id = '{$batch_id}' and node_id = '{$this->nodeId}'")->find();
        if (! $batchInfo)
            $this->error('未找到该营销活动');
        
        $ruleList = M('tcj_rule')->where(
            "batch_id = '{$batch_id}' and status = '1'")->select();
        if (! $ruleList)
            $this->error('请先保存抽奖基础配置！');
        
        if (count($ruleList) > 1)
            $this->error('系统异常：存在多条启用的抽奖规则记录！');
        $ruleInfo = $ruleList[0];
        $cj_rule_id = $ruleInfo['id'];
        
        // 校验粉丝组
        if ($member_id != '') {
            $cnt = count(explode('-', $member_id));
            $in_str = str_replace('-', ',', $member_id);
            $map = array(
                'node_id' => $this->nodeId, 
                '_string' => "id in ({$in_str})");
            $cnt2 = M('tmember_batch')->where($map)->count();
            if ($cnt != $cnt2)
                $this->error('粉丝组信息错误！');
        }
        
        // 新增
        if ($cj_cate_id == '') {
            
            $map = array(
                'batch_id' => $batch_id, 
                'cj_rule_id' => $cj_rule_id, 
                'name' => $cj_cate_name);
            
            if (M('tcj_cate')->where($map)->count() > 0)
                $this->error('奖项名称已经存在！');
                // 计算应该存进去的排序值
            $sortMax = M('tcj_cate')->where(
                array(
                    'batch_id' => $batch_id))->max('sort');
            $sortMax = $sortMax ? $sortMax : 0;
            
            $data = array(
                'batch_type' => $batchInfo['batch_type'], 
                'batch_id' => $batchInfo['id'], 
                'node_id' => $batchInfo['node_id'], 
                'cj_rule_id' => $cj_rule_id, 
                'name' => $cj_cate_name, 
                'member_batch_id' => $member_id, 
                'add_time' => date('YmdHis'), 
                'status' => '1', 
                'score' => $score, 
                'min_rank' => $min_rank, 
                'max_rank' => $max_rank, 
                'sort' => (intval($sortMax) + 1));
            
            $cat_id = M('tcj_cate')->add($data);
            
            if ($cat_id === false)
                $this->error('奖项添加失败！');
            
            node_log("创建营销活动奖项", print_r($_POST, TRUE));
            
            $this->ajaxReturn(array(
                'cat_id' => $cat_id), '奖项添加成功！', 1);
        }  // 编辑
else {
            
            $map = array(
                'batch_id' => $batch_id, 
                'cj_rule_id' => $cj_rule_id, 
                'name' => $cj_cate_name, 
                '_string' => "id != $cj_cate_id");
            
            if (M('tcj_cate')->where($map)->count() > 0)
                $this->error('奖项名称已经存在！');
            
            $map = array(
                'id' => $cj_cate_id, 
                'batch_id' => $batchInfo['id'], 
                'cj_rule_id' => $cj_rule_id, 
                'node_id' => $this->nodeId);
            $awardInfo = M('tcj_cate')->where($map)->find();
            if (! $awardInfo)
                $this->error('奖项不存在！');
            
            $data = array(
                'name' => $cj_cate_name, 
                'member_batch_id' => $member_id, 
                'score' => $score, 
                'min_rank' => $min_rank, 
                'max_rank' => $max_rank);
            $flag = M('tcj_cate')->where($map)->save($data);
            
            if ($flag === false)
                $this->error('奖项编辑失败！');
            
            node_log("编辑营销活动奖项", print_r($_POST, TRUE));
            
            $this->success('保存成功！');
        }
    }
    /**
     * [feelJp 一键体验奖品]
     * @return [type] [none]
     */
    public function feelJp(){
        $m_id = I('post.m_id', ''); // 活动id
        $c_id = I('post.c_id','');  // 奖项id

        if ($m_id) {
            // 判断活动号是不是这个机构的
            $map = array(
                'node_id' => $this->nodeId, 
                'id'      => $m_id
                );
            $marketingInfo = M('tmarketing_info')->where($map)->find();
            if (! $marketingInfo) {
                $this->error('传入参数有误');
            }
        }else{
            $this->error('传入参数有误');
        }
        if ($c_id) {
            // 奖项校验
            $map = array(
                'batch_id' => $m_id, 
                'id'       => $c_id
                );
            $awardInfo = M('tcj_cate')->where($map)->find();
            if (! $awardInfo)
                $this->error('未找到奖项信息！');
        }else{
            $this->error('未找到奖项信息！');
        }
        // 创建奖品
        $goodsInfo   = self::createGoodsInfo();
        // 获取cj_rule信息
        $ruleList = M('tcj_rule')->where(['batch_id'=>$m_id,'status'=>1])->select();
        $cnt = count($ruleList);
        if ($cnt == 0) {
            try {
                $ruleInfo = array();
                $ruleInfo['id'] = $this->_jpRuleInit($m_id);
            } catch (Exception $e) {
                $this->error('抽奖规则保存失败！请重试');
            }
        } else if ($cnt > 1)
            $this->error('系统异常：存在多条启用的抽奖规则记录！');
        else
            $ruleInfo = $ruleList[0];
        // 开启事务处理模式
        M()->startTrans();
        $goodsMap = array(
            'goods_id' => $goodsInfo['goods_id'],
            'node_id'  => $this->nodeId,
            'status'   => 0
            );
        $goodsInfo = M('tgoods_info')->where($goodsMap)->lock(true)->find();
        if(empty($goodsInfo))
        {
            M()->rollback();
            $this->error('奖品信息有误');
        }
        $data = array(
            'batch_no'             => $goodsInfo['batch_no'], 
            'batch_short_name'     => $goodsInfo['goods_name'], 
            'batch_name'           => $goodsInfo['goods_name'], 
            'node_id'              => $this->nodeId, 
            'user_id'              => $this->userId, 
            'batch_class'          => $goodsInfo['goods_type'], 
            'batch_type'           => $goodsInfo['source'], 
            'info_title'           => '体验标题', 
            'use_rule'             => '短信内容', 
            'sms_text'             => '彩信内容', 
            'batch_img'            => $goodsInfo['goods_image'], 
            'batch_amt'            => $goodsInfo['goods_amt'], 
            'begin_time'           => $marketingInfo['start_time'], 
            'end_time'             => $marketingInfo['end_time'], 
            'send_begin_date'      => $marketingInfo['start_time'], 
            'send_end_date'        => $marketingInfo['end_time'], 
            'verify_begin_date'    => date('YmdHis'), 
            'verify_end_date'      => date('YmdHis',strtotime('+60 days')), 
            'verify_begin_type'    => 0, 
            'verify_end_type'      => 0, 
            'storage_num'          => 5, 
            'add_time'             => date('YmdHis'), 
            'node_pos_group'       => $goodsInfo['pos_group'], 
            'node_pos_type'        => $goodsInfo['pos_group_type'], 
            'batch_desc'           => $goodsInfo['goods_desc'], 
            'node_service_hotline' => $goodsInfo['node_service_hotline'], 
            'goods_id'             => $goodsInfo['goods_id'], 
            'remain_num'           => 5, 
            'material_code'        => $goodsInfo['customer_no'], 
            'print_text'           => $goodsInfo['print_text'], 
            'm_id'                 => $marketingInfo['id'], 
            'validate_type'        => $goodsInfo['validate_type']
            );
            
        $b_id = M('tbatch_info')->data($data)->add();
        if (! $b_id) {
            M()->rollback();
            $this->error('数据库出错添加失败！');
        }

        // cj_batch奖品处理
        $data = array(
            'batch_id'     => $m_id,            // '抽奖活动id'
            'node_id'      => $this->nodeId,    // '商户号'
            'activity_no'  => $goodsInfo['batch_no'],   // '奖品活动号'
            'award_origin' => '2',          // '奖品来源 1支撑 2旺财'
            'award_rate'   => 5,     // '中奖率'
            'total_count'  => 5,     // '奖品总数'
            'day_count'    => 5,       // '每日奖品数'
            'batch_type'   => $marketingInfo['batch_type'], 
            'cj_rule_id'   => $ruleInfo['id'],  // '抽奖规则id'
            'send_type'    => '0',              // '0-下发，1-不下发'
            'status'       => '1',              // '1正常 2停用'
            'cj_cate_id'   => $c_id,      // '抽奖类别id对应tcj_cate主键id'
            'add_time'     => date('YmdHis'),   // '奖品添加时间'
            'goods_id'     => $goodsInfo['goods_id'], 
            'b_id'         => $b_id
            );

        $cj_batch_id = M('tcj_batch')->add($data);
        if (!$cj_batch_id){
            M()->rollback();
            $this->error('奖品处理信息保存失败');
        }
        // 变更营销活动的抽奖标志
        if ($marketingInfo['is_cj'] == '0') {
            $data = array('is_cj' => '1'); // '抽奖活动id'
            $flag = M('tmarketing_info')->where(['id'=>$m_id])->save($data);
            if ($flag === false){
                M()->rollback();
                $this->error('营销活动抽奖标识设置失败！');
            }
        }
        
        // 扣减库存
        $goodsM = D('Goods');
        $flag = $goodsM->storagenum_reduc($goodsInfo['goods_id'],5, $cj_batch_id, '0', '');
        if ($flag === false){
            M()->rollback();
            $this->error($goodsM->getError());
        }
        M()->commit();
        $this->success('添加奖品成功');
    }
    /*
     * 添加奖品
     */
    public function jpAdd() {
        $rules = array(
            'batch_id' => array(
                'null' => false, 
                'strtype' => 'int', 
                'name' => '营销活动号'), 
            'goods_id' => array(
                'null' => false, 
                'name' => '商品号'), 
            // 'js_cate_id' => array('null'=>false,'strtype'=>'int',
            // 'name'=>'奖项号'),
            'day_count' => array(
                'null' => false, 
                'strtype' => 'int', 
                'minval' => '0', 
                'name' => '每日数量'), 
            'goods_count' => array(
                'null' => false, 
                'strtype' => 'int', 
                'minval' => '0', 
                'name' => '商品数量'), 
            'batch_amt' => array(
                'null' => true, 
                'minval' => '0', 
                'name' => '平安币'), 
            'jp_type' => array(
                'null' => true, 
                'strtype' => 'int', 
                'name' => '奖品类型'),  // 0 卡券 1 微信券
            'int_count' => array(
                'null' => true, 
                'strtype' => 'int', 
                'minval' => '0', 
                'name' => '积分值')); // 积分值
                                   
        // 河北太平洋保险，可不下发凭证
        if ($this->hbtpybx_flag) {
            $rules['send_type'] = array(
                'null' => false, 
                'strtype' => 'int', 
                'name' => '是否下发凭证', 
                'inarr' => array(
                    '0', 
                    '1'));
        }
        $req_data = $this->_verifyReqData($rules);
        $req_data['jp_type'] = intval($req_data['jp_type']);
        $js_cate_id = I('js_cate_id', 0, 'intval');
        // 判断是否设置新奖项
        if ($js_cate_id == 0) {
            $cate_name = I('cate_name', '', 'trim,mysql_real_escape_string');
            if ($cate_name == '')
                $this->error('请输入新奖项名称！');
            
            if (mb_strlen($cate_name, 'utf8') > 6)
                $this->error('新奖项名称超长！');
        }
        
        // 营销活动校验
        $map = array(
            'id' => $req_data['batch_id'], 
            'node_id' => $this->nodeId);
        $marketingInfo = M('tmarketing_info')->where($map)->find();
        if (! $marketingInfo)
            $this->error('未找到该营销活动');
            
            // 查询商品信息
        $map = array(
            'goods_id' => $req_data['goods_id'], 
            'node_id' => $this->nodeId, 
            'status' => '0');
        if ($req_data['goods_id'] == 'gw1400000000') { // 如果是积分,先看看goods_info里面有没有,没有的话加一条
            $jfGoods = D('Goods')->createJfGoods($this->node_id);
            if ($jfGoods['code'] == '0000') {
                $map['goods_id'] = $jfGoods['goods_id'];
                $req_data['goods_id'] = $jfGoods['goods_id'];
            } else {
                $this->error($jfGoods['msg']);
            }
        }
        $goodsInfo = M('tgoods_info')->where($map)->find();
        if (! $goodsInfo)
            $this->error('未找到该卡券');
            
            // 平安非标
        if ($goodsInfo['goods_type'] == '10') {
            extract($req_data);
        } elseif ($goodsInfo['goods_type'] == '12' || $goodsInfo['goods_type'] == '7') {// 定额红包
            extract($req_data);
            $verify_time_type = '0';
            $verify_begin_date = $goodsInfo['begin_time'];
            $verify_end_date = $goodsInfo['end_time'];
        } elseif (intval($req_data['jp_type']) == 0  && !in_array($goodsInfo['goods_type'], ['9', CommonConst::GOODS_TYPE_LLB, CommonConst::GOODS_TYPE_JF]) && !in_array($goodsInfo['source'], array(CommonConst::GOODS_SOURCE_SELF_CREATE_WXHB, CommonConst::GOODS_SOURCE_YIMA_CREATE_WXHB))) {// 非旺财卡券处理\非微信券,非积分,非微信红包
            $rules = array(
                        /*
                'mms_title' => array(
                    'null' => false, 
                    'maxlen_cn' => '10', 
                    'name' => '彩信标题'),
                'mms_text' => array(
                    'null' => false, 
                    'maxlen_cn' => '100', 
                    'name' => '使用说明'),
                */
                'verify_time_type' => array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'name' => '验证时间类型', 
                    'inarr' => array(
                        '0', 
                        '1')));
            // 翼蕙宝不验证短信内容
            if ($this->node_id == C('Yhb.node_id')) {
                unset($rules['mms_title']);
                unset($rules['mms_text']);
            }
            $req_data = array_merge($req_data, $this->_verifyReqData($rules));
            
            if ($req_data['verify_time_type'] == '0') {
                $rules = array(
                    'verify_begin_date' => array(
                        'null' => false, 
                        'strtype' => 'datetime', 
                        'format' => 'Ymd', 
                        'name' => '验证开始时间'), 
                    'verify_end_date' => array(
                        'null' => false, 
                        'strtype' => 'datetime', 
                        'format' => 'Ymd', 
                        'name' => '验证结束时间'));
            } else {
                $rules = array(
                    'verify_begin_days' => array(
                        'null' => false, 
                        'strtype' => 'int', 
                        'minval' => '0', 
                        'name' => '验证开始天数'), 
                    'verify_end_days' => array(
                        'null' => false, 
                        'strtype' => 'int', 
                        'minval' => '0', 
                        'name' => '验证结束天数'));
            }
            
            $req_data = array_merge($req_data, $this->_verifyReqData($rules));
            extract($req_data);
            
            $verify_begin_date .= '000000';
            $verify_end_date .= '235959';
            
            if ($verify_time_type == '0' && date('YmdHis') > $verify_end_date)
                $this->error('验证结束时间必须大于当前时间');
            if ($verify_time_type == '0' && $verify_begin_date > $verify_end_date)
                $this->error('验证开始时间不能大于验证结束时间');
        } else { // 旺财卡券处理
            extract($req_data);
//            $sms_text = $goodsInfo['sms_text'];
            $verify_time_type = $goodsInfo['verify_begin_type'];
            if ($verify_time_type == '0') {
                $verify_begin_date = $goodsInfo['verify_begin_date'];
                $verify_end_date = $goodsInfo['verify_end_date'];
            } else {
                $verify_begin_days = $goodsInfo['verify_begin_date'];
                $verify_end_days = $goodsInfo['verify_end_date'];
            }
        }
        $jp_type = I('jp_type',0);
        // 微信卡券
        if ($jp_type == 1) {
            $wx_card_id = I('wx_card_id', '', 'trim,mysql_real_escape_string');
            $map = array(
                'node_id' => $this->node_id, 
                'goods_id' => $req_data['goods_id'],
                'card_id' => $wx_card_id);
            $cardInfo = M('twx_card_type')->where($map)->find();
            if (! $cardInfo) {
                $this->error('未找到微信卡券信息');
            }
            if ($cardInfo['auth_flag'] != '2') {
                $this->error('微信卡券还未审核通过，无法添加为奖品！');
            }
        }else{
            if (!in_array($goodsInfo['goods_type'], 
                array(CommonConst::GOODS_TYPE_JF, CommonConst::GOODS_TYPE_HB, CommonConst::GOODS_TYPE_LLB, CommonConst::GOODS_TYPE_HF)) 
                && !in_array($goodsInfo['source'], 
                array(CommonConst::GOODS_SOURCE_SELF_CREATE_WXHB, CommonConst::GOODS_SOURCE_YIMA_CREATE_WXHB))) {
                    
                $useText = I('mms_text',null);
                if(empty($useText)){
                    $this->error('使用说明不能为空');
                }
            }
        }
        //特殊处理欧洲杯每日奖品（每日奖品限量就是奖品总量）
        if ($marketingInfo['batch_type'] == '61') {
            $day_count = $goods_count;
        }
        
        if ($day_count > $goods_count)
            $this->error('每日奖品数量不能大于奖品总数');
        
        if ($js_cate_id != 0) {
            // 奖项校验
            $map = array(
                'batch_id' => $batch_id, 
                'id' => $js_cate_id);
            $awardInfo = M('tcj_cate')->where($map)->find();
            if (! $awardInfo)
                $this->error('未找到奖项信息！');
        }
        
        // 获取cj_rule信息
        $ruleList = M('tcj_rule')->where(
            "batch_id = '{$batch_id}' and status = '1'")->select();
        $cnt = count($ruleList);
        if ($cnt == 0) {
            // $this->error('请先保存抽奖基础配置！');
            try {
                $ruleInfo = array();
                $ruleInfo['id'] = $this->_jpRuleInit($batch_id);
            } catch (Exception $e) {
                $this->error('抽奖规则保存失败！请重试');
            }
        } else if ($cnt > 1)
            $this->error('系统异常：存在多条启用的抽奖规则记录！');
        else
            $ruleInfo = $ruleList[0];
        
        $error = '';
        
        // 联盟商品，校验联盟商品的有效期和营销活动的有效期，营销活动的有效期必须包含联盟商品活动的有效期
        if ($goodsInfo['source'] == '2') {
            $m_begin_time = substr($marketingInfo['start_time'], 0, 8);
            $m_end_time = substr($marketingInfo['end_time'], 0, 8);
            $g_begin_time = substr($goodsInfo['begin_time'], 0, 8);
            $g_end_time = substr($goodsInfo['end_time'], 0, 8);
            if (max($m_begin_time, date('Ymd')) < max($g_begin_time, 
                date('Ymd')) || $m_end_time > $g_end_time)
                $this->error(
                    sprintf("添加失败！营销活动的有效期【%s到%s】不在该盟主商品的有效期【%s到%s】之内", 
                        dateformat($m_begin_time, 'Y-m-d'), 
                        dateformat($m_end_time, 'Y-m-d'), 
                        dateformat($g_begin_time, 'Y-m-d'), 
                        dateformat($g_end_time, 'Y-m-d')));
        }
        
        $cj_rule_id = $ruleInfo['id'];
        try {
            // 商品查询，由于涉及到库存扣减，需要锁记录
            M()->startTrans();
            
            $goodsInfo = M('tgoods_info')->where(
                "goods_id='{$goods_id}' AND node_id= '{$this->nodeId}' AND status=0")
                ->lock(true)
                ->find();
            
            // 库存校验
            if ($goodsInfo['storage_type'] == '1') {
                if ($goods_count > $goodsInfo['remain_num'])
                    throw new Exception("奖品数大于库存");
            }
            
            // 盟主商品，不允许修改短彩信内容
            if ($goodsInfo['source'] == '2') {
//                $mms_title = get_scalar_val($goodsInfo['mms_title'], '电子券');
                $mms_text = $goodsInfo['mms_text'];
            }
            
            // 微信卡券,且是限制库存的
            if ($jp_type == 1 && $cardInfo['quantity'] != 99999) {
                $cnt = (int) M('tbatch_info')->where("card_id = '{$wx_card_id}'")->sum(
                    'storage_num');
                if ($goods_count > ($cardInfo['quantity'] - $cnt))
                    throw new Exception("微信卡券库存不足！");
            }
            
            // 新增分类
            if ($js_cate_id == 0) {
                $map = array(
                    'batch_id' => $batch_id, 
                    'cj_rule_id' => $cj_rule_id, 
                    'name' => $cate_name);
                
                if (M('tcj_cate')->where($map)->count() > 0)
                    throw new Exception('奖项名称已经存在！');
                
                $data = array(
                    'batch_type' => $marketingInfo['batch_type'], 
                    'batch_id' => $marketingInfo['id'], 
                    'node_id' => $marketingInfo['node_id'], 
                    'cj_rule_id' => $cj_rule_id, 
                    'name' => $cate_name, 
                    'add_time' => date('YmdHis'), 
                    'status' => '1');
                // 'score'=>$score
                
                $js_cate_id = M('tcj_cate')->add($data);
                
                if ($js_cate_id === false)
                    throw new Exception('添加奖项失败！');
            }
            // 如果是积分奖品
            if ($goodsInfo['goods_type'] == CommonConst::GOODS_TYPE_JF) {
                $goodsInfo['goods_amt'] = $req_data['int_count'];
            }
            if (in_array($goodsInfo['source'], 
                array(CommonConst::GOODS_SOURCE_SELF_CREATE_WXHB, CommonConst::GOODS_SOURCE_YIMA_CREATE_WXHB))) {
                $goodsInfo['goods_image'] = './Home/Public/Image/eTicket/wechat_bf.png';
            }
            $data = array(
                'batch_no' => $goodsInfo['batch_no'], 
                'batch_short_name' => $jp_type == 1 ? $cardInfo['title'] : $goodsInfo['goods_name'], 
                'batch_name' => $jp_type == 1 ? $cardInfo['title'] : $goodsInfo['goods_name'], 
                'node_id' => $this->nodeId, 
                'user_id' => $this->userId, 
                'batch_class' => $goodsInfo['goods_type'],
                'batch_type' => $goodsInfo['source'], 
                'info_title' => '电子券',
//                'info_title' => get_scalar_val($mms_title,'电子券'),
//                'use_rule' => get_val($mms_text),
                'use_rule' => get_val($useText),
//                'sms_text' => get_val($sms_text),
                'batch_img' => $goodsInfo['goods_image'],
                'batch_amt' => $goodsInfo['goods_type'] != '10' ? $goodsInfo['goods_amt'] : $batch_amt, 
                'begin_time' => $marketingInfo['start_time'], 
                'end_time' => $marketingInfo['end_time'], 
                'send_begin_date' => $marketingInfo['start_time'], 
                'send_end_date' => $marketingInfo['end_time'], 
                'verify_begin_date' => $verify_time_type == '0' ? $verify_begin_date : $verify_begin_days, 
                'verify_end_date' => $verify_time_type == '0' ? $verify_end_date : $verify_end_days, 
                'verify_begin_type' => $verify_time_type, 
                'verify_end_type' => $verify_time_type, 
                'storage_num' => $goods_count, 
                'add_time' => date('YmdHis'), 
                'node_pos_group' => $goodsInfo['pos_group'], 
                'node_pos_type' => $goodsInfo['pos_group_type'], 
                'batch_desc' => $goodsInfo['goods_desc'], 
                'node_service_hotline' => $goodsInfo['node_service_hotline'], 
                'goods_id' => $goods_id, 
                'remain_num' => $goods_count, 
                'material_code' => $goodsInfo['customer_no'], 
                'print_text' => $goodsInfo['print_text'], 
                'm_id' => $marketingInfo['id'], 
                'validate_type' => $goodsInfo['validate_type'],
                'card_id' => get_val($wx_card_id));

            //短信内容
            $startUp = M('tnode_info')->where(array('node_id'=>$this->node_id))->getField('custom_sms_flag');
            if($startUp == 1  && $jp_type == 0 && in_array($goodsInfo['goods_type'],array('0','1','2','3')) && !in_array($goodsInfo['source'],array('6','7'))){
                $sms_text = I('cusMsg','');
                if(empty($sms_text)){
                    throw new Exception('短信内容不能空！');
                }else{
                    $data['sms_text'] = $sms_text;
                }
            }
            
            $result = M('tbatch_info')->add($data);
            if (! $result) {
                throw new Exception('数据库出错添加失败！');
            }
            
            $b_id = $result;
            
            // cj_batch奖品处理
            $data = array(
                'batch_id' => $batch_id,  // '抽奖活动id'
                'node_id' => $this->nodeId,  // '商户号'
                'activity_no' => $goodsInfo['batch_no'],  // '奖品活动号'
                'award_origin' => '2',  // '奖品来源 1支撑 2旺财'
                'award_rate' => $goods_count,  // '中奖率'
                'total_count' => $goods_count,  // '奖品总数'
                'day_count' => $day_count,  // '每日奖品数'
                'batch_type' => $marketingInfo['batch_type'],  // ,
                'cj_rule_id' => $cj_rule_id,  // '抽奖规则id'
                'send_type' => '0',  // '0-下发，1-不下发'
                'status' => '1',  // '1正常 2停用'
                'cj_cate_id' => $js_cate_id,  // '抽奖类别id对应tcj_cate主键id'
                'add_time' => date('YmdHis'),  // '奖品添加时间'
                'goods_id' => $goodsInfo['goods_id'], 
                'b_id' => $b_id);
            
            if ($this->hbtpybx_flag) {
                $data['send_type'] = $send_type;
            }
            
            $cj_batch_id = M('tcj_batch')->add($data);
            if ($cj_batch_id === false)
                throw new Exception('添加奖品失败！');
                
                // 变更营销活动的抽奖标志
            if ($marketingInfo['is_cj'] == '0') {
                $data = array(
                    'is_cj' => '1'); // '抽奖活动id'
                
                $flag = M('tmarketing_info')->where("id='{$batch_id}'")->save(
                    $data);
                if ($flag === false)
                    throw new Exception('营销活动抽奖标识设置失败！');
            }
            
            // 扣减库存
            $goodsM = D('Goods');
            $flag = $goodsM->storagenum_reduc($goodsInfo['goods_id'], 
                $goods_count, $cj_batch_id, '0', '');
            if ($flag === false)
                throw new Exception($goodsM->getError());
        } 

        catch (Exception $e) {
            M()->rollback();
            $this->error($e->getMessage());
        }
        
        M()->commit();
        
        node_log("营销活动奖品添加", print_r($_POST, TRUE));
        
        $showArr = array(
            'cj_batch_id' => $cj_batch_id);

        if ($marketingInfo['batch_type'] == CommonConst::BATCH_TYPE_WEEL) { //幸运大转盘 做特殊处理
            if (empty($this->DrawLotteryBaseService)) {
                $this->DrawLotteryBaseService = D('DrawLotteryBase', 'Service');
            }
//            $this->DrawLotteryBaseService->generateDrawLotteryFinalDataWithStorage($batch_id);
            $this->DrawLotteryBaseService->modifySinglePrizeWithOtherInfo($batch_id, $b_id, $cj_batch_id);
        }

        // 新版抽奖，返回所有奖品信息
        if (I('version', 0, 'intval') == 3) {
            $this->_returnJpList($batch_id);
        }
        $this->ajaxReturn($showArr, '奖品添加成功！', 1);
    }

    /**
     * 返回指定营销活动的所有奖品信息
     */
    public function _returnJpList($batch_id) {
        $map = array(
            'node_id' => $this->node_id, 
            'batch_id' => $batch_id, 
            'status' => 1);
        $ruleInfo = M('tcj_rule')->where($map)->find();
        if (! $ruleInfo) {
            $this->ajaxReturn(array(), '奖品添加成功！', 1);
        }
        
        $map = array(
            'cj_rule_id' => $ruleInfo['id'], 
            'batch_id' => $batch_id, 
            'status' => 1);
        $cate_arr = M('tcj_cate')->where($map)->select();
        if (! $cate_arr) {
            $this->ajaxReturn(array(), '奖品添加成功！', 1);
        }
        
        $cate_arr = array_valtokey($cate_arr, 'id');
        
        $map = array(
            'a.cj_rule_id' => $ruleInfo['id'], 
            'a.batch_id' => $batch_id, 
            );
        $list = M('tcj_batch')
        ->alias('a')
        ->field('a.*, b.batch_short_name,b.batch_class,c.online_verify_flag')
        ->where($map)
        ->join('tbatch_info b on a.b_id = b.id')
        ->join('tgoods_info c on a.goods_id = c.goods_id')
        ->select();
        foreach ($list as $jp) {
            $cate_arr[$jp['cj_cate_id']]['child'][] = $jp;
        }
        
        $this->ajaxReturn(array_values($cate_arr), '奖品保存成功！', 1);
    }

    public function _verifyReqData($rules, $return = false, $method = 'post', $value_array = null) {
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
    
    // 编辑奖品
    public function jpEdit() {
        // ################基础参数校验#################
        $rules = array(
            'batch_id' => array(
                'null' => false, 
                'strtype' => 'int', 
                'name' => '营销活动号'), 
            'cj_batch_id' => array(
                'null' => false, 
                'strtype' => 'int', 
                'name' => '奖品号'), 
            'day_count' => array(
                'null' => false, 
                'strtype' => 'int', 
                'minval' => '0', 
                'name' => '每日数量'), 
            'goods_count' => array(
                'null' => false, 
                'strtype' => 'int', 
                'minval' => '0', 
                'name' => '商品数量'), 
            'batch_amt' => array(
                'null' => true, 
                'minval' => '0', 
                'name' => '平安币'), 
            'ignore_daycount' => array(
                'null' => true, 
                'strtype' => 'int', 
                'minval' => '0', 
                'maxval' => '1', 
                'name' => '商品数量'), 
            'int_count' => array(
                'null' => true, 
                'strtype' => 'int', 
                'minval' => '0', 
                'name' => '商品数量'));
        
        // 河北太平洋保险，可不下发凭证
        if ($this->hbtpybx_flag) {
            $rules['send_type'] = array(
                'null' => false, 
                'strtype' => 'int', 
                'name' => '是否下发凭证', 
                'inarr' => array(
                    '0', 
                    '1'));
        }
        $req_data = $this->_verifyReqData($rules);
        
        // ################有效性校验#################
        // 获取cj_rule_id
        $ruleList = M('tcj_rule')->where(
            "batch_id = '{$req_data['batch_id']}' and status = '1'")->select();
        if (! $ruleList)
            $this->error('请先保存抽奖基础配置！');
        
        if (count($ruleList) > 1)
            $this->error('系统异常：存在多条启用的抽奖规则记录！');
        $ruleInfo = $ruleList[0];
        $cj_rule_id = $ruleInfo['id'];
        
        $map = array(
            'node_id' => $this->nodeId, 
            'batch_id' => $req_data['batch_id'], 
            'cj_rule_id' => $cj_rule_id, 
            'id' => $req_data['cj_batch_id']);
        $cjBatchInfo = M('tcj_batch')->where($map)->find();
        if (! $cjBatchInfo)
            $this->error('参数错误！');
        
        $batchInfo = M('tbatch_info')->where("id = '{$cjBatchInfo['b_id']}'")->find();
        
        $goodsInfo = M('tgoods_info')->where(
            "goods_id = '{$cjBatchInfo['goods_id']}'")->find();
        if (! $goodsInfo)
            $this->error('参数错误！');
            // ################非旺财卡券参数校验#################
        if (! in_array($goodsInfo['goods_type'], 
            array(
                CommonConst::GOODS_TYPE_HF,
                '9', 
                '10', 
                '12', 
                '14',
                CommonConst::GOODS_TYPE_LLB, 
                '22')) && $batchInfo['card_id'] == '' 
            && !in_array($goodsInfo['source'], 
                array(
                    CommonConst::GOODS_SOURCE_SELF_CREATE_WXHB, 
                    CommonConst::GOODS_SOURCE_YIMA_CREATE_WXHB
                ))) {
            $rules = array(
                /*
                'mms_title' => array(
                    'null' => false, 
                    'maxlen_cn' => '10', 
                    'name' => '彩信标题'),
                'mms_text' => array(
                    'null' => false, 
                    'maxlen_cn' => '100', 
                    'name' => '使用说明'),
                */
                'verify_time_type' => array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'name' => '验证时间类型', 
                    'inarr' => array(
                        '0', 
                        '1')));
            // 翼蕙宝不验证短信内容
            if ($this->node_id == C('Yhb.node_id')) {
                unset($rules['mms_title']);
                unset($rules['mms_text']);
            }
            $req_data = array_merge($req_data, $this->_verifyReqData($rules));
            
            if ($req_data['verify_time_type'] == '0') {
                $rules = array(
                    'verify_begin_date' => array(
                        'null' => false, 
                        'strtype' => 'datetime', 
                        'format' => 'Ymd', 
                        'name' => '验证开始时间'), 
                    'verify_end_date' => array(
                        'null' => false, 
                        'strtype' => 'datetime', 
                        'format' => 'Ymd', 
                        'name' => '验证结束时间'));
            } else {
                $rules = array(
                    'verify_begin_days' => array(
                        'null' => false, 
                        'strtype' => 'int', 
                        'minval' => '0', 
                        'name' => '验证开始天数'), 
                    'verify_end_days' => array(
                        'null' => false, 
                        'strtype' => 'int', 
                        'minval' => '0', 
                        'name' => '验证结束天数'));
            }
            $useText = I('mms_text',null);
            if(empty($useText)){
                $this->error('使用说明不能为空');
            }
            
            $req_data = array_merge($req_data, $this->_verifyReqData($rules));
            
            if ($req_data['verify_time_type'] == '0' &&
                 date('Ymd') > $req_data['verify_end_date'])
                $this->error('验证结束时间必须大于当前时间');
            if ($req_data['verify_time_type'] == '0' &&
                 $req_data['verify_begin_date'] > $req_data['verify_end_date'])
                $this->error('验证开始时间不能大于验证结束时间');
        }
        
        if (! $req_data['ignore_daycount']) {
            if ($req_data['day_count'] > $req_data['goods_count'])
                $this->error('日中奖次数不能大于总中奖次数');
        }
        $map = array(
            'rule_id' => $req_data['cj_batch_id']);
        $count = M('taward_daytimes')->where($map)->sum('award_times');
        if ($count > $req_data['goods_count'])
            $this->error('已中奖数已大于奖品总数！');
        
        $map['trans_date'] = date('Ymd');
        $count = M('taward_daytimes')->where($map)->getField('award_times');
        if ($count > $req_data['day_count'])
            $this->error('当日中奖数大于每日奖品限量！');
            
            // 查询营销活动
        $marketingInfo = M('tmarketing_info')->where(
            array(
                'node_id' => $this->nodeId, 
                'id' => $req_data['batch_id']))->find();
        if (! $marketingInfo)
            $this->error("参数错误！未找到营销活动");
        //特殊处理欧洲杯每日奖品（每日奖品限量就是奖品总量）
        if ($marketingInfo['batch_type'] == '61') {
            $req_data['day_count'] = $req_data['goods_count'];
        }
            
        M()->startTrans();
        
        try {
            $goodsInfo = M('tgoods_info')->where(
                "goods_id = '{$cjBatchInfo['goods_id']}'")
                ->lock(true)
                ->find();
            if (! $goodsInfo) {
                throw new Exception('系统异常：商品不存在！');
            }
            $batchInfo = M('tbatch_info')->where(
                "id = '{$cjBatchInfo['b_id']}'")
                ->lock(true)
                ->find();
            if($marketingInfo['status'] == '1' && $batchInfo['storage_num'] != - 1 && $batchInfo['storage_num'] != $req_data['goods_count'])
            {
                throw new Exception("活动进行中，无法编辑库存");
            }
            // 库存变动
            $goodsM = D('Goods');
            $flag = $goodsM->adjust_batch_storagenum($cjBatchInfo['b_id'], 
                $req_data['goods_count'], $req_data['cj_batch_id']);
            if ($flag === false)
                throw new Exception($goodsM->getError());
            
            $batch_remain_num = $req_data['goods_count'] -
                 ($batchInfo['storage_num'] - $batchInfo['remain_num']);
            $data = array(
                'remain_num' => $batch_remain_num, 
                'storage_num' => $req_data['goods_count'],
                'update_time' => date('YmdHis'));
            //短信内容
            $startUp = M('tnode_info')->where(array('node_id'=>$this->node_id))->getField('custom_sms_flag');
            if($startUp == 1  && in_array($goodsInfo['goods_type'],array('0','1','2','3')) && !in_array($goodsInfo['source'],array('6','7')) && empty($batchInfo['card_id'])){
                $sms_text = I('cusMsg','');
                if(empty($sms_text)){
                    throw new Exception('短信内容不能空！');
                }else{
                    $data['sms_text'] = $sms_text;
                }
            }

            if ($goodsInfo['source'] != '2' && $goodsInfo['goods_type'] != '9' &&
                 $goodsInfo['goods_type'] != '10') {
                $data = array_merge($data, 
                    array(
//                        'use_rule' => $req_data['mms_text'],
                        'use_rule' => get_val($useText),
                        'info_title' => $req_data['mms_title']?$req_data['mms_title'] : '电子券',
                        'verify_begin_date' => $req_data['verify_time_type'] ==
                         '0' ? $req_data['verify_begin_date'] . '000000' : $req_data['verify_begin_days'], 
                        'verify_end_date' => $req_data['verify_time_type'] == '0' ? $req_data['verify_end_date'] .
                         '235959' : $req_data['verify_end_days'], 
                        'verify_begin_type' => $req_data['verify_time_type'], 
                        'verify_end_type' => $req_data['verify_time_type']));
            }
            // 平安非标
            if ($goodsInfo['goods_type'] == '10') {
                $data['batch_amt'] = $req_data['batch_amt'];
            }
            // 积分值修改
            if ($goodsInfo['goods_type'] == '14') {
                $data['batch_amt'] = $req_data['int_count'];
            }
            $flag = M('tbatch_info')->where("id = '{$batchInfo['id']}'")->save(
                $data);
            if ($flag === false)
                throw new Exception('奖品活动信息更新失败！');
                
                // 更新tcj_batch
            $save_arr = array(
                'total_count' => $req_data['goods_count'], 
                'day_count' => $req_data['day_count'], 
                'award_rate' => $req_data['goods_count']);
            
            if ($this->hbtpybx_flag) {
                $save_arr['send_type'] = $req_data['send_type'];
            }
            $query = M('tcj_batch')->where(
                array(
                    'id' => $req_data['cj_batch_id']))->save($save_arr);
            if ($query === false)
                throw new Exception('奖品设置更新失败！');
            
            if ($flag === false)
                throw new Exception($goodsM->getError());
        } catch (Exception $e) {
            M()->rollback();
            $this->error($e->getMessage());
        }
        
        M()->commit();
        // 更新redis缓存
        if (empty($this->DrawLotteryBaseService)) {
            $this->DrawLotteryBaseService = D('DrawLotteryBase', 'Service');
        }
//        $this->DrawLotteryBaseService->generateDrawLotteryFinalDataWithStorage($marketingInfo['id']);
        $this->DrawLotteryBaseService->modifySinglePrizeWithOtherInfo(
                $marketingInfo['id'],
                $batchInfo['id'],
                $req_data['cj_batch_id']
        );
        node_log("营销活动奖品编辑", print_r($_POST, TRUE));
        
        // 新版抽奖，返回所有奖品信息
        if (I('version', 0, 'intval') == 3) {
            $this->_returnJpList($req_data['batch_id']);
        }
        if ($marketingInfo['batch_type'] == CommonConst::BATCH_TYPE_WEEL && $marketingInfo['status'] == 2) { //幸运大转盘且为停用状态 做特殊处理
            if (empty($this->DrawLotteryBaseService)) {
                $this->DrawLotteryBaseService = D('DrawLotteryBase', 'Service');
            }
            $this->DrawLotteryBaseService->generateDrawLotteryFinalDataWithStorage($req_data['batch_id']);
        }
        $this->success('编辑成功！');
    }
    
    // 1 启用 2 停用
    public function _jpStatusChg($status) {
        $rules = array(
            'batch_id' => array(
                'null' => false, 
                'strtype' => 'int', 
                'name' => '营销活动号'), 
            'cj_batch_id' => array(
                'null' => false, 
                'strtype' => 'int', 
                'name' => '奖品号'));
        
        $req_data = $this->_verifyReqData($rules);
        
        // 查询营销活动
        $map = array(
            'node_id' => $this->nodeId, 
            'batch_id' => $req_data['batch_id'], 
            'id' => $req_data['cj_batch_id']);
        $info = M('tcj_batch')->where($map)->find();
        if (! $info)
            $this->error('参数错误！');
        
        if ($info['status'] == $status)
            $this->success('操作成功！');
        
        $flag = M('tcj_batch')->where($map)->save(
            array(
                'status' => $status));
        if ($flag === false)
            $this->error('操作失败！请重试');
        
        node_log("营销活动奖品停用启用", print_r($_POST, TRUE));
        
        $this->success('操作成功！');
    }
    
    // 编辑停用
    public function jpStop() {
        $this->_jpStatusChg('2');
    }
    
    // 编辑启用
    public function jpStart() {
        $this->_jpStatusChg('1');
    }
    
    // 奖品分组删除
    public function jpCateDel() {
        $rules = array(
            'cj_cate_id' => array(
                'null' => false, 
                'strtype' => 'int', 
                'minval' => '0', 
                'name' => '奖项ID'), 
            'batch_id' => array(
                'null' => false, 
                'strtype' => 'int', 
                'name' => '营销活动号'));
        
        $req_data = $this->_verifyReqData($rules);
        extract($req_data);
        
        // 查询
        $map = array(
            'node_id' => $this->nodeId, 
            'batch_id' => $batch_id, 
            'cj_cate_id' => $cj_cate_id);
        $cnt = M('tcj_batch')->where($map)->count();
        if ($cnt > 0)
            $this->error('该奖项下存在奖品，不允许删除');
        
        $map = array(
            'node_id' => $this->nodeId, 
            'batch_id' => $batch_id, 
            'id' => $cj_cate_id);
        M()->startTrans();
        // 由于新增了排序，删除奖项时要更新排序
        $cjCateModel = M('tcj_cate');
        $cjCate = $cjCateModel->where(
            array(
                'node_id' => $this->node_id, 
                'batch_id' => $batch_id))->getField('id,sort', true);
        if ($cjCate) {
            foreach ($cjCate as $k => $v) {
                if (get_val($v,'sort') > $cjCate[$cj_cate_id]) {
                    $updateSortRe = $cjCateModel->where(
                        array(
                            'id' => $k))->save(
                        array(
                            'sort' => ($v['sort'] - 1)));
                    if (false === $updateSortRe) {
                        M()->rollback();
                        $this->error('更新排序失败！请重试！');
                    }
                }
            }
        }
        M()->commit();
        $flag = M('tcj_cate')->where($map)->delete();
        if ($flag === false) {
            $this->error('奖项删除失败！请重试！');
        }
        node_log("营销活动奖项删除", print_r($_POST, TRUE));
        $this->success('奖项删除成功！');
    }

    /**
     * 验码时间校验 array( 'm_begin_time', 'm_end_time', 'g_begin_time', 'g_end_time',
     * 'verify_time_type', 'verify_begin_date', 'verify_end_date',
     * 'verify_begin_days', 'verify_end_days', ) 营销活动有效期 T1 ~ T2 商品有效期 T3 ~ T4
     * 卡券验证时间 T5 ~ T6 支撑活动时间 T7 ~ T8 1.选择卡券时需满足一下条件： T4 >= T2 T3 <= max(T1, now)
     * 2.提交卡券 T7 = T1 T8 = max(T2, T6) 不判断与 T4
     */
    public function _checkVerifyDate($params) {
        $error = '';
        $m_begin_time = $params['m_begin_time'];
        $m_end_time = $params['m_end_time'];
        $g_begin_time = $params['g_begin_time'];
        $g_end_time = $params['g_end_time'];
        $verify_time_type = $params['verify_time_type'];
        $verify_begin_date = $params['verify_begin_date'];
        $verify_end_date = $params['verify_end_date'];
        $verify_begin_days = $params['verify_begin_days'];
        $verify_end_days = $params['verify_end_days'];
        
        // 验码开始时间转换成日期格式
        switch ($verify_time_type) {
            case '0':
                if ($verify_begin_date > $verify_end_date)
                    throw new Exception("卡券使用结束时间不能小于使用开始时间");
                $v_begin_date = $verify_begin_date . '000000';
                $v_end_date = $verify_end_date . '235959';
                break;
            case '1':
                if ($verify_begin_date > $verify_end_date)
                    throw new Exception("卡券使用结束时间不能小于使用开始时间");
                
                $v_begin_date = $verify_begin_days;
                $v_end_date = $verify_end_days;
                break;
            default:
                throw new Exception('请填卡券使用时间信息');
        }
        
        $verifyData = array(
            'begin_time' => $m_begin_time,  // 活动开始时间
            'end_time' => $m_end_time,  // 活动结束时间
            'send_begin_date' => $m_begin_time,  // 发码开始时间
            'send_end_date' => $m_end_time,  // 发码结束时间
            'verify_begin_date' => $v_begin_date,  // 验码开始时间或天数
            'verify_end_date' => $v_end_date,  // 验码结束时间或天数
            'verify_begin_type' => $verify_time_type,  // 验码开始时间类型
            'verify_end_type' => $verify_time_type); // 验码结束时间类型
        
        return $verifyData;
        
        // 商品的有效期不能小于等于活动的有效期
        /*
         * if($g_end_time < $m_end_time) throw new Exception
         * ('卡券的有效期-截止日期【'.dateformat($g_end_time,
         * 'Y-m-d').'】不能小于营销活动的有效期-截止日期【'.dateformat($m_begin_time,
         * 'Y-m-d').'】');
         */
        
        // 商品的开始时间必须小于等于 max(活动的开始时间,当前时间)
        /*
         * if( $g_begin_time > max($m_begin_time, date('YmdHis')) ){ if(
         * $m_begin_time >= date('YmdHis') ) $msg =
         * "营销活动的有效期-开始【".dateformat($m_begin_time, 'Y-m-d')."】"; else $msg =
         * "当前时间【".date('Ymd')."】"; throw new Exception
         * ('卡券的有效期-开始日期【'.dateformat($abc, 'Y-m-d').'】不能大于'.$msg); }
         */
        // 验码结束时间要大于等于发码结束时间
        if ($beginverifyDate > $endverifyDate)
            throw new Exception('卡券使用开始时间不能大于卡券使用结束时间');
            
            // 验码结束时间要大于等于发码结束时间
        if ($m_end_time > $endverifyDate)
            $this->error('卡券使用结束时间要大于营销活动结束时间');
            
            // if($beginverifyDate<$beginTime) throw new
            // Exception('卡券使用开始时间不能小于营销活动的开始时间');
            
        // 计算活动开始时间和结束时间
        $beginTime = $m_begin_time;
        // 类型为日期：活动结束时间=发码结束时间和验码结束时间两者之间的最大值
        strtotime($m_end_time) > strtotime($endverifyDate) ? $endTime = $m_end_time : $endTime = $endverifyDate;
    }

    /**
     * 创建电子合约
     */
    public function _createPgoods($goods_id) {
        $goodsInfo = M('tgoods_info')->where("goods_id = '$goods_id'")->find();
        
        if ($goodsInfo['p_goods_id'] != '')
            return $goodsInfo['p_goods_id'];
            
            // 盟主商品不需要电子合约号
        if ($goodsInfo['source'] == '2')
            return '';
        
        $ShopNodeId = $goodsInfo['node_id'];
        $GroupId = $goodsInfo['pos_group'];
        // 采购商品
        if ($goodsInfo['source'] == '1') {
            $buyGoodsInfo = M('tgoods_info')->where(
                "goods_id = '{$goodsInfo['purchase_goods_id']}'")->find();
            $ShopNodeId = $buyGoodsInfo['node_id'];
            $GroupId = $buyGoodsInfo['pos_group'];
        }
        
        $req_array = array(
            'CreateTreatyReq' => array(
                'SystemID' => C('ISS_SYSTEM_ID'), 
                'RequestSeq' => date("YmdHis") . mt_rand(100000, 999999), 
                'ShopNodeId' => $ShopNodeId, 
                'BussNodeId' => $goodsInfo['node_id'], 
                'TreatyName' => $goodsInfo['goods_name'], 
                'TreatyShortName' => $goodsInfo['goods_name'], 
                'StartTime' => date('Ymd000000'), 
                'EndTime' => '20301231235959', 
                'GroupId' => $GroupId, 
                'PrintText' => $goodsInfo['print_text'], 
                'CustmomNo' => $goodsInfo['customer_no'], 
                'GoodsName' => $goodsInfo['goods_name'], 
                'GoodsShortName' => $goodsInfo['goods_name'], 
                'GoodsCustmomNo' => $goodsInfo['customer_no']));
        
        $RemoteRequest = D('RemoteRequest', 'Service');
        $resp_array = $RemoteRequest->requestIssForImageco($req_array);
        $ret_msg = $resp_array['CreateTreatyRes']['Status'];
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000')) {
            throw new Exception("支撑电子合约创建失败！原因:{$ret_msg['StatusText']}");
        }
        
        $p_goods_id = $resp_array['CreateTreatyRes']['TreatyId'];
        
        $data = array(
            'p_goods_id' => $p_goods_id);
        
        $flag = M('tgoods_info')->where("goods_id = '{$goods_id}'")->save($data);
        if ($flag === false)
            throw new Exception("电子合约号保存失败！");
        
        return $p_goods_id;
    }

    public function addToPrizeItem() {
        $m_id = I('m_id', ''); // 活动id
        $prizeCateId = I('prizeCateId', ''); // 奖项id
        $prizeId = I('prizeId', '', 'trim'); // 奖品的goods_id
        $b_id = I('b_id', '', 'trim'); // 如果有这个值表示是编辑奖品
        if ($m_id) {
            // 判断活动号是不是这个机构的
            $basicInfo = M('tmarketing_info')->where(
                array(
                    'node_id' => $this->node_id, 
                    'id' => $m_id))->find();
            if (! $basicInfo) {
                $this->error('传入参数有误', '', true);
            }
            $this->assign('marketInfo',$basicInfo);
            // 查询所有奖项
            $cate_arr = M('tcj_cate')->where(
                array(
                    'node_id' => $this->node_id, 
                    'batch_id' => $m_id))->getField('id', true);
            // 查询传过来的奖项是不是该机构的
            if (! in_array($prizeCateId, $cate_arr)) {
                $this->error('传入参数有误', '', true);
            }
        }
        //自定义短信内容
        $cusMsg = '';
        if ($b_id) { // 编辑奖品
            $batch_info = M('tbatch_info')->alias('a')
                ->field(
                'b.day_count,a.remain_num,a.verify_begin_type,
			        a.batch_amt,a.sms_text,
					a.verify_begin_date,a.verify_end_date,a.info_title,a.card_id,
					a.use_rule,b.total_count,b.id as cj_batch_id,
					c.goods_type, c.goods_amt,c.goods_id, c.source, 
					c.remain_num as goods_remain_num,c.goods_name,
    				c.begin_time as goods_begin_date, c.end_time as goods_end_date')
                ->join('tcj_batch b on a.id = b.b_id')
                ->join('tgoods_info c on b.goods_id = c.goods_id')
                ->where(array(
                'a.id' => $b_id))
                ->select();
            //使用说明
            $prizeData = M('tbatch_info')->where(array('id'=>$b_id))->find();

            $this->assign('prizeData', $prizeData);
            if (! $batch_info) {
                $this->error('传入参数有误', '', true);
            }
            $verify_begin_date = $batch_info[0]['verify_begin_date'];
            $verify_end_date = $batch_info[0]['verify_end_date'];
            $verify_begin_type = $batch_info[0]['verify_begin_type'];
            $cardId = $batch_info[0]['card_id']; // 微信卡券id
            $goodsType = $batch_info[0]['goods_type'];
            $this->assign('day_count', $batch_info[0]['day_count']);
            $this->assign('remain_num', $batch_info[0]['remain_num']);
            $this->assign('info_title', $batch_info[0]['info_title']);
            $this->assign('use_rule', $batch_info[0]['use_rule']); // 彩信内容
            $this->assign('total_count', $batch_info[0]['total_count']); // 奖品总数
            $this->assign('cj_batch_id', $batch_info[0]['cj_batch_id']);
            $this->assign('goods_remain_num',$batch_info[0]['goods_remain_num']);
            $this->assign('goodsType',$batch_info[0]['goods_type']);        //奖品类型
            
            // 是否显示输入积分的框
            $showJf = ($goodsType == CommonConst::GOODS_TYPE_JF) ? true : false;
            $this->assign('showJf', $showJf);
            $this->assign('batch_amt', $batch_info[0]['batch_amt']); // 积分值
            
            $goodsName = $batch_info[0]['goods_name'];
            $goodsAmt = $batch_info[0]['goods_amt'];
            // 取出goods_id下面需要判断显示不显示微信卡券发送方式
            $prizeId = $batch_info[0]['goods_id'];
            $goodsSource = $batch_info[0]['source'];
            $cusMsg = $prizeData['sms_text'];
        } else { 
            $cardId = I('get.card_id', 0, 'int');
        // 添加奖品
                 // 查询准备添加的奖品是否是这个机构下的
            $goodsInfo = M('tgoods_info')->where(
                array(
                    'node_id' => $this->node_id, 
                    'goods_id' => $prizeId))->find();
            if (! $goodsInfo) {
                $this->error('传入参数有误', '', true);
            }
            $verify_begin_date = $basicInfo['start_time'];
            $verify_end_date = $basicInfo['end_time'];
            $verify_begin_type = '0';
            $this->assign('goods_remain_num', $goodsInfo['remain_num']);
            $goodsType = $goodsInfo['goods_type'];
            $goodsName = $goodsInfo['goods_name'];
            $goodsAmt = $goodsInfo['goods_amt'];
            if ($goodsType == CommonConst::GOODS_TYPE_TLQ && $goodsInfo['online_verify_flag'] == '1') {
                $this->assign('use_rule', '本券支持线上提领'); // 彩信内容
            }
            $goodsSource = $goodsInfo['source'];
        }
        
        //获取发码费用价格
        $sendPrice = D('ActivityPayConfig')->getSendFee($this->node_id);
        $sendPrice = '';
        $this->assign('sendPrice', $sendPrice);
        
        // 用于判断是否显示"下发形式"的哪个选项（编辑的时候都不能选，添加的时候积分是另外的界面，所以红包这里控制不显示就可以了）
        $this->assign('goodsType', $goodsType);
        // 用goods_source为6和7判断微信红包不显示
        $this->assign('goodsSource', $goodsSource);

        /******************整理示例图中的短信文字************/
        //企业简称
        $storeShortName = session('userSessInfo.node_short_name');
        //企业简称的字数差值
        $storeDifference = 6-mb_strlen($storeShortName,'utf8');
        if($storeDifference < 0){          //企业简称字数超出时
            $storeShortName = mb_substr($storeShortName,0,6,'utf8');
            //当卡券名称大于11个字的时候。。。。以下相同
            if(mb_strlen($goodsName,'utf8') > 11){
                $cardName = mb_substr($goodsName,0,10,'utf8').'...';
            }else{
                $cardName = mb_substr($goodsName,0,11,'utf8');
            }
        }elseif($storeDifference > 0){     //企业简称字数有结余时
            if(mb_strlen($goodsName,'utf8') > (11+abs($storeDifference))){
                $cardName = mb_substr($goodsName,0,(10+abs($storeDifference)),'utf8').'...';
            }else{
                $cardName = mb_substr($goodsName,0,(11+abs($storeDifference)),'utf8');
            }
        }else{
            if(mb_strlen($goodsName,'utf8') > 11){
                $cardName = mb_substr($goodsName,0,10,'utf8').'...';
            }else{
                $cardName = mb_substr($goodsName,0,11,'utf8');
            }
        }
        $smsContent = '【'.$storeShortName.'】的'.$cardName;
        $this->assign('smsContent', $smsContent);
        //自定义短信内容
        $startUp = M('tnode_info')->where(array('node_id'=>$this->node_id))->getField('custom_sms_flag');
        $this->assign('startUp',$startUp);
        $this->assign('cusMsg', $cusMsg);

        if ($verify_begin_type == 0) {
            $verify_begin_date = substr($verify_begin_date, 0, 8);
            $verify_end_date = substr($verify_end_date, 0, 8);
        }
        // 如果商品是q币或者话费彩短信要用固定模板且readonly
        if ($goodsType == CommonConst::GOODS_TYPE_HF ||
             $goodsType == CommonConst::GOODS_TYPE_QB) {
            $goodsAmt = intval($goodsAmt);
            if ($goodsType == CommonConst::GOODS_TYPE_HF) {
                $txtContent = '您已获得' . $goodsAmt .
                     '元手机话费，点击[#GET_URL]，提交待充值手机号，即可领取！领取截止时间：[#END_DATE]。';
                $txtTitle = $goodsAmt . '元手机话费';
            }
            if ($goodsType == CommonConst::GOODS_TYPE_QB) {
                $txtContent = '您已获得' . $goodsAmt .
                     '元Q币，点击[#GET_URL],即可领取！领取截止时间：[#END_DATE]。';
                $txtTitle = $goodsAmt . '元Q币';
            }
            $this->assign('txt_content', $txtContent);
            $this->assign('txt_title', $txtTitle);
        }
        $availableSendType = I('availableSendType', '0,1'); // 默认(短信+微信)下发
        if (I('callback') == 'priseitem' || I('callback') == 'priseitem_tiack'){
            $canUseCard = true;
        }else{
            $canUseCard = D('DrawLotteryAdmin')->canUseCard($this->node_id, $m_id);
            // 选择奖品时，如果遇到不能选择卡券的情况(选手机参与方式的不能选微信卡券)
        }
        if (! $canUseCard) {
            $availableSendType = $this->removeWeiXinSendType($availableSendType);
        }
        // 卡券发送形式
        $cardInfoAndSendTypeArr = D('SelectJp')->getSendTypeArr($prizeId, $availableSendType, $m_id);
        $this->assign('sendTypeArr', $cardInfoAndSendTypeArr['sendTypeArr']);
        $cardId = get_val($cardId);
        // 默认选中的发送方式
        if ($availableSendType == '1') {
            //如果可选的发送方式只有微信卡券，那么默认选中卡券
            $selectType = CommonConst::SEND_PRIZE_TYPE_WX_CARD;
        } else {
            $selectType = $cardId ? CommonConst::SEND_PRIZE_TYPE_WX_CARD : CommonConst::SEND_PRIZE_TYPE_TEXT;
        }
        $this->assign('selectType', $selectType);
        
        $this->assign('wx_card_date_beginTimeStamp', 
            date('Ymd', 
                $cardInfoAndSendTypeArr['cardInfo']['date_begin_timestamp']));
        $this->assign('wx_card_date_endTimeStamp', 
            date('Ymd', 
                $cardInfoAndSendTypeArr['cardInfo']['date_end_timestamp']));
        
        $this->assign('goodsCardId', 
            $cardInfoAndSendTypeArr['cardInfo']['card_id']);
        //微信卡券结束时间小于活动时间时，不可选，颜色为灰色
        $cardOverTime = false;
        if ($cardInfoAndSendTypeArr['cardInfo']) {
            $cardOverTime = $cardInfoAndSendTypeArr['cardInfo']['overTime'];
        }
        $this->assign('cardOverTime', $cardOverTime);
        
        // 是否显示短信输入框
        $isShowMms = 1;
        // 微信卡券,红包,积分,微信红包不显示
        if (in_array($goodsType, array(
            CommonConst::GOODS_TYPE_HB, 
            CommonConst::GOODS_TYPE_JF, 
            CommonConst::GOODS_TYPE_LLB,
            CommonConst::GOODS_TYPE_HF
        ))
            || in_array($goodsSource, array(CommonConst::GOODS_SOURCE_SELF_CREATE_WXHB, CommonConst::GOODS_SOURCE_YIMA_CREATE_WXHB))
            ) {
            $isShowMms = 0;
        }
        $this->assign('isShowMms', $isShowMms);
        $this->assign('verify_begin_date', $verify_begin_date);
        $this->assign('verify_end_date', $verify_end_date);
        $this->assign('verify_begin_type', $verify_begin_type);
        $this->assign('b_id', $b_id);
        $this->assign('m_id', $m_id);
        $this->assign('prizeCateId', $prizeCateId);
        $this->assign('prizeId', $prizeId);
        $this->assign('activity_end_time', $basicInfo['end_time']);
        $this->assign('cardId', $cardId);
        $this->assign('jp_type', ($cardId ? 1 : 0)); // 0卡券 1微信卡券
        //欧洲杯需要用于判断是否显示每日奖品上限
        $this->assign('batchType', isset($basicInfo['batch_type']) ? $basicInfo['batch_type'] : '');
        $this->display();
    }

    public function canStart() {
        $mId = I('m_id');
        $cjBatchId = I('cj_batch_id');
        $result = D('SelectJp')->canStart($this->node_id, $mId, $cjBatchId);
        if ($result['errorCode'] == '0000') {
            if ($result['canStart'] == true) {
                $this->success();
            } else {
                $this->error($result['msg']);
            }
        } else {
            $this->error($result['msg']);
        }
    }

    public function checkOnlineWithdraw() {
        $goodId = I('post.goodsId');
        $type = M('tgoods_info')->where(
            array(
                'goods_id' => $goodId))->getfield('online_verify_flag');
        $this->ajaxReturn($type);
    }
    
    // 检查活动奖品中是否有红包
    public function checkJpRedPack($batchId) {
        $jpArr = M()->table('tcj_batch a')
            ->field('b.batch_type')
            ->join('tbatch_info b on a.b_id=b.id')
            ->where(
            "a.node_id='" . $this->node_id . "' and a.batch_id='" . $batchId .
                 "'")
            ->select();
        $flag = false;
        if (is_array($jpArr)) {
            foreach ($jpArr as $k => $v) {
                if (verify_scalar_val($v['batch_type'], '6')) {
                    $flag = true;
                    break;
                }
            }
        }

        return $flag;
    }

    /**
     * 移除下发形式中微信下发的选项（手机参与的不能添加微信卡券）
     *
     * @param string $availableSendType
     * @return string
     */
    private function removeWeiXinSendType($availableSendType) {
        $availableSendTypeArr = explode(',', $availableSendType);
        $k = array_search('1', $availableSendTypeArr); // 1表示微信形式下发
        if (false !== $k) {
            unset($availableSendTypeArr[$k]);
            $availableSendType = implode(',', $availableSendTypeArr);
        }
        return $availableSendType;
    }
    /**
     * [createGoodsInfo 创建默认提领券]
     * @return [type] [description]
     */
    private function createGoodsInfo(){
        $name = '翼码旺财用户体验奖品专用卡券';
        // 卡券数量
        $goodsNum = 5;
        // 打印小票
        $printText = '仅作翼码旺财用户体验之用';
        // 卡券图片
        $goodImage = 'Image/logofour.png';
        // 找出所有的机构id
        $nodeList = M()->query($this->nodeIn(null, true));
        $nodeArr = array();
        foreach ($nodeList as $v) {
            $nodeArr[] = $v['node_id'];
        }
        $dataList = implode(',', $nodeArr);
        // 支撑创建终端组
        M('tnode_info')->where("node_id='{$this->nodeId}'")->setInc(
            'posgroup_seq');
        M()->startTrans();
        $req_array = array(
            'CreatePosGroupReq' => array(
                'NodeId'    => $this->nodeId, 
                'GroupType' => 0, 
                'GroupName' => str_pad($this->nodeInfo['client_id'], 6, '0', 
                STR_PAD_LEFT) . $this->nodeInfo['posgroup_seq'], 
                'GroupDesc' => '', 
                'DataList'  => $dataList
                )
            );
        $RemoteRequest = D('RemoteRequest', 'Service');
        $resp_array = $RemoteRequest->requestIssForImageco($req_array);
        $ret_msg = $resp_array['CreatePosGroupRes']['Status'];
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
             $ret_msg['StatusCode'] != '0001')) {
            log_write("创建终端组失败，原因：{$ret_msg['StatusText']}");
            $this->error('创建门店失败:' . $ret_msg['StatusText']);
        }
        $groupId = $resp_array['CreatePosGroupRes']['GroupID'];
        // 插入终端组信息
        $num = M('tpos_group')->where(
            "group_id='{$groupId}' AND node_id='{$this->nodeId}'")->count();
        if ($num == '0') { // 不存在终端组去创建
            $groupData = array( // tpos_group
                'node_id'    => $this->nodeId, 
                'group_id'   => $groupId, 
                'group_name' => $req_array['CreatePosGroupReq']['GroupName'], 
                'group_type' => '0', 
                'status'     => '0');
            $result = M('tpos_group')->add($groupData);
            if (! $result) {
                M()->rollback();
                $this->error('终端数据创建失败');
            }
            foreach ($nodeList as $v) {
                $data_1 = array(
                    'group_id' => $groupId, 
                    'node_id'  => $v['node_id']);
                $result = M('tgroup_pos_relation')->add($data_1);
                if (! $result) {
                    M()->rollback();
                    $this->error('终端数据创建失败');
                }
            }
        }
        // 创建合约
        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 流水号
        $req_array = array(
            'CreateTreatyReq' => array(
                'SystemID'        => C('ISS_SYSTEM_ID'), 
                'RequestSeq'      => $TransactionID, 
                'ShopNodeId'      => $this->nodeId, 
                'BussNodeId'      => $this->nodeId, 
                'TreatyName'      => $name, 
                'TreatyShortName' => $name, 
                'StartTime'       => date('YmdHis'), 
                'EndTime'         => '20301231235959', 
                'GroupId'         => $groupId, 
                'GoodsName'       => $name, 
                'GoodsShortName'  => $name, 
                'SalePrice'       => '0'
                )
            );
        $RemoteRequest = D('RemoteRequest', 'Service');
        $resp_array = $RemoteRequest->requestIssForImageco($req_array);
        $ret_msg = $resp_array['CreateTreatyRes']['Status'];
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
             $ret_msg['StatusCode'] != '0001')) {
            M()->rollback();
            log_write("创建合约失败，原因：{$ret_msg['StatusText']}");
            $this->error('创建合约失败:' . $ret_msg['StatusText']);
        }
        $treatyId = $resp_array['CreateTreatyRes']['TreatyId']; 
        // 创建活动
        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); 
        $req_array = array(
            'ActivityCreateReq' => array(
                'SystemID'      => C('ISS_SYSTEM_ID'), 
                'ISSPID'        => $this->nodeId, 
                'RelationID'    => $this->nodeId, 
                'TransactionID' => $TransactionID, 
                'SmilID'        => '', 
                'ActivityInfo'  => array(
                    'CustomNo'          => '', 
                    'ActivityName'      => $name, 
                    'ActivityShortName' => $name, 
                    'BeginTime'         => date('YmdHis'), 
                    'EndTime'           => '20301231235959', 
                    'UseRangeID'        => $groupId, 
                    'SpecialTag'        => ''
                ), 
                'VerifyMode' => array(
                        'UseTimesLimit' => 1, 
                        'UseAmtLimit'   => 0
                         ), 
                'GoodsInfo' => array(
                    'pGoodsId' => $treatyId
                    ), 
                'DefaultParam' => array(
                    'PasswordTryTimes' => 3, 
                    'PasswordType'     => '', 
                    'PrintText'        => $printText
                    )
                )
            );
        $RemoteRequest = D('RemoteRequest', 'Service');
        $resp_array = $RemoteRequest->requestIssForImageco($req_array);
        $ret_msg = $resp_array['ActivityCreateRes']['Status'];
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
             $ret_msg['StatusCode'] != '0001')) {
            $this->error("活动创建失败:{$ret_msg['StatusText']}");
        }
        $batchNo = $resp_array['ActivityCreateRes']['Info']['ActivityID'];
        
        // tgoods_info数据添加
        $goodsId = get_goods_id();
        $data['goods_id']           = $goodsId;
        $data['batch_no']           = $batchNo;
        $data['goods_name']         = $name;
        $data['goods_image']        = $goodImage;
        $data['node_id']            = $this->nodeId;
        $data['user_id']            = $this->userId;
        $data['goods_type']         = 2;
        $data['storage_type']       = 1;
        $data['storage_num']        = $goodsNum;
        $data['remain_num']         = $goodsNum;
        $data['print_text']         = $printText;
        $data['add_time']           = date('YmdHis');
        $data['p_goods_id']         = $treatyId;
        $data['pos_group']          = $groupId;
        $data['online_verify_flag'] = 0;
        $data['pos_group_type']     = 1;
        $id = M('tgoods_info')->data($data)->add();
        if ($id) {
            M()->commit();
        } else {
            M()->rollback();
            $this->error('系统出错,卡券创建失败');
        }
        return array_merge(['id'=>$id],$data);
    }
    
    /**
     * 奖品的结束时间如果小于活动的结束时间,保存活动时间的时候需要提示
     */
    public function ajaxConfirmActTime() {
        $mid = I('m_id');
        $minfo = D('MarketInfo')->getMarketingInfo(array('id' => $mid));
        $model = D('OrderActivityAdmin');
        $re = $model->confirmActEndTimeWithPrize($minfo);
        $this->success($re);
    }
    
    public function saveRate(){
        $rate = I('post.rate', 0, 'int');
        if($rate != 0){
            cookie('rate', $rate, 300);
        }
    }
}
