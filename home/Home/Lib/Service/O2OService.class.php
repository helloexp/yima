<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates and open the template
 * in the editor.
 */

/**
 * Description of O2OService
 *
 * @author john_zeng
 */
class O2OService {

    public function __construct() {
    }

    /**
     * Description of O2OService 即时更新模板中商品价格，名称和图片
     *
     * @author john_zeng
     */
    public function changePageContent($content, $nodeId = false) {
        $pageInfo_tmp = $content;
        $a = json_decode($pageInfo_tmp, true);
        // echo($content);
        // die;
        foreach ($a['module'] as $k => &$v) {
            if ($v['name'] == 'Pro') {
                foreach ($v['list'] as $lk => &$lv) {
                    $url = $lv['url'];
                    $id_tmp = strstr($url, "&id=");
                    $id_arr = explode("=", $id_tmp);
                    $label_id = $id_arr[1];
                    // todo debug 还有待优化
                    $m_id = M('tbatch_channel')->where(
                        array(
                            'id' => $label_id))->getField('batch_id');
                    $goods_detail = M()->table('tbatch_info b')
                        ->join('tgoods_info g on g.goods_id=b.goods_id')
                        ->where(
                        array(
                            'b.m_id' => $m_id))
                        ->field(
                        'b.id, b.batch_amt, b.status, g.goods_image, g.goods_name, g.is_sku')
                        ->find();
                    
                    // 创建sku信息
                    $skuObj = D('Sku', 'Service');
                    // sku商品
                    if ("1" === $goods_detail['is_sku']) {
                        $skuObj->nodeId = $nodeId;
                        $goods_detail = $skuObj->makeGoodsListInfo($goods_detail, $m_id, '');
                    }
                    $lv['price'] = $goods_detail['batch_amt'];
                    $lv['title'] = $goods_detail['goods_name'];
                    $lv['img'] = get_upload_url($goods_detail['goods_image']);
                    if ($goods_detail['status'] != '0')
                        unset($v['list'][$lk]);
                }
            }
            
            if ($v['name'] == 'ProList') {
                // 取得当前页面可展示产品数
                $list_count = $v['checkProListNum'];
                if (isset($v['list'][0]['url'])) {
                    $url = $v['list'][0]['url'];
                    $new_url = explode("id=", $url);
                    $new_url = $new_url[0] . 'id=';
                    $id_tmp = strstr($url, "&id=");
                    $id_arr = explode("=", $id_tmp);
                    $label_id = $id_arr[1];
                    $m_id = M('tbatch_channel')->where(
                        array(
                            'id' => $label_id))->getField('batch_id');
                    $all_goods = $this->getProListInfo($m_id, $list_count);
                    $checkproPic = $v['list'][0]['checkproPic'];
                    $checkproBtn = $v['list'][0]['checkproBtn'];
                    $checkproName = $v['list'][0]['checkproName'];
                    $checkproPrice = $v['list'][0]['checkproPrice'];
                    // 清空数据
                    unset($v['list']);
                    foreach ($all_goods as $jk => &$jv) {
                        $v['list'][$jk]['id'] = $jv['id'];
                        $v['list'][$jk]['img'] = get_upload_url(
                            $jv['goods_image']);
                        $v['list'][$jk]['url'] = $new_url . $jv['label_id'];
                        $v['list'][$jk]['title'] = $jv['goods_name'];
                        // sku商品
                        if ("1" === $jv['is_sku']) {
                            // 创建sku信息
                            $skuObj = D('Sku', 'Service');
                            $skuObj->nodeId = $nodeId;
                            $jv = $skuObj->makeGoodsListInfo($jv, $jv['m_id'], '');
                        }
                        $v['list'][$jk]['price'] = $jv['batch_amt'];
                        $v['list'][$jk]['checkproPic'] = $checkproPic;
                        $v['list'][$jk]['checkproBtn'] = $checkproBtn;
                        $v['list'][$jk]['checkproName'] = $checkproName;
                        $v['list'][$jk]['checkproPrice'] = $checkproPrice;
                        $v['list'][$jk]['size'] = $this->getPicType($jk, 
                            $checkproPic);
                    }
                    // echo json_encode($all_goods);die;
                    $goods_detail = M()->table('tbatch_info b')
                        ->join('tgoods_info g on g.goods_id=b.goods_id')
                        ->where(
                        array(
                            'b.m_id' => $m_id))
                        ->field(
                        'b.batch_amt, b.status, g.goods_image, g.goods_name')
                        ->find();
                } else {
                    unset($v['list']);
                }
            }
        } // die;
        return json_encode($a);
    }

    /**
     * Description of O2OService 查询商品关联分类信息下的所有商品
     *
     * @param int $cat_id m_id
     * @author john_zeng
     */
    public function getProListInfo($cat_id, $count = 6) {
        if ($count < 1)
            $count = 6;
        $cat_id = M('tecshop_goods_ex')->where(
            array(
                'm_id' => $cat_id))->getField('ecshop_classify');
        // $nodeWhere=" e.node_id='".$this->node_id."' AND b.status='0' AND
        // b.end_time>='".date('YmdHis')."' AND e.ecshop_classify
        // ='".$cat_id."'";
        $nodeWhere = "  e.ecshop_classify ='" . $cat_id . "'  AND b.status='0'";
        
        $allCatGoods = M('tecshop_goods_ex e ')->join(
            "tbatch_info b ON b.id=e.b_id")
            ->join('tgoods_info g on g.goods_id=b.goods_id')
            ->where($nodeWhere)
            ->field(
            'b.id, b.m_id, b.batch_amt, g.goods_image, g.goods_name,e.label_id, g.is_sku')
            ->order("b.id desc")
            ->limit($count)
            ->select();
        return $allCatGoods;
    }

    /**
     * Description of O2OService 取得当前图片类型
     *
     * @param int $num 数组当前键值 int $type 选择图片展示类型
     * @author john_zeng
     */
    public function getPicType($num, $type) {
        $return_str = 'small';
        switch ($type) {
            case '1':
                $return_str = 'big';
                break;
            case '2':
                $return_str = 'small';
                break;
            case '3':
                if ($num == 0 || $num == 3)
                    $return_str = 'big';
                else
                    $return_str = 'small';
                break;
        }
        return $return_str;
    }

    /**
     * Description of SkuService 获取手续费信息
     *
     * @param
     *
     * @return string $o2oFee
     * @author john_zeng
     */
    public function getO2OFEE() {
        $o2oFee = M('tsystem_param')->field('param_value')
            ->where(array(
            'param_name' => 'O2OFEE'))
            ->getField('param_value');
        return $o2oFee;
    }
}
