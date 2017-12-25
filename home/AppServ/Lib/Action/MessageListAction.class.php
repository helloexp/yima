<?php

/**
 * 功能：消息列表
 *
 * @author cxz @update author bao 时间：2013-03-07 更新时间:2013-6-24
 */
class MessageListAction extends BaseAction {

    public $node_id;
    // 商户号
    public $user_id;
    // 用户号
    public $pos_id;
    // 终端号
    public $current_page;
    // 当前页
    public $page_size;

    // 每页大小
    public function _initialize() {
        parent::_initialize();
        $this->checkLogin(); // 校验用户登录
        // 初始化请求参数
        $this->current_page = I('current_page', 1);
        $this->page_size    = I('page_size');
    }

    public function run() {
        $rs = $this->check();
        if (!$rs) {
            $resp_desc = "请填写商户号、终端号、用户号、当前页、每页大小";
            $this->returnError($resp_desc);
        }
        $twposMessage = M('TposMessage');
        $start        = ($this->current_page - 1) * $this->page_size;

        $message_info = $twposMessage->field('msg_title, msg_content, add_time, msg_type')->order('add_time desc')->limit($start,
                $this->page_size)->select();
        if (!empty($message_info)) {
            $resp_desc = "获取消息列表成功";
            foreach ($message_info as &$value) {
                $value             = array_change_key_case($value, CASE_LOWER);
                $value['add_time'] = date("Y-m-d H:i:s", $value['add_time']);
            }
            unset($value);
            $all_count                 = $twposMessage->count();
            $page_info                 = array();
            $page_info['page_size']    = $this->page_size;
            $page_info['current_page'] = $this->current_page;
            $page_info['total_num']    = $all_count;

            // 获取最新发布消息的时间
            $read_time = time();

            // 将最新发布消息的时间保存在当前用户里
            $rs = M('TuserInfo')->where("USER_ID='" . $this->user_id . "'")->save(array(
                    'last_read_message_time' => $read_time,
            ));
            if ($rs === false) {
                $resp_desc = "更新当前用户查看消息最新时间失败";
                $this->returnError($resp_desc);
            }
            $this->returnSuccess($resp_desc, array(
                            'msg_list' => $message_info,
                            'page_nav' => $page_info,
                    ));
        } else {
            $resp_desc = "找不到任何消息";
            $this->returnError($resp_desc, '');
        }
    }

    private function check() {
        if (empty($this->node_id) || empty($this->pos_id) || empty($this->user_id) || empty($this->current_page) || empty($this->page_size)) {
            return false;
        }

        return true;
    }
}