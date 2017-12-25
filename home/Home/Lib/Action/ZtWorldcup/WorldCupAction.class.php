<?php

/**
 * 世界杯营销活动
 */
class WorldCupAction extends BaseAction {

    public function index() {
        $cuptime = strtotime('20170613040000');
        $diff = $cuptime - time();
        
        // $this->assign('start_flag', $diff > 0);
        if ($diff > 0) {
            $day = floor($diff / 86400);
            $hour = floor($diff % 86400 / 3600);
            $minute = floor($diff % 86400 % 3600 / 60);
        } else {
            $day = $hour = $minute = 0;
        }
        
        $this->assign('day', $day);
        $this->assign('hour', $hour);
        $this->assign('minute', $minute);
        
        $model = M('tmarketing_info');
        $where = array(
            'node_id' => $this->node_id, 
            'batch_type' => array(
                'in', 
                '22,23,24,25'));
        $list = $model->where($where)
            ->group('batch_type')
            ->getField('batch_type, count(*)');
        
        $this->assign('status_arr', $list);
        $this->assign('time_remain', $diff < 0 ? 0 : $diff);
        $this->display();
    }

    public function events() {
        $bTime = I('badd_time');
        $bTime = $bTime ? date('YmdHis', strtotime($bTime)) : '';
        $eTime = I('eadd_time');
        $eTime = $eTime ? date('YmdHis', strtotime($eTime . ' 23:59:59')) : '';
        $model = M('tworld_cup_events');
        $where['begin_time'] = array(
            array(
                'EGT', 
                date('YmdHis')),
        );
        if ($bTime) {
            $where['begin_time'][] = array('EGT', $bTime);
        }
        if ($eTime) {
            $where['begin_time'][] = array('ELT', $eTime);
        }
        $list = (array) $model->where($where)
            ->order('events_type asc, begin_time asc')
            ->select();
        $team_arr = M('tworld_cup_team_info')->getField('team_id, team_name');
        foreach ($list as &$info) {
            $info['team1_name'] = $team_arr[$info['team1_id']];
            $info['team2_name'] = $team_arr[$info['team2_id']];
        }
        // 已经创建过活动的场次（页面上不能选择）
        $map = array(
            'batch_type' => '61',//欧洲杯活动
            'node_id' => $this->node_id,
            'status' => '1');
        $selected = M('tmarketing_info')->where($map)->getField('defined_one_name', true);
        //$this->assign('selectedMatch', $selected);
        foreach ($list as $k => $v) {
            if (in_array($v['session_id'], $selected)) {
                unset($list[$k]);
            }
        }
        $this->assign('list', $list);
        
        $this->display();
    }
}
