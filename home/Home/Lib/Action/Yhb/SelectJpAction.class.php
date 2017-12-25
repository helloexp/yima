<?php

/**
 * 营销活动奖品处理：奖项添加、奖品添加、抽奖基础控制 kk@2014年7月8日11:46:49
 */
class SelectJpAction extends YhbAction {

    private $self_flag = false;
    // 多版本控制，
    public $_authAccessMap = '*';

    public function _initialize() {
        parent::_initialize();
        $this->assign('self_flag', $this->self_flag);
        import('@.Vendor.CommonConst') or die('include file fail.');
    }

    public function index() {
        $goodsModel = D('Goods');
        $sourceType = array(
            '0' => '自建', 
            '1' => '采购', 
            '3' => '卡券', 
            '4' => '采购');
        $selectType = $goodsModel->getGoodsType('0,1,2,3,7,8,11');
        $goodsName = I('goods_name', null, 'mysql_real_escape_string');
        
        $join = "tfb_yhb_goods yg on yg.goods_id = g.goods_id";
        $batch_id = I('batch_id');
        
        $mark_info = M('tmarketing_info')->field('start_time,end_time')
            ->where(
            array(
                'id' => $batch_id, 
                'node_id' => $this->node_id))
            ->find();
        
        $map = array(
            'g.node_id' => $this->nodeId, 
            'g.status' => 0, 
            'yg.merchant_id' => $this->merchant_id, 
            'g.end_time' => array(
                'egt', 
                $mark_info['end_time']),  // 20150824000000
            'g.begin_time' => array(
                'elt', 
                $mark_info['start_time']));
        if (! empty($goodsName)) {
            $map['g.goods_name'] = array(
                'LIKE', 
                "%" . (string) $goodsName . '%');
        }
        
        $model = M('tgoods_info');
        
        $field = "g.id,g.goods_id as card_id,g.goods_id,g.`goods_name`,g.goods_type,g.`source`,g.add_time,g.pos_group_type,g.pos_group,g.begin_time,g.end_time,
				g.storage_type,g.storage_num,g.remain_num,g.validate_type,g.market_price,g.goods_amt,g.goods_discount,g.goods_image,
				g.node_id,g.mms_title,g.mms_text,g.sms_text,g.print_text,case when g.verify_begin_type = g.verify_end_type then g.verify_begin_type else 0 end verify_time_type,
			    g.verify_begin_date, g.verify_end_date,g.goods_desc ,(SELECT COUNT(*) FROM tgroup_pos_relation WHERE group_id=g.pos_group) AS store_num";
        import("ORG.Util.Page");
        $model->alias('g');
        $count = $model->join($join)
            ->where($map)
            ->count();
        // 分页处理
        $p = new Page($count, 10);
        $limit = $p->firstRow . ',' . $p->listRows;
        // 重置别名
        $model->alias('g');
        $list = $model->join($join)
            ->field($field)
            ->where($map)
            ->order('g.add_time desc')
            ->limit($limit)
            ->select();
        // 处理一下图片
        
        foreach ($list as &$v) {
            $v['goods_image_url'] = get_upload_url($v['goods_image']);
        }
        unset($v);
        
        $sourceColor = array( // 来源颜色
            '0' => 'tp1', 
            '1' => 'tp2', 
            '3' => 'tp3', 
            '4' => 'tp2');
        $goodsHelp = U('Home/Help/helpConter', 
            array(
                'type' => 7, 
                'left' => 'dzq'));
        
        // 招募活动改动了添加奖品的步骤，第一步为选择卡券或定额红包
        $next_step = I('next_step', '', '');
        if ($next_step) {
            $this->assign('next_step', urldecode($next_step));
        }
        foreach ($_REQUEST as $key => $val) {
            $p->parameter[$key] = urlencode($val); // 赋值给Page
        }
        // 分页显示
        // 新增sku数据查询
        $skuObj = D('Sku', 'Service');
        foreach ($list as &$vals) {
            // 取得商品sku数据
            $goods_sku_info = $skuObj->getSkuInfoList($vals['goods_id'], 
                $this->nodeId);
            if (NULL != $goods_sku_info) {
                // 分离商品表中的规格和规格值ID
                $goods_sku_list = $skuObj->getReloadSku($goods_sku_info);
                // 取得规格值表信息
                $goods_sku_detail_list = '';
                if (is_array($goods_sku_list['list']))
                    $goods_sku_detail_list = $skuObj->getSkuDetailList(
                        $goods_sku_list['list']);
                    
                    // 取得规格表信息
                if (is_array($goods_sku_detail_list))
                    $goods_sku_type_list = $skuObj->getSkuTypeList(
                        $goods_sku_detail_list);
                    // 规格值排列值
                $skutype = $skuObj->makeSkuType($goods_sku_type_list, 
                    $goods_sku_detail_list);
                // 价格列表
                $skudetail = $skuObj->makeSkuList($goods_sku_info);
                $vals['is_sku'] = true;
                $vals['skutype'] = $skutype;
                $vals['skudetail'] = $skudetail;
            } else {
                $vals['is_sku'] = false;
            }
        }
        
        $page = $p->show();
        $this->assign('list', $list);
        $this->assign("page", $page);
        $this->assign('sourceType', $sourceType);
        $this->assign('selectType', $selectType);
        $this->assign('stroeNum', $storeNum);
        $this->assign('sourceColor', $sourceColor);
        $this->assign('posStoreNum', $posStoreNum);
        $this->assign('sourceSelectStatus', $sourceSelectStatus);
        $this->assign('goodsTypeSelectStatus', $goodsTypeSelectStatus);
        $this->assign('goodsHelp', $goodsHelp);
        $this->assign('show_type', $show_type);
        // 显示时,切换到"创建和采购卡券"的部分
        $this->assign('showSetElec', I('get.showSetElec', ''));
        $this->display("MarketActive:SelectJp_index");
    }
}
