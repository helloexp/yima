<?php

/**
 * 全民营销service测试
 */
class IreturnCommissionAction extends BaseAction {

    public function _initialize() {
    }

    public function index() {
        return;
        $marketing_info_id = 3;
        $ReturnCommission = D('ReturnCommission', 'Service');
        // $resp_array = $ReturnCommission->effect_stat($marketing_info_id, 0,
        // 0);
        // $resp_array = $ReturnCommission->effect_stat($marketing_info_id, 1,
        // 0);
        // $resp_array = $ReturnCommission->effect_stat($marketing_info_id, 2,
        // 0);
        // $resp_array = $ReturnCommission->effect_stat($marketing_info_id, 3,
        // 0);
        // $resp_array = $ReturnCommission->effect_stat($marketing_info_id, 4,
        // 0);
        // $resp_array = $ReturnCommission->effect_stat($marketing_info_id, 0,
        // 1);
        // $resp_array = $ReturnCommission->effect_stat($marketing_info_id, 1,
        // 1);
        // $resp_array = $ReturnCommission->effect_stat($marketing_info_id, 2,
        // 1);
        // $resp_array = $ReturnCommission->effect_stat($marketing_info_id, 3,
        // 1);
        // $resp_array = $ReturnCommission->effect_stat($marketing_info_id, 4,
        // 1);
        // $resp_array =
        // $ReturnCommission->return_commission($marketing_info_id,
        // "13564896047", '0', '00001', 1, 5);
        // $resp_array =
        // $ReturnCommission->return_commission($marketing_info_id,
        // "13564896047", '0', '00001', 1, 5);
        $resp_array = $ReturnCommission->return_commission($marketing_info_id, 
            "13564896047", '1', '13564896047', '00001', 1, 5);
        // $resp_array =
        // $ReturnCommission->return_commission($marketing_info_id,
        // "13564896047", '2', '00001', 1, 5);
        // $resp_array =
        // $ReturnCommission->return_commission($marketing_info_id,
        // "13564896047", '3', '00001', 1, 5);
        // $resp_array =
        // $ReturnCommission->return_commission($marketing_info_id,
        // "13564896047", '4', '00001', 1, 5);
        // $resp_array = $ReturnCommission->notify_gen('20140902');
        if ($resp_array === true)
            echo "succ";
        else
            print_r($resp_array);
        // echo $resp_array;
        // $this->display();
    }

    public function notify_gen() {
        echo date("Ymd") . "执行notify_gen开始\n";
        $date = date("Ymd", strtotime("-1 day"));
        $ReturnCommission = D('ReturnCommission', 'Service');
        $resp_array = $ReturnCommission->notify_gen($date);
        echo "执行结果:" . $resp_array . "\n";
        echo date("Ymd") . "执行notify_gen结束\n";
        // $this->display();
    }

    public function finance_stat() {
        echo date("Ymd") . "执行finance_stat开始\n";
        $date = date("Ymd");
        $ReturnCommission = D('ReturnCommission', 'Service');
        $resp_array = $ReturnCommission->finance_stat($date);
        echo "执行结果:" . $resp_array . "\n";
        echo date("Ymd") . "执行finance_stat结束\n";
        // $this->display();
    }

    public function test() {
        $date = date("Ymd", strtotime("-1 day"));
        echo date("Ymd") . "执行notify_gen开始\n";
        echo "执行结果:" . $date . "\n";
        echo date("Ymd") . "执行notify_gen结束\n";
        // $this->display();
    }
}
