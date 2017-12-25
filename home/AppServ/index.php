<?
error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set('memory_limit','32M');
ini_set('magic_quotes_sybase',0);
set_magic_quotes_runtime(0);
define('_APP_PID_','PID:'.time().mt_rand(10,99));
define('APP_NAME', 'AppServ');
define('APP_PATH', './');
define('RUNTIME_PATH','/SDA/'.APP_NAME.'_runtime/');
//开启调试模式
define('APP_DEBUG', true);
// 加载框架入口文件
//require( "../Lib/ThinkPHP/ThinkPHP.php");
require( "../Lib/ThinkPHP/ThinkPHP.php");