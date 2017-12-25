<?php

class WhfhAction extends MyBaseAction {

    public function index() {
        // session('cjUserInfo',array('phone_no'=>'13764737738'));
        $batch_id = C('whfh');
        if ($this->batch_id != $batch_id)
            exit();
            
            // 参与人数
            // $count = M('tfb_whfh_stat')->count();
        $count = M('tmarketing_info')->where(
            array(
                'id' => $batch_id))->getField('click_count');
        
        // 排名前四
        $search = M('tfb_whfh_stat')->order('total_count desc')
            ->limit(5)
            ->select();
        
        // 我的排名
        $userInfo = session('cjUserInfo');
        
        if ($userInfo) {
            $query = M('tfb_whfh_stat')->where(
                array(
                    'phone_no' => $userInfo['phone_no']))->find();
            
            if ($query) {
                $wh = array(
                    'total_count' => array(
                        'exp', 
                        '>' . $query['total_count']));
                
                $mycount1 = M('tfb_whfh_stat')->where($wh)->count();
                $wh['total_count'] = $query['total_count'];
                $wh['id'] = array(
                    'exp', 
                    '<=' . $query['id']);
                $mycount2 = M('tfb_whfh_stat')->where($wh)->count();
                $mycount = $mycount1 + $mycount2;
            }
        }
        
        $batchModel = M('tmarketing_info');
        $row = $batchModel->where(
            array(
                'id' => $this->batch_id))->find();
        
        $this->assign('count', $count);
        $this->assign('query', $query);
        $this->assign('mycount', $mycount);
        $this->assign('search', $search);
        
        $this->assign('id', $this->id);
        $this->assign('batch_type', $this->batch_type);
        $this->assign('batch_id', $this->batch_id);
        $this->assign('row', $row);
        $this->display();
    }
}