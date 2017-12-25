<?php
/**
 * author 王盼
 * User: kira
 * Date: 2016/1/6
 * Time: 18:32
 */
class TmemberMsgModel extends Model{
    protected $tableName = 'tmember_msg';
    /**
     * 获取当前商户下的所有消息
     * @param $nodeId        string     商户编号
     * @param $searchTitle   string     搜索条件
     * @param $firstRow      string
     * @return mixed
     */
    public function getAllMessage($nodeId, $searchTitle, $firstRow, $listRows){
        $where = array('a.node_id'=>$nodeId,'a.reader'=>2,'a.status'=>2);
        if(!empty($searchTitle)){
            $where['a.title'] = array('like','%'.$searchTitle.'%');
        }
        $field = ' a.*,b.user_name ';
        $join = ' a left join tuser_info b on a.user_id=b.user_id ';
        $result = $this->field($field)->where($where)->join($join)->limit($firstRow, $listRows)->order('a.add_time desc')->select();
        foreach($result as $key=>$value){
            if(!empty($value['reader_list'])){
                $cardStr = M('tmember_cards')->field("GROUP_CONCAT(card_name) reader_list")->where(array('node_id'=>$nodeId,'id'=>array('in',$value['reader_list'])))->select();
                $result[$key]['cardList'] = str_replace(',',' | ',$cardStr[0]['reader_list']);
            }
        }
        return (array)$result;

    }
    /**
     * 获取指定系统消息
     * @param $nodeId  string  商户编号
     * @param $mId     string  消息ID
     * @return mixed
     */
    public function getRowMessage($nodeId, $mId){
        $field = ' a.*,b.user_name user_name';
        $where = array('a.node_id'=>$nodeId,'a.id'=>$mId,'a.status'=>2);
        $join = ' a left join tuser_info b on a.user_id=b.user_id ';
        $result = $this->field($field)->where($where)->join($join)->find();
        $sendMemberCards = M('tmember_cards')->field('GROUP_CONCAT(card_name) reader_list')->where(array('node_id'=>$nodeId,'id'=>array('in',$result['reader_list'])))->select();
        $result['cardList'] = str_replace(',',' | ',$sendMemberCards[0]['reader_list']);
        return $result;
    }
    /**
     * 添加系统消息
     * @param $data    array   要添加的消息
     * @return mixed
     */
    public function addMessage($data){
        $result = $this->add($data);
        return $result;
    }
    /**
     * 修改系统消息
     * @param $nodeId   string   商户编号
     * @param $mId      string   消息id
     * @param $data     array    要修改的消息
     * @return mixed
     */
    public function editMessage($nodeId, $mId, $data){
        $result = $this->where(array('id'=>$mId,'node_id'=>$nodeId))->save($data);
        return $result;
    }
    /**
     * 删除指定消息
     * @param  $nodeId  string  商户编号
     * @param  $id      string  消息ID
     * @return mixed
     */
    public function delMsg($nodeId, $id){
        $result = $this->where(array('node_id'=>$nodeId,'id'=>$id))->setField('status','4');
        return $result;
    }
}