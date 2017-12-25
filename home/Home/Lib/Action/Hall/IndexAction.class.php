<?php
// 交易大厅
class IndexAction extends HallBaseAction {

    public $mk_price_arr = array();

    public $city_arr = array();

    /**
     *
     * @var VisitLogModel
     */
    private $VisitLogModel;

    public function _initialize() {
        parent::_initialize();
        $this->mk_price_arr = array(
                '0' => '单价不限',
                '1' => '免费',
                '2' => '1-50元',
                '3' => '51-100元',
                '4' => '100元以上');

        $this->city_arr = array(
                '0' => '所有城市',
                '021' => '上海',
                '010' => '北京',
                '020' => '广州',
                '755' => '深圳',
                '027' => '武汉',
                '731' => '长沙',
                '022' => '天津',
                '591' => '福州');

        switch (ACTION_NAME) {
            case 'index':
                $title = '卡券交易大厅-首页';
                $logInfo = '卡券交易大厅-首页';
                break;
            case 'build':
                $title = '卡券交易大厅-全部卡券';
                $logInfo = '卡券交易大厅-全部卡券';
                break;
            case 'hallMessage':
                $title = '卡券交易大厅-采购需求';
                $logInfo = '卡券交易大厅-采购需求';
                break;
            case 'employeeActivity':
                $title = '卡券交易大厅-首页';
                $logInfo = '员工福利';
                break;
            case 'feedbackActivity':
                $title = '卡券交易大厅-首页';
                $logInfo = '回馈客户';
                break;
            case 'cashGiftActivity':
                $title = '卡券交易大厅-首页';
                $logInfo = '积分换礼';
                break;
            case 'hallHelp':
                $title = '卡券交易大厅';
                $logInfo = '帮助中心';
                break;
            case 'purchaseMessage':
                $title = '卡券交易大厅';
                $logInfo = '留言求购';
                break;
            default:
                $title = '卡券交易大厅';
                $logInfo = ACTION_NAME;
        }
        $visitPage = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http' .
                '://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
        $this->VisitLogModel = D('VisitLog');
        $this->VisitLogModel->logByAction($visitPage, $title, $logInfo);
    }

    public function index() {
        // 获取首页图片
        $bannerInfo = M('tym_news')->field("news_img,go_url")
                ->where("class_id='56' AND status='1'")
                ->order("sort DESC")
                ->select();
        $map = "";
        if(empty($this->userInfo['node_id'])){
            $map = "AND g.goods_type not in (7,15)";
        }
        // 获取热度最大的卡券
        $hotHallInfo = M()->table("thall_goods t") ->field(
                "t.*,(SELECT COUNT(*) FROM tnode_favorite WHERE relation_id = t.id AND fav_type = '0') AS fav_num")
                ->join("tgoods_info g ON t.goods_id=g.goods_id")
                ->where("t.status='0' AND t.check_status='1' AND t.is_hot='1' ".$map)
                ->order("t.visit_num desc")
                ->select();
        // 获取最新采购留言
        $purchaseMessageList = M('twc_board')->where(
                "liuyan_type='2' AND screen='0'")
                ->order("add_time DESC")
                ->limit(6)
                ->select();
        // dump($purchaseMessageList);
        // 获取交易大厅公告,规则,常见问题
        $helpNoticeList = M('tym_news')->field('news_id,news_name')
                ->where("class_id='58' and parent_class_id='44' and status='1'")
                ->order("news_id desc")
                ->limit(4)
                ->select();
        $helpRuleList = M('tym_news')->field('news_id,news_name')
                ->where("class_id='55' and parent_class_id='44' and status='1'")
                ->order("news_id desc")
                ->limit(4)
                ->select();
        // 待付款,待确认,已采购,已供货订单
        $waitingOrder = M('tnode_goods_book')->where(
                "node_id='{$this->nodeId}' AND status='1'")->count();
        $waitingCofirmOrder = M('tnode_goods_book')->where(
                "goods_node_id='{$this->nodeId}' AND status='0'")->count();
        $purchaseOrder = M('tnode_goods_book')->where(
                "node_id='{$this->nodeId}' AND status='4'")->count();
        $supplyOrder = M('tnode_goods_book')->where(
                "goods_node_id='{$this->nodeId}' AND status='4'")->count();
        // 采购入门,供货入门链接
        $pLoginLink = U('Hall/Index/hallHelpView',
                array(
                        'news_id' => C('HALL_PS_LINK.PLOGIN_ID'),
                        'leftId' => 'cjwt',
                        'type' => '1')); // 登陆大厅
        $pSearchLink = U('Hall/Index/hallHelpView',
                array(
                        'news_id' => C('HALL_PS_LINK.PSEARCH_ID'),
                        'leftId' => 'cjwt',
                        'type' => '1')); // 搜索卡券
        $pOrderLink = U('Hall/Index/hallHelpView',
                array(
                        'news_id' => C('HALL_PS_LINK.PORDER_ID'),
                        'leftId' => 'cjwt',
                        'type' => '1')); // 下单采购
        $sLink = U('Hall/Index/hallHelpView',
                array(
                        'news_id' => C('HALL_PS_LINK.S_ID'),
                        'leftId' => 'cjwt',
                        'type' => '1')); // 发布卡券
        // 招商,平安,移动连接
        $zsLink = U('Hall/Index/goods',
                array(
                        'goods_id' => C('HALL_COOPERATION_CASE.ZS_GOODS_ID')));
        $paLink = U('Hall/Index/goods',
                array(
                        'goods_id' => C('HALL_COOPERATION_CASE.PA_GOODS_ID')));
        $ydLink = U('Hall/Index/goods',
                array(
                        'goods_id' => C('HALL_COOPERATION_CASE.YD_GOODS_ID')));
        $this->assign('hotHallInfo', $hotHallInfo);
        $this->assign('bannerInfo', $bannerInfo);
        $this->assign('purchaseMessageList', $purchaseMessageList);
        $this->assign('helpNoticeList', $helpNoticeList);
        $this->assign('helpRuleList', $helpRuleList);
        $this->assign('pLoginLink', $pLoginLink);
        $this->assign('pSearchLink', $pSearchLink);
        $this->assign('pOrderLink', $pOrderLink);
        $this->assign('sLink', $sLink);
        $this->assign('zsLink', $zsLink);
        $this->assign('paLink', $paLink);
        $this->assign('ydLink', $ydLink);
        $this->assign('waitingOrder', $waitingOrder);
        $this->assign('waitingCofirmOrder', $waitingCofirmOrder);
        $this->assign('purchaseOrder', $purchaseOrder);
        $this->assign('hallModel',D('Hall'));
        $this->assign('supplyOrder', $supplyOrder);
        $this->display();
    }

    public function buildGet(){
        $type = I('type', null);
        $amount = I('amount', null);
        $city = I('city_code', null);
       // $city_name = I('city_name', null);
        //$order_str = I('order_str', null);
        $oneCate = I('oneCate', null);
        $twoCate = I('twoCate', null);
        $keyWords = I('key_words', null, 'trim');
        $general = I('general', null);
        $hotest = I('hotest', null);
        $sales = I('sales', null);
        $time = I('time', null);
        $amtWhere = "";
        //未登陆不显示话费、流量
        if(empty($this->userInfo['node_id'])){
            $amtWhere .= "AND  g.goods_type not in(15,7)";
        }
        switch ($type) {
            case '优惠券':
                $amtWhere .= " AND  g.goods_type='0'";
                break;
            case '代金券':
                $amtWhere .= " AND  g.goods_type='1'";
                break;
            case '提领券':
                $amtWhere .= " AND  g.goods_type='2'";
                break;
            case '折扣券':
                $amtWhere .= " AND  g.goods_type='3'";
                break;
            case '话费':
                $amtWhere .= " AND  g.goods_type='7'";
                break;
            case 'Q币':
                $amtWhere .= " AND  g.goods_type='8'";
                break;
            case '流量包':
                $amtWhere .= " AND  g.goods_type='15'";
                break;
            case '微信红包':
                $amtWhere .= " AND  g.goods_type='22'";
                break;
        }
        //单价
        if ($amount != "单价不限" && $amount != "") {
            if ($amount == '免费') {
                $amtWhere .= " AND  t.batch_amt='0'";
            } elseif ($amount == '1-50元') {
                $amtWhere .= " AND  ( t.batch_amt>='1' AND t.batch_amt<='50' )";
            } elseif ($amount == '51-100元') {
                $amtWhere .= " AND ( t.batch_amt>='51' AND t.batch_amt<='100' )";
            } elseif ($amount == '100元以上') {
                $amtWhere .= " AND  t.batch_amt>'100' ";
            }
        }
        // 分类筛选
      //  $twoCateData = array();
        if (! empty($oneCate) && $oneCate != '-') {
            if (! empty($twoCate) && $twoCate != '-') {
                $amtWhere .= " AND t.goods_cat = '{$twoCate}'";
            } else {
                $amtWhere .= " AND t.goods_cat Like '{$oneCate}%'";
            }
        }

        // 城市筛选
        $cityWhere = '';

        if ($city != '' && $city != '所有城市') {
            $city_['city'] = array('like', '%'.$city.'%');
            $result = M('tcity_code')->where($city_)->getfield('city_code');
            $cityWhere .= " AND zz.city_code LIKE '%{$result}%'";
        } else {
            $cityWhere .= " OR (xx.city_note='1')";
        }

        // 排序处理
        $orderStr = '';
        if( $general == 1 ) {
            $orderStr = 'xx.hall_top_time DESC,xx.sell_num_note DESC';
        }
        if($hotest == null && $sales == null && $time == null){
            $orderStr = 'xx.hall_top_time DESC,xx.sell_num_note DESC';
        }
        if($hotest == 1) {
            $orderStr = 'xx.hall_top_time DESC,xx.sell_num_note DESC';
        }elseif($hotest === "0") {
            $orderStr = 'xx.hall_top_time DESC,xx.sell_num_note ASC';
        }
        if($sales == 1) {
            $orderStr = 'xx.hall_top_time DESC,xx.batch_amt DESC';
        }elseif($sales === "0") {
            $orderStr = 'xx.hall_top_time DESC,xx.batch_amt ASC';
        }
        if($time == 1) {
            $orderStr = 'xx.hall_top_time DESC,xx.add_time DESC';
        }elseif($time === "0") {
            $orderStr = 'xx.hall_top_time DESC,xx.add_time ASC';
        }


/*        $amtflag = 'sales_down';
        switch ($order_str) {
            case 'hotest':
                $orderStr .= 'xx.sell_num_note DESC';
                break;
            case 'collect':
                $orderStr .= 'xx.fav_num DESC';
                break;
            case 'sales_up':
                $orderStr .= 'xx.batch_amt DESC';
                $amtflag = 'sales_down';
                break;
            case 'sales_down':
                $orderStr .= 'xx.batch_amt ASC';
                $amtflag = 'sales_up';
                break;
            case 'time':
                $orderStr .= 'xx.add_time DESC';
                break;
            default://对置顶卡券以外卡券按销量从高到低排序
                $orderStr .= 'xx.hall_top_time desc,xx.sell_num_note DESC';
        }*/
        if ($keyWords != '') {
            $amtWhere .= " AND  t.batch_name like '%{$keyWords}%'";
        }
        import('@.ORG.Util.Page'); // 导入分页类
        $sql = "SELECT COUNT(*) as count FROM (SELECT t.*,(SELECT COUNT(*) FROM tnode_favorite WHERE relation_id = t.id AND fav_type = '0') AS fav_num,g.goods_type
											   FROM thall_goods t,tgoods_info g
											   WHERE t.status = '0' AND t.check_status = '1' AND t.goods_id = g.goods_id {$amtWhere}
										      ) xx

											  LEFT JOIN

											  (SELECT yy.goods_id,GROUP_CONCAT(DISTINCT yy.city_code) AS city_code
											   FROM (SELECT t.`goods_id`,s.city_code
												     FROM thall_goods t,tgoods_info g,tstore_info s,tpos_group_store gs
												     WHERE t.status = '0' AND t.check_status = '1' AND t.goods_id = g.goods_id AND gs.group_id = g.`pos_group` AND s.store_id = gs.store_id AND g.`pos_group_type` = '2'
												     GROUP BY g.goods_id,s.city_code
													 UNION
													 SELECT t.`goods_id`,s.city_code
													 FROM thall_goods t,tgoods_info g,tstore_info s
													 WHERE t.status = '0' AND t.check_status = '1' AND t.goods_id = g.goods_id AND s.node_id = g.node_id AND g.`pos_group_type` = '1'
													 GROUP BY g.goods_id,s.city_code
												     ) yy GROUP BY yy.goods_id
											   ) zz ON xx.goods_id = zz.goods_id
				WHERE 1=1 {$cityWhere}";

        $mapcount = M()->query($sql);
        $Page = new Page($mapcount[0]['count'], 20); // 实例化分页类 传入总记录数和每页显示的记录数

        $sql = "SELECT xx.city_note,xx.id,xx.label_note,xx.batch_img,xx.batch_name,xx.batch_short_name,xx.batch_amt,xx.sell_num_note,zz.city_code FROM (SELECT t.*,(SELECT COUNT(*) FROM tnode_favorite WHERE relation_id = t.id AND fav_type = '0') AS fav_num,g.goods_type
											   FROM thall_goods t,tgoods_info g
											   WHERE t.status = '0' AND t.check_status = '1' AND t.goods_id = g.goods_id {$amtWhere}
										      ) xx

											  LEFT JOIN

											  (SELECT yy.goods_id,GROUP_CONCAT(DISTINCT yy.city_code) AS city_code
											   FROM (SELECT t.`goods_id`,s.city_code
												     FROM thall_goods t,tgoods_info g,tstore_info s,tpos_group_store gs
												     WHERE t.status = '0' AND t.check_status = '1' AND t.goods_id = g.goods_id AND gs.group_id = g.`pos_group` AND s.store_id = gs.store_id AND g.`pos_group_type` = '2'
												     GROUP BY g.goods_id,s.city_code
													 UNION
													 SELECT t.`goods_id`,s.city_code
													 FROM thall_goods t,tgoods_info g,tstore_info s
													 WHERE t.status = '0' AND t.check_status = '1' AND t.goods_id = g.goods_id AND s.node_id = g.node_id AND g.`pos_group_type` = '1'
													 GROUP BY g.goods_id,s.city_code
												     ) yy GROUP BY yy.goods_id
											   ) zz ON xx.goods_id = zz.goods_id
				WHERE 1=1 {$cityWhere}
				ORDER BY {$orderStr}
				LIMIT " . $Page->firstRow . "," . $Page->listRows;
        $list = M()->query($sql);
       // var_dump($list);exit;
        //echo M()->_sql();exit;
        $Page->setConfig('theme',
                '%upPage% %prePage%  %linkPage%  %nextPage% %downPage% ');
        $Page->setConfig('prev', '«');
        $Page->setConfig('next', '»');
        $show = $Page->show(); // 分页显示输出

        $cityArr = $this->getcityCode();
        if (! empty($list)) {
            foreach ($list as $ck => $cal) {
                $citystr = array();
                foreach (explode(",", $cal['city_code']) as $v) {
                    if (array_key_exists($v, $cityArr)) {
                        $citystr[] = $cityArr[$v];
                    }
                }
                if(count($citystr)>4){
                    $citystr = array_slice($citystr ,0 ,4);
                    $citystr[4] = '......';
                }
                if ($cal['city_note'] == '1') {
                    $citystr = array(
                            "全国");
                }

                $batchimg = json_decode($list[$ck]['batch_img'],true);
                $list[$ck]['batch_img'] =  get_upload_url($batchimg[0]) == NUll?get_upload_url($list[$ck]['batch_img']):get_upload_url($batchimg[0]);
                $list[$ck]['citystr'] = $citystr;
            }
        }
        // 筛选条件初始化
        $goods_type_arr = array(
                '0' => '全部类型',
                '1' => '优惠券',
                '2' => '代金券',
                '3' => '提领券',
                '4' => '折扣券',
                '8' => 'Q币',
                '22'=> '微信红包'
        );
        if(!empty($this->userInfo['node_id'])){
            $goods_type_arr = array(
                    '0' => '全部类型',
                    '1' => '优惠券',
                    '2' => '代金券',
                    '3' => '提领券',
                    '4' => '折扣券',
                    '7' => '话费',
                    '8' => 'Q币',
                    '15' => '流量包',
                    '22'=> '微信红包'
            );
        }
        $oneCateData =  $this->getGoodsCate(1, 1);
        $allClass = [];
        //$allCity = $cityArr;
        // 查询最新卡券
        $lastBatch = $this->get_new_batch();
        foreach($lastBatch as $ck=>$cal){
            $batchimg = json_decode($lastBatch[$ck]['batch_img'],true);
            $lastBatch[$ck]['batch_img'] =  get_upload_url($batchimg[0]) == NULl?get_upload_url($lastBatch[$ck]['batch_img']):get_upload_url($batchimg[0]);
        }
        foreach($oneCateData as $key=>$value){
            $allClass[] = array(
                    "id"=>$key,
                    "name"=>$value,
                    "list" =>  $this->getGoodsCate($key, 2)
            );
        }

        $data=array(
                "allType" => $goods_type_arr,
                "allCity" => $this->city_arr,
                "allPrice" => $this->mk_price_arr,
                "allClass" => $allClass,
                "cardInfo" => $list,
                "cardInfo_right" => $lastBatch,
                "totleNum" => $mapcount[0]['count']
        );

        $this->ajaxReturn(array('status' => 0,'info' => $data),'JSON');

    }

    // 交易大厅首页-卡券
    public function build() {

        $this->display();
    }

    //发布卡券第一步
    public function publishChoose(){
        $this->checkLogin();//校验登陆
        $map = array(
                'g.goods_type' => array('in','0,1,2,3'),
                'g.source' => '0',
                'g.status' => '0',
                'g.remain_num' => array('gt','0'),
                'g.node_id' => $this->nodeId,
                'h.id' => array('exp','is null')
        );
        import("ORG.Util.Page");
        $count = M('tgoods_info g')->join('thall_goods h ON g.goods_id=h.goods_id')->where($map)->count();
        $p = new Page($count, 10);
        $list = M('tgoods_info g')->field('g.*')
                ->join('thall_goods h ON g.goods_id=h.goods_id')
                ->where($map)
                ->order('g.add_time DESC')
                ->limit($p->firstRow . ',' . $p->listRows)
                ->select();
        //dump($list);exit;
        // 分页显示
        $page = $p->show();
        $goodsModel = D('Goods');
        $goodsType = $goodsModel->getGoodsType();
        $this->assign('list',$list);
        $this->assign('goodsType', $goodsType);
        $this->assign('page',$page);
        $this->display();
    }

    /**
     * 发布卡券第二步
     */
    public function publishSubmit(){
        $this->checkLogin();//校验登陆
        $goodsId = I('goods_id',null);
        $actionFlag = empty($goodsId) ? '2' : '1';//1为发布 2为创建发布
        if($this->ispost()){
            $data = I('post.');
            $goodsModel = D('Goods');
            switch($actionFlag){
                case '1'://发布
                    //创建发布数据
                    $pData = array(
                            'goods_cat' => $data['cate2'],
                            'batch_amt' => $data['show_price'],
                            'cg_name' => $data['cg_name'],
                            'cg_mail' => $data['cg_mail'],
                            'cg_phone' => $data['cg_phone'],
                            'cg_mark' => $data['cg_mark'],
                            'batch_img' => $data['batch_img'],
                            'use_rule' => $data['show_batch_desc'],
                            'invoice_type' => $data['invoice_type'],
                            'user_id' => $this->userId,
                            'client_id' => $this->clientId
                    );
                    $goodsModel->startTrans();
                    $resutl = $goodsModel->publishNumGoods($goodsId,$this->nodeId,$pData);
                    if(!$resutl){
                        $goodsModel->rollback();
                        $this->error($goodsModel->getError());
                    }
                    $goodsModel->commit();
                    $this->success('发布成功');
                    break;
                case '2'://创建并发布
                    //创建电子券
                    $goodsData = array(
                            'goods_name'     => $data['name'],
                            'storage_num'    => $data['goods_num'],
                            'goods_type'     => $data['goods_type'],
                            'goods_image'    => $data['batch_img'][0],
                            'goods_amt'      => $data['price'],
                            'validate_type'  => $data['validate_type'],
                            'online_verify_flag' => $data['online_verify_flag'],
                            'goods_discount' => $data['discount'],
                            'pos_group_type' => $data['shop'],
                            'openStores'     => $data['openStores'],
                            'print_text'     => $data['print_text'],
                            'client_id'      => $this->clientId,
                            'user_id'        => $this->userId,
                            'is_saiWsan'     => $data['is_saiWsan']
                    );
                    $goodsModel->startTrans();
                    $goodsId = $goodsModel->addNumGoods($this->nodeId,$goodsData);
                    if($goodsId === false){
                        $goodsModel->rollback();
                        $this->error($goodsModel->getError());
                    }
                    //发布
                    $pData = array(
                            'goods_cat' => $data['cate2'],
                            'batch_amt' => $data['show_price'],
                            'cg_name' => $data['cg_name'],
                            'cg_mail' => $data['cg_mail'],
                            'cg_phone' => $data['cg_phone'],
                            'cg_mark' => $data['cg_mark'],
                            'batch_img' => $data['batch_img'],
                            'use_rule' => $data['show_batch_desc'],
                            'invoice_type' => $data['invoice_type'],
                            'user_id' => $this->userId,
                            'client_id' => $this->clientId
                    );
                    $resutl = $goodsModel->publishNumGoods($goodsId,$this->nodeId,$pData);
                    if($resutl === false){
                        $goodsModel->rollback();
                        $this->error($goodsModel->getError());
                    }
                    $goodsModel->commit();
                    $this->success('创建并发布成功');
                    break;
                default:
                    $this->error('未知的操作类型');
            }
            exit;
        }
        if($actionFlag == '1'){
            $goodsInfo = M('tgoods_info')->field("goods_name,goods_image")->where("goods_id='{$goodsId}'")->find();
        }
        $goodsCate = M('tgoods_category')->where("level=1")->select();
        $this->assign('goodsCate', $goodsCate);
        $this->assign('goodsInfo',$goodsInfo);
        $this->display();
    }

    /**
     * 发布第3步
     */
    function publishSuccess(){
        //热销
        $hallModel = D('Hall');
        $hotGoodsList = $hallModel->getHotGoods();
        $this->assign('hotGoodsList',$hotGoodsList);
        $this->display();
    }

    // ajax获取卡券类目
    public function ajaxGoodsCate() {
        $id = I('id', null, 'mysql_real_escape_string');
        $cateData = M('tgoods_category')->where("parent_code='{$id}'")->select();
        if ($cateData) {
            $this->ajaxReturn($cateData, '', 1);
        }
        return null;
    }

    //获取门店
    public function shopList(){
        R('WangcaiPc/NumGoods/shopList');
    }
    //校验是否开通线上提领门店
    public function OnlineStoreStatus(){
        R('WangcaiPc/NumGoods/OnlineStoreStatus');
    }

    public function goods_favorite($id) {
        // 校验商品有效性

        // 查询是否有收藏
        $wh = array(
                'node_id' => $this->nodeId,
                'fav_type' => '0',
                'relation_id' => $id);
        $info = D('tnode_favorite')->where($wh)->find();
        if ($info) {
            // $this->success('您已经收藏过了！');

            // $this->assign("id",$id);
            // $this->display("havefav");
            $this->success('收藏成功！');
            exit();
        } else {
            $data = array(
                    'node_id' => $this->nodeId,
                    'fav_type' => '0',
                    'relation_id' => $id,
                    'add_user' => $this->userId,
                    'add_time' => date('YmdHis'));

            $flag = D('tnode_favorite')->add($data);
            if ($flag === false) {

                /*
                 * $msg="收藏失败,数据库异常!"; $this->assign("msg",$msg);
                 * $this->display("openmsg");
                 */
                $this->error('收藏失败，请重试！');
                exit();
            } else {

                /*
                 * $msg="收藏成功"; $this->assign("msg",$msg);
                 * $this->display("openmsg");
                 */
                $this->success('收藏成功！');
                exit();
            }
        }
    }

    public function cl_goods_favorite($id) {
        // 查询是否有收藏
        $wh = array(
                'node_id' => $this->nodeId,
                'fav_type' => '0',
                'relation_id' => $id);
        $flag = D('tnode_favorite')->where($wh)->delete();
        if ($flag === false) {
            $this->error('取消收藏失败，请重试！');

            /*
             * $msg="取消收藏成功"; $this->assign("msg",$msg);
             * $this->display("openmsg");
             */
            exit();
        } else {
            $this->success('取消收藏成功！');
            /*
             * $msg="取消收藏失败，数据库异常"; $this->assign("msg",$msg);
             * $this->display("openmsg");
             */
            exit();
        }
    }

    // 预订卡券
    public function book_batch() {
        $this->checkLogin();//检查登陆
        $id = I('get.batch_id', null, 'trim');
        $map = array(
                'a.id' => $id,
                'a.goods_id' => array(
                        'exp',
                        '=b.goods_id'),
                'a.node_id' => array(
                        'exp',
                        '=n.node_id'));
        $goodsInfo = M()->table('thall_goods a, tgoods_info b ,tnode_info n')
                ->field('b.goods_type,n.node_name,a.*')
                ->where($map)
                ->find();
        // dump($goodsInfo);
        // 该商户热门的3个卡券
        $otherList = M('thall_goods')->field(
                'id,batch_name,batch_img,batch_amt,visit_num')
                ->where(
                        "status='0' AND node_id='{$goodsInfo['node_id']}' AND id <> '{$goodsInfo['id']}'")
                ->order('visit_num desc')
                ->limit(3)
                ->select();
        if (empty($otherList)) {
            $otherList = M('thall_goods')->field(
                    'id,batch_name,batch_img,batch_amt,visit_num')
                    ->where("status='0' AND id <> '{$goodsInfo['id']}'")
                    ->order('hall_top_time desc')
                    ->limit(3)
                    ->select();
        }
        $this->assign('goodsInfo', $goodsInfo);
        $this->assign("id", $id);
        $this->assign('email', $this->user_name);
        $this->assign('otherList', $otherList);
        $this->assign('hallModel',D('Hall'));
        $this->display();
    }

    // 提交预订
    public function book_submit_batch() {
        $this->checkLogin();//检查登陆
        $error = '';
        $hallId = I('batch_id', null, 'mysql_real_escape_string');
        $tel = I('tel', null);
        if (! check_str($tel,array('null' => false,'strtype' => 'number'), $error)) {
            $this->error("联系电话{$error}");
        }
        $email = I('email');
        if (! check_str($email,array('null' => false,'strtype' => 'email'), $error)) {
            $this->error("联系邮箱{$error}");
        }
        $linkman = I('linkman');
        if (! check_str($linkman,array('null' => false,'maxlen_cn' => '32'), $error)) {
            $this->error("联系人{$error}");
        }
        $remark = I('remark');
        if (! check_str($remark,array('null' => true,'maxlen_cn' => '250'), $error)) {
            $this->error("留言{$error}");
        }
        $bookNum = I('bookNum');
        if (! check_str($bookNum,array('null' => false,'strtype' => 'int','minval' => '1'), $error)) {
            $this->error("采购数量{$error}");
        }
        $map = array(
                'a.id' => $hallId,
                'a.goods_id' => array('exp','=b.goods_id')
        );
        $hallInfo = M('thall_goods a, tgoods_info b')->field("a.*,b.goods_type,b.goods_id")
                ->where($map)
                ->find();
        if (! $hallInfo) $this->error('参数错误！请刷新商品页，重新预订！');
        if ($hallInfo['node_id'] == $this->nodeId) $this->error('表闹~自己不要预订自己的商品！');
        if ($hallInfo['belongs_note'] == '1') {
            $numCity = I('num_city');
            if (! check_str($numCity,array('null' => false), $error)) {
                $this->error("请选择可用城市");
            }
            if ($numCity == '1') {
                $payPrice = $bookNum * $hallInfo['batch_amt'];
                if ($payPrice < 10000000) $this->error('总金额不得低于1000万元');
            } else {
                $numCityName = I('num_city_name');
                if (! check_str($numCityName,array('null' => false), $error)) {
                    $this->error("请填写可用城市名称");
                }
            }
        }
        $goodsModel = D('Goods');
        if ($hallInfo['belongs_note'] == '1') { // 麦当劳卡券订购
            $sendGoodsName = $goodsModel->getGoodsType($hallInfo['goods_type']);
            $sendCity = $numCity == '1' ? '全国' : $numCityName;
            // 邮件通知
            $sendMailContent = "卡券名称:{$hallInfo['batch_name']}<br/>类型:{$sendGoodsName[$hallInfo['goods_type']]}<br/>可用城市:{$sendCity}<br/>采购价:{$hallInfo['batch_amt']}<br/>采购数量:{$bookNum}<br/>
								采购联系人:{$linkman}<br/>采购邮箱:{$email}<br/>采购电话:{$tel}<br/>采购留言:{$remark}<br/>供货联系人:{$hallInfo['cg_name']}<br/>供货人电话:{$hallInfo['cg_phone']}<br/>供货人邮箱:{$hallInfo['cg_mail']}<br/>采购条件:{$hallInfo['cg_mark']}";
            $mailData = array(
                    'subject' => "卡券大厅麦当劳卡券采购:求购商户:{$this->nodeId}",
                    'content' => $sendMailContent,
                    'email' => C('HALL_MDLSENDMAIL')
            );
            send_mail($mailData);
        } else { // 一般流程卡券采购流程

            $orderId = get_sn();
            $data = array(
                    'order_id' => $orderId,
                    'hall_id' => $hallId,
                    'add_user' => $this->userId,
                    'node_id' => $this->nodeId,
                    'add_time' => date('YmdHis'),
                    'linkman' => $linkman,
                    'tel' => $tel,
                    'email' => $email,
                    'remark' => $remark,
                    'book_num' => $bookNum,
                    'goods_price' => $hallInfo['batch_amt'],
                    'check_end_time' => date('YmdHis', time() + (3 * 24 * 3600)),  // 3天自动过期
                    'order_end_time' => date('YmdHis', time() + (6 * 24 * 3600)),  // 3天自动过期
                    'goods_node_id' => $hallInfo['node_id'],
                    'profit_status' => '0');
            if (in_array($hallInfo['node_id'], C('HALL_NOCHECKED_NODE'))) {
                $data['status'] = '1';
            }
            $result = M('tnode_goods_book')->add($data);
            if (! $result) $this->error('系统出错订单生成失败');
            // 特殊商户直接跳转付款页面
            if (in_array($hallInfo['node_id'], C('HALL_NOCHECKED_NODE')) && $hallInfo['batch_amt'] > '0') {
                $goodsModel = D('Goods');
                $goodsModel->startTrans();
                // 扣减供货商库存
                $reduc = $goodsModel->storagenum_reduc($hallInfo['goods_id'],$bookNum, '订单确认', '14');
                if (! $reduc) {
                    $goodsModel->rollback();
                    $this->error('库存扣减失败:' . $goodsModel->getError());
                }
                $goodsModel->commit();
                // 跳转到支付页面
                $data = array(
                        'status' => '1',
                        'info' => '订购成功',
                        'url' => U('WangcaiPc/OnlineTrading/orderPayConfirm',array('order_id' => $orderId), '', '', true)
                );
                $this->ajaxReturn($data);
                exit();
            }
            // 邮件通知
            $goodsModel = D('Goods');
            $sendGoodsName = $goodsModel->getGoodsType($hallInfo['goods_type']);
            $sendPrice = $hallInfo['batch_amt'];
            $sendMailContent = "卡券名称:{$hallInfo['batch_name']}<br/>类型:{$sendGoodsName[$hallInfo['goods_type']]}<br/>采购价:{$sendPrice}<br/>采购数量:{$bookNum}<br/>
			采购联系人:{$linkman}<br/>采购邮箱:{$email}<br/>采购电话:{$tel}<br/>采购留言:{$remark}<br/>供货联系人:{$hallInfo['cg_name']}<br/>供货人电话:{$hallInfo['cg_phone']}<br/>供货人邮箱:{$hallInfo['cg_mail']}<br/>采购条件:{$hallInfo['cg_mark']}";
            $mailData = array(
                    'subject' => "卡券大厅预定:采购商:{$this->nodeId}-供应商:{$hallInfo['node_id']}",
                    'content' => $sendMailContent,
                    'email' => C('HALL_OTHERSENDMAIL'));
            send_mail($mailData);
        }
        $this->success('订购成功');
    }

    // 商品详情
    public function goods($goods_id) {
        if ($goods_id != "") {

            $map['A.id'] = $goods_id;

            // 查询最新卡券
            $BatchInfo = M()->table('thall_goods A')
                    ->field(
                            'A.id,A.batch_name,A.sell_num_note,A.batch_short_name,A.batch_desc,N.node_name,A.node_pos_group,
					     A.node_id,A.batch_img,A.batch_amt,A.end_time,A.visit_num,A.status,A.invoice_type,A.cg_mark,A.is_agent,tg.goods_type,tg.pos_group,tg.pos_group_type,A.param1,
					     (select count(*) from tnode_favorite B where B.relation_id=A.id and B.fav_type="0") as count,A.city_note,A.node_name_note')
                    ->join('tnode_info N ON N.node_id=A.node_id ')
                    ->join('tgoods_info tg ON tg.goods_id=A.goods_id ')
                    ->where($map)
                    ->order('A.id desc')
                    ->find();

            if (! empty($BatchInfo)) {
                // 查询城市
                $cityArr = array();
                if ($BatchInfo['pos_group_type'] == '1') { // 商户型终端
                    // 根据node_pos_group获取所有机构(包括下级机构)，根据机构到tstore_info去distinct
                    // city_code
                    $nodelist = M()->query(
                            "SELECT distinct node_id FROM tgroup_pos_relation where group_id={$BatchInfo['node_pos_group']}");
                    if (! empty($nodelist)) {
                        foreach ($nodelist as $nk => $nal) {
                            // 根据机构到tstroe_info查找城市
                            $citylist = M()->query(
                                    "SELECT distinct t.city_code,c.city FROM tstore_info t left join tcity_code c on c.city_code=t.city_code where node_id='{$nal['node_id']}' and c.city_level='2'");
                            if (! empty($citylist)) {
                                foreach ($citylist as $cal) {
                                    $cityArr[$cal['city_code']] = $cal['city'];
                                }
                            }
                        }
                    }
                } else { // 终端型
                    $storelist = M()->query(
                            "SELECT distinct store_id,node_id FROM tgroup_pos_relation where group_id={$BatchInfo['node_pos_group']} and node_id='{$BatchInfo['node_id']}'");
                    // 根据storelist查找城市
                    if ($storelist) {
                        foreach ($storelist as $sk => $sal) {
                            $citylist = M()->query(
                                    "SELECT distinct t.city_code,c.city FROM tstore_info t left join tcity_code c on c.city_code=t.city_code where t.node_id='{$sal['node_id']}' and t.store_id={$sal['store_id']} and c.city_level='2'");
                            if (! empty($citylist)) {
                                foreach ($citylist as $ck => $cal) {
                                    $cityArr[$cal['city_code']] = $cal['city'];
                                }
                            }
                        }
                    }
                }
                $citystr = "";
                if (! empty($cityArr)) {
                    foreach ($cityArr as $cal) {
                        $citystr .= "<li>" . $cal . "</li>";
                    }
                }
                if ($BatchInfo['city_note'] == '1') {
                    $citystr = "<li>全国</li>";
                }
                // 赋值城市
                $BatchInfo['citystr'] = $citystr;
            }
        } else {
            $this->error('参数错误，卡券ID不能为空！');
        }
        // 特殊卡券(我司定死的)商户名称
        $BatchInfo['node_name'] = empty($BatchInfo['node_name_note']) ? $BatchInfo['node_name'] : $BatchInfo['node_name_note'];
        // 将时间转换为Y-m-d格式
        $BatchInfo['end_time'] = Date("Y-m-d",
                strtotime(substr($BatchInfo['end_time'], 0, - 6)));

        // 访问量+1
        $tmodel = M('thall_goods');
        $map_ = array(
                $goods_id,
                'id' => $goods_id);
        $clickadd = $tmodel->where($map_)->setInc('visit_num', 1);
        $goodsModel = D('Goods');
        $goods_types = $goodsModel->getGoodsType();
        $lastBatch = $this->get_new_batch();
        // 最近浏览cookie设置
        $hallModel = D('Hall');
        $hallModel->setHallCookie($goods_id, 'hall_browsed_goods');
        $this->assign('lastBatch', $lastBatch);
        $this->assign('BatchInfo', $BatchInfo);
        $this->assign('goods_types', $goods_types);
        $this->assign('favFlag',
                D('tnode_favorite')->where(
                        "node_id = '{$this->nodeId}' and relation_id = '{$goods_id}' and fav_type = '0'")
                        ->count() > 0);
        $this->assign('invoiceTypeArr',D('Hall')->getInvoicePayType());
        $this->assign('hallModel',$hallModel);
        $this->display();
    }

    /*
     * 求购留言
     */
    public function purchaseMessage() {
        if ($this->isPost()) {
            $error = '';
            $title = I('title');
            if (! check_str($title,
                    array(
                            'null' => false,
                            'maxlen_cn' => '24'), $error)) {
                $this->error("留言标题{$error}");
            }
            $content = I('content');
            if (! check_str($content,
                    array(
                            'null' => false,
                            'maxlen_cn' => '100'), $error)) {
                $this->error("留言内容{$error}");
            }
            $phone = I('phone');
            if (! check_str($phone,
                    array(
                            'null' => false), $error)) {
                $this->error("联系方式{$error}");
            }
            $nodeName = M('tnode_info')->where("node_id={$this->nodeId}")->getField(
                    'node_name');
            // 邮件通知
            $sendMailContent = "留言标题:{$title}<br/>留言内容:{$content}<br/>留言电话:{$phone}<br/>商户:{$nodeName}<br/>商户号:{$this->nodeId}";
            $mailData = array(
                    'subject' => "卡券大厅留言求购:求购商户:{$nodeName}",
                    'content' => $sendMailContent,
                    'email' => C('HALL_PURCHASE_MESSAGE'));
            send_mail($mailData);
            $data = array(
                    'node_id' => $this->nodeId,
                    'liuyan_type' => '2',
                    'liuyan_title' => $title,
                    'liuyan_content' => $content,
                    'lianxifangshi' => $phone,
                    'screen' => '0',
                    'add_time' => date('YmdHis'));
            $result = M('twc_board')->add($data);
            if ($result) {
                $this->success('留言成功');
            } else {
                $this->error('系统出错');
            }
        }
        $this->display();
    }

    public function city_py() {
        $list = D('tcity_code')->field('city_code, city')
                ->where("city_level = '2'")
                ->select();
        $arr = array();

        foreach ($list as $info) {
            $info['city'] = str_replace('市', '', $info['city']);
            $city = $info['city'];
            $len = strlen($city);
            for ($i = 0; $i < $len; $i = $i + 3) {
                $word = substr($city, $i, 3);
                $py = Pinyin(substr($city, $i, 3), 1);
                $fpy = substr($py, 0, 1);
                if ($i == 0) {
                    $info['py_first'] = $fpy;
                }
                $info['py'] .= $py;
                $info['py_short'] .= $fpy;
            }
            $arr[] = $info;
        }

        return $arr;
    }

    // 城市弹出框
    public function city_dialog() {
        $callback = I('get.call_back', null, 'trim');
        // 有缓存
        if (S('city')) {
            $citylist = S('city');
        } else {
            $citylist = $this->city_py();
            S('city', $citylist, 3600);
        }

        // 分组排序城市
        $newcity = array();
        if (! empty($citylist)) {

            foreach ($citylist as $ck => $cal) {
                if ($cal['py_first'] != "") {
                    $newcity[$cal['py_first']][$ck] = $cal;
                }
            }
        }

        // print_r($newcity);

        // ABCD
        $ABCD_Arr = array();
        // EFGH
        $EFGH_Arr = array();
        // JKLM
        $JKLM_Arr = array();
        // NOPQRS
        $NOPQRS_Arr = array();
        // TUVWX
        $TUVWX_Arr = array();
        // YZ
        $YZ_Arr = array();

        // 按字母排序
        if (! empty($newcity)) {
            foreach ($newcity as $k => $v) {

                if ($k == 'a' || $k == 'b' || $k == 'c' || $k == 'd') {
                    $k = strtoupper($k);
                    $ABCD_Arr[$k] = $v;
                } elseif ($k == 'e' || $k == 'f' || $k == 'g' || $k == 'h') {
                    $k = strtoupper($k);
                    $EFGH_Arr[$k] = $v;
                } elseif ($k == 'j' || $k == 'k' || $k == 'l' || $k == 'm') {
                    $k = strtoupper($k);
                    $JKLM_Arr[$k] = $v;
                } elseif ($k == 'n' || $k == 'o' || $k == 'p' || $k == 'q' ||
                        $k == 'r' || $k == 's') {
                    $k = strtoupper($k);
                    $NOPQRS_Arr[$k] = $v;
                } elseif ($k == 't' || $k == 'u' || $k == 'v' || $k == 'w' ||
                        $k == 'x') {
                    $k = strtoupper($k);
                    $TUVWX_Arr[$k] = $v;
                } elseif ($k == 'y' || $k == 'z') {
                    $k = strtoupper($k);
                    $YZ_Arr[$k] = $v;
                }
            }
        }

        ksort($ABCD_Arr);
        ksort($EFGH_Arr);
        ksort($JKLM_Arr);
        ksort($NOPQRS_Arr);
        ksort($TUVWX_Arr);
        ksort($YZ_Arr);

        $this->assign("ABCD_Arr", $ABCD_Arr);
        $this->assign("EFGH_Arr", $EFGH_Arr);
        $this->assign("JKLM_Arr", $JKLM_Arr);
        $this->assign("NOPQRS_Arr", $NOPQRS_Arr);
        $this->assign("TUVWX_Arr", $TUVWX_Arr);
        $this->assign("YZ_Arr", $YZ_Arr);

        $this->assign("city_arr", $this->city_arr);

        $this->assign("callback", $callback);

        $this->display();
    }

    // 最新卡券
    public function get_new_batch() {

        // $map['A.batch_type']='3';
        $map['A.check_status'] = '1';
        $map['A.status'] = array(
                'eq',
                '0');
        if(empty($this->userInfo['node_id'])){
            $map['S.goods_type']  = array('not in','15,7');

        }
        // 查询最新卡券
        $lastBatch = M()->table('thall_goods A')
                ->field(
                        'A.id,A.city_note,A.label_note,A.sell_num_note,A.batch_name,A.node_pos_group,A.node_id,A.batch_img,A.batch_amt,A.visit_num, A.param1,G.group_type,(select count(*) from tnode_favorite B where B.relation_id=A.id and  b.fav_type="0") as count')
                ->join(
                        'tpos_group G ON G.node_id=A.node_id and G.group_id=A.node_pos_group')
                ->join(
                        'tgoods_info S ON S.goods_id=A.goods_id')
                ->where($map)
                ->order('A.id desc')
                ->limit(3)
                ->select();
        if (! empty($lastBatch)) {
            foreach ($lastBatch as $lk => $lal) {

                $citystr = array();
                if ($lal['group_type'] == '0') { // 商户型终端
                    // 根据node_pos_group获取所有机构，根据机构到tstore_info去distinct
                    // city_code
                    $nodelist = M()->query(
                            "SELECT distinct node_id FROM tgroup_pos_relation where group_id={$lal['node_pos_group']}");
                    if (! empty($nodelist)) {
                        foreach ($nodelist as $nk => $nal) {
                            // 根据机构到tstroe_info查找城市
                            $citylist = M()->query(
                                    "SELECT distinct t.city_code,c.city FROM tstore_info t left join tcity_code c on c.city_code=t.city_code where node_id='{$nal['node_id']}' and c.city_level='2'");
                            if (! empty($citylist)) {
                                foreach ($citylist as $ck => $cal) {

                                    $citystr[$cal['city_code']] = $cal['city'];
                                }
                            }
                        }
                    }
                } else { // 终端型
                    $storelist = M()->query(
                            "SELECT distinct store_id,node_id FROM tgroup_pos_relation where group_id={$lal['node_pos_group']} and node_id='{$lal['node_id']}'");
                    // 根据storelist查找城市
                    if ($storelist) {
                        foreach ($storelist as $sk => $sal) {
                            $citylist = M()->query(
                                    "SELECT distinct t.city_code,c.city FROM tstore_info t left join tcity_code c on c.city_code=t.city_code where t.node_id='{$sal['node_id']}' and t.store_id={$sal['store_id']} and c.city_level='2'");
                            if (! empty($citylist)) {
                                foreach ($citylist as $ck => $cal) {

                                    $citystr[$cal['city_code']] = $cal['city'];
                                }
                            }
                        }
                    }
                }
                if ($lal['city_note'] == '1') {
                    $citystr = array(
                            "全国");
                }
                // 赋值城市
                sort($citystr);
                if(count($citystr)>4){
                    $citystr = array_slice($citystr ,0 ,4);
                    $citystr[4] = '......';
                }
                $lastBatch[$lk]['citystr'] = $citystr;
            }
        }

        return $lastBatch;
    }

    /*
     * 获得卡券行业类型 $code 当type为1时该值为tgoods_category表中level的值,当type为2时该值为code值 $type
     * 1-获取指定级别的类目(一级或二级) 2-获取指定父分类下的子分类 返回一维数组
     */
    public function getGoodsCate($code, $type) {
        $cateData = F('cateDataCache');
        if (empty($cateData)) {
            $cateData = M('tgoods_category')->select();
            F('cateDataCache', $cateData); // 加入文件缓存
        }
        $cateArr = array();
        switch ($type) {
            case 1:
                foreach ($cateData as $v) {
                    if ($v['level'] == $code) {
                        $cateArr[$v['path']] = $v['name'];
                    }
                }
                break;
            case 2:
                foreach ($cateData as $v) {
                    if ($v['parent_code'] == $code) {
                        $cateArr[$v['path']] = $v['name'];
                    }
                }
                break;
            default:
                $cateArr = $cateData;
        }
        return $cateArr;
    }

    public function getcityCode() {
        $cityData = F('cityDataCache');
        if (empty($cityData)) {
            $data = M('tcity_code')->field('city_code,city')
                    ->where("city_level='2'")
                    ->select();
            // 变为一维数组
            $cityData = array();
            foreach ($data as $v) {
                $cityData[$v['city_code']] = $v['city'];
            }
            F('cityDataCache', $cityData); // 加入文件缓存
        }
        return $cityData;
    }

    // 电子交易大厅帮助
    public function hallHelp() {
        import("ORG.Util.Page");
        $type = I('type');
        $likeSea = I('likeSea');
        $whe = array(
                'parent_class_id' => 44,
                'status' => 1);
        if ($likeSea != '') {
            $whe['_string'] = '(news_name like "%' . $likeSea . '%")';
        }
        switch ($type) {
            case 1:
                $class_name = '常见问题';
                $whe['class_id'] = 59;
                break;
            case 2:
                $class_name = '交易大厅公告';
                $whe['class_id'] = 58;
                break;
            case 3:
                $class_name = '交易大厅规则';
                $whe['class_id'] = 55;
                break;
            default:
                $class_name = '常见问题';
                $whe['class_id'] = 59;
        }
        $count = M('tym_news')->where($whe)->count();
        $p = new Page($count, 30);
        $page = $p->show();
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        $problem = M('tym_news')->where($whe)
                ->order('news_id asc')
                ->limit($p->firstRow . ',' . $p->listRows)
                ->select();
        // echo M()->getLastSql();
        $this->assign('type', $type);
        $this->assign('class_name', $class_name);
        $this->assign('page', $page);
        $this->assign('problem', $problem);
        $this->assign('post', $_REQUEST);
        $this->display();
    }
    // 交易大厅帮助详情
    public function hallHelpView() {
        $news_id = I('news_id');
        $leftId = I('leftId');
        $class_name = I('class_name');
        $type = I('type');
        switch ($type) {
            case 1:
                $class_name = '常见问题';
                break;
            case 2:
                $class_name = '交易大厅公告';
                break;
            case 3:
                $class_name = '交易大厅规则';
                break;
            default:
                $class_name = '常见问题';
        }
        $list = M('tym_news')->where(
                array(
                        'news_id' => $news_id))->find();
        $this->assign('type', $type);
        $this->assign('leftId', $leftId);
        $this->assign('class_name', $class_name);
        $this->assign('list', $list);
        $this->assign('post', $_REQUEST);
        $this->display();
    }

    // 大厅留言板
    public function hallMessage() {
        $where = array(
                'b.screen' => '0',
                'b.liuyan_type' => '2');
        import('ORG.Util.Page');
        $count = M()->table("twc_board b")->field('b.*,n.node_name')
                ->join('tnode_info n on b.node_id=n.node_id')
                ->where($where)
                ->count();
        $Page = new Page($count, 15);
        $show = $Page->show();

        $list = M()->table("twc_board b")->field('b.*,n.node_name')
                ->join('tnode_info n on b.node_id=n.node_id')
                ->where($where)
                ->order('id DESC')
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->select();
        $star = $this->twc_star();
        $this->assign("star", $star);
        $this->assign("list", $list);
        $this->assign("show", $show);
        $this->display();
    }

    // 留言详情页
    public function hallMessageView() {
        $id = I('id', null);
        $where = array(
                'b.screen' => '0',
                'b.liuyan_type' => '2',
                'b.id' => $id);
        $info = M()->table("twc_board b")->field('b.*,n.node_name')
                ->join('tnode_info n on b.node_id=n.node_id')
                ->where($where)
                ->find();
        $star = $this->twc_star();
        $this->assign("star", $star);
        $this->assign('info', $info);
        $this->display();
    }
    // 获取明星商户
    public function twc_star() {
        $model = M('twc_star');
        $list = array();
        $star = $model->order('id DESC')->select();

        if ($star) {
            foreach ($star as $k => $v) {
                if ($v['trade'] == '0') {
                    $list['0'][] = $v;
                } else if ($v['trade'] == '1') {
                    $list['1'][] = $v;
                } else if ($v['trade'] == '2') {
                    $list['2'][] = $v;
                }
            }
        }

        return $list;
    }
    // 给明星商户留言
    public function submit_contactnode() {
        // 被联系机构号
        $contact_node = I("contact_node");
        $current_node = $this->user_Info['node_id'];
        $contact_msg = I("contact_msg");
        $screen = M('tnode_info')->where(
                array(
                        'node_id' => $current_node))->getField('screen');
        if ($screen == '1') {
            exit(
            json_encode(
                    array(
                            'code' => '0',
                            'codeText' => '您已经被禁言了！！！')));
        }
        if (empty($this->user_Info)) {
            // $this->error("请先登录后提交留言！");
            exit(
            json_encode(
                    array(
                            'code' => '0',
                            'codeText' => '请先登录后提交留言！')));
        }

        $screen = M('tnode_info')->where(
                array(
                        'node_id' => $current_node))->getField('screen');
        if ($screen == '1') {
            exit(
            json_encode(
                    array(
                            'code' => '0',
                            'codeText' => '您已经被禁言了！！！')));
        }

        if ($contact_node == "") {
            // $this->error("机构参数错误！");
            exit(
            json_encode(
                    array(
                            'code' => '0',
                            'codeText' => '机构参数错误！')));
        }

        if ($current_node == "") {
            exit(
            json_encode(
                    array(
                            'code' => '0',
                            'codeText' => '机构参数错误！')));
        }

        // 查询被联系机构用户ID
        $userID = M()->table('tuser_info n ')
                ->field("n.user_id")
                ->where("node_id='" . $contact_node . "'")
                ->find();

        $data = array(
                "message_text" => $contact_msg,
                "send_node_id" => $this->userInfo['node_id'],
                "send_user_id" => $this->userInfo['user_id'],
                "receive_node_id" => $contact_node,
                "receive_user_id" => $userID['user_id'],
                "ck_status" => '2',
                "status" => '4',
                "reply_id" => time(),
                "laiyuan_type" => '2',
                "add_time" => date('YmdHis'));
        $insertok = M('tmessage_info')->add($data);
        if ($insertok) {
            exit(
            json_encode(
                    array(
                            'code' => '1',
                            'codeText' => '发送成功！！！')));
        } else {
            // $this->error("发送消息失败，入库错误！");
            exit(
            json_encode(
                    array(
                            'code' => '0',
                            'codeText' => '发送消息失败，入库错误！')));
        }
    }

    // 交易大厅员工福利活动页面
    public function employeeActivity() {
        // $hallId =
        // '20055,20068,20069,20190,20075,20071,20056,20067,20074,20168,20274';
        $hallId = C('HALL_ACTIVITY_CONFIG.EMPLOYEE_HALL_ID');
        $map = array(
                't.id' => array(
                        'in',
                        $hallId));
        $list = M()->table("thall_goods t")->field(
                't.id,t.batch_name,t.batch_img,t.batch_amt,t.visit_num,(SELECT COUNT(*) FROM tnode_favorite WHERE relation_id = t.id AND fav_type = "0") AS fav_num')
                ->where($map)
                ->select();
        $topList = array(); // 顶部3个卡券
        $topIdArr = array(
                '20055',
                '20068',
                '20069');
        $topList[] = M()->table("thall_goods t")->field(
                't.id,t.batch_name,t.batch_img,t.batch_amt,t.visit_num,(SELECT COUNT(*) FROM tnode_favorite WHERE relation_id = t.id AND fav_type = "0") AS fav_num')
                ->where("t.id='{$topIdArr[0]}'")
                ->find();
        $topList[] = M()->table("thall_goods t")->field(
                't.id,t.batch_name,t.batch_img,t.batch_amt,t.visit_num,(SELECT COUNT(*) FROM tnode_favorite WHERE relation_id = t.id AND fav_type = "0") AS fav_num')
                ->where("t.id='{$topIdArr[1]}'")
                ->find();
        $topList[] = M()->table("thall_goods t")->field(
                't.id,t.batch_name,t.batch_img,t.batch_amt,t.visit_num,(SELECT COUNT(*) FROM tnode_favorite WHERE relation_id = t.id AND fav_type = "0") AS fav_num')
                ->where("t.id='{$topIdArr[2]}'")
                ->find();
        $this->assign('topList', $topList);
        $this->assign('list', $list);
        $this->display();
    }

    // 交易大厅回馈活动页面
    public function feedbackActivity() {
        // $hallId =
        // '20057,20193,20285,20283,20284,20281,20352,20332,20347,20100';
        $hallId = C('HALL_ACTIVITY_CONFIG.FEEDBACK_HALL_ID');
        $map = array(
                't.id' => array(
                        'in',
                        $hallId));
        $list = M()->table("thall_goods t")->field(
                't.id,t.batch_name,t.batch_img,t.batch_amt,t.visit_num,(SELECT COUNT(*) FROM tnode_favorite WHERE relation_id = t.id AND fav_type = "0") AS fav_num')
                ->where($map)
                ->select();
        $topList = array(); // 顶部2个卡券
        $topIdArr = array(
                '20057',
                '20283');
        $topList[] = M()->table("thall_goods t")->field(
                't.id,t.batch_name,t.batch_img,t.batch_amt,t.visit_num,(SELECT COUNT(*) FROM tnode_favorite WHERE relation_id = t.id AND fav_type = "0") AS fav_num')
                ->where("t.id='{$topIdArr[0]}'")
                ->find();
        $topList[] = M()->table("thall_goods t")->field(
                't.id,t.batch_name,t.batch_img,t.batch_amt,t.visit_num,(SELECT COUNT(*) FROM tnode_favorite WHERE relation_id = t.id AND fav_type = "0") AS fav_num')
                ->where("t.id='{$topIdArr[1]}'")
                ->find();
        $topList[] = M()->table("thall_goods t")->field(
                't.id,t.batch_name,t.batch_img,t.batch_amt,t.visit_num,(SELECT COUNT(*) FROM tnode_favorite WHERE relation_id = t.id AND fav_type = "0") AS fav_num')
                ->where("t.id='{$topIdArr[2]}'")
                ->find();
        $this->assign('topList', $topList);
        $this->assign('list', $list);
        $this->display();
    }

    // 交易大厅积分换礼活动页面
    public function cashGiftActivity() {
        // $hallId =
        // '20056,20055,20057,20063,20068,20075,20190,20061,20071,20230,20385,20390,20076,20414';
        $hallId = C('HALL_ACTIVITY_CONFIG.FEEDBACK_HALL_ID');
        $map = array(
                't.id' => array(
                        'in',
                        $hallId));
        $list = M()->table("thall_goods t")->field(
                't.id,t.batch_name,t.batch_img,t.batch_amt,t.visit_num,(SELECT COUNT(*) FROM tnode_favorite WHERE relation_id = t.id AND fav_type = "0") AS fav_num')
                ->where($map)
                ->select();
        $topList = array(); // 顶部2个卡券
        foreach ($list as $k => $v) {
            if (in_array($k,
                    array(
                            0,
                            1,
                            2,
                            3,
                            4,
                            5))) {
                $topList[] = $list[$k];
                unset($list[$k]);
            }
        }
        $this->assign('topList', $topList);
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 数据运维脚本:将thall_goods表中图片数据转换成json格式
     */
    public function imgToJson(){
        set_time_limit(0);
        $p = 0;
        do{
            $dataList = M('thall_goods')->field('id,batch_img')->limit($p*100,'100')->select();

            if(empty($dataList)){
                echo '没有发码数据或已完成!';exit;//没有数据中断执行
            }
            foreach($dataList as $k=>$v){
                //检查是否为json格式
                $str = substr($v['batch_img'],0,1);
                if($str == '[') continue;
                //开始转换
                $imgArr = array();
                $imgArr[] = $v['batch_img'];
                $sdata = array(
                        'batch_img' => json_encode($imgArr)
                );
                M('thall_goods')->where("id='{$v['id']}'")->save($sdata);
            }
            $p++;
        }while(true);

    }




}