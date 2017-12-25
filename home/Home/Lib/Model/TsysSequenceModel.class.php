<?php

class TsysSequenceModel extends Model {

    public function getNextSeq($seq_name) {
        $sql = "UPDATE %TABLE% SET `cur_value` = LAST_INSERT_ID(
		  CASE 
		  WHEN `cur_value` >= `max_value` THEN `min_value`
		  ELSE
		  `cur_value`+ 1
		  END )
		  WHERE `seq_name`='$seq_name'";
        $result = $this->execute($sql, true);
        return $result ? $this->getLastInsID() : false;
    }
}