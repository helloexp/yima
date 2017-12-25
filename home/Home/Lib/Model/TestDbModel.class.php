<?php

/**
 * 渠道相关 model
 *
 * @author : Jeff Liu<liuwy@imageco.com.cn> Date: 2015/09/08
 */
class TestDbModel extends BaseModel {

    public function testTran() {
        $this->startTrans();
        
        $this->table('tmarketing_info')
            ->lock(true)
            ->where('id > 8619')
            ->select();
        usleep(2000000); // 睡眠2s
        
        $this->commit();
    }

    public function testQuery() {
        $this->table('tmarketing_info')
            ->where('id > 8620')
            ->select();
    }

    public function testExec() {
        $this->table('tmarketing_info')
            ->where('id = 8621')
            ->save(array(
            'start_time' => 20151001000123));
    }

    public function getGoodsId() {
        $r = M()->query("SELECT _nextval('goods_id') as goods_id FROM DUAL");
        if (empty($r) || ! isset($r[0]['goods_id'])) {
            var_export(M()->getDbError());
            exit();
        }
        return $r[0]['goods_id'];
    }
}