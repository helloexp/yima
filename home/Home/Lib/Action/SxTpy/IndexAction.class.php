<?php

/**
 * 【非标V1.0.0_C14】山西太平洋保险
 */
class IndexAction extends BaseAction {
    // public $_authAccessMap = '*';
    private $status_arr = array(
        '0' => '未使用', 
        '1' => '已使用');

    public function _initialize() {
        parent::_initialize();
        $this->assign('status_arr', $this->status_arr);
    }

    /**
     * 山西【非标】山西太平洋保险V1.0
     */
    public function index() {
        $phone_no = I('phone_no');
        $begin_time = I('begin_time');
        $end_time = I('end_time');
        $list = array();
        $page = '';
        
        if ($phone_no != '') {
            $map = array(
                'a.phone_no' => $phone_no);
            $time_arr = array();
            if ($begin_time != '') {
                $time_arr[] = array(
                    'egt', 
                    $begin_time . '000000');
            }
            if ($end_time != '') {
                $time_arr[] = array(
                    'elt', 
                    $end_time . '235959');
            }
            if ($time_arr) {
                $time_arr[] = 'and';
                $map['a.add_time'] = $time_arr;
            }
            $count = M()->table('tfb_sxtpy_trace a')
                ->where($map)
                ->count();
            
            import("ORG.Util.Page");
            $p = new Page($count, 10);
            $page = $p->show();
            
            $list = M()->table('tfb_sxtpy_trace a')
                ->where($map)
                ->limit($p->firstRow . ',' . $p->listRows)
                ->order('status asc, a.id desc')
                ->field('a.*')
                ->select();
        }
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->display();
    }

    public function my_his() {
        $this->assign('page_title', '使用明细（个人）');
        $this->_history(0);
    }

    public function his() {
        $this->assign('page_title', '使用明细（全部）');
        $this->_history(1);
    }

    /**
     * 核销历史
     */
    public function _history($type) {
        $mid = I('mid', 0, 'intval');
        $begin_time = I('begin_time');
        $end_time = I('end_time');
        
        $map = array(
            'a.status' => '1');
        
        if ($mid != 0)
            $map['a.m_id'] = $mid;
        
        $time_arr = array();
        if ($begin_time != '') {
            $time_arr[] = array(
                'egt', 
                $begin_time . '000000');
        }
        if ($end_time != '') {
            $time_arr[] = array(
                'elt', 
                $end_time . '235959');
        }
        if ($time_arr) {
            $time_arr[] = 'and';
            $map['a.use_time'] = $time_arr;
        }
        
        if ($type == 0) {
            $roleInfo = M('tuser_info')->where(
                "user_id={$this->userId} AND node_id='{$this->nodeId}'")
                ->field('role_id,new_role_id')
                ->find();
            $map['a.op_user_id'] = $this->user_id;
        }
        
        $count = M()->table('tfb_sxtpy_trace a')
            ->where($map)
            ->count();
        
        // 下载
        if (I('download_flag', 0, 'intval') == 1) {
            if ($count == 0)
                $this->error('未查询到记录！');
            $fileName = '使用明细.csv';
            $fileName = iconv('utf-8', 'gbk', $fileName);
            
            ob_end_clean();
            header("Content-type: text/plain");
            header("Accept-Ranges: bytes");
            header("Content-Disposition: attachment; filename=" . $fileName);
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Pragma: no-cache");
            header("Expires: 0");
            $start_num = 0;
            $page_count = 5000;
            $cj_title = "手机号,活动名称,奖品名称,中奖时间,奖品有效期,保单号,车牌号,使用时间,操作员\r\n";
            echo $cj_title = iconv('utf-8', 'gbk', $cj_title);
            for ($j = 1; $j < 100; $j ++) {
                $page = ($j - 1) * $page_count;
                $list = M()->table('tfb_sxtpy_trace a')
                    ->join('tuser_info u on a.op_user_id = u.user_id')
                    ->where($map)
                    ->limit($page . ',' . $page_count)
                    ->order('a.status asc, a.id desc')
                    ->field('a.*, u.true_name')
                    ->select();
                if (! $list)
                    exit();
                foreach ($list as $v) {
                    $data = array(
                        $v['phone_no'], 
                        $v['mname'], 
                        $v['bname'], 
                        dateformat($v['add_time'], 'Y-m-d H:i:s'), 
                        dateformat($v['va_end_time'], 'Y-m-d H:i:s'), 
                        default_nvl($v['insur_number'], '--'), 
                        default_nvl($v['car_number'], '--'), 
                        dateformat($v['use_time'], 'Y-m-d H:i:s'), 
                        $v['true_name'] . "\r\n");
                    
                    iconv_arr('utf-8', 'gbk', $data);
                    echo implode(",", $data);
                }
            }
            
            exit();
        }
        
        import("ORG.Util.Page");
        $p = new Page($count, 10);
        $page = $p->show();
        
        $list = M()->table('tfb_sxtpy_trace a')
            ->join('tuser_info u on a.op_user_id = u.user_id')
            ->where($map)
            ->limit($p->firstRow . ',' . $p->listRows)
            ->order('a.status asc, a.id desc')
            ->field('a.*, u.true_name')
            ->select();
        
        $this->assign('list', $list);
        $this->assign('page', $page);
        
        $map = array(
            'node_id' => $this->node_id, 
            'batch_type' => array(
                'in', 
                '2,3,10,20'));
        $batch_list = M('tmarketing_info')->where($map)->getField('id, name');
        $this->assign('batch_list', $batch_list);
        $this->display('history');
    }

    public function deal() {
        $trace_id = I('trace_id', 0, 'intval');
        $order_type = I('order_type', 0, 'intval');
        $order_no = I('order_no', 0, 'trim,mysql_real_escape_string');
        
        if ($trace_id === 0)
            $this->error('参数错误！');
            
            // 车牌
        if ($order_type == 0) {
            if ($order_no == '')
                $this->error('车牌号不能为空！');
            
            $order_no = strtoupper($order_no);
            /*
             * if
             * (preg_match('/^(WJ|([\x{4e00}-\x{9fa5}{1}]))[A-Z]{1}[A-Z_0-9]{5}$/u',
             * $order_no) == 0) { $this->error('车牌号格式不正确！'); }
             */
        }  // 保单号
else {
            if ($order_no == '')
                $this->error('保单号不能为空！');
        }
        
        $model = M('tfb_sxtpy_trace');
        $map = array(
            'id' => $trace_id);
        $traceInfo = $model->where($map)->find();
        if (! $traceInfo)
            $this->error('参数错误！');
        
        if ($traceInfo['status'] == '1')
            $this->error('已被验证！');
        
        if ($traceInfo['va_begin_time'] != '' &&
             $traceInfo['va_begin_time'] > date('YmdHis'))
            $this->error('未到开始使用时间！');
        
        if ($traceInfo['va_end_time'] < date('YmdHis'))
            $this->error('凭证已过期，不能使用！');
        
        $data = array(
            'use_time' => date('YmdHis'), 
            'op_user_id' => $this->user_id, 
            'car_number' => $order_type ? '' : $order_no, 
            'insur_number' => $order_type ? $order_no : '', 
            'status' => 1);
        
        $flag = $model->where($map)->save($data);
        if ($flag === false) {
            php_log('山西平安非标处理,核销失败！' . M()->_sql());
            $this->error('核销失败！请重试');
        }
        
        $this->success('核销成功！');
    }

    public function import() {
        if ($this->node_id != C('sxtpybx.node_id'))
            $this->error('非法访问');
        
        $mid = I('batch_id', 0, 'intval');
        if ($mid === 0)
            $this->error('参数错误！');
        
        $map = array(
            'node_id' => $this->node_id, 
            'id' => $mid);
        $batchInfo = M('tmarketing_info')->where($map)->find();
        if (! $batchInfo)
            $this->error('参数错误！');
        
        $map = array(
            'a.batch_id' => $mid, 
            'a.b_id' => array(
                'exp', 
                '=b.id'), 
            'a.status' => '1');
        $list = M()->table('tcj_batch a, tbatch_info b')
            ->where($map)
            ->field('b.id, b.batch_name')
            ->select();
        if (! $list) {
            $this->show(
                '<h1>该活动未配置奖品，无法批量导入！</h1><br/><a href="' . U(
                    'LabelAdmin/CjSet/index', 
                    array(
                        'batch_id' => $mid)) . '" target="_top">现在去设置</a>');
            exit();
        }
        
        $num = M('tfb_sxtpy_import')->where("mid={$mid}")
            ->field('ifnull(sum(num),0) num')
            ->find();
        $this->assign('num', $num['num']);
        $this->assign('list', $list);
        $this->assign('mid', $mid);
        $this->display();
    }

    public function import_submit() {
        if (! IS_POST)
            $this->error('非法访问！');
        
        $mid = I('mid', 0, 'intval');
        $bid = I('bid', 0, 'intval');
        
        if ($mid === 0 || $bid === 0)
            $this->error('参数错误！');
        $map = array(
            'node_id' => $this->node_id, 
            'id' => $mid);
        $mInfo = M('tmarketing_info')->where($map)->find();
        if (! $mInfo)
            $this->error('参数错误！');
        
        $map = array(
            'a.batch_id' => $mid, 
            'a.b_id' => array(
                'exp', 
                '=b.id'), 
            'a.status' => '1', 
            'b.id' => $bid);
        $bInfo = M()->table('tcj_batch a, tbatch_info b')
            ->where($map)
            ->field('b.*')
            ->find();
        if (! $bInfo)
            $this->error('参数错误！');
        
        set_time_limit(0);
        $maxSize = 1024 * 1024 * 100;
        $allowExts = array(
            'csv', 
            'txt');
        $m_ = M('tfb_sxtpy_import');
        
        import('ORG.Net.UploadFile');
        $upload = new UploadFile(); // 实例化上传类
        $upload->maxSize = $maxSize;
        $upload->allowExts = $allowExts;
        $upload->savePath = APP_PATH . '/Upload/import/'; // 设置附件
        $upload->saveRule = time() . sprintf('%04s', mt_rand(0, 1000));
        $t1 = time();
        
        // 上传错误提示错误信息
        if (! $upload->upload()) {
            $this->error($upload->getErrorMsg());
            exit();
        }
        
        $info = $upload->getUploadFileInfo();
        $md5 = $info[0]['hash'];
        $query = $m_->where(array(
            'file_md5' => $md5))->find();
        if ($query) {
            @unlink(APP_PATH . '/Upload/import/' . $info[0]['savename']);
            $this->error('已上传过同样的文件，为了防止您提交错误的文件，请改动后再重新上传');
            exit();
        }
        
        $data = array(
            'path' => '/Upload/import/' . $info[0]['savename'], 
            'mid' => $mid, 
            'bid' => $bid, 
            'add_time' => date('YmdHis'), 
            'file_md5' => $md5, 
            'remark' => I('remark', '', 'mysql_real_escape_string'));
        $batch_id = $m_->add($data);
        if ($batch_id === false) {
            $this->ajaxReturn(
                array(
                    'info' => '文件上传失败，请重试！', 
                    'status' => 1), 'json');
            exit();
        }
        
        $handle = fopen(APP_PATH . '/Upload/import/' . $info[0]['savename'], 
            'r');
        $i = 0;
        $j = 0;
        $msg = array();
        while ($data = fgetcsv($handle, 15, ",")) {
            ++ $i;
            $phone_no = trim($data[0]);
            if ($phone_no === '')
                continue;
            if (! preg_match("/^1[34578][0-9]{9}$/", $phone_no)) {
                $msg[] = "第{$i}行，手机格式不对";
                continue;
            }
            if (++ $j > 50000) {
                fclose($handle);
                @unlink(APP_PATH . '/Upload/import/' . $info[0]['savename']);
                $m_->delete($batch_id);
                $this->error('最多只支持5W条数据！');
            }
        }
        
        if ($msg || $j == 0) {
            fclose($handle);
            @unlink(APP_PATH . '/Upload/import/' . $info[0]['savename']);
            $m_->delete($batch_id);
            $this->error($msg ? implode('<br>', $msg) : '上传文件中无数据！');
        }
        
        fseek($handle, 0);
        
        // 计算凭证截止时间
        $end_time = date("Ymd235959", strtotime($bInfo["verify_end_date"]));
        if ($bInfo["verify_end_type"] == '1') {
            $end_time = date("Ymd235959", 
                strtotime("+" . $bInfo["verify_end_date"] . " days"));
        }
        
        $tmodel = M('tfb_sxtpy_trace');
        M()->startTrans();
        $i = 0;
        $succ_num = 0;
        while ($data = fgetcsv($handle, 15, ",")) {
            ++ $i;
            $phone_no = trim($data[0]);
            if ($phone_no === '')
                continue;
            
            $data = array(
                'm_id' => $mid, 
                'mname' => $mInfo['name'], 
                'b_id' => $bid, 
                'bname' => $bInfo['batch_name'], 
                'add_time' => date('YmdHis'), 
                'phone_no' => $phone_no, 
                'va_end_time' => $end_time, 
                'batch_id' => $batch_id);
            $id = $tmodel->add($data);
            if ($id === false) {
                M()->rollback();
                fclose($handle);
                @unlink(APP_PATH . '/Upload/import/' . $info[0]['savename']);
                $m_->delete($batch_id);
                $this->error("处理失败！原因：第{$i}行，处理异常！");
            }
            ++ $succ_num;
        }
        $m_->where("id = $batch_id")->save(
            array(
                'num' => $succ_num));
        M()->commit();
        $this->success('批量导入成功！总共导入' . $succ_num . '条记录！');
    }
}