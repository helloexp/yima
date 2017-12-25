<?php
import('@.Service.LoginService') or die('导入包失败');
class TempLoginService extends LoginService{

    // 来源标识
    protected $tempNodeType;
    // 临时用户nodeid
    protected $tempNodeId;
    // 临时用户跳转连接
    protected $tempUrl;
    // 正常用户跳转连接
    protected $normalUrl;
    // 用户信息
    protected $nodeInfo;
    
    public function __construct() 
    {
        // 初始化落地页跳转过来时，所带的标识
        $this->tempNodeType = session('tempNodeType');
        // 初始化游客ID
        $this->tempNodeId = array(
            'newPosterVisiter'      => C('NEW_POSTER_VISITER.node_id'),
            'newVisualCodeVisiter'  => C('NEW_VISUALCODE_VISITER.node_id'),
            );
        // 初始化游客跳转url
        $this->tempUrl = array(
            'newPosterVisiter'      => U('MarketActive/NewPoster/addBasicInfoForTempUser'),
            'newVisualCodeVisiter'  => U('MarketActive/VisualCode/addBasicInfoForTempUser'),
            );
        // 初始化普通用户跳转url
        $this->normalUrl = array(
            'newPosterVisiter'      => U('MarketActive/NewPoster/index'),
            'newVisualCodeVisiter'  => U('MarketActive/VisualCode/index'),
            );
        $this->nodeInfo = D('NodeInfo')->getNodeInfo(['node_id' => $this->getTempNodeId()]);
    }
    /**
     * [makeUserSessInfo 处理登录session]
     * @return [type] [null]
     */
    public function makeUserSessInfo()
    {
        if($nodeId = $this->getTempNodeId())
            $_SESSION['userSessInfo']['node_id'] = $nodeId;
        else
            $_SESSION['userSessInfo'] = null;
    }
    /**
     * [getTempNodeId 获取游客ID]
     * @return [type] [nodeid]
     */
    public function getTempNodeId() 
    {
        return get_val($this->tempNodeId,$this->tempNodeType,'');
    }
    /**
     * [redirectUrlByUser 跳转连接]
     * @return [type] [null]
     */
    public function redirectUrlByUser()
    {
        $nodeId = get_val(session('userSessInfo'),'node_id','');
        if(in_array($nodeId,$this->tempNodeId))
            $this->tempRedirectUrl($nodeId); // 游客跳转
        else
            $this->normalRedirectUrl(); // 正常用户跳转
    }
    
    /**
     * 判断传过来的node_id是否是临时用户的node_id
     * @param string $nodeId
     * @return number 1:是临时用户，2:不是临时用户
     */
    public function isTempUser($nodeId) {
        $isTempUser = 2;
        if ($nodeId == $this->getTempNodeId()) {
            $isTempUser = 1;
        }
        return $isTempUser;
    }
    
    /**
     * [tempRedirectUrl 游客跳转]
     * @param  [type] $nodeId [游客ID]
     * @return [type]         [null]
     */
    public function tempRedirectUrl($nodeId) {
        if(in_array($nodeId,$this->tempNodeId))
        {
            $cookieInfo = cookie($this->tempNodeType.'Node');
            if (!$cookieInfo) {
                cookie($this->tempNodeType.'Node', D('Sequence')->getCookieInfo(), 3600 * 24 * 365);
                redirect($this->tempUrl[$this->tempNodeType]);
            } else {
                //如果有cookie到临时表里去找对应的活动id
                $url = D('TempUserSequence')->getUrl($this->tempNodeType);
                if (empty($url)) {
                    die('临时用户查找活动号失败');
                }
                redirect($url);
            }
        }
    }
    /**
     * [normalRedirectUrl 正常用户跳转]
     * @return [type] [null]
     */
    public function normalRedirectUrl(){
        redirect($this->normalUrl[$this->tempNodeType]);
    }
}