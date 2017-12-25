<?php
// 更新点击数
class ClickStat {
    
    // 获取标签详情
    public function updateStat($id) {
        $now = Date('Ymd');
        $model = M('tlabel_info');
        $map = array(
            'label_no' => $id);
        $result = $model->where($map)->find();
        if (! $result)
            return false;
            
            // 开启事物
        $tranDb = new Model();
        $tranDb->startTrans();
        $query = $model->where($map)->setInc('click_count', 1);
        
        // 更新日统计
        $dmodel = M('tgame_daystat');
        $smap = array(
            'label_id' => $id, 
            'day' => $now);
        $row = $dmodel->where($smap)->find();
        if ($row) {
            $squery = $dmodel->where($smap)->setInc('click_count', 1);
        } else {
            $smap['click_count'] = '1';
            $squery = $dmodel->add($smap);
        }
        
        if ($query && $squery) {
            $tranDb->commit();
        } else {
            $tranDb->rollback();
        }
    }
}