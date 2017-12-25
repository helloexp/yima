<?php
/**
 * 支撑通过流水号获取商品图片接口
 * @author bao
 *
 */
class QueryBarcodeImgageAction extends Action{
	
	private $reqData = array();//请求数据
	private $xml;//xml解析对象
		
	public function _initialize(){
		import('@.ORG.Util.Xml') or die('@.ORG.Util.Xml导入失败');
		$this->xml = new Xml();
		if(ACTION_NAME != 'test'){
			$reqData = file_get_contents("php://input");
			if(empty($reqData)) $this->response('0001');
			log_write('request:'.$reqData);
			$reArr = $this->xml->parse($reqData);
			$reArr = $this->xml->getAll();
			// 转换成 utf-8 编码
			array_walk_recursive($reArr, 'utf8Str');
			if ($this->xml->error()) $this->response('0002');
			$this->reqData = $reArr['QueryBarcodeImageReq'];
		}
	}
	
	public function index(){
		$baseUrl = C('TMPL_PARSE_STRING.__UPLOAD__');
		$reqSeq = $this->reqData['TransactionID'];
		if(empty($reqSeq)) $this->response('0003');
		//查找图片路径
		$imageInfo = M('tbarcode_trace a')->field('b.goods_image')->join("tgoods_info b ON a.goods_id = b.goods_id")->where("req_seq = '{$reqSeq}'")->find();
		if(empty($imageInfo['goods_image'])){
			$this->response('0004');
		}else{
			$imageUrl = $baseUrl.'/'.$imageInfo['goods_image'];
			$this->response('0000',array('url'=>$imageUrl));
		}
		
	}
	
	/**
	 * 应答信息
	 * @param string $errCode 错误码
	 * @param array $data 其他数据
	 */
	private function response($errCode,$data=array()){
		$reqData = array(
			'QueryBarcodeImageRes' => array(
					'TransactionID' => $this->reqData['TransactionID'],
					'StatusCode' => $errCode,
					'StatusText' => $this->getErrMsg($errCode),
					'ImageURL' => $data['url']
			)
		);
		$mxlData = $this->xml->getXMLFromArray($reqData,'gbk');//转换成gbk
		log_write('response:'.$mxlData);
		exit($mxlData);
	}
	
	private function getErrMsg($errCode){
		$errArr = array(
			'0000' => '成功',
			'0001' => '数据不能为空',
			'0002' => '解析xml数据失败',
			'0003' => '请求流水号参数不能为空',
			'0004' => '图片未找到'
		);
		return $errArr[$errCode];
	}
	
	public function test(){exit;
		$reqSeqId = '20160322100540026519';
		$url = 'www.wangcai.com/index.php?g=Interface&m=QueryBarcodeImgage&a=index';
		$reqArr = array(
			'QueryBarcodeImageReq' => array(
				'TransactionID' => $reqSeqId
			)
		);
		$xmlstr = $this->xml->getXMLFromArray($reqArr,'gbk');
		$ch = curl_init();
		// 超时时间
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		// 这里设置代理，如果有的话
		// curl_setopt($ch,CURLOPT_PROXY, '10.206.30.98');
		// curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlstr);
		$data = curl_exec($ch);
		$errorStr = curl_error($ch);
		$errorNo = curl_errno($ch);
		curl_close($ch);
		$arr = $this->xml->parse($data);
		$arr = $this->xml->getAll();
		array_walk_recursive($arr, 'utf8Str');
		dump($arr);exit;
	}
	
}