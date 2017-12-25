<?php

class Rank
{
    const PREFIX = 'rank:';

    protected $Redis = null;

    protected $expire;

    public function __construct(Redis $redis, $expire)
    {
        $this->Redis  = $redis;
        $this->expire = $expire;
    }

    public function addScoresByKey($key, $member, $scores, $expire = 0)
    {
        $key = self::PREFIX . $key;
        return $this->doAddScores($key, $scores, $member, $expire);
    }

    public function addScores($member, $scores, $expire = 0)
    {
        $key = self::PREFIX . date('Ymd');
        return $this->doAddScores($key, $scores, $member, $expire);
    }

    private function doAddScores($key, $scores, $member, $expire)
    {
        $return = $this->Redis->zIncrBy($key, $scores, $member);
        $r = $this->Redis->expire($key, $expire);
        file_debug('$key:'.$key . ' $expire:'.$expire .' => '. var_export($r,1), 'doAddScores','sql.log');
        return $return;
    }

    protected function getOneDayRankings($date, $start, $stop)
    {
        $key    = self::PREFIX . $date;
        $return = $this->Redis->zRevRange($key, $start, $stop, true);
        $this->Redis->expire($key, $this->expire);
        return $return;
    }

    /**
     * @param      $keys
     * @param      $outKey
     * @param      $start
     * @param      $stop
     *
     * @param bool $withscore
     *
     * @return array
     */
    public function getMultiKeysRankings($keys, $outKey, $start, $stop, $withscore = true)
    {
        $finalKeys = array_map(
                function ($key) {
                    return self::PREFIX . $key;
                },
                $keys
        );
        $weights   = array_fill(0, count($finalKeys), 1);
        $this->Redis->zUnion($outKey, $finalKeys, $weights);
        $return = $this->Redis->zRevRange($outKey, $start, $stop, $withscore);

        $this->Redis->expire($outKey, $this->expire);
        return $return;
    }

    public function getTopNByKeys($keys, $start = 0, $end = 9, $withscore = true)
    {
        return $this->getMultiKeysRankings($keys, 'rank:topn', $start, $end, $withscore);
    }
}