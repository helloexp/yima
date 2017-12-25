<?php

/**
 *
 * @author Jeff Liu
 */
class DebugHelper implements ArrayAccess {

    private static $instance = null;

    private $debugInfo = null;

    /**
     * @var self|FirePHP
     */
    private $debugInstance = null;

    private $_isdebug = false;

    private function __construct() {
    }

    function offsetExists($index) {
        return isset($this->debugInfo[$index]);
    }

    function offsetGet($index) {
        return isset($this->debugInfo[$index]) ? $this->debugInfo[$index] : null;
    }

    function offsetShow($index = null) {
        if ($index && isset($this->debugInfo[$index])) {
            foreach ($this->debugInfo[$index] as $ed) {
                var_dump($ed);
                echo '<br/>';
            }
        } else {
            if ($this->debugInfo) {
                foreach ($this->debugInfo as $key => $debuginfo) {
                    echo '<b>' . $key . '</b> :<br/>';
                    foreach ($debuginfo as $ed) {
                        var_dump($ed);
                        echo '<br/>';
                    }
                }
            }
        }
    }

    function offsetCount($index = null) {
        if ($index && isset($this->debugInfo[$index])) {
            return count($this->debugInfo[$index]);
        } else {
            return count($this->debugInfo);
        }
    }

    function offsetSet($index, $newValue) {
        if ($index != '') {
            $this->debugInfo[$index][] = $newValue;
        } else {
            $this->debugInfo[] = $newValue;
        }
    }

    function offsetUnset($index) {
        unset($this->debugInfo[$index]);
    }

    /**
     *
     * @static
     *
     * @param string $instanceName
     *
     * @return DebugHelper|FirePHP
     */
    public static function getInstance($instanceName = 'firephp') {
        $id = md5($instanceName);
        if (isset(self::$instance[$id]) && self::$instance[$id] instanceof self) {
        } else {
            $instance = new DebugHelper();
            $bool     = false;
            if (C('PRODUCTION_FLAG') == 0 || I('get.__debug__', null) === 'show_debug') {
                $bool = true;
            }

            $instance->_isdebug = $bool;
            if ($instance->_isdebug) {
                if ($instanceName === 'firephp') {
                    import('@.Vendor.FirePHP');
                    $instance->debugInstance = FirePHP::getInstance(true);
                    $instance->debugInstance->setEnabled($bool);
                } else {
                    $instance->debugInstance = new $instanceName();
                }
            }
            self::$instance[$id] = $instance;
        }
        return self::$instance[$id];
    }

    /**
     *
     * @param array $param
     *
     * @return bool
     */
    public function debug2(
            $param = array( /*'info' => '', 'label' => '', 'level' => '', 'options' => ''*/)
    ) {
        $info       = isset($param['info']) ? $param['info'] : ''; // 这个可以为数组 也可以为字符串
        $label      = isset($param['label']) ? $param['label'] : '';
        $debugLevel = isset($param['level']) ? $param['level'] : ''; //
        $options    = isset($param['options']) ? $param['options'] : ''; //
        if (!$this->_isdebug) {
            return false;
        }
        $debugLevel = strtolower($debugLevel); // 默认应该是log
        if (!in_array($debugLevel, array(
                'log',
                'info',
                'error',
                'warn',
        ), true)
        ) {
            $debugLevel = 'log';
        }

        return $this->debugInstance->$debugLevel($info, $label, $options);
    }

    /**
     *
     * @author Jeff Liu
     *
     * @param mixed  $info
     * @param string $label
     * @param string $level
     * @param string $options
     *
     * @return bool
     */
    public function debug($info = '', $label = '', $level = '', $options = '') {
        if (!$this->_isdebug) {
            return false;
        }
        $debugLevel = strtolower($level); // 默认应该是log
        if (!in_array($debugLevel, array(
                'log',
                'info',
                'error',
                'warn',
        ), true)
        ) {
            $debugLevel = 'log';
        }

        return $this->debugInstance->$debugLevel($info, $label, $options);
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param mixed  $info
     * @param string $label
     * @param string $level
     * @param mixed  $options
     * @param string $debugFile
     *
     * @return bool
     */
    public function fileDebug(
            $info = '',
            $label = '',
            $level = '',
            $options = '',
            $debugFile = ''
    ) {
        if (!$this->_isdebug) {
            return false;
        }
        $msgFormat = '%s|%s|%s|%s|%s' . PHP_EOL;

        if (empty($debugFile)) {
            if (defined('IS_WIN') && IS_WIN) {
                $defaultFile = 'd:/fileDebug.log';
            } else {
                $defaultFile = '/tmp/fileDebug';
            }
            $debugFile = C('DEBUG_FILE', $defaultFile);
        } else {
            $parentDir = dirname($debugFile);
            if (!file_exists($parentDir)) {
                $mkdirResult = mkdir($parentDir, 777, true);
                if ($mkdirResult === false) { // 文件夹创建失败
                    trigger_error($parentDir . 'create failure!', E_USER_WARNING);
                }
            }
        }

        $msg = $this->formatMsg($info, $label, $level, $options, $msgFormat);
        error_log($msg, 3, $debugFile);

        return true;
    }

    /**
     * 格式化消息
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param mixed  $info
     * @param string $label
     * @param string $level
     * @param mixed  $options
     * @param string $msgFormat
     *
     * @return string
     */
    private function formatMsg(
            $info = '',
            $label = '',
            $level = '',
            $options = '',
            $msgFormat = ''
    ) {
        if (empty($msgFormat)) {
            $msgFormat = '%s|%s|%s|%s|%s' . PHP_EOL;
        }

        return sprintf($msgFormat, date('Y-m-d H:i:s'), $level, $label, var_export($info, 1), var_export($options, 1));
    }

    /**
     * todo
     *
     * @param mixed  $info
     * @param string $label
     * @param string $level
     * @param mixed  $options
     *
     * @return bool
     */
    public function socketDebug($info = '', $label = '', $level = '', $options = '') {
        if (!$this->_isdebug) {
            return false;
        }
        $msgFormat = '%s|%s|%s|%s|%s' . PHP_EOL;

        $msg = $this->formatMsg($info, $label, $level, $options, $msgFormat);

        // todo 将msg写入指定socket
        return true;
    }

    /**
     * Log object with label to firebug console
     *
     * @see FirePHP::LOG
     *
     * @param mixed  $object
     * @param string $label
     *
     * @return true
     * @throws Exception
     */
    public function log($object, $label = null, $options = array()) {
        return $this->debugInstance->log($object, $label, $options);
    }

    /**
     * Log object with label to firebug console
     *
     * @see FirePHP::INFO
     *
     * @param mixed  $object
     * @param string $label
     *
     * @return true
     * @throws Exception
     */
    public function info($object, $label = null, $options = array()) {
        return $this->debugInstance->info($object, $label, $options);
    }

    /**
     * Log object with label to firebug console
     *
     * @see FirePHP::WARN
     *
     * @param mixed  $object
     * @param string $label
     *
     * @return true
     * @throws Exception
     */
    public function warn($object, $label = null, $options = array()) {
        return $this->debugInstance->warn($object, $label, $options);
    }

    /**
     * Log object with label to firebug console
     *
     * @see FirePHP::ERROR
     *
     * @param mixed  $object
     * @param string $label
     *
     * @return true
     * @throws Exception
     */
    public function error($object, $label = null, $options = array()) {
        return $this->debugInstance->error($object, $label, $options);
    }

    /**
     * Dumps key and variable to firebug server panel
     *
     * @see FirePHP::DUMP
     *
     * @param string $key
     * @param mixed  $variable
     *
     * @return true
     * @throws Exception
     */
    public function dump($key, $variable, $options = array()) {
        return $this->debugInstance->dump($variable, $key, $options);
    }

    /**
     * Log a trace in the firebug console
     *
     * @see FirePHP::TRACE
     *
     * @param string $label
     *
     * @return true
     * @throws Exception
     */
    public function trace($label) {
        return $this->debugInstance->trace($label);
    }
}