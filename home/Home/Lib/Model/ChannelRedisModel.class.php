<?php

/**
 * 抽奖相关 model
 *
 * @author : Jeff Liu<liuwy@imageco.com.cn> Date: 2015/09/08
 */
import('@.Model.RedisDBBaseModel');

class ChannelRedisModel extends RedisDBBaseModel {

    protected $tableName = 'tchannel';

    protected $_pk = 'node_id';

    protected $_sk = 'id';
}