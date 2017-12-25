<?php

/**
 * @author lwb
 * 20160422
 */
class SequenceModel extends Model{
    protected $tableName = 'tsequence';
    
    public function getCurrentSeq($name = '') {
        if (!$name) {
            return false;
        }
        $re = false;
        $res = M()->query("SELECT _nextval('$name') as reqid FROM DUAL");
        if ($res) {
            $re = $res[0]['reqid'];
        }
        return $re;
    }
    
    public function getCookieInfo() {
        $seq = $this->getCurrentSeq('guest_sequence');
        $seq = str_pad($seq, 7, '0', STR_PAD_LEFT);
        $seq = 'p' . $seq;
        return $seq;
    }
}