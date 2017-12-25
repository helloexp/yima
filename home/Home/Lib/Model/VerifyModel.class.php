<?php

/**
 *
 * @author lwb Time 20150917
 */
class VerifyModel extends Model {
    protected $tableName = '__NONE__';
    /**
     *
     * @param array $rule 验证规则数组
     * @param array $requestedValue 待验证的数据
     * @return array $req_data 通过验证的数据，未通过的抛异常
     */
    public function verifyReqData($rule, $requestedValue) {
        if (! is_array($rule)) {
            throw_exception('传入的参数不正确');
        }
        $req_data = array();
        foreach ($rule as $k => $v) {
            $value = $requestedValue[$k];
            if (! check_str($value, $v, $error)) {
                $msg = $v['name'] . $error;
                throw_exception($msg);
            }
            $req_data[$k] = $value;
        }
        return $req_data;
    }
}