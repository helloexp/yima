<?php

/**
 * @功能：北京光平 @更新时间: 2015/02/04 15:50
 */
class GpBaseAction extends BaseAction
{
    //public $_authAccessMap = '*';
    // 初始化
    public $isSuperAdmin = false;
    public function _initialize()
    {
        parent::_initialize();
        $this->isSuperAdmin = $this->new_role_id==2;
        session('new_role_id', $this->new_role_id);
        session('merchant_id', (int) $this->userInfo['merchant_id']);
        session('node_id', $this->userInfo['node_id']);
        session('user_id',$this->user_id);
        $this->assign('isSuperAdmin', $this->isSuperAdmin);
        $this->assign('store_admin_role_id', C('GpEye.store_admin_role_id'));
        $this->assign('store_user_role_id', C('GpEye.store_user_role_id'));
    }

    public function isSuperAdmin(){
        $this->success($this->new_role_id==2, null, true);
    }
}
