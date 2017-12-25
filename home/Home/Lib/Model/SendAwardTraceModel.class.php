<?php
class SendAwardTraceModel extends Model {
    protected $tableName = 'tsend_award_trace';
    
    /**
     * 发送奖品的记录
     * @param unknown $nodeId
     * @param unknown $mId
     * @param string $phoneNo
     * @param string $nickName
     * @param string $time  例：20160318
     * @param string $status 1正常 2失败
     * @return Page[]|unknown[]
     */
    public function getTraceList($nodeId, $mId, $phoneNo='', $nickName='', $time='', $status = '') {
        $count = 0;
        import("ORG.Util.Page");
        $re = M('tbatch_info')
        ->alias('i')
        ->field('i.id,i.batch_name,i.batch_class,i.goods_id')
        ->where(array('i.node_id' => $nodeId, 'i.m_id' => $mId))
        ->select(false);
        
        $traceMap = array(
            't.node_id' => $nodeId, 
            't.batch_id' => $mId, 
            't.status' => 2
        );
        if (!empty($phoneNo)) {
            $traceMap['t.send_mobile'] = $phoneNo;
        }
        //需要检索微信昵称时
        $nickJoinSql = '';
        if (!empty($nickName)) {
            $nickJoinSql = M('tcj_trace')
            ->alias('ct')
            ->field('IFNULL(m.nickname,ct.wx_name) as wx_nickname,ct.id')
            ->where(array('ct.node_id' => $nodeId, 'ct.batch_id' => $mId, 'ct.status' => 2))
            ->join('left join tmember_info m on ct.member_id = m.id')
            ->select(false);
            $nickJoinSql = 'inner join ' . $nickJoinSql . ' as n on n.id = t.id';
            $traceMap['n.wx_nickname'] = $nickName;
        }
        
        //dump($re);exit;
        $result = M('tcj_trace')
        //$result = M('tbarcode_trace')
        ->alias('t')
        ->field('b.batch_name,t.request_id,t.send_mobile,t.add_time,b.batch_class,b.goods_id,t.wx_name')
        ->where($traceMap)
        ->join('inner join ' . $re . ' as b on b.id = t.b_id')
        ->join($nickJoinSql)
        ->select(false);
        
        //$where['trace.node_id'] = $nodeId;
        $where = array();
        if (!empty($time)) {
            $map['trace.deal_time'] = array(
                    'like',
                    $time . '%'
            );
            $map['r.add_time'] = array(
                    'like',
                    $time . '%'
            );
            $map['_logic'] = 'or';
            $where['_complex'] = $map;
        }
        if ($status !== '') {
            if ($status == 1) {
                $where['_string'] = 'trace.deal_flag is null or trace.deal_flag = 3';
            } else {
                $where['trace.deal_flag'] = array('neq', 3);
            }
        }
        
        $count = $this->alias('trace')
        ->join('right join ' . $result . ' as r on r.request_id = trace.request_id')
        ->order('trace.id desc')
        ->where($where)
        ->count();
        $p = new Page($count, 8);
        
        foreach ($_REQUEST as $key => $val) {
            $p->parameter[$key] = urlencode($val); // 赋值给Page
        }
        
        $trace = $this
        ->alias('trace')
        ->field(
            'r.batch_name,r.send_mobile,r.batch_class,trace.request_id,r.goods_id,r.wx_name,
            trace.id,ifnull(trace.deal_time, r.add_time) as send_time,
            ifnull(trace.deal_flag, 3) as send_flag,trace.*')
        ->join('right join ' . $result . ' as r on r.request_id = trace.request_id')
        ->limit($p->firstRow, $p->listRows)
        ->order('send_time desc')
        ->where($where)
        ->select();
        return array(
            'trace' => $trace, 
            'p' => $p
        );
    }
    
    /**
     * 修改处理失败的记录为未处理
     * @param unknown $nodeId
     * @param unknown $traceId
     * @return boolean
     */
    public function reviseDealFlag($nodeId, $traceId) {
        return $this->where(array('node_id' => $nodeId, 'id' => $traceId))->save(array('deal_flag' => '1'));
    }
    
    public function reviseDealFlagByMid($nodeId, $mId) {
        $where = array(
            't.node_id' => $nodeId,
            't.batch_id' => $mId,
            't.status' => '2',
            'ta.deal_flag' => array('neq', '3'),
        );
        $re = M('tcj_trace')->alias('t')
        ->field('ta.id')
        ->join('left join tsend_award_trace ta on t.request_id = ta.request_id')
        ->where($where)
        ->select(false);
        $this->query("UPDATE tsend_award_trace a inner join ".$re." b on b.id = a.id SET `deal_flag`='1'");
    }
    
    /**
     * 获取发送奖品失败的记录
     * @param  $nodeId
     * @param unknown $mId
     * @return 失败的流水记录 or false
     */
    public function getFailedRecord($nodeId, $mId) {
        $where = array(
            't.node_id' => $nodeId, 
            't.batch_id' => $mId,
            't.status' => '2', 
            'ta.deal_flag' => array('neq', '3'), 
        );
        
        $count = M('tcj_trace')->alias('t')
        ->join('inner join tbatch_info i on t.b_id = i.id')
        ->join('left join tsend_award_trace ta on t.request_id = ta.request_id')
        ->where($where)
        ->count();
        
        return $count;
    }
    
    public function savePhoneNumberById($nodeId, $id, $phone) {
        return $this->where(array('id' => $id, 'node_id' => $nodeId))->save(array('phone_no' => $phone, 'ims_flag' => 1));
    }

    public function getByRequestId($requestId)
    {
        return $this->where(array('request_id' => $requestId))->find();
    }

    /**
     * @param $data
     * @param $where
     *
     * @return bool
     */
    public function updatePhonenoAndStatus($data, $where)
    {
        return $this->where($where)->save($data);
    }
}