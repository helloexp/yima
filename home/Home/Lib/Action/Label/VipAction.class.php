<?php

class VipAction extends MyBaseAction {

    public $expiresTime = 120;
    // 手机验证码过期时间
    
    // 初始化
    public function _initialize() {
        parent::_initialize();
        if ($this->node_id == '00004488') {
            $fb_wap_title = "长沙移动";
        } else {
            $fb_wap_title = "O2OPark";
        }
        $this->assign('fb_wap_title', $fb_wap_title);
        $this->assign('id', $this->id);
        if($_GET['y']){session('userInfo',false);}
    }

    public function init() {
        $userInfo = session('userInfo');
        if ($userInfo)
            $this->redirect(
                U('Label/Vip/index', 
                    array(
                        'id' => $this->id)));
            // 微信会员判断
        $sId = I('s_id', null, 'mysql_real_escape_string');
        $loginTime = I('login_time', null);
        if (! empty($sId) && ! empty($loginTime)) {
            // 获取password
            $loginInfo = M('tmember_login')->where(
                "node_id='" . $this->node_id . "' AND s_id='{$sId}'")->find();
            if (! $loginInfo)
                $this->error('参数错误');
                // 校验sid
            if ($sId != md5($loginTime . $loginInfo['password']))
                $this->error('错误的参数');
                // 过期校验
            C('WeixinServ', require (CONF_PATH . 'configWeixinServ.php'));
            $expired = C('WeixinServ.SID_TIME');
            if (time() - strtotime($loginTime) > ($expired + 5) * 60)
                $this->error('该链接已过期,请重新获取');
            
            $arr = array(
                's_id' => $sId, 
                'login_time' => $loginTime);
            session('token', $arr);
            
            if (! empty($loginInfo['phone_no'])) {
                session('userInfo', $loginInfo['phone_no']);
                $this->redirect(
                    U('Label/Vip/index', 
                        array(
                            'id' => $this->id)));
            }
        } else {
            $this->error('请通过微信访问！');
        }
    }
    
    // 校验是否登录
    public function checkLogin() {
        $userInfo = session('userInfo');
        if (! $userInfo) {
            $this->error('该链接已过期,请重新获取');
        }
        
        // 校验手机号是否在白名单
        $check_arr = array(
            'node_id' => $this->node_id, 
            'batch_type' => $this->batch_type, 
            'batch_id' => $this->batch_id, 
            'mobile' => $userInfo, 
            'status' => '1');
        if ($this->node_id == '00000370') {
            if ($this->batch_id != '9617') {
                $query = M('tfb_phone')->where($check_arr)->find();
                if (! $query)
                    $this->error('您还不是长沙移动vip会员，暂不能参与此次活动。');
            }
        }
        
        return $userInfo;
    }
    
    // 校验是否有系统消息
    public function checksysMsg($mobile) {
        $count = M('tfb_msg')->where(
            "node_id='" . $this->node_id . "' and batch_id='" . $this->batch_id .
                 "' and mobile='" . $mobile .
                 "' and  status = '2' and check_is_cj='2' and is_zj='1' ")->count();
        $this->assign('sysCount', $count);
    }
    
    // 登录页
    public function login() {
        $isLogin = session('userInfo');
        if ($isLogin)
            $this->redirect(
                U('Label/Vip/index', 
                    array(
                        'id' => $this->id)));
        $token = session('token');
        if (! $token)
            $this->init();
        
        if ($this->isPost()) {
            $sid = $token['s_id'];
            $mobile = I('phone', NULL);
            $check_code = I('check_code', NULL);
            if (empty($mobile) || empty($check_code))
                $this->error('手机号和验证码不能为空！');
                
                // 手机验证码
            $phoneCheckCode = session('checkCode');
            if (empty($phoneCheckCode) &&
                 $phoneCheckCode['number'] != $check_code)
                $this->error('手机验证码不正确');
            if (time() - $phoneCheckCode['add_time'] > $this->expiresTime)
                $this->error('手机验证码已经过期');
                
                // 更新手机号
            $map = array(
                'node_id' => $this->node_id, 
                's_id' => $sid);
            $query = M('tmember_login')->where($map)->save(
                array(
                    'phone_no' => $mobile));
            if ($query === false)
                $this->error('登录失败！');
            
            session('userInfo', $mobile);
            session('checkCode', null);
            $this->redirect(
                U('Label/Vip/index', 
                    array(
                        'id' => $this->id)));
        }
        
        $this->assign('expiresTime', $this->expiresTime);
        $this->display();
    }

    public function add() {
        $this->checkLogin();
        $userMobile = session('userInfo');
        $this->checksysMsg($userMobile);
        
        if ($this->isPost()) {
            
            $title = I('title', NULL);
            $content = I('content', NULL);
            if (empty($title) || empty($content))
                $this->ajaxReturn("error", "请填写标题和内容！", 0);
            
            $is_exits = M('tfb_msg')->where(
                "node_id='" . $this->node_id . "' and batch_id='" .
                     $this->batch_id . "' and mobile='" . $userMobile .
                     "' and title='" . $title . "'")->count();
            if ($is_exits > 0)
                $this->ajaxReturn("error", "请勿重复操作！", 0);
                
                // 判断已发文次数
            $fb_fwnt = M('tmarketing_info')->where(
                array(
                    'node_id' => $this->node_id, 
                    'id' => $this->batch_id))->getField('fb_fwnt');
            if ($fb_fwnt > 0) {
                $fb_fwnt_ed = M('tfb_msg')->where(
                    array(
                        'node_id' => $this->node_id, 
                        'batch_id' => $this->batch_id, 
                        'status' => '2', 
                        'mobile' => $userMobile))->count();
                if ($fb_fwnt_ed >= $fb_fwnt)
                    $this->ajaxReturn("error", "发文次数超过限制! ", 0);
            }
            
            if (isset($_FILES['pic'])) {
                
                $pic = $_FILES['pic'];
                if ($pic['error'] !== 0)
                    $this->ajaxReturn("error", "图片上传失败，请重试！", 0);
                if ($pic['size'] === 0)
                    $this->ajaxReturn("error", "图片无效，请重新选择！", 0);
                if ($pic['size'] > 1024 * 1024 * 1)
                    $this->ajaxReturn("error", "图片超过1M，请重新选择！", 0);
                $pic_ex = strtolower(
                    substr($pic['name'], strripos($pic['name'], '.') + 1));
                if (! in_array($pic_ex, 
                    array(
                        'gif', 
                        'jpg', 
                        'jpeg', 
                        'bmp', 
                        'png'), true))
                    $this->ajaxReturn("error", "图片格式不对！", 0);
                
                import('ORG.Net.UploadFile');
                $upload = new UploadFile(); // 实例化上传类
                $upload->maxSize = 1024 * 1024 * 1;
                $upload->savePath = C('UPLOAD') . '/FbUpload/'; // 设置附件
                $upload->saveRule = time() . sprintf('%06s', mt_rand(0, 100000));
                $upload->supportMulti = false;
                $upload->allowExts = array(
                    'gif', 
                    'jpg', 
                    'jpeg', 
                    'bmp', 
                    'png');
                /*
                 * $upload->thumb=true; $upload->thumbPrefix='m';
                 * $upload->thumbMaxWidth=150; $upload->thumbMaxHeight=150;
                 * $upload->thumbRemoveOrigin=true;
                 */
                
                if (! $upload->upload()) { // 上传错误提示错误信息
                    $this->ajaxReturn("error", "图片上传失败！", 0);
                    exit();
                } else { // 上传成功 获取上传文件信息
                    $info = $upload->getUploadFileInfo();
                    $up_img = $info[0]['savename'];
                }
            }
            
            $data = array(
                'batch_id' => $this->batch_id, 
                'batch_type' => $this->batch_type, 
                'title' => $title, 
                'content' => $content, 
                'up_img' => $up_img, 
                'add_time' => date('YmdHis'), 
                'node_id' => $this->node_id, 
                'mobile' => $userMobile, 
                'ip' => get_client_ip());
            if (empty($up_img))
                $t_msg = 'success';
            else
                $t_msg = $up_img;
            $query = M('tfb_msg')->add($data);
            if (! $query)
                $this->ajaxReturn("error", "图片上传失败！", 0);
            else
                $this->ajaxReturn($t_msg, "内容发布成功，审核通过的消息将会发送至您的消息中心", 1);
        }
        
        $maxlength = M('tmarketing_info')->where(
            array(
                'node_id' => $this->node_id, 
                'id' => $this->batch_id, 
                'batch_type' => $this->batch_type))->getField('fb_fwznt');
        $this->assign('maxlength', $maxlength);
        $this->display();
    }
    
    // 手机发送验证码
    public function sendCheckCode() {
        $token = session('token');
        if (! $token)
            $this->init();
        
        $overdue = $this->checkDate();
        if ($overdue === false)
            $this->error("该活动不在有效期之内！");
        
        $phoneNo = I('post.phone', null);
        if (! check_str($phoneNo, 
            array(
                'null' => false, 
                'strtype' => 'mobile'), $error)) {
            $this->error("手机号{$error}");
        }
        
        // 发送频率验证
        $checkCode = session('checkCode');
        if (! empty($checkCode) &&
             (time() - $checkCode['add_time']) < $this->expiresTime) {
            $this->error('验证码发送过于频繁!');
        }

        //if(is_production()){
        if(true){
            $num = mt_rand(1000, 9999);
            // 短信内容
            //$nodeName = M('tnode_info')->where("node_id={$saveInfo['node_id']}")->getField('node_short_name');
            $codeInfo = "短信验证码：{$num}；如非本人操作请忽略！";
/* 旧代码
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
                    'SendClass' => 'MMS',
                    'MessageText' => $codeInfo,  // 短信内容
                    'Subject' => '',
                    'ActivityID' => '14053089541',
                    'ChannelID' => '',
                    'ExtentCode' => ''));
            $RemoteRequest = D('RemoteRequest', 'Service');
            $resp_array = $RemoteRequest->requestIssServ($req_array);
            // dump($resp_array);exit;
            $ret_msg = $resp_array['NotifyRes']['Status'];

            if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
                    $ret_msg['StatusCode'] != '0001')) {
                $this->error('发送失败');
            }
*/
            $res=send_SMS($this->node_id,$phoneNo,$codeInfo);
            if(!$res){
                 $this->error('发送失败');
            }
        }else{
            $num = 1111;
        }

        $checkCode = array(
            'number' => $num, 
            'add_time' => time());
        session('checkCode', $checkCode);
        $this->success('验证码已发送');
    }
    
    // 列表
    public function index() {
        $this->checkLogin();
        $userMobile = session('userInfo');
        $type = I('type', null);
        
        // 访问量
        import('@.Vendor.DataStat');
        $opt = new DataStat($this->id, $this->full_id);
        $opt->UpdateRecord();
        
        import('ORG.Util.Page'); // 导入分页类
                                 // 最新发布
        if (empty($type)) {
            $mapcount = M('tfb_msg')->field('id,title,content,dz_count,up_img')
                ->where(
                array(
                    'node_id' => $this->node_id, 
                    'status' => '2', 
                    'batch_id' => $this->batch_id))
                ->count();
            $Page = new Page($mapcount, 8);
            if ($_GET['p'] > ceil($mapcount / 8) && $this->isAjax())
                return;
            
            $result = M('tfb_msg')->field('id,title,content,dz_count,up_img')
                ->where(
                array(
                    'node_id' => $this->node_id, 
                    'status' => '2', 
                    'batch_id' => $this->batch_id))
                ->order('id desc')
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->select();
        } elseif ($type == '1') {
            $mapcount = M('tfb_msg')->field('id,title,content,dz_count,up_img')
                ->where(
                array(
                    'node_id' => $this->node_id, 
                    'status' => '2', 
                    'batch_id' => $this->batch_id))
                ->count();
            $Page = new Page($mapcount, 8);
            if ($_GET['p'] > ceil($mapcount / 8) && $this->isAjax())
                return;
            
            $result = M('tfb_msg')->field('id,title,content,dz_count,up_img')
                ->where(
                array(
                    'node_id' => $this->node_id, 
                    'status' => '2', 
                    'batch_id' => $this->batch_id))
                ->order('dz_count desc')
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->select();
        } elseif ($type == '2') {
            $mapcount = M('tfb_msg')->field('id,title,content,dz_count,up_img')
                ->where(
                array(
                    'node_id' => $this->node_id, 
                    'status' => '2', 
                    'mobile' => $userMobile, 
                    'batch_id' => $this->batch_id))
                ->count();
            $Page = new Page($mapcount, 8);
            if ($_GET['p'] > ceil($mapcount / 8) && $this->isAjax())
                return;
            
            $result = M('tfb_msg')->field('id,title,content,dz_count,up_img')
                ->where(
                array(
                    'node_id' => $this->node_id, 
                    'status' => '2', 
                    'mobile' => $userMobile, 
                    'batch_id' => $this->batch_id))
                ->order('dz_count desc')
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->select();
        }
        $dz_arr = array();
        if ($result) {
            foreach ($result as $dz) {
                $query_arr = M('tfb_msg_seq')->field('id')
                    ->where(
                    array(
                        'node_id' => $this->node_id, 
                        'msg_id' => $dz['id'], 
                        'mobile' => $userMobile))
                    ->find();
                if ($query_arr)
                    $dz_arr[] = $dz['id'];
            }
        }
        // 组装下一页url
        $nowPage = empty($_GET['p']) ? 1 : $_GET['p'];
        $nexUrl = U('Label/Vip/index', 
            array(
                'id' => $this->id, 
                'type' => $type), '', '', true) . '&p=' . ($nowPage + 1);
        // 获取logo
        $batch_logo = M('tmarketing_info')->where(
            array(
                'node_id' => $this->node_id, 
                'id' => $this->batch_id))->getField('log_img');
        $this->assign('batch_logo', $batch_logo);
        
        $this->checksysMsg($userMobile);
        $this->assign('type', $type);
        $this->assign('nextUrl', $nexUrl);
        $this->assign('list', $result);
        $this->assign('dz_arr', $dz_arr);
        $this->display();
    }
    
    // 详情页面
    public function info() {
        $this->checkLogin();
        $userMobile = session('userInfo');
        $msg_id = I('msg_id', null);
        if (empty($msg_id))
            $this->error('参数错误！');
        $result = M('tfb_msg')->where(
            array(
                'node_id' => $this->node_id, 
                'id' => $msg_id))->find();
        
        $query_arr = M('tfb_msg_seq')->where(
            array(
                'node_id' => $this->node_id, 
                'msg_id' => $msg_id, 
                'mobile' => $userMobile))->find();
        $query = M('tfb_msg')->where(
            array(
                'node_id' => $this->node_id, 
                'id' => $msg_id))->setInc('click_count', 1);
        $is_dz = $query_arr ? true : false;
        $this->checksysMsg($userMobile);
        $this->assign('is_dz', $is_dz);
        $this->assign('list', $result);
        $this->display();
    }
    
    // 系统消息
    public function sysMsg() {
        $this->checkLogin();
        $userMobile = session('userInfo');
        import('ORG.Util.Page'); // 导入分页类
        $Page = new Page($mapcount, 12);
        $result = M('tfb_msg')->field(
            'id,title,check_time,check_is_cj,status,is_zj')
            ->where(
            "node_id= '" . $this->node_id . "' and batch_id='" . $this->batch_id .
                 "' and mobile='" . $userMobile . "' and status in(2,3)")
            ->order('id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        
        // 组装下一页url
        $nowPage = empty($_GET['p']) ? 1 : $_GET['p'];
        $nexUrl = U('Label/Vip/sysMsg', 
            array(
                'id' => $this->id, 
                'mobile' => $userMobile), '', '', true) . '&p=' . ($nowPage + 1);
        
        $this->checksysMsg($userMobile);
        $this->assign('is_cj', $is_cj);
        $this->assign('is_zj', $is_zj);
        $this->assign('type', $type);
        $this->assign('nextUrl', $nexUrl);
        $this->assign('list', $result);
        $this->display();
    }
    
    // 点赞
    public function dz() {
        $this->checkLogin();
        $userMobile = session('userInfo');
        $msg_id = I('msg_id', NULL);
        
        $batch_info = M('tmarketing_info')->field(
            'fb_dz_is_cj,fb_dzn,fb_dz_cj_set')
            ->where(
            array(
                'node_id' => $this->node_id, 
                'id' => $this->batch_id))
            ->find();
        
        // 点赞最大次数
        $dz_max_count = M('tfb_msg_seq')->where(
            array(
                'batch_id' => $this->batch_id, 
                'mobile' => $userMobile))->count();
        if ($batch_info['fb_dzn'] != '0') {
            if ($dz_max_count >= $batch_info['fb_dzn'])
                $this->ajaxReturn("error", "不能再点赞了", 0);
        }
        
        $query_arr = M('tfb_msg_seq')->where(
            array(
                'node_id' => $this->node_id, 
                'msg_id' => $msg_id, 
                'mobile' => $userMobile))->find();
        
        if (! $query_arr) {
            $tranDb = new Model();
            $tranDb->startTrans();
            $data = array(
                'batch_type' => $this->batch_type, 
                'batch_id' => $this->batch_id, 
                'mobile' => $userMobile, 
                'msg_id' => $msg_id, 
                'node_id' => $this->node_id, 
                'ip' => get_client_ip(), 
                'add_time' => date('YmdHis'));
            $row = M('tfb_msg_seq')->add($data);
            if (! $row) {
                $tranDb->rollback();
                $this->ajaxReturn("error", "系统错误！", 0);
            } else {
                $query = M('tfb_msg')->where(
                    array(
                        'node_id' => $this->node_id, 
                        'id' => $msg_id))->setInc('dz_count', 1);
                if (! $query) {
                    $tranDb->rollback();
                    $this->ajaxReturn("error", "系统错误！", 0);
                }
                $tranDb->commit();
                
                if ($batch_info['fb_dz_is_cj'] != '2')
                    $this->ajaxReturn("success", "无抽奖权限！", 1);
                
                if ($batch_info['fb_dz_cj_set'] == '1') {
                    if ($dz_max_count > 0)
                        $this->ajaxReturn("success", "只有一次抽奖权限！", 1);
                }
                $this->ajaxReturn("iscj", "可以抽奖", 1);
            }
        }
        $this->ajaxReturn("success", "点赞成功", 1);
    }
    
    // 发文抽奖
    public function fwcj() {
        $this->checkLogin();
        $userMobile = session('userInfo');
        $msg_id = I('msg_id', NULL);
        $query = M('tfb_msg')->where(
            array(
                'node_id' => $this->node_id, 
                'id' => $msg_id, 
                'mobile' => $userMobile))->find();
        if ($query['is_zj'] != '1')
            $this->ajaxReturn("error", "无抽奖权限", 0);
        
        import('@.Vendor.VipChouJiang');
        $choujiang = new VipChouJiang($this->id, $userMobile, $this->full_id, 
            '1');
        $resp = $choujiang->send_code();
        if (isset($resp['resp_id']) && $resp['resp_id'] == '0000') {
            $query = M('tfb_msg')->where(
                array(
                    'node_id' => $this->node_id, 
                    'id' => $msg_id, 
                    'mobile' => $userMobile))->save(
                array(
                    'is_zj' => '3'));
            $this->ajaxReturn("success", "恭喜您中奖了，我们已将奖品凭证发送至您的手机上，请注意查收。", 1);
        } else {
            $query = M('tfb_msg')->where(
                array(
                    'node_id' => $this->node_id, 
                    'id' => $msg_id, 
                    'mobile' => $userMobile))->save(
                array(
                    'is_zj' => '2'));
            $this->ajaxReturn("error", "很抱歉，未能抽中奖品", 0);
        }
    }

    public function dzCj() {
        $this->checkLogin();
        $userMobile = session('userInfo');
        $msg_id = I('msg_id', NULL);
        if (empty($msg_id))
            $this->ajaxReturn("error", "参数错误！", 1);
            
            // 是否已抽过奖
        $is_cj = M('tfb_msg_seq')->where(
            array(
                'msg_id' => $msg_id, 
                'mobile' => $userMobile, 
                'batch_id' => $this->batch_id))->getField('is_cj');
        if ($is_cj == '2')
            $this->ajaxReturn("error", "很抱歉，您已抽过奖！", 1);
        
        $batch_info = M('tmarketing_info')->field(
            'fb_dz_is_cj,fb_dzn,fb_dz_cj_set')
            ->where(
            array(
                'node_id' => $this->node_id, 
                'id' => $this->batch_id))
            ->find();
        
        // 点赞最大次数
        $dz_max_count = M('tfb_msg_seq')->where(
            array(
                'batch_id' => $this->batch_id, 
                'mobile' => $userMobile))->count();
        if ($batch_info['fb_dzn'] != '0') {
            if ($dz_max_count >= $batch_info['fb_dzn'])
                $this->ajaxReturn("error", "很抱歉，未能抽中奖品！", 1);
        }
        
        if ($batch_info['fb_dz_is_cj'] != '2')
            $this->ajaxReturn("error", "很抱歉，未能抽中奖品！", 1);
        
        if ($batch_info['fb_dz_cj_set'] == '1') {
            if ($dz_max_count > 0)
                $this->ajaxReturn("error", "很抱歉，未能抽中奖品！", 1);
        }
        
        // 抽奖
        import('@.Vendor.VipChouJiang');
        $choujiang = new VipChouJiang($this->id, $userMobile, $this->full_id, 
            '2');
        $resp = $choujiang->send_code();
        if (isset($resp['resp_id']) && $resp['resp_id'] == '0000') {
            $this->ajaxReturn("success", "恭喜您中奖了，我们已将奖品凭证发送至您的手机上，请注意查收。", 1);
        } else {
            $this->ajaxReturn("error", "很抱歉，未能抽中奖品！", 1);
        }
    }
}