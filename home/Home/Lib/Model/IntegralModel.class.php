<?php

/**
 * 油豆积分商城
 *
 *
 * @author : chenchang Date: 2015/10/30
 */
class IntegralModel extends BaseModel {
    public $tableName = '__NONE__';
    /**
     * 查询商品信息
     *
     * @param str $where 商品搜索条件 str $nowP 页数 str $order 排序条件 str $field field条件
     * @return array 返回商品信息
     * @author chenchang
     */
    public function goodsInfo($cata_id, $order_id, $search_name, $node_id, $nowP) {
        
        // 默认条件
        $all_data = array(
            'a.node_id' => $node_id, 
            'a.market_show' => '0', 
            'a.is_delete' => '0', 
            'd.status' => '1'
        );
        $field = 'b.goods_name, a.goods_desc,a.tape_price_type,a.tape_price,a.market_price, a.id, b.pos_group, a.label_id, b.is_sku, b.goods_id, b.goods_image ,b.exchange_num';
        $order = 'b.add_time desc';
        
        // 增加分类条件
        if ($cata_id != null) {
            $all_data['e.id'] = $cata_id;
        }
        
        // 增加排序条件
        if ($order_id != null) {
            if ($order_id == '2') {
                $order = ' d.click_count desc';
                $field .= ', d.click_count';
            }
        }
        
        // 增加商户名搜索条件
        if ($search_name != null) {
            $all_data['b.goods_name'] = array(
                'like', 
                '%' . $search_name . '%');
        }
        empty($nowP) ? $nowP = 1 : $nowP;
        $pageCount = 6; // 每页显示条数
        $limit = ($nowP - 1) * $pageCount . ',' . $pageCount;
        $list = M()->table('tintegral_goods_ex a')
            ->join('tgoods_info b ON a.goods_id = b.id')
            ->join('tbatch_info c ON b.id = c.goods_id')
            ->join('tmarketing_info d ON d.id = a.m_id')
            ->join('tintegral_classify e ON a.ecshop_classify = e.id')
            ->where($all_data)
            ->limit($limit)
            ->order($order)
            ->field($field)
            ->select();
        // 计算sku商品
        foreach ($list as $key => $value) {
            if ($value['is_sku'] == '1') {
                $goods_id = $value['goods_id'];
                $sku_goods = M('tgoods_sku_integral_info')->where(
                    "goods_id = '$goods_id'")
                    ->order('market_price')
                    ->select();
                $sku_goods_end = end($sku_goods);
                if ($sku_goods[0]['market_price'] ==
                     $sku_goods_end['market_price']) {
                    $list[$key]['market_price'] = floor(
                        $sku_goods[0]['market_price']);
                } else {
                    $list[$key]['market_price'] = floor(
                        $sku_goods[0]['market_price']) . "~" .
                         floor($sku_goods_end['market_price']);
                }
            } else {
                $list[$key]['market_price'] = floor($list[$key]['market_price']);
            }
            if (mb_strlen($list[$key]['goods_desc'], 'utf-8') > 20) {
                $list[$key]['goods_desc'] = mb_substr($list[$key]['goods_desc'], 
                    0, 20, 'utf-8') . '……';
            }
        }
        return $list;
    }

    /**
     * 查询商品分类名字
     *
     * @param int $cata_id 分类ID
     * @return str 返回分类名字
     * @author chenchang
     */
    public function cata($cata_id) {
        $cata_name = M('tintegral_classify')->where("id = '$cata_id'")->getField(
            'class_name');
        return $cata_name;
    }

    /**
     * 首页分类导航
     *
     * @param int $node_id 商户号
     * @return array 返回分类
     * @author chenchang
     */
    public function cataAll($node_id) {
        $cate_re = M('tintegral_classify')->where("node_id = '$node_id'")->select();
        return $cate_re;
    }

    /**
     * 会员积分
     *
     * @param type $member_id
     * @return type
     */
    public function user_info($member_id) {
        $user_info = M('tmember_info')->where(
            array(
                'id' => $member_id, 
                'status' => 0))->find();
        return $user_info;
    }

    /**
     * 全部手机端的收获地址
     *
     * @return array
     */
    public function getAllPhoneAddress($mobile) {
        $address_data = array(
            'a.user_phone' => $mobile);
        $lastAddress = M()->table("tphone_address a")->join(
            'tcity_code b on a.path = b.path')
            ->where($address_data)
            ->field('a.*, b.province, b.city, b.town')
            ->order('a.last_use_time DESC')
            ->select();
        return $lastAddress;
    }

    /**
     * 检查商城积分分组信息
     *
     * @param string $nodeId 商户号
     * @return string id 返回积分分组ID
     * @author johnzeng
     */
    public function checkClassify($nodeId) {
        $classifyINFO = M('tecshop_classify')->where(
            "node_id = '$nodeId' and class_type = 1")
            ->field('id,class_name')
            ->find();
        if (! $classifyINFO) {
            $data = array(
                'node_id' => $nodeId, 
                'class_name' => '积分换购', 
                'add_time' => date('YmdHis'), 
                'sort' => 999, 
                'class_type' => 1);
            M('tecshop_classify')->where("node_id = '$nodeId'")->add($data);
            $classifyId = M()->getLastInsID();
            $classifyINFO['id'] = $classifyId;
            $classifyINFO['class_name'] = '积分换购';
        }
        
        return $classifyINFO;
    }

    /**
     * 获取积分兑换比例
     *
     * @param string $nodeId 商户号
     * @return array 返回兑换比例
     * @author johnzeng
     */
    public function getIntegralExchange($nodeId) {
        $integralExchange = M('tintegral_exchange')->where(
            "node_id = '$nodeId'")
            ->field('intergral,money')
            ->find();
        if ($integralExchange)
            $reExchange = $integralExchange['money'] / $integralExchange['intergral'];
        else
            $reExchange = false;
        return $reExchange;
    }

    /**
     * 检查商城积分分组信息
     *
     * @param string $amount 订单金额 string $nodeId 商户号
     * @return string $reAmount 可使用的积分数
     * @author johnzeng
     */
    public function getUseIntergral($amount, $nodeId) {
        // 查询规则
        $where = array(
            "node_id" => $nodeId, 
            "status" => '1');
        $reAmount = 0;
        $ruleList = M('tintegral_rules ')->where($where)
            ->order('rev_amount desc')
            ->select();
        // 取得总规则信息
        $ruleType = D('SalePro', 'Service')->getNodeRule($nodeId, 
            'tintegral_rule_main');
        switch ($ruleType) {
            case 1: // 不限制使用积分
                $reAmount = $amount;
                break;
            case 2: // 限制使用积分
                foreach ($ruleList as $k => $v) {
                    
                    if ($amount >= $v['rev_amount']) {
                        
                        $reAmount = $v['use_amount'];
                        break;
                    }
                }
                break;
            default: // 不使用积分
                $reAmount = 0;
                break;
        }
        
        return $reAmount;
    }
}