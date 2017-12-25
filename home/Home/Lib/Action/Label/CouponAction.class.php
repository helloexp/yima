<?php

class CouponAction extends MyBaseAction {

    public function index() {
        if ($this->batch_type != '9') {
            $this->error(
                array(
                    'errorImg' => '__PUBLIC__/Label/Image/waperro5.png', 
                    'errorTxt' => '错误访问！', 
                    'errorSoftTxt' => '你的访问地址出错啦~'));
        }
        // 访问量
        import('@.Vendor.DataStat');
        $opt = new DataStat($this->id, $this->full_id);
        $opt->UpdateRecord();
        
        // 活动
        $batchModel = M('tmarketing_info');
        $row = $batchModel->where(
            array(
                'id' => $this->batch_id))->find();
        
        // 抽奖配置表
        if ($row['is_cj'] == '1') {
            $model_c = M('tcj_rule');
            $map_c = array(
                'batch_type' => $this->batch_type, 
                'batch_id' => $this->batch_id, 
                'status' => '1');
            $cj_rule_query = $model_c->field('cj_button_text,cj_check_flag')
                ->where($map_c)
                ->find();
            // 抽奖文字配置
            $cj_text = $cj_rule_query['cj_button_text'];
        }
        
        // for 中奖记录 start
        $DLCommonMobile = $this->getMobileForAwardList($this->id);
        if ($DLCommonMobile) {
            log_write('if $DLCommonMobile:' . var_export($DLCommonMobile, 1));
            $this->assign('showAwardList', 'block');
        } else {
            log_write('else $DLCommonMobile:' . var_export($DLCommonMobile, 1));
            $this->assign('showAwardList', 'none');
        }
        // for 中奖记录 end
        
        $this->assign('id', $this->id);
        $this->assign('batch_type', $this->batch_type);
        $this->assign('batch_id', $this->batch_id);
        $this->assign('cj_text', $cj_text);
        $this->assign('row', $row);
        $this->assign('pay_token', $this->pay_token);
        $this->display(); // 输出模板
    }
}