<?php

/*
 * To change this template, choose Tools | Templates and open the template in
 * the editor.
 */
class TmemberInfoModel extends Model {

    protected $tableName = 'tmember_info';

    public function getWhere($where) {
        return '';
    }
    /**
     *��ȡָ����Ա��Ϣ
     * @param $memberId string
     * @return mixed
     */
    public function memberInfo($memberId){
        $result = $this->where(array('id'=>$memberId))->find();
        return $result;
    }
}
?>
