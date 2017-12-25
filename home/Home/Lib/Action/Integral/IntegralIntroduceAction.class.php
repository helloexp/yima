<?php

class IntegralIntroduceAction extends BaseAction {

    public $_authAccessMap = '*';

    public function index() {
        if (get_wc_version($this->node_id) != 'v4') {
            $powers = M("tnode_info")->where(
                array(
                    'node_id' => $this->node_id))->getField('pay_module');
            if (empty($powers)) {
                $integralFlag = '1';
            } else {
                $powers = explode(",", $powers);
                if (! in_array("m4", $powers)) {
                    $integralFlag = '1';
                } else {
                    $integralFlag = '2';
                }
            }
        } else {
            $integralFlag = '2';
        }
        $this->assign("integralFlag", $integralFlag);
        $this->display('IntegralIntroduce/index');
    }
}