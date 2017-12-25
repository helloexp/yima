<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates and open the template
 * in the editor.
 */

/**
 * Description of SkuService
 *
 * @author john_zeng
 */
class SkuService {

    public $id_list = array();

    public $sku_storage_num = 0;
    // 总库存
    public $sku_storage_max = false;
    // 总库存不限
    public $sku_remain_num = 0;
    // 总剩余数
    public $sku_remain_max = false;
    // 剩余数不限
    public $error = '';
    //是否变更规格
    public $isChange = false;
    //商品ID
    public $goodsId = '';     
    //商户唯一标识 
    public $nodeId = '';    
    //规格更换信息
    public $changInfo = array();


    public function __construct() {
    }

    /**
     * Description of SkuService 查询Sku分类是否存在
     *
     * @param array $info 分类信息 
     * @param int $nodeId 商户标识 
     * @param int $goodId 商品Id 
     * @param bloor $isChange  是否变更规格 
     * 
     * @return array $info
     * @author john_zeng
     */
    public function checkSkuType($info, $isOrder = false) {
        // 读取数据库中的规格id
        $tmp_array = array();
        if (is_array($info)) {
            foreach ($info as &$v) {
                $v['data_id'] = $this->getSku($v['name'], $isOrder);
                $tmp_new_id = $v['newid'];
                $tmp_array[$tmp_new_id] = $v['data_id']['id'];
                if (is_array($v['list'])) {
                    foreach ($v['list'] as &$lv) {
                        $lv['data_id'] = $this->getSkuDetail($lv['val'], $v['data_id']['id']);
                        $tmp_new_id = $lv['newid'];
                        $tmp_array[$tmp_new_id] = $lv['data_id']['id'];
                    }
                }
            }
        }
        $this->id_list = $tmp_array;
        return $info;
    }

    /**
     * Description of SkuService 查询Sku商品信息
     *
     * @param array $info sku商品信息 int $newIdList 页面生成的ID和数据库中的ID数组列表 int
     *            $goodsId 商品Id int $nodeId 商户标识
     * @return array 返回失败信息
     * @author john_zeng
     */
    public function checkSkuPro($info, $goodsId) {
        $confirm = array(
            'msg' => true, 
            'content' => '');
        foreach ($info as $v) {
            $skuDetailId = $v['newid'];
            $skuInfo = $this->addSkuInfo($v, $skuDetailId, $goodsId);
            if (false === $skuInfo['msg']) {
                $confirm['msg'] = $skuInfo['msg'];
                $confirm['content'] = $skuInfo['content'];
            }
        }
        return $confirm;
    }

    /**
     * Description of SkuService 查询Sku商品信息
     *
     * @param str $key1 需要比对的键值1 
     * @param str $key2 需要比对的键值2 
     * @param array $newInfo 比对数组1 
     * @param array $oldInfo 比对数组2
     * 
     * @return array 数组比对结果
     * @author john_zeng
     */
    public function checkArray($key1, $key2, $newInfo, $oldInfo) {
        $return_info = false;
        $tmpArray1 = array();
        $tmpArray2 = array();
        foreach ($newInfo as $v) {
            $tmpArray1[] = $v[$key1];
        }
        foreach ($oldInfo as &$v) {
            if ('sku_detail_id' == $key1) {
                $tmpArray2[] = str_replace(',', '#', $v[$key2]);
            } else {
                $tmpArray2[] = $v[$key2];
            }
        }
        //新增加的规格信息
        $newNum = count(array_diff($tmpArray1, $tmpArray2));
        //减少的规格信息
        $oldNum = count(array_diff($tmpArray2, $tmpArray1));
        if ($newNum > 0 || $oldNum > 0) {
            //获取更新前的旧ID
            $this->changInfo['removeCount'] = count(array_diff($tmpArray2, $tmpArray1));
            $this->changInfo['remove'] = array_diff($tmpArray2, $tmpArray1);
            //取得新增加ID
            $this->changInfo['addCount'] = count(array_diff($tmpArray1, $tmpArray2));
            $return_info = true;
        }
        return $return_info;
    }

    /**
     * Description of SkuService 添加sku商品信息
     *
     * @param array $info 商品信息
     * @return string 商品价格
     * @author john_zeng
     */
    public function getSkuPrice($info) {
        if (isset($info[0]['price'])) {
            return $info[0];
        } else {
            return false;
        }
    }

    /**
     * Description of SkuService 查询上架的商品是否是sku产品
     *
     * @param int $bId tbatch_info的id
     * 
     * @return int sku分类数
     * @author john_zeng
     */
    public function checkIsSkuPro($bId) {
        $isSku = M('tgoods_info as g')
                ->join('tbatch_info as b on b.goods_id = g.goods_id')
                ->where(['b.id'=>$bId, 'b.node_id'=>$this->nodeId])
                ->getField('is_sku');
        return $isSku;
    }

    /**
     * Description of SkuService 查询上架的商品是否为普通商品改sku商品
     *
     * @param int $nodeId 商户标识 int $mId tmarketing_info的id int $status 商品状态
     * @return int $isChange 是否普通商品转sku商品
     * @author john_zeng
     */
    public function checkIsProToSku($nodeId, $mId = 0, $status = '1') {
        $isChange = 0;
        if ('1' === $status) { // 只处理上架商品
            $map = array(
                'bi.node_id' => $nodeId, 
                'bi.m_id' => $mId);
            $skuInfo = M('tbatch_info as bi')->join(
                "left join tgoods_info as gi on gi.goods_id = bi.goods_id")
                ->field('gi.is_sku')
                ->where($map)
                ->find();
            if ('1' === $skuInfo['is_sku']) {
                $isChange = 1;
            }
        }
        return $isChange;
    }

    /**
     * Description of SkuService 查询上架的商品是否是sku产品
     *
     * @param array $submitInfo 提交的库存信息 array $skuInfo 已上架sku信息
     * @return bloor 返回库存是否正确
     * @author john_zeng
     */
    public function checkRemainNum($submitInfo, $skuInfo) {
        $tmpArray = array();
        $return = array(
            'msg' => true, 
            'info' => '');
        foreach ($skuInfo as $v) {
            $skuOldDetailId = $v['sku_detail_id'];
            if ('-1' != $v['remain'] || '-1' != $v['remain']){
                $tmpArray[$skuOldDetailId] = $v['remain_num'] + $v['remain'];
            }else{
                $tmpArray[$skuOldDetailId] = '-1';
            }    
        }
        foreach ($submitInfo as $val) {
            $skuDetailId = str_replace(',', '#', $val['newid']);
            $remainNum = isset($tmpArray[$skuDetailId]) ? $tmpArray[$skuDetailId] : '';
            if ('' === $remainNum) {
                $return['msg'] = false;
                $return['info'] = 'sku商品信息不存在或已下架';
            } else {
                if ('-1' != $remainNum) {
                    $num = (int) $val['num'];
                    if ($num > $remainNum) {
                        $return['msg'] = false;
                        $return['info'] = 'sku库存不足' . $remainNum;
                    }
                }
            }
        }
        return $return;
    }

    /**
     * Description of SkuService 查询商品规格是否存在并生成，返回id
     *
     * @param array $info 规格名称 
     * @param int $nodeId 商户标识 
     * @param int $goodsId 商品id 
     * @param bloor $isChange  是否变更
     * 
     * @return int 返回规格ID
     * @author john_zeng
     */
    public function getSku($info, $isOrder = false) {
        $map = array(
            'node_id' => $this->nodeId, 
            'goods_id' => $this->goodsId, 
            'sku_name' => $info, 
            'status' => 0, 
            'type' => $isOrder ? 1 : 0);
        $sku_id = M('tgoods_sku')->where($map)
            ->field('id')
            ->find();
        
        if ($sku_id == NULL) {
            $data = array(
                'node_id' => $this->nodeId, 
                'sku_name' => $info, 
                'goods_id' => $this->goodsId, 
                'status' => 0, 
                'sort' => 0, 
                'type' => $isOrder ? 1 : 0);
            $sku_id['id'] = M('tgoods_sku')->data($data)->add();
        }
        return $sku_id;
    }

    /**
     * Description of SkuService 返回已上架skuecshopId
     *
     * @param string $skuDetailId sku拼接id
     * @return int 上架skuID
     * @author john_zeng
     */
    public function getEcshopSkuId($skuDetailId) {
        $skuEcshopId = 0;
        $map = array(
            'sku_detail_id' => $skuDetailId, 
            'status' => 0);
        $skuInfo = M('tgoods_sku_info')->where($map)
            ->field('id')
            ->find();
        if (isset($skuInfo['id'])) {
            $map = array(
                'skuinfo_id' => $skuInfo['id'], 
                'status' => 0);
            $skuEcshopInfo = M('tecshop_goods_sku')->where($map)
                ->field('id')
                ->find();
            $skuEcshopId = $skuEcshopInfo['id'];
        }
        return $skuEcshopId;
    }

    /**
     * Description of SkuService 查询商品规格值表是否存在并生成，返回id
     *
     * @param array $info 规格名称 
     * @param int $skuId 规格id
     * 
     * @return int 返回规格id
     * @author john_zeng
     */
    public function getSkuDetail($info, $skuId) {
        $map = array(
            'node_id' => $this->nodeId, 
            'goods_id' => $this->goodsId, 
            'sku_detail_name' => $info, 
            'sku_id' => $skuId, 
            'status' => 0);
        $sku_id = M('tgoods_sku_detail')->where($map)
            ->field('id')
            ->find();
        if ($sku_id == NULL) {
            $data = array(
                'node_id' => $this->nodeId, 
                'sku_detail_name' => $info, 
                'goods_id' => $this->goodsId, 
                'sku_id' => $skuId, 
                'status' => 0, 
                'sort' => 0);
            $sku_id['id'] = M('tgoods_sku_detail')->data($data)->add();
        }
        return $sku_id;
    }

    /**
     * Description of SkuService 通过newId取得对应的规格Id
     *
     * @param array $idArray 规格数组 str $newId newid数组
     * @return string 返回id字符串
     * @author john_zeng
     */
    public function changeSkuId($idArray, $newArray) {
        $newArray = array_filter($newArray);
        foreach ($newArray as &$v) {
            $v = $idArray[$v];
        }
        return implode('#', $newArray);
    }

    /**
     * Description of SkuService 添加sku商品信息
     *
     * @param array $info 商品信息 str $detailId 规格值id集合 int $goodsId 商品id int
     *            $nodeId 商户唯一标识
     * @return int 返回商品id
     * @author john_zeng
     */
    public function addSkuInfo($info, $detailId = 0, $goodsId = '') {
        
        if(0 != $detailId){
            $detailId = str_replace(',', '#', $detailId);
        } 
        $mep = [
            'sku_detail_id' => $detailId, 
            'goods_id' => $goodsId, 
            'node_id' => $this->nodeId, 
            'status' => 0
        ];
        //$mep = "sku_detail_id = '{$detailId}' and goods_id = '{$goodsId}' and node_id = {$this->nodeId} and status = 0";
        $skuInfoId = M('tgoods_sku_info')->where($mep)->getField('id');
        //数据信息
        $tmp_data = array(
            'node_id' => $this->nodeId, 
            'goods_id' => $goodsId, 
            'sku_detail_id' => $detailId, 
            'market_price' => $info['price'], 
            'storage_num' => $info['num'], 
            'remain_num' => $info['num'], 
            'status' => 0, 
            'sort' => 0);
        
        //判断数据是否存在
        if (! $skuInfoId) {
            $skuId = M('tgoods_sku_info')->data($tmp_data)->add();
        } else {
            $skuId = M('tgoods_sku_info')
                    ->where(array('id' => $skuInfoId))
                    ->data($tmp_data)
                    ->save();
        }
        
        $skuInfo['storage_num'] = $info['num'];
        $skuInfo['remain_num'] = $info['num'];
        $skuInfo['msg'] = $skuId;
        $skuInfo['content'] = '系统出错，更新数据失败';
        return $skuInfo;
    }

    /**
     * Description of SkuService 添加上架sku商品信息
     *
     * @param array $info 商品信息 
     * @param int $mId tmarketing_info的id
     * @param int $bId tbatch_info的id  
     * @param string $action 提交方式
     * 
     * @return int $sku_id //返回上架商品id
     * @author john_zeng
     */
    public function addGoodsSkuInfo($info, $mId, $bId = 0, $action = 'add') {
        $returnInfo = true;
        //判断是否编辑数据
        if('edit' === $action){
            $status = '1';
        }else{
            $status = '0';
        }
        foreach ($info as $v) {
            $v['num'] = (int) $v['num'];
            $sdid = empty($v['newid']) ? str_replace(',', '#', $v['id']) : $v['newid'];
            $mep = array('status'=> 0, 'sku_detail_id' => $sdid, 'node_id'=>$this->nodeId);
//            $mep = "status = 0 and sku_detail_id = '{$sdid}' and node_id = '{$this->nodeId}'";
            $skuInfoId = M('tgoods_sku_info')->where($mep)->getField('id');
            $skuInfo = array();
            if ($skuInfoId) {
                $mep = array(
                    'skuinfo_id' => $skuInfoId, 
                    'node_id' => $this->nodeId, 
                    'm_id' => $mId, 
                    'b_id' => $bId, 
                    'status' => $status);
                $skuInfo = M('tecshop_goods_sku')->where($mep)
                    ->field('id, remain_num')
                    ->find();
                if (NULL === $skuInfo['id']) {
                    $tmpData = array(
                        'node_id' => $this->nodeId, 
                        'm_id' => $mId, 
                        'b_id' => $bId, 
                        'skuinfo_id' => $skuInfoId, 
                        'sale_price' => $v['price'], 
                        'storage_num' => $v['num'], 
                        'remain_num' => $v['num'], 
                        'sell_num' => 0, 
                        'status' => $status, 
                        'sort' => 0);
                    $sku_id = M('tecshop_goods_sku')->data($tmpData)->add();
                    if (!$sku_id) {
                        $this->error = '添加规格商品信息失败';
                        return false;
                    }
                } else {
                    $storageNum = $v['num'] + $skuInfo['sell_num'];
                    $remainNum = $v['num'];
                    $tmpData = array(
                        'sale_price' => $v['price'], 
                        // 修改只更改剩余库存
                        'storage_num' => $storageNum, 
                        'remain_num' => $remainNum);
                    
                    $result = M('tecshop_goods_sku')
                            ->where(array('id' => $skuInfo['id']))
                            ->save($tmpData);
                    if (false === $result) {
                        $this->error = '系统出错,更新库存失败';
                        return false;
                    }
                }
            } else {
                $this->error = '规格商品不存在，请检查';
                return false;
            }
        }

        return $returnInfo;
        
        // return $sku_id;
    }

    /**
     * Description of SkuService 添加上架普通商品信息
     *
     * @param array $info 商品信息 
     * @param int $nodeId 商户唯一标识 
     * @param int $mId tmarketing_info的id
     * @param int $bId tbatch_info的id
     * @param string $action 是否编辑数据
     * 
     * @return int $sku_id //返回上架商品id
     * @author john_zeng
     */
    public function addNomorlGoodsInfo($info, $mId, $bId = 0, $action = 'add') {
        if('edit' === $action){
            $status = '1';
        }else{
            $status = '0';
        }
        //判断普通商品ID是否存在
        $map = array('m_id'=>$mId, 'node_id'=>$this->nodeId,'skuinfo_id'=>0);
        //普通商品信息
        $goodsInfo = M('tecshop_goods_sku')->where($map)->field('id,sell_num')->find();
        
        if(NUll === $goodsInfo){
            $tmpData = [
                'node_id' => $this->nodeId, 
                'm_id' => $mId, 
                'b_id' => $bId, 
                'skuinfo_id' => 0, 
                'sale_price' => $info['batchAmt'], 
                'storage_num' => $info['salesNum'], 
                'remain_num' => $info['salesNum'], 
                'sell_num' => 0, 
                'status' => $status, 
                'sort' => 0
                ];
            $reuslt = M('tecshop_goods_sku')->data($tmpData)->add();
            if (!$reuslt) {
                $this->error = '添加商品失败';
                return false;
            }
        }else{
            $tmpData = array(
                'skuinfo_id' => 0, 
                'sale_price' => $info['batchAmt'], 
                'storage_num' => $info['salesNum'] + $goodsInfo['sell_num'], 
                'remain_num' => $info['salesNum'], 
                'status' => $status, 
                'sort' => 0);
            $reuslt = M('tecshop_goods_sku')->where(array('id'=>$goodsInfo['id']))->data($tmpData)->save();
            if (false === $reuslt) {
                $this->error = '保存商品失败';
                return false;
            }
        }
        return $reuslt;
    }
    /**
     * Description of SkuService 查询sku商品信息列表
     *
     * @param int $goodsId 商品id int $nodeId 商户唯一标识
     * @return array 返回查询到的信息列表
     * @author john_zeng
     */
    public function getSkuInfoList($goodsId, $nodeId) {
        $data = array(
            'goods_id' => $goodsId, 
            'node_id' => $nodeId, 
            'status' => 0);
        $skuList = M('tgoods_sku_info')->where($data)
            ->field('sku_detail_id, market_price, storage_num, remain_num')
            ->select();
        if(!$skuList){
            $this->error = '获取原规格商品信息失败';
            return false;
        }else{
            return $skuList;
        }
    }

    /**
     * Description of SkuService 查询sku商品上架信息列表
     *
     * @param int $mId tmarketing_info的id
     * @param int $nodeId 商户唯一标识 
     * @param bool $isdown 商品是否已下架
     * 
     * @return array 返回查询到的信息列表
     * @author john_zeng
     */
    public function getSkuEcshopList($mId, $nodeId, $isdown = false) {
        if(false === $isdown){
            $status = 1;
        }else{
            if(true === $isdown){
                $isdown = 0;
            }
            $status = $isdown;   //如商品已上架时复制商品时需要状态为0
        }
        $data = array(
            'e.m_id' => $mId, 
            'e.node_id' => $nodeId, 
            'e.status' => $status, 
            'g.status' => 0);
        $skuList = M()->table("tecshop_goods_sku e")->join(
            'tgoods_sku_info g on g.id=e.skuinfo_id')
            ->where($data)
            ->field(
            'e.skuinfo_id, e.sale_price, e.storage_num, e.remain_num, g.sku_detail_id, g.market_price, g.storage_num as storage, g.remain_num as remain, g.goods_id')
            ->select();
        
        if(!$skuList){
            $this->error = '获取原规格商品信息失败';
            return false;
        }else{
            return $skuList;
        }
    }

    /**
     * Description of SkuService 取得sku商品规格值信息
     *
     * @param array $sku_list sku商品列表
     * @return array 返回组合后的数组
     * @author john_zeng
     */
    public function getReloadSku($sku_list) {
        $tmp_str = $tmp_str1 = $tmp_str2 = $tmp_str3 = '';
        $tmp_array = array();
        foreach ($sku_list as $v) {
            $tmp_array = explode('#', $v['sku_detail_id']);
            isset($tmp_array[0]) ? $tmp_str1 .= ',' . $tmp_array[0] : $tmp_str1 .= '';
            isset($tmp_array[1]) ? $tmp_str2 .= ',' . $tmp_array[1] : $tmp_str2 .= '';
            isset($tmp_array[2]) ? $tmp_str3 .= ',' . $tmp_array[2] : $tmp_str3 .= '';
        }
        //排列顺序不乱
        $tmp_str = $tmp_str1 . $tmp_str2 . $tmp_str3;
        
        $sku_info['list'] = array_filter(array_unique(explode(',', $tmp_str)));
        return $sku_info;
    }
    
    /**
     * Description of SkuService 取得sku商品规格值信息
     *
     * @param array $sku_list sku商品列表
     * @return array 返回组合后的数组
     * @author john_zeng
     */
    public function getReloadSkuType($skuTypInfo, $skuInfo) {
        $tmpArray1 = array();
        $tmpArray2 = array();
        foreach ($skuInfo as $v) {
            $tmp_str .= ',' . $v['sku_id'];
        }
        $tmpArray1 = array_filter(array_unique(explode(',', $tmp_str)));
        //重新排列
        foreach ($skuTypInfo as $val){
            $tmpArray2[$val['id']] = $val;
        }
        foreach ($tmpArray1 as &$vl){
           $vl =  $tmpArray2[$vl];
        }

        return $tmpArray1;
    }

    /**
     * Description of SkuService 取得sku商品规格值信息
     *
     * @param array $idAarray skudetail ID
     * @return array 返回查询到的信息列表
     * @author john_zeng
     */
    public function getSkuDetailList($idAarray) {
        $map['tgsd.id'] = array('in', implode(',', $idAarray));
        $map['tgsd.status'] = 0;
        $skuList = M()->table("tgoods_sku_detail tgsd")
                ->join('tgoods_sku tgs ON tgs.id = tgsd.sku_id')
                ->where($map)
                ->field('tgsd.id, tgsd.sku_detail_name, tgsd.sku_id, tgs.sku_name')
                ->order('tgsd.sku_id')
                ->select();

        if(!$skuList){
            $this->error = '获取原规格值表信息失败';
            return false;
        }else{
            return $skuList;
        }
    }

    /**
     * Description of SkuService 取得sku商品规格信息
     *
     * @param array $idAarray skudetail ID 
     * @param string $cycleType 订购类型 周期购
     * 
     * @return array 规格值
     * @author john_zeng
     */
    public function getSkuTypeList($idAarray, $cycleType = '') {
        $tmp_str = '';
        foreach ($idAarray as $v) {
            $tmp_str .= $v['sku_id'] . ',';
        }
        if ('' === $cycleType) {
            $field = 'id, sku_name';
        } else {
            $field = 'id, sku_name,CASE type WHEN 1 THEN ' . $cycleType .
                 ' END as type';
        }
        $sku_list = array_filter(array_unique(explode(',', $tmp_str)));
        $map['id'] = array(
            'in', 
            implode(',', $sku_list));
        $map['status'] = 0;
        $sku_list = M('tgoods_sku')->where($map)
            ->field($field)
            ->select();
        return $sku_list;
    }

    /**
     * Description of SkuService 取得上架sku商品规格中文名
     *
     * @param string $strId skudetail ID
     * @return string 规格中文信息
     * @author john_zeng
     */
    public function getSkuTypeName($strId) {
        $tmp_array = explode('#', $strId);
        $tmp_info = array();
        if (is_array($tmp_array)) {
            foreach ($tmp_array as $val) {
                $skuInfo = M('tgoods_sku_detail')->where(
                    array(
                        'id' => $val))
                    ->field('sku_detail_name')
                    ->find();
                $tmp_info[] = $skuInfo['sku_detail_name'];
            }
        }
        
        return implode('/', $tmp_info);
    }
    
    // /**
    // * Description of SkuService
    // * 取得sku商品关联的goodsId
    // * @param
    // * array $skuDetailInfo skudetail ID
    // * int $nodeId 商户唯一标识
    // *
    // * @return array 规格值
    // * @author john_zeng
    // */
    // public function getGoodsId($skuDetailInfo, $nodeId) {
    // $goodsIdList = array();
    // $skuNum = count($skuDetailInfo);
    // if($skuNum == 1){
    // $map = array(
    // 'i.node_id' => $nodeId,
    // 'i.sku_detail_id' => $skuDetailInfo[0]
    // );
    // }else{
    // $skuDetailInfo = implode(',', $skuDetailInfo);
    // $map = array(
    // 'i.node_id' => $nodeId,
    // 'i.sku_detail_id' => $skuDetailInfo
    // );
    // }
    // $goodsInfo = M('tgoods_sku_info i')
    // ->join('tecshop_goods_sku s on s.skuinfo_id = i.id')
    // ->field('s.b_id, i.sku_detail_id')->where($map)->find();
    // foreach ($goodsInfo as $v){
    // $key = $v['sku_detail_id'];
    // $goodsIdList[$key] = $v['b_id'];
    // }
    // return $goodsIdList;
    // }
    
    /**
     * Description of SkuService 取得sku表ID
     *
     * @param array $array //格式 ‘字段名’=> 值 string $table //表名 string $field
     *            //需要查询的字段信息，默认为所有信息
     * @return int skuid
     * @author john_zeng
     */
    public function getOneSkuInfo($array, $table, $field = null) {
        foreach ($array as $key => $val){
            $mep[$key] = $val;
        }   
        $skuInfo = M($table)->where($mep)
            ->field($field)
            ->find();
        if ($skuInfo) {
            return $skuInfo;
        } else {
            return false;
        }
    }

    /**
     * Description of SkuService 取得sku详细信息
     *
     * @param array $array //格式 ‘字段名’=> 值 string $table //表名 string $field
     *            //需要查询的字段信息，默认为所有信息
     * @return array skuid
     * @author john_zeng
     */
    public function getMoreSkuInfo($array, $table, $field = null) {
        foreach ($array as $key => $val){
            $mep[$key] = $val;
        }    
        $skuInfo = M($table)->where($mep)
            ->field($field)
            ->select();
        if ($skuInfo) {
            return $skuInfo;
        } else {
            return false;
        }
    }

    /**
     * Description of SkuService 生成静态页面规格数组
     *
     * @param array $type 规格信息 array $detail 规格值信息
     * @return string 生成静态json串
     * @author john_zeng
     */
    public function makeSkuType($type, $detail) {
        $tmp_str = '';
        if ($type != '') {
            foreach ($type as $v) {
                $tmp_str .= '{ ';
                $tmp_str .= 'id:"' . $v['id'] . '", ';
                $skuName = isset($v['sku_name']) ? $v['sku_name'] : '';
                $tmp_str .= 'name:"' . $skuName . '",';
                $type = isset($v['type']) ? $v['type'] : 0;
                $tmp_str .= 'ordertype:"' . $type . '",';
                $tmp_str .= 'list:[';
                foreach ($detail as $lv) {
                    if ($v['id'] == $lv['sku_id']) {
                        $tmp_str .= '{id:"' . $lv['id'] . '",val:"' .
                             $lv['sku_detail_name'] . '"},';
                    }
                }
                $tmp_str .= ']},';
            }
            return $tmp_str;
        } else {
            return $tmp_str;
        }
    }

    /**
     * Description of SkuService 生成规格数组
     *
     * @param array $type 规格信息 array $detail 规格值信息
     * @return array 规格数组
     * @author john_zeng
     */
    public function makeSkuTypeArray($type, $detail) {
        $tmp_array = array();
        if ($type != '') {
            foreach ($type as $key => $v) {
                $tmp_array[$key]['id'] = $v['id'];
                $tmp_array[$key]['name'] = $v['sku_name'];
                foreach ($detail as $lkey => $lv) {
                    if ($v['id'] == $lv['sku_id']) {
                        $tmp_array[$key]['list'][$lkey]['id'] = $lv['id'];
                        $tmp_array[$key]['list'][$lkey]['name'] = $lv['sku_detail_name'];
                    }
                }
            }
            return $tmp_array;
        } else {
            return FALSE;
        }
    }

    /**
     * Description of SkuService 生成静态页面规格值信息
     *
     * @param array $skuIdInfo sku上架id detail值 int $bId tbatch_info的id int $mId
     *            tmarketing_info的id
     * @return array b_id对应的skuId和中文信息
     * @author john_zeng
     */
    public function makeSkuOrderInfo($skuIdInfo, $bId = 0, $mId = 0) {
        $filter[] = "g.sku_detail_id in ('" . $skuIdInfo . "')";
        if ($bId > 0)
            $filter[] = "s.b_id = " . $bId;
        if ($mId > 0)
            $filter[] = "s.m_id = " . $mId;
        $goodsInfo = M()->table("tecshop_goods_sku s")->join(
            'tgoods_sku_info g on s.skuinfo_id = g.id')
            ->field('s.id, s.sale_price, g.sku_detail_id')
            ->where($filter)
            ->find();
        if ($goodsInfo) {
            $skuName = $this->getSkuTypeName($goodsInfo['sku_detail_id']);
            $goodsInfo['sku_name'] = $skuName;
            $goodsInfo['batch_amt'] = $goodsInfo['sale_price'];
        }
        return $goodsInfo;
    }

    /**
     * Description of SkuService 重新生成价格及库存信息
     *
     * @param array $goodsInfo 商品信息 
     * @param int $mId tmarketing_info的id 
     * @param int $bId tbatch_info的id
     * 
     * @return array $goodsInfo 整合后的商品信息
     * @author john_zeng
     */
    public function makeGoodsListInfo($goodsInfo, $mId = 0, $format = '￥') {
        $map = array('gs.node_id'=>$this->nodeId, 'gs.m_id'=>$mId, 'si.status'=>'0');
        $table = 'tecshop_goods_sku';
        $field = 'sale_price';
        $skuInfo = M('tecshop_goods_sku as gs')
                ->join('tgoods_sku_info as si ON gs.skuinfo_id = si.id')
                ->where($map)
                ->field('sale_price')
                ->select();
        
        if($skuInfo){
            $tmpArray = array();
            $priceInfo = array();
            foreach ($skuInfo as $val) {
                // 将价格加入数组
                array_push($tmpArray, $val['sale_price']);   
            }
            $priceInfo = $this->compareArray($tmpArray);
            // 取得sku的价格区间
            if ($priceInfo['min'] == $priceInfo['max']){
                $goodsInfo['batch_amt'] = $format.$priceInfo['max'];
            }else{
                $goodsInfo['batch_amt'] = $format.$priceInfo['min'] . '～' . $format.$priceInfo['max'];
            }    
        }
        if('0.00' == $goodsInfo['group_price']){
            $goodsInfo['group_price'] = $goodsInfo['batch_amt'];
        }
        if(!empty($goodsInfo['price'])){
            $goodsInfo['price'] = $goodsInfo['batch_amt'];
        }
        return $goodsInfo;
    }
    /**
     * Description of SkuService 生成订单信息信息
     *
     * @param array sku上架信息
     * @return string 生成静态json串
     * @author john_zeng
     */
    public function makeSkuList($goodsInfo) {
        $tmp_str = '';
        if ($goodsInfo) {
            foreach ($goodsInfo as &$v) {
                $tmp_str .= '{ ';
                $tmp_str .= 'id:"' . str_replace('#', ',', $v['sku_detail_id']) .
                     '", ';
                $tmp_str .= 'price:"' . $v['sale_price'] . '",';
                // 上架信息
                if (isset($v['sale_price'])) {
                    $tmp_str .= 'sellprice:"' . $v['sale_price'] . '",';
                    $storageNum = isset($v['storage_num']) ? $v['storage_num'] : 0;
                    $tmp_str .= 'sku_storag:"' . $storageNum . '",' ;
                    $remainNum = isset($v['remain_num']) ? $v['remain_num'] : 0;
                    $tmp_str .= 'sku_remain:"' . $remainNum . '",';
                    $sellNum = isset($v['sell_num']) ? $v['sell_num'] : 0;
                    $tmp_str .= 'sell_num:"' . $sellNum . '",';
                }
                
                isset($v['sale_price']) ? $tmp_str .= 'num:"' . $v['storage'] . '",' : $tmp_str .= 'num:"' . $v['storage_num'] . '",';
                isset($v['sale_price']) ? $tmp_str .= 'remain_num:"' . $v['remain'] . '",' : $tmp_str .= 'remain_num:"' . $v['remain_num'] . '",';
                $tmp_str .= '},';
            }
            return $tmp_str;
        } else {
            return $tmp_str;
        }
    }

    /**
     * Description of SkuService 重新生成商品数组
     *
     * @param array $info sku上架商品信息 string $key 重组键名
     * @return array 返回重组后的sku上架信息
     * @author john_zeng
     */
    public function reloadSkuInfo($info, $key) {
        $returnAarray = array();
        if (is_array($info)) {
            foreach ($info as $v) {
                $tmp_id = $v[$key];
                $returnAarray[$tmp_id] = $v;
            }
        }
        
        return $returnAarray;
    }

    /**
     * Description of SkuService 替换数组中的值
     *
     * @param array $array 需要替换的数组 string $filter 分隔符 string $search 查找需要替换的值
     *            string $replace 需要替换的值
     * @return array 返回替换好的数组
     * @author john_zeng
     */
    public function replaceArray($array, $filter, $search, $replace) {
        $tmp_str = '';
        if (is_array($array)) {
            $tmp_str = implode($filter, $array);
            $tmp_str = str_replace($search, $replace, $tmp_str);
            $array = explode($filter, $tmp_str);
        } else {
            $tmp_str = str_replace($search, $replace, $array);
            $array = explode($filter, $tmp_str);
        }
        return $array;
    }

    /**
     * Description of SkuService 比对数组中的最大值和最小值
     *
     * @param array $info 需要比对的数组
     * @return array 返回比对好的数组
     * @author john_zeng
     */
    public function compareArray($info) {
        $max = $info[0];
        $min = $info[0];
        foreach ($info as $val) {
            if ($val > $max){
                $max = $val;
            }    
            if ($val < $min){
                $min = $val;
            }    
        }
        return array(
            'max' => $max, 
            'min' => $min);
    }

    /**
     * Description of SkuService 下架商品
     *
     * @param string $goodsId 下架商品ID int $nodeId 商户唯一标识
     * @return array 返回比对好的数组
     * @author john_zeng
     */
    public function Offline($goodsId, $nodeId) {
        // 取得商品的mId
        $mInfo = $this->getMoreSkuInfo(
                array(
                    'goods_id' => $goodsId, 
                    'node_id' => $nodeId ), 
                'tbatch_info',
                'm_id');
        foreach ($mInfo as $mArray) {
            $mId = $mArray['m_id'];
            if ($mId > 0) {
                $data = array('status' => '1');
                $flag = M('tbatch_info')->where("m_id = '{$mId}'")->save($data);
                $data = array('status' => '2');
                $flag = M('tmarketing_info')->where("id = '{$mId}'")->save(
                    $data);
                
                $data = array(
                    'is_commend' => '9');
                $flag = M('tecshop_goods_ex')->where("m_id = '{$mId}'")->save($data);
                if ($flag === false) {
                    M()->rollback();
                }
            }
        }
        $mInfo = M('tgoods_sku_info')->where(
            array(
                'goods_id' => $goodsId, 
                'node_id' => $nodeId))->save(
            array(
                'status' => 1));
    }

    /**
     * Description of SkuService 回退sku商品库存
     *
     * @param int $returnNum 返回商品数 int $skuId sku关联ID int $orderId 订单ID
     * @return bloor 返回操作是否成功
     * @author john_zeng
     */
    public function returnGoodsNum($returnNum = 0, $skuId = 0, $orderId = false) {
        if (false != $orderId) {
            $skuInfo = M('ttg_order_info_ex')->where(
                array(
                    'order_id' => $orderId))
                ->field('ecshop_sku_id, goods_num')
                ->find();
            if (NULL === $skuInfo) {
                $result = 'noSku';
            } else {
                $skuId = $skuInfo['ecshop_sku_id'];
                $returnNum = $skuInfo['goods_num'];
            }
        }
        if ($skuId > 0 && $returnNum > 0) {
            $filter[] = "id=" . $skuId;
            $filter[] = "storage_num <> '-1'";
            $result = M('tecshop_goods_sku')->where($filter)->setInc(
                'remain_num', $returnNum);
        } else {
            $result = 'noSku';
        }
        return $result;
    }

    /**
     * Description of SkuService 已取消订单支付状态处理
     *
     * @param int $returnNum 返回商品数 int $skuId sku关联ID int $orderId 订单ID
     * @return bloor 返回操作是否成功
     * @author john_zeng
     */
    public function returnPayGoodsNum($returnNum = 0, $skuId = 0, $orderId = false, 
        $isCheckOrder = false) {
        if (false != $isCheckOrder) {
            $skuInfo = M('ttg_order_info_ex')->where(
                array(
                    'order_id' => $orderId))
                ->field('ecshop_sku_id, goods_num')
                ->find();
            if (NULL === $skuInfo) {
                return false;
            } else {
                $skuId = $skuInfo['ecshop_sku_id'];
                $returnNum = $skuInfo['goods_num'];
            }
        }
        if (! empty($skuId) && ! empty($returnNum)) {
            $filter[] = "id=" . $skuId;
            // 取得sku信息
            $skuInfo = M('tecshop_goods_sku')->where($filter)->find();
            if (false === $skuInfo) {
                $result = false;
            } else {
                if ($skuInfo['remain_num'] > $returnNum) {
                    $result = M('tecshop_goods_sku')->where($filter)->setDec(
                        'remain_num', $returnNum);
                    if (false === $result) {
                        log_write("支付，已取消订单的库存扣减失败. 订单号：{$orderId}");
                    }
                } else {
                    // 库存不足
                    $result = M('ttg_order_info')
                        ->where(array("order_id" => $orderId))
                        ->save(array('memo' => '已取消订单收到支付通知，但库存不足'));
                }
            }
        } else {
            $result = false;
        }
        return $result;
    }

    /**
     * Description of SkuService 取得sku展示信息
     *
     * @param string $skuId sku生成后的ID
     * @return string 返回规格信息 如 白色/37码
     * @author john_zeng
     */
    public function getSkuNameList($skuId) {
        $skuArray = explode('#', $skuId);
        $getArray = self::getSkuDetailList($skuArray);
        $string = '';
        if (false != $getArray) {
            foreach ($getArray as $val) {
                $string = $string . $val['sku_detail_name'] . ',';
            }
            $tmpArray = array_filter(explode(',', $string));
            $string = implode('/', $tmpArray);
        }
        return $string;
    }

    /**
     * Description of SkuService 返回错误
     *
     * PreViewChannelModel@param
     *
     * @return error
     * @author john_zeng
     */
    public function getError() {
        return $this->error;
    }

    /**
     * 检验订购订单日期
     *
     * @param type $goodsId
     * @param type $deliveryDate
     * @return string
     */
    public function checkBookOrderDeliveryDate($goodsId, $deliveryDate) {
        $bookOrderConfig = M('tbatch_info as tbi')
                ->join('tgoods_info tgi ON tbi.goods_id = tgi.goods_id')
                ->where(array('tbi.id' => $goodsId))
                ->getfield('tgi.config_data');
        $bookOrderConfig = json_decode($bookOrderConfig, TRUE);
        if ($bookOrderConfig['cycle']['cycle_type'] == 1 &&
             ($deliveryDate > 31 || $deliveryDate < 1)) {
            $deliveryDate = 'error';
        } elseif ($bookOrderConfig['cycle']['cycle_type'] == 2 &&
             ($deliveryDate > 7 || $deliveryDate < 1)) {
            $deliveryDate = 'error';
        }
        return $deliveryDate;
    }

    /**
     * 获取订购订单的配送日期
     *
     * @param type $type 类型配置 月、周、日
     * @param type $setNum 配送日配置
     * @param type $bookDate 上一次配送日
     * @return type char 8 下一次配送日
     */
    public function getBookOrderDeliveryDate($type, $setNum, $bookDate) {
        switch ($type) {
            case '1':
                if (strlen($setNum) == '1') {
                    $setNum = '0' . $setNum;
                }
                if ($bookDate == '') {
                    $dateToday = date('d');
                    $dispatchingDate = date('Ym') . $setNum;
                    $dateMonth = date('m');
                    if ($setNum == '31') {
                        if ($dateToday == $setNum && $dateMonth != '12' &&
                             $dateMonth != '07' && $dateMonth != '01') {
                            $dispatchingDate = date('Ymd', 
                                strtotime("+1 months", 
                                    strtotime($dispatchingDate)) - 1);
                        } elseif ($dateMonth == '01') {
                            $dispatchingDate = date('Ymd', 
                                strtotime("+1 months", 
                                    strtotime($dispatchingDate)) - 86401);
                        } else {
                            $dispatchingDate = date('Ymd', 
                                strtotime("+1 months", 
                                    strtotime($dispatchingDate)));
                        }
                    }
                    
                    if ($setNum == '30') {
                        if ($dateToday >= $setNum && $dateMonth != '01') {
                            $dispatchingDate = date('Ymd', 
                                strtotime("+1 months", 
                                    strtotime($dispatchingDate)));
                        } elseif ($dateToday >= $setNum && $dateMonth == '01') {
                            $dispatchingDate = date('Ymd', 
                                strtotime("+1 months", 
                                    strtotime($dispatchingDate)) - 1);
                        } elseif ($dateMonth == '02') {
                            $dispatchingDate = date('Y') . '0330';
                        }
                    }
                    
                    if ($setNum == '29' && $dateMonth == '01') {
                        $leapYear = date('Y') % 4;
                        if ($leapYear == 0) {
                            $dispatchingDate = date('Ymd', 
                                strtotime("+1 months", 
                                    strtotime($dispatchingDate)));
                        } else {
                            $dispatchingDate = date('Ymd', 
                                strtotime("+1 months", 
                                    strtotime($dispatchingDate)) - 1);
                        }
                    }
                    
                    if ($dateToday >= $setNum) {
                        $dispatchingDate = date('Ymd', 
                            strtotime("+1 months", strtotime($dispatchingDate)));
                    }
                } else {
                    $dateMonth = substr($bookDate, 4, 2);
                    if ($setNum > '28') {
                        if ($dateMonth != '12' && $dateMonth != '07' &&
                             $dateMonth != '01' && $dateMonth != '02') {
                            if ($setNum == '31') {
                                $dispatchingDate = date('Ymd', 
                                    strtotime("+1 months", strtotime($bookDate)) -
                                     1);
                            } else {
                                $dispatchingDate = date('Ymd', 
                                    strtotime("+1 months", strtotime($bookDate)));
                            }
                        } elseif ($dateMonth == '01') {
                            $leapYear = date('Y') % 4;
                            if ($leapYear == 0) {
                                $dispatchingDate = date('Y') . '0229';
                            } else {
                                $dispatchingDate = date('Y') . '0228';
                            }
                        } elseif ($dateMonth == '02') {
                            $dispatchingDate = date('Y') . '03' . $setNum;
                        } elseif ($dateMonth == '12' || $dateMonth == '07') {
                            $dispatchingDate = date('Ymd', 
                                strtotime("+1 months", strtotime($bookDate)));
                        }
                    } else {
                        $dispatchingDate = date('Ymd', 
                            strtotime("+1 months", strtotime($bookDate)));
                    }
                }
                break;
            case '2':
                if ($bookDate == '') {
                    $weekName = date('w');
                    if ($weekName >= $setNum) {
                        $tmpStr = '-' . ($weekName - $setNum) . ' day';
                        $tmpDate = strtotime(date('Ymd', strtotime($tmpStr)));
                        $dispatchingDate = date('Ymd', 
                            strtotime('+1 week', $tmpDate));
                    } elseif ($weekName < $setNum) {
                        $tmpStr = '+' . ($setNum - $weekName) . ' day';
                        $dispatchingDate = date('Ymd', strtotime($tmpStr));
                    }
                } else {
                    $dispatchingDate = date('Ymd', 
                        strtotime("+1 week", strtotime($bookDate)));
                }
                break;
            default:
                if ($bookDate == '') {
                    $dispatchingDate = date('Ymd', strtotime('+1 day'));
                } else {
                    $dispatchingDate = date('Ymd', 
                        strtotime("+1 day", strtotime($bookDate)));
                }
        }
        return $dispatchingDate;
    }

    /**
     * Description of SkuService 取得订单类型信息
     *
     * @param array $skuNameList sku规格信息
     * @return string 返回规格信息 如 白色/37码
     * @author john_zeng
     */
    public function getCycleType($skuNameList) {
        $cycleType = false;
        if (is_array($skuNameList)) {
            foreach ($skuNameList as $val) {
                if (isset($val['ordertype'])) {
                    $cycleType = (int) $val['ordertype'];
                }
            }
        }
        return $cycleType;
    }
    
    /**
     * 获取商品已销售数量
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param string  $deliveryFlag   商品信息  
     * 
     * @return boolean 
     */
    //获取已售卖商品数
    public function getSaleNum($mId){
        $salesInfo = M('tecshop_goods_sku')->field("SUM(sell_num) as salesnum")->where(array('m_id'=>$mId, 'node_id'=>$this->nodeId))->find();
        if(!$salesInfo){
            $salesNum = 0;
        }  else {
            $salesNum = $salesInfo['salesnum'];
        }
        return $salesNum;
    }
    
    /**
     * 检查商品修改后是否更改规格
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param array  $newInfo   新上架商品信息
     * @param string $goodsId    商品ID 
     * 
     * @return boolean 
     */
    //获取已售卖商品数
    public function checkGoodsDiff($newInfo){
        //获取商品是否SKU标识
        $isSku = M('tgoods_info')->where(array('goods_id'=>$this->goodsId))->getField('is_sku');
        if(false === $isSku){
            $this->error = '获取商品信息失败';
            return false;
        }
        if('1' == $isSku){
            $isSku = true;
        }else{
            $isSku = false;
        }
        //判断是否普通变规格
        if($isSku != $newInfo['isSku']){
            //原商品为规格商品
            if(false === $isSku){
                // 添加规格并重新生成规格信息
                self::checkSkuType($newInfo['skuName']);
            }else{
                //所有商品下架
                return self::OfflineSkuAll();
            }
        }else{
            if(true === $isSku){
                // 检查规格是否变动
                $tmpSkuInfo = array();
                foreach ($newInfo['skuName'] as $v) {
                    foreach ($v['list'] as $lv){
                        array_push($tmpSkuInfo, $lv);
                    }    
                }
                //获取已有规格信息
                $goodsSkuInfo = self::getSkuInfoList($this->goodsId, $this->nodeId);
                // 分离商品表中的规格和规格值ID
                $goodsSkuList = self::getReloadSku($goodsSkuInfo);
                // 取得规格值表信息
                $goodsSkuDetailList = self::getSkuDetailList($goodsSkuList['list']);
                // 取得规格表信息
                $goodsSkuTypeList = self::getSkuTypeList($goodsSkuDetailList);  
                // 检查规格是否变动
                self::checkSkuInfo('id', 'id', $newInfo['skuName'], $goodsSkuTypeList, true);
                // 检查规格值是否变动
                self::checkSkuInfo('id', 'id', $tmpSkuInfo, $goodsSkuDetailList);
            }
            // 检查商品组合是否变动
            if (true == $this->isChange) {
                // 添加规格并重新生成规格信息
                self::checkSkuType($newInfo['skuName']);
            }
        }
    }
    /**
     * 检查商品规格是否有变化
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param string $key1    新商品键值 
     * @param string $key2    旧商品键值 
     * @param array $newInfo     新商品信息
     * @param array $oldInfo     旧商品信息
     * @param boolen $isType     是否规格
     * 
     * @return boolean 
     */
    //获取已售卖商品数
    public function checkSkuInfo($key1, $key2, $newInfo, $oldInfo, $isType = false){
        $this->isChange = self::checkArray($key1, $key2, $newInfo, $oldInfo, $isType);
        if(true === $this->isChange && false === $isType){
            //判断更新的是规格表还是规格值表
            $isDetailTable = in_array_value_or_key('', 'sku_id', $oldInfo);
            //是否有需要移除的信息
            if($this->changInfo['removeCount'] > 0){
                //处理规格值表信息
                if(true == $isDetailTable){
                    $result = self::OfflineSkuList($this->changInfo['remove']);
                }else{
                    $result = self::OfflineSkuList($this->changInfo['remove'], true);
                }
                if(false === $result){
                    return false;
                }
            }
        }
        return true;
    }
    /**
     * 下架当前商品所有规格信息
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param string $goodsId    商品ID 
     * @param string $nodeId     商户唯一标识
     * @param array $changeInfo     需要更换的规格信息
     * 
     * @return boolean 
     */
    //获取已售卖商品数
    public function OfflineSkuAll(){
        //下架商品大规格
        $result = M('tgoods_sku')->where(array('goods_id'=>$this->goodsId, 'node_id'=>$this->nodeId))->save(array('status'=>'1'));
        if(!$result){
            $this->error = '商品规格类下架失败';
            return false;
        }
        //下架商品规格值
        $result = M('tgoods_sku_detail')->where(array('goods_id'=>$this->goodsId, 'node_id'=>$this->nodeId))->save(array('status'=>'1'));
        if(!$result){
            $this->error = '商品规格值下架失败';
            return false;
        }
        return true;
    }
    /**
     * 下架当前商品所有规格值信息
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param string $isSkuType    是否为规格类型表
     * @param string $id     需要下架的ID号
     * 
     * @return boolean 
     */
    //获取已售卖商品数
    public function OfflineSkuList($info, $isSkuType = false){
        foreach ($info as $val){
            if(true === $isSkuType){
                //下架商品大规格
                $result = M('tgoods_sku')->where(array('goods_id'=>$this->goodsId, 'node_id'=>$this->nodeId, 'id'=>$val))->save(array('status'=>'1'));
                if(!$result){
                    $this->error = '商品规格类下架失败';
                    return false;
                }
                //下架相关商品规格值
                self::OfflineSkuDetail($val);
                //下架相关商品信息
                self::OfflineSkuGoodsInfo();

            }else{
                //下架相关商品规格值
                self::OfflineSkuDetail($val);
                //下架相关商品信息
                self::OfflineSkuGoodsInfo();
            }
         }
        return true;
    }
    
    /**
     * 下架当前商品规格值
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param string $id     需要下架的ID号
     * 
     * @return boolean 
     */
    //获取已售卖商品数
    public function OfflineSkuDetail($id){
        //下架相关商品规格值
        $result = M('tgoods_sku_detail')
                ->where(array('goods_id'=>$this->goodsId, 'node_id'=>$this->nodeId, 'sku_id'=>$id))
                ->save(array('status'=>'1'));
        if(!$result){
            $this->error = '商品规格值下架失败';
            return false;
        }
        return $result;
    }
    
    /**
     * 获取规格值信息
     *
     * @author John zeng<zengc@imageco.com.cn>
     * 
     * @return boolean 
     */
    //获取已售卖商品数
    public function getSkuListInfo($goodsInfo){
        $skuInfoList = self::getSkuEcshopList($goodsInfo['m_id'], $this->nodeId, true);
        // 判断是否有sku数据
        $goodsInfo['sku'] = array();

        // 分离商品表中的规格和规格值ID
        $goodsSkuList = self::getReloadSku($skuInfoList);
        
        // 取得规格值表信息
        if (is_array($goodsSkuList['list'])){
            $goodsSkuDetailList = self::getSkuDetailList($goodsSkuList['list']);
        }    

        if ($goodsInfo['is_order'] == '2') {
            $goodsConfigData = json_decode($goodsInfo['config_info'], TRUE);
            $goodsInfo['config_info'] = $goodsConfigData['cycle'];
            $goodsInfo['end_days'] = (int) ((strtotime($goodsInfo['end_time']) - time()) / 86400);
        }
        // 取得规格表信息
        if (is_array($goodsSkuDetailList)) {
            if (isset($goodsInfo['config_info']['cycle_type'])){
                // 取得订购类型
                $goodsSkuTypeList = self::getSkuTypeList(
                        $goodsSkuDetailList, 
                        $goodsInfo['config_info']['cycle_type']
                    );
            }else{
                $goodsSkuTypeList = self::getSkuTypeList($goodsSkuDetailList);
            }    
        }
        // 价格列表
        $goodsInfo['skuDetail'] = self::makeSkuList($skuInfoList);
        // 取得sku价格
        $goodsInfo = self::makeGoodsListInfo($goodsInfo, $goodsInfo['m_id']);

        $goodsInfo['skuType'] = self::makeSkuType($goodsSkuTypeList, $goodsSkuDetailList);
            
        return $goodsInfo;
    }
    
    /**
     * 下架当前商品信息
     *
     * @author John zeng<zengc@imageco.com.cn>
     * 
     * @return boolean 
     */
    //获取已售卖商品数
    public function OfflineSkuGoodsInfo(){
        //下架相关商品规格值
        $result = M('tgoods_sku_info')
                ->where(array('goods_id'=>$this->goodsId, 'node_id'=>$this->nodeId))
                ->save(array('status'=>'1'));
        if(!$result){
            $this->error = '商品信息下架失败';
            return false;
        }
        return $result;
    } 
   /**
     * 获取sku商品标识
     *
     * @author John zeng<zengc@imageco.com.cn>
     * 
     * @return boolean 
     */
    //获取已售卖商品数 
    public function getIsSkuInfo($goodsId){
        //下架相关商品规格值
        $isSku = M('tgoods_info')->where("goods_id = '{$goodsId}'")->getField('is_sku');
        
        return $isSku;
    } 
    
    /**
     * Description of SkuService 将sku信息重新排序
     *
     * @param string $strId skudetail ID
     * @return string 规格中文信息
     * @author john_zeng
     */
    public function getNewSkuTypeList($skuListInfo, $oldList) {
        $newListInfo = array();
        $tmpArray = array();
        //重新组合规格商品排列
        foreach ($skuListInfo as $info){
            $tmpArray[$info['id']] = $info;
        }
        foreach ($oldList['list'] as $val){
            $newListInfo[] = $tmpArray[$val];
        }
        return $newListInfo;
    }
}
