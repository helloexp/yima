<?php

class GameAction extends BaseAction {

    public function _initialize() {
    }

    public function index() {
        // 标签
        $model = M('tlabel_info');
        $id = $this->_param('id');
        $map = array(
            'label_no' => $id);
        $result = $model->where($map)->find();
        
        if (! $result)
            die('game url error!');
        
        $game = M('tgame');
        $query_arr = $game->select();
        
        import('@.Vendor.GameClickStat');
        $opt = new ClickStat();
        $opt->updateStat($id);
        
        $this->assign('id', $id);
        $this->assign('result', $result);
        $this->assign('query_arr', $query_arr);
        $this->display(); // 输出模板
    }
}