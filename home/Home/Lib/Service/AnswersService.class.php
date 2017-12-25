<?php

class AnswersService
{
    // 活动类型
    public $BATCH_TYPE = '10';

    /**
     * @var RedisHelper
     */
    public $redisHelper;

    public function __construct()
    {
        import('@.Vendor.RedisHelper');
        $this->redisHelper = RedisHelper::getInstance();
    }

    public function getQueueData($originKey, $batchId)
    {
        $queueDataKey = $this->generateQueueDataKey($batchId, $originKey);
        return $this->redisHelper->get($queueDataKey);
    }

    private function formatAskList($batchId, $aQuestion, &$anwersQuestionList, $aIndex, $valueList)
    {
        $askList = M('tanswers_question_stat')->where(
                ['label_id' => $batchId, 'question_id' => $aQuestion['id']]
        )->field('answer_list')->select();
        foreach ($askList as $kk => $vv) {
            $anwersQuestionList[$aIndex]['ask_list'][]  = $askList[$kk]['answer_list']; // 选项内容（单选选项）
            $anwersQuestionList[$aIndex]['ask_lists'][] = $askList[$kk]['answer_list']; // 选项内容（多选选项）
            $anwersQuestionList[$aIndex]['count']       = count($anwersQuestionList[$aIndex]['ask_lists']); // 选项总数统计
            // 多选处理
            if (2 == $aQuestion['type']) {
                foreach ($anwersQuestionList[$aIndex]['ask_list'] as $kkk => $vvv) {
                    if (1 < strlen($vvv)) {
                        $arr = explode('-', $anwersQuestionList[$aIndex]['ask_list'][$kkk]);
                        unset($anwersQuestionList[$aIndex]['ask_list'][$kkk]);
                        $anwersQuestionList[$aIndex]['ask_list'] = array_merge(
                                $anwersQuestionList[$aIndex]['ask_list'],
                                $arr
                        );
                    }
                }
            }
            $anwersQuestionList[$aIndex]['ask_list_count'] = array_count_values(
                    $anwersQuestionList[$aIndex]['ask_list']
            ); // 选项内容分类个数统计
        }
        ksort($anwersQuestionList[$aIndex]['ask_list_count']);

        foreach ($anwersQuestionList[$aIndex]['ask_list_count'] as $kkk => $vvvv) {
            $anwersQuestionList[$aIndex]['ask_list_counts'][$valueList[$kkk] - 1] = $vvvv;
            $anwersQuestionList[$aIndex]['percent'][$valueList[$kkk] - 1]         = number_format(
                    $vvvv / $anwersQuestionList[$aIndex]['count'],
                    4
            );
        }
    }

    public function generateQueueKey($batchId, $key = '', &$newKey = '')
    {
        $newKey = $this->genreateCommonQueueKey($key);
        if ($newKey) {
            return $batchId . ':' . $newKey;
        } else {
            return $batchId;
        }

    }

    /**
     * @param $text
     *
     * @return bool
     */
    private function ctype_int($text)
    {
        return preg_match('/^[0-9]+$/', (string)$text) ? true : false;
    }

    public function getInfoResult($originKey = '', $batchId = '', $map = [])
    {
        if ($batchId) {
            if (empty($originKey)) {
                $originKey = $this->generateQueueDataKey($batchId, $this->genreateCommonQueueKey());
            }
            $result = $this->getQueueData($originKey, $batchId);
            $refreshTime = 0;
            if (empty($result)) {
                $newKey = $originKey;
                $result = $this->getInfoQueue($originKey, $batchId);
                if (empty($result)) {
                    $this->addInfoQueue($originKey, $batchId, $map, $newKey);
                } else if ($result != 1001) { //还没有处理 调用 异步请求进行处理
                    $this->processQueueData($newKey, $batchId);
                }
                return ['retCode' => '1001', 'data' => ['key' => ''], 'retTxt' => '请等待,正在处理数据'];
            } else {
                $refreshTime = $this->getInfoRefreshTime($batchId);
            }
            return ['retCode' => '0000', 'content' => $result, 'refreshTime' => $refreshTime, 'retTxt' => '已经计算完成'];
        }

        return ['retCode' => '1002', 'data' => [], 'retTxt' => '请求参数错误'];
    }


    /**
     * 异步请求
     *
     * @param        $url
     * @param int    $limit
     * @param string $post
     * @param string $cookie
     * @param string $ip
     * @param int    $timeout
     * @param int    $block
     *
     * @return string
     */
    public function asyncRequest($url, $limit = 0, $post = '', $cookie = '', $ip = '', $timeout = 3, $block = 0)
    {
        $return = '';
        $uri    = parse_url($url);

        isset($uri['host']) || $uri['host'] = '';
        isset($uri['path']) || $uri['path'] = '';
        isset($uri['query']) || $uri['query'] = '';
        isset($uri['port']) || $uri['port'] = '';
        $host = $uri['host'];
        if ((isset($_GET['__TEST_ENV']) && $_GET['__TEST_ENV']) || (isset($_COOKIE['SERVER_ID']) && $_COOKIE['SERVER_ID'] == 'RUN-IN-TEST-APP2')) {//测试环境
            if (empty($uri['query'])) {
                $uri['query'] = '__TEST_ENV=true';
            } else {
                $uri['query'] .= '&__TEST_ENV=true';
            }
        }
        $path = $uri['path'] ? $uri['path'] . ($uri['query'] ? '?' . $uri['query'] : '') : '/';
        $port = !empty($uri['port']) ? $uri['port'] : 80;

        if ($post) {
            $out = "POST $path HTTP/1.1\r\n";
            $out .= "Accept: */*\r\n";
            //$out .= "Referer: $boardurl\r\n";
            $out .= "Accept-Language: zh-cn\r\n";
            $out .= "Content-Type: application/x-www-form-urlencoded\r\n";
            $out .= "User-Agent: $_SERVER[HTTP_USER_AGENT]\r\n";
            $out .= "Host: $host\r\n";
            $out .= 'Content-Length: ' . strlen($post) . "\r\n";
            $out .= "Connection: Close\r\n";
            $out .= "Cache-Control: no-cache\r\n";
            $out .= "Cookie: $cookie\r\n\r\n";
            $out .= $post;
        } else {
            $out = "GET $path HTTP/1.1\r\n";
            $out .= "Accept: */*\r\n";
            //$out .= "Referer: $boardurl\r\n";
            $out .= "Accept-Language: zh-cn\r\n";
            $out .= "User-Agent: $_SERVER[HTTP_USER_AGENT]\r\n";
            $out .= "Host: $host\r\n";
            $out .= "Connection: Close\r\n";
            $out .= "Cookie: $cookie\r\n\r\n";
        }

        $fp = fsockopen(($ip ? $ip : $host), $port, $errno, $errstr, $timeout);
        if (!$fp) {
            return '';
        } else {
            stream_set_blocking($fp, $block);//集阻塞/非阻塞模式流,$block==true则应用流模式
            stream_set_timeout($fp, $timeout);//设置流的超时时间
            fwrite($fp, $out);

            $status = stream_get_meta_data($fp);//从封装协议文件指针中取得报头／元数据
            //timed_out如果在上次调用 fread() 或者 fgets() 中等待数据时流超时了则为 TRUE,下面判断为流没有超时的情况
            if (!$status['timed_out']) {
                if ($block) {//如果设置为block 需要
                    while (!feof($fp)) {
                        if (($header = @fgets($fp)) && ($header == "\r\n" || $header == "\n")) {
                            break;
                        }
                    }
                    $stop = false;
                    //如果没有读到文件尾
                    while (!feof($fp) && !$stop) {
                        //看连接时限是否=0或者大于8192  =》8192  else =》limit  所读字节数
                        $data = fread($fp, ($limit == 0 || $limit > 8192 ? 8192 : $limit));
                        $return .= $data;
                        if ($limit) {
                            $limit -= strlen($data);
                            $stop = $limit <= 0;
                        }
                    }
                }
            }
            usleep(50);//PS:如果不sleep的话，会出现请求还没有发送出去 就被close了
            fclose($fp);
            return $return;
        }

    }

    /**
     * @param string $key
     *
     * @return bool|int|string
     */
    public function genreateCommonQueueKey($key = '')
    {
        return '';
        if (!$this->ctype_int($key) || strlen($key) != 12) {
            $key = date('YmdHi'); //201606101009
        }
        $key = substr_replace($key, 0, -1);
        return $key;
    }

    /**
     * @param $batchId
     * @param $key
     *
     * @return bool|int|string
     */
    public function generateQueueDataKey($batchId, $key)
    {
        $key = $this->genreateCommonQueueKey($key);
        if ($key) {
            $key = 'answerListData:' . $batchId . ':' . $key;
        } else {
            $key = 'answerListData:' . $batchId;
        }

        return $key;
    }

    /**
     * @param        $originKey
     * @param int    $batchId
     * @param array  $map
     * @param string $newKey
     *
     * @return bool
     */
    public function addInfoQueue($originKey, $batchId = 0, $map = [], &$newKey = '')
    {
        $key     = $this->generateQueueKey($batchId, $originKey, $newKey);
        $mainKey = 'answerListQueue';
        if (!$this->redisHelper->hExists($mainKey, $key)) {
            $map['key'] = $newKey;
            $this->redisHelper->hSet($mainKey, $key, $map);
            $this->processQueueData($newKey, $batchId);
            return true;
        }
        return true;
    }

    /**
     * @param $key
     * @param $batchId
     */
    public function processQueueData($key, $batchId)
    {
        $url = U('CronJob/Answers/calcInfoData', ['key' => $key, 'batch_id' => $batchId], '', '', true);
        $this->asyncRequest($url);
    }

    /**
     * @return bool|mixed|null|string
     */
    public function getAllInfoQueue()
    {
        $mainKey = 'answerListQueue';
        return $this->redisHelper->hGetAll($mainKey);
    }

    public function setInfoQueueStatus($key, $status)
    {
        $mainKey = 'answerListQueue';
        $this->redisHelper->hSet($mainKey, $key, $status);
        return true;
    }

    public function deleteInfoQueue($key)
    {
        $mainKey = 'answerListQueue';
        $this->redisHelper->hDel($mainKey, $key);
    }

    /**
     * @param int $batchId
     *
     * @param     $originKey
     *
     * @return bool|mixed|null|string
     */
    public function getInfoQueue($originKey, $batchId)
    {
        $key     = $this->generateQueueKey($batchId, $originKey);
        $mainKey = 'answerListQueue';
        if (!$this->redisHelper->hExists($mainKey, $key)) {
            return false;
        } else {
            $result = $this->redisHelper->hGet($mainKey, $key);
            if ($result == 1001) { //正在处理
                $result = false;
            }
        }
        return $result;
    }

    public function calcInfoData($key = '', $batchId = '')
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        import('@.Vendor.RedisHelper');
        $do = false;
        if (empty($batchId)) {
            $allQueueData     = $this->getAllInfoQueue();
            $currentQueueData = [];
            $currentField     = '';
            foreach ($allQueueData as $currentField => $map) {
                if ($currentQueueData != 1001) {
                    $this->setInfoQueueStatus($currentField, 1001);
                    $do = true;
                    break;
                }
            }
        } else {
            $newKey = '';
            $currentField     = $this->generateQueueKey($batchId, $key, $newKey);
            $map          = $this->getInfoQueue($key, $batchId);
            if ($map && $map != 1001) {//需要进行处理
                $this->setInfoQueueStatus($currentField, 1001);
                $do = true;
            }
        }

        if (!$do) {
            echo date('Y-m-d H:i:s'), ' no data needed process ..';
            exit;
        }
        $batchId = get_val($map, 'id');
        $key     = get_val($map, 'key');

        $anwersQuestionList = M('tanswers_question')->where(['label_id' => $batchId, 'type' => ['in', '1,2']])->order(
                "sort"
        )->select();

        // 答案以及百分比
        $valueList = [
                'A' => '1',
                'B' => '2',
                'C' => '3',
                'D' => '4',
                'E' => '5',
                'F' => '6',
                'G' => '7',
                'H' => '8',
                'I' => '9',
                'J' => '10',
                'K' => '11',
                'L' => '12',
                'M' => '13',
                'N' => '14',
                'O' => '15',
                'P' => '16',
                'Q' => '17',
                'R' => '18',
                'S' => '19',
                'T' => '20',
                'U' => '21',
                'V' => '22',
                'W' => '23',
                'X' => '24',
                'Y' => '25',
                'Z' => '26'
        ];

        foreach ($anwersQuestionList as $aIndex => $aQuestion) {
            $answersList = M('tanswers_question_info')->where(['question_id' => $aQuestion['id']])->select();
            if ($answersList !== false) {
                $anwersQuestionList[$aIndex]['answers_list'] = $answersList;
            }
            $this->formatAskList($batchId, $aQuestion, $anwersQuestionList, $aIndex, $valueList);
        }

        $queueDataKey = $this->generateQueueDataKey($batchId, $key);
        $this->redisHelper->set($queueDataKey, $anwersQuestionList);
        $this->deleteInfoQueue($currentField);
        $this->setInfoRefreshTime($batchId, date('Y-m-d H:i:s'));
    }

    public function setInfoRefreshTime($batchId, $time)
    {
        return $this->redisHelper->set($batchId . ':refresh_time', $time);
    }

    /**
     * @param $key
     *
     * @return bool|mixed|null|string
     */
    public function getInfoRefreshTime($batchId)
    {
        $return = $this->redisHelper->get($batchId . ':refresh_time');
        if (empty($return)) {
            $return = '-';
        }
        return $return;
    }
}