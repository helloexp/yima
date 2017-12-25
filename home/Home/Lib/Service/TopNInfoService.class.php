<?php

class TopNInfoService
{

    /**
     * @var TopNInfoRedisModel
     */
    public $TopNInfoRedisModel;

    public function __construct()
    {
        $this->TopNInfoRedisModel = D('TopNInfoRedis');
    }

    /**
     * @return string
     */
    public function getTopN()
    {
        return $this->TopNInfoRedisModel->getTopN();
    }
}
