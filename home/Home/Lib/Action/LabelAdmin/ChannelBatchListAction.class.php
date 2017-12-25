<?php
// 历史活动
class ChannelBatchListAction extends BaseAction {

    public function index() {
        $id = I('id');
        $model = M('tbatch_channel');
        $map = array(
            'node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"), 
            'channel_id' => $id);
        
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = $model->where($map)->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $model->where($map)
            ->order('id DESC')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        
        if ($list) {
            foreach ($list as $k => $v) {
                // 如果是绑定的外链的活动
                if ($v['batch_id'] == 0 && $v['batch_type'] == 0) {
                    $goUrl = D('Channel')->getChannelField(
                        array(
                            'id' => $v['channel_id'], 
                            'node_id' => $this->node_id), 
                        array(
                            'go_url'));
                    $list[$k]['name'] = $goUrl;
                    $list[$k]['ck_count'] = $v['click_count'];
                } else {
                    $where = array(
                        'node_id' => $v['node_id'], 
                        'id' => $v['batch_id'], 
                        'batch_type' => $v['batch_type']);
                    $marketInfo = D('MarketInfo')->getSingleField($where, 
                        array(
                            'name', 
                            'click_count', 
                            'send_count'));
                    $list[$k]['name'] = $marketInfo['name'];
                    $list[$k]['ck_count'] = $marketInfo['click_count'];
                    $list[$k]['sd_count'] = $marketInfo['send_count'];
                }
            }
        }
        
        $arr = C('BATCH_TYPE_NAME');
        $this->assign('query_list', $list);
        $this->assign('batch_id', $batch_id);
        $this->assign('statusArr', $statusArr);
        $this->assign('channel_type_arr', $channel_type_arr);
        $this->assign('arr', $arr);
        $this->assign('id', $id);
        // dump($list);die;
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }

    /**
     * 功能：下载该渠道的活动数据 参数：渠道ID 说明：下载文件为csv文件
     */
    public function download() {
        $id = I('id');
        if (is_null($id))
            $this->error('缺少参数');
        $fileName = date('YmdHis') . '.csv';
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        $title = "活动名称,活动类型,绑定及解绑时间,访问量,中奖数\r\n";
        echo $title = iconv('utf-8', 'gbk', $title);
        
        $model = M('tbatch_channel');
        $map = array(
            'node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"), 
            'channel_id' => $id);
        $list = $model->where($map)
            ->order('id DESC')
            ->select();
        if ($list) {
            foreach ($list as $k => $v) {
                $mod = M('tmarketing_info');
                $query = $mod->where(
                    array(
                        'node_id' => $v['node_id'], 
                        'id' => $v['batch_id'], 
                        'batch_type' => $v['batch_type']))->find();
                $list[$k]['info'] = $query;
            }
        }
        $arr = C('BATCH_TYPE_NAME');
        foreach ($list as $v) {
            $name = iconv('utf-8', 'gbk', $v['info']['name']);
            $type = iconv('utf-8', 'gbk', $arr[$v['batch_type']]);
            echo "{$name}" . ",";
            echo "{$type}" . ",";
            echo date('Y-m-d', strtotime($v['add_time'])) . "--";
            echo $v['change_time'] == '' ? '' : date('Y-m-d', 
                strtotime($v['change_time']));
            echo ",";
            echo $v['click_count'] . "/";
            echo round($v['click_count'] / $v['info']['click_count'], 2) * 100;
            echo "%,";
            echo $v['send_count'] . "/";
            echo round($v['send_count'] / $v['info']['send_count'], 2) * 100;
            echo "%,";
            echo "\r\n";
        }
    }
    
    // 拍吗时间下载
    public function codeTimeDown() {
        $channelId = I('id');
        $map = array(
            'a.channel_id' => $channelId, 
            'a.node_id' => $this->nodeId);
        $mapcount = M()->table("tbatch_trace_his a")->where($map)->count();
        $mapcount2 = M()->table("tbatch_trace a")->where($map)->count();
        if ($mapcount == 0 && $mapcount2 == 0)
            $this->error('未查询到记录！');
        $fileName = '拍码时间-截止到.csv';
        $fileName = iconv('utf-8', 'gb2312', $fileName);
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        $start_num = 0;
        $page_count = 5000;
        $cj_title = "活动名称,拍码时间,IP\r\n";
        echo $cj_title = iconv('utf-8', 'gbk', $cj_title);
        for ($j = 1; $j < 100; $j ++) {
            $page = ($j - 1) * $page_count;
            $list = M()->table('tbatch_trace a')
                ->field("b.name,a.ip,a.add_time")
                ->join('tmarketing_info b ON a.batch_id = b.id')
                ->where($map)
                ->order('a.batch_id desc,a.add_time desc')
                ->limit($page, $page_count)
                ->select();
            if (! $list)
                exit();
            foreach ($list as $v) {
                $name = iconv('utf-8', 'gbk', $v['name']);
                echo "{$name}," . dateformat($v['add_time']) . ",{$v['ip']}\r\n";
            }
        }
        for ($j = 1; $j < 100; $j ++) {
            $page = ($j - 1) * $page_count;
            $list = M()->table('tbatch_trace_his a')
                ->field("b.name,a.ip,a.add_time")
                ->join('tmarketing_info b ON a.batch_id = b.id')
                ->where($map)
                ->order('a.batch_id desc,a.add_time desc')
                ->limit($page, $page_count)
                ->select();
            if (! $list)
                exit();
            foreach ($list as $v) {
                $name = iconv('utf-8', 'gbk', $v['name']);
                echo "{$name}," . dateformat($v['add_time']) . ",{$v['ip']}\r\n";
            }
        }
    }
    
    // 活动数据
    public function batchStat() {
        $id = I('id');
        $mId = I('mId');
        $row = M('tchannel')->where(
            array(
                'node_id' => array(
                    'exp', 
                    "in (" . $this->nodeIn() . ")"), 
                'id' => $id))
            ->find();
        // 外链的batch_type和mId为0
        if ($mId != 0) {
            $batchType = M('tmarketing_info')->where(
                array(
                    'node_id' => $this->node_id, 
                    'id' => $mId))->getField('batch_type');
        } else {
            $batchType = 0;
        }
        
        $model = M('tdaystat');
        $map = array(
            'batch_type' => $batchType, 
            'batch_id' => $mId, 
            'channel_id' => $row['id']);
        
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = $model->where($map)->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $model->where($map)
            ->order('id DESC')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        $this->assign('list', $list);
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }
    
    // 渠道活动分析图表
    public function Chart() {
        $id = I('id');
        $model = M('tbatch_channel');
        $map = array(
            'node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"), 
            'channel_id' => $id);
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = $model->where($map)->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $model->where($map)
            ->order('id DESC')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        
        if ($list) {
            foreach ($list as $k => $v) {
                $mod = M('tmarketing_info');
                $query = $mod->where(
                    array(
                        'node_id' => $v['node_id'], 
                        'id' => $v['batch_id'], 
                        'batch_type' => $v['batch_type']))->getField('name');
                $list[$k]['name'] = $query;
            }
        }
        $batch_name = M('tchannel')->where(
            array(
                'node_id' => array(
                    'exp', 
                    "in (" . $this->nodeIn() . ")"), 
                'id' => $id))
            ->getField('name');
        $this->assign('batch_id', $batch_id);
        $this->assign('batch_type', $batch_type);
        $this->assign('batch_name', $batch_name);
        $this->assign('channel_type_arr', $channel_type_arr);
        $this->assign('query_list', $list);
        $this->assign('statusArr', $statusArr);
        
        $this->display(); // 输出模板
    }
}