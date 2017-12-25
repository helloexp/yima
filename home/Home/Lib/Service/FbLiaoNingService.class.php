<?php

class FbLiaoNingService 
{
	private $node_id;
	private $wx_serv;
	private $wx_qrcode_serv;
	public  $accessToken;

	/**
	 * 通过机构号获取事件列表
	 * @param  [char] $node_id 机构号
	 * @return null|array
	 */
	public function getEventListByNodeId($node_id)
	{
		$config=C('WEIXIN_MENU_EVENTS');
		$list=$config['list'];
		$event_list=$config[$node_id];
		$node_event_list=[];
		if($event_list){
			foreach($event_list as $row){
				$node_event_list[$row]=$list[$row];
			}
		}
		return $node_event_list;
	}

	/**
	 * 校验是否有我的二维码事件功能
	 * @param  [char] $node_id 机构号
	 * @return bool
	 */
	public function checkMyQrCodeByNodeId($node_id)
	{
		$config=$this->getEventListByNodeId($node_id);
		if(empty($config['myqrcode'])){
			return false;
		}
		//校验成功后才能赋值，调用功能时只能校验 node_id 就可以
		$this->node_id=$node_id;
		return true;
	}

	/**
	 * 我的二维码
	 */
	public function myQrCode($resp)
	{	
		if(empty($this->node_id)){
			return false;
		}
		$this->_log("【{$this->node_id}】我的二维码事件",$resp);
		$match="^".CommonConst::WEIXIN_MENU_MYQRCODE_KEY."{1}[\d]+";
		if(preg_match("/".$match."/",$resp['EventKey'])){
			$menu_id=str_replace(CommonConst::WEIXIN_MENU_MYQRCODE_KEY,"",$resp['EventKey']);
		}
		
		if(empty($menu_id)){
			return false;
		}
		//校验菜单有效性
		$config=C('WEIXIN_MENU_EVENTS');
		$where=array(
			'node_id'=>$this->node_id,
			'id'=>$menu_id,
			'response_class'=>$config['list']['myqrcode']['response_class'],
			);
		$check=M('twx_menu')->where($where)->count('id');

		if(empty($check)){
			$this->_log("菜单不存在",$where);
			return false;
		}
		$where=array(
			'openid'=>$resp['fromUserName'],
			'node_id'=>$this->node_id,
			);
		$info=D('FbMyQrCode')->getMyQrCodeByWhere($where);
		if(empty($info['media_id'])){
			$url=U("Label/MyQrCode/index","","","",true);
			$_resp=array(
				'Articles'=>
					array(
						array(
						'title' => "申请推广二维码", 
				        'description' => "点击申请推广二维码", 
				        'picurl' => "", 
				        'url' => htmlspecialchars_decode($url)
				        ),
					),
				);
		}else{
			$_resp=array(
				'mediaId'=>$info['media_id'],
				);
		}
		$resp=array_merge($resp,$_resp);
		return $resp;
	}

	/**
	 * 日志
	 * @param  [type] $mome [description]
	 * @param  [type] $info [description]
	 * @return [type]       [description]
	 */
	public function _log($mome,$info=[])
	{
		$msg="微信菜单自定义事件：".$msg;
		if(!empty($mome)){
          if(!empty($info)){
              $info=var_export($info,true);
          }
          $msg.=$mome;
          $info and $msg.=$info;
      	}
      $msg.="最后操作SQL：".M()->_sql();
      log_write($msg);
      return;
	}

	/**
	 * 设置微信二维码服务类
	 * @param  $node_id 机构号
	 */
	public function setWeiXinQrcodeServByNodeId($node_id='')
	{
		$node_id=$node_id?$node_id:$this->node_id;
		if(empty($node_id)){
			return false;
		}
        // 查询公众账号配置
        $weixinInfo = M('tweixin_info')->where(
            array(
                'node_id' => $node_id))->find();
        // 1.校验公众账号
        if (! $weixinInfo || ! $weixinInfo['app_id'] ||
             ! $weixinInfo['app_secret']) {
        	return false;
        }
        
        // 去微信获取token
        $wxService = D('WeiXinQrcode', 'Service');
        $wxService->init($weixinInfo['app_id'], $weixinInfo['app_secret'], 
            $weixinInfo['app_access_token']);
        $this->wx_qrcode_serv=$wxService;
        $this->accessToken=$wxService->accessToken;
        return $wxService;
	}

	/**
	 * 设置微信服务类
	 * @param  $node_id 机构号
	 */
	public function setWeiXinServByNodeId($node_id='')
	{
		$node_id=$node_id?$node_id:$this->node_id;
		if(empty($node_id)){
			return false;
		}
        // 查询公众账号配置
        $weixinInfo = M('tweixin_info')->where(
            array(
                'node_id' => $node_id))->find();
        // 1.校验公众账号
        if (! $weixinInfo || ! $weixinInfo['app_id'] ||
             ! $weixinInfo['app_secret']) {
        	return false;
        }
        
        // 去微信获取token
        $wxService = D('WeiXin', 'Service');
        $wxService->init($weixinInfo['app_id'], $weixinInfo['app_secret'], 
            $weixinInfo['app_access_token']);
        $this->wx_serv=$wxService;
        $this->accessToken=$wxService->accessToken;
        return $wxService;
	}

	 /**
     * 获取二维码 data：scene_id:场景号
     */
    public function createQrcodeImg($data) {
        $this->setWeiXinQrcodeServByNodeId($data['node_id']);
        if(empty($this->wx_qrcode_serv)){
        	return false;
        }

        $data = array(
            'action_info' => array(
                'scene' => array(
                    'scene_id' => $data['scene_id']))); // 场景值ID，
        
        $data = $this->wx_qrcode_serv->formatJson($data, 
            array(
                'expire_seconds' => 2592000,  // 该二维码有效时间，以秒为单位。 最大不超过1800。
                'action_name' => 'QR_SCENE',  // 二维码类型，QR_SCENE为临时,QR_LIMIT_SCENE为永久
                'action_info' => array(
                    'scene' => array('scene_id'=>1)))); // 场景值ID，
        
        $apiUrl = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=';
        
        $sendResult = $this->wx_qrcode_serv->send($data, $apiUrl);
        for ($i = 0; $i < 3; $i ++) {
            if (! $this->wx_qrcode_serv->accessToken) {
                $tokenResult = $this->wx_qrcode_serv->getToken();
                // 获取token失败
                if (! $tokenResult || $tokenResult['errcode'] != '0') {
                    return array(
                        'status' => '0', 
                        'info' => $tokenResult['errmsg']);
                }
            }
            $accessToken = $this->wx_qrcode_serv->accessToken;
            $error = '';
            Log::write('---第:' . $i . '次请求---');
            $result_str = httpPost($apiUrl . $accessToken, $data, $error, 
                array(
                    'TIMEOUT' => 30));
            Log::write($result_str, $this->wx_qrcode_serv->thisClassName);
            $result = json_decode($result_str, true);
            // 如果没有获取到，睡一秒继续来一次
            if (! $result) {
                continue;
            }
            // token失效的话再获取一次
            $result['errcode'] = $this->wx_qrcode_serv->accessToken == 'error' ? '42001' : $result['errcode'];
            if ($result['errcode'] == '42001' || $result['errcode'] == '40001') {
                Log::write("重新获取微信token");
                $tokenResult = $this->wx_qrcode_serv->getToken();
                // 获取token失败
                if (! $tokenResult || $tokenResult['errcode']) {
                    Log::write("重新获取微信token失败");
                    return $tokenResult;
                }
                continue;
            }
            // 查看状态
            break;
        }

        if (! $result || $result['errcode'] != 0) {
            $this->_log("获取二维码失败",$result);
            return false;
        } else {
            // 获取到图片
          /*  $ticket = $result['ticket'];
            $ticket_url = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=' .
                 $ticket;
            $img_content = $this->wx->httpGet($ticket_url, '', $this->error, 
                array(
                    'METHOD' => 'GET'));*/
           return $result['url'];
        }
    }
    
    /**
     * 创建二维码
     * @param  array  $param['img_info']  二维码内容 $param['apply_time'] 日期
     * @param  string  $img_path 保存为文件
     * @param  boolean $is_down 是否下载
     * @return boolean 
     */
    public function createCodeByString($param,$img_path="",$is_down=false)
    {
        if(empty($param['img_info'])
        || empty($param['apply_time'])){
            return false;
        }
        import('@.Vendor.phpqrcode.phpqrcode') or die('include file fail.');
        $c=C('WEIXIN_MENU_EVENTS.list');
        $bk=$c['myqrcode']['background'];
        if(!file_exists($bk)){
            return false;
        }
        $path=C('Upload')."lnsy/";
        if(!is_dir($path)){
        	if (! mkdir($path, 0777, true)) {
				return false;
			} 
        }
        $temp_img=$path.md5(uniqid(microtime(true))).".png";
        QRcode::png((string)$param['img_info'], $temp_img, 0, 200, $margin = 1,false);
        if(!file_exists($temp_img)){
           return false;
        }
        $bk_reso=imagecreatefromjpeg($bk);
        $img_reso=imagecreatefrompng($temp_img);
        $bw=imagesx($bk_reso);
        $bh=imagesy($bk_reso);
        $iw=imagesx($img_reso);
        $ih=imagesy($img_reso);
        $imgdest   =    imagecreatetruecolor ( $bw, $bh );
        imagecopyresampled ( $imgdest, $bk_reso, 0, 0, 0, 0 , $bw, $bh, $bw, $bh ) ;
        imagecopyresized ( $imgdest, $img_reso,187, 321, 0, 0 , 268, 268, $iw, $ih) ;
        $bigfont = "./Home/Public/Image/res/arial.ttf";
        $color = imagecolorallocate($imgdest, 0, 0,0);
        $date=explode('/',date('Y/m/d',($param['apply_time']+(721*3600))));
        $y=$date[0];
        $m=$date[1];
        $d=$date[2];     
        imagettftext($imgdest, 18, 0, 420, 965.5, $color, $bigfont,$y ); // 文字位置
        imagettftext($imgdest, 18, 0, 504, 965.5, $color, $bigfont,$m ); // 文字位置
        imagettftext($imgdest, 18, 0, 560, 965.5, $color, $bigfont,$d ); // 文字位置
        @unlink($temp_img);
        if($img_path){
        	$res=imagejpeg($imgdest,$img_path);
        }else{
        	 if($is_down){
	            $filename = iconv('utf-8', 'gbk', $c['myqrcode']['name'].".png");
	            header('Content-Description: File Transfer');
	            header('Content-Type: application/octet-stream');
	            header(
	                'Content-Disposition: attachment; filename=' .
	                     $filename);
	        }else{
	            header("content-type:image/png");
	        }
        	$res=imagejpeg($imgdest);
        }
        ImageDestroy($imgdest);
        return $res;
    }

}