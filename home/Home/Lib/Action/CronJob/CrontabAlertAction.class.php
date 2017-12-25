<?php

/**
 * Class CrontabAlertAction 旺财日终异常报警crontab
 */
class CrontabAlertAction extends Action {

    function index() {
        $time = date("Ymd", strtotime("-1 day")); // 昨天
        $mailContent['petname'] = 'gongxg@imageco.com.cn'; // 收件人
        $mailContent['test_title'] = '旺财日终异常报警-' . $time; // 邮件标题
        $this->TprocLogModel = D('TprocLog'); // 实例化model
        $mailContent['text_content'] = $this->TprocLogModel->getDaydate($time); // 获取邮件正文
        $flag = to_email($mailContent); // 发送邮件，成功返回2,
        if ($flag == 2) {
            echo "发送成功";
        } else {
            echo "发送失败";
        }
    }
}