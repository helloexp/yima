<?php

/*
 * 该model是卡券业务逻辑用的,o2o的业务逻辑不要往这里放
 */
class GoodsModel extends Model {

    protected $tableName = 'tgoods_info';
    public $error;

    public function _initialize() {
        import('@.Vendor.CommonConst') or die('include file fail.');
    }

    /**
     * 卡券库存扣减
     *
     * @param $goods_id
     * @param $num 剩余库存数量 正数为减库存 负数增加库存 对应tgoods_info remain_num
     * @param $relation_id 库存变动日志记录表relation_id字段
     * @param $opt_type 库存变动日志记录表relation_id字段
     * @param $userId 操作员id
     * @param $opt_desc 库存变动日志记录表relation_id字段
     * @param $total_num 总库存数量 对应tgoods_info
     *            storage_num(比如为卡券补充库存时既要增加remain_num,也要增加storage_num)
     * @param string $is_new
     * @return boolean
     */
    public function storagenum_reduc($goods_id, $num, $relation_id, $opt_type,
        $opt_desc = '', $total_num = '0', $is_new = false ,$user_id = null) {
        if ($num == 0)
            return true;
            
            // M()->execute('SAVEPOINT storagenum_reduc');
        $map = array(
            'goods_id' => $goods_id);
        $goodsInfo = $this->where($map)->lock(true)->find();
        if (! $goodsInfo) {
            $this->error = '未找到商品信息';
            return false;
        }
        if ($goodsInfo['storage_type'] == '0')
            return true;
        if (true === $is_new) { // 新增判断商品上架
            $remain_num = $num;
            $total_num = $num - $goodsInfo['remain_num'];
            $storage_num = $goodsInfo['storage_num'] + $total_num;
        } else {
            if ($num > 0 && $goodsInfo['remain_num'] < $num) {
                $this->error = '库存不足';
                return false;
            }
            $storage_num = $goodsInfo['storage_num'] - $total_num;
            $remain_num = $goodsInfo['remain_num'] - $num;
        }
        $udata = array(
            'storage_num' => $storage_num, 
            'remain_num' => $remain_num);
        $flag = $this->where($map)->save($udata);
        if ($flag === false) {
            $this->error = '商品库存扣减失败.！';
            // M()->execute('ROLLBACK TO SAVEPOINT storagenum_reduc');
            return false;
        }
        
        $data = array(
            'node_id' => $goodsInfo['node_id'], 
            'goods_id' => $goodsInfo['id'], 
            'change_num' => abs($num), 
            'pre_num' => $goodsInfo['remain_num'], 
            'current_num' => $remain_num, 
            'opt_type' => $opt_type, 
            'opt_desc' => $opt_desc, 
            'relation_id' => $relation_id,
        	'user_id' => $user_id,
            'add_time' => date('YmdHis'));
        $flag = M('tgoods_storage_trace')->add($data);
        if ($flag === false) {
            $this->error = '库存扣减流水入库失败！';
            // M()->execute('ROLLBACK TO SAVEPOINT storagenum_reduc');
            return false;
        }
        
        return true;
    }
        
    /**
     * 获取库存变化调用类型
     *
     * @param $type 获取的类型 格式 $type='0,1,2,3,....'
     * @return array
     */
    public function getStorageTraceOptType($type) {
        $optType = array(
            '0' => '营销活动调用', 
            '1' => '活动释放', 
            '2' => '商品下架', 
            '3' => '库存增加', 
            '4' => '卡券采购', 
            '5' => '采购需求主动供货', 
            '6' => '奖品增加库存', 
            '7' => '奖品扣减库存', 
            '8' => '礼品派发（批量）', 
            '9' => '粉丝回馈', 
            '10' => '礼品派发(单条)', 
            '11' => '小店商品上架', 
            '12' => '小店商品库存调整', 
            '13' => '微信卡券调用', 
            '13' => '主动分销', 
            '14' => '卡券交易大厅采购', 
            '15' => '话费Q币采购退回', 
            '16' => '发布到个人',
            '17' => '发布到旺财app',
            '18' => '库存释放',
        	'19' => '融e购',
        	'20' => '奖品库存回退'
        );
        if (! is_null($type)) {
            $type = array_flip(explode(',', $type));
            $optType = array_intersect_key($optType, $type);
        }
        return $optType;
    }

    /**
     * 多乐互动，奖品数量调整 需要在变更tbatch_info之前调用
     */
    public function adjust_batch_storagenum($bid, $new_count, $relation_id, 
        $opt_type = null, $opt_desc = '') {
        $batchInfo = M('tbatch_info')->where("id = '$bid'")->lock(true)->find();
        if (! $batchInfo) {
            $this->error = '未找到活动信息';
            return false;
        }
        if ($batchInfo['storage_num'] == - 1)
            return true;
        if ($batchInfo['storage_num'] == $new_count)
            return true;
        
        $send_num = $batchInfo['storage_num'] - $batchInfo['remain_num'];
        if ($new_count < $send_num) {
            $this->error = '新的库存数量小于已使用数！';
            return false;
        }
        
        $count = $new_count - $batchInfo['storage_num'];
        $opt_type = $opt_type !== null ? $opt_type : ($count > 0 ? '6' : '7');
        return $this->storagenum_reduc($batchInfo['goods_id'], $count, 
            $relation_id ? $relation_id : $bid, $opt_type, $opt_desc);
    }

    /**
     * 卡券验码天数转换成日期
     *
     * @param $type 时间类型 0-日期类型 1-天数类型
     * @param $dateInfo
     */
    public function dayToDate($dateInfo, $type) {
        if ($type == 0)
            return $dateInfo;
        return date('YmdHis', time() + $dateInfo * 24 * 3600);
    }

    /**
     * 获取某个商户各个类型卡券数量
     *
     * @param $nodeId 商户号
     * @param $source 卡券来源 格式:$source = '0,1,2,3'
     * @param $where 附加查询条件
     * @return array 一维数组
     */
    public function getGoodsNum($nodeId, $source = null, $where = array()) {
        $typeArr = array(
            '0' => '0', 
            '1' => '0', 
            '2' => '0', 
            '3' => '0', 
            '6' => '0', 
            '7' => '0', 
            '8' => '0', 
            '9' => '0', 
            '10' => '0', 
            '11' => '0', 
            '12' => '0',
            '15' => '0',
            '22' => '0'
            );
        $map = array(
            'node_id' => $nodeId);
        if (! is_null($source)) {
            $map['source'] = array(
                'in', 
                $source);
        }
        $map = array_merge($map, $where);
        $list = $this->field('goods_type,count(*) as num')->where($map)->group(
            'goods_type')->select();
        $resultArr = array();
        foreach ($list as $v) {
            $resultArr[$v['goods_type']] = $v['num'];
        }
        return ($resultArr + $typeArr);
    }

    /**
     * 获取卡券类型名称
     *
     * @param $type 获取的类型 格式 $type='0,1,3'
     */
    public function getGoodsType($type = null) {
        $goodsType = array(
            '0' => '优惠券', 
            '1' => '代金券', 
            '2' => '提领券', 
            '3' => '折扣券', 
            '6' => '商品销售', 
            '7' => '话费', 
            '8' => 'Q币', 
            '9' => '翼码免费卡券', 
            '10' => '积分', 
            '11' => '哈格达斯卡券', 
            '12' => '定额红包', 
            '15' => '流量包',
            '22' => '微信红包'//改类型只有翼码自己机构存在用于卡券商城的微信红包
        );
        if (! is_null($type)) {
            $type = array_flip(explode(',', $type));
            $goodsType = array_intersect_key($goodsType, $type);
        }
        return $goodsType;
    }

    /**
     * 获取卡券来源名称
     *
     * @param $type 获取的类型 格式 $type='0,1,3'
     */
    public function getGoodsSource($type) {
        $sourceType = array(
            '0' => '自建', 
            '1' => '采购', 
            '4' => '分销', 
            '5' => '萨湾代理', 
            '6' => '微信红包',
            '7' => '翼码代理微信红包'
        );
        if (! is_null($type)) {
            $type = array_flip(explode(',', $type));
            $sourceType = array_intersect_key($sourceType, $type);
        }
        return $sourceType;
    }

    /**
     * 获取某个商户某类卡券的发码,验码,撤销量
     *
     * @param $nodeId 商户号
     * @param $goodsType 卡券类型
     * @param $source 来源 格式:$source='0,1,2'
     * @param $sTime $eTime 日期区间
     * @return array 一维数组
     */
    public function getGoodsCodes($nodeId, $goodsType, $source = null, $sTime = null, 
        $eTime = null) {
        $map = array(
            'i.node_id' => $nodeId, 
            'i.goods_type' => $goodsType);
        if (! is_null($source))
            $map['i.source'] = array(
                'in', 
                $source);
        if (! is_null($sTime))
            $map[' c.trans_date'] = array(
                'egt', 
                $sTime);
        if (! is_null($eTime))
            $map['c.trans_date'] = array(
                'elt', 
                $eTime);
        $codeList = $this->table('tgoods_info i')
            ->field(
            'ifnull(sum(send_num),0) as send_num,
						 		  ifnull(sum(send_amt),0) as send_amt,
						 		  ifnull(sum(verify_num),0) as verify_num,
						 		  ifnull(sum(verify_amt),0) as verify_amt,
						 		  ifnull(sum(cancel_num),0) as cancel_num,
						 		  ifnull(sum(cancel_amt),0) as cancel_amt')
            ->join('tpos_day_count c ON i.goods_id = c.goods_id')
            ->where($map)
            ->select();
        return $codeList[0];
    }

    /**
     * 获取卡券门店信息
     *
     * @param $goodsId
     * @param bloor $listStore //是否显示全门店
     * @param string $nodeId 取得子门店信息
     * @param string $type 获取门店类型
     * @return 1表示全门店,数组表示子门店信息
     */
    public function getGoodsShop($goodsId, $listStore = false, $nodeId, $type = '') {
        $goodsInfo = $this->where("goods_id='{$goodsId}'")->find();
        if (! $goodsInfo) {
            $this->error = '未找到该卡券';
            return false;
        }
        $storeList = array();
        switch ($goodsInfo['pos_group_type']) {
            case '1': // 全门店
                $storeList = '1';
                $where = "node_id in ({$nodeId})";
                if ($type == 'noOnline') {
                    $where .= " AND type <> '3' ";
                }
                if (true === $listStore) {
                    $storeList = $this->table('tstore_info')->where($where)->order(
                        'add_time')->select();
                }
                break;
            case '2': // 子门店
                $where = "g.group_id = '{$goodsInfo['pos_group']}' and t.node_id in ({$nodeId}) and t.status = 0";
                if ($type == 'noOnline') {
                    $where .= " AND t.type <> '3' ";
                }
                $storeList = $this->table('tgroup_pos_relation g')
                    ->join('tstore_info t ON g.store_id=t.store_id')
                    ->where($where)
                    ->field('t.*')
                    ->group('g.store_id')
                    ->order('t.add_time')
                    ->select();
                break;
            default:
                $this->error = '未知门店类型';
                return false;
        }
        return $storeList;
    }

    /**
     * 获取卡券被调用状态
     *
     * @param $outType 调用目标类型 1-是否发送过app 2-是否上架过交易大厅
     * @param $goodsId
     * @return boolean
     */
    public function getGoodsOutStatus($goodsId, $outType) {
        $outStatus = false;
        switch ($outType) {
            case '1':
                $result = $this->getUseInfo($goodsId, '4');
                if ($result > 0)
                    $outStatus = true;
                break;
            case '2':
                $result = M('thall_goods')->where("goods_id='{$goodsId}'")->count();
                if ($result > 0)
                    $outStatus = true;
                break;
        }
        
        return $outStatus;
    }

    /**
     * 获取卡券被其他模块使用库存
     *
     * @param $goodsId
     * @param $type 1-活动奖品 2-旺财小店 3-发布到个人 4-APP 5-微信卡券
     */
    public function getUseInfo($goodsId, $type) {
        $useNum = '0';
        switch ($type) {
            case '1':
                $map = array(
                    'b.goods_id' => $goodsId, 
                    'b.storage_num' => array(
                        'gt', 
                        '0'), 
                    'm.batch_type' => array(
                        'not in', 
                        '31,1007,1006,40'));
                $result = M()->table("tbatch_info b")->field("sum(b.storage_num) as num")->join(
                    "tmarketing_info m ON b.m_id=m.id")->where($map)->find();
                $useNum = $result['num'];
                break;
            case '2':
                $map = array(
                    'b.goods_id' => $goodsId, 
                    'b.storage_num' => array(
                        'gt', 
                        '0'), 
                    'm.batch_type' => '31');
                $result = M()->table("tbatch_info b")->field("sum(b.storage_num) as num")->join(
                    "tmarketing_info m ON b.m_id=m.id")->where($map)->find();
                $useNum = $result['num'];
                break;
            case '3':
                $map = array(
                    'b.goods_id' => $goodsId, 
                    'b.storage_num' => array(
                        'gt', 
                        '0'), 
                    'm.batch_type' => '1007');
                $result = M()->table("tbatch_info b")->field("sum(b.storage_num) as num")->join(
                    "tmarketing_info m ON b.m_id=m.id")->where($map)->find();
                $useNum = $result['num'];
                break;
            case '4': // app只允许发布一次
                $map = array(
                    'b.goods_id' => $goodsId, 
                    'b.storage_num' => array(
                        'gt', 
                        '0'), 
                    'm.batch_type' => '1006');
                $result = M()->table("tbatch_info b")->field("b.storage_num as num")->join(
                    "tmarketing_info m ON b.m_id=m.id")->where($map)->find();
                $useNum = $result['num'];
                break;
            case '5':
                $map = array(
                    'b.goods_id' => $goodsId, 
                    'b.storage_num' => array(
                        'gt', 
                        '0'), 
                    'm.batch_type' => '40');
                $result = M()->table("tbatch_info b")->field("sum(b.storage_num) as num")->join(
                    "tmarketing_info m ON b.m_id=m.id")->where($map)->find();
                $useNum = $result['num'];
                break;
        }
        return empty($useNum) ? '0' : $useNum;
    }
    
    // 日期格式'Y-m-d'
    public function getTopVerifyGoods($nodeId, $sTime = null, $eTime = null, 
        $source = null) {
        $map = array(
            'g.node_id' => $nodeId, 
            'g.goods_type' => array(
                'in', 
                '0,1,2,3'), 
            'g.source' => array(
                'in', 
                '0,1,2,4'), 
            'p.trans_date' => array(
                array(
                    'egt', 
                    $sTime), 
                array(
                    'elt', 
                    $eTime), 
                'and'));
        if (! is_null($source))
            $map['g.source'] = $source;
            
            // 前5验证率最高的卡券
        $codeEfficiencyList = M()->table("tgoods_info g")->field(
            'g.goods_name,ifnull(SUM(p.send_num),0) as send_num,ifnull(SUM(p.verify_num),0) as verify_num,ifnull(truncate((SUM(p.verify_num)/SUM(p.send_num))*100,2),0) as codeEfficiency')
            ->join("tpos_day_count p ON g.batch_no = p.batch_no")
            ->join("tnode_info t ON g.node_id = t.`node_id`")
            ->where($map)
            ->group('g.goods_id')
            ->having("SUM(p.verify_num)/SUM(p.send_num) <= 1")
            ->order('codeEfficiency DESC,send_num DESC')
            ->limit('5')
            ->select();
        return $codeEfficiencyList;
    }

    /**
     * 支撑活动smilId创建和修改
     *
     * @param string $imageName 图片路径(不包括C('UPLOAD')目录)
     * @param string $name 图片描述
     * @param string $nodeId 商户号
     * @param string $smilId 修改提供原smilId
     * @return string SmilId
     */
    public function getSmil($imageName, $name, $nodeId, $smilId = "") {
        $imagePath = realpath(C('UPLOAD'));
        $zipFileName = date('YmdHis') . '.zip';
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
        if (stripos($imageName, 'http://static.wangcaio2o.com/') === 0) { //图片库的$imageName都是写死的绝对地址，需要特殊处理
            $imageName = str_replace('http://static.wangcaio2o.com/Home/Upload/', '', $imageName);
        }
        $imageUrl = $imagePath . '/' . $imageName;
        if (! is_file($imageUrl)) {
            $this->error = '未找到图片';
            return false;
        }
        // 缩放图片大小要小于60k
        import('ORG.Util.Image');
        $smilUrl = Image::thumb($imageUrl, 
            dirname($imageUrl) . '/smi_' . basename($imageUrl), '', 150, 150, 
            true);
        if (! $smilUrl) {
            $this->error = '图片压缩失败';
            return false;
        }
        $imageUrl = $smilUrl;
        $smil_cfg = create_smil_cfg($imageUrl);
        if ($smil_cfg === false) {
            $this->error = '创建smil_cfg失败';
            return false;
        }
        // $defultSmil = $path.'/template/default.smil';
        // $imagecoImage = $path.'/template/imageco_image_b.jpg';
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
                'ISSPID' => $nodeId, 
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
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000' && $ret_msg['StatusCode'] != '0001')) {
            log_write("获取SmilId失败 原因：{$ret_msg['StatusText']}");
            $this->error = "获取SmilId失败 原因：{$ret_msg['StatusText']}";
            return false;
        }
        return $resp_array['SmilAddEditRes']['SmilId'];
    }

    /**
     * 支撑创建终端组
     *
     * @param $groupType 终端类型 0-全商户门店 1-指定门店
     * @param $clientId 旺号
     * @param $posgroupSeq node_info表中posgroup_seq
     *            {传入之前先M('tnode_info')->where("node_id='{$this->nodeId}'")->setInc('posgroup_seq');//posgroup_seq
     *            +1,放在事物外面}
     * @param $dataList 终端列表
     * @return 终端组号
     */
    public function zcCreateGroup($groupType, $clientId, $posgroupSeq, $dataList, 
        $nodeId) {
        $req_array = array(
            'CreatePosGroupReq' => array(
                'NodeId' => $nodeId, 
                'GroupType' => $groupType, 
                'GroupName' => str_pad($clientId, 6, '0', STR_PAD_LEFT) .
                     $posgroupSeq, 
                    'GroupDesc' => '', 
                    'DataList' => $dataList));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $resp_array = $RemoteRequest->requestIssForImageco($req_array);
        $ret_msg = $resp_array['CreatePosGroupRes']['Status'];
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
             $ret_msg['StatusCode'] != '0001')) {
            $this->error = '创建门店失败:' . $ret_msg['StatusText'];
            return false;
        }
        $groupId = $resp_array['CreatePosGroupRes']['GroupID'];
        $groupInfo = array(
            'groupId' => $resp_array['CreatePosGroupRes']['GroupID'], 
            'groupName' => $req_array['CreatePosGroupReq']['GroupName']);
        return $groupInfo;
    }

    /**
     * 支撑创建合约
     *
     * @param $data 报文参数(一维数组) array( 'shopNodeId' => '', //合作商商户号-不可为空
     *            'bussNodeId' => '', //业务商商户号-不可为空 'treatyName' => '',
     *            //合约名称-不可为空 'treatyShortName' => '', //合约简称-不可为空 'groupId' =>
     *            '', //终端组号-不可为空 'salePrice' => null, //商品零售价-可为空 'pactPrice'
     *            => null, //合约价格-可为空 'printPriceFlag' =>
     *            null,//小票金额打印方式-0:按协议价格 1:按前端价格 2:按发票金额 可为空 'printControl' =>
     *            null, //打印控制(非0时PrintText不可为空)-0-放开 1-不放开 2-部分放开 可为空
     *            'printText' => null, //打印文本(printControl非0时不可为空)
     *            'goodsCustmomNo' => null,//自定义商品id-可为空 )
     * @return 合约号
     */
    public function zcCreateTreaty($data) {
        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 流水号
        $req_array = array(
            'CreateTreatyReq' => array(
                'SystemID' => C('ISS_SYSTEM_ID'), 
                'RequestSeq' => $TransactionID, 
                'ShopNodeId' => $data['shopNodeId'], 
                'BussNodeId' => $data['bussNodeId'], 
                'TreatyName' => $data['treatyName'], 
                'TreatyShortName' => $data['treatyShortName'], 
                'StartTime' => date('YmdHis'), 
                'EndTime' => '20301231235959', 
                'PrintText' => $data['printText'], 
                'CustmomNo' => $data['custmomNo'], 
                'GroupId' => $data['groupId'], 
                'GoodsName' => $data['treatyName'], 
                'GoodsShortName' => $data['treatyShortName'], 
                // 'GoodsCustmomNo' => $data['goodsCustmomNo'],
                'SalePrice' => $data['salePrice'], 
                'PactPrice' => $data['pactPrice'], 
                'PrintPriceFlag' => is_null($data['printPriceFlag']) ? '1' : $data['printPriceFlag'], 
                'PrintControl' => $data['printControl']));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $resp_array = $RemoteRequest->requestIssForImageco($req_array);
        $ret_msg = $resp_array['CreateTreatyRes']['Status'];
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
             $ret_msg['StatusCode'] != '0001')) {
            $this->error = '创建合约失败:' . $ret_msg['StatusText'];
            return false;
        }
        $treatyId = $resp_array['CreateTreatyRes']['TreatyId']; // 合约id
        return $treatyId;
    }

    /**
     * 支撑创建活动
     *
     * @param $data 报文参数(一维数组) array( 'isspid' => '', //业务商机构号-不能为空 'relationId'
     *            => '', //合作商户机构号-不能为空 'batchName' => '', //活动名称-不可为空
     *            'batchShortName' => '', //活动简称-不可为空 'groupId' => '',
     *            //终端组号-不可为空 'validateType' => '', //验证类型- 1:金额验证 0:次数验证 不可为空
     *            'serviceType' => null, //活动码类型-可为空 'onlineVerify' => null,
     *            //特殊标记-默认为'',01-线上提领 'smilId' => null, //smilid-可为空 'treatyId'
     *            => null, //合约号-可为空 'printText' => null, //打印文本-可为空 )
     * @return 活动信息
     */
    public function zcCreateBatch($data) {
        $transactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
        if (is_null($data['treatyId'])) {
            $goodsInfo = array(
                'GoodsName' => $data['batchName'], 
                'GoodsShortName' => $data['batchShortName']);
        } else {
            $goodsInfo = array(
                'pGoodsId' => $data['treatyId']);
        }
        // 请求参数
        $req_array = array(
            'ActivityCreateReq' => array(
                'SystemID' => C('ISS_SYSTEM_ID'), 
                'ISSPID' => $data['isspid'], 
                'RelationID' => $data['relationId'], 
                'TransactionID' => $transactionID, 
                'SmilID' => $data['smilId'], 
                'ActivityInfo' => array(
                    'CustomNo' => '', 
                    'ActivityName' => $data['batchName'], 
                    'ActivityShortName' => $data['batchShortName'], 
                    'ActivityServiceType' => is_null($data['serviceType']) ? '00' : $data['serviceType'], 
                    'BeginTime' => date('YmdHis'), 
                    'EndTime' => '20301231235959', 
                    'UseRangeID' => $data['groupId'], 
                    'SpecialTag' => is_null($data['onlineVerify']) ? '' : $data['onlineVerify']), 
                'VerifyMode' => array(
                    'UseTimesLimit' => $data['validateType'] == 1 ? 0 : 1, 
                    'UseAmtLimit' => $data['validateType'] == 1 ? 1 : 0), 
                'GoodsInfo' => $goodsInfo, 
                'DefaultParam' => array(
                    'PasswordTryTimes' => 3, 
                    'PasswordType' => '', 
                    'PrintText' => $data['printText'])));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $resp_array = $RemoteRequest->requestIssForImageco($req_array);
        $ret_msg = $resp_array['ActivityCreateRes']['Status'];
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
             $ret_msg['StatusCode'] != '0001')) {
            $this->error = "活动创建失败:{$ret_msg['StatusText']}";
            return false;
        }
        $batchNo = $resp_array['ActivityCreateRes']['Info']['ActivityID'];
        $pGoodsId = $resp_array['ActivityCreateRes']['Info']['pGoodsId'];
        return array(
            'batchNo' => $batchNo, 
            'pGoodsId' => $pGoodsId);
    }

    /**
     * 支撑修改活动接口
     *
     * @param unknown $nodeId 商户号
     * @param unknown $batchNo 活动号
     * @param unknown $batchName 活动名称
     * @param unknown $batchShortName 活动简称
     * @param unknown $beginTime 活动开始时间
     * @param unknown $printText 合约打印内容
     * @param string $smilId smilid
     * @param string $specialTag 是否支持线上门店 '0'-不支持 '01'-支持
     */
    public function zcModifyBatch($nodeId, $batchNo, $batchName, $batchShortName, 
        $beginTime, $printText, $smilId = null, $specialTag = '') {
        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
                                                                   // 请求参数
        $req_array = array(
            'ActivityModifyReq' => array(
                'SystemID' => C('ISS_SYSTEM_ID'), 
                'ISSPID' => $nodeId, 
                'TransactionID' => $TransactionID, 
                'SmilID' => $smilId, 
                'ActivityID' => $batchNo, 
                'ActivityStatus' => '0', 
                'ActivityInfo' => array(
                    'ActivityName' => $batchName, 
                    'ActivityShortName' => $batchShortName, 
                    'BeginTime' => $beginTime, 
                    'EndTime' => '20301231235959'), 
                'PGoodsInfo' => array(
                    'pGoodsPrintText' => $printText), 
                'DefaultParam' => array(
                    'PrintText' => $printText), 
                'SpecialTag' => $specialTag));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $resp_array = $RemoteRequest->requestIssServ($req_array);
        $ret_msg = $resp_array['ActivityModifyReq']['Status'];
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
             $ret_msg['StatusCode'] != '0001')) {
            $this->error = "活动修改失败:{$ret_msg['StatusText']}";
            return false;
        }
        return true;
    }

    /**
     * 支撑修改终端组
     *
     * @param $nodeId
     * @param $pGoodsId 旧合约号
     * @param $operateFlag 操作标识 1-增加 2-修改 3-删除 4-变为全商户
     * @param $storeList 终端号
     *
     */
    public function zcModifyStore($nodeId, $pGoodsId, $operateFlag, 
        $storeList = '') {
        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
                                                                   // 请求参数
        $req_array = array(
            'PosGroupModifyReq' => array(
                'SystemID' => C('ISS_SYSTEM_ID'), 
                'NodeID' => $nodeId, 
                'TransactionID' => $TransactionID, 
                'pGoodsId' => $pGoodsId, 
                'StroreIDList' => $storeList, 
                'OperateFlag' => $operateFlag));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $resp_array = $RemoteRequest->requestIssForImageco($req_array);
        $ret_msg = $resp_array['PosGroupModifyRes']['Status'];
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
             $ret_msg['StatusCode'] != '0001')) {
            $this->error = "门店修改失败:{$ret_msg['StatusText']}";
            return false;
        }
        return $resp_array['PosGroupModifyRes']['GroupId'];
    }

    /**
     * 营帐账户冻结接口
     *
     * @param $transactionId 交易流水号
     * @param $clientId 用户旺号
     * @param $accountType 账户类型 1-Q币 2-话费
     * @param $transType 交易类型 1-购买 2-使用 3-退订 4-冲正
     * @param $transAmt 交易金额
     * @return 成功失败状态
     */
    public function yzFreezeAccount($transactionId, $clientId, $accountType, 
        $transType, $transAmt) {
        $yz_array = array(
            'FreezeAccountReq' => array(
                'SystemID' => C('YZ_SYSTEM_ID'), 
                'TransactionID' => $transactionId, 
                'ClientID' => $clientId, 
                'AccountType' => $accountType, 
                'TransType' => $transType, 
                'TransAmt' => $transAmt));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $resp_array = $RemoteRequest->requestYzServ($yz_array);
        $ret_msg = $resp_array['Status'];
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
             $ret_msg['StatusCode'] != '0001')) {
            $this->error = $ret_msg['StatusText'];
            return false;
        }
        return true;
    }

    /**
     * Description of SkuService 取得门店处理的的datalist
     *
     * @param int $groupType 门店类型 array $posData 取得nodeid数组信息
     * @return array dataList
     * @author john_zeng
     */
    
    public function getDataList($groupType, $posData) {
        switch ($groupType) {
            case 1: // 全门店
                $nodeArr = array();
                foreach ($posData as $v) {
                    $nodeArr[] = $v['node_id'];
                }
                $dataList = implode(',', $nodeArr);
                break;
            case 2: // 子门店
                $posArr = array();
                foreach ($posData as $v) {
                    if (! is_null($v['pos_id'])) {
                        $posArr[] = $v['pos_id'];
                    }
                }
                if (! $posArr)
                    $this->error('未找到有效的验证终端');
                $dataList = implode(',', $posArr);
                break;
            default:
                $dataList = false;
        }
        return $dataList;
    }

    /**
     * Description of SkuService 添加门店关联信息信息
     *
     * @param string $groupName 门店信息 
     * @param int $nodeId 商户唯一标识 
     * @param int $groupType 门店类型 0  全商户, 1 子门店 
     * @param array $nodeList 商户标识组 
     * @param int $groupId 门店ID
     * @param bloor $isDetel 是否删除旧合约
     * 
     * 
     * @return array dataList
     * @author john_zeng
     */
    public function addPosRelation($groupName, $nodeId, $groupType, $posData, $groupId, $isDetel = false) {
        $returnInfo = true;
        $num = M('tpos_group')->where(array('group_id' => $groupId))->count();
        if ($num == '0') { // 不存在终端组去创建
            $groupData = array( // tpos_group
                'node_id' => $nodeId, 
                'group_id' => $groupId, 
                'group_name' => $groupName, 
                'group_type' => $groupType, 
                'status' => '0');
            $reqArray = array(
                    'CreatePosGroupReq' => array(
                    'NodeId' => $this->nodeId, 
                    'GroupType' => $sendGroupId, 
                    'GroupName' => str_pad($nodeInfo['client_id'], 6, '0', STR_PAD_LEFT) . $nodeInfo['posgroup_seq'], 
                    'GroupDesc' => '', 
                    'DataList' => $dataList));
            $result = M('tpos_group')->add($groupData);
            if (! $result) {
                $returnInfo = false;
                $this->error('终端数据创建失败');
            }
            switch ($groupType) { // tgroup_pos_relation
                case 0: // 全商户
                    foreach ($posData as $v) {
                        $tempData = array(
                            'group_id' => $groupId, 
                            'node_id' => $v['node_id']);
                        $result = M('tgroup_pos_relation')->add($tempData);
                        if (! $result) {
                            $returnInfo = false;
                            $this->error('终端数据创建失败');
                        }
                    }
                    break;
                case 1: // 终端型
                    foreach ($posData as $v) {
                        $tempData = array(
                            'group_id' => $groupId, 
                            'node_id' => $v['node_id'], 
                            'store_id' => $v['store_id'], 
                            'pos_id' => $v['pos_id']);
                        $result = M('tgroup_pos_relation')->add($tempData);
                        if (! $result) {
                            $returnInfo = false;
                            $this->error('终端数据创建失败');
                        }
                    }
                    break;
                default:
                    $returnInfo = false;
                    $this->error('商户类型不正确');
            }
            
            return $returnInfo;
        } else {
            if (true === $isDetel) {
                // 删除旧合约
                $result = M('tpos_group')->where(
                    "group_id='{$groupId}' AND node_id={$nodeId}")->delete();
                if ($result === false) {
                    $returnInfo = false;
                    $this->error('数据出错,旧合约删除失败01');
                }
                $result = M('tgroup_pos_relation')->where(
                    "group_id='{$groupId}' AND node_id={$nodeId}")->delete();
                if ($result === false) {
                    $returnInfo = false;
                    $this->error('数据出错,旧合约删除失败02');
                }
                $returnInfo = self::addPosRelation($groupName, $nodeId, 
                    $groupType, $posData, $groupId);
            }
            
            return $returnInfo;
        }
    }

    /**
     * Description of SkuService 取得门店信息
     *
     * @param int $sendType 门店类型 
     * @param string $nodeSql $this->nodeId返回的信息 
     * @param string $shopList 分店列表
     * 
     * @return array $getData
     * @author john_zeng
     */
    public function getPosData($sendType, $nodeSql, $shopList = '') {
        $getData = false;
        switch ($sendType) {
            case 1: // 全门店
                $getData = M()->query($nodeSql);
                if (! $getData)
                    $this->error('获取门店信息出错');
                break;
            case 2: // 子门店
                $shopList = explode(',', $shopList);
                if (! is_array($shopList) || empty($shopList))
                    $this->error('请选择验证门店');
                    // $shopstr = implode(',',array_unique($shopList));
                $where = array(
                    's.store_id' => array(
                        'in', 
                        array_unique($shopList)), 
                    'p.node_id' => array(
                        'exp', 
                        "in ({$nodeSql})"), 
                    's.pos_range' => array(
                        'gt', 
                        '0'));
                // 获取终端号
                $getData = M()->table('tstore_info s')
                    ->field('p.pos_id,p.store_id,p.node_id')
                    ->join(
                    'tpos_info p ON s.store_id=p.store_id AND s.node_id=p.node_id')
                    ->where($where)
                    ->select();
                if (! $getData)
                    $this->error('获取门店信息出错');
                break;
            default:
                break;
        }
        return $getData;
    }

    /**
     * 获得话费,Q币发码时的短信标题和内容
     *
     * @param $goodsId
     */
    public function getPqText($goodsId) {
        $smsText = array();
        $dataInfo = $this->field('goods_type,goods_amt')->where(
            "goods_id='{$goodsId}'")->find();
        switch ($dataInfo['goods_type']) {
            case '7': // 话费
                $smsText['title'] = $dataInfo['goods_amt'] . '元手机话费';
                $smsText['content'] = '您已获得' . $dataInfo['goods_amt'] .
                     '元手机话费，点击[#GET_URL]，提交待充值手机号，即可领取！领取截止时间：[#END_DATE]。';
                break;
            case '8': // Q币
                $smsText['title'] = $dataInfo['goods_amt'] . '元Q币';
                $smsText['content'] = '您已获得' . $dataInfo['goods_amt'] .
                     '元Q币，点击[#GET_URL]，提交待充值QQ号，即可领取！领取截止时间：[#END_DATE]。';
                break;
        }
        return $smsText;
    }

    /**
     * 查询可选的商品
     *
     * @param $nodeId 机构号
     * @param int $edit_id 编辑的活动id
     * @param string $limit, 为false时,结果返回count值
     * @param string $limit, 为false时,结果返回sku信息
     * @return array 查询结果 | int $limit为false时返回count值
     */
    public function getGoodsInfoForSelect($nodeId, $edit_id, $limit = false, $isSku = false, $store_id = '') {
        if (true === $isSku) {
            $where = array(
                'a.node_id' => $nodeId, 
                'a.m_id' => array(
                    'neq', 
                    $edit_id), 
                'T.status' => '0', 
                'g.is_sku' => '0');
        } else {
            $where = array(
                'a.node_id' => $nodeId, 
                'a.m_id' => array(
                    'neq', 
                    $edit_id), 
                'T.status' => '0');
        }

        if (false === $limit) {
            if ($nodeId == C('adb.node_id')) {
                $where['tags.store_id'] = $store_id;
                $mapcount = M()->table('tfb_adb_goods_store tags')
                ->join('tbatch_info T on T.id=tags.b_id')
                ->join('tecshop_goods_ex a ON a.node_id=T.node_id and T.id = a.b_id')
                ->join('tgoods_info g on T.goods_id =  g.goods_id')
                ->where($where)
                ->count();
            }else{
                $mapcount = M()->table('tecshop_goods_ex a')
                ->join('tbatch_info T ON a.node_id=T.node_id and T.id = a.b_id')
                ->join('tgoods_info g on T.goods_id =  g.goods_id') 
                ->where($where)
                ->count();
            }
            return $mapcount;
        }
        //爱蒂宝
        if ($nodeId == C('adb.node_id')) {
            $where['tags.store_id'] = $store_id;
            $list = M()->table('tfb_adb_goods_store tags')
                ->field(
                "a.m_id as id, g.goods_name, g.goods_image, g.storage_type,g.goods_id,T.remain_num, T.storage_num,
                     T.begin_time, T.end_time,T.batch_amt,a.label_id, g.is_sku")
                ->join('tbatch_info T on T.id=tags.b_id')
                ->join('tecshop_goods_ex a ON a.node_id=T.node_id and T.id = a.b_id')
                ->join('tgoods_info g on T.goods_id =  g.goods_id')
                ->where($where)
                ->order("a.id desc")
                ->limit($limit)
                ->select();
        } else {
            $list = M()->table('tecshop_goods_ex a')
                ->field(
                "a.m_id as id, g.goods_name, g.goods_image, g.storage_type,g.goods_id,T.remain_num, T.storage_num,
                     T.begin_time, T.end_time,T.batch_amt,a.label_id, g.is_sku")
                ->join('tbatch_info T ON a.node_id=T.node_id and t.id = a.b_id')
                ->join('tgoods_info g on T.goods_id =  g.goods_id')
                ->where($where)
                ->order("a.id desc")
                ->limit($limit)
                ->select();
        }
        if ($list) {
            foreach ($list as $key => &$v) {
                if ($v['goods_image'] != '')
                    $v['goods_image'] = $v['goods_image'];
                if ("1" === $v['is_sku']) {
                    // 创建sku信息
                    $skuObj = D('Sku', 'Service');
                    $skuObj->nodeId = $nodeId;
                    $v = $skuObj->makeGoodsListInfo($v, $v['id'], '');
                    if(false === $v){
                        unset($list[$key]);
                    }
                }
            }
        }
        return $list;
    }

    /**
     *
     * @param 机构号 $nodeId
     * @return string
     */
    public function createJfGoods($nodeId) {
        $map = array(
            'node_id' => $nodeId, 
            'status' => '0', 
            'goods_type' => CommonConst::GOODS_TYPE_JF);
        $resGoods = $this->where($map)->find();
        $integralName = M('tintegral_node_config')->where(
            array(
                'node_id' => $this->node_id))->getField('integral_name');
        if (! $integralName) { // 如果还没有在config表里插入记录,默认名字是"积分"
            $integralName = '积分';
        }
        if ($resGoods) {
            $this->where($map)->save(
                array(
                    'goods_name' => $integralName));
            return array(
                'code' => '0000', 
                'goods_id' => $resGoods['goods_id'], 
                'goods_name' => $integralName);
        }
        // $radom = date("YmdHis") . mt_rand(1000, 9999);
        $jfSeq = D('TsysSequence')->getNextSeq('jf_seq');
        if (! $jfSeq) {
            return array(
                'code' => '-1001', 
                'msg' => '获取积分序列号失败');
        }
        $jfSeq = str_pad($jfSeq, 10, '0', STR_PAD_LEFT);
        $goodsId = 'jf' . $jfSeq;
        // 积分的商品只有一条记录
        $jfGoodsInfo = array(
            'goods_id' => $goodsId, 
            'goods_name' => $integralName, 
            'node_id' => $nodeId, 
            'goods_type' => CommonConst::GOODS_TYPE_JF, 
            'status' => '0', 
            'storage_type' => '0',
            'pos_group_type'=>'1',
            'storage_num' => '-1', 
            'remain_num' => '-1', 
            'source' => '0', 
            'batch_no' => '0' . $jfSeq, 
            'goods_image' => './Home/Public/Label/Image/20151224/jfIcon.png');
        $re = $this->add($jfGoodsInfo);
        if (! $re) {
            return array(
                'code' => '-1002', 
                'msg' => '插入积分商品失败');
        }
        return array(
            'code' => '0000', 
            'goods_id' => $jfGoodsInfo['goods_id'], 
            'goods_name' => $jfGoodsInfo['goods_name']);
    }

    /**
     * 最近预览的商品
     *
     * @param type $id
     */
    function recentLookGoods($id) {
        // 最近预览商品
        $recentGoods = cookie('recentGoods') ? cookie('recentGoods') : array();
        if (! in_array($id, $recentGoods)) {
            array_unshift($recentGoods, $id);
            if (count($recentGoods) > 20) {
                $recentGoods = array_slice($recentGoods, 0, 20);
            }
            cookie('recentGoods', $recentGoods, 86400 * 365);
        }
    }

    /**
     * 获取电子券验证城市
     * @param unknown $goodsId
     * 
     */
    public function getGoodsVerifyCity($goodsId) {
        $goodsInfo = $this->where("goods_id='{$goodsId}'")->find();
        if ($goodsInfo) {
            // 查询城市
            $cityArr = array();
            if ($goodsInfo['pos_group_type'] == '1') { // 商户型终端
                                                       // 根据node_pos_group获取所有机构(包括下级机构)，根据机构到tstore_info去distinct
                                                       // city_code
                $nodelist = M()->query(
                    "SELECT distinct node_id FROM tgroup_pos_relation where group_id={$goodsInfo['node_pos_group']}");
                if (! empty($nodelist)) {
                    foreach ($nodelist as $nk => $nal) {
                        // 根据机构到tstroe_info查找城市
                        $citylist = M()->query(
                            "SELECT distinct t.city_code,c.city FROM tstore_info t left join tcity_code c on c.city_code=t.city_code where node_id='{$nal['node_id']}' and c.city_level='2'");
                        if (! empty($citylist)) {
                            foreach ($citylist as $cal) {
                                $cityArr[$cal['city_code']] = $cal['city'];
                            }
                        }
                    }
                }
            } else { // 终端型
                $storelist = M()->query(
                    "SELECT distinct store_id,node_id FROM tgroup_pos_relation where group_id={$goodsInfo['node_pos_group']} and node_id='{$goodsInfo['node_id']}'");
                // 根据storelist查找城市
                if ($storelist) {
                    foreach ($storelist as $sk => $sal) {
                        $citylist = M()->query(
                            "SELECT distinct t.city_code,c.city FROM tstore_info t left join tcity_code c on c.city_code=t.city_code where t.node_id='{$sal['node_id']}' and t.store_id={$sal['store_id']} and c.city_level='2'");
                        if (! empty($citylist)) {
                            foreach ($citylist as $ck => $cal) {
                                $cityArr[$cal['city_code']] = $cal['city'];
                            }
                        }
                    }
                }
            }
            return $cityArr;
        }
    }
    
    public function error($msg){
        $this->error = $msg;
    }
    
    public function getError(){
        return $this->error;
    }
    
    
    /**
     * 将结束活动中未用完的奖品库存回退
     * @param $goodsId
     */
    public function storageRollbackBatch($goodsId){
    	
    	
    }
    
    /**
     * 创建自建电子券
     * @param $nodeId
     * @param 
     * $data = array(请保持和数据库字段名称对应
     *     'goods_name'     => '',
     *     'storage_num'    => '',
     *     'goods_type'     => '',
     *     'goods_image'    => '',
     *     'goods_amt'      => '',
     *     'validate_type'  => '',
     *     'online_verify_flag' => '',
     *     'goods_discount' => '',
     *     'pos_group_type' => '',
     *     'openStores'     => '',
     *     'print_text'     => '',
     *     'client_id'      => '',
     *     'user_id'        => '',
     *     'is_saiWsan'     => ''
     * )
     * @return goodsId
     */
    public function addNumGoods($nodeId,$data){
    	node_log("首页+创建卡券");
    	// 商户信息
    	$nodeInfo = M('tnode_info')->field('node_name,client_id,node_service_hotline,posgroup_seq')
	        ->where("node_id='{$this->nodeId}'")
	    	->find();
    	//初始化变量
    	$error = '';
    	$onlineVerify = '0';//是否支持线上提领
    	$sData = array();
    	//数据验证
    	if (!check_str($data['goods_name'],array('null' => false,'maxlen_cn' => '24'), $error)) {
    		$this->error = "卡券名称{$error}";
    		return false;
    	}
    	// 卡券数量
    	if (! check_str($data['storage_num'],array('null' => false,'strtype' => 'int','minval' => '1','maxval' => '9999999'), $error)) {
    		$this->error = "卡券数量{$error}";
    		return false;
    	}
    	// 卡券价格信息
    	$type = $data['goods_type'];
    	switch ($type) {
    		case '0': // 优惠券
    			break;
    		case '1': // 代金卷
    			if (! check_str($data['goods_amt'],array('null' => false,'strtype' => 'number','minval' => '0'), $error)) {
    				$this->error = "减免金额{$error}";
    				return false;
    			}
    			$sData['goods_amt'] = $data['goods_amt'];
    			if (! check_str($data['validate_type'],array('null' => false,'strtype' => 'int','minval' => '0','maxval' => '1'), $error)) {
    				$this->error = "核销限制{$error}";
    				return false;
    			}
    			$sData['validate_type'] = $data['validate_type'];
    			break;
    		case '2': // 提领券
    			$onlineVerify = $data['online_verify_flag'];
    			// 是否支持线上门店
    			$onlineStoreInfo = M('tstore_info')->where(array('node_id' => $nodeId,'status' => 0,'type' => 3))->find();
    			if ($onlineVerify == 1 && empty($onlineStoreInfo)) {
    				$this->error = "您尚未开通线上门店";
    				return false;
    			}
    			break;
    		case '3': // 折扣券
    			$discount = $data['goods_discount'];
    			if (! check_str($discount,array('null' => false,'strtype' => 'int','minval' => '1','maxval' => '100'), $error)) {
    				$this->error("折扣信息{$error}");
    				return false;
    			}
    			$sData['goods_discount'] = $discount;
    			break;
    		default:
    			$this->error('未知的卡券类型');
    			return false;
    	}
    	// 门店处理
    	$shop = $data['pos_group_type']; //验证类型
    	$storeList = $data['openStores']; //选择的子门店
    	switch ($shop) {
    		case 1: // 全门店
    			$groupType = 0;
    			$nodeList = nodeIn($nodeId,false,false);
    			$dataList = implode(',',$nodeList);
    			break;
    		case 2: // 子门店
    			$groupType = 1;
    			// 获取所有终端列表
    			empty($storeList) ? $shopList = array() : $shopList = explode(',', $storeList);
    			if ($onlineVerify == '1') { // 线上门店处理
    				$shopList[] = $onlineStoreInfo['store_id'];
    			}
    			if (! is_array($shopList) || empty($shopList)){
    				$this->error = '请选择核验门店';
    				return false;
    			}
    			$where = array(
    				's.store_id' => array('in',array_unique($shopList)),
    				's.node_id' => array('exp',"in (".nodeIn($nodeId,false).")"),
    				's.pos_range' => array('gt','0')
    			);
    			// 获取终端号
    			$posData = M()->table('tstore_info s')
    				->field('p.pos_id,p.store_id,p.node_id')
    				->join('tpos_info p ON s.store_id=p.store_id AND s.node_id=p.node_id')
    				->where($where)
    				->select();
    			$posArr = array();
    			foreach ($posData as $v) {
    				if (! is_null($v['pos_id'])) {
    					$posArr[] = $v['pos_id'];
    				}
    			}
    			if (! $posArr){
    				$this->error = '未找到有效的验证终端';
    				return false;
    			}
    			$dataList = implode(',', $posArr);
    			break;
    		default:
    			$this->error = "请选择卡券可验证门店";
    			return false;
    	}
    	// 打印小票
    	$printText = $data['print_text'];
    	if ($onlineVerify == '1' && empty($storeList)) {
    		if (! check_str($printText,array('null' => true,'maxlen_cn' => '100'), $error)) {
    			$this->error = "打印小票内容{$error}";
    			return false;
    		}
    	} else {
    		if (! check_str($printText,array('null' => false,'maxlen_cn' => '100'), $error)) {
    			$this->error = "打印小票内容{$error}";
    			return false;
    		}
    	}
    	// 卡券图片
    	$goodImage = $data['goods_image'];
    	if (! check_str($goodImage,array('null' => false,'maxlen_cn' => '100'), $error)) {
    		$this->error = "请上传卡券图片";
    		return false;
    	}
    	// 支撑创建终端组
    	$groupInfo = $this->zcCreateGroup($groupType, $data['client_id'], uniqid(), $dataList, $nodeId);
    	$groupId = $groupInfo['groupId'];
    	// 插入终端组信息
    	$num = M('tpos_group')->where("group_id='{$groupId}' AND node_id='{$nodeId}'")->count();
    	if ($num == '0') { // 不存在终端组去创建
    		$groupData = array( // tpos_group
    			'node_id' => $nodeId,
    			'group_id' => $groupId,
    			'group_name' => $groupInfo['groupName'],
    			'group_type' => $groupType,
    			'status' => '0'
    		);
    		$result = M('tpos_group')->add($groupData);
    		if (! $result) {
    			$this->error = '终端数据创建失败';
    			return false;
    		}
    		switch ($groupType) { // tgroup_pos_relation
    			case 0: // 全商户
    				foreach ($nodeList as $v) {
    					$data_1 = array(
    						'group_id' => $groupId,
    						'node_id' => $v['node_id']
    					);
    					$result = M('tgroup_pos_relation')->add($data_1);
    					if (! $result) {
    						$this->error = '终端数据创建失败';
    						return false;
    					}
    				}
    				break;
    			case 1: // 终端型
    				foreach ($posData as $v) {
    					$data_2 = array(
    						'group_id' => $groupId,
    						'node_id' => $v['node_id'],
    						'store_id' => $v['store_id'],
    						'pos_id' => $v['pos_id']
    					);
    					$result = M('tgroup_pos_relation')->add($data_2);
    					if (! $result) {
    						$this->error = '终端数据创建失败';
    						return false;
    					}
    				}
    				break;
    		}
    	}
    	// 创建合约
    	$treatyData = array(
    		'shopNodeId' => $nodeId,
    		'BussNodeId' => $nodeId,
    		'TreatyName' => $data['goods_name'],
    		'TreatyShortName' => $data['goods_name'],
    		'groupId' => $groupId,
    		'goods_amt' => $data['goods_amt']
    	);
    	$treatyId = $this->zcCreateTreaty($treatyData); // 合约id
    	//获取smillid
    	$smilId = $this->getSmil($goodImage, $data['goods_name'],$nodeId);
    	//创建活动
    	$batchData = array(
    		'isspid' => $nodeId,
    		'relationId' => $nodeId,
    		'smilId' => $smilId,
    		'batchName' => $data['goods_name'],
    		'batchShortName' => $data['goods_name'],
    		'groupId' => $groupId,
    		'onlineVerify' => $onlineVerify,
    		'validateType' => $data['validate_type'],
    		'treatyId' => $treatyId,
    		'printText' => $printText
    	);
    	$batchInfo = $this->zcCreateBatch($batchData);
    	$batchNo = $batchInfo['batchNo'];
    	// tgoods_info数据添加
    	$goodsId = get_goods_id();
    	$sData['goods_id'] = $goodsId;
    	$sData['batch_no'] = $batchNo;
    	$sData['goods_name'] = $data['goods_name'];
    	$sData['goods_image'] = $goodImage;
    	$sData['node_id'] = $nodeId;
    	$sData['user_id'] = $data['user_id'];
    	$sData['goods_type'] = $type;
    	$sData['storage_type'] = 1;
    	$sData['storage_num'] = $data['storage_num'];
    	$sData['remain_num'] = $data['storage_num'];
    	$sData['print_text'] = $printText;
    	$sData['add_time'] = date('YmdHis');
    	$sData['p_goods_id'] = $treatyId;
    	$sData['pos_group'] = $groupId;
    	$sData['online_verify_flag'] = $onlineVerify == '1' ? 1 : 0;
    	$sData['pos_group_type'] = $shop;
    	// 撒湾非标
    	$isSaiWan = $data['is_saiWsan'];
    	if ($isSaiWan == '1' && $this->nodeId == C('withDraw.createNodeId')) {
    		$sData['source'] = '5';
    		$sData['purchase_node_id'] = C('withDraw.fromNodeId');
    	}
    	$id = $this->add($sData);
    	node_log("创建卡券，类型：" . $type . "，名称：" . $data['goods_name']);
    	if ($id){
    		return $goodsId;
    	}else{
    		$this->error = '系统出错,卡券创建失败';
    		return false;
    	}
    }
    
    /**
     * 发布卡券到电子交易大厅
     * @param $goodsId
     * @param $nodeId
     * @param 
     * $data = array(请保持和数据库字段名称对应
     *     'goods_cat'=>'',
     *     'batch_amt'=>'',
     *     'cg_name'=>'',
     *     'cg_mail'=>'',
     *     'cg_phone'=>'',
     *     'cg_mark'=>'',
     *     'batch_img'=>'',
     *     'use_rule'=>'',
     *     'invoice_type'=>'',
     *     'user_id'=>'',
     *     'client_id'=>'',
     * ) 
     */
    public function publishNumGoods($goodsId,$nodeId,$data){
    	$map = array(
    		'goods_id' => $goodsId,
    		'node_id'  => $nodeId,
    		'status'   => '0'
    	);
    	$goodsInfo = $this->field('goods_name,pos_group,pos_group_type')->where($map)->find();
    	if(empty($goodsInfo)){
    		$this->error = '无效的电子券或该电子券已停用或过期';
    		return false;
    	}
    	//每个电子券只能发布一次
    	if($this->getGoodsOutStatus($goodsId,2)){
    		$this->error = '该卡券已发布过电子交易大厅';
    		return false;
    	}
    	//数据验证
    	$error = '';
        //分类
    	if (! check_str($data['goods_cat'],array('null' => false), $error)) {
    		$this->error = "类目{$error}";
    		return false;
    	}
    	//价格
    	if (!check_str($data['batch_amt'],array('null' => false,'strtype' => 'number','minval' => '0.01'), $error)) {
    		$this->error = "卡券市场采购价{$error}";
    		return false;
    	}
    	//采购联系人
    	if (!check_str($data['cg_name'],array('null' => false,'maxlen_cn' => '24'), $error)) {
    		$this->error = "采购联系人{$error}";
    		return false;
    	}
    	//邮箱
    	if (!check_str($data['cg_mail'],array('null' => false), $error)) {
    		$this->error("采购联系人邮箱{$error}");
    		return false;
    	}
    	//电话
    	if (!check_str($data['cg_phone'],array('null' => false,'maxlen_cn' => '24'), $error)) {
    		$this->error = "采购联系人电话{$error}";
    		return false;
    	}
    	//采购条件
    	if (!check_str($data['cg_mark'],array('null' => true,'maxlen_cn' => '200'), $error)) {
    		$this->error("采购条件{$error}");
    		return false;
    	}
    	// 图片
    	if(empty($data['batch_img']) && !is_array('batch_img')){
    		$this->error = "请上传卡券图片";
    		return false;
    	}
    	// 描述
    	if (!check_str($data['use_rule'],array('null' => false), $error)) {
    		$this->error = "卡券描述{$error}";
    		return false;
    	}
    	//发票
    	if (!check_str($data['invoice_type'],array('null' => false,'strtype' => 'int','minval' => '0','maxval' => '2'), $error)) {
    		$this->error = "发票类型{$error}";
    		return false;
    	}
    	$sData = array(
    		'batch_short_name' => $goodsInfo['goods_name'],
    		'batch_name' => $goodsInfo['goods_name'],
    		'node_id' => $nodeId,
    		'user_id' => $data['user_id'],
    		'use_rule' => $data['use_rule'],
    		'batch_img' => json_encode($data['batch_img']),
    		'batch_amt' => $data['batch_amt'],
    		// 'begin_time' => $goodsData['begin_time'],
    		// 'end_time' => $endDate.'235959',
    		'add_time' => date('YmdHis'),
    		'node_pos_group' => $goodsInfo['pos_group'],
    		'node_pos_type' => $goodsInfo['pos_group_type'],
    		'batch_desc' => $data['use_rule'],
    		'goods_id' => $goodsId,
    		'cg_name' => $data['cg_name'],
    		'cg_mail' => $data['cg_mail'],
    		'cg_phone' => $data['cg_phone'],
    		'cg_mark' => $data['cg_mark'],
    		'goods_cat' => $data['goods_cat'],
    		'invoice_type' => $data['invoice_type']
    	);
    	//dump($sData);exit;
    	$batchId = M('thall_goods')->add($sData);
    	if ($batchId === false){
    		$this->error = '数据出错,发布失败';
    		return false;
    	}
    	// 邮件通知
    	$sendMailContent = "卡券名称:{$goodsInfo['goods_name']}<br/>商户名:" .get_node_info($nodeId, 'node_name') ."<br/>旺号:{$data['client_id']}";
    	$mailData = array(
    		'subject' => "卡券:{$goodsInfo['goods_name']}-大厅发布",
    		'content' => $sendMailContent,
    		'email' => C('PUBLISH_SEND_MAIL'));
    	send_mail($mailData);
    	node_log("卡券发布，发布类型：异业联盟中心，名称：" . $goodsInfo['goods_name']);
    	return true;
    	
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    

}
