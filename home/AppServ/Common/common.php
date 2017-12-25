<?php
// 共公函数库
/* http请求 */
function httpPost($url, $data = null, &$error = null, $opt = array())
{
    $opt = array_merge(array(
        'TIMEOUT' => 30
    ), $opt);
    // 创建post请求参数
    import('@.ORG.Net.FineCurl') or die('导入包失败');
    $socket = new FineCurl();
    $socket->setopt('URL', $url);
    $socket->setopt('TIMEOUT', $opt['TIMEOUT']);
    if (is_array($data)) {
        $data = http_build_query($data);
    }
	log_write('请求：'.$url.'参数：'.$data);
    $result = $socket->send($data);
    $error = $socket->error();
    // 记录日志
    if ($error) {
        log_write($error);
    }
    log_write('响应：' . (function_exists('mb_convert_encoding') ? mb_convert_encoding($result, 'utf-8', 'utf-8,gbk') : $result));
    return $result;
}

// 获取正常的打印文本
function get_print_text($org_print_text)
{
    if ($org_print_text == '') {
        return null;
    } else {
        $print_text_arr = explode('&lt;div style=&quot;display:none;&quot;&gt;', $org_print_text);
        return $print_text_arr[0];
    }
}

// 动态转成utf-8
function utf8Str(&$str)
{
    $str = function_exists('mb_convert_encoding') ? mb_convert_encoding($str, 'utf-8', 'utf-8,gbk') : $str;
    return $str;
}

function toiss_reqid()
{
    $data = M()->query("SELECT _nextval('send_toiss_seq') as reqid FROM DUAL");
    if (! $data) {
        log_write('send_toiss_seq fail!');
        $req = rand(1, 999999);
    } else {
        $req = $data[0]['reqid'];
    }
    return date('YmdHis') . str_pad($req, 6, '0', STR_PAD_LEFT);
}

function award_reqid()
{
    $data = M()->query("SELECT _nextval('award_send_seq') as reqid FROM DUAL");
    if (! $data) {
        log_write('award_send_seq fail!');
        $req = rand(1, 999999);
    } else {
        $req = $data[0]['reqid'];
    }
    return date('YmdHis') . str_pad($req, 6, '0', STR_PAD_LEFT);
}

function is_production()
{
    return C('PRODUCTION_FLAG') == 1 ? true : false;
}

/**
 * 格式化日期时间
 *
 *
 * @param string $str
 *            日期时间
 * @param string $format
 *            日期时间格式
 * @return string
 */
function dateformat($str, $format = 'Y-m-d H:i:s')
{
    $str = trim($str);
    if (! $str)
        return false;
    $date = strtotime($str);
    if (! $date)
        return $str;
        
        // 微信消息管理自定格式，
    if ($format == 'defined1') {
        // 今天
        $day = date('Ymd', $date);
        $time = date('H:i', $date);
        if ($day == date('Ymd', strtotime("-1 day"))) {
            $time = '昨天&nbsp;&nbsp;&nbsp;' . $time;
        } else if ($day != date('Ymd')) {
            $week = array(
                '星期一',
                '星期二',
                '星期三',
                '星期四',
                '星期五',
                '星期六',
                '星期日'
            );
            $time = $week[date('w', $date)] . $time;
        }
        $date = $time;
    } else {
        $date = date($format, $date);
        if (strpos($date, '1970') === 1)
            return $str;
    }
    return $date;
}

/**
 * 方法：to_email($content)
 * 功能:发邮件
 * 参数：数组
 * 返回值：成功 2 失败 1
 *
 * @param $content
 *
 * @return string
 * @throws phpmailerException
 */
function to_email($content)
{
    Vendor('PHPMailer.class', '', '.phpmailer.php'); // 导入第三方邮件库
    $mail = new PHPMailer();
    $mail->isSMTP(); // Set mailer to use SMTP
    $mail->Host = 'smtp.263xmail.com'; // Specify main and backup SMTP servers
    $mail->SMTPAuth = true; // Enable SMTP authentication
    $mail->Username = 'noreply@imageco.com.cn'; // SMTP username
    $mail->Password = 'nlnoreply'; // SMTP password
                                   // $mail->SMTPSecure = 'tls'; // Enable TLS encryption, `ssl` also accepted
    $mail->CharSet = 'utf-8';
    $mail->From = 'noreply@imageco.com.cn'; // 发件人
    $mail->FromName = 'noreply'; // 发件人别名
    $mail->Port = 25; // 端口
    $mail->addAddress($content['petname']);
    $mail->addCC($content['CC']); // 抄送人 可多次调用添加多个抄送人
                                  // $mail->WordWrap = 50; // Set word wrap to 50 characters
    $mail->isHTML(true); // 以HTML发送
    $mail->Subject = $content['test_title']; // 邮件标题
    $mail->Body = $content['text_content']; // 邮件内容
                                                // 处理附件 如果add_file为array则表示多附件
    if (is_array($content['add_file'])) {
        foreach ($content['add_file'] as $v)
            $mail->addAttachment($v); // 附件
    } else
        $mail->addAttachment($content['add_file']); // 附件
    
    if (! $mail->send()) {
        return '1'; // 失败
    } else {
        return '2'; // 成功
    }
}

/**
 * 翼蕙宝短信接口
 * 
 * @param number $number
 *            目标号码
 * @param string $text
 *            短信内容
 * @return bool 成功与否
 */
function Yhb_sms($number, $text)
{
    if (empty($number) || empty($text)) {
        $msg = "翼蕙宝短信接口：\n     发送至：" . $number . "\n内容：" . $text . "\n发送状态：发送参数错误";
        log_write($msg);
        return false;
    }
    // 获取配置
    $conf = C('Yhb.yhb_sms');
    $username = $conf["username"];
    $password = $conf["password"];
    $subid = $conf["subid"];
    $msgtype = $conf['msgtype'] or 1;
    $url = $conf['url'];
    
    if (empty($username) || empty($password) || empty($url)) {
        $msg = "翼蕙宝短信接口：\n     发送至：" . $number . "\n内容：" . $text . "\n" . print_r($conf, true) . "\n发送状态：配置参数错误";
        log_write($msg);
        return false;
    }
    // 数据处理
    $data = array(
        "username" => $username,
        'password' => $password,
        'to' => $number,
        'text' => urlencode(iconv("UTF-8", "gbk", $text)),
        'subid' => $subid,
        'msgtype' => $msgtype
    );
    foreach ($data as $key => $val) {
        $url .= $key . '=' . $val . '&';
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $out = curl_exec($ch);
    curl_close($ch);
    $msg = "翼蕙宝短信接口：\n     发送至：" . $number . "\n内容：" . $text . "\n发送状态：" . $out;
    log_write($msg);
    if ($out !== false and $out == '0') {
        return true;
    } else {
        $content = array(
            'petname' => "yangch@imageco.com.cn",
            'test_title' => "翼蕙宝短信接口",
            'text_content' => "翼蕙宝短信发送出错" . $msg,
            'CC' => "翼蕙宝"
        );
        to_email($content);
        return false;
    }
}

/**
 * 记录文本日志
 * 
 * @author tr
 *        
 * @param string $content
 *            内容
 * @param string $level
 *            级别
 * @param string $log_name
 *            日志文件名（不包括日期）
 *            
 */
function log_write($content, $level = '', $log_name = '')
{
    static $__APP_LOG_PID__; // 进程号
    $GROUP_NAME= 'GROUP_NAME';
    $MODULE_NAME = 'MODULE_NAME';
    $ACTION_NAME = 'ACTION_NAME';
    $GROUP_NAME = (defined('GROUP_NAME') ? GROUP_NAME : $GROUP_NAME);
    $MODULE_NAME = (defined('MODULE_NAME') ? MODULE_NAME : $MODULE_NAME);
    $ACTION_NAME = (defined('ACTION_NAME') ? ACTION_NAME : $ACTION_NAME);
    $first = '[PAGE:' . $GROUP_NAME . DIRECTORY_SEPARATOR . $MODULE_NAME . DIRECTORY_SEPARATOR . $ACTION_NAME . ']';
    if (! $__APP_LOG_PID__) {
        $__APP_LOG_PID__ = '[PID:' . mt_rand(1000, 9999) . ']';
        $first .= '[IP:' . get_client_ip() . '][GET:' . $_SERVER['REQUEST_URI'] . '][ACTION:' . $ACTION_NAME . ']';
    }
    if (! C('SERVER_LOG_FILE_PATH')) {
        C('SERVER_LOG_FILE_PAT', C('CUSTOM_LOG_PATH'));
    }
    if ($log_name == '') {
        $log_name = $MODULE_NAME;
    }
    $destination = 'WC_' . $GROUP_NAME . '_' . $log_name;
    $hostName = function_exists('gethostname') ? gethostname() : php_uname('n');
    Log::write($content, '[' . $hostName . ']' . $__APP_LOG_PID__ . $first . $level, '', $destination);
}

function file_debug($params, $label = '', $fileName = '', $filePath = '')
{
    if (empty($filePath)) {
        if (defined('PHP_OS') && strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            $filePath = '/tmp/';
        } else {
            $filePath = 'd:/';
        }
    }

    if (empty($fileName)) {
        if (empty($label)) {
            $fileName = 'file_debug.log';
        } else {
            $fileName = $label;
        }
    }

    $file = $filePath . $fileName;
    if (!is_scalar($params)) {
        $params = var_export($params,1);
    }
    error_log($label . ':' . $params . PHP_EOL, 3, $file);
}
