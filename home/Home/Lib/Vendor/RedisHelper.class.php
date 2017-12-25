<?php

/**
 * Redis操作助手类 这个类还需要加强, 很多功能没有完成
 *
 * @author  Jeff Liu
 * @version 0.1
 */
class RedisHelper
{

    const REDIS_EXPIRE_TIME = -1;

    private static $expire = 0;

    /**
     *
     * @var array
     */
    private static $instance = array();

    private $decodedPrefix = 'RH_DECODED:';
    private $decodedPrefixLen;

    /**
     *
     * @param int $index
     *
     * @return RedisHelper|Redis
     */
    public static function getInstance($index = 0)
    {
        if (!isset(self::$instance[$index])) {
            self::$instance[$index] = new self($index);
        }

        return self::$instance[$index];
    }

    /**
     * @param $expire
     */
    public static function setExpire($expire)
    {
        self::$expire = $expire;
    }

    public static function getExpire()
    {
        return self::$expire;
    }

    /**
     * todo 还没有实现
     *
     * @param number $uid
     *
     * @return RedisHelper
     */
    public static function instanceById($uid)
    {
    }

    /**
     *
     * @var Redis
     */
    private $handle = null;

    /**
     *
     * @var array
     */
    private $config;

    /**
     *
     * @var string
     */
    private $prefix;

    /**
     * RedisHelper constructor.
     *
     * @param $index
     */
    private function __construct($index)
    {
        $config = C('REDIS');
        if (isset($config['host'])) {
            $config[0] = $config;
        }
        $prefix = C('REDIS_PREFIX');
        if (empty($prefix)) {
            $prefix = 'imageco:';
        }
        $this->config           = $config[$index];
        $this->prefix           = $prefix;
        $this->decodedPrefixLen = strlen($this->decodedPrefix);
    }

    /**
     * 连接redis 服务器
     *
     * @return bool
     */
    private function init()
    {
        return true;
        // if (null === $this->handle) {
        //     $this->handle = new Redis();

        //     if (isset($this->config['pconnect']) && $this->config['pconnect']) {
        //         $flag = $this->handle->pconnect($this->config['host'], $this->config['port']);
        //     } else {
        //         $flag = $this->handle->connect($this->config['host'], $this->config['port']);
        //     }

        //     return $flag;
        // } else {
        //     return true;
        // }
    }

    /**
     * @param $key
     * @param $hashKey
     * @param $value
     *
     * @return mixed|null
     */
    public function hIncrBy($key, $hashKey, $value)
    {
        $data = $this->__call('hIncrBy', [$key, $hashKey, $value]);
        return $data;
    }

    /**
     *
     * @param $key
     *
     * @return mixed|null
     */
    public function hGetAll($key)
    {
        $datas = $this->__call('hgetall', [$key]);
        if ($datas) {
            foreach ($datas as &$data) {
                $data = $this->tryDecodeData($data);
            }
        }
        return $datas;
    }

    /**
     *
     * @param $key
     * @param $field
     *
     * @return mixed|null
     */
    public function hdel($key, $field)
    {
        return $this->__call('hdel', [$key, $field]);
    }

    /**
     *
     * @param $key
     *
     * @return mixed|null
     */
    public function delete($key)
    {
        return $this->__call('delete', [$key]);
    }

    /**
     * @param $key
     * @param $hashKeys
     *
     * @return mixed|null
     */
    public function hMGet($key, $hashKeys)
    {
        $reData = $this->__call('hmget', [$key, $hashKeys]);
        foreach ($reData as &$data) {
            $data = $this->tryDecodeData($data);
        }
        return $reData;
    }

    /**
     * @param $key
     * @param $datas
     *
     * @return mixed|null
     */
    public function hMset($key, $datas)
    {
        foreach ($datas as &$data) {
            $data = $this->tryEncodeData($data);
        }
        $reData = $this->__call('hmset', [$key, $datas]);
//        $this->__call('expire', [$key, self::REDIS_EXPIRE_TIME]);
        return $reData;
    }

    /**
     * @param $key
     * @param $field
     * @param $data
     *
     * @return mixed|null
     */
    public function hSet($key, $field, $data)
    {
        $data   = $this->tryEncodeData($data);
        $reData = $this->__call('hset', [$key, $field, $data]);
//        $this->__call('expire', [$key, self::REDIS_EXPIRE_TIME]);
        return $reData;
    }

    /**
     *
     * @param $key
     * @param $field
     *
     * @return mixed|null
     */
    public function hGet($key, $field)
    {
        $reData = $this->__call('hget', [$key, $field]);
        $reData = $this->tryDecodeData($reData);
        return $reData;
    }

    public function get($key)
    {
        $reData = $this->__call('get', [$key]);
        $reData = $this->tryDecodeData($reData);
        return $reData;
    }

    public function set($key, $value)
    {
        $reData = $this->tryEncodeData($value);
        $reData = $this->__call('set', [$key, $reData]);

        return $reData;
    }

    /**
     * @param $data
     *
     * @return bool
     */
    private function needEncode($data)
    {
        return !is_scalar($data);
    }

    /**
     * @param $data
     *
     * @return bool
     */
    private function needDecode($data)
    {
        return strpos($data, $this->decodedPrefix) === 0;
    }

    /**
     * @param $data
     *
     * @return mixed|string
     */
    private function tryDecodeData($data)
    {
        if ($this->needDecode($data)) {
            $data = substr($data, $this->decodedPrefixLen);
            $data = json_decode($data, true);
        }
        return $data;
    }

    /**
     * @param $data
     *
     * @return string
     */
    private function tryEncodeData($data)
    {
        if ($this->needEncode($data)) {
            $data = $this->decodedPrefix . json_encode($data);
        }
        return $data;
    }

    /**
     * @param $key
     *
     * @return mixed|null
     */
    public function hGets($key)
    {
        return $this->hGetAll($key);
    }

    /**
     * @param            $Output
     * @param            $ZSetKeys
     * @param array|null $Weights
     * @param string     $aggregateFunction
     *
     * @return mixed|null
     */
    public function zUnion($Output, $ZSetKeys, array $Weights = null, $aggregateFunction = 'SUM')
    {
        $datas = $this->__call('zunion', [$Output, $ZSetKeys, $Weights, $aggregateFunction]);
        return $datas;
    }

    /**
     * @param       $name
     * @param array $param
     *
     * @return mixed|null
     */
    public function __call($name, $param = [])
    {
        $flag = $this->init();
        if (!in_array($name, ['script', 'evalSha', 'evaluate'])) {
            $param[0] = $this->prefix . $param[0];
        }
        if ($name === 'evalSha' || $name === 'evaluate') {
            array_unshift($param[1], $this->prefix);
            $param[2] += 1;
        }
        if ($name == 'zunion') {
            $keys = isset($param[1]) ? $param[1] : array();
            if (is_array($keys)) {
                foreach ($keys as $index => $key) {
                    $keys[$index] = $this->prefix . $key;
                }
                $param[1] = $keys;
            }
        }

        if ($flag) {
            $ret = call_user_func_array([&$this->handle, $name], $param);
            return $ret;
        } else {
            return null;
        }
    }

    /**
     * @param $key
     * @param $value
     *
     * @return mixed|null
     */
    public function setnx($key, $value)
    {
        $datas = $this->__call('setnx', [$key, $value]);

        return $datas;
    }

    /**
     * @param $key
     * @param $ttl
     *
     * @return mixed|null
     */
    public function expire($key, $ttl)
    {
        $datas = $this->__call('expire', [$key, $ttl]);
        return $datas;
    }

    /**
     * @param $key
     *
     * @return mixed|null
     */
    public function ttl($key)
    {
        $datas = $this->__call('ttl', [$key]);
        return $datas;
    }

    /**
     * @param $key
     *
     * @return mixed|null
     */
    public function watch($key)
    {
        $datas = $this->__call('watch', [$key]);
        return $datas;
    }

    /**
     * @return mixed|null
     */
    public function unwatch()
    {
        $datas = $this->__call('unwatch', []);
        return $datas;
    }

    /**
     * @return mixed|null
     */
    public function multi()
    {
        $datas = $this->__call('multi', []);
        return $datas;
    }

    /**
     * @return mixed|null
     */
    public function exec()
    {
        $datas = $this->__call('exec', []);
        return $datas;
    }

    /**
     * @param $key
     *
     * @return mixed|null
     */
    public function incr($key)
    {
        $datas = $this->__call('incr', [$key]);
        return $datas;
    }

    /**
     * @param $key
     * @param $value
     *
     * @return mixed|null
     */
    public function incrBy($key, $value)
    {
        $datas = $this->__call('incrBy', [$key, $value]);
        return $datas;
    }

    /**
     * @param $key
     * @param $value
     *
     * @return mixed|null
     */
    public function incrByFloat($key, $value)
    {
        $datas = $this->__call('incrByFloat', [$key, $value]);
        return $datas;
    }

    /**
     * @param $key
     *
     * @return mixed|null
     */
    public function decr($key)
    {
        $datas = $this->__call('decr', [$key]);
        return $datas;
    }

    /**
     * @param $key
     * @param $value
     *
     * @return mixed|null
     */
    public function decrBy($key, $value)
    {
        $datas = $this->__call('decrBy', [$key, $value]);
        return $datas;
    }

    /**
     * @return mixed|null
     */
    public function getLastError()
    {
        return $this->__call('getLastError');
    }

    /**
     * @param        $command
     * @param string $script
     *
     * @return mixed|null
     */
    public function script($command, $script = '')
    {
        $datas = $this->__call('script', [$command, $script]);
        return $datas;
    }

    /**
     * @param       $scriptSha
     * @param array $args
     * @param int   $numKeys
     *
     * @return mixed|null
     */
    public function evalSha($scriptSha, $args = array(), $numKeys = 0)
    {
        $datas = $this->__call('evalSha', [$scriptSha, $args, $numKeys]);
        return $datas;
    }

    /**
     * @param       $script
     * @param array $args
     * @param int   $numKeys
     *
     * @return mixed|null
     */
    public function evaluate($script, $args = array(), $numKeys = 0)
    {
        $datas = $this->__call('evaluate', [$script, $args, $numKeys]);
        return $datas;
    }

    /**
     * 构建流水号
     * @author Jeff.Liu<liuwy@imageco.com.cn>
     *
     * @param     $name
     *
     * @param int $start
     *
     * @return int|mixed|null|string
     */
    public function genreateSerialNumber($name, $start = 100000000000)
    {
        $r   = null;
        $key = 'serialno';
        $rs  = $this->multi();
        if (is_null($rs)) {
            return $rs;
        }

        $origin = $this->hGet($key, $name);
        $r      = sprintf('%.0f', $origin);
        if ($origin == 0) {
            $r = $start;
        } else {
            $r += 1;
        }

        $this->hSet($key, $name, $r);
        $this->exec();

        return $r;
    }

    /**
     * @param     $lockName
     * @param int $acquireTimeout
     * @param int $lockTimeout
     *
     * @return bool|string
     */
    public function acquireLockWithTimeoutByLua($lockName, $acquireTimeout = 0, $lockTimeout = 0)
    {
        $identifier  = com_create_guid();
        $lockName    = 'lock:' . $lockName;
        $lockTimeout = ceil($lockTimeout);

        $acquired = false;
        $end      = time() + $acquireTimeout;
        $script   = <<<'SCRIPT'
            if redis.call('exists', KEYS[1]) == 0  then
                return redis.call('setex', KEYS[1], unpack(ARGV))
            end
SCRIPT;
        while (time() < $end && !$acquired) {
            $acquired = $this->loadScriptAndExec($script, [$lockName], [$lockTimeout, $identifier]) == 'OK';
            if ($acquired) {
                return $identifier;
            }
            usleep(1);
        }

        return false;
    }

    /**
     * @param $lockName
     * @param $identifier
     *
     * @return mixed|null
     */
    public function releaseLockByLua($lockName, $identifier)
    {
        $lockName = 'lock:' . $lockName;
        $script   = <<<'SCRIPT'
            if redis.call('get', KEYS[1]) == ARGV[1]  then
                return redis.call('del', KEYS[1]) or true
            end
SCRIPT;
        return $this->loadScriptAndExec($script, [$lockName], [$identifier]);
    }

    /**
     * todo 需要优化 怎么获得 script sha，以后不需要script load 直接执行 evalsha？？？？ 写文件吗？？？
     * 可以搞一个 sha-script mapping 直接传递 sha过来
     *
     * @param       $script
     * @param array $keys
     * @param array $args
     *
     * @return mixed|null
     */
    public function loadScriptAndExec($script, $keys = [], $args = [])
    {
        $ret = null;
        $sha = $this->script('load', $script);
        if ($sha) { //加载成功
            $ret = $this->evalSha($sha, array_merge($keys, $args), count($keys));
        }

        if (is_null($ret)) {
            $ret = $this->evaluate($script, array_merge($keys, $args), count($keys));
        }

        return $ret;
    }

    /**
     * 获得lua util字符串
     *
     * @param $filePath
     *
     * @return string
     */
    private function getLuaContent($filePath)
    {
        static $fileContent;
        if (empty($fileContent[$filePath])) {
            if (file_exists($filePath)) {
                $fileContent[$filePath] = file_get_contents($filePath);
            }
        }
        if (isset($fileContent[$filePath])) {
            return $fileContent[$filePath];
        } else {
            return '';
        }
    }

    /**
     * @param array $scriptAndAlias alias:脚本别名 luaFiles：需要加载的lua文件列表(注意顺序,  再alias不存在的时候会获取指定的lua内容)
     * @param array $keys
     * @param array $args
     * @param null  $error
     *
     * @return bool|mixed|null|string
     */
    public function loadScriptWithAliasAndExec($scriptAndAlias, $keys = [], $args = [], &$error = null)
    {
        $alias = $scriptAndAlias['alias'];
        $this->multi();
        $scriptShaKey = 'script:sha';
        $sha          = $this->hGet($scriptShaKey, $alias);
        if (empty($sha)) {
            $scriptFiles = $scriptAndAlias['luaFiles'];
            $script      = '';
            foreach ($scriptFiles as $scriptFile) {
                $script .= PHP_EOL . self::getLuaContent($scriptFile);
            }
            $sha = $this->script('load', $script);
            if ($sha === false) { //lua script error
                $error = $this->handle->getLastError();
                return false;
            }
            $this->hSet($scriptShaKey, $alias, $sha);
        }
        $ret = $this->evalSha($sha, array_merge($keys, $args), count($keys));
        $this->exec();
        $ret = $this->tryDecodeData($ret);
        return $ret;
    }


    /**
     * 获取锁
     *
     * @param string $lockName
     * @param int    $acquireTimeout
     * @param int    $lockTimeout
     *
     * @return bool|string
     */
    public function acquireLockWithTimeout($lockName, $acquireTimeout = 0, $lockTimeout = 0)
    {
        $identifier  = com_create_guid();
        $lockName    = 'lock:' . $lockName;
        $lockTimeout = ceil($lockTimeout);
        $end         = time() + $acquireTimeout;
        while (time() < $end) {
            if ($this->setnx($lockName, $identifier)) {
                $this->expire($lockName, $lockTimeout);
                return $identifier;
            } else if ($this->ttl($lockName)) {
                $this->expire($lockName, $lockTimeout);
            }
            usleep(1);
        }

        return false;
    }

    /**
     * 释放锁
     *
     * @param $lockName
     * @param $identifier
     *
     * @return bool
     */
    public function releaseLock($lockName, $identifier)
    {
        $lockName = 'lock:' . $lockName;
        while (true) {
            try {
                $this->watch($lockName);
                if ($identifier === $this->get($identifier)) {
                    $this->multi();
                    $this->delete($lockName);
                    $this->exec();
                    return true;
                }
                $this->unwatch();
                break;
            } catch (Exception $e) {
                continue;
            }
        }
        return false;
    }
}