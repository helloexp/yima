<?php

class StoreAction extends MyBaseAction {

    public $upload_path;

    const BATCH_TYPE_STORE = 29;

    public $node_short_name = '';

    public $pageInfo = '';

    public $wx_flag = 0;

    public $isSku = 2;
    // 0 为非sku商品， 2为sku商品，目前只用于addGoodsToCart函数
    public $wfxService;
    //初始化模块
    public $goodsModel = '';
    public $storeOrderModel = '';

    public $cityExpress;// 区域运费
    public $cityFreight;//非标和包
    public $isCmPay = 0;// 统一运费

    /**
     * @var O2OLoginService
     */
    public $O2OLoginService;

    public function _initialize() {
        parent::_initialize();
        $node_info = M('tnode_info')->where(
            array(
                'node_id' => $this->node_id))->find();
        $this->node_short_name = $node_info['node_short_name'];
        $this->upload_path = './Home/Upload/MicroWebImg/' . $this->node_id . '/';
        $this->expiresTime = 120; // 手机发送间隔
        $this->CodeexpiresTime = 600; // 手机验证码过期时间
        $this->assign('expiresTime', $this->expiresTime);
        $this->assign('node_short_name', $this->node_short_name);
        $this->assign('id', $this->id);
        $this->assign('node_id', $this->node_id);
        if (! session("?id")) {
            // 小店的id session过期或者不是从小店首页进来的页面
            $m_id = M('tmarketing_info')->where(
                array(
                    'node_id' => $this->node_id, 
                    'batch_type' => 29))->getField('id');
            if (! $m_id)
                $this->error('获取默认小店活动失败');
                // 渠道默认获取为当前渠道
            $c_id = $this->channel_id;
            if (! $c_id)
                $c_id = M('tchannel')->where(
                    array(
                        'type' => '4', 
                        'sns_type' => '46', 
                        'node_id' => $this->node_id))->getField('id');
            if (! $c_id)
                $this->error('获取默认小店渠道失败');
            $mbc_id = get_batch_channel($m_id, $c_id, '29', $this->node_id);
            if (! $mbc_id)
                $this->error('获取默认小店地址失败');
            session("id", $mbc_id);
        }
        
        // 分销员存session 3小时
        $saler_id = I('saler_id');
        $saler_sess = session('saler_sess' . $this->node_id);
        if ($saler_id) {
            session("saler_sess" . $this->node_id, 
                array(
                    'saler_id' => $saler_id));
        }
        log_write("pengqi".print_r(session('saler_sess' . $this->node_id),true));
        // 不是同一个appid则清除session
        $wxUserInfo = session('wxUserInfo');
        if ($wxUserInfo) {
            if ($this->node_id == C('fb_boya.node_id'))
                $appid = C('fb_boya.appid');
            else
                $appid = C('WEIXIN.appid');
            if ($wxUserInfo['appid'] != $appid) {
                session('wxUserInfo', null);
//                session('groupPhone', null);//todo 暂时屏蔽  怀疑因为这个导致 大流量下 数据被清
                $groupPhone = session('groupPhone');
                log_write('groupPhone:'.$groupPhone . 'appid:'.$appid.' session:'.var_export($_SESSION,1), 'WARN', 'sessionDump');
            }
        }

        //新增 如果有cookie(标识已经openid和phone绑定过) 且是从微信浏览器打开 强制设置session 不需要再次登录 end
        if (!session("?groupPhone") && cookie('__wc_binded_openid_and_phone_')) {
            if (empty($this->O2OLoginService)) {
                $this->O2OLoginService = D('O2OLogin', 'Service');
                $this->O2OLoginService->autoLoginPhone($this->node_id, cookie('__wc_binded_openid_and_phone_'), $this->channel_id, $this->batch_id);
            }
        }
        //初始化变量
        $phoneNo = '';
        if (session("?groupPhone")) {
            $phoneNo = session("groupPhone");
            if (! session('?store_mem_id' . $this->node_id)) {
                $userId = addMemberByO2o($phoneNo, $this->node_id, $this->channel_id, $this->batch_id);
                session('store_mem_id' . $this->node_id, 
                    array(
                        'user_id' => $userId));
            }
            // 补充全局cookie
            $global_phone = cookie('_global_user_mobile');
            if (! $global_phone) {
                cookie('_global_user_mobile', $phoneNo, 3600 * 24 * 365);
            }
            $this->assign('phoneNo', $phoneNo);
            $this->assign('nodeId', $this->node_id);
        }
        
        // 旺分销
        $this->wfxService = D('Wfx', 'Service');

        // 判断是否是微信中打开 0 否 1是
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') === false) {
            $wx_flag = 0;
        } else {
            $wx_flag = 1;
        }
        $this->wx_flag = $wx_flag;
        $this->assign('wx_flag', $wx_flag);
        //和包支付
        if(C('CMPAY.nodeId') == $this->node_id){
            $cmPayId = $this->node_id;
            $this->assign('cmPayId', $this->node_id);
        }else{
            $cmPayId = 0;
            $this->assign('cmPayId', 0);
        }
        //实例化
        $this->goodsModel = D('GoodsInfo');
        $this->goodsModel->nodeId = $this->node_id;
        if(!empty($phoneNo)){
            $this->goodsModel->phoneNum = $phoneNo;
            // 购物车SESSION名
            $this->goodsModel->session_cart_name = 'session_cart_products_' . $this->node_id . '_' . $phoneNo;
            // 商品收货地址SESISON名
            $this->goodsModel->session_ship_name = 'session_ship_products_' . $this->node_id . '_' . $phoneNo;
        }

        $this->storeOrderModel = D('StoreOrder');
        $this->storeOrderModel->nodeId = $this->node_id;
        
        // 运费配置信息
        $cityExpressModel = D('CityExpressShipping');
        // 区域运费
        $this->cityExpress = $cityExpressModel->getCityExpressConfig(
            $this->node_id);
        // 统一运费
        $this->cityFreight = $cityExpressModel->getFreightConfig($this->node_id);
        // 爱蒂宝*****请保存在初始化的最后一行
        $this->adbInit();
    }

    public function index() {
        $wechatService = D('WeiXin', 'Service');
        $wechatConfig = array();
        $shareConfig['config'] = $wechatService->getWxShareConfig();
        $this->assign('shareData', $shareConfig);
        $id = $this->id; // channel_id
        session("id", $id);
        $batch_id = $this->batch_id; // tmaketing_info id
        $nowPage = empty($_GET['p']) ? 1 : $_GET['p'];
        if ($nowPage == 1) {
            // 更新访问量
            $channel_type = M('tchannel')->where(
                array(
                    'id' => $this->channel_id))->getField('sns_type');
            $number_no = M('tbd_wail')->where(
                array(
                    'node_id' => $this->node_id))->getfield('status');
            if ($channel_type != '52' || $number_no == '2') {
                import('@.Vendor.DataStat');
                $opt = new DataStat($this->id, $this->full_id);
                $opt->UpdateRecord();
            }
        }
        //记录用户访问方式
        session("login_type" . $this->node_id, 'store_in');
        $batch_model = M('tmarketing_info');
        $map = array(
            'node_id' => $this->node_id, 
            'batch_type' => self::BATCH_TYPE_STORE, 
            'id' => $batch_id);
        $batch_info = $batch_model->where($map)->find();
        if (! $batch_info) {
            $this->error('获取默认营销活动失败');
        }
        
        session("title", $batch_info['name']);
        
        // 计算购物车商品数量
        $goodsCount = $this->getCat('', true);
        // 查询店铺logo
        $logoWhere = array(
            'node_id' => $this->node_id, 
            'ban_type' => 1, 
            'm_id' => $this->batch_id);
        $logoInfo = M('tecshop_banner')->where($logoWhere)->find();
        
        // 查询店铺banner
        session("logo", $logoInfo['img_url']);
        
        $bannerWhere = array(
            'node_id' => $this->node_id, 
            'ban_type' => 0, 
            'm_id' => $this->batch_id);
        $bannerInfo = M('tecshop_banner')->where($bannerWhere)
            ->order("order_by desc ")
            ->select();
        // 查询登录LOGO和标题
        $node_model = M('tecshop_banner');
        $map = array(
            'node_id' => $this->node_id, 
            'ban_type' => 1, 
            'm_id' => $this->batch_id);
        $loginInfo = $node_model->where($map)->find();
        
        // 获取店铺名称
        $marketInfo = M('tmarketing_info')->field('id,name')
            ->where(
            array(
                'node_id' => $this->node_id, 
                'batch_type' => self::BATCH_TYPE_STORE))
            ->order("id desc")
            ->find();
        
        session("login_logo", $loginInfo['img_url']);
        session("login_title", $marketInfo['name']);
        
        // 判断模板是否自定义，如果有定义则使用新模板，否则使用老模板
        $catWhere = array(
            'node_id' => $this->node_id, 
            'page_type' => array(
                "in", 
                C('o2oTpl.tplId')), 
            'status' => '1');
        
        $pageInfo = M('tecshop_page_sort')->where($catWhere)
            ->order("id desc")
            ->find();
        $pageContentTmp = $pageInfo['page_content'];
        $pageInfo['page_content'] = str_replace("g=Label&m=Label&a=index", "g=Label&m=Label&a=index&saler=" . I('get.saler_id'), $pageContentTmp);
        //获取商品列表最新价格
        $pageInfo['page_content'] = $this->goodsModel->getPageInfoNew($pageInfo['page_content']);
        // 存入cookie,保存3个小时
        
        if (I('get.saler_id')) {
            cookie('saler_id', I('get.saler_id'), 10800);
        }
        
        // 直达号渠道
        if ($channel_type == '52') {
            $number = M('tbd_wail')->where(
                array(
                    'node_id' => $this->node_id, 
                    '_string' => "status='1' or status='2'"))->getfield('app_id');
        } else {
            $number = '';
        }
        $this->assign('number', $number);
    
        if (isset($pageInfo['id']) && $pageInfo['id'] != "") {
            $this->assign('node_id', $this->node_id);
            $this->pageInfo = $pageInfo;
            $this->index2();
            exit();
        }
        
        // 搜索
        $keyword = I("keyword");
        // 分类
        $cat_id = I("cat_id");
        
        $catWhere = array(
            'node_id' => $this->node_id);
        
        // 查询分类
        $categoryInfo = M('tecshop_classify ')->where($catWhere)
            ->order("id desc ")
            ->select();
        
        $nodeWhere = array();
        
        $nodeWhere = " e.node_id='" . $this->node_id .
             "' AND b.status='0' AND ((b.end_time>='" . date('YmdHis') .
             "' AND b.begin_time<='" . date('YmdHis') .
             "') or (e.purchase_time_limit = '1' and b.end_time >= '" .
             date('YmdHis') . "'))";
        
        if ($keyword != "") {
            
            $nodeWhere .= " AND b.batch_name like '%" . $keyword . "%'";
        }
        
        if ($cat_id != "") {
            
            $nodeWhere .= " AND e.ecshop_classify ='" . $cat_id . "'";
        }
        
        $mapcount = M()->table('tecshop_goods_ex e ')
            ->join("tbatch_info b ON b.id=e.b_id")
            ->where($nodeWhere)
            ->count();
        
        import('ORG.Util.Page'); // 导入分页类
        $Page = new Page($mapcount, 6);
        $totalpage = ceil($mapcount / 6);
        
        // 查询商品
        $goodsList = M()->table('tecshop_goods_ex e ')
            ->join("tbatch_info b ON b.id=e.b_id")
            ->join("tgoods_info t on t.goods_id=b.goods_id")
            ->where($nodeWhere)
            ->order("e.is_commend asc,b.id desc ")
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        // echo M('tecshop_goods_ex e ')->getLastSql();
        
        if ($nowPage > $totalpage) {
            $goodsList = array();
        }
        
        // 组装下一页url
        $nexUrl = U('Label/Store/index', 
            array(
                'id' => $this->id, 
                'cat_id' => $cat_id, 
                'keyword' => $keyword), '', '', true) . '&p=' . ($nowPage + 1);
        
        // 组装分享信息
        $currhost = C('CURRENT_DOMAIN');
        $currentUrl = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        
        $share_pic = C('TMPL_PARSE_STRING.__UPLOAD__') . '/' .
             $loginInfo['img_url'];
        
        $currentUrl = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        
        // 创建sku信息
        $skuObj = D('Sku', 'Service');
        $skuObj->nodeId=$this->node_id;
        foreach ($goodsList as &$val) {
            // sku商品
            if ("1" === $val['is_sku']) {
                $skuObj->nodeId = $this->node_id;
                $val = $skuObj->makeGoodsListInfo($val, $val['m_id'], '');
            }
        }

        $this->assign('nextUrl', $nexUrl);
        $this->assign('id', $id);
        $this->assign('cartCount', isset($goodsCount[1]) ? $goodsCount[1] : 0);
        $this->assign('keyword', $keyword);
        $this->assign('goodsList', $goodsList);
        $this->assign('bannerInfo', $bannerInfo);
        $this->assign('logoInfo', $logoInfo);
        $this->assign('batch_info', $batch_info);
        $this->assign('categoryInfo', $categoryInfo);
        $this->assign('currentUrl', $currentUrl);
        $this->assign('share_pic', $share_pic);
        $this->display("index"); // 输出模板
    }

    public function index2() {
        $currhost = C('CURRENT_DOMAIN');

        $currentUrl = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        
        // 获取店铺名称
        $marketInfo = M('tmarketing_info')->field('id,name')
            ->where(
            array(
                'node_id' => $this->node_id, 
                'batch_type' => self::BATCH_TYPE_STORE))
            ->order("id desc")
            ->find();

        // 查询小店分享图片
        $shareInfo = M('tecshop_banner')->field('img_url')
            ->where(
            array(
                'node_id' => $this->node_id, 
                'm_id' => $marketInfo['id'], 
                'ban_type' => 4))
            ->order("id desc")
            ->find();
        // 查询小店描述
        $descInfo = M('tecshop_banner')->field('memo')
            ->where(
            array(
                'node_id' => $this->node_id, 
                'm_id' => $marketInfo['id'], 
                'ban_type' => 3))
            ->order("id desc")
            ->find();
        
        // 分享图片处理
        $len = strlen($shareInfo['img_url']) - 2;
        $tmpic = substr($shareInfo['img_url'], 2, $len);
        $share_pic = $currhost . $tmpic;
        // 特殊字符处理
        $descInfo['memo'] = my_nl2br($descInfo['memo']);
        $share_pic = get_upload_url($shareInfo['img_url']);
        $this->assign('pageInfo', $this->pageInfo);
        $this->assign('marketInfo', $marketInfo);
        $this->assign('descInfo', $descInfo);
        $this->assign('share_pic', $share_pic);
        $this->assign('currentUrl', $currentUrl);
        $this->assign('salerId', I('get.saler_id'));
        $this->display("index2"); // 输出模板
    }
    
    // 单页显示
    public function pageview() {
        $page_id = I("page_id");
        if ($page_id == "") {
            $this->error("参数错误");
        }
        
        $catWhere = array(
            'node_id' => $this->node_id, 
            'page_type' => '2', 
            'id' => $page_id);
        $pageInfo = M('tecshop_page_sort')->where($catWhere)->find();
        
        $currhost = C('CURRENT_DOMAIN');
        $len = strlen($pageInfo['share_pic']) - 2;
        
        $tmp = substr($pageInfo['share_pic'], 2, $len);
        $share_pic = $currhost . $tmp;
        $currentUrl = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        // 即时更新商品信息
        $changeContentObject = D('O2O', 'Service');
        $pageInfo['page_content'] = $changeContentObject->changePageContent(
            $pageInfo['page_content'], $this->node_id);
           
        $this->assign('pageInfo', $pageInfo);
        $this->assign('currentUrl', $currentUrl);
        $this->assign('share_pic', $share_pic);
        $this->display("pageview");
    }
    
    // 分类页展示
    public function catview() {
        /*
         * $catid=I("catid"); if($catid==""){ $this->error("参数错误"); }
         * $url="index.php?g=Label&m=Store&a=index&id=".$this->id."&cat_id=".$catid;
         * header("location:".$url);
         */
        
        // 搜索
        $keyword = I("keyword");
        // 分类
        $cat_id = I("catid");
        
        $catWhere = array(
            'node_id' => $this->node_id);
        
        // 查询分类
        $categoryInfo = M('tecshop_classify ')->where($catWhere)
            ->order("id desc ")
            ->select();
        
        $nodeWhere = array();
        
        $nodeWhere = " e.node_id='" . $this->node_id .
             "' AND b.status='0' AND b.end_time>='" . date('YmdHis') . "'";
        
        if ($keyword != "") {
            
            $nodeWhere .= " AND b.batch_name like '%" . $keyword . "%'";
        }
        
        if ($cat_id != "") {
            
            $nodeWhere .= " AND FIND_IN_SET ({$cat_id}, e.ecshop_classify)";
        }
        // 爱蒂宝
        $nodeWhere = $this->adbCatView($nodeWhere);
        $mapcount = M('tecshop_goods_ex e ')->join(
            "tbatch_info b ON b.id=e.b_id")
            ->where($nodeWhere)
            ->count();
        
        import('ORG.Util.Page'); // 导入分页类
        $Page = new Page($mapcount, 6);
        $totalpage = ceil($mapcount / 6);
        $nowPage = empty($_GET['p']) ? 1 : $_GET['p'];
        
        // 查询商品
        $goodsList = M('tecshop_goods_ex e ')->join(
            "tbatch_info b ON b.id=e.b_id")
            ->join("tgoods_info t on t.goods_id=b.goods_id")
            ->where($nodeWhere)
            ->order("e.is_commend asc,b.id desc ")
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();

        // 创建sku信息
        $skuObj = D('Sku', 'Service');
        $skuObj->nodeId = $this->node_id;
        foreach ($goodsList as $key => &$val) {
            // sku商品
            if ("1" === $val['is_sku']) {
                $skuObj->nodeId = $this->node_id;
                $val = $skuObj->makeGoodsListInfo($val, $val['m_id'], '');
                if(false == $val){
                    unset($goodsList[$key]);
                }
            }
        }
        // echo M('tecshop_goods_ex e ')->getLastSql();
        // echo $nowPage."==".$totalpage;
        // exit;
        if ($nowPage > $totalpage) {
            $goodsList = array();
        }
        
        // 组装下一页url
        $nexUrl = U('Label/Store/catview', 
            array(
                'catid' => $cat_id, 
                'keyword' => $keyword), '', '', true) . '&p=' . ($nowPage + 1);
        // 查询登录LOGO和标题
        if (session("login_logo") == "") {
            $node_model = M('tecshop_banner');
            $map = array(
                'node_id' => $this->node_id, 
                'ban_type' => 1, 
                'm_id' => $this->batch_id);
            $loginInfo = $node_model->where($map)->find();
            session("login_logo", $loginInfo['img_url']);
        }
        $this->assign('nextUrl', $nexUrl);
        $this->assign('keyword', $keyword);
        $this->assign('goodsList', $goodsList);
        $this->assign('categoryInfo', $categoryInfo);
        $this->display('catview'); // 输出模板
    }

    public function detail() {
        $wechatService = D('WeiXin', 'Service');
        $shareConfig = $wechatService->getWxShareConfig();
        $this->assign('shareData', $shareConfig);

        $id = I("id"); // batch_channl id;

        if ($id) {
            import('@.Vendor.RankHelper');
            $RankHelper = RankHelper::getInstance();
            $RankHelper->addOneScore($id);
        }

        $this->assign('batchChannel', $id);
        $marketInfo = $this->marketInfo;
        $mem_flag = 0; // 粉丝标注 0 否 1是
        if ($marketInfo['member_join_flag'] == '1' && $this->wx_flag == 1) {
            $wxnodeinfo = M('tweixin_info')->where(array('node_id' => $this->node_id))->find();
            if ($wxnodeinfo['app_id'] && $wxnodeinfo['app_secret']) {
                $wxBatchOpen = session('wxBatchOpen');
                if (! $wxBatchOpen['openid']) {
                    $surl = U('Label/Store/detail', 
                        array(
                            'id' => $id), '', '', true);
                    redirect(
                        U('Label/GetWeiXinInfo/goAuthorize', 
                            array(
                                'node_id' => $this->node_id, 
                                'surl' => urlencode($surl))));
                }
                $count = M('twx_user')->where(
                    array(
                        'subscribe' => array(
                            'neq', 
                            '0'), 
                        'openid' => $wxBatchOpen['openid'], 
                        'node_id' => $this->node_id))->count();
                if ($count > 0)
                    $mem_flag = 1;
            }
        }
        // 更新访问量
        import('@.Vendor.DataStat');
        $opt = new DataStat($id, $this->full_id);
        $opt->recordSeq();

        if ($id == "") {
            $id = session("goods_id");
        } else {
            session("goods_id", $id);
        }
        
        // 取任意渠道
        if (session("id") == "") {
            $this->goodsModel->getGoodsChannel();
        }
        
        if ($this->batch_id == "" || empty($id)) {
            $this->error([
                    'errorImg' => '__PUBLIC__/Label/Image/waperro5.png', 
                    'errorTxt' => '错误访问！', 
                    'errorSoftTxt' => '你的访问地址出错啦~'
                ]);
        }
        //爱蒂宝
        $this->adbGoodsInfo();
        
        // 根据batch_id（m_id）查询 batch_info id
        $goodsInfo = $this->goodsModel->getGoodsInfoByBatchId($this->batch_id);
        
        $bid = $goodsInfo['id'];
        $mid = $this->batch_id;
        
        // 计算购物车商品数量
        $goodsCount = $this->getCat('', true);
        $where = [
            "e.b_id" => $bid, 
            "e.m_id" => $mid,
            'b.status' => '0'
            ];
        $goodsInfo = M('tecshop_goods_ex e ')->join(
            "tbatch_info b ON b.id=e.b_id")
            ->join('tgoods_info gi ON gi.goods_id = b.goods_id')
            ->join('tmarketing_info m ON b.m_id = m.id')
            ->where($where)
            ->order("e.is_commend,b.id desc ")
            ->field(
            'e.*, b.*, gi.config_data as config_info, gi.market_price, gi.is_sku, gi.is_order, m.batch_type as store_type')
            ->find();
        if(!$goodsInfo){
            $this->error('商品已下架');
        }

        //判断是否SKU商品
        if('1' == $goodsInfo['is_sku']){
            // 是否sku商品
            $skuObj = D('Sku', 'Service');
            $skuObj->nodeId = $this->node_id;
            $goodsInfo = $skuObj->getSkuListInfo($goodsInfo);
        }
        //初始化
        $relationGoods = '';
        if(!empty($goodsInfo['relation_goods'])){
            // 查询关联商品      
            $relationWhere = " e.node_id='" . $this->node_id . 
                    "' AND e.m_id IN (".$goodsInfo['relation_goods'].
                    ") AND b.status='0' AND t.is_sku = '0' " .     //去除关联商品为SKU商品
                    " AND ((b.end_time>='" . date('YmdHis') . 
                    "' AND b.begin_time<='" . date('YmdHis') . 
                    "') or (e.purchase_time_limit = '1' and b.end_time >= '" . date('YmdHis') . "'))";
            $relationGoods = M('tecshop_goods_ex e ')->join(
                "tbatch_info b ON b.id=e.b_id")
                ->join("tgoods_info t on t.goods_id=b.goods_id")
                ->where($relationWhere)
                ->order("b.id desc ")
                ->select();
        }
        // 取得总规则信息
        $ruleType = D('SalePro', 'Service')->getNodeRule($this->node_id);
        if ($marketInfo['bonus_flag'] == '1' && '0' != $ruleType) {
            $bonusRule = M('tbonus_rules')->where(
                    array(
                    'node_id' => $this->node_id, 
                    'status' => '1'))
                ->order('rev_amount')
                ->select();
        }
        $integralRule = '';
        // 取得积分规则信息
        $intergralType = D('SalePro', 'Service')->getNodeRule($this->node_id, 
            'tintegral_rule_main');
        if ($marketInfo['integral_flag'] == '1' && '0' != $intergralType) {
            $integralRule = M('tintegral_rules')->where(
                array(
                    'node_id' => $this->node_id, 
                    'status' => '1'))
                ->order('rev_amount')
                ->select();
        }
        // 获取满减规则
        $fullReduceRule = D('FullReduce')->getFullReduceRuleList($this->node_id, 
            $goodsInfo['m_id'], $goodsInfo['store_type']);
        if (false != $fullReduceRule) {
            if (isset($fullReduceRule[0])) {
                $this->assign('fullReduceRule', $fullReduceRule[0]); // 满减兑换规则
            }
        }
        
        $this->assign('node_id', $this->node_id);
        
        // 取得门店信息
        $goodsM = D('Goods');
        $storeList = $goodsM->getGoodsShop($goodsInfo['goods_id'], true, nodeIn($this->node_id));
        $goodsM->recentLookGoods($this->id);

        //门店类型 1普通门店 2系统自建默认门店 3线上门店 4验证助手默认门店
        //排除3与4
        foreach($storeList as $k=>$v){
            if($v['type'] == 3 || $v['type'] == 4 || $v['type'] == 2){
                unset($storeList[$k]);
            }
        }
        shuffle($storeList);//删除键名重新排序，
        
        $area = $this->get_user_city();//获取用户所在省市
        

        $userData = array();//存放该城市的所有门店数据
        foreach($storeList as $k=>$v){
            if($v['city'] == $area['city']){
                $userData[] = $v;
            }
        }
        
        $this->setStoreList($storeList);;//将门店列表保存进session
        
        $provinces = array();//下拉框中的省
        foreach($storeList as $v=>$k){
            $provinces[]=$k['province'];
        }

        $provinces = array_flip(array_flip($provinces)); //去空值
        $provinces=array_filter ($provinces);//去重复值
        
        //商品展示配置
        $configData = json_decode($goodsInfo['config_data'], true);
        //如未设置销售比例，设置销售比例初始值
        if(empty($configData['saleProportion'])){
            $configData['saleProportion'] = 1;
        }
        //美惠非标
        $goodsInfo['priceAmt'] = $goodsInfo['batch_amt'];
        $this->mhGoodsInfo($goodsInfo);
        
        $this->assign('provinces',$provinces);//所有的省
        $this->assign('userArea',$area);//用户所在省市
        $this->assign('userData',$userData);//所在城市的所有数据
        //微信关注页地址
        $wxUrlInfo = D('TweixinInfo')->getGuidUrl($this->node_id);
        $this->assign('wxUrlInfo', $wxUrlInfo);
        // 获取送礼信息
        $sendGift = D('MarketInfo')->getSendGiftTage($marketInfo);
        $this->assign('sendGift', $sendGift);
        $this->assign('relationGoods', $relationGoods);
        $this->assign('bonusRule', $bonusRule);
        $this->assign('ruleType', $ruleType); // 红包总规则
        $this->assign('intergralType', $intergralType); // 积分规则
        $this->assign('integralRule', $integralRule); // 积分兑换规则
        $this->assign('goodsInfo', $goodsInfo);
        $this->assign('cartCount', isset($goodsCount[1]) ? $goodsCount[1] : 0);
        $this->assign('id', $id);
        $this->assign('mid', $mid);
        $this->assign('storeList', $storeList);
        $this->assign('mem_flag', $mem_flag);
        $this->assign('marketInfo', $marketInfo);
        $this->assign('saleProportion', $configData['saleProportion']);  //销售比例
        $this->assign('sellNum', ($goodsInfo['storage_num'] - $goodsInfo['remain_num']) * $configData['saleProportion']);
        $this->assign('remainNum', $goodsInfo['remain_num'] * $configData['saleProportion']);
        $this->assign('wxName', isset($wxnodeinfo['weixin_code']) ? $wxnodeinfo['weixin_code'] : '');
        $this->assign('configData',$configData);//商品展示配置
        $this->display(); // 输出模板
    }


    public function  setStoreList($storeList)
    {
        import('@.Vendor.RedisHelper');
        $this->redis = RedisHelper::getInstance();
        $this->redis->set('storeList:'.$this->node_id, $storeList);
    }

    public function getStoreList()
    {
        import('@.Vendor.RedisHelper');
        $this->redis = RedisHelper::getInstance();
        return $this->redis->get('storeList:'.$this->node_id);
    }


    public function htmlSelectShow()
    {
        /**
         * Task :
         * Author:zhaobl
         * Date: 2015/01/14
         */
        $storeList = $this->getStoreList();
    
        if($this->isPOST()){
            $pro = I('pro');
            $city = I('city');

            if($city != '' && $city != '-请选择-'){
                //如果选择了市
                $dataReturn = array();//筛选后要返回的数据
                foreach($storeList as $k=>$v){
                    if($v['city'] == $city){
                        $dataReturn[] = $storeList[$k];

                    }
                }
                $this->ajaxReturn($dataReturn,'JSON');
            }

            $datas = array();
            //$datas['citys'] = array();//下拉框中的市
            //$datas = array();//该省下的所有门店数据
            foreach($storeList as $k=>$v){
                if($v['province'] == $pro){
                    $datas['citys'][]=$v['city'];
                    $datas['shops'][] = $storeList[$k];
                }
            }
            $datas['citys'] = array_flip(array_flip($datas['citys']));//去除重复值
            $datas['citys']=array_filter ($datas['citys']);//去除空值
            shuffle($datas['citys']);//删除键名重新排序，

            $this->ajaxReturn($datas,'JSON');
        } 
    

    }
    
    public function get_user_city()
    {
        static $city_code = null;
    
        if($city_code != null)
            return $city_code;
    
            //$userIp = GetIP();
            //$userIp = get_client_ip();
            $userIp = '114.80.166.240';

            //2秒超时
            $ctx = stream_context_create(array(
                'http' => array(
                    'timeout' => 2
                )
            )
                );
    
            $result = @file_get_contents('http://int.dpool.sina.com.cn/iplookup/iplookup.php?ip='.$userIp, 0, $ctx);

            $result = iconv('GBK', 'UTF-8', $result);
            if($result === false){
                return false;
            }

                $info = explode("\t", $result);

                if($info[0] == -1 || !isset($info[5])){
                    return false;
                }

                $area['pro'] = $info[4];
                $area['city'] = $info[5].'市';

/*                    $info = D('tcity_code')->where("city_level = '2' and city like '{$city}%'")->limit(1)->select();
                    
                    if(!$info)
                        return false;
    
                        $city_code = $info[0]['city_code']; */
                        return $area;
    }

    public function add_goods_cart() {
        $goodsArr = I("goods");
        $goodcount = I("goodcount", 1, 'intval');
        $delivery = I("delivery", 0, 'intval');
        $skuInfo = I("sku_info");
        if ('' != $skuInfo) {
            // 是否sku商品
            $skuObj = D('Sku', 'Service');
            // 将传输进入的，号替换为#号
            $skuInfo = $skuObj->replaceArray($skuInfo, '&', ',', '#');
            $skuList = implode(',', $skuInfo);
        }
        
        if (empty($goodsArr)) {
            $this->error("请选择商品");
        }
        if (! empty($goodsArr)) {
            foreach ($goodsArr as  $val) {
                // 检查库存，数量限制
                $result = $this->goodsModel->checkStoreage($val, 1, $this->goodsModel->phoneNum, $goodcount, $skuList);
                if (false === $result) {
                    $this->error($this->goodsModel->getError());
                    exit();
                } else {
                    if ('1' === $this->goodsModel->isSku) {
                        $ret = $this->addGoodsToCart($val, $goodcount, $this->goodsModel->isSku, $skuList);
                    } else {
                        $ret = $this->addGoodsToCart($val, $goodcount);
                    }
                }
                // 更新送货session
                $ships = $this->goodsModel->_getShipType();
                if('1' === $this->goodsModel->isSku){
                    $skuId = str_replace(',', '#', $skuList);
                    $keys = $skuId . '_' . $val;
                }else{
                    $keys = $val;
                }

                $ships[$keys] = $delivery;
                session($this->goodsModel->session_ship_name, serialize($ships));
            }
            
            $returnArr = array(
                "status" => 1, 
                "info" => '加入购物车成功', 
                "goodsnum" => $ret[2]);
            echo json_encode($returnArr);
            exit();
        }
    }

    /**
     * 添加购物车
     */
    public function addGoodsToCart($id, $countnum = 0, $isSku = 0, $skuId = null) {
        $count = isset($countnum) ? intval($countnum) : 1;
        $carts = $this->goodsModel->_getCart($this->goodsModel->phoneNum);
        if(false === $carts){
            // 判断是否登录
            $this->error('您还没有登录');
        } 
        if ('1' === $isSku) {
            $skuId = str_replace(',', '#', $skuId);
            $id = $skuId . '_' . $id;
        }

        if (isset($carts[$id])) {
            $carts[$id] += $count;
        } else {
            $carts[$id] = $count;
        }
        session($this->goodsModel->session_cart_name, serialize($carts));
        
        $calCartInfo = $this->calCartInfo();
        return $calCartInfo;
    }
    
    /**
     * 更改购物车信息
     */
    public function editGoodsToCart() {
        $goodcount = I("goodcount", 1, 'intval');
        $delivery = I("delivery", 0, 'intval');
        $id = I('id', 0, 'intval');
        $skuInfo = I('sku_info');         //变更后规格信息
        $oldSkuInfo = I('old_sku_info');   //原商品规格信息
        $carts = $this->goodsModel->_getCart($this->goodsModel->phoneNum);
        if(false === $carts){
            // 判断是否登录
            $this->error('您还没有登录');
        } 
        $cartsKey = $id;
        //规格商品处理
        if(!empty($skuInfo)){
            $skuInfo = str_replace(',', '#', $skuInfo);
            $cartsKey = $skuInfo . '_' . $id;
            //获取旧规格商品KEY
            $oldSkuInfo = str_replace(',', '#', $oldSkuInfo);
            //判断规格商品是否有变更
            if($oldSkuInfo != $skuInfo){
                //获取更换规格信息
                $changeCartsKey = $oldSkuInfo . '_' . $id;
                //判断更换的商品规格是否存在
                if(isset($carts[$cartsKey])){
                    //存在在原有数量上增加
                    
                    $goodcount = $carts[$cartsKey] + $goodcount;
                }
                //删除旧规格商品信息
                unset($carts[$changeCartsKey]);   
            }
            $carts[$cartsKey] = $goodcount;
        }else{
            //非规格商品
            if (isset($carts[$id])) {
                $carts[$id] += $goodcount;
            } else {
                $carts[$id] = $goodcount;
            }
        }
        //商品库存判断
        $ret = $this->goodsModel->checkStoreage($id, 2, $this->goodsModel->phoneNum, $goodcount, $skuInfo);
        if(false === $ret){
            $this->error($this->goodsModel->getError());
        }
        
        session($this->goodsModel->session_cart_name, serialize($carts));
        
        $this->success();
    }
    
    /**
     * 记录产品送货地址方式
     */
    public function addGoodsShip() {
        $gid = I("gid");
        $skuInfo = I("skuInfo");  //规格商品
        $type = I("type");
        //判断是否SKU商品
        if(!empty($skuInfo)){
            $skuInfo = str_replace(',', '#', $skuInfo);
            $gid = $skuInfo . '_' . $gid;
        }
        
        $ships = $this->goodsModel->_getShipType();
        $ships[$gid] = $type;
        
        session($this->goodsModel->session_ship_name, serialize($ships));
        
        $returnArr = array(
            "status" => "1", 
            "msg" => '成功');
        echo json_encode($returnArr);
        exit();
    }
   
    // 购物车数据计算
    private function calCartInfo($carts = '') {
        $totalCount = $totalFee = 0;
        $data = $this->getCat();
        if(empty($carts)){
            $carts = empty($data[0]) ? '' : $data[0];
        }
        foreach ($carts as $pid => $row) {
            $totalCount += $row['total'];
            $totalFee += $row['totalPrice'];
        }
        
        return array(
            $totalCount, 
            $totalFee, 
            $data[1]);
    }

    /**
     * 计算一次购物的总的价格与数量
     *
     * @param array $carts
     * @param boole $islogin    //是否需要登录
     */
    public function getCat($carts = '', $islogin = false) {
        $carts = empty($carts) ? $this->goodsModel->_getCart() : $carts;
        if(true === $islogin){
            return false;
        }
        if(false === $carts){
            // 判断是否登录
            $surl = urlencode(U('Label/Store/cart'));
            $link = U('Label/O2OLogin/index', 
                array(
                    'node_id' => $this->node_id, 
                    'backcall' => 'bclick', 
                    'surl' => $surl), '', '', true);
            $this->error('您还没有登录', $link);
            return false;
        } 
        // 取购物车商品ID
        $pids = array_keys($carts);
        // 商品id
        if (empty($pids)) {
            return array(
                array());
        }
        // sku商品特殊处理
        $tmpArray = array();
        $noSkuPids = $pids;
        $tmpPids = $pids;
        foreach ($pids as $k => &$val) {
            if (strstr($val, '_')) {
                $tmpInfo = explode('_', $val);
                $val = $tmpInfo[1];
                $key = $tmpInfo[0];
                $tmpArray[$key] = $val;
                // 去除sku关联ID
                unset($tmpPids[$k]);
            } else {
                // 去除非sku关联ID
                unset($pids[$k]);
            }
        }
        $skuPids = array_keys($tmpArray);
        // $skuPids = NULL;
        // 判断是否sku商品
        if (empty($skuPids)) {
            $where = array(
                "e.b_id" => array(
                    "in", 
                    $noSkuPids));
            $goodsList = M()->table('tecshop_goods_ex e ')
                ->join("tbatch_info b ON b.id=e.b_id")
                ->join("tgoods_info g on g.goods_id=b.goods_id")
                ->join("tmarketing_info m on m.id=b.m_id")
                ->field(
                'e.*, g.goods_image,b.*,m.bonus_flag, m.integral_flag,m.deli_pay_flag')
                ->where($where)
                ->select();
        } else {
            $where1 = array(
                "e.b_id" => array(
                    "in", 
                    $pids), 
                "tt.sku_detail_id" => array(
                    "in", 
                    $skuPids));
            if (! empty($tmpPids)) {
                $where2 = array(
                    "e.b_id" => array(
                        "in", 
                        $tmpPids));
                $where = array(
                    '_logic' => 'OR', 
                    $where1, 
                    $where2);
            } else {
                $where = $where1;
            }
            $goodsList = M()->table('tecshop_goods_ex e ')
                ->join("tbatch_info b ON b.id=e.b_id")
                ->join("tecshop_goods_sku s ON s.b_id=e.b_id")
                ->join("tgoods_sku_info tt ON tt.id=s.skuinfo_id")
                ->join("tgoods_info g on g.goods_id=b.goods_id")
                ->join("tmarketing_info m on m.id=b.m_id")
                ->field(
                'e.*,b.*, g.goods_image,m.bonus_flag, m.integral_flag,m.deli_pay_flag, s.sale_price as sku_price, tt.sku_detail_id')
                ->where($where)
                ->select();
        }
        
        $list = array();
        $goodscount = 0;
        if (! empty($goodsList)) {
            foreach ($goodsList as $k => &$v) {
                isset($v['sku_detail_id']) ? $bId = $v['sku_detail_id'] . '_' . $v['b_id'] : $bId = $v['b_id'];
                $count = $carts[$bId];
                $list[$bId]['batch_name'] = $v['batch_name'];
                $list[$bId]['b_id'] = $v['b_id'];
                $list[$bId]['total'] = $count;
                $list[$bId]['price'] = isset($v['sku_price']) ? $v['sku_price'] : $v['batch_amt'];
                $list[$bId]['totalPrice'] = isset($v['sku_price']) ? $count * $v['sku_price'] : $count * $v['batch_amt'];
                //计算各个商品的会员优惠总价(美惠非标)
                $goods_discount = array('goods_id'=>$v['goods_id'], 'priceAmt'=>$list[$bId]['totalPrice']);
                $this->mhGoodsInfo($goods_discount);
                if($goods_discount['vip_discount'] > 0) {
                    $vamount = $count * $goods_discount['vip_discount'];
                    $list[$bId]['vipDiscoiuntPrice'] = $vamount;
                }
                //>>>>>end>>>
                $list[$bId]['bonus_flag'] = $v['bonus_flag'];
                $list[$bId]['goods_image'] = $v['goods_image'];
                $list[$bId]['integral_flag'] = $v['integral_flag'];
                $list[$bId]['deli_pay_flag'] = $v['deli_pay_flag'];
                $goodscount = $goodscount + $count;
                // 创建sku信息
                if (isset($v['sku_detail_id'])) {
                    $skuObj = D('Sku', 'Service');
                    // 取得sku规格
                    $skuInfoList = $skuObj->makeSkuOrderInfo(
                        $v['sku_detail_id'], $v['b_id']);
                    if ($skuInfoList) {
                        $list[$bId]['sku_name'] = $skuInfoList['sku_name'];
                        $list[$bId]['skuId'] = $v['sku_detail_id'];
                    }
                }
            }
        }
        return array(
            $list, 
            $goodscount);
    }

    /**
     * 购物车列表
     */
    public function cart() {
        $totalCount = $totalFee = 0;
        $data = $this->getCat();

        if (isset($data[0])) {
            foreach ($data[0] as $pid => $row) {
                $totalCount += $row['total'];
                $totalFee += $row['totalPrice'];
            }
        }
        $list = $data[0];
        $shipdata = $this->goodsModel->_getShipType();
        // 如果没设置过则默认商品第一个领取方式
        foreach ($list as $k => $v) {
            if (! isset($shipdata[$k])) {
                $deli = M('tecshop_goods_ex')->where(
                    array(
                        'b_id' => $k))->getField('delivery_flag');
                if ($deli == '1')
                    $shipdata[$k] = '1';
                else
                    $shipdata[$k] = '0';
            }
        }
        // 重新更新收获方式session
        session($this->goodsModel->session_ship_name, serialize($shipdata));
        
        $this->assign('products', $list);
        $this->assign('shipdata', $shipdata);
        $this->assign('totalFee', $totalFee);
        $this->assign('totalCount', $totalCount);
        $this->assign('metaTitle', '购物车');
        $this->display();
    }
    
    // 更新购物车数量
    public function update_cart() {
        $goodsId = I("goods_id");
        $count = I("count");
        $bId = $goodsId;
        // sku商品处理
        if (strstr($goodsId, '_')) {
            $tmpInfo = explode('_', $goodsId);
            $skuInfo = str_replace(',', '#', $tmpInfo[0]);
            $goodsId = $skuInfo . '_' . $tmpInfo[1];
            $bId = $tmpInfo[1];
        }

        $count = isset($count) ? intval($count) : 1;
        $carts = $this->goodsModel->_getCart();
        if(false === $carts){
            // 判断是否登录
            $this->error('您还没有登录');
        } 
        $result = $this->goodsModel->checkStoreage($bId, 2, $this->goodsModel->phoneNum, $count, $skuInfo); 
        if(false === $result){
            $this->error($this->goodsModel->getError());
//            $returnArr = array(
//                "status" => "0", 
//                "info" => );
//            echo json_encode($returnArr);
//            exit();
        }
        $carts[$goodsId] = $count;
        session($this->goodsModel->session_cart_name, serialize($carts));
        
        $calCartInfo = $this->calCartInfo();
        $this->success();
    }

    public function deleteCart() {
        $goods_id = I("goods_id");
        $sku_id = I("sku_id");
        $sku_id = str_replace(',', '#', $sku_id);
        
        $result = $this->goodsModel->deleteCart($goods_id, $sku_id) ;
        if(true === $result){
            $returnArr = array(
                "status" => '1', 
                "info" => "操作成功");
            echo json_encode($returnArr);
            exit();
        } else {
            $returnArr = array(
                "status" => '0', 
                "info" => "操作失败");
            echo json_encode($returnArr);
            exit();
        }
    }

    // 结算页面
    public function cart_check() {
        // 判断是否登录
        if (! session('?groupPhone')) {
            $this->error('您还没有登录');
        }
        //购买商品信息
        $goodsString = $_POST['goods'];
        $goodsArray = json_decode($goodsString, true)['goods'];
        $shipdata = $this->goodsModel->_getShipType();
        $data = $this->getCat();
        if (empty($data[0]) || empty($goodsArray)) {
            $this->error("购物车数据为空！");
        } else {
            // 循环判断是否显示收货地址
            $haveAddr = 0;
            $haveCode = 0;
            $deli_pay_flag = 1; // 默认支持货到付款
            //获取需要结算的购物车信息
            $payData = array();
            foreach($goodsArray as $val){
                $key = $val['bId'];
                //拼接规格商品KEY
                if(!empty($val['skuId'])){
                    $val['skuId'] = str_replace(',', '#', $val['skuId']);
                    $key = $val['skuId'] . '_' . $key;
                }
                $payData[$key] = $data[0][$key];
            }

            foreach ($payData as $k => $val) {
                $goodsInfo = M('tecshop_goods_ex e ')->field("e.delivery_flag")
                    ->where("e.b_id='" . $val['b_id'] . "'")
                    ->find();
                $key = $val['b_id'];
                if(!empty($val['skuId'])){
                    $val['skuId'] = str_replace(',', '#', $val['skuId']);
                    $key = $val['skuId'] . '_' . $key;
                }
                if ($shipdata[$key] == '1'  && strstr($goodsInfo['delivery_flag'], '1')) {
                    $haveAddr ++;
                } else {
                    $haveCode ++;
                }
                if ($val['deli_pay_flag'] == '0'){
                    $deli_pay_flag = 0;
                }
            }
        }
        // 如果存在发码的商品则不支持货到付款
//        if ($haveCode > 0)
//            $deli_pay_flag = 0;
        
        $curAddress = array();
        // 读取收货地址
        if ($haveAddr > 0) {
            $addressList = M('tphone_address e ')->where(
                "e.user_phone='" . session('groupPhone') . "'")
                ->order("last_use_time desc")
                ->limit(20)
                ->select();
            if (! empty($addressList)) {
                
                $selectddres = array();
                
                foreach ($addressList as $ak => &$al) {
                    // 新增城市级联
                    $cityInfo = array();
                    if ($al['path']) {
                        $cityInfo = M('tcity_code')->where(
                            array(
                                'path' => $al['path']))
                            ->field(
                            'province_code, city_code, town_code, province, city, town')
                            ->find();
                        $al['province_code'] = $cityInfo['province_code'];
                        $al['city_code'] = $cityInfo['city_code'];
                        $al['town_code'] = $cityInfo['town_code'];
                        $al['province'] = $cityInfo['province'];
                        $al['city'] = $cityInfo['city'];
                        $al['town'] = $cityInfo['town'];
                    }
                    if ($ak == 0) {
                        $curAddress = $al;
                    }
                    $selectddres[] = $al;
                }
            } else{
                $curAddress['phone_no'] = session('groupPhone');
            }    
        }
        if ($haveAddr == 0) {
            $curAddress['phone_no'] = session('groupPhone');
        }
        
        // 查询运费配置，如果运费配置了，则需要加上运费
        
        $calCartInfo = $this->calCartInfo();
        $shippingFee = 0;
        // 免运费限制
        $ruleFeeLimit = 0;
        if ($haveAddr > 0) {
            $provinceCode = isset($al['province_code']) ? $al['province_code'] : 0;
            $cityCode = $provinceCode .
                 (isset($al['city_code']) ? $al['city_code'] : 0);
            $cityExpressModel = D('CityExpressShipping');
            $shippingFee = $cityExpressModel->getShippingFee($this->node_id, 
                $calCartInfo[1], $cityCode); // 取得地区运费
            $feeLimit = $cityExpressModel->getFeeLimit($this->node_id);
            if ($calCartInfo[1] > $feeLimit && NULL != $feeLimit)
                $ruleFeeLimit = 2;
            // $calCartInfo[1]= $calCartInfo[1]+$shippingFee;
        }
        
        // 计算可用多少红包
        // $userBonus=$this->getUseBonus($calCartInfo[1]);
        // 计算红包只算购物车内可使用红包的商品
        $orderBonusAmt = 0; // 可用于计算可扣减红包的总金额
        $orderIntegralAmt = 0; // 可用于计算可扣除积分的总金额
        $orderOtherAmt = 0; // 可用于计算可扣除积分和红包的总金额
        $normalgoodslist = array(); // 不参加活动的普通商品
        $bonusgoodslist = array(); // 参加红包的活动商品
        $integralGoodsList = array(); // 参加积分的活动商品
        $otherGoodsList = array(); // 既参加积分和红包活动的商品
        $normalTotal = 0;
        $integralTotal = 0; // 参加积分活动的商品总数
        $bonusTotal = 0; // 参加红包活动的商品总数
        $otherTotal = 0; // 既参加红包又参加积分的商品总数
        $vipDiscountPriceAmt = 0; //优惠总金额(美惠非标)
        foreach ($payData as $kk => $vv) {
            if ($vv['bonus_flag'] == '1' && $vv['integral_flag'] == '0') {
                $orderBonusAmt += $vv['totalPrice'];
                $bonusgoodslist[$kk] = $vv;
                $bonusTotal += $vv['totalPrice'];
            } else if ($vv['bonus_flag'] == '0' && $vv['integral_flag'] == '1') {
                $orderIntegralAmt += $vv['totalPrice'];
                $integralGoodsList[$kk] = $vv;
                $integralTotal += $vv['totalPrice'];
            } else if ($vv['bonus_flag'] == '1' && $vv['integral_flag'] == '1') {
                $orderOtherAmt += $vv['totalPrice'];
                $otherGoodsList[$kk] = $vv;
                $otherTotal += $vv['totalPrice'];
            } else {
                $normalgoodslist[$kk] = $vv;
                $normalTotal += $vv['totalPrice'];
            }

            //计算所有商品的会员优惠总价(美惠非标)
            if($vv['vipDiscoiuntPrice']) {
                $vipDiscountPriceAmt += $vv['vipDiscoiuntPrice'];
        }
        }
        if($vipDiscountPriceAmt > 0) {
            $this->assign('vipDiscountPriceAmt', $vipDiscountPriceAmt);
        }
        
        if (($orderBonusAmt + $orderOtherAmt) > 0)
            $userBonus = $this->getUseBonus($orderBonusAmt + $orderOtherAmt);
        else
            $userBonus = 0;
            
            // 可用使用的红包明细
        $userBounsList = $this->getUserBonus();
        // 获取可支付通道
        $hasPayChannel = 0;
        $payChannelInfo = M('tnode_account')->where(
            array(
                'node_id' => $this->node_id))->getField('account_type,status');
        foreach ($payChannelInfo as $v => $k) {
            if ($k == 1)
                $hasPayChannel = 1;
        }
        // 旺分销
        $saler_sess = session('saler_sess' . $this->node_id);
        $errcode = - 1;
        foreach ($payData as $v) {
            $batch_id = M('tbatch_info')->where(
                array(
                    'id' => $v['b_id']))->getField('m_id');
            $salerInfo = $this->wfxService->get_bind_saler($this->node_id, 
                session('groupPhone'), $batch_id, $saler_sess['saler_id']);
            if ($this->wfxService->errcode == 0)
                $errcode = 0;
            if ($errcode == 0 && $salerInfo)
                break;
        }
        // 取得总规则信息
        $ruleType = D('SalePro', 'Service')->getNodeRule($this->node_id);
        // 取得积分规则信息
        $intergralType = D('SalePro', 'Service')->getNodeRule($this->node_id, 
            'tintegral_rule_main');
        // 用户积分
        $memberInfo = D('MemberInstall')->telTermMemberFlag($this->node_id, 
            session('groupPhone'));
        $userPoint = isset($memberInfo['point']) ? (int) $memberInfo['point'] : 0;
        // 获取积分兑换比例
        $exchangeInfo = D('Integral')->getIntegralExchange($this->node_id);
        
        if (! $exchangeInfo) {
            $userIntegral = 0;
        } else {
            if ($userPoint > 0 && ($orderIntegralAmt + $orderOtherAmt) > 0)
                $userIntegral = D('Integral')->getUseIntergral(
                    $orderIntegralAmt + $orderOtherAmt, $this->node_id);
            else
                $userIntegral = 0;
        }
        // 可使用积分
        
        $canUserIntegral = (int) $userIntegral / $exchangeInfo;
        // 积分规则
        $this->assign('userPoint', $userPoint); // 用户积分
        $this->assign('userIntegral', (int) $userIntegral); // 可使用积分规则
        $this->assign('canUserIntegral', (int) $canUserIntegral); // 可使用积分数
        $this->assign('exchangeInfo', $exchangeInfo); // 积分比例
        $this->assign('intergralType', $intergralType); // 积分总规则
        $this->assign('ruleType', $ruleType); // 红包总规则
        $this->assign('hasPayChannel', $hasPayChannel);
        $this->assign('ruleFeeLimit', $ruleFeeLimit); // 免运费总金额
        $this->assign('payChannelInfo', $payChannelInfo);
        $this->assign('userBonus', $userBonus);
        $this->assign('userBounsList', $userBounsList);
        $this->assign('salerInfo', $salerInfo);
        $this->assign('errcode', $errcode);
        $this->assign('curAddress', $curAddress);
        $this->assign('selectddres', $selectddres);
        $this->assign('cartlist', $payData);
        $this->assign('payData', $goodsString);    //需要支付的商品
        $this->assign('shipdata', $shipdata);
        $this->assign('shippingFee', $shippingFee);
        $this->assign('haveAddr', $haveAddr);
        $this->assign('haveCode', $haveCode);
        $this->assign('totalAmount', $calCartInfo[1]);
        $this->assign('normalgoodslist', $normalgoodslist);
        $this->assign('bonusgoodslist', $bonusgoodslist); // 红包商品
        $this->assign('integralGoodsList', $integralGoodsList); // 积分商品
        $this->assign('otherGoodsList', $otherGoodsList); // 积分+红包商品
        $this->assign('bonusTotal', $bonusTotal); // 红包总数
        $this->assign('integralTotal', $integralTotal); // 积分总数
        $this->assign('otherTotal', $otherTotal); // 红包+积分总数
        $this->assign('normalTotal', $normalTotal);
        $this->assign('deli_pay_flag', $deli_pay_flag);
        $this->display();
    }

    public function getShippingFee($orderAmt, $cityCode = 0) {
        $shippingFee = 0;
        
        $queryMap = array(
            "node_id" => $this->node_id);
        // $shippingConfig=M("tecshop_config")->where($queryMap)->find();
        $shippingConfig = M("tcity_express_shipping")->where($queryMap)->find();
        if ($shippingConfig['freight_free_flag'] == 1) {
            if ($orderAmt < $shippingConfig['freight_free_limit']) {
                if (0 === $cityCode) {
                    $shippingFee = $shippingConfig[freight];
                } else {
                    $shippingFee = $shippingConfig[freight];
                }
            } else {
                $shippingFee = 0.00;
            }
        } else {
            $shippingFee = $shippingConfig[freight];
        }
        
        return $shippingFee;
    }
    
    // 立即购买结算页面
    public function direct_to_check() {

        $id = I('id');
        if ($id) {
            import('@.Vendor.RankHelper');
            $RankHelper = RankHelper::getInstance();
            $RankHelper->addOneScore($id);
        }

        $goodsArr = I("goods");
        if (empty($goodsArr)) {
            $this->error("请选择商品");
        }
        $mId = I('mid');
        if (empty($mId)) {
            $this->error("商品信息错误");
        }
        $rgoodsIds = M('tecshop_goods_ex')->where(['m_id' => $mId])->getField('relation_goods');
        if('' != $rgoodsIds){
            //检查关联商品
            $result = $this->goodsModel->checkRelationGoods($rgoodsIds);
            if(false === $result){
                $this->error($this->goodsModel->getError());
            }
        }
        $batchChannelId = I('get.id', session('id'), 'string');
        $this->assign('batchChannelId', $batchChannelId);
        $goodcount = I("goodcount");
        $goodcount = empty($goodcount) ? 1 : (int) $goodcount;
        if ($goodcount < 1) {
            $this->error("请输入正确的商品数");
        }
        //提货方式
        $delivery = I("delivery");
        $delivery = empty($delivery) ? 0 : $delivery;
        
        $skuInfo = I("sku_info");
        $isSku = I('is_sku');
        $skuList = '';
        if ('1' === $isSku) {
            // 是否sku商品
            $skuObj = D('Sku', 'Service');
            // 将传输进入的，号替换为#号
            $skuInfo = $skuObj->replaceArray($skuInfo, '&', ',', '#');
            $skuList = implode(',', $skuInfo);
        }
        
        $newlist = array();
        $totalAmount = 0;
        $haveAddr = 0;
        $haveCode = 0;
        
        $orderType = I('post.orderType', 'normal', 'string');
        $deliPayFlag = 1; // 默认支持货到付款
        if ($orderType == 'bookOrder') {
            $deli_pay_flag = 0;
            $orderDate = I('post.orderDate', '0', 'string');
            $deliveryDate = I('post.specifyOderDate', '0', 'int');
            if ($deliveryDate == '0' && $orderDate != '0') {
                $deliveryDate = $orderDate;
            }
            $deliveryDate = $skuObj->checkBookOrderDeliveryDate($goodsArr[0], $deliveryDate);
            if ($deliveryDate == 'error') {
                $this->error('请填写正确的配送日期');
                exit();
            } else {
                $this->assign('deliveryDate', $deliveryDate);
            }
        }
        $storeType = 0;
        if (! empty($goodsArr)) {
            
            foreach ($goodsArr as $k => $val) {
                // 检查库存，数量限制
                $ret = $this->goodsModel->checkStoreage($val, 2, $this->goodsModel->phoneNum, $goodcount, $skuList);
                
                if (false === $ret) {
                    $this->error($this->goodsModel->getError());
                    exit;
                }
                
                $where = array("e.b_id" => $val);
                
                // 判断是否sku商品
                $skuObj = D('Sku', 'Service');
                if ('1' == $this->goodsModel->isSku) {
                    // 将传输进入的，号替换为#号
                    $filter[] = "g.sku_detail_id in ('" . $skuList . "')";
                    $filter[] = "e.b_id = " . $val;
                    $goodsList = M()->table('tecshop_goods_ex e ')
                        ->join("tbatch_info b ON b.id=e.b_id")
                        ->join("tecshop_goods_sku s ON s.b_id=e.b_id")
                        ->join("tgoods_info t on t.goods_id=b.goods_id")
                        ->join("tgoods_sku_info g ON g.id = s.skuinfo_id")
                        ->join("tmarketing_info m on m.id=b.m_id")
                        ->field(
                        'e.*, b.batch_name, t.goods_id, t.goods_image,m.bonus_flag, m.integral_flag,m.deli_pay_flag,s.skuinfo_id,s.sale_price as batch_amt, s.storage_num as storage_num, s.remain_num as remain_num, b.batch_amt as sale_price, b.storage_num as num, b.remain_num as remain')
                        ->where($filter)
                        ->find();
                    // 取得sku规格
                    $skuInfoList = $skuObj->makeSkuOrderInfo($skuList, $val);
                    if ($skuInfoList) {
                        $goodsList['sku_name'] = $skuInfoList['sku_name'];
                        $goodsList['batch_amt'] = $skuInfoList['batch_amt'];
                    }
                } else {
                    $goodsList = M()->table('tecshop_goods_ex e ')
                        ->join("tbatch_info b ON b.id=e.b_id")
                        ->join("tmarketing_info m on m.id=b.m_id")
                        ->join("tgoods_info t on t.goods_id=b.goods_id")
                        ->field(
                        'e.*,b.*,m.bonus_flag, m.integral_flag,m.deli_pay_flag, m.batch_type as store_type, t.goods_image')
                        ->where($where)
                        ->find();
                }

                if ($delivery == '1') {
                    $haveAddr ++;
                } else {
                    $haveCode ++;
                }
                
                $newlist[$goodsList['b_id']]['batch_name'] = $goodsList['batch_name'];
                $newlist[$goodsList['b_id']]['b_id'] = $goodsList['b_id'];
                $newlist[$goodsList['b_id']]['total'] = $goodcount;
                $newlist[$goodsList['b_id']]['price'] = $goodsList['batch_amt'];
                $amount = $goodcount * $goodsList['batch_amt'];
                $newlist[$goodsList['b_id']]['totalPrice'] = $amount;
                $goodsList['priceAmt'] = $amount;
                //计算各个商品的会员优惠总价(美惠非标)
                $this->mhGoodsInfo($goodsList);
                if($goodsList['vip_discount'] > 0) {
                    $vamount = $goodcount * $goodsList['vip_discount'];
                    $newlist[$goodsList['b_id']]['vipDiscoiuntPrice'] = $vamount;
                }
                //>>>>>end>>>
                $newlist[$goodsList['b_id']]['goods_image'] = $goodsList['goods_image'];
                $newlist[$goodsList['b_id']]['bonus_flag'] = $goodsList['bonus_flag'];
                $newlist[$goodsList['b_id']]['integral_flag'] = $goodsList['integral_flag']; // 积分
                $newlist[$goodsList['b_id']]['deli_pay_flag'] = $goodsList['deli_pay_flag'];
                $newlist[$goodsList['b_id']]['sku_name'] = isset($goodsList['sku_name']) ? $goodsList['sku_name'] : '';
                //是否货到付款
                $deli_pay_flag = $goodsList['deli_pay_flag'];
                $totalAmount = $totalAmount + $amount;
                $storeType = isset($goodsList['store_type']) ? $goodsList['store_type'] : 0;
                $mIdArray[$goodsList['m_id']] = $amount;
            }
        }

        $mIdList = array_keys($mIdArray);
        // 获取满减规则
        $mId = implode(',', $mIdList);
        $fullReduceRule = D('FullReduce')->getFullReduceRuleList($this->node_id, $mId, $storeType);
        if (false != $fullReduceRule) {
            $fullReduceBonus = D('FullReduce')->getReduceBonus($fullReduceRule, $mIdArray);
            $this->assign('fullReduceBonus', $fullReduceBonus); // 满减兑换规则
        }
        if ($haveCode > 0)
            $deli_pay_flag = 0;
        $curAddress = array();
        // 读取收货地址
        if ($haveAddr > 0) {
            $addressList = M('tphone_address e ')->where(
                "e.user_phone='" . session('groupPhone') . "'")
                ->order("last_use_time desc")
                ->limit(20)
                ->select();
            if (! empty($addressList)) {
                
                $selectddres = array();
                foreach ($addressList as $ak => &$al) {
                    $cityInfo = array();
                    if ($al['path']) {
                        $cityInfo = M('tcity_code')->where(
                            array(
                                'path' => $al['path']))
                            ->field(
                            'province_code, city_code, town_code, province, city, town')
                            ->find();
                        $al['province_code'] = $cityInfo['province_code'];
                        $al['city_code'] = $cityInfo['city_code'];
                        $al['town_code'] = $cityInfo['town_code'];
                        $al['province'] = $cityInfo['province'];
                        $al['city'] = $cityInfo['city'];
                        $al['town'] = $cityInfo['town'];
                    }
                    if ($ak == 0) {
                        $curAddress = $al;
                    }
                    $selectddres[] = $al;
                }
            } else
                $curAddress['phone_no'] = session('groupPhone');
        } else {
            $curAddress['phone_no'] = session('groupPhone');
        }
        
        $shippingFee = 0;
        // 免运费限制
        $ruleFeeLimit = 0;
        if ($haveAddr > 0) {
            $this->storeOrderModel->nodeId = $this->node_id;
            $shippingFee = $this->storeOrderModel->getShippingFee($al['province_code'], $al['city_code'], $totalAmount);
            // $totalAmount=$totalAmount+$shippingFee;
            $feeLimit = D('CityExpressShipping')->getFeeLimit($this->node_id);
            if ($totalAmount > $feeLimit && NULL != $feeLimit)
                $ruleFeeLimit = 2;
        }
        
        // 计算红包积分使用的商品
        $orderBonusAmt = 0; // 可用于计算可扣减红包的总金额
        $orderIntegralAmt = 0; // 可用于计算可扣除积分的总金额
        $orderOtherAmt = 0; // 可用于计算可扣除积分和红包的总金额
        $normalgoodslist = array(); // 不参加活动的普通商品
        $bonusgoodslist = array(); // 参加红包的活动商品
        $integralGoodsList = array(); // 参加积分的活动商品
        $otherGoodsList = array(); // 既参加积分和红包活动的商品
        $normalTotal = 0;
        $integralTotal = 0; // 参加积分活动的商品总数
        $bonusTotal = 0; // 参加红包活动的商品总数
        $otherTotal = 0; // 既参加红包又参加积分的商品总数
        $vipDiscountPriceAmt = 0; //会员优惠总金额（美惠非标）
        foreach ($newlist as $kk => $vv) {
            if ($vv['bonus_flag'] == '1' && $vv['integral_flag'] == '0') {
                $orderBonusAmt += $vv['totalPrice'];
                $bonusgoodslist[$kk] = $vv;
                $bonusTotal += $vv['totalPrice'];
            } else if ($vv['bonus_flag'] == '0' && $vv['integral_flag'] == '1') {
                $orderIntegralAmt += $vv['totalPrice'];
                $integralGoodsList[$kk] = $vv;
                $integralTotal += $vv['totalPrice'];
            } else if ($vv['bonus_flag'] == '1' && $vv['integral_flag'] == '1') {
                $orderOtherAmt += $vv['totalPrice'];
                $otherGoodsList[$kk] = $vv;
                $otherTotal += $vv['totalPrice'];
            } else {
                $normalgoodslist[$kk] = $vv;
                $normalTotal += $vv['totalPrice'];
            }

            //计算所有商品的会员优惠总价(美惠非标)
            if($vv['vipDiscoiuntPrice']) {
                $vipDiscountPriceAmt += $vv['vipDiscoiuntPrice'];
        }
        }
        if($vipDiscountPriceAmt > 0) {
            $this->assign('vipDiscountPriceAmt', $vipDiscountPriceAmt);
        }
        
        if (($orderBonusAmt + $orderOtherAmt) > 0)
            $userBonus = $this->getUseBonus($orderBonusAmt + $orderOtherAmt);
        else
            $userBonus = 0;
            
            // 可用使用的红包明细
        $userBounsList = $this->getUserBonus();
        
        // 获取可支付通道
        $hasPayChannel = 0;
        $payChannelInfo = M('tnode_account')->where(
            array(
                'node_id' => $this->node_id))->getField('account_type,status');
        foreach ($payChannelInfo as $v => $k) {
            if ($k == 1)
                $hasPayChannel = 1;
        }
        // 旺分销
        $saler_sess = session('saler_sess' . $this->node_id);
        log_write("pengqi1".print_r(session('saler_sess' . $this->node_id),true));
        log_write("yesong:".$this->batch_id);
        $salerInfo = $this->wfxService->get_bind_saler($this->node_id, 
            session('groupPhone'), $this->batch_id, $saler_sess['saler_id']);
        log_write("pengqi2".print_r($salerInfo,true));
        $errcode = $this->wfxService->errcode;
        // 取得红包总规则信息
        $ruleType = D('SalePro', 'Service')->getNodeRule($this->node_id);
        // 取得积分规则信息
        $intergralType = D('SalePro', 'Service')->getNodeRule($this->node_id, 
            'tintegral_rule_main');
        // 用户积分
        $memberInfo = D('MemberInstall')->telTermMemberFlag($this->node_id, 
            session('groupPhone'));
        $userPoint = isset($memberInfo['point']) ? (int) $memberInfo['point'] : 0;
        // 获取积分兑换比例
        $exchangeInfo = D('Integral')->getIntegralExchange($this->node_id);
        
        if (! $exchangeInfo) {
            $userIntegral = 0;
        } else {
            if ($userPoint > 0 && ($orderIntegralAmt + $orderOtherAmt) > 0)
                $userIntegral = D('Integral')->getUseIntergral(
                    $orderIntegralAmt + $orderOtherAmt, $this->node_id);
            else
                $userIntegral = 0;
        }

        // 可使用积分
        $canUserIntegral = (int) ($userIntegral / $exchangeInfo);
        $totalAmount = floor(bcmul($totalAmount, 100)) / 100;
        $this->assign('hasPayChannel', $hasPayChannel);
        $this->assign('payChannelInfo', $payChannelInfo);
        $this->assign('userBonus', $userBonus);
        // 积分规则
        $this->assign('userPoint', $userPoint); // 用户积分
        $this->assign('userIntegral', (int) $userIntegral); // 可使用积分规则
        $this->assign('canUserIntegral', (int) $canUserIntegral); // 可使用积分数
        $this->assign('exchangeInfo', $exchangeInfo); // 积分比例
        $this->assign('intergralType', $intergralType); // 积分总规则
        $this->assign('ruleType', $ruleType); // 红包总规则
        $this->assign('userBounsList', $userBounsList);
        $this->assign('ruleFeeLimit', $ruleFeeLimit); // 免运费总金额
        $this->assign('salerInfo', $salerInfo);
        $this->assign('errcode', $errcode);
        $this->assign('curAddress', $curAddress);
        $this->assign('selectddres', isset($selectddres) ?  $selectddres : '');
        $this->assign('haveAddr', $haveAddr);
        $this->assign('haveCode', $haveCode);
        $this->assign('shippingFee', $shippingFee);
        $this->assign('cartlist', $newlist);
        $this->assign('skuInfo', implode('&', $skuInfo));
        $this->assign('totalAmount', $totalAmount);
        $this->assign('normalgoodslist', $normalgoodslist);
        $this->assign('bonusgoodslist', $bonusgoodslist); // 红包商品
        $this->assign('integralGoodsList', $integralGoodsList); // 积分商品
        $this->assign('otherGoodsList', $otherGoodsList); // 积分+红包商品
        $this->assign('bonusTotal', $bonusTotal); // 红包总数
        $this->assign('integralTotal', $integralTotal); // 积分总数
        $this->assign('otherTotal', $otherTotal); // 红包+积分总数
        $this->assign('normalTotal', $normalTotal);
        $this->assign('deli_pay_flag', $deli_pay_flag);
        $this->assign('orderType', $orderType);
        $this->display();
    }
    
    // 登录判断
    public function checkPhoneLogin() {
        // 判断是否登录
        if (! session('?groupPhone')) {
            $this->ajaxReturn('', '', 0);
        } else {
            $this->ajaxReturn('', '', 1);
        }
    }
    
    // 手机发送验证码
    public function sendCheckCode() {
        
        // 图片校验码
        /*
         * $verify = I('post.verify',null,'mysql_real_escape_string');
         * if(session('verify') != md5($verify)) { $this->error("图片验证码错误"); }
         */
        $phoneNo = I('post.phone', null);
        if (! check_str($phoneNo, 
            array(
                'null' => false, 
                'strtype' => 'mobile'), $error)) {
            $this->error("手机号{$error}");
        }
        
        // 测试环境不下发，验证码直接为1111
        if (! is_production()) {
            $groupCheckCode = array(
                'number' => 1111, 
                'add_time' => time(), 
                'phoneNo' => $phoneNo);
            session('groupCheckCode', $groupCheckCode);
            $this->ajaxReturn("success", "验证码已发送", 1);
        }
        
        // 发送频率验证
        $groupCheckCode = session('groupCheckCode');
        if (! empty($groupCheckCode) &&
             (time() - $groupCheckCode['add_time']) < $this->expiresTime) {
            $this->error('动态密码发送过于频繁!');
        }
        $num = mt_rand(1000, 9999);
        $exptime = $this->CodeexpiresTime / 60;
        $text = "您在{$this->node_short_name}商户的动态密码是：{$num}，有效期{$exptime}分钟。如非本人操作请忽略。";
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
        $ret_msg = $resp_array['NotifyRes']['Status'];
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
             $ret_msg['StatusCode'] != '0001')) {
            $this->error('发送失败');
        }
        $groupCheckCode = array(
            'number' => $num, 
            'add_time' => time(), 
            'phoneNo' => $phoneNo);
        session('groupCheckCode', $groupCheckCode);
        $this->success('动态密码已发送');
    }
    
    // 登录
    public function loginPhone() {
        $phoneNo = I('post.phone', null, 'mysql_real_escape_string');
        if (! check_str($phoneNo, 
            array(
                'null' => false, 
                'strtype' => 'mobile'), $error)) {
            $this->ajaxReturn(array(
                'type' => 'phone'), "手机号{$error}", 0);
        }
        // 手机验证码
        $checkCode = I('post.check_code', null);
        if (! check_str($checkCode, array(
            'null' => false), $error)) {
            $this->ajaxReturn(array(
                'type' => 'pass'), "动态密码{$error}", 0);
        }
        $groupCheckCode = session('groupCheckCode');
        if (! empty($groupCheckCode) && $groupCheckCode['phoneNo'] != $phoneNo)
            $this->ajaxReturn(array(
                'type' => 'phone'), '手机号码不正确', 0);
        if (! empty($groupCheckCode) && $groupCheckCode['number'] != $checkCode)
            $this->ajaxReturn(array(
                'type' => 'pass'), '动态密码不正确', 0);
        if (time() - $groupCheckCode['add_time'] > $this->CodeexpiresTime)
            $this->ajaxReturn(array(
                'type' => 'pass'), '动态密码已经过期', 0);
            
            // 记录session
        session('groupPhone', $phoneNo);
        // 插入tmember_info_tmp会员表
        $userId = addMemberByO2o($phoneNo, $this->node_id, $this->channel_id, $this->batch_id);
        session('store_mem_id' . $this->node_id, 
            array(
                'user_id' => $userId));
        // 补充全局cookie
        $global_phone = cookie('_global_user_mobile');
        if (! $global_phone) {
            cookie('_global_user_mobile', $phoneNo, 3600 * 24 * 365);
        }
        $this->success('登录成功');
    }
    
    // 用户订单查看
    public function showOrderList() {
        // 判断是否登录
        if (! session('?groupPhone')) {
            $this->showMsg('您还没有登录');
        }
        // 标签
        $model = M('tbatch_channel');
        $map = array(
            'id' => session("id"), 
            'batch_type' => $this->batch_type);
        $result = $model->where($map)->find();
        
        if (! $result)
            $this->error('错误的参数！', 0, $id, $this->node_short_name);
        $where = array(
            'o.order_phone' => session('groupPhone'), 
            'o.node_id' => $result['node_id'], 
            'o.order_type' => '2', 
            'o.order_status' => array(
                'neq', 
                '2')); // 已取消的订单wap页不显示
        
        $orderList = M()->table('ttg_order_info o')
            ->where($where)
            ->order('o.add_time DESC')
            ->select();
        // 获取城市信息
        foreach ($orderList as $key => &$val) {
            if ($val['receiver_citycode']) {
                $cityInfo = M('tcity_code')->where(
                    array(
                        'path' => $val['receiver_citycode']))
                    ->field(
                    'province_code, city_code, town_code, province, city, town')
                    ->find();
                $orderList[$key]['province_code'] = $cityInfo['province_code'];
                $orderList[$key]['city_code'] = $cityInfo['city_code'];
                $orderList[$key]['town_code'] = $cityInfo['town_code'];
                $orderList[$key]['province'] = $cityInfo['province'];
                $orderList[$key]['city'] = $cityInfo['city'];
                $orderList[$key]['town'] = $cityInfo['town'];
            }
        }
        
        /*
         * $nowP = I('p',null,mysql_real_escape_string);//页数 empty($nowP) ?
         * $nowP = 1 : $nowP; $pageCount = 10;//每页显示条数 $field =
         * array('o.*,g.group_goods_name,g.group_price'); $orderList =
         * M()->table('ttg_order_info o')->field($field) ->join("tmarketing_info
         * g ON o.group_batch_no=g.member_level")
         * ->where($where)->order('o.add_time DESC')
         * ->limit(($nowP-1)*$pageCount,$pageCount) ->select(); $status =
         * array('1'=>'未支付','2'=>'已支付'); $ajax = I('get.ajax',null);
         */
        
        $status = array(
            '1' => '未支付', 
            '2' => '已支付');
        $this->assign('orderList', $orderList);
        $this->assign('status', $status);
        $this->assign('node_short_name', $this->node_short_name);
        $this->assign('id', $id);
        $this->display();
    }

    public function direct_order_save() {
        // 判断是否登录
        if (! session('?groupPhone')) {
            $this->error('您还没有登录');
        }
        $pcode = I("province_code", "");
        $ccode = I("city_code", "");
        $town_code = I("town_code", "");
        $addsPath = $pcode . $ccode . $town_code;
        
        // 判断提交的商品数据是否存在
        $goods = I("goods");
        $total = I("total");
        $sessionId = session("id");
        $discountPrice = I("discountPrice");
        $skuInfo = I("skuInfo");
        $usePoint = (int) I("usePoint"); // 使用积分
        $needPayMoney = 0; // 积分需要抵用金额(初始化) 使用的红包
        $bonus_use_id = I("bonus_use_id");
        $names = I("names");
        $bonus_flag = I("bonus_flag");
        $integral_flag = I("integral_flag");
        $deli_pay_flag = I("deli_pay_flag");
        if (empty($goods) || empty($total) || empty($bonus_flag) || empty($integral_flag) || empty($deli_pay_flag)) {
            $this->error('参数错误');
        }
        
        $address = I("receive_address");
        if (! empty($address)) {
            if (empty($pcode) || empty($ccode) || empty($town_code)) {
                $this->error('请选择正确的城市信息');
            }
        }
        $receive_name = I("receive_name");
        $receive_phone = I("receive_phone");
        $memo = I("memo");
        $haveAddr = I("haveAddr");
        $pay_channel = I("payment_type"); // 1 支付宝 2联动优势
                                          // 货到付款不可使用红包
        //货到付款
        if ($pay_channel == '4') {
            $bonus_use_id = '';
        }
        
        //检查支付通道是否为空
        if (empty($pay_channel)){
                $this->error('请选择支付方式');
        }
        
        $totalgoods = 0;
        $totalAmt = 0;
        
        $order_phone = session('groupPhone');
        if ($receive_phone == "") {
            $receive_phone = session('groupPhone');
        }

        $order_id = date('ymd') . substr(time(), - 3) . substr(microtime(), 2, 7);
        // 爱蒂宝
        $this->adbOrder($order_id);
        if (! empty($goods)) {
            // 计算红包只算购物车内可使用红包的商品
            $orderBonusAmt = 0; // 可用于计算可扣减红包的总金额
            $orderIntegralAmt = 0; // 可用于计算可扣除积分的总金额
            $orderOtherAmt = 0; // 可用于计算可扣除积分和红包的总金额
            $discountPriceAmt = 0; //优惠总金额
            M()->startTrans(); // 起事务
            // 判断提交的商品数据是否存在
            $bInfo = M('tbatch_info')->field('storage_num,remain_num, node_id')
                ->where(array('id' => $goods[0]))
                ->lock(true)
                ->find();
            if(!$bInfo){
                M()->rollback();
                $this->error("当前商品信息获取失败！");
            }
            $this->node_id = $bInfo['node_id'];
            $this->storeOrderModel->nodeId = $this->node_id;
            foreach ($goods as $b => $bl) {
                // 创建sku信息
                $skuObj = D('Sku', 'Service');
                $skuObj->nodeId = $this->node_id;
                $price = $bInfo['batch_amt'];
                // 将传输进入的，号替换为#号
                $skuInfo = $skuObj->replaceArray($skuInfo, '&', ',', '#');
                $skuList = implode(',', $skuInfo);
                //检查商品配置及库存
                $ret = $this->goodsModel->checkStoreage($bl, 2, $order_phone, $total[$b], $skuList);
                //获取规格商品价格
                $price[$b] = $this->goodsModel->getPrice;
                if('' === $price[$b]){
                    $this->error("获取商品价格失败！");
                }
                if($total[$b] < 1){
                    $this->error("购买商品数量有误！");
                }
                if (false === $ret) {
                    M()->rollback();
                    $this->error($this->goodsModel->getError());
                    exit;
                }
                $totalgoods = $totalgoods + $total[$b];
                $goodsamt = $total[$b] * $price[$b];
                if($this->node_id == C('meihui.node_id')) {
                    $goodsamt = $goodsamt - $discountPrice[$b];
                    $discountPriceAmt = $discountPriceAmt + $discountPrice[$b];
                }
                $totalAmt = $totalAmt + $goodsamt;
                if ($bonus_flag[$b] == '1' && $integral_flag[$b] == '0') {
                    $orderBonusAmt = $orderBonusAmt + $goodsamt;
                }
                if ($bonus_flag[$b] == '0' && $integral_flag[$b] == '1') {
                    $orderIntegralAmt = $orderIntegralAmt + $goodsamt;
                }
                if ($bonus_flag[$b] == '1' && $integral_flag[$b] == '1') {
                    $orderOtherAmt = $orderOtherAmt + $goodsamt;
                }
            }
            
            // 判断是否存在物流，只要存在一个产品是物流，订单主表记录物流方式
            $shippingFee = 0;
            if ($haveAddr > 0) {
                $isshipping = "1";
                // 计算运费
                $shippingFee = $this->storeOrderModel->getShippingFee($pcode, $ccode, $totalAmt);
                $totalAmt = $totalAmt + $shippingFee;
                // 插入收货地址表
                // 判断地址是否存在，不存插入
                $result = $this->storeOrderModel->updateOrderAddress($order_phone, $receive_phone, $address, $receive_name, $addsPath);
                if(!$result){
                    M()->rollback();
                    $this->error("添加收货地址信息失败！");
                }
            } else {
                $isshipping = "0";
            }
            
            // 包则订单金额减去红包金额，如果红包金额小于红包可使用金额则减去金额，否则减去最大可以使用金额
            // 用于红包可兑换的总金额
            $orderBonusCountAmt = $orderBonusAmt + $orderOtherAmt;
            $bounsAmount = 0;
            if ($bonus_use_id != "") {
                // 计算订单可减去金额
                $reAmount = $this->orderCutBonus($bonus_use_id);
                // 如果累计的红包金额满足最大的使用红包金额，则减去最大红包金额，否则减去红包金额累计
                $maxAmount = $this->getUseBonus($orderBonusCountAmt);
                if ($reAmount < $maxAmount) {
                    $bounsAmount = $reAmount;
                } else {
                    $bounsAmount = $maxAmount;
                }
            }
            // 计算积分换购数
            $needUseIntegral = 0; // 可使用积分，初始化0
            if ($bounsAmount > $orderBonusAmt) {
                $needUseIntegral = ($orderBonusCountAmt + $orderIntegralAmt) - $bounsAmount;
            } else {
                $needUseIntegral = $orderBonusCountAmt + $orderIntegralAmt;
            }
            // 判断如果有可使用的积分
            if ($usePoint > 0 && ($needUseIntegral) > 0) {
                // 取得积分规则信息
                $intergralType = D('SalePro', 'Service')->getNodeRule(
                    $this->node_id, 'tintegral_rule_main');
                // 用户积分
                $memberInfo = D('MemberInstall')->telTermMemberFlag(
                    $this->node_id, session('groupPhone'));
                $myPoint = isset($memberInfo['point']) ? (int) $memberInfo['point'] : 0;
                if ($myPoint < $usePoint) {
                    M()->rollback();
                    $this->error("您当前积分不足以支付本次订单！");
                }
                // 获取积分兑换比例
                $exchangeInfo = D('Integral')->getIntegralExchange($this->node_id);
                if (! $exchangeInfo) {
                    $userIntegral = 0;
                } else {
                    if ($usePoint > 0){
                        // 取得积分购总金额
                        $userIntegral = D('Integral')->getUseIntergral($orderOtherAmt + $orderIntegralAmt, $this->node_id);
                    }else{
                        $userIntegral = 0;
                    }    
                }
                // 可使用积分
                $canUserIntegral = (int) $userIntegral / $exchangeInfo;
                if ($usePoint > $canUserIntegral){
                    M()->rollback();
                    $this->error("您使用的积分超出了本次需要使用的积分限制！");
                }
                    // 可抵用金额
                $needPayMoney = $usePoint * $exchangeInfo;
                // 判断用户使用的积分和剩余可扣除的金额
                if ($needPayMoney > $needUseIntegral){
                    M()->rollback();
                    $this->error("您使用的积分已超出本次可抵扣金额！");
                }
            }else{
                $usePoint = 0;
            }
            $cutAmount = $bounsAmount + $needPayMoney;
            $cutAmount = floor(bcmul($cutAmount, 100)) / 100;
            // 判断银联金额是否大于1元
            if ('2' == $pay_channel) {
                $msg = D('PayOrder', 'Service')->checkPayRule(bcsub($totalAmt, $cutAmount, 2), $pay_channel, '1.00');
                if (false === $msg){
                    M()->rollback();
                    $this->error("订单生成失败，银联支付金额必须大于1元！");
                }    
            }
            $wxOpenId = '';
            // 判断是否微信订单
            if ($this->wx_flag == 1) {
                $wxUserInfo = session('merWxUserInfo');
                if (! $wxUserInfo){
                    $wxUserInfo = session('wxUserInfo');
                }    
                $wxOpenId = $wxUserInfo['openid'];
            }
            $ip = GetIP();
            // 入库主订单
            $data = array(
                'order_id' => $order_id, 
                'order_type' => '2', 
                'from_channel_id' => $this->id, 
                'from_batch_no' => $this->batch_id, 
                'batch_no' => $this->batch_id, 
                'order_phone' => $order_phone, 
                'buy_num' => $totalgoods, 
                'batch_channel_id' => $this->id, 
                'node_id' => $this->node_id, 
                'order_amt' => bcsub($totalAmt, $cutAmount, 2),
                'add_time' => date('YmdHis'), 
                'receiver_type' => $isshipping, 
                'receiver_name' => trim($receive_name), 
                'receiver_addr' => trim($address), 
                'receiver_phone' => trim($receive_phone), 
                'pay_channel' => $pay_channel, 
                'order_status' => '0', 
                'memo' => $memo, 
                'freight' => $shippingFee, 
                'order_ip' => $ip, 
                'receiver_citycode' => trim($addsPath),  // 新增城市代码 添加人：曾成
                'bonus_use_amt' => $bounsAmount,  // 新增红包使用金额在订单表中 添加人：曾成
                'point_use' => $usePoint,  // 新增使用积分在订单表中 添加人：曾成
                'point_use_amt' => $needPayMoney,  // 新增积分使用金额在订单表中 添加人：曾成
                'openId' => $wxOpenId); // 新增微信OPENID在订单表中 添加人：曾成

            if($this->node_id == C('meihui.node_id')) {
                $data['discount_use_amt'] = $discountPriceAmt;
            }
            $orderType = I('post.orderType', 'normal', 'string');
            if ($orderType == 'bookOrder') {
                $data['other_type'] = '1';
                $deliveryDate = I('post.deliveryDate', '0', 'string');
                $deliveryDate = $skuObj->checkBookOrderDeliveryDate( $goods[0], $deliveryDate);
                if ($deliveryDate == 'error') {
                    M()->rollback();
                    $this->error('请填写正确的配送日期');
                } else {
                    $data['book_delivery_date'] = $deliveryDate;
                }
            } else {
                $data['other_type'] = '0';
            }
            // 博雅非标
            if ($this->node_id == C('fb_boya.node_id')) {
                $data['parm1'] = $this->storeOrderModel->orderToFbBoya($order_phone);
            }
            // 旺分销
            $saler_sess = session('saler_sess' . $this->node_id);
            log_write("pengqi4".print_r($saler_sess,true));
            if($saler_sess){
                $saler_phone = I('saler_phone');
                log_write("pengqi5".$saler_phone);
                $data['saler_id'] = $this->storeOrderModel->isSalersess($order_id, $goods, $order_phone, $saler_sess, $saler_phone);
                log_write("pengqi6".$data['saler_id']);
            }
            
            $result = M('ttg_order_info')->add($data);
            if ($result) {
                // $shipdata =$this->_getShipType();
                foreach ($goods as $c => $cl) {
                    $child_order_id = date('ymd') . substr(time(), -3) . substr(mt_rand(11111, 99999), 0) . $cl;
                    $row['order_id'] = $order_id;
                    $row['trade_no'] = $child_order_id;
                    $row['b_id'] = $cl;
                    $row['b_name'] = $names[$c];
                    $row['goods_num'] = $total[$c];
                    $row['price'] = $price[$c];
                    $row['amount'] = $total[$c] * $price[$c];
                    $row['receiver_type'] = $isshipping;
                    //判断是否规格商品
                    $isSku = $skuObj->checkIsSkuPro($cl);
                    if ('1' === $isSku) {
                        // sku信息商品处理
                        $skuInfoList = $skuObj->makeSkuOrderInfo($skuList, $cl);
                        if ($skuInfoList) {
                            $row['ecshop_sku_id'] = $skuInfoList['id'];
                            $row['ecshop_sku_desc'] = $skuInfoList['sku_name'];
                        }
                    }

                    $res = M('ttg_order_info_ex')->add($row);
                    if ($res) {       
                        $result = $this->storeOrderModel->orderStorageDec($bInfo, $cl, $row, $isSku);
                        if(!$result){
                            M()->rollback();
                            $this->error($this->storeOrderModel->getError());
                        }
                    } else {
                        
                        M()->rollback();
                        $this->error("订单生成失败，订单商品入库异常！");
                    }
                    unset($isSku, $skuInfoList, $row);
                }

                // 批量减红包
                if ($bonus_use_id != "") {
                    
                    $resok = $this->useBonus($bonus_use_id, $data['order_id'], $bounsAmount, $data['order_amt']); // 实际可用红包的商品总额
                    if (! $resok) {
                        M()->rollback();
                        $this->error("订单生成失败，红包扣减异常！");
                    }
                }
                if($usePoint > 0){
                    // 扣除用户积分
                    log_write("yyyyyy".$this->node_id);
                    $retInfo = D('MemberInstall')->deductionMemberPoint($this->node_id, $order_phone, $usePoint, $data['order_id']);
                    if (! $retInfo) {
                        M()->rollback();
                        $this->error("订单生成失败，积分扣减异常！");
                    }
                }
            } else {
                M()->rollback();
                $this->error("订单生成失败，主订单入库异常！");
            }
            
            M()->commit(); // 提交事务
            //更新用户购买信息
            //$this->goodsModel->getCustomerBuyNum($order_phone, $this->batch_id, $totalgoods);
            //订单支付
            $this->storeOrderModel->orderToPay($order_id, $data['order_amt'], $pay_channel, $this->wx_flag, $sessionId, $this->id, $this->node_short_name, $data['other_type']);
        } else {
            
            $this->error(
                "商品数据为空，请到个人中心,<a href='index.php?g=Label&m=MyOrder&a=index&node_id=" .
                     $this->node_id . "'>查看我的订单</a>");
        }
    }
    
    // 保存订单
    public function order_save() {
        // 判断是否登录
        if (! session('?groupPhone')) {
            $this->error('您还没有登录');
        }
        //购买商品信息
        $goodsString = $_POST['payData'];
        $goodsArray = json_decode($goodsString, true)['goods'];
        $order_id = date('ymd') . substr(time(), - 3) . substr(microtime(), 2, 7);
        $pcode = I("province_code", "");
        $ccode = I("city_code", "");
        $town_code = I("town_code", "");
        $addsPath = $pcode . $ccode . $town_code;
        $address = I("receive_address");
        if (! empty($address)) {
            if (empty($pcode) || empty($ccode) || empty($town_code)) {
                $this->error('请选择正确的城市信息');
            }
        }
        $receive_name = I("receive_name");
        $receive_phone = I("receive_phone");
        $bonus_use_id = I("bonus_use_id", "", "trim");
        $memo = I("memo");
        $pay_channel = I("payment_type"); // 支付宝
        $usePoint = (int) I("usePoint"); // 使用积分
        $needPayMoney = 0; // 积分需要抵用金额(初始化)
        $carts = $this->goodsModel->_getCart();
        if(false === $carts){
            // 判断是否登录
            $this->error('您还没有登录');
        }

        // 货到付款不可使用红包
        if ($pay_channel == '4'){
            $bonus_use_id = '';
        }
        //检查支付通道是否为空
        if (empty($pay_channel)){
                $this->error('请选择支付方式');
        }
        $order_phone = session('groupPhone');
        $sessionId = session("id");
        if ($receive_phone == "") {
            $receive_phone = session('groupPhone');
        }
            // 爱蒂宝
        $this->adbOrder($order_id);
        
        if (! empty($carts) && !empty($goodsArray)) {
            // 购物车数据
            $cartlist = $this->getCat($carts);
            if (empty($cartlist[0])) {
                $this->error(
                    "购物车数据为空，请到个人中心,<a href='index.php?g=Label&m=MyOrder&a=index&node_id=" .
                         $this->node_id . "'>查看我的订单</a>");
            } else {
                M()->startTrans(); // 起事务
                $totalgoods = 0;
                //获取需要结算的购物车信息
                $payData = array();
                foreach($goodsArray as $val){
                    $key = $val['bId'];
                    //拼接规格商品KEY
                    if(!empty($val['skuId'])){
                        $val['skuId'] = str_replace(',', '#', $val['skuId']);
                        $key = $val['skuId'] . '_' . $key;
                    }
                    $payData[$key] = $cartlist[0][$key];
                    $payCartDate[$key] = $carts[$key];
                }
                // 计算金额
                $calCartInfo = $this->calCartInfo($payData);
                // 判断库存是否足够
                foreach ($payData as $k => $kal) {
                    // 判断提交的商品数据是否存在(锁表操作)
                    $goodsInfo = $bInfoList[$kal['b_id']] = M('tbatch_info')->field('storage_num,remain_num,node_id')
                            ->where(array('id' => $kal['b_id']))
                            ->lock(true)
                            ->find();
                    //判断商品是否存在
                    if(!$goodsInfo){
                            M()->rollback();
                            $this->error("当前商品信息获取失败！");
                    }
                    $this->node_id = $goodsInfo['node_id'];
                    $this->storeOrderModel->nodeId = $this->node_id;
                    
                    if (isset($kal['skuId'])) {
                        $goodsInfo = M('tecshop_goods_sku s')->join(
                            'tgoods_sku_info g ON g.id = s.skuinfo_id')
                            ->field("s.storage_num,s.remain_num")
                            ->where("g.sku_detail_id='" . $kal['skuId'] . "'")
                            ->find();
                    } 

                    $totalgoods = $totalgoods + $kal['total'];
                    //检查商品配置及库存
                    $ret = $this->goodsModel->checkStoreage($kal['b_id'], 1, $order_phone, 0, $kal['skuId']);
                    if (false === $ret) {
                        M()->rollback();
                        $this->error($this->goodsModel->getError());
                    }
                }
            }
            
            // 判断是否存在物流，只要存在一个产品是物流，订单主表记录物流方式
            $shipdata = $this->goodsModel->_getShipType();
            $isshipping = "0";
            if (! empty($shipdata)) {
                foreach ($shipdata as $s => $sal) {
                    if ($sal == '1') {
                        $isshipping = "1";
                    }
                }
            }
            // 计算运费
            
            $shippingFee = 0;
            if ($isshipping == "1") {
                $shippingFee = $this->storeOrderModel->getShippingFee($pcode, $ccode, $totalAmt);
                $calCartInfo[1] = $calCartInfo[1] + $shippingFee;
                // 插入收货地址表
                // 判断地址是否存在，不存插入
                $result = $this->storeOrderModel->updateOrderAddress($order_phone, $receive_phone, $address, $receive_name, $addsPath);
                if(!$result){
                    M()->rollback();
                    $this->error("添加收货地址信息失败！");
                }
            }
            
            // 计算红包只算购物车内可使用红包的商品
            $orderBonusAmt = 0; // 可用于计算可扣减红包的总金额
            $orderIntegralAmt = 0; // 可用于计算可扣除积分的总金额
            $orderOtherAmt = 0; // 可用于计算可扣除积分和红包的总金额
            $discountPriceAmt = 0; //会员优惠总金额
            foreach ($payData as $kk => $vv) {
                $totalPrice = $vv['totalPrice'];
                if($vv['vipDiscoiuntPrice']) {
                    $discountPriceAmt += $vv['vipDiscoiuntPrice'];
                    $totalPrice = $totalPrice - $vv['vipDiscoiuntPrice'];
                }
                // 只计算红包单独可使用的金额
                if ($vv['bonus_flag'] == '1' && $vv['integral_flag'] == '0') {
                    $orderBonusAmt += $totalPrice;
                }
                // 只计算积分单独可使用的金额
                if ($vv['integral_flag'] == '1' && $vv['bonus_flag'] == '0') {
                    $orderIntegralAmt += $totalPrice;
                }
                // 只计算既可以使用积分又可以使用红包的金额
                if ($vv['integral_flag'] == '1' && $vv['bonus_flag'] == '1') {
                    $orderOtherAmt += $totalPrice;
                }
            }
            //总金额减去会员优惠金额
            if($this->node_id == C('meihui.node_id')) {
                $calCartInfo[1] = $calCartInfo[1] - $discountPriceAmt;
            }
            // 用于红包可兑换的总金额
            $orderBonusCountAmt = $orderBonusAmt + $orderOtherAmt;
            // 判断如果有可使用的红包则订单金额减去红包金额，如果红包金额小于红包可使用金额则减去金额，否则减去最大可以使用金额
            $bounsAmount = 0;
            if ($bonus_use_id != "") {
                // 计算订单可减去金额
                $reAmount = $this->orderCutBonus($bonus_use_id);
                // 如果累计的红包金额满足最大的使用红包金额，则减去最大红包金额，否则减去红包金额累计
                $maxAmount = $this->getUseBonus($orderBonusCountAmt);
                if ($reAmount < $maxAmount) {
                    $bounsAmount = $reAmount;
                } else {
                    $bounsAmount = $maxAmount;
                }
            }
            // 计算积分换购数
            $needUseIntegral = 0; // 可使用积分，初始化0
            if ($bounsAmount > $orderBonusAmt) {
                $needUseIntegral = ($orderBonusCountAmt + $orderIntegralAmt) -
                     $bounsAmount;
            } else {
                $needUseIntegral = $orderBonusCountAmt + $orderIntegralAmt;
            }
            // 判断如果有可使用的积分
            if ($usePoint > 0 && ($needUseIntegral) > 0) {
                // 取得积分规则信息
                $intergralType = D('SalePro', 'Service')->getNodeRule($this->node_id, 'tintegral_rule_main');
                // 用户积分
                $memberInfo = D('MemberInstall')->telTermMemberFlag($this->node_id, $order_phone);
                $myPoint = isset($memberInfo['point']) ? (int) $memberInfo['point'] : 0;
                if ($myPoint < $usePoint) {
                    M()->rollback();
                    $this->error("您当前积分不足以支付本次订单！");
                }
                // 获取积分兑换比例
                $exchangeInfo = D('Integral')->getIntegralExchange($this->node_id);
                
                if (! $exchangeInfo) {
                    $userIntegral = 0;
                } else {
                    if ($usePoint > 0){
                        // 取得积分购总金额
                        $userIntegral = D('Integral')->getUseIntergral($orderOtherAmt + $orderIntegralAmt, $this->node_id);
                    }else{
                        $userIntegral = 0;
                    }    
                }
                // 可使用积分
                $canUserIntegral = (int) $userIntegral / $exchangeInfo;
                if ($usePoint > $canUserIntegral){
                    M()->rollback();
                    $this->error("您使用的积分超出了本次需要使用的积分限制！");
                }    
                    // 可抵用金额
                $needPayMoney = $usePoint * $exchangeInfo;
                // 判断用户使用的积分和剩余可扣除的金额
                if ($needPayMoney > $needUseIntegral){
                    M()->rollback();
                    $this->error("您使用的积分已超出本次可抵扣金额！");
                }    
            }else{
                $usePoint = 0;
            }
            $cutAmount = $bounsAmount + $needPayMoney;
            $cutAmount = floor(bcmul($cutAmount, 100)) / 100;
            // 判断银联金额是否大于1元
            if ('2' == $pay_channel) {
                $msg = D('PayOrder', 'Service')->checkPayRule(
                    bcsub($calCartInfo[1], $cutAmount, 2), $pay_channel, '1.00');
                if (false === $msg){
                    M()->rollback();
                    $this->error("订单生成失败，银联支付金额必须大于1元！");
                }    
            }
            $wxOpenId = '';
            // 判断是否微信订单
            if ($this->wx_flag == 1) {
                $wxUserInfo = session('merWxUserInfo');
                if (! $wxUserInfo)
                    $wxUserInfo = session('wxUserInfo');
                $wxOpenId = $wxUserInfo['openid'];
            }
            $ip = GetIP();

            // 入库主订单
            $data = array(
                'order_id' => $order_id, 
                'order_type' => '2', 
                'from_channel_id' => $sessionId, 
                'from_batch_no' => $this->batch_id, 
                'batch_no' => $this->batch_id, 
                'order_phone' => $order_phone, 
                'buy_num' => $totalgoods, 
                'batch_channel_id' => $sessionId, 
                'node_id' => $this->node_id, 
                'order_amt' => bcsub($calCartInfo[1], $cutAmount, 2),
                'add_time' => date('YmdHis'), 
                'receiver_name' => trim($receive_name), 
                'receiver_type' => $isshipping, 
                'receiver_addr' => trim($address), 
                'receiver_phone' => trim($receive_phone), 
                'pay_channel' => $pay_channel, 
                'order_status' => '0', 
                'memo' => $memo, 
                'add_time' => date('YmdHis'), 
                'freight' => $shippingFee, 
                'order_ip' => $ip, 
                'bonus_use_amt' => $bounsAmount,  // 新增红包使用金额在订单表中 添加人：曾成
                'point_use' => $usePoint,  // 新增使用积分在订单表中 添加人：曾成
                'point_use_amt' => $needPayMoney,  // 新增积分使用金额在订单表中 添加人：曾成
                'receiver_citycode' => trim($addsPath),  // 新增城市代码 添加人：曾成
                'openId' => $wxOpenId); // 新增微信OPENID在订单表中 添加人：曾成

            if($this->node_id == C('meihui.node_id')) {
                $data['discount_use_amt'] = $discountPriceAmt;
            }
                                        
            // 博雅非标
            if ($this->node_id == C('fb_boya.node_id')) {
                $data['parm1'] = $this->storeOrderModel->orderToFbBoya($order_phone);
            }
            
            // 旺分销
            $saler_sess = session('saler_sess' . $this->node_id);
            $goods=array();
            if($saler_sess){
                $saler_phone = I('saler_phone');
                $shopCar = $this->getCat($payCartDate);
                foreach ($shopCar[0] as $k => $c) {
                    if (isset($c['skuId'])) {
                        $info = explode('_', $k);
                        $bId = $info[1];
                    } else {
                        $bId = $k;
                    }
                    $goods[]=$bId;
                }
                log_write($order_id."goods:".print_r($goods,true));
                $data['saler_id'] = $this->storeOrderModel->isSalersess($order_id, $goods, $order_phone, $saler_sess, $saler_phone);
            }
            $result = M('ttg_order_info')->add($data);
            if ($result) {
                // 入库订单商品
                $row = array();
                $tdata = $this->getCat($payCartDate);
                $shipdata = $this->goodsModel->_getShipType();
                foreach ($tdata[0] as $k => $c) {
                    if (isset($c['skuId'])) {
                        $info = explode('_', $k);
                        $bId = $info[1];
                    } else {
                        $bId = $k;
                    }
                    // 判断物流方式,如果没选择配送方式，则判断如果只有物流配送则默认物流配送，如果自提和物流方式都有则默认自提
                    if (empty($shipdata[$k])) {
                        
                        $where = array(
                            "e.b_id" => $bId);
                        
                        $goodsInfo = M('tecshop_goods_ex e ')->join("tbatch_info b ON b.id=e.b_id")
                            ->where($where)
                            ->find();
                        if ($goodsInfo['delivery_flag'] == '0-1' ||
                             $goodsInfo['delivery_flag'] == '0') {
                            $shipdata[$k] = '0';
                        } else {
                            $shipdata[$k] = '1';
                        }
                    }
                    // 取得上架skuId
                    $getSkuId = 0;
                    if (isset($c['skuId'])) {
                        // 实例化sku
                        $skuObj = D('Sku', 'Service');
                        $getSkuId = $skuObj->getEcshopSkuId($c['skuId']);
                    }
                    
                    $child_order_id = date('ymd') . substr(time(), - 3) .substr(mt_rand(11111, 99999)) . $bId;
                    $row['order_id'] = $order_id;
                    $row['trade_no'] = $child_order_id;
                    $row['b_id'] = $bId;
                    $row['b_name'] = $c['batch_name'];
                    $row['goods_num'] = $c['total'];
                    $row['price'] = $c['price'];
                    $row['amount'] = $c['totalPrice'];
                    $row['ecshop_sku_id'] = $getSkuId;
                    $row['ecshop_sku_desc'] = $c['sku_name'];
                    $row['receiver_type'] = $shipdata[$k];

                    $res = M('ttg_order_info_ex')->add($row);

                    if ($res) {
                        //扣减库存操作
                        if($getSkuId > 0){
                            $isSku = '1';
                        }else{
                            $isSku = '0';
                        }
                        $bInfo = $bInfoList[$bId];
                        $result = $this->storeOrderModel->orderStorageDec($bInfo, $bId, $row, $isSku);
                        if(!$result){
                            M()->rollback();
                            $this->error($this->storeOrderModel->getError());
                        }
                    } else {
                        M()->rollback();
                        $this->error("订单生成失败，订单商品入库异常！");
                    }
                }
                // 批量减红包
                if ($bonus_use_id != "") {
                    
                    $resok = $this->useBonus($bonus_use_id, $data['order_id'],$bounsAmount, $data['order_amt']); // 实际可用红包的商品总额
                    if (! $resok) {
                        M()->rollback();
                        $this->error("订单生成失败，红包扣减异常！");
                    }
                }
                // 扣除用户积分
                $retInfo = D('MemberInstall')->deductionMemberPoint($this->node_id, session('groupPhone'), $usePoint,$data['order_id']);
                if (! $retInfo) {
                    M()->rollback();
                    $this->error("订单生成失败，积分扣减异常！");
                }
            } else {
                M()->rollback();
                $this->error("订单生成失败，主订单入库异常！");
            }

            //更新用户购买信息
            $this->goodsModel->getCustomerBuyNum(session('?groupPhone'), $this->batch_id, $totalgoods);
            
            //去除购物车中购买商品信息
            foreach($goodsArray as $val){
                $key = $val['bId'];
                //拼接规格商品KEY
                if(!empty($val['skuId'])){
                    $val['skuId'] = str_replace(',', '#', $val['skuId']);
                }else{
                    $val['skuId'] = '';
                }
                $result = $this->goodsModel->deleteCart($val['bId'], $val['skuId']) ;
                if(false === $result){
                    M()->rollback();
                    $this->error("购物车信息删除失败！");
                }

            }
            M()->commit(); // 提交事务
            // $this->success("订单生成成功,订单号：".$order_id);
            //订单支付
            $this->storeOrderModel->orderToPay($order_id, $data['order_amt'], $pay_channel, $this->wx_flag, $sessionId, $this->id, $this->node_short_name);
        } else { // 如果不存在购物车数据
            $this->error(
                "购物车数据为空，请到个人中心,<a href='index.php?g=Label&m=MyOrder&a=index&node_id=" .
                     $this->node_id . "'>查看我的订单</a>");
        }
    }

    public function gotoPay() {
        $order_id = I("order_id");
        if ($order_id == "") {
            $this->error("参数错误");
        }
        $pay_channel = M('ttg_order_info')->where(
            array(
                'order_id' => $order_id))->getField('pay_channel');
        // 判断是否免支付订单 红包抵扣
        if (0 == $data['order_amt']) {
            $saleModel = D('SalePro', 'Service');
            $result = $saleModel->OrderPay($order_id, $pay_channel);
            if ($result == 'success') {
                // 油豆信息
                $sourceInfo = D('MemberInstall')->orderPay($order_id, 
                    $this->node_id, $this->id);
                A('Label/PayMent')->showMsgInfo('购买成功', 1, $this->id, $order_id, 
                    $this->node_id, $this->node_short_name);
            } else {
                A('Label/PayMent')->showMsgInfo('购买失败', 0, $this->id, $order_id, 
                    $this->node_id, $this->node_short_name);
            }
        } else {
            if ($pay_channel == '2') {
                // 去支付
                $payModel = A('Label/PayUnion');
                $payModel->OrderPay($order_id);
            } elseif ($pay_channel == '1') {
                if ($this->wx_flag == 1) {
                    // 微信中用支付宝支付则跳转到中转页面
                    redirect(
                        U('Label/PayConfirm/index', 
                            array(
                                'order_id' => $order_id, 
                                'id' => session("id"))));
                } else {
                    $payModel = A('Label/PayMent');
                    $payModel->OrderPay($order_id);
                }
            } elseif ($pay_channel == '3') {
                $payModel = A('Label/PayWeixin');
                $payModel->goAuthorize($order_id);
            } elseif ($pay_channel == '4') {
                // 货到付款
                redirect(
                    U('Label/PayDelivery/OrderPay', 
                        array(
                            'order_id' => $order_id)));
            }
            exit();
        }
    }
    
    // 查询凭证
    public function code() {
        
        // 判断是否登录
        if (! session('?groupPhone')) {
            $this->error('您还没有登录');
        }
        
        $where = array(
            "b.node_id" => $this->node_id, 
            "b.phone_no" => session('groupPhone'), 
            // "a.goods_type"=>'6',
            "b.trans_type" => '0001', 
            "b.status" => '0');
        
        $goodsList = M()->table('tbarcode_trace b ')
            ->join("tgoods_info a ON a.batch_no=b.batch_no")
            ->join("torder_trace tt ON tt.code_trace = b.request_id")
            ->join('ttg_order_info_ex t on t.order_id = tt.order_id')
            ->field(
            "b.*,a.goods_name,a.goods_image, t.ecshop_sku_id, t.ecshop_sku_desc")
            ->where($where)
            ->order("b.id desc")
            ->select();
        
        $this->assign('goodsList', $goodsList);
        $this->display();
    }

    public function code_detail() {
        
        // 判断是否登录
        if (! session('?groupPhone')) {
            $this->error('您还没有登录');
        }
        
        $code_seq = I("code_seq");
        if ($code_seq == "") {
            $this->error('参数错误');
        }
        
        $where = array(
            "b.node_id" => $this->node_id, 
            "b.phone_no" => session('groupPhone'), 
            // "a.goods_type"=>'6',
            "b.req_seq" => $code_seq);
        
        $goodsInfo = M('tbarcode_trace b ')->field(
            "a.goods_name,b.end_time,b.use_status,b.assist_number,b.mms_title,b.mms_text,b.barcode_bmp, t.ecshop_sku_id, t.ecshop_sku_desc")
            ->join("tgoods_info a ON a.batch_no=b.batch_no")
            ->join("torder_trace tt ON tt.code_trace = b.request_id")
            ->join('ttg_order_info_ex t on t.order_id = tt.order_id')
            ->where($where)
            ->find();
        
        $goodsInfo['barcode_bmp'] = $goodsInfo['barcode_bmp'] ? 'data:image/png;base64,' . base64_encode(
            $this->_bar_resize(base64_decode($goodsInfo['barcode_bmp']), 'png')) : '';
        
        // 查询热线
        $telphone = M('tnode_info')->where(
            array(
                'node_id' => $this->node_id))->getField('node_service_hotline');
        
        $user_status = array(
            "0" => "未使用", 
            "1" => "使用中", 
            "2" => "已使用");
        $this->assign('user_status', $user_status);
        $this->assign('goodsInfo', $goodsInfo);
        $this->assign('telphone', $telphone);
        $this->display();
    }

    public function logout() {
        if (session('groupPhone') != "") {
            session('groupPhone', null);
        }
        $url = "location:index.php?g=Label&m=Store&a=index&id=" . session("id");
        header($url);
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
    
    // 输出信息页面
    protected function showMsg($info, $status = 0, $id = '', $node_short_name = '') {
        $this->assign('id', $id ? $id : $this->id);
        $this->assign('status', $status);
        $this->assign('info', $info);
        $this->assign('node_short_name', $node_short_name);
        $this->display('msg');
        exit();
    }
    
    // 判断最多使用多少红包
    protected function getUseBonus($amount) {
        
        // 查询规则
        $where = array(
            "b.node_id" => $this->node_id, 
            "b.status" => '1');
        $reAmount = 0;
        $ruleList = M('tbonus_rules b ')->where($where)
            ->order('b.rev_amount desc')
            ->select();
        // 取得总规则信息
        $ruleType = D('SalePro', 'Service')->getNodeRule($this->node_id);
        switch ($ruleType) {
            case 1: // 不限制使用红包
                $reAmount = $amount;
                break;
            case 2: // 限制使用红包
                foreach ($ruleList as $k => $v) {
                    
                    if ($amount >= $v['rev_amount']) {
                        
                        $reAmount = $v['use_amount'];
                        break;
                    }
                }
                break;
            default: // 不使用红包
                $reAmount = 0;
                break;
        }
        return $reAmount;
    }
    
    // 获取用户红包数据
    protected function getUserBonus() {
        $phone = session('groupPhone');
        // 查询此用户的红包
        if ($phone == "") {
            return array();
        } else {
            
            $currentDay = date("YmdHis");
            $where = array(
                "b.node_id" => $this->node_id, 
                // "b.status"=>'1', //红包活动停止后已领红包仍可试用
                // "f.status"=>'1',
                "b.phone" => $phone);
            // $where['_string'] = "(b.bonus_num-b.bonus_use_num)>0 AND
            // i.bonus_end_time>='".$currentDay."' AND
            // f.end_time>='".$currentDay."'";
            $where['_string'] = "(b.bonus_num-b.bonus_use_num)>0 AND i.bonus_end_time>='" .
                 $currentDay . "'";
            $userBonusList = M('tbonus_use_detail b ')->field('b.*,m.amount,i.bonus_end_time')
                ->join("tbonus_detail m on m.id=b.bonus_detail_id")
                ->join("tbonus_info i on i.id=m.bonus_id")
                ->join("tmarketing_info f on f.id=b.m_id")
                ->order("f.end_time asc")
                ->where($where)
                ->select();
            
//              echo M()->getLastSql();
//              exit;
            
            return $userBonusList;
        }
    }
    
    // 判断订单可减去红包金额
    protected function orderCutBonus($bonus_use_id) {
        $phone = session('groupPhone');
        $detailArr = explode($bonus_use_id, ",");
        if (empty($detailArr)) {
            return 0;
        }
        $where = array(
            "b.id" => array(
                'in', 
                $bonus_use_id), 
            // "b.status"=>'1', //红包活动停止后已领红包仍可试用
            "b.phone" => $phone);
        $where['_string'] = " (b.bonus_num-b.bonus_use_num)>0 ";
        $bonusAmount = M('tbonus_use_detail b ')->field(
            'sum(m.amount) as amount')
            ->join("tbonus_detail m on m.id=b.bonus_detail_id")
            ->where($where)
            ->find();
        return $bonusAmount['amount'];
    }
    
    // 使用红包，更新红包使用明细
    protected function useBonus($bonus_use_id, $order_id, $cut_amt, $order_amt) {
        if ($bonus_use_id == "") {
            return false;
        }
        
        // 记录面额ID
        $detail_id = "";
        // 记录减的数量
        $use_num = 0;
        $bonusUseList = explode(",", $bonus_use_id);
        if ($bonusUseList) {
            $allBonusAmt = M()->table('tbonus_use_detail t')
                ->join('tbonus_detail d ON d.id=t.bonus_detail_id')
                ->where(
                array(
                    't.id' => array(
                        'in', 
                        $bonusUseList)))
                ->getField('sum(d.amount)');
            foreach ($bonusUseList as $item => $val) {
                
                $bonusMap = array(
                    "t.id" => $val);
                $bonusMap['_string'] = " (t.bonus_num-t.bonus_use_num)>0 ";
                
                // 查询红包数据
                $useInfo = M()->table('tbonus_use_detail t')
                    ->join('tbonus_detail d ON d.id=t.bonus_detail_id')
                    ->where($bonusMap)
                    ->field('t.*,d.amount') 
                    ->find();
                if ($useInfo) {
                    // 数量加一
                    $use_num ++;
                    $detail_id = $useInfo['bonus_detail_id'];
                    
                    $bonusData['t.bonus_use_num'] = array(
                        'exp', 
                        'bonus_use_num+1');
                    $bonusData['t.order_id'] = $order_id;
                    $bonusData['t.bonus_amount'] = $cut_amt;
                    $bonusData['t.order_amt_per'] = $order_amt *
                         ($useInfo['amount'] / $allBonusAmt);
                    $bonusData['t.use_time'] = date('YmdHis');
                    
                    $res = M()->table('tbonus_use_detail t')
                        ->where($bonusMap)
                        ->save($bonusData);
                    if ($res === false) {
                        return false;
                    }
                    
                    // 更新使用bonus_detail
                    $detailMap = array(
                        "id" => $detail_id, 
                        "node_id" => $this->node_id);
                    $detailres = M('tbonus_detail')->where($detailMap)->setInc('use_num', 1);
                    
                    if ($detailres === false) {
                        return false;
                    }
                }
            }
            return true;
        } else {
            return false;
        }
    }
    
    // 我的红包
    public function myBonus() {
        $show_wx = 0;
        $wxUserInfo = session('wxUserInfo');
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            if (! $wxUserInfo['openid']) {
                $surl = U('Label/Store/myBonus', 
                    array(
                        'id' => $this->id), '', '', true);
                redirect(
                    U('Label/O2OLogin/goAuthorize', 
                        array(
                            'id' => $this->id, 
                            'surl' => urlencode($surl))));
            } else {
                $show_wx = 1;
                // $cPhone =
                // M('to2o_wx_config')->where(array('openid'=>$wxUserInfo['openid']))->getField('phone_no');
                $cPhone = session('groupPhone');
            }
        }
        // 判断是否登录
        if (! session('?groupPhone')) {
            $this->showMsg('您还没有登录');
        }
        $Phone = session('groupPhone');
        $map['b.phone'] = $Phone;
        $map['b.node_id'] = $this->node_id;
        
        $list = M('tbonus_use_detail b')->field(
            'b.*,m.bonus_page_name,m.bonus_amount,d.amount,m.bonus_start_time,m.bonus_end_time')
            ->join('tbonus_info m on b.bonus_id=m.id')
            ->join('tbonus_detail d on d.id=b.bonus_detail_id')
            ->join("tmarketing_info f on f.id=b.m_id")
            ->where($map)
            ->order('m.bonus_end_time desc,b.id asc')
            ->select();
        $this->assign('list', $list);
        $this->assign('show_wx', $show_wx);
        $this->assign('cPhone', $cPhone);
        $this->assign('now_time', date('YmdHis'));
        $this->display();
    }

    /**
     * 个人中心
     */
    public function my() {
        
        // 判断是否登录
        if (! session('?groupPhone')) {
            $this->showMsg('您还没有登录');
        }
        
        $salerInfo = $this->_getTwxType();
        $_SESSION['twfxSalerID'] = $salerInfo['id'];
        $_SESSION['twfxRole'] = $salerInfo['role'];
        $_SESSION['node_id'] = $salerInfo['node_id'];
        if ($salerInfo['role'] == '1') {
            $salerInfo['customerUrl'] = U('Label/MyOrder/dealerCustomer');
        } elseif ($salerInfo['role'] == '2') {
            $salerInfo['customerUrl'] = U('Label/MyOrder/myCustomer');
        }
        $this->assign('salerInfo', $salerInfo);
        $this->display();
    }

    function _getTwxType() {
        $wfxSalerModel = M('TwfxSaler');
        $salerInfo = $wfxSalerModel->join(
            'twfx_node_info ON twfx_node_info.node_id = twfx_saler.node_id')
            ->where(
            array(
                'twfx_saler.node_id' => $this->node_id, 
                'twfx_saler.phone_no' => $_SESSION['groupPhone']))
            ->field(
            'twfx_saler.node_id, twfx_saler.status, twfx_saler.role, twfx_saler.level, twfx_saler.parent_path, twfx_saler.name, twfx_saler.id, twfx_node_info.customer_bind_flag')
            ->find();
        return $salerInfo;
    }

    /**
     *
     * @return array return_money_type: 设置单次现金返还类型 0-金额 1-商品价格比例（返现金时有值）
     *         return_money: 设置单次现金返还数量/比例 比例取值为 0-100 允许2位小数（返现金时有值）
     */
    public function _getShopReturn() {
        static $info = null;
        if ($info !== null) {
            return $info;
        }
        
        $map = array(
            'marketing_info_id' => $this->batch_id, 
            'return_commission_start_time' => array(
                'elt', 
                date('YmdHis')), 
            'return_commission_end_time' => array(
                'egt', 
                date('YmdHis')));
        $return_info = M('treturn_commission_info')->field('*')
            ->where($map)
            ->find();
        if (! $return_info) {
            return false;
        }
        
        $map = array(
            'return_commission_id' => $return_info['id']);
        $info = M('treturn_commission_info_ext')->where($map)
            ->field('return_money_type,return_money')
            ->find();
        $info['label_id'] = $return_info['label_id'];
        if (! $info) {
            return false;
        }
        return $info;
    }

    /*
     * 送礼给他
     */
    public function send_gift() {
        $goodsArr = I("goods", null);
        $delivery = I("g_delivery", 0);
        // if ($delivery == 1)
        // $this->error('物流方式的商品无法进行送礼');
        $skuInfo = I("sku_info");
        $b_id = $goodsArr[0];
        $skuList = '';
        // 判断是否sku商品
        $skuObj = D('Sku', 'Service');
        $skuObj->nodeId = $this->node_id;
        $isSku = $skuObj->checkIsSkuPro($b_id);
        if ('1' === $isSku) {
            // 将传输进入的，号替换为#号
            $skuListInfo = $skuObj->replaceArray($skuInfo, '&', ',', '#');
            $skuList = implode(',', $skuListInfo);
            $filter[] = "g.sku_detail_id in ('" . $skuList . "')";
            $filter[] = "t.b_id = " . $b_id;
            
            $goodsInfo = M()->table('tecshop_goods_ex t')
                ->join('tbatch_info b on b.id=t.b_id')
                ->join("tecshop_goods_sku s ON s.b_id=t.b_id")
                ->join("tgoods_sku_info g ON g.id = s.skuinfo_id")
                ->field(
                't.*,b.batch_name,b.batch_img,s.skuinfo_id,s.sale_price as batch_amt, s.storage_num as storage_num, s.remain_num as remain_num, b.batch_amt as sale_price, b.storage_num as num, b.remain_num as remain')
                ->where($filter)
                ->find();
            // 取得sku规格
            $skuInfoList = $skuObj->makeSkuOrderInfo($skuList, $b_id);
            if ($skuInfoList) {
                $goodsInfo['sku_name'] = $skuInfoList['sku_name'];
            }
        } else {
            $goodsInfo = M()->table('tecshop_goods_ex t')
                ->join('tbatch_info b on b.id=t.b_id')
                ->field(
                't.*,b.batch_name,b.batch_img,b.batch_amt,b.storage_num,b.remain_num')
                ->where(array(
                't.b_id' => $b_id))
                ->find();
        }

        if (! $goodsInfo)
            $this->error('未找到商品数据');
            
            // 判断最多可购买的份数
        if (($goodsInfo['day_buy_num'] == - 1) &&
             ($goodsInfo['person_buy_num'] == - 1)) {
            $buy_limit = 99;
        } elseif (($goodsInfo['day_buy_num'] != - 1) &&
             ($goodsInfo['person_buy_num'] == - 1)) {
            $buy_limit = min(99, $goodsInfo['day_buy_num']);
        } elseif (($goodsInfo['day_buy_num'] == - 1) &&
             ($goodsInfo['person_buy_num'] != - 1)) {
            $buy_limit = min(99, $goodsInfo['person_buy_num']);
        } elseif (($goodsInfo['day_buy_num'] != - 1) &&
             ($goodsInfo['person_buy_num'] != - 1)) {
            $buy_limit = min($goodsInfo['day_buy_num'], 
                $goodsInfo['person_buy_num']);
        } else
            $buy_limit = 99;
        
        if ($goodsInfo['storage_num'] != - 1)
            $buy_limit = min($buy_limit, $goodsInfo['remain_num']);
        ;
        // 旺分销
        $saler_sess = session('saler_sess' . $this->node_id);
        $salerInfo = $this->wfxService->get_bind_saler($this->node_id, 
            session('groupPhone'), $this->batch_id, $saler_sess['saler_id']);
        $errcode = $this->wfxService->errcode;
        
        $this->assign('salerInfo', $salerInfo);
        $this->assign('errcode', $errcode);
        $this->assign('buy_limit', $buy_limit);
        $this->assign('skuInfo', $skuInfo);
        $this->assign('goodsInfo', $goodsInfo);
        $this->assign('id', session('id'));
        $this->assign('b_id', $b_id);
        $this->display();
    }

    /*
     * 送礼订单确认
     */
    public function pay_confirm() {
        // 判断是否登录
        if (! session('?groupPhone')) {
            $this->error('您还没有登录');
        }
        $id = I('id', 0, 'intval');
        $b_id = I('b_id', 0, 'intval');
        $bless_name = I('bless_name', null);
        $bless_msg = I('bless_msg', null);
        $gift_type = I('gift_type', 0, 'intval'); // 1 微信送礼 2短信
        $gift_num = I('gift_num', 0, 'intval');
        $rece_phone = I('rece_phone', array());
        $skuInfo = I("skuInfo");
        if (! $b_id || ! $id)
            $this->error('商品参数错误');
        if ($bless_name == '')
            $this->error('您的称呼不能为空');
        if ($bless_msg == '')
            $this->error('祝福语不能为空');
        if (! in_array($gift_type, 
            array(
                1, 
                2)))
            $this->error('送礼方式类型错误');
        if ($gift_num < 1 && $gift_type == 1)
            $this->error('送礼分数错误');
        if ($gift_type == 2)
            $gift_num = count($rece_phone);
            // 把送礼配置数据存cookie
        $cookie_arr = array(
            'bless_msg' => $bless_msg, 
            'bless_name' => $bless_name, 
            'gift_type' => $gift_type, 
            'gift_num' => $gift_num, 
            'rece_p_list' => implode("|", $rece_phone));
        
        cookie('gift_cookie', $cookie_arr, 86400); // 24小时有效
                                                   // 判断是否sku商品
        $skuObj = D('Sku', 'Service');
        $skuObj->nodeId = $this->node_id;
        $isSku = $skuObj->checkIsSkuPro($b_id);
        if ('1' === $isSku) {
            // 将传输进入的，号替换为#号
            $skuListInfo = $skuObj->replaceArray($skuInfo, '&', ',', '#');
            $skuList = implode(',', $skuListInfo);
            $filter[] = "g.sku_detail_id in ('" . $skuList . "')";
            $filter[] = "t.b_id = " . $b_id;
            
            $goodsInfo = M()->table('tecshop_goods_ex t')
                ->join('tbatch_info b on b.id=t.b_id')
                ->join("tecshop_goods_sku s ON s.b_id=t.b_id")
                ->join("tgoods_sku_info g ON g.id = s.skuinfo_id")
                ->field(
                't.*,b.batch_name,b.batch_img,s.skuinfo_id,s.sale_price as batch_amt, s.storage_num as storage_num, s.remain_num as remain_num, b.batch_amt as sale_price, b.storage_num as num, b.remain_num as remain')
                ->where($filter)
                ->find();
            // 取得sku规格
            $skuInfoList = $skuObj->makeSkuOrderInfo($skuList, $b_id);
            if ($skuInfoList) {
                $goodsInfo['sku_name'] = $skuInfoList['sku_name'];
            }
        } else {
            $goodsInfo = M()->table('tecshop_goods_ex t')
                ->join('tbatch_info b on b.id=t.b_id')
                ->field(
                't.*,b.batch_name,b.batch_img,b.batch_amt,b.storage_num,b.remain_num')
                ->where(array(
                't.b_id' => $b_id))
                ->find();
        }
        
        if (! $goodsInfo)
            $this->error('未找到商品数据');
            
            // 获取可用红包金额和红包数据
            // 计算可用多少红包
        $userBonus = $this->getUseBonus($goodsInfo['batch_amt'] * $gift_num);
        
        // 可用使用的红包明细
        $userBounsList = $this->getUserBonus();
        
        // 获取可支付通道
        $hasPayChannel = 0;
        $payChannelInfo = M('tnode_account')->where(
            array(
                'node_id' => $this->node_id))->getField('account_type,status');
        foreach ($payChannelInfo as $v => $k) {
            if ($k == 1)
                $hasPayChannel = 1;
        }
        // 分销员信息
        $saler_sess = session('saler_sess' . $this->node_id);
        $sid = M('tbatch_info')->where(array(
            'id' => $b_id))->getField('m_id');
        $salerInfo = $this->wfxService->get_bind_saler($this->node_id, 
            session('groupPhone'), $sid, $saler_sess['saler_id']);
        $errcode = $this->wfxService->errcode;
        if ($errcode == 0 && ! $salerInfo) {
            $saler_phone = I('saler_phone', null);
            if ($saler_phone) {
                $salerInfo = $this->wfxService->get_saler_info_by_phone(
                    $this->node_id, $saler_phone);
            }
        }
        $this->assign('errcode', $errcode);
        $this->assign('salerInfo', $salerInfo);
        $this->assign('saler_phone', $saler_phone);
        $this->assign('hasPayChannel', $hasPayChannel);
        $this->assign('payChannelInfo', $payChannelInfo);
        $this->assign('userBonus', $userBonus);
        $this->assign('userBounsList', $userBounsList);
        $this->assign('goodsInfo', $goodsInfo);
        $this->assign('bless_name', $bless_name);
        $this->assign('skuInfo', $skuInfo);
        $this->assign('bless_msg', $bless_msg);
        $this->assign('gift_type', $gift_type);
        $this->assign('gift_num', $gift_num);
        $this->assign('rece_phone', $rece_phone);
        $this->assign('rece_phone_list', implode("|", $rece_phone));
        $this->assign('id', $id);
        $this->assign('b_id', $b_id);
        $this->display();
    }

    /*
     * 送礼支付
     */
    public function pay_gift() {
        // 判断是否登录
        if (! session('?groupPhone')) {
            $this->error('您还没有登录');
        }
        
        // 判断提交的商品数据是否存在
        $id = I('id', 0, 'intval');
        $b_id = I('b_id', 0, 'intval');
        $bless_name = I('bless_name', null);
        $bless_msg = I('bless_msg', null);
        $gift_type = I('gift_type', 0, 'intval'); // 1 微信送礼 2短信
        $gift_num = I('gift_num', 0, 'intval');
        $rece_phone = I('rece_phone', ''); // phone1|phone2|phone3
        $skuInfo = I("skuInfo");
        
        // 使用的红包
        $bonus_use_id = I("bonus_use_id");
        $memo = I("memo");
        $pay_channel = I("payment_type"); // 1 支付宝 2联动优势
        
        if (! $b_id || ! $id){
            $this->error('商品参数错误');
        }    
        if ($bless_name == ''){
            $this->error('您的称呼不能为空');
        }    
        if ($bless_msg == ''){
            $this->error('祝福语不能为空');
        }    
        if (! in_array($gift_type,[1, 2])){
            $this->error('送礼方式类型错误');
        }    
        if ($gift_num < 1){
            $this->error('送礼分数错误');
        }    
        if (empty($pay_channel)){
            $this->error('请选择支付方式');
        }    
        
        $totalAmt = 0;
        $receive_phone = session('groupPhone');
        $sessionId = session("id");
        $order_id = date('ymd') . substr(time(), - 3) . substr(microtime(), 2, 7);
        
        if (! empty($b_id)) {
            M()->startTrans(); // 起事务
            // 判断提交的商品数据是否存在(锁表操作)
            $goodsInfo = $bInfo = M('tbatch_info')->field('batch_amt, batch_no,storage_num,remain_num,node_id')
                    ->where(array('id' => $b_id))
                    ->lock(true)
                    ->find();
            //判断商品是否存在
            if(!$goodsInfo){
                    M()->rollback();
                    $this->error("当前商品信息获取失败！");
            }
            // 判断是否sku商品
            $skuObj = D('Sku', 'Service');
            $skuObj->nodeId = $this->node_id;
            $skuList = '';
            $isSku = $skuObj->checkIsSkuPro($b_id);
            if ('1' === $isSku) {
                // 将传输进入的，号替换为#号
                $skuListInfo = $skuObj->replaceArray($skuInfo, '&', ',', '#');
                $skuList = implode(',', $skuListInfo);
                $filter[] = "g.sku_detail_id in ('" . $skuList . "')";
                $filter[] = "t.b_id = " . $b_id;

                $goodsInfo = M()->table('tecshop_goods_ex t')
                    ->join('tbatch_info b on b.id=t.b_id')
                    ->join("tecshop_goods_sku s ON s.b_id=t.b_id")
                    ->join("tgoods_sku_info g ON g.id = s.skuinfo_id")
                    ->field(
                    't.*,b.batch_name,b.batch_img,s.id as skuinfo_id,s.sale_price as batch_amt, s.storage_num as storage_num, s.remain_num as remain_num, b.batch_amt as sale_price, b.storage_num as num, b.remain_num as remain')
                    ->where($filter)
                    ->find();
                // 取得sku规格
                $skuInfoList = $skuObj->makeSkuOrderInfo($skuList, $b_id);
                if ($skuInfoList) {
                    $goodsInfo['sku_name'] = $skuInfoList['sku_name'];
                }
            } 

            //检查商品配置及库存
//            $userKey = 'store_' . session('?groupPhone') . '_' . $this->batch_id . '_' . date('Ymd');
            $ret = $this->goodsModel->checkStoreage($b_id, 2, $receive_phone, $gift_num, $skuList);
            
            if (false === $ret) {
                M()->rollback();
                $this->error($this->goodsModel->getError());
            }
            $totalAmt = $goodsInfo['batch_amt'] * $gift_num;
            
            // 判断如果有可使用的红包则订单金额减去红包金额，如果红包金额小于红包可使用金额则减去金额，否则减去最大可以使用金额
            if ($bonus_use_id != "") {
                // 计算订单可减去金额
                $reAmount = $this->orderCutBonus($bonus_use_id);
                // 如果累计的红包金额满足最大的使用红包金额，则减去最大红包金额，否则减去红包金额累计
                $maxAmount = $this->getUseBonus($totalAmt);
                if ($reAmount < $maxAmount) {
                    $cutAmount = $reAmount;
                } else {
                    $cutAmount = $maxAmount;
                }
            }
            // 判断银联金额是否大于1元
            if ('2' == $pay_channel) {
                $msg = D('PayOrder', 'Service')->checkPayRule(
                    bcsub($totalAmt, $cutAmount, 2), $pay_channel, '1.00');
                if (false === $msg){
                    M()->rollback();
                    $this->error("订单生成失败，银联支付金额必须大于1元！");
                }    
            }
            $wxOpenId = '';
            // 判断是否微信订单
            if ($this->wx_flag == 1) {
                $wxUserInfo = session('merWxUserInfo');
                if (! $wxUserInfo)
                    $wxUserInfo = session('wxUserInfo');
                $wxOpenId = $wxUserInfo['openid'];
            }
            $ip = GetIP();
            // 入库主订单
            $data = array(
                'order_id' => $order_id, 
                'order_type' => '2', 
                'from_channel_id' => $sessionId, 
                'from_batch_no' => $this->batch_id, 
                'batch_no' => $this->batch_id, 
                'group_batch_no' => $goodsInfo['batch_no'], 
                'order_phone' => $receive_phone, 
                'buy_num' => $gift_num, 
                'batch_channel_id' => $sessionId, 
                'node_id' => $this->node_id, 
                'order_amt' => bcsub($totalAmt, $cutAmount, 2),
                'add_time' => date('YmdHis'), 
                'receiver_type' => '0', 
                // 'receiver_name'=>trim($receive_name),
                // 'receiver_addr'=>trim($address),
                'receiver_phone' => trim($receive_phone), 
                'pay_channel' => $pay_channel, 
                'order_status' => '0', 
                'memo' => $memo, 
                'add_time' => date('YmdHis'), 
                'freight' => 0.00, 
                'order_ip' => $ip, 
                'is_gift' => '1', 
                'bonus_use_amt' => $cutAmount,  // 新增红包使用金额在订单表中 添加人：曾成
                'openId' => $wxOpenId); // 新增微信OPENID在订单表中 添加人：曾成
                                        
            // 旺分销
            $saler_sess = session('saler_sess' . $this->node_id);
            if($saler_sess){
                $saler_phone = I('saler_phone');
                
                $data['saler_id'] = $this->storeOrderModel->isSalersess($order_id, $goods, $order_phone, $saler_sess, $saler_phone);
            }
            $result = M('ttg_order_info')->add($data);
            if ($result) {
                // 子订单表
                $row = array();
                $child_order_id = date('ymd') . substr(time(), - 3) . substr(mt_rand(11111, 99999)) . $b_id;
                $row['order_id'] = $order_id;
                $row['trade_no'] = $child_order_id;
                $row['b_id'] = $b_id;
                $row['b_name'] = $goodsInfo['batch_name'];
                $row['goods_num'] = $gift_num;
                $row['price'] = $goodsInfo['batch_amt'];
                $row['amount'] = $goodsInfo['batch_amt'] * $gift_num;
                $row['ecshop_sku_id'] = isset($goodsInfo['skuinfo_id']) ? $goodsInfo['skuinfo_id'] : 0;
                $row['ecshop_sku_desc'] = isset($goodsInfo['sku_name']) ? $goodsInfo['sku_name'] : '';
                $row['receiver_type'] = '0';
                $res = M('ttg_order_info_ex')->add($row);
                if ($res) {
                    // 批量减红包
                    if ($bonus_use_id != "") {
                        $resok = $this->useBonus($bonus_use_id, $order_id, 
                            $cutAmount, $data['order_amt']);
                        if (! $resok) {
                            M()->rollback();
                            $this->error("订单生成失败，红包扣减异常！");
                        }
                    }
                    // 判断是否减库存
                    $result = $this->storeOrderModel->orderStorageDec($bInfo, $b_id, $row, $isSku);
                    if(!$result){
                        M()->rollback();
                        $this->error($this->storeOrderModel->getError());
                    }
                    // 新增送礼表 ttg_order_gift
                    $gift_arr = array(
                        'order_id' => $order_id, 
                        'bless_msg' => $bless_msg, 
                        'bless_name' => $bless_name, 
                        'rece_phone_list' => $rece_phone, 
                        'gift_type' => $gift_type);
                    $res = M('ttg_order_gift')->add($gift_arr);
                    if ($res === false) {
                        M()->rollback();
                        $this->error("订单生成失败，送礼订单配置异常！");
                    }
                } else {
                    M()->rollback();
                    $this->error("订单生成失败，订单商品入库异常！");
                }
            } else {
                M()->rollback();
                $this->error("订单生成失败，主订单入库异常！");
            }
            M()->commit(); // 提交事务
            
            //更新用户购买信息
            //$this->goodsModel->getCustomerBuyNum(session('?groupPhone'), $this->batch_id, $gift_num);
            
            cookie('gift_cookie', null); // 去掉缓存cookie
            //订单支付
            $this->storeOrderModel->orderToPay($order_id, $data['order_amt'], $pay_channel, $this->wx_flag, $sessionId, $this->id, $this->node_short_name);
        } else {
            $this->error(
                "商品数据为空，请到个人中心,<a href='index.php?g=Label&m=MyOrder&a=index&node_id=" .
                     $this->node_id . "'>查看我的订单</a>");
        }
    }

    public function showOrderInfo() {
        // 判断是否登录
        if (! session('?groupPhone')) {
            $this->showMsg('您还没有登录');
        }
        // 标签
        $model = M('tbatch_channel');
        $map = array(
            'id' => session("id"), 
            'batch_type' => $this->batch_type);
        $result = $model->where($map)->find();
        if (! $result)
            $this->error('错误的参数！', 0, $id, $this->node_short_name);
        $order_id = I('order_id', null);
        if (! $order_id) {
            $this->error("参数错误");
        }
        $where = array(
            'o.order_id' => $order_id);
        
        $orderInfo = M('ttg_order_info')->where(
            array(
                'order_id' => $order_id))->find();
        $orderListInfo = M()->table('ttg_order_info_ex e')
            ->join('tbatch_info b on b.id=e.b_id')
            ->field('e.*,b.batch_img,b.batch_amt')
            ->where(array(
            'e.order_id' => $order_id))
            ->select();
        // 送礼数据
        $giftInfo = M('ttg_order_gift')->where(
            array(
                'order_id' => $order_id))->find();
        // 领取数据
        $codeTrace = M('torder_trace')->where(
            array(
                'order_id' => $order_id))->select();
        $hav_count = count($codeTrace);
        // 红包数据
        $bonusInfo = M('tbonus_use_detail')->where(
            array(
                'order_id' => $order_id))->getField('bonus_amount');
        if (! $bonusInfo)
            $bonusInfo = 0;
        $status = array(
            '1' => '未支付', 
            '2' => '已支付');
        $deliveryStatus = array(
            '1' => '待配送', 
            '2' => '配送中', 
            '3' => '已配送', 
            '4' => '凭证自提');
        $this->assign('orderInfo', $orderInfo);
        $this->assign('orderListInfo', $orderListInfo);
        $this->assign('status', $status);
        $this->assign('deliveryStatus', $deliveryStatus);
        $this->assign('node_short_name', $this->node_short_name);
        $this->assign('codeTrace', $codeTrace);
        $this->assign('giftInfo', $giftInfo);
        $this->assign('bonusInfo', $bonusInfo);
        $this->assign('hav_count', $hav_count);
        $this->assign('id', session('id'));
        $this->display();
    }
    
    // 发送更换绑定手机号的验证码
    public function sendChangeCode() {
        
        // 图片校验码
        /*
         * $verify = I('post.verify',null,'mysql_real_escape_string');
         * if(session('verify') != md5($verify)) { $this->error("图片验证码错误"); }
         */
        $phoneNo = I('post.change_phone', null);
        if (! check_str($phoneNo, 
            array(
                'null' => false, 
                'strtype' => 'mobile'), $error)) {
            $this->error("换绑手机号{$error}");
        }
        // 测试环境不下发，验证码直接为1111
        if (! is_production()) {
            $groupChangeCode = array(
                'number' => 1111, 
                'add_time' => time(), 
                'phoneNo' => $phoneNo);
            session('groupChangeCode', $groupChangeCode);
            $this->ajaxReturn("success", "验证码已发送", 1);
        }
        // 发送频率验证
        $groupChangeCode = session('groupChangeCode');
        if (! empty($groupChangeCode) &&
             (time() - $groupChangeCode['add_time']) < $this->expiresTime) {
            $this->error('动态密码发送过于频繁!');
        }
        $num = mt_rand(1000, 9999);
        $exptime = $this->CodeexpiresTime / 60;
        $text = "您在{$this->node_short_name}商户的动态密码是：{$num}，有效期{$exptime}分钟。如非本人操作请忽略。";
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
        $ret_msg = $resp_array['NotifyRes']['Status'];
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
             $ret_msg['StatusCode'] != '0001')) {
            $this->error('发送失败');
        }
        $groupChangeCode = array(
            'number' => $num, 
            'add_time' => time(), 
            'phoneNo' => $phoneNo);
        session('groupChangeCode', $groupChangeCode);
        $this->success('动态密码已发送');
    }

    public function updateChangePhone() {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') === false) {
            $this->error('请在微信中打开');
        }
        $change_phone = I('change_phone', null, 'mysql_real_escape_string');
        if (! check_str($change_phone, 
            array(
                'null' => false, 
                'strtype' => 'mobile'), $error)) {
            $this->ajaxReturn(array(
                'type' => 'phone'), "换绑手机号{$error}", 0);
        }
        // 手机验证码
        $checkCode = I('check_code', null);
        if (! check_str($checkCode, array(
            'null' => false), $error)) {
            $this->ajaxReturn(array(
                'type' => 'pass'), "动态密码{$error}", 0);
        }
        $groupChangeCode = session('groupChangeCode');
        if (! empty($groupChangeCode) &&
             $groupChangeCode['phoneNo'] != $change_phone)
            $this->ajaxReturn(array(
                'type' => 'phone'), '手机号码不正确', 0);
        if (! empty($groupChangeCode) && $groupChangeCode['number'] != $checkCode)
            $this->ajaxReturn(array(
                'type' => 'pass'), '动态密码不正确', 0);
        if (time() - $groupChangeCode['add_time'] > $this->CodeexpiresTime)
            $this->ajaxReturn(array(
                'type' => 'pass'), '动态密码已经过期', 0);
            // 更换数据库
        $wxUserInfo = session('wxUserInfo');
        $ret = M('to2o_wx_config')->where(
            array(
                'openid' => $wxUserInfo['openid']))->save(
            array(
                'phone_no' => $change_phone));
        if ($ret === false)
            $this->ajaxReturn(array(
                'type' => 'pass'), "换绑手机号更新失败", 0);
            // 记录session
        session('groupPhone', $change_phone);
        
        $this->success('登录成功');
    }

    /*
     * 取消订单 订单状态，回滚库存，取消红包
     */
    public function ordercancel() {
        $order_id = I("order_id");
        if ($order_id == "") {
            $this->showMsg("参数错误");
        }
        $orderInfo = M('ttg_order_info')->where(
            array(
                'order_id' => $order_id))->find();
        if (! $orderInfo)
            $this->showMsg("订单数据错误");
        if ($orderInfo['order_status'] != '0')
            $this->showMsg("订单状态有误，无法取消订单！");
        if ($orderInfo['pay_status'] != '1')
            $this->showMsg("订单支付状态有误，无法取消订单！");
            
            // 开启事务
        M()->startTrans();
        // 更新订单状态
        $result = M('ttg_order_info')->where(
            array(
                'order_id' => $order_id))->save(
            array(
                'order_status' => '2', 
                'update_time' => date('YmdHis')));
        if ($result === false) {
            M()->rollback();
            $this->showMsg("订单状态更新失败");
        }
        
        // 更新库存
        if ($orderInfo['order_type'] == '2') { // 小店订单
            $exorderList = M('ttg_order_info_ex')->where(
                array(
                    'order_id' => $order_id))->select();
            if (! $exorderList) {
                M()->rollback();
                $this->showMsg("子订单数据错误");
            }
            foreach ($exorderList as $v) {
                $result = M('tbatch_info')->where(
                    array(
                        'id' => $v['b_id'], 
                        'storage_num' => array(
                            'neq', 
                            '-1')))->setInc('remain_num', $v['goods_num']);
                // if($result === false){
                // M()->rollback();
                // $this->showMsg("小店订单商品库存回滚失败");
                // }
            }
        } elseif ($orderInfo['order_type'] == '0') { // 单品订单
            $result = M('tbatch_info')->where(
                array(
                    'm_id' => $orderInfo['batch_no'], 
                    'storage_num' => array(
                        'neq', 
                        '-1')))->setInc('remain_num', $orderInfo['buy_num']);
            // if($result === false){
            // M()->rollback();
            // $this->showMsg("单品订单商品库存回滚失败");
            // }
        }
        
        // 更新红包
        $bonusInfo = M('tbonus_use_detail')->where(
            array(
                'order_id' => $order_id))->select();
        if ($bonusInfo) {
            foreach ($bonusInfo as $v) {
                $result = M('tbonus_detail')->where(
                    array(
                        'id' => $v['bonus_detail_id'], 
                        'node_id' => $this->node_id))->setDec('use_num', 
                    $v['bonus_use_num']);
                if ($result === false) {
                    M()->rollback();
                    $this->showMsg("红包使用数量统计回滚失败");
                }
            }
            // 统一更新他bonus_use_detail
            $result = M('tbonus_use_detail')->where(
                array(
                    'order_id' => $order_id))->save(
                array(
                    'order_id' => '', 
                    'bonus_use_num' => 0, 
                    'bonus_amount' => 0, 
                    'use_time' => ''));
            if ($result === false) {
                M()->rollback();
                $this->showMsg("红包使用明细回滚失败");
            }
        }
        M()->commit();
        $this->showMsg('订单取消成功', 1, $this->id, $this->node_short_name);
        exit();
    }
    
    // 购买者领取剩下的所有礼品
    public function getLeftGift() {
        $order_id = I('order_id');
        $orderInfo = M('ttg_order_info')->where(
            array(
                'order_id' => $order_id))->find();
        if ($orderInfo['pay_status'] != '2')
            $this->error('订单尚未支付');
        if ($orderInfo['is_gift'] != '1')
            $this->error('订单为非送礼类订单');
            // 送礼类型
        $giftInfo = M('ttg_order_gift')->where(
            array(
                'order_id' => $order_id))->find();
        if ($giftInfo['gift_type'] != '1')
            $this->error('订单为非微信送礼类订单，无法领取剩余礼品');
            // 已领
        $count = M('torder_trace')->where(
            array(
                'order_id' => $order_id))->count();
        if ($count >= $orderInfo['buy_num'])
            $this->error('订单已被全部领取，无法领取剩余礼品');
            
            // 处理发码
        $sendCount = $orderInfo['buy_num'] - $count;
        if ($sendCount < 1)
            $this->error('订单已无剩余礼品');
        $channelId = M('tbatch_channel')->where(
            array(
                'id' => $orderInfo['batch_channel_id']))->getField('channel_id');
        if ($orderInfo['order_type'] == '2') {
            $ecgoodsInfo = M()->table('ttg_order_info_ex g')
                ->field('g.*,b.batch_no,b.m_id')
                ->join('tbatch_info b ON b.id=g.b_id')
                ->where(array(
                'g.order_id' => $order_id))
                ->find();
            $mId = $ecgoodsInfo['m_id'];
            $bId = $ecgoodsInfo['b_id'];
            $issBatchNo = $ecgoodsInfo['batch_no'];
        } elseif ($orderInfo['order_type'] == '0') {
            $batchInfo = M('tbatch_info')->where(
                array(
                    'm_id' => $orderInfo['batch_no']))->find();
            $mId = $orderInfo['batch_no'];
            $bId = $batchInfo['id'];
            $issBatchNo = $batchInfo['batch_no'];
        }
        for ($i = 1; $i <= $sendCount; $i ++) {
            $ret = $this->sendCode2($orderInfo['order_id'], 
                $orderInfo['order_type'], $orderInfo['node_id'], $issBatchNo, 
                $orderInfo['receiver_phone'], $bId, $mId, $channelId, 
                $orderInfo['receiver_phone']);
        }
        $this->success('领取成功');
    }
    
    // 发码处理
    protected function sendCode2($orderId, $orderType, $nodeId, $issBatchNo, 
        $phone, $bId, $mId, $channelId, $gift_phone = null) {
        // 发码
        // $transId = date('YmdHis').sprintf('%04s',mt_rand(0,1000));
        $transId = get_request_id();
        import("@.Vendor.SendCode");
        $req = new SendCode();
        $res = $req->wc_send($nodeId, '', $issBatchNo, $phone, '8', $transId, 
            '', $bId, $channelId);
        if ($res == true) {
            $gift_type = 0;
            if ($gift_phone)
                $gift_type = 2;
                // 将code_trace存入ex表
            $result = M('ttg_order_info_ex')->where(
                array(
                    'order_id' => $orderId, 
                    'b_id' => $bId))->save(
                array(
                    'code_trace' => $transId));
            if ($result === false) {
                log_write(
                    "订单发码成功,更新订单关联表失败;order_id:{$orderInfo['order_id']},send_seq:{$transId}");
            }
            $result = M('torder_trace')->add(
                array(
                    'order_id' => $orderId, 
                    'm_id' => $mId, 
                    'b_id' => $bId, 
                    'code_trace' => $transId, 
                    'gift_phone' => $gift_phone, 
                    'gift_type' => $gift_type, 
                    'transt_time' => date('YmdHis')));
            if ($result === false) {
                log_write(
                    "订单发码成功,更新send_seq失败;order_id:{$orderId},send_seq:{$transId}");
            }
        } else {
            log_write("订单发码失败,原因:{$res}");
        }
    }
    
    // 分销员判断
    public function checkSaler() {
        $saler_phone = I('saler_phone', null);
        if (! $saler_phone)
            $this->error("分销员手机号不存在，<br />请检查分销员手机号");
        
        $salerInfo = $this->wfxService->get_saler_info_by_phone($this->node_id, 
            $saler_phone);
        if (! $salerInfo)
            $this->error("分销员不存在，<br />请检查分销员");
        else
            $this->success("分销员存在，<br />可继续购买");
    }
    
    // 根据城市选择返回运费
    public function getCityFee() {
        $provinceCode = I('post.province', null);
        $cityCode = I('post.city', null);
        $cityFee = D('CityExpressShipping')->getCityFee($this->cityExpress, 
            $cityCode, $this->cityFreight);
        echo $cityFee;
    }

    /**
     * *************爱蒂宝START****************
     */
    private $is_adb;
    private $adb_first_login;
    private $adb_store_id;

    /**
     * 初始化 主要处理公用数据
     *
     * @return null
     */
    
    private function adbInit() {
        $config = C('adb');
        if ($config['node_id'] != $this->node_id)
            return true;
        $this->is_adb = true;
        // 微信登入
        $weixin_info = $this->adbWeixinAuthorize($this->node_id, 0);

        if(!session("?wxUserInfo")){
            session('wxUserInfo',$weixin_info);
        }
        if(!session("?merWxUserInfo")){
            session('merWxUserInfo',$weixin_info);
        }
        $wx_openid = $weixin_info['openid'];
        //小店ID
        if(strtolower(ACTION_NAME) == 'detail'){
            $id=M()->table("tbatch_channel bc")
                 ->join('tchannel c on c.id=bc.channel_id')
                 ->join('tmarketing_info t on t.id = bc.batch_id')
                 ->where(array(
                    'bc.node_id'=>$this->node_id,
                    'bc.batch_type'=>29,
                    'c.type'=>4,
                    'c.sns_type'=>46,
                    't.node_id'=>$this->node_id,
                    't.batch_type'=>29,
                    ))
                 ->getField('bc.id');
        }else{
            $id=$this->id;
        }
        session("id", $id);    

        // 记录使用中的openid
        $this->wxSess['adb_user_openid'] = $wx_openid;
        // 检查关注状态
        $where = array(
            'u.openid' => $wx_openid, 
            'u.node_id' => $this->node_id, 
            'u.subscribe' => array(
                'neq', 
                '0'));
        $check_sub = M('twx_user')->alias('u')
                                  ->join('tweixin_info n on n.node_id=u.node_id and n.node_wx_id=u.node_wx_id')
                                  ->where($where)->count();
        //获取绑定门店ID
        $where=array(
            'openid'=>$wx_openid,
            );
        $bind_info=M('tfb_adb_user_store')->where($where)->find();
        $store_id=$bind_info['store_id'];
        if(is_null($store_id)){
            $data=array(
                'openid'=>$wx_openid,
                'store_id'=>0,
                );
            //自动绑定
            M('tfb_adb_user_store')->add($data);
            $store_id=0;
        }

        //第一次进入则默认绑定门店
        if($bind_info['first'] == 1){
            M('tfb_adb_user_store')->where($where)->setField('first',0);
            session('adb_store_id',$store_id);
        }

        $change=I('get.change');
        //转换到总部商城
        if($change == 1){
            session('adb_store_id',0);
        }elseif($change == 2){
            D('Adb','Service')->cleanCarts();
            session('adb_store_id',$store_id);
        }else{
            $temp_id=session('adb_store_id');
            if(!is_numeric($temp_id)){
                session('adb_store_id',$store_id);
            }
        }

        $this->adb_store_id=session('adb_store_id');
        //当处于分店时。校验门店的可用性，不可用是转换总店
        if($this->adb_store_id){
            $s_where=array(
                's.node_id'=>$this->node_id,
                'p.status'=>1,
                's.store_id'=>$store_id,
                );
            $store_info=M('tstore_info')->alias('s')
                                        ->field('s.store_id,s.store_name')
                                        ->join('tfb_adb_store_page sp on sp.store_id=s.store_id')
                                        ->join("tecshop_page_sort p on p.id=sp.page_id")
                                        ->where($s_where)->find();

            if(empty($store_info)){
                session('adb_store_id',0);
                $this->error("您所绑定的门店店铺暂时停止服务<br><a href='".U('Label/Label/index',array('id'=>$this->id,'change'=>1),'','',true)."'>返回总部门店</a>");
            }
        }else{
            $where = array(
            'ps.status' => 1, 
            "s.node_id" => $this->node_id, 
            "ps.node_id" => $this->node_id);
            $list = M()->table("tfb_adb_store_page ap")->field(
                's.province_code,s.store_id,s.store_name')
                ->join('tstore_info s on ap.store_id=s.store_id')
                ->join("tecshop_page_sort ps on ps.id = ap.page_id")
                ->where($where)
                ->select();
            
            // 地区
            $model = M('tcity_code');
            $province_code = array();
            $area_list = array();
            if (! empty($list)) {
                foreach ($list as $row) {
                    $province_code[] = $row['province_code'];
                }
                $province_code = array_unique($province_code);
                $sort_list = $model->field('province_code id,province name')
                    ->where(
                    array(
                        'city_level' => 1, 
                        "province_code" => array(
                            "in", 
                            $province_code)))
                    ->order('province_code')
                    ->select();
                if($sort_list){
                    $sort=$config['city_sort'];
                    $sort_num=count($sort);
                    
                    foreach($sort_list as $row){
                        if($sort[$row['id']]){
                            $area_list[$sort[$row['id']]["sort"]]=$row;
                        }else{
                            $sort_num++;
                            $area_list[$sort_num]=$row;
                        }
                    } 
                    ksort($area_list);
                    $area_list=array_values($area_list);
                }
            }
            $this->assign("adb_area_list", $area_list);
            $this->assign('adb_store_list', $list);
        }
        $this->assign('adb_store_info',$store_info);
        // 是否关注
        $this->assign('adb_is_sub', (bool) $check_sub);
        // 标识
        $this->assign('is_adb', true);
        // 是否绑定门店
        $this->assign('adb_store_id', $store_id);
        $this->assign('adb_cur_store_id',$this->adb_store_id);
        // 关注地址二微码图片路径
        $this->assign("adb_attention_href", $config['attention_href']);
        //搜索
        $this->adbSerach();
        // 总/分店切换
        $this->adbShowStore();
        //检查订单
        $this->adbChackOrder();
    }

    /**
     * 爱蒂宝分店商品
     *
     * @return [type] [description]
     */
    private function adbGoodsInfo() {
        $store_id = $this->adb_store_id;
        if (! $this->is_adb || $store_id == 0)
            return true;
        $check = M()->table("tfb_adb_goods_store g")->join(
            'tfb_adb_store_page s on s.store_id=g.store_id')
            ->join("tbatch_info i on i.id=g.b_id")
            ->join("tecshop_page_sort p on p.id=s.page_id")
            ->where(
             array(
                'i.m_id'=>$this->batch_id,
                'p.status' => 1, 
                'g.store_id' => $store_id))
            ->count();

        if (! $check) {
            $this->error(
                array(
                    'errorImg' => '__PUBLIC__/Label/Image/waperro5.png', 
                    'errorTxt' => '错误访问！', 
                    'errorSoftTxt' => '您所在门店暂无此商品'));
        }
    }

    /**
     * 爱蒂宝订单处理
     *
     * @param [type] $order_id [description]
     * @return [type] [description]
     */
    private function adbOrder($order_id) {
        if (! $this->is_adb || empty($order_id))
            return true;
        $info = I('post.');
        M()->StartTrans();

        $store_id = $info['adb_store_id']; // ?$info['adb_store_id']:0;
        $type = array(
            1 => "10:00到12:00", 
            "12:00到14:00", 
            "14:00到16:00", 
            "16:00到18:00", 
            "18:00到20:00",
            "10:00到11:00",
            "11:00到12:00",
            "12:00到13:00",
            "13:00到14:00",
            "14:00到15:00",
            "15:00到16:00",
            "16:00到17:00",
            "17:00到18:00",
            "18:00到19:00",
            "19:00到20:00",
            ); // 2016/01/05 18:00到20:00

        $data = array(
            'order_id' => $order_id, 
            'deliver_store_id' => $store_id, 
            'self_store_id'  => 0,
            'order_store_id' =>  $this->adb_store_id);

       if($info['adb_self_order'] == 2 || $info['adb_self_order'] == 3){

            if(empty($info['contacts'])){
                $this->error("请输入自提联系人");
            }
            if(mb_strlen($info['contacts'],'utf-8') > 10){
                $this->error("自提联系人名称过长");
            }

            if(!in_array($info['adb_self_date_time'], range(6, 15))){
                 $this->error("请选择自提时段");
            }      
            if(empty($info['adb_self_store_id'])){
                $this->error("请选择自提门店");
            }
            $data['self_store_id']=$info['adb_self_store_id'];
            $data['contacts']=trim($info['contacts']);
            $data['deliver_period2']=$info['adb_self_date'] . " " . $type[$info['adb_self_date_time']];
        }
        
        if($info['adb_self_order'] == 1 || $info['adb_self_order'] == 3){
            if(!in_array($info['adb_delivery_date_time'], range(1, 5))){
                 $this->error("请选择配送时段");
            }

            if (empty($info['adb_store_id'])) {
                $this->error("请选择配送门店");
            }
            $data['deliver_period']=$info['adb_delivery_date'] . " " . $type[$info['adb_delivery_date_time']];
        }
        
      
        $res = M('tfb_adb_ecshop_order')->add($data);
        
        $log = "爱蒂宝：添加订单\n" . var_export($data,true) . "\n结果：" . $res . "\nSQL:" .
             M('tfb_adb_ecshop_order')->_sql();
        log_write($log);
        if ($res == false) {
            M()->rollback();
            $this->error("生成订单失败");
        }
        return true;
    }

    /**
     * 爱蒂宝搜索页
     * @return [type] [description]
     */
    private function adbSerach(){
        $keyword=I('keyword');
        $store_id=$this->adb_store_id;
        if(!$this->is_adb || empty($keyword) || $store_id == 1){
            return true;
        }
        $this->catview();
        exit;
    }

    /**
     * 爱蒂宝分店查询条件
     *
     * @return [type] [description]
     */
    private function adbCatView($nodeWhere) {
        $store_id = $this->adb_store_id;
        if (! $this->is_adb || $store_id == 0)
            return $nodeWhere;
        $where = " exists (select * from tfb_adb_goods_store gs where gs.b_id = e.b_id and gs.store_id = '" .
             $store_id . "')";
        $where .= " and exists (select * from tfb_adb_store_page adbsp join tecshop_page_sort adbp on adbsp.page_id = adbp.id where adbp.status = '1' and adbsp.store_id = '" .
             $store_id . "')";
        if (empty($nodeWhere)) {
            $nodeWhere = $where;
        } else {
            $nodeWhere .= " and" . $where;
        }
       
        return $nodeWhere;
    }

    /**
     * 爱蒂宝检查订单
     * @return [type] [description]
     */
    private function adbChackOrder() {
        if(!$this-> is_adb || !in_array(ACTION_NAME,array('cart_check','direct_to_check')) ){
            return true;
        }
        if(strtolower(ACTION_NAME) == 'direct_to_check'){
            $delivery = I("delivery");
            $type=empty($delivery)?2:1;
            $this->assign('adb_self_order',$type);
            return true;
        }
        $shipdata = $this->goodsModel->_getShipType();
        $data = $this->getCat();
        
        if (!empty($data[0])) {
            $ship_num=0;
            $self_num=0;
            foreach ($data[0] as $k => $val) {
                if($shipdata[$k] == '1'){
                    $ship_num++;
                }else{
                    $self_num++;
                }
            }
            //混合
            if($ship_num > 0 && $self_num > 0){
                $type=3;
            }elseif($ship_num == 0){
            //自提
                $type=2;
            }else{
            //物流
                $type=1;
            }
            $this->assign('adb_self_order',$type);
        }
        return true;
    }

    /**
     * 显示所属门店
     *
     * @return [type] [description]
     */
    private function adbShowStore() {
        if (! $this->is_adb)
            return true;
              
        $this->adbShowTitle();
        if (in_array(ACTION_NAME, 
            array(
                'index', 
                'adbIndex'))) {
            $store_id = $this->adb_store_id;

            if ($store_id == 0 ) {
                $is_index = true;
                $page_id = false;
            } else {
                $is_index = false;
                $where = array(
                    'ps.status' => 1, 
                    's.status'  => 0,
                    "ap.store_id" => $store_id, 
                    "ps.node_id" => $this->node_id);
                $page_id = M()->table("tfb_adb_store_page ap")->join(
                    "tecshop_page_sort ps on ps.id = ap.page_id")
                    ->join("tstore_info s on ap.store_id = s.store_id")
                    ->where($where)
                    ->getField('ps.id');
            }

            // 访问的门店
            $action = "index";

            if ($page_id) {
                $_GET['page_id'] = $page_id;
                $this->assign("adb_is_page", 1);
                $action = "adbIndex";
            }

            $this->$action();
            exit();
        }
    }

    /**
     * 爱蒂宝显示所属门店title
     * @return [type] [description]
     */
    private function adbShowTitle(){
        if(!$this->is_adb){
            return true;
        }
        $store_id=$this->adb_store_id;
        if($store_id == 0){
            $name = M('tmarketing_info')
            ->where(
            array(
                'node_id' => $this->node_id, 
                'batch_type' => self::BATCH_TYPE_STORE))
            ->order("id desc")
            ->getField('name');
        }else{
             $where = array(
                    'ps.status' => 1, 
                    's.status'  => 0,
                    "ap.store_id" => $this->adb_store_id, 
                    "ps.node_id" => $this->node_id);
            $name = M()->table("tfb_adb_store_page ap")->join(
                    "tecshop_page_sort ps on ps.id = ap.page_id")
                    ->join("tstore_info s on ap.store_id = s.store_id")
                    ->where($where)
                    ->getField('ps.page_name');
        }

        session('login_title',$name);
        session('title',$name);
        $batch_info['name']=$name;
        $this->assign('batch_info',$batch_info);
         return true;
    }

    /**
     * 门店分店
     * @param  [type] $page_id [description]
     * @return [type]          [description]
     */
    public function adbIndex($page_id) {
        $page_id=I('page_id');
        if ($page_id == "" || !$this->is_adb) {
            $this->error("参数错误");
        }
        $wechatService = D('WeiXin', 'Service');
        $wechatConfig = array();
        $wechatConfig['app_id'] = C('WEIXIN.appid');
        $wechatConfig['app_secret'] = C('WEIXIN.secret');
        $shareConfig = $wechatService->getWxShareConfig('', 
        $wechatConfig['app_id'], $wechatConfig['app_secret']);
        $this->assign('shareData', $shareConfig);
        $catWhere = array(
            'node_id' => $this->node_id, 
            'page_type' => '5', 
            'id' => $page_id);
        $pageInfo = M('tecshop_page_sort')->where($catWhere)->find();
        
        $share_pic = get_upload_url($pageInfo['share_pic']);
        $currentUrl = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        if($_GET['adbtest']==2){
          $this->assign('adbtest',1);
        }
        // 即时更新商品信息
        $changeContentObject = D('Adb', 'Service');
        $pageInfo['page_content'] = $changeContentObject->changePageContent($pageInfo['page_content'], $this->node_id,session('adb_store_id'));
        $this->assign('pageInfo', $pageInfo);
        $this->assign('currentUrl', $currentUrl);
        $this->assign('share_pic', $share_pic);
        $this->display("adbindex");
    }

    /**
     * 绑定门店
     *
     * @return [type] [description]
     */
    public function adbSaveStore() {
        if (! $this->is_adb || ! IS_POST)
            redirect(U('Label/Store/index', I('get.'), '', '', true));
        $store_id = I('post.store_id');
        if (empty($store_id)) {
            $this->error("请选择要绑定的门店");
        }
        
        $where = array(
            's.store_id' => $store_id, 
            'p.status' => 1, 
            's.status' => 0,
            "s.node_id" => $this->node_id);
        
        $check = M()->table("tstore_info s")->join(
            "tfb_adb_store_page a on a.store_id = s.store_id")
            ->join("tecshop_page_sort p on a.page_id = p.id")
            ->where($where)
            ->count();
  
        
        if (! $check) {
            $this->error("门店不存在");
        }
        $openid = $this->wxSess['adb_user_openid'];
        $model = M('tfb_adb_user_store');
        $info = $model->field('id,store_id')
            ->where(array(
            'openid' => $openid))
            ->find();
        $id = $info['id'];
        if ($id) {
            if ($info['store_id'] == $store_id) {
                $res = true;
            } else {
                // 更新
                $res = $model->where(
                    array(
                        'id' => $id))->save(
                    array(
                        'store_id' => $store_id));
            }
        } else {
            // 新添
            $res = $model->add(
                array(
                    'openid' => $openid, 
                    'store_id' => $store_id));
        }
        if ($res == false) {
            $this->error("绑定门店失败");
        }
        session($this->goodsModel->session_cart_name, null);
        session($this->goodsModel->session_ship_name, null);
        $this->success("绑定门店成功", 
            U('Label/Store/adbIndex', 
                array(
                    'id' => session('id'))));
    }

    /**
     * 微信授权
     *
     * @param [type] $nodeId [description]
     * @param integer $type [description]
     * @return [type] [description]
     */
    private function adbWeixinAuthorize($nodeId, $type = 1) {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') === false) {
            $this->error('请在微信中打开');
        }

        if (empty($nodeId)) {
            $this->error('参数错误！');
        }
        $type = $type == 1 ? 1 : 0;
        $weixin_info = session("node_wxid_" . $this->node_id);
        if (empty($weixin_info) || empty($weixin_info['openid'])) {
            $is_back = I('get.weixinback');
            if (empty($this->WeiXinService)) {
                $this->WeiXinService = D('WeiXin', 'Service');
            }
            if ($is_back == 1) { // 授权
                log_write(print_r(I('get.'), true));
                $code = I('code', null);
                if (empty($code)) {
                    $this->error('参数错误！');
                }
                $result = $this->WeiXinService->getWeixinInfoByNodeId($nodeId);
                $result = $this->WeiXinService->weixinCallbackByParam($code,$result);
                log_write("爱蒂宝授权数据：".print_r($result, true));

                if ($result['errmsg']) {
                    $this->error($result['errmsg']);
                }
                session('node_wxid_' . $this->node_id, $result);
                $weixin_info = $result;
            } else { // 跳转到微信
                $get = I('get.');
                $get['weixinback'] = 1;
                $backurl = U('', $get, '', '', true);
                $result = $this->WeiXinService->buildWechatAuthorizeParamByNodeId(
                    $nodeId, 
                    array(
                        'type' => $type, 
                        "apiCallbackUrl" => $backurl));
                log_write("爱蒂宝授权地址：".print_r($result, true));
                if ($result['errmsg']) {
                    $this->error($result['errmsg']);
                }
                $result = $this->WeiXinService->processWechatAuthorize($result);
                if ($result['errmsg']) {
                    $this->error($result['errmsg']);
                }
            }
        }
        return $weixin_info;
    }


/**
 * *************爱蒂宝END****************
 */

    //和包支付
    public function cmOrderSave() {        
        // 判断提交的商品数据是否存在
        $goods = I("goods");
        if(!session('?groupPhone')){
            $this->error('购买信息有误');
        }
        $goodsInfo = M('tbatch_info')->where(array('id' => $goods))->find();
        if(!$goodsInfo){
            $this->error('商品不存在');
        }
        $price = I("price");
        $total = (int)I("number");
        
        if (empty($goods) || empty($price) || $total < 1) {
            $this->error('参数错误');
        }
        //非标和包
        $payChannel = '6';     //和包支付通道
        $orderId = date('ymd') . substr(time(), - 3) . substr(microtime(), 2, 7);
        if (! empty($goods)) {
            M()->startTrans(); // 起事务
            $ip = GetIP();
            // 入库主订单
            $data = array(
                'order_id' => $orderId, 
                'order_type' => '2', 
                'from_channel_id' => $this->id, 
                'from_batch_no' => $this->batch_id, 
                'batch_no' => $this->batch_id, 
                'order_phone' => session('groupPhone'), 
                'buy_num' => $total, 
                'batch_channel_id' => $this->id, 
                'node_id' => $goodsInfo['node_id'], 
                'order_amt' => $total * $price, 
                'add_time' => date('YmdHis'), 
                'receiver_type' => '0',    //和包默认电子卷商品
                'receiver_phone' => session('groupPhone'),   //发码必填
                'pay_channel' => $payChannel, 
                'order_status' => '0', 
                'order_ip' => $ip
                ); 
            //存储订单
            $res = M('ttg_order_info')->add($data);
            if($res){  
                //订单扩展表
                $child_order_id = date('ymd') . substr(time(), -3) . substr(rand(11111, 99999), 0) . $cl;
                $row['order_id'] = $orderId;
                $row['trade_no'] = $child_order_id;
                $row['b_id'] = $goods;
                $row['b_name'] = $goodsInfo['batch_name'];
                $row['goods_num'] = $total;
                $row['price'] = $price;
                $row['amount'] = $total * $price;
                $row['receiver_type'] = '0';
                $res = M('ttg_order_info_ex')->add($row);
                if ($res) {
                    // 判断是否减库存
                    $goodsInfoObj = D('GoodsInfo');
                    $result = $goodsInfoObj->goodsStockChange($goods, $total);
                    if(false === $result){
                        $this->error("订单生成失败，订单商品库存操作异常！");
                    }
                    $isSku = M('tgoods_info')->where(array('goods_id'=>$goodsInfo['goods_id']))->getField('is_sku');
                    // 判断是否是sku商品
                    if ('1' === $isSku) {
                        // 判断是否减库存
                        $result = $goodsInfoObj->goodsStockChange($goods, $total, true);
                        if(false === $result){
                            $this->error("订单生成失败， 规格商品库存操作异常！");
                        }
                    }
                } else {
                    M()->rollback();
                    $this->error("订单扩展信息生成失败！");
                }

                M()->commit(); // 提交事务
                //更新用户购买信息
                $this->goodsModel->getCustomerBuyNum(session('?groupPhone'), $this->batch_id, $total);
                //支付
                A('Label/PayCM')->OrderPay($orderId);
            } else {
               $this->error('订单生成失败');
            }
        }else{
            M()->rollback();
            $this->error("商品数据为空，请到个人中心,<a href='index.php?g=Label&m=MyOrder&a=index&node_id=" .$this->node_id . "'>查看我的订单</a>");
            
        }  
    }


    /**
     * 美惠非标
     */
    private function mhGoodsInfo(&$goodsInfo)
    {
        if(!session("?groupPhone")) {
            return ;
        }
        $saler = D('MeiHui')->getBySalerInfo(session("groupPhone"), $this->node_id);
        if($saler) {
            $info = D('MeiHui')->getByVipDiscount($this->node_id, $goodsInfo['goods_id'], $saler['level']);
            if($info) {
                $goodsInfo['vip_discount'] = $info[0]['vip_discount'];

                if($goodsInfo['is_sku'] == '1') {
                    $skuObj = D('Sku', 'Service');
                    $skuData = $skuObj->getSkuInfoList($goodsInfo['goods_id'], $this->node_id);
                    $minP = 0;
                    $maxP = 0;
                    foreach($skuData as $k=>$v) {
                        if($k == 0) {
                            $minP = $v['market_price'];
                            $maxP = $v['market_price'];
                        }
                        if($minP > $v['market_price']) {
                            $minP = $v['market_price'];
                        }
                        if($maxP < $v['market_price']) {
                            $maxP = $v['market_price'];
                        }
                    }
                    $goodsInfo['vip_price'] = number_format( ($minP - $info[0]['vip_discount']), 2).'～'.number_format( ($maxP - $info[0]['vip_discount']), 2);
                } else {
                    $paid_price = $goodsInfo['priceAmt'] - $info[0]['vip_discount'];
                    $goodsInfo['vip_price'] = number_format($paid_price, 2);    
                }
            }
        }
    }
    
    //处理发码失败的订单
    public function reSendCode(){
        $orderId = I('orderId');
        D('SalePro', 'Service')->sendCode($orderId);
    }
}
