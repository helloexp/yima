<?php

class TposDayCountModel extends Model {
	protected $tableName = '__NONE__';
    /**
     * 写日结
     *
     * @param string $posId     终端号
     * @param int    $transType 交易类型 0：验证 ，1：撤销，2：发码
     * @param float  $amt
     *
     * @return boolean
     */
    public function writeStat(
            $posId,
            $transType,
            $amt,
            $batchNo,
            $batchDesc,
            $transDate = '',
            $nodeId = '',
            $num = 1
    ) {
        $set = "";
        if ($transType == "0") {
            $set = "VERIFY_NUM = VERIFY_NUM+1 , VERIFY_AMT = VERIFY_AMT+" . $amt . ", NODE_ID ='{$nodeId}' ";
        } else if ($transType == "1") {
            $set = "CANCEL_NUM = CANCEL_NUM+1 , CANCEL_AMT = CANCEL_AMT+" . $amt . " ,NODE_ID ='{$nodeId}' ";
        } else if ($transType == "2") {
            $set = "SEND_NUM = SEND_NUM+" . $num . " , SEND_AMT = SEND_AMT+" . $amt . ", NODE_ID ='{$nodeId}' ";
        } else {
            return false;
        }
        $transDate = empty($transDate) ? date('Y-m-d') : $transDate;
        $sql       = "insert into %TABLE% set POS_ID ='{$posId}', TRANS_DATE='{$transDate}', BATCH_NO ='{$batchNo}' , {$set} ON DUPLICATE KEY UPDATE {$set}";
        $rs        = $this->execute($sql, true);

        return $rs;
    }

    /**
     * 读取日统计报表
     *
     * @param unknown_type $posId
     * @param unknown_type $transDate
     */
    public function searchStats($posId, $beginTime, $endTime, $start, $length) {
        $rs = $this->where("POS_ID='{$posId}' and   TRANS_DATE between '{$beginTime}' and '{$endTime}'")->limit("{$start},{$length}")->order(" id desc")->select();

        return $rs;
    }

    public function countStats($posId, $beginTime, $endTime) {
        $rs = $this->where("POS_ID='{$posId}' and   TRANS_DATE between '{$beginTime}' and '{$endTime}'")->count();

        return $rs;
    }

    /**
     * 获取业务统计表ID字段值
     *
     * @param unknown_type $posId      终端号
     * @param unknown_type $trans_date 交易时间
     * @param unknown_type $batch_no   活动号
     */
    public function getStatId($posId, $trans_date, $batch_no) {
        return $this->where("POS_ID='{$posId}' and TRANS_DATE ='{$trans_date}' and BATCH_NO ='{$batch_no}'")->field('ID')->getField('ID');
    }
}

?>