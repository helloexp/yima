<?php

class WorldCupAction extends Action {

    public function add() {
        $error = '';
        /* 数据验证 */
        $name = I('post.name');
        if (! check_str($name, 
            array(
                'null' => false, 
                'maxlen_cn' => '32'), $error)) {
            $this->error("姓名{$error}");
        }
        $phone = I('post.phone');
        if (! check_str($phone, 
            array(
                'null' => false, 
                'strtype' => 'mobile'), $error)) {
            $this->error("手机号码{$error}");
        }
        $email = I('post.email');
        if (! check_str($email, 
            array(
                'null' => false, 
                'strtype' => 'email'), $error)) {
            $this->error("邮箱{$error}");
        }
        $office = I('post.office');
        if (! check_str($office, 
            array(
                'null' => false, 
                'maxlen_cn' => '32'), $error)) {
            $this->error("职位{$error}");
        }
        $companyName = I('post.company_name');
        if (! check_str($companyName, 
            array(
                'null' => false, 
                'maxlen_cn' => '50'), $error)) {
            $this->error("企业名称{$error}");
        }
        $province = I('post.province');
        if (! check_str($province, array(
            'null' => false), $error)) {
            $this->error("请选择企业所在省");
        }
        $city = I('post.city');
        if (! check_str($city, array(
            'null' => false), $error)) {
            $this->error("请选择企业所在城市");
        }
        $town = I('post.town');
        if (! check_str($town, array(
            'null' => false), $error)) {
            $this->error("请选择企业所在区域");
        }
        $data = array(
            'name' => $name, 
            'email' => $email, 
            'office' => $office, 
            'phone' => $phone, 
            'company_name' => $companyName, 
            'province_code' => $province, 
            'city_code' => $city, 
            'town_code' => $town, 
            'add_time' => date('YmdHis'));
        $result = M('tworld_cup')->add($data);
        if (! $result)
            $this->error('系统出错,请您稍后再尝试报名');
        $this->success('感谢报名"旺财的世界杯"活动，我们的工作人员将会跟您联系确认');
    }
}