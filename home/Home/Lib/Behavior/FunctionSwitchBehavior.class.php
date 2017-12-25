<?php

/**
 *
 *
 * @author Jeff Liu<liuwy@imageco.com.cn> Class PayOrderTaskFinishBehavior
 */
class FunctionSwitchBehavior extends Behavior
{

    /**
     * @param BaseAction $params
     */
    public function run(&$params)
    {
        $functionSwitchResult = functionSwitchResult(MODULE_NAME);
        if ($functionSwitchResult === false) {
            $params->showErrorByErrno(-1074);
        }
    }
}