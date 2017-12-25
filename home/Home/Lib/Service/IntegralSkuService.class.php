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
class IntegralSkuService {

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

    public function __construct() {
    }

    /**
     * Description of SkuService 查询Sku分类是否存在
     *
     * @param array $info 分类信息 int $nodeId 商户标识 int $goodId 商品Id bloor $isChange
     *            是否变更规格
     * @return array $info
     * @author john_zeng
     */
    public function checkSkuType($info, $nodeId, $goodId, $isChange = false) {
        // 读取数据库中的规格id
        $tmp_array = array();
        if (is_array($info)) {
            foreach ($info as $k => &$v) {
                $v['data_id'] = $this->getSku($v['name'], $nodeId, $goodId, 
                    $isChange);
                $tmp_new_id = $v['newid'];
                $tmp_array[$tmp_new_id] = $v['data_id']['id'];
                if (is_array($v['list'])) {
                    foreach ($v['list'] as $lk => &$lv) {
                        $lv['data_id'] = $this->getSkuDetail($lv['val'], 
                            $nodeId, $goodId, $v['data_id']['id']);
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
    public function checkSkuPro($info, $newIdList, $goodsId, $nodeId) {
        $confirm = array(
            'msg' => true, 
            'content' => '');
        foreach ($info as $k => $v) {
            $sku_detail_id = $this->changeSkuId($newIdList, $v['newid']);
            $skuInfo = $this->addSkuInfo($v, $sku_detail_id, $goodsId, $nodeId);
            
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
     * @param str $key1 需要比对的键值1 str $key2 需要比对的键值2 array $array1 比对数组1 array
     *            $array2 比对数组2
     * @return array 数组比对结果
     * @author john_zeng
     */
    public function checkArray($key1, $key2, $array1, $array2) {
        $return_info = false;
        $tmp_array1 = array();
        $tmp_array2 = array();
        foreach ($array1 as $v) {
            $tmp_array1[] = $v[$key1];
        }
        foreach ($array2 as &$v) {
            if ('sku_detail_id' == $key1) {
                $tmp_array2[] = str_replace(',', '#', $v[$key2]);
            } else {
                $tmp_array2[] = $v[$key2];
            }
        }
        $num = count(array_diff($tmp_array1, $tmp_array2));
        if ($num > 0) {
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
     * @param int $nodeId 商户标识 int $mId tmarketing_info的id int $bId
     *            tbatch_info的id
     * @return int sku分类数
     * @author john_zeng
     */
    public function checkIsSkuPro($nodeId, $bId = 0, $mId = 0) {
        $tmp_count = 0;
        $map = array(
            'node_id' => $nodeId);
        if ($bId > 0)
            $map['b_id'] = $bId;
        if ($mId > 0)
            $map['m_id'] = $mId;
        if (0 === $mId && 0 === $bId)
            return $tmp_count;
        $tmp_count = M('tecshop_goods_integral_sku')->where($map)->count();
        return (int) $tmp_count;
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
        $table = 'tgoods_sku_integral_info';
        $field = 'remain_num';
        $tmpArray = array();
        $return = array(
            'msg' => true, 
            'info' => '');
        foreach ($skuInfo as $v) {
            $skuOldDetailId = $v['sku_detail_id'];
            if ('-1' != $v['remain'] || '-1' != $v['remain'])
                $tmpArray[$skuOldDetailId] = $v['remain_num'] + $v['remain'];
            else
                $tmpArray[$skuOldDetailId] = '-1';
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
     * @param array $info 规格名称 int $nodeId 商户标识 int $goodsId 商品id
     * @return int 返回规格ID
     * @author john_zeng
     */
    public function getSku($info, $nodeId, $goodId, $isChange) {
        $map = array(
            'node_id' => $nodeId, 
            'goods_id' => $goodId, 
            'sku_name' => $info, 
            'status' => 0);
        $sku_id = M('tgoods_integral_sku')->where($map)
            ->field('id')
            ->find();
        if (true === $isChange) {
            $data = array(
                'status' => 1);
            M('tgoods_integral_sku')->where($map)
                ->data($data)
                ->save();
            $sku_id = NULL;
        }
        // if ($sku_id != NULL) {
        // $data = array('status' => 1);
        // M('tgoods_integral_sku')->where($map)->data($data)->save();
        // }
        if ($sku_id == NULL) {
            $data = array(
                'node_id' => $nodeId, 
                'sku_name' => $info, 
                'goods_id' => $goodId, 
                'status' => 0, 
                'sort' => 0);
            $sku_id['id'] = M('tgoods_integral_sku')->data($data)->add();
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
        $skuInfo = M('tgoods_sku_integral_info')->where($map)
            ->field('id')
            ->find();
        if (isset($skuInfo['id'])) {
            $map = array(
                'skuinfo_id' => $skuInfo['id'], 
                'status' => 0);
            $skuEcshopInfo = M('tecshop_goods_integral_sku')->where($map)
                ->field('id')
                ->find();
            $skuEcshopId = $skuEcshopInfo['id'];
        }
        return $skuEcshopId;
    }

    /**
     * Description of SkuService 查询商品规格值表是否存在并生成，返回id
     *
     * @param array $info 规格名称 int $nodeId 商户标识 int $goodsId 商品id int $skuId
     *            规格id
     * @return int 返回规格id
     * @author john_zeng
     */
    public function getSkuDetail($info, $nodeId, $goodId, $skuId) {
        $map = array(
            'node_id' => $nodeId, 
            'goods_id' => $goodId, 
            'sku_detail_name' => $info, 
            'sku_id' => $skuId, 
            'status' => 0);
        $sku_id = M('tgoods_sku_integral_detail')->where($map)
            ->field('id')
            ->find();
        if ($sku_id == NULL) {
            $data = array(
                'node_id' => $nodeId, 
                'sku_detail_name' => $info, 
                'goods_id' => $goodId, 
                'sku_id' => $skuId, 
                'status' => 0, 
                'sort' => 0);
            $sku_id['id'] = M('tgoods_sku_integral_detail')->data($data)->add();
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
    public function changeSkuId($idArray, $new_array) {
        $new_array = array_filter($new_array);
        foreach ($new_array as &$v) {
            $v = $idArray[$v];
        }
        return implode('#', $new_array);
    }

    /**
     * Description of SkuService 添加sku商品信息
     *
     * @param array $info 商品信息 str $detailId 规格值id集合 int $goodsId 商品id int
     *            $nodeId 商户唯一标识
     * @return int 返回商品id
     * @author john_zeng
     */
    public function addSkuInfo($info, $detailId, $goodsId, $nodeId) {
        $detailId = str_replace(',', '#', $detailId);
        $mep = array(
            'sku_detail_id' => $detailId, 
            'goods_id' => $goodsId, 
            'node_id' => $nodeId, 
            'status' => 0);
        $skuInfo = M('tgoods_sku_integral_info')->where($mep)
            ->field('id, remain_num, storage_num')
            ->find();
        if (! isset($skuInfo['id'])) {
            $tmp_data = array(
                'node_id' => $nodeId, 
                'goods_id' => $goodsId, 
                'sku_detail_id' => $detailId, 
                'market_price' => $info['price'], 
                'storage_num' => $info['num'], 
                'remain_num' => $info['num'], 
                'status' => 0, 
                'sort' => 0);
            $sku_id = M('tgoods_sku_integral_info')->data($tmp_data)->add();
            $skuInfo['storage_num'] = $info['num'];
            $skuInfo['remain_num'] = $info['num'];
            $skuInfo['msg'] = $sku_id;
            $skuInfo['content'] = '系统出错，更新sku数据失败';
        } else {
            // 已设置不限的商品不允许设置有限
            if ('不限' == $info['num'] || '' == $info['num'])
                $info['num'] = '-1';
            if ('-1' == $skuInfo['remain_num'] && '-1' != $info['num']) {
                $skuInfo['msg'] = false;
                $skuInfo['content'] = '已设置不限的商品不允许设置有限';
            } else {
                if ($skuInfo['remain_num'] > $info['num'])
                    $nums = $skuInfo['storage_num'] -
                         ($skuInfo['remain_num'] - $info['num']);
                else
                    $nums = $skuInfo['storage_num'] +
                         ($info['num'] - $skuInfo['remain_num']);
                $tmp_data = array(
                    'node_id' => $nodeId, 
                    'goods_id' => $goodsId, 
                    'sku_detail_id' => $detailId, 
                    'market_price' => $info['price'], 
                    'storage_num' => $nums, 
                    'remain_num' => $info['num'], 
                    'status' => 0, 
                    'sort' => 0);
                $sku_id = M('tgoods_sku_integral_info')->where(
                    array(
                        'id' => $skuInfo['id']))
                    ->data($tmp_data)
                    ->save();
                $skuInfo['storage_num'] = $nums;
                $skuInfo['remain_num'] = $info['num'];
                $skuInfo['msg'] = $sku_id;
                $skuInfo['content'] = '系统出错，更新sku数据失败';
            }
        }
        return $skuInfo;
    }

    /**
     * Description of SkuService 添加上架sku商品信息
     *
     * @param array $info 商品信息 int $nodeId 商户唯一标识 int $mId tmarketing_info的id
     *            int $bId tbatch_info的id bloor $other 是否需要回滚tgood_info库存
     * @return int $sku_id //返回上架商品id
     * @author john_zeng
     */
    public function addGoodsSkuInfo($info, $nodeId, $mId, $bId = 0, 
        $other = false) {
        $returnInfo = true;
        foreach ($info as $v) {
            $v['newid'] = str_replace(',', '#', $v['newid']);
            // 判断是否设置不限
            if ('' === $v['num'] || '不限' === $v['num'])
                $v['num'] = '-1';
            else
                $v['num'] = (int) $v['num'];
            $mep = array(
                'sku_detail_id' => $v['newid']);
            $field = 'id, storage_num, remain_num, goods_id';
            $getSkuInfo = $this->getOneSkuInfo($mep, 'tgoods_sku_integral_info', 
                $field);
            $tmp_data = array();
            $skuInfo = array();
            M()->startTrans();
            if ($getSkuInfo['id']) {
                unset($mep);
                $mep = array(
                    'skuinfo_id' => $getSkuInfo['id'], 
                    'node_id' => $nodeId, 
                    'm_id' => $mId, 
                    'b_id' => $bId, 
                    'status' => 0);
                $skuInfo = M('tecshop_goods_integral_sku')->where($mep)
                    ->field('id, remain_num')
                    ->find();
                if (NULL === $skuInfo['id']) {
                    // 判断库存
                    if ($v['num'] > $getSkuInfo['remain_num'] &&
                         '-1' != $getSkuInfo['remain_num']) {
                        $returnInfo = false;
                    } else {
                        $tmpData = array(
                            'node_id' => $nodeId, 
                            'm_id' => $mId, 
                            'b_id' => $bId, 
                            'skuinfo_id' => $getSkuInfo['id'], 
                            'sale_price' => $v['price'], 
                            'storage_num' => $v['num'], 
                            'remain_num' => $v['num'], 
                            'sell_num' => 0, 
                            'status' => 0, 
                            'sort' => 0);
                        $sku_id = M('tecshop_goods_integral_sku')->data(
                            $tmpData)->add();
                        if ($sku_id) {
                            // 更改商品剩余库存
                            $mep = array(
                                'sku_detail_id' => $v['newid']);
                            if ('-1' != $getSkuInfo['remain_num']) {
                                $result = M('tgoods_sku_integral_info')->where(
                                    $mep)->setDec('remain_num', $v['num']);
                                if (false === $result) {
                                    M()->rollback();
                                    $this->error('系统出错,更新库存失败');
                                }
                                // 是否回滚
                                if (true === $other) {
                                    $result = M('tgoods_info')->where(
                                        array(
                                            'goods_id' => $getSkuInfo['goods_id']))->setDec(
                                        'remain_num', $v['num']);
                                    if (false === $result) {
                                        M()->rollback();
                                        $this->error('系统出错,更新库存失败');
                                    }
                                }
                            }
                        } else {
                            $returnInfo = false;
                        }
                    }
                } else {
                    // 判断是否设置不限
                    if ('-1' == $getSkuInfo['remain_num'] && $v['num'] == '-1') {
                        $storageNum = '-1';
                        $remainNum = '-1';
                    } else {
                        if ($v['num'] == '-1') {
                            $this->error('商品有限，不允许上架无限');
                        }
                        $storageNum = $v['num'] + $skuInfo['sell_num'];
                        $remainNum = $v['num'];
                    }
                    $tmpData = array(
                        'sale_price' => $v['price'], 
                        // 修改只更改剩余库存
                        'storage_num' => $storageNum, 
                        'remain_num' => $remainNum);
                    
                    $result = M('tecshop_goods_integral_sku')->where(
                        array(
                            'id' => $skuInfo['id']))->save($tmpData);
                    if (false === $result) {
                        M()->rollback();
                        $this->error('系统出错,更新库存失败');
                    }
                    // 判断已上架商品是否大于本次设置商品数
                    if ($skuInfo['remain_num'] > $v['num'] &&
                         '-1' != $getSkuInfo['remain_num'] && '-1' != $v['num']) {
                        // 更改商品剩余库存
                        $mep = array(
                            'sku_detail_id' => $v['newid']);
                        $result = M('tgoods_sku_integral_info')->where($mep)->setInc(
                            'remain_num', ($skuInfo['remain_num'] - $v['num']));
                        if ($result === false) {
                            M()->rollback();
                            $this->error('系统出错,更新库存失败');
                        }
                        if (true === $other) {
                            // 回滚库存
                            $result = M('tgoods_info')->where(
                                array(
                                    'goods_id' => $getSkuInfo['goods_id']))->setInc(
                                'remain_num', 
                                ($skuInfo['remain_num'] - $v['num']));
                            if ($result === false) {
                                M()->rollback();
                                $this->error('系统出错,更新库存失败');
                            }
                        }
                    } else {
                        if ('-1' != $getSkuInfo['remain_num']) {
                            if ('-1' == $v['num']) {
                                M()->rollback();
                                $this->error('商品有限，不允许上架无限');
                            }
                            $mep = array(
                                'sku_detail_id' => $v['newid']);
                            $result = M('tgoods_sku_integral_info')->where($mep)->setDec(
                                'remain_num', 
                                ($v['num'] - $skuInfo['remain_num']));
                            if ($result === false) {
                                M()->rollback();
                                $this->error('系统出错,更新库存失败');
                            }
                            // 回滚库存
                            if (true === $other) {
                                $result = M('tgoods_info')->where(
                                    array(
                                        'goods_id' => $getSkuInfo['goods_id']))->setDec(
                                    'remain_num', 
                                    $v['num'] - $skuInfo['remain_num']);
                                if ($result === false) {
                                    M()->rollback();
                                    $this->error('系统出错,更新库存失败');
                                }
                            }
                        }
                    }
                }
            } else {
                $returnInfo = false;
                $this->error('系统出错,更新库存失败1');
            }
            M()->commit();
        }
        return $returnInfo;
        
        // return $sku_id;
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
        $sku_list = M('tgoods_sku_integral_info')->where($data)
            ->field('sku_detail_id, market_price, storage_num, remain_num')
            ->select();
        return $sku_list;
    }

    /**
     * Description of SkuService 查询sku商品上架信息列表
     *
     * @param int $nodeId 商户唯一标识 int $mId tmarketing_info的id
     * @return array 返回查询到的信息列表
     * @author john_zeng
     */
    public function getSkuEcshopList($goods_id, $nodeId) {
        $sku_list = M()->table("tgoods_sku_integral_info e")->where(
            array(
                'node_id' => $nodeId, 
                'goods_id' => $goods_id))->select();
        return $sku_list;
    }

    /**
     * Description of SkuService 取得sku商品规格值信息
     *
     * @param array $sku_list sku商品列表
     * @return array 返回组合后的数组
     * @author john_zeng
     */
    public function getReloadSku($sku_list) {
        $tmp_str = '';
        $tmp_array = array();
        foreach ($sku_list as $v) {
            $tmp_array = explode('#', $v['sku_detail_id']);
            isset($tmp_array[0]) ? $tmp_str .= ',' . $tmp_array[0] : $tmp_str .= '';
            isset($tmp_array[1]) ? $tmp_str .= ',' . $tmp_array[1] : $tmp_str .= '';
            isset($tmp_array[2]) ? $tmp_str .= ',' . $tmp_array[2] : $tmp_str .= '';
        }
        
        $sku_info['list'] = array_filter(array_unique(explode(',', $tmp_str)));
        return $sku_info;
    }

    /**
     * Description of SkuService 取得sku商品规格值信息
     *
     * @param array $idAarray skudetail ID
     * @return array 返回查询到的信息列表
     * @author john_zeng
     */
    public function getSkuDetailList($idAarray) {
        $map['id'] = array(
            'in', 
            implode(',', $idAarray));
        $map['status'] = 0;
        $sku_list = M('tgoods_sku_integral_detail')->where($map)
            ->field('id, sku_detail_name, sku_id')
            ->select();
        return $sku_list;
    }

    /**
     * Description of SkuService 取得sku商品规格信息
     *
     * @param array $idAarray skudetail ID
     * @return array 规格值
     * @author john_zeng
     */
    public function getSkuTypeList($idAarray) {
        $tmp_str = '';
        foreach ($idAarray as $v) {
            $tmp_str .= $v['sku_id'] . ',';
        }
        $sku_list = array_filter(array_unique(explode(',', $tmp_str)));
        $map['id'] = array(
            'in', 
            implode(',', $sku_list));
        $map['status'] = 0;
        $sku_list = M('tgoods_integral_sku')->where($map)
            ->field('id, sku_name')
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
                $skuInfo = M('tgoods_sku_integral_detail')->where(
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
    // $goodsInfo = M('tgoods_sku_integral_info i')
    // ->join('tecshop_goods_integral_sku s on s.skuinfo_id = i.id')
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
        foreach ($array as $key => $val)
            $mep[$key] = $val;
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
        foreach ($array as $key => $val)
            $mep[$key] = $val;
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
                $tmp_str .= 'name:"' . $v['sku_name'] . '",';
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
        $goodsInfo = M()->table("tecshop_goods_integral_sku s")->join(
            'tgoods_sku_integral_info g on s.skuinfo_id = g.id')
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
     * @param array $goodsInfo 商品信息 int $nodeId 商户唯一标识 boolr $isSale 是否上架 int
     *            $mId tmarketing_info的id int $bId tbatch_info的id
     * @return array $goodsInfo 整合后的商品信息
     * @author john_zeng
     */
    public function makeGoodsListInfo($goodsInfo, $nodeId = false, $isSale = false, 
        $bId = 0, $mId = 0) {
        if (false === $isSale) {
            $map = array(
                // 'node_id' => $nodeId,
                'goods_id' => $goodsInfo['goods_id'], 
                'status' => '0');
            if (false != $nodeId)
                $map['node_id'] = $nodeId;
            $table = 'tgoods_sku_integral_info';
            $field = 'market_price, storage_num, remain_num';
            $skuInfo = $this->getMoreSkuInfo($map, $table, $field);
            $tmpArray = array();
            foreach ($skuInfo as $val) {
                // 将价格加入数组
                array_push($tmpArray, $val['market_price']);
            }
            $priceInfo = $this->compareArray($tmpArray);
            // 取得sku的价格区间
            if ($priceInfo['min'] == $priceInfo['max'])
                $goodsInfo['market_price'] = $priceInfo['max'];
            else
                $goodsInfo['market_price'] = $priceInfo['min'] . '～' .
                     $priceInfo['max'];
        } else {
            $map = array();
            if (false != $nodeId)
                $map['node_id'] = $nodeId;
            if ($bId > 0)
                $map['b_id'] = $bId;
            if ($mId > 0)
                $map['m_id'] = $mId;
            $table = 'tgoods_sku_integral_info';
            $field = 'market_price, storage_num, remain_num';
            $skuInfo = $this->getMoreSkuInfo($map, $table, $field);
            $tmpArray = array();
            $tmpStorage = 0;
            $tmpRemain = 0;
            $storageMax = false;
            $remainMax = false;
            foreach ($skuInfo as $val) {
                // 将价格加入数组
                array_push($tmpArray, $val['market_price']);
                if ('-1' === $val['storage_num'])
                    $storageMax = true;
                else
                    $tmpStorage = $tmpStorage + (int) $val['storage_num'];
                if ('-1' === $val['remain_num'])
                    $remainMax = true;
                else
                    $tmpRemain = $tmpRemain + (int) $val['remain_num'];
            }
            $priceInfo = $this->compareArray($tmpArray);
            // 取得sku的价格区间
            if ($priceInfo['min'] == $priceInfo['max'])
                $goodsInfo['batch_amt'] = $priceInfo['max'];
            else
                $goodsInfo['batch_amt'] = $priceInfo['min'] . '～' .
                     $priceInfo['max'];
                // 判断是否无限制sku商品
            if (true === $storageMax)
                $goodsInfo['storage_num'] = '-1';
            else
                $goodsInfo['storage_num'] = $tmpStorage;
                // 判断是否无限制sku商品
            if (true === $remainMax)
                $goodsInfo['remain_num'] = '-1';
            else
                $goodsInfo['remain_num'] = $tmpRemain;
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
                $tmp_str .= 'price:"' . floor($v['market_price']) . '",';
                $tmp_str .= 'sku_storag:"' . $v['storage_num'] . '",';
                $tmp_str .= 'sku_remain:"' . $v['remain_num'] . '",';
                // 上架信息
                isset($v['sale_price']) ? $tmp_str .= 'num:"' . $v['storage'] .
                     '",' : $tmp_str .= 'num:"' . $v['storage_num'] . '",';
                isset($v['sale_price']) ? $tmp_str .= 'remain_num:"' .
                     $v['remain'] . '",' : $tmp_str .= 'remain_num:"' .
                     $v['remain_num'] . '",';
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
            if ($val > $max)
                $max = $val;
            if ($val < $min)
                $min = $val;
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
                'node_id' => $nodeId), 'tbatch_info', 'm_id');
        M()->startTrans();
        foreach ($mInfo as $mArray) {
            $mId = $mArray['m_id'];
            if ($mId > 0) {
                $data = array(
                    'status' => '1');
                $flag = M('tbatch_info')->where("m_id = '{$mId}'")->save($data);
                $data = array(
                    'status' => '2');
                $flag = M('tmarketing_info')->where("id = '{$mId}'")->save(
                    $data);
                
                $data = array(
                    'is_commend' => '9');
                $flag = M('tecshop_goods_ex')->where("m_id = '{$mId}'")->save(
                    $data);
                if ($flag === false) {
                    M()->rollback();
                }
            }
        }
        M()->commit();
        $mInfo = M('tgoods_sku_integral_info')->where(
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
            $result = M('tecshop_goods_integral_sku')->where($filter)->setInc(
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
            $skuInfo = M('tecshop_goods_integral_sku')->where($filter)->find();
            if (false === $skuInfo) {
                $result = false;
            } else {
                M()->startTrans();
                if ($skuInfo['remain_num'] > $returnNum) {
                    $result = M('tecshop_goods_integral_sku')->where($filter)->setDec(
                        'remain_num', $returnNum);
                    if (false === $result) {
                        M()->rollback();
                        log_write("支付，已取消订单的库存扣减失败. 订单号：{$orderId}");
                    }
                } else {
                    // 库存不足
                    $ret = M('ttg_order_info')->where(
                        array(
                            "order_id" => $orderId))->save(
                        array(
                            'memo' => '已取消订单收到支付通知，但库存不足'));
                }
                M()->commit();
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
     * @param
     *
     * @return error
     * @author john_zeng
     */
    public function getError() {
        return $this->error;
    }
}
