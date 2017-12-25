<?php
define('GROUP_NAME', 'TEST');
define('MODULE_NAME', 'Test');
define('ACTION_NAME', 'TEST_log');
define('SERVER_LOG_FILE_PATH', '/www/php_log/');
$log    = array();
$format = '[ c ]';
$i      = 1;
for ($i = 1; $i <= 100; $i++) {
    log_write($i);
}
function log_write($content, $level = '', $log_name = '')
{
    static $__APP_LOG_PID__; //进程号
    $first = '[PAGE:' . GROUP_NAME . DIRECTORY_SEPARATOR . MODULE_NAME . DIRECTORY_SEPARATOR . ACTION_NAME . ']';
    if (!$__APP_LOG_PID__) {
        $__APP_LOG_PID__ = '[PID:' . getmypid() . ']';
        $first .= '[IP:][GET:' . $_SERVER['REQUEST_URI'] . '][ACTION:' . ACTION_NAME . ']';
    }
    if (!SERVER_LOG_FILE_PATH) {
        C('SERVER_LOG_FILE_PAT', C('CUSTOM_LOG_PATH'));
    }
    if ($log_name == '') {
        $log_name = MODULE_NAME;
    }
    $destination = 'WC_' . GROUP_NAME . '_' . $log_name;
    //        $destination = 'TEST_log';
    $hostName = function_exists('gethostname') ? gethostname() : php_uname('n');
    write($content, '[' . $hostName . ']' . $__APP_LOG_PID__ . $first . $level, '', $destination);
}

function write($message, $level = self::ERR, $type = '', $destination = '', $extra = '')
{
    $now = date('YmdHis');
    //$type = $type?$type:C('LOG_TYPE');
    //启用syslog
    //        define('LOG_PID', '1');
    //        define('LOG_LOCAL5', '168');
    openlog($destination, 1, 168);
    syslog(LOG_DEBUG, $now . $level . "Messagge: $message");
    closelog();
}


