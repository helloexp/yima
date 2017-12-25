<?php

/**
 * 粽礼寻Ta节设置 @auther yes
 */
class ZongZiCjSetAction extends BaseAction {

    public $_authAccessMap = '*';

    const BATCH_TYPE_SPRING = 50;

    public function index() {
        $batch_id = I('batch_id', NULL, 'trim');
        if (empty($batch_id))
            $this->error('活动id不能为空！');
            
            // 校验活动
        $query_arr = M('tmarketing_info')->field(
            'id,cj_phone_type,batch_type,defined_one_name')
            ->where(
            array(
                'node_id' => $this->node_id, 
                'id' => $batch_id, 
                'batch_type' => self::BATCH_TYPE_SPRING))
            ->find();
        
        if (! $query_arr)
            $this->error('参数错误！');
        
        $isShowCjButton = true;
        
        // 未设置抽奖规则默认写入
        $cj_rule_arr = M('tcj_rule')->where(
            array(
                'node_id' => $this->node_id, 
                'batch_id' => $batch_id, 
                'status' => '1'))->find();
        if (! $cj_rule_arr) {
            $in_arr = array(
                'node_id' => $this->node_id, 
                'batch_type' => $query_arr['batch_type'], 
                'batch_id' => $batch_id, 
                'add_time' => date('YmdHis'), 
                'total_chance' => 100, 
                'phone_total_count' => 0, 
                'phone_day_count' => 0, 
                'phone_total_part' => 0, 
                'phone_day_part' => 0);
            $insert = M('tcj_rule')->add($in_arr);
            if (! $insert) {
                $this->error('操作失败！');
            }
            $cj_rule_arr = M('tcj_rule')->where(
                array(
                    'node_id' => $this->node_id, 
                    'batch_id' => $batch_id, 
                    'status' => '1'))->find();
        }
        // 奖项
        $cj_cate_arr = M('tcj_cate')->where(
            array(
                'node_id' => $this->node_id, 
                'batch_id' => $batch_id, 
                'cj_rule_id' => $cj_rule_arr['id']))->select();
        // 默认插入奖项
        if (! $cj_cate_arr) {
            $default_arr = array(
                '1' => array(
                    'name' => '奖品'));
            $cate_in = array(
                'node_id' => $this->node_id, 
                'batch_type' => $query_arr['batch_type'], 
                'batch_id' => $batch_id, 
                'cj_rule_id' => $cj_rule_arr ? $cj_rule_arr['id'] : $insert, 
                'add_time' => date('YmdHis'));
            foreach ($default_arr as $d) {
                $cate_in['name'] = $d['name'];
                $cate_in['score'] = $d['score'];
                $cate_default = M('tcj_cate')->add($cate_in);
            }
            $cj_cate_arr = M('tcj_cate')->where(
                array(
                    'node_id' => $this->node_id, 
                    'batch_id' => $batch_id, 
                    'cj_rule_id' => $cj_rule_arr['id']))->select();
        }
        // 奖品
        $jp_arr = M()->table('tcj_batch a')
            ->field('a.*,b.batch_name,c.online_verify_flag')
            ->join('tbatch_info b on a.b_id=b.id')
            ->join('tgoods_info c on c.goods_id = a.goods_id')
            ->where(
            "a.node_id='" . $this->node_id . "' and a.batch_id='" . $batch_id .
                 "'")
            ->select();
        $data = array(
            'id' => $batch_id);
        $num = M('tmarketing_info')->where($data)->getfield('defined_one_name');
        $this->assign('num', $num);
        $this->assign('batch_type', $query_arr['batch_type']);
        $this->assign('cj_phone_type', $query_arr['cj_phone_type']);
        $this->assign('jp_arr', $jp_arr);
        $this->assign('cj_cate_arr', $cj_cate_arr);
        $this->assign('cj_rule_arr', $cj_rule_arr);
        $this->assign('batch_id', $batch_id);
        $this->assign('isShowCjButton', $isShowCjButton);
        $this->assign('defined_one_name', $query_arr['defined_one_name']);
        $this->display();
    }
    // 设置中奖率
    public function zhongjl() {
        $batch_id = I('batch_id');
        $num = I("num");
        $date = array(
            'id' => $batch_id, 
            'node_id' => $this->node_id);
        $date1 = array(
            'defined_one_name' => $num);
        $re = M('tmarketing_info')->where($date)->save($date1);
        if ($re === false) {
            exit(
                json_encode(
                    array(
                        'info' => '保存失败！', 
                        'status' => 0)));
        } else {
            exit(
                json_encode(
                    array(
                        'info' => '保存成功！', 
                        'status' => 1)));
        }
    }
    // 设置奖项
    public function jpType() {
        $cj_cate_id = I('cj_cate_id', NULL, 'trim');
        $batch_id = I('batch_id', NULL, 'trim');
        if (empty($batch_id))
            $this->error('活动id不能为空！');
        
        if ($cj_cate_id) {
            $cate_arr = M('tcj_cate')->where(
                array(
                    'node_id' => $this->node_id, 
                    'id' => $cj_cate_id, 
                    'batch_id' => $batch_id))->find();
        }
        $this->assign('cate_arr', $cate_arr);
        $this->assign('cj_cate_id', $cj_cate_id);
        $this->assign('batch_id', $batch_id);
        $this->display();
    }
    
    // 设置奖品
    public function selectJp() {
        $batch_id = I('batch_id', '', 'trim');
        $cj_cate_id = I('cj_cate_id', '', 'trim');
        $cj_batch_id = I('cj_batch_id', '', 'trim');
        if (empty($batch_id))
            $this->error('活动id不能为空！');
        if ($cj_batch_id) {
            // 奖品
            $jp_arr = M()->table('tcj_batch a')
                ->join('tbatch_info b on a.b_id=b.id')
                ->
            // ->join('tgoods_info c on b.goods_id=c.goods_id')
            where(
                "a.node_id='" . $this->node_id . "' and a.id='" . $cj_batch_id .
                     "'")
                ->find();
        }
        $this->assign('cj_batch_id', $cj_batch_id);
        $this->assign('cj_cate_id', $cj_cate_id);
        $this->assign('batch_id', $batch_id);
        $this->assign('jp_arr', $jp_arr);
        $this->display();
    }
}
