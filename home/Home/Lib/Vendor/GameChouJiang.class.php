<?php

class GameChouJiang {

    public $id = '';
    // 标签id
    public $mobile = '';
    // 手机号
    public $today = '';
    // 当前日期
    public $status = '1';
    // 中奖状态 1-未中奖
    public $time = '';
    // 当前时间
    public $chance = '';
    // 中奖概率
    public $goods_count = '';
    // 奖品总数
    public $day_goods_count = '';
    // 每日奖品总数
    public $p_code = '';

    public $c_code = '';

    public $resp_msg = '';

    public $cj_id = '';

    public function __construct($id, $mobile) {
        if (empty($id) || empty($mobile)) {
            $this->resp_msg = '错误请求！';
            return false;
        }
        $this->id = $id;
        $this->mobile = $mobile;
        $this->today = Date('Ymd');
        $this->time = Date('His');
    }
    
    // 获取标签详情
    public function getLabel() {
        $model = M('tlabel_info');
        $map = array(
            'label_no' => $this->id);
        $result = $model->where($map)->find();
        if (! $result) {
            $this->resp_msg = '错误参数';
            return false;
        }
        
        $this->p_code = $result['province_code'];
        $this->c_code = $result['city_code'];
    }
    
    // 获取配置参数信息
    public function getBatch() {
        $query = $this->getLabel();
        if ($query === false) {
            $this->resp_msg = '未查询到活动信息';
            return false;
        }
        
        $model = M('tgame_config');
        $query_arr = $model->where()->find();
        if ($query_arr['status'] != '1') {
            $this->resp_msg = '状态不正常';
            return false;
        }
        
        $this->chance = $query_arr['chance'];
        $this->goods_count = $query_arr['goods_count'];
        $this->day_goods_count = $query_arr['day_goods_count'];
    }
    // 抽奖条件
    public function isAllowCj() {
        $query = $this->getBatch();
        if ($query === false) {
            return false;
        }
        $checkModel = M('tgame_cj_trace');
        $map = array(
            'label_id' => $this->id, 
            'mobile' => $this->mobile, 
            'status' => '2', 
            'add_time' => array(
                array(
                    'egt', 
                    $this->today . '000000'), 
                array(
                    'elt', 
                    $this->today . '235959')));
        $query = $checkModel->where($map)->count();
        if ($query >= 3) {
            $this->resp_msg = '不能重复抽奖';
            return false;
        }
        
        // 每日总数
        $map = array(
            'label_id' => $this->id, 
            'status' => '2', 
            'add_time' => array(
                array(
                    'egt', 
                    $this->today . '000000'), 
                array(
                    'elt', 
                    $this->today . '235959')));
        $day_count = $checkModel->where($map)->count();
        
        if ($day_count >= $this->goods_count) {
            $this->resp_msg = '每日奖品已经抽完';
            return false;
        }
        
        // 总数
        $map = array(
            'label_id' => $this->id, 
            'status' => '2');
        $count = $checkModel->where($map)->count();
        if ($count >= $this->day_goods_count) {
            $this->resp_msg = '奖品已经抽完';
            return false;
        }
        return true;
    }
    
    // 抽奖规则
    public function cjRule() {
        $resp = $this->isAllowCj();
        if ($resp === true) {
            $rule = $this->chance;
            $rand = rand(1, 100);
            if ($rand <= $rule) {
                $this->status = '2';
                $this->resp_msg = '中奖';
            } else {
                $this->status = '1';
                $this->resp_msg = '未中奖';
            }
            $this->recordSeq();
        }
    }
    
    // 发码
    public function send_code() {
        $this->cjRule();
        if ($this->status == '2') {
            
            $this->send_count();
            // return true;
            import("@.Vendor.SendCode");
            $req = new SendCode();
            // 发码
            
            $resp = $req->jpc_send($this->mobile, $this->p_code, $this->c_code);
            
            if ($resp === false) {
                $this->UpdateStauts();
                Log::write(print_r($resp, true));
                $msg = "发码异常";
                return $resp;
            } else {
                // 更新流水号
                $this->UpdateSeq($resp);
                return true;
            }
        } else {
            return $this->resp_msg;
        }
    }
    // 更新发码数量
    public function send_count() {
        // 开启事物
        $tranDb = new Model();
        $tranDb->startTrans();
        
        // 更新活动统计
        $model_ = M('tlabel_info');
        $map_ = array(
            'label_no' => $this->id);
        $query_arr = $model_->where($map_)->setInc('send_count', 1);
        
        // 更新日统计
        $dmodel = M('tgame_daystat');
        $smap = array(
            'label_id' => $this->id, 
            'day' => Date('Ymd'));
        $row = $dmodel->where($smap)->find();
        if ($row) {
            $squery = $dmodel->where($smap)->setInc('send_count', 1);
        } else {
            $smap['send_count'] = '1';
            $squery = $dmodel->add($smap);
        }
        // 事务处理
        if ($query_arr && $squery) {
            $tranDb->commit();
        } else {
            $tranDb->rollback();
        }
    }
    
    // 记录抽奖流水
    public function recordSeq() {
        $model = M('tgame_cj_trace');
        $map = array(
            
            'label_id' => $this->id, 
            'mobile' => $this->mobile, 
            'add_time' => $this->today . $this->time, 
            'status' => $this->status, 
            'ip' => get_client_ip());
        $res = $model->add($map);
        if ($res)
            $this->cj_id = $res;
    }
    // 记录发码流水号
    public function UpdateSeq($trade_seq) {
        $model = M('tgame_cj_trace');
        $map = array(
            'id' => $this->cj_id);
        $arr = array(
            'trade_seq' => $trade_seq);
        $res = $model->where($map)->save($arr);
    }
    // 条码发送失败更新为未中奖
    public function UpdateStauts() {
        $model = M('tgame_cj_trace');
        $map = array(
            'id' => $this->cj_id);
        $arr = array(
            'status' => '1');
        $res = $model->where($map)->save($arr);
    }
}