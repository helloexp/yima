<?php
// 微信群发定时设置
class WeixinMakeCardOnTimeAction {

    public function doMakeCardOnTime() {
        echo 'aaaa';
        set_time_limit(0);
        // 检查同步标志
        $sync_flag = M('tsystem_param')->where(
            "param_name ='WX_MAKE_CARD_FLAG'")->find();
        if (! $sync_flag) {
            $this->log("get WX_MAKE_CARD_FLAG not exit");
            return;
        }
        
        if ($sync_flag['param_value'] != '1') {
            $this->log("the WX_MAKE_CARD_FLAG is syncing");
            return;
        }
        
        // 更新同步标志
        $sync_flag_save['param_value'] = '2';
        $rs = M('tsystem_param')->where("param_name ='WX_MAKE_CARD_FLAG'")->save(
            $sync_flag_save);
        if ($rs === false) {
            $this->log("update WX_MAKE_CARD_FLAG fail" . M()->_sql());
            return;
        }
        // 获取待发送批次
        $batch_list = M('tbatch_import')->where(
            "trans_type IN ('3','4') AND STATUS = '0'")->select();
        log_write('检测错误[1]:'.M()->_sql());
        foreach ($batch_list as $batch) {
            $this->log("get batch  :[" . $batch['batch_id'] . "]");
            $this->makeCard($batch);
        }
        // 更新完成恢复同步标志
        $sync_flag_save['param_value'] = '1';
        $rs = M('tsystem_param')->where("param_name ='WX_MAKE_CARD_FLAG'")->save(
            $sync_flag_save);
        if (! $rs) {
            $this->log("update WX_MAKE_CARD_FLAG fail" . M()->_sql());
            return;
        }
    }

    private function makeCard($batch) {
        log_write('检测错误[2]:'.print_r($batch,true));
        $zipFileName = date('YmdHis').'_'.$batch['batch_id'];
        $batch_update['succ_num'] = 0;
        $batch_update['fail_num'] = 0;
        $wxCardService = D('WeiXinCard', 'Service');
        $wxCardService->init_by_node_id($batch['node_id']);
        // 更改发送状态-发送中
        $batch_update['status'] = '1';
        $rs = M('tbatch_import')->where(
            "batch_id = '{$batch['batch_id']}'")->save($batch_update);
        log_write('检测错误[3]:'.M()->getLastSql());
        if ($rs === false) {
            log_write('更新tbatch_import 状态失败'.M()->_sql());
            return;
        }
        $wx_batch_info = M()->table('tbatch_info')
            ->where("id = " . $batch['b_id'])
            ->find();
        log_write('检测错误[4]:'.M()->getLastSql());
        //获取outId
        $outId = $wxCardService->getOutId('2', $batch['batch_id'], 0, 0, $wx_batch_info['m_id'], $wx_batch_info['id']);
        log_write('检测错误[5]:'.print_r($outId,true));
        if ($batch['trans_type'] == '4'){// 4-微信卡券(一码多券)
            $codeArr = array();
            for ($i = 0 ; $i < $batch['total_count']; $i++){
                $codeArr[] = $wxCardService->add_assist_number_for_create($wx_batch_info);
            }
            $url = $wxCardService->create_wxcard_qrcode_multiple($wx_batch_info['card_id'], $codeArr, $outId);
            if ($url === false){
                log_write('制二维码失败 create_wxcard_qrcode_multiple'.M()->_sql());
                $batch_update['status'] = '2';
                $batch_update['succ_num'] = 0;
                $batch_update['fail_num'] = $batch['total_count'];
                $rs = M('tbatch_import')->where(
                    "batch_id = '{$batch['batch_id']}'")->save($batch_update);
                if ($rs === false) {
                    log_write('更新tbatch_import 状态失败'.M()->_sql());
                    return false;
                }
                return false;
            }
            $urlArr[]=$url;
            $zipFileName = $this->createZip($zipFileName, $batch['node_id'], $batch['batch_id'], $urlArr);
            $batch_update['status'] = '3';
            $batch_update['succ_num'] = $batch['total_count'];
            $batch_update['fail_num'] = 0;
            $batch_update['file_name'] = $zipFileName;
            $rs = M('tbatch_import')->where(
                "batch_id = '{$batch['batch_id']}'")->save($batch_update);
            if ($rs === false) {
                log_write('更新tbatch_import 状态失败'.M()->_sql());
                return false;
            }
            return true;
        }else if ($batch['trans_type'] == '3'){//3-微信卡券(一码一券)
            $codeArr = array();
            for ($i = 0 ; $i < $batch['total_count']; $i++){
                $codeArr = $wxCardService->add_assist_number_for_create($wx_batch_info);
                log_write('检测错误[6]:'.print_r($codeArr,true));
                $url = $wxCardService->create_wxcard_qrcode($wx_batch_info['card_id'], $codeArr, $outId);
                if ($url === false){
                    log_write('制二维码失败 create_wxcard_qrcode'.M()->_sql());
                    $batch_update['status'] = '2';
                    $batch_update['fail_num'] ++;
                    $rs = M('tbatch_import')->where(
                        "batch_id = '{$batch['batch_id']}'")->save($batch_update);
                    if ($rs === false) {
                        log_write('更新tbatch_import 状态失败'.M()->_sql());
                        return false;
                    }
                    return false;
                }else{
                    $batch_update['succ_num'] ++;
                    $rs = M('tbatch_import')->where(
                        "batch_id = '{$batch['batch_id']}'")->save($batch_update);
                    if ($rs === false) {
                        log_write('更新tbatch_import 状态失败'.M()->_sql());
                        return false;
                    }
                    $urlArr[]=$url;
                }
            }
            
            $zipFileName = $this->createZip($zipFileName, $batch['node_id'], $batch['batch_id'], $urlArr);
            $batch_update['status'] = '3';
            $batch_update['file_name'] = $zipFileName;
            $rs = M('tbatch_import')->where(
                "batch_id = '{$batch['batch_id']}'")->save($batch_update);
            if ($rs === false) {
                log_write('更新tbatch_import 状态失败'.M()->_sql());
                return false;
            }
            return true;
        }
        
    }

    private function createZip($zipFileName, $nodeId, $batchId, $urlArr){
        import('@.Vendor.phpqrcode.phpqrcode') or die('include file fail.');
        $rootpath = APP_PATH . 'Upload/wxcard_codeLoad/';
        if (! is_dir($rootpath)) {
            mkdir($rootpath);
        }
        $path = $rootpath . $nodeId . '/';
        if (! is_dir($path)) {
            mkdir($path);
        }
        $path = $path . $batchId . '/';
        if (! is_dir($path)) {
            mkdir($path);
        }
        
        if (! empty($urlArr)) {
            $zip = new ZipArchive();
            $zipfilename = $zipFileName.'.zip';
            $zipfile = $path . $zipfilename;
            $zip_path = mb_convert_encoding($zipfile, "GBK", "UTF-8");
            if ($zip->open($zip_path, ZipArchive::OVERWRITE) === TRUE) {
                $i = 1;
                foreach ($urlArr as $url) {
                    $filename = str_pad($i, 8, '0', STR_PAD_LEFT). '.png';
                    $file = $path . $filename;
                    if (! file_exists($file)) {
                        $this->codePng($url, false, $file, 600);
                    }
                    $zip->addFile($file, $filename);
                    $i++;
                }
                $zip->close();
            }
            return $zipfile;
        } else {
            log_write('无数据');
            return false;
        }
    }

     // 二维码
    private function codePng($data, $isUrl = false, $path = false, $size = 1) {
        $ap_arr = array(
            'is_resp' => '1', 
            'wfx' => '');
        import('@.Vendor.phpqrcode.phpqrcode') or die('include file fail.');
        if ($path) {
            QRcode::png($data, $path, 'L', $size, 0, false, '');
        } else {
            if ($isUrl) {
                return $data;
            } else {
                QRcode::png($data, false, 'L', $size, 0, false, '', 
                    '', $ap_arr);
            }
        }
    }
    // 记录日志
    protected function log($msg, $level = Log::INFO) {
        log_write($msg);
    }
}
