<?php 

$redis = new Redis();

$redis->connect('10.10.1.134', '6379');

$redis->set('tttttt', 'kkkk');

echo $redis->get('tttttt');