<?php

/**
 * 卡券交易大厅
 *
 * @author bao
 */
class HallModel extends Model {

    protected $tableName = 'thall_goods';

    /**
     * 获取卡券交易大厅最热卡券
     *
     * @return array
     */
    public function getHotGoods() {
        $list = $this->alias('t')
            ->field(
            "t.*,(SELECT COUNT(*) FROM tnode_favorite WHERE relation_id = t.id AND fav_type = '0') AS fav_num")
            ->where("t.status='0' AND t.check_status='1' AND is_hot='1'")
            ->order("t.visit_num desc")
            ->select();
        return $list;
    }

    /**
     * 设置cookie用于记录交易大厅浏览过的商品
     *
     * @param $id 大厅商品id
     * @param $name cookie名称
     */
    public function setHallCookie($id, $name) {
        $cArr = cookie($name);
        if (empty($cArr) || ! is_array($cArr))
            $cArr = array();
        if (in_array($id, $cArr))
            return true; // 如果已存在返回
        $cArr[] = $id;
        if (count($cArr) > 3) { // 只保留3个
            array_shift($cArr);
        }
        cookie($name, $cArr, 
            array(
                'expire' => 30 * 3600, 
                'domain' => C('HALL_COOKIE_DOMAIN')));
    }

    /**
     * 获取大厅卡券出售数量
     *
     * @param unknown $goodsId
     */
    public function getSellNum($goodsId) {
        $bId = $this->where("goods_id='{$goodsId}'")->getField('id');
        if (! $bId) {
            $this->error = '未找到卡券信息';
            return false;
        }
        $map = array(
            'hall_id' => $bId, 
            'status' => array(
                'in', 
                '2,4'));
        $dataInfo = M('tnode_goods_book')->field("sum(book_num) as num")
            ->where($map)
            ->find();
        return empty($dataInfo['num']) ? '0' : $dataInfo['num'];
    }
    
    
    /**
     * 获取订单支付类型(暂时作废)
     *
     * @param $type 获取的类型 格式 $type='0,1,3'
     */
    public function getPayType($type = null) {
    	$goodsType = array(
    			'1' => '支付宝支付',
    			'2' => '银联支付',
    			'3' => '银行转账',
    	);
    	if (! is_null($type)) {
    		$type = array_flip(explode(',', $type));
    		$goodsType = array_intersect_key($goodsType, $type);
    	}
    	return $goodsType;
    }
    
    /**
     * 获取订单发票类型
     *
     * @param $type 获取的类型 格式 $type='0,1,3'
     */
    public function getInvoicePayType($type = null) {
    	$goodsType = array(
    			'0' => '不提供发票',
    			'1' => '地税通用发票',
    			'2' => '增值税发票',
    	);
    	if (! is_null($type)) {
    		$type = array_flip(explode(',', $type));
    		$goodsType = array_intersect_key($goodsType, $type);
    	}
    	return $goodsType;
    }
    
    /**
     * 获取订单状态
     *
     * @param $type 获取的类型 格式 $type='0,1,3'
     */
    public function getOrderStatus($type = null) {
    	$goodsType = array(
    			'0' => '待确认',
    			'1' => '等待付款',
    			'2' => '已支付',
    			'3' => '已关闭',
    			'4' => '已发货',
    			'5' => '待审核'
    	);
    	if (! is_null($type)) {
    		$type = array_flip(explode(',', $type));
    		$goodsType = array_intersect_key($goodsType, $type);
    	}
    	return $goodsType;
    }
    
    /**
     * 解析大厅图片json数据成数组
     */
    public function getImgArr($imgJson){
    	return json_decode($imgJson,true);
    }
    
    /**
     * 获取大厅图片第一张图片
     */
    public function getfirestImg($imgJson){
    	$arr = $this->getImgArr($imgJson);
    	return $arr[0];
    }
    
}