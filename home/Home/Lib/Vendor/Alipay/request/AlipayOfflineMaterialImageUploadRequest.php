<?php
/**
 * ALIPAY API: alipay.offline.material.image.upload request
 *
 * @author auto create
 * @since 1.0, 2016-03-01 17:41:20
 */
class AlipayOfflineMaterialImageUploadRequest
{
	/** 
	 * 图片二进制内容
	 **/
	private $imageContent;
	
	/** 
	 * 图片名称
	 **/
	private $imageName;
	
	/** 
	 * 用于显示指定图片所属的partnerId，只适用于特殊调用者，普通用户请勿使用此参数
	 **/
	private $imagePid;
	
	/** 
	 * 图片格式
	 **/
	private $imageType;

	private $apiParas = array();
	private $terminalType;
	private $terminalInfo;
	private $prodCode;
	private $apiVersion="1.0";
	private $notifyUrl;

	
	public function setImageContent($imageContent)
	{
		$this->imageContent = $imageContent;
		$this->apiParas["image_content"] = $imageContent;
	}

	public function getImageContent()
	{
		return $this->imageContent;
	}

	public function setImageName($imageName)
	{
		$this->imageName = $imageName;
		$this->apiParas["image_name"] = $imageName;
	}

	public function getImageName()
	{
		return $this->imageName;
	}

	public function setImagePid($imagePid)
	{
		$this->imagePid = $imagePid;
		$this->apiParas["image_pid"] = $imagePid;
	}

	public function getImagePid()
	{
		return $this->imagePid;
	}

	public function setImageType($imageType)
	{
		$this->imageType = $imageType;
		$this->apiParas["image_type"] = $imageType;
	}

	public function getImageType()
	{
		return $this->imageType;
	}

	public function getApiMethodName()
	{
		return "alipay.offline.material.image.upload";
	}

	public function setNotifyUrl($notifyUrl)
	{
		$this->notifyUrl=$notifyUrl;
	}

	public function getNotifyUrl()
	{
		return $this->notifyUrl;
	}

	public function getApiParas()
	{
		return $this->apiParas;
	}

	public function getTerminalType()
	{
		return $this->terminalType;
	}

	public function setTerminalType($terminalType)
	{
		$this->terminalType = $terminalType;
	}

	public function getTerminalInfo()
	{
		return $this->terminalInfo;
	}

	public function setTerminalInfo($terminalInfo)
	{
		$this->terminalInfo = $terminalInfo;
	}

	public function getProdCode()
	{
		return $this->prodCode;
	}

	public function setProdCode($prodCode)
	{
		$this->prodCode = $prodCode;
	}

	public function setApiVersion($apiVersion)
	{
		$this->apiVersion=$apiVersion;
	}

	public function getApiVersion()
	{
		return $this->apiVersion;
	}

}
