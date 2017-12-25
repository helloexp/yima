<?php

class FixBonusSendAction extends BaseAction {

    public $m_id = null;

    public function _initialize() {
        parent::_initialize();
    }

    public function index() {
        session('num', 0);
        $mId = I('get.m_id');
        $batchInfo = M('tbatch_info')->where(
            array(
                'm_id' => $mId, 
                'node_id' => $this->node_id))->find();
        
        $this->m_id = $batchInfo['m_id'];
        $this->assign('batch_class', $batchInfo['batch_class']);
        $this->assign('batch_id', $batchInfo['id']);
        $this->display();
    }

    public function submit() {
        if (session('num') == 0) {
            session('num', 1);
        } else {
            die('您已经提交过了，请不要重复提交！');
            exit();
        }
        $type = I('post.type');
        $mobile = I('post.mobile');
        $batch_id = I('post.batch_id', '', 'intval');
        // 活动是否存在
        $batchInfo = M('tbatch_info')->where(
            "id='{$batch_id}' AND node_id='{$this->nodeId}' AND status=0")->find();
        if (! $batchInfo)
            $this->resp_msg('未找到有效的活动', '1');
        $this->m_id = $batchInfo['m_id'];
        $type == '1' ? $dataForm = '0' : $dataForm = '2';
        $add_time = date('YmdHis');
        $resp_log_img = I('post.resp_log_img');
        if ($type == '1') {
            if (! is_numeric($mobile) || strlen($mobile) != '11') {
                $this->resp_msg('手机号错误！', '1');
            }
            // 校验库存
            M()->startTrans();
            $goodsStorage = M('tgoods_info')->lock(true)
                ->field('storage_type,remain_num,bonus_id')
                ->where("goods_id='{$batchInfo['goods_id']}'")
                ->find();
            if ($goodsStorage['remain_num'] < 1 &&
                 $goodsStorage['storage_type'] == '1') {
                M()->rollback();
                $this->resp_msg('库存不足！', '1');
            }
            // 插入tbonus_use_detail
            $bonusInfo = M('tbonus_info')->where(
                array(
                    'id' => $goodsStorage['bonus_id']))->find();
            if (! $bonusInfo) {
                M()->rollback();
                $this->resp_msg('红包数据错误！', '1');
            }
            $bonusDetailInfo = M('tbonus_detail')->where(
                array(
                    'bonus_id' => $goodsStorage['bonus_id']))->find();
            if (! $bonusDetailInfo) {
                M()->rollback();
                $this->resp_msg('红包明细数据错误！', '1');
            }
            $data = array(
                'm_id' => $bonusInfo['m_id'], 
                'node_id' => $this->node_id, 
                'bonus_id' => $bonusInfo['id'], 
                'bonus_detail_id' => $bonusDetailInfo['id'], 
                'bonus_num' => 1, 
                'bonus_use_num' => 0, 
                'phone' => $mobile, 
                'status' => '1');
            $resp = M('tbonus_use_detail')->add($data);
            if ($resp === false) {
                M()->rollback();
                $this->resp_msg('红包派发明细数据错误！', '1');
            }
            $ret = M('tbonus_detail')->where(
                array(
                    'id' => $bonusDetailInfo['id']))->setInc('get_num');
            if ($ret === false) {
                M()->rollback();
                $this->resp_msg('红包统计数据错误！', '1');
            }
            $ret = M('tmarketing_info')->where(
                array(
                    'id' => $bonusInfo['m_id']))->setInc('send_count');
            if ($ret === false) {
                M()->rollback();
                $this->resp_msg('红包派发统计数据错误！', '1');
            }
            // 更新库存
            $goodsModel = D('Goods');
            $result = $goodsModel->storagenum_reduc($batchInfo['goods_id'], 1, 
                $mobile, 12, '定额红包单个派发');
            if (! $result) {
                log::write(
                    '信息:' . $goodsModel->getError() . 'goods_id:' .
                         $batchInfo['goods_id'] . 'num:1');
            }
            M()->commit();
            node_log("定额单条发送，红包名称：{$batchInfo['batch_name']}，手机号：{$mobile}");
            $this->resp_msg('派发成功！', '', '1');
        } else {
            $file_array = explode('-', $resp_log_img);
            $file_name = $file_array[0];
            $file_md5 = $file_array[1];
            if ($file_md5 == '' || $file_name == '')
                $this->resp_msg('未上传文件', '1');
            
            $m_ = M();
            $m_->startTrans();
            
            // 批量数据
            $map = array(
                'batch_no' => $batchInfo['batch_no'], 
                'user_id' => $this->userId, 
                'node_id' => $this->nodeId, 
                'send_level' => '2', 
                'status' => '3', 
                'add_time' => $add_time, 
                'send_begin_time' => date('YmdHis'), 
                'file_md5' => $file_md5, 
                'file_name' => $file_name, 
                'data_from' => $dataForm, 
                'info_title' => $batchInfo['info_title'], 
                'mms_notes' => $batchInfo['use_rule'], 
                'notes' => $batchInfo['use_rule'], 
                'print_text' => $batchInfo['print_text'], 
                'validate_times' => '1', 
                'validate_amt' => $batchInfo['batch_amt'], 
                // 'verify_begin_time' =>
                // D('Goods')->dayToDate($batchInfo['verify_begin_date'],$batchInfo['verify_begin_type']),
                // 'verify_end_time' =>
                // D('Goods')->dayToDate($batchInfo['verify_end_date'],$batchInfo['verify_end_type']),
                'b_id' => $batchInfo['id']);
            
            $m_import = M('tbatch_import');
            $m_detail = M('tbonus_use_detail');
            $batch_id = $m_import->add($map);
            if (! $batch_id) {
                $m_->rollback();
                $this->resp_msg('导入失败！', '1');
            }
            $goodsStorage = M('tgoods_info')->field(
                'storage_type,remain_num,bonus_id')
                ->where("goods_id='{$batchInfo['goods_id']}'")
                ->find();
            $bonusDetailInfo = M('tbonus_detail')->where(
                array(
                    'bonus_id' => $goodsStorage['bonus_id']))->find();
            if (! $bonusDetailInfo) {
                M()->rollback();
                $this->resp_msg('红包明细数据错误！', '1');
            }
            // 读取csv文件
            $resp_arr = array();
            $handle = fopen(APP_PATH . '/Upload/import/' . $file_name, 'r');
            
            $sum_arr = array();
            $row = 0;
            while ($file = fgetcsv($handle, 1000, ",")) {
                $num = count($file);
                $row ++;
                for ($c = 0; $c < $num; $c ++) {
                    $sum_arr[] = $file[$c];
                    // 派发明细流水
                    $data = array(
                        'm_id' => $bonusDetailInfo['m_id'], 
                        'node_id' => $this->node_id, 
                        'bonus_id' => $bonusDetailInfo['bonus_id'], 
                        'bonus_detail_id' => $bonusDetailInfo['id'], 
                        'bonus_num' => 1, 
                        'bonus_use_num' => 0, 
                        'phone' => $file[$c], 
                        'status' => '1');
                    if (! is_numeric($file[$c]) || strlen($file[$c]) != '11') {
                        $m_->rollback();
                        $this->resp_msg('第' . $row . '行手机号错误:' . $file[$c], '1');
                    }
                    
                    $query = $m_detail->add($data);
                    if (! $query) {
                        $m_->rollback();
                        $this->resp_msg('导入失败！', '1');
                    }
                }
            }
            fclose($handle);
            if (! $sum_arr) {
                $m_->rollback();
                $this->resp_msg('文件内容错误！', '1');
            }
            $total_count = count($sum_arr);
            // 更新tbonus_detail的已发数量
            $save_ = M('tbonus_detail')->where(
                array(
                    'id' => $bonusDetailInfo['id']))->setInc('get_num', 
                $total_count);
            if ($save_ === false) {
                $m_->rollback();
                $this->resp_msg('更新定额红包发放数量失败！', '1');
            }
            $save_ = $m_import->where(
                array(
                    'batch_id' => $batch_id))->save(
                array(
                    'send_level' => $send_level, 
                    'total_count' => $total_count));
            if ($save_) {
                // 更新库存
                $goodsStorage = M('tgoods_info')->lock(true)
                    ->field('storage_type,remain_num')
                    ->where("goods_id='{$batchInfo['goods_id']}'")
                    ->find();
                
                if ($goodsStorage['remain_num'] < $total_count &&
                     $goodsStorage['storage_type'] == '1') {
                    $m_->rollback();
                    $this->resp_msg('库存不足！', '1');
                }
                $goodsModel = D('Goods');
                $result = $goodsModel->storagenum_reduc($batchInfo['goods_id'], 
                    $total_count, $batch_id, 8);
                if (! $result) {
                    log::write(
                        '信息:' . $goodsModel->getError() . 'goods_id:' .
                             $batchInfo['goods_id'] . 'num:1');
                }
                $ret = M('tmarketing_info')->where(
                    array(
                        'id' => $bonusDetailInfo['m_id']))->setInc('send_count', 
                    $total_count);
                if ($ret === false) {
                    M()->rollback();
                    $this->resp_msg('红包派发统计数据错误！', '1');
                }
                
                $m_->commit();
                node_log("定额红包批量派发，红包名称：{$batchInfo['batch_name']}");
                
                $this->resp_msg('操作成功!', '', '', $dataForm);
            } else {
                $m_->rollback();
                $this->resp_msg('系统错误！', '1');
            }
        }
    }

    public function resp_msg($msg, $err = '', $seq = '', $type = '0') {
        if ($err != '') {
            $this->assign('error', $err);
        } elseif ($seq != '') {
            $this->assign('seq', '1');
        }
        $this->assign('jumpUrl', $jumpUrl);
        $this->assign('m_id', $this->m_id);
        $this->assign('message', $msg);
        $this->display('respMsg');
        exit();
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
}