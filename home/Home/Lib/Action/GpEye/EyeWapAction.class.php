<?php

class EyeWapAction extends BaseAction{

	public $_authAccessMap = '*';
	public $needCheckLogin = false;
	public $needCheckUserPower = false;
	public $openid='';
	public $wx_flag = 0;
    const SESSION_PREFIX = 'GPWAP_';

	public function _initialize() {
        parent::_initialize();
        //默认错误跳转对应的模板文件
        C('TMPL_ACTION_ERROR' , './Home/Tpl/Label/Public_error.html');
        C('TMPL_ACTION_SUCCESS' , './Home/Tpl/Label/Public_msg.html');
        $this->expiresTime = 120; // 手机发送间隔
        $this->CodeexpiresTime = 600; // 手机验证码过期时间
        $this->assign('expiresTime', $this->expiresTime);
        $this->node_id=C('GpEye.node_id');
        $this->assign('node_id', $this->node_id);
        // 判断是否是微信中打开 0 否 1是
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') === false) {
            $this->error('请用微信打开');
        }
        $wx_flag = 1;
        if(in_array(ACTION_NAME,array('treatmentRecordWap','customerFeedback'))){
            $cUrl = U('GpEye/EyeWap/'.ACTION_NAME,I('get'), false, false, true);
            session(self::SESSION_PREFIX.'c_url',$cUrl);
        }
        if(ACTION_NAME=='callbackAndRedirectByNodeId'){
            $this->callbackAndRedirectByNodeId();
        }
        $wechatUserInfo = $this->getWechatUserInfo($this->node_id);
    	if (empty($wechatUserInfo)) {
            $currentUrl = U('GpEye/EyeWap/'.ACTION_NAME, I('get.'), false, false, true);
            $get = I('get.');
            $get['node_id'] = $this->node_id;
            $apiCallbackUrl = U('GpEye/EyeWap/callbackAndRedirectByNodeId', $get, false, false, true);
            $this->wechatAuthorizeByNodeId($this->node_id, 0, $currentUrl, $apiCallbackUrl);
    	}
    	$openId = $wechatUserInfo['openid'];
    	$this->openid=$openId;
        //根据openid判断手机号绑定情况
        if(in_array(ACTION_NAME,array('treatmentRecordWap','customerFeedback'))){
            $r_open=M('tfb_gp_customer_login')->where(array('openid'=>$this->openid))->find();
            if($r_open&&$r_open['mobile']!=''){
                $r_mobile=$r_open['mobile'];
                $c_url=session(self::SESSION_PREFIX.'c_url');
                session(self::SESSION_PREFIX."groupPhone",$r_mobile);
                

            }else{
                $this->error("您尚未登录或登录已超时："."</br>"."您还可以:"."</br>" , 
                            array('请立即登录' => U('GpEye/EyeWap/loginPhone'))
                            );
            }
        }
        $wechatService = D('WeiXin', 'Service');
        $wechatConfig = array();
        $shareConfig['config'] = $wechatService->getWxShareConfig();
        $this->assign('shareData', $shareConfig);
        $this->wx_flag = $wx_flag;
        $this->assign('wx_flag', $wx_flag);
    }
    // 手机发送验证码
    public function sendCheckCode() {
        
        $phoneNo = I('post.phone', null);
        if (! check_str($phoneNo, 
            array(
                'null' => false, 
                'strtype' => 'mobile'), $error)) {
            $this->error("手机号{$error}");
        }
        
        // 测试环境不下发，验证码直接为1111
        if (! is_production()) {
            $groupCheckCode = array(
                'number' => 1111, 
                'add_time' => time(), 
                'phoneNo' => $phoneNo);
            session(self::SESSION_PREFIX.'groupCheckCode', $groupCheckCode);
            $this->ajaxReturn("success", "验证码已发送", 1);
        }
        
        // 发送频率验证
        $groupCheckCode = session(self::SESSION_PREFIX.'groupCheckCode');
        if (! empty($groupCheckCode) &&
             (time() - $groupCheckCode['add_time']) < $this->expiresTime) {
            $this->error('动态密码发送过于频繁!');
        }
        $num = mt_rand(1000, 9999);
        $exptime = $this->CodeexpiresTime / 60;
        $text = "您在{$this->node_short_name}商户的动态密码是：{$num}，有效期{$exptime}分钟。如非本人操作请忽略。";
        // 通知支撑
        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
                                                                   // 请求参数
        $req_array = array(
            'NotifyReq' => array(
                'TransactionID' => $TransactionID, 
                'ISSPID' => C('MOBILE_ISSPID'), 
                'SystemID' => C('ISS_SYSTEM_ID'), 
                'SendLevel' => '1', 
                'Recipients' => array(
                    'Number' => $phoneNo),  // 手机号
                'SendClass' => 'SMS', 
                'MessageText' => $text,  // 短信内容
                'Subject' => '', 
                'ActivityID' => C('ALIPAY_NOTICE_ACTIVITYID'), 
                'ChannelID' => '', 
                'ExtentCode' => ''));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $resp_array = $RemoteRequest->requestIssServ($req_array);
        $ret_msg = $resp_array['NotifyRes']['Status'];
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
             $ret_msg['StatusCode'] != '0001')) {
            $this->error('发送失败');
        }
        $groupCheckCode = array(
            'number' => $num, 
            'add_time' => time(), 
            'phoneNo' => $phoneNo);
        session(self::SESSION_PREFIX.'groupCheckCode', $groupCheckCode);
        $this->success('动态密码已发送');
    }
    
    // 登录
    public function loginPhone() {
        if($this->isPost()){
            $phoneNo = I('post.phone', null, 'mysql_real_escape_string');
            $wx_flag=I('wx_flag','');
            if (! check_str($phoneNo, 
                array(
                    'null' => false, 
                    'strtype' => 'mobile'), $error)) {
                $this->ajaxReturn(array(
                    'type' => 'phone'), "手机号{$error}", 0);
            }
            //验证手机号是否是客户
            $info=D('GpCustomer')->customerInfo(array('mobile'=>$phoneNo));
            if(!$info){
                $this->ajaxReturn(array('type'=>'phone'),'手机号不是客户登录手机号',0);
            }
            // 手机验证码
            $checkCode = I('post.check_code', null);
            if (! check_str($checkCode, array(
                'null' => false), $error)) {
                $this->ajaxReturn(array(
                    'type' => 'pass'), "动态密码{$error}", 0);
            }
            $groupCheckCode = session(self::SESSION_PREFIX.'groupCheckCode');
            if (! empty($groupCheckCode) && $groupCheckCode['phoneNo'] != $phoneNo)
                $this->ajaxReturn(array(
                    'type' => 'phone'), '手机号码不正确', 0);
            if (! empty($groupCheckCode) && $groupCheckCode['number'] != $checkCode)
                $this->ajaxReturn(array(
                    'type' => 'pass'), '动态密码不正确', 0);
            if (time() - $groupCheckCode['add_time'] > $this->CodeexpiresTime)
                $this->ajaxReturn(array(
                    'type' => 'pass'), '动态密码已经过期', 0);
                // 记录session
            session(self::SESSION_PREFIX.'groupPhone', $phoneNo);
            $data=array('openid'=>$this->openid,'mobile'=>$phoneNo);
            $s_info=M('tfb_gp_customer_login')->where(array('openid'=>$this->openid))->find();
            if($s_info['mobile']!=$phoneNo&&$s_info){
                $re=M('tfb_gp_customer_login')->where(array('openid'=>$this->openid))->delete();
                if(!$re){
                    $this->ajaxReturn(array(
                        'type' => 'openid'), '手机号与openid绑定失败', 0); 
                }else{
                    $rev=M('tfb_gp_customer_login')->add($data);
                    if(!$rev){
                    $this->ajaxReturn(array(
                        'type' => 'openid'), '手机号与openid绑定失败', 0);
                    }
                }
            }else{
                $rev=M('tfb_gp_customer_login')->add($data); 
                if(!$rev){
                    $this->ajaxReturn(array(
                        'type' => 'openid'), '手机号与openid绑定失败', 0); 
                }
            }
            if(!session(self::SESSION_PREFIX.'c_url')){
                $surl=U('GpEye/EyeWap/treatmentRecordWap','',false,false,true);
                $this->ajaxReturn(array('url'=>$surl,'info'=>'登录成功','status'=>1));
            }else{
                $url=session(self::SESSION_PREFIX.'c_url');
                $this->ajaxReturn(array('url'=>$url,'info'=>'登录成功','status'=>1));
            }
        }
        $this->display('EyeWap/loginPhone');
    }
    //门店单页wap
    public function storeInfoWap(){
    	$id=I('id',"");
    	if($id==''||$id==0){
    		$this->error('参数错误');
    	}
        //获取门店和技师信息
    	$technician=D('GpTechnician')->technicianInfo(array('a.merchant_id'=>$id,'a.status'=>0));
        $info=D('GpMerchant')->merchant_info(array('id'=>$id));
        if($info['status']=='2'){
            $this->error('该门店已解约');
        }elseif($info['status']=='1'){
            $this->error('该门店待审核中');
        }elseif($info['status']=='3'){
            $this->error('该门店未通过审核');
        }elseif(!$info){
            $this->error('该门店不存在');
        }
    	$this->assign('info',$info);
        $this->assign('store_pic',array_filter(json_decode($info['store_pic'],true)));
    	$this->assign('technician',$technician['list']);
    	$this->display('EyeWap/storeInfoWap');
    }
    //我的治疗记录wap
    public function treatmentRecordWap(){
        $phoneNo = session(self::SESSION_PREFIX."groupPhone");
        $info=D('GpCustomer')->customerInfo(array('mobile'=>$phoneNo));
    	if(!$info&&$phoneNo==''){
    		$this->error('参数错误');
    	}
        import('ORG.Util.Page'); // 导入分页类
        $count=D('GpTreatmentRecord')->myTreatmentCount(array('tc.mobile'=>$phoneNo));
        $Page = new Page($count, 10);
        $pageCount=10;//每页个数
        if ($_GET['p'] > ceil($count / 10) && $this->isAjax()) {
            return;
        }
        //根据客户信息获取我的治疗记录
        $nowPage = I('p', null, 'mysql_real_escape_string'); //页数
        empty($nowPage) ? $nowPage = 1 : $nowPage;
        $infos=M()->table('tfb_gp_treatment_record tr')->join('tfb_gp_technician tt on tt.id=tr.technician_id')->join('tfb_gp_customer tc on tc.id=tr.customer_id')->join('tuser_info ti on ti.user_id=tr.user_id')->field('tr.*,tt.name,tc.treatment_process,ti.true_name')->where(array('tc.mobile'=>$phoneNo))->order('tr.treatment_time desc')->limit(($nowPage - 1) * $pageCount, $pageCount)->select();
         //组装下一页url
        $nexUrl = U('GpEye/EyeWap/treatmentRecordWap', array('tc.mobile'=>$phoneNo, 'p' => ($nowPage + 1)),'','',true);
        $arr_f=array('未评价','已评价');
        $a=($nowPage-1)*10+1;
        $this->assign('arr_f', $arr_f);
        $this->assign('count', $count);
        $this->assign('nexUrl', $nexUrl);
    	$this->assign('info',$infos);
        $this->assign('a',$a);
    	$this->display('EyeWap/treatmentRecordWap');
    }
    //治疗记录评价页
    public function customerFeedback(){
        $keys=I('k','');
    	$id=I('id','');//治疗记录id
    	if($id==''||$id==0){
    		$this->error('参数错误');
    	}
    	if($this->isPost()){
            $info=D('GpTreatmentRecord')->selectTreatmentInfo(array('tr.id'=>$id));
            if($info['feed_status']==1){
                $this->error('该恢复记录已经评价');
            }
            $p_store=I('pfstore','');
            $keys=I('k','');
    		$data=array();
    		$data=array(
    			'feedback_num'=>date('YmdHis').sprintf("%03d", mt_rand(1,999)),
    			'merchant_id'=>I('merchant_id',''),
    			'customer_id'=>I('customer_id',''),
    			'technician_id'=>I('technician_id',''),
    			'record_id'=>$id,
    			'add_time'=>date('YmdHis'),
    			'memo'=>I('memo',''),
    			'mobile'=>I('phone',''),
    			'service_attitude'=>3-$p_store[1],
    			'service_result'=>3-$p_store[0],
    			);
    		$result=M('tfb_gp_customer_feedback')->add($data);
    		if($result){
                //更改我的治疗记录的评价状态
    			$re=M('tfb_gp_treatment_record')->where(array('id'=>$id))->save(array('feed_status'=>1));
                if($re){
                    $this->ajaxReturn(array('status'=>1,'info'=>'评价成功','url'=>U('GpEye/EyeWap/feedbackSuc',array('id'=>$id,'k'=>$keys))));
                }else{
                    $this->ajaxReturn(array('status'=>0,'info'=>'评价失败','url'=>U('GpEye/EyeWap/treatmentRecordWap')));
                }
    		}else{
    			$this->ajaxReturn(array('status'=>0,'info'=>'评价失败','url'=>U('GpEye/EyeWap/treatmentRecordWap')));
    		}
    	}
    	$info=D('GpTreatmentRecord')->selectTreatmentInfo(array('tr.id'=>$id));
        if($info['feed_status']==1){
            $url=U('GpEye/EyeWap/feedbackSuc',array('k'=>$keys,'id'=>$id),'','',true);
            header("Location:".$url);
        }
    	$mobile=D('GpCustomer')->getCustomerInfoById(array('id'=>$info['customer_id']));
        $arrr=array('体验期','恢复期','保养期');
    	$this->assign('arrr',$arrr);
        $this->assign('info',$info);
        $this->assign('id',$id);
        $this->assign('keys',$keys);
    	$this->assign('mobile',$mobile['mobile']);
    	$this->display('EyeWap/customerFeedback');
    }
    //评价成功页面
    public function feedbackSuc(){
        $keys=I('k','');
        $id=I('id','');
        if($id==''||$id==0){
            $this->error('参数错误');
        }
        $arr=array('优','良','差');
        $feed=D('GpCustomerFeedback')->customerFeedbackInfo(array('record_id'=>$id));
        $info=D('GpTreatmentRecord')->selectTreatmentInfo(array('tr.id'=>$id));
        $arrr=array('体验期','恢复期','保养期');
        $this->assign('arrr',$arrr);
        $this->assign('info',$info);
        $this->assign('arr',$arr);
        $this->assign('keys',$keys);
        $this->assign('feed',$feed);
        $this->display('EyeWap/feedbackSuc');
    }
    //邀请卡wap
    public function appointmentWap(){
        //邀请卡标题和内容
        $cardInfo=D('GpAppointment')->invitationCardInfo();
        //获取加盟商or商户信息
    	$merchant=D('GpMerchant')->merchantListWap(array('status'=>0));
    	if($this->isPost()){
    		$phoneNo = I('post.phone', null, 'mysql_real_escape_string');
        	if (! check_str($phoneNo, 
            	array(
                	'null' => false, 
                	'strtype' => 'mobile'), $error)) {
            	$this->ajaxReturn(array(
                	'type' => 'phone'), "手机号{$error}", 0);
        	}
        	// 手机验证码
        	$checkCode = I('post.check_code', null);
        	if (! check_str($checkCode, array(
            		'null' => false), $error)) {
            	$this->ajaxReturn(array(
                	'type' => 'pass'), "动态密码{$error}", 0);
        	}
        	$groupCheckCode = session(self::SESSION_PREFIX.'groupCheckCode');
        	if (! empty($groupCheckCode) && $groupCheckCode['phoneNo'] != $phoneNo)
            	$this->ajaxReturn(array(
                	'type' => 'phone'), '手机号码不正确', 0);
        	if (! empty($groupCheckCode) && $groupCheckCode['number'] != $checkCode)
            	$this->ajaxReturn(array(
                	'type' => 'pass'), '动态密码不正确', 0);
        	if (time() - $groupCheckCode['add_time'] > $this->CodeexpiresTime)
            	$this->ajaxReturn(array(
                	'type' => 'pass'), '动态密码已经过期', 0);
    		$data=array();
    		$data=array(
    			'merchant_id'=>I('merchant_id',''),
    			'name'=>I('name',''),
    			'mobile'=>I('phone',''),
    			'age'=>I('age',''),
    			'sex'=>I('sex',''),
    			'add_time'=>date('YmdHis'),
    			);
    		$result=M('tfb_gp_appointment')->add($data);
    		if($result){
                //open_Id与手机号存入登陆表中，给后期推送消息提供接收方
                $c_info=M('tfb_gp_customer_login')->where(array('openid'=>$this->openid))->find();
                if(!empty($c_info)){
                    if($c_info['mobile']!=$phoneNo){
                        $save_flag=M('tfb_gp_customer_login')->where(array('id'=>$c_info['id']))->save(array('mobile'=>$phoneNo));
                        if(!$save_flag){
                            $this->ajaxReturn(array('status'=>0,'info'=>'预约失败'));
                        }
                    }
                }else{
                    $add_flag=M('tfb_gp_customer_login')->add(array('openid'=>$this->openid,'mobile'=>$phoneNo));
                    if(!$add_flag){
                        $this->ajaxReturn(array('status'=>0,'info'=>'预约失败'));
                    }
                }
    			$this->ajaxReturn(array('status'=>1,'info'=>'预约成功'));
    		}else{
    			$this->ajaxReturn(array('status'=>0,'info'=>'预约失败'));
    		}
    	}
        $this->assign('cardInfo',$cardInfo);
    	$this->assign('merchant',$merchant);
    	$this->display('EyeWap/appointmentWap');
    }
    //通过门店id和客户手机号判断是否为新客户
    public function judgeByInfo(){
    	$merchant=I('merchant_id','');
    	$mobile=I('phone','');
    	$result=D('GpCustomer')->customerInfo(array('mobile'=>$mobile,'merchant_id'=>$merchant));
    	if($result){
    		$this->ajaxReturn(array('status'=>0,'info'=>'本活动仅支持新客户'));
    	}else{
            $this->ajaxReturn(array('status'=>1));
        }
    }
    //获取门店相关信息
    public function merchantInfoById(){
    	$id=I('id','');
    	if($id==''||$id==0){
    		$this->error('参数错误');
    	}
    	$merchant=D('GpMerchant')->merchantInfo(array('id'=>$id,'status'=>0));
        if($merchant){
            $this->ajaxReturn(array('status'=>1,'data'=>$merchant));
        }
    }
}