<?php
/**
 *@@@ edit by dongdong 2015/4/7 10:27
 */

$postStr = file_get_contents('php://input');
/*error_log('index.php $postStr' . var_export($postStr,1) . PHP_EOL,3,'/tmp/elog.log');
error_log('index.php $_GET' . var_export($_GET,1) . PHP_EOL,3,'/tmp/elog.log');
error_log('index.php $_POST' . var_export($_POST,1) . PHP_EOL,3,'/tmp/elog.log');
error_log('index.php $_REQUEST' . var_export($_REQUEST,1) . PHP_EOL,3,'/tmp/elog.log');*/

if (isset($_GET['amp;amp;m']) && isset($_GET['amp;amp;a'])) {
    $requestUri = $_SERVER['REQUEST_URI'];
    header('Location:'.html_entity_decode(html_entity_decode($requestUri)));
    exit;
} else if (isset($_GET['amp;m']) && isset($_GET['amp;a'])) {
    $requestUri = $_SERVER['REQUEST_URI'];
    header('Location:'.html_entity_decode($requestUri));
    exit;
}

if (!headers_sent()) { //for N/E队列 start
    if (isset($_GET['g']) && $_GET['g'] == 'Label') {
        $id = isset($_GET['id']) ? $_GET['id'] : 0;
        if (empty($id)) {
            $id = isset($_POST['id']) ? $_POST['id'] : 0;
        }
        if ($id) {
            setcookie('queue_id', $id, time() + 300);
        }
    } else {
        if (isset($_COOKIE['queue_id']) && $_COOKIE['queue_id']) {
            setcookie('queue_id', 0);
        }
    }
}//for N/E队列 end

error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set('memory_limit', '132M');
ini_set('magic_quotes_sybase', 0);
ini_set('magic_quotes_runtime', 0);
//set_magic_quotes_runtime(0);

header("Content-type:text/html;charset=utf-8");

if (strstr($_SERVER['HTTP_HOST'], 'wangcaio2o.com') !== false) {
    session_set_cookie_params(null, '/', '.wangcaio2o.com', null, true);
}

//商旅文演示用----不上生产
if($_SERVER['HTTP_X_FORWARDED_FOR'] == '121.40.155.155'){
    session_set_cookie_params(null, null, null, null, true);
}
//session_set_cookie_params (null,'/','.wangcaio2o.com',NULL,TRUE);
define('APP_NAME', 'Home');
define('APP_PATH', './Home/');
define('INDEX_PATH', __DIR__);
//define('RUNTIME_PATH', 'F:/SDA/wangcai/');
//开启调试模式
define('APP_DEBUG', true);
// 加载框架入口文件
require("./Lib/ThinkPHP/ThinkPHP.php");