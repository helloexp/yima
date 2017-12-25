<?php
// 空动作
class EmptyAction extends Action {

    public function index() {
        $this->_empty();
    }

    public function _empty() {
        try {
            $content = $this->fetch();
            echo $content;
        } catch (Exception $e) {
            header("HTTP/1.0 404 Not Found");
            $this->display('../Public/404');
        }
    }

    public function stime() {
        echo sprintf("%.0f", time() * 1000);
    }
}