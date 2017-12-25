<?php

/**
 * 爱蒂宝
 */
abstract class AdibaoBaseBehavior extends Behavior {

    /**
     *
     * @var
     *
     */
    protected $params;

    private $aidibaoFlag = false;

    /**
     *
     * @var array
     */
    
    public function _initialize(&$params) {
        $this->params = &$params;
        $this->aidibaoFlag = $params['node_id'] == C('adb.node_id');
    }

    public function checkIsAdb() {
        return $this->aidibaoFlag;
    }

    public function bReturn($error, $msg, $data) {
        $this->params['return'] = array(
            'error' => $error, 
            'msg' => $msg, 
            'data' => $data);
    }
}