<?php

/**
 * 功能：检测新消息
 *
 * @author cxz @update author bao 时间：2013-03-07 更新时间:2013-6-24
 */
class MessageCheckNewAction extends BaseAction {

    public $node_id;
    // 商户号
    public $user_id;
    // 用户号
    public $pos_id;

    // 终端号
    public function _initialize() {
        parent::_initialize();
        $this->checkLogin(); // 校验用户登录
    }

    public function run() {
        $rs = $this->check();
        if (!$rs) {
            $resp_desc = "请填写商户号、终端号、用户号";
            $this->returnError($resp_desc);
        }
        // 得到最近一条的时间
        $dataInfo  = M('TuserInfo')->where("USER_ID={$this->user_id}")->field('last_read_message_time')->find();
        $read_time = $dataInfo['last_read_message_time'];

        $messageCount = M('TposMessage')->limit('0,1')->order('ADD_TIME desc')->where("add_time > '$read_time'")->count();

        if (!$messageCount) {
            $resp_desc = "没有发现新消息";
            $news_flag = 0;
        } else {
            $resp_desc = "有发现新消息";
            $news_flag = 1;
        }
        $this->returnSuccess($resp_desc, array(
                        'news_flag' => $news_flag,
                ));
    }

    private function check() {
        if (empty($this->node_id) || empty($this->pos_id) || empty($this->user_id)) {
            return false;
        }

        return true;
    }
}