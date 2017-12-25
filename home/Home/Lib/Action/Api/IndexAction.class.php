<?php

class IndexAction extends Action {

    public $AppName;

    public $key = '123456789';

    public $mac;

    public $data = array();

    public function _initialize() {
        $this->AppName = I("landname");
        $data = I("post.");
        // print_r($data);
        $keys = ksort($data);
        $this->mac = md5($this->key . http_build_query($keys) . $this->key);
    }

    public function index() {
        $this->AppName = I("landname");
        $user = ksort(I("post."));
        // print_r($user);
        // exit;
        $macs = md5($this->key . http_build_query($user) . $this->key);
        if ($macs == $this->mac) {
            switch ($this->AppName) {
                case "xxqd":
                    $email = I("email");
                    $model = M("tnode_info");
                    $offtype = I("offtype");
                    $landname = I("landname");
                    $re1 = $model->where(
                        array(
                            'contact_eml' => $email))->select();
                    // echo M()->_sql();
                    if ($re1 == '') {
                        $data['result']['info'] = '该用户不是旺财用户';
                        $data['result']['success'] = 0;
                    } else {
                        $data['result']['info'] = '该用户是旺财用户';
                        $data['result']['success'] = 1;
                        $data['result'] = $model->field(
                            "node_name,contact_name,contact_phone,contact_eml,node_citycode,trade_type")
                            ->where(
                            array(
                                'contact_eml' => $email))
                            ->find();
                        // echo M()->_sql();
                        $data['result']['offtype'] = $offtype;
                        $data['result']['landname'] = $landname;
                        // print_r($data['result']);
                    }
                    break;
                case "offlineH5":
                    $email = I("email");
                    $model = M("tnode_info");
                    $offtype = I("offtype");
                    $landname = I("landname");
                    $re1 = $model->where(
                        array(
                            'contact_eml' => $email))->select();
                    // echo M()->_sql();
                    if ($re1 == '') {
                        $data['result']['info'] = '该用户不是旺财用户';
                        $data['result']['success'] = 0;
                    } else {
                        $data['result']['info'] = '该用户是旺财用户';
                        $data['result']['success'] = 1;
                        $data['result'] = $model->field(
                            "node_name,contact_name,contact_phone,contact_eml,node_citycode,trade_type")
                            ->where(
                            array(
                                'contact_eml' => $email))
                            ->find();
                        // echo M()->_sql();
                        $data['result']['offtype'] = $offtype;
                        $data['result']['landname'] = $landname;
                        // print_r($data['result']);
                    }
                    break;
            }
        } else {
            $data['result']['info'] = '接口错误';
            $data['result']['success'] = 2;
        }
        $tem = json_encode($data['result']);
        echo $tem;
        // return $data['result'];
    }
}