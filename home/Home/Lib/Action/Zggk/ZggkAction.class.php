<?php

// 广西石油首页
class ZggkAction extends BaseAction {

    public $is_admin = false;

    public $catalog_array = array();

    public function _initialize() {
        parent::_initialize();
        if (C('zggk.node_id') != $this->node_id) {
            header("location:index.php?g=Home&m=Index&a=index");
        }
//        $queryList = M()->table("tfb_cnpcgx_catalog")->select();
//        $catalog_array = array();
//        foreach ($queryList as $v) {
//            $catalog_array[$v['id']] = $v['catalog_name'];
//        }
//        $this->catalog_array = $catalog_array;
        $this->_integralName();
//        $this->assign('catalog_array', $catalog_array);
    }
    // 商城名称
    public function _integralName() {
        $integral_name = M("tintegral_node_config")->where(
            array(
                'node_id' => $this->node_id))->getField('integral_name');
        if ($integral_name) {
            L('INTEGRAL_NAME', $integral_name);
        } else {
            L('INTEGRAL_NAME', '积分');
        }
    }
}
