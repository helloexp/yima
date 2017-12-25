<?php

/**
 * @@@ mamajie @@@ add dongdong @@@ time 2015/04/10 10:41
 */
class MamaSjbAction extends MyBaseAction {
    // 活动类型
    const mamaJie = 46;
    // 活动配置
    public $marketConf;

    public function _initialize() {
        parent::_initialize();
        $this->judgeLiq();
        
        if ($this->batch_type != self::mamaJie) {
            $this->error(
                array(
                    'errorImg' => '__PUBLIC__/Label/Image/waperro5.png', 
                    'errorTxt' => '错误访问！', 
                    'errorSoftTxt' => '你的访问地址出错啦~'));
        }
        
        $marketInfo = $this->marketInfo;
        
        if ($marketInfo['start_time'] > date('YmdHis')) {
            $this->error(
                array(
                    'errorImg' => '__PUBLIC__/Label/Image/waperro4.png', 
                    'errorTxt' => '该活动未开始！', 
                    'errorSoftTxt' => '活动还没开始哦~'));
        }

        /**
         * 深圳平安母亲节活动定制！by kk @ 2016年5月3日16:08:58
         * todo 活动时间截止到2016年5月12日；13日后可清除下面代码
         */
        if(in_array($this->batch_id, C('szpa.mamaBatchId'))){
            $this->_checkUser(true);
            $szpaSerivce = D('Szpa', 'Service');
            $this->wxid = $szpaSerivce->getMamaWxid($this->wxUserInfo, $this->node_id, $this->id);
            if($this->wxid === false){
                $this->error(
                    array(
                        'errorImg' => '__PUBLIC__/Label/Image/waperro4.png',
                        'errorTxt' => '噢，出错了！',
                        'errorSoftTxt' => '请重新进入活动页面~'));
            }
        }
        else{
            if (! session("?MamaSjb")) {
                $this->redirect(
                    U('Label/GetWxUserInfo/weixinSq',
                        array(
                            'id' => $this->id,
                            'laiyuan' => I('laiyuan'))));
            }
            $this->wxid = session("MamaSjb");
        }

        // 分享信息
        $shareUrl = U('index',
            array(
                'id' => $this->id, 
                'laiyuan' => $this->wxid), '', '', TRUE);
        $wx_share_config = D('WeiXin', 'Service')->getWxShareConfig();
        $this->assign('wx_share_config', $wx_share_config);
        $shareArr = array(
            'config' => $wx_share_config, 
            'link' => $shareUrl, 
            'title' => $marketInfo['wap_title'], 
            'desc' => '你Show爱我送礼！', 
            'imgUrl' => get_upload_url($marketInfo['log_img']));
        $this->assign('marketInfo', $marketInfo);
        $this->assign('shareData', $shareArr);
        $this->assign('isSzpaMamaSjb', in_array($this->batch_id , C('szpa.mamaBatchId')) ? 1 : 0);
    }

    public function index() {
        import('@.Vendor.DataStat');
        $opt = new DataStat($this->id, $this->full_id);
        $opt->UpdateRecord();
        // 看看来源有没有给妈妈发卡片
        if (I('laiyuan') != '' && I('laiyuan') != $this->wxid &&
             $this->isAddCard(I('laiyuan')) != false) {
            $this->index2($this->isAddCard(I('laiyuan')), 0);
            die();
        }
        // 看看自己发的卡片
        if ($this->isAddCard($this->wxid) != false) {
            $this->index2($this->isAddCard($this->wxid), 1);
            die();
        }
        
        $arrCount = $this->searchJp();
        // 奖品总数
        $jpnum = 0;
        if ($arrCount) {
            foreach ($arrCount as $v) {
                $jpnum += $v['remain_num'];
            }
        }
        
        $this->assign('id', $this->id);
        $this->assign('batch_type', $this->batch_type);
        $this->assign('batch_id', $this->batch_id);
        $this->assign('heheJp', $arrCount);
        $this->assign('jpnum', $jpnum);
        $this->display();
    }
    
    // 预览卡片
    public function index2($row, $mid = 0) {
        $this->assign('row', $row);
        $this->assign('mid', $mid);
        $this->display('index2');
    }
    
    // 判断浏览器类型
    public function judgeLiq() {
        $useragent = $_SERVER['HTTP_USER_AGENT'];
        if (strpos($useragent, 'MicroMessenger') === false &&
             strpos($useragent, 'Windows Phone') === false) {
            $this->error(
                array(
                    'errorImg' => '__PUBLIC__/Label/Image/waperro7.png', 
                    'errorTxt' => '请使用微信浏览器！', 
                    'errorSoftTxt' => '亲，这个页面要用微信浏览器打开哦~'));
        }
    }
    
    // 查询奖品
    private function searchJp() {
        $jp_sql = "SELECT b.id as cid, b.name,b.score ,c.batch_name,c.batch_img,c.remain_num FROM tcj_batch a
				LEFT JOIN tcj_cate b ON a.cj_cate_id = b.id
				LEFT JOIN tbatch_info c ON a.b_id=c.id
				WHERE  a.status =1  AND a.batch_id ='" .
             $this->batch_id . "' order by b.id";
        $jp_arr = M()->query($jp_sql);
        return $jp_arr;
    }
    
    // 发信息
    private function noteConf($mama_trace_id, $pulsNum, $son) {
        import('@.ORG.Crypt.Des') or die('[@.ORG.Crypt.Des]导入包失败');
        
        $noteInfo = "亲爱的妈妈：在这特殊的日子我想对您说一声：“妈妈我爱你——您的:  " . $son . "”，这是我做的电子贺卡： ";
        $noteInfo .= $this->shorturl(
            U('Label/MamaLook/index', 
                http_build_query(
                    array(
                        'id' => $this->id, 
                        'cardId' => base64_encode(
                            Des::encrypt($mama_trace_id, 
                                C('BATCH_MAMA.DES_KEY'))))), '', '', true));
        // $noteInfo.="您的: ".$son;
        $res = send_SMS($this->node_id, $pulsNum, $noteInfo);
        
        return $res;
    }
    
    // 长换短
    private function shorturl($long_url) {
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
    
    // 贺卡制造
    public function gCsubmit() {
        $verify = I('verify');
        $sonNum = I('sonNum');
        $mamaNum = I('mamaNum');
        $smsContent = I('smsContent');
        $shengri = I('shengri');
        $sonPetName = I('sonPetName');
        $j_thumb = I('j_thumb');
        if (session('verify_cj') != md5($verify))
            $this->error('验证码不正确！');
        if (! preg_match("/^1[34578][0-9]{9}$/", $sonNum))
            $this->error('您的手机号格式不正确');
        
        if (! preg_match("/^1[34578][0-9]{9}$/", $mamaNum))
            $this->error('妈妈手机号格式不正确');
        
        if ($smsContent == '')
            $this->error('请输入您要对妈妈说的话');
        
        if ($shengri == '')
            $this->error('请输入您的生日');
        
        if ($sonPetName == '')
            $this->error('请输入您的昵称');
        
        $label_row = $this->marketInfo;
        if (empty($label_row['member_reg_mid']) === false) {
            $fruit = M('tmember_info')->where(
                array(
                    'phone_no' => $sonNum, 
                    'node_id' => $this->node_id))->find();
            if ($fruit == '') {
                
                $label_id = M('tbatch_channel')->where(
                    array(
                        'batch_id' => $label_row['member_reg_mid']))->getField(
                    'id');
                $this->error($label_id);
            }
        }
        if ($this->isAddCard($this->wxid) != false)
            $this->error('您已经发布过贺卡');
        
        if ($j_thumb != '') {
            $jpgname = date('YmdHis') . mt_rand(1000, 9999) . '.jpg';
            $jpgAddress = './Home/Upload/mamawoaini/' . $jpgname;
            $j_thumb = end(explode(',', $j_thumb));
            if (file_put_contents($jpgAddress, base64_decode($j_thumb)) == false) {
                $this->error('图片上传失败。');
            }
        }
        // 开始入库
        $arr = array(
            'label_id' => $this->id, 
            'batch_type' => self::mamaJie, 
            'batch_id' => $this->batch_id, 
            'channel_id' => $this->channel_id, 
            'mobile' => $sonNum, 
            'son_name' => $sonPetName, 
            'mama_mobile' => $mamaNum, 
            'bless_content' => $smsContent, 
            'son_birthday' => $shengri, 
            'pic_url' => isset($jpgname) ? $jpgname : '', 
            'wxid' => $this->wxid, 
            'add_time' => date('YmdHis'));
        
        $mama_trace_id = M('tmama_trace')->add($arr);
        
        if ($mama_trace_id) {
            if ($this->noteConf($mama_trace_id, $mamaNum, $sonPetName) == false) {
                M('tmama_trace')->where(
                    array(
                        'id' => $mama_trace_id))->delete();
                $this->error('贺卡发送失败了');
            }
            
            $map1 = array(
                'node_id' => $this->node_id, 
                'batch_id' => $this->batch_id, 
                'batch_type' => $this->batch_type);
            $ruleInfo = M('tcj_rule')->where($map1)->find();
            // 未中奖以及中奖提示文字
            $noAwardNotice = explode('|', $ruleInfo['no_award_notice']);
            $noAwardNotice = $noAwardNotice[array_rand($noAwardNotice)];
            $awardNotice = $ruleInfo["cj_resp_text"] == "" ? "恭喜你，中奖了！！！" : $ruleInfo["cj_resp_text"];
            
            import('@.Vendor.ChouJiang');
            $other = [];
            /**
             * 深圳平安母亲节活动定制！by kk @ 2016年5月3日16:08:58
             * todo 活动时间截止到2016年5月12日；13日后可清除下面代码
             */
            if(in_array($this->batch_id, C('szpa.mamaBatchId'))){
                $other['wx_open_id'] = $this->wxUserInfo['openid'];
                $other['wx_nick'] = $this->wxUserInfo['nickname'];
            }

            $choujiang = new ChouJiang($this->id, $sonNum, $this->full_id, null, $other);
            $resp = $choujiang->send_code();

            if (isset($resp['resp_id']) && $resp['resp_id'] == '0000') {
                if ($resp['batch_class'] == '15') {
                    $awardNotice .= ' 恭喜您获得了' . $resp['batch_name']  . '，奖品将在3日内充值到账，请您注意查收！';
                }
                $this->success([
                            'msg' => "发送贺卡成功，" . $awardNotice,
                            'award_flag' => 1
                    ]);
            } else {
                $this->success([
                    'msg' => "发送贺卡成功！" . $noAwardNotice,
                    'award_flag' => 0
                ]);
            }
        }

        //log_write('save mama trace error!' . print_r($arr, true) . M()->_sql());
        $this->error("发送贺卡失败");
    }
    
    // 判断 $kpid 有没有创建 卡片
    private function isAddCard($kpid) {
        $info = M('tmama_trace')->where(
            array(
                'batch_id' => $this->batch_id, 
                'wxid' => $kpid))->find();
        
        return empty($info) ? false : $info;
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

        if ($info == false)
            $this->error('错误访问！');
        
        $this->assign('info', $info);
        $this->assign('cardId', $cardId);
        $this->display('card_view');
    }
}