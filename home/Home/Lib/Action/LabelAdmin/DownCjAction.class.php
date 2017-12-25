<?php
// 抽奖记录下载
class DownCjAction extends BaseAction {
    // 中奖名单下载
    public function winningExport() {
        $batchId = I('batch_id', null);
        if (is_null($batchId))
            $this->error('缺少参数');
        
        $edit_wh = array(
            'node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"), 
            'id' => $batchId);
        $nodeInfo = M('tmarketing_info')->where($edit_wh)->find();
        if (! $nodeInfo)
            $this->error('未查询到记录！');
        
        $fileName = $nodeInfo['name'] . '-' . date('Y-m-d') . '-中奖名单.csv';
        $fileName = iconv('utf-8', 'gbk', $fileName);
        
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        $start_num = 0;
        $page_count = 5000;
        $cj_title = "参与号,接收手机号,中奖时间,中奖状态,奖品等级名称,奖品名称,渠道名称\r\n";
        echo $cj_title = iconv('utf-8', 'gbk', $cj_title);
        for ($j = 1; $j < 100; $j ++) {
            $page = ($j - 1) * $page_count;
            $sql = "SELECT a.mobile,case when send_mobile = '13900000000' then '' else send_mobile end send_mobile,a.add_time,a.status,a.prize_level,c.name,d.batch_name,e.name as channel_name
					FROM tcj_trace a left join (select * from tcj_batch cjb where cjb.batch_id = '" .
                 $batchId . "') as b on a.rule_id =b.id
					left join tchannel e on a.channel_id= e.id
					left join tcj_cate c  on b.cj_cate_id= c.id
					left join tbatch_info d on b.b_id=d.id
					 WHERE a.batch_id='" . $batchId . "' AND
				     a.node_id ='" . $nodeInfo['node_id'] . "'
					 ORDER by a.status DESC LIMIT {$page},{$page_count}";
            
            $query = M()->query($sql);
            if (! $query)
                exit();
            foreach ($query as $v) {
                $cj_status = iconv('utf-8', 'gbk', 
                    $v['status'] == "1" ? '未中奖' : '中奖');
                $cj_cate = iconv('utf-8', 'gbk', $v['name']);
                $jp_name = iconv('utf-8', 'gbk', $v['batch_name']);
                $channel_name = iconv('utf-8', 'gbk', $v['channel_name']);
                echo "{$v['mobile']},{$v['send_mobile']}," .
                     date('Y-m-d H:i:s', strtotime($v['add_time'])) .
                     ",{$cj_status},{$cj_cate},{$jp_name},{$channel_name}\r\n";
            }
        }
    }
}