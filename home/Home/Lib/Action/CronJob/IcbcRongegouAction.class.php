<?php
/**
 * 工行融e购平台订单对接
 * g=CronJob&m=IcbcRongegou&a=xxx
 * 订单表:texternal_mall_order
 * 订单扩展表:texternal_mall_order_ex
 * @author bao
 *
 */
class IcbcRongegouAction extends Action{
	
	protected $version   = '1.0';   //接口版本
	protected $format    = 'xml';   //交互数据包格式
	protected $appKey    = '';      //接入系统标识
	protected $authCode  = '';      //授权码
	protected $appSecret = '';      //秘钥
	protected $apiUrl    = 'https://218.205.193.39:443/icbcrouter';      //请求地址
	protected $reXml     = '';
	
	public function _initialize(){
		$this->appKey = C('ICBC_RONGEGOU.APP_KEY');
		$this->authCode = C('ICBC_RONGEGOU.AUTH_CODE');
		$this->appSecret = C('ICBC_RONGEGOU.APP_SECRET');
		set_time_limit(0);
		//dump($this->getOrderDetail('020160229IM8617472,020160229IM8617471'));exit;
		
	}
	
	/**
	 * 查询订单(计划任务每3分钟查询一次)
	 * g=CronJob&m=IcbcRongegou&a=getOrderList&b=20160205&e=20160206
	 * 更改绑定关系需要清楚缓存data目录下
	 */
	public function getOrderList(){
		$bDate = I('b');//用于手动触发
		$eDate = I('e');
		$error = '';
		if (! check_str($bDate,array('null' => true,'strtype' => 'datetime'), $error)) {
			echo '开始日期格式错误';exit;
		}
		if (! check_str($eDate,array('null' => true,'strtype' => 'datetime'), $error)) {
			echo '结束日期格式错误';exit;
		}
		if($bDate > $eDate){
			echo '开始时间不能大于结束时间';exit;
		}
		$bDate = empty($bDate) ? date('Y-m-d H:i:s',time()-60*60) : date('Y-m-d H:i:s',strtotime($bDate)); //保证数据完整,多取一分钟前的数据
		$eDate = empty($eDate) ? date('Y-m-d H:i:s',time()) : date('Y-m-d H:i:s',strtotime($eDate)); 
		$reqData = array(
			'create_start_time' => $bDate, //保证数据完整,多取一分钟前的数据
			'create_end_time' => $eDate,
			'modify_time_from' => '',
			'modify_time_to' => '',
			'order_status' => '02' //只查待发货的订单
		);
		//dump($reqData);exit;
		$dataArr = $this->send($reqData,'icbcb2c.order.list');
		//dump($dataArr);exit;
		$orderList = $dataArr['response']['body']['order_list']['order'];
		if(empty($orderList)){
			echo '没有拉取的订单信息';exit;
		}
		//获取订单详情
		if(!isset($orderList[0])) $orderList = array($orderList);//变为二维数组
		foreach($orderList as $v){
			$orderDetail = $this->getOrderDetail($v['order_id']);//获取订单详情
			$orderData = array();    //texternal_mall_order入库数据
			if($orderDetail){
				//获取商品信息
				$productArr = $orderDetail['products']['product'];
				if(!isset($productArr[0])){//变为二维数组
					$productArr = array($productArr);
				}
				//texternal_mall_order数据处理
				$orderData['order_type']   = '1';
				$orderData['ext_order_id'] = $v['order_id'];
				$orderData['phone_no']     = $orderDetail['consignee']['consignee_mobile'];
				$orderData['order_amt']    = $orderDetail['order_amount'];
				//texternal_mall_order_ex数据处理
				foreach($productArr as $pv){
					$productData = array();  //texternal_mall_order_ex入库数据
					$productData['ext_order_id'] = $v['order_id'];
					$productData['ext_goods_id'] = $pv['product_id'];
					$productData['order_amt'] = $pv['product_price'];
					$productData['goods_num'] = $pv['product_number'];
					$productData['status'] = '0';
					//获取绑定信息(绑定信息配置错误的话,改完要清除缓存)
					$thirdPartyCache = S('thirdPartyCache');
					$bindInfo = $thirdPartyCache[$pv['product_id']];
					if(empty($bindInfo)){//为空去数据库查
						$where = array(
								'tp_goods_id' => $pv['product_id'],
								'check_status' => '1',
								'status' => '0',
						);
						$bindInfo = M('tsale_third_party')->field('node_id,goods_id')->where($where)->find();
						if($bindInfo){
							$thirdPartyCache[$bindInfo['tp_goods_id']] = $bindInfo;
							S('thirdPartyCache',$thirdPartyCache,'24*3600');//加入缓存
						}
					}
					$productData['g_id'] = $bindInfo['goods_id'];
					//数据库插入
					$resutl = M('texternal_mall_order_ex')->add($productData);
					if(!$resutl){
						log_write('add_data error:'.M()->getDbError());
						echo '数据插入失败,原因:'.M()->getDbError().'<br/><br/>';
					}
					$orderData['node_id'] = $bindInfo['node_id'];
					$orderData['status'] = empty($bindInfo) ? '9' : '0';
				}
			}
			if(empty($orderData)){//未查到详情信息处理
				$orderData = array(
					'order_type' => '1',
					'ext_order_id' => $v['order_id'],
					'status' => '9'
				);
			}
			//数据库插入
			$orderData['order_info'] = $this->reXml;
			$orderData['notice_flag'] = '0';
			$orderData['add_time'] = date('YmdHis');
			log_write('add_data:' . print_r($orderData,true));
			$resutl = M('texternal_mall_order')->add($orderData);
			if(!$resutl){
				log_write('add_data error:'.M()->getDbError());
				echo '数据插入失败,原因:'.M()->getDbError();
			}
			echo "订单{$v['order_id']}拉取完成<br/><br/>";
		}
		
	}
	
	/**
	 * 发码
	 * g=CronJob&m=IcbcRongegou&a=sendCode&order_id=aaaaaa,bbbbbb
	 */
	public function sendCode(){
		$p = 0;
		$orderId = I('order_id');
		do{
		  	$where = array(
				'order_type' => '1',
				'status' => '0',
			);
		  	if(!empty($orderId)) $where['ext_order_id'] = array('in',$orderId);
			$dataList = M('texternal_mall_order')->where($where)->limit($p*100,'100')->select();
			if(empty($dataList)){
				echo '没有发码数据或已完成!';exit;//没有数据中断执行
			}
			foreach($dataList as $k=>$v){
				//查找texternal_mall_order_ex数据
				$exDataList = M('texternal_mall_order_ex')->where("ext_order_id='{$v['ext_order_id']}'")->select();
				if(empty($exDataList)){
					echo "未找到订单{$v['ext_order_id']}的商品数据<br/>";
					continue;
				}
				$mStatus = '2';//处理状态  2-处理失败  1-已处理    有一个发码成功,状态即变为已处理
				//对订单下的商品发码
				foreach($exDataList as $ek=>$ev){
					if($ev['status'] == '1') continue;
					//获取电子券信息
					$goodsInfo = M()->table("tgoods_info g")->field('batch_no,goods_name,goods_type,goods_amt,print_text')->where("goods_id='{$ev['g_id']}'")->find();
					//获取电子券和商品绑定信息
					$gpBindInfo = M('tsale_third_party')->field('icbc_code_info')->where("goods_id='{$ev['g_id']}' and tp_goods_id='{$ev['ext_goods_id']}'")->find();
					$codeInfo = json_decode($gpBindInfo['icbc_code_info'],true);
					if(empty($codeInfo)){
						echo '发码信息不完整<br/>';
						break;
					}
					//判断是否已经创建了tmarketing_info和tbatch_info
					//M()->startTrans();
					$mtBindInfoCache = S('mtBindInfoCache');
					$isCreate = $mtBindInfoCache[$v['node_id'].$ev['g_id']];
					if(empty($isCreate)){//缓存为空去数据库查
						$where = array(
								'b.node_id' => $v['node_id'],
								'b.goods_id' => $ev['g_id'],
								'm.batch_type' => '2002'
						);
						$isCreate = M()->table("tbatch_info b")->field('b.id AS b_id,b.m_id AS m_id,b.storage_num')
						->join('tmarketing_info m ON m.id=b.m_id')
						->where($where)
						->find();
						if($isCreate){
							$mtBindInfoCache[$v['node_id'].$ev['g_id']]  = $isCreate;
							S('mtBindInfoCache',$mtBindInfoCache,'24*3600');//加入缓存
						}
						
					}
					
					$marketData['name'] = "融e购电子券-{$goodsInfo['goods_name']}";
					$marketData['batch_type'] = '2002';
					$marketData['node_id'] = $v['node_id'];
					$marketData['start_time'] = date('YmdHis');
					$marketData['end_time'] = date('YmdHis', strtotime('+10 year'));
					$marketData['add_time'] = date('YmdHis');
					if (empty($isCreate)) {
						$mId = M('tmarketing_info')->add($marketData);
						if (!$mId) {
							//M()->rollback();
							echo 'tmarketing_info数据创建失败'.M()->getDbError().'<br/>';exit;
						}
					} else {
						$mId = $isCreate['m_id'];
						$mIdEx = M('tmarketing_info')->where("id='{$mId}'")->save($marketData);
						if (false === $mIdEx) {
							//M()->rollback();
							echo 'tmarketing_info数据更新失败'.M()->getDbError().'<br/>';exit;
						}
					}
					
					
					// tbatch_info 插入数据
					$batchInfoData['batch_short_name'] = $goodsInfo['goods_name'];
					$batchInfoData['batch_no'] = $goodsInfo['batch_no'];
					$batchInfoData['node_id'] = $v['node_id'];
					$batchInfoData['batch_class'] = $goodsInfo['goods_type'];
					$batchInfoData['use_rule'] = $codeInfo['content'];//使用说明
					$batchInfoData['batch_amt'] = $goodsInfo['goods_amt'];
					$batchInfoData['info_title'] = '电子券';
					$batchInfoData['print_text'] = $goodsInfo['print_text'];
					$batchInfoData['begin_time'] = date('YmdHis');
					$batchInfoData['add_time'] = date('YmdHis');
					//验码时间
					$batchInfoData['verify_begin_date'] = $codeInfo['b_time'];
					$batchInfoData['verify_end_date'] = $codeInfo['e_time'];
					$batchInfoData['verify_begin_type'] = $codeInfo['time_type'];  //1是天数,0是日期
					$batchInfoData['verify_end_type'] = $codeInfo['time_type'];    //1是天数,0是日期
					$batchInfoData['end_time'] = date('YmdHis', strtotime('+10 year'));
					$batchInfoData['goods_id'] = $ev['g_id'];
					$batchInfoData['m_id'] = $mId;
					$batchInfoData['storage_num'] = $ev['goods_num'];
					$batchInfoData['remain_num'] = 0;
					$batchInfoData['user_id'] = '';
					if (empty($isCreate)) {
						$bId = M('tbatch_info')->add($batchInfoData);
						if (!$bId) {
							//M()->rollback();
							echo 'tbatch_info数据创建失败'.M()->getDbError().'<br/>';exit;
						}
					} else {
						$bId = $isCreate['b_id'];
						$bIdEx = M('tbatch_info')->where("id='{$bId}'")->save($batchInfoData);
						if (false === $bIdEx) {
							//M()->rollback();
							echo 'tbatch_info数据更新失败'.M()->getDbError().'<br/>';exit;
						}
					}
					//扣库存
					$goodsModel = D('Goods');
					$reduc = $goodsModel->storagenum_reduc($ev['g_id'],$ev['goods_num'], '工行商品销售', '19');
					if (!$reduc) {
						//M()->rollback();
						echo '库存扣减失败:' . $goodsModel->getError().'<br/>';exit;
					}
					//M()->commit();
					//发码
					import("@.Vendor.SendCode");
					$req = new SendCode();
					$successNum = 0;
					$reqId = '';
					for($i=1;$i<=$ev['goods_num'];$i++){
						$requestId = get_request_id();
						$resp = $req->wc_send($v['node_id'],'',$goodsInfo['batch_no'],$v['phone_no'],0,$requestId,null,$bId,null);
						//$resp = true;
						if ($resp != true) {
							echo $v['phone_no'].'发送失败:'.$resp.'<br/>';
						}else{
							$reqId .= $requestId.',';
							$successNum++;
						}
					}
					//更新发送状态
					if($successNum > 0){//成功一条即已处理
						$mStatus = '1';
						$sData = array(
							'status' => '1',
							'request_id' => substr($reqId,0,-1)
						);
						$resutl = M('texternal_mall_order_ex')->where("ext_order_id='{$ev['ext_order_id']}'")->save($sData);
						if(!$resutl){
							echo 'texternal_mall_order_ex数据更新失败' . M()->getDbError() .'<br/>';
						}
					}
				}
				//更改订单处理状态
				$sData = array(
					'status' => $mStatus
				);
				$resutl = M('texternal_mall_order')->where("ext_order_id='{$v['ext_order_id']}'")->save($sData);
				if(!$resutl){
					echo 'texternal_mall_order数据更新失败' . M()->getDbError() .'<br/>';
				}
			}
			$p++;
		}while(true);
	}
	
	/**
	 * 同步通知
	 * g=CronJob&m=IcbcRongegou&a=sendNotice&order_id=aaaaaa,bbbbbb
	 */
	public function sendNotice(){
		$p = 0;
		$orderId = I('order_id');
		do{
			$where = array(
				'order_type' => '1',
				'status' => '1',
				'notice_flag' => '0'
			);
			if(!empty($orderId)) $where['ext_order_id'] = array('in',$orderId);
			$dataList = M('texternal_mall_order')->where($where)->limit($p*100,'100')->select();
			if(empty($dataList)){
				echo '没有通知数据或已完成!';exit;//没有数据中断执行
			}
			foreach($dataList as $k=>$v){
				$reqData = array(
					'order_id' => $v['ext_order_id'],
					'logistics_company' => '0000000736',                  //物流公司编码(写死)
					'shipping_code' => substr($v['ext_order_id'],-12),  //运单号
					'shipping_time' => date('Y-m-d H:i:s',time()),         //发货时间
				);
				$dataArr = $this->send($reqData,'icbcb2c.order.sendmess');
				//dump($dataArr);exit;
				$resutlInfo = $dataArr['response']['head'];
				$sData = array(
					'notice_flag' => '1'
				);
				if($resutlInfo['ret_code'] !== '0'){//通知失败
					echo "{$v['ext_order_id']}通知接口返回信息:".$resutlInfo['ret_msg'].'<br/>';
					log_write("{$v['ext_order_id']}通知接口返回信息:".$resutlInfo['ret_msg']);
					$sData['notice_flag'] = '2';
				}
				$resutl = M('texternal_mall_order')->where("ext_order_id='{$v['ext_order_id']}' and order_type='1'")->save($sData);
				//echo M()->getLastSql();exit;
				if($resutl === false){
					echo 'texternal_mall_order数据更新失败' . M()->getDbError() .'<br/>';
					exit;
				}
				echo "{$v['ext_order_id']}状态更新成功<br/><br/>";
			}
			$p++;
		}while(true);
	}
	
	/**
	 * 获取订单详情
	 * @param str $orderIdStr 订单号  '12345,45678'
	 * g=CronJob&m=IcbcRongegou&a=getOrderDetail&order_id=020160229IM8617472
	 */
	public function getOrderDetail($orderStr=null){
		$reqData = array(
				'order_ids' => empty($orderStr) ? I('order_id') : $orderStr
		);
		$dataArr = $this->send($reqData,'icbcb2c.order.detail');
		$orderDetailList = $dataArr['response']['body']['order_list']['order'];
		//dump($orderDetailList);exit;
		if(empty($orderDetailList)){
			return false;
		}else{
			return $orderDetailList;
		}
	}
	
	
	/**
	 * 计算签名
	 * @param xml $reqData req_data请求参数
	 */
	public function hashSign($reqData){
		$content = "app_key=" . $this->appKey . "&auth_code=" . $this->authCode . "&req_data=" . $reqData;
		$hash_hmac = hash_hmac('sha256', mb_convert_encoding($content, 'utf-8'), mb_convert_encoding($this->appSecret, 'utf-8'),true);
		return base64_encode($hash_hmac);
	}
	
	/**
	 * 构建req_data的xml数据
	 * @param array $reqData
	 */
	public function arrToXml($reqData){
		import('@.ORG.Util.Xml') or die('@.ORG.Util.Xml导入失败');
		$xml = new Xml();
		$xmlStr = $xml->_getXMLFromArray($reqData);
		$str = '<?xml version="1.0" encoding="utf-8"?><body>'.$xmlStr.'</body>';
		return $str;
	}
	
	/**
	 * xml数据转换数组
	 * @param xml $xml
	 */
	public function xmlToArr($xmlStr){
		import('@.ORG.Util.Xml') or die('@.ORG.Util.Xml导入失败');
		$xml = new Xml();
		$xml->_encoding = 'utf-8';
		$arr = $xml->parse($xmlStr);
        $arr = $xml->getAll();
        return $arr;
	}
	
	/**
	 * 发送报文
	 * @param array $reqData req_data请求数据
	 * @param string $method 接口类型
	 */
	public function send($reqData,$method){
		$reqData = $this->arrToXml($reqData);
		$sign = $this->hashSign($reqData);
		$reqSid = $this->getReqId();
		$sendData = array(
			'sign' => $sign,
			'timestamp' => date('Y-m-d H:i:s'),
			'version' => $this->version,
			'app_key' => $this->appKey,
			'method' => $method,
			'format' => $this->format,
			'req_sid' => $reqSid,
			'auth_code' => $this->authCode,
			'req_data' => $reqData
		);
		// 创建post请求参数
		import('@.ORG.Net.FineCurl') or die('[@.ORG.Net.FineCurl]导入包失败');
		$socket = new FineCurl();
		$socket->setopt('URL',$this->apiUrl);
		$socket->setopt('TIMEOUT','30');
		$socket->setopt('HEADER_TYPE','POST');
		//$certFile = $this->createCertFile($this->apiclientCert,$this->apiclientKey);
		//$socket->setopt('CURLOPT_SSLCERT',$certFile);//设置证书
		if (is_array($sendData)) {
			$data = http_build_query($sendData);
		}
		//dump($data);exit;
		log_write('request:' . $this->apiUrl . ' POST:' . $data, 'REMOTE');
		$result = $socket->send($data);
		$error  = $socket->error();
		// 记录日志
		if ($error) {
			log_write($error, 'ERROR');
		}
		log_write('response:' . $result, 'REMOTE');
		$this->reXml = $result;
		return $this->xmlToArr($result);
	}
	
	/**
	 * 活动流水号
	 */
	public function getReqId(){
		return date('ymd') . substr(time(), -5) . substr(microtime(), 2, 5);
	}
	
	/**
	 * 创建证书文件
	 *
	 * @param $apiclientCert //微信商户证书
	 * @param $apiclientKey //证书秘钥
	 */
	protected function createCertFile($apiclientCert, $apiclientKey) {
		$fileName = RUNTIME_PATH . 'redpack/' . $this->nodeId . '.pem';
		if (! is_file($fileName) || ceil((time() - filemtime($fileName)) / 3600) > 24) { // 24小时重新生成文件
			$dir = dirname($fileName);
			// 目录不存在则创建
			if (! is_dir($dir)) mkdir($dir, 0755, true);
			$result = file_put_contents($fileName,$this->apiclientCert . $this->apiclientKey);
			if (! $result) {
				$this->error = '证书写入文件失败';
				log_write("WeixinRedPackLog:{$this->nodeId},证书写入文件失败");
				return false;
			}
		}
		return $fileName;
	}
	
}