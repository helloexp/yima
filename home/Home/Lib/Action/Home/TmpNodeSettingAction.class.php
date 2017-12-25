<?php

class TmpNodeSettingAction extends BaseAction {

    public $default_param = array(
        'hide_footer' => array(
            'name' => '隐藏底部', 
            'default_value' => 0, 
            'memo' => '0否1是'), 
        'hide_footer_free_create_link' => array(
            'name' => '隐藏底部免费创建活动链接', 
            'default_value' => 0, 
            'memo' => '0否1是'));

    public function _initialize() {
        $userInfo = D('UserSess', 'Service')->getUserInfo();
        if ($userInfo['user_name'] == 'admin') {
            $this->_authAccessMap = '*';
        }
        parent::_initialize();
    }

    public function index() {
        $node_id = I('node_id');
        if ($node_id) {
            $info = M('tnode_info')->where(
                array(
                    'node_id' => $node_id))->find();
            $cfgData = unserialize($info['cfg_data']);
            
            $arr = array();
            foreach ($this->default_param as $k => $v) {
                $value = $v['default_value'];
                if (isset($cfgData[$k])) {
                    $value = $cfgData[$k];
                }
                $this->default_param[$k]['value'] = $value;
                $arr[$k] = $value;
            }
            $param = $this->default_param;
        } else {
            $param = '';
        }
        $this->assign('param', $param);
        $this->assign('node_id', $node_id);
        $this->display();
    }

    public function save() {
        $data = I('post.');
        $node_id = I('post.node_id');
        if (! $node_id) {
            redirect(U('index'));
        }
        $arr = array();
        foreach ($this->default_param as $k => $v) {
            $value = $v['value'];
            if (isset($data[$k])) {
                $value = $data[$k];
            }
            $arr[$k] = $value;
        }
        $arr = serialize($arr);
        $cfgData = array(
            'cfg_data' => $arr);
        $result = M('tnode_info')->where(
            array(
                'node_id' => $node_id))->save($cfgData);
        $this->success("更新成功");
    }
}