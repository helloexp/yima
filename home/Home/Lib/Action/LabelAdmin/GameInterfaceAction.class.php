<?php
// 游戏抽奖
class GameInterfaceAction extends Action {

    public function index() {
        $mobile = I('mobile');
        $id = I('id');
        $game_id = I('game_id');
        if (empty($mobile) || empty($id) || empty($game_id)) {
            $resp_arr = array(
                "resp_id" => "9999", 
                "resp_msg" => urlencode("手机号,游戏号和id号不能为空！"));
            $json_str = json_encode($resp_arr);
            Log::write('手机号,游戏号和id号不能为空');
            echo urldecode($json_str);
            exit();
        } else {
            import('@.Vendor.GameChouJiang') or die('include file fail.');
            $choujiang = new GameChouJiang($id, $mobile);
            $resp = $choujiang->send_code();
            if ($resp === true) {
                $resp_arr = array(
                    "resp_id" => "0000", 
                    "resp_msg" => urlencode("抽奖成功！"));
                $json_str = json_encode($resp_arr);
                $log_msg = '游戏请求【手机号】:' . $mobile . '【标签id号】：' . $id .
                     '返回【resp_id】:0000 【resp_msg】:抽奖成功';
                Log::write(iconv('utf-8', 'gb2312', $log_msg));
                // php_log_info(iconv('utf-8','gb2312',$log_msg));
                echo urldecode($json_str);
                exit();
            } else {
                $resp_arr = array(
                    "resp_id" => "9999", 
                    "resp_msg" => urlencode("未中奖！"));
                $json_str = json_encode($resp_arr);
                $log_msg = '游戏请求【手机号】:' . $mobile . '【标签id号】：' . $id .
                     '返回【resp_id】:9999 【resp_msg】:未中奖！';
                // php_log_info(iconv('utf-8','gb2312',$log_msg));
                Log::write(iconv('utf-8', 'gb2312', $log_msg));
                echo urldecode($json_str);
                exit();
            }
        }
    }
}