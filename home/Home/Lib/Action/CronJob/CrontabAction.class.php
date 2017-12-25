<?php

/**
 * 【数据中心】旺财首页三大业务数据自动取数和展示
 * 数据仓库每天2点前会会将旺财首页三大业务数据进行统计，并把结果（更新日期、签约商户数、营销活动数、个人参与数）放在FTP上， 用crontab定时执行
 * 旺财平台自动获取和展示
 */
class CrontabAction extends Action {

    public function dw_wc_data() {
        $time = date('Ymd', time());
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 
            'ftp://mdlftp:mdlftp!1@10.10.1.34/dw_wc_data_' . $time . '.txt');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $data = curl_exec($ch);
        curl_close($ch);
        
        $arr = explode(',', $data);
        $info = M('tsystem_param')->where(
            "param_name in ('HOME_PAGE_SHOP_NUMBER','HOME_PAGE_BATCH_NUMBER','HOME_PAGE_IN_NUMBER')")->select();
        if ($info[0]['param_value'] < $arr[0]) {
            $result1 = M('tsystem_param')->where(
                array(
                    'param_name' => 'HOME_PAGE_SHOP_NUMBER'))->save(
                array(
                    'param_value' => $arr[0]));
        }
        if ($info[1]['param_value'] < $arr[1]) {
            $result2 = M('tsystem_param')->where(
                "param_name = 'HOME_PAGE_BATCH_NUMBER'")->save(
                array(
                    'param_value' => $arr[1]));
        }
        if ($info[2]['param_value'] < $arr[2]) {
            $result3 = M('tsystem_param')->where(
                "param_name = 'HOME_PAGE_IN_NUMBER'")->save(
                array(
                    'param_value' => $arr[2]));
        }
        
        $msg = 'dw_wc_data_' . $time . '.txt :' . $data;
        if (isset($result2) && ! $result1) {
            $msg .= '签约商户数修改失败，';
        }
        if (isset($result1) && ! $result2) {
            $msg .= '营销活动数修改失败，';
        }
        if (isset($result3) && ! $result3) {
            $msg .= '个人参与数修改失败';
        }
        
        log_write('Crontab:' . $msg);
    }
}



