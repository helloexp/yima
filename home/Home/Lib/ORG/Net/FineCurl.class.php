<?php

class FineCurl {

    public $socket;

    public $hostname = 'localhost';
    // 默认地址
    public $port = '80';
    // 端口
    public $path = '';

    public $error_str = null;

    public $error_code;

    /**
     * 错误类型 1 连接错误 2 接收错误
     */
    public $error_type;

    /**
     * 设置选项 $option URL 发送地址 TIMEOUT 总超时秒数 TIMEOUT_CONN 连接超时秒数 TIMEOUT_RECE
     * 读取超时秒数 TIMEOUT_SEND 写入超时秒数 TRYTIMES 连接超时重试次数 KEEP 0 是短连接,send完不关闭 1是长连接
     * send完可以再次send HEADER_TYPE 发送类型　POST GET NULL,默认是POST HEADER 设置文件头
     * RECE_TYPE 读取类型 HTTP NULL ALL RECE_SIZE 读取字节数
     */
    protected $option = array();

    /**
     * 信息　info TIME_CONNECT 连接时间 TIME_RECE 接收时间 TIME_SEND 写入时间 TRYTIMES 连接尝试的次数
     * RESP_STR 返回内容
     */
    public $info = array();

    static $error_arr = array(
        7 => 'CURLE_COULDNT_CONNECT:timeout:Failed to connect() to host or proxy. ', 
        28 => 'CURLE_OPERATION_TIMEDOUT:Operation timeout. The specified time-out period was reached according to the conditions.  ');

    function curlHeaderCallback($curl, $header) {
        $this->response = $header;
        return;
    }

    /**
     * 开始构造函数
     */
    function __construct($url = null) {
        // 初始化各项参数
        $this->option['HEADER_TYPE'] = 'POST';
        $this->option['URL'] = $url;
        $this->option['TIMEOUT_CONN'] = 1;
        $this->option['TRYTIMES'] = 3;
        $this->option['HEADER'] = '';
        $this->option['TIMEOUT_RECE'] = 30;
    }

    /**
     * 设置参数
     */
    function setopt($opt = null, $val = null) {
        if (is_array($opt)) {
            foreach ($opt as $key => $valstr) {
                $this->option[$key] = $valstr;
            }
        } else {
            $this->option[$opt] = $val;
        }
    }

    function makemsg($xml, $path = '') {
        /*
         * $length = strlen($xml); //格式化要发送的内容 $header = ''; $header.= 'POST
         * '.$path.' HTTP/1.1'."\n"; //url path $header.= 'HOST:
         * '.$this->hostname.':'.$this->port."\r\n"; $header.= 'Content-Type:
         * application/x-www-form-urlencoded;charset=gb2312'."\r\n"; $header.=
         * 'Content-Length:'.$length."\r\n\r\n"; return $header.$xml;
         */
        return $xml;
    }

    function sendmsg($msg = null) {
        return $this->send($msg);
    }

    function send($msg = null) {
        $option = & $this->option;
        // 初始化socket
        if (! $this->socket) {
            $this->socket = curl_init();
            curl_setopt($this->socket, CURLOPT_URL, $option['URL']);
            curl_setopt($this->socket, CURLOPT_USERAGENT, 'finePHP');
            // 设置 POST
            if ($option['HEADER_TYPE'] == 'POST') {
                curl_setopt($this->socket, CURLOPT_POST, 1);
            }
            // 设置发送头
            if ($option['HEADER'] != '') {
                if (is_string($option['HEADER']))
                    $option['HEADER'] = explode("\r\n", $option['HEADER']);
                curl_setopt($this->socket, CURLOPT_HTTPHEADER, 
                    $option['HEADER']);
            }
            // curl_setopt($this->socket,CURLOPT_WRITEFUNCTION,
            // array('FINE_Curl','curlHeaderCallback'));
            curl_setopt($this->socket, CURLOPT_RETURNTRANSFER, 1); // 不直接输出，返回到变量
                                                                   // 解析 url
            $urlarr = parse_url($option['URL']);
            $this->scheme =  isset($urlarr['scheme']) ? strtolower($urlarr['scheme']) : '';
            $this->hostname = gethostbyname(isset($urlarr['host']) ? strtolower($urlarr['host']) : '');
            $this->port = isset($urlarr['port']) ? $urlarr['port'] : '80';
            $this->path = isset($urlarr['path']) ? strtolower($urlarr['path']) : '';
        }
        if ($msg) {
            curl_setopt($this->socket, CURLOPT_POSTFIELDS, $msg);
        }
        // 设置接收超时
        if ($option['TIMEOUT_RECE']) {
            curl_setopt($this->socket, CURLOPT_TIMEOUT, $option['TIMEOUT_RECE']);
        }
        // 设置连接超时
        if ($option['TIMEOUT_CONN']) {
            curl_setopt($this->socket, CURLOPT_CONNECTTIMEOUT, 
                $option['TIMEOUT_CONN']);
        }
        if ($this->scheme == 'https') {
            curl_setopt($this->socket, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($this->socket, CURLOPT_SSL_VERIFYHOST, FALSE);
        }
        // 连接超时重试
        $_runtime = microtime();
        for ($i = 0; $i < $option['TRYTIMES']; $i ++) {
            $this->response = curl_exec($this->socket);
            //log_write('查看微信响应结果0:'.var_dump($this->socket,true));
            //log_write('查看微信响应结果1:'.var_dump($this->response,true));

            $this->error_code = curl_errno($this->socket);
            //log_write('查看微信响应结果2:'.var_dump($this->error_code,true));

            $this->error_str = curl_error($this->socket);
            //log_write('查看微信响应结果3:'.var_dump($this->error_str,true));

            $info = curl_getinfo($this->socket);
            if ($this->error_code != 7 && $this->error_code != 28) {
                break;
            }
            sleep(3);
        }
        // 计算连接时间,次数
        $this->info['TIME_CONNECT'] = $this->_runtime($_runtime);
        $this->info['TRYTIMES'] = $i;
        // 返回的header信息
        $this->info['RECE_HEADER'] = $info;
        // 返回错误信息
        $this->error_str = ($this->error_str == "" && $this->error_code) ? self::$error_arr[$this->error_code] : $this->error_str;
        $this->error_str = $this->error_code ? 'connect_error:[' .
             $this->error_code . '] ' . $this->error_str : '';
        curl_close($this->socket);
        return $this->response;
    }
    // 设置超时秒数
    function setTimeout($sec, $type = 'rece') {
        // 设置接收超时
        if ($type == 'rece') {
            $this->option['TIMEOUT_RECE'] = $sec;
        } // 设置发送超时
elseif ($type == 'send') {
            $this->option['TIMEOUT_SEND'] = $sec;
        } // 设置连接超时
elseif ($type == 'conn') {
            $this->option['TIMEOUT_CONN'] = $sec;
        }  // 全部超时
else {
            $this->option['TIMEOUT_RECE'] = $sec;
            $this->option['TIMEOUT_SEND'] = $sec;
            $this->option['TIMEOUT_CONN'] = $sec;
        }
    }

    function close() {
        $this->error_str = null;
        $this->error_code = null;
        $this->error_type = null;
        return true;
    }

    function error() {
        return $this->error_str;
    }

    function error_type() {
        return $this->error_type;
    }

    /**
     * 获取信息
     */
    function getinfo($type) {
        if ($type == null) {
            return $this->info;
        }
        return $this->info[$type];
    }
    // 字符串截取
    function cutstr($filestr, $start, $end) {
        $returnval = '';
        // 取开始位置
        if ($start == null) {
            $spos = 0;
        } else {
            $spos = strpos($filestr, $start);
        }
        // 取结束位置
        if ($end == null) {
            $epos = strlen($filestr);
        } else {
            $epos = strpos($filestr, $end);
        }
        
        // 取内容
        if ($epos > $spos) {
            $returnval = substr($filestr, $spos + strlen($start), 
                $epos - $spos - strlen($start));
        }
        return $returnval;
    }

    /**
     * 获取运行时间
     */
    function _runtime($t1) {
        $t = explode(' ', $t1);
        $t1 = ((float) $t[0] + (float) $t[1]);
        $t = explode(' ', microtime());
        $t2 = ((float) $t[0] + (float) $t[1]);
        return sprintf('%.1f', ($t2 - $t1) * 1000);
    }
}

?>