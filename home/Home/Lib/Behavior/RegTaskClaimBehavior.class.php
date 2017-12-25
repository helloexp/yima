<?php

/**
 * 注册功能任务完成
 *
 * @author Jeff Liu<liuwy@imageco.com.cn> Class TaskInitBehavior
 */
import('@.Service.DoubleFestivalService');

class RegTaskClaimBehavior extends TaskClaimBehavior {

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param array $params
     *
     * @return array|void
     */
    public function run(&$params) {
        $params['taskId'] = DoubleFestivalService::REG_TASK_ID;
        return parent::run($params);
    }
}