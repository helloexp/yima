<?php

/**
 * Created by PhpStorm. User: Administrator Date: 2015/8/18 Time: 15:47
 */
class TprocLogModel extends Model {

    /**
     * 获得log数据返回邮件文本
     *
     * @param int $time 传入的时间(昨天)
     * @return string 邮件文本
     */
    public function getDaydate($time) {
        global $strAll;
        $data = M("tauto_proc_log"); // tauto_proc_log表
        $dayData = $data->where("LEFT (proc_time, 8) = $time")->find(); // 当日是否有数据
        if (is_null($dayData)) {
            $content = "数据库中没有找到" . $time . "日的数据,请核查";
        } else {
            $dayData = $data->where(
                "LEFT (proc_time, 8) = $time AND proc_flag = 0")->getField(
                "id,proc_name,LEFT(proc_time,8) as time"); // 当日log日志中未成功的信息
            foreach ($dayData as $k => $v) {
                
                $str = "id:" . $v['id'] . "\t proc_name:" . $v['proc_name'] .
                     "</br>";
                $strAll = $strAll . $str;
            }
            $content = $strAll;
        }
        return $content;
    }
}