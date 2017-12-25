<?php

class PointShopAction extends BaseAction {
    
    // public $_authAccessMap = '*';
    public $BATCH_TYPE = "1001";
    // 积分商城 tmarket类型
    public $CHANNEL_TYPE = "5";

    public $CHANNEL_SNS_TYPE = "55";

    public function _initialize() {
        parent::_initialize();
        // 验证是否开通服务
        $node_info = get_node_info($this->node_id);
        $hasEcshop = $this->_hasEcshop();
        if (! in_array(ACTION_NAME, 
            array(
                'O2Ohelp', 
                'O2OIntroduce'))) {
            if ($hasEcshop != true)
                $this->error("您未开通多宝电商服务模块。");
            /*
             * if (!$node_info['receive_phone']) {
             * $this->error("您的接受通知手机号为空，请至多宝电商配置处补齐。", array('返回' =>
             * 'javascript:history.go(-1)', '去配置' =>
             * U('Ecshop/BusiOption/index'))); }
             */
            /*
             * $account_info = M('tnode_account')->where(array('node_id' =>
             * $this->node_id))->select(); if (!$account_info)
             * $this->error("您的收款账户信息不完整，请至多宝电商配置处添加", array('返回' =>
             * 'javascript:history.go(-1)', '去配置' =>
             * U('Ecshop/BusiOption/index')));
             */
        }
    }

    public function index() {
        $channelInfo = M('tchannel')->where(
            array(
                'node_id' => $this->node_id, 
                'type' => '5', 
                'sns_type' => 55))->find();
        if (! $channelInfo) { // 不存在则添加渠道
                              // 营销活动不存在则新增
            $channel_arr = array(
                'name' => 'DF积分商城渠道', 
                'type' => $this->CHANNEL_TYPE, 
                'sns_type' => $this->CHANNEL_SNS_TYPE, 
                'status' => '1', 
                'start_time' => date('YmdHis'), 
                'end_time' => date('YmdHis', strtotime("+1 year")), 
                'add_time' => date('YmdHis'), 
                'click_count' => 0, 
                'cj_count' => 0, 
                'send_count' => 0, 
                'node_id' => $this->node_id);
            
            $cid = M('tchannel')->add($channel_arr);
            if (! $cid) {
                $this->error('添加默认微官网渠道号失败');
            }
        }
        $marketInfo = M('tmarketing_info')->where(
            array(
                'batch_type' => $this->BATCH_TYPE, 
                'node_id' => $this->node_id))->find();
        if (! $marketInfo) { // 不存在则自动新建该营销活动和渠道
            $m_arr = array(
                'batch_type' => $this->BATCH_TYPE, 
                'name' => 'DF积分商城', 
                'status' => '1', 
                'start_time' => date('YmdHis'), 
                'end_time' => date('YmdHis', strtotime("+10 year")), 
                'add_time' => date('YmdHis'), 
                'click_count' => 0, 
                'cj_count' => 0, 
                'send_count' => 0, 
                'node_id' => $this->node_id, 
                'batch_come_from' => session('batch_from') ? session(
                    'batch_from') : '1');
            
            $m_id = M('tmarketing_info')->add($m_arr);
            if (! $m_id) {
                $this->error('添加旺财小店失败');
            }
        }
        
        redirect(U('Df/PointShop/preview'));
        /*
         * $list =
         * M('tmarketing_info')->where(array('batch_type'=>$this->BATCH_TYPE,'node_id'=>$this->node_id))->select();
         * $this->assign('list',$list); $this->display();
         */
    }

    public function preview() {
        // 小店的m_id
        $m_info = M('tmarketing_info')->where(
            array(
                'node_id' => $this->node_id, 
                'batch_type' => '1001'))->find();
        
        $logo = M('tfb_df_pointshop_banner')->where(
            array(
                'm_id' => $m_info['id'], 
                'node_id' => $this->node_id, 
                'ban_type' => '1'))->find();
        $logo_url = C('UPLOAD') . $logo['img_url'];
        // 店铺地址
        $channel_id = M('tchannel')->where(
            array(
                'node_id' => $this->node_id, 
                'type' => '5', 
                'sns_type' => '55'))->getField('id');
        $label_id = get_batch_channel($m_info['id'], $channel_id, '1001', 
            $this->node_id);
        
        $today = date('Ymd');
        $yesterday = date('Ymd', strtotime("-1 day"));
        $batch_type = '1001';
        $batch_id = $m_info['id'];
        $_get = I('get.');
        // 查询
        $_get['begin_date'] = $begin_date = I('begin_date', 
            dateformat("-30 days", 'Ymd'));
        $_get['end_date'] = $end_date = I('end_date', 
            dateformat("0 days", 'Ymd'));
        $map = array(
            'batch_type' => $batch_type, 
            'batch_id' => $batch_id);
        
        // 查询日期
        $map['day'] = array();
        if ($begin_date != '') {
            $map['day'][] = array(
                'EGT', 
                $begin_date);
        }
        if ($end_date != '') {
            $map['day'][] = array(
                'ELT', 
                $end_date);
        }
        
        $shop_jsChartDataClick = array(); // 小店PV访问量
        $shop_jsChartDataOrder = array(); // 小店订单数
        $shop_jsChartDataAmt = array(); // 小店销售额
        $shop_data = array(
            'PV' => array(
                date('Ymd') => 0, 
                date('Ymd', strtotime("-1 day")) => 0), 
            'order' => array(
                date('Ymd') => 0, 
                date('Ymd', strtotime("-1 day")) => 0), 
            'saleamt' => array(
                date('Ymd') => 0, 
                date('Ymd', strtotime("-1 day")) => 0));
        
        // 小店访问量
        $pv_arr = M('Tdaystat')->where($map)
            ->field("batch_type,batch_id,day,sum(click_count) click_count")
            ->group("day")
            ->select();
        // 小店-计算出JS值
        foreach ($pv_arr as $v) {
            $shop_jsChartDataClick[$v['day']] = array(
                $v['day'], 
                $v['click_count'] * 1);
            if ($v['day'] == $today)
                $shop_data['PV'][$today] = $v['click_count'] * 1;
            if ($v['day'] == $yesterday)
                $shop_data['PV'][$yesterday] = $v['click_count'] * 1;
        }
        // 小店订单数
        $order_map = array(
            'order_type' => '2', 
            'order_status' => '0', 
            'pay_status' => '2', 
            'node_id' => $this->node_id);
        // 小店查询日期
        $order_map['add_time'] = array();
        if ($begin_date != '') {
            $order_map['add_time'][] = array(
                'EGT', 
                $begin_date . '000000');
        }
        if ($end_date != '') {
            $order_map['add_time'][] = array(
                'ELT', 
                $end_date . '235959');
        }
        $order_arr = M("ttg_order_info")->field(
            "count(order_id) as order_count,substr(add_time,'1',8) as day,sum(order_amt) as order_amt")
            ->where($order_map)
            ->group("substr(add_time,1,8)")
            ->select();
        
        // 小店-计算出JS值
        foreach ($order_arr as $v) {
            $shop_jsChartDataOrder[$v['day']] = array(
                $v['day'], 
                $v['order_count'] * 1);
            $shop_jsChartDataAmt[$v['day']] = array(
                $v['day'], 
                $v['order_amt'] * 1);
            
            if ($v['day'] == $today) {
                $shop_data['order'][$today] = $v['order_count'] * 1;
                $shop_data['saleamt'][$today] = $v['order_amt'] * 1;
            }
            if ($v['day'] == $yesterday) {
                $shop_data['order'][$yesterday] = $v['order_count'] * 1;
                $shop_data['saleamt'][$yesterday] = $v['order_amt'] * 1;
            }
        }
        // 商品数量
        $goods_count = M()->table("tfb_df_pointshop_goods_ex e")->join(
            'tbatch_info b on b.id=e.b_id')
            ->where(
            array(
                'e.node_id' => $this->node_id, 
                'b.status' => '0'))
            ->count();
        // 单页数
        $page_sort = M('tfb_df_pointshop_page_sort')->where(
            array(
                'node_id' => $this->node_id, 
                'page_type' => '2'))->count();
        if (is_array($shop_jsChartDataClick)) {
            foreach ($shop_jsChartDataClick as $kk => $vv) {
                if (! isset($shop_jsChartDataOrder[$kk])) {
                    $shop_jsChartDataOrder[$kk] = array(
                        $vv[0], 
                        0);
                }
                if (! isset($shop_jsChartDataAmt[$kk])) {
                    $shop_jsChartDataAmt[$kk] = array(
                        $vv[0], 
                        0);
                }
            }
        }
        ksort($shop_jsChartDataAmt);
        ksort($shop_jsChartDataOrder);
        $this->assign('page_sort', $page_sort);
        $this->assign('goods_count', $goods_count);
        $this->assign('m_info', $m_info);
        $this->assign('_get', $_get);
        $this->assign('shop_jsChartDataClick', 
            json_encode(array_values($shop_jsChartDataClick)));
        $this->assign('shop_jsChartDataOrder', 
            json_encode(array_values($shop_jsChartDataOrder)));
        $this->assign('shop_jsChartDataAmt', 
            json_encode(array_values($shop_jsChartDataAmt)));
        $this->assign('shop_data', $shop_data);
        $this->assign('today', $today);
        $this->assign('yesterday', $yesterday);
        $this->assign('logo_url', $logo_url);
        $this->assign('label_id', $label_id);
        $this->display('PointShop/preview');
    }

    /* 单页面设计 */
    public function pagediy() {
        $id = I("id");
        $pagetype = I("pagetype"); // 1 首页 2 单页
        $havetpl = I("havetpl"); // 1 首页 2 单页
        if ($id != "") {
            $catWhere = array(
                'node_id' => $this->node_id, 
                'id' => $id);
            $pageInfo = M('tfb_df_pointshop_page_sort')->where($catWhere)->find();
        }
        // 查询小店名称
        $marketInfo = M('tmarketing_info')->field('id,name')
            ->where(
            array(
                'node_id' => $this->node_id, 
                'batch_type' => $this->BATCH_TYPE))
            ->find();
        $this->assign('pageInfo', $pageInfo);
        $this->assign('marketInfo', $marketInfo);
        $this->assign('id', $id);
        $this->assign('havetpl', $havetpl);
        $this->assign('pagetype', $pagetype);
        $this->display('PointShop/pagediy');
    }

    /* 选择商品分类 */
    public function selectCat() {
        $this->display('PointShop/selectCat');
    }
    
    // 选择单个商品
    public function SelectOnegoods() {
        $edit_id = I('edit_id');
        $where = array(
            'a.node_id' => $this->nodeId, 
            'a.m_id' => array(
                'neq', 
                $edit_id), 
            'T.status' => '0');
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = M()->table('tfb_df_pointshop_goods_ex a')
            ->field('a.*,T.*')
            ->join('tbatch_info T ON a.node_id=T.node_id and t.id = a.b_id')
            ->where($where)
            ->count();
        
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $list = M()->table('tfb_df_pointshop_goods_ex a')
            ->field(
            "a.m_id as id , g.goods_name, g.goods_image, T.begin_time, T.end_time,T.batch_amt,a.label_id")
            ->join('tbatch_info T ON a.node_id=T.node_id and t.id = a.b_id')
            ->join('tgoods_info g on T.goods_id =  g.goods_id')
            ->where($where)
            ->order("a.id desc")
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        $show = $Page->show(); // 分页显示输出
        
        if ($list) {
            foreach ($list as &$v) {
                if ($v['goods_image'] != '')
                    $v['goods_image'] = C('UPLOAD') . $v['goods_image'];
            }
        }
        
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('list', $list); // 赋值分页输出
        $this->assign('CatList', $CatList); // 赋值分页输出
        $this->display('PointShop/SelectOnegoods');
    }
    
    // 选择商品分组
    public function SelectGoodsCat() {
        
        // 查询分类
        $catWhere = array(
            'node_id' => $this->node_id);
        $categoryInfo = M('tfb_df_pointshop')->where($catWhere)
            ->order("id desc ")
            ->select();
        $this->assign('list', $categoryInfo);
        $this->display('PointShop/SelectGoodsCat');
    }

    public function get_cat_goods() {
        $cat_id = I("cat_id");
        if ($cat_id == "") {
            $this->error("参数错误");
        } else {
            
            $nodeWhere = " e.node_id='" . $this->node_id .
                 "' AND b.status='0' AND b.end_time>='" . date('YmdHis') .
                 "' AND e.ecshop_classify ='" . $cat_id . "'";
            // $nodeWhere.=" e.ecshop_classify ='".$cat_id."'";
            
            $allCatGoods = M('tfb_df_pointshop_goods_ex e ')->join(
                "tbatch_info b ON b.id=e.b_id")
                ->where($nodeWhere)
                ->order("b.id desc")
                ->limit(24)
                ->select();
            
            $returnStr = '{"status":"0","list":[';
            $liststr = "";
            
            if (! empty($allCatGoods)) {
                
                foreach ($allCatGoods as $k => $val) {
                    if ($liststr != "") {
                        $liststr .= ",";
                    }
                    $liststr .= '{"price":"' . $val['batch_amt'] . '","id":"' .
                         $val['id'] .
                         '","url":"index.php?g=Label&m=Label&a=index&id=' .
                         $val['label_id'] . '","title":"' . $val['batch_name'] .
                         '","img":"' . C('UPLOAD') . $val['batch_img'] .
                         '","checkproPic":"3","checkproBtn":"1","checkproName":"1","checkproPrice":"1","size":"small"}';
                }
            }
            
            $returnStr .= $liststr;
            
            $returnStr .= "]}";
            
            echo $returnStr;
            exit();
        }
    }

    public function SelectOnePage() {
        
        // 查询分类
        $catWhere = array(
            'node_id' => $this->node_id);
        $PageInfo = M('tfb_df_pointshop_page_sort')->where($catWhere)
            ->order("id desc ")
            ->select();
        $this->assign('list', $PageInfo);
        $this->display('PointShop/SelectOnePage');
    }

    public function pagelist() {
        
        // 查询页面列表
        $catWhere = array(
            'node_id' => $this->node_id, 
            'page_type' => 2);
        
        $PageList = M('tfb_df_pointshop_page_sort')->where($catWhere)
            ->order("id desc ")
            ->select();
        $this->assign('list', $PageList);
        $this->display('PointShop/pagelist');
    }

    public function pageindex() {
        
        // 查询小店名称
        $marketInfo = M('tmarketing_info')->field('id,name')
            ->where(
            array(
                'node_id' => $this->node_id, 
                'batch_type' => $this->BATCH_TYPE))
            ->find();
        
        // 查询当前使用模板,如是停用所有的首页模板，则使用老的模板
        $catWhere = array(
            'node_id' => $this->node_id, 
            'page_type' => array(
                'in', 
                '1,3'), 
            'status' => '1');
        $pageInfo = M('tfb_df_pointshop_page_sort')->where($catWhere)
            ->order("id desc")
            ->find();
        
        // 获取店铺地址
        $m_info = M('tmarketing_info')->where(
            array(
                'node_id' => $this->node_id, 
                'batch_type' => '1001'))->find();
        $channel_id = M('tchannel')->where(
            array(
                'node_id' => $this->node_id, 
                'type' => '5', 
                'sns_type' => '55'))->getField('id');
        $label_id = get_batch_channel($m_info['id'], $channel_id, '1001', 
            $this->node_id);
        
        // 查询未被启用的主页模板
        $catWhere = array(
            'node_id' => $this->node_id, 
            'page_type' => '1');
        $unusePage = M('tfb_df_pointshop_page_sort')->where($catWhere)
            ->order("id desc")
            ->select();
        
        // 查询模板页面
        $Where = array(
            'node_id' => $this->node_id, 
            'page_type' => '3');
        $tempPage = M('tfb_df_pointshop_page_sort')->where($Where)
            ->order("id desc")
            ->find();
        
        $shopUrl = urlencode(
            U('Label/Label/index', 
                array(
                    'id' => $label_id), '', '', true));
        $this->assign('unusePage', $unusePage);
        $this->assign('marketInfo', $marketInfo);
        $this->assign('label_id', $label_id);
        $this->assign('tempPage', $tempPage);
        $this->assign('shopUrl', $shopUrl);
        $this->assign('imgcode', $code);
        $this->assign('pageInfo', $pageInfo);
        $this->display('PointShop/pageindex');
    }
    
    // 启用老版默认页面
    public function select_default() {
        $Where = array(
            'node_id' => $this->node_id);
        $data = array(
            'status' => '2');
        $upok = M('tfb_df_pointshop_page_sort')->where($Where)->save($data);
        if ($upok !== false) {
            $this->success("启用成功！");
        } else {
            $this->error("启用失败！");
        }
    }

    public function del_page() {
        $id = I("id");
        if ($id == "") {
            $this->error("删除失败，参数错误！");
        } else {
            
            $Where = array(
                'node_id' => $this->node_id, 
                'id' => $id);
            $delok = M('tfb_df_pointshop_page_sort')->where($Where)->delete();
            if ($delok) {
                $this->success("删除成功！");
            } else {
                $this->error("删除失败，查询异常！");
            }
        }
    }

    public function select_page() {
        // 启用模板页面ID
        $id = I("id");
        if ($id != "") {
            
            // 更新所有页面状态为停用，然后更新当前ID页面为启用
            $Where = array(
                'node_id' => $this->node_id, 
                'page_type' => array(
                    'in', 
                    '1,3'));
            $data = array(
                'status' => '2');
            $res = M('tfb_df_pointshop_page_sort')->where($Where)->save($data);
            
            // 更新当前选择ID状态为启动
            
            $Where = array(
                'node_id' => $this->node_id, 
                'id' => $id);
            $data = array(
                'status' => '1');
            $upok = M('tfb_df_pointshop_page_sort')->where($Where)->save($data);
            if ($upok !== false) {
                $this->success("启用成功！");
            }
        } else {
            $this->error("参数错误！");
        }
    }
    
    // 修改模板名称和图片
    public function editpagename() {
        $id = I("id");
        
        if ($id == "") {
            $this->error("参数错误！");
        }
        // 查询数据
        $Where = array(
            'node_id' => $this->node_id, 
            'id' => $id);
        $pageInfo = M('tfb_df_pointshop_page_sort')->where($Where)->find();
        $this->assign('pageInfo', $pageInfo);
        $this->display('PointShop/editpagename');
    }
    
    // 修改模板名称和图片提交数据
    public function editpagename_post() {
        $page_name = I("page_name");
        $page_id = I("page_id");
        $page_pic = I("page_pic");
        if ($page_id == "") {
            $this->error("参数错误！");
        }
        
        $uploadPath = C("BATCH_IMG_PATH_NAME");
        // 判断图片移动图片
        if ($page_pic != "") {
            $moveres = $this->move_pic($page_pic);
            if ($moveres) {
                $page_pic = str_replace("img_tmp", 
                    $uploadPath[$this->BATCH_TYPE], $page_pic);
            }
        }
        
        $Where = array(
            'node_id' => $this->node_id, 
            'id' => $page_id);
        $data = array(
            'page_name' => $page_name, 
            'page_pic' => $page_pic);
        $upok = M('tfb_df_pointshop_page_sort')->where($Where)->save($data);
        if ($upok !== false) {
            $this->success("修改成功！");
        } else {
            $this->error("修改失败！");
        }
    }

    public function edithomename() {
        // 查询店铺名称
        $marketInfo = M('tmarketing_info')->field('id,name')
            ->where(
            array(
                'node_id' => $this->node_id, 
                'batch_type' => $this->BATCH_TYPE))
            ->order("id desc")
            ->find();
        $ecshopBannerModel = M('tfb_df_pointshop_banner');
        // 查询店铺LOGO
        $logoInfo = $ecshopBannerModel->field('img_url')
            ->where(
            array(
                'node_id' => $this->node_id, 
                'm_id' => $marketInfo['id'], 
                'ban_type' => 1))
            ->order("id desc")
            ->find();
        // 查询小店描述
        $descInfo = $ecshopBannerModel->field('memo')
            ->where(
            array(
                'node_id' => $this->node_id, 
                'm_id' => $marketInfo['id'], 
                'ban_type' => 3))
            ->order("id desc")
            ->find();
        // 查询小店分享图片
        $shareInfo = $ecshopBannerModel->field('img_url')
            ->where(
            array(
                'node_id' => $this->node_id, 
                'm_id' => $marketInfo['id'], 
                'ban_type' => 4))
            ->order("id desc")
            ->find();
        // 查询企业Logo
        $nodePhoto = get_node_info($this->node_id, 'head_photo');
        
        $uploadPath = C("UPLOAD");
        $this->assign('marketInfo', $marketInfo);
        $this->assign('uploadPath', $uploadPath);
        $this->assign('logoInfo', $logoInfo['img_url']);
        $this->assign('descInfo', $descInfo);
        $this->assign('shareInfo', $shareInfo);
        $this->assign('nodePhoto', $nodePhoto);
        $this->display('PointShop/edithomename');
    }

    public function edithomename_post() {
        $m_id = I("m_id");
        $page_name = I("page_name");
        $is_upload = I("is_upload");
        // logo位置处理
        if ($is_upload == '1') {
            $logo = I("logo_pic", "", trim);
            $len = strlen($logo) - 14;
            $logo_pic = substr($logo, 14, $len);
        } else {
            $logo_pic = I("logo_pic", "", trim);
        }
        
        $page_description = I("page_description");
        $share_pic = I("share_pic");
        if ($m_id == "") {
            $this->error("营销活动不能为空！");
        }
        if ($page_name == "") {
            $this->error("小店名称不能为空！");
        }
        if ($logo_pic == "") {
            $this->error("小店LOGO不能为空！");
        }
        $uploadPath = C("BATCH_IMG_PATH_NAME");
        
        // 更新名称
        $Where = array(
            'node_id' => $this->node_id, 
            'id' => $m_id);
        $data = array(
            'name' => $page_name);
        $upok = M('tmarketing_info')->where($Where)->save($data);
        if ($upok === false) {
            $this->error("修改名称失败,更新异常！");
        }
        
        // 查询LOGO是否存在，存在则更新，不存在插入
        $logoInfo = M('tfb_df_pointshop_banner')->field('img_url')
            ->where(
            array(
                'node_id' => $this->node_id, 
                'm_id' => $m_id, 
                'ban_type' => 1))
            ->order("id desc")
            ->find();
        if ($logoInfo['img_url'] != "") {
            // 更新LOGO
            $Where = array(
                'node_id' => $this->node_id, 
                'm_id' => $m_id, 
                'ban_type' => 1);
            $data = array(
                'img_url' => $logo_pic);
            $upok = M('tfb_df_pointshop_banner')->where($Where)->save($data);
            if ($upok === false) {
                $this->error("修改LOGO失败,更新异常！");
            }
        } else {
            $data = array(
                'm_id' => $m_id, 
                'ban_type' => 1, 
                'img_url' => $logo_pic, 
                'node_id' => $this->node_id);
            $upok = M('tfb_df_pointshop_banner')->add($data);
            if ($upok == false) {
                $this->error("修改LOGO失败,更新异常！");
            }
        }
        
        $descInfo = M('tfb_df_pointshop_banner')->field('memo')
            ->where(
            array(
                'node_id' => $this->node_id, 
                'm_id' => $m_id, 
                'ban_type' => 3))
            ->order("id desc")
            ->find();
        if ($descInfo['memo'] != "") {
            // 更新描述
            $Where = array(
                'node_id' => $this->node_id, 
                'm_id' => $m_id, 
                'ban_type' => 3);
            $data = array(
                'memo' => $page_description);
            $upok = M('tfb_df_pointshop_banner')->where($Where)->save($data);
            if ($upok === false) {
                $this->error("修改描述失败,更新异常！");
            }
        } else {
            $data = array(
                'memo' => $page_description, 
                'm_id' => $m_id, 
                'node_id' => $this->node_id, 
                'ban_type' => 3);
            $upok = M('tfb_df_pointshop_banner')->add($data);
            if ($upok == false) {
                $this->error("修改描述失败,更新异常！");
            }
        }
        
        $shareInfo = M('tfb_df_pointshop_banner')->field('img_url')
            ->where(
            array(
                'node_id' => $this->node_id, 
                'm_id' => $marketInfo['id'], 
                'ban_type' => 4))
            ->order("id desc")
            ->find();
        if ($shareInfo['img_url'] != "") {
            // 更新分享图片
            $Where = array(
                'node_id' => $this->node_id, 
                'm_id' => $m_id, 
                'ban_type' => 4);
            $data = array(
                'img_url' => $share_pic);
            $upok = M('tfb_df_pointshop_banner')->where($Where)->save($data);
            if ($upok === false) {
                $this->error("修改分享图片失败,更新异常！");
            }
        } else {
            
            $data = array(
                'img_url' => $share_pic, 
                'm_id' => $m_id, 
                'node_id' => $this->node_id, 
                'ban_type' => 4);
            $upok = M('tfb_df_pointshop_banner')->add($data);
            if ($upok == false) {
                $this->error("修改分享图片失败,添加异常！");
            }
        }
        
        $this->success("修改成功！");
    }
    
    // 获取活动访问地址 小店渠道
    public function getBatchUrl() {
        $batch_id = I("batch_id");
        $batch_type = I("batch_type");
        
        // 查询渠道号
        $Where = array(
            "sns_type" => '46', 
            "type" => '4', 
            "node_id" => $this->node_id);
        $channelInfo = M('tchannel')->where($Where)->find();
        
        $label_id = get_batch_channel($batch_id, $channelInfo['id'], 
            $batch_type, $this->node_id);
        
        if ($label_id != "") {
            $returnstr = '{"status":"0","msg":"获取url成功！","url":"index.php?g=Label&m=Label&a=index&id=' .
                 $label_id . '"}';
        } else {
            $returnstr = '{"status":"1","msg":"获取url失败！","url":""}';
        }
        
        echo $returnstr;
        exit();
    }

    public function codeshow() {
        import('@.Vendor.phpqrcode.phpqrcode');
        $url = htmlspecialchars_decode((urldecode(I("get.url"))));
        echo QRcode::png($url);
        exit();
    }

    public function getJson() {
        $data = I("json_data", "", "trim");
        $page_str = I("page_str", "", "trim");
        $pic_str = I("pic_str", "", "trim");
        
        if (ini_get("magic_quotes_gpc") == "1") {
            $page_str = stripslashes($page_str);
            $data = stripslashes($data);
        }
        
        $pageInfo = json_decode($page_str, true);
        $dataInfo = json_decode($data, true);
        
        // 插入或者更新页面
        if (! empty($dataInfo)) {
            $uploadPath = C("BATCH_IMG_PATH_NAME");
            
            // 判断分享图片存在，移动图片
            if ($pageInfo['share_pic'] != "") {
                $moveres = $this->move_pic($pageInfo['share_pic']);
                if ($moveres) {
                    $pageInfo['share_pic'] = str_replace("img_tmp", 
                        $uploadPath[$this->BATCH_TYPE], $pageInfo['share_pic']);
                }
            }
            
            if ($pic_str != "") {
                $moveok = $this->move_pic($pic_str);
                if ($moveok) {
                    
                    $newdata = str_replace("img_tmp", 
                        $uploadPath[$this->BATCH_TYPE], $data);
                } else {
                    $returnstr = '{"status":"1","msg":"移动图片失败！"}';
                    echo $returnstr;
                    exit();
                }
            } else {
                
                $newdata = $data;
            }
            
            // 如果ID存在则是更新
            if ($pageInfo['page_id'] != "") {
                
                $update_data = array(
                    "page_name" => $pageInfo['page_title'], 
                    "page_description" => $pageInfo['page_description'], 
                    "share_pic" => $pageInfo['share_pic'], 
                    "page_content" => $newdata);
                $Where['id'] = $pageInfo['page_id'];
                $res = M('tfb_df_pointshop_page_sort')->where($Where)->save(
                    $update_data);
                if ($res !== false) {
                    
                    $returnstr = '{"status":"0","msg":"更新成功！"}';
                    echo $returnstr;
                    exit();
                } else {
                    $returnstr = '{"status":"1","msg":"更新失败！"}';
                    echo $returnstr;
                    exit();
                }
            } else {
                
                $insert_data = array(
                    "node_id" => $this->node_id, 
                    "page_type" => $pageInfo['page_type'], 
                    "page_name" => $pageInfo['page_title'], 
                    "page_description" => $pageInfo['page_description'], 
                    "share_pic" => $pageInfo['share_pic'], 
                    "page_content" => $newdata, 
                    "status" => '2', 
                    "add_time" => date('YmdHis'));
                
                $res = M('tfb_df_pointshop_page_sort')->add($insert_data);
                if ($res) {
                    
                    $returnstr = '{"status":"0","msg":"保存成功！"}';
                    echo $returnstr;
                    exit();
                } else {
                    $returnstr = '{"status":"1","msg":"保存失败！"}';
                    echo $returnstr;
                    exit();
                }
            }
        } else {
            
            $returnstr = '{"status":"1","msg":"保存失败，数据异常！"}';
            echo $returnstr;
            exit();
        }
    }

    public function move_pic($piclist) {
        $picArr = explode(",", $piclist);
        if (! empty($picArr)) {
            foreach ($picArr as $k => $val) {
                // 判断目录是否存在img_tmp
                if (strpos($val, 'img_tmp') !== false) {
                    $img_move = move_batch_image($val, $this->BATCH_TYPE, 
                        $this->node_id);
                    if ($img_move !== true) {
                        return $img_move;
                    }
                }
            }
        }
        return true;
    }

    public function storepreview() {
        $this->display('PointShop/storepreview');
    }
}
