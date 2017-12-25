<?php
// 选择商品活动
class SelectGoodsAction extends BaseAction {

    public function index() {
        // echo "这是选择商品";die;
        $map = array(
            "goods_type" => "6", 
            "node_id" => $this->node_id, 
            "status" => "0");
        $list = M("tgoods_info")->where($map)->select();
        $this->assign('list', $list);
        $this->display(); // 输出模板
    }
}