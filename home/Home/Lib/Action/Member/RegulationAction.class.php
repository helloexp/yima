<?php

/*
 * To change this template, choose Tools | Templates and open the template in
 * the editor. 设置粉丝级别，粉丝规则。
 */
class RegulationAction extends MemberAction {

    public function index() {
        $model = M('tmember_batch');
        $map = array(
            'node_id' => $this->node_id, 
            'status' => '1');
        $list = $model->where($map)
            ->order('member_level')
            ->select();
        
        if ($list) {
            $list = array_valtokey($list, 'member_level');
        }
        $this->assign('list', $list);
        
        $showTab = I('show');
        if (empty($showTab) || (int) $showTab < 1 ||
             (int) $showTab > C("MEMBER_LEVEL"))
            $showTab = 1;
        $this->assign('level_arr', C(LEVEL_ARR));
        $this->assign('level', C("MEMBER_LEVEL"));
        $this->assign('showTab', (int) $showTab);
        $this->display();
    }
    
    // 粉丝卡更新
    public function save() {
        $error = '';
        $memberLevel = I('level_id', null, 'mysql_real_escape_string');
        if (! check_str($memberLevel, 
            array(
                'null' => false, 
                'minval' => '1', 
                'maxval' => C('MEMBER_LEVEL'), 
                'strtype' => 'int'), $error)) {
            $this->error('参数错误');
        }
        $cardsData = M('tmember_batch')->where(
            "node_id='{$this->nodeId}' AND member_level='{$memberLevel}' AND status=1")->find();
        if (! $cardsData)
            $this->error('未找到该粉丝权益信息');
            // 门店信息
        $goodsData = M('tgoods_info')->where(
            "batch_no='{$cardsData['batch_no']}' AND node_id='{$this->nodeId}'")->find();
        if ($goodsData['pos_group_type'] == '2') { // 获取终端门店
            $oldStoreArr = array();
            $storeData = M('tgroup_pos_relation')->field('store_id')
                ->where("group_id={$goodsData['pos_group']}")
                ->select();
            foreach ($storeData as $v) {
                $oldStoreArr[] = $v['store_id'];
            }
            $oldStoreArr = array_unique($oldStoreArr);
        }
        
        if ($this->isPost()) { // 数据提交
            
            $levelName = I('level_name', null);
            if (! check_str($levelName, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '7'), $error)) {
                $this->error("粉丝权益卡名称{$error}");
            }
            $dateType = I('date_type', null);
            switch ($dateType) {
                case 0:
                    $validDay = I('valid_day', null);
                    if (! check_str($validDay, 
                        array(
                            'null' => false, 
                            'minval' => '1', 
                            'maxval' => '365', 
                            'strtype' => 'int'), $error)) {
                        $this->error("有效期天数{$error}");
                    }
                    $beginDate = date('YmdHis');
                    $endDate = '20301230235959';
                    $verifyBeginDate = '0';
                    $verifyEndDate = $validDay;
                    $verifyBeginType = '1';
                    $verfyEndType = '1';
                    break;
                case 1:
                    $beginTime = I('post.begin_date');
                    if (! check_str($beginTime, 
                        array(
                            'null' => false, 
                            'strtype' => 'datetime', 
                            'format' => 'Ymd'), $error)) {
                        $this->error("有效期开始日期{$error}");
                    }
                    $endTime = I('post.end_date');
                    if (! check_str($endTime, 
                        array(
                            'null' => false, 
                            'strtype' => 'datetime', 
                            'format' => 'Ymd'), $error)) {
                        $this->error("有效期结束日期{$error}");
                    }
                    if ($endTime < date('Ymd')) {
                        $this->error('有效期结束日期不能小于当前日期');
                    }
                    if (strtotime($endTime) < strtotime($beginTime)) {
                        $this->error('有效期开始日期不能大于有效期结束日期');
                    }
                    $beginDate = $beginTime . '000000';
                    $endDate = $endTime . '235959';
                    $verifyBeginDate = $beginTime . '000000';
                    $verifyEndDate = $endTime . '235959';
                    $verifyBeginType = '0';
                    $verfyEndType = '0';
                    break;
                default:
                    $this->error('请选择有效期');
            }
            $printInfo = I('pri_info');
            if (! check_str($printInfo, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '100'), $error)) {
                $this->error("特权描述{$error}");
            }
            $goodsModel = D('Goods');
            $goodsModel->startTrans();
            // 门店修改
            $shop = I('post.shop');
            switch ($shop) {
                case '1':
                    if ($goodsData['pos_group_type'] == '2') { // 子门店变为全门店
                        $groupId = $goodsModel->zcModifyStore($this->nodeId, 
                            $goodsData['p_goods_id'], '4');
                        if (! $groupId) {
                            $goodsModel->rollback();
                            $this->error($goodsModel->getError());
                        }
                        // 新建合约
                        $nodeList = M()->query($this->nodeIn(null, true));
                        $groupData = array( // tpos_group
                            'node_id' => $this->nodeId, 
                            'group_id' => $groupId, 
                            'group_name' => $this->nodeId . '商户型-终端组', 
                            'group_type' => '0', 
                            'status' => '0');
                        $result = M('tpos_group')->add($groupData);
                        if (! $result) {
                            M()->rollback();
                            $this->error('终端数据创建失败01');
                        }
                        foreach ($nodeList as $v) {
                            $data_1 = array(
                                'group_id' => $groupId, 
                                'node_id' => $v['node_id']);
                            $result = M('tgroup_pos_relation')->add($data_1);
                            if (! $result) {
                                M()->rollback();
                                $this->error('终端数据创建失败02');
                            }
                        }
                        $posGroup = $groupId;
                        $posGroupType = $shop;
                    }
                    break;
                case '2':
                    $shopList = array_unique(
                        explode(',', I('post.shop_idstr', '')));
                    if (! is_array($shopList) || empty($shopList))
                        $this->error('请选择验证门店');
                        // 获取所选门店的所有终端
                    $where = array(
                        's.store_id' => array(
                            'in', 
                            $shopList), 
                        's.node_id' => array(
                            'exp', 
                            "in ({$this->nodeIn()})"), 
                        's.pos_range' => array(
                            'gt', 
                            '1'));
                    $posData = M()->table("tstore_info s")->field(
                        'p.pos_id,p.store_id,p.node_id')
                        ->join(
                        'tpos_info p ON s.store_id=p.store_id AND s.node_id=p.node_id')
                        ->where($where)
                        ->select();
                    // 获取有效的门店和过滤非法$shopList
                    $newStoreArr = array();
                    foreach ($posData as $v) {
                        $newStoreArr[] = $v['store_id'];
                    }
                    $newStoreArr = array_unique($newStoreArr);
                    $arrayDiff = array_diff($newStoreArr, $oldStoreArr);
                    if ($goodsData['pos_group_type'] == '1' ||
                         count($newStoreArr) != count($oldStoreArr) ||
                         ! empty($arrayDiff)) { // 全门店变成子门店或门店增加减少
                        $groupId = $goodsModel->zcModifyStore($this->nodeId, 
                            $goodsData['p_goods_id'], '2', 
                            implode(',', $newStoreArr));
                        if (! $groupId) {
                            $goodsModel->rollback();
                            $this->error($goodsModel->getError());
                        }
                        $num = M('tpos_group')->where(
                            "group_id='{$groupId}' AND node_id='{$this->nodeId}'")->count();
                        if ($num != '0') { // 删除旧合约
                            $result = M('tpos_group')->where(
                                "group_id='{$groupId}' AND node_id={$this->nodeId}")->delete();
                            if ($result === false) {
                                $goodsModel->rollback();
                                $this->error('数据出错,旧合约删除失败01');
                            }
                            $result = M('tgroup_pos_relation')->where(
                                "group_id='{$groupId}' AND node_id={$this->nodeId}")->delete();
                            if ($result === false) {
                                $goodsModel->rollback();
                                $this->error('数据出错,旧合约删除失败02');
                            }
                        }
                        // 创建新合约
                        $groupData = array( // tpos_group
                            'node_id' => $this->nodeId, 
                            'group_id' => $groupId, 
                            'group_name' => $this->nodeId . '终端型-终端组', 
                            'group_type' => '1', 
                            'status' => '0');
                        $result = M('tpos_group')->add($groupData);
                        if (! $result) {
                            $goodsModel->rollback();
                            $this->error('终端数据创建失败03');
                        }
                        foreach ($posData as $v) {
                            $data_2 = array(
                                'group_id' => $groupId, 
                                'node_id' => $v['node_id'], 
                                'store_id' => $v['store_id'], 
                                'pos_id' => $v['pos_id']);
                            $result = M('tgroup_pos_relation')->add($data_2);
                            if (! $result) {
                                $goodsModel->rollback();
                                $this->error('终端数据创建失败04');
                            }
                        }
                        $posGroup = $groupId;
                        $posGroupType = $shop;
                    }
                    break;
            }
            
            $result = $goodsModel->zcModifyBatch($this->nodeId, 
                $goodsData['batch_no'], $levelName, $levelName, 
                $goodsData['add_time'], $printInfo);
            if (! $result) {
                $goodsModel->rollback();
                $this->error($goodsModel->getError());
            }
            /*
             * //支撑修改活动 $TransactionID = date("YmdHis").mt_rand(100000,999999);
             * //请求单号 //请求参数 $req_array = array( 'ActivityModifyReq'=>array(
             * 'SystemID'=>C('ISS_SYSTEM_ID'), 'ISSPID'=>$this->nodeId,
             * 'TransactionID'=>$TransactionID,
             * 'ActivityID'=>$cardsData['batch_no'], 'ActivityStatus'=>'0',
             * 'ActivityInfo'=>array( 'ActivityName'=>$levelName,
             * 'ActivityShortName'=>$levelName,
             * 'BeginTime'=>$cardsData['add_time'], 'EndTime'=>'20301231235959',
             * ) ) ); $RemoteRequest = D('RemoteRequest','Service'); $resp_array
             * = $RemoteRequest->requestIssServ($req_array); $ret_msg =
             * $resp_array['ActivityModifyReq']['Status'];
             * if(!$resp_array||($ret_msg['StatusCode'] != '0000'&&
             * $ret_msg['StatusCode'] != '0001')) {
             * $this->error("更新失败:{$ret_msg['StatusText']}"); } //特权添加 $result =
             * $this->privilegeEdit($cardsData['batch_no'], $printInfo);
             * if(!$result) $this->error('系统出错,特权信息编辑失败');
             */
            $data = array(
                'level_name' => $levelName, 
                'valid_day' => $dateType == '1' ? '' : $verifyEndDate, 
                'print_info' => $printInfo, 
                'verify_begin_date' => $verifyBeginDate, 
                'verify_end_date' => $verifyEndDate, 
                'date_type' => $dateType);
            $result = M('tmember_batch')->where(
                "node_id='{$this->nodeId}' AND member_level='{$memberLevel}'")->save(
                $data);
            if ($result === false) {
                M()->rollback();
                $this->error('系统出错,更新失败!');
            }
            // 更新goods_info表数据
            $data = array(
                'user_id' => $this->userId, 
                'goods_name' => $levelName, 
                'update_time' => date('YmdHis'), 
                'begin_time' => $beginDate, 
                'end_time' => $endDate, 
                'verify_begin_date' => $verifyBeginDate, 
                'verify_end_date' => $verifyEndDate, 
                'verify_begin_type' => $verifyBeginType, 
                'verify_end_type' => $verfyEndType, 
                'print_text' => $printInfo, 
                'pos_group' => $posGroup, 
                'pos_group_type' => $posGroupType);
            $result = M('tgoods_info')->where(
                "node_id='{$this->nodeId}' AND batch_no='{$cardsData['batch_no']}'")->save(
                $data);
            if ($result === false) {
                M()->rollback();
                $this->error('系统出错,更新失败!');
            }
            M()->commit();
            node_log("粉丝框:粉丝权益卡更新，名称：" . $levelName);
            $this->success('更新成功');
        } else {
            $cardsData['node_pos_type'] = $goodsData['pos_group_type'];
            $cardsData['oldStoreStr'] = empty($oldStoreArr) ? '' : implode(',', 
                $oldStoreArr);
            $cardsData['storeCount'] = empty($oldStoreArr) ? '0' : count(
                $oldStoreArr);
            $this->ajaxReturn($cardsData, '', 1);
        }
    }

    /**
     * 粉丝权益启用
     */
    public function openLevel() {
        $error = '';
        $memberLevel = I('level_id', null, 'mysql_real_escape_string');
        if (! check_str($memberLevel, 
            array(
                'null' => false, 
                'minval' => '1', 
                'maxval' => C('MEMBER_LEVEL'), 
                'strtype' => 'int'), $error)) {
            $this->error('参数错误');
        }
        $levelName = I('level_name', null);
        if (! check_str($levelName, 
            array(
                'null' => false, 
                'maxlen_cn' => '7'), $error)) {
            $this->error("粉丝权益卡名称{$error}");
        }
        $dateType = I('date_type', null);
        switch ($dateType) {
            case 0:
                $validDay = I('valid_day', null);
                if (! check_str($validDay, 
                    array(
                        'null' => false, 
                        'minval' => '1', 
                        'maxval' => '365', 
                        'strtype' => 'int'), $error)) {
                    $this->error("有效期天数{$error}");
                }
                $beginDate = date('YmdHis');
                $endDate = '20301230235959';
                $verifyBeginDate = '0';
                $verifyEndDate = $validDay;
                $verifyBeginType = '1';
                $verfyEndType = '1';
                break;
            case 1:
                $beginTime = I('post.begin_date');
                if (! check_str($beginTime, 
                    array(
                        'null' => false, 
                        'strtype' => 'datetime', 
                        'format' => 'Ymd'), $error)) {
                    $this->error("有效期开始日期{$error}");
                }
                $endTime = I('post.end_date');
                if (! check_str($endTime, 
                    array(
                        'null' => false, 
                        'strtype' => 'datetime', 
                        'format' => 'Ymd'), $error)) {
                    $this->error("有效期结束日期{$error}");
                }
                if ($endTime < date('Ymd')) {
                    $this->error('有效期结束日期不能小于当前日期');
                }
                if (strtotime($endTime) < strtotime($beginTime)) {
                    $this->error('有效期开始日期不能大于有效期结束日期');
                }
                $beginDate = $beginTime . '000000';
                $endDate = $endTime . '235959';
                $verifyBeginDate = $beginTime . '000000';
                $verifyEndDate = $endTime . '235959';
                $verifyBeginType = '0';
                $verfyEndType = '0';
                break;
            default:
                $this->error('请选择有效期');
        }
        $printInfo = I('pri_info');
        if (! check_str($printInfo, 
            array(
                'null' => false, 
                'maxlen_cn' => '100'), $error)) {
            $this->error("特权描述{$error}");
        }
        $batchModel = M('tmember_batch');
        // 获取该用户所有粉丝卡
        $cardsData = $batchModel->field('level_name,member_level,batch_no')
            ->where("node_id='{$this->nodeId}'")
            ->select();
        
        $actionType = false; // 操作类型 true 更新 false 添加
        if ($cardsData) {
            $cardsData = array_valtokey($cardsData, 'member_level');
            foreach ($cardsData as $v) {
                if ($v['level_name'] == $levelName &&
                     $memberLevel != $v['member_level']) {
                    $this->error('该粉丝权益卡名称和其他粉丝权益卡名称重复');
                }
                if ($memberLevel == $v['member_level']) {
                    $actionType = true;
                }
            }
        }
        // 门店处理
        $shop = I('post.shop');
        switch ($shop) {
            case 1: // 全门店
                $groupType = 0;
                $nodeList = M()->query($this->nodeIn(null, true));
                $nodeArr = array();
                foreach ($nodeList as $v) {
                    $nodeArr[] = $v['node_id'];
                }
                $dataList = implode(',', $nodeArr);
                break;
            case 2: // 子门店
                $groupType = 1;
                // 获取所有终端列表
                $shopList = explode(',', I('post.shop_idstr', ''));
                if (! is_array($shopList) || empty($shopList))
                    $this->error('请选择验证门店');
                $where = array(
                    's.store_id' => array(
                        'in', 
                        array_unique($shopList)), 
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
                    $this->error('获取门店信息出错');
                $posArr = array();
                foreach ($posData as $v) {
                    $posArr[] = $v['pos_id'];
                }
                $dataList = implode(',', $posArr);
                break;
            default:
                $this->error("请选择卡券可验证门店");
        }
        M('tnode_info')->where("node_id='{$this->nodeId}'")->setInc(
            'posgroup_seq'); // posgroup_seq
                             // +1;
                             // 商户信息
        $nodeInfo = M('tnode_info')->field(
            'node_name,client_id,node_service_hotline,posgroup_seq')
            ->where("node_id='{$this->nodeId}'")
            ->find();
        $goodsModel = D('Goods');
        // 支撑创建终端组
        $groupInfo = $goodsModel->zcCreateGroup($groupType, 
            $nodeInfo['client_id'], $nodeInfo['posgroup_seq'], $dataList, 
            $this->nodeId);
        if (! $groupInfo)
            $this->error($goodsModel->getError());
        $groupId = $groupInfo['groupId'];
        // 插入终端组信息
        $num = M('tpos_group')->where("group_id='{$groupId}'")->count();
        M()->startTrans();
        if ($num == '0') { // 不存在终端组去创建
            $data = array( // tpos_group
                'node_id' => $this->nodeId, 
                'group_id' => $groupId, 
                'group_name' => $groupInfo['groupName'], 
                'group_type' => $groupType, 
                'status' => '0');
            $result = M('tpos_group')->add($data);
            if (! $result) {
                M()->rollback();
                $this->error('终端数据创建失败');
            }
            switch ($groupType) { // tgroup_pos_relation
                case 0: // 全商户
                    foreach ($nodeList as $v) {
                        $data = array(
                            'group_id' => $groupId, 
                            'node_id' => $v['node_id']);
                        $result = M('tgroup_pos_relation')->add($data);
                        if (! $result) {
                            M()->rollback();
                            $this->error('终端数据创建失败');
                        }
                    }
                    break;
                case 1: // 终端型
                    foreach ($posData as $v) {
                        $data = array(
                            'group_id' => $groupId, 
                            'node_id' => $v['node_id'], 
                            'store_id' => $v['store_id'], 
                            'pos_id' => $v['pos_id']);
                        $result = M('tgroup_pos_relation')->add($data);
                        if (! $result) {
                            M()->rollback();
                            $this->error('终端数据创建失败');
                        }
                    }
                    break;
            }
        }
        // 支撑创建合约
        $zcData = array(
            'shopNodeId' => $this->nodeId, 
            'bussNodeId' => $this->nodeId, 
            'treatyName' => $levelName, 
            'treatyShortName' => $levelName, 
            'groupId' => $groupId);
        $treatyId = $goodsModel->zcCreateTreaty($zcData);
        if (! $treatyId)
            $this->error($goodsModel->getError());
            
            // 支撑创建活动
        $zcData = array(
            'isspid' => $this->nodeId, 
            'relationId' => $this->nodeId, 
            'batchName' => $levelName, 
            'batchShortName' => $levelName, 
            'groupId' => $groupId, 
            'validateType' => '0', 
            'serviceType' => '00', 
            'onlineVerify' => '', 
            'smilId' => null, 
            'treatyId' => $treatyId, 
            'printText' => $printInfo);
        $batchInfo = $goodsModel->zcCreateBatch($zcData);
        if (! $batchInfo)
            $this->error($goodsModel->getError());
        $batchNo = $batchInfo['batchNo'];
        
        // 添加tgoods_info表信息
        $data = array(
            'goods_id' => get_goods_id(), 
            'batch_no' => $batchNo, 
            'node_id' => $this->nodeId, 
            'user_id' => $this->userId, 
            'goods_type' => '0', 
            'source' => '3', 
            'goods_name' => $levelName, 
            'begin_time' => $beginDate, 
            'end_time' => $endDate, 
            'validate_times' => '99999', 
            'verify_begin_date' => $verifyBeginDate, 
            'verify_end_date' => $verifyEndDate, 
            'verify_begin_type' => $verifyBeginType, 
            'verify_end_type' => $verfyEndType, 
            'pos_group' => $groupId, 
            'pos_group_type' => $shop, 
            'p_goods_id' => $treatyId, 
            'print_text' => $printInfo, 
            'add_time' => date('YmdHis'));
        $result = M('tgoods_info')->add($data);
        if (! $result) {
            M()->rollback();
            $this->error('系统出错,启用失败-0001!');
        }
        
        if ($actionType) { // 有数据执行更新操,支撑创建新活动
            $data = array(
                'level_name' => $levelName, 
                'batch_no' => $batchNo, 
                'valid_day' => $dateType == '1' ? '' : $verifyEndDate, 
                'status' => '1', 
                'verify_begin_date' => $verifyBeginDate, 
                'verify_end_date' => $verifyEndDate, 
                'print_info' => $printInfo, 
                'date_type' => $dateType);
            $result = $batchModel->where(
                "node_id='{$this->nodeId}' AND member_level='{$memberLevel}'")->save(
                $data);
            if ($result === false) {
                M()->rollback();
                $this->error('系统出错,更新失败-0002!');
            }
        } else { // 添加新数据
                 // 添加tmember_batch表数据
            $data = array(
                'node_id' => $this->nodeId, 
                'batch_no' => $batchNo, 
                'level_name' => $levelName, 
                'member_level' => $memberLevel, 
                'valid_day' => $dateType == '1' ? '' : $verifyEndDate, 
                'print_info' => $printInfo, 
                'status' => '1', 
                'add_time' => date('YmdHis'), 
                'verify_begin_date' => $verifyBeginDate, 
                'verify_end_date' => $verifyEndDate, 
                'date_type' => $dateType);
            $result = $batchModel->add($data);
            if (! $result) {
                M()->rollback();
                $this->error('系统出错,启用失败-0003!');
            }
        }
        M()->commit();
        node_log("粉丝框:粉丝权益卡启用，名称：" . $levelName);
        $this->success('启用成功');
    }

    /**
     * 粉丝权益停用
     */
    public function levelStop() {
        $memberLevel = I('level_id', null, 'mysql_real_escape_string');
        if (! check_str($memberLevel, 
            array(
                'null' => false, 
                'minval' => '1', 
                'maxval' => C('MEMBER_LEVEL'), 
                'strtype' => 'int'), $error)) {
            $this->error('参数错误');
        }
        $levelInfo = M('tmember_batch')->where(
            "node_id='{$this->nodeId}' AND member_level='{$memberLevel}' AND status=1")->find();
        if (! $levelInfo) {
            $this->error('未找到该粉丝权益卡信息或该粉丝卡已停用');
        }
        // 撤销该权益下的粉丝权益
        $memberData = M('tmember_info')->where(
            "batch_no={$levelInfo['batch_no']} AND node_id='{$this->nodeId}'")->select();
        $erNum = 0; // 撤销失败计数
        foreach ($memberData as $k => $v) {
            if (! empty($v['request_id'])) { // 发过码的撤销
                $result = $this->cancelCode($v['request_id']);
                if ($result !== true) {
                    $erNum ++;
                } else { // 更新数据
                    $arr = array(
                        'request_id' => '', 
                        'batch_no' => '', 
                        'update_time' => date('YmdHis'));
                    $res_code = M('tmember_info')->where("id={$v['id']}")->save(
                        $arr);
                    if ($res_code === false) {
                        $erNum ++;
                        log::write(
                            "粉丝权益撤销失败::phone:{$v['phone_no']},备注:码已撤销,数据更新失败");
                    }
                }
            } else { // 未发过的只更新数据
                $arr = array(
                    'batch_no' => '', 
                    'update_time' => date('YmdHis'));
                $res_code = M('tmember_info')->where("id={$v['id']}")->save(
                    $arr);
                if ($res_code === false) {
                    $erNum ++;
                    log::write("粉丝权益撤销失败::phone:{$v['phone_no']},备注:数据更新失败");
                }
            }
        }
        unset($memberData);
        if ($erNum > 0) {
            $this->error($erNum . '个粉丝权益撤销未成功,停用失败');
        } else {
            $arr = array(
                'status' => '3');
            $result = M('tmember_batch')->where(
                "node_id='{$this->nodeId}' AND member_level='{$memberLevel}'")->save(
                $arr);
            if ($result === false) {
                $this->error('系统出错,停用失败');
            }
            $this->success('停用成功');
        }
    }

    /**
     * 特权添加和更新
     */
    public function privilegeEdit($batchNo, $printInfo) {
        // 支撑请求修改打印文本
        $req_array = array(
            'SetGoodsInfoReq' => array(
                'InterfaceAccount' => 'g', 
                'InterfacePassword' => 'g', 
                'BatchNo' => $batchNo, 
                'PrintControl' => '1', 
                'StartTime' => date('YmdHis'), 
                'EndTime' => '20301231235959', 
                'PrintText' => $printInfo));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $resp_array = $RemoteRequest->SetGoodsInfoReq($req_array);
        $ret_msg = $resp_array['SetGoodsInfoReq']['Status'];
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
             $ret_msg['StatusCode'] != '0001')) {
            return false;
        }
        return true;
    }

    /*
     * 获取活动号
     */
    public function getBatch_no($batchName, $nodePosGroup, $trans = 0) {
        // 通知支撑
        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
        $req_array = array(
            'ActivityCreateReq' => array(
                'SystemID' => C('ISS_SYSTEM_ID'), 
                'ISSPID' => $this->nodeId, 
                'TransactionID' => $TransactionID, 
                'ActivityInfo' => array(
                    'CustomNo' => '', 
                    'ActivityName' => $batchName, 
                    'ActivityShortName' => $batchName, 
                    'UseRangeID' => '', 
                    'BeginTime' => date('YmdHis'), 
                    'EndTime' => '20301231235959', 
                    'UseRangeID' => $nodePosGroup), 
                'VerifyMode' => array(
                    'UseTimesLimit' => 1, 
                    'UseAmtLimit' => 0), 
                'GoodsInfo' => array(
                    'GoodsName' => $batchName . uniqid(), 
                    'GoodsShortName' => $batchName), 
                'DefaultParam' => array(
                    'PasswordTryTimes' => 3, 
                    'PasswordType' => '', 
                    'ServiceType' => '01')));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $resp_array = $RemoteRequest->requestIssServ($req_array);
        
        $ret_msg = $resp_array['ActivityCreateRes']['Status'];
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
             $ret_msg['StatusCode'] != '0001')) {
            if ($trans)
                M()->rollback();
            $this->error($ret_msg['StatusText']);
        } else {
            return $resp_array['ActivityCreateRes']['ActivityID'];
        }
    }
}

