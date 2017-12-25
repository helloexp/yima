<?php

/*
 * 非标业务 圣诞集图标
 */
class XiamenShousiAction extends IndexAction {

    public $node_id;

    public $req;

    public $wx;

    public $act_name;

    public $config;

    public $media_id;

    public $giftDao;

    public $_logId;

    public function _init(&$params) {
        $this->_logId = mt_rand(10, 99) . time();
        $this->act_name = $params[0];
        $this->req = $params[1]->req;
        $this->wx = $params[1]->wx;
        $this->giftDao = M('tfb_xiameng_shousi_gift');
        // 载入配置文件
        $this->config = require (CONF_PATH .
             'WeixinServ/Feibiao/cfgXiamenShousi.php');
    }

    public function run(&$params) {
        $this->_init($params); // 初始化
                               // 以下是自定义处理
        $msgType = $this->req['msgType'];
        $config = $this->config;
        // 得到随机图片
        if ($msgType == 'text') {
            // 判断关键词
            if (! $this->_checkKwd($this->req['Content'], $config['keyword'])) {
                return;
            }
            // 判断活动时间
            if ($config['begin_time'] > date('YmdHis')) {
                $this->_log('活动未开始' . $config['begin_time']);
                $txt = $this->config['notbegin_text'];
                $this->_respMsg($txt);
                exit();
            }
            // 判断用户是否已经领取过
            $where = array(
                'wx_id' => $this->req['fromUserName'], 
                'gift_type' => 0);
            $giftInfo = $this->giftDao->where($where)->find();
            $this->_log('查询 SQL：' . M()->getLastSql());
            // 已经领取过了,提示已经领过，并且下发图片
            if ($giftInfo) {
                $this->_log('领取过');
                $txt = $this->config['fetched_text'];
                $this->_respMsg($txt, $giftInfo);
                exit();
            }
            // 如果未领取
            // 查询一条未领取，随机类型
            $where = array(
                'status' => 0, 
                'gift_type' => 0);
            //
            $giftInfo = $this->giftDao->where($where)->find();
            $this->_log('领取 SQL：' . M()->getLastSql());
            // 已经领完
            if (! $giftInfo) {
                $txt = $this->config['over_text'];
                $this->_respMsg($txt);
                // $this->_sendImg($this->config['over_img']);
                exit();
            }
            // 开始领券
            if ($giftInfo) {
                $gift_type = $giftInfo['gift_type'];
                // 如果成功，则开始更新券
                $where = array(
                    'id' => $giftInfo['id']);
                $saveResult = $this->giftDao->where($where)->save(
                    array(
                        'wx_id' => $this->req['fromUserName'], 
                        'fetched_time' => date('YmdHis'), 
                        'status' => 1));
                if ($saveResult === false) {
                    $this->_respMsg("系统正忙");
                }
                $txt = $this->config['gift_text'][$gift_type];
                $txt = str_replace("\r\n", "\n", $txt);
                // 加上辅助码
                $ercode = substr($giftInfo['gift_id'], 0, 
                    strpos($giftInfo['gift_id'], '.'));
                if (! $ercode) {
                    $this->_respMsg("系统正忙[02]" . $giftInfo['gift_id']);
                }
                $txt = '[辅助数字串:' . $ercode . ']' . $txt;
                $this->_respMsg($txt, $giftInfo);
                exit();
            }
            exit();
        }
    }

    protected function _getImgMediaId($pic_path) {
        $hash = 'FeibiaoMedia' . md5_file($pic_path);
        $media_id = F($hash);
        if ($media_id) {
            // 得到缓存
            $this->_log("cache_file:" . $pic_path);
            return $media_id;
        }
        $media_id = $this->wx->uploadMediaFile($pic_path);
        F($hash, $media_id);
        return $media_id;
    }

    protected function _checkKwd($kwd, $list) {
        foreach ($list as $v) {
            if (strpos($kwd, $v) !== false) {
                return true;
            }
        }
        return false;
    }
    
    // 发送图片
    protected function _sendImg($pic_path, $media_id = '') {
        $this->_log('------------' . $pic_path);
        if (! $media_id) {
            $media_id = $this->_getImgMediaId($pic_path);
        }
        
        $respArr = array(
            "touser" => $this->req['fromUserName'], 
            "msgtype" => "image", 
            "image" => array(
                "media_id" => $media_id));
        $result = $this->wx->sendMsg($respArr);
        $this->media_id = $media_id; // 保存media_id
                                     
        // 如果已经更新了accessToken
        if ($this->wx->accessTokenUpdated) {
            // 同步更新数据库的accessToken
            M('tweixin_info')->where(
                array(
                    'node_id' => $this->node_id))->save(
                array(
                    'app_access_token' => $this->wx->accessToken));
        }
        
        return $result;
    }
    // 主动调接口回复图片
    protected function _sendGift($giftInfo) {
        $pic_path = $this->config['img_path'] . '/' . $giftInfo['gift_type'] .
             '/' . $giftInfo['gift_id'];
        $result = $this->_sendImg($pic_path, $giftInfo['media_id']);
        // 错误
        if (! $result || $result['errcode'] != '0') {
            return false;
        }
        if (! $giftInfo['media_id']) {
            // 更新media_id
            $this->giftDao->where(
                array(
                    'id' => $giftInfo['id']))->save(
                array(
                    'media_id' => $this->media_id));
            // //更新media_idend
        }
        return true;
    }

    protected function _respMsg($txt, $giftInfo = null) {
        $this->_log($txt);
        // 回复文字
        $this->wx->respText(array(
            'Content' => $txt));
        // 回复图片
        if (! $giftInfo)
            return;
        
        $result = $this->_sendGift($giftInfo);
        $this->_log('giftInfo' . print_r($giftInfo, true));
        if ($result) {
            $this->_log('回复图片成功');
        } else {
            $this->_log('回复图片失败');
        }
        exit();
    }

    public function _log($msg) {
        log_write($msg, 'XiamenShousi-Log:');
    }
}