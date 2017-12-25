<?

/**
 * 异业联盟商户操作类
 */
class AdminAction extends BaseAction {

    public function _initialize() {
        parent::_initialize();
        
        $this->assign('book_cks_arr', 
            array(
                '0' => '未审核', 
                '1' => '审核通过', 
                '2' => '审核拒绝'));
    }
    
    // 发布的卡券列表
    public function Goods() {
        $bacthClass = I('batch_class', null, 'mysql_real_escape_string');
        if (isset($bacthClass) && $bacthClass != '') {
            $map['g.goods_type'] = $bacthClass;
        }
        
        $name = I('name', null, 'mysql_real_escape_string');
        if (! empty($name)) {
            $map['b.batch_short_name'] = array(
                'like', 
                "%{$name}%");
        }
        // 处理特殊查询字段
        $baddTime = I('badd_time', null, 'mysql_real_escape_string');
        if (! empty($baddTime)) {
            $map['b.add_time'] = array(
                'egt', 
                $baddTime . '000000');
        }
        $eaddTime = I('eadd_time', null, 'mysql_real_escape_string');
        if (! empty($eaddTime)) {
            $map['b.add_time '] = array(
                'elt', 
                $eaddTime . '235959');
        }
        $status = I('status', null, 'mysql_real_escape_string');
        if (isset($status) && $status != '') {
            $map['b.status'] = $status;
        }
        $nodeId = I('node_id', null, 'mysql_real_escape_string');
        if (! empty($nodeId)) {
            $map['b.node_id '] = $nodeId;
        }
        
        $map['b.node_id'] = array(
            'exp', 
            "in ({$this->nodeIn()})");
        $batchType = I('batch_type', null, 'mysql_real_escape_string');
        
        import("ORG.Util.Page");
        $count = M()->table("thall_goods b")->join(
            'tgoods_info g on g.goods_id = b.goods_id')
            ->where($map)
            ->count();
        $p = new Page($count, 10);
        // 确保分页查询条件
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        $list = M()->table('thall_goods b')
            ->field('b.*,n.node_name,g.goods_type')
            ->join('tnode_info n ON b.node_id=n.node_id')
            ->join('tgoods_info g on g.goods_id = b.goods_id')
            ->where($map)
            ->order('add_time DESC')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        // 分页显示
        $page = $p->show();
        
        $batchClass = array(
            '0' => '优惠劵', 
            '1' => '代金券', 
            '2' => '提领券');
        $status = array(
            '0' => '正常', 
            '1' => '已下架', 
            '2' => '过期');
        $checkStatus = array(
            '0' => '未审核', 
            '1' => '审核通过', 
            '2' => '审核拒绝');
        $this->getNodeTree();
        $this->assign('batchClass', $batchClass);
        $this->assign('status', $status);
        $this->assign('list', $list);
        $this->assign('checkStatus', $checkStatus);
        $this->assign('nodeList', $this->getNodeTree());
        $this->assign('post', $_REQUEST);
        $this->assign("page", $page);
        $this->display();
    }

    /**
     * 卡券预订申请(采购方视角)
     */
    public function MyGoodsBookList() {
        $batch_name = I('batch_name', '', 'trim');
        $check_status = I('check_status', null, 'trim');
        $_string = '';
        if ($batch_name != '')
            $_string = " and b.batch_name like '%$batch_name%'";
        if ($check_status != '') {
            $_string = " and a.check_status = '$check_status'";
        }
        
        $count = M()->table('tnode_goods_book a, thall_goods b ')
            ->where(
            array(
                'a.node_id' => $this->nodeId, 
                '_string' => "a.goods_id = b.id" . $_string))
            ->count();
        
        import('@.ORG.Util.Page'); // 导入分页类
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show();
        
        $queryList = (array) M()->table(
            'tnode_goods_book a, thall_goods b, tnode_info n, tgoods_info g ')
            ->where(
            array(
                'a.node_id' => $this->nodeId, 
                '_string' => "a.goods_id = b.id and a.node_id = n.node_id and b.goods_id = g.goods_id" .
                     $_string))
            ->field(
            "a.*, b.batch_short_name, b.batch_amt, n.node_name as book_node_name,g.remain_num,g.storage_type ")
            ->order('a.add_time desc')
            ->limit($Page->firstRow, $Page->listRows)
            ->select();
        
        $this->assign('queryList', $queryList);
        $this->assign('page', $show);
        
        $this->display();
    }

    /**
     * 卡券预订申请(供货方视角)
     */
    public function GoodsBookList() {
        $batch_name = I('batch_name', '', 'trim');
        $check_status = I('check_status', '', 'trim,intval');
        $_string = '';
        if ($batch_name != '')
            $_string = " and b.batch_name like '%$batch_name%'";
        if ($check_status != '')
            $_string = " and a.check_status = '$check_status'";
        
        $count = M()->table('tnode_goods_book a, thall_goods b ')
            ->where(
            array(
                'b.node_id' => $this->nodeId, 
                '_string' => "a.goods_id = b.id" . $_string))
            ->count();
        
        import('@.ORG.Util.Page'); // 导入分页类
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show();
        
        $queryList = (array) M()->table(
            'tnode_goods_book a, thall_goods b, tnode_info n, tgoods_info g ')
            ->where(
            array(
                'b.node_id' => $this->nodeId, 
                '_string' => "a.goods_id = b.id and a.node_id = n.node_id and b.goods_id = g.goods_id" .
                     $_string))
            ->field(
            "a.*, b.batch_short_name, b.batch_amt, n.node_name as book_node_name,g.remain_num,g.storage_type ")
            ->order('a.add_time desc')
            ->limit($Page->firstRow, $Page->listRows)
            ->select();
        
        $this->assign('queryList', $queryList);
        $this->assign('page', $show);
        
        $this->display();
    }

    /**
     * 卡券预订申请查看（采购商视角）
     */
    public function GoodsBookView() {
        $book_id = I('id', '', 'intval');
        $bookInfo = M('tnode_goods_book')->find($book_id);
        
        if (! $bookInfo)
            $this->error('参数错误！');
        
        $batchInfo = M('thall_goods')->find($bookInfo['goods_id']);
        if (! $batchInfo)
            $this->error('参数错误！');
        
        $goodsInfo = M('tgoods_info')->where(
            "goods_id = '{$batchInfo['goods_id']}'")->find();
        
        $this->assign('bookInfo', $bookInfo);
        $this->assign('batchInfo', $batchInfo);
        $this->assign('goodsInfo', $goodsInfo);
        $this->display();
    }

    /**
     * 卡券预订申请审核(供货方视角)
     */
    public function GoodsBookCheck() {
        $book_id = I('id', '', 'intval');
        $bookInfo = M('tnode_goods_book')->find($book_id);
        
        if (! $bookInfo)
            $this->error('参数错误！');
        
        $batchInfo = M('thall_goods')->find($bookInfo['goods_id']);
        if (! $batchInfo)
            $this->error('参数错误！');
        
        if ($batchInfo['node_id'] != $this->nodeId)
            $this->error('error!');
        
        $goodsInfo = M('tgoods_info')->where(
            "goods_id = '{$batchInfo['goods_id']}'")->find();
        
        /**
         * 1. 变更预订记录的审核状态 预订审核(如果审核通过) 2. 在采购方机构下：创建商品（电子合约号是由供货商发布给采购方的） 3.
         * 扣减供货商的商品库存
         */
        if ($this->isPost()) {
            $result = $this->_GoodsBookCheck($book_id, 
                array(
                    'autoCheck' => false, 
                    'bookInfo' => $bookInfo, 
                    'batchInfo' => $batchInfo, 
                    'goodsInfo' => $goodsInfo, 
                    'check_status' => I('check_status', null, 'trim,intval'), 
                    'check_memo' => I('check_memo', null, 'trim')));
            
            $this->success($result === true ? '卡券预订申请审核成功！' : $result);
            exit();
        }
        
        $this->assign('bookInfo', $bookInfo);
        $this->assign('batchInfo', $batchInfo);
        $this->assign('goodsInfo', $goodsInfo);
        $this->display();
    }
    
    // 商品预订审核，可供其他模块调用，成功返回true，错误返回错误原因
    public function _GoodsBookCheck($book_id, $params = array()) {
        $autoCheck = $params['autoCheck'];
        $bookInfo = $params['bookInfo'];
        $batchInfo = $params['batchInfo'];
        $goodsInfo = $params['goodsInfo'];
        $check_status = $params['check_status'];
        $check_memo = $params['check_memo'];
        
        if ($bookInfo == null) {
            $bookInfo = M('tnode_goods_book')->find($book_id);
            
            if (! $bookInfo)
                return '参数错误！';
        }
        
        if ($batchInfo == null) {
            $batchInfo = M('thall_goods')->find($bookInfo['goods_id']);
            if (! $batchInfo)
                return '参数错误！';
        }
        
        if (! $autoCheck) {
            if ($batchInfo['node_id'] != $this->nodeId)
                return 'error!';
        }
        
        if ($goodsInfo == null) {
            $goodsInfo = M('tgoods_info')->where(
                "goods_id = '{$batchInfo['goods_id']}'")->find();
        }
        /**
         * 1. 变更预订记录的审核状态 预订审核(如果审核通过) 2. 在采购方机构下：创建商品（电子合约号是由供货商发布给采购方的） 3.
         * 扣减供货商的商品库存
         */
        if ($bookInfo['check_status'] != '0')
            return '该申请已经审核通过，请刷新页面查看详情';
        
        M()->startTrans();
        $goodsInfo = M('tgoods_info')->where(
            "goods_id = '{$batchInfo['goods_id']}'")
            ->lock(true)
            ->find();
        
        if ($goodsInfo['storage_type'] == 1 &&
             $goodsInfo['remain_num'] < $bookInfo['book_num'])
            return '库存不足！无法审核通过！';
        
        $bookData = array(
            'check_time' => date('YmdHis'), 
            'check_status' => $check_status, 
            'check_memo' => $check_memo);
        
        $flag = M('tnode_goods_book')->where("id = '{$book_id}'")->save(
            $bookData);
        if ($flag === false) {
            M()->rollback();
            return '审核失败[01]！';
        }
        
        // 审核通过
        if ($check_status == '1') {
            // ==========创建支撑活动========开始
            try {
                $result = $this->_createZctpBatch($goodsInfo, 
                    $batchInfo['batch_img'], $bookInfo['node_id']);
            } catch (Exception $e) {
                M()->rollback();
                return $e->getMessage();
            }
            
            $p_goods_id = $result['p_goods_id'];
            $batch_no = $result['batch_no'];
            // ==========创建支撑活动========结束
            
            $goodsId = $this->_getGoodsId();
            // 创建商品
            $goods_field = 'goods_name, goods_desc, user_id, pos_id, pos_group, pos_group_type, goods_type, market_price, goods_amt, goods_discount, customer_no, mms_title, mms_text, sms_text, print_text, validate_times, begin_time, end_time, send_begin_date, send_end_date, verify_begin_date, verify_end_date, verify_begin_type, verify_end_type, add_time, update_time, STATUS, join_rule, node_service_hotline, goods_cat';
            $sql = "insert into tgoods_info (goods_id, node_id, {$goods_field}, storage_type, storage_num, remain_num, source, purchase_goods_id, purchase_type, purchase_relation_id, purchase_batch_id, goods_image, p_goods_id, batch_no)
					select '{$goodsId}', '{$bookInfo['node_id']}', {$goods_field}, 1, {$bookInfo['book_num']}, {$bookInfo['book_num']}, '1', '{$goodsInfo['goods_id']}', '0','{$book_id}', '{$batchInfo['id']}', '{$batchInfo['batch_img']}', '{$p_goods_id}', '{$batch_no}'
					from tgoods_info where id = '{$goodsInfo['id']}'";
            
            $flag = M()->execute($sql);
            if ($flag === false) {
                M()->rollback();
                return '审核失败[02]！';
            }
            
            if ($goodsInfo['storage_type'] == 1) {
                // 变更库存
                $flag = M('tgoods_info')->where("id='{$goodsInfo['id']}'")->save(
                    array(
                        'remain_num' => $goodsInfo['remain_num'] -
                             $bookInfo['book_num']));
                
                // 记录变更流水
                $data = array(
                    'node_id' => $this->node_id, 
                    'goods_id' => $goodsInfo['id'], 
                    'change_num' => $bookInfo['book_num'], 
                    'pre_num' => $goodsInfo['remain_num'], 
                    'current_num' => $goodsInfo['remain_num'] -
                         $bookInfo['book_num'], 
                        'opt_type' => '4', 
                        'relation_id' => $book_id, 
                        'add_time' => date('YmdHis'));
                $flag = M('tgoods_storage_trace')->add($data);
                if ($flag === false) {
                    M()->rollback();
                    return '审核失败[03]！';
                }
                
                if ($flag === false) {
                    M()->rollback();
                    return '审核失败[04]！';
                }
            }
        }
        
        M()->commit();
        node_log('卡券预订申请审核成功！');
        return true;
    }

    /**
     * 采购需求供货列表(采购方视角)
     */
    public function GoodsSupplyList() {
        $check_status = I('check_status', null, 'trim');
        $_string = '';
        if ($check_status != '') {
            $_string = " and a.check_status = '$check_status'";
        }
        $count = M()->table(
            'tnode_demand_reply a, tnode_demand b, tgoods_info c ')
            ->where(
            array(
                '_string' => "a.demand_id = b.id and a.goods_id = c.id and b.node_id = '{$this->nodeId}'" .
                     $_string))
            ->count();
        
        import('@.ORG.Util.Page'); // 导入分页类
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show();
        
        $queryList = (array) M()->table(
            'tnode_demand_reply a, tnode_demand b, tgoods_info c, tnode_info d ')
            ->where(
            array(
                '_string' => "a.demand_id = b.id and a.goods_id = c.id and b.node_id = '{$this->nodeId}' and a.reply_node_id = d.node_id" .
                     $_string))
            ->field(
            "b.demand_desc, b.amount, a.demand_id, b.num, a.check_status, a.add_time supply_time, a.id reply_id, d.node_short_name as reply_node_name ")
            ->limit($Page->firstRow, $Page->listRows)
            ->select();
        
        $this->assign('queryList', $queryList);
        $this->assign('page', $show);
        
        $this->display();
    }

    /**
     * 采购需求供货列表(采购方视角)
     */
    public function MyGoodsSupplyList() {
        $check_status = I('check_status', null, 'trim');
        $_string = '';
        if ($check_status != '') {
            $_string = " and a.check_status = '$check_status'";
        }
        $count = M()->table(
            'tnode_demand_reply a, tnode_demand b, tgoods_info c ')
            ->where(
            array(
                '_string' => "a.demand_id = b.id and a.goods_id = c.id and a.reply_node_id = '{$this->nodeId}'" .
                     $_string))
            ->count();
        
        import('@.ORG.Util.Page'); // 导入分页类
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show();
        
        $queryList = (array) M()->table(
            'tnode_demand_reply a, tnode_demand b, tgoods_info c, tnode_info d ')
            ->where(
            array(
                '_string' => "a.demand_id = b.id and a.goods_id = c.id and a.reply_node_id = '{$this->nodeId}' and a.reply_node_id = d.node_id" .
                     $_string))
            ->field(
            "b.demand_desc, b.amount, a.demand_id, b.num, a.check_status, a.add_time supply_time, a.id reply_id, d.node_short_name as reply_node_name ")
            ->limit($Page->firstRow, $Page->listRows)
            ->select();
        
        $this->assign('queryList', $queryList);
        $this->assign('page', $show);
        
        $this->display();
    }

    /**
     * 采购需求供货审核(采购方视角)
     */
    public function GoodsSupplyCheck() {
        $reply_id = I('id', '', 'intval');
        $demandInfo = M()->table('tnode_demand a, tnode_demand_reply b')
            ->where(
            "a.id = b.demand_id and b.id = '{$reply_id}' and a.node_id = '{$this->nodeId}'")
            ->field("a.*")
            ->find();
        
        if (! $demandInfo)
            $this->error('参数错误！');

        $replyInfo =  M()->table("tnode_demand_reply a")->find($reply_id);
        $goodsInfo = M('tgoods_info')->find($replyInfo['goods_id']);
        $replyNode = M('tnode_info')->where(
            "node_id = '{$replyInfo['reply_node_id']}'")->find();
        
        if ($this->isPost()) {
            if ($replyInfo['check_status'] != '0')
                $this->error('该申请已经审核通过，请刷新页面查看详情');
            
            $check_status = I('check_status', null, 'trim,intval');
            $check_memo = I('check_memo', null, 'trim');
            
            if ($goodsInfo['storage_type'] == 1 &&
                 $goodsInfo['remain_num'] < $demandInfo['num'])
                $this->error('库存不足！无法审核通过！');
            
            M()->startTrans();
            $replyData = array(
                'check_time' => date('YmdHis'), 
                'check_user' => $this->userId, 
                'check_status' => $check_status, 
                'check_memo' => $check_memo);
            
            $flag = M('tnode_demand_reply')->where("id = '{$reply_id}'")->save(
                $replyData);
            if ($flag === false) {
                M()->rollback();
                $this->error('审核失败[01]！');
            }
            
            // 审核通过
            if ($check_status == '1') {
                // ==========创建支撑活动========开始
                try {
                    $result = $this->_createZctpBatch($goodsInfo, 
                        $goodsInfo['goods_image'], $demandInfo['node_id']);
                } catch (Exception $e) {
                    M()->rollback();
                    $this->error($e->getMessage());
                }
                
                $p_goods_id = $result['p_goods_id'];
                $batch_no = $result['batch_no'];
                // ==========创建支撑活动========结束
                
                $goodsId = $this->_getGoodsId();
                // 创建商品
                /*
                 * $goods_field = 'goods_name, goods_desc, user_id, pos_id,
                 * pos_group, pos_group_type, goods_type, market_price,
                 * goods_amt, goods_discount, customer_no, mms_title, mms_text,
                 * sms_text, print_text, validate_times, begin_time, end_time,
                 * send_begin_date, send_end_date, verify_begin_date,
                 * verify_end_date, verify_begin_type, verify_end_type,
                 * add_time, update_time, STATUS, join_rule,
                 * node_service_hotline, goods_cat, bloc_name'; $sql = "insert
                 * into tgoods_info (goods_id, node_id, {$goods_field},
                 * storage_type, storage_num, remain_num, source,
                 * purchase_goods_id, purchase_type, purchase_relation_id)
                 * select '{$goodsId}', '{$demandInfo['node_id']}',
                 * {$goods_field}, 1, {$demandInfo['num']},
                 * {$demandInfo['num']}, '1','{$goodsInfo['goods_id']}', '1',
                 * '{$reply_id}' from tgoods_info where id =
                 * '{$goodsInfo['id']}'";
                 */
                
                // 创建商品
                $goods_field = 'goods_name, goods_desc, user_id, pos_id, pos_group, pos_group_type, goods_type, market_price, goods_amt, goods_discount, customer_no, mms_title, mms_text, sms_text, print_text, validate_times, begin_time, end_time, send_begin_date, send_end_date, verify_begin_date, verify_end_date, verify_begin_type, verify_end_type, add_time, update_time, STATUS, join_rule, node_service_hotline, goods_cat';
                $sql = "insert into tgoods_info (goods_id, node_id, {$goods_field}, storage_type, storage_num, remain_num, source, purchase_goods_id, purchase_type, purchase_relation_id, goods_image, p_goods_id, batch_no)
						select '{$goodsId}', '{$demandInfo['node_id']}', {$goods_field}, 1, {$demandInfo['num']}, {$demandInfo['num']}, '1', '{$goodsInfo['goods_id']}', '1','{$reply_id}', '{$goodsInfo['goods_image']}', '{$p_goods_id}', '{$batch_no}'
						from tgoods_info where id = '{$goodsInfo['id']}'";
                
                $flag = M()->execute($sql);
                if ($flag === false) {
                    M()->rollback();
                    $this->error('审核失败[02]！' . $sql);
                }
                
                if ($goodsInfo['storage_type'] == 1) {
                    // 变更库存
                    $flag = M('tgoods_info')->where("id='{$goodsInfo['id']}'")->save(
                        array(
                            'remain_num' => $goodsInfo['remain_num'] -
                                 $demandInfo['num']));
                    
                    // 记录变更流水
                    $data = array(
                        'node_id' => $replyInfo['reply_node_id'], 
                        'goods_id' => $goodsInfo['id'], 
                        'change_num' => $demandInfo['num'], 
                        'pre_num' => $goodsInfo['remain_num'], 
                        'current_num' => $goodsInfo['remain_num'] -
                             $demandInfo['num'], 
                            'opt_type' => '5', 
                            'relation_id' => $reply_id, 
                            'add_time' => date('YmdHis'));
                    $flag = M('tgoods_storage_trace')->add($data);
                    if ($flag === false) {
                        M()->rollback();
                        $this->error('审核失败[03]！');
                    }
                    
                    if ($flag === false) {
                        M()->rollback();
                        $this->error('审核失败[04]！');
                    }
                }
            }
            
            M()->commit();
            node_log('供货意向审核成功！');
            $this->success('供货意向审核成功！');
            
            exit();
        }
        
        $this->assign('demandInfo', $demandInfo);
        $this->assign('replyInfo', $replyInfo);
        $this->assign('goodsInfo', $goodsInfo);
        $this->assign('replyNode', $replyNode);
        $this->display();
    }

    /**
     * 采购需求供货查看(供货方视角)
     */
    public function GoodsSupplyView() {
        $reply_id = I('id', '', 'intval');
        $demandInfo = M()->table('tnode_demand a, tnode_demand_reply b')
            ->where(
            "a.id = b.demand_id and b.id = '{$reply_id}' and b.reply_node_id = '{$this->nodeId}'")
            ->field("a.*")
            ->find();
        
        if (! $demandInfo)
            $this->error('参数错误！');

        $replyInfo = M()->table("tnode_demand_reply a")->find($reply_id);
        $goodsInfo = M('tgoods_info')->find($replyInfo['goods_id']);
        $replyNode = M('tnode_info')->where(
            "node_id = '{$replyInfo['reply_node_id']}'")->find();
        
        $this->assign('demandInfo', $demandInfo);
        $this->assign('replyInfo', $replyInfo);
        $this->assign('goodsInfo', $goodsInfo);
        $this->assign('replyNode', $replyNode);
        $this->display();
    }

    /**
     * 采购需求供货单（采购方视角）
     */
    public function DemandRplyList() {
        M()->table('tnode_demand_reply a, tnode_demand b')
            ->where("a.demand_id = b.id")
            ->field("b.demand_desc, b.id demand_id, ");
    }
    
    // goods_id生成
    public function _getGoodsId() {
        $data = M()->query("SELECT _nextval('goods_id') as goods_id FROM DUAL");
        if (! $data)
            $this->error('商品id生成失败');
        $goodsId = $data[0]['goods_id'];
        return 'gw' . str_pad($goodsId, 10, '0', STR_PAD_LEFT);
    }
    
    // 旺财联盟信息列表(别的商户与申请加入盟主的列表)
    public function blocList() {
        $map = array(
            'b.node_id' => array(
                'exp', 
                "in ({$this->nodeIn()})"), 
            'b.goods_type' => '1', 
            'b.source' => '2');
        $name = I('name', null, 'mysql_real_escape_string');
        if (! empty($name)) {
            $map['b.goods_name'] = array(
                'like', 
                "%{$name}%");
        }
        // 处理特殊查询字段
        $baddTime = I('badd_time', null, 'mysql_real_escape_string');
        if (! empty($baddTime)) {
            $map['b.add_time'] = array(
                'egt', 
                $baddTime . '000000');
        }
        $eaddTime = I('eadd_time', null, 'mysql_real_escape_string');
        if (! empty($eaddTime)) {
            $map['b.add_time '] = array(
                'elt', 
                $eaddTime . '235959');
        }
        $checkStatus = I('status', null, 'mysql_real_escape_string');
        if (isset($checkStatus) && $checkStatus != '') {
            $map['b.check_status'] = $checkStatus;
        }
        $nodeId = I('node_id', null, 'mysql_real_escape_string');
        if (! empty($nodeId)) {
            $map['b.node_id '] = $nodeId;
        }
        import("ORG.Util.Page");
        $count = M()->table("tgoods_info b") ->where($map)->count();
        $p = new Page($count, 10);
        // 确保分页查询条件
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        $list = M()->table('tgoods_info b')
            ->field(
            'b.*,(SELECT COUNT(*) FROM tbatch_relation r WHERE r.goods_id=b.goods_id AND r.node_id=b.node_id AND r.status=0 AND join_type in(0,2)) as join_num')
            ->where($map)
            ->order('b.add_time desc,join_num desc')
            ->select();
        // 分页显示
        $page = $p->show();
        
        $checkStatus = array(
            '0' => '正在审核', 
            '1' => '审核通过', 
            '2' => '审核拒绝');
        node_log("首页+异业联盟+旺财联盟列表");
        $this->assign('checkStatus', $checkStatus);
        $this->assign('list', $list);
        $this->assign('nodeList', $this->getNodeTree());
        $this->assign('post', $_REQUEST);
        $this->assign("page", $page);
        $this->display();
    }
    
    // 旺财联盟编辑
    public function blocEdit() {
        $goodsId = I('goods_id', 'mysql_real_escape_string');
        $map = array(
            'goods_id' => $goodsId, 
            'source' => '2', 
            'goods_type' => '1', 
            'node_id' => $this->nodeId);
        $goodsData = M('tgoods_info')->where($map)->find();
        if (! $goodsData)
            $this->error('未找到该旺财联盟信息');
        if ($this->isPost()) {
            // 卡券有效期(只能延长)
            $goodsEndDate = I('post.goods_end_date');
            if (! check_str($goodsEndDate, 
                array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd'), $error)) {
                $this->error("联盟合作有效期结束日期{$error}");
            } elseif ($goodsEndDate < date('Ymd')) {
                $this->error('联盟合作有效期结束日期不能小于当前日期');
            } elseif (strtotime($goodsEndDate) <
                 strtotime($goodsData['begin_time'])) {
                $this->error('联盟合作有效期开始日期不能大于联盟合作有效期结束日期');
            } elseif (strtotime($goodsEndDate . '235959') <
                 strtotime($goodsData['end_time'])) {
                $this->error('联盟合作有效期结束时间不能小于修改之前的结束时间');
            }
            $recruitEndDate = I('post.recruit_end_date');
            if (! check_str($recruitEndDate, 
                array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd'), $error)) {
                $this->error("招募截止日期{$error}");
            }
            if ($recruitEndDate > $goodsEndDate) {
                $this->error('招募截止日期要小于联盟合作有效期结束日期');
            }
            $goodImage = I('post.img_resp');
            if (! check_str($goodImage, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '100'), $error)) {
                $this->error("请上传卡券图片");
            }
            // 使用时间
            $verifyTimeType = I('post.verify_time_type');
            switch ($verifyTimeType) {
                case 0:
                    $verifyBeginDate = I('post.verify_begin_date');
                    if (! check_str($verifyBeginDate, 
                        array(
                            'null' => false, 
                            'strtype' => 'datetime', 
                            'format' => 'Ymd'), $error)) {
                        $this->error("使用开始时间日期{$error}");
                    }
                    $verifyEndDate = I('post.verify_end_date');
                    if (! check_str($verifyEndDate, 
                        array(
                            'null' => false, 
                            'strtype' => 'datetime', 
                            'format' => 'Ymd'), $error)) {
                        $this->error("使用结束时间日期{$error}");
                    }
                    if (strtotime($verifyEndDate) < strtotime($verifyBeginDate)) {
                        $this->error('使用开始时间日期不能大于使用结束时间日期');
                    }
                    $verifyBeginDate .= '000000';
                    $verifyEndDate .= '235959';
                    break;
                case 1:
                    $verifyBeginDate = I('post.verify_begin_days');
                    if (! check_str($verifyBeginDate, 
                        array(
                            'null' => false, 
                            'strtype' => 'int'), $error)) {
                        $this->error("使用开始天数{$error}");
                    }
                    $verifyEndDate = I('post.verify_end_days');
                    if (! check_str($verifyEndDate, 
                        array(
                            'null' => false, 
                            'strtype' => 'int'), $error)) {
                        $this->error("使用结束天数{$error}");
                    }
                    if ($verifyEndDate < $verifyBeginDate) {
                        $this->error('使用开始天数不能大于使用结束天数');
                    }
                    break;
                default:
                    $this->error('未知的使用时间类型');
            }
            // 图片移动
            if (basename($goodsData['goods_image']) != $goodImage) {
                R('WangcaiPc/NumGoods/moveImage', 
                    array(
                        $goodImage));
            }
            $data = array(
                'goods_image' => 'NumGoods/' . $this->nodeId . '/' . $goodImage, 
                'begin_time' => $goodsData['begin_time'], 
                'end_time' => $goodsEndDate . '235959', 
                'status' => '0', 
                'verify_begin_date' => $verifyBeginDate, 
                'verify_end_date' => $verifyEndDate, 
                'verify_begin_type' => $verifyTimeType, 
                'verify_end_type' => $verifyTimeType, 
                'recruit_end_date' => $recruitEndDate . '235959', 
                'check_status' => '0');
            $result = M('tgoods_info')->where("goods_id='{$goodsId}'")->save(
                $data);
            if ($result === false) {
                $this->error('数据出错,更新失败');
            }
            node_log("旺财联盟卡券编辑，名称：" . $goodsData['goods_name']);
            $this->success('更新成功');
            exit();
        }
        $this->assign('goodsData', $goodsData);
        $this->display();
    }
    
    // 旺财联盟详情
    public function blocDetail() {
        $goodsId = I('goods_id', 'mysql_real_escape_string');
        $map = array(
            'goods_id' => $goodsId, 
            'goods_type' => '1', 
            'source' => '2', 
            'node_id' => $this->nodeId);
        $list = M()->table('tgoods_info')
            ->where($map)
            ->find();
        if (! $list)
            $this->error('未找到该联盟信息');
            // 查找城市
        $citylist = M()->table('tcity_code t')
            ->field('GROUP_CONCAT(t.city) as city')
            ->where(
            "t.city_level='2' and t.city_code in(SELECT city_code FROM tgoods_bloc_city WHERE goods_id='{$list['goods_id']}')")
            ->find();
        $list['city_str'] = $citylist['city'];
        // 时间计算
        $now = date('Y-m-d');
        $end_time = $list['recruit_end_date'];
        $day = floor((strtotime($end_time) - strtotime($now)) / 86400);
        if ($day < 0) {
            $day = "已结束";
        } else {
            $day = $day . "天";
        }
        $list['day'] = $day;
        
        import("ORG.Util.Page");
        // 申请列表
        $joinCount = M('tbatch_relation')->where(
            "goods_id='{$goodsId}' AND node_id='{$this->nodeId}' AND join_type=0")->count();
        $joinP = new Page($joinCount, 10);
        // 获得该卡券的加盟商户信息
        $joinList = M()->table('tbatch_relation r')
            ->field('r.*,n.node_name')
            ->join("tnode_info n ON r.req_node_id=n.node_id")
            ->where(
            "r.goods_id='{$goodsId}' AND r.node_id='{$this->nodeId}' AND r.join_type=0")
            ->order('add_time DESC,status')
            ->limit($joinP->firstRow . ',' . $joinP->listRows)
            ->select();
        // 邀请列表
        C('VAR_PAGE', 'yp');
        $yCount = M('tbatch_relation')->where(
            "goods_id='{$goodsId}' AND node_id='{$this->nodeId}' AND join_type=2")->count();
        $yp = new Page($yCount, 10);
        // 获得该卡券的加盟商户信息
        $yList = M()->table('tbatch_relation r')
            ->field('r.*,n.node_name')
            ->join("tnode_info n ON r.req_node_id=n.node_id")
            ->where(
            "r.goods_id='{$goodsId}' AND r.node_id='{$this->nodeId}' AND r.join_type=2")
            ->order('add_time DESC,status')
            ->limit($yp->firstRow . ',' . $yp->listRows)
            ->select();
        // 分页显示
        $joinPage = $joinP->show();
        $yPage = $yp->show();
        $this->assign('list', $list);
        $this->assign('joinList', $joinList);
        $this->assign('yList', $yList);
        $this->assign('joinPage', $joinPage);
        $this->assign('yPage', $yPage);
        $this->display();
    }
    
    // 获取门店信息
    public function getStoreList() {
        $id = I('id', null, 'mysql_real_escape_string');
        $where = array(
            'id' => $id, 
            'node_id' => $this->nodeId, 
            'join_type' => array(
                'in', 
                '0,2'));
        $storeStr = M('tbatch_relation')->where($where)->getField('store_list');
        if (! $storeStr)
            $this->error('未找到门店信息');
        $where = array(
            'store_id' => array(
                'in', 
                $storeStr));
        $storeList = M('tstore_info')->field(
            'store_name,province_code,city_code,town_code')
            ->where($where)
            ->select();
        foreach ($storeList as $k => $v) {
            $storeList[$k]['address'] = D('CityCode')->getAreaText(
                $v['province_code'] . $v['city_code'] . $v['town_code']);
        }
        $this->ajaxReturn($storeList, '', 1);
    }
    // 同意加入联盟
    public function agreeJoin() {
        $id = I('id', null, 'mysql_real_escape_string');
        $relationInfo = M('tbatch_relation')->where(
            "id='{$id}' AND node_id='{$this->nodeId}' AND join_type IN(0,2)")->find();
        if (! $relationInfo)
            $this->error('未找到有效数据');
        if ($relationInfo['status'] == '1') {
            $this->error('您已经拒绝了该申请');
        } elseif ($relationInfo['status'] == '2') {
            $this->error('该申请已经加入到联盟中了');
        }
        // 创建活动
        $goodsInfo = M('tgoods_info')->where(
            "goods_id='{$relationInfo['goods_id']}'AND node_id='{$this->nodeId}'")->find();
        if ($goodsInfo['status'] != '0') {
            $this->error('该联盟活动已过期或已停用');
        } elseif ($goodsInfo['check_status'] != '1') {
            $this->error('该联盟活动未审核通过');
        }
        // 支撑创建终端组(终端组和合约都是加盟商的)
        M('tnode_info')->where("node_id='{$relationInfo['req_node_id']}'")->setInc(
            'posgroup_seq'); // posgroup_seq
                             // +1;
        $nodeInfo = M('tnode_info')->where(
            "node_id={$relationInfo['req_node_id']}")->find();
        $req_array = array(
            'CreatePosGroupReq' => array(
                'NodeId' => $relationInfo['req_node_id'], 
                'GroupType' => '1', 
                'GroupName' => str_pad($nodeInfo['client_id'], 6, '0', 
                    STR_PAD_LEFT) . $nodeInfo['posgroup_seq'], 
                'GroupDesc' => '', 
                'DataList' => $relationInfo['pos_list']));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $resp_array = $RemoteRequest->requestIssForImageco($req_array);
        $ret_msg = $resp_array['CreatePosGroupRes']['Status'];
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
             $ret_msg['StatusCode'] != '0001')) {
            M()->rollback();
            log::write("创建终端组失败，原因：{$ret_msg['StatusText']}");
            $this->error('创建门店失败:' . $ret_msg['StatusText']);
        }
        $groupId = $resp_array['CreatePosGroupRes']['GroupID'];
        // 创建合约号
        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 流水号
        $req_array = array(
            'CreateTreatyReq' => array(
                'SystemID' => C('ISS_SYSTEM_ID'), 
                'RequestSeq' => $TransactionID, 
                'ShopNodeId' => $relationInfo['req_node_id'],  // 邀请加入
                'BussNodeId' => $relationInfo['node_id'],  // 盟主
                'TreatyName' => $goodsInfo['goods_name'], 
                'TreatyShortName' => $goodsInfo['goods_name'], 
                'StartTime' => date('YmdHis'), 
                'EndTime' => '20301231235959', 
                'GroupId' => $groupId, 
                'GoodsName' => $goodsInfo['goods_name'], 
                'GoodsShortName' => $goodsInfo['goods_name'], 
                'SalePrice' => empty($goodsInfo['goods_amt']) ? 0 : $goodsInfo['goods_amt']));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $resp_array = $RemoteRequest->requestIssForImageco($req_array);
        $ret_msg = $resp_array['CreateTreatyRes']['Status'];
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
             $ret_msg['StatusCode'] != '0001')) {
            M()->rollback();
            log::write("创建合约失败，原因：{$ret_msg['StatusText']}");
            $this->error('创建合约失败:' . $ret_msg['StatusText']);
        }
        $treatyId = $resp_array['CreateTreatyRes']['TreatyId']; // 合约id
        if (empty($goodsInfo['batch_no'])) { // 创建活动
            $smilId = '';
            if (! empty($goodsInfo['goods_image'])) {
                $smilId = R('WangcaiPc/NumGoods/getSmil', 
                    array(
                        $goodsInfo['goods_image'], 
                        $goodsInfo['goods_name'], 
                        ''));
                if (! $smilId)
                    $this->error('创建失败:smilid获取失败');
            }
            // 创建活动
            $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
                                                                       // 请求参数
            $req_array = array(
                'ActivityCreateReq' => array(
                    'SystemID' => C('ISS_SYSTEM_ID'), 
                    'ISSPID' => $this->nodeId, 
                    'RelationID' => $relationInfo['req_node_id'], 
                    'TransactionID' => $TransactionID, 
                    'SmilID' => $smilId, 
                    'ActivityInfo' => array(
                        'CustomNo' => '', 
                        'ActivityName' => $goodsInfo['goods_name'], 
                        'ActivityShortName' => $goodsInfo['goods_name'], 
                        'BeginTime' => $goodsInfo['add_time'], 
                        'EndTime' => '20301231235959', 
                        'UseRangeID' => $groupId), 
                    'VerifyMode' => array(
                        'UseTimesLimit' => $goodsInfo['validate_type'] == 1 ? 0 : 1, 
                        'UseAmtLimit' => $goodsInfo['validate_type'] == 1 ? 1 : 0), 
                    'GoodsInfo' => array(
                        'pGoodsId' => $treatyId), 
                    'DefaultParam' => array(
                        'PasswordTryTimes' => 3, 
                        'PasswordType' => '', 
                        'PrintText' => $goodsInfo['print_text'])));
            $RemoteRequest = D('RemoteRequest', 'Service');
            $resp_array = $RemoteRequest->requestIssForImageco($req_array);
            $ret_msg = $resp_array['ActivityCreateRes']['Status'];
            if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
                 $ret_msg['StatusCode'] != '0001')) {
                $this->error("活动创建失败:{$ret_msg['StatusText']}");
            }
            $batchNo = $resp_array['ActivityCreateRes']['Info']['ActivityID'];
            // $treatyId = $resp_array['ActivityCreateRes']['Info']['pGoodsId'];
        } else { // 加入合约
            $req_array = array(
                'ModifyMutiTreatyReq' => array(
                    'SystemID' => C('ISS_SYSTEM_ID'), 
                    'RequestSeq' => $TransactionID, 
                    'ActivityId' => $goodsInfo['batch_no'], 
                    'TreatyIdList' => $treatyId, 
                    'OptType' => 1));
            $RemoteRequest = D('RemoteRequest', 'Service');
            $resp_array = $RemoteRequest->requestIssForImageco($req_array);
            $ret_msg = $resp_array['ModifyMutiTreatyRes']['Status'];
            if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
                 $ret_msg['StatusCode'] != '0001')) {
                $this->error("合约添加失败:{$ret_msg['StatusText']}");
            }
        }
        // 更新终端组号,合约号等信息
        $saveData = array(
            'status' => '2', 
            'posgroup_id' => $groupId, 
            'pgoods_id' => $treatyId, 
            'update_time' => date('YmdHis'));
        M()->startTrans();
        $result = M('tbatch_relation')->where(
            array(
                'id' => $id))->save($saveData);
        if ($result === false) {
            M()->rollback();
            $this->error('系统出错更新失败');
        }
        if (empty($goodsInfo['batch_no'])) {
            $saveData = array(
                'batch_no' => $batchNo, 
                'update_time' => date('YmdHis'));
            $result = M('tgoods_info')->where(
                "goods_id='{$relationInfo['goods_id']}'AND node_id='{$this->nodeId}'")->save(
                $saveData);
            if ($result === false) {
                M()->rollback();
                $this->error('系统出错更新失败');
            }
        }
        M()->commit();
        $this->success('数据创建成功');
    }
    
    // 拒绝加入联盟
    public function refusalJoin() {
        $id = I('id', null, 'mysql_real_escape_string');
        $relationInfo = M('tbatch_relation')->where(
            "id='{$id}' AND node_id='{$this->nodeId}' AND join_type IN(0,2)")->find();
        if (! $relationInfo)
            $this->error('未找到有效数据');
        if ($relationInfo['status'] == '1') {
            $this->error('您已经拒绝了该申请');
        } elseif ($relationInfo['status'] == '2') {
            $this->error('该申请已经加入到联盟中了');
        }
        $data = array(
            'status' => '1');
        $result = M('tbatch_relation')->where("id='{$id}")->save($data);
        if ($result === false)
            $this->error('数据出错更新失败');
        $this->success('数据更新成功');
    }
    
    // 盟主申请加入别的联盟活动的列表
    public function joinOtherBloc() {
        $map = array(
            'r.req_node_id' => array(
                'exp', 
                "in ({$this->nodeIn()})"), 
            'r.join_type' => '0', 
            'g.goods_type' => '1', 
            'g.source' => '2');
        $name = I('name', null, 'mysql_real_escape_string');
        if (! empty($name)) {
            $map['g.goods_name'] = array(
                'like', 
                "%{$name}%");
        }
        // 处理特殊查询字段
        $baddTime = I('badd_time', null, 'mysql_real_escape_string');
        if (! empty($baddTime)) {
            $map['r.add_time'] = array(
                'egt', 
                $baddTime . '000000');
        }
        $eaddTime = I('eadd_time', null, 'mysql_real_escape_string');
        if (! empty($eaddTime)) {
            $map['r.add_time '] = array(
                'elt', 
                $eaddTime . '235959');
        }
        $checkStatus = I('status', null, 'mysql_real_escape_string');
        if (isset($checkStatus) && $checkStatus != '') {
            $map['r.status'] = $checkStatus;
        }
        $nodeId = I('node_id', null, 'mysql_real_escape_string');
        if (! empty($nodeId)) {
            $map['r.node_id '] = $nodeId;
        }
        import("ORG.Util.Page");
        $count = M()->table("tbatch_relation r") ->where($map)->count();
        $p = new Page($count, 10);
        // 确保分页查询条件
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        $list = M()->table('tbatch_relation r')
            ->field(
            'r.id,r.add_time,r.status,g.bloc_name,g.goods_name,g.recruit_end_date,g.begin_time,g.end_time,n.node_name')
            ->join('tgoods_info g ON g.goods_id=r.goods_id')
            ->join('tnode_info n ON n.node_id=r.node_id')
            ->where($map)
            ->order('r.add_time DESC')
            ->select();
        
        // 分页显示
        $page = $p->show();
        $checkStatus = array(
            '0' => '正在审核', 
            '1' => '拒绝加入', 
            '2' => '已经加入');
        node_log("首页+异业联盟+加入联盟申请列表");
        $this->getNodeTree();
        $this->assign('checkStatus', $checkStatus);
        $this->assign('list', $list);
        $this->assign('nodeList', $this->getNodeTree());
        $this->assign('post', $_REQUEST);
        $this->assign("page", $page);
        $this->display();
    }
    
    // 加入联盟申请详情
    public function joinOtherBlocDetail() {
        $id = I('id', 'mysql_real_escape_string');
        $map = array(
            'r.id' => $id, 
            'i.goods_type' => '1', 
            'i.source' => '2', 
            'r.req_node_id' => array(
                'exp', 
                "in ({$this->nodeIn()})"));
        $list = M()->table('tbatch_relation r')
            ->field(
            'r.add_time,r.status,r.store_list,i.bloc_name,i.goods_name,i.goods_image,i.recruit_end_date,i.goods_amt,i.begin_time,i.end_time,i.storage_num,i.storage_type')
            ->join("tgoods_info i ON i.goods_id=r.goods_id")
            ->where($map)
            ->find();
        if (! $list)
            $this->error('未找到该联盟信息');
            // 查找城市
        $citylist = M()->table('tcity_code t')
            ->field('GROUP_CONCAT(t.city) as city')
            ->where(
            "t.city_level='2' and t.city_code in(SELECT city_code FROM tgoods_bloc_city WHERE goods_id='{$list['goods_id']}')")
            ->find();
        $list['city_str'] = $citylist['city'];
        // 时间计算
        $now = date('Y-m-d');
        $end_time = $list['recruit_end_date'];
        $day = floor((strtotime($end_time) - strtotime($now)) / 86400);
        if ($day < 0) {
            $day = "已结束";
        } else {
            $day = $day . "天";
        }
        $list['day'] = $day;
        // 门店名称
        $where = array(
            'store_id' => array(
                'in', 
                $list['store_list']));
        $storeList = M('tstore_info')->where($where)->getField('store_name', 
            true);
        $list['store_name'] = implode(",", $storeList);
        $checkStatus = array(
            '0' => '正在审核', 
            '1' => '拒绝加入', 
            '2' => '已经加入');
        $this->assign('list', $list);
        $this->assign('checkStatus', $checkStatus);
        $this->display();
    }
    // 被别人邀请的记录
    public function adminshop() {
        $map = array(
            'a.req_node_id' => array(
                'exp', 
                "in ({$this->nodeIn()})"), 
            'a.join_type' => array(
                'in', 
                '1,2'));
        if ($_POST) {
            $name = I('post.name');
            $badd_time = I('post.badd_time');
            if ($name != "") {
                $map['c.node_name'] = array(
                    'like', 
                    '%' . $name . '%');
            }
            if ($badd_time != "") {
                $map['b.recruit_end_date'] = array(
                    'elt', 
                    $badd_time . '235959');
            }
        }
        import("ORG.Util.Page");
        $count = M()->table('tbatch_relation a')
            ->join('tgoods_info b on a.goods_id=b.goods_id')
            ->where($map)
            ->count();
        $Page = new Page($count, 10);
        $checkStatus = array(
            '0' => '等待加入', 
            '1' => '已拒绝', 
            '2' => '已同意');
        $m = M()->table('tbatch_relation a')
            ->field(
            'a.id,a.goods_id,a.req_node_id,b.bloc_name,a.invite_status,b.goods_name,b.recruit_end_date,c.node_name')
            ->join('tgoods_info b on a.goods_id=b.goods_id')
            ->join('tnode_info c on a.node_id=c.node_id')
            ->where($map)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        // dump($m);die;
        
        $show = $Page->show(); // 分页显示输出
        $this->assign('list', $m);
        $this->assign('page', $show);
        $this->assign('checkStatus', $checkStatus);
        $this->assign('nodeList', $this->getNodeTree());
        $this->display();
    }
    
    // 被别人邀请的记录详情
    public function shop_mes() {
        $id = I('get.id', null, 'mysql_real_escape_string');
        $map = array(
            'r.id' => $id, 
            'r.join_type' => array(
                'in', 
                '1,2'), 
            'r.req_node_id' => $this->nodeId);
        $goods_id = M('tbatch_relation')->field('goods_id,remark')
            ->where(array(
            'id' => $id))
            ->find();
        $list = M()->table('tbatch_relation r')
            ->field(
            'n.node_name,n.node_citycode,g.goods_name,g.bloc_name,g.goods_amt,g.goods_desc,g.recruit_end_date,r.id,r.remark,r.status,r.invite_status,r.store_list,r.contact_name,r.contact_phone,r.contact_eml,g.begin_time,g.end_time')
            ->join("tgoods_info g ON g.goods_id=r.goods_id")
            ->join("tnode_info n ON r.node_id=n.node_id")
            ->where($map)
            ->find();
        $cid = substr($list["node_citycode"], 2, 3);
        // echo $cid;
        $city = M('tcity_code')->where(
            array(
                'city_code' => $cid, 
                'city_level' => '2'))->getField('city');
        // 时间计算
        $now = date('Y-m-d');
        $end_time = $list['recruit_end_date'];
        $day = floor((strtotime($end_time) - strtotime($now)) / 86400);
        if ($day < 0) {
            $day = "已过期";
        } else {
            $day = $day . "天";
        }
        // 已同意获取门店信息和对方审核状态
        if ($list['invite_status'] == '2') {
            // 门店名称
            $where = array(
                'store_id' => array(
                    'in', 
                    $list['store_list']));
            $storeList = M('tstore_info')->where($where)->getField('store_name', 
                true);
            $list['store_name'] = implode(",", $storeList);
        }
        // dump($list);
        $checkStatus = array(
            '0' => '正在审核', 
            '1' => '拒绝加入', 
            '2' => '已经加入');
        $this->assign('city', $city);
        $this->assign('row', $list);
        $this->assign('days', $day);
        $this->assign('checkStatus', $checkStatus);
        $this->display();
    }
    // （被邀请）拒绝加入联盟
    public function reject() {
        $id = I('batchId', null, 'mysql_real_escape_string');
        $relationInfo = M('tbatch_relation')->where(
            "id='{$id}' AND req_node_id='{$this->nodeId}' AND join_type='1'")->find();
        if (! $relationInfo)
            $this->error('未找到有效数据');
        if ($relationInfo['status'] == '1') {
            $this->error('您已经拒绝了该申请');
        } elseif ($relationInfo['status'] == '2') {
            $this->error('该申请已经加入到联盟中了');
        }
        $data = array(
            'invite_status' => '1');
        $result = M('tbatch_relation')->where("id='{$id}'")->save($data);
        if ($result === false)
            $this->error('数据出错更新失败');
        $this->success('数据更新成功');
    }
    
    // 同意加入联盟
    public function mixlm() {
        $id = I('id', null, 'mysql_real_escape_string');
        if ($this->isPost()) {
            $map = array(
                'r.id' => $id, 
                'r.invite_status' => '0', 
                'b.check_status' => '1', 
                'b.status' => '0', 
                'b.goods_type' => '1', 
                'b.source' => '2', 
                'b.recruit_end_date' => array(
                    'gt', 
                    date('YmdHis')));
            $goodsInfo = M()->table('tbatch_relation r')
                ->field('b.*,r.invite_status')
                ->join("tgoods_info b ON b.goods_id=r.goods_id")
                ->where($map)
                ->find();
            if (! $goodsInfo)
                $this->error('无效联盟信息或该联盟招募时间已结束');
                // 门店处理
            $shopData = I('post.shop_id', null);
            if (! is_array($shopData) || empty($shopData))
                $this->error('请选择验证门店');
            $shopData = array_unique($shopData);
            // 多次加入去重处理
            $where = array(
                'goods_id' => $goodsInfo['goods_id'], 
                'node_id' => $goodsInfo['node_id'], 
                'status' => array(
                    'in', 
                    '0,2'), 
                'req_node_id' => $this->nodeId);
            $oldShopData = M('tbatch_relation')->where($where)->select();
            if ($oldShopData) {
                $oldShopList = array();
                foreach ($oldShopData as $v) { // 加入的所有门店列表
                    $oldShopList = array_merge($oldShopList, 
                        explode(',', $v['store_list']));
                }
                foreach ($shopData as $k => $v) { // 去重
                    if (in_array($v, $oldShopList)) {
                        unset($shopData[$k]);
                    }
                }
                unset($oldShopData);
                unset($oldShopList);
            }
            if (empty($shopData))
                $this->error('您的门店信息已经加入过该联盟,不要重复加入');
            $where = array(
                's.store_id' => array(
                    'in', 
                    $shopData), 
                's.node_id' => array(
                    'exp', 
                    "in ({$this->nodeIn()})"), 
                's.pos_range' => array(
                    'gt', 
                    '1'));
            // 获取终端号
            $posData = M()->table('tstore_info s')
                ->field('p.pos_id,p.store_id,p.node_id')
                ->join(
                'tpos_info p ON s.store_id=p.store_id AND s.node_id=p.node_id')
                ->where($where)
                ->select();
            if (! $posData)
                $this->error('未找到门店终端信息');
            $posArr = array();
            foreach ($posData as $v) {
                $posArr[] = $v['pos_id'];
            }
            $posList = implode(',', $posArr);
            $storeList = implode(',', $shopData);
            $data = array(
                'store_list' => $storeList, 
                'pos_list' => $posList, 
                'invite_status' => '2', 
                'join_type' => '2', 
                'update_time' => date('YmdHis'));
            $result = M('tbatch_relation')->where("id='{$id}'")->save($data);
            if ($result === false)
                $this->error('系统出错添加失败');
            $this->success('加入成功,等待对方审核');
            exit();
        }
        $this->assign('id', $id);
        $this->display();
    }
    
    // 联盟招募邀请
    public function invite() {
        $map = array(
            'a.node_id' => $this->node_id, 
            'a.join_type' => array(
                'in', 
                '1,2'));
        if (I('post.name') != "") {
            $map['b.bloc_name'] = array(
                'like', 
                '%' . I('post.name') . '%');
        }
        if (I('post.username') != "") {
            $map['c.node_name'] = array(
                'like', 
                '%' . I('post.username') . '%');
        }
        if (I('post.badd_time') != "") {
            $map['a.add_time'] = array(
                'eq', 
                I('post.badd_time'));
        }
        if (I('post.status') != "") {
            $map['a.invite_status'] = I('post.status');
        }
        import("ORG.Util.Page"); // 导入分页
        $count = M()->table('tbatch_relation a')
            ->join(
            'tgoods_info b ON a.goods_id=b.goods_id AND a.node_id=b.node_id')
            ->join('tnode_info c ON a.req_node_id=c.node_id')
            ->where($map)
            ->count();
        $p = new Page($count, 10);
        $list = M()->table('tbatch_relation a')
            ->field('a.*,b.bloc_name,c.node_name,a.add_time')
            ->join(
            'tgoods_info b ON a.goods_id=b.goods_id AND a.node_id=b.node_id')
            ->join('tnode_info c ON a.req_node_id=c.node_id')
            ->where($map)
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        // dump($list);
        // echo M()->getLastSql();
        $checkStatus = array(
            '未处理', 
            '已拒绝', 
            '已同意');
        $page = $p->show();
        $this->assign('Page', $page);
        $this->assign('list', $list);
        $this->assign('checkStatus', $checkStatus);
        $this->display();
    }
    // 联盟招募邀请商户详情
    public function invite_mes($id) {
        $m = M('tbatch_relation')->where(
            array(
                'id' => $id, 
                'node_id' => $this->node_id))->find();
        $tname = M('goods_info')->where(
            array(
                'node_id' => $this->node_id, 
                'goods_id' => $m['goods_id']))->getField('bloc_name');
        $row = M('tnode_info')->where(
            array(
                'node_id' => $m['req_node_id']))->find();
        $this->assign('lmname', $tname);
        $this->assign('list', $row);
        $this->display();
    }

    /**
     * 创建支撑活动 $goodsInfo 采购或提供的商品信息 $img 图片信息 $node_id 机构号（业务商）
     */
    public function _createZctpBatch($goodsInfo, $img, $node_id) {
        // ================创建smil==================
        $imageName = $img;
        $name = $goodsInfo['goods_name'];
        $smilId = '';
        
        $imagePath = realpath(C('UPLOAD'));
        $zipFileName = time() . mt_rand(100000, 999999) . '.zip';
        import('@.ORG.Net.Zip', '', '.php') or die('导入包失败');
        $test = new zip_file($zipFileName);
        $test->set_options(
            array(
                'basedir' => $imagePath . '/template/', 
                'inmemory' => 0, 
                'recurse' => 1, 
                'storepaths' => 0, 
                'overwrite' => 1, 
                'level' => 5, 
                'name' => $zipFileName));
        $imageUrl = $imagePath . '/' . $imageName;
        
        $smil_id = '';
        if (is_file($imageUrl)) {
            // 缩放图片大小要小于60k
            import('ORG.Util.Image');
            $smilUrl = Image::thumb($imageUrl, 
                dirname($imageUrl) . '/smi_' . basename($imageUrl), '', 150, 150, 
                true);
            if (! $smilUrl) {
                throw new Exception("SmilId接口图片压缩失败");
            }
            
            $imageUrl = $smilUrl;
            $smil_cfg = create_smil_cfg($imageUrl);
            if ($smil_cfg === false) {
                throw new Exception("创建smil_cfg失败");
            }
            $info = pathinfo($imageUrl);
            $ex = $info['extension'];
            $files = array(
                '1.' . $ex => $imageUrl, 
                'default.smil' => realpath($smil_cfg));
            $test->add_files($files);
            $test->create_archive();
            $zipPath = $imagePath . '/template/' . $zipFileName;
            $SmilZip = base64_encode(file_get_contents($zipPath));
            // 通知支撑
            $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
                                                                       // 请求参数
            $req_array = array(
                'SmilAddEditReq' => array(
                    'ISSPID' => $node_id, 
                    'PlatformID' => C('ISS_PLATFORM_ID'), 
                    'TransactionID' => $TransactionID, 
                    'Username' => C('ISS_SEND_USER'), 
                    'Password' => C('ISS_SEND_USER_PASS'), 
                    'SmilInfo' => array(
                        'SmilId' => $smilId, 
                        'SmilName' => time(), 
                        'SmilDesc' => $name, 
                        'SmilZip' => $SmilZip)));
            $RemoteRequest = D('RemoteRequest', 'Service');
            $resp_array = $RemoteRequest->smilAddEditReq($req_array);
            @unlink($zipPath);
            @unlink($smil_cfg);
            @unlink($smilUrl);
            $ret_msg = $resp_array['SmilAddEditRes']['Status'];
            if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
                 $ret_msg['StatusCode'] != '0001')) {
                throw new Exception("获取SmilId失败 原因：{$ret_msg['StatusText']}");
            }
            $smil_id = $resp_array['SmilAddEditRes']['SmilId'];
        }
        
        // ================创建活动==================
        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
                                                                   // 处理接口报文GoodsInfo信息
        $reqInfo = array(
            'GoodsName' => $goodsInfo['goods_name'], 
            'GoodsShortName' => $goodsInfo['goods_name']);
        
        // 请求参数
        $req_array = array(
            'ActivityCreateReq' => array(
                'SystemID' => C('ISS_SYSTEM_ID'), 
                'ISSPID' => $node_id, 
                'RelationID' => $goodsInfo['node_id'], 
                'TransactionID' => $TransactionID, 
                'SmilID' => $smil_id, 
                'ActivityInfo' => array(
                    'CustomNo' => '', 
                    'ActivityName' => $goodsInfo['goods_name'], 
                    'ActivityShortName' => $goodsInfo['goods_name'], 
                    'BeginTime' => date('Ymd000000'), 
                    'EndTime' => date('20301231235959'), 
                    'UseRangeID' => $goodsInfo['pos_group']), 
                'VerifyMode' => array(
                    'UseTimesLimit' => $goodsInfo['goods_type'] == 3 ? 0 : 1, 
                    'UseAmtLimit' => $goodsInfo['goods_type'] == 3 ? 1 : 0), 
                'GoodsInfo' => $reqInfo, 
                'DefaultParam' => array(
                    'PasswordTryTimes' => 3, 
                    'PasswordType' => '', 
                    'PrintText' => $goodsInfo['print_text'])));
        
        $RemoteRequest = D('RemoteRequest', 'Service');
        $resp_array = $RemoteRequest->requestIssForImageco($req_array);
        $ret_msg = $resp_array['ActivityCreateRes']['Status'];
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
             $ret_msg['StatusCode'] != '0001')) {
            throw new Exception("添加失败:{$ret_msg['StatusText']}");
        }
        
        $ActivityID = $resp_array['ActivityCreateRes']['Info']['ActivityID'];
        $pGoodsId = $resp_array['ActivityCreateRes']['Info']['pGoodsId'];
        
        return array(
            'batch_no' => $ActivityID, 
            'p_goods_id' => $pGoodsId);
    }
}