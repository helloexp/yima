<?php

class OrderAction extends BaseAction {

    public function index() {
        $user = D('User');
        $dao = M('TorderInfo');
        $arr = $dao->where(
            array(
                'torder_info.node_id' => $user->getUserInfo('node_id')))
            ->join(
            "left join tcharge_info on tcharge_info.charge_id=torder_info.charge_id")
            ->select();
        $this->assign('orderList', $arr);
        $this->display();
    }
}