<?php

/**
 * 双旦购买模块功能任务完成
 *
 * @author Jeff Liu<liuwy@imageco.com.cn> Class PayOrderTaskFinishBehavior
 */
import('@.Service.DoubleFestivalService');

class FavourTaskInitBehavior extends TaskInitBehavior {

    public function run(&$params) {
        $params['taskId'] = DoubleFestivalService::FAVOUR_TASK_ID;
        return parent::run($params);
    }
}