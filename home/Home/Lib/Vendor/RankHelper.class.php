<?php

import('@.Vendor.Rank');
import('@.Vendor.RedisHelper');

class RankHelper
{

    /**
     * @var Redis
     **/
    private $Redis;

    private static $instance;

    private $handle;

    private $expire;//过期时间(秒数)

    private function __construct($expire)
    {
        $this->Redis  = RedisHelper::getInstance();
        $this->handle = new Rank($this->Redis, $expire);
        $this->expire = $expire;
    }

    private function __clone()
    {
    }

    public static function getInstance($expire  = 300)
    {
        if (empty(self::$instance)) {
            self::$instance = new RankHelper($expire);
        }
        return self::$instance;
    }

    public function buildKey($t = '')
    {
        if (empty($t)) {
            $t = time();
        }
        return date('YmdHi', $t); //如果需要一分为单位的话 需要修改为 date('YmdHi', $t)
    }

    public function addOneScore($member, $expire = 0)
    {
        return $this->addScores($member, 1, $expire);
    }

    public function addScores($member, $scores, $expire = 0)
    {
        $key = $this->buildKey();
        return $this->addScoresByKey($key, $member, $scores, $expire);
    }

    public function addScoresByKey($key, $member, $scores, $expire = 0)
    {
        if ($expire === 0) {
            $expire = $this->expire;
        }
        $return = $this->handle->addScoresByKey($key, $member, $scores, $expire);
        return $return;
    }

    public function buildTopNKey($n)
    {
        $keys   = [];
        $nowKey = $this->buildKey();
        for (; $n > 0; $n--) {
            $keys[] = $nowKey - $n;
        }
        return $keys;
    }

    public function getTop10()
    {
        $n    = 10;
        $keys = $this->buildTopNKey($n);
        return $this->getTopNByKeys($keys, $n, false);
    }

    /**
     * @param      $keys
     *
     * @param      $n
     * @param bool $withscore
     *
     * @return array
     */
    public function getTopNByKeys($keys, $n, $withscore = true)
    {
        return $this->handle->getTopNByKeys($keys, 0, $n, $withscore);
    }
}

//
//$redis = RedisHelper::getRedisInstance();
//
//$rank = new Rank($redis);
//
////$rank->addScoresNew('201603081110', 'zhangsan',5);
////$rank->addScoresNew('201603081110', 'lisi',6);
////$rank->addScoresNew('201603081110', 'wangwu',2);
////
////$rank->addScoresNew('201603081111', 'zhangsan',5);
////$rank->addScoresNew('201603081111', 'lisi',3);
////$rank->addScoresNew('201603081111', 'wangwu',3);
////
////$rank->addScoresNew('201603081112', 'zhangsan',5);
////$rank->addScoresNew('201603081112', 'lisi',3);
////$rank->addScoresNew('201603081112', 'xiaoming',20);
////
////$rank->addScoresNew('201603081113', 'zhangsan',5);
////$rank->addScoresNew('201603081113', 'lisi',3);
////$rank->addScoresNew('201603081113', 'liukun',90);
//
//$data = $rank->getCurrentMonthTop10();
//var_export($data);