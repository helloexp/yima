<?php

class MamaAction extends MyBaseAction {

    public $blessings_arr = array(
        '妈！这些天特别忙，忙着挣钱，挣了钱好给您买好吃的，好用的，让您漂漂亮亮出门，羡慕死您身边那一群姐妹们，母亲节快乐，我爱您！', 
        '老妈，别再天天对着镜子瞅了，您脸上的皱纹都是您曾今美丽笑容的见证，在我心目中，您永远是最美的。祝您母亲节快乐！', 
        '大清早起床就感觉今天特别不一般，阳光明媚，鸟语花香整个世界都充满了爱。原来今天是母亲节，妈妈您陪我走过每个春夏秋冬，如今您依然是我生命里最美丽的阳光，母亲节快乐！', 
        '感谢妈妈在多年前生下了全国限量版的我，并给我幸福生活，在此我要祝您永远年轻漂亮，身体健康！您在我心里永远是世界上最美丽的女人！', 
        '世界上最好吃的饭，是妈妈做的饭，最伟大的爱，是妈妈给予的爱，今天母亲节，是您的节日。妈妈，祝您节日快乐，我永远爱您！', 
        '又到了母亲节，意味着妈妈您又辛苦了一年，知道您再累也不会和我们讲，既然今天是母亲节，希望您在我的祝福中度过美好的一天，休息休息吧，妈妈！');

    public function index() {
        if ($this->batch_type != '18') {
            $this->error(
                array(
                    'errorImg' => '__PUBLIC__/Label/Image/waperro5.png', 
                    'errorTxt' => '错误访问！', 
                    'errorSoftTxt' => '你的访问地址出错啦~'));
        }
        // 访问量
        import('@.Vendor.DataStat');
        $opt = new DataStat($this->id, $this->full_id);
        $opt->UpdateRecord();
        
        // 活动
        $batchModel = M('tmarketing_info');
        $row = $batchModel->where(
            array(
                'id' => $this->batch_id))->find();
        
        // 是否过期
        $this->assign('id', $this->id);
        $this->assign('batch_type', $this->batch_type);
        $this->assign('batch_id', $this->batch_id);
        $this->assign('row', $row);
        
        $this->assign('blessings_arr', $this->blessings_arr);
        $this->display(); // 输出模板
    }

    public function shorturl($long_url) {
        $apiUrl = C('ISS_SERV_FOR_IMAGECO');
        $req_arr = array(
            'CreateShortUrlReq' => array(
                'SystemID' => C('ISS_SYSTEM_ID'), 
                'TransactionID' => time() . rand(10000, 99999), 
                'OriginUrl' => "<![CDATA[$long_url]]>"));
        
        import('@.ORG.Util.Xml') or die('@.ORG.Util.Xml导入失败');
        $xml = new Xml();
        $str = $xml->getXMLFromArray($req_arr, 'gbk');
        $error = '';
        $result_str = httpPost($apiUrl, $str, $error);
        if ($error) {
            echo $error;
            return '';
        }
        
        $arr = $xml->parse($result_str);
        $arr = $xml->getArrayNoRoot();
        
        return $arr['Status']['StatusCode'] == '0000' ? $arr['ShortUrl'] : '';
    }

    /**
     * 送贺卡抽奖
     */
    public function submit() {
        $id = $this->id;
        $overdue = $this->checkDate();
        if ($overdue === false)
            $this->ajaxReturn("error", "该活动不在有效期之内！", 0);
            // 是否抽奖
        $query_arr = M('tmarketing_info')->field('is_cj,start_time,end_time')
            ->where(
            array(
                'id' => $this->batch_id, 
                'batch_type' => $this->batch_type))
            ->find();
        if ($query_arr['is_cj'] != '1')
            $this->ajaxReturn("error", "未查询到抽奖活动！", 0);
        
        if (! $this->isPost()) {
            $this->ajaxReturn("error", "非法提交！", 0);
        }
        if (session('verify_cj') != md5(I('post.verify'))) {
            $this->ajaxReturn("error", "验证码错误！", 0);
        }
        
        // 儿子手机
        $mobile = I('post.mobile', '', 'trim');
        // 妈妈手机
        $mama_mobile = I('post.mama_mobile', '', 'trim');
        // 儿子称呼
        $son_name = I('post.son_name', '', 'trim');
        // 祝福语
        $blessings_id = I('post.blessings_id', 0, 'trim, intval');
        
        $len = mb_strlen($son_name, 'utf8');
        
        if ($len == 0 || $len > 4)
            $this->ajaxReturn("error", "您的称呼错误！", 0);
        if ($this->blessings_arr[$blessings_id] == '')
            $blessings_id = 0;
        if (! is_numeric(trim($mobile)) || strlen(trim($mobile)) != '11')
            $this->ajaxReturn("error", "您的手机号错误！", 0);
        if (! is_numeric(trim($mama_mobile)) ||
             strlen(trim($mama_mobile)) != '11')
            $this->ajaxReturn("error", "妈妈的手机号错误！", 0);
        if (empty($id))
            $this->ajaxReturn("error", "错误的请求！", 0);
            
            // 贺卡处理
        $count = M('tmama_trace')->where(
            array(
                'batch_type' => '18', 
                'batch_id' => $this->batch_id, 
                'mama_mobile' => $mama_mobile, 
                'mobile' => $mobile))->count();
        
        if ($count > 0)
            $this->ajaxReturn("error", "您已经给该手机号送过贺卡了！", 0);
        
        $count = M('tmama_trace')->where(
            array(
                'batch_type' => '18', 
                'batch_id' => $this->batch_id, 
                'mama_mobile' => $mama_mobile))->count();
        if ($count >= 3)
            $this->ajaxReturn("error", "妈妈的手机号接受的贺卡数量已达活动上线了！", 0);
            
            // 发送贺卡短信==========开始
        M()->startTrans();
        $trace_data = array(
            'label_id' => $id, 
            'batch_type' => $this->batch_type, 
            'batch_id' => $this->batch_id, 
            'channel_id' => $this->channel_id, 
            'mobile' => $mobile, 
            'son_name' => $son_name, 
            'mama_mobile' => $mama_mobile, 
            'bless_id' => $blessings_id, 
            'add_time' => date('YmdHis'));
        $mama_trace_id = M('tmama_trace')->add($trace_data);
        if ($mama_trace_id === false) {
            M()->rollback();
            $this->ajaxReturn("error", "系统错误，请重试！", 0);
        }
        
        $codeInfo = "妈妈您辛苦了,我想对你说:\"妈妈，我爱你\"!{$son_name}做了张贺卡给您哦!点这里查看,还可以参与抽奖的哦>>";
        import('@.ORG.Crypt.Des') or die('[@.ORG.Crypt.Des]导入包失败');
        $codeInfo .= $this->shorturl(
            U('Label/Mama/card_view', 
                http_build_query(
                    array(
                        'id' => $id, 
                        'cardId' => base64_encode(
                            Des::encrypt($mama_trace_id, 
                                C('BATCH_MAMA.DES_KEY'))))), '', '', true));
        
        // 通知支撑
        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
                                                                   // 请求参数
        $req_array = array(
            'NotifyReq' => array(
                'TransactionID' => $TransactionID, 
                'ISSPID' => C('OTHER_MOBILE_ISSPID'), 
                'SystemID' => C('ISS_SYSTEM_ID'), 
                'SendLevel' => '1', 
                'Recipients' => array(
                    'Number' => $mama_mobile), 
                'SendClass' => 'MMS', 
                'MessageText' => $codeInfo, 
                'Subject' => '', 
                'ActivityID' => C('OTHER_MOBILE_ACTIVITYID'), 
                'ChannelID' => '', 
                'ExtentCode' => ''));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $resp_array = $RemoteRequest->requestIssServ($req_array);
        $ret_msg = $resp_array['NotifyRes']['Status'];
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
             $ret_msg['StatusCode'] != '0001')) {
            M()->rollback();
            log_write('母情节活动，发送贺卡短信失败！参数：' . print_r($req_array, true) . '返回结果：' . var_export($resp_array, true), 
                'REMOTE');
            $this->ajaxReturn("error", "贺卡发送失败！请重试！", 0);
        }
        M()->commit();
        // 发送贺卡短信==========结束
        
        session('verify_cj', null);
        import('@.Vendor.ChouJiang');
        $choujiang = new ChouJiang($id, $mobile);
        $resp = $choujiang->send_code();
        
        if (isset($resp['resp_id']) && $resp['resp_id'] == '0000') {
            $cj_msg = "恭喜您抽中奖品,奖品将以短彩信发送至您的手机，请注意查收！";
            
            if ($resp['award_level'] == '1') { // 一等奖
                $cj_msg = "恭喜您获得一等奖,奖品将以短彩信发送至您的手机，请注意查收！";
            } elseif ($resp['award_level'] == '2') { // 二等奖
                $cj_msg = "恭喜您获得二等奖,奖品将以短彩信发送至您的手机，请注意查收！";
            } elseif ($resp['award_level'] == '3') { // 三等奖
                $cj_msg = "恭喜您获得三等奖,奖品将以短彩信发送至您的手机，请注意查收！";
            }
            $this->ajaxReturn("success", "贺卡赠送成功！" . $cj_msg, 1);
        } else {
            
            if ($resp['resp_id'] == '1005') {
                $resp = '今天您已经参与过抽奖，不能再抽了！';
            } elseif ($resp['resp_id'] == '1016') {
                $resp = '您已经参与过该抽奖活动，不能再抽了！';
            } else {
                $resp = '很遗憾，未中奖,感谢您的参与！';
            }
            $this->ajaxReturn("error", "贺卡赠送成功！" . $resp, 0);
        }
    }

    /**
     * 送贺卡抽奖
     */
    public function mama_submit() {
        $id = $this->id;
        $overdue = $this->checkDate();
        if ($overdue === false)
            $this->ajaxReturn("error", "该活动不在有效期之内！", 0);
            // 是否抽奖
        $query_arr = M('tmarketing_info')->field('is_cj,start_time,end_time')
            ->where(
            array(
                'id' => $this->batch_id, 
                'batch_type' => $this->batch_type))
            ->find();
        if ($query_arr['is_cj'] != '1')
            $this->ajaxReturn("error", "未查询到抽奖活动！", 0);
        
        if (! $this->isPost())
            $this->ajaxReturn("error", "非法提交！", 0);
        
        import('@.ORG.Crypt.Des') or die('[@.ORG.Crypt.Des]导入包失败');
        $cardId = I('cardId', '', 'trim');
        $trace_id = Des::decrypt(base64_decode(rawurldecode($cardId)), 
            C('BATCH_MAMA.DES_KEY'));
        $info = M('tmama_trace')->where(
            array(
                'batch_id' => $this->batch_id, 
                'id' => $trace_id))->find();
        
        if (! $info)
            $this->ajaxReturn("error", "错误的参数！", 0);
        
        if ($info['check_flag'] != '0')
            $this->ajaxReturn("error", "已经抽过奖了！", 0);
        
        import('@.Vendor.ChouJiang');
        $mobile = $info['mama_mobile'];
        $choujiang = new ChouJiang($id, $mobile);
        $resp = $choujiang->send_code();
        
        // 将记录标注为已抽奖
        M('tmama_trace')->where(
            array(
                'batch_id' => $this->batch_id, 
                'id' => $trace_id))->save(
            array(
                'check_flag' => '1'));
        
        if (isset($resp['resp_id']) && $resp['resp_id'] == '0000') {
            $cj_msg = "恭喜您抽中奖品,奖品将以短彩信发送至您的手机，请注意查收！";
            
            if ($resp['award_level'] == '1') { // 一等奖
                $cj_msg = "恭喜您获得一等奖,奖品将以短彩信发送至您的手机，请注意查收！";
            } elseif ($resp['award_level'] == '2') { // 二等奖
                $cj_msg = "恭喜您获得二等奖,奖品将以短彩信发送至您的手机，请注意查收！";
            } elseif ($resp['award_level'] == '3') { // 三等奖
                $cj_msg = "恭喜您获得三等奖,奖品将以短彩信发送至您的手机，请注意查收！";
            }
            $this->ajaxReturn("success", $cj_msg, 1);
        } else {
            if ($resp['resp_id'] == '1005') {
                $resp = '今天您已经参与过抽奖，不能再抽了！';
            } elseif ($resp['resp_id'] == '1016') {
                $resp = '您已经参与过该抽奖活动，不能再抽了！';
            } else {
                $resp = '很遗憾，未中奖,感谢您的参与！';
            }
            $this->ajaxReturn("error", $resp, 0);
        }
    }

    public function card_view() {
        import('@.ORG.Crypt.Des') or die('[@.ORG.Crypt.Des]导入包失败');
        $cardId = I('cardId', '', 'trim');
        $cardId = str_replace(' ', '+', $cardId);
        $trace_id = Des::decrypt(base64_decode($cardId), 
            C('BATCH_MAMA.DES_KEY'));
        $info = M('tmama_trace')->where(
            array(
                'batch_id' => $this->batch_id, 
                'id' => $trace_id))->find();
        
        if (! $info)
            $this->error('错误访问！');
        
        $this->assign('info', $info);
        $this->assign('cardId', $cardId);
        $this->display();
    }
}