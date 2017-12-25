<?php

class IndexCountAction extends Action {

    public function index() {
        $merchantrange = rand(30, 50);
        $activityrange = $merchantrange * rand(3, 4);
        $goodsrange = rand(15000, 25000);
        
        $index_count = M("index_count"); // 实例化对象
        $index_count->where("rec_id=1")->setInc('merchant', $merchantrange);
        $index_count->where("rec_id=1")->setInc('activity', $activityrange);
        $upok = $index_count->where("rec_id=1")->setInc('goods', $goodsrange);
        
        // echo "xxxxxxxxxxxxxxx";
    }
}