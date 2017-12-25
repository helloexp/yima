<?php

/**
 * 双旦购买模块功能任务完成
 *
 * @author Jeff Liu<liuwy@imageco.com.cn> Class PayOrderTaskFinishBehavior
 */
class DoubleFestivalBehavior extends Behavior
{

    /**
     * @var DoubleFestivalService
     */
    private $DoubleFestivalService;

    /**
     * @param BaseAction $params
     */
    public function run(&$params)
    {
        $functionSwitchResult = functionSwitchResult('DoubleFestival');
        if ($functionSwitchResult === true) {
            if ($params->functionSwitchResult) {
                $this->DoubleFestivalService = D('DoubleFestival', 'Service');
                $nodeId                      = isset($params->userInfo['node_id']) ? $params->userInfo['node_id'] : '';
                if ($nodeId) {
                    $finishedTask = $this->DoubleFestivalService->showIconData(
                            $params->userInfo['node_id']
                    );
                    if ($finishedTask) {
                        $taskName = isset($finishedTask['taskName']) ? $finishedTask['taskName'] : '';
                        $cssClass = isset($finishedTask['cssClass']) ? $finishedTask['cssClass'] : '';
                        $data     = array(
                                'taskName' => $taskName,
                                'cssClass' => $cssClass,
                        );
                        $params->setDoubleFestivalData($data);
                    }
                }
            }
        }
    }
}