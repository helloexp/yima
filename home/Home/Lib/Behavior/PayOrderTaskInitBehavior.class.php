<?php

/**
 * 双旦购买模块功能任务完成
 *
 * @author Jeff Liu<liuwy@imageco.com.cn> Class PayOrderTaskInitBehavior
 */
import('@.Service.DoubleFestivalService');

class PayOrderTaskInitBehavior extends TaskInitBehavior {

    public function run(&$params) {
        $params['taskId'] = DoubleFestivalService::PAY_ORDER_TASK_ID;
        return parent::run($params);
    }
}