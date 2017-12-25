<?php

class WheelAction extends Action {

    public $_authAccessMap = '*';

    public function index() {
    }

    /**
     * [introduce 活动介绍页]
     *
     * @return [null] [无]
     */
    public function introduce() {
        $url = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $appId = C('WEIXIN.appid'); // 应用ID
        $appSecret = C('WEIXIN.secret'); // 应用密钥
        import('@.Vendor.JSSDK', '', '.php');
        $jssdk = new JSSDK($appId, $appSecret);
        $signPackage = $jssdk->GetSignPackage($url);
        $wx_share_config = array(
            'appId' => $appId, 
            'timestamp' => $signPackage['timestamp'], 
            'nonceStr' => $signPackage['nonceStr'], 
            'signature' => $signPackage['signature'], 
            'url' => $signPackage['url']);
        $shareArr = array(
            'config' => $wx_share_config, 
            'title' => '幸运大转盘，玩转你的微营销', 
            'desc' => '首款免费在手机端就可以创建的营销场景，点击创建立即领取多重好礼', 
            'imgUrl' => C('CURRENT_DOMAIN') .
                 'Home/Public/Label/Image/Item/dzp/banner.jpg');
        $this->assign('shareData', $shareArr);
        $this->display();
    }

    /**
     * [login 手机号码登陆及ajax提交]
     *
     * @return [null] [无]
     */
    public function login() {
        if ($this->isPost()) {
            $bindPhoneNo = I('post.bindPhoneNo', null); // 手机号码
            $verifyCode = I('post.verifyCode', null); // 验证码，作为临时密码
                                                      // 判断密码是否同获取的相同
            $sessVerifyCode = session('verifyCode');
            if ($verifyCode != $sessVerifyCode['number']) {
                $this->error("密码错误");
            }
            if ($bindPhoneNo != $sessVerifyCode['bindPhoneNo']) {
                $this->error("手机号已更改，请重新获取密码");
            }
            $wheelModel = D('Wheel');
            // 获取用户类型,没有此用户，将会在临时表中创建该用户
            try {
                $userType = $wheelModel->getUserType($bindPhoneNo, $verifyCode);
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }
            $data['userType'] = $userType;
            // 登录成功，存入session
            $loginSuccess = array(
                'bindPhoneNo' => $bindPhoneNo, 
                'flag' => 1);
            session('loginSuccess', $loginSuccess);
            $this->success("手机验证成功", '', $data);
        }
        $this->display();
    }

    /**
     * [gotoPcTips 前往PC端]
     *
     * @return [type] [description]
     */
    public function gotoPcTips() {
        self::checkIsLogin();
        $wheelModel = D('Wheel');
        // 获取有效期相关信息
        $wheelInfo = $wheelModel->getWheelInfo();
        $validityPeriodInfo = D('Wheel')->getValidityPeriod($wheelInfo);
        $bindChannelInfo = D('BindChannel')->getChannelInfoAndBatchChannelId(
            $wheelInfo['is_register'], 
            array(
                $wheelInfo['is_channel']), $wheelInfo['is_marketing']);
        $this->assign('bindChannelInfo', 
            $bindChannelInfo['channelInfoArr'][$wheelInfo['is_channel']]);
        $this->assign('validityPeriodInfo', $validityPeriodInfo);
        $this->display();
    }

    /**
     * [hasEventTips 已经创建过活动]
     *
     * @return boolean [description]
     */
    public function hasEventTips() {
        self::checkIsLogin();
        $wheelModel = D('Wheel');
        // 获取有效期相关信息
        $wheelInfo = $wheelModel->getWheelInfo();
        $validityPeriodInfo = D('Wheel')->getValidityPeriod($wheelInfo);
        $bindChannelInfo = D('BindChannel')->getChannelInfoAndBatchChannelId(
            $wheelInfo['is_register'], 
            array(
                $wheelInfo['is_channel']), $wheelInfo['is_marketing']);
        $this->assign('bindChannelInfo', 
            $bindChannelInfo['channelInfoArr'][$wheelInfo['is_channel']]);
        $this->assign('validityPeriodInfo', $validityPeriodInfo);
        $this->display();
    }

    /**
     * [createEvent 创建活动]
     *
     * @return [type] [description]
     */
    public function createEvent() {
        self::checkIsLogin();
        $wheelModel = D('Wheel');
        if ($this->isPost()) {
            $data['name'] = I('name', "");
            $data['batchName'] = I('batchName', "");
            $data['startTime'] = I('startTime', "");
            $data['endTime'] = I('endTime', "");
            $data['serveTelphone'] = I('serveTelphone', "");
            $data['batchIntroduce'] = I('batchIntroduce', "");
            $data['haslogo'] = I('haslogo', "");
            $data['logo'] = $_FILES['logo'];
            try {
                $Result = $wheelModel->checkCreateEvent($data);
                $wheelModel->createEvent($Result);
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }
            $this->success("保存成功");
        }
        // 查看是否存入过此数据
        $eventData = $wheelModel->getEventData();
        $wheelInfo = $wheelModel->getWheelInfo();
        // 防止好事者，不按照流程进入此页面
        if ($wheelInfo['is_batchchannel'] != 0) {
            $this->error("您已经开通发布过wap端活动");
        }
        $this->assign('eventData', $eventData);
        $this->assign('wheelInfo', $wheelInfo);
        $this->assign('curDate', date('Y-m-d'));
        $this->display();
    }

    /**
     * [createTicket 创建卡券]
     *
     * @return [type] [description]
     */
    public function createTicket() {
        $wheelModel = D('Wheel');
        if ($this->isPost()) {
            $data['name'] = I('name', "");
            $data['number'] = I('number', "");
            $data['startTime'] = I('startTime', "");
            $data['endTime'] = I('endTime', "");
            $data['haslogo'] = I('haslogo', "");
            $data['logo'] = $_FILES['logo'];
            try {
                $Result = $wheelModel->checkCreateTicket($data);
                $wheelModel->createTicket($Result);
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }
            $this->success("保存成功");
        }
        // 查看是否存入过此数据
        $ticketData = $wheelModel->getTicketData();
        $wheelInfo = $wheelModel->getWheelInfo();
        // 防止好事者，不按照流程进入此页面
        if ($wheelInfo['is_batchchannel'] != 0) {
            $this->error("您已经开通发布过wap端活动");
        }
        $this->assign('ticketData', $ticketData);
        $this->assign('wheelInfo', $wheelInfo);
        $this->assign('curDate', date('Y-m-d'));
        $this->display();
    }

    /**
     * [setLuckDraw 设置抽奖]
     */
    public function setLuckDraw() {
        $wheelModel = D('Wheel');
        if ($this->isPost()) {
            $data['luckProbability'] = I('post.luckProbability');
            $data['dayLimit'] = I('post.dayLimit');
            $data['msgTitle'] = I('post.msgTitle');
            $data['msgContent'] = I('post.msgContent');
            try {
                // 判断字段是否满足要求
                $Result = $wheelModel->checkLuckDraw($data);
                // 将字段插入到数据库中
                $wheelModel->setLuckDraw($Result);
                // 取出所有数据，进行注册，然后创建一个免费活动
                $wheelModel->createRealData();
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }
            $this->success("设置抽奖成功");
        }
        // 查看是否存入过此数据
        $luckDrawData = $wheelModel->getLuckDrawData();
        $wheelInfo = $wheelModel->getWheelInfo();
        // 防止好事者，不按照流程进入此页面
        if ($wheelInfo['is_batchchannel'] != 0) {
            $this->error("您已经开通发布过wap端活动");
        }
        $this->assign('luckDrawData', $luckDrawData);
        $this->assign('wheelInfo', $wheelInfo);
        $this->display();
    }

    /**
     * [releaseEvent 发布活动]
     *
     * @return [type] [description]
     */
    public function releaseEvent() {
        $wheelInfo = D('Wheel')->getWheelInfo();
        $validityPeriodInfo = D('Wheel')->getValidityPeriod($wheelInfo);
        $bindChannelInfo = D('BindChannel')->getChannelInfoAndBatchChannelId(
            $wheelInfo['is_register'], 
            array(
                $wheelInfo['is_channel']), $wheelInfo['is_marketing']);
        $this->assign('bindChannelInfo', 
            $bindChannelInfo['channelInfoArr'][$wheelInfo['is_channel']]);
        $this->assign('validityPeriodInfo', $validityPeriodInfo);
        $this->display();
    }

    /**
     * [verifyCode 验证码]
     *
     * @return [type] [description]
     */
    public function verifyCode() {
        $wheelModel = D('Wheel');
        $bindPhoneNo = I('bindPhoneNo', null);
        $text = "您的动态密码是：verifyCode，有效期expiresTime秒。您也可以使用该手机账号和密码登录旺财官网（www.wangcaio2o.com）哦！如非本人操作请忽略。";
        try {
            // 发送短信
            $wheelModel->sendVerifyCode($bindPhoneNo, 60, $text, 6);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
        $this->success("发送成功");
    }

    /**
     * [checkIsLogin 检查是否登录]
     *
     * @return [type] [没有登录则跳转到登录页]
     */
    private function checkIsLogin() {
        // 获取登录信息
        $loginSuccess = session('loginSuccess');
        // 如果没有登录，则跳转到登录页
        if ($loginSuccess['flag'] != 1) {
            redirect(U('Label/Wheel/login'));
        }
    }
}

?>