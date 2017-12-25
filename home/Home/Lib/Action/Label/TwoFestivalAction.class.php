<?php

class TwoFestivalAction extends MyBaseAction {
    // 跳转回来地址
    const __TRUE_BACK_URL__ = '__TRUE_BACK_URL__';

    const NO_AWARD_TIP = '对不起，未中奖';
    // 微信用户id
    public $expiresTime = 60;
    // 手机验证码过期时间
    public $wechatInfo;

    public $auth_flag;

    public $openId;
    // 微信openId
    public $js_global = array();

    public $wap_sess_name = '';

    public $fromOpenId;

    public $toOpenId;

    const SELECT_TYPE_ONE = 1;
    // 查询一条记录
    const BATCH_TYPE = '59';
    // 双旦
    public function _initialize() {
        if (I('_sid_', '') == 'w') {
            $_SERVER['HTTP_USER_AGENT'] = 'MicroMessenger';
            session('wxid', 1);
        }
        
        // 重置数据
        // if (I('_sid_') == 'clean') {
        // session('wxid', null);
        // session('from_user_id', null);
        // redirect(U('', array('id' => $this->id)));
        // exit;
        // }
        parent::_initialize();
        // 检查活动开始没有?结束没有?
        $checked = $this->checkDate();
        if (! $checked) {
            $this->showErrorByErrno(- 1016);
        }
        if ($this->batch_type != self::BATCH_TYPE) {
            $this->error(
                array(
                    'errorImg' => '__PUBLIC__/Label/Image/waperro5.png', 
                    'errorTxt' => '错误访问！', 
                    'errorSoftTxt' => '你的访问地址出错啦~'));
        }
        
        // if(ACTION_NAME != 'index') {
        $wxUserInfo = $this->getWxUserInfo();
        if (empty($wxUserInfo)) {
            // 判断是否为双蛋的非标用户
            self::checkFbVerify($this->node_id, $this->batch_type, I('get.'));
            $this->_checkUser(true);
        }
        // }
        // 活动信息
        $marketInfo = $this->marketInfo;
        
        // 分享信息
        // $uparr = C('BATCH_IMG_PATH_NAME');
        // if (!empty($this->wxUserInfo)) {
        try {
            D('WxWapUser')->setWxUserInfo($this->wxUserInfo, $this->node_id, 
                $this->id);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
        $toOpenId = I('get.toOpenId', $this->wxUserInfo['openid']); // 默认如果没有祝福给谁的话就是自己
        $shareUrl = U('blessWall', 
            array(
                'id' => $this->id, 
                'toOpenId' => $toOpenId), '', '', TRUE);
        $this->fromOpenId = $this->wxUserInfo['openid'];
        $this->toOpenId = $toOpenId;
        // }
        // else {
        // $shareUrl = U('index', array('id' => $this->id), '', '', TRUE);
        // }
        $wx_share_config = D('WeiXin', 'Service')->getWxShareConfig();
        $this->assign('wx_share_config', $wx_share_config);
        $config = unserialize($marketInfo['config_data']);
        $shareArr = array(
            'config' => $wx_share_config, 
            'link' => $shareUrl, 
            // 'title' => $marketInfo['wap_title'],
            'title' => get_val($config['share_title']), 
            // 'shareNote'=>$row['wap_info'],
            'desc' => $config['share_descript'], 
            // 'imgUrl' => C('CURRENT_DOMAIN') .
            // 'Home/Public/Label/Image/20150501/' . L('IMAGE_ICON')
            'imgUrl' => $marketInfo['share_pic'] ? get_upload_url(
                $marketInfo['share_pic']) : C('CURRENT_DOMAIN') .
                 'Home/Public/Label/Image/20151224/icon1.png');
        $this->assign('testShareUrl', $shareUrl);
        // M('tdraft')->add(array('content' => json_encode($shareArr)));
        $this->assign('marketInfo', $marketInfo);
        $this->assign('shareData', $shareArr);
        $this->assign('id', $this->id);
        $this->assign('wxid', $this->wxid);
        $this->assign('fromOpenId', $this->fromOpenId);
        $this->assign('toOpenId', $this->toOpenId);
    }

    public function _before_blessWall() {
        // M('tdraft')->add(array('content' => '条过了祝福墙'));
        // 查询他自己有没有录过音，没有的话跳转首页
        $re = D('WxVoice')->getVoiceByToOpenId($this->toOpenId, 
            $this->marketInfo['id']);
        // M('tdraft')->add(array('content' => json_encode($re)));
        if (! $re) { // 没有录音的时候
            $this->redirect('index', 
                array(
                    'id' => $this->id));
        }
    }

    public function _before_index() {
        // M('tdraft')->add(array('content' => '条过了祝福墙'));
        // 查询他自己有没有录过音，没有的话跳转首页
        // 是否抽过奖
        if ($this->wxUserInfo['openid'] == $this->toOpenId) {
            $where = array(
                'mobile' => $this->wxUserInfo['openid'], 
                'batch_id' => $this->marketInfo['id']);
            $cjTraceResult = D('CjTrace')->getCjTrace($where, 
                self::SELECT_TYPE_ONE);
            // M('tdraft')->add(array('content' => json_encode($re)));
            if ($cjTraceResult) { // 如果抽过奖
                $this->redirect('blessWall', 
                    array(
                        'id' => $this->id, 
                        'toOpenId' => $this->toOpenId));
            }
        }
    }
    
    // 首页
    public function index() {
        // 校验活动是否在有效期中
        $stauts = $this->marketInfo;
        if ($stauts['status'] == 2) {
            $this->error('活动不在有效期中！');
        }
        
        // 访问量
        import('@.Vendor.DataStat');
        $opt = new DataStat($this->id, $this->full_id);
        $opt->UpdateRecord();
        
        // 奖品显示
        // $jp_arr = $this->searchJp();
        $this->assign('id', $this->id);
        $this->assign('batch_type', $this->batch_type);
        $this->assign('batch_id', $this->batch_id);
        // $this->assign('jp_arr', $jp_arr);
        $cj_button_text = M('tcj_rule')->where(
            array(
                'batch_id' => $this->marketInfo['id']))->getField(
            'cj_button_text');
        $this->assign('cj_button_text', $cj_button_text);
        $wap_info = str_replace("\r", '', 
            str_replace("\n", '', nl2br($this->marketInfo['wap_info'])));
        $this->assign('wap_info', $wap_info);
        
        // 取出奖品
        $cj_cate_arr = M('tcj_cate')->where(
            array(
                'node_id' => $this->node_id, 
                'batch_id' => $this->marketInfo['id']))
            ->order('sort asc')
            ->select();
        $jp_arr = M()->table('tcj_batch a')
            ->field('a.*,b.batch_name,b.remain_num,b.storage_num,b.batch_img')
            ->join('tbatch_info b on a.b_id=b.id')
            ->where(
            "a.node_id='" . $this->node_id . "' and a.batch_id='" .
                 $this->marketInfo['id'] . "' and a.status='1'")
            ->select();
        foreach ($jp_arr as $k => $v) {
            $jp_arr[$k]['batch_img'] = get_upload_url($v['batch_img']);
        }
        $this->assign('prize', json_encode($jp_arr));
        $this->assign('cate', json_encode($cj_cate_arr));
        $this->display(); // 输出模板
    }

    public function operate() {
        $this->cacheControl();
        $isNeedRedirect = $this->isNeedRedirect();
        $this->assign('isNeedRedirect', $isNeedRedirect);
        $this->assign('verifyCodeExpireTime', $this->expiresTime);
        $this->display();
    }

    protected function isNeedRedirect() {
        // 机构号是否设置需要抽奖
        if (! $this->marketInfo['is_cj']) {
            return true;
        }
        
        // 是否抽过奖
        $where = array(
            'mobile' => $this->wxUserInfo['openid'], 
            'batch_id' => $this->marketInfo['id']);
        $cjTraceResult = D('CjTrace')->getCjTrace($where, self::SELECT_TYPE_ONE);
        if ($cjTraceResult) {
            return true;
        }
        
        // 如果是预览渠道不用抽奖
        $previewChannelId = D('Channel')->getPreviewChannelId($this->node_id);
        $channelId = $this->channel_id ? $this->channel_id : 0;
        $previewChannelId = (int) $previewChannelId;
        $channelId = (int) $channelId;
        if ($channelId === $previewChannelId) {
            return true;
        }
        // 如果不是自己的录音,跳过抽奖
        if ($this->fromOpenId != $this->toOpenId) {
            return true;
        }
        return false;
    }
    
    // 登录
    // public function check_code(){
    // $code=I('code','null');
    // $mobile=I('phone','null');
    // if(empty($code)|| empty($mobile)){
    // exit(json_encode(array(
    // 'info'=>'提交验证码或者手机号码不能为空！',
    // 'status'=>1
    // )));
    // }
    // $phoneCheckCode = session('checkCode');
    // if(empty($phoneCheckCode) || $phoneCheckCode['number'] != $code){
    // exit(json_encode(array(
    // 'info'=>'手机验证码不正确！',
    // 'status'=>1
    // )));
    // }
    // if(time()-$phoneCheckCode['add_time'] > $this->expiresTime){
    // exit(json_encode(array(
    // 'info'=>'手机验证码已经过期！',
    // 'status'=>1
    // )));
    // }
    // //手机号码和验证码正确，存储用户数据
    // $map = array(
    // 'user_type' => '1',
    // 'openid' => $mobile,
    // 'mobile' => $mobile,
    // //'node_id'=>$this->node_id,
    // );
    // $info = M('twx_wap_user')->where($map)->find();
    // if(!$info){
    // $data = $map;
    // $data['add_time'] = date('YmdHis');
    // $data['label_id'] = $this->id;
    // $data['node_id'] = $this->node_id;
    // M()->startTrans();
    // $member_id = M('twx_wap_user')->add($data);
    // if($member_id===false) {
    // M()->rollback();
    // exit(json_encode(array(
    // 'info' => '手机登录失败！',
    // 'status' => 1
    // )));
    // }
    // //手机登录成功，应该将twx_labordayscore里面写进一条记录
    // $info = M('twx_wap_user')->where($map)->find();
    // }
    // // session('LaborDay_mobile',$mobile);
    // // $date = array(
    // // 'node_id' => $this->node_id,
    // // 'batch_id' => $this->batch_id,
    // // 'wx_user_id' => $info['id']
    // // );
    // // if(!M('twx_laborday_score')->where($date)->find()){
    // // $res = M('twx_laborday_score')->add($date);
    // // if($res===false){
    // // M()->rollback();
    // // exit(json_encode(array(
    // // 'info'=>'手机登录失败！',
    // // 'status'=>1
    // // )));
    // // }
    // // }
    
    // M()->commit();
    
    // $member_id = $info['id'];
    // session($this->wap_sess_name, $member_id);
    // exit(json_encode(array(
    // 'info'=>'登录成功进入游戏界面！',
    // 'status'=>0,
    // 'return_url'=>U('playGame',array('id'=>$this->id))
    // )));
    // }
    /**
     * 根据营销活动参数判断session，获取是否登录
     *
     * @param bool $return
     */
    // public function _labor_checklogin($return = true){
    // if(session('?'.$this->wap_sess_name) && $return) return true;
    // $join_mode = $this->marketInfo['join_mode'];
    // $member_join_flag = $this->marketInfo['member_join_flag'];
    // $login = false;
    // $userid = '';
    // if($join_mode == '0'){//该活动只能微信参与
    // $this->error('抽奖参与方式设置错误！');
    // }
    // else{
    // $backurl = U('',I('get.'),'','',true);
    // $backurl = urlencode($backurl);
    // if($join_mode == '1' && $member_join_flag == '0'){
    // $login = session('?node_wxid_global');
    // $jumpurl =
    // U('Label/WeixinLoginGlobal/index',array('id'=>$this->id,'type'=>0,'backurl'=>$backurl));
    // if($login)
    // $info = session('node_wxid_global');
    // }
    // if($join_mode == '1' && $member_join_flag == '1'){
    // $login = session('?node_wxid_'.$this->node_id);
    // $jumpurl =
    // U('Label/WeixinLoginNode/wechatAuthorizeAndRedirectByDefault',array('id'=>$this->id,'type'=>0,'backurl'=>$backurl));
    // log_write('k12345'.$jumpurl);
    // if($login)
    // $info = session('node_wxid_'.$this->node_id);
    // }
    // if(!$login && !$return){
    // redirect($jumpurl);
    // }
    // if($login) {
    // $openid = $info['openid'];
    // //判断是否是粉丝
    // if($join_mode == '1' && $member_join_flag == '1'){
    // $count = M('twx_user')->where("openid = '{$openid}' and ifnull(subscribe,
    // '0') != '0'")->count();
    // if($count != 1){
    // if(IS_AJAX){
    // exit(json_encode(array(
    // 'info' => '请先关注公众号再参与游戏！',
    // 'status' => 1
    // )));
    // }
    // else{
    // if($this->marketInfo['fans_collect_url'] != ''){
    // redirect($this->marketInfo['fans_collect_url']);
    // }
    // $this->error('请先关注公众号再参与游戏！');
    // }
    // }
    // }
    // $map = array(
    // 'user_type' => '0',
    // 'openid' => $openid,
    // );
    // $user = M('twx_wap_user')->where($map)->find();
    // M()->startTrans();
    // if (!$user) {
    // $data = $map;
    // $data['add_time'] = date('YmdHis');
    // $data['label_id'] = $this->id;
    // $data['node_id'] = $this->node_id;
    // $userid = M('twx_wap_user')->add($data);
    // if ($userid === false) {
    // M()->rollback();
    // exit(json_encode(array(
    // 'info' => '手机登录失败！',
    // 'status' => 1
    // )));
    // }
    // //将用户信息写进twx_wap_user表中，同时应该给用户生成一个sorce表格
    // $user = M('twx_wap_user')->where($map)->find();
    
    // }
    // M()->commit();
    // $userid = $user['id'];
    // }
    // }
    // session($this->wap_sess_name, $userid);
    // return $login;
    // }
    
    // //游戏
    // public function playGame()
    // {
    // $overdue = $this->checkDate();
    // if ($overdue === false)
    // $this->error("该活动不在有效期之内！");
    // $myscore = $this->searchMyInfo();
    // //设置js
    // $this->_setGlobalJs(array(
    // 'nowcode' => $myscore['score'],//分数
    // 'game_number' => $myscore['game_number'],//剩下次数
    // 'nowball' => $myscore['game_number'] ? 3 : 0,//劳动数
    // ));
    // $this->assign('info', $myscore);
    // $this->display();
    // }
    
    // //强制刷新
    // public function getInfo()
    // {
    // $myscore = $this->searchMyInfo();
    // $this->success($myscore);
    // }
    
    // //领取奖品
    // public function getPrize()
    // {
    // $overdue = $this->checkDate();
    // if ($overdue === false)
    // $this->error("该活动不在有效期之内！");
    // $myscore = $this->searchMyInfo();
    // $jp_arr = $this->searchJp();
    // //已领奖项
    // $user_id = M('twx_laborday_score')->where(array('wx_user_id' =>
    // $this->wxid, 'batch_id' => $this->batch_id))->getField('id');
    // $map = array(
    // 'node_id' => $this->node_id,
    // 'batch_id' => $this->batch_id,
    // 'status' => '2',
    // 'user_id' => $user_id
    // );
    // $cjcate_arr = M('tcj_trace')->field('cate_id')->where($map)->select();
    // $zj_cate_arr = array();
    // if ($cjcate_arr) {
    // foreach ($cjcate_arr as $cjcate) {
    // $zj_cate_arr[] = $cjcate['cate_id'];
    // }
    // }
    // $this->assign('join_mode',$this->marketInfo['join_mode']);
    // $this->assign('mobile',session('LaborDay_mobile'));
    // $this->assign('zj_cate_arr', $zj_cate_arr);
    // $this->assign('myscore', $myscore);
    // $this->assign('jp_arr', $jp_arr);
    // $this->display();
    // }
    
    // //查询奖品
    // protected function searchJp($data = array())
    // {
    // $jp_sql = "SELECT b.id as cid, b.name,b.score
    // ,c.batch_name,c.batch_img,c.remain_num FROM tcj_batch a
    // LEFT JOIN tcj_cate b ON a.cj_cate_id = b.id
    // LEFT JOIN tbatch_info c ON a.b_id=c.id
    // WHERE a.status =1 AND a.batch_id ='" . $this->batch_id . "' order by
    // b.id";
    // $jp_arr = M()->query($jp_sql);
    // return $jp_arr;
    // }
    
    // //当前活动个人统计查询
    // protected function searchMyInfo()
    // {
    // $map = array(
    // 'node_id' => $this->node_id,
    // 'batch_id' => $this->batch_id,
    // 'wx_user_id' => $this->wxid
    // );
    // $map1=$map;
    // $map1['_string']="add_time like '".date('Ymd')."%'";
    // $gamenumber =3-intval(M('twx_laborday_trace')->where($map1)->count());
    // $score = intval(M('twx_laborday_score')->where($map)->getField('score'));
    // $myranking=array(
    // 'game_number'=>$gamenumber,
    // 'score'=>$score
    // );
    // return $myranking;
    // }
    
    // //当前用户的好友，总分，总小球数
    // protected function MyCount()
    // {
    // $map = array(
    // 'node_id' => $this->node_id,
    // 'wx_user_id' => $this->wxid,
    // 'batch_id' => $this->batch_id
    // );
    // $info = M('twx_laborday_score')->where($map)->find();
    
    // $array = array(
    // 'all_score' => $info['score']
    // );
    // return $array;
    
    // }
    
    // //发送游戏分数
    // public function gameScore()
    // {
    // //通过随机数来判断等级
    // $codeRandom = mt_rand(1,100);
    // if($codeRandom<=10){
    // $i = 0;
    // }else if($codeRandom>10 && $codeRandom<=40){
    // $i = 1;
    // }else if($codeRandom>40 && $codeRandom<=80){
    // $i = 2;
    // }else if($codeRandom>80 && $codeRandom<=95){
    // $i = 3;
    // }else if($codeRandom>95){
    // $i = 4;
    // }
    // $code = array(100,50,20,10,0);
    // $score=$code[$i];
    // $success_num = I('success_num', '', 'intval');
    // if ($score < 0) {
    // $this->responseJson(-1, '参数错误');
    // }
    // M()->startTrans();
    // //能不能玩
    // $map = array(
    // 'node_id' => $this->node_id,
    // 'batch_id' => $this->batch_id,
    // 'wx_user_id' => $this->wxid
    // );
    // $info = M('twx_laborday_score')->where($map)->lock(true)->find();
    // if(!$info){
    // log_write('syserror...........'.M()->_sql());
    // $this->responseJson(-1, '系统错误！');
    // }
    // $map2 = $map;
    // $map2['_string']="add_time like '".date('Ymd')."%'";
    // $traceInfo=M('twx_laborday_trace')->where($map2)->count();
    // if ($traceInfo >= 3) {
    // M()->rollback();
    // $this->responseJson(-1, '不能再玩了');
    // }
    
    // //记录游戏流水
    // $data = array(
    // 'node_id' => $this->node_id,
    // 'batch_id' => $this->batch_id,
    // 'wx_user_id' => $this->wxid,
    // 'score' => $score,
    // 'add_time' => date('YmdHis')
    // );
    // $flag = M('twx_laborday_trace')->add($data);
    // if ($flag === false) {
    // M()->rollback();
    // $this->responseJson(-1, '流水记录失败');
    // }
    
    // $remain_score = $info['score'] + $score;
    // $data = array(
    // 'ranking_count' => $info['ranking_count'] + $score,
    // 'score' => $remain_score,
    // );
    // $flag = M('twx_laborday_score')->where($map)->save($data);
    // M()->commit();
    // log_write('wxid:' . $this->wxid . ',score:' . $score);
    
    // $map = array(
    // 'batch_id' => $this->batch_id
    // );
    // $jp_list = M('tcj_cate')->where($map)->order('score')->select();
    // if(!$jp_list){
    // $this->responseJson(0, '成功啦',array(
    // 'score' => $score,
    // 'tag' => 0
    // ));
    // }
    
    // $match = 0;
    // $cate_name = '';
    // $tag = 1;
    // $prech=0;
    // foreach($jp_list as $key=>$jp){
    // if($jp['score'] > $remain_score){
    // $match = $jp['score'];
    // $cate_name = $jp['name'];
    // break;
    // }
    // }
    // if($match==0){
    // $tag=2;
    // }else{
    // //相差积分''
    // $prech=$match-$remain_score;
    // }
    // $this->responseJson(0, '成功啦',array(
    // 'score' => $score,
    // 'tag' => $tag,
    // 'prech'=>$prech,
    // 'cate_name' => $cate_name
    // ));
    // }
    
    // //领取奖品
    // public function submitCj()
    // {
    // if(!$this->_labor_checklogin()){
    // $this->ajaxReturn("error", '登录超时！&nbsp;&nbsp;&nbsp;<a href="'.U('index',
    // array('id'=>$this->id)).'">返回</a>', 0);
    // }
    
    // $overdue = $this->checkDate();
    // if ($overdue === false)
    // $this->ajaxReturn("error", "该活动不在有效期之内！", 0);
    // $mobile = I('mobile', NULL);
    // $cate_id = I('cate_id', NULL);
    
    // if (!is_numeric(trim($mobile)) || strlen(trim($mobile)) != '11') {
    // $this->ajaxReturn("error", "请正确填写手机号！", 0);
    // }
    
    // $map = array(
    // 'node_id' => $this->node_id,
    // 'batch_id' => $this->batch_id,
    // 'wx_user_id' => $this->wxid
    // );
    
    // if (empty($cate_id) ) {
    // $this->ajaxReturn("error", "参数错误！" . $cate_id , 0);
    // }
    // $map = array(
    // 'node_id' => $this->node_id,
    // 'batch_id' => $this->batch_id,
    // 'id' => $cate_id
    // );
    // $score = M('tcj_cate')->where($map)->getField('score');
    // $mycount = $this->MyCount();
    // if ((int)$score > (int)$mycount['all_score']) {
    // $this->ajaxReturn("error", "未达到该奖项的金币！", 0);
    // }
    // //每个奖项只能领取一次
    // $map = array('wap_user_id' => $this->wxid, 'cate_id' => $cate_id,
    // 'batch_id'=>$this->batch_id);
    // $cjcount = M('twx_laborday_gethis')->where($map)->count();
    // if ($cjcount > 0) {
    // $this->ajaxReturn("error", "该奖项只能领取一次", 0);
    // }
    
    // //减去领奖的积分
    // M()->startTrans();
    // $save = array('score' => array('exp', 'score-' . $score));
    // $query = M('twx_laborday_score')
    // ->where(array('node_id' => $this->node_id, 'batch_id' => $this->batch_id,
    // 'wx_user_id' => $this->wxid))
    // ->save($save);
    // if ($query === false) {
    // M()->rollback();
    // $this->ajaxReturn("error", "金币扣减失败！", 0);
    // }
    // $other_arr = array(
    // 'wx_cjcate_id' => $cate_id,
    // );
    // if($this->marketInfo['join_mode'] == '1'){
    // $sess = $this->marketInfo['member_join_flag'] ?
    // session('node_wxid_'.$this->node_id) : session('node_wxid_global');
    // $other_arr['wx_open_id'] = $sess['openid'];
    // $other_arr['wx_nick'] = $sess['nickname'];
    // }
    
    // import('@.Vendor.ChouJiang');
    // $t_mobile = $this->marketInfo['join_mode'] == '1' ? '' : $mobile;
    // $choujiang = new ChouJiang($this->id, $t_mobile, $this->full_id, '',
    // $other_arr);
    // $resp = $choujiang->send_code();
    // if ($resp['resp_id'] == '0000') {
    // if($this->marketInfo['join_mode'] == '1'){
    // if(empty($resp['request_id'])){
    // M()->rollback();
    // $this->ajaxReturn("error", '很遗憾领取失败', 0);
    // }
    
    // $cj_trace_id = $resp['cj_trace_id'];
    // $request_id = $resp['request_id'];
    
    // //修改数据库中的手机号字段，并且调用重发接口
    // $result = M('tcj_trace')->where(array(
    // 'id' => $cj_trace_id,
    // //'user_id'=>$this->wxid,
    // //'cate_id'=>$cate_id
    // ))->save(array(
    // 'send_mobile' => $mobile
    // ));
    // //修改发码表的字段
    // $result = M('tbarcode_trace')->where(array(
    // 'request_id' => $request_id
    // ))->save(array(
    // 'phone_no' => $mobile,
    // ));
    // M()->commit();
    // //然后调用重发接口
    // import("@.Vendor.CjInterface");
    // $req = new CjInterface();
    // $result = $req->cj_resend(array(
    // 'request_id' => $request_id,
    // 'node_id' => $this->node_id,
    // 'user_id' => '00000000',
    // ));
    // if (!$result || $result['resp_id'] != '0000') {
    // M()->rollback();
    // $this->ajaxReturn("error", '很遗憾领取失败', 0);
    // }
    // }
    
    // $data = array('wap_user_id' => $this->wxid, 'cate_id' => $cate_id,
    // 'batch_id'=>$this->batch_id, 'node_id'=>$this->node_id,
    // 'add_time'=>date('YmdHis'));
    // $cjcount = M('twx_laborday_gethis')->add($data);
    // M()->commit();
    // $this->ajaxReturn("sucess", "领取成功", 1);
    // } else {
    // M()->rollback();
    // $fail_arr = array('1001', '1002', '1003', '1006', '1005', '1016',
    // '1014');
    // if (in_array($resp['resp_id'], $fail_arr))
    // $resp_msg = "您来晚了，该奖品已经被抢光了";
    // else
    // $resp_msg = "很遗憾领取失败";
    
    // $this->ajaxReturn("error", $resp_msg, 0);
    // }
    // }
    // protected function _setGlobalJs($arr = array())
    // {
    // $this->js_global = array_merge($this->js_global, $arr);
    // $this->assign('js_global', json_encode($this->js_global));
    // }
    
    // 响应json
    protected function responseJson($code, $msg, $data = array(), $debug = array()) {
        if (IS_AJAX) {
            header('Content-type:text/json;charset=utf-8');
        }
        $resp = array(
            'code' => $code, 
            'msg' => $msg, 
            'data' => $data);
        if (C('SHOW_PAGE_TRACE')) {
            $resp['debug'] = $debug;
        }
        log_write(print_r($resp, true), 'RESPONSE');
        echo json_encode($resp);
        tag('view_end');
        exit();
    }

    /**
     * 根据微信发的语音的severId下载到服务器
     */
    public function upload() {
        $serverId = I('serverId'); // 微信服务器上的语音id
        $time = I('time'); // 录制的时间长度
        $accessToken = $this->WeiXinService->getAccessToken(C('WEIXIN.appid'), 
            C('WEIXIN.secret'));
        $token = is_array($accessToken) ? $accessToken['access_token'] : $accessToken;
        $result = $this->WeiXinService->downloadWeixinFile($token, $serverId);
        log_write(
            'accessToken数组：' . $token . ',serverVoiceId:' . $serverId . ',结果：' .
                 json_encode($result));
        $savePath = APP_PATH . 'Upload/UploadVoice/' . $this->node_id . '/' .
             date('Y/m/d');
        // 检查上传目录
        if (! is_dir($savePath)) {
            // 检查目录是否编码后的
            if (is_dir(base64_decode($savePath))) {
                $savePath = base64_decode($savePath);
            } else {
                // 尝试创建目录
                if (! mkdir($savePath, 0777, true)) {
                    $this->error('上传目录' . $savePath . '不存在');
                }
            }
        } else {
            if (! is_writeable($savePath)) {
                $this->error('上传目录' . $savePath . '不可写');
            }
        }
        $name = $savePath . '/' . date("YmdHis") . mt_rand(100000, 999999) .
             '.amr';
        $this->WeiXinService->saveWeixinFile($name, $result['body']);
        $fromOpenId = $this->fromOpenId;
        $toOpenId = $this->toOpenId;
        // M('tdraft')->add(array('content' => json_encode($result['header'])));
        try {
            $name = D('WxVoice')->transferToMp3($name);
            D('WxVoice')->addRecord($this->node_id, $this->marketInfo['id'], 
                $name, $fromOpenId, $toOpenId, $time);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
        $this->success();
    }

    public function blessWall() {
        // $topThree = D('WxVoice')->getTopThree($this->node_id,
        // $this->marketInfo['id'], $this->toOpenId, $this->fromOpenId);
        // $this->assign('topThree', $topThree);
        $record = D('WxVoice')->getVoiceList($this->node_id, 
            $this->marketInfo['id'], $this->toOpenId, $this->fromOpenId, 1, 
            false, 'v.id desc');
        $length = count($record);
        unset($record[($length - 1)]);
        $voiceCount = D('WxVoice')->getVoiceList($this->node_id, 
            $this->marketInfo['id'], $this->toOpenId, $this->fromOpenId, 1, true);
        // $firstRecord = $record[0];
        $firstRecord = D('WxVoice')->getVoiceList($this->node_id, 
            $this->marketInfo['id'], $this->toOpenId, $this->fromOpenId, 1, 
            false, 'v.id asc', '1');
        $this->assign('firstRecord', $firstRecord[0]);
        $traceModel = M('twx_voice_trace');
        // 如果这个活动被这个用户点过赞了 就不能再点了
        // $hasClicked = $traceModel->where(array(
        // 'm_id' => $this->marketInfo['id'],
        // 'open_id' => $this->fromOpenId,
        // 'click_flag' => '2'))->find();
        // $hasClicked = $hasClicked ? true : false;
        // $this->assign('hasClicked', $hasClicked);
        $this->assign('voiceCount', ($voiceCount - 1)); // 声音的记录条数
        log_write('record:' . json_encode($record));
        $this->assign('record', $record);
        // 是否有自己的祝福墙
        $hasOwnWall = D('WxVoice')->hasOwnWall($this->node_id, 
            $this->marketInfo['id'], $this->fromOpenId);
        $this->assign('hasOwnWall', $hasOwnWall);
        $configData = unserialize($this->marketInfo['config_data']);
        $bg_pic2 = empty($configData['bg_pic2']) ? '' : get_upload_url(
            $configData['bg_pic2']);
        $this->assign('bg_pic2', $bg_pic2);
        $this->cacheControl();
        $next_url = U('TwoFestival/ajaxGetPage', 
            array(
                'page' => '2', 
                'id' => $this->id));
        $this->assign('next_url', $next_url);
        $this->display();
    }

    public function ajaxGetPage() {
        $mId = $this->batch_id;
        $page = I('page');
        $record = D('WxVoice')->getVoiceList($this->node_id, 
            $this->marketInfo['id'], $this->toOpenId, $this->fromOpenId, $page);
        $this->assign('record', $record);
        $html = $this->fetch('page');
        echo $html;
        exit();
    }

    /**
     * 手机发送验证码
     *
     * @author lwb
     */
    public function sendCheckCode() {
        $overdue = $this->checkDate();
        if ($overdue === false) { // 该活动不在有效期之内
            $this->showErrorByErrno(- 1016);
        }
        
        $mobile = I('post.phone_no', null);
        if (! check_str($mobile, 
            array(
                'null' => false, 
                'strtype' => 'mobile'), $error)) {
            $this->showErrorByErrno(- 1008, null, $error);
        }
        
        // 发送频率验证
        $check_code = session('checkCode');
        $oldMobile = isset($check_code['mobile']) ? $check_code['mobile'] : '';
        if (! empty($check_code) && $oldMobile == $mobile &&
             (time() - $check_code['add_time']) < $this->expiresTime) {
            $this->showErrorByErrno(- 1017, null, 
                time() - $check_code['add_time']);
        }
        $num = mt_rand(1000, 9999);
        // 短信内容
        $node_name = D('MemberRecruit', 'Service')->getNodeInfo(
            $this->marketInfo['node_id']);
        $code_info = "【{$node_name}】双旦祝福,您此次的动态验证码为：{$num} 如非本人操作请忽略！";
        // 通知支撑
        $transaction_id = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
                                                                    // 请求参数
        $req_array = array(
            'NotifyReq' => array(
                'TransactionID' => $transaction_id, 
                'ISSPID' => C('MOBILE_ISSPID'), 
                'SystemID' => C('ISS_SYSTEM_ID'), 
                'SendLevel' => '1', 
                'Recipients' => array(
                    'Number' => $mobile),  // 手机号
                'SendClass' => 'MMS', 
                'MessageText' => $code_info,  // 短信内容
                'Subject' => '', 
                'ActivityID' => C('MOBILE_ACTIVITYID'), 
                'ChannelID' => '', 
                'ExtentCode' => ''));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $resp_array = $RemoteRequest->requestIssServ($req_array);
        
        $ret_msg = $resp_array['NotifyRes']['Status'];
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
             $ret_msg['StatusCode'] != '0001')) {
            $this->showErrorByErrno(- 1018);
        }
        $check_code = array(
            'number' => $num, 
            'add_time' => time(), 
            'mobile' => $mobile);
        session('checkCode', $check_code);
        $this->success('验证码已发送');
    }

    public function cjSubmit() {
        $id = $this->id;
        $overdue = $this->checkDate();
        if ($overdue === false) {
            $this->success(
                array(
                    'code' => - 1066, 
                    'msg' => '活动已过期或不可用')); // 活动不可用,或过期
        }
        
        if ($this->marketInfo['is_cj'] != '1')
            $this->success(array(
                'code' => - 1040)); // 不用抽奖
        
        if (! $this->isPost()) {
            $this->success(array(
                'code' => - 1067)); // 非法提交
        }
        
        // 如果是预览渠道不用抽奖
        $previewChannelId = D('Channel')->getPreviewChannelId($this->node_id);
        $channelId = $this->channel_id ? $this->channel_id : 0;
        $previewChannelId = (int) $previewChannelId;
        $channelId = (int) $channelId;
        if ($previewChannelId === $channelId) {
            $this->success(array(
                'code' => - 1048)); // 预览渠道直接跳转
        }
        // 短信验证检验
        $code = I('code', 'null');
        $mobile = I('mobile', 'null');
        if (empty($code) || empty($mobile)) {
            $this->success(
                array(
                    'code' => - 1035, 
                    'msg' => '手机号或验证码不能为空', 
                    'mobile' => $mobile));
        }
        $phoneCheckCode = session('checkCode');
        if (empty($phoneCheckCode) || $phoneCheckCode['number'] != $code) { // 验证码不正确
            $this->success(
                array(
                    'code' => - 1035, 
                    'msg' => '验证码不正确', 
                    'mobile' => $mobile));
        }
        if (time() - $phoneCheckCode['add_time'] > 600) { // 这个600秒是抄劳动节的（600秒后，验证码失效）
            $this->success(
                array(
                    'code' => - 1035, 
                    'msg' => '验证码失效', 
                    'mobile' => $mobile));
        }
        if ($mobile != $phoneCheckCode['mobile']) { // 防止收到验证码后，换个任意手机号码填写
            $this->success(
                array(
                    'code' => - 1035, 
                    'msg' => '验证码不正确', 
                    'mobile' => $mobile));
        }
        
        // 是否抽过奖
        $where = array(
            'mobile' => $this->wxUserInfo['openid'], 
            'batch_id' => $this->marketInfo['id']);
        $cjTraceResult = D('CjTrace')->getCjTrace($where, self::SELECT_TYPE_ONE);
        $tmpChoujian = session('_TmpChouJian_');
        if (! empty($tmpChoujian)) { // 如果有session表示下发没有成功
                                     // 重发的逻辑
            $this->resendCode();
        } else {
            if ($cjTraceResult) {
                $this->success(
                    array(
                        'code' => - 1040)); // 已经抽过奖了，直接告诉页面跳转
            }
            // 完整抽奖逻辑
            import('@.Vendor.ChouJiang');
            $mobile = I('mobile');
            $otherParam = array(
                'wx_open_id' => $this->wxUserInfo['openid'], 
                'wx_nick' => $this->wxUserInfo['nickname']);
            $choujiang = new ChouJiang($id, $mobile, $this->full_id, '', 
                $otherParam); // 双旦只能是微信抽奖，每个用户只能抽一次
            $resp = $choujiang->send_code();
            if (isset($resp['resp_id']) && $resp['resp_id'] == '0000') {
                // 防止下发失败，记录session
                $cj_code = time() . mt_rand(100, 999);
                session('_TmpChouJian_', 
                    array(
                        'cj_code' => $cj_code, 
                        'request_id' => $resp['request_id'], 
                        'cj_trace_id' => $resp['cj_trace_id'], 
                        'card_ext' => $resp['card_ext'], 
                        'card_id' => $resp['card_id'], 
                        'batch_no' => $resp['batch_no']));
                log_write('抽奖返回参数' . var_export($resp, true));
                $this->resendCode();
            } else {
                if ($resp['resp_id'] == '1016') {
                    $this->success(
                        array(
                            'code' => - 1040)); // 已经参与过了,1040表示直接跳祝福墙
                } else {
                    $this->success(
                        array(
                            'code' => - 1002, 
                            'msg' => '很遗憾未中奖')); // 1002表示未中奖
                }
            }
        }
    }

    /**
     * 微信登陆参加的活动， 需要重新下发短彩信的操作
     */
    protected function resendCode() {
        $mobile = I('mobile');
        $tmpChoujian = session('_TmpChouJian_');
        M()->startTrans();
        // 修改数据库中的手机号字段，并且调用重发接口
        $result = M('tcj_trace')->where(
            array(
                'id' => $tmpChoujian['cj_trace_id']))->save(
            array(
                'send_mobile' => $mobile));
        if (false === $result) {
            log_write(
                'tcj_trace修改手机号失败，id:' . $this->id . ',resp:' .
                     json_encode($tmpChoujian));
            M()->rollback();
            $this->success(
                array(
                    'code' => - 1069, 
                    'msg' => '下发奖品失败'));
        }
        // 修改发码表的字段
        $result = M('tbarcode_trace')->where(
            array(
                'request_id' => $tmpChoujian['request_id']))->save(
            array(
                'phone_no' => $mobile));
        if (false === $result) {
            log_write(
                'tbarcode_trace修改发码表的字段失败，id:' . $this->id . ',resp:' .
                     json_encode($tmpChoujian));
            M()->rollback();
            $this->success(
                array(
                    'code' => - 1069, 
                    'msg' => '下发奖品失败'));
        }
        M()->commit();
        // 然后调用重发接口
        import("@.Vendor.CjInterface");
        $req = new CjInterface();
        $result = $req->cj_resend(
            array(
                'request_id' => $tmpChoujian['request_id'], 
                'node_id' => $this->node_id, 
                'user_id' => '00000000'));
        if (! $result || $result['resp_id'] != '0000') {
            log_write(
                '重发失败' . json_encode($tmpChoujian) . ',重发的返回结果：' .
                     json_encode($result));
            $this->success(
                array(
                    'code' => - 1069, 
                    'msg' => '下发奖品失败'));
        }
        $batchInfo = M('tbatch_info')->field('batch_img,batch_name')
            ->where(
            array(
                'batch_no' => $tmpChoujian['batch_no']))
            ->find();
        $data = array(
            'goods_name' => $batchInfo['batch_name'], 
            'code' => 0, 
            'batch_img' => get_upload_url($batchInfo['batch_img']));
        session('_TmpChouJian_', null);
        $this->success($data);
    }

    /**
     * 设置session中的剩余抽奖次数
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $drawLotteryId
     * @param $step
     */
    public function setTmpLeftChances($drawLotteryId, $step) {
        $drawLotteryChanceInfo = $this->getDrawLotteryChanceInfo($drawLotteryId);
        if (isset($drawLotteryChanceInfo['leftChances'])) {
            $drawLotteryChanceInfo['leftChances'] -= abs($step);
            if ($drawLotteryChanceInfo['leftChances'] < 0) {
                $drawLotteryChanceInfo['leftChances'] = 0;
            }
        }
        $this->setDrawLotteryChanceInfo($drawLotteryId, $drawLotteryChanceInfo);
    }

    /**
     * 查询调用抽奖异步结果
     */
    public function getDrawLotteryResult() {
        import("@.Vendor.CjInterface");
        $cjInterface = new CjInterface();
        $key = I('get.key', I('post.key'));
        log_write('redis key:' . $key);
        $result = $cjInterface->getCjResultByKey($key);
        
        if (! $result) {
            $this->responseJson(- 1001, 'waiting');
            
            return;
        }
        log_write('result:' . print_r($result, true));
        if ($result['resp_id'] != '0000') { // 如果是被限制都统一叫未中奖
            $noAwardNoticeMsg = self::NO_AWARD_TIP;
            $this->responseJson($result['resp_id'], $noAwardNoticeMsg);
            return;
        }
        
        $goods_id = $result['resp_data']['rule_id'];
        // $bonus_id = $result['resp_data']['bonus_use_detail_id'];
        $where = "a.id='{$goods_id}'";
        
        $goodsDefaultInfo = array(
            'goods_name' => '', 
            'goods_id' => '', 
            'goods_type' => '', 
            'bonus_id' => '', 
            'link_url' => '');
        $goodsInfo = D('DrawLottery')->getGoodsInfoAndBounsInfo($where);
        $goodsInfo = array_merge($goodsDefaultInfo, $goodsInfo);
        // 中了手机凭证奖品
        $resp = $result['resp_data'];
        if (! empty($resp['request_id'])) {
            log_write(print_r($resp, true));
            // 临时中奖码，需要响应给前台，校验一下正确性用的，校验正确后失效,以免直接暴露 request_id和
            // cj_trace_id,用完以后清空
            $cj_code = time() . mt_rand(100, 999);
            session('_TmpChouJian_', 
                array(
                    'cj_code' => $cj_code, 
                    'request_id' => $resp['request_id'], 
                    'cj_trace_id' => $resp['cj_trace_id'], 
                    'card_ext' => $resp['card_ext'], 
                    'card_id' => $resp['card_id'], 
                    'goods_info' => $goodsInfo));
            $result['resp_data']['cj_code'] = $cj_code;
        }
        // 返回结果
        $result['resp_data']['goods_info'] = $goodsInfo;
        log_write(
            '$result[\'resp_data\']:' . print_r($result['resp_data'], true));
        
        $formatedInfo = $this->formatDrawLotteryResult($result);
        $this->responseJson($formatedInfo['status'], $formatedInfo['msg'], 
            $formatedInfo['data']);
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $result
     * @return array
     */
    public function formatDrawLotteryResult($result) {
        $finalMsg = array(
            'status' => '', 
            'msg' => '', 
            'data' => '');
        if (isset($result['resp_id']) && $result['resp_id'] == '0000') { // 已中奖
            $finalMsg['status'] = 0; // 成功
            $finalMsg['data'] = $result['resp_data'];
            if (isset($result['resp_data']['card_id']) &&
                 $result['resp_data']['card_id']) { // 微信卡券
                $finalMsg['msg'] = isset(
                    $result['resp_data']['goods_info']['goods_name']) ? $result['resp_data']['goods_info']['goods_name'] : '';
            } else if (isset($result['resp_data']['goods_info']['bonus_id']) &&
                 $result['resp_data']['goods_info']['bonus_id']) { // 红包
                $finalMsg['msg'] = isset(
                    $result['resp_data']['goods_info']['goods_name']) ? $result['resp_data']['goods_info']['goods_name'] : '';
                $finalMsg['msg'] .= '， 请到' .
                     $result['resp_data']['goods_info']['node_name'] . '使用';
            } else { // 卡券
                $finalMsg['msg'] = isset(
                    $result['resp_data']['goods_info']['goods_name']) ? $result['resp_data']['goods_info']['goods_name'] : '';
                $finalMsg['msg'] .= '， 中奖凭证将自动下发至您的手机，请注意查收!';
            }
        } else { // 未中奖
            $finalMsg = array(
                'status' => $result['resp_id'], 
                'msg' => self::NO_AWARD_TIP, 
                'data' => '');
        }
        return $finalMsg;
    }

    /**
     * 更新当前用户有没有听过这个语音
     */
    public function updateListened() {
        $voiceId = I('voiceId');
        $currentOpenId = I('currentOpenId');
        $voiceTraceModel = M('twx_voice_trace');
        $where = array(
            'voice_id' => $voiceId, 
            'open_id' => $currentOpenId);
        $re = $voiceTraceModel->where($where)->find();
        if (! $re) {
            $data = array(
                'voice_id' => $voiceId, 
                'open_id' => $currentOpenId, 
                'listen_flag' => '2', 
                'm_id' => $this->marketInfo['id']);
            $voiceTraceModel->add($data);
        } else {
            $voiceTraceModel->where($where)->save(
                array(
                    'listen_flag' => '2'));
        }
    }

    public function updateLike() {
        $data = $_POST['changedata'];
        D('WxVoice')->updateLikeCount($data, $this->fromOpenId, 
            $this->marketInfo['id']);
        $this->success();
    }

    /**
     * 禁止浏览器缓存
     */
    private function cacheControl() {
        if (! headers_sent()) {
            header('pragma:no-cache');
            header('Cache-Control:no-store, must-revalidate');
            header('expires:0');
        }
        $cacheControl = '<META HTTP-EQUIV="pragma" CONTENT="no-cache">
<META HTTP-EQUIV="Cache-Control" CONTENT="no-store, must-revalidate">
<META HTTP-EQUIV="expires" CONTENT="Wed, 26 Feb 1997 08:21:57 GMT">
<META HTTP-EQUIV="expires" CONTENT="0"> ';
        $this->assign('cacheControl', $cacheControl);
    }

    /**
     * 校验活动是否过期
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param bool|false $showFlag
     *
     * @return bool
     */
    public function checkDate($showFalg = null) {
        $query_arr = $this->marketInfo;
        $channelInfo = $this->channelInfo;
        if (! empty($query_arr['start_time']) && ! empty($query_arr['end_time'])) {
            $this_time = date('YmdHis');
            if ($this_time < $query_arr['start_time'] ||
                 $this_time > $query_arr['end_time']) {
                return false;
            }
        }
        return true;
    }

    /**
     * [checkFbVerify 双蛋非标验证]
     *
     * @param [type] $nodeId [商户机构号]
     * @param [type] $getsInfo [url中的参数]
     * @return [type] [无]
     */
    private function checkFbVerify($nodeId, $batchType, $getsInfo) {
        if ($nodeId == C('shuangdan_fb_nodeid') && $batchType == '59') {
            log_write(
                '双蛋非标验证，机构号：' . $nodeId . '->请求信息为：' . print_r($getsInfo, true));
            $fanscode = isset($getsInfo['fanscode']) ? $getsInfo['fanscode'] : session(
                'fanscode');
            if (! empty($fanscode)) {
                $decode = base64_decode($fanscode);
                $cic = substr($decode, 0, 3);
                $time = substr($decode, 3);
                if ($cic != 'cic' || (time() - $time) > 600) {
                    log_write(
                        '双蛋非标验证，机构号：' . $nodeId . '->fanscode参数不对' . $decode .
                             '，或其中的时间戳已超过10分钟：' . date('YmdHis', $time));
                    redirect('http://wx.95585.cn/thirdgift/shuangdan');
                    exit();
                }
            } else {
                log_write('双蛋非标验证，机构号：' . $nodeId . '->fanscode没有传过来');
                redirect('http://wx.95585.cn/thirdgift/shuangdan');
                exit();
            }
            log_write(
                '双蛋非标验证，机构号：' . $nodeId . '->验证通过。fanscode参数为' .
                     base64_decode($getsInfo['fanscode']));
            session('fanscode', $fanscode);
        }
    }
}