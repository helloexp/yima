<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates and open the template
 * in the editor.
 */

/**
 * Description of AdbService
 */
class AdbService {
    protected $node_id;
    protected $session_cart_name;
    protected $session_ship_name;
    public function __construct() {
        $this->node_id=C('adb.node_id');
        $phoneNo = session("groupPhone");
        $this->session_cart_name = 'session_cart_products_' . $this->node_id. '_' . $phoneNo;
        $this->session_ship_name = 'session_ship_products_' . $this->node_id. '_' . $phoneNo;
    }

    /**
     * Description of AdbService 即时更新模板中商品价格，名称和图片
     */
    public function changePageContent($content, $nodeId = false, $store_id) {
        $pageInfo_tmp = $content;
        $a = json_decode($pageInfo_tmp, true);
        // 创建sku信息
        $skuObj = D('Sku', 'Service');
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
                    $m_id = M('tbatch_channel')->where(array(
                        'id' => $label_id))->getField('batch_id');
                    $goods_detail = M()->table('tbatch_info b')
                        ->join('tgoods_info g on g.goods_id=b.goods_id')
                        ->where(array(
                        'b.m_id' => $m_id))
                        ->field(
                        'b.id, b.batch_amt,g.node_id, b.status, g.goods_image, g.goods_name, g.is_sku')
                        ->find();

                    $skuObj->nodeId=$goods_detail['node_id'];
                    // sku商品
                    if ("1" === $goods_detail['is_sku']) {
                        $goods_detail = $skuObj->makeGoodsListInfo(
                            $goods_detail, $m_id);
                    }
                    $lv['price'] = $goods_detail['batch_amt'];
                    $lv['title'] = $goods_detail['goods_name'];
                    $lv['img'] = get_upload_url($goods_detail['goods_image']);
                    if ($goods_detail['status'] != '0'){
                        $lv['url']='javascript:void(0);';
                        unset($v['list'][$lk]);
                    }
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
                    $m_id = M('tbatch_channel')->where(array(
                        'id' => $label_id))->getField('batch_id');
                    $all_goods = $this->getProListInfo($m_id, $list_count, 
                        $store_id);
                    $checkproPic = $v['list'][0]['checkproPic'];
                    $checkproBtn = $v['list'][0]['checkproBtn'];
                    $checkproName = $v['list'][0]['checkproName'];
                    $checkproPrice = $v['list'][0]['checkproPrice'];
                    // 清空数据
                    // unset($v['list']);
                     
                    foreach ($all_goods as $jk => &$jv) {
                        $v['list'][$jk]['id'] = $jv['id'];
                        $v['list'][$jk]['img'] = get_upload_url(
                            $jv['goods_image']);
                        $v['list'][$jk]['url'] =  $new_url . $jv['label_id'];
                        $v['list'][$jk]['title'] = $jv['goods_name'];
                        // sku商品
                        if ("1" === $jv['is_sku']) {
                            $skuObj->nodeId=$goods_detail['node_id'];
                            $jv = $skuObj->makeGoodsListInfo($jv, $jv['id']);
                        }
                        $v['list'][$jk]['price'] = $jv['batch_amt'];
                        $v['list'][$jk]['checkproPic'] = $checkproPic;
                        $v['list'][$jk]['checkproBtn'] = $checkproBtn;
                        $v['list'][$jk]['checkproName'] = $checkproName;
                        $v['list'][$jk]['checkproPrice'] = $checkproPrice;
                        $v['list'][$jk]['size'] = $this->getPicType($jk, 
                            $checkproPic);
                    }
                    foreach($v['list'] as $key => $row){
                        $check=M('tbatch_info')->where(array('id'=>$row['id'],'status'=>0))->count('id');
                        if(!$check){
                            $v['list'][$key]['url']='javascript:void(0);';
                            $v['list'][$key]['checkproBtn']="false";
                        }
                    }
                    // echo json_encode($all_goods);die;
                   
                } else {
                    unset($v['list']);
                }
            }
        } // die;
        return json_encode($a);
    }

    /**
     * Description of AdbService 查询商品关联分类信息下的当前门店上架商品
     * 
     * @param int $cat_id m_id
     * @author john_zeng
     */
    public function getProListInfo($cat_id, $count = 6, $store_id) {
        if ($count < 1)
            $count = 6;
        $cat_id = M('tecshop_goods_ex')->where(array(
            'm_id' => $cat_id))->getField('ecshop_classify');
        // $nodeWhere=" e.node_id='".$this->node_id."' AND b.status='0' AND
        // b.end_time>='".date('YmdHis')."' AND e.ecshop_classify
        // ='".$cat_id."'";
        $nodeWhere .= "  a.ecshop_classify ='" . $cat_id .
             "'  AND T.status='0' AND tags.store_id='" . $store_id . "'";

        $allCatGoods = M()->table("tfb_adb_goods_store tags")->join(
            'tbatch_info T on T.id=tags.b_id')
            ->join(
            'tecshop_goods_ex a ON a.node_id=T.node_id and T.id = a.b_id')
            ->join('tgoods_info g on T.goods_id =  g.goods_id')
            ->where($nodeWhere)
            ->field(
            'T.id, T.batch_amt, g.goods_image, g.goods_name,a.label_id, g.is_sku')
            ->order("T.id desc")
            ->limit($count)
            ->select();
        return $allCatGoods;
    }

    /**
     * Description of AdbService 取得当前图片类型
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
     */
    public function getO2OFEE() {
        $o2oFee = M('tsystem_param')->field('param_value')->where(
            array(
                'param_name' => 'O2OFEE'))->getField('param_value');
        return $o2oFee;
    }



    /**
     * 绑定门店事件
     * @param  [type] $resp [description]
     * @return [type]       [description]
     */
    public function bindStoreEvent($resp)
    {
        if(empty($resp['openid'])){
            return false;
        }
        $openid=$resp['openid'];
        $store_id=0;
        if($resp['scene_id']){
            $store_id=M('twx_qrchannel')->alias('q')
                                        ->join('tchannel c on q.channel_id=c.id')
                                        ->join('tstore_info s on s.store_id=c.store_id')
                                        ->where(array(
                                            's.status'=>0,
                                            'q.node_id'=>$this->node_id,
                                            'q.scene_id'=>$resp['scene_id'],
                                            ))
                                        ->getField('c.store_id');
        }
        $model=M('tfb_adb_user_store');
        $store_id=$store_id?$store_id:0;
        $where['openid']=$openid;
        $info=$model->where($where)->find();
        $save=array(
            'openid'=>$openid,
            'store_id'=>$store_id,
            'first'=>1,//第一次登入
            );
        $res=true;
        if($info['id']){
            $c_save=[];
            if($info['store_id'] != $store_id){ //不改变关系
                $c_save['store_id']=$store_id;
            }
            if($info['first'] == 0 && $info['store_id'] != $store_id){
                $c_save['first']=$save['first'];
            }
            if($c_save){
                $where['id']=$info['id'];
                $res=$model->where($where)->save($c_save);
            }
        }else{
            $res=$model->add($save);
        }
        if($res == false){
            log_write("爱蒂宝绑定门店失败：".var_export($resp,true)."\n最后SQL：".M()->_sql());
            return false;
        }
        return true;
    }

    /**
     * 清空购物车
     * @return [type] [description]
     */
    public function cleanCarts()
    {
        session($this->session_cart_name, null);
        return;
    }

    //是否存在商品
    public function checkCarts()
    {
        return (session("?".$this->session_cart_name));
    }
}
