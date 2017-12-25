<?php

/**
 * 注册功能任务初始化
 *
 * @author Jeff Liu<liuwy@imageco.com.cn> Class TaskInitBehavior
 */
import('@.Service.DoubleFestivalService');

class RegTaskInitBehavior extends TaskInitBehavior {

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param array $params
     *
     * @return array|void
     */
    public function run(&$params) {
        $params['taskId'] = DoubleFestivalService::REG_TASK_ID;
        if ((isset($_REQUEST['landname']) &&
             ($_REQUEST['landname'] == 'sdpc' || $_REQUEST['landname'] == 'dfp')) ||
             (isset($_REQUEST['tg_id']) && $_REQUEST['tg_id'] == 'dfp')) { // 只有指定landname才可以参与双旦任务
            $return = parent::run($params);
            return $return;
        }
        
        return array(
            'code' => '-1', 
            'msg' => 'landname is invalid');
    }
}