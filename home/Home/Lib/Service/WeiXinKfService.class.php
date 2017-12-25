<?php
/**
 * 微信多客服接口
 * 基于微信接口
 * 只做接口交互，不做本地数据库处理
 */
class WeiXinKfService
{
    protected $token = '';
    public $error = '';
    public $appId;
    public $appSecret;
    public $accessToken;
    private $kf_method;

    private $log_option;
    private $erron_list;

    private $weixin_service;

    public function __construct(){
        //微信接口
        $this->weixin_service=D('WeiXin','Service');
    }

    /**
     * 初始化
     * @param  string $appId      
     * @param  string $appSecret   
     * @param  string $accessToken 可设置token
     */
    public function init($appId,$appSecret,$accessToken = '')
    {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->accessToken = $accessToken;
        $this->weixin_service->init($appId,$appSecret,$accessToken);
        return $this;
    }

    /**
     * 获取token
     * @param  string $appId     [description]
     * @param  string $appSecret [description]
     * @return string|false      返回token
     */
    protected function getToken()
    {   
        //参数错误
        if(empty($this->accessToken) && (empty($this->appId) || empty($this->appSecret))){
            $this->log_option=array('appId'=>$this->appId,'appSecret'=>$this->appSecret,'accessToken'=>$this->accessToken);
            $this->erron=1001;
            return false;
        }
        //请求获取token
        if(empty($this->accessToken)){
           $this->weixin_service->getAccessToken($this->appId,$this->appSecret);
           $this->accessToken=$this->weixin_service->accessToken;
        }
        //获取token失败
        if(empty($this->accessToken)){
            $this->erron=1002;
        }
        return $this->accessToken;
    }

    /**
     * 通过机构号设置appid
     * @param [type] $node_id [description]
     */
    public function setAppIdByNodeId($node_id)
    {
        if(empty($node_id)){
            $this->erron=1011;
            return $this;
        }
        $info=$this->weixin_service->getWeixinInfoByNodeId($node_id);
        if($info['errmsg']){
            $this->erron_list=array('-1111'=>array('errorMsg'=>$info['errmsg'],'erron'=>'-1111'));
            $this->erron=-1111;
            $this->log_option=$info;
            return $this;
        }
        $this->init($info['appid'],$info['secret']);
        return $this;
    }

    //http get
    protected function httpGet($url,$data=null,$error=null,$opt = array())
    {    $this->_log("客服GET请求".$url);
        $result=$this->weixin_service->httpGet($url,$data,$error,$opt);
        return json_decode($result,true);
    }

    //http post
    protected function httpPost($url, $data = null)
    {   $this->_log("客服POST请求".$url,$data); 
        $data=json_encode($data,JSON_UNESCAPED_UNICODE);
        $result=$this->weixin_service->https_request($url,$data);
        return json_decode($result,true);
    }


    //错误码
    protected function erron2Word()
    {   
        //错误码列表
        $list=array(
            "default" =>  array(
                    "errorMsg" => "未知错误",
                    "erron"    => 9999,
                    ),
            1001 => array(
                    "errorMsg" => "appId或appSecret参数错误",
                    "erron"    => 1001,
                    ),
            1002 => array(
                    "errorMsg" => "获取token失败",
                    "erron"    => 1002,
                    ),
            1003 => array(
                    "errorMsg" => "向微信请求失败",
                    "erron"    => 1003,
                    ),
            1004 => array(
                    "erronMsg" => "客服账号参数错误",
                    'erron'    => 1004,
                    ),
            1005 => array(
                    "erronMsg" => "添加客服账号失败",
                    'erron'    => 1005,
                    ),
            1006 => array(
                    "erronMsg" => "修改客服账号失败",
                    'erron'    => 1006,
                    ),
            1007 => array(
                    "erronMsg" => "删除客服账号失败",
                    'erron'    => 1007,
                    ),
            1008 => array(
                    "erronMsg" => "用户参数错误",
                    'erron'    => 1008,
                    ),
            1009 => array(
                    "erronMsg" => "时间格式不对",
                    'erron'    => 1009,
                    ),
            1010 => array(
                    "erronMsg" => "请使用JPEG格式图片",
                    'erron'    => 1010,
                    ),
            1011 => array(
                    "erronMsg" => "机构号不能空",
                    'erron'    => 1011,
                    ),

            61458 => array(
                    "erronMsg" => "客户正在被其他客服接待",
                    'erron'    => 61458,
                    ),
            61459 => array(
                    "erronMsg" => "客服不在线",
                    'erron'    => 61459,
                    ),
            );

        $error=$list[$this->erron];
        if($this->erron_list[$this->erron]['erronMsg']){
            $error=$this->erron_list[$this->erron];
        }
        $error=$error?$error:$list['default'];
        $this->_log(var_export($error,true),$this->log_option);
        $this->log_option=false;
        return $error;
    }

    //获取错误
    public function getError()
    {   
        return $this->erron2Word();
    }

/*    //日志
    protected function _log($info){
        $this->weixin_service->log($info);
        return true;
    }
*/
    /**
     * 日志
     * @param  [type] $mome [description]
     * @param  [type] $info 其他参数
     * @return [type]       [description]
     */
    public function _log($mome,$info)
    {
      $msg="微信客服请求：";
          if(!empty($info)){
              $info=var_export($info,true);
          }
          $msg.=$mome;
          $info and $msg.=$info;
          log_write($msg);
    }

    /**
     * 获取客服聊天记录列表
     * @param int $start 开始时间
     * @param int $end   结束时间
     * @return array|bool 
     */
    public function KfListByMsgRecord($start,$end)
    {
        $start=strtotime($start);
        $end=strtotime($end);
        if($start >= $end){
            $this->erron=1009;
            return false;
        }
        //计算天差(由于微信客服自能按当天查询)
        $day=round(($end-$start)/(24*3600));
        $list=array();
        for($i=0;$i<$day;$i++){
            //当天开始时间
            $day_start=$start+($i*(24*3600));
            //当天结算时间
            $day_end=strtotime(date('Ymd',$day_start).'235959');
            //大于结束时间时，按结束时间
            $day_end=$day_end >= $end?$end:$day_end;
            $index=0;//页数
            while(true){
                $index++;
                $result=$this->KfMsgRecord($day_start,$day_end,$index);
                if($result){
                   $list=array_merge($list,(array)$result);
                }
                if(count($result) < 50){
                    break;
                }
            }
        }

        if(empty($list)){
            return false;
        }
        foreach($list as &$row){
            $row['time']=date('YmdHis',$row['time']);
        }
        return $list;
    }


    /**
     * 获取客服聊天记录
     * @param int $start 开始时间 时间戳
     * @param int $end   结束时间 时间戳
     * @param int $index 查询第几页，
     * @return array|bool 
     */
    private function KfMsgRecord($start,$end,$index=1)
    {
        $this->getToken();
        if(!$this->accessToken){
            return false;
        }
        $data=array(
            "starttime"=>$start,
            "endtime"  =>$end,
            "pageindex"=>$index,
            "pagesize" =>50,
            );
        $url="https://api.weixin.qq.com/customservice/msgrecord/getrecord?access_token=".$this->accessToken;
        $result=$this->httpPost($url,$data);
        if(!$result['recordlist']){
            return false;
        }
        return $result['recordlist'];
    }




//多客服管理 START
    
    /**
     * 获取客服基本信息
     * @return  array $list|false 信息列表 
     */
    public function KfListByBase()
    {   
        $this->getToken();
        if(!$this->accessToken){
            return false;
        }
        $url="https://api.weixin.qq.com/cgi-bin/customservice/getkflist?access_token=".$this->accessToken;
        $result=$this->httpGet($url);
        return $result['kf_list'];
    }

    /**
     * 获取在线客服
     * @return  array $list|false 客服列表
     */
    public function KfListByOnline()
    {
        $this->getToken();
        if(!$this->accessToken){
            return false;
        }
        $url="https://api.weixin.qq.com/cgi-bin/customservice/getonlinekflist?access_token=".$this->accessToken;
        $result=$this->httpGet($url);
        return $result['kf_online_list'];
    }

    /**
     * 添加客服
     * @param string $account  客服登入账号
     * @param string $nickname 客服昵称
     * @param string $password 客服登入密码 md5 加密过的
     * @return bool
     */
    public function KfAdd($account,$nickname,$password)
    {   
        //客服账号参数错误
        if(empty($account)
        || empty($nickname)
        || empty($password)){
            $this->erron=1004;
            $this->log_option=array('account'=>$account,'nickname'=>$nickname,'password'=>$password);
            return false;
        }
        $this->getToken();
        if(!$this->accessToken){
            return false;
        }
        $method=$this->kf_method=="update"?"update":"add";
        $url="https://api.weixin.qq.com/customservice/kfaccount/".$method."?access_token=".$this->accessToken;
        $data=array(
            'kf_account' => $account,
            'nickname'   => $nickname,
            'password'   => $password,
            );
        $result=$this->httpPost($url,$data);
        if($result['errcode'] != 0){
            $this->log_option=$result;
            $this->erron=1005;
            return false;
        }
        return true;
    }
    
    /**
     * 编制客服
     * @param string $account  客服登入账号
     * @param string $nickname 客服昵称
     * @param string $password 客服登入密码
     * @return bool
     */
    public function KfEdit($account,$nickname,$password)
    {   
        //设置接口编制客服请求
        $this->kf_method="update";
        $result=$this->KfAdd($account,$nickname,$password);
        if(!$result && $this->erron == 1005){
            $this->erron=1006;
        }
        return $result;
    }

    /**
     * 删除客服
     * @param string $account 客服账号
     * @return bool
     */
    public function KfDel($account)
    {   
        //参数错误
        if(empty($account)){
            $this->erron=1004;
            $this->log_option=array('account'=>$account);
            return false;
        }
        $this->getToken();
        if(!$this->accessToken){
            return false;
        }
        $url="https://api.weixin.qq.com/customservice/kfaccount/del?access_token=".$this->accessToken."&kf_account=".$account;
        $result=$this->httpGet($url);
        if($result['errcode'] != 0){
            $this->erron=1007;
            $this->log_option=$result;
            return false;
        }
        return true;
    }

    /**
     * 上传客服头像
     * @param string $account  可正常使用的客服账号
     * @param string $file_path 上传的图片绝对路径 只支持 jpg jpeg 格式
     */
    public function KfAvatar($account,$file_path)
    {
        if(empty($account)
        || !file_exists($file_path)){
            $this->erron=1004;
            $this->log_option=array('account'=>$account,'avatar'=>$file_path);
            return false;
        }
        $img_info=getimagesize($file_path);
        if(!in_array($img_info['mime'],array('image/jpeg','image/jpg'))){
            $this->erron=1010;
            $this->log_option=$img_info;
            return false;
        }
        $this->getToken();
        if(!$this->accessToken){
            return false;
        }

        $data=array(
            "media"=>"@".$file_path,
            );
        $url="http://api.weixin.qq.com/customservice/kfaccount/uploadheadimg?access_token=".$this->accessToken."&kf_account=".$account;
        $result=$this->weixin_service->https_request($url,$data);
        $result= json_decode($result,true);
        if($result['errcode'] != 0 ){
            $this->erron=$result['errcode'];
            return false;
        }
        return true;
    }

//多客服管理 END



//多客服会话控制 START

    /**
     * 创建会话
     * @param string $account 被叫客服账号
     * @param string $openid  用户openid
     * @param string $text    备注
     * @return bool
     */
    public function KfCreateCall($account,$openid,$text='')
    {
        if(empty($account)
        || empty($openid)){
            $this->erron=1008;
            $this->log_option=array('account'=>$account,'openid'=>$openid);
            return false;
        }
        $this->getToken();
        if(!$this->accessToken){
            return false;
        }
        $data=array(
            'openid'    => $openid,
            'kf_account'=> $account,
            'text'      => $text,
            );
        $method=$this->kf_method=="close"?"close":"create";
        $url="https://api.weixin.qq.com/customservice/kfsession/".$method."?access_token=".$this->accessToken;
        $result=$this->httpPost($url,$data);
        if($result['errcode'] != 0 ){
            $this->erron=$result['errcode'];
            $this->log_option=$result;
            return false;
        }
        return true;
    }

    /**
     * 关闭会话
     * @param string $account 会话中客服账号
     * @param string $openid  会话中用户openid
     * @param string $text    备注
     * @return bool
     */
    public function KfCloseCall($account,$openid,$text='')
    {
        //设置接口关闭会话请求
        $this->kf_method="close";
        $result=$this->KfCreateCall($account,$openid,$text);
        return $result;
    }

    /**
     * 转接会话
     * @param string $src    主叫账号
     * @param string $dst    被叫账号
     * @param string $openid 会话用户openid
     */
    public function KfAdapter($src,$dst,$openid)
    {
        //关闭主叫会话
        $res=$this->KfCloseCall($src,$openid);
        //创建被叫会话
        $res=$this->KfCreateCall($dst,$openid);
        return true;
    }

    /**
     * 获取用户会话状态
     * @param string $openid 用户openid
     * @return array|false 
     */
    public function KfGetUserCall($openid)
    {
        if(empty($openid)){
            $this->erron=1008;
            $this->log_option=array('openid'=>$openid);
            return false;
        }
        $this->getToken();
        if(!$this->accessToken){
            return false;
        }
        $url="https://api.weixin.qq.com/customservice/kfsession/getsession?access_token=".$this->accessToken."&openid=".$openid;
        $result=$this->httpGet($url);
        if($result['errcode'] != 0 || empty($result['kf_account'])){
            $this->log_option=$result;
            return false;
        }
        return $result['kf_account'];
    }

    /**
     * 获取客服当前会话列表
     * @param string $account 客服账号
     * @param array|false
     */
    public function KfListByCall($account)
    {
        if(empty($account)){
            $this->erron=1004;
            $this->log_option=array('account'=>$account);
            return false;
        }
        $this->getToken();
        if(!$this->accessToken){
            return false;
        }
        $url="https://api.weixin.qq.com/customservice/kfsession/getsessionlist?access_token=".$this->accessToken."&kf_account=".$account;
        $result=$this->httpGet($url);
        if(!$result['sessionlist']){
            return false;
        }
        return  $result['sessionlist'];
    }

    /**
     * 获取未接入会话列表
     */
    public function KfListByQueue()
    {
        $this->getToken();
        if(!$this->accessToken){
            return false;
        }
        $url="https://api.weixin.qq.com/customservice/kfsession/getwaitcase?access_token=".$this->accessToken;
        $result=$this->httpGet($url);
        if(empty($result['waitcaselist'])){
            return false;
        }
        return $result;
    }

//多客服会话控制 END

}