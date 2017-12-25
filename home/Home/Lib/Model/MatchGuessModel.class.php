<?php

/**
 *
 * @author lwb Time 20150720
 */
class MatchGuessModel extends Model {
    protected $tableName = '__NONE__';

    public function _initialize() {
        import('@.Vendor.CommonConst') or die('include file fail.');
    }

    public function getGuessList($nodeId, $mId, $limit) {
        $re = M('tworld_cup_match_quiz')
        ->alias('wcmq')
        ->field("wcmq.phone_no,ifnull(wcti.team_name,'平局') team_name,wcmq.add_time,
            wcti1.team_name as team1_name,wcti2.team_name as team2_name,wcmq.score")
        ->where(['node_id' => $nodeId, 'batch_id' => $mId])
        ->join('left join tworld_cup_team_info wcti on wcti.team_id = wcmq.team_id')
        ->join('left join tworld_cup_events wce on wce.session_id = wcmq.session_id')
        ->join('left join tworld_cup_team_info wcti1 on wcti1.team_id = wce.team1_id')
        ->join('left join tworld_cup_team_info wcti2 on wcti2.team_id = wce.team2_id')
        ->limit($limit)
        ->select();
        return $re;
    }
}