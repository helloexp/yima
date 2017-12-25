<?php

class BmModel extends BaseModel {
    protected $tableName = '__NONE__';
    // 市场调研 自动保存
    public function tdraftSave($data, $node_id) {
        for ($i = 0; $i <= 30; $i ++) {
            if ($data['q_' . $i] == '')
                continue;
            $question_arr = array(
                'questions' => $data['q_' . $i], 
                'type' => $data['t_' . $i], 
                'sort' => $data['sort_' . $i], 
                'count' => count($data['a_' . $i]), 
                'answer' => $data['a_' . $i]);
            unset($data['q_' . $i]);
            unset($data['a_' . $i]);
            unset($data['t_' . $i]);
            unset($data['sort_' . $i]);
            $data['question'][$i] = $question_arr;
        }
        foreach ($data['question'] as $val) {
            $sort[] = $val['sort'];
        }
        array_multisort($sort, SORT_DESC, $data['question']);
        $data['question2'] = array_reverse($data['question']);
        $data['question_count'] = count($data['question']);
        $org_id = $data['id'];
        if (! $org_id)
            $org_id = 0;
        $content = json_encode($data);
        $where = array(
            'node_id' => $node_id, 
            'type' => 5, 
            'org_id' => $org_id);
        $tdraft = M('tdraft')->where($where)->find();
        if (! $tdraft) {
            $status = M('tdraft')->add(
                array(
                    'node_id' => $node_id, 
                    'content' => $content, 
                    'add_time' => date('Y-m-d H:i:s'), 
                    'type' => 5, 
                    'org_id' => $org_id));
        } else {
            $status = M('tdraft')->where($where)->save(
                array(
                    'content' => $content, 
                    'add_time' => date('Y-m-d H:i:s')));
        }
        return $status;
    }
    
    /**
     * 获取市场调研的参与人数
     * @param int $nodeId
     * @param int $mId
     * @return mixed
     */
    public function getJoinCountByMId($nodeId, $mId) {
        $re = M('tbm_trace')->where(['label_id' => $mId, 'node_id' => $nodeId])->count();
        return $re;
    }
}