<?php
return array(
        'REDIS'                => array(
                'host' => '10.10.1.134',
                'port' => '6379'
        ),
        'REDIS_PREFIX'         => 'imageco:',
        'useRedisCache'        => true,  // 是否使用redis作为db的缓存
        'useRedisCacheControl' => 'model',//config:使用 config中的redisCacheTableList  model:使用model的 useCache
        'redisCacheTableList'  => array( // 缓存表
                'tmarketing_info' => 1,  // 值为1标示使用redis作为db缓存
                'tbatch_channel'  => 1
        )
);
// 值为1标示使用redis作为db缓存


