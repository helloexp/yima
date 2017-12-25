<?php
// 渠道
class WhiteBlackAction extends BaseAction {
    
    // 首页
    public function index() {
        $infom = M('tnode_info');
        $cj_status = $infom->where(
            array(
                'node_id' => $this->node_id))->getField('cj_white_black');
        $model = M('tcj_white_blacklist');
        $phone_no = I('post.phone_no');
        $map = array(
            'node_id' => $this->node_id);
        if ($phone_no != '') {
            $map['phone_no'] = $phone_no;
        }
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = $model->where($map)->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        
        $list = $model->where($map)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        $this->assign('cj_status', $cj_status);
        $this->assign('query_list', $list);
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }
    
    // 改状态
    public function changeStatus() {
        $infom = M('tnode_info');
        $cj_status = $infom->where(
            array(
                'node_id' => $this->node_id))->getField('cj_white_black');
        
        $map = array(
            'node_id' => $this->node_id);
        if ($cj_status == '' || $cj_status == '0') {
            $data = array(
                'cj_white_black' => '1');
        } else {
            $data = array(
                'cj_white_black' => '0');
        }
        $query = $infom->where($map)->save($data);
        if ($query !== false)
            $this->success('操作成功', 
                array(
                    '返回白名单' => U('WhiteBlack/index')));
        else
            $this->error('操作失败！');
    }

    public function submit() {
        $resp_log_img = I('post.resp_log_img');
        $file_array = explode('-', $resp_log_img);
        $file_name = $file_array[0];
        if ($file_name == '')
            $this->resp_msg('未上传文件', '1');
            
            // 读取csv文件
        $resp_arr = array();
        $handle = fopen(APP_PATH . '/Upload/import/' . $file_name, 'r');
        
        $sum_arr = array();
        $row = 0;
        $exec = M('tcj_white_blacklist');
        $exec->startTrans();
        
        // 删除原有记录
        $dquery = $exec->where(
            array(
                'node_id' => $this->node_id))->delete();
        if ($dquery === false) {
            $exec->rollback();
            $this->resp_msg('系统正忙。。', '1');
        }
        while ($file = fgetcsv($handle, 50000, ",")) {
            $num = count($file);
            $row ++;
            for ($c = 0; $c < $num; $c ++) {
                $sum_arr[] = $file[$c];
                // 明细流水
                $data = array(
                    'phone_no' => $file[$c], 
                    'node_id' => $this->node_id);
                if (! is_numeric($file[$c]) || strlen($file[$c]) != '11') {
                    $exec->rollback();
                    $this->resp_msg('第' . $row . '行手机号错误:' . $file[$c], '1');
                }
                $query = $exec->add($data);
                if (! $query) {
                    $exec->rollback();
                    $this->resp_msg('导入失败！', '1');
                }
            }
        }
        fclose($handle);
        @unlink(APP_PATH . '/Upload/import/' . $file_name);
        $query = $exec->commit();
        if ($query) {
            $this->resp_msg('操作成功!');
        } else
            $this->resp_msg('系统错误！', '1');
    }
    // 导入文件
    public function import() {
        $this->display();
    }

    public function down() {
        header("Content-Type: application/vnd.ms-excel; charset=GB2312");
        header("Content-Disposition: attachment;filename=example.csv ");
        
        $rs = array(
            array(
                '13900000000'), 
            array(
                '13900000000'), 
            array(
                '13900000000'), 
            array(
                '13900000000'));
        
        $str = '';
        foreach ($rs as $row) {
            $str_arr = array();
            foreach ($row as $column) {
                $str_arr[] = str_replace('"', '', $column);
            }
            $str .= implode(',', $str_arr) . PHP_EOL;
        }
        echo $str;
        exit();
    }

    public function resp_msg($msg, $err = '', $seq = '') {
        if ($err != '') {
            $this->assign('error', $err);
        } elseif ($seq != '') {
            $this->assign('seq', '1');
        }
        
        $this->assign('batch_no', $this->batchNo);
        $this->assign('message', $msg);
        $this->display('respMsg');
    }
}