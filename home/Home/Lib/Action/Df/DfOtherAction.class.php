<?php

/**
 */
class DfOtherAction extends BaseAction {

    public $_authAccessMap = '*';

    public function _initialize() {
        parent::_initialize();
    }
    
    // 门店管理员设置相关门店
    public function bind_store() {
        $user_id = I('user_id', 0, 'intval');
        $map = array(
            'user_id' => $user_id, 
            'node_id' => $this->node_id, 
            'new_role_id' => C('df.store_admin_role_id'));
        $user_info = M('tuser_info')->where($map)->find();
        if (! $user_info) {
            $this->error('参数错误！');
        }
        
        // 查询当前所绑定的门店
        $model = M('tuser_param');
        $map2 = array(
            'param_name' => 'df_store_id', 
            'user_id' => $user_id);
        $info = $model->where($map2)->find();
        
        if (IS_POST) {
            $store_id = I('store_id', '', 'mysql_real_escape_string');
            $data = array(
                'param_value' => $store_id);
            if ($info) {
                $flag = $model->where($map2)->save($data);
            } else {
                $data = array_merge($map2, $data);
                $flag = $model->add($data);
            }
            
            if ($flag === false) {
                $this->erorr('门店设置失败！');
            }
            
            $this->success('门店设置成功！');
            exit();
        }
        
        $store_list = M('tstore_info')->where("node_id = '{$this->node_id}'")->getField(
            'store_id, store_name');
        $this->assign('user_info', $user_info);
        $this->assign('store_list', $store_list);
        $this->assign('cur_store_id', $info['param_value']);
        $this->display('DfOther/bind_store');
    }
}
