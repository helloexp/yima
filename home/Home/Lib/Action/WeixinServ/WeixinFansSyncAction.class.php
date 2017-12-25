<?php
//指令接口服务设置
class WeixinFansSyncAction extends BaseAction{
	protected $wx;
	public $node_id;
	public $req;
	public $token;
	public $access_token;
	public $app_id;
	public $app_secret;
	public $user_name;
	public $response_msg_id;
	public $node_wx_id;
	public $scene_id;
	public $msg_type;
	public $msg_info;
	public $weixin_info;
    public $fansGroupService;
    private $groupMapArray = array();
    private $fansGroupSyncType = 1; // 1-以微信为准进行同步  2-以旺财为准进行同步
	/*
		{"location":{"location_flag":"1","resp_count":"3","large_image":"00004488top.jpg","small_image":"00004488item.jpg"}}
	*/
	public $setting = array();

	public function _initialize(){
		C('WeixinServ',require(CONF_PATH.'configWeixinServ.php'));
		if(C('WeixinServ.LOG_PATH')) C('LOG_PATH',C('WeixinServ.LOG_PATH')."SYNC_"); //重新定义目志目录

	}


	public function getAllFansList(){
        set_time_limit(0);
        ini_set('memory_limit','2048M');
		//检查同步标志
		$sync_flag = M('tsystem_param')->where("param_name ='WX_SYNC_FLAG'")->find();
		if (!$sync_flag){
			$this->log("get sync_flag not exit" );
			return;
		}

		if ($sync_flag['param_value'] != '1'){
			$this->log("the sync_flag is syncing" );
			return;
		}

		//更新同步标志
		$sync_flag_save['param_value'] = '2';
		$rs = M('tsystem_param')->where("param_name ='WX_SYNC_FLAG'")->save($sync_flag_save);
		if ($rs === false){
			$this->log("update sync_flag fail". M()->_sql() );
			return;
		}
		//获取所有未同步粉丝数据的微信机构
		$weixin_info_list = M('tweixin_info')->where("app_id IS NOT NULL AND getfans_flag in ('0', '4')")->select();
        if(!$weixin_info_list) $weixin_info_list = array();
		foreach($weixin_info_list as $weixin_info){
            $this->log("get fanslist  :[".$weixin_info['node_id']."]");
            if ($weixin_info['app_id'] == null){
                $this->log("同步参数未配置 :".$weixin_info['node_id'] );
                //更新状态为同步失败
                $weixin_info_save['getfans_flag'] = '3';
                M('TweixinInfo')->where("node_id='".$weixin_info['node_id']."'")->save($weixin_info_save);
                continue;
            }
			$this->node_id = $weixin_info['node_id'];
			$this->weixin_info = $weixin_info;
			$_REQUEST['node_id'] = $weixin_info['node_id'];
			$this->getFansListNew();
		}
        unset($weixin_info);
		//更新完成恢复同步标志
		$sync_flag_save['param_value'] = '1';
		$rs = M('tsystem_param')->where("param_name ='WX_SYNC_FLAG'")->save($sync_flag_save);
		if (!$rs){
			$this->log("update sync_flag fail". M()->_sql() );
			return;
		}

	}
	public function getFansListNew()
	{
		$this->wx = D('WeiXin','Service');

        $this->fansGroupService = D('WeiXinFansGroup','Service');
        $this->fansGroupService->init($this->node_id);

        //判断同步方式
        if ($this->weixin_info['getfans_flag'] == '4'){ 
            $this->fansGroupSyncType = '2';
        }

		//更新状态为同步中
		$weixin_info_save['getfans_flag'] = '2';
		$rs = M('TweixinInfo')->where("node_id='".$this->node_id."'")->save($weixin_info_save);
		if ( $rs === false){
			$this->log("保存标志失败 :".$this->node_id );
			return;
		}

		$this->log("start sync weixin fans :".$this->node_id );

		$this->access_token = $this->weixin_info['app_access_token'];
		$this->app_id= $this->weixin_info['app_id'];
		$this->app_secret = $this->weixin_info['app_secret'];
		$this->node_wx_id = $this->weixin_info['node_wx_id'];
        //设置只是为了跳过原有的service里面的一些校验
		if ($this->app_secret == null){
			$this->app_secret = '1';
		}
        //调用一次获取列表接口用来判断总数是否一致
		$openid_list = $this->wx->getFansList($this->access_token,'');
		if($openid_list['errcode'] == '40001'||$openid_list['errcode'] == '42001' ||$openid_list['errcode'] == '41001')//需要更新access_token
		{
			$this->wx->getAccessToken($this->app_id,$this->app_secret);
            $this->access_token = $this->wx->accessToken;

			$wx_info['app_access_token'] = $this->access_token ;
			M('TweixinInfo')->where("node_id='".$this->node_id."'")->save($wx_info);

			$openid_list = $this->wx->getFansList($this->access_token,'');
		}

        $weixin_info_save['getfans_flag'] = '1';

        if (isset($openid_list['errcode']) && $openid_list['errcode'] != 0 ){
            //更新状态为同步失败
            $this->log("get fanslist no data error:[".$openid_list['errcode']."]");
            $weixin_info_save['getfans_flag'] = '3';
            M('TweixinInfo')->where("node_id='".$this->node_id."'")->save($weixin_info_save);
            return;
        }
        //校验粉丝总数
        $fansSyncFlag = $this->checkFansCount($openid_list['total']);
        //总数一致的情况下,校验粉丝分组 如果都一致就不同步
        if ($fansSyncFlag){
            $fansSyncFlag = $this->checkFansGroupCount();
        }
        if ($fansSyncFlag){
            //更新状态为同步结束
            M('TweixinInfo')->where("node_id='".$this->node_id."'")->save($weixin_info_save);
            $this->log("end sync weixin fans all:".$this->node_id );
            return;
        }

        //确定将进行分组同步
         //清空微信用户备份表
        $sql = "delete from twx_user_bak where node_id ='".$this->node_id."' and node_wx_id = '".$this->node_wx_id."'";
        $rs = M()->execute($sql);
        if($rs === false)
        {
            $this->log("清空微信用户表 error :".$this->node_id );
            //更新状态为同步失败
            $weixin_info_save['getfans_flag'] = '3';
            $rs = M('TweixinInfo')->where("node_id='".$this->node_id."'")->save($weixin_info_save);
            return;
        }
        //将用户数据备份至备份表
        $sql = "REPLACE  INTO twx_user_bak  SELECT * FROM twx_user t  where t.node_id ='".$this->node_id."' and node_wx_id = '".$this->node_wx_id."'";
        $rs = M()->execute($sql);
        if($rs === false)
        {
            $this->log("将用户数据备份至备份表 error :".$this->node_id );
            //更新状态为同步失败
            $weixin_info_save['getfans_flag'] = '3';
            M('TweixinInfo')->where("node_id='".$this->node_id."'")->save($weixin_info_save);
            return;
        }

        //清空微信用户表
        $sql = "delete from twx_user where node_id ='".$this->node_id."' and node_wx_id = '".$this->node_wx_id."'";
        $rs = M()->execute($sql);
        if($rs === false)
        {
            $this->log("清空微信用户表 error :".$this->node_id );
            //更新状态为同步失败
            $weixin_info_save['getfans_flag'] = '3';
            $rs = M('TweixinInfo')->where("node_id='".$this->node_id."'")->save($weixin_info_save);
            return;
        }
        //对分组进行处理
        if ($this->fansGroupSyncType == '2'){//以旺财为准进行分组同步
            //把黑名单数据从备份表里拉回来
            $sql = "REPLACE  INTO twx_user  SELECT * FROM twx_user_bak t  where t.node_id ='".$this->node_id."' and node_wx_id = '".$this->node_wx_id."' and t.group_id = '1'";
              $rs = M()->execute($sql);
            if($rs === false)
            {
                $this->log("把黑名单数据从备份表里拉回来 error :".$this->node_id );
                //更新状态为同步失败
                $weixin_info_save['getfans_flag'] = '3';
                $rs = M('TweixinInfo')->where("node_id='".$this->node_id."'")->save($weixin_info_save);
                return;
            }

            if($openid_list['count'] > '0') {
                foreach ($openid_list['data']['openid'] as &$this->user_name) {
                    $this->fansAddUpdateByOpenID();
                }
            }

            while(($openid_list['next_openid'] != null) && strlen($openid_list['next_openid'])>0){
                $openid_list = $this->wx->getFansList($this->access_token, $openid_list['next_openid']);

                if (isset($openid_list['errcode']) && $openid_list['errcode'] != 0 ){
                    //更新状态为同步失败
                    $this->log("get fanslist no data error:[".$openid_list['errcode']."]");
                    $weixin_info_save['getfans_flag'] = '3';
                    M('TweixinInfo')->where("node_id='".$this->node_id."'")->save($weixin_info_save);
                    return;
                }

                if($openid_list['count'] > '0')
                {
                    foreach($openid_list['data']['openid'] as &$this->user_name)
                    {
                        $this->fansAddUpdateByOpenID();
                    }
                }
            };
            $this->log("end sync weixin fans only openid :".$this->node_id );
            //对本次同步进来的数据进行处理 nickname如果不为空有可能是新关注的数据
            $wx_user_list = M('twx_user')->where("node_id ='".$this->node_id."' and node_wx_id = '".$this->node_wx_id."' and  nickname is null")->select();
            foreach($wx_user_list as $wx_user_info){
                $this->user_name = $wx_user_info['openid'];
                $this->fansAddUpdate();
            }
            unset($wx_user_info);

            $this->log("sync by wangcai fans all:".$this->node_id );
            if (!$this->fansGroupService->wangcaiGroupSyncToWeixin()){
                $this->log("wangcaiGroupSyncToWeixin error :".$this->node_id );
                $weixin_info_save['getfans_flag'] = '3';
            }else{
                if (!$this->fansGroupService->wangcaiFansGroupsBatchSyncToWeixin()){
                    $this->log("syncWangcaiFansGroupToWeixin error :".$this->node_id );
                    $weixin_info_save['getfans_flag'] = '3';
                }
            }
        }else{//按微信为准进行同步
            //重建旺财本地微信分组
            //开启事务
            M()->startTrans();
            if (!$this->fansGroupService->reCreateWangcaiAllGroupByWeixin()){
                $this->log("reCreateWangcaiAllGroupByWeixin error :".$this->fansGroupService->error );
                $weixin_info_save['getfans_flag'] = '3';
                M('TweixinInfo')->where("node_id='".$this->node_id."'")->save($weixin_info_save);
                return;
            }
            //获取本地分组和微信分组的映射
            $this->groupMapArray = $this->fansGroupService->getGroupMapArray();
            M()->commit();

            if($openid_list['count'] > '0') {
                foreach ($openid_list['data']['openid'] as &$this->user_name) {
                    //先全部插入
                    $this->fansAddUpdateByOpenID();
                }
            }

            while(($openid_list['next_openid'] != null) && strlen($openid_list['next_openid'])>0){
                $openid_list = $this->wx->getFansList($this->access_token, $openid_list['next_openid']);

                if (isset($openid_list['errcode']) && $openid_list['errcode'] != 0 ){
                    //更新状态为同步失败
                    $this->log("get fanslist no data error:[".$openid_list['errcode']."]");
                    $weixin_info_save['getfans_flag'] = '3';
                    M('TweixinInfo')->where("node_id='".$this->node_id."'")->save($weixin_info_save);
                    return;
                }

                if($openid_list['count'] > '0')
                {
                    foreach($openid_list['data']['openid'] as &$this->user_name)
                    {
                        //先全部插入
                        $this->fansAddUpdateByOpenID();
                    }
                }
            };
            $this->log("end sync weixin fans only openid :".$this->node_id );
            //对本次同步进来的数据全部进行处理 ，主要是更新粉丝分组
            $wx_user_list = M('twx_user')->where("node_id ='".$this->node_id."' and node_wx_id = '".$this->node_wx_id."'")->select();
            foreach($wx_user_list as $wx_user_info){
                $this->user_name = $wx_user_info['openid'];
                $this->fansAddUpdate();
            }
            unset($wx_user_info);
            $this->log("sync by wangcai fans all:".$this->node_id );
        }

		//更新状态为同步结束
		$rs = M('TweixinInfo')->where("node_id='".$this->node_id."'")->save($weixin_info_save);
		$this->log("end sync weixin fans all:".$this->node_id );
	}
    /*
     * 根据备份表的数据进行更新
     */
	private function fansAddUpdateByOpenID()
	{
        //插入前排重一次
		$where = "node_id ='".$this->node_id."' and openid='".$this->user_name."'";
		$rs = M('TwxUser')->where($where)->find();

		if(!$rs){
			$wx_user['node_id'] = $this->node_id;
			$wx_user['node_wx_id'] = $this->node_wx_id;
			$wxuser_bak = M('twx_user_bak')->where($where)->find();
			if (!$wxuser_bak){
				$wx_user['group_id'] = 0;
			}else{
				$wx_user=$wxuser_bak;
			}
			$wx_user['subscribe'] = '1';
			$wx_user['openid'] = $this->user_name;
			$rs = M('TwxUser')->add($wx_user);
		}
	}


    private function fansAddUpdate()
	{
		$where = "node_id ='".$this->node_id."' and openid='".$this->user_name."'";
		$rs = M('TwxUser')->where($where)->find();

		$wx_user=$this->wx->getFansInfo($this->user_name,$this->access_token);
		if($wx_user['errcode'] == '40001'||$wx_user['errcode'] == '42001' ||$wx_user['errcode'] == '41001')//需要更新access_token
		{
			$this->wx->getAccessToken($this->app_id,$this->app_secret);
			$access_token = $this->wx->accessToken;
			$wx_info = array();
			$wx_info['app_access_token'] = $access_token ;
			M('TweixinInfo')->where("node_id='".$this->node_id."'")->save($wx_info);

			$this->access_token = $access_token ;
			$wx_user=$this->wx->getFansInfo($this->user_name,$this->access_token);
		}
		if(!isset($wx_user['errcode']))
		{
			$wx_user['node_id'] = $this->node_id;
			$wx_user['node_wx_id'] = $this->node_wx_id;
			$wx_user['group_id'] = $this->groupMapArray[$wx_user['groupid']];
			$wx_user['subscribe'] = '1';
            $wx_user['openid'] = $this->user_name;
			if(!$rs)
			{
				$rs = M('TwxUser')->add($wx_user);
			}
			else
			{
				$rs = M('TwxUser')->where($where)->save($wx_user);
			}
            if($rs === false) log_write('qqqqqqqqqqqqqqqqqqqqqqqqqqq'.M()->_sql());
		}
		else
		{
			$this->log("get fans error:[".$wx_user['errcode']."]");
		}
	}

    /*
     * 校验粉丝总数是否一致
     */
    private function checkFansCount($wxCount){
        if ($wxCount <= 0)
            return false;
        //获取本地记录关注粉丝数
        $where = "node_id ='".$this->node_id."' and node_wx_id = '".$this->node_wx_id."' and subscribe = '1'";
        $localCount = M('TwxUser')->where($where)->count();

        if ($wxCount == $localCount) {
            return true;
        }else {
            return false;
        }
    }

    /*
     * 校验粉丝分组统计数是否一致
     */
    private function checkFansGroupCount(){
        return $this->fansGroupService->checkFansGroupCount();
    }

	//记录日志
	protected function log($msg,$level = Log::INFO){
		//trace('Log.'.$level.':'.$msg);
		log_write($msg);
	}

	
}
