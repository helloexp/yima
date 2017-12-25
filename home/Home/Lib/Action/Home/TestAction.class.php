<?php

class TestAction extends BaseAction {

    public function _initialize() {
    }

    public function index() {
        $this->display();
    }

    public function dolog() {
        log_write("hahahaha");
        tag('view_end');
    }

    public function doTask() {
        // 这儿调用任务服务
        $task = D('Task', 'Service')->getTask('guestbook_submit');
        if ($task) {
            $result = $task->start("大家好aaaaaaaaaaaaaaaaa");
            dump($result);
        }
    }

    public function test() {
        import('@.ORG.Com.Auth');
        $auth = new Auth();
        $auth->setUserInfo($this->user_id, array(
            'id' => 2));
        dump($auth->check('LabelAdmin/Answers/index', $this->user_id));
        tag('view_end');
    }

    public function checkin_calender() {
        $year = I("post.year");
        $month = I("post.month");
        $days = $this->get_month_days($year, $month);
        $weak = date("w", mktime(0, 0, 0, $month, 1, $year));
        $html = "<table class='show' border='1' cellspancing='0' paddingspancing='10' width='500' height='300'><tr>";
        for ($i = 1; $i <= $weak; $i ++) {
            $html .= "<td>&nbsp;</td>";
        }
        for ($j = 1; $j <= $days; $j ++) {
            if ($i % 7 == 0) {
                $html .= "<td data-date='$year$month$j'>$j</td></tr><tr>";
            } else {
                $html .= "<td data-date='$year$month$j'>$j</td>";
            }
            $i ++;
        }
        $html .= "</tr></table>";
        echo $html;
    }

    public function get_month_days($year, $month) {
        $firstDay = date('Ym01', strtotime($year . $month));
        return date('d', strtotime("$firstDay +1 month -1 day"));
    }
}