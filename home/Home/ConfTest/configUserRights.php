<?php
return array(
    'NEW_CHARGE_ID' => '3005',  // 拍码阅读
    'BM_CHARGE_ID' => '3006',  // 拍码调研
    'MEMBER_CHARGE_ID' => '3003',  // 会员卡
    'CARDS_CHARGE_ID' => '3004',  // 储值卡
    'BASIC_CHARGE_ID' => '3050'); // 基础平台服务费(包括拍码阅读和拍码调研)




/*
//用户权限,配置需要验证的模块操作'action'=>'*'表示该分组模块下所有操作都需要验证，'action'=>array('index','add')表示对该分组模块下特定的操作作验证
return array(
		'USERRIGHTS' => array(
				
				'LabelAdmin' => array(//营销服务
						'Game' => array(//玩游戏赢大奖
								'chargeId' => '',//对应的chargeId
								'action' => '*'
						),
						'News' => array(//拍码阅读
								'chargeId' => '3005',
								'action' => '*'
						),
						'Bm' => array(//拍码调研
								'chargeId' => '3006',
								'action' => '*'
						),
				),
		)
);
*/