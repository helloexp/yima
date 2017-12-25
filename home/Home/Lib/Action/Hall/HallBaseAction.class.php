<?php

/**
 * 大厅基础类
 *
 * @author bao
 */
class HallBaseAction extends BaseAction {
	
	public $isLogin;//登陆标识

    public function _initialize() {
        // // 卡券大厅重定向二级域名
        // if (C('APP_SUB_DOMAIN_DEPLOY')) { // 开启子域名部署
        //                                   // 获取二级域名的分组
        //     $domainGroup = array();
        //     foreach (C('APP_SUB_DOMAIN_RULES') as $key => $rule) {
        //         $domainGroup[substr($rule[0], 0, strpos($rule[0], '/'))] = $key;
        //     }
        //     $domainName = $domainGroup[GROUP_NAME];
        //     $hostFirst = substr($_SERVER['HTTP_HOST'], 0, 
        //         strpos($_SERVER['HTTP_HOST'], '.'));
        //     if ($hostFirst != $domainName) {
        //         redirect(
        //             (is_ssl() ? 'https://' : 'http://') . $domainName .
        //                  strstr($_SERVER['HTTP_HOST'], '.') .
        //                  $_SERVER['REQUEST_URI']);
        //     }
        // }
        $userService = D('UserSess', 'Service');
        $this->isLogin = $userService->isLogin();
        if ($this->isLogin) {
            parent::_initialize();
            if ($this->isGet() && ! $this->isAjax()) {
                $data = array(
                    'group' => GROUP_NAME, 
                    'module' => MODULE_NAME, 
                    'action' => ACTION_NAME, 
                    'param' => $_SERVER['QUERY_STRING'], 
                    'visit_ip' => get_client_ip(), 
                    'visit_day' => date('Ymd'), 
                    'visit_time' => date('YmdHis'), 
                    'node_id' => $this->nodeId, 
                    'user_id' => $this->userId);
                D('tpage_visit_record')->add($data);
            }
        }
    }
    
    public function checkLogin(){
    	if($this->isLogin){
    		return true;
    	}else{
    		$this->goLogin();
    	}
    }
    
    /* 登录错误页 */
    public function goLogin() {
    	$urlInfo = parse_url(U('Hall/Index/goods','',true,false,true));
    	$scheme = empty($urlInfo['scheme']) ? '' : $urlInfo['scheme'].'://';
    	$backUrl = $scheme.$urlInfo['host'].$_SERVER['REQUEST_URI'];
    	$fromUrl = U('Home/Login/showLogin',array('fromurl'=>urlencode($backUrl)),ture,false,true);
    	$this->error("您尚未登录或登录已超时：",array('请立即登录' => $fromUrl));
    }
    
    /**
     * 图片上传
     */
    public function meitulogin(){
    	R('ImgResize/Meitulogin/index');
    }
    
    public function upload(){
    	R('ImgResize/Upload/index');
    }
    
    /**
     * 视频上传
     */
    public function video(){
    	R('ImgResize/Upload/video');
    }
    
}