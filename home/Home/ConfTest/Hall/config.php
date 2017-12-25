<?php
return array(
    'HALL_MDLSENDMAIL' => 'qianwen@imageco.com.cn,qianwen@imageco.com.cn',  // 麦当劳采购邮箱
    'HALL_OTHERSENDMAIL' => 'qianwen@imageco.com.cn,qianwen@imageco.com.cn,qianwen@imageco.com.cn',  // 普通采购订单发送邮箱
    'HALL_PURCHASE_MESSAGE' => 'qianwen@imageco.com.cn',  // 采购留言发送邮箱
                                                         // 采购入门,供货入门链接
    'HALL_PS_LINK' => array(
        'PLOGIN_ID' => '1358',  // 登陆大厅id
        'PSEARCH_ID' => '1355',  // 搜索卡券id
        'PORDER_ID' => '1356',  // 下单采购id
        'S_ID' => '1357'),  // 发布卡券id
                           // 招商,平安,移动连接
    'HALL_COOPERATION_CASE' => array(
        'ZS_GOODS_ID' => '3791',  // 招商合作id
        'PA_GOODS_ID' => '3791',  // 平安合作id
        'YD_GOODS_ID' => '3791'),  // 移动合作id
                                  // 活动页面配置
    'HALL_ACTIVITY_CONFIG' => array(
        'EMPLOYEE_HALL_ID' => '3826,3775,3769,3774,3847,3815,3784,3816',  // 交易大厅员工福利活动页面hall_id
        'FEEDBACK_HALL_ID' => '3826,3775,3769,3774,3847,3815,3784,3816',  // 交易大厅回馈活动页面hall_id
        'CASHGIFT_HALL_ID' => '3791,3792,3793,3826,3775,3769,3774,3847,3815,3784,3816'),  // 交易大厅积分换礼活动页面hall_id
                                                                                         
    // 卡券大厅采购卡券不用后台审核和供货商确认的商户并直接付款
    'HALL_NOCHECKED_NODE' => array(
        '00026652', 
        '00030804'), 
    // 交易大厅最近浏览cookie域名
    'HALL_COOKIE_DOMAIN' => '.wangcaio2o.com');