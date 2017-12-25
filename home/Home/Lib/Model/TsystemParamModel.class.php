<?php

/* 获取系统参数 */
class TsystemParamModel extends Model {

    static $_data = array();

    public function getValue($key) {
        $_data = self::$_data;
        if (! $_data) {
            $_data = $this->getField("param_name,param_value");
            self::$_data = $_data;
        }
        return isset($_data[$key]) ? $_data[$key] : null;
    }
}