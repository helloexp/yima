<?php

/**
 * 抽奖相关 model
 *
 * @author : Jeff Liu<liuwy@imageco.com.cn> Date: 2015/09/08
 */
import('@.Model.RedisDBBaseModel');

class BatchChannelRedisModel extends RedisDBBaseModel
{
    protected $tableName = 'tbatch_channel';
    protected $_pk = 'id';
    protected $_sk = null;
}