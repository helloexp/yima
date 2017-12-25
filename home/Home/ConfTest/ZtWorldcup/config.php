<?php
// 引入外部配置
return array_merge(require (CONF_PATH . 'LabelAdmin/config.php'), 
    array(
	/*自定义配置*/
		'WORLDCUP_END_TIME' => '20140627070000',  // 世界杯结束时间(小组赛的结束时间)
        'WORLDCUP_OVER_TIME' => '20140714070000',  // 世界杯结束时间
        'LEVEL_MORE_PRIZE_NODE' => array(
            '00004488'), 
        'WORLDCUP_PUFA_NODE_ID' => '00004488'));
