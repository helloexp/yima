<?php

// 广西石油Wap
class WapStoreAction extends Action {

    public $node_id;

    public function _initialize() {
        $this->node_id = C('cnpc_gx.node_id');
    }

    public function index() {
        $id = I('get.id');
        $parentInfo = M('tfb_cnpcgx_catalog')->field('id,catalog_name')
            ->where("parent_id=0 and is_delete=0")
            ->select();
        $catalogInfo = array();
        foreach ($parentInfo as $v) {
            $catalogInfo[$v['id']] = M('tfb_cnpcgx_catalog')->field(
                'id,catalog_name')
                ->where("parent_id=" . $v['id'] . " and is_delete=0")
                ->select();
        }
        
        $model = M('tcity_code');
        
        $map = array(
            'city_level' => '2', 
            'province_code' => 21);
        $field_str = 'city_code,city';
        $city = $model->field($field_str)
            ->where($map)
            ->select();
        $town = array();
        foreach ($city as $v) {
            $maptown = array(
                'city_level' => '3', 
                'province_code' => 21, 
                'city_code' => $v['city_code']);
            $town_str = 'town_code,town';
            $town[$v['city_code']] = $model->field($town_str)
                ->where($maptown)
                ->select();
        }
        
        $map = array(
            "a.status" => 1, 
            "a.type" => 0, 
            "a.show_status" => 1);
        $catalogId = I('catalog_id', null); // 分类
        $parentId = I('parent_id', null);
        $cityCode = I('city_code', null); // 城市
        $townCode = I('town_code', null);
        $key = I('keyword', null);
        if ($catalogId) {
            $map['a.catalog_id'] = $catalogId;
        }
        if ($parentId) {
            $map['a.parent_id'] = $parentId;
        }
        if ($cityCode) {
            $map['a.city_code'] = $cityCode;
        }
        if ($townCode) {
            $map['a.town_code'] = $townCode;
        }
        if ($key) {
            $map['_string'] = '(a.merchant_name like "%' . $key .
                 '%" or a.address like "%' . $key . '%")';
        }
        
        // 分页
        $nowP = I('p', null, 'mysql_real_escape_string'); // 页数
        empty($nowP) ? $nowP = 1 : $nowP;
        import("ORG.Util.Page");
        $count = M()->table("tfb_cnpcgx_node_info a")
            ->where($map)
            ->count();
        $pageSize = 10;
        
        $list = array();
        if ($pageSize * ($nowP - 1) < $count) {
            $p = new Page($count, $pageSize);
            $page = $p->show();
            $list = M()->table("tfb_cnpcgx_node_info a")
                ->where($map)
                ->limit($p->firstRow . ',' . $p->listRows)
                ->order('a.order_sort desc,a.id desc')
                ->field('a.*')
                ->select();
        }
        
        $nextUrl = U('CnpcGX/WapStore/index', 
            array(
                'keyword' => $key, 
                'catalog_id' => $catalogId, 
                'parent_id' => $parentId, 
                'city_code' => $cityCode, 
                'town_code' => $townCode, 
                'id' => $id), '', '', true) . '&p=' . ($nowP + 1);
        
        $ajax = I('get.ajax', null);
        if ($ajax == 1) {
            $str = '';
            if ($list) {
                foreach ($list as $vo) {
                    $str .= '<div class="box">
                            <a href="' . U(
                        'CnpcGX/WapStore/goodslist', 
                        array(
                            'id' => $id, 
                            'merchant_id' => $vo['id'])) .
                         '">
                                <img src="' .
                         C('UPLOAD') . $vo['image_link'] . '" />
                                <div class="proItem-msg">
                                    <h1>' .
                         $vo['merchant_name'] .
                         '</h1>
                                    <h2>&nbsp;</h2>
                                    <h3><p class="l">' .
                         $vo['address'] .
                         '</p></h3>
                                    <a href="tel:' .
                         $vo['hot_line_tel'] . '" class="phone"></a>
                                </div>
                            </a>
                        </div>';
                }
            }
            header("Content-type: text/html; charset=utf-8");
            $this->ajaxReturn(
                array(
                    'nextUrl' => $nextUrl, 
                    'str' => $str), 'JSON');
            exit();
        }
        
        // 获取当前选中的城市名称或者分类名称
        $selCity = '全部城市';
        $selCata = '全部分类';
        $map = array();
        if ($catalogId) {
            $map['id'] = $catalogId;
            $map['parent_id'] = $parentId;
        }
        if ($parentId && ! $catalogId) {
            $map['id'] = $parentId;
        }
        if ($map) {
            $selCata = M('tfb_cnpcgx_catalog')->where($map)->getField(
                'catalog_name');
        }
        
        $map = array();
        
        if ($townCode) {
            $map = array(
                'province_code' => '21', 
                'city_code' => $cityCode, 
                'town_code' => $townCode, 
                'city_level' => 3);
            $field = 'town';
        }
        if ($cityCode && ! $townCode) {
            $map = array(
                'province_code' => '21', 
                'city_code' => $cityCode, 
                'city_level' => 2);
            $field = 'city';
        }
        
        if ($map) {
            $selCity = M('tcity_code')->where($map)->getField($field);
        }
        
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('id', $id);
        $this->assign("catalogInfo", $catalogInfo);
        $this->assign("parentInfo", $parentInfo);
        $this->assign("city", $city);
        $this->assign("town", $town);
        $this->assign("keyword", $key);
        $this->assign('nextUrl', $nextUrl);
        $this->assign("loc_city_code", $cityCode);
        $this->assign("loc_town_code", $townCode);
        $this->assign("selCity", $selCity);
        $this->assign("selCata", $selCata);
        
        $this->display();
    }

    public function goodslist() {
        $id = I('get.id');
        $merchantId = I('get.merchant_id');
        $keyword = I("keyword");
        // 查询分类
        $catWhere = array(
            'node_id' => $this->node_id);
        $categoryInfo = M('tecshop_classify ')->where($catWhere)
            ->order("id desc ")
            ->select();
        
        // $map = array("a.merchant_id" => $merchantId);
        $where = array(
            'cg.merchant_id' => $merchantId, 
            'a.node_id' => $this->node_id, 
            'T.status' => '0', 
            'T.is_delete' => 0,  // 显示未删除的信息
            '_string' => ' 1=1 ');
        
        // 分页
        $nowP = I('p', null, 'mysql_real_escape_string'); // 页数
        empty($nowP) ? $nowP = 1 : $nowP;
        import("ORG.Util.Page");
        $mapcount = M()->table('tecshop_goods_ex a')
            ->field(
            "a.ecshop_classify, a.label_id, T.*, m.click_count,g.goods_image,x.sale_num,x.lock_num, g.is_sku ")
            ->join(
            "(SELECT o.node_id, oe.b_id,SUM(CASE WHEN o.pay_status IN('2','3') THEN oe.goods_num END) sale_num ,SUM(CASE WHEN o.pay_status IN ('1') THEN oe.goods_num END) lock_num FROM ttg_order_info o, ttg_order_info_ex oe WHERE o.order_id = oe.order_id AND o.order_type = '2' AND o.order_status = '0' GROUP BY o.node_id, oe.b_id) X ON  x.node_id = a.node_id AND x.b_id = a.b_id")
            ->join('tbatch_info T ON a.node_id=T.node_id and t.id = a.b_id')
            ->join('tgoods_info g on T.goods_id =  g.goods_id')
            ->join('tmarketing_info m on m.id =  a.m_id')
            ->join('tfb_cnpcgx_goods cg on cg.goods_no =  g.goods_id')
            ->where($where)
            ->count();
        
        $pageSize = 6;
        $p = new Page($mapcount, $pageSize);
        $page = $p->show();
        $list = array();
        if ($pageSize * ($nowP - 1) < $mapcount) {
            $sortSql = 'status asc , id desc';
            $subQuery = M()->table('tecshop_goods_ex a')
                ->field(
                "a.ecshop_classify, a.label_id, T.*, m.click_count,g.goods_image,x.sale_num,x.lock_num, g.is_sku ")
                ->join(
                "(SELECT o.node_id, oe.b_id,SUM(CASE WHEN o.pay_status IN('2','3') THEN oe.goods_num END) sale_num ,SUM(CASE WHEN o.pay_status IN ('1') THEN oe.goods_num END) lock_num FROM ttg_order_info o, ttg_order_info_ex oe WHERE o.order_id = oe.order_id AND o.order_type = '2' AND o.order_status = '0' GROUP BY o.node_id, oe.b_id) X ON  x.node_id = a.node_id AND x.b_id = a.b_id")
                ->join('tbatch_info T ON a.node_id=T.node_id and t.id = a.b_id')
                ->join('tgoods_info g on T.goods_id =  g.goods_id')
                ->join('tmarketing_info m on m.id =  a.m_id')
                ->join('tfb_cnpcgx_goods cg on cg.goods_no =  g.goods_id')
                ->where($where)
                ->buildSql();
            
            $list = M()->table($subQuery . ' a')
                ->order($sortSql)
                ->limit($p->firstRow . ',' . $p->listRows)
                ->select();
        }
        
        // 创建sku信息
        $skuObj = D('Sku', 'Service');
        foreach ($list as &$val) {
            // sku商品
            if ("1" === $val['is_sku']) {
                $val = $skuObj->makeGoodsListInfo($val, $this->node_id, true, 
                    $val['b_id'], $val['m_id']);
            }
        }
        
        $nextUrl = U('CnpcGX/WapStore/goodslist', 
            array(
                'id' => $id, 
                'keyword' => $keyword, 
                'merchant_id' => $merchantId), '', '', true) . '&p=' . ($nowP + 1);
        $ajax = I('get.ajax', null);
        if ($ajax == 1) {
            $str = '';
            if ($list) {
                foreach ($list as $vo) {
                    $str .= '<div class="box">
                                <img src="' . C('UPLOAD') .
                         $vo['goods_image'] . '" />
                                <div class="proItem-msg">
                                    <h1>' .
                         $vo['batch_name'] .
                         '</h1>
                                    <h2>&nbsp;</h2>
                                    <h3><p class="l">￥' .
                         $vo['batch_amt'] . '</p></h3>
                                    <a href="javascript:void(0)" class="btn-ok r" id="buy">购买</a>
                                </div>
                        </div>';
                }
            }
            header("Content-type: text/html; charset=utf-8");
            $this->ajaxReturn(
                array(
                    'nextUrl' => $nextUrl, 
                    'str' => $str), 'JSON');
            exit();
        }
        $merchant = M('tfb_cnpcgx_node_info')->where("id=" . $merchantId)->find();
        $this->assign('keyword', $keyword);
        $this->assign("merchant", $merchant);
        $this->assign('goodsList', $list);
        $this->assign('page', $page);
        $this->assign('id', $id);
        $this->assign('nextUrl', $nextUrl);
        $this->assign('categoryInfo', $categoryInfo);
        $this->display();
    }
}
