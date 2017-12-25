<?php

class FineCurl {

    public $socket;

    public $hostname = 'localhost';
    // Ĭ�ϵ�ַ
    public $port = '80';
    // �˿�
    public $path = '';

    public $error_str = null;

    public $error_code;

    /**
     * �������� 1 ���Ӵ��� 2 ���մ���
     */
    public $error_type;

    /**
     * ����ѡ�� $option URL ���͵�ַ TIMEOUT �ܳ�ʱ���� TIMEOUT_CONN ���ӳ�ʱ����
     * TIMEOUT_RECE ��ȡ��ʱ���� TIMEOUT_SEND д�볬ʱ���� TRYTIMES ���ӳ�ʱ���Դ��� KEEP
     * 0 �Ƕ�����,send�겻�ر� 1�ǳ����� send������ٴ�send HEADER_TYPE �������͡�POST
     * GET NULL,Ĭ����POST HEADER �����ļ�ͷ RECE_TYPE ��ȡ���� HTTP NULL ALL
     * RECE_SIZE ��ȡ�ֽ���
     */
    protected $option = array();

    /**
     * ��Ϣ��info TIME_CONNECT ����ʱ�� TIME_RECE ����ʱ�� TIME_SEND д��ʱ��
     * TRYTIMES ���ӳ��ԵĴ��� RESP_STR ��������
     */
    public $info = array();

    static $error_arr = array(
            7  => 'CURLE_COULDNT_CONNECT:timeout:Failed to connect() to host or proxy. ',
            28 => 'CURLE_OPERATION_TIMEDOUT:Operation timeout. The specified time-out period was reached according to the conditions.  ',
    );

    function curlHeaderCallback($curl, $header) {
        $this->response = $header;

        return;
    }

    /**
     * ��ʼ���캯��
     */
    function __construct($url = null) {
        // ��ʼ���������
        $this->option['HEADER_TYPE']  = 'POST';
        $this->option['URL']          = $url;
        $this->option['TIMEOUT_CONN'] = 1;
        $this->option['TRYTIMES']     = 3;
    }

    /**
     * ���ò���
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
         * $length = strlen($xml); //��ʽ��Ҫ���͵����� $header = ''; $header.=
         * 'POST '.$path.' HTTP/1.1'."\n"; //url path $header.= 'HOST:
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
        $option = &$this->option;
        // ��ʼ��socket
        if (!$this->socket) {
            $this->socket = curl_init();
            curl_setopt($this->socket, CURLOPT_URL, $option['URL']);
            curl_setopt($this->socket, CURLOPT_USERAGENT, 'finePHP');
            // ���� POST
            if ($option['HEADER_TYPE'] == 'POST') {
                curl_setopt($this->socket, CURLOPT_POST, 1);
            }
            // ���÷���ͷ
            if ($option['HEADER'] != '') {
                if (is_string($option['HEADER'])) {
                    $option['HEADER'] = explode("\r\n", $option['HEADER']);
                }
                curl_setopt($this->socket, CURLOPT_HTTPHEADER, $option['HEADER']);
            }
            // curl_setopt($this->socket,CURLOPT_WRITEFUNCTION,
            // array('FINE_Curl','curlHeaderCallback'));
            curl_setopt($this->socket, CURLOPT_RETURNTRANSFER, 1); // ��ֱ����������ص�����
            // ���� url
            $urlarr         = parse_url($option['URL']);
            $this->scheme   = strtolower($urlarr['scheme']);
            $this->hostname = gethostbyname($urlarr['host']);
            $this->port     = isset($urlarr['port']) ? $urlarr['port'] : '80';
            $this->path     = $urlarr['path'];
        }
        curl_setopt($this->socket, CURLOPT_POSTFIELDS, $msg);
        // ���ý��ճ�ʱ
        if ($option['TIMEOUT_RECE']) {
            curl_setopt($this->socket, CURLOPT_TIMEOUT, $option['TIMEOUT_RECE']);
        }
        // �������ӳ�ʱ
        if ($option['TIMEOUT_CONN']) {
            curl_setopt($this->socket, CURLOPT_CONNECTTIMEOUT, $option['TIMEOUT_CONN']);
        }
        if ($this->scheme == 'https') {
            curl_setopt($this->socket, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($this->socket, CURLOPT_SSL_VERIFYHOST, false);
        }
        // ���ӳ�ʱ����
        $_runtime = microtime();
        for ($i = 0; $i < $option['TRYTIMES']; $i++) {
            $this->response   = curl_exec($this->socket);
            $this->error_code = curl_errno($this->socket);
            $this->error_str  = curl_error($this->socket);
            $info             = curl_getinfo($this->socket);
            if ($this->error_code != 7 && $this->error_code != 28) {
                break;
            }
            sleep(3);
        }
        // ��������ʱ��,����
        $this->info['TIME_CONNECT'] = $this->_runtime($_runtime);
        $this->info['TRYTIMES']     = $i;
        // ���ص�header��Ϣ
        $this->info['RECE_HEADER'] = $info;
        // ���ش�����Ϣ
        $this->error_str = ($this->error_str == "" && $this->error_code) ? self::$error_arr[$this->error_code] : $this->error_str;
        $this->error_str = $this->error_code ? 'connect_error:[' . $this->error_code . '] ' . $this->error_str : '';
        curl_close($this->socket);

        return $this->response;
    }

    // ���ó�ʱ����
    function setTimeout($sec, $type = 'rece') {
        // ���ý��ճ�ʱ
        if ($type == 'rece') {
            $this->option['TIMEOUT_RECE'] = $sec;
        } // ���÷��ͳ�ʱ
        elseif ($type == 'send') {
            $this->option['TIMEOUT_SEND'] = $sec;
        } // �������ӳ�ʱ
        elseif ($type == 'conn') {
            $this->option['TIMEOUT_CONN'] = $sec;
        }  // ȫ����ʱ
        else {
            $this->option['TIMEOUT_RECE'] = $sec;
            $this->option['TIMEOUT_SEND'] = $sec;
            $this->option['TIMEOUT_CONN'] = $sec;
        }
    }

    function close() {
        $this->error_str  = null;
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
     * ��ȡ��Ϣ
     */
    function getinfo($type) {
        if ($type == null) {
            return $this->info;
        }

        return $this->info[$type];
    }

    // �ַ�����ȡ
    function cutstr($filestr, $start, $end) {
        $returnval = '';
        // ȡ��ʼλ��
        if ($start == null) {
            $spos = 0;
        } else {
            $spos = strpos($filestr, $start);
        }
        // ȡ����λ��
        if ($end == null) {
            $epos = strlen($filestr);
        } else {
            $epos = strpos($filestr, $end);
        }

        // ȡ����
        if ($epos > $spos) {
            $returnval = substr($filestr, $spos + strlen($start), $epos - $spos - strlen($start));
        }

        return $returnval;
    }

    /**
     * ��ȡ����ʱ��
     */
    function _runtime($t1) {
        $t  = explode(' ', $t1);
        $t1 = ((float)$t[0] + (float)$t[1]);
        $t  = explode(' ', microtime());
        $t2 = ((float)$t[0] + (float)$t[1]);

        return sprintf('%.1f', ($t2 - $t1) * 1000);
    }
}

?>