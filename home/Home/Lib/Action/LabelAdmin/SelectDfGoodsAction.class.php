<?php

// 选择旺财小店商品
class SelectDfGoodsAction extends BaseAction {

    public $_authAccessMap = '*';

    public function index() {
        $batch_type = I('batch_type', '1002');
        $search = array();
        $pageShow = '';
        $queryList = $this->_getBatchList($batch_type, $search, $pageShow);
        /*
         * $queryList = M('tecshop_goods_ex t')->field('t.m_id as
         * batch_id,a.batch_short_name as name,a.begin_time,a.end_time')
         * ->join('LEFT JOIN tbatch_info a ON a.id=t.b_id')
         * ->where(array('t.node_id'=>$this->node_id,'a.status'=>0)) ->select();
         */
        $id = I('id');
        if (! $id)
            $id = 1;
            /*
         * if(!empty($id)){ $mod = M('tchannel'); $query =
         * $mod->where(array('node_id'=>array('exp',"in
         * (".$this->nodeIn().")"),'id'=>$id))->find(); if(!$query)
         * $this->error('错误参数'); $this->assign('id',$id); }
         */
        
        $batch_name = $this->_getBatchType($batch_type);
        $this->assign('batch_name', $batch_name);
        $this->assign('batch_type', $batch_type);
        $this->assign('queryList', $queryList);
        $this->assign('page', $pageShow);
        $this->assign('id', $id);
        $this->display();
    }
    
    // 获取活动名称
    private function _getBatchType($batch_type) {
        $type_name = '';
        $type_name_arr = C('BATCH_TYPE_NAME');
        $type_name = $type_name_arr[$batch_type];
        if (! $type_name)
            $this->error('未知活动类型:' . $batch_type);
        return $type_name;
    }
    
    // 获取活动内容列表
    public function _getBatchList($batch_type, $search = array(), &$pageShow = null) {
        $search['m.node_id'] = $this->nodeId;
        $search['m.batch_type'] = $batch_type;
        $search['b.status'] = 0;
        if ($batch_type == '4') {
            $search['m.re_type'] = '0';
        }
        $totalNum = M()->table("tmarketing_info m")->join('tbatch_info b on b.m_id=m.id')
            ->where($search)
            ->count();
        import("ORG.Util.Page"); // 导入分页类
        $Page = new Page($totalNum, 10);
        $Page->setConfig('theme', 
            '%totalRow% %header% %nowPage%/%totalPage% 页 %first% %upPage% %linkPage% %downPage% %end%');
        $pageShow = $Page->show();
        $queryData = M()->table("tmarketing_info m")->field(
            "m.id batch_id,m.name,m.start_time,m.end_time")
            ->join('tbatch_info b on b.m_id=m.id')
            ->where($search)
            ->order("m.id desc")
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        // 获取活动类型
        $type_name = $this->_getBatchType($batch_type);
        if (! $queryData)
            $queryData = array();
        foreach ($queryData as &$v) {
            $v['info'] = $type_name . ' ＞ ' . $v['name'];
        }
        unset($v);
        
        return $queryData;
    }

    public function submit() {
        $is_true = getNodeTypePower($this->nodeType, '二维码渠道绑定');
        if ($is_true)
            $this->error('该功能未开通！');
        
        $channel_id = I('id');
        $batch_type = I('batchType');
        $batch_id = I('ckid');
        if (empty($channel_id) || empty($batch_type) || empty($batch_id)) {
            $this->error('错误参数！');
        }
        $model = M('tchannel');
        $wh = array(
            'node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"), 
            'id' => $channel_id);
        $data = array(
            'batch_type' => $batch_type, 
            'batch_id' => $batch_id);
        
        $channel_arr = $model->where($wh)->find();
        if (! $channel_arr)
            $this->error('错误参数！');
        
        if ($channel_arr['batch_id'] == $batch_id &&
             $channel_arr['batch_type'] == $batch_type)
            $this->error('当前已绑定该活动，请重新选择！');
            
            // 开启事物
        $tranDb = new Model();
        $tranDb->startTrans();
        
        if (! empty($channel_arr['batch_id']) &&
             ! empty($channel_arr['batch_type'])) {
            
            // 更新标签状态
            $modbc = M('tbatch_channel');
            $whbc = array(
                'node_id' => $this->nodeId, 
                'batch_type' => $channel_arr['batch_type'], 
                'batch_id' => $channel_arr['batch_id'], 
                'channel_id' => $channel_id);
            $query = $modbc->where($whbc)->save(
                array(
                    'status' => '2', 
                    'change_time' => Date('YmdHis')));
            if ($query === false) {
                $tranDb->rollback();
                $this->error('绑定失败！');
            }
        }
        $res = $model->where($wh)->save($data);
        if ($res === false) {
            $tranDb->rollback();
            $this->error('绑定失败！');
        }
        // 绑定
        
        $mod = M('tbatch_channel');
        // 是否已绑定过该活动
        $where = array(
            'node_id' => $this->nodeId, 
            'batch_type' => $batch_type, 
            'batch_id' => $batch_id, 
            'channel_id' => $channel_id);
        $is_bind = $mod->where($where)->find();
        if ($is_bind) {
            $query = $mod->where($where)->save(
                array(
                    'status' => '1'));
            if (! $query) {
                $tranDb->rollback();
                $this->error('绑定失败！');
            }
        } else {
            $data = array(
                'batch_type' => $batch_type, 
                'batch_id' => $batch_id, 
                'channel_id' => $channel_id, 
                'add_time' => Date('YmdHis'), 
                'node_id' => $this->nodeId);
            $query = $mod->add($data);
            if (! $query) {
                $tranDb->rollback();
                $this->error('绑定失败！');
            }
        }
        $tranDb->commit();
        node_log('绑定互动模块|渠道编号:' . $channel_id . '|活动编号:' . $batch_id);
        $this->success('绑定成功！');
    }
}
