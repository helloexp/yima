<?php
// ������JOB
class WfxJobAction extends BaseAction {

    public function _initialize() {
        C('LOG_PATH', LOG_PATH . 'WFX_'); // ���¶���Ŀ־Ŀ¼
    }

    /* ��ں��� */
    public function index() {
    }

    public function day_note_gen() {
        $postStr = file_get_contents('php://input');
        $this->log("wfx day_note_gen :" . $postStr);
        $this->log(
            'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER["SERVER_PORT"] .
                 $_SERVER["REQUEST_URI"]);
        $date = date("Ymd", strtotime("-1 day"));
        $wfx = D('Wfx', 'Service');
        echo $wfx->notify_gen($date);
    }

    public function month_deal() {
        $postStr = file_get_contents('php://input');
        $this->log("wfx month_deal :" . $postStr);
        $this->log(
            'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER["SERVER_PORT"] .
                 $_SERVER["REQUEST_URI"]);
        $date = date("Ymd", strtotime("-1 day"));
        $wfx = D('Wfx', 'Service');
        echo $wfx->month_get_bonus();
    }

    public function week_deal() {
        $postStr = file_get_contents('php://input');
        $this->log("wfx month_deal :" . $postStr);
        $this->log(
            'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER["SERVER_PORT"] .
                 $_SERVER["REQUEST_URI"]);
        $date = date("Ymd", strtotime("-1 day"));
        $wfx = D('Wfx', 'Service');
        echo $wfx->week_get_bonus();
    }

    public function short_url_gen() {
        $postStr = file_get_contents('php://input');
        $this->log("wfx month_deal :" . $postStr);
        $this->log(
            'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER["SERVER_PORT"] .
                 $_SERVER["REQUEST_URI"]);
        $date = date("Ymd", strtotime("-1 day"));
        $wfx = D('Wfx', 'Service');
        echo $wfx->short_url_gen();
    }
    
    // ��¼��־
    protected function log($msg, $level = Log::INFO) {
        // trace('Log.'.$level.':'.$msg);
        Log::write($msg, '[' . getmypid() . ']' . $level);
    }
}
