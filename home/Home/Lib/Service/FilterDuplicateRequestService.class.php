<?php

/**
 * 过滤重复请求 （现在主要针对抽奖）
 *
 * @author Jeff Liu<liuwy@imageco.com.cn> Class FilterDuplicateRequestService
 */
class FilterDuplicateRequestService
{

    /**
     *
     * @var Memcache
     */
    private $Memcache;

    /**
     * @var RedisHelper
     */
    private static $RedisHelper;

    /**
     * 获得memcache
     *
     * @return Memcache
     */
    private function getMemcache()
    {
        if (empty($this->Memcache)) {
            $memcache      = new Memcache();
            $memcache_ip   = C('SSO_MEMCACHE_IP');
            $memcache_port = 11211;
            $memcache->addserver($memcache_ip, $memcache_port) or die("退出失败,无法连接memcache");
            $this->Memcache = $memcache;
        }

        return $this->Memcache;
    }

    /**
     * 设置处理标示（3s内重复提交 后来的直接无视）
     *
     * @param       $flag
     * @param int   $expire
     * @param array $duplicateKey
     *
     * @internal param $data
     */
    private function setProcessingFlag($flag, $expire = 3, array $duplicateKey)
    {
        $this->getMemcache();
        $key = $this->buildKey($duplicateKey);
        $this->Memcache->set($key, $flag, 0, $expire);
    }

    private function buildKey($duplicateKey = array())
    {
        if ($duplicateKey) {
            $md5 = md5(implode(':', $duplicateKey));
        } else {
            $md5 = md5(implode(':', $_REQUEST));
        }
        return 'dp:pk:' . $md5;
    }

    /**
     * 获得标示
     *
     * @return int
     */
    private function getProcessingFlag($duplicateKey)
    {
        $this->getMemcache();
        $key = $this->buildKey($duplicateKey);
        return (int)($this->Memcache->get($key));
    }

    /**
     * 过滤重复请求（相同mobile，batch_id,node_id视为相同的请求）
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param array $duplicateKey
     * @param int   $expire
     *
     * @return bool
     */
    public function filterDuplicateRequest(array $duplicateKey, $expire = 3)
    {
        $sleep = mt_rand(0, 1000) * 1000; // 0s - 1s
        usleep($sleep);
        $inProcessing = $this->getProcessingFlag($duplicateKey);
        if ($inProcessing) { // 相同手机号、相同node_id、相同batch_id 3s内有处理过 不再进行处理
            return false;
        }
        $this->setProcessingFlag(1, $expire, $duplicateKey); // 设置标示
        return true;
    }


    //===== filter via redis for server start
    public function filterDuplicateRequestBaseRedis($params = array(), $prefix = '')
    {
        import('@.Vendor.RequestUtil');
        $requestUtil = new RequestUtil();
        $prefix      = get_scalar_val($prefix, 'filterDuplicateRequest:');
        $requestUtil->init(0, 2000000, 3);
        $key = $requestUtil->buildRedisKey($prefix, $params);
        $ret = $requestUtil->filterDuplicateRequest($key);
        return $ret;
    }
    //===== filter via redis for server end


    //==== filter via cookie for browser  start

    /**
     * @param        $id
     * @param        $personId
     * @param string $keyPrefix
     *
     * @return bool
     */
    public static function getIgnoreFlag($id, $keyPrefix = 'needTmpIgnore')
    {
        $key        = self::genereateIgnoreKey($id, $keyPrefix);
        $needIgnore = cookie($key);
        return (boolean)$needIgnore;
    }

    public static function setIgnoreFlag($id, $value = 1, $expire = '', $keyPrefix = 'needTmpIgnore')
    {
        if (empty($expire)) {
            $expire = time() + 300;
        } else {
            $expire += time();
        }
        $needIgnoreKey = self::genereateIgnoreKey($id, $keyPrefix);
        setcookie($needIgnoreKey, $value, $expire);
    }

    public static function genereateIgnoreKey($id, $keyPrefix = 'needTmpIgnore')
    {
        return $keyPrefix . $id;
    }

    /**
     * @param        $id
     * @param        $personId
     * @param string $keyPrefix
     *
     * @return string
     */
    public static function genereateIgnoreReidsKey($id, $personId, $keyPrefix = 'needTmpIgnore')
    {
        return $keyPrefix . $personId . ':' . $id;
    }
    //==== filter with cookie for browser  end


    // -------- 针对微信抽奖进行特殊处理 start  ------------------------------------------------------------
    /**
     * @param        $id
     * @param        $personId
     * @param string $keyPrefix
     *
     * @return string
     */
    public static function genereateWechatDrawLotteryFlagKey($id, $personId, $keyPrefix = 'wechatDrawLotteryFlag')
    {
        return $keyPrefix .':'. $personId . ':' . $id;
    }

    /**
     * @param     $batchId
     * @param     $personId
     * @param int $expire
     */
    public static function setWechatDrawLotteryFlag($batchId, $personId, $expire = 86400)
    {
        $key = self::genereateWechatDrawLotteryFlagKey($batchId, $personId);
        if (empty(self::$RedisHelper)) {
            import('@.Vendor.RedisHelper');
            self::$RedisHelper = RedisHelper::getInstance();
        }

        self::$RedisHelper->set($key, 1);
        self::$RedisHelper->expire($key, $expire);//一天以后过期
    }

    /**
     * @param $batchId
     * @param $personId
     *
     * @return bool|mixed|null|string
     */
    public static function getWechatDrawLotteryFlag($batchId, $personId)
    {
        $key = self::genereateWechatDrawLotteryFlagKey($batchId, $personId);
        if (empty(self::$RedisHelper)) {
            import('@.Vendor.RedisHelper');
            self::$RedisHelper = RedisHelper::getInstance();
        }

        return self::$RedisHelper->get($key);
    }
    // -------- 针对微信抽奖进行特殊处理 end------------------------------------------------------------------


    // -------- 记录抽奖正在处理中的flag------------ start----------------------------------------------------------------
    /**
     * @param        $id
     * @param        $personId
     * @param string $keyPrefix
     *
     * @return string
     */
    public static function genereateDrawLotteryProcessingFlagKey($id, $personId, $keyPrefix = 'DrawLotteryProcessingFlag')
    {
        return $keyPrefix .':'. $personId . ':' . $id;
    }

    /**
     * @param     $batchId
     * @param     $personId
     * @param int $expire
     */
    public static function setDrawLotteryProcessingFlag($batchId, $personId, $expire = 86400)
    {
        $key = self::genereateDrawLotteryProcessingFlagKey($batchId, $personId);
        if (empty(self::$RedisHelper)) {
            import('@.Vendor.RedisHelper');
            self::$RedisHelper = RedisHelper::getInstance();
        }

        self::$RedisHelper->set($key, 1);
        self::$RedisHelper->expire($key, $expire);//$expire 秒以后过期
    }

    /**
     * @param $batchId
     * @param $personId
     *
     * @return bool|mixed|null|string
     */
    public static function getDrawLotteryProcessingFlag($batchId, $personId)
    {
        $key = self::genereateDrawLotteryProcessingFlagKey($batchId, $personId);
        if (empty(self::$RedisHelper)) {
            import('@.Vendor.RedisHelper');
            self::$RedisHelper = RedisHelper::getInstance();
        }

        return self::$RedisHelper->get($key);
    }

    public static function delDrawLotteryProcessingFlag($batchId, $personId)
    {
        $key = self::genereateDrawLotteryProcessingFlagKey($batchId, $personId);
        if (empty(self::$RedisHelper)) {
            import('@.Vendor.RedisHelper');
            self::$RedisHelper = RedisHelper::getInstance();
        }

        self::$RedisHelper->del($key);
        return true;
    }
    // -------- 记录抽奖正在处理中的flag------------ end------------------------------------------------------------------

}