<?php

class DFStoreAction extends DfBaseAction {
    // 跳转回来地址
    const __TRUE_BACK_URL__ = '__TRUE_BACK_URL__';
    // 微信用户id
    public $wxid;

    public $openId;
    // 微信openId
    public $js_global = array();

    public $wap_sess_name = '';

    public $upload_path;

    const BATCH_TYPE_STORE = 1001;

    public $node_short_name = '';

    public $pageInfo = '';

    public $df_openid = '';

    public $expiresTime = "";

    public $CodeexpiresTime = "";

    public function _initialize() {
        parent::_initialize();
        // 获取OPENDI
        $this->wap_sess_name = 'node_wxid_' . $this->node_id;
        // 定义一个数组
        $wein_appid = session($this->wap_sess_name);
        // $wein_appid['openid']="oRdXPtxUmsYNAprPaluo36M2nBD4";
        if ($_GET['_SID_'] == 'w') {
            $wein_appid['openid'] = "oRdXPtxUmsYNAprPaluo36M2nBD4";
            $wein_appid['att_flag'] = true;
            session($this->wxid, $wein_appid);
        }
        if (empty($wein_appid['openid'])) {
            $this->_df_checklogin(false);
        }
        $this->df_openid = $wein_appid['openid'];
        $this->wxid = session($this->wap_sess_name);
        // 获取OPENDI
        $node_info = M('tnode_info')->where(
            array(
                'node_id' => $this->node_id))->find();
        $this->node_short_name = $node_info['node_short_name'];
        $this->upload_path = './Home/Upload/MicroWebImg/' . $this->node_id . '/';
        // 购物车SESSION名
        $this->session_cart_name = 'session_cart_products_' . $this->node_id;
        // 商品收货地址SESISON名
        $this->expiresTime = 120; // 手机发送间隔
        $this->CodeexpiresTime = 600; // 手机验证码过期时间
        $this->assign('expiresTime', $this->expiresTime);
        $this->assign('node_short_name', $this->node_short_name);
        $this->assign('id', $this->id);
        
        if (session("?groupPhone")) {
            $phoneNo = session("groupPhone");
            // 补充全局cookie
            $global_phone = cookie('_global_user_mobile');
            if (! $global_phone) {
                cookie('_global_user_mobile', $phoneNo, 3600 * 24 * 365);
            }
        }
        // 判断是否是微信中打开
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') === false) {
            $wx_flag = 0;
        } else {
            $wx_flag = 1;
        }
        $this->assign('wx_flag', $wx_flag);
    }

    public function index() {
        $id = $this->id; // channel_id
        session("id", $id);
        $batch_id = $this->batch_id; // tmaketing_info id
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
        
        // 查询店铺logo
        $logoWhere = array(
            'node_id' => $this->node_id, 
            'ban_type' => 1, 
            'm_id' => $this->batch_id);
        $logoInfo = M('tfb_df_pointshop_banner')->where($logoWhere)->find();
        // 查询店铺banner
        session("logo", $logoInfo['img_url']);
        $bannerWhere = array(
            'node_id' => $this->node_id, 
            'ban_type' => 0, 
            'm_id' => $this->batch_id);
        $bannerInfo = M('tfb_df_pointshop_banner')->where($bannerWhere)
            ->order("order_by desc ")
            ->select();
        // 查询登录LOGO和标题
        $node_model = M('tfb_df_pointshop_banner');
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
                "1,3"), 
            'status' => '1');
        $pageInfo = M('tfb_df_pointshop_page_sort')->where($catWhere)
            ->order("id desc")
            ->find();
        
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
        
        if ($pageInfo['id'] != "") {
            
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
        $categoryInfo = M('tfb_df_pointshop_catalog')->where($catWhere)
            ->order("id desc ")
            ->select();
        
        $nodeWhere = array();
        
        // $nodeWhere = " e.node_id='" . $this->node_id . "' AND b.status='0'
        // AND b.end_time>='" . date('YmdHis') . "' AND b.begin_time<='" .
        // date('YmdHis') . "'";
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
        
        $mapcount = M()->table('tfb_df_pointshop_goods_ex` e ')
            ->join("tbatch_info b ON b.id=e.b_id")
            ->where($nodeWhere)
            ->count();
        
        import('ORG.Util.Page'); // 导入分页类
        $Page = new Page($mapcount, 6);
        $totalpage = ceil($mapcount / 6);
        $nowPage = empty($_GET['p']) ? 1 : $_GET['p'];
        
        // 查询商品
        $goodsList = M()->table('tfb_df_pointshop_goods_ex e ')
            ->join("tbatch_info b ON b.id=e.b_id")
            ->join("tgoods_info t on t.goods_id=b.goods_id")
            ->where($nodeWhere)
            ->order("e.is_commend asc,b.id desc ")
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        if ($nowPage > $totalpage) {
            $goodsList = array();
        }
        
        // 组装下一页url
        $nexUrl = U('Df/DFStore/index', 
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
        
        $this->assign('nextUrl', $nexUrl);
        $this->assign('cartCount', $goodsCount[1]);
        $this->assign('keyword', $keyword);
        $this->assign('goodsList', $goodsList);
        $this->assign('bannerInfo', $bannerInfo);
        $this->assign('logoInfo', $logoInfo);
        $this->assign('batch_info', $batch_info);
        $this->assign('categoryInfo', $categoryInfo);
        $this->assign('currentUrl', $currentUrl);
        $this->assign('share_pic', $share_pic);
        $this->assign('id', $id);
        $this->display("Dfwap/DFStore_index"); // 输出模板
    }

    public function index2() {
        $currhost = C('CURRENT_DOMAIN');
        // $len=strlen($this->pageInfo['share_pic'])-2;
        
        // $tmp=substr($this->pageInfo['share_pic'],2,$len);
        // $share_pic=$currhost.$tmp;
        
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
        
        $share_pic = get_upload_url($shareInfo['img_url']);
        
        $pageInfo_tmp = $this->pageInfo['page_content'];
        $a = json_decode($pageInfo_tmp, true);
        
        foreach ($a['module'] as $k => &$v) {
            foreach ($v['list'] as $lk => &$lv) {
                $url = $lv['url'];
                $id_tmp = strstr($url, "&id=");
                $id_arr = explode("=", $id_tmp);
                $label_id = $id_arr[1];
                // todo debug 还有待优化
                $m_id = M('tbatch_channel')->where(
                    array(
                        'id' => $label_id))->getField('batch_id');
                $new_price = M()->table('tbatch_info b')
                    ->join('tgoods_info g on g.goods_id=b.goods_id')
                    ->where(array(
                    'b.m_id' => $m_id))
                    ->getField('b.batch_amt');
                $lv['price'] = $new_price;
                $lv['img'] = get_upload_url($lv['img']);
            }
        }
        $this->pageInfo['page_content'] = json_encode($a);
        
        $this->assign('pageInfo', $this->pageInfo);
        $this->assign('marketInfo', $marketInfo);
        $this->assign('descInfo', $descInfo);
        $this->assign('share_pic', $share_pic);
        $this->assign('currentUrl', $currentUrl);
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
        
        $pageInfo_tmp = $pageInfo['page_content'];
        $a = json_decode($pageInfo_tmp, true);
        
        foreach ($a['module'] as $k => &$v) {
            foreach ($v['list'] as $lk => &$lv) {
                $url = $lv['url'];
                $id_tmp = strstr($url, "&id=");
                $id_arr = explode("=", $id_tmp);
                $label_id = $id_arr[1];
                $m_id = M('tbatch_channel')->where(
                    array(
                        'id' => $label_id))->getField('batch_id');
                $new_price = M()->table('tbatch_info b')
                    ->join('tgoods_info g on g.goods_id=b.goods_id')
                    ->where(array(
                    'b.m_id' => $m_id))
                    ->getField('b.batch_amt');
                $lv['price'] = $new_price;
            }
        }
        $pageInfo['page_content'] = json_encode($a);
        
        // print_r($pageInfo);
        $this->assign('pageInfo', $pageInfo);
        $this->assign('currentUrl', $currentUrl);
        $this->assign('share_pic', $share_pic);
        $this->display();
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
            
            $nodeWhere .= " AND e.ecshop_classify ='" . $cat_id . "'";
        }
        
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
        
        $this->assign('nextUrl', $nexUrl);
        $this->assign('id', $id);
        $this->assign('cartCount', $goodsCount[1]);
        $this->assign('keyword', $keyword);
        $this->assign('goodsList', $goodsList);
        $this->assign('bannerInfo', $bannerInfo);
        $this->assign('logoInfo', $logoInfo);
        $this->assign('batch_info', $batch_info);
        $this->assign('categoryInfo', $categoryInfo);
        $this->assign('id', $id);
        $this->display(); // 输出模板
    }
    
    // DF积分商城页面
    public function detail() {
        $this->check_weixinlogin();
        $id = I("id"); // batch_channl id
                       // 更新访问量
        import('@.Vendor.DataStat');
        $opt = new DataStat($id, $this->full_id);
        $opt->UpdateRecord();
        
        if ($id == "") {
            $id = session("goods_id");
        } else {
            session("goods_id", $id);
        }
        
        // 取任意渠道
        if (session("id") == "") {
            $batch_model = M('tmarketing_info');
            $map = array(
                'node_id' => $this->node_id, 
                'batch_type' => self::BATCH_TYPE_STORE);
            $batch_info = $batch_model->where($map)->find();
            if (! empty($batch_info)) {
                
                // 查询活动渠道最近的
                $channel_model = M('tbatch_channel');
                $map = array(
                    'node_id' => $this->node_id, 
                    'batch_id' => $batch_info['id'], 
                    'status' => '1');
                $channel_info = $channel_model->where($map)
                    ->order("id desc")
                    ->find();
                if (! empty($channel_info)) {
                    session("id", "");
                    session("id", $channel_info['id']);
                }
            }
        }
        
        if ($this->batch_id == "" || empty($id)) {
            $this->error("参数错误");
        }
        
        // 根据batch_id（m_id）查询 batch_info id
        $goodsInfo = M('tbatch_info e ')->field('id')
            ->where("e.m_id='" . $this->batch_id . "'")
            ->find();
        $bid = $goodsInfo['id'];
        $mid = $this->batch_id;
        $where = array(
            "e.b_id" => $bid, 
            "e.m_id" => $mid);
        $goodsInfo = M('tfb_df_pointshop_goods_ex e ')->field(
            "e.id,b.id,t.goods_id,b.batch_name,b.storage_num,b.remain_num,b.batch_amt,b.begin_time,b.end_time,b.status,e.goods_desc,e.show_picture1,e.show_picture2,e.relation_goods,e.show_picture3,e.market_show,e.wap_info,e.purchase_time_limit,e.market_price")
            ->join("tbatch_info b ON b.id=e.b_id")
            ->join("tgoods_info t on t.goods_id=b.goods_id")
            ->where($where)
            ->order("e.is_commend,b.id desc ")
            ->find();
        $batch_amt = str_replace('.00', '', $goodsInfo['batch_amt']);
        // 查询库存数量
        // ->getField("b.batch_name,b.storage_number,b.remain_num,b.batch_amt,t.market_price",true);
        // 查询关联商品
        $relationWhere = array(
            "e.m_id" => array(
                "in", 
                $goodsInfo['relation_goods']));
        $relationGoods = M('tfb_df_pointshop_goods_ex e ')->join(
            "tbatch_info b ON b.id=e.b_id")
            ->join("tgoods_info t on t.goods_id=b.goods_id")
            ->where($relationWhere)
            ->order("b.id desc ")
            ->select();
        // echo M('tecshop_goods_ex e ')->getLastSql();
        $this->assign('relationGoods', $relationGoods);
        $this->assign("batch_amt", $batch_amt);
        $this->assign('goodsInfo', $goodsInfo);
        $this->assign('id', $id);
        $this->display("Dfwap/DFStore_detail"); // 输出模板
    }
    // 判断用户积分是否够
    public function is_point() {
        $this->check_weixinlogin();
        // 判断用户授权的Openid是否为会员
        $weixin_session = $this->wxid;
        // if($weixin_session['att_flag']===false){
        // exit(json_encode(array(
        // 'info' => '您还不是DF的会员，只有会员才可使用积分兑换商品！',
        // 'status' => 1,
        // )));
        // }
        $is_member = M("tfb_df_member")->where(
            array(
                "openid" => $this->df_openid))->find();
        if (empty($is_member['mobile'])) {
            exit(
                json_encode(
                    array(
                        'info' => '您还不是DF的会员，只有会员才可使用积分兑换商品！', 
                        'status' => 1)));
        }
        $id = I("id"); // batch_channl id
                       // 更新访问量
        import('@.Vendor.DataStat');
        $opt = new DataStat($id, $this->full_id);
        $opt->UpdateRecord();
        if ($this->batch_id == "" || empty($id)) {
            $this->error("参数错误");
        }
        $where = array(
            "e.m_id" => $this->batch_id);
        $goodsInfo = M('tfb_df_pointshop_goods_ex e ')->join(
            "tbatch_info b ON b.id=e.b_id")
            ->join("tgoods_info t on t.goods_id=b.goods_id")
            ->where($where)
            ->order("e.is_commend,b.id desc ")
            ->find();
        // 通过Openid去查询积分
        $openid = $this->df_openid;
        $point = M("tfb_df_member")->where(
            array(
                "openid" => $openid))->getField("point");
        if (intval($point) < intval($goodsInfo['batch_amt'])) {
            exit(
                json_encode(
                    array(
                        'info' => '您的积分不足，无法兑换！', 
                        'status' => 0)));
        }
        exit(json_encode(array(
            'status' => 2)));
    }

    public function Dforder_isok() {
        $this->check_weixinlogin();
        $id = I("id"); // batch_channl id
        if ($this->batch_id == "" || empty($id)) {
            $this->error("参数错误");
        }
        $where = array(
            "e.m_id" => $this->batch_id);
        $goodsInfo = M('tfb_df_pointshop_goods_ex e ')->join(
            "tbatch_info b ON b.id=e.b_id")
            ->join("tgoods_info t on t.goods_id=b.goods_id")
            ->where($where)
            ->order("e.is_commend,b.id desc ")
            ->find();
        // 截取价格
        $batch_amt = str_replace('.00', '', $goodsInfo['batch_amt']);
        // 通过Openid去查询积分
        $openid = $this->df_openid;
        $point = M("tfb_df_member")->where(
            array(
                "openid" => $openid))->getField("point");
        
        if (intval($point) < intval($goodsInfo['batch_amt'])) {
            $this->error("您的积分不足！");
        }
        // 用户积分减去商品积分=用户剩余积分
        $other_point = intval($point) - intval($goodsInfo['batch_amt']);
        $this->assign('other_point', $other_point);
        $this->assign('point', $point);
        $this->assign('batch_amt', $batch_amt);
        $this->assign('goodsInfo', $goodsInfo);
        $this->assign('id', $id);
        $this->display("Dfwap/Dforder_isok");
    }

    public function add_goods_cart() {
        $this->check_weixinlogin();
        $goods = I("goods");
        if (empty($goods)) {
            $this->error("请选择商品");
        }
        // 用户购买的分数
        $buy_number = I('buy_number');
        if (! empty($goods)) {
            // 检查库存，数量限制
            $ret = $this->check_storeage($goods, 2, $buy_number);
            if ($ret['status'] == '0') {
                $returnArr = array(
                    "status" => 0, 
                    "info" => $ret['info'], 
                    "goodsnum" => 0);
                echo json_encode($returnArr);
                exit();
            }
        }
        // 获取领取方式 1 到店领取 2 物流配送
        // $mInfo = M('tmarketing_info
        // m')->field('m.defined_one_name,g.goods_amt,b.remain_num')
        // ->join('tbatch_info b on b.m_id=m.id')
        // ->join('tgoods_info g on g.goods_id=b.goods_id')
        // ->join('tfb_df_pointshop_goods_ex pg on pg.goods_id=g.goods_id')
        // ->where(array('m.id'=>$this->batch_id))->find();
        // $delivery_flag = $mInfo['defined_one_name'];
        // $point = $mInfo['goods_amt'];
        // if($mInfo['status'] == '1')
        // $this->ajaxReturn(array('code'=>'0013','err_msg'=>'商户已被暂停，无法兑换'),'json');
        // //无存货
        // if($mInfo['remain_num'] <= 0)
        // $this->ajaxReturn(array('code'=>'0012','err_msg'=>'商品已被兑换完，无法兑换'),'json');
        // 如果库存存在，进行积分扣减以及卡券下发
        // 当前积分
        $point_now = I('point_now');
        // 通过openid,获取用户的积分
        $openid = $this->df_openid;
        $point = M("tfb_df_member")->where(
            array(
                "openid" => $openid))->getField("point");
        // 减去领奖的积分
        $score = intval($point) - intval($point_now);
        if ($score < 0) {
            exit(
                json_encode(
                    array(
                        'info' => '积分不足！', 
                        'status' => 0)));
        }
        M()->startTrans();
        $map_save = array(
            'openid' => $openid, 
            'type' => 2, 
            'before_num' => $point, 
            'change_num' => $point_now, 
            'after_num' => $score, 
            'trace_time' => date('YmdHis'), 
            'remark' => '积分商城扣减');
        // 添加积分扣减流水记录
        $query = M('tfb_df_point_trace')->add($map_save);
        if ($query === false) {
            M()->rollback();
            $this->ajaxReturn("error", "积分扣减失败！", 0);
        }
        // 修改tfb_df_member里面用户的积分
        $date = array(
            'point' => $score);
        $member_point = M("tfb_df_member")->where(
            array(
                'openid' => $openid))->save($date);
        if ($member_point === false) {
            M()->rollback();
            $this->ajaxReturn("error", "积分扣减失败！", 0);
        }
        // 判断商品是否有限制，无限制，无需扣减库存
        // 扣减库存里面的数量
        // $bInfo =
        // $model_->field('storage_num,remain_num')->where($map_)->find();
        // if ($bInfo['storage_num'] != -1) {
        // $query_arr = $model_->where($map_)->setDec('remain_num',
        // $row['goods_num']);
        // }
        $bInfo = M('tbatch_info')->field('storage_num,remain_num')
            ->where(array(
            'm_id' => $this->batch_id))
            ->find();
        if ($bInfo['storage_num'] != - 1) {
            $ret = M('tbatch_info')->where(
                array(
                    'm_id' => $this->batch_id))->setDec('remain_num', $buy_number); // 库存减1
        }
        if ($ret === false) {
            M()->rollback();
            $this->ajaxReturn(
                array(
                    'code' => '0007', 
                    'err_msg' => '扣减库存失败'), 'json');
        }
        // 积分扣减成功，生成订单号
        $order_id = date('ymd') . substr(time(), - 3) . substr(microtime(), 2, 
            7);
        $ip = GetIP();
        $mobile = M("tfb_df_member")->where(
            array(
                'openid' => $openid))->getField('mobile');
        // 生成订单编号
        $goodsInfo = M('tfb_df_pointshop_goods_ex e ')->join(
            "tbatch_info b ON b.id=e.b_id")
            ->where("b.id='" . $goods . "'")
            ->find();
        $orderData = array(
            'order_id' => $order_id, 
            'm_id' => $this->batch_id, 
            'batch_no' => $this->batch_id, 
            'from_channel_id' => session("id"), 
            'order_type' => '2', 
            'trans_time' => date('YmdHis', time()), 
            'node_id' => $this->node_id, 
            'pay_status' => 2, 
            'buy_num' => $buy_number, 
            'order_ip' => $ip, 
            'order_amt' => $point_now, 
            'order_phone' => $mobile, 
            'add_time' => date('YmdHis'), 
            'openid' => $this->df_openid);
        $order_status = M("tfb_df_pointshop_order_info")->add($orderData);
        if ($order_status === false) {
            M()->rollback();
            $this->ajaxReturn("error", "生成订单号失败！", 0);
        }
        // 生成订单成后，需向附表增加
        // 入库订单商品
        $row = array();
        $child_order_id = date('ymd') . substr(time(), - 3) . rand(11111, 99999);
        $row['order_id'] = $order_id;
        $row['trade_no'] = $child_order_id;
        $row['b_id'] = $goodsInfo['b_id'];
        $row['b_name'] = $goodsInfo['batch_short_name'];
        $row['goods_num'] = $buy_number;
        $row['price'] = $point_now / $buy_number;
        $row['amount'] = $point_now * $buy_number;
        $row['receiver_type'] = 0;
        $row['create_time'] = date('YmdHis');
        $res = M('tfb_df_pointshop_order_info_ex')->add($row);
        if ($res === false) {
            M()->rollback();
            $this->error("订单生成失败，订单商品入库异常！");
        }
        M()->commit();
        // 定单流水生成后，对用户进行支撑发码
        $goodsInfo = M('tbatch_info e ')->field('id')
            ->where("e.m_id='" . $this->batch_id . "'")
            ->find();
        $bid = $goodsInfo['id'];
        $mid = $this->id;
        log_write("DF活动ID" . $mid);
        $channelId = $this->channel_id;
        // 获取tbatch_info里面的ID
        $batch_no = M('tbatch_info')->where(
            array(
                'm_id' => $this->batch_id))->getField('batch_no');
        $appid_array = array(
            'df_openid' => $openid);
        for ($i = 1; $i <= $buy_number; $i ++) {
            $status = $this->sendCode($order_id, '', $this->node_id, $batch_no, 
                $mobile, $bid, $mid, $gift_phone = null, $appid_array);
            if ($status === false) {
                log_write("发码失败,订单号为：" . $order_id);
            }
        }
        exit(
            json_encode(
                array(
                    'info' => '兑换成功！', 
                    'status' => 1)));
    }
    
    // /发码处理
    protected function sendCode($orderId, $orderType, $nodeId, $issBatchNo, 
        $phone, $bId, $mId, $gift_phone = null, $appid_array) {
        // 发码
        // $transId = date('YmdHis').sprintf('%04s',mt_rand(0,1000));
        $transId = get_request_id();
        import("@.Vendor.SendCode");
        $req = new SendCode();
        // df非标======开始======
        // if($nodeId == C('df.node_id')){
        // $memberInfo = M('tfb_df_member')->where("mobile =
        // '{$phone}'")->find();
        // if(!$memberInfo){
        // $memberInfo = M('tfb_df_member_import')->where("mobile = '{$phone}'
        // and status = '0'")->find();
        // if($memberInfo)
        // $other['df_openid'] = $phone;
        // }else{
        // $other['df_openid'] = $memberInfo['openid'];
        // }
        // }
        
        // df非标======结束======
        
        $res = $req->wc_send($nodeId, '', $issBatchNo, $phone, '0', $transId, 
            '', $bId, null, $appid_array);
        log_write("13545204021:{$res}");
        if ($res == true) {
            $result = M('tfb_df_torder_trace')->add(
                array(
                    'order_id' => $orderId, 
                    'm_id' => $mId, 
                    'b_id' => $bId, 
                    'code_trace' => $transId, 
                    'gift_phone' => $gift_phone));
            if ($result === false) {
                log_write(
                    "DF积分商城订单发码成功,更新send_seq失败;order_id:{$orderId},send_seq:{$transId}");
            }
            return ture;
        } else {
            log_write("订单发码失败,原因:{$res}");
            return false;
        }
    }
    
    // 校验库存，购买数量限制，1 购物车数据 2 直接购买校验
    public function check_storeage($goods_id, $iscart, $buynum = 1) {
        if ($goods_id == "") {
            
            $returnArr = array(
                "status" => 0, 
                "info" => '参数错误');
            
            return $returnArr;
        }
        // 取商品信息判断商品库存
        $goodsInfo = M('tfb_df_pointshop_goods_ex e ')->join(
            "tbatch_info b ON b.id=e.b_id")
            ->where("b.id='" . $goods_id . "'")
            ->find();
        if ($iscart == 1) {
            $carts = $this->_getCart();
            $existnum = $carts[$goods_id] + $buynum;
        } else {
            $existnum = $buynum;
        }
        if (empty($goodsInfo)) {
            $returnArr = array(
                "status" => 0, 
                "info" => '操作失败,商品不存在！');
            return $returnArr;
        }
        
        $curdate = date("Ymd");
        // 判断日限购数量
        if ($goodsInfo['day_buy_num'] != - 1) {
            
            $goodsWhere = array(
                "a.b_id" => $goods_id, 
                "e.add_time" => array(
                    "like", 
                    "{$curdate}%"), 
                "e.order_status" => "0");
            // 计算当天订单商品数量
            $goodsCount = M('tfb_df_pointshop_order_info e ')->join(
                'tfb_df_pointshop_order_info_ex a ON e.order_id=a.order_id ')
                ->where($goodsWhere)
                ->sum("e.buy_num");
            $allcount = $existnum + $goodsCount;
            
            if ($allcount > $goodsInfo['day_buy_num']) {
                $returnArr = array(
                    "status" => 0, 
                    "info" => '操作失败，商品日限购' . $goodsInfo['day_buy_num'] . '份');
                return $returnArr;
            }
        }
        // 判断用户限购
        if ($goodsInfo['person_buy_num'] != - 1) {
            $goodsWhere = array(
                "a.b_id" => $goods_id, 
                "e.add_time" => array(
                    "like", 
                    "{$curdate}%"), 
                "e.openid" => $this->df_openid, 
                "e.order_status" => "0");
            // 计算当天用户订单商品数量
            $goodsCount = M('tfb_df_pointshop_order_info e ')->join(
                'tfb_df_pointshop_order_info_ex a ON e.order_id=a.order_id ')
                ->where($goodsWhere)
                ->sum("e.buy_num");
            $buycount = $existnum + $goodsCount;
            if ($buycount > $goodsInfo['person_buy_num']) {
                $returnArr = array(
                    "status" => 0, 
                    "info" => '操作失败，商品每个用户限购' . $goodsInfo['person_buy_num'] .
                         '份', 
                        "goodsnum" => 0);
                return $returnArr;
            }
        }
        
        // 判断库存
        if ($goodsInfo['storage_num'] == - 1 || ($goodsInfo['storage_num'] != - 1 &&
             $goodsInfo['remain_num'] >= $existnum)) {
            // $ret=$this->addGoodsToCart($goods_id,1);
            
            $returnArr = array(
                "status" => 1, 
                "info" => '成功');
            return $returnArr;
        } else {
            $returnArr = array(
                "status" => 0, 
                "info" => '操作失败，库存已售完！');
            return $returnArr;
        }
    }

    public function getShippingFee($orderAmt) {
        $shippingFee = 0;
        
        $queryMap = array(
            "node_id" => $this->node_id);
        $shippingConfig = M("tecshop_config")->where($queryMap)->find();
        if ($shippingConfig['freight_free_flag'] == 1) {
            if ($orderAmt < $shippingConfig['freight_free_limit']) {
                $shippingFee = $shippingConfig[freight];
            } else {
                $shippingFee = 0.00;
            }
        } else {
            $shippingFee = $shippingConfig[freight];
        }
        
        return $shippingFee;
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
        // if (!session('?groupPhone')) {
        // $this->showMsg('您还没有登录');
        // }
        // 标签
        // $model = M('tbatch_channel');
        // $map = array('id' => session("id"), 'batch_type' =>
        // $this->batch_type);
        // $result = $model->where($map)->find();
        $map = array(
            'openid' => $this->df_openid);
        // if (!$result) $this->error('错误的参数！', 0, $id, $this->node_short_name);
        // $where = array(
        // 'o.order_phone' => session('groupPhone'),
        // 'o.node_id' => $result['node_id'],
        // 'o.order_type' => '2'
        // );
        
        $orderList = M()->table('tfb_df_pointshop_order_info')
            ->where($map)
            ->order('add_time DESC')
            ->select();
        $status = array(
            '1' => '未支付', 
            '2' => '已支付');
        $this->assign('orderList', $orderList);
        
        $this->assign('status', $status);
        $this->assign('node_short_name', $this->node_short_name);
        $this->display('Dfwap/MyOrder_showOrderList');
    }

    public function direct_order_save() {
        
        // 判断是否登录
        if (! session('?groupPhone')) {
            $this->error('您还没有登录');
        }
        
        // 判断提交的商品数据是否存在
        $goods = I("goods");
        $price = I("price");
        $total = I("total");
        // 使用的红包
        $bonus_use_id = I("bonus_use_id");
        $names = I("names");
        if (empty($goods) || empty($price) || empty($total)) {
            $this->error('参数错误');
        }
        
        $address = I("receive_address");
        $receive_name = I("receive_name");
        $receive_phone = I("receive_phone");
        $memo = I("memo");
        $haveAddr = I("haveAddr");
        $pay_channel = I("payment_type"); // 1 支付宝 2联动优势
        
        $totalgoods = 0;
        $totalAmt = 0;
        
        if ($receive_phone == "") {
            $receive_phone = session('groupPhone');
        }
        
        $order_id = date('ymd') . substr(time(), - 3) . substr(microtime(), 2, 
            7);
        
        if (! empty($goods)) {
            foreach ($goods as $b => $bl) {
                
                $ret = $this->check_storeage($bl, 2, $total[$b]);
                
                if ($ret['status'] == '0') {
                    $this->error($ret['info']);
                }
                $totalgoods = $totalgoods + $total[$b];
                $goodsamt = $total[$b] * $price[$b];
                $totalAmt = $totalAmt + $goodsamt;
            }
            
            // 判断是否存在物流，只要存在一个产品是物流，订单主表记录物流方式
            if ($haveAddr > 0) {
                $isshipping = "1";
            } else {
                $isshipping = "0";
            }
            
            $shippingFee = 0;
            if ($haveAddr > 0) {
                // 计算运费
                $shippingFee = $this->getShippingFee($totalAmt);
                $totalAmt = $totalAmt + $shippingFee;
            }
            
            M()->startTrans(); // 起事务
                               
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
            
            $ip = GetIP();
            // 入库主订单
            $data = array(
                'order_id' => $order_id, 
                'order_type' => '2', 
                'from_channel_id' => session("id"), 
                'from_batch_no' => $this->batch_id, 
                'batch_no' => $this->batch_id, 
                'order_phone' => session('groupPhone'), 
                'buy_num' => $totalgoods, 
                'batch_channel_id' => session("id"), 
                'node_id' => $this->node_id, 
                'order_amt' => $totalAmt - $cutAmount, 
                'add_time' => date('YmdHis'), 
                'receiver_type' => $isshipping, 
                'receiver_name' => trim($receive_name), 
                'receiver_addr' => trim($address), 
                'receiver_phone' => trim($receive_phone), 
                'pay_channel' => $pay_channel, 
                'order_status' => '0', 
                'memo' => $memo, 
                'add_time' => date('YmdHis'), 
                'freight' => $shippingFee, 
                'order_ip' => $ip, 
                'bonus_use_amt' => $cutAmount); // 新增红包使用金额在订单表中 添加人：曾成
            
            $shop_return = $this->_getShopRC($this->_get_shop_mid());
            if ($shop_return !== false) {
                $data['from_user_id'] = $shop_return['u'];
                $data['from_type'] = $shop_return['t'];
            }
            $result = M('ttg_order_info')->add($data);
            if ($result) {
                
                // $shipdata =$this->_getShipType();
                foreach ($goods as $c => $cl) {
                    $child_order_id = date('ymd') . substr(time(), - 3) .
                         substr(rand(11111, 99999)) . $cl;
                    $row['order_id'] = $order_id;
                    $row['trade_no'] = $child_order_id;
                    $row['b_id'] = $cl;
                    $row['b_name'] = $names[$c];
                    $row['goods_num'] = $total[$c];
                    $row['price'] = $price[$c];
                    $row['amount'] = $total[$c] * $price[$c];
                    $row['receiver_type'] = $isshipping;
                    $res = M('ttg_order_info_ex')->add($row);
                    
                    if ($res) {
                        
                        // 批量减红包
                        if ($bonus_use_id != "") {
                            
                            $resok = $this->useBonus($bonus_use_id, $order_id, 
                                $cutAmount);
                            if (! $resok) {
                                M()->rollback();
                                $this->error("订单生成失败，红包扣减异常！");
                            }
                        }
                        
                        // 判断是否减库存
                        $model_ = M('tbatch_info');
                        $map_ = array(
                            'id' => $cl);
                        
                        $bInfo = $model_->field('storage_num,remain_num')
                            ->where($map_)
                            ->find();
                        
                        if ($bInfo['storage_num'] != - 1) {
                            $query_arr = $model_->where($map_)->setDec(
                                'remain_num', $row['goods_num']);
                        }
                    } else {
                        
                        M()->rollback();
                        $this->error("订单生成失败，订单商品入库异常！");
                    }
                }
            } else {
                M()->rollback();
                $this->error("订单生成失败，主订单入库异常！");
            }
            
            // 插入收货地址表
            // 判断地址是否存在，不存插入
            $map_ = array(
                'user_phone' => session('groupPhone'), 
                'phone_no' => trim($receive_phone), 
                'address' => trim($address));
            $bInfo = M('tphone_address')->field('id')
                ->where($map_)
                ->find();
            if ($bInfo['id'] == "" && $receive_name != "" && $address != "") {
                $wurow['user_phone'] = session('groupPhone');
                $wurow['user_name'] = trim($receive_name);
                $wurow['phone_no'] = trim($receive_phone);
                $wurow['address'] = trim($address);
                $wurow['add_time'] = date('YmdHis');
                $wurow['last_use_time'] = date('YmdHis');
                $res = M('tphone_address')->add($wurow);
            } else {
                $wurow['last_use_time'] = date('YmdHis');
                $res = M('tphone_address')->where("id='" . $bInfo['id'] . "'")->save(
                    $wurow);
            }
            
            M()->commit(); // 提交事务
                           
            // 去支付
            if ($pay_channel == '2') {
                // 去支付
                $payModel = A('Label/PayUnion');
                $payModel->OrderPay($order_id);
            } elseif ($pay_channel == '1') {
                $payModel = A('Label/PayMent');
                $payModel->OrderPay($order_id);
            } elseif ($pay_channel == '3') {
                // 微信支付
                $payModel = A('Label/PayWeixin');
                $payModel->goAuthorize($order_id);
            }
            // $payModel = A('Label/PayMent');
            // $payModel->OrderPay($order_id);
            exit();
        } else {
            
            $this->error(
                "商品数据为空，请到订单中心,<a href='index.php?g=Label&m=Store&a=showOrderList&id=" .
                     session('id') . "'>查看我的订单</a>");
        }
    }
    
    // 保存订单
    public function order_save() {
        
        // 判断是否登录
        if (! session('?groupPhone')) {
            $this->error('您还没有登录');
        }
        
        $order_id = date('ymd') . substr(time(), - 3) . substr(microtime(), 2, 
            7);
        $address = I("receive_address");
        $receive_name = I("receive_name");
        $receive_phone = I("receive_phone");
        $bonus_use_id = I("bonus_use_id", "", "trim");
        $memo = I("memo");
        
        $pay_channel = I("payment_type"); // 支付宝
        
        $carts = $this->_getCart();
        
        if (! empty($carts)) {
            // 计算金额
            $calCartInfo = $this->calCartInfo($carts);
            
            // 购物车数据
            $cartlist = $this->getCat($this->_getCart());
            if (empty($cartlist[0])) {
                $this->error(
                    "购物车数据为空，请到订单中心,<a href='index.php?g=Label&m=Store&a=showOrderList&id=" .
                         session('id') . "'>查看我的订单</a>");
            } else {
                
                $totalgoods = 0;
                // 判断库存是否足够
                foreach ($cartlist[0] as $k => $kal) {
                    
                    $goodsInfo = M('tbatch_info')->field(
                        "storage_num,remain_num")
                        ->where("id='" . $kal['b_id'] . "'")
                        ->find();
                    
                    $totalgoods = $totalgoods + $kal['total'];
                    
                    $ret = $this->check_storeage($kal['b_id'], 1);
                    
                    if ($ret['status'] == '0') {
                        $this->error(
                            $ret['info'] .
                                 "<a href='index.php?g=Label&m=Store&a=cart&id=" .
                                 session("id") . "'>点击返回");
                    }
                    // print_r($goodsInfo);
                    /*
                     * if($goodsInfo['storage_num']!=-1){ //echo
                     * $goodsInfo['remain_num']."xxxx===".$kal['total'];
                     * if($goodsInfo['remain_num']<$kal['total']){
                     * $this->error("订单生成失败，".$kal['batch_name']."库存缺货，返回购物车修改！,<a
                     * href='index.php?g=Label&m=Store&a=cart&id=".session("id")."'>点击返回");
                     * } }
                     */
                }
            }
            
            $where = array(
                "id" => session("id"));
            // 查询机构
            $channelInfo = M('batch_channel ')->field('node_id')
                ->where($where)
                ->find();
            
            if ($receive_phone == "") {
                $receive_phone = session('groupPhone');
            }
            
            // 判断是否存在物流，只要存在一个产品是物流，订单主表记录物流方式
            $shipdata = $this->_getShipType();
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
                $shippingFee = $this->getShippingFee($totalAmount);
                $calCartInfo[1] = $calCartInfo[1] + $shippingFee;
            }
            
            M()->startTrans(); // 起事务
                               
            // 判断如果有可使用的红包则订单金额减去红包金额，如果红包金额小于红包可使用金额则减去金额，否则减去最大可以使用金额
            if ($bonus_use_id != "") {
                // 计算订单可减去金额
                $reAmount = $this->orderCutBonus($bonus_use_id);
                // 如果累计的红包金额满足最大的使用红包金额，则减去最大红包金额，否则减去红包金额累计
                $maxAmount = $this->getUseBonus($calCartInfo[1]);
                if ($reAmount < $maxAmount) {
                    $cutAmount = $reAmount;
                } else {
                    $cutAmount = $maxAmount;
                }
            }
            $ip = GetIP();
            // 入库主订单
            $data = array(
                'order_id' => $order_id, 
                'order_type' => '2', 
                'from_channel_id' => session("id"), 
                'from_batch_no' => $this->batch_id, 
                'batch_no' => $this->batch_id, 
                'order_phone' => session('groupPhone'), 
                'buy_num' => $totalgoods, 
                'batch_channel_id' => session("id"), 
                'node_id' => $this->node_id, 
                'order_amt' => $calCartInfo[1] - $cutAmount, 
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
                'bonus_use_amt' => $cutAmount); // 新增红包使用金额在订单表中 添加人：曾成
            
            $shop_return = $this->_getShopRC($this->_get_shop_mid());
            if ($shop_return !== false) {
                $data['from_user_id'] = $shop_return['u'];
                $data['from_type'] = $shop_return['t'];
            }
            $result = M('ttg_order_info')->add($data);
            
            if ($result) {
                // 入库订单商品
                $row = array();
                $tdata = $this->getCat($carts);
                $shipdata = $this->_getShipType();
                
                foreach ($tdata[0] as $k => $c) {
                    
                    // 判断物流方式,如果没选择配送方式，则判断如果只有物流配送则默认物流配送，如果自提和物流方式都有则默认自提
                    if (empty($shipdata[$k])) {
                        
                        $where = array(
                            "e.b_id" => $k);
                        
                        $goodsInfo = M('tecshop_goods_ex e ')->join(
                            "tbatch_info b ON b.id=e.b_id")
                            ->where($where)
                            ->find();
                        if ($goodsInfo['delivery_flag'] == '0-1' ||
                             $goodsInfo['delivery_flag'] == '0') {
                            $shipdata[$k] = '0';
                        } else {
                            $shipdata[$k] = '1';
                        }
                    }
                    
                    $child_order_id = date('ymd') . substr(time(), - 3) .
                         substr(rand(11111, 99999)) . $k;
                    $row['order_id'] = $order_id;
                    $row['trade_no'] = $child_order_id;
                    $row['b_id'] = $k;
                    $row['b_name'] = $c['batch_name'];
                    $row['goods_num'] = $c['total'];
                    $row['price'] = $c['price'];
                    $row['amount'] = $c['totalPrice'];
                    $row['receiver_type'] = $shipdata[$k];
                    $res = M('ttg_order_info_ex')->add($row);
                    if ($res) {
                        
                        // 批量减红包
                        if ($bonus_use_id != "") {
                            
                            $resok = $this->useBonus($bonus_use_id, $order_id, 
                                $cutAmount);
                            if (! $resok) {
                                M()->rollback();
                                $this->error("订单生成失败，红包扣减异常！");
                            }
                        }
                        
                        // 判断是否减库存
                        $model_ = M('tbatch_info');
                        $map_ = array(
                            'id' => $k);
                        
                        $bInfo = $model_->field('storage_num,remain_num')
                            ->where($map_)
                            ->find();
                        if ($bInfo['storage_num'] != - 1) {
                            $query_arr = $model_->where($map_)->setDec(
                                'remain_num', $row['goods_num']);
                        }
                    } else {
                        M()->rollback();
                        $this->error("订单生成失败，订单商品入库异常！");
                    }
                }
            } else {
                M()->rollback();
                $this->error("订单生成失败，主订单入库异常！");
            }
            
            // 插入收货地址表
            // 判断地址是否存在，不存插入
            $map_ = array(
                'user_phone' => session('groupPhone'), 
                'phone_no' => trim($receive_phone), 
                'address' => trim($address));
            $bInfo = M('tphone_address')->field('id')
                ->where($map_)
                ->find();
            if ($bInfo['id'] == "" && $receive_name != "" && $address != "") {
                $wurow['user_phone'] = session('groupPhone');
                $wurow['user_name'] = trim($receive_name);
                $wurow['phone_no'] = trim($receive_phone);
                $wurow['address'] = trim($address);
                $wurow['add_time'] = date('YmdHis');
                $wurow['last_use_time'] = date('YmdHis');
                $res = M('tphone_address')->add($wurow);
            } else {
                $wurow['last_use_time'] = date('YmdHis');
                $res = M('tphone_address')->where("id='" . $bInfo['id'] . "'")->save(
                    $wurow);
            }
            
            M()->commit(); // 提交事务
            
            session($this->session_cart_name, null);
            session($this->session_ship_name, null);
            
            // $this->success("订单生成成功,订单号：".$order_id);
            
            // 去支付
            if ($pay_channel == '2') {
                // 去支付
                $payModel = A('Label/PayUnion');
                $payModel->OrderPay($order_id);
            } elseif ($pay_channel == '1') {
                $payModel = A('Label/PayMent');
                $payModel->OrderPay($order_id);
            } elseif ($pay_channel == '3') {
                // 微信支付
                $payModel = A('Label/PayWeixin');
                $payModel->goAuthorize($order_id);
            }
            exit();
        } else { // 如果不存在购物车数据
            
            $this->error(
                "购物车数据为空，请到订单中心,<a href='index.php?g=Label&m=Store&a=showOrderList&id=" .
                     session('id') . "'>查看我的订单</a>");
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
        if ($pay_channel == '2') {
            // 去支付
            $payModel = A('Label/PayUnion');
            $payModel->OrderPay($order_id);
        } elseif ($pay_channel == '1') {
            $payModel = A('Label/PayMent');
            $payModel->OrderPay($order_id);
        } elseif ($pay_channel == '3') {
            $payModel = A('Label/PayWeixin');
            $payModel->goAuthorize($order_id);
        }
        // $payModel = A('Label/PayMent');
        // $payModel->OrderPay($order_id);
        exit();
    }
    
    // 查询凭证
    public function code() {
        
        // 判断是否登录
        // if (!session('?groupPhone')) {
        // $this->error('您还没有登录');
        // }
        
        // $where = array(
        // "b.node_id" => $this->node_id,
        // "b.phone_no" => session('groupPhone'),
        // //"a.goods_type"=>'6',
        // "b.trans_type" => '0001',
        // "b.status" => '0'
        // );
        $where = array(
            'c.df_openid' => $this->df_openid);
        $where[] = "c.barcode_id=b.id";
        $goodsList = M()->table('tfb_dftrace_relation c,tbarcode_trace b')
            ->join("tgoods_info a ON a.batch_no=b.batch_no")
            ->field("b.*,a.goods_name,a.goods_image")
            ->where($where)
            ->order("b.id desc")
            ->select();
        $this->assign('goodsList', $goodsList);
        $this->display('Dfwap/MyOrder_code');
    }

    public function code_detail() {
        
        // 判断是否登录
        // if (!session('?groupPhone')) {
        // $this->error('您还没有登录');
        // }
        $code_seq = I("code_seq");
        if ($code_seq == "") {
            $this->error('参数错误');
        }
        
        //
        // $where = array(
        // "b.node_id" => $this->node_id,
        // "b.phone_no" => session('groupPhone'),
        // //"a.goods_type"=>'6',
        // "b.req_seq" => $code_seq,
        // );
        
        $where = array(
            "b.node_id" => $this->node_id, 
            "c.df_openid" => $this->df_openid, 
            // "a.goods_type"=>'6',
            "b.req_seq" => $code_seq);
        $where[] = "c.barcode_id=b.id";
        $goodsInfo = M()->table('tfb_dftrace_relation c,tbarcode_trace b')
            ->field(
            "a.goods_name,b.end_time,b.use_status,b.assist_number,b.mms_title,b.mms_text,b.barcode_bmp")
            ->join("tgoods_info a ON a.batch_no=b.batch_no")
            ->where($where)
            ->find();
        $goodsInfo['barcode_bmp'] = $goodsInfo['barcode_bmp'] ? 'data:image/png;base64,' .
             base64_encode(
                $this->_bar_resize(base64_decode($goodsInfo['barcode_bmp']), 
                    'png')) : '';
        
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
        $this->display('Dfwap/MyOrder_code_detail');
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
        // print_r($ruleList);
        
        if (! empty($ruleList)) {
            
            foreach ($ruleList as $k => $v) {
                
                if ($amount >= $v['rev_amount']) {
                    
                    return $v['use_amount'];
                    break;
                }
            }
            
            return $reAmount;
        } else {
            
            return $reAmount;
        }
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
                "b.status" => '1', 
                // "f.status"=>'1',
                "b.phone" => $phone);
            // $where['_string'] = "(b.bonus_num-b.bonus_use_num)>0 AND
            // i.bonus_end_time>='".$currentDay."' AND
            // f.end_time>='".$currentDay."'";
            $where['_string'] = "(b.bonus_num-b.bonus_use_num)>0 AND i.bonus_end_time>='" .
                 $currentDay . "'";
            $userBonusList = M('tbonus_use_detail b ')->field('b.*,m.amount')
                ->join("tbonus_detail m on m.id=b.bonus_detail_id")
                ->join("tbonus_info i on i.id=m.bonus_id")
                ->join("tmarketing_info f on f.id=b.m_id")
                ->order("f.end_time asc")
                ->where($where)
                ->select();
            
            // echo M()->getLastSql();
            // exit;
            
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
            "b.status" => '1', 
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
    protected function useBonus($bonus_use_id, $order_id, $cut_amt) {
        if ($bonus_use_id == "") {
            return false;
        }
        
        // 记录面额ID
        $detail_id = "";
        // 记录减的数量
        $use_num = 0;
        $bonusUseList = explode(",", $bonus_use_id);
        if ($bonusUseList) {
            foreach ($bonusUseList as $item => $val) {
                
                $bonusMap = array(
                    "id" => $val);
                $bonusMap['_string'] = " (bonus_num-bonus_use_num)>0 ";
                
                // 查询红包数据
                $useInfo = M('tbonus_use_detail')->where($bonusMap)->find();
                if ($useInfo) {
                    if ($detail_id != "") {
                        $detail_id .= ",";
                    }
                    // 数量加一
                    $use_num ++;
                    
                    $detail_id .= $useInfo['bonus_detail_id'];
                    
                    $bonusData['bonus_use_num'] = array(
                        'exp', 
                        'bonus_use_num+1');
                    $bonusData['order_id'] = $order_id;
                    $bonusData['bonus_amount'] = $cut_amt;
                    $bonusData['use_time'] = date('YmdHis');
                    
                    $res = M('tbonus_use_detail')->where($bonusMap)->save(
                        $bonusData);
                    if ($res === false) {
                        return false;
                    }
                }
            }
            
            // 更新使用bonus_detail
            $detailMap = array(
                "id" => array(
                    'in', 
                    $detail_id), 
                "node_id" => $this->node_id);
            $detailres = M('tbonus_detail')->where($detailMap)->setInc(
                'use_num', $use_num);
            
            if ($detailres === false) {
                
                return false;
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
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') == true) {
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
                $cPhone = M('to2o_wx_config')->where(
                    array(
                        'openid' => $wxUserInfo['openid']))->getField('phone_no');
            }
        }
        $Phone = session('groupPhone');
        $map['b.phone'] = $Phone;
        $map['b.node_id'] = $this->node_id;

        $list =  M()->table("tbonus_use_detail b")->field(
            'b.*,m.bonus_page_name,m.bonus_amount,d.amount,m.bonus_start_time,m.bonus_end_time')
            ->join('tbonus_info m on b.bonus_id=m.id')
            ->join('tbonus_detail d on d.id=b.bonus_detail_id')
            ->join("tmarketing_info f on f.id=b.m_id")
            ->where($map)
            ->order('b.status asc,b.use_time asc,f.end_time desc,b.id asc')
            ->select();
        $this->assign('list', $list);
        $this->assign('show_wx', $show_wx);
        $this->assign('cPhone', $cPhone);
        $this->assign('now_time', date('YmdHis'));
        $this->display('Dfwap/MyOrder_myBonus');
    }

    /**
     * 个人中心
     */
    public function my() {
        // 判断是否登录
        if (! session('?groupPhone')) {
            $this->showMsg('您还没有登录');
        }
        $this->display();
    }

    public function showOrderInfo() {
        // 判断是否登录
        // if (!session('?groupPhone')) {
        // $this->showMsg('您还没有登录');
        // }
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
        $this->assign('orderInfo', $orderInfo);
        $this->assign('orderListInfo', $orderListInfo);
        $this->assign('status', $status);
        $this->assign('node_short_name', $this->node_short_name);
        $this->assign('codeTrace', $codeTrace);
        $this->assign('giftInfo', $giftInfo);
        $this->assign('bonusInfo', $bonusInfo);
        $this->assign('hav_count', $hav_count);
        $this->assign('id', session('id'));
        $this->display('Dfwap/MyOrder_showOrderInfo');
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
        // dump($resp_array);exit;
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
        $dfInfo = session($this->wxid);
        if (empty($dfInfo['openid'])) {
            $this->_df_checklogin();
        } else {
            return;
        }
    }
}