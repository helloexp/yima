<?php

/**
 * 福建号码百事通页面
 */
class UserCodeAction extends Action {

    private $fj114Service;
    private $fj114Model;
    private $nodeId;
    private $storeId;
    private $cardImg = '00004488/2016/01/11/569358f5d925e.jpg';
    private $goodsIdList;

    public function __construct() {
        parent::__construct();
		
        if (!isset($_SESSION['Fj114'])) {
            $this->error('请登录');
            exit;
        }
        $this->nodeId = $_SESSION['Fj114']['node_id'];
        $this->storeId = $_SESSION['Fj114']['store_id'];
        $_SESSION['node_id'] = $_SESSION['Fj114']['node_id'];
        $this->fj114Service = D('Fj114', 'Service');
        $this->fj114Model = D('Fj114');
        $goodsIdList = $this->fj114Model->getGoodsList($_SESSION['Fj114']['phone_no']);
        $this->goodsIdList = $goodsIdList;
        $createdCardAccount = $this->fj114Service->checkCreatedCardCount($goodsIdList);
        $this->assign('createdCardAccount', $createdCardAccount);
    }
	//新建代码开始30-81
	//业务介绍	
	public function index(){
		$this->display();
	}
	public function Package(){
	$this->display();
	}
	
	//查看套餐	
	public function Packages(){
		if( $_SESSION['Fj114']['package']==38 ){
			$surplus = 180-$this->createdCardAccount;
			$content = "1:二维码电子券180条：用户可以配置优惠券、代金券、抵用券等形式的内容跟随挂机短信下发。2：二维码由E-POS验证平台进行核销";
		}elseif($_SESSION['Fj114']['package']==68){
			$surplus = 380-$this->createdCardAccount;
			$content = "1:二维码电子券380条：用户可以配置优惠券、代金券、抵用券等形式的内容跟随挂机短信下发。2：二维码由E-POS验证平台进行核销";
		}elseif($_SESSION['Fj114']['package']==158){
			$surplus = 380-$this->createdCardAccount;
			$content = "1:二维码电子券380条：用户可以配置优惠券、代金券、抵用券等形式的内容跟随挂机短信下发。
			2：砸金蛋、微抽奖、节日营销、电子海报等微营销功能。3：二维码由E-POS验证平台进行核销";
		}

		$packages = array(
			'name'=>$_SESSION['Fj114']['package'].'套餐',
			'price'=>$_SESSION['Fj114']['package'].'元/月',
			'content'=>$content,
			'totle'=>$this->createdCardAccount,
			'surplus'=>$surplus
		);
		$this->ajaxReturn(array('info'=>$packages),'JSON');

	}

	
	/**
     * 停用某一优惠券
     */
    public function deleteCode() {
        $goodsId = I('post.goodsid', '0', 'string');
		$shopId = $_SESSION['Fj114']['id'];		
		$times = $this->fj114Service->codeSendSetedtime($goodsId,$shopId);	
		if($times !== NULL || $times['end_time'] > date('YmdHis')){
			$this->ajaxReturn(array('resultCode' => '1001', 'resultTxt' => '当前卡券发送时间未结束，不能删除'));
		}
        if ($goodsId != '0') {
            M('tgoods_info')
                ->where(array('goods_id'=>$goodsId))
                ->save(array('status'=>'1'));
            $this->ajaxReturn(array('error'=>'0'));
        }else{
           $this->ajaxReturn(array('error'=>'1001'));
        }
        
    }
	//新建代码结束
    /**
     * 创建优惠券显示页面
     */
    public function createCode() {
		/*
        $goodsId = I('get.id', '0', 'string');	
        if ($goodsId != '0') {
            $cardInfo = $this->fj114Service->cardDetail($goodsId);
            $str = "{'name':'".$cardInfo['goods_name']."','details':'".$cardInfo['print_text']."','notice':'".$cardInfo['config']['notice']."','days':'".$cardInfo['config']['verify_end_time']."','img':'".get_upload_url($cardInfo['goods_image'])."'}";

			$this->assign('cardInfoStr', $str);
            $this->assign('goodsId', $cardInfo['id']);
            $this->assign('cardType', $cardInfo['config']['cardType']);
            $this->assign('img', $cardInfo['goods_image']);
        }else{			
            $this->assign('cardInfoStr', 'undefined');
        }		
		*/		
        $this->display();
    }
	public function createCode_data(){				
		$goodsId = I('get.id', '0', 'string');	
        if ($goodsId != '0') {
			$cardInfo = $this->fj114Service->cardDetail($goodsId);
			$resultTxt = array(
				"name" => $cardInfo['goods_name'],
				"details" => $cardInfo['print_text'],
				"notice" => $cardInfo['config']['notice'],
				"days" => $cardInfo['config']['verify_end_time'],
				"img" => get_upload_url($cardInfo['goods_image']),
				"creaType" => $cardInfo['config']['cardType']
			);	
			$this->ajaxReturn(array('resultCode'=>0,'resultTxt'=>$resultTxt),'JSON');	
		}	
	}
    /**
     * 创建优惠券
     */
    public function createCard() {
        if (!IS_POST) {
            $this->ajaxReturn(array('resultCode' => '1001', 'resultTxt' => '非法操作'));
            exit;
        }     
        $result = array();				
        $cardName = I('post.name');
        if (!check_str($cardName, array('null' => false, 'maxlen_cn' => '24'), $error)) {
            $result['resultCode'] = '1001';
            $result['resultTxt'] = '卡券名称' . $error;
            $this->ajaxReturn($result);
        }

        $goodImage = I('post.imgPath', '0', 'string');
		$imglen = strlen($goodImage);
        if($goodImage == '0' || $goodImage =='' || $imglen > 6){
            $goodImage = $this->cardImg;
        }

        $printText = I('post.print_text');
        if (!check_str($printText, array('maxlen_cn' => '100'), $error) || $printText == '') {
            $result['resultCode'] = '1001';
            $result['resultTxt'] = '优惠详情' . $error;
            $this->ajaxReturn($result);
        }
        
        $veryDays = (int)I('post.verify_end_days','0', 'string');
        if($veryDays < 1){
            $result['resultCode'] = '1001';
            $result['resultTxt'] = '请填写正确的优惠券有效期';
            $this->ajaxReturn($result);
        }
        
        $cardType = I('post.cardType','0','string');
        if($cardType == '0'){
           $this->ajaxReturn(array('resultCode'=>'1001', 'resultTxt'=>'请选择卡券类型'));
        }

        $useNotice = I('post.notice', '0', 'string');
        if($useNotice == '0' || $useNotice == ''){
            $result['resultCode'] = '1001';
            $result['resultTxt'] = '请填写使用说明';
            $this->ajaxReturn($result);
        }

        $posId = $this->fj114Service->checkTerminal($_SESSION['Fj114']['store_id'], $_SESSION['Fj114']['node_id']);
        if (!$posId) {
            $result['resultCode'] = '1002';
            $result['resultTxt'] = '请先至左侧菜单【EPOS设置】中填写您的邮箱地址。';
            $this->ajaxReturn($result);
        }
        $posGroup = M('tfb_fjguaji_shop_info')->where(array('phone_no' => $_SESSION['Fj114']['phone_no']))->getField('pos_group');
        if ($posGroup == '') {
            $createTerminalGroupResult = $this->fj114Service->createTerminalGroup($this->nodeId, $posId, $this->storeId);
            self::ajaxResult($createTerminalGroupResult);
        }
        
        $cardInfo = array();
        $cardInfo['notice'] = $useNotice;
        $cardInfo['verify_end_time'] = $veryDays;
        $cardInfo['cardType'] = $cardType;
        $goodsId = I('post.goods_id', '0', 'string');
        if($goodsId == '0' || $goodsId == ''){
			$codeResult = $this->fj114Service->createContract($posGroup, $cardName, $goodImage, $printText, $cardInfo, $this->nodeId, $_SESSION['Fj114']['phone_no']);
            
			$startTime = I('post.startTime');
			$endTime =  I('post.endTime');
			if($endTime == NULL && $startTime == NULL){
				self::ajaxResult($codeResult);
			}
//
			if($codeResult['resultTxt'] == 'success' && $codeResult['id'] !=='' ){
				
				$goodsId = $codeResult['id'];
				
				$newData = array();       
				if ($goodsId == '') {
					$this->ajaxReturn(array('resultCode' => '1001', 'resultTxt' => '请选择卡券'));
				}
				$checkGoodIdResult = $this->fj114Service->checkGoodId($goodsId, $_SESSION['Fj114']['phone_no']);
				self::ajaxResult($checkGoodIdResult);
				$newData['g_id'] = $goodsId;
								
					
					$startTime = dateformat($startTime, 'Ymd', '000000');
					if ($startTime == FALSE) {
						$this->ajaxReturn(array('resultCode' => '1001', 'resultTxt' => '开始时间格式错误'));
					}
					
					$endTime = dateformat($endTime, 'Ymd', '235959');
					if ($endTime == FALSE) {
						$this->ajaxReturn(array('resultCode' => '1001', 'resultTxt' => '结束时间格式错误'));
					}
					
					$checkCodeDateRepeatResult = $this->fj114Service->checkCodeDateRepeat($startTime, $endTime);
					self::ajaxResult($checkCodeDateRepeatResult);
					$newData['start_time'] = $startTime;
					$newData['end_time'] = $endTime;
					$newData['shop_id'] = $_SESSION['Fj114']['id'];
					$newSetId = M('tfb_fjguaji_send_set')->add($newData);
					if ($newSetId) {
						$this->ajaxReturn(array('resultCode' => '0', 'resultTxt' => '卡券创建成功'));
					} else {
						$this->ajaxReturn(array('resultCode' => '1002', 'resultTxt' => '卡券创建失败'));
					}
			}
//			
        }else{
            $checkGoodsResult = $this->fj114Service->checkGoodId($goodsId, $_SESSION['Fj114']['phone_no']);
            self::ajaxResult($codeResult);
            $data = array();
            $data['print_text'] = $printText;
            $data['goods_name'] = $cardName;
            $data['goods_image'] = $goodImage;
            $data['config_data'] = json_encode($cardInfo);
            M('tgoods_info')->where(array('id'=>$goodsId))->save($data);
            $this->ajaxReturn(array('resultCode'=>'0', 'resultTxt'=>'修改成功'));
        }
		
		
    }

    /**
     * 卡全列表
     */
    public function codeList() {
        $keyWord = I('get.keyWord', '0', 'string');
        $condition = array();
        if ($keyWord != '0') {
            $condition['tgi.goods_name'] = array('like', '%' . $keyWord . '%');
            $this->assign('keyWord', $keyWord);
        }
        
        $condition['tgi.id'] = array('in', $this->goodsIdList);
        $condition['tgi.status'] = array('eq',0);//新加查询条件卡券是否停用
        $join_sql = M('tbarcode_trace')->field('goods_id, ifnull(count(*),0) as cnt')->group('goods_id')->buildSql();
        $cardInfo = M('tgoods_info tgi')
                ->field('tgi.goods_name, tgi.id, tgi.goods_image, tgi.goods_id, IFNULL(t2.cnt,0) as total_send')
                ->join("{$join_sql} t2 ON t2.goods_id = tgi.goods_id")
                ->where($condition)
                ->select();
        $mothStartDate = date('Ym01') . '000000';
        $monthEndDate = date('Ym') . date('t') . '235959';
        foreach ($cardInfo as $key => $val) {
            $condition = array('goods_id' => $val['goods_id']);
            $condition['trans_time'] = array('between', array($mothStartDate, $monthEndDate));
            $cardInfo[$key]['monthSend'] = M('tbarcode_trace')->where($condition)->count();
        }
        $this->assign('cardInfo', $cardInfo);
        $this->display();
    }

    /**
     * 发送卡券设置列表
     */
    public function codeSendSetedList() {
        $goodsIdList = $this->fj114Model->getGoodsList($_SESSION['Fj114']['phone_no']);
        $codeNameList = M('tgoods_info')->field('goods_name, id')
                ->where(array('id' => array('in', $goodsIdList),array('status' =>array('eq', 0))))//新加查询条件卡券是否停用
                ->select();
        $this->assign('codeNameList', $codeNameList);

        $codeList = M()->table("tfb_fjguaji_send_set tfss")
                ->join('tgoods_info tgi ON tgi.id = tfss.g_id')
                ->field('tfss.*, tgi.goods_name')
                ->where(array('tfss.shop_id' => $_SESSION['Fj114']['id'], 'tfss.is_delete'=>'0'))
                ->order('tfss.type DESC, tfss.start_time DESC')
                ->select();
        $this->assign('codeList', $codeList);
        $this->display();
    }
    
    /**
     * 删除设置的发送卡券
     */
    public function delSendList(){
        $listId = I('post.listId', 0, 'int');
        if($listId != 0){
            M('tfb_fjguaji_send_set')
                ->where(array('shop_id'=>$_SESSION['Fj114']['id'] , 'id'=>$listId))
                ->save(array('is_delete'=>'1'));
            $this->ajaxReturn(array('error'=>'0'));
        }else{
            $this->ajaxReturn(array('error'=>'1001'));
        }
    }

    /**
     * 卡券发送设置
     */
	public function codeSendSet() {
        $newData = array();
       
        $goodsId =  I('post.goodNum');
        if ($goodsId == '') {
            $this->ajaxReturn(array('resultCode' => '1001', 'resultTxt' => '请选择卡券'));
        }
        $checkGoodIdResult = $this->fj114Service->checkGoodId($goodsId, $_SESSION['Fj114']['phone_no']);
        self::ajaxResult($checkGoodIdResult);
        $newData['g_id'] = $goodsId;
        
        $cardId = I('post.card', '0', 'string');
        if($cardId != '' && $cardId != '0'){
            $cardType = M('tfb_fjguaji_send_set')->where(array('id'=>$cardId))->getfield('type');
            $cardType = get_val($cardType, '', '0');
            M('tfb_fjguaji_send_set')->where(array('id'=>$cardId, 'shop_id'=>$_SESSION['Fj114']['id']))->save($newData);
            $this->ajaxReturn(array('resultCode' => '0'));
        }else{
            $startTime = I('post.startTime');
            $startTime = dateformat($startTime, 'Ymd', '000000');
            if ($startTime == FALSE) {
                $this->ajaxReturn(array('resultCode' => '1001', 'resultTxt' => '开始时间格式错误'));
            }

            $endTime =  I('post.endTime');
            $endTime = dateformat($endTime, 'Ymd', '235959');
            if ($endTime == FALSE) {
                $this->ajaxReturn(array('resultCode' => '1001', 'resultTxt' => '结束时间格式错误'));
            }
            
            $checkCodeDateRepeatResult = $this->fj114Service->checkCodeDateRepeat($startTime, $endTime);
            self::ajaxResult($checkCodeDateRepeatResult);
            $newData['start_time'] = $startTime;
            $newData['end_time'] = $endTime;
            $newData['shop_id'] = $_SESSION['Fj114']['id'];
            $newSetId = M('tfb_fjguaji_send_set')->add($newData);
            if ($newSetId) {
                $this->ajaxReturn(array('resultCode' => '0'));
            } else {
                $this->ajaxReturn(array('resultCode' => '1002', '设置失败'));
            }
        }
    }
    

    /**
     * Epos设置
     */
    public function EposSet() {
        if (IS_POST) {
            $emailAddr = I('post.email');
            if (!check_str($emailAddr, array('strtype' => 'email')) || strpos($emailAddr, '@guaji.com')) {
                $this->ajaxReturn(array('resultCode' => '1001', 'resultTxt' => '请填写正确的邮箱')); 
            };

            $changeEmailResult = $this->fj114Service->changeShopEmail($emailAddr);
            self::ajaxResult($changeEmailResult);
            $this->ajaxReturn(array('resultCode' => '0'));
        } else {
            $condition = array();
            $condition['store_id'] = $_SESSION['Fj114']['store_id'];
            $condition['node_id'] = $_SESSION['Fj114']['node_id'];
            $email = M('tstore_info')->where($condition)->getfield('principal_email');
            if (strpos($email, '@guaji.com')) {
                $email = '';
            }
            $this->assign('email', $email);
            $this->display();
        }
    }

    /**
     * 卡券详情
     */
    public function myCardInfo() {
        $goodsId = I('get.id', '0', 'string');
        if ($goodsId != '0') {
            $cardInfo = $this->fj114Service->cardDetail($goodsId);
            $this->assign('cardInfo', $cardInfo);
        }
        $this->display();
    }

    /**
     * 上传图片
     */
    public function uploadImg() {
        $allowedTypes = array('image/jpg', 'image/jpeg', 'image/png', 'image/pjpeg', 'image/x-png');
        $exist = get_val($_FILES, 'img', '1101');
        if ($_FILES['img']['error'] != '0' || $exist == '1101') {
            $this->ajaxReturn(array('resultCode' => '1001', 'resultTxt' => '上传文件错误'));
            exit;
        } elseif ($_FILES['img']['size'] > 1024 * 1024) {
            $this->ajaxReturn(array('resultCode' => '1001', 'resultTxt' => '上传文件大于1M'));
            exit;
        } elseif (!in_array($_FILES['img']['type'], $allowedTypes)) {
            $this->ajaxReturn(array('resultCode' => '1001', 'resultTxt' => '文件类型不正确'));
            exit;
        }

        $uploadPath = APP_PATH . 'Upload/';
        $targetFile = $_SESSION['Fj114']['node_id'];
        if (!is_dir($uploadPath . $targetFile)) {
            mkdir($uploadPath . $targetFile);
        }

        $targetFile .= '/' . $_SESSION['Fj114']['store_id'];
        if (!is_dir($uploadPath . $targetFile)) {
            mkdir($uploadPath . $targetFile);
        }
        
        $imageName = md5(time() . $_FILES['img']['name']).'.jpg';
        $targetFile .= '/' . $imageName;
        if (move_uploaded_file($_FILES['img']['tmp_name'], $uploadPath . $targetFile)) {
            $this->ajaxReturn(array('resultCode' => '0', 'path' => $targetFile, 'url' => get_upload_url($targetFile)));
        } else {
            $this->ajaxReturn(array('resultCode' => '1001', 'resultTxt' => '移动文件失败'));
        }
    }

    private function ajaxResult($orgion) {
        if (is_array($orgion)) {
            $this->ajaxReturn($orgion);
            exit;
        }
    }

}
