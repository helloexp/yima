<?php
/**
 * @author lwb
 * 20160601
 */
class LabelMatchGuessModel extends BaseModel {

    protected $tableName = '__NONE__';


    /**
     * 获取奖品列表
     * @param array $condition
     * @return mixed
     */
    public function getAwardList($condition) {
//         $whereFormat = 't.mobile="%s" AND t.status=2 AND t.batch_id=%d and t.batch_type=%d';
//         $mobile = isset($condition['mobile']) ? $condition['mobile'] : '';
//         $batch_id = isset($condition['batch_id']) ? $condition['batch_id'] : '';
//         $batch_type = isset($condition['batch_type']) ? $condition['batch_type'] : '';
//         $where = sprintf($whereFormat, $mobile, $batch_id, $batch_type);
        $where = [
            't.mobile' => $condition['mobile'], 
            't.status' => '2', 
            't.batch_id' => $condition['batch_id'], 
            't.batch_type' => $condition['batch_type'],
        ];
        $normalAwardList = M()->table('tcj_trace t')
            ->field(
            't.id as cj_trace_id,t.status,t.mobile,t.send_mobile,
                t.request_id,gi.goods_name,gi.node_id,gi.goods_type,gi.source,
                t.batch_id,bi.id as card_batch_id,bi.batch_amt,
                bi.batch_img,bi.card_id,bi.batch_short_name,ni.node_name,
                ct.end_time as userpoint_end_time')
            ->join('tbatch_info bi ON bi.id=t.b_id')
            ->join('tgoods_info gi ON bi.goods_id=gi.goods_id')
            ->join('tnode_info ni ON ni.node_id=t.node_id')
            ->join('tbarcode_trace ct ON ct.request_id=t.request_id')
            ->join('tphone_bills_trace pbt on pbt.request_id=t.request_id')
            ->where($where)
            ->select();
        //log_write('欧洲杯中奖纪录sql：' . M()->_sql());
        return $normalAwardList;
    }

    /**
     * 获取该机构号下该场次的某个队伍的支持数
     * @param string $nodeId 机构号
     * @param int $batchId 活动号
     * @param array $teamIdArr 队伍号
     * @param int $sessionId 场次号
     * @return int $count 支持数
     */
    public function getSupportNo($nodeId, $batchId, $teamIdArr, $sessionId) {
        $sql1 = M('tworld_cup_match_quiz')->where(
                [
                    'node_id' => $nodeId, 
                    'batch_id' => $batchId, 
                    'team_id' => $teamIdArr[0], 
                    'session_id' => $sessionId
                ]
            )
            ->field('count(batch_id) as a_count')
            ->select(false);
        $sql2 = M('tworld_cup_match_quiz')->where(
                [
                    'node_id' => $nodeId,
                    'batch_id' => $batchId,
                    'team_id' => '0',
                    'session_id' => $sessionId
                ]
            )
            ->field('count(batch_id) as b_count')
            ->select(false);
        $sql3 = M('tworld_cup_match_quiz')->where(
                [
                    'node_id' => $nodeId,
                    'batch_id' => $batchId,
                    'team_id' => $teamIdArr[1],
                    'session_id' => $sessionId
                ]
            )
            ->field('count(batch_id) as c_count')
            ->select(false);
        $result = M()->table($sql1 . ' a')
        ->field('a_count,b_count,c_count')
        ->join($sql2 . ' b on 1=1')
        ->join($sql3 . ' c on 1=1')
        ->select();
        return $result[0];
    }

    /**
     * 查询属否属于微信粉丝
     * @param mixed $where wx_user查询条件
     * @return boolean|array 
     */
    public function getWxUser($where) {
        return M('twx_user')->where($where)->find();
    }
    
    public function getHistoryPrizeList($nodeId, $openId) {
        $phone = array();
        $result = M('tworld_cup_match_quiz')
        ->distinct(true)
        ->where(['node_id' => $nodeId, 'wx_id' => $openId])
        ->getField('phone_no', true);
        if ($result) {
            $phone = $result;
        }
        $phone[] = $openId;//由于积分用手机记录，所以必须获取之前参与的所有记录的手机号,再加一个wxopenid去查
        $re = M()
        ->table('tcj_trace ct')
        ->field(
            'wcti1.team_name as team1_name,
            wcti2.team_name as team2_name,
            bi.batch_amt,bi.batch_class,
            bi.batch_type,bi.batch_short_name,
            bi.batch_img as img')
        ->join('left join tmarketing_info mi on mi.id = ct.batch_id')
        ->join('left join tworld_cup_events wce on wce.session_id = mi.defined_one_name')
        ->join('left join tworld_cup_team_info wcti1 on wcti1.team_id = wce.team1_id')
        ->join('left join tworld_cup_team_info wcti2 on wcti2.team_id = wce.team2_id')
        ->join('left join tbatch_info bi on bi.id = ct.b_id')
        ->where(
                [
                    'ct.node_id' => $nodeId, 
                    'ct.mobile' => ['in', $phone], 
                    'ct.batch_type' => '61', 
                    'ct.status' => '2', 
                ]
            )
        ->select();
        return $re;
    }
}