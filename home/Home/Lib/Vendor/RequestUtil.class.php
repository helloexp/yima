<?php

/**
 * 请求 公用程序
 *
 * @author Jeff Liu @date 2016-01-26 Class RequestUtil
 */
class RequestUtil
{

    /**
     *
     * @var Redis
     */
    private $Redis;

    private $min = 0;//单位微秒
    private $max = 2000000; //单位微秒
    private $expire = 3;//单位：秒

    public function init($mix, $max, $expire)
    {
        $this->min    = $mix;
        $this->max    = $max;
        $this->expire = $expire;
    }

    /**
     * 过滤重复请求（batch_id,node_id视为相同的请求）
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param $key
     *
     * @return bool
     */
    public function filterDuplicateRequest($key)
    {
        $sleep = rand($this->min, $this->max);
        usleep($sleep);
        $inProcessing = $this->getPoolData($key);
        if ($inProcessing) { // 已经在处理的东西不需要再此进行处理
            return false;
        }
        $this->setPoolData($key, 1, $this->expire); // 设置标示
        return true;
    }

    /**
     * @param $key
     *
     * @return boolean
     */
    public function filterDuplicateRequestViaScript($key)
    {
        $script = <<<'SCRIPT'
        local final_key = KEYS[1] .. KEYS[2]
        local ret = redis.call("setnx", final_key, KEYS[4])
        if ret == 1 then
            redis.call("expire", final_key, KEYS[3])
            return true
        else 
            return false
        end
SCRIPT;
        return $this->Redis->evaluate($script,['imageco:', $key, $this->expire, 1]);//evaluate方法是有返回值的 IDE提示错误
    }

    /**
     * 新增队列
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param     $key
     * @param     $data
     * @param int $expire 单位：秒数
     */
    private function setPoolData($key, $data, $expire = 0)
    {
        $redis = $this->getRedisInstance();
        if ($redis) {
            $redis->set($key, $data);
            if ($expire) {
                $redis->expire($key, $expire); // 为key设置过期时间
            }
        }
    }

    /**
     *
     * @param $key
     *
     * @return mixed
     */
    private function getPoolData($key)
    {
        $data  = 0;
        $redis = $this->getRedisInstance();
        if ($redis) {
            $data = $redis->get($key);
        }

        return $data;
    }

    /**
     * @param $param
     *
     * @return string
     */
    public function buildRedisKey($prefix, $param)
    {
        return $prefix . md5(json_encode($param));
    }


    /**
     * 获取redis连接
     *
     * @return bool|Redis
     */
    public function getRedisInstance()
    {
        if (empty($this->Redis)) {
            $this->Redis = new Redis();
            $config = C('REDIS') or die(__CLASS__ . ' CONFIG.REDIS is undefined');
            $this->Redis->connect($config['host'], $config['port']);
            if (!$this->Redis) {
                $msg = 'redis ' . $config['host'] . ':' . $config['port'] . ' is gone';
                log_write($msg);

                return false;
            }
        }

        return $this->Redis;
    }
}