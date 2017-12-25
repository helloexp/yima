<?php

/**
 * 商户相关 model
 *
 * @author : Jeff Liu<liuwy@imageco.com.cn> Date: 2016-04-14
 */
class DrawLotteryWhiteBlackListRedisModel extends RedisDBBaseModel
{
    protected $tableName = 'tcj_white_blacklist';
    protected $_pk = 'node_id';
    protected $_sk = 'phone_no';
}