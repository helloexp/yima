<?php

// ���������ͬ�Ľ�������
class DispatchBehavior extends Behavior {
    // ��Ϊ��չ��ִ����ڱ�����run
    public function run(&$params) {
        C('URL_MODEL', 0);
        $_GET['m'] = $_GET['a'];
        $_GET['a'] = 'run';
        unset($_POST['a']);
        unset($_POST['m']);
        // define('ACTION_NAME','index');
        // define('MODULE_NAME',$_GET['a']);
    }
}