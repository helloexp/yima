<?php

// 翼惠宝首页
class YhbAction extends BaseAction {

    public $is_admin = false;

    public $is_yhb = true;

    public $catalog_array = array();

    const APPLY_PASSED = 2;

    public $merchant_id = null;

    public $admin_flag = false;

    public function _initialize() {
        $this->assign('is_yhb', $this->is_yhb ? 1 : 0);
        parent::_initialize();
        if (C('yhb.node_id') != $this->node_id) {
            header("location:index.php?g=Home&m=Index&a=index");
        }
        if (! $this->is_admin) {
            $this->merchant_id = M('tfb_yhb_node_info')->where(
                array(
                    'login_name' => $this->user_name))->getField('id');
        }
        $this->is_admin = $this->check_is_admin($this->node_id);
        $this->assign('is_admin', $this->is_admin);
        $queryList = M()->table("tfb_yhb_catalog")->select();
        $catalog_array = array();
        foreach ($queryList as $v) {
            $catalog_array[$v['id']] = $v['catalog_name'];
        }
        $this->assign('admin_flag', $this->is_admin);
        $this->catalog_array = $catalog_array;
        $this->assign('catalog_array', $catalog_array);
    }

    public function check_is_admin($node_id) {
        $userInfo = array_merge($this->userInfo, $this->nodeInfo);
        $user_id = $userInfo['user_id'];
        $tuserinfo = M("tuser_info")->where("user_id=" . $user_id)
            ->field("id")
            ->find();
        // var_dump($userinfo_id);
        // echo "<pre>";
        // var_dump($userInfo);
        // echo "</pre>";
        $info = M('tfb_yhb_node_info')->where(
            array(
                'user_info_id' => $tuserinfo['id']))->find();
        if (! $info) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 查看活动是否申请通过
     *
     * @param [type] $id [description]
     * @return [type] [description]
     */
    public function check_batch_passed($id) {
        $is = false;
        if (! is_numeric($id) || $id <= 0) {
            return $is;
        }
        $where['merchant_id'] = $id;
        $status = M('tfb_yhb_mconfig')->where($where)->getField('pub_status');
        if ($status == self::APPLY_PASSED) {
            $is = true;
        }
        return $is;
    }
    // 判断商户状态是否正常
    public function storeStatus($merchant_id) {
        $res = M("tfb_yhb_node_info")->where(
            array(
                'id' => $merchant_id))->find();
        if ($res['status'] != 1) {
            $this->error("商户状态不正常！");
        }
    }
}
