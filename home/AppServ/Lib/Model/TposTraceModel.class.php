<?php

/* 终端流查询 */

class TposTrace extends Model {
	protected $tableName = '__NONE__';

    /**
     * 搜索终端流水
     */
    public function searchFlows($condition = array(), $start, $length) {
        if (empty($condition)) {
            return array();
        }
        $str = array();
        foreach ($condition as $key => $val) {
            if (is_array($val)) {
                $str[] = "({$key} between '{$val[0]}' and '{$val[1]}')";
            } else {
                $str[] = " " . $key . " = '" . $val . "' ";
            }
        }
        $str = implode(" and ", $str);
        // 不查询ret_code =3035的记录，这些记录表示需要金额和密码。
        $str .= ' and RET_CODE!=3035 ';

        $rs = $this->where($str)->limit(" {$start},{$length} ")->order(" id desc ")->select();

        return $rs;
    }

    public function countFlows($condition = array()) {
        if (empty($condition)) {
            return array();
        }
        $str = array();
        foreach ($condition as $key => $val) {
            if (is_array($val)) {
                $str[] = "({$key} between '{$val[0]}' and '{$val[1]}')";
            } else {
                $str[] = " " . $key . " = '" . $val . "' ";
            }
        }
        $str = implode(" and ", $str);
        // 不查询ret_code =3035的记录，这些记录表示需要金额和密码。
        $str .= ' and (RET_CODE!=3035 or ret_code is null)';

        $rs = $this->where($str)->count();

        return $rs;
    }
}

?>