<?php

/**
 * 福建号码百事通
 */
class InterfaceAction extends Action {

    /**
     * @var Fj114Service
     */
    private $fj114Service;
    private $creatAccountKey = '2e556fb487d94919eb382c104e6f470d';
    private $businessContentKey = '2e556fb487d94919eb382c104e6f470d';
    private $loginKey = '18B27AE4B1527356737293C91A345A4F';
    private $nodeId = '00004488';
    private $configArr = array('38','68','158');

    public function __construct() {
        parent::__construct();
        log_write(print_r($_GET, TRUE));
        $this->fj114Service = D('Fj114', 'Service');
    }

    /**
     * 商户添加、取消、变更接口
     */
    public function account() {
        $result = array();

        $traceData = array_change_key_case($_GET);
        M('tfb_fjguaji_order_trace')->add($traceData);

        $serialNum = I('get.serialNum', '0', 'string');
        $this->fj114Service->checkNotNull($serialNum, 'serialNum');

        $telephone = I('get.telephone', '0', 'string');
        $this->fj114Service->checkNotNull($telephone, 'telephone');

        $orderType = I('get.orderType', '0', 'string');
        $this->fj114Service->checkNotNull($orderType, 'orderType');

        $package = I('get.package', '0', 'string');
        $this->fj114Service->checkNotNull($package, 'package', $this->configArr);

        $orderTime = I('get.orderTime', '0', 'string');
        $this->fj114Service->checkNotNull($orderTime, 'orderTime');

        $ShopsName = I('get.ShopsName', '0', 'string');
        if(strlen($ShopsName) > 24){
            $ShopsName = mb_substr($ShopsName, 0, 6);
        }elseif($ShopsName == '0' || $ShopsName == ''){
            $ShopsName = $telephone;
        }
        $ShopsName = $this->fj114Service->getShopName($this->nodeId, $ShopsName, 0, $telephone);
        $ShopsRegion = I('get.ShopsRegion', '0', 'int');
        $this->fj114Service->checkNotNull($ShopsRegion, 'ShopsRegion');

        $ShopsAddr = I('get.ShopsAddr', 'shopAddr: undefined', 'int');

        $opkey = I('get.opkey', '0', 'string');
        $this->fj114Service->checkNotNull($opkey, 'opkey');

        $keyStr = $this->fj114Service->createKey($serialNum, $telephone, $orderType, $package, $orderTime, $this->creatAccountKey);
        $this->fj114Service->checkKey($opkey, $keyStr);

        $req_arr = array();
        $req_arr['SystemID'] = C('ISS_SYSTEM_ID');
        $req_arr['TransactionID'] = time() . mt_rand('1000', '9999');
        $req_arr['ISSPID'] = $this->nodeId;
        $req_arr['StoreName'] = $ShopsName;
        $req_arr['StoreShortName'] = $ShopsName;
        $req_arr['ContactName'] = 'undefined';
        $req_arr['ContactTel'] = $telephone;
        $req_arr['ContactPhone'] = $telephone;
        $req_arr['ContactEmail'] = $telephone . '@guaji.com';
        $req_arr['RegionInfo']['Province'] = '13';
        $req_arr['RegionInfo']['City'] = $ShopsRegion;
        $req_arr['RegionInfo']['Town'] = '006';
        $req_arr['RegionInfo']['Address'] = $ShopsAddr;
        $req_arr['ContactTel'] = $telephone;
        $req_arr['ContactPhone'] = $telephone;
        $req_arr['ContactEmail'] = $telephone . '@guaji.com';
        
        $existStoreId = M('tfb_fjguaji_shop_info')->where(array('phone_no' => $telephone))->getfield('store_id');
        $existStoreId = get_val($existStoreId, '', '0000');
        switch ($orderType) {
            //订购
            case '1':
                if ($existStoreId != '0000') {
                    $result['resultCode'] = (int) 3001;
                    $result['resultTxt'] = 'account exist';
                    echo json_encode($result);
                    exit;
                }
                $req_arr['Url'] = '<![CDATA[旺财会员账户中心]]>';
                log_write(print_r($req_arr, TRUE));
                $shopCreate = D('RemoteRequest', 'Service')->requestIssServ(array('CreateStoreExReq' => $req_arr));
                $shopCreateResult = isset($shopCreate['CreateStoreExRes']) ? $shopCreate['CreateStoreExRes']['Status'] : $shopCreate['Status'];
                if ($shopCreateResult['StatusCode'] != '0000' || $shopCreate['CreateStoreExRes']['StoreId'] == '') {
                    $result['resultCode'] = (int) 4001;
                    $result['resultTxt'] = $shopCreateResult['StatusText'] ? $shopCreateResult['StatusText'] : '创建门店失败';
                    echo json_encode($result);
                    exit;
                }
                
                $this->fj114Service->saveData($this->nodeId, $telephone, $ShopsName, $ShopsRegion, $ShopsAddr, $package, $shopCreate['CreateStoreExRes']['StoreId'], 'new');
                break;
            //取消
            case '2':
                if($existStoreId == '0000'){
                    echo json_encode(array('resultCode'=>(int) 3001, 'resultTxt'=>'未找到门店'));
                    exit;
                }
                M('tfb_fjguaji_shop_info')->where(array('phone_no' => $telephone))->save(array('status' => '1'));
                echo json_encode(array('resultCode'=>(int) 0, 'resultTxt'=>'success'));
                break;
            //变更
            case '3':
                $req_arr['StoreID'] = $existStoreId;
                $req_result = D('RemoteRequest', 'Service')->requestIssServ(array('MotifyPosStoreReq' => $req_arr));
                $respStatus = isset($req_result['MotifyPosStoreRes']) ? $req_result['MotifyPosStoreRes']['Status'] : $req_result['Status'];
                if ($respStatus['StatusCode'] != '0000' && $respStatus['StatusText'] != '相同门店名称已存在') {
                    $result['resultCode'] = (int) 4001;
                    $result['resultTxt'] = 'Modify failed';
                    echo json_encode($result);
                    exit;
                }
                
                $this->fj114Service->saveData($this->nodeId, $telephone, $ShopsName, $ShopsRegion, $ShopsAddr, $package, $existStoreId, 'save');
                break;
                
            default:
                $result['resultCode'] = (int) 2001;
                $result['resultTxt'] = 'undefined orderType';
                echo json_encode($result);
                exit;
        }
    }

    /**
     * 商户信息接口
     */
    public function shopInfo() {
        $result = array();
        $seqid = I('get.seqid', '0', 'string');
        $this->fj114Service->checkNotNull($seqid, 'seqid');

        $telephone = I('get.telephone', '0', 'string');
        $this->fj114Service->checkNotNull($telephone, 'telephone');

        $isdn = I('get.isdn', '0', 'string');
        $this->fj114Service->checkNotNull($isdn, 'isdn');

        $opTime = I('get.opTime', '0', 'string');
        $this->fj114Service->checkNotNull($opTime, 'opTime');

        $opkey = I('get.opkey', '0', 'string');
        $this->fj114Service->checkNotNull($opkey, 'opkey');
        $keyStr = $this->fj114Service->createKey($seqid, $telephone, $isdn, $opTime, $this->businessContentKey);
        $this->fj114Service->checkKey($opkey, $keyStr);

        $shopInfo = M('tfb_fjguaji_shop_info')->where(array('phone_no' => $telephone))->field('id, goods_list, package')->find();
        if (empty($shopInfo)) {
            $result['resultCode'] = (int) 4001;
            $result['resultTxt'] = 'missing shop';
            echo json_encode($result);
            exit;
        }
        
        $cardCreatedCount = $this->fj114Service->checkCreatedCardCount($shopInfo['goods_list']);
        if( ($shopInfo['package'] == '38' && $cardCreatedCount > 180) || 
            ($shopInfo['package'] == '68' && $cardCreatedCount > 380) || 
            ($shopInfo['package'] == '158' && $cardCreatedCount > 380)){
            echo json_encode(array('resultCode'=>(int) 1, 'resultTxt'=>'本月制码数已满'));
            exit;
        }

        $searchCondition = array();
        $searchCondition['shop_id'] = $shopInfo['id'];
        $searchCondition['start_time'] = array('elt', date("YmdHis"));
        $searchCondition['end_time'] = array('egt', date("YmdHis"));
        $searchCondition['type'] = '1';
        $searchCondition['is_delete'] = '0';
        $goodsId = M('tfb_fjguaji_send_set')->where($searchCondition)->getField('g_id');
        if(!$goodsId){
            $cardSetInfo = M('tfb_fjguaji_send_set')->where(array('shop_id'=>$shopInfo['id'], 'type'=>'2'))->field('g_id, start_time, end_time')->find();
            if($cardSetInfo['start_time'] != ''){
                $nowTime = date('YmdHis');
                if($nowTime <= $cardSetInfo['end_time'] && $nowTime >= $cardSetInfo['start_time']){
                    $goodsId = $cardSetInfo['g_id'];
                }else{
                    $result = array('resultCode'=>(int)4002, 'resultTxt'=>'未找到有效时间段内的卡券');
                    echo json_encode($result);
                    exit;
                }
            }else{
                $goodsId = $cardSetInfo['g_id'];
            }
        }
        $batchInfoId = $this->fj114Service->createMarketingId($goodsId, $this->nodeId);
        if(is_array($batchInfoId)){
            echo json_encode($batchInfoId);
            exit;
        }
        
        $goodsInfo = M('tgoods_info')->where(array('id'=>$goodsId))->field('batch_no, goods_name')->find();
        $shortUrl = $this->fj114Service->sendCode($this->nodeId, $goodsInfo['batch_no'], $batchInfoId);
        $result['resultCode'] = (int) 0;
        $result['resultTxt'] = 'success';
        $result['content'] = '恭喜您获得'.$goodsInfo['goods_name'].'，详情请点击：'.$shortUrl.' 获取';
        echo json_encode($result);
        exit;
    }

    /**
     * 商户登录接口
     */
    public function userLogin() {
        $telephone = I('get.telephone', '0', 'string');
        $this->fj114Service->checkNotNull($telephone, 'telephone');

        $timestamp = I('get.timestamp', '0', 'string');
        $this->fj114Service->checkNotNull($timestamp, 'timestamp');

        $opkey = I('get.opkey', '0', 'string');
        $this->fj114Service->checkNotNull($opkey, 'opkey');

        $keyStr = $this->fj114Service->createKey($telephone, $timestamp, $this->loginKey);
        $this->fj114Service->checkKey($opkey, $keyStr);

        $shopInfo = M('tfb_fjguaji_shop_info')->where(array('phone_no' => $telephone))->field('`id`', 'store_id', 'phone_no', 'package', 'status')->find();
        if ($shopInfo['status'] == '0') {
            $shopInfo['node_id'] = $this->nodeId;
            $_SESSION['Fj114'] = $shopInfo;
            $result['resultCode'] = (int) 0;
            $result['resultTxt'] = 'success';
            $this->redirect(U('Fj114/UserCode/index', '', '', '', TRUE));
        } elseif ($shopInfo['status'] == '1') {
            $this->error('账号已停用');
        } else {
            $this->error('请核对您的帐号');
        }
        exit;
    }

}
