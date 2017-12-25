<?php
date_default_timezone_set("PRC");

class TphoneAddressModel extends BaseModel {

    /**
     * 最近手机端的收获地址
     *
     * @return array
     */
    public function getLastPhoneAddress() {
        $phoneAddressModel = M("tphone_address");
        $lastAddress = $phoneAddressModel->alias("a")->join(
            'tcity_code b on a.path = b.path')
            ->where(
            array(
                'a.user_phone' => $_SESSION['groupPhone']))
            ->field('a.*, b.province, b.city, b.town')
            ->order('a.last_use_time DESC')
            ->find();
        return $lastAddress;
    }

    /**
     * 全部手机端的收获地址
     *
     * @return array
     */
    public function getAllPhoneAddress() {
        $phoneAddressModel = M("tphone_address");
        $lastAddress = $phoneAddressModel->alias("a")->join(
            'tcity_code b on a.path = b.path')
            ->where(
            array(
                'a.user_phone' => $_SESSION['groupPhone']))
            ->field('a.*, b.province, b.city, b.town')
            ->order('a.last_use_time DESC')
            ->select();
        return $lastAddress;
    }

    /**
     * 指定手机端的收获地址 $id tphone_address id
     *
     * @return array
     */
    public function getDefinedPhoneAddress($id) {
        $phoneAddressModel = M("tphone_address");
        $lastAddress = $phoneAddressModel->alias("a")->join(
            'tcity_code b on a.path = b.path')
            ->where(
            array(
                'a.user_phone' => $_SESSION['groupPhone'], 
                'a.id' => $id))
            ->field('a.*, b.province, b.city, b.town')
            ->order('a.last_use_time DESC')
            ->find();
        return $lastAddress;
    }

    /**
     * 检验地址
     *
     * @return string
     */
    public function checkAddr($id) {
        $phoneAddressId = M('tphone_address')->where(
            array(
                'user_phone' => $_SESSION['groupPhone'], 
                'id' => $id))->getField('phone_no');
        return $phoneAddressId;
    }

    public function insert($user_phone, $user_name, $phone_no, $address, $path) {
        $model = M('tphone_address');
        $add_time = $last_use_time = date("YmdHis", time());
        $data['user_phone'] = $user_phone;
        $data['user_name'] = $user_name;
        $data['phone_no'] = $phone_no;
        $data['address'] = $address;
        $data['add_time'] = $add_time;
        $data['last_use_time'] = $last_use_time;
        $data['path'] = $path;
        
        $result = $model->add($data);
        return $result;
    }

    public function select($user_phone) {
        $model = M('tphone_address');
        $arr = $model->alias('a')
            ->join('left join tcity_code as c on a.path = c.path')
            ->where(array(
            'a.user_phone' => $user_phone))
            ->field(
            array(
                '*', 
                'a.id' => 'aid'))
            ->select();
        
        if ($arr) {
            foreach ($arr as $k => $v) {
                $arr[$k]['address'] = $arr[$k]['province'] . $arr[$k]['city'] .
                     $arr[$k]['address'];
            }
        }
        return $arr;
    }

    public function getAddress($con) {
        $model = M('tphone_address');
        $result = $model->where("id={$con}")->find();
        return $result;
    }

    public function getCity($path) {
        $model = M('tcity_code');
        $data = $model->where("path={$path}")->find();
        return $data;
    }

    public function update($id, $user_name, $phone_no, $address, $path) {
        $model = M('tphone_address');
        $data['user_name'] = $user_name;
        $data['phone_no'] = $phone_no;
        $data['address'] = $address;
        $data['path'] = $path;
        $result = $model->where("id=$id")->save($data);
        return $result;
    }

    public function del($id) {
        $model = M('tphone_address');
        $line = $model->where("id=$id")->delete();
        return $line;
    }
}
