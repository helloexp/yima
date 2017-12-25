<?php

/**
 * 双旦购买模块功能任务完成
 *
 * @author Jeff Liu<liuwy@imageco.com.cn> Class PayOrderTaskFinishBehavior
 */
import('@.Service.DoubleFestivalService');

class PayOrderTaskFinishBehavior extends TaskFinishBehavior {

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param mixed $params
     *
     * @return array|void
     */
    public function run(&$params) {
        $params['taskId'] = DoubleFestivalService::PAY_ORDER_TASK_ID;
        return parent::run($params);
    }
}