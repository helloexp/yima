<?php

/**
 * 商品相关model
 *
 * @author : John zeng<zengc@imageco.com.cn> Date: 2016/03/08
 */
class GoodsInfoModel extends Model {

    protected $tableName = 'tgoods_info';
    protected $_pk = 'node_id';
    protected $_sk = 'id';
    public $error;
    public $nodeId = '';
    public $userId = '';
    public $groupId = '';     //支撑生成ID
    public $batchNo = '';     //支撑活动号
    public $treatyId = '';     //合约号
    public $goodsTypes = '6';    //商品类型 6为小店商品
    public $batchType = '31';    //营销类型 31为小店商品
    public $session_cart_name = '';   //购物车session
    public $session_ship_name = '';   // 商品收货地址SESISON名
    public $phoneNum = '';    //用户手机号码
    public $remoteRequestService = '';
    public $getPrice = '';    //获得规格商品价格
    public $isSku = '0';      //是规格商品
    private $goodsQrcodeDir;//二维码存放路径
    private $goodsModel = '';   //实例化商品模块
    private $getPosData = '';    //终端获取门店信息
    private $curdate = '';   //获取当前时间

    public function _initialize() {
        $this->remoteRequestService = D('RemoteRequest', 'Service');
        $this->goodsQrcodeDir = APP_PATH . 'Upload/goods_qrcode' . '/';
        $this->goodsModel = D('Goods');
        $this->curdate = date('Ymd');     //获取当前时间
    }
    /**
     * 限定条件格式化
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param boole  $boole  是否必验字段
     * @param string $type   字符类型
     * @param string $name   字段名称
     * @param array  $sendArray  需要传递的参数
     * @return array
     */
    public function _verifyInfo($boole = false, $name = '', $sendArray=array()){
        
        $retArray = array(
            'null' => $boole, 
            'name' => $name);
        foreach($sendArray as $key => $val){
            $retArray[$key] = $val;
        }

        return $retArray;        
    }
    
    /**
     * 商品信息更新
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param array  $goodsInfo  商品信息

     * @return array
     */
    public function addGoodsInfo($goodsInfo, $nodeIn){
        $goodsImg = isset($goodsInfo['goodsImg'][0]) ? $goodsInfo['goodsImg'][0] : '';
        if('' === $goodsImg){
            $this->error = "请上传商品图片";
            return false;
        }    
        //创建终端组
        $result = self::createSupport($goodsInfo, $nodeIn);
        if(false === $result){
            $this->error = '创建终端失败';
            return false;
        }
        //创建终端活动
        $result = self::createSupportActive($goodsInfo);
        if(false === $result){
            $this->error = '创建终端活动失败';
            return false;
        }
        //取得商品ID
        $goodsId = get_goods_id();
        // 创建tgoods_info数据
        $goodsData = array(
            'goods_id' => $goodsId, 
            'goods_name' => $goodsInfo['goodsName'], 
            'goods_desc' => $goodsInfo['goodsDesc'],    
            'goods_image' => isset($goodsInfo['goodsImg'][0]) ? $goodsInfo['goodsImg'][0] : '', 
            'node_id' => $this->nodeId, 
            'user_id' => $this->userId, 
            'pos_group' => $this->groupId, 
            'pos_group_type' => $goodsInfo['shop'], 
            'goods_type' => $this->goodsTypes,  // 商品销售类型
            'market_price' => $goodsInfo['marktPrice'], 
            'batch_no'=> $this->batchNo ,         //支撑活动号
            'storage_type' => 0, 
            'storage_num' => -1, 
            'remain_num' => -1, 
            'customer_no' => $goodsInfo['customerNo'], 
            'mms_title' => $goodsInfo['goodsName'], 
            'mms_text' => $goodsInfo['smsText'], 
            'sms_text' => $goodsInfo['smsText'], 
            'print_text' => $goodsInfo['printText'], 
            'p_goods_id' => $this->treatyId, 
            'validate_times' => 1, 
            'begin_time' => $goodsInfo['beginTime'], 
            'end_time' => $goodsInfo['endTime'], 
            'add_time' => date('YmdHis'), 
            'status' => '0', 
            'source' => '0', 
            'is_sku' => $goodsInfo['isSku'],
            'is_order' => $goodsInfo['isOrder'], 
            'config_data' => isset($configData) ? '' : json_encode($configData)); 
        //返回goodsId
        $result = $this->insertGoodsInfo($goodsData);
        if($result){
            return $goodsId;
        }else{
            $this->error = '添加商品基础信息失败';
            return false;
        }
    }
    
    /**
     * 创建终端活动
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param array  $newInfo   新增商品信息  
     * 
     * @return boolean 
     */
    public function createSupportActive($newInfo){
        //创建终端活动
        $reqArray = array(
                'ActivityCreateReq' => array(
                'SystemID' => C('ISS_SYSTEM_ID'), 
                'ISSPID' => $this->nodeId, 
                'RelationID' => $this->nodeId, 
                'TransactionID' => date("YmdHis") . mt_rand(100000, 999999),  // 请求单号
                'ActivityInfo' => array(
                'CustomNo' => '', 
                'ActivityName' => iconv("utf-8", "gbk", $newInfo['goodsName']), 
                'ActivityShortName' => iconv("utf-8", "gbk", $newInfo['goodsName']), 
                'BeginTime' => date('Ymd') . '000000', 
                'EndTime' => '20301231235959', 
                'UseRangeID' => $this->groupId), 
                'VerifyMode' => array(
                'UseTimesLimit' => 1, 
                'UseAmtLimit' => 0), 
                'GoodsInfo' => array(
                'GoodsName' => iconv("utf-8", "gbk", $newInfo['goodsName']), 
                'GoodsShortName' => iconv("utf-8", "gbk", $newInfo['goodsName'])), 
                'DefaultParam' => array(
                'PasswordTryTimes' => 3, 
                'PasswordType' => '')));
        $respArray = $this->createActivity($reqArray);
        if(false === $respArray){
            $this->error = "创建终端活动失败";
            return false;
        }
        //取得活动ID1
        $this->batchNo = $respArray['ActivityCreateRes']['Info']['ActivityID'];
        // 取得合约号
        $this->treatyId = isset($respArray['ActivityCreateRes']['Info']['pGoodsId']) ? $respArray['ActivityCreateRes']['Info']['pGoodsId'] : 0;
        if (0 === $this->treatyId) {
            $this->error = "创建合约失败";
            return false;
        }else{
            return true;
        }
    }
    /**
     * 添加商品信息
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param array  $goodsInfo   商品信息  
     * @param int  $id   商品ID
     * 
     * @return boolean 
     */
    public function insertGoodsInfo($goodsInfo, $id = null){
        if(null === $id){
            //添加信息
            $result = M($this->tableName)->data($goodsInfo)->add();
            if($result){
                return M($this->tableName)->getLastInsID();
            }else{
                return false;
            }
        }  else {
            //修改信息
            $result = M($this->tableName)->where(array('goods_id'=>$id))->data($goodsInfo)->save();
            return $result;
        } 
    }
    
    /**
     * 添加商品marketInfo信息
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param array  $goodsInfo   商品信息  
     * @param int  $id   商品ID
     * 
     * @return boolean 
     */
    public function insertMarketInfo($goodsInfo, $id = null){
        if(null === $id){
            //添加信息
            $result = M('tmarketing_info')->data($goodsInfo)->add();
            if($result){
                return M('tmarketing_info')->getLastInsID();
            }else{
                return false;
            }
        }  else {
            //修改信息
            $result = M('tmarketing_info')->where(array('id'=>$id))->data($goodsInfo)->save();
            return $result;
        }  
    }
    
    /**
     * 添加商品batchInfo信息
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param array  $goodsInfo   商品信息 
     * @param int  $id   商品ID 
     * 
     * @return boolean 
     */
    public function insertBatchInfo($goodsInfo, $id = null){
        if(null === $id){
            //添加信息
            $result = M('tbatch_info')->data($goodsInfo)->add();
            if($result){
                return M('tbatch_info')->getLastInsID();
            }else{
                return false;
            }
        }  else {
            //修改信息
            $result = M('tbatch_info')->where(array('id'=>$id))->data($goodsInfo)->save();
            return $result;
        }  
    }
    
    /**
     * 获取老数据展示(仅展示闪购,码上买,新品发布默认渠道)
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param string  $mId  商品 ID     
     * @param string  $type  商品类型     
     * 
     * @return array $channleId
     */
    public function getOdlEcshopChannel($mId, $type, $nodeId){
        $where = " a.node_id = '{$nodeId}' and ifnull(b.sns_type, -99) != '53'";//现在应该没有sns_type为53的，以后不等于53的应该能去掉
        $where .= " and a.batch_type = '" . $type . "'";
        $where .= " and a.batch_id = '" . $mId . "'";
        $where .= " and b.status = 1";
        $channelId = M()->table('tbatch_channel a')
            ->field('a.id')
            ->join('tchannel b ON a.channel_id=b.id')
            ->where($where)
            ->find();

        return $channelId['id'];
    }
    
    /**
     * 添加商品日志信息
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param array  $goodsInfo   商品信息 
     * @param int  $id   商品ID 
     * 
     * @return boolean 
     */
    public function insertMarketChangeTraceInfo($goodsInfo, $id= null){
        if(null === $id){
            //添加信息
            $result = M('tmarketing_change_trace')->data($goodsInfo)->add();
            if($result){
                return M('tmarketing_change_trace')->getLastInsID();
            }else{
                return false;
            }
        }  else {
            //修改信息
            $result = M('tmarketing_change_trace')->where(array('id'=>$id))->data($goodsInfo)->save();
            return $result;
        }  
    }
    
    /**
     * 添加商品扩展信息信息
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param array  $goodsInfo   商品信息  
     * @param int  $id   商品ID 
     * 
     * @return boolean 
     */
    public function insertGoodsExInfo($goodsInfo, $id = null){
        if(null === $id){
            //添加信息
            $result = M('tecshop_goods_ex')->data($goodsInfo)->add();
            if($result){
                return M('tecshop_goods_ex')->getLastInsID();
            }else{
                return false;
            }
        }  else {
            //修改信息
            $result = M('tecshop_goods_ex')->where(array('id'=>$id))->data($goodsInfo)->save();
            return $result;
        }  
    }
    
    /**
     * 添加商品渠道信息
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param array  $goodsInfo   商品信息  
     * 
     * @return boolean 
     */
    public function insertBatchChannel($goodsInfo){
        $result = M('tbatch_channel')->data($goodsInfo)->add();
        if($result){
            return M('tbatch_channel')->getLastInsID();
        }else{
            return false;
        }
    }
    
    /**
     * 创建支撑终端组
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param string  $nodeIn   取得用户下的所有唯一商户ID
     * @param array  $batchInfo   旧门店信息
     * @param array $newInfo 商品信息  
     * @param string $action 门店动作  
     * 
     * @return string
     */
    public function createSupport($newInfo = array(), $nodeIn, $batchInfo = false, $action = ''){
        //门店类型
        $groupTypeId = $newInfo['shop'];
        //分店信息
        $groupIdInfo = $newInfo['openStores'];
        //获取商户信息
        $nodeInfo = self::getNodeInfo();
        //判断是否新的门店
        if(false === $batchInfo){
            $groupId = false;
        }else{
            $groupId = $batchInfo['node_pos_group'];
        }
        if(!$nodeInfo){
            $this->error = '未找到该商户信息';
            return false;
        }  
        //获取旧的小店关联信息
        // posgroup_seq +1 门店提领
        M('tnode_info')->where(array('node_id' => $this->nodeId))->setInc('posgroup_seq'); 
        //获取门店信息
        $dataList = self::getStoreInfo($groupTypeId,$nodeIn, $groupIdInfo);
        if (false === $dataList){
            $this->error = '请选择门店信息';
            return false;
        }
        //获取支撑ID 
        $this->groupId = self::getGroupId($groupTypeId, $nodeInfo, $dataList, $groupId);
        
        switch ($groupTypeId) {
            case 1: // 全门店
                $sendGroupId = 0;
                //更改门店信息
                if(false === $batchInfo){
                    $retMsg = $this->goodsModel->addPosRelation($newInfo['goodsName'], $this->nodeId, $sendGroupId, $this->getPosData, $this->groupId);
                }else{
                    $retMsg = self::editPosRelation($newInfo, $sendGroupId, $batchInfo);
                }
                break;
            case 2: // 子门店
                $sendGroupId = 1;
                if(false == $batchInfo){
                    $retMsg = $this->goodsModel->addPosRelation($newInfo['goodsName'], $this->nodeId, $sendGroupId, $this->getPosData, $this->groupId);
                }  else {
                    //旧门店信息
                    $oldStoreArr = self::getOldStoreInfo($this->groupId);
                    //新门店信息
                    $newStoreArr = self::getNewStoreInfo();
                    $arrayDiff = array_diff($newStoreArr, $oldStoreArr);
                    //生成新的终端合约号
                    if ($batchInfo['node_pos_type'] == '1' || count($newStoreArr) != count($oldStoreArr) || ! empty($arrayDiff)) { // 全门店变成子门店或门店增加减少
                        $retMsg = self::editPosRelation($newInfo, $sendGroupId, $batchInfo, $newStoreArr);
                    }   
                }                    
                break;
        }
        if(false === $retMsg){
            $this->error = '保存门店关联信息失败,';
            return false;
        }
        return true;
    }
    /**
     * 修改门店关联信息
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param array  $newInfo   新商品信息
     * @param string  $sendGroupId   支撑终端类型
     * @param array  $batchInfo   旧商品信息
     * @param array  $newStoreInfo   新子门店信息
     * 
     * @return string $newStoreArr
     */
    private function editPosRelation($newInfo, $sendGroupId, $batchInfo = false, $newStoreInfo = false){
        //获取合约ID
        $treatyId = M('tgoods_info')->where(array('goods_id'=>$batchInfo['goods_id']))->getField('p_goods_id');
        if(!$treatyId){
            $this->error = '获取合约号失败';
            return false;
        }
        switch ($newInfo['shop']){
            case 1:
                if ($batchInfo['node_pos_type'] == '2') { // 子门店变为全门店
                    $this->groupId = $this->goodsModel->zcModifyStore($this->nodeId, $treatyId, '4');
                    if (! $this->groupId) {
                        $this->error = $this->goodsModel->getError();
                    }
                    // 新建合约
                    $retMsg = $this->goodsModel->addPosRelation($this->nodeId . '子门店-全门店', $this->nodeId, $sendGroupId, $this->getPosData, $this->groupId);
                    if (false === $retMsg) {
                        $this->error = $this->goodsModel->getError();
                        return false;
                    }
                }
                break;
            case 2:
                $this->groupId = $this->goodsModel->zcModifyStore($this->nodeId, $treatyId, '2', implode(',', $newStoreInfo));
                if (! $this->groupId) {
                    $this->error = $this->goodsModel->getError();
                }
                // 新建合约
                $retMsg = $this->goodsModel->addPosRelation($this->nodeId . '子门店-子门店', $this->nodeId, $sendGroupId, $this->getPosData, $this->groupId, true);
                if (false === $retMsg) {
                    $this->error = $this->goodsModel->getError();
                    return false;
                }
                break;
        }
        return true;
    }
    /**
     * 获取新门店信息
     *
     * @author John zeng<zengc@imageco.com.cn>
     * 
     * @return string $newStoreArr
     */
    private function getNewStoreInfo(){
        //新门店信息
        $newStoreArr = array();
        foreach ($this->getPosData as $v) {
            $newStoreArr[] = $v['store_id'];
        }
        //排重1
        $newStoreArr = array_unique($newStoreArr);
        return $newStoreArr;
    }
    /**
     * 获取旧门店信息
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param int  $groupId   终端ID
     * 
     * @return string $oldStoreArr
     */
    private function getOldStoreInfo($groupId){
        $oldStoreArr = array();
        $storeData = M('tgroup_pos_relation')->field('store_id')
            ->where("group_id={$groupId}")
            ->select();
        foreach ($storeData as $v) {
            $oldStoreArr[] = $v['store_id'];
        }
        //排重
        $oldStoreArr = array_unique($oldStoreArr);
        return $oldStoreArr;
    }
    /**
     * 生成终端ID
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param int  $groupTypeId   门店类型
     * @param array $nodeInfo  商户信息
     * @param array $dataList  门店信息
     * 
     * @return string $groupId
     */
    public function getGroupId($groupTypeId, $nodeInfo, $dataList, $groupId = false){
        //新增需创建新的支撑ID
        if(false === $groupId){
            $reqArray = array(
                'CreatePosGroupReq' => array(
                'NodeId' => $this->nodeId, 
                'GroupType' => $groupTypeId == '2' ? 1 : 0, 
                'GroupName' => str_pad($nodeInfo['client_id'], 6, '0', STR_PAD_LEFT) . $nodeInfo['posgroup_seq'], 
                'GroupDesc' => '', 
                'DataList' => $dataList));
            $groupId = $this->makeSupportId($reqArray);
        }
        return $groupId;
    }
    
    /**
     * 获取商品信息
     *
     * @author John zeng<zengc@imageco.com.cn>
     * 
     * @return array $dataList
     */
    public function getNodeInfo(){
        $nodeInfo = M('tnode_info')->field(
             'node_name,client_id,node_service_hotline,posgroup_seq')
             ->where("node_id='{$this->nodeId}'")
             ->find();
        return $nodeInfo;
    }
    /**
     * 获取终端门店信息
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param string  $nodeIn   取得用户下的所有唯一商户ID
     * @param int  $groupTypeId   门店类型
     * 
     * @return array $dataList
     */
    public function getStoreInfo($groupTypeId,$nodeIn, $groupIdInfo = ''){
        if ('2' != $groupTypeId) {
            $groupTypeId = 1;    //强制转换为全门店
            $this->getPosData = $this->goodsModel->getPosData($groupTypeId, $nodeIn);
            if (false === $this->getPosData){
                $this->error = $this->goodsModel->getError();
                return false;
            }    
            $dataList = $this->goodsModel->getDataList($groupTypeId, $this->getPosData);
        } else {
            $this->getPosData = $this->goodsModel->getPosData($groupTypeId, $nodeIn, $groupIdInfo);
            if (false === $this->getPosData){
                $this->error = $this->goodsModel->getError();
                return false;
            }    
            $dataList = $this->goodsModel->getDataList($groupTypeId, $this->getPosData);
        }
        return $dataList;
    }

    /**
     * 创建支撑终端组
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param array $reqArray 创建终端信息  
     * 
     * @return int $groupId
     */
    public function makeSupportId($reqArray){
        //创建终端信息
        $respArray = $this->addSupportInfo($reqArray);
        if(false === $respArray){
            $this->error = '创建门店失败:' . $respArray['StatusText'];
            return false;
        }

        //取得终端生成号
        $groupId = $respArray['CreatePosGroupRes']['GroupID'];
        
        return $groupId;
    }
    
    /**
     * 创建支撑活动
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param array  $reqArray   终端需要的信息
     * 
     * @return array $respArray  终端返回信息
     */
    public function createActivity($reqArray){
        //创建支撑活动
        $respArray = $this->addSupportActivity($reqArray);
        if(false === $respArray){
            return false;
        }
        return $respArray;
    }
    
    /**
     * 添加端组信息
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param array  $reqArray   终端需要的信息
     * 
     * @return array $respArray  终端返回信息
     */
    public function addSupportInfo($reqArray){
        $respArray = $this->remoteRequestService->requestIssForImageco($reqArray);
        $retMsg = $respArray['CreatePosGroupRes']['Status'];
        if (! $respArray || ($retMsg['StatusCode'] != '0000' && $retMsg['StatusCode'] != '0001')) {
            log::write("创建终端组失败，原因：{$retMsg['StatusText']}");
            return false;
        }else{
            return $respArray;
        }
    }
    
    /**
     * 添加支撑活动
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param array  $reqArray   终端需要的信息
     * 
     * @return array $respArray  终端返回信息
     */
    public function addSupportActivity($reqArray){
        $respArray = $this->remoteRequestService->requestIssForImageco($reqArray);
        $retMsg = $respArray['ActivityCreateRes']['Status'];
        if (! $respArray || ($retMsg['StatusCode'] != '0000' && $retMsg['StatusCode'] != '0001')) {
            $this->error = "创建失败:{$retMsg['StatusText']}";
            return false;
        }
        
        return $respArray;
    }
    
    /**
     * 关联商品检查
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param string  $rgoodsIds   关联商品ID
     * 
     * @return boolean 
     */
    public function relationGoodsCheck($rgoodsIds){
        if ($rgoodsIds != '') {
            $arr = explode(',', $rgoodsIds);
            $cnt = count($arr);
            if ($cnt > 8){
                $this->error = '关联商品最多只能选择8个！';
                return false;
            }    
            $where = "b.batch_type='31' and b.id IN({$rgoodsIds})";
            $cnt2 = M('tecshop_goods_ex as a')
                    ->join('tmarketing_info as b ON a.m_id = b.id')
                    ->where($where)->count();
            if ($cnt != $cnt2){
                $this->error = '关联商品有误！';
                return false;
            }else{
                return true;
            }    
        }else{
            return false;
        }
    }
    /**
     * 获取渠道ID
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param string  $nodeId   商户唯一标识
     * 
     * @return string $filename
     */
    public function getTbatchChannelId($nodeId = null) {
        if(null === $nodeId){
            $nodeId = $this->nodeId;
        }
        
        $info = M('tbatch_channel')
                ->where(array('node_id'=>$nodeId))
                ->order('id DESC')
                ->find();
        return $info;
    }
    /**
     * 通过渠道batchId查询商品名称
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param string  $qrcodeId   渠道batchId ID
     * 
     * @return string $filename
     */
    public function getShopName($qrcodeId) {
        // 查商品名
        $where = array('tc.id'=>$qrcodeId);
        $batchId = M('tbatch_channel as tc')
                ->join('tchannel as t ON tc.channel_id = t.id')
                ->where($where)
                ->getField('tc.batch_id');
        $where = array(
            'node_id' => $this->nodeId,
            'id' => $batchId
            );
        $filename = M('tmarketing_info')
                ->where($where)
                ->getField('name');
        return $filename;
    }
    /**
     * 检查给定的url是否为全路径
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param string  $qrcodeId   渠道batchId ID
     * 
     * @return string $url
     */
    public function getQRcodeUrl($qrcodeId)
    {
        //检测要存入二维码中的url是否是全路径 不是全路径则拼接成全路径返回
        $url = U('Label/Store/detail', array('id' => $qrcodeId), false, false, true); // 要存入二维码的URL全路径

        $location = strpos($url, 'ttp://');
        if ($location != 1) {
            // 查找http在不在里面 在里面则为全路径 不在里面则拼接路径
            $url = 'http://' . $_SERVER['HTTP_HOST'] . $url;
        }
        return $url;
    }
    /**
     * 获取小店渠道链接
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param string  $qrcodeId   渠道batchId ID
     * 
     * @return string $url
     */
    public function getGoodsUrl($bId)
    {
        $labelId = M('tbatch_info as b')->join('tecshop_goods_ex as ex on b.id = ex.b_id')->where(['b.m_id'=>$bId])->getField('ex.label_id');
        if(!$labelId){
            $labelId = $bId;
        }
        //检测要存入二维码中的url是否是全路径 不是全路径则拼接成全路径返回
        $url = U('Label/Label/index', array('id' => $labelId)); // 要存入二维码的URL全路径
        $location = strpos($url, 'ttp://');
        if ($location != 1) {
            // 查找http在不在里面 在里面则为全路径 不在里面则拼接路径
            $url = 'http://' . $_SERVER['HTTP_HOST'] . $url;
        }
        return $url;
    }
    /**
     * 生成二维码
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param string  $url   生成二维码的url地址
     * @param string  $qrcodeId   渠道batchId ID
     * @param string  $time   文件命名方式
     * 
     * @return boolean
     */
    public function mkQRCode($url, $time,$qrcodeId) {
        // 上架成功的时候生成二维码存放在服务器
        import('@.Vendor.phpqrcode.qrcode') or die('include file fail.');
        $errorCorrectionLevel = "L";
        $matrixPointSize = "4";

        $logourl = $this->goodsQrcodeDir . $this->nodeId . '/' . $time . '.png'; //这个是存放二维码的路径
        //在服务器生成二维码
        QRcode::png($url, $logourl, $errorCorrectionLevel, $matrixPointSize, 2);

        $logourl = $this->goodsQrcodeDir . $this->nodeId . '/' . $time . '.png'; //这个是存放二维码的路径

        $data['goods_qrcode_path'] = $logourl;
        $where['label_id'] = $qrcodeId;
        $result = M("tecshop_goods_ex")-> where($where) -> save($data); //将二维码路径存入表中
        return $result;
    }
    /**
     * 物流自提商品结构解析
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param string  $deliveryFlag   商品信息  
     * 
     * @return boolean 
     */
    public function getDeliverInfo($deliveryFlag){
        $deliveryArray = ['deliveryFlag'=>0, 'storeFlag'=>0];
        $deliveryFlagInfo = explode('-', $deliveryFlag);
        if(in_array('0',$deliveryFlagInfo)){
            $deliveryArray['storeFlag'] = 1;
        }
        if(in_array('1',$deliveryFlagInfo)){
            $deliveryArray['deliveryFlag'] = 1;
        }
        return $deliveryArray;
    }
    /**
     * 获取二维码ID
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param string  $id   渠道商品ID
     * 
     * @return string $qrcodeId
     */
    public function getQrcodeId($id) {
        $map = array(
            'a.batch_type'=>'31',
            'a.status'=>'1',
            'a.batch_id'=>$id,
            'b.sns_type'=>'46'
        );
        $qrcodeId = M('tbatch_channel as a')
                ->join('tchannel as b ON a.`channel_id` = b.`id`')
                ->where($map)
                ->getField('a.id');
        return $qrcodeId;
    }
    
    /**
     * 检查商品分组信息
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param array  $classifyInfo   商品分组信息
     * 
     * @return boolean 
     */
    public function reloadClassify($classifyInfo){
        $newClassify = [];
        $map = ['node_id' => $this->nodeId];
        if(!is_array($classifyInfo)){
            $this->error = '商品分组信息有误，请重新添加';
            return false;
        }
        foreach ($classifyInfo as $val){
            $classifList = explode(',', $val);
            //新增分组
            if(!isset($classifList[1]) || '' == $classifList[1]){
                if(!isset($classifList[0]) || '' == $classifList[0]){
                    $this->error = '商品分组信息有误，请重新添加';
                    return false;
                }
                $data = [
                    'node_id' => $this->nodeId,
                    'class_name' => $classifList[0],
                    'add_time' => date('YmdHis'),
                    'sort' => 99
                ];
                $result = M('tecshop_classify')->where($map)->add($data);
                if(!$result){
                    $this->error = '商品分组添加失败';
                    return false;
                }
                $newClassify[] = M()->getLastInsID();
            }else{
                $newClassify[] = $classifList[1];
            }
        }
        if(!count($newClassify)){
            $this->error = '商品分组添加失败1';
            return false;
        }
        return $newClassify;
    }
    
    /**
     * 获取当前使用的分组信息
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param array  $classifyId   商品分组ID
     * 
     * @return boolean 
     */
    public function getUseClassify($classifyId){
        if(empty($classifyId)){
            return false;
        }
        $map = ['node_id' => $this->nodeId,'id'=>['in',$classifyId]];
        $classifyInfo = M('tecshop_classify')->where($map)->field('class_name, id')->select();
        return $classifyInfo;
    }
    /**
     * 获取商户分组信息
     *
     * @author John zeng<zengc@imageco.com.cn>
     * 
     * @return boolean 
     */
    public function getNodeClassify(){
        $map = ['node_id' => $this->nodeId];
        //添加信息
        $classifyInfo = M('tecshop_classify')->where($map)->order('sort')->select();
        if($classifyInfo){
            return $classifyInfo;
        }else{
            return false;
        }
    }
    /**
     * 获取商品分组信息
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param int  $mId   商品ID 
     * 
     * @return boolean 
     */
    public function getGoodsClassify($mId = false){
        if(false === $mId){
            return false;
        }
        $map = ['m_id' => $mId];
        //添加信息
        $classifyInfo = M('tecshop_goods_ex')->where($map)->getField('ecshop_classify');
        if($classifyInfo){
            return $classifyInfo;
        }else{
            return false;
        }
    }
    /**
     * 商品库存管理
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param int  $mId   商品ID 
     * 
     * @return boolean 
     */
    public function goodsStockChange($bId, $sellNum, $isSku = false){
        $result = true;
        // 判断是否减库存
        if(true === $isSku){
            $model_ = M('tecshop_goods_sku');
            $map_ = ['id' => $bId, 'status' => '0'];
        }else{
            $model_ = M('tbatch_info');
            $map_ = ['id' => $bId, 'status' => '0'];
        }
        $bInfo = $model_->field('storage_num,remain_num')
            ->where($map_)
            ->find();
        if(!$bInfo){
            log_write('查找商品库存失败');
            return false;
        }
        if ($bInfo['storage_num'] != - 1) {
            $result = $model_->where($map_)->setDec('remain_num', $sellNum);
        }
        return $result;
    }
    
    /**
     * 获取商品渠道信息
     *
     * @author John zeng<zengc@imageco.com.cn>
     * 
     * @return 
     */
    public function getGoodsChannel(){
        $batchModel = M('tmarketing_info');
        $map = ['node_id' => $this->node_id, 'batch_type' => self::BATCH_TYPE_STORE];
        $batchInfo = $batchModel->where($map)->find();
        if (! empty($batchInfo)) {
            // 查询活动渠道最近的
            $channelModel = M('tbatch_channel');
            $map = [
                'node_id' => $this->node_id, 
                'batch_id' => $batch_info['id'], 
                'status' => '1'
                ];
            $channelInfo = $channelModel->where($map)->order("id desc")->find();
            if (! empty($channelInfo)) {
                session("id", "");
                session("id", $channelInfo['id']);
            }
        }
    }
    /**
     * 检验商品库存信息
     *
     * @author John zeng<zengc@imageco.com.cn>
     * 
     * @return 
     */
    // 校验库存，购买数量限制，1 购物车数据 2 直接购买校验
    public function checkStoreage($goodsId, $iscart, $orderPhone, $buynum = 1, $skuList = '') {
        if ($goodsId == "") {
            $this->error = '参数错误';
            return false;
        }
        //获取商品信息
        $goodsInfo = self::getSalesGoodsInfo($goodsId, $skuList);
        if(false === $goodsInfo){
            return false;
        }
        if ($iscart == 1) {
            $carts = self::_getCart();
            // sku商品库存处理
            if ('1' === $this->isSku) {
                $getGoodsId = $skuList . '_' . $goodsId;
                $existnum = $carts[$getGoodsId] + $buynum;
            } else {
                $existnum = $carts[$goodsId] + $buynum;
            }
        } else {
            $existnum = $buynum;
        }
        //判断首次购
        $goodsConfigData = json_decode($goodsInfo['config_data'],true);
        if ($goodsConfigData['fristBuyNum'] > 0) {
            $result = self::getFristBuyNum($goodsId, $goodsConfigData['fristBuyNum'], $existnum);
            if(false === $result){return false;}
        }
        // 判断日限购数量
        if ($goodsInfo['day_buy_num'] > 0) {
            $result = self::getDayBuyNum($goodsId, $goodsInfo['day_buy_num'], $existnum);
            if(false === $result){return false;}
        }
        // 判断用户限购
        if ($goodsInfo['person_buy_num'] > 0) {
            $result = self::getPersonBuyNum($goodsId, $orderPhone, $goodsInfo['person_buy_num'], $existnum);
            if(false === $result){return false;}
        }
        
        // 判断库存
        if ($goodsInfo['storage_num'] == - 1 || ($goodsInfo['storage_num'] != - 1 && $goodsInfo['remain_num'] >= $existnum)) {
            // $ret=$this->addGoodsToCart($goods_id,1);
            return true;
        } else {
            log_write('库存已售完，购买数' . $existnum . print_r($goodsInfo, true));
            return false;
        }
    }        
    
    /**
     * 获取销售商品信息
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param string  $goodsId  商品batchInfo ID   
     * @param string  $skuList  规格商品 ID   
     * 
     * @return array $goodsInfo
     */
    public function getSalesGoodsInfo($goodsId, $skuList = ''){
        // 判断是否sku商品
        $where = ['b.id' => $goodsId];
        //获取商品信息
        $field = 'e.day_buy_num, e.person_buy_num, e.config_data, b.batch_amt, b.storage_num, b.remain_num, b.begin_time, b.end_time, g.is_sku';
        $goodsInfo = M('tecshop_goods_ex as e ')
            ->join("tbatch_info as b ON b.id=e.b_id")
            ->join("tgoods_info as g ON g.goods_id=b.goods_id")    
            ->where($where)
            ->field($field) 
            ->find();
        if (empty($goodsInfo)) {
            $this->error = '操作失败,商品不存在！';
            return false;
        }
        
        //判断时间是否开始
        if($goodsInfo['begin_time'] > date("YmdHis")){
            $this->error = '商品还未开始销售';
            return false;
        }
        //判断时间是否开始
        if($goodsInfo['end_time'] < date("YmdHis")){
            $this->error = '商品已下架';
            return false;
        }
        //库存判断
        if($goodsInfo['storage_num'] != -1 && $goodsInfo['remain_num'] < 1){
            $this->error = '商品已售完';
            return false;
        }
        $this->getPrice = $goodsInfo['batch_amt'];
        $this->isSku = $goodsInfo['is_sku'];
        //判断是否SKU商品
        if('1' == $this->isSku){
            $map = ['g.sku_detail_id' => $skuList,'b.b_id'=>$goodsId, 'g.status' => '0', 'b.status' => '0'];
            //获取规格商品剩余数
            $skuInfos = M('tecshop_goods_sku as b')
                    ->join('tgoods_sku_info as g on g.id = b.skuinfo_id')
                    ->where($map)
                    ->field('b.remain_num,b.sale_price')
                    ->find();
            if(!$skuInfos){
                $this->error = '规格商品已下架，获取规格信息失败！';
                return false;
            }
            $goodsInfo['remain_num'] = $skuInfos['remain_num'];
            $this->getPrice = $skuInfos['sale_price'];
        }
        return $goodsInfo;
    }
    
    /**
     * 获取用户起购数
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param string  $bId   商品ID 
     * @param string  $fristBuyNum 起购数   
     * @param string  $existnum  购物车商品数   
     * 
     * @return array $goodsInfo
     */
    public function getFristBuyNum($bId, $fristBuyNum, $existnum){
        if($fristBuyNum > $existnum){
            $this->error = '操作失败，商品起购数为' . $fristBuyNum . '份，不能低于此数量';
            return false;
        }
        return true;
    }
    /**
     * 获取用户限购
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param string  $key   用户购买session key 
     * @param string  $personBuyNum 用户限购数
     * @param string  $existnum  购物车商品数 
     * 
     * @return array $goodsInfo
     */
    public function getPersonBuyNum($goodsId, $orderPhone, $personBuyNum, $existnum){
        // 计算当天用户订单商品数量
        $goodsWhere = array(
            "a.b_id" => $goodsId, 
            "e.order_phone" => $orderPhone, 
            "e.order_status" => "0");
        // 计算当天用户订单商品数量
        $goodsCount = M('ttg_order_info e ')->join(
            'ttg_order_info_ex a ON e.order_id=a.order_id ')
            ->where($goodsWhere)
            ->sum('a.goods_num');
        $allcount = (int)($existnum + $goodsCount);
        if ($allcount > $personBuyNum) {
            $this->error = '操作失败，商品每个用户限购' . $personBuyNum . '份';
            return false;
        }
        return true;
    }
     /**
     * 获取用户已购买商品数量信息
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param string  $phoneNum   购买手机号码
     * @param string  $mId 商品Id
     * @param string  $buyNum 购买商品数
     * 
     */
    public function getCustomerBuyNum($phoneNum, $mId, $buyNum){
	//个人限购
        $pesonLimitBuy = 'store_' . $phoneNum . '_' . $mId;
        if(session('?' . $pesonLimitBuy)){
            $pesonBuyNum = session($pesonLimitBuy) + $buyNum;
        }else{
            $pesonBuyNum = $buyNum;
        }
        //记录用户购买信息
        session($pesonLimitBuy, $pesonBuyNum);
        
        //日限购
	$dayLimitBuy = 'store_' . $mId . '_' . date('Ymd');
	if(session('?' . $dayLimitBuy)){
            $dayBuyNum = session($dayLimitBuy) + $buyNum;
        }else{
            $dayBuyNum = $buyNum;
        }
        //记录用户购买信息
        session($dayLimitBuy, $dayBuyNum);
    }
    /**
     * 获取购物车数据
     *
     * @author John zeng<zengc@imageco.com.cn>
     * 
     * @return array $goodsInfo
     */
    public function _getCart() {
        //判断用户是否登录
        if(empty($this->phoneNum)){
            return false;
        }

        if (! session('?' . $this->session_cart_name)) {
            $carts = array();
        } else {
            $carts = unserialize(session($this->session_cart_name));
        }
        return $carts;
    }
    
    /**
     * 删除购物车数据
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param string  $bId     商品ID
     * @param string  $skuId   规格商品ID
     * 
     * @return array $goodsInfo
     */
    public function deleteCart($bId,$skuId = '') {
        $carts = $this->_getCart();
        if(false === $carts){
            // 判断是否登录
            $this->error('您还没有登录');
        } 
        // 判断是否sku商品
        if (empty($skuId)) {
            $cartInfo = $carts[$bId];
        } else {
            $key = $skuId . '_' . $bId;
            $cartInfo = $carts[$key];
        }
        $shipdata = $this->_getShipType();
        if (! empty($shipdata)) {
            unset($shipdata[$key]);
            session($this->session_ship_name, serialize($shipdata));
        }
        // unset($carts);
        if (! empty($cartInfo)) {
            // 判断是否sku商品
            if (empty($skuId)) {
                unset($carts[$bId]);
            } else {
                $key = $skuId . '_' . $bId;
                unset($carts[$key]);
            }
            session($this->session_cart_name, serialize($carts));
            return true;
        } else {
            return false;
        }
    }
    
    // 获商品配送方式
    public function _getShipType() {
        if (! session('?' . $this->session_ship_name)) {
            $ships = array();
        } else {
            $ships = unserialize(session($this->session_ship_name));
        }
        return $ships;
    }

    /**
     * 获取商品售卖剩余数
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param string  $bId   商品ID 
     * @param string  $phoneNum   用户手机号
     * 
     * @return array $goodsInfo
     */
    public function getSalesGoodsRemainNum($bId, $phoneNum = false){
        $goodsWhere = [
                'a.b_id' => $bId, 
                'e.add_time' => ['like', "{$this->curdate}%"],      
                'e.order_status' => '0'
            ];
        if(false != $phoneNum){
           $goodsWhere['e.order_phone'] = $phoneNum;
        }    
        // 计算当天订单商品数量
        $goodsCount = M('ttg_order_info as e ')
            ->join('ttg_order_info_ex as a ON e.order_id=a.order_id ')
            ->where($goodsWhere)
            ->sum('a.goods_num');
        return (int)$goodsCount;
    }
    
    /**
     * 获取商品日限购
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param string  $key   用户购买session key 
     * @param string  $dayBuyNum 商品日限购
     * @param string  $existnum  购物车商品数  
     * 
     * @return array $goodsInfo
     */
    public function getDayBuyNum($goods_id, $dayBuyNum, $existnum){
        // 计算当天用户订单商品数量
        //用户当天购买数
        $curdate = date("Ymd");
        $goodsWhere = array(
            "a.b_id" => $goods_id, 
            "e.add_time" => array(
                "like", 
                "{$curdate}%"), 
            "e.order_status" => "0");
        // 计算当天订单商品数量
        $goodsCount = M('ttg_order_info e ')->join(
            'ttg_order_info_ex a ON e.order_id=a.order_id ')
            ->where($goodsWhere)
            ->sum('a.goods_num');
        $allcount = (int)($existnum + $goodsCount);
        if ($allcount > $dayBuyNum) {
            $this->error = '操作失败，商品日限购' . $dayBuyNum . '份';
            return false;
        }else{
            return true;
        }
    }
   

    /**
     * 获取商品信息（通过batchId）
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param string  $batchId   
     * 
     * @return array $goodsInfo
     */
    public function getGoodsInfoByBatchId($batchId){
        $goodsInfo = M('tbatch_info')
                ->field('id')
                ->where("m_id='" . $batchId . "'")
                ->find();
        return $goodsInfo;
    }
    
    /**
     * 获取商品展示页面最新价格信息
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param string  $batchId   
     * 
     * @return array $pageInfo
     */
    public function getPageInfoNew($pageInfo){
        $tmpArray = json_decode($pageInfo, true);
        foreach ($tmpArray['module'] as &$val){
            if( 'Pro' === $val['name'] || 'ProList' === $val['name']){
                foreach ($val['list'] as &$v){
                    $urlArr = explode('=', $v['url']);
                    $batchInfo = M('tbatch_info as b')
                            ->join('tecshop_goods_ex as ex on b.id = ex.b_id')
                            ->where(['label_id' =>$urlArr[5]])
                            ->field('b.batch_amt, b.goods_id, b.node_id, b.m_id')->find();
                    //数据未找到
                    if(!$batchInfo){
                        continue;
                    }
                    //重新赋值商品价格
                    $v['batch_amt'] = $batchInfo['batch_amt'];
                    
                    $isSku = M('tgoods_info')->where(['goods_id'=>$batchInfo['goods_id']])->getField('is_sku');
                    if('1' == $isSku){
                        $skuObj = D('Sku', 'Service');
                        $skuObj->nodeId = $batchInfo['node_id'];
                        $v = $skuObj->makeGoodsListInfo($v, $batchInfo['m_id'], '');
                    }else{  
                        $v['price'] = $batchInfo['batch_amt'];
                    } 
                }
            }
        }
        return json_encode($tmpArray);
    }
    
    /**
     * 检查关联商品信息
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @param string  $rgoodsIds    //关联商品信息
     * 
     * @return array $goodsInfo
     */
    public function checkRelationGoods($rgoodsIds, $isdwon = true){
        
        $arr = explode(',', $rgoodsIds);
        $cnt = count($arr);
        foreach ($arr as $vId) {
            $skuInfoList = D('Sku', 'Service')->getSkuEcshopList($vId, $this->nodeId, $isdwon);
            // 判断是否有sku数据
            if ($skuInfoList) {
                $this->error = '关联商品目前暂不支持SKU商品！';
                return false;
            }
        }
        if ($cnt > 8){
            $this->error = '关联商品最多只能选择8个！';
            return false;
        }    
        $cnt2 = M()->table('tecshop_goods_ex a, tmarketing_info b')->where(
            "b.batch_type = '31' and a.m_id = b.id and b.id in ({$rgoodsIds})")->count();
        if ($cnt != $cnt2){
            $this->error = '关联商品有误！';
            return false;
        } 
        return true;
    }
   
    //释放
    private function __destruct() {
        unset($this->goodsModel);
    }
    /**
     * 获取错误信息
     *
     * @author John zeng<zengc@imageco.com.cn>
     * @return string
     */
    public function getError(){
        return $this->error;
    }
}
