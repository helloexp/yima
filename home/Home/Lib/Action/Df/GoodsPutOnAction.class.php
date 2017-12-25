<?php

// 商品上下架
class GoodsPutOnAction extends BaseAction {
    
    // public $_authAccessMap = '*';
    public $mk_price_arr = array();

    public $city_arr = array();

    public $classify_arr = array();

    public $goods_status_arr = array(
        '0' => '已上架', 
        '1' => '已下架', 
        '2' => '已过期');

    public $img_path;
    // 卡券图片存放路径
    public $store_mid = '';

    public function _initialize() {
        parent::_initialize();
        $map = array(
            'node_id' => $this->nodeId);
        $this->classify_arr = M('tfb_df_pointshop_catalog')->where($map)
            ->order('sort asc')
            ->getField('id, class_name', true);
        $this->assign('classify_arr', $this->classify_arr);
        
        // $this->img_path = C('BATCH_IMG_PATH_NAME') . '/' . $this->nodeId .
        // '/';
        $this->store_mid = M('tmarketing_info')->where(
            "node_id = '{$this->nodeId}' and batch_type = '1001'")->getField(
            'id');
        $this->assign('store_mid', $this->store_mid);
        $this->assign('goods_status_arr', $this->goods_status_arr);
    }
    
    // 上架管理
    public function index() {
        $goods_name = I('goods_name', '');
        $min_price = I('min_price', '');
        $max_price = I('max_price', '');
        $min_date = I('begin_date', '');
        $max_date = I('end_date', '');
        $status = I('status', '');
        $classify = I('classify', '');
        $sort_field = I('sort_field', '');
        $sort_arr = array(
            'sale_num', 
            'remain_num', 
            'lock_num', 
            'click_count');
        $sort_sql = 'status asc , id desc';
        if ($sort_field != '') {
            do {
                $arr = explode('|', $sort_field);
                if (count($arr) != 2)
                    break;
                if (! in_array($arr[0], $sort_arr))
                    break;
                $sort_sql = $arr[0] . ($arr[1] == 'asc' ? ' asc' : ' desc');
            }
            while (0);
        }
        
        $where = array(
            'a.node_id' => $this->nodeId, 
            'T.is_delete' => 0,  // 显示未删除的信息
            '_string' => ' 1=1 ');
        
        if ($goods_name != "")
            $where['T.batch_name'] = array(
                'exp', 
                "like '%$goods_name%'");
        
        if ($min_price != '')
            $where['T.batch_amt'] = array(
                'egt', 
                $min_price);
        
        if ($max_price != '')
            $where['T.batch_amt'] = array(
                'elt', 
                $max_price);
        
        if ($min_date != '')
            $where['_string'] .= "and if(t.update_time is null or t.update_time = '', t.add_time, t.update_time) >= '{$min_date}000000'";
        
        if ($max_date != '')
            $where['_string'] .= "and if(t.update_time is null or t.update_time = '', t.add_time, t.update_time) <= '{$max_date}000000'";
        
        if ($status != '')
            $where['T.status'] = $status;
        
        if ($classify != '')
            $where['a.ecshop_classify'] = $classify;
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = M()->table('tfb_df_pointshop_goods_ex a')
            ->field('a.*,T.*')
            ->join('tbatch_info T ON a.node_id=T.node_id and t.id = a.b_id')
            ->where($where)
            ->count();
        
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $subQuery = M()->table('tfb_df_pointshop_goods_ex a')
            ->field(
            "a.ecshop_classify, a.label_id, T.*, m.click_count,g.goods_image, (SELECT sum(oe.goods_num) FROM tfb_df_pointshop_order_info o, tfb_df_pointshop_order_info_ex oe WHERE o.node_id = t.node_id AND o.order_id = oe.order_id AND oe.b_id = a.b_id AND o.order_type = '2' AND o.order_status = '0' AND o.pay_status IN ('2','3')) as sale_num, (SELECT sum(oe.goods_num) FROM tfb_df_pointshop_order_info o, tfb_df_pointshop_order_info_ex oe WHERE o.node_id = t.node_id AND o.order_id = oe.order_id AND oe.b_id = a.b_id AND o.order_type = '2' AND o.order_status = '0' AND o.pay_status IN ('1')) as lock_num")
            ->join('tbatch_info T ON a.node_id=T.node_id and t.id = a.b_id')
            ->join('tgoods_info g on T.goods_id =  g.goods_id')
            ->join('tmarketing_info m on m.id =  a.m_id')
            ->where($where)
            ->buildSql();
        $list = M()->table($subQuery . ' a')
            ->order($sort_sql)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        $show = $Page->show(); // 分页显示输出
                               // 获取商品分类
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('list', $list); // 赋值分页输出
        $this->display('GoodsPutOn/index');
    }

    public function putOn() {
        $node_info = M('tnode_info')->where(
            array(
                'node_id' => $this->node_id))->find();
        $this->assign('node_info', $node_info);
        $this->display('GoodsPutOn/putOn');
    }

    public function Offline() {
        $m_id = I('id', 0, 'intval');
        if ($m_id == 0)
            $this->error('参数错误！');
        
        $map = array(
            'node_id' => $this->nodeId, 
            'id' => $m_id, 
            'batch_type' => '1002');
        $marketInfo = M('tmarketing_info')->where($map)->find();
        if (! $marketInfo)
            $this->error('参数错误！');
        
        $batchInfo = M('tbatch_info')->where("m_id = '{$m_id}'")->find();
        if ($batchInfo['status'] != '0')
            $this->error('该订单已经下线或过期，不能下架');
        if ($batchInfo['is_delete'] != '0')
            $this->error('该订单已经删除，不能下架');
        
        M()->startTrans();
        $data = array(
            'status' => '1');
        $flag = M('tbatch_info')->where("m_id = '{$m_id}'")->save($data);
        if ($flag === false)
            $this->error('下架失败！');
        
        $data = array(
            'is_commend' => '9');
        $flag = M('tfb_df_pointshop_goods_ex')->where("m_id = '{$m_id}'")->save(
            $data);
        if ($flag === false) {
            M()->rollback();
            $this->error('下架失败！');
        }
        
        M()->commit();
        
        $this->success('下架成功！');
    }

    public function Edit() {
        $m_id = I('id', 0, 'intval');
        $puton_flag = I('puton_flag', 0, 'intval');
        if ($m_id == 0)
            $this->error('参数错误！');
        
        $map = array(
            'node_id' => $this->nodeId, 
            'id' => $m_id, 
            'batch_type' => '1002');
        $marketInfo = M('tmarketing_info')->where($map)->find();
        if (! $marketInfo)
            $this->error('参数错误！');
        
        $goodsInfo = M('tbatch_info')->where("m_id = '{$m_id}'")->find();
        if ($goodsInfo['status'] == '0')
            $this->error('商品已启用，不能编辑', U('index'));
        if ($goodsInfo['status'] == '2')
            $this->error('商品已过期，不能编辑', U('index'));
        if ($goodsInfo['is_delete'] == '1')
            $this->error('商品已删除，不能编辑', U('index'));
        
        $goodsInfoEx = M('tfb_df_pointshop_goods_ex')->where("m_id = '{$m_id}'")->find();
        
        if ($this->isPost()) {
            $rules = array(
                'goods_desc' => array(
                    'null' => false, 
                    'maxlen_cn' => '200', 
                    'name' => '商品描述'), 
                'wap_info' => array(
                    'null' => false, 
                    'maxlen_cn' => '10000', 
                    'name' => '商品描述详情'), 
                'resp_img1' => array(
                    'null' => true, 
                    'name' => '第一张图片'), 
                'resp_img2' => array(
                    'null' => true, 
                    'name' => '第一张图片'), 
                'resp_img3' => array(
                    'null' => true, 
                    'name' => '第一张图片'), 
                'classify' => array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'name' => '所属分组', 
                    'inarr' => array_keys($this->classify_arr)), 
                // 'delivery_flag' => array('null'=>false,'name'=>'配送方式'),
                'batch_amt' => array(
                    'null' => false, 
                    'strtype' => 'number', 
                    'name' => '销售价格'), 
                'storage_type' => array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'name' => '总库存', 
                    'inarr' => array(
                        '0', 
                        '1')), 
                'market_show' => array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'name' => '商品市场价格', 
                    'inarr' => array(
                        '0', 
                        '1')), 
                'day_buy_num_flag' => array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'name' => '商品每日限购', 
                    'inarr' => array(
                        '0', 
                        '1')), 
                'person_buy_num_flag' => array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'name' => '每人限购', 
                    'inarr' => array(
                        '0', 
                        '1')), 
                'purchase_time_limit' => array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'name' => '是否限时购买', 
                    'inarr' => array(
                        '0', 
                        '1')));
            $reqData = $this->_verifyReqData($rules);
            
            $time_fmt = $reqData['purchase_time_limit'] == '0' ? 'Y-m-d' : 'Y-m-d H:i:s';
            $rules = array(
                'begin_date' => array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => $time_fmt, 
                    'name' => '商品销售开始时间'), 
                'end_date' => array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => $time_fmt, 
                    'name' => '商品销售结束时间'));
            $reqData = array_merge($reqData, $this->_verifyReqData($rules));
            
            $reqData['delivery_flag'] = I('delivery_flag');
            // 非商品类营销品 写死自提方式
            if ($goodsInfo['batch_class'] != '6')
                $reqData['delivery_flag'] = array(
                    '0');
            $rules = array();
            /*
             * if (!empty($reqData['delivery_flag'])) {
             * $reqData['delivery_flag'] = implode('-',
             * $reqData['delivery_flag']); } else $this->error("未选择是否配送");
             */
            // 凭证有效期校验
            $a = strstr($reqData['delivery_flag'], '0');
            if ($a !== false) {
                $rules['mms_title'] = array(
                    'null' => false, 
                    'maxlen_cn' => '10', 
                    'name' => '彩信标题');
                $rules['mms_info'] = array(
                    'null' => false, 
                    'maxlen_cn' => '100', 
                    'name' => '彩信内容');
                $rules['verify_time_type'] = array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'name' => '验证时间', 
                    'inarr' => array(
                        '0', 
                        '1'));
                
                if (I('verify_time_type', 0, 'intval') == '0') {
                    $rules['verify_begin_date'] = array(
                        'null' => false, 
                        'strtype' => 'datetime', 
                        'format' => 'Ymd', 
                        'name' => '验证开始时间');
                    $rules['verify_end_date'] = array(
                        'null' => false, 
                        'strtype' => 'datetime', 
                        'format' => 'Ymd', 
                        'name' => '验证结束时间');
                } else {
                    $rules['verify_begin_days'] = array(
                        'null' => false, 
                        'strtype' => 'int', 
                        'minval' => '0', 
                        'name' => '验证开始天数');
                    $rules['verify_end_days'] = array(
                        'null' => false, 
                        'strtype' => 'int', 
                        'minval' => '0', 
                        'name' => '验证结束天数');
                }
            }
            
            // 库存
            if ($reqData['storage_type'] == '1') {
                $minStorageNum = M()
                        ->table("tfb_df_pointshop_order_info_ex oe")
                        ->join('tfb_df_pointshop_order_info o ON o.order_id=oe.order_id')
                    ->where(
                    array(
                        'o.order_status' => '0', 
                        'oe.b_id' => $goodsInfo['id']))
                    ->getField('sum(oe.goods_num)');
                $rules['storage_num'] = array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'minval' => $minStorageNum, 
                    'name' => '总库存');
            }
            // 市场价格
            if ($reqData['market_show'] == '1')
                $rules['market_price'] = array(
                    'null' => false, 
                    'strtype' => 'number', 
                    'minval' => 0, 
                    'name' => '商品市场价格');
                // 每日限购
            if ($reqData['day_buy_num_flag'] == '1')
                $rules['day_buy_num'] = array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'minval' => '1', 
                    'name' => '商品每日限购');
                // 每人限购
            if ($reqData['person_buy_num_flag'] == '1')
                $rules['person_buy_num'] = array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'minval' => '1', 
                    'name' => '每人限购');
            
            $reqData = array_merge($reqData, $this->_verifyReqData($rules));
            // 关联商品处理
            $rgoods_ids = I('rgoods_ids');
            if ($rgoods_ids != '') {
                $arr = explode(',', $rgoods_ids);
                $cnt = count($arr);
                if ($cnt > 8)
                    $this->error('关联商品最多只能选择8个！');
                
                $cnt2 = M()->table(
                    'tfb_df_pointshop_goods_ex a, tmarketing_info b')
                    ->where(
                    "b.batch_type = '1002' and a.m_id = b.id and b.id in ({$rgoods_ids})")
                    ->count();
                if ($cnt != $cnt2)
                    $this->error('关联商品有误！');
            }
            
            // 图片处理
            $img_arr = array(
                'img1' => '', 
                'img2' => '', 
                'img3' => '');
            $i = 0;
            foreach ($img_arr as $k => &$v) {
                ++ $i;
                $tmp_name = $reqData['resp_' . $k];
                if ($tmp_name != '')
                    $v = $tmp_name;
            }
            unset($v);
            
            M()->startTrans();
            // =====================tmarketing_info更新============================
            if ($reqData['purchase_time_limit'] == '0') {
                $reqData['begin_date'] .= '000000';
                $reqData['end_date'] .= '235959';
            }
            $reqData['begin_date'] = date_clean($reqData['begin_date']);
            $reqData['end_date'] = date_clean($reqData['end_date']);
            
            $marketData = array(
                'start_time' => $reqData['begin_date'], 
                'end_time' => $reqData['end_date']);
            $flag = M('tmarketing_info')->where("id = '{$marketInfo['id']}'")->save(
                $marketData);
            if ($flag === false) {
                M()->rollback();
                $this->error('商品信息保存失败3！');
            }
            
            // =====================库存处理============================
            $goodsInfo = M('tbatch_info')->where("m_id = '{$m_id}'")->find();
            $goodsM = D('Goods');
            $flag = $goodsM->adjust_batch_storagenum($goodsInfo['id'], 
                $reqData['storage_num'], $m_id, '12');
            if ($flag === false) {
                M()->rollback();
                $this->error($goodsM->getError());
            }
            
            // =====================tbatch_info更新==========================
            $batchData = array();
            $batchData['batch_amt'] = $reqData['batch_amt'];
            $batchData['begin_time'] = $reqData['begin_date'] . '000000';
            $batchData['end_time'] = $reqData['end_date'] . '235959';
            if ($a !== false) {
                $batchData['info_title'] = $reqData['mms_title'];
                $batchData['use_rule'] = $reqData['mms_info'];
                $batchData['verify_begin_date'] = $reqData['verify_time_type'] ==
                     '0' ? $reqData['verify_begin_date'] . '000000' : $reqData['verify_begin_days'];
                $batchData['verify_end_date'] = $reqData['verify_time_type'] ==
                     '0' ? $reqData['verify_end_date'] . '235959' : $reqData['verify_end_days'];
                $batchData['verify_begin_type'] = $reqData['verify_time_type'];
                $batchData['verify_end_type'] = $reqData['verify_time_type'];
            }
            if ($reqData['storage_type'] == '0') {
                $batchData['storage_num'] = - 1;
                $batchData['remain_num'] = - 1;
            } else {
                $batchData['storage_num'] = $reqData['storage_num'];
                $batchData['remain_num'] = $reqData['storage_num'] -
                     $minStorageNum;
            }
            if ($puton_flag == 1) {
                $batchData['status'] = '0';
            }
            
            if ($batchData) {
                $flag = M('tbatch_info')->where("id = '{$goodsInfo['id']}'")->save(
                    $batchData);
                if ($flag === false) {
                    M()->rollback();
                    $this->error('商品信息保存失败1！');
                }
            }
            
            // =====================tfb_df_pointshop_goods_ex更新==========================
            $exData = array(
                'goods_desc' => $reqData['goods_desc'], 
                'ecshop_classify' => $reqData['classify'], 
                'wap_info' => $reqData['wap_info'], 
                'show_picture1' => $img_arr['img1'] ? $img_arr['img1'] : $goodsDataEx['show_picture1'], 
                'show_picture2' => $img_arr['img2'] ? $img_arr['img2'] : $goodsDataEx['show_picture2'], 
                'show_picture3' => $img_arr['img3'] ? $img_arr['img3'] : $goodsDataEx['show_picture3'], 
                'relation_goods' => $rgoods_ids, 
                'market_price' => $reqData['market_price'], 
                'day_buy_num' => $reqData['day_buy_num_flag'] == '0' ? - 1 : $reqData['day_buy_num'], 
                'person_buy_num' => $reqData['person_buy_num_flag'] == '0' ? - 1 : $reqData['person_buy_num'], 
                'market_show' => $reqData['market_show'], 
                'delivery_flag' => $reqData['delivery_flag'], 
                'purchase_time_limit' => $reqData['purchase_time_limit']);
            $flag = M('tfb_df_pointshop_goods_ex')->where(
                "id = '{$goodsInfoEx['id']}'")->save($exData);
            if ($flag === false) {
                M()->rollback();
                $this->error('商品信息保存失败2！' . M()->_sql());
            }
            
            // =====================tfb_df_pointshop_change_trace记录编辑流水==========================
            $changeData = array(
                'batch_id' => $marketInfo['id'], 
                'batch_type' => '1002', 
                'name' => $marketInfo['name'], 
                'start_time' => $reqData['begin_date'] . '000000', 
                'end_time' => $reqData['end_date'] . '235959', 
                'memo' => $reqData['goods_desc'], 
                'wap_info' => $reqData['wap_info'], 
                'defined_one_name' => $reqData['delivery_flag'], 
                // 'defined_two_name'=>$showFlag,
                'defined_three_name' => $reqData['person_buy_num_flag'] == '0' ? - 1 : $reqData['person_buy_num'], 
                'market_price' => $reqData['market_show'] == '1' ? $reqData['market_price'] : 0, 
                'group_price' => $reqData['batch_amt'], 
                'goods_num' => $reqData['storage_type'] == '1' ? $reqData['storage_num'] : - 1, 
                'buy_num' => $reqData['day_buy_num_flag'] == '0' ? 0 : $reqData['day_buy_num'], 
                'verify_begin_date' => $reqData['verify_time_type'] == '0' ? $reqData['verify_begin_date'] .
                     '235959' : $reqData['verify_begin_days'], 
                    'verify_end_date' => $reqData['verify_time_type'] == '0' ? $reqData['verify_end_date'] .
                     '235959' : $reqData['verify_end_days'], 
                    'verify_begin_type' => $reqData['verify_time_type'], 
                    'verify_end_type' => $reqData['verify_time_type'], 
                    'modify_time' => date('YmdHis'), 
                    'modify_type' => '1', 
                    'oper_id' => $this->user_id);
            $flag = M('tfb_df_pointshop_change_trace')->add($changeData);
            if ($flag === false) {
                M()->rollback();
                $this->error('商品信息编辑记录保存失败！' . M()->_sql());
            }
            
            M()->commit();
            node_log('旺财小店商品编辑', print_r($reqData, true));
            $this->success('商品编辑成功！' . ($puton_flag == 1 ? '商品上架成功！' : ''));
            exit();
        }
        
        $basegoodsInfo = M('tgoods_info')->where(
            "goods_id = '{$goodsInfo['goods_id']}'")->find();
        
        // 关联商品
        $rgoodsList = array();
        if ($goodsInfoEx['relation_goods'] != '') {
            $rgoodsList = M()->table(
                'tfb_df_pointshop_goods_ex a, tmarketing_info b, tbatch_info c, tgoods_info g')
                ->where(
                "b.batch_type = '1002' and a.m_id = b.id and a.b_id = c.id and c.goods_id = g.goods_id and b.id in ({$goodsInfoEx['relation_goods']})")
                ->field('b.id, g.goods_name, g.goods_image')
                ->select();
        }
        $node_info = M('tnode_info')->where(
            array(
                'node_id' => $this->node_id))->find();
        $this->assign('node_info', $node_info);
        $this->assign('rgoodsList', $rgoodsList);
        $this->assign('marketInfo', $marketInfo);
        $this->assign('goodsInfo', $goodsInfo);
        $this->assign('goodsInfoEx', $goodsInfoEx);
        $this->assign('basegoodsInfo', $basegoodsInfo);
        $this->assign('puton_flag', $puton_flag);
        $this->display('GoodsPutOn/Edit');
    }

    public function deleteAjax() {
        // 数据库中增加新字段 is_delete
        M()->startTrans();
        $batch_id = I('id');
        $data['is_delete'] = 1;
        $map = array(
            'm_id' => $batch_id, 
            'status' => array(
                'neq', 
                '0'), 
            'node_id' => $this->node_id);
        // 因为过期没有处理is_commend字段，这里要同步处理一下
        $tecshopData = array(
            'is_commend' => '9');
        $flag = M('tfb_df_pointshop_goods_ex')->where("m_id = '{$batch_id}'")->save(
            $tecshopData);
        if ($flag === false) {
            M()->rollback();
            $this->ajaxReturn(0, "删除失败！", 0);
        }
        // 如果是上架中的，不能被删除
        $res = M("tbatch_info")->where($map)->save($data);
        if ($res) {
            M()->commit();
            $this->ajaxReturn(1, "删除成功！", 1);
        } else {
            M()->rollback();
            $this->ajaxReturn(0, "删除失败,商品正在上架中！", 0);
        }
    }

    public function View() {
        $m_id = I('id', 0, 'intval');
        $puton_flag = I('puton_flag', 0, 'intval');
        $preview_flag = I('preview_flag', 0, 'intval');
        if ($m_id == 0)
            $this->error('参数错误！');
        
        $map = array(
            'node_id' => $this->nodeId, 
            'id' => $m_id, 
            'batch_type' => '1002');
        $marketInfo = M('tmarketing_info')->where($map)->find();
        if (! $marketInfo)
            $this->error('参数错误！');
        
        $goodsInfo = M('tbatch_info')->where("m_id = '{$m_id}'")->find();
        $goodsInfoEx = M('tfb_df_pointshop_goods_ex')->where("m_id = '{$m_id}'")->find();
        $basegoodsInfo = M('tgoods_info')->where(
            "goods_id = '{$goodsInfo['goods_id']}'")->find();
        
        // 关联商品
        $rgoodsList = array();
        if ($goodsInfoEx['relation_goods'] != '') {
            $rgoodsList = M()->table(
                'tfb_df_pointshop_goods_ex a, tmarketing_info b, tbatch_info c, tgoods_info g')
                ->where(
                "b.batch_type = '1002' and a.m_id = b.id and a.b_id = c.id and c.goods_id = g.goods_id and b.id in ({$goodsInfoEx['relation_goods']})")
                ->field('b.id, g.goods_name, g.goods_image')
                ->select();
        }
        $this->assign('rgoodsList', $rgoodsList);
        $this->assign('marketInfo', $marketInfo);
        $this->assign('goodsInfo', $goodsInfo);
        $this->assign('goodsInfoEx', $goodsInfoEx);
        $this->assign('basegoodsInfo', $basegoodsInfo);
        $this->assign('puton_flag', $puton_flag);
        $tpl_name = 'GoodsPutOn/View';
        if ($preview_flag == 1) {
            $tpl_name = 'GoodsPutOn/putOnPreview';
        }
        $this->display($tpl_name);
    }
    
    // public
    public function putOnSubmit() {
        // 数据校验
        // $classify_arr = M('tfb_df_pointshop_catalog')->where("node_id =
        // '{$this->nodeId}'")->getField('id', NULL);
        $rules = array(
            'goods_id' => array(
                'null' => false, 
                'name' => '营销活动号'), 
            'classify' => array(
                'null' => false, 
                'strtype' => 'int', 
                'name' => '所属分组', 
                'inarr' => array_keys($this->classify_arr)), 
            'batch_amt' => array(
                'null' => false, 
                'strtype' => 'number', 
                'name' => '销售价格'), 
            'storage_type' => array(
                'null' => false, 
                'strtype' => 'int', 
                'name' => '总库存', 
                'inarr' => array(
                    '0', 
                    '1')), 
            'market_show' => array(
                'null' => false, 
                'strtype' => 'int', 
                'name' => '商品市场价格', 
                'inarr' => array(
                    '0', 
                    '1')), 
            'day_buy_num_flag' => array(
                'null' => false, 
                'strtype' => 'int', 
                'name' => '商品每日限购', 
                'inarr' => array(
                    '0', 
                    '1')), 
            'person_buy_num_flag' => array(
                'null' => false, 
                'strtype' => 'int', 
                'name' => '每人限购', 
                'inarr' => array(
                    '0', 
                    '1')), 
            'purchase_time_limit' => array(
                'null' => false, 
                'strtype' => 'int', 
                'name' => '是否限时购买', 
                'inarr' => array(
                    '0', 
                    '1')), 
            // 'delivery_flag' => array('null'=>false,'name'=>'配送方式'),
            // 'begin_date' => array('null' => false, 'strtype' => 'datetime',
            // 'format' => 'Ymd', 'name' => '商品销售开始时间'),
            // 'end_date' => array('null' => false, 'strtype' => 'datetime',
            // 'format' => 'Ymd', 'name' => '商品销售结束时间'),
            'goods_desc' => array(
                'null' => false, 
                'maxlen_cn' => '200', 
                'name' => '商品描述'), 
            'wap_info' => array(
                'null' => false/* ,'maxlen_cn'=>'10000' */, 'name' => '商品描述详情'), 
            'resp_img1' => array(
                'null' => false, 
                'name' => '第一张图片'), 
            'resp_img2' => array(
                'null' => true, 
                'name' => '第一张图片'), 
            'resp_img3' => array(
                'null' => true, 
                'name' => '第一张图片'));
        
        $reqData = $this->_verifyReqData($rules);
        $time_fmt = $reqData['purchase_time_limit'] == '0' ? 'Y-m-d' : 'Y-m-d H:i:s';
        $rules = array(
            'begin_date' => array(
                'null' => false, 
                'strtype' => 'datetime', 
                'format' => $time_fmt, 
                'name' => '商品销售开始时间'), 
            'end_date' => array(
                'null' => false, 
                'strtype' => 'datetime', 
                'format' => $time_fmt, 
                'name' => '商品销售结束时间'));
        $reqData = array_merge($reqData, $this->_verifyReqData($rules));
        $reqData['delivery_flag'] = I('delivery_flag');
        
        // if($reqData['end_date'] > '20140930')
        // $this->error('商品销售结束日期不能大于9月30日');
        // 自提才需要配置短彩信内容、配置验证时间
        $rules = array();
        /*
         * if (!empty($reqData['delivery_flag'])) { $reqData['delivery_flag'] =
         * implode('-', $reqData['delivery_flag']); } else
         * $this->error("未选择是否配送"); $a = strstr($reqData['delivery_flag'], '0');
         */
        if ($a !== false) {
            $rules['verify_time_type'] = array(
                'null' => false, 
                'strtype' => 'int', 
                'name' => '验证时间', 
                'inarr' => array(
                    '0', 
                    '1'));
            $rules['mms_title'] = array(
                'null' => false, 
                'maxlen_cn' => '10', 
                'name' => '彩信标题');
            $rules['mms_info'] = array(
                'null' => false, 
                'maxlen_cn' => '100', 
                'name' => '彩信内容');
            
            if (I('verify_time_type', 0, 'intval') == '0') {
                $rules['verify_begin_date'] = array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd', 
                    'name' => '验证开始时间');
                $rules['verify_end_date'] = array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd', 
                    'name' => '验证结束时间');
            } else {
                $rules['verify_begin_days'] = array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'minval' => '0', 
                    'name' => '验证开始天数');
                $rules['verify_end_days'] = array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'minval' => '0', 
                    'name' => '验证结束天数');
            }
        }
        
        // 库存
        if ($reqData['storage_type'] == '1')
            $rules['storage_num'] = array(
                'null' => false, 
                'strtype' => 'int', 
                'minval' => '1', 
                'name' => '总库存');
            // 市场价格
        if ($reqData['market_show'] == '1')
            $rules['market_price'] = array(
                'null' => false, 
                'strtype' => 'number', 
                'minval' => 0, 
                'name' => '商品市场价格');
            // 每日限购
        if ($reqData['day_buy_num_flag'] == '1')
            $rules['day_buy_num'] = array(
                'null' => false, 
                'strtype' => 'int', 
                'minval' => '1', 
                'name' => '商品每日限购');
            // 每人限购
        if ($reqData['person_buy_num_flag'] == '1')
            $rules['person_buy_num'] = array(
                'null' => false, 
                'strtype' => 'int', 
                'minval' => '1', 
                'name' => '每人限购');
        
        $reqData = array_merge($reqData, $this->_verifyReqData($rules));
        
        // 凭证有效期校验
        
        if ($a !== false) {
            if ($reqData['verify_time_type'] == '0' &&
                 $reqData['verify_begin_date'] > $reqData['verify_end_date'])
                $this->error('验证结束时间不能大于验证开始时间');
            if ($reqData['verify_time_type'] == '1' &&
                 $reqData['verify_begin_days'] > $reqData['verify_end_days'])
                $this->error('验证结束时间不能大于验证开始时间');
        }
        
        // 查询渠道信息
        $map = array(
            'node_id' => $this->nodeId, 
            'type' => 5, 
            'sns_type' => 55);
        $channelInfo = M('tchannel')->where($map)->find();
        if (! $channelInfo)
            $this->error('没有DF积分商城渠道！');
            
            // 关联商品处理
        
        $rgoods_ids = I('rgoods_ids');
        if ($rgoods_ids != '') {
            $arr = explode(',', $rgoods_ids);
            $cnt = count($arr);
            if ($cnt > 8)
                $this->error('关联商品最多只能选择8个！');
            
            $cnt2 = M()->table('tfb_df_pointshop_goods_ex a, tmarketing_info b')
                ->where(
                "b.batch_type = '1002' and a.m_id = b.id and b.id in ({$rgoods_ids})")
                ->count();
            if ($cnt != $cnt2)
                $this->error('关联商品有误！');
        }
        
        // 图片处理
        $img_arr = array(
            'img1' => '', 
            'img2' => '', 
            'img3' => '');
        $i = 0;
        foreach ($img_arr as $k => &$v) {
            ++ $i;
            $tmp_name = $reqData['resp_' . $k];
            if ($tmp_name != '')
                $v = $tmp_name;
        }
        unset($v);
        
        M()->startTrans();
        try {
            // 商品初步校验
            $map = array(
                'node_id' => $this->nodeId, 
                'goods_id' => $reqData['goods_id']);
            // 'goods_type' => '6'
            
            $goodsInfo = M('tgoods_info')->where($map)->find();
            if (! $goodsInfo)
                throw new Exception('商品信息错误！');
                
                // 库存校验
            if ($goodsInfo['storage_type'] == '1' &&
                 $reqData['storage_type'] == '0')
                throw new Exception("该商品的总库存不能设置为不限！");
            if ($goodsInfo['storage_type'] == '1' &&
                 $reqData['goods_count'] > $goodsInfo['remain_num'])
                throw new Exception("商品库存不足！");
            
            if ($reqData['purchase_time_limit'] == '0') {
                $reqData['begin_date'] .= '000000';
                $reqData['end_date'] .= '235959';
            }
            $reqData['begin_date'] = date_clean($reqData['begin_date']);
            $reqData['end_date'] = date_clean($reqData['end_date']);
            $marketData = array(
                'batch_id' => '0', 
                'batch_type' => '1002', 
                'node_id' => $goodsInfo['node_id'], 
                'name' => $goodsInfo['goods_name'], 
                'start_time' => $reqData['begin_date'], 
                'end_time' => $reqData['end_date'], 
                'add_time' => date('YmdHis'), 
                'wap_info' => $reqData['wap_info'], 
                'status' => '1');
            
            $m_id = M('tmarketing_info')->add($marketData);
            if ($m_id === false)
                throw new Exception("营销活动保存失败！");
                
                // 扣减库存
            if ($reqData['storage_type'] == '1') {
                $goodsM = D('Goods');
                $flag = $goodsM->storagenum_reduc($goodsInfo['goods_id'], 
                    $reqData['storage_num'], $m_id, '11', '');
                if ($flag === false)
                    throw new Exception($goodsM->getError());
            }
            
            $goodsData = array(
                'batch_no' => $goodsInfo['batch_no'], 
                'batch_short_name' => $goodsInfo['goods_name'], 
                'batch_name' => $goodsInfo['goods_name'], 
                'node_id' => $goodsInfo['node_id'], 
                'user_id' => $this->user_id, 
                'batch_class' => $goodsInfo['goods_type'], 
                'batch_type' => '0', 
                'batch_img' => $goodsInfo['goods_image'], 
                'validate_times' => '1', 
                'batch_amt' => $reqData['batch_amt'], 
                'begin_time' => $reqData['begin_date'], 
                'end_time' => $reqData['end_date'], 
                'send_begin_date' => $reqData['begin_date'], 
                'send_end_date' => $reqData['end_date'], 
                'add_time' => date('YmdHis'), 
                'node_pos_group' => $goodsInfo['pos_group'], 
                'node_pos_type' => $goodsInfo['pos_group_type'], 
                'batch_desc' => $goodsInfo['goods_desc'], 
                'goods_id' => $reqData['goods_id'], 
                'storage_num' => $reqData['storage_type'] == '1' ? $reqData['storage_num'] : - 1, 
                'remain_num' => $reqData['storage_type'] == '1' ? $reqData['storage_num'] : - 1, 
                'print_text' => $goodsInfo['print_text'], 
                'm_id' => $m_id, 
                'validate_type' => $goodsInfo['validate_type']);
            
            if ($a !== false) {
                $goodsData = array_merge($goodsData, 
                    array(
                        'info_title' => $reqData['mms_title'], 
                        'use_rule' => $reqData['mms_info'], 
                        'verify_begin_date' => $reqData['verify_time_type'] ==
                             '0' ? $reqData['verify_begin_date'] . '000000' : $reqData['verify_begin_days'], 
                            'verify_end_date' => $reqData['verify_time_type'] ==
                             '0' ? $reqData['verify_end_date'] . '235959' : $reqData['verify_end_days'], 
                            'verify_begin_type' => $reqData['verify_time_type'], 
                            'verify_end_type' => $reqData['verify_time_type']));
            }
            
            $b_id = M('tbatch_info')->add($goodsData);
            if ($b_id === false)
                throw new Exception("商品信息保存失败！");
            
            if ($goodsInfo['goods_type'] != '6') {
                $reqData['delivery_flag'] = '0';
            }
            
            $goodsDataEx = array(
                'node_id' => $this->nodeId, 
                'b_id' => $b_id, 
                'm_id' => $m_id, 
                'ecshop_classify' => $reqData['classify'], 
                'relation_goods' => $relation_goods, 
                // , 'is_commend' => '9'
                'show_picture1' => $img_arr['img1'], 
                'show_picture2' => $img_arr['img2'], 
                'show_picture3' => $img_arr['img3'], 
                'wap_info' => $reqData['wap_info'], 
                'goods_desc' => $reqData['goods_desc'], 
                'market_price' => $reqData['market_price'], 
                'day_buy_num' => $reqData['day_buy_num_flag'] == '0' ? - 1 : $reqData['day_buy_num'], 
                'person_buy_num' => $reqData['person_buy_num_flag'] == '0' ? - 1 : $reqData['person_buy_num'], 
                'delivery_flag' => $reqData['delivery_flag'], 
                'market_show' => $reqData['market_show'], 
                'relation_goods' => $rgoods_ids, 
                'purchase_time_limit' => $reqData['purchase_time_limit']);
            
            $flag = M('tfb_df_pointshop_goods_ex')->add($goodsDataEx);
            if ($b_id === false)
                throw new Exception("商品信息保存失败！");
                // 记录编辑流水
            $changeData = array(
                'batch_id' => $m_id, 
                'batch_type' => '1002', 
                'name' => $goodsInfo['goods_name'], 
                'start_time' => $reqData['begin_date'], 
                'end_time' => $reqData['end_date'], 
                'memo' => $reqData['goods_desc'], 
                'wap_info' => $reqData['wap_info'], 
                'defined_one_name' => $reqData['delivery_flag'], 
                // 'defined_two_name'=>$showFlag,
                'defined_three_name' => $reqData['person_buy_num_flag'] == '0' ? - 1 : $reqData['person_buy_num'], 
                'market_price' => $reqData['market_price'], 
                'group_price' => $reqData['batch_amt'], 
                'goods_num' => $reqData['storage_type'] == '1' ? $reqData['storage_num'] : - 1, 
                'buy_num' => $reqData['day_buy_num_flag'] == '0' ? 0 : $reqData['day_buy_num'], 
                'verify_begin_date' => $reqData['verify_time_type'] == '0' ? $reqData['verify_begin_date'] .
                     '000000' : $reqData['verify_begin_days'], 
                    'verify_end_date' => $reqData['verify_time_type'] == '0' ? $reqData['verify_end_date'] .
                     '235959' : $reqData['verify_end_days'], 
                    'verify_begin_type' => $reqData['verify_time_type'], 
                    'verify_end_type' => $reqData['verify_time_type'], 
                    'modify_time' => date('YmdHis'), 
                    'modify_type' => '0', 
                    'oper_id' => $this->user_id);
            $flag = M('tfb_df_pointshop_change_trace')->add($changeData);
            if ($flag === false)
                throw new Exception("商品信息编辑记录保存失败！");
            
            $batchChannelData = array(
                'batch_type' => '1002', 
                'batch_id' => $m_id, 
                'channel_id' => $channelInfo['id'], 
                'add_time' => date('YmdHis'), 
                'node_id' => $this->nodeId);
            $label_id = M('tbatch_channel')->add($batchChannelData);
            if ($label_id === false)
                throw new Exception("商品信息保存失败！");
            
            $goodsDataEx = array(
                'label_id' => $label_id);
            $flag = M('tfb_df_pointshop_goods_ex')->where("b_id = '$b_id'")->save(
                $goodsDataEx);
            if ($flag === false)
                throw new Exception("商品渠道信息保存失败！");
        } catch (Exception $e) {
            M()->rollback();
            $this->error($e->getMessage());
        }
        M()->commit();
        node_log('旺财小店商品上架', print_r($reqData, true));
        $this->success('商品上架成功');
    }
    
    // 选择关联商品
    public function SelectRgoods() {
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
                    $v['goods_image'] = $v['goods_image'];
            }
        }
        
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('list', $list); // 赋值分页输出
        $this->assign('CatList', $CatList); // 赋值分页输出
        $this->display('GoodsPutOn/SelectRgoods');
    }

    public function ClassifyAdd() {
        $rules = array(
            'name' => array(
                'null' => false, 
                'maxlen_cn' => '6', 
                'name' => '分类名称'));
        $reqData = $this->_verifyReqData($rules);
        
        $model = M('tfb_df_pointshop_catalog');
        $data = array(
            'node_id' => $this->nodeId, 
            'class_name' => $reqData['name'], 
            'add_time' => date('YmdHis'));
        if (in_array('', $data, true))
            $this->error('必要参数为空！');
        
        $map = array(
            'node_id' => $this->nodeId, 
            'class_name' => $reqData['name']);
        $cnt = $model->where($map)->find();
        if ($cnt > 0)
            $this->error('分类名称重复');
        
        $map = array(
            'node_id' => $this->nodeId);
        $data['sort'] = $model->where($map)->max('sort') + 1;
        
        $id = $model->add($data);
        if ($id === false)
            $this->error('分类添加失败！');
        
        node_log('添加旺财小店分类', print_r($data, true));
        $this->ajaxReturn(array(
            'id' => $id), '分类创建成功！', 1);
    }

    public function _verifyReqData($rules, $return = false, $method = 'post') {
        if (! is_array($rules))
            return;
        $error = '';
        $req_data = array();
        foreach ($rules as $k => $v) {
            $value = I($method . '.' . $k);
            if (! check_str($value, $v, $error)) {
                $msg = $v['name'] . $error;
                if ($return)
                    return $msg;
                else
                    $this->error($msg);
            }
            
            $req_data[$k] = $value;
        }
        return $req_data;
    }
}
